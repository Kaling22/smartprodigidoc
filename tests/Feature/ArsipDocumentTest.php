<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentPurger;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dokumen LAMA / arsip (butir 0): PDF yang sudah terlanjur ada, didaftarkan apa
 * adanya dan langsung Berlaku.
 *
 * Yang dijaga di sini adalah janji-janji yang rusak TANPA GEJALA di layar:
 *
 *  1. Tombol PDF menyajikan BERKAS ASLI, bukan hasil DomPDF. Dokumen arsip tak
 *     punya `contents`, jadi mesin cetak untuknya menghasilkan PDF kosong berkop
 *     — yang tampak seperti "dokumennya memang begitu", bukan seperti bug.
 *  2. Jalur MOBILE menempuh cabang yang sama. Menambal sisi web saja adalah
 *     kesalahan yang mustahil terlihat dari kursi pengembang.
 *  3. Berkasnya TIDAK bisa diambil tanpa login. Ia dokumen mutu utuh, bukan
 *     foto lampiran.
 *  4. Memusnahkan dokumen ikut membuang berkasnya dari disk.
 */
class ArsipDocumentTest extends TestCase
{
    use DatabaseTransactions;

    /** PDF terkecil yang sah — cukup untuk lolos pemeriksaan magic bytes. */
    private function pdfPalsu(string $nama = 'lama.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n%%EOF");
    }

    private function gl(): User
    {
        // Profil akses penuh: berkas ini menguji arsip/catatan/unggahan,
        // bukan wewenang menyusun (PLAN-AKSES-v7 Fase 4).
        return $this->berprofilPenuh($this->aktorGl());
    }

    /** Nomor yang pasti belum dipakai, supaya test tak bergantung isi DB nyata. */
    private function nomorBaru(): string
    {
        return 'ARSIP-UJI-'.Str::upper(Str::random(8));
    }

    /**
     * @param  array<string, mixed>  $ubah
     */
    private function daftarkan(User $gl, array $ubah = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($gl)->post(route('documents.arsip.store'), array_merge([
            'document_type_id' => DocumentType::where('code', 'SOP')->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Prosedur Lama Uji',
            'doc_number' => $this->nomorBaru(),
            'edisi' => 2,
            'no_revisi' => 3,
            'tanggal_efektif' => '2019-05-17',
            'berkas' => $this->pdfPalsu(),
        ], $ubah));
    }

    private function terakhirDidaftarkan(): Document
    {
        return Document::whereNotNull('arsip_path')->latest('id')->firstOrFail();
    }

    /**
     * GL mendaftarkan dokumen lama → LANGSUNG Berlaku, bernomor lamanya, dan
     * `doc_number_final` ikut terisi.
     *
     * Nomor final itu bukan hiasan: `DocumentNumberService::generateFinal()`
     * membaca kolom itu untuk menahan nomor yang pernah terbit. Tanpa terisi,
     * dokumen SmartPro berikutnya boleh mengambil nomor yang sama.
     */
    public function test_gl_mendaftarkan_dokumen_lama_langsung_berlaku(): void
    {
        Storage::fake('local');
        $gl = $this->gl();

        // SOP/SP/IK kini singgah dulu di lembar Catatan Revisi (Fase H) — status
        // dokumennya tak berubah, hanya halaman berikutnya yang berbeda.
        $doc0 = Document::whereNotNull('arsip_path')->latest('id')->first();
        $this->daftarkan($gl)->assertRedirect(route('documents.arsip.catatan',
            Document::whereNotNull('arsip_path')->where('id', '>', $doc0?->id ?? 0)->latest('id')->firstOrFail()));

        $doc = $this->terakhirDidaftarkan();

        $this->assertSame('published', $doc->status);
        $this->assertTrue($doc->isArsip());
        $this->assertSame($doc->doc_number, $doc->doc_number_final, 'Nomor final wajib ikut terkunci.');
        $this->assertSame('2', $doc->edisi);
        $this->assertSame(3, $doc->no_revisi);
        $this->assertSame('2019-05-17', $doc->published_at->format('Y-m-d'),
            'Tanggal terbit = tanggal efektif dokumen lamanya, bukan hari impor.');
        Storage::disk('local')->assertExists($doc->arsip_path);
    }

