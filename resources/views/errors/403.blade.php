{{-- Halaman 403 berbranding. Laravel memakai berkas ini otomatis bila ada —
     `bootstrap/app.php` tidak perlu disentuh.

     Sengaja `layouts.guest`, BUKAN `layouts.app`: 403 kerap terjadi justru saat
     sesi/otorisasi sedang bermasalah, dan layout app membutuhkan sidebar +
     data pengguna yang saat itu belum tentu ada.

     Fase 13 tak mengubah maksudnya, hanya kerangkanya: `layouts/app.blade.php`
     sudah tak dipakai siapa pun dan `layouts/guest` kini cangkang mandiri tanpa
     Bootstrap. Ikon `bi-house-door` ikut hilang bersama Bootstrap Icons —
     tombolnya sengaja tanpa ikon, bukan diganti SVG karangan. --}}
@extends('layouts.guest')

@section('title', 'Akses Ditolak')

@section('content')
    <img src="{{ asset('images/errors/403.png') }}" alt="Akses ditolak" class="gambar">
    <h1>Akses Ditolak</h1>
    <p>Kamu tidak punya wewenang membuka halaman ini.</p>
    <a href="{{ url('/') }}" class="tombol">Kembali ke Beranda</a>
@endsection
