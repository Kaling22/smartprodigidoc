import { HourglassIcon, OctagonXIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router } from '@inertiajs/react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import { AuthLayout } from '@/layouts/V2/AuthLayout';

/**
 * "Menunggu Persetujuan" — kembaran V2 `pages/Auth/Pending.tsx`.
 *
 * Memakai kerangka tamu yang sama dengan Masuk & Daftar, jadi ketiganya satu
 * wajah. Penolakan disampaikan lewat `Alert variant="destructive"`, bukan kotak
 * merah karangan sendiri.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — isinya RATA KIRI, bukan `items-center text-center`:
 *    `layouts/V2/AuthLayout` sejak REVISI-UI-V3 R12 menurunkan logo tepat di
 *    atas blok ini dan merapatkannya ke kiri (pola `V2/Auth/Login`).
 *  · §3.2 — medali ikon `rounded-full` dipertahankan (§3.2 memang menyebut
 *    `rounded-full` untuk avatar & titik), ukurannya tetap `size-12` dengan
 *    glif `size-6` di dalamnya.
 *  · §3.4 — kedua ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}`.
 */
export default function Pending({ name, status }: { name: string; status: string }) {
    return (
        <AuthLayout judul="Menunggu Persetujuan" lebar="max-w-sm">
            <div className="flex flex-col gap-4">
                <div className="bg-muted flex size-12 items-center justify-center rounded-full">
                    <HugeiconsIcon
                        icon={HourglassIcon}
                        strokeWidth={1.5}
                        className="text-muted-foreground size-6"
                    />
                </div>

                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-bold">Akun Menunggu Persetujuan</h1>
                    <p className="text-muted-foreground text-sm text-balance">
                        Halo <strong className="text-foreground">{name}</strong>, pendaftaran Anda
                        sudah diterima. Akun akan aktif setelah disetujui oleh Group Leader,
                        Pimpinan, atau Admin IT departemen Anda.
                    </p>
                </div>

                {status === 'rejected' ? (
                    <Alert variant="destructive">
                        <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} className="size-4" />
                        <AlertTitle>Pendaftaran ditolak</AlertTitle>
                        <AlertDescription>
                            Silakan hubungi administrator departemen Anda.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <Button variant="outline" onClick={() => router.post(route('logout'))}>
                    Kembali ke Login
                </Button>
            </div>
        </AuthLayout>
    );
}
