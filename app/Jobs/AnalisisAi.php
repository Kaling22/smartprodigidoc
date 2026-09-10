<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Pengaturan;
use App\Services\Ai\AiReviewerInterface;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Menjalankan AiReviewerInterface::review() di pekerja antrean, bukan di
 * dalam request web — panggilan penyedia bisa memakan sampai TIMEOUT_DETIK
 * (180 dtk, atau 360 dtk lewat FallbackReviewer), jauh di atas batas eksekusi
 * PHP yang wajar untuk satu request web. Hasilnya dijemput lewat cache oleh
 * ReviewController::aiStatus() / MdReviewController::aiStatus().
 *
 * Konstruktor menerima ID, bukan model: payload job tersimpan APA ADANYA di
 * tabel `jobs`, dan menyerialkan Document/User utuh menaruh isinya di sana
 * tanpa terlihat (CLAUDE.md §4 — pola yang sama dengan larangan memindahkan
 * model utuh ke props Inertia).
 */
class AnalisisAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 400;

    public int $tries = 1;

    public function __construct(
        private readonly int $documentId,
        private readonly int $userId,
        private readonly string $fokus,
    ) {}

    public function handle(AuditService $audit): void
    {
        $document = Document::find($this->documentId);
        if (! $document) {
            return;
        }

        // `Pengaturan` men-cache tiap kunci SEUMUR PROSES (lihat komentar
        // `$ingatan` di Pengaturan.php) — asumsi yang benar untuk request web
        // (proses baru tiap kali) tapi SALAH untuk pekerja antrean, yang satu
        // proses hidup lintas ratusan job. Tanpa baris ini, penyedia/model/kunci
        // AI yang baru diganti dari Konfigurasi Sistem tak pernah kepakai
        // sampai `queue:work` di-restart manual — persis yang bikin bingung:
        // provider sudah diganti ke Gemini, tapi pekerja lama tetap
        // menghubungi OpenRouter karena masih ingat setelan sebelum diganti.
        // Karena itu AiReviewerInterface TAK boleh ikut di parameter method
        // (Laravel me-resolve-nya SEBELUM baris pertama handle() berjalan) —
        // diambil manual sesudah ingatan dibuang.
        Pengaturan::lupakanIngatan();
        $ai = app(AiReviewerInterface::class);

        $hasil = $ai->review($document, $document->contentMap(), $this->fokus);

        Cache::put(self::kunci($this->documentId, $this->userId, $this->fokus), $hasil, now()->addMinutes(30));
        Cache::forget(self::kunciAntre($this->documentId, $this->userId, $this->fokus));

        // AuditService::log() membaca Auth::id() (lihat AuditService.php) — di
        // dalam pekerja antrean tak ada pengguna masuk secara alami, jadi tanpa
        // ini baris auditnya tercatat dengan user_id kosong.
        Auth::onceUsingId($this->userId);

        $meta = ['findings' => count($hasil['findings'] ?? [])];
        if ($this->fokus === AiReviewerInterface::FOKUS_PENULISAN) {
            $audit->log('document.md_ai_review', $this->documentId, ['fokus' => $this->fokus] + $meta);
        } else {
            $audit->log('document.ai_review', $this->documentId, $meta);
        }
    }

    /** Job sengaja tak pernah mengulang (lihat $tries); ini jaring terakhir agar penjemput tak berputar selamanya bila pekerjaannya benar-benar mati. */
    public function failed(\Throwable $e): void
    {
        Cache::put(self::kunci($this->documentId, $this->userId, $this->fokus), [
            'summary' => "Gagal memanggil AI ({$e->getMessage()}). Reviewer dapat melanjutkan secara manual.",
            'findings' => [],
            'gagal' => true,
        ], now()->addMinutes(30));
        Cache::forget(self::kunciAntre($this->documentId, $this->userId, $this->fokus));
    }

    private static function kunci(int $documentId, int $userId, string $fokus): string
    {
        return "ai-tinjau:{$documentId}:{$userId}:{$fokus}";
    }

    private static function kunciAntre(int $documentId, int $userId, string $fokus): string
    {
        return self::kunci($documentId, $userId, $fokus).':antre';
    }

    /**
     * Mulai analisis bila belum ada yang antre untuk dokumen+pengguna+fokus
     * ini. `Cache::add()` atomik, jadi klik ganda (dobel klik, dua tab) tak
     * melahirkan dua job berjalan bersamaan.
     */
    public static function mulai(Document $document, int $userId, string $fokus): void
    {
        if (Cache::add(self::kunciAntre($document->id, $userId, $fokus), true, 600)) {
            Cache::forget(self::kunci($document->id, $userId, $fokus));
            self::dispatch($document->id, $userId, $fokus);
        }
    }

    /** @return array{summary: string, findings: array}|null null = belum selesai. */
    public static function hasil(Document $document, int $userId, string $fokus): ?array
    {
        return Cache::get(self::kunci($document->id, $userId, $fokus));
    }
}