    /**
     * Berkas ditulis ke disk `local` (privat), BUKAN `public`.
     *
     * routes/web.php menyajikan storage/app/public tanpa login sama sekali —
     * satu baris salah disk di sini membocorkan seluruh arsip dokumen mutu ke
     * siapa pun yang menebak URL-nya, dan tak ada satu pun gejala di layar.
     */
    public function test_berkas_disimpan_di_disk_privat_bukan_publik(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->daftarkan($this->gl());
        $doc = $this->terakhirDidaftarkan();

        $this->assertStringStartsWith('arsip/', $doc->arsip_path);
        Storage::disk('local')->assertExists($doc->arsip_path);
        Storage::disk('public')->assertMissing($doc->arsip_path);
    }

    /** Berkas ber-ekstensi .pdf tapi isinya bukan PDF → ditolak (magic bytes). */
    public function test_berkas_menyamar_pdf_ditolak(): void
    {
        Storage::fake('local');

        $this->daftarkan($this->gl(), [
            'berkas' => UploadedFile::fake()->createWithContent('palsu.pdf', '<?php echo "halo";'),
        ])->assertSessionHasErrors('berkas');
    }

    /** Nomor yang sudah dipakai dokumen lain → ditolak, bukan diterima diam-diam. */
    public function test_nomor_kembar_ditolak(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $nomor = $this->nomorBaru();

        $this->daftarkan($gl, ['doc_number' => $nomor]);
        $this->daftarkan($gl, ['doc_number' => $nomor])->assertSessionHasErrors('doc_number');
    }

    /**
     * Tombol PDF menyajikan BERKAS ASLI, bukan hasil render DomPDF.
     *
     * Ditandai lewat isi berkasnya sendiri: kalau cabang arsip hilang, yang
     * kembali adalah PDF bangkitan yang TIDAK memuat penanda ini.
     */
    public function test_pdf_web_menyajikan_berkas_asli(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $tanda = 'PENANDA-'.Str::random(10);

        $this->daftarkan($gl, [
            'berkas' => UploadedFile::fake()->createWithContent('lama.pdf', "%PDF-1.4\n% {$tanda}\n%%EOF"),
        ]);
        $doc = $this->terakhirDidaftarkan();

        $isi = $this->actingAs($gl)->get(route('documents.pdf', $doc))->assertOk()->streamedContent();

        $this->assertStringContainsString($tanda, $isi);
    }

    /**
     * Jalur MOBILE menempuh cabang yang sama.
     *
     * Ini test yang paling mudah tak ditulis dan paling mahal bila absen: web
     * tampak benar, dan yang menemukan dokumen kosong adalah orang di lapangan.
     */
    public function test_pdf_mobile_menyajikan_berkas_asli(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $tanda = 'PENANDA-'.Str::random(10);

        $this->daftarkan($gl, [
            'berkas' => UploadedFile::fake()->createWithContent('lama.pdf', "%PDF-1.4\n% {$tanda}\n%%EOF"),
        ]);
        $doc = $this->terakhirDidaftarkan();

        $isi = $this->actingAs($gl, 'sanctum')
            ->get("/api/files/sop/{$doc->id}.pdf")
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($tanda, $isi);
    }

    /** Berkas arsip tak bisa diambil lewat rute berkas publik. */
    public function test_berkas_arsip_tak_terjangkau_tanpa_login(): void
    {
        Storage::fake('local');
        $this->daftarkan($this->gl());
        $doc = $this->terakhirDidaftarkan();

        // `actingAs` di daftarkan() masih memegang guard-nya — tanpa dilepas,
        // permintaan di bawah tetap datang sebagai GL dan test ini lulus tanpa
        // pernah menguji apa pun.
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->flushSession();

        $this->get('/storage/'.$doc->arsip_path)->assertNotFound();
        $this->get(route('documents.pdf', $doc))->assertRedirect(route('login'));
    }

