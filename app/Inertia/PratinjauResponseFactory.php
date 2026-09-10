<?php

namespace App\Inertia;

use Inertia\Response;
use Inertia\ResponseFactory;

/**
 * Pemilih kerangka halaman — SATU titik, nol duplikasi route.
 *
 * ## Sejak 2026-09-07: V1 MATI, V2 satu-satunya kerangka
 *
 * Pemilik memutuskan "matikan V1 dan seluruh komponennya, V2 jadi utama".
 * Kelas ini karena itu TIDAK LAGI membaca `session('ui_v2')`: kembaran `V2/*`
 * dipilih SELALU. Sakelarnya (`pratinjau.toggle`) sudah dicabut dari
 * `routes/web.php` dan tombolnya dari topbar, jadi tak ada lagi jalan — lewat
 * peramban maupun sesi — untuk menggambar pohon lama.
 *
 * Berkas `pages/*.tsx` V1 (41 halaman) dan `components/dasbor/*` MASIH ADA di
 * disk dan kini nol pembaca. Menghapusnya butuh konfirmasi eksplisit pemilik
 * (CLAUDE.md §4) dan itu belum diberikan; sampai saat itu ia cuma berat di
 * `tsc`, bukan di bundel — Vite hanya memaketkan modul yang benar-benar
 * terjangkau.
 *
 * Syarat `is_file` DIPERTAHANKAN sebagai jaring: halaman baru yang lahir tanpa
 * kembaran `V2/` akan jatuh ke `pages/{komponen}.tsx` seperti sebelumnya alih-
 * alih melempar "Halaman Inertia tidak ditemukan" di peramban.
 *
 * ## Catatan sejarah (mekanisme pratinjau, sudah tidak berlaku)
 *
 * Tranche pratinjau harus bisa dibuka BERDAMPINGAN dengan 41 halaman lama
 * supaya keduanya bisa dibandingkan langsung. Cara yang jelas — menggandakan
 * route-nya — berarti 41 titik yang bisa menyimpang diam-diam dari
 * pasangannya: satu `can:` yang lupa disalin, satu prop yang tertinggal, dan
 * pembandingnya berhenti membandingkan hal yang sama.
 *
 * Jadi yang digandakan hanya NAMA KOMPONEN, pada satu tempat yang seluruh
 * halaman lewati. Controller, props, Policy, middleware `can:`, dan closure
 * `share()` tetap jalur yang sama persis.
 *
 * Awalan `V2/` dipasang hanya bila DUA syarat terpenuhi:
 *
 *  a. sesi tidak MEMATIKAN pratinjau (`session('ui_v2', true)` — bawaannya
 *     menyala sejak rencana pra-produksi Fase 0, keputusan K-A: produksi
 *     memakai V2, dan V1 tinggal cadangan mati yang tak dihapus), dan
 *  b. kembaran TSX-nya benar-benar ada di disk.
 *
 * Syarat (b) yang membuat tranche bertahap ini mungkin: dari 41 halaman, T1
 * baru menulis tiga. Sisanya jatuh ke komponen aslinya, bukan ke galat
 * "Halaman Inertia tidak ditemukan" yang dilempar `app.tsx`.
 *
 * Konsekuensi yang disengaja: dengan bendera MATI (`ui_v2 => false`, dipasang
 * lewat rute sakelar) kelas ini tak mengubah apa pun — itulah jalan kembali ke
 * V1 yang tetap dipelihara. Yang berubah sejak Fase 0 hanya BAWAANNYA: sesi
 * kosong — pengguna baru, produksi — mendapat V2.
 *
 * Berumur pendek — Tranche 4 (approve) memindahkan `pages/V2/*` ke `pages/*`
 * dan menghapus berkas ini beserta binding-nya.
 */
class PratinjauResponseFactory extends ResponseFactory
{
    public function render($component, $props = []): Response
    {
        if (is_file(resource_path("js/pages/V2/{$component}.tsx"))) {
            $component = "V2/{$component}";
        }

        return parent::render($component, $props);
    }
}
