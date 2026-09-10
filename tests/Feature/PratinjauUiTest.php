<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Inertia\ResponseFactory;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pemilihan kerangka halaman — pengunci "V1 MATI, V2 satu-satunya".
 *
 * Berkas ini lahir sebagai pengunci mekanisme PRATINJAU (Tranche 1
 * REDESAIN-UI-V2): satu bendera sesi memilih antara pohon lama dan kembaran
 * `V2/*`. Pemilik mematikan V1 pada 2026-09-07, jadi yang dijaga sekarang bukan
 * lagi sakelarnya melainkan KETIADAANNYA — dan tiap janjinya tetap punya cara
 * gagal yang diam:
 *
 *  1. **Sesi TIDAK bisa lagi mengembalikan V1.** Sesi warisan yang masih
 *     menyimpan `ui_v2 => false` (peramban pemilik sesudah pemeriksaan mata,
 *     misalnya) tetap harus mendapat V2. Kalau `PratinjauResponseFactory` suatu
 *     hari membaca sesi lagi, orang itu terkurung di pohon mati tanpa satu pun
 *     galat.
 *
 *  2. **Sesi kosong — pengguna baru di produksi — mendapat V2.**
 *
 *  3. **Halaman tanpa kembaran jatuh ke komponen ASLINYA**, bukan ke galat
 *     "Halaman Inertia tidak ditemukan". Syarat `is_file()` itu tetap jaring
 *     bagi halaman baru yang kembarannya belum lahir.
 *
 *  4. **Rute sakelar benar-benar hilang**, bukan sekadar tombolnya
 *     disembunyikan.
 *
 * Plus dua penjaga yang berdiri sendiri: pohon V2 tak mencampur kit lama, dan
 * himpunan kunci ikon V2 tak boleh menyusut.
 */
class PratinjauUiTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Sesi warisan yang MASIH menyimpan `ui_v2 => false` tetap mendapat V2.
     *
     * Kebalikan persis dari tes yang berdiri di sini sebelum 2026-09-07. Ia
     * tidak dihapus melainkan DIBALIK (§14c): perilaku yang dijaganya memang
     * sengaja dibuang, dan yang berbahaya sekarang justru kembalinya — sesi
     * lama tak dibersihkan saat rute sakelarnya dicabut, jadi pembacaan sesi
     * yang menyelinap masuk lagi akan mengurung orang di pohon mati.
     */
    public function test_sesi_warisan_tak_bisa_mengembalikan_v1(): void
    {
        $this->actingAs($this->aktorGl())
            ->withSession(['ui_v2' => false])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('V2/Dashboard'));
    }

    /**
     * Sesi KOSONG mendapat V2 — bawaan produksi (rencana pra-produksi Fase 0,
     * keputusan K-A).
     *
     * Dipisah dari tes "bendera nyala" di bawahnya justru karena keduanya
     * terlihat sama: yang satu memeriksa sesi yang MENYEBUT `ui_v2 => true`,
     * yang ini memeriksa sesi yang tak menyebut apa pun — persis keadaan
     * pengguna baru di produksi. Cara gagalnya diam: satu pembacaan sesi yang
     * lupa diberi bawaan `true` membuat seluruh produksi balik ke V1 tanpa satu
     * pun galat, cuma halaman yang rupanya lama.
     */
    public function test_bawaan_menggambar_v2(): void
    {
        $this->actingAs($this->aktorGl())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('V2/Dashboard'));
    }

    public function test_bendera_nyala_menggambar_kembaran_v2(): void
    {
        $this->actingAs($this->aktorGl())
            ->withSession(['ui_v2' => true])
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->component('V2/Dashboard'));
    }

    /**
     * Halaman yang belum punya kembaran V2 tetap digambar komponen ASLINYA,
     * bukan galat "Halaman Inertia tidak ditemukan" yang dilempar `app.tsx`.
     *
     * Sampai PATOKAN-GAYA-V2 Fase 5 tes ini menunjuk satu ROUTE sungguhan yang
     * belum bersaudara — `account.info` dulu, lalu `job-executions.index` —
     * dengan janji tertulis: kalau kembarannya lahir, tukar halamannya, jangan
     * hapus janjinya. Fase 4 sudah menukarnya sekali persis begitu.
     *
     * Fase 6 menghabiskan daftarnya: sesudah ketujuh halaman terakhir
     * dikembarkan, `grep "render('…')"` di seluruh `app/Http/Controllers/`
     * TIDAK menyisakan satu pun komponen tanpa berkas `resources/js/pages/V2/`.
     * Tak ada halaman pengganti — bukan karena janjinya dilepas, melainkan
     * karena pekerjaannya selesai.
     *
     * Jadi yang ditunjuk sekarang bukan halaman melainkan MEKANISMENYA
     * langsung: satu nama komponen yang memang tak akan pernah punya kembaran.
     * Syarat (b) `PratinjauResponseFactory` — "kembaran TSX-nya benar-benar ada
     * di disk" — itulah satu-satunya yang diuji di sini, dan ia tetap harus
     * hidup sampai Fase 7 membuang sakelarnya. Cara gagalnya masih sama
     * diamnya: kalau `is_file()` diganti prasangka "pasti sudah ada semua",
     * halaman yang kembarannya belum lahir langsung jadi LAYAR PUTIH.
     *
     * Diperiksa lewat balasan X-Inertia (JSON) karena di situlah nama
     * komponennya benar-benar tertulis; balasan HTML biasa cuma menyematkannya
     * di atribut `data-page`.
     */
    public function test_halaman_tanpa_kembaran_jatuh_ke_komponen_asli(): void
    {
        $this->actingAs($this->aktorGl());

        $this->assertFileDoesNotExist(
            resource_path('js/pages/V2/KomponenTanpaKembaran.tsx'),
            'Nama sengaja dipilih supaya tak pernah berkembaran; kalau berkasnya ada, tes ini berhenti menguji apa pun.',
        );

        $balasan = app(ResponseFactory::class)
            ->render('KomponenTanpaKembaran')
            ->toResponse(Request::create('/', 'GET', server: ['HTTP_X_INERTIA' => 'true']));

        $this->assertSame('KomponenTanpaKembaran', $balasan->getData(true)['component']);
    }

    /**
     * Rute sakelar `pratinjau.toggle` benar-benar DICABUT, bukan sekadar
     * tombolnya dilepas dari topbar.
     *
     * Dulu tes ini menguji sakelarnya bekerja & menolak tamu. Ia dibalik, bukan
     * dihapus (§14c): menyembunyikan tombol bukan mencabut jalan masuk, dan
     * rute POST yang tertinggal tetap bisa ditembak siapa pun yang tahu namanya.
     * Diperiksa lewat TABEL RUTE — menembak URL-nya saja tak membedakan "rute
     * hilang" dari "rute ada tapi kebetulan menolak".
     */
    public function test_rute_sakelar_pratinjau_sudah_dicabut(): void
    {
        $this->assertNull(
            app('router')->getRoutes()->getByName('pratinjau.toggle'),
            'Rute pratinjau.toggle masih terdaftar — V1 belum benar-benar mati.',
        );

        $this->actingAs($this->aktorGl())->post('/pratinjau/0')->assertNotFound();
    }

    /**
     * Halaman login TETAP V2 sesudah logout — dari sesi mana pun.
     *
     * Tes ini dulu (REVISI-UI-V3 Fase 2b) menjaga bendera pratinjau menyeberangi
     * `session()->invalidate()`, sebab tanpa itu `V2/Auth/Login` mustahil
     * dicapai lewat peramban. Sejak V1 mati, tiga baris pembawa bendera itu
     * dicabut dari `LoginController::destroy()` — dan yang dijaga jadi
     * kebalikannya: logout TIDAK BOLEH bisa menjatuhkan siapa pun kembali ke
     * login lama, termasuk dari sesi warisan yang menyimpan `ui_v2 => false`.
     *
     * Cara gagalnya tetap sama diamnya: bukan galat, bukan 500, cuma halaman
     * login berupa lama dan orang mengira pekerjaannya belum selesai.
     */
    public function test_login_tetap_v2_sesudah_logout(): void
    {
        $this->actingAs($this->aktorGl())
            ->withSession(['ui_v2' => true])
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page->component('V2/Auth/Login'));

        $this->actingAs($this->aktorGl())
            ->withSession(['ui_v2' => false])
            ->post(route('logout'));

        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page->component('V2/Auth/Login'));
    }

    /**
     * PATOKAN-GAYA-V2 §2 — pohon V2 tak boleh mencampur kit lama.
     *
     * Bukan soal rupa. `ui/tooltip` dan `ui-maia/tooltip` adalah dua MODUL,
     * jadi dua React context — dan Radix MELEMPAR, bukan diam, bila Root
     * dipakai di luar Provider-nya. Satu impor yang tersasar cukup untuk
     * membuat SELURUH halaman jadi putih kosong. Itu persis kegagalan yang
     * melahirkan `smartpro:uji-render` di Fase 3.
     *
     * Penjaga ini lahir di Fase 0 justru SEBELUM 35 halaman digarap: selama
     * cuma ada enam halaman V2, mata masih sanggup; sesudah 41, tidak. Dan
     * pencampuran kit adalah kelas kesalahan yang paling mudah menyelinap saat
     * seseorang menyalin halaman V1 lalu lupa menukar satu barisnya.
     *
     * Yang diperiksa IMPOR (`from '…'`), bukan penyebutan: dua komentar di
     * pohon V2 memang menyebut `lucide-react` untuk menjelaskan apa yang
     * digantikan, dan komentar tak pernah bisa membuat layar putih.
     */
    public function test_pohon_v2_tak_mencampur_kit_lama(): void
    {
        $pelanggaran = [];

        foreach (['js/components/v2', 'js/pages/V2', 'js/layouts/V2'] as $pohon) {
            foreach ($this->berkasTsxRekursif(resource_path($pohon)) as $berkas) {
                $isi = (string) file_get_contents($berkas);
                $nama = str_replace(resource_path().DIRECTORY_SEPARATOR, '', $berkas);

                if (preg_match("#from '[^']*lucide-react'#", $isi)) {
                    $pelanggaran[] = "{$nama} mengimpor lucide-react (pakai @hugeicons/*)";
                }

                if (preg_match("#from '@/components/ui/#", $isi)) {
                    $pelanggaran[] = "{$nama} mengimpor kit nova @/components/ui/ (pakai ui-maia)";
                }
            }
        }

        $this->assertSame([], $pelanggaran, implode("\n", $pelanggaran));
    }

    /**
     * Himpunan kunci `v2/Ikon.tsx` ⊇ SELURUH kunci `bi-*` yang dikirim SERVER.
     *
     * Tanpa penjaga ini, satu entri yang terlewat jatuh DIAM-DIAM ke lingkaran
     * netral — tak ada galat, tak ada tes merah, cuma ikon yang berubah jadi
     * bulatan kosong dan tak seorang pun tahu sampai ada yang membandingkan dua
     * tangkapan layar. Itu kelas kegagalan yang paling mahal di proyek ini.
     *
     * Pembandingnya DULU `components/Ikon.tsx` (peta V1). Pohon V1 dihapus
     * 2026-09-07, jadi tes ini kehilangan sisi kirinya — dan ia DIALIHKAN, bukan
     * dihapus (§14c): perilaku yang dijaganya tak hilang sedikit pun, cuma
     * sumber kebenarannya yang naik satu tingkat ke tempat yang seharusnya sejak
     * awal. Kunci `bi-*` memang tak pernah dikarang TSX; ia cerminan
     * `Document::STATUS_META`, `AuditLog::AKSI`, `NavigasiSidebar`,
     * `AntreanTugas`, dan kolom `informasi_kategori.ikon` — semuanya PHP.
     *
     * Menyapu `app/` + `database/seeders/` dengan regex, bukan memanggil tiap
     * konstantanya satu per satu: daftar konstanta yang diketik tangan di sini
     * akan tertinggal persis pada hari sebuah konstanta baru lahir, dan itulah
     * hari penjaga ini paling dibutuhkan.
     */
    public function test_peta_ikon_v2_memuat_seluruh_kunci_yang_dikirim_server(): void
    {
        $dariServer = [];

        foreach ([app_path(), database_path('seeders')] as $pohon) {
            foreach ($this->berkasPhpRekursif($pohon) as $berkas) {
                preg_match_all("/'(bi-[a-z0-9-]+)'/", (string) file_get_contents($berkas), $m);
                $dariServer = array_merge($dariServer, $m[1]);
            }
        }

        $dariServer = array_values(array_unique($dariServer));
        sort($dariServer);

        $v2 = $this->kunciIkon(resource_path('js/components/v2/Ikon.tsx'));

        $this->assertNotEmpty($dariServer, 'Sapuan kunci bi-* di app/ terbaca kosong — polanya berubah?');
        $this->assertSame([], array_values(array_diff($dariServer, $v2)),
            'Ada kunci bi-* yang dikirim server tapi tak punya glif di components/v2/Ikon.tsx — ikonnya jadi bulatan kosong.');
    }

    /**
     * Tranche 2 — halaman BERPARAMETER ikut memilih kembarannya.
     *
     * Ketiga halaman ini luput dari `smartpro:uji-render` tanpa `--uri`
     * (`/documents/{document}/edit` dan `/review/{document}` berparameter),
     * jadi merekalah yang paling butuh pengunci di sini.
     *
     * Bendera MATI ikut diperiksa untuk tiap rute, bukan sekali saja di
     * `test_bendera_mati_...`: yang berbahaya bukan pratinjau yang tak menyala
     * melainkan pratinjau yang menyala pada peran yang TIDAK memintanya.
     */
    public function test_wizard_dan_layar_tinjau_memilih_kembaran_v2(): void
    {
        $gl = $this->berprofilPenuh($this->aktorGl());
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Pratinjau T2'
        );

        $sh = $this->aktorSh();
        $tinjau = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Pratinjau T2 Tinjau'
        );
        $tinjau->update(['reviewer_id' => $sh->id, 'approver_id' => $this->aktorPjo()->id, 'status' => 'in_review']);

        $layar = [
            ['aktor' => $gl, 'url' => route('documents.create', ['type' => 'SOP']), 'komponen' => 'Documents/Create'],
            ['aktor' => $gl, 'url' => route('documents.edit', $doc), 'komponen' => 'Documents/Edit'],
            ['aktor' => $sh, 'url' => route('review.show', $tinjau), 'komponen' => 'Review/Show'],
        ];

        foreach ($layar as ['aktor' => $aktor, 'url' => $url, 'komponen' => $komponen]) {
            // KEDUA arah bendera warisan diperiksa, dan keduanya kini wajib
            // menggambar V2: wizard dan layar tinjau adalah dua halaman paling
            // rumit di aplikasi, jadi merekalah yang paling mahal kalau seseorang
            // diam-diam mengembalikan pembacaan sesi.
            foreach ([false, true] as $warisan) {
                $this->actingAs($aktor)->withSession(['ui_v2' => $warisan])->get($url)->assertOk()
                    ->assertInertia(fn (Assert $page) => $page->component("V2/{$komponen}")->etc());
            }
        }
    }

    /**
     * Nol berkas YATIM di pohon wizard & layar tinjau.
     *
     * Tes ini dulu membandingkan DAFTAR BERKAS `components/dokumen/fields` &
     * `components/tinjau` dengan kembaran V2-nya, dan cara gagal yang dijaganya
     * adalah: seseorang menambah `fields/Sesuatu.tsx`, memakainya di pohon V1,
     * lalu lupa pohon V2. Pohon V1 dihapus 2026-09-07, jadi tak ada lagi sisi
     * kiri untuk dibandingkan.
     *
     * Ia DIALIHKAN, bukan dihapus (§14c) — cara gagalnya masih ada, cuma
     * arahnya membalik. Yang mungkin sekarang: berkas field lahir (atau
     * TERTINGGAL sesudah pemakainya diubah) tanpa satu pun pengimpor, dan
     * akibatnya sama diamnya seperti dulu — satu tipe seksi tak pernah
     * tergambar, tanpa galat, sebab `section.type` yang tak dikenal memang
     * sengaja dilewati alih-alih melempar. `tsc` buta terhadapnya: berkas yang
     * tak diimpor siapa pun tetap berkas yang sah, dan Vite pun tak
     * memaketkannya sehingga `npm run build` ikut diam.
     *
     * Sapuan pengimpornya REKURSIF atas seluruh pohon V2 — komponen, halaman,
     * layout — sebab field dipakai dari tiga tempat berbeda: peta
     * `fields/index.tsx`, `RepeatableGroup` (field bersarang), dan halaman
     * wizard langsung.
     *
     * `index.tsx` dan `konteks.tsx` dikecualikan: keduanya memang bukan
     * komponen yang dipanggil dengan namanya.
     */
    public function test_nol_berkas_yatim_di_pohon_wizard_dan_tinjau(): void
    {
        $sumber = '';

        foreach (['js/components/v2', 'js/pages/V2', 'js/layouts/V2'] as $pohon) {
            foreach ($this->berkasTsxRekursif(resource_path($pohon)) as $berkas) {
                $sumber .= (string) file_get_contents($berkas);
            }
        }

        foreach (['dokumen/fields', 'tinjau'] as $pohon) {
            $dir = resource_path("js/components/v2/{$pohon}");

            $berkas = array_values(array_diff(
                array_map(fn (string $b) => basename($b, '.tsx'), $this->berkasTsx($dir)),
                ['index', 'konteks'],
            ));

            $this->assertNotEmpty($berkas, "Pohon components/v2/{$pohon} terbaca kosong — pindah tempat?");

            foreach ($berkas as $nama) {
                // Dihitung DUA kali: berkasnya sendiri selalu menyebut namanya
                // (deklarasi `export function`), jadi satu penyebutan berarti
                // NOL pengimpor.
                $this->assertGreaterThan(1, substr_count($sumber, $nama),
                    "components/v2/{$pohon}/{$nama}.tsx tak diimpor siapa pun — berkas yatim.");
            }
        }

    }

    /**
     * Seluruh `.tsx` di bawah `$dir`, sampai ke anak cucunya.
     *
     * Terpisah dari `berkasTsx()` yang sengaja SATU tingkat: yang itu
     * membandingkan DAFTAR nama berkas antar-pohon kembar, sedangkan yang ini
     * menyapu isi. Pohon V2 bersarang (`v2/dasbor`, `v2/dokumen/fields`,
     * `pages/V2/Documents`), jadi sapuan satu tingkat akan melewatkan justru
     * bagian terbanyaknya dan penjaganya diam-diam berhenti menjaga.
     *
     * @return list<string>
     */
    private function berkasTsxRekursif(string $dir): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $daftar = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $berkas) {
            if ($berkas->isFile() && $berkas->getExtension() === 'tsx') {
                $daftar[] = $berkas->getPathname();
            }
        }

        sort($daftar);

        return $daftar;
    }

    /** @return list<string> */
    private function berkasTsx(string $dir): array
    {
        $daftar = array_map('basename', glob($dir.'/*.tsx') ?: []);
        sort($daftar);

        return $daftar;
    }

    /**
     * Seluruh `.php` di bawah `$dir`, sampai ke anak cucunya.
     *
     * @return list<string>
     */
    private function berkasPhpRekursif(string $dir): array
    {
        $daftar = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $berkas) {
            if ($berkas->isFile() && $berkas->getExtension() === 'php') {
                $daftar[] = $berkas->getPathname();
            }
        }

        sort($daftar);

        return $daftar;
    }

    /** @return list<string> */
    private function kunciIkon(string $jalur): array
    {
        preg_match_all("/'(bi-[a-z0-9-]+)':/", (string) file_get_contents($jalur), $m);

        return array_values(array_unique($m[1]));
    }
}
