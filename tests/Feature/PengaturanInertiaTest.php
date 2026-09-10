<?php

namespace Tests\Feature;

use App\Http\Requests\SimpanAiRequest;
use App\Models\DocumentType;
use App\Models\InformasiKategori;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 7 — tiga layar Pengaturan & Master Data pindah ke Inertia.
 *
 * Yang dijaga di sini adalah hal-hal yang gagal DIAM-DIAM, dan tak satu pun
 * tertangkap `npm run build`, `tsc --noEmit`, atau `smartpro:uji-render`:
 *
 *  1. **Kunci API tak pernah sampai ke payload.** `Pengaturan::ai()`
 *     mengembalikan kunci dalam bentuk TERBACA. Blade lama aman karena tak
 *     pernah mencetaknya; props Inertia tidak punya kemewahan itu — seluruhnya
 *     tertulis di atribut `data-page` dan terbaca lewat "view source". Ini
 *     kebocoran rahasia, bukan kerapian.
 *  2. **Peta & konstanta datang dari SERVER** (pakem P3/P5). `kolom()` dan
 *     `DocumentType::rupa()` dulu dipanggil dari dalam Blade. Kalau salah
 *     satunya tak ikut dikirim, klien akan menghitungnya sendiri — salinan
 *     aturan yang dilarang CLAUDE.md §4.
 *  3. **Otorisasi tetap di server** (pakem P4). Menyembunyikan menu bukan
 *     otorisasi; ketiga rute wajib tetap 403 bagi yang bukan Admin.
 */
class PengaturanInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    private function bukanAdmin(): User
    {
        return User::role(User::JABATAN_GROUP_LEADER)->firstOrFail();
    }

    /* ======================= Komponen & bentuk props ======================= */

    public function test_penomoran_merender_komponennya(): void
    {
        $this->actingAs($this->admin())
            ->get(route('pengaturan.penomoran'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Pengaturan/Penomoran')
                ->where('prefix', Pengaturan::prefix())
                ->where('namaSite', Pengaturan::namaSite()));
    }

    public function test_master_data_merender_komponennya_dengan_ketiga_daftarnya(): void
    {
        $this->actingAs($this->admin())
            ->get(route('pengaturan.master'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Pengaturan/Master')
                ->has('departemen')
                ->has('jenis')
                ->has('kategori')
                ->has('jumlahInformasi')
                ->has('kolomTersedia')
                ->has('rupa'));
    }

    public function test_sistem_merender_komponennya_dengan_kartu_kesehatan(): void
    {
        $this->actingAs($this->admin())
            ->get(route('pengaturan.sistem'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Pengaturan/Sistem')
                ->has('ai')
                ->has('penyedia')
                ->has('kesehatan.php')
                ->has('kesehatan.dokumen_semua')
                ->has('kesehatan.mailer')
                // Status kedua penyedia AI (pra-produksi Fase 3). Dua baris
                // TETAP, dan kuncinya tak pernah ikut — kartu Kesehatan lah
                // yang menyebut "kunci kosong" tanpa Admin perlu menekan
                // tombol Uji koneksi.
                ->has('kesehatan.ai', 2, fn (Assert $baris) => $baris
                    ->has('label')
                    ->has('provider')
                    ->has('model')
                    ->has('status')));
    }

    /* ============================ Kunci rahasia ============================ */

    /**
     * Janji terpenting layar ini: kunci API tak pernah meninggalkan server
     * dalam bentuk terbaca — TIDAK sebagai props, TIDAK di dalam HTML-nya.
     */
    public function test_kunci_api_tak_pernah_ikut_ke_payload(): void
    {
        Pengaturan::simpanRahasia('ai.key', 'rahasia-utama-1234');
        Pengaturan::simpanRahasia('ai.cadangan.key', 'rahasia-cadangan-5678');
        Pengaturan::lupakanIngatan();

        $respons = $this->actingAs($this->admin())->get(route('pengaturan.sistem'))->assertOk();

        $ai = $this->propsInertia($respons)['ai'];
        $this->assertArrayNotHasKey('key', $ai);
        $this->assertArrayNotHasKey('cadangan_key', $ai);

        $respons->assertDontSee('rahasia-utama-1234')
            ->assertDontSee('rahasia-cadangan-5678');

        // Yang BOLEH sampai ke layar hanya empat aksara terakhirnya.
        $this->assertSame('••••••••1234', $this->propsInertia($respons)['keyTopeng']);
        $this->assertSame('••••••••5678', $this->propsInertia($respons)['cadanganKeyTopeng']);
    }

    /** Penyedia yang ditawarkan layar = daftar tertutup yang benar-benar punya kelasnya. */
    public function test_daftar_penyedia_ai_datang_dari_form_request(): void
    {
        $penyedia = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('pengaturan.sistem'))->assertOk()
        )['penyedia'];

        $this->assertSame(SimpanAiRequest::PENYEDIA, $penyedia);
    }

    /* ===================== Peta & hitungan dari server ===================== */

    /**
     * `kolom()` menyaring `kolom_json` terhadap KOLOM_TERSEDIA. Yang dikirim
     * wajib hasil saringnya, bukan kolom mentahnya — kalau tidak, kotak centang
     * di layar akan mencentang kunci yang tak punya tempat menyimpan isinya.
     */
    public function test_kolom_kategori_sudah_disaring_di_server(): void
    {
        $props = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('pengaturan.master'))->assertOk()
        );

        $this->assertSame(InformasiKategori::KOLOM_TERSEDIA, $props['kolomTersedia']);

        $tersedia = array_keys(InformasiKategori::KOLOM_TERSEDIA);
        $this->assertNotEmpty($props['kategori']);

        foreach ($props['kategori'] as $k) {
            $this->assertArrayHasKey('kolom', $k, 'Kategori dikirim tanpa kolom terhitung.');
            $this->assertArrayNotHasKey('kolom_json', $k, 'Kolom mentah ikut terkirim.');
            $this->assertSame([], array_diff($k['kolom'], $tersedia));
            // Slug tampil (beku), tapi tak pernah bisa disunting dari layar.
            $this->assertArrayHasKey('slug', $k);
        }
    }

    /** Ikon & warna jenis dokumen dari `DocumentType::RUPA`, bukan diketik di TSX (P3). */
    public function test_rupa_jenis_dikirim_utuh_dan_urutan_jenis_mengikutinya(): void
    {
        $props = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('pengaturan.master'))->assertOk()
        );

        $this->assertSame(DocumentType::RUPA, $props['rupa']);

        $kodeTerkirim = array_column($props['jenis'], 'code');
        $urutSeharusnya = array_values(array_intersect(array_keys(DocumentType::RUPA), $kodeTerkirim));

        $this->assertSame($urutSeharusnya, $kodeTerkirim,
            'Urutan jenis harus mengikuti DocumentType::RUPA, bukan urutan id basis data.');

        foreach ($props['jenis'] as $j) {
            $this->assertArrayHasKey('berjalan_count', $j);
            $this->assertArrayHasKey('berlaku_count', $j);
        }
    }

    /**
     * Angka dokumen per departemen ikut dikirim — ia yang membuat kode
     * departemen terkunci di layar, dan yang membuat saklar "Aktif" bisa
     * ditimbang.
     */
    public function test_departemen_membawa_angka_penguncinya(): void
    {
        $props = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('pengaturan.master'))->assertOk()
        );

        $this->assertNotEmpty($props['departemen']);

        foreach ($props['departemen'] as $d) {
            $this->assertArrayHasKey('documents_count', $d);
            $this->assertArrayHasKey('users_count', $d);
            $this->assertIsBool($d['is_active']);
        }
    }

    /* ============================== Otorisasi ============================== */

    /** P4 — tombol yang disembunyikan React bukan otorisasi; middleware `can:` yang menjaga. */
    public function test_ketiga_layar_tertutup_bagi_bukan_admin(): void
    {
        $gl = $this->bukanAdmin();

        foreach (['pengaturan.penomoran', 'pengaturan.master', 'pengaturan.sistem'] as $rute) {
            $this->actingAs($gl)->get(route($rute))->assertForbidden();
        }
    }
}
