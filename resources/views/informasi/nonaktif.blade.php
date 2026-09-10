@extends('layouts.app')
@section('title', $kat->nama)

{{-- Kategori Informasi yang DITUTUP Admin (PLAN-AKSES-v8 Fase 5b, ketetapan H).

     Kategori tidak pernah dihapus — `informasi.kategori` menunjuk ke sana, sama
     persis dengan alasan "hapus departemen" dibuang. Yang ditutup adalah
     pintunya, dan halaman ini mengatakannya terang-terangan alih-alih membiarkan
     menunya lenyap tanpa kabar: dokumennya masih ada, dan orang perlu tahu
     bahwa yang berubah adalah keputusan, bukan datanya. --}}

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi {{ $kat->ikon }} text-secondary" style="font-size:3rem"></i>
            <h1 class="h4 fw-bold text-dark mt-3">{{ $kat->nama }} Sedang Ditutup</h1>
            <p class="text-muted col-md-8 mx-auto mb-1">
                Kategori ini dinonaktifkan oleh Admin, jadi daftarnya tidak dibuka dan
                unggahan baru tidak diterima.
            </p>
            <p class="text-muted col-md-8 mx-auto">
                Dokumen yang sudah ada <strong>tidak dihapus</strong>. Bila Anda masih
                memerlukannya, hubungi Admin untuk membuka kembali kategori ini.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-light mt-2">Kembali</a>
        </div>
    </div>
@endsection
