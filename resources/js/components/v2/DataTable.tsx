import { ArrowDown01Icon, ArrowUp01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';
import { Fragment, type ReactNode } from 'react';

import { Button } from '@/components/ui-maia/button';
import {
    Pagination, PaginationContent, PaginationItem,
} from '@/components/ui-maia/pagination';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui-maia/table';
import { cn } from '@/lib/utils';

/**
 * Tabel daftar V2 — arketipe untuk 20+ daftar lain.
 *
 * TETAP BUKAN TanStack Table, dan itu keputusan sadar yang tak berubah:
 * pengurutan ada di `Document::scopeUrut` dan halamannya dipenggal paginator
 * Laravel. Mengurutkan di klien hanya akan membalik 15 baris yang kebetulan
 * sedang tampil — bukan seluruh dokumen — sehingga mesin tabel di peramban
 * justru merusak perilaku yang sudah benar.
 *
 * Beda dari versi nova:
 *  • `thead` LENGKET (`sticky top-0`) — daftar panjang berhenti kehilangan nama
 *    kolomnya saat digulir.
 *  • Baris punya keadaan hover.
 *  • Pembungkus `rounded-lg border` dilepas: tabel ini sekarang duduk di dalam
 *    `Card` yang sudah punya tepinya sendiri, dan dua tepi bersarang membaca
 *    seperti kotak di dalam kotak.
 *
 * Yang WAJIB bertahan, dan bertahan: `perluas()` (baris ekspansi "Dokumen Tidak
 * Berlaku"), `JudulUrut` yang menulis `sort`/`dir` ke query string sambil
 * mempertahankan query lain dan MEMBUANG `page`, serta paginator yang membaca
 * `links`/`from`/`to`/`total` Laravel apa adanya.
 */

export interface Kolom<T> {
    judul: ReactNode;
    /** Kunci di `Document::KOLOM_URUT`. Diisi = judulnya jadi tautan urut. */
    urut?: string;
    kelas?: string;
    render: (baris: T) => ReactNode;
}

/** Bentuk paginator Laravel (`->links` gaya Bootstrap/Tailwind bawaan). */
export interface Paginator<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
}

