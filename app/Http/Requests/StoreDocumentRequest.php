<?php

namespace App\Http\Requests;

use App\Models\DocumentType;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Izin `document.create` saja tak lagi cukup: sejak PLAN-AKSES-v7 Fase 4,
     * jenis yang boleh disusun ditentukan profil akses per orang.
     *
     * Diperiksa DI SINI, bukan di controller: inilah satu-satunya pintu tulis
     * wizard. `document_type_id` yang tak dikenal jatuh ke null → false, dan
     * pesan validasi `exists:` tak pernah sempat menggantikannya dengan
     * jawaban yang lebih ramah — itu memang benar, sebab id palsu bukan salah
     * ketik melainkan permintaan yang dikarang.
     */
    public function authorize(): bool
    {
        return $this->user()->bolehBuatJenis(
            DocumentType::find($this->document_type_id)?->code
        );
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'exists:document_types,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'doc_number_manual' => ['sometimes', 'boolean'],
            'doc_number' => ['nullable', 'required_if:doc_number_manual,1', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Manual numbers must be unique across documents.
            if ($this->boolean('doc_number_manual') && filled($this->doc_number)) {
                if (! app(DocumentNumberService::class)->isUnique($this->doc_number)) {
                    $validator->errors()->add('doc_number', 'Nomor dokumen ini sudah dipakai.');
                    // Saran nomor bebas untuk dialog "Nomor Sudah Dipakai"
                    // (rencana pra-produksi Fase 5). Dihitung server; TSX tak
                    // pernah menebak nomor berikutnya sendiri.
                    $this->session()->flash('nomorSaran', app(DocumentNumberService::class)
                        ->saranUntuk($this->document_type_id, $this->department_id));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'doc_number.required_if' => 'Nomor dokumen wajib diisi bila input manual diaktifkan.',
            'title.required' => 'Judul dokumen wajib diisi.',
        ];
    }
}
