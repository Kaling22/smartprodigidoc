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
     * Lebar kolom adaptif + strip. Edisi yang sudah lewat tampil PENUH rev 0–4
     * (roll-over §7 menjamin kelimanya ada); hanya edisi terakhir yang dipotong
     * di revisi tertinggi yang benar-benar ada. Tanggal tiap revisi dibaca dari
     * rantai `revises_document_id`, bukan dari dokumen yang berlaku sekarang —
     * dokumen itu hanya tahu tanggalnya sendiri.
     */
    public function test_kolom_revisi_adaptif_dan_sel_kosong_berstrip(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        $lama = $svc->createDraft($gl, $type, $gl->department, 'SOP Versi Lama');
        $lama->update([
            'status' => 'obsolete', 'edisi' => '2', 'no_revisi' => 0,
            'published_at' => '2024-01-10 08:00:00',
        ]);

        $baru = $svc->createDraft($gl, $type, $gl->department, 'SOP Sudah Direvisi');
        $baru->update([
            'status' => 'published', 'edisi' => '2', 'no_revisi' => 1,
            'revises_document_id' => $lama->id, 'published_at' => '2024-06-20 08:00:00',
        ]);

        // Dokumen yang belum pernah direvisi — pembanding untuk sel berstrip.
        $polos = $svc->createDraft($gl, $type, $gl->department, 'SOP Belum Direvisi');
        $polos->update(['status' => 'published', 'edisi' => '1', 'no_revisi' => 0, 'published_at' => '2025-02-02 08:00:00']);

        $res = $this->actingAs($this->aktorPjo())
            ->get(route('documents.export', 'SOP'))
            ->assertOk();

        // Edisi 1 penuh (5 kolom), Edisi 2 dipotong di revisi 1 (2 kolom).
        $res->assertSee('<th colspan="5">Edisi 1</th>', false)
            ->assertSee('<th colspan="2">Edisi 2</th>', false)
            ->assertDontSee('Edisi 3');

        // Tanggal versi lama datang dari rantai, bukan dari baris yang berlaku.
        $res->assertSee('10/01/2024')->assertSee('20/06/2024');

        // Sel yang tak berlaku diberi strip, bukan dibiarkan kosong.
        $res->assertSee('class="center">-</td>', false);
    }
}
