import { ArrowLeft01Icon, Tick02Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { Spinner } from '@/components/ui-maia/spinner';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { UsersFormProps } from '@/types/pengguna';

/**
 * "Buat Akun" — kembaran V2 `pages/Users/Create.tsx`.
 *
 * Akun yang lahir dari sini LANGSUNG aktif (lihat `UserManagementController::
 * store`), berbeda dari pendaftaran mandiri yang menunggu persetujuan. Jabatan
 * dan departemen turunan perannya ditentukan server (`turunanPeran()`) — form
 * ini hanya mengirim `role` + `department_id` apa adanya.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — seluruh isian dibungkus `FieldGroup`; gridnya duduk DI DALAM
 *    pembungkus itu (arketipe `V2/Documents/Arsip/Edit.tsx`), jadi jarak
 *    antar-kelompok datang dari kit alih-alih dari `gap-5` yang diketik.
 *  · §3.6 — keterangan "Akun yang dibuat admin langsung berstatus aktif."
 *    tetap di kepala kartu, bukan naik ke prop `sub`: ia menerangkan FORMULIR
 *    ini, bukan halamannya, dan remah di topbar sudah menamai halamannya.
 *  · Tombol Simpan memakai `Spinner` alih-alih menukar teksnya jadi
 *    "Menyimpan…" — tombol yang berubah lebar saat ditekan menggeser tata
 *    letak tepat pada saat orang sedang menunggu (preseden `V2/Auth/Login`).
 */
export default function UsersCreate({ departments, roles, roleLabels }: UsersFormProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        nrp: '',
        jabatan_diajukan: '',
        nomor_hp: '',
        department_id: '',
        role: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function kirim(e: FormEvent) {
        e.preventDefault();
        post(route('users.store'));
    }

    return (
        <AppLayout
            judul="Buat Akun"
            remah={[{ label: 'Manajemen User', href: route('users.index') }, { label: 'Buat Akun' }]}
        >
            <Card>
                <CardHeader>
                    <CardTitle>Buat Akun</CardTitle>
                    <CardDescription>
                        Akun yang dibuat admin langsung berstatus aktif.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={kirim}>
                        <FieldGroup>
                            <div className="grid gap-4 md:grid-cols-2">
                                <Isian
                                    id="name"
                                    label="Nama Lengkap"
                                    nilai={data.name}
                                    galat={errors.name}
                                    onUbah={(v) => setData('name', v)}
                                    wajib
                                />
                                <Isian
                                    id="nrp"
                                    label="NRP"
                                    petunjuk="Dipakai untuk login."
                                    nilai={data.nrp}
                                    galat={errors.nrp}
                                    onUbah={(v) => setData('nrp', v)}
                                    wajib
                                />
                                <Isian
                                    id="jabatan_diajukan"
                                    label="Jabatan untuk Pengesahan"
                                    petunjuk="Ditampilkan pada lembar pengesahan, mis. Group Leader ICT."
                                    nilai={data.jabatan_diajukan}
                                    galat={errors.jabatan_diajukan}
                                    onUbah={(v) => setData('jabatan_diajukan', v)}
                                />
                                <Isian
                                    id="nomor_hp"
                                    label="Nomor HP"
                                    petunjuk="mis. 0812xxxxxxx"
                                    nilai={data.nomor_hp}
                                    galat={errors.nomor_hp}
                                    onUbah={(v) => setData('nomor_hp', v)}
                                />

                                <Field data-invalid={!!errors.department_id || undefined}>
                                    <FieldLabel htmlFor="department_id">Departemen</FieldLabel>
                                    <Select
                                        value={data.department_id}
                                        onValueChange={(v) => setData('department_id', v)}
                                    >
                                        <SelectTrigger
                                            id="department_id"
                                            className="w-full"
                                            aria-invalid={!!errors.department_id}
                                        >
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
                                    <FieldError
                                        errors={
                                            errors.department_id
                                                ? [{ message: errors.department_id }]
                                                : undefined
                                        }
                                    />
                                </Field>
                            </div>

                            <Field data-invalid={!!errors.role || undefined}>
                                <FieldLabel htmlFor="role">Jabatan / Peran</FieldLabel>
                                <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                    <SelectTrigger
                                        id="role"
                                        className="w-full"
                                        aria-invalid={!!errors.role}
                                    >
                                        <SelectValue placeholder="— Pilih Jabatan —" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((r) => (
                                            <SelectItem key={r} value={r}>
                                                {roleLabels[r] ?? r}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <FieldError
                                    errors={errors.role ? [{ message: errors.role }] : undefined}
                                />
                            </Field>

                            <Isian
                                id="email"
                                label="Email"
                                type="email"
                                petunjuk="Opsional."
                                nilai={data.email}
                                galat={errors.email}
                                onUbah={(v) => setData('email', v)}
                            />

                            <div className="grid gap-4 md:grid-cols-2">
                                <Isian
                                    id="password"
                                    label="Kata Sandi"
                                    type="password"
                                    nilai={data.password}
                                    galat={errors.password}
                                    onUbah={(v) => setData('password', v)}
                                    wajib
                                />
                                <Isian
                                    id="password_confirmation"
                                    label="Ulangi Kata Sandi"
                                    type="password"
                                    nilai={data.password_confirmation}
                                    galat={errors.password_confirmation}
                                    onUbah={(v) => setData('password_confirmation', v)}
                                    wajib
                                />
                            </div>
                        </FieldGroup>

                        <div className="mt-6 flex gap-2">
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <HugeiconsIcon
                                        icon={Tick02Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                )}
                                Simpan Akun
                            </Button>
                            <Button asChild variant="ghost">
                                <Link href={route('users.index')}>
                                    <HugeiconsIcon
                                        icon={ArrowLeft01Icon}
                                        strokeWidth={1.5}
                                        className="size-4"
                                    />
                                    Batal
                                </Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}

/** Satu field teks — pola yang sama tujuh kali, ditulis sekali. */
function Isian({
    id,
    label,
    nilai,
    galat,
    onUbah,
    type = 'text',
    petunjuk,
    wajib = false,
}: {
    id: string;
    label: string;
    nilai: string;
    galat?: string;
    onUbah: (v: string) => void;
    type?: string;
    petunjuk?: string;
    wajib?: boolean;
}) {
    return (
        <Field data-invalid={!!galat || undefined}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <Input
                id={id}
                name={id}
                type={type}
                required={wajib}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                aria-invalid={!!galat}
            />
            {petunjuk ? <FieldDescription>{petunjuk}</FieldDescription> : null}
            <FieldError errors={galat ? [{ message: galat }] : undefined} />
        </Field>
    );
}
