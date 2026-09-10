@extends('layouts.app')
@section('title', 'Tinjau Dokumen')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Tinjau Dokumen</h1>
        <p class="text-muted small mb-0">Dokumen yang menunggu peninjauan Anda.</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>@include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th><th>Jenis</th><th>Dept</th><th>Pembuat</th>@include('partials._urut-th', ['kolom' => 'revisi', 'label' => 'Edisi/Revisi'])<th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge-soft">{{ $doc->type->code }}</span></td>
                                <td><span class="badge-soft">{{ $doc->department->code }}</span></td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                {{-- Angka DOKUMEN (yang tercetak di kop), bukan
                                     penghitung putaran peninjauan — lihat catatan
                                     panjang di review/show.blade.php. --}}
                                <td class="small">Ed. {{ $doc->edisi ?? 1 }} · Rev. {{ $doc->no_revisi }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    {{-- Alihkan langsung dari antrian: GL SHE yang mejanya penuh
                                         tak perlu membuka satu per satu dokumen hanya untuk melepasnya.
                                         Syaratnya yang MURAH saja di sini (jenis, pemegang, status) —
                                         "ada peninjau lain yang bisa menerima" butuh resolver, dan
                                         menjalankannya per baris membebani justru halaman milik orang
                                         yang antreannya paling panjang. Penjaga sebenarnya tetap di
                                         ReviewController::alihkan(). --}}
                                    @if ($doc->type->code === 'JSA' && $doc->reviewer_id === auth()->id())
                                        <a href="{{ route('review.show', [$doc, 'alih' => 1]) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-arrow-left-right"></i> Alihkan
                                        </a>
                                    @endif
                                    <a href="{{ route('review.show', $doc) }}" class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i> Tinjau</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada dokumen untuk ditinjau.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>

    {{-- Status Revisi: dokumen yang Anda tolak — pantau & bisa Batalkan Revisi (v3.1 §4.3) --}}
    <div class="mt-4 mb-2">
        <h2 class="h6 fw-bold text-secondary"><i class="bi bi-arrow-repeat"></i> Status Revisi <span class="text-muted fw-normal small">— dokumen yang Anda kembalikan</span></h2>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>@include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th><th>Pembuat</th><th>Tahap</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($statusRevisi as $doc)
                            <tr>
                                <td class="font-monospace small">{{ $doc->displayNumber() }}</td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                <td><span class="badge-soft badge-soft-danger">Ditolak — menunggu revisi pembuat</span></td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
                                    <form method="POST" action="{{ route('review.cancelRevision', $doc) }}" class="d-inline"
                                          data-confirm="Batalkan penolakan? Dokumen kembali ditinjau (in_review) untuk Anda periksa ulang." data-confirm-title="Batalkan Revisi?" data-confirm-ok="Ya, batalkan">
                                        @csrf
                                        <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-counterclockwise"></i> Batalkan Revisi</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada dokumen yang Anda tolak.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
