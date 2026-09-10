/**
 * KEMBARAN MAIA dari `resources/js/components/dokumen/DialogAksi.tsx`
 * (PATOKAN-GAYA-V2 Fase 1).
 *
 * Logikanya SALIN PERSIS — nol baris keputusan bergeser: rute, nama field,
 * `required`/`maxLength`/`minLength`, urutan konfirmasi, dan penutupan jendela
 * sesudah kiriman berhasil semuanya tetap. Yang berubah dari kit mana
 * komponennya diambil (`ui` → `ui-maia`) dan pustaka ikonnya (lucide →
 * hugeicons, PATOKAN §3.4). Berkas aslinya sengaja TIDAK disentuh: halaman lama
 * masih memakainya sebagai pembanding selama jendela pratinjau. Fase 7 menukar
 * keduanya dan mencopot yang lama.
 *
 * Karena itu tiap perbaikan perilaku di sini WAJIB ikut ke berkas aslinya, dan
 * sebaliknya — sampai jendela pratinjau ditutup.
 *
 * TIGA penyimpangan dari berkas asli, dan ketiganya patokan Fase 0:
 *  • `<Label>` + `<Textarea>` + `<p className="text-destructive">` menjadi
 *    `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`
 *    (§3.9). Galatnya tetap datang dari `errors` Inertia, bukan state sendiri;
 *    yang bertambah cuma `aria-invalid` pada kendali dan `data-invalid` pada
 *    `Field` — dua atribut yang memang wajib berpasangan di sana;
 *  • kotak tiap masukan `rounded-lg` → `rounded-2xl`, disamakan dengan
 *    `ui-maia/card` (§3.2);
 *  • ikon dalam tombol & `Alert` ditulis eksplisit `strokeWidth={1.5}` +
 *    `size-4` (§3.4).
 *
 * Yang TIDAK dipindah ke `Field orientation="horizontal"`: baris centang
 * Masukan Lapangan. Di sana `<Label>` sengaja MEMBUNGKUS checkbox beserta
 * seluruh kutipannya, sehingga mengklik di mana pun di kartu itu ikut
 * mencentang — perilaku yang hilang begitu label jadi saudara checkbox.
 */

/**
 * Empat jendela aksi dokumen.
 *
 * Disatukan dalam SATU berkas karena keempatnya bukan empat hal melainkan satu
 * pola yang dipakai empat kali, dan pola itulah yang mudah menyimpang bila
 * disalin: **formulirnya diisi DULU, konfirmasi MENYUSUL saat dikirim**
 * (CLAUDE.md v4 §4). Dulu tombolnya langsung memunculkan SweetAlert, dan alasan
 * yang wajib ditulis tak punya tempat.
 *
 * Konfirmasinya `ConfirmDialog` terkendali (PATOKAN §3.8): tombol Kirim tetap
 * `type="submit"`, sehingga `required`/`minLength` peramban tetap bekerja dan
 * konfirmasi hanya muncul untuk formulir yang sudah sah.
 *
 * Jendelanya ditutup sendiri sesudah kiriman berhasil — halaman dimuat ulang
 * Inertia, dan jendela yang tertinggal terbuka menampilkan keadaan yang basi.
 *
 * DUA cara memakainya, sama seperti `ConfirmDialog`:
 *
 *   1. **Berpemicu** — oper `pemicu`; komponen ini yang memegang buka-tutupnya.
 *   2. **Dikendalikan** — oper `buka` + `onUbahBuka`, tanpa `pemicu`. WAJIB
 *      dipakai bila pembukanya sebuah item di dalam `StripAksi`: Radix
 *      MELEPAS isi menunya begitu item dipilih, jadi jendela yang dirender di
 *      dalamnya ikut lenyap sebelum sempat terlihat.
 */
import {
    CalendarRemove01Icon, CircleSlashIcon, Delete02Icon, InformationCircleIcon,
    Message01Icon, OctagonAlertIcon, RefreshCwIcon, SentIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import { Checkbox } from '@/components/ui-maia/checkbox';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui-maia/dialog';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { Label } from '@/components/ui-maia/label';
import { Separator } from '@/components/ui-maia/separator';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DaftarMasukan } from '@/components/v2/DaftarMasukan';
import type { BarisMasukan, JadwalPembuat, JanjiRevisi } from '@/types/dokumen';

