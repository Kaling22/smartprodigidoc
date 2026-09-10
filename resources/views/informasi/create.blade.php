@extends('layouts.app')
@section('title', 'Tambah ' . \App\Models\Informasi::labelKategori($kategori))

@section('content')
    <div class="mb-3">
        <a href="{{ route('informasi.index', ['kategori' => $kategori]) }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Tambah {{ \App\Models\Informasi::labelKategori($kategori) }}</h1>
        <p class="text-muted small mb-0">Untuk dokumen yang <strong>belum ada</strong> di daftar. Yang sudah ada diperbarui lewat tombol Perbarui pada barisnya.</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @include('informasi._form', ['action' => route('informasi.store'), 'induk' => null])
                </div>
            </div>
        </div>
    </div>
@endsection
