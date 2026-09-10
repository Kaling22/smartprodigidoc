<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\ReviewerAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pengunci Fase 9 — pembuatan & wizard pengisian dokumen pindah ke Inertia.
 *
 * Yang dijaga di sini adalah hal-hal yang gagal DIAM-DIAM, dan tak satu pun
 * tertangkap `npm run build`, `tsc --noEmit`, atau `smartpro:uji-render`:
 *
 *  1. **Schema tetap penggerak tunggal** (pakem P1). Seksi yang ditambahkan
 *     lewat SQL harus muncul sendiri di props, dan tiap tipe seksi yang
 *     dipakai schema mana pun harus punya komponen di `fields/index.tsx` —
 *     tipe yang tak terpetakan JATUH ke kolom teks, diam-diam, dan babnya
 *     berubah rupa tanpa satu pun galat.
 *  2. **Model tak pernah ikut mentah ke payload.** Wizard menggendong DAFTAR
 *     KANDIDAT — model User beserta `password`, `remember_token`, dan kunci
 *     mentah `jabatan`. Kelas kesalahan yang sama dengan Fase 6, 7, dan 8.
 *  3. **Kiriman langkah kini JSON bersarang**, bukan FormData berkurung.
 *     Bentuknya berubah, penerimanya tidak — jadi bentuk barunya harus
 *     dibuktikan benar-benar tersimpan.
 *  4. **Tanggal diformat SERVER.** `ReviewerAvailability` memulangkan objek
 *     Carbon; kalau ia lolos apa adanya ke props, nama bulan & zona waktunya
 *     ditentukan mesin pengguna, bukan aplikasi.
 *  5. **Otorisasi tetap di server** (pakem P4) dan **jenis unggahan ikut
 *     diuji** (pakem P6).
 */
class WizardInertiaTest extends TestCase
{
    use DatabaseTransactions;

    private function gl(): User
    {
        return $this->berprofilPenuh($this->aktorGl());
    }

