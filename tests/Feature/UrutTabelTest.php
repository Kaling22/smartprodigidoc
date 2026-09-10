<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Pengurutan tabel dokumen dari tautan judul kolom.
 *
 * Yang dikunci: urutannya benar-benar BERBALIK (bukan sekadar tautan yang bisa
 * diklik), penyaringan yang sedang aktif TIDAK hilang, dan nama kolom asing
 * dari query string tak pernah masuk ke SQL.
 */
class UrutTabelTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Tiga dokumen Berlaku bernomor berurutan di departemen yang bersih.
     *
     * `published_at`-nya sengaja BERLAWANAN dengan urutan nomornya. Halaman
     * "Dokumen Berlaku" defaultnya `latest('published_at')`, jadi susunan ini
     * membuat urutan bawaan BERTENTANGAN dengan urutan yang diminta pengguna —
     * satu-satunya cara test ini benar-benar membuktikan pilihan penggunalah
     * yang menang. Dulu ketiganya memakai `now()` yang sama, sehingga urutan
     * bawaan tak punya pendapat dan bug-nya lolos sampai ke layar.
     */
    private function tigaBerlaku(): array
    {
        $dept = Department::create([
            'code' => 'URT'.strtoupper(\Illuminate\Support\Str::random(3)),
            'name' => 'Departemen Uji Urut',
        ]);
        $gl = $this->aktorGl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $dibuat = [];
        foreach ([['01', 0, 1], ['02', 3, 5], ['03', 1, 9]] as [$seq, $revisi, $hariLalu]) {
            $doc = app(DocumentService::class)->createDraft($gl, $type, $dept, "SOP Urut {$seq}");
            $doc->update([
                'status' => 'published',
                'published_at' => now()->subDays($hariLalu),
                'updated_at' => now()->subDays($hariLalu),
                'no_revisi' => $revisi,
                'doc_number_final' => "PPA-ADRO-SOP-{$dept->code}-{$seq}",
            ]);
            $dibuat[$seq] = $doc->refresh();
        }

        return [$dept, $dibuat];
    }

    /** Urutan nomor dokumen pada halaman, sesuai kemunculannya di HTML. */
    private function urutanDiHalaman(string $html, array $nomor): array
    {
        $posisi = [];
        foreach ($nomor as $n) {
            $posisi[$n] = strpos($html, $n);
        }
        asort($posisi);

        return array_keys($posisi);
    }

    public function test_nomor_dokumen_bisa_diurutkan_naik_dan_turun(): void
    {
        [$dept, $dibuat] = $this->tigaBerlaku();
        $pjo = $this->aktorPjo();
        $nomor = collect($dibuat)->map->doc_number_final->values()->all();

        $naik = $this->actingAs($pjo)
            ->get(route('documents.published', ['department_id' => $dept->id, 'sort' => 'nomor', 'dir' => 'asc']))
            ->assertOk()->getContent();

        $turun = $this->actingAs($pjo)
            ->get(route('documents.published', ['department_id' => $dept->id, 'sort' => 'nomor', 'dir' => 'desc']))
            ->assertOk()->getContent();

        $this->assertSame($nomor, $this->urutanDiHalaman($naik, $nomor));
        $this->assertSame(array_reverse($nomor), $this->urutanDiHalaman($turun, $nomor));
    }

    /**
     * Revisi diurutkan EDISI dulu baru nomor revisi. Edisi 2 Rev 0 lebih baru
     * daripada Edisi 1 Rev 4 — mengurutkan `no_revisi` sendirian membalik
     * keduanya, dan salahnya tak kelihatan sampai ada dokumen ber-Edisi 2.
     */
    public function test_revisi_diurutkan_edisi_dulu(): void
    {
        [$dept, $dibuat] = $this->tigaBerlaku();
        $pjo = $this->aktorPjo();

        // -01 jadi Edisi 2 Rev 0: paling BARU, meski angka revisinya terkecil.
        $dibuat['01']->update(['edisi' => '2', 'no_revisi' => 0]);
        $dibuat['02']->update(['edisi' => '1', 'no_revisi' => 4]);
        $dibuat['03']->update(['edisi' => '1', 'no_revisi' => 1]);

        $nomor = collect($dibuat)->map->doc_number_final->values()->all();

        $html = $this->actingAs($pjo)
            ->get(route('documents.published', ['department_id' => $dept->id, 'sort' => 'revisi', 'dir' => 'asc']))
            ->assertOk()->getContent();

        $this->assertSame(
            [$dibuat['03']->doc_number_final, $dibuat['02']->doc_number_final, $dibuat['01']->doc_number_final],
            $this->urutanDiHalaman($html, $nomor),
            'urutan harus E1R1 → E1R4 → E2R0'
        );
    }

    /** Menekan judul kolom tak boleh menghapus penyaringan yang sedang aktif. */
    public function test_tautan_urut_membawa_serta_penyaringan(): void
    {
        [$dept] = $this->tigaBerlaku();
        $pjo = $this->aktorPjo();

        $res = $this->actingAs($pjo)
            ->get(route('documents.published', ['department_id' => $dept->id, 'q' => 'SOP Urut', 'sort' => 'nomor', 'dir' => 'asc']))
            ->assertOk();

        /*
        | Tautan judul kolom dirakit `components/DataTable.tsx` dari DUA hal:
        | alamat halaman yang sedang dibuka (`page.url`) dan arah berikutnya. Ia
        | menyalin seluruh query string apa adanya lalu menimpa `sort`/`dir`,
        | jadi yang harus dijamin server adalah alamat itu masih membawa
        | penyaringnya — dan bahwa penyaring itu benar-benar sampai ke halaman.
        |
        | Dulu diperiksa dengan mencari `dir=desc` di markup. Spasi tetap
        | ter-encode `%20`, bukan `+`: Laravel menyusun query string-nya dengan
        | PHP_QUERY_RFC3986.
        */
        $page = $res->viewData('page');

        $this->assertStringContainsString('q=SOP'.rawurlencode(' ').'Urut', $page['url']);
        $this->assertStringContainsString('department_id='.$dept->id, $page['url']);
        $this->assertStringContainsString('sort=nomor', $page['url']);

        $this->assertSame('SOP Urut', $page['props']['filters']['q']);
        $this->assertSame((string) $dept->id, (string) $page['props']['filters']['department_id']);
    }

    /**
     * Nama kolom dari query string TIDAK pernah masuk ke SQL — ia cuma kunci ke
     * Document::KOLOM_URUT. Nilai asing jatuh ke urutan bawaan halaman.
     */
    public function test_kolom_asing_diabaikan_bukan_diteruskan_ke_sql(): void
    {
        $pjo = $this->aktorPjo();

        foreach (['title', 'id; DROP TABLE documents', '', 'doc_number'] as $jahat) {
            $this->actingAs($pjo)
                ->get(route('documents.published', ['sort' => $jahat, 'dir' => 'desc']))
                ->assertOk();
        }

        // Arah selain desc SELALU jadi asc — tak ada jalan lain masuk ke SQL.
        $sql = Document::query()->urut('nomor', 'asc; DROP TABLE documents')->toSql();
        $this->assertStringContainsString('asc', $sql);
        $this->assertStringNotContainsString('DROP', $sql);

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('documents')->whereNull('id')->count(),
            'tabel documents harus masih ada');
    }

    /** Kesembilan halaman berdaftar dokumen memasang tautan urut nomor. */
    public function test_semua_halaman_daftar_memasang_tautan_urut(): void
    {
        /*
        | Aktornya dibungkus closure, bukan disebut NRP-nya: tiap halaman menuntut
        | PERAN tertentu, dan itulah yang relevan di sini. `ADM-0001` & `MD-0001`
        | tetap dicari dari basis data karena keduanya memang masih ada
        | (PLAN-AKSES-v8 §3) — hanya kelima NRP contoh yang pensiun.
        */
        $halaman = [
            'documents.index' => fn () => $this->aktorGl(),
            'documents.published' => fn () => $this->aktorPjo(),
            'documents.obsolete' => fn () => User::where('nrp', 'ADM-0001')->firstOrFail(),
            'documents.revisions' => fn () => $this->aktorGl(),
            'documents.distribution' => fn () => $this->aktorSh(),
            'documents.staffStatus' => fn () => $this->aktorSh(),
            'review.index' => fn () => $this->aktorSh(),
            'review.md' => fn () => User::where('nrp', 'MD-0001')->firstOrFail(),
            'approvals.index' => fn () => $this->aktorPjo(),
        ];

        foreach ($halaman as $rute => $aktor) {
            $res = $this->actingAs($aktor())->get(route($rute))->assertOk();

            // Halaman Blade lama masih menempelkan tautannya sendiri di markup.
            // Halaman Inertia merakitnya di klien dari kolom ber-`urut`, dan
            // itulah yang dikunci test berikutnya.
            if (! str_contains((string) $res->getContent(), 'data-page="app"')) {
                $res->assertSee('sort=nomor', escape: false);
            }
        }
    }

    /**
     * Tiap halaman daftar Inertia memasang kolom ber-`urut`, dan kuncinya SAH.
     *
     * KENAPA memeriksa berkas, bukan halamannya: sejak Fase 8 tautan urut
     * dirakit `components/DataTable.tsx` di peramban, sehingga `assertSee(
     * 'sort=nomor')` tak akan pernah bisa merah lagi — ia hanya jadi hijau
     * trivial. Yang bisa rusak diam-diam justru kuncinya: `urut: 'nomer'` lolos
     * TypeScript, lolos `npm run build`, lolos gerbang render, dan hasilnya
     * sekadar daftar yang berhenti terurut tanpa satu pun galat.
     *
     * Jadi yang diuji di sini: setiap `urut:` yang tertulis di halaman Documents
     * adalah kunci yang benar-benar ada di {@see Document::KOLOM_URUT}, dan tiap
     * halaman berdaftar punya minimal satu.
     */
    public function test_kolom_urut_di_halaman_inertia_memakai_kunci_yang_sah(): void
    {
        $halaman = [
            'Documents/Index', 'Documents/Published', 'Documents/Obsolete',
            'Documents/Revisions', 'Documents/StaffStatus',
            // Fase 10 — antrean tinjau, penulisan, persetujuan, nonaktif.
            'Review/Index', 'Review/Md', 'Approvals/Index', 'Nonaktif/Index',
            // Fase 11 — Distribusi. Cabang `?sumber=informasi` sengaja TIDAK
            // punya kolom urut: barisnya bukan dokumen, jadi `Document::KOLOM_URUT`
            // tak berlaku di sana (di Blade lama pun `_urut-th` tak dipakai).
            'Documents/Distribution',
        ];

        foreach ($halaman as $nama) {
            $berkas = resource_path("js/pages/V2/{$nama}.tsx");
            $this->assertFileExists($berkas);

            preg_match_all("/urut:\s*'([^']+)'/", (string) file_get_contents($berkas), $m);

            $this->assertNotEmpty($m[1], "{$nama}.tsx harus punya minimal satu kolom ber-urut");

            foreach ($m[1] as $kunci) {
                $this->assertArrayHasKey($kunci, Document::KOLOM_URUT,
                    "{$nama}.tsx memakai kunci urut '{$kunci}' yang tak ada di Document::KOLOM_URUT");
            }
        }
    }
}
