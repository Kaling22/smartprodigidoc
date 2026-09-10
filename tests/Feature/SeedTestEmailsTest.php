<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Perintah pengisi alamat email uji.
 *
 * Yang paling penting dijaga di sini adalah apa yang perintah ini TIDAK
 * lakukan: menimpa alamat yang sudah ada. Sebagian akun memakai alamat yang
 * bisa jadi sungguhan, dan menimpanya diam-diam berarti menghapus data nyata
 * tanpa satu pun pesan salah.
 */
class SeedTestEmailsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mengisi_hanya_yang_kosong(): void
    {
        $kosong = User::firstOrFail();
        $kosong->forceFill(['email' => null])->save();

        $terisi = User::where('id', '!=', $kosong->id)->firstOrFail();
        $terisi->forceFill(['email' => 'asli@gmail.com'])->save();

        $this->artisan('smartpro:email-uji --force')->assertExitCode(0);

        $this->assertSame(strtolower($kosong->nrp).'@ppa-adro.local', $kosong->fresh()->email);
        $this->assertSame('asli@gmail.com', $terisi->fresh()->email, 'alamat yang sudah ada tak boleh disentuh');
    }

    public function test_timpa_mengganti_alamat_yang_sudah_ada(): void
    {
        $u = User::firstOrFail();
        $u->forceFill(['email' => 'lama@gmail.com'])->save();

        $this->artisan('smartpro:email-uji --timpa --force')->assertExitCode(0);

        $this->assertSame(strtolower($u->nrp).'@ppa-adro.local', $u->fresh()->email);
    }

    public function test_domain_bisa_diganti(): void
    {
        $u = User::firstOrFail();
        $u->forceFill(['email' => null])->save();

        $this->artisan('smartpro:email-uji --domain=uji.test --force')->assertExitCode(0);

        $this->assertSame(strtolower($u->nrp).'@uji.test', $u->fresh()->email);
    }

    public function test_domain_kosong_ditolak(): void
    {
        $this->artisan('smartpro:email-uji --domain= --force')->assertExitCode(1);
    }

    public function test_sesudah_dijalankan_notifikasi_penting_memakai_kanal_mail(): void
    {
        // Inilah alasan perintah ini ada: tanpa alamat, `via()` mengembalikan
        // ['database'] saja dan seluruh alur email TAMPAK mati padahal benar.
        $sh = $this->aktorSh();
        $sh->forceFill(['email' => null])->save();

        $gl = $this->aktorGl();
        $d = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Email Uji'
        );
        $n = new DocumentNotification($d, 'Perlu ditinjau.', 'bi-clipboard-check', 'review.index', penting: true);

        $this->assertSame(['database'], $n->via($sh->fresh()), 'sebelum: lonceng saja');

        $this->artisan('smartpro:email-uji --force')->assertExitCode(0);

        $this->assertSame(['database', 'mail'], $n->via($sh->fresh()), 'sesudah: lonceng + email');
    }

    public function test_tanpa_force_bisa_dibatalkan(): void
    {
        $u = User::firstOrFail();
        $u->forceFill(['email' => null])->save();

        $this->artisan('smartpro:email-uji')
            ->expectsConfirmation('Isikan alamat @ppa-adro.local kepada '.User::whereNull('email')->count().' pengguna? Lanjutkan?', 'no')
            ->assertExitCode(1);

        $this->assertNull($u->fresh()->email, 'dibatalkan = tak ada yang berubah');
    }
}
