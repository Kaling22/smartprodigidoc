import {
    CircleSlashIcon, FileEditIcon, InboxIcon, InformationCircleIcon, OctagonXIcon, Tick02Icon,
    ViewIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardFooter } from '@/components/ui-maia/card';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui-maia/dialog';
import { DropdownMenuItem } from '@/components/ui-maia/dropdown-menu';
import { Empty, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui-maia/empty';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui-maia/field';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom, type Paginator } from '@/components/v2/DataTable';
import { NomorDokumen } from '@/components/v2/NomorDokumen';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisNonaktif } from '@/types/tinjau';

/**
 * "Persetujuan Nonaktif" — kembaran V2 `pages/Nonaktif/Index.tsx`.
 *
 * Mematikan dokumen Berlaku BERJENJANG (PLAN-REVISI-v6 Fase F): SH/DH
 * departemen → Management Development → PJO. Siapa yang melihat baris apa
 * dijawab `Document::scopeMenungguNonaktif` — nol penyaringan di klien.
 *
 * Alurnya ditulis DI LAYAR, bukan cuma di kode: pemutus tahap pertama perlu
 * tahu bahwa keputusannya BUKAN yang terakhir, dan pemutus tahap terakhir perlu
 * tahu bahwa nomornya akan benar-benar dilepas.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.2/§3.5 — pita `bg-accent … rounded-lg` jadi `alert` resmi. Nol warna
 *    baru, nol radius diketik ulang.
 *  · §3.6 — paragraf pengantar pindah ke prop `sub` layout.
 *  · §3.7 — satu kartu: tabel di `CardContent` tanpa talang, paginasi di
 *    `CardFooter` bertepi atas (arketipe `V2/Documents/Revisions.tsx`).
 *  · §3.9 — alasan penolakan jadi `FieldGroup` > `Field` > `FieldLabel` +
 *    `Textarea` + `FieldError`, lengkap `aria-invalid` + `data-invalid`.
 *  · Keadaan kosong memakai komponen `empty` resmi, bukan rakitan `div` + ikon.
 */
export default function NonaktifIndex() {
    const { documents } = usePage<PageProps & { documents: Paginator<BarisNonaktif> }>().props;

    const kolom: Kolom<BarisNonaktif>[] = [
        { judul: 'No. Dokumen', urut: 'nomor', render: (d) => <NomorDokumen doc={d} /> },
        { judul: 'Judul', render: (d) => <span className="font-medium">{d.judul}</span> },
        { judul: 'Jenis', render: (d) => <Badge variant="outline">{d.jenis ?? '—'}</Badge> },
        { judul: 'Dept', render: (d) => <Badge variant="outline">{d.dept ?? '—'}</Badge> },
        { judul: 'Pengaju', render: (d) => <span className="text-sm">{d.pengaju ?? '—'}</span> },
        {
            judul: 'Tahap',
            render: (d) => (
                <div className="flex flex-col items-start gap-1">
                    <Badge variant="secondary">{d.tahapLabel}</Badge>
                    {d.tahapTerakhir ? (
                        <Badge
                            variant="destructive"
                            title="Menyetujui di tahap ini langsung mematikan dokumen"
                        >
                            tahap terakhir
                        </Badge>
                    ) : null}
                </div>
            ),
        },
        {
            judul: 'Alasan',
            kelas: 'max-w-88',
            render: (d) => <span className="text-muted-foreground text-sm">{d.alasan ?? '—'}</span>,
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (d) => <AksiBaris doc={d} />,
        },
    ];

    return (
        <AppLayout
            judul="Persetujuan Nonaktif"
            sub={
                <span className="flex flex-wrap items-center gap-1.5">
                    <HugeiconsIcon
                        icon={CircleSlashIcon}
                        strokeWidth={1.5}
                        className="size-4"
                        aria-hidden="true"
                    />
                    Pengajuan mematikan dokumen Berlaku yang menunggu keputusan Anda.
                </span>
            }
        >
            <Alert>
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                <AlertDescription>
                    Pengajuan berjalan{' '}
                    <strong>SH/DH departemen → Management Development → PJO</strong>. Dokumen tetap{' '}
                    <strong>Berlaku</strong> selama pengajuan berjalan; nomornya baru dilepas setelah
                    tahap terakhir menyetujui, dan penolakan di tahap mana pun mengembalikannya ke
                    Berlaku.
                </AlertDescription>
            </Alert>

            <Card>
                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={documents.data}
                        kunci={(d) => d.id}
                        kosong={
                            <Empty className="border-0">
                                <EmptyHeader>
                                    <EmptyMedia variant="icon">
                                        <HugeiconsIcon
                                            icon={InboxIcon}
                                            strokeWidth={1.5}
                                            className="size-6"
                                        />
                                    </EmptyMedia>
                                    <EmptyTitle>
                                        Tidak ada pengajuan nonaktif yang menunggu keputusan Anda.
                                    </EmptyTitle>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={documents} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}

/**
 * Tombol per baris.
 *
 * "Tolak" duduk di strip, bukan berdampingan dengan "Setujui" — dan itu bukan
 * demi jumlah tombol: keduanya adalah keputusan berlawanan yang akan terpisah
 * beberapa piksel di baris sempit, dan yang salah klik di sini melepas nomor
 * dokumen.
 */
function AksiBaris({ doc }: { doc: BarisNonaktif }) {
    // Jendela dirender DI LUAR `StripAksi`: Radix melepas isi menunya begitu
    // item dipilih, sehingga jendela yang dirender di dalamnya ikut lenyap
    // sebelum sempat terlihat (pelajaran Fase 8, PATOKAN §3.8).
    const [tolak, setTolak] = useState(false);

    return (
        <div className="flex items-center justify-end gap-2">
            <Button asChild variant="outline" size="icon" title="Lihat PDF">
                <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                    <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                </a>
            </Button>

            <ConfirmDialog
                judul="Setujui Nonaktif?"
                pesan={
                    doc.tahapTerakhir
                        ? `Ini tahap TERAKHIR: ${doc.nomor} langsung menjadi Tidak Berlaku dan nomornya dilepas.`
                        : 'Pengajuan diteruskan ke tahap berikutnya. Dokumen tetap Berlaku sampai keputusan terakhir.'
                }
                tombolYa="Ya, setujui"
                destruktif={doc.tahapTerakhir}
                onKonfirmasi={() =>
                    router.post(
                        route('nonaktif.putuskan', doc.id),
                        { keputusan: 'setuju' },
                        { preserveScroll: true },
                    )
                }
                pemicu={
                    <Button variant="secondary" size="sm">
                        <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} className="size-4" />
                        Setujui
                    </Button>
                }
            />

            <StripAksi>
                <DropdownMenuItem asChild>
                    <Link href={route('documents.show', doc.id)}>
                        <HugeiconsIcon icon={ViewIcon} strokeWidth={1.5} className="size-4" />
                        Lihat detail
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem variant="destructive" onSelect={() => setTolak(true)}>
                    <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} className="size-4" />
                    Tolak
                </DropdownMenuItem>
            </StripAksi>

            <DialogTolak doc={doc} buka={tolak} onUbahBuka={setTolak} />
        </div>
    );
}

