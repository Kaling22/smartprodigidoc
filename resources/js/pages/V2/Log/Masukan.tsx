import { InboxIcon, RefreshCwIcon, ReplyIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui-maia/card';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui-maia/dialog';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import {
    Field, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { DialogRevisi } from '@/components/v2/DialogAksi';
import {
    PenyaringDokumen, saringDepartemen, type PilihanSaring,
} from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisLogMasukan, LogMasukanProps, RevisiDariMasukan } from '@/types/log';

/**
 * "Masukan Lapangan" — kembaran V2 `pages/Log/Masukan.tsx`.
 *
 * Seluruh masukan dalam SATU halaman, menggantikan tempelan yang tercecer di
 * lencana Dokumen Berlaku, panel halaman dokumen, dan kartu dashboard.
 *
 * Dua tombol aksinya meminjam alur yang sudah ada — balas (`feedback.respond`)
 * dan `DialogRevisi` yang sama dengan Dokumen Berlaku sejak Fase 8 — jadi
 * halaman ini tak memperkenalkan satu pun aturan wewenang baru. Siapa boleh apa
 * datang sebagai `boleh_balas` / `boleh_revisi` per baris, dijawab model.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring ke `CardHeader className="border-b"`, tabel ke
 *    `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t
 *    pt-6"` — SATU kartu (arketipe `V2/Documents/Index.tsx`).
 *  · §3.6 — kalimat pengantar pindah ke prop `sub` layout.
 *  · §3.9 — kotak balasan: `Label` + `Textarea` + `<p className="text-destructive">`
 *    → `FieldGroup` > `Field` > `FieldLabel` + `Textarea` + `FieldError`,
 *    lengkap `aria-invalid` pada kendali dan `data-invalid` pada `Field`.
 *  · §3.2 — kotak kutipan masukan di dalam jendela Balas `rounded-lg` →
 *    `rounded-2xl` (kotak buatan sendiri yang berperan sebagai kartu).
 *  · §3.4 — tiap ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}` +
 *    `size-4`; keadaan kosong `size-6` di dalam `EmptyMedia variant="icon"`.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 *
 * Kolom Aksi TETAP dua tombol berdiri, bukan `StripAksi`: dua aksi belum layak
 * dikubur satu klik lebih dalam, dan keduanya justru alasan layar ini dibuka
 * (preseden `V2/Users/Pending`).
 */
export default function LogMasukan() {
    const { masukan, revisi, filters, departments, statusOpsi, bolehMembalas, ketersediaanPembuat } =
        usePage<PageProps & LogMasukanProps>().props;

    // Jendela Revisi dirender DI LUAR tabel dan dikendalikan dari sini: satu
    // per DOKUMEN (beberapa masukan bisa menunjuk dokumen yang sama), persis
    // seperti `groupBy('document_id')` di Blade lama.
    const [revisiBuka, setRevisiBuka] = useState<number | null>(null);

    const pilihan: PilihanSaring[] = [
        { nama: 'status', label: 'Status', opsi: [['', 'Semua'], ...Object.entries(statusOpsi)] },
        ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
    ];

    const kolom: Kolom<BarisLogMasukan>[] = [
        { judul: 'No. Masukan', render: (m) => <span className="font-mono text-sm">{m.nomor}</span> },
        {
            judul: 'Dokumen',
            render: (m) =>
                m.dokumen ? (
                    <span className="text-sm">
                        <Link
                            href={route('documents.show', m.dokumen.id)}
                            className="font-semibold hover:underline"
                        >
                            {m.dokumen.nomor}
                        </Link>
                        <span className="text-muted-foreground block">{m.dokumen.judul}</span>
                    </span>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
        { judul: 'Isi', kelas: 'max-w-88', render: (m) => <IsiMasukan m={m} /> },
        { judul: 'Pengirim', render: (m) => <span className="text-sm">{m.oleh ?? '—'}</span> },
        {
            judul: 'Tanggal',
            kelas: 'whitespace-nowrap',
            render: (m) => <span className="text-sm">{m.waktu} WITA</span>,
        },
        { judul: 'Status', render: (m) => <Badge variant={rona(m.status)}>{m.status_label}</Badge> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (m) => (
                <div className="flex items-center justify-end gap-2">
                    {m.boleh_balas ? <DialogBalas m={m} /> : null}
                    {m.boleh_revisi && m.dokumen ? (
                        <TombolRevisi id={m.dokumen.id} onBuka={setRevisiBuka} />
                    ) : null}
                </div>
            ),
        },
    ];

    return (
        <AppLayout
            judul="Masukan Lapangan"
            sub={`Masukan orang lapangan atas dokumen Berlaku ${
                departments.length > 0 ? 'di 7 departemen' : 'di departemen Anda'
            }. ${
                bolehMembalas
                    ? 'Balas untuk menutup, atau Revisi untuk menjadikannya bahan perbaikan.'
                    : 'Ditindaklanjuti penyusun dokumen (GL) atau Management Development.'
            }`}
        >
            <Card>
                <CardHeader className="border-b">
                    <PenyaringDokumen
                        url={route('log.masukan')}
                        filters={filters}
                        labelCari="Cari (isi / nomor masukan)"
                        placeholderCari="mis. MSK-2026 atau kata di isinya..."
                        pilihan={pilihan}
                    />
                </CardHeader>

                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={masukan.data}
                        kunci={(m) => m.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon icon={InboxIcon} strokeWidth={1.5} className="size-6" />
                                    </EmptyMedia>
                                    <EmptyTitle>Belum ada masukan lapangan</EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>

                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={masukan} />
                </CardFooter>
            </Card>

            {revisi.map((r) => (
                <JendelaRevisi
                    key={r.id}
                    r={r}
                    jadwal={r.pembuat_id ? (ketersediaanPembuat[r.pembuat_id] ?? null) : null}
                    buka={revisiBuka === r.id}
                    onUbahBuka={(b) => setRevisiBuka(b ? r.id : null)}
                />
            ))}
        </AppLayout>
    );
}

/** Menyalakan jendela Revisi milik dokumen ini; jendelanya berdiri di luar tabel. */
function TombolRevisi({ id, onBuka }: { id: number; onBuka: (id: number) => void }) {
    return (
        <Button variant="secondary" size="sm" onClick={() => onBuka(id)}>
            <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" />
            Revisi
        </Button>
    );
}

/** Menjembatani bentuk props `revisi` ke kontrak `DialogRevisi` (Fase 8). */
function JendelaRevisi({
    r,
    jadwal,
    buka,
    onUbahBuka,
}: {
    r: RevisiDariMasukan;
    jadwal: LogMasukanProps['ketersediaanPembuat'][number] | null;
    buka: boolean;
    onUbahBuka: (buka: boolean) => void;
}) {
    return (
        <DialogRevisi
            doc={{ id: r.id, nomor: r.nomor, janji_revisi: r.janji_revisi }}
            masukanRevisi={r.masukan}
            tercentang={r.tercentang}
            jadwalPembuat={jadwal}
            buka={buka}
            onUbahBuka={onUbahBuka}
        />
    );
}

/**
 * Isi masukan, dipotong 120 karakter; selebihnya dibuka `<details>` — nol JS,
 * dan barisnya tak pernah setinggi paragraf.
 */
function IsiMasukan({ m }: { m: BarisLogMasukan }) {
    return (
        <div className="text-sm">
            {m.isi.length <= 120 ? (
                m.isi
            ) : (
                <details>
                    <summary className="cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        {m.isi.slice(0, 120)}…
                        <span className="text-muted-foreground text-xs"> selengkapnya</span>
                    </summary>
                    <div className="mt-1">{m.isi}</div>
                </details>
            )}
            {m.balasan ? (
                /* Pita kutipan, bukan kartu — bentuknya disamakan dengan
                   `v2/DaftarMasukan` supaya balasan tampak sama di mana pun ia
                   muncul. Karena itu `rounded-r` di sini TIDAK ikut naik ke
                   `rounded-2xl`. */
                <div className="bg-muted mt-2 rounded-r border-l-4 px-2 py-1">
                    <HugeiconsIcon
                        icon={ReplyIcon}
                        strokeWidth={1.5}
                        className="inline size-3.5"
                        aria-hidden="true"
                    />{' '}
                    {m.balasan}
                    {m.pembalas ? <span className="text-muted-foreground"> — {m.pembalas}</span> : null}
                </div>
            ) : null}
        </div>
    );
}

/** Sama seperti `v2/DaftarMasukan`: variant registry, label dari server. */
function rona(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'baru') return 'destructive';
    if (status === 'diadopsi') return 'default';

    return 'secondary';
}

/**
 * Balas & Tutup — menutup masukan TANPA revisi.
 *
 * Tombol Kirim tetap `type="submit"` dan konfirmasi MENYUSUL sesudah formulir
 * lolos (`ConfirmDialog` terkendali, §3.8); dengan pemicu biasa ia terpaksa
 * `type="button"` dan `required` peramban ikut mati diam-diam.
 */
function DialogBalas({ m }: { m: BarisLogMasukan }) {
    const [buka, setBuka] = useState(false);
    const [konfirmasi, setKonfirmasi] = useState(false);
    const form = useForm({ balasan: '' });

    function ajukan(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <Dialog open={buka} onOpenChange={setBuka}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <HugeiconsIcon icon={ReplyIcon} strokeWidth={1.5} className="size-4" />
                    Balas
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-1.5">
                        <HugeiconsIcon icon={ReplyIcon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                        Balas — <span className="font-mono">{m.nomor}</span>
                    </DialogTitle>
                    <DialogDescription>
                        Masukan ini akan DITUTUP; pengirimnya diberi tahu.
                    </DialogDescription>
                </DialogHeader>

                <div className="rounded-2xl border p-2 text-sm">
                    {m.isi}
                    <span className="text-muted-foreground block text-xs">{m.oleh ?? '—'}</span>
                </div>

                <form id={`balas-${m.id}`} onSubmit={ajukan}>
                    <FieldGroup>
                        <Field data-invalid={!!form.errors.balasan || undefined}>
                            <FieldLabel htmlFor={`balasan-${m.id}`}>
                                Balasan untuk pengirim <span className="text-destructive">*</span>
                            </FieldLabel>
                            <Textarea
                                id={`balasan-${m.id}`}
                                rows={3}
                                maxLength={2000}
                                required
                                placeholder="mis. sudah sesuai standar terbaru."
                                value={form.data.balasan}
                                aria-invalid={!!form.errors.balasan}
                                onChange={(e) => form.setData('balasan', e.target.value)}
                            />
                            <FieldError
                                errors={
                                    form.errors.balasan ? [{ message: form.errors.balasan }] : undefined
                                }
                            />
                        </Field>
                    </FieldGroup>
                </form>

                <DialogFooter>
                    <Button type="button" variant="ghost" onClick={() => setBuka(false)}>
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        form={`balas-${m.id}`}
                        variant="secondary"
                        disabled={form.processing}
                    >
                        <HugeiconsIcon icon={ReplyIcon} strokeWidth={1.5} className="size-4" />
                        Balas &amp; Tutup
                    </Button>
                </DialogFooter>

                <ConfirmDialog
                    judul="Balas & Tutup?"
                    pesan={`Tutup masukan ${m.nomor} tanpa revisi dan kirim balasan ke pengirimnya?`}
                    tombolYa="Ya, kirim balasan"
                    buka={konfirmasi}
                    onUbahBuka={setKonfirmasi}
                    onKonfirmasi={() =>
                        form.post(route('documents.feedback.respond', m.id), {
                            preserveScroll: true,
                            onSuccess: () => {
                                setBuka(false);
                                form.reset();
                            },
                        })
                    }
                />
            </DialogContent>
        </Dialog>
    );
}
