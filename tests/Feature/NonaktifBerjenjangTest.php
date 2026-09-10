<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Nonaktif berjenjang + pelepasan nomor (PLAN-REVISI-v6 Fase F).
 *
 * Dua hal yang dikunci di sini, dan keduanya mahal kalau salah:
 *   • URUTAN tahapnya — termasuk tahap MD yang DILEWATI bila MD sendiri yang
 *     mengajukan. Alur yang melompat satu tahap tak terlihat dari layar; yang
 *     terlihat cuma dokumen yang mati lebih cepat dari yang seharusnya.
 *   • PELEPASAN nomor — satu-satunya jalan sebuah nomor dokumen mutu kembali ke
 *     kolam. Melepas yang salah berarti dua dokumen berbeda bernomor sama.
 */
class NonaktifBerjenjangTest extends TestCase
{
    use DatabaseTransactions;

    private function gl(): User
    {
        return $this->aktorGl();
    }

    /** Dokumen Berlaku milik GL ICTMD, bernomor final. */
    private function berlaku(string $judul, ?string $nomorFinal = null): Document
    {
        $gl = $this->gl();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
        $doc->update([
            'status' => 'published',
            'published_at' => now(),
            // Nomor sementara IKUT disetel ketika nomor final ditentukan dari
            // luar. Tanpa itu dokumennya memegang dua nomor berbeda, dan
            // `isUnique()` — yang memeriksa `doc_number` MAUPUN
            // `doc_number_final` — menjawab beda dengan kolam penomoran.
            'doc_number' => $nomorFinal ?? $doc->doc_number,
            'doc_number_final' => $nomorFinal ?? $doc->doc_number,
        ]);

        return $doc->fresh();
    }

    private function setuju(User $pemutus, Document $doc): void
    {
        $this->actingAs($pemutus)->post(route('nonaktif.putuskan', $doc), ['keputusan' => 'setuju'])
            ->assertRedirect();
    }

    /** 1. Rantai penuh: GL mengajukan → SH → MD → PJO → mati + nomor dilepas. */
    public function test_rantai_penuh_gl_sh_md_pjo(): void
    {
        $doc = $this->berlaku('SOP Nonaktif Penuh');

        $this->actingAs($this->gl())
            ->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Alat X sudah dipensiunkan.'])
            ->assertRedirect();

        $doc->refresh();
        $this->assertSame('menunggu_nonaktif', $doc->status);
        $this->assertSame('sh', $doc->nonaktif_tahap);
        $this->assertSame($this->gl()->id, $doc->nonaktif_oleh);

        $this->setuju($this->aktorSh(), $doc);
        $this->assertSame('md', $doc->refresh()->nonaktif_tahap, 'SH meneruskan ke MD');

        $this->setuju(User::where('nrp', 'MD-0001')->firstOrFail(), $doc);
        $this->assertSame('pjo', $doc->refresh()->nonaktif_tahap, 'MD meneruskan ke PJO');

        $this->setuju($this->aktorPjo(), $doc);

        $doc->refresh();
        $this->assertSame('obsolete', $doc->status);
        $this->assertSame(Document::OBSOLETE_DINONAKTIFKAN, $doc->obsolete_reason);
        $this->assertNull($doc->nonaktif_tahap, 'tak ada tahap yang tersisa');
    }

    /** 2. MD mengajukan → tahap MD DILEWATI (sh → pjo). */
    public function test_pengajuan_md_melewati_tahap_md(): void
    {
        $md = User::where('nrp', 'MD-0001')->firstOrFail();
        $doc = $this->berlaku('SOP Nonaktif oleh MD');

        $this->actingAs($md)->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Sudah digantikan aturan pusat.'])
            ->assertRedirect();
        $this->assertSame('sh', $doc->refresh()->nonaktif_tahap);

        $this->setuju($this->aktorSh(), $doc);
        $this->assertSame('pjo', $doc->refresh()->nonaktif_tahap, 'tahap MD dilewati');

        $this->setuju($this->aktorPjo(), $doc);
        $this->assertSame('obsolete', $doc->refresh()->status);
    }

