import {
    Building02Icon, Files01Icon, FloppyDiskIcon, Idea01Icon, InformationCircleIcon, LockIcon,
    PlusSignIcon,
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
import { FieldError } from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui-maia/input-group';
import { Switch } from '@/components/ui-maia/switch';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui-maia/table';
import { Ikon } from '@/components/v2/Ikon';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type {
    BarisDepartemen, BarisJenis, BarisKategori, MasterProps,
} from '@/types/pengaturan';

/**
 * "Master Data" — kembaran V2 `pages/Pengaturan/Master.tsx`.
 *
 * SATU layar tiga kartu, bukan tiga halaman: ketiganya daftar acuan yang jarang
 * disentuh dan hampir selalu disentuh bersamaan (departemen baru biasanya
 * datang bersama kategori barunya).
 *
 * TAK ADA TOMBOL HAPUS di seluruh layar ini, dan itu disengaja: ketiga tabel
 * ditunjuk data lain. Yang tersedia saklar "Aktif" — berhenti ditawarkan,
 * bukan lenyap.
 *
 * BENTUK FORMULIRNYA DIPERTAHANKAN, termasuk alasannya: satu `<form>` kosong
 * per baris, ditautkan ke kotak isiannya lewat atribut `form="…"`. HTML
 * melarang `<form>` membungkus `<tr>`, dan React tidak menambal larangan itu —
 * bundel SSR (`smartpro:uji-render`) menghasilkan markup yang tetap dibaca
 * parser HTML peramban, yang akan memindahkan form itu keluar tabel diam-diam
 * sehingga tak satu pun input ikut terkirim. Atribut `form` adalah cara baku
 * menautkannya, dan ia pula yang membuat `required` peramban tetap bekerja.
 *
 * Kantong galatnya juga tetap: tiap pengiriman membawa `errorBag` bernama sama
 * dengan id form-nya (`dept-7`, `jenis-2`, `kat-baru`), dan Laravel membalasnya
 * ke kantong itu (`Inertia\Middleware::resolveValidationErrors`). Tanpa kantong
 * terpisah, satu baris yang gagal validasi akan mewarnai merah SELURUH baris di
 * tabelnya — sebab props `errors` memang satu untuk seluruh halaman.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — ketiga tabel turun ke `CardContent className="px-0"` dan
 *    pembungkusnya tinggal `overflow-x-auto`. `rounded-lg border` V1 dilepas:
 *    kartunya sudah membawa tepi, dan tepi kedua di dalamnya cuma menggandakan
 *    garis (preseden `V2/Documents/Index.tsx`).
 *  · §3.9 — `<p className="text-destructive text-xs">` per sel → `FieldError`.
 *    Kotak ikon kategori yang di V1 dirakit sendiri (`size-9 rounded-md border
 *    bg-muted` di samping `Input`) jadi `InputGroup` + `InputGroupAddon`, jadi
 *    nol angka geometri diketik — tingginya pindah ke pembungkusnya.
 *  · §3.4 — ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons` +
 *    `strokeWidth={1.5}`; ikon berkunci SERVER (`rupa[j.code]` pada lencana
 *    jenis, `informasi_kategori.ikon` pada pratinjau kategori) TETAP lewat
 *    `v2/Ikon`. `strokeWidth={2}` pada gembok `size-3`.
 *
 * **Yang sengaja TIDAK diubah:** kotak centang "Kolom yang dipakai" tetap
 * `<label>` yang MEMBUNGKUS `Checkbox`-nya, bukan `Field orientation="horizontal"`.
 * `Field` membawa `w-full` di kelas dasarnya, sehingga tiga centang yang harus
 * berjajar dalam satu `flex-wrap` akan pecah jadi tiga baris penuh — dan itu
 * mengubah tata letak sel, bukan mengembarkannya (alasan yang sama dengan
 * baris centang `DialogRevisi`, §9).
 */
export default function PengaturanMaster({
    departemen,
    jenis,
    kategori,
    jumlahInformasi,
    kolomTersedia,
    rupa,
}: MasterProps) {
    return (
        <AppLayout judul="Master Data">
            <Alert>
                <HugeiconsIcon icon={InformationCircleIcon} strokeWidth={1.5} className="size-4" />
                <AlertDescription>
                    Tidak ada penghapusan di layar ini. Departemen, jenis, dan kategori ditunjuk oleh
                    dokumen &amp; pengguna yang sudah ada, jadi yang tersedia adalah saklar{' '}
                    <strong>Aktif</strong>: barisnya berhenti ditawarkan pada formulir baru,
                    sementara seluruh dokumen dan penggunanya tetap utuh dan tetap terbaca.
                </AlertDescription>
            </Alert>

            <KartuDepartemen departemen={departemen} />
            <KartuJenis jenis={jenis} rupa={rupa} />
            <KartuKategori
                kategori={kategori}
                jumlahInformasi={jumlahInformasi}
                kolomTersedia={kolomTersedia}
            />
        </AppLayout>
    );
}

/* ============================== DEPARTEMEN ============================== */

function KartuDepartemen({ departemen }: Pick<MasterProps, 'departemen'>) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={Building02Icon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Departemen
                </CardTitle>
                <CardDescription>
                    Kode departemen ikut masuk ke nomor dokumen, jadi ia terkunci begitu departemen
                    itu punya dokumen.
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-36">Kode</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead className="w-32">Alias</TableHead>
                                <TableHead className="w-24 text-center">Dokumen</TableHead>
                                <TableHead className="w-24 text-center">Pengguna</TableHead>
                                <TableHead className="w-20 text-center">Aktif</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {departemen.map((d) => (
                                <BarisDept key={d.id} d={d} />
                            ))}
                            <BarisDept d={null} />
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

/** Satu baris departemen — `d === null` berarti baris "tambah baru". */
function BarisDept({ d }: { d: BarisDepartemen | null }) {
    const idForm = d ? `dept-${d.id}` : 'dept-baru';
    const form = useForm({
        code: d?.code ?? '',
        name: d?.name ?? '',
        alias: d?.alias ?? '',
        is_active: d ? d.is_active : true,
    });

    // Kode terkunci begitu departemen punya dokumen: ia sudah masuk ke nomor
    // yang beredar. Penjaga sesungguhnya tetap di SimpanDepartemenRequest —
    // yang di sini hanya supaya kehendaknya terbaca sebelum ditolak.
    const terkunci = !!d && d.documents_count > 0;

    return (
        <BarisSimpan
            form={form}
            idForm={idForm}
            baru={!d}
            judulKonfirmasi={d ? `Simpan ${d.code}?` : 'Tambah departemen?'}
            pesanKonfirmasi="Departemen yang dinonaktifkan berhenti ditawarkan pada pendaftaran akun & pembuatan dokumen. Dokumen dan penggunanya tidak berubah."
            kirim={() =>
                d
                    ? form.put(route('pengaturan.master.departemen.simpan', d.id), {
                          preserveScroll: true,
                          errorBag: idForm,
                      })
                    : form.post(route('pengaturan.master.departemen.tambah'), {
                          preserveScroll: true,
                          errorBag: idForm,
                          onSuccess: () => form.reset(),
                      })
            }
        >
            <TableCell>
                <Input
                    form={idForm}
                    className="font-mono uppercase"
                    maxLength={20}
                    required={!!d}
                    placeholder={d ? undefined : 'KODE'}
                    disabled={terkunci}
                    aria-label={`Kode departemen ${d?.code ?? 'baru'}`}
                    value={form.data.code}
                    onChange={(e) => form.setData('code', e.target.value)}
                    aria-invalid={!!form.errors.code}
                />
                {terkunci ? (
                    <span className="text-muted-foreground mt-1 flex items-center gap-1 text-[.68rem]">
                        <HugeiconsIcon icon={LockIcon} strokeWidth={2} className="size-3" aria-hidden="true" />
                        terkunci oleh {d!.documents_count} dokumen
                    </span>
                ) : null}
                <Galat pesan={form.errors.code} />
            </TableCell>
            <TableCell>
                <Input
                    form={idForm}
                    maxLength={100}
                    required={!!d}
                    placeholder={d ? undefined : 'Nama departemen baru'}
                    aria-label={`Nama departemen ${d?.code ?? 'baru'}`}
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    aria-invalid={!!form.errors.name}
                />
                <Galat pesan={form.errors.name} />
            </TableCell>
            <TableCell>
                <Input
                    form={idForm}
                    className="uppercase"
                    maxLength={50}
                    placeholder="Alias"
                    aria-label={`Alias departemen ${d?.code ?? 'baru'}`}
                    value={form.data.alias ?? ''}
                    onChange={(e) => form.setData('alias', e.target.value)}
                />
                <Galat pesan={form.errors.alias} />
            </TableCell>
            <TableCell className="text-center">
                {d ? <Badge variant="secondary">{d.documents_count}</Badge> : <Kosong />}
            </TableCell>
            <TableCell className="text-center">
                {d ? <Badge variant="secondary">{d.users_count}</Badge> : <Kosong />}
            </TableCell>
            <SelAktif
                nilai={form.data.is_active}
                onUbah={(v) => form.setData('is_active', v)}
                label={`Departemen ${d?.code ?? 'baru'} aktif`}
            />
        </BarisSimpan>
    );
}

/* ============================ JENIS DOKUMEN ============================ */

function KartuJenis({ jenis, rupa }: Pick<MasterProps, 'jenis' | 'rupa'>) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={Files01Icon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Jenis Dokumen
                </CardTitle>
                <CardDescription>
                    Nama &amp; saklar aktif saja. Menambah jenis baru menuntut schema JSON dan
                    template cetak, jadi ia tidak bisa dibuat dari layar mana pun.
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-28">Kode</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead className="w-28 text-center">Berjalan</TableHead>
                                <TableHead className="w-28 text-center">Berlaku</TableHead>
                                <TableHead className="w-20 text-center">Aktif</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {jenis.map((j) => (
                                <BarisJenisDok key={j.id} j={j} rupa={rupa} />
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}

function BarisJenisDok({ j, rupa }: { j: BarisJenis; rupa: MasterProps['rupa'] }) {
    const idForm = `jenis-${j.id}`;
    const form = useForm({ name: j.name, is_active: j.is_active });
    const [ikon, warna] = rupa[j.code] ?? ['bi-file-earmark-text', '#8392ab'];

    return (
        <BarisSimpan
            form={form}
            idForm={idForm}
            judulKonfirmasi={`Simpan ${j.code}?`}
            pesanKonfirmasi={`Jenis yang dinonaktifkan hilang dari menu Dokumen Baru dan tidak bisa disusun lagi. Dokumen ${j.code} yang sudah ada tetap terbaca dan tetap bisa dicetak.`}
            kirim={() =>
                form.put(route('pengaturan.master.jenis.simpan', j.id), {
                    preserveScroll: true,
                    errorBag: idForm,
                })
            }
        >
            <TableCell>
                {/* Warna hex-nya datang dari `Document::JENIS_META` lewat props
                    `rupa`, sama seperti titik warna matriks dasbor — ia dipakai
                    apa adanya, bukan diketik di sini (CLAUDE.md §13). */}
                <span className="flex items-center gap-1.5 font-mono font-semibold" style={{ color: warna }}>
                    <Ikon nama={ikon} className="size-4" />
                    {j.code}
                </span>
            </TableCell>
            <TableCell>
                <Input
                    form={idForm}
                    maxLength={100}
                    required
                    aria-label={`Nama jenis ${j.code}`}
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    aria-invalid={!!form.errors.name}
                />
                <Galat pesan={form.errors.name} />
            </TableCell>
            <TableCell className="text-center">
                <Badge variant="secondary">{j.berjalan_count}</Badge>
            </TableCell>
            <TableCell className="text-center">
                <Badge variant="default">{j.berlaku_count}</Badge>
            </TableCell>
            <SelAktif
                nilai={form.data.is_active}
                onUbah={(v) => form.setData('is_active', v)}
                label={`Jenis ${j.code} aktif`}
            />
        </BarisSimpan>
    );
}

/* ========================== KATEGORI INFORMASI ========================== */

function KartuKategori({
    kategori,
    jumlahInformasi,
    kolomTersedia,
}: Pick<MasterProps, 'kategori' | 'jumlahInformasi' | 'kolomTersedia'>) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={InformationCircleIcon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Kategori Informasi
                </CardTitle>
                <CardDescription>
                    Submenu pada menu Informasi. Kategori baru langsung tampil di sidebar dan di
                    aplikasi mobile — tanpa rilis kode.
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <div className="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-52">Nama</TableHead>
                                <TableHead className="w-44">Ikon</TableHead>
                                <TableHead>Deskripsi</TableHead>
                                <TableHead className="w-56">Kolom yang dipakai</TableHead>
                                <TableHead className="w-24 text-center">Dokumen</TableHead>
                                <TableHead className="w-20 text-center">Aktif</TableHead>
                                <TableHead className="w-20" />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {kategori.map((k) => (
                                <BarisKat
                                    key={k.id}
                                    k={k}
                                    jumlah={jumlahInformasi[k.slug] ?? 0}
                                    kolomTersedia={kolomTersedia}
                                />
                            ))}
                            <BarisKat k={null} jumlah={0} kolomTersedia={kolomTersedia} />
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
            <CardFooter className="text-muted-foreground border-t gap-2 pt-6 text-sm">
                <HugeiconsIcon
                    icon={Idea01Icon}
                    strokeWidth={1.5}
                    className="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>
                    Kolom yang bisa dipilih hanya <strong>Edisi</strong>, <strong>No. Revisi</strong>
                    , dan <strong>Tanggal Efektif</strong> — ketiganya sudah ada di tabel informasi.
                    Nomor &amp; judul selalu dipakai. Nama ikon diambil dari{' '}
                    <span className="font-mono">bootstrap-icons</span>, mis.{' '}
                    <span className="font-mono">bi-journal-bookmark</span>.
                </span>
            </CardFooter>
        </Card>
    );
}

/** Satu baris kategori — `k === null` berarti baris "tambah baru". */
function BarisKat({
    k,
    jumlah,
    kolomTersedia,
}: {
    k: BarisKategori | null;
    jumlah: number;
    kolomTersedia: MasterProps['kolomTersedia'];
}) {
    const idForm = k ? `kat-${k.id}` : 'kat-baru';
    const form = useForm({
        nama: k?.nama ?? '',
        deskripsi: k?.deskripsi ?? '',
        ikon: k?.ikon ?? 'bi-info-circle',
        kolom: k?.kolom ?? [],
        is_active: k ? k.is_active : true,
    });

    function pilihKolom(kunci: string, aktif: boolean) {
        form.setData(
            'kolom',
            aktif ? [...form.data.kolom, kunci] : form.data.kolom.filter((x) => x !== kunci),
        );
    }

    return (
        <BarisSimpan
            form={form}
            idForm={idForm}
            baru={!k}
            judulKonfirmasi={k ? `Simpan ${k.nama}?` : 'Tambah kategori?'}
            pesanKonfirmasi="Kategori yang dinonaktifkan tetap tampil di menu Informasi dalam keadaan tak bisa diklik, dan tidak menerima unggahan baru. Dokumen yang sudah ada tidak dihapus."
            kirim={() =>
                k
                    ? form.put(route('pengaturan.master.kategori.simpan', k.id), {
                          preserveScroll: true,
                          errorBag: idForm,
                      })
                    : form.post(route('pengaturan.master.kategori.tambah'), {
                          preserveScroll: true,
                          errorBag: idForm,
                          onSuccess: () => form.reset(),
                      })
            }
        >
            <TableCell>
                <Input
                    form={idForm}
                    maxLength={60}
                    required={!!k}
                    placeholder={k ? undefined : 'Nama kategori baru'}
                    aria-label={`Nama kategori ${k?.nama ?? 'baru'}`}
                    value={form.data.nama}
                    onChange={(e) => form.setData('nama', e.target.value)}
                    aria-invalid={!!form.errors.nama}
                />
                {/* Slug ditampilkan, tapi tak pernah bisa diubah: ia tersimpan
                    sebagai teks di `informasi.kategori`. */}
                <span className="text-muted-foreground mt-1 block font-mono text-[.68rem]">
                    {k ? k.slug : 'kuncinya dibuat otomatis dari nama'}
                </span>
                <Galat pesan={form.errors.nama} />
            </TableCell>
            <TableCell>
                {/* Pratinjau ikon jadi addon `InputGroup`, bukan kotak yang
                    dirakit sendiri di samping `Input`: tingginya pindah ke
                    pembungkus, jadi nol angka geometri diketik (§3.9). */}
                <InputGroup>
                    <InputGroupAddon>
                        <Ikon nama={form.data.ikon} className="size-4" />
                    </InputGroupAddon>
                    <InputGroupInput
                        form={idForm}
                        className="font-mono"
                        maxLength={50}
                        required={!!k}
                        aria-label={`Ikon kategori ${k?.nama ?? 'baru'}`}
                        value={form.data.ikon}
                        onChange={(e) => form.setData('ikon', e.target.value)}
                        aria-invalid={!!form.errors.ikon}
                    />
                </InputGroup>
                <Galat pesan={form.errors.ikon} />
            </TableCell>
            <TableCell>
                <Input
                    form={idForm}
                    maxLength={255}
                    placeholder="opsional"
                    aria-label={`Deskripsi kategori ${k?.nama ?? 'baru'}`}
                    value={form.data.deskripsi ?? ''}
                    onChange={(e) => form.setData('deskripsi', e.target.value)}
                />
                <Galat pesan={form.errors.deskripsi} />
            </TableCell>
            <TableCell>
                <div className="flex flex-wrap gap-3">
                    {Object.entries(kolomTersedia).map(([kunci, label]) => {
                        const id = `kol-${k?.id ?? 'baru'}-${kunci}`;

                        return (
                            <label key={kunci} htmlFor={id} className="flex items-center gap-1.5 text-sm">
                                <Checkbox
                                    id={id}
                                    checked={form.data.kolom.includes(kunci)}
                                    onCheckedChange={(v) => pilihKolom(kunci, v === true)}
                                />
                                {label}
                            </label>
                        );
                    })}
                </div>
            </TableCell>
            <TableCell className="text-center">
                {k ? <Badge variant="secondary">{jumlah}</Badge> : <Kosong />}
            </TableCell>
            <SelAktif
                nilai={form.data.is_active}
                onUbah={(v) => form.setData('is_active', v)}
                label={`Kategori ${k?.nama ?? 'baru'} aktif`}
            />
        </BarisSimpan>
    );
}

/* =============================== Pembantu =============================== */

/**
 * Kerangka satu baris tabel yang bisa disimpan.
 *
 * Tiga tabel memakainya, dan itu memang tiga kali pola yang persis sama: baris
 * = satu formulir, tombolnya `type="submit"` (supaya `required` peramban tetap
 * bekerja), konfirmasi menyusul sesudah formulirnya lolos (§3.8).
 */
function BarisSimpan({
    form,
    idForm,
    baru = false,
    judulKonfirmasi,
    pesanKonfirmasi,
    kirim,
    children,
}: {
    form: { processing: boolean };
    idForm: string;
    baru?: boolean;
    judulKonfirmasi: string;
    pesanKonfirmasi: string;
    kirim: () => void;
    children: ReactNode;
}) {
    const [konfirmasi, setKonfirmasi] = useState(false);

    function minta(e: FormEvent) {
        e.preventDefault();
        setKonfirmasi(true);
    }

    return (
        <TableRow className={baru ? 'bg-muted/40' : undefined}>
            {children}
            <TableCell className="text-right align-top">
                {/* Form KOSONG; isinya ditautkan lewat atribut `form` pada tiap
                    kotak isian — lihat docblock halaman. */}
                <form id={idForm} onSubmit={minta} />
                <Button
                    type="submit"
                    form={idForm}
                    size="icon"
                    variant={baru ? 'default' : 'outline'}
                    disabled={form.processing}
                    title={baru ? 'Tambah' : 'Simpan'}
                    aria-label={baru ? 'Tambah' : 'Simpan'}
                >
                    <HugeiconsIcon
                        icon={baru ? PlusSignIcon : FloppyDiskIcon}
                        strokeWidth={1.5}
                        className="size-4"
                    />
                </Button>
                <ConfirmDialog
                    judul={judulKonfirmasi}
                    pesan={pesanKonfirmasi}
                    tombolYa={baru ? 'Ya, tambahkan' : 'Ya, simpan'}
                    buka={konfirmasi}
                    onUbahBuka={setKonfirmasi}
                    onKonfirmasi={kirim}
                />
            </TableCell>
        </TableRow>
    );
}

/**
 * Sel saklar Aktif.
 *
 * Nilainya boolean penuh — bukan checkbox yang menghilang saat tak dicentang —
 * sehingga `prepareForValidation()` di ketiga Form Request tetap menerima
 * "matikan" sebagai kehendak, bukan sebagai "jangan ubah apa-apa".
 */
function SelAktif({
    nilai,
    onUbah,
    label,
}: {
    nilai: boolean;
    onUbah: (v: boolean) => void;
    label: string;
}) {
    return (
        <TableCell className="text-center">
            <div className="flex justify-center">
                <Switch checked={nilai} onCheckedChange={onUbah} aria-label={label} />
            </div>
        </TableCell>
    );
}

/**
 * Galat satu sel.
 *
 * `FieldError` dipakai berdiri sendiri — tanpa `Field` induk — karena label
 * kolomnya sudah ada di kepala tabel dan tiap kendali membawa `aria-label`
 * sendiri. Yang dibutuhkan di sini cuma pengumumnya (`role="alert"`) dengan
 * warna & ukuran dari kit, bukan seluruh kerangka §3.9.
 */
function Galat({ pesan }: { pesan?: string }) {
    return pesan ? (
        <FieldError className="mt-1" errors={[{ message: pesan }]} />
    ) : null;
}

function Kosong() {
    return <span className="text-muted-foreground">—</span>;
}
