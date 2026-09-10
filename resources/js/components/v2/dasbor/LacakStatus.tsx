import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';
import type { CSSProperties } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { ScrollArea } from '@/components/ui-maia/scroll-area';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Progress } from '@/components/ui-maia/progress';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui-maia/table';
import { Avatar } from '@/components/v2/Avatar';
import { LencanaDelta } from '@/components/v2/dasbor/LencanaDelta';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type { BarisMatriks, BarisMeja } from '@/types/dasbor';

/**
 * "Lacak Status Dokumen" (§5b) — pengganti `PerluTindakan`.
 *
 * Susunannya: empat angka status berbatang → tabel dokumen yang berjalan →
 * baris ringkas jumlah. Ketiga lapisannya menjawab pertanyaan yang berbeda:
 * seberapa banyak yang sedang berjalan, mana yang menunggu SAYA, dan ke mana
 * melihat sisanya.
 *
 * TIGA sumber data, dan dua di antaranya sudah dikirim server sejak lama tanpa
 * pernah digambar sekali pun (D11 §8):
 *
 *  • `matrix` — 7 status × 6 jenis, sudah disaring hak akses di server
 *    (`DashboardController::index`, closure `$visible()` yang sama dengan
 *    `$stats` dan `$sebaran`). Empat baris di antaranya jadi angka berbatang.
 *  • `menungguDiMeja` — empat baris tabelnya.
 *  • `daftarTahap` / `ke` / `persen` di tiap baris meja — jadi pita kemajuan di
 *    kolom ketiga. Nol query tambahan.
 *
 * ## Tabelnya memakai desain V1, dan itu keputusan pemilik (REVISI-UI-V3 §4.3)
 *
 * Versi sebelumnya memakai `v2/DataTable` dengan kolom
 * `Dokumen · Menunggu · Umur · Status` plus baris bentangan berisi tahapan.
 * Pemilik memilih kembali ke bentuk `components/dasbor/KartuPerjalanan.tsx`:
 * empat kolom `Pembuat · Dokumen · Kemajuan · Status`, dengan WAJAH pembuatnya
 * di kolom pertama. Alasannya terbaca dari kartunya sendiri — pertanyaan yang
 * dibawa orang ke widget ini adalah "punya siapa, sampai mana", dan nama tanpa
 * wajah menjawab setengahnya.
 *
 * Konsekuensi yang disengaja:
 *
 *  • **`v2/DataTable` tidak dipakai di sini.** Ia arketipe untuk daftar
 *    BERPAGINASI yang diurutkan server; empat baris tetap tanpa paginator tak
 *    butuh `thead` lengket, `JudulUrut`, maupun `perluas()`.
 *  • **Baris bentangan hilang, datanya TIDAK.** `daftarTahap`/`ke`/`persen`
 *    sekarang tampil PERMANEN di kolom Kemajuan, jadi tahapan tak lagi perlu
 *    diklik untuk dilihat. `sejak` tetap terpakai di tab "Ditugaskan" milik
 *    `KartuKetersediaan`.
 *  • **`table-fixed` + lebar per kolom** — itulah yang membuat tabelnya
 *    mustahil melebihi kartunya betapapun panjang judul dokumennya, jadi tak
 *    ada gulir mendatar di dalam kartu dashboard.
 *
 * Di atas pita: MENUNGGU APA, bukan persen. Persennya tak pernah menjawab
 * pertanyaan siapa pun — panjang pita sudah mengatakan hal yang sama, sedangkan
 * "menunggu persetujuan SH" langsung memberi tahu apa yang harus terjadi
 * berikutnya.
 *
 * TIGA elemen referensi yang sengaja TIDAK diambil (§5b):
 *  • Tombol `Export` → jadi "Lihat semua". Tak ada endpoint ekspor massal, dan
 *    `DocumentExportController` masuk daftar HARAM CLAUDE.md §14b.
 *  • Kotak `Filter…` + `Columns` → dibuang. Kendali penyaring di atas EMPAT
 *    baris adalah hiasan.
 *  • `Previous`/`Next` → jadi hitungan + tautan ke daftar yang memang
 *    berpaginasi sungguhan.
 *
 * Batasnya tetap 4 baris (§P1) — dipatok controller, dan `DashboardWidgetTest`
 * menguncinya. Angka totalnya tetap jujur karena dijumlahkan dari `matrix`,
 * bukan dari panjang koleksi yang sudah dipotong.
 *
 * LENCANA di baris angka (F2 DASBOR-V4, D1) mengukur ALIRAN MASUK 30 hari —
 * BUKAN perubahan potret `matrix` (larangan §5b DASBOR-V2 tetap berlaku).
 * `aliranKet` di bawah tiap angka menyebut nama alirannya ("diloloskan 30
 * hari") supaya lencananya tak terbaca sebagai "draft bertambah 5%".
 */

