<?php

namespace App\Services\Ai;

use App\Models\Document;
use Illuminate\Support\Facades\Log;

/**
 * Rantai penyedia: coba yang utama, pindah ke cadangan bila gagal.
 *
 * Bukan penyedia AI, melainkan PEMBUNGKUS beberapa penyedia — jadi menambah
 * cadangan tidak menyentuh GeminiReviewer/OpenRouterReviewer sama sekali, dan
 * controller tetap hanya mengenal AiReviewerInterface.
 *
 * Yang disebut "gagal" hanya kegagalan MEMANGGIL penyedia (kredit habis, key
 * dicabut, penyedia tumbang) — ditandai kunci `gagal` dari AbstractAiReviewer.
 * Jawaban AI yang sah tetapi tanpa temuan BUKAN kegagalan dan tidak memicu
 * cadangan: memanggil penyedia kedua untuk itu hanya membakar kuota dua kali.
 */
class FallbackReviewer implements AiReviewerInterface
{
    /** @param  list<AiReviewerInterface>  $reviewers  urut: utama lebih dulu, cadangan sesudahnya */
    public function __construct(private readonly array $reviewers) {}

    public function isEnabled(): bool
    {
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Penyedia di dalam rantai, urut: utama lebih dulu.
     *
     * SENGAJA bukan ping() yang menggabungkan hasil kedua penyedia jadi satu
     * jawaban: layar Konfigurasi Sistem perlu tahu penyedia MANA yang mati, dan
     * penggabungan justru membuang tepat informasi itu — "salah satu gagal" tak
     * memberi tahu Admin kunci mana yang harus diperbaiki.
     *
     * @return list<AiReviewerInterface>
     */
    public function daftar(): array
    {
        return $this->reviewers;
    }

    /**
     * Kontrak antarmuka, untuk pemanggil yang hanya bertanya "AI-nya hidup?".
     * Layar Admin memakai daftar() supaya bisa melaporkan per penyedia.
     */
    public function ping(): ?string
    {
        foreach ($this->reviewers as $reviewer) {
            if ($reviewer->isEnabled()) {
                return $reviewer->ping();
            }
        }

        return 'AI dinonaktifkan atau kunci belum disetel.';
    }

    public function review(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): array
    {
        $terakhir = null;

        foreach ($this->reviewers as $reviewer) {
            if (! $reviewer->isEnabled()) {
                continue;   // key kosong — lewati diam-diam, bukan kegagalan
            }

            $hasil = $reviewer->review($document, $contentMap, $fokus);

            if (empty($hasil['gagal'])) {
                return $hasil;
            }

            $terakhir = $hasil;

            // Timeout/koneksi putus: berhenti di sini. Cadangan tak akan lebih
            // cepat (model gratis yang sama lambatnya, jaringan yang sama), jadi
            // meneruskan hanya membuat peninjau menunggu dua kali batas waktu
            // untuk kegagalan yang sama.
            if (! empty($hasil['gagal_koneksi'])) {
                Log::warning('AI gagal karena koneksi/timeout, cadangan dilewati', [
                    'provider' => $reviewer::class,
                    'summary' => $hasil['summary'] ?? '',
                ]);

                return $hasil;
            }

            Log::warning('AI provider gagal, mencoba cadangan', [
                'provider' => $reviewer::class,
                'summary' => $hasil['summary'] ?? '',
            ]);
        }

        // Semua penyedia gagal (atau tak satu pun berkey): kembalikan galat
        // TERAKHIR apa adanya — peninjau perlu tahu sebabnya, bukan pesan kabur.
        return $terakhir ?? [
            'summary' => 'AI tidak dikonfigurasi (API key kosong).',
            'findings' => [],
        ];
    }
}
