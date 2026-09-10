<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Penomoran dokumen: DUA tahap, DUA kolam, aturannya sengaja berbeda.
 *
 * Yang dikunci di sini adalah tiga janji yang gampang rusak diam-diam:
 * draft yang dibatalkan MENGEMBALIKAN nomornya, nomor yang pernah terbit TIDAK
 * pernah lahir kembali, dan layar pra-terbit TIDAK pernah menampilkan nomor
 * final. Ketiganya tak kelihatan salah sampai ada dua dokumen bernomor sama.
 */
class DocumentNumberingTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Departemen yang BERSIH, supaya urutan nomornya bisa diramalkan.
     *
     * Kodenya sengaja huruf saja: nomor urut dibaca dari angka di UJUNG, dan
     * kode berangka membuat pembacaannya ambigu.
     */
    private function dept(): Department
    {
        return Department::create([
            'code' => 'UJI'.strtoupper(\Illuminate\Support\Str::random(3)),
            'name' => 'Departemen Uji Penomoran',
        ]);
    }

    /** Nomor urut (angka di ujung) dari sebuah nomor dokumen. */
    private function seqOf(?string $nomor): int
    {
        return preg_match('/(\d+)$/', (string) $nomor, $m) ? (int) $m[1] : 0;
    }

    private function draft(Department $dept, string $judul): Document
    {
        return app(DocumentService::class)->createDraft(
            $this->aktorGl(),
            DocumentType::where('code', 'SOP')->firstOrFail(),
            $dept,
            $judul,
        );
    }

    private function seq(Document $doc): int
    {
        return $this->seqOf($doc->displayNumber());
    }

    /**
     * Draft yang dihapus mengembalikan nomornya — draft berikutnya MENGISI celah
     * itu, bukan naik satu. Draft belum tentu jadi dokumen; ia tak berhak
     * menyandera nomor selamanya.
     */
    public function test_draft_dihapus_mengembalikan_nomornya(): void
    {
        $dept = $this->dept();
        $gl = $this->aktorGl();

        $satu = $this->draft($dept, 'SOP Satu');
        $dua = $this->draft($dept, 'SOP Dua');
        $tiga = $this->draft($dept, 'SOP Tiga');

        $this->assertSame([1, 2, 3], [$this->seq($satu), $this->seq($dua), $this->seq($tiga)]);

        $this->actingAs($gl)->delete(route('documents.destroy', $dua))->assertRedirect();

        $baru = $this->draft($dept, 'SOP Pengganti');
        $this->assertSame(2, $this->seq($baru), 'draft baru harus mengisi celah bekas draft yang dihapus');
    }

    /**
     * Nomor yang pernah TERBIT ditahan selamanya — bahkan sesudah arsipnya
     * dibersihkan. Nomor dokumen mutu dirujuk di luar sistem (audit, dokumen
     * lain, cetakan lapangan); mendaur ulangnya membuat satu nomor menunjuk dua
     * dokumen berbeda sepanjang sejarah.
     */
    public function test_nomor_yang_pernah_terbit_tak_pernah_lahir_kembali(): void
    {
        $dept = $this->dept();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $nomor = app(DocumentNumberService::class);

        $terbit = $this->draft($dept, 'SOP Terbit');
        $terbit->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $nomor->generateFinal($type, $dept)]);
        $this->assertSame(1, $this->seq($terbit->refresh()));

        // Dinonaktifkan → nomornya TETAP tertahan.
        $terbit->update(['status' => 'obsolete']);
        $this->assertSame(2, $this->seqOf($nomor->generateFinal($type, $dept)),
            'dokumen nonaktif menahan nomornya');

        // Arsipnya dibersihkan → nomornya TETAP tertahan.
        $terbit->delete();
        $this->assertSame(2, $this->seqOf($nomor->generateFinal($type, $dept)),
            'nomor terbit tak boleh kembali ke kolam meski arsipnya dihapus');

        $this->assertFalse($nomor->isUnique($terbit->doc_number_final),
            'nomor terbit juga tak boleh bisa diketik manual');
    }

    /** Dokumen yang disahkan MENGISI celah nomor final, bukan menambah di ujung. */
    public function test_dokumen_disahkan_mengisi_celah_nomor_final(): void
    {
        $dept = $this->dept();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $nomor = app(DocumentNumberService::class);

        foreach ([1, 3] as $seq) {
            $d = $this->draft($dept, "SOP Terbit {$seq}");
            $d->update([
                'status' => 'published',
                'doc_number_final' => sprintf('PPA-ADRO-SOP-%s-%02d', $dept->code, $seq),
            ]);
        }

        $this->assertSame(2, $this->seqOf($nomor->generateFinal($type, $dept)));
    }

    /** Draft tak pernah menampilkan nomor yang sedang dipegang dokumen Berlaku. */
    public function test_draft_tak_memakai_nomor_dokumen_berlaku(): void
    {
        $dept = $this->dept();

        $terbit = $this->draft($dept, 'SOP Berlaku');
        $terbit->update([
            'status' => 'published',
            'doc_number_final' => sprintf('PPA-ADRO-SOP-%s-02', $dept->code),
        ]);

        // Nomor sementara 01 miliknya sendiri masih terpakai, dan 02 kini
        // dipegang versi terbitnya — jadi draft berikutnya harus lompat ke 03.
        $this->assertSame(3, $this->seq($this->draft($dept, 'SOP Draft Baru')));
    }

    /**
     * ATURAN TAMPIL: nomor final hanya muncul setelah dokumen Berlaku.
     *
     * Seluruh layar pra-terbit ("Dokumen Saya", "Status Dokumen Staff", "Status
     * Dokumen Departemen", antrean tinjau & persetujuan) membaca displayNumber(),
     * jadi cukup mengunci fungsi itu — bukan sembilan halaman satu per satu.
     */
    public function test_nomor_final_hanya_muncul_setelah_berlaku(): void
    {
        $dept = $this->dept();
        $doc = $this->draft($dept, 'SOP Aturan Tampil');
        $sementara = $doc->doc_number;

        foreach (['draft', 'waiting_for_review', 'in_review', 'verifikasi_md', 'pending_approval', 'rejected'] as $status) {
            $doc->update(['status' => $status]);
            $this->assertSame($sementara, $doc->refresh()->displayNumber(),
                "status {$status} harus menampilkan nomor sementara");
            $this->assertFalse($doc->hasFinalNumber());
        }

        $final = sprintf('PPA-ADRO-SOP-%s-09', $dept->code);
        $doc->update(['status' => 'published', 'doc_number_final' => $final]);

        $this->assertSame($final, $doc->refresh()->displayNumber());
        $this->assertTrue($doc->hasFinalNumber());
    }

    /** Draft revisi mewarisi nomor versi lama, tak memakan nomor baru. */
    public function test_draft_revisi_mewarisi_nomor_versi_lama(): void
    {
        $dept = $this->dept();
        $sh = $this->aktorSh();

        $terbit = $this->draft($dept, 'SOP Akan Direvisi');
        $final = sprintf('PPA-ADRO-SOP-%s-01', $dept->code);
        $terbit->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $final]);

        $revisi = app(DocumentService::class)->requestRevision($terbit->refresh(), $sh);

        $this->assertSame($final, $revisi->doc_number, 'draft revisi mewarisi nomor versi lama');
        $this->assertNull($revisi->doc_number_final, 'nomor final baru dikunci saat revisinya disahkan');
        $this->assertSame($final, $revisi->displayNumber());
    }

    /**
     * Dokumen menunggu persetujuan, siap di-POST ke `approvals.store`.
     *
     * Lewat CONTROLLER, bukan `update()` langsung: yang diuji butir 2 justru
     * baris yang menimpa nomor tepat pada detik pengesahan, dan baris itu tak
     * pernah dijalankan kalau statusnya diubah dari dalam test.
     */
    private function menungguPersetujuan(Department $dept, string $judul, ?string $manual = null): array
    {
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();

        $doc = $this->draft($dept, $judul);
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan uji']]);
        $doc->update([
            'status' => 'pending_approval',
            'reviewer_id' => $sh->id,
            'approver_id' => $pjo->id,
        ] + ($manual ? ['doc_number' => $manual, 'doc_number_manual' => true] : []));

        return [$doc, $pjo];
    }

    /**
     * BUTIR 2 — nomor yang diketik tangan BERTAHAN sampai dokumen berlaku.
     *
     * Sebelum perbaikan, `doc_number_final` masih NULL sampai pengesahan dan
     * dokumen non-revisi tak pernah memeriksa `doc_number_manual`, sehingga
     * `generateFinal()` menimpanya DIAM-DIAM tepat pada detik dokumen menjadi
     * Berlaku. Bukan dugaan: audit log dokumen 1151 merekamnya — dibuat dengan
     * `…-10`, disahkan menjadi `…-02`.
     */
    public function test_nomor_manual_bertahan_saat_dokumen_disahkan(): void
    {
        $dept = $this->dept();
        $manual = sprintf('PPA-ADRO-SOP-%s-99', $dept->code);
        [$doc, $pjo] = $this->menungguPersetujuan($dept, 'SOP Nomor Manual', $manual);

        $this->actingAs($pjo)->post(route('approvals.store', $doc), ['decision' => 'approve'])
            ->assertRedirect();

        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertSame($manual, $doc->doc_number_final,
            'nomor ketikan tangan tak boleh ditimpa generateFinal() saat pengesahan');

        // Nomor 99 dipakai, tapi ia TIDAK menggeser urutan otomatis ke 100:
        // kolam nomor mengisi celah dari bawah, jadi dokumen berikutnya dapat 1.
        $this->assertSame(1, $this->seqOf(
            app(DocumentNumberService::class)->generateFinal(
                DocumentType::where('code', 'SOP')->firstOrFail(), $dept
            )
        ), 'dokumen berikutnya tak mewarisi lompatan nomor manual');
    }

    /**
     * BUTIR 2 — nomor manual yang BENTROK ditolak saat pengesahan, bukan
     * diterima lalu melahirkan dua dokumen berlaku bernomor sama.
     *
     * Pemeriksaannya sengaja di sini, bukan hanya di Form Request: antara saat
     * nomor diketik dan saat dokumen disahkan bisa lewat berhari-hari, dan
     * dokumen lain bisa mengambil nomor itu di tengah-tengahnya.
     */
    public function test_nomor_manual_bentrok_ditolak_saat_pengesahan(): void
    {
        $dept = $this->dept();
        $manual = sprintf('PPA-ADRO-SOP-%s-99', $dept->code);

        // Nomor itu sudah dipegang dokumen lain yang TERBIT lebih dulu.
        $pemegang = $this->draft($dept, 'SOP Pemegang 99');
        $pemegang->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $manual]);

        [$doc, $pjo] = $this->menungguPersetujuan($dept, 'SOP Nomor Kembar', $manual);

        $this->actingAs($pjo)->post(route('approvals.store', $doc), ['decision' => 'approve'])
            ->assertSessionHasErrors('doc_number');

        $doc->refresh();
        $this->assertSame('pending_approval', $doc->status, 'dokumen tetap menunggu, tidak terbit');
        $this->assertNull($doc->doc_number_final);
        $this->assertSame(0, $doc->approvals()->count(),
            'persetujuan tak boleh tercatat untuk pengesahan yang gagal');
    }

    /** REGRESI: dokumen bernomor OTOMATIS tak berubah perilakunya sedikit pun. */
    public function test_nomor_otomatis_tetap_dibangkitkan_saat_pengesahan(): void
    {
        $dept = $this->dept();
        [$doc, $pjo] = $this->menungguPersetujuan($dept, 'SOP Nomor Otomatis');

        $this->actingAs($pjo)->post(route('approvals.store', $doc), ['decision' => 'approve'])
            ->assertRedirect();

        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertSame(1, $this->seqOf($doc->doc_number_final),
            'nomor final tetap dari kolam urut, bukan dari nomor sementara');
    }
}
