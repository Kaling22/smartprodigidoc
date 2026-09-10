import {
    CancelCircleIcon, CircleSlashIcon, Delete02Icon, FileEditIcon, FolderOpenIcon, Message01Icon,
    PencilIcon, RefreshCwIcon, Xls01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger,
} from '@/components/ui-maia/dropdown-menu';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import {
    DialogMasukan, DialogMusnahkan, DialogNonaktif, DialogRevisi,
} from '@/components/v2/DialogAksi';
import { Ikon } from '@/components/v2/Ikon';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { PenyaringDokumen, saringDepartemen, saringJenis } from '@/components/v2/PenyaringDokumen';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisBerlaku, DocumentsPublishedProps, JadwalPembuat } from '@/types/dokumen';

/**
 * "Dokumen Berlaku" — kembaran V2 `pages/Documents/Published.tsx`.
 *
 * Tiga status mungkin muncul di daftar ini sejak Fase F (Berlaku / Sedang
 * Direvisi / Menunggu Nonaktif), jadi rupanya diambil dari SATU sumber yang
 * sama dengan seluruh aplikasi (`StatusBadge` ← props `statusMeta`) alih-alih
 * if-else di sini.
 *
 * Bentuknya arketipe daftar V2 (`V2/Documents/Index.tsx`): SATU kartu dengan
 * penyaring di `CardHeader`, tabel di `CardContent` tanpa talang, paginasi di
 * `CardFooter`. Keterangan halaman pindah ke prop `sub` layout — talang & irama
 * dipasang sekali di `layouts/V2/AppLayout` (PATOKAN-GAYA-V2 §3.6).
 *
 * Keadaan kosong memakai komponen `empty` resmi, bukan rakitan sendiri.
 */
