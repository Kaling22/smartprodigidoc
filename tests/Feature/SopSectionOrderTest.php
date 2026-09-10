<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use App\Services\SchemaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Susunan bab SOP sesudah FLOWCHART disisipkan (Fase B).
 *
 *   I–IV   Tujuan · Ruang Lingkup · Referensi · Definisi   → mengalir bersama
 *   V      FLOWCHART                                        → BERDIRI SENDIRI
 *   VI–VII Aktivitas & Tanggung Jawab · Lampiran            → mengalir bersama
 *
 * Nomor bab tak lagi ditulis tangan melainkan diturunkan dari urutan larik
 * (DocumentTypeSeeder::bernomor()), jadi yang diuji di sini bukan cuma "nomornya
 * benar" tetapi bahwa urutan dan penomoran itu SEJALAN.
 */
class SopSectionOrderTest extends TestCase
{
    use DatabaseTransactions;

    private function sop(string $judul = 'SOP Urutan Bab'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    /**
     * Teks per halaman ISI (cover dibuang).
     *
     * @return array<int, string> indeks 0 = halaman isi pertama
     */
    private function halamanIsi(Document $document): array
    {
        $raw = app(PdfRenderer::class)->render($document)->output();

        $out = [];
        foreach ($this->pdfStreams($raw) as $c) {
            if (! str_contains($c, 'BT')) {
                continue;
            }

            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $c, $lit);
            $txt = '';
            foreach ($lit[0] as $l) {
                $txt .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
            $out[] = $txt;
        }

        return array_values(array_slice($out, 1));   // buang halaman cover
    }

    /** Halaman (1-based) yang memuat $teks, atau null. */
    private function halamanBerisi(array $halaman, string $teks): ?int
    {
        foreach ($halaman as $i => $t) {
            if (str_contains($t, $teks)) {
                return $i + 1;
            }
        }

        return null;
    }

    /** Isi tiap bab, secukupnya untuk membuat dokumen bertumpuk beberapa halaman. */
    private function isiPenuh(Document $doc): Document
    {
        $doc->contents()->createMany([
            ['section_key' => 'tujuan', 'value_json' => ['Tujuan pertama', 'Tujuan kedua']],
            ['section_key' => 'ruang_lingkup', 'value_json' => ['Lingkup pertama', 'Lingkup kedua']],
            ['section_key' => 'referensi', 'value_json' => ['ISO 9001:2015 Sistem Manajemen Mutu']],
            ['section_key' => 'definisi', 'value_json' => ['DEFINISIUJI adalah istilah uji']],
            ['section_key' => 'flowchart', 'value_json' => [
                ['judul' => 'FLOWCHARTUJI Alur Serah Terima', 'gambar' => '', 'keterangan' => 'KETFLOW penjelasan alur'],
            ]],
            ['section_key' => 'aktivitas', 'value_json' => [
                ['sub_judul' => 'AKTIVITASUJI Persiapan', 'deskripsi' => 'Deskripsi kegiatan persiapan.', 'pic' => 'Tim ICT'],
            ]],
            ['section_key' => 'lampiran', 'value_json' => [
                ['dokumen' => 'LAMPIRANUJI PPA-ADRO-IK-ICTMD-02', 'keterangan' => 'KETLAMP catatan lampiran'],
            ]],
        ]);

        return $doc->refresh();
    }

    /** Tujuh bab, urut, dengan nomor Romawi & auto_number yang sejalan. */
    public function test_tujuh_bab_urut_dengan_nomor_yang_sejalan(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());

        $bab = collect($schema->allSections())
            ->filter(fn ($s) => ($s['type'] ?? '') !== 'user_picker')
            ->values();

        $harapan = [
            ['tujuan', 'I. TUJUAN', '1.'],
            ['ruang_lingkup', 'II. RUANG LINGKUP', '2.'],
            ['referensi', 'III. REFERENSI', '3.'],
            ['definisi', 'IV. DEFINISI', '4.'],
            ['flowchart', 'V. FLOWCHART', '5.'],
            ['aktivitas', 'VI. AKTIVITAS DAN TANGGUNG JAWAB', '6.'],
            ['lampiran', 'VII. LAMPIRAN', '7.'],
        ];

        $this->assertCount(count($harapan), $bab, 'jumlah bab SOP harus tujuh');

        foreach ($harapan as $i => [$key, $label, $nomor]) {
            $this->assertSame($key, $bab[$i]['key'], "bab ke-".($i + 1)." harus {$key}");
            $this->assertSame($label, $bab[$i]['label'], "label bab {$key} salah");
            $this->assertSame($nomor, $bab[$i]['auto_number'], "auto_number bab {$key} harus sejalan dgn nomor Romawi");
        }
    }

