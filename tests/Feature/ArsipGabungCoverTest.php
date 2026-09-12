<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\Print\ArsipPenggabung;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Jalur AKTIF (bawaan) dari saklar Admin `arsip.gabung_cover_enabled`:
 * menyimpan lembar Catatan Revisi dokumen lama langsung memotong halaman 1-2
 * berkas PDF asli dan menggantinya dengan Cover + Catatan Revisi hasil cetak
 * sistem ({@see \App\Services\Print\ArsipPenggabung}) — TANPA draft/wizard
 * sama sekali. Kembaran {@see ArsipCatatanRevisiTest}, yang memaksa saklar ini
 * NONAKTIF dan menjaga jalur "ketik ulang di wizard" sebagai jalan turun.
 *
 * Yang dikunci di sini:
 *   1. Bawaan sistem (tanpa baris `pengaturan` sama sekali) adalah AKTIF.
 *   2. Jumlah halaman akhir TETAP sama dengan berkas asli (2 halaman terpotong
 *      diganti 2 halaman baru) — bukti potongannya persis "halaman 1 dan 2".
 *   3. Dokumen TETAP `published`; tak ada draft/revisi yang lahir.
 *   4. Berkas ASLI (`arsip_path_asli`) tak pernah ditimpa — mengulang lembar
 *      Catatan Revisi tak boleh memakan halaman lebih jauh.
 *   5. PDF tak terbaca → galat tampil di form, berkas tak tersentuh.
 *   6. Saklar Admin benar-benar mengubah cabang yang dipakai `simpanCatatan()`.
 *   7. Peninjau & Penyetuju WAJIB dipilih (dan harus kandidat SAH) saat aktif —
 *      Cover-nya butuh nama+jabatan keduanya untuk kotak pengesahan, dan
 *      dokumen arsip ini tak melewati alur tinjau sungguhan untuk
 *      mendapatkannya sendiri.
 */
class ArsipGabungCoverTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        // Larik statis `Pengaturan::$ingatan` tak ikut rollback transaksi —
        // lihat catatan yang sama di ArsipCatatanRevisiTest::tearDown().
        Pengaturan::lupakanIngatan();

        parent::tearDown();
    }

    /** PDF sungguhan berisi $halaman halaman — FPDI menolak PDF tiruan. */
    private function pdfNyata(int $halaman): string
    {
        $pdf = new \FPDF;
        for ($i = 1; $i <= $halaman; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 24);
            $pdf->Cell(0, 20, "Halaman {$i}");
        }

        return $pdf->Output('S');
    }

    private function gl(): User
    {
        return $this->berprofilPenuh($this->aktorGl());
    }

    /** Daftarkan dokumen lama; kembalikan barisnya. */
    private function daftarkan(string $isiPdf, string $jenis = 'SOP'): Document
    {
        $gl = $this->gl();

        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => DocumentType::where('code', $jenis)->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Prosedur Lama Gabung Cover',
            'doc_number' => 'ARSIP-GAB-'.Str::upper(Str::random(8)),
            'edisi' => 2,
            'no_revisi' => 3,
            'berkas' => UploadedFile::fake()->createWithContent('lama.pdf', $isiPdf),
        ]);

        return Document::whereNotNull('arsip_path')->latest('id')->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function kirimCatatan(Document $doc, array $ubah = []): TestResponse
    {
        return $this->actingAs($this->gl())->post(route('documents.arsip.catatan.store', $doc), array_merge([
            'edisi' => 2,
            'no_revisi' => 3,
            // Kandidat sah untuk SOP dept ICTMD (DocumentParticipantResolver):
            // peninjau = SH/DH dept sendiri, penyetuju = PJO.
            'reviewer_id' => $this->aktorSh()->id,
            'approver_id' => $this->aktorPjo()->id,
            'sections' => ['catatan_revisi' => [
                ['no_rev' => 3, 'tanggal' => '2020-01-31', 'halaman' => '1-2', 'catatan' => 'Penyesuaian alur kerja.'],
            ]],
        ], $ubah));
    }

    private function halaman(string $path): int
    {
        return app(ArsipPenggabung::class)->jumlahHalaman(Storage::disk('local')->path($path));
    }

    /** Janji 1 — tanpa baris `pengaturan` sama sekali, saklarnya AKTIF. */
    public function test_bawaan_sistem_aktif(): void
    {
        $this->assertTrue(Pengaturan::arsipGabungCoverAktif());
    }

    /**
     * Janji 2 & 3 — potong PERSIS halaman 1-2, dokumen tetap Berlaku tanpa draft.
     */
    public function test_simpan_catatan_memotong_halaman_1_2_tanpa_draft(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(5));
        $asli = $doc->arsip_path;

        $this->kirimCatatan($doc)->assertRedirect(route('documents.published'));

        $doc->refresh();
        $this->assertSame('published', $doc->status, 'dokumen tetap Berlaku, tak pernah masuk sedang_direvisi');
        $this->assertSame(0, Document::where('revises_document_id', $doc->id)->count(),
            'tak ada draft/revisi yang lahir dari jalur gabung');
        $this->assertNotSame($asli, $doc->arsip_path, 'berkas yang disajikan sudah berkas hasil gabung');
        $this->assertSame($asli, $doc->arsip_path_asli, 'berkas asli tersimpan utuh sebagai rujukan potongan ulang');
        $this->assertSame(5, $this->halaman($doc->arsip_path),
            '5 halaman asli - 2 dipotong + 2 (Cover, Catatan Revisi) = 5 halaman');
        $this->assertSame('Penyesuaian alur kerja.', $doc->contentMap()['catatan_revisi'][0]['catatan']);
    }

    /** Janji 4 — berkas ASLI tak pernah ditimpa; mengulang lembar tak memakan halaman lagi. */
    public function test_berkas_asli_tak_tersentuh_saat_diulang(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(4));
        $asli = $doc->arsip_path;

        $this->kirimCatatan($doc)->assertRedirect();
        $this->assertSame(4, $this->halaman($doc->refresh()->arsip_path));

        // Mengulang (mis. lembar Catatan Revisi disunting lagi) — potongannya
        // SELALU dihitung dari arsip_path_asli, bukan hasil gabung sebelumnya.
        $this->kirimCatatan($doc, [
            'sections' => ['catatan_revisi' => [
                ['no_rev' => 3, 'tanggal' => '2020-01-31', 'halaman' => '1-2', 'catatan' => 'Perbaikan salah ketik.'],
            ]],
        ])->assertRedirect();

        $doc->refresh();
        $this->assertSame($asli, $doc->arsip_path_asli, 'berkas asli tetap sama, tak pernah berpindah');
        $this->assertSame(4, $this->halaman($asli), 'berkas asli sendiri tak berkurang halamannya');
        $this->assertSame(4, $this->halaman($doc->arsip_path), 'hasil gabung kedua tetap 4 halaman, tak menyusut');
        $this->assertSame('Perbaikan salah ketik.', $doc->contentMap()['catatan_revisi'][0]['catatan']);
    }

    /** Janji 5 — PDF tak terbaca (terkunci/rusak) gagal DENGAN GALAT, berkas tak tersentuh. */
    public function test_pdf_tak_terbaca_menampilkan_galat_tanpa_menyentuh_berkas(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan("%PDF-1.4\nisi berkas ini bukan struktur PDF yang sah\n%%EOF");
        $asli = $doc->arsip_path;

        $this->kirimCatatan($doc)->assertSessionHasErrors('berkas');

        $doc->refresh();
        $this->assertSame($asli, $doc->arsip_path, 'berkas tak boleh berubah saat gabung gagal');
        $this->assertNull($doc->arsip_path_asli);
        $this->assertSame('published', $doc->status);
    }

    /** Janji 6 — mematikan saklar mengembalikan jalur lama (draft/wizard). */
    public function test_saklar_dimatikan_mengembalikan_jalur_wizard(): void
    {
        Storage::fake('local');

        $this->actingAs($this->aktorAdmin())
            ->put(route('pengaturan.sistem.arsip-gabung'), ['enabled' => false])
            ->assertSessionHasNoErrors();

        $this->assertFalse(Pengaturan::arsipGabungCoverAktif());

        $doc = $this->daftarkan($this->pdfNyata(3));
        $asli = $doc->arsip_path;

        $this->kirimCatatan($doc)->assertRedirect();

        $draft = Document::where('revises_document_id', $doc->id)->firstOrFail();
        $this->assertSame('draft', $draft->status);

        $doc->refresh();
        $this->assertSame('sedang_direvisi', $doc->status);
        $this->assertSame($asli, $doc->arsip_path, 'jalur lama tak pernah menyentuh berkas unggahan');
    }

    /** Rute setelan hanya untuk pemegang `user.manage`. */
    public function test_saklar_hanya_untuk_admin(): void
    {
        $this->actingAs($this->gl())
            ->put(route('pengaturan.sistem.arsip-gabung'), ['enabled' => false])
            ->assertForbidden();
    }

    /** Janji 7 — Peninjau & Penyetuju yang dipilih tersimpan di dokumen (dibaca Cover). */
    public function test_peninjau_dan_penyetuju_tersimpan_di_dokumen(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();

        $this->kirimCatatan($doc, ['reviewer_id' => $sh->id, 'approver_id' => $pjo->id])->assertRedirect();

        $doc->refresh();
        $this->assertSame($sh->id, $doc->reviewer_id);
        $this->assertSame($pjo->id, $doc->approver_id);
    }

    /** Janji 7 — tanpa Peninjau/Penyetuju, ditolak dan berkas tak tersentuh. */
    public function test_peninjau_dan_penyetuju_wajib_saat_gabung_aktif(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $asli = $doc->arsip_path;

        $this->kirimCatatan($doc, ['reviewer_id' => null, 'approver_id' => null])
            ->assertSessionHasErrors(['reviewer_id', 'approver_id']);

        $doc->refresh();
        $this->assertNull($doc->reviewer_id);
        $this->assertNull($doc->approver_id);
        $this->assertSame($asli, $doc->arsip_path, 'berkas tak boleh berubah saat validasi gagal');
    }

    /** Janji 7 — id yang bukan kandidat sah (mis. GL, bukan SH/DH/PJO) ditolak. */
    public function test_peninjau_bukan_kandidat_sah_ditolak(): void
    {
        Storage::fake('local');
        $doc = $this->daftarkan($this->pdfNyata(3));
        $asli = $doc->arsip_path;

        // GL bukan kandidat peninjau MAUPUN penyetuju untuk SOP (SH/DH dept &
        // PJO saja) — DocumentParticipantResolver yang menegakkannya.
        $this->kirimCatatan($doc, ['reviewer_id' => $this->aktorGl()->id])
            ->assertSessionHasErrors('reviewer_id');

        $doc->refresh();
        $this->assertNull($doc->reviewer_id);
        $this->assertSame($asli, $doc->arsip_path);
    }
}
