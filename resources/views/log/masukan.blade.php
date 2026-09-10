@extends('layouts.app')
@section('title', 'Masukan Lapangan')

{{--
    Log Dokumen → Masukan Lapangan (PLAN-REVISI-v6 Fase C, D5).

    Seluruh masukan dalam SATU halaman, menggantikan tempelan yang tercecer di
    lencana Dokumen Berlaku, panel halaman dokumen, dan kartu dashboard.

    Dua tombol aksinya meminjam alur yang sudah ada — form balas dari
    `documents/_masukan-list` dan modal `documents/_modal-revisi` — jadi halaman
    ini tak memperkenalkan satu pun aturan wewenang baru.
--}}
@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Masukan Lapangan</h1>
            <p class="text-muted small mb-0">
                Masukan orang lapangan atas dokumen Berlaku
                {{ $departments->isNotEmpty() ? 'di 7 departemen' : 'di departemen Anda' }}.
                @can('document.feedback_respond')
                    Balas untuk menutup, atau Revisi untuk menjadikannya bahan perbaikan.
                @else
                    Ditindaklanjuti penyusun dokumen (GL) atau Management Development.
                @endcan
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari (isi / nomor masukan)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. MSK-2026 atau kata di isinya...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\DocumentFeedback::STATUS_LABELS as $kunci => $label)
                            <option value="{{ $kunci }}" @selected(($filters['status'] ?? '') === $kunci)>{{ $label }}</option>
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
                <div class="col-md-3">
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('log.masukan') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                            <th>No. Masukan</th><th>Dokumen</th><th>Isi</th>
                            <th>Pengirim</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($masukan as $m)
                            @php $warna = ['baru' => 'danger', 'dibaca' => 'secondary', 'diadopsi' => 'success', 'ditolak' => 'dark'][$m->status] ?? 'secondary'; @endphp
                            <tr>
                                <td class="font-monospace small">{{ $m->feedback_number }}</td>
                                <td class="small">
                                    @if ($m->document)
                                        <a href="{{ route('documents.show', $m->document) }}" class="fw-semibold text-decoration-none">{{ $m->document->displayNumber() }}</a>
                                        <div class="text-muted">{{ $m->document->title }}</div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small" style="max-width:22rem">
                                    {{-- Dipotong 120 karakter; selebihnya dibuka <details> —
                                         nol JS, dan barisnya tak pernah setinggi paragraf. --}}
                                    @if (mb_strlen($m->isi) <= 120)
                                        {{ $m->isi }}
                                    @else
                                        <details>
                                            <summary class="pp-ringkas">{{ mb_substr($m->isi, 0, 120) }}…</summary>
                                            <div class="mt-1">{{ $m->isi }}</div>
                                        </details>
                                    @endif
                                    @if ($m->balasan)
                                        <div class="bg-body-secondary border-start border-3 rounded-end px-2 py-1 mt-2">
                                            <i class="bi bi-reply"></i> {{ $m->balasan }}
                                            @if ($m->replier)<span class="text-muted">— {{ $m->replier->nameWithJabatan() }}</span>@endif
                                        </div>
                                    @endif
                                </td>
                                <td class="small">{{ $m->user?->nameWithJabatan() ?? '—' }}</td>
                                <td class="small text-nowrap">{{ $m->created_at?->format('d/m/Y H:i') }} WITA</td>
                                <td><span class="badge-soft badge-soft-{{ $warna }}">{{ $m->statusLabel() }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if ($m->bisaDibalasOleh(auth()->user()))
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalBalas{{ $m->id }}">
                                            <i class="bi bi-reply"></i> Balas
                                        </button>
                                    @endif
                                    @if ($m->bisaDiadopsiOleh(auth()->user()))
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $m->document->id }}">
                                            <i class="bi bi-arrow-repeat"></i> Revisi
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada masukan lapangan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($masukan->hasPages())<div class="card-footer bg-white">{{ $masukan->links() }}</div>@endif
    </div>

    {{-- Modal dirender DI LUAR tabel: di dalam <td> ia mewarisi konteks
         penumpukan tabel dan bisa tampil terpotong. --}}
    @foreach ($masukan as $m)
        @if ($m->bisaDibalasOleh(auth()->user()))
            <div class="modal fade" id="modalBalas{{ $m->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('documents.feedback.respond', $m) }}" class="modal-content"
                          data-confirm="Tutup masukan {{ $m->feedback_number }} tanpa revisi dan kirim balasan ke pengirimnya?"
                          data-confirm-title="Balas &amp; Tutup?" data-confirm-ok="Ya, kirim balasan">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title h6 fw-bold mb-0"><i class="bi bi-reply"></i> Balas — <span class="font-monospace">{{ $m->feedback_number }}</span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="border rounded p-2 mb-3 small">
                                {{ $m->isi }}
                                <div class="text-muted" style="font-size:.75rem">{{ $m->user?->nameWithJabatan() ?? '—' }}</div>
                            </div>
                            <label for="balasan{{ $m->id }}" class="form-label small fw-semibold">Balasan untuk pengirim</label>
                            <textarea id="balasan{{ $m->id }}" name="balasan" rows="3" maxlength="2000" required
                                      class="form-control" placeholder="mis. sudah sesuai standar terbaru."></textarea>
                            <div class="form-text">Masukan ini akan DITUTUP; pengirimnya diberi tahu.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-sm btn-secondary"><i class="bi bi-reply"></i> Balas &amp; Tutup</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Modal Revisi: SATU per dokumen (beberapa masukan bisa menunjuk dokumen
         yang sama), dengan masukan pemicunya sudah tercentang. --}}
    @foreach ($masukan->filter(fn ($m) => $m->bisaDiadopsiOleh(auth()->user()))->groupBy('document_id') as $perDokumen)
        @include('documents._modal-revisi', [
            'document' => $perDokumen->first()->document,
            'masukanRevisi' => $perDokumen,
            'masukanTercentang' => $perDokumen->pluck('id')->all(),
        ])
    @endforeach
@endsection

@push('styles')
<style>
    /* Ringkasan <details> dibuat terlihat bisa diklik tanpa satu baris JS. */
    .pp-ringkas { cursor: pointer; list-style: none; }
    .pp-ringkas::-webkit-details-marker { display: none; }
    .pp-ringkas::after { content: ' selengkapnya'; color: var(--bs-secondary-color); font-size: .75rem; }
    details[open] > .pp-ringkas::after { content: ' tutup'; }
</style>
@endpush
