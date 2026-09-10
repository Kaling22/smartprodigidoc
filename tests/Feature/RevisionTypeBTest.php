<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Revisi Tipe B (PRD v2 §3.3): dokumen Berlaku 0 -> 1, versi lama disimpan &
 * jadi obsolete setelah versi baru approved.
 */
class RevisionTypeBTest extends TestCase
{
    use DatabaseTransactions;

    public function test_revision_type_b_zero_to_one(): void
    {
        $gl = $this->aktorGl();        // pembuat
        $sh = $this->aktorSh();        // peninjau (pengaju revisi kini GL, Fase C)
        $pimpinan = $this->aktorPjo(); // approver
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Buat dokumen sudah Berlaku (No. Revisi 0).
        $doc = $svc->createDraft($gl, $type, $gl->department, 'SOP Berlaku');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan asli']]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);

        // SH Ajukan Revisi (GL sebagai pembuat tak lagi berhak).
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();

        $doc->refresh();
        $this->assertSame('sedang_direvisi', $doc->status, 'versi lama jadi Sedang Direvisi');
        $this->assertSame(1, $doc->versions()->count(), 'snapshot versi lama tersimpan');

        $new = \App\Models\Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();
        // butir 7a: nomor revisi BELUM naik saat revisi diajukan — draft masih
        // memikul nomor versi terbit sampai ia benar-benar dikirim.
        $this->assertSame(0, $new->no_revisi, 'No. Revisi belum naik saat revisi baru diajukan');
        $this->assertSame('draft', $new->status);
        $this->assertSame(['Tujuan asli'], $new->contentMap()['tujuan'], 'isi disalin dari versi lama');

        // Versi baru: pilih peninjau/approver, kirim, review, approve.
        $new->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->assertSame(1, $new->refresh()->no_revisi, 'No. Revisi naik ke 1 saat dikirim');
        $this->actingAs($sh)->get(route('review.show', $new)); // waiting_for_review -> in_review
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'approve'])->assertRedirect();
        // SOP melewati Management Development dulu sebelum sampai ke PJO.
        $this->actingAs(User::peninjauMd()->firstOrFail())
            ->post(route('review.md.store', $new), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pimpinan)->post(route('approvals.store', $new), ['decision' => 'approve'])->assertRedirect();

        $new->refresh();
        $doc->refresh();
        $this->assertSame('published', $new->status, 'versi baru Berlaku');
        $this->assertSame('obsolete', $doc->status, 'versi lama jadi obsolete');
    }

    /**
     * Roll-over: revisi 0..5; revisi berikutnya = Edisi+1 Rev 1 (W-5 — edisi
     * ≥2 tak pernah berevisi 0).
     *
     * Ambangnya naik dari 5 ke 6 atas keputusan pemilik K-B (rencana
     * pra-produksi Fase 1). Tabel penuhnya 0..6 diuji RollOverRevisiTest; yang
     * dikunci DI SINI adalah ambang itu benar-benar dipakai alur nyata — draft
     * revisi yang DIKIRIM, bukan cuma rumusnya.
     */
    public function test_revision_rolls_over_to_next_edition_at_six(): void
    {
        $this->assertSame([1, 1], DocumentService::nextEditionRevision(1, 0));
        $this->assertSame([1, 5], DocumentService::nextEditionRevision(1, 4), 'rev 4 -> masih Edisi 1 Rev 5');
        $this->assertSame([2, 1], DocumentService::nextEditionRevision(1, 5), 'rev 5 -> Edisi 2 Rev 1');
        $this->assertSame([3, 1], DocumentService::nextEditionRevision(2, 5));

        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pimpinan = $this->aktorPjo();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Dokumen Berlaku di ujung siklus: Edisi 1, Revisi 5.
        $doc = $svc->createDraft($gl, $type, $gl->department, 'SOP Ujung Siklus');
        $doc->update([
            'status' => 'published', 'published_at' => now(), 'no_revisi' => 5, 'edisi' => '1',
            'doc_number_final' => $doc->doc_number,
        ]);

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();

        $new = \App\Models\Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();
        // Roll-over ikut pindah ke saat KIRIM (butir 7a): Edisi dan Revisi harus
        // berasal dari satu saat yang sama, kalau tidak keduanya bisa berselisih.
        $this->assertSame(5, $new->no_revisi, 'sebelum dikirim masih Edisi 1 Rev 5');
        $this->assertSame('1', $new->edisi);

        // Approve → walau no_revisi 0, ini tetap REVISI: nomor diwariskan (bukan
        // nomor final baru) dan versi lama otomatis Tidak Berlaku.
        $new->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id]);
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $new->refresh();
        $this->assertSame(1, $new->no_revisi, 'revisi mulai 1 saat dikirim (W-5)');
        $this->assertSame('2', $new->edisi, 'edisi naik ke 2 saat dikirim');
        $this->actingAs($sh)->get(route('review.show', $new));
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'approve'])->assertRedirect();
        // SOP melewati Management Development dulu sebelum sampai ke PJO.
        $this->actingAs(User::peninjauMd()->firstOrFail())
            ->post(route('review.md.store', $new), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pimpinan)->post(route('approvals.store', $new), ['decision' => 'approve'])->assertRedirect();

        $new->refresh();
        $doc->refresh();
        $this->assertSame('published', $new->status, 'Edisi 2 Rev 1 Berlaku');
        $this->assertSame($doc->doc_number, $new->doc_number_final, 'nomor final diwariskan, bukan nomor baru');
        $this->assertSame('obsolete', $doc->status, 'versi lama (Edisi 1 Rev 5) otomatis Tidak Berlaku');
    }
}
