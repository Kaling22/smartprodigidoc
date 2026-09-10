import { Link, usePage } from '@inertiajs/react';

import { Button } from '@/components/ui-maia/button';
import {
    Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,
} from '@/components/ui-maia/empty';
import { Ikon } from '@/components/v2/Ikon';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { InformasiNonaktifProps } from '@/types/informasi';

/**
 * Kategori Informasi yang DITUTUP Admin — kembaran V2
 * `pages/Informasi/Nonaktif.tsx`.
 *
 * Kategori tidak pernah dihapus (`informasi.kategori` menunjuk ke sana), yang
 * ditutup adalah pintunya. Halaman ini mengatakannya terang-terangan alih-alih
 * membiarkan menunya lenyap tanpa kabar: dokumennya masih ada, dan orang perlu
 * tahu bahwa yang berubah adalah keputusan, bukan datanya.
 *
 * Sidebar sudah membuat menunya tak bisa diklik; layar ini menjaga URL yang
 * ditempel langsung atau ditandai favorit.
 *
 * Ikonnya datang dari `informasi_kategori.ikon` — kunci SERVER, jadi ia tetap
 * lewat `v2/Ikon` dan tak boleh diimpor langsung (PATOKAN-GAYA-V2 §3.4).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · Halaman ini SELURUHNYA satu keadaan kosong, jadi ia memakai komponen
 *    `empty` resmi apa adanya — bukan `Card` berisi ikon, judul, dan dua
 *    paragraf yang disusun sendiri dengan `py-10 text-center` yang diketik.
 *    `Empty` sudah membawa tepi, jarak, dan lebar teksnya (preseden
 *    `V2/Documents/Unavailable.tsx`).
 *  · §3.4 — ikon hiasan `size-12` → `size-8`, ukuran hiasan baku patokan.
 *
 * Kedua paragraf tetap DUA: yang pertama menerangkan keadaannya, yang kedua
 * menjawab pertanyaan yang langsung menyusul ("dokumen saya bagaimana?").
 */
export default function InformasiNonaktif() {
    const { kat } = usePage<PageProps & InformasiNonaktifProps>().props;

    return (
        <AppLayout judul={kat.nama}>
            <Empty>
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Ikon nama={kat.ikon} className="text-muted-foreground size-8" />
                    </EmptyMedia>
                    <EmptyTitle>{kat.nama} Sedang Ditutup</EmptyTitle>
                    <EmptyDescription>
                        <p>
                            Kategori ini dinonaktifkan oleh Admin, jadi daftarnya tidak dibuka dan
                            unggahan baru tidak diterima.
                        </p>
                        <p className="mt-2">
                            Dokumen yang sudah ada <strong>tidak dihapus</strong>. Bila Anda masih
                            memerlukannya, hubungi Admin untuk membuka kembali kategori ini.
                        </p>
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <Button asChild variant="outline">
                        <Link href={route('dashboard')}>Kembali</Link>
                    </Button>
                </EmptyContent>
            </Empty>
        </AppLayout>
    );
}