    /**
     * Wizard dialihkan untuk dokumen arsip.
     *
     * Tanpa ini, GL membuka formulir berbab KOSONG atas dokumen yang isinya ada
     * di berkasnya — lalu mengisinya, dan isian itu tak akan pernah muncul di
     * PDF mana pun.
     */
    public function test_wizard_dialihkan_untuk_dokumen_arsip(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $this->daftarkan($gl);
        $doc = $this->terakhirDidaftarkan();

        $this->actingAs($gl)->get(route('documents.edit', $doc))
            ->assertRedirect(route('documents.show', $doc));
    }

    /** Pengunggah boleh memperbaiki metadata & mengganti berkasnya. */
    public function test_pengunggah_memperbaiki_metadata_dan_berkas(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $this->daftarkan($gl);
        $doc = $this->terakhirDidaftarkan();
        $berkasLama = $doc->arsip_path;

        $this->actingAs($gl)->put(route('documents.arsip.update', $doc), [
            'title' => 'Judul Sudah Diperbaiki',
            'doc_number' => $doc->doc_number,
            'edisi' => 3,
            'no_revisi' => 1,
            'tanggal_efektif' => '2020-01-02',
            'berkas' => $this->pdfPalsu('pengganti.pdf'),
        ])->assertRedirect(route('documents.published'));

        $doc->refresh();

        $this->assertSame('Judul Sudah Diperbaiki', $doc->title);
        $this->assertSame('3', $doc->edisi);
        $this->assertSame(1, $doc->no_revisi);
        $this->assertNotSame($berkasLama, $doc->arsip_path, 'Berkas pengganti harus punya jalur baru.');
        Storage::disk('local')->assertMissing($berkasLama);
        Storage::disk('local')->assertExists($doc->arsip_path);
    }

    /**
     * Nomor final ikut berubah saat nomor diperbaiki.
     *
     * `displayNumber()` mendahulukan `doc_number_final`; kalau hanya
     * `doc_number` yang diperbarui, perbaikannya tersimpan di basis data tapi
     * layar tetap menampilkan nomor lamanya — gejala yang membuat orang
     * mengulangi perbaikan yang sama berkali-kali.
     */
    public function test_perbaikan_nomor_ikut_mengubah_nomor_final(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $this->daftarkan($gl);
        $doc = $this->terakhirDidaftarkan();
        $nomorBaru = $this->nomorBaru();

        $this->actingAs($gl)->put(route('documents.arsip.update', $doc), [
            'title' => $doc->title,
            'doc_number' => $nomorBaru,
            'edisi' => 1,
            'no_revisi' => 0,
        ]);

        $this->assertSame($nomorBaru, $doc->refresh()->displayNumber());
    }

    /** Orang lain (bukan pengunggah, bukan Admin) tak boleh memperbaikinya. */
    public function test_orang_lain_tak_boleh_memperbaiki(): void
    {
        Storage::fake('local');
        $this->daftarkan($this->gl());
        $doc = $this->terakhirDidaftarkan();

        $this->actingAs($this->aktorNonStaff())
            ->get(route('documents.arsip.edit', $doc))
            ->assertForbidden();
    }

    /** Admin boleh memperbaiki dokumen arsip departemen mana pun. */
    public function test_admin_boleh_memperbaiki(): void
    {
        Storage::fake('local');
        $this->daftarkan($this->gl());
        $doc = $this->terakhirDidaftarkan();

        $this->actingAs(User::where('nrp', 'ADM-0001')->firstOrFail())
            ->get(route('documents.arsip.edit', $doc))
            ->assertOk();
    }

    /**
     * Memusnahkan dokumen arsip ikut membuang berkasnya.
     *
     * Tanpa ini berkasnya menumpuk selamanya di disk, tanpa satu pun baris
     * database yang menunjuknya — dan berkas arsip bisa puluhan MB.
     */
    public function test_musnahkan_ikut_membuang_berkas_arsip(): void
    {
        Storage::fake('local');
        $this->daftarkan($this->gl());
        $doc = $this->terakhirDidaftarkan();
        $berkas = $doc->arsip_path;

        app(DocumentPurger::class)->purge($doc, 'uji pemusnahan arsip');

        Storage::disk('local')->assertMissing($berkas);
    }

