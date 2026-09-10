<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/** Validasi login dari aplikasi mobile. */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // login memang terbuka untuk tamu
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nrp' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nrp.required' => 'NRP wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }
}
