{{--
| SEBARAN DOKUMEN — donat per JENIS, gaya "Order Statistics" (sneat).
|
| Dulu kartu ini batang bertumpuk status × jenis. Bentuk itu menjawab dua
| pertanyaan sekaligus dan karena itu tak menjawab satu pun dengan cepat;
| komposisi status sudah punya tempatnya sendiri di matriks. Yang tersisa di
| sini pertanyaan yang sederhana: dari seluruh dokumen, berapa bagian tiap
| jenis.
|
| Warna & ikon per jenis dari DocumentType::RUPA — sama persis dengan tabel
| Distribusi di sebelahnya, jadi donat dan tabel saling menerjemahkan.
--}}
@php $totalSebaran = array_sum(array_column($sebaran, 'jumlah')); @endphp

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent pb-0">
        <h6 class="fw-bold mb-1">Sebaran Dokumen</h6>
        <p class="text-sm text-muted mb-0">Komposisi dokumen berlaku menurut jenisnya</p>
    </div>
    <div class="card-body pt-3">
        @if ($totalSebaran === 0)
            <div class="text-center text-muted py-4">
                <i class="bi bi-pie-chart fs-1 d-block mb-2 opacity-50"></i>
                Belum ada dokumen berlaku untuk dipetakan.
            </div>
        @else
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <div class="pp-angka-besar">{{ number_format($totalSebaran, 0, ',', '.') }}</div>
                    <div class="text-xs text-muted">Total Dokumen Berlaku</div>
                </div>
                {{-- Wadah KOSONG: sejak spec v3 R2 donat ini digambar ApexCharts,
                     dan dimensinya (130 × 165, sama dengan "Order Statistics"
                     sneat) datang dari `chart.width/height` di konfigurasinya —
                     bukan dari gaya inline di sini. Ia pembanding proporsi, bukan
                     bacaan angka; angkanya ada di daftar tepat di bawahnya. --}}
                <div id="grafikSebaran"></div>
            </div>

            <ul class="list-unstyled mb-0">
                @foreach ($sebaran as $kode => $s)
                    <li class="d-flex align-items-center mb-3">
                        <span class="pp-jenis-ikon me-3" style="background:{{ $s['warna'] }}1f;color:{{ $s['warna'] }}">
                            <i class="bi {{ $s['ikon'] }}"></i>
                        </span>
                        <div class="d-flex w-100 align-items-center justify-content-between gap-2">
                            <div>
                                <h6 class="mb-0 text-sm fw-semibold">{{ $kode }}</h6>
                                <span class="text-xs text-muted">{{ $s['nama'] }}</span>
                            </div>
                            <h6 class="mb-0 fw-bold">{{ $s['jumlah'] }}</h6>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@once
    @push('styles')<style>
        .pp-angka-besar { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
    </style>@endpush
@endonce
