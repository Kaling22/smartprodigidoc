<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 8 — enam layar daftar dokumen pindah ke Inertia.
 *
 * Yang dijaga di sini adalah hal-hal yang gagal DIAM-DIAM, dan tak satu pun
 * tertangkap `npm run build`, `tsc --noEmit`, atau `smartpro:uji-render`:
 *
 *  1. **Model tak pernah ikut mentah ke payload.** Enam daftar ini menggendong
 *     relasi `creator` — model User beserta `password` dan `remember_token`.
 *     Blade lama aman karena tak pernah mencetaknya; props Inertia tidak punya
 *     kemewahan itu, seluruhnya tertulis di atribut `data-page` dan terbaca
 *     lewat "view source". Kelas kesalahan yang sama dengan Fase 6 & 7.
 *  2. **Bentuk paginator utuh** (`->through()`, bukan `->map()`). Dengan
 *     `map()` daftarnya tetap tampil dan hanya `links`/`from`/`to`/`total` yang
 *     lenyap — paginasinya mati tanpa satu pun galat (pelajaran Fase 6).
 *  3. **Tiap tombol per-baris datang sebagai `boleh_*` dari SERVER** (pakem
 *     P5). Kalau salah satu tak ikut dikirim, klien akan menghitungnya sendiri
 *     — salinan aturan wewenang yang dilarang CLAUDE.md §4.
 *  4. **Otorisasi tetap di server** (pakem P4): dokumen di luar jangkauan tetap
 *     403 walau tombolnya memang tak pernah digambar.
 *  5. **Jenis UNGGAHAN ikut diuji** (pakem P6): FK/PX tak punya bab, dan tiap
 *     fase yang menyentuh dokumen wajib melewatinya juga — bukan cuma SOP.
 */
class DokumenInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    private function dept(): Department
    {
        return Department::create([
            'code' => 'DIN'.Str::upper(Str::random(3)),
            'name' => 'Departemen Uji Inertia Dokumen',
        ]);
    }

    private function berlaku(User $gl, string $judul, string $jenis = 'SOP'): Document
    {
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', $jenis)->firstOrFail(), $gl->department, $judul
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    /* ======================= Komponen tiap rute ======================= */

    public function test_setiap_rute_daftar_merender_komponennya(): void
    {
        $peta = [
            'documents.index' => ['V2/Documents/Index', fn () => $this->aktorGl()],
            'documents.published' => ['V2/Documents/Published', fn () => $this->aktorPjo()],
            'documents.obsolete' => ['V2/Documents/Obsolete', fn () => $this->admin()],
            'documents.revisions' => ['V2/Documents/Revisions', fn () => $this->aktorGl()],
            'documents.staffStatus' => ['V2/Documents/StaffStatus', fn () => $this->aktorSh()],
        ];

        foreach ($peta as $rute => [$komponen, $aktor]) {
            $this->actingAs($aktor())->get(route($rute))->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($komponen)
                    // Bentuk paginator UTUH — `through()`, bukan `map()`.
                    ->has('documents.data')
                    ->has('documents.links')
                    ->has('documents.total'));
        }
    }

    /**
     * "Dokumen Revisi" tak pernah mengirim anotasi tanpa komentar.
     *
     * Baris seperti itu memang ada — sejak tanda ✓/✗ JSA,
     * {@see \App\Services\ReviewDecision::simpanAnotasi()} menulis anotasi yang
     * HANYA memikul `verdict`, dengan `comment` null. Layarnya memanggil
     * `potong()` atas tiap komentar, dan `.length` pada null MELEMPAR: satu
     * tinjauan JSA bertanda ✗ tanpa catatan sudah cukup membuat seluruh halaman
     * ini PUTIH KOSONG di peramban.
     *
     * Cara gagalnya diam total di semua gerbang lain: `php artisan test` hijau
     * (props memang terkirim), `tsc --noEmit` hijau (tipenya menyatakan
     * `komentar: string`, dan itulah justru yang dilanggar kiriman), `npm run
     * build` hijau. Hanya `smartpro:uji-render` yang melihatnya — dan itu pun
     * hanya bila kebetulan ada anotasi kosong di basis data saat dijalankan.
     * Karena itu penjaganya dipaku di sini, pada BENTUK KIRIMANNYA.
     */
    public function test_daftar_revisi_membuang_anotasi_tanpa_komentar(): void
    {
        $gl = $this->aktorGl();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Uji Anotasi Kosong'
        );
        $doc->update(['status' => 'rejected']);

        $review = $doc->reviews()->create([
            'reviewer_id' => $this->aktorSh()->id,
            'revision_round' => 0,
            'decision' => 'needs_revision',
            'summary' => 'Ada satu pengendalian yang belum sesuai.',
        ]);
        $review->annotations()->create([
            'section_key' => 'analisa', 'item_ref' => 'L0-B0-P0',
            'severity' => 'minor', 'comment' => null, 'verdict' => 'perlu_revisi',
        ]);
        $review->annotations()->create([
            'section_key' => 'analisa', 'item_ref' => 'L0-B0-P1',
            'severity' => 'minor', 'comment' => 'Tambahkan APD yang dipakai.',
        ]);

        $baris = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('documents.revisions'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        $this->assertNotNull($baris, 'dokumen rejected wajib muncul di daftar revisi pembuatnya');
        $this->assertSame(['Tambahkan APD yang dipakai.'], array_column($baris['anotasi'], 'komentar'));
        $this->assertSame(0, $baris['anotasi_sisa'], 'sisanya dihitung dari yang berkomentar saja');
    }

    public function test_detail_dokumen_merender_komponennya(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->berlaku($gl, 'SOP Uji Detail Inertia');

        $this->actingAs($gl)->get(route('documents.show', $doc))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Documents/Show')
                ->where('document.id', $doc->id)
                ->where('document.nomor', $doc->displayNumber())
                ->has('timeline')
                ->has('masukan')
                ->has('masukanBelumDitindak'));
    }

    /* ======================= Kebocoran ======================= */

    /**
     * `password` & `remember_token` tak pernah ikut ke payload mana pun.
     *
     * Diperiksa di HTML-nya, bukan cuma di props: yang berbahaya adalah apa yang
     * benar-benar terkirim ke peramban.
     */
    public function test_kolom_rahasia_pengguna_tak_pernah_ikut_ke_payload(): void
    {
        $gl = $this->aktorGl();
        $this->berlaku($gl, 'SOP Uji Bocor Inertia');

        foreach (['documents.index', 'documents.published'] as $rute) {
            $res = $this->actingAs($gl)->get(route($rute))->assertOk();

            $this->assertStringNotContainsString('remember_token', (string) $res->getContent(), $rute);
            $this->assertStringNotContainsString($gl->password, (string) $res->getContent(), $rute);

            foreach ($this->propsInertia($res)['documents']['data'] as $baris) {
                $this->assertArrayNotHasKey('password', $baris);
                $this->assertArrayNotHasKey('creator', $baris);
            }
        }
    }

    /* ======================= Peta status dari props ======================= */

    /**
     * Rupa status datang dari `statusMeta`/`statusLabels`, bukan diketik di TSX.
     *
     * Pengganti pemindaian `.badge.bg-*` yang di halaman Inertia lolos trivial —
     * penggantian yang sama sudah dilakukan untuk dashboard di Fase 5.
     */
    public function test_rupa_status_daftar_datang_dari_status_meta(): void
    {
        foreach (['documents.published', 'documents.staffStatus'] as $rute) {
            $this->actingAs($this->aktorSh())->get(route($rute))->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->has('statusMeta.verifikasi_md')   // yang dulu abu-abu di empat Blade
                    ->has('statusLabels.verifikasi_md')
                    ->has('statusMeta', count(Document::STATUS_META))
                    ->has('statusLabels', count(Document::STATUS_LABELS)));
        }
    }

    /* ======================= Wewenang per baris ======================= */

    /**
     * Kelima `boleh_*` di Dokumen Berlaku dijawab SERVER, dan jawabannya berbeda
     * per peran — kalau salah satunya hilang, klien akan mengarangnya sendiri.
     */
    public function test_tombol_per_baris_dijawab_server(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->berlaku($gl, 'SOP Uji Wewenang Baris');

        $kunci = [
            'boleh_edit_arsip', 'boleh_beri_masukan', 'boleh_revisi',
            'boleh_batal_revisi', 'boleh_musnahkan',
        ];

        $baris = fn (User $u) => collect($this->propsInertia(
            $this->actingAs($u)->get(route('documents.published', ['q' => 'SOP Uji Wewenang Baris']))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        foreach ([$gl, $this->aktorSh(), $this->admin()] as $u) {
            foreach ($kunci as $k) {
                $this->assertArrayHasKey($k, $baris($u), "props {$k} wajib ada bagi {$u->nrp}");
            }
        }

        // GL pemilik boleh merevisi; SH sejak Fase C hanya membaca.
        $this->assertTrue($baris($gl)['boleh_revisi']);
        $this->assertFalse($baris($this->aktorSh())['boleh_revisi']);
        // Musnahkan = Admin saja (§8).
        $this->assertFalse($baris($gl)['boleh_musnahkan']);
        $this->assertTrue($baris($this->admin())['boleh_musnahkan']);
    }

    /**
     * Masukan yang boleh dicentang jadi alasan revisi disaring di SERVER.
     *
     * Yang sudah ditindak (`diadopsi`/`ditolak`) tak boleh ikut — kalau
     * penyaringnya pindah ke klien, aturan itu punya salinan kedua.
     */
    public function test_masukan_belum_ditindak_disaring_server(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->berlaku($gl, 'SOP Uji Saring Masukan');

        $buat = fn (string $status, string $isi) => DocumentFeedback::create([
            'feedback_number' => 'MSK-DIN-'.Str::upper(Str::random(6)),
            'document_id' => $doc->id,
            'user_id' => $nonStaff->id,
            'isi' => $isi,
            'status' => $status,
        ]);

        $buat('baru', 'Masukan yang masih hidup.');
        $buat('ditolak', 'Masukan yang sudah ditutup.');

        $props = $this->propsInertia(
            $this->actingAs($gl)->get(route('documents.show', $doc))->assertOk()
        );

        $this->assertSame(
            ['Masukan yang masih hidup.'],
            array_column($props['masukanBelumDitindak'], 'isi')
        );
        // Panel utamanya tetap memuat KEDUANYA — yang disaring cuma kotak centangnya.
        $this->assertCount(2, $props['masukan']);
    }

    /* ======================= Jenis unggahan (pakem P6) ======================= */

    /** FK/PX muncul di daftar dan ditandai `arsip`, bukan diperlakukan sbg wizard. */
    public function test_jenis_unggahan_muncul_di_daftar_dengan_penanda_arsip(): void
    {
        $admin = $this->admin();
        $dept = $this->dept();
        $nomor = 'PPA-ADRO-FK-'.$dept->code.'-01';

        $fk = Document::create([
            'doc_number' => $nomor,
            'doc_number_final' => $nomor,
            'document_type_id' => DocumentType::where('code', 'FK')->firstOrFail()->id,
            'department_id' => $dept->id,
            'title' => 'Formulir Uji Unggahan Inertia',
            'status' => 'published',
            'arsip_path' => 'arsip/uji-inertia.pdf',
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);

        $baris = collect($this->propsInertia(
            $this->actingAs($admin)
                ->get(route('documents.published', ['department_id' => $dept->id]))->assertOk()
        )['documents']['data'])->firstWhere('id', $fk->id);

        $this->assertNotNull($baris, 'dokumen unggahan wajib ikut di daftar Berlaku');
        $this->assertSame('FK', $baris['jenis']);
        $this->assertTrue($baris['arsip'], 'FK/PX ditandai arsip — tombol PDF-nya menyajikan berkas asli');
        $this->assertTrue($baris['boleh_edit_arsip'], 'Admin boleh memperbaiki metadata dokumen lama');
    }

    /* ======================= Otorisasi tetap di server (P4) ======================= */

    public function test_dokumen_di_luar_jangkauan_tetap_403(): void
    {
        $gl = $this->aktorGl();
        $dept = $this->dept();
        $lain = User::create([
            'name' => 'GL Departemen Lain',
            'nrp' => 'DIN-'.Str::upper(Str::random(5)),
            'jabatan' => User::JABATAN_GROUP_LEADER,
            'status' => 'active',
            'password' => bcrypt('rahasia123'),
            'department_id' => $dept->id,
        ]);
        $lain->assignRole(\Database\Seeders\RolePermissionSeeder::ROLE_GROUP_LEADER);

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji 403 Inertia'
        );

        $this->actingAs($lain)->get(route('documents.show', $doc))->assertForbidden();
    }

    /* ======================= Penyaring departemen (butir 13) ======================= */

    /**
     * "Status Dokumen Staff" punya penyaring Departemen sendiri — dan daftar
     * pilihannya datang dari SERVER, bukan dari cabang jabatan di TSX.
     *
     * Dua sisi dikunci sekaligus, karena keduanya adalah keputusan peran yang
     * dulu hanya tersirat dari sub-menu sidebar:
     *   • PJO (`document.view_all`) menerima `departments` terisi, pilihannya
     *     benar-benar menyaring (`selectedDept`), dan pilihan itu BERTAHAN saat
     *     penyaring lain (`type`) ikut diubah — kalau `department_id` tak ikut
     *     terbawa, mengganti jenis akan diam-diam melebarkan daftar ke 7 dept.
     *   • GL/SH menerima `departments` KOSONG, jadi penyaringnya tak digambar.
     *     Itulah pola `departments.length > 0` yang dipakai delapan halaman V2
     *     lain: daftar kosong dari server = penyaring tak tampil.
     */
    public function test_penyaring_departemen_status_dokumen_staff_datang_dari_server(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();

        $props = $this->propsInertia(
            $this->actingAs($this->aktorPjo())
                ->get(route('documents.staffStatus', ['department_id' => $dept->id, 'type' => 'SOP']))
                ->assertOk()
        );

        $this->assertNotEmpty($props['departments'], 'PJO wajib menerima daftar departemen');
        $this->assertSame($dept->id, (int) $props['filters']['department_id']);
        $this->assertSame('SOP', $props['filters']['type']);
        $this->assertSame($dept->code, $props['selectedDept']['code']);

        // SH: daftar kosong → penyaringnya memang tak boleh digambar.
        $this->assertSame([], $this->propsInertia(
            $this->actingAs($this->aktorSh())->get(route('documents.staffStatus'))->assertOk()
        )['departments']);
    }
}
