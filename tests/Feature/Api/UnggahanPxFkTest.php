<?php

namespace Tests\Feature\Api;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PLAN-MOBILE-v6 §1.1 — PX (Prosedur External) & FK (Formulir Kerja) sampai
 * ke HP lewat rute yang SAMA dengan SOP/IK/SP/JSA.
 *
 * Keduanya berkelas `unggahan`: tak punya bab, isinya berkas PDF yang diunggah
 * (`documents.arsip_path`) dan dialirkan ArsipPdf, bukan digenerate DomPDF.
 * Tak ada `/api/px` maupun `/api/fk` — menambahnya membatalkan keputusan satu
 * rute untuk semua jenis (CLAUDE.md §7).
 *
 * Sampai ada dokumen PX/FK sungguhan yang diunggah lewat web, INILAH satu-
 * satunya bukti bahwa rantainya utuh.
 */
class UnggahanPxFkTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumenUnggahan(User $pemilik, string $kode, ?string $arsipPath): Document
    {
        return Document::create([
            'document_type_id' => DocumentType::where('code', $kode)->firstOrFail()->id,
            'department_id' => $pemilik->department_id,
            'doc_number' => "UJI-{$kode}-001",
            'title' => "Dokumen Uji {$kode}",
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $pemilik->id,
            'arsip_path' => $arsipPath,
        ]);
    }

    public function test_seeder_menanam_px_dan_fk_sebagai_jenis_unggahan_yang_aktif(): void
    {
        foreach (['PX', 'FK'] as $kode) {
            $type = DocumentType::where('code', $kode)->first();

            $this->assertNotNull($type, "DocumentTypeSeeder belum dijalankan: {$kode} tak ada.");
            $this->assertSame('unggahan', $type->class);
            $this->assertTrue((bool) $type->is_active);
        }
    }

    public function test_daftar_dan_detail_melayani_px_lewat_rute_yang_sama(): void
    {
        $gl = $this->aktorGl();
        $px = $this->dokumenUnggahan($gl, 'PX', 'arsip/uji-px.pdf');

        Sanctum::actingAs($gl);

        $baris = collect($this->getJson('/api/documents?type=PX')->assertOk()->json('data'))
            ->firstWhere('id', $px->id);

        $this->assertNotNull($baris, 'PX tak ikut di `?type=PX`.');
        $this->assertSame('PX', $baris['jenis']);
        // Nama berkas semu yang dipakai mobile menyusun `{publicUrl}/px/{berkas}`.
        $this->assertSame($px->id.'.pdf', $baris['berkas']);

        // Detailnya TIDAK membawa `langkah`/`jsa_info` — keduanya khusus JSA,
        // dan layar detail di HP memang tak boleh mengasumsikan keberadaannya.
        $detail = $this->getJson("/api/documents/{$px->id}")->assertOk()->json('data');
        $this->assertArrayNotHasKey('langkah', $detail);
        $this->assertArrayNotHasKey('jsa_info', $detail);
    }

    public function test_pdf_px_dialirkan_dari_berkas_unggahan_bukan_digenerate(): void
    {
        Storage::fake('local');

        $gl = $this->aktorGl();
        $fk = $this->dokumenUnggahan($gl, 'FK', 'arsip/uji-fk.pdf');
        Storage::disk('local')->put($fk->arsip_path, '%PDF-1.4 berkas unggahan');

        Sanctum::actingAs($gl);

        $res = $this->get("/api/files/fk/{$fk->id}.pdf")->assertOk();
        $this->assertStringContainsString('berkas unggahan', $res->streamedContent());
    }

    /**
     * Baris terdaftar lebih dulu, berkasnya menyusul — keadaan yang memang
     * terjadi. Pesannya harus SAMPAI ke layar; `ApiClient.unduhBiner` di HP
     * membacanya dari kunci `message`, dan itu hanya ada bila balasannya JSON.
     */
    public function test_berkas_belum_diunggah_menjawab_404_dengan_pesan_yang_terbaca(): void
    {
        $gl = $this->aktorGl();
        $px = $this->dokumenUnggahan($gl, 'PX', null);

        Sanctum::actingAs($gl);

        $this->getJson("/api/files/px/{$px->id}.pdf")
            ->assertNotFound()
            ->assertJsonPath('message', 'Berkas dokumen ini belum diunggah.');
    }
}
