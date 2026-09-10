import { Link } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle } from '@/components/ui-maia/item';
import { Progress } from '@/components/ui-maia/progress';
import { Avatar } from '@/components/v2/Avatar';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { BarisMeja } from '@/types/dasbor';

/**
 * "Perlu Tindakan Anda" — bekas `KartuPerjalanan`, naik ke baris PERTAMA.
 *
 * Kenapa dipindahkan (REDESAIN-UI-V2 §8): antrean kerja adalah satu-satunya
 * hal yang benar-benar ditunggu pengguna saat membuka aplikasi ini, dan di
 * dasbor lama ia duduk di baris KEEMPAT — di bawah kartu sapaan, dua grafik,
 * dan empat kartu angka. Urutan halaman sekarang mengikuti pertanyaan yang
 * memang dibawa orang: apa yang menunggu saya → seberapa lancar arusnya → apa
 * yang terjadi.
 *
 * Isinya tak berubah sedikit pun, termasuk yang paling mudah hilang saat
 * dipindah:
 *
 *  • Umur dokumen jadi lencana yang menghangat sendiri — <3 hari, 3–6, ≥7 hari.
 *    Ambangnya dihitung SERVER (`baris[].tingkat`), tak pernah diulang di sini.
 *  • Di atas pita: MENUNGGU APA, bukan persen. Persen tak menjawab pertanyaan
 *    siapa pun — panjang pita sudah mengatakan hal yang sama, sedangkan
 *    "menunggu persetujuan SH" langsung memberi tahu apa yang harus terjadi
 *    berikutnya.
 *  • TEPAT 4 baris (dibatasi controller).
 *
 * Bentuknya berubah dari `Table table-fixed` jadi `Item`: tabel empat kolom
 * memaksa lebar tiap kolom dipatok supaya judul panjang tak melebarkan kartu,
 * dan patokan itu menyisakan ruang kosong di baris berjudul pendek. `Item`
 * mengalir — nomor + judul di kiri, kemajuan di tengah, tombol di kanan.
 */
const RONA: Record<BarisMeja['tingkat'], string> = {
    baru: 'border-chart-2/40 bg-chart-2/10 text-chart-2',
    sedang: 'border-chart-3/40 bg-chart-3/10 text-chart-3',
    lama: 'border-destructive/40 bg-destructive/10 text-destructive',
};

const TEKS: Record<BarisMeja['tingkat'], string> = {
    baru: 'text-chart-2',
    sedang: 'text-chart-3',
    lama: 'text-destructive',
};

export function PerluTindakan({ baris }: { baris: BarisMeja[] }) {
    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader>
                <CardTitle>Perlu Tindakan Anda</CardTitle>
                <CardDescription>
                    {baris.length > 0
                        ? 'Dokumen yang sedang berjalan, beserta berapa lama ia diam'
                        : 'Tak ada dokumen yang sedang berjalan'}
                </CardDescription>
                {baris.length > 0 ? (
                    <CardAction>
                        <Badge variant="secondary">{baris.length}</Badge>
                    </CardAction>
                ) : null}
            </CardHeader>

            <CardContent className="flex-1">
                {baris.length === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-hourglass-split" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Tak ada dokumen yang sedang berjalan</EmptyTitle>
                            <EmptyDescription>
                                Dokumen yang sudah dikirim muncul di sini beserta umurnya.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ItemGroup className="gap-2">
                        {baris.map((b) => (
                            <Item key={b.dokumen.id} variant="outline" className="items-start">
                                <ItemMedia>
                                    <Avatar
                                        nama={b.pembuat.nama}
                                        foto={b.pembuat.foto}
                                        className="size-9"
                                    />
                                </ItemMedia>

                                <ItemContent className="gap-1">
                                    <ItemTitle className="flex-wrap">
                                        <Link href={b.tautan} className="hover:text-primary truncate">
                                            {b.judul}
                                        </Link>
                                    </ItemTitle>
                                    <ItemDescription className="font-mono">{b.nomor}</ItemDescription>

                                    <div className="mt-1 flex items-center gap-2">
                                        <span className={cn('truncate text-xs font-semibold', TEKS[b.tingkat])}>
                                            {b.menunggu}
                                        </span>
                                        <span className="text-muted-foreground shrink-0 text-xs tabular-nums">
                                            {b.ke}/{b.daftarTahap.length}
                                        </span>
                                    </div>
                                    <Progress
                                        value={b.persen}
                                        className="h-1"
                                        title={`${b.daftarTahap.join(' → ')} · sekarang: ${b.tahap}`}
                                    />
                                    <p className="text-muted-foreground truncate text-xs">
                                        {b.pembuat.nama} · {b.pembuat.jabatan}
                                        {b.dept ? ` · ${b.dept}` : ''}
                                        {b.pemegang ? ` — di meja ${b.pemegang}` : ''}
                                    </p>
                                </ItemContent>

                                <ItemActions className="flex-col items-end gap-2">
                                    <Badge variant="outline" className={cn('font-medium', RONA[b.tingkat])}>
                                        {b.umur < 1 ? 'hari ini' : `${b.umur} hari`}
                                    </Badge>
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={b.tautan}>Buka</Link>
                                    </Button>
                                </ItemActions>
                            </Item>
                        ))}
                    </ItemGroup>
                )}
            </CardContent>
        </Card>
    );
}
