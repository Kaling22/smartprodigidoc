<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Matriks wewenang masukan & revisi (PLAN-REVISI-v6 Fase C §C.1).
 *
 * Fase itu MENCABUT izin yang aktif dipakai di produksi, jadi yang dikunci di
 * sini bukan satu jalur melainkan seluruh matriksnya — empat kemampuan × delapan
 * peran. Satu tabel, satu test: kalau kelak ada yang menggeser satu sel, yang
 * merah adalah selnya, lengkap dengan nama peran & kemampuannya.
 *
 * | Peran            | Lihat | Balas | Revisi              | Adopsi |
 * |------------------|-------|-------|---------------------|--------|
 * | GL (pemilik)     |  ✅   |  ✅   | ✅                  |  ✅    |
 * | GL (bukan)       |  ✅   |  ✅*  | ❌ (bukan buatannya)|  ❌    |
 * | MD               |  ✅   |  ✅   | ✅ 7 dept           |  ✅    |
 * | SH / DH          |  ✅   |  ❌   | ❌                  |  ❌    |
 * | PJO              |  ✅   |  ❌   | ❌                  |  ❌    |
 * | Non-Staff        | ✅ª   |  ❌   | ❌                  |  ❌    |
 * | Admin            |  ✅   |  ✅   | ✅                  |  ✅    |
 *
 * * GL sedepartemen; GL departemen lain tak boleh (dikunci DocumentFeedbackTest).
 * ª hanya kirimannya sendiri.
 */
class IzinRevisiMasukanTest extends TestCase
{
    use DatabaseTransactions;

