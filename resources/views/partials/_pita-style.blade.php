{{--
    Gaya tampilan ketersediaan (FITUR-BARU-v4 §6).

    Dipakai DUA tempat dengan bentuk yang berbeda, karena tugasnya memang beda:
      • KALENDER MINI di kartu dashboard — "kapan saya libur?" (jadwal diri)
      • PITA LURUS di papan pemilihan peninjau — "siapa yang paling cepat bisa?"
        (membandingkan antar-orang, jadi harinya wajib sejajar)

    Warnanya sama di kedua tempat, jadi orang yang menyatakan dirinya off
    mengenali keadaan yang sama seperti yang dilihat GL tentang dirinya.

    ATURAN LEBAR: tak ada satu pun sel berlebar tetap. Semua `flex: 1 1 0` atau
    `1fr`, sehingga mengisi ruang yang tersedia. Versi sebelumnya mematok
    1,15rem — akibatnya nama hari 8,8px terjepit di kotak 18px sementara sisa
    lebar kartu dibiarkan kosong.

    `@once` menjaga blok ini hanya tercetak sekali walau di-include berkali-kali.
--}}
@once
    @push('styles')
        <style>
            /* ============================================================
               Warna keadaan — memakai gradasi tema Soft UI yang sudah ada,
               bukan `bg-warning` datar yang menyilaukan di batang setipis ini.

               OFF = ORANYE (spec v2 W1): gradasi --su-orange1/2, gradasi yang
               sama persis dengan `bg-su-orange` di seluruh aplikasi. Dulu
               --su-warn1/2 — gradasi itu berangkat dari KUNING, sehingga sel
               off terbaca kuning, bukan oranye identitas.

               Diubah di SATU tempat, jadi kalender dashboard dan pita papan
               pemilihan peninjau tetap sewarna: orang yang menyatakan dirinya
               off mengenali keadaan yang sama seperti yang dilihat GL tentang
               dirinya.
               ============================================================ */
            .pp-av-off    { background-image: linear-gradient(310deg, var(--su-orange1), var(--su-orange2)); }

            /* Akhir pekan & libur nasional TAMPIL SAMA PERSIS dengan hari kerja
               (ketetapan pemilik, 4 Agustus 2026): warna sama, tanpa peredupan,
               tanpa bingkai khusus.
               Yang dibedakan pita ini cuma SATU hal — orangnya ada atau tidak.
               Kantor tutup bukan urusan itu; menandainya hanya menambah bahasa
               visual yang harus dihafal, dan dua percobaan sebelumnya (arsiran
               45°, lalu strip diredupkan) sama-sama menyita perhatian untuk
               keterangan yang tak menentukan keputusan apa pun.
               Keterangannya tidak hilang: `title` tiap sel tetap berbunyi
               "Akhir pekan" / "Libur nasional" saat kursor lewat. Dan sisi
               LOGIKA-nya sama sekali tak tersentuh — ReviewerAvailability tetap
               melewati hari tutup saat menghitung "kembali tanggal berapa". */
            .pp-av-tersedia,
            .pp-av-tutup  { background-color: var(--bs-secondary-bg); }

            /* ============================================================
               KALENDER MINI (kartu dashboard) — 2 baris × 7 kolom
               ============================================================ */
            .pp-kal { display: grid; grid-template-columns: repeat(7, 1fr); gap: .3rem; }
            .pp-kal-nama {
                text-align: center; font-size: .7rem; font-weight: 600;
                color: var(--bs-secondary-color); padding-bottom: .15rem;
            }
            .pp-kal-sel {
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                gap: .2rem; min-height: 2.9rem; padding: .35rem .1rem;
                border: 1px solid var(--bs-border-color); border-radius: .6rem;
                background: var(--bs-body-bg); cursor: pointer;
                transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
            }
            .pp-kal-sel:hover { transform: translateY(-2px); box-shadow: 0 4px 10px -4px rgba(0,0,0,.25); border-color: var(--su-orange1); }
            .pp-kal-sel:focus-visible { outline: 2px solid var(--su-orange1); outline-offset: 2px; }
            .pp-kal-tgl { font-size: .8rem; font-weight: 600; line-height: 1; }
            /* Penanda keadaan: batang kecil di bawah angka. */
            .pp-kal-tanda { width: 60%; height: .3rem; border-radius: .2rem; }
            /* Hari kantor tutup TIDAK lagi dibedakan: bingkai putus-putus dan
               angka yang diredupkan sama-sama dihapus. Lihat alasannya pada
               .pp-av-tutup di atas. */
            /* Hari ini: cincin oranye — penanda paling kuat di kalender. */
            .pp-kal-sel.is-hari-ini { box-shadow: 0 0 0 2px var(--su-orange1); border-color: transparent; }

            /* ============================================================
               PITA LURUS (papan pemilihan peninjau)
               ============================================================ */
            .pp-papan-baris { display: grid; grid-template-columns: minmax(0, 14rem) 1fr; gap: 1rem; align-items: center; }
            .pp-papan-head { padding: 0 .85rem .3rem 2.6rem; }
            .pp-kandidat-orang { min-width: 0; }

            /* Kolom BEBAN (section peninjau saja). Lebar `auto` supaya pil
               semua kandidat berbaris rata di ujung baris — itulah gunanya:
               dibandingkan sekali pandang, bukan dibaca satu per satu.
               Nol warna baru di sini; warnanya datang dari `badge-soft-*`. */
            .pp-papan-beban .pp-papan-baris { grid-template-columns: minmax(0, 14rem) 1fr auto; }
            .pp-papan-beban-sel { text-align: right; white-space: nowrap; }

            /* Alur latar membuat 14 batang terbaca sebagai SATU garis waktu,
               bukan titik-titik lepas. */
            .pp-pita { display: flex; gap: 3px; background: var(--bs-tertiary-bg); border-radius: .45rem; padding: .2rem; }
            .pp-sel { flex: 1 1 0; min-width: 0; height: .9rem; border-radius: .3rem; }
            /* Celah pemisah antar-minggu — menggantikan nama hari, yang di papan
               justru membuat tampilan dempet. */
            .pp-sel-pekan-baru { margin-left: .45rem; }
            .pp-pita-label { display: flex; gap: 3px; padding: 0 .2rem; }
            .pp-pita-label > span { flex: 1 1 0; min-width: 0; font-size: .65rem; color: var(--bs-secondary-color); }
            .pp-pita-label > span.pp-sel-pekan-baru { margin-left: .45rem; }

            /* Baris kandidat */
            .pp-kandidat { display: block; position: relative; margin-bottom: .45rem; cursor: pointer; }
            .pp-kandidat-input { position: absolute; left: .85rem; top: 1.25rem; z-index: 1; }
            .pp-kandidat-isi {
                display: block; padding: .7rem .85rem .7rem 2.6rem;
                border: 1px solid var(--bs-border-color); border-left: 3px solid transparent;
                border-radius: .7rem; transition: background-color .15s, border-color .15s;
            }
            .pp-kandidat:hover .pp-kandidat-isi { background: var(--bs-tertiary-bg); }
            /* Selektor saudara (~), bukan :has() — peramban lama di PC lapangan
               belum tentu mendukungnya. */
            .pp-kandidat-input:checked ~ .pp-kandidat-isi { border-left-color: var(--su-orange1); background: var(--bs-tertiary-bg); }
            .pp-kandidat-input:focus-visible ~ .pp-kandidat-isi { outline: 2px solid var(--su-orange1); outline-offset: 2px; }
            .pp-kandidat-terkunci { cursor: not-allowed; }
            .pp-kandidat-terkunci .pp-kandidat-isi { opacity: .55; }
            .pp-kandidat-terkunci:hover .pp-kandidat-isi { background: transparent; }

            /* Keterangan warna, dipakai kedua tampilan. */
            .pp-av-legenda { display: flex; flex-wrap: wrap; gap: .85rem; font-size: .7rem; color: var(--bs-secondary-color); }
            .pp-av-legenda i { display: inline-block; width: .85rem; height: .5rem; border-radius: .2rem; vertical-align: middle; }

            /* Layar sempit: pita papan dipotong jadi 7 hari, bukan digulir
               mendatar. Kalender tetap 7 kolom — ia memang sudah muat. */
            @media (max-width: 767.98px) {
                .pp-hari-lanjut { display: none; }
                /* Varian 3 kolom IKUT dilipat — kalau tertinggal, pil "0 dok"
                   menghimpit pita sampai gepeng di layar 375px. */
                .pp-papan-baris,
                .pp-papan-beban .pp-papan-baris { grid-template-columns: minmax(0, 1fr); gap: .5rem; }
                /* Satu kolom: pil naik ke ATAS pita (order 1 vs 2), tetap rata
                   kanan — tempat mata sudah mencarinya. Label "Beban" di baris
                   kepala tak lagi menerangkan kolom apa pun, jadi disembunyikan. */
                .pp-papan-beban .pp-papan-beban-sel { order: 1; }
                .pp-papan-beban .pp-pita { order: 2; }
                .pp-papan-head .pp-papan-beban-sel { display: none; }
                .pp-kal-sel { min-height: 2.4rem; }
            }
            @media (prefers-reduced-motion: reduce) {
                .pp-kandidat-isi, .pp-kal-sel { transition: none; }
                .pp-kal-sel:hover { transform: none; }
            }
        </style>
    @endpush
@endonce
