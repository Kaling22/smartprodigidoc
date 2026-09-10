<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Peninjauan JSA sesudah PLANT dicabut (PLAN-AKSES-v7 Fase 3, butir 2).
 *
 * Aturan barunya satu kalimat: yang meninjau JSA adalah GL **SHE** saja.
 * Yang dikunci di sini adalah PENCABUTANNYA — tiga pintu sekaligus, sebab
 * mencabut di satu tempat dan lupa di tempat lain persis bentuk kegagalan yang
 * paling mahal: papan pemilihan masih menawarkan orangnya, lalu ia 403 saat
 * membuka dokumen yang sudah jadi tanggung jawabnya.
 *
 *   1. WEWENANG — `canReviewJsa()` & gate `review-access` (menu + penjaga rute).
 *   2. PEMILIHAN — `DocumentParticipantResolver::reviewerCandidates()`.
 *   3. HALAMAN — `review.show`, penjaga sesungguhnya lewat `ReviewAccess`.
 *
 * SELURUH akun dibuat di dalam transaksi. Itu bukan kerapian: suite ini
 * berjalan di atas basis data pengembangan yang sudah kehilangan sebagian akun
 * contoh seeder (PLAN-AKSES-v7 §3), sehingga test yang menembak NRP tetap akan
 * merah karena sebab yang sama sekali tak berhubungan dengan aturan ini.
 */
class PeninjauJsaTest extends TestCase
{
    use DatabaseTransactions;

