import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui-maia/item';
import { Separator } from '@/components/ui-maia/separator';
import { LencanaDelta } from '@/components/v2/dasbor/LencanaDelta';
import { TumpukanWajah } from '@/components/v2/dasbor/TumpukanWajah';
import { Avatar } from '@/components/v2/Avatar';
import type { PerformaPic as Data } from '@/types/dasbor';

/**
 * Kartu "Performa PIC" (D9, §5d) — tiga hal bertumpuk, dipisah `Separator`:
 * angka besar PIC aktif, deret wajah paling produktif, tiga baris sorotan.
 *
 * PENYIMPANGAN dari registry (CLAUDE.md §13): tak ada blok shadcn untuk kartu
 * bertumpuk seperti ini. Yang dipakai `card` + `separator` dari registry, dan
 * deret wajahnya `v2/dasbor/TumpukanWajah` yang SUDAH ADA — dipakai ulang, tak
 * digambar ulang. Panah sorotannya `LencanaDelta` yang sama dengan kartu KPI,
 * jadi satu panah berarti hal yang sama di dua tempat.
 *
 * BUKAN PAPAN PERINGKAT, dan itu keputusan yang sengaja tertulis: judulnya
 * "Paling Produktif", tanpa nomor urut, dan tak ada yang ditampilkan di posisi
 * terbawah. Kartu yang memperlihatkan siapa paling sedikit bekerja adalah kartu
 * yang akan dipakai untuk hal yang bukan urusan dasbor.
 *
 * KEBOCORAN (§P4): `wajah` yang datang dari server HANYA memuat `nama`, `foto`,
 * `jumlah`, `rincian` (empat integer) dan `ket` (satu kalimat) — bukan model
 * `User`, tanpa `id`/`nrp`/`email`. Kartu ini menampilkan wajah dan nama orang,
 * jadi ia justru yang paling mudah menyeret seluruh kolom `users` ke atribut
 * `data-page`. Dikunci `DasborV2Test` nomor 8; jangan sekali pun menambah kunci
 * di sini "karena mungkin berguna".
 *
 * RINCIAN (butir 10 & 11): kalimatnya (`ket`) dirakit SERVER, dan daftar di
 * bawah tumpukan wajah cuma mencetaknya. Tumpukan wajahnya TETAP — ia yang
 * memberi kesan "berapa banyak orang"; daftarnya yang memberi "siapa
 * mengerjakan apa".
 *
 * `null` bagi GL dan Non-Staff (REVISI-UI-V3 §4.1) — gerbangnya `lingkupPic()`
 * di server; K1 dan `dashboardPenuh()` yang dulu meloloskan GL SUDAH DICABUT
 * 2026-09-01. Yang menyaringnya halaman, bukan kartu ini.
 *
 * `judul`/`subjudul`/`satuan` (F4b DASBOR-V4, D3): kosakata kartu berbeda per
 * lingkup — SH/DH ("Produktivitas Tim", satuan "GL") vs PJO/MD/Admin ("PIC
 * Aktif", satuan "PIC") — SELURUHNYA dari server. Nol `if peran` di sini.
 */
export function PerformaPic({ data }: { data: Data }) {
    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">{data.judul}</CardTitle>
                <CardDescription>{data.subjudul}</CardDescription>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-4">
                <p className="text-4xl font-semibold tabular-nums">{data.picAktif}</p>

                <Separator />

                <div>
                    <p className="text-muted-foreground mb-2 text-xs font-medium">
                        Paling produktif 30 hari
                    </p>
                    {/* `ket` = jumlah langkah alur yang ia gerakkan, tampil di
                        HoverCard. Angkanya tak dicetak di bawah wajahnya:
                        deretnya akan berubah jadi papan peringkat. */}
                    <TumpukanWajah
                        satuan={data.satuan}
                        pembaca={data.wajah.map((w) => ({
                            nama: w.nama,
                            foto: w.foto,
                            ket: `${w.jumlah}`,
                        }))}
                    />

                    {/* Lima baris saja: kartunya harus tetap setinggi
                        tetangganya di baris dasbor yang sama. Sisanya tetap
                        terbaca lewat HoverCard tumpukan di atas. */}
                    {data.wajah.length > 0 && (
                        <div className="mt-3 flex flex-col">
                            {data.wajah.slice(0, 5).map((w, i) => (
                                <Item key={`${w.nama}-${i}`} size="sm" className="gap-2 px-0 py-1.5">
                                    <ItemMedia>
                                        <Avatar nama={w.nama} foto={w.foto} className="size-6" />
                                    </ItemMedia>
                                    <ItemContent className="gap-0">
                                        <ItemTitle className="truncate">{w.nama}</ItemTitle>
                                        <ItemDescription className="truncate">{w.ket}</ItemDescription>
                                    </ItemContent>
                                </Item>
                            ))}
                        </div>
                    )}
                </div>

                <Separator />

                <div className="space-y-3">
                    <p className="text-muted-foreground text-xs font-medium">Sorotan</p>
                    {data.sorotan.map((s) => (
                        <div key={s.label} className="flex items-center gap-2">
                            <span className="text-muted-foreground min-w-0 flex-1 truncate text-sm">
                                {s.label}
                            </span>
                            <LencanaDelta arah={s.arah} delta={s.delta} deltaLabel={s.deltaLabel} />
                            {/* `nilai` sudah diformat server (mis. `2,4`) —
                                jangan diformat ulang di sini, atau dashboard dan
                                papan peninjau akan menyebut angka berbeda untuk
                                hal yang sama. */}
                            <span className="shrink-0 text-sm font-semibold tabular-nums">{s.nilai}</span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
