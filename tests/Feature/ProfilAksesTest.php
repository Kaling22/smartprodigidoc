<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Profil akses (PLAN-AKSES-v7 Fase 4a, butir 3).
 *
 * Aturannya dua kalimat: jenis yang boleh DISUSUN ditentukan profil akses per
 * orang, dan tanpa profil berarti tanpa wewenang. Wewenang meninjau JSA ikut
 * profil yang sama — SHE tetap otomatis.
 *
 * Yang dikunci di sini adalah SELURUH PINTU TULIS sekaligus, sebab menutup
 * satu dan lupa yang lain persis bentuk kegagalan yang tak terlihat sampai
 * seseorang menempel URL:
 *
 *   1. FORMULIR   — `documents.create` (wizard maupun daftar dokumen lama).
 *   2. WIZARD     — `documents.store` / StoreDocumentRequest.
 *   3. ARSIP      — `documents.arsip.store` / ArsipDocumentRequest.
 *   4. PENINJAUAN — `canReviewJsa()` & papan pemilihan peninjau.
 *
 * SELURUH akun dibuat di dalam transaksi: suite ini berjalan di atas basis
 * data pengembangan yang sudah kehilangan sebagian akun contoh seeder
 * (PLAN-AKSES-v7 §3), jadi test yang menembak NRP merah karena sebab yang
 * sama sekali tak berhubungan dengan aturan ini.
 */
class ProfilAksesTest extends TestCase
{
    use DatabaseTransactions;

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PAKS-'.$tanda,
            'jabatan' => $role === Roles::ROLE_ADMIN ? 'staff' : $role,
            'department_id' => $dept?->id,
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    /** @param  array<int, string>  $jenis */
    private function profil(array $jenis, bool $reviewJsa = false): AccessProfile
    {
        return AccessProfile::create([
            'nama' => 'Uji Profil '.Str::upper(Str::random(6)),
            'jenis_dibolehkan' => $jenis,
            'boleh_review_jsa' => $reviewJsa,
        ]);
    }

    private function dept(string $code): Department
    {
        return Department::where('code', $code)->firstOrFail();
    }

    private function jenisId(string $code): int
    {
        return DocumentType::where('code', $code)->firstOrFail()->id;
    }

    /** Kirim wizard sebagai $gl untuk jenis $kode. */
    private function kirimWizard(User $gl, string $kode): TestResponse
    {
        return $this->actingAs($gl)->post(route('documents.store'), [
            'document_type_id' => $this->jenisId($kode),
            'department_id' => $gl->department_id,
            'title' => 'Dokumen Uji Profil Akses',
        ]);
    }

