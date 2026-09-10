import {
    ChevronRightIcon, Delete02Icon, File01Icon, FileMinusIcon, HistoryIcon, Image01Icon, InboxIcon,
    LinkSquare01Icon, RefreshCwIcon, Upload01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import {
    Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle,
} from '@/components/ui-maia/dialog';
import { DropdownMenuItem } from '@/components/ui-maia/dropdown-menu';
import {
    Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,
} from '@/components/ui-maia/empty';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { Ikon } from '@/components/v2/Ikon';
import { PenyaringDokumen } from '@/components/v2/PenyaringDokumen';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type { BarisInformasi, InformasiIndexProps, VersiInformasi } from '@/types/informasi';

/**
 * Daftar Informasi per kategori — kembaran V2 `pages/Informasi/Index.tsx`.
 *
 * SATU halaman untuk SELURUH kategori (CLAUDE.md §3): kolom tabelnya mengikuti
 * `kolom` (`informasi_kategori.kolom_json`), bukan cabang per kategori — itulah
 * yang membuat kategori baru bisa ditambah Admin dari Master Data tanpa
 * menyentuh satu berkas pun.
 *
 * Riwayat TERSARANG di bawah barisnya sendiri, terlipat. Dulu ia halaman
 * sendiri, dan itu keliru: versi lama hanya punya arti DI SEBELAH versi yang
 * menggantikannya.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring masuk ke `CardHeader className="border-b"` kartu
 *    tabelnya, tabel ke `CardContent className="px-0"`, paginasi ke
 *    `CardFooter className="border-t pt-6"`. SATU kartu, bukan penyaring yang
 *    berdiri sendiri di atas kartu tabel (arketipe `V2/Documents/Index.tsx`).
 *  · §3.7 — Perbarui & Hapus turun ke `StripAksi`; jendelanya dirender DI LUAR
 *    menu (Radix melepas isi menunya begitu item dipilih, §3.8). Yang TETAP
 *    berdiri di baris: penyingkap Riwayat — ia penyingkap baris, bukan aksi
 *    atas dokumen (preseden `V2/Documents/Obsolete.tsx`) — dan tombol Buka,
 *    satu-satunya aksi yang dipakai SEMUA peran, termasuk yang tak mengelola.
 *  · §3.6 — kalimat pengantar beserta ikon kategorinya pindah ke prop `sub`
 *    layout.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon,
 *    dan jalan keluarnya (unggah yang pertama) jadi tombol sungguhan alih-alih
 *    tautan bergaris bawah di tengah kalimat.
 *
 * Ikon kategori datang dari `informasi_kategori.ikon` — kunci SERVER, jadi ia
 * tetap lewat `v2/Ikon`; ikon kendali diimpor langsung (§3.4).
 */
export default function InformasiIndex() {
    const { kategori, label, ikon, kolom, kelola, daftar, filters, prefix } =
        usePage<PageProps & InformasiIndexProps>().props;

    // Buka-tutup riwayat dipegang HALAMAN: penyingkapnya duduk di dua tempat
    // (chevron kolom pertama + tombol "Riwayat (N)" di kolom Aksi) yang berbagi
    // satu keadaan, persis seperti satu `x-data` per <tbody> di Blade lama.
    const [terbuka, setTerbuka] = useState<number[]>([]);
    const alih = (id: number) =>
        setTerbuka((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

    const adaRevisi = kolom.includes('edisi');
    const adaTanggal = kolom.includes('tanggal_efektif');

    const kolomTabel: Kolom<BarisInformasi>[] = [
        {
            judul: <span className="sr-only">Riwayat</span>,
            kelas: 'w-10',
            render: (i) =>
                i.riwayat.length > 0 ? (
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-expanded={terbuka.includes(i.id)}
                        title={
                            terbuka.includes(i.id)
                                ? 'Sembunyikan riwayat'
                                : `Lihat ${i.riwayat.length} versi lama`
                        }
                        onClick={() => alih(i.id)}
                    >
                        <HugeiconsIcon
                            icon={ChevronRightIcon}
                            strokeWidth={1.5}
                            className={cn(
                                'size-4 transition-transform',
                                terbuka.includes(i.id) && 'rotate-90',
                            )}
                        />
                    </Button>
                ) : null,
        },
        { judul: 'Nomor', render: (i) => <span className="font-mono text-sm">{i.nomor}</span> },
        { judul: 'Judul', render: (i) => <span className="font-medium">{i.judul}</span> },
        ...(adaRevisi
            ? [
                  {
                      judul: 'Edisi / Rev',
                      kelas: 'text-center',
                      render: (i: BarisInformasi) => `${i.edisi ?? '—'} / ${i.no_revisi ?? '—'}`,
                  } satisfies Kolom<BarisInformasi>,
              ]
            : []),
        ...(adaTanggal
            ? [
                  {
                      judul: 'Tgl Efektif',
                      render: (i: BarisInformasi) => <span className="text-sm">{i.tanggal ?? '—'}</span>,
                  } satisfies Kolom<BarisInformasi>,
              ]
            : []),
        {
            judul: 'Diunggah',
            render: (i) => (
                <span className="text-muted-foreground text-sm">
                    {i.diunggah}
                    <span className="block text-xs">{i.oleh ?? '—'}</span>
                </span>
            ),
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (i) => (
                <div className="flex items-center justify-end gap-2">
                    {/* Riwayat duduk di kolom Aksi sebagai tombol sungguhan.
                        Dulu ia lencana di kolom Judul: bisa diklik, tapi tak ada
                        yang menyangka lencana adalah tombol. */}
                    {i.riwayat.length > 0 ? (
                        <Button variant="outline" size="sm" onClick={() => alih(i.id)}>
                            <HugeiconsIcon icon={HistoryIcon} strokeWidth={1.5} className="size-4" />
                            Riwayat ({i.riwayat.length})
                        </Button>
                    ) : null}
                    <TombolBuka versi={i} />
                    {kelola ? <AksiKelola info={i} /> : null}
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul={label}
            sub={
                <span className="flex items-start gap-1.5">
                    <Ikon nama={ikon} className="mt-0.5 size-4 shrink-0" />
                    Dokumen informasi yang berlaku — terbuka untuk semua departemen dan semua jabatan.
                    Versi lama tersimpan sebagai riwayat di bawah barisnya masing-masing.
                </span>
            }
            aksi={
                kelola ? (
                    <Button asChild>
                        <Link href={route('informasi.create', { kategori })}>
                            <HugeiconsIcon icon={Upload01Icon} strokeWidth={1.5} className="size-4" />
                            Tambah {label}
                        </Link>
                    </Button>
                ) : null
            }
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('informasi.index')}
                        filters={filters}
                        contoh={`${prefix}-KBJ`}
                        tersembunyi={{ kategori }}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    <DataTable
                        kolom={kolomTabel}
                        baris={daftar.data}
                        kunci={(i) => i.id}
                        perluas={(i) =>
                            terbuka.includes(i.id) && i.riwayat.length > 0 ? (
                                <Riwayat info={i} kelola={kelola} />
                            ) : null
                        }
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
                                    <EmptyTitle>Belum ada {label}</EmptyTitle>
                                    <EmptyDescription>
                                        Coba longgarkan penyaring di atas.
                                    </EmptyDescription>
                                </EmptyHeader>
                                {kelola ? (
                                    <EmptyContent>
                                        <Button asChild size="sm">
                                            <Link href={route('informasi.create', { kategori })}>
                                                <HugeiconsIcon
                                                    icon={Upload01Icon}
                                                    strokeWidth={1.5}
                                                    className="size-4"
                                                />
                                                Unggah yang pertama
                                            </Link>
                                        </Button>
                                    </EmptyContent>
                                ) : null}
                            </Empty>
                        }
                    />
                </CardContent>

                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={daftar} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}

/** Tombol Buka — ikonnya mengikuti jenis berkasnya (POSTER boleh gambar). */
function TombolBuka({ versi, ringkas = false }: { versi: VersiInformasi; ringkas?: boolean }) {
    return (
        <Button asChild variant="outline" size="sm">
            <a href={route('informasi.file', versi.id)} target="_blank" rel="noopener">
                <HugeiconsIcon
                    icon={ringkas ? LinkSquare01Icon : versi.gambar ? Image01Icon : File01Icon}
                    strokeWidth={1.5}
                    className="size-4"
                />
                Buka
            </a>
        </Button>
    );
}

/**
 * Perbarui & Hapus untuk baris yang boleh dikelola.
 *
 * Jendela Hapus dirender DI LUAR menu dan DIKENDALIKAN (§3.8): Radix melepas
 * isi menunya begitu item dipilih, jadi jendela yang dirender di dalamnya
 * lenyap sebelum sempat terlihat.
 */
function AksiKelola({ info }: { info: BarisInformasi }) {
    const [hapus, setHapus] = useState(false);

    return (
        <>
            <StripAksi>
                <DropdownMenuItem asChild>
                    <Link href={route('informasi.perbarui', info.id)}>
                        <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" />
                        Perbarui
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem variant="destructive" onSelect={() => setHapus(true)}>
                    <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                    Hapus
                </DropdownMenuItem>
            </StripAksi>
            <DialogHapus info={info} buka={hapus} onUbahBuka={setHapus} />
        </>
    );
}

/**
 * Versi lama satu nomor. Menjorok + bergaris kiri, supaya kepemilikannya pada
 * baris di atasnya terbaca sekali lihat tanpa perlu membaca nomornya lagi.
 */
function Riwayat({ info, kelola }: { info: BarisInformasi; kelola: boolean }) {
    return (
        <div className="border-border ml-1 border-l-[3px] pl-3">
            <p className="text-muted-foreground mb-2 flex items-center gap-1.5 text-sm font-semibold">
                <HugeiconsIcon
                    icon={HistoryIcon}
                    strokeWidth={1.5}
                    className="size-4"
                    aria-hidden="true"
                />
                Versi lama {info.nomor}
            </p>
            {info.riwayat.map((lama) => (
                <div
                    key={lama.id}
                    className="flex flex-wrap items-center justify-between gap-2 border-b py-1 last:border-b-0"
                >
                    <span className="text-sm">
                        {lama.judul}
                        {lama.revisi ? (
                            <Badge variant="secondary" className="ml-2">
                                {lama.revisi}
                            </Badge>
                        ) : null}
                        <span className="text-muted-foreground block text-xs">
                            Diunggah {lama.diunggah_lengkap} WITA · {lama.oleh ?? '—'}
                        </span>
                    </span>
                    <span className="flex gap-2">
                        <TombolBuka versi={lama} ringkas />
                        {/* Riwayat hanya bisa dihapus satu-satu: "hapus seluruhnya"
                            adalah aksi atas NOMOR, dan tempatnya di baris induk. */}
                        {kelola ? (
                            <ConfirmDialog
                                judul="Hapus Versi Lama?"
                                pesan={`Hapus versi lama ini dari riwayat ${info.nomor}? Versi yang sedang berlaku tidak tersentuh.`}
                                tombolYa="Ya, hapus"
                                destruktif
                                onKonfirmasi={() => hapus(lama.id, 'versi')}
                                pemicu={
                                    <Button variant="outline" size="icon" title="Hapus versi lama">
                                        <HugeiconsIcon
                                            icon={Delete02Icon}
                                            strokeWidth={1.5}
                                            className="size-4"
                                        />
                                    </Button>
                                }
                            />
                        ) : null}
                    </span>
                </div>
            ))}
        </div>
    );
}

function hapus(id: number, cakupan: 'versi' | 'semua') {
    router.delete(route('informasi.destroy', id), {
        data: { cakupan },
        preserveScroll: true,
    });
}

/**
 * Hapus versi BERLAKU — cakupannya dipilih di sini, tak diasumsikan.
 *
 * Dua pilihan itu tak bisa dibatalkan dan akibatnya sangat berbeda: "versi ini
 * saja" mengembalikan riwayat terbaru menjadi berlaku, sehingga nomor itu tetap
 * punya dokumen; "seluruhnya" membuang nomor itu dari daftar. Satu tombol Hapus
 * yang diam-diam memilih salah satunya adalah tombol yang suatu hari memilih
 * yang tidak diinginkan.
 *
 * Tanpa riwayat, keduanya berakibat sama persis — jadi pilihannya tak
 * ditawarkan sama sekali. Pilihan palsu lebih buruk daripada tidak ada pilihan.
 *
 * Jendelanya DIKENDALIKAN dari `AksiKelola`, bukan berpemicu seperti di V1:
 * pemicunya kini item menu, dan menu Radix melepas isinya begitu item dipilih.
 */
function DialogHapus({
    info,
    buka,
    onUbahBuka,
}: {
    info: BarisInformasi;
    buka: boolean;
    onUbahBuka: (b: boolean) => void;
}) {
    const jumlah = info.riwayat.length;
    const punyaRiwayat = jumlah > 0;

    return (
        <Dialog open={buka} onOpenChange={onUbahBuka}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-1.5">
                        <HugeiconsIcon
                            icon={Delete02Icon}
                            strokeWidth={1.5}
                            className="size-4"
                            aria-hidden="true"
                        />
                        Hapus {info.nomor}
                    </DialogTitle>
                    <DialogDescription>
                        <span className="font-semibold">{info.judul}</span>
                        {info.revisi ? ` — ${info.revisi}` : ''}
                        {punyaRiwayat ? `. Nomor ini punya ${jumlah} versi lama di riwayatnya.` : ''}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-2">
                    <ConfirmDialog
                        judul={punyaRiwayat ? 'Hapus Versi Ini?' : `Hapus ${info.nomor}?`}
                        pesan={
                            punyaRiwayat
                                ? `Versi ini dihapus, dan versi riwayat TERBARU naik menggantikannya sehingga nomor ${info.nomor} tetap punya dokumen.`
                                : `Hapus ${info.nomor}? Nomor ini tidak punya versi lain, jadi ia hilang dari daftar sampai diunggah lagi.`
                        }
                        tombolYa="Ya, hapus"
                        destruktif
                        onKonfirmasi={() => {
                            onUbahBuka(false);
                            hapus(info.id, 'versi');
                        }}
                        pemicu={
                            <Button
                                variant={punyaRiwayat ? 'outline' : 'destructive'}
                                className="h-auto w-full justify-start py-2 text-left"
                            >
                                <HugeiconsIcon
                                    icon={FileMinusIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                <span>
                                    <span className="block font-semibold">
                                        {punyaRiwayat ? 'Hapus versi ini saja' : 'Hapus dokumen ini'}
                                    </span>
                                    <span className="block text-xs font-normal opacity-80">
                                        {punyaRiwayat
                                            ? 'Riwayat terbaru naik jadi berlaku.'
                                            : 'Tidak ada riwayat yang ikut terhapus.'}
                                    </span>
                                </span>
                            </Button>
                        }
                    />

                    {punyaRiwayat ? (
                        <ConfirmDialog
                            judul="Hapus Seluruhnya?"
                            pesan={`SELURUH versi bernomor ${info.nomor} dihapus — versi berlaku dan ${jumlah} versi riwayatnya. Nomor ini hilang dari daftar sampai diunggah lagi.`}
                            tombolYa="Ya, hapus semuanya"
                            destruktif
                            onKonfirmasi={() => {
                                onUbahBuka(false);
                                hapus(info.id, 'semua');
                            }}
                            pemicu={
                                <Button
                                    variant="destructive"
                                    className="h-auto w-full justify-start py-2 text-left"
                                >
                                    <HugeiconsIcon
                                        icon={Delete02Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    <span>
                                        <span className="block font-semibold">
                                            Hapus seluruhnya, riwayat sekalian
                                        </span>
                                        <span className="block text-xs font-normal opacity-80">
                                            {jumlah + 1} versi dibuang.
                                        </span>
                                    </span>
                                </Button>
                            }
                        />
                    ) : null}
                </div>
            </DialogContent>
        </Dialog>
    );
}
