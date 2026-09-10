<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * FASE 11 — Distribusi, rincian informasi, dan dokumen lama (arsip) sebagai
 * halaman Inertia.
 *
 * Yang dijaga di sini bukan rupa halamannya, melainkan empat hal yang bisa
 * rusak tanpa satu pun gerbang lain berbunyi:
 *
 *   1. **Kebocoran props.** Kelas kesalahan yang sama sudah lima fase berturut
 *      turut: `password` (6), kunci API (7), `creator` (8), kandidat wizard (9),
 *      dan tiga koleksi layar tinjau (10). Di sini yang menggendong model User
 *      ada tiga — `creator` tiap dokumen Berlaku, relasi `uploader` tiap
 *      informasi, dan KEDUA sisi rincian pembaca — ditambah satu rahasia yang
 *      bukan User sama sekali: `arsip_path`, jalur berkas privat yang sengaja
 *      tak bisa ditebak dari nomor dokumen.
 *   2. **Otorisasi tetap di server** (pakem P4): GL & Non-Staff tetap 403.
 *   3. **Pengurutan & penyaringan tetap di server** (pakem P5) — daftar ini tak
 *      berhalaman, jadi mengurutkan di klien akan TERLIHAT benar dan tetap
 *      salah begitu ia berhalaman kelak.
 *   4. **Jenis unggahan tidak terlupa** (pakem P6).
 */
class DistribusiInertiaTest extends TestCase
{
    use DatabaseTransactions;

    /** Dokumen BERLAKU milik departemen si GL. */
    private function dokumenBerlaku(string $judul = 'SOP Distribusi Inertia'): Document
    {
        $gl = $this->aktorGl();

        $d = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
        $d->forceFill(['status' => 'published', 'published_at' => now()])->save();

        return $d->fresh();
    }

    private function informasi(): Informasi
    {
        return Informasi::create([
            'nomor' => 'INF-F11-'.Str::upper(Str::random(6)),
            'judul' => 'Poster Distribusi Fase 11',
            'kategori' => 'poster',
            'file_path' => 'informasi/poster/uji.pdf',
            'file_mime' => 'application/pdf',
            'berlaku' => true,
            'uploaded_by' => User::where('nrp', 'ADM-0001')->firstOrFail()->id,
        ]);
    }

    /** PDF sungguhan — FPDI menolak PDF tiruan. */
    private function pdfNyata(int $halaman): string
    {
        $pdf = new \FPDF;
        for ($i = 1; $i <= $halaman; $i++) {
            $pdf->AddPage();
            $pdf->SetFont('Helvetica', '', 24);
            $pdf->Cell(0, 20, "Halaman {$i}");
        }

        return $pdf->Output('S');
    }

    /** Daftarkan dokumen lama dan kembalikan barisnya. */
    private function daftarkanArsip(string $jenis = 'SOP'): Document
    {
        $gl = $this->berprofilPenuh($this->aktorGl());

        $this->actingAs($gl)->post(route('documents.arsip.store'), [
            'document_type_id' => DocumentType::where('code', $jenis)->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Dokumen Lama Fase 11',
            'doc_number' => 'ARSIP-F11-'.Str::upper(Str::random(8)),
            'edisi' => 2,
            'no_revisi' => 3,
            'berkas' => UploadedFile::fake()->createWithContent('lama.pdf', $this->pdfNyata(3)),
        ]);

        return Document::whereNotNull('arsip_path')->latest('id')->firstOrFail();
    }

    // ── Komponen tiap rute ───────────────────────────────────────────────────

