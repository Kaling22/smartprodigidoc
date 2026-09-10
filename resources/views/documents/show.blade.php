@extends('layouts.app')
@section('title', 'Detail: ' . $document->displayNumber())

@php
    // Peta warna status pindah ke Document::STATUS_META (partials/_badge-status).
    $actionMeta = [
        'document.create' => ['Dokumen dibuat', 'bi-file-earmark-plus', 'primary'],
        'document.submit' => ['Dikirim untuk ditinjau', 'bi-send', 'info'],
        'document.withdraw' => ['Ditarik kembali ke draft', 'bi-arrow-counterclockwise', 'secondary'],
        'document.review_start' => ['Mulai ditinjau', 'bi-clipboard', 'info'],
        'document.review_approve' => ['Diloloskan peninjau', 'bi-check2', 'success'],
        'document.review_reject' => ['Dikembalikan untuk revisi', 'bi-arrow-counterclockwise', 'warning'],
        'document.approve' => ['Disetujui — Berlaku', 'bi-patch-check', 'success'],
        'document.approval_reject' => ['Ditolak approver', 'bi-x-circle', 'danger'],
        'document.cancel_revision' => ['Penolakan dibatalkan (ditinjau ulang)', 'bi-arrow-repeat', 'secondary'],
        'document.reassign_review' => ['Peninjauan dialihkan', 'bi-arrow-left-right', 'info'],
        'document.arsip_upload' => ['Dokumen lama didaftarkan — Berlaku', 'bi-archive', 'success'],
        'document.arsip_edit' => ['Dokumen lama diperbaiki', 'bi-pencil', 'secondary'],
        'document.request_revision' => ['Revisi diajukan', 'bi-arrow-repeat', 'warning'],
        'document.cancel_revision_b' => ['Revisi dibatalkan', 'bi-x-circle', 'secondary'],
        'document.nonaktif_ajukan' => ['Nonaktif diajukan', 'bi-slash-circle', 'warning'],
        'document.nonaktif_setuju' => ['Nonaktif disetujui satu tahap', 'bi-check2', 'warning'],
        'document.nonaktif_tolak' => ['Pengajuan nonaktif ditolak', 'bi-x-circle', 'secondary'],
        'document.nonaktif_selesai' => ['Tidak Berlaku — nomor dilepas', 'bi-slash-circle', 'danger'],
        'attachment.comment' => ['Komentar pada lampiran', 'bi-chat-left-text', 'info'],
        'feedback.create' => ['Masukan lapangan masuk', 'bi-chat-left-dots', 'primary'],
        'feedback.respond' => ['Masukan dibalas & ditutup', 'bi-reply', 'secondary'],
        'masukan_sejawat.kirim' => ['Masukan sejawat masuk', 'bi-chat-square-text', 'info'],
    ];

    // Masukan lapangan (FITUR-BARU-v4 §3). Sejak Fase C tiga wewenang yang
    // berbeda, dulu satu: MELIHAT (semua kecuali Non-Staff), MEMBALAS (GL/MD/
    // Admin, dijawab controller), dan MEREVISI (GL pemilik / MD / Admin).
    $bolehLihatMasukan = auth()->user()->dashboardPenuh();
    $bolehMembalas = $bolehMembalasMasukan;
    $bolehRevisi = $document->bisaDirevisiOleh(auth()->user());
    $bolehMemberiMasukan = $document->bisaDiberiMasukanOleh(auth()->user());
    $masukanBelumDitindak = $masukan->whereIn('status', ['baru', 'dibaca']);
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ url()->previous() }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
            @include('partials._badge-status', ['status' => $document->status])
            @include('partials._badge-nomor-lama', ['doc' => $document])
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
            {{-- Dokumen LAMA (butir 0) tak punya formulir berbab — isinya ada di
                 berkasnya. "Buka Form" digantikan "Perbaiki" bagi yang berhak. --}}
            @if ($document->isArsip())
                @if ($document->bisaDisuntingArsipOleh(auth()->user()))
                    <a href="{{ route('documents.arsip.edit', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Perbaiki</a>
                @endif
            @else
                <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Buka Form</a>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-info-circle"></i> Informasi Dokumen</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Jenis</dt><dd class="col-7">{{ $document->type->name }}</dd>
                        <dt class="col-5 text-muted">Departemen</dt><dd class="col-7">{{ $document->department->name }}</dd>
                        <dt class="col-5 text-muted">No. Revisi</dt><dd class="col-7">{{ $document->no_revisi }}</dd>
                        {{-- Nama + jabatan + departemen, mis. "Angga - GL ICTMD". --}}
                        <dt class="col-5 text-muted">Dibuat Oleh</dt><dd class="col-7">{{ $document->creator?->nameWithJabatan() ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Ditinjau Oleh</dt><dd class="col-7">{{ $document->reviewer?->nameWithJabatan() ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Disetujui Oleh</dt><dd class="col-7">{{ $document->approver?->nameWithJabatan() ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Tgl Terbit</dt><dd class="col-7">{{ $document->published_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-clock-history"></i> Timeline Riwayat</span>
                    @if ($timeline->count() > 4)
                        <span class="badge-soft badge-soft-secondary">{{ $timeline->count() }} peristiwa</span>
                    @endif
                </div>
                {{-- Tinggi dipatok ±4 peristiwa lalu digulir. Dokumen yang sudah
                     lama berjalan bisa punya belasan baris riwayat, dan kartu
                     yang memanjang itulah yang meninggalkan rongga di bawah
                     "Informasi Dokumen" di sebelahnya. Riwayat lamanya tidak
                     dibuang — cukup digulir. --}}
                <div class="card-body pp-timeline-gulir">
                    <div class="pp-timeline">
                        @forelse ($timeline as $log)
                            @php [$lbl, $icon, $clr] = $actionMeta[$log->action] ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'secondary']; @endphp
                            <div class="pp-timeline-item">
                                <span class="pp-timeline-dot bg-{{ $clr }}"><i class="bi {{ $icon }}"></i></span>
                                <div class="pp-timeline-content">
                                    <div class="fw-semibold small">{{ $lbl }}</div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        {{ $log->user->name ?? 'Sistem' }} · {{ $log->created_at?->format('d/m/Y H:i') }} WITA
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Belum ada riwayat.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel DISTRIBUSI (v5 Fase C) — di bawah Timeline, karena keduanya
             menjawab pertanyaan yang sama dari sisi berbeda: Timeline "apa yang
             sudah terjadi pada dokumen ini", Distribusi "apa yang sudah terjadi
             SETELAHNYA, di luar sana".

             Kolom "Belum Membaca" sengaja didahulukan secara visual (lencana
             merah): itulah satu-satunya bagian yang bisa ditindaklanjuti. --}}
        @if ($distribusi)
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="fw-bold"><i class="bi bi-broadcast"></i> Distribusi</span>
                        <div style="min-width:14rem">@include('partials._pita-cakupan', ['c' => $distribusi])</div>
                    </div>
                    <div class="card-body">
                        @include('partials._rincian-pembaca', [
                            'rincian' => $distribusiRincian,
                            'cakupan' => $distribusi,
                            'kosong' => 'Tak ada pengguna aktif lain di departemen ini, jadi tak ada yang bisa diukur.',
                        ])
                    </div>
                </div>
            </div>
        @endif

        {{-- Panel "Masukan Lapangan" (FITUR-BARU-v4 §3). Sengaja MENYATU dengan
             alur revisi: dari sini masukan langsung bisa dicentang menjadi alasan
             revisi (§5). Kotak-masuk terpisah membuat SH berpindah-pindah halaman
             dan masukannya mudah terlupakan. --}}
        @if ($bolehLihatMasukan || $bolehMemberiMasukan || $masukan->isNotEmpty())
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="fw-bold">
                            <i class="bi bi-chat-left-dots"></i> Masukan Lapangan
                            @if ($masukanBelumDitindak->isNotEmpty())
                                <span class="badge-soft badge-soft-danger">{{ $masukanBelumDitindak->count() }} belum ditindak</span>
                            @endif
                        </span>
                        <div class="d-flex gap-2">
                            @if ($bolehMemberiMasukan)
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMasukan{{ $document->id }}">
                                    <i class="bi bi-chat-left-dots"></i> Beri Masukan
                                </button>
                            @endif
                            @if ($bolehRevisi)
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $document->id }}">
                                    <i class="bi bi-arrow-repeat"></i> Revisi
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @include('documents._masukan-list', ['masukan' => $masukan, 'bolehMembalas' => $bolehMembalas])
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($bolehMemberiMasukan)
        @include('documents._modal-masukan', ['masukanSaya' => $masukan])
    @endif
    @if ($bolehRevisi)
        @include('documents._modal-revisi', ['masukanRevisi' => $masukanBelumDitindak])
    @endif
@endsection

@push('styles')
<style>
    .pp-timeline { position: relative; padding-left: .5rem; }
    .pp-timeline-item { position: relative; padding-left: 2.4rem; padding-bottom: 1.1rem; }
    .pp-timeline-item:not(:last-child)::before { content: ''; position: absolute; left: .85rem; top: 1.6rem; bottom: -.2rem; width: 2px; background: var(--bs-border-color); }
    .pp-timeline-dot { position: absolute; left: 0; top: 0; width: 1.75rem; height: 1.75rem; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; }

    /* Empat peristiwa terlihat, sisanya digulir. 13.6rem = 4 × (ikon 1.75rem +
       dua baris teks + jarak 1.1rem) — diturunkan dari .pp-timeline-item di
       atas, jadi mengubah paddingnya berarti menyesuaikan angka ini juga. */
    .pp-timeline-gulir { max-height: 13.6rem; overflow-y: auto; }
    /* Spec v3 R4: kedua kartu berakhir di garis yang sama. Tingginya datang dari
       `h-100` pada baris yang memang sudah stretch — bukan dari angka yang
       ditebak. Yang ditambahkan di sini cuma satu hal: begitu kartunya
       diregangkan, badan linimasa ikut memuai mengisi ruangnya alih-alih
       meninggalkan pita putih di bawah baris riwayat terakhir.
       `max-height` tetap berlaku sebagai batas atas, dan tetap jadi satu-satunya
       pengendali saat kolom bertumpuk (di bawah lg, h-100 tak mengukur apa pun). */
    @media (min-width: 992px) {
        .pp-timeline-gulir { flex: 1 1 auto; min-height: 0; }
    }
    /* Batang gulir tipis: kartu ini kecil, batang bawaan Windows memakan lebar
       yang seharusnya jadi teks. */
    .pp-timeline-gulir::-webkit-scrollbar { width: 6px; }
    .pp-timeline-gulir::-webkit-scrollbar-thumb { background: var(--bs-border-color); border-radius: 3px; }
</style>
@endpush
