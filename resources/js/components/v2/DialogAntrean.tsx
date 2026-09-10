import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui-maia/dialog';
import { Item, ItemContent, ItemMedia, ItemTitle } from '@/components/ui-maia/item';
import { Ikon } from '@/components/v2/Ikon';
import type { PageProps } from '@/types';

/**
 * Modal "tugas menunggu" — kembaran maia dari `components/DialogAntrean.tsx`.
 *
 * Perilakunya tak bergeser: muncul SEKALI tepat sesudah login (penandanya flash
 * `antrean_awal`), isinya `AntreanTugas` — sumber yang SAMA dengan lencana
 * sidebar, jadi angka di modal dan di menu mustahil berbeda. Antrean kosong tak
 * pernah sampai ke sini (server mengirim `null`): modal yang tak membawa kabar
 * hanya melatih orang menutupnya tanpa membaca.
 *
 * Bedanya baris tugas kini komponen `Item` resmi alih-alih `<Link>` rakitan —
 * ia sudah membawa jarak, `ItemMedia`, dan keadaan hover-nya sendiri.
 */
export function DialogAntrean() {
    const { antreanAwal } = usePage<PageProps>().props;
    const [buka, setBuka] = useState(true);

    if (!antreanAwal || antreanAwal.length === 0) {
        return null;
    }

    return (
        <Dialog open={buka} onOpenChange={setBuka}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Ikon nama="bi-inbox" className="size-4" />
                        Ada {antreanAwal.length} hal yang menunggu Anda
                    </DialogTitle>
                </DialogHeader>

                <div className="flex flex-col gap-1">
                    {antreanAwal.map((tugas) => (
                        <Item key={tugas.url} asChild variant="outline" size="sm">
                            <Link href={tugas.url} onClick={() => setBuka(false)}>
                                <ItemMedia>
                                    <Ikon nama={tugas.icon} className="size-4" />
                                </ItemMedia>
                                <ItemContent>
                                    <ItemTitle>{tugas.label}</ItemTitle>
                                </ItemContent>
                                <Badge variant="destructive">{tugas.jumlah}</Badge>
                            </Link>
                        </Item>
                    ))}
                </div>

                <DialogFooter>
                    <Button variant="outline" size="sm" onClick={() => setBuka(false)}>
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