    /** Kelima layar Fase 11 memakai komponen Inertia-nya sendiri. */
    public function test_kelima_layar_memakai_komponen_inertia(): void
    {
        Storage::fake('local');
        $this->dokumenBerlaku();
        $info = $this->informasi();
        $arsip = $this->daftarkanArsip();

        $pjo = $this->aktorPjo();
        $gl = $this->berprofilPenuh($this->aktorGl());

        $layar = [
            ['V2/Documents/Distribution', $pjo, route('documents.distribution')],
            ['V2/Documents/Distribution', $pjo, route('documents.distribution', ['sumber' => 'informasi'])],
            ['V2/Documents/RincianInformasi', $pjo, route('documents.rincianInformasi', $info)],
            ['V2/Documents/Arsip/Edit', $gl, route('documents.arsip.edit', $arsip)],
            ['V2/Documents/Arsip/Catatan', $gl, route('documents.arsip.catatan', $arsip)],
        ];

        foreach ($layar as [$komponen, $aktor, $url]) {
            $page = $this->actingAs($aktor)->get($url)->assertOk()->viewData('page');

            $this->assertSame($komponen, $page['component'], "{$url} harus dirender {$komponen}");
        }
    }

    // ── Kebocoran props ──────────────────────────────────────────────────────

    /**
     * Tak satu pun rahasia ikut ke atribut `data-page`.
     *
     * Diperiksa pada HTML MENTAH, bukan pada larik props: yang berbahaya justru
     * kolom yang tak pernah dibaca TSX mana pun — ia tetap terbaca lewat "view
     * source", dan justru karena tak dibaca, tak ada satu pun tes rupa yang
     * bisa menangkapnya.
     */
    public function test_props_tak_membocorkan_kata_sandi_maupun_jalur_berkas_arsip(): void
    {
        Storage::fake('local');
        $this->dokumenBerlaku();
        $info = $this->informasi();
        $arsip = $this->daftarkanArsip();

        $pjo = $this->aktorPjo();
        $gl = $this->berprofilPenuh($this->aktorGl());

        $halaman = [
            [$pjo, route('documents.distribution')],
            [$pjo, route('documents.distribution', ['sumber' => 'informasi'])],
            [$pjo, route('documents.rincianInformasi', $info)],
            [$gl, route('documents.arsip.edit', $arsip)],
            [$gl, route('documents.arsip.catatan', $arsip)],
        ];

        foreach ($halaman as [$aktor, $url]) {
            $html = $this->actingAs($aktor)->get($url)->assertOk()->getContent();

            // `photo_path` menyusul sejak wajah pembaca & pelaku timeline
            // digambar: yang boleh keluar cuma URL hasil `Storage::url()`,
            // TAK PERNAH jalur disknya.
            foreach (['password', 'remember_token', 'arsip_path', 'photo_path'] as $rahasia) {
                $this->assertStringNotContainsString('"'.$rahasia.'"', (string) $html,
                    "{$url} membocorkan `{$rahasia}` ke payload Inertia");
            }
        }
    }

    /**
     * Rincian pembaca informasi diratakan — kolomnya TERBATAS, bukan model User.
     *
     * `foto` menyusul (URL foto profil, supaya wajah pembacanya muncul di panel
     * distribusi). Ia ditambahkan SENGAJA di sini, bukan diakali dengan
     * melonggarkan asersinya jadi `assertArrayHasKey`: yang dijaga tes ini
     * adalah HIMPUNAN kuncinya yang persis, karena satu kunci yang menyelinap
     * masuk di sini artinya satu kolom `users` menyelinap ke atribut
     * `data-page`. Nilainya sendiri cuma `Storage::url()`, bukan `photo_path`.
     */
    public function test_rincian_informasi_hanya_membawa_kolom_yang_diizinkan(): void
    {
        $info = $this->informasi();

        $rincian = $this->propsInertia(
            $this->actingAs($this->aktorPjo())->get(route('documents.rincianInformasi', $info))->assertOk()
        )['rincian'];

        $this->assertArrayHasKey('sudah', $rincian);
        $this->assertArrayHasKey('belum', $rincian);

        foreach ($rincian['belum'] as $orang) {
            $this->assertSame(['nama', 'jabatan', 'foto'], array_keys($orang));
        }

        foreach ($rincian['sudah'] as $orang) {
            $this->assertSame(['nama', 'jabatan', 'foto', 'platform', 'waktu'], array_keys($orang));
        }
    }

    // ── Bentuk props ─────────────────────────────────────────────────────────

