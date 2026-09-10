<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Persetujuan pendaftaran akun (CLAUDE.md §6: PJO menyetujui akun baru).
 *
 * PJO TIDAK punya departemen, sehingga penyaringan `department_id` lama membuat
 * daftar pendaftar SELALU kosong untuknya. Aturan cakupan kini dipusatkan di
 * {@see User::scopePendingVisibleTo()} agar halaman & kartu dashboard tak
 * mungkin berbeda.
 */
class UserApprovalTest extends TestCase
{
    use DatabaseTransactions;

    /** Pendaftar baru (status pending) di departemen tertentu. */
    private function makePending(string $deptCode, string $nrp = 'REG-9001'): User
    {
        $user = User::create([
            'name' => 'Pendaftar '.$nrp,
            'nrp' => $nrp,
            'jabatan' => User::JABATAN_STAFF,
            'department_id' => Department::where('code', $deptCode)->firstOrFail()->id,
            'password' => Hash::make('rahasia123'),
            'status' => 'pending',
        ]);
        $user->assignRole(Roles::ROLE_STAFF);

        return $user;
    }

    /** PJO (tanpa departemen) melihat & menyetujui pendaftar dari dept mana pun. */
    public function test_pjo_sees_and_approves_pending_accounts_from_any_department(): void
    {
        $pjo = $this->aktorPjo();
        $this->assertNull($pjo->department_id, 'prasyarat: PJO memang tanpa departemen');

        $ict = $this->makePending('ICTMD', 'REG-ICT-1');
        $she = $this->makePending('SHE', 'REG-SHE-1');

        $this->actingAs($pjo)->get(route('users.pending'))
            ->assertOk()
            ->assertSee($ict->name)
            ->assertSee($she->name);

        $this->actingAs($pjo)->post(route('users.approve', $she))->assertRedirect();
        $this->assertSame('active', $she->refresh()->status, 'PJO dapat menyetujui akun');

        $this->actingAs($pjo)->post(route('users.reject', $ict))->assertRedirect();
        $this->assertSame('rejected', $ict->refresh()->status, 'PJO dapat menolak akun');
    }

    /** Kartu antrean dashboard memakai cakupan yang SAMA dgn halamannya. */
    public function test_dashboard_queue_matches_page_scope_for_pjo(): void
    {
        $pjo = $this->aktorPjo();
        $this->makePending('ICTMD', 'REG-ICT-2');
        $this->makePending('SHE', 'REG-SHE-2');

        $this->assertSame(
            User::pendingVisibleTo($pjo)->count(),
            User::where('status', 'pending')->count(),
            'PJO menghitung pendaftar dari seluruh departemen'
        );
        $this->assertGreaterThanOrEqual(2, User::pendingVisibleTo($pjo)->count());
    }

    /** SH tetap dibatasi departemennya sendiri (tak berubah). */
    public function test_section_head_still_scoped_to_own_department(): void
    {
        $sh = $this->aktorSh();   // ICTMD
        $luar = $this->makePending('SHE', 'REG-SHE-3');

        $this->actingAs($sh)->get(route('users.pending'))
            ->assertOk()
            ->assertDontSee($luar->name);

        $this->actingAs($sh)->post(route('users.approve', $luar))->assertForbidden();
        $this->assertSame('pending', $luar->refresh()->status, 'status tak berubah');
    }

    /**
     * Helper jabatan+departemen yang dipakai seluruh kolom pengesahan.
     * Layar memakai bentuk RINGKAS ("GL ICTMD"); dokumen cetak memakai bentuk
     * lengkap ("Group Leader (ICTMD)"). PJO tanpa departemen → tanpa kurung.
     */
    public function test_jabatan_helpers_include_department(): void
    {
        $gl = $this->aktorGl();
        $pjo = $this->aktorPjo();
        $dept = $gl->department->code;

        $this->assertSame("Group Leader ({$dept})", $gl->jabatanWithDepartment());
        $this->assertSame("GL {$dept}", $gl->jabatanShort());
        $this->assertSame($gl->name." - GL {$dept}", $gl->nameWithJabatan());

        // PJO tak punya departemen → tanpa kurung/spasi menggantung.
        $this->assertSame('PJO', $pjo->jabatanWithDepartment());
        $this->assertSame('PJO', $pjo->jabatanShort());
    }

    /** Tanpa isian jabatan: kolom menampilkan LABEL, bukan kunci internal "staff". */
    public function test_pending_page_shows_human_readable_jabatan(): void
    {
        $pjo = $this->aktorPjo();
        $this->makePending('ICTMD', 'REG-ICT-3');

        $this->actingAs($pjo)->get(route('users.pending'))
            ->assertOk()
            ->assertSee('Non-Staff')
            ->assertDontSee('>staff<', false);
    }

    /**
     * Jabatan yang DIKETIK pendaftar terbawa apa adanya sampai halaman
     * persetujuan. Dulu isian ini dibuang (RegisterRequest tak memvalidasinya &
     * RegisterController menimpanya) sehingga "Magang" muncul sebagai "Staff".
     */
    public function test_self_declared_jabatan_survives_registration(): void
    {
        $dept = Department::where('code', 'ICTMD')->firstOrFail();

        // Rute POST /register tak bernama; URI-nya sama dgn route('register').
        $this->post(route('register'), [
            'name' => 'Pendaftar Magang',
            'nrp' => 'REG-MGG-1',
            'jabatan' => 'Magang',
            'nomor_hp' => '08120000000',
            'department_id' => $dept->id,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect();

        $baru = User::where('nrp', 'REG-MGG-1')->firstOrFail();
        $this->assertSame('Magang', $baru->jabatan_diajukan, 'isian pendaftar tersimpan');
        $this->assertSame(User::JABATAN_STAFF, $baru->jabatan, 'peran alur tetap Non-Staff (read-only)');
        $this->assertSame('pending', $baru->status);

        /*
        | Penyetuju melihat isian pendaftar SEKALIGUS hak akses yang akan didapat.
        |
        | Dulu `assertSee('Akses: Non-Staff')` — kalimat itu dirangkai di dalam
        | Blade. Sejak halamannya jadi Inertia (Fase 6) kalimatnya dirangkai di
        | TSX, jadi yang dikirim server adalah DUA nilai terpisah. Yang diperiksa
        | tetap sama: keduanya sampai, dan keduanya berbeda.
        */
        $baris = collect($this->propsInertia(
            $this->actingAs($this->aktorPjo())->get(route('users.pending'))->assertOk()
        )['pendingUsers'])->firstWhere('nrp', 'REG-MGG-1');

        $this->assertSame('Magang', $baris['jabatan_diajukan']);
        $this->assertSame('Non-Staff', $baris['jabatan_label']);
    }
}
