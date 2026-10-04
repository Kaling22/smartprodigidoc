<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Export "Daftar Induk Dokumen" per jenis ke Excel.
 *
 * Berkasnya HTML ber-Content-Type Excel, bukan .xlsx sungguhan — cukup untuk
 * dibuka Excel dan tidak menyeret PhpSpreadsheet masuk composer.json.
 *
 * Tiga izin, lingkupnya BERBEDA: `document.publish` (PJO + Admin IT) menarik
 * ketujuh departemen; `document.review` (SH/DH, butir 6) dan `document.create`
 * (GL, PLAN-AKSES-v7 Fase 1) hanya departemennya sendiri. MD sengaja TIDAK ikut
 * meski memegang `document.view_all` — ia meninjau penulisan, bukan pemegang
 * daftar induk.
 */
class DocumentExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pjo_mengunduh_daftar_induk_sop(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $berlaku = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Uji Export');
        $berlaku->update(['status' => 'published', 'published_at' => now()]);

        // Draft TIDAK boleh ikut: daftar induk memuat dokumen yang berlaku saja.
        $draft = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Masih Draft');

        $res = $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'SOP'))
            ->assertOk();

        $this->assertStringContainsString('application/vnd.ms-excel', $res->headers->get('content-type'));
        $this->assertStringContainsString(
            'DAFTAR INDUK DOKUMEN SOP PPA SITE ADARO INDONESIA.xls',
            $res->headers->get('content-disposition')
        );

        $res->assertSee('SOP Uji Export')
            ->assertSee($berlaku->displayNumber())
            ->assertDontSee('SOP Masih Draft');

        // Jenis lain tidak kecipratan.
        $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'JSA'))
            ->assertOk()
            ->assertDontSee('SOP Uji Export');

        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_non_staff_tidak_boleh_mengunduh(): void
    {
        $this->actingAs($this->aktorNonStaff())
            ->get(route('documents.export', 'SOP'))
            ->assertForbidden();
    }

    /**
     * MD tetap tertutup: ia lolos `view_all` tapi bukan pemegang daftar induk —
     * yang diperiksanya sistematika PENULISAN, bukan kelengkapan nomor.
     *
     * Dulu GL ikut di sini. PLAN-AKSES-v7 Fase 1 mencabutnya: penyusunlah yang
     * paling sering perlu tahu nomor mana yang masih kosong di departemennya.
     */
    public function test_md_tidak_boleh_mengunduh(): void
    {
        $this->actingAs(User::where('nrp', 'MD-0001')->firstOrFail())
            ->get(route('documents.export', 'SOP'))
            ->assertForbidden();
    }

    /** Admin IT berwenang penuh (CLAUDE.md §6), jadi ia tetap kebagian. */
    public function test_admin_it_tetap_boleh_mengunduh(): void
    {
        $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
            ->get(route('documents.export', 'SOP'))
            ->assertOk();
    }

    public function test_jenis_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'MEMO'))
            ->assertNotFound();
    }

    /**
     * GL/SH/DH mengunduh, tapi HANYA departemennya. Ini penjaga yang sebenarnya:
     * lingkupnya dipaksa dari user, tak ada parameter departemen yang bisa
     * diputar untuk menarik daftar induk dept lain.
     *
     * GL ikut diuji di sini, bukan di test tersendiri: yang membedakan ketiganya
     * cuma izin mana yang membuka pintunya — pagar departemennya satu dan sama,
     * dan pagar itulah yang harus dijaga tetap rapat.
     */
    public function test_gl_sh_dan_dh_hanya_melihat_departemennya(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $deptLain = Department::where('code', 'SHE')->firstOrFail();
        $svc = app(DocumentService::class);

        $milikSendiri = $svc->createDraft($gl, $type, $gl->department, 'SOP Punya ICTMD');
        $milikSendiri->update(['status' => 'published', 'published_at' => now()]);

        $milikTetangga = $svc->createDraft($gl, $type, $deptLain, 'SOP Punya SHE');
        $milikTetangga->update(['status' => 'published', 'published_at' => now()]);

        // Keduanya berdepartemen sama dengan dokumennya; yang diuji batas
        // DEPARTEMEN, bukan orangnya.
        // DH sengaja TIDAK ikut di sini: di basis data belum ada Departemen
        // Head di ICTMD — satu-satunya DH aktif ada di ENGINEERING, jadi ia
        // memang TIDAK boleh melihat dokumen ICTMD. Memasukkannya berarti
        // menguji kebalikan dari aturannya sendiri. Begitu ada akun DH
        // ICTMD, kembalikan `$this->aktorDh()` ke daftar ini.
        foreach ([$this->aktorSh(), $this->aktorGl()] as $aktor) {
            $this->actingAs($aktor)
                ->get(route('documents.export', 'SOP'))
                ->assertOk()
                ->assertSee('SOP Punya ICTMD')
                ->assertDontSee('SOP Punya SHE');
        }

        // PJO lintas departemen — pembanding yang membuktikan dokumennya memang ada.
        $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'SOP'))
            ->assertOk()
            ->assertSee('SOP Punya SHE');
    }

    /**
     * Lebar kolom adaptif + strip, penomoran kolom 1..5 (BUKAN 0..4), dan
     * tanggal yang diambil dari lembar CATATAN REVISI ($dok sendiri) — BUKAN
     * dari `published_at` versi-versi di rantai `revises_document_id`.
     *
     * Baris `catatan_revisi` (diisi manual saat dokumen lama didaftarkan, atau
     * lewat langkah "Log Revisi") TAK bertanda edisi — batasnya dikenali dari
     * `no_rev` yang naik lalu TURUN kembali ke 1 begitu edisi berganti (lihat
     * docblock `DocumentExportController::matriks()`). Revisi 0 — versi
     * PERTAMA, sebelum direvisi sama sekali — tak pernah punya baris di
     * lembar ini; tanggalnya cuma tercetak di "Tanggal Efektif", yang TETAP
     * ditelusuri lewat rantai (satu-satunya hal yang masih dibacanya).
     */
    public function test_kolom_revisi_adaptif_dan_sel_kosong_berstrip(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Versi PERTAMA — revisi 0, tak pernah muncul di catatan_revisi.
        // Rantai inilah satu-satunya sumber "Tanggal Efektif".
        $akar = $svc->createDraft($gl, $type, $gl->department, 'SOP Versi Akar');
        $akar->update([
            'status' => 'obsolete', 'edisi' => '1', 'no_revisi' => 0,
            'published_at' => '2023-01-10 08:00:00',
        ]);

        // Dokumen BERLAKU sekarang: edisi 2, revisi 1 — memegang SELURUH
        // riwayat tanggalnya sendiri di catatan_revisi (terakumulasi lintas
        // revisi, persis seperti `requestRevision` menyalin section).
        $baru = $svc->createDraft($gl, $type, $gl->department, 'SOP Sudah Direvisi');
        $baru->update([
            'status' => 'published', 'edisi' => '2', 'no_revisi' => 1,
            'revises_document_id' => $akar->id, 'published_at' => '2024-06-20 08:00:00',
        ]);
        $svc->saveSection($baru, 'catatan_revisi', [
            ['no_rev' => 1, 'tanggal' => '2023-02-01', 'halaman' => '1', 'catatan' => 'Revisi 1'],
            ['no_rev' => 2, 'tanggal' => '2023-05-01', 'halaman' => '1', 'catatan' => 'Revisi 2'],
            ['no_rev' => 3, 'tanggal' => '2023-08-01', 'halaman' => '1', 'catatan' => 'Revisi 3'],
            ['no_rev' => 4, 'tanggal' => '2023-11-01', 'halaman' => '1', 'catatan' => 'Revisi 4'],
            // Revisi 5 edisi 1 — no_rev TERTINGGI sebelum roll-over.
            ['no_rev' => 5, 'tanggal' => '2024-01-10', 'halaman' => '1', 'catatan' => 'Revisi 5'],
            // Roll-over ke edisi 2: no_rev TURUN kembali ke 1.
            ['no_rev' => 1, 'tanggal' => '2024-06-20', 'halaman' => '1', 'catatan' => 'Revisi edisi 2'],
        ]);

        // Dokumen yang belum pernah direvisi — pembanding untuk sel berstrip.
        $polos = $svc->createDraft($gl, $type, $gl->department, 'SOP Belum Direvisi');
        $polos->update(['status' => 'published', 'edisi' => '1', 'no_revisi' => 0, 'published_at' => '2025-02-02 08:00:00']);

        $res = $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'SOP'))
            ->assertOk();

        // Edisi 1 penuh (5 kolom: 1..5), Edisi 2 dipotong di revisi 1 (1 kolom).
        $res->assertSee('<th colspan="5">Edisi 1</th>', false)
            ->assertSee('<th colspan="1">Edisi 2</th>', false)
            ->assertDontSee('Edisi 3');

        // Kolom bernomor 1..5 — "0" TIDAK BOLEH lagi jadi label kolom.
        $res->assertSee('<th style="width:70px">1</th>', false)
            ->assertSee('<th style="width:70px">5</th>', false)
            ->assertDontSee('<th style="width:70px">0</th>', false);

        // Tanggal revisi 5 (edisi 1) & revisi 1 (edisi 2) datang dari
        // catatan_revisi milik $baru sendiri.
        $res->assertSee('10/01/2024')->assertSee('20/06/2024');

        // Revisi 0 ($akar, lewat rantai) TIDAK muncul di grid — hanya di
        // "Tanggal Efektif".
        $res->assertSee('10/01/2023');

        // Sel yang tak berlaku diberi strip, bukan dibiarkan kosong.
        $res->assertSee('class="center">-</td>', false);
    }

    /**
     * Dokumen LAMA (arsip) yang didaftarkan sebagai SATU baris saja — TANPA
     * rantai `revises_document_id` sama sekali. Ini persis kasus nyata yang
     * memicu perbaikan ini (PPA-ADRO-SOP-ICTMD-09): seluruh riwayat 8
     * revisinya hanya ada di `catatan_revisi` milik baris itu sendiri, jadi
     * menelusuri rantai (kosong) tidak akan menemukan satu tanggal pun.
     */
    public function test_dokumen_arsip_satu_baris_tetap_membaca_catatan_revisi(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        $arsip = $svc->createDraft($gl, $type, $gl->department, 'SOP Arsip Satu Baris');
        $arsip->update([
            'status' => 'published', 'edisi' => '2', 'no_revisi' => 3,
            'revises_document_id' => null, 'published_at' => '2022-12-09 08:00:00',
        ]);
        $svc->saveSection($arsip, 'catatan_revisi', [
            ['no_rev' => 1, 'tanggal' => '2023-05-15', 'halaman' => '1', 'catatan' => 'Revisi 1'],
            ['no_rev' => 2, 'tanggal' => '2023-07-10', 'halaman' => '1', 'catatan' => 'Revisi 2'],
            ['no_rev' => 3, 'tanggal' => '2024-04-22', 'halaman' => '1', 'catatan' => 'Revisi 3'],
            ['no_rev' => 4, 'tanggal' => '2024-05-10', 'halaman' => '1', 'catatan' => 'Revisi 4'],
            ['no_rev' => 5, 'tanggal' => '2024-12-12', 'halaman' => '1', 'catatan' => 'Revisi 5'],
            // Roll-over ke edisi 2.
            ['no_rev' => 1, 'tanggal' => '2025-03-04', 'halaman' => '1', 'catatan' => 'Revisi edisi 2 - 1'],
            ['no_rev' => 2, 'tanggal' => '2025-12-24', 'halaman' => '1', 'catatan' => 'Revisi edisi 2 - 2'],
            ['no_rev' => 3, 'tanggal' => '2026-09-12', 'halaman' => '1', 'catatan' => 'Revisi edisi 2 - 3'],
        ]);

        $res = $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'SOP'))
            ->assertOk();

        // Tanpa rantai sama sekali, seluruh 8 tanggal tetap tercetak — dari
        // catatan_revisi baris itu sendiri, bukan dari versi lain yang tak ada.
        $res->assertSee('15/05/2023')->assertSee('10/07/2023')->assertSee('22/04/2024')
            ->assertSee('10/05/2024')->assertSee('12/12/2024')
            ->assertSee('04/03/2025')->assertSee('24/12/2025')->assertSee('12/09/2026');

        // Tanggal Efektif = published_at dokumen itu sendiri (tak ada rantai
        // untuk ditelusuri lebih jauh).
        $res->assertSee('09/12/2022');
    }
}
