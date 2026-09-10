<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 4 (migrasi shadcn) — tiga halaman tamu pindah dari Blade ke
 * komponen Inertia.
 *
 * Yang dikunci di sini BUKAN rupanya, melainkan yang gampang hilang tanpa jejak
 * saat lapisan tampilan diganti:
 *
 *   1. Ketiga rute benar-benar merender komponen Inertia yang dimaksud, dengan
 *      props yang dibutuhkan halamannya (`departments`, `name`, `status`).
 *   2. Kredensial salah tetap melahirkan galat validasi pada kunci `nrp` —
 *      itulah kunci yang dibaca `Login.tsx`. Kalau kuncinya bergeser, pesannya
 *      lenyap dari layar tanpa satu pun galat.
 *   3. Batas percobaan login (CLAUDE.md §15) TETAP di server dan tak ikut
 *      pindah ke klien.
 *   4. Akun yang belum aktif tetap tertahan di halaman tunggu, dan yang aktif
 *      tetap dilempar ke dashboard.
 */
class AutentikasiInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private const SANDI = 'rahasia-panjang';

    /**
     * Akun uji dengan kata sandi yang DIKETAHUI.
     *
     * Aktor `aktorGl()` dkk. adalah pengguna sungguhan hasil impor produksi —
     * kata sandinya tidak diketahui, jadi tak bisa dipakai menguji `Auth::attempt`.
     */
    private function pendaftar(string $status): User
    {
        return User::create([
            'name' => 'Pendaftar Uji',
            'nrp' => 'UJI-'.Str::upper(Str::random(8)),
            'jabatan' => User::JABATAN_STAFF,
            'department_id' => Department::query()->value('id'),
            'password' => Hash::make(self::SANDI),
            'status' => $status,
        ]);
    }

    public function test_halaman_masuk_merender_komponen_inertia(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('V2/Auth/Login'));
    }

    public function test_halaman_daftar_membawa_daftar_departemen_aktif(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->component('V2/Auth/Register')
                    ->has('departments')
                    ->where(
                        'departments',
                        fn ($d) => collect($d)->isNotEmpty()
                            && collect($d)->every(fn ($x) => isset($x['id'], $x['code'], $x['name'])),
                    ),
            );
    }

    public function test_login_benar_masuk_ke_dashboard(): void
    {
        $orang = $this->pendaftar('active');

        $this->post(route('login.store'), ['nrp' => $orang->nrp, 'password' => self::SANDI])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($orang);
    }

    /**
     * Pesan galat kredensial menempel di kunci `nrp` — kunci yang dibaca
     * `Login.tsx`. Satu pesan untuk NRP maupun kata sandi, supaya halaman ini
     * tak jadi alat menebak NRP mana yang terdaftar.
     */
    public function test_login_salah_melahirkan_galat_di_kunci_nrp(): void
    {
        $orang = $this->pendaftar('active');

        $this->from(route('login'))
            ->post(route('login.store'), ['nrp' => $orang->nrp, 'password' => 'salah-sekali'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('nrp');

        $this->assertGuest();
    }

    /** Batas percobaan (CLAUDE.md §15) tetap dijaga server, bukan klien. */
    public function test_login_dibatasi_setelah_percobaan_beruntun(): void
    {
        $orang = $this->pendaftar('active');
        $kunci = Str::transliterate(Str::lower($orang->nrp).'|127.0.0.1');
        RateLimiter::clear($kunci);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['nrp' => $orang->nrp, 'password' => 'salah-sekali']);
        }

        // Kata sandi kali ini BENAR, dan tetap ditolak — itulah buktinya batas
        // percobaan bekerja di server, bukan sekadar tombol yang dinonaktifkan.
        $this->post(route('login.store'), ['nrp' => $orang->nrp, 'password' => self::SANDI])
            ->assertSessionHasErrors('nrp');

        $this->assertGuest();
        RateLimiter::clear($kunci);
    }

    public function test_pendaftaran_mendarat_di_halaman_tunggu(): void
    {
        $dept = Department::aktif()->firstOrFail();
        $nrp = 'UJI-'.Str::upper(Str::random(8));

        $this->post(route('register.store'), [
            'name' => 'Pendaftar Uji',
            'nrp' => $nrp,
            'department_id' => $dept->id,
            'password' => 'rahasia-panjang',
            'password_confirmation' => 'rahasia-panjang',
        ])->assertRedirect(route('pending'));

        $baru = User::where('nrp', $nrp)->firstOrFail();
        $this->assertSame('pending', $baru->status);

        $this->actingAs($baru)->get(route('pending'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->component('V2/Auth/Pending')
                    ->where('name', 'Pendaftar Uji')
                    ->where('status', 'pending'),
            );
    }

    public function test_halaman_tunggu_menandai_pendaftaran_yang_ditolak(): void
    {
        $ditolak = $this->pendaftar('rejected');

        $this->actingAs($ditolak)->get(route('pending'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page->component('V2/Auth/Pending')->where('status', 'rejected'),
            );
    }

    /** Akun aktif tak punya urusan di halaman tunggu. */
    public function test_akun_aktif_dilempar_dari_halaman_tunggu(): void
    {
        $this->actingAs($this->pendaftar('active'))
            ->get(route('pending'))
            ->assertRedirect(route('dashboard'));
    }

    /** Akun belum aktif tertahan middleware `active`, tak bisa ke dashboard. */
    public function test_akun_belum_aktif_tertahan_di_luar_dashboard(): void
    {
        $this->actingAs($this->pendaftar('pending'))
            ->get(route('dashboard'))
            ->assertRedirect(route('pending'));
    }
}
