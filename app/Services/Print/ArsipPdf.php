<?php

namespace App\Services\Print;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyajikan berkas PDF dokumen LAMA (arsip, butir 0) apa adanya.
 *
 * Pasangan {@see PdfRenderer} untuk sisi seberang: PdfRenderer MEMBANGKITKAN
 * PDF dari isi dokumen, kelas ini hanya MENGALIRKAN berkas yang sudah ada.
 * Dokumen arsip tak punya `contents`, jadi memanggil mesin cetak untuknya
 * menghasilkan PDF kosong berkop — bukan dokumen yang dimaksud pengguna.
 *
 * SATU kelas, dipakai DUA penyaji: DocumentPdfController (web) dan
 * Api\DocumentFileController (mobile). Menambal salah satunya saja adalah
 * kesalahan yang tak terlihat dari kursi pengembang: web tampak benar, dan yang
 * menemukan dokumen kosong adalah orang di lapangan dengan HP di tangan.
 *
 * Berkasnya ada di disk `local` (storage/app/private) yang `serve`-nya mati,
 * jadi TAK ADA URL yang bisa menjangkaunya selain lewat rute berpenjaga auth
 * di sini — berbeda dari foto lampiran yang memang publik (routes/web.php:53).
 */
class ArsipPdf
{
    /**
     * @param  Request  $request  dibaca untuk `If-None-Match` saja
     */
    public function sajikan(Document $document, Request $request): Response
    {
        $disk = Storage::disk('local');
        $path = (string) $document->arsip_path;

        // Baris ada tapi berkasnya hilang = 404, bukan galat 500. Ini keadaan
        // yang MEMANG bisa terjadi: butir 8 mendaftarkan dokumen lebih dulu dan
        // berkasnya menyusul belakangan.
        abort_unless($path !== '' && $disk->exists($path), 404, 'Berkas dokumen ini belum diunggah.');

        // ETag dari mtime + ukuran, bukan dari isi berkasnya: menghitung hash
        // beberapa MB pada tiap permintaan justru mengalahkan tujuan cache.
        // Berkas hanya berubah lewat Edit (yang menulis nama acak baru), jadi
        // pasangan ini sudah cukup membedakan versi.
        $etag = '"'.md5($path.':'.$disk->lastModified($path).':'.$disk->size($path)).'"';

        $headers = [
            'Content-Type' => 'application/pdf',
            'ETag' => $etag,
            'Cache-Control' => 'private, no-cache',
            // Berkas ini datang dari unggahan pengguna. `nosniff` menahan
            // peramban menebak-nebak tipenya (dan mengeksekusinya sebagai HTML
            // bila tebakannya meleset); CSP mengunci sisanya.
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; object-src 'self'",
        ];

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304, $headers);
        }

        // `response()` milik disk MENGALIRKAN berkasnya (streamed), tidak
        // memuatnya utuh ke memori — arsip pindaian bisa puluhan MB.
        return $disk->response($path, $document->displayNumber().'.pdf', $headers);
    }
}
