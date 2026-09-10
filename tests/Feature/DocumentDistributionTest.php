<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRead;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentDistribution;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * PELACAKAN DISTRIBUSI dokumen Berlaku (v5 Fase C).
 *
 * Dua hal yang dijaga di sini, dan keduanya mudah rusak diam-diam:
 *   1. Pencatatan harus IDEMPOTEN per orang. Kalau tidak, satu orang yang
 *      menyegarkan halaman sepuluh kali akan tampak seperti sepuluh pembaca dan
 *      angka cakupan menjadi bohong ke arah yang menenangkan.
 *   2. Lingkup departemen. Halaman ini menyebut nama orang beserta apa yang
 *      belum mereka baca — bocor lintas departemen berarti bocor data personil.
 */
class DocumentDistributionTest extends TestCase
{
    use DatabaseTransactions;

    /** Dokumen BERLAKU milik departemen si GL. */
    private function dokumenBerlaku(): Document
    {
        $gl = $this->aktorGl();

        $d = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Distribusi'
        );
        $d->forceFill(['status' => 'published', 'published_at' => now()])->save();

        return $d->fresh();
    }

    /**
     * Pengguna aktif sedepartemen selain pembuatnya — dan bukan Admin IT.
     *
     * Definisinya sengaja MENIRU `sasaran()` lewat scope yang sama: helper yang
     * memilih orang di luar himpunan sasaran akan menghasilkan test yang merah
     * padahal kodenya benar (Admin IT beridentitas 1, jadi `orderBy('id')`
     * dulu selalu memilihnya lebih dahulu).
     */
    private function pembaca(Document $d, int $lewati = 0): User
    {
        return User::sasaranDistribusi()
            ->where('department_id', $d->department_id)
            ->where('id', '!=', $d->created_by)
            ->orderBy('id')->skip($lewati)->firstOrFail();
    }

    /** Admin IT, dipindahkan ke departemen dokumen (ikut di-rollback transaksi test). */
    private function adminSedepartemen(Document $d): User
    {
        $admin = User::where('nrp', 'ADM-0001')->firstOrFail();
        $admin->forceFill(['department_id' => $d->department_id])->save();

        return $admin;
    }

    public function test_catat_idempoten_per_pengguna(): void
    {
        $d = $this->dokumenBerlaku();
        $u = $this->pembaca($d);
        $svc = app(DocumentDistribution::class);

        $svc->catat($d, $u);
        $svc->catat($d, $u);
        $svc->catat($d, $u, unduh: true);

        $baris = DocumentRead::where('document_id', $d->id)->get();

        $this->assertCount(1, $baris, 'tiga kunjungan satu orang = satu baris');
        $this->assertSame(3, $baris[0]->read_count);
        $this->assertSame(1, $baris[0]->download_count, 'hanya kunjungan PDF yang dihitung unduhan');
    }

    public function test_first_read_at_tak_pernah_bergeser(): void
    {
        $d = $this->dokumenBerlaku();
        $u = $this->pembaca($d);
        $svc = app(DocumentDistribution::class);

        $this->travelTo(now()->subDays(3));
        $svc->catat($d, $u);
        $pertama = DocumentRead::where('document_id', $d->id)->value('first_read_at');

        $this->travelBack();
        $svc->catat($d, $u);
        $baris = DocumentRead::where('document_id', $d->id)->first();

        $this->assertEquals($pertama, $baris->first_read_at, 'first_read_at hanya ditulis sekali');
        $this->assertTrue($baris->last_read_at->gt($baris->first_read_at));
    }

    public function test_draft_dan_pembuat_sendiri_tidak_dicatat(): void
    {
        $gl = $this->aktorGl();
        $draft = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Draft'
        );
        $svc = app(DocumentDistribution::class);

        // Draft: belum layak didistribusikan.
        $svc->catat($draft, $this->pembaca($draft));
        $this->assertSame(0, DocumentRead::where('document_id', $draft->id)->count());

        // Pembuat membaca karyanya sendiri: bukan bukti distribusi.
        $berlaku = $this->dokumenBerlaku();
        $svc->catat($berlaku, $berlaku->creator);
        $this->assertSame(0, DocumentRead::where('document_id', $berlaku->id)->count());
    }

    public function test_cakupan_menghitung_orang_bukan_kunjungan(): void
    {
        $d = $this->dokumenBerlaku();
        $svc = app(DocumentDistribution::class);

        $a = $this->pembaca($d, 0);
        $svc->catat($d, $a);
        $svc->catat($d, $a, unduh: true);
        $svc->catat($d, $this->pembaca($d, 1));

        $c = $svc->cakupanSatu($d);
        $sasaran = $svc->sasaran($d)->count();

        $this->assertSame(2, $c['pembaca'], 'dua ORANG, walau kunjungannya tiga');
        $this->assertSame($sasaran, $c['sasaran']);
        $this->assertSame((int) round(2 / $sasaran * 100), $c['persen']);
        $this->assertSame(1, $c['unduhan']);
    }

    public function test_sasaran_mengecualikan_pembuat_dan_departemen_lain(): void
    {
        $d = $this->dokumenBerlaku();
        $sasaran = app(DocumentDistribution::class)->sasaran($d);

        $this->assertNotContains($d->created_by, $sasaran->pluck('id')->all());
        $this->assertTrue(
            $sasaran->every(fn (User $u) => $u->department_id === $d->department_id),
            'sasaran tak boleh melintasi departemen'
        );
        $this->assertTrue($sasaran->every(fn (User $u) => $u->status === 'active'));
    }

    /**
     * Admin IT bukan sasaran distribusi, walau ia terdaftar di departemen
     * dokumen. Kalau ia terhitung, tiap departemen tampak tertinggal satu orang
     * selamanya dan tak ada satu pun gejala yang memperlihatkan sebabnya.
     */
    public function test_admin_it_bukan_sasaran(): void
    {
        $d = $this->dokumenBerlaku();
        $admin = $this->adminSedepartemen($d);
        $svc = app(DocumentDistribution::class);

        $this->assertNotContains($admin->id, $svc->sasaran($d)->pluck('id')->all());
        $this->assertNotContains($admin->id, $svc->rincian($d)['belum']->pluck('id')->all());
    }

    /**
     * Bacaan Admin IT tak menaikkan pembilang — kalau ia naik sementara Admin
     * tak ada di penyebutnya, yang muncul adalah "9 dari 8 orang".
     *
     * Barisnya TETAP tercatat: penyaringnya di sisi BACA, bukan di catat(),
     * supaya baris yang telanjur ada ikut beres tanpa menghapus data.
     */
    public function test_bacaan_admin_it_tak_menaikkan_angka(): void
    {
        $d = $this->dokumenBerlaku();
        $admin = $this->adminSedepartemen($d);
        $svc = app(DocumentDistribution::class);

        $sebelum = $svc->cakupanSatu($d);
        $svc->catat($d, $admin);
        $sesudah = $svc->cakupanSatu($d);

        $this->assertSame($sebelum['pembaca'], $sesudah['pembaca'], 'Admin IT bukan pembaca yang dihitung');
        $this->assertSame($sebelum['sasaran'], $sesudah['sasaran']);
        $this->assertNotContains($admin->id, $svc->rincian($d)['sudah']->pluck('user_id')->all());
        $this->assertSame(
            1,
            DocumentRead::where('document_id', $d->id)->where('user_id', $admin->id)->count(),
            'barisnya tetap ada — yang disaring hanya pembacaannya'
        );
    }

    /**
     * Pembuat yang berada DI LUAR departemen dokumen tak lagi mengurangi
     * penyebut. Pengurangan `-1` tanpa syarat dulu tetap berjalan walau tak ada
     * apa pun untuk dikurangi, dan hasilnya cuma persentase yang salah diam-diam.
     */
    public function test_pembuat_di_luar_departemen_tak_mengurangi_sasaran(): void
    {
        $d = $this->dokumenBerlaku();
        $svc = app(DocumentDistribution::class);

        // Pembuatnya pindah departemen — dokumennya tetap milik departemen lama.
        $lain = Department::where('id', '!=', $d->department_id)->firstOrFail();
        User::whereKey($d->created_by)->update(['department_id' => $lain->id]);

        $this->assertSame(
            $svc->sasaran($d)->count(),
            $svc->cakupanSatu($d)['sasaran'],
            'penyebut cakupan() harus selalu sama dengan jumlah sasaran()'
        );
    }

    public function test_belum_membaca_menyusut_saat_orang_membaca(): void
    {
        $d = $this->dokumenBerlaku();
        $svc = app(DocumentDistribution::class);
        $u = $this->pembaca($d);

        $sebelum = $svc->rincian($d);
        $this->assertContains($u->id, $sebelum['belum']->pluck('id')->all());

        $svc->catat($d, $u);

        $sesudah = $svc->rincian($d);
        $this->assertNotContains($u->id, $sesudah['belum']->pluck('id')->all());
        $this->assertContains($u->id, $sesudah['sudah']->pluck('user_id')->all());
    }

    public function test_membuka_pdf_tercatat_sebagai_unduhan(): void
    {
        $d = $this->dokumenBerlaku();
        $u = $this->pembaca($d);

        $this->actingAs($u)->get(route('documents.pdf', $d))->assertOk();

        $baris = DocumentRead::where('document_id', $d->id)->where('user_id', $u->id)->first();
        $this->assertNotNull($baris);
        $this->assertSame(1, $baris->download_count);
        $this->assertSame('web', $baris->platform);
    }

    public function test_membuka_detail_dokumen_tercatat_tanpa_unduhan(): void
    {
        $d = $this->dokumenBerlaku();
        $u = $this->pembaca($d);

        $this->actingAs($u)->get(route('documents.show', $d))->assertOk();

        $baris = DocumentRead::where('document_id', $d->id)->where('user_id', $u->id)->first();
        $this->assertNotNull($baris);
        $this->assertSame(0, $baris->download_count, 'buka detail ≠ membaca isi');
    }

    public function test_menu_distribusi_tertutup_bagi_yang_tak_berwenang(): void
    {
        // GL membuat dokumen tapi tak berwenang menegur siapa pun soal cakupan.
        $gl = $this->aktorGl();

        $this->actingAs($gl)->get(route('documents.distribution'))->assertForbidden();
    }

    public function test_sh_hanya_melihat_dokumen_departemennya(): void
    {
        $sh = $this->aktorSh();
        $lain = Document::berlaku()->where('department_id', '!=', $sh->department_id)->first();

        $respons = $this->actingAs($sh)->get(route('documents.distribution'))->assertOk();

        if ($lain) {
            $respons->assertDontSee($lain->displayNumber(), false);
        }
    }

    public function test_widget_dashboard_muncul_untuk_sh_dan_menaut_ke_menu(): void
    {
        $this->dokumenBerlaku();
        $sh = $this->aktorSh();

        // Kartunya ada DAN tombol "Lihat semua"-nya menyala — keduanya kini
        // dijawab props, bukan disimpulkan dari ada-tidaknya URL di HTML.
        $widget = $this->propsInertia(
            $this->actingAs($sh)->get(route('dashboard'))->assertOk()
        )['distribusiWidget'];

        $this->assertNotNull($widget['mutu']);
        $this->assertTrue($widget['bolehBukaHalaman']);
        $this->assertSame(route('documents.distribution'), $widget['urlMutu']);
    }

    public function test_panel_distribusi_hanya_untuk_yang_berwenang(): void
    {
        $d = $this->dokumenBerlaku();
        $sh = $this->aktorSh();

        // Non-Staff sedepartemen: boleh membaca dokumennya, TIDAK boleh melihat
        // siapa saja yang belum membacanya.
        $nonStaff = User::where('status', 'active')
            ->where('department_id', $d->department_id)
            ->where('jabatan', User::JABATAN_STAFF)->first();

        // Dulu `assertSee('Belum Membaca')` pada markup Blade. Judul kolomnya
        // kini dirangkai TSX, jadi yang diperiksa props yang menentukan panelnya
        // dirender atau tidak — `null` = kartunya tak ada sama sekali.
        if ($nonStaff) {
            $props = $this->propsInertia(
                $this->actingAs($nonStaff)->get(route('documents.show', $d))->assertOk()
            );
            $this->assertNull($props['distribusi']);
            $this->assertNull($props['distribusiRincian']);
        }

        if ($sh->department_id === $d->department_id) {
            $props = $this->propsInertia(
                $this->actingAs($sh)->get(route('documents.show', $d))->assertOk()
            );
            $this->assertNotNull($props['distribusi']);
            $this->assertArrayHasKey('belum', $props['distribusiRincian']);
        }
    }

    /**
     * Pratinjau PDF di halaman Persetujuan PJO (rencana pra-produksi Fase 9)
     * tak boleh menggelembungkan angka unduhan.
     *
     * Iframe-nya menembak `documents.pdf` tiap kali halaman dibuka, dan
     * `DocumentPdfController::pdf()` memanggil `catat(..., unduh: true)` pada
     * SETIAP hit. Yang menahannya bukan parameter baru melainkan syarat status
     * di `catat()` sendiri: hanya `published`/`sedang_direvisi` yang dilacak,
     * sedangkan layar persetujuan cuma bisa dibuka untuk `pending_approval`.
     *
     * Tesnya memaku syarat itu, bukan halamannya — kalau suatu saat
     * `pending_approval` ikut dilacak, angka distribusi mulai menghitung
     * pembukaan halaman persetujuan dan tak ada gerbang lain yang berbunyi.
     */
    public function test_pratinjau_persetujuan_tak_menambah_unduhan(): void
    {
        $gl = $this->aktorGl();
        $pjo = $this->aktorPjo();

        $d = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Pratinjau PJO'
        );
        $d->forceFill(['status' => 'pending_approval', 'approver_id' => $pjo->id])->save();
        $d = $d->fresh();

        $this->actingAs($pjo)->get(route('approvals.show', $d))->assertOk();

        app(DocumentDistribution::class)->catat($d, $pjo, unduh: true);

        $this->assertSame(0, DocumentRead::where('document_id', $d->id)->count());
    }
}
