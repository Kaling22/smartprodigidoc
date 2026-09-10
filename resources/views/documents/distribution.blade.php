@extends('layouts.app')
@section('title', 'Distribusi Dokumen')

{{--
| Menu DISTRIBUSI — apakah dokumen Berlaku benar-benar sampai ke orangnya.
|
| Diurutkan cakupan MENAIK: halaman ini ada untuk menemukan yang belum sampai.
| Kalau yang paling sukses ditaruh di puncak, daftarnya enak dipandang tapi tak
| berguna — yang perlu ditindaklanjuti justru terkubur di dasar.
--}}
@section('content')
    @php $adalahInformasi = $sumber === 'informasi'; @endphp

    <div class="mb-3 d-flex justify-content-between align-items-start gap-2 flex-wrap">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Distribusi {{ $adalahInformasi ? 'Informasi' : 'Dokumen' }}</h1>
            <p class="text-muted small mb-0">
                @if ($adalahInformasi)
                    {{-- Informasi berlaku lintas 7 departemen; yang menyempit
                         bagi SH/DH bukan daftarnya melainkan ORANG yang dihitung
                         sebagai sasaran (keputusan C1). --}}
                    Seberapa jauh kebijakan, memo, dan poster sudah dibuka orang
                    {{ auth()->user()->can('document.view_all') ? 'di 7 departemen' : 'di departemen Anda' }}.
                @else
                    Seberapa jauh dokumen Berlaku sudah dibaca orang
                    {{ $departments->isNotEmpty() ? 'di 7 departemen' : 'di departemen Anda' }}.
                @endif
                Diurutkan dari yang <strong>paling sedikit</strong> dibaca.
            </p>
        </div>
        {{-- Saklar sumber = dua TAUTAN biasa, bukan JS: halaman ini toh dimuat
             ulang saat menyaring, dan tautan bisa ditandai/dibagikan. --}}
        <div class="btn-group btn-group-sm" role="group" aria-label="Sumber distribusi">
            <a href="{{ route('documents.distribution') }}"
               class="btn btn-outline-secondary {{ $adalahInformasi ? '' : 'active' }}">Dokumen Mutu</a>
            <a href="{{ route('documents.distribution', ['sumber' => 'informasi']) }}"
               class="btn btn-outline-secondary {{ $adalahInformasi ? 'active' : '' }}">Informasi</a>
        </div>
    </div>

    @if ($rendah > 0)
        <div class="alert alert-warning d-flex align-items-center gap-2 py-2">
            <i class="bi bi-exclamation-triangle"></i>
            <span class="small mb-0">
                <strong>{{ $rendah }} {{ $adalahInformasi ? 'informasi' : 'dokumen' }}</strong>
                baru dibaca kurang dari separuh orang yang seharusnya tahu.
            </span>
        </div>
    @endif

    @if ($adalahInformasi)
        @include('documents._distribusi-informasi')
    @else
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. {{ \App\Models\Pengaturan::prefix() }}-SOP atau judul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\DocumentType::kode() as $code)<option value="{{ $code }}" @selected(strtoupper($filters['type'] ?? '') === $code)>{{ $code }}</option>@endforeach
                    </select>
                </div>
                @if ($departments->isNotEmpty())
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $d)<option value="{{ $d->id }}" @selected(($filters['department_id'] ?? '') == $d->id)>{{ $d->code }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.distribution') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th><th>Jenis</th><th>Dept</th>
                            <th style="min-width:11rem">Cakupan</th>
                            <th class="text-center">Unduhan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $d)
                            @php $c = $cakupan[$d->id]; @endphp
                            <tr>
                                <td class="font-monospace small">{{ $d->displayNumber() }}</td>
                                <td>{{ $d->title }}</td>
                                <td><span class="badge-soft">{{ $d->type->code }}</span></td>
                                <td><span class="badge-soft">{{ $d->department->code ?? '—' }}</span></td>
                                <td>@include('partials._pita-cakupan', ['c' => $c])</td>
                                <td class="text-center small">
                                    {{-- Unduhan dipisah dari cakupan: cakupan = berapa ORANG,
                                         unduhan = berapa KALI. Dokumen yang dibuka 40 kali
                                         oleh 2 orang belum tersebar. --}}
                                    <span class="text-muted">{{ $c['unduhan'] }}×</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('documents.show', $d) }}" class="btn btn-sm btn-outline-secondary" title="Rincian pembaca">
                                        <i class="bi bi-people"></i> Rincian
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-broadcast fs-3 d-block mb-2 opacity-50"></i>
                                Belum ada dokumen Berlaku yang cocok dengan filter ini.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@endsection
