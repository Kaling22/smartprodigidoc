<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiKategori;
use App\Models\JobExecution;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * FASE 12 — Informasi, riwayat, dan log sebagai halaman Inertia.
 *
 * Delapan layar sekaligus (`informasi/{index,create,perbarui,nonaktif}`,
 * `audit`, `job_executions`, `log/{masukan,pesan}`). Yang dijaga di sini bukan
 * rupanya, melainkan lima hal yang bisa rusak tanpa satu pun gerbang lain
 * berbunyi:
 *
 *   1. **Kebocoran props** — kelas kesalahan yang sama sudah TUJUH fase
 *      berturut-turut. Di sini yang menggendong model User ada lima (`uploader`
 *      tiap Informasi, `user` tiap AuditLog & JobExecution, `user`/`replier`
 *      tiap masukan, `oleh` tiap baris Log Pesan), ditambah DUA jalur berkas
 *      privat: `informasi.file_path` — yang nama berkasnya diacak Laravel
 *      supaya tak bisa ditebak dari nomornya — dan `documents.arsip_path` yang
 *      ikut menumpang lewat relasi `document`.
 *   2. **Kolom mengikuti KATEGORI, bukan cabang per kategori** — padanan pakem
 *      P1 di modul ini: kategori lahir dari basis data, dan formulir/tabelnya
 *      harus ikut tanpa satu baris kode disentuh.
 *   3. **Bentuk paginator utuh** (`->through()`, bukan `->map()` — pelajaran
 *      Fase 6: paginasinya mati tanpa satu pun galat).
 *   4. **Penyaringan & pengelompokan tetap di server** (pakem P5).
 *   5. **Otorisasi tetap di server** (pakem P4) dan **jenis unggahan tak
 *      terlupa** (pakem P6).
 */
class InformasiLogInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    /** Dokumen BERLAKU milik departemen si GL. */
    private function dokumenBerlaku(string $jenis = 'SOP', string $judul = 'SOP Log Fase 12'): Document
    {
        $gl = $this->aktorGl();

        $d = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', $jenis)->firstOrFail(), $gl->department, $judul
        );
        $d->forceFill(['status' => 'published', 'published_at' => now()])->save();

        return $d->fresh();
    }

    private function masukan(Document $doc, string $isi = 'Rambu simpang perlu diperbarui.'): DocumentFeedback
    {
        return DocumentFeedback::create([
            'feedback_number' => 'MSK-'.now()->format('Y').'-F12'.random_int(100, 999),
            'document_id' => $doc->id,
            'user_id' => $this->aktorNonStaff()->id,
            'isi' => $isi,
            'status' => 'baru',
        ]);
    }

    private function informasi(string $kategori = 'kebijakan'): Informasi
    {
        return Informasi::create([
            'kategori' => $kategori,
            'nomor' => 'INF-F12-'.Str::upper(Str::random(6)),
            'judul' => 'Kebijakan Fase 12',
            'edisi' => 1,
            'no_revisi' => 0,
            // Nama berkas yang SENGAJA tak bisa ditebak — persis pola
            // `Storage::store()`. Kalau jalur ini sampai ke props, lapis kedua
            // itu batal diam-diam.
            'file_path' => 'informasi/kebijakan/9f3c7b1a2d4e5f60.pdf',
            'file_mime' => 'application/pdf',
            'berlaku' => true,
            'uploaded_by' => $this->admin()->id,
        ]);
    }

    /* ======================= Komponen tiap layar ======================= */

    public function test_delapan_layar_memakai_komponen_inertia(): void
    {
        $admin = $this->admin();
        $info = $this->informasi();

        $peta = [
            'V2/Informasi/Index' => route('informasi.index', ['kategori' => 'kebijakan']),
            'V2/Informasi/Create' => route('informasi.create', ['kategori' => 'kebijakan']),
            'V2/Informasi/Perbarui' => route('informasi.perbarui', $info),
            'V2/Audit/Index' => route('audit.index'),
            'V2/JobExecutions/Index' => route('job-executions.index'),
            'V2/Log/Masukan' => route('log.masukan'),
            'V2/Log/Pesan' => route('log.pesan'),
        ];

        foreach ($peta as $komponen => $url) {
            $this->assertSame(
                $komponen,
                $this->actingAs($admin)->get($url)->assertOk()->viewData('page')['component'],
                $url.' tidak memakai komponen '.$komponen,
            );
        }

        // Layar kedelapan hanya muncul untuk kategori yang DITUTUP Admin.
        $kat = InformasiKategori::cari('kebijakan');
        $kat->update(['is_active' => false]);
        InformasiKategori::lupakanIngatan();

        $this->assertSame(
            'V2/Informasi/Nonaktif',
            $this->actingAs($admin)->get(route('informasi.index', ['kategori' => 'kebijakan']))
                ->assertOk()->viewData('page')['component'],
        );
    }

    /* ======================= Kebocoran props ======================= */

    /**
     * Diperiksa pada HTML MENTAH, bukan larik props: yang berbahaya justru
     * kolom yang tak dibaca satu pun TSX — ia tetap terbaca lewat "view
     * source", dan justru karena tak dibaca, tak ada tes rupa yang bisa
     * menangkapnya.
     */
    public function test_props_tak_membocorkan_kata_sandi_maupun_jalur_berkas(): void
    {
        $admin = $this->admin();
        $info = $this->informasi();
        $doc = $this->dokumenBerlaku();
        $this->masukan($doc);

        // Dokumen unggahan berjalur arsip privat — ia menumpang ke Audit Log
        // lewat relasi `document`.
        $fk = Document::create([
            'doc_number' => 'PPA-ADRO-FK-'.$this->aktorGl()->department->code.'-91',
            'document_type_id' => DocumentType::where('code', 'FK')->firstOrFail()->id,
            'department_id' => $this->aktorGl()->department_id,
            'title' => 'Formulir Uji Kebocoran Fase 12',
            'status' => 'published',
            'arsip_path' => 'arsip/rahasia-tak-tertebak-f12.pdf',
            'created_by' => $admin->id,
            'published_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $admin->id,
            'document_id' => $fk->id,
            'action' => 'document.publish',
            'created_at' => now(),
        ]);

        $rahasia = [
            'password', 'remember_token',
            $info->file_path, 'arsip_path', $fk->arsip_path,
        ];

        $layar = [
            route('informasi.index', ['kategori' => 'kebijakan']),
            route('informasi.perbarui', $info),
            route('audit.index'),
            route('job-executions.index'),
            route('log.masukan'),
            route('log.pesan'),
        ];

        foreach ($layar as $url) {
            $html = $this->actingAs($admin)->get($url)->assertOk()->getContent();

            foreach ($rahasia as $bocor) {
                $this->assertStringNotContainsString($bocor, $html, $url.' membocorkan '.$bocor);
            }
        }
    }

    /* ======================= Kolom mengikuti kategori ======================= */

    /**
     * Padanan pakem P1 di modul ini: kolom tabel & isian formulir datang dari
     * `informasi_kategori.kolom_json`, jadi mengubahnya di BASIS DATA harus
     * langsung terlihat di props — tanpa satu baris kode disentuh.
     */
    public function test_kolom_informasi_datang_dari_kategori_bukan_dari_kode(): void
    {
        $admin = $this->admin();
        $kat = InformasiKategori::cari('kebijakan');
        $semula = $kat->kolom_json;

        $kat->update(['kolom_json' => ['edisi', 'no_revisi', 'tanggal_efektif']]);
        InformasiKategori::lupakanIngatan();

        $props = $this->propsInertia(
            $this->actingAs($admin)->get(route('informasi.create', ['kategori' => 'kebijakan']))->assertOk()
        );
        $this->assertSame(['edisi', 'no_revisi', 'tanggal_efektif'], $props['kolom']);

        // Dicabut satu → hilang sendiri dari props, dan Form Request menolak
        // kiriman yang tetap memuatnya (`prohibited`).
        $kat->update(['kolom_json' => ['edisi']]);
        InformasiKategori::lupakanIngatan();

        $props = $this->propsInertia(
            $this->actingAs($admin)->get(route('informasi.index', ['kategori' => 'kebijakan']))->assertOk()
        );
        $this->assertSame(['edisi'], $props['kolom']);

        $kat->update(['kolom_json' => $semula]);
        InformasiKategori::lupakanIngatan();
    }

    /* ======================= Bentuk paginator ======================= */

    /**
     * `->through()`, bukan `->map()`. Dengan `map()` daftarnya TETAP TAMPIL dan
     * hanya `links`/`from`/`to`/`total` yang lenyap — paginasinya mati tanpa
     * satu pun galat (pelajaran Fase 6).
     */
    public function test_kelima_daftar_membawa_bentuk_paginator_utuh(): void
    {
        $admin = $this->admin();
        $this->informasi();
        $this->masukan($this->dokumenBerlaku());

        $peta = [
            'daftar' => route('informasi.index', ['kategori' => 'kebijakan']),
            'logs' => route('audit.index'),
            'pekerjaan' => route('job-executions.index'),
            'masukan' => route('log.masukan'),
            'pesan' => route('log.pesan'),
        ];

        foreach ($peta as $kunci => $url) {
            $paginator = $this->propsInertia(
                $this->actingAs($admin)->get($url)->assertOk()
            )[$kunci];

            foreach (['data', 'links', 'from', 'to', 'total', 'current_page'] as $wajib) {
                $this->assertArrayHasKey($wajib, $paginator, $url.' kehilangan `'.$wajib.'`');
            }
        }
    }

    /* ======================= Penyaringan tetap di server ======================= */

    public function test_penyaring_dikerjakan_server(): void
    {
        $admin = $this->admin();
        $doc = $this->dokumenBerlaku();
        $m = $this->masukan($doc, 'Masukan uji penyaring Fase 12.');

        // Audit: nama aksi disaring `like` di server.
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'uji.fase12.penanda',
            'created_at' => now(),
        ]);

        $aksi = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('audit.index', ['action' => 'uji.fase12']))->assertOk()
        )['logs']['data'])->pluck('aksi')->unique();

        $this->assertSame(['uji.fase12.penanda'], $aksi->values()->all());

        // Log Masukan: status.
        $nomor = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('log.masukan', ['status' => 'ditolak']))->assertOk()
        )['masukan']['data'])->pluck('nomor')->all();

        $this->assertNotContains($m->feedback_number, $nomor);

        // Log Pesan: tahap. Baris masukan sejawat tak pernah lolos saringan
        // `pengajuan_revisi`, dan penyaringnya memang dikerjakan di server.
        $tahap = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('log.pesan', ['tahap' => 'pengajuan_revisi']))->assertOk()
        )['pesan']['data'])->pluck('tahap')->unique();

        $this->assertTrue(
            $tahap->isEmpty() || $tahap->all() === ['pengajuan_revisi'],
            'Log Pesan meloloskan tahap di luar saringan.',
        );
    }

    /* ======================= Bentuk baris tiap layar ======================= */

    public function test_audit_meratakan_pelaku_dan_dokumen(): void
    {
        $admin = $this->admin();
        $doc = $this->dokumenBerlaku();

        $log = AuditLog::create([
            'user_id' => $admin->id,
            'document_id' => $doc->id,
            'action' => 'uji.fase12.audit',
            'ip_address' => '10.7.110.91',
            'created_at' => now(),
        ]);

        $baris = collect($this->propsInertia(
            $this->actingAs($admin)->get(route('audit.index', ['action' => 'uji.fase12.audit']))->assertOk()
        )['logs']['data'])->firstWhere('id', $log->id);

        $this->assertSame($admin->name, $baris['oleh']);
        $this->assertSame($admin->nrp, $baris['nrp']);
        $this->assertSame('10.7.110.91', $baris['ip']);
        // Dokumen jadi DUA kolom, bukan model utuh — tautannya dirakit klien
        // dari `id`, nomornya sudah `displayNumber()`.
        $this->assertSame(['id' => $doc->id, 'nomor' => $doc->displayNumber()], $baris['dokumen']);
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2}:\d{2}$#', $baris['waktu']);
    }

    public function test_riwayat_pekerjaan_membawa_progres_dan_label_status(): void
    {
        $gl = $this->aktorGl();
        $jsa = $this->dokumenBerlaku('JSA', 'JSA Riwayat Fase 12');
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Persiapan', 'bahaya' => [
                ['risiko' => 'Terjatuh', 'pengendalian' => ['Harness', 'Lifeline']],
            ]],
        ]]);

        $job = JobExecution::create([
            'document_id' => $jsa->id,
            'user_id' => $gl->id,
            'nama_pekerjaan' => 'Pekerjaan Uji Fase 12',
            'lokasi' => 'Area 3B',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'analisa_snapshot' => $jsa->fresh()->contentMap()['analisa'],
            'status' => 'berlangsung',
        ]);

        $baris = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('job-executions.index'))->assertOk()
        )['pekerjaan']['data'])->firstWhere('id', $job->id);

        $this->assertSame('Pekerjaan Uji Fase 12', $baris['nama']);
        $this->assertSame($jsa->displayNumber(), $baris['jsa_nomor']);
        $this->assertSame($gl->nrp, $baris['nrp']);
        // Total dihitung per TINDAKAN PENGENDALIAN, dan perhitungannya tetap di
        // model (`JobExecution::progres()`), bukan diulang di klien.
        $this->assertSame(['total' => 2, 'selesai' => 0], $baris['progres']);
        $this->assertSame(JobExecution::STATUS_LABELS['berlangsung'], $baris['status_label']);
    }

    public function test_log_pesan_meratakan_oleh_dan_tanggal_jadi_teks(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumenBerlaku();

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Alasan uji perataan baris Log Pesan Fase 12.',
        ])->assertRedirect();

        $baris = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('log.pesan', ['tahap' => 'pengajuan_revisi']))->assertOk()
        )['pesan']['data'])->firstWhere('alasan', 'Alasan uji perataan baris Log Pesan Fase 12.');

        $this->assertNotNull($baris, 'Pengajuan revisi tak muncul di Log Pesan.');
        // `oleh` sudah STRING: modelnya User utuh, dan larik ini berakhir di
        // atribut `data-page`.
        $this->assertIsString($baris['oleh']);
        $this->assertStringContainsString($gl->name, $baris['oleh']);
        // `tanggal` masih Carbon selama disaring & diurutkan di controller;
        // yang menyeberang ke props sudah jadi teks.
        $this->assertMatchesRegularExpression('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2}$#', $baris['tanggal']);
        $this->assertArrayNotHasKey('document', $baris);
    }

    /* ======================= Pengelompokan tetap di server (P5) ======================= */

    /**
     * Jendela "Revisi" datang SATU per DOKUMEN, sudah dikelompokkan server —
     * beberapa masukan bisa menunjuk dokumen yang sama, dan mengulang
     * pengelompokan itu di klien berarti aturan "siapa boleh mengadopsi" ikut
     * pindah ke sana.
     */
    public function test_jendela_revisi_dikelompokkan_per_dokumen_di_server(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumenBerlaku();
        $a = $this->masukan($doc, 'Masukan pertama atas dokumen yang sama.');
        $b = $this->masukan($doc, 'Masukan kedua atas dokumen yang sama.');

        $revisi = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('log.masukan'))->assertOk()
        )['revisi']);

        $grup = $revisi->firstWhere('id', $doc->id);

        $this->assertNotNull($grup);
        $this->assertCount(1, $revisi->where('id', $doc->id), 'Satu jendela per dokumen, bukan per masukan.');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $grup['tercentang']);
        // Angka yang DIJANJIKAN dihitung server (DocumentService::nextEditionRevision).
        $this->assertCount(2, $grup['janji_revisi']);
    }

    /* ======================= Jenis unggahan (pakem P6) ======================= */

    /** Masukan atas dokumen FK terbaca di Log Masukan, nomornya apa adanya. */
    public function test_jenis_unggahan_fk_ikut_terbaca_di_log_masukan(): void
    {
        $gl = $this->aktorGl();
        $nomor = 'PPA-ADRO-FK-'.$gl->department->code.'-92';

        $fk = Document::create([
            'doc_number' => $nomor,
            'doc_number_final' => $nomor,
            'document_type_id' => DocumentType::where('code', 'FK')->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Formulir Kerja Uji Log Fase 12',
            'status' => 'published',
            'arsip_path' => 'arsip/fk-log-f12.pdf',
            'created_by' => $gl->id,
            'published_at' => now(),
        ]);

        $m = $this->masukan($fk, 'Kolom tanda tangan di formulir ini kurang satu.');

        $baris = collect($this->propsInertia(
            $this->actingAs($gl)->get(route('log.masukan'))->assertOk()
        )['masukan']['data'])->firstWhere('nomor', $m->feedback_number);

        $this->assertNotNull($baris, 'Masukan atas dokumen unggahan wajib ikut terbaca.');
        $this->assertSame($nomor, $baris['dokumen']['nomor']);
        $this->assertSame('Formulir Kerja Uji Log Fase 12', $baris['dokumen']['judul']);
    }

    /* ======================= Otorisasi tetap di server (pakem P4) ======================= */

    public function test_tetap_403_di_luar_jangkauan(): void
    {
        $nonStaff = $this->aktorNonStaff();
        $sh = $this->aktorSh();

        // Log Dokumen: lingkupnya `dashboardPenuh()`.
        $this->actingAs($nonStaff)->get(route('log.masukan'))->assertForbidden();
        $this->actingAs($nonStaff)->get(route('log.pesan'))->assertForbidden();

        // Audit Log: `can:audit.view`. Middleware-nya TIDAK boleh dilepas
        // dengan alasan menunya sudah disembunyikan di React.
        $this->assertFalse($nonStaff->can('audit.view'));
        $this->actingAs($nonStaff)->get(route('audit.index'))->assertForbidden();

        // Unggah Informasi: `can:informasi.manage` — SH sengaja belum diberi.
        $this->assertFalse($sh->can('informasi.manage'));
        $this->actingAs($sh)->get(route('informasi.create', ['kategori' => 'kebijakan']))->assertForbidden();

        // Membaca Informasi justru terbuka bagi semua akun aktif — itu inti menu ini.
        $this->actingAs($nonStaff)->get(route('informasi.index', ['kategori' => 'kebijakan']))->assertOk();
    }
}