    /** Cakupan menempel di barisnya, lengkap keempat angkanya. */
    public function test_tiap_baris_membawa_cakupannya_sendiri(): void
    {
        $doc = $this->dokumenBerlaku();

        $baris = collect(
            $this->propsInertia(
                $this->actingAs($this->aktorPjo())->get(route('documents.distribution'))->assertOk()
            )['documents']
        )->firstWhere('id', $doc->id);

        $this->assertNotNull($baris, 'dokumen Berlaku ikut terdaftar');
        $this->assertSame(['pembaca', 'sasaran', 'persen', 'unduhan'], array_keys($baris['cakupan']));
        // Lencana "Arsip"/"Nomor Lama" digambar dari kolom yang sama dengan
        // seluruh daftar dokumen sejak Fase 8 — bukan dihitung ulang di klien.
        $this->assertArrayHasKey('nomor_luar_pola', $baris);
        $this->assertArrayHasKey('arsip', $baris);
    }

    /**
     * Bawaannya cakupan MENAIK — dan menekan judul kolom membatalkannya.
     *
     * Keduanya dikerjakan server. Kalau pengurutan bawaannya kelak pindah ke
     * klien, halaman ini berhenti menjawab pertanyaan yang melahirkannya:
     * dokumen mana yang belum sampai.
     */
    public function test_pengurutan_dikerjakan_server(): void
    {
        $this->dokumenBerlaku();

        $persen = collect(
            $this->propsInertia(
                $this->actingAs($this->aktorPjo())->get(route('documents.distribution'))->assertOk()
            )['documents']
        )->pluck('cakupan.persen')->all();

        $urut = $persen;
        sort($urut);
        $this->assertSame($urut, $persen, 'bawaannya cakupan menaik');

        $nomor = collect(
            $this->propsInertia(
                $this->actingAs($this->aktorPjo())
                    ->get(route('documents.distribution', ['sort' => 'nomor', 'dir' => 'asc']))
                    ->assertOk()
            )['documents']
        )->pluck('nomor')->all();

        $urutNomor = $nomor;
        sort($urutNomor);
        $this->assertSame($urutNomor, $nomor, 'permintaan pembaca yang menang saat kolom ditekan');
    }

    /** Penyaring jenis menyaring di SERVER, bukan di klien. */
    public function test_penyaring_jenis_menyaring_di_server(): void
    {
        $this->dokumenBerlaku();

        $props = $this->propsInertia(
            $this->actingAs($this->aktorPjo())
                ->get(route('documents.distribution', ['type' => 'JSA']))
                ->assertOk()
        );

        $this->assertSame('JSA', $props['filters']['type']);

        foreach ($props['documents'] as $baris) {
            $this->assertSame('JSA', $baris['jenis']);
        }
    }

