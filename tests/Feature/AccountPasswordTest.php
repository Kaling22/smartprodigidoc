<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ubah kata sandi sendiri (`PageController::updatePassword`) — beda dari
 * reset Admin (`UserResetPasswordTest`), yang justru dipakai saat sandi
 * lama SUDAH lupa dan tak bisa dibuktikan.
 */
class AccountPasswordTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_dapat_mengganti_sandi_sendiri(): void
    {
        $user = $this->aktorNonStaff();
        $user->update(['password' => Hash::make('sandilama123')]);

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'sandilama123',
                'password' => 'sandibaru123',
                'password_confirmation' => 'sandibaru123',
            ])
            ->assertRedirect(route('account.info'));

        $this->assertTrue(Hash::check('sandibaru123', $user->refresh()->password));
    }

    public function test_menolak_bila_sandi_lama_salah(): void
    {
        $user = $this->aktorNonStaff();
        $user->update(['password' => Hash::make('sandilama123')]);

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'salah-total',
                'password' => 'sandibaru123',
                'password_confirmation' => 'sandibaru123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('sandilama123', $user->refresh()->password));
    }

    public function test_menolak_sandi_baru_yang_terlalu_pendek_atau_tak_cocok(): void
    {
        $user = $this->aktorNonStaff();
        $user->update(['password' => Hash::make('sandilama123')]);

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'sandilama123',
                'password' => 'abc',
                'password_confirmation' => 'abc',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'sandilama123',
                'password' => 'sandibaru123',
                'password_confirmation' => 'tidak-cocok',
            ])
            ->assertSessionHasErrors('password');
    }

    /** Kebijakannya cuma PANJANG minimal — angka saja atau huruf saja tetap sah. */
    public function test_menerima_sandi_4_karakter_angka_saja_atau_huruf_saja(): void
    {
        $user = $this->aktorNonStaff();
        $user->update(['password' => Hash::make('sandilama123')]);

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'sandilama123',
                'password' => '1234',
                'password_confirmation' => '1234',
            ])
            ->assertRedirect(route('account.info'));

        $this->assertTrue(Hash::check('1234', $user->refresh()->password));
    }
}
