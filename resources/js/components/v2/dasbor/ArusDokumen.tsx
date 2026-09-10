import { useState } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';

import {
    Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import {
    ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig,
} from '@/components/ui-maia/chart';
import { Progress } from '@/components/ui-maia/progress';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui-maia/toggle-group';
import { useIsMobile } from '@/hooks/use-mobile';
import type { Tren, DeretTren, KunciDurasi } from '@/types/dasbor';

/**
 * "Arus Dokumen" — GABUNGAN `GrafikOverview` + `MeterTertinjau`.
 *
 * Kenapa digabung (REDESAIN-UI-V2 §8): keduanya menjawab pertanyaan yang sama
 * atas rentang waktu yang sama, dan di dasbor lama mereka duduk bersebelahan
 * sebagai dua kartu yang harus dijaga agar tidak bercerita tentang periode
 * berbeda. Penjagaan itu dulu berupa SATU state `durasi` yang diangkat ke
 * `pages/Dashboard.tsx` lalu dioper ke dua komponen — peretasan yang bekerja,
 * tapi yang membuat halaman ikut memikul urusan dalam sebuah kartu.
 *
 * Sesudah digabung, penjagaan itu tak diperlukan lagi: `durasi` jadi state
 * INTERNAL, dan dua angka yang berbeda rentang bukan lagi sesuatu yang harus
 * dicegah melainkan sesuatu yang mustahil secara struktur.
 *
 * Susunannya: rail kiri (persen tertinjau besar + Dibuat/Berlaku) + area chart
 * di kanan, segmented control di `CardAction`. Busur radial 220px milik
 * `MeterTertinjau` diganti angka + `Progress` tipis — satu angka persen tak
 * pernah membutuhkan seperempat kartu untuk dibaca, dan ruang itu kini jadi
 * milik grafiknya.
 *
 * Dua deret dibedakan HUE, bukan terang-gelap: `--chart-1` merah PPA dan
 * `--chart-2` teal datang dari palet kategorikal `.ui-v2` (§6). `stackId`
 * milik blok tetap DIBUANG — "Dibuat" dan "Berlaku" bukan dua bagian dari satu
 * jumlah; dokumen yang sama dihitung di keduanya, jadi menumpuknya menggambar
 * total yang tak berarti apa-apa.
 */
const konfigurasi = {
    dibuat: { label: 'Dibuat', color: 'var(--chart-1)' },
    berlaku: { label: 'Berlaku', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const NAMA_DURASI: Record<KunciDurasi, string> = {
    hari: 'Harian',
    minggu: 'Mingguan',
    bulan: 'Bulanan',
};

export function ArusDokumen({ tren }: { tren: Tren }) {
    // Bulanan = keadaan awal, sama dengan Blade lama (`$awal = $tren['bulan']`).
    const [durasi, setDurasi] = useState<KunciDurasi>('bulan');
    const deret: DeretTren = tren[durasi];
    const isMobile = useIsMobile();

    // Recharts menerima satu larik obyek per titik; server mengirim tiga larik
    // sejajar (labels/dibuat/berlaku) karena itulah bentuk yang dipakai Chart.js
    // lama. Menjahitnya di sini lebih murah daripada mengubah bentuk props dan
    // memaksa tes aritmetika (`DashboardIkhtisarTest`) ikut ditulis ulang.
    const data = deret.labels.map((label, i) => ({
        label,
        dibuat: deret.dibuat[i] ?? 0,
        berlaku: deret.berlaku[i] ?? 0,
    }));

    const pilih = (nilai: string) => {
        // ToggleGroup mengirim string kosong saat item yang aktif ditekan lagi;
        // membiarkannya lewat akan mengosongkan grafik.
        if (nilai) setDurasi(nilai as KunciDurasi);
    };

    return (
        <Card className="@container/card h-full">
            <CardHeader>
                <CardTitle>Arus Dokumen</CardTitle>
                <CardDescription>{deret.rentang}</CardDescription>
                <CardAction>
                    <ToggleGroup
                        type="single"
                        value={durasi}
                        onValueChange={pilih}
                        variant="outline"
                        className="hidden *:data-[slot=toggle-group-item]:px-4! @[767px]/card:flex"
                    >
                        <ToggleGroupItem value="hari">Harian</ToggleGroupItem>
                        <ToggleGroupItem value="minggu">Mingguan</ToggleGroupItem>
                        <ToggleGroupItem value="bulan">Bulanan</ToggleGroupItem>
                    </ToggleGroup>
                    <Select value={durasi} onValueChange={pilih}>
                        <SelectTrigger
                            className="flex w-32 **:data-[slot=select-value]:block **:data-[slot=select-value]:truncate @[767px]/card:hidden"
                            size="sm"
                            aria-label="Pilih durasi"
                        >
                            <SelectValue placeholder={NAMA_DURASI[durasi]} />
                        </SelectTrigger>
                        <SelectContent>
                            {(Object.keys(NAMA_DURASI) as KunciDurasi[]).map((k) => (
                                <SelectItem key={k} value={k}>
                                    {NAMA_DURASI[k]}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardAction>
            </CardHeader>

            <CardContent className="grid gap-6 @2xl/card:grid-cols-[minmax(0,11rem)_1fr]">
                <div className="flex flex-col justify-center gap-4">
                    <div>
                        <div className="text-4xl font-semibold tabular-nums">{deret.growth}%</div>
                        <p className="text-muted-foreground text-sm">sudah melewati tinjauan</p>
                        <Progress value={deret.growth} className="mt-2 h-1.5" />
                        <p className="text-muted-foreground mt-1.5 text-xs">
                            {deret.tertinjau} dari {deret.total} dokumen
                        </p>
                    </div>

                    {/* Angka ditulis pada deret yang penting — identitas deret
                        tak pernah warna saja (REDESAIN-UI-V2 §10). Titik warna
                        di sini merangkap legenda grafik di sebelahnya. */}
                    <dl className="grid grid-cols-2 gap-3 @2xl/card:grid-cols-1">
                        <Angka warna="var(--color-dibuat)" label="Dibuat" nilai={deret.total} />
                        <Angka warna="var(--color-berlaku)" label="Berlaku" nilai={deret.berlakuTotal} />
                    </dl>
                </div>

                <ChartContainer config={konfigurasi} className="aspect-auto h-[230px] w-full">
                    <AreaChart data={data}>
                        <defs>
                            <linearGradient id="v2IsiDibuat" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-dibuat)" stopOpacity={0.9} />
                                <stop offset="95%" stopColor="var(--color-dibuat)" stopOpacity={0.05} />
                            </linearGradient>
                            <linearGradient id="v2IsiBerlaku" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-berlaku)" stopOpacity={0.7} />
                                <stop offset="95%" stopColor="var(--color-berlaku)" stopOpacity={0.05} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="label"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            minTickGap={isMobile ? 24 : 32}
                        />
                        {/* Crosshair, bukan `cursor={false}`: dua deret yang
                            saling menutup butuh garis tegak untuk memastikan
                            kedua angka di tooltip memang titik yang sama. */}
                        <ChartTooltip
                            cursor={{ stroke: 'var(--border)', strokeWidth: 1 }}
                            content={<ChartTooltipContent indicator="dot" />}
                        />
                        <Area
                            dataKey="berlaku"
                            type="natural"
                            fill="url(#v2IsiBerlaku)"
                            stroke="var(--color-berlaku)"
                        />
                        <Area
                            dataKey="dibuat"
                            type="natural"
                            fill="url(#v2IsiDibuat)"
                            stroke="var(--color-dibuat)"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}

function Angka({ warna, label, nilai }: { warna: string; label: string; nilai: number }) {
    return (
        <div className="flex items-center gap-2">
            <span className="size-2.5 shrink-0 rounded-full" style={{ backgroundColor: warna }} />
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="ml-auto font-semibold tabular-nums">{nilai}</dd>
        </div>
    );
}
