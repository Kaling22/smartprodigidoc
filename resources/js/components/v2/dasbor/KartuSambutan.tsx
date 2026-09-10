/**
 * KEMBARAN MAIA dari `resources/js/components/dasbor/KartuSambutan.tsx` (DASBOR-V2-REVISI §4a).
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
import { ArrowRight01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardAction, CardDescription, CardHeader, CardTitle } from '@/components/ui-maia/card';
import type { Hero } from '@/types/dasbor';

/**
 * Kartu sambutan — pengganti `.pp-hero` di `dashboard.blade.php`.
 *
 * Isinya SELURUHNYA dari props `hero`: kalimat "menunggu tindakanmu" dan
 * tombolnya berbeda per jabatan, dan aturan itu tinggal di
 * `App\Services\DasborTampilan` (pakem P5). Berkas ini cuma menggambar.
 *
 * Susunannya `Card` + `CardHeader`/`CardAction` dari registry, sama dengan
 * kartu `dashboard-01`. Ombak SVG oranye milik tema Soft UI lama sengaja TIDAK
 * ikut pindah: palet identitas kini `--primary` (keputusan Fase 3.0), dan
 * gradasi yang dipatok hex akan bertabrakan dengannya di mode gelap.
 */
export function KartuSambutan({ greeting, nama, hero }: { greeting: string; nama: string; hero: Hero }) {
    return (
        <Card className="@container/card bg-gradient-to-t from-primary/5 to-card shadow-xs dark:bg-card">
            <CardHeader>
                <CardDescription className="text-xs font-semibold tracking-wider uppercase">
                    {hero.jabatan}
                    {hero.dept ? ` · ${hero.dept}` : ''}
                </CardDescription>
                <CardTitle className="text-xl font-semibold @[540px]/card:text-2xl">
                    {greeting}, {nama}.
                </CardTitle>
                <CardAction className="text-right">
                    <div className="text-2xl font-bold tabular-nums">{hero.jam}</div>
                    <div className="text-xs text-muted-foreground">WITA · {hero.tanggal}</div>
                </CardAction>
                <p className="text-sm text-muted-foreground">
                    {hero.tunggu.length > 0 ? (
                        <>
                            Menunggu tindakanmu:{' '}
                            <span className="font-semibold text-foreground">{hero.tunggu.join(' · ')}</span>.
                        </>
                    ) : (
                        'Tidak ada yang menunggu tindakanmu. Selamat bekerja.'
                    )}
                </p>
                <div className="flex flex-wrap gap-2 pt-2">
                    {hero.aksi.map((a) => (
                        <Button key={a.label} asChild size="sm" variant={a.utama ? 'default' : 'outline'}>
                            <Link href={a.url}>
                                {a.label}
                                <HugeiconsIcon icon={ArrowRight01Icon} strokeWidth={1.5} className="size-4" />
                            </Link>
                        </Button>
                    ))}
                </div>
            </CardHeader>
        </Card>
    );
}
