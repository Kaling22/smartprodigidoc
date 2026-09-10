import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';
import type { ComponentProps } from 'react';

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui-maia/collapsible';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarGroup, SidebarGroupLabel, SidebarHeader, SidebarMenu,
    SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui-maia/sidebar';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui-maia/tooltip';
import { Ikon } from '@/components/v2/Ikon';
import { NavUser } from '@/components/v2/NavUser';
import type { MenuItem, PageProps } from '@/types';

/**
 * Sidebar V2 — penggambar pohon menu, BUKAN penentunya.
 *
 * Seluruh aturan "menu mana untuk peran apa" ada di
 * `App\Services\NavigasiSidebar` dan tiba lewat props `navigation`. Berkas ini
 * sengaja tidak memuat satu pun pemeriksaan izin: menyalin sembilan aturan
 * peran ke TSX dilarang CLAUDE.md §4, dan salinan kedua itulah yang dulu
 * membuat menu menyimpang diam-diam untuk sebagian peran.
 *
 * DUA perubahan atas versi nova, keduanya disengaja (REDESAIN-UI-V2 §7):
 *
 *  1. **`collapsible="icon"`, bukan `"offcanvas"`.** Versi lama HILANG TOTAL
 *     saat diciutkan, jadi menciutkannya berarti kehilangan navigasi. Rel ikon
 *     3rem tetap menyisakan ikon + tooltip, sehingga layar bisa dilebarkan
 *     untuk tabel dan wizard tanpa membayar dengan navigasi. CLAUDE.md §13
 *     menyebut `"offcanvas"` sebagai kerangka acuan — perubahan ini karena itu
 *     ikut merevisi §13 saat approve, dicatat di sini supaya tak lolos sebagai
 *     penyimpangan diam-diam.
 *
 *  2. **Item terkunci pakai `Tooltip` shadcn, bukan atribut `title`.** Tooltip
 *     bawaan peramban baru muncul sesudah ~1 detik menggantung dan tak terbaca
 *     pembaca layar — padahal isinya justru ALASAN kenapa menu itu mati, satu
 *     hal yang paling ingin diketahui orang saat melihatnya.
 *
 * Grup yang bisa dilipat SENGAJA tidak dikerjakan (keputusan D4): induk menu
 * sudah melipat, dan lapisan kedua berarti dua klik per tautan sementara
 * `item.active` dari server sudah membuka cabang yang benar.
 */
