<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Dua widget kolom kanan dashboard: "Perjalanan Dokumen" dan kartu masukan.
 *
 * Yang dikunci di sini adalah LINGKUPNYA — siapa melihat apa. Bentuk visualnya
 * (rel bersegmen, kutipan) sengaja tak diuji: itu berubah tiap kali tampilan
 * dirapikan, dan mengujinya hanya membuat test rapuh tanpa menjaga apa pun.
 */
class DashboardWidgetTest extends TestCase
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

    private function makeUser(string $jabatan, Department $dept, string $nama): User
    {
        static $n = 0;
        $n++;
        $u = User::create([
            'name' => $nama, 'nrp' => "DW-{$n}", 'jabatan' => $jabatan,
            'department_id' => $dept->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole($jabatan);

        return $u;
    }

    /** Dokumen yang sudah dikirim dan sedang menunggu peninjau. */
    private function kirimkan(User $gl, Department $dept, string $judul, ?User $peninjau = null): Document
    {
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, $judul
        );
        $doc->update([
            'status' => 'waiting_for_review',
            'submitted_at' => now()->subDays(5),
            'reviewer_id' => $peninjau?->id,
        ]);

        return $doc->refresh();
    }

    /**
     * Id dokumen yang masuk kartu "Perjalanan Dokumen" milik seseorang.
     *
     * Dibaca dari PROPS, BUKAN dari HTML: Log Aktivitas di kolom yang sama ikut
     * memuat nomor dokumen sedepartemen, sehingga memeriksa seluruh HTML akan
     * salah menuduh kartu ini bocor. (Dulu `viewData('menungguDiMeja')`; props
     * Inertia sudah melewati JSON, jadi tiap baris di sini array — `pluck`
     * dengan notasi titik tetap bekerja.)
     *
     * @return array<int, int>
     */
    private function idDiMeja(User $user): array
    {
        return collect($this->baris($user))->pluck('dokumen.id')->all();
    }

    /** @return array<int, array<string, mixed>> baris "Perjalanan Dokumen" */
    private function baris(User $user): array
    {
        return $this->propsInertia(
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
        )['menungguDiMeja'];
    }

    /**
     * Kartu GL memuat dokumennya sendiri dan TIDAK memuat dokumen GL lain
     * sedepartemen — lingkupnya sama dengan menu "Dokumen Saya".
     */
    public function test_kartu_meja_gl_hanya_memuat_dokumennya_sendiri(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $saya = $this->makeUser('group_leader', $dept, 'GL Saya');
        $lain = $this->makeUser('group_leader', $dept, 'GL Lain');

        $milikSaya = $this->kirimkan($saya, $dept, 'SOP Punya Saya');
        $milikLain = $this->kirimkan($lain, $dept, 'SOP Punya Orang Lain');

        $ids = $this->idDiMeja($saya);

        $this->assertContains($milikSaya->id, $ids, 'dokumen buatannya sendiri muncul');
        $this->assertNotContains($milikLain->id, $ids, 'dokumen GL lain sedepartemen TIDAK muncul');
    }

    /**
     * SELURUH dokumen berjalan ikut, tak ada yang dipotong diam-diam.
     *
     * Dulu tes ini menuntut TEPAT 4 baris (spec v2 L1): angka itu yang membuat
     * tepi bawah kartu V1 jatuh di batas kalender–Log Aktivitas, jadi ia bagian
     * dari tata letak. V1 dihapus 2026-09-07 dan `LacakStatus` V2 menggulir
     * tabelnya di dalam kartu — tinggi kartunya tak lagi ditentukan jumlah
     * baris, jadi memotongnya cuma menyembunyikan dokumen dari orang yang
     * seharusnya menindaknya.
     *
     * Ia dibalik, bukan dihapus (§14c): yang dijaga sekarang justru KELENGKAPAN,
     * dan cara gagalnya sama diamnya — dokumen keenam yang lenyap dari meja
     * tak melempar galat apa pun. Plafon kerasnya dijaga terpisah di
     * `DasborV2Test::test_baris_meja_tetap_berplafon`.
     */
    public function test_kartu_meja_memuat_semua_dokumen_berjalan(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $dept, 'GL Enam Dokumen');

        for ($i = 1; $i <= 6; $i++) {
            $this->kirimkan($gl, $dept, "SOP Berjalan {$i}");
        }

        $this->assertCount(6, $this->idDiMeja($gl),
            'Ada dokumen berjalan yang tak sampai ke kartu meja — barisnya dipotong lagi.');
    }

    /**
     * Kolom Kemajuan menyebut TAHAP + PEMEGANGNYA, dan MUNDUR saat dokumen
     * dikembalikan.
     *
     * Dua-duanya bagian dari janji kartu ini: "Menunggu tinjauan SH" menyuruh
     * orang bertindak, dan dokumen yang ditolak harus terlihat mundur ke meja
     * penyusunnya — bukan lenyap dari kartu seperti dulu.
     */
    public function test_kemajuan_menyebut_tahap_dan_mundur_saat_ditolak(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $dept, 'GL Kemajuan');
        $sh = $this->makeUser('section_head', $dept, 'SH Peninjau Kemajuan');

        $ditinjau = $this->kirimkan($gl, $dept, 'SOP Sedang Ditinjau', $sh);
        $ditolak = $this->kirimkan($gl, $dept, 'SOP Dikembalikan', $sh);
        $ditolak->update(['status' => 'rejected']);

        $baris = collect($this->baris($gl))->keyBy(fn ($b) => $b['dokumen']['id']);

        $this->assertSame('Menunggu tinjauan SH', $baris[$ditinjau->id]['menunggu']);
        $this->assertSame('Ditinjau', $baris[$ditinjau->id]['tahap']);

        $this->assertArrayHasKey($ditolak->id, $baris->all(), 'dokumen ditolak tetap tampil');
        $this->assertSame('Menunggu revisi GL', $baris[$ditolak->id]['menunggu']);
        $this->assertSame('Dibuat', $baris[$ditolak->id]['tahap'], 'pitanya MUNDUR ke tahap penyusunan');
    }

    /** Dokumen yang ia bantu susun ikut muncul — sama seperti di "Dokumen Saya". */
    public function test_kartu_meja_gl_memuat_dokumen_yang_ia_bantu_susun(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $pembuat = $this->makeUser('group_leader', $dept, 'GL Pembuat');
        $pembantu = $this->makeUser('group_leader', $dept, 'GL Pembantu');

        $doc = $this->kirimkan($pembuat, $dept, 'SOP Disusun Berdua');
        $doc->authors()->create(['user_id' => $pembantu->id, 'is_primary' => false]);

        $this->assertContains($doc->id, $this->idDiMeja($pembantu));
    }

    /** SH melihat SELURUH dokumen berjalan di departemennya, dan hanya itu. */
    public function test_kartu_meja_sh_melihat_sedepartemen(): void
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();

        $sh = $this->makeUser('section_head', $ictmd, 'SH ICTMD');
        $glSedept = $this->makeUser('group_leader', $ictmd, 'GL Sedept');
        $glDeptLain = $this->makeUser('group_leader', $she, 'GL Dept Lain');

        $sedept = $this->kirimkan($glSedept, $ictmd, 'SOP Sedepartemen', $sh);
        $deptLain = $this->kirimkan($glDeptLain, $she, 'SOP Departemen Lain');

        $ids = $this->idDiMeja($sh);

        $this->assertContains($sedept->id, $ids);
        $this->assertNotContains($deptLain->id, $ids);
    }

    /**
     * Umur menghangat sendiri: ≤2 hari netral, 3–6 `--su-warn`, ≥7 `--su-danger`.
     *
     * Inilah satu-satunya hal dari kartu ini yang benar-benar dibaca sekilas,
     * jadi ambang batasnya dikunci — bukan bentuk relnya.
     */
    public function test_umur_dokumen_diwarnai_menurut_ambangnya(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $dept, 'GL Umur');

        foreach ([1, 5, 9] as $hari) {
            $this->kirimkan($gl, $dept, "SOP Umur {$hari}")
                ->update(['submitted_at' => now()->subDays($hari)]);
        }

        // Ambangnya kini dihitung SERVER dan dikirim sebagai `tingkat`; dulu ia
        // kelas Bootstrap yang dicocokkan dari HTML (`badge-soft-warning`).
        // Yang diuji tetap sama — di mana batas 3 dan 7 hari jatuh — tapi kini
        // pada angkanya, bukan pada nama kelas yang bisa berganti tiap kali
        // tampilan dirapikan.
        $tingkat = collect($this->baris($gl))->pluck('tingkat', 'umur');

        foreach ([1 => 'baru', 5 => 'sedang', 9 => 'lama'] as $hari => $harusnya) {
            $this->assertSame(
                $harusnya,
                $tingkat[$hari] ?? null,
                "umur {$hari} hari seharusnya bertingkat {$harusnya}",
            );
        }
    }

    /** Masukan terbit sebagai dokumen Berlaku di sebuah departemen. */
    private function masukan(User $pengirim, Department $dept, string $isi): DocumentFeedback
    {
        $gl = $this->makeUser('group_leader', $dept, 'GL Penulis '.$dept->code);
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Berlaku '.$dept->code
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return DocumentFeedback::create([
            'feedback_number' => 'MSK-TEST-'.$doc->id,
            'document_id' => $doc->id,
            'user_id' => $pengirim->id,
            'isi' => $isi,
            'status' => 'baru',
        ]);
    }

    /**
     * Kartu masukan memuat LEBIH dari empat kutipan, dan menggulung.
     *
     * Dulu `masukanWidget()` memasang `limit(4)` di dalam controller. Akibatnya
     * bukan sekadar "sedikit": dengan grid dua kolom, empat kutipan hanya
     * mengisi DUA BARIS, sehingga kartunya tak pernah bisa meluap — dan
     * permintaan pemilik "kalau banyak, buat agar bisa digulir" mustahil
     * dipenuhi berapa pun gaya CSS yang ditulis.
     *
     * Test ini menjaga dua hal sekaligus, dan keduanya akan gagal bila angka 4
     * itu kembali merayap ke dalam kode:
     *   1. batasnya datang dari KONFIGURASI, dan
     *   2. tubuh kartunya memakai `.pp-kutip-tubuh` — wadah yang menggulung,
     *      satu-satunya alasan angka besar tidak memanjangkan kartu.
     */
    public function test_widget_masukan_memuat_lebih_dari_empat_dan_menggulung(): void
    {
        config(['smartpro.dashboard.masukan_widget' => 20]);

        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $sh = $this->makeUser('section_head', $ictmd, 'SH Pembaca Banyak');
        $pengirim = $this->makeUser('staff', $ictmd, 'Non-Staff Pengirim');

        for ($i = 1; $i <= 7; $i++) {
            $this->masukan($pengirim, $ictmd, "Temuan lapangan nomor {$i}");
        }

        $isi = collect(
            $this->propsInertia($this->actingAs($sh)->get(route('dashboard'))->assertOk())['masukanWidget']
        )->pluck('isi');

        // Ketujuhnya ikut — termasuk yang ke-5 dst. yang dulu terpotong
        // `limit(4)` di dalam controller.
        for ($i = 1; $i <= 7; $i++) {
            $this->assertContains("Temuan lapangan nomor {$i}", $isi->all());
        }

        // Penjaga kedua yang dulu `assertSee('pp-kutip-tubuh')`: bahwa jumlah
        // yang dikirim BOLEH melampaui tinggi kartu, sebab kartunya menggulung.
        // Kelas CSS-nya tinggal di TSX sekarang, jadi yang dikunci di sini
        // batasnya — angka 4 tak boleh merayap kembali ke dalam kode.
        $this->assertGreaterThan(4, $isi->count());
    }

    /** Widget SH hanya memuat masukan departemennya sendiri. */
    public function test_widget_masukan_sh_hanya_departemennya(): void
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();

        $sh = $this->makeUser('section_head', $ictmd, 'SH Penindak');
        $staffSedept = $this->makeUser('staff', $ictmd, 'Non-Staff Sedept');
        $staffDeptLain = $this->makeUser('staff', $she, 'Non-Staff Dept Lain');

        $this->masukan($staffSedept, $ictmd, 'Rambu di simpang tiga sudah pudar');
        $this->masukan($staffDeptLain, $she, 'Masukan dari departemen sebelah');

        $isi = collect(
            $this->propsInertia($this->actingAs($sh)->get(route('dashboard'))->assertOk())['masukanWidget']
        )->pluck('isi')->all();

        $this->assertContains('Rambu di simpang tiga sudah pudar', $isi);
        $this->assertNotContains('Masukan dari departemen sebelah', $isi);
    }

    /**
     * PJO melihat masukan dari KETUJUH departemen.
     *
     * Ia tanpa departemen, jadi penyaringan `department_id` yang benar untuk
     * SH/DH justru selalu mengosongkan kartunya (CLAUDE.md §6).
     */
    public function test_widget_masukan_pjo_lintas_departemen(): void
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();
        $pjo = $this->aktorPjo();

        $this->masukan($this->makeUser('staff', $ictmd, 'Non-Staff ICT'), $ictmd, 'Masukan dari ICTMD');
        $this->masukan($this->makeUser('staff', $she, 'Non-Staff SHE'), $she, 'Masukan dari SHE');

        $isi = collect(
            $this->propsInertia($this->actingAs($pjo)->get(route('dashboard'))->assertOk())['masukanWidget']
        )->pluck('isi')->all();

        $this->assertContains('Masukan dari ICTMD', $isi);
        $this->assertContains('Masukan dari SHE', $isi);
    }

    /**
     * GL mendapat kartu Distribusi, TAPI tanpa tombol "Lihat semua": halaman
     * Distribusi tetap tertutup baginya, dan tautan yang berujung 403 lebih
     * buruk daripada tak ada tautan.
     */
    public function test_widget_distribusi_gl_tanpa_tautan_halaman(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = $this->makeUser('group_leader', $dept, 'GL Lihat Distribusi');

        // Satu dokumen Berlaku supaya kartunya punya isi.
        app(DocumentService::class)
            ->createDraft($gl, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Untuk Distribusi')
            ->update(['status' => 'published', 'published_at' => now()]);

        $this->actingAs($gl)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // GL MENDAPAT kartunya…
                ->has('distribusiWidget.mutu')
                // …tapi tanpa tombol "Lihat semua".
                ->where('distribusiWidget.bolehBukaHalaman', false)
            );

        // Dan halamannya sendiri tetap tertutup.
        $this->actingAs($gl)->get(route('documents.distribution'))->assertForbidden();
    }

    /** Non-Staff melihat kirimannya sendiri BESERTA balasan — pertama kali di web. */
    public function test_widget_masukan_non_staff_melihat_kiriman_dan_balasan(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $saya = $this->makeUser('staff', $dept, 'Non-Staff Saya');
        $oranglain = $this->makeUser('staff', $dept, 'Non-Staff Lain');

        $milikSaya = $this->masukan($saya, $dept, 'Tangga akses belum berpegangan');
        $milikSaya->update([
            'status' => 'ditolak',
            'balasan' => 'Sudah dijadwalkan perbaikan minggu depan.',
            'replied_at' => now(),
        ]);
        $this->masukan($oranglain, $dept, 'Kiriman milik orang lain');

        $masukan = collect(
            $this->propsInertia($this->actingAs($saya)->get(route('dashboard'))->assertOk())['masukanWidget']
        );

        $this->assertContains('Tangga akses belum berpegangan', $masukan->pluck('isi')->all());
        $this->assertContains('Sudah dijadwalkan perbaikan minggu depan.', $masukan->pluck('balasan')->all());
        $this->assertNotContains('Kiriman milik orang lain', $masukan->pluck('isi')->all());
    }

    /** Non-Staff tak pernah punya dokumen "di meja", jadi kartunya tak dirender. */
    public function test_non_staff_tak_mendapat_kartu_meja(): void
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $staff = $this->makeUser('staff', $ictmd, 'Non-Staff Tanpa Meja');
        $gl = $this->makeUser('group_leader', $ictmd, 'GL Sedept Non-Staff');
        $this->kirimkan($gl, $ictmd, 'SOP Sedang Berjalan');

        // Kartunya tak dirender karena barisnya kosong DAN ia bukan salah satu
        // peran yang tetap mendapat kartunya (lihat `adaMeja` di
        // `pages/Dashboard.tsx`) — keempat benderanya di bawah yang menjaganya.
        $this->actingAs($staff)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('menungguDiMeja', 0)
                ->where('isCreator', false)
                ->where('isDeptHead', false)
                ->where('isPjo', false)
                ->where('isMd', false)
            );
    }
}
