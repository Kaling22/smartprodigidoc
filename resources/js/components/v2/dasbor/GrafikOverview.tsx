/**
 * KEMBARAN MAIA dari `resources/js/components/dasbor/GrafikOverview.tsx` (DASBOR-V2-REVISI §4a).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, §5). Berkas aslinya sengaja TIDAK disentuh: 41 halaman
 * lama masih memakainya sebagai pembanding selama jendela pratinjau. Tranche 4
 * menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 */
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';

import { useIsMobile } from '@/hooks/use-mobile';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui-maia/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui-maia/chart';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui-maia/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui-maia/toggle-group';
import type { DeretTren, KunciDurasi } from '@/types/dasbor';

/**
 * "Overview Dokumen" — salinan `chart-area-interactive.tsx` (blok `dashboard-01`).
 *
 * Yang diambil apa adanya dari blok: `Card @container/card`, `CardAction` berisi
 * `ToggleGroup` di layar lebar + `Select` di layar sempit (`@[767px]/card`),
 * `ChartContainer aspect-auto h-[250px] w-full`, dua `linearGradient` `fill*`,
 * `CartesianGrid vertical={false}`, `XAxis` tanpa garis dengan `minTickGap`,
 * `ChartTooltip cursor={false}` + `indicator="dot"`, dan dua `<Area type="natural">`.
 *
 * Yang diganti: sumber datanya (props server, bukan larik contoh), nama deret
 * (dibuat/berlaku, bukan desktop/mobile), dan tiga pilihan durasi SmartPro.
 * `stackId` blok DIBUANG: "Dibuat" dan "Berlaku" bukan dua bagian dari satu
 * jumlah — dokumen yang sama dihitung di keduanya, jadi menumpuknya
 * menggambar total yang tak berarti apa-apa.
 *
 * Ketiga durasi sudah ikut halaman; berganti durasi hanya menukar larik, tak
 * ada permintaan ke server — perilaku yang sama dengan Chart.js lama.
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

export function GrafikOverview({
    deret,
    durasi,
    onDurasi,
}: {
    deret: DeretTren;
    durasi: KunciDurasi;
    onDurasi: (d: KunciDurasi) => void;
}) {
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

    const rasio = deret.total > 0 ? Math.round((deret.berlakuTotal / deret.total) * 100) : null;

    const pilih = (nilai: string) => {
        // ToggleGroup mengirim string kosong saat item yang aktif ditekan lagi;
        // membiarkannya lewat akan mengosongkan grafik.
        if (nilai) onDurasi(nilai as KunciDurasi);
    };

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Overview Dokumen</CardTitle>
                <CardDescription>
                    <span className="font-semibold text-foreground">
                        {deret.berlakuTotal} dari {deret.total}
                    </span>{' '}
                    dokumen yang dibuat sudah disahkan
                    {rasio !== null ? ` (${rasio}%)` : ''} dalam {deret.rentang}
                </CardDescription>
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
                        <SelectContent className="rounded-xl">
                            {(Object.keys(NAMA_DURASI) as KunciDurasi[]).map((k) => (
                                <SelectItem key={k} value={k} className="rounded-lg">
                                    {NAMA_DURASI[k]}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </CardAction>
            </CardHeader>
            {/* `flex-1` + kartu `h-full`: kartu ini dan `MeterTertinjau`
                duduk di SATU baris grid, dan sebelumnya hanya yang kanan
                meregang (ia anak grid langsung, sedangkan kartu ini terbungkus
                `col-span-2`). Akibatnya tinggi keduanya tak pernah sama.
                Tinggi grafiknya sendiri turun dari 250 px tetap jadi 200 px
                MINIMUM yang lalu meregang mengisi sisa kartu, jadi kedua
                kartu selalu setinggi yang paling tinggi dan tak ada lagi
                ruang kosong di bawah salah satunya (keputusan pemilik
                2026-09-07). */}
            <CardContent className="flex flex-1 flex-col px-2 pt-4 sm:px-6 sm:pt-6">
                <ChartContainer config={konfigurasi} className="aspect-auto min-h-[200px] w-full flex-1">
                    <AreaChart data={data}>
                        <defs>
                            <linearGradient id="isiDibuat" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-dibuat)" stopOpacity={1.0} />
                                <stop offset="95%" stopColor="var(--color-dibuat)" stopOpacity={0.1} />
                            </linearGradient>
                            <linearGradient id="isiBerlaku" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-berlaku)" stopOpacity={0.8} />
                                <stop offset="95%" stopColor="var(--color-berlaku)" stopOpacity={0.1} />
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
                        <ChartTooltip cursor={false} content={<ChartTooltipContent indicator="dot" />} />
                        <Area
                            dataKey="berlaku"
                            type="natural"
                            fill="url(#isiBerlaku)"
                            stroke="var(--color-berlaku)"
                        />
                        <Area
                            dataKey="dibuat"
                            type="natural"
                            fill="url(#isiDibuat)"
                            stroke="var(--color-dibuat)"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
