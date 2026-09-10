<?php

namespace App\Http\Controllers;

use App\Jobs\AnalisisAi;
use App\Models\Document;
use App\Models\Review;
use App\Notifications\DocumentNotification;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\NullReviewer;
use App\Services\AuditService;
use App\Services\ReviewScreen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

/**
 * Peninjauan Management Development — tahap KEDUA.
 *
 * MD memeriksa SISTEMATIKA PENULISAN (typo, salah tulis, kalimat tak sesuai),
 * sesudah SH/DH memeriksa SUBSTANSI. Perannya MENJEMBATANI menuju PJO:
 * meloloskan atau mengembalikan, TIDAK menyetujui.
 *
 * Sengaja terpisah dari {@see ReviewController} — tahapnya beda, izinnya beda
 * (`document.review_md`), dan bentuk formulirnya beda (keputusan + catatan,
 * bukan anotasi per-item). Menggabungkan keduanya akan membuat satu controller
 * melayani dua tahap dengan aturan berlainan, dan itu sumber kekeliruan.
 *
 * Akun MD bersifat BERSAMA — dipakai siapa pun di departemen itu.
 */
class MdReviewController extends Controller
{
    /** Dipakai berulang di tiga titik masuk; disatukan agar pesannya tak berbeda-beda. */
    private const BUKAN_TAHAP_MD = 'Dokumen tidak sedang menunggu peninjauan Management Development.';

    public function __construct(
        private readonly AuditService $audit,
        private readonly ReviewScreen $layar,
    ) {}

    /** Antrean dokumen yang menunggu peninjauan MD (seluruh departemen). */
    public function index(Request $request): mixed
    {
        $documents = Document::with('type', 'department', 'creator', 'reviewer')
            ->where('status', 'verifikasi_md')
            ->latest('updated_at')
            ->urut($request->sort, $request->dir)
            ->paginate(15)->withQueryString();

        return Inertia::render('Review/Md', [
            // `through()`, bukan `map()`: yang kedua memulangkan Collection
            // biasa dan paginasinya mati diam-diam (pelajaran Fase 6).
            'documents' => $documents->through(fn (Document $d) => $d->barisDaftar($request->user()) + [
                // Kolom khas layar ini: siapa yang sudah memeriksa SUBSTANSI-nya.
                'peninjau' => $d->reviewer?->name,
            ]),
        ]);
    }

    /**
     * Halaman tinjau MD — memakai VIEW YANG SAMA dengan SH/DH.
     *
     * Pemilik meminta tampilannya sama persis: pratinjau dokumen berdampingan
     * dengan kotak catatan per-bagian, sebab itulah yang memudahkan menelusuri
     * naskah. Yang berbeda hanya teks petunjuk, tujuan formulir, dan ada
     * tidaknya panel AI.
     */
    public function show(Request $request, Document $document): mixed
    {
        abort_unless($document->status === 'verifikasi_md', 403, self::BUKAN_TAHAP_MD);

        return Inertia::render('Review/Show', array_merge($this->layar->bersama($document), [
            // --- penyesuaian khusus tahap MD ---
            'backUrl' => route('review.md'),
            'formAction' => route('review.md.store', $document),

            // Tanda ✓/✗ per Tindakan Pengendalian hanya ada pada JSA, sedangkan
            // tahap MD tak pernah menerima JSA (Document::perluTinjauanMd()
            // saat ini hanya SOP). Dikirim tegas `false` supaya tahap ini tak
            // ikut berubah bentuk bila kelak jenis lain masuk ke MD.
            'pakaiVerdict' => false,

            // Panel AI HILANG bila Admin mematikannya untuk akun ini.
            'aiUrl' => $request->user()->ai_review_enabled ? route('review.md.ai', $document) : null,

            'petunjuk' => 'Periksa <strong>cara penulisannya</strong>: typo, salah tulis, kalimat tak sesuai, '
                .'dan teks yang jelas bukan pada tempatnya. Kebenaran isi sudah menjadi bagian peninjau sebelumnya. '
                .'Bila ada satu saja catatan, pilih <strong>Kembalikan untuk Perbaikan</strong>.',

            'labelTolak' => 'Kembalikan untuk Perbaikan',
            'labelLolos' => 'Loloskan ke PJO',
            'konfirmasiTolak' => 'Kembalikan dokumen ke pembuat untuk perbaikan penulisan?',
            'konfirmasiLolos' => 'Loloskan dokumen ke tahap persetujuan PJO?',
        ]));
    }

