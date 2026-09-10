import { FileEditIcon, InboxIcon, Message01Icon, ViewIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import {
    PenyaringDokumen, saringDepartemen, saringJenis, saringStatus,
} from '@/components/v2/PenyaringDokumen';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisStaffStatus, DocumentsStaffStatusProps } from '@/types/dokumen';

/**
 * "Status Dokumen Staff" / "Dokumen Departemen" — kembaran V2
 * `pages/Documents/StaffStatus.tsx`.
 *
 * Read-only tanpa kecuali. Judulnya (dan ejaan "Staff" yang dijaga
 * `LabelJabatanTest`) datang dari props `judul`, bukan dirakit ulang di sini.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring pindah ke `CardHeader` tabelnya, tabel ke `CardContent`
 *    tanpa talang, paginasi ke `CardFooter`: SATU kartu, bukan kartu penyaring
 *    bertumpuk di atas kartu tabel (arketipe `V2/Documents/Index.tsx`).
 *  · §3.6 — paragraf "Read-only …" pindah ke prop `sub` layout; talang &
 *    iramanya dipasang sekali di `layouts/V2/AppLayout`.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 *    Kedua kalimatnya tetap dua, karena keduanya menjawab sebab yang berbeda.
 */
export default function DocumentsStaffStatus() {
    const {
        documents, filters, types, departments, canAll, selectedDept, isGlDept, judul, statusOpsi,
        prefix, statusLabels,
    } = usePage<PageProps & DocumentsStaffStatusProps>().props;

    const kolom: Kolom<BarisStaffStatus>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Status', render: (d) => <StatusBadge status={d.status} /> },
        {
            judul: 'Pembuat',
            render: (d) => <span className="text-muted-foreground text-sm">{d.pembuat ?? '—'}</span>,
        },
        {
            judul: 'Lihat',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => (
                <div className="flex items-center justify-end gap-2">
                    <Button asChild variant="outline" size="icon" title="Lihat PDF">
                        <a href={route('documents.pdf', d.id)} target="_blank" rel="noopener">
                            <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                        </a>
                    </Button>
                    {/* Masukan sejawat (PLAN-AKSES-v8 Fase 2). Syaratnya dijawab
                        model, aturan yang SAMA dengan penjaga controllernya. */}
                    {d.boleh_masukan_sejawat ? (
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('masukan-sejawat.create', d.id)}>
                                <HugeiconsIcon icon={Message01Icon} strokeWidth={1.5} className="size-4" />
                                Beri Masukan
                            </Link>
                        </Button>
                    ) : null}
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('documents.show', d.id)}>
                            <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                            Lihat
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul={judul}
            sub={
                <span className="flex flex-wrap items-center gap-1">
                    <HugeiconsIcon
                        icon={ViewIcon}
                        strokeWidth={1.5}
                        className="size-4"
                        aria-hidden="true"
                    />
                    Read-only — {isGlDept ? 'seluruh dokumen' : 'pantau status dokumen'}{' '}
                    {canAll ? (
                        selectedDept ? (
                            <>
                                di departemen <strong>{selectedDept.code}</strong>
                            </>
                        ) : (
                            'di seluruh departemen (pilih dept di sub-menu)'
                        )
                    ) : (
                        'di departemen Anda'
                    )}
                    .
                </span>
            }
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('documents.staffStatus')}
                        filters={filters}
                        prefix={prefix}
                        // Departemen kini bisa diganti dari SINI juga (rencana
                        // pra-produksi Fase 6 / butir 13). Sub-menu sidebar
                        // dipertahankan sebagai pintasan: keduanya menulis ke query
                        // string yang sama (`?department_id=`), jadi tak ada keadaan
                        // yang bisa berselisih.
                        //
                        // Daftar kosong dari server = penyaringnya tak tampil, jadi
                        // keputusan peran tetap milik server (pola disalin dari
                        // `V2/Documents/Index.tsx`). Karena itu `tersembunyi` tak
                        // perlu lagi menitipkan `department_id`.
                        pilihan={[
                            ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
                            saringJenis(types),
                            saringStatus(statusOpsi, statusLabels),
                        ]}
                        tersembunyi={{}}
                    />
                </CardHeader>
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
                                        {canAll && !selectedDept
                                            ? 'Pilih departemen di sub-menu untuk melihat dokumennya.'
                                            : 'Belum ada dokumen sesuai filter.'}
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
