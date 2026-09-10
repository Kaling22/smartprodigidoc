import {
    AiBrain01Icon, Alert02Icon, CheckmarkCircle01Icon, CircleSlashIcon, Delete02Icon,
    IdentityCardIcon, Settings01Icon, ShieldKeyIcon, SpellCheckIcon, ToggleOnIcon,
    UserAdd01Icon, UserSettings01Icon,
} from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Badge } from '@/components/ui-maia/badge';
import { Button } from '@/components/ui-maia/button';
import {
    Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import {
    Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui-maia/dialog';
import { DropdownMenuItem } from '@/components/ui-maia/dropdown-menu';
import {
    Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle,
} from '@/components/ui-maia/empty';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui-maia/field';
import {
    InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput,
} from '@/components/ui-maia/input-group';
import { Separator } from '@/components/ui-maia/separator';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { DataTable, Paginasi, type Kolom } from '@/components/v2/DataTable';
import { LencanaStatusAkun } from '@/components/v2/LencanaStatusAkun';
import { PenyaringDokumen, saringDepartemen } from '@/components/v2/PenyaringDokumen';
import { StripAksi } from '@/components/v2/StripAksi';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { PageProps } from '@/types';
import type { BarisAdmin, UsersIndexProps } from '@/types/pengguna';

/**
 * "Manajemen User" — kembaran V2 `pages/Users/Index.tsx`.
 *
 * Urutannya tetap disengaja: akun Management Development DI ATAS dan terpisah,
 * baru daftar user. Alasannya tertulis di controller — akun MD bersifat
 * BERSAMA, tak pernah muncul di dropdown mana pun, dan memegang satu tahap
 * WAJIB pada alur SOP; menyelipkannya ke tabel biasa menyembunyikan justru hal
 * yang perlu diketahui pengelolanya.
 *
 * Label peran datang dari props `roleLabels` (`User::ROLE_LABELS`), tidak
 * diketik di sini — CLAUDE.md §4.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.7 — penyaring yang di V1 berdiri sebagai KARTU SENDIRI di atas kartu
 *    tabel kini masuk ke `CardHeader` kartu itu: SATU kartu, bukan dua kotak
 *    bertumpuk untuk satu daftar (arketipe `V2/Documents/Index.tsx`). Ia juga
 *    kehilangan tombol "Filter" — `PenyaringDokumen` menyaring hidup dengan
 *    debounce 300 ms + `preserveState`/`preserveScroll`/`replace`, dan
 *    penyaringannya sendiri TETAP di server lewat query string.
 *  · §3.7 — sel aksi kehilangan `flex-wrap`; ketiga tombolnya masuk `StripAksi`
 *    (pola `V2/Documents/Published.tsx`), dan kedua jendelanya dirender DI LUAR
 *    menu karena Radix melepas isi menu begitu item dipilih.
 *  · §3.9 — kotak sandi MD + tombol Simpan jadi `InputGroup`, bukan tombol yang
 *    disandingkan sendiri di samping `Input`. Keterangan & galatnya jadi
 *    `FieldDescription` + `FieldError`.
 *  · §3.4 — lencana Bantuan AI memakai `size-3` + `strokeWidth={2}`: garis
 *    1,5px pada glif 12px praktis hilang.
 *  · Keadaan kosong kedua tabel memakai komponen `empty` resmi, bukan rakitan
 *    `div` + ikon.
 */
export default function UsersIndex() {
    const { users, departments, filters, akunMd, roleLabels, auth } =
        usePage<PageProps & UsersIndexProps>().props;

    const kolom: Kolom<BarisAdmin>[] = [
        {
            judul: 'Nama',
            render: (u) => (
                <div>
                    <div className="font-medium">{u.name}</div>
                    <div className="text-muted-foreground flex items-center gap-1 text-xs">
                        <HugeiconsIcon
                            icon={IdentityCardIcon}
                            strokeWidth={1.5}
                            className="size-3.5"
                            aria-hidden="true"
                        />
                        {u.nrp ?? '—'}
                    </div>
                </div>
            ),
        },
        { judul: 'NRP', render: (u) => <span className="font-mono text-sm">{u.nrp ?? '—'}</span> },
        { judul: 'No. HP', render: (u) => <span className="text-sm">{u.nomor_hp ?? '—'}</span> },
        { judul: 'Departemen', render: (u) => <Badge variant="outline">{u.dept ?? '—'}</Badge> },
        {
            judul: 'Peran',
            render: (u) => (
                <Badge variant="secondary">{(u.role && roleLabels[u.role]) ?? u.role ?? '—'}</Badge>
            ),
        },
        { judul: 'Status', render: (u) => <LencanaStatusAkun status={u.status} /> },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (u) => <AksiBaris user={u} milikSendiri={u.id === auth.user?.id} />,
        },
    ];

    return (
        <AppLayout
            judul="Manajemen User"
            sub={`${users.total} akun terdaftar.`}
            aksi={
                <Button asChild>
                    <Link href={route('users.create')}>
                        <HugeiconsIcon icon={UserAdd01Icon} strokeWidth={1.5} className="size-4" />
                        Buat Akun
                    </Link>
                </Button>
            }
        >
            <KartuAkunMd akunMd={akunMd} />

            <Card>
                <CardHeader className="border-b">
                    <CardTitle>Daftar Akun</CardTitle>
                    <CardDescription>Kelola semua akun dan buat akun staf.</CardDescription>
                    <PenyaringDokumen
                        url={route('users.index')}
                        filters={filters}
                        pilihan={[saringDepartemen(departments)]}
                        labelCari="Cari (nama / NRP / email)"
                        placeholderCari="mis. nama, NRP, atau email…"
                    />
                </CardHeader>
                <CardContent className="px-0">
                    <DataTable
                        kolom={kolom}
                        baris={users.data}
                        kunci={(u) => u.id}
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
                                    <EmptyTitle>Tidak ada user ditemukan.</EmptyTitle>
                                    <EmptyDescription>
                                        Coba longgarkan penyaring di atas.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        }
                    />
                </CardContent>
                <CardFooter className="border-t pt-6">
                    <Paginasi paginator={users} />
                </CardFooter>
            </Card>
        </AppLayout>
    );
}

