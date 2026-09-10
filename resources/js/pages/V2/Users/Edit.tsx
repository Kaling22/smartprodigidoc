import { ArrowLeft01Icon, InformationCircleIcon, Tick02Icon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';

import { Alert, AlertDescription } from '@/components/ui-maia/alert';
import { Button } from '@/components/ui-maia/button';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui-maia/card';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui-maia/select';
import { Spinner } from '@/components/ui-maia/spinner';
import { ConfirmDialog } from '@/components/v2/ConfirmDialog';
import { AppLayout } from '@/layouts/V2/AppLayout';
import type { UsersFormProps } from '@/types/pengguna';

interface Props extends UsersFormProps {
    user: { id: number; name: string; nrp: string | null; department_id: number | null };
    peranSekarang: string | null;
}

/**
 * "Ubah Peran" — kembaran V2 `pages/Users/Edit.tsx`.
 *
 * KENAPA ADA layar ini: pendaftaran mandiri SELALU melahirkan Non-Staff, jadi
 * seorang GL yang terlanjur mendaftar lewat halaman login dulu terkunci
 * selamanya (NRP unik). Lihat `UserManagementController::update`.
 *
 * Konfirmasi sebelum kirim WAJIB tetap ada (CLAUDE.md §13): mengubah peran ikut
 * mengubah hak akses DAN posisi orang itu pada alur dokumen.
 *
 * Penyimpangan dari berkas V1, seluruhnya PATOKAN-GAYA-V2:
 *  · §3.9 — kedua pilihan dibungkus `FieldGroup`, dan `SelectTrigger`-nya
 *    membawa `aria-invalid` sendiri; sebelumnya cuma `Field` yang menandainya.
 *  · §3.4 — ikon `Alert` eksplisit `strokeWidth={1.5}` + `size-4`.
 *  · Tombol Simpan memakai `Spinner` alih-alih menukar teksnya (preseden
 *    `V2/Auth/Login`).
 */
export default function UsersEdit({ user, departments, roles, roleLabels, peranSekarang }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        role: peranSekarang ?? '',
        department_id: user.department_id ? String(user.department_id) : '',
    });

    return (
        <AppLayout
            judul="Ubah Peran"
            remah={[
                { label: 'Manajemen User', href: route('users.index') },
                { label: 'Ubah Peran' },
            ]}
        >
            <Card>
                <CardHeader>
                    <CardTitle>Ubah Peran</CardTitle>
                    <CardDescription>
                        {user.name} · NRP {user.nrp ?? '—'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert className="mb-6">
                        <HugeiconsIcon
                            icon={InformationCircleIcon}
                            strokeWidth={1.5}
                            className="size-4"
                        />
                        <AlertDescription>
                            Peran sekarang:{' '}
                            <span className="font-medium">
                                {(peranSekarang && roleLabels[peranSekarang]) ?? peranSekarang ?? '—'}
                            </span>
                            . Mengubah peran ikut menyesuaikan jabatan pada alur dokumen.
                        </AlertDescription>
                    </Alert>

                    <FieldGroup>
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

                        <Field data-invalid={!!errors.department_id || undefined}>
                            <FieldLabel htmlFor="department_id">Departemen</FieldLabel>
                            {/* `*` = tanpa departemen. Radix Select tak menerima
                                nilai kosong sebagai item, dan "tanpa departemen"
                                memang pilihan yang SAH di sini (Pimpinan / Admin). */}
                            <Select
                                value={data.department_id || '*'}
                                onValueChange={(v) => setData('department_id', v === '*' ? '' : v)}
                            >
                                <SelectTrigger
                                    id="department_id"
                                    className="w-full"
                                    aria-invalid={!!errors.department_id}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="*">
                                        — Tanpa departemen (Pimpinan / Admin) —
                                    </SelectItem>
                                    {departments.map((d) => (
                                        <SelectItem key={d.id} value={String(d.id)}>
                                            {d.code} — {d.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <FieldDescription>
                                Pimpinan (PJO) selalu disimpan tanpa departemen — ia melintasi
                                ketujuhnya.
                            </FieldDescription>
                            <FieldError
                                errors={
                                    errors.department_id
                                        ? [{ message: errors.department_id }]
                                        : undefined
                                }
                            />
                        </Field>
                    </FieldGroup>

                    <div className="mt-6 flex gap-2">
                        <ConfirmDialog
                            judul="Ubah Peran?"
                            pesan={`Ubah peran ${user.name}? Hak akses dan posisinya pada alur dokumen ikut berubah.`}
                            tombolYa="Ya, ubah"
                            destruktif
                            onKonfirmasi={() => put(route('users.update', user.id))}
                            pemicu={
                                <Button disabled={processing}>
                                    {processing ? (
                                        <Spinner />
                                    ) : (
                                        <HugeiconsIcon
                                            icon={Tick02Icon}
                                            strokeWidth={1.5}
                                            className="size-4"
                                        />
                                    )}
                                    Simpan Perubahan
                                </Button>
                            }
                        />
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
                </CardContent>
            </Card>
        </AppLayout>
    );
}
