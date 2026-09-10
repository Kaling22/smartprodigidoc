<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\DocumentWizard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pembuat tambahan (v3 rev): hanya **GL & Non-Staff dari departemen yang SAMA**
 * dengan dokumen. SH/DH/PJO dikecualikan karena mereka peninjau/penyetuju —
 * tak boleh merangkap penyusun dokumen yang kelak mereka nilai sendiri.
 *
 * Aturannya dipusatkan di DocumentWizard::coAuthorCandidates() dan dipakai
 * BERSAMA oleh dropdown maupun penyimpanan, sehingga tak mungkin berbeda.
 */
class CoAuthorCandidateTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $jabatan, Department $dept, string $nama): User
    {
        static $n = 0;
        $n++;
        $u = User::create([
            'name' => $nama, 'nrp' => "CA-{$n}", 'jabatan' => $jabatan,
            'department_id' => $dept->id, 'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $u->assignRole($jabatan);

        return $u;
    }

    /** Draft SOP milik GL ICTMD + rombongan kandidat untuk diuji. */
    private function scenario(): array
    {
        $ictmd = Department::where('code', 'ICTMD')->firstOrFail();
        $she = Department::where('code', 'SHE')->firstOrFail();

        $pembuat = $this->makeUser('group_leader', $ictmd, 'Pembuat Utama');
        $doc = app(DocumentService::class)->createDraft(
            $pembuat, DocumentType::where('code', 'SOP')->firstOrFail(), $ictmd, 'SOP Pembuat Tambahan'
        );

        return [$doc, $pembuat, [
            'glSedept' => $this->makeUser('group_leader', $ictmd, 'GL Sedept'),
            'staffSedept' => $this->makeUser('staff', $ictmd, 'Non-Staff Sedept'),
            'shSedept' => $this->makeUser('section_head', $ictmd, 'SH Sedept'),
            'dhSedept' => $this->makeUser('departemen_head', $ictmd, 'DH Sedept'),
            'glDeptLain' => $this->makeUser('group_leader', $she, 'GL Dept Lain'),
            'staffDeptLain' => $this->makeUser('staff', $she, 'Non-Staff Dept Lain'),
        ]];
    }

    /**
     * Daftar kandidat hanya GL & Non-Staff sedepartemen.
     *
     * Diperiksa langsung ke sumbernya, BUKAN lewat HTML halaman: langkah 2
     * memuat beberapa pemilih sekaligus (peninjau/penyetuju), sehingga nama
     * SH/DH memang wajar muncul di sana — memeriksa seluruh HTML akan salah
     * menuduh. Sisi ujung-ke-ujungnya dijaga test penyimpanan di bawah.
     */
    public function test_candidates_are_same_department_gl_and_non_staff_only(): void
    {
        [$doc, $pembuat, $u] = $this->scenario();

        $ids = app(DocumentWizard::class)->coAuthorCandidates($doc)->pluck('id');

        $this->assertTrue($ids->contains($u['glSedept']->id), 'GL sedepartemen jadi kandidat');
        $this->assertTrue($ids->contains($u['staffSedept']->id), 'Non-Staff sedepartemen jadi kandidat');

        $this->assertFalse($ids->contains($u['shSedept']->id), 'SH sedepartemen BUKAN kandidat');
        $this->assertFalse($ids->contains($u['dhSedept']->id), 'DH sedepartemen BUKAN kandidat');
        $this->assertFalse($ids->contains($u['glDeptLain']->id), 'GL departemen lain BUKAN kandidat');
        $this->assertFalse($ids->contains($u['staffDeptLain']->id), 'Non-Staff departemen lain BUKAN kandidat');
        $this->assertFalse($ids->contains($pembuat->id), 'pembuat utama tak jadi kandidat tambahan');
    }

    /** Pemilih "Pembuat Tambahan" tetap tampil di wizard langkah 2. */
    public function test_co_author_picker_is_rendered_on_step_two(): void
    {
        [$doc, $pembuat] = $this->scenario();

        // Untuk draft yang BISA diedit, halaman memakai current_step dokumen
        // (query view_step diabaikan) — jadi majukan dulu ke langkah 2.
        $doc->update(['current_step' => 2]);

        $resp = $this->actingAs($pembuat)->get(route('documents.edit', $doc))->assertOk();

        $seksi = $this->seksiWizard($resp);
        $this->assertArrayHasKey('pembuat_tambahan', $seksi, 'pemilih pembuat tambahan tak ada di langkah 2');
        $this->assertStringContainsString('Pembuat Tambahan', $seksi['pembuat_tambahan']['label']);
        $this->assertTrue($seksi['pembuat_tambahan']['multiple'] ?? false, 'pembuat tambahan boleh lebih dari satu');

        // Kandidatnya datang JADI dari server — nol penyaringan di klien.
        $this->assertArrayHasKey('pembuat_tambahan', $this->propsInertia($resp)['candidates']);
    }

    /**
     * Pertahanan kedua: kiriman POST berisi id TAK SAH dibuang saat disimpan.
     * Menyaring dropdown saja tidak cukup — id bisa dikirim langsung.
     */
    public function test_ineligible_ids_are_rejected_on_save(): void
    {
        [$doc, $pembuat, $u] = $this->scenario();

        $this->actingAs($pembuat)->post(route('documents.saveStep', $doc), [
            'step' => 2,
            'action' => 'save',
            'sections' => ['pembuat_tambahan' => [
                $u['glSedept']->id,        // sah
                $u['staffSedept']->id,     // sah
                $u['shSedept']->id,        // TIDAK sah — peninjau
                $u['dhSedept']->id,        // TIDAK sah — peninjau
                $u['glDeptLain']->id,      // TIDAK sah — departemen lain
                $u['staffDeptLain']->id,   // TIDAK sah — departemen lain
                $pembuat->id,              // TIDAK sah — pembuat utama
            ]],
        ])->assertRedirect();

        $tersimpan = Document::find($doc->id)->authors()->where('is_primary', false)
            ->pluck('user_id')->sort()->values()->all();

        $this->assertSame(
            collect([$u['glSedept']->id, $u['staffSedept']->id])->sort()->values()->all(),
            $tersimpan,
            'hanya GL & Non-Staff sedepartemen yang tersimpan'
        );
    }

    /** Rentang off yang mencakup HARI INI. */
    private function offkan(User $user): void
    {
        $user->offDays()->create([
            'jenis' => 'cuti',
            'mulai' => now()->subDay()->toDateString(),
            'sampai' => now()->addDays(3)->toDateString(),
        ]);
    }

    /**
     * Pembuat tambahan ada untuk SATU tujuan: melanjutkan pengisian bila pembuat
     * utama berhalangan. Cadangan yang sedang cuti bukan cadangan.
     */
    public function test_kandidat_yang_sedang_cuti_tak_ditawarkan(): void
    {
        [$doc, , $u] = $this->scenario();

        $this->offkan($u['glSedept']);

        $ids = app(DocumentWizard::class)->coAuthorCandidates($doc)->pluck('id');

        $this->assertFalse($ids->contains($u['glSedept']->id), 'GL yang sedang cuti tak jadi kandidat baru');
        $this->assertTrue($ids->contains($u['staffSedept']->id), 'yang tidak cuti tetap jadi kandidat');
    }

    /**
     * Yang SUDAH tercatat lalu mengajukan cuti tetap muncul — dan namanya tak
     * boleh hilang saat dokumen disimpan ulang.
     *
     * Ini bukan kelonggaran melainkan keharusan: saveParticipants() menghapus
     * lalu menulis ulang seluruh pembuat tambahan, disaring dengan daftar
     * kandidat. Tanpa pengecualian ini, baris "Dibuat Oleh" di halaman
     * pengesahan lenyap diam-diam pada penyimpanan berikutnya.
     */
    public function test_pembuat_tambahan_yang_sudah_tercatat_tak_hilang_saat_cuti(): void
    {
        [$doc, $pembuat, $u] = $this->scenario();

        // Ditunjuk dulu, SESUDAH itu ia mengajukan cuti.
        $this->actingAs($pembuat)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'save',
            'sections' => ['pembuat_tambahan' => [$u['glSedept']->id]],
        ])->assertRedirect();

        $this->offkan($u['glSedept']);

        $ids = app(DocumentWizard::class)->coAuthorCandidates($doc->refresh())->pluck('id');
        $this->assertTrue($ids->contains($u['glSedept']->id), 'yang sudah tercatat tetap ditawarkan walau cuti');

        // Simpan ulang dengan kiriman yang sama — namanya harus bertahan.
        $this->actingAs($pembuat)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'save',
            'sections' => ['pembuat_tambahan' => [$u['glSedept']->id]],
        ])->assertRedirect();

        $this->assertSame(
            [$u['glSedept']->id],
            Document::find($doc->id)->authors()->where('is_primary', false)->pluck('user_id')->all(),
            'pembuat tambahan yang cuti tetap tersimpan sesudah dokumen disimpan ulang'
        );
    }
}
