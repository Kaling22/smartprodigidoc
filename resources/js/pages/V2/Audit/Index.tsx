import { ClipboardListIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { PenyaringDokumen } from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { AuditIndexProps, BarisAudit } from '@/types/log';

/**
 * "Audit Log" — kembaran V2 `pages/Audit/Index.tsx`.
 *
 * Menu tersendiri untuk penelusuran formal (PRD v3.1 §9/§10); rutenya dipagari
 * `can:audit.view` dan pagar itu TIDAK boleh dilepas dengan alasan menunya
 * sudah disembunyikan (pakem P4).
 *
 * Penyaringnya tetap query string yang dikerjakan SERVER: daftarnya berhalaman
 * 30 baris, dan menyaring di klien hanya menyaring halaman yang sedang tampil.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring yang di V1 berdiri LEPAS di atas kartu tabel masuk ke
 *    `CardHeader className="border-b"` kartu itu, tabel ke
 *    `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"`
 *    — SATU kartu (arketipe `V2/Documents/Index.tsx`). `CardFooter pt-0` V1
 *    ikut lepas: angkanya milik kit.
 *  · §3.6 — kalimat pengantar pindah ke prop `sub` layout.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan string telanjang.
 */
export default function AuditIndex() {
    const { logs, filters } = usePage<PageProps & AuditIndexProps>().props;

    const kolom: Kolom<BarisAudit>[] = [
        {
            judul: 'Waktu (WITA)',
            kelas: 'whitespace-nowrap',
            render: (l) => <span className="text-sm">{l.waktu}</span>,
        },
        {
            judul: 'User',
            render: (l) => (
                <span className="text-sm">
                    {l.oleh} <span className="text-muted-foreground">{l.nrp ?? ''}</span>
                </span>
            ),
        },
        {
            judul: 'Aksi',
            render: (l) => (
                <Badge variant="secondary" className="font-mono">
                    {l.aksi}
                </Badge>
            ),
        },
        {
            judul: 'Dokumen',
            render: (l) =>
                l.dokumen ? (
                    <Link href={route('documents.show', l.dokumen.id)} className="font-mono text-sm hover:underline">
                        {l.dokumen.nomor}
                    </Link>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
        {
            judul: 'IP',
            render: (l) => <span className="text-muted-foreground text-sm">{l.ip ?? '—'}</span>,
        },
    ];

    return (
        <AppLayout
            judul="Audit Log"
            sub="Jejak seluruh aksi penting (termasuk aksi admin) — waktu WITA."
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('audit.index')}
                        filters={filters}
                        labelCari="Cari user (nama / NRP)"
                        placeholderCari="mis. Budi atau GL-0001..."
                        pilihan={[
                            {
                                nama: 'action',
                                label: 'Aksi',
                                tipe: 'teks',
                                placeholder: 'mis. approve, submit',
                            },
                        ]}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={logs.data}
                        kunci={(l) => l.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon icon={ClipboardListIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada catatan audit</EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>

                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={logs} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}
