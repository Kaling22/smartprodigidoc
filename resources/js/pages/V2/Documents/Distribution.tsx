import {
    Alert02Icon, ChevronDownIcon, ChevronUpIcon, HierarchySquare01Icon, RadioIcon, UserMultipleIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader } from '@/components/ui-maia/card';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { PitaCakupan } from '@/components/v2/dasbor/PitaCakupan';
import { DataTable, type Kolom } from '@/components/v2/DataTable';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import {
    PenyaringDokumen, saringDepartemen, saringJenis, type PilihanSaring,
} from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type {
    BarisDistribusiDokumen, BarisDistribusiInformasi, DistribusiProps,
} from '@/types/distribusi';

/**
 * Menu DISTRIBUSI — kembaran V2 `pages/Documents/Distribution.tsx`.
 *
 * Diurutkan cakupan MENAIK: halaman ini ada untuk menemukan yang belum sampai.
 * Kalau yang paling sukses ditaruh di puncak, daftarnya enak dipandang tapi tak
 * berguna — yang perlu ditindaklanjuti justru terkubur di dasar. Pengurutan itu
 * dikerjakan server, dan menekan judul kolom membatalkannya (permintaan pembaca
 * yang menang).
 *
 * SATU komponen, DUA sumber — sama seperti Blade-nya, dan sama alasannya:
 * penjaga akses, saklar sumber, kartu peringatan, dan pita cakupannya identik;
 * yang benar-benar berbeda cuma KOLOM tabelnya. Tabel informasi tetap dipecah
 * ke komponennya sendiri di bawah, persis seperti partial yang digantikannya.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — keterangan halaman & saklar sumber pindah ke prop `sub`/`aksi`
 *    layout. Barisnya memang sudah "keterangan kiri, kendali kanan"; layout V2
 *    menggambar baris itu sekali untuk semua halaman.
 *  · §3.2 — pita peringatan `rounded-lg` → `rounded-2xl`, sepadan `card`.
 *  · §3.7 — penyaring masuk ke `CardHeader` tabelnya, satu kartu bukan dua;
 *    sel aksi kehilangan `flex-wrap` (REVISI-UI-V3 R4) — kolomnya `w-px`, jadi
 *    membungkus dua tombol jadi dua baris hanya menaikkan tinggi baris.
 */
export default function DocumentsDistribution() {
    const props = usePage<PageProps & DistribusiProps>().props;
    const adalahInformasi = props.sumber === 'informasi';

    return (
        <AppLayout
            judul={`Distribusi ${adalahInformasi ? 'Informasi' : 'Dokumen'}`}
            sub={
                <>
                    {adalahInformasi ? (
                        <>
                            {/* Informasi berlaku lintas 7 departemen; yang menyempit bagi
                                SH/DH bukan daftarnya melainkan ORANG yang dihitung
                                sebagai sasaran (keputusan C1). */}
                            Seberapa jauh kebijakan, memo, dan poster sudah dibuka orang{' '}
                            {props.canAll ? 'di 7 departemen' : 'di departemen Anda'}.
                        </>
                    ) : (
                        <>
                            Seberapa jauh dokumen Berlaku sudah dibaca orang{' '}
                            {props.canAll ? 'di 7 departemen' : 'di departemen Anda'}.
                        </>
                    )}{' '}
                    Diurutkan dari yang <strong>paling sedikit</strong> dibaca.
                </>
            }
            aksi={
                /* Saklar sumber = dua TAUTAN biasa, bukan saklar klien: halaman
                   ini toh dimuat ulang saat menyaring, dan tautannya bisa
                   ditandai & dibagikan. */
                <nav className="flex gap-1" aria-label="Sumber distribusi">
                    <Button asChild size="sm" variant={adalahInformasi ? 'outline' : 'secondary'}>
                        <Link href={route('documents.distribution')}>Dokumen Mutu</Link>
                    </Button>
                    <Button asChild size="sm" variant={adalahInformasi ? 'secondary' : 'outline'}>
                        <Link href={route('documents.distribution', { sumber: 'informasi' })}>Informasi</Link>
                    </Button>
                </nav>
            }
        >
            {props.rendah > 0 ? (
                <p className="border-chart-3/40 bg-chart-3/10 flex items-center gap-2 rounded-2xl border px-3 py-2 text-sm">
                    <HugeiconsIcon
                        icon={Alert02Icon}
                        strokeWidth={1.5}
                        className="size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <span>
                        <strong>
                            {props.rendah} {adalahInformasi ? 'informasi' : 'dokumen'}
                        </strong>{' '}
                        baru dibaca kurang dari separuh orang yang seharusnya tahu.
                    </span>
                </p>
            ) : null}

            {props.sumber === 'informasi' ? <TabelInformasi {...props} /> : <TabelDokumen {...props} />}
        </AppLayout>
    );
}

