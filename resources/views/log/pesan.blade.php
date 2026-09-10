@extends('layouts.app')
@section('title', 'Log Pesan')

{{--
    Log Dokumen → Log Pesan (PLAN-REVISI-v6 Fase C, D5; diperluas PLAN B / B3).

    Setiap pesan yang pernah ditulis atas sebuah dokumen, dari tahap-tahap yang
    datanya SUDAH tersimpan tapi belum pernah bisa dibaca di satu tempat:
    pengajuan revisi, penolakan peninjau/MD/penyetuju, pengalihan peninjauan JSA,
    pengajuan & penolakan nonaktif, alasan PEMUSNAHAN (dokumennya sudah lenyap,
    jadi nomor & judulnya dibaca dari audit), dan balasan atas masukan lapangan.

    BACA SAJA — tak ada satu pun tombol yang mengubah status di halaman ini.
--}}
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Log Pesan</h1>
            <p class="text-muted small mb-0">
                Alasan revisi, penolakan, pengalihan, pemusnahan, dan balasan masukan
                {{ $departments->isNotEmpty() ? 'di 7 departemen' : 'di departemen Anda' }}.
                Baca saja.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Cari (pesan / nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. APD atau {{ \App\Models\Pengaturan::prefix() }}-SOP...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Tahap</label>
                    <select name="tahap" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($tahapan as $kunci => [$label, $warna])
                            <option value="{{ $kunci }}" @selected(($filters['tahap'] ?? '') === $kunci)>{{ $label }}</option>
                        @endforeach
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
                    <label class="form-label small fw-semibold">Dari</label>
                    <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Sampai</label>
                    <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-12 d-flex gap-1 mt-2">
                    <button class="btn btn-sm btn-input-h btn-secondary"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('log.pesan') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i> Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Dokumen</th><th>Tahap</th><th>Oleh</th><th>Pesan</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($pesan as $b)
                            @php [$label, $warna] = $tahapan[$b['tahap']] ?? [$b['tahap'], 'secondary']; @endphp
                            <tr>
                                <td class="small text-nowrap">{{ $b['tanggal']->format('d/m/Y H:i') }} WITA</td>
                                <td class="small">
                                    <span class="font-monospace fw-semibold">{{ $b['nomor'] }}</span>
                                    <div class="text-muted">{{ $b['judul'] }} <span class="badge-soft">{{ $b['dept'] }}</span></div>
                                </td>
                                <td><span class="badge-soft badge-soft-{{ $warna }}">{{ $label }}</span></td>
                                <td class="small">{{ $b['oleh']?->nameWithJabatan() ?? '—' }}</td>
                                <td class="small" style="max-width:26rem">
                                    @if (mb_strlen($b['alasan']) <= 140)
                                        {{ $b['alasan'] }}
                                    @else
                                        <details>
                                            <summary class="pp-ringkas">{{ mb_substr($b['alasan'], 0, 140) }}…</summary>
                                            <div class="mt-1" style="white-space:pre-line">{{ $b['alasan'] }}</div>
                                        </details>
                                    @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    {{-- Tujuan, label, dan ikonnya SELURUHNYA dari controller
                                         (DocumentLogController::tautan) — PLAN-AKSES-v8 Fase 3b.
                                         Dulu tiap baris bertombol "Dokumen" yang sama, sehingga
                                         pembuat yang membaca alasan penolakannya di sini masih
                                         harus mencari sendiri jalan ke form revisinya. Peta
                                         kedua sengaja TIDAK ditaruh di Blade: dua peta yang
                                         harus sepakat adalah dua peta yang suatu hari tidak
                                         sepakat. Null = tanpa tombol (baris pemusnahan). --}}
                                    @if ($b['tautan'])
                                        <a href="{{ $b['tautan']['url'] }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi {{ $b['tautan']['ikon'] }}"></i> {{ $b['tautan']['label'] }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-card-list fs-1 d-block mb-2"></i>Belum ada pesan tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($pesan->hasPages())<div class="card-footer bg-white">{{ $pesan->links() }}</div>@endif
    </div>
@endsection

@push('styles')
<style>
    .pp-ringkas { cursor: pointer; list-style: none; }
    .pp-ringkas::-webkit-details-marker { display: none; }
    .pp-ringkas::after { content: ' selengkapnya'; color: var(--bs-secondary-color); font-size: .75rem; }
    details[open] > .pp-ringkas::after { content: ' tutup'; }
</style>
@endpush
