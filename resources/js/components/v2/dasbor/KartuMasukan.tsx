import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Item, ItemContent, ItemDescription, ItemGroup, ItemTitle } from '@/components/ui-maia/item';
import { ScrollArea } from '@/components/ui-maia/scroll-area';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { BarisMasukan } from '@/types/dasbor';

/**
 * Masukan lapangan (§5f) — digambar ulang di atas `ui-maia/item`, komponen
 * registry yang memang untuk bentuk "media · isi · tindakan".
 *
 * Isinya ucapan orang lapangan, jadi bentuknya tetap KUTIPAN, bukan baris
 * tabel. DUA WAJAH, dipilih dari jabatan pembacanya — keduanya wajib bertahan:
 *
 *   • SH/DH/GL/PJO — masukan yang BELUM ditindak. Tiap kutipan menaut ke
 *     halaman dokumen, tempat form balasan sudah ada — tak ada jalur tindakan
 *     baru yang dibuat di sini.
 *   • Non-Staff    — kiriman sendiri; balasan SH/DH muncul menjorok di
 *     bawahnya, membentuk percakapan dua giliran.
 *
 * TIGA keadaan, tiga tata letak — yang dijaga SAMA di ketiganya adalah DIMENSI
 * kartunya, yang berubah hanya isinya:
 *   0 kutipan   → keterangan kosong, dipusatkan di tengah kartu
 *   1–2 kutipan → SATU kolom; kalimatnya melebar mengisi kartu
 *   3+ kutipan  → dua kolom di kartu selebar 2/3, rapat ke atas, MENGGULUNG
 *
 * Menggulung, bukan memanjang: batas kutipan datang dari konfigurasi
 * (`smartpro.dashboard.masukan_widget`) dan bisa puluhan.
 *
 * TIGA hal yang datang JADI dari server, dan tak boleh dihitung ulang di sini:
 *
 *  • **`rona`** — titik status di kiri-atas. Kosakatanya (`maju`/`netral`/
 *    `baik`/`buruk`) SAMA PERSIS dengan `KartuLog` di sebelahnya, dan peta
 *    kelasnya sengaja dibuat sejajar: "hijau = kabar baik" harus berarti hal
 *    yang sama di dua kartu bersebelahan. Yang dipetakan `rona`, bukan
 *    `statusKunci` — menerjemahkan status mentah di sini berarti menyalin
 *    aturan yang sudah tinggal di `DashboardController::masukanBaris()`.
 *  • **`boleh_balas`** — jawaban `DocumentFeedback::bisaDibalasOleh()`. Ikon
 *    lingkaran-centang di kanan-atas cuma TAUTAN, bukan aksi baru: yang boleh
 *    membalas diantar ke `log.masukan` tempat form balasannya sudah berumah,
 *    sisanya ke halaman dokumennya. Tak ada form balas inline yang dibangun.
 *  • **`masukanTotal`** — angka kaki. Bukan `masukan.length`: koleksinya
 *    dipotong `config('smartpro.dashboard.masukan_widget')`, jadi panjangnya
 *    tak pernah jadi jumlah yang sebenarnya.
 */

/** Sejajar dengan peta `RONA` di `KartuLog` — satu kosakata, dua kartu. */
const RONA: Record<string, string> = {
    maju: 'bg-primary',
    netral: 'bg-muted-foreground',
    baik: 'bg-chart-5',
    buruk: 'bg-destructive',
};

export function KartuMasukan({
    masukan,
    total,
    nonStaff,
    urlLogMasukan,
    urlBerlaku,
}: {
    masukan: BarisMasukan[];
    total: number;
    nonStaff: boolean;
    urlLogMasukan: string;
    urlBerlaku: string;
}) {
    const jumlah = masukan.length;
    const sedikit = jumlah > 0 && jumlah < 3;
    const sisa = total - jumlah;

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">{nonStaff ? 'Masukan Saya' : 'Masukan Lapangan'}</CardTitle>
                <CardDescription className="flex items-center gap-1.5">
                    <Ikon nama="bi-chat-left-quote" className="size-3.5" />
                    {nonStaff ? 'kiriman Anda & balasannya' : 'belum ditindak'}
                </CardDescription>
                {/* SATU tombol di kanan-atas, wajahnya mengikuti `nonStaff`:
                    yang tak boleh menindak diantar ke tempat ia BOLEH bertindak
                    (mengirim masukan baru), bukan ke daftar yang akan menolaknya. */}
                <CardAction>
                    <Button asChild variant="outline" size="sm">
                        <Link href={nonStaff ? urlBerlaku : urlLogMasukan}>
                            {nonStaff ? 'Beri Masukan' : 'Lihat semua'}
                            <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                        </Link>
                    </Button>
                </CardAction>
            </CardHeader>

            {jumlah === 0 ? (
                <CardContent className="flex flex-1 items-center justify-center">
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-chat-left-dots" className="size-6" />
                            </EmptyMedia>
                            {nonStaff ? (
                                <>
                                    <EmptyTitle>Anda belum mengirim masukan</EmptyTitle>
                                    <EmptyDescription>
                                        Buka{' '}
                                        <Link href={urlBerlaku} className="underline underline-offset-2">
                                            Dokumen Berlaku
                                        </Link>{' '}
                                        lalu pilih Beri Masukan.
                                    </EmptyDescription>
                                </>
                            ) : (
                                <>
                                    <EmptyTitle>Tak ada masukan yang menunggu</EmptyTitle>
                                    <EmptyDescription>
                                        Masukan baru dari lapangan muncul di sini.
                                    </EmptyDescription>
                                </>
                            )}
                        </EmptyHeader>
                    </Empty>
                </CardContent>
            ) : (
                <CardContent className="flex flex-1 flex-col">
                    <ScrollArea className="h-[17rem]">
                        <ItemGroup
                            className={cn(
                                'grid gap-3 pr-3',
                                sedikit
                                    ? 'grid-cols-1 content-center'
                                    : '@3xl/card:grid-cols-2 grid-cols-1 content-start',
                            )}
                        >
                            {masukan.map((m) => (
                                <Kutipan
                                    key={m.id}
                                    m={m}
                                    nonStaff={nonStaff}
                                    urlLogMasukan={urlLogMasukan}
                                />
                            ))}
                        </ItemGroup>
                    </ScrollArea>

                    {sisa > 0 ? (
                        <Link
                            href={nonStaff ? urlBerlaku : urlLogMasukan}
                            className="text-muted-foreground hover:text-primary mt-3 text-xs underline underline-offset-2"
                        >
                            Lihat {sisa} masukan lainnya →
                        </Link>
                    ) : null}
                </CardContent>
            )}
        </Card>
    );
}

