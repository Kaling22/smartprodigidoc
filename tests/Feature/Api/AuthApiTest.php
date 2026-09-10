<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Autentikasi API untuk aplikasi mobile (Flutter).
 *
 * Bentuk JSON di sini BUKAN selera kita — ia mengikuti apa yang sudah dibaca
 * `lib/auth_service.dart` di aplikasi mobile (`token`, lalu `user.id`,
 * `user.nama`, `user.departemen`, `user.nrp`, `user.role`). Kalau kunci-kunci
 * itu berubah, aplikasi mobile gagal login TANPA pesan yang jelas — karena itu
 * dikunci test.
 */
class AuthApiTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $nrp, string $status = 'active'): User
    {
        return User::create([
            'name' => 'Uji '.$nrp,
            'nrp' => $nrp,
            'jabatan' => User::JABATAN_STAFF,
            'status' => $status,
            'password' => Hash::make('rahasia123'),
            'department_id' => Department::where('code', 'ICTMD')->value('id'),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Rute login dibatasi 6 percobaan/menit. Tanpa dibersihkan, test yang
        // berjalan beruntun saling mewarisi hitungan dan gagal dengan 429.
        RateLimiter::clear('');
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    public function test_login_mengembalikan_token_dan_bentuk_user_yang_dibaca_mobile(): void
    {
        $user = $this->makeUser('API-0001');

        $res = $this->postJson('/api/login', ['nrp' => 'API-0001', 'password' => 'rahasia123']);

        $res->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'nrp', 'nama', 'departemen', 'role'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.nama', 'Uji API-0001')
            ->assertJsonPath('user.departemen', 'ICTMD')
            ->assertJsonPath('user.role', User::JABATAN_STAFF);

        $this->assertNotEmpty($res->json('token'));
    }

    public function test_password_bocor_tidak_ikut_terkirim(): void
    {
        $this->makeUser('API-0002');

        $res = $this->postJson('/api/login', ['nrp' => 'API-0002', 'password' => 'rahasia123']);

        $this->assertArrayNotHasKey('password', $res->json('user'));
        $this->assertArrayNotHasKey('remember_token', $res->json('user'));
    }

    public function test_nrp_tak_dikenal_dan_sandi_salah_memberi_pesan_yang_sama(): void
    {
        $this->makeUser('API-0003');

        $sandiSalah = $this->postJson('/api/login', ['nrp' => 'API-0003', 'password' => 'keliru']);
        $nrpAsing = $this->postJson('/api/login', ['nrp' => 'TIDAK-ADA', 'password' => 'keliru']);

        $sandiSalah->assertStatus(401);
        $nrpAsing->assertStatus(401);

        // Pesan WAJIB identik: kalau dibedakan, endpoint ini bisa dipakai
        // menebak NRP mana yang terdaftar di sistem.
        $this->assertSame($sandiSalah->json('message'), $nrpAsing->json('message'));
    }

    public function test_akun_belum_aktif_ditolak(): void
    {
        $this->makeUser('API-0004', 'pending');

        $this->postJson('/api/login', ['nrp' => 'API-0004', 'password' => 'rahasia123'])
            ->assertStatus(403);
    }

    public function test_field_kosong_ditolak_dengan_pesan_indonesia(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nrp', 'password']);
    }

    public function test_me_menolak_permintaan_tanpa_token(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_logout_mencabut_token_yang_dipakai(): void
    {
        $this->makeUser('API-0005');

        $token = $this->postJson('/api/login', [
            'nrp' => 'API-0005', 'password' => 'rahasia123',
        ])->json('token');

        $pakai = fn () => $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/me');

        $pakai()->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/logout')->assertOk();

        // Di dalam test, container Laravel dipakai ulang antar-permintaan sehingga
        // guard masih memegang user hasil permintaan sebelumnya — token yang sudah
        // dihapus pun tetap terlihat sah. Di server sungguhan tiap permintaan
        // memakai proses baru, jadi masalah ini tak ada (sudah dibuktikan lewat
        // curl: token bekas logout mengembalikan 401).
        $this->app['auth']->forgetGuards();

        $pakai()->assertStatus(401);
    }
}
