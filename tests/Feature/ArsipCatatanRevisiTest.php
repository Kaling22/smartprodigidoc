<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\Print\ArsipPenggabung;
use App\Services\SchemaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Lembar CATATAN REVISI dokumen LAMA yang diunggah (PLAN-REVISI-v6 Fase H),
 * sesudah pilihan "gabung" dicabut (rencana pra-produksi Fase 2, butir 7).
 *
 * Yang dijaga di sini bergeser, dan pergeserannya disengaja. Dulu berkas
 * unggahan MEMANG ditimpa hasil gabung, jadi tiap test menghitung halaman PDF
 * nyata supaya salah potong tak lolos. Sejak jalur itu dicabut, kerusakan yang
 * mungkin justru kebalikannya:
 *
 *  • berkas unggahan tetap tersentuh padahal tak ada lagi yang boleh menyentuhnya;
 *  • auto-approve salinan BOCOR ke revisi Tipe B biasa — dokumen terbit tanpa
 *    pernah ditinjau siapa pun, dan tak satu pun layar menunjukkannya;
 *  • dokumen unggahan asli tertinggal `sedang_direvisi` selamanya.
 *
 * Ketiganya gagal diam-diam, jadi ketiganya punya pengunci di bawah.
 */
class ArsipCatatanRevisiTest extends TestCase
{
    use DatabaseTransactions;

    /** PDF sungguhan berisi $halaman halaman — FPDI menolak PDF tiruan. */
    private function pdfNyata(int $halaman): string
    {
        $pdf = new \FPDF;
        for ($i = 1; $i <= $halaman; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 24);
            $pdf->Cell(0, 20, "Halaman {$i}");
        }

