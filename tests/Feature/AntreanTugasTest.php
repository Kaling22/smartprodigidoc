<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\AntreanTugas;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Antrean tugas (PLAN-KESADARAN-TUGAS A1) — sumber tunggal badge sidebar,
 * kartu dashboard, dan modal saat login.
 *
 * Semua pemeran dibuat lewat {@see TestCase::peninjauBersih()}: suite ini
 * berjalan di atas basis data PENGEMBANGAN, jadi akun contoh sudah membawa
 * beban dokumen sungguhan dan angka yang diuji di sini harus berangkat dari nol.
 */
class AntreanTugasTest extends TestCase
{
    use DatabaseTransactions;

    private function antrean(User $user): array
    {
        return app(AntreanTugas::class)->untuk($user);
    }

    private function dokumen(string $jenis, Department $dept, User $pembuat, array $atribut): Document
    {
        return Document::create([
            'doc_number' => 'UJI-'.$jenis.'-'.uniqid(),
            'document_type_id' => DocumentType::where('code', $jenis)->firstOrFail()->id,
            'department_id' => $dept->id,
            'title' => "Dokumen uji antrean {$jenis}",
            'created_by' => $pembuat->id,
        ] + $atribut);
    }

    public function test_peninjau_melihat_jumlah_dokumen_yang_menunggu(): void
    {
        $sh = $this->peninjauBersih($this->aktorSh());
        $gl = $this->aktorGl();
        $dept = Department::findOrFail($sh->department_id);

        $this->dokumen('SOP', $dept, $gl, ['status' => 'waiting_for_review', 'reviewer_id' => $sh->id]);
        $this->dokumen('SOP', $dept, $gl, ['status' => 'in_review', 'reviewer_id' => $sh->id]);

        $antrean = $this->antrean($sh);

        // in_review IKUT: dokumen jadi in_review begitu peninjau membukanya,
        // dan membukanya bukan berarti menyelesaikannya.
        $this->assertSame(2, $antrean['tinjau']['jumlah']);
        $this->assertSame('review.index', $antrean['tinjau']['route']);

        // Kunci berjumlah 0 tak pernah muncul — SH memegang `document.approve`,
        // tapi tak satu dokumen pun menunggu persetujuannya.
        $this->assertArrayNotHasKey('setujui', $antrean);
    }

    /**
     * Inilah bug diam yang ikut diperbaiki: GL SHE/Plant meninjau JSA lewat
     * `document.review_jsa`, izin yang TIDAK dilihat penjaga lama
     * (`document.review`) — sehingga angka antreannya tak pernah muncul
     * meski menunya terbuka lewat gate `review-access`.
     */
    public function test_gl_she_peninjau_jsa_departemen_lain_ikut_dihitung(): void
    {
        $glShe = $this->peninjauBersih($this->aktorGl());
        $she = Department::where('code', 'SHE')->firstOrFail();
        $glShe->forceFill(['department_id' => $she->id])->save();
        $glShe = $glShe->fresh();

        $deptLain = Department::where('code', '!=', 'SHE')->firstOrFail();
        $this->dokumen('JSA', $deptLain, $glShe, ['status' => 'waiting_for_review', 'reviewer_id' => $glShe->id]);

        $this->assertTrue($glShe->canReviewJsa());
        $this->assertSame(1, $this->antrean($glShe)['tinjau']['jumlah'] ?? 0);
    }

    public function test_non_staff_tanpa_tugas_mendapat_antrean_kosong(): void
    {
        $ns = $this->peninjauBersih($this->aktorNonStaff());

        $this->assertSame([], $this->antrean($ns));
    }

    /**
     * Modal login: penandanya flash `antrean_awal` (bertahan tepat satu
     * request), isinya $antrean milik layout. Diuji ujung ke ujung karena yang
     * mudah patah justru sambungannya, bukan salah satu ujungnya.
     */
    public function test_login_memunculkan_modal_tugas_sekali_saja(): void
    {
        // Kartu Ketersediaan di dashboard memanggil API libur nasional.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        \Illuminate\Support\Facades\Http::fake(['api-harilibur.vercel.app/*' => \Illuminate\Support\Facades\Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);

        $sh = $this->peninjauBersih($this->aktorSh());
        $gl = $this->aktorGl();
        $this->dokumen('SOP', Department::findOrFail($sh->department_id), $gl,
            ['status' => 'waiting_for_review', 'reviewer_id' => $sh->id]);

        $this->post(route('login.store'), ['nrp' => $sh->nrp, 'password' => 'password'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('antrean_awal');

        // Judul modalnya kini di TSX; penandanya props `antreanAwal` — dibagikan
        // `HandleInertiaRequests`, bukan lagi `@if (session(...))` di layout
        // Blade. Yang dijaga tetap sama: ia terisi tepat SEKALI.
        $this->assertNotNull(
            $this->propsInertia($this->get(route('dashboard'))->assertOk())['antreanAwal'],
        );

        // Muat ulang: flash sudah habis, modal tak muncul lagi.
        $this->assertNull(
            $this->propsInertia($this->get(route('dashboard'))->assertOk())['antreanAwal'],
        );
    }
}
