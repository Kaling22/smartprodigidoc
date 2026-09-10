<?php

namespace Tests\Feature;

use App\Models\Informasi;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Menu Informasi (butir 3) — upload-only, dibaca semua orang.
 *
 * Yang dijaga di sini adalah empat janji yang rusak tanpa gejala:
 *
 *  1. **Semua akun aktif bisa membaca**, lintas departemen & jabatan. Kalau
 *     suatu hari izin baca ditambahkan "demi rapi", Non-Staff — yang justru
 *     jadi sasaran kebijakan & poster — terkunci di luar.
 *  2. **Hanya `informasi.manage` yang bisa mengunggah/menghapus.**
 *  3. **Kolom mengikuti kategori**, dibaca dari data sistem lama. Kiriman yang
 *     memuat kolom di luar kategorinya ditolak, bukan disimpan diam-diam ke
 *     baris yang tak akan pernah menampilkannya.
 *  4. **Perbarui menurunkan versi lama, tak menghapusnya**, dan hanya boleh ada
 *     SATU baris berlaku per (kategori, nomor).
 */
class InformasiTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    private function nonStaff(): User
    {
        return $this->aktorNonStaff();
    }

    private function pdf(string $nama = 'kebijakan.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    private function nomorBaru(): string
    {
        return 'INF-UJI-'.Str::upper(Str::random(8));
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function unggah(array $ubah = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin())->post(route('informasi.store'), array_merge([
            'kategori' => 'kebijakan',
            'nomor' => $this->nomorBaru(),
            'judul' => 'Kebijakan Uji',
            'edisi' => 4,
            'no_revisi' => 2,
            'berkas' => $this->pdf(),
        ], $ubah));
    }

    private function terakhir(): Informasi
    {
        return Informasi::latest('id')->firstOrFail();
    }

    // ── Akses ────────────────────────────────────────────────────────────────

    /**
     * Non-Staff — jabatan paling bawah, read-only atas dokumen mutu — TETAP
     * bisa membaca Informasi dan membuka berkasnya. Inilah inti menu ini.
     */
    public function test_non_staff_bisa_membaca_dan_membuka_berkas(): void
    {
        Storage::fake('local');
        $this->unggah();
        $info = $this->terakhir();

        $this->actingAs($this->nonStaff())
            ->get(route('informasi.index', ['kategori' => 'kebijakan']))
            ->assertOk()->assertSee($info->judul);

        $this->actingAs($this->nonStaff())
            ->get(route('informasi.file', $info))->assertOk();
    }

    /** Setiap kategori AKTIF bisa dibuka; yang tak dikenal 404, bukan 500. */
    public function test_sepuluh_kategori_terbuka_dan_kategori_asing_404(): void
    {
        // Daftarnya dari tabel `informasi_kategori` (Fase 5b), bukan konstanta:
        // jumlahnya memang bisa bertambah dari layar Master Data, dan test yang
        // mematok angka sepuluh akan merah karena Admin bekerja, bukan karena
        // ada yang rusak.
        foreach (array_keys(Informasi::kategori(aktifSaja: true)) as $slug) {
            // Nama kategorinya dibaca dari PROPS, bukan markup: sejak halaman ini
            // Inertia, tanda & di "SERTIFIKAT & SIO" ter-escape jadi \u0026 di
            // dalam payload JSON, sehingga assertSee gagal karena EJAAN — bukan
            // karena kategorinya tak terbuka.
            $props = $this->propsInertia(
                $this->actingAs($this->nonStaff())
                    ->get(route('informasi.index', ['kategori' => $slug]))
                    ->assertOk()
            );

            $this->assertSame(Informasi::labelKategori($slug), $props['label']);
            $this->assertSame($slug, $props['kategori']);
        }

        $this->actingAs($this->nonStaff())
            ->get(route('informasi.index', ['kategori' => 'tidak-ada']))
            ->assertNotFound();
    }

    /**
     * Pemegang `informasi.manage` MELIHAT ketiga tombolnya.
     *
     * Pasangan wajib bagi test "Non-Staff tak melihat tombol" di bawahnya:
     * tanpa sisi positifnya, sebuah `@can` yang keliru — atau izin yang tak
     * pernah ter-seed — lolos sebagai "berhasil menyembunyikan tombol", dan
     * yang terlihat di layar adalah halaman tanpa satu pun cara mengunggah.
     */
    public function test_pemegang_izin_melihat_tombol_tambah_perbarui_hapus(): void
    {
        Storage::fake('local');
        $this->unggah();
        $info = $this->terakhir();

        // Ketiga tombol digambar dari SATU props `kelola` (dulu tiga @can
        // terpisah di Blade). Barisnya diperiksa juga: tombol Perbarui & Hapus
        // menempel pada baris, bukan pada halaman.
        $props = $this->propsInertia(
            $this->actingAs($this->admin())
                ->get(route('informasi.index', ['kategori' => 'kebijakan']))
                ->assertOk()
        );

        $this->assertTrue($props['kelola']);
        $this->assertSame('KEBIJAKAN', $props['label']);
        $this->assertContains($info->id, collect($props['daftar']['data'])->pluck('id')->all());
    }

    /**
     * GL ikut mengunggah Informasi.
     *
     * Keputusan B2 semula "Admin saja DULU"; pemilik membukanya ke GL. Aman
     * karena Informasi tak punya alur mutu — tak ada status yang bisa
     * dilompati, tak ada persetujuan yang bisa dilewati. SH/DH & PJO sengaja
     * BELUM diberi, dan bagian kedua test inilah yang menjaga batas itu tetap
     * disengaja alih-alih ikut terbawa.
     */
    public function test_gl_boleh_mengunggah_tapi_sh_belum(): void
    {
        Storage::fake('local');
        $gl = $this->aktorGl();

        $this->assertTrue($gl->can('informasi.manage'));

        $this->assertTrue($this->propsInertia(
            $this->actingAs($gl)->get(route('informasi.index', ['kategori' => 'kebijakan']))->assertOk()
        )['kelola']);

        $this->actingAs($gl)->post(route('informasi.store'), [
            'kategori' => 'kebijakan',
            'nomor' => $this->nomorBaru(),
            'judul' => 'Kebijakan Unggahan GL',
            'edisi' => 1,
            'no_revisi' => 0,
            'berkas' => $this->pdf(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('Kebijakan Unggahan GL', $this->terakhir()->judul);

        $sh = $this->aktorSh();
        $this->assertFalse($sh->can('informasi.manage'));
        $this->actingAs($sh)->get(route('informasi.create', ['kategori' => 'kebijakan']))->assertForbidden();
    }

    /** Non-Staff tak melihat satu pun tombol kelola, dan rutenya pun tertutup. */
    public function test_non_staff_tak_bisa_mengunggah_atau_menghapus(): void
    {
        Storage::fake('local');
        $this->unggah();
        $info = $this->terakhir();
        $staf = $this->nonStaff();

        // Dibaca dari props, bukan markup: tombolnya kini digambar React, jadi
        // assertDontSee akan hijau bahkan bila `kelola` bocor jadi true — hijau
        // trivial yang tak pernah bisa merah lagi.
        $this->assertFalse($this->propsInertia(
            $this->actingAs($staf)->get(route('informasi.index', ['kategori' => 'kebijakan']))->assertOk()
        )['kelola']);

        $this->actingAs($staf)->get(route('informasi.create', ['kategori' => 'kebijakan']))->assertForbidden();
        $this->actingAs($staf)->post(route('informasi.store'), [])->assertForbidden();
        $this->actingAs($staf)->delete(route('informasi.destroy', $info))->assertForbidden();
    }

    /** Berkas tak bisa diambil tanpa login sama sekali. */
    public function test_berkas_tertutup_tanpa_login(): void
    {
        Storage::fake('local');
        $this->unggah();
        $info = $this->terakhir();

        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->flushSession();

        $this->get(route('informasi.file', $info))->assertRedirect(route('login'));
        $this->get('/storage/'.$info->file_path)->assertNotFound();
    }

    // ── Unggah & validasi ────────────────────────────────────────────────────

    public function test_admin_mengunggah_kebijakan(): void
    {
        Storage::fake('local');
        $this->unggah(['judul' => 'Kebijakan K3 Uji'])->assertRedirect();

        $info = $this->terakhir();

        $this->assertSame('kebijakan', $info->kategori);
        $this->assertSame('Kebijakan K3 Uji', $info->judul);
        $this->assertSame(4, $info->edisi);
        $this->assertSame(2, $info->no_revisi);
        $this->assertTrue($info->berlaku);
        $this->assertStringStartsWith('informasi/kebijakan/', $info->file_path);
        Storage::disk('local')->assertExists($info->file_path);
    }

    /**
     * MEMO tak punya edisi/revisi/tanggal efektif — mengirimnya DITOLAK.
     *
     * Bukan sekadar diabaikan: kiriman yang memuatnya berarti formulir dan
     * aturannya sudah tak sinkron, dan menelannya diam-diam menyimpan angka
     * yang tak akan pernah tampil di layar mana pun.
     */
    public function test_kolom_di_luar_kategori_ditolak(): void
    {
        Storage::fake('local');

        $this->unggah(['kategori' => 'memo_external', 'edisi' => 3, 'no_revisi' => 1])
            ->assertSessionHasErrors('edisi');
    }

    /** Sebaliknya: kolom yang MEMANG dipakai kategori itu wajib diisi. */
    public function test_kolom_wajib_kategori_tak_boleh_kosong(): void
    {
        Storage::fake('local');

        // INSTRUKSI KTT memakai ketiganya; tanggal efektif sengaja tak dikirim.
        $this->unggah(['kategori' => 'instruksi_ktt', 'edisi' => 1, 'no_revisi' => 0])
            ->assertSessionHasErrors('tanggal_efektif');
    }

    /** MEMO tanpa kolom ekstra tersimpan bersih — ketiganya null. */
    public function test_memo_tersimpan_tanpa_kolom_ekstra(): void
    {
        Storage::fake('local');
        $this->unggah(['kategori' => 'memo_internal', 'edisi' => null, 'no_revisi' => null]);

        $info = $this->terakhir();

        $this->assertSame('memo_internal', $info->kategori);
        $this->assertNull($info->edisi);
        $this->assertNull($info->no_revisi);
        $this->assertNull($info->tanggal_efektif);
    }

    /** POSTER menerima gambar; kategori lain tidak. */
    public function test_poster_menerima_gambar_kategori_lain_tidak(): void
    {
        Storage::fake('local');

        $this->unggah([
            'kategori' => 'poster', 'edisi' => null, 'no_revisi' => null,
            'berkas' => UploadedFile::fake()->image('poster.jpg'),
        ])->assertSessionHasNoErrors();

        $this->assertStringStartsWith('image/', $this->terakhir()->file_mime);

        $this->unggah(['berkas' => UploadedFile::fake()->image('bukan-kebijakan.jpg')])
            ->assertSessionHasErrors('berkas');
    }

    /** Berkas ber-ekstensi .pdf tapi isinya bukan PDF → ditolak (magic bytes). */
    public function test_berkas_menyamar_pdf_ditolak(): void
    {
        Storage::fake('local');

        $this->unggah(['berkas' => UploadedFile::fake()->createWithContent('palsu.pdf', '<?php echo 1;')])
            ->assertSessionHasErrors('berkas');
    }

    /**
     * Nomor kembar lewat TAMBAH ditolak, dan pesannya mengarahkan ke Perbarui.
     *
     * Ketetapan pemilik: Tambah hanya untuk dokumen yang belum ada; yang sudah
     * ada diganti lewat Perbarui.
     */
    public function test_tambah_dengan_nomor_yang_sudah_ada_ditolak(): void
    {
        Storage::fake('local');
        $nomor = $this->nomorBaru();

        $this->unggah(['nomor' => $nomor])->assertSessionHasNoErrors();
        $this->unggah(['nomor' => $nomor])->assertSessionHasErrors('nomor');

        $this->assertSame(1, Informasi::senomor('kebijakan', $nomor)->count());
    }

    // ── Perbarui ─────────────────────────────────────────────────────────────

    /**
     * Perbarui: versi lama TURUN jadi riwayat (tidak dihapus), versi baru
     * berlaku dengan nomor yang sama. Tepat satu baris berlaku.
     */
    public function test_perbarui_menurunkan_versi_lama_jadi_riwayat(): void
    {
        Storage::fake('local');
        $nomor = $this->nomorBaru();
        $this->unggah(['nomor' => $nomor, 'judul' => 'Versi Pertama']);
        $lama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $lama), [
            'judul' => 'Versi Kedua',
            'edisi' => 4,
            'no_revisi' => 3,
            'berkas' => $this->pdf('baru.pdf'),
        ])->assertRedirect();

        $baru = $this->terakhir();

        $this->assertSame('Versi Kedua', $baru->judul);
        $this->assertSame($nomor, $baru->nomor, 'Nomor diwarisi, bukan diambil dari kiriman.');
        $this->assertTrue($baru->berlaku);
        $this->assertFalse($lama->refresh()->berlaku, 'Versi lama turun jadi riwayat...');
        $this->assertNull($lama->deleted_at, '...tapi TIDAK dihapus.');
        $this->assertSame(1, Informasi::senomor('kebijakan', $nomor)->berlaku()->count());
    }

    /** Kategori & nomor pada kiriman Perbarui ditolak — keduanya diwarisi. */
    public function test_perbarui_menolak_kategori_dan_nomor_dari_kiriman(): void
    {
        Storage::fake('local');
        $this->unggah();
        $lama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $lama), [
            'kategori' => 'poster',
            'nomor' => 'NOMOR-LAIN',
            'judul' => 'Coba Pindah Kategori',
            'edisi' => 4, 'no_revisi' => 3,
            'berkas' => $this->pdf(),
        ])->assertSessionHasErrors(['kategori', 'nomor']);
    }

    /**
     * Riwayat tampil TERSARANG di bawah barisnya sendiri, di halaman yang sama.
     *
     * Rancangan semula memisahkannya ke tab `?riwayat=1` dan itu keliru:
     * riwayat empat versi dari SATU dokumen berbaur dengan riwayat dokumen lain
     * di satu daftar datar, sehingga makin rajin sebuah kebijakan diperbarui,
     * makin tak terbaca daftar itu. Versi lama hanya punya arti DI SEBELAH
     * versi yang menggantikannya.
     */
    public function test_riwayat_tersarang_di_bawah_barisnya_sendiri(): void
    {
        Storage::fake('local');
        $nomor = $this->nomorBaru();
        $this->unggah(['nomor' => $nomor, 'judul' => 'Judul Versi Lama']);
        $lama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $lama), [
            'judul' => 'Judul Versi Baru', 'edisi' => 4, 'no_revisi' => 3, 'berkas' => $this->pdf(),
        ]);

        // Satu halaman, tanpa parameter tambahan: versi berlaku DAN riwayatnya.
        // Riwayat MENEMPEL di barisnya (bukan peta kedua ber-key nomor), jadi
        // yang diperiksa adalah kedalaman itu — bukan teks "Riwayat (1)" yang
        // kini dirakit React dan mustahil merah lagi.
        $baris = collect($this->propsInertia(
            $this->actingAs($this->admin())
                ->get(route('informasi.index', ['kategori' => 'kebijakan', 'q' => $nomor]))
                ->assertOk()
        )['daftar']['data']);

        $this->assertCount(1, $baris, 'Daftar utama memuat versi BERLAKU saja.');
        $this->assertSame('Judul Versi Baru', $baris[0]['judul']);
        $this->assertCount(1, $baris[0]['riwayat']);
        $this->assertSame('Judul Versi Lama', $baris[0]['riwayat'][0]['judul']);
        $this->assertSame($nomor, $baris[0]['riwayat'][0]['nomor']);
    }

    /**
     * Riwayat dokumen A tidak ikut muncul di bawah dokumen B.
     *
     * Inilah cacat yang persis dibuang bersama tab lama, jadi ia dijaga
     * eksplisit: pengelompokannya per NOMOR, bukan per kategori.
     */
    public function test_riwayat_tak_bocor_ke_dokumen_lain(): void
    {
        Storage::fake('local');
        $nomorA = $this->nomorBaru();
        $this->unggah(['nomor' => $nomorA, 'judul' => 'Dokumen A Versi Lama']);
        $a = $this->terakhir();
        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $a), [
            'judul' => 'Dokumen A Versi Baru', 'edisi' => 4, 'no_revisi' => 3, 'berkas' => $this->pdf(),
        ]);

        $nomorB = $this->nomorBaru();
        $this->unggah(['nomor' => $nomorB, 'judul' => 'Dokumen B Tanpa Riwayat']);

        // Disaring ke B saja: riwayat A tak boleh ikut terbawa, dan B tak boleh
        // mengaku punya riwayat.
        $baris = collect($this->propsInertia(
            $this->actingAs($this->admin())
                ->get(route('informasi.index', ['kategori' => 'kebijakan', 'q' => $nomorB]))
                ->assertOk()
        )['daftar']['data']);

        $this->assertCount(1, $baris);
        $this->assertSame('Dokumen B Tanpa Riwayat', $baris[0]['judul']);
        // B tak boleh mengaku punya riwayat, dan riwayat A tak boleh ikut
        // terbawa. Diperiksa pada LARIK riwayat baris itu — di markup, tombolnya
        // dirakit React sehingga assertDontSee-nya mustahil merah lagi.
        $this->assertSame([], $baris[0]['riwayat']);
        $this->assertStringNotContainsString($nomorA, json_encode($baris));
    }

    /** Yang sudah jadi riwayat tak bisa diperbarui lagi — hanya versi berlaku. */
    public function test_riwayat_tak_bisa_diperbarui(): void
    {
        Storage::fake('local');
        $this->unggah();
        $lama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $lama), [
            'judul' => 'Versi Kedua', 'edisi' => 4, 'no_revisi' => 3, 'berkas' => $this->pdf(),
        ]);

        $this->actingAs($this->admin())->get(route('informasi.perbarui', $lama))->assertNotFound();
    }

    // ── Hapus ────────────────────────────────────────────────────────────────

    /**
     * Hapus "versi ini saja": riwayat TERBARU naik menggantikannya, sehingga
     * nomor itu tak pernah kosong hanya karena unggahan terakhir salah.
     */
    public function test_hapus_versi_menaikkan_riwayat_terbaru(): void
    {
        Storage::fake('local');
        $nomor = $this->nomorBaru();
        $this->unggah(['nomor' => $nomor, 'judul' => 'Versi Pertama']);
        $pertama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $pertama), [
            'judul' => 'Versi Kedua', 'edisi' => 4, 'no_revisi' => 3, 'berkas' => $this->pdf(),
        ]);
        $kedua = $this->terakhir();

        $this->actingAs($this->admin())
            ->delete(route('informasi.destroy', $kedua), ['cakupan' => 'versi'])
            ->assertRedirect();

        $this->assertNotNull($kedua->fresh()->deleted_at);
        $this->assertTrue($pertama->refresh()->berlaku, 'Versi sebelumnya naik menggantikannya.');
    }

    /** Hapus "seluruhnya": versi berlaku DAN riwayatnya sama-sama hilang. */
    public function test_hapus_semua_membuang_seluruh_riwayat(): void
    {
        Storage::fake('local');
        $nomor = $this->nomorBaru();
        $this->unggah(['nomor' => $nomor, 'judul' => 'Versi Pertama']);
        $pertama = $this->terakhir();

        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $pertama), [
            'judul' => 'Versi Kedua', 'edisi' => 4, 'no_revisi' => 3, 'berkas' => $this->pdf(),
        ]);
        $kedua = $this->terakhir();

        $this->actingAs($this->admin())
            ->delete(route('informasi.destroy', $kedua), ['cakupan' => 'semua'])
            ->assertRedirect();

        $this->assertSame(0, Informasi::senomor('kebijakan', $nomor)->count());
        $this->assertNotNull($pertama->fresh()->deleted_at);
        $this->assertNotNull($kedua->fresh()->deleted_at);
    }

    /**
     * Modul ini tak menyentuh dokumen mutu sama sekali — halaman dokumen tetap
     * berjalan, dan menu Informasi muncul di sidebar semua peran.
     */
    public function test_menu_informasi_tampil_dan_dokumen_mutu_tak_terganggu(): void
    {
        // Menunya dibaca dari props `navigation` (sidebar sudah pindah ke sana);
        // di dalam payload JSON URL-nya ter-escape, jadi `assertSee(route(...))`
        // akan gagal karena ejaan, bukan karena menunya hilang.
        $this->assertContains(
            route('informasi.index', ['kategori' => 'kebijakan']),
            collect($this->menuSidebar(
                $this->actingAs($this->nonStaff())->get(route('dashboard'))->assertOk()
            ))->pluck('href')->all(),
        );

        $this->actingAs($this->admin())->get(route('documents.published'))->assertOk();
    }

    /**
     * Roll-over Edisi/Revisi di modul Informasi mengikuti aturan yang sama
     * dengan dokumen mutu: revisi 0..5, yang keenam naik Edisi (K-B).
     *
     * Modul ini sempat punya rumus keduanya sendiri (`no_revisi + 1` telanjang
     * di formulir, `max:99` di Form Request), sehingga induk Edisi 1 Rev 5
     * memuat "Edisi 1 Rev 6" — angka yang menurut CLAUDE.md §7 tak pernah ada.
     */
    public function test_perbarui_menggulung_revisi_keenam_jadi_edisi_berikutnya(): void
    {
        Storage::fake('local');
        $this->unggah(['edisi' => 1, 'no_revisi' => 5, 'judul' => 'Kebijakan Rev Lima']);
        $lama = $this->terakhir();

        // Formulir Perbarui menjanjikan Edisi 2 Rev 1 (W-5), bukan Edisi 1 Rev 6.
        // Angkanya dihitung SERVER (DocumentService::nextEditionRevision) dan
        // dikirim sebagai props — form React hanya memakainya sebagai nilai awal,
        // jadi di sinilah rumusnya benar-benar terkunci (pakem P5).
        $props = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('informasi.perbarui', $lama))->assertOk()
        );

        $this->assertSame(2, $props['edisiBaru']);
        $this->assertSame(1, $props['revisiBaru']);
        $this->assertSame('Edisi 1 Rev 5', $props['induk']['revisi']);

        // Dan angka 6 tetap tertolak walau dikirim langsung, melewati formulir.
        $this->actingAs($this->admin())->post(route('informasi.perbarui.store', $lama), [
            'judul' => 'Kebijakan Rev Enam',
            'edisi' => 1,
            'no_revisi' => 6,
            'berkas' => $this->pdf('baru.pdf'),
        ])->assertSessionHasErrors('no_revisi');
    }
}
