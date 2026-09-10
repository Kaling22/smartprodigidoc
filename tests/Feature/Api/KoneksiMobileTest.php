<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Butir 4 — permukaan API yang dipanggil aplikasi mobile TAPI belum ada
 * servernya: pendaftaran akun, sunting profil + foto, arsip Tidak Berlaku,
 * dan menu Informasi.
 *
 * Yang dikunci di sini bukan selera bentuk JSON melainkan KONTRAK: tiap nama
 * kunci di bawah benar-benar dibaca sebuah baris di `SmartPro-mobile/lib`.
 * Mengubahnya membuat layar di HP gagal diam-diam — kosong, bukan bergalat —
 * dan tak ada satu pun test lain yang menangkapnya.
 */
class KoneksiMobileTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Rute baru ber-throttle ketat (register 3/menit, foto 10/menit).
        // Tanpa ini test yang berjalan beruntun saling mewarisi hitungannya.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /** Dokumen Berlaku milik departemen GL bawaan, siap dijadikan arsip. */
    private function dokumen(User $gl, string $judul): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    // ── Pendaftaran akun (POST /api/register) ────────────────────────────────

    public function test_pendaftaran_dari_hp_membuat_akun_non_staff_pending_tanpa_token(): void
    {
        $res = $this->postJson('/api/register', [
            'nama' => 'Pendaftar Mobile',
            'nrp' => 'REG-MOB-1',
            'no_hp' => '081234567890',
            'jabatan' => 'Teknisi ICTMD',
            'departemen' => 'ICTMD',          // KODE, bukan id — kosakata mobile
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertCreated();

        // Token TIDAK boleh ikut: akun `pending` yang memegangnya hanya punya
        // kredensial hidup yang setiap pemakaiannya ditolak isActive().
        $this->assertArrayNotHasKey('token', $res->json());

        $baru = User::where('nrp', 'REG-MOB-1')->firstOrFail();
        $this->assertSame('pending', $baru->status);
        $this->assertSame(User::JABATAN_STAFF, $baru->jabatan);
        // Jabatan yang DIKETIK pendaftar tak boleh menimpa kunci alur.
        $this->assertSame('Teknisi ICTMD', $baru->jabatan_diajukan);
        $this->assertSame(Department::where('code', 'ICTMD')->value('id'), $baru->department_id);

        // Dan ia benar-benar belum bisa masuk.
        $this->postJson('/api/login', ['nrp' => 'REG-MOB-1', 'password' => 'rahasia123'])
            ->assertStatus(403);
    }

    public function test_pendaftaran_menolak_kode_departemen_asing_dan_nrp_kembar(): void
    {
        $this->postJson('/api/register', [
            'nama' => 'Salah Dept', 'nrp' => 'REG-MOB-2',
            'departemen' => 'COE',            // kosakata aplikasi LAMA, sudah ICTMD
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors(['department_id']);

        $this->postJson('/api/register', [
            'nama' => 'NRP Kembar', 'nrp' => $this->aktorGl()->nrp,
            'departemen' => 'ICTMD',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors(['nrp']);
    }

    // ── Profil (GET/PATCH /api/me, POST /api/me/foto) ────────────────────────

    public function test_me_mengirim_no_hp_email_dan_url_foto_yang_absolut(): void
    {
        Storage::fake('public');

        $gl = $this->aktorGl();
        $gl->update(['nomor_hp' => '0811', 'email' => 'gl-uji@ppa.test', 'photo_path' => 'avatars/uji.jpg']);

        Sanctum::actingAs($gl);

        $res = $this->getJson('/api/me')->assertOk()
            ->assertJsonPath('user.no_hp', '0811')
            ->assertJsonPath('user.email', 'gl-uji@ppa.test');

        // ABSOLUT, bukan `/storage/...`: NetworkImage di HP menolak URL relatif
        // dan avatarnya tak pernah tampil, tanpa satu pun pesan galat.
        $this->assertStringStartsWith('http', $res->json('user.foto'));
        $this->assertStringEndsWith('/storage/avatars/uji.jpg', $res->json('user.foto'));
    }

    public function test_patch_me_memperbarui_hp_dan_email_tanpa_menyentuh_identitas(): void
    {
        $gl = $this->aktorGl();
        $namaAsli = $gl->name;

        Sanctum::actingAs($gl);

        $this->patchJson('/api/me', ['no_hp' => '085700001111', 'email' => 'baru@ppa.test'])
            ->assertOk()
            ->assertJsonPath('user.no_hp', '085700001111');

        $gl->refresh();
        $this->assertSame('085700001111', $gl->nomor_hp);
        $this->assertSame('baru@ppa.test', $gl->email);
        // Nama/NRP/jabatan ditetapkan admin — tak boleh bisa disunting dari HP.
        $this->assertSame($namaAsli, $gl->name);
    }

    public function test_unggah_foto_menyimpan_berkas_lalu_hapus_foto_membuangnya(): void
    {
        Storage::fake('public');

        $gl = $this->aktorGl();
        Sanctum::actingAs($gl);

        $this->post('/api/me/foto', ['foto' => UploadedFile::fake()->image('profil.jpg')])
            ->assertOk();

        $jalur = $gl->refresh()->photo_path;
        $this->assertNotNull($jalur);
        Storage::disk('public')->assertExists($jalur);

        $this->patchJson('/api/me', ['hapus_foto' => true])->assertOk();

        $this->assertNull($gl->refresh()->photo_path);
        // Berkasnya ikut dibuang — kalau tidak, tiap penggantian foto
        // meninggalkan berkas yatim yang menumpuk sampai kuota hosting habis.
        Storage::disk('public')->assertMissing($jalur);
    }

    public function test_unggah_berkas_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->aktorGl());

        // Header `Accept` disertakan persis seperti ApiClient.unggahBerkas di
        // aplikasi. Tanpa itu Laravel membalas REDIRECT saat validasi gagal,
        // bukan 422 ber-JSON — dan aplikasi tak punya cara membaca sebabnya.
        $this->post(
            '/api/me/foto',
            ['foto' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')],
            ['Accept' => 'application/json'],
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['foto']);
    }

    // ── Arsip Tidak Berlaku (?status=tidak_berlaku) ──────────────────────────

    public function test_arsip_mengirim_dokumen_obsolete_beserta_nomor_penggantinya(): void
    {
        $gl = $this->aktorGl();

        $lama = $this->dokumen($gl, 'SOP Uji Arsip Lama');
        $baru = $this->dokumen($gl, 'SOP Uji Arsip Baru');
        $lama->update(['status' => 'obsolete']);
        $baru->update(['revises_document_id' => $lama->id]);

        Sanctum::actingAs($gl);

        $res = $this->getJson('/api/documents?status=tidak_berlaku')->assertOk();

        $baris = collect($res->json('data'))->firstWhere('id', $lama->id);
        $this->assertNotNull($baris, 'Dokumen obsolete tak ikut terkirim.');
        $this->assertSame('obsolete', $baris['status']);
        $this->assertSame('Tidak Berlaku', $baris['status_label']);
        $this->assertSame($baru->doc_number, $baris['digantikan_oleh']);

        // Daftar Berlaku TIDAK ikut memuatnya — dua cabang, bukan satu daftar
        // yang disaring di HP.
        $berlaku = collect($this->getJson('/api/documents')->json('data'))->pluck('id');
        $this->assertFalse($berlaku->contains($lama->id));
    }

    public function test_arsip_tertutup_bagi_non_staff_di_ketiga_pintunya(): void
    {
        $gl = $this->aktorGl();
        $lama = $this->dokumen($gl, 'SOP Uji Arsip Tertutup');
        $lama->update(['status' => 'obsolete']);

        $nonStaff = User::create([
            'name' => 'Non-Staff Uji Arsip', 'nrp' => 'STF-ARS-1',
            'jabatan' => User::JABATAN_STAFF, 'status' => 'active',
            'password' => bcrypt('rahasia123'), 'department_id' => $gl->department_id,
        ]);

        Sanctum::actingAs($nonStaff);
        $this->getJson('/api/documents?status=tidak_berlaku')->assertForbidden();
        $this->getJson("/api/documents/{$lama->id}")->assertNotFound();
        $this->getJson("/api/files/sop/{$lama->id}.pdf")->assertNotFound();

        // GL menempuh ketiganya dengan sukses — layar arsip memanggil daftar,
        // lalu detail, lalu berkas, berurutan untuk SATU pratinjau PDF.
        Sanctum::actingAs($gl);
        $this->getJson('/api/documents?status=tidak_berlaku')->assertOk();
        $this->getJson("/api/documents/{$lama->id}")->assertOk();
        $this->get("/api/files/sop/{$lama->id}.pdf")->assertOk();
    }

    public function test_status_salah_ketik_ditolak_bukan_diam_diam_jadi_berlaku(): void
    {
        Sanctum::actingAs($this->aktorGl());

        $this->getJson('/api/documents?status=draf')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ── Menu Informasi ───────────────────────────────────────────────────────

    public function test_informasi_mengirim_daftar_kategori_isi_dan_berkasnya(): void
    {
        Storage::fake('local');

        $gl = $this->aktorGl();

        $memo = Informasi::create([
            'kategori' => 'memo_external', 'nomor' => 'MEMO-UJI-01',
            'judul' => 'Memo Uji Mobile', 'file_path' => 'informasi/memo_external/uji.pdf',
            'file_mime' => 'application/pdf', 'berlaku' => true, 'uploaded_by' => $gl->id,
        ]);
        Storage::disk('local')->put($memo->file_path, '%PDF-1.4 uji');

        // Versi lama nomor yang sama — TIDAK boleh ikut ke HP.
        Informasi::create([
            'kategori' => 'memo_external', 'nomor' => 'MEMO-UJI-01',
            'judul' => 'Memo Uji Mobile (versi lama)', 'file_path' => 'informasi/memo_external/lama.pdf',
            'file_mime' => 'application/pdf', 'berlaku' => false, 'uploaded_by' => $gl->id,
        ]);

        Sanctum::actingAs($gl);

        // Tanpa `kategori` → grid menu, seluruh kategori AKTIF datang dari SERVER.
        $this->getJson('/api/informasi')->assertOk()
            ->assertJsonCount(count(Informasi::kategori(aktifSaja: true)), 'kategori')
            ->assertJsonPath('kategori.0.slug', 'kebijakan');

        $res = $this->getJson('/api/informasi?kategori=memo_external')->assertOk()
            ->assertJsonPath('label', 'MEMO External');

        $baris = collect($res->json('data'))->firstWhere('nomor', 'MEMO-UJI-01');
        $this->assertSame('Memo Uji Mobile', $baris['judul']);
        $this->assertFalse($baris['gambar']);
        $this->assertCount(1, collect($res->json('data'))->where('nomor', 'MEMO-UJI-01'));

        $this->get("/api/informasi/{$memo->id}/berkas")->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_informasi_kategori_asing_ditolak_dan_tanpa_token_tertutup(): void
    {
        Sanctum::actingAs($this->aktorGl());
        $this->getJson('/api/informasi?kategori=memo')->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/informasi')->assertStatus(401);
    }
}
