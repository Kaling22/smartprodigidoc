import {
    FileEditIcon, InboxIcon, InformationCircleIcon, SpellCheckIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
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
 * "Tinjau Penulisan" — antrean Management Development, kembaran V2
 * `pages/Review/Md.tsx`.
 *
 * Tahap KEDUA: MD memeriksa SISTEMATIKA PENULISAN sesudah SH/DH memeriksa
 * SUBSTANSI. Batas peran itu ditegaskan DI LAYAR, bukan hanya di kode — tanpa
 * itu peninjau mudah melebar ke ranah SH/DH dan alurnya jadi berulang.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.2/§3.5 — pita `bg-accent … rounded-lg` jadi komponen `alert` resmi.
 *    Nol warna baru, nol radius diketik ulang.
 *  · §3.6 — paragraf pengantar pindah ke prop `sub` layout. Ia TIDAK dilebur
 *    dengan Alert di bawahnya: yang satu menjawab "antrean apa ini", yang lain
 *    "apa yang boleh Anda periksa".
 *  · §3.7 — satu kartu: tabel di `CardContent` tanpa talang, paginasi di
 *    `CardFooter` bertepi atas (arketipe `V2/Documents/Revisions.tsx`).
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function ReviewMd() {
    const { documents } = usePage<PageProps & { documents: Paginator<BarisTinjau> }>().props;

    const kolom: Kolom<BarisTinjau>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Pembuat', render: (d) => <span className="text-sm">{d.pembuat ?? '—'}</span> },
        {
            judul: 'Peninjau Substansi',
            render: (d) => <span className="text-sm">{d.peninjau ?? '—'}</span>,
        },
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
                        <Link href={route('review.md.show', d.id)}>
                            <HugeiconsIcon icon={SpellCheckIcon} strokeWidth={1.5} className="size-4" />
                            Tinjau
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Tinjau Penulisan"
            sub={
                <>
                    Dokumen yang sudah lolos peninjauan substansi (SH/DH) dan menunggu pemeriksaan{' '}
                    <strong>cara penulisan</strong> sebelum diteruskan ke PJO.
                </>
            }
        >
            <Alert>
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                <AlertDescription>
                    Yang diperiksa di tahap ini:{' '}
                    <strong>typo, salah tulis, kalimat tak sesuai</strong>, dan teks yang jelas bukan
                    pada tempatnya. Kebenaran isi — konteks, definisi, kelengkapan langkah — sudah
                    menjadi bagian peninjau sebelumnya.
                </AlertDescription>
            </Alert>

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
                                        Tidak ada dokumen yang menunggu peninjauan penulisan.
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
