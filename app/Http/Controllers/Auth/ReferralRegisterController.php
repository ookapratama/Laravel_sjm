<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\MitraProfile;
use App\Models\ActivationPin; // -> table: activation_pins (atau pin_codes)

class ReferralRegisterController extends Controller
{
    public function create(Request $request)
    {
        $ref = $request->query('ref');
        $sponsor = null;

        if ($ref) {
            $sponsor = User::where('referral_code', $ref)
                ->orWhere('username', $ref)
                ->first();
        }

        return view('auth.referral-register', [
            'ref' => $ref,
            'sponsor' => $sponsor,
        ]);
    }

    public function store(Request $request)
    {
        try {
            // 1) Validasi input
            $validated = $request->validate([
                'ref'              => ['required', 'string'],
                'pin_aktivasi'     => ['required', 'string'],

                'name'             => ['required', 'string', 'max:255'],
                'username'         => ['required', 'alpha_dash', 'min:4', 'max:30', 'unique:users,username'],
                'email'            => ['nullable', 'email', 'max:255', 'unique:users,email'],
                'phone'            => ['required', 'string', 'max:30'],
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
                    'phone'      => $validated['phone'],
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
            auth()->login($user);

            return response()->json([
                'success'  => 'Registrasi berhasil. Akun Anda aktif.',
                'redirect' => route('member'),
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
