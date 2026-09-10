import { Link, router } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import {
    Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle,
} from '@/components/ui-maia/item';
import { Paginasi } from '@/components/v2/DataTable';
import { Ikon } from '@/components/v2/Ikon';
import { PenyaringDokumen } from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { NotificationsIndexProps, NotifikasiBaris } from '@/types/notifikasi';

/**
 * Halaman Notifikasi penuh (F7 DASBOR-V4, gambar 7) — lahir V2-only (D4):
 * tak punya kembaran V1, jadi `PratinjauResponseFactory` dilewati begitu saja
 * (komponen literal `V2/Notifications/Index` tak pernah cocok pola prefiksnya).
 *
 * Tombol aksi tiap baris HANYA SATU tautan ke layar kerjanya (`tautan`,
 * `DocumentNotification::urlUntuk()`) — bukan dua tombol Setujui/Tolak
 * langsung dari daftar (D5): menolak wajib beralasan, menyetujui mengunci
 * nomor final, dan keduanya tak boleh diambil tanpa membuka dokumennya.
 */
export default function NotificationsIndex({
    notifikasi,
    filters,
    kategoriOpsi,
    belumDibaca,
}: NotificationsIndexProps) {
    return (
        <AppLayout
            judul="Notifikasi"
            sub="Ikuti perkembangan dokumen yang menyangkut Anda."
            aksi={
                belumDibaca > 0 ? (
                    <Button
                        variant="outline"
                        onClick={() => router.post(route('notifications.readAll'), {}, { preserveScroll: true })}
                    >
                        <Ikon nama="bi-check-circle" className="size-4" />
                        Tandai Semua Dibaca
                    </Button>
                ) : null
            }
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('notifications.index')}
                        filters={filters}
                        contoh="perlu ditinjau"
                        labelCari="Cari notifikasi"
                        placeholderCari="Cari notifikasi…"
                        pilihan={[
                            {
                                nama: 'status',
                                label: 'Status',
                                opsi: [
                                    ['', 'Semua'],
                                    ['belum', 'Belum dibaca'],
                                    ['sudah', 'Sudah dibaca'],
                                ],
                            },
                            {
                                nama: 'kategori',
                                label: 'Kategori',
                                opsi: [['', 'Semua'], ...kategoriOpsi.map((k): [string, string] => [k, k])],
                            },
                        ]}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    {notifikasi.data.length === 0 ? (
                        <Empty className="border-0">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <Ikon nama="bi-bell" className="size-6" />
                                </EmptyMedia>
                                <EmptyTitle>Belum ada notifikasi</EmptyTitle>
                                <EmptyDescription>Coba longgarkan penyaring.</EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    ) : (
                        <ItemGroup className="gap-0 px-4">
                            {notifikasi.data.map((n, i) => (
                                <Baris key={n.id} n={n} terakhir={i === notifikasi.data.length - 1} />
                            ))}
                        </ItemGroup>
                    )}
                </CardContent>

                {notifikasi.data.length > 0 ? (
                    <div className="border-t px-6 py-4">
                        <Paginasi paginator={notifikasi} />
                    </div>
                ) : null}
            </Card>
        </AppLayout>
    );
}

function Baris({ n, terakhir }: { n: NotifikasiBaris; terakhir: boolean }) {
    return (
        <Item asChild className={terakhir ? '' : 'border-b'}>
            <Link href={route('notifications.open', n.id)}>
                <ItemMedia variant="icon">
                    <Ikon nama={n.ikon} className="size-4" />
                </ItemMedia>
                <ItemContent>
                    <ItemTitle>
                        {n.judul}
                        {!n.dibaca ? (
                            <span aria-hidden="true" className="bg-primary size-2 shrink-0 rounded-full" />
                        ) : null}
                    </ItemTitle>
                    <ItemDescription>{n.pesan}</ItemDescription>
                </ItemContent>
                <ItemActions>
                    <Badge variant="secondary">{n.kategori}</Badge>
                    {n.waktu ? (
                        <span className="text-muted-foreground text-xs whitespace-nowrap">{n.waktu}</span>
                    ) : null}
                </ItemActions>
            </Link>
        </Item>
    );
}
