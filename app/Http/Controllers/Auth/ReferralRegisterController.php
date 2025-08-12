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

        // Clean and format phone number
        $cleanPhone = preg_replace('/\D/', '', $no_telp);

        // Convert to international format
        if (substr($cleanPhone, 0, 2) === '62') {
            $phoneNumber = $cleanPhone;
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $phoneNumber = '62' . substr($cleanPhone, 1);
        } else {
            return response()->json([
                'valid' => false,
                'message' => 'Format nomor tidak valid untuk Indonesia'
            ]);
        }

        // Validate length (Indonesian mobile numbers: 62 + 9-12 digits)
        if (strlen($phoneNumber) < 11 || strlen($phoneNumber) > 14) {
            return response()->json([
                'valid' => false,
                'message' => 'Panjang nomor tidak valid (harus 9-12 digit setelah +62)'
            ]);
        }

        // Check if phone already exists in database (uncomment if needed)
        // $existingUser = User::where('no_telp', $phoneNumber)
        //     ->orWhere('no_telp', $no_telp)
        //     ->orWhere('no_telp', '0' . substr($phoneNumber, 2))
        //     ->first();

        // if ($existingUser) {
        //     return response()->json([
        //         'valid' => false,
        //         'message' => 'Nomor sudah terdaftar di sistem',
        //         'reason' => 'already_registered'
        //     ]);
        // }

        // Get first 3-4 digits after 62 for provider validation
        $mobileNumber = substr($phoneNumber, 2); // Remove '62'
        $provider = $this->getIndonesianProvider($mobileNumber);

        if (!$provider) {
            return response()->json([
                'valid' => false,
                'message' => 'Nomor bukan provider seluler Indonesia yang valid',
                'reason' => 'invalid_provider'
            ]);
        }

        // All validations passed
        return response()->json([
            'valid' => true,
            'message' => 'Nomor WhatsApp valid',
            'info' => 'Format: +' . $phoneNumber . ' | Provider: ' . $provider
        ]);
    }

    /**
     * Get Indonesian mobile provider based on number prefix
     */
    private function getIndonesianProvider($mobileNumber)
    {
        // Telkomsel prefixes
        $telkomselPrefixes = [
            // Kartu Halo, simPATI, Kartu As
            '811',
            '812',
            '813',
            '814',
            '815',
            '816',
            '817',
            '818',
            '819',
            '821',
            '822',
            '823',
            '824',
            '825',
            '826',
            '827',
            '828',
            '829',
            '851',
            '852',
            '853'
        ];

        // Indosat Ooredoo prefixes
        $indosatPrefixes = [
            // IM3, Mentari, Matrix
            '814',
            '815',
            '816',
            '855',
            '856',
            '857',
            '858',
            '838' // Matrix Ooredoo
        ];

        // XL Axiata prefixes
        $xlPrefixes = [
            '817',
            '818',
            '819',
            '859',
            '877',
            '878'
        ];

        // Tri (3) prefixes
        $triPrefixes = [
            '895',
            '896',
            '897',
            '898',
            '899'
        ];

        // Smartfren prefixes
        $smartfrenPrefixes = [
            '881',
            '882',
            '883',
            '884',
            '885',
            '886',
            '887',
            '888'
        ];

        // Get first 3 digits
        $prefix3 = substr($mobileNumber, 0, 3);

        // Check each provider
        if (in_array($prefix3, $telkomselPrefixes)) {
            return 'Telkomsel';
        }

        if (in_array($prefix3, $indosatPrefixes)) {
            return 'Indosat Ooredoo';
        }

        if (in_array($prefix3, $xlPrefixes)) {
            return 'XL Axiata';
        }

        if (in_array($prefix3, $triPrefixes)) {
            return 'Tri (3)';
        }

        if (in_array($prefix3, $smartfrenPrefixes)) {
            return 'Smartfren';
        }

        // Extended validation for more comprehensive coverage
        return $this->getExtendedProviderValidation($mobileNumber);
    }

    /**
     * Extended provider validation for comprehensive coverage
     */
    private function getExtendedProviderValidation($mobileNumber)
    {
        $first2 = substr($mobileNumber, 0, 2);
        $first3 = substr($mobileNumber, 0, 3);

        // More comprehensive provider patterns
        $providerPatterns = [
            // Telkomsel (most comprehensive)
            'Telkomsel' => [
                // Kartu Halo
                '811',
                '812',
                '813',
                '821',
                '822',
                '823',
                '851',
                '852',
                '853',
                // simPATI
                '812',
                '813',
                '821',
                '822',
                '823',
                // Kartu As
                '823',
                '853'
            ],

            // Indosat Ooredoo
            'Indosat Ooredoo' => [
                '814',
                '815',
                '816',
                '855',
                '856',
                '857',
                '858',
                '838' // Matrix
            ],

            // XL Axiata
            'XL Axiata' => [
                '817',
                '818',
                '819',
                '859',
                '877',
                '878'
            ],

            // Tri (3)
            'Tri (3)' => [
                '895',
                '896',
                '897',
                '898',
                '899'
            ],

            // Smartfren
            'Smartfren' => [
                '881',
                '882',
                '883',
                '884',
                '885',
                '886',
                '887',
                '888'
            ],

            // By.U (Telkomsel sub-brand)
            'By.U' => [
                '851',
                '852',
                '853'
            ]
        ];

        foreach ($providerPatterns as $provider => $prefixes) {
            if (in_array($first3, $prefixes)) {
                return $provider;
            }
        }

        // Fallback: Check if it's a valid Indonesian mobile pattern
        if ($this->isValidIndonesianMobile($mobileNumber)) {
            return 'Provider Indonesia Lainnya';
        }

        return null; // Invalid
    }

    /**
     * Check if number follows Indonesian mobile pattern
     */
    private function isValidIndonesianMobile($mobileNumber)
    {
        // Indonesian mobile numbers typically start with 8
        if (!str_starts_with($mobileNumber, '8')) {
            return false;
        }

        // Length should be 9-12 digits
        if (strlen($mobileNumber) < 9 || strlen($mobileNumber) > 12) {
            return false;
        }

        // Should not start with patterns that are not mobile
        $invalidStarts = ['80', '804']; // These are typically landline or special numbers

        foreach ($invalidStarts as $invalid) {
            if (str_starts_with($mobileNumber, $invalid)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Alternative: Simple validation (if you want to allow all Indonesian mobile numbers)
     */
    public function checkWhatsAppSimple(Request $request)
    {
        $request->validate([
            'no_telp' => 'required|string'
        ]);

        $no_telp = $request->no_telp;
        $cleanPhone = preg_replace('/\D/', '', $no_telp);

        // Convert to international format
        if (substr($cleanPhone, 0, 2) === '62') {
            $phoneNumber = $cleanPhone;
        } elseif (substr($cleanPhone, 0, 1) === '0') {
            $phoneNumber = '62' . substr($cleanPhone, 1);
        } else {
            return response()->json([
                'valid' => false,
                'message' => 'Format nomor tidak valid untuk Indonesia'
            ]);
        }

        // Simple validation: Check length and starts with 628
        if (strlen($phoneNumber) >= 11 && strlen($phoneNumber) <= 14 && str_starts_with($phoneNumber, '628')) {
            return response()->json([
                'valid' => true,
                'message' => 'Nomor WhatsApp valid',
                'info' => 'Format: +' . $phoneNumber
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Format nomor tidak valid untuk WhatsApp Indonesia'
        ]);
    }

    /**
     * Test method to validate various Indonesian numbers
     */
    public function testIndonesianNumbers()
    {
        $testNumbers = [
            '08123456789',    // Telkomsel
            '081234567890',   // Telkomsel
            '085612345678',   // Indosat
            '087712345678',   // XL
            '089612345678',   // Tri
            '088812345678',   // Smartfren
            '628123456789',   // International format
            '+628123456789',  // With plus
            '021234567',      // Invalid (landline)
            '08012345678',    // Invalid pattern
        ];

        $results = [];

        foreach ($testNumbers as $number) {
            $request = new \Illuminate\Http\Request(['no_telp' => $number]);
            $response = $this->checkWhatsApp($request);
            $results[$number] = $response->getData(true);
        }

        return response()->json($results);
    }
}
