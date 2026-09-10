<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk dokumen yang dikirim ke aplikasi mobile.
 *
 * Kunci `judul` dan `berkas` adalah bentuk BARU yang seragam untuk semua jenis.
 * Kunci `judul_sop`/`file_sop` (mengikuti jenis dokumennya) ikut dikirim sebagai
 * ALIAS agar layar-layar lama aplikasi mobile — yang membaca `item['judul_sop']`
 * — tetap jalan tanpa diubah. Dengan begitu penyesuaian di sisi mobile cukup
 * satu baris URL per layar, bukan membongkar isinya.
 *
 * Alias ini boleh dihapus kelak setelah seluruh layar mobile memakai `judul`.
 *
 * @mixin \App\Models\Document
 */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $jenis = strtolower($this->type?->code ?? 'dok');

        return [
            'id' => $this->id,
            'judul' => $this->title,
            'no_dokumen' => $this->doc_number,
            'jenis' => strtoupper($jenis),
            'departemen' => $this->department?->code,
            'edisi' => $this->edisi,
            'no_revisi' => $this->no_revisi,
            'tanggal_berlaku' => $this->published_at?->toDateString(),

            // Status dikirim agar layar arsip tak perlu menebak dari daftar mana
            // sebuah kartu berasal — chip "Tidak Berlaku" harus benar walau
            // dokumennya muncul lewat pencarian.
            'status' => $this->status,
            'status_label' => \App\Models\Document::STATUS_LABELS[$this->status] ?? $this->status,

            // Nomor dokumen yang MENGGANTIKAN yang ini; kosong selama tak ada.
            // `whenLoaded` menjaga daftar Berlaku tetap dua query — relasinya
            // hanya dimuat pada cabang arsip (DocumentApiController::index).
            'digantikan_oleh' => $this->whenLoaded(
                'direvisiOleh',
                fn () => $this->direvisiOleh?->doc_number_final ?? $this->direvisiOleh?->doc_number,
                null,
            ),

            // Nama berkas semu: mobile menyusun URL `{publicUrl}/{jenis}/{berkas}`.
            // Isinya id dokumen, sebab SmartPro TIDAK menyimpan PDF sebagai file —
            // PDF-nya digenerate DomPDF saat diminta (lihat DocumentFileController).
            'berkas' => $this->id.'.pdf',

            // Mobile menampilkan penghitung ini. SmartPro belum mencatat jumlah
            // baca, jadi 0 dulu — kolom penghitung bisa ditambah kemudian.
            'views' => 0,

            // --- alias kompatibilitas layar mobile lama ---
            'judul_'.$jenis => $this->title,
            'file_'.$jenis => $this->id.'.pdf',
        ];
    }
}
