<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Review;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\ReviewScreen;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 10 — delapan layar peninjauan & persetujuan pindah ke Inertia.
 *
 * Yang dijaga di sini adalah hal-hal yang gagal DIAM-DIAM, dan tak satu pun
 * tertangkap `npm run build`, `tsc --noEmit`, atau `smartpro:uji-render`:
 *
 *  1. **SATU komponen melayani TIGA pintu masuk.** `review/show.blade.php`
 *     sejak awal dipakai peninjauan SH/DH, peninjauan MD, dan Masukan Sejawat.
 *     Kalau salah satunya diam-diam bercabang jadi halaman kedua, tak ada
 *     gerbang lain yang bicara — sampai suatu hari keduanya tak sepakat.
 *  2. **Model tak pernah ikut mentah ke payload.** Layar ini menggendong
 *     kandidat pengalihan (koleksi `User`), komentar lampiran
 *     (`comments.user`), dan pemberi masukan sejawat (`masukan.user`) —
 *     ketiganya membawa `password` + `remember_token`. Kelas kesalahan yang
 *     sama dengan Fase 6, 7, 8, dan 9.
 *  3. **HTML `rich_text` dibersihkan di SERVER.** Blade lama membersihkannya
 *     tepat saat mencetak; props Inertia tak punya kemewahan itu — apa pun
 *     yang sampai ke `data-page` sudah terlanjur ada di halaman.
 *  4. **Pakem P1**: bab yang ditinjau datang dari `schema_json`, bukan daftar
 *     nama bab di TSX.
 *  5. **Pakem P4**: otorisasi tetap di server, sekalipun tombolnya tak pernah
 *     digambar.
 *  6. **Pakem P6**: jenis unggahan (FK/PX) ikut diuji, bukan cuma SOP.
 */
class TinjauInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function md(): User
    {
        return User::peninjauMd()->firstOrFail();
    }

    /** Dokumen milik GL ICTMD yang sedang dipegang $peninjau. */
    private function ditinjau(User $peninjau, string $jenis = 'SOP', string $status = 'in_review'): Document
    {
        $gl = $this->aktorGl();

        $doc = app(DocumentService::class)->createDraft(
            $gl,
            DocumentType::where('code', $jenis)->firstOrFail(),
            $gl->department,
            "{$jenis} Uji Tinjau Inertia ".Str::random(6),
        );
        $doc->update(['reviewer_id' => $peninjau->id, 'approver_id' => $this->aktorPjo()->id, 'status' => $status]);

        return $doc->fresh();
    }

    /* ======================= Komponen tiap rute ======================= */

    public function test_setiap_antrean_merender_komponennya_dengan_paginator_utuh(): void
    {
        $peta = [
            'review.index' => ['V2/Review/Index', fn () => $this->aktorSh()],
            'review.md' => ['V2/Review/Md', fn () => $this->md()],
            'approvals.index' => ['V2/Approvals/Index', fn () => $this->aktorPjo()],
        ];

        foreach ($peta as $rute => [$komponen, $aktor]) {
            $this->actingAs($aktor())->get(route($rute))->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($komponen)
                    // Bentuk paginator UTUH — `through()`, bukan `map()`.
                    // Dengan `map()` daftarnya tetap tampil dan hanya
                    // `links`/`from`/`to`/`total` yang lenyap, sehingga
                    // paginasinya mati tanpa satu pun galat (pelajaran Fase 6).
                    ->has('documents.data')
                    ->has('documents.links')
                    ->has('documents.total')
                    ->etc());
        }

        // "Status Revisi" duduk di halaman yang SAMA dengan antrean tinjau —
        // dan sengaja bukan paginator (v3.1 §4.3).
        $this->actingAs($this->aktorSh())->get(route('review.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('statusRevisi'));
    }

    /**
     * SATU halaman melayani TIGA pintu masuk — persis seperti Blade-nya.
     *
     * Yang membedakan hanya props: alamat tujuan formulir, panel AI, dan
     * ada-tidaknya tombol keputusan.
     */
    public function test_tiga_pintu_masuk_memakai_komponen_yang_sama(): void
    {
        $sh = $this->aktorSh();
        $sop = $this->ditinjau($sh);

        $this->actingAs($sh)->get(route('review.show', $sop))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                ->where('formAction', route('review.store', $sop))
                ->where('backUrl', route('review.index'))
                ->has('aiUrl'));

        $sop->update(['status' => 'verifikasi_md']);
        $this->actingAs($this->md())->get(route('review.md.show', $sop))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                ->where('formAction', route('review.md.store', $sop))
                ->where('backUrl', route('review.md')));

        [$glA, $glB, $draft] = $this->duaGlSedepartemen();
        $this->actingAs($glB)->get(route('masukan-sejawat.create', $draft))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                ->where('formAction', route('masukan-sejawat.store', $draft))
                ->where('tanpaKeputusan', true));

        $this->assertNotSame($glA->id, $glB->id);
    }

    /* ======================= Kebocoran ======================= */

    /**
     * Tiga koleksi model yang layar ini gendong, tak satu pun boleh mentah.
     *
     * Kandidat pengalihan, komentar lampiran, dan pemberi masukan sejawat
     * semuanya berujung ke `User` — lengkap dengan `password`, `remember_token`,
     * dan kunci mentah `jabatan` yang tak pernah boleh tercetak (CLAUDE.md §6c).
     */
    public function test_kolom_rahasia_pengguna_tak_pernah_ikut_ke_payload(): void
    {
        $glShe = $this->aktorGl('SHE');
        $lain = User::create([
            'name' => 'GL SHE Uji Bocor',
            'nrp' => 'TIB-'.Str::upper(Str::random(6)),
            'jabatan' => User::JABATAN_GROUP_LEADER,
            'department_id' => Department::where('code', 'SHE')->firstOrFail()->id,
            'password' => Hash::make('rahasia123'),
            'status' => 'active',
        ]);
        $lain->assignRole('group_leader');

        $jsa = $this->ditinjau($glShe, 'JSA');
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);

        $res = $this->actingAs($glShe)->get(route('review.show', $jsa))->assertOk();
        $html = (string) $res->getContent();

        $this->assertStringNotContainsString('remember_token', $html);
        $this->assertStringNotContainsString($lain->fresh()->password, $html);

        $kandidat = $this->propsInertia($res)['alihKandidat'];
        $this->assertNotEmpty($kandidat, 'papan pengalihan JSA harus punya kandidat');

        foreach ($kandidat as $k) {
            // Persis lima kunci — sama dengan wizard (`DocumentWizard::propsKandidat`).
            $this->assertSame(['id', 'nama', 'nrp', 'dept', 'jabatan'], array_keys($k));
            // LABEL, bukan kunci mentah (dijaga juga LabelJabatanTest).
            $this->assertNotSame(User::JABATAN_GROUP_LEADER, $k['jabatan']);
        }
    }

    public function test_masukan_sejawat_tak_membocorkan_pemberinya(): void
    {
        [$glA, $glB, $draft] = $this->duaGlSedepartemen();

        $this->actingAs($glB)->post(route('masukan-sejawat.store', $draft), [
            'summary' => 'Ringkasan uji kebocoran.',
            'annotations' => ['tujuan' => [0 => 'Catatan uji kebocoran.']],
        ])->assertRedirect();

        $res = $this->actingAs($glA)->get(route('masukan-sejawat.show', $draft))->assertOk();

        $this->assertStringNotContainsString('remember_token', (string) $res->getContent());
        $this->assertStringNotContainsString($glB->fresh()->password, (string) $res->getContent());

        $masukan = $this->propsInertia($res)['masukan'][0];
        $this->assertSame(['id', 'oleh', 'waktu', 'ringkasan', 'perSection'], array_keys($masukan));
        // Nama bagian datang dari SCHEMA, bukan `section_key` mentah.
        $this->assertSame('I. TUJUAN', $masukan['perSection'][0]['label']);
        // Penunjuk item sudah jadi kalimat, bukan angka mesin.
        $this->assertSame('Item 1', $masukan['perSection'][0]['items'][0]['ref']);
    }

    /* ======================= Pembersihan HTML ======================= */

    /**
     * `rich_text` dibersihkan DI SERVER sebelum masuk props.
     *
     * Inilah SATU-SATUNYA layar yang menyajikan isi `document_contents` sebagai
     * HTML kepada orang yang wewenangnya LEBIH TINGGI daripada penulisnya.
     * Blade lama membersihkannya tepat saat mencetak; di Inertia itu terlambat.
     */
    public function test_rich_text_dibersihkan_sebelum_masuk_props(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->ditinjau($sh);
        $doc->contents()->create(['section_key' => 'aktivitas', 'value_json' => [
            ['sub_judul' => 'Uji', 'deskripsi' => '<p>Kalimat aman</p><script>alert(1)</script>'],
        ]]);

        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()
        );

        $isi = $props['contentMap']['aktivitas'][0]['deskripsi'];
        $this->assertStringContainsString('Kalimat aman', $isi);
        $this->assertStringNotContainsString('<script', $isi);
    }

    /* ======================= Pakem P1 ======================= */

    /**
     * Bab yang bisa ditinjau datang dari `schema_json`, bukan dari daftar nama
     * bab yang diketik di TSX.
     *
     * Seksi disisipkan lewat basis data, lalu dibuktikan muncul sendiri di
     * props — dikembalikan `DatabaseTransactions`.
     */
    public function test_seksi_baru_di_schema_muncul_sendiri_di_layar_tinjau(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->ditinjau($sh);

        $type = $doc->type;
        $schema = $type->schema_json;
        $schema['steps'][0]['sections'][] = [
            'key' => 'seksi_uji_p1',
            'type' => 'rich_list',
            'label' => 'Bab Uji P1',
        ];
        $type->update(['schema_json' => $schema]);

        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('review.show', $doc->fresh()))->assertOk()
        );

        $kunci = collect($props['schema']['steps'])->flatMap(fn ($s) => $s['sections'] ?? [])->pluck('key');
        $this->assertContains('seksi_uji_p1', $kunci->all());

        // …dan tipenya memang ikut dianggap "bisa ditinjau", jadi bab itu benar
        // benar mendapat kotak catatan tanpa satu baris TSX pun disentuh.
        $this->assertContains('rich_list', $props['tipeDitinjau']);
    }

    /** Daftar tipe yang ditinjau datang dari SERVER, bukan diketik di TSX. */
    public function test_tipe_seksi_yang_ditinjau_datang_dari_server(): void
    {
        $sh = $this->aktorSh();

        $this->actingAs($sh)->get(route('review.show', $this->ditinjau($sh)))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tipeDitinjau', ReviewScreen::TIPE_DITINJAU));

        // `user_picker` sengaja TIDAK ikut: peninjau & penyetuju bukan isi yang
        // ditinjau. Kalau ia kelak masuk daftar, itu keputusan sadar — bukan
        // pergeseran diam-diam.
        $this->assertNotContains('user_picker', ReviewScreen::TIPE_DITINJAU);
    }

    /* ======================= Anotasi putaran sebelumnya ======================= */

    /**
     * `anotasiLama` diratakan jadi `section_key` → `item_ref` → daftar komentar,
     * dan baris TANPA komentar dibuang.
     *
     * Baris tanpa komentar memang ada: sejak tanda ✓/✗ JSA,
     * {@see ReviewDecision::simpanAnotasi()} menulis baris yang hanya memikul
     * verdict. Blade lama mencetaknya juga — hasilnya "Catatan sebelumnya:"
     * yang kosong di belakangnya, satu per pengendalian.
     */
    public function test_anotasi_lama_diratakan_dan_yang_kosong_dibuang(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->ditinjau($sh);

        $review = Review::create([
            'document_id' => $doc->id,
            'reviewer_id' => $sh->id,
            'revision_round' => 0,
            'decision' => 'needs_revision',
            'summary' => 'Ringkasan putaran sebelumnya.',
        ]);
        $review->annotations()->create([
            'section_key' => 'tujuan', 'item_ref' => '0',
            'severity' => 'minor', 'comment' => 'Perjelas sasarannya.',
        ]);
        $review->annotations()->create([
            'section_key' => 'tujuan', 'item_ref' => '1',
            'severity' => 'minor', 'comment' => null, 'verdict' => 'sesuai',
        ]);

        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()
        );

        $this->assertSame(['0' => ['Perjelas sasarannya.']], $props['anotasiLama']['tujuan']);
    }

    /* ======================= Papan pengalihan ======================= */

    /**
     * Papan pengalihan datang SUDAH JADI dari server — beban, jadwal, dan
     * ambang warnanya.
     *
     * Tanggal pita sudah berupa TEKS: memformatnya di peramban berarti locale &
     * zona waktu ditentukan mesin pengguna, bukan aplikasi (CLAUDE.md §2).
     */
    public function test_papan_pengalihan_sudah_jadi_dari_server(): void
    {
        $glShe = $this->aktorGl('SHE');
        $jsa = $this->ditinjau($glShe, 'JSA');
        $jsa->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K']]]],
        ]]);

        $props = $this->propsInertia(
            $this->actingAs($glShe)->get(route('review.show', $jsa))->assertOk()
        );

        $this->assertNotEmpty($props['alihKandidat']);
        $this->assertEqualsCanonicalizing(['padat', 'sibuk', 'jenis'], array_keys($props['ambang']));
        $this->assertSame(route('review.alihkan', $jsa), $props['alihUrl']);

        $satu = reset($props['alihKetersediaan']);
        $this->assertArrayHasKey('pita', $satu);
        $this->assertIsString($satu['pita'][0]['tanggal'], 'tanggal pita harus sudah jadi teks');
        $this->assertIsBool($satu['tersedia']);

        // SOP tak mengenal pengalihan sama sekali — `null`, bukan larik kosong,
        // supaya TSX tak perlu menebak bedanya.
        $this->actingAs($this->aktorSh())->get(route('review.show', $this->ditinjau($this->aktorSh())))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('alihKandidat', null));
    }

    /* ======================= Pakem P6 — jenis unggahan ======================= */

    /**
     * FK/PX tak punya satu pun bab, dan layar tinjaunya tetap harus berdiri.
     *
     * Unggahan dideteksi lewat `document_types.class`, BUKAN `schema_json`
     * NULL — nilainya `[]`.
     */
    public function test_jenis_unggahan_tetap_bisa_ditinjau(): void
    {
        $sh = $this->aktorSh();
        $fk = DocumentType::where('class', 'unggahan')->firstOrFail();

        $doc = $this->ditinjau($sh, $fk->code);

        $this->actingAs($sh)->get(route('review.show', $doc))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Review/Show')
                // Nol bab: `schema.steps` kosong, jadi layar tak menggambar
                // satu pun kotak catatan — tepat seperti Blade lama.
                ->where('schema', [])
                ->where('contentMap', [])
                ->where('pakaiVerdict', false));
    }

    /* ======================= Pakem P4 — otorisasi di server ======================= */

    public function test_layar_tinjau_dan_persetujuan_tetap_403_di_luar_jangkauan(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->ditinjau($sh);

        // Bukan peninjaunya.
        $this->actingAs($this->aktorGl())->get(route('review.show', $doc))->assertForbidden();
        // Bukan Management Development.
        $this->actingAs($sh)->get(route('review.md'))->assertForbidden();
        // Bukan penyetujunya — dan dokumennya pun belum menunggu persetujuan.
        $this->actingAs($this->aktorPjo())->get(route('approvals.show', $doc))->assertForbidden();
        // Masukan sejawat hanya antar-GL sedepartemen atas dokumen rekan.
        $this->actingAs($this->aktorNonStaff())->get(route('masukan-sejawat.create', $doc))->assertForbidden();
    }

    /* ======================= Antrean nonaktif ======================= */

    /**
     * Tahap & alasan pengajuan nonaktif datang dari SERVER.
     *
     * `tahapTerakhir` menentukan bunyi konfirmasinya — menyetujui di tahap itu
     * LANGSUNG mematikan dokumen dan melepas nomornya, dan klien tak boleh
     * menghitung sendiri kapan itu terjadi (pakem P5).
     */
    public function test_antrean_nonaktif_membawa_tahap_dan_alasan_dari_server(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();

        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Uji Nonaktif Inertia'
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);

        $this->actingAs($gl)->post(route('nonaktif.ajukan', $doc), [
            'alasan' => 'Alat yang dipakai sudah tidak ada.',
        ])->assertRedirect();

        $baris = collect($this->propsInertia(
            $this->actingAs($sh)->get(route('nonaktif.index'))->assertOk()
        )['documents']['data'])->firstWhere('id', $doc->id);

        $this->assertSame('sh', $baris['tahap']);
        $this->assertSame('SH/DH Departemen', $baris['tahapLabel']);
        $this->assertSame('Alat yang dipakai sudah tidak ada.', $baris['alasan']);
        $this->assertFalse($baris['tahapTerakhir'], 'SH bukan tahap terakhir');
        $this->assertSame($gl->name, $baris['pengaju']);

        // Baris pengajuan memang tersimpan sebagai `approvals` ber-kind nonaktif.
        $this->assertSame(1, $doc->approvals()->where('kind', Approval::KIND_NONAKTIF)->count());
    }

    /* ======================= Bentuk kiriman ======================= */

    /**
     * Bentuk kiriman berubah, penerimanya tidak.
     *
     * Formulir Blade mengirim `annotations[analisa][L0-B0-P0]` sebagai FormData
     * berkurung, dan penanda AI-nya sebagai `<input type="hidden" value="1">` —
     * yaitu STRING `'1'`. React mengirim JSON bersarang, dan penandanya BOOLEAN.
     * `ReviewDecision::simpanAnotasi()` membacanya dengan `== '1'`, yang pada
     * PHP 8 memang benar untuk `true` — tapi itu kesimpulan dari membaca kode,
     * bukan dari menjalankannya. Di sinilah ia dijalankan.
     *
     * Pelajaran yang sama dengan Fase 9 (`saveStep`/`autosave`): yang berubah
     * bentuk wajib dibuktikan langsung ke penerimanya, bukan diandaikan.
     */
    public function test_kiriman_json_bersarang_react_diterima_apa_adanya(): void
    {
        $sh = $this->aktorSh();
        $doc = $this->ditinjau($sh, 'JSA');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'L', 'bahaya' => [['risiko' => 'R', 'pengendalian' => ['K1', 'K2']]]],
        ]]);

        $this->actingAs($sh)->postJson(route('review.store', $doc), [
            'summary' => 'Ringkasan dari React.',
            // Bersarang, bukan berkurung — dan penandanya BOOLEAN, bukan '1'.
            'annotations' => ['analisa' => ['L0-B0-P0' => 'Catatan dari React.']],
            'annotations_ai' => ['analisa' => ['L0-B0-P0' => true]],
            'verdicts' => ['L0-B0-P0' => 'sesuai', 'L0-B0-P1' => 'perlu_revisi'],
        ])->assertRedirect(route('review.index'));

        // Satu tanda ✗ → dokumen dikembalikan (keputusan DITURUNKAN dari tanda).
        $this->assertSame('rejected', $doc->refresh()->status);

        $anotasi = $doc->reviews()->latest('id')->first()->annotations()->get()->keyBy('item_ref');

        $this->assertSame('Catatan dari React.', $anotasi['L0-B0-P0']->comment);
        $this->assertSame('sesuai', $anotasi['L0-B0-P0']->verdict);
        // Inilah yang dibuktikan: boolean `true` tetap terbaca sebagai "dari AI".
        $this->assertTrue($anotasi['L0-B0-P0']->ai_generated);
        $this->assertTrue($anotasi['L0-B0-P0']->ai_adopted);

        // Pengendalian kedua: bertanda tanpa catatan — tetap meninggalkan jejak.
        $this->assertSame('perlu_revisi', $anotasi['L0-B0-P1']->verdict);
        $this->assertNull($anotasi['L0-B0-P1']->comment);
        $this->assertFalse($anotasi['L0-B0-P1']->ai_generated);
    }

    /** Dua GL sedepartemen + satu draft milik yang pertama. */
    private function duaGlSedepartemen(): array
    {
        $tanda = Str::upper(Str::random(6));
        $dept = Department::create(['code' => 'TIN'.$tanda, 'name' => 'Departemen Uji Tinjau '.$tanda]);

        $buat = function (string $suffix) use ($dept) {
            $u = User::create([
                'name' => 'GL Uji Tinjau '.$suffix,
                'nrp' => 'TIG-'.Str::upper(Str::random(6)),
                'jabatan' => User::JABATAN_GROUP_LEADER,
                'department_id' => $dept->id,
                'password' => Hash::make('rahasia123'),
                'status' => 'active',
            ]);
            $u->assignRole('group_leader');

            return $u->fresh();
        };

        $glA = $buat('A');
        $glB = $buat('B');

        $draft = app(DocumentService::class)->createDraft(
            $glA, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Uji Sejawat '.$tanda
        );

        return [$glA, $glB, $draft];
    }
}
