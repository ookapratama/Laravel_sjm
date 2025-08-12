<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\MitraProfile;

class ChangeCredentialsController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        // Gunakan user langsung jika preRegistration tidak ada
        $pre = $user->preRegistration ?? $user;

        return view('auth.change-credentials', compact('pre', 'user'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'username' => 'required|unique:users,username,' . $user->id,
            'password' => 'required|confirmed|min:6',

            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',

            'no_ktp' => 'nullable|string',
            'jenis_kelamin' => 'required|in:pria,wanita',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'agama' => 'required|in:islam,kristen,katolik,budha,hindu,lainnya',
            'alamat' => 'required|string',
            'rt' => 'nullable|string',
            'rw' => 'nullable|string',
            'desa' => 'required|string',
            'kecamatan' => 'required|string',
            'kota' => 'required|string',
            'kode_pos' => 'nullable|string',

            'nama_rekening' => 'required|string',
            'nama_bank' => 'required|string',
            'nomor_rekening' => 'required|string',

            'nama_ahli_waris' => 'nullable|string',
            'hubungan_ahli_waris' => 'nullable|string',
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors'  => $v->errors(),
            ], 422);
        }

        // Update user data
        $user->update([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'email' => $request->email,
            'no_telp' => $request->phone, // Sesuaikan dengan nama kolom di database
            'must_change_credentials' => false,
        ]);

        // Update atau create mitra profile
        $user->mitraProfile()->updateOrCreate(
            ['user_id' => $user->id], // Pastikan user_id sesuai
            [
                'no_ktp' => $request->no_ktp,
                'jenis_kelamin' => $request->jenis_kelamin,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'agama' => $request->agama,
                'alamat' => $request->alamat,
                'rt' => $request->rt,
                'rw' => $request->rw,
                'desa' => $request->desa,
                'kecamatan' => $request->kecamatan,
                'kota' => $request->kota,
                'kode_pos' => $request->kode_pos,
                'nama_rekening' => $request->nama_rekening,
                'nama_bank' => $request->nama_bank,
                'nomor_rekening' => $request->nomor_rekening,
                'nama_ahli_waris' => $request->nama_ahli_waris,
                'hubungan_ahli_waris' => $request->hubungan_ahli_waris,
            ]
        );

        $redirect = match ($user->role) {
            'admin' => route('admin'),
            'super-admin' => route('super-admin'),
            'finance' => route('finance'),
            'member' => route('member'),
            default => route('dashboard'),
        };

        return response()->json([
            'success' => 'Username dan password berhasil diperbarui.',
            'redirect' => $redirect,
        ]);
    }
}
