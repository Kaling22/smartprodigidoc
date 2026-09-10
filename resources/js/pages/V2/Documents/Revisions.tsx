import { ArrowTurnBackwardIcon, CheckmarkCircle01Icon, FileEditIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisRevisi, DocumentsRevisionsProps } from '@/types/dokumen';

/**
 * "Dokumen Revisi" — kembaran V2 `pages/Documents/Revisions.tsx`.
 *
 * Hanya PEMBUAT yang melihat dokumennya di sini. Peninjau memantau lewat Tinjau
 * Dokumen → Status Revisi (v3.1 §0/§4.2), bukan menu ini.
 *
 * Bentuknya arketipe daftar V2 (`V2/Documents/Index.tsx`): satu kartu, tabel di
 * `CardContent` tanpa talang, paginasi di `CardFooter`. Tanpa `CardHeader` —
 * daftar ini memang tak punya penyaring.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.5 — keadaan kosong `text-emerald-600 dark:text-emerald-400` →
 *    `text-chart-5`, hijau palet `.ui-v2`. Nol warna baru.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan sendiri.
 */
export default function DocumentsRevisions() {
    const { documents } = usePage<PageProps & DocumentsRevisionsProps>().props;

    const kolom: Kolom<BarisRevisi>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        {
            judul: 'Status',
            render: (d) =>
                d.draft_revisi ? (
                    <Badge variant="secondary">
                        Revisi Diajukan (Edisi {d.edisi} Rev {d.no_revisi})
                    </Badge>
                ) : (
                    <Badge variant="destructive">{d.status_label}</Badge>
                ),
        },
        {
            judul: 'Feedback Peninjau',
            kelas: 'min-w-56',
            render: (d) => (
                <div className="text-sm">
                    {/* Alasan Ajukan Revisi / penolakan approver disimpan sebagai
                        rangkuman review — ikut ditampilkan supaya pembuat tahu APA
                        yang harus diperbaiki sebelum membuka form (§4). */}
                    {d.rangkuman ? (
                        <p className="text-destructive font-semibold whitespace-pre-line">
                            {potong(d.rangkuman, 220)}
                        </p>
                    ) : null}
                    {d.anotasi.map((a, i) => (
                        <p key={i} className="text-destructive">
                            · <span className="text-muted-foreground">[{a.bagian}]</span>{' '}
                            {potong(a.komentar, 70)}
                        </p>
                    ))}
                    {!d.rangkuman && d.anotasi.length === 0 ? (
                        <span className="text-muted-foreground">
                            Lihat komentar approver / peninjau di form revisi.
                        </span>
                    ) : null}
                    {d.anotasi_sisa > 0 ? (
                        <p className="text-muted-foreground">+{d.anotasi_sisa} lainnya…</p>
                    ) : null}
                </div>
            ),
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
                    <Button asChild variant="secondary" size="sm">
                        <Link href={route('documents.edit', d.id)}>
                            <HugeiconsIcon icon={ArrowTurnBackwardIcon} strokeWidth={1.5} className="size-4" />
                            Revisi
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Dokumen Revisi"
            sub={`Dokumen yang perlu Anda revisi, alasannya tertulis pada tiap baris — ${documents.total} dokumen.`}
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
                                            icon={CheckmarkCircle01Icon}
                                            strokeWidth={1.5}
                                            className="text-chart-5 size-6"
                                        />
                                    </EmptyMedia>
                                    <EmptyTitle>Tidak ada dokumen yang perlu direvisi</EmptyTitle>
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

/** Padanan `Str::limit()` — pemenggalan tampilan, bukan aturan bisnis. */
function potong(teks: string, batas: number): string {
    return teks.length > batas ? `${teks.slice(0, batas)}…` : teks;
}
