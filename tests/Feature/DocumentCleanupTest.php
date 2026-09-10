<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DocumentCleanupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_generate_final_fills_gap(): void
    {
        $dept = Department::create(['code' => 'TST'.random_int(100, 999), 'name' => 'Test Dept']);
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $gl = $this->aktorGl();
        $svc = app(DocumentNumberService::class);

        // Belum ada final → mulai 01.
        $this->assertStringEndsWith('-01', $svc->generateFinal($type, $dept));

        // Final 01 & 03 terpakai → celah di 02.
        $a = app(DocumentService::class)->createDraft($gl, $type, $dept, 'A');
        $a->update(['doc_number_final' => "PPA-ADRO-SOP-{$dept->code}-01", 'status' => 'published']);
        $c = app(DocumentService::class)->createDraft($gl, $type, $dept, 'C');
        $c->update(['doc_number_final' => "PPA-ADRO-SOP-{$dept->code}-03", 'status' => 'published']);

        $this->assertStringEndsWith('-02', $svc->generateFinal($type, $dept), 'nomor final mengisi celah kosong');
    }

    /**
     * Nomor sementara MENGISI celah bekas draft yang dihapus, dan tetap tak
     * pernah bentrok dengan draft yang masih ada.
     *
     * Aturannya sengaja diubah (permintaan pemilik): dulu draft yang dihapus
     * tetap menyandera nomornya, sehingga urutan draft merambat naik terus
     * padahal dokumennya tak pernah ada. Draft belum tentu jadi dokumen — beda
     * dengan nomor FINAL, yang justru ditahan selamanya
     * ({@see \Tests\Feature\DocumentNumberingTest}).
     */
    public function test_temp_number_fills_gap_after_delete(): void
    {
        $dept = Department::create(['code' => 'TMP'.random_int(100, 999), 'name' => 'Temp Dept']);
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $gl = $this->aktorGl();
        $svc = app(DocumentService::class);

        $a = $svc->createDraft($gl, $type, $dept, 'A');   // -01
        $b = $svc->createDraft($gl, $type, $dept, 'B');   // -02
        $c = $svc->createDraft($gl, $type, $dept, 'C');   // -03

        $this->assertStringEndsWith('-03', $c->doc_number);

        $b->delete();

        $d = $svc->createDraft($gl, $type, $dept, 'D');
        $this->assertStringEndsWith('-02', $d->doc_number, 'draft baru mengisi celah bekas draft yang dihapus');

        // Yang tak boleh: dua dokumen HIDUP bernomor sama. Yang sudah dihapus
        // boleh berbagi nomor dengan penggantinya — memang itu maksudnya.
        $this->assertSame(
            1,
            Document::where('department_id', $dept->id)->where('doc_number', $d->doc_number)->count(),
            'nomor dokumen yang masih hidup harus unik'
        );

        // Dan yang berikutnya melanjutkan ke ujung, bukan mengulang -02.
        $e = $svc->createDraft($gl, $type, $dept, 'E');
        $this->assertStringEndsWith('-04', $e->doc_number);
    }

    public function test_published_can_be_made_obsolete_then_deleted(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Cleanup');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        // Yang diuji BERKAS ini adalah pembersihannya, bukan alurnya: sejak Fase F
        // mematikan dokumen melewati tiga tahap persetujuan, dan itu punya
        // testnya sendiri (NonaktifBerjenjangTest). Di sini cukup keadaan
        // akhirnya.
        $doc->update(['status' => 'obsolete', 'obsolete_reason' => Document::OBSOLETE_DINONAKTIFKAN]);

        // Hapus dari halaman Dokumen Tidak Berlaku
        $this->actingAs($gl)->delete(route('documents.destroy', $doc))->assertRedirect();
        $this->assertNull(Document::find($doc->id), 'dokumen obsolete terhapus');
    }

    public function test_ajukan_nonaktif_menolak_yang_belum_berlaku(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Draft');

        // 422, bukan 403: GL memang berhak atas dokumennya sendiri — yang belum
        // benar adalah STATUS dokumennya (Fase F memisahkan kedua pesan itu).
        $this->actingAs($gl)->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Coba dari draft.'])
            ->assertStatus(422);
    }
}
