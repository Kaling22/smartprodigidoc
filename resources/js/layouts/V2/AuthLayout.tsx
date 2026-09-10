import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

/**
 * Kerangka halaman tamu V2 — susunan blok `login-02` di gaya maia:
 * `grid min-h-svh lg:grid-cols-2`, kolom kiri `p-6 md:p-10`, kolom kanan panel
 * merek.
 *
 * KOLOM KANAN TIDAK LAGI FOTO. Versi nova memasang
 * `/soft-ui/img/curved-images/curved6.jpg` — aset tema Soft UI yang sudah
 * dibuang dari proyek (CLAUDE.md §13). Menyisakan satu-satunya jejaknya justru
 * di halaman pertama yang dilihat orang adalah kebalikan dari yang diputuskan.
 *
 * Penggantinya panel merek: gradasi `--primary` (merah PPA) + kalimat
 * identitas. Nol aset baru, nol unduhan, dan warnanya ikut token — jadi ia tak
 * bisa bertabrakan dengan tema gelap seperti foto ber-hex tetap.
 * (Keputusan pemilik 2026-08-31 atas pertanyaan terbuka §9b.)
 *
 * REVISI-UI-V3 Fase 2b (R12) mengubah DUA hal, keduanya tata letak:
 *
 *  - Logo tak lagi dipatok di pojok kiri atas halaman melainkan **turun
 *    menemani judul** — logo, judul, dan formulir jadi SATU blok rata kiri di
 *    tengah kolom. Merek yang berdiri sendiri di pojok membaca sebagai hiasan
 *    kop; merek yang menempel di judul membaca sebagai identitas layar yang
 *    sedang dibuka.
 *  - Panel kanan dibagi dua: sambutan di atas, satu kartu mengambang di bawah.
 *    Logo di puncak panel DICABUT (keputusan pemilik 2026-09-01) — sesudah
 *    logo turun ke kolom kiri, yang di panel cuma jadi salinan kedua di layar
 *    yang sama.
 *
 * `layouts/guest.blade.php` TIDAK disentuh — ia cangkang mandiri untuk halaman
 * galat, dan justru harus tetap tergambar saat build sedang bermasalah.
 *
 * `lebar` adalah satu-satunya penyimpangan dari blok: `max-w-xs` bawaan pas
 * untuk login (dua field) tapi menjepit formulir pendaftaran (tujuh field).
 * Pemakainya sekarang cuma `V2/Auth/Login`, tapi propnya dipertahankan supaya
 * Register/Pending bisa menyusul tanpa membongkar kerangka ini lagi.
 */
export function AuthLayout({
    judul,
    lebar = 'max-w-xs',
    children,
}: {
    judul: string;
    lebar?: string;
    children: ReactNode;
}) {
    return (
        <div className="ui-v2 grid min-h-svh lg:grid-cols-2">
            <Head title={judul} />

            <div className="flex flex-col gap-4 p-6 md:p-10">
                <div className="flex flex-1 items-center justify-center">
                    {/* Logo + judul + formulir = satu kolom rata kiri. `lebar`
                        membatasi ketiganya sekaligus, jadi logo tak pernah
                        melebar melampaui kotak isian di bawahnya.

                        `h-12` menyamakan merek ini dengan logo sidebar V2
                        (docs/PATOKAN-GAYA-V2.md §3.1) — dulu `h-8`. Aman di
                        kedua tempat karena berkasnya 4000×2000 = 2:1, jadi
                        `h-12` berarti lebar 96px, sementara kolom ini
                        `max-w-xs` (320px) dan rel sidebar 18rem (288px). */}
                    <div className={cn('flex w-full flex-col gap-6', lebar)}>
                        <Link href={route('login')} className="flex items-center gap-2 font-medium">
                            <img
                                src="/images/logo-web.png"
                                alt="SmartPro"
                                className="h-12 w-auto object-contain dark:hidden"
                            />
                            <img
                                src="/images/logodarkmode.png"
                                alt="SmartPro"
                                className="hidden h-12 w-auto object-contain dark:block"
                            />
                        </Link>
                        {children}
                    </div>
                </div>
                <p className="text-muted-foreground text-center text-xs">
                    © {new Date().getFullYear()} PT Putra Perkasa Abadi — Divisi ICTMD
                </p>
            </div>

            {/* Panel merek — foto `public/images/login.jpg` (kiriman pemilik
                2026-09-01, menggantikan gradasi polos).

                Gradasi `--primary` TIDAK dibuang melainkan turun pangkat jadi
                LAPIS TINT di atas fotonya, dan itu yang membuat teks tetap
                terbaca. Fotonya nyaris hitam di atas tapi merah TERANG di
                bawah — persis tempat kartu mengambang duduk — jadi
                `primary-foreground` (nyaris putih) di atas merah terang itu
                kontrasnya tipis. Tint-nya karena itu paling pekat di bawah
                (`from-primary/85`), paling tipis di tengah (`via-primary/30`)
                supaya gelombangnya tetap terlihat, dan sedang di atas
                (`to-primary/60`).

                Tetap **nol hex**: yang dipakai token `--primary` dengan opasitas
                berbeda, bukan warna baru. Kartu mengambangnya pun begitu —
                latarnya `primary-foreground/10`, yaitu warna teksnya sendiri
                yang ditipiskan (REDESAIN-UI-V2 §9b).

                Fotonya `aria-hidden` lewat `alt=""`: ia dekorasi, dan nama
                berkas yang dibacakan pembaca layar cuma kebisingan.

                `justify-between` + `flex-1` pada blok sambutan: sambutan
                mengambil sisa ruang dan memusatkan dirinya di sana, kartu
                menempel ke bawah. Jadi kartunya duduk di tempat yang sama
                betapapun tinggi jendelanya. */}
            <div className="bg-primary relative hidden lg:block">
                <img
                    src="/images/login.jpg"
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="from-primary/85 via-primary/30 to-primary/60 absolute inset-0 bg-gradient-to-t" />
                <div className="text-primary-foreground absolute inset-0 flex flex-col justify-between gap-8 p-12">
                    <div className="flex flex-1 flex-col justify-center gap-4">
                        <p className="max-w-sm text-3xl leading-tight font-semibold text-balance">
                            Dokumen mutu, satu pintu.
                        </p>
                        <p className="max-w-sm text-sm/relaxed opacity-80">
                            SOP, Instruksi Kerja, Standar Parameter, dan JSA untuk tujuh departemen
                            PT Putra Perkasa Abadi — site Adaro.
                        </p>
                    </div>

                    {/* Isinya sengaja kalimat identitas, BUKAN angka.
                        Halaman ini belum terautentikasi: menampilkan jumlah
                        pengguna atau dokumen di sini berarti mengarang data
                        atau membocorkannya (§5). */}
                    <div className="bg-primary-foreground/10 ring-primary-foreground/20 max-w-sm rounded-2xl p-6 shadow-lg ring-1">
                        <p className="font-semibold">Satu alur, dari draft sampai berlaku</p>
                        <p className="mt-1.5 text-sm/relaxed opacity-80">
                            Tiap dokumen menempuh jalur tinjau dan setuju yang sama, dan riwayat
                            revisinya tersimpan sampai versi terakhir.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
