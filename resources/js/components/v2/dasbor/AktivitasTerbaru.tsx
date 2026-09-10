import { Link, usePage } from '@inertiajs/react';

import { Avatar } from '@/components/v2/Avatar';
import { DataTable, Paginasi, type Kolom, type Paginator } from '@/components/v2/DataTable';
import {
    PenyaringDokumen, saringDepartemen, saringJenis, saringStatus,
} from '@/components/v2/PenyaringDokumen';
import { StatusBadge } from '@/components/v2/StatusBadge';
import {
    Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import { Progress } from '@/components/ui-maia/progress';
import type { Departemen, PageProps } from '@/types';
import type { AktivitasBaris } from '@/types/dasbor';

/**
 * "Aktivitas Terbaru" gaya Recent Projects (F6 DASBOR-V4) — HANYA PJO/MD/Admin.
 *
 * Baris PENUH LEBAR, satu-satunya kartu selebar layar di dashboard — bentuk
 * referensinya memang begitu. Dirakit dari komponen yang SUDAH ada
 * (`PenyaringDokumen`, `DataTable`, `Avatar`, `StatusBadge`), nol tabel baru.
 *
 * `menungguDiMeja` TIDAK diperbesar untuk ini (§P1 rencana) — prop ini
 * `aktivitasTabel`, berpaginasi TERPISAH (`pageName: 'aktivitas'`) supaya
 * tak bentrok dengan `?bulan=` kalender ketersediaan di halaman yang sama.
 */
export function AktivitasTerbaru({
    tabel,
    filters,
    departemen,
    statusOpsi,
    jenisList,
}: {
    tabel: Paginator<AktivitasBaris>;
    filters: Record<string, string | undefined>;
    departemen: Departemen[];
    statusOpsi: string[];
    jenisList: string[];
}) {
    const { statusLabels } = usePage<PageProps>().props;

    const kolom: Kolom<AktivitasBaris>[] = [
        {
            judul: 'Dokumen',
            render: (b) => (
                <Link href={b.tautan} className="hover:underline">
                    <div className="font-medium">{b.judul}</div>
                    <div className="text-muted-foreground text-xs">{b.nomor}</div>
                </Link>
            ),
        },
        {
            judul: 'Pembuat',
            render: (b) => (
                <div className="flex items-center gap-2">
                    <Avatar nama={b.pembuat.nama} foto={b.pembuat.foto} className="size-7" />
                    <div className="min-w-0">
                        <div className="truncate text-sm">{b.pembuat.nama}</div>
                        <div className="text-muted-foreground truncate text-xs">{b.pembuat.jabatan}</div>
                    </div>
                </div>
            ),
        },
        {
            judul: 'Dibuat',
            render: (b) => <span className="text-muted-foreground text-sm">{b.dibuat ?? '—'}</span>,
        },
        {
            judul: 'Diperbarui',
            render: (b) => <span className="text-muted-foreground text-sm">{b.diperbarui ?? '—'}</span>,
        },
        {
            judul: 'Status',
            render: (b) => <StatusBadge status={b.status} />,
        },
        {
            judul: 'Kemajuan',
            kelas: 'w-40',
            render: (b) => (
                <div className="flex items-center gap-2">
                    <Progress value={b.persen} className="h-1.5" />
                    <span className="text-muted-foreground w-9 shrink-0 text-right text-xs tabular-nums">
                        {b.persen}%
                    </span>
                </div>
            ),
        },
    ];

    return (
        <Card className="@container/card">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Aktivitas Terbaru</CardTitle>
                <CardDescription>Dokumen yang belakangan bergerak, lintas tujuh departemen</CardDescription>
                <div className="mt-3">
                    <PenyaringDokumen
                        url={route('dashboard')}
                        filters={filters}
                        contoh="PPA-ADRO-SOP"
                        pilihan={[
                            saringJenis(jenisList),
                            ...(departemen.length > 0 ? [saringDepartemen(departemen)] : []),
                            saringStatus(statusOpsi, statusLabels),
                        ]}
                    />
                </div>
            </CardHeader>
            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={tabel.data}
                    kunci={(b) => b.id}
                    kosong="Belum ada aktivitas dokumen."
                />
            </CardContent>
            <CardFooter className="border-t pt-6">
                <Paginasi paginator={tabel} />
            </CardFooter>
        </Card>
    );
}
