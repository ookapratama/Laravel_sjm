<?php

// app/Http/Controllers/Auth/ReferralRegisterController.php
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
// Ganti model/tablename PIN sesuai sistem Anda
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

    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:4|max:20|regex:/^[a-zA-Z0-9_]+$/'
        ]);

        $username = $request->username;

        // Check if username exists in database
        $exists = User::where('username', $username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Username sudah terpakai' : 'Username tersedia'
        ]);
    }

    // RegisterController.php

    public function checkPin(Request $request)
    {
        $request->validate([
            'pin_aktivasi' => 'required|string|min:8|max:16|regex:/^[A-Z0-9]+$/'
        ]);

        $pin = $request->pin_aktivasi;

        // Cek apakah PIN ada di database dan belum digunakan
        // Sesuaikan dengan struktur database Anda
        $pinRecord = ActivationPin::where('code', $pin)
            ->where('status', 'unused') // atau 'unused'
            ->first();

        if ($pinRecord) {
            return response()->json([
                'valid' => true,
                'message' => 'PIN valid dan tersedia',
                'info' => "Nilai PIN: Rp " . number_format($pinRecord->price ?? 0, 0, ',', '.')
            ]);
        }

        // Cek apakah PIN sudah digunakan
        $usedPin = ActivationPin::where('code', $pin)
            ->where('status', 'used')
            ->first();

        if ($usedPin) {
            return response()->json([
                'valid' => false,
                'message' => 'PIN sudah pernah digunakan'
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'PIN tidak ditemukan atau tidak valid'
        ]);
    }

    public function checkSponsor(Request $request)
    {
        $request->validate([
            'sponsor_code' => 'required|string|min:3|max:20|regex:/^[A-Za-z0-9]+$/'
        ]);

        $sponsorCode = $request->sponsor_code;

        // Cek apakah sponsor ada di database
        // Sesuaikan dengan struktur database Anda
        $sponsor = User::where('username', $sponsorCode)
            ->orWhere('referral_code', $sponsorCode)
            ->where('is_active', '1')
            ->first();

        if ($sponsor) {
            return response()->json([
                'valid' => true,
                'message' => 'Sponsor ditemukan',
                'sponsor_info' => [
                    'name' => $sponsor->name,
                    'member_id' => $sponsor->id ?? $sponsor->username,
                    'level' => $sponsor->level ?? 'Member',
                    'join_date' => $sponsor->created_at->format('d/m/Y')
                ]
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Kode sponsor tidak ditemukan'
        ]);
    }

    // RegisterController.php

    public function checkWhatsApp(Request $request)
    {
        $request->validate([
            'no_telp' => 'required|string'
        ]);

        $no_telp = $request->no_telp;

        // Clean and format no_telp number
        $cleanno_telp = preg_replace('/\D/', '', $no_telp);

        // Convert to international format
        if (substr($cleanno_telp, 0, 2) === '62') {
            $no_telpNumber = $cleanno_telp;
        } elseif (substr($cleanno_telp, 0, 1) === '0') {
            $no_telpNumber = '62' . substr($cleanno_telp, 1);
        } else {
            return response()->json([
                'valid' => false,
                'message' => 'Format nomor tidak valid untuk Indonesia'
            ]);
        }

        // Validate length (Indonesian mobile numbers)
        if (strlen($no_telpNumber) < 10 || strlen($no_telpNumber) > 15) {
            return response()->json([
                'valid' => false,
                'message' => 'Panjang nomor tidak valid (harus 10-13 digit)'
            ]);
        }

        // Check if no_telp already exists in database
        // $existingUser = User::where('no_telp', $no_telpNumber)
        //     ->orWhere('no_telp', $no_telp)
        //     ->orWhere('no_telp', '0' . substr($no_telpNumber, 2))
        //     ->first();

        // if ($existingUser) {
        //     return response()->json([
        //         'valid' => false,
        //         'message' => 'Nomor sudah terdaftar di sistem',
        //         'reason' => 'already_registered'
        //     ]);
        // }

        // Check if it's a valid Indonesian mobile number
        $validPrefixes = [
            '628111',
            '628112',
            '628113',
            '628114',
            '628115',
            '628116',
            '628117',
            '628118',
            '628119', // Telkomsel
            '628181',
            '628182',
            '628183',
            '628184',
            '628185',
            '628186',
            '628187',
            '628188',
            '628189',
            '628211',
            '628212',
            '628213',
            '628214',
            '628215',
            '628216',
            '628217',
            '628218',
            '628219',
            '628121',
            '628122',
            '628123',
            '628124',
            '628125',
            '628126',
            '628127',
            '628128',
            '628129',
            '628131',
            '628132',
            '628133',
            '628134',
            '628135',
            '628136',
            '628137',
            '628138',
            '628139',
            '628141',
            '628142',
            '628143',
            '628144',
            '628145',
            '628146',
            '628147',
            '628148',
            '628149',
            '628151',
            '628152',
            '628153',
            '628154',
            '628155',
            '628156',
            '628157',
            '628158',
            '628159',
            '628161',
            '628162',
            '628163',
            '628164',
            '628165',
            '628166',
            '628167',
            '628168',
            '628169',
            '628171',
            '628172',
            '628173',
            '628174',
            '628175',
            '628176',
            '628177',
            '628178',
            '628179',
            '628561',
            '628562',
            '628563',
            '628564',
            '628565',
            '628566',
            '628567',
            '628568',
            '628569', // Indosat
            '628571',
            '628572',
            '628573',
            '628574',
            '628575',
            '628576',
            '628577',
            '628578',
            '628579',
            '628581',
            '628582',
            '628583',
            '628584',
            '628585',
            '628586',
            '628587',
            '628588',
            '628589',
            '628591',
            '628592',
            '628593',
            '628594',
            '628595',
            '628596',
            '628597',
            '628598',
            '628599',
            '628381',
            '628382',
            '628383',
            '628384',
            '628385',
            '628386',
            '628387',
            '628388',
            '628389', // Three
            '628961',
            '628962',
            '628963',
            '628964',
            '628965',
            '628966',
            '628967',
            '628968',
            '628969',
            '628971',
            '628972',
            '628973',
            '628974',
            '628975',
            '628976',
            '628977',
            '628978',
            '628979',
            '628981',
            '628982',
            '628983',
            '628984',
            '628985',
            '628986',
            '628987',
            '628988',
            '628989',
            '628991',
            '628992',
            '628993',
            '628994',
            '628995',
            '628996',
            '628997',
            '628998',
            '628999'
        ];

        $prefix4 = substr($no_telpNumber, 0, 6);
        $isValidPrefix = in_array($prefix4, $validPrefixes);

        
        // dump($prefix4);
        // dump($isValidPrefix);
        // dump($validPrefixes);
        // dd('stop');
        if (!$isValidPrefix) {
            return response()->json([
                'valid' => false,
                'message' => 'Nomor bukan provider seluler Indonesia yang valid',
                'reason' => 'invalid_provider'
            ]);
        }

        // Basic WhatsApp format validation (always valid if reach here)
        return response()->json([
            'valid' => true,
            'message' => 'Nomor WhatsApp valid',
            'info' => 'Format: +' . $no_telpNumber . ' | Provider: ' . $this->getProvider($prefix4)
        ]);
    }

    private function getProvider($prefix)
    {
        if (in_array($prefix, ['8111', '8112', '8113', '8121', '8122', '8151', '8152', '8153'])) {
            return 'Telkomsel';
        } elseif (in_array($prefix, ['8561', '8562', '8571', '8572', '8581', '8582'])) {
            return 'Indosat';
        } elseif (in_array($prefix, ['8381', '8382', '8383', '8961', '8962', '8971', '8972'])) {
            return 'Three';
        }
        return 'Lainnya';
    }
}
