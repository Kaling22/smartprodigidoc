<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\NullReviewer;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AiReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ai_analyze_returns_findings_from_provider(): void
    {
        config(['services.ai.enabled' => true]);

        // Swap the provider for a deterministic fake (no network call).
        $this->app->instance(AiReviewerInterface::class, new class implements AiReviewerInterface
        {
            public function isEnabled(): bool
            {
                return true;
            }

            public function ping(): ?string
            {
                return null;
            }

            public function review(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): array
            {
                return [
                    'summary' => 'Ringkasan uji.',
                    'findings' => [
                        ['section_key' => 'tujuan', 'severity' => 'minor', 'issue' => 'Kurang spesifik', 'suggestion' => 'Perjelas cakupan tujuan.'],
                    ],
                ];
            }
        });

        $staff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan']]);
        $doc->update(['status' => 'in_review', 'reviewer_id' => $sh->id, 'submitted_at' => now()]);

        $this->actingAs($sh)
            ->postJson(route('review.ai', $doc))
            ->assertStatus(202);

        $this->actingAs($sh)
            ->getJson(route('review.ai.status', $doc))
            ->assertOk()
            ->assertJson([
                'status' => 'selesai',
                'enabled' => true,
                'summary' => 'Ringkasan uji.',
                'findings' => [['section_key' => 'tujuan', 'suggestion' => 'Perjelas cakupan tujuan.']],
            ]);
    }

    public function test_adopted_ai_annotation_is_flagged(): void
    {
        $staff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI Adopt');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan']]);
        $doc->update(['status' => 'in_review', 'reviewer_id' => $sh->id]);

        $this->actingAs($sh)->post(route('review.store', $doc), [
            'decision' => 'reject',
            'annotations' => ['tujuan' => [0 => 'Perjelas cakupan tujuan.']],
            'annotations_ai' => ['tujuan' => [0 => '1']], // ditandai adopsi dari AI
        ])->assertRedirect();

        $annotation = $doc->reviews()->latest()->first()->annotations()->first();
        $this->assertTrue((bool) $annotation->ai_generated, 'anotasi ditandai berasal dari AI');
        $this->assertTrue((bool) $annotation->ai_adopted);
    }

    public function test_ai_disabled_returns_gracefully(): void
    {
        config(['services.ai.enabled' => false]);

        $staff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI Off');
        $doc->update(['status' => 'in_review', 'reviewer_id' => $sh->id]);

        $this->actingAs($sh)
            ->postJson(route('review.ai', $doc))
            ->assertOk()
            ->assertJson(['enabled' => false, 'findings' => []]);
    }

    /**
     * TEMUAN-F8: `NullReviewer` membedakan "dimatikan" dari "kunci belum
     * lengkap" (rencana pra-produksi Fase 3) — alasannya wajib sampai ke
     * `summary` respons `review.ai`, bukan cuma ke kartu Kesehatan.
     */
    public function test_ai_tak_lengkap_menyebut_alasannya_di_summary(): void
    {
        $this->app->instance(AiReviewerInterface::class, new NullReviewer(NullReviewer::TAK_LENGKAP));

        $staff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, 'SOP AI Tak Lengkap');
        $doc->update(['status' => 'in_review', 'reviewer_id' => $sh->id]);

        $this->actingAs($sh)
            ->postJson(route('review.ai', $doc))
            ->assertOk()
            ->assertJson(['enabled' => false, 'summary' => NullReviewer::TAK_LENGKAP, 'findings' => []]);
    }

    /**
     * `users.ai_review_enabled` berlaku di tahap tinjau BIASA juga, bukan cuma
     * di tahap MD.
     *
     * Sampai rencana pra-produksi Fase 3 satu izin ini ditegakkan di satu jalur
     * saja (MdReviewController), jadi Admin yang mencabut AI dari sebuah akun
     * tetap melihat panelnya utuh di layar tinjau SH/DH — satu izin, dua
     * perilaku.
     */
    public function test_panel_ai_hilang_bila_toggle_akun_dimatikan(): void
    {
        [$sh, $doc] = $this->dokumenSiapTinjau('SOP AI Toggle');
        $sh->update(['ai_review_enabled' => false]);

        $this->actingAs($sh)
            ->get(route('review.show', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('aiUrl', null));
    }

    /**
     * Menyembunyikan tombolnya BUKAN otorisasi (CLAUDE.md §4) — rutenya tetap
     * bisa dipanggil langsung, jadi penegakannya harus ada di server.
     */
    public function test_ai_analyze_ditolak_bila_toggle_akun_dimatikan(): void
    {
        [$sh, $doc] = $this->dokumenSiapTinjau('SOP AI Tolak');
        $sh->update(['ai_review_enabled' => false]);

        $this->actingAs($sh)
            ->postJson(route('review.ai', $doc))
            ->assertForbidden()
            ->assertJson(['enabled' => false, 'findings' => []]);

        // Menyembunyikan tombol bukan otorisasi (CLAUDE.md §4) — rute GET
        // status yang lahir bersama pekerjaan antrean wajib ditegakkan sama.
        $this->actingAs($sh)
            ->getJson(route('review.ai.status', $doc))
            ->assertForbidden()
            ->assertJson(['enabled' => false, 'findings' => []]);
    }

    /** Satu SOP milik Non-Staff yang sudah duduk di meja SH. */
    private function dokumenSiapTinjau(string $judul): array
    {
        $staff = $this->aktorNonStaff();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($staff, $type, $staff->department, $judul);
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan']]);
        $doc->update(['status' => 'in_review', 'reviewer_id' => $sh->id, 'submitted_at' => now()]);

        return [$sh, $doc];
    }
}
