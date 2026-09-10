import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { DropdownMenuItem } from '@/components/ui-maia/dropdown-menu';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { Ikon } from '@/components/v2/Ikon';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import {
    PenyaringDokumen, saringDepartemen, saringJenis, saringStatus,
} from '@/components/v2/PenyaringDokumen';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisIndex, DocumentsIndexProps } from '@/types/dokumen';

/**
 * "Dokumen Saya" / "Status Dokumen" — ARKETIPE seluruh daftar V2.
 *
 * Bentuk halaman ini menentukan 20+ daftar lain (`Users/*`, `Log/*`, `Audit`,
 * `Review/*`, `Approvals`, `Informasi/Index`, `Nonaktif`, `JobExecutions`,
 * `Akses`, dan lima daftar dokumen lainnya), jadi tiga keputusan di bawah
 * dibuat sekali di sini:
 *
 *  1. **SATU kartu, bukan dua.** Penyaring masuk ke `CardHeader` tabelnya.
 *     Versi nova menumpuk kartu penyaring di atas kartu tabel — dua tepi, dua
 *     bayangan, dan satu jarak kosong di antaranya, untuk satu daftar.
 *  2. **Keadaan kosong pakai komponen `empty` resmi**, bukan rakitan sendiri —
 *     dan ia memuat jalan keluarnya (tombol Dokumen Baru), bukan cuma
 *     memberitahu bahwa daftarnya kosong.
 *  3. **Paginasi di `CardFooter`**, membaca `links`/`from`/`to`/`total` Laravel
 *     apa adanya.
 *
 * Judulnya berganti menurut `showAllStatuses`: bagi GL daftar ini adalah
 * seluruh dokumen BUATANNYA (segala status), bagi peran lain hanya yang sedang
 * berproses — Berlaku punya menunya sendiri. Keputusan itu dibuat controller;
 * halaman ini cuma membacanya.
 *
 * Urutan kolom Status → Aksi tetap konsisten (CLAUDE.md §13).
 */
export default function DocumentsIndex() {
    const {
        documents, filters, types, departments, showAllStatuses, statusOpsi, jenisBaru, prefix,
        statusLabels,
    } = usePage<PageProps & DocumentsIndexProps>().props;

    const kolom: Kolom<BarisIndex>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        {
            judul: 'Judul',
            render: (d) => (
                <div className="flex flex-wrap items-center gap-1 font-medium">
                    {d.judul}
                    {/* Lencana masukan sejawat. Hanya ditawarkan kepada yang
                        pasti lolos penjaga MasukanSejawatController::show —
                        controller yang memutuskannya, lihat props
                        `masukan_sejawat`. */}
                    {d.masukan_sejawat ? (
                        <Link href={route('masukan-sejawat.show', d.id)} title="Masukan dari rekan sedepartemen">
                            <Badge variant="secondary">
                                <Ikon nama="bi-chat-left-text" className="size-3" />
                                {d.masukan_sejawat}
                            </Badge>
                        </Link>
                    ) : null}
                </div>
            ),
        },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Status', render: (d) => <StatusBadge status={d.status} /> },
        { judul: 'Pembuat', render: (d) => <span className="text-muted-foreground text-sm">{d.pembuat ?? '—'}</span> },
        { judul: 'Aksi', kelas: 'w-px text-right whitespace-nowrap', render: (d) => <AksiBaris doc={d} /> },
    ];

    return (
        <AppLayout
            judul={showAllStatuses ? 'Dokumen Saya' : 'Status Dokumen'}
            sub={
                showAllStatuses
                    ? `Seluruh dokumen yang Anda buat, segala status — ${documents.total} dokumen.`
                    : `Dokumen yang Anda buat atau di departemen Anda — ${documents.total} dokumen.`
            }
            aksi={
                jenisBaru ? (
                    <Button asChild>
                        <Link href={route('documents.create', { type: jenisBaru })}>
                            <Ikon nama="bi-file-earmark-plus" className="size-4" />
                            Dokumen Baru
                        </Link>
                    </Button>
                ) : null
            }
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('documents.index')}
                        filters={filters}
                        prefix={prefix}
                        pilihan={[
                            ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
                            saringStatus(statusOpsi, statusLabels),
                            saringJenis(types),
                            // Pemilih SUMBER hanya di halaman ini: "Dokumen Berlaku"
                            // dan "Status Dokumen Staff" sengaja mencampur keduanya.
                            {
                                nama: 'sumber',
                                label: 'Sumber',
                                opsi: [['', 'Semua'], ['wizard', 'Dokumen SmartPro'], ['unggahan', 'Dokumen Lama']],
                            },
                        ]}
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
                                        <Ikon nama="bi-inbox" className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada dokumen</EmptyTitle>
                                    <EmptyDescription>
                                        Coba longgarkan penyaring, atau mulai dokumen baru.
                                    </EmptyDescription>
                                </EmptyHeader>
                                {jenisBaru ? (
                                    <EmptyContent>
                                        <Button asChild size="sm">
                                            <Link href={route('documents.create', { type: jenisBaru })}>
                                                <Ikon nama="bi-file-earmark-plus" className="size-4" />
                                                Buat dokumen baru
                                            </Link>
                                        </Button>
                                    </EmptyContent>
                                ) : null}
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

