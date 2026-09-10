<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Penomoran Dokumen — setelan site (PLAN-AKSES-v8 Fase 5a).
 *
 * Setelan ini menyentuh NOMOR DOKUMEN MUTU, jadi yang dikunci di sini bukan
 * tampilannya melainkan empat janji yang kalau rusak baru ketahuan berbulan
 * kemudian, saat auditor memegang cetakan dengan nomor yang tak lagi ada:
 *
 *   1. Sistem yang setelannya BELUM pernah disentuh bernomor persis seperti
 *      sebelum tabel `pengaturan` ada. Tak ada baris seed; bawaannya di kode.
 *   2. Prefix baru hanya mengenai dokumen BARU. Nomor yang sudah tersimpan
 *      tak pernah ditulis ulang — itu keputusan, bukan kelalaian (§11).
 *   3. Badge "Nomor Lama" ikut membaca setelan yang SAMA. Ini jebakan yang
 *      paling mudah terlewat: kalau ia tetap menghardcode "PPA-ADRO", setiap
 *      dokumen yang baru dibuat langsung dicap lama — persis kebalikan artinya.
 *   4. Layarnya milik Admin saja, dan perubahannya ter-audit.
 *
 * SELURUH aktor lahir di dalam transaksi (§3 jalan B).
 */
class PengaturanSiteTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Setelan di-cache selamanya. Tanpa membuangnya di sini, nilai yang
        // ditulis satu test bisa terbaca test berikutnya lewat cache meski
        // barisnya sudah ikut ter-rollback bersama transaksi.
        $this->lupakanCache();
    }

    protected function tearDown(): void
    {
        $this->lupakanCache();

        parent::tearDown();
    }

    private function lupakanCache(): void
    {
        Cache::forget('pengaturan:site.prefix');
        Cache::forget('pengaturan:site.nama');

        // Ingatan seumur-request Pengaturan adalah larik STATIS — ia tak ikut
        // di-rollback bersama transaksi, jadi harus dibuang terpisah.
        Pengaturan::lupakanIngatan();
    }

    private function dept(): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create(['code' => 'UJN'.$tanda, 'name' => 'Departemen Uji '.$tanda]);
    }

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PNM-'.$tanda,
            'jabatan' => $role === 'admin_it' ? User::JABATAN_STAFF : $role,
            'department_id' => ($dept ?? $this->dept())->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function draft(User $gl, Department $dept, string $judul)
    {
        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, $judul
        );
    }

    /** Janji 1 — tabel kosong berarti berperilaku seperti sebelum tabel ini ada. */
    public function test_tanpa_setelan_memakai_nilai_bawaan(): void
    {
        $this->assertSame('PPA-ADRO', Pengaturan::prefix());
        $this->assertSame('ADARO INDONESIA', Pengaturan::namaSite());

        $dept = $this->dept();
        $doc = $this->draft($this->orang(User::JABATAN_GROUP_LEADER, $dept), $dept, 'SOP Bawaan');

        $this->assertStringStartsWith('PPA-ADRO-SOP-', $doc->doc_number);
        $this->assertFalse($doc->nomorLuarPola());
    }

    /** Nilai kosong DIBACA sebagai "belum disetel", bukan sebagai prefix kosong. */
    public function test_setelan_kosong_jatuh_ke_bawaan(): void
    {
        Pengaturan::simpan('site.prefix', '');

        $this->assertSame('PPA-ADRO', Pengaturan::prefix());
    }

    /**
     * Janji 2 & 3 — dokumen baru ikut prefix baru, dokumen lama tidak ditulis
     * ulang, dan lencana "Nomor Lama" membaca setelan yang sama.
     */
    public function test_prefix_baru_hanya_mengenai_dokumen_baru(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);

        $lama = $this->draft($gl, $dept, 'SOP Sebelum Ganti Prefix');
        $nomorLama = $lama->doc_number;
        $this->assertStringStartsWith('PPA-ADRO-SOP-', $nomorLama);

        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.penomoran.simpan'), ['prefix' => 'PPA-MSW', 'nama_site' => 'MSW UTAMA'])
            ->assertRedirect();

        // Nomor yang sudah tersimpan TIDAK ditulis ulang.
        $this->assertSame($nomorLama, $lama->fresh()->doc_number);

        // Dokumen berikutnya lahir dengan prefix baru…
        $baru = $this->draft($gl, $dept, 'SOP Sesudah Ganti Prefix');
        $this->assertStringStartsWith('PPA-MSW-SOP-', $baru->doc_number);

        // …dan TIDAK dicap "Nomor Lama" — inti jebakan yang dijaga test ini.
        $this->assertFalse($baru->nomorLuarPola());

        // Sebaliknya dokumen berprefix lama memang kini di luar pola berlaku.
        // Itu perilaku yang disengaja & diperingatkan di layarnya, bukan bug.
        $this->assertTrue($lama->fresh()->nomorLuarPola());
    }

    /**
     * Nama site mengalir sampai ke NAMA BERKAS daftar induk.
     *
     * Diuji lewat rute ekspornya, bukan berhenti di `namaSite()`, karena justru
     * di situ letak risikonya: judulnya dirakit di controller, dan DocumentExportTest
     * yang biasanya menjaganya sedang tak bisa dijalankan (§3 — akun NRP lama).
     * Aktornya dibuat sendiri supaya test ini tetap hidup.
     */
    public function test_nama_site_dipakai_judul_daftar_induk(): void
    {
        $gl = $this->orang(User::JABATAN_GROUP_LEADER);

        // Bawaan dulu: nama berkas harus PERSIS seperti sebelum Fase 5a.
        $this->actingAs($gl)->get(route('documents.export', ['type' => 'SOP']))
            ->assertOk()
            ->assertHeader('content-disposition',
                'attachment; filename="DAFTAR INDUK DOKUMEN SOP PPA SITE ADARO INDONESIA.xls"');

        Pengaturan::simpan('site.nama', 'MSW UTAMA');
        $this->assertSame('MSW UTAMA', Pengaturan::namaSite());

        $this->actingAs($gl)->get(route('documents.export', ['type' => 'SOP']))
            ->assertOk()
            ->assertHeader('content-disposition',
                'attachment; filename="DAFTAR INDUK DOKUMEN SOP PPA SITE MSW UTAMA.xls"');
    }

    /** Bentuk prefix dijaga: spasi ditolak, huruf kecil dinaikkan otomatis. */
    public function test_bentuk_prefix_dijaga(): void
    {
        $admin = $this->orang('admin_it');

        $this->actingAs($admin)
            ->put(route('pengaturan.penomoran.simpan'), ['prefix' => 'PPA ADRO', 'nama_site' => 'ADARO INDONESIA'])
            ->assertSessionHasErrors('prefix');

        $this->assertSame('PPA-ADRO', Pengaturan::prefix());   // tak tersimpan

        $this->actingAs($admin)
            ->put(route('pengaturan.penomoran.simpan'), ['prefix' => 'ppa-msw', 'nama_site' => 'msw utama'])
            ->assertSessionHasNoErrors();

        $this->lupakanCache();
        $this->assertSame('PPA-MSW', Pengaturan::prefix());
        $this->assertSame('MSW UTAMA', Pengaturan::namaSite());
    }

    /** Janji 4 — layarnya milik Admin, dan perubahannya ter-audit. */
    public function test_layar_tertutup_bagi_bukan_admin_dan_perubahan_teraudit(): void
    {
        $this->actingAs($this->orang(User::JABATAN_GROUP_LEADER))
            ->get(route('pengaturan.penomoran'))
            ->assertForbidden();

        $admin = $this->orang('admin_it');
        $this->actingAs($admin)->get(route('pengaturan.penomoran'))->assertOk()->assertSee('PPA-ADRO');

        $this->actingAs($admin)->put(route('pengaturan.penomoran.simpan'), [
            'prefix' => 'PPA-MSW', 'nama_site' => 'MSW UTAMA',
        ])->assertRedirect();

        $log = AuditLog::where('action', 'pengaturan.site_diubah')->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('PPA-ADRO', $log->meta_json['sebelum']['prefix']);
        $this->assertSame('PPA-MSW', $log->meta_json['sesudah']['prefix']);
    }
}
