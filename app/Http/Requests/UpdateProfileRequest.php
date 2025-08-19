<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
{
    return [
        'username'        => ['required','string','min:3','max:30','alpha_dash','unique:users,username,'.auth()->id()],
        'no_telp'         => ['nullable','string','max:20'],      // <<< ganti dari 'phone'
        'address'         => ['nullable','string','max:500'],     // disimpan ke mitra.alamat

        // bank (hanya dipakai jika masih kosong di DB)
        'nama_bank'       => ['nullable','string','max:100'],
        'nomor_rekening'  => ['nullable','string','max:50'],
        'nama_rekening'   => ['nullable','string','max:120'],
    ];
}


    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip dan garis bawah.',
        ];
    }
}
