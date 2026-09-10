<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman Notifikasi penuh (F7 DASBOR-V4) — lahir V2-only (D4), tanpa
 * kembaran V1.
 */
class NotifikasiInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumen(User $gl): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'Uji Halaman Notifikasi');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    public function test_halaman_merender_dengan_paginator_utuh(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumen($gl);
        $gl->notify(new DocumentNotification($doc, 'Perlu ditinjau.', 'bi-clipboard-check', 'review.index'));

        $this->actingAs($gl)->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Notifications/Index')
                ->has('notifikasi.data')
                ->has('notifikasi.total')
                ->has('notifikasi.links')
                ->has('kategoriOpsi')
                ->has('belumDibaca')
            );
    }

    /**
     * Saringan `status`/`q` TETAP di server (bukan penyaringan klien atas 20
     * baris yang kebetulan tampil), dan mengubah satu tak menghapus yang lain
     * dari props `filters`. Dicari lewat teks UNIK, bukan angka total mutlak —
     * basis data pengembangan sudah memuat lonceng lama akun yang sama.
     */
    public function test_saring_status_dan_q_di_server_dan_saling_bertahan(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumen($gl);
        $unik = 'UjiSaringNotif-'.uniqid();

        $gl->notify(new DocumentNotification($doc, "Perlu ditinjau {$unik}.", 'bi-clipboard-check', 'review.index'));
        $lolos = $gl->fresh()->notifications()->where('data->message', "Perlu ditinjau {$unik}.")->firstOrFail();

        $this->actingAs($gl)
            ->get(route('notifications.index', ['status' => 'belum', 'q' => $unik]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Notifications/Index')
                ->where('filters.status', 'belum')
                ->where('filters.q', $unik)
                ->where('notifikasi.total', 1)
            );

        $lolos->markAsRead();

        // Sudah dibaca -> tak lolos saringan status=belum walau teksnya tetap cocok.
        $this->actingAs($gl)
            ->get(route('notifications.index', ['status' => 'belum', 'q' => $unik]))
            ->assertInertia(fn (Assert $page) => $page->where('notifikasi.total', 0));
    }

    /**
     * Kebocoran (§P4) pada HTML MENTAH: notifikasi milik ORANG LAIN tak
     * pernah ikut ke daftar seseorang, dan atribut `data-page` tak membawa
     * kolom akun siapa pun.
     */
    public function test_notifikasi_orang_lain_tak_ikut_dan_nol_kebocoran_kolom_sensitif(): void
    {
        $gl = $this->aktorGl();
        $lain = $this->aktorSh();
        $doc = $this->dokumen($gl);

        $rahasiaLain = 'RahasiaOrangLain-'.uniqid();
        $lain->notify(new DocumentNotification($doc, $rahasiaLain, 'bi-clipboard-check', 'review.index'));

        $html = (string) $this->actingAs($gl)->get(route('notifications.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString($rahasiaLain, $html,
            'Halaman Notifikasi GL memuat notifikasi milik SH.');

        foreach (['password', 'remember_token', 'email'] as $rahasia) {
            $this->assertStringNotContainsString('&quot;'.$rahasia.'&quot;', $html,
                "Halaman Notifikasi membocorkan kolom `{$rahasia}` ke atribut data-page.");
            $this->assertStringNotContainsString('"'.$rahasia.'"', $html,
                "Halaman Notifikasi membocorkan kolom `{$rahasia}` ke badan respons.");
        }
    }
}