/**
 * Tombol per baris.
 *
 * Akun sendiri tak dapat tombol apa pun — bukan sekadar disembunyikan di layar:
 * `destroy()` dan `toggleStatus()` menolaknya dengan 403 (P4, menyembunyikan
 * tombol bukan otorisasi).
 *
 * Kedua jendela konfirmasi dirender DI LUAR `StripAksi`: Radix melepas isi
 * menunya begitu item dipilih, jadi jendela yang lahir di dalamnya lenyap
 * sebelum sempat terlihat. Itemnya cuma memilih jendela mana yang menyala.
 */
type Jendela = 'status' | 'hapus' | null;

function AksiBaris({ user, milikSendiri }: { user: BarisAdmin; milikSendiri: boolean }) {
    const [jendela, setJendela] = useState<Jendela>(null);

    if (milikSendiri) {
        return <Badge variant="outline">Akun Anda</Badge>;
    }

    return (
        <div className="flex items-center justify-end gap-2">
            <StripAksi>
                <DropdownMenuItem asChild>
                    <Link href={route('users.edit', user.id)}>
                        <HugeiconsIcon
                            icon={UserSettings01Icon}
                            strokeWidth={1.5}
                            className="size-4"
                        />
                        Ubah Peran
                    </Link>
                </DropdownMenuItem>

                <DropdownMenuItem onSelect={() => setJendela('status')}>
                    <HugeiconsIcon
                        icon={user.aktif ? CircleSlashIcon : CheckmarkCircle01Icon}
                        strokeWidth={1.5}
                        className="size-4"
                    />
                    {user.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                </DropdownMenuItem>

                {/* Hapus DITOLAK bila user masih tertaut dokumen (lihat
                    UserManagementController::jejakDokumen) — pesannya
                    mengarahkan ke Nonaktifkan, bukan sekadar gagal. */}
                <DropdownMenuItem variant="destructive" onSelect={() => setJendela('hapus')}>
                    <HugeiconsIcon icon={Delete02Icon} strokeWidth={1.5} className="size-4" />
                    Hapus
                </DropdownMenuItem>
            </StripAksi>

            <ConfirmDialog
                judul="Ubah Status Akun?"
                pesan={`Ubah status akun ${user.name}?`}
                tombolYa="Ya, ubah"
                destruktif={user.aktif}
                buka={jendela === 'status'}
                onUbahBuka={(b) => setJendela(b ? 'status' : null)}
                onKonfirmasi={() =>
                    router.post(route('users.toggleStatus', user.id), {}, { preserveScroll: true })
                }
            />

            <ConfirmDialog
                judul="Hapus Akun?"
                pesan={`Hapus akun ${user.name} (${user.nrp ?? '—'})? Akun yang masih tertaut dokumen akan ditolak — pakai Nonaktifkan untuk itu.`}
                tombolYa="Ya, hapus"
                destruktif
                buka={jendela === 'hapus'}
                onUbahBuka={(b) => setJendela(b ? 'hapus' : null)}
                onKonfirmasi={() =>
                    router.delete(route('users.destroy', user.id), { preserveScroll: true })
                }
            />
        </div>
    );
}

/** Kartu akun Management Development — daftar + pintu konfigurasinya. */
function KartuAkunMd({ akunMd }: { akunMd: BarisAdmin[] }) {
    const kolom: Kolom<BarisAdmin>[] = [
        { judul: 'NRP', render: (m) => <span className="font-mono text-sm">{m.nrp ?? '—'}</span> },
        { judul: 'Nama', render: (m) => <span className="font-medium">{m.name}</span> },
        { judul: 'Dept', render: (m) => <Badge variant="outline">{m.dept ?? '—'}</Badge> },
        { judul: 'Status', render: (m) => <LencanaStatusAkun status={m.status} /> },
        {
            judul: 'Bantuan AI',
            render: (m) => (
                <Badge variant={m.ai ? 'secondary' : 'outline'}>
                    <HugeiconsIcon icon={AiBrain01Icon} strokeWidth={2} className="size-3" />
                    {m.ai ? 'Aktif' : 'Mati'}
                </Badge>
            ),
        },
        {
            judul: 'Aksi',
            kelas: 'w-px text-right whitespace-nowrap',
            render: (m) => <DialogKonfigurasiMd md={m} />,
        },
    ];

    return (
        <Card>
            <CardHeader className="border-b">
                <CardTitle className="flex items-center gap-2">
                    <HugeiconsIcon
                        icon={SpellCheckIcon}
                        strokeWidth={1.5}
                        className="text-primary size-4"
                        aria-hidden="true"
                    />
                    Akun Management Development
                </CardTitle>
                <CardDescription>
                    Peninjau kedua — memeriksa sistematika penulisan sesudah SH/DH dan sebelum PJO.
                    Akun bersama; hanya Admin yang boleh membuat dan mengelolanya.
                </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
                <DataTable
                    kolom={kolom}
                    baris={akunMd}
                    kunci={(m) => m.id}
                    kosong={
                        <Empty className="border-0">
                            <EmptyHeader>
                                <EmptyMedia variant="icon">
                                    <HugeiconsIcon
                                        icon={Alert02Icon}
                                        strokeWidth={1.5}
                                        className="size-6"
                                    />
                                </EmptyMedia>
                                <EmptyTitle>Belum ada akun Management Development.</EmptyTitle>
                                <EmptyDescription>
                                    Tanpa akun ini, dokumen SOP akan tertahan dan tak bisa sampai ke
                                    PJO.
                                </EmptyDescription>
                            </EmptyHeader>
                        </Empty>
                    }
                />
            </CardContent>
        </Card>
    );
}

/**
 * Tiga saklar akun MD dalam satu jendela.
 *
 * Ketiganya menembak rute yang SAMA (`users.mdConfig`) dengan `aksi` berbeda;
 * itu bentuk yang dipilih controllernya supaya semua tercatat audit dengan pola
 * yang sama. Jendelanya ditutup sendiri sesudah kiriman berhasil — halaman
 * dimuat ulang oleh Inertia, dan jendela yang tertinggal terbuka menampilkan
 * keadaan yang sudah basi.
 *
 * Jendela BUKAN-konfirmasi, jadi `ui-maia/dialog` — bukan `ConfirmDialog`
 * (PATOKAN §3.8). Konfirmasi yang memang perlu cuma satu, di saklar status.
 */
function DialogKonfigurasiMd({ md }: { md: BarisAdmin }) {
    const [buka, setBuka] = useState(false);
    const sandi = useForm({ aksi: 'reset_sandi', sandi_baru: '' });

    function kirimAksi(aksi: 'ai' | 'status') {
        router.post(
            route('users.mdConfig', md.id),
            { aksi },
            { preserveScroll: true, onSuccess: () => setBuka(false) },
        );
    }

    function gantiSandi(e: FormEvent) {
        e.preventDefault();
        sandi.post(route('users.mdConfig', md.id), {
            preserveScroll: true,
            onSuccess: () => {
                sandi.reset('sandi_baru');
                setBuka(false);
            },
        });
    }

    return (
        <Dialog open={buka} onOpenChange={setBuka}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    <HugeiconsIcon icon={Settings01Icon} strokeWidth={1.5} className="size-4" />
                    Konfigurasi
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfigurasi — {md.nrp}</DialogTitle>
                    <DialogDescription>{md.name}</DialogDescription>
                </DialogHeader>

                <div className="grid gap-4">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-1.5 text-sm font-medium">
                                <HugeiconsIcon
                                    icon={AiBrain01Icon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Bantuan AI
                            </div>
                            <p className="text-muted-foreground text-sm">
                                Membantu menemukan typo &amp; salah tulis. Keputusan tetap di tangan
                                peninjau.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" onClick={() => kirimAksi('ai')}>
                            {md.ai ? 'Matikan' : 'Aktifkan'}
                        </Button>
                    </div>

                    <Separator />

                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-1.5 text-sm font-medium">
                                <HugeiconsIcon
                                    icon={ToggleOnIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Status akun
                            </div>
                            <p className="text-muted-foreground text-sm">
                                Menonaktifkan akun menghentikan seluruh SOP di tahap ini.
                            </p>
                        </div>
                        <ConfirmDialog
                            judul={md.aktif ? 'Nonaktifkan akun MD?' : 'Aktifkan akun MD?'}
                            pesan={
                                md.aktif
                                    ? 'Dokumen SOP akan TERTAHAN di tahap MD sampai diaktifkan lagi.'
                                    : 'Akun MD kembali menerima dokumen untuk ditinjau.'
                            }
                            tombolYa={md.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan'}
                            destruktif={md.aktif}
                            onKonfirmasi={() => kirimAksi('status')}
                            pemicu={
                                <Button variant="outline" size="sm">
                                    {md.aktif ? 'Nonaktifkan' : 'Aktifkan'}
                                </Button>
                            }
                        />
                    </div>

                    <Separator />

                    {/* Reset sandi — penting justru karena akunnya BERSAMA:
                        begitu ada orang pindah/keluar, sandinya harus cepat
                        diganti. */}
                    <form onSubmit={gantiSandi}>
                        <Field data-invalid={!!sandi.errors.sandi_baru || undefined}>
                            <FieldLabel
                                htmlFor={`sandi-${md.id}`}
                                className="flex items-center gap-1.5"
                            >
                                <HugeiconsIcon
                                    icon={ShieldKeyIcon}
                                    strokeWidth={1.5}
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Ganti kata sandi
                            </FieldLabel>
                            {/* Tombolnya menempel di kotaknya lewat `InputGroup`
                                (PATOKAN §3.9), jadi tingginya pindah ke
                                pembungkus dan tak ada satu pun angka diketik. */}
                            <InputGroup>
                                <InputGroupInput
                                    id={`sandi-${md.id}`}
                                    type="password"
                                    required
                                    minLength={8}
                                    placeholder="Kata sandi baru (min. 8 karakter)"
                                    value={sandi.data.sandi_baru}
                                    aria-invalid={!!sandi.errors.sandi_baru}
                                    onChange={(e) => sandi.setData('sandi_baru', e.target.value)}
                                />
                                <InputGroupAddon align="inline-end">
                                    <InputGroupButton type="submit" disabled={sandi.processing}>
                                        Simpan
                                    </InputGroupButton>
                                </InputGroupAddon>
                            </InputGroup>
                            <FieldDescription>
                                Akun ini dipakai bersama — ganti sandinya setiap ada orang keluar
                                atau pindah tugas.
                            </FieldDescription>
                            <FieldError
                                errors={
                                    sandi.errors.sandi_baru
                                        ? [{ message: sandi.errors.sandi_baru }]
                                        : undefined
                                }
                            />
                        </Field>
                    </form>
                </div>
            </DialogContent>
        </Dialog>
    );
}
