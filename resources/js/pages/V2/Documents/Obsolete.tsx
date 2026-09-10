import {
    Archive01Icon, ArrowTurnBackwardIcon, ChevronDownIcon, ChevronRightIcon, CircleSlashIcon,
    Delete02Icon, FileEditIcon, InboxIcon, ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { DropdownMenuItem } from '@/components/ui-maia/dropdown-menu';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { DialogMusnahkan } from '@/components/v2/DialogAksi';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { PenyaringDokumen, saringDepartemen } from '@/components/v2/PenyaringDokumen';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisObsolete, DocumentsObsoleteProps, GrupObsolete } from '@/types/dokumen';

/**
 * "Dokumen Tidak Berlaku" — kembaran V2 `pages/Documents/Obsolete.tsx`.
 *
 * SATU baris per DOKUMEN, bukan per versi: tiap revisi Tipe B mewarisi
 * `doc_number` yang sama, jadi dokumen berusia 5 revisi dulu memakan 5 baris.
 * Versi lamanya tetap terjangkau — dibentangkan di bawah barisnya lewat tombol
 * "N versi".
 *
 * Bentuknya arketipe daftar V2 (`V2/Documents/Index.tsx`): satu kartu,
 * penyaring di `CardHeader`, paginasi di `CardFooter`, keterangan halaman di
 * prop `sub` layout (PATOKAN-GAYA-V2 §3.6).
 *
 * Tabel versi yang dibentangkan tetap `<table>` polos, BUKAN `DataTable`: ia
 * hidup DI DALAM `perluas()` milik `DataTable` induknya, jadi memakai
 * `DataTable` kedua berarti pembungkus `overflow-x-auto` dan kepala `sticky`
 * bersarang di dalam pembungkus yang sama — dua gulungan mendatar bertumpuk.
 */
