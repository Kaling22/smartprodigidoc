<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\JobExecution;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pelaksanaan pekerjaan memakai JSA — checklist dari HP + cerminannya di web.
 *
 * Kontrak API-nya dipatok di sini dengan sengaja: jalur, nama field, dan bentuk
 * balasannya dipakai aplikasi mobile apa adanya, jadi merapikannya tanpa
 * mengubah aplikasinya = merusak yang di lapangan.
 */
class JobExecutionTest extends TestCase
{
    use DatabaseTransactions;

    /** JSA BERLAKU beserta analisanya — acuan setiap pekerjaan. */
    private function jsaBerlaku(User $pembuat, string $judul)
    {
        $type = DocumentType::where('code', 'JSA')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($pembuat, $type, $pembuat->department, $judul);

        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Persiapan alat', 'bahaya' => [
                ['risiko' => 'Terjatuh', 'pengendalian' => ['Body harness', 'Lifeline']],
            ]],
            ['langkah' => 'Pengelasan', 'bahaya' => [
                ['risiko' => 'Percikan api', 'pengendalian' => ['Apron kulit']],
            ]],
        ]]);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    public function test_mobile_mulai_pekerjaan_lalu_centang_lalu_selesai(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->jsaBerlaku($gl, 'JSA Uji Pekerjaan');

        Sanctum::actingAs($gl);

        $mulai = $this->postJson('/api/pekerjaan', [
            'document_id' => $doc->id,
            'nama_pekerjaan' => 'Pengelasan rangka',
            'lokasi' => 'Workshop A',
            'tanggal_pelaksanaan' => now()->toDateString(),
        ])->assertCreated();

        $job = JobExecution::findOrFail($mulai->json('data.id'));

        // Snapshot: analisa disalin saat mulai, bukan dibaca ulang saat dilihat —
        // revisi JSA di kemudian hari tak boleh mengubah riwayat yang sudah jadi.
        $this->assertCount(2, $job->analisa_snapshot);
        $this->assertSame('Persiapan alat', $job->analisa_snapshot[0]['langkah']);

        $this->patchJson("/api/pekerjaan/{$job->id}/checklist", ['items' => [
            ['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true, 'catatan' => 'Harness dicek'],
        ]])->assertOk();

        // Dikirim dua kali: `uq_checklist_position` membuat centang ulang
        // memperbarui baris yang sama, bukan menggandakannya.
        $this->patchJson("/api/pekerjaan/{$job->id}/checklist", ['items' => [
            ['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true],
        ]])->assertOk();

        $this->assertSame(1, $job->checklistItems()->count());

        // Total dihitung per TINDAKAN PENGENDALIAN, bukan per langkah:
        // 2 pengendalian di langkah 0 + 1 di langkah 1.
        $this->assertSame(['total' => 3, 'selesai' => 1], $job->fresh()->progres());

        $this->patchJson("/api/pekerjaan/{$job->id}/selesai", ['status' => 'selesai'])->assertOk();
        $this->assertSame('selesai', $job->fresh()->status);

        // Pekerjaan yang sudah final tak bisa dicentang lagi.
        $this->patchJson("/api/pekerjaan/{$job->id}/checklist", ['items' => [
            ['langkah_ke' => 1, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true],
        ]])->assertStatus(422);

        $this->getJson('/api/pekerjaan')
            ->assertOk()
            ->assertJsonPath('data.0.nama_pekerjaan', 'Pengelasan rangka');
    }

    /**
     * Bahaya tanpa pengendalian tak boleh ikut jadi penyebut: tak ada posisi
     * `pengendalian_ke` yang sah untuknya, jadi kalau dihitung, bar kemajuan
     * mustahil penuh berapa pun yang dicentang pelaksana.
     */
    public function test_bahaya_tanpa_pengendalian_tak_menggantung_kemajuan(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->jsaBerlaku($gl, 'JSA Uji Progres');

        $job = JobExecution::create([
            'document_id' => $doc->id,
            'user_id' => $gl->id,
            'nama_pekerjaan' => 'Uji progres',
            'lokasi' => 'Workshop A',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'analisa_snapshot' => [
                ['langkah' => 'Langkah A', 'bahaya' => [
                    ['risiko' => 'Terjatuh', 'pengendalian' => ['Body harness']],
                    // JSA yang belum lengkap: risikonya ditulis, pengendaliannya belum.
                    ['risiko' => 'Terpeleset', 'pengendalian' => []],
                ]],
            ],
            'status' => 'berlangsung',
        ]);

        Sanctum::actingAs($gl);
        $this->patchJson("/api/pekerjaan/{$job->id}/checklist", ['items' => [
            ['langkah_ke' => 0, 'bahaya_ke' => 0, 'pengendalian_ke' => 0, 'checked' => true],
        ]])->assertOk();

        $this->assertSame(['total' => 1, 'selesai' => 1], $job->fresh()->progres());
    }

    /** Dokumen selain JSA yang berlaku tak boleh jadi acuan pekerjaan. */
    public function test_mobile_menolak_acuan_selain_jsa_berlaku(): void
    {
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $sop = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Bukan Acuan');
        $sop->update(['status' => 'published', 'published_at' => now()]);

        Sanctum::actingAs($gl);

        $this->postJson('/api/pekerjaan', [
            'document_id' => $sop->id,
            'nama_pekerjaan' => 'Coba pakai SOP',
            'lokasi' => 'Workshop A',
            'tanggal_pelaksanaan' => now()->toDateString(),
        ])->assertStatus(422);
    }

    /**
     * Detail JSA membawa langkah ter-INDEKS, sebab indeks itulah yang dikirim
     * balik aplikasi sebagai `langkah_ke`/`bahaya_ke`/`pengendalian_ke`.
     */
    public function test_detail_jsa_membalas_langkah_untuk_checklist_mobile(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->jsaBerlaku($gl, 'JSA Uji Payload');

        Sanctum::actingAs($gl);

        $this->getJson("/api/documents/{$doc->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'jsa_info' => ['lokasi_kerja', 'apd', 'tools'],
                'langkah' => [['ke', 'teks', 'bahaya' => [['ke', 'risiko', 'pengendalian' => [['ke', 'teks']]]]]],
            ]])
            ->assertJsonPath('data.langkah.0.teks', 'Persiapan alat')
            ->assertJsonPath('data.langkah.0.bahaya.0.pengendalian.1.ke', 1)
            ->assertJsonPath('data.langkah.0.bahaya.0.pengendalian.1.teks', 'Lifeline');
    }

    /**
     * Riwayat web dibatasi departemen — KECUALI Admin IT.
     *
     * Perannya bernama `admin_it`, bukan `admin`; dengan string yang keliru
     * Admin justru ikut tersaring ke departemennya sendiri dan tak pernah
     * melihat pekerjaan departemen lain.
     */
    public function test_riwayat_web_dibatasi_departemen_kecuali_admin(): void
    {
        $she = Department::where('code', 'SHE')->firstOrFail();
        $pelaksana = User::create([
            'name' => 'GL SHE Uji',
            'nrp' => 'GL-JOB-SHE',
            'jabatan' => User::JABATAN_GROUP_LEADER,
            'status' => 'active',
            'password' => bcrypt('rahasia123'),
            'department_id' => $she->id,
        ]);

        $doc = $this->jsaBerlaku($pelaksana, 'JSA SHE Uji Lingkup');

        JobExecution::create([
            'document_id' => $doc->id,
            'user_id' => $pelaksana->id,
            'nama_pekerjaan' => 'Inspeksi Tangki SHE',
            'lokasi' => 'Area 3B',
            'tanggal_pelaksanaan' => now()->toDateString(),
            'analisa_snapshot' => $doc->contentMap()['analisa'],
            'status' => 'berlangsung',
        ]);

        $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
            ->get(route('job-executions.index'))
            ->assertOk()
            ->assertSee('Inspeksi Tangki SHE');

        $this->actingAs($this->aktorGl())
            ->get(route('job-executions.index'))
            ->assertOk()
            ->assertDontSee('Inspeksi Tangki SHE');
    }

    public function test_pekerjaan_butuh_token(): void
    {
        $this->getJson('/api/pekerjaan')->assertUnauthorized();
    }
}
