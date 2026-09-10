<?php

namespace Tests\Feature\Api;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kontrak API untuk Form Kerja JSA di aplikasi mobile.
 *
 * Berbeda dari {@see \Tests\Feature\JobExecutionTest} yang menguji ATURANNYA,
 * berkas ini meniru URUTAN PANGGILAN dan NAMA KUNCI persis seperti yang ditulis
 * aplikasi (`lib/JSA/jsaWorkForm.dart`):
 *
 *   1. GET  /api/me                                → `departemen` dipakai menyusun URL berikutnya
 *   2. GET  /api/documents?type=JSA&department=…   → daftar, dicari lewat `judul_jsa`
 *   3. GET  /api/documents/{id}                    → `jsa_info.lokasi_kerja` + `langkah`
 *   4. POST /api/pekerjaan                         → 201
 *   5. PATCH /api/pekerjaan/{id}/checklist         → 200
 *   6. PATCH /api/pekerjaan/{id}/selesai           → 200
 *
 * Kuncinya: aplikasi berjalan di HP dan tak bisa ikut di-deploy bersama server.
 * Mengganti nama kunci di sini = aplikasi di lapangan rusak tanpa peringatan
 * apa pun, dan test inilah satu-satunya yang menyadarkan lebih dulu.
 */
class KontrakMobileJsaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_urutan_panggilan_aplikasi_kerja_jsa(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $jsa = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'JSA Kontrak Mobile');
        $jsa->contents()->createMany([
            ['section_key' => 'lokasi_kerja', 'value_json' => 'Workshop A'],
            ['section_key' => 'analisa', 'value_json' => [
                ['langkah' => 'Persiapan', 'bahaya' => [
                    ['risiko' => 'Terjatuh', 'pengendalian' => ['Body harness', 'Lifeline']],
                ]],
            ]],
        ]);
        $jsa->update(['status' => 'published', 'published_at' => now()]);

        Sanctum::actingAs($gl);

        // (1) Aplikasi menyusun URL daftar dari `departemen` milik user.
        $dept = $this->getJson('/api/me')->assertOk()->json('user.departemen');
        $this->assertNotNull($dept, 'aplikasi memakai `departemen` untuk menyaring daftar JSA');

        // (2) Daftar JSA. Pencarian di aplikasi membaca `judul_jsa`, BUKAN `judul`.
        $daftar = $this->getJson("/api/documents?type=JSA&department={$dept}")->assertOk();
        $baris = collect($daftar->json('data'))->firstWhere('id', $jsa->id);

        $this->assertNotNull($baris, 'JSA Berlaku harus muncul di daftar departemennya');
        $this->assertSame('JSA Kontrak Mobile', $baris['judul_jsa'] ?? null,
            'alias `judul_jsa` dipakai kotak pencarian aplikasi');
        $this->assertSame('JSA Kontrak Mobile', $baris['judul'] ?? null);

        // (3) Detail: lokasi kerja mengisi formulir, `langkah` menyusun checklist.
        $detail = $this->getJson("/api/documents/{$jsa->id}")->assertOk();

        $this->assertSame('Workshop A', $detail->json('data.jsa_info.lokasi_kerja'));
        $this->assertSame('Persiapan', $detail->json('data.langkah.0.teks'));
        $this->assertSame('Terjatuh', $detail->json('data.langkah.0.bahaya.0.risiko'));
        $this->assertSame('Lifeline', $detail->json('data.langkah.0.bahaya.0.pengendalian.1.teks'));
        $this->assertSame(1, $detail->json('data.langkah.0.bahaya.0.pengendalian.1.ke'),
            'indeks `ke` itulah yang dikirim balik sebagai pengendalian_ke');

        // (4) Mulai pekerjaan — badan permintaan PERSIS seperti yang dikirim aplikasi.
        $mulai = $this->postJson('/api/pekerjaan', [
            'document_id' => $jsa->id,
            'nama_pekerjaan' => 'JSA Kontrak Mobile',
            'lokasi' => 'Workshop A',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'catatan' => 'Shift pagi',
        ])->assertCreated();

        $idPekerjaan = $mulai->json('data.id');
        // Layar berikutnya langsung memakai balasan ini, jadi bentuknya harus utuh.
        $this->assertSame('berlangsung', $mulai->json('data.status'));
        $this->assertSame(['total' => 2, 'selesai' => 0], $mulai->json('data.progres'));

        // (5) Centang — `catatan` dikirim null saat kosong.
        $this->patchJson("/api/pekerjaan/{$idPekerjaan}/checklist", ['items' => [
            ['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true, 'catatan' => null],
            ['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 1, 'checked' => true, 'catatan' => 'Dicek ulang'],
        ]])->assertOk()
            ->assertJsonPath('data.progres', ['total' => 2, 'selesai' => 2])
            ->assertJsonPath('data.langkah.0.bahaya.0.pengendalian.1.checked', true);

        // (6) Selesai.
        $this->patchJson("/api/pekerjaan/{$idPekerjaan}/selesai", ['status' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('data.status', 'selesai');
    }

    /**
     * Aplikasi memasang menunya dari `bisa_tinjau` — bukan menebak dari jabatan.
     * Dipakai layar Tinjau JSA (Fase B2).
     */
    public function test_me_memberi_kapabilitas_untuk_menu_aplikasi(): void
    {
        Sanctum::actingAs($this->aktorSh());

        $this->getJson('/api/me')->assertOk()->assertJsonStructure(['user' => [
            'id', 'nrp', 'nama', 'departemen', 'role',
            'bisa_tinjau', 'bisa_tinjau_jsa', 'bisa_beri_masukan',
        ]]);
    }

    /** Jenis atau departemen yang tak dikenal membalas daftar KOSONG, bukan semua dokumen. */
    public function test_saringan_salah_ketik_tak_membocorkan_dokumen_lain(): void
    {
        Sanctum::actingAs($this->aktorGl());

        $this->getJson('/api/documents?type=JSAA&department=ICTMD')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/documents?type=JSA&department=TIDAKADA')->assertOk()->assertJsonCount(0, 'data');
    }
}
