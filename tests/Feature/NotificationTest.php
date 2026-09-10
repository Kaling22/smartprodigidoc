<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_bell_notifications_fire_through_workflow(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pimpinan = $this->aktorPjo();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        // Clean slate (rolled back by DatabaseTransactions) so counts are deterministic.
        foreach ([$gl, $sh, $pimpinan] as $u) {
            $u->notifications()->delete();
        }

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Notif');
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pimpinan->id, 'current_step' => 2]);

        // Submit -> reviewer notified.
        $this->actingAs($gl)->post(route('documents.submit', $doc));
        $this->assertSame(1, $sh->fresh()->unreadNotifications()->count());

        // Peninjau membuka (waiting_for_review -> in_review) lalu meloloskan.
        // SOP mampir ke MD dulu, jadi yang dinotifikasi MD — BUKAN PJO.
        //
        // Dihitung sebagai SELISIH, bukan jumlah mutlak: akun MD bersifat
        // bersama dan basis data uji ini dipakai bersama, jadi ia bisa saja
        // sudah memegang notifikasi lain. Menuntut angka mutlak membuat test
        // gagal karena sisa data, bukan karena kodenya salah.
        $md = User::peninjauMd()->firstOrFail();
        $mdSemula = $md->unreadNotifications()->count();

        $this->actingAs($sh)->get(route('review.show', $doc));
        $this->actingAs($sh)->post(route('review.store', $doc), ['decision' => 'approve']);
        $this->assertSame($mdSemula + 1, $md->fresh()->unreadNotifications()->count(), 'MD diberi tahu');
        $this->assertSame(0, $pimpinan->fresh()->unreadNotifications()->count(), 'PJO belum boleh diberi tahu');

        // v5 Fase D: pembuat ikut diberi tahu bahwa dokumennya LOLOS. Dulu ia
        // hanya mendengar kabar buruk, sehingga satu-satunya cara tahu kabar
        // baik adalah membuka daftar berulang kali.
        $this->assertSame(1, $gl->fresh()->unreadNotifications()->count(), 'pembuat tahu dokumennya lolos tinjauan');

        // MD meloloskan -> barulah PJO dinotifikasi; pembuat juga.
        $this->actingAs($md)->post(route('review.md.store', $doc), ['decision' => 'approve']);
        $this->assertSame(1, $pimpinan->fresh()->unreadNotifications()->count());
        $this->assertSame(2, $gl->fresh()->unreadNotifications()->count(), 'pembuat tahu tahap MD terlewati');

        // Final approve -> creator notified (Berlaku).
        $this->actingAs($pimpinan)->post(route('approvals.store', $doc), ['decision' => 'approve']);
        $this->assertSame(3, $gl->fresh()->unreadNotifications()->count(), 'lolos tinjauan + lolos MD + Berlaku');

        // Bell "mark all read".
        $this->actingAs($gl)->post(route('notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $gl->fresh()->unreadNotifications()->count());
    }
}