/** Cabang bawaan: dokumen mutu. Kolomnya nomor/judul/jenis/dept. */
function TabelDokumen(props: Extract<DistribusiProps, { sumber: 'mutu' }>) {
    const pilihan: PilihanSaring[] = [saringJenis(props.jenis)];

    if (props.departments.length > 0) {
        pilihan.push(saringDepartemen(props.departments));
    }

    const kolom: Kolom<BarisDistribusiDokumen>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => d.judul },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Cakupan', kelas: 'min-w-44', render: (d) => <PitaCakupan c={d.cakupan} /> },
        {
            judul: 'Unduhan',
            kelas: 'text-center',
            // Unduhan dipisah dari cakupan: cakupan = berapa ORANG, unduhan =
            // berapa KALI. Dokumen yang dibuka 40 kali oleh 2 orang belum
            // tersebar.
            render: (d) => <span className="text-muted-foreground text-sm">{d.cakupan.unduhan}×</span>,
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => (
                <Button asChild size="sm" variant="outline" title="Rincian pembaca">
                    <Link href={route('documents.show', d.id)}>
                        <HugeiconsIcon icon={UserMultipleIcon} strokeWidth={1.5} className="size-4" />
                        Rincian
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <Card>
            <CardHeader className="border-b">
                <PenyaringDokumen
                    url={route('documents.distribution')}
                    filters={props.filters}
                    prefix={props.prefix}
                    pilihan={pilihan}
                />
            </CardHeader>
            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={props.documents}
                    kunci={(d) => d.id}
                    kosong={<Kosong teks="Belum ada dokumen Berlaku yang cocok dengan filter ini." />}
                />
            </CardContent>
        </Card>
    );
}

/**
 * Cabang `?sumber=informasi` — pengganti `_distribusi-informasi.blade.php`.
 *
 * Komponen sendiri, bukan `if` raksasa di dalam halaman: kolomnya memang
 * berbeda (kategori, bukan jenis & departemen), dan dua tabel bertumpuk di satu
 * berkas membuat keduanya sulit diubah tanpa merusak yang lain.
 */
function TabelInformasi(props: Extract<DistribusiProps, { sumber: 'informasi' }>) {
    const [terbuka, setTerbuka] = useState<number | null>(null);

    const kolom: Kolom<BarisDistribusiInformasi>[] = [
        { judul: 'Nomor', render: (i) => <span className="font-mono text-sm">{i.nomor}</span> },
        {
            judul: 'Judul',
            render: (i) => (
                <>
                    {i.judul}
                    {i.revisi ? <span className="text-muted-foreground text-sm"> · {i.revisi}</span> : null}
                </>
            ),
        },
        { judul: 'Kategori', render: (i) => <Badge variant="outline">{i.kategori}</Badge> },
        { judul: 'Cakupan', kelas: 'min-w-44', render: (i) => <PitaCakupan c={i.cakupan} /> },
        {
            judul: 'Unduhan',
            kelas: 'text-center',
            render: (i) => <span className="text-muted-foreground text-sm">{i.cakupan.unduhan}×</span>,
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            // Dua tombol, dua pertanyaan berbeda: penyingkap menjawab
            // "departemen mana yang tertinggal", Rincian menjawab "siapa
            // orangnya". Rincian ada untuk SEMUA yang berwenang.
            render: (i) => (
                <div className="flex justify-end gap-1">
                    {i.perDept.length > 0 ? (
                        <Button
                            size="sm"
                            variant="outline"
                            aria-expanded={terbuka === i.id}
                            onClick={() => setTerbuka(terbuka === i.id ? null : i.id)}
                        >
                            <HugeiconsIcon icon={HierarchySquare01Icon} strokeWidth={1.5} className="size-4" />
                            Per Departemen
                            <HugeiconsIcon
                                icon={terbuka === i.id ? ChevronUpIcon : ChevronDownIcon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                        </Button>
                    ) : null}
                    <Button asChild size="sm" variant="outline" title="Siapa yang sudah & belum membuka">
                        <Link href={route('documents.rincianInformasi', i.id)}>
                            <HugeiconsIcon icon={UserMultipleIcon} strokeWidth={1.5} className="size-4" />
                            Rincian
                        </Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <Card>
            <CardHeader className="border-b">
                <PenyaringDokumen
                    url={route('documents.distribution')}
                    filters={props.filters}
                    contoh="nomor kebijakan"
                    tersembunyi={{ sumber: 'informasi' }}
                    pilihan={[
                        {
                            nama: 'kategori',
                            label: 'Kategori',
                            opsi: [['', 'Semua'], ...Object.entries(props.kategoriOpsi)],
                        },
                    ]}
                />
            </CardHeader>
            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={props.informasi}
                    kunci={(i) => i.id}
                    kosong={<Kosong teks="Belum ada informasi berlaku yang cocok dengan filter ini." />}
                    perluas={(i) =>
                        terbuka === i.id && i.perDept.length > 0 ? (
                            <div>
                                <div className="grid gap-2 py-1 md:grid-cols-2 xl:grid-cols-3">
                                    {i.perDept.map((d) => (
                                        <div key={d.dept} className="flex items-center gap-2">
                                            <Badge variant="outline" className="min-w-18 justify-center">
                                                {d.dept}
                                            </Badge>
                                            <div className="flex-1">
                                                <PitaCakupan c={d} ringkas />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    Rincian ini hanya menghitung orang yang punya departemen, jadi jumlahnya bisa
                                    lebih kecil daripada angka gabungan di kolom Cakupan.
                                </p>
                            </div>
                        ) : null
                    }
                />
            </CardContent>
        </Card>
    );
}

function Kosong({ teks }: { teks: string }) {
    return (
        <Empty className="border-0">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <HugeiconsIcon icon={RadioIcon} strokeWidth={1.5} className="size-6" />
                </EmptyMedia>
                <EmptyTitle>{teks}</EmptyTitle>
            </EmptyHeader>
        </Empty>
    );
}