    /**
     * Bawaan yang disengaja: GL tanpa profil tak bisa menyusun APA PUN.
     *
     * Ketiga pintu diuji dalam satu test karena yang dikunci memang satu
     * aturan — memecahnya jadi tiga hanya menyembunyikan bahwa ketiganya wajib
     * jatuh bersama-sama.
     */
    public function test_gl_tanpa_profil_tertutup_di_tiga_pintu(): void
    {
        $gl = $this->orang(Roles::ROLE_GROUP_LEADER, $this->dept('ICTMD'));

        $this->assertTrue($gl->can('document.create'), 'izin perannya tetap utuh');
        $this->assertFalse($gl->bolehBuatJenis('SOP'));

        $this->actingAs($gl)->get(route('documents.create', ['type' => 'SOP']))->assertForbidden();
        $this->kirimWizard($gl, 'SOP')->assertForbidden();

        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => $this->jenisId('SOP'),
            'department_id' => $gl->department_id,
            'title' => 'Arsip Uji',
            'doc_number' => 'PAKS-'.Str::upper(Str::random(8)),
            'edisi' => 1,
            'no_revisi' => 0,
        ])->assertForbidden();
    }

    /**
     * Profil ["SOP"] berarti SOP DAN HANYA SOP.
     *
     * Sisi "boleh"-nya diuji sampai dokumennya benar-benar tersimpan, bukan
     * berhenti di kode 200: profil yang meloloskan formulir tapi menjegal
     * penyimpanan adalah kegagalan yang paling mahal ditemukan pengguna.
     */
    public function test_profil_sop_membuka_sop_saja(): void
    {
        $gl = $this->orang(Roles::ROLE_GROUP_LEADER, $this->dept('ICTMD'));
        $gl->accessProfile()->associate($this->profil(['SOP']))->save();
        $gl = $gl->fresh();

        $this->assertTrue($gl->bolehBuatJenis('SOP'));
        $this->assertTrue($gl->bolehBuatJenis('sop'), 'kode tak peka huruf besar-kecil');
        $this->assertFalse($gl->bolehBuatJenis('JSA'));

        $this->actingAs($gl)->get(route('documents.create', ['type' => 'SOP']))->assertOk();
        $this->kirimWizard($gl, 'SOP')->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'created_by' => $gl->id,
            'title' => 'Dokumen Uji Profil Akses',
        ]);

        $this->actingAs($gl)->get(route('documents.create', ['type' => 'JSA']))->assertForbidden();
        $this->kirimWizard($gl, 'JSA')->assertForbidden();
    }

    /**
     * Admin IT tak pernah terkunci (CLAUDE.md §6).
     *
     * Bukan kemudahan: kalau Admin ikut menunggu profil, satu profil yang
     * salah sunting bisa mengunci satu-satunya orang yang boleh membetulkannya.
     */
    public function test_admin_lolos_tanpa_profil(): void
    {
        $admin = $this->orang(Roles::ROLE_ADMIN, $this->dept('ICTMD'));

        $this->assertNull($admin->access_profile_id);
        $this->assertTrue($admin->bolehBuatJenis('JSA'));
        $this->actingAs($admin)->get(route('documents.create', ['type' => 'JSA']))->assertOk();
    }

    /** Non-Staff tetap read-only: profil bukan jalan pintas melewati izin. */
    public function test_profil_tak_memberi_wewenang_kepada_yang_tak_punya_izin(): void
    {
        $staff = $this->orang(Roles::ROLE_STAFF, $this->dept('ICTMD'));
        $staff->accessProfile()->associate($this->profil(['SOP', 'IK', 'SP', 'JSA']))->save();

        $this->assertFalse($staff->fresh()->bolehBuatJenis('SOP'),
            'profil adalah lapis KEDUA di atas document.create, bukan penggantinya');
    }

    /**
     * Keluwesan yang dijanjikan Fase 3: GL non-SHE mendapat wewenang meninjau
     * JSA kembali lewat profil — satu per satu, atas penetapan Admin.
     *
     * Diuji sampai papan pemilihan, bukan berhenti di `canReviewJsa()`:
     * wewenang yang tak muncul sebagai kandidat sama saja dengan tak ada.
     */
    public function test_profil_mengembalikan_wewenang_tinjau_jsa(): void
    {
        $glPlant = $this->orang(Roles::ROLE_GROUP_LEADER, $this->dept('PLANT'));
        $this->assertFalse($glPlant->canReviewJsa(), 'tanpa profil tetap tercabut (Fase 3)');

        $glPlant->accessProfile()->associate($this->profil([], true))->save();
        $glPlant = $glPlant->fresh();

        $this->assertTrue($glPlant->canReviewJsa());

        $ictmd = $this->dept('ICTMD');
        $doc = app(DocumentService::class)->createDraft(
            $this->orang(Roles::ROLE_GROUP_LEADER, $ictmd),
            DocumentType::where('code', 'JSA')->firstOrFail(),
            $ictmd,
            'JSA Uji Profil'
        );

        $this->assertTrue(
            app(DocumentParticipantResolver::class)->reviewerCandidates($doc)->contains('id', $glPlant->id),
            'GL berprofil peninjau JSA wajib muncul di papan pemilihan'
        );

        $doc->update(['reviewer_id' => $glPlant->id, 'status' => 'in_review']);
        $this->actingAs($glPlant)->get(route('review.show', $doc->fresh()))->assertOk();
    }

    /**
     * Profil "peninjau JSA saja" TIDAK ikut membuka wewenang menyusun.
     *
     * Dua kolom, dua pertanyaan — dan yang satu tak pernah menjawab yang lain.
     */
    public function test_boleh_tinjau_jsa_bukan_berarti_boleh_menyusun_jsa(): void
    {
        $gl = $this->orang(Roles::ROLE_GROUP_LEADER, $this->dept('PLANT'));
        $gl->accessProfile()->associate($this->profil([], true))->save();

        $this->assertFalse($gl->fresh()->bolehBuatJenis('JSA'));
    }
}
