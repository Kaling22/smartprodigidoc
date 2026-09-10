import {
    Alert02Icon, ArrowLeft01Icon, ArrowRight01Icon, FloppyDiskIcon, HashIcon, ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import {
    Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { Spinner } from '@/components/ui-maia/spinner';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PenomoranProps } from '@/types/pengaturan';

/**
 * "Penomoran Dokumen" — kembaran V2 `pages/Pengaturan/Penomoran.tsx`.
 *
 * Layar ini menyetel dua nilai yang masuk ke NOMOR DOKUMEN MUTU, jadi
 * bentuknya sengaja lebih berhati-hati daripada formulir biasa:
 *
 *   • Pratinjau LANGSUNG — Admin melihat nomor yang akan lahir sebelum menekan
 *     Simpan, bukan sesudahnya.
 *   • Contoh SEBELUM & SESUDAH bersanding — perubahan prefix tak pernah
 *     terlihat dari satu kotak isian sendirian. `semula` diambil dari props,
 *     bukan dari state, supaya ia tetap menunjukkan yang TERSIMPAN meski
 *     kotaknya sudah diketik ulang.
 *   • Peringatan tetap, bukan hanya di konfirmasi: yang berubah HANYA dokumen
 *     baru. Tak ada penomoran ulang surut, dan itu disengaja — nomor lama sudah
 *     beredar di luar sistem.
 *
 * Huruf besar dinaikkan di server (`SimpanPenomoranRequest::
 * prepareForValidation`); yang di sini hanya `uppercase` CSS supaya yang
 * terlihat sama dengan yang akan tersimpan.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — kedua isian dibungkus `FieldGroup`, jadi jarak antar-kelompok
 *    datang dari kit alih-alih dari `grid gap-5` yang diketik (arketipe
 *    `V2/Users/Create.tsx`).
 *  · §3.2 — kotak pratinjau `rounded-lg` → `rounded-2xl`: ia kotak buatan
 *    sendiri yang berperan sebagai kartu.
 *  · §3.5 — ikon peringatan `text-amber-500` → `text-chart-3`, kuning palet
 *    `.ui-v2` (preseden `V2/Documents/Show.tsx`). Nol warna baru.
 *  · §3.4 — tiap ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}` +
 *    `size-4`.
 *  · Tombol Simpan memakai `Spinner` alih-alih menukar teksnya jadi
 *    "Menyimpan…" — tombol yang berubah lebar saat ditekan menggeser tata
 *    letak tepat pada saat orang sedang menunggu (preseden `V2/Auth/Login`).
 */
export default function PengaturanPenomoran({ prefix, namaSite }: PenomoranProps) {
    const { data, setData, put, processing, errors } = useForm({
        prefix,
        nama_site: namaSite,
    });

    const [konfirmasi, setKonfirmasi] = useState(false);
    const contohBaru = `${data.prefix.toUpperCase() || '…'}-SOP-ICTMD-01`;

    // Tombol Simpan tetap `type="submit"` supaya `required` peramban bekerja;
    // konfirmasi menyusul HANYA setelah formulirnya lolos.
    function minta(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <AppLayout judul="Penomoran Dokumen">
            <div className="grid gap-4 md:gap-6 lg:grid-cols-12">
                <form onSubmit={minta} className="lg:col-span-7">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={HashIcon}
                                    strokeWidth={1.5}
                                    className="text-primary size-4"
                                    aria-hidden="true"
                                />
                                Setelan Site
                            </CardTitle>
                            <CardDescription>
                                Prefix nomor dan nama site yang dipakai seluruh dokumen mutu — berlaku
                                untuk semua jenis dokumen dan 7 departemen.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <FieldGroup>
                                <Field data-invalid={!!errors.prefix || undefined}>
                                    <FieldLabel htmlFor="prefix">Prefix Penomoran</FieldLabel>
                                    <Input
                                        id="prefix"
                                        required
                                        maxLength={20}
                                        className="font-mono uppercase"
                                        value={data.prefix}
                                        onChange={(e) => setData('prefix', e.target.value)}
                                        aria-invalid={!!errors.prefix}
                                    />
                                    <FieldDescription>
                                        Huruf, angka, dan garis pisah tunggal. Huruf kecil dinaikkan
                                        otomatis.
                                    </FieldDescription>
                                    <FieldError
                                        errors={errors.prefix ? [{ message: errors.prefix }] : undefined}
                                    />
                                </Field>

                                <Field data-invalid={!!errors.nama_site || undefined}>
                                    <FieldLabel htmlFor="nama_site">Nama Site</FieldLabel>
                                    <Input
                                        id="nama_site"
                                        required
                                        maxLength={60}
                                        className="uppercase"
                                        value={data.nama_site}
                                        onChange={(e) => setData('nama_site', e.target.value)}
                                        aria-invalid={!!errors.nama_site}
                                    />
                                    <FieldDescription>
                                        Tercetak pada judul daftar induk &amp; kop ekspor:{' '}
                                        <span className="font-mono">
                                            DAFTAR INDUK DOKUMEN SOP PPA SITE{' '}
                                            {data.nama_site.toUpperCase() || '…'}
                                        </span>
                                    </FieldDescription>
                                    <FieldError
                                        errors={
                                            errors.nama_site ? [{ message: errors.nama_site }] : undefined
                                        }
                                    />
                                </Field>

                                <div className="bg-muted/40 grid gap-2 rounded-2xl border p-3">
                                    <div className="text-muted-foreground flex items-center gap-2 text-sm font-medium">
                                        <HugeiconsIcon
                                            icon={ViewIcon}
                                            strokeWidth={1.5}
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Pratinjau nomor dokumen baru
                                    </div>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <div>
                                            <div className="text-muted-foreground text-[.72rem]">
                                                Sekarang
                                            </div>
                                            <code className="text-muted-foreground text-sm">
                                                {prefix}-SOP-ICTMD-01
                                            </code>
                                        </div>
                                        <HugeiconsIcon
                                            icon={ArrowRight01Icon}
                                            strokeWidth={1.5}
                                            className="text-muted-foreground size-4"
                                            aria-hidden="true"
                                        />
                                        <div>
                                            <div className="text-muted-foreground text-[.72rem]">
                                                Setelah disimpan
                                            </div>
                                            <code className="text-sm font-medium">{contohBaru}</code>
                                        </div>
                                    </div>
                                </div>
                            </FieldGroup>
                        </CardContent>

                        <CardFooter className="justify-end gap-2">
                            <Button asChild variant="ghost">
                                <Link href={route('dashboard')}>
                                    <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} className="size-4" />
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <HugeiconsIcon icon={FloppyDiskIcon} strokeWidth={1.5} className="size-4" />
                                )}
                                Simpan
                            </Button>
                            <ConfirmDialog
                                judul="Ubah penomoran site?"
                                pesan="Setelan ini hanya berlaku untuk dokumen yang dibuat SETELAH disimpan. Nomor dokumen yang sudah ada tidak akan berubah."
                                tombolYa="Ya, simpan"
                                buka={konfirmasi}
                                onUbahBuka={setKonfirmasi}
                                onKonfirmasi={() => put(route('pengaturan.penomoran.simpan'))}
                            />
                        </CardFooter>
                    </Card>
                </form>

                <Card className="bg-muted/30 lg:col-span-5">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <HugeiconsIcon
                                icon={Alert02Icon}
                                strokeWidth={1.5}
                                className="text-chart-3 size-4"
                                aria-hidden="true"
                            />
                            Yang perlu diketahui
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="text-muted-foreground grid list-disc gap-3 pl-4 text-sm">
                            <li>
                                Setelan ini <strong>hanya berlaku untuk dokumen baru</strong>. Nomor
                                dokumen yang sudah dibuat — apalagi yang sudah Berlaku — tidak
                                ditulis ulang.
                            </li>
                            <li>
                                Penomoran ulang surut memang <strong>tidak disediakan</strong>: nomor
                                dokumen mutu sudah beredar di luar sistem (laporan audit, dokumen
                                lain yang merujuknya, cetakan di lapangan). Menulisnya ulang membuat
                                rujukan itu menunjuk ke sesuatu yang tak lagi bernama sama.
                            </li>
                            <li>
                                Karena itu daftar induk akan memuat{' '}
                                <strong>dua pola nomor sekaligus</strong> selama masa peralihan. Itu
                                perilaku yang benar, bukan kesalahan.
                            </li>
                            <li>
                                Konsekuensinya: dokumen berprefix lama akan berlencana{' '}
                                <Badge variant="secondary">Nomor Lama</Badge> — sebab lencana itu
                                memang berarti &ldquo;di luar pola yang berlaku sekarang&rdquo;.
                                Isinya tidak berubah dan tetap bisa dibuka seperti biasa.
                            </li>
                            <li>
                                Perubahan dicatat di{' '}
                                <Link href={route('audit.index')} className="underline">
                                    Audit Log
                                </Link>{' '}
                                lengkap dengan nilai sebelum &amp; sesudahnya.
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
