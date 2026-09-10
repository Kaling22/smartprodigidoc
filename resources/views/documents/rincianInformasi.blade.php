@extends('layouts.app')
@section('title', 'Rincian Distribusi Informasi')

{{--
| RINCIAN distribusi satu informasi (PLAN B / B2).
|
| Daftar Distribusi menjawab "berapa persen" dan expander "Per Departemen"
| menjawab "departemen mana yang tertinggal". Yang belum pernah bisa dibaca
| adalah SIAPA ORANGNYA — itulah halaman ini, dan hanya itu.
|
| Tata letaknya dipinjam utuh dari panel Distribusi di detail dokumen mutu lewat
| `partials/_rincian-pembaca`: pertanyaannya sama, jadi jawabannya tak pantas
| tampil dalam dua bentuk berbeda.
--}}

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-start gap-2 flex-wrap">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">{{ $informasi->judul }}</h1>
            <p class="text-muted small mb-0">
                <span class="font-monospace">{{ $informasi->nomor }}</span>
                · {{ \App\Models\Informasi::labelKategori($informasi->kategori) }}
                @if ($informasi->labelRevisi())
                    · {{ $informasi->labelRevisi() }}
                @endif
            </p>
        </div>
        <a href="{{ route('documents.distribution', ['sumber' => 'informasi']) }}" class="btn btn-sm btn-light">
            <i class="bi bi-arrow-left"></i> Kembali ke Distribusi
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold"><i class="bi bi-broadcast"></i> Distribusi</span>
            <div style="min-width:14rem">@include('partials._pita-cakupan', ['c' => $cakupan])</div>
        </div>

        @if ($departments->isNotEmpty())
            {{-- Pemilih departemen = form GET biasa, tanpa JS: halaman ini toh
                 dimuat ulang saat menyaring, dan hasilnya bisa ditandai. Hanya
                 pemegang `document.view_all` yang melihatnya — SH/DH sudah
                 terkurung ke departemennya di sisi server. --}}
            <div class="card-body border-bottom py-2">
                <form method="GET" class="d-flex align-items-end gap-2 flex-wrap">
                    <div>
                        <label class="form-label small fw-semibold mb-1">Departemen</label>
                        <select name="department_id" class="form-select form-select-sm" style="min-width:12rem">
                            <option value="">Semua (7 departemen)</option>
                            @foreach ($departments as $d)
                                <option value="{{ $d->id }}" @selected($deptId == $d->id)>{{ $d->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-sm btn-input-h btn-secondary"><i class="bi bi-funnel"></i> Terapkan</button>
                    @if ($deptId)
                        <a href="{{ route('documents.rincianInformasi', $informasi) }}"
                           class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
                    @endif
                </form>
            </div>
        @endif

        <div class="card-body">
            @include('partials._rincian-pembaca', [
                'rincian' => $rincian,
                'cakupan' => $cakupan,
                'kosong' => 'Tak ada pengguna aktif yang dihitung sebagai sasaran di lingkup ini, jadi tak ada yang bisa diukur.',
            ])
        </div>
    </div>
@endsection
