import {
    ArrowLeft01Icon, ArrowTurnBackwardIcon, CheckmarkCircle01Icon, FileEditIcon,
    InformationCircleIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { ArsipEditProps } from '@/types/distribusi';

/**
 * "Perbaiki Dokumen Lama" — kembaran V2 `pages/Documents/Arsip/Edit.tsx`.
 *
 * Jenis & departemen hanya DITAMPILKAN, tak pernah dikirim: keduanya menyusun
 * nomor dokumen sekaligus jalur berkasnya, jadi mengubahnya di sini akan
 * meninggalkan berkas di folder yang salah dan nomor yang tak lagi cocok dengan
 * isinya. Servernya menolaknya juga (`prohibited` di `ArsipDocumentRequest`) —
 * yang di layar cuma menghemat satu galat, bukan menggantikan penjaganya.
 *
 * Konfirmasi MENYUSUL sesudah formulir sah, bukan mendahuluinya: tombol Simpan
 * tetap `type="submit"` sehingga `required` peramban tetap bekerja (PATOKAN
 * §3.8, pola yang sama sejak Fase 7).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — seluruh isian jadi `FieldGroup` > `Field` > `FieldLabel` + kendali
 *    + `FieldDescription` + `FieldError`. Galatnya kini muncul DI BAWAH kendali
 *    yang bersangkutan, bukan cuma sebagai daftar di kepala kartu, dan tiap
 *    kendali bergalat membawa `aria-invalid`. Daftar di kepala tetap ada:
 *    galat yang tak punya kendali di layar (mis. `jenis`/`dept` yang
 *    `prohibited`) tak boleh hilang tanpa jejak.
 *  · §3.2/§3.5 — daftar galat `border-destructive/40 rounded-lg` → komponen
 *    `alert` resmi `variant="destructive"`. Nol warna baru.
 *  · §3.6 — tautan Kembali & baris nomor·judul pindah ke prop `aksi`/`sub`
 *    layout.
 *  · §3.4 — `size-3.5` pada ikon tautan "Lihat berkas sekarang", ukuran rapat
 *    patokan untuk ikon di dalam teks kecil.
 */
export default function DocumentsArsipEdit() {
    const { document: doc, errors } = usePage<PageProps & ArsipEditProps>().props;
    const [konfirmasi, setKonfirmasi] = useState(false);

    const form = useForm<{
        doc_number: string;
        title: string;
        edisi: number;
        no_revisi: number;
        tanggal_efektif: string;
        berkas: File | null;
    }>({
        doc_number: doc.nomor,
        title: doc.judul,
        edisi: doc.edisi,
        no_revisi: doc.no_revisi,
        tanggal_efektif: doc.tanggal_efektif ?? '',
        berkas: null,
    });

    // Inertia beralih sendiri ke FormData + spoof `_method` begitu ada File di
    // dalam data — jadi PUT dengan unggahan tetap ditulis sebagai `put()`.
    const kirim = () => form.put(route('documents.arsip.update', doc.id), { forceFormData: true });

    const daftarGalat = Object.values(errors ?? {});

    return (
        <AppLayout
            judul={`Perbaiki Dokumen Lama: ${doc.nomor}`}
            sub={
                <>
                    <span className="font-mono">{doc.nomor}</span> — {doc.judul}
                </>
            }
            aksi={
                <Button asChild variant="ghost" size="sm">
                    <Link href={route('documents.published')}>
                        <HugeiconsIcon icon={ArrowLeft01Icon} strokeWidth={1.5} className="size-4" />
                        Kembali
                    </Link>
                </Button>
            }
        >
            <h2 className="text-lg font-bold">Perbaiki Dokumen Lama</h2>

            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent>
                        {daftarGalat.length > 0 ? (
                            <Alert variant="destructive" className="mb-6">
                                <AlertDescription>
                                    <ul className="list-disc pl-4">
                                        {daftarGalat.map((e) => (
                                            <li key={e}>{e}</li>
                                        ))}
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        <form
                            onSubmit={(e: FormEvent) => {
                                e.preventDefault();
                                setKonfirmasi(true);
                            }}
                        >
                            <FieldGroup>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field>
                                        <FieldLabel htmlFor="jenis">Jenis Dokumen</FieldLabel>
                                        <Input
                                            id="jenis"
                                            value={`${doc.jenis} — ${doc.jenis_nama}`}
                                            readOnly
                                            disabled
                                            className="bg-muted"
                                        />
                                    </Field>
                                    <Field>
                                        <FieldLabel htmlFor="dept">Departemen</FieldLabel>
                                        <Input
                                            id="dept"
                                            value={`${doc.dept} — ${doc.dept_nama}`}
                                            readOnly
                                            disabled
                                            className="bg-muted"
                                        />
                                    </Field>
                                </div>

                                <Field data-invalid={!!errors?.doc_number || undefined}>
                                    <FieldLabel htmlFor="doc_number">Nomor Dokumen</FieldLabel>
                                    <Input
                                        id="doc_number"
                                        name="doc_number"
                                        required
                                        className="font-mono"
                                        value={form.data.doc_number}
                                        aria-invalid={!!errors?.doc_number}
                                        onChange={(e) => form.setData('doc_number', e.target.value)}
                                    />
                                    <FieldDescription>
                                        Nomor lama diterima apa adanya; yang di luar pola resmi ditandai
                                        badge <strong>Nomor Lama</strong>.
                                    </FieldDescription>
                                    <FieldError
                                        errors={
                                            errors?.doc_number ? [{ message: errors.doc_number }] : undefined
                                        }
                                    />
                                </Field>

                                <Field data-invalid={!!errors?.title || undefined}>
                                    <FieldLabel htmlFor="title">Judul Dokumen</FieldLabel>
                                    <Input
                                        id="title"
                                        name="title"
                                        required
                                        value={form.data.title}
                                        aria-invalid={!!errors?.title}
                                        onChange={(e) => form.setData('title', e.target.value)}
                                    />
                                    <FieldError
                                        errors={errors?.title ? [{ message: errors.title }] : undefined}
                                    />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-3">
                                    <Field data-invalid={!!errors?.edisi || undefined}>
                                        <FieldLabel htmlFor="edisi">Edisi</FieldLabel>
                                        <Input
                                            id="edisi"
                                            name="edisi"
                                            type="number"
                                            min={1}
                                            max={99}
                                            value={form.data.edisi}
                                            aria-invalid={!!errors?.edisi}
                                            onChange={(e) => form.setData('edisi', Number(e.target.value))}
                                        />
                                        <FieldError
                                            errors={errors?.edisi ? [{ message: errors.edisi }] : undefined}
                                        />
                                    </Field>

                                    <Field data-invalid={!!errors?.no_revisi || undefined}>
                                        <FieldLabel htmlFor="no_revisi">No. Revisi</FieldLabel>
                                        <Input
                                            id="no_revisi"
                                            name="no_revisi"
                                            type="number"
                                            min={0}
                                            max={4}
                                            value={form.data.no_revisi}
                                            aria-invalid={!!errors?.no_revisi}
                                            onChange={(e) =>
                                                form.setData('no_revisi', Number(e.target.value))
                                            }
                                        />
                                        <FieldError
                                            errors={
                                                errors?.no_revisi
                                                    ? [{ message: errors.no_revisi }]
                                                    : undefined
                                            }
                                        />
                                    </Field>

                                    <Field data-invalid={!!errors?.tanggal_efektif || undefined}>
                                        <FieldLabel htmlFor="tanggal_efektif">Tanggal Efektif</FieldLabel>
                                        <Input
                                            id="tanggal_efektif"
                                            name="tanggal_efektif"
                                            type="date"
                                            value={form.data.tanggal_efektif}
                                            aria-invalid={!!errors?.tanggal_efektif}
                                            onChange={(e) =>
                                                form.setData('tanggal_efektif', e.target.value)
                                            }
                                        />
                                        <FieldError
                                            errors={
                                                errors?.tanggal_efektif
                                                    ? [{ message: errors.tanggal_efektif }]
                                                    : undefined
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field data-invalid={!!errors?.berkas || undefined}>
                                    <FieldLabel htmlFor="berkas">Ganti Berkas PDF</FieldLabel>
                                    <Input
                                        id="berkas"
                                        name="berkas"
                                        type="file"
                                        accept="application/pdf"
                                        aria-invalid={!!errors?.berkas}
                                        onChange={(e) =>
                                            form.setData('berkas', e.target.files?.[0] ?? null)
                                        }
                                    />
                                    <FieldDescription>
                                        Kosongkan bila berkasnya tidak diganti. PDF saja, maksimal{' '}
                                        <strong>40 MB</strong>.{' '}
                                        <a
                                            href={route('documents.pdf', doc.id)}
                                            target="_blank"
                                            rel="noopener"
                                            className="text-foreground inline-flex items-center gap-1 underline"
                                        >
                                            <HugeiconsIcon
                                                icon={FileEditIcon}
                                                strokeWidth={1.5}
                                                className="size-3.5"
                                            />
                                            Lihat berkas sekarang
                                        </a>
                                    </FieldDescription>
                                    <FieldError
                                        errors={errors?.berkas ? [{ message: errors.berkas }] : undefined}
                                    />
                                </Field>
                            </FieldGroup>

                            <div className="mt-6 flex flex-wrap gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    <HugeiconsIcon
                                        icon={CheckmarkCircle01Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Simpan Perubahan
                                </Button>
                                {/* Jalan masuk KEDUA ke lembar Catatan Revisi (Fase H):
                                    halaman itu muncul otomatis sesudah unggahan, tapi
                                    pemakainya boleh menekan "Nanti Saja" — tanpa tautan
                                    ini, "nanti" berarti tak pernah. */}
                                {doc.berlembar_revisi ? (
                                    <Button asChild variant="outline">
                                        <Link href={route('documents.arsip.catatan', doc.id)}>
                                            <HugeiconsIcon
                                                icon={ArrowTurnBackwardIcon}
                                                strokeWidth={1.5}
                                                className="size-4"
                                            />
                                            Lembar Catatan Revisi
                                        </Link>
                                    </Button>
                                ) : null}
                                <Button asChild variant="ghost">
                                    <Link href={route('documents.published')}>Batal</Link>
                                </Button>
                            </div>
                        </form>

                        <ConfirmDialog
                            buka={konfirmasi}
                            onUbahBuka={setKonfirmasi}
                            judul="Simpan Perubahan?"
                            pesan="Simpan perubahan pada dokumen lama ini? Perubahannya tercatat di Audit Log."
                            tombolYa="Ya, simpan"
                            onKonfirmasi={kirim}
                        />
                    </CardContent>
                </Card>

                <Card className="bg-muted/40">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <HugeiconsIcon
                                icon={InformationCircleIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Yang Perlu Diketahui
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="text-muted-foreground list-outside list-disc space-y-1 pl-4 text-sm">
                            <li>
                                Dokumen ini <strong>Berlaku</strong> — perbaikan di sini mengubah apa yang
                                dilihat seluruh departemen seketika.
                            </li>
                            <li>
                                Setiap perubahan tercatat di <strong>Audit Log</strong> beserta nilai
                                sebelum &amp; sesudahnya.
                            </li>
                            <li>
                                Salah jenis atau departemen? Keduanya tak bisa diubah — musnahkan
                                dokumennya lalu daftarkan ulang.
                            </li>
                            <li>Berkas lama dihapus dari disk begitu penggantinya tersimpan.</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
