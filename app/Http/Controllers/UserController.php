<?php

namespace App\Http\Controllers;

use App\Models\MitraProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Events\MemberCountUpdated;
use App\Events\PairingDownline;
use App\Services\BonusManager;
// use DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessPairingJob;
use App\Events\UserNotificationReceived;
use App\Models\ActivationPin;

// =======
use App\Models\Notification;
use App\Models\Package;
use App\Models\ProductPackage;
use Illuminate\Support\Facades\DB;
// >>>>>>> main
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        try {
            //code...
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
        } catch (\Exception $e) {
            \Log::error('Gagal akses halaman: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat akses halaman',
                'error' => $e->getMessage()
            ], 500);
        }
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

        DB::beginTransaction();
        try {
            // try {
            $user   = \App\Models\User::findOrFail($request->input('user_id'));
            $upline = \App\Models\User::findOrFail($validated['upline_id']);

            // ✅ JANGAN set $user->upline_id/position di sini.
            // Biarkan BonusManager yang validasi slot, pasang, bootstrap bagan, dan proses pairing.
            app(\App\Services\BonusManager::class)
                ->assignToUpline($user, $upline, $validated['position'], false); // false = kalau penuh, 422




            $user = User::findOrFail($request->input('user_id'));

            $user->upline_id = $validated['upline_id'];
            $user->position = $validated['position'];
            $user->save();


            DB::commit();

            ProcessPairingJob::dispatch($user);

            // Jika ingin tetap asynchronous
            Notification::create([
                'user_id' => $user->id,
                'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
                'url' => route('member'),
            ]);

            // Broadcast via Pusher
            event(new PairingDownline($user->id, [
                'type' => 'pairing_downProcessPairingJobline', // atau 'preregistration_received' jika Anda ingin beda
                'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
                'url' => route('member'),
                'created_at' => now()->toDateTimeString()
            ]));

            \Log::info('PIN Request Rejected and Notification Sent', [
                'user_id' => 'User berhasil dipasang ke tree dan pairing diproses.',
                'message' => $user->id,
            ]);


            return response()->json([
                'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
                'id' => $user->id,
                'name' => $user->username,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Gagal update dan pasang user', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
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
            'ref'              => ['required','string'],
            'pin_aktivasi'     => ['required','string'],

            'register_method'  => ['nullable','string'], // 'from_tree' atau null

            // Wajib hanya kalau from_tree (tanpa nullable)
            'tree_upline_id'   => ['required_if:register_method,from_tree','exists:users,id'],
            'tree_position'    => ['required_if:register_method,from_tree','in:left,right'],

            'name'             => ['required','string','max:255'],
            'username'         => ['required','alpha_dash','min:4','max:30','unique:users,username'],
            'email'            => ['nullable','email','max:255'],
            'no_telp'          => ['required','string','max:30'],
            'password'         => ['required','string','min:6','confirmed'],

            'no_ktp'           => ['nullable','string','max:50'],
            'jenis_kelamin'    => ['required','in:pria,wanita'],
            'tempat_lahir'     => ['required','string','max:100'],
            'tanggal_lahir'    => ['required','date'],
            'agama'            => ['required','in:islam,kristen,katolik,budha,hindu,lainnya'],
            'alamat'           => ['required','string','max:500'],
            'rt'               => ['nullable','string','max:10'],
            'rw'               => ['nullable','string','max:10'],
            'desa'             => ['required','string','max:100'],
            'kecamatan'        => ['required','string','max:100'],
            'kota'             => ['required','string','max:100'],
            'kode_pos'         => ['nullable','string','max:10'],

            'nama_rekening'    => ['required','string','max:150'],
            'nama_bank'        => ['required','string','max:150'],
            'nomor_rekening'   => ['required','string','max:50'],

            'nama_ahli_waris'      => ['nullable','string','max:150'],
            'hubungan_ahli_waris'  => ['nullable','string','max:100'],

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

        // 3) Ambil paket aktif (sebelum transaksi)
        // $package = Package::where('is_active', true)->inRandomOrder()->first();
        // if (!$package) {
        //     return response()->json([
        //         'message' => 'Tidak ada product package aktif.',
        //     ], 422);
        // }

        // 4) Transaksi: buat user, profil, klaim PIN + set package
        $user = DB::transaction(function () use ($validated, $sponsor) {
            $pin = ActivationPin::where('code', $validated['pin_aktivasi'])
                ->lockForUpdate()
                ->first();

            if (!$pin) {
                throw ValidationException::withMessages(['pin_aktivasi' => 'PIN tidak ditemukan.']);
            }
            if (!in_array($pin->status, ['unused','reserved'], true)) {
                throw ValidationException::withMessages(['pin_aktivasi' => 'PIN sudah terpakai / tidak aktif.']);
            }

            $user = User::create([
                'name'        => $validated['name'],
                'username'    => $validated['username'],
                'email'       => $validated['email'] ?? null,
                'no_telp'     => $validated['no_telp'],
                'password'    => Hash::make($validated['password']),
                'role'        => 'member',
                'status'      => 'active',
                'sponsor_id'  => $sponsor->id,
                'upline_id'   => null,
                'position'    => null,
            ]);

            MitraProfile::create([
                'user_id'             => $user->id,
                'no_ktp'              => $validated['no_ktp'] ?? null,
                'jenis_kelamin'       => $validated['jenis_kelamin'],
                'tempat_lahir'        => $validated['tempat_lahir'],
                'tanggal_lahir'       => $validated['tanggal_lahir'],
                'agama'               => $validated['agama'],
                'alamat'              => $validated['alamat'],
                'rt'                  => $validated['rt'] ?? null,
                'rw'                  => $validated['rw'] ?? null,
                'desa'                => $validated['desa'],
                'kecamatan'           => $validated['kecamatan'],
                'kota'                => $validated['kota'],
                'kode_pos'            => $validated['kode_pos'] ?? null,
                'nama_rekening'       => $validated['nama_rekening'],
                'nama_bank'           => $validated['nama_bank'],
                'nomor_rekening'      => $validated['nomor_rekening'],
                'nama_ahli_waris'     => $validated['nama_ahli_waris'] ?? null,
                'hubungan_ahli_waris' => $validated['hubungan_ahli_waris'] ?? null,
            ]);

            // Tandai PIN terpakai + set paket produk SEKALI DI SINI
            $pin->update([
                'status'              => 'used',
                'used_by'             => $user->id,
                // 'product_package_id'  => $package->id,
                'used_at'             => now(),
            ]);

            return $user;
        });

        // 5) Tentukan upline & posisi dari request
        if ($request->register_method === 'from_tree') {
            $upline   = User::findOrFail($validated['tree_upline_id']);
            $position = $validated['tree_position'];
        } else {
            $upline   = $sponsor;
            $position = $request->input('tree_position');
            if (!in_array($position, ['left','right'], true)) {
                throw ValidationException::withMessages([
                    'tree_position' => 'Posisi harus left atau right.'
                ]);
            }
        }

        // 6) Pasang ke tree (JANGAN set manual upline_id/position lagi)
        $bonus = app(\App\Services\BonusManager::class);
        $bonus->assignToUpline($user, $upline, $position, false);

        // Proses pairing via job (atau sinkron—pilih salah satu)
        // $bonus->processPairing($user);
        ProcessPairingJob::dispatch($user);

        // 7) Event & notifikasi
        event(new MemberCountUpdated(User::count()));

        Notification::create([
            'user_id' => $user->id,
            'message' => 'User berhasil dipasang ke tree dan pairing diproses.',
            'url'     => route('tree.index'),
        ]);

        event(new PairingDownline($user->id, [
            'type'       => 'pairing_downline',
            'message'    => 'User berhasil dipasang ke tree dan pairing diproses.',
            'url'        => route('tree.index'),
            'created_at' => now()->toDateTimeString()
        ]));

        $route   = $request->register_method === 'from_tree' ? route('tree.index') : route('users.downline');
        $message = $request->register_method === 'from_tree'
            ? 'User berhasil disimpan dan dipairing. Akun user telah aktif.'
            : 'User berhasil disimpan. Akun user telah aktif.';

        return response()->json([
            'success'  => $message,
            'redirect' => $route,
        ]);

    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Throwable $e) {
        \Log::error('Registration error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Terjadi kesalahan internal server. Silakan coba lagi nanti.'], 500);
    }
}


}