export default function DocumentsPublished() {
    const { documents, filters, departments, jenis, rupa, prefix, ketersediaanPembuat, auth } =
        usePage<PageProps & DocumentsPublishedProps>().props;

    /*
    | Izin tombol Export SAMA PERSIS dengan penjaga rutenya
    | (`document.publish` = PJO + Admin lintas 7 dept; `document.review` = SH/DH
    | dan `document.create` = GL, dept sendiri), supaya tak ada tombol yang
    | tampil lalu berakhir 403. Alasan pemilihan izinnya di routes/web.php.
    */
    const bolehExport =
        auth.can['document.publish'] || auth.can['document.review'] || auth.can['document.create'];

    const kolom: Kolom<BarisBerlaku>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        {
            judul: 'Judul',
            render: (d) => (
                <div className="flex flex-wrap items-center gap-1 font-medium">
                    {d.judul}
                    {/* Lencana masukan lapangan: yang BELUM ditindak. Semua kecuali
                        Non-Staff — SH/DH & PJO tak lagi menindak, tapi tetap perlu
                        tahu (Fase C). Controller yang memutuskannya. */}
                    {d.masukan_count ? (
                        <Link href={route('documents.show', d.id)}>
                            <Badge variant="destructive">
                                <HugeiconsIcon icon={Message01Icon} strokeWidth={2} className="size-3" />
                                {d.masukan_count} masukan
                            </Badge>
                        </Link>
                    ) : null}
                </div>
            ),
        },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'No. Revisi', urut: 'revisi', kelas: 'text-center', render: (d) => d.no_revisi },
        { judul: 'Status', render: (d) => <StatusBadge status={d.status} /> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => (
                <AksiBaris
                    doc={d}
                    jadwal={d.pembuat_id ? (ketersediaanPembuat[d.pembuat_id] ?? null) : null}
                />
            ),
        },
    ];

    return (
        <AppLayout
            judul="Dokumen Berlaku"
            sub={
                <>
                    Dokumen aktif {departments.length > 0 ? 'di 7 departemen' : 'di departemen Anda'} —{' '}
                    {documents.total} dokumen.{' '}
                    {/* Satu-satunya keterangan yang menunjuk ke tempat yang TIDAK
                        terlihat dari halaman ini (PLAN C §C5). */}
                    {!auth.can['document.request_revision'] && !auth.can['beri-masukan'] ? (
                        <>
                            Masukan lapangan &amp; alasan revisi terkumpul di menu{' '}
                            <strong>Log Dokumen</strong>.
                        </>
                    ) : null}
                </>
            }
            aksi={bolehExport ? <MenuExport jenis={jenis} rupa={rupa} /> : null}
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('documents.published')}
                        filters={filters}
                        prefix={prefix}
                        pilihan={[
                            saringJenis(jenis),
                            ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
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
                                        <HugeiconsIcon icon={FolderOpenIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada dokumen Berlaku</EmptyTitle>
                                    <EmptyDescription>Coba longgarkan penyaring di atas.</EmptyDescription>
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

/** Daftar induk dokumen → Excel, satu berkas per jenis. */
function MenuExport({
    jenis,
    rupa,
}: {
    jenis: string[];
    rupa: DocumentsPublishedProps['rupa'];
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                    <HugeiconsIcon icon={Xls01Icon} strokeWidth={1.5} className="size-4" />
                    Export Excel
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuLabel>Daftar Induk Dokumen</DropdownMenuLabel>
                {jenis.map((j) => (
                    <DropdownMenuItem key={j} asChild>
                        {/* Unduhan berkas — tautan biasa, bukan kunjungan Inertia.
                            Ikonnya berkunci server (`rupa`), jadi lewat `Ikon`. */}
                        <a href={route('documents.export', j)}>
                            <Ikon nama={rupa[j]?.[0]} className="size-4" />
                            {j}
                        </a>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

/**
 * Tombol per baris.
 *
 * PDF tetap tombol berdiri sendiri (PLAN C §C3): aksi yang paling sering
 * dipakai, menguburnya berbiaya satu klik di setiap baris. Sisanya masuk strip.
 *
 * Semua jendela dirender DI LUAR `StripAksi` — Radix melepas isi menunya begitu
 * item dipilih, jadi jendela yang lahir di dalamnya ikut lenyap sebelum sempat
 * terlihat. Itemnya cuma memilih jendela mana yang menyala.
 */
type Jendela = 'masukan' | 'revisi' | 'nonaktif' | 'musnahkan' | 'batal' | null;

function AksiBaris({ doc, jadwal }: { doc: BarisBerlaku; jadwal: JadwalPembuat | null }) {
    const [jendela, setJendela] = useState<Jendela>(null);

    return (
        <div className="flex items-center justify-end gap-2">
            <Button asChild variant="outline" size="sm">
                <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                    <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                    PDF
                </a>
            </Button>

            <StripAksi>
                {/* Dokumen LAMA: perbaikan metadata & berkas. Admin melihatnya DI
                    SINI, bukan di "Dokumen Saya" — dokumen Berlaku memang tak
                    masuk daftar itu baginya. */}
                {doc.boleh_edit_arsip ? (
                    <DropdownMenuItem asChild>
                        <Link href={route('documents.arsip.edit', doc.id)}>
                            <HugeiconsIcon icon={PencilIcon} strokeWidth={1.5} className="size-4" />
                            Edit
                        </Link>
                    </DropdownMenuItem>
                ) : null}

                {/* Non-Staff: kanal masukan (§3). Tak menyentuh isi dokumen. */}
                {doc.boleh_beri_masukan ? (
                    <DropdownMenuItem onSelect={() => setJendela('masukan')}>
                        <HugeiconsIcon icon={Message01Icon} strokeWidth={1.5} className="size-4" />
                        Beri Masukan
                    </DropdownMenuItem>
                ) : null}

                {doc.boleh_revisi ? (
                    <DropdownMenuItem onSelect={() => setJendela('revisi')}>
                        <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" />
                        Revisi
                    </DropdownMenuItem>
                ) : null}
                {/* Fase F: mematikan dokumen bukan lagi sekali klik. Jendelanya
                    meminta alasan; pengajuannya lalu melewati SH/DH, MD, dan PJO. */}
                {doc.boleh_revisi ? (
                    <DropdownMenuItem onSelect={() => setJendela('nonaktif')}>
                        <HugeiconsIcon icon={CircleSlashIcon} strokeWidth={1.5} className="size-4" />
                        Ajukan Nonaktif
                    </DropdownMenuItem>
                ) : null}

                {doc.boleh_batal_revisi ? (
                    <DropdownMenuItem variant="destructive" onSelect={() => setJendela('batal')}>
                        <HugeiconsIcon icon={CancelCircleIcon} strokeWidth={1.5} className="size-4" />
                        Batalkan Revisi
                    </DropdownMenuItem>
                ) : null}

                {doc.boleh_musnahkan ? (
                    <DropdownMenuItem variant="destructive" onSelect={() => setJendela('musnahkan')}>
                        <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                        Musnahkan
                    </DropdownMenuItem>
                ) : null}
            </StripAksi>

            {doc.boleh_beri_masukan ? (
                <DialogMasukan
                    doc={doc}
                    masukanSaya={doc.masukan}
                    buka={jendela === 'masukan'}
                    onUbahBuka={(b) => setJendela(b ? 'masukan' : null)}
                />
            ) : null}
            {doc.boleh_revisi ? (
                <>
                    <DialogRevisi
                        doc={doc}
                        masukanRevisi={doc.masukan}
                        jadwalPembuat={jadwal}
                        buka={jendela === 'revisi'}
                        onUbahBuka={(b) => setJendela(b ? 'revisi' : null)}
                    />
                    <DialogNonaktif
                        doc={doc}
                        buka={jendela === 'nonaktif'}
                        onUbahBuka={(b) => setJendela(b ? 'nonaktif' : null)}
                    />
                </>
            ) : null}
            {doc.boleh_batal_revisi ? (
                <ConfirmDialog
                    judul="Batalkan Revisi?"
                    pesan={`Batalkan revisi? Versi baru dibuang dan versi lama (${doc.nomor}) kembali Berlaku.`}
                    tombolYa="Ya, batalkan"
                    destruktif
                    buka={jendela === 'batal'}
                    onUbahBuka={(b) => setJendela(b ? 'batal' : null)}
                    onKonfirmasi={() =>
                        router.post(route('documents.cancelRevisionB', doc.id), {}, { preserveScroll: true })
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
        </div>
    );
}
