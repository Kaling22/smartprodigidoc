@extends('layouts.app')
@section('title', 'Dashboard')

@php
    // Label & warna status kini dari Document::STATUS_LABELS + STATUS_META.
    // Peta lokal yang dulu di sini tak memuat `waiting_for_review` maupun
    // `verifikasi_md`, sehingga dua baris matriks tampil abu-abu tanpa label.
    /*
    | Warna ikon linimasa mengikuti tata bahasa referensi soft_ui:
    |   oranye = dokumen MAJU selangkah
    |   hitam  = peristiwa netral / rutin
    |   hijau  = HASIL baik (disahkan)      <- hanya di sini
    |   merah  = HASIL buruk (ditolak/hapus) <- hanya di sini
    | Dulu warnanya biru/kuning/merah-muda berselang tanpa aturan, sehingga
    | tak satu pun warna memberi tahu apa pun tentang peristiwanya.
    */
    $actionMeta = [
        'document.create' => ['membuat dokumen', 'bi-file-earmark-plus', 'bg-su-orange'],
        'document.submit' => ['mengirim untuk ditinjau', 'bi-send', 'bg-su-orange'],
        'document.withdraw' => ['menarik dokumen', 'bi-arrow-counterclockwise', 'bg-su-dark'],
        'document.review_start' => ['mulai meninjau', 'bi-clipboard', 'bg-su-dark'],
        'document.review_approve' => ['meloloskan tinjauan', 'bi-check2', 'bg-su-orange'],
        'document.review_reject' => ['mengembalikan untuk revisi', 'bi-arrow-counterclockwise', 'bg-su-merah'],
        'document.approve' => ['menyetujui (Berlaku)', 'bi-patch-check', 'bg-su-hijau'],
        'document.approval_reject' => ['menolak (approver)', 'bi-x-circle', 'bg-su-merah'],
        'document.cancel_revision' => ['membatalkan penolakan', 'bi-arrow-repeat', 'bg-su-dark'],
        'document.reassign_review' => ['mengalihkan peninjauan', 'bi-arrow-left-right', 'bg-su-dark'],
        'document.request_revision' => ['mengajukan revisi', 'bi-arrow-repeat', 'bg-su-orange'],
        'document.cancel_revision_b' => ['membatalkan revisi', 'bi-x-circle', 'bg-su-dark'],
        'document.nonaktif_ajukan' => ['mengajukan nonaktif', 'bi-slash-circle', 'bg-su-orange'],
        'document.nonaktif_setuju' => ['menyetujui nonaktif', 'bi-check2', 'bg-su-orange'],
        'document.nonaktif_tolak' => ['menolak pengajuan nonaktif', 'bi-x-circle', 'bg-su-dark'],
        'document.nonaktif_selesai' => ['menonaktifkan dokumen', 'bi-slash-circle', 'bg-su-merah'],
        'document.delete' => ['menghapus draft', 'bi-trash', 'bg-su-merah'],
        'attachment.comment' => ['mengomentari lampiran', 'bi-chat-left-text', 'bg-su-dark'],
        'document.md_approve' => ['meloloskan verifikasi MD', 'bi-fonts', 'bg-su-orange'],
        'document.ai_review' => ['meminta tinjauan AI', 'bi-stars', 'bg-su-dark'],
        'document.md_ai_review' => ['meminta tinjauan AI (MD)', 'bi-stars', 'bg-su-dark'],
        'feedback.create' => ['mengirim masukan', 'bi-chat-left-quote', 'bg-su-dark'],
        'feedback.respond' => ['membalas masukan', 'bi-reply', 'bg-su-dark'],
        'masukan_sejawat.kirim' => ['memberi masukan sejawat', 'bi-chat-square-text', 'bg-su-dark'],
        'off.create' => ['mengajukan off', 'bi-calendar-x', 'bg-su-dark'],
        'user.md_ai_toggle' => ['mengubah setelan AI MD', 'bi-toggles', 'bg-su-dark'],
        'user.register' => ['mendaftar akun', 'bi-person-plus', 'bg-su-dark'],
        'user.login' => ['masuk', 'bi-box-arrow-in-right', 'bg-su-dark'],
        'user.logout' => ['keluar', 'bi-box-arrow-right', 'bg-su-dark'],
    ];
@endphp

