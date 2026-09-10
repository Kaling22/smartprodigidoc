<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\MasukanSejawat;
use App\Models\Review;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pesan yang menuju tempatnya (PLAN-AKSES-v8 Fase 3).
 *
 * Dua permukaan, satu janji: sebuah pesan mengantar penerimanya ke LAYAR TEMPAT
 * pesan itu bisa ditindaklanjuti — bukan ke halaman daftar, dan bukan ke galat.
 *
 *   • 3a LONCENG — NotificationController::open() dulu membuang parameter
 *     dokumen, jadi setiap notifikasi mendarat di halaman daftar, dan yang
 *     ber-rute `documents.show` melempar UrlGenerationException alias 500.
 *   • 3b LOG PESAN — tiap baris dulu bertombol `documents.show` yang sama,
 *     siapa pun yang melihatnya.
 *
 * SELURUH aktor lahir di dalam transaksi (§3 jalan B).
 */
class PesanBerkontekTest extends TestCase
{
    use DatabaseTransactions;

    private function dept(): Department
    {
        $tanda = Str::upper(Str::random(6));

        return Department::create(['code' => 'UJP'.$tanda, 'name' => 'Departemen Uji '.$tanda]);
    }

    private function orang(string $role, ?Department $dept = null): User
    {
        $tanda = Str::upper(Str::random(6));
        $u = User::create([
            'name' => 'Uji '.$role.' '.$tanda,
            'nrp' => 'PSN-'.$tanda,
            'jabatan' => $role,
            'department_id' => $dept?->id,
            'password' => Hash::make('x'),
            'status' => 'active',
        ]);
        $u->assignRole($role);

        return $u->fresh();
    }

    private function draft(User $pembuat, Department $dept): Document
    {
        return app(DocumentService::class)->createDraft(
            $pembuat, DocumentType::where('code', 'SOP')->firstOrFail(), $dept, 'SOP Uji Pesan '.Str::random(6)
        );
    }

    /**
     * Tautan baris Log Pesan untuk dokumen ini, sebagaimana DILIHAT `$penonton`.
     *
     * Dibaca dari props, bukan markup: di payload Inertia URL-nya ter-escape
     * (`http:\/\/…`), jadi `assertSee(route(...))` gagal karena ejaan — bukan
     * karena tombolnya salah.
     *
     * @return array{url: string, label: string, ikon: string}|null
     */
    private function tautanPesan(User $penonton, Document $doc): ?array
    {
        $props = $this->propsInertia(
            $this->actingAs($penonton)->get(route('log.pesan'))->assertOk()
        );

        return collect($props['pesan']['data'])->firstWhere('nomor', $doc->displayNumber())['tautan'] ?? null;
    }

    /** Bunyikan satu notifikasi lalu buka dari lonceng; kembalikan responsnya. */
    private function klikLonceng(User $penerima, Document $doc, string $routeName)
    {
        $penerima->notify(new DocumentNotification($doc, 'Pesan uji.', 'bi-bell', $routeName));

        $id = $penerima->fresh()->notifications()->latest()->firstOrFail()->id;

        return $this->actingAs($penerima)->get(route('notifications.open', $id));
    }

    public function test_notifikasi_berparameter_tidak_lagi_melempar_500(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);
        $doc = $this->draft($gl, $dept);

