import { Head, usePage } from '@inertiajs/react';
import { useEffect, type CSSProperties, type ReactNode } from 'react';
import { toast } from 'sonner';

import { SidebarInset, SidebarProvider } from '@/components/ui-maia/sidebar';
import { AppSidebar } from '@/components/v2/AppSidebar';
import { DialogAntrean } from '@/components/v2/DialogAntrean';
import { SiteHeader, type Remah } from '@/components/v2/SiteHeader';
import type { PageProps } from '@/types';

/**
 * Kerangka V2 — irama `dashboard-01` apa adanya.
 *
 * Yang datang langsung dari blok: `--sidebar-width: calc(var(--spacing)*72)`,
 * `--header-height: calc(var(--spacing)*12)`, `Sidebar variant="inset"`,
 * `@container/main`, irama `gap-4 py-4 md:gap-6 md:py-6`, dan talang
 * `px-4 lg:px-6` yang dipasang SEKALI di sini alih-alih diulang di tiap anak.
 *
 * JUDUL halaman TIDAK digambar di badan. Ia duduk di topbar, sebaris dengan
 * `SidebarTrigger` — tempatnya di blok, dan tempat pemilik memintanya
 * (pemeriksaan mata sesudah Fase 2). Percobaan Fase 2 menaruhnya di badan
 * sebagai H1 besar; ia menggandakan sapaan `KartuSambutan` yang tepat di
 * bawahnya dan memakan satu baris penuh sebelum isi pertama.
 *
 * Yang TERSISA di badan cuma baris `sub` + `aksi` — dan hanya bila halaman
 * benar-benar mengisi salah satunya. Halaman yang tak mengisi keduanya membuka
 * langsung dengan isinya, tanpa baris kosong berjarak.
 *
 * Kelas `.ui-v2` di akar: palet grafik kategorikal berlingkup di situ
 * (REDESAIN-UI-V2 §6), supaya 41 halaman lama tak ikut berubah warnanya selama
 * jendela pratinjau. Tranche 4 memindahkannya ke `:root`.
 */
export function AppLayout({
    judul,
    remah,
    sub,
    aksi,
    children,
}: {
    judul: string;
    /** Breadcrumb topbar. Kosong = topbar hanya memuat kendali. */
    remah?: Remah[];
    /** Baris keterangan di bawah H1 (jabatan · dept · jam, jumlah baris, dsb.). */
    sub?: ReactNode;
    /** Kendali & tombol utama halaman — rata kanan, di atas isi. */
    aksi?: ReactNode;
    children: ReactNode;
}) {
    return (
        <SidebarProvider
            className="ui-v2"
            style={
                {
                    '--sidebar-width': 'calc(var(--spacing) * 72)',
                    '--header-height': 'calc(var(--spacing) * 12)',
                } as CSSProperties
            }
        >
            <Head title={judul} />
            <AppSidebar variant="inset" />
            <SidebarInset>
                <SiteHeader judul={judul} remah={remah} />
                <FlashToast />
                {/* Modal tugas pasca-login. Di layout, bukan di dashboard:
                    LoginController tak berjanji tujuannya selalu dashboard. */}
                <DialogAntrean />
                <div className="flex flex-1 flex-col">
                    <div className="@container/main flex flex-1 flex-col gap-2">
                        <div className="flex flex-col gap-4 px-4 py-4 md:gap-6 md:py-6 lg:px-6">
                            {sub || aksi ? (
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    {sub ? (
                                        <p className="text-muted-foreground min-w-0 text-sm">{sub}</p>
                                    ) : (
                                        <span />
                                    )}
                                    {aksi ? (
                                        <div className="flex flex-wrap items-center gap-2">{aksi}</div>
                                    ) : null}
                                </div>
                            ) : null}
                            {children}
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </SidebarProvider>
    );
}

/**
 * Pesan sukses/galat dari session.
 *
 * Dipasang di layout, bukan di tiap halaman, karena itu memang corong yang
 * dilewati semuanya. (`flash.success` sudah ikut membaca kunci `status`, lihat
 * `HandleInertiaRequests::share()`.)
 *
 * Satu `Toaster` melayani kedua kerangka — ia dipasang di `app.tsx` dan
 * `toast()` datang dari paket `sonner` langsung, jadi tak ada yang perlu
 * digandakan di sini.
 */
function FlashToast() {
    const { flash } = usePage<PageProps>().props;

    useEffect(() => {
        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
    }, [flash.success, flash.error]);

    return null;
}