function Kutipan({
    m,
    nonStaff,
    urlLogMasukan,
}: {
    m: BarisMasukan;
    nonStaff: boolean;
    urlLogMasukan: string;
}) {
    const tujuan = m.boleh_balas ? urlLogMasukan : m.tautan;

    return (
        /*
        | `flex-nowrap` WAJIB menemani `flex-col`.
        |
        | `Item` registry berbunyi `flex flex-wrap items-center` — dirancang
        | MENDATAR. Dibalik jadi kolom, `flex-wrap` mulai membungkus ke KOLOM
        | baru, dan `ItemHeader`/`ItemFooter` yang ber-`basis-full` (= tinggi
        | 100% begitu sumbu utamanya vertikal) memaksa tiap bagian pindah
        | kolomnya sendiri: status, umur, kutipan, pengirim, dan nomor berjajar
        | ke samping alih-alih bertumpuk. Itulah kartu masukan yang rusak.
        |
        | Karena itu pula kepala & kakinya `div` biasa, bukan `ItemHeader`/
        | `ItemFooter`: keduanya menggendong `basis-full` yang cuma benar di
        | `Item` mendatar.
        */
        <Item variant="outline" className="flex-col flex-nowrap items-stretch gap-2">
            <div className="flex items-center justify-between gap-2">
                <ItemTitle className="flex items-center gap-1.5 text-xs font-medium">
                    <span
                        aria-hidden="true"
                        className={cn('size-2 shrink-0 rounded-full', RONA[m.rona] ?? RONA.netral)}
                    />
                    {m.status}
                </ItemTitle>
                <div className="flex items-center gap-1.5">
                    {m.umur ? (
                        <span className="text-muted-foreground text-xs whitespace-nowrap">{m.umur}</span>
                    ) : null}
                    {tujuan ? (
                        <Link
                            href={tujuan}
                            title={m.boleh_balas ? 'Balas atau tutup masukan ini' : 'Buka dokumennya'}
                            className="text-muted-foreground hover:text-primary"
                        >
                            <Ikon nama="bi-check-circle" className="size-4" />
                            <span className="sr-only">
                                {m.boleh_balas ? 'Balas atau tutup masukan ini' : 'Buka dokumennya'}
                            </span>
                        </Link>
                    ) : null}
                </div>
            </div>

            <ItemContent className="gap-1">
                {/* Tanda kutip GANTUNG — di luar alur teks, jadi barisnya tetap
                    rata kiri. */}
                <div className="relative pl-4 text-sm leading-relaxed">
                    <span
                        className="text-primary absolute top-[-0.35rem] left-0 text-xl leading-none"
                        aria-hidden="true"
                    >
                        &ldquo;
                    </span>
                    {m.tautan && !nonStaff ? (
                        <Link href={m.tautan} className="hover:text-primary">
                            {m.isi}
                        </Link>
                    ) : (
                        m.isi
                    )}
                </div>

                {nonStaff && m.balasan ? (
                    <div className="border-primary bg-muted/60 mt-1 ml-4 rounded-r-md border-l-2 px-2 py-1.5 text-xs leading-relaxed">
                        <Ikon nama="bi-reply" className="mr-1 inline size-3" />
                        {m.balasan}
                    </div>
                ) : null}
            </ItemContent>

            {/* Pengirim hanya bagi yang MEMBACA masukan orang lain; Non-Staff
                membaca kirimannya sendiri, dan namanya sendiri bukan informasi.
                PEMILIK dokumennya tetap disebut untuk keduanya — Non-Staff pun
                perlu tahu ke meja siapa masukannya mendarat. */}
            <div className="flex items-center justify-between gap-2">
                <ItemDescription className="truncate">
                    {nonStaff ? `Pemilik: ${m.pemilik}` : `Dari ${m.pengirim} · Pemilik: ${m.pemilik}`}
                </ItemDescription>
                <Badge variant="secondary" className="shrink-0 font-mono font-normal">
                    {m.nomor}
                </Badge>
            </div>
        </Item>
    );
}
