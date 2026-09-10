import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { ThemeProvider } from 'next-themes';
import ReactDOMServer from 'react-dom/server';

import { TooltipProvider } from '@/components/ui-maia/tooltip';

/**
 * GERBANG ASAP — merender halaman Inertia di Node, tanpa peramban.
 *
 * KENAPA ADA: `npm run build` dan `tsc --noEmit` sama-sama HIJAU pada halaman
 * yang, di peramban, cuma menampilkan layar putih. Keduanya memeriksa tipe dan
 * penyusunan modul; tak satu pun benar-benar MERENDER. Galat yang lolos
 * keduanya justru yang paling mahal — mis. `<Tooltip>` di luar
 * `TooltipProvider`, yang bukan diabaikan melainkan DILEMPAR Radix, sehingga
 * satu item sidebar cukup untuk memutihkan seluruh aplikasi.
 *
 * Dipakai `php artisan smartpro:uji-render`, yang mengambil payload halaman
 * SUNGGUHAN (lewat permintaan internal sebagai pengguna nyata) lalu
 * memasukkannya ke sini. Halaman yang melempar galat = gerbang merah, lengkap
 * dengan nama komponen yang menyebabkannya.
 *
 * Susunan pembungkus di bawah WAJIB cerminan `app.tsx`. Kalau di sana lahir
 * provider baru, ia harus ikut ke sini — kalau tidak, gerbang ini berhenti
 * menguji keadaan yang sebenarnya dijalankan pengguna.
 *
 * Bukan SSR sungguhan: keluarannya dibuang, dan `bootstrap/ssr` tidak dilayani
 * siapa pun. Yang dicari cuma "apakah ia melempar".
 */
const halaman = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx');

/*
 | `route()` di peramban datang dari `@routes` (Ziggy), yang tidak ada di Node.
 | Di sini ia diganti boneka yang mengembalikan jalur palsu — DISENGAJA: yang
 | diuji gerbang ini adalah apakah halaman BISA DIRENDER, bukan apakah URL-nya
 | benar. Kebenaran nama rute dijaga di tempat lain: Ziggy melempar galat untuk
 | nama yang tak dikenal saat dipakai sungguhan.
 */
(globalThis as Record<string, unknown>).route ??= (nama: string) => `/uji/${nama}`;

export async function render(page: unknown): Promise<string> {
    const hasil = await createInertiaApp({
        page: page as never,
        render: ReactDOMServer.renderToString,
        resolve: (nama) => {
            const muat = halaman[`./pages/${nama}.tsx`];

            if (!muat) {
                throw new Error(`Halaman Inertia tidak ditemukan: resources/js/pages/${nama}.tsx`);
            }

            return muat().then((modul) => modul.default);
        },
        setup: ({ App, props }) => (
            <ThemeProvider attribute="class" defaultTheme="light" enableSystem={false}>
                {/* Cerminan app.tsx — SATU Provider sejak pohon V1 dihapus
                    (2026-09-07). Pembungkus di sini WAJIB tetap cerminan
                    `app.tsx`: kalau tidak, gerbang render berhenti menguji
                    keadaan yang benar-benar dijalankan pengguna
                    (CLAUDE.md §14c). */}
                <TooltipProvider>
                    <App {...props} />
                </TooltipProvider>
            </ThemeProvider>
        ),
    });

    return hasil?.body ?? '';
}
