<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SmartPro</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-webicon.png') }}">

    {{-- Soft UI Dashboard CSS (sudah memuat Bootstrap 5) — mengganti Bootstrap CDN, tanpa dobel --}}
    <link href="{{ asset('soft-ui/css/soft-ui-dashboard.min.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script>
        (function () { document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('pp-theme') || 'light'); })();
        function ppToggleTheme() {
            var next = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', next);
            localStorage.setItem('pp-theme', next);
        }
    </script>
    <style>
        /* Palet Soft UI (referensi build "orange primary") — dipakai seluruh tema:
           orange=Users/primary, info=Clicks, warning=Sales, danger=Items. */
        :root {
            --pp-navy: #0f2b46; --pp-teal: #12707a; --pp-amber: #f5a623;
            --su-orange1: #ea580c; --su-orange2: #facc15;   /* primary (Users)  */
            --su-info1: #0ea5e9;   --su-info2: #06b6d4;      /* info (Clicks)    */
            --su-warn1: #eab308;   --su-warn2: #f97316;      /* warning (Sales)  */
            /* Ujung kedua dulu MERAH MUDA (#ec4899). Merah muda tak pernah berarti
               apa-apa di aplikasi ini — ia cuma bawaan demo tema — dan membuat
               setiap peringatan tampak seperti aksen dekoratif. Diganti merah
               tua supaya "merah" konsisten berarti gagal/berhenti. */
            --su-danger1: #ef4444; --su-danger2: #dc2626;    /* danger           */
            --su-hijau1:  #22c55e; --su-hijau2:  #16a34a;    /* HASIL baik saja  */
            --su-dark1: #27272a;   --su-dark2: #18181b;      /* dark             */
        }
        /*
        | Kartu sambutan (.pp-hero) — SATU markup, dua tema.
        |
        | Dulu kartunya dipatok gradasi hitam TANPA syarat tema dan seluruh warna
        | latar depannya ditulis mentah (`text-white`, `rgba(255,255,255,…)`),
        | jadi di mode terang ia jadi balok hitam yang tak pernah dirancang.
        | Sekarang bentuk, tata letak, dan TINGGI-nya identik di kedua tema —
        | yang bertukar hanya sembilan token di bawah ini.
        |
        | Terang dijadikan nilai dasar; blok gelap di bawahnya HANYA menimpa
        | token ini. Atribut `data-bs-theme` menempel di <html> — yaitu :root
        | itu sendiri — sehingga `:root[data-bs-theme="dark"]` cukup, tanpa
        | menyentuh IIFE anti-FOUC di atas.
        |
        | Ombak SVG sengaja TIDAK ber-token: oranyenya terbaca di atas krem
        | maupun hitam, jadi variabel yang nilainya sama di kedua tema hanya
        | akan jadi lapisan tak berguna.
        */
        :root {
            /* Krem hangat, bukan putih: kartu biasa sudah putih, hero harus
               tetap terbaca sebagai kepala halaman tanpa perlu jadi blok hitam. */
            --pp-hero-bg1: #fffbf5;  --pp-hero-bg2: #ffedd5;
            --pp-hero-teks: var(--su-dark1);
            --pp-hero-teks-lembut: #6b7280;
            /* Oranye, BUKAN --su-orange2: kuning #facc15 tak terbaca di atas krem. */
            --pp-hero-eyebrow: var(--su-orange1);
            --pp-hero-btn-bg: var(--su-dark1); --pp-hero-btn-teks: #fff;
            --pp-hero-btn-aksen: var(--su-orange2);
            --pp-hero-garis: rgba(0, 0, 0, .18); --pp-hero-garis-isi: rgba(0, 0, 0, .06);
        }
        :root[data-bs-theme="dark"] {
            --pp-hero-bg1: var(--su-dark1); --pp-hero-bg2: var(--su-dark2);
            --pp-hero-teks: #fff;
            --pp-hero-teks-lembut: rgba(255, 255, 255, .72);
            --pp-hero-eyebrow: var(--su-orange2);
            --pp-hero-btn-bg: #fff; --pp-hero-btn-teks: var(--su-dark1);
            --pp-hero-btn-aksen: var(--su-orange1);
            --pp-hero-garis: rgba(255, 255, 255, .35); --pp-hero-garis-isi: rgba(255, 255, 255, .12);
        }
        /*
        | Font: Poppins.
        |
        | Menukar `body` SAJA tidak cukup. Soft UI menyetel
        | `--bs-font-sans-serif:"Inter"`, dan variabel itulah yang dibaca tombol
        | (--bs-btn-font-family), form-control, dan tabel — bukan `body`.
        | Tanpa baris pertama di bawah, hasilnya font CAMPUR: teks Poppins tapi
        | tombol & input masih Inter.
        */
        :root { --bs-font-sans-serif: 'Poppins', sans-serif; }
        body { font-family: 'Poppins', sans-serif; }
        /* Poppins geometris & lebih lebar dari Inter — rapatkan tracking pada
           ukuran besar supaya judul tak terkesan gemuk. */
        h1, h2, h3, .h1, .h2, .h3 { letter-spacing: -.015em; }
        [x-cloak] { display: none !important; }

        /* ===== Sidebar Soft UI: putih, ikon oranye, item aktif = pill gradasi oranye ===== */
        .sidenav .sidenav-header { min-height: 4.2rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .sidenav .sidenav-header img { max-width: 100%; max-height: 46px; height: auto; object-fit: contain; }

        /* Logo per tema — SILANG-PUDAR, bukan tukar `display`.
           `display:none/block` memotong keras: logo lama lenyap dan yang baru
           muncul di frame yang sama, jadi pergantian tema terasa berkedip.
           Di sini keduanya ditumpuk absolut di dalam kotak setinggi tetap dan
           hanya opasitasnya yang beranimasi — tinggi sidebar tak pernah
           bergoyang, dan tak ada frame kosong di antaranya.

           Kedua berkas SAMA rasionya (4000x2000), jadi max-height-nya WAJIB
           sama. Dulu dipatok beda (54px terang vs 72px gelap) mengikuti
           komentar usang yang menyebut aset lama 2000x821 vs 2000x2000 —
           itulah sebab logo berubah ukuran saat tema ditukar.

           Selektornya sengaja ikut `.sidenav .sidenav-header`: aturan img di
           atas mematok max-height 46px dan spesifisitasnya lebih tinggi
           daripada satu kelas, jadi tanpa ini tinggi di sini tak pernah
           berlaku. */
        /* Kotak logo WAJIB punya lebar nyata (width:100%). `.sidenav-header`
           sebuah flex container, dan isi `.navbar-brand` seluruhnya berposisi
           absolut — tanpa lebar eksplisit ia menyusut mendekati nol, lalu
           "tengah" yang dihitung anaknya adalah tengah dari kotak nol itu.

           Pemusatannya memakai left:50% + translateX(-50%), BUKAN
           `inset:0 + margin:auto`: begitu gambarnya lebih lebar dari kotak
           induk (mis. saat max-width dinaikkan melewati 100%), aturan
           margin:auto jadi over-constrained — CSS membuang margin kanan dan
           gambarnya menempel ke KIRI. Cara ini tak peduli lebar gambarnya. */
        .sidenav .sidenav-header .navbar-brand { position: relative; display: block; width: 100%; height: 54px; }
        .sidenav .sidenav-header .pp-logo {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            max-width: 100%; max-height: 54px; height: auto; object-fit: contain;
            transition: opacity .25s ease;
        }
        .sidenav .sidenav-header .pp-logo-terang { opacity: 1; }
        .sidenav .sidenav-header .pp-logo-gelap { opacity: 0; }
        [data-bs-theme="dark"] .sidenav .sidenav-header .pp-logo-terang { opacity: 0; }
        [data-bs-theme="dark"] .sidenav .sidenav-header .pp-logo-gelap { opacity: 1; }
        /* Hormati setelan sistem: silang-pudar ini murni dekoratif. */
        @media (prefers-reduced-motion: reduce) {
            .sidenav .sidenav-header .pp-logo { transition: none; }
        }
        .sidenav .nav-link .icon { background-image: none !important; background-color: #fff !important; }
        .sidenav .nav-link .icon i { color: var(--su-orange1); font-size: .8rem; }   /* ikon selalu oranye */
        /* Soft UI menggeser glyph ke bawah (.icon-shape i{top:11px}, .icon-sm i{top:2px})
           sehingga ikon tak pernah center di kotaknya. Netralkan agar flex-center bekerja. */
        .sidenav .icon i, .sidenav .icon-shape i, .sidenav .icon-sm i {
            position: static; top: auto; opacity: 1;
        }
        .sidenav .navbar-nav > .nav-item > .nav-link.active {
            background-image: linear-gradient(310deg, var(--su-orange1) 0%, var(--su-orange2) 100%) !important;
            box-shadow: 0 3px 6px -2px rgba(234, 88, 12, .45);
        }
        /* Teks putih hanya untuk item TINGKAT ATAS yang berlatar pill gradasi oranye.
           Dulu tak dibatasi `> .nav-item >`, sehingga sub-menu aktif ikut memutih —
           putih di atas latar oranye MUDA = nyaris tak terbaca. */
        .sidenav .navbar-nav > .nav-item > .nav-link.active .nav-link-text { color: #fff !important; font-weight: 600; }
        .sidenav .nav-link.active .icon { background-color: #fff !important; }
        .sidenav .nav-link.active .icon i { color: var(--su-orange1) !important; }
        /* Dropdown "Dokumen Baru" — sub-item pakai pembungkus ikon (kotak) */
        .pp-caret { font-size: .7rem; transition: transform .2s ease; color: #67748e; }
        .pp-caret-open { transform: rotate(180deg); }

        /*
        | Titik penanda pada menu INDUK — "ada yang perlu dikerjakan di dalam".
        |
        | Lencana angka hanya menempel di SUB-menu, jadi menu induk yang sedang
        | tertutup tak memberi isyarat apa pun: Persetujuan Nonaktif & Masukan
        | Lapangan bisa menumpuk berhari-hari tanpa terlihat sampai seseorang
        | kebetulan membuka menunya.
        |
        | Ditaruh tepat SESUDAH label, bukan didorong ke kanan dengan `ms-auto`.
        | Sebabnya bukan selera: caret di sebelahnya sudah memakai `ms-auto`
        | (utilitas Bootstrap ber-`!important`), dan dua margin auto dalam satu
        | baris flex justru MEMBAGI ruang kosong di antara keduanya — titiknya
        | berakhir mengambang di tengah. Menempel pada teks juga lebih terbaca:
        | ia menerangkan menunya, bukan berdiri sendiri di tepi.
        */
        .pp-titik {
            display: inline-block; flex: none;
            width: 7px; height: 7px; margin-left: .4rem;
            border-radius: 50%; background: var(--su-orange1);
        }
        .sidenav .pp-subnav { padding: .35rem .45rem; margin: 0 .3rem 5px 0; color: #67748e; font-weight: 600; border-radius: .55rem; }
        /* Sorot terakhir tak perlu menyisakan jarak ke item berikutnya. */
        .sidenav .pp-submenu > .nav-item:last-child > .pp-subnav { margin-bottom: 0; }
        /* margin-right menimpa utilitas `me-2` (0.5rem) — merapatkan ikon ke teks
           memberi ruang cadangan supaya label terpanjang tak mepet ke tepi. */
        .sidenav .pp-subnav .icon { width: 26px; height: 26px; min-width: 26px; padding: 0; margin-right: .3rem !important; display: flex !important; align-items: center; justify-content: center; }
        .sidenav .pp-subnav .icon i { color: var(--su-orange1); font-size: .82rem; line-height: 1; }

        /*
        | Sub-menu sidebar — memperbaiki tiga hal sekaligus:
        |
        | 1. JARAK. Sub-menu dulu menempel ke menu induknya (hanya `my-1`),
        |    sehingga hierarkinya tak terbaca. Diberi napas atas & bawah.
        | 2. SOROT. Hover 7% dan aktif 12% oranye nyaris tak terlihat di latar
        |    putih. Dinaikkan, dan item aktif diberi batang oranye di tepi kiri
        |    supaya kentara mana yang sedang dibuka.
        | 3. LEBAR. Label panjang seperti "Dokumen Departemen" tak muat karena
        |    indentasi 1,5rem (`ms-3` + `ps-2`) memakan lebar teks. Indentasi
        |    dikecilkan dan hierarki kini dipegang GARIS PEMANDU vertikal, bukan
        |    ruang kosong — teks dapat ~14px lebih lebar TANPA perlu dipotong.
        |    Sorot hover pun jadi lebih lebar karena ikut melebar.
        |
        | Ellipsis di bawah hanya jaring pengaman bila suatu saat ada label yang
        | lebih panjang lagi; teks penuhnya tetap muncul sebagai tooltip.
        */
        .sidenav .pp-submenu {
            margin: .4rem 0 .65rem .7rem;
            padding-left: .4rem;
            border-left: 2px solid rgba(234, 88, 12, .18);
        }
        .sidenav .pp-subnav .nav-link-text {
            min-width: 0;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        .sidenav .navbar-nav > .nav-item { margin-bottom: 2px; }
        /* Perataan ikon sidebar: buang margin-bottom & float bawaan .icon-shape supaya
           setiap ikon sejajar vertikal dgn teksnya (simetris di item & sub-item). */
        .sidenav .nav-link .icon { margin-bottom: 0 !important; float: none !important; flex: 0 0 auto; }
        .sidenav .nav-link { display: flex; align-items: center; }
        .sidenav .pp-subnav .nav-link-text { font-size: .8rem; }
        .sidenav .pp-subnav:hover { background: rgba(234, 88, 12, .13); color: var(--su-orange1); }
        /* Jenis dokumen di luar profil akses (Fase 4c) — terlihat, tak bisa dipakai. */
        .sidenav .pp-subnav-mati { opacity: .45; cursor: not-allowed; }
        .sidenav .pp-subnav-mati:hover { background: transparent; color: #67748e; }
        .sidenav .pp-subnav.active {
            background: rgba(234, 88, 12, .17) !important;
            background-image: none !important;
            box-shadow: inset 3px 0 0 0 var(--su-orange1);   /* batang penanda di tepi kiri */
        }
        .sidenav .pp-subnav.active .nav-link-text { color: var(--su-orange1); font-weight: 700; }
        .sidenav .pp-subnav.active .icon { background-color: #fff !important; }
        .sidenav .pp-subnav.active .icon i { color: var(--su-orange1) !important; }   /* ikon tetap hidup saat aktif */

        /* Sidebar responsif: backdrop + bisa diklik di layar kecil */
        .pp-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 1039; }
        @media (min-width: 1200px) { .pp-backdrop { display: none !important; } }
        .sidenav { z-index: 1045; }
        /* Navbar ikut ter-scroll (bukan sticky) */
        .navbar-main { position: relative !important; top: auto !important; }

        /*
        | Kontrol topbar (hamburger, tema, lonceng, profil) — SATU kotak seragam.
        |
        | Dulu keempatnya tak sejajar karena tiga sebab bertumpuk:
        |   1. Hamburger & toggle tema memakai .btn, sedangkan lonceng & profil
        |      memakai <a>. Soft UI memberi `.btn{margin-bottom:1rem}` — di baris
        |      flex align-items-center, margin itu ikut dihitung sehingga kedua
        |      tombol terdorong NAIK ~.5rem terhadap dua tetangganya. (Penyakit
        |      yang sama sudah pernah ditambal .btn-input-h & .btn-field untuk
        |      baris form; ini kejadian ketiga, karena itu dijadikan kelas.)
        |   2. Ukuran ikon berbeda-beda: fs-3 (1.75rem) vs fs-4 (1.5rem).
        |   3. Avatar 36px sedangkan ikon cadangannya hanya setinggi teks, jadi
        |      tinggi baris BERUBAH tergantung pengguna punya foto atau tidak.
        |
        | Solusinya kotak 40x40 yang sama untuk semuanya: sejajar, simetris, dan
        | sekaligus memberi area sentuh yang layak.
        */
        .pp-navbtn {
            width: 40px; height: 40px; flex: 0 0 40px;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0; margin: 0 !important; border: 0; border-radius: .55rem;
            background: transparent; box-shadow: none !important;
            color: #67748e; text-decoration: none;
            transition: background-color .15s ease, color .15s ease;
        }
        .pp-navbtn:hover, .pp-navbtn:focus-visible { background: rgba(234, 88, 12, .1); color: var(--su-orange1); }
        .pp-navbtn i { font-size: 1.25rem; line-height: 1; }
        /*
        | Lencana jumlah notifikasi: ditambatkan ke kotak 40px, bukan ke glif.
        |
        | Selektor sengaja DUA kelas. Aturan `.badge{padding:.5em .75em}` di
        | bagian bawah berkas ini ditulis SESUDAH aturan ini; dengan spesifisitas
        | sama, yang belakangan menang — dan padding sebesar itu membuat lencana
        | menggembung sampai menutupi ikon loncengnya.
        */
        .pp-navbtn .pp-navbtn-lencana {
            top: 3px; right: 2px;
            min-width: 17px; height: 17px;
            padding: 0 4px;
            font-size: .62rem; font-weight: 700; line-height: 17px;
            display: inline-flex; align-items: center; justify-content: center;
            border: 2px solid #fff;
        }
        [data-bs-theme="dark"] .pp-navbtn .pp-navbtn-lencana { border-color: #1f2428; }
        /* Avatar: tinggi dikunci sama dengan kotak kontrol supaya tinggi baris
           tidak berubah antara pengguna berfoto dan tidak. */
        .pp-navavatar {
            width: 36px; height: 36px; flex: 0 0 36px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            overflow: hidden; background: rgba(234, 88, 12, .12); color: var(--su-orange1);
        }
        .pp-navavatar img { width: 100%; height: 100%; object-fit: cover; }
        .pp-navavatar i { font-size: 1.35rem; line-height: 1; }
        /* Blok nama+peran: kunci tinggi barisnya supaya pas 36px, sejajar avatar. */
        .pp-navuser-nama { font-size: .8rem; font-weight: 600; line-height: 1.15; }
        .pp-navuser-peran { font-size: .7rem; line-height: 1.15; text-transform: capitalize; }
        [data-bs-theme="dark"] .pp-navbtn { color: #a9b4c4; }
        [data-bs-theme="dark"] .pp-navbtn:hover { background: rgba(234, 88, 12, .18); color: var(--su-orange2); }
        .sidenav .section-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; opacity: .55; padding: .6rem 1rem .25rem; }
        .btn-pp { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); color: #fff; border: none; }
        .btn-pp:hover { color: #fff; opacity: .92; }
        /* Kartu/aksen gradasi palet Soft UI (Users/Clicks/Sales/Items) */
        .bg-su-orange { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)) !important; }
        .bg-su-blue   { background-image: linear-gradient(310deg, var(--su-info1), var(--su-info2)) !important; }
        .bg-su-yellow { background-image: linear-gradient(310deg, var(--su-warn1), var(--su-warn2)) !important; }
        .bg-su-pink   { background-image: linear-gradient(310deg, var(--su-danger1), var(--su-danger2)) !important; }
        .bg-su-dark   { background-image: linear-gradient(310deg, var(--su-dark1), var(--su-dark2)) !important; }
        /* Dua utilitas SEMANTIK — dipakai HANYA untuk menandai hasil, tak pernah
           sebagai hiasan. Bila keduanya mulai muncul di mana-mana, keduanya
           berhenti berarti "berhasil" dan "gagal". */
        .bg-su-hijau  { background-image: linear-gradient(310deg, var(--su-hijau1), var(--su-hijau2)) !important; }
        .bg-su-merah  { background-image: linear-gradient(310deg, var(--su-danger1), var(--su-danger2)) !important; }

        /* =====================================================================
        | JATAH WARNA — baca ini sebelum memberi warna pada komponen baru.
        |
        | Perbandingan yang dituju per halaman, kira-kira:
        |
        |   NETRAL  ~70%  putih & abu — permukaan kartu, latar, teks, tabel,
        |                 form, sidebar. Ini BADAN aplikasi. Kalau ragu, netral.
        |   ORANYE  ~20%  aksen & ajakan bertindak.
        |   HITAM   ~8%   jangkar. HEMAT: paling banyak SATU blok hitam besar
        |                 per halaman, plus kartu statistik selang-seling.
        |   HIJAU/  ~2%   HANYA penanda hasil. Bukan hiasan.
        |   MERAH
        |
        | ORANYE dipakai TEPAT di:
        |   1. Tombol aksi utama (.btn-pp) & tautan CTA (.pp-cta)
        |   2. Keadaan aktif — pil sidebar, kolom yang sedang diurutkan, hover
        |   3. Kartu statistik (berselang dengan hitam)
        |   4. Deret "dibuat" pada grafik & aksen ombak kartu sambutan
        |   5. Badge status yang sedang BERJALAN (ramp hangat di Document::STATUS_META)
        |
        | HITAM dipakai TEPAT di:
        |   1. Kartu sambutan — HANYA di mode gelap. Di mode terang ia krem
        |      hangat (token --pp-hero-*); balok hitam di atas halaman terang
        |      tak pernah dirancang, itu sisa desain yang cuma memikirkan gelap.
        |   2. Dua dari empat kartu statistik
        |   3. Ikon linimasa untuk peristiwa netral
        |   4. Badge status "Tidak Berlaku"
        |
        | HIJAU dipakai TEPAT di: badge "Berlaku", deret "berlaku" pada grafik,
        |   bilah cakupan >=80%, ikon linimasa "disetujui".
        | MERAH dipakai TEPAT di: badge "Ditolak", kartu "Dokumen Ditolak",
        |   bilah cakupan <50%, ikon linimasa penolakan/penghapusan.
        |
        | TIDAK ADA biru, ungu, merah muda, atau teal sebagai hiasan. Kalau
        | sebuah komponen baru "butuh warna lain supaya beda", yang sebenarnya
        | kurang adalah hierarki — perbaiki ukuran, tebal, atau jaraknya dulu.
        ===================================================================== */

        /* CTA teks berpanah — panahnya bergeser saat hover (icon-move-right). */
        .pp-cta {
            display: inline-flex; align-items: center; gap: .4rem;
            font-weight: 600; font-size: .8rem; text-decoration: none;
            color: var(--su-orange1);
        }
        .pp-cta i { transition: transform .2s cubic-bezier(.34, 1.61, .7, 1.3); }
        .pp-cta:hover { color: var(--su-orange1); }
        .pp-cta:hover i, .pp-cta:focus-visible i { transform: translateX(5px); }
        .pp-cta-terang { color: #fff; }
        .pp-cta-terang:hover { color: var(--su-orange2); }

        /* Tombol CTA di atas kartu sambutan — warnanya TERBALIK antar tema
           (terang: tombol gelap di atas krem; gelap: tombol putih di atas hitam),
           karena itu keduanya membaca token hero, bukan warna mentah. */
        .btn-pp-terang {
            background: var(--pp-hero-btn-bg); color: var(--pp-hero-btn-teks); border: none; font-weight: 600;
            margin-bottom: 0; box-shadow: 0 4px 12px rgba(0, 0, 0, .25);
        }
        .btn-pp-terang:hover { background: var(--pp-hero-btn-bg); color: var(--pp-hero-btn-aksen); }
        .btn-pp-garis {
            background: transparent; color: var(--pp-hero-teks); border: 1px solid var(--pp-hero-garis);
            font-weight: 600; margin-bottom: 0; box-shadow: none;
        }
        .btn-pp-garis:hover { background: var(--pp-hero-garis-isi); color: var(--pp-hero-teks); border-color: var(--pp-hero-teks); }

        /* Kartu sambutan, dengan ombak oranye di tepi kanan. Ombaknya ditulis
           inline sebagai SVG (bukan waves-white.svg 215 KB dari tema) supaya nol
           permintaan jaringan dan warnanya bisa ikut palet. Latar & teksnya
           bertukar lewat token hero di atas — lihat catatan di sana. */
        .pp-hero {
            position: relative; overflow: hidden; border: none;
            background-image: linear-gradient(310deg, var(--pp-hero-bg1), var(--pp-hero-bg2));
        }
        .pp-hero-ombak {
            position: absolute; inset: 0 0 0 auto; width: 46%; height: 100%;
            opacity: .9; pointer-events: none;
        }
        @media (max-width: 767.98px) { .pp-hero-ombak { display: none; } }

        /* Angka besar pada kartu statistik. */
        .pp-angka { font-size: 2rem; font-weight: 700; line-height: 1.1; letter-spacing: -.02em; }
        /* Delta arah — SATU-SATUNYA tempat hijau/merah dipakai di luar status. */
        .pp-delta { font-size: .72rem; font-weight: 600; display: inline-flex; align-items: center; gap: .2rem; }

        /* Dark theme */
        [data-bs-theme="dark"] body { background: #14171a !important; }
        /* Pengecualian HARUS memuat `bg-su-` juga. Utilitas gradasi tema ini
           bernama .bg-su-orange/blue/yellow/pink — bukan .bg-gradient — sehingga
           dulu keempat kartu statistik dashboard ikut tertimpa abu-abu di mode
           gelap dan kehilangan seluruh warnanya.

           `.pp-hero` ikut dikecualikan dengan alasan yang sama. Ia sebuah .card
           tanpa kedua kelas itu, jadi selama ini `background:#1f2428 !important`
           di sini MENGHAPUS gradasinya di mode gelap — `!important` selalu
           menang atas `background-image` biasa. Akibatnya kartu sambutan tak
           pernah benar-benar bergradasi di mode gelap, dan tanpa baris ini
           token --pp-hero-bg* pun akan mati di sana. */
        [data-bs-theme="dark"] .sidenav,
        [data-bs-theme="dark"] .card:not([class*='bg-gradient']):not([class*='bg-su-']):not(.pp-hero),
        [data-bs-theme="dark"] .navbar-main { background: #1f2428 !important; }
        [data-bs-theme="dark"] .sidenav .navbar-brand span { color: #fff; }

        /*
        | Sidebar mode gelap — SELURUH label menu praktis tak terbaca sebelumnya.
        |
        | Item tingkat atas mewarisi warna teks gelap bawaan Soft UI, dan
        | `.pp-subnav` mematok `color:#67748e`. Keduanya dirancang untuk latar
        | putih; di atas #1f2428 hanya item AKTIF (pil oranye) yang terbaca,
        | sisanya nyaris lenyap — navigasinya jadi tak terpakai.
        |
        | Dibatasi :not(.active) supaya tak menabrak dua aturan yang sudah ada:
        | teks putih pada pil oranye, dan teks oranye pada sub-item aktif.
        */
        [data-bs-theme="dark"] .sidenav .nav-link:not(.active),
        [data-bs-theme="dark"] .sidenav .nav-link:not(.active) .nav-link-text { color: #c3ccd8; }
        [data-bs-theme="dark"] .sidenav .section-label { color: #97a3b6; opacity: .9; }
        [data-bs-theme="dark"] .sidenav .pp-caret { color: #97a3b6; }
        /* Kotak ikon: putih pekat di sidebar gelap terlalu menyilaukan dan
           menarik perhatian melebihi labelnya sendiri. */
        [data-bs-theme="dark"] .sidenav .nav-link:not(.active) .icon { background-color: #2b323a !important; }
        [data-bs-theme="dark"] .sidenav .nav-link:not(.active) .icon i { color: var(--su-orange2); }
        [data-bs-theme="dark"] .sidenav .pp-submenu { border-left-color: rgba(234, 88, 12, .3); }

        @media (min-width: 1200px) { .main-content { margin-left: 17.125rem; } }
        /* Sidebar mobile: sembunyikan geser-kiri, tampil saat .pp-open (toggle hamburger).
           !important agar MENANG atas transform bawaan Soft UI (.g-sidenav-show .sidenav …)
           yang bila tidak, membuat hamburger seakan "tak berfungsi". */
        @media (max-width: 1199.98px) {
            .sidenav { transform: translateX(-110%) !important; transition: transform .2s ease; z-index: 1040; }
            .sidenav.pp-open { transform: translateX(0) !important; }
        }

        /* ===== Gulir TANPA batang gulir =====
           Isi kartu tetap bisa digulir (roda tetikus, sentuh, panah papan ketik,
           gulir-ke-fokus), tapi batangnya tak ikut menggambar garis di tepi
           kartu. Dipakai kartu Distribusi & Log Aktivitas di dashboard.
           Ketiga barisnya perlu semua: `scrollbar-width` untuk Firefox,
           `-ms-overflow-style` untuk Edge lama, `::-webkit-scrollbar` untuk
           Chrome/Edge sekarang. */
        .pp-gulir { overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .pp-gulir::-webkit-scrollbar { width: 0; height: 0; }

        /* ===== Gulir dengan batang MINIMALIS (spec v3 R3) =====
           Bedanya dengan .pp-gulir: batangnya ADA. Tabel Distribusi kepalanya
           dipaku (sticky) sementara badannya bergulir — tanpa batang gulir tak
           ada satu pun isyarat bahwa masih ada baris di bawah, dan kepala yang
           diam malah terbaca seperti tabel yang memang cuma sependek itu.
           6px, tanpa jalur, muncul lebih pekat saat kartunya disentuh tetikus. */
        .pp-gulir-tipis { overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(131, 146, 171, .35) transparent; }
        .pp-gulir-tipis::-webkit-scrollbar { width: 6px; height: 6px; }
        .pp-gulir-tipis::-webkit-scrollbar-track { background: transparent; }
        .pp-gulir-tipis::-webkit-scrollbar-thumb { background: rgba(131, 146, 171, .35); border-radius: 3px; }
        .pp-gulir-tipis:hover { scrollbar-color: rgba(131, 146, 171, .6) transparent; }
        .pp-gulir-tipis:hover::-webkit-scrollbar-thumb { background: rgba(131, 146, 171, .6); }

        /* ===== Animasi masuk halaman (spec v3 R6) =====
           Naik-perlahan murni CSS: nol JavaScript, nol pengamat perpotongan.
           Urutannya dari `--rise` (indeks) sehingga widget muncul berurutan
           alih-alih serempak.

           `backwards` WAJIB: tanpa itu widget tampil penuh dulu selama
           penundaannya baru melompat ke posisi awal animasinya.

           Dan HANYA `backwards`, bukan `both`. Bingkai akhir animasi menang
           atas kaskade biasa selama fill-mode masih memegangnya, sehingga
           `both` akan mengunci `transform: none` dan mematikan angkat-hover
           kartu statistik (.pp-tile-link). Dengan `backwards` elemen kembali
           ke gayanya sendiri begitu animasi usai — dan gaya itu memang tak
           punya transform sama sekali, yang justru lebih tegas daripada
           `translateY(0)`: nilai translate berapa pun, termasuk nol,
           menyisakan lapisan komposit yang dirasterkan sendiri dan membuat
           kanvas grafik di dalamnya buram. Itulah keluhan R2(a). */
        @keyframes ppNaik {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: none; }
        }
        .pp-naik {
            animation: ppNaik .34s cubic-bezier(.22, .61, .36, 1) backwards;
            animation-delay: calc(var(--rise, 0) * 60ms);
        }
        @media (prefers-reduced-motion: reduce) {
            .pp-naik { animation: none; }
        }

        /* ===== Pemilih rentang waktu (spec v3 R5) =====
           Bentuk SNEAT: kotak bergaris, ikon kalender di kiri, chevron di kanan
           yang DIPISAH garis tegak setinggi tombol. Dulu dua pemilih di kartu
           Overview berbeda rupa — satu tautan teks polos, satu tombol abu-abu
           tanpa isyarat apa pun bahwa ia bisa dibuka.

           Garis tegaknya digambar oleh ::after bawaan .dropdown-toggle: caret
           segitiga Bootstrap diganti glif chevron Bootstrap Icons, dan margin
           negatifnya (= padding-y tombol) yang meregangkan garis itu dari tepi
           atas sampai tepi bawah. */
        .btn-rentang {
            --bs-btn-padding-y: .3rem; --bs-btn-padding-x: .7rem;
            --bs-btn-font-size: .75rem;
            display: inline-flex; align-items: center; gap: .4rem;
            margin-bottom: 0; font-weight: 600; line-height: 1.4;
            color: var(--bs-body-color);
            background: transparent;
            border: 1px solid var(--bs-border-color);
            border-radius: .5rem;
            transition: color .15s ease, border-color .15s ease, background-color .15s ease;
        }
        .btn-rentang:hover, .btn-rentang.show {
            color: var(--su-orange1); border-color: var(--su-orange1);
            background: rgba(234, 88, 12, .07);
        }
        .btn-rentang > i { color: var(--su-orange1); font-size: .82rem; line-height: 1; }
        .btn-rentang.dropdown-toggle::after {
            content: '\F282';   /* bi-chevron-down */
            font-family: bootstrap-icons !important;
            display: flex; align-items: center;
            border: 0; border-left: 1px solid var(--bs-border-color);
            margin: -.3rem -.15rem -.3rem .15rem;
            padding: 0 0 0 .5rem;
            font-size: .6rem; vertical-align: baseline;
            transition: transform .2s ease;
        }
        .btn-rentang.show::after { transform: rotate(180deg); }
        .btn-rentang:hover::after, .btn-rentang.show::after { border-left-color: var(--su-orange1); }

        /* ===== Tabel gaya Soft UI (header uppercase kecil, tanpa bg abu) ===== */
        .table thead th, thead.table-light th {
            text-transform: uppercase; font-size: .62rem; letter-spacing: .04em;
            color: #8392ab; font-weight: 700; background: transparent !important;
            border-bottom: 1px solid #e9ecef; padding: .7rem 1rem;
        }
        .table > tbody > tr > td { border-bottom: 1px solid #f0f2f5; vertical-align: middle; padding: .7rem 1rem; }
        .table > tbody > tr:last-child > td { border-bottom: 0; }

        /* ===== Judul kolom yang bisa diurutkan (partials/_urut-th) =====
           Dua caret bertumpuk ala penjelajah berkas Windows: yang sedang berlaku
           pekat, pasangannya redup. Satu caret saja memaksa pengguna mengingat
           klik terakhirnya untuk tahu arah yang sedang aktif. */
        .pp-urut { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: .3rem; }
        .pp-urut:hover { color: var(--su-orange1); }
        .pp-urut-aktif { color: var(--su-orange1); }
        .pp-urut-caret { display: inline-flex; flex-direction: column; line-height: .5; font-size: .5rem; }
        .pp-urut-caret i { opacity: .22; }
        .pp-urut-caret i.on { opacity: 1; color: var(--su-orange1); }
        /*
        | ===== .badge-soft — SATU kelas untuk SEMUA label status & chip kode =====
        |
        | Spec dashboard v3 R1: tak ada lagi badge "bold". Sebelumnya satu tabel
        | bisa memuat empat bahasa sekaligus — gradasi pekat (.badge.bg-success),
        | tint Bootstrap (.bg-*-subtle .text-*-emphasis), chip putih bergaris
        | (.bg-light), dan .pp-status milik badge status. Empat rupa untuk satu
        | jenis benda, dan yang paling nyaring justru chip kode departemen yang
        | paling tak bermakna.
        |
        | Bentuknya di sini; RONA-nya datang dari dua variabel saja:
        |   --soft-bg  latar tint
        |   --soft-fg  warna teks yang sudah digelapkan sampai terbaca
        | Varian di bawah cuma menyetel dua variabel itu. Badge status
        | (partials/_badge-status) menyetelnya inline dari Document::STATUS_META,
        | jadi palet status tetap punya SATU sumber dan tak disalin ke sini.
        |
        | Teks gelapnya bukan tebakan: dihitung dengan rumus yang sama seperti di
        | partial itu (turunkan luminansi ke ambang .33), lalu diuji BadgeStatusTest.
        */
        .badge-soft {
            --soft-bg: rgba(100, 116, 139, .12);
            --soft-fg: #495566;
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .35rem .7rem; border-radius: .5rem;
            font-size: .75rem; font-weight: 600; line-height: 1;
            white-space: nowrap; vertical-align: middle;
            border: 1px solid transparent;
            background-color: var(--soft-bg);
            color: var(--soft-fg);
        }
        .badge-soft i { font-size: .8rem; line-height: 1; }
        a.badge-soft { text-decoration: none; }
        a.badge-soft:hover { filter: brightness(.94); color: var(--soft-fg); }

        .badge-soft-success   { --soft-bg: rgba(34, 197, 94, .12);  --soft-fg: #126b33; }
        .badge-soft-danger    { --soft-bg: rgba(239, 68, 68, .12);  --soft-fg: #c03636; }
        .badge-soft-warning   { --soft-bg: rgba(245, 158, 11, .12); --soft-fg: #7c5005; }
        .badge-soft-info      { --soft-bg: rgba(14, 165, 233, .12); --soft-fg: #08648e; }
        .badge-soft-secondary { --soft-bg: rgba(100, 116, 139, .12); --soft-fg: #495566; }
        .badge-soft-dark      { --soft-bg: rgba(39, 39, 42, .12);   --soft-fg: #27272a; }
        .badge-soft-primary   { --soft-bg: rgba(234, 88, 12, .12);  --soft-fg: #ad4108; }
        /* `light` tetap dikenali supaya blade yang menghitung nama warna
           Bootstrap tak perlu memetakan ulang — rupanya = netral. */
        .badge-soft-light     { --soft-bg: rgba(100, 116, 139, .09); --soft-fg: #67748e; }

        /* Mode gelap: tint 12% lenyap di atas permukaan #1f2428, dan teks yang
           sudah digelapkan untuk latar putih jadi terlalu pekat. Tint dinaikkan,
           teks dicerahkan 55% ke arah putih (aturan yang sama dengan partial). */
        [data-bs-theme="dark"] .badge-soft            { --soft-bg: rgba(100, 116, 139, .22); --soft-fg: #b9c0ca; }
        [data-bs-theme="dark"] .badge-soft-success    { --soft-bg: rgba(34, 197, 94, .22);  --soft-fg: #9be4b6; }
        [data-bs-theme="dark"] .badge-soft-danger     { --soft-bg: rgba(239, 68, 68, .22);  --soft-fg: #f7aaaa; }
        [data-bs-theme="dark"] .badge-soft-warning    { --soft-bg: rgba(245, 158, 11, .22); --soft-fg: #fad391; }
        [data-bs-theme="dark"] .badge-soft-info       { --soft-bg: rgba(14, 165, 233, .22); --soft-fg: #92d6f5; }
        [data-bs-theme="dark"] .badge-soft-secondary  { --soft-bg: rgba(100, 116, 139, .22); --soft-fg: #b9c0ca; }
        [data-bs-theme="dark"] .badge-soft-dark       { --soft-bg: rgba(160, 160, 168, .18); --soft-fg: #c9c9cf; }
        [data-bs-theme="dark"] .badge-soft-primary    { --soft-bg: rgba(234, 88, 12, .22);  --soft-fg: #f5b391; }
        [data-bs-theme="dark"] .badge-soft-light      { --soft-bg: rgba(100, 116, 139, .16); --soft-fg: #a6b0bf; }

        /* Badge status gaya Soft UI (gradient pill) — global, tanpa ubah markup.
           SISA pemakainya tinggal yang memang bukan status: lencana angka pada
           lonceng, pil langkah wizard, penomoran baris repeatable. */
        .badge { padding: .5em .75em; font-weight: 600; border-radius: .6rem; }
        .badge.bg-success { background-image: linear-gradient(310deg, #22c55e, #16a34a) !important; }
        .badge.bg-danger  { background-image: linear-gradient(310deg, #ef4444, #dc2626) !important; }
        .badge.bg-warning { background-image: linear-gradient(310deg, #f59e0b, #d97706) !important; color: #fff !important; }
        .badge.bg-info    { background-image: linear-gradient(310deg, #0ea5e9, #0284c7) !important; color: #fff !important; }
        .badge.bg-primary { background-image: linear-gradient(310deg, var(--pp-teal), var(--pp-navy)) !important; }
        .badge.bg-secondary { background-image: linear-gradient(310deg, #64748b, #475569) !important; }
        .badge.bg-dark    { background-image: linear-gradient(310deg, #334155, #1e293b) !important; }
        .form-control, .form-select, .input-group-text { border-radius: .6rem; }
        /* Tombol filter sejajar dgn input/dropdown. Soft UI memberi .btn{margin-bottom:1rem};
           pada baris align-items-end margin itu ikut dihitung -> tombol terdorong naik 16px.
           Nol-kan marginnya + samakan tinggi dgn .form-control-sm. */
        /* Soft UI memberi `.btn{--bs-btn-padding-x:1.5rem}`; di kolom col-md-2 yang
           dibagi berdua dengan tombol Reset, label "Filter" jadi TERBUNGKUS ke
           baris kedua sehingga tombolnya lebih tinggi daripada input di sebelahnya.
           Padding-x dikecilkan + dilarang membungkus. Berlaku untuk kesembilan
           halaman berfilter sekaligus, karena kelas ini dipakai bersama. */
        .btn-input-h {
            --bs-btn-padding-y: .25rem; --bs-btn-padding-x: .75rem;
            --bs-btn-line-height: 1.5; --bs-btn-font-size: .75rem;
            margin-bottom: 0; white-space: nowrap;
            display: inline-flex; align-items: center; justify-content: center; gap: .3rem;
            min-height: calc(1.5em + .5rem + 2px);   /* = tinggi .form-control-sm */
        }
        /* Tombol ikon di samping field form (+ / − / ×): kotak, tinggi mengikuti
           input di sampingnya (align-self stretch dlm flex gap-2), tanpa
           margin-bottom bawaan Soft UI — sejajar & berjarak dari kotak field. */
        .btn-field { width: 2.7rem; min-width: 2.7rem; padding: 0; display: inline-flex; align-items: center; justify-content: center; align-self: stretch; margin-bottom: 0; flex: 0 0 auto; }
        .alert { border-radius: .9rem; border: none; }
        .btn-pp { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); color: #fff; }

        [data-bs-theme="dark"] .table thead th { color: #8b98b3; border-color: #2b3138; }
        [data-bs-theme="dark"] .table > tbody > tr > td { border-color: #2b3138; }
        [data-bs-theme="dark"] .bg-white { background-color: #1f2428 !important; }
        /* KECUALI di dalam kartu bergradasi: lingkaran ikon di sana memang harus
           tetap PUTIH — latarnya oranye/hitam pekat, bukan permukaan halaman.
           Tanpa pengecualian ini ikonnya jadi gelap-di-atas-gelap dan lenyap. */
        [data-bs-theme="dark"] .card[class*="bg-su-"] .bg-white { background-color: #fff !important; }
        /* Kartu hitam di halaman gelap kehilangan tepinya — nyaris tak terlihat
           sebagai kartu. Diberi garis tepi tipis supaya bentuknya tetap terbaca. */
        [data-bs-theme="dark"] .card.bg-su-dark { border: 1px solid #3a424c !important; }
        [data-bs-theme="dark"] .text-dark { color: #e6e9ee !important; }

        /*
        | Tambalan mode gelap yang selama ini terlewat.
        |
        | 1. Chip `badge bg-light text-dark` (kode JENIS & DEPT di semua tabel)
        |    NYARIS TAK TERBACA: aturan .text-dark di atas sudah membalik teksnya
        |    jadi terang, tetapi .bg-light tetap putih — terang di atas terang.
        |    Ini bukan soal selera, kodenya benar-benar hilang.
        | 2. Label form (.form-label) mewarisi warna gelap, jadi judul kolom
        |    penyaring seperti "Cari (nomor / judul)" lenyap ke latar.
        | 3. Kotak isian tetap putih menyala, satu-satunya bidang terang di
        |    halaman gelap.
        */
        [data-bs-theme="dark"] .badge.bg-light {
            background-color: #2b323a !important;
            border-color: #3a424c !important;
            color: #dbe2ea !important;
        }
        [data-bs-theme="dark"] .form-label,
        [data-bs-theme="dark"] .form-check-label { color: #c3ccd8; }
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select,
        [data-bs-theme="dark"] .input-group-text {
            background-color: #2b323a; border-color: #3a424c; color: #e6e9ee;
        }
        [data-bs-theme="dark"] .form-control::placeholder { color: #8b98b3; }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background-color: #2b323a; color: #e6e9ee; border-color: var(--su-orange1);
        }
        /* Panah kustom .form-select ditanam sbg SVG gelap oleh Soft UI — di latar
           gelap ia lenyap. Dibalik jadi terang. */
        [data-bs-theme="dark"] .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23c3ccd8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        }
        [data-bs-theme="dark"] .btn-light {
            background-color: #2b323a; border-color: #3a424c; color: #dbe2ea;
        }
        [data-bs-theme="dark"] .card-footer.bg-white { background-color: #1f2428 !important; }

        /*
        | Kolom Aksi (PLAN C §C3): MENU AKSI menurun yang melayang di atas
        | halaman.
        |
        | `position: fixed`, koordinatnya dari komponen (resources/views/
        | components/aksi.blade.php). Bukan `absolute`: <td>-nya berada di dalam
        | .table-responsive yang ber-overflow, dan menu yang dibuka pada baris
        | TERAKHIR akan terpotong tepi bawah kartu.
        |
        | Seluruh baris menu rata kiri, selebar menu, dengan ikon selebar sama —
        | itulah yang membuat deretnya terbaca sebagai satu daftar, bukan
        | kumpulan tombol yang kebetulan bertumpuk.
        */
        .pp-aksi { position: relative; display: inline-block; }
        /* Pemicu berisi satu ikon; Soft UI memberi .btn padding-x lebar yang di
           sini cuma jadi ruang kosong. */
        .pp-aksi-pemicu { padding-left: .55rem; padding-right: .55rem; }
        .pp-aksi-menu {
            position: fixed;
            z-index: 1045;              /* di atas .card & sticky header, di bawah modal */
            min-width: 12.5rem;
            display: flex; flex-direction: column; gap: .1rem;
            padding: .35rem;
            text-align: left;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: .75rem;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .15);
        }
        /* Form pembungkus tombol memakai .d-inline di pemanggil (warisan deretan
           mendatar); di dalam menu ia harus jadi blok agar tombolnya selebar menu. */
        .pp-aksi-menu form { display: block !important; margin: 0; }
        /*
        | Tiap aksi jadi satu BARIS DAFTAR, bukan tombol.
        |
        | Sifatnya ditulis sebagai properti sungguhan — bukan lewat `--bs-btn-*`
        | seperti percobaan pertama. Soft UI menembus variabel itu dengan dua
        | deklarasi langsung:
        |     .btn-warning,.btn-danger,…{color:#fff}
        |     .btn-warning:hover{background-color:#eab308}
        | Akibatnya tombol Revisi tak terlihat sama sekali sampai disorot (putih
        | di atas putih), lalu menyala kuning pekat. Selektor di sini sengaja
        | menyebut :hover/:focus/:active supaya menang atas keduanya.
        |
        | `margin: 0` juga bukan hiasan: Soft UI memberi `.btn{margin-bottom:1rem}`,
        | dan itulah yang membuat jarak antarbaris menu menganga.
        */
        .pp-aksi-menu .btn,
        .pp-aksi-menu .btn:hover,
        .pp-aksi-menu .btn:focus,
        .pp-aksi-menu .btn:active {
            display: flex; align-items: center; gap: .6rem;
            width: 100%;
            margin: 0;
            padding: .5rem .65rem;
            border: 0;
            border-radius: .5rem;
            font-size: .8125rem; font-weight: 500; letter-spacing: 0;
            text-align: left;
            color: var(--pp-aksi-fg, var(--bs-body-color));
            background-color: transparent;
            background-image: none;
            box-shadow: none;
        }
        .pp-aksi-menu .btn:hover,
        .pp-aksi-menu .btn:focus { background-color: rgba(100, 116, 139, .1); }
        .pp-aksi-menu .btn:active { background-color: rgba(100, 116, 139, .16); }
        /* Hanya aksi MERUSAK yang berwarna: menu yang enam barisnya enam warna
           terbaca sebagai enam benda berbeda. */
        .pp-aksi-menu .btn-danger,
        .pp-aksi-menu .btn-outline-danger { --pp-aksi-fg: #c03636; }
        /* Ikon selebar sama = teksnya mulai pada satu garis lurus. */
        .pp-aksi-menu .btn i { flex: none; width: 1rem; font-size: .9rem; text-align: center; opacity: .75; }
        [data-bs-theme="dark"] .pp-aksi-menu {
            background: #1f2428; border-color: #3a424c;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .5);
        }
        [data-bs-theme="dark"] .pp-aksi-menu .btn:hover,
        [data-bs-theme="dark"] .pp-aksi-menu .btn:focus { background-color: rgba(148, 163, 184, .16); }
        [data-bs-theme="dark"] .pp-aksi-menu .btn:active { background-color: rgba(148, 163, 184, .24); }
        [data-bs-theme="dark"] .pp-aksi-menu .btn-danger,
        [data-bs-theme="dark"] .pp-aksi-menu .btn-outline-danger { --pp-aksi-fg: #f7aaaa; }

        /*
        | Alert: setint .badge-soft, bukan gradasi pekat.
        |
        | soft-ui-dashboard.min.css menimpa SETIAP .alert-* dengan
        | `linear-gradient(310deg, ...)` + teks putih. Hasilnya pita paling
        | nyaring di layar padahal isinya cuma "tersimpan". Rona & teks di sini
        | mengambil nilai yang SAMA PERSIS dengan .badge-soft di atas, jadi
        | pesan dan lencana status berbicara dengan satu suara.
        */
        .alert {
            --soft-bg: rgba(100, 116, 139, .12);
            --soft-fg: #495566;
            background-image: none;
            background-color: var(--soft-bg);
            color: var(--soft-fg);
            font-size: .875rem;
            padding: .8rem 1rem;
        }
        .alert-success { --soft-bg: rgba(34, 197, 94, .12);  --soft-fg: #126b33; }
        .alert-danger  { --soft-bg: rgba(239, 68, 68, .12);  --soft-fg: #c03636; }
        .alert-warning { --soft-bg: rgba(245, 158, 11, .12); --soft-fg: #7c5005; }
        .alert-info    { --soft-bg: rgba(14, 165, 233, .12); --soft-fg: #08648e; }
        .alert-primary { --soft-bg: rgba(234, 88, 12, .12);  --soft-fg: #9a3412; }
        .alert-secondary, .alert-light { --soft-bg: rgba(100, 116, 139, .12); --soft-fg: #495566; }
        .alert-dark    { --soft-bg: rgba(39, 39, 42, .12);   --soft-fg: #27272a; }
        .alert .alert-link, .alert strong { color: inherit; }
        /* Tanda silangnya bawaan soft-ui berupa SVG PUTIH — tak terlihat di atas
           latar tint. Diwarnai ulang mengikuti teks alertnya. */
        .alert .btn-close {
            background-image: none;
            opacity: .55;
            padding: .9rem;
            display: flex; align-items: center; justify-content: center;
        }
        .alert .btn-close::before {
            content: "×"; font-size: 1.25rem; line-height: 1; color: currentColor;
        }
        [data-bs-theme="dark"] .alert-success { --soft-bg: rgba(34, 197, 94, .22);  --soft-fg: #9be4b6; }
        [data-bs-theme="dark"] .alert-danger  { --soft-bg: rgba(239, 68, 68, .22);  --soft-fg: #f7aaaa; }
        [data-bs-theme="dark"] .alert-warning { --soft-bg: rgba(245, 158, 11, .22); --soft-fg: #fad391; }
        [data-bs-theme="dark"] .alert-info    { --soft-bg: rgba(14, 165, 233, .22); --soft-fg: #92d6f5; }
        [data-bs-theme="dark"] .alert-primary { --soft-bg: rgba(234, 88, 12, .22);  --soft-fg: #f5b391; }
        [data-bs-theme="dark"] .alert,
        [data-bs-theme="dark"] .alert-secondary,
        [data-bs-theme="dark"] .alert-light,
        [data-bs-theme="dark"] .alert-dark { --soft-bg: rgba(100, 116, 139, .22); --soft-fg: #b9c0ca; }

        /*
        | ===== RICH TEXT (Quill 2 "snow") — kolom Deskripsi Aktivitas =========
        |
        | Quill mengurus PERILAKUnya; yang di bawah ini hanya menyetel RUPANYA
        | supaya sekotak dengan .form-control di sekelilingnya — radius .6rem,
        | border --bs-border-color, cincin fokus oranye PPA, huruf Poppins.
        | Tanpa ini editor terbaca seperti tempelan dari situs lain di tengah
        | form Soft UI.
        |
        | SELURUH aturan diawali `.pp-rt` — dua alasan: (a) ia menang atas CSS
        | bawaan Quill tanpa `!important` sebiji pun, apa pun urutan muatnya;
        | (b) halaman lain yang kelak memakai Quill tak ikut terbawa gaya ini
        | tanpa diminta.
        */
        .pp-rt .ql-toolbar.ql-snow,
        .pp-rt .ql-container.ql-snow { border-color: var(--bs-border-color); }
        .pp-rt .ql-toolbar.ql-snow {
            border-top-left-radius: .6rem; border-top-right-radius: .6rem;
            background: var(--bs-tertiary-bg); padding: .3rem .45rem;
        }
        .pp-rt .ql-container.ql-snow {
            border-bottom-left-radius: .6rem; border-bottom-right-radius: .6rem;
            font-family: 'Poppins', sans-serif; font-size: .875rem;
            background: var(--bs-body-bg);
        }
        .pp-rt .ql-editor { min-height: 9rem; padding: .7rem .9rem; line-height: 1.65; color: var(--bs-body-color); }
        /* Placeholder Quill miring & pucat sekali; disamakan dgn ::placeholder
           .form-control supaya tak terbaca sbg teks yang sudah diketik. */
        .pp-rt .ql-editor.ql-blank::before {
            color: var(--bs-secondary-color); font-style: normal; left: .9rem; right: .9rem;
        }

        /* Tombol toolbar. Ikonnya BOOTSTRAP ICONS (ditukar di edit.blade.php),
           bukan SVG bawaan Quill — sebahasa dgn seluruh aplikasi, CLAUDE.md §13. */
        .pp-rt .ql-toolbar.ql-snow button {
            width: 1.9rem; height: 1.9rem; padding: 0; border-radius: .45rem;
            display: inline-flex; align-items: center; justify-content: center;
            color: var(--bs-secondary-color);
            transition: background-color .15s ease, color .15s ease;
        }
        .pp-rt .ql-toolbar.ql-snow button i { font-size: .95rem; line-height: 1; }
        .pp-rt .ql-toolbar.ql-snow button:hover { background: rgba(234, 88, 12, .1); color: var(--su-orange1); }
        .pp-rt .ql-toolbar.ql-snow button.ql-active { background: rgba(234, 88, 12, .16); color: var(--su-orange1); }
        /* Quill mewarnai ikonnya lewat stroke/fill SVG (#444 → #06c saat aktif).
           Ikon kita HURUF, jadi warnanya ikut `color`; aturan itu dinolkan supaya
           tak ada sisa biru bila kelak ada tombol yang masih ber-SVG. */
        .pp-rt .ql-snow .ql-stroke { stroke: currentColor; }
        .pp-rt .ql-snow .ql-fill { fill: currentColor; }

        /* Pemisah kelompok tombol — menggantikan jarak kosong bawaan Quill. */
        .pp-rt .ql-toolbar.ql-snow .ql-formats {
            margin-right: .4rem; padding-right: .4rem; border-right: 1px solid var(--bs-border-color);
        }
        .pp-rt .ql-toolbar.ql-snow .ql-formats:last-child { border-right: 0; margin-right: 0; padding-right: 0; }

        /* Fokus: cincin yang sama dgn .form-control:focus, dipasang di PEMBUNGKUS
           karena yang menerima fokus sesungguhnya .ql-editor di dalamnya. */
        .pp-rt { border-radius: .6rem; transition: box-shadow .15s ease; }
        .pp-rt:focus-within { box-shadow: 0 0 0 .2rem rgba(234, 88, 12, .18); }
        .pp-rt:focus-within .ql-toolbar.ql-snow,
        .pp-rt:focus-within .ql-container.ql-snow { border-color: var(--su-orange1); }

        /* Ditandai ppValidateRequired — sepadan dgn .is-invalid pada input biasa. */
        .pp-rt-invalid .ql-toolbar.ql-snow,
        .pp-rt-invalid .ql-container.ql-snow { border-color: var(--bs-danger); }

        /*
        | Isi editor & tampilan BACA (halaman Tinjau) memakai aturan yang SAMA
        | lewat .pp-rt-isi, supaya yang dilihat penyusun = yang dilihat peninjau.
        | Angkanya sengaja bukan salinan angka cetak: di layar satuannya rem &
        | px, di PDF pt — yang dijaga sama adalah BENTUKNYA (kutipan menjorok &
        | miring, gambar tak melebihi lebar kolom).
        */
        .pp-rt .ql-editor blockquote, .pp-rt-isi blockquote {
            border-left: 3px solid var(--su-orange1); padding-left: .8rem; margin-left: 0;
            font-style: italic; color: var(--bs-secondary-color);
        }
        /* Gambar SELALU sebaris sendiri — tak pernah berdampingan dgn kalimat.
           Blot `image` Quill bersifat inline, jadi tanpa display:block ia duduk
           di samping teks; di PDF ia memang sudah jadi baris tabel tersendiri,
           dan editor harus memperlihatkan hal yang sama supaya penyusun tak
           terkejut saat mencetak. */
        .pp-rt .ql-editor img, .pp-rt-isi img {
            display: block; max-width: 100%; height: auto;
            margin: .45rem 0; border-radius: .4rem;
        }
        .pp-rt-isi { font-size: .875rem; line-height: 1.65; }
        .pp-rt-isi p { margin-bottom: .35rem; }
        .pp-rt-isi ol, .pp-rt-isi ul { margin-bottom: .35rem; padding-left: 1.4rem; }
        .pp-rt-isi > :last-child { margin-bottom: 0; }

        /* Gelap: Quill snow menuliskan warnanya sendiri (#fff/#ccc/#444), jadi
           ketiga permukaannya harus disebut ulang — kalau tidak, editor menyala
           putih di tengah halaman gelap. */
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow { background: #232a31; }
        [data-bs-theme="dark"] .pp-rt .ql-container.ql-snow { background: #2b323a; }
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow,
        [data-bs-theme="dark"] .pp-rt .ql-container.ql-snow,
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow .ql-formats { border-color: #3a424c; }
        [data-bs-theme="dark"] .pp-rt .ql-editor { color: #e6e9ee; }
        [data-bs-theme="dark"] .pp-rt .ql-editor.ql-blank::before { color: #8b98b3; }
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow button { color: #b9c0ca; }
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow button:hover,
        [data-bs-theme="dark"] .pp-rt .ql-toolbar.ql-snow button.ql-active {
            background: rgba(234, 88, 12, .2); color: var(--su-orange2);
        }
    </style>
    @stack('styles')
</head>
<body class="g-sidenav-show bg-gray-100">
@php
    $user = auth()->user();
    $nav = fn ($pattern) => request()->routeIs($pattern) ? 'active' : '';
    // Jenis yang AKTIF (PLAN-AKSES-v8 Fase 5b). Satu query untuk seluruh
    // sidebar: dua submenu di bawah menyaring daftar hardcoded-nya dengan ini,
    // sehingga jenis yang dimatikan Admin di Master Data benar-benar hilang
    // dari menu — bukan sekadar dari dropdown saringan.
    $jenisAktif = \App\Models\DocumentType::kode();
    $allDepts = \App\Models\Department::orderBy('code')->get(['id', 'code', 'name']);
    $canViewAll = $user->can('document.view_all');
    // Jenis dokumen untuk submenu "Status Dokumen" + ikonnya. Ikon di sidebar
    // sengaja berbeda dari DocumentType::RUPA (yang dipakai kartu & donat):
    // di sini ukurannya 13px, jadi glif yang lebih sederhana lebih terbaca.
    $jenisMenu = ['SOP' => 'bi-file-earmark-text', 'IK' => 'bi-file-earmark-ruled', 'SP' => 'bi-sliders2', 'JSA' => 'bi-shield-exclamation', 'FK' => 'bi-ui-checks-grid', 'PX' => 'bi-box-arrow-in-down'];
    // Ikon khas per departemen — disamakan dengan lambang resmi 7 departemen PT PPA.
    // Bootstrap Icons tak punya ikon pabrik, jadi PLANT memakai gedung-bergerigi
    // yang paling mendekati; sisanya cocok satu-satu dengan lambang resminya.
    $deptIconMap = [
        'PRODUKSI'    => 'bi-truck',              // truk angkut
        'ENGINEERING' => 'bi-gear-fill',          // gerigi
        'PLANT'       => 'bi-building-fill-gear', // pabrik
        'SHE'         => 'bi-shield-fill-check',  // perisai
        'HCGA'        => 'bi-people-fill',        // sekelompok orang
        'FAW-SCM'     => 'bi-share-fill',         // simpul rantai pasok
        'ICTMD'       => 'bi-hdd-network-fill',   // perangkat & jaringan
    ];
    $deptIcon = fn ($code) => $deptIconMap[strtoupper($code)] ?? 'bi-building';
    // Angka badge sidebar — SATU panggilan untuk seluruh menu. Layout ini
    // dirender di SETIAP halaman, jadi menghitung per item menu berarti tujuh
    // query yang berulang selamanya.
    $antrean = app(\App\Services\AntreanTugas::class)->untuk($user);
    $jml = fn ($kunci) => $antrean[$kunci]['jumlah'] ?? 0;
@endphp

<div x-data="{ sidebar: false,
    docMenu: {{ request()->routeIs('documents.create') ? 'true' : 'false' }},
    statusMenu: {{ request()->routeIs('documents.index') || request()->routeIs('documents.staffStatus') ? 'true' : 'false' }},
    berlakuMenu: {{ request()->routeIs('documents.published') || request()->routeIs('documents.obsolete') || request()->routeIs('documents.distribution') || request()->routeIs('nonaktif.*') ? 'true' : 'false' }},
    staffMenu: {{ request()->routeIs('documents.staffStatus') ? 'true' : 'false' }},
    logMenu: {{ request()->routeIs('log.*') ? 'true' : 'false' }},
    infoMenu: {{ request()->routeIs('informasi.*') ? 'true' : 'false' }} }">
    {{-- Backdrop saat sidebar terbuka di layar kecil (klik untuk menutup) --}}
    <div class="pp-backdrop" x-show="sidebar" x-cloak @click="sidebar = false" x-transition.opacity></div>
    {{-- ===== Sidebar (Soft UI sidenav) ===== --}}
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-white"
           data-color="primary" :class="sidebar && 'pp-open'">
        <div class="sidenav-header text-center py-3 px-3">
            {{-- DUA berkas logo, bukan satu yang difilter: tema bisa ditukar tanpa
                 memuat ulang halaman, jadi keduanya ikut halaman sejak awal dan
                 CSS yang memilih — nol JS, nol kedipan saat tema berganti.
                 Keduanya berukuran SAMA (4000×2000), ditumpuk dan disilang-pudar;
                 jatah tingginya satu nilai saja (lihat .pp-logo di blok style).
                 Kalau salah satu aset diganti, samakan rasionya — beda rasio akan
                 membuat logo tampak melompat ukuran saat tema ditukar. --}}
            <a class="navbar-brand m-0 d-block" href="{{ route('dashboard') }}">
                <img class="pp-logo pp-logo-terang" src="{{ asset('images/logo-web.png') }}" alt="SmartPro">
                <img class="pp-logo pp-logo-gelap" src="{{ asset('images/logodarkmode.png') }}" alt="SmartPro">
            </a>
        </div>
        <hr class="horizontal dark mt-0 mb-2">
        <div class="collapse navbar-collapse w-auto h-auto" style="overflow-y:auto;max-height:calc(100vh - 8rem)">
            <ul class="navbar-nav">
                @php
                    // Lencana antrean. Hanya dirender bila ada isinya: menu tanpa
                    // tugas tak boleh menampilkan angka 0 — nol bukan kabar, cuma
                    // bising. Keluarannya dicetak {!! !!}, jadi angkanya di-cast.
                    $lencana = fn ($badge) => (int) $badge > 0
                        ? '<span class="badge-soft badge-soft-danger ms-auto">'.(int) $badge.'</span>'
                        : '';
                    // Titik penanda menu INDUK: ada pekerjaan di salah satu
                    // sub-menunya. Angkanya tetap milik sub-menu — induk cukup
                    // memberi tahu bahwa ada sesuatu di balik menu yang tertutup.
                    // Variadic supaya menu berisi beberapa sub-menu berangka
                    // kelak cukup menyebut semuanya, tanpa menjumlahkan sendiri.
                    $titik = fn (int ...$angka) => array_sum($angka) > 0
                        ? '<span class="pp-titik" title="Ada yang perlu dikerjakan"></span>'
                        : '';
                    $item = function ($route, $label, $icon, $active, $badge = 0) use ($lencana) {
                        return '<li class="nav-item"><a class="nav-link '.$active.'" href="'.$route.'">'
                            .'<div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi '.$icon.'"></i></div>'
                            .'<span class="nav-link-text ms-1">'.$label.'</span>'.$lencana($badge).'</a></li>';
                    };
                    // Item sub-menu (pembungkus ikon kotak) — dipakai dropdown jenis/dept.
                    $subItem = function ($url, $label, $icon, $active = '', $badge = 0) use ($lencana) {
                        // title = jaring pengaman: label yang terlalu panjang ter-ellipsis,
                        // teks penuhnya tetap terbaca lewat tooltip.
                        return '<li class="nav-item"><a class="nav-link pp-subnav d-flex align-items-center '.$active.'" href="'.$url.'" title="'.e($label).'">'
                            .'<div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi '.$icon.'"></i></div>'
                            .'<span class="nav-link-text">'.$label.'</span>'.$lencana($badge).'</a></li>';
                    };
                @endphp
                {!! $item(route('dashboard'), 'Dashboard', 'bi-speedometer2', $nav('dashboard')) !!}

                <div class="section-label">Dokumen</div>
                @can('document.create')
                    {{-- Dokumen Baru: dropdown jenis. FK & PX ada di daftar yang SAMA
                         (keputusan pemilik BARU-1): keduanya jenis dokumen penuh, hanya
                         cara mengisinya yang berbeda — unggah berkas, bukan wizard. --}}
                    <li class="nav-item">
                        <a class="nav-link {{ $nav('documents.create') }}" href="#" @click.prevent="docMenu = !docMenu" :aria-expanded="docMenu.toString()">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-file-earmark-plus"></i></div>
                            <span class="nav-link-text ms-1">Dokumen Baru</span>
                            <i class="bi bi-chevron-down ms-auto pp-caret" :class="docMenu && 'pp-caret-open'"></i>
                        </a>
                        <div x-show="docMenu" x-cloak x-transition.opacity>
                            <ul class="nav flex-column pp-submenu">
                                @foreach (['SOP' => ['Standard Operating Procedure', 'bi-file-earmark-text'], 'IK' => ['Instruksi Kerja', 'bi-file-earmark-ruled'], 'SP' => ['Standar Parameter', 'bi-sliders2'], 'JSA' => ['Job Safety Analysis', 'bi-shield-exclamation'], 'FK' => ['Formulir Kerja (unggah berkas)', 'bi-ui-checks-grid'], 'PX' => ['Prosedur External (unggah berkas)', 'bi-box-arrow-in-down']] as $code => $meta)
                                    {{-- Jenis NONAKTIF (Master Data) dilewati sama sekali, bukan
                                         ditampilkan terkunci: "terkunci" di menu ini sudah punya arti
                                         lain — di luar profil akses, mintalah ke Admin. Jenis yang
                                         dimatikan tak bisa diminta siapa pun. --}}
                                    @continue (! in_array($code, $jenisAktif, true))
                                    @php
                                        $subActive = request()->routeIs('documents.create') && strtoupper(request('type', 'SOP')) === $code;
                                        $boleh = $user->bolehBuatJenis($code);
                                    @endphp
                                    <li class="nav-item">
                                        @if ($boleh)
                                            <a class="nav-link pp-subnav d-flex align-items-center {{ $subActive ? 'active' : '' }}" href="{{ route('documents.create', ['type' => $code]) }}" title="{{ $meta[0] }}">
                                                <div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi {{ $meta[1] }}"></i></div>
                                                <span class="nav-link-text">{{ $code }}</span>
                                            </a>
                                        @else
                                            {{-- Jenis di luar profil akses (PLAN-AKSES-v7 Fase 4c). <span> tanpa
                                                 href mustahil diklik MAUPUN di-Tab — tak ada `pointer-events:none`
                                                 yang bisa ditembus, dan tooltipnya atribut `title` bawaan peramban
                                                 (nol JavaScript). Menu induknya tetap tampil meski SEMUA jenisnya
                                                 mati: ketetapan pemilik butir 4 — pengguna harus melihat fiturnya
                                                 ada dan tahu harus meminta akses. --}}
                                            <span class="nav-link pp-subnav pp-subnav-mati d-flex align-items-center" title="Tidak berwenang menyusun {{ $meta[0] }} — hubungi Admin">
                                                <div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi {{ $meta[1] }}"></i></div>
                                                <span class="nav-link-text">{{ $code }}</span>
                                                <i class="bi bi-lock-fill ms-auto small"></i>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                    {!! $item(route('documents.revisions'), 'Dokumen Revisi', 'bi-arrow-counterclockwise', $nav('documents.revisions'), $jml('revisi')) !!}
                @endcan

                {{-- Status Dokumen: HANYA untuk GL (pembuat) & Non-Staff (read-only).
                     SH/DH/PJO memakai "Status Dokumen Staff" (v2 rev: hapus Status Dokumen
                     di SH/DH/PJO agar tidak dobel). --}}
                @if (! $user->can('document.review') && ! $canViewAll)
                    @if ($user->can('document.create'))
                        {{-- GL: dropdown 2 submenu — "Dokumen Departemen" (se-dept, read-only)
                             & "Dokumen Saya" (buatannya, bisa hapus draft). --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.index') }}{{ $nav('documents.staffStatus') }}" href="#" @click.prevent="statusMenu = !statusMenu" :aria-expanded="statusMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-list-check"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="statusMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="statusMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column pp-submenu">
                                    {!! $subItem(route('documents.staffStatus'), 'Dokumen Departemen', 'bi-building', $nav('documents.staffStatus')) !!}
                                    {!! $subItem(route('documents.index'), 'Dokumen Saya', 'bi-person-lines-fill', $nav('documents.index')) !!}
                                </ul>
                            </div>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.index') }}" href="#" @click.prevent="statusMenu = !statusMenu" :aria-expanded="statusMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-list-check"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="statusMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="statusMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column pp-submenu">
                                    {!! $subItem(route('documents.index'), 'Semua', 'bi-grid', request()->routeIs('documents.index') && ! request('type') ? 'active' : '') !!}
                                    @foreach (array_intersect_key($jenisMenu, array_flip($jenisAktif)) as $code => $icon)
                                        {!! $subItem(route('documents.index', ['type' => $code]), $code, $icon, strtoupper(request('type', '')) === $code ? 'active' : '') !!}
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endif
                @endif

                {{-- Dokumen Berlaku (+ sub-menu Tidak Berlaku bagi SH/DH/PJO/Admin;
                     GL ikut melihat read-only, v3 rev) --}}
                {{-- Semua KECUALI Non-Staff (Fase C). Dulu tiga izin di-OR yang
                     kebetulan berjumlah sama; sejak `document.request_revision`
                     pindah ke GL/MD, rumus lama menjatuhkan SH/DH dari daftar. --}}
                @php $canObsolete = $user->dashboardPenuh(); @endphp
                @if ($canObsolete)
                    <li class="nav-item">
                        <a class="nav-link {{ $nav('documents.published') }}{{ $nav('documents.obsolete') }}{{ $nav('nonaktif.*') }}" href="#" @click.prevent="berlakuMenu = !berlakuMenu" :aria-expanded="berlakuMenu.toString()">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-folder-check"></i></div>
                            <span class="nav-link-text ms-1">Dokumen Berlaku</span>{!! $titik($jml('nonaktif')) !!}
                            <i class="bi bi-chevron-down ms-auto pp-caret" :class="berlakuMenu && 'pp-caret-open'"></i>
                        </a>
                        <div x-show="berlakuMenu" x-cloak x-transition.opacity>
                            <ul class="nav flex-column pp-submenu">
                                {!! $subItem(route('documents.published'), 'Berlaku', 'bi-folder-check', $nav('documents.published')) !!}
                                {!! $subItem(route('documents.obsolete'), 'Tidak Berlaku', 'bi-slash-circle', $nav('documents.obsolete')) !!}
                                {{-- Distribusi (v5 Fase C) — SH/DH + PJO/Admin/MD.
                                     Menggantungkannya pada izin revisi (yang sejak
                                     PLAN-REVISI-v6 Fase C milik GL/MD) akan mencabut
                                     menunya justru dari pihak yang menegur cakupan
                                     rendah. Aturannya di User::bisaLihatDistribusi(). --}}
                                @if ($user->bisaLihatDistribusi())
                                    {!! $subItem(route('documents.distribution'), 'Distribusi', 'bi-broadcast', $nav('documents.distribution')) !!}
                                @endif
                                {{-- Persetujuan Nonaktif (Fase F) — SATU antrean untuk
                                     ketiga tahap; tampil hanya bagi yang berwenang di
                                     salah satunya. `tahapNonaktifUntuk` tinggal jadi
                                     penjaga tampil/tidaknya menu; angkanya datang dari
                                     AntreanTugas — view tak boleh punya query sendiri. --}}
                                @if (\App\Models\Document::tahapNonaktifUntuk($user) !== [])
                                    {!! $subItem(route('nonaktif.index'), 'Persetujuan Nonaktif', 'bi-slash-circle', $nav('nonaktif.*'), $jml('nonaktif')) !!}
                                @endif
                            </ul>
                        </div>
                    </li>
                @else
                    {!! $item(route('documents.published'), 'Dokumen Berlaku', 'bi-folder-check', $nav('documents.published')) !!}
                @endif

                {{-- Log Dokumen (PLAN-REVISI-v6 Fase C, D5) — dua daftar yang
                     selama ini tercecer sebagai tempelan di halaman lain:
                     masukan lapangan, dan setiap pesan yang pernah ditulis atas
                     sebuah dokumen. Terbuka bagi semua kecuali Non-Staff; apa
                     yang TAMPIL di dalamnya dibatasi lagi per peran. --}}
                @if ($user->dashboardPenuh())
                    <li class="nav-item">
                        <a class="nav-link {{ $nav('log.masukan') }}{{ $nav('log.pesan') }}" href="#" @click.prevent="logMenu = !logMenu" :aria-expanded="logMenu.toString()">
                            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-journal-text"></i></div>
                            <span class="nav-link-text ms-1">Log Dokumen</span>{!! $titik($jml('masukan')) !!}
                            <i class="bi bi-chevron-down ms-auto pp-caret" :class="logMenu && 'pp-caret-open'"></i>
                        </a>
                        <div x-show="logMenu" x-cloak x-transition.opacity>
                            <ul class="nav flex-column pp-submenu">
                                {!! $subItem(route('log.masukan'), 'Masukan Lapangan', 'bi-chat-left-dots', $nav('log.masukan'), $jml('masukan')) !!}
                                {!! $subItem(route('log.pesan'), 'Log Pesan', 'bi-card-list', $nav('log.pesan')) !!}
                            </ul>
                        </div>
                    </li>
                @endif

                {{-- Riwayat Pekerjaan JSA — cerminan web atas checklist yang diisi
                     lapangan dari HP. Terbuka bagi SEMUA peran aktif; lingkup
                     departemennya dibatasi di JobExecutionController::index(). --}}
                {!! $item(route('job-executions.index'), 'Riwayat Pekerjaan', 'bi-person-check', $nav('job-executions.*')) !!}

                @if ($user->can('review-access') || $user->can('document.review_md'))
                    <div class="section-label">Peninjauan</div>
                @endif

                {{-- Peninjau SUBSTANSI (SH/DH, plus GL SHE untuk JSA).
                     Polanya sengaja TIDAK `review.*` — itu ikut menyorot halaman
                     MD, sehingga bagi Admin (pemegang kedua izin) dua menu
                     tampak aktif sekaligus. --}}
                @can('review-access')
                    {!! $item(route('review.index'), 'Tinjau Dokumen', 'bi-clipboard-check',
                        $nav('review.index').$nav('review.show'), $jml('tinjau')) !!}
                @endcan

                {{-- Peninjau PENULISAN (Management Development), tahap kedua. --}}
                @can('document.review_md')
                    {!! $item(route('review.md'), 'Tinjau Penulisan', 'bi-spellcheck', $nav('review.md*'), $jml('tinjau_md')) !!}
                @endcan

                {{-- Status Dokumen Staff: SH/DH (dept sendiri, read-only) 2d; PJO (7 dept submenu) 2e --}}
                @if ($user->can('document.review') || $canViewAll)
                    @if ($canViewAll)
                        <li class="nav-item">
                            <a class="nav-link {{ $nav('documents.staffStatus') }}" href="#" @click.prevent="staffMenu = !staffMenu" :aria-expanded="staffMenu.toString()">
                                <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-people-fill"></i></div>
                                <span class="nav-link-text ms-1">Status Dokumen Staff</span>
                                <i class="bi bi-chevron-down ms-auto pp-caret" :class="staffMenu && 'pp-caret-open'"></i>
                            </a>
                            <div x-show="staffMenu" x-cloak x-transition.opacity>
                                <ul class="nav flex-column pp-submenu">
                                    @foreach ($allDepts as $d)
                                        {!! $subItem(route('documents.staffStatus', ['department_id' => $d->id]), $d->code, $deptIcon($d->code), request('department_id') == $d->id ? 'active' : '') !!}
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @else
                        {!! $item(route('documents.staffStatus'), 'Status Dokumen Staff', 'bi-people-fill', $nav('documents.staffStatus')) !!}
                    @endif
                @endif
                @can('document.approve')
                    {!! $item(route('approvals.index'), 'Persetujuan Saya', 'bi-patch-check', $nav('approvals.*'), $jml('setujui')) !!}
                @endcan

                {{-- Pengaturan Sistem (PLAN-AKSES-v8 Fase 4) — dulu terbelah
                     "Administrasi" + "Pengaturan Sistem". Belahan itu tak pernah
                     punya garis yang jelas: Manajemen User menyetel SIAPA yang ada
                     di sistem, sama seperti Manajemen Akses menyetel siapa boleh
                     apa. Section-nya ber-@canany atas ketiga izin, BUKAN
                     @can('user.manage'), supaya GL — yang memegang
                     user.approve_registration & audit.view — tidak kehilangan dua
                     menunya. Urutan: yang paling sering dipakai di atas.
                     Ketiga item Fase 5 (Penomoran Dokumen, Master Data, Konfigurasi
                     Sistem) duduk DI DALAM blok ini, semuanya @can('user.manage'). --}}
                @canany(['user.manage','user.approve_registration','audit.view'])
                    <div class="section-label">Pengaturan Sistem</div>
                    @can('user.approve_registration'){!! $item(route('users.pending'), 'Persetujuan Akun', 'bi-person-check', $nav('users.pending'), $jml('akun')) !!}@endcan
                    @can('user.manage'){!! $item(route('users.index'), 'Manajemen User', 'bi-people', $nav('users.index').' '.$nav('users.create')) !!}@endcan
                    @can('user.manage'){!! $item(route('akses.index'), 'Manajemen Akses', 'bi-diagram-3', $nav('akses.index')) !!}@endcan
                    @can('user.manage'){!! $item(route('pengaturan.penomoran'), 'Penomoran Dokumen', 'bi-hash', $nav('pengaturan.penomoran')) !!}@endcan
                    @can('user.manage'){!! $item(route('pengaturan.master'), 'Master Data', 'bi-diagram-2', $nav('pengaturan.master')) !!}@endcan
                    @can('user.manage'){!! $item(route('pengaturan.sistem'), 'Konfigurasi Sistem', 'bi-sliders', $nav('pengaturan.sistem')) !!}@endcan
                    @can('audit.view'){!! $item(route('audit.index'), 'Audit Log', 'bi-shield-lock', $nav('audit.index')) !!}@endcan
                @endcanany

                <div class="section-label">Informasi</div>
                {{-- Menu INFORMASI (butir 3), DI ATAS "Informasi Akun" atas
                     ketetapan pemilik (BARU-2). Terbuka bagi SEMUA akun aktif —
                     tanpa @can sama sekali, karena itu memang intinya:
                     kebijakan & poster ditujukan justru kepada Non-Staff. --}}
                <li class="nav-item">
                    <a class="nav-link {{ $nav('informasi.*') }}" href="#" @click.prevent="infoMenu = !infoMenu" :aria-expanded="infoMenu.toString()">
                        <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi bi-info-square"></i></div>
                        <span class="nav-link-text ms-1">Informasi</span>
                        <i class="bi bi-chevron-down ms-auto pp-caret" :class="infoMenu && 'pp-caret-open'"></i>
                    </a>
                    <div x-show="infoMenu" x-cloak x-transition.opacity>
                        <ul class="nav flex-column pp-submenu">
                            {{-- Kategori dari tabel `informasi_kategori` (Fase 5b), bukan
                                 konstanta. Yang NONAKTIF tetap tampil dalam keadaan mati —
                                 ketetapan pemilik H: dokumennya masih ada, dan orang perlu
                                 melihat bahwa kategorinya sengaja ditutup, bukan mendapati
                                 menunya lenyap. <span> tanpa href mustahil diklik maupun
                                 di-Tab; polanya sama dengan jenis dokumen di luar profil
                                 akses (nol JavaScript). --}}
                            @php $katBawaan = \App\Models\Informasi::kategoriBawaan(); @endphp
                            @foreach (\App\Models\InformasiKategori::semua() as $slug => $kat)
                                @if ($kat->is_active)
                                    {!! $subItem(
                                        route('informasi.index', ['kategori' => $slug]),
                                        $kat->nama,
                                        $kat->ikon,
                                        request()->routeIs('informasi.index') && request('kategori', $katBawaan) === $slug ? 'active' : ''
                                    ) !!}
                                @else
                                    <li class="nav-item">
                                        <span class="nav-link pp-subnav pp-subnav-mati d-flex align-items-center" title="{{ $kat->nama }} sedang ditutup Admin">
                                            <div class="icon icon-shape shadow-sm border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi {{ $kat->ikon }}"></i></div>
                                            <span class="nav-link-text">{{ $kat->nama }}</span>
                                            <i class="bi bi-lock-fill ms-auto small"></i>
                                        </span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </li>
                {!! $item(route('account.info'), 'Informasi Akun', 'bi-person-badge', $nav('account.info')) !!}
            </ul>
        </div>
    </aside>

    {{-- ===== Main content ===== --}}
    <main class="main-content position-relative border-radius-lg">
        <nav class="navbar navbar-main navbar-expand-lg mx-3 mt-3 px-3 py-2 shadow-sm border-radius-xl bg-white">
            <div class="d-flex align-items-center w-100">
                <button type="button" class="pp-navbtn d-xl-none me-1" @click="sidebar = !sidebar" aria-label="Buka menu"><i class="bi bi-list"></i></button>
                <h6 class="mb-0 fw-bold text-dark lh-1">@yield('title', 'Dashboard')</h6>
                <div class="ms-auto d-flex align-items-center gap-1">
                    <button type="button" class="pp-navbtn" onclick="ppToggleTheme()" title="Ganti tema" aria-label="Ganti tema"><i class="bi bi-circle-half"></i></button>

                    @php $ppUnread = $user->unreadNotifications; @endphp
                    <div class="dropdown">
                        <a href="#" class="pp-navbtn position-relative" data-bs-toggle="dropdown" aria-label="Notifikasi">
                            <i class="bi bi-bell"></i>
                            {{-- Dibatasi "99+": tiga digit melebarkan lencana sampai keluar kotak 40px. --}}
                            @if($ppUnread->count())<span class="position-absolute badge rounded-pill bg-danger pp-navbtn-lencana">{{ $ppUnread->count() > 99 ? '99+' : $ppUnread->count() }}</span>@endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:330px;max-height:420px;overflow:auto">
                            <li class="d-flex justify-content-between align-items-center px-3 py-2">
                                <span class="fw-semibold small">Notifikasi</span>
                                {{-- mb-0: menetralkan `.btn{margin-bottom:1rem}` Soft UI yang
                                     kalau dibiarkan menyisakan ruang hantu di header dropdown. --}}
                                @if($ppUnread->count())<form method="POST" action="{{ route('notifications.readAll') }}" class="d-flex">@csrf<button class="btn btn-link btn-sm p-0 mb-0 shadow-none text-decoration-none" style="font-size:.75rem">Tandai dibaca</button></form>@endif
                            </li>
                            <li><hr class="dropdown-divider my-0"></li>
                            @forelse($user->notifications->take(8) as $n)
                                {{-- Belum dibaca = latar netral + titik penanda; sudah dibaca = polos --}}
                                <li><a class="dropdown-item d-flex gap-2 py-2 align-items-start {{ $n->read_at ? '' : 'bg-body-secondary fw-semibold' }}" href="{{ route('notifications.open', $n->id) }}">
                                    <i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} mt-1"></i>
                                    <span class="small text-wrap flex-grow-1">{{ $n->data['message'] ?? '' }}<br><span class="text-muted fw-normal" style="font-size:.7rem">{{ $n->created_at->diffForHumans() }}</span></span>
                                    @unless($n->read_at)<span class="rounded-circle mt-1" style="width:8px;height:8px;background:#8392ab;flex:0 0 auto"></span>@endunless
                                </a></li>
                            @empty
                                <li><span class="dropdown-item-text text-muted small text-center d-block py-3">Belum ada notifikasi</span></li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="dropdown ms-1">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                            <div class="text-end d-none d-sm-block">
                                <div class="pp-navuser-nama text-dark">{{ $user->name }}</div>
                                {{-- Pakai label jabatan dulu: kunci peran `staff` di layar
                                     bernama "Non-Staff" (CLAUDE.md §6). Peran tanpa jabatan
                                     (Admin IT, Management Development) jatuh ke nama perannya. --}}
                                <div class="pp-navuser-peran text-secondary">{{ $user->jabatanLabel() ?: str_replace('_',' ', $user->getRoleNames()->first() ?? '-') }}</div>
                            </div>
                            {{-- Pembungkus bulat dipakai untuk KEDUA cabang: foto dan ikon
                                 cadangan menempati kotak 36px yang sama, jadi tinggi
                                 topbar tak berubah antar pengguna. --}}
                            <span class="pp-navavatar">
                                @if ($user->photoUrl())
                                    <img src="{{ $user->photoUrl() }}" alt="{{ $user->name }}">
                                @else
                                    <i class="bi bi-person-fill"></i>
                                @endif
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('account.info') }}"><i class="bi bi-person-badge"></i> Informasi Akun</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button></form></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container-fluid py-4">
            {{-- Pesan sukses menerima DUA kunci: `status` (dipakai sebagian besar
                 controller) dan `success` (konvensi Laravel yang umum). Sebelum ini
                 hanya `status` yang dirender, sehingga keempat pesan sukses
                 AccessProfileController hilang diam-diam — Admin menekan Simpan dan
                 layarnya tak berkata apa-apa, padahal profilnya tersimpan.

                 Diperbaiki DI SINI, bukan dengan menyeragamkan controller-nya:
                 layout adalah corong yang dilewati semuanya, jadi satu blok ini
                 menyembuhkan pemakai `success` yang sekarang MAUPUN yang ditulis
                 nanti. Menyeragamkan controller hanya menutup yang empat itu, dan
                 memasang kembali jebakan yang sama untuk kode berikutnya.

                 Dikunci ManajemenAksesTest::test_pesan_sukses_tampil_di_layar. --}}
            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('status') ?? session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle"></i> {{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Validasi "semua field wajib terisi" di sisi klien: tandai field kosong MERAH
    // (is-invalid), gulir + fokus ke yang pertama, tampilkan pesan (bukan alert
    // browser "…says"). Field opsional diberi atribut `data-optional`. Hanya field
    // yang TERLIHAT (langkah aktif) yang dicek; kelengkapan antar-langkah dijaga
    // server. Return true bila semua terisi.
    function ppValidateRequired(form) {
        const els = form.querySelectorAll('input:not([type=hidden]):not([type=file]):not([type=checkbox]):not([type=radio]):not([type=button]):not([type=submit]), textarea, select');
        let first = null;
        els.forEach(function (el) {
            // `[inert]` = bab opsional yang sedang dimatikan. Menuntutnya terisi
            // berarti jalan buntu: kolomnya tak bisa diklik sama sekali.
            if (el.disabled || el.dataset.optional !== undefined || el.offsetParent === null || el.closest('[inert]')) return;
            el.classList.remove('is-invalid');
            if (!String(el.value).trim()) { el.classList.add('is-invalid'); if (!first) first = el; }
        });
        // Grup RADIO wajib (papan ketersediaan peninjau — FITUR-BARU-v4 §6).
        // Radio dikecualikan dari querySelectorAll di atas karena satu grup punya
        // banyak elemen dan hanya SATU yang perlu terisi; tanpa penanganan
        // tersendiri, grup kosong lolos ke sini lalu ditangkap gelembung bawaan
        // peramban — persis "…says" yang sengaja kita hindari.
        const grup = {};
        form.querySelectorAll('input[type=radio][required]').forEach(function (el) {
            // Anggota yang TERKUNCI dilewati. Konsekuensinya disengaja: grup yang
            // SELURUH anggotanya terkunci (sejak kuota dimatikan: semua peninjau
            // sedang off) tak ikut divalidasi, sehingga dokumen tetap bisa DISIMPAN —
            // persis jalan keluar yang ditawarkan pesan buntu di papan.
            if (el.disabled || el.offsetParent === null) return;
            grup[el.name] = grup[el.name] || [];
            grup[el.name].push(el);
        });
        Object.values(grup).forEach(function (radios) {
            const terpilih = radios.some(function (el) { return el.checked; });
            radios.forEach(function (el) { el.classList.toggle('is-invalid', ! terpilih); });
            if (! terpilih && ! first) first = radios[0];
        });

        /*
         | Kolom RICH TEXT (Deskripsi Aktivitas SOP/SP/IK).
         |
         | Nilainya dipegang sebuah input HIDDEN — Quill yang menulis ke sana —
         | dan hidden SENGAJA dikecualikan dari querySelectorAll di atas (kolom
         | tersembunyi milik langkah lain tak boleh ikut dituntut). Tanpa bagian
         | ini, bab wajib yang dibiarkan kosong lolos diam-diam dan penyusun
         | baru mengetahuinya setelah dokumennya dikembalikan peninjau.
         |
         | Tanda merahnya dipasang di PEMBUNGKUS (.pp-rt-invalid): input hidden
         | tak punya rupa, dan yang dilihat penyusun adalah kotak editornya.
         */
        form.querySelectorAll('input[type=hidden][data-pp-rich]').forEach(function (el) {
            const kotak = el.closest('.pp-rt');
            if (! kotak || el.dataset.optional !== undefined || kotak.offsetParent === null) return;
            const kosong = ! String(el.value).trim();
            kotak.classList.toggle('pp-rt-invalid', kosong);
            // .ql-editor contenteditable → focus() bekerja seperti pada <textarea>.
            if (kosong && ! first) first = kotak.querySelector('.ql-editor') || kotak;
        });

        if (first) {
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function () { try { first.focus({ preventScroll: true }); } catch (e) {} }, 250);
            Swal.fire({ icon: 'error', title: 'Ada kolom yang belum diisi',
                text: 'Lengkapi semua kolom yang ditandai merah sebelum melanjutkan.', confirmButtonColor: '#ea580c' });
            return false;
        }
        return true;
    }
    // Bersihkan tanda merah begitu field diisi. Untuk radio, seluruh grupnya
    // ikut bersih — yang salah tadi memang grupnya, bukan satu tombolnya.
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el.classList) return;

        if (el.type === 'radio' && el.checked && el.form) {
            el.form.querySelectorAll('input[type=radio][name="' + CSS.escape(el.name) + '"]')
                .forEach(function (r) { r.classList.remove('is-invalid'); });
            return;
        }

        // Rich text: penandanya di pembungkus, dan peristiwa `input`-nya
        // dikirim sendiri oleh komponen richText tiap kali Quill berubah.
        if (el.matches && el.matches('input[data-pp-rich]')) {
            const kotak = el.closest('.pp-rt');
            if (kotak && String(el.value).trim()) kotak.classList.remove('pp-rt-invalid');
            return;
        }

        if (el.classList.contains('is-invalid') && String(el.value).trim()) {
            el.classList.remove('is-invalid');
        }
    });
</script>
<script>
    // Konfirmasi SweetAlert untuk form ber-`data-confirm`. Sumber atribut = TOMBOL
    // yang diklik (e.submitter) bila ia punya data-confirm (mendukung 2 tombol beda
    // pesan dalam 1 form, mis. Setujui/Kembalikan), jika tidak → form.
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const btn = e.submitter;
        const src = (btn && btn.hasAttribute('data-confirm')) ? btn : form;
        const msg = src.getAttribute('data-confirm');
        if (!msg || form.dataset.confirmed) return;
        e.preventDefault();
        Swal.fire({
            title: src.getAttribute('data-confirm-title') || 'Konfirmasi', text: msg,
            icon: src.getAttribute('data-confirm-icon') || 'question', showCancelButton: true,
            confirmButtonText: src.getAttribute('data-confirm-ok') || 'Ya, lanjutkan', cancelButtonText: 'Batal',
            confirmButtonColor: '#ea580c', cancelButtonColor: '#6c757d',
        }).then(function (r) {
            if (!r.isConfirmed) return;
            form.dataset.confirmed = '1';
            // Klik ulang tombol agar name/value-nya (mis. decision=approve) ikut terkirim.
            if (btn) { btn.click(); } else { form.submit(); }
        });
    }, true);
</script>
{{-- Modal "tugas menunggu": hanya tepat sesudah login DAN hanya bila memang ada
     yang menunggu. Bootstrap bundle sudah dimuat di atas, jadi nol aset baru. --}}
@if (session('antrean_awal') && $antrean !== [])
    @include('partials._modal-antrean')
    <script>new bootstrap.Modal(document.getElementById('modalAntrean')).show();</script>
@endif
@stack('scripts')
</body>
</html>