        return $pdf->Output('S');
    }

    private function gl(): User
    {
        // Profil akses penuh: berkas ini menguji arsip/catatan/unggahan,
        // bukan wewenang menyusun (PLAN-AKSES-v7 Fase 4).
        return $this->berprofilPenuh($this->aktorGl());
    }

    /** Daftarkan dokumen lama; kembalikan barisnya. */
    private function daftarkan(string $isiPdf, string $jenis = 'SOP', ?string $tanggalEfektif = null): Document
    {
        $gl = $this->gl();

        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => DocumentType::where('code', $jenis)->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Prosedur Lama Berlembar Revisi',
            'doc_number' => 'ARSIP-CAT-'.Str::upper(Str::random(8)),
            'edisi' => 2,
            'no_revisi' => 3,
            'berkas' => UploadedFile::fake()->createWithContent('lama.pdf', $isiPdf),
            'tanggal_efektif' => $tanggalEfektif,
        ]);

        return Document::whereNotNull('arsip_path')->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function kirimCatatan(Document $doc, array $ubah = []): TestResponse
    {
        return $this->actingAs($this->gl())->post(route('documents.arsip.catatan.store', $doc), array_merge([
            'edisi' => 2,
            'no_revisi' => 3,
            'sections' => ['catatan_revisi' => [
                ['no_rev' => 3, 'tanggal' => '2020-01-31', 'halaman' => '1-2', 'catatan' => 'Penyesuaian alur kerja.'],
            ]],
        ], $ubah));
    }

    private function halaman(string $path): int
    {
        return app(ArsipPenggabung::class)->jumlahHalaman(Storage::disk('local')->path($path));
    }

    /** Draft salinan siap kirim: peninjau & penyetuju tetap wajib dipilih. */
    private function siapKirim(Document $draft): Document
    {
        $draft->update(['reviewer_id' => $this->aktorSh()->id, 'approver_id' => $this->aktorPjo()->id]);

        return $draft;
    }

    /**
     * Berkas unggahan TAK TERSENTUH saat lembar disimpan.
     *
     * Dulu ia memang ditimpa hasil gabung; sesudah pilihan itu dicabut, satu
     * pemanggil `ArsipPenggabung` yang tertinggal akan memotong halaman tanpa
     * ada yang memintanya — dan berkas aslinya tak bisa dikembalikan.
     */
    public function test_lembar_tersimpan_tanpa_menyentuh_berkas_unggahan(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $unggahan = $doc->arsip_path;

        $this->kirimCatatan($doc)->assertRedirect();

        $doc->refresh();
        $this->assertSame($unggahan, $doc->arsip_path, 'berkas yang disajikan tetap berkas unggahan');
        $this->assertNull($doc->arsip_path_asli, 'tak ada lagi salinan hasil gabung');
        $this->assertSame(3, $this->halaman($doc->arsip_path), 'jumlah halaman tak berubah');
        $this->assertSame('Penyesuaian alur kerja.', $doc->contentMap()['catatan_revisi'][0]['catatan']);
    }

    /** Halaman catatan tak lagi mengurai PDF — prop `jumlahHalaman` ikut dicabut. */
    public function test_halaman_catatan_tak_lagi_mengirim_jumlah_halaman(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));

        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.arsip.catatan', $doc))->assertOk()
        );

        $this->assertArrayNotHasKey('jumlahHalaman', $props,
            'pengurai PDF tak boleh dipanggil lagi saat halaman ini digambar');
        $this->assertSame(['edisi' => 2, 'revisi' => 3], $props['revisiKirim'],
            'dokumen arsip belum jadi draft revisi — nomornya belum naik');
    }

    /** Batas revisi tetap ditegakkan lewat SATU konstanta (DocumentService::MAKS_REVISI). */
    public function test_no_revisi_di_atas_batas_ditolak(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));

        $this->kirimCatatan($doc, ['no_revisi' => DocumentService::MAKS_REVISI + 1])
            ->assertSessionHasErrors('no_revisi');

        $this->assertSame(0, Document::where('revises_document_id', $doc->id)->count(),
            'lembar yang ditolak tak boleh meninggalkan draft salinan');
    }

    /**
     * PDF tak terbaca (terkunci/rusak) TIDAK lagi menghalangi apa pun.
     *
     * Dulu ia galat, karena berkasnya harus diurai untuk digabung. Sekarang
     * berkasnya cuma jadi rujukan di panel kanan — dan justru dokumen yang
     * berkasnya bermasalah itulah yang paling perlu diketik ulang.
     */
    public function test_pdf_tak_terbaca_tetap_bisa_disalin(): void
    {
        Storage::fake('local');
        // Lolos pemeriksaan magic bytes saat diunggah, tapi tak bisa diurai FPDI —
        // persis bentuk kegagalan PDF terenkripsi di lapangan.
        $doc = $this->daftarkan("%PDF-1.4\nisi berkas ini bukan struktur PDF yang sah\n%%EOF");

        $this->kirimCatatan($doc)->assertSessionMissing('error');

        $this->assertSame(1, Document::where('revises_document_id', $doc->id)->count());
    }

    /** "Salin seluruh" → draft wizard lahir dengan catatan_revisi terisi + PDF rujukan. */
    public function test_salin_seluruh_membuat_draft_wizard(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));

        $this->kirimCatatan($doc)->assertRedirect();

        $draft = Document::where('revises_document_id', $doc->id)->firstOrFail();
        $this->assertSame('draft', $draft->status);
        $this->assertNotNull($draft->salin_arsip_at, 'asal-usul draft dicatat, bukan ditebak belakangan');
        $this->assertSame('Penyesuaian alur kerja.', $draft->contentMap()['catatan_revisi'][0]['catatan']);
        $this->assertSame('sedang_direvisi', $doc->refresh()->status);

        // Panel kanan wizard memuat berkas unggahan sebagai tab Referensi.
        // Tabnya lahir dari props ini: `rujukanPdfUrl` null = tab tak dirender.
        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $draft))->assertOk()
        );

        $this->assertSame(route('documents.pdf', $doc), $props['rujukanPdfUrl'],
            'berkas unggahan yang sedang disalin harus jadi tab Referensi di panel kanan');
    }

    /** Salinan dikirim → LANGSUNG Berlaku, memikul nomor manual dokumen lamanya. */
    public function test_salinan_dikirim_langsung_berlaku(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $this->kirimCatatan($doc);

        $draft = $this->siapKirim(Document::where('revises_document_id', $doc->id)->firstOrFail());
        $this->actingAs($this->gl())->post(route('documents.submit', $draft))->assertRedirect();

        $draft->refresh();
        $this->assertSame('published', $draft->status, 'salinan tak melewati antrean tinjauan');
        $this->assertNotNull($draft->published_at);
        $this->assertSame($doc->doc_number, $draft->doc_number_final,
            'nomor manual dokumen lama bertahan sampai Berlaku');
    }

    /**
     * Tgl. Efektif kop = tanggal yang dimasukkan saat DOKUMEN LAMA diunggah,
     * bukan hari pengetikan ulang maupun kiriman wizard.
     *
     * Dulu `sahkanSalinanArsip()` menulis `published_at = now()` tanpa syarat,
     * jadi seluruh arsip yang disalin tampak efektif hari ini, alih-alih pada
     * tanggal yang dicatat ketika berkas lamanya didaftarkan.
     *
     * Diuji sampai SESUDAH pengesahan, bukan cuma sesudah langkah wizard:
     * yang dulu rusak justru pengesahannya, dan draft yang tanggalnya benar
     * tetap salah begitu dikirim.
     */
    public function test_tanggal_efektif_salinan_arsip_mewarisi_input_unggahan(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3), 'SOP', '2018-02-03');
        $this->kirimCatatan($doc);

        $draft = $this->siapKirim(Document::where('revises_document_id', $doc->id)->firstOrFail());

        // Props langkah Log Revisi membawa bawaannya: tanggal revisi = hari ini.
        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $draft))->assertOk()
        );
        $this->assertSame(now()->toDateString(), $props['tanggalCetak']['revisi']);

        // Langkah Log Revisi = langkah VIRTUAL sesudah langkah terakhir schema.
        $this->actingAs($this->gl())->post(route('documents.saveStep', $draft), [
            'step' => SchemaService::for($draft->type)->stepCount() + 1,
            'action' => 'save',
            'edisi' => 2,
            'no_revisi' => 3,
            // Kiriman yang dirakit sendiri pun tidak boleh mengganti tanggal
            // efektif yang tercatat saat arsip awal didaftarkan.
            'tanggal_terbit' => '2019-03-04',
            'tanggal_revisi' => '2021-07-15',
            'sections' => ['catatan_revisi' => [
                ['no_rev' => 3, 'tanggal' => '2021-07-15', 'halaman' => '2', 'catatan' => 'Penyesuaian.'],
            ]],
        ])->assertRedirect();

        $this->actingAs($this->gl())->post(route('documents.submit', $draft))->assertRedirect();

        $draft->refresh();
        $this->assertSame('2018-02-03', $draft->published_at?->toDateString(),
            'tanggal efektif salinan harus sama dengan input saat arsip didaftarkan');
        $this->assertSame('2021-07-15', $draft->tanggal_revisi?->toDateString());
    }

    /**
     * Revisi Tipe B BIASA tak boleh bisa menulis tanggal terbit lewat kiriman
     * wizard: tanggalnya milik pengesahan, dan batas itu dijaga di server —
     * bukan dengan menyembunyikan isiannya di layar.
     */
    public function test_tanggal_kop_ditolak_pada_draft_yang_bukan_salinan_arsip(): void
    {
        $gl = $this->gl();
        $doc = app(DocumentService::class)->createDraft(
            $gl,
            DocumentType::where('code', 'SOP')->firstOrFail(),
            $gl->department,
            'SOP Bukan Salinan',
        );

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 1,
            'action' => 'save',
            'tanggal_terbit' => '2001-01-01',
            'tanggal_revisi' => '2001-01-01',
            'sections' => [],
        ])->assertRedirect();

        $doc->refresh();
        $this->assertNull($doc->published_at, 'draft biasa tak boleh mendapat tanggal terbit dari kiriman wizard');
        $this->assertNull($doc->tanggal_revisi);
    }

    /** Dokumen unggahan asli jadi Tidak Berlaku begitu salinannya terbit. */
    public function test_dokumen_arsip_asli_jadi_obsolete_saat_salinan_terbit(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $this->kirimCatatan($doc);

        $draft = $this->siapKirim(Document::where('revises_document_id', $doc->id)->firstOrFail());
        $this->actingAs($this->gl())->post(route('documents.submit', $draft))->assertRedirect();

        $doc->refresh();
        $this->assertSame('obsolete', $doc->status,
            'tanpa ini dokumen lama terkunci "sedang_direvisi" selamanya');
        $this->assertSame(Document::OBSOLETE_REVISI, $doc->obsolete_reason);
    }

    /**
     * Pengunci TERPENTING: auto-approve tak bocor ke revisi Tipe B biasa.
     *
     * Revisi biasa atas dokumen unggahan memenuhi setiap syarat yang bisa
     * DIHITUNG (induknya ber-`arsip_path`, nomornya diwarisi, versi lamanya
     * `sedang_direvisi`) — hanya `salin_arsip_at` yang membedakannya.
     */
    public function test_revisi_tipe_b_biasa_atas_dokumen_arsip_tetap_ditinjau(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));

        $this->actingAs($this->gl())
            ->post(route('documents.requestRevision', $doc), ['alasan' => 'Prosedur berubah di lapangan.'])
            ->assertRedirect();

        $draft = $this->siapKirim(Document::where('revises_document_id', $doc->id)->firstOrFail());
        $this->assertNull($draft->salin_arsip_at);

        $this->actingAs($this->gl())->post(route('documents.submit', $draft))->assertRedirect();

        $this->assertSame('waiting_for_review', $draft->refresh()->status);
        $this->assertSame('sedang_direvisi', $doc->refresh()->status, 'versi lama belum boleh obsolete');
    }

    /**
     * Kunci asing dari halaman V1 yang dibekukan DIABAIKAN, bukan ditolak 422.
     *
     * Pohon V1 tak ikut disunting (K-A), jadi ia masih mengirim `pilihan`,
     * `potong_halaman`, dan `halaman_awal`. Mematikan sakelar V2 tak boleh
     * membuat lembar ini mustahil disimpan.
     */
    public function test_kunci_asing_halaman_v1_diabaikan(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));

        $this->kirimCatatan($doc, ['pilihan' => 'gabung', 'potong_halaman' => 99, 'halaman_awal' => 7])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Document::where('revises_document_id', $doc->id)->count(),
            'kiriman V1 tetap menempuh jalur salin');
        $this->assertSame(3, $this->halaman($doc->refresh()->arsip_path),
            'potong_halaman dari V1 tak boleh menyentuh berkas');
    }

    /** FK/PX (kelas unggahan) tak melewati halaman ini — langsung Berlaku. */
    public function test_jenis_unggahan_tidak_melewati_halaman_catatan(): void
    {
        Storage::fake('local');
        $jenis = DocumentType::where('class', 'unggahan')->firstOrFail();

        $gl = $this->gl();
        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => $jenis->id,
            'department_id' => $gl->department_id,
            'title' => 'Formulir Lama',
            'doc_number' => 'ARSIP-FK-'.Str::upper(Str::random(8)),
            'edisi' => 1,
            'no_revisi' => 0,
            'berkas' => UploadedFile::fake()->createWithContent('fk.pdf', $this->pdfNyata(1)),
        ])->assertRedirect(route('documents.published'));

        $doc = Document::whereNotNull('arsip_path')->latest('id')->firstOrFail();
        $this->assertSame('published', $doc->status);
        $this->actingAs($gl)->get(route('documents.arsip.catatan', $doc))->assertNotFound();
    }
}
