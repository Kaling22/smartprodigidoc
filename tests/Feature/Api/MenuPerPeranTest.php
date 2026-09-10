<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\JobExecution;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * PLAN-MOBILE-v6 FASE 2 — menu per peran.
 *
 * Tiga fitur, satu berkas: ketiganya menjawab pertanyaan yang sama dengan
 * jawaban yang harus KONSISTEN — "sampai mana daftar orang ini melebar?"
 * (`User::lingkupTim()`). Memisahnya jadi tiga berkas membuat perbedaan
 * jawabannya justru sulit terlihat.
 */
class MenuPerPeranTest extends TestCase
{
    use DatabaseTransactions;

    private User $glPlant;
    private User $shPlant;
    private User $stafPlant;
    private User $stafShe;
    private Document $dokPlant;
    private Document $dokShe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        // Notifikasi bukan yang diuji di sini, dan `adopsi` mengirimnya ke
        // seluruh penyusun — tanpa fake ini test bergantung pada mailer.
        Notification::fake();

        $plant = Department::where('code', 'PLANT')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();

        $this->glPlant = $this->akun('GL-PLT-F2', User::JABATAN_GROUP_LEADER, $plant->id);
        $this->shPlant = $this->akun('SH-PLT-F2', User::JABATAN_SECTION_HEAD, $plant->id);
        $this->stafPlant = $this->akun('STF-PLT-F2', User::JABATAN_STAFF, $plant->id);
        $this->stafShe = $this->akun('STF-SHE-F2', User::JABATAN_STAFF, $she->id);

        $glShe = $this->akun('GL-SHE-F2', User::JABATAN_GROUP_LEADER, $she->id);