    /** FLOWCHART punya ketiga isian yang diminta: judul, gambar, keterangan. */
    public function test_flowchart_punya_judul_gambar_dan_keterangan(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());
        $flow = $schema->findSection('flowchart');

        $this->assertNotNull($flow, 'bab FLOWCHART tak ada di schema SOP');

        $field = collect($flow['group_fields'])->keyBy('key');
        $this->assertSame(['judul', 'gambar', 'keterangan'], $field->keys()->all(),
            'flowchart harus punya judul, gambar, keterangan — dalam urutan itu');
        $this->assertSame('image', $field['gambar']['type'], 'gambar flowchart harus dapat diunggah');
        $this->assertSame('both', $flow['page_break'] ?? '', 'flowchart harus berdiri sendiri sehalaman');
    }

    /**
     * LAMPIRAN merujuk dokumen lain, dan komentarnya TIDAK WAJIB.
     *
     * "Tidak wajib" ditegakkan dua lapis: tak ada `required` di schema, dan
     * DocumentWizard::cleanValue() hanya membuang baris yang SELURUH kolomnya
     * kosong — baris berisi dokumen tanpa keterangan tetap tersimpan.
     */
    public function test_lampiran_memilih_dokumen_dengan_keterangan_opsional(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());
        $lampiran = $schema->findSection('lampiran');

        $field = collect($lampiran['group_fields'])->keyBy('key');
        $this->assertSame(['dokumen', 'keterangan'], $field->keys()->all(),
            'lampiran harus berisi pemilih dokumen + keterangan, tanpa unggah gambar');
        $this->assertSame('document_picker', $field['dokumen']['type']);
        $this->assertArrayNotHasKey('required', $field['keterangan'], 'keterangan lampiran tidak boleh wajib');

        // Baris tanpa keterangan TIDAK boleh dibuang.
        $tersimpan = app(\App\Services\DocumentWizard::class)->cleanValue('repeatable_group', [
            ['dokumen' => 'PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja', 'keterangan' => ''],
            ['dokumen' => '', 'keterangan' => ''],
        ]);

        $this->assertCount(1, $tersimpan, 'baris berisi dokumen tanpa keterangan harus tetap tersimpan');
        $this->assertSame('PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja', $tersimpan[0]['dokumen']);
    }

    /**
     * FLOWCHART berdiri SENDIRI: tak ada bab lain yang menumpang halamannya.
     * Bab I–IV mengalir bersama, begitu pula VI–VII.
     */
    public function test_flowchart_berdiri_sendiri_sehalaman(): void
    {
        $halaman = $this->halamanIsi($this->isiPenuh($this->sop()));

        $hal = [];
        foreach (['I. TUJUAN', 'IV. DEFINISI', 'V. FLOWCHART', 'VI. AKTIVITAS', 'VII. LAMPIRAN'] as $bab) {
            $hal[$bab] = $this->halamanBerisi($halaman, $bab);
            $this->assertNotNull($hal[$bab], "bab \"{$bab}\" tak tercetak sama sekali");
        }

        $this->assertSame($hal['I. TUJUAN'], $hal['IV. DEFINISI'], 'bab I–IV harus mengalir di halaman yang sama');
        $this->assertGreaterThan($hal['IV. DEFINISI'], $hal['V. FLOWCHART'], 'FLOWCHART harus mulai di halaman baru');
        $this->assertGreaterThan($hal['V. FLOWCHART'], $hal['VI. AKTIVITAS'], 'bab sesudah FLOWCHART harus mulai di halaman baru');
        $this->assertSame($hal['VI. AKTIVITAS'], $hal['VII. LAMPIRAN'], 'bab VI–VII harus mengalir di halaman yang sama');

        // Halaman flowchart tak boleh memuat bab lain.
        $halFlow = $halaman[$hal['V. FLOWCHART'] - 1];
        foreach (['I. TUJUAN', 'IV. DEFINISI', 'VI. AKTIVITAS', 'VII. LAMPIRAN'] as $lain) {
            $this->assertStringNotContainsString($lain, $halFlow, "halaman FLOWCHART kemasukan \"{$lain}\"");
        }
    }

    /**
     * FLOWCHART yang KOSONG tak boleh memakan halamannya sendiri.
     *
     * Babnya opsional (min_groups 0). Tanpa penjagaan ini, setiap SOP yang tak
     * memakai flowchart mencetak satu halaman penuh berisi bilah "V. FLOWCHART"
     * dan tak ada apa-apa lagi — dan halaman itu ikut terhitung di "Halaman X
     * dari Y". Babnya tetap tercetak, hanya ikut mengalir.
     */
    public function test_flowchart_kosong_tak_memakan_halaman(): void
    {
        $doc = $this->sop('SOP Tanpa Flowchart');
        $doc->contents()->createMany([
            ['section_key' => 'tujuan', 'value_json' => ['Tujuan tunggal']],
            ['section_key' => 'aktivitas', 'value_json' => [
                ['sub_judul' => 'AKTIVITASUJI', 'deskripsi' => 'Deskripsi.', 'pic' => 'ICT'],
            ]],
        ]);

        $halaman = $this->halamanIsi($doc->refresh());

        $this->assertNotNull($this->halamanBerisi($halaman, 'V. FLOWCHART'), 'bab FLOWCHART tetap harus tercetak');
        $this->assertSame(
            $this->halamanBerisi($halaman, 'I. TUJUAN'),
            $this->halamanBerisi($halaman, 'VI. AKTIVITAS'),
            'tanpa isi flowchart, bab I dan VI harus tetap di halaman yang sama',
        );
    }

    /** Isi flowchart & lampiran benar-benar tercetak. */
    public function test_isi_flowchart_dan_lampiran_tercetak(): void
    {
        $halaman = $this->halamanIsi($this->isiPenuh($this->sop()));
        $semua = implode(' ', $halaman);

        foreach (['FLOWCHARTUJI', 'KETFLOW', 'LAMPIRANUJI', 'KETLAMP'] as $teks) {
            $this->assertStringContainsString($teks, $semua, "\"{$teks}\" tak tercetak");
        }
    }

    /**
     * Lampiran bentuk LAMA (judul + gambar, sebelum jadi pemilih dokumen) tetap
     * tercetak — dokumen yang sudah tersimpan tak boleh mendadak kosong.
     */
    public function test_lampiran_bentuk_lama_tetap_tercetak(): void
    {
        $doc = $this->sop('SOP Lampiran Lama');
        $doc->contents()->create(['section_key' => 'lampiran', 'value_json' => [
            ['judul' => 'LAMPIRANLAMA Form Ceklis', 'keterangan' => 'KETLAMA caption lama', 'gambar' => ''],
        ]]);

        $semua = implode(' ', $this->halamanIsi($doc->refresh()));

        $this->assertStringContainsString('LAMPIRANLAMA', $semua, 'judul lampiran bentuk lama harus tetap tercetak');
        $this->assertStringContainsString('KETLAMA', $semua, 'keterangan lampiran bentuk lama harus tetap tercetak');
    }

    /**
     * Form wizard langkah 2 benar-benar merender kontrol barunya.
     *
     * Pemilih dokumen berupa combobox Alpine: satu kolom yang bisa DIPILIH dari
     * daftar dokumen Berlaku atau DIKETIK sendiri. Nilainya tetap milik
     * `row[field]` komponen `repeatable`, sehingga ketikan bebas tak pernah
     * tertimpa — itulah yang diuji lewat kehadiran `x-model="row['dokumen']"`.
     */
    /**
     * FLOWCHART & LAMPIRAN mulai dengan satu baris STANDBY, sama seperti
     * AKTIVITAS — penyusun langsung melihat kolomnya tanpa harus menemukan
     * tombol "+ Tambah" lebih dulu.
     *
     * Diuji dari SCHEMA, bukan dari HTML: `min_groups` itulah satu-satunya
     * saklarnya (`repeatable` di edit.blade.php membaca angka ini), jadi kalau
     * ia berubah, di sinilah ketahuannya.
     */
    public function test_flowchart_dan_lampiran_mulai_dengan_satu_baris(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());

        foreach (['flowchart', 'aktivitas', 'lampiran'] as $key) {
            $this->assertSame(1, $schema->findSection($key)['min_groups'] ?? null,
                "bab {$key} harus menyediakan satu baris standby");
        }

        // Baris standby yang DIBIARKAN kosong tetap dibuang saat disimpan —
        // itulah yang menjaga bab kosong tak memakan halaman di PDF.
        $this->assertSame([], app(\App\Services\DocumentWizard::class)->cleanValue('repeatable_group', [
            ['judul' => '', 'gambar' => '', 'keterangan' => ''],
        ]));
    }

    public function test_form_langkah_dua_merender_flowchart_dan_pemilih_dokumen(): void
    {
        $gl = $this->aktorGl();

        // Satu dokumen Berlaku sedepartemen → wajib muncul sebagai pilihan.
        $rujukan = $this->sop('Dokumen Rujukan Uji');
        $rujukan->update(['status' => 'published', 'published_at' => now()]);

        $doc = $this->sop('SOP Cek Form');
        $doc->update(['current_step' => 2]);

        $resp = $this->actingAs($gl)->get(route('documents.edit', $doc))->assertOk();
        $props = $this->propsInertia($resp);
        $seksi = $this->seksiWizard($resp);

        $label = collect($seksi)->flatMap(fn ($s) => array_merge(
            [$s['label'] ?? ''],
            collect($s['group_fields'] ?? [])->pluck('label')->all(),
        ))->all();

        foreach (['V. FLOWCHART', 'VI. AKTIVITAS DAN TANGGUNG JAWAB', 'VII. LAMPIRAN',
            // Label lampiran menyebut JUDUL sejak butir 6 — kotak utama kini
            // berisi nomor saja, judulnya turun ke kotak di bawahnya.
            'Judul Flowchart', 'Gambar Flowchart', 'Nomor Dokumen', 'Judul / Keterangan (opsional)'] as $teks) {
            $this->assertContains($teks, $label, "\"{$teks}\" tak muncul di form langkah 2");
        }

        $grup = file_get_contents(resource_path('js/components/v2/dokumen/fields/RepeatableGroup.tsx'));
        $this->assertStringContainsString("field.type === 'document_picker'", $grup, 'combobox pemilih dokumen tak dirender');
        $this->assertStringContainsString('<DocumentPicker', $grup);
        $this->assertStringContainsString('onChange={(teks) => ubahBaris(i, f.key, teks)}', $grup,
            'kolom dokumen harus tetap terikat ke barisnya — kalau tidak, ketikan bebas hilang');

        $this->assertContains($rujukan->displayNumber(), collect($props['dokumenBerlaku'])->pluck('nomor')->all(),
            'dokumen Berlaku sedepartemen harus jadi pilihan');
        // Jenisnya tak dipatok: daftar memuat SELURUH dokumen Berlaku sedepartemen,
        // urut nomor — jenis mana yang kebetulan pertama bukan urusan test ini.
        foreach (['jenis', 'nomor', 'judul'] as $kunci) {
            $this->assertArrayHasKey($kunci, $props['dokumenBerlaku'][0],
                "tiap pilihan harus membawa \"{$kunci}\" terpisah agar bisa diberi lencana & disaring");
        }

        // Lampiran tak boleh lagi menawarkan unggah gambar.
        $this->assertNotContains('Foto / Gambar (opsional)', $label, 'lampiran SOP tak lagi mengunggah gambar');
    }

    /** SP memakai fungsi schema yang sama tetapi TIDAK ikut berubah. */
    public function test_sp_tidak_ikut_berubah(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SP')->firstOrFail());

        $this->assertNull($schema->findSection('flowchart'), 'SP belum diminta memakai bab FLOWCHART');

        $lampiran = $schema->findSection('lampiran');
        $this->assertSame('VI. LAMPIRAN', $lampiran['label'], 'lampiran SP tetap bab VI');
        $this->assertTrue(
            collect($lampiran['group_fields'])->contains('key', 'gambar'),
            'lampiran SP tetap berupa unggah gambar',
        );
    }
}
