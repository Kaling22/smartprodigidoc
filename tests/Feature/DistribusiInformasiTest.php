<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiRead;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\InformasiDistribution;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * DISTRIBUSI menu Informasi (PLAN-REVISI-v6 Fase D / butir 4).
 *
 * Yang dijaga di sini adalah tiga janji yang rusak tanpa gejala apa pun —
 * halaman tetap tampil, angkanya saja yang bohong:
 *
 *  1. **Pencatatan idempoten per orang.** Kalau tidak, satu orang yang membuka
 *     poster sepuluh kali tampak seperti sepuluh pembaca, dan angka cakupan
 *     berbohong ke arah yang menenangkan.
 *  2. **Lingkup departemen (keputusan C1).** GL/SH/DH menghitung orangnya
 *     sendiri; PJO/MD menghitung seluruh pengguna dan mendapat rincian 7
 *     departemen. Pembaca ikut disaring — kalau hanya sasarannya yang disaring,
 *     yang muncul adalah "12 dari 8 orang".
 *  3. **Sasaran kosong tak pernah membagi nol.**
 */
class DistribusiInformasiTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    private function dept(string $kode = 'ICTMD'): Department
    {
        return Department::where('code', $kode)->firstOrFail();
    }

    private function orang(string $jabatan, Department $dept, string $nama): User
    {
        static $n = 0;
        $n++;
        $u = User::create([
            'name' => $nama, 'nrp' => "DI-{$n}", 'jabatan' => $jabatan,
            'department_id' => $dept->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole($jabatan);

        return $u;
    }

    /** Satu informasi BERLAKU, diunggah Admin. */
    private function informasi(): Informasi
    {
        return Informasi::create([
            'kategori' => 'poster',
            'nomor' => 'INF-DIST-'.Str::upper(Str::random(6)),
            'judul' => 'Poster Distribusi Uji',
            'file_path' => 'informasi/poster/uji.pdf',
            'file_mime' => 'application/pdf',
            'berlaku' => true,
            'uploaded_by' => $this->admin()->id,
        ]);
    }

    // ── Pencatatan ───────────────────────────────────────────────────────────

    /** Membuka berkas mencatat SATU baris; membuka lagi menaikkan pencacahnya. */
    public function test_membuka_berkas_dicatat_dan_idempoten(): void
    {
        Storage::fake('local');
        $info = $this->informasi();
        Storage::disk('local')->put($info->file_path, UploadedFile::fake()->create('uji.pdf')->get());

        $pembaca = $this->orang('staff', $this->dept(), 'Non-Staff Pembaca');

        $this->actingAs($pembaca)->get(route('informasi.file', $info))->assertOk();
        $this->actingAs($pembaca)->get(route('informasi.file', $info))->assertOk();

        $baris = InformasiRead::where('informasi_id', $info->id)->get();

        $this->assertCount(1, $baris, 'dua kunjungan satu orang = satu baris');
        $this->assertSame(2, $baris[0]->read_count);
        $this->assertSame(2, $baris[0]->download_count, 'berkasnya sendiri yang dibuka, jadi tiap kunjungan = paparan isi');
        $this->assertSame('web', $baris[0]->platform);
    }

    /** Pengunggahnya sendiri tak pernah dihitung sebagai pembaca. */
    public function test_pengunggah_tak_dihitung(): void
    {
        Storage::fake('local');
        $info = $this->informasi();
        Storage::disk('local')->put($info->file_path, UploadedFile::fake()->create('uji.pdf')->get());

        $this->actingAs($this->admin())->get(route('informasi.file', $info))->assertOk();

        $this->assertSame(0, InformasiRead::where('informasi_id', $info->id)->count());
    }

    // ── Cakupan & lingkup ────────────────────────────────────────────────────

    /**
     * Lingkup departemen: pembaca DAN sasaran sama-sama disaring, sehingga
     * pembaca dari departemen lain tak pernah masuk hitungan SH.
     */
    public function test_cakupan_departemen_menyaring_pembaca_dan_sasaran(): void
    {
        $info = $this->informasi();
        $svc = app(InformasiDistribution::class);

        $ictmd = $this->dept();
        $she = $this->dept('SHE');

        $svc->catat($info, $this->orang('staff', $ictmd, 'Pembaca ICTMD'));
        $svc->catat($info, $this->orang('staff', $she, 'Pembaca SHE'));

        $daftar = collect([$info]);
        $global = $svc->cakupan($daftar)[$info->id];
        $lokal = $svc->cakupan($daftar, $ictmd->id)[$info->id];

        $this->assertSame(2, $global['pembaca'], 'lingkup gabungan menghitung kedua departemen');
        $this->assertSame(1, $lokal['pembaca'], 'lingkup ICTMD hanya menghitung pembacanya sendiri');
        $this->assertLessThan($global['sasaran'], $lokal['sasaran'], 'sasaran ikut menyempit');
        $this->assertLessThanOrEqual($lokal['sasaran'], $lokal['pembaca'], 'pembaca tak pernah melebihi sasaran selingkupnya');
    }

    /** Sasaran kosong → persen 0, bukan pembagian nol. */
    public function test_sasaran_kosong_tak_membagi_nol(): void
    {
        $info = $this->informasi();

        // Departemen yang tak dihuni satu pun pengguna aktif: id yang mustahil ada.
        $c = app(InformasiDistribution::class)->cakupan(collect([$info]), 99999)[$info->id];

        $this->assertSame(0, $c['sasaran']);
        $this->assertSame(0, $c['persen']);
    }

    /**
     * Admin IT bukan sasaran distribusi di mana pun angkanya muncul: sasaran(),
     * cakupan(), maupun rincian per departemen. Kalau hanya salah satunya yang
     * mengecualikannya, dua halaman menampilkan dua kebenaran atas pertanyaan
     * yang sama — dan tak satu pun terlihat salah.
     */
    public function test_admin_it_bukan_sasaran_di_ketiga_rumus(): void
    {
        $info = $this->informasi();
        $svc = app(InformasiDistribution::class);
        $ictmd = $this->dept();

        // Admin dipindahkan ke ICTMD (ikut di-rollback transaksi test) supaya
        // ia benar-benar berpeluang terhitung di lingkup departemen itu.
        $admin = $this->admin();
        $admin->forceFill(['department_id' => $ictmd->id])->save();

        $this->assertNotContains($admin->id, $svc->sasaran($info, $ictmd->id)->pluck('id')->all());

        // Admin membuka posternya sendiri tak pernah tercatat (ia pengunggahnya),
        // jadi yang diuji di sini pembaca Admin atas informasi orang lain.
        $lain = $this->orang('section_head', $ictmd, 'SH Pengunggah Informasi');
        $info->forceFill(['uploaded_by' => $lain->id])->save();

        $sebelum = $svc->cakupan(collect([$info]), $ictmd->id)[$info->id];
        $svc->catat($info, $admin);
        $sesudah = $svc->cakupan(collect([$info]), $ictmd->id)[$info->id];

        $this->assertSame($sebelum['pembaca'], $sesudah['pembaca'], 'bacaan Admin IT tak menaikkan pembilang');
        $this->assertNotContains($admin->id, $svc->rincian($info, $ictmd->id)['sudah']->pluck('user_id')->all());

        $barisIctmd = collect($svc->perDept(collect([$info]))[$info->id])->firstWhere('dept', $ictmd->code);
        $this->assertSame(
            $svc->sasaran($info, $ictmd->id)->count(),
            $barisIctmd['sasaran'],
            'penyebut per departemen harus sama dengan jumlah sasaran()'
        );
    }

    /** rincian() memisahkan yang sudah & belum membuka, tersaring departemen. */
    public function test_rincian_memisahkan_sudah_dan_belum(): void
    {
        $info = $this->informasi();
        $svc = app(InformasiDistribution::class);
        $ictmd = $this->dept();

        $pembaca = $this->orang('staff', $ictmd, 'Non-Staff Sudah Membuka');
        $belum = $this->orang('staff', $ictmd, 'Non-Staff Belum Membuka');
        $seberang = $this->orang('staff', $this->dept('SHE'), 'Non-Staff SHE');

        $svc->catat($info, $pembaca);
        $svc->catat($info, $seberang);

        $r = $svc->rincian($info, $ictmd->id);

        $this->assertContains($pembaca->id, $r['sudah']->pluck('user_id')->all());
        $this->assertContains($belum->id, $r['belum']->pluck('id')->all());
        $this->assertNotContains($pembaca->id, $r['belum']->pluck('id')->all());
        $this->assertNotContains($seberang->id, $r['sudah']->pluck('user_id')->all(), 'departemen lain tak bocor');
        $this->assertNotContains($seberang->id, $r['belum']->pluck('id')->all());
    }

    // ── Halaman & widget ─────────────────────────────────────────────────────

    /** Halaman rincian: nama orangnya terbaca oleh yang berwenang, tertutup bagi Non-Staff. */
    public function test_halaman_rincian_informasi(): void
    {
        $info = $this->informasi();
        $ictmd = $this->dept();
        $belum = $this->orang('staff', $ictmd, 'Non-Staff Tertinggal');
        $sh = $this->orang('section_head', $ictmd, 'SH Rincian Informasi');

        // Dulu `assertSee('Belum Membaca')` pada markup Blade. Judul kolomnya
        // kini dirangkai `components/dokumen/RincianPembaca.tsx`, jadi yang
        // diperiksa adalah props yang mengisinya — dan itu justru lebih tepat:
        // nama orangnya harus ada di sisi BELUM, bukan sekadar di suatu tempat
        // dalam payload.
        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('documents.rincianInformasi', $info))->assertOk()
        );

        $this->assertSame($info->judul, $props['informasi']['judul']);
        $this->assertContains($belum->name, collect($props['rincian']['belum'])->pluck('nama')->all());

        $this->actingAs($this->orang('staff', $ictmd, 'Non-Staff Rincian'))
            ->get(route('documents.rincianInformasi', $info))
            ->assertForbidden();
    }

    /** PJO bisa menyempitkan rincian ke satu departemen lewat pemilihnya. */
    public function test_rincian_informasi_pjo_bisa_disempitkan_per_departemen(): void
    {
        $info = $this->informasi();
        $she = $this->orang('staff', $this->dept('SHE'), 'Non-Staff SHE Rincian');
        $pjo = $this->aktorPjo();

        $this->actingAs($pjo)->get(route('documents.rincianInformasi', $info))
            ->assertOk()->assertSee($she->name);

        $this->actingAs($pjo)
            ->get(route('documents.rincianInformasi', [$info, 'department_id' => $this->dept()->id]))
            ->assertOk()->assertDontSee($she->name);
    }

    /** PJO (`document.view_all`) melihat daftar informasi + rincian 7 departemen. */
    public function test_halaman_informasi_untuk_pjo_memuat_rincian_per_departemen(): void
    {
        $info = $this->informasi();

        // Dulu `assertSee('Distribusi Informasi')` + `viewData('perDept')`.
        // Judul halaman & tombol "Per Departemen" kini dirangkai TSX, dan
        // `perDept` menempel di barisnya masing-masing — bukan peta kedua yang
        // harus dicocokkan ulang di klien.
        $props = $this->propsInertia(
            $this->actingAs($this->aktorPjo())
                ->get(route('documents.distribution', ['sumber' => 'informasi']))
                ->assertOk()
        );

        $this->assertSame('informasi', $props['sumber']);

        $baris = collect($props['informasi'])->firstWhere('id', $info->id);

        $this->assertNotNull($baris, 'informasinya ikut terkirim');
        $this->assertSame($info->judul, $baris['judul']);
        $this->assertNotEmpty($baris['perDept'], 'PJO mendapat rincian per departemen');
    }

    /** SH melihat halaman yang sama TANPA rincian per departemen (lingkupnya satu). */
    public function test_halaman_informasi_untuk_sh_tanpa_rincian_per_departemen(): void
    {
        $this->informasi();
        $sh = $this->orang('section_head', $this->dept(), 'SH Distribusi Informasi');

        $props = $this->propsInertia(
            $this->actingAs($sh)
                ->get(route('documents.distribution', ['sumber' => 'informasi']))
                ->assertOk()
        );

        // Tiap baris membawa `perDept`-nya sendiri, dan bagi SH ia KOSONG —
        // penyingkap "Per Departemen" karena itu tak pernah terbit.
        $this->assertNotEmpty($props['informasi']);

        foreach ($props['informasi'] as $baris) {
            $this->assertSame([], $baris['perDept']);
        }

        $this->assertFalse($props['canAll']);
    }

    /** Non-Staff tak berwenang atas halaman Distribusi — sumber apa pun. */
    public function test_non_staff_ditolak(): void
    {
        $staff = $this->orang('staff', $this->dept(), 'Non-Staff Distribusi');

        $this->actingAs($staff)->get(route('documents.distribution', ['sumber' => 'informasi']))->assertForbidden();

        // Kartunya pun tak dibangun sama sekali — bukan dibangun lalu
        // disembunyikan. (Diperiksa lewat props, sebab tulisan "Distribusi"
        // juga muncul di tempat lain di dashboard.)
        $this->assertNull(
            $this->propsInertia(
                $this->actingAs($staff)->get(route('dashboard'))->assertOk()
            )['distribusiWidget'],
        );
    }

    /** Widget dashboard memuat KEDUA panel server-side. */
    /**
     * Widget dashboard memuat DUA panel: Dokumen Mutu & Informasi.
     *
     * Saklar sumber ("Dokumen Mutu" / "Informasi") sengaja hanya terbit bila
     * KEDUA sumber ada isinya — tombol yang menuju panel kosong cuma menjebak
     * (`partials/_widget-distribusi`). Karena itu test ini wajib menyediakan
     * kedua-duanya. Dulu ia hanya membuat sisi Informasi dan hijau selama basis
     * data uji KEBETULAN memuat dokumen mutu terbit; begitu isinya kosong ia
     * merah tanpa satu pun kode berubah.
     */
    public function test_widget_dashboard_memuat_dua_panel(): void
    {
        $info = $this->informasi();
        $dept = $this->dept();
        $sh = $this->orang('section_head', $dept, 'SH Widget Informasi');

        // Sisi Dokumen Mutu — tanpa ini panelnya cuma satu dan saklarnya absen.
        $doc = app(DocumentService::class)->createDraft(
            $this->aktorGl(), DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Widget Distribusi'
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        $widget = $this->propsInertia(
            $this->actingAs($sh)->get(route('dashboard'))->assertOk()
        )['distribusiWidget'];

        $this->assertNotNull($widget['mutu'], 'panel Dokumen Mutu ikut dibangun');
        $this->assertNotNull($widget['informasi'], 'panel Informasi ikut dibangun');

        // Judul informasinya benar-benar ikut terkirim — dulu `assertSee`,
        // yang sejak halaman ini Inertia hanya membuktikan teksnya ada di
        // suatu tempat dalam payload, bukan di panel yang benar.
        $this->assertContains(
            $info->judul,
            collect($widget['informasi']['baris'])->pluck('judul')->all(),
        );
    }

    /** API mobile: `?sumber=informasi` memakai BENTUK JSON yang sama. */
    public function test_api_sumber_informasi_bentuknya_sama(): void
    {
        $info = $this->informasi();

        $r = $this->actingAs($this->aktorPjo(), 'sanctum')
            ->getJson(route('api.distribusi.index', ['sumber' => 'informasi']))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'no_dokumen', 'judul', 'jenis', 'departemen', 'berlaku_sejak', 'pembaca', 'sasaran', 'persen', 'unduhan']],
                'meta' => ['total', 'lingkup', 'terkunci_dept', 'rendah'],
            ]);

        $this->assertContains($info->nomor, collect($r->json('data'))->pluck('no_dokumen')->all());
    }
}
