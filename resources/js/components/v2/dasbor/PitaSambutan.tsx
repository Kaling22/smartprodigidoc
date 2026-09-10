import { Link } from '@inertiajs/react';

import { Button } from '@/components/ui-maia/button';
import type { Hero } from '@/types/dasbor';

/**
 * Pita sambutan — satu-satunya permukaan berwarna PENUH di seluruh aplikasi.
 *
 * **Penyimpangan sadar dari CLAUDE.md §13 ("kode dari registry").** Blok
 * `dashboard-01` tak punya padanannya: ia membuka dengan H1 telanjang di atas
 * latar halaman. Itu benar untuk kit yang harus netral bagi ribuan proyek, dan
 * salah untuk SATU aplikasi internal yang punya warna identitas — hasilnya
 * sebelas kotak putih tanpa satu pun titik masuk, yang persis jadi keluhan
 * pemilik 2026-08-31 ("kurang nendang, tiada yang tampak spesial").
 *
 * Aturannya karena itu dijaga di tempat lain: pita ini **satu-satunya**
 * pengecualian. Begitu ada permukaan berwarna penuh KEDUA, ia berhenti jadi
 * jangkar dan berubah jadi kebisingan — dan seluruh sisa dasbor kembali rata.
 *
 * NOL HEX, dan itu bukan kerapian melainkan syarat: gradasinya dua ujung dari
 * SATU token (`--primary`), dan seluruh teks di atasnya memakai
 * `--primary-foreground` — pasangan yang MENURUT DEFINISI kontras dengannya di
 * terang maupun gelap. Idiom yang sama sudah dipakai panel kanan
 * `V2/Auth/Login` (REDESAIN-UI-V2 §9b), jadi halaman pertama dan halaman kedua
 * yang dilihat orang berbicara dengan bahasa yang sama.
 *
 * NOL GERAK. Ornamennya statis dan tak ada satu pun transisi masuk, jadi tak
 * ada yang perlu dijaga `prefers-reduced-motion` — kesan datang dari kontras,
 * bukan dari animasi yang harus dimatikan lagi untuk sebagian orang.
 *
 * Isinya SELURUHNYA dari props `hero` + `greeting`, yang aturannya tinggal di
 * `App\Services\DasborTampilan` (pakem P5). Tak satu pun kalimat per jabatan
 * lahir di berkas ini.
 */
export function PitaSambutan({
    greeting,
    nama,
    hero,
}: {
    greeting: string;
    nama: string;
    hero: Hero;
}) {
    return (
        <section className="from-primary to-primary/70 text-primary-foreground relative isolate overflow-hidden rounded-2xl bg-gradient-to-br px-5 py-5 md:px-7 md:py-6">
            {/* Ornamen — murni dekoratif, karena itu `aria-hidden` dan `-z-10`.
                Dua lingkaran redup memberi pita ini kedalaman tanpa satu pun
                aset gambar: nol unduhan, nol berkas yang bisa hilang saat
                deploy, dan warnanya ikut tema karena ia token yang sama. */}
            <span
                aria-hidden="true"
                className="bg-primary-foreground/10 pointer-events-none absolute -top-24 -right-16 -z-10 size-64 rounded-full blur-2xl"
            />
            <span
                aria-hidden="true"
                className="bg-primary-foreground/10 pointer-events-none absolute -bottom-28 left-1/3 -z-10 size-56 rounded-full blur-2xl"
            />

            <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
                <div className="min-w-0">
                    {/* H1 halaman hidup DI SINI, bukan di layout — lihat prop
                        `kepala` di `layouts/V2/AppLayout`. Tanpa ini halaman
                        dasbor tak punya satu pun H1. */}
                    <h1 className="text-2xl font-semibold tracking-tight text-balance md:text-3xl">
                        {greeting}, {nama}.
                    </h1>
                    <p className="text-primary-foreground/75 mt-1 text-sm">
                        {hero.jabatan}
                        {hero.dept ? ` · ${hero.dept}` : ''} · {hero.jam} WITA · {hero.tanggal}
                    </p>

                    {/* Yang menunggu jadi PIL, bukan kalimat bersambung. Pada
                        SH yang antreannya dua macam, versi lama menyambungnya
                        dengan " · " di tengah paragraf abu-abu — terbaca
                        sebagai keterangan, padahal itu justru tugasnya. */}
                    {hero.tunggu.length > 0 ? (
                        <ul className="mt-3 flex flex-wrap gap-2">
                            {hero.tunggu.map((t) => (
                                <li
                                    key={t}
                                    className="bg-primary-foreground/15 ring-primary-foreground/25 rounded-full px-3 py-1 text-sm font-medium ring-1"
                                >
                                    {t}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-primary-foreground/75 mt-3 text-sm">
                            Tidak ada yang menunggu tindakanmu. Selamat bekerja.
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    {hero.aksi.map((a) => (
                        <Button
                            key={a.label}
                            asChild
                            size="sm"
                            variant={a.utama ? 'secondary' : 'ghost'}
                            className={
                                a.utama
                                    ? // Kebalikan pita, bukan `--secondary`: pasangan
                                      // primary/primary-foreground dijamin kontras di
                                      // KEDUA tema, sementara `--secondary` kebetulan
                                      // terang di mode terang dan gelap di mode gelap —
                                      // di mode gelap ia jadi kepingan gelap di atas
                                      // merah.
                                      'bg-primary-foreground text-primary hover:bg-primary-foreground/90'
                                    : 'text-primary-foreground hover:bg-primary-foreground/15 hover:text-primary-foreground'
                            }
                        >
                            <Link href={a.url}>{a.label}</Link>
                        </Button>
                    ))}
                </div>
            </div>
        </section>
    );
}
