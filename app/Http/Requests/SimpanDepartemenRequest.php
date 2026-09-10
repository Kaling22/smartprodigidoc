<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validasi Master Data → kartu Departemen (PLAN-AKSES-v8 Fase 5b).
 *
 * Dipakai untuk TAMBAH (tanpa route model) maupun UBAH (dengan). Dua aturan
 * yang tak boleh hilang:
 *
 * 1. **Kode terkunci begitu departemen punya dokumen.** Kode departemen masuk
 *    ke NOMOR DOKUMEN (`PPA-ADRO-SOP-ICTMD-01`) dan nomor itu sudah beredar di
 *    luar sistem. Menggantinya tak menulis ulang satu pun nomor lama, jadi yang
 *    dihasilkan adalah satu departemen dengan dua kode yang sama-sama sah —
 *    dan tak ada tempat yang mencatat bahwa keduanya orang yang sama.
 *    Ditolak dengan pesan, bukan diabaikan diam-diam: Admin yang mengetiknya
 *    berhak tahu bahwa kehendaknya tidak dijalankan.
 *
 * 2. **`is_active` selalu ada nilainya.** Checkbox yang tidak dicentang tidak
 *    ikut terkirim sama sekali; tanpa `prepareForValidation()`, "matikan
 *    departemen" akan tersimpan sebagai "jangan ubah apa-apa".
 */
class SimpanDepartemenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    /** Departemen yang sedang disunting, atau null saat menambah. */
    public function departemen(): ?Department
    {
        return $this->route('department');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper(trim((string) $this->input('code'))),
            'alias' => Str::upper(trim((string) $this->input('alias'))) ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            // Pola sama ketatnya dengan prefix site (Fase 5a) dan karena alasan
            // yang sama: kode ini menjadi satu ruas nomor dokumen, dan ruas
            // berspasi menghasilkan nomor yang tak pernah cocok dengan polanya
            // sendiri.
            'code' => [
                'required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/',
                Rule::unique('departments', 'code')->ignore($this->departemen()),
            ],
            'name' => ['required', 'string', 'max:100'],
            'alias' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $dept = $this->departemen();

            if ($dept && $dept->code !== $this->input('code') && $dept->documents()->exists()) {
                $validator->errors()->add('code',
                    "Kode {$dept->code} sudah dipakai pada nomor dokumen yang terbit, jadi tidak bisa diubah lagi. "
                    .'Nomor yang sudah beredar tidak ikut ditulis ulang — mengubah kodenya hanya akan membuat satu '
                    .'departemen punya dua kode yang sama-sama berlaku.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Kode departemen hanya boleh huruf, angka, dan garis pisah tunggal — mis. FAW-SCM.',
            'code.unique' => 'Kode departemen ini sudah dipakai.',
        ];
    }

    public function attributes(): array
    {
        return ['code' => 'kode departemen', 'name' => 'nama departemen', 'alias' => 'alias'];
    }
}
