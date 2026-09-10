<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CancelRevisionBTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cancel_revision_type_b_restores_published(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Berlaku');
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => 'PPA-ADRO-SOP-ICTMD-01']);

        // Mulai Revisi (oleh GL, penyusunnya — Fase C) -> lama Sedang Direvisi + versi baru.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();
        $doc->refresh();
        $this->assertSame('sedang_direvisi', $doc->status);
        $new = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        // Batalkan Revisi -> lama kembali Berlaku, versi baru dibuang.
        $this->actingAs($gl)->post(route('documents.cancelRevisionB', $doc))->assertRedirect();
        $doc->refresh();
        $this->assertSame('published', $doc->status, 'versi lama kembali Berlaku');
        $this->assertNull(Document::find($new->id), 'versi baru dibuang (soft-deleted)');
    }
}
