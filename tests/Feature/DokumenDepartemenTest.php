<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * "Dokumen Departemen" ≠ "Dokumen Saya" (PLAN-AKSES-v8 Fase 1).
 *
 * Satu janji yang dikunci: bagi GL, layar `documents.staffStatus` memuat
 * pekerjaan REKAN sedepartemennya dan tak pernah memuat pekerjaannya sendiri —
 * yang itu sudah punya menunya sendiri (`documents.index`). Dokumen yang muncul
 * di dua daftar membuat keduanya tak bisa dipercaya sebagai hitungan.
 *
 * Dua penjaga ikut dikunci karena keduanya justru yang bisa rusak diam-diam
 * oleh saringan baru itu:
 *   • SH/DH — layar yang sama bernama "Status Dokumen Staff"; ia tak pernah
 *     membuat dokumen, jadi saringannya wajib no-op baginya.
 *   • Admin/PJO (`document.view_all`) — baginya layar ini pantauan 7 dept, dan
 *     dokumen ARSIP didaftarkan atas namanya sendiri. Menyembunyikannya =
 *     melubangi pantauan.
 *
 * SELURUH aktor & departemennya lahir di dalam transaksi (§3 jalan B): basis
 * data uji kehilangan sebagian akun contoh seeder, dan departemen nyata sudah
 * memuat dokumen orang lain yang membuat kasus "daftar kosong" mustahil diuji.
 */
class DokumenDepartemenTest extends TestCase
{
    use DatabaseTransactions;

    private function dept(): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create(['code' => 'UJD'.$tanda, 'name' => 'Departemen Uji '.$tanda]);
    }

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'DEPT-'.$tanda,
            'jabatan' => $role === Roles::ROLE_ADMIN ? User::JABATAN_STAFF : $role,
            'department_id' => $dept?->id,
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function draft(User $pembuat, Department $dept, string $judul)
    {
        return app(DocumentService::class)->createDraft(
            $pembuat, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, $judul
        );
    }

    public function test_gl_melihat_dokumen_rekan_dan_bukan_miliknya_sendiri(): void
    {
        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);

        $docA = $this->draft($glA, $dept, 'SOP Milik GL-A '.Str::random(6));
        $docB = $this->draft($glB, $dept, 'SOP Milik GL-B '.Str::random(6));

        // GL-B: pekerjaan GL-A tampil, pekerjaannya sendiri tidak.
        $this->actingAs($glB)->get(route('documents.staffStatus'))
            ->assertOk()
            ->assertSee($docA->title)
            ->assertDontSee($docB->title);

        // GL-A: kebalikannya, persis.
        $this->actingAs($glA)->get(route('documents.staffStatus'))
            ->assertOk()
            ->assertSee($docB->title)
            ->assertDontSee($docA->title);

        // Yang tersembunyi di sana TIDAK hilang dari sistem — ia ada di
        // "Dokumen Saya" milik pembuatnya.
        $this->actingAs($glA)->get(route('documents.index'))
            ->assertOk()
            ->assertSee($docA->title)
            ->assertDontSee($docB->title);
    }

    public function test_status_dokumen_staff_milik_section_head_tetap_utuh(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);

        $doc = $this->draft($gl, $dept, 'SOP Pantauan SH '.Str::random(6));

        $this->actingAs($sh)->get(route('documents.staffStatus'))
            ->assertOk()
            ->assertSee($doc->title);
    }

    public function test_admin_tetap_melihat_dokumen_yang_didaftarkannya_sendiri(): void
    {
        $dept = $this->dept();
        $admin = $this->orang(Roles::ROLE_ADMIN);

        // Dokumen arsip didaftarkan ATAS NAMA Admin (DocumentArsipController:60).
        $doc = $this->draft($admin, $dept, 'SOP Arsip Admin '.Str::random(6));

        $this->actingAs($admin)->get(route('documents.staffStatus', ['department_id' => $dept->id]))
            ->assertOk()
            ->assertSee($doc->title);
    }
}
