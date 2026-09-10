<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rollback ke versi revisi sebelumnya (PLAN-REVISI-v6 Fase G, butir 7).
 *
 * Yang dikunci di sini adalah hal-hal yang RUSAK TANPA GEJALA: draft rollback
 * yang isinya diam-diam bukan isi versi tujuan, dokumen Berlaku yang terlanjur
 * obsolete padahal revisinya belum disahkan, dan tiga penjaga yang bila jebol
 * membuat dokumen mati bisa dihidupkan lewat pintu belakang.
 */
class RollbackVersiTest extends TestCase
{
    use DatabaseTransactions;

    /** Departemen baru tiap test — suite ini berjalan di atas DB pengembangan. */
    private function dept(): Department
    {
        return Department::create(['code' => 'ROL'.Str::upper(Str::random(3)), 'name' => 'Departemen Uji Rollback']);
    }

    /**
     * Satu dokumen dengan riwayat: versi lama (obsolete) + versi Berlaku,
     * keduanya bernomor sama — persis yang dihasilkan revisi Tipe B.
     *
     * @return array{0:Document,1:Document} [versi lama, versi berlaku]
     */
    private function riwayat(User $gl, ?Department $dept = null): array
    {
        // Default DEPARTEMEN PEMBUAT, bukan departemen baru: peninjau SH-0001
        // hanya berwenang atas departemennya sendiri, jadi dokumen di dept
        // karangan tak akan pernah bisa disahkan. Nomornya tetap diacak supaya
        // tak bentrok dengan data DB pengembangan.
        $dept ??= $gl->department;
        $nomor = 'PPA-ADRO-SOP-'.$dept->code.'-'.Str::upper(Str::random(4));
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $buat = fn (string $status, int $revisi, string $isi) => tap(Document::create([
            'doc_number' => $nomor,
            'doc_number_final' => $nomor,
            'doc_number_manual' => false,
            'is_controlled' => true,
            'document_type_id' => $type->id,
            'department_id' => $dept->id,
            'title' => 'Prosedur Uji Rollback',
            'status' => $status,
            'edisi' => '1',
            'no_revisi' => $revisi,
            'created_by' => $gl->id,
            'published_at' => now(),
        ]), function (Document $d) use ($isi) {
            $d->contents()->create(['section_key' => 'tujuan', 'value_json' => [$isi]]);
            $d->authors()->create(['user_id' => $d->created_by, 'is_primary' => true]);
        });

        return [$buat('obsolete', 1, 'Isi versi lama'), $buat('published', 2, 'Isi versi berlaku')];
    }

    /** Draft rollback = isi versi tujuan, milik pembuat asli, tampil di menu Dokumen Revisi. */
    public function test_rollback_membuat_draft_berisi_versi_lama(): void
    {
        $gl = $this->aktorGl();
        [$lama, $berlaku] = $this->riwayat($gl);

        $this->actingAs($gl)->post(route('documents.rollback', $lama))->assertRedirect();

        $draft = Document::where('doc_number', $lama->doc_number)->where('status', 'draft')->firstOrFail();
        $this->assertSame($gl->id, $draft->created_by, 'draft milik PEMBUAT ASLI, bukan penekan tombol');
        $this->assertSame(['Isi versi lama'], $draft->contentMap()['tujuan'], 'isi diambil dari versi yang dituju');
        $this->assertSame($berlaku->id, $draft->revises_document_id, 'yang direvisi = versi BERLAKU, bukan versi lama');

        // Lembar Catatan Revisi terisi baris pembuka — GL tinggal melanjutkan.
        $this->assertStringContainsString('Rollback ke Edisi 1 Revisi 1',
            $draft->contentMap()['catatan_revisi'][0]['catatan'] ?? '');

        $this->actingAs($gl)->get(route('documents.revisions'))->assertOk()->assertSee($draft->title);
    }

    /** Dokumen Berlaku hanya "Sedang Direvisi" — ia BELUM obsolete. */
    public function test_dokumen_berlaku_belum_obsolete_saat_rollback_diajukan(): void
    {
        $gl = $this->aktorGl();
        [$lama, $berlaku] = $this->riwayat($gl);

        $this->actingAs($gl)->post(route('documents.rollback', $lama))->assertRedirect();

        $this->assertSame('sedang_direvisi', $berlaku->refresh()->status);
        $this->assertSame(1, $berlaku->versions()->count(), 'snapshot versi berlaku tersimpan');
    }

