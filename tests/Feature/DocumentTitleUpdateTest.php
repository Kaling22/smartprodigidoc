<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Revisi judul dokumen — celah yang dilaporkan pemilik 2026-10-10: pembuat
 * tak punya cara mengubah judul sejak H1 layout (`Edit.tsx`) menggantikan
 * kartu kop lama. `documents.updateTitle` SENGAJA terpisah dari saveStep/
 * autosave: `title` kolom `documents`, bukan isi `document_contents` yang
 * digerbangi langkah schema.
 */
class DocumentTitleUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private function draft(string $judul = 'SOP Judul Lama')
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        return [app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul), $gl];
    }

    public function test_pembuat_dapat_merevisi_judul_draft(): void
    {
        [$doc, $gl] = $this->draft();

        $this->actingAs($gl)
            ->put(route('documents.updateTitle', $doc), ['title' => 'SOP Judul Baru'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('SOP Judul Baru', $doc->refresh()->title);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.title_updated',
            'document_id' => $doc->id,
            'user_id' => $gl->id,
        ]);
    }

    /** Dokumen yang DITOLAK (Tipe A) tetap bisa direvisi judulnya — status itu masih `isEditable()`. */
    public function test_judul_dapat_direvisi_saat_dokumen_ditolak(): void
    {
        [$doc, $gl] = $this->draft();
        $doc->update(['status' => 'rejected']);

        $this->actingAs($gl)
            ->put(route('documents.updateTitle', $doc), ['title' => 'SOP Hasil Perbaikan'])
            ->assertSessionHas('status');

        $this->assertSame('SOP Hasil Perbaikan', $doc->refresh()->title);
    }

    public function test_judul_tak_dapat_diubah_sesudah_dikirim_ditinjau(): void
    {
        [$doc, $gl] = $this->draft();
        $doc->update(['status' => 'waiting_for_review']);

        $this->actingAs($gl)
            ->put(route('documents.updateTitle', $doc), ['title' => 'Coba Ubah'])
            ->assertForbidden();

        $this->assertSame('SOP Judul Lama', $doc->refresh()->title);
    }

    public function test_bukan_pembuat_tak_dapat_mengubah_judul_draft_orang_lain(): void
    {
        [$doc, $gl] = $this->draft();
        // GL KEDUA sedepartemen, kloningan via peninjauBersih() (bukan
        // aktorGl('ICTMD', 2) — fixture "GL kedua" itu tak selalu tersedia di
        // basis data pengembangan). Bisa MELIHAT dokumen ini (authorizeView:
        // sedepartemen), tapi bukan pembuatnya, jadi isEditable() wajib menolak.
        $glLain = $this->peninjauBersih($gl);

        $this->actingAs($glLain)
            ->put(route('documents.updateTitle', $doc), ['title' => 'Coba Ubah'])
            ->assertForbidden();

        $this->assertSame('SOP Judul Lama', $doc->refresh()->title);
    }

    public function test_judul_kosong_ditolak_validasi(): void
    {
        [$doc, $gl] = $this->draft();

        $this->actingAs($gl)
            ->put(route('documents.updateTitle', $doc), ['title' => ''])
            ->assertSessionHasErrors('title');

        $this->assertSame('SOP Judul Lama', $doc->refresh()->title);
    }

    /** Judul yang dikirim sama persis tak perlu menulis ulang kolom atau audit log — tak ada yang berubah. */
    public function test_judul_identik_tak_menambah_audit_log(): void
    {
        [$doc, $gl] = $this->draft();

        $this->actingAs($gl)
            ->put(route('documents.updateTitle', $doc), ['title' => 'SOP Judul Lama'])
            ->assertRedirect();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.title_updated', 'document_id' => $doc->id]);
    }
}
