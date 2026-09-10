<?php

namespace App\Services\Ai;

use App\Models\Document;

/**
 * Provider abstraction for AI review assist (D10). Swapping providers means
 * swapping the binding in a service provider — not rewriting call sites.
 *
 * The AI never approves or rejects. It only produces structured suggestions;
 * the human reviewer adopts, customises, or rejects them (PRD §8).
 */
interface AiReviewerInterface
{
    /**
     * Fokus peninjauan tahap PERTAMA (SH/DH): kebenaran ISI — konteks,
     * definisi, kelengkapan langkah, korelasi antar-aktivitas.
     */
    public const FOKUS_SUBSTANSI = 'substansi';

    /**
     * Fokus peninjauan tahap KEDUA (Management Development): cara PENULISAN —
     * typo, salah tulis, kalimat tak sesuai, dan teks yang jelas bukan pada
     * tempatnya (mis. "lorem ipsum" yang tertinggal).
     *
     * Sengaja dipisah sebagai FOKUS, bukan method baru: yang berbeda hanya
     * instruksi di dalam prompt, sedangkan pemanggilan penyedia, penguraian
     * jawaban, dan bentuk keluarannya sama persis.
     */
    public const FOKUS_PENULISAN = 'penulisan';

    /**
     * Analyse a document's content and return structured suggestions.
     *
     * @param  string  $fokus  self::FOKUS_SUBSTANSI atau self::FOKUS_PENULISAN
     * @return array{summary: string, findings: array<int, array{section_key:string, severity:string, issue:string, suggestion:string}>}
     */
    public function review(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): array;

    /**
     * Uji kredensial TANPA membangkitkan tinjauan. Null = berhasil; string =
     * pesan galat penyedia apa adanya.
     *
     * Sengaja BUKAN review() atas dokumen boneka: review() memakai
     * TIMEOUT_DETIK 180 dan MAX_TOKEN_JAWABAN 6000 karena model "reasoning"
     * berpikir ~2.000 token sebelum menjawab. Admin yang cuma ingin tahu
     * "kuncinya benar atau tidak" tak boleh menunggu tiga menit.
     */
    public function ping(): ?string;

    public function isEnabled(): bool;
}
