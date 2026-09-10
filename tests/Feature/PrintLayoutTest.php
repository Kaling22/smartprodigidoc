<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use App\Services\Print\PdfStream;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Menjaga tata letak PDF yang berulang kali salah: kop JSA berulang TIAP halaman
 * (docs/JSA new.docx) sedangkan blok info+TTD hanya halaman 1; footer hanya
 * SEKALI di halaman terakhir; "Halaman X dari Y" di-stamp; orientasi ikut
 * schema; seluruh TTD bercap APPROVED saat dokumen Berlaku.
 *
 * Diperiksa dari PDF yang BENAR-BENAR dirender (bukan HTML-nya), dgn membaca
 * kembali teks di dalam stream PDF.
 */
class PrintLayoutTest extends TestCase
{
    use DatabaseTransactions;

    private const FOOTER = 'Dokumen elektronik ini merupakan dokumen tidak terkendali';

    /** Render PDF sungguhan (jalur sama dgn unduhan user, termasuk stamp halaman). */
    private function renderPdf(Document $document): array
    {
        $dompdf = app(PdfRenderer::class)->render($document);
        $canvas = $dompdf->getCanvas();

        return [
            $canvas->get_page_count(),
            $canvas->get_width(),
            $canvas->get_height(),
            $this->extractText($dompdf->output()),
        ];
    }

    /**
     * Teks per HALAMAN (bukan seluruh dokumen). Kop SOP/IK/SP berulang tiap
     * halaman lewat position:fixed, jadi stream yang memuat "Dokumen"
     * (baris "No. Dokumen" pada kop) = satu halaman nyata.
     *
     * @return string[] indeks 0 = halaman 1
     */
    private function textPerPage(Document $document): array
    {
        $pages = [];
        foreach ($this->pdfStreams(app(PdfRenderer::class)->render($document)->output()) as $content) {
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $content, $literals);
            $text = '';
            foreach ($literals[0] as $literal) {
                $literal = preg_replace('/\\\\([()\\\\])/', '$1', substr($literal, 1, -1));
                $text .= str_replace("\x00", '', $literal);
            }
            $text = preg_replace('/\s+/', ' ', $text);

            if (str_contains($text, 'Dokumen')) {
                $pages[] = $text;
            }
        }

