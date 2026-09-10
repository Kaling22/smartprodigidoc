<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-webicon.png') }}">

    {{--
      Root template SELURUH halaman Inertia.

      TANPA CDN Bootstrap 5, TANPA Alpine.js, TANPA SweetAlert2 — dan itu
      disengaja. Bootstrap masih hidup di `layouts/app.blade.php` yang tidak
      pernah disentuh halaman Inertia, jadi selama masa transisi kedua lapisan
      berjalan berdampingan tanpa satu pun kelas CSS lama bocor ke sini.
    --}}

    <title inertia>{{ config('app.name', 'SmartPro') }}</title>

    {{--
      Anti-FOUC tema. Inertia dirender di klien, jadi tanpa baris ini pengguna
      mode gelap melihat satu frame putih tiap kali halaman dimuat penuh.
      Kuncinya `theme` — kunci bawaan `next-themes`, yang mengambil alih begitu
      React selesai memasang.
    --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
        } catch (e) {}
    </script>

    {{-- Ziggy: menyisipkan seluruh rute bernama + fungsi `route()` ke global.
         Dipasang karena 229 pemanggilan `route()` di 100 nama rute harus tetap
         bisa ditulis dengan NAMA, bukan URL mentah — jalur yang berubah di
         `routes/web.php` akan ikut sendiri. Otorisasi tak tersentuh: middleware
         `can:` tetap di server (pakem P4); yang dibagikan hanya peta nama→URL. --}}
    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