    /**
     * Keputusan MD.
     *
     * Kosakata keputusannya sengaja SAMA dengan SH/DH (`approve`/`reject`)
     * karena formulirnya memang view yang sama. Memakai istilah berbeda hanya
     * menambah satu terjemahan yang bisa salah tanpa memberi manfaat apa pun.
     *
     * - approve → `pending_approval`, PJO dinotifikasi
     * - reject  → `rejected`, kembali ke pembuat (alasan WAJIB)
     */
    public function store(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->status === 'verifikasi_md', 403, self::BUKAN_TAHAP_MD);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            // Alasan wajib saat mengembalikan — pembuat harus tahu APA yang
            // perlu diperbaiki, bukan sekadar bahwa dokumennya dikembalikan.
            'summary' => ['nullable', 'required_if:decision,reject', 'string', 'max:2000'],
        ], [
            'summary.required_if' => 'Alasan wajib diisi saat mengembalikan dokumen.',
        ]);

        // Catatan MD menumpang tabel `reviews` yang sudah ada — cukup memuat
        // peninjau, keputusan, dan ringkasan. Tak perlu tabel tersendiri.
        Review::create([
            'document_id' => $document->id,
            'reviewer_id' => $request->user()->id,
            'revision_round' => $document->revision_round,
            'decision' => $data['decision'] === 'approve' ? 'approved' : 'needs_revision',
            'summary' => '[Management Development] '.($data['summary'] ?? 'Sistematika penulisan sesuai.'),
        ]);

        return $data['decision'] === 'approve'
            ? $this->loloskan($document)
            : $this->kembalikan($document, $data['summary']);
    }

    /**
     * Bantuan AI khusus tahap MD — fokus PENULISAN, bukan substansi.
     *
     * Dua saklar harus sama-sama menyala: konfigurasi AI seluruh aplikasi, DAN
     * saklar per-akun yang dipegang Admin. Panel AI memang sudah disembunyikan
     * di layar saat saklarnya mati, tapi pemeriksaan di sini tetap perlu —
     * menyembunyikan tombol bukan pengamanan, permintaan tetap bisa dikirim
     * langsung ke alamatnya.
     *
     * Penyedia AI-nya sama dengan SH/DH; yang membedakan hanya FOKUS yang
     * dikirim — instruksi di dalam prompt. Teks anomali yang tertinggal
     * ("lorem ipsum", "TBD", "asdf") ditandai sebagai temuan KRITIS, sebab
     * dokumen resmi yang terbit membawa teks semacam itu adalah cacat serius.
     */
    public function aiAnalyze(Request $request, Document $document, AiReviewerInterface $ai): JsonResponse
    {
        abort_unless($document->status === 'verifikasi_md', 403, self::BUKAN_TAHAP_MD);

        if ($galat = $this->aiGuard($request, $ai)) {
            return $galat;
        }

        AnalisisAi::mulai($document, $request->user()->id, AiReviewerInterface::FOKUS_PENULISAN);

        return response()->json(['status' => 'antre'], 202);
    }

    /**
     * Dijemput panel tiap beberapa detik selagi AnalisisAi berjalan di
     * pekerja antrean. Penjagaan SAMA seperti aiAnalyze() — rute GET ini bisa
     * dipanggil langsung, menyembunyikan tombol bukan otorisasi (CLAUDE.md §4).
     */
    public function aiStatus(Request $request, Document $document, AiReviewerInterface $ai): JsonResponse
    {
        abort_unless($document->status === 'verifikasi_md', 403, self::BUKAN_TAHAP_MD);

        if ($galat = $this->aiGuard($request, $ai)) {
            return $galat;
        }

        $hasil = AnalisisAi::hasil($document, $request->user()->id, AiReviewerInterface::FOKUS_PENULISAN);

        return $hasil === null
            ? response()->json(['status' => 'antre'])
            : response()->json(array_merge(['status' => 'selesai', 'enabled' => true], $hasil));
    }

    /**
     * Penjagaan bersama aiAnalyze()/aiStatus(): toggle per-akun ditegakkan di
     * server, lalu satu sumber untuk "AI mati" — NullReviewer membedakan
     * "dimatikan" dari "kunci belum diisi" (Fase 3 pra-produksi).
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

    private function loloskan(Document $document): RedirectResponse
    {
        $document->update(['status' => 'pending_approval']);
        $this->audit->log('document.md_approve', $document->id);

        $document->approver?->notify(new DocumentNotification(
            $document,
            "Dokumen {$document->doc_number} perlu disetujui.",
            'bi-patch-check',
            'approvals.show',
            penting: true,
        ));

        // Penyusun diberi tahu bahwa tahap MD terlewati — tanpa ini dokumennya
        // seolah menghilang antara "ditinjau" dan "Berlaku". Bel saja.
        Notification::send($document->penyusun(), new DocumentNotification(
            $document,
            "Dokumen {$document->doc_number} lolos Management Development — menunggu persetujuan.",
            'bi-check2',
            'documents.show'
        ));

        return redirect()->route('review.md')
            ->with('status', "Dokumen {$document->doc_number} diloloskan ke persetujuan.");
    }

    private function kembalikan(Document $document, string $alasan): RedirectResponse
    {
        $document->update(['status' => 'rejected']);
        $this->audit->log('document.md_reject', $document->id);

        Notification::send($document->penyusun(), new DocumentNotification(
            $document,
            "Dokumen {$document->doc_number} dikembalikan Management Development: {$alasan}",
            'bi-arrow-counterclockwise',
            'documents.edit',
            penting: true,
            catatan: $alasan,
        ));

        return redirect()->route('review.md')
            ->with('status', "Dokumen {$document->doc_number} dikembalikan untuk perbaikan penulisan.");
    }
}
