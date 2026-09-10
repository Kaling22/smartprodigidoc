<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\AuditService;
use App\Services\DocumentParticipantResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

/**
 * Nonaktif BERJENJANG (PLAN-REVISI-v6 Fase F, keputusan F1 & F2).
 *
 * Sebelum ini mematikan dokumen adalah SEKALI KLIK: satu izin, satu update,
 * selesai. Padahal dokumen Berlaku adalah acuan kerja tujuh departemen —
 * mematikannya setara mencabut aturan, dan itu tak boleh jadi keputusan satu
 * orang. Sekarang:
 *
 *     GL mengajukan  ->  SH/DH dept  ->  MD  ->  PJO  ->  Tidak Berlaku + nomor dilepas
 *     MD mengajukan  ->  SH/DH dept  ->  PJO        ->  Tidak Berlaku + nomor dilepas
 *                             | tolak (alasan wajib, di tahap mana pun)
 *                             +-> kembali Berlaku
 *
 * DUA hal yang sengaja TIDAK dibangun di sini:
 *   • Tak ada mesin alur (status per tahap, tabel transisi). Satu status
 *     `menunggu_nonaktif` + satu kolom `nonaktif_tahap` sudah memodelkannya
 *     dengan jujur — preseden `verifikasi_md`, yang menyelamatkan 83 test.
 *   • Tak ada tabel jejak baru. Tiap pengajuan & keputusan adalah baris
 *     `approvals` ber-`kind = 'nonaktif'`, sehingga halaman "Log Pesan"
 *     (Fase C) membacanya tanpa satu sumber tambahan.
 *
 * Controller SENDIRI, bukan tambahan ke DocumentController yang sudah 574 baris
 * (CLAUDE.md §3).
 */
class NonaktifController extends Controller
{
    /** Nama tahap dalam bahasa manusia — dulu larik di dalam `nonaktif/index`. */
    private const LABEL_TAHAP = [
        'sh' => 'SH/DH Departemen',
        'md' => 'Management Development',
        'pjo' => 'PJO',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly DocumentParticipantResolver $resolver,
    ) {}

    /**
     * Ajukan nonaktif — GL atas dokumen BUATANNYA, MD & Admin lintas 7 dept.
     *
     * Wewenangnya sengaja SAMA dengan mengajukan revisi (Fase C, D1): keduanya
     * memulai perubahan nasib dokumen Berlaku, dan memberi keduanya aturan
     * berbeda berarti dua daftar yang harus dijaga tetap seiring.
     *
     * Dua penjagaan, dua kode HTTP: orang yang salah = 403; orang yang benar
     * atas dokumen yang belum siap = 422 (lihat Document::pemilikRevisi()).
     */
    public function ajukan(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->pemilikRevisi($user), 403, 'Anda tidak berhak menonaktifkan dokumen ini.');
        abort_unless(
            $document->status === 'published',
            422,
            $document->status === 'sedang_direvisi'
                ? 'Dokumen sedang direvisi — batalkan revisinya dulu sebelum mengajukan nonaktif.'
                : 'Hanya dokumen Berlaku yang dapat diajukan nonaktif.',
        );

        $data = $request->validate([
            // Wajib: inilah satu-satunya keterangan yang dibaca tiga pemutus
            // berikutnya, dan yang kelak terbaca di "Log Pesan".
            'alasan' => 'required|string|max:2000',
        ], [], ['alasan' => 'alasan nonaktif']);

        $document->update([
            'status' => 'menunggu_nonaktif',
            'nonaktif_tahap' => 'sh',
            'nonaktif_oleh' => $user->id,
        ]);

        // Baris PENGAJUAN: `decision` sengaja NULL — belum ada keputusan, yang
        // tersimpan baru alasannya.
        $document->approvals()->create([
            'approver_id' => $user->id,
            'kind' => Approval::KIND_NONAKTIF,
            'decision' => null,
            'comment' => $data['alasan'],
            'signed_at' => now(),
        ]);

        $this->audit->log('document.nonaktif_ajukan', $document->id, [
            'oleh' => $user->name,
            'alasan' => $data['alasan'],
            'tahapan' => $document->fresh()->tahapanNonaktif(),
        ]);

        $this->kabari($document, 'sh',
            "{$user->name} mengajukan {$document->displayNumber()} untuk dinonaktifkan.", $data['alasan']);

