<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PLAN-MOBILE-v6 §1.2 — pagar departemen kanal mobile.
 *
 * Sebelum ini `GET /api/documents` sama sekali tidak menyaring departemen:
 * chip departemen di HP tinggal ditekan untuk membaca dokumen orang lain, dan
 * server mengizinkannya.
 *
 * Yang dikunci di sini bukan satu controller melainkan KETIGA pintunya —
 * daftar, detail, dan byte PDF. Menambal salah satu saja menghasilkan gejala
 * yang tak terlihat dari kursi pengembang: daftarnya bersih, tapi PDF-nya
 * tetap terbuka.
 */
class PagarDepartemenTest extends TestCase
{
    use DatabaseTransactions;

    private Document $dokumenShe;

    protected function setUp(): void
    {
        parent::setUp();

        $she = Department::where('code', 'SHE')->firstOrFail();

        $glShe = $this->akun('GL-SHE-PGR', User::JABATAN_GROUP_LEADER, $she->id);

        $this->dokumenShe = app(DocumentService::class)->createDraft(
            $glShe,
            DocumentType::where('code', 'SOP')->firstOrFail(),
            $she,
            'SOP Uji Pagar Departemen',
        );
        $this->dokumenShe->update(['status' => 'published', 'published_at' => now()]);
        $this->dokumenShe->refresh();
    }

    private function akun(string $nrp, ?string $jabatan, ?int $departmentId): User
    {
        return User::create([
            'name' => $nrp,
            'nrp' => $nrp,
            'jabatan' => $jabatan,
            'status' => 'active',
            'password' => bcrypt('rahasia123'),
            'department_id' => $departmentId,
        ]);
    }

    /** Ketiga pintu sekaligus — inilah yang harus sepakat. */
    private function ketigaPintuTertutup(User $orang): void
    {
        Sanctum::actingAs($orang);
        $id = $this->dokumenShe->id;

        // Memaksa `?department=SHE` dari HP tak menembus pagar: paksaan
        // departemen ditaruh SESUDAH filter query string.
        $this->assertSame(
            [],
            $this->getJson('/api/documents?department=SHE')->assertOk()->json('data'),
            "Daftar bocor untuk {$orang->nrp}.",
        );

        // Tanpa `?department=` pun dokumen SHE tak boleh ikut.
        $this->assertNotContains(
            $id,
            collect($this->getJson('/api/documents')->json('data'))->pluck('id')->all(),
            "Dokumen SHE ikut di daftar tanpa filter untuk {$orang->nrp}.",
        );

        // 404, bukan 403: memberi tahu bahwa dokumen ADA tapi terlarang sudah
        // membocorkan keberadaannya.
        $this->getJson("/api/documents/{$id}")->assertNotFound();
        $this->get("/api/files/sop/{$id}.pdf")->assertNotFound();
    }

    public function test_non_staff_plant_tak_menembus_dokumen_she(): void
    {
        $plant = Department::where('code', 'PLANT')->value('id');
        $this->ketigaPintuTertutup($this->akun('STF-PLT-PGR', User::JABATAN_STAFF, $plant));
    }

    public function test_group_leader_plant_tak_menembus_dokumen_she(): void
    {
        $plant = Department::where('code', 'PLANT')->value('id');
        $this->ketigaPintuTertutup($this->akun('GL-PLT-PGR', User::JABATAN_GROUP_LEADER, $plant));
    }

    public function test_section_head_dan_departemen_head_plant_tak_menembus_dokumen_she(): void
    {
        $plant = Department::where('code', 'PLANT')->value('id');
        $this->ketigaPintuTertutup($this->akun('SH-PLT-PGR', User::JABATAN_SECTION_HEAD, $plant));
        $this->ketigaPintuTertutup($this->akun('DH-PLT-PGR', User::JABATAN_DEPARTEMEN_HEAD, $plant));
    }

    public function test_pemegang_view_all_tetap_lintas_departemen(): void
    {
        // PJO — inilah yang dikirim UserResource sebagai `lintas_departemen`.
        $pjo = $this->aktorPjo();
        $this->assertTrue($pjo->can('document.view_all'));

        Sanctum::actingAs($pjo);
        $id = $this->dokumenShe->id;

        $this->assertContains(
            $id,
            collect($this->getJson('/api/documents?department=SHE')->json('data'))->pluck('id')->all(),
        );
        $this->getJson("/api/documents/{$id}")->assertOk();
    }

    /**
     * Fase 3.1 bergantung pada pengecualian ini: GL SHE meninjau JSA departemen
     * LAIN, jadi peninjau yang ditunjuk harus tetap bisa membuka byte PDF-nya
     * walau pagar departemen menutup jalur biasa.
     */
    public function test_peninjau_yang_ditunjuk_tetap_membuka_pdf_departemen_lain(): void
    {
        $plant = Department::where('code', 'PLANT')->value('id');
        $peninjau = $this->akun('GL-PLT-TJU', User::JABATAN_GROUP_LEADER, $plant);

        $this->dokumenShe->update(['reviewer_id' => $peninjau->id]);

        Sanctum::actingAs($peninjau);
        $this->get("/api/files/sop/{$this->dokumenShe->id}.pdf")->assertOk();
    }
}