    /**
     * Halaman yang menampilkan dokumen arsip tidak meledak.
     *
     * Dokumen arsip TIDAK punya contents, reviewer, maupun approver — tiga hal
     * yang diandaikan ada oleh layar-layar ini. Satu `->name` pada relasi null
     * sudah cukup untuk 500.
     */
    public function test_halaman_dokumen_arsip_tidak_meledak(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $this->daftarkan($gl);
        $doc = $this->terakhirDidaftarkan();

        $this->actingAs($gl)->get(route('documents.show', $doc))->assertOk();
        $this->actingAs($gl)->get(route('documents.published'))->assertOk();
        $this->actingAs($gl)->get(route('documents.index', ['sumber' => 'unggahan']))->assertOk()
            ->assertSee($doc->title);
        $this->actingAs($gl)->get(route('documents.index', ['sumber' => 'wizard']))->assertOk()
            ->assertDontSee($doc->title);
    }

    /**
     * Merevisi dokumen arsip berjalan lewat jalur SmartPro yang SUDAH ADA —
     * inilah yang membuat opsi (a) murah: PDF lama tak perlu diedit, ia jadi
     * versi lama, dan versi barunya dokumen SmartPro penuh.
     *
     * Dua hal yang dikunci: draft revisinya BUKAN arsip (versi baru disusun di
     * wizard, bukan diunggah), dan snapshot versi lamanya MENGINGAT berkasnya —
     * tanpa itu riwayat versi kehilangan satu-satunya petunjuk ke dokumen asli.
     */
    public function test_revisi_dokumen_arsip_melahirkan_draft_wizard_biasa(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $this->daftarkan($gl);
        $arsip = $this->terakhirDidaftarkan();
        $berkas = $arsip->arsip_path;

        $draft = app(\App\Services\DocumentService::class)->requestRevision(
            $arsip, $this->aktorSh()
        );

        $this->assertFalse($draft->isArsip(), 'Versi baru disusun di wizard, bukan diunggah.');
        $this->assertNull($draft->arsip_path);
        $this->assertSame($arsip->doc_number, $draft->doc_number, 'Nomor lama diwariskan.');
        $this->assertSame('sedang_direvisi', $arsip->refresh()->status);
        $this->assertSame($berkas, $arsip->versions()->latest('id')->first()->snapshot_json['arsip_path']);

        // Draft revisinya membuka wizard seperti dokumen SmartPro lain — tak
        // ikut terkena pengalihan dokumen arsip.
        $this->actingAs($gl)->get(route('documents.edit', $draft))->assertOk();
    }

    /** Departemen dipaksa dari user: GL tak bisa mendaftarkan untuk dept lain. */
    public function test_gl_tak_bisa_mendaftarkan_untuk_departemen_lain(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $lain = Department::where('id', '!=', $gl->department_id)->firstOrFail();

        $this->daftarkan($gl, ['department_id' => $lain->id]);

        $this->assertSame($gl->department_id, $this->terakhirDidaftarkan()->department_id);
    }

    /* ============== Dialog "Nomor Sudah Dipakai" (butir 4) ============== */