        return back()->with('status', "Pengajuan nonaktif {$document->displayNumber()} dikirim ke SH/DH departemen.");
    }

    /** Antrean "Persetujuan Nonaktif" — disaring oleh siapa yang membukanya. */
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(Document::tahapNonaktifUntuk($user) === [], 403);

        $documents = Document::with('type', 'department', 'creator', 'nonaktifOleh')
            // Alasan PENGAJUAN (baris tanpa keputusan) — satu query untuk
            // seluruh halaman, bukan satu per baris.
            ->with(['approvals' => fn ($q) => $q->where('kind', Approval::KIND_NONAKTIF)
                ->whereNull('decision')->latest('id')])
            ->menungguNonaktif($user)
            ->latest('updated_at')
            ->urut($request->sort, $request->dir)
            ->paginate(15)->withQueryString();

        return Inertia::render('Nonaktif/Index', [
            // `through()`, bukan `map()`: yang kedua memulangkan Collection
            // biasa dan paginasinya mati diam-diam (pelajaran Fase 6).
            'documents' => $documents->through(fn (Document $d) => $d->barisDaftar($user) + [
                'pengaju' => $d->nonaktifOleh?->name,
                'tahap' => $d->nonaktif_tahap,
                // Label tahap: dulu larik `$tahapLabel` di dalam Blade. Di
                // server supaya ia satu-satunya salinan (pakem P3).
                'tahapLabel' => self::LABEL_TAHAP[$d->nonaktif_tahap] ?? $d->nonaktif_tahap,
                // Alasan PENGAJUAN — baris `approvals` tanpa keputusan, sudah
                // ikut ter-eager-load di kueri di atas.
                'alasan' => $d->approvals->first()?->comment,
                // Menyetujui di tahap ini LANGSUNG mematikan dokumennya dan
                // melepas nomornya; konfirmasinya karena itu berbunyi lain.
                'tahapTerakhir' => $d->tahapNonaktifBerikut() === null,
            ]),
        ]);
    }

    /**
     * Keputusan satu tahap.
     *
     * Setuju → maju ke tahap berikut, atau (bila ini yang terakhir) dokumen
     * benar-benar mati dan nomornya dilepas. Tolak → langsung kembali Berlaku,
     * alasan wajib; tak ada "mundur satu tahap", sebab yang ditolak adalah
     * PENGAJUANNYA, bukan tahapnya.
     */
    public function putuskan(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();
        abort_unless($document->bisaMemutuskanNonaktif($user), 403, 'Dokumen ini tidak sedang menunggu keputusan Anda.');

        $data = $request->validate([
            'keputusan' => 'required|in:setuju,tolak',
            'alasan' => 'nullable|required_if:keputusan,tolak|string|max:2000',
        ], [
            'alasan.required_if' => 'Alasan wajib diisi saat menolak pengajuan nonaktif.',
        ]);

        $setuju = $data['keputusan'] === 'setuju';

        $document->approvals()->create([
            'approver_id' => $user->id,
            'kind' => Approval::KIND_NONAKTIF,
            'decision' => $setuju ? 'approved' : 'rejected',
            'comment' => $data['alasan'] ?? null,
            'signed_at' => now(),
        ]);

        return $setuju
            ? $this->teruskan($document, $user)
            : $this->tolak($document, $user, $data['alasan']);
    }

    /** Setuju: maju satu tahap, atau matikan dokumennya bila ini tahap terakhir. */
    private function teruskan(Document $document, User $user): RedirectResponse
    {
        $tahapTadi = $document->nonaktif_tahap;
        $berikut = $document->tahapNonaktifBerikut();

        $this->audit->log('document.nonaktif_setuju', $document->id, [
            'oleh' => $user->name, 'tahap' => $tahapTadi, 'berikut' => $berikut,
        ]);

        if ($berikut !== null) {
            $document->update(['nonaktif_tahap' => $berikut]);
            $this->kabari($document, $berikut,
                "Pengajuan nonaktif {$document->displayNumber()} menunggu keputusan Anda.");

            return back()->with('status', "Pengajuan nonaktif {$document->displayNumber()} diteruskan.");
        }

        // Tahap terakhir: dokumen mati DAN nomornya kembali ke kolam (F2).
        // Barisnya tetap memegang `doc_number_final` lamanya supaya arsip tetap
        // terbaca — dua dokumen boleh sah memegang nomor final yang sama.
        $document->update([
            'status' => 'obsolete',
            'obsolete_reason' => Document::OBSOLETE_DINONAKTIFKAN,
            'nonaktif_tahap' => null,
        ]);

        $this->audit->log('document.nonaktif_selesai', $document->id, [
            'nomor_dilepas' => $document->doc_number_final,
        ]);

        Notification::send($this->pihakTerkait($document), new DocumentNotification(
            $document,
            "Dokumen {$document->displayNumber()} kini Tidak Berlaku — nomornya dilepas dan bisa dipakai dokumen baru.",
            'bi-slash-circle',
            'documents.show',
            penting: true,
        ));

        return back()->with('status', "Dokumen {$document->displayNumber()} kini Tidak Berlaku; nomornya dilepas.");
    }

    /** Tolak: dokumen langsung kembali Berlaku, pengaju dikabari alasannya. */
    private function tolak(Document $document, User $user, string $alasan): RedirectResponse
    {
        $document->update(['status' => 'published', 'nonaktif_tahap' => null]);

        $this->audit->log('document.nonaktif_tolak', $document->id, [
            'oleh' => $user->name, 'alasan' => $alasan,
        ]);

        Notification::send($this->pihakTerkait($document), new DocumentNotification(
            $document,
            "Pengajuan nonaktif {$document->displayNumber()} ditolak {$user->name}: {$alasan}",
            'bi-slash-circle',
            'documents.show',
            penting: true,
            catatan: $alasan,
        ));

        return back()->with('status', "Pengajuan nonaktif {$document->displayNumber()} ditolak; dokumen kembali Berlaku.");
    }

    /**
     * Siapa yang harus dikabari pada satu tahap.
     *
     * Daftarnya diminta ke DocumentParticipantResolver — "siapa SH/DH dept X"
     * dan "siapa PJO" hanya boleh punya satu jawaban di seluruh aplikasi.
     */
    private function kabari(Document $document, string $tahap, string $pesan, ?string $catatan = null): void
    {
        $penerima = match ($tahap) {
            'sh' => $this->resolver->heads([$document->department_id]),
            'md' => User::peninjauMd()->get(),
            'pjo' => $this->resolver->pjo(),
            default => collect(),
        };

        Notification::send($penerima, new DocumentNotification(
            $document, $pesan, 'bi-slash-circle', 'nonaktif.index', penting: true, catatan: $catatan,
        ));
    }

    /** Pengaju + penyusun dokumen — yang berhak tahu bagaimana pengajuan berakhir. */
    private function pihakTerkait(Document $document): Collection
    {
        return $document->penyusun()
            ->merge([$document->nonaktifOleh])
            ->filter()
            ->unique('id')
            ->values();
    }
}
