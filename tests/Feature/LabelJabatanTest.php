<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kunci internal jabatan tak boleh pernah sampai ke layar.
 *
 * Nilai `users.jabatan` sengaja TIDAK ikut di-rename jadi non-staff
 * ({@see User::JABATAN_STAFF} — di PPA, GL/SH/DH itulah staff; "Non-Staff" =
 * teknisi/magang/helper). Konsekuensinya seluruh tampilan WAJIB lewat
 * JABATAN_LABELS / jabatanLabel() / jabatanShort(); begitu ada satu tempat yang
 * mencetak `$user->jabatan` mentah, pengguna akan membaca "staff" untuk orang
 * yang justru berlabel "Non-Staff" — persis kebalikan artinya.
 *
 * Pola `'>staff<'` (bukan sekadar `'staff'`) dipakai supaya yang ditangkap
 * hanyalah kunci yang tercetak sebagai TEKS elemen. Kata "staff" sendiri sah
 * muncul di tempat lain — nama route `documents.staffStatus`, judul menu
 * "Status Dokumen Staff", dan `role_filter` di dalam schema JSON — dan
 * assertion yang lebih longgar akan merah karenanya tanpa ada yang salah.
 */
class LabelJabatanTest extends TestCase
{
    use DatabaseTransactions;

    /** Kunci mentah yang tak boleh tampil sebagai teks elemen. */
    private const KUNCI_MENTAH = ['>staff<', '>group_leader<', '>section_head<', '>departemen_head<', '>pimpinan<'];

    private function takAdaKunciMentah(\Illuminate\Testing\TestResponse $res): void
    {
        foreach (self::KUNCI_MENTAH as $kunci) {
            $res->assertDontSee($kunci, false);
        }
    }

    /** Dashboard tiap jabatan menampilkan label, bukan kunci internalnya. */
    public function test_dashboard_tak_pernah_mencetak_kunci_jabatan(): void
    {
        /*
        | Dulu berisi tujuh NRP contoh (`GL-0001`, `SH-0001`, …) yang dilewati
        | diam-diam lewat `continue` bila tak ditemukan. Sesudah akun contoh
        | pensiun (PLAN-AKSES-v8 keputusan I), LIMA dari tujuh tak ada lagi —
        | jadi test ini hijau tanpa memeriksa apa pun, dan aturan CLAUDE.md §6
        | yang seharusnya dijaganya berjalan tanpa penjaga.
        |
        | Sekarang aktornya diambil dari tabel terpusat `TestCase::NRP_AKTOR`
        | (NRP pengguna sungguhan), dan `firstOrFail()` di dalamnya membuat akun
        | yang hilang MERAH, bukan terlewat. `ADM-0001`/`MD-0001` tetap dicari
        | apa adanya: keduanya akun BERSAMA yang memang tak punya NRP karyawan,
        | dan keduanya benar-benar ada di produksi.
        */
        $aktor = [
            $this->aktorNonStaff(),
            $this->aktorGl(),
            $this->aktorSh(),
            $this->aktorDh(),
            $this->aktorPjo(),
            User::where('nrp', 'ADM-0001')->firstOrFail(),
            User::peninjauMd()->firstOrFail(),
        ];

        foreach ($aktor as $user) {
            $res = $this->actingAs($user)->get(route('dashboard'))->assertOk();
            $this->takAdaKunciMentah($res);
        }
    }

    /** Halaman akun & daftar user memakai label yang terbaca manusia. */
    public function test_halaman_akun_dan_daftar_user_memakai_label(): void
    {
        $nonStaff = $this->aktorNonStaff();

        $akun = $this->actingAs($nonStaff)->get(route('account.info'))->assertOk()->assertSee('Non-Staff');
        $this->takAdaKunciMentah($akun);

        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $daftar = $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee('Non-Staff');
        $this->takAdaKunciMentah($daftar);
    }

    /**
     * Satu tujuan, satu nama. Tombol dashboard dan menu sidebar dulu berbeda
     * ejaan ("Status Dokumen Staf" vs "…Staff") padahal menunjuk rute yang sama.
     *
     * Diperiksa dengan regex "Staf yang tidak diikuti f", BUKAN
     * assertSee/assertDontSee: menu sidebar SELALU memuat ejaan yang benar, jadi
     * assertSee('…Staff') tetap hijau walau tombolnya salah eja — assertion
     * seperti itu tak pernah bisa merah dan karena itu tak membuktikan apa pun.
     */
    public function test_penamaan_status_dokumen_staff_konsisten(): void
    {
        $dh = $this->aktorDh();

        $html = $this->actingAs($dh)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Status Dokumen Staff', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/Status Dokumen Staf(?!f)/',
            $html,
            'Ada tempat yang masih mengeja "Status Dokumen Staf" — satu rute tak boleh punya dua nama.',
        );
    }
}
