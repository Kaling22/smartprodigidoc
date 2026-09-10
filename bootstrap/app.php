<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // Rute untuk aplikasi mobile (Flutter). Laravel memberinya awalan `/api`
        // dan grup middleware `api` (stateless, tanpa sesi/CSRF) secara otomatis.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // `active` menghadang akun yang belum disetujui (pending/rejected) dan
        // mengarahkannya ke halaman tunggu — dipasang pada grup rute terautentikasi.
        $middleware->alias([
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);

        // Kontrak data global halaman Inertia (auth.can, notifications, flash,
        // statusMeta/statusLabels). Tak berpengaruh pada halaman Blade lama —
        // `share()` hanya terbaca oleh respons Inertia.
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        /*
        | Hosting bersama (Hostinger) memutus TLS di proxy, lalu meneruskan
        | permintaannya ke PHP sebagai HTTP polos. Tanpa mempercayai proxy itu,
        | Laravel menyimpulkan skemanya `http` dan SETIAP URL absolut yang ia
        | bangun ikut salah — termasuk `foto` pada /api/me, yang membuat avatar
        | di HP menempuh redirect http→https tiap kali dimuat.
        |
        | `at: '*'` aman DI SINI karena aplikasi hanya dapat dicapai lewat proxy
        | hosting; ia tidak pernah menerima koneksi langsung dari internet.
        | Di XAMPP lokal header X-Forwarded-* tak pernah ada, jadi baris ini
        | tidak mengubah apa pun saat pengembangan.
        */
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
