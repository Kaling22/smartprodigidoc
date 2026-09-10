@extends('layouts.app')
@section('title', 'Perbarui ' . $induk->nomor)

@section('content')
    <div class="mb-3">
        <a href="{{ route('informasi.index', ['kategori' => $kategori]) }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Perbarui {{ \App\Models\Informasi::labelKategori($kategori) }}</h1>
        <p class="text-muted small mb-0">
            <span class="font-monospace">{{ $induk->nomor }}</span> — {{ $induk->judul }}
            @if ($induk->labelRevisi())<span class="badge-soft">{{ $induk->labelRevisi() }}</span>@endif
        </p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="alert alert-warning py-2 small d-flex align-items-start gap-2">
                        <i class="bi bi-arrow-repeat mt-1"></i>
                        <div>
                            Berkas ini menjadi <strong>versi yang berlaku</strong> untuk nomor
                            <span class="font-monospace">{{ $induk->nomor }}</span>. Versi sekarang pindah ke
                            <strong>Riwayat</strong>, tidak dihapus.
                        </div>
                    </div>

                    @include('informasi._form', ['action' => route('informasi.perbarui.store', $induk)])
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-secondary"><i class="bi bi-clock-history"></i> Versi Sekarang</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Judul</dt><dd class="col-7">{{ $induk->judul }}</dd>
                        @if ($induk->labelRevisi())
                            <dt class="col-5 text-muted">Edisi / Revisi</dt><dd class="col-7">{{ $induk->labelRevisi() }}</dd>
                        @endif
                        @if ($induk->tanggal_efektif)
                            <dt class="col-5 text-muted">Tgl Efektif</dt><dd class="col-7">{{ $induk->tanggal_efektif->format('d/m/Y') }}</dd>
                        @endif
                        <dt class="col-5 text-muted">Diunggah</dt><dd class="col-7">{{ $induk->created_at->format('d/m/Y H:i') }} WITA</dd>
                        <dt class="col-5 text-muted">Oleh</dt><dd class="col-7">{{ $induk->uploader?->name ?? '—' }}</dd>
                    </dl>
                    <a href="{{ route('informasi.file', $induk) }}" target="_blank" class="btn btn-sm btn-outline-secondary mt-3">
                        <i class="bi bi-box-arrow-up-right"></i> Buka berkas sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
