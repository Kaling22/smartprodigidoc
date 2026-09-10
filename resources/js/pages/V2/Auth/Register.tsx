import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { Spinner } from '@/components/ui-maia/spinner';
import { AuthLayout } from '@/layouts/V2/AuthLayout';

interface Departemen {
    id: number;
    code: string;
    name: string;
}

/**
 * "Daftar" — kembaran V2 `pages/Auth/Register.tsx`.
 *
 * Pendaftaran akun Non-Staff. Daftar departemen datang JADI dari
 * `RegisterController@create` (hanya yang aktif). Tidak ada penyaringan di
 * sini: departemen nonaktif ditolak server lewat `RegisterRequest`, dan
 * dropdown memang bukan penjaga.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.6 — judul & keterangan RATA KIRI, bukan `items-center text-center`:
 *    `layouts/V2/AuthLayout` sejak REVISI-UI-V3 R12 menurunkan logo tepat di
 *    atas blok ini dan merapatkannya ke kiri, jadi judul yang dipusatkan
 *    tidak lagi segaris dengan mereknya sendiri (pola `V2/Auth/Login`).
 *  · Tombol Daftar memakai `Spinner` alih-alih menukar teksnya jadi
 *    "Memproses…" — tombol yang berubah lebar saat ditekan menggeser tata
 *    letak tepat pada saat orang sedang menunggu.
 *  · `SelectTrigger` membawa `aria-invalid` sendiri; sebelumnya cuma `Field`
 *    yang menandainya (§3.9).
 *
 * **Yang sengaja TIDAK diubah:** kedua kotak sandi TIDAK diberi tombol mata,
 * meski `V2/Auth/Login` punya. Tombol itu lahir sebagai permintaan R12 untuk
 * layar masuk; memasangnya di sini berarti menambah kendali yang tak ada di
 * V1 — dan fase ini mengembarkan, bukan menambah.
 */
export default function Register({ departments }: { departments: Departemen[] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        nrp: '',
        nomor_hp: '',
        jabatan: '',
        department_id: '',
        password: '',
        password_confirmation: '',
    });

    function kirim(e: FormEvent) {
        e.preventDefault();
        post(route('register.store'));
    }

    return (
        <AuthLayout judul="Daftar" lebar="max-w-md">
            <form onSubmit={kirim} className="flex flex-col gap-6">
                <FieldGroup>
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold">Pendaftaran Akun Non-Staff</h1>
                        <p className="text-muted-foreground text-sm text-balance">
                            Akun aktif setelah disetujui oleh Group Leader, Pimpinan, atau Admin IT.
                        </p>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
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
                            nilai={data.nrp}
                            galat={errors.nrp}
                            onUbah={(v) => setData('nrp', v)}
                            petunjuk="Nomor Registrasi Pegawai"
                            autoComplete="username"
                            wajib
                        />
                        <Isian
                            id="nomor_hp"
                            label="Nomor HP"
                            nilai={data.nomor_hp}
                            galat={errors.nomor_hp}
                            onUbah={(v) => setData('nomor_hp', v)}
                            petunjuk="mis. 0812xxxxxxx"
                        />
                        {/* Halaman ini HANYA melahirkan akun Non-Staff, dan
                            Non-Staff di PPA berarti teknisi/magang/helper —
                            bukan "Staff". Contoh "Staff ICTMD" menyuruh
                            pendaftar menuliskan jabatan yang justru tak bisa ia
                            peroleh dari sini. */}
                        <Isian
                            id="jabatan"
                            label="Jabatan"
                            nilai={data.jabatan}
                            galat={errors.jabatan}
                            onUbah={(v) => setData('jabatan', v)}
                            petunjuk="mis. Teknisi ICTMD, Magang, Helper"
                        />

                        <Field
                            className="sm:col-span-2"
                            data-invalid={!!errors.department_id || undefined}
                        >
                            <FieldLabel htmlFor="department_id">Departemen</FieldLabel>
                            <Select
                                value={data.department_id}
                                onValueChange={(v) => setData('department_id', v)}
                            >
                                <SelectTrigger
                                    id="department_id"
                                    className="bg-background w-full"
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

                        <Isian
                            id="password"
                            label="Kata Sandi"
                            type="password"
                            nilai={data.password}
                            galat={errors.password}
                            onUbah={(v) => setData('password', v)}
                            autoComplete="new-password"
                            wajib
                        />
                        <Isian
                            id="password_confirmation"
                            label="Ulangi Kata Sandi"
                            type="password"
                            nilai={data.password_confirmation}
                            galat={errors.password_confirmation}
                            onUbah={(v) => setData('password_confirmation', v)}
                            autoComplete="new-password"
                            wajib
                        />
                    </div>

                    <Field>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            Daftar
                        </Button>
                        <FieldDescription className="text-center">
                            Sudah punya akun?{' '}
                            <Link href={route('login')} className="underline underline-offset-4">
                                Masuk
                            </Link>
                        </FieldDescription>
                    </Field>
                </FieldGroup>
            </form>
        </AuthLayout>
    );
}

/** Satu field teks — tujuh kali pola yang sama, ditulis sekali. */
function Isian({
    id,
    label,
    nilai,
    galat,
    onUbah,
    type = 'text',
    petunjuk,
    autoComplete,
    wajib = false,
}: {
    id: string;
    label: string;
    nilai: string;
    galat?: string;
    onUbah: (v: string) => void;
    type?: string;
    petunjuk?: string;
    autoComplete?: string;
    wajib?: boolean;
}) {
    return (
        <Field data-invalid={!!galat || undefined}>
            <FieldLabel htmlFor={id}>{label}</FieldLabel>
            <Input
                id={id}
                name={id}
                type={type}
                value={nilai}
                onChange={(e) => onUbah(e.target.value)}
                placeholder={petunjuk}
                autoComplete={autoComplete}
                required={wajib}
                aria-invalid={!!galat}
                className="bg-background"
            />
            <FieldError errors={galat ? [{ message: galat }] : undefined} />
        </Field>
    );
}
