import {
    ArrowLeft01Icon, Clock01Icon, FileEditIcon, InformationCircleIcon, Message01Icon, PencilIcon,
    RadioIcon, RefreshCwIcon, ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, usePage } from '@inertiajs/react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Avatar } from '@/components/v2/Avatar';
import { DaftarMasukan } from '@/components/v2/DaftarMasukan';
import { PitaCakupan } from '@/components/v2/dasbor/PitaCakupan';
import { DialogMasukan, DialogRevisi } from '@/components/v2/DialogAksi';
import { Ikon } from '@/components/v2/Ikon';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { RincianPembaca } from '@/components/v2/RincianPembaca';
import { StatusBadge } from '@/components/v2/StatusBadge';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { DocumentsShowProps, PeristiwaTimeline } from '@/types/dokumen';

/**
 * Detail dokumen — kembaran V2 `pages/Documents/Show.tsx`.
 *
 * Tiga panel di bawah kepala, dan urutannya disengaja: **Timeline** menjawab
 * "apa yang sudah terjadi PADA dokumen ini", **Distribusi** menjawab "apa yang
 * terjadi SETELAHNYA, di luar sana", dan **Masukan Lapangan** menyatu dengan
 * alur revisi — dari sana masukan langsung bisa dicentang jadi alasan revisi
 * (FITUR-BARU-v4 §5). Kotak-masuk terpisah membuat SH berpindah-pindah halaman
 * dan masukannya mudah terlupakan.
 *
 * Halaman PERTAMA yang memasang `v2/DialogAksi`, `v2/DaftarMasukan`, dan
 * `v2/RincianPembaca` — ketiganya lahir di Fase 1 tanpa pengimpor, jadi gerbang
 * render belum pernah benar-benar menggambarnya (PATOKAN-GAYA-V2 §9).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.5 — peta `RONA` timeline (kosakata Bootstrap lama dari
 *    `AuditLog::AKSI_META`) tak lagi memakai `sky-600`/`emerald-600`/
 *    `amber-500` melainkan token palet `.ui-v2`. Nol warna baru, dan yang
 *    dipetakan tetap NAMA rona dari server — bukan status yang ditebak di TSX.
 */