export default function DocumentsObsolete() {
    const { documents, filters, departments, prefix } =
        usePage<PageProps & DocumentsObsoleteProps>().props;

    // Grup mana yang sedang terbentang. Set, bukan satu id: membuka grup kedua
    // tak boleh menutup yang pertama.
    const [terbuka, setTerbuka] = useState<number[]>([]);

    function alih(id: number) {
        setTerbuka((t) => (t.includes(id) ? t.filter((x) => x !== id) : [...t, id]));
    }

    const kolom: Kolom<GrupObsolete>[] = [
        {
            judul: 'No. Dokumen',
            urut: 'nomor',
            render: (d) => (
                <div className="flex flex-wrap items-center gap-1">
                    <NomorDokumen doc={d} dicoret={d.nomor_dilepas} />
                    {/* Nomor DILEPAS (Fase F): barisnya tetap memegang nomor
                        lamanya supaya arsip terbaca, tapi nomor itu sudah kembali
                        ke kolam — dua dokumen boleh sah memegangnya. */}
                    {d.nomor_dilepas ? (
                        <Badge
                            variant="outline"
                            title="Nomor kembali ke kolam dan bisa dipakai dokumen baru"
                        >
                            Nomor dilepas
                        </Badge>
                    ) : null}
                </div>
            ),
        },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Edisi', kelas: 'text-center', render: (d) => d.edisi },
        { judul: 'Revisi', urut: 'revisi', kelas: 'text-center', render: (d) => d.no_revisi },
        { judul: 'Pembuat', render: (d) => <span className="text-sm">{d.pembuat ?? '—'}</span> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => (
                <div className="flex items-center justify-end gap-2">
                    {/* SATU penanda untuk satu hal, dan tetap DI LUAR strip aksi
                        (PLAN C §C3): ini penyingkap baris, bukan aksi atas
                        dokumen. Grup berisi satu versi tak dapat tombol — dokumen
                        yang dimatikan (bukan direvisi) memang selalu tunggal. */}
                    {d.versi.length > 1 ? (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => alih(d.id)}
                            aria-expanded={terbuka.includes(d.id)}
                        >
                            <HugeiconsIcon
                                icon={terbuka.includes(d.id) ? ChevronDownIcon : ChevronRightIcon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            {d.versi.length} versi
                        </Button>
                    ) : null}
                    <TombolPdf doc={d} />
                    <AksiBaris doc={d} induk sendirian={d.versi.length === 1} />
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Dokumen Tidak Berlaku"
            sub={
                <span className="flex items-center gap-1.5">
                    <HugeiconsIcon
                        icon={CircleSlashIcon}
                        strokeWidth={1.5}
                        className="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    Dokumen yang sudah tidak berlaku, beserta versi lama yang digantikan revisi.
                </span>
            }
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('documents.obsolete')}
                        filters={filters}
                        prefix={prefix}
                        pilihan={departments.length > 0 ? [saringDepartemen(departments)] : []}
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
                                        <HugeiconsIcon icon={InboxIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Tidak ada dokumen tidak berlaku</EmptyTitle>
                                    <EmptyDescription>Coba longgarkan penyaring di atas.</EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        }
                        perluas={(d) =>
                            d.versi.length > 1 && terbuka.includes(d.id) ? <TabelVersi grup={d} /> : null
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

function TombolPdf({ doc }: { doc: BarisObsolete }) {
    return (
        <Button asChild variant="outline" size="icon" title="Lihat PDF">
            <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
            </a>
        </Button>
    );
}

/** Seluruh versi grup ini, terurut Edisi menurun lalu revisi menurun. */
function TabelVersi({ grup }: { grup: GrupObsolete }) {
    return (
        <table className="w-full text-sm">
            <thead className="text-muted-foreground">
                <tr>
                    <th className="px-2 py-1 text-center font-normal">Edisi</th>
                    <th className="px-2 py-1 text-center font-normal">Revisi</th>
                    <th className="px-2 py-1 text-left font-normal">Tanggal nonaktif</th>
                    <th className="px-2 py-1 text-left font-normal">Sebab</th>
                    <th className="px-2 py-1 text-right font-normal">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {grup.versi.map((v) => (
                    <tr key={v.id}>
                        <td className="px-2 py-1 text-center">{v.edisi}</td>
                        <td className="px-2 py-1 text-center">{v.no_revisi}</td>
                        <td className="px-2 py-1">{v.dinonaktifkan} WITA</td>
                        <td className="px-2 py-1">
                            <Badge variant={v.nomor_dilepas ? 'outline' : 'secondary'}>
                                {v.nomor_dilepas ? 'Dinonaktifkan' : 'Digantikan revisi'}
                            </Badge>
                        </td>
                        <td className="px-2 py-1">
                            <div className="flex items-center justify-end gap-2">
                                <TombolPdf doc={v} />
                                <Button asChild variant="outline" size="icon" title="Lihat detail">
                                    <Link href={route('documents.show', v.id)}>
                                        <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                                    </Link>
                                </Button>
                                {/* Musnahkan versi lama (PLAN C §C1): tanpa ini,
                                    versi yang digantikan revisi hanya bisa
                                    dimusnahkan lewat CLI — padahal justru itulah
                                    yang paling sering perlu dibersihkan. */}
                                <AksiBaris doc={v} induk={false} sendirian={false} />
                            </div>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

/**
 * Strip aksi untuk baris induk MAUPUN baris versi.
 *
 * Bedanya cuma dua, dan keduanya datang dari pemanggil:
 *  - `induk` — hanya baris induk punya Lihat / Aktifkan / Arsipkan;
 *  - `sendirian` — Rollback ikut ke baris induk HANYA bila grupnya berisi satu
 *    versi. Pada grup bertingkat ia sengaja tinggal di baris versi: memilih
 *    versi mana yang dituju harus eksplisit.
 *
 * Jendela dirender DI LUAR menu (Radix melepas isi menunya begitu item dipilih).
 */
function AksiBaris({
    doc,
    induk,
    sendirian,
}: {
    doc: BarisObsolete;
    induk: boolean;
    sendirian: boolean;
}) {
    const [jendela, setJendela] = useState<'musnahkan' | 'aktifkan' | 'arsipkan' | 'rollback' | null>(null);
    const bolehRollback = doc.boleh_rollback && (!induk || sendirian);

    return (
        <>
            <StripAksi>
                {induk ? (
                    <DropdownMenuItem asChild>
                        <Link href={route('documents.show', doc.id)}>
                            <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                            Lihat
                        </Link>
                    </DropdownMenuItem>
                ) : null}

                {bolehRollback ? (
                    <DropdownMenuItem onSelect={() => setJendela('rollback')}>
                        <HugeiconsIcon icon={ArrowTurnBackwardIcon} strokeWidth={1.5} className="size-4" />
                        Rollback
                    </DropdownMenuItem>
                ) : null}

                {induk && doc.boleh_aktifkan ? (
                    <DropdownMenuItem onSelect={() => setJendela('aktifkan')}>
                        <HugeiconsIcon icon={ArrowTurnBackwardIcon} strokeWidth={1.5} className="size-4" />
                        Aktifkan
                    </DropdownMenuItem>
                ) : null}

                {induk && doc.boleh_arsipkan ? (
                    <DropdownMenuItem onSelect={() => setJendela('arsipkan')}>
                        <HugeiconsIcon icon={Archive01Icon} strokeWidth={1.5} className="size-4" />
                        Arsipkan
                    </DropdownMenuItem>
                ) : null}

                {doc.boleh_musnahkan ? (
                    <DropdownMenuItem variant="destructive" onSelect={() => setJendela('musnahkan')}>
                        <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                        Musnahkan
                    </DropdownMenuItem>
                ) : null}
            </StripAksi>

            {/* Tiga syaratnya sama persis dengan penjaga di
                DocumentRevisionController::rollback(); server memeriksanya ulang. */}
            {bolehRollback ? (
                <ConfirmDialog
                    judul="Kembalikan ke Versi Ini?"
                    pesan={`Isi Edisi ${doc.edisi} Revisi ${doc.no_revisi} akan disalin menjadi draft revisi baru. Dokumen yang berlaku sekarang tetap berlaku sampai revisi ini disahkan.`}
                    tombolYa="Ya, buat draft revisi"
                    buka={jendela === 'rollback'}
                    onUbahBuka={(b) => setJendela(b ? 'rollback' : null)}
                    onKonfirmasi={() =>
                        router.post(route('documents.rollback', doc.id), {}, { preserveScroll: true })
                    }
                />
            ) : null}

            {/* Nomor yang bentrok SELALU diganti saat diaktifkan lagi — server
                memeriksanya ulang, jadi `nomor_baru` di sini cuma menerangkan
                lebih dulu apa yang akan terjadi. */}
            {induk && doc.boleh_aktifkan ? (
                <ConfirmDialog
                    judul={doc.nomor_bentrok ? 'Nomor Bentrok' : 'Aktifkan Kembali?'}
                    pesan={
                        doc.nomor_bentrok
                            ? doc.nomor_dilepas
                                ? `Nomor ${doc.nomor_final} sudah dilepas saat dokumen ini dinonaktifkan. Aktifkan ${doc.judul} dengan NOMOR BARU (dibuat otomatis)?`
                                : `Nomor ${doc.nomor_final} masih dipakai dokumen yang Berlaku. Aktifkan ${doc.judul} dengan NOMOR BARU (dibuat otomatis)?`
                            : `Aktifkan kembali ${doc.nomor}? Dokumen akan kembali berstatus Berlaku.`
                    }
                    tombolYa={doc.nomor_bentrok ? 'Ya, beri nomor baru' : 'Ya, aktifkan'}
                    buka={jendela === 'aktifkan'}
                    onUbahBuka={(b) => setJendela(b ? 'aktifkan' : null)}
                    onKonfirmasi={() =>
                        router.post(
                            route('documents.restoreObsolete', doc.id),
                            doc.nomor_bentrok ? { nomor_baru: 1 } : {},
                            { preserveScroll: true },
                        )
                    }
                />
            ) : null}

            {/* Aksinya SOFT-DELETE — barisnya tetap ada dan nomornya tetap
                terkunci. Yang benar-benar memusnahkan adalah Musnahkan di
                sebelahnya (Admin saja). */}
            {induk && doc.boleh_arsipkan ? (
                <ConfirmDialog
                    judul="Arsipkan Dokumen?"
                    pesan={`Arsipkan ${doc.nomor}? Dokumen disingkirkan dari daftar, tetapi isi & nomornya tetap tersimpan dan bisa dipulihkan lewat basis data.`}
                    tombolYa="Ya, arsipkan"
                    buka={jendela === 'arsipkan'}
                    onUbahBuka={(b) => setJendela(b ? 'arsipkan' : null)}
                    onKonfirmasi={() =>
                        router.delete(route('documents.destroy', doc.id), { preserveScroll: true })
                    }
                />
            ) : null}

            {doc.boleh_musnahkan ? (
                <DialogMusnahkan
                    doc={doc}
                    buka={jendela === 'musnahkan'}
                    onUbahBuka={(b) => setJendela(b ? 'musnahkan' : null)}
                />
            ) : null}
        </>
    );
}
