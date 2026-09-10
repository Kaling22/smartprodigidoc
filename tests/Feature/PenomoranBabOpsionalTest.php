<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use App\Services\SchemaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Bab OPSIONAL & penomoran bab yang ikut berpindah (Fase B).
 *
 * FLOWCHART kini dinyatakan lewat sakelar "Gunakan Flowchart", bukan dengan
 * membiarkannya kosong. Dimatikan → babnya tak tercetak DAN bab sesudahnya naik
 * nomor: AKTIVITAS jadi V (5.1, 5.2…), LAMPIRAN jadi VI. Nomor bolong ("bab V
 * kosong lalu VI–VII") tak pantas untuk dokumen mutu.
 *
 * Yang dijaga di sini, dan kenapa:
 *
 *   1. Kunci `pakai_flowchart` yang TAK ADA berarti MENYALA. Kalau bab dinilai
 *      dari isinya ("kosong = tak dipakai"), setiap SOP yang terlanjur terbit
 *      dengan flowchart kosong akan dinomori ulang tanpa ada yang menyentuhnya.
 *   2. Mematikan sakelar MENYEMBUNYIKAN bab, bukan menghapus isinya — jalur
 *      SIMPAN memakai schema penuh, jalur TAMPIL yang dinomori ulang.
 *   3. SP & IK tak punya bab opsional, jadi tak boleh bergeser sedikit pun.
 */
class PenomoranBabOpsionalTest extends TestCase
{
    use DatabaseTransactions;

    private function sop(string $judul = 'SOP Bab Opsional'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    /** Isi minimal supaya PDF-nya punya sesuatu untuk dicetak. */
    private function isi(Document $doc, ?string $pakaiFlowchart = null): Document
    {
        $baris = [
            ['section_key' => 'tujuan', 'value_json' => ['Tujuan tunggal']],
            ['section_key' => 'flowchart', 'value_json' => [
                ['judul' => 'FLOWCHARTUJI Alur', 'gambar' => '', 'keterangan' => 'KETFLOW'],
            ]],
            ['section_key' => 'aktivitas', 'value_json' => [
                ['sub_judul' => 'AKTIVITASUJI', 'deskripsi' => 'Deskripsi.', 'pic' => 'ICT'],
            ]],
        ];

        if ($pakaiFlowchart !== null) {
            $baris[] = ['section_key' => 'pakai_flowchart', 'value_json' => $pakaiFlowchart];
        }

        $doc->contents()->createMany($baris);

        return $doc->refresh();
    }

    /** Seluruh teks PDF, halaman digabung. */
    private function teksPdf(Document $document): string
    {
        $raw = app(PdfRenderer::class)->render($document)->output();

        $teks = '';
        foreach ($this->pdfStreams($raw) as $c) {
            if (! str_contains($c, 'BT')) {
                continue;
            }
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $c, $lit);
            foreach ($lit[0] as $l) {
                $teks .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
        }

        return $teks;
    }

    /** @return array<string, array{label:string, auto_number:string}> bab → label & prefix */
    private function bab(SchemaService $schema): array
    {
        return collect($schema->allSections())
            ->filter(fn ($s) => ($s['type'] ?? '') !== 'user_picker')
            ->mapWithKeys(fn ($s) => [$s['key'] => [
                'label' => $s['label'] ?? '',
                'auto_number' => $s['auto_number'] ?? '',
            ]])
            ->all();
    }

    /**
     * KUNCI TAK ADA → MENYALA. Dokumen yang sudah ada tercetak PERSIS seperti
     * sebelum fitur ini ada — ini jaring pengaman butir 1 di atas.
     */
    public function test_tanpa_kunci_sakelar_penomoran_tak_bergeser(): void
    {
        $doc = $this->isi($this->sop());
        $bab = $this->bab(SchemaService::untuk($doc));

        $this->assertSame('V. FLOWCHART', $bab['flowchart']['label']);
        $this->assertSame('VI. AKTIVITAS DAN TANGGUNG JAWAB', $bab['aktivitas']['label']);
        $this->assertSame('6.', $bab['aktivitas']['auto_number']);
        $this->assertSame('VII. LAMPIRAN', $bab['lampiran']['label']);
    }

    /** Nilai '1' yang eksplisit sama saja dengan tak ada kunci. */
    public function test_sakelar_menyala_sama_dengan_keadaan_sekarang(): void
    {
        $doc = $this->isi($this->sop('SOP Sakelar Nyala'), '1');

        $this->assertSame('VI. AKTIVITAS DAN TANGGUNG JAWAB',
            $this->bab(SchemaService::untuk($doc))['aktivitas']['label']);
    }

    /** '0' → bab flowchart dibuang, bab sesudahnya naik satu nomor. */
    public function test_sakelar_mati_menaikkan_nomor_bab_sesudahnya(): void
    {
        $doc = $this->isi($this->sop('SOP Tanpa Flowchart'), '0');
        $bab = $this->bab(SchemaService::untuk($doc));

        $this->assertArrayNotHasKey('flowchart', $bab, 'bab flowchart harus hilang dari daftar section');
        $this->assertSame('V. AKTIVITAS DAN TANGGUNG JAWAB', $bab['aktivitas']['label']);
        $this->assertSame('5.', $bab['aktivitas']['auto_number'], 'lencana baris aktivitas harus jadi 5.1, 5.2…');
        $this->assertSame('VI. LAMPIRAN', $bab['lampiran']['label']);
        $this->assertSame('6.', $bab['lampiran']['auto_number']);

        // Bab I–IV di depan flowchart tak boleh ikut bergeser.
        $this->assertSame('I. TUJUAN', $bab['tujuan']['label']);
        $this->assertSame('IV. DEFINISI', $bab['definisi']['label']);

        // Judul langkah ikut menyebut bab yang benar-benar dipakai.
        $this->assertSame('Aktivitas, Lampiran & Verifikasi', SchemaService::untuk($doc)->stepTitle(2));
    }

    /** Yang tercetak pun ikut: PDF tak lagi memuat bab FLOWCHART. */
    public function test_pdf_tak_memuat_flowchart_saat_sakelar_mati(): void
    {
        // Judulnya sengaja TAK memuat kata "flowchart" — judul ikut tercetak
        // di kop tiap halaman dan akan membuat asersi di bawah salah tuduh.
        $teks = $this->teksPdf($this->isi($this->sop('SOP Cetak Tanpa Bagan'), '0'));

        $this->assertStringNotContainsString('FLOWCHART', $teks, 'bab FLOWCHART tak boleh tercetak saat sakelarnya mati');
        $this->assertStringNotContainsString('KETFLOW', $teks, 'isi flowchart tak boleh tercetak saat sakelarnya mati');
        $this->assertStringContainsString('V. AKTIVITAS DAN TANGGUNG JAWAB', $teks);
        $this->assertStringContainsString('VI. LAMPIRAN', $teks);
        $this->assertStringContainsString('AKTIVITASUJI', $teks, 'isi aktivitas tetap tercetak');
    }

    /**
     * Mematikan sakelar MENYEMBUNYIKAN, bukan menghapus.
     *
     * Diuji lewat jalur sungguhan (POST langkah 2), karena inilah yang dijaga:
     * jalur SIMPAN memakai schema PENUH, jadi bab flowchart tetap tersimpan
     * walau sakelarnya mati — dan isinya utuh saat dinyalakan lagi.
     */
    public function test_sakelar_mati_tak_menghapus_isi_flowchart(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->sop('SOP Simpan Sakelar');
        $doc->update(['current_step' => 2]);

        $kirim = fn (string $sakelar) => $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2,
            'action' => 'save',
            'sections' => [
                'pakai_flowchart' => $sakelar,
                'flowchart' => [['judul' => 'FLOWCHARTUJI Alur', 'gambar' => '', 'keterangan' => 'KETFLOW']],
                'aktivitas' => [['sub_judul' => 'AKTIVITASUJI', 'deskripsi' => 'Deskripsi.', 'pic' => 'ICT']],
            ],
        ]);

        $kirim('0')->assertRedirect();
        $isi = $doc->refresh()->contentMap();

        $this->assertSame('0', $isi['pakai_flowchart'], 'sakelar harus tersimpan sebagai isi dokumen');
        $this->assertSame('FLOWCHARTUJI Alur', $isi['flowchart'][0]['judul'],
            'isi flowchart WAJIB tetap tersimpan walau sakelarnya mati');
        $this->assertArrayNotHasKey('flowchart', $this->bab(SchemaService::untuk($doc)));

        // Dinyalakan lagi → semuanya kembali seperti semula.
        $kirim('1')->assertRedirect();
        $isi = $doc->refresh()->contentMap();

        $this->assertSame('1', $isi['pakai_flowchart']);
        $this->assertSame('FLOWCHARTUJI Alur', $isi['flowchart'][0]['judul']);
        $this->assertSame('VI. AKTIVITAS DAN TANGGUNG JAWAB',
            $this->bab(SchemaService::untuk($doc))['aktivitas']['label']);
    }

    /** Form merender sakelarnya, DAN kedua varian nomor bab (§5.4: PHP yang menomori). */
    public function test_form_merender_sakelar_dan_dua_varian_nomor(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->isi($this->sop('SOP Form Sakelar'));
        $doc->update(['current_step' => 2]);

        $resp = $this->actingAs($gl)->get(route('documents.edit', $doc))->assertOk();
        $props = $this->propsInertia($resp);
        $seksi = $this->seksiWizard($resp);

        $this->assertSame('Gunakan Flowchart', $seksi['flowchart']['toggle_label'] ?? null, 'sakelar bab opsional tak dirender');
        $this->assertSame('pakai_flowchart', $seksi['flowchart']['toggle_key'] ?? null, 'nilai sakelar tak ikut terkirim');

        // Kedua label sampai ke layar; React tinggal memilih. Bab flowchart
        // TETAP dirender walau kelak dimatikan — kalau tidak, sakelarnya ikut
        // hilang dan tak ada cara menyalakannya kembali.
        $this->assertSame('V. FLOWCHART', $seksi['flowchart']['label']);
        $this->assertSame('VI. AKTIVITAS DAN TANGGUNG JAWAB', $seksi['aktivitas']['label']);
        $this->assertSame('V. AKTIVITAS DAN TANGGUNG JAWAB', $props['labelMati']['aktivitas']['label'] ?? null,
            'varian "sakelar mati" tak dikirim ke layar');
        $this->assertTrue($props['babAktif'], 'sakelar menyala');
    }

    /**
     * Sakelar mati → isian bab DIREDUPKAN, bukan disembunyikan.
     *
     * Bedanya penting: kolom yang hilang dari layar membuat penyusun mengira
     * ketikannya terhapus. Kuncinya `inert` (bukan `disabled`) — kolom
     * disabled tak ikut terkirim, jadi isian yang sudah diketik akan benar-benar
     * hilang begitu langkah ini disimpan.
     */
    public function test_form_saat_sakelar_mati_meredupkan_bab_bukan_menyembunyikan(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->isi($this->sop('SOP Form Sakelar Mati'), '0');
        $doc->update(['current_step' => 2]);

        $resp = $this->actingAs($gl)->get(route('documents.edit', $doc))->assertOk();
        $props = $this->propsInertia($resp);
        $seksi = $this->seksiWizard($resp);

        // Bab flowchart TETAP dikirim ke layar — schema form memakai
        // SchemaService::for(), bukan ::untuk() yang MEMBUANG bab mati.
        $this->assertArrayHasKey('flowchart', $seksi, 'isian flowchart harus tetap terlihat, hanya mati');
        $this->assertSame('Judul Flowchart', collect($seksi['flowchart']['group_fields'])->firstWhere('key', 'judul')['label']);

        // Keadaan sakelar datang dari SERVER, bukan ditebak peramban: bab yang
        // memang dimatikan tak boleh tampil hidup sekejap sebelum React jalan.
        $this->assertFalse($props['babAktif'], 'bab yang dimatikan harus sudah redup sejak dari server');

        // `inert`, BUKAN `disabled` — kolom disabled tak ikut terkirim dan
        // isiannya akan hilang saat disimpan. Dijaga di RepeatableGroup.tsx.
        $tsx = file_get_contents(resource_path('js/components/v2/dokumen/fields/RepeatableGroup.tsx'));
        $this->assertStringContainsString('inert={babMati || undefined}', $tsx,
            'bab yang dimatikan harus inert, bukan sekadar redup');
        $this->assertStringNotContainsString('disabled={babMati', $tsx,
            'JANGAN disabled — kolom disabled tak ikut terkirim dan isiannya akan hilang saat disimpan');

        // Nomor bab yang tercetak di layar = keadaan sekarang.
        $this->assertSame('V. AKTIVITAS DAN TANGGUNG JAWAB', $props['labelMati']['aktivitas']['label'] ?? null);
    }

    /** SP & IK tak punya bab opsional — tak boleh bergeser sedikit pun. */
    public function test_sp_dan_ik_tak_ikut_bergeser(): void
    {
        $gl = $this->aktorGl();

        foreach (['SP' => ['lampiran', 'VI. LAMPIRAN'], 'IK' => ['aktivitas', 'I. AKTIVITAS DAN TANGGUNG JAWAB']] as $kode => [$key, $label]) {
            $doc = app(DocumentService::class)->createDraft(
                $gl, DocumentType::where('code', $kode)->firstOrFail(), $gl->department, "Dokumen {$kode} Uji"
            );

            // Bahkan bila kunci sakelar ikut tersimpan (mis. terbawa salin
            // dokumen), jenis tanpa bab opsional tak terpengaruh sama sekali.
            $doc->contents()->create(['section_key' => 'pakai_flowchart', 'value_json' => '0']);

            $this->assertSame($label, $this->bab(SchemaService::untuk($doc->refresh()))[$key]['label'],
                "bab {$key} pada {$kode} tak boleh bergeser");
        }
    }
}
