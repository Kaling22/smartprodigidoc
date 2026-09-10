import {
    Delete02Icon, EraserIcon, FloppyDiskIcon, Mail01Icon, OctagonAlertIcon, PlugSocketIcon,
    PulseIcon, RefreshCwIcon, RepeatIcon, RoboticIcon, SentIcon, ShieldKeyIcon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import {
    Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import { Checkbox } from '@/components/ui-maia/checkbox';
import {
    Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader,
    DialogTitle, DialogTrigger,
} from '@/components/ui-maia/dialog';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { Separator } from '@/components/ui-maia/separator';
import { Spinner } from '@/components/ui-maia/spinner';
import { Switch } from '@/components/ui-maia/switch';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui-maia/table';
import { Textarea } from '@/components/ui-maia/textarea';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { Kesehatan, SistemProps } from '@/types/pengaturan';

/** Penanda "tanpa cadangan" untuk Radix Select, yang tak menerima string kosong. */
const TANPA = '*';

/**
 * "Konfigurasi Sistem" — kembaran V2 `pages/Pengaturan/Sistem.tsx`.
 *
 * DUA kartu utama di satu layar, dan keduanya memang sepasang: yang satu
 * menyetel satu-satunya layanan luar yang dipanggil sistem ini, yang lain
 * menjawab "sistemnya masih waras?". Keduanya dibuka pada saat yang sama —
 * waktu ada yang tidak beres.
 *
 * TIGA hal yang sengaja TIDAK ada di sini, dan tetap tak ada sesudah migrasi:
 *
 *   • Kotak isian perintah artisan. Yang tersedia dua tombol tetap. Kotak
 *     perintah adalah eksekusi kode jarak jauh dengan nama lain.
 *   • Kotak alamat tujuan email uji. Tujuannya SELALU alamat Admin yang sedang
 *     login (`PengaturanController::ujiEmail`).
 *   • Kunci API dalam bentuk terbaca. Ia bahkan tak sampai ke props — lihat
 *     `Arr::except` di controller.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — isian kartu AI dibungkus `FieldGroup`, jadi jarak antar-kelompok
 *    datang dari kit alih-alih dari `grid gap-5` yang diketik. Yang BERTAMBAH:
 *    `aria-invalid` kini juga dipasang pada `SelectTrigger` penyedia utama &
 *    cadangan — sebelumnya cuma `Field` yang menandainya, jadi kotak
 *    pilihannya sendiri tak pernah merah (preseden `V2/Users/Create.tsx`).
 *  · §3.9 — kotak centang "Hapus kunci yang tersimpan" jadi
 *    `Field orientation="horizontal"`; `htmlFor` ↔ `id` tetap berpasangan.
 *  · §3.7 — tabel Kesehatan turun ke `CardContent className="px-0"` dan
 *    pembungkusnya tinggal `overflow-x-auto`; `rounded-lg border` V1 dilepas
 *    karena kartunya sudah membawa tepi (preseden `V2/Pengaturan/Master.tsx`).
 *  · §3.4 — tiap ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}` +
 *    `size-4`.
 *  · Ketiga tombol yang menunggu jawaban server memakai `Spinner` alih-alih
 *    menukar teksnya jadi "Menyimpan…"/"Menghubungi…" — tombol yang berubah
 *    lebar saat ditekan menggeser tata letak tepat pada saat orang sedang
 *    menunggu (preseden `V2/Auth/Login`).
 */
export default function PengaturanSistem({
    ai,
    keyTopeng,
    cadanganKeyTopeng,
    penyedia,
    kesehatan,
}: SistemProps) {
    return (
        <AppLayout judul="Konfigurasi Sistem">
            <div className="grid gap-4 md:gap-6 lg:grid-cols-12">
                <div className="grid gap-4 md:gap-6 lg:col-span-7">
                    <KartuAi
                        ai={ai}
                        keyTopeng={keyTopeng}
                        cadanganKeyTopeng={cadanganKeyTopeng}
                        penyedia={penyedia}
                    />
                    <KartuUjiAi />
                </div>

                <div className="grid gap-4 md:gap-6 lg:col-span-5">
                    <KartuKesehatan kesehatan={kesehatan} />
                    <KartuEmail kesehatan={kesehatan} />
                    <KartuPemeliharaan />
                </div>
            </div>

            <ZonaBerbahaya jumlahDokumen={kesehatan.dokumen_semua} />
        </AppLayout>
    );
}

/* ================================== AI ================================== */

function KartuAi({ ai, keyTopeng, cadanganKeyTopeng, penyedia }: Omit<SistemProps, 'kesehatan'>) {
    const { data, setData, put, processing, errors } = useForm({
        enabled: ai.enabled,
        provider: ai.provider ?? '',
        model: ai.model ?? '',
        key: '',
        hapus_key: false,
        cadangan_provider: ai.cadangan_provider ?? '',
        cadangan_model: ai.cadangan_model ?? '',
        cadangan_key: '',
        hapus_cadangan_key: false,
    });

    const [konfirmasi, setKonfirmasi] = useState(false);

    function minta(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <form onSubmit={minta}>
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <HugeiconsIcon
                            icon={RoboticIcon}
                            strokeWidth={1.5}
                            className="text-primary size-4"
                            aria-hidden="true"
                        />
                        Bantuan AI
                    </CardTitle>
                    <CardDescription>
                        AI hanya <strong>membantu</strong> peninjau — ia tak pernah meloloskan atau
                        menolak dokumen sendiri.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <FieldGroup>
                        <Field orientation="horizontal">
                            <Switch
                                id="ai-enabled"
                                checked={data.enabled}
                                onCheckedChange={(v) => setData('enabled', v)}
                            />
                            <div className="grid gap-1">
                                <FieldLabel htmlFor="ai-enabled">Aktifkan bantuan AI</FieldLabel>
                                <FieldDescription>
                                    Dimatikan, panel AI hilang dari layar tinjau dan sisanya berjalan
                                    seperti biasa — peninjauan tak pernah bergantung padanya.
                                </FieldDescription>
                            </div>
                        </Field>

                        <div className="grid gap-4 sm:grid-cols-12">
                            <Field className="sm:col-span-5" data-invalid={!!errors.provider || undefined}>
                                <FieldLabel htmlFor="provider">Penyedia</FieldLabel>
                                <Select value={data.provider} onValueChange={(v) => setData('provider', v)}>
                                    <SelectTrigger
                                        id="provider"
                                        className="w-full"
                                        aria-invalid={!!errors.provider}
                                    >
                                        <SelectValue placeholder="— Pilih —" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(penyedia).map(([kunci, label]) => (
                                            <SelectItem key={kunci} value={kunci}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <FieldError
                                    errors={errors.provider ? [{ message: errors.provider }] : undefined}
                                />
                            </Field>

                            <Field className="sm:col-span-7" data-invalid={!!errors.model || undefined}>
                                <FieldLabel htmlFor="model">Model</FieldLabel>
                                <Input
                                    id="model"
                                    required
                                    maxLength={120}
                                    className="font-mono"
                                    placeholder="mis. gemini-2.0-flash"
                                    value={data.model}
                                    onChange={(e) => setData('model', e.target.value)}
                                    aria-invalid={!!errors.model}
                                />
                                <FieldError errors={errors.model ? [{ message: errors.model }] : undefined} />
                            </Field>
                        </div>

                        <IsianKunci
                            id="key"
                            label="Kunci API"
                            topeng={keyTopeng}
                            nilai={data.key}
                            onUbah={(v) => setData('key', v)}
                            hapus={data.hapus_key}
                            onUbahHapus={(v) => setData('hapus_key', v)}
                            galat={errors.key}
                            petunjukKosong={
                                <>
                                    Belum ada kunci tersimpan di basis data — sistem memakai nilai dari{' '}
                                    <span className="font-mono">.env</span> bila ada.
                                </>
                            }
                        />

                        <Separator />

                        {/* Cadangan: dipakai HANYA bila panggilan ke penyedia utama gagal
                            (kredit habis, key dicabut, penyedianya tumbang). Membawa key
                            sendiri, jadi satu akun yang kehabisan kredit tak ikut
                            menjatuhkan cadangannya. */}
                        <div className="grid gap-1">
                            <div className="text-muted-foreground flex items-center gap-2 text-sm font-semibold">
                                <HugeiconsIcon
                                    icon={RepeatIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Penyedia cadangan <span className="font-normal">(opsional)</span>
                            </div>
                            <p className="text-muted-foreground text-sm">
                                Dipakai hanya bila panggilan ke penyedia utama gagal. Kosongkan
                                penyedianya untuk mematikan cadangan.
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-12">
                            <Field
                                className="sm:col-span-5"
                                data-invalid={!!errors.cadangan_provider || undefined}
                            >
                                <FieldLabel htmlFor="cadangan_provider">Penyedia cadangan</FieldLabel>
                                <Select
                                    value={data.cadangan_provider || TANPA}
                                    onValueChange={(v) =>
                                        setData('cadangan_provider', v === TANPA ? '' : v)
                                    }
                                >
                                    <SelectTrigger
                                        id="cadangan_provider"
                                        className="w-full"
                                        aria-invalid={!!errors.cadangan_provider}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={TANPA}>— tanpa cadangan —</SelectItem>
                                        {Object.entries(penyedia).map(([kunci, label]) => (
                                            <SelectItem key={kunci} value={kunci}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <FieldError
                                    errors={
                                        errors.cadangan_provider
                                            ? [{ message: errors.cadangan_provider }]
                                            : undefined
                                    }
                                />
                            </Field>

                            <Field
                                className="sm:col-span-7"
                                data-invalid={!!errors.cadangan_model || undefined}
                            >
                                <FieldLabel htmlFor="cadangan_model">Model cadangan</FieldLabel>
                                <Input
                                    id="cadangan_model"
                                    maxLength={120}
                                    className="font-mono"
                                    value={data.cadangan_model}
                                    onChange={(e) => setData('cadangan_model', e.target.value)}
                                    aria-invalid={!!errors.cadangan_model}
                                />
                                <FieldError
                                    errors={
                                        errors.cadangan_model
                                            ? [{ message: errors.cadangan_model }]
                                            : undefined
                                    }
                                />
                            </Field>

                            <div className="sm:col-span-12">
                                <IsianKunci
                                    id="cadangan_key"
                                    label="Kunci API cadangan"
                                    topeng={cadanganKeyTopeng}
                                    nilai={data.cadangan_key}
                                    onUbah={(v) => setData('cadangan_key', v)}
                                    hapus={data.hapus_cadangan_key}
                                    onUbahHapus={(v) => setData('hapus_cadangan_key', v)}
                                    galat={errors.cadangan_key}
                                />
                            </div>
                        </div>
                    </FieldGroup>
                </CardContent>

                <CardFooter className="flex-wrap items-center justify-between gap-2">
                    <span className="text-muted-foreground flex items-center gap-1.5 text-sm">
                        <HugeiconsIcon
                            icon={ShieldKeyIcon}
                            strokeWidth={1.5}
                            className="size-4"
                            aria-hidden="true"
                        />
                        Kunci disimpan tersandi; audit log mencatat perubahannya tanpa isinya.
                    </span>
                    <Button type="submit" disabled={processing}>
                        {processing ? (
                            <Spinner />
                        ) : (
                            <HugeiconsIcon icon={FloppyDiskIcon} strokeWidth={1.5} className="size-4" />
                        )}
                        Simpan
                    </Button>
                    <ConfirmDialog
                        judul="Simpan setelan AI?"
                        pesan="Setelan AI berlaku untuk peninjauan berikutnya. Kunci API yang dikosongkan tidak akan terhapus."
                        tombolYa="Ya, simpan"
                        buka={konfirmasi}
                        onUbahBuka={setKonfirmasi}
                        onKonfirmasi={() => put(route('pengaturan.sistem.ai'))}
                    />
                </CardFooter>
            </Card>
        </form>
    );
}

/**
 * Satu kotak kunci API + kotak centang penghapusnya.
 *
 * Kosong berarti PERTAHANKAN YANG LAMA, bukan hapus — aturan itu hidup di
 * `SimpanAiRequest`, dan yang di sini cuma menuliskannya supaya Admin tak
 * menebak. Menghapus menuntut niat tersendiri: kotak centang, dan kotak itu
 * hanya muncul kalau memang ada kunci tersimpan.
 */
function IsianKunci({
    id,
    label,
    topeng,
    nilai,
    onUbah,
    hapus,
    onUbahHapus,
    galat,
    petunjukKosong,
}: {
    id: string;
    label: string;
    topeng: string | null;
    nilai: string;
    onUbah: (v: string) => void;
    hapus: boolean;
    onUbahHapus: (v: boolean) => void;
    galat?: string;
    petunjukKosong?: ReactNode;
}) {
    return (
        <Field data-invalid={!!galat || undefined}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <Input
                id={id}
                type="password"
                maxLength={400}
                autoComplete="new-password"
                className="font-mono"
                placeholder={topeng ?? 'belum disetel'}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={!!galat}
            />
            <FieldDescription>
                {topeng ? (
                    <>
                        Tersimpan &amp; tersandi: <span className="font-mono">{topeng}</span>.
                        Kosongkan untuk mempertahankannya.
                    </>
                ) : (
                    petunjukKosong
                )}
            </FieldDescription>
            {topeng ? (
                <Field orientation="horizontal">
                    <Checkbox
                        id={`hapus-${id}`}
                        checked={hapus}
                        onCheckedChange={(v) => onUbahHapus(v === true)}
                    />
                    <FieldLabel htmlFor={`hapus-${id}`} className="text-destructive font-normal">
                        Hapus kunci yang tersimpan
                    </FieldLabel>
                </Field>
            ) : null}
            <FieldError errors={galat ? [{ message: galat }] : undefined} />
        </Field>
    );
}

/**
 * Uji koneksi AI.
 *
 * DI LUAR kartu penyimpan setelan, dan itu bukan soal tata letak: form
 * bersarang tidak sah di HTML, dan tombol di dalam form yang salah akan
 * MENYIMPAN setelan, bukan mengujinya. Yang diuji adalah kunci yang SUDAH
 * TERSIMPAN.
 */
function KartuUjiAi() {
    return (
        <Card>
            <CardContent className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm">
                    <span className="font-semibold">Uji koneksi AI.</span> Memanggil penyedia sekali
                    tanpa membangkitkan tinjauan — menjawab &ldquo;kuncinya hidup atau tidak&rdquo;
                    dalam hitungan detik, bukan menit. Yang diuji adalah setelan yang sudah{' '}
                    <strong>tersimpan</strong>.
                </p>
                <TombolAksi
                    url={route('pengaturan.sistem.uji-ai')}
                    ikon={PlugSocketIcon}
                    label="Uji koneksi AI"
                    judul="Uji koneksi AI?"
                    pesan="Satu panggilan ke penyedia AI, tanpa biaya tinjauan. Batas 6 kali per menit."
                    tombolYa="Ya, uji"
                />
            </CardContent>
        </Card>
    );
}

/* =============================== KESEHATAN =============================== */

function KartuKesehatan({ kesehatan }: { kesehatan: Kesehatan }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={PulseIcon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Kesehatan Sistem
                </CardTitle>
                <CardDescription>Keadaan server &amp; isi basis data.</CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <div className="overflow-x-auto">
                    <Table>
                        <TableBody>
                            <BarisKesehatan label="PHP" mono>
                                {kesehatan.php}
                            </BarisKesehatan>
                            <BarisKesehatan label="Laravel" mono>
                                {kesehatan.laravel}
                            </BarisKesehatan>
                            <BarisKesehatan label="Zona waktu" mono>
                                {kesehatan.zona}
                            </BarisKesehatan>
                            <BarisKesehatan label="Mode debug">
                                {/* Bukan hiasan: APP_DEBUG menyala di server produksi
                                    menampilkan jejak galat lengkap — termasuk isi .env —
                                    kepada siapa pun yang memicu error. */}
                                {kesehatan.debug ? (
                                    <Badge variant="destructive">
                                        Menyala — matikan di server produksi
                                    </Badge>
                                ) : (
                                    <Badge variant="secondary">Mati</Badge>
                                )}
                            </BarisKesehatan>
                            <BarisKesehatan label="Lampiran foto">
                                {(kesehatan.lampiran_bytes / 1048576).toFixed(1)} MB{' '}
                                <span className="text-muted-foreground text-[.72rem]">
                                    (diperbarui tiap 5 menit)
                                </span>
                            </BarisKesehatan>
                            <BarisKesehatan label="Dokumen">
                                {kesehatan.dokumen} total &middot; {kesehatan.dokumen_berlaku} Berlaku
                            </BarisKesehatan>
                            <BarisKesehatan label="Pengguna aktif">{kesehatan.pengguna}</BarisKesehatan>
                            {/*
                             * Status per penyedia AI. Ada di sini, bukan di kartu AI
                             * di atas, karena pertanyaannya "apakah sistemnya sehat"
                             * — dan tombol "Uji koneksi AI" hanya menjawab kalau ada
                             * yang menekannya. Statusnya dirakit SERVER
                             * (PengaturanController::kesehatanAi), nol cabang di sini.
                             */}
                            {kesehatan.ai.map((p) => (
                                <BarisKesehatan key={p.label} label={`AI ${p.label}`}>
                                    <span className="font-mono text-sm">
                                        {p.provider ?? '—'} / {p.model ?? '—'}
                                    </span>{' '}
                                    &middot;{' '}
                                    {/* Merah HANYA untuk yang kosong: "dimatikan" itu pilihan Admin, bukan kerusakan. */}
                                    <span
                                        className={
                                            p.status.endsWith('kosong')
                                                ? 'text-destructive font-medium'
                                                : 'text-muted-foreground'
                                        }
                                    >
                                        {p.status}
                                    </span>
                                </BarisKesehatan>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

function BarisKesehatan({
    label,
    mono = false,
    children,
}: {
    label: string;
    mono?: boolean;
    children: ReactNode;
}) {
    return (
        <TableRow>
            <TableCell className="text-muted-foreground w-40 text-sm">{label}</TableCell>
            <TableCell className={mono ? 'font-mono text-sm' : 'text-sm'}>{children}</TableCell>
        </TableRow>
    );
}

function KartuEmail({ kesehatan }: { kesehatan: Kesehatan }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={Mail01Icon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Pengiriman Email
                </CardTitle>
                <CardDescription>
                    Kanal notifikasi penting; lonceng tak terpengaruh.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-3">
                <dl className="grid grid-cols-[5rem_1fr] gap-x-3 gap-y-1 text-sm">
                    <dt className="text-muted-foreground">Mailer</dt>
                    <dd className="font-mono">{kesehatan.mailer}</dd>
                    <dt className="text-muted-foreground">Host</dt>
                    <dd className="font-mono break-all">{kesehatan.mail_host ?? '—'}</dd>
                    <dt className="text-muted-foreground">Pengirim</dt>
                    <dd className="font-mono break-all">{kesehatan.mail_dari ?? '—'}</dd>
                </dl>

                {kesehatan.mailer === 'log' ? (
                    <Alert>
                        <AlertDescription>
                            Mailer masih <span className="font-mono">log</span> — email tidak
                            benar-benar terkirim, isinya ditulis ke{' '}
                            <span className="font-mono">storage/logs</span>.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {kesehatan.mail_paksa_ke ? (
                    /* Katup pengaman peralihan (docs/PANDUAN-PRODUKSI-EMAIL.md). Wajib
                       terlihat di sini: selama ia terisi, SELURUH notifikasi email
                       mendarat di satu alamat dan tak seorang penerima pun sadar. */
                    <Alert variant="destructive">
                        <AlertDescription>
                            Seluruh email sedang <strong>dibelokkan</strong> ke{' '}
                            <span className="font-mono">{kesehatan.mail_paksa_ke}</span> (
                            <span className="font-mono">MAIL_PAKSA_KE</span>). Penerima sesungguhnya
                            tidak menerima apa pun.
                        </AlertDescription>
                    </Alert>
                ) : null}
            </CardContent>
            <CardFooter>
                <TombolAksi
                    url={route('pengaturan.sistem.uji-email')}
                    ikon={SentIcon}
                    label="Kirim email uji ke alamat saya"
                    judul="Kirim email uji?"
                    pesan="Email uji dikirim ke alamat akun Anda sendiri. Batas 3 kali per menit."
                    tombolYa="Ya, kirim"
                    kelas="w-full"
                />
            </CardFooter>
        </Card>
    );
}

function KartuPemeliharaan() {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={EraserIcon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Pemeliharaan
                </CardTitle>
                <CardDescription>Dipakai setelah mengubah izin atau setelan.</CardDescription>
            </CardHeader>
            <CardContent>
                <p className="text-muted-foreground text-sm">
                    Membersihkan cache aplikasi dan cache izin (spatie). Tidak menyentuh satu pun
                    data: dokumen, pengguna, dan setelan tetap utuh — yang dibuang hanya salinan
                    sementaranya.
                </p>
            </CardContent>
            <CardFooter>
                <TombolAksi
                    url={route('pengaturan.sistem.cache')}
                    ikon={RefreshCwIcon}
                    label="Bersihkan cache"
                    judul="Bersihkan cache?"
                    pesan="Cache aplikasi & cache izin akan dibersihkan. Tidak ada data yang terhapus."
                    tombolYa="Ya, bersihkan"
                    kelas="w-full"
                    varian="secondary"
                />
            </CardFooter>
        </Card>
    );
}

/**
 * Tombol POST tanpa isian — uji email, uji AI, bersihkan cache.
 *
 * Ketiganya memanggil layanan luar atau perintah sistem, jadi ketiganya butuh
 * hal yang sama: konfirmasi lebih dulu (CLAUDE.md §13) dan status tertunda di
 * tombolnya — tanpa itu Admin menekan "Uji AI" berkali-kali karena tak ada
 * tanda apa pun bahwa yang pertama sedang berjalan. Yang berganti dari V1:
 * penandanya `Spinner` menggantikan ikon, bukan label kedua yang mengubah
 * lebar tombol (§preseden `V2/Auth/Login`).
 */
function TombolAksi({
    url,
    ikon,
    label,
    judul,
    pesan,
    tombolYa,
    kelas,
    varian = 'outline',
}: {
    url: string;
    ikon: typeof PlugSocketIcon;
    label: string;
    judul: string;
    pesan: string;
    tombolYa: string;
    kelas?: string;
    varian?: 'outline' | 'secondary';
}) {
    const { post, processing } = useForm({});

    return (
        <ConfirmDialog
            judul={judul}
            pesan={pesan}
            tombolYa={tombolYa}
            onKonfirmasi={() => post(url, { preserveScroll: true })}
            pemicu={
                <Button type="button" variant={varian} className={kelas} disabled={processing}>
                    {processing ? (
                        <Spinner />
                    ) : (
                        <HugeiconsIcon icon={ikon} strokeWidth={1.5} className="size-4" />
                    )}
                    {label}
                </Button>
            }
        />
    );
}

/* ============================ ZONA BERBAHAYA ============================ */

/**
 * Bersihkan SELURUH dokumen (PLAN C §C4).
 *
 * Tempatnya di layar ini, bukan di Dokumen Berlaku/Tidak Berlaku: di sana ia
 * bertetangga dengan tombol Musnahkan satuan yang bentuknya mirip, dan salah
 * klik di antara keduanya berbeda SATU dokumen dengan SELURUH arsip.
 *
 * Dua penghalang dipertahankan apa adanya — alasan tertulis + mengetik frasa
 * lengkap — lalu konfirmasi menyusul saat dikirim. Keduanya juga ditegakkan
 * server (`PurgeAllRequest`); yang di sini hanya supaya tak ada perjalanan
 * sia-sia ke server untuk kesalahan yang sudah terlihat.
 */
function ZonaBerbahaya({ jumlahDokumen }: { jumlahDokumen: number }) {
    const [buka, setBuka] = useState(false);
    const [konfirmasi, setKonfirmasi] = useState(false);
    const form = useForm({ alasan: '', konfirmasi: '' });

    function minta(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="text-destructive flex items-center gap-2">
                    <HugeiconsIcon
                        icon={OctagonAlertIcon}
                        strokeWidth={1.5}
                        className="size-4"
                        aria-hidden="true"
                    />
                    Zona Berbahaya
                </CardTitle>
            </CardHeader>
            <CardContent className="flex flex-wrap items-center justify-between gap-3">
                <p className="text-sm">
                    <span className="font-semibold">Bersihkan seluruh dokumen.</span> Menghapus{' '}
                    {jumlahDokumen} dokumen beserta lampiran, versi, dan riwayatnya — tanpa undo.
                    Akun, departemen, dan Audit Log tidak tersentuh.
                </p>

                <Dialog open={buka} onOpenChange={setBuka}>
                    <DialogTrigger asChild>
                        <Button variant="destructive" disabled={jumlahDokumen === 0}>
                            <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                            Bersihkan Seluruh Dokumen
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form onSubmit={minta} className="grid gap-4">
                            <DialogHeader>
                                <DialogTitle className="text-destructive flex items-center gap-2">
                                    <HugeiconsIcon
                                        icon={OctagonAlertIcon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Bersihkan Seluruh Dokumen
                                </DialogTitle>
                                <DialogDescription>
                                    {jumlahDokumen} dokumen — segala status, tujuh departemen,
                                    termasuk yang sudah diarsipkan. Beserta lampiran, berkas arsip,
                                    dan singgahan PDF-nya. Tidak ada undo.
                                </DialogDescription>
                            </DialogHeader>

                            <Alert>
                                <AlertDescription>
                                    Akun pengguna, departemen, jenis dokumen, menu Informasi, dan{' '}
                                    <strong>Audit Log</strong> tidak tersentuh.
                                </AlertDescription>
                            </Alert>

                            <FieldGroup>
                                <Field data-invalid={!!form.errors.alasan || undefined}>
                                    <FieldLabel htmlFor="alasan">Alasan pembersihan</FieldLabel>
                                    <Textarea
                                        id="alasan"
                                        rows={3}
                                        required
                                        minLength={10}
                                        maxLength={1000}
                                        placeholder="mis. mengosongkan data uji sebelum impor arsip lama"
                                        value={form.data.alasan}
                                        onChange={(e) => form.setData('alasan', e.target.value)}
                                        aria-invalid={!!form.errors.alasan}
                                    />
                                    <FieldDescription>
                                        Tersimpan di Audit Log sebagai satu-satunya jejak yang tersisa.
                                    </FieldDescription>
                                    <FieldError
                                        errors={
                                            form.errors.alasan
                                                ? [{ message: form.errors.alasan }]
                                                : undefined
                                        }
                                    />
                                </Field>

                                <Field data-invalid={!!form.errors.konfirmasi || undefined}>
                                    <FieldLabel htmlFor="konfirmasi">Ketik frasa konfirmasi</FieldLabel>
                                    <Input
                                        id="konfirmasi"
                                        required
                                        autoComplete="off"
                                        className="font-mono"
                                        placeholder="MUSNAHKAN SEMUA DOKUMEN"
                                        value={form.data.konfirmasi}
                                        onChange={(e) => form.setData('konfirmasi', e.target.value)}
                                        aria-invalid={!!form.errors.konfirmasi}
                                    />
                                    <FieldDescription>
                                        Harus persis{' '}
                                        <span className="font-mono font-semibold">
                                            MUSNAHKAN SEMUA DOKUMEN
                                        </span>
                                        .
                                    </FieldDescription>
                                    <FieldError
                                        errors={
                                            form.errors.konfirmasi
                                                ? [{ message: form.errors.konfirmasi }]
                                                : undefined
                                        }
                                    />
                                </Field>
                            </FieldGroup>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="ghost">
                                        Batal
                                    </Button>
                                </DialogClose>
                                <Button type="submit" variant="destructive" disabled={form.processing}>
                                    <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                                    Musnahkan Semua
                                </Button>
                            </DialogFooter>

                            {/* Konfirmasi terakhir, DI DALAM jendelanya: dua
                                lapisan modal bersaudara akan berebut jebakan
                                fokus, dan yang di luar bisa menutup yang di
                                dalam saat muncul. */}
                            <ConfirmDialog
                                judul="Bersihkan Seluruh Dokumen?"
                                pesan={`Musnahkan SELURUH ${jumlahDokumen} dokumen? Seluruh isi, versi, riwayat, lampiran, dan masukan hilang permanen. Tidak ada undo.`}
                                tombolYa="Ya, musnahkan semua"
                                destruktif
                                buka={konfirmasi}
                                onUbahBuka={setKonfirmasi}
                                onKonfirmasi={() =>
                                    form.delete(route('documents.purgeAll'), {
                                        // Jendelanya hanya ditutup kalau memang
                                        // berhasil — galat validasi harus tetap
                                        // terbaca di tempat isiannya, bukan
                                        // hilang bersama jendelanya.
                                        onSuccess: () => {
                                            setBuka(false);
                                            form.reset();
                                        },
                                    })
                                }
                            />
                        </form>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    );
}
