<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\UserOffDay;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengalihan tugas peninjauan JSA (PLAN-REVISI-v6 butir 3).
 *
 * Yang dikunci di sini bukan cuma "tombolnya bekerja", melainkan BATAS-nya:
 * pengalihan memindahkan tanggung jawab tanpa satu pun gerbang persetujuan,
 * jadi setiap penjagaannya harus punya testnya sendiri.
 */
class PengalihanPeninjauanTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * GL SHE KEDUA — lawan main pengalihan; dibuat di dalam transaksi &
     * ikut di-rollback.
     *
     * Dulu ia GL Plant. PLAN-AKSES-v7 Fase 3 mencabut Plant dari peninjauan
     * JSA, jadi tujuan pengalihan yang sah kini sesama GL SHE. Premisnya tetap
     * sah — bahkan lebih tajam: dokumen ujinya milik ICTMD, sehingga KEDUA GL
     * SHE sama-sama kandidat dan pengalihan memang punya tujuan yang benar.
     */
    private function glSheLain(string $tanda = 'A'): User
    {
        $u = User::create([
            'name' => 'GL SHE Uji '.$tanda,
            'nrp' => 'GLSHU-'.$tanda,
            'jabatan' => 'group_leader',
            'department_id' => Department::where('code', 'SHE')->firstOrFail()->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole('group_leader');

        return $u->fresh();
    }

    /** JSA milik GL ICTMD yang sedang dipegang $peninjau. */
    private function jsa(User $peninjau, string $status = 'in_review'): Document
    {
        $gl = $this->aktorGl();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Dialihkan'
        );
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);
        $doc->update(['reviewer_id' => $peninjau->id, 'status' => $status]);

        return $doc->fresh();
    }

    public function test_gl_she_mengalihkan_jsa_ke_gl_she_lain(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain();
        $doc = $this->jsa($glShe);
        $pembuat = $doc->creator;

        foreach ([$glSheLain, $pembuat] as $u) {
            $u->notifications()->delete();
        }

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $glSheLain->id,
            'alasan' => 'Sedang menangani 6 JSA lain minggu ini.',
        ])->assertRedirect(route('review.index'));

        $doc->refresh();
        $this->assertSame($glSheLain->id, $doc->reviewer_id, 'dokumen berpindah tangan');
        $this->assertSame('waiting_for_review', $doc->status, 'penerima belum menyentuhnya');

        $this->assertSame(1, $glSheLain->fresh()->unreadNotifications()->count(), 'penerima diberi tahu');
        $this->assertSame(1, $pembuat->fresh()->unreadNotifications()->count(), 'pembuat diberi tahu');

        $log = AuditLog::where('document_id', $doc->id)->where('action', 'document.reassign_review')->firstOrFail();
        $this->assertSame($glShe->name, $log->meta_json['dari']);
        $this->assertSame($glSheLain->name, $log->meta_json['ke']);
        $this->assertStringContainsString('6 JSA lain', $log->meta_json['alasan']);

        // Tombolnya lenyap dari layar orang yang sudah melepasnya, dan muncul
        // di layar penerimanya. Sejak Fase 10 syaratnya satu props: TSX hanya
        // merender tombol Alihkan bila `alihKandidat` berisi.
        $this->actingAs($glSheLain)->get(route('review.show', $doc))
            ->assertOk()
            // Ukurannya sengaja tak dipatok: kandidatnya datang dari
            // `DocumentParticipantResolver` dan ikut tumbuh bersama data
            // sungguhan di basis data pengembangan. Yang dijaga cuma ADA-nya.
            ->assertInertia(fn (Assert $page) => $page->has('alihKandidat.0'));
    }

    /**
     * Penerima MEMBACA alasannya di halaman tinjau — bukan hanya di email.
     *
     * Lonceng menyimpan `message` saja, jadi tanpa ini alasan pengalihan lenyap
     * dari layar begitu notifikasinya ditandai terbaca. Yang dijaga: kotaknya
     * tetap ada SESUDAH notifikasi dibaca, dan tak muncul di layar orang yang
     * sudah melepas dokumennya.
     */
    public function test_penerima_membaca_alasan_pengalihan_di_halaman_tinjau(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('R');
        $doc = $this->jsa($glShe);

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $glSheLain->id,
            'alasan' => 'Saya sedang cuti dua minggu mulai Senin.',
        ])->assertRedirect(route('review.index'));

        // Notifikasi ditandai terbaca dulu: justru sesudah inilah alasannya
        // dulu hilang tanpa jejak di layar.
        $glSheLain->fresh()->unreadNotifications->markAsRead();

        $this->actingAs($glSheLain)->get(route('review.show', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pengalihan.alasan', 'Saya sedang cuti dua minggu mulai Senin.')
                ->where('pengalihan.dari', $glShe->name)
            );

        // Pemegang lama membuka dokumen lain miliknya: kotak itu bukan miliknya.
        $lain = $this->jsa($glShe);
        $this->actingAs($glShe)->get(route('review.show', $lain))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('pengalihan', null));
    }

    /** Alasan WAJIB — tanpanya pengalihan tak punya jejak yang bisa dibaca. */
    public function test_alasan_wajib_diisi(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('B');
        $doc = $this->jsa($glShe);

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $glSheLain->id,
        ])->assertSessionHasErrors('alasan');

        $this->assertSame($glShe->id, $doc->refresh()->reviewer_id, 'dokumen tak bergerak');
    }

    /** Pengalihan HANYA JSA — jenis lain tak punya kolam peninjau setara. */
    public function test_bukan_jsa_ditolak(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('C');
        $gl = $this->aktorGl();

        $sop = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Tak Bisa Dialihkan'
        );
        $sop->update(['reviewer_id' => $glShe->id, 'status' => 'in_review']);

        $this->actingAs($glShe)->post(route('review.alihkan', $sop), [
            'tujuan' => $glSheLain->id, 'alasan' => 'Coba alihkan SOP.',
        ])->assertStatus(422);
    }

    /** Hanya PEMEGANGNYA yang boleh melepas — bukan sesama peninjau JSA. */
    public function test_bukan_pemegang_ditolak(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('D');
        $doc = $this->jsa($glShe);

        $this->actingAs($glSheLain)->post(route('review.alihkan', $doc), [
            'tujuan' => $glShe->id, 'alasan' => 'Bukan urusan saya.',
        ])->assertForbidden();

        $this->assertSame($glShe->id, $doc->refresh()->reviewer_id);
    }

    /**
     * Tujuan di luar daftar resolver ditolak — termasuk GL departemen dokumen
     * itu sendiri, yang justru konflik kepentingan yang sengaja dikecualikan.
     */
    public function test_tujuan_di_luar_kandidat_ditolak(): void
    {
        $glShe = $this->aktorGl('SHE');
        $gl = $this->aktorGl();     // ICTMD — pembuatnya
        $doc = $this->jsa($glShe);

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $gl->id, 'alasan' => 'Serahkan ke pembuatnya saja.',
        ])->assertStatus(422);

        $this->assertSame($glShe->id, $doc->refresh()->reviewer_id);
    }

    /** Peninjau yang sedang off tak boleh menerima dokumen baru. */
    public function test_tujuan_sedang_off_ditolak(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('E');
        $doc = $this->jsa($glShe);

        UserOffDay::create([
            'user_id' => $glSheLain->id,
            'jenis' => 'cuti',
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $glSheLain->id, 'alasan' => 'Meja saya penuh.',
        ])->assertStatus(422);

        $this->assertSame($glShe->id, $doc->refresh()->reviewer_id);
    }

    /** Dokumen yang sudah lepas dari tangan peninjau tak bisa dialihkan lagi. */
    public function test_status_di_luar_peninjauan_ditolak(): void
    {
        $glShe = $this->aktorGl('SHE');
        $glSheLain = $this->glSheLain('F');
        $doc = $this->jsa($glShe, 'published');

        $this->actingAs($glShe)->post(route('review.alihkan', $doc), [
            'tujuan' => $glSheLain->id, 'alasan' => 'Sudah terlambat.',
        ])->assertStatus(422);

        $this->assertSame($glShe->id, $doc->refresh()->reviewer_id);
    }
}