/** Empat status yang ditampilkan berbatang, urut mengikuti perjalanan dokumen. */
const DIPANTAU = ['draft', 'in_review', 'pending_approval', 'rejected'];

/** Status yang dihitung sebagai "sedang berjalan" di baris kaki. */
const BERJALAN = ['draft', 'in_review', 'verifikasi_md', 'pending_approval', 'rejected', 'sedang_direvisi'];

/**
 * Umur dokumen menghangat sendiri: <3 hari sejuk, 3–6 hangat, ≥7 merah.
 * Ambangnya dihitung SERVER (`menungguDiMeja[].tingkat`), tak diulang di sini.
 */
const RONA_UMUR: Record<BarisMeja['tingkat'], string> = {
    baru: 'border-chart-2/40 bg-chart-2/10 text-chart-2',
    sedang: 'border-chart-3/40 bg-chart-3/10 text-chart-3',
    lama: 'border-destructive/40 bg-destructive/10 text-destructive',
};

const TEKS_UMUR: Record<BarisMeja['tingkat'], string> = {
    baru: 'text-chart-2',
    sedang: 'text-chart-3',
    lama: 'text-destructive',
};

export function LacakStatus({
    baris,
    matrix,
    urlSemua,
}: {
    baris: BarisMeja[];
    matrix: BarisMatriks[];
    urlSemua: string;
}) {
    const { statusMeta } = usePage<PageProps>().props;

    const per = Object.fromEntries(matrix.map((m) => [m.status, m]));
    const totalBerjalan = BERJALAN.reduce((n, s) => n + (per[s]?.total ?? 0), 0);
    // Pembagi batangnya total SELURUH matrix, bukan total yang berjalan: kalau
    // pembaginya ikut menyusut, "Draft: 3" akan tampil sebagai batang penuh di
    // hari yang kebetulan sepi.
    const totalSemua = matrix.reduce((n, m) => n + m.total, 0);

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Lacak Status Dokumen</CardTitle>
                <CardDescription>
                    {baris.length > 0
                        ? 'Yang menunggu tindakan Anda, beserta berapa lama ia diam'
                        : 'Tak ada dokumen yang menunggu tindakan Anda'}
                </CardDescription>
                <CardAction>
                    <Button asChild variant="outline" size="sm">
                        <Link href={urlSemua}>
                            Lihat semua
                            <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>

            <CardContent className="flex min-h-0 flex-1 flex-col gap-4">
                <div className="@2xl/card:grid-cols-4 grid grid-cols-2 gap-4">
                    {DIPANTAU.map((s) => (
                        <AngkaStatus key={s} baris={per[s]} warna={statusMeta[s]?.[0]} total={totalSemua} />
                    ))}
                </div>

                {baris.length === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-hourglass-split" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Tak ada dokumen yang menunggu Anda</EmptyTitle>
                            <EmptyDescription>
                                Dokumen yang sudah dikirim muncul di sini beserta umurnya.
                            </EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    /* Tabelnya DIGULIR di dalam kartu, tingginya bukan
                       tingginya isi.

                       Polanya sama dengan `KartuDistribusi` dan lahir dari
                       kegagalan yang sama: kartu ini berbagi baris grid dengan
                       `KartuKetersediaan`, dan selama tabelnya digambar penuh
                       ia menarik tinggi baris ke bawah tanpa batas — makin
                       banyak dokumen berjalan, makin panjang halamannya.

                       Kotak absolutnya WAJIB `div` biasa. Memberi `absolute`
                       langsung ke `ScrollArea` tidak bekerja:
                       `ui-maia/scroll-area.tsx` merakit kelasnya sebagai
                       `cn("relative", className)`, dan dua kelas `position`
                       pada satu elemen dimenangkan urutan stylesheet — di
                       Tailwind v4 `relative` yang menang, `inset-0` mati, dan
                       gulirnya tak pernah aktif.

                       `inset-0` mengukur dari kotak BORDER `CardContent`, jadi
                       tabelnya otomatis membentang selebar kartu dan `-mx-6`
                       yang dulu dipakai untuk itu tak diperlukan lagi. */
                    <div className="relative min-h-[12rem] flex-1">
                        <div className="absolute inset-0">
                            <ScrollArea className="h-full">
                        <Table className="table-fixed">
                            <TableHeader className="bg-card sticky top-0 z-10">
                                <TableRow>
                                    <TableHead className="w-[27%] pl-6">Pembuat</TableHead>
                                    <TableHead className="w-[28%]">Dokumen</TableHead>
                                    <TableHead className="w-[30%]">Kemajuan</TableHead>
                                    <TableHead className="w-[15%] pr-6">Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {baris.map((b) => (
                                    <TableRow key={b.dokumen.id} className="hover:bg-muted/40">
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-2">
                                                <Avatar
                                                    nama={b.pembuat.nama}
                                                    foto={b.pembuat.foto}
                                                    className="size-8 shrink-0"
                                                />
                                                <div className="min-w-0">
                                                    <div
                                                        className="truncate text-sm font-semibold"
                                                        title={b.pembuat.nama}
                                                    >
                                                        {b.pembuat.nama}
                                                    </div>
                                                    <div className="text-muted-foreground truncate text-xs">
                                                        {b.pembuat.jabatan}
                                                        {b.dept ? ` · ${b.dept}` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Link
                                                href={b.tautan}
                                                className="hover:text-primary block truncate text-sm font-semibold"
                                                title={b.judul}
                                            >
                                                {b.judul}
                                            </Link>
                                            <span className="text-muted-foreground block truncate font-mono text-xs">
                                                {b.nomor}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="mb-1 flex items-center justify-between gap-2">
                                                <span
                                                    className={cn(
                                                        'truncate text-xs font-semibold',
                                                        TEKS_UMUR[b.tingkat],
                                                    )}
                                                    title={b.pemegang ? `${b.menunggu} · ${b.pemegang}` : b.menunggu}
                                                >
                                                    {b.menunggu}
                                                </span>
                                                <span className="text-muted-foreground shrink-0 text-xs tabular-nums">
                                                    {b.ke}/{b.daftarTahap.length}
                                                </span>
                                            </div>
                                            <Progress
                                                value={b.persen}
                                                className="h-1.5"
                                                title={`${b.daftarTahap.join(' → ')} · sekarang: ${b.tahap}`}
                                            />
                                        </TableCell>
                                        <TableCell className="pr-6">
                                            <Badge
                                                variant="outline"
                                                className={cn('font-medium', RONA_UMUR[b.tingkat])}
                                            >
                                                {b.umur < 1 ? 'hari ini' : `${b.umur} hari`}
                                            </Badge>
                                            {b.pemegang && (
                                                <span
                                                    className="text-muted-foreground mt-1 block truncate text-xs"
                                                    title={b.pemegang}
                                                >
                                                    {b.pemegang}
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                            </ScrollArea>
                        </div>
                    </div>
                )}

                <p className="text-muted-foreground mt-auto text-xs">
                    {baris.length} dari {totalBerjalan} dokumen berjalan ·{' '}
                    <Link href={urlSemua} className="hover:text-primary underline underline-offset-2">
                        lihat daftar lengkapnya
                    </Link>
                </p>
            </CardContent>
        </Card>
    );
}

/**
 * Satu angka status berbatang.
 *
 * Warnanya dari `statusMeta` (CLAUDE.md §4). Hex-nya masuk lewat CSS variable
 * dan bukan kelas Tailwind karena nilainya baru diketahui saat jalan; batang
 * `ui-maia/progress` diwarnai dengan menyasar `data-slot`-nya sendiri, jadi
 * berkas registry-nya tak perlu disentuh (CLAUDE.md §4). Status di luar peta
 * jatuh ke `--primary` — batang tak berwarna tampak seperti bug.
 */
function AngkaStatus({ baris, warna, total }: { baris?: BarisMatriks; warna?: string; total: number }) {
    const nilai = baris?.total ?? 0;
    const rona = warna ?? 'var(--primary)';

    return (
        <div className="min-w-0">
            <p className="text-3xl font-semibold tabular-nums">{nilai}</p>
            <div className="flex items-center gap-1.5">
                <p className="text-muted-foreground truncate text-xs">{baris?.label ?? '—'}</p>
                {baris && (
                    <LencanaDelta
                        arah={baris.arah ?? 'netral'}
                        delta={baris.delta}
                        deltaLabel={baris.deltaLabel}
                        polos
                        className="text-xs"
                    />
                )}
            </div>
            <Progress
                value={total > 0 ? (nilai / total) * 100 : 0}
                className="mt-1.5 h-2 rounded-full [&>[data-slot=progress-indicator]]:bg-(--rona)"
                style={
                    {
                        '--rona': rona,
                        backgroundColor: `color-mix(in oklab, ${rona} 18%, transparent)`,
                    } as CSSProperties
                }
            />
            {baris?.aliranKet && (
                <p className="text-muted-foreground mt-1 truncate text-[11px]">{baris.aliranKet}</p>
            )}
        </div>
    );
}
