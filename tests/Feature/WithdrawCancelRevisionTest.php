<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Peninjaunya memakai {@see TestCase::peninjauBersih()}, bukan akun contoh
 * `SH-0001`: suite ini berjalan di atas basis data pengembangan, dan `SH-0001`
 * membawa dokumen sungguhan. Begitu bebannya melewati batas,
 * `tolakBilaPeninjauPenuh()` menolak penugasan baru dan test "Kirim" di sini
 * gagal dengan pesan tentang STATUS dokumen — petunjuk yang menyesatkan, karena
 * sebabnya beban peninjau.
 */
class WithdrawCancelRevisionTest extends TestCase
{
    use DatabaseTransactions;

    private function makeSubmitted(): array
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pimpinan = $this->aktorPjo();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji');
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);
        $this->actingAs($gl)->post(route('documents.submit', $doc));

        return [$doc->refresh(), $gl, $sh];
    }

    public function test_withdraw_returns_waiting_document_to_draft(): void
    {
        [$doc, $gl] = $this->makeSubmitted();
        $this->assertSame('waiting_for_review', $doc->status);

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertRedirect();
        $this->assertSame('draft', $doc->refresh()->status);
    }

    /**
     * Kirim dari WIZARD (saveStep action=submit) juga harus waiting_for_review —
     * dulu langsung in_review sehingga GL tak pernah bisa Menarik dokumen.
     */
    public function test_wizard_submit_is_withdrawable_until_reviewed(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pimpinan = $this->aktorPjo();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Wizard Kirim');
        $doc->update(['current_step' => 2]);

        // Form wizard langkah 2 selalu mengirim user_picker peninjau/penyetuju.
        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'submit',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pimpinan->id],
        ])->assertRedirect();
        $this->assertSame('waiting_for_review', $doc->refresh()->status, 'wizard Kirim -> masih bisa Ditarik');

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertRedirect();
        $this->assertSame('draft', $doc->refresh()->status);
    }

    public function test_withdraw_blocked_once_in_review(): void
    {
        [$doc, , $sh] = $this->makeSubmitted();
        $this->actingAs($sh)->get(route('review.show', $doc)); // -> in_review
        $gl = $this->aktorGl();

        $this->actingAs($gl)->post(route('documents.withdraw', $doc))->assertForbidden();
        $this->assertSame('in_review', $doc->refresh()->status);
    }

    public function test_reviewer_can_cancel_revision(): void
    {
        [$doc, , $sh] = $this->makeSubmitted();
        $this->actingAs($sh)->get(route('review.show', $doc));
        $this->actingAs($sh)->post(route('review.store', $doc), ['decision' => 'reject', 'annotations' => ['tujuan' => [0 => 'perbaiki']]]);
        $this->assertSame('rejected', $doc->refresh()->status);

        // Batalkan Revisi -> kembali in_review.
        $this->actingAs($sh)->post(route('review.cancelRevision', $doc))->assertRedirect();
        $this->assertSame('in_review', $doc->refresh()->status);
    }

    /** Dokumen Tidak Berlaku bernomor final tertentu. */
    private function obsolete(string $judul, ?string $nomorFinal): \App\Models\Document
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['status' => 'obsolete', 'doc_number_final' => $nomorFinal]);

        return $doc->refresh();
    }

    public function test_admin_mengaktifkan_kembali_dokumen_tidak_berlaku(): void
    {
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $doc = $this->obsolete('SOP Uji Aktifkan', 'PPA-ADRO-SOP-ICTMD-97');

        $this->actingAs($admin)->post(route('documents.restoreObsolete', $doc))->assertRedirect();

        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertSame('PPA-ADRO-SOP-ICTMD-97', $doc->doc_number_final, 'tanpa bentrok, nomornya tak boleh berubah');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.restore_obsolete', 'document_id' => $doc->id]);
    }

    /**
     * Nomor final yang masih dipegang dokumen Berlaku — keadaan lazim, sebab
     * dokumen jadi obsolete justru karena penerusnya disahkan.
     */
    public function test_nomor_bentrok_ditolak_sampai_nomor_baru_diminta(): void
    {
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $nomor = 'PPA-ADRO-SOP-ICTMD-98';

        $penerus = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Penerus');
        $penerus->update(['status' => 'published', 'doc_number_final' => $nomor, 'published_at' => now()]);

        $lama = $this->obsolete('SOP Versi Lama', $nomor);

        // Tanpa `nomor_baru`: ditolak, dan statusnya TIDAK berubah.
        $this->actingAs($admin)->post(route('documents.restoreObsolete', $lama))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('obsolete', $lama->refresh()->status);

        // Dengan `nomor_baru`: aktif, tapi bernomor lain — dua dokumen Berlaku
        // bernomor sama tak boleh pernah terjadi.
        $this->actingAs($admin)->post(route('documents.restoreObsolete', $lama), ['nomor_baru' => 1])
            ->assertRedirect();

        $lama->refresh();
        $this->assertSame('published', $lama->status);
        $this->assertNotSame($nomor, $lama->doc_number_final);
        $this->assertSame($nomor, $penerus->refresh()->doc_number_final, 'penerusnya tak boleh ikut tersentuh');
    }

    public function test_selain_admin_tidak_boleh_mengaktifkan_kembali(): void
    {
        $doc = $this->obsolete('SOP Uji Wewenang', 'PPA-ADRO-SOP-ICTMD-96');

        // Tiga peran yang BUKAN Admin — mengaktifkan kembali dokumen obsolete
        // hanya wewenang Admin.
        foreach ([$this->aktorSh(), $this->aktorPjo(), $this->aktorGl()] as $bukanAdmin) {
            $this->actingAs($bukanAdmin)
                ->post(route('documents.restoreObsolete', $doc))
                ->assertForbidden();
        }

        $this->assertSame('obsolete', $doc->refresh()->status);
    }

    /**
     * Halaman Tidak Berlaku memberi peringatan yang BERBEDA saat nomornya
     * bentrok, dan hanya form bentrok yang membawa `nomor_baru`. Tanpa test ini
     * `$nomorTerpakai` bisa lupa dikirim controller dan halamannya baru meledak
     * di hadapan pemakai.
     */
    public function test_halaman_tidak_berlaku_menandai_nomor_yang_bentrok(): void
    {
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $aman = $this->obsolete('SOP Nomor Aman', 'PPA-ADRO-SOP-ICTMD-95');

        $nomorDipakai = 'PPA-ADRO-SOP-ICTMD-94';
        app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Penerus Lain')
            ->update(['status' => 'published', 'doc_number_final' => $nomorDipakai, 'published_at' => now()]);
        $bentrok = $this->obsolete('SOP Nomor Bentrok', $nomorDipakai);

        /*
        | Diperiksa PER BARIS, bukan se-halaman: basis data pengembangan sudah
        | memuat dokumen obsolete lain yang nomornya juga bentrok, jadi
        | menghitung "nomor_baru" di seluruh halaman menguji data orang lain,
        | bukan kode ini.
        |
        | Dulu dibaca dari markup form Blade. Sejak Fase 8 peringatan & muatan
        | `nomor_baru` sama-sama dipilih dari SATU props `nomor_bentrok`
        | (`pages/Documents/Obsolete.tsx`), dan server tetap memeriksanya ulang
        | di `restoreObsolete()` — jadi props itulah yang dikunci di sini.
        */
        $baris = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('documents.obsolete'))->assertOk()
        )['documents']['data'])->keyBy('id');

        $this->assertFalse($baris[$aman->id]['nomor_bentrok'],
            'dokumen yang nomornya aman tak boleh mengirim nomor_baru');
        $this->assertSame($aman->displayNumber(), $baris[$aman->id]['nomor']);

        $this->assertTrue($baris[$bentrok->id]['nomor_bentrok'],
            'dokumen yang nomornya bentrok harus mengirim nomor_baru');
        $this->assertSame($nomorDipakai, $baris[$bentrok->id]['nomor_final']);

        $this->assertSame('obsolete', $bentrok->refresh()->status);
    }

    /** Yang bukan obsolete tak boleh "diaktifkan" — mis. draft yang masih disusun. */
    public function test_hanya_dokumen_tidak_berlaku_yang_bisa_diaktifkan(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $draft = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Masih Draft');

        $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
            ->post(route('documents.restoreObsolete', $draft))
            ->assertStatus(422);

        $this->assertSame('draft', $draft->refresh()->status);
    }
}
