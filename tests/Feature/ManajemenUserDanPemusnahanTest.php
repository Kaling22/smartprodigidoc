<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Fase 5 (ubah peran & hapus user) + Fase 6 (musnahkan dokumen).
 *
 * Keduanya jalur DESTRUKTIF: yang dijaga di sini bukan "fiturnya jalan",
 * melainkan penjaga-penjaganya benar-benar menolak.
 */
class ManajemenUserDanPemusnahanTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::where('nrp', 'ADM-0001')->firstOrFail();
    }

    /** Peran & jabatan WAJIB bergerak bersama — dua kolom kebenaran, nol penegak di DB. */
    public function test_ubah_peran_menyelaraskan_jabatan_dan_role(): void
    {
        $ns = $this->peninjauBersih($this->aktorNonStaff());
        $this->assertSame(User::JABATAN_STAFF, $ns->jabatan);

        $this->actingAs($this->admin())
            ->put(route('users.update', $ns), [
                'role' => Roles::ROLE_GROUP_LEADER,
                'department_id' => $ns->department_id,
                'jabatan_diajukan' => 'Group Leader ICT',
            ])
            ->assertRedirect(route('users.index'));

        $ns->refresh();
        $this->assertSame(User::JABATAN_GROUP_LEADER, $ns->jabatan);
        $this->assertSame('Group Leader ICT', $ns->jabatan_diajukan);
        $this->assertTrue($ns->hasRole(Roles::ROLE_GROUP_LEADER));
        $this->assertFalse($ns->hasRole(Roles::ROLE_STAFF), 'peran lama harus dilepas, bukan ditumpuk');
    }

    /** Admin dapat menetapkan nama jabatan cetak tanpa mengubah kunci peran. */
    public function test_buat_akun_menyimpan_jabatan_diajukan_terpisah_dari_peran(): void
    {
        $tanda = Str::upper(Str::random(8));
        $dept = $this->aktorGl()->department;

        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'GL Uji '.$tanda,
                'nrp' => 'GL-UJI-'.$tanda,
                'jabatan_diajukan' => 'Group Leader ICT',
                'nomor_hp' => null,
                'email' => null,
                'department_id' => $dept->id,
                'role' => Roles::ROLE_GROUP_LEADER,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('nrp', 'GL-UJI-'.$tanda)->firstOrFail();
        $this->assertSame(User::JABATAN_GROUP_LEADER, $user->jabatan);
        $this->assertSame('Group Leader ICT', $user->jabatan_diajukan);
    }

    /** MD & Admin bukan jabatan alur dokumen — jabatannya NULL, bukan nama perannya. */
    public function test_peran_md_menghasilkan_jabatan_null(): void
    {
        $ns = $this->peninjauBersih($this->aktorNonStaff());

        $this->actingAs($this->admin())
            ->put(route('users.update', $ns), ['role' => Roles::ROLE_MD, 'department_id' => $ns->department_id]);

        $ns->refresh();
        $this->assertNull($ns->jabatan, 'jabatan "management_development" tak dikenal JABATAN_LABELS');
        $this->assertTrue($ns->hasRole(Roles::ROLE_MD));
    }

    public function test_admin_tak_bisa_mengubah_peran_dirinya_sendiri(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), ['role' => Roles::ROLE_STAFF, 'department_id' => null])
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->hasRole(Roles::ROLE_ADMIN));
    }

    /**
     * created_by/reviewer_id/approver_id cuma INDEX tanpa FK, jadi database diam
     * saja — tapi relasi creator() ikut global scope SoftDeletes dan jadi null,
     * lalu halaman pengesahan pecah saat memanggil ->name.
     */
    public function test_user_yang_punya_dokumen_tak_bisa_dihapus(): void
    {
        $gl = $this->aktorGl();

        $this->actingAs($this->admin())->delete(route('users.destroy', $gl))->assertRedirect();

        $this->assertNotNull($gl->fresh(), 'user pemilik dokumen harus tetap ada');
    }

    public function test_user_bersih_bisa_dihapus(): void
    {
        $bersih = $this->peninjauBersih($this->aktorNonStaff());

        $this->actingAs($this->admin())->delete(route('users.destroy', $bersih));

        $this->assertNull(User::find($bersih->id));
    }

    // ---------- Fase 6 ----------

    private function dokumenUji(): Document
    {
        $gl = $this->aktorGl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'Dokumen Uji Musnah'
        );
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Uji']]);

        return $doc->fresh();
    }

    public function test_musnahkan_menghapus_dokumen_beserta_isinya(): void
    {
        $doc = $this->dokumenUji();
        $id = $doc->id;

        $this->actingAs($this->admin())
            ->delete(route('documents.purge', $doc), [
                'alasan' => 'Dokumen ganda hasil kesalahan input.',
                'konfirmasi_nomor' => $doc->displayNumber(),
            ])
            ->assertRedirect(route('documents.published'));

        $this->assertNull(Document::withTrashed()->find($id), 'baris dokumen harus benar-benar hilang');
        $this->assertSame(0, \DB::table('document_contents')->where('document_id', $id)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.purge']);
    }

    public function test_nomor_konfirmasi_salah_membatalkan_pemusnahan(): void
    {
        $doc = $this->dokumenUji();

        $this->actingAs($this->admin())
            ->delete(route('documents.purge', $doc), [
                'alasan' => 'Alasan yang cukup panjang.',
                'konfirmasi_nomor' => 'PPA-ADRO-SALAH-99',
            ])
            ->assertSessionHasErrors('konfirmasi_nomor');

        $this->assertNotNull(Document::find($doc->id), 'dokumen harus selamat');
    }

    /** revises_document_id ber-SET NULL: memusnahkan induknya meninggalkan draft yatim. */
    public function test_dokumen_yang_sedang_direvisi_ditolak(): void
    {
        $doc = $this->dokumenUji();
        $doc->update(['status' => 'sedang_direvisi']);

        $this->actingAs($this->admin())
            ->delete(route('documents.purge', $doc), [
                'alasan' => 'Mencoba memusnahkan yang sedang direvisi.',
                'konfirmasi_nomor' => $doc->displayNumber(),
            ])
            ->assertSessionHas('error');

        $this->assertNotNull(Document::find($doc->id));
    }

    /**
     * Versi lama yang sudah digantikan revisi BERLAKU tetap memegang
     * `revises_document_id` penerusnya selamanya. Tanpa saringan status pada
     * penjaga, setiap versi lama akan ditolak dengan alasan "sedang direvisi"
     * dan hanya bisa dimusnahkan lewat CLI (PLAN C §C1).
     */
    public function test_versi_lama_yang_revisinya_sudah_terbit_bisa_dimusnahkan(): void
    {
        $lama = $this->dokumenUji();
        $lama->update(['status' => 'obsolete', 'obsolete_reason' => 'dinonaktifkan']);

        $penerus = $this->dokumenUji();
        $penerus->update(['status' => 'published', 'revises_document_id' => $lama->id]);

        $this->actingAs($this->admin())
            ->delete(route('documents.purge', $lama), [
                'alasan' => 'Versi lama sudah digantikan revisi yang berlaku.',
                'konfirmasi_nomor' => $lama->displayNumber(),
            ])
            ->assertRedirect(route('documents.published'));

        $this->assertNull(Document::withTrashed()->find($lama->id));
        $this->assertNotNull(Document::find($penerus->id), 'penerusnya harus selamat');
    }

    public function test_non_admin_tak_boleh_memusnahkan(): void
    {
        $doc = $this->dokumenUji();
        $sh = $this->aktorSh();

        $this->actingAs($sh)
            ->delete(route('documents.purge', $doc), [
                'alasan' => 'Bukan wewenang saya.',
                'konfirmasi_nomor' => $doc->displayNumber(),
            ])
            ->assertForbidden();

        $this->assertNotNull(Document::find($doc->id));
    }

    // ===== Bersihkan Seluruh Dokumen (PLAN C §C4) =====

    /**
     * "Tanpa terkecuali" diuji secara harfiah: dokumen biasa, dokumen yang
     * SEDANG DIREVISI (ditolak pemusnahan satuan), dan dokumen yang sudah
     * di-soft-delete — ketiganya harus hilang dalam satu tindakan.
     */
    public function test_bersihkan_semua_menghapus_seluruh_dokumen_tanpa_terkecuali(): void
    {
        $this->dokumenUji();
        $this->dokumenUji()->update(['status' => 'sedang_direvisi']);
        $this->dokumenUji()->delete();

        $jumlahUser = User::count();

        $this->actingAs($this->admin())
            ->delete(route('documents.purgeAll'), [
                'alasan' => 'Mengosongkan data uji sebelum impor arsip lama.',
                'konfirmasi' => 'MUSNAHKAN SEMUA DOKUMEN',
            ])
            // v9 Fase 2b: tombolnya pindah ke Konfigurasi Sistem, jadi ke sanalah
            // pengguna dikembalikan — bukan ke Manajemen User yang tak lagi memuatnya.
            ->assertRedirect(route('pengaturan.sistem'));

        $this->assertSame(0, Document::withTrashed()->count(), 'seluruh dokumen harus hilang, termasuk yang soft-deleted');
        $this->assertSame($jumlahUser, User::count(), 'akun pengguna tak boleh tersentuh');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.purge_all']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.purge']);
    }

    /** Frasa adalah SATU-SATUNYA rem sebelum seluruh arsip hilang. */
    public function test_frasa_salah_membatalkan_pembersihan(): void
    {
        $doc = $this->dokumenUji();

        $this->actingAs($this->admin())
            ->delete(route('documents.purgeAll'), [
                'alasan' => 'Alasan yang cukup panjang.',
                'konfirmasi' => 'musnahkan semua dokumen',
            ])
            ->assertSessionHasErrors('konfirmasi');

        $this->assertNotNull(Document::find($doc->id), 'tak satu pun dokumen boleh hilang');
    }

    public function test_non_admin_tak_boleh_membersihkan_semua(): void
    {
        $doc = $this->dokumenUji();
        $sh = $this->aktorSh();

        $this->actingAs($sh)
            ->delete(route('documents.purgeAll'), [
                'alasan' => 'Bukan wewenang saya.',
                'konfirmasi' => 'MUSNAHKAN SEMUA DOKUMEN',
            ])
            ->assertForbidden();

        $this->assertNotNull(Document::find($doc->id));
    }

    /**
     * v9 Fase 2b — kartunya PINDAH, bukan digandakan.
     *
     * Yang dikunci bukan tata letak melainkan satu hal yang gagal diam-diam:
     * `users/index.blade.php` dulu memakai variabel `$jumlahDokumen` dari
     * controllernya. Kalau blade-nya tertinggal saat controllernya dibersihkan,
     * seluruh Manajemen User berubah jadi halaman galat — dan tak ada satu pun
     * test lama yang menyentuh isi halaman itu untuk menangkapnya.
     */
    public function test_kartu_bersihkan_dokumen_pindah_ke_konfigurasi_sistem(): void
    {
        $this->dokumenUji();
        $admin = $this->admin();

        // Sejak Fase 7 keduanya halaman Inertia: kartunya digambar TSX, jadi
        // yang membuktikan "pindah, bukan digandakan" adalah ANGKANYA — kartu
        // itu mustahil digambar tanpa `kesehatan.dokumen_semua`.
        $users = $this->propsInertia($this->actingAs($admin)->get(route('users.index'))->assertOk());
        $this->assertArrayNotHasKey('kesehatan', $users);
        $this->assertArrayNotHasKey('jumlahDokumen', $users);

        $sistem = $this->actingAs($admin)->get(route('pengaturan.sistem'))->assertOk();
        $sistem->assertInertia(fn (AssertableInertia $page) => $page
            ->component('V2/Pengaturan/Sistem')
            ->has('kesehatan.dokumen_semua'));
    }

    /** Angka yang dijanjikan tombol = angka yang benar-benar terhapus, soft-delete ikut. */
    public function test_angka_zona_berbahaya_menghitung_dokumen_terhapus_juga(): void
    {
        $this->dokumenUji();
        $this->dokumenUji()->delete();

        $semua = Document::withTrashed()->count();

        $this->actingAs($this->admin())->get(route('pengaturan.sistem'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('kesehatan.dokumen_semua', $semua));

        $this->assertGreaterThan(Document::count(), $semua, 'uji ini hampa bila tak ada dokumen soft-deleted');
    }
}
