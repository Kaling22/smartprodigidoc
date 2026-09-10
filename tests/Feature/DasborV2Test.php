<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DasborTampilan;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use ReflectionClass;
use Tests\TestCase;

/**
 * Lapisan DATA dashboard V2 (DASBOR-V2-REVISI §9a).
 *
 * Yang dikunci di sini BUKAN rupa halaman — enam widget V2 belum digambar saat
 * berkas ini ditulis, dan mengunci tata letak yang belum ada cuma menghasilkan
 * tes yang harus dihapus minggu depan. Yang dikunci adalah JANJI PROPS-nya,
 * karena props itulah yang dibagi V1 dan V2 (§P2): perubahan server di sini
 * hanya boleh ADITIF, dan setiap kunci baru punya cara gagal yang senyap.
 *
 * Empat kelas kegagalan yang dijaga:
 *
 *  1. **Kebocoran.** Tiap fase migrasi nyaris membocorkan satu model — dan dua
 *     kali terakhir yang bocor bahkan bukan `User` melainkan jalur berkas
 *     privat (CLAUDE.md §4). Di sini `menungguDiMeja` dulu mengirim model
 *     `Document` UTUH beserta relasi `creator`, dan kartu Performa PIC yang
 *     baru menampilkan WAJAH dan NAMA orang. Keduanya diperiksa pada HTML
 *     MENTAH, bukan pada props yang sudah diurai — atribut `data-page` itulah
 *     yang benar-benar terkirim ke peramban.
 *
 *  2. **Lencana yang berbohong.** Lencana kenaikan hanya boleh berdiri di atas
 *     aksi audit yang sungguh mewakili kartunya (§P2b/§P9). Aksi salah ketik
 *     tidak membuat apa pun merah — lencananya cuma diam di nol selamanya —
 *     jadi penjaganya harus berupa tes, bukan mata.
 *
 *  3. **Aturan yang disalin ke klien.** `boleh_balas` dan `arah` wajib datang
 *     JADI dari server. Kalau keduanya benar di sini tapi TSX menghitungnya
 *     sendiri, yang rusak adalah otorisasi, bukan tampilan (CLAUDE.md §4).
 *
 *  4. **Perilaku V1 yang ikut berubah.** `menungguDiMeja` tetap 4 baris, dan
 *     `tiles` tetap 4 kartu. Keduanya sudah dijaga `DashboardWidgetTest` /
 *     `DashboardRenderTest`; yang di sini menjaga arah sebaliknya — bahwa
 *     penambahan kunci tidak diam-diam menggeser jumlahnya.
 */
