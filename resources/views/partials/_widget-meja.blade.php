{{--
| "Perjalanan Dokumen" — tabel gaya "Pending Requests" (staradmin).
|
| Isinya dokumen yang SEDANG BERJALAN beserta berapa lama ia diam. Di kolom 70%
| ada ruang untuk menyebut SIAPA yang menunggu (pembuatnya), dan itu yang
| membuat baris ini bisa ditindaklanjuti: nama lebih menggerakkan daripada
| nomor dokumen.
|
| Umur tetap jadi kabar utamanya, sebagai badge lunak yang menghangat sendiri:
| <3 hari hijau, 3-6 kuning, >=7 merah.
|
| TEPAT 4 baris (dibatasi DashboardController::menungguDiMeja) dan TANPA gulir
| mendatar: lebar kolom dipatok lewat `table-layout: fixed` + <colgroup>, jadi
| tabelnya mustahil melebihi kartunya betapapun panjang judul dokumennya.
--}}
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent pb-0 d-flex justify-content-between align-items-start gap-2">
        <div>
            <h6 class="fw-bold mb-1">Perjalanan Dokumen</h6>
            <p class="text-sm text-muted mb-0">
                @if ($menungguDiMeja->isNotEmpty())
                    <span class="fw-bold text-body">{{ $menungguDiMeja->count() }} dokumen</span> sedang berjalan
                @else
                    Tak ada dokumen yang sedang berjalan
                @endif
            </p>
        </div>
    </div>
    <div class="card-body px-0 pb-2">
        @if ($menungguDiMeja->isEmpty())
            <div class="text-center text-muted py-4">
                <i class="bi bi-hourglass fs-1 d-block mb-2 opacity-50"></i>
                <div class="text-sm">Tak ada dokumen yang sedang berjalan.</div>
                <div class="text-xs">Dokumen yang sudah dikirim muncul di sini beserta umurnya.</div>
            </div>
        @else
            <div>
                <table class="table align-items-center mb-0 pp-meja-tabel">
                    {{-- Lebar dipatok di sini, bukan diserahkan ke isi: itulah yang
                         menjamin tak ada gulir mendatar (spec v2 L1). --}}
                    {{-- Kemajuan melebar karena isinya kini KALIMAT ("Menunggu
                         verifikasi MD"), bukan angka persen. --}}
                    <colgroup>
                        <col style="width:27%"><col style="width:28%"><col style="width:30%"><col style="width:15%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7 ps-4">Pembuat</th>
                            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7">Dokumen</th>
                            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7">Kemajuan</th>
                            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7 pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($menungguDiMeja as $baris)
                            @php
                                $d = $baris['dokumen'];
                                $p = $d->creator;

                                // Tahap hanya memajang yang BENAR-BENAR dilalui jenis ini:
                                // segmen MD muncul cuma bila Document::perluTinjauanMd()
                                // benar (saat ini SOP), sehingga JSA tak pernah dijanjikan
                                // tahap yang tak ada dalam alurnya.
                                $tahap = array_values(array_filter([
                                    'Dibuat', 'Ditinjau', $d->perluTinjauanMd() ? 'MD' : null, 'Disetujui',
                                ]));
                                // Tahap & kalimatnya datang dari SATU sumber
                                // (DashboardController::menungguDiMeja), jadi pita dan
                                // teks di atasnya mustahil bercerita berbeda.
                                $sekarang = $baris['tahap'];
                                $menunggu = $baris['menunggu'];
                                $i = array_search($sekarang, $tahap, true);
                                $ke = ($i === false ? 1 : $i) + 1;
                                $persen = (int) round($ke / count($tahap) * 100);

                                $umur = $baris['umur'];
                                $tingkat = $umur >= 7 ? 'lama' : ($umur >= 3 ? 'sedang' : 'baru');
                                // Rona badge umur menumpang varian .badge-soft yang sama
                                // dengan seluruh aplikasi (spec v3 R1), jadi "7 hari" di
                                // sini merah dengan cara yang sama seperti status Ditolak.
                                $ronaUmur = ['baru' => 'success', 'sedang' => 'warning', 'lama' => 'danger'][$tingkat];
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center py-1">
                                        @include('partials._avatar', ['orang' => $p])
                                        {{-- `min-width:0` wajib pada anak flex: tanpa itu ia
                                             menolak menyusut di bawah lebar teksnya dan
                                             ellipsis di dalamnya tak pernah aktif. --}}
                                        <div class="ms-2" style="min-width:0">
                                            <h6 class="mb-0 text-sm fw-semibold pp-satu-baris" title="{{ $p->name ?? '' }}">
                                                {{ $p->name ?? 'Tidak diketahui' }}
                                            </h6>
                                            <span class="text-xs text-muted pp-satu-baris">
                                                {{ \App\Models\User::JABATAN_LABELS[$p->jabatan ?? ''] ?? '—' }}
                                                @if ($d->department) · {{ $d->department->code }} @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{-- Satu baris + ellipsis, judul lengkapnya di `title`:
                                         judul dokumen mutu bisa 90 karakter, dan membiarkannya
                                         membungkus membuat baris ini setinggi tiga baris
                                         sementara tiga baris lain hanya dua. --}}
                                    <a href="{{ route('documents.show', $d) }}" class="pp-meja-judul pp-satu-baris text-decoration-none"
                                       title="{{ $d->title }}">{{ $d->title }}</a>
                                    <span class="text-xs text-muted font-monospace pp-satu-baris">{{ $d->displayNumber() }}</span>
                                </td>
                                <td>
                                    {{-- Di atas pita: MENUNGGU APA, bukan persen. Persennya
                                         tak pernah menjawab pertanyaan siapa pun — panjang
                                         pita sudah mengatakan hal yang sama, sedangkan
                                         "menunggu persetujuan" langsung memberi tahu apa
                                         yang harus terjadi berikutnya. --}}
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                        <span class="text-xs fw-semibold pp-teks-{{ $tingkat }} pp-satu-baris"
                                              title="{{ $menunggu }}@if ($baris['pemegang']) · {{ $baris['pemegang'] }}@endif">{{ $menunggu }}</span>
                                        <span class="text-xs text-muted flex-shrink-0">{{ $ke }}/{{ count($tahap) }}</span>
                                    </div>
                                    <div class="pp-progres" title="{{ implode(' → ', $tahap) }} · sekarang: {{ $sekarang }}">
                                        <span class="bg-su-orange" style="width:{{ $persen }}%"></span>
                                    </div>
                                </td>
                                <td class="pe-4">
                                    <span class="badge-soft badge-soft-{{ $ronaUmur }}">
                                        {{ $umur < 1 ? 'hari ini' : $umur.' hari' }}
                                    </span>
                                    {{-- Badge status (ikon jam pasir dsb.) DIBUANG dari sini:
                                         kolom Kemajuan sudah menyebut status itu dengan kata-kata
                                         ("Menunggu tinjauan SH"), jadi ikonnya cuma mengulang
                                         hal yang sama dengan bahasa yang lebih sulit. Yang
                                         tersisa: umurnya, satu-satunya kabar yang belum
                                         disebut kolom mana pun. --}}
                                    @if ($baris['pemegang'])
                                        <span class="text-xs text-muted d-block mt-1 pp-satu-baris"
                                              title="{{ $baris['pemegang'] }}">{{ $baris['pemegang'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@once
    @push('styles')<style>
        /* `fixed` menghormati <colgroup> apa pun isinya — judul panjang MEMBUNGKUS
           alih-alih melebarkan tabel. Tanpa ini `table-layout: auto` mengukur
           dari isi dan kartunya menumbuhkan gulir mendatar sendiri. */
        .pp-meja-tabel { table-layout: fixed; width: 100%; }
        .pp-meja-tabel td, .pp-meja-tabel th { overflow-wrap: anywhere; }

        .pp-meja-judul { font-size: .82rem; font-weight: 600; color: var(--bs-body-color); line-height: 1.3; }
        .pp-meja-judul:hover { color: var(--su-orange1); }

        /* `.pp-satu-baris` (satu baris + ellipsis) PINDAH ke dashboard.blade.php:
           Distribusi Dokumen memakainya juga, dan kedua kartu bisa muncul tanpa
           yang lain — aturan yang didorong sekali dari sini akan hilang di
           halaman yang merender Distribusi tanpa Perjalanan Dokumen. */

        /* Baris dirapatkan: empat baris harus selesai setinggi kartu kalender
           di sebelahnya (spec v2 L1). */
        .pp-meja-tabel > tbody > tr > td { padding-top: .55rem; padding-bottom: .55rem; }

        .pp-progres { height: .45rem; border-radius: .45rem; background: var(--bs-secondary-bg); overflow: hidden; }
        .pp-progres > span { display: block; height: 100%; border-radius: .45rem; transition: width .3s ease; }

        /* .pp-badge-soft & ketiga varian umurnya DIBUANG (spec v3 R1): badge
           lunak sekarang punya satu kelas untuk seluruh aplikasi, .badge-soft
           di layouts/app. Yang tersisa cuma warna TEKS tingkat umur, yang
           dipakai judul kolom Kemajuan — itu bukan badge. */
        .pp-teks-baru { color: var(--su-hijau2); }
        .pp-teks-sedang { color: var(--su-warn2); }
        .pp-teks-lama { color: var(--su-danger2); }
    </style>@endpush
@endonce
