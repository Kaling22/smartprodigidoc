<?php

namespace Tests\Feature\Api;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Butir 15 §3 — lonceng terbaca dari HP.
 *
 * Sampai butir ini, DocumentNotification menulis ke channel `database` dan
 * docblock-nya menjanjikan notifikasi "muncul di lonceng, di web maupun di
 * aplikasi mobile" — padahal tak ada satu pun endpoint yang membacanya.
 *
 * Penghuninya dicari lewat JABATAN, bukan NRP contoh: basis data pengembangan
 * sudah berisi akun sungguhan dan `GL-0001` tak lagi ada di sana. Test yang
 * mematok NRP jadi merah karena datanya, bukan karena kodenya.
 */
class NotifikasiApiTest extends TestCase
{
    use DatabaseTransactions;

    private function gl(): User
    {
        return User::where('jabatan', User::JABATAN_GROUP_LEADER)
            ->where('status', 'active')->whereNotNull('department_id')->firstOrFail();
    }

    private function dokumen(User $gl): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji Lonceng Mobile');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    public function test_lonceng_terbaca_dan_bisa_ditandai_dibaca_dari_api(): void
    {
        $gl = $this->gl();
        $dok = $this->dokumen($gl);

        $gl->notify(new DocumentNotification($dok, 'Dokumen Anda ditolak.', 'bi-x-circle'));

        // Basis data pengembangan bisa saja sudah memuat lonceng lama milik
        // orang yang sama — jadi yang diperiksa SELISIHNYA, bukan angka mutlak.
        $awal = $gl->fresh()->unreadNotifications()->count();
        $this->assertGreaterThanOrEqual(1, $awal);

        Sanctum::actingAs($gl);

        $this->getJson('/api/notifications/unread-count')->assertOk()
            ->assertJsonPath('belum_dibaca', $awal);

        $res = $this->getJson('/api/notifications?belum_dibaca=1')->assertOk()
            ->assertJsonPath('meta.belum_dibaca', $awal)
            ->assertJsonPath('data.0.dibaca', false)
            ->assertJsonPath('data.0.pesan', 'Dokumen Anda ditolak.')
            ->assertJsonPath('data.0.no_dokumen', $dok->doc_number)
            ->assertJsonPath('data.0.document_id', $dok->id);

        $id = $res->json('data.0.id');

        $this->postJson("/api/notifications/{$id}/read")->assertOk()
            ->assertJsonPath('data.dibaca', true)
            ->assertJsonPath('meta.belum_dibaca', $awal - 1);

        // Sisi WEB ikut berkurang — satu tabel `notifications`, bukan dua.
        $this->assertSame($awal - 1, $gl->fresh()->unreadNotifications()->count());

        $this->postJson('/api/notifications/read-all')->assertOk()
            ->assertJsonPath('belum_dibaca', 0);
        $this->assertSame(0, $gl->fresh()->unreadNotifications()->count());
    }

    /** Lonceng orang lain mustahil dibuka, meski id-nya ditebak benar. */
    public function test_lonceng_milik_orang_lain_tak_bisa_ditandai_dibaca(): void
    {
        $gl = $this->gl();
        $gl->notify(new DocumentNotification($this->dokumen($gl), 'Rahasia.', 'bi-bell'));
        $id = $gl->fresh()->unreadNotifications()->firstOrFail()->id;
        $sebelum = $gl->fresh()->unreadNotifications()->count();

        Sanctum::actingAs(User::where('jabatan', User::JABATAN_SECTION_HEAD)
            ->where('status', 'active')->firstOrFail());

        $this->postJson("/api/notifications/{$id}/read")->assertNotFound();
        $this->assertSame($sebelum, $gl->fresh()->unreadNotifications()->count());
    }

    public function test_lonceng_tertutup_tanpa_token(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
    }

    /**
     * Butir 15 §5 — bentuk UserResource DIKUNCI. Inilah satu-satunya tempat
     * privilege diumumkan ke aplikasi: kalau satu bendera hilang, menunya
     * lenyap di HP tanpa satu pun galat, dan tak ada test lain yang melihatnya.
     */
    public function test_me_mengirim_seluruh_bendera_kapabilitas(): void
    {
        Sanctum::actingAs($this->gl());

        $user = $this->getJson('/api/me')->assertOk()->json('user');

        foreach ([
            'id', 'nrp', 'nama', 'departemen', 'departemen_nama', 'role', 'role_label',
            'foto', 'no_hp', 'email',
            'bisa_tinjau', 'bisa_tinjau_jsa', 'bisa_beri_masukan', 'bisa_kerja_jsa',
            'bisa_ketersediaan', 'bisa_perjalanan', 'bisa_status_dokumen',
            'bisa_distribusi', 'bisa_daftar_induk', 'bisa_lihat_arsip',
            'lintas_departemen',
        ] as $kunci) {
            $this->assertArrayHasKey($kunci, $user, "UserResource kehilangan `{$kunci}`.");
        }

        // Kunci mentah dikirim APA ADANYA, bukan labelnya (CLAUDE.md §6).
        $this->assertSame(User::JABATAN_GROUP_LEADER, $user['role']);
    }
}
