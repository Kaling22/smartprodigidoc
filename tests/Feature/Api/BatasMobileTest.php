<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Butir 15 — PAGAR permukaan API mobile.
 *
 * Aturan yang ditetapkan user: dari HP orang MELIHAT, ia tidak membuat dokumen
 * dan tidak menyetujui. Pengecualiannya sudah ada dan disengaja (meninjau JSA,
 * mengirim masukan, mencatat pekerjaan JSA, mengajukan off).
 *
 * Ini test KONTRAK, bukan test perilaku: ia membaca tabel rute, bukan mengetuk
 * endpoint. Yang dijaga bukan "apakah controller X menolak" melainkan "apakah
 * controller X pernah TERPASANG di /api sama sekali" — sebab begitu ia
 * terpasang, satu peran yang salah pagar sudah cukup. Kelas bug ini paling
 * mahal kalau baru ketahuan setelah aplikasinya terpasang di HP orang.
 */
class BatasMobileTest extends TestCase
{
    /** @return array<int, \Illuminate\Routing\Route> */
    private function ruteApi(): array
    {
        return array_filter(
            Route::getRoutes()->getRoutes(),
            fn ($r) => str_starts_with($r->uri(), 'api/')
        );
    }

    public function test_api_tidak_punya_endpoint_membuat_atau_menyetujui_dokumen(): void
    {
        $terlarang = [
            'DocumentController@store', 'DocumentController@update',
            'DocumentController@submit', 'DocumentController@destroy',
            'DocumentController@saveStep', 'DocumentController@autosave',
            'ApprovalController@', 'NonaktifController@',
            'DocumentRevisionController@purge',
            'InformasiController@store', 'InformasiController@destroy',
        ];

        foreach ($this->ruteApi() as $rute) {
            $aksi = $rute->getActionName();

            foreach ($terlarang as $pola) {
                $this->assertStringNotContainsString(
                    $pola, $aksi,
                    "Rute API {$rute->uri()} menunjuk {$aksi} — mobile tak boleh membuat/menyetujui dokumen."
                );
            }
        }
    }

    public function test_seluruh_rute_api_terkunci_sanctum_kecuali_login_dan_register(): void
    {
        // Dua-duanya memang HARUS terbuka: keduanyalah cara mendapat token.
        $terbuka = ['api/login', 'api/register'];

        foreach ($this->ruteApi() as $rute) {
            if (in_array($rute->uri(), $terbuka, true)) {
                continue;
            }

            $this->assertContains(
                'auth:sanctum', $rute->gatherMiddleware(),
                "Rute API {$rute->uri()} terbuka tanpa auth:sanctum."
            );
        }
    }
}
