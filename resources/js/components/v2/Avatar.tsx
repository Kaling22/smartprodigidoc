import { UserIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

import { Avatar as AvatarDasar, AvatarFallback, AvatarImage } from '@/components/ui-maia/avatar';
import { cn } from '@/lib/utils';

/**
 * Avatar pengguna — kembaran maia dari `components/Avatar.tsx`.
 *
 * Sama perilakunya: foto dan ikon cadangan menempati kotak yang SAMA, supaya
 * tinggi topbar dan baris tabel tak berubah antar pengguna. Bedanya bentuk
 * bawaan `rounded-full` mengikuti bahasa pil maia, dan glif cadangannya
 * hugeicons.
 */
export function Avatar({
    nama,
    foto,
    className,
}: {
    nama: string;
    foto?: string | null;
    className?: string;
}) {
    return (
        <AvatarDasar className={cn('size-9 rounded-full', className)}>
            {foto ? <AvatarImage src={foto} alt={nama} /> : null}
            <AvatarFallback className="rounded-full">
                <HugeiconsIcon icon={UserIcon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                <span className="sr-only">{nama}</span>
            </AvatarFallback>
        </AvatarDasar>
    );
}