/** Kerangka bersama: pemicu → jendela berformulir → konfirmasi. */
function JendelaFormulir({
    pemicu,
    judul,
    keterangan,
    anak,
    tombolKirim,
    konfirmasiJudul,
    konfirmasiPesan,
    konfirmasiYa,
    destruktif = false,
    sedangKirim,
    onKirim,
    lebar,
    buka: bukaLuar,
    onUbahBuka,
}: {
    pemicu?: ReactNode;
    judul: ReactNode;
    keterangan?: ReactNode;
    anak: ReactNode;
    tombolKirim: ReactNode;
    konfirmasiJudul: string;
    konfirmasiPesan: string;
    konfirmasiYa: string;
    destruktif?: boolean;
    sedangKirim: boolean;
    /** Dipanggil sesudah konfirmasi; `tutup()` dioper supaya jendelanya menutup. */
    onKirim: (tutup: () => void) => void;
    lebar?: string;
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
}) {
    const [bukaSendiri, setBukaSendiri] = useState(false);
    const [konfirmasi, setKonfirmasi] = useState(false);
    const dikendalikan = bukaLuar !== undefined;
    const buka = dikendalikan ? bukaLuar : bukaSendiri;
    const setBuka = dikendalikan ? (onUbahBuka ?? (() => {})) : setBukaSendiri;

    function ajukan(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <Dialog open={buka} onOpenChange={setBuka}>
            {pemicu ? <DialogTrigger asChild>{pemicu}</DialogTrigger> : null}
            <DialogContent className={lebar}>
                <form onSubmit={ajukan} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{judul}</DialogTitle>
                        {keterangan ? <DialogDescription>{keterangan}</DialogDescription> : null}
                    </DialogHeader>

                    <div className="max-h-[60vh] overflow-y-auto pr-1">{anak}</div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={() => setBuka(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={sedangKirim} variant={destruktif ? 'destructive' : 'default'}>
                            {tombolKirim}
                        </Button>
                    </DialogFooter>
                </form>

                <ConfirmDialog
                    judul={konfirmasiJudul}
                    pesan={konfirmasiPesan}
                    tombolYa={konfirmasiYa}
                    destruktif={destruktif}
                    buka={konfirmasi}
                    onUbahBuka={setKonfirmasi}
                    onKonfirmasi={() => onKirim(() => setBuka(false))}
                />
            </DialogContent>
        </Dialog>
    );
}

/**
 * "Beri Masukan" untuk Non-Staff (FITUR-BARU-v4 §3, kanal WEB).
 *
 * Kembaran layar Report di aplikasi mobile — orang lapangan tak selalu memegang
 * HP saat berada di kantor; keduanya menulis ke tabel yang sama. Riwayat
 * masukan si pengirim atas dokumen ini ikut ditampilkan supaya ia tahu
 * masukannya berujung ke mana tanpa berpindah halaman.
 */
export function DialogMasukan({
    doc,
    masukanSaya,
    ...kendali
}: {
    doc: { id: number; nomor: string; dept: string | null };
    masukanSaya: BarisMasukan[];
    /** Tombol pembuka. Kosongkan bila jendelanya dikendalikan dari luar. */
    pemicu?: ReactNode;
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
}) {
    const form = useForm({ isi: '' });

    return (
        <JendelaFormulir
            {...kendali}
            judul={
                <span className="flex items-center gap-2">
                    <HugeiconsIcon icon={Message01Icon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                    Beri Masukan — <span className="font-mono">{doc.nomor}</span>
                </span>
            }
            sedangKirim={form.processing}
            konfirmasiJudul="Kirim Masukan?"
            konfirmasiPesan={`Kirim masukan ini ke Section Head / Departemen Head ${doc.dept ?? ''}?`}
            konfirmasiYa="Ya, kirim"
            tombolKirim={
                <>
                    <HugeiconsIcon icon={SentIcon} strokeWidth={1.5} className="size-4" />
                    Kirim Masukan
                </>
            }
            onKirim={(tutup) =>
                form.post(route('documents.feedback.store', doc.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset('isi');
                        tutup();
                    },
                })
            }
            anak={
                <div className="grid gap-3">
                    <Alert>
                        <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                        <AlertDescription>
                            Masukan tidak mengubah isi dokumen. Ia dikirim ke atasan Anda sebagai bahan
                            pertimbangan revisi.
                        </AlertDescription>
                    </Alert>

                    <FieldGroup>
                        <Field data-invalid={!!form.errors.isi || undefined}>
                            <FieldLabel htmlFor={`isi-${doc.id}`}>Isi masukan</FieldLabel>
                            <Textarea
                                id={`isi-${doc.id}`}
                                rows={4}
                                required
                                maxLength={2000}
                                placeholder="mis. Langkah 5 tidak sesuai kondisi pit; alat pelindung belum disebut."
                                value={form.data.isi}
                                aria-invalid={!!form.errors.isi}
                                onChange={(e) => form.setData('isi', e.target.value)}
                            />
                            <FieldError errors={form.errors.isi ? [{ message: form.errors.isi }] : undefined} />
                        </Field>
                    </FieldGroup>

                    {masukanSaya.length > 0 ? (
                        <>
                            <Separator />
                            <p className="text-sm font-semibold">Masukan Anda sebelumnya</p>
                            {/* `bolehMembalas` WAJIB false di sini: komponennya
                                dipasang DI DALAM form, dan form bersarang tak sah. */}
                            <DaftarMasukan masukan={masukanSaya} bolehMembalas={false} />
                        </>
                    ) : null}
                </div>
            }
        />
    );
}

/**
 * "Ajukan Revisi" Tipe B (FITUR-BARU-v4 §4 & §5).
 *
 * Masukan lapangan yang dicentang ikut jadi alasan revisi dan statusnya berubah
 * `diadopsi` — masukan jadi pemicu tindakan, bukan arsip mati.
 */
export function DialogRevisi({
    doc,
    masukanRevisi,
    jadwalPembuat,
    tercentang = [],
    ...kendali
}: {
    doc: { id: number; nomor: string; janji_revisi: JanjiRevisi };
    masukanRevisi: BarisMasukan[];
    /** Keterangan "pembuat sedang cuti"; `null` bila ia tersedia. */
    jadwalPembuat?: JadwalPembuat | null;
    /**
     * Masukan yang langsung TERCENTANG saat jendela dibuka — perilaku
     * `$masukanTercentang`, yang hanya dipakai tombol "Revisi" di Log Dokumen →
     * Masukan Lapangan.
     */
    tercentang?: number[];
    /** Tombol pembuka. Kosongkan bila jendelanya dikendalikan dari luar. */
    pemicu?: ReactNode;
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
}) {
    const form = useForm<{ alasan: string; masukan: number[] }>({ alasan: '', masukan: tercentang });
    const [edisi, revisi] = doc.janji_revisi;

    function alihMasukan(id: number, pilih: boolean) {
        form.setData(
            'masukan',
            pilih ? [...form.data.masukan, id] : form.data.masukan.filter((m) => m !== id),
        );
    }

    return (
        <JendelaFormulir
            {...kendali}
            lebar="sm:max-w-2xl"
            judul={
                <span className="flex items-center gap-2">
                    <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                    Revisi — <span className="font-mono">{doc.nomor}</span>
                </span>
            }
            sedangKirim={form.processing}
            konfirmasiJudul="Mulai Revisi?"
            konfirmasiPesan={`Buat Edisi ${edisi} Revisi ${revisi} untuk ${doc.nomor}? Versi lama tetap Berlaku sementara sampai versi baru disetujui.`}
            konfirmasiYa="Ya, revisi"
            tombolKirim={
                <>
                    <HugeiconsIcon icon={RefreshCwIcon} strokeWidth={1.5} className="size-4" />
                    Revisi
                </>
            }
            onKirim={(tutup) =>
                form.post(route('documents.requestRevision', doc.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset();
                        tutup();
                    },
                })
            }
            anak={
                <div className="grid gap-3">
                    {/* Jadwal PEMBUAT — KETERANGAN saja (§6). Pada GL ketersediaan
                        tak pernah menghalangi: SH tetap boleh mengajukan revisi,
                        ia hanya perlu tahu draftnya akan menunggu. */}
                    {jadwalPembuat ? (
                        <Alert>
                            <HugeiconsIcon icon={CalendarRemove01Icon} strokeWidth={1.5} className="size-4" />
                            <AlertDescription>
                                {jadwalPembuat.nama} (pembuat) sedang {jadwalPembuat.jenis} sampai{' '}
                                <strong>{jadwalPembuat.kembali}</strong>. Draft revisi tetap dibuat atas
                                namanya dan menunggu sampai ia kembali.
                            </AlertDescription>
                        </Alert>
                    ) : null}

                    <FieldGroup>
                        <Field data-invalid={!!form.errors.alasan || undefined}>
                            <FieldLabel htmlFor={`alasan-${doc.id}`}>
                                Apa yang perlu direvisi? <span className="text-destructive">*</span>
                            </FieldLabel>
                            <Textarea
                                id={`alasan-${doc.id}`}
                                rows={4}
                                required
                                maxLength={2000}
                                placeholder="mis. Langkah 5 tidak lagi sesuai kondisi pit terbaru."
                                value={form.data.alasan}
                                aria-invalid={!!form.errors.alasan}
                                onChange={(e) => form.setData('alasan', e.target.value)}
                            />
                            <FieldDescription>Dibaca penyusun revisi, SH/DH, dan MD.</FieldDescription>
                            <FieldError
                                errors={form.errors.alasan ? [{ message: form.errors.alasan }] : undefined}
                            />
                        </Field>
                    </FieldGroup>

                    {masukanRevisi.length > 0 ? (
                        <>
                            <Separator />
                            <p className="flex items-center gap-2 text-sm font-semibold">
                                <HugeiconsIcon
                                    icon={Message01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Masukan Lapangan ({masukanRevisi.length})
                            </p>
                            <p className="text-muted-foreground text-sm">
                                Yang dicentang otomatis ikut tercatat sebagai alasan revisi, dan statusnya
                                menjadi <em>Diadopsi</em>.
                            </p>
                            {masukanRevisi.map((m) => (
                                <Label
                                    key={m.id}
                                    htmlFor={`msk-${m.id}`}
                                    className="grid grid-cols-[auto_1fr] items-start gap-2 rounded-2xl border p-2 font-normal"
                                >
                                    <Checkbox
                                        id={`msk-${m.id}`}
                                        checked={form.data.masukan.includes(m.id)}
                                        onCheckedChange={(v) => alihMasukan(m.id, v === true)}
                                    />
                                    <span className="text-sm">
                                        <span className="text-muted-foreground font-mono">{m.nomor}</span>
                                        <span className="block">{m.isi}</span>
                                        <span className="text-muted-foreground block text-xs">
                                            {m.oleh ?? '—'} · {m.waktu} WITA
                                        </span>
                                    </span>
                                </Label>
                            ))}
                        </>
                    ) : null}
                </div>
            }
        />
    );
}

/**
 * "Ajukan Nonaktif" (PLAN-REVISI-v6 Fase F).
 *
 * Dulu tombolnya langsung mematikan dokumen. Alasannya bukan formalitas: ia
 * satu-satunya keterangan yang dibaca SH/DH, MD, dan PJO berikutnya.
 */
export function DialogNonaktif({
    doc,
    ...kendali
}: {
    doc: { id: number; nomor: string };
    /** Tombol pembuka. Kosongkan bila jendelanya dikendalikan dari luar. */
    pemicu?: ReactNode;
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
}) {
    const form = useForm({ alasan: '' });

    return (
        <JendelaFormulir
            {...kendali}
            judul={
                <span className="flex items-center gap-2">
                    <HugeiconsIcon icon={CircleSlashIcon} strokeWidth={1.5} className="size-4" aria-hidden="true" />
                    Ajukan Nonaktif — <span className="font-mono">{doc.nomor}</span>
                </span>
            }
            sedangKirim={form.processing}
            konfirmasiJudul="Ajukan Nonaktif?"
            konfirmasiPesan="Pengajuan dikirim ke SH/DH departemen, lalu Management Development, lalu PJO. Dokumen tetap Berlaku sampai keputusan terakhir."
            konfirmasiYa="Ya, ajukan"
            tombolKirim={
                <>
                    <HugeiconsIcon icon={CircleSlashIcon} strokeWidth={1.5} className="size-4" />
                    Ajukan Nonaktif
                </>
            }
            onKirim={(tutup) =>
                form.post(route('nonaktif.ajukan', doc.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset();
                        tutup();
                    },
                })
            }
            anak={
                <div className="grid gap-3">
                    <Alert>
                        <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                        <AlertDescription>
                            Bila disetujui sampai tahap terakhir, dokumen menjadi <strong>Tidak Berlaku</strong>{' '}
                            dan <strong>nomornya dilepas</strong> — nomor itu bisa dipakai dokumen baru.
                        </AlertDescription>
                    </Alert>

                    <FieldGroup>
                        <Field data-invalid={!!form.errors.alasan || undefined}>
                            <FieldLabel htmlFor={`alasan-nonaktif-${doc.id}`}>
                                Alasan nonaktif <span className="text-destructive">*</span>
                            </FieldLabel>
                            <Textarea
                                id={`alasan-nonaktif-${doc.id}`}
                                rows={3}
                                required
                                maxLength={2000}
                                placeholder="mis. Pekerjaannya sudah tidak dilakukan sejak alat X dipensiunkan."
                                value={form.data.alasan}
                                aria-invalid={!!form.errors.alasan}
                                onChange={(e) => form.setData('alasan', e.target.value)}
                            />
                            <FieldDescription>
                                Dibaca SH/DH, Management Development, dan PJO; tercatat di Log Dokumen.
                            </FieldDescription>
                            <FieldError
                                errors={form.errors.alasan ? [{ message: form.errors.alasan }] : undefined}
                            />
                        </Field>
                    </FieldGroup>
                </div>
            }
        />
    );
}

/**
 * "Musnahkan" — Admin saja (§8).
 *
 * DUA isian yang sama-sama wajib, dan itulah sebabnya ini jendela penuh dan
 * bukan sekadar konfirmasi: alasan tertulis (min. 10 aksara) DAN nomor dokumen
 * yang harus diketik ulang persis. Berbeda dari "Tidak Berlaku" yang hanya
 * memindahkan; ini menghapus tanpa rollback.
 */
export function DialogMusnahkan({
    doc,
    ...kendali
}: {
    doc: { id: number; nomor: string; judul: string };
    /** Tombol pembuka. Kosongkan bila jendelanya dikendalikan dari luar. */
    pemicu?: ReactNode;
    buka?: boolean;
    onUbahBuka?: (buka: boolean) => void;
}) {
    const form = useForm({ alasan: '', konfirmasi_nomor: '' });

    return (
        <JendelaFormulir
            {...kendali}
            destruktif
            judul={
                <span className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={OctagonAlertIcon}
                        strokeWidth={1.5}
                        className="text-destructive size-4"
                        aria-hidden="true"
                    />
                    Musnahkan Dokumen
                </span>
            }
            sedangKirim={form.processing}
            konfirmasiJudul="Musnahkan Dokumen?"
            konfirmasiPesan={`Musnahkan ${doc.nomor}? Isi, riwayat tinjauan, persetujuan, versi, dan masukan ikut hilang permanen.`}
            konfirmasiYa="Ya, musnahkan"
            tombolKirim={
                <>
                    <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                    Musnahkan
                </>
            }
            onKirim={(tutup) =>
                form.delete(route('documents.purge', doc.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset();
                        tutup();
                    },
                })
            }
            anak={
                <div className="grid gap-3">
                    <Alert variant="destructive">
                        <HugeiconsIcon icon={OctagonAlertIcon} strokeWidth={1.5} className="size-4" />
                        <AlertDescription>
                            <span className="font-semibold">{doc.nomor}</span> — {doc.judul}. Seluruh isi,
                            versi, riwayat, lampiran, dan masukan hilang{' '}
                            <span className="font-semibold">tanpa bisa dikembalikan</span>. Nomornya kembali
                            ke kolam.
                        </AlertDescription>
                    </Alert>

                    <FieldGroup>
                        <Field data-invalid={!!form.errors.alasan || undefined}>
                            <FieldLabel htmlFor={`musnah-alasan-${doc.id}`}>Alasan penghapusan</FieldLabel>
                            <Textarea
                                id={`musnah-alasan-${doc.id}`}
                                rows={3}
                                required
                                minLength={10}
                                placeholder="mis. dokumen ganda, terbit karena kesalahan input"
                                value={form.data.alasan}
                                aria-invalid={!!form.errors.alasan}
                                onChange={(e) => form.setData('alasan', e.target.value)}
                            />
                            <FieldDescription>
                                Tersimpan di Audit Log sebagai satu-satunya jejak yang tersisa.
                            </FieldDescription>
                            <FieldError
                                errors={form.errors.alasan ? [{ message: form.errors.alasan }] : undefined}
                            />
                        </Field>

                        <Field data-invalid={!!form.errors.konfirmasi_nomor || undefined}>
                            <FieldLabel htmlFor={`musnah-nomor-${doc.id}`}>Ketik ulang nomor dokumen</FieldLabel>
                            <Input
                                id={`musnah-nomor-${doc.id}`}
                                required
                                autoComplete="off"
                                placeholder={doc.nomor}
                                value={form.data.konfirmasi_nomor}
                                aria-invalid={!!form.errors.konfirmasi_nomor}
                                onChange={(e) => form.setData('konfirmasi_nomor', e.target.value)}
                            />
                            <FieldDescription>
                                Harus persis <span className="font-semibold">{doc.nomor}</span>.
                            </FieldDescription>
                            <FieldError
                                errors={
                                    form.errors.konfirmasi_nomor
                                        ? [{ message: form.errors.konfirmasi_nomor }]
                                        : undefined
                                }
                            />
                        </Field>
                    </FieldGroup>
                </div>
            }
        />
    );
}
