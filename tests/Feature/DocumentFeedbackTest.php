<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Masukan lapangan Non-Staff (FITUR-BARU-v4 §3) dan pengadopsiannya menjadi
 * alasan revisi (§4 & §5).
 *
 * Yang dikunci di sini: batas siapa boleh memberi masukan (Non-Staff, dokumen
 * BERLAKU, departemen sendiri), kewajiban mengisi alasan saat memulai revisi,
 * dan ketertelusuran masukan → dokumen revisi.
 *
 * Sejak PLAN-REVISI-v6 Fase C yang MEMBALAS & MEREVISI adalah GL (penyusunnya)
 * dan MD, bukan lagi SH/DH & PJO — matriks lengkapnya dikunci
 * {@see IzinRevisiMasukanTest}.
 */
class DocumentFeedbackTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumenBerlaku(User $gl, string $judul = 'SOP Uji Masukan'): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    public function test_non_staff_mengirim_masukan_dan_sh_diberi_tahu(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $doc = $this->dokumenBerlaku($gl);

        $this->actingAs($nonStaff)
            ->post(route('documents.feedback.store', $doc), ['isi' => 'Langkah 5 tidak sesuai kondisi pit.'])
            ->assertRedirect();

        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();
        $this->assertSame('baru', $masukan->status);
        $this->assertSame($nonStaff->id, $masukan->user_id);
        $this->assertMatchesRegularExpression('/^MSK-\d{4}-\d{4}$/', $masukan->feedback_number);

        // SH departemen itulah yang berwenang menindak → dialah yang dinotifikasi.
        Notification::assertSentTo($sh, \App\Notifications\DocumentNotification::class);
    }

    public function test_masukan_ditolak_bila_bukan_non_staff_beda_dept_atau_dokumen_belum_berlaku(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl);

        // GL punya kanalnya sendiri (menyusun dokumen) — bukan kanal masukan.
        $this->actingAs($gl)
            ->post(route('documents.feedback.store', $doc), ['isi' => 'Coba dari GL.'])
            ->assertForbidden();

        // Departemen lain: masukan ditujukan ke SH/DH departemen dokumen itu.
        $she = \App\Models\Department::where('code', 'SHE')->firstOrFail();
        $docSHE = Document::create([
            'doc_number' => 'PPA-ADRO-SOP-SHE-99',
            'document_type_id' => $doc->document_type_id, 'department_id' => $she->id,
            'title' => 'SOP Dept Lain', 'status' => 'published', 'created_by' => $gl->id,
        ]);
        $this->actingAs($nonStaff)
            ->post(route('documents.feedback.store', $docSHE), ['isi' => 'Coba lintas dept.'])
            ->assertForbidden();

        // Draft: belum ada yang bisa menindaklanjuti masukannya.
        $draft = app(DocumentService::class)->createDraft($gl, $doc->type, $gl->department, 'SOP Draft');
        $this->actingAs($nonStaff)
            ->post(route('documents.feedback.store', $draft), ['isi' => 'Coba di draft.'])
            ->assertForbidden();

        // Dihitung per dokumen, bukan `DocumentFeedback::count()`: DB pengembangan
        // bisa sudah berisi masukan sungguhan dari penelusuran manual.
        $this->assertSame(0, DocumentFeedback::whereIn('document_id', [$doc->id, $docSHE->id, $draft->id])->count());
    }

    /** §4 + Fase C D3 — memulai revisi TANPA alasan tertulis selalu ditolak. */
    public function test_ajukan_revisi_wajib_beralasan(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumenBerlaku($gl);

        $this->actingAs($gl)
            ->post(route('documents.requestRevision', $doc))
            ->assertSessionHasErrors('alasan');

        $this->assertSame('published', $doc->refresh()->status, 'dokumen tak jadi direvisi');
        $this->assertSame(1, Document::where('doc_number', $doc->doc_number)->count(), 'tak ada draft revisi yang lahir');
    }

    /** §5 — masukan yang dicentang jadi alasan revisi, berstatus diadopsi & tertaut. */
    public function test_masukan_yang_dicentang_menjadi_alasan_revisi(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl);

        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Alat pelindung kurang disebut.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Menyesuaikan APD.',
            'masukan' => [$masukan->id],
        ])->assertRedirect();

        $revisi = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        $masukan->refresh();
        $this->assertSame('diadopsi', $masukan->status);
        $this->assertSame($revisi->id, $masukan->revision_document_id, 'masukan tertaut ke dokumen revisinya');
        $this->assertSame($gl->id, $masukan->replied_by);

        // Alasan terbaca pembuat di form revisi (dipakai sbg "Rangkuman Peninjau").
        $rangkuman = $revisi->reviews()->where('decision', 'needs_revision')->value('summary');
        $this->assertStringContainsString('Menyesuaikan APD.', $rangkuman);
        $this->assertStringContainsString($masukan->feedback_number, $rangkuman);
        $this->assertStringContainsString('Alat pelindung kurang disebut.', $rangkuman);

        // Pengirimnya diberi tahu masukannya berujung ke mana.
        Notification::assertSentTo($nonStaff, \App\Notifications\DocumentNotification::class);
    }

    /**
     * Fase C (D3) MEMBALIK aturan lama "masukan saja sudah cukup": alasan kini
     * WAJIB ditulis, sebab ia dikirim ke SH/DH & MD — dan "[MSK-…] pagar rusak"
     * tanpa kalimat pengaju tak menerangkan apa pun kepada mereka. Mencentang
     * masukan tetap opsional (D2).
     */
    public function test_masukan_tanpa_alasan_tertulis_ditolak(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl);

        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Nomor telepon darurat sudah berubah.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['masukan' => [$masukan->id]])
            ->assertSessionHasErrors('alasan');
        $this->assertSame('baru', $masukan->refresh()->status, 'masukan tak jadi diadopsi');

        // Dengan alasan tertulis: berjalan, dan masukannya ikut teradopsi.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Nomor darurat diperbarui.', 'masukan' => [$masukan->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame('diadopsi', $masukan->refresh()->status);
    }

    public function test_gl_membalas_dan_menutup_masukan_tanpa_revisi(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $doc = $this->dokumenBerlaku($gl);

        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Usul ganti istilah.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        $this->actingAs($gl)->post(route('documents.feedback.respond', $masukan), ['balasan' => 'Istilah sudah sesuai standar terbaru.'])
            ->assertRedirect();

        $masukan->refresh();
        $this->assertSame('ditolak', $masukan->status);
        $this->assertSame('Istilah sudah sesuai standar terbaru.', $masukan->balasan);
        $this->assertSame($gl->id, $masukan->replied_by);
        $this->assertNotNull($masukan->replied_at);

        // Non-Staff tak boleh membalas masukan siapa pun; SH/DH sejak Fase C
        // hanya MELIHAT (D4).
        foreach ([$nonStaff, $sh] as $tak) {
            $this->actingAs($tak)->post(route('documents.feedback.respond', $masukan), ['balasan' => 'Coba balas.'])
                ->assertForbidden();
        }
    }

    /**
     * A1 — masukan departemen lain BUKAN urusan GL mana pun.
     *
     * `document.feedback_respond` dipegang SEMUA GL, jadi tanpa batas departemen
     * seorang GL bisa membalas — dan MENUTUP — masukan atas dokumen departemen
     * orang lain.
     *
     * PJO dulu lolos di sini lewat `document.view_all`; sejak Fase C (D4) ia
     * hanya MELIHAT, dan yang menembus tujuh departemen adalah MD.
     */
    public function test_departemen_lain_tak_boleh_membalas_masukan(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        // GL SHE — sengaja BUKAN departemen `$gl` (ICTMD): yang diuji di sini
        // justru batas departemennya.
        $glLain = $this->aktorGl('SHE');
        $pjo = $this->aktorPjo();
        $md = User::peninjauMd()->firstOrFail();

        $doc = $this->dokumenBerlaku($gl, 'SOP Batas Departemen');
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Perlu diperjelas.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        foreach ([$glLain, $pjo] as $tak) {
            $this->actingAs($tak)
                ->post(route('documents.feedback.respond', $masukan), ['balasan' => 'Coba dari luar.'])
                ->assertForbidden();
        }
        $this->assertNull($masukan->refresh()->balasan, 'balasan tak boleh tertulis');

        // MD memang lintas 7 departemen — ia tetap boleh.
        $this->actingAs($md)
            ->post(route('documents.feedback.respond', $masukan), ['balasan' => 'Sudah sesuai.'])
            ->assertRedirect();
        $this->assertSame('ditolak', $masukan->refresh()->status);
    }

    /** A3 — masukan yang sudah ditindak tak boleh ditimpa lewat POST langsung. */
    public function test_masukan_yang_sudah_ditindak_tak_bisa_dibalas_ulang(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();

        $doc = $this->dokumenBerlaku($gl, 'SOP Sudah Ditindak');
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Langkah 3 keliru.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        // Diadopsi jadi bahan revisi → sudah selesai.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Langkah 3 diperbaiki.', 'masukan' => [$masukan->id],
        ]);
        $this->assertSame('diadopsi', $masukan->refresh()->status);

        $this->actingAs($gl)
            ->post(route('documents.feedback.respond', $masukan), ['balasan' => 'Menimpa jejak adopsi.'])
            ->assertForbidden();

        $masukan->refresh();
        $this->assertSame('diadopsi', $masukan->status, 'status adopsi tetap utuh');
        $this->assertNotNull($masukan->revision_document_id, 'tautan ke dokumen revisi tak hilang');
    }

    /**
     * A4 — masukan yang belum dijawab TIDAK boleh lenyap saat revisi disahkan.
     *
     * Ia menempel ke versi lama; begitu versi itu jadi `obsolete` ia hilang dari
     * halaman Dokumen Berlaku dan tak muncul di versi baru — pengirimnya tak
     * pernah dijawab.
     */
    public function test_masukan_belum_ditindak_ikut_pindah_ke_versi_baru(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();

        $doc = $this->dokumenBerlaku($gl, 'SOP Masukan Menyeberang');
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Belum dijawab siapa pun.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        // Revisi dimulai TANPA mengadopsi masukan itu.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Alasan lain.']);
        $revisi = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        // Revisi disahkan → versi lama jadi obsolete.
        $revisi->update(['reviewer_id' => $sh->id, 'approver_id' => $pjo->id, 'status' => 'pending_approval']);
        $this->actingAs($pjo)->post(route('approvals.store', $revisi), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame('obsolete', $doc->refresh()->status);
        $this->assertSame($revisi->id, $masukan->refresh()->document_id,
            'masukan yang belum dijawab ikut ke versi yang kini Berlaku');
        $this->assertSame('baru', $masukan->status, 'statusnya tak berubah — hanya pindah versi');
    }

    /**
     * A6 — hanya orang sedepartemen YANG MENINDAK yang menghabiskan penanda
     * "baru". Sejak Fase C itu berarti GL/MD, bukan SH/DH lagi: SH boleh
     * membuka dokumennya sepuasnya tanpa memadamkan sorotan milik GL.
     */
    public function test_yang_tak_menindak_tak_menghabiskan_penanda_baru(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $pjo = $this->aktorPjo();   // tanpa departemen
        $sh = $this->aktorSh();     // sedepartemen

        $doc = $this->dokumenBerlaku($gl, 'SOP Penanda Baru');
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Masih baru.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        foreach ([$pjo, $sh] as $penonton) {
            $this->actingAs($penonton)->get(route('documents.show', $doc))->assertOk();
            $this->assertSame('baru', $masukan->refresh()->status, 'sorotan milik GL tak boleh padam');
        }

        $this->actingAs($gl)->get(route('documents.show', $doc))->assertOk();
        $this->assertSame('dibaca', $masukan->refresh()->status);
    }

    /** A7 — pengiriman masukan direm supaya lonceng SH/DH tak bisa dibanjiri. */
    public function test_pengiriman_masukan_direm(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Rem Masukan');

        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($nonStaff)
                ->post(route('documents.feedback.store', $doc), ['isi' => "Masukan ke-{$i}."])
                ->assertRedirect();
        }

        $this->actingAs($nonStaff)
            ->post(route('documents.feedback.store', $doc), ['isi' => 'Yang ke-11.'])
            ->assertStatus(429);

        $this->assertSame(10, DocumentFeedback::where('document_id', $doc->id)->count());
    }

    /**
     * A8 — departemen tanpa SH/DH aktif: masukan tak boleh masuk ke ruang hampa.
     */
    public function test_dept_tanpa_kepala_jatuh_ke_pjo(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $pjo = $this->aktorPjo();

        // Seluruh SH/DH departemen itu berhenti aktif ('pending' — enum kolom
        // status hanya mengenal active/pending/rejected).
        User::where('department_id', $gl->department_id)
            ->whereIn('jabatan', [User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD])
            ->update(['status' => 'pending']);

        $doc = $this->dokumenBerlaku($gl, 'SOP Tanpa Kepala');
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Ada yang harus tahu.'])
            ->assertRedirect();

        Notification::assertSentTo($pjo, \App\Notifications\DocumentNotification::class);
    }

    /**
     * Kanal masukan HARUS terlihat Non-Staff di web (permintaan 1 Agustus 2026:
     * orang lapangan tak selalu memegang HP saat di kantor) — dan tetap
     * tertutup bagi peran lain.
     */
    public function test_kanal_masukan_tampil_bagi_non_staff_di_web(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Kanal Web');

        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Rambu simpang kurang jelas.']);
        $masukan = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();

        /*
        | Dulu diperiksa lewat id modal Bootstrap (`modalMasukan{id}`). Sejak
        | Fase 8 jendelanya komponen React yang dirender bila — dan hanya bila —
        | barisnya membawa izinnya, jadi yang diperiksa izin itu sendiri beserta
        | isi jendelanya (riwayat masukan si pengirim).
        */
        $baris = collect($this->propsInertia(
            $this->actingAs($nonStaff)->get(route('documents.published'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        $this->assertTrue($baris['boleh_beri_masukan'], 'Non-Staff mendapat kanal masukan');
        $this->assertFalse($baris['boleh_revisi'], 'Non-Staff tak pernah mengajukan revisi');
        $this->assertSame(
            [$masukan->feedback_number],
            array_column($baris['masukan'], 'nomor'),
            'jendela memuat riwayat masukannya sendiri'
        );

        // Halaman detail: panel Masukan Lapangan, tanpa form balasan (itu GL/MD).
        $detail = $this->propsInertia(
            $this->actingAs($nonStaff)->get(route('documents.show', $doc))->assertOk()
        );
        $this->assertSame(['Rambu simpang kurang jelas.'], array_column($detail['masukan'], 'isi'));
        $this->assertFalse($detail['bolehMembalasMasukan'], 'form Balas & Tutup milik GL/MD');

        // Masukan rekan sedepartemen bukan urusannya — hanya miliknya yang tampil.
        $lain = User::create([
            'name' => 'Non-Staff Lain', 'nrp' => 'STF-9999', 'jabatan' => User::JABATAN_STAFF,
            'status' => 'active', 'password' => bcrypt('rahasia123'), 'department_id' => $gl->department_id,
        ]);
        $this->actingAs($lain)->post(route('documents.feedback.store', $doc), ['isi' => 'Masukan rekan.']);
        $this->actingAs($nonStaff)->get(route('documents.show', $doc))
            ->assertOk()
            ->assertDontSee('Masukan rekan.');

        // GL melihat halaman yang sama TANPA kanal masukan.
        $barisGl = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('documents.published'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        $this->assertFalse($barisGl['boleh_beri_masukan']);
    }

    /**
     * Lencana di "Dokumen Berlaku" menghitung masukan yang BELUM ditindak —
     * sudah dibaca tetap terhitung, yang sudah ditutup tidak.
     */
    public function test_lencana_hanya_menghitung_masukan_yang_belum_ditindak(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Lencana Masukan');

        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Masukan pertama.']);
        $this->actingAs($nonStaff)->post(route('documents.feedback.store', $doc), ['isi' => 'Masukan kedua.']);

        // Dulu `assertSee('2 masukan')`: kalimat lencananya kini dirangkai TSX
        // dari angka ini, jadi angkanya sendiri yang diperiksa.
        $this->assertSame(2, $this->lencanaMasukan($gl, $doc));

        // Membuka detail hanya menandai "dibaca" — lencana tetap hidup.
        $this->actingAs($gl)->get(route('documents.show', $doc))->assertOk();
        $this->assertSame(2, $this->lencanaMasukan($gl, $doc));

        $ditutup = DocumentFeedback::where('document_id', $doc->id)->firstOrFail();
        $this->actingAs($gl)->post(route('documents.feedback.respond', $ditutup), ['balasan' => 'Sudah sesuai.']);
        $this->assertSame(1, $this->lencanaMasukan($gl, $doc));
    }

    /** Angka lencana masukan pada baris $doc di halaman Dokumen Berlaku. */
    private function lencanaMasukan(User $sebagai, Document $doc): ?int
    {
        $baris = collect($this->propsInertia(
            $this->actingAs($sebagai)->get(route('documents.published'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        return $baris['masukan_count'];
    }
}
