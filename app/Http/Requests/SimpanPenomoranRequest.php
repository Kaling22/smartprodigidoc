<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validasi setelan penomoran site (PLAN-AKSES-v8 Fase 5a).
 *
 * Dua isian saja, tapi keduanya masuk ke NOMOR DOKUMEN MUTU — rujukan yang
 * dipakai di luar sistem (audit, cetakan di lapangan, dokumen lain). Karena itu
 * bentuknya dijaga ketat di sini, bukan dipercayakan ke layar.
 *
 * `prepareForValidation()` MENAIKKAN huruf keduanya lebih dulu, bukan menolak
 * huruf kecil. Nomor dokumen selalu huruf besar dan judul daftar induk selalu
 * huruf besar; menolak "ppa-adro" hanya memaksa Admin mengetik ulang sesuatu
 * yang sudah jelas maksudnya. Yang ditolak adalah bentuk yang benar-benar
 * salah — spasi, garis bawah, garis pisah ganda.
 *
 * Otorisasinya sudah dipegang middleware `can:user.manage` di grup rutenya;
 * `authorize()` menegaskannya ulang supaya kelas ini tetap aman kalau suatu
 * hari rutenya dipindah.
 */
class SimpanPenomoranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'prefix' => Str::upper(trim((string) $this->input('prefix'))),
            'nama_site' => Str::upper(trim((string) $this->input('nama_site'))),
        ]);
    }

    public function rules(): array
    {
        return [
            /*
            | Kelompok huruf/angka yang dipisah SATU garis pisah, mis. "PPA-ADRO"
            | atau "PPAMSW". Polanya sengaja sama ketatnya dengan yang dipakai
            | Document::nomorLuarPola() untuk MEMBACA nomor: prefix berspasi atau
            | bergaris-pisah ganda akan menghasilkan nomor yang tak pernah cocok
            | dengan polanya sendiri, dan setiap dokumen baru langsung dicap
            | "Nomor Lama".
            |
            | Batas 20 aksara: sisa nomornya masih perlu memuat jenis, kode
            | departemen, dan dua digit urut agar terbaca di satu baris tabel.
            */
            'prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/'],
            'nama_site' => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'prefix.regex' => 'Prefix hanya boleh berisi huruf, angka, dan garis pisah tunggal — mis. PPA-ADRO.',
        ];
    }

    public function attributes(): array
    {
        return [
            'prefix' => 'prefix penomoran',
            'nama_site' => 'nama site',
        ];
    }
}
