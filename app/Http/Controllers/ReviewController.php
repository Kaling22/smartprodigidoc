<?php

namespace App\Http\Controllers;

use App\Jobs\AnalisisAi;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\NullReviewer;
use App\Services\AuditService;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentWizard;
use App\Services\ReviewAccess;
use App\Services\ReviewDecision;
use App\Services\ReviewerAvailability;
use App\Services\ReviewScreen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

/**
 * Peninjauan dokumen (PRD v2 §3). Peninjau = document.reviewer_id (ditentukan
 * saat pembuat mengisi, sesuai jabatan pembuat). Per-item annotations; lolos ->
 * pending_approval, ada temuan -> rejected (kembali ke pembuat).
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ReviewAccess $akses,
        private readonly DocumentParticipantResolver $peserta,
        private readonly ReviewerAvailability $ketersediaan,
        private readonly ReviewScreen $layar,
        private readonly DocumentWizard $wizard,
    ) {}

    /** Antrian "Tinjau Dokumen" + bagian "Status Revisi" (dokumen yang saya tolak). */
    public function index(Request $request)
    {
        $user = $request->user();

        // Perlu ditinjau: menunggu (belum disentuh) + sedang ditinjau.
        $documents = $this->akses->antrian($user, ['waiting_for_review', 'in_review'])
            ->latest('submitted_at')
            ->urut($request->sort, $request->dir)
            ->paginate(15)->withQueryString();

        // Status Revisi: dokumen yang saya tolak — dipantau (bisa Batalkan Revisi) (v3.1 §4.3).
        //
        // Ikut memakai `sort`/`dir` yang SAMA dengan antrean di atasnya: kedua
        // tabel ada di satu halaman, dan membiarkan yang satu bisa diurutkan
        // sementara yang lain tidak justru terbaca sebagai tombol yang rusak.
        $statusRevisi = $this->akses->antrian($user, ['rejected'])
            ->latest('updated_at')
            ->urut($request->sort, $request->dir)
            ->get();

        return Inertia::render('Review/Index', [
            // `through()`, BUKAN `map()`: memetakan isi paginator dengan `map()`
            // memulangkan Collection biasa, sehingga `links`/`from`/`to`/`total`
            // lenyap dan paginasinya mati tanpa satu pun galat (pelajaran Fase 6).
            'documents' => $documents->through(fn (Document $d) => $d->barisDaftar($user) + [
                // Syarat MURAH saja di sini (jenis + pemegang), persis seperti
                // tombol Alihkan di Blade lama: "ada peninjau lain yang bisa
                // menerima" butuh resolver, dan menjalankannya per baris
                // membebani justru halaman milik orang yang antreannya paling
                // panjang. Penjaga sebenarnya tetap di `alihkan()`.
                'boleh_alih' => $d->type?->code === 'JSA' && $d->reviewer_id === $user->id,
            ]),
            'statusRevisi' => $statusRevisi->map(fn (Document $d) => $d->barisDaftar($user))->values()->all(),
        ]);
    }

    /** Halaman tinjauan per-item. Membuka dokumen menandai in_review (v3.1 §4.2). */
    public function show(Request $request, Document $document, ReviewDecision $keputusan)
    {
        $this->authorizeReviewer($request, $document, ['waiting_for_review', 'in_review']);

        // Reviewer mulai menyentuh → in_review (pembuat tak bisa Tarik lagi).
        if ($document->status === 'waiting_for_review') {
            $document->update(['status' => 'in_review']);
            $this->audit->log('document.review_start', $document->id);
        }

        // Kandidat pengalihan (PLAN-REVISI-v6 butir 3). Null = tombol "Alihkan"
        // tak dirender sama sekali — halaman ini dipakai bersama tahap MD, dan
        // di sana pengalihan memang tak berlaku.
        $alihKandidat = $this->kandidatAlih($document, $request->user()->id);

        /*
        | Alasan pengalihan, bagi orang yang MENERIMA dokumennya (PLAN B / B4).
        |
        | Lonceng & email memang sudah mengabarkannya, tapi alasannya hanya ikut
        | di email: `DocumentNotification::toArray()` menyimpan `message` saja,
        | jadi begitu notifikasinya ditandai terbaca, sebab pengalihan lenyap
        | dari layar. Dibaca ulang di sini supaya ia bertahan, dan berada persis
        | di halaman tempat penerimanya akan bekerja — tanpa kolom baru.
        |
        | Dicocokkan dengan NAMA pemegang sekarang karena itulah yang tersimpan
        | di meta (lihat alihkan()); baris lama untuk pemegang sebelumnya karena
        | itu tak ikut tampil.
        */
        $pengalihan = AuditLog::where('document_id', $document->id)
            ->where('action', 'document.reassign_review')
            ->latest('id')->first();

        if ($pengalihan && ($pengalihan->meta_json['ke'] ?? null) !== $document->reviewer?->name) {
            $pengalihan = null;
        }

        return Inertia::render('Review/Show', array_merge($this->layar->bersama($document), [
            // Alasan pengalihan diratakan: `AuditLog` membawa `meta_json` utuh,
            // dan hanya tiga kunci ini yang dicetak.
            'pengalihan' => $pengalihan ? [
                'dari' => $pengalihan->meta_json['dari'] ?? 'peninjau sebelumnya',
                'alasan' => $pengalihan->meta_json['alasan'] ?? '',
                'waktu' => $pengalihan->created_at?->format('d/m/Y H:i').' WITA',
            ] : null,
            // Dikirim EKSPLISIT — halaman TSX sengaja tak memberinya nilai
            // bawaan, supaya tahap MD bisa mengirim null untuk menyembunyikan
            // panel AI.
            // Panel AI HILANG bila Admin mematikannya untuk akun ini — aturan
            // yang sama dengan tahap MD (MdReviewController:84). Sebelum
            // rencana pra-produksi Fase 3, satu izin ini ditegakkan di satu
            // jalur saja: SH yang AI-nya dicabut tetap mendapat panelnya di sini.
            'aiUrl' => $request->user()->ai_review_enabled ? route('review.ai', $document) : null,
            'formAction' => route('review.store', $document),
            'backUrl' => route('review.index'),
            // Tanda ✓/✗ per Tindakan Pengendalian (JSA). Kondisinya diambil dari
            // service yang sama dengan penegakan di server, sehingga layar tak
            // pernah menawarkan bentuk tombol yang kelak ditolak.
            'pakaiVerdict' => $keputusan->pakaiVerdict($document),
            /*
            | Papan yang sama dengan wizard: peninjau yang off tetap terkunci,
            | dan pil bebannya (Fase A) langsung terpakai — GL SHE yang
            | kebanjiran JSA bisa melihat siapa yang lebih lapang.
            |
            | Lewat `propsKandidat()`/`propsKetersediaan()` yang sama dengan
            | wizard, dan itu WAJIB: kandidat di sini koleksi model `User`
            | lengkap dengan `password`, `remember_token`, dan kunci mentah
            | `jabatan` (pelajaran Fase 9). Kuncinya `tujuan` — nama kolom yang
            | dikirim formulir pengalihan.
            */
            'alihKandidat' => $alihKandidat
                ? $this->wizard->propsKandidat(['tujuan' => $alihKandidat])['tujuan']
                : null,
            'alihKetersediaan' => $alihKandidat
                ? $this->wizard->propsKetersediaan(['tujuan' => $this->ketersediaan->untuk($alihKandidat, $document)])['tujuan']
                : [],
            'ambang' => config('smartpro.peninjau.ambang'),
            'alihUrl' => route('review.alihkan', $document),
            // Datang dari tombol Alihkan di antrian: jendelanya langsung
            // terbuka begitu halaman ini tiba (dulu `?alih=1` + bootstrap.Modal).
            'alihOtomatis' => (bool) $request->query('alih'),
        ]));
    }

    /**
     * Kandidat penerima pengalihan peninjauan, atau null bila fitur ini tak
     * berlaku di sini (PLAN-REVISI-v6 butir 3).
     *
     * Daftarnya diambil APA ADANYA dari resolver — persis kolam yang sah
     * menerima dokumen ini sejak awal, jadi tak ada permukaan otorisasi baru
     * dan konflik kepentingan (GL departemen dokumen itu sendiri) sudah
     * dikecualikan di sana. Isinya karena itu bisa memuat SH/DH SHE, bukan
     * GL saja.
     *
     * Dipakai layar DAN penjaga di server, sehingga mustahil tombolnya
     * menawarkan orang yang kelak ditolak.
     *
     * @return Collection<int, User>|null
     */
    private function kandidatAlih(Document $document, int $pemegangId): ?Collection
    {
        // Hanya JSA: beban menumpuk justru di sana (GL SHE meninjau JSA
        // enam departemen lain), dan hanya di sana peninjaunya lebih dari satu
        // orang yang setara.
        if ($document->type?->code !== 'JSA' || $document->reviewer_id !== $pemegangId) {
            return null;
        }

        if (! in_array($document->status, ['waiting_for_review', 'in_review'], true)) {
            return null;
        }

        return $this->peserta->reviewerCandidates($document)
            ->reject(fn ($u) => $u->id === $pemegangId)
            ->values();
    }

    /**
     * Simpan keputusan tinjauan.
     *
     * Isi keputusannya ada di {@see ReviewDecision} — dipakai bersama jalur API
     * aplikasi mobile, supaya alur status & notifikasi tak pernah punya dua
     * salinan yang bisa berbeda.
     */
    public function store(Request $request, Document $document, ReviewDecision $keputusan): RedirectResponse
    {
        $this->authorizeReviewer($request, $document);

        $data = $request->validate([
            // Pada JSA keputusan diturunkan dari tanda ✓/✗, jadi tombolnya tak
            // mengirim `decision` sama sekali.
            'decision' => [$keputusan->pakaiVerdict($document) ? 'nullable' : 'required', 'in:approve,reject'],
            'summary' => 'nullable|string|max:2000',
            'annotations' => 'array',
            'annotations.*.*' => 'nullable|string|max:2000',
            'annotations_ai' => 'array',
            'verdicts' => 'array',
            'verdicts.*' => 'in:sesuai,perlu_revisi',
        ]);
        $data['annotations_ai'] = $request->input('annotations_ai', []);

        $message = $keputusan->simpan($document, $request->user(), $data);

        return redirect()->route('review.index')->with('status', $message);
    }

    /**
     * AI Review Assist (PRD v2 §9). Memulai analisis di antrean (AnalisisAi
     * job — panggilan penyedia bisa sampai 360 dtk, jauh di atas batas
     * eksekusi request web) dan langsung kembali; hasilnya dijemput lewat
     * aiStatus(). AI tidak pernah approve/reject — hanya membantu peninjau.
     */
    public function aiAnalyze(Request $request, Document $document, AiReviewerInterface $ai): JsonResponse
    {
        $this->authorizeReviewer($request, $document);

        if ($galat = $this->aiGuard($request, $ai)) {
            return $galat;
        }

        AnalisisAi::mulai($document, $request->user()->id, AiReviewerInterface::FOKUS_SUBSTANSI);

        return response()->json(['status' => 'antre'], 202);
    }

    /**
     * Dijemput panel tiap beberapa detik selagi AnalisisAi berjalan di
     * pekerja antrean. Penjagaan SAMA seperti aiAnalyze() — rute GET ini bisa
     * dipanggil langsung, menyembunyikan tombol bukan otorisasi (CLAUDE.md §4).
     */
    public function aiStatus(Request $request, Document $document, AiReviewerInterface $ai): JsonResponse
    {
        $this->authorizeReviewer($request, $document);

        if ($galat = $this->aiGuard($request, $ai)) {
            return $galat;
        }

        $hasil = AnalisisAi::hasil($document, $request->user()->id, AiReviewerInterface::FOKUS_SUBSTANSI);

        return $hasil === null
            ? response()->json(['status' => 'antre'])
            : response()->json(array_merge(['status' => 'selesai', 'enabled' => true], $hasil));
    }

    /**
     * Penjagaan bersama aiAnalyze()/aiStatus(): toggle per-akun ditegakkan di
     * server (bukan hanya dengan menyembunyikan panel — `aiUrl` null cuma
     * menghilangkan tombolnya, rutenya tetap bisa dipanggil langsung), lalu
     * satu sumber untuk "AI mati" (binding AppServiceProvider mengembalikan
     * NullReviewer yang membedakan "dimatikan" dari "kunci belum diisi").
     *
     * @return JsonResponse|null null = lolos, lanjutkan.
     */
    private function aiGuard(Request $request, AiReviewerInterface $ai): ?JsonResponse
    {
        if (! $request->user()->ai_review_enabled) {
            return response()->json([
                'enabled' => false,
                'summary' => 'Bantuan AI untuk akun ini dimatikan oleh Admin.',
                'findings' => [],
            ], 403);
        }

        if (! $ai->isEnabled()) {
            return response()->json([
                'enabled' => false,
                'summary' => $ai->ping() ?? NullReviewer::DIMATIKAN,
                'findings' => [],
            ]);
        }

        return null;
    }

    /**
     * Alihkan tugas peninjauan JSA ke peninjau lain (PLAN-REVISI-v6 butir 3).
     *
     * GL SHE yang kebanjiran JSA sebelumnya tak punya jalan keluar selain
     * mendiamkannya. Pengalihannya LANGSUNG BERPINDAH (keputusan pemilik B1):
     * nol status baru, nol tabel baru, nol gerbang persetujuan — penerima cukup
     * dikabari. Yang menjaganya dari penyalahgunaan bukan alur, melainkan jejak:
     * alasannya wajib ditulis dan tercatat di audit log.
     *
     * Empat penjagaan, sengaja di controller (gaya {@see DocumentController::withdraw()}):
     * hanya JSA, hanya pemegangnya sendiri, hanya selama dokumen masih di
     * tangannya, dan hanya kepada kandidat sah yang sedang tersedia.
     */
    public function alihkan(Request $request, Document $document): RedirectResponse
    {
        $dari = $request->user();
        $kandidat = $this->kandidatAlih($document, $dari->id);

        // Satu sumber untuk tiga syarat pertama — layar memakai fungsi yang sama.
        // 422 (bukan 403) karena yang salah keadaan dokumennya, bukan orangnya;
        // kecuali saat ia memang bukan pemegangnya.
        abort_unless($document->reviewer_id === $dari->id, 403, 'Anda bukan peninjau dokumen ini.');
        abort_unless($kandidat !== null, 422, 'Pengalihan hanya berlaku untuk JSA yang sedang Anda tinjau.');

        $data = $request->validate([
            'tujuan' => 'required|integer',
            // Wajib: inilah satu-satunya keterangan yang diterima penerima, dan
            // yang kelak terbaca di "Log Pesan".
            'alasan' => 'required|string|max:2000',
        ], [], ['tujuan' => 'peninjau tujuan', 'alasan' => 'alasan pengalihan']);

        $tujuan = $kandidat->firstWhere('id', (int) $data['tujuan']);
        abort_unless((bool) $tujuan, 422, 'Tujuan pengalihan bukan peninjau yang sah untuk dokumen ini.');
        abort_unless(
            $this->ketersediaan->untukSatu($tujuan)['tersedia'],
            422,
            "{$tujuan->name} sedang tidak bisa menerima dokumen baru.",
        );

        // Kembali ke `waiting_for_review`: dokumen belum disentuh PEMEGANG BARU,
        // jadi antreannya harus terbaca "menunggu". `show()` yang membalikkannya
        // ke `in_review` begitu penerima membukanya.
        $document->update(['reviewer_id' => $tujuan->id, 'status' => 'waiting_for_review']);

        $this->audit->log('document.reassign_review', $document->id, [
            'dari' => $dari->name,
            'ke' => $tujuan->name,
            'alasan' => $data['alasan'],
        ]);

        // Penerima: pekerjaan BARU yang harus dikerjakan → ikut ke email.
        $tujuan->notify(new DocumentNotification(
            $document,
            "{$dari->name} mengalihkan peninjauan {$document->displayNumber()} kepada Anda.",
            'bi-arrow-left-right',
            'review.show',
            penting: true,
            catatan: $data['alasan'],
        ));

        // Pembuat: nasib dokumennya berpindah tangan — lonceng saja, tak ada
        // yang perlu ia kerjakan.
        $document->creator?->notify(new DocumentNotification(
            $document,
            "Peninjauan {$document->displayNumber()} dialihkan dari {$dari->name} ke {$tujuan->name}.",
            'bi-arrow-left-right',
            'documents.show',
        ));

        return redirect()->route('review.index')
            ->with('status', "Peninjauan {$document->displayNumber()} dialihkan ke {$tujuan->name}.");
    }

    /**
     * Batalkan Revisi (v3.1 §4.3): peninjau menarik penolakannya. Dokumen
     * `rejected` → kembali `in_review` (GL periksa ulang; antisipasi reviewer
     * salah tulis). Untuk dokumen tipe B lihat Approval/DocumentController.
     */
    public function cancelRevision(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeReviewer($request, $document, ['rejected']);

        $document->update(['status' => 'in_review']);
        $this->audit->log('document.cancel_revision', $document->id, ['from' => 'rejected']);
        $document->creator?->notify(new DocumentNotification(
            $document, "Penolakan dokumen {$document->doc_number} dibatalkan peninjau — sedang ditinjau ulang.", 'bi-arrow-repeat', 'documents.show'
        ));

        return back()->with('status', "Revisi {$document->doc_number} dibatalkan; dokumen ditinjau ulang.");
    }

    /**
     * Aturannya ada di {@see ReviewAccess} — dipakai bersama jalur API, agar
     * pemeriksaan wewenang mustahil berbeda antara web dan aplikasi mobile.
     */
    private function authorizeReviewer(Request $request, Document $document, array $allowedStatuses = ['in_review']): void
    {
        $this->akses->pastikan($request->user(), $document, $allowedStatuses);
    }
}
