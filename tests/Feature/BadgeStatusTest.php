<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;

/**
 * Badge status dokumen (partials/_badge-status).
 *
 * Yang dikunci:
 *  1. SETIAP status yang bisa muncul punya rupa — dulu peta warna disalin di
 *     empat berkas Blade dan `verifikasi_md` tak ada di satu pun, sehingga
 *     tampil abu-abu tanpa identitas.
 *  2. Teks SETIAP badge cukup kontras untuk dibaca. Warna teksnya tidak
 *     ditabelkan melainkan DIHITUNG dari luminansi warna status; tes ini yang
 *     memastikan hitungan itu benar-benar menghasilkan angka yang lolos WCAG AA
 *     — termasuk untuk warna status yang ditambahkan kemudian.
 *
 *     Sejak spec dashboard v3 R1 tak ada lagi badge bergradasi berteks putih,
 *     jadi `published`/`rejected`/`obsolete` yang dulu dikecualikan kini ikut
 *     diukur. Itu bukan sekadar cakupan yang bertambah: ketiganya adalah status
 *     yang paling sering dibaca, dan sampai sekarang warna teksnya belum pernah
 *     sekali pun dihitung.
 */
class BadgeStatusTest extends TestCase
{
    /** Ambang WCAG AA untuk teks berukuran kecil. */
    private const AMBANG_KONTRAS = 4.5;

    /** Opacity latar badge — harus sama dengan --sb di partial. */
    private const OPACITY_LATAR = .12;

    public function test_setiap_status_punya_rupa_sendiri(): void
    {
        foreach (array_keys(Document::STATUS_LABELS) as $status) {
            $this->assertArrayHasKey(
                $status,
                Document::STATUS_META,
                "Status `{$status}` punya label tapi tak punya rupa di STATUS_META — "
                . 'badge-nya akan jatuh ke abu-abu tanpa identitas.'
            );

            [$c1, $c2, $ikon] = Document::STATUS_META[$status];

            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $c1);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $c2);
            $this->assertStringStartsWith('bi-', $ikon, "Ikon `{$status}` bukan Bootstrap Icons.");
        }

        // Status aktif (bukan warisan) tak boleh berbagi ikon — ikonlah yang
        // membedakannya bagi mata yang sulit membedakan warna.
        $aktif = array_diff_key(
            Document::STATUS_META,
            array_flip(['submitted', 'needs_revision', 'archived'])
        );
        $ikonAktif = array_column($aktif, 2);
        $this->assertSame(
            count($ikonAktif),
            count(array_unique($ikonAktif)),
            'Ada dua status aktif memakai ikon yang sama: ' . implode(', ', $ikonAktif)
        );
    }

    public function test_teks_badge_cukup_kontras(): void
    {
        $diuji = 0;

        foreach (Document::STATUS_META as $status => [$c1]) {
            $html = view('partials._badge-status', ['status' => $status])->render();

            $this->assertSame(
                1,
                preg_match('/--sf:(#[0-9a-f]{6})/', $html, $cocok),
                "Partial tak memancarkan --sf untuk `{$status}`."
            );

            // Latar badge = warna status pada 12% di atas permukaan kartu putih.
            [$lr, $lg, $lb] = sscanf($c1, '#%02x%02x%02x');
            $latar = [
                255 + ($lr - 255) * self::OPACITY_LATAR,
                255 + ($lg - 255) * self::OPACITY_LATAR,
                255 + ($lb - 255) * self::OPACITY_LATAR,
            ];

            $rasio = $this->rasioKontras(sscanf($cocok[1], '#%02x%02x%02x'), $latar);

            $this->assertGreaterThanOrEqual(
                self::AMBANG_KONTRAS,
                $rasio,
                sprintf(
                    'Badge `%s`: teks %s di atas latarnya hanya %.2f:1, di bawah ambang %.1f:1. '
                    . 'Setel ulang ambang luminansi di partials/_badge-status.',
                    $status,
                    $cocok[1],
                    $rasio,
                    self::AMBANG_KONTRAS
                )
            );

            $diuji++;
        }

        $this->assertGreaterThan(0, $diuji, 'Tak ada badge yang teruji — apakah STATUS_META kosong?');
    }

    /**
     * Spec dashboard v3 R1: satu kelas untuk semua badge. Kalau partial ini
     * memakai kelasnya sendiri lagi, seluruh audit R1 batal diam-diam.
     */
    public function test_badge_status_memakai_kelas_bersama(): void
    {
        $html = view('partials._badge-status', ['status' => 'published'])->render();

        $this->assertStringContainsString('badge-soft', $html);
        $this->assertStringNotContainsString('pp-status', $html);
    }

    public function test_status_tak_dikenal_tetap_dirender_tanpa_galat(): void
    {
        $html = view('partials._badge-status', ['status' => 'status_karangan'])->render();

        $this->assertStringContainsString('badge-soft', $html);
        $this->assertStringContainsString('status_karangan', $html, 'Label cadangan harus memakai kunci mentahnya.');
    }

    /** Rasio kontras WCAG 2.x antara dua warna RGB. */
    private function rasioKontras(array $depan, array $belakang): float
    {
        $a = $this->luminansi($depan);
        $b = $this->luminansi($belakang);

        return (max($a, $b) + .05) / (min($a, $b) + .05);
    }

    /** Luminansi relatif WCAG dari satu warna RGB. */
    private function luminansi(array $rgb): float
    {
        $kanal = array_map(static function ($c) {
            $c /= 255;

            return $c <= .03928 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4;
        }, $rgb);

        return .2126 * $kanal[0] + .7152 * $kanal[1] + .0722 * $kanal[2];
    }
}
