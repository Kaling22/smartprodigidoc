<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Jenis UNGGAHAN — FK (Formulir Kerja) & PX (Prosedur External).
 *
 * Keduanya jenis dokumen penuh, tapi tanpa bab: isinya berkas PDF yang
 * diunggah. Yang dijaga di sini adalah tiga janji yang rusak diam-diam:
 *
 *  1. `documents/create?type=FK` MEMBUKA formulir unggah, bukan halaman
 *     "belum tersedia". Schema kosongnya bentuk normal, bukan tanda belum siap.
 *  2. Pintu wizard TERTUTUP untuk FK/PX. Draft berbab untuk jenis tanpa bab
 *     adalah dokumen yang tak akan pernah bisa diisi maupun dicetak — dan ia
 *     tampak baik-baik saja di daftar sampai ada yang membukanya.
 *  3. Pendaftarannya menempuh jalur arsip yang SUDAH ADA, tanpa cabang baru.
 */
class JenisUnggahanTest extends TestCase
{
    use DatabaseTransactions;

    private function gl(): User
    {
        // Profil akses penuh: berkas ini menguji arsip/catatan/unggahan,
        // bukan wewenang menyusun (PLAN-AKSES-v7 Fase 4).
        return $this->berprofilPenuh($this->aktorGl());
    }

    public function test_jenis_fk_dan_px_terdaftar_sebagai_unggahan(): void
    {
        foreach (['FK', 'PX'] as $kode) {
            $type = DocumentType::where('code', $kode)->firstOrFail();

            $this->assertTrue($type->isUnggahan(), "{$kode} harus berkelas unggahan.");
            $this->assertTrue($type->is_active);
            $this->assertEmpty($type->schema_json['steps'] ?? [], "{$kode} tak pernah punya bab.");
            $this->assertContains($kode, DocumentType::kode(),
                'Jenis yang tak ada di kode() akan hilang dari saringan, submenu, dan daftar induk.');
        }
    }

    /** Formulir "Dokumen Baru FK" terbuka, dan bentuknya formulir UNGGAH. */
    public function test_formulir_fk_membuka_mode_unggah_bukan_halaman_belum_tersedia(): void
    {
        $resp = $this->actingAs($this->gl())
            ->get(route('documents.create', ['type' => 'FK']))
            ->assertOk();

        // Formulir UNGGAH, bukan halaman "Belum Tersedia".
        $props = $this->propsInertia($resp);
        $this->assertSame('V2/Documents/Create', $resp->viewData('page')['component']);

        // `unggahanSaja` = saklar "dokumen lama" dikunci menyala DAN
        // disembunyikan; kolom berkas PDF selalu tampil. Ketiganya digambar
        // Create.tsx dari satu props ini — dijaga WizardInertiaTest.
        $this->assertTrue($props['unggahanSaja']);
        $this->assertSame('FK', $props['type']['code']);
    }

    /** Wizard menolak FK/PX: draft berbab untuk jenis tanpa bab tak pernah sah. */
    public function test_wizard_menolak_jenis_unggahan(): void
    {
        $this->actingAs($this->gl())
            ->post(route('documents.store'), [
                'document_type_id' => DocumentType::where('code', 'PX')->firstOrFail()->id,
                'department_id' => $this->gl()->department_id,
                'title' => 'PX Lewat Wizard',
            ])
            ->assertStatus(422);
    }

    /** PX didaftarkan lewat jalur arsip → langsung Berlaku, berkasnya tersimpan. */
    public function test_px_didaftarkan_lewat_jalur_arsip(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $nomor = 'PX-UJI-'.Str::upper(Str::random(8));

        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => DocumentType::where('code', 'PX')->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Prosedur External Uji',
            'doc_number' => $nomor,
            'edisi' => 1,
            'no_revisi' => 0,
            'tanggal_efektif' => '2024-03-01',
            'berkas' => UploadedFile::fake()->createWithContent('px.pdf', "%PDF-1.4\ntrailer\n%%EOF"),
        ])->assertRedirect(route('documents.published'));

        $doc = Document::where('doc_number', $nomor)->firstOrFail();

        $this->assertSame('published', $doc->status);
        $this->assertTrue($doc->isArsip());
        Storage::disk('local')->assertExists($doc->arsip_path);
    }
}
