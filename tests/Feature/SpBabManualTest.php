<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Isi dokumen SP (Standar Parameter) sejak keputusan pemilik 2026-10-06,
 * disederhanakan lagi 2026-10-07 — lihat `DocumentTypeSeeder::spSchema()` dan
 * "Pengecualian KELIMA" di CLAUDE.md §14b. SATU bab saja: judul diketik
 * manual + isi rich text — bukan lagi pola SOP (Tujuan/Ruang
 * Lingkup/Referensi/Definisi/Aktivitas/Lampiran), dan TANPA bab RIWAYAT
 * REVISI (sempat ada, dicabut lagi — langsung ke bab isi).
 *
 * Diperiksa dari PDF yang BENAR-BENAR dirender (sama pola dengan
 * PrintLayoutTest/RichTextAktivitasTest), bukan dari HTML preview.
 */
class SpBabManualTest extends TestCase
{
    use DatabaseTransactions;

    private function spDraft(string $judul = 'SP Uji Bab Manual'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SP')->firstOrFail(), $gl->department, $judul
        );
    }

    /**
     * Teks PDF lewat `pdftotext` (dipakai juga oleh CLAUDE.md §14b untuk
     * memverifikasi baseline cetak) — lebih tahan banting daripada mengurai
     * literal `(...)` di stream mentah: dokumen pendek (cover + sedikit isi)
     * bisa membuat operator gambar/canvas si cover bercampur dengan stream
     * teks, dan pengurai literal naif ikut menelan byte binernya.
     */
    private function pdfText(Document $document): string
    {
        $raw = app(PdfRenderer::class)->render($document->refresh())->output();

        $berkas = tempnam(sys_get_temp_dir(), 'sp-bab-').'.pdf';
        file_put_contents($berkas, $raw);

        try {
            $teks = shell_exec('pdftotext -layout '.escapeshellarg($berkas).' - ') ?? '';
        } finally {
            @unlink($berkas);
        }

        return preg_replace('/\s+/', ' ', $teks);
    }

    /** Jumlah objek gambar di dalam PDF — pola sama dengan RichTextAktivitasTest::hitungGambar(). */
    private function hitungGambar(Document $document): int
    {
        $raw = app(PdfRenderer::class)->render($document->refresh())->output();

        return preg_match_all('#/Subtype\s*/Image#', $raw);
    }

    public function test_bab_manual_tercetak_tanpa_bab_sop_lama(): void
    {
        $doc = $this->spDraft();

        $doc->contents()->create(['section_key' => 'bab', 'value_json' => [
            ['judul' => 'JUDULBABSATU', 'isi' => '<p>Isi <strong>TEBALBABSATU</strong> bab pertama</p>'],
            ['judul' => 'JUDULBABDUA', 'isi' => '<p>ISIBABDUA polos</p>'],
            ['judul' => 'JUDULBABTIGA', 'isi' => '<p>ISIBABTIGA polos</p>'],
        ]]);

        $teks = $this->pdfText($doc);

        // Bab manual: ketiga judul yang diketik bebas + isi rich text-nya.
        foreach (['JUDULBABSATU', 'JUDULBABDUA', 'JUDULBABTIGA', 'TEBALBABSATU', 'ISIBABDUA', 'ISIBABTIGA'] as $kata) {
            $this->assertStringContainsString($kata, $teks, "{$kata} (bab manual) harus tercetak");
        }
        // HTML-nya sendiri tak boleh bocor sebagai huruf (sudah disanitasi).
        $this->assertStringNotContainsString('strong', $teks, 'tag HTML tak boleh tercetak sebagai huruf');

        // Tiap bab bernomor ROMAWI sendiri (keputusan pemilik 2026-10-07):
        // I./II./III. — BUKAN sub-nomor "1.1"/"1.2"/"1.3" ala Aktivitas.
        $this->assertStringContainsString('I. JUDULBABSATU', $teks);
        $this->assertStringContainsString('II. JUDULBABDUA', $teks);
        $this->assertStringContainsString('III. JUDULBABTIGA', $teks);
        $this->assertStringNotContainsString('1.1', $teks, 'tak boleh lagi memakai sub-nomor desimal');

        // Bab-bab SOP lama (termasuk Riwayat Revisi yang sempat ada lalu
        // dicabut) sudah dicabut total dari SP — tak boleh ada sisa.
        foreach (['TUJUAN', 'RUANG LINGKUP', 'REFERENSI', 'DEFINISI', 'AKTIVITAS DAN TANGGUNG JAWAB', 'LAMPIRAN', 'RIWAYAT REVISI'] as $labelLama) {
            $this->assertStringNotContainsString($labelLama, $teks, "\"{$labelLama}\" seharusnya tak lagi tercetak di SP");
        }
    }

    /**
     * Inti keluhan pemilik: gambar yang diunggah lewat editor rich text bab
     * manual TIDAK tampil di PDF. Penyebabnya `render.blade.php` dulu
     * mencetak `$row['isi']` APA ADANYA — `<img src="/storage/…">` yang
     * DomPDF tak bisa memuat sendiri (lihat `PembersihHtml::jalurGambar()`
     * & `PdfRenderer::viewData()` `$embed`), berbeda dari Aktivitas yang
     * lewat `ActivityPrintLayout`/`$selAkt()` sudah menukarnya jadi data URI
     * base64. `PembersihHtml::embedGambar()` menutup celah yang sama untuk
     * bab manual, TANPA ikut membongkar `<ol>`/`<ul>` jadi teks bernomor
     * (yang akan terjadi bila dipaksakan lewat `blok()`/ActivityPrintLayout).
     *
     * Berlaku utk dokumen SP APA PUN asal-usulnya — baru lewat wizard atau
     * lama lewat arsip — karena keduanya menulis ke `document_contents`
     * dengan bentuk yang SAMA (`PembersihHtml::bersihkan()`) dan dicetak
     * lewat satu jalur `render.blade.php` yang sama; tes ini tak perlu
     * membedakan keduanya.
     */
    public function test_gambar_di_isi_bab_manual_tercetak_di_pdf(): void
    {
        $dept = $this->aktorGl()->department->code;
        $rel = "lampiran/{$dept}/SP/uji_".uniqid().'.png';
        $abs = storage_path('app/public/'.$rel);

        if (! is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0777, true);
        }
        $im = imagecreatetruecolor(600, 300);
        imagefill($im, 0, 0, imagecolorallocate($im, 220, 60, 60));
        imagepng($im, $abs);
        imagedestroy($im);

        try {
            $doc = $this->spDraft('SP Foto Sisipan');

            // (a) tanpa foto — patokan: gambar yang ada hanyalah logo kop.
            $doc->contents()->create(['section_key' => 'bab', 'value_json' => [
                ['judul' => 'JUDULFOTOUJI', 'isi' => '<p>FOTOUJI langkah</p>'],
            ]]);
            $tanpa = $this->hitungGambar($doc);

            // (b) dgn foto, lewat jalur yang sama dgn tombol gambar & tempelan.
            $doc->contents()->where('section_key', 'bab')->delete();
            $doc->contents()->create(['section_key' => 'bab', 'value_json' => [
                ['judul' => 'JUDULFOTOUJI', 'isi' => '<p>FOTOUJI langkah</p><p><img src="/storage/'.$rel.'"></p>'],
            ]]);
            $dengan = $this->hitungGambar($doc);

            $this->assertSame($tanpa + 1, $dengan,
                'PDF harus memuat TEPAT satu objek gambar lebih banyak saat isi bab berisi foto');
        } finally {
            @unlink($abs);
        }
    }
}
