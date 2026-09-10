import {
    Archive01Icon,
    CircleArrowRight01Icon,
    InformationCircleIcon,
    SparklesIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardContent } from '@/components/ui-maia/card';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui-maia/select';
import { Switch } from '@/components/ui-maia/switch';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { DocumentsCreateProps } from '@/types/wizard';

/**
 * "Dokumen Baru — Langkah 1", kerangka V2 — kembaran maia dari
 * `pages/Documents/Create.tsx`.
 *
 * Logikanya SALIN PERSIS: satu formulir dua bentuk isian, sakelar "dokumen
 * lama" yang menukar bagian bawahnya beserta rute tujuannya
 * (`documents.store` vs `documents.arsip.store`), sakelar itu dikunci menyala &
 * disembunyikan pada jenis UNGGAHAN (FK/PX), dan konfirmasi "langsung Berlaku"
 * yang MENYUSUL sesudah formulir sah — tombolnya tetap `type="submit"` supaya
 * `required` peramban tetap bekerja.
 *
 * Yang berubah cuma kerangkanya: judul + keterangan + tautan Kembali yang di
 * versi nova digambar sebagai blok tersendiri di atas isi kini larut ke blok
 * judul layout (H1 + sub-line + remah), sama seperti di wizard-nya.
 *
 * Halaman ini tak disebut §13 sebagai isi T2; ia diikutkan atas keputusan
 * pemilik 2026-08-31 supaya alur GL tak melompat nova → maia di tengah
 * pembuatan dokumen. Untungnya yang kedua: rutenya `/documents/create` TANPA
 * parameter, jadi ia ikut tersapu `smartpro:uji-render --pratinjau` secara
 * cuma-cuma — `documents.edit` dan `review.show` berparameter dan butuh
 * `--uri` untuk itu.
 */
