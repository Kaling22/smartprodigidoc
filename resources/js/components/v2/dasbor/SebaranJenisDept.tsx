import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { usePage } from '@inertiajs/react';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui-maia/chart';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Ikon } from '@/components/v2/Ikon';
import { LencanaDelta } from '@/components/v2/dasbor/LencanaDelta';
import { rampMerah } from '@/components/v2/dasbor/ramp';
import type { PageProps } from '@/types';
import type { SebaranJenisDept as Data } from '@/types/dasbor';

/**
 * "Sebaran Dokumen Departemen" (F4a DASBOR-V4) — mengisi kolom 2/3 baris ke-5
 * bagi SH/DH, yang sebelumnya ganjal `<div hidden>` (REVISI-UI-V3 §4.6:
 * `sebaranDepartemen` dicabut dari SH/DH karena gerbangnya `document.view_all`).
 *
 * Pola bar bertumpuk SAMA dengan `PitaDepartemen` (blok registry
 * `chart-bar-stacked`, CLAUDE.md §13), sumbunya DIBALIK: SH/DH cuma satu
 * departemen, jadi "per departemen" tak berarti apa-apa di sini. Bar-nya per
 * JENIS dokumen, tumpukannya per STATUS.
 *
 * Nol query baru — `sebaranJenisDept` dipivot dari `matrix` yang sudah dikirim
 * (`DashboardController::sebaranJenisDept()`).
 *
 * ## Kenapa warnanya ramp merah, bukan `statusMeta`
 *
 * Kartu ini SEBELUMNYA mewarnai tumpukannya dari `statusMeta` (props global,
 * sama dengan `StatusBadge`/`LacakStatus`). Pemilik menolaknya pada pemeriksaan
 * mata 2026-09-07: kartu SH/DH dan kartu PJO adalah kartu yang SAMA baginya —
 * "style-nya samakan plek ketiplek, dari warna, jarak, font apapun itu" — dan
 * dua kartu bersebelahan yang satu merah bertingkat dan satu tujuh-warna
 * terbaca sebagai dua rancangan berbeda.
 *
 * Ini TIDAK melanggar CLAUDE.md §4. Yang dilarang di sana adalah MENULIS ULANG
 * peta status di TSX; di sini tak ada satu pun nama, label, urutan, atau
 * jumlah status yang diketik: kuncinya datang dari `sebaran.baris[].per`
 * (server), labelnya dari `statusLabels` (props), dan yang ditentukan TSX
 * hanyalah langkah keberapa pada satu ramp — sebuah posisi, bukan sebuah
 * identitas. Status yang lahir kedelapan mendapat langkahnya sendiri tanpa
 * satu baris pun disentuh, sedangkan peta warna yang diketik tangan justru
 * akan menjatuhkannya ke abu-abu diam-diam.
 *
 * Konsekuensi yang harus disadari: warna segmen di kartu ini TIDAK lagi sama
 * dengan warna `StatusBadge` untuk status yang sama. Legendanya karena itu
 * menyebutkan nama tiap status, sama seperti `PitaDepartemen` menyebutkan kode
 * tiap jenis — syarat yang sama yang membuat keputusan W-2 sah untuk kartu itu.
 *
 * TATA LETAKNYA disalin dari `PitaDepartemen` kelas per kelas: dua angka besar
 * kiri, legenda kanan-atas (batang tebal + nama + persen), sumbu Y, garis kisi
 * putus-putus, batang berujung membulat, grafik `flex-1`.
 */
