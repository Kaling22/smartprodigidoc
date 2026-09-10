import { Link } from '@inertiajs/react';
import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useState } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Item, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle } from '@/components/ui-maia/item';
import { ScrollArea } from '@/components/ui-maia/scroll-area';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui-maia/tabs';
import { PitaCakupan } from '@/components/v2/dasbor/PitaCakupan';
import { TumpukanWajah } from '@/components/v2/dasbor/TumpukanWajah';
import { Ikon } from '@/components/v2/Ikon';
import type { DistribusiWidget, PanelDistribusi } from '@/types/dasbor';

/**
 * "Distribusi & Keterbacaan".
 *
 * Yang diurutkan tetap CAKUPAN TERENDAH (diurutkan di SERVER), bukan yang
 * terbaru: kartu ini gunanya menemukan yang belum sampai ke orangnya.
 *
 * DUA SUMBER: Dokumen Mutu dan menu Informasi. Keduanya sudah ikut halaman dan
 * ditukar `Tabs` — nol request tambahan, nol kedipan. Saklarnya hanya muncul
 * bila memang ADA dua sumber: tab yang menuju panel kosong cuma menjebak.
 *
 * Barisnya sudah diseragamkan di `DashboardController`, jadi satu daftar
 * melayani kedua sumber — tak ada satu pun percabangan "kalau ini dokumen,
 * kalau ini informasi" di sini.
 *
 * Tombol "Lihat semua" mengikuti `bolehBukaHalaman` dari server: GL & Non-Staff
 * tak melihatnya, karena halaman Distribusi tetap menolak mereka 403.
 *
 * Bentuknya `Item`, bukan `Table`: empat kolom berpatokan persen memaksa nama
 * dokumen dipotong lebih awal daripada perlu, sementara kolom Unduhan yang
 * isinya satu digit tetap memakan 12% lebar. Cakupan dan unduhan kini duduk di
 * baris kedua tiap butir, tempat keduanya boleh selebar isinya.
 */
