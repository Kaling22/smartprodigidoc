@extends('layouts.app')
@section('title', 'Perbaiki Dokumen Lama: ' . $document->displayNumber())

@section('content')
    <div class="mb-3">
        <a href="{{ route('documents.published') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Perbaiki Dokumen Lama</h1>
        <p class="text-muted small mb-0">
            <span class="font-monospace">{{ $document->displayNumber() }}</span> — {{ $document->title }}
        </p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <form method="POST" action="{{ route('documents.arsip.update', $document) }}" enctype="multipart/form-data"
                          data-confirm="Simpan perubahan pada dokumen lama ini? Perubahannya tercatat di Audit Log."
                          data-confirm-title="Simpan Perubahan?" data-confirm-ok="Ya, simpan">
                        @csrf @method('PUT')

                        {{-- Jenis & departemen sengaja hanya DITAMPILKAN: keduanya
                             menyusun nomor dokumen sekaligus jalur berkasnya, jadi
                             mengubahnya di sini akan meninggalkan berkas di folder yang
                             salah dan nomor yang tak lagi cocok dengan isinya. --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Jenis Dokumen</label>
                                <input type="text" class="form-control bg-light" value="{{ $document->type->code }} — {{ $document->type->name }}" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Departemen</label>
                                <input type="text" class="form-control bg-light" value="{{ $document->department->code }} — {{ $document->department->name }}" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nomor Dokumen</label>
                            <input type="text" name="doc_number" value="{{ old('doc_number', $document->displayNumber()) }}" class="form-control font-monospace" required>
                            <div class="form-text">Nomor lama diterima apa adanya; yang di luar pola resmi ditandai badge <strong>Nomor Lama</strong>.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul Dokumen</label>
                            <input type="text" name="title" value="{{ old('title', $document->title) }}" class="form-control" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Edisi</label>
                                <input type="number" name="edisi" value="{{ old('edisi', (int) ($document->edisi ?: 1)) }}" min="1" max="99" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">No. Revisi</label>
                                <input type="number" name="no_revisi" value="{{ old('no_revisi', $document->no_revisi) }}" min="0" max="4" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Tanggal Efektif</label>
                                <input type="date" name="tanggal_efektif" value="{{ old('tanggal_efektif', $document->published_at?->format('Y-m-d')) }}" class="form-control">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small fw-semibold">Ganti Berkas PDF</label>
                            <input type="file" name="berkas" accept="application/pdf" class="form-control">
                            <div class="form-text">
                                Kosongkan bila berkasnya tidak diganti. PDF saja, maksimal <strong>40 MB</strong>.
                                <a href="{{ route('documents.pdf', $document) }}" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Lihat berkas sekarang</a>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-pp"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
                            @if ($document->jenisBerlembarRevisi())
                                {{-- Jalan masuk kedua ke lembar Catatan Revisi (Fase H):
                                     halaman itu muncul otomatis sesudah unggahan, tapi
                                     pemakainya boleh menekan "Nanti Saja" — tanpa tautan
                                     ini, "nanti" berarti tak pernah. --}}
                                <a href="{{ route('documents.arsip.catatan', $document) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-list-columns-reverse"></i> Lembar Catatan Revisi
                                </a>
                            @endif
                            <a href="{{ route('documents.published') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-secondary"><i class="bi bi-info-circle"></i> Yang Perlu Diketahui</h2>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>Dokumen ini <strong>Berlaku</strong> — perbaikan di sini mengubah apa yang dilihat seluruh departemen seketika.</li>
                        <li>Setiap perubahan tercatat di <strong>Audit Log</strong> beserta nilai sebelum &amp; sesudahnya.</li>
                        <li>Salah jenis atau departemen? Keduanya tak bisa diubah — musnahkan dokumennya lalu daftarkan ulang.</li>
                        <li>Berkas lama dihapus dari disk begitu penggantinya tersimpan.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
