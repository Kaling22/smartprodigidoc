<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiKategori;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Master Data — departemen, jenis dokumen, kategori informasi
 * (PLAN-AKSES-v8 Fase 5b).
 *
 * Yang dikunci di sini bukan tampilan tiga kartu, melainkan LIMA janji yang
 * kalau rusak baru ketahuan lama sesudahnya:
 *
 *   1. **Nonaktif ≠ hilang.** Departemen, jenis, dan kategori yang dimatikan
 *      berhenti DITAWARKAN, sementara dokumen & penggunanya tetap utuh. Tak
 *      ada satu pun rute hapus di layar ini.
 *   2. **Kode departemen terkunci begitu ada dokumen.** Kode masuk ke nomor
 *      dokumen yang sudah beredar di luar sistem; menggantinya menghasilkan
 *      satu departemen dengan dua kode yang sama-sama sah.
 *   3. **Saklar jenis benar-benar menyalakan sesuatu.** Ini jebakan yang
 *      dicatat rencananya: selama `DocumentType::kode()` membaca konstanta
 *      RUPA telanjang, `is_active` tak menghilangkan jenis itu dari satu pun
 *      dropdown.
 *   4. **Slug kategori beku.** Ia tersimpan sebagai TEKS di
 *      `informasi.kategori`; berubah = memutus dokumennya tanpa satu pun galat.
 *   5. **Layarnya milik Admin**, dan tiap perubahan ter-audit.
 *
 * SELURUH aktor lahir di dalam transaksi (§3 jalan B) — tak satu pun NRP
 * contoh dipakai.
 */
class MasterDataTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar kategori diingat SEUMUR REQUEST dalam properti statis, dan
        // properti statis tak ikut di-rollback bersama transaksi basis data.
        InformasiKategori::lupakanIngatan();
    }

    protected function tearDown(): void
    {
        InformasiKategori::lupakanIngatan();

        parent::tearDown();
    }

    private function dept(bool $aktif = true): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create([
            'code' => 'UJM'.$tanda,
            'name' => 'Departemen Uji '.$tanda,
            'is_active' => $aktif,
        ]);
    }

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'MST-'.$tanda,
            'jabatan' => $role === 'admin_it' ? User::JABATAN_STAFF : $role,
            'department_id' => ($dept ?? $this->dept())->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function kategoriUji(bool $aktif = true): InformasiKategori
    {
        $tanda = Str::lower(Str::random(6));

        return InformasiKategori::create([
            'slug' => 'uji_'.$tanda,
            'nama' => 'UJI '.Str::upper($tanda),
            'ikon' => 'bi-info-circle',
            'kolom_json' => [],
            'is_active' => $aktif,
            'urutan' => 900,
        ]);
    }

    /* ===================== Wewenang ===================== */

    public function test_hanya_admin_yang_boleh_membuka_master_data(): void
    {
        $this->actingAs($this->orang('admin_it'))->get(route('pengaturan.master'))->assertOk();
        $this->actingAs($this->orang(User::JABATAN_GROUP_LEADER))->get(route('pengaturan.master'))->assertForbidden();
        $this->actingAs($this->orang(User::JABATAN_SECTION_HEAD))->get(route('pengaturan.master'))->assertForbidden();
    }

    /* ===================== Departemen ===================== */

    public function test_departemen_baru_langsung_ditawarkan_pada_pendaftaran(): void
    {
        $kode = 'UJT'.Str::upper(Str::random(5));

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.master.departemen.tambah'), [
                'code' => Str::lower($kode),   // huruf kecil DINAIKKAN, bukan ditolak
                'name' => 'Departemen Baru Uji',
                'is_active' => '1',
            ])->assertRedirect();

        $baru = Department::where('code', $kode)->first();
        $this->assertNotNull($baru, 'Departemen baru tidak tersimpan.');
        $this->assertTrue($baru->is_active);

        // Halaman pendaftaran hanya untuk TAMU — `actingAs` di atas masih
        // melekat, dan tanpa logout yang diuji cuma pengalihannya.
        Auth::logout();
        // Halaman pendaftaran kini komponen Inertia (Fase 4): yang diperiksa
        // props `departments`-nya, bukan HTML-nya.
        $this->get(route('register'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('V2/Auth/Register')
                ->where('departments', fn ($d) => collect($d)->contains('name', 'Departemen Baru Uji')),
        );
    }

    /**
     * Janji 1 — nonaktif berarti BERHENTI DITAWARKAN, bukan lenyap: dokumen &
     * penggunanya tetap ada, dan formulir yang memilih departemen tak lagi
     * memuatnya.
     */
    public function test_departemen_nonaktif_hilang_dari_pilihan_tapi_datanya_utuh(): void
    {
        $dept = $this->dept();
        $penghuni = $this->orang(User::JABATAN_GROUP_LEADER, $dept);

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.departemen.simpan', $dept), [
                'code' => $dept->code,
                'name' => $dept->name,
                // `is_active` sengaja TIDAK dikirim — itulah bentuk checkbox
                // yang tak dicentang, dan bentuk itulah yang harus berarti
                // "matikan", bukan "jangan ubah apa-apa".
            ])->assertRedirect();

        $this->assertFalse($dept->fresh()->is_active);

        // Datanya utuh.
        $this->assertNotNull($penghuni->fresh());
        $this->assertSame($dept->id, $penghuni->fresh()->department_id);

        // Tak lagi ditawarkan — di layar maupun di server. (Halaman pendaftaran
        // hanya untuk tamu; `actingAs` di atas harus dilepas dulu.)
        Auth::logout();
        $this->get(route('register'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('V2/Auth/Register')
                ->where('departments', fn ($d) => ! collect($d)->contains('name', $dept->name)),
        );

        $this->post(route('register.store'), [
            'name' => 'Pendaftar Uji',
            'nrp' => 'REG-'.Str::upper(Str::random(6)),
            'department_id' => $dept->id,
            'password' => 'rahasia-panjang',
            'password_confirmation' => 'rahasia-panjang',
        ])->assertSessionHasErrors('department_id');
    }

    /** Janji 2 — kode terkunci begitu departemen itu punya dokumen. */
    public function test_kode_departemen_terkunci_bila_sudah_punya_dokumen(): void
    {
        $dept = $this->dept();
        $admin = $this->orang('admin_it');

        // Belum punya dokumen → kode masih boleh diubah.
        $kodeBaru = 'UJB'.Str::upper(Str::random(5));
        $this->actingAs($admin)
            ->put(route('pengaturan.master.departemen.simpan', $dept), [
                'code' => $kodeBaru, 'name' => $dept->name, 'is_active' => '1',
            ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($kodeBaru, $dept->fresh()->code);

        // Sesudah punya dokumen → ditolak, DAN kodenya tak bergeser.
        app(DocumentService::class)->createDraft(
            $this->orang(User::JABATAN_GROUP_LEADER, $dept),
            DocumentType::where('code', 'SOP')->firstOrFail(),
            $dept,
            'SOP Pengunci Kode'
        );

        $this->actingAs($admin)
            ->put(route('pengaturan.master.departemen.simpan', $dept), [
                'code' => 'UJZ'.Str::upper(Str::random(5)), 'name' => $dept->name, 'is_active' => '1',
            ])->assertSessionHasErrors('code');

        $this->assertSame($kodeBaru, $dept->fresh()->code);
    }

    /* ===================== Jenis dokumen ===================== */

    /**
     * Janji 3 — saklar `is_active` benar-benar menghilangkan jenis itu dari
     * daftar yang dipakai seluruh antarmuka, dan formulir pembuatannya berubah
     * jadi halaman "belum tersedia".
     */
    public function test_jenis_nonaktif_hilang_dari_daftar_dan_formulir(): void
    {
        $jenis = DocumentType::where('code', 'SP')->firstOrFail();
        $this->assertContains('SP', DocumentType::kode(), 'Prasyarat: SP semestinya aktif.');

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.jenis.simpan', $jenis), ['name' => $jenis->name])
            ->assertRedirect();

        $this->assertFalse($jenis->fresh()->is_active);
        $this->assertNotContains('SP', DocumentType::kode());

        // Dokumen SP yang sudah ada tak ikut hilang — jenisnya masih ada
        // barisnya, hanya berhenti ditawarkan.
        $this->assertNotNull(DocumentType::where('code', 'SP')->first());

        $gl = $this->berprofilPenuh($this->orang(User::JABATAN_GROUP_LEADER));
        $resp = $this->actingAs($gl)->get(route('documents.create', ['type' => 'SP']))->assertOk();

        $this->assertSame('V2/Documents/Unavailable', $resp->viewData('page')['component'],
            'jenis yang dimatikan harus mendarat di halaman "Belum Tersedia"');
        $this->assertSame('SP', $this->propsInertia($resp)['type']['code']);
    }

    public function test_nama_jenis_bisa_diubah_tanpa_menyentuh_kodenya(): void
    {
        $jenis = DocumentType::where('code', 'IK')->firstOrFail();

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.jenis.simpan', $jenis), [
                'name' => 'Instruksi Kerja (Uji)', 'is_active' => '1',
            ])->assertRedirect();

        $segar = $jenis->fresh();
        $this->assertSame('Instruksi Kerja (Uji)', $segar->name);
        $this->assertSame('IK', $segar->code);
    }

    /* ===================== Kategori informasi ===================== */

    public function test_kategori_baru_langsung_tampil_di_menu_informasi(): void
    {
        $nama = 'Uji Kategori '.Str::upper(Str::random(5));

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.master.kategori.tambah'), [
                'nama' => $nama,
                'ikon' => 'bi-clipboard-check',
                'kolom' => ['edisi', 'no_revisi'],
                'is_active' => '1',
            ])->assertRedirect();

        InformasiKategori::lupakanIngatan();
        $baru = InformasiKategori::where('nama', $nama)->firstOrFail();

        $this->assertTrue($baru->is_active);
        $this->assertSame(['edisi', 'no_revisi'], $baru->kolom());
        $this->assertSame(['edisi', 'no_revisi'], Informasi::kolomEkstra($baru->slug));
        $this->assertArrayHasKey($baru->slug, Informasi::kategori(aktifSaja: true));

        // Sidebar & daftarnya langsung bisa dibuka — tanpa rilis kode.
        $this->actingAs($this->orang(User::JABATAN_STAFF))
            ->get(route('informasi.index', ['kategori' => $baru->slug]))
            ->assertOk()->assertSee($nama);
    }

    /** Janji 4 — mengganti NAMA tak pernah menggeser slug. */
    public function test_slug_kategori_beku_meski_namanya_diubah(): void
    {
        $kat = $this->kategoriUji();
        $slugSemula = $kat->slug;

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.kategori.simpan', $kat), [
                'nama' => 'Nama Sama Sekali Berbeda '.Str::upper(Str::random(4)),
                'ikon' => 'bi-megaphone',
                'is_active' => '1',
            ])->assertRedirect();

        $this->assertSame($slugSemula, $kat->fresh()->slug);
        $this->assertSame('bi-megaphone', $kat->fresh()->ikon);
    }

    /**
     * Janji 1 untuk kategori (ketetapan pemilik H) — ditutup, bukan dihapus:
     * halamannya menyatakan alasannya, unggahan baru ditolak, dan dokumen yang
     * sudah ada tak tersentuh.
     */
    public function test_kategori_nonaktif_ditutup_tapi_dokumennya_tidak_hilang(): void
    {
        $kat = $this->kategoriUji();
        $admin = $this->orang('admin_it');

        $dokumen = Informasi::create([
            'kategori' => $kat->slug,
            'nomor' => 'UJI-'.Str::upper(Str::random(5)),
            'judul' => 'Dokumen Uji Kategori Tertutup',
            'file_path' => 'informasi/'.$kat->slug.'/uji.pdf',
            'file_mime' => 'application/pdf',
            'berlaku' => true,
            'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('pengaturan.master.kategori.simpan', $kat), [
                'nama' => $kat->nama, 'ikon' => $kat->ikon,
            ])->assertRedirect();

        InformasiKategori::lupakanIngatan();
        $this->assertFalse($kat->fresh()->is_active);

        // Barisnya masih ada, apa adanya.
        $this->assertNotNull($dokumen->fresh());

        // Daftarnya ditutup — dengan alasan yang terbaca, bukan 404 telanjang.
        // Halaman penjelasnya kini komponen tersendiri; kalimat "Sedang Ditutup"
        // digambar React, jadi yang diperiksa komponen + nama kategorinya.
        $resp = $this->actingAs($admin)
            ->get(route('informasi.index', ['kategori' => $kat->slug]))->assertOk();

        $this->assertSame('V2/Informasi/Nonaktif', $resp->viewData('page')['component']);
        $this->assertSame($kat->nama, $this->propsInertia($resp)['kat']['nama']);

        // Dan tak menerima unggahan baru.
        $this->actingAs($admin)->get(route('informasi.create', ['kategori' => $kat->slug]))
            ->assertNotFound();

        // Tak ikut dikirim ke aplikasi mobile.
        $slug = $kat->slug;
        $this->assertArrayNotHasKey($slug, Informasi::kategori(aktifSaja: true));
        $this->assertArrayHasKey($slug, Informasi::kategori(), 'Daftar penuh tetap memuatnya untuk menyaring data lama.');
    }

    public function test_ikon_di_luar_bootstrap_icons_ditolak(): void
    {
        $kat = $this->kategoriUji();

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.kategori.simpan', $kat), [
                'nama' => $kat->nama, 'ikon' => 'fa-solid fa-file', 'is_active' => '1',
            ])->assertSessionHasErrors('ikon');

        $this->assertSame('bi-info-circle', $kat->fresh()->ikon);
    }

    /* ===================== Audit ===================== */

    /** Janji 5 — perubahan master data tercatat lengkap dengan nilai sebelumnya. */
    public function test_perubahan_master_data_teraudit(): void
    {
        $dept = $this->dept();

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.master.departemen.simpan', $dept), [
                'code' => $dept->code, 'name' => 'Nama Departemen Sesudah Diubah', 'is_active' => '1',
            ])->assertRedirect();

        $log = AuditLog::where('action', 'master.departemen_simpan')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame($dept->id, $log->meta_json['department_id']);
        $this->assertSame($dept->name, $log->meta_json['sebelum']['name']);
        $this->assertSame('Nama Departemen Sesudah Diubah', $log->meta_json['sesudah']['name']);
    }
}