    /** 3. Tolak di tahap mana pun → kembali Berlaku, alasan tersimpan, pengaju dikabari. */
    public function test_tolak_mengembalikan_ke_berlaku(): void
    {
        $gl = $this->gl();
        $doc = $this->berlaku('SOP Nonaktif Ditolak');

        $this->actingAs($gl)->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Sudah tak dipakai.'])
            ->assertRedirect();

        $gl->notifications()->delete();

        // Alasan WAJIB saat menolak.
        $this->actingAs($this->aktorSh())
            ->post(route('nonaktif.putuskan', $doc), ['keputusan' => 'tolak'])
            ->assertSessionHasErrors('alasan');
        $this->assertSame('menunggu_nonaktif', $doc->refresh()->status, 'tanpa alasan, tak bergerak');

        $this->actingAs($this->aktorSh())
            ->post(route('nonaktif.putuskan', $doc), [
                'keputusan' => 'tolak', 'alasan' => 'Masih dipakai shift malam.',
            ])->assertRedirect();

        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertNull($doc->nonaktif_tahap);
        $this->assertNull($doc->obsolete_reason, 'penolakan tak melepas nomor');

        $tolakan = Approval::where('document_id', $doc->id)
            ->where('kind', Approval::KIND_NONAKTIF)->where('decision', 'rejected')->firstOrFail();
        $this->assertStringContainsString('shift malam', (string) $tolakan->comment);

        $this->assertSame(1, $gl->fresh()->unreadNotifications()->count(), 'pengaju dikabari');
    }

