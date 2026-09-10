<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pendaftaran & perbaikan dokumen LAMA (arsip, butir 0).
 *
 * SATU kelas untuk store dan update, karena aturannya memang sama kecuali satu
 * hal: saat mendaftar, berkas WAJIB ada; saat memperbaiki, mengosongkannya
 * berarti "berkasnya tidak diganti". Dua kelas berarti dua daftar aturan yang
 * suatu hari menyimpang — dan yang menyimpang diam-diam adalah keunikan nomor.
 *
 * Dibedakan lewat ada-tidaknya parameter rute `{document}`, bukan lewat
 * parameter tersembunyi di formulir: rute adalah kebenaran yang tak bisa
 * dipalsukan pengirim.
 */
class ArsipDocumentRequest extends FormRequest
{
    /**
     * MENDAFTARKAN dokumen lama tunduk pada profil akses (PLAN-AKSES-v7 Fase
     * 4) — mendaftarkan SOP tetap menyusun SOP, hanya isinya sudah ada.
     *
     * MEMPERBAIKI yang sudah terdaftar TIDAK: `bisaDisuntingArsipOleh` sudah
     * mengurung suntingan ke pemiliknya sendiri, dan mencabut wewenangnya
     * hanya akan meninggalkan dokumen salah ketik yang tak seorang pun boleh
     * betulkan.
     */
    public function authorize(): bool
    {
        $document = $this->dokumen();

        return $document
            ? $document->bisaDisuntingArsipOleh($this->user())
            : $this->user()->bolehBuatJenis(
                DocumentType::find($this->document_type_id)?->code
            );
    }

    /** Dokumen yang sedang diperbaiki, atau null bila ini pendaftaran baru. */
    public function dokumen(): ?Document
    {
        $document = $this->route('document');

        return $document instanceof Document ? $document : null;
    }

    public function rules(): array
    {
        $baru = $this->dokumen() === null;

        return [
            'document_type_id' => [$baru ? 'required' : 'prohibited', 'exists:document_types,id'],
            'department_id' => [$baru ? 'required' : 'prohibited', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'doc_number' => ['required', 'string', 'max:100'],
            // Edisi mulai dari 1 (dokumen terbitan pertama), revisi 0–5 —
            // batas atasnya mengikuti roll-over CLAUDE.md §7: revisi ke-6 tak
            // pernah ada, ia menaikkan edisi dan kembali ke 0. Angkanya DIBACA
            // dari DocumentService, bukan disalin: ambang yang bergeser di sana
            // tanpa bergeser di sini menolak angka yang baru saja disahkan.
            'edisi' => ['required', 'integer', 'min:1', 'max:99'],
            'no_revisi' => ['required', 'integer', 'min:0', 'max:'.DocumentService::MAKS_REVISI],
            'tanggal_efektif' => ['nullable', 'date'],
            'berkas' => [$baru ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:40960'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $document = $this->dokumen();

            // Nomor lama tetap harus unik di dalam SmartPro: dua dokumen
            // bernomor sama membuat daftar induk mustahil dibaca, dan
            // `usedSequences()` akan memesan urutan yang sama dua kali.
            if (filled($this->doc_number)
                && ! app(DocumentNumberService::class)->isUnique($this->doc_number, $document?->id)) {
                $validator->errors()->add('doc_number', 'Nomor dokumen ini sudah dipakai dokumen lain.');
                // Galat merah saja tak cukup: orang yang mendaftarkan dokumen
                // lama tak punya cara tahu nomor mana yang masih bebas. Saran
                // dihitung SERVER (nomor urut bebas TERKECIL, mengisi celah) dan
                // dititipkan sebagai flash — `DocumentController::create` yang
                // menyajikannya kembali sebagai prop `nomorSaran` sesudah
                // redirect-back. Null bila jenis/departemennya sendiri tak sah,
                // dan dialognya memang tak digambar untuk keadaan itu.
                $this->session()->flash('nomorSaran', app(DocumentNumberService::class)
                    ->saranUntuk($this->document_type_id, $this->department_id));
            }

            // `mimes:pdf` hanya membaca EKSTENSI berkas yang diunggah — ia
            // meloloskan berkas apa pun yang dinamai ulang jadi .pdf. Yang
            // benar-benar menjawab "ini PDF?" adalah empat byte pertamanya.
            $berkas = $this->file('berkas');
            if ($berkas && $berkas->isValid() && file_get_contents($berkas->getRealPath(), false, null, 0, 5) !== '%PDF-') {
                $validator->errors()->add('berkas', 'Berkas ini bukan PDF yang sah (isinya tidak diawali %PDF-).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'doc_number.required' => 'Nomor dokumen lama wajib diisi.',
            'title.required' => 'Judul dokumen wajib diisi.',
            'berkas.required' => 'Berkas PDF dokumen lama wajib diunggah.',
            'berkas.mimes' => 'Berkas harus berformat PDF.',
            'berkas.max' => 'Ukuran berkas melebihi 40 MB.',
            'document_type_id.prohibited' => 'Jenis dokumen tidak bisa diubah setelah didaftarkan.',
            'department_id.prohibited' => 'Departemen tidak bisa diubah setelah didaftarkan.',
        ];
    }
}
