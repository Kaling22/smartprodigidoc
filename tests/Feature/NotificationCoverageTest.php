<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * CAKUPAN PEMBERITAHUAN (v5 Fase D).
 *
 * Dua hal yang dijaga:
 *
 *  1. TAK ADA PERAN YANG TERLEWAT pada tiap perpindahan status. Celah
 *     pemberitahuan tak pernah melempar galat — ia cuma membuat seseorang
 *     menunggu kabar yang tak akan pernah datang, dan itu mustahil ketahuan
 *     tanpa test seperti ini.
 *  2. KLASIFIKASI email. Kalau semua peristiwa ikut ke email, orang berhenti
 *     membaca email SmartPro dan yang genting justru ikut terlewat. Jadi
 *     "hanya yang penting yang menambah kanal mail" adalah aturan yang harus
 *     dikunci, bukan kebiasaan.
 */
class NotificationCoverageTest extends TestCase
{
    use DatabaseTransactions;

    private function draft(string $judul = 'SOP Notifikasi'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    private function berEmail(): User
    {
        $u = $this->aktorGl();
        $u->forceFill(['email' => 'gl@ppa-adro.local'])->save();

        return $u->fresh();
    }

    // ── Klasifikasi kanal ────────────────────────────────────────────────

    public function test_penting_menambah_kanal_mail(): void
    {
        $n = new DocumentNotification($this->draft(), 'Perlu ditinjau.', 'bi-clipboard-check', 'review.index', penting: true);

        $this->assertSame(['database', 'mail'], $n->via($this->berEmail()));
    }

    public function test_tidak_penting_hanya_lonceng(): void
    {
        $n = new DocumentNotification($this->draft(), 'Masukan Anda dibalas.', 'bi-reply', 'documents.published');

        $this->assertSame(['database'], $n->via($this->berEmail()));
    }

    public function test_pengguna_tanpa_email_tak_pernah_masuk_antrean_mail(): void
    {
        // Banyak akun lapangan belum mengisi email; tanpa penyaringan ini tiap
        // peristiwa penting akan melempar galat di antrean.
        $u = $this->aktorGl();
        $u->forceFill(['email' => null])->save();

        $n = new DocumentNotification($this->draft(), 'Perlu ditinjau.', 'bi-clipboard-check', 'review.index', penting: true);

        $this->assertSame(['database'], $n->via($u->fresh()));
    }

    public function test_lonceng_sinkron_email_lewat_antrean(): void
    {
        // Inilah yang menjaga aksi tinjau/setujui tetap cepat sekaligus membuat
        // lonceng muncul walau queue:work tak berjalan.
        $koneksi = (new DocumentNotification($this->draft(), 'x'))->viaConnections();

        $this->assertSame('sync', $koneksi['database']);
        $this->assertSame('database', $koneksi['mail']);
    }

    public function test_subjek_email_memuat_nomor_dokumen(): void
    {
        $d = $this->draft();
        $n = new DocumentNotification($d, 'Perlu ditinjau.', 'bi-clipboard-check', 'review.index', penting: true);

        $subjek = $n->toMail($this->berEmail())->subject;

        $this->assertStringContainsString('[SmartPro]', $subjek);
        $this->assertStringContainsString('Perlu Ditinjau', $subjek);
        $this->assertStringContainsString($d->doc_number, $subjek);
    }

    public function test_badan_email_memuat_kalimat_yang_sama_dengan_lonceng(): void
    {
        // Satu sumber kalimat: kalau keduanya boleh berbeda, pengguna yang
        // membaca lonceng DAN email akan mengira ada dua kejadian.
        $d = $this->draft();
        $pesan = 'Dokumen '.$d->doc_number.' perlu ditinjau.';
        $n = new DocumentNotification($d, $pesan, 'bi-clipboard-check', 'review.index', penting: true, catatan: 'Bab III kurang rujukan.');

        $html = $n->toMail($this->berEmail())->render();

        $this->assertStringContainsString($pesan, $html);
        $this->assertStringContainsString('Bab III kurang rujukan.', $html);
        $this->assertStringContainsString($d->title, $html);
        $this->assertStringContainsString('SmartPro', $html);
    }

    public function test_tombol_email_tak_pernah_menempelkan_id_sebagai_query_string(): void
    {
        // `route('review.index', $document)` TIDAK melempar galat — Laravel
        // diam-diam menempelkan id sebagai query (`/review?9238`), URL yang
        // tampak benar tapi tak menuju ke mana-mana. Terukur di Mailpit.
        $d = $this->draft();

        $daftar = (new DocumentNotification($d, 'x', 'bi-clipboard-check', 'review.index', penting: true))
            ->toMail($this->berEmail())->render();
        $this->assertStringContainsString(route('review.index'), $daftar);
        $this->assertStringNotContainsString('review?'.$d->id, $daftar);

        // Rute yang MEMANG menerima dokumen tetap membawanya.
        $rinci = (new DocumentNotification($d, 'x', 'bi-check-circle', 'documents.show', penting: true))
            ->toMail($this->berEmail())->render();
        $this->assertStringContainsString(route('documents.show', $d), $rinci);
    }

    // ── Cakupan peran per perpindahan status ─────────────────────────────

    /** Penyusun = pembuat utama + pembuat tambahan. */
    public function test_penyusun_mencakup_pembuat_tambahan(): void
    {
        $d = $this->draft();
        $lain = User::where('department_id', $d->department_id)
            ->where('id', '!=', $d->created_by)->firstOrFail();
        $d->authors()->create(['user_id' => $lain->id, 'is_primary' => false]);

        $ids = $d->fresh()->penyusun()->pluck('id')->all();

        $this->assertContains($d->created_by, $ids);
        $this->assertContains($lain->id, $ids);
    }

    public function test_pembuat_diberi_tahu_saat_dokumennya_LOLOS_tinjauan(): void
    {
        // Celah lama: pembuat hanya mendengar kabar buruk. Satu-satunya cara
        // tahu dokumennya lolos adalah membuka daftar berulang kali.
        Notification::fake();

        $d = $this->draft();
        $sh = $this->aktorSh();
        $d->forceFill(['status' => 'in_review', 'reviewer_id' => $sh->id])->save();

        $this->actingAs($sh)->post(route('review.store', $d), ['decision' => 'approve'])->assertRedirect();

        Notification::assertSentTo(
            $d->creator,
            fn (DocumentNotification $n) => str_contains($n->message, 'lolos tinjauan')
        );
    }

    public function test_terbit_memberi_tahu_penyusun_dan_non_staff_sedepartemen(): void
    {
        Notification::fake();

        $d = $this->draft();
        $pjo = User::where('jabatan', User::JABATAN_PIMPINAN)->firstOrFail();
        $d->forceFill(['status' => 'pending_approval', 'approver_id' => $pjo->id])->save();

        $this->actingAs($pjo)->post(route('approvals.store', $d), ['decision' => 'approve'])->assertRedirect();

        // Pembuat: kabar penting (dokumennya sah).
        Notification::assertSentTo(
            $d->creator,
            fn (DocumentNotification $n) => str_contains($n->message, 'kini Berlaku') && $n->penting
        );

        // Non-Staff sedepartemen: merekalah yang harus MENJALANKAN prosedurnya.
        // Bel saja — satu dokumen terbit bisa berarti puluhan email sekaligus.
        $lapangan = User::where('status', 'active')
            ->where('department_id', $d->department_id)
            ->where('jabatan', User::JABATAN_STAFF)->first();

        if ($lapangan) {
            Notification::assertSentTo(
                $lapangan,
                fn (DocumentNotification $n) => str_contains($n->message, 'Dokumen baru Berlaku') && ! $n->penting
            );
        }
    }

    public function test_penolakan_approver_memberi_tahu_penyusun_dan_peninjau(): void
    {
        Notification::fake();

        $d = $this->draft();
        $sh = $this->aktorSh();
        $pjo = User::where('jabatan', User::JABATAN_PIMPINAN)->firstOrFail();
        $d->forceFill(['status' => 'pending_approval', 'approver_id' => $pjo->id, 'reviewer_id' => $sh->id])->save();

        $this->actingAs($pjo)
            ->post(route('approvals.store', $d), ['decision' => 'reject', 'summary' => 'Belum sesuai format.'])
            ->assertRedirect();

        Notification::assertSentTo($d->creator, fn (DocumentNotification $n) => $n->penting && str_contains($n->message, 'ditolak approver'));
        Notification::assertSentTo($sh, fn (DocumentNotification $n) => $n->penting && str_contains($n->message, 'Anda loloskan'));
    }
}
