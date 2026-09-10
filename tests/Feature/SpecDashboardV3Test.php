<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Spec dashboard v3 — yang tak bisa dilihat mata dari kode sumbernya.
 *
 * Bukan tes rupa: CSS tak diuji di sini. Yang dikunci cuma hal-hal yang kalau
 * lepas akan lepas DIAM-DIAM.
 *
 * DITULIS ULANG DI FASE 5 (migrasi Inertia), dan ini perlu dijelaskan supaya
 * tak terbaca sebagai tes yang dilemahkan:
 *
 * Empat asersi lama menyebut markup pustaka yang KINI SUDAH TIDAK ADA —
 * `.btn-rentang` (dropdown Bootstrap), `.pp-naik` (animasi CSS tema lama),
 * `apexcharts` + `<div id="grafikSebaran">` (donat ApexCharts), dan
 * `.pp-gulir-tipis` (batang gulir tema lama). Ketiga pustaka itu (Bootstrap 5,
 * Chart.js, ApexCharts) diganti recharts + komponen registry shadcn, jadi
 * mencocokkan namanya lagi hanya akan menguji ejaan sesuatu yang tak dipakai.
 *
 * Yang DIJAGA tetap sama, karena itulah maksud tiap asersi aslinya:
 *
 *   R5 — judulnya "Overview Dokumen", BUKAN "Ikhtisar Dokumen", dan pemilih
 *        rentangnya melayani TIGA durasi yang seluruhnya ikut halaman (jadi
 *        berganti durasi tak pernah butuh permintaan kedua ke server).
 *   R2 — kartu Sebaran punya data untuk digambar begitu ada dokumen Berlaku;
 *        SATU pustaka grafik saja untuk seluruh halaman.
 *   R3 — daftar Distribusi dibatasi (sepuluh baris) supaya gulir di dalam
 *        kartunya memang terpakai, bukan hiasan.
 *   R1 — tak ada label status yang warnanya diketik di halaman; semuanya dari
 *        `statusMeta` (pakem P3).
 */
