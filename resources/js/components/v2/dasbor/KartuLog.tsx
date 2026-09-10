import { Link } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { ScrollArea } from '@/components/ui-maia/scroll-area';
import { Ikon } from '@/components/v2/Ikon';
import { cn } from '@/lib/utils';
import type { BarisAktivitas } from '@/types/dasbor';

/**
 * "Aktivitas Terbaru" — garis waktu vertikal, digambar ulang (D10 §5g).
 *
 * Empat lapis per baris, dan tiap lapis menjawab satu pertanyaan:
 *   titik + judul  → APA yang terjadi   (`rona` + `aksi`, keduanya dari server)
 *   lencana        → peristiwa JENIS apa (`kategori`)
 *   keterangan     → oleh SIAPA, atas dokumen mana
 *   tanggal        → KAPAN (sudah ber-WITA dari server)
 *
 * Dibatasi 15 baris di server dan DIGULIR di dalam tinggi tetap: jumlah baris
 * tak menentukan tinggi halaman.
 *
 * Rona ikonnya mengikuti tata bahasa yang sudah dipakai Blade lama:
 *   maju   = dokumen MAJU selangkah
 *   netral = peristiwa rutin
 *   baik   = HASIL baik (disahkan)
 *   buruk  = HASIL buruk (ditolak/dihapus)
 * Kuncinya datang dari server (`activities[].rona`); yang di sini cuma
 * terjemahannya ke kelas tema — nol nama aksi audit diketik di TSX. Peta yang
 * sama, dengan kosakata yang sama, dipakai titik status `KartuMasukan` di
 * sebelahnya.
 *
 * `baik` memakai `--chart-5` (hijau), bukan `--chart-2`: di palet kategorikal
 * (§6) slot ke-2 adalah teal, dan "berhasil" yang berwarna teal berhenti
 * terbaca sebagai berhasil.
 *
 * LENCANANYA `kategori` — kata benda pendek yang dihitung `DasborTampilan`
 * dari peta `AKSI` yang sudah ada. Ia TIDAK boleh disimpulkan di sini dari
 * string `aksi` (CLAUDE.md §4): kalimat aksinya milik server, dan mengurainya
 * kembali di TSX berarti dua tempat yang harus sepakat setiap kali satu aksi
 * baru lahir.
 *
 * Kosakata `Completed / In progress / Upcoming` dari referensi sengaja TIDAK
 * dipakai: log aktivitas adalah peristiwa yang SUDAH terjadi, jadi ketiganya
 * akan selalu berbunyi "Completed" dan berhenti berarti apa-apa. Kosakata itu
 * dipakai di tempat yang benar — tahapan dokumen di baris bentangan
 * `LacakStatus` (§5b).
 */
const RONA: Record<string, string> = {
    maju: 'bg-primary text-primary-foreground',
    netral: 'bg-foreground text-background',
    baik: 'bg-chart-5 text-background',
    buruk: 'bg-destructive text-white',
};

export function KartuLog({ activities }: { activities: BarisAktivitas[] }) {
    return (
        <Card className="@container/card flex h-full flex-col">
            <CardHeader className="bg-muted/40 border-b">
                <CardTitle className="text-sm font-bold">Aktivitas Terbaru</CardTitle>
                <CardDescription>Apa yang terjadi belakangan ini</CardDescription>
            </CardHeader>
            <CardContent className="flex-1">
                {activities.length === 0 ? (
                    <Empty className="border-0">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <Ikon nama="bi-inbox" className="size-6" />
                            </EmptyMedia>
                            <EmptyTitle>Belum ada aktivitas</EmptyTitle>
                        </EmptyHeader>
                    </Empty>
                ) : (
                    <ScrollArea className="h-[22rem]">
                        <div className="pr-3">
                            {activities.map((log, i) => (
                                <div key={log.id} className="relative flex gap-3 pb-5">
                                    {/* Garis penyambung berhenti di baris terakhir —
                                        ekor yang menggantung tanpa titik berikutnya
                                        terbaca seperti daftar yang terpotong. */}
                                    {i < activities.length - 1 && (
                                        <span
                                            className="bg-border absolute top-8 bottom-0 left-[15px] w-px"
                                            aria-hidden="true"
                                        />
                                    )}
                                    <span
                                        className={cn(
                                            'z-10 flex size-8 shrink-0 items-center justify-center rounded-full',
                                            RONA[log.rona] ?? RONA.netral,
                                        )}
                                    >
                                        <Ikon nama={log.ikon} className="size-3.5" />
                                    </span>

                                    <div className="min-w-0 flex-1 pt-0.5">
                                        {/* Judul dan lencana berbagi satu baris yang
                                            BOLEH melipat: nomor dokumen terpanjang
                                            di proyek ini tak muat bersama lencana di
                                            kolom 1/3, dan lencana yang terdorong
                                            keluar kartu lebih buruk daripada dua
                                            baris. */}
                                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <p className="text-sm font-semibold">{log.aksi}</p>
                                            <Badge variant="outline" className="font-normal">
                                                {log.kategori}
                                            </Badge>
                                        </div>

                                        <p className="text-muted-foreground truncate text-xs">
                                            oleh {log.pelaku}
                                            {log.nomor ? ' · ' : ''}
                                            {log.nomor ? (
                                                log.tautan ? (
                                                    <Link
                                                        href={log.tautan}
                                                        className="hover:text-primary font-mono"
                                                    >
                                                        {log.nomor}
                                                    </Link>
                                                ) : (
                                                    <span className="font-mono">{log.nomor}</span>
                                                )
                                            ) : null}
                                        </p>

                                        <p className="text-muted-foreground mt-0.5 text-xs">{log.waktu}</p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </ScrollArea>
                )}
            </CardContent>
        </Card>
    );
}
