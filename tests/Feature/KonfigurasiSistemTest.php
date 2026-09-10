<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\NullReviewer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Konfigurasi Sistem — AI & Kesehatan (PLAN-AKSES-v8 Fase 5c).
 *
 * Yang dikunci di sini bukan tampilannya, melainkan LIMA janji yang kalau
 * rusak tidak menimbulkan galat apa pun — hanya perilaku yang salah diam-diam:
 *
 *   1. Setelan yang BELUM pernah disentuh dari layar tetap ikut .env, persis
 *      seperti sebelum fase ini ada (aturan yang sama dengan §9.2 aturan 1).
 *   2. Saklar di layar BENAR-BENAR mematikan AI. Ini jebakan yang sudah kena
 *      sekali di `DocumentType::kode()` (§9.3 butir 1): selama controller
 *      tinjau membaca `config('services.ai.enabled')` sendiri, saklarnya
 *      menyala tanpa mematikan apa pun.
 *   3. KUNCI API KOSONG = PERTAHANKAN, bukan hapus. Layarnya menampilkan kunci
 *      bertopeng, jadi tanpa aturan ini setiap penyimpanan biasa mencabut
 *      kunci AI diam-diam. Menghapus butuh kotak centang tersendiri.
 *   4. Kunci tersimpan TERSANDI, dan audit log tak pernah memuat isinya.
 *   5. Email uji hanya ke alamat Admin sendiri; layarnya milik Admin saja.
 *
 * SELURUH aktor lahir di dalam transaksi (§3 jalan B).
 */
class KonfigurasiSistemTest extends TestCase
{
    use DatabaseTransactions;

    /** Kunci setelan yang disentuh test ini — dibuang dari cache di antara test. */
    private const KUNCI = [
        'ai.enabled', 'ai.provider', 'ai.model', 'ai.key',
        'ai.cadangan.provider', 'ai.cadangan.model', 'ai.cadangan.key',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->lupakanCache();
    }

    protected function tearDown(): void
    {
        $this->lupakanCache();
        parent::tearDown();
    }

    private function lupakanCache(): void
    {
        foreach (self::KUNCI as $k) {
            Cache::forget('pengaturan:'.$k);
        }

        // Ingatan seumur-request adalah larik STATIS: ia tak ikut di-rollback
        // bersama transaksi, jadi harus dibuang terpisah.
        Pengaturan::lupakanIngatan();
    }

