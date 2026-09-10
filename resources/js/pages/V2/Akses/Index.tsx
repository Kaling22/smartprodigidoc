import {
    Delete02Icon, HierarchyIcon, MinusSignIcon, PencilIcon, PlusSignIcon, Tick02Icon,
    UserSettings01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';

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
    Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,
} from '@/components/ui-maia/empty';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { Ikon } from '@/components/v2/Ikon';
import {
    PenyaringDokumen, saringDepartemen, type PilihanSaring,
} from '@/components/v2/PenyaringDokumen';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { AksesIndexProps, BarisGroupLeader, ProfilAkses } from '@/types/pengguna';

/** Penanda "tanpa nilai" untuk Radix Select, yang tak menerima string kosong. */
const KOSONG = '*';

/**
 * "Manajemen Akses" — kembaran V2 `pages/Akses/Index.tsx`.
 *
 * Polanya konfigurasi Mikrotik (lihat `AccessProfileController`): Admin membuat
 * PROFIL sekali, lalu MENETAPKAN profil itu ke orang. Wewenangnya sendiri
 * ditegakkan di `User::bolehBuatJenis()` / `canReviewJsa()` — halaman ini cuma
 * mengelola datanya, dan tak satu pun aturan wewenang boleh punya salinan di
 * sini.
 *
 * Kandidat penetapan HANYA Group Leader, dan itu disaring server; `tetapkan()`
 * menolak selain GL dengan 403 walau dropdown-nya dipalsukan (P4).
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring kartu Penetapan pindah ke `CardHeader`-nya, tabel ke
 *    `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t
 *    pt-6"` (arketipe `V2/Documents/Index.tsx`). Tombol "Filter" & "Bersihkan"
 *    hilang: `PenyaringDokumen` menyaring hidup dengan debounce 300 ms dan
 *    memunculkan tombol reset sendiri begitu ada isian yang bisa direset —
 *    penyaringannya TETAP di server lewat query string.
 *  · §3.7 — kedua sel aksi kehilangan `flex-wrap`. Keduanya cuma dua tombol,
 *    jadi tetap berdiri di baris alih-alih masuk `StripAksi`.
 *  · §3.9 — isian jendela profil dibungkus `FieldGroup`; kotak centang jadi
 *    `Field orientation="horizontal"`, dan `htmlFor` ↔ `id` tetap berpasangan
 *    sehingga menekan labelnya tetap mencentang.
 *  · Keadaan kosong kedua tabel memakai komponen `empty` resmi.
 */
export default function AksesIndex({
    profiles,
    groupLeaders,
    departments,
    filters,
    jenis,
    rupa,
}: AksesIndexProps) {
    return (
        <AppLayout
            judul="Manajemen Akses"
            aksi={
                <DialogProfil
                    profil={null}
                    jenis={jenis}
                    rupa={rupa}
                    pemicu={
                        <Button>
                            <HugeiconsIcon
                                icon={PlusSignIcon}
                                strokeWidth={1.5}
                                className="size-4"
                            />
                            Profil Baru
                        </Button>
                    }
                />
            }
        >
            <KartuProfil profiles={profiles} jenis={jenis} rupa={rupa} />
            <KartuPenetapan
                profiles={profiles}
                groupLeaders={groupLeaders}
                departments={departments}
                filters={filters}
            />
        </AppLayout>
    );
}

