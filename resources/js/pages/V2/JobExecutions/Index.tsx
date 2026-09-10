import { ClipboardXIcon, Delete02Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Progress } from '@/components/ui-maia/progress';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { PenyaringDokumen } from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisPekerjaan, JobExecutionsIndexProps } from '@/types/log';

/**
 * "Riwayat Pekerjaan JSA" — kembaran V2 `pages/JobExecutions/Index.tsx`.
 *
 * Cerminan web atas apa yang dicentang lapangan lewat `/api/pekerjaan/*`.
 * Lingkupnya dibatasi DEPARTEMEN di controller (Admin & PJO lintas departemen)
 * — nol penyaringan di klien.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring yang di V1 berdiri LEPAS di atas kartu tabel masuk ke
 *    `CardHeader className="border-b"` kartu itu, tabel ke `CardContent
 *    className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` —
 *    SATU kartu (arketipe `V2/Documents/Index.tsx`). `CardFooter pt-0` V1 ikut
 *    lepas: angkanya milik kit.
 *  · §3.6 — kalimat pengantar pindah ke prop `sub` layout.
 *  · §3.4 — ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons`,
 *    `strokeWidth={1.5}` + `size-4`.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 *
 * Kolom Aksi TETAP satu tombol berdiri, bukan `StripAksi`: satu aksi yang
 * dikubur di balik menu hanya menambah satu klik (preseden `V2/Users/Pending`).
 */
export default function JobExecutionsIndex() {
    const { pekerjaan, filters, statusLabels, bolehHapus } =
        usePage<PageProps & JobExecutionsIndexProps>().props;

    const kolom: Kolom<BarisPekerjaan>[] = [
        {
            judul: 'Nama Pekerjaan',
            render: (j) => (
                <span>
                    <span className="block font-medium">{j.nama}</span>
                    {j.catatan ? (
                        <span
                            className="text-muted-foreground block max-w-50 truncate text-sm"
                            title={j.catatan}
                        >
                            {j.catatan}
                        </span>
                    ) : null}
                </span>
            ),
        },
        {
            judul: 'JSA Acuan',
            render: (j) => (
                <span>
                    <span className="block text-sm font-medium">{j.jsa_judul ?? '—'}</span>
                    <span className="text-muted-foreground block text-xs">
                        {j.jsa_nomor ?? '—'}
                        {j.jsa_dept ? ` · ${j.jsa_dept}` : ''}
                    </span>
                </span>
            ),
        },
        { judul: 'Pelaksana', render: (j) => j.pelaksana ?? '—' },
        {
            judul: 'NRP',
            render: (j) => (
                <Badge variant="secondary" className="font-normal">
                    {j.nrp ?? '—'}
                </Badge>
            ),
        },
        { judul: 'Lokasi', render: (j) => <span className="text-sm">{j.lokasi ?? '—'}</span> },
        {
            judul: 'Tanggal',
            kelas: 'whitespace-nowrap',
            render: (j) => <span className="text-sm">{j.tanggal ?? '—'}</span>,
        },
        { judul: 'Progres', kelas: 'min-w-28', render: (j) => <BarProgres job={j} /> },
        {
            judul: 'Status',
            render: (j) => <Badge variant={rona(j.status)}>{j.status_label}</Badge>,
        },
        ...(bolehHapus
            ? [
                  {
                      judul: 'Aksi',
                      kelas: 'w-px text-right whitespace-nowrap',
                      render: (j: BarisPekerjaan) => (
                          <ConfirmDialog
                              judul="Hapus Riwayat Pekerjaan?"
                              pesan={`Hapus riwayat pekerjaan "${j.nama}"? Data yang dihapus tidak dapat dikembalikan.`}
                              tombolYa="Ya, hapus"
                              destruktif
                              onKonfirmasi={() =>
                                  router.delete(route('job-executions.destroy', j.id), {
                                      preserveScroll: true,
                                  })
                              }
                              pemicu={
                                  <Button variant="outline" size="icon" title="Hapus riwayat">
                                      <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                                  </Button>
                              }
                          />
                      ),
                  } satisfies Kolom<BarisPekerjaan>,
              ]
            : []),
    ];

    return (
        <AppLayout
            judul="Riwayat Pekerjaan JSA"
            sub="Daftar pekerjaan lapangan yang menggunakan formulir JSA — se-departemen Anda."
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('job-executions.index')}
                        filters={filters}
                        labelCari="Cari (nama pekerjaan / lokasi / user / JSA)"
                        placeholderCari="mis. pengelasan, Area 3B, GL-0001..."
                        pilihan={[
                            {
                                nama: 'status',
                                label: 'Status',
                                opsi: [['', 'Semua'], ...Object.entries(statusLabels)],
                            },
                            { nama: 'tanggal_dari', label: 'Tanggal dari', tipe: 'tanggal' },
                            { nama: 'tanggal_sampai', label: 'Tanggal sampai', tipe: 'tanggal' },
                        ]}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={pekerjaan.data}
                        kunci={(j) => j.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon icon={ClipboardXIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada riwayat pekerjaan</EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>

                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={pekerjaan} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}

/**
 * Rona status memakai `variant` Badge registry, bukan warna karangan
 * (keputusan Fase 6.3 butir 2). LABELNYA datang dari server
 * (`JobExecution::STATUS_LABELS`), jadi tak ada teks status yang diketik di sini.
 */
function rona(status: string): 'default' | 'secondary' | 'outline' {
    if (status === 'selesai') return 'default';
    if (status === 'dibatalkan') return 'outline';

    return 'secondary';
}

/**
 * Bar progres checklist. Pekerjaan yang DIBATALKAN tak punya bar sama sekali —
 * bukan bar kosong: 0% menuduh pelaksananya belum selesai, padahal pekerjaannya
 * memang tak jadi dikerjakan.
 */
function BarProgres({ job }: { job: BarisPekerjaan }) {
    if (job.status === 'dibatalkan') {
        return <span className="text-muted-foreground text-sm">—</span>;
    }

    const { selesai, total } = job.progres;
    const persen = total > 0 ? Math.round((selesai / total) * 100) : 0;

    return (
        <div className="flex items-center gap-1.5">
            <Progress value={persen} className="h-1.5 flex-1" />
            <span className="text-muted-foreground text-xs whitespace-nowrap">
                {selesai}/{total}
            </span>
        </div>
    );
}
