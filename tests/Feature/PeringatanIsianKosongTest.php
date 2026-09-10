<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\SchemaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Peringatan untuk isian yang boleh kosong tapi sebaiknya diisi.
 *
 * Batas yang dijaga di sini: peringatan itu ada di LAYAR, bukan di server.
 * Begitu ia merambat jadi aturan validasi, keterangan lampiran berubah dari
 * "opsional" menjadi wajib tanpa seorang pun memutuskannya.
 */
class PeringatanIsianKosongTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * FLOWCHART & LAMPIRAN tidak menghalangi tombol Kirim.
     *
     * Inilah cacat yang pernah lolos ke layar: begitu keduanya diberi baris
     * STANDBY, `ppValidateRequired` — yang menganggap SETIAP kolom terlihat itu
     * wajib kecuali ber-`data-optional` — menolak pengiriman sampai bab yang
     * memang tak dipakai ikut diisi. Penandanya harus ada di ketiga ragam
     * kolom: textarea, teks biasa, dan combobox pemilih dokumen.
     */
    public function test_flowchart_dan_lampiran_tak_menghalangi_kirim(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji Peringatan');
        $doc->update(['current_step' => 2]);

        $seksi = $this->seksiWizard(
            $this->actingAs($gl)->get(route('documents.edit', $doc))->assertOk()
        );

        // Bab yang dilewatkan bulat-bulat tetap diingatkan
        // (`data-pp-warn-bab` di RepeatableGroup.tsx lahir dari kedua kunci ini).
        foreach (['flowchart' => 'V. FLOWCHART', 'lampiran' => 'VII. LAMPIRAN'] as $kunci => $label) {
            $this->assertSame($label, $seksi[$kunci]['label']);
            $this->assertFalse($seksi[$kunci]['required'], "bab {$label} tak wajib");
            $this->assertTrue($seksi[$kunci]['warn_if_empty'], "kekosongan bab {$label} harus diingatkan");
        }

        /*
        | Tiap kolom yang DIINGATKAN harus sekaligus DILEWATI validasi wajib.
        | Dipasangkan begini, bukan dihitung satu per satu: kalau salah satunya
        | lepas, kolomnya berubah jadi penghalang senyap — persis cacat yang
        | test ini ada untuk mencegahnya. (Menghitung `data-optional`
        | se-halaman tak bisa dipakai: "Pembuat Tambahan" juga opsional.)
        |
        | Empat label = tiga ragam kolom yang berbeda: teks biasa (Judul
        | Flowchart), textarea (dua Keterangan), dan combobox (Dokumen).
        */
        $diingatkan = [];
        foreach ($seksi as $sec) {
            foreach ($sec['group_fields'] ?? [] as $f) {
                if ($f['warn_if_empty'] ?? false) {
                    $diingatkan[] = $f['label'];
                    $this->assertFalse($sec['required'] ?? true,
                        "kolom \"{$f['label']}\" diingatkan, jadi babnya WAJIB tak-wajib — kalau tidak ia jadi penghalang senyap");
                }
            }
        }

        // Dua label lampiran berganti sejak butir 6 (nomor & judul dipisah);
        // yang dijaga di sini tetap sama: empat kolom itu MENGINGATKAN, tak
        // satu pun MENGHALANGI.
        $this->assertSame(['Judul Flowchart', 'Keterangan', 'Nomor Dokumen', 'Judul / Keterangan (opsional)'], $diingatkan);
    }

    /**
     * AKTIVITAS DAN TANGGUNG JAWAB tetap WAJIB — pelonggaran ini tak boleh
     * merembet. SOP tanpa aktivitas bukan SOP.
     */
    public function test_aktivitas_tetap_wajib(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());

        $this->assertNotFalse($schema->findSection('aktivitas')['required'] ?? true,
            'AKTIVITAS tidak boleh ikut dijadikan opsional');

        foreach ($schema->findSection('aktivitas')['group_fields'] as $f) {
            $this->assertArrayNotHasKey('warn_if_empty', $f,
                'kolom AKTIVITAS diwajibkan, bukan sekadar diingatkan');
        }
    }

    /** Schema-lah sumbernya, jadi penandanya ikut jenis mana pun yang memakainya. */
    public function test_penanda_lahir_dari_schema(): void
    {
        $schema = SchemaService::for(DocumentType::where('code', 'SOP')->firstOrFail());

        foreach (['lampiran', 'flowchart'] as $key) {
            $bab = $schema->findSection($key);

            $this->assertFalse($bab['required'], "bab {$key} harus opsional");
            $this->assertTrue($bab['warn_if_empty'], "bab {$key} yang kosong tetap harus diingatkan");
        }

        // Tetap TIDAK wajib di server — peringatan bukan validasi.
        $keterangan = collect($schema->findSection('lampiran')['group_fields'])
            ->keyBy('key')['keterangan'];
        $this->assertTrue($keterangan['warn_if_empty']);
        $this->assertArrayNotHasKey('required', $keterangan);
    }

    /** Server tetap menerima keterangan kosong, dan dokumennya tetap terkirim. */
    public function test_server_tetap_menerima_keterangan_kosong(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pjo = $this->aktorPjo();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Kirim Tanpa Keterangan');
        $doc->update(['current_step' => 2, 'reviewer_id' => $sh->id, 'approver_id' => $pjo->id]);

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'submit',
            'sections' => [
                'peninjau' => $sh->id,
                'penyetuju' => $pjo->id,
                'lampiran' => [['dokumen' => 'PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja', 'keterangan' => '']],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('waiting_for_review', $doc->refresh()->status);
        // `keterangan` tersimpan NULL, bukan '' — middleware bawaan Laravel
        // ConvertEmptyStringsToNull yang mengubahnya, dan itu memang perilaku
        // yang berlaku di seluruh formulir proyek ini.
        $this->assertSame(
            [['dokumen' => 'PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja', 'keterangan' => null]],
            $doc->contentMap()['lampiran'] ?? null,
            'baris berisi dokumen tanpa keterangan harus tetap tersimpan'
        );
    }
}
