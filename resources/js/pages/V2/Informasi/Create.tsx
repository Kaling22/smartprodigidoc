import { usePage } from '@inertiajs/react';

import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import { FormInformasi } from '@/components/v2/FormInformasi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { InformasiCreateProps } from '@/types/informasi';

/**
 * "Tambah Informasi" — kembaran V2 `pages/Informasi/Create.tsx`.
 *
 * Untuk dokumen yang BELUM ada di daftar; yang sudah ada diperbarui lewat
 * tombol Perbarui pada barisnya (dua pintu berbeda, lihat `InformasiController`).
 *
 * Bentuk formulirnya sendiri milik `v2/FormInformasi` (PATOKAN-GAYA-V2 Fase 1),
 * yang dipakai bersama halaman Perbarui — halaman ini cuma menaruhnya.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — keterangan "Untuk dokumen yang belum ada di daftar…" tidak naik ke
 *    prop `sub` melainkan turun ke kepala kartu: ia menerangkan FORMULIR ini
 *    (pintu mana yang benar), bukan halamannya, dan remah di topbar sudah
 *    menamai halamannya (preseden `V2/Users/Create.tsx`).
 *  · `max-w-3xl` dilepas dari kartunya. Isian formulir ini sudah dibatasi
 *    `FieldGroup`, dan lebar kartu yang dipatok sendiri adalah satu angka lagi
 *    yang menyimpang dari kartu V2 lain tanpa satu pun gerbang merah.
 */
export default function InformasiCreate() {
    const props = usePage<PageProps & InformasiCreateProps>().props;

    return (
        <AppLayout
            judul={`Tambah ${props.label}`}
            remah={[
                { label: props.label, href: route('informasi.index', { kategori: props.kategori }) },
                { label: 'Tambah' },
            ]}
        >
            <Card>
                <CardHeader>
                    <CardTitle>Tambah {props.label}</CardTitle>
                    <CardDescription>
                        Untuk dokumen yang <strong>belum ada</strong> di daftar. Yang sudah ada
                        diperbarui lewat tombol Perbarui pada barisnya.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <FormInformasi {...props} />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
