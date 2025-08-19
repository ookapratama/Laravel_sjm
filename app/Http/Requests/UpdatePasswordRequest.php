<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'old_password'              => ['required'],
            'new_password'              => ['required','string','min:4','max:72','confirmed'],
            'new_password_confirmation' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
            'new_password.min'       => 'Password minimal 4 karakter.',
        ];
    }
}
