<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Titik penanda pada menu INDUK sidebar (PLAN-AKSES-v7 Fase 2).
 *
 * Lencana angka hanya menempel di SUB-menu, jadi menu induk yang tertutup dulu
 * tak memberi isyarat apa pun bahwa ada pekerjaan di dalamnya. Yang dikunci di
 * sini SATU janji: titiknya muncul persis ketika sub-menunya berangka, dan
 * lenyap ketika tidak — bukan bentuk, warna, atau posisinya.
 *
 * SELURUH data dibuat di dalam transaksi, termasuk DEPARTEMENNYA. Itu bukan
 * kerapian: suite ini berjalan di atas basis data pengembangan yang memuat
 * masukan lapangan sungguhan, sehingga seorang SH di departemen yang sudah ada
 * bisa saja mewarisi antrean orang lain — dan kasus "tanpa antrean" jadi
 * mustahil diuji. Departemen baru menjamin mejanya benar-benar kosong.
 */
class MenuTitikTest extends TestCase
{
    use DatabaseTransactions;

    private function dept(): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create(['code' => 'UJI'.$tanda, 'name' => 'Departemen Uji '.$tanda]);
    }

    private function orang(string $jabatan, Department $dept): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$jabatan.' '.$tanda,
            'nrp' => 'TTK-'.$tanda,
            'jabatan' => $jabatan,
            'department_id' => $dept->id,
            'password' => bcrypt('x'),
            'status' => 'active',
        ]);
        $u->assignRole($jabatan);

        return $u->fresh();
    }

    /** Satu masukan lapangan yang BELUM ditindak atas dokumen Berlaku di $dept. */
    private function masukan(Department $dept): DocumentFeedback
    {
        $gl = $this->orang('group_leader', $dept);
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Uji Titik'
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return DocumentFeedback::create([
            'feedback_number' => 'MSK-TITIK-'.$doc->id,
            'document_id' => $doc->id,
            'user_id' => $this->orang(User::JABATAN_STAFF, $dept)->id,
            'isi' => 'Langkah 3 tidak sesuai kondisi lapangan.',
            'status' => 'baru',
        ]);
    }

    public function test_menu_induk_bertitik_saat_sub_menunya_berangka(): void
    {
        $dept = $this->dept();
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);

        // Meja kosong dulu: tak ada titik yang boleh muncul.
        $this->assertFalse($this->adaTitik($sh), 'meja kosong tak boleh bertitik');

        $this->masukan($dept);

        // Masukan masuk → "Log Dokumen" (menu induknya) bertitik, meski
        // menunya sedang tertutup dan angkanya ada di sub-menu.
        $this->assertTrue($this->adaTitik($sh), 'menu induk harus bertitik');
    }

    /**
     * Ada menu induk bertitik?
     *
     * Dulu `assertSee('class="pp-titik"')` pada markup Blade. Sejak Fase 8
     * `documents.published` halaman Inertia dan sidebarnya datang sebagai props
     * `navigation` (NavigasiSidebar) — yang diperiksa TETAP sama, kunci `dot`
     * pada item menunya, bukan kelas CSS yang menggambarnya.
     */
    private function adaTitik(User $sebagai): bool
    {
        $res = $this->actingAs($sebagai)->get(route('documents.published'))->assertOk();

        return collect($this->menuSidebar($res))->contains(fn (array $m) => $m['dot']);
    }

    /**
     * Titik lenyap begitu masukannya ditindak.
     *
     * Ini yang membedakan titik dari hiasan: ia membaca antrean yang SAMA
     * dengan lencana angka (AntreanTugas), bukan "pernah ada sesuatu di sini".
     */
    public function test_titik_lenyap_setelah_masukan_ditindak(): void
    {
        $dept = $this->dept();
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);
        $masukan = $this->masukan($dept);

        $this->assertTrue($this->adaTitik($sh));

        $masukan->update(['status' => 'ditolak']);   // = sudah ditindak, keluar antrean

        $this->assertFalse($this->adaTitik($sh));
    }
}
