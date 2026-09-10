@extends('layouts.app')
@section('title', 'Catatan Revisi Dokumen Lama: ' . $document->displayNumber())

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Lembar Catatan Revisi</h1>
        <p class="text-muted small mb-0">
            <span class="font-monospace">{{ $document->displayNumber() }}</span> — {{ $document->title }}
            <span class="badge-soft ms-1">{{ $document->type->code }}</span>
            <span class="badge-soft">{{ $document->department->code }}</span>
        </p>
    </div>

    <div class="alert alert-info py-2 small d-flex align-items-start gap-2">
        <i class="bi bi-info-circle mt-1"></i>
        <div>
            Dokumen ini <strong>sudah Berlaku</strong>. Berkas yang Anda unggah
            <strong>tak pernah ditimpa</strong>, jadi pemotongan halaman bisa diulang kapan saja.
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('documents.arsip.catatan.store', $document) }}" x-data="{ pilihan: 'gabung' }">
        @csrf
        <input type="hidden" name="pilihan" :value="pilihan">

        <div class="row g-3">
            {{-- KIRI: formulir --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        {{-- Khas dokumen lama: berapa halaman AWAL berkas yang dibuang
                             (biasanya lembar revisi lama yang hendak diganti), dan nomor
                             halaman berapa yang tercetak pada lembar sisipan. Keduanya
                             tak bisa ditebak sistem — penomoran dokumen lama tiap
                             departemen berbeda. --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Potong halaman awal</label>
                                <input type="number" name="potong_halaman" value="{{ old('potong_halaman', 1) }}"
                                       min="0" max="{{ max(0, $jumlahHalaman - 1) }}" class="form-control">
                                <div class="form-text">
                                    Berkas ini <strong>{{ $jumlahHalaman }} halaman</strong>. Isi <strong>0</strong> bila
                                    tak ada yang dibuang — lembar Catatan Revisi hanya ditambahkan di depan.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Nomor halaman lembar sisipan</label>
                                <input type="number" name="halaman_awal" value="{{ old('halaman_awal', 1) }}" min="1" max="999" class="form-control">
                                <div class="form-text">Angka yang tercetak di kop lembar Catatan Revisi.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- Partial yang SAMA dengan langkah "Log Revisi" wizard: nama
                             field, bentuk baris, dan penyimpannya (persistRevisionLog)
                             identik, jadi lembar dokumen lama tak mungkin tersimpan
                             dalam bentuk yang berbeda dari lembar dokumen web. --}}
                        @include('documents.fields._revision_log', ['document' => $document, 'value' => $baris])

                        <hr class="my-4">

                        <label class="form-label fw-semibold">Setelah ini</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" id="pilihGabung" value="gabung" x-model="pilihan">
                            <label class="form-check-label small" for="pilihGabung">
                                <strong>Cukup unggahan + lembar revisi baru.</strong>
                                Lembar di atas dicetak dan disisipkan di depan berkas; dokumen tetap Berlaku apa adanya.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="pilihSalin" value="salin" x-model="pilihan">
                            <label class="form-check-label small" for="pilihSalin">
                                <strong>Salin seluruh dokumen ke web.</strong>
                                Isi dokumen diketik ulang lewat wizard; berkas unggahan tinggal jadi rujukan di panel kanan.
                            </label>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-pp"
                                    x-bind:data-confirm="pilihan === 'gabung'
                                        ? 'Lembar Catatan Revisi disisipkan di depan berkas dan halaman awalnya dipotong sesuai angka di atas. Berkas asli tetap tersimpan utuh.'
                                        : 'Dokumen akan disalin ke wizard untuk diketik ulang. Berkas unggahan tetap bisa dilihat sebagai rujukan.'"
                                    data-confirm-title="Lanjutkan?" data-confirm-ok="Ya, lanjutkan">
                                <i class="bi bi-check2-circle"></i> Simpan &amp; Lanjutkan
                            </button>
                            <a href="{{ route('documents.published') }}" class="btn btn-light">Nanti Saja</a>
                        </div>
                        <div class="form-text mt-2">
                            "Nanti Saja" tak membatalkan apa pun — dokumennya sudah terdaftar &amp; Berlaku.
                            Lembar ini bisa diisi belakangan lewat menu Dokumen Berlaku, tombol Perbaiki.
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANAN: berkas yang baru diunggah.
                 `src` ditulis di HTML (bukan dipasang JS), pola yang sama dengan
                 panel pratinjau wizard: peramban mulai mengambilnya berbarengan
                 dengan halaman, dan keterangannya tertindih sendiri saat penampil
                 PDF mengecat — nol kedipan. --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top:1rem">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <span class="fw-bold small"><i class="bi bi-file-earmark-pdf"></i> Berkas yang diunggah</span>
                        <a href="{{ route('documents.pdf', $document) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-up-right"></i> Buka
                        </a>
                    </div>
                    <div class="card-body p-0 position-relative" style="background:#525659;height:78vh">
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50 small">
                            <i class="bi bi-file-earmark-text fs-1 mb-2"></i>
                            Menyiapkan berkas…
                        </div>
                        <iframe title="Berkas dokumen lama"
                                src="{{ route('documents.pdf', $document) }}#toolbar=1&navpanes=0&view=Fit"
                                class="position-relative" style="width:100%;height:100%;border:0"></iframe>
                    </div>
                    <div class="card-footer bg-white small text-muted">
                        Telusuri halamannya di sini untuk memastikan berapa halaman awal yang perlu dibuang.
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
