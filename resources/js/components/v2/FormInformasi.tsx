/**
 * KEMBARAN MAIA dari `resources/js/components/informasi/FormInformasi.tsx`
 * (PATOKAN-GAYA-V2 Fase 1).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser: kunci `useForm` tetap
 * lengkap termasuk yang tak dirender, `forceFormData`, rute Tambah vs Perbarui,
 * dan konfirmasi yang HANYA muncul pada Perbarui semuanya tetap. Yang berubah
 * dari kit mana komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya
 * (lucide → hugeicons, PATOKAN §3.4). Berkas aslinya sengaja TIDAK disentuh:
 * halaman lama masih memakainya sebagai pembanding selama jendela pratinjau.
 * Fase 7 menukar keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 *
 * SATU penyimpangan dari berkas asli, dan ia patokan Fase 0: jarak antar-isian
 * tak lagi diketik sebagai `grid gap-5` di `<form>` melainkan diserahkan pada
 * `FieldGroup` (§3.9). Angkanya jadi milik kit, bukan milik berkas ini — dan
 * itulah yang membuat 35 halaman berikutnya tak masing-masing menebak.
 */
import { RefreshCwIcon, Upload01Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import type { InformasiFormProps, InformasiPerbaruiProps } from '@/types/informasi';

/**
 * Formulir unggah Informasi — dipakai Tambah DAN Perbarui.
 *
 * Bentuknya menyesuaikan KATEGORI, bukan satu formulir seragam: kolom yang tak
 * dipakai kategori ini tidak ditampilkan sama sekali (`kolom`, asalnya
 * `informasi_kategori.kolom_json` yang disetel Admin di Master Data). Itu yang
 * membuat pengunggah MEMO tak pernah melihat tiga kotak yang tak pernah ia isi
 * — dan `StoreInformasiRequest` menolak kiriman yang tetap memuatnya
 * (`prohibited`).
 *
 * Seluruh kunci tetap dikirim, termasuk yang tak dirender: `prohibited` di
 * Laravel berarti "tidak ada ATAU kosong", dan Inertia mengirim `null` sebagai
 * string kosong. Membangun larik data yang berbeda-beda per kategori hanya
 * menambah cabang tanpa satu pun perbedaan hasil.
 *
 * Konfirmasi hanya pada PERBARUI, dan MENYUSUL sesudah formulirnya sah
 * (`ConfirmDialog` terkendali, PATOKAN §3.8) — tombolnya tetap `type="submit"`
 * sehingga `required` peramban tetap bekerja. Tambah tak berkonfirmasi: ia tak
 * menggantikan apa pun.
 */
export function FormInformasi({
    kategori,
    label,
    kolom,
    ekstensi,
    prefix,
    edisiBaru,
    revisiBaru,
    induk,
}: InformasiFormProps & { induk: InformasiPerbaruiProps['induk'] | null }) {
    const { data, setData, post, processing, errors } = useForm<{
        kategori: string;
        nomor: string;
        judul: string;
        edisi: string;
        no_revisi: string;
        tanggal_efektif: string;
        berkas: File | null;
    }>({
        kategori: induk ? '' : kategori,
        nomor: '',
        judul: induk?.judul ?? '',
        edisi: String(edisiBaru),
        no_revisi: String(revisiBaru),
        tanggal_efektif: induk?.tanggal_input ?? '',
        berkas: null,
    });

    const [konfirmasi, setKonfirmasi] = useState(false);
    const punya = (k: string) => kolom.includes(k);
    const daftarGalat = Object.values(errors).filter(Boolean) as string[];

    function kirim() {
        post(
            induk ? route('informasi.perbarui.store', induk.id) : route('informasi.store'),
            { forceFormData: true },
        );
    }

    function ajukan(e: FormEvent) {
        e.preventDefault();

        if (induk) {
            setKonfirmasi(true);

            return;
        }

        kirim();
    }

    return (
        <form onSubmit={ajukan} className="grid gap-6">
            {daftarGalat.length > 0 ? (
                <Alert variant="destructive">
                    <AlertDescription>
                        <ul className="list-disc pl-4">
                            {daftarGalat.map((pesan) => (
                                <li key={pesan}>{pesan}</li>
                            ))}
                        </ul>
                    </AlertDescription>
                </Alert>
            ) : null}

            <FieldGroup>
                <Field>
                    <FieldLabel htmlFor="kategori">Kategori</FieldLabel>
                    <Input id="kategori" value={label} readOnly className="bg-muted" />
                </Field>

                <Field data-invalid={!!errors.nomor || undefined}>
                    <FieldLabel htmlFor="nomor">Nomor Dokumen</FieldLabel>
                    {induk ? (
                        <>
                            <Input id="nomor" value={induk.nomor} readOnly className="bg-muted font-mono" />
                            <FieldDescription>
                                Nomor diwarisi dari versi yang berlaku — versi baru memang bernomor sama.
                            </FieldDescription>
                        </>
                    ) : (
                        <>
                            <Input
                                id="nomor"
                                required
                                className="font-mono"
                                placeholder={`mis. ${prefix}-KBJ-01`}
                                value={data.nomor}
                                aria-invalid={!!errors.nomor}
                                onChange={(e) => setData('nomor', e.target.value)}
                            />
                            <FieldDescription>
                                Sudah ada dokumen bernomor ini? Pakai tombol <strong>Perbarui</strong> pada
                                barisnya, bukan Tambah.
                            </FieldDescription>
                        </>
                    )}
                    <FieldError errors={errors.nomor ? [{ message: errors.nomor }] : undefined} />
                </Field>

                <Field data-invalid={!!errors.judul || undefined}>
                    <FieldLabel htmlFor="judul">Judul</FieldLabel>
                    <Input
                        id="judul"
                        required
                        value={data.judul}
                        aria-invalid={!!errors.judul}
                        onChange={(e) => setData('judul', e.target.value)}
                    />
                    <FieldError errors={errors.judul ? [{ message: errors.judul }] : undefined} />
                </Field>

                {kolom.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-3">
                        {punya('edisi') ? (
                            <Field data-invalid={!!errors.edisi || undefined}>
                                <FieldLabel htmlFor="edisi">Edisi</FieldLabel>
                                <Input
                                    id="edisi"
                                    name="edisi"
                                    type="number"
                                    min={1}
                                    max={99}
                                    required
                                    value={data.edisi}
                                    aria-invalid={!!errors.edisi}
                                    onChange={(e) => setData('edisi', e.target.value)}
                                />
                                <FieldError errors={errors.edisi ? [{ message: errors.edisi }] : undefined} />
                            </Field>
                        ) : null}

                        {punya('no_revisi') ? (
                            <Field data-invalid={!!errors.no_revisi || undefined}>
                                <FieldLabel htmlFor="no_revisi">No. Revisi</FieldLabel>
                                <Input
                                    id="no_revisi"
                                    name="no_revisi"
                                    type="number"
                                    min={0}
                                    max={4}
                                    required
                                    value={data.no_revisi}
                                    aria-invalid={!!errors.no_revisi}
                                    onChange={(e) => setData('no_revisi', e.target.value)}
                                />
                                <FieldError
                                    errors={errors.no_revisi ? [{ message: errors.no_revisi }] : undefined}
                                />
                            </Field>
                        ) : null}

                        {punya('tanggal_efektif') ? (
                            <Field data-invalid={!!errors.tanggal_efektif || undefined}>
                                <FieldLabel htmlFor="tanggal_efektif">Tanggal Efektif</FieldLabel>
                                <Input
                                    id="tanggal_efektif"
                                    type="date"
                                    required
                                    value={data.tanggal_efektif}
                                    aria-invalid={!!errors.tanggal_efektif}
                                    onChange={(e) => setData('tanggal_efektif', e.target.value)}
                                />
                                <FieldError
                                    errors={
                                        errors.tanggal_efektif
                                            ? [{ message: errors.tanggal_efektif }]
                                            : undefined
                                    }
                                />
                            </Field>
                        ) : null}
                    </div>
                ) : null}

                <Field data-invalid={!!errors.berkas || undefined}>
                    <FieldLabel htmlFor="berkas">Berkas</FieldLabel>
                    <Input
                        id="berkas"
                        type="file"
                        required
                        accept={ekstensi.map((e) => `.${e}`).join(',')}
                        aria-invalid={!!errors.berkas}
                        onChange={(e) => setData('berkas', e.target.files?.[0] ?? null)}
                    />
                    <FieldDescription>
                        Format: <strong>{ekstensi.join(', ').toUpperCase()}</strong>. Maksimal{' '}
                        <strong>40 MB</strong> (batas server). Berkas disimpan privat — hanya bisa dibuka
                        pengguna yang sudah masuk.
                    </FieldDescription>
                    <FieldError errors={errors.berkas ? [{ message: errors.berkas }] : undefined} />
                </Field>
            </FieldGroup>

            <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={processing}>
                    <HugeiconsIcon
                        icon={induk ? RefreshCwIcon : Upload01Icon}
                        strokeWidth={1.5}
                        className="size-4"
                    />
                    {induk ? 'Perbarui & Berlakukan' : 'Unggah'}
                </Button>
                <Button asChild variant="ghost">
                    <Link href={route('informasi.index', { kategori })}>Batal</Link>
                </Button>
            </div>

            {induk ? (
                <ConfirmDialog
                    judul={`Perbarui ${induk.nomor}?`}
                    pesan={
                        <>
                            Versi yang sekarang berlaku ({induk.revisi ?? induk.judul}) akan dipindahkan ke
                            Riwayat, dan versi baru inilah yang ditampilkan dengan nomor {induk.nomor}
                            {punya('no_revisi')
                                ? ` — akan menjadi Edisi ${edisiBaru} Rev ${revisiBaru}`
                                : ''}
                            .
                        </>
                    }
                    tombolYa="Ya, perbarui"
                    buka={konfirmasi}
                    onUbahBuka={setKonfirmasi}
                    onKonfirmasi={kirim}
                />
            ) : null}
        </form>
    );
}
