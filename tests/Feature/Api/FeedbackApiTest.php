<?php

namespace Tests\Feature\Api;

use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Masukan lapangan dari aplikasi mobile (FITUR-BARU-v4 §3, kanal HP).
 *
 * Menulis ke tabel yang SAMA dengan kanal web — dikunci di sini supaya tak ada
 * yang tergoda membuat tabel `reports` terpisah seperti server lama.
 */
class FeedbackApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mobile_mengirim_masukan_lalu_membaca_riwayatnya(): void
    {
        $gl = $this->aktorGl();

        // Pengirim dibuat SEGAR, bukan memakai STF-0001: riwayat "/api/masukan"
        // dihitung per pengguna, jadi akun bawaan yang sudah pernah dipakai
        // menelusuri aplikasi akan membuat jumlahnya meleset.
        $nonStaff = User::create([
            'name' => 'Non-Staff Uji Mobile',
            'nrp' => 'STF-API-1',
            'jabatan' => User::JABATAN_STAFF,
            'status' => 'active',
            'password' => bcrypt('rahasia123'),
            'department_id' => $gl->department_id,
        ]);

        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji Mobile');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        Sanctum::actingAs($nonStaff);

        $this->postJson("/api/documents/{$doc->id}/masukan", ['isi' => 'Rambu di simpang tak terbaca malam hari.'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'baru')
            ->assertJsonPath('data.isi', 'Rambu di simpang tak terbaca malam hari.')
            ->assertJsonStructure(['data' => ['id', 'nomor', 'judul', 'no_dokumen', 'status_label', 'balasan', 'tanggal']]);

        // Kanal web & mobile satu tabel — bukan dua sumber kebenaran.
        $this->assertSame(1, DocumentFeedback::where('document_id', $doc->id)->count());

        $this->getJson('/api/masukan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'SOP Uji Mobile');
    }

    public function test_mobile_menolak_masukan_dari_selain_non_staff(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji Mobile 2');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        Sanctum::actingAs($gl);

        $this->postJson("/api/documents/{$doc->id}/masukan", ['isi' => 'Coba dari GL.'])
            ->assertForbidden();
    }

    public function test_masukan_butuh_token(): void
    {
        $this->getJson('/api/masukan')->assertUnauthorized();
    }
}
