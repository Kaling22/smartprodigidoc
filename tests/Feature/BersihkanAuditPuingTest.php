<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DocumentType;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `smartpro:bersihkan-audit-puing` — pembuang puing `audit_logs` sesudah
 * penggabungan basis data 2026-09-06.
 *
 * Yang diuji bukan "apakah ia menghapus" melainkan **apa yang TIDAK ikut
 * terhapus**. Perintah yang terlalu rakus tak akan berbunyi sama sekali: ia
 * berhasil, hanya saja timeline dokumen yang sehat kehilangan riwayatnya, dan
 * itu baru ketahuan berbulan-bulan kemudian saat ada yang mencarinya.
 *
 * Mode kering diuji terpisah karena ia satu-satunya yang dijalankan orang
 * sebelum memutuskan: mode kering yang diam-diam menghapus adalah cacat
 * terburuk yang bisa dipunyai perintah ini.
 */
class BersihkanAuditPuingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mode_kering_tak_menghapus_apa_pun(): void
    {
        $puing = $this->puing();
        $sebelum = AuditLog::count();

        $this->artisan('smartpro:bersihkan-audit-puing')->assertSuccessful();

        $this->assertSame($sebelum, AuditLog::count(),
            'Mode kering menghapus baris — orang yang cuma ingin melihat justru kehilangan data.');
        $this->assertDatabaseHas('audit_logs', ['id' => $puing]);
    }

    public function test_terapkan_membuang_yatim_dan_menyisakan_riwayat_yang_sehat(): void
    {
        $puing = $this->puing();

        // Dokumen sehat berikut riwayatnya — inilah yang tak boleh tersentuh.
        $gl = $this->berprofilPenuh($this->aktorGl());
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Puing Audit'
        );

        $sehat = AuditLog::where('document_id', $doc->id)->pluck('id');
        $this->assertNotEmpty($sehat, 'createDraft tak mencatat audit — tesnya kehilangan pembandingnya.');

        // Baris tanpa dokumen sama sekali (login, pengaturan) juga tak boleh
        // ikut: ia tak menunjuk dokumen apa pun, jadi mustahil salah tunjuk.
        $tanpaDokumen = AuditLog::create(['user_id' => $gl->id, 'action' => 'user.login', 'document_id' => null]);

        $this->artisan('smartpro:bersihkan-audit-puing --terapkan')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $puing]);
        $this->assertDatabaseHas('audit_logs', ['id' => $tanpaDokumen->id]);

        foreach ($sehat as $id) {
            $this->assertDatabaseHas('audit_logs', ['id' => $id]);
        }
    }

    /**
     * Satu baris audit yang menunjuk dokumen yang tak pernah ada — kriteria (a).
     *
     * Ditulis lewat query builder, bukan `AuditLog::create()`, supaya tak ada
     * satu pun kait model yang bisa memperbaikinya diam-diam.
     */
    private function puing(): int
    {
        $hantu = (int) DB::table('documents')->max('id') + 9_999;

        return (int) DB::table('audit_logs')->insertGetId([
            'user_id' => null,
            'action' => 'document.approve',
            'document_id' => $hantu,
            'created_at' => now(),
        ]);
    }
}