    /**
     * Nomor bentrok tak lagi berhenti sebagai galat merah: server ikut
     * menghitung nomor bebas dan mengirimnya sebagai prop `nomorSaran`.
     *
     * Dirantai sampai halamannya digambar ulang, bukan berhenti di flash —
     * yang gagal diam-diam justru sambungannya: flash yang tak pernah diambil
     * `DocumentController::create` menghasilkan dialog yang tak pernah muncul,
     * tanpa satu pun galat.
     *
     * Sarannya juga diperiksa benar-benar BEBAS. Nomor saran yang ternyata
     * ikut bentrok hanya memindahkan kebuntuan satu langkah ke depan.
     */
    public function test_nomor_bentrok_mengirim_saran_nomor_bebas(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $nomor = $this->nomorBaru();
        $create = route('documents.create', ['type' => 'SOP']);

        $this->daftarkan($gl, ['doc_number' => $nomor]);

        $this->from($create)->actingAs($gl)
            ->post(route('documents.arsip.store'), [
                'document_type_id' => DocumentType::where('code', 'SOP')->firstOrFail()->id,
                'department_id' => $gl->department_id,
                'title' => 'Prosedur Lama Bentrok',
                'doc_number' => $nomor,
                'edisi' => 1,
                'no_revisi' => 0,
                'berkas' => $this->pdfPalsu(),
            ])
            ->assertSessionHasErrors('doc_number')
            ->assertSessionHas('nomorSaran');

        $saran = $this->propsInertia(
            $this->actingAs($gl)->get($create)->assertOk()
        )['nomorSaran'];

        $this->assertNotNull($saran, 'prop nomorSaran wajib sampai ke halaman Create');
        $this->assertNotSame($nomor, $saran);
        $this->assertTrue(
            app(\App\Services\DocumentNumberService::class)->isUnique($saran),
            "nomor saran {$saran} ternyata sudah dipakai"
        );
    }

    /**
     * F6b: `errors.doc_number` dan `nomorSaran` wajib tiba pada RESPONS YANG
     * SAMA (redirect-back diikuti sampai halaman Create digambar ulang) —
     * bukan cuma keduanya ada di sesi lewat dua request terpisah. Modal
     * bentrok di `Create.tsx` dibaca dari satu render yang sama; kalau
     * keduanya tak pernah bersamaan, dialognya tak pernah bisa dibuka.
     */
    public function test_nomor_bentrok_errors_dan_saran_tiba_bersamaan(): void
    {
        Storage::fake('local');
        $gl = $this->gl();
        $nomor = $this->nomorBaru();

        $this->daftarkan($gl, ['doc_number' => $nomor]);

        $props = $this->propsInertia(
            $this->from(route('documents.create', ['type' => 'SOP']))
                ->actingAs($gl)
                ->followingRedirects()
                ->post(route('documents.arsip.store'), [
                    'document_type_id' => DocumentType::where('code', 'SOP')->firstOrFail()->id,
                    'department_id' => $gl->department_id,
                    'title' => 'Prosedur Lama Bentrok Bersamaan',
                    'doc_number' => $nomor,
                    'edisi' => 1,
                    'no_revisi' => 0,
                    'berkas' => $this->pdfPalsu(),
                ])
                ->assertOk()
        );

        $errors = (array) ($props['errors'] ?? []);
        $this->assertNotNull($errors['doc_number'] ?? null, 'errors.doc_number wajib ikut respons yang sama');
        $this->assertNotNull($props['nomorSaran'] ?? null, 'nomorSaran wajib ikut respons yang sama');
    }

    /**
     * "Pakai nomor otomatis" = sakelar manual dimatikan lalu kiriman diulang.
     * Hasilnya draft bernomor bangkitan server dengan `doc_number_manual`
     * MATI — bukan nomor bentrok yang diketik ulang diam-diam.
     */
    public function test_pakai_nomor_otomatis_menghasilkan_draft_bernomor_bangkitan(): void
    {
        $gl = $this->gl();
        $bentrok = $this->nomorBaru();

        $this->actingAs($gl)->post(route('documents.store'), [
            'document_type_id' => DocumentType::where('code', 'SOP')->firstOrFail()->id,
            'department_id' => $gl->department_id,
            'title' => 'Prosedur Nomor Otomatis Uji',
            'doc_number_manual' => 0,
            'doc_number' => $bentrok,
        ])->assertSessionHasNoErrors();

        $draft = Document::where('title', 'Prosedur Nomor Otomatis Uji')->latest('id')->firstOrFail();

        $this->assertFalse((bool) $draft->doc_number_manual);
        $this->assertNotSame($bentrok, $draft->doc_number);
        $this->assertStringContainsString('-SOP-', $draft->doc_number);
    }
}
