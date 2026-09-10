<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentDistribution;
use App\Services\Print\ArsipPdf;
use App\Services\Print\PdfRenderer;
use Illuminate\Http\Request;

/**
 * Menyajikan PDF dokumen ke aplikasi mobile.
 *
 * SmartPro TIDAK menyimpan PDF sebagai berkas tetap — ia digenerate DomPDF lalu
 * disinggahkan per sidik isi ({@see PdfRenderer::cetak()}). Aplikasi mobile
 * mengunduh byte-nya lalu merender sendiri dengan `flutter_pdfview`; tidak ada
 * browser yang terlibat.
 *
 * Mesin cetaknya SENGAJA dipakai ulang lewat {@see PdfRenderer} — sama persis
 * dengan yang dipakai sisi web. Menyalin logika cetak ke sini akan membuat PDF
 * mobile bisa berbeda dari PDF web, dan PrintLayoutTest tak akan menangkapnya.
 */
class DocumentFileController extends Controller
{
    public function __construct(
        private readonly PdfRenderer $pdf,
        private readonly ArsipPdf $arsip,
        private readonly DocumentDistribution $distribusi,
    ) {}

    /**
     * Rute: GET /api/files/{jenis}/{berkas}
     *
     * Bentuk jalurnya mengikuti cara aplikasi mobile menyusun URL
     * (`{publicUrl}/{jenis}/{berkas}`). `{jenis}` hanya hiasan agar jalurnya
     * terbaca; yang menentukan dokumen adalah angka pada nama berkas.
     */
    public function show(Request $request, string $jenis, string $berkas)
    {
        $id = (int) pathinfo($berkas, PATHINFO_FILENAME);

        $document = Document::with(['type', 'department'])->findOrFail($id);

        /*
        | Dokumen BERLAKU terbuka bagi siapa pun yang sudah masuk — itu memang
        | terbitan resmi.
        |
        | Yang BELUM terbit tertutup, KECUALI bagi orang yang memang sedang
        | memegangnya: peninjau dan penyetuju yang ditunjuk, serta pemegang
        | `document.view_all`. Tanpa pengecualian ini fitur tinjau di HP mustahil
        | — peninjau tak akan pernah bisa membuka PDF dokumen yang justru sedang
        | ia nilai. Daftar orangnya disamakan dengan AttachmentCommentController,
        | bukan dikarang baru.
        |
        | Tetap 404 (bukan 403) bagi yang tak berhak: memberi tahu bahwa sebuah
        | dokumen ADA tapi terlarang sudah membocorkan keberadaannya.
        */
        $terbit = in_array($document->status, ['published', 'sedang_direvisi'], true)
            // Arsip Tidak Berlaku — GL/SH/DH/PJO saja. Kalau cabang ini hanya
            // ada di DocumentApiController::show, layar arsip berhasil memuat
            // metadatanya lalu gagal 404 tepat saat PDF-nya diminta.
            || ($document->status === 'obsolete' && $request->user()?->bisaLihatArsip());

        // Pagar departemen (PLAN-MOBILE-v6 §1.2) mengunci cabang "terbitan
        // resmi" saja. Peninjau & penyetuju yang DITUNJUK tetap lolos meski
        // dokumennya milik departemen lain — tanpa pengecualian itu GL SHE
        // mustahil meninjau JSA departemen lain dari HP.
        $pemegang = ($terbit && $document->terlihatOleh($request->user()))
            || $request->user()?->id === $document->reviewer_id
            || $request->user()?->id === $document->approver_id
            || $request->user()?->can('document.view_all');

        abort_unless($pemegang, 404);

        // Distribusi (v5 Fase C) — `platform: mobile`, sehingga laporan bisa
        // menjawab "apakah lapangan benar-benar memakai aplikasinya?".
        $this->distribusi->catat($document, $request->user(), unduh: true, platform: 'mobile');

        // Dokumen LAMA (butir 0) — jalur yang SAMA PERSIS dengan sisi web,
        // lewat ArsipPdf. Kalau cabang ini hanya ada di web, dokumen arsip
        // sampai ke HP sebagai PDF kosong berkop dan tak seorang pun di kantor
        // akan melihat gejalanya.
        // PX & FK ikut lewat sini walau `arsip_path`-nya masih kosong: jenis
        // berkelas `unggahan` tak punya bab sama sekali, jadi DomPDF hanya
        // akan mencetak kop kosong. ArsipPdf menjawab 404 "Berkas dokumen ini
        // belum diunggah." — pesan yang sudah dibacakan layar di HP.
        if ($document->dariBerkasUnggahan()) {
            return $this->arsip->sajikan($document, $request);
        }

        /*
        | Singgahan PDF dipusatkan di PdfRenderer::cetak(). Dulu di sini ada
        | singgahan KEDUA bernama sendiri yang kuncinya cuma `updated_at`, dan
        | kunci itu terlalu lemah: mengedit isi bab menulis ke
        | `document_contents`, bukan ke `documents`, jadi ponsel bisa menerima
        | PDF basi. `sidik()` menghitung isi, peninjauan, pembuat tambahan,
        | schema, dan mtime berkas cetak sekaligus — satu singgahan, satu aturan
        | kedaluwarsa, dipakai web maupun ponsel.
        |
        | ETag membuat ponsel yang sudah memegang berkasnya cukup menerima 304.
        */
        $sidik = $this->pdf->sidik($document);
        $etag = '"'.$sidik.'"';
        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->doc_number.'.pdf"',
            'ETag' => $etag,
            'Cache-Control' => 'private, no-cache',
        ];

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        return response($this->pdf->cetak($document), 200, $headers);
    }
}