        $this->dokPlant = $this->dokumen($this->glPlant, $plant, 'SOP Uji Fase 2 PLANT');
        $this->dokShe = $this->dokumen($glShe, $she, 'SOP Uji Fase 2 SHE');
    }

    private function akun(string $nrp, ?string $jabatan, ?int $departmentId): User
    {
        $user = User::create([
            'name' => $nrp, 'nrp' => $nrp, 'jabatan' => $jabatan, 'status' => 'active',
            'password' => bcrypt('rahasia123'), 'department_id' => $departmentId,
        ]);

        // Izin datang dari ROLE, bukan dari `jabatan` — tanpa sinkronisasi ini
        // `can('document.request_revision')` selalu false dan test hijau karena
        // alasan yang salah.
        $user->syncRoles([match ($jabatan) {
            User::JABATAN_GROUP_LEADER => \Database\Seeders\RolePermissionSeeder::ROLE_GROUP_LEADER,
            User::JABATAN_SECTION_HEAD => \Database\Seeders\RolePermissionSeeder::ROLE_SECTION_HEAD,
            default => \Database\Seeders\RolePermissionSeeder::ROLE_STAFF,
        }]);

        return $user->refresh();
    }

    private function dokumen(User $pembuat, Department $dept, string $judul, string $kode = 'SOP'): Document
    {
        $doc = app(DocumentService::class)->createDraft(
            $pembuat, DocumentType::where('code', $kode)->firstOrFail(), $dept, $judul,
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    private function masukan(Document $doc, User $pengirim, string $isi): DocumentFeedback
    {
        Sanctum::actingAs($pengirim);
        $res = $this->postJson("/api/documents/{$doc->id}/masukan", ['isi' => $isi])->assertCreated();

        return DocumentFeedback::findOrFail($res->json('data.id'));
    }

    private function pekerjaan(User $pelaksana, Document $jsa): JobExecution
    {
        return JobExecution::create([
            'document_id' => $jsa->id,
            'user_id' => $pelaksana->id,
            'nama_pekerjaan' => "Pekerjaan {$pelaksana->nrp}",
            'lokasi' => 'Pit 3',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'analisa_snapshot' => [],
            'status' => 'berlangsung',
        ]);
    }

    // ── 2.1 Ketersediaan tertutup bagi Non-Staff ─────────────────────────────

    public function test_non_staff_tak_bisa_mencatat_ketersediaan_di_kedua_kanal(): void
    {
        $badan = ['jenis' => 'off_day', 'mulai' => now()->addDay()->toDateString(),
            'sampai' => now()->addDay()->toDateString()];

        Sanctum::actingAs($this->stafPlant);
        $this->postJson('/api/off', $badan)->assertForbidden();

        // Menutup pintu HP saja bukan pembatasan — web harus ikut tertutup.
        $this->actingAs($this->stafPlant)->post('/off', $badan)->assertForbidden();

        // GL tetap bisa: menunya memang hanya dilepas dari Beranda Non-Staff.
        Sanctum::actingAs($this->glPlant);
        $this->postJson('/api/off', $badan)->assertCreated();
    }

    // ── 2.2 Masukan Dokumen (kotak masuk) ────────────────────────────────────

    public function test_kotak_masuk_gl_berisi_masukan_departemennya_saja(): void
    {
        $milikPlant = $this->masukan($this->dokPlant, $this->stafPlant, 'Rambu simpang tak terbaca.');
        $milikShe = $this->masukan($this->dokShe, $this->stafShe, 'APD di gudang kurang.');

        Sanctum::actingAs($this->glPlant);
        $res = $this->getJson('/api/masukan')->assertOk()
            ->assertJsonPath('meta.mode', 'kotak_masuk');

        $id = collect($res->json('data'))->pluck('id');
        $this->assertTrue($id->contains($milikPlant->id), 'Masukan sedepartemen tak sampai ke GL.');
        $this->assertFalse($id->contains($milikShe->id), 'Masukan departemen lain bocor ke GL.');

        // Kotak masuk tanpa nama pengirim tak bisa ditindaklanjuti.
        $baris = collect($res->json('data'))->firstWhere('id', $milikPlant->id);
        $this->assertSame($this->stafPlant->name, $baris['pengirim']['nama']);
        $this->assertSame($this->stafPlant->nrp, $baris['pengirim']['nrp']);
        // Pratinjau dokumen: HP menyusun `{publicUrl}/{jenis}/{berkas}`.
        $this->assertSame('SOP', $baris['jenis']);
        $this->assertSame($this->dokPlant->id.'.pdf', $baris['berkas']);
    }

    public function test_non_staff_tetap_melihat_masukan_saya_seperti_semula(): void
    {
        $punyaku = $this->masukan($this->dokPlant, $this->stafPlant, 'Punya saya.');

        // Masukan orang lain di departemen yang sama — TIDAK boleh ikut.
        $lain = $this->akun('STF-PLT-F2B', User::JABATAN_STAFF, $this->stafPlant->department_id);
        $punyaLain = $this->masukan($this->dokPlant, $lain, 'Punya orang lain.');

        Sanctum::actingAs($this->stafPlant);
        $res = $this->getJson('/api/masukan')->assertOk()
            ->assertJsonPath('meta.mode', 'saya');

        $id = collect($res->json('data'))->pluck('id');
        $this->assertTrue($id->contains($punyaku->id));
        $this->assertFalse($id->contains($punyaLain->id));
    }

    public function test_gl_membalas_dari_hp_lalu_masukannya_tertutup(): void
    {
        $m = $this->masukan($this->dokPlant, $this->stafPlant, 'Perlu penerangan tambahan.');

        Sanctum::actingAs($this->glPlant);

        // Sejak PLAN-REVISI-v6 Fase C penjaganya izin TERSENDIRI: membalas
        // masukan dipisah dari merevisi dokumen (keputusan D4).
        $this->assertTrue($this->glPlant->can('document.feedback_respond'));

        $this->postJson("/api/masukan/{$m->id}/balas", ['balasan' => 'Sudah diajukan ke SHE.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ditolak')
            ->assertJsonPath('data.balasan', 'Sudah diajukan ke SHE.');

        // Sekali tutup, tak bisa ditimpa — jejak tindak lanjutnya tetap utuh.
        $this->postJson("/api/masukan/{$m->id}/balas", ['balasan' => 'Ralat.'])->assertForbidden();
    }

    public function test_gl_departemen_lain_tak_bisa_membalas(): void
    {
        $m = $this->masukan($this->dokShe, $this->stafShe, 'Masukan SHE.');

        Sanctum::actingAs($this->glPlant);
        $this->postJson("/api/masukan/{$m->id}/balas", ['balasan' => 'Numpang jawab.'])
            ->assertForbidden();
    }

    /**
     * PLAN-REVISI-v6 Fase C (keputusan D1) MEMBALIK peran di test ini: yang
     * memulai revisi kini PENYUSUNNYA (GL, atas dokumen buatannya), sedangkan
     * SH — yang meninjau & menyetujui — tak boleh sekaligus yang memulainya.
     */
    public function test_gl_mengadopsi_masukan_menjadi_revisi_sedangkan_sh_tidak_boleh(): void
    {
        $m = $this->masukan($this->dokPlant, $this->stafPlant, 'Langkah 4 tak sesuai lapangan.');

        Sanctum::actingAs($this->shPlant);
        $this->postJson("/api/masukan/{$m->id}/adopsi")->assertForbidden();

        Sanctum::actingAs($this->glPlant);
        $this->postJson("/api/masukan/{$m->id}/adopsi", ['alasan' => 'Ditinjau ulang bersama SHE.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'diadopsi');

        // Draft revisi benar-benar lahir, dan versi lama masuk "sedang direvisi".
        $revisi = Document::where('revises_document_id', $this->dokPlant->id)->first();
        $this->assertNotNull($revisi, 'Draft revisi tak dibuat.');
        $this->assertSame('sedang_direvisi', $this->dokPlant->refresh()->status);

        // Tertaut ke revisinya — "kenapa dokumen ini direvisi?" bisa dijawab
        // sampai ke masukan pemicunya.
        $this->assertSame($revisi->id, $m->refresh()->revision_document_id);

        // Alasan pengaju + nomor masukannya tercatat sebagai Review.
        $ringkasan = $revisi->reviews()->value('summary');
        $this->assertStringContainsString('Ditinjau ulang bersama SHE.', $ringkasan);
        $this->assertStringContainsString($m->feedback_number, $ringkasan);
    }

    // ── 2.3 Riwayat Pekerjaan tim ────────────────────────────────────────────

    public function test_gl_melihat_pekerjaan_bawahannya_tapi_tak_boleh_mencentangnya(): void
    {
        $jsa = $this->dokPlant;
        $punyaStaf = $this->pekerjaan($this->stafPlant, $jsa);
        $punyaShe = $this->pekerjaan($this->stafShe, $this->dokShe);

        Sanctum::actingAs($this->glPlant);

        $id = collect($this->getJson('/api/pekerjaan')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($id->contains($punyaStaf->id), 'GL tak melihat pekerjaan bawahannya.');
        $this->assertFalse($id->contains($punyaShe->id), 'Pekerjaan departemen lain bocor.');

        // Daftar dan detail HARUS sepakat: kartu yang ditampilkan lalu 403 saat
        // diketuk adalah menu yang berbohong.
        $this->getJson("/api/pekerjaan/{$punyaStaf->id}")->assertOk()
            ->assertJsonPath('data.pelaksana.nama', $this->stafPlant->name);
        $this->getJson("/api/pekerjaan/{$punyaShe->id}")->assertForbidden();

        // Atasan MELIHAT, bukan mencentang.
        $this->patchJson("/api/pekerjaan/{$punyaStaf->id}/checklist", [
            'items' => [['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true]],
        ])->assertForbidden();

        $this->patchJson("/api/pekerjaan/{$punyaStaf->id}/selesai", ['status' => 'selesai'])
            ->assertForbidden();
    }

    /**
     * Yang MELAKSANAKAN pekerjaan hanya Non-Staff & GL (ketetapan pemilik).
     * SH/DH & PJO membuka layar yang sama untuk MENGAWASI — dan GL, karena ia
     * ikut mengerjakan, harus melihat dirinya sendiri di daftar itu.
     */
    public function test_hanya_non_staff_dan_gl_yang_memulai_pekerjaan(): void
    {
        $jsa = $this->dokumen(
            $this->glPlant,
            Department::where('code', 'PLANT')->firstOrFail(),
            'JSA Uji Fase 2 PLANT',
            'JSA',
        );

        $badan = [
            'document_id' => $jsa->id, 'nama_pekerjaan' => 'Ganti liner crusher',
            'lokasi' => 'Pit 3', 'tanggal_pelaksanaan' => now()->toDateString(),
        ];

        // SH mengawasi, bukan turun mencentang pengendalian.
        Sanctum::actingAs($this->shPlant);
        $this->postJson('/api/pekerjaan', $badan)->assertForbidden();

        Sanctum::actingAs($this->stafPlant);
        $this->postJson('/api/pekerjaan', $badan)->assertCreated();

        Sanctum::actingAs($this->glPlant);
        $punyaGl = $this->postJson('/api/pekerjaan', $badan)->assertCreated()->json('data.id');

        // GL melihat DIRINYA SENDIRI di daftar, bukan cuma bawahannya.
        $id = collect($this->getJson('/api/pekerjaan')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($id->contains($punyaGl), 'GL tak melihat pekerjaannya sendiri.');

        // SH tetap melihat KEDUANYA — pengawasan tak ikut tertutup.
        Sanctum::actingAs($this->shPlant);
        $this->assertCount(
            2,
            collect($this->getJson('/api/pekerjaan?status=berlangsung')->json('data')),
        );
    }

    public function test_non_staff_hanya_melihat_pekerjaannya_sendiri(): void
    {
        $punyaku = $this->pekerjaan($this->stafPlant, $this->dokPlant);
        $rekan = $this->akun('STF-PLT-F2C', User::JABATAN_STAFF, $this->stafPlant->department_id);
        $punyaRekan = $this->pekerjaan($rekan, $this->dokPlant);

        Sanctum::actingAs($this->stafPlant);

        $id = collect($this->getJson('/api/pekerjaan')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($id->contains($punyaku->id));
        $this->assertFalse($id->contains($punyaRekan->id), 'Non-Staff melihat pekerjaan rekannya.');

        $this->getJson("/api/pekerjaan/{$punyaRekan->id}")->assertForbidden();
    }
}
