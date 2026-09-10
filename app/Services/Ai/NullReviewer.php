<?php

namespace App\Services\Ai;

use App\Models\Document;

/**
 * Penyedia kosong: dipakai saat AI dimatikan ATAU saat setelannya belum lengkap.
 *
 * Alasannya dibawa sebagai nilai, bukan dibiarkan seragam, karena kedua keadaan
 * itu menuntut tindakan yang berbeda dari Admin — "sengaja dimatikan" tak perlu
 * diapa-apakan, "kunci belum diisi" harus segera diisi. Sampai rencana
 * pra-produksi Fase 3 keduanya berbunyi persis sama, dan itulah sebabnya baris
 * `ai.key` yang hilang bisa bertahan berhari-hari tanpa ada yang sadar.
 */
class NullReviewer implements AiReviewerInterface
{
    public const DIMATIKAN = 'Fitur AI Review sedang dinonaktifkan.';

    public const TAK_LENGKAP = 'AI aktif, tetapi penyedia/kunci API/model belum lengkap di Konfigurasi Sistem.';

    public function __construct(private readonly string $alasan = self::DIMATIKAN) {}

    public function review(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): array
    {
        return [
            'summary' => $this->alasan,
            'findings' => [],
        ];
    }

    public function ping(): ?string
    {
        return $this->alasan;
    }

    public function isEnabled(): bool
    {
        return false;
    }
}
