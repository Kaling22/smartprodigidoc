import {
    ArrowTurnBackwardIcon, CheckmarkBadge01Icon, CheckmarkCircle01Icon, FileEditIcon,
    InformationCircleIcon, LinkSquare01Icon, UserMultipleIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { ApprovalsShowProps } from '@/types/tinjau';

/**
 * Persetujuan final PJO — kembaran V2 `pages/Approvals/Show.tsx`.
 *
 * Halaman ini SENGAJA ringkas: keputusan + alasan saja. Penilaian per-item
 * (termasuk analisa JSA) adalah tugas PENINJAU dan tidak diduplikasi di sini —
 * itulah sebabnya tak ada satu pun kotak `annotations` di layar ini.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — tautan "Kembali ke antrian", judul dokumen, baris nomor · dept, dan
 *    tombol Lihat PDF pindah ke `remah`/`sub`/`aksi` layout. Jalan kembalinya
 *    tak hilang: ia jadi remah pertama, pola yang sama dengan
 *    `V2/Review/Show`.
 *  · §3.9 — `Label` + `Textarea` + `<p className="text-destructive">` jadi
 *    `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` +
 *    `FieldError`, lengkap dengan `aria-invalid` dan `data-invalid`.
 *  · §3.2 — kartunya tak lagi `gap-0 py-0` dengan `px-4 py-3` per bagian;
 *    jaraknya diserahkan ke `ui-maia/card` (preseden `RincianInformasi`).
 *  · Galat `doc_number` jadi `alert` resmi `variant="destructive"`, bukan
 *    paragraf merah: ia satu-satunya galat di layar ini yang TAK punya kendali
 *    (nomor final dikunci server saat pengesahan), jadi ia harus berdiri
 *    sendiri alih-alih menempel di bawah kotak alasan yang bukan sebabnya.
 *  · Pratinjau PDF (rencana pra-produksi Fase 9) menempati kolom kanan 7/12,
 *    sementara Rantai + Keputusan menumpuk di kolom kiri 5/12. Kartunya
 *    disalin dari `V2/Documents/Arsip/Catatan.tsx` — di situlah pola
 *    "kartu iframe" ini sudah berdiri, jadi ia dipakai ulang, bukan digambar
 *    ulang. Tombol "Lihat PDF" di `aksi` TETAP: iframe tak menggantikan
 *    kebutuhan membuka PDF penuh di tab baru.
 */
export default function ApprovalsShow() {
    const { document: doc, errors } = usePage<PageProps & ApprovalsShowProps>().props;

    const [alasan, setAlasan] = useState('');
    const [keputusan, setKeputusan] = useState<'approve' | 'reject' | null>(null);

    const kirim = (decision: 'approve' | 'reject') =>
        router.post(route('approvals.store', doc.id), { decision, summary: alasan });

    return (
        <AppLayout
            judul={`Persetujuan: ${doc.nomor}`}
            remah={[
                { label: 'Kembali ke antrian', href: route('approvals.index') },
                { label: doc.nomor },
            ]}
            sub={
                <span className="flex flex-wrap items-center gap-1.5">
                    <span className="font-medium">{doc.judul}</span>
                    <span className="text-primary font-mono text-sm">{doc.nomor}</span>
                    <Badge variant="outline">{doc.dept ?? '—'}</Badge>
                </span>
            }
            aksi={
                <Button asChild variant="outline" size="sm">
                    <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                        <HugeiconsIcon icon={FileEditIcon} strokeWidth={1.5} className="size-4" />
                        Lihat PDF
                    </a>
                </Button>
            }
        >
            <div className="grid gap-4 lg:grid-cols-12">
                {/* KIRI: rantai lalu keputusan, satu tumpukan — supaya kolom
                    kanan bebas ditempati pratinjau setinggi layar. */}
                <div className="grid content-start gap-4 lg:col-span-5">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={UserMultipleIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Rantai Dokumen
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-3 gap-y-2 text-sm">
                                <dt className="text-muted-foreground">Dibuat Oleh</dt>
                                <dd className="col-span-2">
                                    {doc.pembuat ?? '—'}
                                    {doc.pembuatNrp ? ` (${doc.pembuatNrp})` : ''}
                                </dd>
                                <dt className="text-muted-foreground">Ditinjau Oleh</dt>
                                <dd className="col-span-2">{doc.peninjau ?? '—'}</dd>
                                <dt className="text-muted-foreground">Departemen</dt>
                                <dd className="col-span-2">{doc.departemen ?? '—'}</dd>
                                <dt className="text-muted-foreground">Edisi / Revisi</dt>
                                <dd className="col-span-2">
                                    Edisi {doc.edisi} · Revisi {doc.noRevisi}
                                </dd>
                            </dl>
                            <Alert className="mt-4">
                                <HugeiconsIcon
                                    icon={InformationCircleIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                <AlertDescription>
                                    Tinjau isi lengkap lewat tombol <strong>Lihat PDF</strong>. Penilaian
                                    per-item sudah dilakukan peninjau.
                                </AlertDescription>
                            </Alert>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <HugeiconsIcon
                                    icon={CheckmarkBadge01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Keputusan
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {/* Nomor final dikunci tepat saat dokumen disahkan, dan
                                bisa bentrok bila nomornya diketik tangan — pesannya
                                datang dari server sebagai galat `doc_number`. */}
                            {errors.doc_number ? (
                                <Alert variant="destructive">
                                    <AlertDescription>{errors.doc_number}</AlertDescription>
                                </Alert>
                            ) : null}

                            <FieldGroup>
                                <Field data-invalid={!!errors.summary || undefined}>
                                    <FieldLabel htmlFor="alasan">
                                        Catatan / Alasan{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (wajib bila mengajukan revisi)
                                        </span>
                                    </FieldLabel>
                                    <Textarea
                                        id="alasan"
                                        rows={3}
                                        placeholder="Alasan revisi / catatan persetujuan..."
                                        aria-invalid={!!errors.summary}
                                        value={alasan}
                                        onChange={(e) => setAlasan(e.target.value)}
                                    />
                                    <FieldDescription>
                                        Catatan ini tampil di form revisi pembuat.
                                    </FieldDescription>
                                    <FieldError
                                        errors={errors.summary ? [{ message: errors.summary }] : undefined}
                                    />
                                </Field>
                            </FieldGroup>

                            <div className="grid gap-2">
                                <Button onClick={() => setKeputusan('approve')}>
                                    <HugeiconsIcon
                                        icon={CheckmarkCircle01Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Setujui (Berlaku)
                                </Button>
                                <Button variant="outline" onClick={() => setKeputusan('reject')}>
                                    <HugeiconsIcon
                                        icon={ArrowTurnBackwardIcon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Kembalikan untuk Revisi
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* KANAN: pratinjau PDF — pola disalin dari
                    `V2/Documents/Arsip/Catatan.tsx` (Card `gap-0 py-0` + header
                    bertombol "Buka" + `CardContent h-[78vh] p-0` + iframe).
                    Iframe ini TIDAK menggelembungkan angka distribusi:
                    `DocumentDistribution::catat()` hanya mencatat dokumen
                    berstatus `published`/`sedang_direvisi`, sedangkan layar ini
                    cuma bisa dibuka untuk `pending_approval`
                    (`ApprovalController::show`). */}
                <Card className="gap-0 py-0 lg:sticky lg:top-4 lg:col-span-7 lg:self-start">
                    <CardHeader className="flex items-center justify-between border-b px-4 py-3">
                        <CardTitle className="flex items-center gap-2 text-sm">
                            <HugeiconsIcon
                                icon={FileEditIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Pratinjau PDF
                        </CardTitle>
                        <Button asChild size="sm" variant="outline">
                            <a href={route('documents.pdf', doc.id)} target="_blank" rel="noopener">
                                <HugeiconsIcon
                                    icon={LinkSquare01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Buka
                            </a>
                        </Button>
                    </CardHeader>
                    <CardContent className="h-[78vh] p-0">
                        <iframe
                            title={`Pratinjau ${doc.nomor}`}
                            src={`${route('documents.pdf', doc.id)}#toolbar=1&navpanes=0&view=Fit`}
                            className="size-full border-0"
                        />
                    </CardContent>
                </Card>
            </div>

            <ConfirmDialog
                buka={keputusan !== null}
                onUbahBuka={(b) => (b ? null : setKeputusan(null))}
                judul={keputusan === 'reject' ? 'Kembalikan untuk Revisi?' : 'Setujui Dokumen?'}
                pesan={
                    keputusan === 'reject'
                        ? 'Kembalikan dokumen ke pembuat untuk revisi? Pastikan alasan/catatan sudah diisi.'
                        : 'Setujui dokumen ini menjadi Berlaku? Seluruh tanda tangan akan bercap APPROVED.'
                }
                tombolYa={keputusan === 'reject' ? 'Ya, kembalikan' : 'Ya, setujui'}
                onKonfirmasi={() => {
                    if (keputusan) kirim(keputusan);
                    setKeputusan(null);
                }}
            />
        </AppLayout>
    );
}
