<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kolom Deskripsi Aktivitas sebagai EDITOR (Quill 2), Fase A3.
 *
 * Tiga hal yang dijaga — sengaja tiga, bukan sepuluh: masing-masing menutup
 * satu jalur yang kalau putus, fiturnya diam-diam tak berguna.
 *
 *   1. FORM  — editornya benar-benar terpasang, bukan textarea lama.
 *   2. SIMPAN — HTML dari peramban disaring sebelum menyentuh basis data.
 *   3. CETAK — yang diketik penyusun benar-benar sampai ke PDF.
 *
 * Rupa toolbar & kalibrasi tata letaknya sudah dikunci di tempat lain
 * (PembersihHtmlTest, ActivityPrintLayoutTest, PrintLayoutTest).
 */
class RichTextAktivitasTest extends TestCase
{
    use DatabaseTransactions;

    private function sopDraft(string $judul = 'SOP Rich Text'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    /**
     * Satu kunci schema (`rich_text`), tiga jenis dokumen ikut — tanpa view atau
     * route baru per jenis (CLAUDE.md §3/§7).
     */
    public function test_deskripsi_aktivitas_bertipe_rich_text_di_sop_sp_dan_ik(): void
    {
        foreach (['SOP', 'SP', 'IK'] as $kode) {
            $schema = \App\Services\SchemaService::for(DocumentType::where('code', $kode)->firstOrFail());
            $bidang = collect($schema->findSection('aktivitas')['group_fields'] ?? [])->firstWhere('key', 'deskripsi');

            $this->assertSame('rich_text', $bidang['type'] ?? null, "kolom deskripsi {$kode} harus rich_text");
        }
    }

    /**
     * Langkah 2 memasang editornya — bukan lagi <textarea> polos.
     *
     * Editornya dirakit di peramban, jadi yang diperiksa DUA hal: (a) schema
     * yang sampai ke layar benar-benar menyebut kolomnya `rich_text`, dan
     * (b) berkas yang menggambarnya memasang Lexical beserta seluruh tombol
     * yang diminta pemilik. Memeriksa markup halaman tak bisa lagi: isinya
     * cuma satu atribut `data-page` berisi JSON.
     *
     * Sejak migrasi dari Quill (dua percobaan menambal daftar bernomor Quill
     * berakhir dgn editornya mati sama sekali di peramban — lihat riwayat
     * commit), pengunci Quill-nya (`ql-*`, `theme: 'snow'`) diganti pengunci
     * Lexical — DIPERBARUI, bukan dihapus: janji "toolbar lengkap + ikon
     * hugeicons" yang dijaganya tetap sama, cuma bentuk pemeriksaannya yang
     * mengikuti editor baru (Lexical tak mendikte markup toolbar sama sekali,
     * jadi tak ada lagi kelas `ql-*` yang bisa dicari).
     */
    public function test_form_memasang_editor_lexical(): void
    {
        $doc = $this->sopDraft();
        $doc->update(['current_step' => 2]);

        $seksi = $this->seksiWizard(
            $this->actingAs($doc->creator)->get(route('documents.edit', $doc))->assertOk()
        );

        $deskripsi = collect($seksi['aktivitas']['group_fields'])->firstWhere('key', 'deskripsi');
        $this->assertSame('rich_text', $deskripsi['type'], 'kolom deskripsi harus sampai ke layar sebagai rich_text');

        $grup = file_get_contents(resource_path('js/components/v2/dokumen/fields/RepeatableGroup.tsx'));
        $this->assertStringContainsString("field.type === 'rich_text'", $grup, 'kolom rich_text tak dialihkan ke editornya');
        $this->assertStringContainsString('<RichText', $grup);
        $this->assertStringNotContainsString('<Textarea', substr($grup, strpos($grup, "field.type === 'rich_text'")),
            'textarea lama tak boleh tersisa di cabang rich_text');

        $editor = file_get_contents(resource_path('js/components/v2/dokumen/fields/RichText.tsx'));
        $this->assertStringContainsString("import('lexical')", $editor, 'pustaka Lexical harus dimuat komponen ini');
        $this->assertStringContainsString('LexicalComposer', $editor, 'kerangka editor Lexical harus terpasang');
        $this->assertStringContainsString('className="pp-rt', $editor, 'kotak editor bertema SmartPro harus ada');
        $this->assertStringContainsString('data-pp-rich', $editor, 'penanda validasi wajib-diisi harus ikut');

        // Tombol yang diminta pemilik — judul (title) tiap tombol, bukan lagi
        // kelas `ql-*` Quill: Lexical tak mendikte markup toolbar sama sekali,
        // toolbar-nya digambar React biasa (`ui-maia/button`).
        foreach ([
            'Tebal', 'Miring', 'Garis bawah', 'Kutipan',
            'Daftar bernomor', 'Daftar berbutir', 'Gambar', 'Bersihkan format',
        ] as $tombol) {
            $this->assertStringContainsString('judul="'.$tombol.'"', $editor, "tombol {$tombol} harus ada di toolbar");
        }

        // Nama glifnya HUGEICONS sejak pohon V1 dihapus (2026-09-07) — kit ikon
        // V2 (PATOKAN-GAYA-V2 §2). Yang dijaga TETAP sama sejak Quill: kedelapan
        // tombol punya glifnya sendiri.
        foreach ([
            'TextBoldIcon', 'TextItalicIcon', 'TextUnderlineIcon', 'QuoteDownIcon',
            'LeftToRightListNumberIcon', 'LeftToRightListBulletIcon', 'Image01Icon', 'EraserIcon',
        ] as $ikon) {
            $this->assertStringContainsString('ikon={'.$ikon.'}', $editor, "ikon {$ikon} harus hugeicons");
        }
    }

    /**
     * Apa pun yang datang dari peramban bisa dikarang. Yang tersimpan harus sudah
     * bersih — jalur cetak, layar tinjau, dan prompt AI bersandar pada itu.
     */
    public function test_html_disaring_saat_disimpan(): void
    {
        $doc = $this->sopDraft('SOP Sanitasi');
        $doc->update(['current_step' => 2]);

        $this->actingAs($doc->creator)->post(route('documents.saveStep', $doc), [
            'step' => 2,
            'action' => 'save',
            'sections' => ['aktivitas' => [
                ['sub_judul' => 'Persiapan', 'pic' => 'ICT', 'deskripsi' =>
                    '<p onclick="curi()"><b>Cek</b> APD</p>'
                    .'<script>alert(1)</script>'
                    .'<p><img src="https://jauh.example.com/a.png"></p>'
                    .'<ol><li data-list="bullet">catatan</li></ol>'],
                // Baris yang HANYA berisi editor kosong: '<p><br></p>' terbaca
                // "terisi" oleh filled(), jadi tanpa sanitasi ia akan tersimpan
                // dan memakan satu baris tabel kosong di PDF.
                ['sub_judul' => '', 'pic' => '', 'deskripsi' => '<p><br></p>'],
            ]],
        ])->assertRedirect();

        $baris = $doc->refresh()->contentMap()['aktivitas'];

        $this->assertCount(1, $baris, 'baris yang editornya kosong harus dibuang');
        $this->assertSame(
            '<p><strong>Cek</strong> APD</p><ul><li>catatan</li></ul>',
            $baris[0]['deskripsi'],
            'onclick, script, dan gambar dari luar harus lenyap; <b> jadi <strong>; bulir Quill jadi <ul>',
        );
    }

    /** Dokumen lama tersimpan sebagai teks polos — menyimpannya ulang tak boleh mengubahnya. */
    public function test_teks_polos_dokumen_lama_tak_berubah(): void
    {
        $doc = $this->sopDraft('SOP Lama');
        $doc->update(['current_step' => 2]);
        $lama = "Langkah pertama\nLangkah kedua";

        $this->actingAs($doc->creator)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'save',
            'sections' => ['aktivitas' => [['sub_judul' => 'Lama', 'pic' => 'ICT', 'deskripsi' => $lama]]],
        ])->assertRedirect();