export default function DocumentsCreate() {
    const { type, departments, defaultDept, canChooseDept, numberPreview, unggahanSaja, prefix,
        nomorSaran, errors } = usePage<PageProps & DocumentsCreateProps>().props;

    const [arsip, setArsip] = useState(unggahanSaja);
    const [manual, setManual] = useState(false);
    const [konfirmasi, setKonfirmasi] = useState(false);

    /*
     * Dialog "Nomor Sudah Dipakai" (rencana pra-produksi Fase 5 / butir 4).
     *
     * Terbuka SENDIRI saat halaman digambar ulang sesudah kiriman ditolak —
     * `nomorSaran` hanya terisi pada request tepat sesudah penolakan, jadi tak
     * ada keadaan lain yang bisa memunculkannya. Galat merah inline TETAP ada
     * di atas formulir; dialog ini menambahkan jalan keluarnya, bukan
     * menggantikan pesannya.
     *
     * Sarannya datang dari server; halaman ini tak pernah menghitung "nomor
     * bebas berikutnya" sendiri (CLAUDE.md §4).
     */
    const bentrokNomor = Boolean(errors?.doc_number && nomorSaran);
    const [bentrok, setBentrok] = useState(bentrokNomor);
    const nomorRef = useRef<HTMLInputElement>(null);

    const form = useForm<{
        arsip: number;
        document_type_id: number;
        department_id: number | null;
        title: string;
        doc_number_manual: number;
        doc_number: string;
        edisi: number;
        no_revisi: number;
        tanggal_efektif: string;
        berkas: File | null;
    }>({
        arsip: unggahanSaja ? 1 : 0,
        document_type_id: type.id,
        department_id: defaultDept,
        title: '',
        doc_number_manual: 0,
        doc_number: '',
        edisi: 1,
        no_revisi: 0,
        tanggal_efektif: '',
        berkas: null,
    });

    const dept = departments.find((d) => d.id === form.data.department_id) ?? departments[0] ?? null;

    /**
     * `preserveState: true` (bawaan Inertia utk POST) tak me-mount ulang
     * komponen sesudah redirect-back, jadi `bentrok` yang cuma diinisialisasi
     * dari props saat MOUNT tak pernah menyala lagi utk percobaan kedua yang
     * bentrok lagi. `onError` dipanggil tiap kali, mount atau tidak.
     */
    const opsiKirim = { onError: (e: Record<string, string>) => setBentrok(Boolean(e.doc_number)) };

    const kirim = () =>
        form.post(arsip ? route('documents.arsip.store') : route('documents.store'), {
            forceFormData: arsip,
            ...opsiKirim,
        });

    /**
     * "Pakai nomor otomatis". Dua mode, dua akibat yang memang berbeda:
     *  · wizard — sakelar manual DIMATIKAN dan kiriman diulang; nomornya
     *    dibangkitkan server seperti kalau manual tak pernah dinyalakan.
     *  · dokumen lama — nomornya cuma diisikan ke kotaknya. Berkas PDF-nya
     *    hilang saat halaman digambar ulang, jadi mengirim ulang di sini pasti
     *    berakhir "berkas wajib diunggah"; yang benar adalah membiarkan orang
     *    melampirkan berkasnya lagi lalu menekan tombolnya sendiri.
     */
    const pakaiNomorOtomatis = () => {
        setBentrok(false);
        if (arsip) {
            form.setData('doc_number', nomorSaran ?? '');
            nomorRef.current?.focus();

            return;
        }
        setManual(false);
        form.setData('doc_number_manual', 0);
        form.transform((d) => ({ ...d, doc_number_manual: 0, doc_number: '' }));
        form.post(route('documents.store'), opsiKirim);
    };

    const onSubmit = (e: FormEvent) => {
        e.preventDefault();
        // Dokumen lama & unggahan LANGSUNG BERLAKU tanpa tinjau–setuju, jadi
        // konfirmasinya wajib; jalur wizard tidak — draft masih bisa disunting.
        if (arsip) setKonfirmasi(true);
        else kirim();
    };

    const daftarGalat = Object.values(errors ?? {});

    return (
        <AppLayout
            judul={`Dokumen Baru ${type.code}${unggahanSaja ? '' : ' — Langkah 1'}`}
            remah={[{ label: 'Dokumen', href: route('documents.index') }, { label: 'Baru' }]}
            sub={
                unggahanSaja ? (
                    <>
                        {type.name} didaftarkan dengan <strong>mengunggah berkas PDF-nya</strong> — tanpa pengisian
                        berbab.
                    </>
                ) : (
                    'Tentukan nomor, judul, dan departemen dokumen. Sudah punya berkas PDF dokumen lama? Nyalakan saklar di bawah.'
                )
            }
        >
            <div className="grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardContent>
                        {daftarGalat.length > 0 ? (
                            <ul className="border-destructive/40 bg-destructive/10 text-destructive mb-3 list-inside list-disc rounded-xl border px-3 py-2 text-sm">
                                {daftarGalat.map((e) => (
                                    <li key={e}>{e}</li>
                                ))}
                            </ul>
                        ) : null}

                        <form onSubmit={onSubmit}>
                            {arsip ? (
                                <p className="border-chart-3/40 bg-chart-3/10 mb-3 flex items-start gap-2 rounded-xl border px-3 py-2 text-sm">
                                    <HugeiconsIcon
                                        icon={Archive01Icon}
                                        strokeWidth={1.5}
                                        className="mt-0.5 size-4 shrink-0"
                                    />
                                    <span>
                                        <strong>{unggahanSaja ? 'Dokumen unggahan.' : 'Mode dokumen lama.'}</strong>{' '}
                                        <strong>Langsung Berlaku</strong> tanpa tinjau–setuju; SH/DH diberi tahu dan
                                        pendaftarannya tercatat di Audit Log.
                                    </span>
                                </p>
                            ) : null}

                            {/* Sakelar hanya ada pada jenis berwizard. Pada FK/PX ia
                                dikunci menyala oleh `unggahanSaja`. */}
                            {unggahanSaja ? null : (
                                <div className="mb-4 flex items-center gap-2">
                                    <Switch
                                        id="arsipToggle"
                                        checked={arsip}
                                        onCheckedChange={(v) => {
                                            setArsip(v);
                                            if (v) setManual(true);
                                            form.setData('arsip', v ? 1 : 0);
                                        }}
                                    />
                                    <Label htmlFor="arsipToggle" className="text-sm font-semibold">
                                        Ini dokumen lama — unggah berkas PDF-nya
                                    </Label>
                                </div>
                            )}

                            <div className="mb-4">
                                <Label className="mb-1.5 text-sm font-semibold">Jenis Dokumen</Label>
                                <Input value={`${type.code} — ${type.name}`} readOnly disabled />
                            </div>

                            <div className="mb-4">
                                <Label className="mb-1.5 text-sm font-semibold">Departemen</Label>
                                {canChooseDept ? (
                                    <Select
                                        value={form.data.department_id ? String(form.data.department_id) : undefined}
                                        onValueChange={(v) => form.setData('department_id', Number(v))}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="— Pilih —" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {departments.map((d) => (
                                                <SelectItem key={d.id} value={String(d.id)}>
                                                    {d.code} — {d.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <Input value={dept ? `${dept.code} — ${dept.name}` : ''} readOnly disabled />
                                )}
                            </div>

                            <div className="mb-4">
                                <Label className="mb-1.5 text-sm font-semibold" htmlFor="title">
                                    Judul Dokumen
                                </Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    value={form.data.title}
                                    placeholder="mis. Prosedur Backup Data Server"
                                    onChange={(e) => form.setData('title', e.target.value)}
                                />
                            </div>

                            <div className="mb-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-sm font-semibold">Nomor Dokumen</Label>
                                    {/* Sakelar manual disembunyikan di mode dokumen lama:
                                        nomornya memang nomor lamanya, tak pernah
                                        dibangkitkan mesin. */}
                                    {arsip ? null : (
                                        <div className="flex items-center gap-2">
                                            <Switch
                                                id="manualToggle"
                                                checked={manual}
                                                onCheckedChange={(v) => {
                                                    setManual(v);
                                                    form.setData('doc_number_manual', v ? 1 : 0);
                                                }}
                                            />
                                            <Label htmlFor="manualToggle" className="text-sm">
                                                Input manual
                                            </Label>
                                        </div>
                                    )}
                                </div>

                                {manual || arsip ? (
                                    <>
                                        <Input
                                            ref={nomorRef}
                                            name="doc_number"
                                            value={form.data.doc_number}
                                            placeholder={`mis. ${prefix}-SOP-ICTMD-05`}
                                            onChange={(e) => form.setData('doc_number', e.target.value)}
                                        />
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {arsip ? (
                                                <>
                                                    Tulis nomor dokumen{unggahanSaja ? '' : ' lamanya'} apa adanya. Nomor
                                                    di luar pola resmi diterima dan ditandai badge{' '}
                                                    <strong>Nomor Lama</strong>.
                                                </>
                                            ) : (
                                                'Pastikan nomor unik dan sesuai format.'
                                            )}
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <Input value={numberPreview} readOnly disabled />
                                        <p className="text-muted-foreground mt-1 flex items-center gap-1.5 text-xs">
                                            <HugeiconsIcon
                                                icon={SparklesIcon}
                                                strokeWidth={1.5}
                                                className="size-3.5 shrink-0"
                                            />
                                            <span>
                                                <strong>Nomor sementara</strong> — final dikunci setelah disetujui.
                                                Format: {prefix}-JENIS-DEPT-NN.
                                            </span>
                                        </p>
                                    </>
                                )}
                            </div>

                            {/* Isian khusus dokumen lama. Metadata di sini sudah
                                TERCETAK di berkasnya, jadi ia diketik ulang sebagai
                                data supaya daftar induk & penomoran bisa membacanya —
                                bukan supaya dicetak lagi. */}
                            {arsip ? (
                                <>
                                    <div className="mt-3 grid gap-3 md:grid-cols-3">
                                        <div>
                                            <Label className="mb-1.5 text-sm font-semibold">Edisi</Label>
                                            <Input
                                                type="number"
                                                name="edisi"
                                                min={1}
                                                max={99}
                                                value={form.data.edisi}
                                                onChange={(e) => form.setData('edisi', Number(e.target.value))}
                                            />
                                        </div>
                                        <div>
                                            <Label className="mb-1.5 text-sm font-semibold">No. Revisi</Label>
                                            <Input
                                                type="number"
                                                name="no_revisi"
                                                min={0}
                                                max={5}
                                                value={form.data.no_revisi}
                                                onChange={(e) => form.setData('no_revisi', Number(e.target.value))}
                                            />
                                            <p className="text-muted-foreground mt-1 text-xs">
                                                0–5. Revisi ke-6 menaikkan Edisi.
                                            </p>
                                        </div>
                                        <div>
                                            <Label className="mb-1.5 text-sm font-semibold">Tanggal Efektif</Label>
                                            <Input
                                                type="date"
                                                name="tanggal_efektif"
                                                value={form.data.tanggal_efektif}
                                                onChange={(e) => form.setData('tanggal_efektif', e.target.value)}
                                            />
                                            <p className="text-muted-foreground mt-1 text-xs">Kosong = hari ini.</p>
                                        </div>
                                    </div>

                                    <div className="mt-3">
                                        <Label className="mb-1.5 text-sm font-semibold">
                                            Berkas PDF {unggahanSaja ? type.name : 'Dokumen Lama'}
                                        </Label>
                                        <Input
                                            type="file"
                                            name="berkas"
                                            accept="application/pdf"
                                            onChange={(e) => form.setData('berkas', e.target.files?.[0] ?? null)}
                                        />
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            PDF saja, maksimal <strong>40 MB</strong> (batas server). Berkas disimpan
                                            privat — hanya bisa dibuka lewat tombol PDF oleh pengguna yang berhak.
                                        </p>
                                    </div>
                                </>
                            ) : null}

                            <div className="mt-6 flex flex-wrap gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    <HugeiconsIcon
                                        icon={arsip ? Archive01Icon : CircleArrowRight01Icon}
                                        strokeWidth={1.5}
                                    />
                                    {arsip ? 'Daftarkan & Berlakukan' : 'Buat & Lanjut Pengisian'}
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={route('documents.index')}>Batal</Link>
                                </Button>
                            </div>
                        </form>

                        <ConfirmDialog
                            buka={konfirmasi}
                            onUbahBuka={setKonfirmasi}
                            judul={`Daftarkan ${unggahanSaja ? type.code : 'Dokumen Lama'}?`}
                            pesan="Dokumen ini langsung BERLAKU tanpa melewati tinjau–setuju. Lanjutkan?"
                            tombolYa="Ya, daftarkan"
                            onKonfirmasi={kirim}
                        />

                        {/* Nomor bentrok — dipakai ulang ConfirmDialog, bukan
                            komponen baru: susunannya persis dua tombol yang
                            dibutuhkan ("Ketik nomor lain" = Batal, "Pakai nomor
                            otomatis" = Aksi), dan kedua tempat yang disebut K-F
                            (mode dokumen lama & sakelar "Input manual") ada di
                            halaman INI. */}
                        <ConfirmDialog
                            buka={bentrok}
                            onUbahBuka={(b) => {
                                setBentrok(b);
                                if (!b) nomorRef.current?.focus();
                            }}
                            judul="Nomor Sudah Dipakai"
                            pesan={
                                <>
                                    Nomor <strong>{form.data.doc_number || '—'}</strong> sudah dipakai dokumen lain.
                                    Nomor bebas berikutnya adalah <strong>{nomorSaran}</strong>.
                                </>
                            }
                            tombolBatal="Ketik nomor lain"
                            tombolYa="Pakai nomor otomatis"
                            onKonfirmasi={pakaiNomorOtomatis}
                        />
                    </CardContent>
                </Card>

                <Card className="bg-muted/40 h-fit">
                    <CardContent>
                        <h2 className="text-muted-foreground flex items-center gap-1.5 font-semibold">
                            <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                            Tentang Penomoran
                        </h2>
                        <p className="text-muted-foreground mt-2 mb-2 text-sm">
                            Nomor mengikuti pola tetap dan bertambah otomatis per jenis + departemen:
                        </p>
                        <code className="mb-2 block text-sm">{prefix}-SOP-ICTMD-01</code>
                        <p className="text-muted-foreground text-sm">
                            Aktifkan <strong>Input manual</strong> hanya bila perlu menyesuaikan nomor lama.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
