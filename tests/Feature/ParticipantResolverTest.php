<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ParticipantResolverTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $role, ?Department $dept): User
    {
        static $n = 0;
        $n++;
        $u = User::create([
            'name' => "T {$role} {$n}", 'nrp' => "T-{$role}-{$n}", 'jabatan' => $role,
            'department_id' => $dept?->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u;
    }

    public function test_sop_reviewer_own_dept_and_approver_pjo_only(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'SOP Matriks');

        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');
        $this->assertTrue($reviewers->contains($shIct->id), 'SH dept sendiri jadi peninjau SOP');
        $this->assertFalse($reviewers->contains($shShe->id), 'SH dept lain BUKAN peninjau SOP');

        // Approver SOP = PJO saja (SH dept tidak boleh)
        $this->assertFalse($resolver->approverCandidates($doc)->pluck('id')->contains($shIct->id));
        $this->assertTrue($resolver->approverCandidates($doc)->every(fn ($u) => $u->hasRole('pimpinan')));
    }

    /**
     * v7 Fase 3 — JSA dari dept SELAIN SHE ditinjau GROUP LEADER SHE
     * (peninjauan K3 berbasis kompetensi, bukan hierarki). SH/DH dept pembuat
     * TIDAK meninjau; mereka yang MENYETUJUI (lihat test penyetuju).
     *
     * GL PLANT dicabut (keputusan pemilik butir 2) — dulu ia ikut di sini.
     */
    public function test_jsa_other_dept_is_reviewed_by_she_group_leaders(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $plant = Department::where('code', 'PLANT')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $dhIct = $this->makeUser('departemen_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);
        $glShe = $this->makeUser('group_leader', $she);
        $glPlant = $this->makeUser('group_leader', $plant);

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'JSA Matriks');
        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($reviewers->contains($glShe->id), 'GL SHE meninjau JSA dept lain');
        $this->assertFalse($reviewers->contains($glPlant->id), 'GL Plant DICABUT dari peninjauan JSA (v7 Fase 3)');
        $this->assertFalse($reviewers->contains($dhIct->id), 'DH dept pembuat BUKAN peninjau (dia penyetuju)');
        $this->assertFalse($reviewers->contains($shShe->id), 'SH SHE bukan peninjau utk JSA dept lain');
        $this->assertFalse($reviewers->contains($gl->id), 'pembuat tak bisa meninjau dokumennya sendiri');
    }

    /**
     * PJO tak pernah jadi PENINJAU. PJO = penyetuju SOP & JSA; tapi IK & SP disetujui
     * SH/DH dept sendiri SAJA (satu orang meninjau sekaligus menyetujui — PJO tak
     * terlibat), referensi PPA-ADRO-SP/IK.
     */
    public function test_pjo_approver_for_sop_jsa_but_ik_sp_use_dept_heads(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $pjo = $this->aktorPjo();

        // PJO tak pernah jadi peninjau untuk jenis apa pun.
        foreach (['SOP', 'IK', 'SP', 'JSA'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} PJO");
            $this->assertFalse($resolver->reviewerCandidates($doc)->pluck('id')->contains($pjo->id), "PJO BUKAN peninjau {$code}");
        }

        // SOP & JSA: PJO adalah penyetuju.
        foreach (['SOP', 'JSA'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} approve");
            $this->assertTrue($resolver->approverCandidates($doc)->pluck('id')->contains($pjo->id), "PJO penyetuju {$code}");
        }

        // IK & SP: penyetuju = SH/DH dept sendiri SAJA; PJO tidak.
        foreach (['IK', 'SP'] as $code) {
            $doc = app(DocumentService::class)->createDraft($gl, DocumentType::where('code', $code)->firstOrFail(), $ictmd, "{$code} dual");
            $approvers = $resolver->approverCandidates($doc)->pluck('id');
            $this->assertFalse($approvers->contains($pjo->id), "PJO BUKAN penyetuju {$code}");
            $this->assertTrue($approvers->contains($shIct->id), "SH dept sendiri penyetuju {$code}");
        }
    }

    /**
     * v3 rev — JSA disetujui SH/DH DEPARTEMEN PEMBUAT (atau PJO). Peninjauannya
     * diserahkan ke SHE & Plant, tapi pengesahannya tetap tanggung jawab atasan
     * di departemen yang membuat pekerjaan itu.
     */
    public function test_jsa_approver_is_creator_dept_heads_or_pjo(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $gl = $this->makeUser('group_leader', $ictmd);
        $shIct = $this->makeUser('section_head', $ictmd);
        $shShe = $this->makeUser('section_head', $she);
        $pjo = $this->aktorPjo();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $ictmd, 'JSA Approver');
        $approvers = $resolver->approverCandidates($doc)->pluck('id');

        $this->assertTrue($approvers->contains($shIct->id), 'SH dept PEMBUAT menyetujui JSA');
        $this->assertTrue($approvers->contains($pjo->id), 'PJO tetap boleh menyetujui JSA');
        $this->assertFalse($approvers->contains($shShe->id), 'SH SHE meninjau, bukan menyetujui JSA dept lain');
    }

    /**
     * v7 Fase 3 — JSA buatan SHE ditinjau GL SHE **yang lain**, plus SH/DH SHE.
     *
     * Pengecualiannya melekat pada ORANG, bukan departemen (keputusan pemilik):
     * yang dibuang penyusunnya sendiri, bukan seluruh rekan sedepartemennya.
     * Cabang `heads()` tetap ada sebagai cadangan — bila GL SHE cuma satu
     * orang, dialah penyusunnya, dan tanpa SH/DH JSA itu buntu.
     *
     * Yang berubah dari v3 rev: GL Plant DAN SH/DH Plant tak lagi kandidat.
     */
    public function test_jsa_from_she_excludes_only_its_author(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $plant = Department::where('code', 'PLANT')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $glShe = $this->makeUser('group_leader', $she);
        $glSheLain = $this->makeUser('group_leader', $she);   // GL SHE lain, bukan pembuat
        $shShe = $this->makeUser('section_head', $she);
        $shPlant = $this->makeUser('section_head', $plant);
        $shIct = $this->makeUser('section_head', $ictmd);
        $glPlant = $this->makeUser('group_leader', $plant);

        $doc = app(DocumentService::class)->createDraft($glShe, $type, $she, 'JSA di SHE');
        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($reviewers->contains($glSheLain->id), 'GL SHE LAIN boleh meninjau JSA milik SHE');
        $this->assertTrue($reviewers->contains($shShe->id), 'SH SHE tetap jadi jalur cadangan');
        $this->assertFalse($reviewers->contains($glShe->id), 'penyusunnya sendiri TETAP dibuang');
        $this->assertFalse($reviewers->contains($glPlant->id), 'GL Plant DICABUT (v7 Fase 3)');
        $this->assertFalse($reviewers->contains($shPlant->id), 'SH Plant ikut lepas: Plant bukan lagi dept khusus');
        $this->assertFalse($reviewers->contains($shIct->id), 'SH ICTMD BUKAN peninjau JSA di SHE');
    }

    /**
     * v7 Fase 3 — PLANT berhenti jadi departemen istimewa: JSA buatannya kini
     * diperlakukan persis seperti JSA departemen lain, yaitu ditinjau GL SHE.
     * Bedanya dengan v3 rev bukan pada GL-nya (GL Plant memang sudah
     * dikecualikan sebagai sedepartemen), melainkan pada SH/DH Plant — dulu
     * ikut jadi kandidat lewat cabang `heads()`, sekarang tidak.
     */
    public function test_jsa_from_plant_is_reviewed_by_she_gl_only(): void
    {
        $resolver = app(DocumentParticipantResolver::class);
        $she = Department::where('code', 'SHE')->firstOrFail();
        $plant = Department::where('code', 'PLANT')->firstOrFail();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $glPlant = $this->makeUser('group_leader', $plant);
        $glPlantLain = $this->makeUser('group_leader', $plant);
        $glShe = $this->makeUser('group_leader', $she);
        $shPlant = $this->makeUser('section_head', $plant);

        $doc = app(DocumentService::class)->createDraft($glPlant, $type, $plant, 'JSA di Plant');
        $reviewers = $resolver->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($reviewers->contains($glShe->id), 'GL SHE boleh: Plant bukan dept-nya');
        $this->assertFalse($reviewers->contains($glPlant->id), 'pembuat tak meninjau dokumennya sendiri');
        $this->assertFalse($reviewers->contains($glPlantLain->id), 'GL Plant lain pun tidak');
        $this->assertFalse($reviewers->contains($shPlant->id), 'SH Plant lepas: Plant bukan lagi dept khusus');
    }

    /**
     * Izin `document.review_jsa` DIBATASI dua lapis: hanya JSA, dan hanya GL
     * SHE (v7 Fase 3 — Plant dicabut). GL dept lain, Plant termasuk, tak
     * mendapat akses peninjauan sama sekali.
     */
    public function test_only_she_group_leaders_may_review_and_only_jsa(): void
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $plant = Department::where('code', 'PLANT')->firstOrFail();

        $glIct = $this->makeUser('group_leader', $ictmd);
        $glShe = $this->makeUser('group_leader', $she);
        $glPlant = $this->makeUser('group_leader', $plant);
        $shIct = $this->makeUser('section_head', $ictmd);

        $this->assertTrue($glShe->canReviewJsa(), 'GL SHE boleh meninjau JSA');
        $this->assertFalse($glIct->canReviewJsa(), 'GL dept lain tidak boleh meninjau');
        $this->assertFalse($glPlant->canReviewJsa(), 'GL Plant DICABUT (v7 Fase 3)');

        // GL tak pernah punya izin meninjau PENUH — hanya varian khusus JSA.
        $this->assertFalse($glShe->can('document.review'), 'GL bukan peninjau umum');
        $this->assertTrue($shIct->can('document.review'), 'SH tetap peninjau umum');

        // Menu & rute peninjauan terbuka utk GL SHE, tertutup utk GL dept lain.
        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($glShe)->allows('review-access'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($glIct)->allows('review-access'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($glPlant)->allows('review-access'));
    }
}
