<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDocumentAccess;
use App\Models\Document;
use App\Services\DocumentDistribution;
use App\Services\Print\ArsipPdf;
use App\Services\Print\PdfRenderer;
use Illuminate\Http\Request;

/**
 * Keluaran cetak dokumen.
 *
 * Hanya SATU jalur: PDF. Pratinjau di panel kanan wizard menampilkan berkas
 * ini juga (lewat iframe), bukan tiruan HTML-nya — dulu ada jalur `preview()`
 * terpisah yang merender template dengan `$screen = true`, dan jalur itu
 * DIHAPUS: ia menghasilkan tata letak yang berbeda (tanpa batas halaman, tanpa
 * nomor halaman, kop hanya sekali) sehingga pratinjau tak pernah benar-benar
 * 1:1, dan tiap perubahan cetak harus dikerjakan dua kali.
 */
class DocumentPdfController extends Controller
{
    use AuthorizesDocumentAccess;

    public function __construct(
        private readonly PdfRenderer $pdf,
        private readonly ArsipPdf $arsip,
        private readonly DocumentDistribution $distribusi,
    ) {}

    /**
     * PDF cetak — inline di tab baru, dan sumber pratinjau panel kanan wizard.
     *
     * Tiga lapis penghindaran render (dari yang termurah):
     *  1. `?v=` cocok dgn sidik → `max-age` panjang, peramban tak bertanya sama
     *     sekali. Aman karena isi yang berubah menghasilkan URL yang berbeda.
     *     Inilah yang membuat panel pratinjau tak berkedip saat pindah langkah.
     *  2. `If-None-Match` cocok → 304, TANPA merender.
     *  3. Cache disk di {@see PdfRenderer::cetak()} → tak merender ulang untuk
     *     pengguna kedua/peramban lain.
     *
     * Tanpa `?v=` (mis. tombol PDF / tautan yang disalin) sengaja
     * `no-cache`: URL-nya tak menyebut versi, jadi ia HARUS bertanya dulu —
     * kalau tidak, dokumen yang baru disahkan bisa terunduh versi lama.
     */
    public function pdf(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        // Distribusi (v5 Fase C): membuka PDF = paparan isi sungguhan, jadi ia
        // dihitung sebagai UNDUHAN, bukan sekadar kunjungan halaman.
        //
        // Dicatat sebelum cabang 304: peramban yang membalas dari cache tetap
        // BENAR-BENAR membuka dokumennya, dan kalau hanya 200 yang dicatat,
        // angka distribusi justru meleset paling jauh untuk orang yang paling
        // sering membacanya.
        $this->distribusi->catat($document, $request->user(), unduh: true);

        // Dokumen LAMA (butir 0): berkasnya sudah ada, tinggal dialirkan.
        // Cabang ini WAJIB mendahului `sidik()` — sidik dihitung dari isi bab,
        // dan dokumen arsip tak punya satu pun, jadi mesin cetak di bawahnya
        // akan menghasilkan PDF kosong berkop alih-alih dokumen aslinya.
        if ($document->isArsip()) {
            return $this->arsip->sajikan($document, $request);
        }

        $sidik = $this->pdf->sidik($document);
        $etag = '"'.$sidik.'"';
        $tetap = $request->query('v') === $sidik;

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$document->doc_number.'.pdf"',
            'ETag' => $etag,
            'Cache-Control' => $tetap ? 'private, max-age=600' : 'private, no-cache',
        ];

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        return response($this->pdf->cetak($document), 200, $headers);
    }

}
