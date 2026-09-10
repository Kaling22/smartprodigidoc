<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardRenderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Kartu Ketersediaan Saya memanggil API libur nasional; suite harus
        // jalan tanpa internet.
        Http::preventStrayRequests();
        Http::fake(['api-harilibur.vercel.app/*' => Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);
    }

    /**
     * Dulu `assertSee('Overview Dokumen')` dkk. Sejak halaman ini Inertia,
     * judul kartu tinggal di TSX — yang bisa diperiksa server adalah PROPS
     * yang menghidupinya. Yang dijaga tetap sama: ketiga kartu itu punya data.
     */
    public function test_dashboard_renders_for_active_user(): void
    {
        $u = User::where('status', 'active')->firstOrFail();
        $this->actingAs($u)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Dashboard')
                ->has('tren.hari.labels', 14)      // Overview Dokumen
                ->has('tren.bulan.labels', 8)
                ->has('sebaran')                   // Sebaran Dokumen
                ->has('activities')                // Log Aktivitas
                ->has('hero.aksi')
                ->has('tiles', 4)
            );
    }

    /**
     * KARTU Distribusi kini untuk GL maupun SH/DH; yang membedakan cuma
     * TAUTAN ke halamannya — halaman itu tetap milik yang berwenang
     * menindaklanjuti cakupan rendah. Dua peran diuji karena keduanya jalur
     * kode terpisah di DashboardController::distribusiWidget().
     */
    public function test_kartu_distribusi_untuk_semua_penyusun_tautannya_tidak(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();

        foreach (['group_leader' => false, 'section_head' => true] as $jabatan => $bolehBukaHalaman) {
            $u = User::create([
                'name' => "Uji {$jabatan}", 'nrp' => 'DR-'.substr($jabatan, 0, 3),
                'jabatan' => $jabatan, 'department_id' => $dept->id,
                'password' => bcrypt('x'), 'status' => 'active',
            ]);
            $u->assignRole($jabatan);

            // `bolehBukaHalaman` menggantikan "ada/tak ada URL di HTML":
            // tombol "Lihat semua" kini disembunyikan dari props, bukan dari
            // Blade. Halamannya sendiri tetap dijaga middleware (pakem P4).
            $this->actingAs($u)->get(route('dashboard'))->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('V2/Dashboard')
                    ->has('sebaran')
                    ->has('distribusiWidget')
                    ->where('distribusiWidget.bolehBukaHalaman', $bolehBukaHalaman)
                );
        }
    }

    /**
     * Kalender bisa maju-mundur lewat ?bulan=YYYY-MM, dan masukan di luar
     * rentang wajar (atau ngawur) jatuh kembali ke bulan berjalan — bukan
     * menghitung kalender tahun 9999 yang tak berguna bagi siapa pun.
     */
    public function test_kalender_bisa_ganti_bulan_dan_menolak_masukan_ngawur(): void
    {
        $u = User::where('status', 'active')->firstOrFail();
        $depan = now()->addMonth();

        $this->actingAs($u)->get(route('dashboard', ['bulan' => $depan->format('Y-m')]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kalenderOff.judul', $depan->translatedFormat('F Y')));

        foreach (['9999-12', 'bukan-bulan', now()->subMonths(30)->format('Y-m')] as $ngawur) {
            $this->actingAs($u)->get(route('dashboard', ['bulan' => $ngawur]))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('kalenderOff.judul', now()->translatedFormat('F Y')));
        }
    }

    /**
     * Tiap jenis WAJIB punya ikon Bootstrap Icons — set lain (boxicons/remix
     * milik tema referensi) tak punya fontnya di sini dan hanya jadi kotak
     * kosong. Ini penjaga agar ikon tak pernah dicomot dari set yang salah.
     */
    public function test_rupa_jenis_selalu_ikon_bootstrap(): void
    {
        foreach (['SOP', 'IK', 'SP', 'JSA', 'entah-apa'] as $kode) {
            [$ikon, $warna] = \App\Models\DocumentType::rupa($kode);
            $this->assertStringStartsWith('bi-', $ikon, "ikon {$kode} bukan Bootstrap Icons");
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $warna);
        }
    }
}
