<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MitraProfile;
use App\Models\UserBagan;
use App\Models\ActivationPin;
use App\Models\Package;
use App\Models\ProductPackage;
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
            
           $userId = $r->user()->id;

$pins = ActivationPin::query()
    ->where('purchased_by', $userId)
    ->orWhere(function ($q) use ($userId) {
        $q->where('transferred_to', $userId)
          ->where('status', 'unused');
    })
    ->orWhere('status', 'transferred')
    ->orderBy('created_at', 'asc')
    ->pluck('code');
            return response()->json(['ok' => true, 'pins' => $pins]);
        } catch (\Throwable $e) {
            \Log::error('unusedPins error', ['msg' => $e->getMessage()]);
            return response()->json([
                'ok' => false,
                'error' => 'Server error: ' . $e->getMessage()
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
            'count'        => ['required', 'integer', 'min:1', 'max:100'],
            'base_user_id' => ['nullable', 'exists:users,id'],
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
    // 1) Validasi request
    $r->validate([
        'parent_id'   => ['required','exists:users,id'],
        'position'    => ['required','in:left,right'],
        'bagan'       => ['nullable','integer','in:1,2,3,4,5'], // tidak dipakai langsung
        'use_login'   => ['required','boolean'],
        'pin_codes'   => ['required','array','min:1'],
        'pin_codes.*' => ['string','distinct'],
    ]);

    $auth     = $r->user();
    $pins     = array_values((array) $r->input('pin_codes', []));
    $qty      = count($pins);
    $parent   = User::findOrFail($r->parent_id);
    $baseUser = $r->boolean('use_login') ? $auth : $parent;

    // 2) Pastikan ada package aktif
    $package = Package::where('is_active', true)->inRandomOrder()->first();
    if (!$package) {
        return response()->json([
            'ok'      => false,
            'message' => 'Tidak ada product package aktif.',
        ], 422);
    }

    // 3) Transaksi: lock PIN, buat user2 baru, konsumsi PIN, tempatkan ke tree
    DB::transaction(function () use ($r, $auth, $pins, $qty, $baseUser, $parent, $package) {

        // 3a) Lock PIN milik user login (pembeli atau penerima transfer) dan status valid
        $pinRows = ActivationPin::whereIn('code', $pins)
            ->where(function ($q) use ($auth) {
                $q->where('purchased_by', $auth->id)
                  ->orWhere('transferred_to', $auth->id);
            })
            ->whereIn('status', ['unused','transferred'])
            ->lockForUpdate()
            ->get();

        if ($pinRows->count() !== $qty) {
            $found = $pinRows->pluck('code')->all();
            $missing = collect($pins)->diff($found)->values()->all();
            throw ValidationException::withMessages([
                'pin_codes' => ['Ada PIN tidak valid / bukan milik Anda / tidak tersedia: '.implode(', ', $missing)]
            ]);
        }

        // 3b) Ambil profil dasar (kalau ada)
        $baseProfile = MitraProfile::where('user_id', $baseUser->id)->first();

        // 3c) Loop tiap PIN → buat user baru, clone profil (opsional), konsumsi PIN, tempatkan ke tree
        foreach ($pinRows as $pin) {
            // Username & referral unik (pakai helper kamu)
            $newUsername = $this->nextIncrement($baseUser->username, 'username');
            $newSponsor  = $this->nextIncrement($baseUser->referral_code ?? $baseUser->username, 'referral_code');

            // Password: pakai yang sudah di-hash dari baseUser (as is)
            $newUser = User::create([
                'name'          => $baseUser->name,
                'username'      => $newUsername,
                'email'         => $baseUser->email,
                'no_telp'       => $baseUser->no_telp,
                'password'      => $baseUser->password, // diasumsikan sudah hashed
                'referral_code' => $newSponsor,
                'sponsor_id'    => $auth->id,
                'role'          => 'member',
                'status'        => 'active',
            ]);

            // Clone profil bila ada
            if ($baseProfile) {
                MitraProfile::create([
                    'user_id'             => $newUser->id,
                    'no_ktp'              => $baseProfile->no_ktp,
                    'jenis_kelamin'       => $baseProfile->jenis_kelamin,
                    'agama'               => $baseProfile->agama,
                    'tempat_lahir'        => $baseProfile->tempat_lahir,
                    'tanggal_lahir'       => $baseProfile->tanggal_lahir,
                    'alamat'              => $baseProfile->alamat,
                    'nama_rekening'       => $baseProfile->nama_rekening,
                    'nama_bank'           => $baseProfile->nama_bank,
                    'nomor_rekening'      => $baseProfile->nomor_rekening,
                    'nama_ahli_waris'     => $baseProfile->nama_ahli_waris,
                    'hubungan_ahli_waris' => $baseProfile->hubungan_ahli_waris,
                    'rt'                  => $baseProfile->rt,
                    'rw'                  => $baseProfile->rw,
                    'desa'                => $baseProfile->desa,
                    'kecamatan'           => $baseProfile->kecamatan,
                    'kota'                => $baseProfile->kota,
                    'kode_pos'            => $baseProfile->kode_pos,
                ]);
            }

            // Konsumsi PIN + set paket
            $pin->update([
                'status'             => 'used',
                'used_by'            => $newUser->id,
                'product_package_id' => $package->id,
                'used_at'            => now(),
            ]);

            // Tempatkan ke tree (jika slot penuh, biarkan pending & log)
            try {
                app(\App\Services\BonusManager::class)->assignToUpline($newUser, $parent, $r->position, false);
            } catch (\InvalidArgumentException $e) {
                \Log::warning('Placement gagal (slot penuh)', [
                    'msg'      => $e->getMessage(),
                    'user_id'  => $newUser->id,
                    'parent'   => $parent->id,
                    'position' => $r->position,
                ]);
            }
        }
    });

    return response()->json([
        'ok'      => true,
        'message' => "{$qty} ID berhasil di-clone & ditempatkan.",
    ]);
}

    private function nextIncrement(string $base, string $target = 'username'): string
    {
        $col = in_array($target, ['username', 'sponsor_code']) ? $target : 'username';

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
            $candidate = $prefix . $i;

            // batasi panjang aman
            if (strlen($candidate) > 50) {
                $take = 50 - strlen((string)$i);
                $candidate = substr($prefix, 0, max(1, $take)) . $i;
            }
        } while (User::where($col, $candidate)->exists());

        return $candidate;
    }
}
