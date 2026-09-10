import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { ThemeProvider } from 'next-themes';
import { createRoot } from 'react-dom/client';

import { Toaster } from '@/components/ui-maia/sonner';
import { TooltipProvider } from '@/components/ui-maia/tooltip';

/**
 * Titik masuk seluruh halaman Inertia.
 *
 * Nama komponen halaman yang dikirim `Inertia::render('X')` dipetakan langsung
 * ke `resources/js/pages/X.tsx` — sesuai aturan CLAUDE.md §3
 * "nama route = nama halaman = nama method, satu banding satu".
 *
 * Glob-nya MALAS (tanpa `eager`), jadi tiap halaman jadi chunk sendiri dan
 * peramban hanya mengunduh yang benar-benar dibuka. Dengan 78 halaman yang
 * akan lahir di Fase 4–12, satu bundel raksasa bukan pilihan.
 */
const halaman = import.meta.glob<{ default: ResolvedComponent }>('./pages/**/*.tsx');

createInertiaApp({
    title: (judul) => (judul ? `${judul} — SmartPro` : 'SmartPro'),
    resolve: (nama) => {
        const muat = halaman[`./pages/${nama}.tsx`];

        if (!muat) {
            throw new Error(`Halaman Inertia tidak ditemukan: resources/js/pages/${nama}.tsx`);
        }

        return muat().then((modul) => modul.default);
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            /*
             | Tema terang/gelap dipegang `next-themes`, bukan IIFE buatan sendiri
             | seperti di Blade lama. Paketnya SUDAH ikut terpasang bersama
             | komponen `sonner` (Toaster membacanya lewat `useTheme`), jadi
             | menulis pengelola tema kedua hanya akan membuat toast dan halaman
             | bisa berbeda tema. Anti-FOUC-nya ada di `views/app.blade.php`.
             */
            <ThemeProvider attribute="class" defaultTheme="light" enableSystem={false} disableTransitionOnChange>
                {/*
                 | TooltipProvider WAJIB, dan letaknya WAJIB di sini.
                 |
                 | Versi `tooltip` yang dibangkitkan shadcn TIDAK membungkus
                 | Provider-nya sendiri, dan `SidebarProvider` juga tidak.
                 | `SidebarMenuButton` memakai <Tooltip> untuk tiap item
                 | bertooltip — dan Radix MELEMPAR galat, bukan sekadar diam,
                 | bila Root dipakai di luar Provider. Satu item sidebar cukup
                 | untuk membuat SELURUH halaman jadi putih kosong.
                 |
                 | Dipasang di titik masuk, bukan di AppLayout: halaman berikutnya
                 | yang memakai Tooltip di luar layout tak boleh bisa menghidupkan
                 | kembali layar putih yang sama.
                 |
                 | Provider KEDUA (`ui/tooltip`, kit nova) dicabut 2026-09-07
                 | bersama seluruh pohon V1 — `components/ui/` sudah tak ada lagi
                 | pembacanya. Selama jendela pratinjau keduanya harus bersarang
                 | sebab dua modul tooltip = dua React context; sekarang tinggal
                 | satu kit, jadi satu Provider.
                 */}
                <TooltipProvider>
                    <App {...props} />
                </TooltipProvider>
                <Toaster richColors closeButton position="top-right" />
            </ThemeProvider>,
        );
    },
    progress: { color: '#c8102e' },
});
