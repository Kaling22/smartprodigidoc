@extends('layouts.app')
@section('title', 'Riwayat Pekerjaan JSA')

@php
    $statusColors = [
        'berlangsung' => 'info',
        'selesai'     => 'success',
        'dibatalkan'  => 'secondary',
    ];
    $statusLabels = \App\Models\JobExecution::STATUS_LABELS;
@endphp

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h4 fw-bold text-dark mb-0">Riwayat Pekerjaan JSA</h1>
        <p class="text-muted small mb-0">
            Daftar pekerjaan lapangan yang menggunakan formulir JSA — se-departemen Anda.
        </p>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Cari (nama pekerjaan / lokasi / user / JSA)</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="mis. pengelasan, Area 3B, GL-0001...">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach ($statusLabels as $val => $lbl)
                        <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal dari</label>
                <input type="date" name="tanggal_dari" value="{{ $filters['tanggal_dari'] ?? '' }}"
                       class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tanggal sampai</label>
                <input type="date" name="tanggal_sampai" value="{{ $filters['tanggal_sampai'] ?? '' }}"
                       class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('job-executions.index') }}"
                       class="btn btn-sm btn-input-h btn-light" title="Reset">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tabel --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Pekerjaan</th>
                        <th>JSA Acuan</th>
                        <th>Pelaksana</th>
                        <th>NRP</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Progres</th>
                        <th>Status</th>
                        @can('user.manage')<th class="text-end">Aksi</th>@endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pekerjaan as $job)
                        @php
                            $progres = $job->progres();
                            $pct     = $progres['total'] > 0
                                ? round(($progres['selesai'] / $progres['total']) * 100)
                                : 0;
                            $barColor = match ($job->status) {
                                'selesai'     => 'success',
                                'dibatalkan'  => 'secondary',
                                default       => $pct >= 100 ? 'success' : 'info',
                            };
                        @endphp
                        <tr>
                            {{-- Nama pekerjaan + catatan (bila ada) --}}
                            <td>
                                <div class="fw-semibold text-dark">{{ $job->nama_pekerjaan }}</div>
                                @if ($job->catatan)
                                    <div class="text-muted small text-truncate" style="max-width:200px"
                                         title="{{ $job->catatan }}">
                                        {{ $job->catatan }}
                                    </div>
                                @endif
                            </td>

                            {{-- JSA yang digunakan --}}
                            <td>
                                <div class="small fw-semibold">{{ $job->document?->title ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.72rem">
                                    {{ $job->document?->displayNumber() }}
                                    @if ($job->document?->department)
                                        · {{ $job->document->department->code }}
                                    @endif
                                </div>
                            </td>

                            {{-- Nama pelaksana --}}
                            <td>{{ $job->user?->name ?? '—' }}</td>

                            {{-- NRP pelaksana --}}
                            <td>
                                <span class="badge-soft fw-normal"
                                      style="font-size:.72rem">
                                    {{ $job->user?->nrp ?? '—' }}
                                </span>
                            </td>

                            {{-- Lokasi --}}
                            <td class="small">{{ $job->lokasi }}</td>

                            {{-- Tanggal pelaksanaan --}}
                            <td class="small text-nowrap">
                                {{ $job->tanggal_pelaksanaan?->translatedFormat('d M Y') ?? '—' }}
                            </td>

                            {{-- Progress bar --}}
                            <td style="min-width:110px">
                                @if ($job->status === 'dibatalkan')
                                    <span class="text-muted small">—</span>
                                @else
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:6px">
                                            <div class="progress-bar bg-{{ $barColor }}"
                                                 style="width:{{ $pct }}%"></div>
                                        </div>
                                        <span class="text-muted" style="font-size:.7rem;white-space:nowrap">
                                            {{ $progres['selesai'] }}/{{ $progres['total'] }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            {{-- Badge status --}}
                            <td>
                                <span class="badge-soft badge-soft-{{ $statusColors[$job->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$job->status] ?? $job->status }}
                                </span>
                            </td>

                            {{-- Hapus (Admin IT saja) --}}
                            @can('user.manage')
                            <td class="text-end">
                                <form method="POST" action="{{ route('job-executions.destroy', $job) }}"
                                      data-confirm="Hapus riwayat pekerjaan &quot;{{ $job->nama_pekerjaan }}&quot;? Data yang dihapus tidak dapat dikembalikan."
                                      data-confirm-title="Hapus Riwayat Pekerjaan?"
                                      data-confirm-ok="Ya, hapus"
                                      data-confirm-icon="warning">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->can('user.manage') ? 9 : 8 }}" class="text-center text-muted py-5">
                                <i class="bi bi-clipboard-x fs-2 d-block mb-2 opacity-50"></i>
                                Belum ada riwayat pekerjaan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginasi --}}
        @if ($pekerjaan->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <span class="small text-muted">
                    Menampilkan {{ $pekerjaan->firstItem() }}–{{ $pekerjaan->lastItem() }}
                    dari {{ $pekerjaan->total() }} pekerjaan
                </span>
                {{ $pekerjaan->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
