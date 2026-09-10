<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', 'unique:users,nrp'],
            // Jabatan yang diisi sendiri pendaftar (mis. "Magang"). Teks bebas,
            // murni informasi untuk penyetuju akun — bukan penentu hak akses.
            'jabatan' => ['nullable', 'string', 'max:100'],
            'nomor_hp' => ['nullable', 'string', 'max:20'],
            // Departemen NONAKTIF ditolak, bukan sekadar disembunyikan dari
            // dropdown (Fase 5b): daftar pilihan bukan penjaga — id-nya bisa
            // dikirim langsung, dan pendaftar dari HP tak pernah melihat
            // dropdown itu sama sekali.
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'nrp.unique' => 'NRP ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'department_id.required' => 'Silakan pilih departemen.',
        ];
    }
}
