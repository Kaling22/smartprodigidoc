import { usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

/**
 * Lencana status dokumen — kembaran maia dari `components/StatusBadge.tsx`.
 *
 * Warna, label, dan ikonnya SELALU dari props `statusMeta`/`statusLabels`
 * (CLAUDE.md §4, pakem P3). Nol hex dan nol teks status diketik di sini: peta
 * itu pernah tersalin di empat Blade lalu menyimpang, dan `verifikasi_md`
 * berakhir abu-abu seperti Draft di semuanya.
 *
 * Hex-nya dipakai lewat `style`, bukan kelas Tailwind, karena nilainya baru
 * diketahui saat jalan. Bentuk `rounded-full` — bahasa pil maia; itu
 * SATU-SATUNYA perbedaan visual dari versi nova.
 */
export function StatusBadge({ status, className }: { status: string; className?: string }) {
    const { statusMeta, statusLabels } = usePage<PageProps>().props;
    const meta = statusMeta[status];
    const label = statusLabels[status] ?? status;

    if (!meta) {
        // Status di luar peta = data yang lebih baru daripada kodenya. Tampil
        // sebagai lencana netral, bukan kosong: kosong tampak seperti bug.
        return (
            <Badge variant="outline" className={className}>
                {label}
            </Badge>
        );
    }

    const [warna, warnaGelap, ikon] = meta;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap',
                className,
            )}
            style={{
                color: warna,
                borderColor: `color-mix(in oklab, ${warna} 45%, transparent)`,
                // Ujung gelapnya jadi latar tipis — dua warna di STATUS_META
                // memang sepasang ramp, bukan warna cadangan.
                backgroundColor: `color-mix(in oklab, ${warnaGelap} 12%, transparent)`,
            }}
        >
            <Ikon nama={ikon} className="size-3.5" />
            {label}
        </span>
    );
}
