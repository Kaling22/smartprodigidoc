@extends('layouts.app')
@section('title', \App\Models\Informasi::labelKategori($kategori))

@php
    $label = \App\Models\Informasi::labelKategori($kategori);
    $kolom = \App\Models\Informasi::kolomEkstra($kategori);
    $kelola = auth()->user()->can('informasi.manage');
    // Kolom tabel mengikuti KATEGORI, sama seperti formulirnya: menampilkan
    // kolom Edisi/Revisi pada MEMO hanya akan berisi strip di seluruh baris.
    $adaRevisi = in_array('edisi', $kolom, true);
    $adaTanggal = in_array('tanggal_efektif', $kolom, true);
    // 1 pelipat + nomor + judul + diunggah + aksi, plus kolom bersyarat.
    $jumlahKolom = 5 + ($adaRevisi ? 1 : 0) + ($adaTanggal ? 1 : 0);
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0"><i class="bi {{ \App\Models\Informasi::ikon($kategori) }}"></i> {{ $label }}</h1>
            <p class="text-muted small mb-0">
                Dokumen informasi yang berlaku — terbuka untuk semua departemen dan semua jabatan.
                Versi lama tersimpan sebagai riwayat di bawah barisnya masing-masing.
            </p>
        </div>
        @if ($kelola)
            <a href="{{ route('informasi.create', ['kategori' => $kategori]) }}" class="btn btn-pp">
                <i class="bi bi-upload"></i> Tambah {{ $label }}
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="d-flex gap-1 justify-content-end">
                <input type="hidden" name="kategori" value="{{ $kategori }}">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm"
                       style="max-width:22rem" placeholder="Cari nomor atau judul...">
                <button class="btn btn-sm btn-input-h btn-secondary"><i class="bi bi-search"></i> Cari</button>
                <a href="{{ route('informasi.index', ['kategori' => $kategori]) }}"
                   class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:2.5rem"></th>
                            <th>Nomor</th>
                            <th>Judul</th>
                            @if ($adaRevisi)<th class="text-center">Edisi / Rev</th>@endif
                            @if ($adaTanggal)<th>Tgl Efektif</th>@endif
                            <th>Diunggah</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    {{-- SATU <tbody> per dokumen — HTML5 memang membolehkan
                         beberapa tbody dalam satu tabel, dan itulah yang membuat
                         baris riwayat bisa berbagi satu state Alpine dengan
                         induknya tanpa membungkus <tr> di dalam <tr> (yang tak
                         sah dan diperbaiki-paksa oleh peramban). --}}
                    @forelse ($daftar as $info)
                        @php $versiLama = $riwayat[$info->nomor] ?? collect(); @endphp
                        <tbody x-data="{ buka: false }">
                            <tr>
                                <td class="text-center">
                                    @if ($versiLama->isNotEmpty())
                                        <button type="button" class="btn btn-sm btn-link p-0 text-muted"
                                                @click="buka = !buka" :aria-expanded="buka.toString()"
                                                :title="buka ? 'Sembunyikan riwayat' : 'Lihat {{ $versiLama->count() }} versi lama'">
                                            <i class="bi bi-chevron-right pp-lipat" :class="buka && 'pp-lipat-buka'"></i>
                                        </button>
                                    @endif
                                </td>
                                <td class="font-monospace small">{{ $info->nomor }}</td>
                                <td class="fw-semibold">{{ $info->judul }}</td>
                                @if ($adaRevisi)<td class="text-center">{{ $info->edisi }} / {{ $info->no_revisi }}</td>@endif
                                @if ($adaTanggal)<td class="small">{{ $info->tanggal_efektif?->format('d/m/Y') ?? '—' }}</td>@endif
                                <td class="small text-muted">
                                    {{ $info->created_at->format('d/m/Y') }}
                                    <span class="d-block" style="font-size:.72rem">{{ $info->uploader?->name ?? '—' }}</span>
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Riwayat duduk di kolom AKSI sebagai tombol sungguhan.
                                         Dulu ia badge di kolom Judul: bisa diklik, tapi tak ada
                                         yang menyangka badge adalah tombol. Chevron di kolom
                                         pertama TETAP — ia penanda baris yang terbuka, dan
                                         keduanya berbagi satu `buka` milik <tbody>. --}}
                                    @if ($versiLama->isNotEmpty())
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                @click="buka = !buka" :aria-expanded="buka.toString()">
                                            <i class="bi bi-clock-history"></i> Riwayat ({{ $versiLama->count() }})
                                        </button>
                                    @endif
                                    <a href="{{ route('informasi.file', $info) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                                        <i class="bi {{ $info->isGambar() ? 'bi-image' : 'bi-file-earmark-pdf' }}"></i> Buka
                                    </a>
                                    @if ($kelola)
                                        <a href="{{ route('informasi.perbarui', $info) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-arrow-repeat"></i> Perbarui
                                        </a>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalHapusInfo{{ $info->id }}">
                                            <i class="bi bi-trash3"></i> Hapus
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- Riwayat dokumen INI saja. Terlipat: tabel tetap
                                 satu baris per nomor sampai seseorang memintanya. --}}
                            @if ($versiLama->isNotEmpty())
                                <tr x-show="buka" x-cloak>
                                    <td></td>
                                    <td colspan="{{ $jumlahKolom - 1 }}" class="pt-0">
                                        <div class="pp-riwayat">
                                            <div class="small text-muted fw-semibold mb-2">
                                                <i class="bi bi-clock-history"></i> Versi lama {{ $info->nomor }}
                                            </div>
                                            @foreach ($versiLama as $lama)
                                                <div class="d-flex justify-content-between align-items-center gap-2 py-1 border-bottom">
                                                    <span class="small">
                                                        {{ $lama->judul }}
                                                        @if ($lama->labelRevisi())
                                                            <span class="badge-soft">{{ $lama->labelRevisi() }}</span>
                                                        @endif
                                                        <span class="text-muted d-block" style="font-size:.72rem">
                                                            Diunggah {{ $lama->created_at->format('d/m/Y H:i') }} WITA
                                                            · {{ $lama->uploader?->name ?? '—' }}
                                                        </span>
                                                    </span>
                                                    <span class="text-nowrap">
                                                        <a href="{{ route('informasi.file', $lama) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-box-arrow-up-right"></i> Buka
                                                        </a>
                                                        @if ($kelola)
                                                            {{-- Riwayat hanya bisa dihapus satu-satu: "hapus
                                                                 seluruhnya" adalah aksi atas NOMOR, dan tempatnya
                                                                 di baris induk, bukan di sini. --}}
                                                            <form method="POST" action="{{ route('informasi.destroy', $lama) }}" class="d-inline"
                                                                  data-confirm="Hapus versi lama ini dari riwayat {{ $info->nomor }}? Versi yang sedang berlaku tidak tersentuh."
                                                                  data-confirm-title="Hapus Versi Lama?" data-confirm-icon="warning" data-confirm-ok="Ya, hapus">
                                                                @csrf @method('DELETE')
                                                                <input type="hidden" name="cakupan" value="versi">
                                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i></button>
                                                            </form>
                                                        @endif
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="{{ $jumlahKolom }}" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada {{ $label }}.
                                @if ($kelola)
                                    <a href="{{ route('informasi.create', ['kategori' => $kategori]) }}">Unggah yang pertama</a>.
                                @endif
                            </td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
        @if ($daftar->hasPages())<div class="card-footer bg-white">{{ $daftar->links() }}</div>@endif
    </div>

    {{-- Modal dirender DI LUAR tabel: di dalam <td> ia mewarisi konteks
         penumpukan tabel dan bisa tampil terpotong/di belakang latar gelapnya. --}}
    @if ($kelola)
        @foreach ($daftar as $info)
            @include('informasi._modal-hapus', ['info' => $info, 'jumlahRiwayat' => ($riwayat[$info->nomor] ?? collect())->count()])
        @endforeach
    @endif
@endsection

@push('styles')
<style>
    /* Panah pelipat: satu transisi, sejalan dengan .pp-caret di sidebar. */
    .pp-lipat { transition: transform .2s ease; display: inline-block; }
    .pp-lipat-buka { transform: rotate(90deg); }
    /* Riwayat menjorok + bergaris kiri, supaya kepemilikannya pada baris di
       atasnya terbaca sekali lihat tanpa perlu membaca nomornya lagi. */
    .pp-riwayat { border-left: 3px solid var(--bs-border-color); padding-left: .85rem; margin-left: .25rem; }
    .pp-riwayat > div:last-child { border-bottom: 0 !important; }
    [x-cloak] { display: none !important; }
</style>
@endpush
