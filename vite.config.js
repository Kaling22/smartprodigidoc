import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // `resources/js/app.js` (Alpine/axios lama) sengaja TIDAK ikut:
            // tak satu pun Blade memakai `@vite`, lapisan lama murni CDN.
            // Berkasnya baru dihapus di Fase 13 bersama layout lamanya.
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
