import { FileAddIcon, TrafficConeIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui-maia/button';
import {
    Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,
} from '@/components/ui-maia/empty';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { DocumentsUnavailableProps } from '@/types/wizard';

/**
 * "Jenis Dokumen Belum Tersedia" — kembaran V2
 * `pages/Documents/Unavailable.tsx`.
 *
 * Muncul saat jenis dokumen dinonaktifkan Admin di Master Data, atau schema-nya
 * belum ada. Jenis UNGGAHAN (FK/PX) TIDAK pernah sampai ke sini: schema
 * kosongnya bukan tanda "belum siap" melainkan bentuk normalnya.
 *
 * Jenis pengganti yang ditawarkan mengikuti PROFIL AKSES pengguna dan dihitung
 * di server (`User::jenisPertamaBoleh()`), bukan dipatok "SOP" — SOP bisa saja
 * justru tertutup baginya.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · Halaman ini SELURUHNYA satu keadaan kosong, jadi ia memakai komponen
 *    `empty` resmi apa adanya — bukan `Card` berisi ikon, judul, dan paragraf
 *    yang disusun sendiri. `Empty` sudah membawa tepi, jarak, dan lebar
 *    teks maksimalnya; `Card` di luarnya cuma menggandakan tepi itu.
 *  · §3.4 — ikon hiasan `size-12` → `size-8`, ukuran hiasan baku patokan.
 *    Rona `text-chart-3` sudah token sejak V1 (§3.5), jadi ia tetap.
 */
export default function DocumentsUnavailable() {
    const { type, jenisBaru } = usePage<PageProps & DocumentsUnavailableProps>().props;

    return (
        <AppLayout judul="Jenis Dokumen Belum Tersedia">
            <Empty>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <HugeiconsIcon
                            icon={TrafficConeIcon}
                            strokeWidth={1.5}
                            className="text-chart-3 size-8"
                        />
                    </EmptyMedia>
                    <EmptyTitle>Dokumen {type.code} Belum Tersedia</EmptyTitle>
                    <EmptyDescription>
                        {type.name} — schema untuk jenis dokumen ini sedang menunggu contoh dokumen dari
                        tim. Jenis lain akan aktif setelah format &amp; contohnya tersedia.
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <div className="flex flex-wrap justify-center gap-2">
                        {jenisBaru ? (
                            <Button asChild>
                                <Link href={route('documents.create', { type: jenisBaru })}>
                                    <HugeiconsIcon
                                        icon={FileAddIcon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Buat Dokumen {jenisBaru}
                                </Link>
                            </Button>
                        ) : null}
                        <Button asChild variant="outline">
                            <Link href={route('documents.index')}>Kembali</Link>
                        </Button>
                    </div>
                </EmptyContent>
            </Empty>
        </AppLayout>
    );
}