export function DataTable<T>({
    kolom,
    baris,
    kunci,
    kosong = 'Belum ada data.',
    className,
    perluas,
}: {
    kolom: Kolom<T>[];
    baris: T[];
    kunci: (b: T, i: number) => string | number;
    kosong?: ReactNode;
    className?: string;
    /**
     * Baris TAMBAHAN di bawah barisnya sendiri (mis. daftar versi lama di
     * "Dokumen Tidak Berlaku"). `null` = tak ada yang dibentangkan.
     *
     * Buka-tutupnya dipegang HALAMAN, bukan komponen ini: penyingkapnya duduk
     * di kolom Aksi yang digambar halaman, jadi keadaannya memang harus ada di
     * sana. Yang dikerjakan di sini cuma menempatkan barisnya.
     */
    perluas?: (b: T) => ReactNode | null;
}) {
    return (
        <div className={cn('overflow-x-auto', className)}>
            <Table>
                <TableHeader className="bg-card sticky top-0 z-10">
                    <TableRow>
                        {kolom.map((k, i) => (
                            <TableHead key={i} className={k.kelas}>
                                {k.urut ? <JudulUrut kolom={k.urut}>{k.judul}</JudulUrut> : k.judul}
                            </TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {baris.length === 0 ? (
                        <TableRow className="hover:bg-transparent">
                            <TableCell colSpan={kolom.length} className="p-0">
                                {kosong}
                            </TableCell>
                        </TableRow>
                    ) : (
                        baris.map((b, i) => {
                            const tambahan = perluas?.(b);

                            return (
                                <Fragment key={kunci(b, i)}>
                                    <TableRow className="hover:bg-muted/40">
                                        {kolom.map((k, j) => (
                                            <TableCell key={j} className={k.kelas}>
                                                {k.render(b)}
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                    {tambahan ? (
                                        <TableRow>
                                            <TableCell colSpan={kolom.length} className="bg-muted/40 p-2">
                                                {tambahan}
                                            </TableCell>
                                        </TableRow>
                                    ) : null}
                                </Fragment>
                            );
                        })
                    )}
                </TableBody>
            </Table>
        </div>
    );
}

/**
 * Judul kolom yang bisa diurutkan.
 *
 * Seluruh query string lain (cari, jenis, departemen) ikut dibawa, jadi menekan
 * judul kolom tak pernah menghapus penyaringan yang sedang aktif. `page` sengaja
 * DIBUANG: urutan baru berarti halaman 3 memuat baris yang sama sekali berbeda.
 */
function JudulUrut({ kolom, children }: { kolom: string; children: ReactNode }) {
    const url = usePage().url;
    const [jalur, kueri = ''] = url.split('?');
    const params = new URLSearchParams(kueri);

    const aktif = params.get('sort') === kolom;
    const menurun = aktif && params.get('dir') === 'desc';
    // Kolom aktif membalik arah; kolom lain selalu mulai dari menaik.
    const arahBerikut = aktif && !menurun ? 'desc' : 'asc';

    params.set('sort', kolom);
    params.set('dir', arahBerikut);
    params.delete('page');

    return (
        <Link
            href={`${jalur}?${params.toString()}`}
            preserveScroll
            className={cn(
                'inline-flex items-center gap-1 hover:underline',
                aktif && 'text-foreground font-semibold',
            )}
            title={`Urutkan ${arahBerikut === 'asc' ? 'menaik' : 'menurun'}`}
        >
            {children}
            {/* Dua caret bertumpuk: yang aktif pekat, pasangannya redup — arah
                yang SEDANG berlaku terbaca tanpa perlu mengingat klik terakhir. */}
            <span className="flex flex-col leading-none" aria-hidden="true">
                <HugeiconsIcon
                    icon={ArrowUp01Icon}
                    strokeWidth={2}
                    className={cn('-mb-1 size-3', aktif && !menurun ? 'opacity-100' : 'opacity-30')}
                />
                <HugeiconsIcon
                    icon={ArrowDown01Icon}
                    strokeWidth={2}
                    className={cn('size-3', menurun ? 'opacity-100' : 'opacity-30')}
                />
            </span>
        </Link>
    );
}

/**
 * Navigasi halaman dari paginator Laravel.
 *
 * Label datang ber-entitas HTML (`&laquo; Previous`). Dua entitas itu diganti
 * teksnya, BUKAN disuntikkan lewat `dangerouslySetInnerHTML`: labelnya memang
 * terbit dari Laravel, tapi menyuntikkan HTML mentah di komponen yang dipakai
 * 20+ halaman adalah kebiasaan yang cuma menunggu label pertama yang berasal
 * dari data pengguna.
 *
 * Memakai kerangka `pagination` resmi, tapi tautannya `Button asChild` + `Link`
 * Inertia — bukan `PaginationLink`, yang membungkus `<a>` biasa dan karena itu
 * memuat ulang seluruh halaman alih-alih melakukan kunjungan Inertia.
 */
function labelHalaman(label: string): string {
    return label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
}

export function Paginasi<T>({ paginator }: { paginator: Paginator<T> }) {
    if (paginator.links.length <= 3) {
        return null;
    }

    return (
        <div className="flex w-full flex-wrap items-center justify-between gap-2">
            <p className="text-muted-foreground text-sm tabular-nums">
                Menampilkan {paginator.from ?? 0}–{paginator.to ?? 0} dari {paginator.total}
            </p>
            <Pagination className="mx-0 w-auto justify-end">
                <PaginationContent>
                    {paginator.links.map((l, i) => (
                        <PaginationItem key={i}>
                            {l.url ? (
                                <Button
                                    asChild
                                    size="sm"
                                    variant={l.active ? 'default' : 'ghost'}
                                    aria-current={l.active ? 'page' : undefined}
                                >
                                    <Link href={l.url} preserveScroll>
                                        {labelHalaman(l.label)}
                                    </Link>
                                </Button>
                            ) : (
                                <Button size="sm" variant="ghost" disabled>
                                    {labelHalaman(l.label)}
                                </Button>
                            )}
                        </PaginationItem>
                    ))}
                </PaginationContent>
            </Pagination>
        </div>
    );
}
