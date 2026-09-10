<?php

namespace Tests\Feature\Api;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Peninjauan dokumen dari aplikasi mobile.
 *
 * Yang dikunci di sini adalah JANJI-nya, bukan bentuk JSON-nya baris demi baris:
 * peninjau hanya melihat dokumen yang memang jadi tanggung jawabnya, batas
 * wewenang jenis (GL SHE hanya JSA) tetap berlaku walau URL ditempel langsung,
 * penilaian JSA wajib lengkap, dan hasilnya IDENTIK dengan jalur web — karena
 * keduanya memanggil ReviewDecision yang sama.
 */
class ReviewApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /** JSA milik GL ICTMD dengan dua pengendalian, sudah dikirim ke $peninjau. */
    private function jsa(User $peninjau, string $judul = 'JSA Uji API'): Document
    {
        $gl = $this->aktorGl();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, $judul
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Excavator memasuki area', 'bahaya' => [
                ['risiko' => 'Pengawas tertabrak unit', 'pengendalian' => [
                    'Lakukan pengawasan di luar radius gerak unit',
                    'Komunikasi 2 arah lewat channel yang ditentukan',
                ]],
            ]],
        ]]);
        $doc->update([
            'reviewer_id' => $peninjau->id,
            'approver_id' => $this->aktorPjo()->id,
            'status' => 'waiting_for_review',
            'submitted_at' => now()->subDays(3),
        ]);

        return $doc->refresh();
    }

    public function test_antrian_hanya_memuat_dokumen_yang_saya_tinjau(): void
    {
        $sh = $this->aktorSh();
        $glShe = $this->aktorGl('SHE');

        $milikSaya = $this->jsa($sh, 'JSA Untuk SH');
        $milikOrangLain = $this->jsa($glShe, 'JSA Untuk GL SHE');

        $data = $this->actingAs($sh, 'sanctum')->getJson('/api/review')
            ->assertOk()->json('data');

        $ids = array_column($data, 'id');
        $this->assertContains($milikSaya->id, $ids);
        $this->assertNotContains($milikOrangLain->id, $ids);

        // Umur ikut dikirim — itu yang dibaca peninjau lebih dulu.
        $baris = collect($data)->firstWhere('id', $milikSaya->id);
        $this->assertSame(3, $baris['umur_hari']);
        $this->assertSame('JSA', $baris['jenis']);
    }

    /**
     * Isi dokumen datang sebagai pohon SIAP PAKAI, dan membukanya menandai
     * `in_review` persis seperti di web.
     */
    public function test_detail_mengirim_pohon_analisa_dan_menandai_in_review(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->jsa($sh);

        $data = $this->actingAs($sh, 'sanctum')->getJson("/api/review/{$doc->id}")
            ->assertOk()
            ->assertJsonPath('data.pakai_verdict', true)
            ->assertJsonPath('data.jumlah_pengendalian', 2)
            ->json('data');

        $this->assertSame('in_review', $doc->refresh()->status, 'membuka dokumen menandainya sedang ditinjau');

        // Ref-nya SAMA dengan yang dipakai formulir web — itulah yang membuat
        // anotasi dari HP dan dari laptop menunjuk item yang sama.
        $this->assertSame('L0', $data['analisa'][0]['ref']);
        $this->assertSame('L0-B0', $data['analisa'][0]['bahaya'][0]['ref']);
        $this->assertSame('L0-B0-P0', $data['analisa'][0]['bahaya'][0]['pengendalian'][0]['ref']);
        $this->assertSame('1.1.2', $data['analisa'][0]['bahaya'][0]['pengendalian'][1]['nomor']);
        $this->assertStringContainsString('radius gerak unit', $data['analisa'][0]['bahaya'][0]['pengendalian'][0]['teks']);
    }

    /** Semua Sesuai → dokumen naik ke persetujuan, penyetuju dinotifikasi. */
    public function test_semua_sesuai_meloloskan_ke_persetujuan(): void
    {
        Notification::fake();

        $sh = $this->aktorSh();
        $doc = $this->jsa($sh);

        $this->actingAs($sh, 'sanctum')->postJson("/api/review/{$doc->id}", [
            'verdicts' => ['L0-B0-P0' => 'sesuai', 'L0-B0-P1' => 'sesuai'],
        ])->assertOk()->assertJsonPath('status', 'pending_approval');

        $this->assertSame('pending_approval', $doc->refresh()->status);
        Notification::assertSentTo($doc->approver, \App\Notifications\DocumentNotification::class);
    }

    /** Satu Perlu Revisi → dokumen kembali ke pembuat, walau tanpa catatan. */
    public function test_satu_perlu_revisi_mengembalikan_dokumen(): void
    {
        Notification::fake();

        $sh = $this->aktorSh();
        $doc = $this->jsa($sh);

        $this->actingAs($sh, 'sanctum')->postJson("/api/review/{$doc->id}", [
            'verdicts' => ['L0-B0-P0' => 'sesuai', 'L0-B0-P1' => 'perlu_revisi'],
            'catatan' => ['L0-B0-P1' => 'Channel radio belum disebutkan.'],
            'ringkasan' => 'Sebagian pengendalian belum spesifik.',
        ])->assertOk()->assertJsonPath('status', 'rejected');

        $this->assertSame('rejected', $doc->refresh()->status);
        Notification::assertSentTo($doc->creator, \App\Notifications\DocumentNotification::class);

        // Catatan datar dari HP dipulangkan ke section yang benar.
        $anotasi = $doc->reviews()->latest('id')->first()->annotations()->get();
        $this->assertSame('analisa', $anotasi->firstWhere('item_ref', 'L0-B0-P1')->section_key);
        $this->assertSame('Channel radio belum disebutkan.', $anotasi->firstWhere('item_ref', 'L0-B0-P1')->comment);
        $this->assertNull($anotasi->firstWhere('item_ref', 'L0-B0-P0')->comment, '✓ tanpa catatan tetap tercatat');
    }

    /** Penilaian setengah jadi ditolak server, bukan hanya oleh layar HP. */
    public function test_penilaian_belum_lengkap_ditolak(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->jsa($sh);

        $this->actingAs($sh, 'sanctum')->postJson("/api/review/{$doc->id}", [
            'verdicts' => ['L0-B0-P0' => 'sesuai'],
        ])->assertStatus(422)->assertJsonValidationErrors('verdicts');

        $this->assertSame('waiting_for_review', $doc->refresh()->status);
        $this->assertSame(0, $doc->reviews()->count());
    }

    /** GL SHE meninjau JSA departemen lain, tapi SOP tetap tertutup baginya. */
    public function test_gl_she_hanya_boleh_jsa(): void
    {
        $glShe = $this->aktorGl('SHE');
        $gl = $this->aktorGl();

        $jsa = $this->jsa($glShe);
        $this->actingAs($glShe, 'sanctum')->getJson("/api/review/{$jsa->id}")->assertOk();

        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Bukan Wewenang GL'
        );
        $sop->update(['reviewer_id' => $glShe->id, 'status' => 'waiting_for_review']);

        $this->actingAs($glShe, 'sanctum')->getJson("/api/review/{$sop->id}")->assertForbidden();
        $this->actingAs($glShe, 'sanctum')->postJson("/api/review/{$sop->id}", ['decision' => 'approve'])->assertForbidden();
    }

    /** Non-Staff tak punya pintu masuk ke antrian sama sekali. */
    public function test_non_staff_ditolak_gate(): void
    {
        $staff = User::where('jabatan', User::JABATAN_STAFF)->firstOrFail();

        $this->actingAs($staff, 'sanctum')->getJson('/api/review')->assertForbidden();
    }

    /**
     * PDF dokumen yang SEDANG ditinjau terbuka bagi peninjaunya — tanpa ini
     * "lihat PDF berdampingan" mustahil — dan tetap tertutup bagi yang lain.
     */
    public function test_pdf_dokumen_dalam_peninjauan_hanya_untuk_peninjaunya(): void
    {
        $sh = $this->aktorSh();
        $staff = User::where('jabatan', User::JABATAN_STAFF)->firstOrFail();
        $doc = $this->jsa($sh);

        $this->actingAs($sh, 'sanctum')->get("/api/files/JSA/{$doc->id}.pdf")->assertOk();
        $this->actingAs($staff, 'sanctum')->get("/api/files/JSA/{$doc->id}.pdf")->assertNotFound();
    }

    /** Aplikasi tahu harus memasang menu Tinjau dari jawaban server, bukan tebakan. */
    public function test_me_membawa_kapabilitas_peninjauan(): void
    {
        $sh = $this->aktorSh();
        $glShe = $this->aktorGl('SHE');
        $staff = User::where('jabatan', User::JABATAN_STAFF)->firstOrFail();

        $this->actingAs($sh, 'sanctum')->getJson('/api/me')
            ->assertJsonPath('user.bisa_tinjau', true)
            ->assertJsonPath('user.bisa_tinjau_jsa', false);

        $this->actingAs($glShe, 'sanctum')->getJson('/api/me')
            ->assertJsonPath('user.bisa_tinjau', true)
            ->assertJsonPath('user.bisa_tinjau_jsa', true);

        $this->actingAs($staff, 'sanctum')->getJson('/api/me')
            ->assertJsonPath('user.bisa_tinjau', false)
            ->assertJsonPath('user.bisa_beri_masukan', true);
    }
}
