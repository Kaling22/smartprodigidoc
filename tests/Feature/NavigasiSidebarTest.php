<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NavigasiSidebar;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Menu sidebar per peran — pengunci Fase 3 (migrasi shadcn).
 *
 * Sidebar pindah dari `layouts/app.blade.php` ke `App\Services\NavigasiSidebar`
 * lalu digambar `AppSidebar.tsx`. Yang berbahaya di perpindahan itu bukan
 * rupanya, melainkan menu yang HILANG DIAM-DIAM untuk satu peran saja: tak ada
 * galat, tak ada tes merah, cuma seseorang yang suatu hari berkata "menu saya
 * tidak ada".
 *
 * Karena itu yang dikunci di sini adalah janji, bukan susunan:
 *
 *   1. Tiap peran melihat menu yang memang haknya.
 *   2. Tiap peran TIDAK melihat menu yang bukan haknya.
 *   3. Aturan yang paling gampang "dirapikan" salah — GL memegang
 *      `user.approve_registration` & `audit.view` tanpa `user.manage`, dan
 *      SH/DH/PJO sengaja TIDAK punya "Status Dokumen" (mereka pakai "Status
 *      Dokumen Staff", CLAUDE.md §6).
 *
 * Aktornya memakai akun sungguhan lewat `aktorGl()` dkk. (lihat TestCase),
 * jadi yang diuji adalah keadaan yang sama dengan yang dipakai pengguna.
 */
class NavigasiSidebarTest extends TestCase
{
    use DatabaseTransactions;

    /** @return list<string> seluruh label menu, induk maupun anak. */
    private function label(User $user): array
    {
        $this->actingAs($user);

        $label = [];

        foreach (app(NavigasiSidebar::class)->untuk($user) as $bagian) {
            foreach ($bagian['items'] as $item) {
                $label[] = $item['label'];

                foreach ($item['items'] ?? [] as $anak) {
                    $label[] = $item['label'].' > '.$anak['label'];
                }
            }
        }

        return $label;
    }

    /**
     * @param  list<string>  $ada
     * @param  list<string>  $tiada
     */
    private function periksa(User $user, array $ada, array $tiada): void
    {
        $label = $this->label($user);

        foreach ($ada as $m) {
            $this->assertContains($m, $label, "Menu '{$m}' hilang untuk peran ".$user->getRoleNames()->first());
        }

        foreach ($tiada as $m) {
            $this->assertNotContains($m, $label, "Menu '{$m}' seharusnya tidak tampil untuk peran ".$user->getRoleNames()->first());
        }
    }

    public function test_group_leader_membuat_dokumen_dan_melihat_status_dokumen_sendiri(): void
    {
        // GL = satu-satunya pembuat, dan "Status Dokumen"-nya berisi DUA
        // submenu: se-departemen (read-only) & buatannya sendiri.
        $this->periksa($this->aktorGl(), [
            'Dashboard',
            'Dokumen Baru',
            'Dokumen Revisi',
            'Status Dokumen',
            'Status Dokumen > Dokumen Departemen',
            'Status Dokumen > Dokumen Saya',
            'Riwayat Pekerjaan',
            'Informasi',
            'Informasi Akun',
        ], [
            // Peninjauan & administrasi penuh bukan miliknya.
            'Tinjau Dokumen',
            'Persetujuan Saya',
            'Manajemen User',
            'Konfigurasi Sistem',
        ]);
    }

    public function test_section_head_meninjau_dan_tak_punya_status_dokumen_sendiri(): void
    {
        // Jebakan yang paling mudah terulang: SH/DH dulu punya DUA menu status
        // yang isinya bertumpang tindih. Yang benar hanya "Status Dokumen Staff".
        $this->periksa($this->aktorSh(), [
            'Tinjau Dokumen',
            'Status Dokumen Staff',
            'Dokumen Berlaku',
            'Dokumen Berlaku > Tidak Berlaku',
            'Log Dokumen > Masukan Lapangan',
        ], [
            'Status Dokumen',
            'Dokumen Baru',
        ]);
    }

    public function test_pimpinan_menyetujui_lintas_tujuh_departemen(): void
    {
        // PJO tanpa departemen: submenu Status Dokumen Staff berisi 7 dept,
        // dan ia TIDAK pernah meninjau (CLAUDE.md §6).
        $pjo = $this->aktorPjo();

        $this->periksa($pjo, [
            'Persetujuan Saya',
            'Status Dokumen Staff',
            'Status Dokumen Staff > ICTMD',
            'Dokumen Berlaku',
        ], [
            'Tinjau Dokumen',
            'Dokumen Baru',
        ]);

        $dept = collect($this->label($pjo))->filter(
            fn (string $l) => str_starts_with($l, 'Status Dokumen Staff > ')
        );

        $this->assertCount(7, $dept, 'PJO harus melihat ketujuh departemen.');
    }

    public function test_non_staff_read_only_tanpa_menu_pembuatan_maupun_administrasi(): void
    {
        $this->periksa($this->aktorNonStaff(), [
            'Dashboard',
            'Status Dokumen',
            'Dokumen Berlaku',
            'Informasi',
            'Informasi Akun',
        ], [
            'Dokumen Baru',
            'Dokumen Revisi',
            'Tinjau Dokumen',
            'Persetujuan Saya',
            'Manajemen User',
            'Audit Log',
            // Non-Staff di luar `dashboardPenuh()`: Log Dokumen tertutup, dan
            // "Dokumen Berlaku" untuknya menu TUNGGAL — tanpa submenu.
            'Log Dokumen',
            'Dokumen Berlaku > Tidak Berlaku',
        ]);
    }

    public function test_admin_memegang_seluruh_menu_pengaturan_sistem(): void
    {
        $this->periksa(User::where('nrp', 'ADM-0001')->firstOrFail(), [
            'Persetujuan Akun',
            'Manajemen User',
            'Manajemen Akses',
            'Penomoran Dokumen',
            'Master Data',
            'Konfigurasi Sistem',
            'Audit Log',
        ], []);
    }

    public function test_izin_terpisah_tidak_saling_menumpang(): void
    {
        // Penjaga aturan yang paling gampang "dirapikan" salah: bagian
        // Pengaturan Sistem ber-canany atas TIGA izin. Kalau suatu hari
        // seseorang menyederhanakannya jadi satu can('user.manage'), pemegang
        // audit.view / user.approve_registration kehilangan menunya diam-diam.
        $gl = $this->aktorGl();

        $this->assertSame(
            $gl->can('user.approve_registration'),
            in_array('Persetujuan Akun', $this->label($gl), true),
            'Menu Persetujuan Akun harus mengikuti izin user.approve_registration, bukan user.manage.',
        );

        $this->assertSame(
            $gl->can('audit.view'),
            in_array('Audit Log', $this->label($gl), true),
            'Menu Audit Log harus mengikuti izin audit.view, bukan user.manage.',
        );
    }

    public function test_menu_jenis_dokumen_mengikuti_profil_akses_bukan_disembunyikan(): void
    {
        // Jenis di luar profil akses tetap TAMPIL dalam keadaan terkunci —
        // ketetapan pemilik: pengguna harus melihat fiturnya ada dan tahu harus
        // meminta akses. Yang hilang sama sekali hanyalah jenis NONAKTIF.
        $gl = $this->aktorGl();
        $this->actingAs($gl);

        $baru = collect(app(NavigasiSidebar::class)->untuk($gl))
            ->flatMap(fn (array $b) => $b['items'])
            ->firstWhere('label', 'Dokumen Baru');

        $this->assertNotNull($baru, 'GL harus punya menu Dokumen Baru.');
        $this->assertNotEmpty($baru['items'], 'Menu Dokumen Baru tak boleh kosong.');

        foreach ($baru['items'] as $jenis) {
            $boleh = $gl->bolehBuatJenis($jenis['label']);

            $this->assertSame(
                $boleh,
                ! ($jenis['locked'] ?? false),
                "Jenis {$jenis['label']} harus terkunci persis saat bolehBuatJenis() menolaknya.",
            );
            $this->assertSame($boleh, $jenis['href'] !== null, "Jenis terkunci tak boleh punya href.");
        }
    }
}