    /** 4. GL tak boleh mengajukan dokumen buatan GL lain. */
    public function test_gl_bukan_pemilik_ditolak(): void
    {
        $doc = $this->berlaku('SOP Milik GL Lain');

        $glLain = User::create([
            'name' => 'GL Uji Nonaktif', 'nrp' => 'GLNON-0001',
            'jabatan' => 'group_leader', 'department_id' => $this->gl()->department_id,
            'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $glLain->assignRole('group_leader');

        $this->actingAs($glLain->fresh())
            ->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Coba dari GL lain.'])
            ->assertForbidden();

        $this->assertSame('published', $doc->refresh()->status);
    }

    /** 5. SH departemen LAIN tak boleh memutus tahap `sh`. */
    public function test_sh_departemen_lain_ditolak(): void
    {
        $doc = $this->berlaku('SOP Nonaktif Lintas Dept');
        $this->actingAs($this->gl())->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Sudah tak dipakai.']);

        $shLain = User::create([
            'name' => 'SH Uji Dept Lain', 'nrp' => 'SHLAIN-0001',
            'jabatan' => 'section_head',
            'department_id' => Department::where('code', '!=', $this->gl()->department->code)->firstOrFail()->id,
            'password' => bcrypt('x'), 'status' => 'active',
        ]);
        $shLain->assignRole('section_head');

        $this->actingAs($shLain->fresh())
            ->post(route('nonaktif.putuskan', $doc), ['keputusan' => 'setuju'])
            ->assertForbidden();

        $this->assertSame('sh', $doc->refresh()->nonaktif_tahap, 'tahapnya tak bergerak');
    }

    /**
     * 6. Nomor dilepas benar-benar KEMBALI ke kolam saat dokumen dinonaktifkan.
     *
     * Yang dikunci di sini jaminan pelepasannya, bukan "nomor berikutnya persis
     * nomor ini". Klaim yang kedua itu hanya benar bila tak ada celah lain yang
     * lebih kecil di kolam — dan basis data sungguhan SELALU punya celah, sebab
     * `generateFinal()` menghitung nomor FINAL sementara draft memegang nomor
     * SEMENTARA (CLAUDE.md §8). Test lama karena itu merah bukan karena
     * pelepasannya gagal, melainkan karena kolam menawarkan celah lain lebih
     * dulu.
     *
     * Tiga hal yang diperiksa, dan ketiganya bebas dari isi basis data:
     *   a. selama BERLAKU nomornya ditahan — tak bisa dipakai ulang;
     *   b. sesudah dinonaktifkan nomornya bebas lagi;
     *   c. kolam tak pernah melompati nomor yang sudah dilepas.
     */
    public function test_nomor_dilepas_dipakai_dokumen_baru(): void
    {
        $gl = $this->gl();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $kolam = app(DocumentNumberService::class);

        $doc = $this->berlaku('SOP Pelepas Nomor');
        $nomor = $doc->doc_number_final;

        // (a) Ditahan selama dokumennya masih berlaku.
        $this->assertFalse($kolam->isUnique($nomor), 'nomor dokumen BERLAKU tak boleh dianggap bebas');

        $doc->update([
            'status' => 'obsolete',
            'obsolete_reason' => Document::OBSOLETE_DINONAKTIFKAN,
        ]);

        // (b) Bebas lagi — dan boleh diketik tangan; tanpa ini pelepasannya
        // setengah jalan.
        $this->assertTrue($kolam->isUnique($nomor), 'nomor yang dilepas harus bebas kembali');

        // (c) Kolam tak melompatinya. Sama persis bila tak ada celah lain;
        // lebih kecil bila kolam masih menyimpan celah yang lebih tua.
        $this->assertLessThanOrEqual($nomor, $kolam->generateFinal($type, $gl->department),
            'kolam tak boleh melompati nomor yang baru dilepas');
    }

    /** 7. Aktifkan kembali dokumen bernomor dilepas → WAJIB nomor baru. */
    public function test_aktifkan_kembali_wajib_nomor_baru(): void
    {
        $admin = User::permission('user.manage')->firstOrFail();
        $doc = $this->berlaku('SOP Dilepas Lalu Diaktifkan');
        $doc->update(['status' => 'obsolete', 'obsolete_reason' => Document::OBSOLETE_DINONAKTIFKAN]);

        // Tanpa `nomor_baru`: ditolak, dokumen tetap mati.
        $this->actingAs($admin)->post(route('documents.restoreObsolete', $doc))->assertRedirect();
        $this->assertSame('obsolete', $doc->refresh()->status);

        $this->actingAs($admin)->post(route('documents.restoreObsolete', $doc), ['nomor_baru' => 1])
            ->assertRedirect();

        $doc->refresh();
        $this->assertSame('published', $doc->status);
        $this->assertNull($doc->obsolete_reason, 'dokumen Berlaku menahan nomornya lagi');
    }

    /** 8. Dokumen yang SEDANG DIREVISI tak boleh diajukan nonaktif. */
    public function test_sedang_direvisi_ditolak(): void
    {
        $doc = $this->berlaku('SOP Sedang Direvisi');
        $doc->update(['status' => 'sedang_direvisi']);

        $this->actingAs($this->gl())
            ->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Coba saat direvisi.'])
            ->assertStatus(422);

        $this->assertSame('sedang_direvisi', $doc->refresh()->status);
    }

    /**
     * 9. Obsolete hasil REVISI tetap 'revisi' dan TIDAK melepas nomor.
     *
     * Inilah pembeda yang membuat seluruh Fase F bermakna: dua jalan menuju
     * status yang sama, hanya satu di antaranya mengembalikan nomor.
     */
    public function test_obsolete_karena_revisi_tidak_melepas_nomor(): void
    {
        $gl = $this->gl();
        $lama = $this->berlaku('SOP Versi Lama');
        $lama->update(['status' => 'sedang_direvisi']);

        $baru = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, 'SOP Versi Baru'
        );
        $baru->update([
            'revises_document_id' => $lama->id,
            'doc_number' => $lama->doc_number,
            'status' => 'pending_approval',
            'approver_id' => $this->aktorPjo()->id,
        ]);

        $this->actingAs($this->aktorPjo())
            ->post(route('approvals.store', $baru), ['decision' => 'approve'])
            ->assertRedirect();

        $lama->refresh();
        $this->assertSame('obsolete', $lama->status);
        $this->assertSame(Document::OBSOLETE_REVISI, $lama->obsolete_reason);
        $this->assertFalse($lama->nomorDilepas(), 'versi lama tetap menahan nomornya');
    }

    /**
     * 10. Layar & menu sepakat dengan kodenya.
     *
     * Sebagian besar bug perizinan di repo ini muncul sebagai TOMBOL YANG
     * TERLIHAT tapi 403 saat ditekan. Yang diperiksa di sini persis pasangan
     * itu: siapa melihat menunya, dan siapa boleh membuka halamannya.
     */
    public function test_menu_dan_halaman_sepakat(): void
    {
        $doc = $this->berlaku('SOP Untuk Uji Layar');
        $this->actingAs($this->gl())->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Sudah tak dipakai.']);

        $berwenang = [
            'SH' => $this->aktorSh(),
            'MD' => User::where('nrp', 'MD-0001')->firstOrFail(),
            'PJO' => $this->aktorPjo(),
        ];

        /*
        | Yang dicari TAUTAN menunya, bukan frasa "Persetujuan Nonaktif".
        | Frasa itu muncul juga di komentar CSS `layouts/app.blade.php`, yang
        | ikut terkirim ke SETIAP halaman untuk SETIAP pengguna — sehingga
        | assertSee-nya dulu selalu hijau dan assertDontSee-nya selalu merah,
        | dua-duanya tanpa ada hubungannya dengan wewenang siapa pun.
        |
        | Sejak Fase 8 sidebarnya datang sebagai props `navigation`, jadi
        | tautannya dibaca dari sana (menuSidebar) alih-alih dari markup.
        */
        $tautanMenu = route('nonaktif.index');

        foreach ($berwenang as $peran => $u) {
            $this->actingAs($u)->get(route('nonaktif.index'))->assertOk();
            $this->assertTrue($this->adaMenu($u, $tautanMenu), "{$peran}: menu tampil");
        }

        // GL & Non-Staff: bukan pemutus di tahap mana pun — menunya tak ada, dan
        // alamatnya tertutup.
        // Non-Staff dibuat sendiri, bukan `User::where('jabatan','staff')->first()`:
        // kunci `staff` juga dipakai akun Admin (CLAUDE.md §6), jadi penghuni
        // pertama basis data bisa saja justru orang yang BOLEH melihat menunya.
        foreach (['GL' => $this->gl(), 'Non-Staff' => $this->aktorNonStaff()] as $peran => $u) {
            $this->actingAs($u)->get(route('nonaktif.index'))->assertForbidden();
            $this->assertFalse($this->adaMenu($u, $tautanMenu), "{$peran}: menu tak tampil");
        }

        // Pemilik dokumen melihat tombol pengajuannya (dokumen lain, yang masih
        // Berlaku) — tombolnya hanya ada di dokumen yang memang bisa diajukan.
        // Tombolnya kini digambar TSX bila `boleh_revisi`; syarat yang SAMA
        // dipakai tombol Revisi dan Ajukan Nonaktif (Fase F).
        $lain = $this->berlaku('SOP Masih Berlaku');
        $baris = collect($this->propsInertia(
            $this->actingAs($this->gl())->get(route('documents.published'))->assertOk()
        )['documents']['data'])->keyBy('id');

        $this->assertTrue($baris[$lain->id]['boleh_revisi'], 'dokumen Berlaku bisa diajukan nonaktif');
        $this->assertSame($lain->displayNumber(), $baris[$lain->id]['nomor']);
        $this->assertFalse(
            $baris[$doc->id]['boleh_revisi'],
            'dokumen yang sudah diajukan nonaktif tak menawarkan tombolnya lagi'
        );
    }

    /** Menu sidebar ber-href $href terlihat oleh $sebagai? */
    private function adaMenu(User $sebagai, string $href): bool
    {
        $res = $this->actingAs($sebagai)->get(route('documents.published'))->assertOk();

        return collect($this->menuSidebar($res))->contains('href', $href);
    }
}
