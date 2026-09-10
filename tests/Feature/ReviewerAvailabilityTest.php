<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\UserOffDay;
use App\Services\DocumentService;
use App\Services\ReviewerAvailability;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Ketersediaan peninjau: cuti/off day + batas beban dokumen (FITUR-BARU-v4 §6).
 *
 * Yang dikunci di sini adalah JANJI fiturnya: peninjau yang penuh atau sedang
 * off tak bisa DITUGASI dokumen baru — di layar MAUPUN di server — sementara
 * dokumen yang sudah dipegangnya sama sekali tidak terganggu.
 *
 * Test yang menyangkut BEBAN memakai {@see TestCase::peninjauBersih()}, bukan
 * akun contoh `SH-0001`: suite ini berjalan di atas basis data pengembangan,
 * dan akun contoh itu membawa dokumen sungguhan milik pemilik. Begitu bebannya
 * melewati batas, test yang mengandaikan meja kosong ikut merah — merah yang
 * menyalahkan kode padahal fiturnya justru sedang bekerja.
 */
class ReviewerAvailabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // API libur nasional tak pernah dipanggil sungguhan dari test: suite
        // harus jalan tanpa internet dan tak boleh bergantung pada layanan luar.
        Http::preventStrayRequests();
        Http::fake(['api-harilibur.vercel.app/*' => Http::response([], 200)]);
        cache()->forget('libur_nasional:'.now()->year);
    }

    /**
     * Kosongkan off milik seseorang lebih dulu.
     *
     * Suite ini berjalan di atas basis data PENGEMBANGAN, dan akun contoh
     * `SH-0001` bisa saja memegang cuti sungguhan milik pemilik. Test yang
     * mengandaikan jadwal kosong lalu merah — merah yang menyalahkan kode
     * padahal fiturnya justru sedang bekerja (persis alasan
     * {@see TestCase::peninjauBersih()} ada, tapi untuk off, bukan beban).
     *
     * Aman: seluruh berkas ini `DatabaseTransactions`, jadi barisnya kembali
     * begitu test selesai.
     */
    private function tanpaOff(User $user): User
    {
        $user->offDays()->delete();

        return $user;
    }

    private function draft(User $gl, string $judul): Document
    {
        $type = DocumentType::where('code', 'SOP')->firstOrFail();
        $doc = app(DocumentService::class)->createDraft($gl, $type, $gl->department, $judul);
        $doc->update(['current_step' => 2]);

        return $doc->refresh();
    }

    /** Dokumen yang MEMBEBANI seorang peninjau: sudah dikirim, belum lepas. */
    private function bebani(User $gl, User $sh, int $jumlah): void
    {
        for ($i = 1; $i <= $jumlah; $i++) {
            $this->draft($gl, "SOP Beban {$i}")
                ->update(['reviewer_id' => $sh->id, 'status' => 'waiting_for_review']);
        }
    }

    /**
     * Pembatasan kuota MASIH ADA di kode — hanya dimatikan bawaannya
     * (PLAN-REVISI-v6 Fase A, `batas_dokumen => 0`). Ia rem darurat yang bisa
     * dihidupkan pemilik lewat satu suntingan config, jadi ia tetap wajib dijaga:
     * rem yang tak pernah diuji adalah rem yang diam-diam sudah putus.
     */
    public function test_peninjau_penuh_tak_bisa_dipilih_di_layar_maupun_di_server(): void
    {
        config(['smartpro.peninjau.batas_dokumen' => 2]);

        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pjo = $this->aktorPjo();

        $this->bebani($gl, $sh, 2);   // batas bawaan = 2
        $doc = $this->draft($gl, 'SOP Uji Batas');

        $ketersediaan = app(ReviewerAvailability::class)->untuk(collect([$sh]), $doc)[$sh->id];
        $this->assertSame(2, $ketersediaan['beban']);
        $this->assertTrue($ketersediaan['penuh']);
        $this->assertFalse($ketersediaan['tersedia']);

        // Layar: barisnya terkunci. Alasan & keterkuncian datang dari props
        // yang SAMA dengan yang ditegakkan server — mustahil berselisih.
        $papan = $this->propsInertia(
            $this->actingAs($gl)->get(route('documents.edit', ['document' => $doc, 'view_step' => 2]))->assertOk()
        )['ketersediaan']['peninjau'][$sh->id];

        $this->assertSame('Penuh — 2 dokumen berjalan', $papan['alasan']);
        $this->assertFalse($papan['tersedia']);

        // Server: dipaksakan lewat POST pun ditolak, dan pilihannya tak tersimpan.
        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'next',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pjo->id],
        ])->assertRedirect();

        $this->assertNull($doc->refresh()->reviewer_id, 'peninjau penuh tak boleh tersimpan');
    }

    /** Dokumen yang SEDANG disunting tak boleh ikut dihitung sebagai beban. */
    public function test_dokumen_yang_sedang_disunting_tak_dihitung_sebagai_beban(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());

        $this->bebani($gl, $sh, 1);
        $doc = $this->draft($gl, 'SOP Sedang Disunting');
        $doc->update(['reviewer_id' => $sh->id, 'status' => 'waiting_for_review']);

        $avail = app(ReviewerAvailability::class);

        $this->assertSame(2, $avail->untuk(collect([$sh]))[$sh->id]['beban'], 'tanpa pengecualian: 2');
        $this->assertSame(1, $avail->untuk(collect([$sh]), $doc)[$sh->id]['beban'], 'dokumen ini tak ikut dihitung');
        $this->assertTrue($avail->untuk(collect([$sh]), $doc)[$sh->id]['tersedia'], 'jadi masih bisa dipilih ulang');
    }

    public function test_peninjau_yang_off_tak_bisa_ditugasi_dan_kembali_di_hari_kerja(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pjo = $this->aktorPjo();

        UserOffDay::create([
            'user_id' => $sh->id, 'jenis' => 'cuti',
            'mulai' => now()->toDateString(), 'sampai' => now()->addDays(2)->toDateString(),
        ]);

        $doc = $this->draft($gl, 'SOP Uji Off');
        $ketersediaan = app(ReviewerAvailability::class)->untuk(collect([$sh]), $doc)[$sh->id];

        $this->assertTrue($ketersediaan['off']);
        $this->assertFalse($ketersediaan['tersedia']);
        $this->assertSame(0, $ketersediaan['beban'], 'off bukan beban — dua sebab yang berbeda');

        // "Kembali Sabtu" tak berguna bagi siapa pun: yang dicari GL adalah hari
        // KERJA pertama saat orangnya benar-benar bisa menerima dokumen lagi.
        $kembali = $ketersediaan['kembali'];
        $this->assertTrue($kembali->greaterThan(now()->addDays(2)));
        $this->assertFalse($kembali->isWeekend(), 'hari kembali tak boleh jatuh di akhir pekan');

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'next',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pjo->id],
        ])->assertRedirect();

        $this->assertNull($doc->refresh()->reviewer_id, 'peninjau yang off tak boleh tersimpan');
    }

    /**
     * Off menghalangi PENUGASAN BARU saja — dokumen yang sudah dipegang tetap
     * di tangannya, dan pembuatnya diberi tahu (ketetapan pemilik).
     */
    public function test_off_tak_menarik_dokumen_yang_sudah_dipegang(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());
        $pjo = $this->aktorPjo();

        $doc = $this->draft($gl, 'SOP Sudah Ditugaskan');
        $doc->update(['reviewer_id' => $sh->id, 'approver_id' => $pjo->id]);

        // SH mengajukan off SESUDAH ditunjuk.
        $this->actingAs($sh)->post(route('off.store'), [
            'jenis' => 'cuti',
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDay()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $doc->refresh();
        $this->assertSame($sh->id, $doc->reviewer_id, 'dokumen TIDAK berpindah tangan');

        // Kirim tetap lolos: memeriksa ulang di sini hanya akan membuat dokumen
        // yang sah mendadak gagal dikirim dengan pesan yang membingungkan.
        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'submit',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pjo->id],
        ])->assertRedirect();

        $this->assertSame('waiting_for_review', $doc->refresh()->status);
    }

    public function test_pembuat_diberi_tahu_saat_peninjaunya_off(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        $sh = $this->tanpaOff($this->aktorSh());

        $this->draft($gl, 'SOP Peninjau Off')
            ->update(['reviewer_id' => $sh->id, 'status' => 'in_review']);

        $this->actingAs($sh)->post(route('off.store'), [
            'jenis' => 'off_day',
            'mulai' => now()->toDateString(),
            'sampai' => now()->addDay()->toDateString(),
        ])->assertRedirect();

        Notification::assertSentTo($gl, \App\Notifications\DocumentNotification::class);
    }

    public function test_pengajuan_off_menolak_rentang_terbalik_dan_bertindih(): void
    {
        $sh = $this->tanpaOff($this->aktorSh());

        // Selesai sebelum mulai.
        $this->actingAs($sh)->post(route('off.store'), [
            'jenis' => 'cuti',
            'mulai' => now()->addDays(5)->toDateString(),
            'sampai' => now()->addDays(2)->toDateString(),
        ])->assertSessionHasErrors('sampai');

        $this->actingAs($sh)->post(route('off.store'), [
            'jenis' => 'cuti',
            'mulai' => now()->addDays(2)->toDateString(),
            'sampai' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasNoErrors();

        // Bertindih dengan yang barusan.
        $this->actingAs($sh)->post(route('off.store'), [
            'jenis' => 'dinas_luar',
            'mulai' => now()->addDays(4)->toDateString(),
            'sampai' => now()->addDays(6)->toDateString(),
        ])->assertSessionHasErrors('mulai');

        $this->assertSame(1, $sh->offDays()->count(), 'hanya satu yang tersimpan');
    }

    public function test_off_hanya_bisa_dibatalkan_pemiliknya(): void
    {
        $sh = $this->aktorSh();
        $dh = $this->aktorDh();

        $off = UserOffDay::create([
            'user_id' => $sh->id, 'jenis' => 'cuti',
            'mulai' => now()->addDay()->toDateString(), 'sampai' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($dh)->delete(route('off.destroy', $off))->assertForbidden();
        $this->assertDatabaseHas('user_off_days', ['id' => $off->id]);

        $this->actingAs($sh)->delete(route('off.destroy', $off))->assertRedirect();
        $this->assertDatabaseMissing('user_off_days', ['id' => $off->id]);
    }

    /** Knob config benar-benar berfungsi: 0 = tanpa batas. */
    public function test_batas_nol_berarti_tanpa_batas(): void
    {
        $gl = $this->aktorGl();
        $sh = $this->peninjauBersih($this->aktorSh());

        $this->bebani($gl, $sh, 3);
        config(['smartpro.peninjau.batas_dokumen' => 0]);

        $ketersediaan = app(ReviewerAvailability::class)->untuk(collect([$sh]))[$sh->id];
        $this->assertSame(3, $ketersediaan['beban']);
        $this->assertFalse($ketersediaan['penuh'], 'batas 0 = tak pernah penuh');
        $this->assertTrue($ketersediaan['tersedia']);
    }

    /**
     * API libur nasional mati → pita tetap terbentuk dan akhir pekan tetap
     * ditandai. Libur hanyalah hiasan pita; ia tak pernah memblokir siapa pun.
     */
    public function test_pita_tetap_utuh_walau_api_libur_gagal(): void
    {
        Http::fake(['api-harilibur.vercel.app/*' => Http::response('gagal', 500)]);
        cache()->forget('libur_nasional:'.now()->year);

        $sh = $this->tanpaOff($this->aktorSh());
        $pita = app(ReviewerAvailability::class)->untuk(collect([$sh]))[$sh->id]['pita'];

        $this->assertCount(config('smartpro.peninjau.hari_pita'), $pita);
        $this->assertSame(now()->toDateString(), $pita[0]['tanggal']->toDateString(), 'sel pertama = hari ini');

        foreach ($pita as $sel) {
            if ($sel['tanggal']->isWeekend()) {
                $this->assertSame(ReviewerAvailability::SEL_TUTUP, $sel['status'], 'akhir pekan dihitung lokal, bukan dari API');
            }
        }
    }

    /**
     * Batas 2 dokumen TIDAK berlaku bagi penyetuju: PJO hanya satu orang, dan
     * memblokirnya berarti seluruh SOP di 7 departemen berhenti bisa dikirim.
     */
    public function test_penyetuju_tak_pernah_terkunci(): void
    {
        $gl = $this->aktorGl();
        $pjo = $this->aktorPjo();

        UserOffDay::create([
            'user_id' => $pjo->id, 'jenis' => 'cuti',
            'mulai' => now()->toDateString(), 'sampai' => now()->addDays(3)->toDateString(),
        ]);

        $doc = $this->draft($gl, 'SOP Penyetuju Off');

        $this->actingAs($gl)->post(route('documents.saveStep', $doc), [
            'step' => 2, 'action' => 'next',
            'sections' => ['penyetuju' => $pjo->id],
        ])->assertRedirect();

        $this->assertSame($pjo->id, $doc->refresh()->approver_id, 'penyetuju yang off tetap boleh dipilih');
    }

    /**
     * Bagian KEDUA fitur §6: pembuat tambahan ikut menerima dokumen revisinya.
     *
     * Sebelumnya namanya tercantum di halaman pengesahan tapi ia tak pernah
     * menerima apa pun — dokumen yang ikut ia susun tak muncul di menu mana pun
     * miliknya.
     */
    public function test_pembuat_tambahan_ikut_menerima_dokumen_revisi(): void
    {
        Notification::fake();

        $gl = $this->aktorGl();
        // Kandidat pembuat tambahan: GL/Non-Staff sedepartemen (CLAUDE.md §6).
        $rekan = $this->aktorGl(ke: 2);

        $doc = $this->draft($gl, 'SOP Dengan Pembuat Tambahan');
        $doc->authors()->create(['user_id' => $rekan->id, 'is_primary' => false]);
        $doc->update(['status' => 'published', 'published_at' => now()]);

        $this->actingAs($gl)->post(route('documents.requestRevision', $doc), [
            'alasan' => 'Menyesuaikan prosedur terbaru.',
        ])->assertRedirect();

        $revisi = Document::where('doc_number', $doc->doc_number)->where('id', '!=', $doc->id)->firstOrFail();

        $this->assertTrue(
            $revisi->authors()->where('user_id', $rekan->id)->where('is_primary', false)->exists(),
            'pembuat tambahan ikut terbawa ke draft revisinya',
        );

        Notification::assertSentTo($rekan, \App\Notifications\DocumentNotification::class);

        // Dan dokumennya muncul di menu "Dokumen Saya" miliknya.
        $this->actingAs($rekan)->get(route('documents.index'))
            ->assertOk()
            ->assertSee('SOP Dengan Pembuat Tambahan');
    }

    /**
     * Kartu "Ketersediaan Saya" — kini kalender BULAN BERJALAN.
     *
     * Yang dikunci: kartunya ada, pintu masuknya ada, dan tanggal off benar-benar
     * berbeda rupa. Pil status & meteran beban sengaja tak diuji lagi — keduanya
     * memang dilepas dari kartu ini (spec dashboard v1 widget 6); pertanyaan
     * "bisakah saya dipilih" dijawab papan pemilihan peninjau.
     */
    public function test_kartu_ketersediaan_tampil_di_dashboard(): void
    {
        $sh = $this->aktorSh();

        // Judul kartu & tombolnya kini di TSX; yang bisa diperiksa server adalah
        // DATA yang menghidupinya — bulan yang tampil dan sel kalendernya.
        $this->actingAs($sh)->get(route('dashboard'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kalenderOff.judul', now()->translatedFormat('F Y'))
                ->has('kalenderOff.sel')
                ->has('jenisOff')            // pilihan di dialog Ajukan Off
                ->has('urlOffStore')         // pintu masuknya
            );

        // Sengaja TANPA "sebelumnya tak ada tanggal off": akun SH ini milik
        // pemilik dan bisa saja sudah punya off tercatat (lihat catatan kelas).
        // Yang dikunci cuma bahwa off yang BARU dibuat memang terlihat.

        UserOffDay::create([
            'user_id' => $sh->id, 'jenis' => 'cuti',
            'mulai' => now()->toDateString(), 'sampai' => now()->addDays(2)->toDateString(),
        ]);

        // Dulu `assertSee('pp-kal-hari is-off')`: kelas CSS yang menandai sel
        // off. Penandanya kini `sel[].off` berisi keterangannya — nilai yang
        // sama yang dipakai TSX untuk mewarnai selnya.
        $sel = collect($this->propsInertia(
            $this->actingAs($sh)->get(route('dashboard'))->assertOk()
        )['kalenderOff']['sel'])->filter();

        $off = $sel->pluck('off')->filter();

        $this->assertNotEmpty($off, 'tanggal off harus ditandai, bukan tampil polos');
        $this->assertStringStartsWith('Cuti ·', $off->first());
    }

    /**
     * Akhir pekan TAK DIBEDAKAN sama sekali dari hari kerja.
     *
     * Ketetapan pemilik (4 Agustus 2026). Dua percobaan sebelumnya sama-sama
     * ditolak: hari tutup dibiarkan tanpa batang (barisnya jadi berlubang), lalu
     * batangnya diredupkan (masih terbaca sebagai "ada apa-apanya"). Yang
     * dibedakan pita ini cuma SATU hal — orangnya ada atau tidak.
     *
     * Sisi logikanya tak ikut berubah: hari tutup tetap dilewati saat menghitung
     * "kembali tanggal berapa", dan itu dijaga test lain di berkas ini.
     */
    public function test_akhir_pekan_tampil_sama_dengan_hari_kerja(): void
    {
        $sh = $this->aktorSh();
        $jumlahHari = now()->daysInMonth;

        // Dihitung dari SEL-nya, bukan dari markup: sejak kartunya Inertia,
        // `data-tanggal="…"` tak ada lagi — dan menghitung dari nama kelas
        // memang tak pernah aman (definisi CSS ikut terkirim ke peramban dan
        // terhitung sebagai sel hantu). Tiap tanggal bulan ini WAJIB punya
        // selnya — tak satu pun hari dilewati, termasuk akhir pekan.
        $sel = collect($this->propsInertia(
            $this->actingAs($sh)->get(route('dashboard'))->assertOk()
        )['kalenderOff']['sel'])->filter();

        $this->assertCount($jumlahHari, $sel, "kalender bulan ini harus punya {$jumlahHari} sel");

        // Dan tak satu sel pun membawa penanda pembeda hari tutup: satu-satunya
        // pembeda yang boleh ada adalah `off`, `iniHariIni`, dan `lewat`.
        foreach ($sel as $s) {
            $this->assertSame(
                ['tanggal', 'angka', 'off', 'iniHariIni', 'lewat', 'judul'],
                array_keys($s),
                'akhir pekan tak boleh dapat penanda tersendiri',
            );
        }
    }

    /**
     * PLAN-REVISI-v6 Fase A — kuota diganti SINYAL yang terbaca.
     *
     * Satu test, tiga janji, karena ketiganya adalah satu fitur yang sama:
     * kuota tak lagi menghalangi, bebannya dicetak sebagai ANGKA, dan angka itu
     * berwarna HANYA pada jenis yang menumpuk (JSA). Memecahnya jadi tiga berkas
     * hanya menyalin tiga kali penyiapan peninjau yang sama.
     *
     * Penanda yang diperiksa `data-tingkat="…"`, BUKAN nama kelas telanjang:
     * definisi CSS `.badge-soft-warning` ikut terkirim ke peramban dan akan cocok
     * dengan apa pun (jebakan yang sama sudah tercatat pada test akhir pekan).
     */
    public function test_beban_tampil_sebagai_angka_dan_kuota_tak_lagi_menghalangi(): void
    {
        $gl = $this->aktorGl();                 // ICTMD, pembuat
        $sh = $this->peninjauBersih($this->aktorSh());
        $pjo = $this->aktorPjo();

        // Jauh di atas kuota lama (2) DAN di atas ambang "sibuk" (5).
        $this->bebani($gl, $sh, 5);

        // (1) Papan SOP: angkanya tercetak, POLOS — tak ada tingkat sama sekali.
        $sop = $this->draft($gl, 'SOP Uji Beban Angka');
        $resp = $this->actingAs($gl)->get(route('documents.edit', ['document' => $sop, 'view_step' => 2]))->assertOk();
        $props = $this->propsInertia($resp);

        $this->assertSame(5, $props['ketersediaan']['peninjau'][$sh->id]['beban']);
        $this->assertSame('normal', $props['ketersediaan']['peninjau'][$sh->id]['tingkat'],
            'SOP tak berambang — angkanya polos, tanpa tingkat');

        // Langkah ini memuat DUA papan (peninjau + penyetuju); hanya papan
        // peninjau yang berkolom beban — di penyetuju angka itu mengukur beban
        // PENINJAUAN, yang tak ada hubungannya dengan tugas menyetujui.
        // Keputusannya di ReviewerAvailability::PAPAN dan dikirim sebagai props,
        // bukan diketik ulang di TSX (pakem P5).
        $berpapan = array_intersect_key($props['papan'], $this->seksiWizard($resp));
        $this->assertSame(['peninjau' => true, 'penyetuju' => false], $berpapan);

        // (2) Peninjau dengan 5 dokumen berjalan TETAP bisa ditugasi — dulu ditolak.
        $this->actingAs($gl)->post(route('documents.saveStep', $sop), [
            'step' => 2, 'action' => 'next',
            'sections' => ['peninjau' => $sh->id, 'penyetuju' => $pjo->id],
        ])->assertRedirect();

        $this->assertSame($sh->id, $sop->refresh()->reviewer_id, 'kuota tak lagi menghalangi penugasan');

        // (3) Papan JSA: ambang berlaku. Peninjaunya GL SHE (dept LAIN), dan
        //     `peninjau` JSA ada di langkah 1, bukan 2.
        $glShe = $this->peninjauBersih($this->aktorGl('SHE'));
        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Uji Ambang Beban'
        );

        // 3 dokumen → padat. Beban dihitung LINTAS JENIS dengan sengaja: yang
        // diukur adalah meja orangnya, bukan meja per jenis dokumen.
        $this->bebani($gl, $glShe, 3);
        $this->assertSame('padat', $this->propsInertia(
            $this->actingAs($gl)->get(route('documents.edit', ['document' => $jsa, 'view_step' => 1]))->assertOk()
        )['ketersediaan']['peninjau'][$glShe->id]['tingkat']);

        // 5 dokumen → sibuk, dan orangnya TETAP tersedia: "sibuk" itu keterangan,
        // bukan pagar. Sengaja diperiksa lewat service, bukan menebak markup
        // `disabled` — di sanalah `tersedia` benar-benar diputuskan, dan papan
        // maupun penjaga server sama-sama membacanya dari situ.
        $this->bebani($gl, $glShe, 2);
        $this->assertSame('sibuk', $this->propsInertia(
            $this->actingAs($gl)->get(route('documents.edit', ['document' => $jsa, 'view_step' => 1]))->assertOk()
        )['ketersediaan']['peninjau'][$glShe->id]['tingkat']);

        $a = app(ReviewerAvailability::class)->untuk(collect([$glShe]), $jsa)[$glShe->id];
        $this->assertSame(5, $a['beban']);
        $this->assertSame('sibuk', $a['tingkat']);
        $this->assertTrue($a['tersedia'], 'sibuk BUKAN terkunci');
    }
}