class SpecDashboardV3Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Kartu Ketersediaan Saya ikut dirender di halaman yang sama, dan ia
        // memanggil API libur nasional. Suite harus jalan tanpa internet.
        Http::preventStrayRequests();
        Http::fake(['api-harilibur.vercel.app/*' => Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);
    }

    private function kepalaSeksi(): User
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $u = User::create([
            'name' => 'SH Spec v3', 'nrp' => 'SPECV3-1', 'jabatan' => User::JABATAN_SECTION_HEAD,
            'department_id' => $dept->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole(User::JABATAN_SECTION_HEAD);

        return $u;
    }

    /**
     * Dokumen BERLAKU minimal satu — prasyarat yang dulu dipinjam dari isi basis
     * data, bukan dibuat sendiri.
     *
     * Kartu Sebaran merender keadaan kosong ("Belum ada dokumen berlaku untuk
     * dipetakan") selama tak ada satu pun dokumen `published`, dan dalam keadaan
     * itu `<div id="grafikSebaran">` memang TIDAK terbit — benar, bukan cacat.
     * Test ini dulu hijau hanya selama basis data uji KEBETULAN berisi dokumen
     * terbit; begitu isinya kosong ia merah tanpa satu pun kode berubah.
     */
    private function dokumenBerlaku(User $gl): void
    {
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Spec v3'
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);
    }

    public function test_dashboard_mengirim_data_yang_dituju_spec_v3(): void
    {
        $this->dokumenBerlaku($this->aktorGl());

        $respons = $this->actingAs($this->kepalaSeksi())->get(route('dashboard'))->assertOk();
        $props = $this->propsInertia($respons);

        // R5 — KETIGA durasi ikut halaman sekaligus. Inilah janji yang dulu
        // dijaga "dua .btn-rentang": satu keadaan durasi untuk grafik DAN
        // meternya, ditukar tanpa permintaan ke server.
        $this->assertSame(['hari', 'minggu', 'bulan'], array_keys($props['tren']));
        foreach (['hari' => 14, 'minggu' => 8, 'bulan' => 8] as $kunci => $jumlah) {
            $this->assertCount($jumlah, $props['tren'][$kunci]['labels']);
            $this->assertCount($jumlah, $props['tren'][$kunci]['dibuat']);
            $this->assertCount($jumlah, $props['tren'][$kunci]['berlaku']);
            $this->assertArrayHasKey('growth', $props['tren'][$kunci], 'meter Tertinjau membaca deret yang sama');
        }

        // R2 — Sebaran punya isi begitu ada dokumen Berlaku, beserta rupa tiap
        // jenis (ikon + warna dari DocumentType::RUPA) yang dipakai donat DAN
        // tabel Distribusi, sehingga keduanya saling menerjemahkan.
        $this->assertNotEmpty($props['sebaran']);
        $this->assertGreaterThan(0, collect($props['sebaran'])->sum('jumlah'));
        foreach ($props['sebaran'] as $kode => $s) {
            $this->assertStringStartsWith('bi-', $s['ikon'], "ikon jenis {$kode}");
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $s['warna']);
        }

        // R3 — daftar Distribusi dibatasi sepuluh baris; itulah yang membuat
        // gulir di dalam kartunya terpakai alih-alih memanjangkan kartunya.
        $this->assertNotNull($props['distribusiWidget']);
        $this->assertLessThanOrEqual(10, count($props['distribusiWidget']['mutu']['baris'] ?? []));
    }

    /**
     * R1 — audit badge. Gradasi pekat `.badge.bg-*` hanya boleh tersisa pada
     * benda yang memang BUKAN label status (lencana angka lonceng). Kalau satu
     * status kembali bergradasi, tes ini yang memberi tahu, bukan mata.
     *
     * Dashboard sudah pindah ke Inertia dan karena itu diperiksa dengan cara
     * yang setara tapi lebih tegas: warnanya WAJIB datang dari `statusMeta`
     * (pakem P3), bukan dari kelas apa pun yang tertulis di halaman. Tiga rute
     * lainnya masih Blade sampai fase berikutnya, jadi pemindaian gradasi
     * lamanya tetap berjalan di sana — dan pindah sendiri saat rutenya migrasi.
     */
    public function test_warna_status_dashboard_datang_dari_status_meta(): void
    {
        $this->actingAs($this->kepalaSeksi())->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('statusMeta.verifikasi_md')       // yang dulu abu-abu di empat Blade
                ->has('statusLabels.verifikasi_md')
                ->has('statusMeta', 13)
                ->has('statusLabels', 13)
            );
    }

    public function test_tak_ada_badge_bergradasi_di_halaman_berdaftar(): void
    {
        $user = $this->kepalaSeksi();

        foreach (['documents.published', 'documents.staffStatus', 'review.index'] as $rute) {
            $html = (string) $this->actingAs($user)->get(route($rute))->assertOk()->getContent();

            /*
            | Halaman yang SUDAH pindah ke Inertia dilewati dengan sengaja, dan
            | itu bukan celah: markupnya cuma satu atribut `data-page` berisi
            | JSON, sehingga regex di bawah lolos trivial di sana — persis
            | kejadian dashboard di Fase 5. Penggantinya sudah ada dan lebih
            | kuat: DokumenInertiaTest::test_rupa_status_daftar_datang_dari_status_meta
            | mengunci bahwa rupa status memang datang dari `Document::STATUS_META`,
            | bukan sekadar bahwa ia tidak bergradasi.
            */
            if (str_contains($html, 'data-page="app"')) {
                continue;
            }

            // Lencana lonceng sengaja dikecualikan: ia hitungan yang memang
            // harus menonjol, bukan label status.
            $tanpaLonceng = str_replace('badge rounded-pill bg-danger pp-navbtn-lencana', '', $html);

            foreach (['bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-secondary'] as $rona) {
                $this->assertDoesNotMatchRegularExpression(
                    '/class="badge[^"]*\b'.$rona.'\b/',
                    $tanpaLonceng,
                    "Halaman `{$rute}` masih memuat badge bergradasi .{$rona} — "
                    .'spec v3 R1 menuntut .badge-soft tanpa kecuali.'
                );
            }
        }
    }
}
