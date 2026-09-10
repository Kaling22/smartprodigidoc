import { CheckmarkBadge01Icon, FileEditIcon, InboxIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { DataTable, Paginasi, type Kolom, type Paginator } from '@/components/v2/DataTable';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisTinjau } from '@/types/tinjau';

/**
 * "Persetujuan Saya" — antrean PJO, kembaran V2 `pages/Approvals/Index.tsx`.
 *
 * Siapa yang melihat baris apa dijawab controller (`approver_id`, atau
 * `document.view_all` untuk Admin) — nol penyaringan di klien (pakem P5).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — paragraf pengantar pindah ke prop `sub` layout.
 *  · §3.7 — satu kartu: tabel di `CardContent` tanpa talang, paginasi di
 *    `CardFooter` bertepi atas (arketipe `V2/Documents/Revisions.tsx`).
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function ApprovalsIndex() {
    const { documents } = usePage<PageProps & { documents: Paginator<BarisTinjau> }>().props;

    const kolom: Kolom<BarisTinjau>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Pembuat', render: (d) => <span className="text-sm">{d.pembuat ?? '—'}</span> },
        { judul: 'Peninjau', render: (d) => <span className="text-sm">{d.peninjau ?? '—'}</span> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => (
                <div className="flex items-center justify-end gap-2">
                    <Button asChild variant="outline" size="icon" title="Lihat PDF">
                        <a href={route('documents.pdf', d.id)} target="_blank" rel="noopener">
                            <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                        </a>
                    </Button>
                    <Button asChild size="sm">
                        <Link href={route('approvals.show', d.id)}>
                            <HugeiconsIcon
                                icon={CheckmarkBadge01Icon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            Tinjau &amp; Setujui
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Persetujuan Saya"
            sub="Dokumen yang lolos tinjauan dan menunggu persetujuan Anda."
        >
            <Card>
                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={documents.data}
                        kunci={(d) => d.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon
                                            icon={InboxIcon}
                                            strokeWidth={1.5}
                                            className="size-6"
                                        />
                                    </EmptyMedia>
                                    <EmptyTitle>
                                        Tidak ada dokumen yang menunggu persetujuan.
                                    </EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={documents} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}
