import { ArrowLeft01Icon, FloppyDiskIcon, ListViewIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui-maia/card';
import { Checkbox } from '@/components/ui-maia/checkbox';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import { Separator } from '@/components/ui-maia/separator';
import { Spinner } from '@/components/ui-maia/spinner';
import { Avatar } from '@/components/v2/Avatar';
import { LencanaStatusAkun } from '@/components/v2/LencanaStatusAkun';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { AkunProps } from '@/types/pengguna';

/**
 * "Informasi Akun" — kembaran V2 `pages/Account/Info.tsx`.
 *
 * Yang bisa disunting pemiliknya sendiri hanya TIGA: foto, nomor HP, email.
 * Identitas (nama, NRP, jabatan, departemen, peran) tetap dikelola Admin — dan
 * itu bukan sekadar disembunyikan di layar: `PageController::updateAccount()`
 * memvalidasi persis ketiga field itu saja.
 *
 * Pratinjau foto memakai `useState` + `URL.createObjectURL`. Penyimpanannya
 * sendiri dipakai bersama kanal mobile lewat `ProfilPengguna::perbarui()` —
 * jangan menduplikasinya di sini.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.2 — `rounded-full` dilepas dari `<Avatar>`: `v2/Avatar` sudah
 *    membawanya, jadi yang tersisa cuma ukurannya.
 *  · §3.9 — kedua isian dibungkus `FieldGroup`; kotak centang "Hapus foto
 *    profil" jadi `Field orientation="horizontal"` alih-alih `div` + `flex`.
 *  · Tombol Simpan memakai `Spinner` alih-alih menukar teksnya (preseden
 *    `V2/Auth/Login`).
 */
export default function AccountInfo({ user }: AkunProps) {
    const { data, setData, put, processing, errors } = useForm<{
        nomor_hp: string;
        email: string;
        photo: File | null;
        remove_photo: boolean;
    }>({
        nomor_hp: user.nomor_hp ?? '',
        email: user.email ?? '',
        photo: null,
        remove_photo: false,
    });

    const [pratinjau, setPratinjau] = useState<string | null>(user.photo_url);

    function pilihFoto(berkas: File | null) {
        setData((sebelum) => ({ ...sebelum, photo: berkas, remove_photo: false }));
        setPratinjau(berkas ? URL.createObjectURL(berkas) : user.photo_url);
    }

    function kirim(e: FormEvent) {
        e.preventDefault();
        // Inertia beralih sendiri ke FormData + spoof `_method` begitu ada File
        // di dalam data — jadi PUT dengan unggahan tetap ditulis sebagai `put()`.
        put(route('account.update'), { forceFormData: true });
    }

    return (
        <AppLayout judul="Informasi Akun">
            <form onSubmit={kirim} className="grid gap-4 md:gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardContent className="flex flex-col items-center gap-3 text-center">
                        <Avatar
                            nama={user.name}
                            foto={data.remove_photo ? null : pratinjau}
                            className="size-28"
                        />
                        <div>
                            <h2 className="font-semibold">{user.name}</h2>
                            <p className="text-muted-foreground text-sm">{user.peran}</p>
                        </div>
                        <LencanaStatusAkun status={user.status} />

                        <Field className="text-left" data-invalid={!!errors.photo || undefined}>
                            <FieldLabel htmlFor="photo">Ganti Foto Profil</FieldLabel>
                            <Input
                                id="photo"
                                type="file"
                                accept="image/jpeg,image/png"
                                aria-invalid={!!errors.photo}
                                onChange={(e) => pilihFoto(e.target.files?.[0] ?? null)}
                            />
                            <FieldDescription>JPG/PNG, maks 2MB.</FieldDescription>
                            <FieldError
                                errors={errors.photo ? [{ message: errors.photo }] : undefined}
                            />

                            {user.punya_foto ? (
                                <Field orientation="horizontal" className="gap-2">
                                    <Checkbox
                                        id="remove_photo"
                                        checked={data.remove_photo}
                                        onCheckedChange={(v) => setData('remove_photo', v === true)}
                                    />
                                    <FieldLabel
                                        htmlFor="remove_photo"
                                        className="text-destructive font-normal"
                                    >
                                        Hapus foto profil
                                    </FieldLabel>
                                </Field>
                            ) : null}
                        </Field>
                    </CardContent>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <HugeiconsIcon
                                icon={ListViewIcon}
                                strokeWidth={1.5}
                                className="size-4"
                                aria-hidden="true"
                            />
                            Detail Akun
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-2 text-sm sm:grid-cols-[10rem_1fr]">
                            <Baris
                                label="NRP"
                                nilai={<span className="font-mono">{user.nrp ?? '—'}</span>}
                            />
                            <Baris label="Nama Lengkap" nilai={user.name} />
                            <Baris label="Jabatan" nilai={user.jabatan_label ?? '—'} />
                            <Baris
                                label="Departemen"
                                nilai={user.departemen ?? '— (lintas departemen)'}
                            />
                            <Baris
                                label="Peran Sistem"
                                nilai={<span className="capitalize">{user.peran}</span>}
                            />
                        </dl>

                        <Separator className="my-6" />

                        <FieldGroup>
                            <Field data-invalid={!!errors.nomor_hp || undefined}>
                                <FieldLabel htmlFor="nomor_hp">Nomor HP</FieldLabel>
                                <Input
                                    id="nomor_hp"
                                    placeholder="mis. 0812xxxxxxx"
                                    value={data.nomor_hp}
                                    aria-invalid={!!errors.nomor_hp}
                                    onChange={(e) => setData('nomor_hp', e.target.value)}
                                />
                                <FieldError
                                    errors={
                                        errors.nomor_hp ? [{ message: errors.nomor_hp }] : undefined
                                    }
                                />
                            </Field>

                            <Field data-invalid={!!errors.email || undefined}>
                                <FieldLabel htmlFor="email">Email</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="nama@perusahaan.com"
                                    value={data.email}
                                    aria-invalid={!!errors.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                <FieldError
                                    errors={errors.email ? [{ message: errors.email }] : undefined}
                                />
                            </Field>
                        </FieldGroup>

                        <div className="mt-6 flex gap-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <HugeiconsIcon
                                        icon={FloppyDiskIcon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                )}
                                Simpan Perubahan
                            </Button>
                            <Button asChild variant="ghost">
                                <Link href={route('dashboard')}>
                                    <HugeiconsIcon
                                        icon={ArrowLeft01Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Batal
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}

/** Satu pasang istilah–nilai pada daftar read-only. */
function Baris({ label, nilai }: { label: string; nilai: ReactNode }) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd>{nilai}</dd>
        </>
    );
}
