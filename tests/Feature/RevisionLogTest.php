<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Fase 3: draft revisi Tipe B mendapat langkah wizard ekstra "Log Revisi"
 * (form lembar CATATAN REVISI); Simpan/Kirim pindah ke langkah itu; barisnya
 * terakumulasi lintas revisi dan tercetak di halaman depan PDF.
 */
class RevisionLogTest extends TestCase
{
    use DatabaseTransactions;

    /** Buat dokumen Berlaku lalu ajukan revisi (SH) → kembalikan [lama, draft baru]. */
    private function makeRevisionDraft(): array
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Log Revisi');
        $doc->contents()->create(['section_key' => 'tujuan', 'value_json' => ['Tujuan awal']]);
        $doc->contents()->create(['section_key' => 'ruang_lingkup', 'value_json' => ['Lingkup awal']]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $doc->doc_number]);

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();
        $new = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        return [$doc, $new];
    }

    public function test_revision_draft_gets_extra_log_step(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();

        $this->assertTrue($new->isRevisionDraft(), 'draft revisi menunjuk dokumen asalnya');

        // Wizard menampilkan langkah ekstra "Log Revisi": satu langkah LEBIH
        // BANYAK daripada yang ada di schema.
        $props = $this->propsInertia($this->actingAs($gl)->get(route('documents.edit', $new))->assertOk());
        $this->assertSame(count($props['schema']['steps']) + 1, $props['totalSteps'], 'langkah Log Revisi tak ditambahkan');

        // Langkah 2 (terakhir schema) BUKAN lagi langkah akhir → tombol Kirim
        // tidak ada di sana; pindah ke langkah 3.
        $new->update(['current_step' => 2]);
        $props = $this->propsInertia($this->actingAs($gl)->get(route('documents.edit', $new))->assertOk());
        $this->assertLessThan($props['totalSteps'], $props['currentStep'], 'Kirim tidak di langkah 2 saat revisi');
        $this->assertFalse($props['isRevLogStep']);

        $new->update(['current_step' => 3]);
        $props = $this->propsInertia($this->actingAs($gl)->get(route('documents.edit', $new))->assertOk());
        $this->assertSame($props['totalSteps'], $props['currentStep'], 'Kirim ada di langkah 3 (Log Revisi)');
        $this->assertTrue($props['isRevLogStep'], 'lembar Catatan Perubahan tak dibuka');
        $this->assertIsArray($props['revisiKirim'], 'nomor Edisi/Revisi saat kirim harus ikut');
    }

    /**
     * JSA DIKECUALIKAN (permintaan pemilik): formulir JSA tak memakai lembar
     * CATATAN REVISI, jadi draft revisinya TIDAK mendapat langkah ke-3 —
     * Simpan/Kirim tetap di langkah terakhir schema. Alur revisi (versi lama
     * jadi Sedang Direvisi, draft milik GL) tak berubah.
     */
    public function test_jsa_revision_draft_has_no_log_step(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'JSA Revisi');
        $doc->contents()->create(['section_key' => 'analisa', 'value_json' => [
            ['langkah' => 'Langkah awal', 'bahaya' => [['risiko' => 'Risiko', 'pengendalian' => ['Kendali']]]],
        ]]);
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $doc->doc_number]);

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();
        $new = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        // Alur revisi tetap: draft menunjuk dokumen asal, versi lama Sedang Direvisi.
        $this->assertTrue($new->isRevisionDraft(), 'alur revisi JSA tidak berubah');
        $this->assertSame('sedang_direvisi', $doc->refresh()->status);

        // Tapi TIDAK memakai lembar/langkah log revisi.
        $this->assertFalse($new->usesRevisionLog(), 'JSA tak memakai lembar catatan revisi');

        $steps = app(\App\Services\SchemaService::class, ['type' => $type])->stepCount();
        $new->update(['current_step' => $steps]);
        $props = $this->propsInertia($this->actingAs($gl)->get(route('documents.edit', $new))->assertOk());

        $this->assertSame($steps, $props['totalSteps'], 'langkah Log Revisi tidak muncul');
        $this->assertFalse($props['isRevLogStep'], 'form catatan revisi tidak muncul');
        $this->assertNull($props['revisiKirim']);
        $this->assertSame($props['totalSteps'], $props['currentStep'], 'Kirim tetap di langkah terakhir schema');
    }

    public function test_log_step_saves_rows_and_manual_edisi_override(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();
        $new->update(['current_step' => 3]);

        $this->actingAs($gl)->post(route('documents.saveStep', $new), [
            'step' => 3,
            'action' => 'save',
            'edisi' => 2,          // override manual
            'no_revisi' => 3,      // override manual
            'sections' => ['catatan_revisi' => [
                ['no_rev' => '', 'tanggal' => '2026-07-19', 'halaman' => '1-2', 'catatan' => 'Perubahan detail aktivitas'],
                ['no_rev' => '', 'tanggal' => '', 'halaman' => '', 'catatan' => ''],   // kosong → dibuang
            ]],
        ])->assertRedirect();

        $new->refresh();
        $this->assertSame('2', $new->edisi, 'edisi bisa dioverride manual');
        $this->assertSame(3, $new->no_revisi, 'no_revisi bisa dioverride manual');

        $rows = $new->contentMap()['catatan_revisi'];
        $this->assertCount(1, $rows, 'baris kosong dibuang');
        $this->assertSame('Perubahan detail aktivitas', $rows[0]['catatan']);
        $this->assertSame(3, $rows[0]['no_rev'], 'baris baru diberi no_revisi dokumen ini');
    }

    /**
     * Bentuk BERSARANG lembar catatan revisi (keputusan pemilik 2026-09-07):
     * satu baris = satu SESI revisi, tiap temuan jadi sub-poin a./b./c.
     *
     * Tiga hal dipaku sekaligus karena ketiganya gagal tanpa suara:
     *
     * 1. Baris yang isinya SELURUHNYA di sub-poin tetap tersimpan. Saringan
     *    lama hanya melihat tanggal/halaman/catatan induk, jadi baris seperti
     *    ini akan lenyap saat disimpan tanpa satu pun galat.
     * 2. Sub-poin KOSONG ikut tersimpan. Tombol Revisi menyusun kerangkanya
     *    lebih dulu dan autosave membekukannya sebelum sempat diketik;
     *    membuangnya berarti kerangka itu hilang tiap kali halaman dimuat
     *    ulang. Yang tak boleh tercetak disaring di lembar cetaknya.
     * 3. `bab` TIDAK ikut tersimpan — ia cuma petunjuk layar, dan menyimpannya
     *    berarti nama bab versi lama ikut tercetak di dokumen versi baru.
     */
    public function test_log_step_menyimpan_sub_poin_bersarang(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();
        $new->update(['current_step' => 3]);

        $this->actingAs($gl)->post(route('documents.saveStep', $new), [
            'step' => 3,
            'action' => 'save',
            'sections' => ['catatan_revisi' => [
                [
                    'no_rev' => '7',
                    'tanggal' => '2026-07-19',
                    'halaman' => '1-2, 5',
                    'catatan' => '',            // induk kosong — isinya ada di sub
                    'sub' => [
                        ['catatan' => ' Pimpinan Departemen diganti. ', 'bab' => 'Tujuan'],
                        ['catatan' => ''],      // kerangka yang belum diketik
                    ],
                ],
            ]],
        ])->assertRedirect();

        $rows = $new->refresh()->contentMap()['catatan_revisi'];

        $this->assertCount(1, $rows, 'baris yang isinya hanya di sub-poin tak boleh dibuang');
        $this->assertSame(7, $rows[0]['no_rev'], 'No. Rev naik tanpa batas — nol roll-over di lembar ini');
        $this->assertSame(
            [['catatan' => 'Pimpinan Departemen diganti.'], ['catatan' => '']],
            $rows[0]['sub'],
            'sub-poin tersimpan terpangkas, yang kosong ikut, dan `bab` tak pernah ikut',
        );
    }

    /**
     * v3 rev: pengaju revisi (SH/DH/PJO) TIDAK dibawa ke form edit — draft revisi
     * milik GL & tampil di Status Dokumen-nya; dokumen Berlaku tidak lagi tampil
     * di Status Dokumen (pindah ke Dokumen Berlaku).
     */
    public function test_requester_not_redirected_to_edit_and_status_list_filtered(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'SOP')->firstOrFail();

        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'SOP Alur Revisi');
        $doc->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $doc->doc_number]);

        // Pengaju (SH) TIDAK diarahkan ke form edit draft revisi.
        $resp = $this->actingAs($gl)->post(route('documents.requestRevision', $doc), ['alasan' => 'Perlu penyesuaian prosedur.']);
        $resp->assertRedirect();
        $this->assertStringNotContainsString('/edit', $resp->headers->get('Location') ?? '', 'pengaju tidak dibawa ke form edit');

        // GL dinotifikasi draft revisinya.
        $this->assertTrue(
            $gl->notifications()->get()->contains(fn ($n) => str_contains($n->data['message'] ?? '', 'diajukan revisi')),
            'GL menerima notifikasi revisi'
        );

        // Status Dokumen GL: versi lama (sedang_direvisi) TIDAK tampil; draft revisi TAMPIL.
        $new = Document::where('revises_document_id', $doc->id)->firstOrFail();
        $ids = $this->idDokumen($this->actingAs($gl)->get(route('documents.index'))->assertOk());

        $this->assertContains($new->id, $ids, 'draft revisi muncul kembali');
        $this->assertNotContains($doc->id, $ids, 'versi Berlaku/sedang_direvisi tersembunyi');
    }

    /** v3 rev3 #7: draft revisi Tipe B tampil di menu "Dokumen Revisi" GL. */
    public function test_revision_draft_appears_in_dokumen_revisi_menu(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();

        $this->assertContains(
            $new->id,
            $this->idDokumen($this->actingAs($gl)->get(route('documents.revisions'))->assertOk())
        );
    }

    /**
     * Id dokumen yang tampil di sebuah halaman daftar.
     *
     * Dulu `assertViewHas('documents', …)` yang membaca paginator Eloquent.
     * Sejak Fase 8 daftarnya props Inertia — isinya larik datar
     * (`Document::barisDaftar`), tapi yang diperiksa tetap sama: baris mana
     * yang lolos penyaringan visibilitas.
     *
     * @return list<int>
     */
    private function idDokumen(\Illuminate\Testing\TestResponse $res): array
    {
        return array_column($this->propsInertia($res)['documents']['data'], 'id');
    }

    public function test_rows_accumulate_across_revisions(): void
    {
        [, $rev1] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();

        // Revisi 1 terbit dengan satu baris catatan.
        app(DocumentService::class)->saveSection($rev1, 'catatan_revisi', [
            ['no_rev' => 1, 'tanggal' => '2026-07-18', 'halaman' => '1', 'catatan' => 'Perubahan pertama'],
        ]);
        // `no_revisi` diset di sini karena test ini melompati pengiriman —
        // di alur nyata angka itu naik saat draft dikirim (butir 7a).
        $rev1->update(['status' => 'published', 'published_at' => now(), 'no_revisi' => 1]);

        // Ajukan revisi lagi → draft revisi 2 MEWARISI baris revisi 1.
        $this->actingAs($gl)->post(route('documents.requestRevision', $rev1), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();
        $rev2 = Document::where('revises_document_id', $rev1->id)->firstOrFail();

        $rows = $rev2->contentMap()['catatan_revisi'];
        $this->assertCount(1, $rows, 'baris revisi terdahulu terbawa (terakumulasi)');
        $this->assertSame('Perubahan pertama', $rows[0]['catatan']);
        // butir 7a: nomornya baru naik saat dikirim; sampai saat itu draft masih
        // memikul nomor versi terbit, dan yang dijanjikan dihitung dari sini.
        $this->assertSame(1, $rev2->no_revisi, 'nomor versi terbit masih dipikul');
        $this->assertSame([1, 2], $rev2->revisiSaatKirim(), 'akan menjadi Edisi 1 Rev 2 saat dikirim');
    }

    /**
     * butir 7b — tombol "Revisi": satu baris per bab yang BERUBAH, lengkap
     * dengan No. Rev, tanggal hari ini (WITA), dan nomor halaman yang diukur
     * dari PDF yang benar-benar dirender. Kolom Catatan tetap urusan manusia.
     */
    public function test_usulan_catatan_revisi_hanya_bab_yang_berubah(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();
        $svc = app(DocumentService::class);

        // Satu bab benar-benar berubah; satu lagi hanya berbeda SPASI — beda
        // spasi bukan revisi, jadi ia tak boleh ikut terdaftar.
        $svc->saveSection($new, 'tujuan', ['Tujuan yang sudah diperbarui']);
        $svc->saveSection($new, 'ruang_lingkup', ['  Lingkup awal  ']);

        $json = $this->actingAs($gl)->getJson(route('documents.revisi.usulan', $new))
            ->assertOk()->json();

        $this->assertSame(1, $json['no_rev'], 'nomor revisi yang DIJANJIKAN, bukan yang masih tersimpan');
        $this->assertSame(now()->toDateString(), $json['tanggal']);
        $this->assertCount(1, $json['baris'], 'hanya bab yang benar-benar berubah');
        $this->assertSame('I. TUJUAN', $json['baris'][0]['bab']);
        // SOP ber-cover: halaman 1 adalah cover & tak bernomor, jadi bab pertama
        // isi tercetak sebagai halaman 1 — sama dengan yang tertulis di kop.
        $this->assertSame('1', $json['baris'][0]['halaman']);
    }

    /** JSA tak punya lembar CATATAN REVISI → tombolnya pun tak boleh dilayani. */
    public function test_usulan_catatan_revisi_ditolak_untuk_jsa_dan_orang_lain(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $type = DocumentType::where('code', 'JSA')->firstOrFail();

        $jsa = app(DocumentService::class)->createDraft($gl, $type, $gl->department, 'JSA Revisi');
        $jsa->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $jsa->doc_number]);
        $this->actingAs($gl)->post(route('documents.requestRevision', $jsa), ['alasan' => 'Perlu penyesuaian prosedur.'])->assertRedirect();
        $draftJsa = Document::where('revises_document_id', $jsa->id)->firstOrFail();

        $this->actingAs($gl)->getJson(route('documents.revisi.usulan', $draftJsa))->assertForbidden();

        // SH boleh MELIHAT dokumennya, tapi ia tak menyunting draft milik GL —
        // aturan yang sama dengan wizard, bukan aturan baru.
        [, $sop] = $this->makeRevisionDraft();
        $this->actingAs($sh)->getJson(route('documents.revisi.usulan', $sop))->assertForbidden();
    }

    /**
     * butir 7a — tiga penjaga kenaikan nomor revisi. Tanpa ketiganya, satu draft
     * revisi bisa naik beberapa tingkat tanpa satu pun revisi tambahan: alur
     * ditolak→perbaiki→kirim dan tarik→kirim sama-sama melewati pengiriman lagi.
     */
    public function test_nomor_revisi_naik_sekali_walau_dikirim_berulang(): void
    {
        [, $new] = $this->makeRevisionDraft();
        $gl = $this->aktorGl();
        $sh = $this->aktorSh();
        $pjo = $this->aktorPjo();

        $new->update(['reviewer_id' => $sh->id, 'approver_id' => $pjo->id]);
        $this->assertSame(0, $new->no_revisi, 'belum naik selama draft');

        // Kirim pertama → naik ke 1.
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->assertSame(1, $new->refresh()->no_revisi);

        // (a) Tarik lalu kirim lagi → TIDAK naik lagi.
        $this->actingAs($gl)->post(route('documents.withdraw', $new))->assertRedirect();
        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->assertSame(1, $new->refresh()->no_revisi, 'tarik lalu kirim ulang tak menaikkan lagi');

        // (b) Ditolak peninjau lalu dikirim ulang → TIDAK naik lagi.
        $this->actingAs($sh)->get(route('review.show', $new));
        $this->actingAs($sh)->post(route('review.store', $new), ['decision' => 'reject', 'summary' => 'Perbaiki bab tujuan.'])->assertRedirect();
        $this->assertSame('rejected', $new->refresh()->status);

        $this->actingAs($gl)->post(route('documents.submit', $new))->assertRedirect();
        $this->assertSame(1, $new->refresh()->no_revisi, 'kirim ulang sesudah ditolak tak menaikkan lagi');
    }
}