export function KartuDistribusi({ widget }: { widget: DistribusiWidget }) {
    const tersedia = (['mutu', 'informasi'] as const).filter((k) => widget[k]);
    const [sumber, setSumber] = useState<'mutu' | 'informasi'>(tersedia[0] ?? 'mutu');

    const panel = widget[sumber] as PanelDistribusi | null;
    const kata = sumber === 'mutu' ? 'dokumen' : 'informasi';

    return (
        <Card className="@container/card flex h-full min-h-[20rem] flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Distribusi &amp; Keterbacaan</CardTitle>
                <CardDescription>
                    {panel && panel.rendah > 0 ? (
                        <>
                            <span className="text-foreground font-semibold">
                                {panel.rendah} {kata}
                            </span>{' '}
                            belum terbaca separuh sasarannya
                        </>
                    ) : (
                        'Semua sudah terbaca lebih dari separuh sasarannya'
                    )}
                </CardDescription>
                <CardAction className="flex items-center gap-2">
                    {tersedia.length > 1 && (
                        <Tabs value={sumber} onValueChange={(v) => setSumber(v as 'mutu' | 'informasi')}>
                            <TabsList>
                                <TabsTrigger value="mutu">Dokumen Mutu</TabsTrigger>
                                <TabsTrigger value="informasi">Informasi</TabsTrigger>
                            </TabsList>
                        </Tabs>
                    )}
                    {widget.bolehBukaHalaman && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={sumber === 'informasi' ? widget.urlInformasi : widget.urlMutu}>
                                Lihat semua
                                <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                            </Link>
                        </Button>
                    )}
                </CardAction>
            </CardHeader>

            {/*
              | TINGGI KARTU INI DITENTUKAN TETANGGANYA, bukan oleh isinya.
              |
              | Kartu ini berbagi baris grid dengan `KartuSebaran` ("Sebaran
              | Jenis"). Keduanya `h-full`, jadi grid `align-items: stretch`
              | menyamakan tingginya — tapi yang MENENTUKAN tinggi baris adalah
              | kartu yang isinya paling tinggi, dan selama daftarnya digambar
              | penuh, kartu inilah yang menang: tujuh butir menumpuk ke bawah,
              | nol yang digulir, dan `KartuSebaran` yang ikut meregang
              | menyisakan ruang kosong besar di bawah legendanya.
              |
              | Dua percobaan sebelumnya sama-sama ditolak pemilik, dan
              | keduanya salah dengan cara yang sama: `h-[24rem]` tetap
              | (menyisakan kosong saat tetangganya lebih tinggi) lalu `h-full`
              | (tak membatasi apa pun, sebab tinggi kartunya sendiri yang
              | sedang ditentukan isi itu).
              |
              | Yang benar: keluarkan daftarnya dari ALIRAN tinggi. `absolute
              | inset-0` membuat gulirnya tak menyumbang satu piksel pun ke
              | tinggi `CardContent`, sehingga `flex-1` di sini melar mengikuti
              | sisa kartu dan tinggi barisnya sepenuhnya ditentukan
              | `KartuSebaran`. Daftar sepanjang apa pun digulir di dalamnya.
              | (Keputusan pemilik 2026-09-07, dikonfirmasi ulang.)
              |
              | `min-h-0` wajib menemaninya — tanpa itu anak flex menolak
              | menyusut di bawah tinggi isinya. Lantai keselamatannya duduk
              | di `Card` (`min-h-[20rem]`), BUKAN di sini: dua kelas
              | `min-h-*` pada satu elemen menulis properti CSS yang sama dan
              | yang menang ditentukan urutan stylesheet, bukan urutan yang
              | diketik. Ia cuma menahan kartu ini kolaps kalau suatu hari
              | `KartuSebaran` jadi lebih pendek darinya — donatnya saja sudah
              | 200px ditambah enam baris legenda, jadi dalam praktik tak
              | pernah aktif.
              |
              | Talang kiri-kanan pindah ke `ScrollArea` (`px-(--card-spacing)`,
              | token yang SAMA dengan `CardContent`) sebab `inset-0` mengukur
              | dari kotak BORDER, bukan kotak padding.
              */}
            <CardContent className="relative min-h-0 flex-1 px-0">
                {/* Kotak absolut ini WAJIB berupa `div` biasa, bukan
                    `ScrollArea` yang langsung diberi `absolute inset-0`.
                    `ui-maia/scroll-area.tsx` merakit kelasnya sebagai
                    `cn("relative", className)`, dan `relative` + `absolute`
                    menulis properti `position` yang SAMA — pemenangnya urutan
                    stylesheet, bukan urutan yang diketik. Di Tailwind v4
                    `relative` menang, `inset-0` jadi mati, dan gulirnya tak
                    pernah aktif sementara kartunya memanjang mengikuti isi.
                    Itu persis kegagalan yang dilaporkan pemilik. */}
                <div className="absolute inset-0">
                    <ScrollArea className="h-full px-(--card-spacing)">
                    <ItemGroup className="gap-2 pr-3">
                        {(panel?.baris ?? []).map((b) => (
                            <Item key={b.tautan} variant="outline" className="flex-wrap items-start">
                                <ItemMedia>
                                    <span
                                        className="flex size-9 shrink-0 items-center justify-center rounded-xl"
                                        style={{
                                            backgroundColor: `color-mix(in oklab, ${b.warna} 15%, transparent)`,
                                            color: b.warna,
                                        }}
                                        title={b.gelar}
                                    >
                                        <Ikon nama={b.ikon} className="size-4" />
                                    </span>
                                </ItemMedia>

                                {/* `min-w-0` wajib pada anak flex: tanpa itu ia
                                    menolak menyusut di bawah lebar teksnya dan
                                    `truncate` di dalamnya tak pernah aktif. */}
                                <ItemContent className="min-w-0 gap-1">
                                    <ItemTitle>
                                        <Link href={b.tautan} className="hover:text-primary truncate">
                                            {b.judul}
                                        </Link>
                                    </ItemTitle>
                                    <ItemDescription>{b.sub}</ItemDescription>
                                    <div className="mt-1 flex items-center gap-3">
                                        <div className="min-w-32 flex-1">
                                            <PitaCakupan c={b.c} ringkas />
                                        </div>
                                        <span
                                            className="text-muted-foreground shrink-0 text-xs tabular-nums"
                                            title="Berapa kali PDF-nya diunduh"
                                        >
                                            {b.c.unduhan} unduhan
                                        </span>
                                        <TumpukanWajah pembaca={b.pembaca} />
                                    </div>
                                </ItemContent>
                            </Item>
                        ))}
                    </ItemGroup>
                    </ScrollArea>
                </div>
            </CardContent>
        </Card>
    );
}