    /** Halaman arsip mengirim Edisi/Revisi yang BERLAKU saat dokumen dikirim. */
    public function test_halaman_catatan_mengirim_revisi_saat_kirim(): void
    {
        Storage::fake('local');
        $arsip = $this->daftarkanArsip();

        $props = $this->propsInertia(
            $this->actingAs($this->berprofilPenuh($this->aktorGl()))
                ->get(route('documents.arsip.catatan', $arsip))
                ->assertOk()
        );

        [$edisi, $revisi] = $arsip->revisiSaatKirim();

        $this->assertSame(['edisi' => $edisi, 'revisi' => $revisi], $props['revisiKirim']);
        // `jumlahHalaman` DICABUT bersama pilihan "gabung" (rencana pra-produksi
        // Fase 2) — tak ada lagi halaman yang dipotong, jadi tak ada yang perlu
        // dihitung. Penguncinya pindah ke ArsipCatatanRevisiTest.
        $this->assertTrue($props['document']['berlembar_revisi']);
        // `<input type="date">` hanya menerima Y-m-d; `published_at` bertipe
        // Carbon yang di JSON jadi cap waktu ISO lengkap dengan jamnya.
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            (string) $this->propsInertia(
                $this->actingAs($this->berprofilPenuh($this->aktorGl()))
                    ->get(route('documents.arsip.edit', $arsip))
                    ->assertOk()
            )['document']['tanggal_efektif'],
        );
    }

    /**
     * Lembar catatan dikirim sebagai JSON BERSARANG, bukan FormData berkurung.
     *
     * Alpine mengirim `sections[catatan_revisi][0][catatan]`; React mengirim
     * larik bersarang. `persistRevisionLog` membaca keduanya lewat
     * `input('sections.catatan_revisi')` — tapi karena bentuknya berubah, ia
     * dibuktikan langsung ke penerimanya (pelajaran Fase 9.5).
     */
    public function test_lembar_catatan_diterima_dalam_bentuk_json_bersarang(): void
    {
        Storage::fake('local');
        $arsip = $this->daftarkanArsip();

        $this->actingAs($this->berprofilPenuh($this->aktorGl()))
            ->postJson(route('documents.arsip.catatan.store', $arsip), [
                'edisi' => 2,
                'no_revisi' => 3,
                'sections' => ['catatan_revisi' => [
                    ['no_rev' => 3, 'tanggal' => '2020-01-31', 'halaman' => '1-2', 'catatan' => 'Kiriman React.'],
                ]],
            ])
            // Sejak pilihan "gabung" dicabut, satu-satunya kelanjutan halaman ini
            // adalah wizard salinan (rencana pra-produksi Fase 2).
            ->assertRedirect(route('documents.edit',
                Document::where('revises_document_id', $arsip->id)->firstOrFail()));

        $this->assertSame('Kiriman React.', $arsip->fresh()->contentMap()['catatan_revisi'][0]['catatan']);
    }

    // ── Pakem P6 — jenis unggahan ────────────────────────────────────────────

    /** FK unggahan: terdaftar di Distribusi, tapi tak pernah punya lembar revisi. */
    public function test_jenis_unggahan_fk(): void
    {
        Storage::fake('local');
        $fk = $this->daftarkanArsip('FK');

        $this->assertTrue($fk->type->isUnggahan());

        // Perbaikan metadata tetap ada, lembar Catatan Revisi TIDAK.
        $props = $this->propsInertia(
            $this->actingAs($this->berprofilPenuh($this->aktorGl()))
                ->get(route('documents.arsip.edit', $fk))
                ->assertOk()
        );
        $this->assertFalse($props['document']['berlembar_revisi']);

        $this->actingAs($this->berprofilPenuh($this->aktorGl()))
            ->get(route('documents.arsip.catatan', $fk))
            ->assertNotFound();

        // Dan ia tetap terhitung di Distribusi — ia dokumen Berlaku juga.
        $baris = collect(
            $this->propsInertia(
                $this->actingAs($this->aktorPjo())->get(route('documents.distribution'))->assertOk()
            )['documents']
        )->firstWhere('id', $fk->id);

        $this->assertNotNull($baris, 'FK unggahan ikut terhitung di Distribusi');
        $this->assertTrue($baris['arsip']);
    }

    // ── Pakem P4 — otorisasi tetap di server ─────────────────────────────────

    /** Keempat pintu tetap tertutup bagi yang tak berwenang. */
    public function test_tetap_403_di_luar_jangkauan(): void
    {
        $info = $this->informasi();
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();

        // GL membuat dokumen, tapi tak berwenang menegur siapa pun soal cakupan.
        $this->actingAs($gl)->get(route('documents.distribution'))->assertForbidden();
        $this->actingAs($gl)->get(route('documents.distribution', ['sumber' => 'informasi']))->assertForbidden();
        $this->actingAs($gl)->get(route('documents.rincianInformasi', $info))->assertForbidden();
        $this->actingAs($nonStaff)->get(route('documents.rincianInformasi', $info))->assertForbidden();
    }

    /** Dokumen lama hanya boleh diperbaiki pengunggahnya (atau Admin). */
    public function test_perbaikan_arsip_tertutup_bagi_orang_lain(): void
    {
        Storage::fake('local');
        $arsip = $this->daftarkanArsip();

        $this->actingAs($this->aktorNonStaff())
            ->get(route('documents.arsip.edit', $arsip))
            ->assertForbidden();

        $this->actingAs($this->aktorNonStaff())
            ->get(route('documents.arsip.catatan', $arsip))
            ->assertForbidden();
    }
}