    private function orang(string $role): User
    {
        $tanda = Str::upper(Str::random(6));
        $dept = Department::create(['code' => 'UJN'.$tanda, 'name' => 'Departemen Uji '.$tanda]);

        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PNM-'.$tanda,
            'email' => 'uji'.Str::lower($tanda).'@contoh.test',
            'jabatan' => $role === 'admin_it' ? User::JABATAN_STAFF : $role,
            'department_id' => $dept->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    /** Isi formulir yang sah, boleh ditimpa sebagian. */
    private function isian(array $ganti = []): array
    {
        return array_merge([
            'enabled' => '1',
            'provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
        ], $ganti);
    }

    /** Janji 1 — belum pernah disetel dari layar berarti ikut .env, bukan ikut "mati". */
    public function test_tanpa_setelan_mengikuti_env(): void
    {
        $ai = Pengaturan::ai();

        $this->assertSame((bool) config('services.ai.enabled'), $ai['enabled']);
        $this->assertSame(config('services.ai.provider'), $ai['provider']);
        $this->assertSame(config('services.'.config('services.ai.provider').'.key'), $ai['key']);
    }

    /**
     * Janji 2 — saklar di layar benar-benar mematikan AI.
     *
     * Yang diperiksa BUKAN nilai setelannya melainkan hasil resolve container:
     * di situlah letak jebakannya. Setelan boleh saja tersimpan rapi sementara
     * controller tinjau tetap memanggil penyedia sungguhan.
     */
    public function test_saklar_mati_membuat_reviewer_null(): void
    {
        // Lewat FORMULIRNYA, bukan Pengaturan::simpan() langsung: kotak centang
        // yang tak dicentang tidak ikut terkirim sama sekali, dan justru di situ
        // letak salah tulis yang gampang lolos — "tak terkirim" harus terbaca
        // MATI, bukan "tak berubah".
        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.sistem.ai'), ['provider' => 'gemini', 'model' => 'gemini-2.0-flash'])
            ->assertSessionHasNoErrors();

        $this->lupakanCache();

        $this->assertFalse(Pengaturan::ai()['enabled']);
        $this->assertInstanceOf(NullReviewer::class, app(AiReviewerInterface::class));
        $this->assertFalse(app(AiReviewerInterface::class)->isEnabled());
    }

    /** Menyala + kunci & model lengkap → penyedia sungguhan, bukan NullReviewer. */
    public function test_saklar_nyala_dengan_kunci_lengkap_memakai_penyedia(): void
    {
        Pengaturan::simpan('ai.enabled', '1');
        Pengaturan::simpan('ai.provider', 'gemini');
        Pengaturan::simpan('ai.model', 'gemini-2.0-flash');
        Pengaturan::simpanRahasia('ai.key', 'kunci-uji-1234');
        $this->lupakanCache();

        $reviewer = app(AiReviewerInterface::class);

        $this->assertNotInstanceOf(NullReviewer::class, $reviewer);
        $this->assertTrue($reviewer->isEnabled());
    }

    /**
     * "AI dimatikan" dan "kunci belum diisi" WAJIB berbunyi berbeda.
     *
     * Inilah kegagalan yang memicu rencana pra-produksi Fase 3: baris `ai.key`
     * hilang dari tabel `pengaturan`, penyedia utama diam-diam jadi
     * NullReviewer, dan pesannya identik dengan saklar yang memang dimatikan
     * Admin. Tak seorang pun bisa membedakan keduanya dari layar tinjau,
     * sehingga kunci yang hilang bertahan berhari-hari.
     */
    public function test_null_reviewer_membedakan_dimatikan_dari_setelan_kosong(): void
    {
        Pengaturan::simpan('ai.enabled', '0');
        $this->lupakanCache();

        $this->assertSame(NullReviewer::DIMATIKAN, app(AiReviewerInterface::class)->ping());

        // Menyala, tetapi kuncinya tak pernah diisi — persis keadaan produksi
        // yang ditemukan 2026-09-01.
        Pengaturan::simpan('ai.enabled', '1');
        Pengaturan::simpan('ai.provider', 'openrouter');
        Pengaturan::simpan('ai.model', 'model-uji');
        Pengaturan::simpanRahasia('ai.key', '');
        config(['services.openrouter.key' => null, 'services.ai.cadangan.key' => null]);
        $this->lupakanCache();

        $reviewer = app(AiReviewerInterface::class);

        $this->assertInstanceOf(NullReviewer::class, $reviewer);
        $this->assertSame(NullReviewer::TAK_LENGKAP, $reviewer->ping());
    }

    /** Janji 3 — kunci kosong mempertahankan yang lama; kotak centang yang menghapusnya. */
    public function test_kunci_kosong_mempertahankan_kunci_lama(): void
    {
        $admin = $this->orang('admin_it');

        $this->actingAs($admin)
            ->put(route('pengaturan.sistem.ai'), $this->isian(['key' => 'kunci-asli-abcd']))
            ->assertSessionHasNoErrors();

        $this->lupakanCache();
        $this->assertSame('kunci-asli-abcd', Pengaturan::ambilRahasia('ai.key'));

        // Menyimpan lagi TANPA mengisi kunci — persis yang dilakukan Admin yang
        // cuma mengganti model. Kuncinya tak boleh ikut lenyap.
        $this->actingAs($admin)
            ->put(route('pengaturan.sistem.ai'), $this->isian(['model' => 'gemini-2.5-pro']))
            ->assertSessionHasNoErrors();

        $this->lupakanCache();
        $this->assertSame('kunci-asli-abcd', Pengaturan::ambilRahasia('ai.key'));
        $this->assertSame('gemini-2.5-pro', Pengaturan::ai()['model']);

        // Menghapusnya menuntut niat tersendiri.
        $this->actingAs($admin)
            ->put(route('pengaturan.sistem.ai'), $this->isian(['hapus_key' => '1']))
            ->assertSessionHasNoErrors();

        $this->lupakanCache();
        $this->assertNull(Pengaturan::ambilRahasia('ai.key'));
    }

    /** Hapus MENANG atas isi baru — centang "hapus" tak boleh dikalahkan sisa teks di kotak. */
    public function test_hapus_menang_atas_kunci_baru(): void
    {
        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.sistem.ai'), $this->isian(['key' => 'kunci-baru-xyz', 'hapus_key' => '1']))
            ->assertSessionHasNoErrors();

        $this->lupakanCache();
        $this->assertNull(Pengaturan::ambilRahasia('ai.key'));
    }

    /** Janji 4 — kunci tersimpan TERSANDI, dan audit log tak memuat isinya. */
    public function test_kunci_tersandi_dan_tak_bocor_ke_audit(): void
    {
        $admin = $this->orang('admin_it');

        $this->actingAs($admin)
            ->put(route('pengaturan.sistem.ai'), $this->isian(['key' => 'rahasia-sekali-9999']))
            ->assertSessionHasNoErrors();

        // Yang tersimpan di kolom `nilai` BUKAN teks terbacanya.
        $mentah = (string) Pengaturan::find('ai.key')->nilai;
        $this->assertNotSame('rahasia-sekali-9999', $mentah);
        $this->assertStringNotContainsString('rahasia-sekali', $mentah);

        $log = AuditLog::where('action', 'pengaturan.ai_diubah')->where('user_id', $admin->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('diganti', $log->meta_json['kunci']);
        $this->assertStringNotContainsString('rahasia-sekali', json_encode($log->meta_json));
    }

    /** Layar menampilkan kunci BERTOPENG, tak pernah teks aslinya. */
    public function test_layar_menampilkan_kunci_bertopeng(): void
    {
        Pengaturan::simpanRahasia('ai.key', 'rahasia-sekali-9999');
        $this->lupakanCache();

        $this->actingAs($this->orang('admin_it'))
            ->get(route('pengaturan.sistem'))
            ->assertOk()
            ->assertDontSee('rahasia-sekali-9999')
            ->assertSee('9999');   // empat aksara terakhir saja
    }

    /** Penyedia di luar daftar tertutup ditolak — salah ketik tak boleh diam-diam mematikan AI. */
    public function test_penyedia_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.sistem.ai'), $this->isian(['provider' => 'chatgpt-pribadi']))
            ->assertSessionHasErrors('provider');
    }

    /** Penyedia cadangan tanpa model = cadangan yang tampak terpasang tapi tak pernah menangkap apa pun. */
    public function test_cadangan_tanpa_model_ditolak(): void
    {
        $this->actingAs($this->orang('admin_it'))
            ->put(route('pengaturan.sistem.ai'), $this->isian(['cadangan_provider' => 'openrouter']))
            ->assertSessionHasErrors('cadangan_model');
    }

    /**
     * Janji 5 — email uji hanya ke alamat Admin yang sedang login, tak pernah
     * dari input. Alamat lain sengaja DIKIRIMKAN dalam permintaan: yang diuji
     * justru bahwa ia diabaikan.
     *
     * Dipakai transport `array`, BUKAN `Mail::fake()`. Alasannya bukan gaya:
     * `MailFake::raw()` badannya kosong — ia tak mencatat apa pun, sehingga
     * assert apa pun sesudahnya akan lulus meski surat benar-benar terkirim ke
     * alamat yang salah. Transport `array` menyimpan pesan Symfony yang
     * sesungguhnya, lengkap dengan penerimanya.
     */
    public function test_email_uji_hanya_ke_alamat_sendiri(): void
    {
        config(['mail.default' => 'array']);
        $transport = Mail::mailer('array')->getSymfonyTransport();
        $transport->flush();

        $admin = $this->orang('admin_it');

        $this->actingAs($admin)
            ->post(route('pengaturan.sistem.uji-email'), ['email' => 'orang.lain@contoh.test'])
            ->assertRedirect();

        $terkirim = $transport->messages();
        $this->assertCount(1, $terkirim);

        $penerima = array_map(
            fn ($a) => $a->getAddress(),
            $terkirim->first()->getOriginalMessage()->getTo()
        );

        $this->assertSame([$admin->email], $penerima);
        $this->assertNotContains('orang.lain@contoh.test', $penerima);
    }

    /** Janji 5 — layarnya milik Admin saja. */
    public function test_layar_tertutup_bagi_bukan_admin(): void
    {
        $gl = $this->orang(User::JABATAN_GROUP_LEADER);

        $this->actingAs($gl)->get(route('pengaturan.sistem'))->assertForbidden();
        $this->actingAs($gl)->put(route('pengaturan.sistem.ai'), $this->isian())->assertForbidden();
        $this->actingAs($gl)->post(route('pengaturan.sistem.cache'))->assertForbidden();

        $this->actingAs($this->orang('admin_it'))->get(route('pengaturan.sistem'))->assertOk();
    }

    /** Bersihkan cache: tercatat di audit, dan tak menghapus satu pun setelan. */
    public function test_bersihkan_cache_teraudit_dan_tak_menghapus_setelan(): void
    {
        Pengaturan::simpan('ai.model', 'gemini-2.5-pro');
        $admin = $this->orang('admin_it');

        $this->actingAs($admin)->post(route('pengaturan.sistem.cache'))->assertRedirect();

        $this->lupakanCache();
        $this->assertSame('gemini-2.5-pro', Pengaturan::ai()['model']);
        $this->assertNotNull(
            AuditLog::where('action', 'pengaturan.cache_dibersihkan')->where('user_id', $admin->id)->first()
        );
    }

    // ============ Uji Koneksi AI (PLAN-PREPRODUKSI-v9 Fase 2a) ============
    //
    // Dipalsukan dengan Http::fake(), bukan MailFake: `Http::fake` benar-benar
    // MENCATAT permintaannya, jadi "tak ada panggilan HTTP saat AI mati" bisa
    // dibuktikan — bukan sekadar diasumsikan.

    /** Simpan setelan AI langsung ke tabel, melewati layar. */
    private function setelAi(array $nilai): void
    {
        foreach ($nilai as $k => $v) {
            str_ends_with($k, '.key')
                ? Pengaturan::simpanRahasia($k, $v)
                : Pengaturan::simpan($k, $v);
        }

        $this->lupakanCache();
    }

    /** Kunci ditolak penyedia → pesan penyedia tampil apa adanya, bukan "401" telanjang. */
    public function test_uji_ai_menampilkan_pesan_penyedia_saat_kunci_ditolak(): void
    {
        $this->setelAi(['ai.enabled' => '1', 'ai.provider' => 'gemini', 'ai.model' => 'gemini-2.0-flash', 'ai.key' => 'kunci-salah']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(
            ['error' => ['message' => 'API key not valid. Please pass a valid API key.']], 400
        )]);

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.sistem.uji-ai'))
            ->assertRedirect()
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'API key not valid'));
    }

    /** Kunci diterima → pesan berhasil, dan penyedia+model ikut disebut. */
    public function test_uji_ai_berhasil_menyebut_penyedia_dan_model(): void
    {
        $this->setelAi(['ai.enabled' => '1', 'ai.provider' => 'gemini', 'ai.model' => 'gemini-2.0-flash', 'ai.key' => 'kunci-benar']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['name' => 'models/gemini-2.0-flash'], 200)]);

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.sistem.uji-ai'))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($p) => str_contains($p, 'terhubung') && str_contains($p, 'gemini-2.0-flash'));
    }

    /**
     * Saklar mati → jawabannya "dinonaktifkan" dan TAK SATU pun panggilan HTTP.
     *
     * Bagian kedua itulah intinya: layar yang tetap menembak penyedia meski AI
     * dimatikan adalah tagihan yang tak seorang pun tahu asalnya.
     */
    public function test_uji_ai_saat_saklar_mati_tak_memanggil_penyedia(): void
    {
        $this->setelAi(['ai.enabled' => '0', 'ai.provider' => 'gemini', 'ai.model' => 'gemini-2.0-flash', 'ai.key' => 'kunci-benar']);

        Http::fake();

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.sistem.uji-ai'))
            ->assertRedirect()
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'dinonaktifkan'));

        Http::assertNothingSent();
    }

    /** Rantai cadangan dilaporkan PER PENYEDIA — Admin harus tahu kunci mana yang mati. */
    public function test_uji_ai_melaporkan_utama_dan_cadangan_terpisah(): void
    {
        $this->setelAi([
            'ai.enabled' => '1',
            'ai.provider' => 'gemini', 'ai.model' => 'gemini-2.0-flash', 'ai.key' => 'kunci-gemini',
            'ai.cadangan.provider' => 'openrouter', 'ai.cadangan.model' => 'model-cadangan', 'ai.cadangan.key' => 'kunci-or',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['name' => 'models/gemini-2.0-flash'], 200),
            'openrouter.ai/*' => Http::response(['error' => ['message' => 'No auth credentials found']], 401),
        ]);

        $this->actingAs($this->orang('admin_it'))
            ->post(route('pengaturan.sistem.uji-ai'))
            ->assertRedirect()
            ->assertSessionHas('error', fn ($p) => str_contains($p, 'Utama')
                && str_contains($p, 'Cadangan')
                && str_contains($p, 'terhubung')
                && str_contains($p, 'No auth credentials found'));
    }

    /** Audit mencatat hasilnya — TAK PERNAH kuncinya (aturan v8 §9.4 butir 5). */
    public function test_uji_ai_teraudit_tanpa_membocorkan_kunci(): void
    {
        $rahasia = 'AIza-rahasia-sekali-'.Str::random(8);
        $this->setelAi(['ai.enabled' => '1', 'ai.provider' => 'gemini', 'ai.model' => 'gemini-2.0-flash', 'ai.key' => $rahasia]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['name' => 'models/gemini-2.0-flash'], 200)]);

        $admin = $this->orang('admin_it');
        $this->actingAs($admin)->post(route('pengaturan.sistem.uji-ai'))->assertRedirect();

        $log = AuditLog::where('action', 'pengaturan.ai_diuji')->where('user_id', $admin->id)->firstOrFail();
        $jejak = json_encode($log->getAttributes(), JSON_UNESCAPED_SLASHES);

        $this->assertStringNotContainsString($rahasia, $jejak, 'kunci API tak boleh mendarat di audit log');
        $this->assertStringContainsString('gemini', $jejak);
    }

    /** Layar & rutenya milik Admin saja — sama dengan tombol uji email di sebelahnya. */
    public function test_uji_ai_tertutup_bagi_bukan_admin(): void
    {
        Http::fake();

        $this->actingAs($this->orang(User::JABATAN_GROUP_LEADER))
            ->post(route('pengaturan.sistem.uji-ai'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    /** Menu sidebar-nya muncul untuk Admin, tidak untuk GL (Fase 4 tak boleh bergeser). */
    public function test_menu_konfigurasi_sistem_hanya_untuk_admin(): void
    {
        $this->actingAs($this->orang('admin_it'))->get(route('dashboard'))
            ->assertOk()->assertSee('Konfigurasi Sistem');

        $this->actingAs($this->orang(User::JABATAN_GROUP_LEADER))->get(route('dashboard'))
            ->assertOk()->assertDontSee('Konfigurasi Sistem');
    }
}