    private function dokumenBerlaku(User $gl, string $judul = 'SOP Uji Matriks Izin'): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        return $doc->refresh();
    }

    private function masukan(Document $doc, User $pengirim): DocumentFeedback
    {
        return DocumentFeedback::create([
            'feedback_number' => 'MSK-'.now()->format('Y').'-9'.random_int(100, 999),
            'document_id' => $doc->id,
            'user_id' => $pengirim->id,
            'isi' => 'Masukan untuk menguji matriks izin.',
            'status' => 'baru',
        ]);
    }

    /** Matriks §C.1, dijalankan sebagai PREDIKAT — bukan lewat HTTP. */
    public function test_matriks_wewenang_utuh(): void
    {
        $gl = $this->aktorGl();
        $nonStaff = $this->aktorNonStaff();
        $doc = $this->dokumenBerlaku($gl);
        $masukan = $this->masukan($doc, $nonStaff);
        $masukan->setRelation('document', $doc);

        // GL sedepartemen yang BUKAN pembuatnya — dibuat di sini karena tak ada
        // di seeder, dan tanpa dia batas "hanya dokumen buatannya" tak teruji.
        $glLain = $this->peninjauBersih($gl);

        $matriks = [
            // [pengguna, boleh membalas, boleh merevisi, boleh mengadopsi]
            'GL pemilik' => [$gl, true, true, true],
            'GL bukan pemilik' => [$glLain, true, false, false],
            'MD' => [User::peninjauMd()->firstOrFail(), true, true, true],
            'SH' => [$this->aktorSh(), false, false, false],
            'DH' => [$this->aktorDh(), false, false, false],
            'PJO' => [$this->aktorPjo(), false, false, false],
            'Non-Staff' => [$nonStaff, false, false, false],
            'Admin' => [User::where('nrp', 'ADM-0001')->firstOrFail(), true, true, true],
        ];

        foreach ($matriks as $peran => [$u, $balas, $revisi, $adopsi]) {
            $this->assertSame($balas, $masukan->bisaDibalasOleh($u), "{$peran}: membalas masukan");
            $this->assertSame($revisi, $doc->bisaDirevisiOleh($u), "{$peran}: memulai revisi");
            $this->assertSame($adopsi, $masukan->bisaDiadopsiOleh($u), "{$peran}: mengadopsi masukan");
        }

        // Kolom "Lihat": semua KECUALI Non-Staff melihat masukan orang lain.
        foreach ($matriks as $peran => [$u]) {
            $this->assertSame(
                $peran !== 'Non-Staff', $u->dashboardPenuh(),
                "{$peran}: melihat masukan orang lain",
            );
        }
    }

    /**
     * Yang sama, lewat HTTP: predikat yang benar tapi rute yang masih dijaga
     * izin lama akan tetap meloloskan orang yang salah.
     */
    public function test_rute_revisi_menolak_yang_bukan_pemiliknya(): void
    {
        $gl = $this->aktorGl();
        $doc = $this->dokumenBerlaku($gl, 'SOP Uji Rute Revisi');
        $glLain = $this->peninjauBersih($gl);

        foreach ([
            'SH' => $this->aktorSh(),
            'DH' => $this->aktorDh(),
            'PJO' => $this->aktorPjo(),
            'GL bukan pemilik' => $glLain,
        ] as $peran => $u) {
            $this->actingAs($u)
                ->post(route('documents.requestRevision', $doc), ['alasan' => 'Coba dari '.$peran])
                ->assertForbidden();
            $this->actingAs($u)->post(route('nonaktif.ajukan', $doc), ['alasan' => 'Coba dari '.$peran])
                ->assertForbidden();
        }

        $this->assertSame('published', $doc->refresh()->status, 'tak satu pun yang menembus');

        // Pemiliknya sendiri: lolos.
        $this->actingAs($gl)
            ->post(route('documents.requestRevision', $doc), ['alasan' => 'Menyesuaikan prosedur.'])
            ->assertRedirect();
        $this->assertSame('sedang_direvisi', $doc->refresh()->status);
    }

    /** MD menembus tujuh departemen — lingkupnya dari `document.view_all`. */
    public function test_md_merevisi_dokumen_departemen_mana_pun(): void
    {
        $gl = $this->aktorGl();
        $md = User::peninjauMd()->firstOrFail();

        // Dokumen milik departemen LAIN — itulah yang membedakan MD dari GL.
        $lain = Department::where('id', '!=', $md->department_id)->firstOrFail();
        $doc = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $lain, 'SOP Uji Lintas Dept MD',
        );
        $doc->update(['status' => 'published', 'published_at' => now()]);
        $doc->refresh();

        $this->assertNotSame($doc->department_id, $md->department_id, 'prasyarat: beda departemen');

        $this->actingAs($md)
            ->post(route('documents.requestRevision', $doc), ['alasan' => 'Sistematika penulisan perlu dirapikan.'])
            ->assertRedirect();

        $this->assertSame('sedang_direvisi', $doc->refresh()->status);
    }

    /**
     * D3 — alasan revisi dikirim ke SH/DH departemen itu DAN MD, sebab sejak
     * revisi dimulai GL merekalah yang tak lagi otomatis tahu dokumennya
     * sedang diubah.
     */
    public function test_alasan_revisi_dikabarkan_ke_head_dan_md(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $md = User::peninjauMd()->firstOrFail();
        $doc = $this->dokumenBerlaku($gl, 'SOP Uji Kabar Revisi');

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Langkah 5 tak lagi sesuai kondisi pit.',
        ])->assertRedirect();

        foreach ([$sh, $md] as $penerima) {
            Notification::assertSentTo(
                $penerima, DocumentNotification::class,
                fn ($n) => str_contains($n->catatan ?? '', 'Langkah 5 tak lagi sesuai kondisi pit.'),
            );
        }
    }

    /** Izin baru benar-benar tersemat pada peran yang dituju §C.3. */
    public function test_izin_terpasang_pada_peran_yang_dituju(): void
    {
        /*
        | Yang diuji di sini memang PERAN, bukan orang — jadi kuncinya nama peran
        | dan aktornya dibungkus closure. `MD-0001` & `ADM-0001` tetap dicari dari
        | basis data: keduanya masih ada (PLAN-AKSES-v8 §3), dan akun MD memang
        | akun BERSAMA yang tak punya padanan "satu per peran".
        */
        $harapan = [
            'Group Leader' => [fn () => $this->aktorGl(), ['document.request_revision' => true, 'document.feedback_respond' => true]],
            'Management Development' => [fn () => User::where('nrp', 'MD-0001')->firstOrFail(), ['document.request_revision' => true, 'document.feedback_respond' => true]],
            'Section Head' => [fn () => $this->aktorSh(), ['document.request_revision' => false, 'document.feedback_respond' => false]],
            'Departemen Head' => [fn () => $this->aktorDh(), ['document.request_revision' => false, 'document.feedback_respond' => false]],
            'Pimpinan/PJO' => [fn () => $this->aktorPjo(), ['document.request_revision' => false, 'document.feedback_respond' => false]],
            'Non-Staff' => [fn () => $this->aktorNonStaff(), ['document.request_revision' => false, 'document.feedback_respond' => false]],
            'Admin IT' => [fn () => User::where('nrp', 'ADM-0001')->firstOrFail(), ['document.request_revision' => true, 'document.feedback_respond' => true]],
        ];

        foreach ($harapan as $peran => [$aktor, $izin]) {
            $u = $aktor();
            foreach ($izin as $nama => $boleh) {
                $this->assertSame($boleh, $u->can($nama), "{$peran} terhadap {$nama}");
            }
        }
    }
}
