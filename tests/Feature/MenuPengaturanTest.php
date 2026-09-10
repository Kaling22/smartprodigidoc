<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Satu section "Pengaturan Sistem" di sidebar (PLAN-AKSES-v8 Fase 4).
 *
 * Sidebar dulu punya DUA section administrasi: "Administrasi" (Persetujuan
 * Akun, Manajemen User, Audit Log) dan "Pengaturan Sistem" (Manajemen Akses).
 * Belahannya tak pernah punya garis yang jelas — Manajemen User menyetel SIAPA
 * yang ada di sistem, persis seperti Manajemen Akses menyetel siapa boleh apa.
 *
 * Yang dikunci di sini bukan urutan atau ikonnya, melainkan dua janji yang bisa
 * rusak diam-diam saat peleburan:
 *
 *   1. Labelnya TINGGAL SATU — kata "Administrasi" benar-benar hilang.
 *   2. TAK ADA peran yang kehilangan menu yang dulu ia punya. Ini bahaya
 *      sesungguhnya: section barunya ber-@canany atas KETIGA izin, bukan
 *      @can('user.manage'). Kalau suatu hari seseorang "merapikannya" jadi
 *      satu @can('user.manage'), GL — yang memegang user.approve_registration
 *      & audit.view tapi bukan user.manage — kehilangan dua menu tanpa satu
 *      pun test lain yang berteriak.
 *
 * SELURUH aktor lahir di dalam transaksi (§3 jalan B), jadi test ini tak
 * bergantung pada akun contoh seeder yang hilang di basis data uji.
 */
class MenuPengaturanTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Judul bagian sidebar. Dulu markup Blade mentah
     * (`<div class="section-label">…</div>`) supaya komentar Blade tak ikut
     * terhitung; sejak sidebar datang dari props `navigation`, yang dicocokkan
     * adalah NILAI `label` bagiannya — tak ada lagi markup yang bisa keliru.
     */
    private const LABEL = 'Pengaturan Sistem';

    /** @return list<string> label bagian sidebar yang benar-benar terkirim. */
    private function bagian(User $user): array
    {
        return collect($this->menuSidebar(
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ))->pluck('bagian')->all();
    }

    /** @return list<string> seluruh label menu, induk maupun anak. */
    private function menu(User $user): array
    {
        return collect($this->menuSidebar(
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ))->pluck('label')->all();
    }

    private function orang(string $role): User
    {
        $tanda = Str::upper(Str::random(6));
        $dept = Department::create(['code' => 'UJS'.$tanda, 'name' => 'Departemen Uji '.$tanda]);

        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PGT-'.$tanda,
            'jabatan' => in_array($role, ['admin_it', 'management_development'], true) ? User::JABATAN_STAFF : $role,
            'department_id' => $dept->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    public function test_admin_melihat_satu_section_berisi_empat_item(): void
    {
        $admin = $this->orang('admin_it');
        $bagian = $this->bagian($admin);
        $menu = $this->menu($admin);

        $this->assertContains(self::LABEL, $bagian);
        $this->assertNotContains('Administrasi', $bagian);

        foreach (['Persetujuan Akun', 'Manajemen User', 'Manajemen Akses', 'Audit Log'] as $item) {
            $this->assertContains($item, $menu);
        }

        // Satu bagian, bukan dua yang kebetulan sama-sama bernama begitu.
        $judulBagian = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('dashboard'))->assertOk()
        )['navigation'])->pluck('label');

        $this->assertSame(1, $judulBagian->filter(fn ($l) => $l === self::LABEL)->count());
    }

    /**
     * GL memegang user.approve_registration & audit.view, tapi TIDAK user.manage.
     * Ia harus tetap melihat labelnya beserta dua menunya — dan tak sebutir pun
     * menu Admin yang bukan haknya.
     */
    public function test_gl_tetap_melihat_persetujuan_akun_dan_audit_log(): void
    {
        $gl = $this->orang(User::JABATAN_GROUP_LEADER);

        $this->assertContains(self::LABEL, $this->bagian($gl));

        $menu = $this->menu($gl);
        $this->assertContains('Persetujuan Akun', $menu);
        $this->assertContains('Audit Log', $menu);
        $this->assertNotContains('Manajemen User', $menu);
        $this->assertNotContains('Manajemen Akses', $menu);
    }

    /** Non-Staff tak memegang satu pun dari ketiga izin → labelnya tak muncul sama sekali. */
    public function test_non_staff_tak_melihat_section_pengaturan(): void
    {
        $staff = $this->orang(User::JABATAN_STAFF);

        $this->assertNotContains(self::LABEL, $this->bagian($staff));

        $menu = $this->menu($staff);
        $this->assertNotContains('Manajemen User', $menu);
        $this->assertNotContains('Audit Log', $menu);
    }
}