@section('content')
    @php
        /*
        | Kartu sambutan: apa yang MENUNGGU orang ini hari ini, bukan sekadar
        | "selamat bekerja". Kalimat & tombolnya berbeda per jabatan, seluruhnya
        | dari $stats/$queues yang sudah dihitung — nol query tambahan.
        */
        $tunggu = [];   // kalimat ringkas
        $aksi = [];     // [label, url, utama?]
        if ($isCreator) {
            if (($stats['need_revision'] ?? 0) > 0) $tunggu[] = $stats['need_revision'].' dokumen perlu direvisi';
            // Menaut ke jenis yang BOLEH ia susun, bukan `type=SOP` mati:
            // sesudah profil akses berlaku (PLAN-AKSES-v7 Fase 4), SOP bisa
            // saja justru satu-satunya jenis yang tertutup baginya. Tanpa satu
            // pun jenis, tombolnya tidak ditawarkan sama sekali.
            if ($jenisBaru = $user->jenisPertamaBoleh()) {
                $aksi[] = ['Buat Dokumen Baru', route('documents.create', ['type' => $jenisBaru]), true];
            }
            if (($stats['need_revision'] ?? 0) > 0) $aksi[] = ['Lihat Dokumen Revisi', route('documents.revisions'), false];
        } elseif ($isDeptHead) {
            if (($queues['review'] ?? 0) > 0) $tunggu[] = $queues['review'].' dokumen menunggu ditinjau';
            if (($queues['approval'] ?? 0) > 0) $tunggu[] = $queues['approval'].' menunggu persetujuanmu';
            if (($queues['review'] ?? 0) > 0) $aksi[] = ['Mulai Meninjau', route('review.index'), true];
            // Ejaannya WAJIB sama dengan menu sidebar & judul halamannya
            // ("Status Dokumen Staff"); dulu di sini "Staf", sehingga satu
            // tujuan yang sama tampil dengan dua nama berbeda.
            $aksi[] = ['Status Dokumen Staff', route('documents.staffStatus'), false];
        } elseif ($isPjo) {
            if (($queues['approval'] ?? 0) > 0) $tunggu[] = $queues['approval'].' dokumen menunggu disetujui';
            if (($queues['approval'] ?? 0) > 0) $aksi[] = ['Buka Persetujuan', route('approvals.index'), true];
            $aksi[] = ['Dokumen Berlaku', route('documents.published'), false];
        } elseif ($isMd ?? false) {
            if (($queues['review_md'] ?? 0) > 0) $tunggu[] = $queues['review_md'].' dokumen menunggu diperiksa';
            if (($queues['review_md'] ?? 0) > 0) $aksi[] = ['Mulai Memeriksa', route('review.md'), true];
            $aksi[] = ['Status Dokumen Staff', route('documents.staffStatus'), false];
        } else {
            $aksi[] = ['Dokumen Berlaku', route('documents.published'), true];
        }
        if (empty($aksi)) $aksi[] = ['Dokumen Berlaku', route('documents.published'), true];
    @endphp

    <div class="card pp-hero shadow-sm mb-3 pp-naik" style="--rise:0">
        {{-- Ombak oranye: SVG inline, bukan waves-white.svg 215 KB milik tema —
             nol permintaan jaringan dan warnanya ikut palet. --}}
        <svg class="pp-hero-ombak" viewBox="0 0 400 200" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="ppOmbak" x1="0" y1="1" x2="1" y2="0">
                    <stop offset="0" stop-color="#ea580c"/>
                    <stop offset="1" stop-color="#facc15"/>
                </linearGradient>
            </defs>
            {{-- Dua lapis saja, keduanya BERGRADASI penuh dan hanya dibedakan
                 opasitasnya. Versi sebelumnya menumpuk tiga lapis oranye
                 semi-transparan di atas hitam; hasil campurannya cokelat lumpur,
                 bukan oranye. --}}
            <path d="M150,0 C215,50 180,115 240,200 L400,200 L400,0 Z" fill="url(#ppOmbak)" opacity=".2"/>
            <path d="M255,0 C315,55 285,125 335,200 L400,200 L400,0 Z" fill="url(#ppOmbak)" opacity=".85"/>
        </svg>

        <div class="card-body position-relative p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    {{-- Warna latar depan hero memakai token --pp-hero-* (lihat
                         layouts/app.blade.php), BUKAN `text-white`/rgba mentah:
                         markupnya satu untuk dua tema, jadi warna yang dipatok
                         di sini akan hilang terbaca begitu latarnya jadi krem. --}}
                    <p class="text-uppercase fw-bold mb-1" style="font-size:.68rem;letter-spacing:.08em;color:var(--pp-hero-eyebrow)">
                        {{ \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? ($user->getRoleNames()->first() ?? '-') }}
                        @if($user->department) · {{ $user->department->code }} @endif
                    </p>
                    <h1 class="h3 fw-bold mb-2" style="color:var(--pp-hero-teks)">{{ $greeting }}, {{ $user->name }}.</h1>
                    <p class="mb-3" style="color:var(--pp-hero-teks-lembut)">
                        @if ($tunggu)
                            Menunggu tindakanmu: <span class="fw-semibold" style="color:var(--pp-hero-teks)">{{ implode(' · ', $tunggu) }}</span>.
                        @else
                            Tidak ada yang menunggu tindakanmu. Selamat bekerja.
                        @endif
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($aksi as [$label, $url, $utama])
                            <a href="{{ $url }}" class="btn btn-sm {{ $utama ? 'btn-pp-terang' : 'btn-pp-garis' }} px-3">
                                {{ $label }} <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-inline-block text-start">
                        <div class="fw-bold" style="font-size:1.6rem;line-height:1;color:var(--pp-hero-teks)">{{ now()->format('H:i') }}</div>
                        <div style="font-size:.72rem;color:var(--pp-hero-teks-lembut)">WITA · {{ now()->translatedFormat('l, d F Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 1: Overview Dokumen (70) | Meter Growth (30) — SATU kartu dengan
         pembatas vertikal (`.pp-row-bordered`, pola `row-bordered` SNEAT).
         Keduanya membaca periode yang SAMA: pemilih durasi di kepala Overview
         menggerakkan grafik sekaligus meter, jadi tak mungkin dua angka di satu
         kartu berbicara tentang rentang waktu yang berbeda. --}}
    @php $awal = $tren['bulan']; @endphp
    <div class="card border-0 shadow-sm pp-naik" style="--rise:1">
        <div class="row pp-row-bordered g-0">
            <div class="col-xl-8">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Overview Dokumen</h6>
                            <p class="text-sm text-muted mb-0">
                                <span class="fw-bold text-body" data-ikhtisar="ringkas">{{ $awal['berlakuTotal'] }} dari {{ $awal['total'] }}</span>
                                dokumen yang dibuat sudah disahkan
                                <span class="fw-semibold" style="color:var(--su-hijau2)" data-ikhtisar="rasio">
                                    @if ($awal['total'] > 0)({{ round($awal['berlakuTotal'] / $awal['total'] * 100) }}%)@endif
                                </span>
                                dalam <span data-ikhtisar="rentang">{{ $awal['rentang'] }}</span>
                            </p>
                        </div>
                        {{-- Legenda ditulis sendiri, bukan legenda bawaan Chart.js: dengan
                             DUA deret pembaca wajib tahu mana yang mana, tapi legenda
                             bawaan memakai kotak & fontnya sendiri yang keluar dari tema. --}}
                        <div class="d-flex align-items-center gap-3 text-sm">
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="pp-legenda-titik" style="background:#f97316"></span>Dibuat
                            </span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <span class="pp-legenda-titik" style="background:#22c55e"></span>Berlaku
                            </span>
                            {{-- Pemilih durasi (spec v3 R5). Dulu tautan teks polos —
                                 tak ada isyarat apa pun bahwa ia bisa dibuka, dan
                                 rupanya berbeda dari kembarannya di kolom sebelah
                                 padahal keduanya menggerakkan keadaan yang SAMA.
                                 Kini keduanya .btn-rentang: ikon kalender, label,
                                 chevron di balik garis tegak (bentuk SNEAT). --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-rentang dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-calendar3"></i>
                                    <span data-durasi-label>Bulanan</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" data-durasi="hari">Harian</a></li>
                                    <li><a class="dropdown-item" href="#" data-durasi="minggu">Mingguan</a></li>
                                    <li><a class="dropdown-item" href="#" data-durasi="bulan">Bulanan</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    {{-- Latar PUTIH, mengikuti kartu "Sales overview" di referensi.
                         Panel hitam di sana hanya dipakai grafik BATANG kecil
                         ("Active Users"), bukan grafik garis besar seperti ini. --}}
                    <div style="height:280px"><canvas id="grafikIkhtisar"></canvas></div>
                </div>
            </div>

            {{-- Meter growth (SNEAT "Total Revenue" kolom kanan): pemilih di atas,
                 radial di tengah, keterangan + dua angka di bawah. Radialnya SVG
                 biasa — busur 270° = `stroke-dasharray` pada lingkaran ber-
                 `pathLength="100"`, jadi persen langsung jadi panjang busur tanpa
                 pustaka grafik kedua. --}}
            <div class="col-xl-4">
                <div class="card-body d-flex flex-column align-items-center text-center">
                    <div class="dropdown mb-3">
                        <button class="btn btn-sm btn-rentang dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-calendar3"></i>
                            <span data-durasi-label>Bulanan</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-durasi="hari">Harian</a></li>
                            <li><a class="dropdown-item" href="#" data-durasi="minggu">Mingguan</a></li>
                            <li><a class="dropdown-item" href="#" data-durasi="bulan">Bulanan</a></li>
                        </ul>
                    </div>

                    <div class="pp-radial">
                        <svg viewBox="0 0 120 120" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="ppRadialGrad" x1="0" y1="1" x2="1" y2="0">
                                    <stop offset="0" style="stop-color:var(--su-orange1)"/>
                                    <stop offset="1" style="stop-color:var(--su-orange2)"/>
                                </linearGradient>
                            </defs>
                            {{-- 75 dari 100 = 270°; sisanya celah di bawah. --}}
                            <circle class="pp-radial-rel" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="75 25"/>
                            <circle class="pp-radial-isi" cx="60" cy="60" r="50" pathLength="100"
                                    stroke-dasharray="{{ $awal['growth'] * 0.75 }} 100" data-growth="busur"/>
                        </svg>
                        <div class="pp-radial-teks">
                            <div class="pp-radial-angka" data-growth="persen">{{ $awal['growth'] }}%</div>
                            <div class="text-xs text-muted">Tertinjau</div>
                        </div>
                    </div>

                    <p class="text-sm text-muted mt-3 mb-3">
                        <span data-growth="tertinjau">{{ $awal['tertinjau'] }}</span> dari
                        <span data-growth="total">{{ $awal['total'] }}</span> dokumen sudah melewati tinjauan
                    </p>

                    <div class="d-flex gap-4 justify-content-center w-100 mt-auto">
                        <div>
                            <div class="text-xs text-muted">Dibuat</div>
                            <h6 class="mb-0 fw-bold" data-growth="dibuat">{{ $awal['total'] }}</h6>
                        </div>
                        <div>
                            <div class="text-xs text-muted">Berlaku</div>
                            <h6 class="mb-0 fw-bold" data-growth="berlaku">{{ $awal['berlakuTotal'] }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Empat kartu ringkasan — dulu 2×2 di sisi kanan Overview; kolom itu kini
         milik meter growth, jadi mereka melebar jadi satu baris berempat. --}}
    <div class="row g-3 mt-1">
                @php
                    /*
                    | EMPAT kartu besar per peran (spec v2 C2). Elemen ke-5 = URL:
                    | kartu jadi tautan ke daftar terkait.
                    |
                    |   Head (SH/DH) : Perlu Disetujui · Total Dokumen · Berlaku · Sedang Revisi
                    |   GL           : Dokumen Saya · Berlaku · Dokumen Ditolak · Sedang Revisi
                    |   PJO          : Perlu Disetujui · Total Dokumen · Berlaku · Sedang Revisi
                    |   MD           : Perlu Diperiksa · Total Dokumen · Berlaku · Sedang Revisi
                    |
                    | Kartu "Akun Menunggu" DIBUANG atas permintaan pemilik —
                    | persetujuan akun tetap hidup sebagai menu tersendiri, tapi
                    | ia bukan pekerjaan harian yang layak menempati satu dari
                    | empat kartu teratas. Penggantinya kartu berlingkup: GL
                    | melihat dokumennya sendiri, sisanya melihat lingkup yang
                    | memang jadi wewenangnya ($stats sudah menghitung keduanya).
                    |
                    | Angka antrean yang tak berlaku bagi seseorang tampil 0, bukan
                    | menghilang: kartu yang kadang ada kadang tidak membuat tata
                    | letak berkedip antar-peran.
                    */
                    $isGl = ($isCreator ?? false);        // group_leader
                    $isDh = ($isDeptHead ?? false);       // section_head / departemen_head
                    $isPjoRole = ($isPjo ?? false);       // pimpinan
                    $isMdRole = ($isMd ?? false);         // role management_development

                    // Lingkup "Total Dokumen" mengikuti hak baca pemiliknya
                    // ($visible() di DashboardController), jadi angka yang sama
                    // berarti sedepartemen bagi Head dan 7 dept bagi PJO/MD.
                    $kTotal = ['Total Dokumen', $stats['total'], 'bi-files', route('documents.staffStatus')];
                    $kBerlaku = ['Berlaku', $stats['published'], 'bi-patch-check', route('documents.published')];
                    $kRevisi = ['Sedang Revisi', $stats['sedang_direvisi'], 'bi-arrow-repeat', route('documents.published')];
                    $kSetuju = ['Perlu Disetujui', $queues['approval'] ?? 0, 'bi-patch-question', route('approvals.index')];

                    if ($isGl) {
                        $tiles = [
                            ['Dokumen Saya', $stats['my_documents'], 'bi-folder2-open', route('documents.index')],
                            $kBerlaku,
                            ['Dokumen Ditolak', $stats['rejected'], 'bi-x-octagon', route('documents.revisions')],
                            $kRevisi,
                        ];
                    } elseif ($isDh || $isPjoRole) {
                        $tiles = [$kSetuju, $kTotal, $kBerlaku, $kRevisi];
                    } elseif ($isMdRole) {
                        /*
                        | MD memeriksa PENULISAN, tak pernah menyetujui — jadi
                        | "Perlu Disetujui" diganti "Perlu Diperiksa".
                        |
                        | "Akun Menunggu" juga TIDAK ikut, beda dengan Head:
                        | MD tak punya `user.approve_registration`, jadi kartunya
                        | akan selalu 0 sekaligus menautkan ke halaman yang
                        | menolaknya 403. Tempatnya diisi "Total Dokumen" —
                        | pembeda MD yang sesungguhnya, sebab hanya ia (di luar
                        | PJO/Admin) yang melihat 7 departemen sekaligus.
                        */
                        $tiles = [
                            ['Perlu Diperiksa', $queues['review_md'] ?? 0, 'bi-spellcheck', route('review.md')],
                            $kTotal,
                            $kBerlaku,
                            $kRevisi,
                        ];
                    } else {
                        // Admin / MD / Non-Staff: ringkasan umum, tanpa antrean
                        // yang memang tak pernah jadi tugasnya.
                        //
                        // Tautan "Total Dokumen" HARUS bercabang: halaman Status
                        // Dokumen Staff menolak Non-Staff dengan 403
                        // (DocumentStaffStatusController: butuh view_all atau
                        // GL/SH/DH), jadi kartu ini dulu memberi Non-Staff sebuah
                        // pintu yang selalu tertutup. Non-Staff diarahkan ke
                        // daftar dokumen departemennya yang memang boleh ia baca.
                        $tautanTotal = $user->dashboardPenuh()
                            ? route('documents.staffStatus')
                            : route('documents.index');
                        $tiles = [
                            ['Total Dokumen', $stats['total'], 'bi-files', $tautanTotal],
                            ['Menunggu Ditinjau', $stats['menunggu_tinjau'], 'bi-hourglass-split', null],
                            $kBerlaku,
                            $kRevisi,
                        ];
                    }
                @endphp
                {{-- Latar selang-seling ORANYE / HITAM. Dulu tiap kartu berwarna
                     sendiri (oranye, biru, kuning, merah muda) — empat warna penuh
                     berdampingan saling berebut perhatian, dan tak satu pun warnanya
                     BERARTI apa-apa. Dua warna bergantian jauh lebih tenang dan
                     sejalan dengan identitas oranye-hitam. --}}
                @foreach ($tiles as $i => [$label, $value, $icon, $url])
                    <div class="col-6 col-lg-3">
                        @if ($url)<a href="{{ $url }}" class="text-decoration-none d-block h-100">@endif
                        {{-- Indeks animasi menyambung dari kartu di atasnya (sambutan 0,
                             overview 1), jadi keempatnya naik berurutan alih-alih
                             serempak — spec v3 R6. --}}
                        <div class="card border-0 shadow-sm h-100 pp-naik {{ $i % 2 === 0 ? 'bg-su-orange' : 'bg-su-dark' }}{{ $url ? ' pp-tile-link' : '' }}"
                             style="--rise:{{ 2 + $i }}">
                            <div class="card-body p-3 d-flex flex-column">
                                {{-- Lingkaran PUTIH dengan glif gelap — perlakuan ikon
                                     yang dipakai referensi di dalam kartu gradasi
                                     (`icon-shape bg-white` + glif `text-dark`). --}}
                                <div class="rounded-circle bg-white shadow-sm mb-2 d-flex align-items-center justify-content-center"
                                     style="width:38px;height:38px">
                                    <i class="bi {{ $icon }}" style="font-size:1.05rem;line-height:1;color:#27272a"></i>
                                </div>
                                <div class="pp-angka text-white">{{ $value }}</div>
                                <span class="text-white mt-auto" style="font-size:.78rem;opacity:.85">{{ $label }}</span>
                                @if ($url)
                                    <span class="pp-cta pp-cta-terang mt-2">Lihat <i class="bi bi-arrow-right"></i></span>
                                @endif
                            </div>
                        </div>
                        @if ($url)</a>@endif
                    </div>
                @endforeach
    </div>

    {{-- ROW 2: Distribusi Dokumen (70) | Sebaran Dokumen (30).
         Dua kartu yang saling menerjemahkan: donat menjawab "jenis apa yang
         banyak", tabel menjawab "yang mana yang belum sampai ke orangnya" —
         keduanya memakai ikon & warna jenis yang SAMA (DocumentType::RUPA).
         Distribusi hanya bagi yang berwenang menindaklanjutinya; bila tak ada,
         Sebaran mengambil lebar penuh, bukan menyisakan kolom kosong. --}}
    <div class="row g-3 mt-1">
        @if ($distribusiWidget)
            <div class="col-xl-8 pp-naik" style="--rise:6">@include('partials._widget-distribusi')</div>
        @endif
        {{-- Animasi ditempel pada KOLOM, bukan kartunya: kartu di dalam sini
             `h-100`, dan elemen ber-animasi yang juga jadi patokan tinggi
             saudaranya lebih mudah dibaca salah oleh peramban. --}}
        <div class="{{ $distribusiWidget ? 'col-xl-4' : 'col-12' }} pp-naik" style="--rise:7">@include('partials._widget-sebaran')</div>
    </div>

    {{-- Baris kartu pill (Perlu Ditinjau / Perlu Disetujui / Akun Menunggu) DIBUANG
         (spec v2 C1): angkanya sudah ada di empat kartu besar di atas, dan dua
         tempat yang menyebut angka yang sama membuat pembaca mengira keduanya
         hal yang berbeda. Antrean "Perlu Ditinjau" tetap dapat sapaan di kartu
         sambutan beserta tombolnya. --}}

    {{-- DUA BARIS BERPASANGAN (spec v2 L1/L2):
         |  Perjalanan Dokumen (70)  |  Ketersediaan Saya / kalender (30)  |
         |  Masukan Lapangan  (70)   |  Log Aktivitas                (30)  |

         Kesejajaran yang diminta L1 — tepi bawah Perjalanan Dokumen jatuh pas
         di batas kalender–Log Aktivitas — datang dari BARIS grid itu sendiri,
         bukan dari tinggi yang ditebak: dua kartu sebaris dengan `h-100`
         selalu berakhir di garis yang sama. Karena itu pula log dipisah jadi
         kartunya sendiri; selama ia menempel di bawah kalender dalam satu
         kartu, "batas" itu tak punya letak yang bisa dijadikan patokan.

         `$duaKolom`: begitu SALAH SATU kartu kiri ada, kedua baris memakai
         70/30 — kalau tidak, kartu kanan melebar penuh dan kesejajarannya
         justru hilang di baris berikutnya. --}}
    @php
        $adaMeja = $menungguDiMeja->isNotEmpty() || ($isCreator ?? false) || ($isDeptHead ?? false) || ($isPjo ?? false)
            || ($isMd ?? false);
        // GL & PJO ikut mendapat kartu Masukan Lapangan — keduanya MEMBACA
        // (GL sedepartemen sebagai penyusun, PJO lintas 7 dept); yang menindak
        // tetap SH/DH departemennya. Kartunya dirender walau kosong, supaya
        // tata letaknya tak berkedip antar-peran.
        $adaMasukan = $masukanWidget->isNotEmpty()
            || $user->jabatan === \App\Models\User::JABATAN_STAFF
            || ($isDeptHead ?? false)
            || ($isPjo ?? false)
            || ($isCreator ?? false)
            // MD ikut: tanpa ini kedua barisnya runtuh jadi satu kolom penuh
            // begitu koleksinya kebetulan kosong, sehingga dashboard MD
            // berganti bentuk dari hari ke hari.
            || ($isMd ?? false);
        $duaKolom = $adaMeja || $adaMasukan;
        $kolomKanan = $duaKolom ? 'col-xl-4' : 'col-12';
    @endphp
    <div class="row g-3 mt-1">
        @if ($adaMeja)
            <div class="col-xl-8">@include('partials._widget-meja')</div>
        @elseif ($duaKolom)
            <div class="col-xl-8"></div>
        @endif
        <div class="{{ $kolomKanan }}">@include('partials._ketersediaan')</div>
    </div>

    <div class="row g-3 mt-1">
        @if ($adaMasukan)
            <div class="col-xl-8">@include('partials._widget-masukan', ['mendatar' => true])</div>
        @elseif ($duaKolom)
            <div class="col-xl-8"></div>
        @endif
        <div class="{{ $kolomKanan }}">@include('partials._widget-log')</div>
    </div>
@endsection

@push('styles')<style>
    /* Titik legenda grafik — dipakai Overview Dokumen & Sebaran Dokumen.
       Legenda ditulis tangan, bukan bawaan Chart.js, supaya ikut tipografi tema. */
    .pp-legenda-titik { width: 9px; height: 9px; border-radius: 50%; flex: 0 0 9px; display: inline-block; }

    /* SATU BARIS + ellipsis, teks utuhnya di atribut `title`. Dipakai Perjalanan
       Dokumen DAN Distribusi Dokumen — judul dokumen mutu bisa 90 karakter, dan
       yang dibiarkan membungkus membuat satu baris setinggi tiga baris.
       Tinggal di sini, bukan di salah satu widget: keduanya bisa muncul sendiri
       tanpa yang lain (lihat $adaMeja / $distribusiWidget), jadi aturan yang
       didorong sekali dari satu widget akan hilang saat widget itu tak
       dirender. */
    .pp-satu-baris { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Pembatas antar-kolom di DALAM satu kartu (pola `row-bordered` SNEAT).
       Mendatar saat kolomnya masih bertumpuk, menegak begitu bersanding. */
    .pp-row-bordered > [class*="col-"] + [class*="col-"] { border-top: 1px solid var(--bs-border-color); }
    @media (min-width: 1200px) {
        .pp-row-bordered > [class*="col-"] + [class*="col-"] { border-top: 0; border-left: 1px solid var(--bs-border-color); }
    }

    /* ── Meter growth: busur 270° ────────────────────────────────────────────
       `pathLength="100"` menormalkan keliling lingkaran jadi 100 satuan,
       sehingga panjang busur = persen × 0,75 tanpa sekali pun menghitung 2πr. */
    .pp-radial { position: relative; width: 170px; max-width: 100%; }
    .pp-radial svg { display: block; width: 100%; height: auto; transform: rotate(135deg); }
    .pp-radial circle { fill: none; stroke-width: 9; stroke-linecap: round; }
    .pp-radial-rel { stroke: var(--bs-border-color); }
    .pp-radial-isi { stroke: url(#ppRadialGrad); transition: stroke-dasharray .4s ease; }
    /* Teks TIDAK ikut diputar — ia saudara <svg>, bukan isinya. */
    .pp-radial-teks {
        position: absolute; inset: 0; display: flex; flex-direction: column;
        align-items: center; justify-content: center; line-height: 1.2;
    }
    .pp-radial-angka { font-size: 1.6rem; font-weight: 700; }
    .text-su-orange { color: #ea580c !important; }
    /* .text-su-blue & .text-su-yellow dibuang: biru sudah keluar dari palet, dan
       kuning ternyata sama persis dengan oranye — dua nama untuk satu warna. */
    /* Timeline gaya Orders overview */
    .pp-timeline { position: relative; }
    .pp-tl-item { position: relative; display: flex; gap: .75rem; padding-bottom: 1.1rem; }
    .pp-tl-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 30px; bottom: 0; width: 2px; background: #eef1f6; }
    .pp-tl-icon { flex: 0 0 auto; width: 30px; height: 30px; border-radius: .6rem; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; box-shadow: 0 2px 5px rgba(0,0,0,.12); }
    .pp-tl-body { padding-top: 2px; }
    [data-bs-theme="dark"] .pp-tl-item:not(:last-child)::before { background: #2b3138; }
    /* Kartu statistik yang jadi tautan (#2): sedikit terangkat saat hover. */
    .pp-tile-link { cursor: pointer; transition: transform .15s ease, box-shadow .15s ease; }
    .pp-tile-link:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.18) !important; }

    /* Gaya "Perjalanan Dokumen" (rel bersegmen + umur) IKUT PINDAH ke
       partials/_widget-meja saat kartunya jadi tabel — gaya menempel pada
       partial pemakainya, bukan pada halaman ini. */

    /* ── Masukan: kutipan ───────────────────────────────────────────────── */
    /* Tubuh kartu masukan MENGGULUNG di dalam kartunya, bukan memanjangkan
       kartunya. Dimensi kartu dikunci pemilik, jadi kelebihan isi harus pergi
       ke sumbu gulir — bukan ke tinggi.

       `min-height:0` WAJIB dan bukan hiasan: `.card` adalah flex kolom dan
       `.card-body` ber-`flex:1 1 auto`, sedangkan bawaan `min-height` anak flex
       adalah `auto` — ia MENOLAK menyusut di bawah tinggi isinya, sehingga
       `overflow-y` tak pernah aktif dan kartunya memanjang seperti semula.
       Ini jebakan flexbox yang paling sering menyita waktu; sekali ditulis
       di sini, tak perlu ditemukan ulang. */
    .pp-kutip-tubuh { overflow-y: auto; min-height: 0; }

    .pp-kutip { padding-bottom: .85rem; }
    .pp-kutip + .pp-kutip { border-top: 1px solid var(--bs-border-color); padding-top: .85rem; }
    /* Ragam MENDATAR: tiap kutipan dikurung kotaknya sendiri. Garis pemisah
       `.pp-kutip + .pp-kutip` tak berlaku lagi di sini — dalam grid tiap kutipan
       terbungkus `.col` sendiri sehingga bukan lagi saudara sebelah. */
    .pp-kutip-kotak {
        height: 100%; padding: .7rem .8rem;
        border: 1px solid var(--bs-border-color); border-radius: .6rem;
    }

    .pp-kutip-isi {
        display: block; position: relative; padding-left: .95rem;
        font-size: .82rem; line-height: 1.45; color: var(--bs-body-color);
    }
    /* Tanda kutip GANTUNG — di luar alur teks, jadi barisnya tetap rata kiri. */
    .pp-kutip-isi::before {
        content: '\201C'; position: absolute; left: 0; top: -.25rem;
        font-size: 1.5rem; line-height: 1; color: var(--su-warn1);
    }
    a.pp-kutip-isi:hover { color: var(--su-orange1); }
    .pp-kutip-atribusi { padding-left: .95rem; margin-top: .25rem; font-size: .7rem; color: #8392ab; }
    .pp-kutip-balasan {
        margin: .4rem 0 0 .95rem; padding: .45rem .6rem;
        border-left: 3px solid var(--su-info1); border-radius: 0 .4rem .4rem 0;
        background: var(--bs-tertiary-bg); font-size: .76rem; line-height: 1.4;
    }
</style>@endpush

@push('scripts')
{{--
| Data grafik dititipkan sebagai JSON MURNI, bukan direktif JSON Blade di
| tengah kode JavaScript.
|
| Alasannya bukan gaya: isi blok skrip dibaca sebagai JavaScript oleh editor,
| dan direktif Blade di tengah ekspresi terbaca sebagai DEKORATOR — dua
| kesalahan palsu per pemakaian (empat di berkas ini) yang menenggelamkan
| kesalahan sungguhan.
| Blok type="application/json" tak dieksekusi maupun diurai sebagai JavaScript,
| jadi editornya diam dan kodenya tetap JavaScript yang sah dari baris pertama.
|
| Bendera 15 = HEX_TAG|HEX_APOS|HEX_QUOT|HEX_AMP: tanda kurang-dari ikut
| di-escape, sehingga nama jenis dokumen yang (entah bagaimana) memuat penutup
| tag skrip tak bisa menutup blok ini lebih awal.
|
| CATATAN: jangan menulis tag skrip UTUH di dalam komentar Blade seperti ini —
| pengurai HTML editor tak peduli ia di dalam komentar, ia tetap membuka konteks
| JavaScript dan seluruh baris sesudahnya jadi merah.
--}}
<script id="ppDataDasbor" type="application/json">@json(['tren' => $tren, 'sebaran' => $sebaran], 15)</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
{{--
| ApexCharts HANYA untuk donat Sebaran (spec v3 R2). Ya, ini pustaka grafik
| KEDUA di satu halaman — keputusan sadar, bukan kelalaian: yang diminta adalah
| konfigurasi "Order Statistics" sneat disalin apa adanya, dan konfigurasi itu
| ApexCharts. Menerjemahkannya ke Chart.js (yang dilakukan spec v2 W3) selalu
| menyisakan selisih perilaku yang tak bisa dibuktikan hilang.
| Grafik Overview tetap Chart.js; memindahkannya juga berarti menyentuh kode
| yang tak disebut spec mana pun.
--}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
<script>
    (function () {
        const ppData = JSON.parse(document.getElementById('ppDataDasbor').textContent);

        /*
         * Menunggu animasi masuk kartu selesai (spec v3 R6 & R2a).
         *
         * Selama .pp-naik berjalan, kartunya punya `transform` — dan lapisan
         * yang ditransformasi dirasterkan terpisah, jadi apa pun yang digambar
         * ke dalamnya pada saat itu ikut buram. Grafik baru digambar setelah
         * animasinya usai, saat kartunya sudah duduk di posisi akhirnya.
         *
         * Kalau kartunya memang tak beranimasi (prefers-reduced-motion, atau
         * elemen tanpa .pp-naik), langsung gambar. Batas waktu 1,2 detik jadi
         * jaring pengaman supaya grafik mustahil tak pernah muncul hanya karena
         * satu peristiwa animationend tak terkirim.
         */
        const saatTenang = (el, gambar) => {
            const kartu = el.closest('.pp-naik');
            if (!kartu || getComputedStyle(kartu).animationName === 'none') return gambar();

            let sudah = false;
            const sekali = () => { if (!sudah) { sudah = true; gambar(); } };
            kartu.addEventListener('animationend', sekali, { once: true });
            setTimeout(sekali, 1200);
        };

        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.font.size = 11;

        // Semua grafik duduk di kartu PUTIH, jadi semuanya ikut tema halaman.
        const grafik = [];

        const gelap = () => document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const warnaSumbu = () => (gelap() ? '#8b98b3' : '#9aa4b2');
        const warnaGaris = () => (gelap() ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)');

        const tooltipTema = {
            backgroundColor: '#344767', titleColor: '#fff', bodyColor: '#fff',
            padding: 10, cornerRadius: 8, boxPadding: 4,
        };

        /* ---------- Overview Dokumen: dibuat vs berlaku ----------
         * Pengenal di bawah (grafikIkhtisar/elIkhtisar/chartIkhtisar) sengaja
         * TIDAK ikut diganti: spec v3 R5 mengubah JUDUL yang dibaca orang,
         * bukan nama variabel, dan menggantinya menyeret diff ke tempat yang
         * tak diminta tanpa satu pun perilaku berubah.
         */
        const elIkhtisar = document.getElementById('grafikIkhtisar');
        if (elIkhtisar) {
            const ctx = elIkhtisar.getContext('2d');
            // Gradasi lembut di bawah tiap garis, mengikuti bentuk "Sales overview"
            // pada referensi Soft UI.
            const gradasi = (r, g, b) => {
                const grad = ctx.createLinearGradient(0, 0, 0, 280);
                grad.addColorStop(0, `rgba(${r},${g},${b},.28)`);
                grad.addColorStop(1, `rgba(${r},${g},${b},0)`);
                return grad;
            };
            const deret = (label, data, warna, grad) => ({
                label, data,
                borderColor: warna, borderWidth: 3, backgroundColor: grad, fill: true,
                tension: .4, pointRadius: 0, pointHoverRadius: 5,
                pointHoverBackgroundColor: warna, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
            });

            // Ketiga durasi sudah ikut halaman (lihat blok JSON di atas);
            // berganti durasi hanya menukar larik, tak ada permintaan ke server.
            const tren = ppData.tren;
            const awal = tren.bulan;

            const chartIkhtisar = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: awal.labels,
                    datasets: [
                        // Oranye = dibuat, hijau = berlaku. Hijau ini warna yang sama
                        // dengan badge status "Berlaku", jadi maknanya sudah dikenal.
                        deret('Dibuat', awal.dibuat, '#f97316', gradasi(249, 115, 22)),
                        deret('Berlaku', awal.berlaku, '#22c55e', gradasi(34, 197, 94)),
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },   // legenda ditulis tangan di header kartu
                        tooltip: { ...tooltipTema, callbacks: { label: c => ` ${c.dataset.label}: ${c.parsed.y} dokumen` } },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: warnaSumbu(), padding: 10 },
                            grid: { color: warnaGaris(), borderDash: [4, 4], drawBorder: false, drawTicks: false },
                        },
                        x: {
                            ticks: { color: warnaSumbu(), padding: 8, autoSkip: true, maxTicksLimit: 8 },
                            grid: { display: false, drawBorder: false },
                        },
                    },
                },
            });
            grafik.push(chartIkhtisar);

            // Spec v3 R6: gambar ulang setelah animasi masuk selesai. Kanvas ini
            // dibuat saat kartunya masih bergerak, dan lapisan yang ditransformasi
            // dirasterkan sendiri — hasilnya garis yang sedikit kabur sampai ada
            // yang memicu gambar ulang. `resize()` melakukan itu tanpa membangun
            // ulang grafiknya.
            saatTenang(elIkhtisar, () => chartIkhtisar.resize());

            /*
             * Pemilih durasi. Ada DUA tombolnya — di kepala Overview dan di atas
             * meter growth — tetapi hanya SATU keadaan: keduanya memanggil
             * fungsi yang sama, sehingga grafik dan meter mustahil menampilkan
             * rentang waktu yang berbeda.
             */
            const namaDurasi = { hari: 'Harian', minggu: 'Mingguan', bulan: 'Bulanan' };
            const isi = (nama, teks) => document
                .querySelectorAll(`[data-ikhtisar="${nama}"], [data-growth="${nama}"]`)
                .forEach(el => { el.textContent = teks; });

            const pilihDurasi = (kunci) => {
                const d = tren[kunci];
                if (!d) return;
                chartIkhtisar.data.labels = d.labels;
                chartIkhtisar.data.datasets[0].data = d.dibuat;
                chartIkhtisar.data.datasets[1].data = d.berlaku;
                chartIkhtisar.update();

                isi('ringkas', `${d.berlakuTotal} dari ${d.total}`);
                isi('rasio', d.total > 0 ? `(${Math.round(d.berlakuTotal / d.total * 100)}%)` : '');
                isi('rentang', d.rentang);
                isi('persen', `${d.growth}%`);
                isi('tertinjau', d.tertinjau);
                isi('total', d.total);
                isi('dibuat', d.total);
                isi('berlaku', d.berlakuTotal);
                document.querySelectorAll('[data-growth="busur"]')
                    .forEach(el => el.setAttribute('stroke-dasharray', `${d.growth * 0.75} 100`));
                document.querySelectorAll('[data-durasi-label]')
                    .forEach(el => { el.textContent = namaDurasi[kunci]; });
            };

            document.querySelectorAll('[data-durasi]').forEach(el => el.addEventListener('click', (e) => {
                e.preventDefault();
                pilihDurasi(el.dataset.durasi);
            }));
        }

        /* ---------- Sebaran Dokumen: donat per jenis (ApexCharts) ----------
         *
         * Konfigurasi donat "Order Statistics" sneat DISALIN APA ADANYA (spec
         * v3 R2b): stroke 5, states hover/active `filter: none`, dataLabels
         * mati, legend mati, grid padding, lubang 75%, dan susunan label tengah
         * (value offsetY -15, name offsetY 20, total show). Yang diganti cuma
         * WARNA, angka, dan nama fon.
         *
         * `dataLabels` membawa `formatter` padahal `enabled: false` — itu memang
         * begitu di sumbernya. Dibiarkan supaya perbandingan baris-per-baris
         * dengan sneat tetap bisa dilakukan; menghapusnya membuat "disalin apa
         * adanya" jadi klaim yang tak bisa diperiksa lagi.
         *
         * `tooltip` sengaja TIDAK disetel: sneat pun tak menyetelnya untuk donat
         * ini. Perilaku bawaan ApexCharts-lah yang diminta — menyorot satu ruas
         * memunculkan tooltip ruas ITU saja, tanpa ruasnya berubah rupa
         * (itu tugas `states.filter: none` di atas).
         */
        const elSebaran = document.getElementById('grafikSebaran');
        let chartSebaran = null;
        if (elSebaran && window.ApexCharts) {
            const kodeSebaran = Object.keys(ppData.sebaran);
            const nilaiSebaran = kodeSebaran.map(k => ppData.sebaran[k].jumlah);
            const jumlahSebaran = nilaiSebaran.reduce((a, b) => a + b, 0);
            const persenSebaran = (n) => (jumlahSebaran > 0 ? Math.round(n / jumlahSebaran * 100) : 0) + '%';

            // Jenis TERBANYAK — itulah yang tertulis di tengah lubang saat tak
            // ada ruas yang disorot, bentuk yang sama dengan "38% / Weekly".
            const puncak = kodeSebaran[nilaiSebaran.indexOf(Math.max(...nilaiSebaran))];

            // Garis pemisah ruas HARUS sewarna kartunya, dan itu dibaca dari
            // kartu sungguhan — bukan ditebak dari --bs-body-bg, yang di tema ini
            // kelabu sementara kartunya putih.
            const kartu = elSebaran.closest('.card');
            const warnaKartu = () => getComputedStyle(kartu).backgroundColor;
            const warnaJudul = () => getComputedStyle(document.body).color;

            const opsiSebaran = () => ({
                chart: { height: 165, width: 130, type: 'donut', fontFamily: 'Poppins, sans-serif' },
                labels: kodeSebaran,
                series: nilaiSebaran,
                colors: kodeSebaran.map(k => ppData.sebaran[k].warna),
                stroke: { width: 5, colors: [warnaKartu()] },
                dataLabels: { enabled: false, formatter: (val) => parseInt(val, 10) + '%' },
                legend: { show: false },
                grid: { padding: { top: 0, bottom: 0, right: 15 } },
                states: {
                    hover: { filter: { type: 'none' } },
                    active: { filter: { type: 'none' } },
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                value: {
                                    fontSize: '1.5rem', fontFamily: 'Poppins, sans-serif',
                                    color: warnaJudul(), offsetY: -15,
                                    formatter: (val) => persenSebaran(Number(val)),
                                },
                                name: { offsetY: 20, fontFamily: 'Poppins, sans-serif' },
                                total: {
                                    show: true, fontSize: '.8125rem', color: '#8392ab',
                                    label: puncak,
                                    formatter: () => persenSebaran(ppData.sebaran[puncak].jumlah),
                                },
                            },
                        },
                    },
                },
            });

            chartSebaran = new ApexCharts(elSebaran, opsiSebaran());
            saatTenang(elSebaran, () => chartSebaran.render());

            // Tema ditukar tanpa memuat ulang halaman: garis pemisah ruas sewarna
            // KARTU dan angka tengah sewarna TEKS BADAN — dua-duanya berubah.
            elSebaran.ppSegarkanTema = () => chartSebaran.updateOptions({
                stroke: { colors: [warnaKartu()] },
                plotOptions: { pie: { donut: { labels: { value: { color: warnaJudul() } } } } },
            }, false, false);
        }

        /*
         * Tema bisa ditukar tanpa memuat ulang halaman, sedangkan warna sumbu &
         * garis bantu sudah "terbakar" ke dalam grafik saat dibuat. Diamati dari
         * atribut `data-bs-theme` alih-alih menempel ke ppToggleTheme(), supaya
         * tetap benar dari mana pun tema diubah.
         */
        new MutationObserver(() => {
            grafik.forEach(g => {
                Object.values(g.options.scales).forEach(s => {
                    s.ticks.color = warnaSumbu();
                    if (s.grid.color) s.grid.color = warnaGaris();
                });
                g.update('none');
            });
            // Donat tak punya sumbu, tapi garis pemisah ruasnya sewarna KARTU —
            // dan warna kartu itulah yang berubah saat tema ditukar. Ia
            // ApexCharts, bukan Chart.js, jadi penyegarannya dititipkan pada
            // elemennya sendiri (lihat blok Sebaran di atas).
            if (elSebaran && elSebaran.ppSegarkanTema) elSebaran.ppSegarkanTema();
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    })();
</script>
@endpush
