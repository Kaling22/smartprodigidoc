@extends('layouts.app')
@section('title', 'Dokumen Baru')

@section('content')
    <div class="mb-3">
        <a href="{{ route('documents.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Dokumen Baru {{ $type->code }}{{ $unggahanSaja ? '' : ' — Langkah 1' }}</h1>
        <p class="text-muted small mb-0">
            @if ($unggahanSaja)
                {{ $type->name }} didaftarkan dengan <strong>mengunggah berkas PDF-nya</strong> — tanpa pengisian berbab.
            @else
                Tentukan nomor, judul, dan departemen dokumen. Sudah punya berkas PDF dokumen lama? Nyalakan saklar di bawah.
            @endif
        </p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    {{-- SATU formulir, dua bentuk isian (butir 0). Saklar "dokumen lama"
                         menukar bagian bawahnya: alih-alih membuat draft yang menuju
                         wizard, ia mendaftarkan PDF yang sudah ada dan langsung Berlaku.
                         Yang menentukan tujuan kirimnya adalah `:action` — pintu masuknya
                         satu ("Dokumen Baru"), penerimanya dua. --}}
                    <form method="POST" enctype="multipart/form-data"
                          x-data="{ manual: {{ old('doc_number_manual') ? 'true' : 'false' }}, arsip: {{ $unggahanSaja || old('arsip') ? 'true' : 'false' }} }"
                          :action="arsip ? '{{ route('documents.arsip.store') }}' : '{{ route('documents.store') }}'"
                          action="{{ route('documents.store') }}">
                        @csrf

                        <div class="alert alert-warning py-2 small d-flex align-items-start gap-2 mb-3" x-show="arsip" x-cloak>
                            <i class="bi bi-archive mt-1"></i>
                            <div>
                                <strong>{{ $unggahanSaja ? 'Dokumen unggahan.' : 'Mode dokumen lama.' }}</strong>
                                <strong>Langsung Berlaku</strong> tanpa tinjau–setuju; SH/DH diberi tahu dan
                                pendaftarannya tercatat di Audit Log.
                            </div>
                        </div>

                        {{-- Saklar hanya ada pada jenis berwizard. Pada FK/PX ia dikunci
                             menyala oleh $unggahanSaja (lihat x-data di atas); yang tersisa
                             hanya input tersembunyinya, supaya isian terisi ulang dengan
                             benar saat validasi gagal. --}}
                        <div class="form-check form-switch mb-3">
                            <input type="hidden" name="arsip" :value="arsip ? 1 : 0">
                            @unless ($unggahanSaja)
                                <input class="form-check-input" type="checkbox" id="arsipToggle" x-model="arsip"
                                       @change="if (arsip) manual = true">
                                <label for="arsipToggle" class="form-check-label small fw-semibold">
                                    Ini dokumen lama — unggah berkas PDF-nya
                                </label>
                            @endunless
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Dokumen</label>
                            <input type="text" class="form-control bg-light" value="{{ $type->code }} — {{ $type->name }}" readonly>
                            <input type="hidden" name="document_type_id" value="{{ $type->id }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Departemen</label>
                            @if ($canChooseDept)
                                <select name="department_id" class="form-select" required>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(old('department_id', $defaultDept?->id) == $dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control" value="{{ $defaultDept?->code }} — {{ $defaultDept?->name }}" readonly>
                                <input type="hidden" name="department_id" value="{{ $defaultDept?->id }}">
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul Dokumen</label>
                            <input type="text" name="title" value="{{ old('title') }}" class="form-control" required placeholder="mis. Prosedur Backup Data Server">
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <label class="form-label small fw-semibold mb-0">Nomor Dokumen</label>
                                {{-- Saklar manual disembunyikan di mode dokumen lama: nomornya
                                     memang nomor lamanya, tak pernah dibangkitkan mesin. --}}
                                <div class="form-check form-switch" x-show="!arsip">
                                    <input type="hidden" name="doc_number_manual" :value="manual ? 1 : 0">
                                    <input class="form-check-input" type="checkbox" id="manualToggle" x-model="manual">
                                    <label for="manualToggle" class="form-check-label small">Input manual</label>
                                </div>
                            </div>

                            {{-- Auto (default): read-only preview --}}
                            <div x-show="!manual && !arsip" x-cloak>
                                <input type="text" class="form-control bg-light" value="{{ $numberPreview }}" readonly>
                                <div class="form-text"><i class="bi bi-magic"></i> <strong>Nomor sementara</strong> — final dikunci setelah disetujui. Format: {{ \App\Models\Pengaturan::prefix() }}-JENIS-DEPT-NN.</div>
                            </div>

                            {{-- Manual: editable + uniqueness validated --}}
                            <div x-show="manual || arsip" x-cloak>
                                <input type="text" name="doc_number" value="{{ old('doc_number') }}" class="form-control" placeholder="mis. {{ \App\Models\Pengaturan::prefix() }}-SOP-ICTMD-05">
                                <div class="form-text">
                                    <span x-show="!arsip">Pastikan nomor unik dan sesuai format.</span>
                                    <span x-show="arsip" x-cloak>Tulis nomor dokumen{{ $unggahanSaja ? '' : ' lamanya' }} apa adanya. Nomor di luar pola resmi diterima dan ditandai badge <strong>Nomor Lama</strong>.</span>
                                </div>
                            </div>
                        </div>

                        {{-- Isian khusus dokumen lama. Metadata di sini sudah TERCETAK di
                             berkasnya, jadi ia diketik ulang sebagai data supaya daftar
                             induk & penomoran bisa membacanya — bukan supaya dicetak lagi. --}}
                        <div x-show="arsip" x-cloak>
                            <div class="row g-3 mt-0">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Edisi</label>
                                    <input type="number" name="edisi" value="{{ old('edisi', 1) }}" min="1" max="99" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">No. Revisi</label>
                                    <input type="number" name="no_revisi" value="{{ old('no_revisi', 0) }}" min="0" max="4" class="form-control">
                                    <div class="form-text">0–4. Revisi ke-5 menaikkan Edisi.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tanggal Efektif</label>
                                    <input type="date" name="tanggal_efektif" value="{{ old('tanggal_efektif') }}" class="form-control">
                                    <div class="form-text">Kosong = hari ini.</div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="form-label small fw-semibold">Berkas PDF {{ $unggahanSaja ? $type->name : 'Dokumen Lama' }}</label>
                                <input type="file" name="berkas" accept="application/pdf" class="form-control">
                                <div class="form-text">
                                    PDF saja, maksimal <strong>40 MB</strong> (batas server). Berkas disimpan privat —
                                    hanya bisa dibuka lewat tombol PDF oleh pengguna yang berhak.
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-pp" x-show="!arsip">
                                <i class="bi bi-arrow-right-circle"></i> Buat &amp; Lanjut Pengisian
                            </button>
                            <button class="btn btn-pp" x-show="arsip" x-cloak
                                    data-confirm="Dokumen ini langsung BERLAKU tanpa melewati tinjau–setuju. Lanjutkan?"
                                    data-confirm-title="Daftarkan {{ $unggahanSaja ? $type->code : 'Dokumen Lama' }}?" data-confirm-icon="warning" data-confirm-ok="Ya, daftarkan">
                                <i class="bi bi-archive"></i> Daftarkan &amp; Berlakukan
                            </button>
                            <a href="{{ route('documents.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-secondary"><i class="bi bi-info-circle"></i> Tentang Penomoran</h2>
                    <p class="small text-muted mb-2">Nomor mengikuti pola tetap dan bertambah otomatis per jenis + departemen:</p>
                    <code class="d-block small mb-2">{{ \App\Models\Pengaturan::prefix() }}-<span class="text-primary">SOP</span>-<span class="text-success">ICTMD</span>-<span class="text-danger">01</span></code>
                    <p class="small text-muted mb-0">Aktifkan <strong>Input manual</strong> hanya bila perlu menyesuaikan nomor lama.</p>
                </div>
            </div>
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
@endsection
