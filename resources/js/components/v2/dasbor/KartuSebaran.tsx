import { Label, Pie, PieChart, Sector } from 'recharts';
import type { PieSectorDataItem } from 'recharts';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import {
    ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig,
} from '@/components/ui-maia/chart';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Ikon } from '@/components/v2/Ikon';
import type { Sebaran } from '@/types/dasbor';

/**
 * "Sebaran Jenis" — donat per jenis, blok `chart-pie-donut-text` (F5 DASBOR-V4).
 *
 * **Keputusan D2 (rekomendasi a, diambil):** warna ruas BUKAN lagi
 * `DocumentType::RUPA` (props `sebaran[].warna`, dipakai kartu Distribusi
 * sebelahnya), melainkan **ramp sekuensial lokal ke kartu ini saja** —
 * irisan diurutkan menurun lalu diwarnai `color-mix(in oklab, var(--chart-1)
 * X%, var(--card))`, X menurun mengikuti peringkat. Nol hex diketik, ramp
 * sekuensial dipakai secara sekuensial (persis kebalikan cacat yang
 * REDESAIN-UI-V2 §6 temukan), dan `DocumentType::RUPA` TAK disentuh — warna
 * identitas jenis di `KartuDistribusi`/`Documents/Index`/Daftar Induk tak
 * bergeser. Konsekuensi yang harus disadari: warna irisan donat di sini
 * TIDAK LAGI sama dengan warna chip jenis di kartu sebelahnya — pilihan
 * sadar, bukan alpa (alternatif (b) mengganti `RUPA` se-aplikasi ditolak,
 * itu bukan keputusan dasbor).
 *
 * Beda dari versi nova: legenda memuat PERSEN, bukan cuma jumlah. Donat tanpa
 * angka memaksa mata mengukur sudut, dan mengukur sudut adalah hal yang paling
 * tidak bisa dilakukan mata dengan tepat — total di lubang donat saja tak
 * menolong membandingkan dua ruas satu sama lain.
 */
function ruasAktif({ cx, cy, innerRadius, outerRadius, startAngle, endAngle, fill }: PieSectorDataItem) {
    return (
        <Sector
            cx={cx}
            cy={cy}
            innerRadius={innerRadius}
            outerRadius={(outerRadius ?? 0) + 8}
            startAngle={startAngle}
            endAngle={endAngle}
            fill={fill}
        />
    );
}

export function KartuSebaran({ sebaran }: { sebaran: Record<string, Sebaran> }) {
    const urutan = Object.keys(sebaran).sort((a, b) => sebaran[b].jumlah - sebaran[a].jumlah);
    const total = urutan.reduce((n, k) => n + sebaran[k].jumlah, 0);

    const warnaRamp = Object.fromEntries(
        urutan.map((k, i) => [
            k,
            `color-mix(in oklab, var(--chart-1) ${90 - i * (60 / Math.max(urutan.length - 1, 1))}%, var(--card))`,
        ]),
    );

    const konfigurasi: ChartConfig = {
        jumlah: { label: 'Dokumen' },
        ...Object.fromEntries(urutan.map((k) => [k, { label: sebaran[k].nama, color: warnaRamp[k] }])),
    };

    const data = urutan.map((k) => ({ jenis: k, jumlah: sebaran[k].jumlah, fill: warnaRamp[k] }));

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Sebaran Jenis</CardTitle>
                <CardDescription>Komposisi dokumen berlaku menurut jenisnya</CardDescription>
            </CardHeader>
            <CardContent className="flex-1">
                {total === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-pie-chart" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Belum ada dokumen berlaku</EmptyTitle>
                            <EmptyDescription>Komposisinya muncul di sini begitu ada yang disahkan.</EmptyDescription>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <>
                        <ChartContainer config={konfigurasi} className="mx-auto aspect-square max-h-[200px]">
                            <PieChart>
                                <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                                <Pie
                                    data={data}
                                    dataKey="jumlah"
                                    nameKey="jenis"
                                    innerRadius={58}
                                    strokeWidth={5}
                                    paddingAngle={1}
                                    activeShape={ruasAktif}
                                >
                                    <Label
                                        content={({ viewBox }) => {
                                            if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                                return (
                                                    <text
                                                        x={viewBox.cx}
                                                        y={viewBox.cy}
                                                        textAnchor="middle"
                                                        dominantBaseline="middle"
                                                    >
                                                        <tspan
                                                            x={viewBox.cx}
                                                            y={viewBox.cy}
                                                            className="fill-foreground text-3xl font-bold"
                                                        >
                                                            {total.toLocaleString('id-ID')}
                                                        </tspan>
                                                        <tspan
                                                            x={viewBox.cx}
                                                            y={(viewBox.cy || 0) + 24}
                                                            className="fill-muted-foreground"
                                                        >
                                                            Berlaku
                                                        </tspan>
                                                    </text>
                                                );
                                            }
                                        }}
                                    />
                                </Pie>
                            </PieChart>
                        </ChartContainer>

                        <ul className="mt-4 space-y-2.5">
                            {urutan.map((k) => (
                                <li key={k} className="flex items-center gap-3">
                                    <span
                                        aria-hidden="true"
                                        className="size-2.5 shrink-0 rounded-full"
                                        style={{ backgroundColor: warnaRamp[k] }}
                                    />
                                    <div className="min-w-0 flex-1">
                                        <div className="text-sm font-semibold uppercase">{k}</div>
                                        <div className="text-muted-foreground truncate text-xs">
                                            {sebaran[k].nama}
                                        </div>
                                    </div>
                                    <span className="font-semibold tabular-nums">{sebaran[k].jumlah}</span>
                                    <span className="text-muted-foreground w-10 text-right text-xs tabular-nums">
                                        {Math.round((sebaran[k].jumlah / total) * 100)}%
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