    /** Sesudah draft disahkan: ia Berlaku, yang tadinya Berlaku jadi obsolete, versi tujuan TETAP obsolete. */
    public function test_setelah_disahkan_versi_lama_tetap_obsolete(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();
        [$lama, $berlaku] = $this->riwayat($gl);

        $this->actingAs($gl)->post(route('documents.rollback', $lama))->assertRedirect();
        $draft = Document::where('doc_number', $lama->doc_number)->where('status', 'draft')->firstOrFail();

        $draft->update(['reviewer_id' => $sh->id, 'approver_id' => $pjo->id]);
        $this->actingAs($gl)->post(route('documents.submit', $draft))->assertRedirect();
        $this->actingAs($sh)->get(route('review.show', $draft));
        $this->actingAs($sh)->post(route('review.store', $draft), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs(User::peninjauMd()->firstOrFail())
            ->post(route('review.md.store', $draft), ['decision' => 'approve'])->assertRedirect();
        $this->actingAs($pjo)->post(route('approvals.store', $draft), ['decision' => 'approve'])->assertRedirect();

        $this->assertSame('published', $draft->refresh()->status);
        $this->assertSame(3, $draft->no_revisi, 'nomor revisi TERBARU, bukan nomor versi yang dituju');
        $this->assertSame('obsolete', $berlaku->refresh()->status);
        $this->assertSame(Document::OBSOLETE_REVISI, $berlaku->obsolete_reason);
        $this->assertSame('obsolete', $lama->refresh()->status, 'versi yang di-rollback tetap Tidak Berlaku');
    }

    /** Tiga versi obsolete satu nomor → daftar Tidak Berlaku tetap SATU grup. */
    public function test_daftar_tidak_berlaku_menampilkan_satu_grup(): void
    {
        $gl = $this->aktorGl();
        $dept = $this->dept();
        [$lama, $berlaku] = $this->riwayat($gl, $dept);
        $berlaku->update(['status' => 'obsolete', 'obsolete_reason' => Document::OBSOLETE_REVISI]);

        // Dulu menghitung sel <td> di markup Blade. Sejak Fase 8 daftarnya props
        // Inertia: satu ELEMEN `documents.data` per grup, dan versinya menempel
        // di dalamnya — yang diperiksa tetap sama, dua versi jadi satu baris.
        $documents = $this->propsInertia(
            $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
                ->get(route('documents.obsolete', ['department_id' => $dept->id]))->assertOk()
        )['documents']['data'];

        $grup = collect($documents)->where('judul', 'Prosedur Uji Rollback');

        $this->assertCount(1, $grup, 'satu BARIS induk untuk kedua versi');
        $this->assertCount(2, $grup->first()['versi']);
    }

    /** Dokumen yang DIMATIKAN (Fase F) tak bisa di-rollback — jalannya "Aktifkan". */
    public function test_dokumen_dinonaktifkan_ditolak(): void
    {
        $gl = $this->aktorGl();
        [$lama] = $this->riwayat($gl);
        $lama->update(['obsolete_reason' => Document::OBSOLETE_DINONAKTIFKAN]);

        $this->actingAs($gl)->post(route('documents.rollback', $lama))->assertStatus(422);
        $this->assertSame(0, Document::where('doc_number', $lama->doc_number)->where('status', 'draft')->count());
    }

    /** GL hanya boleh me-rollback dokumen BUATANNYA (keputusan pemilik D1). */
    public function test_gl_lain_ditolak(): void
    {
        $gl = $this->aktorGl();
        [$lama] = $this->riwayat($gl);

        $glLain = User::create([
            'name' => 'GL Departemen Lain', 'nrp' => 'GL-'.Str::upper(Str::random(5)),
            'jabatan' => User::JABATAN_GROUP_LEADER, 'department_id' => $this->dept()->id,
            'password' => bcrypt('password'), 'status' => 'active',
        ]);
        $glLain->syncRoles($gl->getRoleNames()->all());

        $this->actingAs($glLain)->post(route('documents.rollback', $lama))->assertStatus(403);
    }

    /** Sudah ada draft revisi berjalan → ditolak; dua draft atas satu nomor tak punya arti. */
    public function test_ditolak_saat_revisi_sedang_berjalan(): void
    {
        $gl = $this->aktorGl();
        [$lama, $berlaku] = $this->riwayat($gl);

        app(DocumentService::class)->requestRevision($berlaku, $gl);   // revisi berjalan

        $this->actingAs($gl)->post(route('documents.rollback', $lama))->assertStatus(422);
        $this->assertSame(1, Document::where('doc_number', $lama->doc_number)->where('status', 'draft')->count(),
            'tak ada draft KEDUA yang lahir');
    }
}
