<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Pendaftaran akun Non-Staff dari aplikasi mobile.
 *
 * TERPISAH dari {@see \App\Http\Requests\Auth\RegisterRequest} bukan karena
 * aturannya berbeda — keduanya identik — melainkan karena NAMA FIELD-nya
 * berbeda. Formulir web memakai kosakata basis data (`name`, `nomor_hp`,
 * `department_id`); API memakai kosakata yang sudah dipakai seluruh endpoint
 * mobile (`nama`, `no_hp`, `departemen` sebagai KODE, bukan id — lihat
 * UserResource). Memaksa aplikasi mengirim `department_id` berarti aplikasi
 * harus lebih dulu mengunduh daftar departemen hanya untuk menerjemahkan
 * "ICTMD" jadi angka.
 *
 * Terjemahannya dikerjakan `prepareForValidation()`, sehingga `rules()` tetap
 * memakai `exists:` yang sama dan pesan galatnya tetap satu bunyi.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Kode departemen ("ICTMD") → id. Kode tak dikenal jadi null → ditolak `required`. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'department_id' => \App\Models\Department::where('code', strtoupper((string) $this->input('departemen')))
                ->value('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'nrp' => ['required', 'string', 'max:50', 'unique:users,nrp'],
            // Jabatan yang diketik sendiri pendaftar (mis. "Magang"). Teks bebas,
            // murni informasi bagi penyetuju akun — bukan penentu hak akses.
            'jabatan' => ['nullable', 'string', 'max:100'],
            'no_hp' => ['nullable', 'string', 'max:20'],
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
            'department_id.required' => 'Departemen tidak dikenal. Silakan pilih ulang.',
        ];
    }
}