class DasborV2Test extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Kartu Ketersediaan Saya ikut dirender di halaman yang sama dan ia
        // memanggil API libur nasional. Suite harus jalan tanpa internet.
        Http::preventStrayRequests();
        Http::fake(['api-harilibur.vercel.app/*' => Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);
    }

    /** Props dashboard sebagai `$aktor`, sudah terurai. */
    private function props(User $aktor): array
    {
        $data = null;
        $this->actingAs($aktor)->get(route('dashboard'))->assertOk()
            ->assertInertia(function (Assert $page) use (&$data) {
                $data = $page->toArray()['props'];
            });

        return $data;
    }

    /** Satu kartu KPI menurut labelnya. */
    private function tile(array $props, string $label): ?array
    {
        return collect($props['tiles'])->firstWhere('label', $label);
    }

    /** Dokumen berjalan milik `$gl`, supaya `menungguDiMeja` tak pernah kosong. */
    private function kirimkan(User $gl, string $judul): Document
    {
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
        $doc->update(['status' => 'waiting_for_review', 'submitted_at' => now()->subDays(5)]);

        return $doc->fresh();
    }

    /**
     * 1 · Sebaran per departemen: HANYA PJO, MD, dan Admin IT.
     *
     * Gerbangnya `can('document.view_all')` — ketetapan pemilik 1 September
     * 2026 ("sebaran dokumen per dept hanya ada di MD dan PJO", REVISI-UI-V3
     * §4.2), yang MENCABUT gerbang `dashboardPenuh()` lama. GL dan SH/DH kini
     * nihil bersama Non-Staff: kartu berisi tujuh departemen cuma berarti bagi
     * yang berwenang atas ketujuhnya.
     *
     * Admin IT ikut karena ia memegang izin yang sama, dan itu memang lensa
     * yang dipakai seluruh controller ini untuk "lintas tujuh departemen".
     *
     * Jumlah barisnya dibandingkan dengan `Department::count()`, bukan angka 7
     * yang ditulis di sini: departemen adalah data, dan tes yang memaku
     * jumlahnya akan merah pada hari departemen kedelapan dibuat — merah yang
     * tak menunjukkan satu pun cacat.
     */
    public function test_sebaran_departemen_gerbang_dan_bentuknya(): void
    {
        // Ketiganya nihil, dan ketiganya harus disebut: GL & SH baru kehilangan
        // kartunya di V3, jadi merah di sini adalah gerbang yang mundur.
        foreach ([
            'Non-Staff' => $this->aktorNonStaff(),
            'GL' => $this->aktorGl(),
            'SH' => $this->aktorSh(),
        ] as $peran => $aktor) {
            $this->assertNull($this->props($aktor)['sebaranDepartemen'],
                "{$peran} tak boleh menerima sebaran lintas departemen (REVISI-UI-V3 §4.2).");
        }

        foreach (['PJO' => $this->aktorPjo(), 'MD' => $this->aktorMd(), 'Admin' => $this->aktorAdmin()] as $peran => $aktor) {
            $sebaran = $this->props($aktor)['sebaranDepartemen'];

            $this->assertNotNull($sebaran, "{$peran} seharusnya menerima sebaranDepartemen.");
            $this->assertCount(Department::count(), $sebaran['baris'],
                "Departemen ber-NOL dokumen wajib tetap muncul ({$peran}) — jumlah kotak legenda tak boleh berubah dari hari ke hari.");
            $this->assertSame(
                array_keys($sebaran['baris'][0]),
                ['kode', 'nama', 'jumlah', 'persen', 'per'],
                'Bentuk baris sebaran berubah — TSX membacanya kolom per kolom.'
            );
            // F3 DASBOR-V4: `per` (tumpukan bar per JENIS) wajib berjumlah
            // sama dengan `jumlah` baris itu sendiri — bar bertumpuk yang
            // segmennya tak menjumlah ke total batangnya adalah bar yang bohong.
            $this->assertSame(
                array_sum($sebaran['baris'][0]['per']),
                $sebaran['baris'][0]['jumlah'],
                'Jumlah `per` (per jenis) tak sama dengan `jumlah` baris — tumpukan bar tak akan setinggi labelnya.'
            );

            // URUT MENURUN: ramp `--primary` di V2 memakai peringkat ini untuk
            // memilih opasitas, jadi urutannya BAGIAN DARI DATA, bukan selera
            // penggambaran (§P5).
            $jumlah = array_column($sebaran['baris'], 'jumlah');
            $urut = $jumlah;
            rsort($urut);
            $this->assertSame($urut, $jumlah, "Baris sebaran {$peran} tidak urut menurun.");

            $this->assertSame(array_sum($jumlah), $sebaran['total'], 'Total sebaran tak sama dengan jumlah barisnya.');

            if ($sebaran['total'] > 0) {
                $this->assertEqualsWithDelta(100, array_sum(array_column($sebaran['baris'], 'persen')), 2,
                    'Persen sebaran tak menjumlah ~100 — pembulatan per baris boleh meleset 1–2, bukan lebih.');
            }
        }

        // Angkanya jujur: totalnya sama dengan hitungan Document::berlaku()
        // langsung, bukan dengan definisi "berlaku" versi kartu ini sendiri.
        $this->assertSame(
            Document::berlaku()->count(),
            $this->props($this->aktorPjo())['sebaranDepartemen']['total'],
            'Total sebaran PJO menyimpang dari Document::berlaku() — arti "berlaku" punya salinan kedua.'
        );
    }

    /**
     * 2 · `menungguDiMeja`: dua kunci baru, dan `dokumen` HANYA berisi `id`.
     *
     * Pengunci §P4. Dulu di sini model `Document` utuh + relasi `creator`, yang
     * berarti `email`/`nrp`/`jabatan` pembuat ikut terserialkan. Kontraknya
     * sudah menyatakan yang benar sejak awal (`types/dasbor.d.ts`), jadi yang
     * diuji adalah bahwa kiriman akhirnya menyusul kontraknya.
     */
    public function test_baris_meja_membawa_sejak_status_dan_dokumen_hanya_id(): void
    {
        $gl = $this->aktorGl();
        $this->kirimkan($gl, 'SOP Uji Baris Meja');

        $baris = $this->props($gl)['menungguDiMeja'];
        $this->assertNotEmpty($baris, 'menungguDiMeja kosong — tes ini tak menguji apa pun.');

        foreach ($baris as $b) {
            $this->assertArrayHasKey('sejak', $b);
            $this->assertArrayHasKey('status', $b);
            $this->assertNotSame('', $b['status'], 'Status mentah kosong — StatusBadge tak punya kunci.');
            $this->assertSame(['id'], array_keys($b['dokumen']),
                '`dokumen` memuat lebih dari `id` — model Document utuh bocor lagi ke data-page (§P4).');
        }
    }

    /**
     * 3 · Tak ada rahasia di HTML MENTAH.
     *
     * Diperiksa pada badan respons, bukan pada props terurai: yang sampai ke
     * peramban adalah atribut `data-page`, dan "view source" membacanya utuh.
     * `email` ikut diperiksa karena kartu Performa PIC yang baru memang
     * menampilkan orang — kartu paling gampang membocorkan satu tabel penuh.
     *
     * `nrp` SENGAJA tidak ikut: `HandleInertiaRequests::share()` mengirimnya
     * untuk pengguna YANG SEDANG LOGIN saja, dan itu keputusan lama yang sah
     * (sidebar mencetaknya). Memasukkannya ke daftar ini akan membuat tes merah
     * karena hal yang benar — dan tes yang merah karena hal yang benar adalah
     * tes yang akan dimatikan orang, bersama sembilan penjaga lain di sebelahnya.
     */
    public function test_dashboard_tak_membocorkan_kolom_sensitif(): void
    {
        $pjo = $this->aktorPjo();
        $gl = $this->aktorGl();
        $sh = $this->aktorSh($gl->department->code);
        $doc = $this->kirimkan($gl, 'SOP Uji Bocor Dashboard');

        // Sejak butir 8, kartu masukan meng-eager-load `document.creator`.
        // Tanpa satu pun baris masukan di layar, sapuan HTML di bawah tak
        // pernah menyentuh relasi itu — dan penjaga yang tak menyentuh apa pun
        // adalah penjaga yang selalu hijau.
        $doc->update(['status' => 'published', 'published_at' => now()]);
        DocumentFeedback::create([
            'feedback_number' => 'UJI-'.now()->timestamp.'-B',
            'document_id' => $doc->id,
            'user_id' => $this->aktorNonStaff($gl->department->code)->id,
            'isi' => 'Masukan uji kebocoran.',
            'status' => 'baru',
        ]);

        foreach (['PJO' => $pjo, 'SH' => $sh] as $peran => $aktor) {
            $html = (string) $this->actingAs($aktor)->get(route('dashboard'))->assertOk()->getContent();

            foreach (['password', 'remember_token', 'arsip_path', 'file_path', 'email'] as $rahasia) {
                $this->assertStringNotContainsString('&quot;'.$rahasia.'&quot;', $html,
                    "Dashboard {$peran} membocorkan kolom `{$rahasia}` ke atribut data-page.");
                $this->assertStringNotContainsString('"'.$rahasia.'"', $html,
                    "Dashboard {$peran} membocorkan kolom `{$rahasia}` ke badan respons.");
            }
        }
    }

    /**
     * 4 · `boleh_balas` datang dari `DocumentFeedback::bisaDibalasOleh()`.
     *
     * SH **tidak** memegang `document.feedback_respond` sejak PLAN-REVISI-v6
     * Fase C (izin itu dipisah dari `request_revision` dan diberikan ke GL, MD,
     * Admin) — jadi `false` baginya bukan kebetulan melainkan justru bukti
     * bahwa jawabannya datang dari model, bukan dari daftar peran yang ditulis
     * ulang di controller. Kalau suatu hari SH diberi izin itu, tes ini merah
     * dan yang perlu diperbaiki adalah tesnya, bukan kodenya.
     */
    public function test_baris_masukan_membawa_rona_dan_izin_balas_dari_model(): void
    {
        $gl = $this->aktorGl();
        $dept = $gl->department;
        $doc = $this->kirimkan($gl, 'SOP Uji Masukan Rona');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        DocumentFeedback::create([
            'feedback_number' => 'UJI-'.now()->timestamp,
            'document_id' => $doc->id,
            'user_id' => $this->aktorNonStaff($dept->code)->id,
            'isi' => 'Langkah 4 sudah tak sesuai kondisi lapangan.',
            'status' => 'baru',
        ]);

        $masukanGl = collect($this->props($gl)['masukanWidget']);
        $this->assertNotEmpty($masukanGl, 'Kartu masukan GL kosong — tes ini tak menguji apa pun.');

        foreach (['rona', 'statusKunci', 'boleh_balas'] as $kunci) {
            $this->assertArrayHasKey($kunci, $masukanGl->first(), "Baris masukan kehilangan kunci `{$kunci}`.");
        }

        // Semua baris kartu ini berstatus `baru`/`dibaca` (`belumDitindak()`),
        // jadi yang membedakan hanya IZIN — persis yang mau diuji.
        $this->assertSame([true], $masukanGl->pluck('boleh_balas')->unique()->values()->all(),
            'GL sedepartemen memegang document.feedback_respond — boleh_balas seharusnya true.');

        $masukanSh = collect($this->props($this->aktorSh($dept->code))['masukanWidget']);
        $this->assertNotEmpty($masukanSh, 'Kartu masukan SH kosong — tes ini tak menguji apa pun.');
        $this->assertSame([false], $masukanSh->pluck('boleh_balas')->unique()->values()->all(),
            'SH TIDAK memegang document.feedback_respond — boleh_balas seharusnya false. '
            .'Kalau ini merah, aturannya sedang disalin ke controller alih-alih dipanggil dari bisaDibalasOleh().');
    }

    /**
     * 4b · Kartu masukan menyebut PEMILIK dokumennya — sebagai STRING.
     *
     * Butir 8 rencana pra-produksi. Cara gagalnya yang berbahaya bukan kunci
     * yang hilang (itu terlihat di layar sebagai "Pemilik: undefined"),
     * melainkan kunci yang diisi MODEL `creator`: nama pembuatnya benar tampil,
     * halaman tampak beres, dan `email`/`password`-nya ikut ke atribut
     * `data-page`. Persis kebocoran Fase 8 migrasi (CLAUDE.md §4). Karena itu
     * yang diuji bukan hanya adanya `pemilik`, tapi juga TIPE-nya.
     */
    public function test_baris_masukan_membawa_pemilik_dokumen(): void
    {
        $gl = $this->aktorGl();
        $dept = $gl->department;
        $doc = $this->kirimkan($gl, 'SOP Uji Pemilik Masukan');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        DocumentFeedback::create([
            'feedback_number' => 'UJI-'.now()->timestamp.'-P',
            'document_id' => $doc->id,
            'user_id' => $this->aktorNonStaff($dept->code)->id,
            'isi' => 'Alat pelindung diri di langkah 2 belum disebutkan.',
            'status' => 'baru',
        ]);

        $baris = collect($this->props($gl)['masukanWidget'])->firstWhere('nomor', $doc->displayNumber());
        $this->assertNotNull($baris, 'Kartu masukan GL tak memuat dokumennya — tes ini tak menguji apa pun.');

        $this->assertArrayHasKey('pemilik', $baris, 'Baris masukan kehilangan kunci `pemilik`.');
        $this->assertIsString($baris['pemilik'],
            '`pemilik` wajib string. Mengirim model `creator` membocorkan email & password pembuatnya.');
        $this->assertSame($gl->name, $baris['pemilik']);

        $this->assertArrayNotHasKey('creator', $baris);
        $this->assertArrayNotHasKey('document', $baris);
    }

    /**
     * 4c · Peta warna Sebaran per Departemen memuat SETIAP jenis dokumen.
     *
     * F3 DASBOR-V4 mencabut peta warna PER-DEPARTEMEN (butir 9): bar sekarang
     * bertumpuk enam JENIS dokumen per departemen, jadi warnanya adalah warna
     * jenis, bukan lagi warna departemen. Sejak TEMUAN-F8 F2a nilainya bukan token
     * `--chart-N` melainkan LANGKAH ramp satu hue (`langkah(i)`), jadi yang
     * dijaga: tiap kode dapat satu langkah, dan nol langkah dipakai dua kali. Jenis yang tak tersebut di peta jatuh
     * ke abu-abu — cara gagal yang tak membuat satu pun gerbang merah dan cuma
     * terbaca sebagai "kok segmen itu kelabu?". Petanya hidup di TSX (nilainya
     * token CSS, bukan aturan peran — §4 tak dilanggar), jadi yang membaca
     * berkasnya adalah tes ini, mengikuti pola `UrutTabelTest`.
     */
    public function test_peta_warna_sebaran_lengkap(): void
    {
        $berkas = resource_path('js/components/v2/dasbor/PitaDepartemen.tsx');
        $isi = file_get_contents($berkas);

        $this->assertMatchesRegularExpression('/const WARNA: Record<string, string> = \{(.+?)\};/s', $isi,
            'Peta WARNA tak ditemukan di PitaDepartemen.tsx — kalau ia berpindah, tes ini ikut dipindahkan.');
        preg_match('/const WARNA: Record<string, string> = \{(.+?)\};/s', $isi, $m);

        preg_match_all("/'?([A-Za-z0-9_-]+)'?\s*:\s*langkah\((\d+)\)/", $m[1], $pasangan, PREG_SET_ORDER);
        $peta = collect($pasangan)->mapWithKeys(fn ($p) => [$p[1] => $p[2]]);

        foreach (DocumentType::kode() as $kode) {
            $this->assertArrayHasKey($kode, $peta->all(),
                "Jenis `{$kode}` tak punya warna di PitaDepartemen.tsx — ia akan tergambar abu-abu.");
        }

        $this->assertSame($peta->count(), $peta->unique()->count(),
            'Dua jenis berbagi satu langkah ramp — legendanya berhenti bisa dibaca.');
    }

    /**
     * 5 · `masukanTotal` memakai penyaring yang SAMA dengan koleksinya.
     *
     * Cara gagalnya senyap: footer "Lihat N masukan lainnya" menunjuk angka
     * yang tak pernah cocok dengan isi kartunya, dan tak ada gerbang yang merah.
     */
    public function test_masukan_total_sejalan_dengan_koleksinya(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->kirimkan($gl, 'SOP Uji Masukan Total');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        foreach (range(1, 3) as $i) {
            DocumentFeedback::create([
                'feedback_number' => "UJI-T{$i}-".now()->timestamp,
                'document_id' => $doc->id,
                'user_id' => $this->aktorNonStaff($gl->department->code)->id,
                'isi' => "Masukan uji total ke-{$i}.",
                'status' => 'baru',
            ]);
        }

        $props = $this->props($gl);
        $this->assertGreaterThanOrEqual(count($props['masukanWidget']), $props['masukanTotal'],
            'masukanTotal lebih kecil dari koleksinya — penyaringnya sudah menyimpang.');
        $this->assertGreaterThanOrEqual(3, $props['masukanTotal']);

        // Non-Staff mendapat kartunya sendiri (kiriman miliknya), jadi angkanya
        // integer — bukan null, bukan galat. `null` dari masukanQuery() hanya
        // berarti "peran ini tak mendapat kartu sama sekali", dan bentuk prop-nya
        // tetap angka supaya TSX tak perlu menjaga dua kemungkinan.
        $this->assertIsInt($this->props($this->aktorNonStaff())['masukanTotal']);
    }

    /**
     * 6 · `tiles` tidak lagi memuat `rona` — pengunci revert §4c.
     *
     * Kunci itu sempat lahir untuk aksen warna per kartu di V2 dan dicabut
     * bersama seluruh tile berona (keputusan pemilik D2). Ia diuji karena
     * mengembalikannya diam-diam adalah cara paling mudah "memperbaiki"
     * kartu yang terasa datar.
     */
    public function test_tiles_tanpa_rona_dan_berkunci_lencana(): void
    {
        foreach ([$this->aktorGl(), $this->aktorSh(), $this->aktorPjo(), $this->aktorNonStaff()] as $aktor) {
            $tiles = $this->props($aktor)['tiles'];

            $this->assertCount(4, $tiles);

            foreach ($tiles as $t) {
                $this->assertArrayNotHasKey('rona', $t, 'Kunci `rona` kembali ke tile (§4c).');
                foreach (['aksi', 'arah', 'deret', 'delta', 'deltaLabel'] as $kunci) {
                    $this->assertArrayHasKey($kunci, $t, "Tile `{$t['label']}` kehilangan kunci lencana `{$kunci}`.");
                }
            }
        }
    }

    /**
     * 7 · `menungguDiMeja` BERPLAFON — dan plafonnya bukan lagi empat.
     *
     * Batas 4 dulu ditegakkan §P1 karena komponen V1 `KartuPerjalanan.tsx`
     * memang dirancang untuk empat baris. V1 dihapus 2026-09-07 dan
     * `LacakStatus` V2 menggulir tabelnya di dalam kartu, jadi baris kelima tak
     * lagi memanjangkan halaman — angkanya naik ke
     * `DashboardController::BARIS_MEJA`.
     *
     * Yang dijaga tes ini karena itu BUKAN angkanya melainkan ADANYA plafon:
     * barisnya ikut ke atribut `data-page` tiap pemuatan dashboard, dan query
     * tanpa `limit` pada departemen sibuk mengirim ratusan dokumen ke peramban
     * tanpa satu pun galat — cuma halaman yang makin berat dari bulan ke bulan.
     * Enam dokumen dikirim supaya tesnya benar-benar melewati batas LAMA;
     * kalau plafonnya hilang sama sekali, `assertLessThanOrEqual` di bawah
     * tetap merah begitu datanya melampaui `BARIS_MEJA`.
     */
    public function test_baris_meja_tetap_berplafon(): void
    {
        $gl = $this->aktorGl();
        foreach (range(1, 6) as $i) {
            $this->kirimkan($gl, "SOP Uji Batas Meja {$i}");
        }

        $plafon = (new ReflectionClass(\App\Http\Controllers\DashboardController::class))
            ->getConstant('BARIS_MEJA');

        $this->assertIsInt($plafon, 'BARIS_MEJA hilang — kartu meja kehilangan plafonnya.');
        $this->assertLessThanOrEqual($plafon, count($this->props($gl)['menungguDiMeja']),
            'menungguDiMeja melampaui plafonnya sendiri.');
    }

    /**
     * 8 · Performa PIC: gerbangnya, dan bentuk WAJAH-nya.
     *
     * Pengunci §P4 untuk kartu yang baru. Ia menampilkan wajah & nama orang,
     * jadi ia wajib mengirim TEPAT lima kunci — bukan model `User`, bukan
     * koleksi `User`, dan terutama bukan `id` yang membuat sisanya bisa
     * ditebak.
     *
     * `rincian` + `ket` menyusul (butir 10 & 11). Daftar kuncinya tetap
     * dibandingkan `assertSame` dan BUKAN `assertArrayHasKey`: yang dijaga di
     * sini justru kunci yang menyelinap masuk, bukan kunci yang hilang.
     */
    public function test_performa_pic_gerbang_dan_bentuk_wajahnya(): void
    {
        // GL menyusul Non-Staff sejak REVISI-UI-V3 §4.1 (keputusan pemilik
        // 2026-09-01, mencabut K1): performa orang lain bukan bacaan penyusun.
        foreach (['Non-Staff' => $this->aktorNonStaff(), 'GL' => $this->aktorGl()] as $peran => $aktor) {
            $this->assertNull($this->props($aktor)['performaPic'],
                "{$peran} tak boleh menerima kartu performa orang lain.");
        }

        // ONGKOS QUERY (§9b). PJO adalah peran termahal: ia satu-satunya yang
        // menerima ketujuh departemen SEKALIGUS kedua kartu baru. Ambangnya
        // longgar dengan sengaja — yang dijaga bukan angka pastinya melainkan
        // N+1 yang menyelinap, dan N+1 di halaman ini berarti satu query per
        // dokumen/per orang, yaitu lonjakan puluhan, bukan satu-dua.
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->props($this->aktorPjo());
        $jumlahQuery = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThan(60, $jumlahQuery,
            "Dashboard PJO menembakkan {$jumlahQuery} query — ada N+1 yang menyelinap di salah satu kartu baru.");

        foreach ([
            'SH' => $this->aktorSh(),
            'PJO' => $this->aktorPjo(),
            'MD' => $this->aktorMd(),
            'Admin' => $this->aktorAdmin(),
        ] as $peran => $aktor) {
            $kartu = $this->props($aktor)['performaPic'];

            $this->assertNotNull($kartu, "{$peran} seharusnya menerima performaPic (gerbang lingkupPic()).");
            $this->assertIsInt($kartu['picAktif']);
            $this->assertLessThanOrEqual(8, count($kartu['wajah']), 'Deret wajah bukan papan peringkat — maksimum 8.');
            $this->assertCount(3, $kartu['sorotan']);

            foreach ($kartu['wajah'] as $w) {
                $this->assertSame(['nama', 'foto', 'jumlah', 'rincian', 'ket'], array_keys($w),
                    'Baris wajah membawa kunci di luar nama/foto/jumlah/rincian/ket — model User bocor ke kartu PIC (§P4).');
            }

            foreach ($kartu['sorotan'] as $s) {
                $this->assertSame(['label', 'nilai', 'arah', 'delta', 'deltaLabel'], array_keys($s));
                $this->assertIsString($s['nilai'], 'Nilai sorotan wajib SUDAH diformat server.');
            }
        }
    }

    /**
     * 8b · Deret wajah Performa PIC benar-benar DISARING di server.
     *
     * Tes 8 hanya membuktikan kartunya ADA dan bentuk barisnya benar; ia tak
     * bisa membedakan saringan yang hidup dari saringan yang tak pernah
     * dipanggil. Yang dijaga di sini justru janji barunya (REVISI-UI-V3 §4.1):
     *
     *   • dashboard SH  → wajahnya HANYA GL, dan HANYA dari departemennya;
     *   • dashboard PJO → wajahnya GL/SH/DH, TAK PERNAH Non-Staff atau PJO.
     *
     * Diperiksa lewat NAMA, karena `nama` memang satu-satunya penanda orang
     * yang boleh keluar dari kartu ini (§P4) — dan itu justru yang membuat tes
     * ini sekaligus jadi pengunci kebocoran: kalau suatu hari `jabatan` ikut
     * terkirim demi memudahkan tes, penjaga kunci di tes 8 langsung merah.
     *
     * Dibangun dari data, bukan dari asumsi isi seeder: aksi audit dibuat atas
     * nama aktor yang jelas jabatannya, lalu dicek siapa yang muncul.
     *
     * **Jumlah barisnya SENGAJA besar, dan itu yang membuat tes ini bisa
     * merah.** Kartunya cuma memuat DELAPAN wajah teratas. Kalau tiap aktor
     * cuma diberi satu baris, ketiganya bisa terlempar keluar dari delapan
     * besar oleh riwayat basis data pengembangan — dan `assertNotContains`
     * akan HIJAU tanpa saringan apa pun berjalan, yaitu tes yang tak pernah
     * bisa gagal. Dengan `$BANYAK` baris ketiganya dijamin di puncak, sehingga
     * ketidakhadiran dua di antaranya benar-benar berarti mereka DISARING.
     *
     * Penjaganya sendiri asersi POSITIF di bawah: GL sedepartemen WAJIB muncul.
     * Kalau ia pun hilang, yang rusak bukan saringannya melainkan andaian tes
     * ini — dan merahnya menunjuk ke sana, bukan menyesatkan.
     */
    public function test_wajah_performa_pic_disaring_jabatan_dan_departemen(): void
    {
        $glSendiri = $this->aktorGl();                  // ICTMD
        $glLain = $this->aktorGl('SHE');                // departemen berbeda
        $nonStaff = $this->aktorNonStaff();
        $sh = $this->aktorSh();

        // Aksi yang menghitung sebagai "produktif" — dibuat baru supaya tak
        // bergantung pada isi audit_logs basis data pengembangan.
        $doc = $this->kirimkan($glSendiri, 'Uji saringan wajah PIC');
        $banyak = 40;

        foreach ([$glSendiri, $glLain, $nonStaff] as $pelaku) {
            for ($i = 0; $i < $banyak; $i++) {
                AuditLog::create([
                    'user_id' => $pelaku->id,
                    'document_id' => $doc->id,
                    'action' => DasborTampilan::aksiProduktif()[0],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $namaSh = array_column($this->props($sh)['performaPic']['wajah'], 'nama');

        $this->assertContains($glSendiri->name, $namaSh,
            'GL sedepartemen TIDAK muncul di deret wajah SH — andaian tes ini yang rusak, '
            .'atau saringannya membuang orang yang justru jadi isi kartunya.');
        $this->assertNotContains($nonStaff->name, $namaSh,
            'Non-Staff muncul di deret wajah SH — saringan JABATAN tak berjalan.');
        $this->assertNotContains($glLain->name, $namaSh,
            'GL departemen lain muncul di deret wajah SH — saringan DEPARTEMEN tak berjalan.');

        // PJO melihat tujuh departemen, jadi GL dept lain BOLEH muncul —
        // yang tetap terlarang cuma Non-Staff.
        $namaPjo = array_column($this->props($this->aktorPjo())['performaPic']['wajah'], 'nama');

        $this->assertContains($glLain->name, $namaPjo,
            'GL departemen lain TIDAK muncul di deret wajah PJO — saringan departemen '
            .'ikut terpasang pada peran yang justru berwenang atas tujuh departemen.');
        $this->assertNotContains($nonStaff->name, $namaPjo,
            'Non-Staff muncul di deret wajah PJO — kartunya hanya untuk GL/SH/DH.');
    }

    /**
     * 8c · Bentuk `rincian` + `ket` tiap wajah (butir 10).
     *
     * Keempat kunci rincian WAJIB selalu ada, termasuk yang bernilai nol:
     * kunci yang menghilang saat angkanya nol membuat bentuk propsnya
     * berubah-ubah per orang, dan pengunci kebocoran di tes 8 — yang
     * membandingkan DAFTAR kunci — jadi mustahil ditulis.
     *
     * `ket` diperiksa sebagai STRING yang benar-benar menyebut kata kerjanya,
     * bukan sekadar `assertIsString`: kalimatnya dirakit di server justru
     * supaya kosakata alur ("membuat"/"meninjau") tak punya salinan kedua di
     * TSX, dan tes yang cuma memeriksa tipenya tetap hijau saat servernya
     * mengirim string kosong.
     *
     * Aksinya dibuat sendiri, bukan mengandalkan isi `audit_logs` basis data
     * pengembangan — dan asersinya `>=` karena baris lama di jendela 30 hari
     * yang sama tak bisa dikosongkan (`DatabaseTransactions`).
     */
    public function test_rincian_dan_ket_wajah_performa_pic(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->kirimkan($gl, 'Uji rincian wajah PIC');

        // 6 aksi produktif (menaikkan `jumlah` DAN `rincian.ditinjau`) + 3
        // `document.create` yang HANYA menaikkan `rincian.dibuat`.
        foreach ([['document.review_approve', 6], ['document.create', 3]] as [$aksi, $kali]) {
            for ($i = 0; $i < $kali; $i++) {
                AuditLog::create([
                    'user_id' => $gl->id,
                    'document_id' => $doc->id,
                    'action' => $aksi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $wajah = collect($this->props($this->aktorSh())['performaPic']['wajah'])
            ->firstWhere('nama', $gl->name);

        $this->assertNotNull($wajah, 'GL sedepartemen tak muncul — andaian tes ini yang rusak.');

        $this->assertSame(['dibuat', 'ditinjau', 'diperiksa', 'disetujui'], array_keys($wajah['rincian']),
            'Kunci rincian bergeser dari DasborTampilan::RINCIAN_PIC — kartu dan query punya dua peta.');

        foreach ($wajah['rincian'] as $kunci => $nilai) {
            $this->assertIsInt($nilai, "rincian.{$kunci} bukan integer — hanya angka yang boleh keluar dari kartu ini (§P4).");
        }

        $this->assertGreaterThanOrEqual(3, $wajah['rincian']['dibuat'],
            '`document.create` tak tersapu ke rincian — butir 11 (berapa dokumen dibuat GL) tak terjawab.');
        $this->assertGreaterThanOrEqual(6, $wajah['rincian']['ditinjau']);

        $this->assertIsString($wajah['ket']);
        $this->assertStringContainsString('membuat', $wajah['ket'],
            'Kalimat rincian tak menyebut kata kerjanya — dirakit di TSX, bukan di server.');
        $this->assertStringContainsString('meninjau', $wajah['ket']);
    }

    /**
     * 8d · `dibuat` TIDAK menyusup ke peringkat wajah CAMPURAN (K-H).
     *
     * Kartunya bukan papan peringkat, dan `KUNCI_PRODUKTIF` sengaja tak memuat
     * `dibuat`: menulis draft memang kerja, tapi mencampurnya dengan
     * meloloskan/menyetujui membuat satu kartu mengukur dua pekerjaan berbeda.
     * Butir 10 menambahkan `rincian`, dan rincian itulah satu-satunya tempat
     * `dibuat` boleh muncul.
     *
     * Diuji lewat **PJO** (lingkup CAMPURAN GL+SH+DH), bukan SH — sejak F4b
     * DASBOR-V4 (D3) lingkup SESAMA-GL (dashboard SH/DH) justru MEMAKAI
     * `dibuat`+`dikirim` (`KUNCI_PRODUKTIF_GL`), sebab GL tak pernah
     * meninjau/menyetujui dan `KUNCI_PRODUKTIF` biasa selalu kosong baginya
     * (§A.2 rencana). K-H aslinya cuma berlaku untuk lingkup campuran, dan
     * PJO itulah satu-satunya peran yang mempertahankannya tanpa berubah.
     *
     * Diuji sebagai URUTAN SEBELUM vs SESUDAH pada dashboard yang sama, bukan
     * sebagai posisi mutlak: basis data pengembangan sudah memuat wajah lain,
     * dan yang dijanjikan kartunya bukan "siapa nomor satu" melainkan bahwa
     * seratus draft baru tak menggeser seorang pun.
     */
    public function test_dibuat_tak_menggeser_urutan_wajah_performa_pic(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->kirimkan($gl, 'Uji urutan wajah PIC');

        for ($i = 0; $i < 40; $i++) {
            AuditLog::create([
                'user_id' => $gl->id,
                'document_id' => $doc->id,
                'action' => DasborTampilan::aksiProduktif()[0],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sebelum = array_column($this->props($this->aktorPjo())['performaPic']['wajah'], 'nama');

        // Seratus draft — jauh lebih banyak dari aksi produktif siapa pun di
        // kartu ini. Kalau `dibuat` ikut menghitung peringkat, urutannya PASTI
        // berubah.
        for ($i = 0; $i < 100; $i++) {
            AuditLog::create([
                'user_id' => $gl->id,
                'document_id' => $doc->id,
                'action' => 'document.create',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $sesudah = $this->props($this->aktorPjo())['performaPic']['wajah'];

        $this->assertSame($sebelum, array_column($sesudah, 'nama'),
            'Urutan wajah bergeser setelah 100 `document.create` — `dibuat` menyusup ke peringkat (K-H).');
        $this->assertGreaterThanOrEqual(100, collect($sesudah)->firstWhere('nama', $gl->name)['rincian']['dibuat'],
            'Draft barunya tak terhitung di rincian sama sekali — sapuannya melewatkan `document.create`.');
    }

    /**
     * 8e · F4a — gerbang `sebaranJenisDept` DAN bentuk barisnya.
     *
     * Pasangan `sebaranDepartemen`, gerbang TERBALIK (D6/F4a): bukan-`null`
     * HANYA bagi SH/DH — GL & Non-Staff tak punya baris ke-5 sama sekali, dan
     * PJO/MD/Admin sudah punya `sebaranDepartemen` lintas tujuh departemen.
     *
     * Bentuk barisnya dikunci dari KODE, bukan angka tebakan: `count(baris)`
     * wajib sama dengan jumlah jenis dokumen terdaftar, dan tumpukan tiap
     * baris (`per`) wajib memuat ketujuh kunci status `matrix` — supaya bar
     * bertumpuk tak diam-diam kehilangan satu status saat jenisnya bertambah.
     *
     * Sejak keputusan pemilik 2026-09-07 ("samakan style-nya seperti milik
     * PJO") kepala kartunya WAJIB membawa kunci yang sama persis dengan
     * `sebaranDepartemen`. Diuji dari DAFTAR kunci `sebaranDepartemen` milik
     * PJO, bukan dari daftar yang diketik ulang di sini: dua kartu yang
     * dijanjikan seragam hanya bisa dijaga oleh perbandingan, dan daftar kedua
     * yang diketik tangan justru cara paling gampang keduanya menyimpang lagi.
     */
    public function test_sebaran_jenis_dept_gerbang_dan_bentuknya(): void
    {
        $jumlahJenis = count(DocumentType::kode());

        foreach ([
            'GL' => $this->aktorGl(),
            'Non-Staff' => $this->aktorNonStaff(),
            'PJO' => $this->aktorPjo(),
            'MD' => $this->aktorMd(),
            'Admin' => $this->aktorAdmin(),
        ] as $peran => $aktor) {
            $this->assertNull($this->props($aktor)['sebaranJenisDept'],
                "{$peran} tak boleh menerima sebaranJenisDept — gerbangnya HANYA SH/DH.");
        }

        foreach (['SH' => $this->aktorSh(), 'DH' => $this->aktorDh()] as $peran => $aktor) {
            $data = $this->props($aktor)['sebaranJenisDept'];

            $this->assertNotNull($data, "{$peran} seharusnya menerima sebaranJenisDept.");
            $this->assertCount($jumlahJenis, $data['baris'],
                'Jumlah baris (per jenis dokumen) tak cocok dengan DocumentType::kode().');

            foreach ($data['baris'] as $baris) {
                $this->assertCount(7, $baris['per'],
                    'Tumpukan status satu baris bukan tujuh — matrix punya 7 status yang dipantau.');
            }

            $this->assertSame(
                array_keys($this->props($this->aktorPjo())['sebaranDepartemen']),
                array_keys($data),
                "Kepala kartu {$peran} tak lagi seragam dengan sebaranDepartemen milik PJO.",
            );

            $this->assertSame(
                array_keys($this->props($this->aktorPjo())['sebaranDepartemen']['baris'][0]),
                array_keys($data['baris'][0]),
                "Baris kartu {$peran} tak lagi seragam dengan sebaranDepartemen milik PJO.",
            );

            $this->assertNotContains($data['arah'], [null, ''],
                'Arah lencana wajib datang dari server (CLAUDE.md §4).');
        }
    }

    /**
     * Mendaftarkan dokumen LAMA bukan "menyetujui" — temuan pemilik 2026-09-07.
     *
     * Kartu Performa PIC di dashboard SH menuliskan "menyetujui N" pada seorang
     * GL, padahal GL tak pernah menyetujui (CLAUDE.md §6). Sebabnya bukan
     * kebocoran lingkup melainkan satu kunci alir yang memuat DUA aksi:
     * `AKSI_ALIR['berlaku']` berisi `document.approve` DAN
     * `document.arsip_upload`, dan yang kedua dikerjakan justru oleh pembuatnya
     * saat mendaftarkan dokumen lama lewat "Salin ke wizard".
     *
     * Diuji pada `DasborTampilan` langsung, tanpa basis data: yang salah adalah
     * PEMETAANNYA, dan menempuhnya lewat `/dashboard` menuntut seorang GL yang
     * kebetulan pernah mengunggah arsip dalam 30 hari — syarat yang membuat tes
     * ini hijau karena datanya sepi, bukan karena kodenya benar.
     *
     * Aliran `berlaku` itu sendiri TIDAK boleh ikut berubah: dokumen lama yang
     * didaftarkan memang berlaku, dan mencabutnya dari sana akan menggeser
     * setiap kartu "Berlaku" di dashboard.
     */
    public function test_mendaftarkan_dokumen_lama_bukan_menyetujui(): void
    {
        $arsip = DasborTampilan::rincian(['document.arsip_upload']);

        $this->assertSame(0, $arsip['disetujui'],
            'document.arsip_upload masih terhitung "menyetujui" — GL yang mendaftarkan dokumen lama akan disebut menyetujui.');
        $this->assertStringNotContainsString('menyetujui', DasborTampilan::ketRincian($arsip),
            'Kalimat rincian masih menyebut kata kerja yang tak pernah dikerjakan orangnya.');

        $this->assertSame(1, DasborTampilan::rincian(['document.approve'])['disetujui'],
            'Persetujuan sungguhan ikut hilang — pengecualiannya terlalu lebar.');

        $this->assertContains('document.arsip_upload', DasborTampilan::AKSI_ALIR['berlaku'],
            'Aliran `berlaku` kehilangan arsip_upload — dokumen lama yang didaftarkan memang berlaku.');
    }

    /**
     * 8f · F4b — dasbor SH tak lagi kosong permanen (§A.2 rencana).
     *
     * Sebelum D3, `wajahProduktif()` untuk lingkup sesama-GL memeringkat
     * dengan `KUNCI_PRODUKTIF` biasa (diloloskan/md/berlaku) — dan GL tak
     * pernah melakukan satu pun dari ketiganya (CLAUDE.md §6), jadi kartunya
     * pasti kosong. Tes ini MERAH tanpa `KUNCI_PRODUKTIF_GL`: GL yang membuat
     * dokumen (`document.create`) di departemen SH wajib muncul di wajahnya.
     */
    public function test_wajah_performa_pic_sh_tak_kosong_setelah_gl_membuat_dokumen(): void
    {
        $sh = $this->aktorSh();
        $gl = $this->aktorGl();
        $this->kirimkan($gl, 'Uji kartu SH tak kosong (F4b)');

        $wajah = $this->props($sh)['performaPic']['wajah'];

        $this->assertNotEmpty($wajah,
            'Kartu Performa PIC SH masih kosong walau GL sedepartemen baru saja membuat dokumen — K-H belum dipersempit (D3).');
        $this->assertContains($gl->name, array_column($wajah, 'nama'));
    }

    /**
     * 8g · F6 — gerbang `aktivitasTabel` DAN bentuk paginatornya.
     *
     * Gerbang SAMA dengan `sebaranDepartemen` (`document.view_all`): `null`
     * bagi GL, SH, Non-Staff — mereka tak berwenang atas ketujuh departemen.
     * Bukan `menungguDiMeja` diperbesar (§P1 rencana): paginatornya utuh
     * (`data`/`links`/`from`/`to`/`total`), bukan larik polos.
     */
    public function test_aktivitas_tabel_gerbang_dan_bentuk_paginatornya(): void
    {
        foreach ([
            'GL' => $this->aktorGl(),
            'SH' => $this->aktorSh(),
            'Non-Staff' => $this->aktorNonStaff(),
        ] as $peran => $aktor) {
            $this->assertNull($this->props($aktor)['aktivitasTabel'],
                "{$peran} tak boleh menerima aktivitasTabel — gerbangnya document.view_all.");
        }

        $pjo = $this->aktorPjo();
        $gl = $this->aktorGl();
        $this->kirimkan($gl, 'Uji aktivitasTabel (F6)');

        $tabel = $this->props($pjo)['aktivitasTabel'];

        $this->assertNotNull($tabel, 'PJO seharusnya menerima aktivitasTabel.');
        $this->assertSame(
            ['data', 'links', 'from', 'to', 'total'],
            array_values(array_intersect(['data', 'links', 'from', 'to', 'total'], array_keys($tabel))),
            'Bentuk paginator tak utuh — TSX membaca data/links/from/to/total apa adanya.'
        );
        $this->assertNotEmpty($tabel['data'], 'Dokumen yang baru dikirim tak muncul di Aktivitas Terbaru.');
        $this->assertSame(
            ['id', 'judul', 'nomor', 'tautan', 'status', 'dibuat', 'diperbarui', 'ke', 'dariTahap', 'persen', 'pembuat'],
            array_keys($tabel['data'][0]),
            'Bentuk baris aktivitasTabel berubah — TSX membacanya kolom per kolom.'
        );
        $this->assertSame(['nama', 'foto', 'jabatan'], array_keys($tabel['data'][0]['pembuat']));
    }

    /**
     * 8h · F6 — kebocoran (§P4) pada HTML MENTAH, khusus `aktivitasTabel`.
     *
     * `menungguDiMeja` sudah dijaga tes 3; ini kartu BARU dengan query
     * `creator` sendiri, jadi ia bisa bocor dengan cara yang berbeda walau
     * tes 3 tetap hijau.
     */
    public function test_aktivitas_tabel_tak_membocorkan_kolom_sensitif(): void
    {
        $pjo = $this->aktorPjo();
        $gl = $this->aktorGl();
        $this->kirimkan($gl, 'Uji bocor aktivitasTabel (F6)');

        $html = (string) $this->actingAs($pjo)->get(route('dashboard'))->assertOk()->getContent();

        foreach (['password', 'remember_token', 'arsip_path', 'file_path', 'email'] as $rahasia) {
            $this->assertStringNotContainsString('&quot;'.$rahasia.'&quot;', $html,
                "aktivitasTabel membocorkan kolom `{$rahasia}` ke atribut data-page.");
            $this->assertStringNotContainsString('"'.$rahasia.'"', $html,
                "aktivitasTabel membocorkan kolom `{$rahasia}` ke badan respons.");
        }
    }

    /**
     * 9 · Lencana KPI datanya NYATA — bergerak saat `audit_logs` bergerak.
     *
     * Diuji sebagai SELISIH, bukan sebagai angka mutlak: suite ini berjalan di
     * atas basis data pengembangan yang sudah memuat ratusan baris audit
     * sungguhan (`DatabaseTransactions`, bukan `RefreshDatabase`), jadi
     * "nol pada 30 hari sebelumnya" tak pernah bisa dijamin dari luar. Yang
     * bisa dijamin — dan yang sebenarnya dijanjikan kartunya — adalah bahwa dua
     * pengesahan baru hari ini MUNCUL di ember bulan berjalan.
     */
    public function test_lencana_kpi_bergerak_mengikuti_audit_logs(): void
    {
        $pjo = $this->aktorPjo();
        $doc = $this->kirimkan($this->aktorGl(), 'SOP Uji Lencana KPI');

        $sebelum = $this->tile($this->props($pjo), 'Berlaku');
        $this->assertCount(8, $sebelum['deret'], 'Sparkline KPI wajib 8 titik bulanan — sama dengan jendela grafik Overview.');

        foreach (range(1, 2) as $i) {
            AuditLog::create([
                'user_id' => $pjo->id,
                'document_id' => $doc->id,
                'action' => 'document.approve',
                'created_at' => now(),
            ]);
        }

        $sesudah = $this->tile($this->props($pjo), 'Berlaku');

        $this->assertSame(
            end($sebelum['deret']) + 2,
            end($sesudah['deret']),
            'Dua `document.approve` hari ini tak muncul di ember bulan berjalan — lencana KPI tidak membaca audit_logs.'
        );
        $this->assertNotNull($sesudah['deltaLabel'], 'Ada aktivitas 30 hari terakhir, tapi lencananya tak digambar.');

        // Kartu MD "Perlu Diperiksa" SENGAJA tanpa lencana: tak ada aksi audit
        // yang jujur mewakili sebuah ANTREAN (§5c).
        $md = User::where('nrp', 'MD-0001')->firstOrFail();
        $tileMd = $this->tile($this->props($md), 'Perlu Diperiksa');

        $this->assertNotNull($tileMd, 'Kartu MD "Perlu Diperiksa" hilang.');
        $this->assertNull($tileMd['aksi'], 'Kartu MD mendapat kunci alir — §5c menyatakan ia tak berlencana.');
        $this->assertNull($tileMd['delta']);
        $this->assertNull($tileMd['deret']);
    }

    /**
     * 10 · `arah` datang dari server, bukan dari tanda angka.
     *
     * Inilah bedanya lencana yang berarti dan lencana yang cuma berwarna:
     * naiknya "Dokumen Ditolak" adalah kabar BURUK meski angkanya positif.
     * Kalau TSX menyimpulkannya dari `delta > 0`, keduanya akan hijau.
     */
    public function test_arah_lencana_dihitung_di_server(): void
    {
        $tiles = $this->props($this->aktorGl())['tiles'];

        $this->assertSame('buruk', collect($tiles)->firstWhere('label', 'Dokumen Ditolak')['arah']);
        $this->assertSame('baik', collect($tiles)->firstWhere('label', 'Berlaku')['arah']);
        $this->assertSame('netral', collect($tiles)->firstWhere('label', 'Dokumen Saya')['arah']);
    }

    /**
     * 11 · Pembagi nol tak pernah melahirkan `INF`/`NAN`.
     *
     * Diuji langsung terhadap `DasborTampilan::tiles()` dengan `$alir` buatan —
     * satu-satunya cara menghadirkan "periode lalu benar-benar nol" secara pasti
     * di atas basis data yang sudah berisi. `INF`/`NAN` di sini tak sekadar
     * jelek: `json_encode` MENOLAK keduanya, jadi kegagalannya berupa halaman
     * yang tak tergambar sama sekali.
     */
    public function test_delta_aman_saat_periode_sebelumnya_nol(): void
    {
        $gl = $this->aktorGl();
        $tampilan = app(DasborTampilan::class);
        $stats = ['total' => 5, 'my_documents' => 5, 'published' => 3, 'rejected' => 1, 'sedang_direvisi' => 0, 'menunggu_tinjau' => 2];

        $buat = fn (array $alir) => collect(
            $tampilan->tiles($gl, $stats, [], true, false, false, false, $alir)
        )->keyBy('label');

        $baru = $buat(['berlaku' => ['deret' => array_fill(0, 8, 0), 'kini' => 3, 'lalu' => 0]])['Berlaku'];
        $this->assertNotNull($baru['delta']);
        $this->assertTrue(is_finite($baru['delta']), 'Delta INF/NAN — json_encode akan menolak seluruh halaman.');
        $this->assertSame('+100.0%', $baru['deltaLabel'], 'Pertumbuhan dari nol wajib berbunyi "+100.0%" — teks "Baru" terbaca sebagai "kartu ini baru", bukan "angkanya naik dari nol" (TEMUAN-F8 1c).');

        $sepi = $buat(['berlaku' => ['deret' => array_fill(0, 8, 0), 'kini' => 0, 'lalu' => 0]])['Berlaku'];
        $this->assertNull($sepi['delta'], 'Dua periode kosong wajib delta null — lencananya tak digambar.');
        $this->assertNull($sepi['deltaLabel']);

        $turun = $buat(['berlaku' => ['deret' => array_fill(0, 8, 0), 'kini' => 4, 'lalu' => 5]])['Berlaku'];
        $this->assertSame(-20.0, $turun['delta']);
        $this->assertSame('-20.0%', $turun['deltaLabel']);
    }

    /** 12 · Tiap baris garis waktu punya `kategori` yang bukan kosong. */
    public function test_aktivitas_membawa_kategori(): void
    {
        $baris = $this->props($this->aktorPjo())['activities'];
        $this->assertNotEmpty($baris, 'Feed aktivitas kosong — tes ini tak menguji apa pun.');

        foreach ($baris as $b) {
            $this->assertArrayHasKey('kategori', $b);
            $this->assertNotSame('', trim((string) $b['kategori']),
                'Lencana kategori kosong menyisakan lubang seukuran lencana di garis waktu.');
        }
    }

    /**
     * 13 · Tiap aksi di `AKSI_ALIR` benar-benar aksi AUDIT — pengunci §P9.
     *
     * Kekhawatiran aslinya konkret: menyapu `grep` atas kode menghasilkan 57
     * string berpola `document.*`, dan sebagiannya nama IZIN spatie
     * (`document.review`, `document.create`, `document.view_all`) yang muncul di
     * kedua peran. Memetakan kartu ke nama yang ternyata izin membuat lencananya
     * menunjukkan 0 SELAMANYA, diam-diam, tanpa satu gerbang pun merah.
     *
     * Gerbangnya berdiri di KATALOG KODE (`AuditLog::AKSI_META` ∪
     * `DasborTampilan::AKSI`), bukan di isi tabel `audit_logs` seperti bunyi
     * rencana. Alasannya: `document.approval_reject` dan `feedback.respond`
     * NOL BARIS di basis data — bukan karena namanya salah, melainkan karena
     * peristiwanya belum pernah terjadi. Menuntut barisnya ada berarti tes ini
     * mengukur kelengkapan data seeder, bukan kebenaran peta; dan peta yang
     * salah ketik tetap lolos begitu seseorang kebetulan menolak satu dokumen.
     * Katalog kode menangkap salah ketik SETIAP saat, tanpa syarat itu.
     */
    public function test_setiap_aksi_alir_adalah_aksi_audit_yang_terdaftar(): void
    {
        $aksiKelas = (new ReflectionClass(DasborTampilan::class))->getConstant('AKSI');
        $katalog = array_merge(array_keys(AuditLog::AKSI_META), array_keys($aksiKelas));

        $this->assertNotEmpty($katalog, 'Katalog aksi audit terbaca kosong — polanya berubah?');

        foreach (DasborTampilan::AKSI_ALIR as $kunci => $aksiSet) {
            $this->assertNotEmpty($aksiSet, "Kunci alir `{$kunci}` tak menyebut satu pun aksi.");

            foreach ($aksiSet as $aksi) {
                $this->assertContains($aksi, $katalog,
                    "`{$aksi}` (kunci alir `{$kunci}`) bukan aksi audit yang terdaftar. "
                    .'Kalau ia nama IZIN spatie, lencananya akan diam di nol selamanya (§P9).');
            }
        }

        // Kunci produktif wajib benar-benar ada di AKSI_ALIR — salah ketik di
        // sini membuat deret wajah Performa PIC menyapu larik kosong.
        foreach (DasborTampilan::KUNCI_PRODUKTIF as $kunci) {
            $this->assertArrayHasKey($kunci, DasborTampilan::AKSI_ALIR,
                "KUNCI_PRODUKTIF menyebut `{$kunci}` yang tak ada di AKSI_ALIR.");
        }
    }

    /**
     * 15 · Meter per peran (rencana pra-produksi Fase 10, keputusan K-C).
     *
     * Judul DAN angkanya berbeda per peran, dan keduanya datang dari server —
     * `MeterTertinjau.tsx` tak lagi menulis kata "Tertinjau" di satu pun dari
     * tiga tempatnya. Kalau `meterLabel` hilang, kartunya kehilangan judul
     * tanpa satu pun gerbang lain berbunyi.
     *
     * Urutan angkanya diuji pada GL vs SH SEDEPARTEMEN — hanya di situ kedua
     * penyebutnya sama persis (dokumen departemen yang sama), sehingga
     * perbandingannya benar-benar menguji pembilangnya. PJO bervisibilitas
     * lintas tujuh departemen, jadi angkanya tak sebanding; yang dijaga
     * untuknya labelnya, dan bahwa pembilangnya tak pernah melebihi penyebut.
     */
    public function test_meter_label_dan_angka_berbeda_per_peran(): void
    {
        $gl = $this->aktorGl();
        $sop = DocumentType::where('code', 'SOP')->firstOrFail();

        // Tiga anak tangga alur, seluruhnya di departemen yang sama:
        //  · waiting_for_review → hanya masuk pembilang GL
        //  · pending_approval   → masuk pembilang GL dan SH
        //  · published          → masuk ketiganya
        foreach (['waiting_for_review', 'pending_approval', 'published'] as $i => $status) {
            $d = app(DocumentService::class)->createDraft($gl, $sop, $gl->department, "SOP Meter {$i}");
            $d->forceFill([
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
            ])->save();
        }

        $glProps = $this->props($gl)['tren']['bulan'];
        $shProps = $this->props($this->aktorSh())['tren']['bulan'];
        $pjoProps = $this->props($this->aktorPjo())['tren']['bulan'];

        $this->assertSame('Dibuat', $glProps['meterLabel']);
        $this->assertSame('Tertinjau', $shProps['meterLabel']);
        $this->assertSame('Disetujui', $pjoProps['meterLabel']);

        $this->assertGreaterThan(
            $shProps['growth'], $glProps['growth'],
            'Pembilang GL wajib memuat dokumen yang belum ditinjau; kalau sama, himpunan statusnya tertukar.'
        );

        foreach ([$glProps, $shProps, $pjoProps] as $set) {
            $this->assertLessThanOrEqual($set['total'], $set['tertinjau']);
            $this->assertLessThanOrEqual(100, $set['growth']);
        }
    }

    /**
     * 16 · `meterLabel` + `meterKeterangan` ikut di KETIGA durasi.
     *
     * Durasi ditukar di sisi klien dari `tren.hari|minggu|bulan`. Kalau hanya
     * satu deret yang membawa kuncinya, mengganti rentang akan mengosongkan
     * judul kartu dan kalimat kakinya — cacat yang tak pernah muncul di deret
     * bawaan, jadi ia lolos pemeriksaan mata.
     */
    public function test_meter_label_ikut_di_ketiga_durasi(): void
    {
        $tren = $this->props($this->aktorPjo())['tren'];

        foreach (['hari', 'minggu', 'bulan'] as $durasi) {
            $set = $tren[$durasi];
            $this->assertSame('Disetujui', $set['meterLabel'], "Durasi {$durasi} kehilangan meterLabel.");
            $this->assertSame(
                "{$set['tertinjau']} dari {$set['total']} dokumen sudah disetujui",
                $set['meterKeterangan'],
                "Kalimat kaki durasi {$durasi} tak sejalan dengan angkanya."
            );
        }
    }

    /**
     * 17 · MD memakai `meterLabel` "Diperiksa" sendiri, bukan "Tertinjau" milik SH.
     *
     * DASBOR-V4-RENCANA §A.3: `$meter` sebelumnya jatuh ke cabang `default`
     * bagi MD, sehingga kartunya membaca label DAN angka milik SH. Diuji di
     * KETIGA durasi — pola yang sama dengan tes 16, karena deret yang
     * kehilangan kuncinya hanya kosong sesudah rentangnya diganti.
     */
    public function test_meter_label_md_diperiksa(): void
    {
        $tren = $this->props($this->aktorMd())['tren'];

        foreach (['hari', 'minggu', 'bulan'] as $durasi) {
            $set = $tren[$durasi];
            $this->assertSame('Diperiksa', $set['meterLabel'], "Durasi {$durasi} bagi MD masih berlabel milik SH.");
            $this->assertSame(
                "{$set['tertinjau']} dari {$set['total']} dokumen sudah melewati pemeriksaan MD",
                $set['meterKeterangan'],
                "Kalimat kaki durasi {$durasi} tak sejalan dengan label MD."
            );
        }
    }

    /**
     * 18 · F2 DASBOR-V4 (D1) — lencana `LacakStatus` mengukur ALIRAN, bukan
     * potret `matrix`.
     *
     * Keempat baris yang dipantau (draft/in_review/pending_approval/rejected)
     * wajib punya `aliranKet` bukan-kosong dan `delta` yang, bila terisi,
     * bukan `INF`/`NAN` (pengunci pembagi-nol yang sama dengan tes 11). Baris
     * lain (mis. `published`) wajib TETAP `null` — larangan §5b DASBOR-V2
     * hanya dilonggarkan untuk empat baris ini, bukan seluruh matrix.
     */
    public function test_lencana_matrix_lacak_status_mengukur_aliran(): void
    {
        $matrix = $this->props($this->aktorPjo())['matrix'];
        $per = collect($matrix)->keyBy('status');

        foreach (['draft', 'in_review', 'pending_approval', 'rejected'] as $status) {
            $row = $per[$status];
            $this->assertNotEmpty($row['aliranKet'], "Baris `{$status}` wajib punya aliranKet.");
            $this->assertNotNull($row['arah']);
            if ($row['delta'] !== null) {
                $this->assertIsNumeric($row['delta']);
                $this->assertFalse(is_infinite($row['delta']), "delta `{$status}` tak boleh INF.");
                $this->assertFalse(is_nan($row['delta']), "delta `{$status}` tak boleh NAN.");
            }
        }

        foreach (['published', 'verifikasi_md', 'sedang_direvisi'] as $status) {
            $this->assertNull($per[$status]['aliranKet'], "Baris `{$status}` bukan bagian LacakStatus, wajib null.");
            $this->assertNull($per[$status]['delta']);
        }
    }
}
