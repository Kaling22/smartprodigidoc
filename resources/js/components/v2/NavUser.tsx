import { Link, router, usePage } from '@inertiajs/react';

import {
    DropdownMenu, DropdownMenuContent, DropdownMenuGroup, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui-maia/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui-maia/sidebar';
import { Avatar } from '@/components/v2/Avatar';
import { Ikon } from '@/components/v2/Ikon';
import type { PageProps } from '@/types';

/**
 * Kartu pengguna di kaki sidebar — kembaran maia dari `components/NavUser.tsx`.
 *
 * Tetap di `SidebarFooter`, BUKAN di header: itu aturan `dashboard-01`, dan
 * memindahkannya ke topbar berarti dua tempat yang harus disepakati saat
 * menu penggunanya berubah.
 *
 * Baris kedua adalah LABEL JABATAN, bukan email — kunci mentah `jabatan` tak
 * pernah dicetak ke layar (CLAUDE.md §6).
 *
 * Saat sidebar menciut jadi rel ikon, `SidebarMenuButton` menyembunyikan
 * sendiri anak `<span>`-nya, jadi yang tersisa avatarnya saja. Tak ada cabang
 * `isCollapsed` di sini — komponennya sudah mengurus itu.
 */
export function NavUser() {
    const { isMobile } = useSidebar();
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;

    if (!user) {
        return null;
    }

    const jabatan = user.jabatan_label || auth.roles[0]?.replaceAll('_', ' ') || '-';

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={user.name}
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <Avatar nama={user.name} foto={user.photo_url} className="size-8" />
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{user.name}</span>
                                <span className="text-muted-foreground truncate text-xs">{jabatan}</span>
                            </div>
                            <Ikon nama="bi-sliders" className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-2xl"
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar nama={user.name} foto={user.photo_url} className="size-8" />
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-medium">{user.name}</span>
                                    <span className="text-muted-foreground truncate text-xs">{user.nrp}</span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            <DropdownMenuItem asChild>
                                <Link href={route('account.info')}>
                                    <Ikon nama="bi-person-badge" className="size-4" />
                                    Informasi Akun
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem variant="destructive" onSelect={() => router.post(route('logout'))}>
                            <Ikon nama="bi-box-arrow-right" className="size-4" />
                            Logout
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
