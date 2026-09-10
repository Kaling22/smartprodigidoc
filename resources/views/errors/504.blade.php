{{-- Halaman 504 berbranding — kembarannya `errors/403.blade.php`; alasan
     memakai `layouts.guest` sama persis, lihat catatan di sana. --}}
@extends('layouts.guest')

@section('title', 'Server Tidak Merespons')

@section('content')
    <img src="{{ asset('images/errors/504.png') }}" alt="Server tidak merespons" class="gambar">
    <h1>Server Tidak Merespons</h1>
    <p>Permintaanmu terlalu lama diproses, coba lagi sebentar.</p>
    <a href="{{ url('/') }}" class="tombol">Kembali ke Beranda</a>
@endsection