/** Kartu 1 — daftar profil akses. */
function KartuProfil({
    profiles,
    jenis,
    rupa,
}: Pick<AksesIndexProps, 'profiles' | 'jenis' | 'rupa'>) {
    const kolom: Kolom<ProfilAkses>[] = [
        {
            judul: 'Nama Profil',
            render: (p) => (
                <div>
                    <div className="font-medium">{p.nama}</div>
                    {p.keterangan ? (
                        <div className="text-muted-foreground text-xs">{p.keterangan}</div>
                    ) : null}
                </div>
            ),
        },
        {
            judul: 'Jenis Dokumen',
            render: (p) =>
                p.jenis_dibolehkan.length === 0 ? (
                    <span className="text-muted-foreground text-sm">— tidak menyusun —</span>
                ) : (
                    <div className="flex flex-wrap gap-1">
                        {p.jenis_dibolehkan.map((kode) => (
                            <LencanaJenis key={kode} kode={kode} rupa={rupa} />
                        ))}
                    </div>
                ),
        },
        {
            judul: 'Tinjau JSA',
            render: (p) => (
                <Badge variant={p.boleh_review_jsa ? 'secondary' : 'outline'}>
                    <HugeiconsIcon
                        icon={p.boleh_review_jsa ? Tick02Icon : MinusSignIcon}
                        strokeWidth={2}
                        className="size-3"
                    />
                    {p.boleh_review_jsa ? 'Ya' : 'Tidak'}
                </Badge>
            ),
        },
        {
            judul: 'Pengguna',
            kelas: 'text-center',
            render: (p) => (
                <Badge variant={p.users_count > 0 ? 'secondary' : 'outline'}>{p.users_count}</Badge>
            ),
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (p) => (
                <div className="flex items-center justify-end gap-2">
                    <DialogProfil
                        profil={p}
                        jenis={jenis}
                        rupa={rupa}
                        pemicu={
                            <Button variant="outline" size="sm">
                                <HugeiconsIcon
                                    icon={PencilIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                                Ubah
                            </Button>
                        }
                    />
                    <ConfirmDialog
                        judul={`Hapus profil "${p.nama}"?`}
                        pesan={
                            p.users_count > 0
                                ? `${p.users_count} pengguna akan kehilangan aksesnya dan kembali ke Tanpa Akses.`
                                : 'Profil ini belum dipakai siapa pun.'
                        }
                        tombolYa="Ya, hapus"
                        destruktif
                        onKonfirmasi={() =>
                            router.delete(route('akses.destroy', p.id), { preserveScroll: true })
                        }
                        pemicu={
                            <Button
                                variant="outline"
                                size="icon"
                                aria-label={`Hapus profil ${p.nama}`}
                            >
                                <HugeiconsIcon
                                    icon={Delete02Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                />
                            </Button>
                        }
                    />
                </div>
            ),
        },
    ];

    return (
        <Card>
            <CardHeader className="border-b">
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={HierarchyIcon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Profil Akses
                </CardTitle>
                <CardDescription>
                    Buat profil sekali, tetapkan ke banyak orang. Mengubah profil langsung mengubah
                    wewenang semua penggunanya.
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={profiles}
                    kunci={(p) => p.id}
                    kosong={
                        <Empty className="border-0">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <HugeiconsIcon
                                        icon={HierarchyIcon}
                                        strokeWidth={1.5}
                                        className="size-6"
                                    />
                                </EmptyMedia>
                                <EmptyTitle>Belum ada profil akses.</EmptyTitle>
                                <EmptyDescription>
                                    Selama belum ada, tak seorang Group Leader pun bisa menyusun
                                    dokumen.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            </CardContent>
        </Card>
    );
}

/** Kartu 2 — penetapan profil ke Group Leader, beserta penyaringnya. */
function KartuPenetapan({
    profiles,
    groupLeaders,
    departments,
    filters,
}: Pick<AksesIndexProps, 'profiles' | 'groupLeaders' | 'departments' | 'filters'>) {
    const kolom: Kolom<BarisGroupLeader>[] = [
        { judul: 'Nama', render: (g) => <span className="font-medium">{g.name}</span> },
        { judul: 'NRP', render: (g) => <span className="font-mono text-sm">{g.nrp ?? '—'}</span> },
        { judul: 'Dept', render: (g) => <Badge variant="outline">{g.dept ?? '—'}</Badge> },
        {
            judul: 'Profil Akses',
            kelas: 'min-w-[280px]',
            render: (g) => <FormTetapkan gl={g} profiles={profiles} />,
        },
    ];

    /* "tanpa" BUKAN id profil melainkan pertanyaan "siapa yang belum
       ditetapkan" — justru daftar itu yang dicari Admin saat memeriksa
       (lihat controller). */
    const saringProfil: PilihanSaring = {
        nama: 'access_profile_id',
        label: 'Profil Akses',
        opsi: [
            ['', 'Semua'],
            ['tanpa', '— Tanpa Akses —'],
            ...profiles.map((p): [string, string] => [String(p.id), p.nama]),
        ],
    };

    return (
        <Card>
            <CardHeader className="border-b">
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={UserSettings01Icon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Penetapan Akses
                </CardTitle>
                <CardDescription>
                    Group Leader saja. Admin IT berwenang penuh tanpa profil; SH/DH/PJO/Non-Staff
                    tidak menyusun dokumen sehingga tidak ditetapkan.
                </CardDescription>
                <PenyaringDokumen
                    url={route('akses.index')}
                    filters={filters}
                    pilihan={[saringDepartemen(departments), saringProfil]}
                    labelCari="Cari (nama / NRP)"
                    placeholderCari="mis. nama atau NRP…"
                />
            </CardHeader>

            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={groupLeaders.data}
                    kunci={(g) => g.id}
                    kosong={
                        <Empty className="border-0">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <HugeiconsIcon
                                        icon={UserSettings01Icon}
                                        strokeWidth={1.5}
                                        className="size-6"
                                    />
                                </EmptyMedia>
                                <EmptyTitle>
                                    {filters.q || filters.department_id || filters.access_profile_id
                                        ? 'Tidak ada Group Leader yang cocok dengan filter.'
                                        : 'Belum ada akun Group Leader.'}
                                </EmptyTitle>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            </CardContent>
            <CardFooter className="border-t pt-6">
                <Paginasi paginator={groupLeaders} />
            </CardFooter>
        </Card>
    );
}

/**
 * Dropdown + Simpan pada satu baris GL.
 *
 * "— Tanpa Akses —" adalah pilihan yang SAH (mencabut), bukan kegagalan
 * validasi — lihat `AccessProfileController::tetapkan()`.
 */
function FormTetapkan({ gl, profiles }: { gl: BarisGroupLeader; profiles: ProfilAkses[] }) {
    const { data, setData, post, processing } = useForm({
        access_profile_id: gl.access_profile_id ? String(gl.access_profile_id) : '',
    });

    function kirim(e: FormEvent) {
        e.preventDefault();
        post(route('akses.tetapkan', gl.id), { preserveScroll: true });
    }

    return (
        <form onSubmit={kirim} className="flex gap-2">
            <Select
                value={data.access_profile_id || KOSONG}
                onValueChange={(v) => setData('access_profile_id', v === KOSONG ? '' : v)}
            >
                <SelectTrigger className="w-full" aria-label={`Profil akses ${gl.name}`}>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={KOSONG}>— Tanpa Akses —</SelectItem>
                    {profiles.map((p) => (
                        <SelectItem key={p.id} value={String(p.id)}>
                            {p.nama}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Button type="submit" variant="outline" size="sm" disabled={processing}>
                <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} className="size-4" />
                Simpan
            </Button>
        </form>
    );
}

/**
 * Jendela Profil Baru / Ubah Profil.
 *
 * SATU komponen untuk keduanya: aturannya memang sama persis
 * (`StoreAccessProfileRequest` melayani keduanya), dan menuliskannya dua kali
 * hanya menciptakan dua daftar kotak centang yang suatu hari menyimpang.
 *
 * Jendela BUKAN-konfirmasi, jadi `ui-maia/dialog` (PATOKAN §3.8).
 */
function DialogProfil({
    profil,
    jenis,
    rupa,
    pemicu,
}: {
    profil: ProfilAkses | null;
    jenis: string[];
    rupa: AksesIndexProps['rupa'];
    pemicu: ReactNode;
}) {
    const [buka, setBuka] = useState(false);
    const form = useForm({
        nama: profil?.nama ?? '',
        keterangan: profil?.keterangan ?? '',
        jenis_dibolehkan: profil?.jenis_dibolehkan ?? [],
        boleh_review_jsa: profil?.boleh_review_jsa ?? false,
    });

    function pilihJenis(kode: string, aktif: boolean) {
        form.setData(
            'jenis_dibolehkan',
            aktif
                ? [...form.data.jenis_dibolehkan, kode]
                : form.data.jenis_dibolehkan.filter((k) => k !== kode),
        );
    }

    function kirim(e: FormEvent) {
        e.preventDefault();
        const opsi = { preserveScroll: true, onSuccess: () => setBuka(false) };

        if (profil) {
            form.put(route('akses.update', profil.id), opsi);
        } else {
            form.post(route('akses.store'), opsi);
        }
    }

    const kunci = profil?.id ?? 'baru';

    return (
        <Dialog open={buka} onOpenChange={setBuka}>
            <DialogTrigger asChild>{pemicu}</DialogTrigger>
            <DialogContent>
                <form onSubmit={kirim} className="grid gap-6">
                    <DialogHeader>
                        <DialogTitle>{profil ? 'Ubah Profil' : 'Profil Baru'}</DialogTitle>
                        <DialogDescription>
                            {profil && profil.users_count > 0
                                ? `Perubahan ini langsung berlaku bagi ${profil.users_count} pengguna.`
                                : 'Tentukan jenis dokumen yang boleh disusun pemegang profil ini.'}
                        </DialogDescription>
                    </DialogHeader>

                    <FieldGroup>
                        <Field data-invalid={!!form.errors.nama || undefined}>
                            <FieldLabel htmlFor={`nama-${kunci}`}>Nama Profil</FieldLabel>
                            <Input
                                id={`nama-${kunci}`}
                                required
                                maxLength={255}
                                placeholder="Penyusun SOP & IK"
                                value={form.data.nama}
                                aria-invalid={!!form.errors.nama}
                                onChange={(e) => form.setData('nama', e.target.value)}
                            />
                            <FieldError
                                errors={form.errors.nama ? [{ message: form.errors.nama }] : undefined}
                            />
                        </Field>

                        <Field>
                            <FieldLabel htmlFor={`ket-${kunci}`}>Keterangan (opsional)</FieldLabel>
                            <Input
                                id={`ket-${kunci}`}
                                maxLength={255}
                                placeholder="Untuk GL yang hanya menyusun prosedur"
                                value={form.data.keterangan ?? ''}
                                onChange={(e) => form.setData('keterangan', e.target.value)}
                            />
                        </Field>

                        <Field>
                            <FieldLabel>Jenis dokumen yang boleh disusun</FieldLabel>
                            <div className="flex flex-wrap gap-4">
                                {jenis.map((kode) => {
                                    const id = `jn-${kunci}-${kode}`;

                                    return (
                                        <Field
                                            key={kode}
                                            orientation="horizontal"
                                            className="w-auto gap-2"
                                        >
                                            <Checkbox
                                                id={id}
                                                checked={form.data.jenis_dibolehkan.includes(kode)}
                                                onCheckedChange={(v) => pilihJenis(kode, v === true)}
                                            />
                                            <FieldLabel htmlFor={id} className="font-normal">
                                                <LencanaJenis kode={kode} rupa={rupa} />
                                            </FieldLabel>
                                        </Field>
                                    );
                                })}
                            </div>
                            <FieldDescription>
                                Tak satu pun dicentang = profil ini tidak memberi wewenang menyusun.
                            </FieldDescription>
                        </Field>

                        <Field>
                            <Field orientation="horizontal" className="gap-2">
                                <Checkbox
                                    id={`jsa-${kunci}`}
                                    checked={form.data.boleh_review_jsa}
                                    onCheckedChange={(v) =>
                                        form.setData('boleh_review_jsa', v === true)
                                    }
                                />
                                <FieldLabel htmlFor={`jsa-${kunci}`} className="font-normal">
                                    Boleh <strong>meninjau</strong> dokumen JSA
                                </FieldLabel>
                            </Field>
                            <FieldDescription>
                                GL departemen SHE sudah berwenang otomatis — centang ini untuk GL
                                departemen lain.
                            </FieldDescription>
                        </Field>
                    </FieldGroup>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="ghost">
                                Batal
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            <HugeiconsIcon icon={Tick02Icon} strokeWidth={1.5} className="size-4" />
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/** Kode jenis + rupanya (`DocumentType::RUPA`, datang dari props). */
function LencanaJenis({ kode, rupa }: { kode: string; rupa: AksesIndexProps['rupa'] }) {
    const [ikon, warna] = rupa[kode] ?? [];

    return (
        <Badge variant="outline">
            <Ikon nama={ikon} className="size-3.5" />
            <span style={warna ? { color: warna } : undefined}>{kode}</span>
        </Badge>
    );
}
