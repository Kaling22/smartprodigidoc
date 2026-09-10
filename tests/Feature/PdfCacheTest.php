<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * SINGGAHAN PDF & sidik isinya.
 *
 * Yang dijaga di sini cuma satu hal, tapi hal itu berbahaya: cache boleh
 * MEMPERCEPAT, tak boleh MEMBOHONGI. Kalau sidiknya gagal berubah saat isi
 * berubah, pengguna akan menerima PDF basi tanpa satu pun pesan salah — jenis
 * cacat yang tak pernah tertangkap kecuali diuji tepat di sini.
 */
class PdfCacheTest extends TestCase
{
    use DatabaseTransactions;

    private function sop(string $judul = 'SOP Singgahan'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    public function test_sidik_stabil_bila_tak_ada_yang_berubah(): void
    {
        $d = $this->sop();
        $pdf = app(PdfRenderer::class);

        $this->assertSame($pdf->sidik($d), $pdf->sidik($d->fresh()));
    }

    public function test_sidik_berubah_saat_isi_bab_berubah(): void
    {
        $d = $this->sop();
        $pdf = app(PdfRenderer::class);
        $sebelum = $pdf->sidik($d);

        app(DocumentService::class)->saveSection($d, 'tujuan', ['Tujuan yang baru saja diketik.']);

        $this->assertNotSame($sebelum, $pdf->sidik($d->fresh()));
    }

    public function test_sidik_berubah_saat_pembuat_tambahan_dilampirkan(): void
    {
        // Melampirkan co-author menulis ke `document_authors`, TIDAK menyentuh
        // `documents.updated_at` — kunci naif berbasis updated_at akan lolos di
        // sini dan menyajikan cover tanpa baris "Dibuat Oleh" tambahannya.
        $d = $this->sop();
        $pdf = app(PdfRenderer::class);
        $sebelum = $pdf->sidik($d);

        $lain = User::where('nrp', '!=', $d->creator->nrp)
            ->where('department_id', $d->department_id)->firstOrFail();
        $d->authors()->create(['user_id' => $lain->id]);

        $this->assertNotSame($sebelum, $pdf->sidik($d->fresh()));
    }

    public function test_cetak_menghasilkan_pdf_yang_sama_dengan_render(): void
    {
        $d = $this->sop();
        $pdf = app(PdfRenderer::class);

        $dingin = $pdf->cetak($d);            // merender & menulis singgahan
        $hangat = $pdf->cetak($d->fresh());   // membaca singgahan

        $this->assertStringStartsWith('%PDF', $dingin);
        $this->assertSame($dingin, $hangat);
    }

    public function test_singgahan_paling_banyak_satu_berkas_per_dokumen(): void
    {
        $d = $this->sop();
        $pdf = app(PdfRenderer::class);
        // Pola diminta ke pemiliknya, bukan disusun ulang di sini — kalau bentuk
        // jalurnya berubah lagi, test ini ikut sendiri alih-alih diam-diam
        // memeriksa folder yang sudah tak dipakai siapa pun.
        $pola = PdfRenderer::jalurSinggahan($d->id, 'SOP');

        $pdf->cetak($d);
        app(DocumentService::class)->saveSection($d, 'tujuan', ['Berubah, jadi sidiknya berubah.']);
        $pdf->cetak($d->fresh());

        $this->assertCount(1, glob($pola) ?: [], 'versi lama harus terhapus saat versi baru ditulis');
    }

    /**
     * Singgahan DIKELOMPOKKAN per jenis dokumen — tak ada lagi `.pdf` datar di akar.
     *
     * Dua hal dijaga sekaligus, dan keduanya pernah salah:
     *   1. berkasnya benar-benar mendarat di `pdf-cache/{JENIS}/`, dan
     *   2. `DocumentPurger` masih menemukannya saat dokumen dimusnahkan — padahal
     *      ia dipanggil SESUDAH `forceDelete()`, ketika relasi `type` sudah tak
     *      bisa dibaca. Jenisnya harus ditangkap sebelum transaksi.
     */
    public function test_singgahan_dikelompokkan_per_jenis_dan_ikut_musnah(): void
    {
        $d = $this->sop();
        app(PdfRenderer::class)->cetak($d);

        $this->assertCount(1, glob(storage_path('app/pdf-cache/SOP').'/'.$d->id.'-*.pdf') ?: [],
            'singgahan harus berada di sub-folder jenis');
        $this->assertCount(0, glob(storage_path('app/pdf-cache').'/'.$d->id.'-*.pdf') ?: [],
            'tak boleh ada singgahan datar di akar');

        app(\App\Services\DocumentPurger::class)->purge($d, 'uji pemusnahan singgahan');

        $this->assertCount(0, glob(storage_path('app/pdf-cache/SOP').'/'.$d->id.'-*.pdf') ?: [],
            'singgahan harus ikut terhapus saat dokumen dimusnahkan');
    }

    public function test_rute_pdf_membalas_304_bila_etag_cocok(): void
    {
        $d = $this->sop();
        $etag = '"'.app(PdfRenderer::class)->sidik($d).'"';

        $this->actingAs($d->creator)
            ->withHeaders(['If-None-Match' => $etag])
            ->get(route('documents.pdf', $d))
            ->assertStatus(304);
    }

    public function test_rute_pdf_menyertakan_etag_dan_hanya_cache_keras_bila_v_cocok(): void
    {
        $d = $this->sop();
        $sidik = app(PdfRenderer::class)->sidik($d);

        // Tanpa `?v=`: URL tak menyebut versi → HARUS bertanya dulu, kalau tidak
        // dokumen yang baru disahkan bisa terunduh versi lama.
        $this->actingAs($d->creator)->get(route('documents.pdf', $d))
            ->assertOk()
            ->assertHeader('ETag', '"'.$sidik.'"')
            ->assertHeader('Cache-Control', 'no-cache, private');

        // Dengan `?v=` yang cocok: aman disimpan lama — isi berubah = URL berubah.
        $this->actingAs($d->creator)->get(route('documents.pdf', [$d, 'v' => $sidik]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=600, private');
    }

    public function test_halaman_wizard_memuat_iframe_pratinjau_langsung_dari_html(): void
    {
        // Inilah yang menghentikan panel "mati-nyala": `src` ada sejak HTML,
        // bukan dipasang JS sesudah Alpine hidup.
        $d = $this->sop();
        $sidik = app(PdfRenderer::class)->sidik($d);

        // Sidiknya datang sebagai props dan langsung jadi `?v=` pada `src`
        // iframe saat halaman pertama digambar — bukan dipasang sesudah
        // permintaan kedua. Perakitan URL-nya dijaga WizardInertiaTest.
        $this->assertSame($sidik, $this->propsInertia(
            $this->actingAs($d->creator)->get(route('documents.edit', $d))->assertOk()
        )['previewV']);
    }

    public function test_autosave_mengembalikan_sidik_baru(): void
    {
        $d = $this->sop();

        $this->actingAs($d->creator)
            ->post(route('documents.autosave', $d), [
                'step' => 1,
                'sections' => ['tujuan' => ['Tujuan hasil autosave.']],
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'v' => app(PdfRenderer::class)->sidik($d->fresh())]);
    }
}