export function SebaranJenisDept({ sebaran }: { sebaran: Data }) {
    const { statusLabels } = usePage<PageProps>().props;
    const statusKeys = Object.keys(sebaran.baris[0]?.per ?? {});

    // Langkah ramp BERKUNCI URUTAN status dari server — bukan peringkat jumlah,
    // supaya warnanya tak berpindah tiap data berubah. Persis aturan `WARNA`
    // di `PitaDepartemen`.
    const warna = (s: string) => rampMerah(statusKeys.indexOf(s), statusKeys.length);

    const chartConfig = Object.fromEntries(
        statusKeys.map((s) => [s, { label: statusLabels[s] ?? s, color: warna(s) }]),
    ) satisfies ChartConfig;

    // Total per status dijumlah dari baris yang SUDAH dikirim server — bukan
    // query baru, sekadar penjumlahan larik (pola yang sama dengan
    // `totalJenis` di PitaDepartemen).
    const totalStatus: Record<string, number> = {};
    for (const s of statusKeys) {
        totalStatus[s] = sebaran.baris.reduce((acc, b) => acc + (b.per[s] ?? 0), 0);
    }

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Sebaran Dokumen Departemen</CardTitle>
                <CardDescription>
                    {sebaran.total > 0
                        ? `${sebaran.total} dokumen, terbagi ke ${sebaran.baris.length} jenis`
                        : 'Belum ada dokumen di departemen ini'}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col">
                {sebaran.total === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-files" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Belum ada dokumen di departemen ini</EmptyTitle>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <>
                        {/* Kepala isi: dua angka besar kiri + legenda kanan —
                            SUSUNAN YANG SAMA PERSIS dengan `PitaDepartemen`
                            (keputusan pemilik 2026-09-07). Sebelumnya kartu ini
                            hanya berlegenda, dan dua kartu yang seharusnya
                            bersaudara tampak berbeda rancangan. */}
                        <div className="flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
                            <div className="flex flex-wrap items-start gap-x-8 gap-y-4">
                                <div>
                                    <span className="text-2xl font-semibold tabular-nums">
                                        {sebaran.persenTerbesar}%
                                    </span>
                                    <p className="text-muted-foreground text-xs">Porsi jenis terbesar</p>
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="text-2xl font-semibold tabular-nums">{sebaran.total}</span>
                                        <LencanaDelta
                                            arah={sebaran.arah}
                                            delta={sebaran.delta}
                                            deltaLabel={sebaran.deltaLabel}
                                        />
                                    </div>
                                    <p className="text-muted-foreground text-xs">Dokumen dibuat · 30 hari</p>
                                </div>
                            </div>

                            {/* Legenda: batang tebal + nama + persen di bawahnya,
                                kanan-atas — kelas per kelas sama dengan
                                `PitaDepartemen`. */}
                            <div className="flex flex-wrap gap-x-5 gap-y-2">
                                {statusKeys.map((s) => (
                                    <div key={s} className="flex flex-col gap-1">
                                        <span
                                            aria-hidden="true"
                                            className="h-1.5 w-8 rounded-full"
                                            style={{ backgroundColor: warna(s) }}
                                        />
                                        <span className="text-muted-foreground text-xs font-medium">
                                            {statusLabels[s] ?? s}
                                        </span>
                                        <span className="text-xs font-semibold tabular-nums">
                                            {sebaran.total > 0
                                                ? Math.round((totalStatus[s] / sebaran.total) * 100)
                                                : 0}
                                            %
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <ChartContainer
                            config={chartConfig}
                            className="mt-4 aspect-auto min-h-[260px] w-full flex-1"
                        >
                            <BarChart data={sebaran.baris} accessibilityLayer>
                                <CartesianGrid vertical={false} strokeDasharray="3 3" />
                                <XAxis dataKey="kode" tickLine={false} axisLine={false} tickMargin={8} />
                                <YAxis tickLine={false} axisLine={false} tickMargin={8} width={32} />
                                <ChartTooltip content={<ChartTooltipContent indicator="dot" />} />
                                {statusKeys.map((s, i) => (
                                    <Bar
                                        key={s}
                                        dataKey={`per.${s}`}
                                        name={statusLabels[s] ?? s}
                                        stackId="a"
                                        maxBarSize={44}
                                        fill={`var(--color-${s})`}
                                        radius={
                                            i === 0
                                                ? [0, 0, 4, 4]
                                                : i === statusKeys.length - 1
                                                  ? [4, 4, 0, 0]
                                                  : 0
                                        }
                                    />
                                ))}
                            </BarChart>
                        </ChartContainer>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
