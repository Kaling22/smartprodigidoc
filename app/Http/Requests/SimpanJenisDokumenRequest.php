<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi Master Data → kartu Jenis Dokumen (PLAN-AKSES-v8 Fase 5b).
 *
 * Hanya DUA field, dan itu memang seluruh yang boleh diubah dari layar:
 * `code` tidak ada di sini karena ia dirujuk DocumentType::RUPA, schema JSON,
 * template cetak, dan `documents.document_type_id` sekaligus — dan tak satu
 * pun dari keempatnya ikut berubah kalau kodenya diganti dari sini.
 *
 * Menambah & menghapus jenis sengaja tak punya rute sama sekali (§9.1).
 */
class SimpanJenisDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    protected function prepareForValidation(): void
    {
        // Checkbox yang tak dicentang tidak terkirim; tanpa baris ini
        // "nonaktifkan" tersimpan sebagai "jangan ubah apa-apa".
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama jenis dokumen'];
    }
}