        // Inilah notifikasi yang sebelum Fase 3a MEMBUAT GALAT 500 saat diklik
        // ("X mengajukan revisi …" ke SH/DH & MD, DocumentFeedbackService).
        $this->klikLonceng($sh, $doc, 'documents.show')
            ->assertRedirect(route('documents.show', $doc));
    }

    public function test_pesan_perbaiki_dokumen_mendarat_di_form_revisinya(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($gl, $dept);

        $this->klikLonceng($gl, $doc, 'documents.edit')
            ->assertRedirect(route('documents.edit', $doc));
    }

    public function test_rute_daftar_tetap_dituju_tanpa_menempelkan_id(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);
        $doc = $this->draft($gl, $dept);

        // `nonaktif.index` memang antrean keputusan — di situlah pekerjaannya.
        // Yang dijaga: id dokumen TIDAK ditempelkan sebagai query string.
        $this->klikLonceng($sh, $doc, 'nonaktif.index')
            ->assertRedirect(route('nonaktif.index'));
    }

    public function test_dokumen_yang_sudah_lenyap_jatuh_ke_dashboard_bukan_galat(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);

        $this->assertSame(route('dashboard'), DocumentNotification::urlUntuk('documents.show', null));
        $this->assertSame(route('nonaktif.index'), DocumentNotification::urlUntuk('nonaktif.index', null));

        // Rute yang tak dikenal sama sekali (notifikasi versi lama) tak boleh
        // menjatuhkan halaman lonceng.
        $this->assertSame(route('dashboard'), DocumentNotification::urlUntuk('rute.yang.sudah.tiada', null));

        $this->assertTrue($gl->exists);
    }

    public function test_baris_penolakan_mengantar_pembuat_ke_revisi_dan_sh_ke_dokumen(): void
    {
        $dept = $this->dept();
        $gl = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $sh = $this->orang(User::JABATAN_SECTION_HEAD, $dept);

        $doc = $this->draft($gl, $dept);
        // `rejected` = dokumen yang memang masih bisa disunting pembuatnya.
        $doc->update(['status' => 'rejected']);

        Review::create([
            'document_id' => $doc->id,
            'reviewer_id' => $sh->id,
            'revision_round' => 0,
            'decision' => 'needs_revision',
            'summary' => 'Alasan uji penolakan peninjau '.Str::random(6),
        ]);

        // Tautannya dibaca dari PROPS: di payload Inertia URL-nya ter-escape
        // (`http:\/\/…`), jadi assertSee(route(...)) gagal karena ejaan — bukan
        // karena tombolnya salah. Tujuan, label, dan ikonnya memang seluruhnya
        // dari controller (DocumentLogController::tautan).
        $this->assertSame(
            ['url' => route('documents.edit', $doc), 'label' => 'Revisi', 'ikon' => 'bi-pencil-square'],
            $this->tautanPesan($gl, $doc),
        );

        // SH yang membaca baris YANG SAMA: tombol Dokumen.
        $this->assertSame(
            ['url' => route('documents.show', $doc), 'label' => 'Dokumen', 'ikon' => 'bi-box-arrow-up-right'],
            $this->tautanPesan($sh, $doc),
        );
    }

    public function test_baris_masukan_sejawat_muncul_dan_mengantar_ke_isinya(): void
    {
        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $alasan = 'Ringkasan masukan sejawat '.Str::random(6);

        MasukanSejawat::create([
            'document_id' => $doc->id,
            'user_id' => $glB->id,
            'ringkasan' => $alasan,
            'catatan_json' => [['section_key' => 'tujuan', 'item_ref' => '0', 'komentar' => 'Catatan uji.']],
        ]);

        $this->actingAs($glA)->get(route('log.pesan'))
            ->assertOk()
            ->assertSee('Masukan sejawat')
            ->assertSee($alasan);

        $this->assertSame(
            [
                'url' => route('masukan-sejawat.show', $doc),
                'label' => 'Lihat Masukan',
                'ikon' => 'bi-chat-square-text',
            ],
            $this->tautanPesan($glA, $doc),
        );
    }

    public function test_masukan_sejawat_tanpa_ringkasan_tetap_muncul_lewat_catatannya(): void
    {
        $dept = $this->dept();
        $glA = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $glB = $this->orang(User::JABATAN_GROUP_LEADER, $dept);
        $doc = $this->draft($glA, $dept);

        $komentar = 'Catatan tanpa ringkasan '.Str::random(6);

        MasukanSejawat::create([
            'document_id' => $doc->id,
            'user_id' => $glB->id,
            'ringkasan' => null,
            'catatan_json' => [['section_key' => 'tujuan', 'item_ref' => '0', 'komentar' => $komentar]],
        ]);

        // Tanpa jatuh-tempo ke komentar pertama, baris ini lenyap tanpa jejak
        // di penyaring "alasan kosong" milik pesan().
        $this->actingAs($glA)->get(route('log.pesan'))
            ->assertOk()
            ->assertSee($komentar);
    }
}
