<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Layar Manajemen Akses (PLAN-AKSES-v7 Fase 4b–4c).
 *
 * Fase 4a menutup pintu; fase inilah yang memberi Admin kuncinya. Tanpa layar
 * ini seluruh Group Leader terkunci permanen — jadi yang diuji di sini bukan
 * kosmetik melainkan JALAN KELUARNYA:
 *
 *   1. layarnya hanya milik Admin (`can:user.manage`);
 *   2. profil bisa dibuat, diubah, dihapus — dan penghapusan TIDAK ikut
 *      menghapus penggunanya (`nullOnDelete`);
 *   3. penetapan benar-benar mengubah wewenang orang yang bersangkutan;
 *   4. sidebar mematikan jenis yang tak berwenang, tanpa menyembunyikannya.
 *
 * SELURUH akun & profil dibuat di dalam transaksi (PLAN-AKSES-v7 §3).
 */
class ManajemenAksesTest extends TestCase
{
    use DatabaseTransactions;

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'MAKS-'.$tanda,
            'jabatan' => $role === Roles::ROLE_ADMIN ? 'staff' : $role,
            'department_id' => $dept?->id,
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function admin(): User
    {
        return $this->orang(Roles::ROLE_ADMIN, Department::where('code', 'ICTMD')->firstOrFail());
    }

    private function gl(string $dept = 'ICTMD'): User
    {
        return $this->orang(Roles::ROLE_GROUP_LEADER, Department::where('code', $dept)->firstOrFail());
    }

    /** Layar ini menyetel wewenang orang lain — GL tak boleh menyentuhnya. */
    public function test_layar_hanya_milik_admin(): void
    {
        $this->actingAs($this->admin())->get(route('akses.index'))->assertOk();
        $this->actingAs($this->gl())->get(route('akses.index'))->assertForbidden();
        $this->actingAs($this->orang(Roles::ROLE_PIMPINAN))->get(route('akses.index'))->assertForbidden();
    }

