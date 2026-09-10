{{--
  | Kerangka halaman galat (403, 504) — SATU-SATUNYA pemakai berkas ini.
  |
  | Halaman tamu yang sesungguhnya (login, registrasi, menunggu persetujuan)
  | sudah pindah ke `resources/js/layouts/AuthLayout.tsx` sejak Fase 4. Yang
  | tersisa di sini hanya galat, dan galat memang TIDAK BISA jadi halaman
  | Inertia: Laravel merendernya dari luar konteks Inertia (lihat catatan di
  | `errors/403.blade.php`).
  |
  | Fase 13: Soft UI + Bootstrap 5 + Bootstrap Icons + Poppins DIBUANG. Bukan
  | sekadar demi kebersihan — halaman 504 justru muncul saat server sedang
  | tersendat, dan saat itulah tiga permintaan CDN eksternal paling mungkin
  | ikut menggantung. Berkas ini kini mandiri penuh: nol CDN, nol `@vite`,
  | nol manifest. Ia tetap tergambar benar meski `public/build` belum dibuat
  | atau rusak — keadaan yang persis melahirkan galat 500 di tempat pertama.
  |
  | Palet & radiusnya disalin dari token `resources/css/app.css` supaya rupanya
  | tetap satu keluarga dengan aplikasi. Kalau `--primary` di sana berubah,
  | ubah juga di sini — hanya dua nilai, dan itu harga yang dibayar demi
  | kemandirian di atas.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SmartPro') — SmartPro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-webicon.png') }}">

    {{-- Anti-FOUC tema, kembaran baris di `app.blade.php`. Kunci `theme` milik
         `next-themes`; halaman ini hanya MEMBACA, tak pernah menulisnya. --}}
    <script>
        try {
            if (localStorage.getItem('theme') === 'dark') document.documentElement.classList.add('dark');
        } catch (e) {}
    </script>

    <style>
        :root {
            --latar: oklch(1 0 0);
            --teks: oklch(0.145 0.008 326);
            --redup: oklch(0.542 0.034 322.5);
            --kartu: oklch(1 0 0);
            --garis: oklch(0.922 0.005 325.62);
            --utama: oklch(0.514 0.222 16.935);
            --utama-teks: oklch(0.969 0.015 12.422);
        }
        .dark {
            --latar: oklch(0.145 0.008 326);
            --teks: oklch(0.985 0 0);
            --redup: oklch(0.711 0.019 323.02);
            --kartu: oklch(0.212 0.019 322.12);
            --garis: rgba(255, 255, 255, .1);
            --utama: oklch(0.455 0.188 13.697);
            --utama-teks: oklch(0.985 0 0);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: var(--latar);
            color: var(--teks);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        .kartu {
            width: 100%;
            max-width: 24rem;
            background: var(--kartu);
            border: 1px solid var(--garis);
            border-radius: 0.625rem;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        /* Dua berkas logo, sama seperti `AuthLayout.tsx` — yang terang tak
           terbaca di atas kartu gelap. */
        .merek { height: 2rem; width: auto; margin-bottom: 1.5rem; }
        .dark .merek-terang, .merek-gelap { display: none; }
        .dark .merek-gelap { display: inline; }
        .gambar { max-width: 100%; height: auto; margin-bottom: 1rem; }
        h1 { font-size: 1.125rem; font-weight: 600; margin: 0 0 0.5rem; }
        p { color: var(--redup); margin: 0 0 1.5rem; }
        .tombol {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--utama);
            color: var(--utama-teks);
            font-weight: 500;
            text-decoration: none;
            transition: opacity .15s;
        }
        .tombol:hover { opacity: .9; }
        .jejak { color: var(--redup); font-size: 0.75rem; margin: 1.5rem 0 0; }
    </style>
</head>
<body>
    <div class="kartu">
        <img src="{{ asset('images/logo-web.png') }}" alt="SmartPro" class="merek merek-terang">
        <img src="{{ asset('images/logodarkmode.png') }}" alt="SmartPro" class="merek merek-gelap">
        @yield('content')
        <p class="jejak">&copy; {{ date('Y') }} PT Putra Perkasa Abadi — Divisi ICTMD</p>
    </div>
</body>
</html>