    private function draft(string $judul = 'SOP Uji Wizard Inertia', string $jenis = 'SOP'): Document
    {
        $gl = $this->gl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', $jenis)->firstOrFail(), $gl->department, $judul
        );
    }

    /** Ketiga rute wizard menggambar komponennya sendiri, dengan props lengkap. */
    public function test_rute_wizard_menggambar_komponen_inertia(): void
    {
        $gl = $this->gl();

        $this->actingAs($gl)->get(route('documents.create', ['type' => 'SOP']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Documents/Create')
                ->has('type.id')->has('type.code')->has('type.name')
                ->has('departments')
                ->has('defaultDept')
                ->has('canChooseDept')
                ->has('numberPreview')
                ->where('unggahanSaja', false)
                // Pola nomor ditentukan Pengaturan, bukan diketik ulang di TSX.
                ->has('prefix')
            );

        $doc = $this->draft();
        $doc->update(['current_step' => 2]);

        $this->actingAs($gl)->get(route('documents.edit', $doc))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Documents/Edit')
                ->has('schema.steps')
                ->where('currentStep', 2)
                ->has('totalSteps')
                ->where('isRevLogStep', false)
                ->has('contentMap')
                ->where('editable', true)
                ->has('babAktif')
                ->has('labelMati')
                ->has('judulLangkahMati')
                ->has('candidates')
                ->has('ketersediaan')
                ->has('papan')
                ->has('ambang.padat')
                ->has('userValues')
                ->has('dokumenBerlaku')
                ->has('previewV')
                ->has('catatanItem')
                ->has('catatanYatim')
                ->where('rujukanPdfUrl', null)
                ->where('revisiKirim', null)
            );
    }

    /**
     * F7b — Langkah Log Revisi wajib membawa `revisiKirim` [edisi, no_revisi]
     * hasil `revisiSaatKirim()`, bukan cuma null seperti draft biasa di atas.
     */
    public function test_revisi_kirim_terisi_pada_langkah_log_revisi(): void
    {
        $doc = $this->draft();
        $doc->update([
            'status' => 'published', 'published_at' => now(), 'no_revisi' => 5, 'edisi' => '1',
            'doc_number_final' => $doc->doc_number, 'is_controlled' => true,
        ]);

        $new = app(DocumentService::class)->requestRevision($doc, $doc->creator);
        $schema = \App\Services\SchemaService::for($new->type);
        $new->update(['current_step' => $schema->stepCount() + 1]);

        $this->actingAs($doc->creator)->get(route('documents.edit', $new))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Documents/Edit')
                ->where('isRevLogStep', true)
                ->where('revisiKirim.edisi', 2)
                ->where('revisiKirim.revisi', 1)
            );
    }

    /**
     * Pakem P1 — seksi baru di `schema_json` muncul SENDIRI di form.
     *
     * Inilah uji yang diminta MIGRASI-SHADCN: kalau form React sudah menyalin
     * struktur schema jadi TSX statis, seksi ini tak akan pernah sampai ke
     * layar dan test ini merah. `DatabaseTransactions` mengembalikan schema-nya
     * begitu test selesai.
     */
    public function test_seksi_baru_di_schema_muncul_sendiri_di_props(): void
    {
        $doc = $this->draft('SOP Uji Pakem P1');
        $type = $doc->type;

        $schema = $type->schema_json;
        $schema['steps'][0]['sections'][] = [
            'key' => 'seksi_karangan_uji',
            'type' => 'rich_list',
            'label' => 'XX. SEKSI KARANGAN UJI',
        ];
        $type->update(['schema_json' => $schema]);

        $seksi = $this->seksiWizard(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        );

        $this->assertArrayHasKey('seksi_karangan_uji', $seksi,
            'seksi yang ditambahkan lewat schema harus muncul tanpa satu baris kode disentuh (pakem P1)');
        $this->assertSame('XX. SEKSI KARANGAN UJI', $seksi['seksi_karangan_uji']['label']);
    }

    /**
     * Tiap tipe seksi yang dipakai schema mana pun punya komponennya.
     *
     * Tipe yang tak terpetakan tidak meledak — ia JATUH ke `Text`, persis
     * seperti Blade lama jatuh ke `_text`. Itu perilaku yang benar untuk
     * bertahan hidup, tapi salah untuk dibiarkan tanpa disadari: bab
     * `jsa_analysis` yang tergambar sebagai satu kolom teks lolos build, lolos
     * tipe, lolos gerbang render, dan baru ketahuan dari dokumen yang rusak.
     */
    public function test_setiap_tipe_seksi_punya_komponen_di_peta_renderer(): void
    {
        $peta = file_get_contents(resource_path('js/components/v2/dokumen/fields/index.tsx'));

        $dipakai = DocumentType::query()->get()
            ->flatMap(fn (DocumentType $t) => collect($t->schema_json['steps'] ?? [])
                ->flatMap(fn ($step) => collect($step['sections'] ?? [])->pluck('type')))
            ->filter()->unique()->values();

        $this->assertNotEmpty($dipakai, 'tak ada satu pun tipe seksi terbaca — schema kosong?');

        foreach ($dipakai as $tipe) {
            $this->assertMatchesRegularExpression('/^\s*'.preg_quote($tipe, '/').':\s/m', $peta,
                "tipe seksi \"{$tipe}\" tak punya komponen — form akan menggambarnya sebagai kolom teks polos");
        }

        // Tipe FIELD di dalam repeatable_group ikut dijaga: cabangnya ada di
        // RepeatableGroup, dan yang tak dikenal jatuh ke <Input> teks.
        $grup = file_get_contents(resource_path('js/components/v2/dokumen/fields/RepeatableGroup.tsx'));

        $tipeKolom = DocumentType::query()->get()
            ->flatMap(fn (DocumentType $t) => collect($t->schema_json['steps'] ?? [])
                ->flatMap(fn ($step) => collect($step['sections'] ?? [])
                    ->flatMap(fn ($sec) => collect($sec['group_fields'] ?? $sec['fields'] ?? [])->pluck('type'))))
            ->filter()->unique()->reject(fn ($t) => $t === 'text')->values();

        foreach ($tipeKolom as $tipe) {
            $this->assertStringContainsString("field.type === '{$tipe}'", $grup,
                "kolom bertipe \"{$tipe}\" tak punya cabang di RepeatableGroup");
        }
    }

    /**
     * Kandidat peserta alur tak boleh membawa satu pun kolom rahasia.
     *
     * Props Inertia tertulis seluruhnya di atribut `data-page` dan terbaca
     * lewat "view source" — model User apa adanya berarti hash password setiap
     * SH/DH/PJO di departemen itu ikut terkirim ke peramban pembuat dokumen.
     */
    public function test_kandidat_tak_membawa_kolom_rahasia(): void
    {
        $doc = $this->draft('SOP Uji Kebocoran Kandidat');
        $doc->update(['current_step' => 2]);

        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        );

        $this->assertNotEmpty($props['candidates']['peninjau'] ?? [], 'papan peninjau kosong — testnya tak menguji apa pun');

        foreach ($props['candidates'] as $key => $daftar) {
            foreach ($daftar as $kandidat) {
                $this->assertSame(['id', 'nama', 'nrp', 'dept', 'jabatan'], array_keys($kandidat),
                    "kandidat {$key} membawa kolom di luar yang dipakai layar");
                // Label, BUKAN kunci mentah (CLAUDE.md §6c) — `staff` yang
                // tercetak apa adanya adalah cacat yang dijaga LabelJabatanTest.
                $this->assertNotContains($kandidat['jabatan'], array_keys(User::JABATAN_LABELS),
                    'kunci mentah jabatan tak boleh tercetak ke layar');
            }
        }

        $mentah = json_encode($props);
        $this->assertStringNotContainsString('remember_token', $mentah);
        $this->assertStringNotContainsString('password', $mentah);
    }

    /**
     * Papan ketersediaan: keputusannya dari SERVER, tanggalnya sudah jadi teks.
     *
     * `ReviewerAvailability::untuk()` memulangkan objek Carbon di `kembali` dan
     * di tiap sel `pita`. Kalau ia lolos apa adanya, tanggalnya diformat ulang
     * di peramban — dengan locale, timezone, dan nama bulan mesin pengguna.
     */
    public function test_papan_dan_ketersediaan_datang_jadi_dari_server(): void
    {
        $doc = $this->draft('SOP Uji Papan Props');
        $doc->update(['current_step' => 2]);

        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        );

        $this->assertSame(ReviewerAvailability::PAPAN, $props['papan'],
            'aturan papan wajib datang dari service, bukan diketik ulang di TSX (pakem P5)');

        $satu = collect($props['ketersediaan']['peninjau'])->first();
        $this->assertIsArray($satu);
        $this->assertSame(
            ['beban', 'batas', 'penuh', 'tingkat', 'off', 'tersedia', 'jenis', 'kembali', 'kembaliIso', 'alasan', 'pita'],
            array_keys($satu),
        );
        $this->assertIsString($satu['pita'][0]['tanggal'], 'tanggal pita harus sudah diformat server');
        $this->assertCount((int) config('smartpro.peninjau.hari_pita', 14), $satu['pita']);
    }

    /**
     * Kiriman langkah kini JSON BERSARANG — bentuk yang benar-benar dikirim
     * peramban sejak wizard jadi React. Sebelumnya FormData berkurung.
     */
    public function test_simpan_langkah_menerima_sections_bersarang_json(): void
    {
        $doc = $this->draft('SOP Uji Kiriman JSON');
        $doc->update(['current_step' => 2]);

        $this->actingAs($this->gl())
            ->postJson(route('documents.saveStep', $doc), [
                'step' => 2,
                'action' => 'save',
                'sections' => [
                    'pakai_flowchart' => '0',
                    'aktivitas' => [
                        ['sub_judul' => 'Langkah A', 'deskripsi' => '<p>Isi A</p>', 'pic' => 'GL'],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.index'));

        $isi = $doc->fresh()->contentMap();

        $this->assertSame('Langkah A', $isi['aktivitas'][0]['sub_judul']);
        $this->assertSame('<p>Isi A</p>', $isi['aktivitas'][0]['deskripsi'], 'HTML sah harus lolos sanitasi utuh');
        $this->assertSame('0', $isi['pakai_flowchart'], 'sakelar bab opsional ikut tersimpan');
    }

    /** Autosave menjawab JSON berisi jam simpan + sidik cetakan yang BARU. */
    public function test_autosave_menerima_json_dan_menjawab_sidik(): void
    {
        $doc = $this->draft('SOP Uji Autosave JSON');
        $doc->update(['current_step' => 1]);

        $this->actingAs($this->gl())
            ->postJson(route('documents.autosave', $doc), [
                'step' => 1,
                'sections' => ['tujuan' => ['Menjaga mutu']],
            ])
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'saved_at', 'v']);

        $this->assertSame(['Menjaga mutu'], $doc->fresh()->contentMap()['tujuan']);
    }

    /**
     * Pakem P6 — jenis UNGGAHAN (FK/PX) tak boleh terlupa.
     *
     * Formulirnya berbeda (unggah PDF, tanpa wizard), dan wizard-nya justru
     * TIDAK boleh terbuka untuk dokumen yang sudah jadi berkas.
     */
    public function test_jenis_unggahan_memakai_formulir_unggah_dan_melewati_wizard(): void
    {
        $gl = $this->gl();

        $this->actingAs($gl)->get(route('documents.create', ['type' => 'FK']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('V2/Documents/Create')
                ->where('unggahanSaja', true)
                ->where('type.code', 'FK')
            );

        // Create.tsx menggantungkan KETIGA perilakunya pada props itu: saklar
        // "dokumen lama" disembunyikan, mode arsip menyala sejak awal, dan
        // kolom berkas PDF selalu tampil.
        $tsx = file_get_contents(resource_path('js/pages/V2/Documents/Create.tsx'));
        $this->assertStringContainsString('useState(unggahanSaja)', $tsx, 'mode arsip harus menyala sejak awal bagi FK/PX');
        $this->assertStringContainsString('unggahanSaja ? null : (', $tsx, 'saklar "dokumen lama" harus disembunyikan');
        $this->assertStringContainsString("name=\"berkas\"", $tsx, 'kolom berkas PDF harus ada');

        // Dokumen yang isinya ada di berkasnya tak punya bab untuk diisi.
        $arsip = $this->draft('FK Uji Wizard', 'FK');
        $arsip->update(['arsip_path' => 'arsip/uji.pdf']);

        $this->actingAs($gl)->get(route('documents.edit', $arsip))
            ->assertRedirect(route('documents.show', $arsip));
    }

    /**
     * Pakem P4 — otorisasi tetap di server.
     *
     * Menyembunyikan tombol bukan otorisasi: wizard dokumen departemen lain
     * tetap 403 walau tak ada satu pun tautan menuju ke sana.
     */
    public function test_wizard_dokumen_di_luar_jangkauan_tetap_403(): void
    {
        $doc = $this->draft('SOP Uji Jangkauan');
        $lain = $this->berprofilPenuh($this->aktorGl('SHE'));

        $this->actingAs($lain)->get(route('documents.edit', $doc))->assertForbidden();
        $this->actingAs($lain)->postJson(route('documents.autosave', $doc), ['step' => 1])->assertForbidden();
    }

    /**
     * Pratinjau dirakit dari `previewV`, bukan dari permintaan kedua.
     *
     * Inilah yang dulu menghentikan panel "mati-nyala": `src` sudah lengkap
     * sejak halaman pertama digambar, dan URL ber-`?v=<sidik>` yang sama antar
     * langkah dilayani dari cache peramban tanpa permintaan jaringan.
     */
    public function test_pratinjau_dirakit_dari_sidik_yang_dikirim_server(): void
    {
        $tsx = file_get_contents(resource_path('js/pages/V2/Documents/Edit.tsx'));

        $this->assertStringContainsString('route(\'documents.pdf\', documentId)}?v=${versi}', $tsx,
            'src iframe harus dirakit dari props previewV');
        $this->assertStringContainsString('toolbar=0&navpanes=0&view=Fit', $tsx,
            'penampil PDF harus tetap tanpa toolbar dan memuat satu halaman penuh');
        $this->assertStringContainsString('useEffect(() => setVersi(previewV), [previewV])', $tsx,
            'sidik baru dari server harus menyegarkan panel');
    }

    /**
     * Butir 6 — pemisahan nomor & judul lampiran digerakkan SCHEMA, bukan kode.
     *
     * Yang dikunci: kunci `judul_ke` sampai ke props apa adanya (React yang
     * membacanya), DAN kolom picker tanpa `judul_ke` tetap berperilaku lama.
     * Kalau kelak seseorang memindahkan keputusan ini ke TSX — mis. "kalau
     * kuncinya `dokumen`, pecah" — test ini tetap hijau di sisi props tapi
     * cabang kedua di bawahnya merah, sebab schema-nya sudah tak menentukan
     * apa-apa lagi (pakem P1).
     */
    public function test_kunci_judul_ke_lampiran_datang_dari_schema(): void
    {
        $doc = $this->draft('SOP Uji Judul Lampiran');
        $doc->update(['current_step' => 2]);

        $picker = fn (array $seksi) => collect($seksi['lampiran']['group_fields'] ?? [])
            ->firstWhere('type', 'document_picker');

        $ada = $picker($this->seksiWizard(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        ));

        $this->assertNotNull($ada, 'bab LAMPIRAN SOP harus punya kolom document_picker');
        $this->assertSame('keterangan', $ada['judul_ke'] ?? null,
            'judul dokumen yang dipilih harus diarahkan ke kolom saudara lewat schema');

        // Cabut kuncinya lewat SQL → perilaku lama (satu baris teks gabungan).
        $type = $doc->type;
        $schema = $type->schema_json;
        foreach ($schema['steps'] as $i => $langkah) {
            foreach ($langkah['sections'] ?? [] as $j => $seksi) {
                foreach ($seksi['group_fields'] ?? [] as $k => $f) {
                    if (($f['type'] ?? '') === 'document_picker') {
                        unset($schema['steps'][$i]['sections'][$j]['group_fields'][$k]['judul_ke']);
                    }
                }
            }
        }
        $type->update(['schema_json' => $schema]);

        $tanpa = $picker($this->seksiWizard(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        ));

        $this->assertArrayNotHasKey('judul_ke', $tanpa,
            'tanpa kunci schema, picker wajib kembali ke perilaku lama tanpa satu baris kode disentuh');
    }

    /** Nomor dan judul tersimpan di DUA kolom terpisah, tak digabung server. */
    public function test_lampiran_menyimpan_nomor_dan_judul_terpisah(): void
    {
        $doc = $this->draft('SOP Uji Simpan Lampiran');
        $doc->update(['current_step' => 2]);

        $this->actingAs($this->gl())
            ->postJson(route('documents.saveStep', $doc), [
                'step' => 2,
                'action' => 'save',
                'sections' => [
                    'lampiran' => [
                        ['dokumen' => 'PPA-ADRO-IK-ICTMD-02', 'keterangan' => 'Instruksi Kerja Perawatan'],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.index'));

        $baris = $doc->fresh()->contentMap()['lampiran'][0];

        $this->assertSame('PPA-ADRO-IK-ICTMD-02', $baris['dokumen'], 'kotak utama hanya berisi NOMOR');
        $this->assertSame('Instruksi Kerja Perawatan', $baris['keterangan'], 'judul turun ke kotak di bawahnya');
    }

    /* ======================= Catatan per item ======================= */

    /** Draft `rejected` berisi dua butir Tujuan — bahan bagi keempat tes di bawah. */
    private function draftDitolak(): Document
    {
        $doc = $this->draft('SOP Uji Catatan Per Item');
        $doc->contents()->updateOrCreate(
            ['section_key' => 'tujuan'],
            ['value_json' => ['Menjamin keselamatan kerja perawatan.', 'Menyeragamkan langkah pemeriksaan.']]
        );
        $doc->update(['status' => 'rejected']);

        return $doc->fresh();
    }

    /** Satu anotasi peninjau atas `tujuan[$ref]`. */
    private function anotasi(Document $doc, User $peninjau, string $ref, ?string $komentar): void
    {
        $review = $doc->reviews()->firstOrCreate(
            ['reviewer_id' => $peninjau->id],
            ['revision_round' => 0, 'decision' => 'needs_revision', 'summary' => 'Ada dua hal yang perlu diperbaiki.']
        );

        $review->annotations()->create([
            'section_key' => 'tujuan', 'item_ref' => $ref,
            'severity' => 'minor', 'comment' => $komentar,
        ]);
    }

    /**
     * Catatan sampai ke wizard BERSARANG `section_key` → `item_ref`, bukan datar.
     *
     * Inilah yang dulu hilang: `edit()` cuma `groupBy('section_key')` lalu
     * `pluck('comment')`, jadi catatan atas butir ke-2 jatuh jadi satu butir
     * berlencana "tujuan" di kepala halaman dan pembuat harus menebak sendiri
     * baris mana yang dimaksud. `kutipan` ikut supaya geser indeks — pembuat
     * menyisipkan/menghapus baris selagi merevisi — terlihat, bukan diam.
     */
    public function test_catatan_peninjau_menempel_ke_item_dan_membawa_kutipan(): void
    {
        $doc = $this->draftDitolak();
        $sh = $this->aktorSh();

        $this->anotasi($doc, $sh, '1', 'Sebutkan komponen yang diperiksa.');
        // Baris tanpa komentar memang ada (tanda ✓/✗ JSA hanya memikul verdict).
        $this->anotasi($doc, $sh, '0', null);

        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        );

        $this->assertArrayNotHasKey('0', $props['catatanItem']['tujuan'],
            'anotasi tanpa komentar tak boleh jadi butir kosong di layar');

        $catatan = $props['catatanItem']['tujuan']['1'][0];

        $this->assertSame('Sebutkan komponen yang diperiksa.', $catatan['komentar']);
        $this->assertSame('peninjau', $catatan['sumber']);
        $this->assertSame('Menyeragamkan langkah pemeriksaan.', $catatan['kutipan'],
            'kutipan wajib teks item yang DIKOMENTARI, bukan item pertama');
    }

    /**
     * Masukan sejawat ikut ke wizard — dan penulisnya WAJIB string.
     *
     * Bentuk `catatan_json` sudah `{section_key, item_ref, komentar}` sejak
     * disimpan, jadi yang dulu hilang cuma jalannya ke sini. Yang diuji sekeras
     * itu justru `oleh`: mengirim relasi `user` menghasilkan halaman yang tampak
     * benar sementara `password` & `remember_token` pemberi masukan ikut ke
     * atribut `data-page` — kebocoran Fase 6/7/8 yang persis sama (CLAUDE.md §4).
     */
    public function test_masukan_sejawat_ikut_dan_penulisnya_tak_bocor(): void
    {
        $doc = $this->draftDitolak();
        $rekan = $this->aktorGl('ICTMD', 2);

        $doc->masukanSejawat()->create([
            'user_id' => $rekan->id,
            'ringkasan' => 'Dua catatan kecil.',
            'catatan_json' => [
                ['section_key' => 'tujuan', 'item_ref' => '0', 'komentar' => 'Tambahkan rujukan SMKP-nya.'],
                ['section_key' => 'tujuan', 'item_ref' => '1', 'komentar' => ''],
            ],
        ]);

        $response = $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk();
        $props = $this->propsInertia($response);

        $catatan = $props['catatanItem']['tujuan']['0'][0];

        $this->assertSame('sejawat', $catatan['sumber']);
        $this->assertIsString($catatan['oleh'], 'penulis dikirim sebagai NAMA, bukan model User');
        $this->assertStringContainsString($rekan->name, $catatan['oleh']);
        $this->assertArrayNotHasKey('1', $props['catatanItem']['tujuan'],
            'komentar kosong disaring, sama seperti anotasi peninjau tanpa komentar');

        // Sapuan HTML MENTAH — props tertulis seluruhnya di atribut `data-page`.
        $html = $response->getContent();
        $this->assertStringNotContainsString($rekan->password, $html);
        $this->assertStringNotContainsString('remember_token', $html);
    }

    /**
     * Masukan sejawat hanya untuk yang berhak membacanya.
     *
     * Wizard juga dibuka penonton read-only (SH sedepartemen boleh melihat isi
     * dokumen). Catatan antar-GL bukan miliknya, dan syaratnya harus sama persis
     * dengan halaman `masukan-sejawat.show` — karena itu keduanya memanggil
     * {@see Document::bisaLihatMasukanSejawat()}, bukan menyalin aturannya.
     */
    public function test_masukan_sejawat_tak_terbaca_penonton_tak_berhak(): void
    {
        $doc = $this->draftDitolak();
        $sh = $this->aktorSh();
        $rekan = $this->aktorGl('ICTMD', 2);

        $this->anotasi($doc, $sh, '0', 'Perjelas sasarannya.');
        $doc->masukanSejawat()->create([
            'user_id' => $rekan->id,
            'ringkasan' => null,
            'catatan_json' => [['section_key' => 'tujuan', 'item_ref' => '0', 'komentar' => 'Catatan rekan.']],
        ]);

        $props = $this->propsInertia(
            $this->actingAs($sh)->get(route('documents.edit', $doc))->assertOk()
        );

        $sumber = array_column($props['catatanItem']['tujuan']['0'], 'sumber');

        $this->assertContains('peninjau', $sumber, 'catatan peninjau tetap terbaca');
        $this->assertNotContains('sejawat', $sumber, 'catatan antar-GL bukan milik peninjau');
    }

    /**
     * Catatan atas item yang sudah DIHAPUS tak boleh lenyap tanpa jejak.
     *
     * `item_ref` berbasis posisi, jadi begitu pembuat menghapus barisnya tak ada
     * lagi kotak untuk ditempeli — dan tanpa jaring ini catatannya hilang dari
     * layar tanpa satu pun galat. Ia jatuh ke `catatanYatim`, dengan penunjuk
     * berbahasa manusia ({@see \App\Services\CatatanPerItem::label()}) yang
     * menghitung dari 1 seperti yang terbaca di layar & PDF.
     */
    public function test_catatan_atas_item_yang_hilang_jatuh_ke_daftar_yatim(): void
    {
        $doc = $this->draftDitolak();

        $this->anotasi($doc, $this->aktorSh(), '5', 'Butir ini terlalu umum.');

        $props = $this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.edit', $doc))->assertOk()
        );

        $this->assertNull($props['catatanItem']['tujuan']['5'][0]['kutipan'],
            'kutipan null adalah penanda "itemnya sudah tak ada"');

        $yatim = collect($props['catatanYatim'])->firstWhere('komentar', 'Butir ini terlalu umum.');

        $this->assertNotNull($yatim, 'catatan tanpa baris tempat menempel wajib muncul di kepala halaman');
        $this->assertSame('Item 6', $yatim['item'], 'penunjuk item dihitung dari 1, bukan dari 0');
    }
}