    /**
     * Membuat profil, lalu menetapkannya — dan wewenangnya BENAR-BENAR berubah.
     *
     * Diuji sebagai satu alur karena begitulah Admin memakainya; memeriksa
     * baris basis data saja tak membuktikan pintu di Fase 4a ikut terbuka.
     */
    public function test_admin_membuat_profil_lalu_menetapkannya(): void
    {
        $admin = $this->admin();
        $gl = $this->gl();
        $nama = 'Penyusun SOP Uji '.Str::upper(Str::random(5));

        $this->actingAs($admin)->post(route('akses.store'), [
            'nama' => $nama,
            'keterangan' => 'Uji Fase 4b',
            'jenis_dibolehkan' => ['SOP', 'IK'],
        ])->assertRedirect();

        $profil = AccessProfile::where('nama', $nama)->firstOrFail();
        $this->assertSame(['SOP', 'IK'], $profil->jenis_dibolehkan);
        $this->assertFalse($profil->boleh_review_jsa, 'kotak yang tak dicentang tak boleh jadi true');

        $this->assertFalse($gl->bolehBuatJenis('SOP'), 'prasyarat: sebelum ditetapkan, tertutup');

        $this->actingAs($admin)->post(route('akses.tetapkan', $gl), [
            'access_profile_id' => $profil->id,
        ])->assertRedirect();

        $gl = $gl->fresh();
        $this->assertTrue($gl->bolehBuatJenis('SOP'));
        $this->assertTrue($gl->bolehBuatJenis('IK'));
        $this->assertFalse($gl->bolehBuatJenis('JSA'));

        // Pintu Fase 4a ikut terbuka — bukan hanya kolomnya yang terisi.
        $this->actingAs($gl)->get(route('documents.create', ['type' => 'SOP']))->assertOk();

        $this->assertDatabaseHas('audit_logs', ['action' => 'akses.profil_dibuat']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'akses.ditetapkan']);
    }

    /**
     * Pesan sukses benar-benar SAMPAI KE LAYAR (PLAN-AKSES-v8 §11 T2).
     *
     * Controller ini mem-flash `success`, sementara layout dulu hanya merender
     * `status` — jadi keempat pesan suksesnya hilang diam-diam: Admin menekan
     * Simpan, profilnya tersimpan, dan layarnya tak berkata apa-apa. Cacat
     * seperti ini tak pernah muncul sebagai galat dan tak akan tertangkap oleh
     * test mana pun yang berhenti di `assertRedirect()`.
     *
     * Karena itu yang diikuti di sini adalah REDIRECT-nya sampai ke halaman
     * tujuan, lalu isi halamannya yang diperiksa — bukan isi session-nya.
     * Memeriksa `assertSessionHas('success')` akan tetap hijau walau layout-nya
     * kembali membuang pesan itu.
     */
    public function test_pesan_sukses_tampil_di_layar(): void
    {
        $admin = $this->admin();
        $nama = 'Profil Pesan Uji '.Str::upper(Str::random(5));

        $this->actingAs($admin)
            ->from(route('akses.index'))
            ->post(route('akses.store'), [
                'nama' => $nama,
                'keterangan' => 'Uji T2',
                'jenis_dibolehkan' => ['SOP'],
            ])
            ->assertRedirect(route('akses.index'));

        /*
        | Sejak Fase 6 halaman ini Inertia, dan pesan flash-nya duduk di props
        | `flash.success` (dibagikan HandleInertiaRequests) — bukan lagi teks di
        | markup. Yang dijaga TIDAK berubah: pesannya benar-benar SAMPAI ke
        | halaman tujuan. Memeriksa `assertSessionHas('success')` tetap akan
        | hijau walau lapisan tampilannya kembali membuangnya, jadi yang dibaca
        | tetap muatan halamannya.
        */
        $props = $this->propsInertia(
            $this->actingAs($admin)->get(route('akses.index'))->assertOk()
        );

        $this->assertSame("Profil \"{$nama}\" dibuat.", $props['flash']['success']);
    }

    /** Mencabut = memilih "— Tanpa Akses —"; itu pilihan sah, bukan kesalahan. */
    public function test_mencabut_profil_mengembalikan_ke_tanpa_akses(): void
    {
        $admin = $this->admin();
        $gl = $this->gl();
        $gl->accessProfile()->associate(AccessProfile::create([
            'nama' => 'Cabut Uji '.Str::upper(Str::random(5)),
            'jenis_dibolehkan' => ['SOP'],
        ]))->save();

        $this->assertTrue($gl->fresh()->bolehBuatJenis('SOP'));

        $this->actingAs($admin)->post(route('akses.tetapkan', $gl), [
            'access_profile_id' => '',
        ])->assertRedirect();

        $this->assertNull($gl->fresh()->access_profile_id);
        $this->assertFalse($gl->fresh()->bolehBuatJenis('SOP'));
    }

    /**
     * Menghapus profil TIDAK menghapus penggunanya — `nullOnDelete`.
     *
     * Kalau ini pernah berubah jadi `cascadeOnDelete`, Admin yang merapikan
     * daftar profil akan menghapus akun orang. Test ini yang menghalanginya.
     */
    public function test_menghapus_profil_tidak_menghapus_penggunanya(): void
    {
        $admin = $this->admin();
        $gl = $this->gl();
        $profil = AccessProfile::create([
            'nama' => 'Hapus Uji '.Str::upper(Str::random(5)),
            'jenis_dibolehkan' => ['SOP'],
        ]);
        $gl->accessProfile()->associate($profil)->save();

        $this->actingAs($admin)->delete(route('akses.destroy', $profil))->assertRedirect();

        $this->assertDatabaseMissing('access_profiles', ['id' => $profil->id]);
        $this->assertNotNull($gl->fresh(), 'penggunanya tak boleh ikut terhapus');
        $this->assertNull($gl->fresh()->access_profile_id);
        $this->assertFalse($gl->fresh()->bolehBuatJenis('SOP'));
    }

    /**
     * Penetapan hanya berlaku bagi Group Leader.
     *
     * Ditegakkan di controller, bukan dipercayakan kepada dropdown: formulir
     * yang dikarang bisa menembak id siapa pun.
     */
    public function test_penetapan_ditolak_untuk_bukan_group_leader(): void
    {
        $profil = AccessProfile::create([
            'nama' => 'Salah Sasaran '.Str::upper(Str::random(5)),
            'jenis_dibolehkan' => ['SOP'],
        ]);
        $sh = $this->orang(Roles::ROLE_SECTION_HEAD, Department::where('code', 'ICTMD')->firstOrFail());

        $this->actingAs($this->admin())
            ->post(route('akses.tetapkan', $sh), ['access_profile_id' => $profil->id])
            ->assertForbidden();

        $this->assertNull($sh->fresh()->access_profile_id);
    }

    /** Nama profil kembar ditolak — dua "Penyusun SOP" mustahil dibedakan. */
    public function test_nama_profil_wajib_unik(): void
    {
        $nama = 'Kembar Uji '.Str::upper(Str::random(5));
        AccessProfile::create(['nama' => $nama, 'jenis_dibolehkan' => ['SOP']]);

        $this->actingAs($this->admin())
            ->post(route('akses.store'), ['nama' => $nama, 'jenis_dibolehkan' => ['IK']])
            ->assertSessionHasErrors('nama');
    }

    /** Jenis yang tak dikenal ditolak: profil yang tak pernah cocok = profil mati. */
    public function test_jenis_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('akses.store'), [
                'nama' => 'Jenis Ngawur '.Str::upper(Str::random(5)),
                'jenis_dibolehkan' => ['SOP', 'XYZ'],
            ])
            // Kuncinya ber-INDEKS: yang ditolak elemen ke-1 (`XYZ`), bukan
            // seluruh lariknya — `SOP` di indeks 0 memang sah.
            ->assertSessionHasErrors('jenis_dibolehkan.1');
    }

    /**
     * Fase 4c — sidebar.
     *
     * Yang diuji BUKAN "menu hilang" melainkan "menu ada tapi mati": ketetapan
     * pemilik butir 4 adalah pengguna harus melihat fiturnya dan tahu ia perlu
     * meminta akses. Menyembunyikannya membuat orang mengira SmartPro tak punya
     * fitur itu.
     */
    public function test_sidebar_mematikan_jenis_di_luar_profil(): void
    {
        $gl = $this->gl();
        $gl->accessProfile()->associate(AccessProfile::create([
            'nama' => 'Sidebar Uji '.Str::upper(Str::random(5)),
            'jenis_dibolehkan' => ['SOP'],
        ]))->save();

        // Dulu dibaca dari markup (`pp-subnav-mati` + `href`); sejak sidebar
        // datang dari props `navigation`, penandanya bernama `locked` dan
        // `href` bernilai null. Yang dijaga sama persis: jenis di luar profil
        // TAMPIL tapi mustahil diklik.
        $menu = collect($this->menuSidebar(
            $this->actingAs($gl->fresh())->get(route('dashboard'))->assertOk()
        ))->keyBy('label');

        $this->assertContains('Dokumen Baru', $menu->pluck('label')->all(), 'menu induknya TETAP tampil');
        $this->assertTrue($menu->contains('locked', true), 'jenis di luar profil harus dimatikan');

        $this->assertSame(route('documents.create', ['type' => 'SOP']), $menu['SOP']['href']);
        $this->assertNull($menu['JSA']['href'], 'jenis mati tak boleh tetap bisa diklik');
        $this->assertTrue($menu['JSA']['locked']);
    }

    /**
     * Filter Penetapan Akses menyaring, bukan sekadar menghias.
     *
     * Yang dijaga khusus: pilihan "Tanpa Akses" — ia bukan id profil melainkan
     * `whereNull`, dan justru daftar itulah yang dicari Admin saat memeriksa
     * siapa yang belum ditetapkan.
     */
    public function test_filter_penetapan_menyaring_nama_dept_dan_tanpa_akses(): void
    {
        $admin = $this->admin();
        $profil = AccessProfile::create(['nama' => 'Uji Filter '.Str::random(5), 'jenis_dibolehkan' => ['SOP']]);

        // `access_profile_id` sengaja TIDAK fillable (lihat User::$fillable) —
        // disetel lewat properti, sama seperti AccessProfileController::tetapkan().
        $berprofil = $this->gl('ICTMD');
        $berprofil->access_profile_id = $profil->id;
        $berprofil->save();
        $kosong = $this->gl('SHE');

        $lihat = fn (array $q) => $this->actingAs($admin)->get(route('akses.index', $q))->assertOk()->getContent();

        // Cari nama → hanya yang dicari yang tersisa.
        $html = $lihat(['q' => $berprofil->name]);
        $this->assertStringContainsString($berprofil->nrp, $html);
        $this->assertStringNotContainsString($kosong->nrp, $html);

        // Departemen.
        $html = $lihat(['department_id' => $kosong->department_id]);
        $this->assertStringContainsString($kosong->nrp, $html);
        $this->assertStringNotContainsString($berprofil->nrp, $html);

        // Tanpa Akses.
        $html = $lihat(['access_profile_id' => 'tanpa']);
        $this->assertStringContainsString($kosong->nrp, $html);
        $this->assertStringNotContainsString($berprofil->nrp, $html);

        // Profil tertentu.
        $html = $lihat(['access_profile_id' => $profil->id]);
        $this->assertStringContainsString($berprofil->nrp, $html);
        $this->assertStringNotContainsString($kosong->nrp, $html);
    }

    /** GL tanpa profil: seluruh jenis mati, menunya tetap ada. */
    public function test_sidebar_gl_tanpa_profil_seluruh_jenis_mati(): void
    {
        $menu = collect($this->menuSidebar(
            $this->actingAs($this->gl())->get(route('dashboard'))->assertOk()
        ))->keyBy('label');

        $this->assertContains('Dokumen Baru', $menu->pluck('label')->all());
        foreach (['SOP', 'IK', 'SP', 'JSA', 'FK', 'PX'] as $kode) {
            $this->assertNull(
                $menu[$kode]['href'] ?? null,
                "jenis {$kode} tak boleh punya tautan bagi GL tanpa profil"
            );
        }
    }
}
