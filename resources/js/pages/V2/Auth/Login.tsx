import { ViewIcon, ViewOffSlashIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';
import { Link, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

import { Button } from '@/components/ui-maia/button';
import { Checkbox } from '@/components/ui-maia/checkbox';
import {
    Field, FieldDescription, FieldError, FieldGroup, FieldLabel,
} from '@/components/ui-maia/field';
import { Input } from '@/components/ui-maia/input';
import {
    InputGroup, InputGroupAddon, InputGroupButton, InputGroupInput,
} from '@/components/ui-maia/input-group';
import { Spinner } from '@/components/ui-maia/spinner';
import { AuthLayout } from '@/layouts/V2/AuthLayout';

/**
 * Masuk — susunan form blok `login-02` (`components/login-form.tsx`) di gaya
 * maia: `FieldGroup` > `Field` > `FieldLabel` + `Input`, tautan penutup lewat
 * `FieldDescription`.
 *
 * Bedanya dari blok: SmartPro masuk dengan **NRP**, bukan email — dan tak ada
 * "lupa kata sandi" maupun tombol pihak ketiga, karena keduanya memang tak ada
 * di aplikasi ini. Reset sandi hanya lewat Admin (`routes/web.php`), jadi
 * tautan "lupa kata sandi" akan berujung 404 — dan tautan yang berujung 404
 * lebih buruk daripada tak ada tautan. Batas percobaan login tetap di server
 * (`LoginRequest::ensureIsNotRateLimited`, CLAUDE.md §15).
 *
 * Beda dari versi nova cuma dua: kit-nya maia (input & tombol berbentuk pil),
 * dan keadaan memproses memakai `Spinner` resmi alih-alih menukar teks tombol —
 * tombol yang berubah lebar saat ditekan menggeser tata letak tepat pada saat
 * orang sedang menunggu.
 *
 * REVISI-UI-V3 Fase 2b (R12): judul RATA KIRI supaya sebaris dengan logo yang
 * turun dari `AuthLayout`, dan kolom kata sandi dapat tombol mata. Itu
 * satu-satunya kendali baru di halaman ini.
 */
export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        nrp: '',
        password: '',
        remember: false as boolean,
    });

    /**
     * Sandi terlihat atau tidak. Sengaja TIDAK diingat antar-kunjungan: nilai
     * bawaannya harus selalu tertutup, karena layar login paling sering dibuka
     * justru di tempat yang ada orang lain.
     */
    const [sandiTampil, setSandiTampil] = useState(false);

    function kirim(e: FormEvent) {
        e.preventDefault();
        post(route('login.store'));
    }

    return (
        <AuthLayout judul="Masuk">
            <form onSubmit={kirim} className="flex flex-col gap-6">
                <FieldGroup>
                    <div className="flex flex-col gap-1">
                        <h1 className="text-2xl font-bold">Selamat datang</h1>
                        <p className="text-muted-foreground text-sm text-balance">
                            Masukkan NRP dan kata sandi untuk masuk.
                        </p>
                    </div>

                    <Field data-invalid={!!errors.nrp || undefined}>
                        <FieldLabel htmlFor="nrp">NRP</FieldLabel>
                        <Input
                            id="nrp"
                            name="nrp"
                            value={data.nrp}
                            onChange={(e) => setData('nrp', e.target.value)}
                            placeholder="Nomor Registrasi Pegawai"
                            autoComplete="username"
                            required
                            autoFocus
                            aria-invalid={!!errors.nrp}
                            className="bg-background"
                        />
                        {/* Kredensial salah dilaporkan server pada kunci `nrp`
                            (LoginController@store) — satu pesan untuk NRP MAUPUN
                            kata sandi, supaya tak jadi alat menebak NRP mana
                            yang terdaftar. */}
                        <FieldError errors={errors.nrp ? [{ message: errors.nrp }] : undefined} />
                    </Field>

                    <Field data-invalid={!!errors.password || undefined}>
                        <FieldLabel htmlFor="password">Kata Sandi</FieldLabel>
                        {/* `InputGroup` registry, bukan tombol yang ditempel
                            sendiri di atas `Input` ber-`relative`: ia sudah
                            memindahkan cincin fokus & rona `aria-invalid` dari
                            input ke pembungkusnya, jadi kotak sandi tetap
                            semerah dan setinggi kotak NRP tanpa satu pun kelas
                            karangan (CLAUDE.md §13). */}
                        <InputGroup className="bg-background">
                            <InputGroupInput
                                id="password"
                                name="password"
                                type={sandiTampil ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                required
                                aria-invalid={!!errors.password}
                            />
                            <InputGroupAddon align="inline-end">
                                <InputGroupButton
                                    size="icon-xs"
                                    onClick={() => setSandiTampil((v) => !v)}
                                    aria-pressed={sandiTampil}
                                    aria-label={
                                        sandiTampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'
                                    }
                                >
                                    <HugeiconsIcon
                                        icon={sandiTampil ? ViewOffSlashIcon : ViewIcon}
                                        strokeWidth={1.5}
                                    />
                                </InputGroupButton>
                            </InputGroupAddon>
                        </InputGroup>
                        <FieldError errors={errors.password ? [{ message: errors.password }] : undefined} />
                    </Field>

                    <Field orientation="horizontal">
                        <Checkbox
                            id="remember"
                            checked={data.remember}
                            onCheckedChange={(v) => setData('remember', v === true)}
                        />
                        <FieldLabel htmlFor="remember" className="font-normal">
                            Ingat saya
                        </FieldLabel>
                    </Field>

                    <Field>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : null}
                            Masuk
                        </Button>
                        <FieldDescription className="text-center">
                            Belum punya akun?{' '}
                            <Link href={route('register')} className="underline underline-offset-4">
                                Daftar sebagai Non-Staff
                            </Link>
                        </FieldDescription>
                    </Field>
                </FieldGroup>
            </form>
        </AuthLayout>
    );
}
