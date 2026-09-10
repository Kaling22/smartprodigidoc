import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent, type ChartConfig } from '@/components/ui-maia/chart';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Ikon } from '@/components/v2/Ikon';
import { LencanaDelta } from '@/components/v2/dasbor/LencanaDelta';
import { rampMerah } from '@/components/v2/dasbor/ramp';
import type { SebaranDepartemen } from '@/types/dasbor';

/**
 * Peta warna per JENIS dokumen — RAMP SATU HUE (`--chart-1`, merah PPA)
 * tua→muda, formula dan alasannya di `./ramp.ts` yang dibaca kartu ini DAN
 * `SebaranJenisDept`. `DocumentType::RUPA` (hex identitas jenis) tetap TIDAK
 * dipakai di sini.
 *
 * Urutan kuncinya yang menentukan langkah, bukan peringkat jumlah: SOP selalu
 * yang tergelap walau angkanya kebetulan nol.
 *
 * `DasborV2Test::test_peta_warna_sebaran_lengkap()` membaca peta ini dan
 * memerahi hari saat jenis ketujuh lahir tanpa langkahnya ikut ditulis.
 */
const RAMP = 6;

const langkah = (i: number) => rampMerah(i, RAMP);

const WARNA: Record<string, string> = {
    SOP: langkah(0),
    IK: langkah(1),
    SP: langkah(2),
    JSA: langkah(3),
    FK: langkah(4),
    PX: langkah(5),
};

const chartConfig = Object.fromEntries(
    Object.entries(WARNA).map(([kode, warna]) => [kode, { label: kode, color: warna }]),
) satisfies ChartConfig;

/**
 * Sebaran dokumen BERLAKU per departemen (D6, §5e) — bar BERTUMPUK enam jenis
 * dokumen per departemen, gaya "Attendance Overview" (gambar 2 DASBOR-V4).
 *
 * MENGGANTIKAN bar proporsi tunggal lama. `Bar`/`BarChart`/`ChartContainer`
 * diambil apa adanya dari blok registry `chart-bar-stacked` (CLAUDE.md §13) —
 * enam `<Bar>` ber-`stackId="a"` sama, radius hanya di ujung tumpukan
 * (bawah pada jenis pertama, atas pada jenis terakhir) persis pola blok resmi.
 *
 * Legenda memakai KODE jenis (bukan nama panjang) — kartu sebelahnya
 * (`KartuSebaran`) sudah menuliskan namanya; di sini kode saja cukup dan tak
 * perlu prop tambahan dari server. Sejak TEMUAN-F8 F2b ia duduk di KANAN-ATAS
 * sebaris dengan dua angka besar (bentuk "On-Time 78% / Late 13% / Absent 10%"
 * di `docs/dasbor_ref/sebaran_perdepartemen.png`), dan grafiknya `flex-1`
 * supaya batangnya memenuhi tinggi kartu — kartu ini `h-full` sejajar
 * `PerformaPic`, dan sebelumnya menyisakan ruang kosong di bawah grafik
 * setinggi kartu tetangganya (temuan 1b).
 */
export function PitaDepartemen({ sebaran }: { sebaran: SebaranDepartemen }) {
    const jenisKeys = Object.keys(sebaran.baris[0]?.per ?? {});

    // Total per jenis dijumlah dari baris yang SUDAH dikirim server — bukan
    // query baru, sekadar penjumlahan larik yang sudah ada (pola yang sama
    // dengan `rasio` di GrafikOverview).
    const totalJenis: Record<string, number> = {};
    for (const j of jenisKeys) {
        totalJenis[j] = sebaran.baris.reduce((acc, b) => acc + (b.per[j] ?? 0), 0);
    }

    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Sebaran per Departemen</CardTitle>
                <CardDescription>
                    {sebaran.total > 0
                        ? `${sebaran.total} dokumen berlaku, terbagi ke ${sebaran.baris.length} departemen`
                        : 'Belum ada dokumen berlaku'}
                </CardDescription>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col">
                {sebaran.total === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-building" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Belum ada dokumen berlaku</EmptyTitle>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <>
                        <div className="flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
                            <div className="flex flex-wrap items-start gap-x-8 gap-y-4">
                                <div>
                                    <span className="text-2xl font-semibold tabular-nums">
                                        {sebaran.persenTerbesar}%
                                    </span>
                                    <p className="text-muted-foreground text-xs">Porsi departemen terbesar</p>
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
                                    <p className="text-muted-foreground text-xs">Dokumen berlaku · 30 hari</p>
                                </div>
                            </div>

                            {/* Legenda: batang tebal + kode + persen di bawahnya, kanan-atas (F2b). */}
                            <div className="flex flex-wrap gap-x-5 gap-y-2">
                                {jenisKeys.map((j) => (
                                    <div key={j} className="flex flex-col gap-1">
                                        <span
                                            aria-hidden="true"
                                            className="h-1.5 w-8 rounded-full"
                                            style={{ backgroundColor: WARNA[j] ?? 'var(--muted-foreground)' }}
                                        />
                                        <span className="text-muted-foreground text-xs font-medium">{j}</span>
                                        <span className="text-xs font-semibold tabular-nums">
                                            {sebaran.total > 0
                                                ? Math.round((totalJenis[j] / sebaran.total) * 100)
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
                                {jenisKeys.map((j, i) => (
                                    <Bar
                                        key={j}
                                        dataKey={`per.${j}`}
                                        name={j}
                                        stackId="a"
                                        maxBarSize={44}
                                        fill={`var(--color-${j})`}
                                        radius={
                                            i === 0
                                                ? [0, 0, 4, 4]
                                                : i === jenisKeys.length - 1
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