export default function DocumentsShow() {
    const {
        document: doc, timeline, masukan, masukanBelumDitindak, bolehMembalasMasukan,
        bolehLihatMasukan, distribusi, distribusiRincian, ketersediaanPembuat,
    } = usePage<PageProps & DocumentsShowProps>().props;

    const panelMasukan = bolehLihatMasukan || doc.boleh_beri_masukan || masukan.length > 0;

    return (
        <AppLayout
            judul={`Detail: ${doc.nomor}`}
            aksi={
                <>
                    <Button asChild variant="outline" size="sm">
                        <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                            <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                            Lihat PDF
                        </a>
                    </Button>
                    {/* Dokumen LAMA tak punya formulir berbab — isinya ada di
                        berkasnya. "Buka Form" digantikan "Perbaiki" bagi yang berhak. */}
                    {doc.arsip ? (
                        doc.boleh_edit_arsip ? (
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('documents.arsip.edit', doc.id)}>
                                    <HugeiconsIcon icon={PencilIcon} strokeWidth={1.5} className="size-4" />
                                    Perbaiki
                                </Link>
                            </Button>
                        ) : null
                    ) : (
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('documents.edit', doc.id)}>
                                <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                                Buka Form
                            </Link>
                        </Button>
                    )}
                </>
            }
        >
            <div>
                {/* Kembali ke halaman SEBELUMNYA, bukan ke rute tetap: layar ini
                    dicapai dari enam daftar yang berbeda. `history.back()`
                    menggantikan `url()->previous()` Blade dan tak pernah bisa
                    berakhir 403 (CLAUDE.md §14). */}
                <button
                    type="button"
                    onClick={() => window.history.back()}
                    className="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline"
                >
                    <HugeiconsIcon
                        icon={ArrowLeft01Icon}
                        strokeWidth={1.5}
                        className="size-3.5"
                        aria-hidden="true"
                    />
                    Kembali
                </button>
                <h2 className="mt-1 text-lg font-bold">{doc.judul}</h2>
                <div className="flex flex-wrap items-center gap-2">
                    <NomorDokumen doc={doc} />
                    <StatusBadge status={doc.status} />
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-12">
                <Card className="lg:col-span-5">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <HugeiconsIcon
                                icon={InformationCircleIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Informasi Dokumen
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                            <Baris label="Jenis" nilai={doc.jenis_nama} />
                            <Baris label="Departemen" nilai={doc.dept_nama} />
                            <Baris label="No. Revisi" nilai={String(doc.no_revisi)} />
                            {/* Nama + jabatan + departemen, mis. "Angga - GL ICTMD". */}
                            <Baris label="Dibuat Oleh" nilai={doc.pembuat} />
                            <Baris label="Ditinjau Oleh" nilai={doc.peninjau} />
                            <Baris label="Disetujui Oleh" nilai={doc.penyetuju} />
                            <Baris label="Tgl Terbit" nilai={doc.terbit} />
                        </dl>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-7">
                    <CardHeader className="flex items-center justify-between">
                        <CardTitle className="flex items-center gap-2">
                            <HugeiconsIcon
                                icon={Clock01Icon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Timeline Riwayat
                        </CardTitle>
                        {timeline.length > 4 ? (
                            <Badge variant="secondary">{timeline.length} peristiwa</Badge>
                        ) : null}
                    </CardHeader>
                    {/* Tinggi dipatok ±4 peristiwa lalu digulir. Dokumen yang sudah
                        lama berjalan bisa punya belasan baris riwayat, dan kartu
                        yang memanjang itulah yang meninggalkan rongga di bawah
                        "Informasi Dokumen" di sebelahnya. */}
                    <CardContent className="max-h-56 overflow-y-auto">
                        {timeline.length === 0 ? (
                            <p className="text-muted-foreground text-sm">Belum ada riwayat.</p>
                        ) : (
                            timeline.map((log) => <Peristiwa key={log.id} log={log} />)
                        )}
                    </CardContent>
                </Card>

                {/* Panel DISTRIBUSI. `null` bagi yang tak berwenang = kartunya tak
                    dirender sama sekali, bukan dirender kosong. */}
                {distribusi && distribusiRincian ? (
                    <Card className="lg:col-span-12">
                        <CardHeader className="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={RadioIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Distribusi
                            </CardTitle>
                            <div className="min-w-56">
                                <PitaCakupan c={distribusi} />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <RincianPembaca
                                rincian={distribusiRincian}
                                cakupan={distribusi}
                                kosong="Tak ada pengguna aktif lain di departemen ini, jadi tak ada yang bisa diukur."
                            />
                        </CardContent>
                    </Card>
                ) : null}

                {panelMasukan ? (
                    <Card className="lg:col-span-12">
                        <CardHeader className="flex flex-wrap items-center justify-between gap-2">
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={Message01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Masukan Lapangan
                                {masukanBelumDitindak.length > 0 ? (
                                    <Badge variant="destructive">
                                        {masukanBelumDitindak.length} belum ditindak
                                    </Badge>
                                ) : null}
                            </CardTitle>
                            <div className="flex gap-2">
                                {doc.boleh_beri_masukan ? (
                                    <DialogMasukan
                                        doc={doc}
                                        masukanSaya={masukan}
                                        pemicu={
                                            <Button variant="outline" size="sm">
                                                <HugeiconsIcon
                                                    icon={Message01Icon}
                                                    strokeWidth={1.5}
                                                    className="size-4"
                                                />
                                                Beri Masukan
                                            </Button>
                                        }
                                    />
                                ) : null}
                                {doc.boleh_revisi ? (
                                    <DialogRevisi
                                        doc={doc}
                                        masukanRevisi={masukanBelumDitindak}
                                        jadwalPembuat={doc.pembuat_id ? (ketersediaanPembuat[doc.pembuat_id] ?? null) : null}
                                        pemicu={
                                            <Button variant="secondary" size="sm">
                                                <HugeiconsIcon
                                                    icon={RefreshCwIcon}
                                                    strokeWidth={1.5}
                                                    className="size-4"
                                                />
                                                Revisi
                                            </Button>
                                        }
                                    />
                                ) : null}
                            </div>
                        </CardHeader>
                        <CardContent>
                            <DaftarMasukan masukan={masukan} bolehMembalas={bolehMembalasMasukan} />
                        </CardContent>
                    </Card>
                ) : null}
            </div>
        </AppLayout>
    );
}

function Baris({ label, nilai }: { label: string; nilai: string | null }) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd>{nilai ?? '—'}</dd>
        </>
    );
}

/**
 * Satu peristiwa timeline.
 *
 * Label, ikon, dan rona datang dari props `meta` (`AuditLog::AKSI_META`) — tak
 * satu pun diketik di sini, alasan yang sama dengan `StatusBadge` (pakem P3).
 * Ronanya nama semantik Bootstrap lama (`primary`/`success`/…), dipetakan ke
 * kelas Tailwind di satu tempat di bawah — token palet `.ui-v2`, sejajar peta
 * `RONA` di `v2/dasbor/KartuLog.tsx` (PATOKAN-GAYA-V2 §3.5).
 */
const RONA: Record<string, string> = {
    primary: 'bg-primary text-primary-foreground',
    info: 'bg-chart-2 text-background',
    success: 'bg-chart-5 text-background',
    warning: 'bg-chart-3 text-background',
    danger: 'bg-destructive text-white',
    secondary: 'bg-muted-foreground text-background',
};

function Peristiwa({ log }: { log: PeristiwaTimeline }) {
    const [label, ikon, rona] = log.meta;

    return (
        <div className="relative flex gap-3 pb-4 last:pb-0">
            <span
                className={`flex size-7 shrink-0 items-center justify-center rounded-full ${
                    RONA[rona] ?? RONA.secondary
                }`}
            >
                <Ikon nama={ikon} className="size-3.5" />
            </span>
            <div className="min-w-0">
                <p className="text-sm font-semibold">{label}</p>
                <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                    {/* Wajah pelaku, bukan cuma namanya: timeline dibaca untuk
                        menjawab "siapa memegangnya", dan nama sepanjang tiga
                        kata di layar penuh peristiwa berhenti terbaca sebagai
                        orang. Foto `null` jatuh ke inisial di `Avatar`. */}
                    <Avatar nama={log.oleh} foto={log.foto} className="size-5" />
                    {log.oleh} · {log.waktu} WITA
                </p>
            </div>
        </div>
    );
}