        return $pages;
    }

    /** Nomor halaman (1-based) yang memuat $needle, atau null. */
    private function pageContaining(array $pages, string $needle): ?int
    {
        foreach ($pages as $i => $text) {
            if (str_contains($text, $needle)) {
                return $i + 1;
            }
        }

        return null;
    }

    /** y-terendah dari operator teks per halaman (koordinat PDF: origin kiri-bawah). */
    private function lowestTextPerPage(Document $document): array
    {
        $raw = app(PdfRenderer::class)->render($document)->output();

        $lows = [];
        foreach ($this->pdfStreams($raw) as $c) {
            if (! str_contains($c, 'BT') || ! preg_match_all('/[\d.]+\s+([\d.]+)\s+(?:Td|TD)/', $c, $m)) {
                continue;
            }
            $lows[] = min(array_map('floatval', $m[1]));
        }

        return $lows;
    }

    /**
     * Baca teks di dalam PDF: dekompres tiap stream, ambil literal "(...)", lalu
     * buang byte null (DomPDF menulis font tertanam sebagai UTF-16BE).
     */
    private function extractText(string $raw): string
    {
        $text = '';
        foreach ($this->pdfStreams($raw) as $content) {
            $text .= PdfStream::teks($content);
        }

        return preg_replace('/\s+/', ' ', $text);
    }

    /** Dokumen JSA berisi banyak baris → dipastikan lebih dari satu halaman. */
    private function makeLongJsa(): Document
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Tata Letak'
        );

        $analisa = [];
        for ($i = 1; $i <= 6; $i++) {
            $analisa[] = [
                'langkah' => "Langkah {$i} ".str_repeat('lorem ipsum ', 4),
                'bahaya' => [[
                    'risiko' => "Risiko {$i} ".str_repeat('lorem ipsum dolor sit amet ', 6),
                    'pengendalian' => [str_repeat('lorem ipsum dolor sit amet ', 10)],
                ]],
            ];
        }

        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        // Disimpan dari widget tanggal (YYYY-MM-DD); dicetak gaya Indonesia.
        $doc->contents()->create(['section_key' => 'form_tgl_efektif', 'value_json' => '2026-09-06']);

        return $doc;
    }

    /**
     * WAJIB: pada JSA multi-halaman, isi tabel analisa HARUS mulai di halaman 1
     * (thead LANGSUNG disambut baris body — bukan halaman 1 kosong lalu isi di
     * halaman 2), dan header kolom (thead) berulang tiap halaman. Regresi lama:
     * head-block terlalu tinggi → baris pertama (atomik) di-bump ke halaman 2 →
     * halaman 1 tinggal thead & DomPDF berhenti mengulang thead. Diuji pada kondisi
     * TERBERAT: dokumen sudah Berlaku (sel stempel APPROVED menambah tinggi head-block).
     */
    public function test_jsa_body_starts_on_first_page_and_header_repeats(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Mulai Hal 1'
        );
        // Konten realistis (berspasi → wrap normal) & cukup banyak → multi-halaman.
        // Prefix "Tahap"/"Risiko" agar bisa dibedakan dari header "Uraian Langkah".
        $lang = fn (int $n) => str_repeat('kata kerja lorem ipsum dolor ', $n);
        $analisa = [];
        for ($i = 1; $i <= 12; $i++) {
            $bahaya = [];
            for ($b = 1; $b <= 2; $b++) {
                $bahaya[] = ['risiko' => 'Risiko '.$lang(3), 'pengendalian' => [$lang(4), $lang(3)]];
            }
            $analisa[] = ['langkah' => "Tahap {$i} ".$lang(5), 'bahaya' => $bahaya];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        $doc->update([
            'status' => 'published', 'published_at' => now(),
            'reviewer_id' => $this->aktorSh()->id,
            'approver_id' => $this->aktorPjo()->id,
        ]);
        $doc->refresh();

        [$pages, , , $text] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertSame($pages, substr_count($text, 'Uraian Langkah Pekerjaan'),
            'header kolom (thead) HARUS berulang tiap halaman');

        // Halaman 1 harus memuat thead DAN baris body (langkah "Tahap 1"), dgn body
        // berada DI BAWAH thead → thead langsung disambut isi, halaman 1 tak kosong.
        $theadY = $bodyY = null;
        foreach ($this->positionedText($this->pageStreams($doc)[0]) as [, $y, , $t]) {
            if (str_contains($t, 'Uraian Langkah')) $theadY = $y;
            if (str_starts_with(trim($t), 'Tahap 1')) $bodyY = $bodyY === null ? $y : max($bodyY, $y);
        }
        $this->assertNotNull($theadY, 'thead tak ada di halaman 1');
        $this->assertNotNull($bodyY, 'baris body pertama (Tahap 1) tak ada di halaman 1 — halaman 1 kosong');
        $this->assertLessThan($theadY, $bodyY, 'body harus di BAWAH thead pada halaman 1');
    }

    /**
     * INTI mesin 2-fase: saat sebuah step melintang beberapa halaman, teks Langkah
     * ("N. ...") HARUS ditulis ULANG di baris pertama tiap halaman lanjutan (spt
     * docs/JSA new.docx: "1. Lipsum" muncul lagi di halaman 2). Diukur dari PDF nyata.
     */
    public function test_jsa_step_header_repeats_on_continuation(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'Header Lanjutan'
        );
        // SATU step panjang (banyak bahaya) → melintang beberapa halaman.
        // 10 → 16: sesudah font pindah ke Helvetica (lebih sempit dari DejaVu),
        // 10 bahaya muat dalam SATU halaman sehingga uji ini kehilangan makna —
        // tak ada halaman lanjutan untuk diperiksa.
        $bahaya = [];
        for ($b = 1; $b <= 16; $b++) {
            $bahaya[] = ['risiko' => "Bahaya {$b} ".str_repeat('lorem ipsum ', 4),
                'pengendalian' => [str_repeat('kendali lorem ipsum ', 5), str_repeat('kendali lorem ipsum ', 4)]];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'LANGKAHSATU '.str_repeat('lorem ipsum ', 3), 'bahaya' => $bahaya],
        ]]);

        [$pages, , , $text] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');

        // Langkah step-1 ditulis ULANG di TIAP halaman yg dilintasinya → sebanyak
        // jumlah halaman (step tunggal mengisi semua halaman).
        $this->assertSame($pages, substr_count($text, 'LANGKAHSATU'),
            'teks Langkah harus diulang di tiap halaman lanjutan');

        // Bukti kuat: halaman 2 (lanjutan, bukan awal step) tetap memuat Langkah.
        $page2 = collect($this->positionedText($this->pageStreams($doc)[1]))
            ->contains(fn ($r) => str_contains($r[3], 'LANGKAHSATU'));
        $this->assertTrue($page2, 'halaman lanjutan (2) harus mengulang teks Langkah');
    }

    /**
     * TANPA revisi: kop JSA hanya di halaman 1 (body); halaman 2 dst. cukup HEADER
     * TABEL. Nomor halaman ikut di kop → di-stamp sekali ("1 dari N").
     */
    public function test_jsa_kop_only_on_first_page_table_header_repeats(): void
    {
        [$pages, $w, $h, $text] = $this->renderPdf($this->makeLongJsa());

        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertGreaterThan($h, $w, 'JSA harus landscape');

        // Kop (judul + baris Edisi) HANYA sekali, di halaman 1.
        $this->assertSame(1, substr_count($text, 'FORMULIR JOB SAFETY ANALYSIS'),
            'kop atas JSA hanya di HALAMAN 1 (#7)');
        $this->assertSame(1, substr_count($text, 'Edisi'), 'baris Edisi hanya di kop halaman 1');
        $this->assertSame(1, substr_count($text, 'No. Pekerjaan/JSA'),
            'blok info+TTD hanya di halaman 1');
        $this->assertSame(1, substr_count($text, self::FOOTER),
            'footer hanya sekali, di halaman terakhir');

        // Header tabel analisa TETAP berulang tiap halaman (pengganti kop).
        $this->assertSame($pages, substr_count($text, 'Uraian Langkah Pekerjaan'),
            'header tabel analisa berulang tiap halaman');

        // Nomor halaman di-stamp SEKALI di kop halaman 1.
        $this->assertSame(1, substr_count($text, "1 dari {$pages}"), 'halaman 1 bernomor "1 dari N"');
        $this->assertSame(0, substr_count($text, "2 dari {$pages}"), 'halaman 2 dst. tanpa nomor (tanpa kop)');

        // Kop halaman 1 memang memuat nomor halaman.
        $page1 = collect($this->positionedText($this->pageStreams($this->makeLongJsa())[0]))
            ->contains(fn ($r) => str_contains($r[3], 'dari '));
        $this->assertTrue($page1, 'nomor halaman berada di halaman 1');
    }

    /**
     * JSA TIDAK memakai lembar CATATAN REVISI (permintaan pemilik): walaupun
     * dokumen punya baris catatan_revisi (mis. warisan revisi sebelumnya),
     * PDF-nya langsung mulai dari body — kop tetap SEKALI di halaman 1 dan
     * nomor halaman hanya di situ.
     */
    public function test_jsa_never_prints_revision_sheet(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Revisi Kop'
        );
        // Analisa cukup panjang agar body + analisa > 1 halaman (jadi ada hal. lanjutan).
        $analisa = [];
        for ($i = 1; $i <= 6; $i++) {
            $analisa[] = ['langkah' => "Langkah {$i} ".str_repeat('lorem ipsum ', 4), 'bahaya' => [[
                'risiko' => "Risiko {$i} ".str_repeat('lorem ipsum dolor sit amet ', 6),
                'pengendalian' => [str_repeat('lorem ipsum dolor sit amet ', 10)],
            ]]];
        }
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => $analisa]);
        $doc->contents()->create(['section_key' => 'catatan_revisi', 'value_json' => [
            ['no_rev' => 1, 'tanggal' => '2026-07-21', 'halaman' => '1', 'catatan' => 'Penambahan lokasi'],
        ]]);

        [$pages, , , $text] = $this->renderPdf($doc);

        $this->assertGreaterThan(1, $pages, 'uji: body + halaman analisa lanjutan > 1 halaman');
        $this->assertStringNotContainsString('CATATAN REVISI', $text, 'JSA tanpa lembar catatan revisi');
        $this->assertStringNotContainsString('Penambahan lokasi', $text, 'baris catatan revisi tak dicetak di JSA');
        // Kop SEKALI saja (halaman 1 = body); tidak di halaman analisa lanjutan.
        $this->assertSame(1, substr_count($text, 'FORMULIR JOB SAFETY ANALYSIS'), 'kop hanya di halaman 1');
        $this->assertSame(1, substr_count($text, 'No. Pekerjaan/JSA'), 'blok info+TTD hanya di halaman 1');
        $this->assertSame(1, substr_count($text, "1 dari {$pages}"), 'nomor halaman hanya di kop halaman 1');
        $this->assertSame(0, substr_count($text, "2 dari {$pages}"), 'halaman lanjutan tanpa nomor');
    }

    /**
     * ISI HALAMAN PENUH (#4): sebuah Uraian Langkah baru harus MULAI di halaman yang
     * masih memuat langkah sebelumnya (halaman terisi ke bawah), bukan dilempar utuh
     * ke halaman baru. Mesin v10 (probe datar) melempar → halaman 1 hanya Langkah 1.
     */
    public function test_jsa_fills_page_before_starting_new_step(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Isi Penuh'
        );
        $L = 'lorem ipsum dolor sit amet ';
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            // Langkah 1: pendek (± setengah halaman).
            ['langkah' => 'LANGKAHSATU '.str_repeat($L, 3), 'bahaya' => [[
                'risiko' => 'R1 '.str_repeat($L, 4),
                'pengendalian' => [str_repeat($L, 3), str_repeat($L, 3), str_repeat($L, 3)],
            ]]],
            // Langkah 2: PANJANG → melimpah ke halaman 2, tapi awalnya HARUS muat di hal.1.
            // 8 → 14 pengendalian: sesudah font pindah ke Helvetica (lebih sempit
            // dari DejaVu) seluruh isi muat satu halaman, sehingga tak ada lagi
            // "melimpah" yang bisa diuji.
            ['langkah' => 'LANGKAHDUA '.str_repeat($L, 3), 'bahaya' => [[
                'risiko' => 'R2 '.str_repeat($L, 4),
                'pengendalian' => array_fill(0, 14, str_repeat($L, 4)),
            ]]],
        ]]);

        [$pages] = $this->renderPdf($doc);
        $this->assertGreaterThan(1, $pages, 'uji harus lebih dari satu halaman');

        // Teks halaman 1 memuat KEDUA langkah → Langkah 2 mulai mengisi hal.1 (bukan dilempar).
        $page1 = collect($this->positionedText($this->pageStreams($doc)[0]))
            ->map(fn ($r) => $r[3])->implode(' ');
        $this->assertStringContainsString('LANGKAHSATU', $page1, 'Langkah 1 di halaman 1');
        $this->assertStringContainsString('LANGKAHDUA', $page1,
            'Langkah 2 harus MULAI di halaman 1 (halaman terisi penuh, bukan dilempar utuh)');
    }

    /**
     * HANGING INDENT (#7): baris ke-2 dst. sebuah teks HARUS sejajar dgn baris
     * pertamanya. Anchor pengukuran 1pt dulu diletakkan di DEPAN sel sehingga
     * lebarnya (±2.9pt) menggeser BARIS PERTAMA ke kanan — kini anchor di AKHIR.
     * Diperiksa utk JSA (Tindakan Pengendalian) & SOP (deskripsi Aktivitas).
     */
    public function test_wrapped_lines_align_with_first_line(): void
    {
        $gl = $this->aktorGl();

        // Awal x tiap BARIS (min x per baris) untuk teks yang memuat $token.
        $lineStarts = function ($doc, string $token): array {
            $lines = [];
            foreach ($this->pageStreams($doc) as $stream) {
                foreach ($this->positionedText($stream) as [$x, $y, , $text]) {
                    if (! str_contains($text, $token)) {
                        continue;
                    }
                    $k = (string) round($y, 1);
                    $lines[$k] = isset($lines[$k]) ? min($lines[$k], round($x, 1)) : round($x, 1);
                }
            }

            return array_values($lines);
        };

        // --- JSA: satu tindakan pengendalian yang panjang (membungkus banyak baris).
        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'Indentasi Kendali'
        );
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah', 'bahaya' => [
                ['risiko' => 'Risiko', 'pengendalian' => [trim(str_repeat('KENDALIX ', 40))]],
            ]],
        ]]);

        $xs = $lineStarts($jsa, 'KENDALIX');
        $this->assertGreaterThan(2, count($xs), 'teks kendali harus membungkus beberapa baris');
        $this->assertCount(1, array_unique($xs),
            'semua baris kendali JSA harus mulai di x sama; dapat: '.implode(',', array_unique($xs)));

        // --- SOP: deskripsi aktivitas yang panjang (template & perbaikan yang sama).
        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'Indentasi Aktivitas'
        );
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Sub', 'deskripsi' => trim(str_repeat('AKTIVITASX ', 30)), 'pic' => 'ICT'],
        ]]);

        $xs = $lineStarts($sop, 'AKTIVITASX');
        $this->assertGreaterThan(2, count($xs), 'deskripsi aktivitas harus membungkus beberapa baris');
        $this->assertCount(1, array_unique($xs),
            'semua baris deskripsi aktivitas harus mulai di x sama; dapat: '.implode(',', array_unique($xs)));
    }

    /**
     * RICH TEXT Deskripsi Aktivitas benar-benar sampai ke PDF (PLAN-RICHTEXT §4.5–4.6).
     *
     * Tiga hal yang diperiksa dari stream PDF sungguhan, bukan dari HTML-nya:
     *
     *   1. Tag tak pernah tercetak sebagai huruf — kalau `<strong>` muncul di
     *      halaman, berarti sel di-escape padahal isinya HTML.
     *   2. Penanda butir "1." HANGING — ia berdiri di KIRI teksnya, bukan
     *      mendorong teks ke kanan. Inilah yang `.mk` (margin-left negatif,
     *      meniru `.jn`) kerjakan; text-indent negatif tak bisa dipakai karena
     *      DomPDF menerapkannya dua kali lipat (HANDOVER §7).
     *   3. Butir yang membungkus beberapa baris tetap RATA KIRI dgn dirinya
     *      sendiri — baris lanjutan sejajar dgn teks butir, bukan dgn nomornya.
     */
    public function test_rich_text_activity_prints_with_hanging_markers(): void
    {
        $gl = User::role('group_leader')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Rich Text'
        );
        $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [[
            'sub_judul' => 'Pemeriksaan',
            'deskripsi' => '<ol><li>BUTIRSATU '.trim(str_repeat('LANJUTAN ', 30)).'</li>'
                .'<li>BUTIRDUA</li></ol><p><strong>TEBALUJI</strong> penutup</p>',
            'pic' => 'ICT',
        ]]]);

        [, , , $teks] = $this->renderPdf($doc->refresh());

        $this->assertStringContainsString('BUTIRSATU', $teks);
        $this->assertStringContainsString('BUTIRDUA', $teks);
        $this->assertStringContainsString('TEBALUJI', $teks);
        $this->assertStringNotContainsString('strong', $teks, 'tag HTML tak boleh tercetak sebagai huruf');
        $this->assertStringNotContainsString('bull;', $teks, 'entitas tak boleh tercetak mentah');

        // x paling kiri per baris: $xTeks = run yang MEMUAT teks butir;
        // $xBaris = apa pun yang paling kiri pada baris yg sama (nomor termasuk).
        $xTeks = $xBaris = [];
        foreach ($this->pageStreams($doc) as $stream) {
            foreach ($this->positionedText($stream) as [$x, $y, , $t]) {
                $k = (string) round($y, 1);
                $xBaris[$k] = isset($xBaris[$k]) ? min($xBaris[$k], round($x, 1)) : round($x, 1);
                if (str_contains($t, 'LANJUTAN')) {
                    $xTeks[$k] = isset($xTeks[$k]) ? min($xTeks[$k], round($x, 1)) : round($x, 1);
                }
            }
        }

        $this->assertGreaterThan(2, count($xTeks), 'butir panjang harus membungkus beberapa baris');
        $this->assertCount(1, array_unique(array_values($xTeks)),
            'baris lanjutan satu butir harus mulai di x sama; dapat: '.implode(',', array_unique($xTeks)));

        // Nomor butir: 16pt di KIRI teksnya (lebar .mk) dan pada BASELINE yang
        // sama. Tanpa vertical-align -2.7pt, kotak inline-block DomPDF duduk
        // 2.747pt di atas baris teksnya — nomor melayang di atas kalimatnya.
        $nomor = null;
        foreach ($this->pageStreams($doc) as $stream) {
            foreach ($this->positionedText($stream) as [$x, $y, , $t]) {
                if (trim($t) === '1.' && $x < 70) {
                    $nomor = [$x, $y];
                }
            }
        }
        $this->assertNotNull($nomor, 'penanda butir "1." harus tercetak sebagai teks');

        $yTeks = max(array_map('floatval', array_keys($xTeks)));   // baris pertama = y terbesar
        $xTeksPertama = $xTeks[(string) $yTeks];

        $this->assertEqualsWithDelta(16.0, $xTeksPertama - $nomor[0], 0.2,
            'nomor butir harus menggantung 16pt di kiri teksnya (lebar .mk)');
        $this->assertEqualsWithDelta($yTeks, $nomor[1], 0.5,
            'nomor butir harus sebaris dgn teksnya (vertical-align .mk)');
    }
    /**
     * Kop JSA: "No. Dokumen" = nomor FORMULIR SHE (hardcode, tetap), sedangkan
     * nomor dokumen kita muncul di baris "No. Pekerjaan/JSA" — dua hal BERBEDA.
     */
    public function test_jsa_kop_form_number_vs_job_number(): void
    {
        $doc = $this->makeLongJsa();
        [, , , $text] = $this->renderPdf($doc);

        $this->assertStringContainsString('PPA-ADRO-F-SHE-03B', $text, 'No. Dokumen kop = nomor formulir SHE (hardcode)');
        $this->assertStringContainsString('No. Pekerjaan/JSA', $text);
        $this->assertStringContainsString($doc->displayNumber(), $text, 'nomor dokumen kita di baris No. Pekerjaan/JSA');
        $this->assertStringContainsString('6 September 2026', $text, 'Tgl Efektif dari widget tanggal');
        $this->assertStringNotContainsString('6 September 2022', $text, 'default lama sudah dibuang');
    }

    /** Saat Berlaku, SELURUH TTD (Dibuat/Ditinjau/Disetujui) bercap APPROVED. */
    public function test_published_document_stamps_every_signature(): void
    {
        $doc = $this->makeLongJsa();
        $doc->update([
            'status' => 'published',
            'published_at' => now(),
            'reviewer_id' => $this->aktorSh()->id,
            'approver_id' => $this->aktorPjo()->id,
        ]);

        [, , , $text] = $this->renderPdf($doc);

        $this->assertSame(3, substr_count($text, 'APPROVED'), 'ketiga TTD bercap APPROVED');
    }

    /**
     * Lembar CATATAN REVISI: tampil di depan PDF hanya utk dokumen hasil revisi —
     * dan hanya untuk SOP/IK/SP (JSA dikecualikan, lihat
     * {@see test_jsa_never_prints_revision_sheet}).
     */
    public function test_catatan_revisi_sheet_only_on_revised_documents(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            // Judul sengaja TIDAK memuat frasa "Catatan Revisi": judul ikut tercetak
            // di kop, sehingga akan mengacaukan hitungan substr_count di bawah.
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Lembar Perubahan'
        );
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan dokumen uji']]);

        [, , , $text] = $this->renderPdf($doc);
        $this->assertStringNotContainsString('CATATAN REVISI', $text, 'dokumen non-revisi tanpa lembar catatan');

        $doc->contents()->create(['section_key' => 'catatan_revisi', 'value_json' => [
            ['no_rev' => 1, 'tanggal' => '2026-07-18', 'halaman' => '1-2', 'catatan' => 'Perubahan detail aktivitas'],
        ]]);
        $doc->refresh();

        [, , , $text] = $this->renderPdf($doc);
        // 2 = judul lembar + nama kolom terakhir (keduanya "CATATAN REVISI").
        $this->assertSame(2, substr_count($text, 'CATATAN REVISI'), 'lembar catatan revisi tampil sekali');
        $this->assertStringContainsString('18 JULI 2026', $text, 'tanggal berformat Indonesia');
        $this->assertStringContainsString('Perubahan detail aktivitas', $text);
    }

    /**
     * Bentuk BERSARANG lembar CATATAN REVISI: satu baris = satu sesi revisi,
     * tiap temuan tercetak sebagai butir a./b./c. di dalam sel yang sama.
     *
     * Penandanya sengaja SATU KATA (`PimpinanDiganti`, `PjoDiganti`): spasi
     * antar kata di stream PDF bisa berupa penggeseran koordinat, bukan
     * karakter spasi — alasan yang sama dengan test_sub_judul_aktivitas_tak_pernah_yatim.
     *
     * Butir yang catatannya KOSONG tak boleh tercetak: kerangkanya memang
     * tersimpan sejak tombol Revisi ditekan, dan huruf c. yang menggantung
     * tanpa kalimat adalah cara gagal yang tak membuat gerbang lain merah.
     */
    public function test_catatan_revisi_mencetak_sub_poin_berhuruf(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Sub Poin'
        );
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan dokumen uji']]);
        $doc->contents()->create(['section_key' => 'catatan_revisi', 'value_json' => [
            ['no_rev' => 1, 'tanggal' => '2026-07-18', 'halaman' => '1-2, 5', 'catatan' => 'Perubahan', 'sub' => [
                ['catatan' => 'PimpinanDiganti'],
                ['catatan' => 'PjoDiganti'],
                ['catatan' => ''],
            ]],
        ]]);
        $doc->refresh();

        [, , , $text] = $this->renderPdf($doc);

        $this->assertStringContainsString('PimpinanDiganti', $text, 'sub-poin pertama tercetak');
        $this->assertStringContainsString('PjoDiganti', $text, 'sub-poin kedua tercetak');
        $this->assertSame(1, substr_count($text, 'a.'), 'penanda huruf a. tercetak sekali');
        $this->assertSame(1, substr_count($text, 'b.'), 'penanda huruf b. tercetak sekali');
        $this->assertStringNotContainsString('c.', $text, 'butir kosong tak bernomor huruf sama sekali');
    }

    /**
     * JUDUL BAB TIDAK PERNAH YATIM (SOP/SP/IK): bilah judul bab wajib sehalaman
     * dengan DUA poin pertamanya; bila sisa halaman tak cukup, seluruh bab pindah
     * ke halaman berikutnya.
     *
     * Tiga ukuran isi di bawah BUKAN angka sembarang — ketiganya hasil pengukuran
     * PDF nyata yang SEBELUM perbaikan menghasilkan bab yatim:
     *   n=8  → "III. REFERENSI" sendirian di dasar hal. 1
     *   n=13 → "II. RUANG LINGKUP" sendirian di dasar hal. 1
     *   n=17 → "IV. DEFINISI" sendirian di dasar hal. 2 (kasus di laporan pemilik)
     *
     * Bab V (AKTIVITAS) hanya diwajibkan ditemani poin PERTAMA: satu poin di sana
     * = satu grup aktivitas yang bisa panjang berhalaman, jadi menuntut dua grup
     * ikut serta akan mendorong blok besar tanpa alasan.
     */
    public function test_chapter_heading_never_orphaned_from_its_points(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $babs = [
            'I. TUJUAN' => ['1.1', '1.2'],
            'II. RUANG LINGKUP' => ['2.1', '2.2'],
            'III. REFERENSI' => ['3.1', '3.2'],
            'IV. DEFINISI' => ['4.1', '4.2'],
            // Bab V kini FLOWCHART; aktivitas bergeser ke VI (Fase B).
            'VI. AKTIVITAS DAN TANGGUNG JAWAB' => ['6.1'],
        ];

        foreach ([8, 13, 17] as $n) {
            $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Bab Yatim');
            $items = fn (int $count, string $word) => array_map(
                fn ($i) => "{$word} {$i} ".str_repeat('lorem ipsum dolor sit amet consectetur ', 3),
                range(1, $count)
            );
            $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => $items($n, 'Tujuan')]);
            $doc->contents()->create(['section_key' => 'ruang_lingkup', 'value_json' => $items(4, 'Lingkup')]);
            $doc->contents()->create(['section_key' => 'referensi', 'value_json' => $items(4, 'Referensi')]);
            $doc->contents()->create(['section_key' => 'definisi', 'value_json' => $items(5, 'Definisi')]);
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
                ['sub_judul' => 'Kegiatan A', 'deskripsi' => "Paragraf satu.\nParagraf dua.", 'pic' => 'ICT'],
                ['sub_judul' => 'Kegiatan B', 'deskripsi' => "Paragraf satu.\nParagraf dua.", 'pic' => 'ICT'],
            ]]);

            $pages = $this->textPerPage($doc->refresh());

            foreach ($babs as $bar => $poinPertama) {
                $halBar = $this->pageContaining($pages, $bar);
                $this->assertNotNull($halBar, "bilah bab {$bar} harus tercetak (n={$n})");

                foreach ($poinPertama as $poin) {
                    $halPoin = $this->pageContaining($pages, $poin);
                    $this->assertSame($halBar, $halPoin,
                        "n={$n}: bab \"{$bar}\" di hal. {$halBar} tapi poin {$poin} di hal. ".($halPoin ?? '—').
                        ' — judul bab tidak boleh terpisah dari poin-poinnya');
                }
            }
        }
    }

    /**
     * Kolom pengesahan memuat jabatan + DEPARTEMEN (permintaan pemilik):
     * "Group Leader (ICTMD)". PJO tak punya departemen → cukup "PJO", tanpa
     * kurung kosong. Berlaku untuk halaman pengesahan SOP/SP/IK maupun blok
     * TTD JSA.
     */
    public function test_approval_columns_show_jabatan_with_department(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();
        $this->assertNull($pjo->department_id, 'prasyarat: PJO tanpa departemen');

        $peserta = ['reviewer_id' => $sh->id, 'approver_id' => $pjo->id];
        $dept = $gl->department->code;

        // SOP — kolom JABATAN pada halaman pengesahan.
        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Jabatan Dept'
        );
        $sop->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan uji']]);
        $sop->update($peserta);

        // Kolom JABATAN sempit (20% lebar, mengikuti grid docx resmi) → DomPDF
        // MEMBUNGKUS "(DEPT)" ke baris kedua, dan saat dibaca dari stream PDF
        // spasi antar-baris hilang. Keduanya sah, jadi terima dua-duanya.
        $memuat = fn (string $teks, string $jabatan) => str_contains($teks, "{$jabatan} ({$dept})")
            || str_contains($teks, "{$jabatan}({$dept})");

        [, , , $text] = $this->renderPdf($sop->refresh());
        $this->assertTrue($memuat($text, 'Group Leader'), 'pembuat: jabatan + departemen');
        $this->assertTrue($memuat($text, 'Section Head'), 'peninjau: jabatan + departemen');
        $this->assertStringContainsString('PJO', $text, 'penyetuju tanpa departemen tetap "PJO"');
        $this->assertStringNotContainsString('PJO ()', $text, 'tak ada kurung kosong utk PJO');
        $this->assertStringNotContainsString('PJO()', $text, 'tak ada kurung kosong utk PJO');

        // JSA — baris "Jabatan :" pada blok TTD.
        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Jabatan Dept'
        );
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah', 'bahaya' => [['risiko' => 'Risiko', 'pengendalian' => ['Kendali']]]],
        ]]);
        $jsa->update($peserta);

        // Kolom TTD JSA lebih lebar (landscape) → muat satu baris utuh.
        [, , , $text] = $this->renderPdf($jsa->refresh());
        $this->assertStringContainsString("Jabatan : Group Leader ({$dept})", $text, 'TTD JSA: jabatan + departemen');
        $this->assertStringContainsString("Jabatan : Section Head ({$dept})", $text, 'TTD JSA: peninjau');
        $this->assertStringContainsString('Jabatan : PJO', $text, 'TTD JSA: PJO tanpa departemen');
    }

    /**
     * WAJIB (#1/#5A): tak ada teks yang melewati margin bawah 2cm (57pt).
     * Dulu aktivitas panjang terpotong di dasar halaman (tabel bersarang atomik).
     */
    public function test_no_text_crosses_bottom_margin(): void
    {
        $gl = $this->aktorGl();

        // SOP: aktivitas panjang berbilah banyak → multi-halaman.
        $bigDesc = implode("\n", array_map(fn ($k) => "{$k}. ".str_repeat('lorem ipsum dolor sit amet ', 12), range(1, 10)));
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Margin');
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Kegiatan A', 'deskripsi' => $bigDesc, 'pic' => 'ICT'],
            ['sub_judul' => 'Kegiatan B', 'deskripsi' => $bigDesc, 'pic' => 'ICT'],
        ]]);

        foreach ($this->lowestTextPerPage($sop) as $page => $y) {
            $this->assertGreaterThanOrEqual(55.0, $y, "SOP halaman ".($page + 1)." menembus margin bawah 2cm (y={$y})");
        }

        foreach ($this->lowestTextPerPage($this->makeLongJsa()) as $page => $y) {
            $this->assertGreaterThanOrEqual(55.0, $y, "JSA halaman ".($page + 1)." menembus margin bawah 2cm (y={$y})");
        }
    }

    /**
     * Operator mentah tiap halaman ISI (sudah didekompres).
     *
     * Halaman COVER sengaja DIBUANG di sini, bukan di tiap pemanggil: seluruh
     * test di berkas ini memeriksa tata letak ISI, dan indeks 0 di semuanya
     * berarti "halaman isi pertama". Cover diperiksa terpisah di CoverPageTest,
     * yang memakai {@see coverStream()}.
     *
     * @return string[] indeks 0 = halaman isi pertama
     */
    private function pageStreams(Document $document): array
    {
        $out = $this->allPageStreams($document);

        return $this->punyaCover($document) ? array_values(array_slice($out, 1)) : $out;
    }

    /** Halaman COVER (halaman 1) — null bila jenis dokumennya tak memakainya. */
    private function coverStream(Document $document): ?string
    {
        return $this->punyaCover($document) ? ($this->allPageStreams($document)[0] ?? null) : null;
    }

    private function punyaCover(Document $document): bool
    {
        return (\App\Services\SchemaService::for($document->type)->raw()['cover_page'] ?? null) === '_cover';
    }

    /** Operator mentah SELURUH halaman, cover termasuk. */
    private function allPageStreams(Document $document): array
    {
        $raw = app(PdfRenderer::class)->render($document)->output();

        $out = [];
        foreach ($this->pdfStreams($raw) as $c) {
            if (str_contains($c, 'BT')) {
                $out[] = $c;
            }
        }

        return $out;
    }

    /** Teks + posisinya: [x, y, ukuran font, isi] per operator Td. */
    private function positionedText(string $stream): array
    {
        // DomPDF menulis "/F2 16.0 Tf" SESUDAH Td, di dalam segmen yang sama —
        // jadi ukuran font diambil dari segmen itu sendiri, bukan dilacak berurutan.
        preg_match_all('/([\d.]+)\s+([\d.]+)\s+(?:Td|TD)\s*(.*?)(?:TJ|Tj)/s', $stream, $m, PREG_SET_ORDER);

        $size = 0.0;
        $rows = [];
        foreach ($m as $seg) {
            if (preg_match('#/[A-Za-z0-9_+.-]+\s+([\d.]+)\s+Tf#', $seg[3], $tf)) {
                $size = (float) $tf[1];
            }
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $seg[3], $lit);
            $t = '';
            foreach ($lit[0] as $l) {
                $t .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
            if (trim($t) !== '') {
                $rows[] = [(float) $seg[1], (float) $seg[2], $size, $t];
            }
        }

        return $rows;
    }

    /**
     * JENIS & JUDUL dokumen di kop HARUS besar (14pt / 12pt), bukan 8pt.
     * Pernah regresi diam-diam: `.kop-title` polos KALAH spesifisitas lawan
     * `table.kop td` (0,1,0 vs 0,1,2) → font-size tak pernah terpakai.
     */
    public function test_kop_title_font_is_enlarged(): void
    {
        $gl = $this->aktorGl();
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'Judul Kop Besar');

        $found = [];
        foreach ($this->positionedText($this->pageStreams($sop)[0]) as [, , $size, $text]) {
            if (str_starts_with($text, 'STANDARD')) {
                $found['jenis'] = $size;
            }
            if (str_starts_with($text, 'JUDUL KOP')) {
                $found['judul'] = $size;
            }
        }

        $this->assertSame(14.0, $found['jenis'] ?? null, 'JENIS dokumen harus 14pt');
        $this->assertSame(12.0, $found['judul'] ?? null, 'JUDUL dokumen harus 12pt');

        foreach ($this->positionedText($this->pageStreams($this->makeLongJsa())[0]) as [, , $size, $text]) {
            if (str_starts_with($text, 'FORMULIR')) {
                // v11 (#7): kop dirampingkan 16→13pt agar ruang tabel bertambah.
                $this->assertSame(13.0, $size, 'judul FORMULIR JSA harus 13pt (kop ramping)');
            }
        }
    }

    /**
     * Nomor "6.1" harus SEBARIS dgn sub-judulnya. Baseline inline-block DomPDF
     * membuat nomor mengambang 2.7pt di atas teks; dikoreksi vertical-align.
     */
    public function test_activity_number_aligns_with_its_title(): void
    {
        $gl = $this->aktorGl();
        $sop = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Sejajar');
        $sop->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Lipsum', 'deskripsi' => 'ada lagi revisi nya', 'pic' => 'ICT'],
        ]]);

        // Ditelusuri di SELURUH halaman isi, bukan hanya halaman pertama: bab
        // mana yang mendarat di halaman berapa bukan urusan test ini, dan
        // mematoknya ke halaman 1 membuatnya pecah tiap kali susunan bab
        // berubah (terjadi saat FLOWCHART disisipkan).
        $numY = $titleY = null;
        foreach ($this->pageStreams($sop) as $stream) {
            foreach ($this->positionedText($stream) as [, $y, , $text]) {
                if ($text === '6.1') {   // aktivitas kini bab VI (Fase B)
                    $numY = $y;
                }
                if ($text === 'Lipsum') {
                    $titleY = $y;
                }
            }
        }

        $this->assertNotNull($numY, 'nomor 6.1 tak ditemukan');
        $this->assertNotNull($titleY, 'sub-judul tak ditemukan');
        $this->assertLessThanOrEqual(0.6, abs($numY - $titleY), "nomor & sub-judul tak sejajar (nomor y={$numY}, judul y={$titleY})");
    }

    /**
     * Teks JSA tak boleh MENEMBUS kolom sebelah. Kata sangat panjang tanpa spasi
     * dulu meluber karena DomPDF MENGABAIKAN `word-break` — hanya `overflow-wrap`
     * (nilai `anywhere`) yang memenggal di tengah kata.
     */
    public function test_jsa_text_never_crosses_column_borders(): void
    {
        $long = str_repeat('kmlklm', 12).str_repeat('m', 40);
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Luber');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => $long, 'bahaya' => [
                ['risiko' => $long, 'pengendalian' => [$long, $long]],
                ['risiko' => $long, 'pengendalian' => [$long]],
            ]],
        ]]);

        // Batas kolom tabel analisa: 20/20/46/14 dari lebar isi (A4 landscape,
        // margin kiri/kanan 8pt).
        $w = 842 - 16;
        $edges = [8.0, 8 + $w * .20, 8 + $w * .40, 8 + $w * .86, 8 + $w];

        $fm = (new \Dompdf\Dompdf())->getFontMetrics();
        $font = $fm->getFont('Arial', 'normal');

        foreach ($this->pageStreams($doc) as $page => $stream) {
            foreach ($this->positionedText($stream) as [$x, , , $text]) {
                if (! str_contains($text, 'kml')) {
                    continue;
                }
                $right = $x + $fm->getTextWidth($text, $font, 8.0);
                foreach ($edges as $edge) {
                    if ($edge > $x + 2) {
                        $this->assertLessThanOrEqual($edge + 6, $right, 'teks menembus batas kolom di halaman '.($page + 1));

                        break;
                    }
                }
            }
        }
    }

    public function test_sop_is_portrait_with_single_footer(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Tata Letak'
        );

        $long = [];
        for ($i = 1; $i <= 30; $i++) {
            $long[] = "Butir {$i} ".str_repeat('lorem ipsum dolor sit amet ', 10);
        }
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => $long]);

        [$pages, $w, $h, $text] = $this->renderPdf($doc);

        // Cover TIDAK ikut dinomori dan tak ikut dihitung sebagai penyebut
        // (kebiasaan Word): pembaca menghitung halaman ISI, bukan lembar kertas.
        $isi = $pages - 1;

        $this->assertGreaterThan(1, $pages, 'isi uji harus lebih dari satu halaman');
        $this->assertGreaterThan($w, $h, 'SOP harus portrait');
        $this->assertSame(1, substr_count($text, self::FOOTER), 'footer hanya sekali, di halaman terakhir');
        $this->assertSame($isi, substr_count($text, "dari {$isi}"), '"Halaman: X dari Y" di-stamp tiap halaman ISI');
        $this->assertSame(1, substr_count($text, "Halaman: 1 dari {$isi}"), 'halaman isi pertama bernomor 1');
        $this->assertStringNotContainsString("Halaman: {$pages} dari", $text, 'cover tak boleh ikut bernomor');
    }

    /**
     * Satu grup AKTIVITAS yang panjang dan terpotong beberapa halaman WAJIB
     * mencetak ulang sel PIC-nya di tiap halaman lanjutan.
     *
     * Ini gejala yang terlihat pengguna ketika mesin paginasi 2-fase salah
     * menyimpulkan titik potong: sel PIC dicetak sekali dgn rowspan yang
     * membentang menembus batas halaman, sehingga di halaman lanjutan kolom PIC
     * kosong TANPA garis, dan sel AKTIVITAS di halaman sebelumnya kehilangan
     * border bawahnya (kelas `mrg-mid` membuang border-bottom).
     *
     * Penyebab yang pernah terjadi ada di pembongkar stream PDF — lihat
     * {@see \Tests\Unit\PdfStreamTest}. Test ini menjaga AKIBATNYA, apa pun
     * sebab barunya nanti.
     */
    public function test_pic_dicetak_ulang_di_tiap_halaman_lanjutan_grup_panjang(): void
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Aktivitas Panjang'
        );

        // Satu grup, banyak paragraf → pasti melewati beberapa halaman.
        $paragraf = [];
        for ($i = 1; $i <= 60; $i++) {
            $paragraf[] = "Paragraf {$i} ".str_repeat('lorem ipsum dolor sit amet consectetur ', 4);
        }
        $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Kegiatan Panjang', 'deskripsi' => implode("\n", $paragraf), 'pic' => 'Pengawas Lapangan'],
        ]]);

        $pages = $this->textPerPage($doc->refresh());

        $halAktivitas = [];
        foreach ($pages as $i => $text) {
            if (str_contains($text, 'Paragraf ')) {
                $halAktivitas[] = $i + 1;
            }
        }

        $this->assertGreaterThanOrEqual(3, count($halAktivitas),
            'prasyarat: grup harus terpotong beberapa halaman');

        foreach ($halAktivitas as $hal) {
            $this->assertStringContainsString('Pengawas Lapangan', $pages[$hal - 1],
                "kolom PIC hilang di halaman {$hal} — sel rowspan menembus batas halaman");
        }
    }

    /**
     * SUB-JUDUL AKTIVITAS TAK PERNAH YATIM: baris judul grup (6.1, 6.2, …) wajib
     * sehalaman dengan SETIDAKNYA satu baris deskripsinya.
     *
     * Aturan "judul BAB tak yatim" (test di atas) dijaga CSS; sub-bab TIDAK bisa —
     * DomPDF tak mengenal `widows`/`orphans`, dan `keep-next` hanya terpasang di
     * baris PERTAMA tabel. Yang menjaganya karena itu mesin paginasi:
     * {@see \App\Services\ActivityPrintLayout::hindariYatim()}.
     *
     * Panjang isian disapu (bukan satu angka) karena titik potong bergantung pada
     * tinggi bab-bab di atasnya: satu ukuran saja hanya menguji satu titik potong,
     * dan yatim justru muncul di ukuran tertentu.
     *
     * Penanda sengaja SATU KATA ("TahapAlfa", "AwalAlfa"): spasi antar kata di
     * stream PDF bisa berupa penggeseran koordinat, bukan karakter spasi, jadi
     * frasa dua kata tak selalu terbaca utuh.
     */
    public function test_sub_judul_aktivitas_tak_pernah_yatim(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $isi = str_repeat('lorem ipsum dolor sit amet consectetur adipiscing ', 4);
        $nama = ['Alfa', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot'];

        $grup = array_map(fn ($g) => [
            'sub_judul' => 'Tahap'.$g,
            'deskripsi' => "Awal{$g}. {$isi}\nTengah{$g}. {$isi}\nAkhir{$g}. {$isi}",
            'pic' => 'ICT',
        ], $nama);

        foreach (range(8, 16) as $n) {
            $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Sub-Bab Yatim');
            $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => array_map(
                fn ($i) => "Tujuan {$i} {$isi}", range(1, $n)
            )]);
            $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => $grup]);

            $pages = $this->textPerPage($doc->refresh());

            foreach ($nama as $i => $g) {
                $halJudul = $this->pageContaining($pages, 'Tahap'.$g);
                $halBaris = $this->pageContaining($pages, 'Awal'.$g);

                $this->assertNotNull($halJudul, "n={$n}: sub-judul Tahap{$g} harus tercetak");
                $this->assertSame($halJudul, $halBaris,
                    "n={$n}: sub-judul \"6.".($i + 1)." Tahap{$g}\" di hal. {$halJudul} tapi baris ".
                    'pertamanya di hal. '.($halBaris ?? '—').' — sub-judul tidak boleh sendirian di dasar halaman');
            }
        }
    }
}
