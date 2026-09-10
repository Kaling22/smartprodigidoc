<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 6 — enam halaman Pengguna & Akses pindah ke Inertia.
 *
 * Yang dijaga di sini BUKAN rupa halaman melainkan tiga hal yang gagal
 * diam-diam kalau salah, dan tak satu pun tertangkap `npm run build`,
 * `tsc --noEmit`, maupun `smartpro:uji-render`:
 *
 *  1. **Komponen yang dirender** — satu rute, satu halaman TSX (CLAUDE.md §3).
 *     Salah nama komponen hanya terlihat sebagai layar kosong di peramban.
 *  2. **Bentuk paginator utuh** — `->through()` memetakan isi halaman TANPA
 *     membongkar `data`/`links`/`from`/`to`/`total`. Kalau seseorang kelak
 *     menggantinya dengan `->map()`, paginasinya lenyap begitu saja: daftarnya
 *     tetap tampil, hanya tombol halaman 2 yang tak pernah muncul lagi.
 *  3. **Model mentah tak pernah dikirim** — `password` dan `remember_token`
 *     ikut terbawa begitu sebuah controller mengirimkan Eloquent apa adanya
 *     (lihat komentar UserResource). Payload Inertia terbaca siapa pun yang
 *     membuka "view source".
 */
class PenggunaInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    public function test_daftar_user_merender_komponennya_dengan_paginator_utuh(): void
    {
        $this->actingAs($this->admin())
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Users/Index')
                // Kelima kunci ini adalah bentuk paginator Laravel yang dibaca
                // komponen `Paginasi`. Hilang satu = navigasi halaman mati.
                ->has('users.data')
                ->has('users.links')
                ->has('users.total')
                ->has('users.from')
                ->has('users.to')
                ->has('departments')
                ->has('akunMd')
                ->has('filters')
                ->has('roleLabels'));
    }

    /**
     * Label peran dikirim sebagai peta UTUH, bukan ditempelkan per baris.
     *
     * Kalau ia ikut baris, halaman yang kebetulan tak memuat seorang Non-Staff
     * pun akan kehilangan ejaan itu dari payload — dan `LabelJabatanTest`,
     * penjaga aturan CLAUDE.md §6, jadi hijau tanpa memeriksa apa pun.
     */
    public function test_peta_label_peran_selalu_lengkap(): void
    {
        $peta = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('users.index'))->assertOk()
        )['roleLabels'];

        $this->assertSame(User::ROLE_LABELS, $peta);
        $this->assertSame('Non-Staff', $peta[User::JABATAN_STAFF]);
    }

    /** Penyaring departemen menyaring DI SERVER — bukan di klien (pakem P5). */
    public function test_penyaring_departemen_menyaring_di_server(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();

        $baris = collect($this->propsInertia(
            $this->actingAs($this->admin())
                ->get(route('users.index', ['department_id' => $dept->id]))
                ->assertOk()
        )['users']['data']);

        $this->assertNotEmpty($baris, 'prasyarat: ICTMD punya pengguna');
        $this->assertSame(['ICTMD'], $baris->pluck('dept')->unique()->values()->all());
    }

    public function test_form_buat_dan_ubah_akun_merender_komponennya(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Users/Create')
                ->has('departments')
                ->has('roles')
                ->where('roleLabels', User::ROLE_DESKRIPSI));

        $sasaran = $this->peninjauBersih($this->aktorGl());

        $this->actingAs($admin)
            ->get(route('users.edit', $sasaran))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Users/Edit')
                ->where('user.id', $sasaran->id)
                ->where('peranSekarang', 'group_leader')
                // Sandi TIDAK pernah ikut, walau propsnya bernama `user`.
                ->missing('user.password')
                ->missing('user.remember_token'));
    }

    public function test_persetujuan_akun_merender_komponennya(): void
    {
        $this->actingAs($this->aktorPjo())
            ->get(route('users.pending'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Users/Pending')
                ->has('pendingUsers'));
    }

    public function test_manajemen_akses_merender_komponennya(): void
    {
        $this->actingAs($this->admin())
            ->get(route('akses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Akses/Index')
                ->has('profiles')
                ->has('groupLeaders.data')
                ->has('groupLeaders.links')
                ->has('departments')
                ->has('filters')
                ->has('jenis')
                // Ikon & warna jenis datang dari `DocumentType::RUPA`; kalau
                // hilang, TSX akan tergoda menuliskan hex-nya sendiri —
                // kesalahan yang sudah mahal dibayar `STATUS_META` (P3).
                ->where('rupa', \App\Models\DocumentType::RUPA));
    }

    public function test_informasi_akun_merender_komponennya_tanpa_membocorkan_sandi(): void
    {
        $orang = $this->aktorNonStaff();

        $this->actingAs($orang)
            ->get(route('account.info'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Account/Info')
                ->where('user.nrp', $orang->nrp)
                ->where('user.jabatan_label', 'Non-Staff')
                ->missing('user.password')
                ->missing('user.remember_token')
                // Kunci mentah `jabatan` sengaja tak ikut sama sekali —
                // yang tak dikirim mustahil tercetak (CLAUDE.md §6).
                ->missing('user.jabatan'));
    }
}
