import { Link } from '@inertiajs/react';

import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Ikon } from '@/components/v2/Ikon';
import { LencanaDelta } from '@/components/v2/dasbor/LencanaDelta';
import { cn } from '@/lib/utils';
import type { Tile } from '@/types/dasbor';

/**
 * Empat kartu ringkasan — dasar dari `section-cards.tsx` blok `dashboard-01`
 * di gaya maia. Inilah blok yang referensi pemilik sendiri turunkan (D8), jadi
 * susunan dasarnya tak dikarang.
 *
 * Yang BEDA dari blok, semuanya karena datanya nyata:
 *
 *  1. `px-4 lg:px-6` milik blok dilepas — talang itu sudah dipasang sekali di
 *     `AppLayout`, dan menuliskannya lagi menggandakan.
 *  2. Header dipisah `border-b` + `bg-muted/40` dari badan kartu (DASBOR-V4 F1,
 *     gambar 3) — beda dari blok yang menyatukan label+angka+lencana tanpa
 *     garis pemisah. Label jadi `CardTitle` (bold, kiri); ikon jenis kartu
 *     pindah ke `CardAction` (kanan) — TEMUAN-F8 W-6: judul tak pernah
 *     didahului ikon, ikon (bila ada) selalu di kanan.
 *  3. ~~SPARKLINE delapan bulan~~ — DICABUT (TEMUAN-F8 W-1). Panjang, lebar,
 *     dan tinggi kartu wajib sama dengan `docs/dasbor_ref/card.png`, dan
 *     sparkline-lah yang membuatnya ~2× lebih tinggi. Prop `t.deret` TETAP
 *     dikirim server (`DasborTampilan::tiles()`), sekadar tak digambar — kalau
 *     kelak dipakai lagi, datanya sudah ada tanpa menyentuh PHP.
 *  4. Kartu yang punya `url` dibungkus `<Link>`. Kartu tanpa `url` (mis.
 *     "Menunggu Ditinjau" bagi Non-Staff) TIDAK dibungkus — halamannya memang
 *     menolaknya 403, dan tautan yang berujung 403 lebih buruk daripada tak
 *     ada tautan.
 *  5. ~~Aksen warna per `rona`~~ — DICABUT (DASBOR-V2-REVISI §4c, D2). Batang
 *     tepi kiri & kotak ikon bernada dibuang bersama seluruh tile berona.
 *
 * LENCANANYA DATANYA NYATA, dari `audit_logs` (§5c, D8). Kartu yang tak punya
 * aksi audit yang jujur mewakilinya — "Perlu Diperiksa (MD)" — pulang dengan
 * `deltaLabel: null` dan TIDAK mendapat lencana. Lebih baik satu kartu tanpa
 * lencana daripada empat kartu yang salah satunya berbohong (§P2b).
 *
 * Tingginya kini seragam TANPA SYARAT: baris "lencana + vs 30 hari sebelumnya"
 * selalu tergambar, dan `LencanaDelta` yang render `null` (dua periode kosong)
 * cuma menghilangkan lencananya, bukan barisnya. Sebelum W-1, kartu tanpa
 * `deret` — mis. "Perlu Diperiksa (MD)" — kehilangan sparkline BESERTA
 * footernya dan jadi lebih pendek dari ketiga tetangganya. Angkanya sendiri
 * tetap `text-foreground` — mewarnai angka menurunkan keterbacaan justru pada
 * elemen yang paling dibaca.
 *
 * Beda dari versi nova: SELURUH kartu jadi target klik dengan umpan balik yang
 * terlihat (`hover:ring-primary/30`), bukan cuma bergeser setengah piksel.
 */
export function KartuStatistik({ tiles }: { tiles: Tile[] }) {
    return (
        <div className="@xl/main:grid-cols-2 @5xl/main:grid-cols-4 grid grid-cols-1 gap-4">
            {tiles.map((t) => {
                const kartu = (
                    <Card
                        className={cn(
                            // `--card-spacing` 24px → 16px: mekanismenya milik kit
                            // (`ui-maia/card.tsx`), jadi header tak lagi setinggi
                            // 72px tanpa satu byte pun berkas registry disunting
                            // (TEMUAN-F8 1a).
                            '@container/card relative h-full gap-0 transition-shadow [--card-spacing:--spacing(4)]',
                            t.url && 'group-hover:ring-primary/30 group-hover:ring-2',
                        )}
                    >
                        <CardHeader className="bg-muted/40 border-b">
                            <CardTitle className="truncate text-sm font-bold">{t.label}</CardTitle>
                            <CardAction>
                                <span className="bg-card flex size-7 shrink-0 items-center justify-center rounded-lg border">
                                    <Ikon nama={t.ikon} className="size-4" />
                                </span>
                            </CardAction>
                        </CardHeader>

                        <CardContent className="flex flex-col gap-1 pt-(--card-spacing)">
                            <CardTitle className="@[250px]/card:text-4xl text-3xl font-semibold tabular-nums">
                                {t.nilai}
                            </CardTitle>
                            <div className="flex items-center gap-1.5 text-xs">
                                <LencanaDelta
                                    arah={t.arah}
                                    delta={t.delta}
                                    deltaLabel={t.deltaLabel}
                                    polos
                                    className="text-xs"
                                />
                                <span className="text-muted-foreground">vs 30 hari sebelumnya</span>
                            </div>
                        </CardContent>
                    </Card>
                );

                return t.url ? (
                    <Link key={t.label} href={t.url} className="group block">
                        {kartu}
                    </Link>
                ) : (
                    <div key={t.label}>{kartu}</div>
                );
            })}
        </div>
    );
}
