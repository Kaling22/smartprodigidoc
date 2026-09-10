<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Ikhtisar Dokumen + meter growth (widget 1 & 2).
 *
 * Yang dikunci HANYA aritmetikanya: pengelompokan ke ember waktu dan persen
 * tertinjau. Bentuk visualnya (busur, dropdown) sengaja tak diuji — itu berubah
 * tiap kali tampilan dirapikan.
 */
class DashboardIkhtisarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Kartu Ketersediaan Saya di halaman yang sama memanggil API libur nasional.
        Http::preventStrayRequests();
        Http::fake(['api-harilibur.vercel.app/*' => Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);
    }

    public function test_ember_hari_dan_persen_tertinjau(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();
        $gl = User::create([
            'name' => 'GL Ikhtisar', 'nrp' => 'DI-1', 'jabatan' => 'group_leader',
            'department_id' => $dept->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $gl->assignRole('group_leader');

        $sop = DocumentType::where('code', 'SOP')->firstOrFail();
        $svc = app(DocumentService::class);

        // Basis dulu: DB uji sudah berisi dokumen seeder, jadi yang diuji SELISIH
        // sebelum/sesudah — bukan angka mutlak yang berubah tiap kali seeder diubah.
        $awal = $this->tren($gl);

        // Dua dokumen dibuat hari ini: satu masih draft, satu sudah Berlaku.
        $svc->createDraft($gl, $sop, $dept, 'SOP Draft Hari Ini');
        $svc->createDraft($gl, $sop, $dept, 'SOP Berlaku Hari Ini')
            ->update(['status' => 'published', 'published_at' => now()]);

        $kini = $this->tren($gl);

        $this->assertSame(2, $kini['hari']['total'] - $awal['hari']['total'], 'keduanya masuk 14 hari terakhir');
        $this->assertSame(1, $kini['hari']['tertinjau'] - $awal['hari']['tertinjau'], 'hanya yang Berlaku terhitung tertinjau');
        $this->assertSame(2, end($kini['hari']['dibuat']) - end($awal['hari']['dibuat']), 'ember terakhir = hari ini');
        $this->assertSame(1, end($kini['hari']['berlaku']) - end($awal['hari']['berlaku']));

        // growth = tertinjau / dibuat, dihitung dari angka periode yang sama.
        $this->assertSame(
            (int) round($kini['hari']['tertinjau'] / $kini['hari']['total'] * 100),
            $kini['hari']['growth'],
        );

        // Ember bulanan memandang data yang sama, hanya rentangnya berbeda.
        $this->assertSame(2, $kini['bulan']['total'] - $awal['bulan']['total']);
        $this->assertCount(8, $kini['bulan']['labels']);
        $this->assertCount(14, $kini['hari']['labels']);
    }

    /**
     * @return array<string, array>
     *
     * Dulu `viewData('tren')`. Sejak dashboard jadi halaman Inertia seluruh
     * props duduk di satu variabel view `page`; yang berubah cuma PEMBACANYA —
     * aritmetika yang diuji di atas sama persis.
     */
    private function tren(User $user): array
    {
        return $this->propsInertia(
            $this->actingAs($user)->get(route('dashboard'))->assertOk()
        )['tren'];
    }
}
