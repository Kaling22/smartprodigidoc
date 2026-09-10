<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JsaDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_jsa_type_is_active_and_landscape(): void
    {
        $jsa = DocumentType::where('code', 'JSA')->firstOrFail();
        $this->assertTrue((bool) $jsa->is_active);
        $this->assertSame('landscape', $jsa->schema_json['orientation'] ?? null);
        $this->assertSame('documents.print.render-jsa', $jsa->schema_json['print_view'] ?? null);
    }

    public function test_nested_analisa_is_saved_and_cleaned(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Perbaikan Tower Radio');
        $doc->update(['current_step' => 2]);

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2,
            'action' => 'save',
            'sections' => [
                'analisa' => [
                    [
                        'langkah' => 'Persiapan alat',
                        'bahaya' => [
                            ['risiko' => 'Terjatuh', 'pengendalian' => ['Body harness', '', 'Lifeline']],
                            ['risiko' => '', 'pengendalian' => ['']], // kosong → dibuang
                        ],
                    ],
                    ['langkah' => '', 'bahaya' => [['risiko' => '', 'pengendalian' => ['']]]], // kosong → dibuang
                ],
            ],
        ])->assertRedirect();

        $analisa = $doc->refresh()->contentMap()['analisa'] ?? null;

        $this->assertIsArray($analisa);
        $this->assertCount(1, $analisa, 'langkah kosong dibuang');
        $this->assertSame('Persiapan alat', $analisa[0]['langkah']);
        $this->assertCount(1, $analisa[0]['bahaya'], 'bahaya kosong dibuang');
        $this->assertSame(['Body harness', 'Lifeline'], $analisa[0]['bahaya'][0]['pengendalian'], 'pengendalian kosong dibuang');
    }

    public function test_jsa_pdf_renders(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji JSA PDF');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah A', 'bahaya' => [['risiko' => 'Risiko A', 'pengendalian' => ['Kendali A']]]],
        ]]);

        $this->actingAs($gl)->get(route('documents.pdf', $doc))->assertOk();
    }

    /**
     * v3 rev — GL SHE/Plant benar-benar bisa MEMBUKA peninjauan JSA dari dept
     * lain, tapi tetap DITOLAK untuk jenis dokumen lain meski URL-nya ditempel
     * langsung (batas jenis ditegakkan ReviewController, bukan hanya gate rute).
     */
    public function test_she_group_leader_reviews_jsa_but_not_sop(): void
    {
        $gl = $this->aktorGl();          // ICTMD, pembuat
        $glShe = $this->aktorGl('SHE');    // SHE, peninjau JSA
        $pjo = $this->aktorPjo();
        $this->assertTrue($glShe->canReviewJsa(), 'prasyarat: GL SHE boleh meninjau JSA');

        $peserta = ['reviewer_id' => $glShe->id, 'approver_id' => $pjo->id, 'status' => 'waiting_for_review'];

        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Ditinjau GL SHE'
        );
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah', 'bahaya' => [['risiko' => 'Risiko', 'pengendalian' => ['Kendali']]]],
        ]]);
        $jsa->update($peserta);

        // JSA: boleh dibuka DAN diputuskan. Keputusannya kini DITURUNKAN dari
        // tanda per pengendalian, jadi tak ada `decision` yang dikirim.
        $this->actingAs($glShe)->get(route('review.show', $jsa))->assertOk();
        $this->actingAs($glShe)->post(route('review.store', $jsa), [
            'verdicts' => ['L0-B0-P0' => 'sesuai'],
        ])->assertRedirect();
        $this->assertSame('pending_approval', $jsa->refresh()->status, 'GL SHE meloloskan JSA');

        // SOP: ditolak walau namanya dipasang sbg peninjau (URL ditempel langsung).
        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Bukan Wewenang GL'
        );
        $sop->update($peserta);

        $this->actingAs($glShe)->get(route('review.show', $sop))->assertForbidden();
        $this->actingAs($glShe)->post(route('review.store', $sop), ['decision' => 'approve'])->assertForbidden();
    }

    /**
     * Tanda ✓/✗ per Tindakan Pengendalian — DIKEMBALIKAN (permintaan pemilik,
     * 4 Agustus 2026), menggantikan aturan sebelumnya yang menghapusnya.
     *
     * Yang dikembalikan bukan mekanisme lama. Bedanya penting:
     *   • Tandanya milik PENINJAUAN (`review_annotations.verdict`), bukan isi
     *     dokumen. Kontrak lama `checklist[…]` yang menulis `jsa_checklist` ke
     *     `document_contents` tetap TIDAK ADA — tanda peninjau bukan bagian dari
     *     naskah yang disahkan.
     *   • Tandanya TETAP TIDAK IKUT TERCETAK. Kolom "Beri tanda" pada PDF tetap
     *     kotak kosong untuk dicentang tangan; itu dijaga PrintLayoutTest.
     */
    public function test_jsa_review_menyimpan_tanda_di_anotasi_bukan_di_isi_dokumen(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'JSA Bertanda');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L A', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K1', 'K2']]]],
        ]]);
        $doc->update([
            'reviewer_id' => $sh->id,
            'approver_id' => $this->aktorPjo()->id,
            'status' => 'in_review',
        ]);

        /*
        | Halaman tinjau. Sejak Fase 10 radionya dirakit
        | `components/tinjau/SeksiTinjau.tsx` dari `contentMap`, jadi yang
        | diperiksa dua hal yang benar-benar bisa rusak:
        |
        |   1. jenis ini MEMANG memakai tanda (`pakaiVerdict`) dan analisanya
        |      sampai ke layar dengan dua pengendalian;
        |   2. tanda checklist dirender TEPAT SATU KALI di berkasnya — di cabang
        |      Tindakan Pengendalian. Kalau kelak seseorang menempelkannya juga
        |      di Langkah Kerja atau Bahaya, jumlahnya naik dan test ini merah.
        |      Itulah yang dulu dijaga `assertStringNotContainsString`.
        */
        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()
        );

        $this->assertTrue($props['pakaiVerdict'], 'JSA beranalisa memakai tanda per pengendalian');
        $this->assertSame(
            ['K1', 'K2'],
            $props['contentMap']['analisa'][0]['bahaya'][0]['pengendalian'],
            'tiap pengendalian sampai ke layar sebagai barisnya sendiri'
        );

        $tsx = (string) file_get_contents(resource_path('js/components/v2/tinjau/SeksiTinjau.tsx'));
        $this->assertSame(1, substr_count($tsx, '<TombolVerdict'),
            'tanda per item hanya di tingkat Tindakan Pengendalian');
        $this->assertStringNotContainsString('checklist[', $tsx, 'kontrak checklist lama tetap tiada');

        // Satu ✗ tanpa catatan → dokumen dikembalikan; tandanya tersimpan.
        $this->actingAs($sh)->post(route('review.store', $doc), [
            'verdicts' => ['L0-B0-P0' => 'sesuai', 'L0-B0-P1' => 'perlu_revisi'],
        ])->assertRedirect();

        $this->assertSame('rejected', $doc->refresh()->status, 'satu ✗ mengembalikan dokumen');
        $this->assertArrayNotHasKey('jsa_checklist', $doc->contentMap(), 'tanda tak pernah masuk isi dokumen');

        $tanda = $doc->reviews()->latest('id')->first()->annotations()
            ->pluck('verdict', 'item_ref')->all();
        $this->assertSame(['L0-B0-P0' => 'sesuai', 'L0-B0-P1' => 'perlu_revisi'], $tanda);
    }

    /**
     * Peninjau JSA berasal dari departemen LAIN, jadi ia harus tetap bisa
     * MELIHAT dokumen yang ia nilai — halaman detailnya maupun PDF-nya.
     *
     * Sebelumnya `authorizeView()` hanya mengizinkan pengawas lintas-dokumen,
     * pembuat, dan orang sedepartemen. GL SHE ditunjuk sebagai peninjau JSA
     * Produksi, menerima notifikasinya, bisa membuka formulir tinjauan — lalu
     * ditolak 403 begitu menekan "Lihat PDF": diminta menilai sesuatu yang tak
     * boleh ia lihat utuh.
     */
    public function test_peninjau_lintas_departemen_bisa_membuka_dokumen_dan_pdf(): void
    {
        $gl = $this->aktorGl();          // ICTMD, pembuat
        $glShe = $this->aktorGl('SHE');    // SHE, peninjau
        $this->assertNotSame($gl->department_id, $glShe->department_id, 'prasyarat: beda departemen');

        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Lintas Departemen'
        );
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);
        $jsa->update(['reviewer_id' => $glShe->id, 'status' => 'in_review']);

        $this->actingAs($glShe)->get(route('documents.show', $jsa))->assertOk();
        $this->actingAs($glShe)->get(route('documents.pdf', $jsa))->assertOk();

        // Batasnya tetap: GL departemen lain yang TIDAK ditunjuk tetap tertutup.
        // Sejak PLAN-AKSES-v7 Fase 3 GL Plant tertutup karena DUA sebab yang
        // menumpuk — tak ditunjuk, DAN tak lagi berwenang meninjau JSA sama
        // sekali. Yang diuji di sini sebab yang pertama; yang kedua punya
        // testnya sendiri di PeninjauJsaTest.
        $glPlant = User::create([
            'name' => 'GL Plant Bukan Peninjau', 'nrp' => 'GLPL-TEST', 'jabatan' => 'group_leader',
            'department_id' => \App\Models\Department::where('code', 'PLANT')->firstOrFail()->id,
            'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $glPlant->assignRole('group_leader');

        $this->actingAs($glPlant)->get(route('documents.show', $jsa))->assertForbidden();
        $this->actingAs($glPlant)->get(route('documents.pdf', $jsa))->assertForbidden();
    }

    /**
     * Angka yang dilihat peninjau harus angka DOKUMEN (edisi & no. revisi —
     * yang tercetak di kop), bukan `revision_round` yang menghitung berapa kali
     * dokumen dikembalikan. Dokumen Edisi 2 Revisi 3 dulu terbaca "Revisi ke-0".
     */
    public function test_layar_tinjau_menampilkan_edisi_dan_revisi_dokumen(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Bernomor Revisi'
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);
        $doc->update([
            'reviewer_id' => $sh->id, 'status' => 'in_review',
            'edisi' => '2', 'no_revisi' => 3, 'revision_round' => 0,
        ]);

        $this->actingAs($sh)->get(route('review.show', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('document.edisi', 2)
                ->where('document.noRevisi', 3)
                // Putaran tinjauan belum pernah terjadi → lencananya tak
                // dipajang (TSX: `putaran > 0`).
                ->where('document.putaran', 0)
            );

        // Sesudah sekali dikembalikan, putarannya muncul TERPISAH — tak lagi
        // menyamar sebagai nomor revisi dokumen.
        $doc->update(['revision_round' => 1]);
        $this->actingAs($sh)->get(route('review.show', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('document.edisi', 2)
                ->where('document.noRevisi', 3)
                ->where('document.putaran', 1)
            );
    }

    /**
     * Penanda borongan per Bahaya & Risiko: mengisi seluruh pengendalian di
     * bawahnya sekaligus.
     *
     * Muncul HANYA bila pengendaliannya lebih dari satu — pada bahaya
     * berpengendalian tunggal ia cuma menduplikasi tombol yang sudah ada tepat
     * di bawahnya. Tanda per baris tetap ada dan tetap wajib; tombol ini hanya
     * mengisi, bukan menggantikan.
     */
    public function test_penanda_borongan_hanya_untuk_bahaya_berpengendalian_banyak(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Borongan'
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L A', 'bahaya' => [
                ['risiko' => 'Bahaya berpengendalian banyak', 'pengendalian' => ['K1', 'K2', 'K3']],
                ['risiko' => 'Bahaya berpengendalian tunggal', 'pengendalian' => ['K1']],
            ]],
        ]]);
        $doc->update([
            'reviewer_id' => $sh->id,
            'approver_id' => $this->aktorPjo()->id,
            'status' => 'in_review',
        ]);

        /*
        | Sejak Fase 10 tombol borongan tak lagi mencari barisnya lewat atribut
        | `data-bahaya` di DOM: ia menerima daftar ref pengendaliannya langsung
        | (`Borongan` di `SeksiTinjau.tsx`). Yang tersisa untuk diuji karena itu
        | dua hal — bahwa kedua bahaya sampai ke layar dengan jumlah
        | pengendalian yang berbeda, dan bahwa SYARAT "lebih dari satu" itu
        | masih tertulis.
        */
        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()
        );

        $bahaya = $props['contentMap']['analisa'][0]['bahaya'];
        $this->assertCount(3, $bahaya[0]['pengendalian'], 'bahaya pertama berpengendalian banyak');
        $this->assertCount(1, $bahaya[1]['pengendalian'], 'bahaya kedua berpengendalian tunggal');

        $this->assertStringContainsString(
            'jumlah <= 1',
            (string) file_get_contents(resource_path('js/components/v2/tinjau/SeksiTinjau.tsx')),
            'penanda borongan tetap disembunyikan pada bahaya berpengendalian tunggal'
        );
    }

    /**
     * Batas fiturnya: SOP/IK/SP TIDAK ikut berubah.
     *
     * Tanda ✓/✗ adalah alat penilaian K3 pada tindakan pengendalian; jenis lain
     * tak punya padanannya. Peninjaunya tetap menekan salah satu dari dua tombol
     * seperti sebelumnya.
     */
    public function test_sop_tetap_memakai_dua_tombol_keputusan(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();

        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Tanpa Tanda'
        );
        $sop->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Memastikan pekerjaan aman']]);
        $sop->update([
            'reviewer_id' => $sh->id,
            'approver_id' => $this->aktorPjo()->id,
            'status' => 'in_review',
        ]);

        /*
        | `pakaiVerdict` adalah satu-satunya saklarnya: `false` berarti TSX
        | merender dua tombol keputusan dan tak satu pun radio tanda
        | (`TombolKeputusan` di `Review/Show.tsx`). Kondisinya dihitung
        | {@see ReviewDecision::pakaiVerdict()} — service yang sama dengan
        | penegakan di server, sehingga layar tak pernah menawarkan bentuk
        | tombol yang kelak ditolak.
        */
        $this->actingAs($sh)->get(route('review.show', $sop))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pakaiVerdict', false)
                // Layar SH/DH memang mengirim keputusan — bukan layar Masukan
                // Sejawat, yang justru menghilangkannya.
                ->missing('tanpaKeputusan')
            );
    }

    /** Penilaian yang belum lengkap ditolak SERVER, bukan hanya oleh layar. */
    public function test_jsa_review_menolak_penilaian_yang_belum_lengkap(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Belum Lengkap'
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L A', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K1', 'K2']]]],
        ]]);
        $doc->update([
            'reviewer_id' => $sh->id,
            'approver_id' => $this->aktorPjo()->id,
            'status' => 'in_review',
        ]);

        // Hanya satu dari dua pengendalian yang dinilai.
        $this->actingAs($sh)->post(route('review.store', $doc), [
            'verdicts' => ['L0-B0-P0' => 'sesuai'],
        ])->assertSessionHasErrors('verdicts');

        $this->assertSame('in_review', $doc->refresh()->status, 'status tak bergerak sebelum penilaian lengkap');
        $this->assertSame(0, $doc->reviews()->count(), 'tak ada tinjauan setengah jadi yang tersimpan');
    }
}
