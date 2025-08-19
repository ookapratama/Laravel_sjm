<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MitraProfile;
use App\Models\UserBagan;
use App\Models\ActivationPin;
use App\Models\TempCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\NetworkPlacer;
use App\Services\BonusManager;

class TreeCloneController extends Controller
{
    /**
     * GET /pins/unused
     * Ambil PIN milik user login yang statusnya 'unused'
     */
 
        public function unusedPins(Request $r)
        {
            try {
                // Cek apakah kolomnya benar 'owner_id'
                // Jika di DB kamu namanya lain (mis: 'buyer_id', 'user_id'), ganti di sini.
                $pins = ActivationPin::query()
                    ->where('purchased_by', $r->user()->id)
                    ->where('status', 'unused')
                    ->orderBy('created_at', 'asc')
                    ->get(['code']);

                return response()->json(['ok' => true, 'pins' => $pins]);
            } catch (\Throwable $e) {
                \Log::error('unusedPins error', ['msg'=>$e->getMessage()]);
                return response()->json([
                    'ok' => false,
                    'error' => 'Server error: '.$e->getMessage()
                ], 500);
            }
        }
    

    /**
     * GET /tree/clone/preview
     * Preview kandidat username & sponsor_code berbasis base user (login/parent)
     * Query: count (int), base_user_id (opsional)
     */
    public function preview(Request $r)
    {
        $r->validate([
            'count'        => ['required','integer','min:1','max:100'],
            'base_user_id' => ['nullable','exists:users,id'],
        ]);

        $base = $r->filled('base_user_id') ? User::findOrFail($r->base_user_id) : $r->user();
        $list = [];
        $baseUsername   = $base->username;
        $baseSponsor    = $base->referral_code ?? $base->username; // fallback

        // Catatan: di preview ini kita "naikkan" berurutan supaya tampilan konsisten
        for ($i = 0; $i < (int)$r->count; $i++) {
            $nextU = $this->nextIncrement($baseUsername, 'username');
            $nextS = $this->nextIncrement($baseSponsor, 'referral_code');

            $list[] = ['username' => $nextU, 'referral_code' => $nextS];
            $baseUsername = $nextU;
            $baseSponsor  = $nextS;
        }

        return response()->json(['candidates' => $list]);
    }

    /**
     * POST /tree/clone
     * Body:
     * - parent_id (required, exists:users,id)
     * - position  (required: left|right)
     * - bagan     (optional, default 1)
     * - use_login (required:boolean) → true = base login; false = base parent
     * - pin_codes[] (required, minimal 1)
     */
    public function store(Request $r)
{
    $r->validate([
        'parent_id'   => ['required','exists:users,id'],
        'position'    => ['required','in:left,right'],
        'bagan'       => ['nullable','integer','in:1,2,3,4,5'], // dikirim UI tapi tidak dipakai langsung oleh BonusManager
        'use_login'   => ['required','boolean'],
        'pin_codes'   => ['required','array','min:1'],
        'pin_codes.*' => ['string','distinct'],
    ]);

    $auth    = $r->user();
    $pins    = (array)$r->pin_codes;
    $qty     = count($pins);
    $parent  = \App\Models\User::findOrFail($r->parent_id);
    $baseUser= $r->boolean('use_login') ? $auth : $parent;

    DB::transaction(function () use ($r, $auth, $pins, $qty, $baseUser, $parent) {
        // Lock PIN milik user login
        $pinRows = ActivationPin::whereIn('code', $pins)
            ->where('purchased_by', $auth->id)
            ->where('status', 'unused')
            ->lockForUpdate()
            ->get();

        if ($pinRows->count() !== $qty) {
            abort(422, 'Ada PIN tidak valid / bukan milik Anda / sudah dipakai.');
        }

        $baseProfile = \App\Models\MitraProfile::where('user_id', $baseUser->id)->first();

        foreach ($pinRows as $pin) {
            // 1) Username & sponsor_code unik (suffix angka)
            $newUsername = $this->nextIncrement($baseUser->username, 'username');
            $newSponsor  = $this->nextIncrement(($baseUser->referral_code ?? $baseUser->username), 'referral_code');

            // 2) Kredensial
            $passwordPlain = \Illuminate\Support\Str::random(10);

            // 3) Buat user baru (email/phone sama)
            $newUser = \App\Models\User::create([
                'name'         => $baseUser->name,
                'username'     => $newUsername,
                'email'        => $baseUser->email,
                'phone'        => $baseUser->phone,
                'password'     => bcrypt($passwordPlain),
                'referral_code' => $newSponsor,
            ]);

            // 4) Clone profil (opsional)
            if ($baseProfile) {
                MitraProfile::create([
                    'user_id'       => $newUser->id,
                    'no_ktp'        => $baseProfile->no_ktp,
                    'jenis_kelamin' => $baseProfile->jenis_kelamin,
                    'tempat_lahir'  => $baseProfile->tempat_lahir,
                    'tanggal_lahir' => $baseProfile->tanggal_lahir,
                    'alamat'        => $baseProfile->alamat,
                    'bank'          => $baseProfile->bank,
                    'nama_rekening' => $baseProfile->nama_rekening,
                    'no_rekening'   => $baseProfile->no_rekening,
                ]);
            }

            // 5) Consume PIN
            $pin->update([
                'status'  => 'used',
                'used_by' => $newUser->id,
                'used_at' => now(),
            ]);


           try {
                    app(\App\Services\BonusManager::class)->assignToUpline($newUser, $parent, $r->position, false);
                } catch (\InvalidArgumentException $e) {
                    // biarkan pending (upline_id null), beritahu ke UI
                    \Log::warning('Placement gagal (slot penuh): '.$e->getMessage());
                }
        }
    });

    return response()->json([
        'ok'      => true,
        'message' => "{$qty} ID berhasil di-clone & ditempatkan.",
    ]);
}


    /**
     * Menambahkan angka di belakang string dasar agar unik di kolom target.
     * - $target: 'username' | 'sponsor_code'
     * - base: 'artmedia'  -> 'artmedia1','artmedia2',...
     * - base: 'artmedia7' -> 'artmedia8',...
     * - batasi panjang ke 50 char (ubah sesuai DB Anda)
     */
    private function nextIncrement(string $base, string $target = 'username'): string
    {
        $col = in_array($target, ['username','sponsor_code']) ? $target : 'username';

        if (preg_match('/^(.*?)(\d+)$/', $base, $m)) {
            $prefix = $m[1];
            $start  = (int)$m[2];
        } else {
            $prefix = $base;
            $start  = 0;
        }

        $candidate = '';
        $i = $start;
        do {
            $i++;
            $candidate = $prefix.$i;

            // batasi panjang aman
            if (strlen($candidate) > 50) {
                $take = 50 - strlen((string)$i);
                $candidate = substr($prefix, 0, max(1,$take)).$i;
            }
        } while (User::where($col, $candidate)->exists());

        return $candidate;
    }
}
