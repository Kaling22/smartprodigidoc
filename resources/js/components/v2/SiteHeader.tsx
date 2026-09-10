import { Moon02Icon, Sun03Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';
import { useTheme } from 'next-themes';
import { Fragment } from 'react';

import {
    Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui-maia/breadcrumb';
import { Button } from '@/components/ui-maia/button';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui-maia/dropdown-menu';
import { Separator } from '@/components/ui-maia/separator';
import { SidebarTrigger } from '@/components/ui-maia/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui-maia/tooltip';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

export interface Remah {
    label: string;
    href?: string;
}

/**
 * Topbar V2 — kerangka `site-header.tsx` blok `dashboard-01` (tinggi
 * `h-(--header-height)`, `border-b`, talang dalam `px-4 lg:gap-2 lg:px-6`).
 *
 * Dua kendali di kanan; keduanya memang tak punya padanan di blok dan
 * alasannya ditulis di masing-masing: lonceng dan toggle tema. Sakelar
 * pratinjau DICABUT 2026-09-07 — V1 mati, jadi tak ada lagi yang bisa ditukar
 * (berkas `v2/SakelarPratinjau.tsx` sengaja ditahan, lihat CLAUDE.md §4).
 *
 * PEMICU ⌘K DICABUT (REVISI-UI-V3 §4.5, keputusan pemilik 2026-09-01: "palet
 * pencarian di dashboard hapus, ga guna"). Ia dulu kendali keempat — kotak cari
 * palsu selebar 14rem di layar lebar, ikon di layar sempit. Berkas
 * `v2/PencarianMenu.tsx` SENGAJA masih ada di disk dan `cmdk` masih terpasang:
 * penghapusan berkas butuh konfirmasi eksplisit (CLAUDE.md §4), dan pemilik
 * memilih menahannya — perlakuan yang sama dengan trio K3 di `v2/dasbor/`.
 * Ia kini nol pengimpor.
 *
 * Menu pengguna TIDAK di sini: di `dashboard-01` tempatnya `SidebarFooter`.
 * Lihat `v2/NavUser.tsx`.
 *
 * JUDUL HALAMAN kembali ke topbar (keputusan pemilik 2026-08-31, pemeriksaan
 * mata sesudah Fase 2) — dan itu justru MENGEMBALIKANNYA ke blok: `site-header.tsx`
 * `dashboard-01` memang membuka dengan `<h1 className="text-base font-medium">`
 * tepat di sebelah `SidebarTrigger`. Percobaan Fase 2 memindahkannya ke badan
 * halaman sebagai H1 besar; pemilik menilainya salah tempat.
 *
 * Halaman ber-REMAH menggambar remahnya, bukan judulnya — remah sudah berakhir
 * pada halaman yang sedang dibuka, jadi mencetak keduanya berarti mencetak nama
 * yang sama dua kali dalam satu baris. Judulnya tetap hidup sebagai H1 `sr-only`
 * di sana: tiap halaman WAJIB punya tepat satu H1, dan tak boleh ada halaman
 * yang kehilangan lompatan judulnya cuma karena kebetulan punya breadcrumb.
 */
export function SiteHeader({ judul, remah = [] }: { judul: string; remah?: Remah[] }) {
    return (
        <header className="flex h-(--header-height) shrink-0 items-center gap-2 border-b transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-(--header-height)">
            <div className="flex w-full items-center gap-1 px-4 lg:gap-2 lg:px-6">
                <SidebarTrigger className="-ml-1" />
                <Separator orientation="vertical" className="mx-2 data-[orientation=vertical]:h-4" />

                {remah.length > 0 ? (
                    <>
                        <h1 className="sr-only">{judul}</h1>
                        <Breadcrumb>
                            <BreadcrumbList>
                                {remah.map((r, i) => (
                                    <Fragment key={i}>
                                        {i > 0 ? <BreadcrumbSeparator /> : null}
                                        <BreadcrumbItem>
                                            {r.href && i < remah.length - 1 ? (
                                                <BreadcrumbLink asChild>
                                                    <Link href={r.href}>{r.label}</Link>
                                                </BreadcrumbLink>
                                            ) : (
                                                <BreadcrumbPage>{r.label}</BreadcrumbPage>
                                            )}
                                        </BreadcrumbItem>
                                    </Fragment>
                                ))}
                            </BreadcrumbList>
                        </Breadcrumb>
                    </>
                ) : (
                    <h1 className="truncate text-base font-medium">{judul}</h1>
                )}

                <div className="ml-auto flex items-center gap-1">
                    <Lonceng />
                    <TombolTema />
                </div>
            </div>
        </header>
    );
}

function TombolTema() {
    const { resolvedTheme, setTheme } = useTheme();

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Ganti tema"
                    onClick={() => setTheme(resolvedTheme === 'dark' ? 'light' : 'dark')}
                >
                    {/* Kedua glif ikut halaman dan CSS yang memilih — menggambar
                        berdasarkan `resolvedTheme` saja membuat ikonnya melompat
                        sekali saat React selesai memuat, karena tema baru
                        diketahui di klien. */}
                    <MatahariBulan />
                </Button>
            </TooltipTrigger>
            <TooltipContent>Ganti tema</TooltipContent>
        </Tooltip>
    );
}

/**
 * Matahari/bulan yang ditukar CSS, bukan JS.
 *
 * Diimpor LANGSUNG dari hugeicons, tidak lewat `v2/Ikon.tsx`: peta di sana
 * hanya memuat kunci `bi-*` yang benar-benar dikirim server, dan kendali
 * antarmuka seperti ini tak pernah punya nama `bi-*`. Menaruhnya di peta itu
 * berarti mengotori daftar yang justru harus cerminan konstanta PHP.
 */
function MatahariBulan() {
    return (
        <>
            <HugeiconsIcon icon={Sun03Icon} strokeWidth={1.5} aria-hidden="true" className="size-4 dark:hidden" />
            <HugeiconsIcon icon={Moon02Icon} strokeWidth={1.5} aria-hidden="true" className="hidden size-4 dark:block" />
        </>
    );
}

/**
 * Lonceng — perilaku Blade lama persis: 8 notifikasi terakhir, penanda belum
 * dibaca. Angka dibatasi "99+": tiga digit melebarkan lencana keluar kotak.
 *
 * "Tandai dibaca" DICABUT dari sini (F7 DASBOR-V4, gambar 6) — digantikan
 * tautan "Lihat semua" ke halaman Notifikasi penuh (`notifications.index`),
 * tempat tombol "Tandai Semua Dibaca" kini berumah (rute `notifications.readAll`
 * TAK berubah, hanya pemanggilnya pindah).
 *
 * Nol query di klien — isinya sudah ikut props global.
 */
function Lonceng() {
    const { notifications } = usePage<PageProps>().props;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative" aria-label="Notifikasi">
                    <Ikon nama="bi-bell" className="size-4" />
                    {notifications.unread > 0 ? (
                        <span className="bg-destructive text-primary-foreground absolute -top-0.5 -right-0.5 flex min-w-4 items-center justify-center rounded-full px-1 text-[10px] leading-4">
                            {notifications.unread > 99 ? '99+' : notifications.unread}
                        </span>
                    ) : null}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="max-h-[420px] w-[330px] overflow-auto rounded-2xl">
                <div className="flex items-center justify-between px-2 py-1.5">
                    <span className="text-sm font-medium">Notifikasi</span>
                    {/* Pil `--primary` PENUH dengan teks `--primary-foreground`
                        (revisi pemilik butir 3: "ganti warna tulisan lihat semua
                        menjadi warna putih").

                        `text-primary-foreground`, BUKAN `text-white` harfiah:
                        keduanya putih hari ini, tapi yang pertama ikut berpindah
                        sendiri bila merah PPA suatu hari diganti nada yang
                        menuntut teks gelap — sedangkan `text-white` diam saja dan
                        tulisannya hilang. Dua rupa sebelumnya sama-sama ditolak:
                        merah bergaris bawah (tipis), lalu pil merah muda samar
                        (kontrasnya kurang di tema gelap). */}
                    <Link
                        href={route('notifications.index')}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 rounded-md px-2.5 py-1 text-xs font-semibold transition-colors"
                    >
                        Lihat semua
                    </Link>
                </div>
                <DropdownMenuSeparator />
                {notifications.items.length === 0 ? (
                    <p className="text-muted-foreground py-6 text-center text-sm">Belum ada notifikasi</p>
                ) : (
                    /* Baris notifikasi DIBERI JARAK (revisi pemilik butir 3):
                       sebelumnya kedelapan butir menempel jadi satu blok abu-abu
                       dan batas antar-pesan hanya bisa ditebak dari huruf
                       kapitalnya. `gap-1.5` + `rounded-lg` per butir membuat tiap
                       pesan jadi kartunya sendiri.

                       Pembungkus `div` aman di dalam `DropdownMenuContent`:
                       navigasi papan-tik Radix memakai collection context, bukan
                       penelusuran anak langsung DOM. */
                    <div className="flex flex-col gap-1.5 p-1">
                        {notifications.items.map((n) => (
                            <DropdownMenuItem key={n.id} asChild>
                                <Link
                                    href={route('notifications.open', n.id)}
                                    className={cn(
                                        'flex items-start gap-2 rounded-lg px-2 py-2',
                                        !n.read_at && 'bg-muted font-medium',
                                    )}
                                >
                                    <Ikon nama={n.icon} className="mt-0.5 size-4 shrink-0" />
                                    <span className="flex-1 text-xs whitespace-normal">{n.message}</span>
                                    {!n.read_at ? (
                                        <span className="bg-muted-foreground mt-1 size-2 shrink-0 rounded-full" />
                                    ) : null}
                                </Link>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
