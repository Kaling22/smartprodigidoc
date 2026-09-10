<?php

namespace Tests\Unit;

use App\Services\DocumentService;
use PHPUnit\Framework\TestCase;

/**
 * Ambang roll-over Edisi/Revisi — tabel penuh, tanpa basis data.
 *
 * KENAPA ADA: ambangnya baru saja bergeser sekali (4 → 5, keputusan pemilik
 * K-B), dan cara bergesernya tak terlihat di mana pun. `nextEditionRevision()`
 * tak pernah melempar, tak pernah mengembalikan nilai mustahil, dan alur nyata
 * yang memakainya (RevisionTypeBTest) cuma menyentuh SATU titik di tabel —
 * ujung siklusnya. Ambang yang bergeser satu angka karena itu bisa lolos
 * seluruh suite sambil diam-diam memakan satu nomor revisi milik pengguna.
 *
 * Yang dikunci di sini seluruh tabel 0..6 sekaligus, jadi pergeseran ke arah
 * mana pun langsung merah — termasuk pergeseran yang "cuma satu".
 */
class RollOverRevisiTest extends TestCase
{
    public function test_tabel_roll_over_nol_sampai_enam(): void
    {
        $harapan = [
            0 => [1, 1],
            1 => [1, 2],
            2 => [1, 3],
            3 => [1, 4],
            4 => [1, 5],   // revisi 5 ADA sejak K-B; dulu di sinilah edisi naik
            5 => [2, 1],   // yang KEENAM naik edisi, revisi mulai 1 (W-5)
            6 => [2, 1],   // baris warisan ber-revisi liar tetap tergulung
        ];

        foreach ($harapan as $noRevisi => $berikut) {
            $this->assertSame(
                $berikut,
                DocumentService::nextEditionRevision(1, $noRevisi),
                "Edisi 1 Rev {$noRevisi} → versi berikutnya",
            );
        }

        $this->assertSame(5, DocumentService::MAKS_REVISI, 'ambang tersimpan di SATU konstanta');
    }
}
