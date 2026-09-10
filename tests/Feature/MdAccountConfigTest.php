<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Konfigurasi akun Management Development oleh Admin.
 *
 * Akun MD bersifat BERSAMA dan memegang satu tahap WAJIB pada alur SOP, jadi
 * salah kelola di sini berakibat langsung: dokumen berhenti mengalir. Yang
 * dikunci: hanya Admin yang boleh mengelolanya, dan saklarnya benar-benar
 * berpengaruh.
 */
class MdAccountConfigTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    private function md(): User
    {
        return User::peninjauMd()->firstOrFail();
    }

    public function test_admin_melihat_bagian_akun_md(): void
    {
        /*
        | Dulu `assertSee('Akun Management Development')` — judul kartunya.
        | Sejak Fase 6 judul itu tinggal di TSX, sementara yang datang dari
        | server adalah props `akunMd`. Justru props itulah yang perlu dijaga:
        | judul kartu bisa ada sementara daftarnya kosong, dan yang membuat
        | halaman ini berguna adalah daftarnya.
        */
        $props = $this->propsInertia(
            $this->actingAs($this->admin())->get(route('users.index'))->assertOk()
        );

        $this->assertContains($this->md()->nrp, collect($props['akunMd'])->pluck('nrp')->all());
    }

    public function test_saklar_ai_berpindah_dan_tersimpan(): void
    {
        $md = $this->md();
        $semula = (bool) $md->ai_review_enabled;

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $md), ['aksi' => 'ai'])
            ->assertRedirect();

        $this->assertSame(! $semula, (bool) $md->refresh()->ai_review_enabled);
    }

    public function test_nonaktifkan_lalu_aktifkan_akun_md(): void
    {
        $md = $this->md();

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $md), ['aksi' => 'status'])
            ->assertRedirect();
        $this->assertFalse($md->refresh()->isActive());

        // Saat nonaktif, akun itu TAK BOLEH lagi terpilih sebagai sasaran
        // notifikasi — kalau tidak, SOP dikirim ke akun yang tak bisa masuk.
        $this->assertEmpty(User::peninjauMd()->get());

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $md), ['aksi' => 'status'])
            ->assertRedirect();
        $this->assertTrue($md->refresh()->isActive());
    }

    public function test_reset_sandi_mengubah_sandi_dan_menolak_yang_terlalu_pendek(): void
    {
        $md = $this->md();

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $md), ['aksi' => 'reset_sandi', 'sandi_baru' => 'pendek'])
            ->assertSessionHasErrors('sandi_baru');

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $md), ['aksi' => 'reset_sandi', 'sandi_baru' => 'sandibaru123'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('sandibaru123', $md->refresh()->password));
    }

    public function test_selain_admin_tidak_boleh_mengonfigurasi(): void
    {
        $md = $this->md();

        // Empat peran yang BUKAN Admin — itulah batas yang diuji, bukan orangnya.
        foreach ([$this->aktorSh(), $this->aktorGl(), $this->aktorPjo(), $this->aktorNonStaff()] as $bukanAdmin) {
            $this->actingAs($bukanAdmin)
                ->post(route('users.mdConfig', $md), ['aksi' => 'ai'])
                ->assertForbidden();
        }
    }

    public function test_konfigurasi_md_menolak_akun_yang_bukan_md(): void
    {
        $bukanMd = $this->aktorGl();

        $this->actingAs($this->admin())
            ->post(route('users.mdConfig', $bukanMd), ['aksi' => 'ai'])
            ->assertForbidden();
    }
}
