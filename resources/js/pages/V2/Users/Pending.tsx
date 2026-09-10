import { Cancel01Icon, CheckmarkCircle02Icon, Tick02Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import {
    Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, type Kolom } from '@/components/v2/DataTable';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { BarisAdmin } from '@/types/pengguna';

/**
 * "Persetujuan Akun" — kembaran V2 `pages/Users/Pending.tsx`.
 *
 * Siapa yang terlihat di sini ditentukan `User::scopePendingVisibleTo()`: Admin
 * IT & PJO melihat SELURUH departemen, sisanya hanya departemennya sendiri.
 * Nol penyaringan di klien — dan `approve()`/`reject()` mengulang penjagaan itu
 * di server (P4).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — tabel pindah ke `CardContent className="px-0"` (arketipe
 *    `V2/Documents/Revisions.tsx`); daftarnya tak berhalaman, jadi tanpa
 *    `CardFooter`. Sel aksi kehilangan `flex-wrap`.
 *  · Kedua tombolnya sengaja TETAP berdiri di baris, bukan masuk `StripAksi`:
 *    Setujui/Tolak adalah satu-satunya alasan layar ini dibuka, dan
 *    menguburnya di menu berbiaya satu klik tambahan pada setiap baris.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function UsersPending({ pendingUsers }: { pendingUsers: BarisAdmin[] }) {
    const kolom: Kolom<BarisAdmin>[] = [
        { judul: 'Nama', render: (u) => <span className="font-medium">{u.name}</span> },
        { judul: 'NRP', render: (u) => <span className="font-mono text-sm">{u.nrp ?? '—'}</span> },
        {
            /* Jabatan yang DIKETIK pendaftar (mis. "Magang"); di bawahnya hak
               akses yang akan ia dapat, agar penyetuju tahu keduanya. Bila tak
               diisi, cukup labelnya — bukan kunci internal "staff". */
            judul: 'Jabatan',
            render: (u) =>
                u.jabatan_diajukan ? (
                    <div>
                        <div>{u.jabatan_diajukan}</div>
                        <div className="text-muted-foreground text-xs">
                            Akses: {u.jabatan_label}
                        </div>
                    </div>
                ) : (
                    (u.jabatan_label || '—')
                ),
        },
        { judul: 'Departemen', render: (u) => <Badge variant="outline">{u.dept ?? '—'}</Badge> },
        {
            judul: 'Email',
            render: (u) => <span className="text-muted-foreground text-sm">{u.email ?? '—'}</span>,
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (u) => (
                <div className="flex items-center justify-end gap-2">
                    <ConfirmDialog
                        judul="Setujui Akun?"
                        pesan={`Setujui pendaftaran akun ${u.name}?`}
                        tombolYa="Ya, setujui"
                        onKonfirmasi={() =>
                            router.post(route('users.approve', u.id), {}, { preserveScroll: true })
                        }
                        pemicu={
                            <Button size="sm">
                                <HugeiconsIcon
                                    icon={Tick02Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Setujui
                            </Button>
                        }
                    />
                    <ConfirmDialog
                        judul="Tolak Pendaftaran?"
                        pesan={`Tolak pendaftaran ${u.name}?`}
                        tombolYa="Ya, tolak"
                        destruktif
                        onKonfirmasi={() =>
                            router.post(route('users.reject', u.id), {}, { preserveScroll: true })
                        }
                        pemicu={
                            <Button variant="outline" size="sm">
                                <HugeiconsIcon
                                    icon={Cancel01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Tolak
                            </Button>
                        }
                    />
                </div>
            ),
        },
    ];

    return (
        <AppLayout judul="Persetujuan Akun">
            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Persetujuan Akun</CardTitle>
                    <CardDescription>
                        Setujui atau tolak pendaftaran User Departemen.
                    </CardDescription>
                    <CardAction>
                        <Badge variant="secondary">{pendingUsers.length} menunggu</Badge>
                    </CardAction>
                </CardHeader>
                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={pendingUsers}
                        kunci={(u) => u.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon
                                            icon={CheckmarkCircle02Icon}
                                            strokeWidth={1.5}
                                            className="size-6"
                                        />
                                    </EmptyMedia>
                                    <EmptyTitle>
                                        Tidak ada akun yang menunggu persetujuan.
                                    </EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
