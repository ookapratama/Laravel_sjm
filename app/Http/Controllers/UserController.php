<?php

namespace App\Http\Controllers;

use App\Models\MitraProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Events\MemberCountUpdated;
use App\Services\BonusManager;
use DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessPairingJob;
use App\Events\UserNotificationReceived;
use App\Models\ActivationPin;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $users = collect(DB::select("
            WITH RECURSIVE downlines AS (
                SELECT id, username,name, upline_id, sponsor_id,email,position
                FROM users
                WHERE upline_id = ?

                UNION ALL

                SELECT u.id, u.username,u.name, u.upline_id, u.sponsor_id,u.email,u.position
                FROM users u
                INNER JOIN downlines d ON u.upline_id = d.id
            )
            SELECT * FROM downlines
        ", [$userId]));
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        try {
            if ($request->upline_id) {
                $upline = User::find($request->upline_id);

                $usedPositions = User::where('upline_id', $upline->id)->pluck('position')->toArray();

                $autoPosition = null;
                if (!in_array('left', $usedPositions)) {
                    $autoPosition = 'left';
                } elseif (!in_array('right', $usedPositions)) {
                    $autoPosition = 'right';
                }

                $position = $request->position ?? $autoPosition;

                if (!$position) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'upline_id' => ['Upline sudah penuh. Silakan pilih upline lain.']
                        ]
                    ], 422);
                }

                if (in_array($position, $usedPositions)) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'upline_id' => ['Posisi "' . $position . '" sudah diisi.']
                        ]
                    ], 422);
                }

                $validated = $request->validate([
                    'name' => 'required',
                    'username' => 'required|unique:users',
                    'password' => 'nullable|confirmed|min:3',
                    'sponsor_id' => 'nullable|exists:users,id',
                    'upline_id' => 'required|exists:users,id',
                    'position' => 'nullable|in:left,right',
                    'joined_at' => 'nullable|date',
                    'tax_id' => 'nullable',
                    'bank_account' => 'nullable',
                    'address' => 'nullable',
                    'level' => 'nullable',
                    'no_telp' => 'nullable',

                ]);

                $validated['position'] = $position;
            } else {
                $validated = $request->validate([
                    'name' => 'required',
                    'username' => 'required|unique:users',
                    'password' => 'nullable|confirmed|min:3',
                    'sponsor_id' => 'nullable|exists:users,id',
                    'upline_id' => 'required|exists:users,id',
                    'position' => 'nullable|in:left,right',
                    'joined_at' => 'nullable|date',
                    'tax_id' => 'nullable',
                    'bank_account' => 'nullable',
                    'level' => 'nullable',
                    'address' => 'nullable',
                    'no_telp' => 'nullable',

                ]);
            }

            // Handle password hanya jika diisi
            if (!empty($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            } else {
                $validated['password'] = bcrypt('123456'); // atau generate random default
            }

            $validated['level'] = 1;

            DB::beginTransaction();

            $user = User::create($validated);

            if ($user->upline) {
                Log::info("Upline: " . ($user->upline->username ?? '-') . " | Posisi: " . $user->position);
                $user->upline->incrementLegCount($user->position);
            }
            // Notifikasi ke sponsor (referral)
            if ($user->sponsor_id) {
                $sponsor = $user->sponsor;
                $notif = [
                    'type' => 'new_referral',
                    'message' => "Member baru: {$user->name} bergabung melalui referral Anda.",
                    'url' => route('members.show', $user->id),
                    'created_at' => now()->toDateTimeString(),
                ];
                event(new UserNotificationReceived($sponsor->id, $notif));
            }
            // Proses bonus + refresh count up ke atas
            $bonusManager = new BonusManager();

            $current = $user;
            while ($current) {
                $bonusManager->processPairing($current);
                $current->refreshChildCounts();
                $current = $current->upline;
            }

            DB::commit();
            //BonusManager::processPairing($user);

            ProcessPairingJob::dispatch($user);
            // Trigger event jumlah member
            $totalMembers = User::count();
            event(new MemberCountUpdated($totalMembers));

            return response()->json([
                'message' => 'User berhasil disimpan',
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Gagal menyimpan user: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function edit($id)
    {
        return response()->json(User::findOrFail($id));
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'upline_id' => 'required|exists:users,id',
        'position'  => 'required|in:left,right',
    ]);

    try {
        $user   = \App\Models\User::findOrFail($request->input('user_id'));
        $upline = \App\Models\User::findOrFail($validated['upline_id']);

        // ✅ JANGAN set $user->upline_id/position di sini.
        // Biarkan BonusManager yang validasi slot, pasang, bootstrap bagan, dan proses pairing.
        app(\App\Services\BonusManager::class)
            ->assignToUpline($user, $upline, $validated['position'], false); // false = kalau penuh, 422

        // (opsional) kalau kamu ingin pairing via job, matikan process() di service
        // lalu baru dispatch job di sini. Kalau tidak, JOB ini tidak perlu.
        // ProcessPairingJob::dispatch($user);

        return response()->json([
            'ok'      => true,
            'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
            'id'      => $user->id,
            'name'    => $user->username,
        ]);

    } catch (\InvalidArgumentException $e) {
        // dari guard: slot penuh / user sudah terpasang
        return response()->json(['ok'=>false, 'message'=>$e->getMessage()], 422);
    } catch (\Throwable $e) {
        \Log::error('❌ Gagal update/pasang user', ['error' => $e->getMessage()]);
        return response()->json(['ok'=>false, 'message'=>'Terjadi kesalahan.'], 500);
    }
}


    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        $totalMembers = User::count();
        event(new MemberCountUpdated($totalMembers));

        return response()->json(['message' => 'User deleted']);
    }

    public function store_member(Request $request)
    {
        try {
            // 1) Validasi input
            $validated = $request->validate([
                'ref'              => ['required', 'string'],
                'pin_aktivasi'     => ['required', 'string'],

                'name'             => ['required', 'string', 'max:255'],
                'username'         => ['required', 'alpha_dash', 'min:4', 'max:30', 'unique:users,username'],
                'email'            => ['nullable', 'email', 'max:255'],
                'no_telp'            => ['required', 'string', 'max:30'],
                'password'         => ['required', 'string', 'min:6', 'confirmed'],

                'no_ktp'           => ['nullable', 'string', 'max:50'],
                'jenis_kelamin'    => ['required', 'in:pria,wanita'],
                'tempat_lahir'     => ['required', 'string', 'max:100'],
                'tanggal_lahir'    => ['required', 'date'],
                'agama'            => ['required', 'in:islam,kristen,katolik,budha,hindu,lainnya'],
                'alamat'           => ['required', 'string', 'max:500'],
                'rt'               => ['nullable', 'string', 'max:10'],
                'rw'               => ['nullable', 'string', 'max:10'],
                'desa'             => ['required', 'string', 'max:100'],
                'kecamatan'        => ['required', 'string', 'max:100'],
                'kota'             => ['required', 'string', 'max:100'],
                'kode_pos'         => ['nullable', 'string', 'max:10'],

                'nama_rekening'    => ['required', 'string', 'max:150'],
                'nama_bank'        => ['required', 'string', 'max:150'],
                'nomor_rekening'   => ['required', 'string', 'max:50'],

                'nama_ahli_waris'      => ['nullable', 'string', 'max:150'],
                'hubungan_ahli_waris'  => ['nullable', 'string', 'max:100'],

                'agree'            => ['accepted'],
            ], [
                'agree.accepted' => 'Anda harus menyetujui syarat & ketentuan.',
            ]);

            // 2) Validasi sponsor
            $sponsor = User::where('referral_code', $validated['ref'])
                ->orWhere('username', $validated['ref'])
                ->first();

            if (!$sponsor) {
                return response()->json([
                    'message' => 'Validasi gagal.',
                    'errors'  => ['ref' => ['Kode referal tidak valid.']],
                ], 422);
            }

            // 3) Transaksi: klaim PIN + buat user + profil
            $user = DB::transaction(function () use ($validated, $sponsor) {
                // 3a) Ambil & kunci PIN
                $pin = ActivationPin::where('code', $validated['pin_aktivasi'])
                    ->lockForUpdate()
                    ->first();

                if (!$pin) {
                    throw ValidationException::withMessages(['pin_aktivasi' => 'PIN tidak ditemukan.']);
                }
                if (!in_array($pin->status, ['unused', 'reserved'], true)) {
                    throw ValidationException::withMessages(['pin_aktivasi' => 'PIN sudah terpakai / tidak aktif.']);
                }
                // (opsional) kalau reserved hanya boleh dipakai pihak tertentu
                // if ($pin->status === 'reserved' && $pin->purchased_by && (int)$pin->purchased_by !== (int)$sponsor->id) {
                //     throw ValidationException::withMessages(['pin_aktivasi' => 'PIN ini sedang di-reserve untuk pengguna lain.']);
                // }
                // 3b) Buat user (referral_code diisi otomatis oleh model User::booted())
                $user = User::create([
                    'name'       => $validated['name'],
                    'username'   => $validated['username'],
                    'email'      => $validated['email'] ?? null,
                    'no_telp'      => $validated['no_telp'],
                    'password'   => Hash::make($validated['password']),
                    'role'       => 'member',
                    'status'     => 'active',
                    'sponsor_id' => $sponsor->id,
                    'upline_id'  => $sponsor->id,
                ]);

                // 3c) Buat mitra profile
                MitraProfile::create([
                    'user_id'            => $user->id,
                    'no_ktp'             => $validated['no_ktp'] ?? null,
                    'jenis_kelamin'      => $validated['jenis_kelamin'],
                    'tempat_lahir'       => $validated['tempat_lahir'],
                    'tanggal_lahir'      => $validated['tanggal_lahir'],
                    'agama'              => $validated['agama'],
                    'alamat'             => $validated['alamat'],
                    'rt'                 => $validated['rt'] ?? null,
                    'rw'                 => $validated['rw'] ?? null,
                    'desa'               => $validated['desa'],
                    'kecamatan'          => $validated['kecamatan'],
                    'kota'               => $validated['kota'],
                    'kode_pos'           => $validated['kode_pos'] ?? null,
                    'nama_rekening'      => $validated['nama_rekening'],
                    'nama_bank'          => $validated['nama_bank'],
                    'nomor_rekening'     => $validated['nomor_rekening'],
                    'nama_ahli_waris'    => $validated['nama_ahli_waris'] ?? null,
                    'hubungan_ahli_waris' => $validated['hubungan_ahli_waris'] ?? null,
                ]);

                // 3d) Tandai PIN terpakai
                $pin->status  = 'used';
                $pin->used_by = $user->id;
                $pin->used_at = now();
                $pin->save();

                return $user;
            });

            // 4) Auto-login dan balas JSON
            // auth()->login($user);

            return response()->json([
                'success'  => 'User berhasil disimpan. Akun user telah aktif.',
                'redirect' => route('users.downline'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan internal server. Silakan coba lagi nanti.'
            ], 500);
        }
    }
}
