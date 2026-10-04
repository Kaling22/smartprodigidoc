<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Reset sandi akun staf oleh Admin (`UserManagementController::resetPassword`)
 * — jalan keluar saat pemiliknya lupa kata sandi, sehingga TANPA syarat sandi
 * lama. Justru karena itu hanya Admin (`can:user.manage`) yang boleh memicunya.
 */
class UserResetPasswordTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_dapat_reset_sandi_akun_staf(): void
    {
        $staf = $this->aktorNonStaff();

        $this->actingAs($this->aktorAdmin())
            ->post(route('users.resetPassword', $staf), [
                'password' => 'sandibaru123',
                'password_confirmation' => 'sandibaru123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('sandibaru123', $staf->refresh()->password));
    }

    public function test_menolak_sandi_yang_terlalu_pendek_atau_tak_cocok(): void
    {
        $staf = $this->aktorNonStaff();

        $this->actingAs($this->aktorAdmin())
            ->post(route('users.resetPassword', $staf), [
                'password' => 'abc',
                'password_confirmation' => 'abc',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($this->aktorAdmin())
            ->post(route('users.resetPassword', $staf), [
                'password' => 'sandibaru123',
                'password_confirmation' => 'tidak-cocok',
            ])
            ->assertSessionHasErrors('password');
    }

    /** Kebijakannya cuma PANJANG minimal — angka saja atau huruf saja tetap sah. */
    public function test_menerima_sandi_4_karakter_angka_saja_atau_huruf_saja(): void
    {
        $staf = $this->aktorNonStaff();

        $this->actingAs($this->aktorAdmin())
            ->post(route('users.resetPassword', $staf), [
                'password' => 'abcd',
                'password_confirmation' => 'abcd',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('abcd', $staf->refresh()->password));
    }

    public function test_selain_admin_tidak_boleh_reset_sandi(): void
    {
        $staf = $this->aktorNonStaff();

        foreach ([$this->aktorSh(), $this->aktorGl(), $this->aktorPjo()] as $bukanAdmin) {
            $this->actingAs($bukanAdmin)
                ->post(route('users.resetPassword', $staf), [
                    'password' => 'sandibaru123',
                    'password_confirmation' => 'sandibaru123',
                ])
                ->assertForbidden();
        }
    }
}
