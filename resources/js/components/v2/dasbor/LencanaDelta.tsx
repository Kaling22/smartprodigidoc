import { ArrowDownRight01Icon, ArrowUpRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Badge } from '@/components/ui-maia/badge';
import { cn } from '@/lib/utils';

/**
 * Lencana kenaikan — SATU komponen untuk kartu KPI (§5c) dan baris sorotan
 * Performa PIC (§5d), karena keduanya memang mengirim bentuk yang sama
 * (`arah` + `delta` + `deltaLabel`, lihat `DasborTampilan::sorotan()`).
 *
 * Pembagian tugas antara server dan layar di sini SENGAJA tajam, dan melanggar
 * salah satunya akan membuat lencananya berbohong tanpa satu gerbang pun merah:
 *
 *  • **`delta` menentukan ARAH PANAH.** Tandanya, bukan artinya.
 *  • **`arah` menentukan WARNA** — ia datang dari server dan berbunyi "naiknya
 *    angka ini kabar apa". Naiknya "Dokumen Ditolak" buruk; naiknya "Berlaku"
 *    baik. TSX DILARANG menyimpulkan ini dari `delta > 0` (CLAUDE.md §4): tanda
 *    angka tak pernah tahu apakah yang tumbuh itu hal yang diinginkan.
 *  • **`deltaLabel` yang DICETAK**, bukan `delta`. Server sudah memformatnya,
 *    termasuk kasus pembagi-nol yang keluar sebagai `+100.0%` alih-alih `∞%`.
 *
 * `deltaLabel === null` berarti kedua periode kosong; lencananya tak digambar
 * sama sekali — bukan digambar `0%`. Nol di atas kartu kosong cuma menyuruh
 * orang mencari makna yang tak ada.
 *
 * Dua RUPA, satu logika: pil ber-`Badge` (bawaan, dipakai kartu Sebaran &
 * Performa PIC) dan `polos` — teks berwarna + panah tanpa pil, bentuk yang
 * dipakai kartu KPI dan Lacak Status mengikuti `docs/dasbor_ref/card.png`.
 * Warna, arah, dan sumber teksnya IDENTIK; yang beda cuma pembungkusnya, jadi
 * tak ada aturan yang bisa menyimpang antar-rupa.
 *
 * Panahnya diimpor LANGSUNG dari hugeicons, tidak lewat `v2/Ikon.tsx`: peta di
 * sana cuma memuat kunci `bi-*` yang benar-benar dikirim server, dan panah ini
 * tak pernah punya nama `bi-*` (alasan yang sama dengan `SiteHeader`).
 */
export function LencanaDelta({
    arah,
    delta,
    deltaLabel,
    polos = false,
    className,
}: {
    arah: string;
    delta: number | null;
    deltaLabel: string | null;
    /** Tanpa pil `Badge` — teks berwarna + panah saja (`card.png`). */
    polos?: boolean;
    className?: string;
}) {
    if (deltaLabel === null || delta === null) {
        return null;
    }

    const naik = delta >= 0;
    // `baik`/`buruk` menyatakan arti KENAIKAN; turunnya membalik arti itu.
    // `netral` tak pernah berwarna — kartu proses berjalan memang tak punya
    // jawaban jujur atas "ini kabar baik atau buruk" (lihat DasborTampilan::tile).
    const kabar = arah === 'netral' ? 'netral' : naik === (arah === 'baik') ? 'baik' : 'buruk';

    const isi = (
        <>
            <HugeiconsIcon
                icon={naik ? ArrowUpRight01Icon : ArrowDownRight01Icon}
                strokeWidth={2}
                aria-hidden="true"
                className="size-3.5"
            />
            {deltaLabel}
        </>
    );

    const warna = cn(
        kabar === 'baik' && 'text-chart-5',
        kabar === 'buruk' && 'text-destructive',
        kabar === 'netral' && 'text-muted-foreground',
    );

    if (polos) {
        return (
            <span className={cn('inline-flex items-center gap-1 font-medium tabular-nums', warna, className)}>
                {isi}
            </span>
        );
    }

    return (
        <Badge
            variant="outline"
            className={cn(
                'gap-1 font-medium tabular-nums',
                warna,
                kabar === 'baik' && 'border-chart-5/40',
                kabar === 'buruk' && 'border-destructive/40',
                className,
            )}
        >
            {isi}
        </Badge>
    );
}