    private function orang(string $role, Department $dept): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PJSA-'.$tanda,
            'jabatan' => $role,
            'department_id' => $dept->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function dept(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    /** JSA milik ICTMD — departemen yang BUKAN SHE maupun PLANT. */
    private function jsaIctmd(User $peninjau): Document
    {
        $ictmd = $this->dept('ICTMD');
        $doc = app(DocumentService::class)->createDraft(
            $this->orang('group_leader', $ictmd),
            DocumentType::where('code', 'JSA')->firstOrFail(),
            $ictmd,
            'JSA Uji Peninjau'
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);
        $doc->update(['reviewer_id' => $peninjau->id, 'status' => 'in_review']);

        return $doc->fresh();
    }

    /**
     * Lapis 1 — wewenangnya sendiri.
     *
     * GL Plant kehilangan `canReviewJsa()`, dan bersamanya SELURUH akses ke
     * area "Tinjau Dokumen": gate `review-access` tak punya sumber lain untuk
     * seorang GL, sebab GL tak pernah memegang `document.review` penuh.
     */
    public function test_gl_plant_kehilangan_wewenang_meninjau_jsa(): void
    {
        $glPlant = $this->orang('group_leader', $this->dept('PLANT'));
        $glShe = $this->orang('group_leader', $this->dept('SHE'));

        $this->assertFalse($glPlant->canReviewJsa(), 'GL Plant DICABUT (v7 Fase 3)');
        $this->assertTrue($glShe->canReviewJsa(), 'GL SHE tetap otomatis berwenang');

        // Izin spatie-nya TIDAK dicabut — yang berubah batas departemennya.
        // Dicatat di sini supaya Fase 4 tahu di mana keluwesannya dikembalikan.
        $this->assertTrue($glPlant->can('document.review_jsa'), 'izin peran tetap utuh');

        $this->assertFalse(Gate::forUser($glPlant)->allows('review-access'), 'menu Tinjau Dokumen tertutup');
        $this->assertTrue(Gate::forUser($glShe)->allows('review-access'));
    }

    /**
     * Lapis 2 — papan pemilihan peninjau.
     *
     * Kalau ini tertinggal, seorang pembuat masih bisa MENUNJUK GL Plant, dan
     * dokumennya berakhir di meja orang yang halamannya 403.
     */
    public function test_gl_plant_lenyap_dari_kandidat_peninjau_jsa(): void
    {
        $glPlant = $this->orang('group_leader', $this->dept('PLANT'));
        $glShe = $this->orang('group_leader', $this->dept('SHE'));
        $ictmd = $this->dept('ICTMD');

        $doc = app(DocumentService::class)->createDraft(
            $this->orang('group_leader', $ictmd),
            DocumentType::where('code', 'JSA')->firstOrFail(),
            $ictmd,
            'JSA Kandidat'
        );

        $kandidat = app(DocumentParticipantResolver::class)->reviewerCandidates($doc)->pluck('id');

        $this->assertFalse($kandidat->contains($glPlant->id), 'GL Plant tak boleh ditawarkan lagi');
        $this->assertTrue($kandidat->contains($glShe->id), 'GL SHE tetap ditawarkan');
    }

    /**
     * Lapis 3 — halaman tinjaunya.
     *
     * Sengaja diuji dengan `reviewer_id` yang SUDAH menunjuk GL Plant: itulah
     * keadaan dokumen yang terlanjur ditugaskan sebelum aturan ini berlaku.
     * Yang menolak harus lapis JENIS (`bolehJenis`), bukan lapis penugasan —
     * kalau penjaganya hanya "apakah dia yang ditunjuk", pencabutan ini bocor.
     */
    public function test_halaman_tinjau_jsa_tertutup_bagi_gl_plant(): void
    {
        $glPlant = $this->orang('group_leader', $this->dept('PLANT'));
        $doc = $this->jsaIctmd($glPlant);

        $this->actingAs($glPlant)->get(route('review.show', $doc))->assertForbidden();
    }

    /** Cermin: GL SHE tetap bekerja seperti biasa — pencabutan tak kebablasan. */
    public function test_halaman_tinjau_jsa_tetap_terbuka_bagi_gl_she(): void
    {
        $glShe = $this->orang('group_leader', $this->dept('SHE'));
        $doc = $this->jsaIctmd($glShe);

        $this->actingAs($glShe)->get(route('review.show', $doc))->assertOk();
    }

    /**
     * JSA buatan SHE — yang dibuang PENYUSUNNYA, bukan seluruh dept SHE.
     *
     * Keputusan pemilik pada sesi Fase 3. Konflik kepentingan melekat pada
     * orang yang menulis dokumennya, bukan pada papan nama departemennya, dan
     * SHE memang punya lebih dari satu GL. `heads()` tetap jadi cadangan —
     * kalau GL SHE cuma satu orang, dialah penyusunnya dan JSA itu buntu
     * tanpa SH/DH.
     */
    public function test_jsa_buatan_she_ditinjau_gl_she_lain_dan_sh(): void
    {
        $she = $this->dept('SHE');
        $glShe = $this->orang('group_leader', $she);
        $glSheLain = $this->orang('group_leader', $she);
        $shShe = $this->orang('section_head', $she);

        $doc = app(DocumentService::class)->createDraft(
            $glShe, DocumentType::where('code', 'JSA')->firstOrFail(), $she, 'JSA Buatan SHE'
        );

        $kandidat = app(DocumentParticipantResolver::class)->reviewerCandidates($doc)->pluck('id');

        $this->assertTrue($kandidat->contains($glSheLain->id), 'GL SHE LAIN boleh meninjaunya');
        $this->assertTrue($kandidat->contains($shShe->id), 'SH SHE tetap jadi jalur cadangan');
        $this->assertFalse($kandidat->contains($glShe->id), 'penyusunnya sendiri tetap dibuang');
    }

    /**
     * PEMBUAT TAMBAHAN ikut dibuang.
     *
     * Namanya tercetak di baris "Dibuat Oleh" pada lembar pengesahan
     * (CLAUDE.md §6), jadi membiarkannya meninjau sama saja dengan menilai
     * tulisannya sendiri — celah yang cuma terbuka di SHE, sebab hanya di sana
     * penyusun dan peninjau berasal dari departemen yang sama.
     */
    public function test_pembuat_tambahan_gl_she_tak_boleh_meninjau(): void
    {
        $she = $this->dept('SHE');
        $glShe = $this->orang('group_leader', $she);
        $glPendamping = $this->orang('group_leader', $she);
        $glKetiga = $this->orang('group_leader', $she);

        $doc = app(DocumentService::class)->createDraft(
            $glShe, DocumentType::where('code', 'JSA')->firstOrFail(), $she, 'JSA Berpendamping'
        );
        $doc->authors()->create(['user_id' => $glPendamping->id, 'is_primary' => false]);

        $kandidat = app(DocumentParticipantResolver::class)->reviewerCandidates($doc->fresh())->pluck('id');

        $this->assertFalse($kandidat->contains($glPendamping->id), 'pembuat tambahan tak meninjau tulisannya sendiri');
        $this->assertFalse($kandidat->contains($glShe->id), 'pembuat utama pun tidak');
        $this->assertTrue($kandidat->contains($glKetiga->id), 'GL SHE yang tak ikut menyusun tetap boleh');
    }
}