        $this->assertSame($lama, $doc->refresh()->contentMap()['aktivitas'][0]['deskripsi']);
    }

    /**
     * FOTO yang disisipkan di editor benar-benar tercetak di PDF.
     *
     * Ini pertanyaan yang paling mudah dijawab keliru: bab AKTIVITAS sudah punya
     * logo PPA di kopnya, jadi PDF-nya SELALU memuat objek gambar — memeriksa
     * "ada gambar atau tidak" akan selalu menjawab "ada" dan tak membuktikan
     * apa pun. Karena itu dokumen yang sama dirender DUA KALI, dengan dan tanpa
     * baris fotonya, lalu jumlah objek gambarnya dibandingkan.
     *
     * Berkasnya nyata di storage/app/public/lampiran/…: closure $embed membacanya
     * lewat storage_path() langsung, jadi Storage::fake() tak akan terlihat
     * olehnya — dan test yang memakai fake justru akan hijau tanpa gambar apa pun.
     */
    public function test_foto_di_deskripsi_tercetak_di_pdf(): void
    {
        $dept = $this->aktorGl()->department->code;
        $rel = "lampiran/{$dept}/SOP/uji_".uniqid().'.png';
        $abs = storage_path('app/public/'.$rel);

        if (! is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0777, true);
        }
        $im = imagecreatetruecolor(600, 300);
        imagefill($im, 0, 0, imagecolorallocate($im, 220, 60, 60));
        imagepng($im, $abs);
        imagedestroy($im);

        try {
            $doc = $this->sopDraft('SOP Foto Sisipan');

            $isi = fn (string $deskripsi) => [[
                'sub_judul' => 'Pemeriksaan', 'pic' => 'ICT', 'deskripsi' => $deskripsi,
            ]];

            // (a) tanpa foto — patokan: gambar yang ada hanyalah logo kop.
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => $isi('<p>FOTOUJI langkah</p>')]);
            $tanpa = $this->hitungGambar($doc->refresh());

            // (b) dgn foto, lewat jalur yang sama dgn tombol gambar & tempelan.
            $doc->contents()->where('section_key', 'aktivitas')->delete();
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => $isi(
                '<p>FOTOUJI langkah</p><p><img src="/storage/'.$rel.'"></p>'
            )]);
            $dengan = $this->hitungGambar($doc->refresh());

            $this->assertSame($tanpa + 1, $dengan,
                'PDF harus memuat TEPAT satu objek gambar lebih banyak saat deskripsi berisi foto');
        } finally {
            @unlink($abs);
        }
    }

    /**
     * FOTO YANG DITEMPEL (Ctrl+V) sampai ke PDF — inti keluhan pemilik.
     *
     * Quill menyisipkan tempelan sebagai `data:image/png;base64,…`. Sebelum
     * GambarTempel ada, PembersihHtml membuangnya (src-nya tak menunjuk
     * lampiran/…), jadi fotonya tampak baik di editor lalu LENYAP di pratinjau
     * dan di PDF — tanpa satu pun pesan kesalahan.
     *
     * Diuji lewat route simpan SUNGGUHAN, bukan dgn memanggil servicenya
     * langsung: yang dulu putus justru sambungannya (cleanValue tak menerima
     * $document), bukan servicenya. Uji yang memanggil service langsung akan
     * hijau sementara penggunanya tetap kehilangan foto.
     */
    public function test_foto_tempelan_base64_disimpan_jadi_berkas_dan_tercetak(): void
    {
        $doc = $this->sopDraft('SOP Foto Tempelan');
        $doc->update(['current_step' => 2]);

        $im = imagecreatetruecolor(800, 400);
        imagefill($im, 0, 0, imagecolorallocate($im, 30, 120, 220));
        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        $tempelan = 'data:image/png;base64,'.base64_encode($png);
        $dept = $doc->department->code;
        $bersih = null;

        try {
            $this->actingAs($doc->creator)->post(route('documents.saveStep', $doc), [
                'step' => 2, 'action' => 'save',
                'sections' => ['aktivitas' => [[
                    'sub_judul' => 'Pemeriksaan', 'pic' => 'ICT',
                    'deskripsi' => '<p>TEMPELUJI sebelum</p><p><img src="'.$tempelan.'"></p><p>TEMPELUJI sesudah</p>',
                ]]],
            ])->assertRedirect();

            $tersimpan = $doc->refresh()->contentMap()['aktivitas'][0]['deskripsi'];

            // (1) data URI-nya sudah TAK ADA — kalau masih ada, ia ikut terkirim
            //     di setiap autosave dan menggembungkan value_json berlipat.
            $this->assertStringNotContainsString('data:image', $tersimpan,
                'data URI tak boleh tersimpan di document_contents');

            // (2) src-nya kini menunjuk berkas lampiran yang sah.
            $this->assertMatchesRegularExpression(
                '#<img src="/storage/(lampiran/'.preg_quote($dept, '#').'/SOP/img_[\w.-]+\.png)">#',
                $tersimpan,
                'foto tempelan harus jadi berkas di lampiran/{DEPT}/{JENIS}/; dapat: '.$tersimpan
            );
            preg_match('#/storage/(lampiran/[^"]+)#', $tersimpan, $m);
            $bersih = storage_path('app/public/'.$m[1]);

            // (3) berkasnya benar-benar ada di disk & tercatat sbg lampiran.
            $this->assertFileExists($bersih);
            $this->assertSame(1, $doc->attachments()->where('section_key', 'aktivitas')->count(),
                'unggahan harus tercatat sebagai lampiran dokumen');

            // (4) teks di atas & di bawah foto tetap utuh.
            $this->assertStringContainsString('TEMPELUJI sebelum', $tersimpan);
            $this->assertStringContainsString('TEMPELUJI sesudah', $tersimpan);

            // (5) dan yang paling penting: fotonya TERCETAK.
            $dengan = $this->hitungGambar($doc->refresh());

            $doc->contents()->where('section_key', 'aktivitas')->delete();
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
                'sub_judul' => 'Pemeriksaan', 'pic' => 'ICT', 'deskripsi' => '<p>TEMPELUJI sebelum</p>',
            ]]]);
            $tanpa = $this->hitungGambar($doc->refresh());

            $this->assertSame($tanpa + 1, $dengan, 'foto tempelan harus tercetak di PDF');
        } finally {
            if ($bersih) {
                @unlink($bersih);
            }
        }
    }

    /**
     * Tangkapan layar 4K diperkecil sebelum disimpan — batas unggahan 2MB dan
     * penyimpanan tak boleh dihabiskan piksel yang tak pernah terlihat: di PDF
     * gambarnya dibatasi .akt-img (maks 220pt tinggi, selebar kolom AKTIVITAS).
     */
    public function test_foto_tempelan_raksasa_diperkecil(): void
    {
        $doc = $this->sopDraft('SOP Foto Besar');
        $doc->update(['current_step' => 2]);

        $im = imagecreatetruecolor(3840, 2160);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 200, 200));
        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        $bersih = null;

        try {
            $this->actingAs($doc->creator)->post(route('documents.saveStep', $doc), [
                'step' => 2, 'action' => 'save',
                'sections' => ['aktivitas' => [[
                    'sub_judul' => 'Besar', 'pic' => 'ICT',
                    'deskripsi' => '<p><img src="data:image/png;base64,'.base64_encode($png).'"></p>',
                ]]],
            ])->assertRedirect();

            $tersimpan = $doc->refresh()->contentMap()['aktivitas'][0]['deskripsi'];
            preg_match('#/storage/(lampiran/[^"]+)#', $tersimpan, $m);
            $this->assertNotEmpty($m, 'foto raksasa tetap harus tersimpan, bukan dibuang');

            $bersih = storage_path('app/public/'.$m[1]);
            [$lebar] = getimagesize($bersih);

            $this->assertSame(1600, $lebar, 'lebar tersimpan harus dipangkas ke 1600px');
        } finally {
            if ($bersih) {
                @unlink($bersih);
            }
        }
    }

    /**
     * Nomor butir yang isinya foto benar-benar TERCETAK di PDF.
     *
     * Uji unitnya (ActivityPrintLayoutTest) menjaga penandanya ikut ke baris
     * gambar; yang ini menjaga bahwa $selAkt di render.blade.php sungguh
     * mencetaknya — dua hal berbeda, dan dulu yang kedua diam-diam membuangnya.
     */
    public function test_nomor_butir_berisi_foto_tercetak_di_pdf(): void
    {
        $dept = $this->aktorGl()->department->code;
        $rel = "lampiran/{$dept}/SOP/uji_".uniqid().'.png';
        $abs = storage_path('app/public/'.$rel);

        if (! is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0777, true);
        }
        $im = imagecreatetruecolor(400, 200);
        imagefill($im, 0, 0, imagecolorallocate($im, 40, 160, 90));
        imagepng($im, $abs);
        imagedestroy($im);

        try {
            $doc = $this->sopDraft('SOP Nomor Foto');
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
                'sub_judul' => 'Langkah', 'pic' => 'ICT',
                'deskripsi' => '<ol><li>NOMORUJI satu</li>'
                    .'<li><img src="/storage/'.$rel.'"></li>'
                    .'<li>NOMORUJI tiga</li></ol>',
            ]]]);

            $teks = $this->teksPdf($doc->refresh());

            $this->assertStringContainsString('1.', $teks);
            $this->assertStringContainsString('2.', $teks, 'nomor butir yang isinya foto harus tetap tercetak');
            $this->assertStringContainsString('3.', $teks);
            $this->assertSame(1, $this->hitungGambar($doc) - $this->hitungGambarTanpaFoto($doc),
                'fotonya sendiri juga harus tercetak');
        } finally {
            @unlink($abs);
        }
    }

    /**
     * "Lanjutkan Penomoran" (tombol toolbar `ql-mulai`, RichText.tsx) —
     * daftar bernomor yang diselingi paragraf polos MELANJUTKAN nomornya di
     * PDF, bukan mulai dari 1 lagi, saat butir pertama bertanda `data-mulai`.
     *
     * Ini pengunci untuk `PembersihHtml::pecahBlok()`, dibaca
     * `ActivityPrintLayout::flatten()` — bukan untuk sisi editor (Quill,
     * counter CSS `--mulai`, TAK bisa diuji lewat suite PHP ini).
     */
    public function test_daftar_bernomor_melanjutkan_penomoran_di_pdf(): void
    {
        $doc = $this->sopDraft('SOP Lanjutkan Penomoran');
        $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
            'sub_judul' => 'Langkah', 'pic' => 'ICT',
            'deskripsi' => '<p>Baris pertama</p><ol><li>pertama</li><li>kedua</li></ol>'
                .'<p>Baris kedua</p><ol><li data-mulai="3">ketiga</li></ol>',
        ]]]);

        $teks = $this->teksPdf($doc->refresh());

        $this->assertStringContainsString('1.pertama', $teks);
        $this->assertStringContainsString('2.kedua', $teks);
        $this->assertStringContainsString('3.ketiga', $teks,
            'butir sesudah paragraf penyela harus melanjutkan nomor, bukan mulai dari 1 lagi');
    }

    /** Dokumen yang sama, baris fotonya dibuang — patokan pembanding. */
    private function hitungGambarTanpaFoto(Document $document): int
    {
        $asli = $document->contentMap()['aktivitas'];
        $document->contents()->where('section_key', 'aktivitas')->delete();
        $document->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
            'sub_judul' => $asli[0]['sub_judul'], 'pic' => $asli[0]['pic'] ?? '',
            'deskripsi' => preg_replace('#<li><img[^>]*></li>#', '', $asli[0]['deskripsi']),
        ]]]);

        return $this->hitungGambar($document->refresh());
    }

    /** Seluruh teks PDF, digabung. */
    private function teksPdf(Document $document): string
    {
        $raw = app(\App\Services\Print\PdfRenderer::class)->render($document)->output();

        $teks = '';
        foreach ($this->pdfStreams($raw) as $c) {
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $c, $lit);
            foreach ($lit[0] as $l) {
                $teks .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
        }

        return $teks;
    }

    /**
     * Foto di dalam daftar TIDAK BOLEH MENINDIH butir di atasnya.
     *
     * Yang terjadi: gambar sebaris (inline) di DomPDF duduk pada BASELINE dan
     * memanjang KE ATAS. Tinggi barisnya sendiri sudah dihitung benar — 177pt
     * untuk foto setinggi 169pt — tapi gambarnya digambar 34pt terlalu tinggi,
     * sehingga menutupi butir 2 dan 3 di atasnya. Penyusun melihat daftar yang
     * rapi di editor, lalu dua butirnya lenyap tertimpa foto di PDF.
     *
     * `vertical-align: top` pada .akt-img yang membetulkannya (DIUKUR, bukan
     * ditaksir: puncak gambar turun dari y=361.6 ke y=327.3, sedangkan butir "3."
     * ada di y=336.0). `display: block` sempat dicoba dan TAK berpengaruh sama
     * sekali di DomPDF — jangan diulang.
     *
     * Diperiksa dari matriks gambar di stream PDF (`W 0 0 H X Y cm` … `/Im Do`),
     * bukan dari HTML: yang salah dulu memang penempatannya, dan HTML-nya sudah
     * benar sejak awal.
     */
    public function test_foto_dalam_daftar_tak_menindih_butir_di_atasnya(): void
    {
        $dept = $this->aktorGl()->department->code;
        $rel = "lampiran/{$dept}/SOP/uji_".uniqid().'.png';
        $abs = storage_path('app/public/'.$rel);

        if (! is_dir(dirname($abs))) {
            mkdir(dirname($abs), 0777, true);
        }
        // Selebar tangkapan layar sungguhan — cukup tinggi untuk menindih
        // beberapa butir bila penempatannya salah lagi.
        $im = imagecreatetruecolor(1512, 730);
        imagefill($im, 0, 0, imagecolorallocate($im, 90, 140, 210));
        imagepng($im, $abs);
        imagedestroy($im);

        try {
            $doc = $this->sopDraft('SOP Foto Dalam Daftar');
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
                'sub_judul' => 'Langkah', 'pic' => 'ICT',
                'deskripsi' => '<ol><li>TINDIHsatu</li><li>TINDIHdua</li><li>TINDIHtiga</li>'
                    .'<li><img src="/storage/'.$rel.'"></li>'
                    .'<li>TINDIHlima</li><li>TINDIHenam</li></ol>',
            ]]]);

            $raw = app(\App\Services\Print\PdfRenderer::class)->render($doc->refresh())->output();

            $gambar = $this->matriksGambar($raw, 340.0);   // hanya foto selebar kolom
            $this->assertNotNull($gambar, 'foto harus tercetak');

            [$lebar, $tinggi, , $bawah] = $gambar;
            $atas = $bawah + $tinggi;

            // Aspek rasio tak boleh gepeng saat dibatasi max-width/max-height.
            $this->assertEqualsWithDelta(730 / 1512, $tinggi / $lebar, 0.01,
                'perbandingan sisi foto harus tetap');

            $baris = $this->baselineTeks($raw, 'TINDIH');
            $this->assertCount(5, $baris, 'kelima butir berteks harus tercetak');

            // Butir 1–3 ada DI ATAS foto; butir 5–6 di bawahnya. Tak satu pun
            // baseline boleh jatuh di dalam kotak foto.
            foreach (['TINDIHsatu', 'TINDIHdua', 'TINDIHtiga'] as $atasnya) {
                $this->assertGreaterThan($atas, $baris[$atasnya],
                    "\"{$atasnya}\" tertimpa foto (baseline {$baris[$atasnya]}, puncak foto {$atas})");
            }
            foreach (['TINDIHlima', 'TINDIHenam'] as $bawahnya) {
                $this->assertLessThan($bawah, $baris[$bawahnya],
                    "\"{$bawahnya}\" tertimpa foto (baseline {$baris[$bawahnya]}, dasar foto {$bawah})");
            }
        } finally {
            @unlink($abs);
        }
    }

    /**
     * Penempatan gambar TERLEBAR di halaman isi: [lebar, tinggi, x, y].
     *
     * $lebarMin menyaring logo kop & cap APPROVED yang selalu ikut tercetak.
     *
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    private function matriksGambar(string $raw, float $lebarMin): ?array
    {
        foreach ($this->pdfStreams($raw) as $c) {
            preg_match_all(
                '/([\d.]+)\s+0\s+0\s+([\d.]+)\s+([\d.-]+)\s+([\d.-]+)\s+cm\s*\/\w+\s+Do/s',
                $c, $m, PREG_SET_ORDER
            );
            foreach ($m as $g) {
                if ((float) $g[1] >= $lebarMin) {
                    return [(float) $g[1], (float) $g[2], (float) $g[3], (float) $g[4]];
                }
            }
        }

        return null;
    }

    /**
     * Baseline (y) tiap potongan teks yang memuat $tanda.
     *
     * @return array<string, float>
     */
    private function baselineTeks(string $raw, string $tanda): array
    {
        $out = [];
        foreach ($this->pdfStreams($raw) as $c) {
            if (! str_contains($c, 'BT')) {
                continue;
            }
            preg_match_all('/([\d.]+)\s+([\d.]+)\s+(?:Td|TD)\s*(.*?)(?:TJ|Tj)/s', $c, $m, PREG_SET_ORDER);
            foreach ($m as $seg) {
                preg_match_all('/\(([^()]*)\)/s', $seg[3], $lit);
                $t = trim(implode('', $lit[1]));
                if (str_contains($t, $tanda)) {
                    $out[$t] = (float) $seg[2];
                }
            }
        }

        return $out;
    }

    /** Berapa objek gambar (XObject /Image) yang benar-benar ditanam di PDF. */
    private function hitungGambar(Document $document): int
    {
        $raw = app(\App\Services\Print\PdfRenderer::class)->render($document)->output();

        return preg_match_all('#/Subtype\s*/Image#', $raw);
    }

    /**
     * Bukti terakhir: yang diketik penyusun sampai ke PDF, dan tag-nya TIDAK ikut
     * tercetak sebagai huruf. Dibaca dari stream PDF sungguhan.
     */
    public function test_isi_berformat_tercetak_di_pdf(): void
    {
        $doc = $this->sopDraft('SOP Cetak Format');
        $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
            'sub_judul' => 'Pemeriksaan',
            'pic' => 'ICT',
            'deskripsi' => '<p><strong>TEBALUJI</strong> sebelum mulai</p>'
                .'<ol><li>BUTIRSATU</li><li>BUTIRDUA</li></ol>'
                .'<blockquote>KUTIPANUJI wajib dipatuhi</blockquote>',
        ]]]);

        $raw = app(\App\Services\Print\PdfRenderer::class)->render($doc->refresh())->output();

        $teks = '';
        foreach ($this->pdfStreams($raw) as $c) {
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $c, $lit);
            foreach ($lit[0] as $l) {
                $teks .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
        }

        foreach (['TEBALUJI', 'BUTIRSATU', 'BUTIRDUA', 'KUTIPANUJI'] as $kata) {
            $this->assertStringContainsString($kata, $teks, "{$kata} harus tercetak");
        }
        $this->assertStringNotContainsString('strong', $teks, 'tag tak boleh tercetak sebagai huruf');
        $this->assertStringNotContainsString('blockquote', $teks);
    }
}
