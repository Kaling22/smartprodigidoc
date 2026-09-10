<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\UserOffDay;
use App\Notifications\DocumentNotification;
use App\Services\AuditService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pengajuan off: cuti, off day, dinas luar (FITUR-BARU-v4 §6).
 *
 * LANGSUNG BERLAKU tanpa persetujuan (ketetapan pemilik): ini sinyal
 * penjadwalan, bukan cuti kepegawaian — cuti resmi tetap urusan HCGA.
 * Penjaganya konfirmasi di layar, bukan antrean persetujuan baru.
 *
 * Off menghalangi PENUGASAN BARU saja. Dokumen yang sedang ditinjau TETAP
 * menjadi tanggung jawab orang itu; yang dilakukan sistem hanyalah memberi
 * tahu para pembuatnya.
 */
class OffDayController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Syarat PERANNYA juga tinggal di model (PLAN-MOBILE-v6 §2.1): menutup
        // pintu HP saja menyisakan pintu web terbuka lebar bagi orang yang sama.
        abort_unless($user->bisaCatatKetersediaan(), 403,
            'Ketersediaan dicatat GL, Section/Departemen Head, dan Pimpinan. Cuti Non-Staff tetap diurus HCGA.');

        // Aturannya tinggal di model: kanal web dan kanal HP memvalidasi dengan
        // daftar yang persis sama (UserOffDay::aturan()).
        $data = $request->validate(...UserOffDay::aturan());

        // Bertindih → tunjukkan YANG MANA, supaya orangnya tahu apa yang harus
        // dibatalkan lebih dulu alih-alih menebak.
        $bentrok = $user->offDays()->bertindih($data['mulai'], $data['sampai'])->first();
        if ($bentrok) {
            return back()->withInput()->withErrors([
                'mulai' => "Anda sudah punya {$bentrok->jenisLabel()} {$bentrok->rentangLabel()}. Batalkan dulu yang itu bila mau menggantinya.",
            ]);
        }

        $off = $user->offDays()->create($data);

        $this->audit->log('off.create', null, [
            'jenis' => $off->jenis,
            'mulai' => $off->mulai->toDateString(),
            'sampai' => $off->sampai->toDateString(),
        ]);

        self::beriTahuPembuat($user, $off);

        return back()->with('status',
            "{$off->jenisLabel()} {$off->rentangLabel()} tercatat. Anda tidak muncul sebagai peninjau yang bisa dipilih selama rentang itu.");
    }

    public function destroy(Request $request, UserOffDay $off): RedirectResponse
    {
        // Hanya pemiliknya sendiri. Off orang lain bukan urusan siapa pun —
        // termasuk atasannya, sebab ini bukan alur persetujuan.
        abort_unless($off->user_id === $request->user()->id, 403);

        $label = $off->jenisLabel().' '.$off->rentangLabel();
        $off->delete();

        $this->audit->log('off.cancel', null, ['rentang' => $label]);

        return back()->with('status', "{$label} dibatalkan. Anda kembali muncul sebagai peninjau yang bisa dipilih.");
    }

    /**
     * Dokumen yang sedang ditinjau TIDAK ditarik — pembuatnya cukup diberi tahu
     * supaya ia tak menunggu tanpa penjelasan.
     *
     * Statis & publik supaya kanal HP (OffDayApiController) memakai pemberitahuan
     * yang SAMA: off yang dicatat dari HP tak boleh diam-diam melewatkan kabar
     * yang dikirim bila dicatat dari laptop.
     */
    public static function beriTahuPembuat(User $user, UserOffDay $off): void
    {
        $dokumen = Document::with('creator')
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['waiting_for_review', 'in_review'])
            ->get();

        $sampai = $off->sampai->translatedFormat('j M');

        foreach ($dokumen as $doc) {
            $doc->creator?->notify(new DocumentNotification(
                $doc,
                "Peninjau {$user->name} off sampai {$sampai}. {$doc->displayNumber()} tetap di tangannya.",
                'bi-calendar-x',
                'documents.show',
            ));
        }
    }
}
