<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menu "Log Dokumen" (PLAN-REVISI-v6 Fase C, D5).
 *
 * Halaman ini tak menyimpan apa pun sendiri — ia MENGUMPULKAN data yang sudah
 * tersebar di `document_feedback`, `reviews`, `approvals`, dan `audit_logs`.
 * Karena itu yang diuji: (a) semua sumber benar-benar terbaca, (b) lingkup
 * per-peran tak lebih longgar dari halaman lain, dan (c) tombol tindakan hanya
 * muncul bagi yang berwenang.
 */
class LogDokumenTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumenBerlaku(User $gl, string $judul): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    private function masukan(Document $doc, User $pengirim, string $isi): DocumentFeedback
    {
        return DocumentFeedback::create([
            'feedback_number' => 'MSK-'.now()->format('Y').'-8'.random_int(100, 999),
            'document_id' => $doc->id,
            'user_id' => $pengirim->id,
            'isi' => $isi,
            'status' => 'baru',
        ]);
    }

    // ── Submenu 1: Masukan Lapangan ─────────────────────────────────────────

    public function test_halaman_masukan_menampilkan_semua_masukan_departemen(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Log Masukan');
        $m = $this->masukan($doc, $nonStaff, 'Rambu simpang kurang jelas di log.');

        $resp = $this->actingAs($gl)->get(route('log.masukan'))->assertOk();

        // Judul halaman kini digambar React — dan "Masukan Lapangan" kebetulan
        // juga label sidebar, jadi assertSee atasnya hijau tanpa syarat.
        // Komponennya yang diperiksa, lalu isi barisnya.
        $props = $this->propsInertia($resp);
        $this->assertSame('V2/Log/Masukan', $resp->viewData('page')['component']);

        $baris = collect($props['masukan']['data'])->firstWhere('nomor', $m->feedback_number);

        $this->assertNotNull($baris, 'Masukan itu tak ada di daftar.');
        $this->assertSame('Rambu simpang kurang jelas di log.', $baris['isi']);
        $this->assertSame($doc->displayNumber(), $baris['dokumen']['nomor']);
    }

    /**
     * Tombol Balas & Revisi mengikuti matriks §C.1 — bukan sekadar "halaman
     * terbuka". SH melihat masukannya tapi tak diberi satu pun tombol.
     */
    public function test_tombol_tindakan_hanya_bagi_yang_berwenang(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Log Tombol');
        $m = $this->masukan($doc, $nonStaff, 'Perlu ditindaklanjuti.');

        // Dulu diperiksa lewat id modal di markup; kini kedua tombol digambar
        // dari `boleh_balas`/`boleh_revisi` per BARIS, dijawab model. Jendela
        // Revisi sendiri datang sebagai `revisi`, satu per DOKUMEN.
        $props = $this->propsInertia($this->actingAs($gl)->get(route('log.masukan'))->assertOk());
        $baris = collect($props['masukan']['data'])->firstWhere('nomor', $m->feedback_number);

        $this->assertTrue($baris['boleh_balas']);
        $this->assertTrue($baris['boleh_revisi']);
        $this->assertContains($doc->id, collect($props['revisi'])->pluck('id')->all());
        // Masukan pemicunya sudah tercentang saat jendelanya dibuka.
        $this->assertContains(
            $m->id,
            collect($props['revisi'])->firstWhere('id', $doc->id)['tercentang'],
        );

        // SH: melihat isinya, tanpa satu pun jalan tindakan.
        $props = $this->propsInertia($this->actingAs($sh)->get(route('log.masukan'))->assertOk());
        $baris = collect($props['masukan']['data'])->firstWhere('nomor', $m->feedback_number);

        $this->assertNotNull($baris, 'SH tetap MEMBACA masukannya.');
        $this->assertFalse($baris['boleh_balas']);
        $this->assertFalse($baris['boleh_revisi']);
        $this->assertSame([], $props['revisi']);
    }

    /** Non-Staff tak punya menu ini sama sekali (lingkup `dashboardPenuh`). */
    public function test_non_staff_tertutup_dari_kedua_submenu(): void
    {
        $nonStaff = $this->aktorNonStaff();

        $this->actingAs($nonStaff)->get(route('log.masukan'))->assertForbidden();
        $this->actingAs($nonStaff)->get(route('log.pesan'))->assertForbidden();
    }

    /**
     * Pagar departemen dipinjam dari `Document::scopeTerlihatOleh` — bukan
     * ditulis ulang. Yang diuji: masukan atas dokumen departemen lain tak bocor
     * kepada GL/SH, tapi terbaca oleh pemegang `document.view_all`.
     */
    public function test_masukan_departemen_lain_tak_bocor(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $pjo = $this->aktorPjo();

        $lain = Department::where('id', '!=', $gl->department_id)->firstOrFail();
        $docLain = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $lain, 'SOP Departemen Seberang',
        );
        $docLain->update(['status' => 'published', 'published_at' => now()]);
        $m = $this->masukan($docLain->refresh(), $nonStaff, 'Masukan dari seberang pagar.');

        $this->actingAs($gl)->get(route('log.masukan'))
            ->assertOk()->assertDontSee('Masukan dari seberang pagar.');

        $this->actingAs($pjo)->get(route('log.masukan'))
            ->assertOk()->assertSee($m->feedback_number);
    }

    // ── Submenu 2: Log Pesan ────────────────────────────────────────────────

    /**
     * Ketiga sumber alasan tampil di satu halaman: `reviews` (pengajuan revisi
     * & penolakan peninjau), `approvals` (penolakan penyetuju), dan
     * `audit_logs` (pengalihan peninjauan JSA).
     */
    public function test_alasan_dari_ketiga_sumber_tampil(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();
        $doc = $this->dokumenBerlaku($gl, 'SOP Log Alasan');

        // (1) Pengajuan revisi — lewat alur nyata, bukan Review buatan tangan.
        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Pit terbaru mengubah langkah lima.',
        ])->assertRedirect();
        $revisi = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        // (2) Penolakan peninjau (tanpa awalan) & (3) penolakan penyetuju.
        $revisi->reviews()->create([
            'reviewer_id' => $sh->id, 'revision_round' => 0,
            'decision' => 'needs_revision', 'summary' => 'Bab tujuan belum menyebut APD.',
        ]);
        $revisi->approvals()->create([
            'approver_id' => $pjo->id, 'decision' => 'rejected',
            'comment' => 'Belum selaras kebijakan mutu terbaru.',
        ]);

        // (4) Pengalihan peninjauan (Fase B) — alasannya di meta audit log.
        AuditLog::create([
            'user_id' => $sh->id, 'document_id' => $revisi->id,
            'action' => 'document.reassign_review',
            'meta_json' => ['alasan' => 'Peninjau sedang menumpuk JSA.'],
            'created_at' => now(),
        ]);

        $this->actingAs($gl)->get(route('log.pesan'))
            ->assertOk()
            ->assertSee('Pit terbaru mengubah langkah lima.')
            ->assertSee('Bab tujuan belum menyebut APD.')
            ->assertSee('Belum selaras kebijakan mutu terbaru.')
            ->assertSee('Peninjau sedang menumpuk JSA.');
    }

    /** Awalan `[Pengaju Revisi]` dsb. dipakai membedakan TAHAP, dan disaring. */
    public function test_penyaring_tahap_memisahkan_pengajuan_dari_penolakan(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $doc = $this->dokumenBerlaku($gl, 'SOP Log Tahap');

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Alasan pengajuan revisi.',
        ])->assertRedirect();
        $revisi = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();
        $revisi->reviews()->create([
            'reviewer_id' => $sh->id, 'revision_round' => 0,
            'decision' => 'needs_revision', 'summary' => 'Alasan penolakan peninjau.',
        ]);

        $this->actingAs($gl)->get(route('log.pesan', ['tahap' => 'pengajuan_revisi']))
            ->assertOk()
            ->assertSee('Alasan pengajuan revisi.')
            ->assertDontSee('Alasan penolakan peninjau.');

        $this->actingAs($gl)->get(route('log.pesan', ['tahap' => 'ditolak_peninjau']))
            ->assertOk()
            ->assertSee('Alasan penolakan peninjau.')
            ->assertDontSee('Alasan pengajuan revisi.');
    }

    /** SH & PJO KEHILANGAN hak merevisi, tapi TIDAK hak membaca alasannya. */
    public function test_sh_dan_pjo_tetap_membaca_alasan(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumenBerlaku($gl, 'SOP Alasan Terbaca Head');

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Alasan yang wajib terbaca atasan.',
        ])->assertRedirect();

        // Atasan yang berhak membacanya: SH sedepartemen + PJO lintas departemen
        // (CLAUDE.md §6).
        // DH sengaja TIDAK ikut di sini: di basis data belum ada Departemen
        // Head di ICTMD — satu-satunya DH aktif ada di ENGINEERING, jadi ia
        // memang TIDAK boleh melihat dokumen ICTMD. Memasukkannya berarti
        // menguji kebalikan dari aturannya sendiri. Begitu ada akun DH
        // ICTMD, kembalikan `$this->aktorDh()` ke daftar ini.
        foreach ([$this->aktorSh(), $this->aktorPjo()] as $atasan) {
            $this->actingAs($atasan)
                ->get(route('log.pesan'))
                ->assertOk()
                ->assertSee('Alasan yang wajib terbaca atasan.');
        }
    }

    /**
     * Alasan PEMUSNAHAN terbaca walau dokumennya sudah lenyap.
     *
     * Inilah pesan yang selama ini hilang tanpa jejak: auditnya ditulis dengan
     * `document_id = null` (dokumennya dihapus di transaksi yang sama), sehingga
     * penyaring `whereHas('document')` membuangnya diam-diam — padahal alasannya
     * wajib minimal 10 karakter, artinya seseorang memang menuliskannya.
     */
    public function test_alasan_pemusnahan_terbaca_meski_dokumennya_lenyap(): void
    {
        $gl = $this->aktorGl();
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $doc = $this->dokumenBerlaku($gl, 'SOP Yang Akan Dimusnahkan');
        $nomor = $doc->displayNumber();

        $this->actingAs($admin)->delete(route('documents.purge', $doc), [
            'alasan' => 'Digantikan prosedur gabungan lintas departemen.',
            'konfirmasi_nomor' => $nomor,
        ])->assertRedirect(route('documents.published'));

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);

        $this->actingAs($admin)->get(route('log.pesan'))
            ->assertOk()
            ->assertSee('Digantikan prosedur gabungan lintas departemen.')
            ->assertSee($nomor)
            ->assertSee('Dokumen dimusnahkan');

        // Tahapnya ikut ke dropdown penyaring, bukan cuma muncul di tabel.
        $this->actingAs($admin)->get(route('log.pesan', ['tahap' => 'musnahkan']))
            ->assertOk()->assertSee('Digantikan prosedur gabungan lintas departemen.');
    }

    /** Balasan SH/DH atas masukan lapangan ikut terkumpul sebagai pesan. */
    public function test_balasan_masukan_muncul_sebagai_tahap_tersendiri(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl, 'SOP Balasan Masukan');

        $this->masukan($doc, $nonStaff, 'Rambu di simpang tiga sudah pudar.')
            ->update([
                'status' => 'dibaca',
                'balasan' => 'Sudah diteruskan ke SHE, penggantian minggu depan.',
                'replied_by' => $sh->id,
                'replied_at' => now(),
            ]);

        $this->actingAs($gl)->get(route('log.pesan', ['tahap' => 'balasan_masukan']))
            ->assertOk()
            ->assertSee('Sudah diteruskan ke SHE, penggantian minggu depan.')
            ->assertSee('Balasan masukan');
    }

    /** Menu sidebar muncul bagi yang berhak, dan tidak bagi Non-Staff. */
    public function test_menu_sidebar_mengikuti_hak_akses(): void
    {
        // Dibaca dari props `navigation`, bukan HTML: sidebar sudah pindah ke
        // sana (lihat NavigasiSidebar), dan di dalam payload JSON URL-nya
        // ter-escape sehingga `assertSee(route(...))` gagal karena ejaan, bukan
        // karena menunya hilang.
        $sh = collect($this->menuSidebar(
            $this->actingAs($this->aktorSh())->get(route('dashboard'))->assertOk()
        ));

        $this->assertContains('Log Dokumen', $sh->pluck('label')->all());
        $this->assertContains(route('log.masukan'), $sh->pluck('href')->all());

        $nonStaff = collect($this->menuSidebar(
            $this->actingAs($this->aktorNonStaff())->get(route('dashboard'))->assertOk()
        ));

        $this->assertNotContains(route('log.masukan'), $nonStaff->pluck('href')->all());
    }

    /**
     * Log Pesan tetap 200 walau SATU-SATUNYA sumber yang berisi adalah
     * pemusnahan — regresi `Call to a member function getKey() on array`.
     *
     * `Eloquent\Collection::map()` hanya turun ke koleksi dasar bila menemukan
     * item non-Model; koleksi KOSONG tak punya item untuk diperiksa, jadi ia
     * tetap Eloquent — dan `merge()`-nya memanggil `getKey()` pada array biasa
     * yang datang dari sumber berisi berikutnya. Karena itu crash-nya bergantung
     * data: ia hanya muncul bila sumber-sumber sebelumnya kebetulan kosong.
     */
    public function test_log_pesan_tetap_terbuka_saat_hanya_pemusnahan_yang_berisi(): void
    {
        DB::table('reviews')->delete();
        DB::table('approvals')->delete();
        DB::table('audit_logs')->where('action', 'document.reassign_review')->delete();

        $gl = $this->aktorGl();
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $doc = $this->dokumenBerlaku($gl, 'SOP Sumber Tunggal Pemusnahan');

        $this->actingAs($admin)->delete(route('documents.purge', $doc), [
            'alasan' => 'Satu-satunya pesan yang tersisa di basis data uji.',
            'konfirmasi_nomor' => $doc->displayNumber(),
        ])->assertRedirect(route('documents.published'));

        $this->actingAs($admin)->get(route('log.pesan'))
            ->assertOk()
            ->assertSee('Satu-satunya pesan yang tersisa di basis data uji.');
    }
}
