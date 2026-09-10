import { ListViewIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { Ikon } from '@/components/v2/Ikon';
import {
    PenyaringDokumen, saringDepartemen, type PilihanSaring,
} from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisPesan, LogPesanProps } from '@/types/log';

/**
 * "Log Pesan" — kembaran V2 `pages/Log/Pesan.tsx`.
 *
 * Setiap pesan yang pernah ditulis atas sebuah dokumen, dari enam sumber yang
 * datanya SUDAH tersimpan tapi belum pernah bisa dibaca di satu tempat:
 * pengajuan revisi, penolakan peninjau/MD/penyetuju, pengalihan peninjauan JSA,
 * pengajuan & penolakan nonaktif, alasan PEMUSNAHAN (dokumennya sudah lenyap,
 * jadi nomor & judulnya dibaca dari audit), balasan atas masukan lapangan, dan
 * masukan sejawat antar-GL.
 *
 * BACA SAJA — tak ada satu pun tombol yang mengubah status di halaman ini.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring ke `CardHeader className="border-b"`, tabel ke
 *    `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t
 *    pt-6"` — SATU kartu (arketipe `V2/Documents/Index.tsx`).
 *  · §3.6 — kalimat pengantar pindah ke prop `sub` layout.
 *  · §3.4 — ikon keadaan kosong diimpor langsung; ikon TOMBOL tiap baris tetap
 *    lewat `v2/Ikon` karena kuncinya datang dari SERVER (`tautan.ikon`).
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function LogPesan() {
    const { pesan, filters, tahapan, departments, prefix } =
        usePage<PageProps & LogPesanProps>().props;

    const pilihan: PilihanSaring[] = [
        {
            nama: 'tahap',
            label: 'Tahap',
            opsi: [
                ['', 'Semua'],
                ...Object.entries(tahapan).map(([k, [label]]): [string, string] => [k, label]),
            ],
        },
        ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
        { nama: 'dari', label: 'Dari', tipe: 'tanggal' },
        { nama: 'sampai', label: 'Sampai', tipe: 'tanggal' },
    ];

    const kolom: Kolom<BarisPesan>[] = [
        {
            judul: 'Tanggal',
            kelas: 'whitespace-nowrap',
            render: (b) => <span className="text-sm">{b.tanggal} WITA</span>,
        },
        {
            judul: 'Dokumen',
            render: (b) => (
                <span className="text-sm">
                    <span className="block font-mono font-semibold">{b.nomor}</span>
                    <span className="text-muted-foreground">
                        {b.judul}{' '}
                        <Badge variant="secondary" className="font-normal">
                            {b.dept}
                        </Badge>
                    </span>
                </span>
            ),
        },
        {
            judul: 'Tahap',
            render: (b) => {
                const [label, warna] = tahapan[b.tahap] ?? [b.tahap, 'secondary'];

                return <Badge variant={rona(warna)}>{label}</Badge>;
            },
        },
        { judul: 'Oleh', render: (b) => <span className="text-sm">{b.oleh ?? '—'}</span> },
        { judul: 'Pesan', kelas: 'max-w-104', render: (b) => <Alasan alasan={b.alasan} /> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (b) =>
                /*
                 | Tujuan, label, DAN ikonnya seluruhnya dari controller
                 | (`DocumentLogController::tautan`): ia bergantung pada
                 | PENONTON, bukan hanya tahap — alasan penolakan yang sama
                 | mengantar PEMBUATNYA ke form revisi dan orang lain ke halaman
                 | dokumen. Peta kedua sengaja TIDAK ditaruh di sini: dua peta
                 | yang harus sepakat adalah dua peta yang suatu hari tidak
                 | sepakat. `null` = tanpa tombol (baris pemusnahan).
                 */
                b.tautan ? (
                    <Button asChild variant="outline" size="sm">
                        <Link href={b.tautan.url}>
                            <Ikon nama={b.tautan.ikon} className="size-4" />
                            {b.tautan.label}
                        </Link>
                    </Button>
                ) : null,
        },
    ];

    return (
        <AppLayout
            judul="Log Pesan"
            sub={`Alasan revisi, penolakan, pengalihan, pemusnahan, dan balasan masukan ${
                departments.length > 0 ? 'di 7 departemen' : 'di departemen Anda'
            }. Baca saja.`}
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('log.pesan')}
                        filters={filters}
                        labelCari="Cari (pesan / nomor / judul)"
                        placeholderCari={`mis. APD atau ${prefix}-SOP...`}
                        pilihan={pilihan}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={pesan.data}
                        kunci={(b, i) => `${b.tahap}-${b.nomor}-${i}`}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon icon={ListViewIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada pesan tercatat</EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>

                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={pesan} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}

/**
 * Warna tahap datang dari `DocumentLogController::TAHAP` sebagai nama rona
 * Bootstrap; yang dipetakan di sini hanyalah rona itu ke `variant` Badge
 * registry — bukan warnanya yang diketik ulang (keputusan Fase 6.3 butir 2).
 */
function rona(warna: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (warna === 'danger') return 'destructive';
    if (warna === 'primary') return 'default';
    if (warna === 'secondary') return 'outline';

    return 'secondary';
}

/** Pesan dipotong 140 karakter; selebihnya dibuka `<details>` — nol JS. */
function Alasan({ alasan }: { alasan: string }) {
    if (alasan.length <= 140) {
        return <span className="text-sm">{alasan}</span>;
    }

    return (
        <details className="text-sm">
            <summary className="cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                {alasan.slice(0, 140)}…
                <span className="text-muted-foreground text-xs"> selengkapnya</span>
            </summary>
            <div className="mt-1 whitespace-pre-line">{alasan}</div>
        </details>
    );
}
