<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Tahap Management Development — peninjau KEDUA (sistematika penulisan).
 *
 * Yang dikunci di sini adalah bahaya utamanya: **dokumen lolos ke PJO tanpa
 * melewati MD**. Kalau itu terjadi tanpa test, tak seorang pun akan sadar —
 * dokumen tetap terbit, hanya saja satu tahap mutu diam-diam terlewat.
 */
class MdReviewStageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_konfigurasi_menetapkan_sop_lewat_md_dan_jenis_lain_tidak(): void
    {
        $this->assertSame(['SOP'], config('smartpro.md.jenis_wajib'));
    }

    public function test_sop_wajib_lewat_md_ik_sp_jsa_tidak(): void
    {
        $gl = $this->aktorGl();
        $svc = app(DocumentService::class);

        foreach (['SOP' => true, 'IK' => false, 'SP' => false, 'JSA' => false] as $kode => $harus) {
            /*
            | Dokumennya DIBUAT di sini, bukan dicari dari basis data. Dulu
            | barisnya `Document::whereHas('type', …)->first()` dan hijau hanya
            | selama basis data uji kebetulan memuat keempat jenis — persis
            | kegagalan yang tercatat di PLAN-AKSES-v8 §11 ("dokumen contoh IK
            | tak ada"). Yang diuji aturan per JENIS, jadi jenisnya harus
            | dipastikan ada, bukan diharap ada.
            */
            $dok = $svc->createDraft(
                $gl, DocumentType::where('code', $kode)->firstOrFail(), $gl->department, "Uji Tahap MD {$kode}"
            );

            $this->assertSame(
                $harus,
                $dok->perluTinjauanMd(),
                "Jenis {$kode} salah dinilai soal keharusan melewati MD."
            );
        }
    }

    public function test_akun_md_ada_dan_izinnya_tepat(): void
    {
        $md = User::peninjauMd()->get();

        $this->assertNotEmpty($md, 'Tak ada akun MD — dokumen SOP akan tersangkut di verifikasi_md.');

        foreach ($md as $u) {
            $this->assertTrue($u->can('document.review_md'));

            // MD MENJEMBATANI ke PJO, bukan menyetujui. Kalau ia punya izin
            // approve, ia bisa mengesahkan dokumen sendiri dan melangkahi PJO.
            $this->assertFalse($u->can('document.approve'), 'MD tidak boleh punya izin menyetujui.');

            // MD bukan peninjau tahap pertama; ia tak boleh masuk antrean SH/DH.
            $this->assertFalse($u->can('document.review'), 'MD tidak boleh punya izin peninjauan tahap pertama.');

            // MD tidak menyusun dokumen.
            $this->assertFalse($u->can('document.create'), 'MD tidak boleh membuat dokumen.');
        }
    }

    public function test_peninjau_tahap_pertama_tidak_kebagian_izin_md(): void
    {
        // Kelima peran di luar MD. Dikunci per PERAN, bukan per NRP: yang
        // dijaga adalah "izin MD tidak bocor ke peran lain".
        $peran = [
            'Section Head' => $this->aktorSh(),
            'Departemen Head' => $this->aktorDh(),
            'Group Leader' => $this->aktorGl(),
            'Pimpinan/PJO' => $this->aktorPjo(),
            'Non-Staff' => $this->aktorNonStaff(),
        ];

        foreach ($peran as $nama => $u) {
            $this->assertFalse(
                $u->can('document.review_md'),
                "{$nama} seharusnya TIDAK punya izin peninjauan MD."
            );
        }
    }

    /** Dokumen yang sedang berada di tahap MD; dikembalikan status semulanya oleh transaksi test. */
    private function dokumenDiTahapMd(): Document
    {
        $dok = Document::whereHas('type', fn ($q) => $q->where('code', 'SOP'))->firstOrFail();
        $dok->update(['status' => 'verifikasi_md']);

        return $dok;
    }

    public function test_halaman_tinjau_md_memakai_tampilan_yang_sama_dengan_sh(): void
    {
        $res = $this->actingAs(User::peninjauMd()->firstOrFail())
            ->get(route('review.md.show', $this->dokumenDiTahapMd()));

        /*
        | Sejak Fase 10 layar ini Inertia: kotak catatannya dirakit
        | `components/tinjau/SeksiTinjau.tsx` dari `schema` + `tipeDitinjau`,
        | jadi yang membuktikan "tampilan yang SAMA dengan SH" adalah komponen
        | halaman yang sama persis + props yang sama, bukan markupnya.
        */
        $res->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                ->has('schema.steps')
                ->has('contentMap')
                ->has('tipeDitinjau')
                ->where('labelLolos', 'Loloskan ke PJO')
                ->where('labelTolak', 'Kembalikan untuk Perbaikan')
            );
    }

    public function test_panel_ai_hilang_saat_admin_mematikannya(): void
    {
        $md = User::peninjauMd()->firstOrFail();
        $dok = $this->dokumenDiTahapMd();

        // Menyala → panel tampil. `aiUrl` adalah SATU-SATUNYA saklarnya: TSX
        // tak merender `PanelAi` sama sekali bila ia null.
        $md->update(['ai_review_enabled' => true]);
        $this->actingAs($md)->get(route('review.md.show', $dok))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('aiUrl', route('review.md.ai', $dok))
            );

        // Dimatikan → panel HILANG, tapi kotak catatan & tombol keputusan tetap.
        $md->update(['ai_review_enabled' => false]);
        $this->actingAs($md)->get(route('review.md.show', $dok))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('aiUrl', null)
                ->has('schema.steps')
                ->where('labelLolos', 'Loloskan ke PJO')
            );
    }

    public function test_endpoint_ai_md_ditolak_saat_saklarnya_mati(): void
    {
        $md = User::peninjauMd()->firstOrFail();
        $dok = $this->dokumenDiTahapMd();

        // Menyembunyikan tombol BUKAN pengamanan — alamatnya tetap bisa
        // dipanggil langsung, jadi penjagaannya harus ada di server.
        $md->update(['ai_review_enabled' => false]);
        $this->actingAs($md)->post(route('review.md.ai', $dok))->assertForbidden();
    }

    public function test_halaman_tinjau_sh_tetap_menampilkan_panel_ai(): void
    {
        // Penyatuan view tak boleh mengubah perilaku tahap pertama.
        $dok = Document::whereHas('type', fn ($q) => $q->where('code', 'SOP'))->firstOrFail();
        $dok->update(['status' => 'in_review', 'reviewer_id' => $this->aktorSh()->id]);

        $this->actingAs($this->aktorSh())
            ->get(route('review.show', $dok))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('aiUrl', route('review.ai', $dok))
                // Tahap SH/DH tak mengirim label khusus — TSX jatuh ke
                // "Loloskan"/"Kembalikan untuk Revisi" bawaannya.
                ->missing('labelLolos')
            );
    }

    /**
     * Halaman persetujuan PJO hanya memuat status `pending_approval`; dokumen
     * yang masih di MD tak boleh bocor ke sana.
     *
     * Yang dicari adalah TAUTAN BARIS antreannya, bukan nomor dokumennya.
     * Nomor itu muncul juga di dropdown lonceng ("Dokumen X perlu disetujui")
     * yang ikut dirender layout di SETIAP halaman, jadi `assertDontSee($nomor)`
     * dulu merah karena notifikasi lama — bukan karena antreannya bocor.
     * Tautan `approvals/{id}` hanya lahir dari baris antrean.
     */
    public function test_pjo_tidak_melihat_dokumen_yang_masih_di_md(): void
    {
        $pjo = $this->aktorPjo();
        $dok = Document::whereHas('type', fn ($q) => $q->where('code', 'SOP'))->first();

        // Sejak Fase 10 tautan barisnya dirakit Ziggy di peramban, jadi yang
        // dibaca adalah ID di dalam paginator — satu tingkat lebih dekat ke
        // kueri antreannya daripada markup.
        $idAntrean = fn () => collect($this->propsInertia(
            $this->actingAs($pjo)->get(route('approvals.index'))->assertOk()
        )['documents']['data'])->pluck('id')->all();

        $asli = $dok->status;

        // Kendali POSITIF lebih dulu: kalau pembacanya sendiri tak pernah
        // menemukan apa pun, test ini akan hijau selamanya tanpa menguji apa pun.
        $dok->update(['status' => 'pending_approval']);
        $this->assertContains($dok->id, $idAntrean());

        $dok->update(['status' => 'verifikasi_md']);
        $this->assertNotContains($dok->id, $idAntrean());

        $dok->update(['status' => $asli]);
    }
}
