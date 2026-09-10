/**
 * KEMBARAN MAIA dari `resources/js/components/dasbor/MeterTertinjau.tsx` (DASBOR-V2-REVISI §4a).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser. Yang berubah hanya
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, §5). Berkas aslinya sengaja TIDAK disentuh: 41 halaman
 * lama masih memakainya sebagai pembanding selama jendela pratinjau. Tranche 4
 * menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup. PENGECUALIAN sejak keputusan
 * K-A (rencana pra-produksi): produksi memakai V2, jadi butir 8–14 dikerjakan
 * HANYA di pohon ini. Judul meter per peran (Fase 10) karena itu sengaja tak
 * dibawa ke berkas V1 — ia mengabaikan kunci barunya dan tetap "Tertinjau".
 */
import { Label, PolarGrid, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui-maia/card';
import { ChartContainer, type ChartConfig } from '@/components/ui-maia/chart';
import type { DeretTren } from '@/types/dasbor';

/**
 * Meter "Tertinjau" — salinan blok `chart-radial-text`.
 *
 * Menggantikan busur SVG 270° tulisan tangan (`.pp-radial`) di Blade lama.
 * Yang diambil apa adanya dari blok: `RadialBarChart` ber-`startAngle`/`endAngle`,
 * `PolarGrid gridType="circle" radialLines={false}` dengan `polarRadius={[86, 74]}`,
 * `RadialBar background cornerRadius={10}`, dan `PolarRadiusAxis` berisi `<Label>`
 * dua `tspan` (angka besar + keterangan di bawahnya).
 *
 * Sudut akhirnya dihitung dari persen (`3.6° per persen`) supaya panjang busur
 * memang mewakili angkanya — di blok nilainya statis, jadi sudutnya pun statis.
 *
 * Durasi tidak punya pemilihnya sendiri di sini: ia satu keadaan dengan
 * Overview Dokumen di sebelahnya (dikendalikan `pages/Dashboard.tsx`), jadi
 * grafik dan meter mustahil bercerita tentang rentang waktu yang berbeda —
 * janji yang sama dengan dua dropdown yang saling terikat di Blade lama.
 */
/*
 * Judul meter datang dari SERVER (`deret.meterLabel`) sejak rencana
 * pra-produksi Fase 10: GL membaca "Dibuat", SH/DH/MD "Tertinjau", PJO
 * "Disetujui". Ketiganya anak tangga alur yang sama, dan cabang perannya
 * hidup di `DashboardController` — bukan di sini (CLAUDE.md §4).
 *
 * `ChartConfig` karena itu dirakit per render, tak lagi konstanta modul:
 * labelnya ikut berganti bersama peran pembacanya.
 */
export function MeterTertinjau({ deret }: { deret: DeretTren }) {
    const konfigurasi = {
        persen: { label: deret.meterLabel },
        nilai: { label: deret.meterLabel, color: 'var(--chart-1)' },
    } satisfies ChartConfig;

    const data = [{ kunci: 'nilai', persen: deret.growth, fill: 'var(--color-nilai)' }];

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="items-center bg-muted/40 border-b pb-0">
                <CardTitle className="text-sm font-bold">{deret.meterLabel}</CardTitle>
                <CardDescription>{deret.rentang}</CardDescription>
            </CardHeader>
            <CardContent className="flex-1 pb-0">
                <ChartContainer config={konfigurasi} className="mx-auto aspect-square max-h-[220px]">
                    <RadialBarChart
                        data={data}
                        startAngle={0}
                        endAngle={deret.growth * 3.6}
                        innerRadius={80}
                        outerRadius={110}
                    >
                        <PolarGrid
                            gridType="circle"
                            radialLines={false}
                            stroke="none"
                            className="first:fill-muted last:fill-background"
                            polarRadius={[86, 74]}
                        />
                        <RadialBar dataKey="persen" background cornerRadius={10} />
                        <PolarRadiusAxis tick={false} tickLine={false} axisLine={false} domain={[0, 100]}>
                            <Label
                                content={({ viewBox }) => {
                                    if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                        return (
                                            <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle" dominantBaseline="middle">
                                                <tspan
                                                    x={viewBox.cx}
                                                    y={viewBox.cy}
                                                    className="fill-foreground text-3xl font-bold"
                                                >
                                                    {deret.growth}%
                                                </tspan>
                                                <tspan
                                                    x={viewBox.cx}
                                                    y={(viewBox.cy || 0) + 24}
                                                    className="fill-muted-foreground"
                                                >
                                                    {deret.meterLabel}
                                                </tspan>
                                            </text>
                                        );
                                    }
                                }}
                            />
                        </PolarRadiusAxis>
                    </RadialBarChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="flex-col gap-2 text-sm">
                <div className="text-center text-muted-foreground">{deret.meterKeterangan}</div>
                <div className="flex w-full justify-center gap-8">
                    <div className="text-center">
                        <div className="text-xs text-muted-foreground">Dibuat</div>
                        <div className="font-semibold tabular-nums">{deret.total}</div>
                    </div>
                    <div className="text-center">
                        <div className="text-xs text-muted-foreground">Berlaku</div>
                        <div className="font-semibold tabular-nums">{deret.berlakuTotal}</div>
                    </div>
                </div>
            </CardFooter>
        </Card>
    );
}
