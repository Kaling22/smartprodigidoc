{{--
| DISTRIBUSI — tabel gaya "Projects" (soft_ui): dokumen · anggota · unduhan ·
| cakupan.
|
| DUA SUMBER sejak Fase D: Dokumen Mutu dan menu Informasi. Keduanya dirender
| server-side dan ditukar tombol (`x-show`) — nol request tambahan dan nol
| kedipan; datanya toh cuma dua GROUP BY per sumber, jadi memuatnya lewat
| permintaan kedua hanya menambah kode dan penantian.
|
| Yang diurutkan tetap CAKUPAN TERENDAH, bukan yang terbaru: kartu ini gunanya
| menemukan yang belum sampai ke orangnya. Isi tabelnya di
| `partials/_widget-distribusi-tabel` — satu markup untuk kedua sumber.
--}}
@php
    $panelMutu = $distribusiWidget['mutu'];
    $panelInformasi = $distribusiWidget['informasi'];
    // Panel awal = yang benar-benar ada isinya. Membuka di panel kosong membuat
    // kartunya tampak rusak padahal sumber satunya penuh.
    $awal = $panelMutu ? 'mutu' : 'informasi';
    $panelAda = array_filter(['mutu' => $panelMutu, 'informasi' => $panelInformasi]);
@endphp
<div class="card border-0 shadow-sm h-100" x-data="{ sumber: '{{ $awal }}' }">
    <div class="card-header bg-transparent pb-0 d-flex justify-content-between align-items-start gap-2">
        <div>
            <h6 class="fw-bold mb-1">Distribusi Dokumen</h6>
            <p class="text-sm text-muted mb-0">
                @foreach ($panelAda as $kunci => $panel)
                    <span x-show="sumber === '{{ $kunci }}'" @if ($kunci !== $awal) x-cloak @endif>
                        @if ($panel['rendah'] > 0)
                            <span class="fw-bold text-body">{{ $panel['rendah'] }} {{ $kunci === 'mutu' ? 'dokumen' : 'informasi' }}</span>
                            belum terbaca separuh sasarannya
                        @else
                            Semua sudah terbaca lebih dari separuh sasarannya
                        @endif
                    </span>
                @endforeach
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            {{-- Saklar sumber hanya muncul bila memang ADA dua sumber: tombol
                 yang menuju panel kosong cuma menjebak. --}}
            @if (count($panelAda) > 1)
                <div class="btn-group btn-group-sm" role="group" aria-label="Sumber distribusi">
                    <button type="button" class="btn btn-outline-secondary" @click="sumber = 'mutu'"
                            :class="sumber === 'mutu' ? 'active' : ''">Dokumen Mutu</button>
                    <button type="button" class="btn btn-outline-secondary" @click="sumber = 'informasi'"
                            :class="sumber === 'informasi' ? 'active' : ''">Informasi</button>
                </div>
            @endif
            {{-- Tombolnya menghilang bagi yang tak boleh membuka halaman Distribusi
                 (GL & Non-Staff): tautan yang berujung 403 lebih buruk daripada tak
                 ada tautan. Syaratnya SAMA dengan penjaga controllernya. --}}
            @if (auth()->user()->bisaLihatDistribusi())
                <a :href="sumber === 'informasi'
                        ? '{{ route('documents.distribution', ['sumber' => 'informasi']) }}'
                        : '{{ route('documents.distribution') }}'"
                   href="{{ route('documents.distribution') }}" class="btn btn-sm btn-outline-secondary">
                    Lihat semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>
    {{-- Digulir di dalam kartunya (spec v3 R3): kepala tabel DIPAKU, badannya
         yang bergerak. Resep tingginya sama dengan Log Aktivitas: memuai
         mengisi sisa tinggi kartu, `min-height:0` supaya benar-benar boleh
         menyusut, `max-height` menjaga kasus saat tetangga barisnya pendek.

         Batang gulirnya `.pp-gulir-tipis`, bukan `.pp-gulir` yang meniadakannya:
         begitu kepala tabel berhenti ikut bergerak, tanpa batang gulir tak ada
         satu pun isyarat bahwa masih ada baris di bawah. --}}
    <div class="card-body px-0 pb-2 pp-gulir-tipis pp-dist-isi">
        @foreach ($panelAda as $kunci => $panel)
            <div x-show="sumber === '{{ $kunci }}'" @if ($kunci !== $awal) x-cloak @endif>
                @include('partials._widget-distribusi-tabel', ['panel' => $panel])
            </div>
        @endforeach
    </div>
</div>

@once
    @push('styles')<style>
        /* `fixed` menghormati <colgroup> apa pun isinya — tanpa itu kolom Dokumen
           diukur dari judul terpanjang dan menggencet kolom lain. Resep sama
           dengan .pp-meja-tabel di Perjalanan Dokumen. */
        .pp-dist-tabel { table-layout: fixed; width: 100%; }

        /* `overflow-wrap: anywhere` DIBUANG: judul kini satu baris ber-ellipsis
           (.pp-satu-baris, di dashboard.blade.php), dan membungkus di mana saja
           justru yang dulu memanjangkan barisnya jadi tiga baris. */
        .pp-dist-judul {
            font-size: .82rem; font-weight: 600; color: var(--bs-body-color);
            line-height: 1.3;
        }
        .pp-dist-judul:hover { color: var(--su-orange1); }
        .pp-dist-isi { flex: 1 1 auto; min-height: 0; max-height: 26rem; }

        /* Kepala tabel dipaku ke puncak wadah gulirnya (spec v3 R3).
           `position: sticky` menempel pada leluhur ber-overflow terdekat —
           itulah `.pp-dist-isi` di atas, jadi tak ada JavaScript dan tak ada
           tabel kedua yang harus dijaga tetap selebar tabel aslinya.

           Latar WAJIB pekat: aturan tabel global menyetel `background:
           transparent !important` pada th, dan tanpa ditimpa di sini
           baris-baris dokumen terbaca menembus tulisan kepalanya. */
        .pp-dist-isi thead th {
            position: sticky; top: 0; z-index: 2;
            background: #fff !important;
        }
        [data-bs-theme="dark"] .pp-dist-isi thead th { background: #1f2428 !important; }

        /* Kotak ikon jenis — dipakai tabel Distribusi DAN daftar Sebaran, jadi
           ukurannya satu supaya dua kartu bersebelahan tak berbeda irama. */
        .pp-jenis-ikon {
            flex: 0 0 auto; width: 34px; height: 34px; border-radius: .55rem;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1rem; line-height: 1;
        }

        /* Gaya tumpukan wajah ada di partials/_avatar — satu bentuk avatar untuk
           seluruh dashboard, jadi kolom Anggota di sini dan kolom Pembuat di
           Perjalanan Dokumen mustahil berbeda rupa. */
    </style>@endpush
@endonce