/**
 * Tolak pengajuan — alasan WAJIB, sebab itulah satu-satunya yang dibaca pengaju.
 *
 * Jendela FORMULIR, bukan konfirmasi (PATOKAN §3.8), jadi `ui-maia/dialog` —
 * dan tanpa konfirmasi bertingkat: `required` peramban sudah menahan kiriman
 * kosong, dan penolakan mengembalikan dokumen ke Berlaku, bukan melepas nomor.
 */
function DialogTolak({
    doc,
    buka,
    onUbahBuka,
}: {
    doc: BarisNonaktif;
    buka: boolean;
    onUbahBuka: (buka: boolean) => void;
}) {
    const form = useForm({ keputusan: 'tolak', alasan: '' });

    return (
        <Dialog open={buka} onOpenChange={onUbahBuka}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} className="size-4" />
                        Tolak Pengajuan — <span className="font-mono">{doc.nomor}</span>
                    </DialogTitle>
                    <DialogDescription>
                        Dokumen langsung kembali <strong>Berlaku</strong> dan pengaju dikabari
                        alasannya.
                    </DialogDescription>
                </DialogHeader>

                <form
                    id={`tolak-${doc.id}`}
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.post(route('nonaktif.putuskan', doc.id), {
                            preserveScroll: true,
                            onSuccess: () => {
                                onUbahBuka(false);
                                form.reset();
                            },
                        });
                    }}
                >
                    <FieldGroup>
                        <Field data-invalid={!!form.errors.alasan || undefined}>
                            <FieldLabel htmlFor={`alasan-${doc.id}`}>
                                Alasan penolakan <span className="text-destructive">*</span>
                            </FieldLabel>
                            <Textarea
                                id={`alasan-${doc.id}`}
                                rows={3}
                                maxLength={2000}
                                required
                                aria-invalid={!!form.errors.alasan}
                                placeholder="mis. Pekerjaannya masih berjalan di shift malam."
                                value={form.data.alasan}
                                onChange={(e) => form.setData('alasan', e.target.value)}
                            />
                            <FieldError
                                errors={
                                    form.errors.alasan ? [{ message: form.errors.alasan }] : undefined
                                }
                            />
                        </Field>
                    </FieldGroup>
                </form>

                <DialogFooter>
                    <Button type="button" variant="ghost" onClick={() => onUbahBuka(false)}>
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        form={`tolak-${doc.id}`}
                        variant="destructive"
                        disabled={form.processing}
                    >
                        <HugeiconsIcon icon={OctagonXIcon} strokeWidth={1.5} className="size-4" />
                        Tolak Pengajuan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