/**
 * Tombol per baris.
 *
 * Draft milik sendiri = satu-satunya baris yang menembus empat aksi (PDF, Edit,
 * Kirim, Hapus). Yang tinggal di luar strip: PDF (dipakai semua peran) dan
 * Kirim (aksi yang dituju GL saat membuka daftar ini). Cabang lain berhenti di
 * tiga tombol, jadi dibiarkan mendatar — menu berisi satu baris hanya menambah
 * satu klik.
 */
function AksiBaris({ doc }: { doc: BarisIndex }) {
    /*
    | Jendela konfirmasi dirender DI LUAR `StripAksi`, dan itu bukan selera:
    | Radix melepas isi menunya begitu item dipilih, sehingga konfirmasi yang
    | dirender di dalamnya ikut lenyap sebelum sempat terlihat. Itemnya cuma
    | menyalakan saklar; jendelanya berdiri sendiri.
    */
    const [hapus, setHapus] = useState(false);

    return (
        <div className="flex items-center justify-end gap-2">
            <Button asChild variant="outline" size="sm">
                <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                    <Ikon nama="bi-file-earmark-text" className="size-4" />
                    PDF
                </a>
            </Button>

            {/* Dokumen LAMA: perbaikan metadata & berkas. Aturannya di
                Document::bisaDisuntingArsipOleh — pengunggah atas dept sendiri,
                atau Admin. */}
            {doc.boleh_edit_arsip ? (
                <Button asChild variant="outline" size="sm">
                    <Link href={route('documents.arsip.edit', doc.id)}>
                        <Ikon nama="bi-pencil" className="size-4" />
                        Edit
                    </Link>
                </Button>
            ) : null}

            {doc.milik && doc.status === 'draft' ? (
                <>
                    <ConfirmDialog
                        judul="Kirim Dokumen?"
                        pesan="Kirim dokumen ini untuk ditinjau? Setelah dikirim tidak bisa diedit (masih bisa Ditarik selama belum ditinjau)."
                        tombolYa="Ya, kirim"
                        onKonfirmasi={() =>
                            router.post(route('documents.submit', doc.id), {}, { preserveScroll: true })
                        }
                        pemicu={
                            <Button size="sm">
                                <Ikon nama="bi-send" className="size-4" />
                                Kirim
                            </Button>
                        }
                    />
                    <StripAksi>
                        <DropdownMenuItem asChild>
                            <Link href={route('documents.edit', doc.id)}>
                                <Ikon nama="bi-pencil" className="size-4" />
                                Edit
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem variant="destructive" onSelect={() => setHapus(true)}>
                            <Ikon nama="bi-trash" className="size-4" />
                            Hapus
                        </DropdownMenuItem>
                    </StripAksi>
                    <ConfirmDialog
                        judul="Hapus Draft?"
                        pesan="Hapus draft ini? Tindakan tidak bisa dibatalkan."
                        tombolYa="Ya, hapus"
                        destruktif
                        buka={hapus}
                        onUbahBuka={setHapus}
                        onKonfirmasi={() =>
                            router.delete(route('documents.destroy', doc.id), { preserveScroll: true })
                        }
                    />
                </>
            ) : doc.milik && doc.status === 'waiting_for_review' ? (
                <>
                    <TombolLihat doc={doc} />
                    <ConfirmDialog
                        judul="Tarik Dokumen?"
                        pesan="Tarik dokumen dari antrian tinjauan? Dokumen kembali ke Draft."
                        tombolYa="Ya, tarik"
                        onKonfirmasi={() =>
                            router.post(route('documents.withdraw', doc.id), {}, { preserveScroll: true })
                        }
                        pemicu={
                            <Button variant="secondary" size="sm">
                                <Ikon nama="bi-arrow-counterclockwise" className="size-4" />
                                Tarik
                            </Button>
                        }
                    />
                </>
            ) : (
                <TombolLihat doc={doc} />
            )}
        </div>
    );
}

function TombolLihat({ doc }: { doc: BarisIndex }) {
    return (
        <Button asChild variant="outline" size="sm">
            <Link href={route('documents.show', doc.id)}>
                <Ikon nama="bi-info-circle" className="size-4" />
                Lihat
            </Link>
        </Button>
    );
}