export function AppSidebar({ ...props }: ComponentProps<typeof Sidebar>) {
    const { navigation } = usePage<PageProps>().props;

    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {/* Tombol merek — susunan `app-sidebar.tsx` blok
                            dashboard-01 apa adanya, termasuk `p-1.5!`-nya.
                            Dua berkas logo, bukan satu yang difilter: tema bisa
                            ditukar tanpa memuat ulang halaman, jadi CSS yang
                            memilih — nol JS, nol kedipan. Logo BISA diklik →
                            home (CLAUDE.md §7).

                            PENYIMPANGAN dari blok (DASBOR-V2-REVISI §4d, D7):
                            tombolnya ditinggikan `h-8` → `h-16`. `SidebarMenuButton`
                            maia mematok `h-8`, jadi membesarkan `<img>` saja akan
                            MEMOTONGNYA, bukan membesarkannya. Angkanya naik DUA KALI:
                            `h-12`/`h-9` Fase 2 masih dinilai terlalu kecil pada
                            pemeriksaan mata — logonya wordmark melebar, jadi tinggi
                            9 satuan menyisakan huruf yang nyaris tak terbaca di
                            sidebar selebar 18rem. Di rel ikon keduanya dikecilkan
                            lagi (`h-8` / `h-6`) supaya merek tak melebar dari rel
                            3rem dan mendorong seluruh isinya. */}
                        <SidebarMenuButton
                            asChild
                            tooltip="SmartPro"
                            className="h-16 group-data-[collapsible=icon]:h-8 data-[slot=sidebar-menu-button]:p-1.5!"
                        >
                            <Link href="/dashboard">
                                <img
                                    src="/images/logo-web.png"
                                    alt="SmartPro"
                                    className="h-12 w-auto object-contain group-data-[collapsible=icon]:h-6 dark:hidden"
                                />
                                <img
                                    src="/images/logodarkmode.png"
                                    alt="SmartPro"
                                    className="hidden h-12 w-auto object-contain group-data-[collapsible=icon]:h-6 dark:block"
                                />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navigation.map((bagian, i) => (
                    <SidebarGroup key={i}>
                        {bagian.label ? <SidebarGroupLabel>{bagian.label}</SidebarGroupLabel> : null}
                        <SidebarMenu>
                            {bagian.items.map((item) => (
                                <Baris key={item.label} item={item} />
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

function Baris({ item }: { item: MenuItem }) {
    if (item.items) {
        return <Grup item={item} />;
    }

    return (
        <SidebarMenuItem>
            {item.locked ? <Terkunci item={item} /> : <Tautan item={item} />}
            {item.badge ? <SidebarMenuBadge>{item.badge}</SidebarMenuBadge> : null}
        </SidebarMenuItem>
    );
}

/**
 * Menu induk. Terbuka sejak awal bila salah satu isinya sedang aktif —
 * keadaan awalnya datang dari server (`item.active`), bukan ditebak di klien.
 */
function Grup({ item }: { item: MenuItem }) {
    return (
        <Collapsible asChild defaultOpen={item.active} className="group/collapsible">
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={item.label} isActive={item.active}>
                        <Ikon nama={item.icon} className="size-4" />
                        <span>{item.label}</span>
                        {/* Titik penanda: ada pekerjaan di salah satu sub-menu.
                            Angkanya tetap milik sub-menu — induk cukup memberi
                            tahu bahwa ada sesuatu di balik menu yang tertutup. */}
                        {item.dot ? (
                            <span
                                className="bg-primary size-1.5 shrink-0 rounded-full"
                                title="Ada yang perlu dikerjakan"
                            />
                        ) : null}
                        <ChevronLipat />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {item.items?.map((anak) => (
                            <SidebarMenuSubItem key={anak.label}>
                                {anak.locked ? (
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <SidebarMenuSubButton asChild className="cursor-not-allowed opacity-50">
                                                {/* <span> tanpa href mustahil diklik
                                                    MAUPUN di-Tab. */}
                                                <span>
                                                    <Ikon nama={anak.icon} className="size-4" />
                                                    <span>{anak.label}</span>
                                                    <Ikon nama="bi-lock-fill" className="ml-auto size-3" />
                                                </span>
                                            </SidebarMenuSubButton>
                                        </TooltipTrigger>
                                        <TooltipContent side="right">{anak.title ?? anak.label}</TooltipContent>
                                    </Tooltip>
                                ) : (
                                    <SidebarMenuSubButton asChild isActive={anak.active}>
                                        <Link href={anak.href!} title={anak.title ?? undefined}>
                                            <Ikon nama={anak.icon} className="size-4" />
                                            <span>{anak.label}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                )}
                                {/* `top-1` WAJIB. `SidebarMenuBadge` registry menaruh `top`-nya lewat
                                    `peer-data-[size=*]/menu-button:top-*`, dan `peer/menu-button` hanya
                                    ada di `SidebarMenuButton` INDUK — `SidebarMenuSubButton` tak
                                    punya. Tanpa ini `top` tak pernah teratur, lencananya jatuh ke
                                    posisi statis DI BAWAH barisnya sendiri, dan angka "Masukan
                                    Lapangan" terbaca sebagai angka "Log Pesan". Sub-button `h-7`,
                                    lencana `h-5` → (28-20)/2 = 4px. */}
                                {anak.badge ? <SidebarMenuBadge className="top-1">{anak.badge}</SidebarMenuBadge> : null}
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}

/**
 * Caret pelipat.
 *
 * Diimpor LANGSUNG dari hugeicons, tidak lewat `v2/Ikon.tsx`: peta di sana
 * hanya memuat kunci `bi-*` yang benar-benar dikirim server, dan caret ini
 * kendali antarmuka murni yang tak pernah punya nama `bi-*`. Menambahkannya ke
 * peta berarti menaruh kunci karangan di daftar yang justru harus cerminan
 * konstanta PHP.
 */
function ChevronLipat() {
    return (
        <HugeiconsIcon
            icon={ArrowRight01Icon}
            strokeWidth={1.5}
            aria-hidden="true"
            className="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
        />
    );
}

function Tautan({ item }: { item: MenuItem }) {
    return (
        <SidebarMenuButton asChild tooltip={item.label} isActive={item.active}>
            <Link href={item.href!} title={item.title ?? undefined}>
                <Ikon nama={item.icon} className="size-4" />
                <span>{item.label}</span>
            </Link>
        </SidebarMenuButton>
    );
}

/**
 * Menu di luar profil akses / ditutup Admin: tampil, tapi mustahil diklik.
 *
 * Tooltipnya berisi `item.title` DARI SERVER — alasan terkuncinya, yang
 * merupakan satu-satunya hal yang berguna diketahui saat melihat menu mati.
 */
function Terkunci({ item }: { item: MenuItem }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <SidebarMenuButton asChild className="cursor-not-allowed opacity-50">
                    <span>
                        <Ikon nama={item.icon} className="size-4" />
                        <span>{item.label}</span>
                        <Ikon nama="bi-lock-fill" className="ml-auto size-3" />
                    </span>
                </SidebarMenuButton>
            </TooltipTrigger>
            <TooltipContent side="right">{item.title ?? item.label}</TooltipContent>
        </Tooltip>
    );
}
