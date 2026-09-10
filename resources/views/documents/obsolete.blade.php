@extends('layouts.app')
@section('title', 'Dokumen Tidak Berlaku')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Dokumen Tidak Berlaku</h1>
        <p class="text-muted small mb-0"><i class="bi bi-slash-circle"></i> Dokumen yang sudah tidak berlaku, beserta versi lama yang digantikan revisi.</p>
    </div>

    {{-- Filter & cari --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. {{ \App\Models\Pengaturan::prefix() }}-SOP atau judul...">
                </div>
                @if ($departments->isNotEmpty())
                <div class="col-md-4">
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
                        <a href="{{ route('documents.obsolete') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                        <tr>@include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th><th>Jenis</th><th>Dept</th><th class="text-center">Edisi</th>@include('partials._urut-th', ['kolom' => 'revisi', 'label' => 'Revisi', 'kelas' => 'text-center'])<th>Pembuat</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    {{-- Satu <tbody> per DOKUMEN (bukan per baris): itulah cakupan
                         Alpine yang sah untuk menyembunyikan/menampilkan baris versi
                         di bawah induknya — <tr> bersaudara tak bisa berbagi x-data,
                         dan <div> pembungkus tidak sah di dalam <table>. --}}
                    @forelse ($documents as $doc)
                        @php $versiDoc = $versi[$doc->doc_number] ?? collect([$doc]); @endphp
                        <tbody x-data="{ buka: false }">
                            <tr>
                                <td class="font-monospace small">
                                    {{-- Nomor DILEPAS (Fase F): barisnya tetap memegang
                                         nomor lamanya supaya arsip terbaca, tapi nomor itu
                                         sudah kembali ke kolam — dua dokumen boleh sah
                                         memegangnya. Dicoret supaya tak terbaca sebagai
                                         nomor yang masih menunjuk dokumen ini. --}}
                                    <span @class(['text-decoration-line-through' => $doc->nomorDilepas()])>{{ $doc->displayNumber() }}</span>
                                    @if ($doc->nomorDilepas())
                                        <span class="badge-soft badge-soft-warning" title="Nomor kembali ke kolam dan bisa dipakai dokumen baru">Nomor dilepas</span>
                                    @endif
                                    @include('partials._badge-nomor-lama', ['doc' => $doc])
                                </td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge-soft">{{ $doc->type->code }}</span></td>
                                <td><span class="badge-soft">{{ $doc->department->code ?? '—' }}</span></td>
                                <td class="text-center small">{{ $doc->edisi ?? 1 }}</td>
                                <td class="text-center small">{{ $doc->no_revisi ?? 0 }}</td>
                                <td class="small">{{ $doc->creator->name ?? '—' }}</td>
                                <td class="text-end">
                                    {{-- SATU penanda untuk satu hal. Dulu dua: chevron
                                         sekecil huruf di kolom nomor, dan badge "N versi"
                                         yang mencolok tapi bukan tombol. Chevron-nya kini
                                         inline di tombol ini, jadi colspan="8" di bawah
                                         tak ikut berubah. Grup berisi satu versi tetap
                                         tanpa tombol — dokumen yang dimatikan (bukan
                                         direvisi) memang selalu tunggal.

                                         Tetap DI LUAR strip aksi (PLAN C §C3): ini
                                         penyingkap baris, bukan aksi atas dokumen. --}}
                                    @if ($versiDoc->count() > 1)
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                x-on:click="buka = ! buka" :aria-expanded="buka"
                                                aria-controls="versi-{{ $doc->id }}">
                                            <i class="bi" :class="buka ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
                                            {{ $versiDoc->count() }} versi
                                        </button>
                                    @endif
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <x-aksi>
                                        <a href="{{ route('documents.show', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat</a>
                                        {{-- Grup berisi SATU versi tak punya dropdown, jadi tombol
                                             Rollback-nya ikut ke baris induk. Pada grup bertingkat
                                             ia sengaja TIDAK di sini: memilih versi mana yang
                                             dituju harus eksplisit, dan itu urusan baris versi. --}}
                                        @if ($versiDoc->count() === 1)
                                            @include('documents._tombol-rollback', ['v' => $doc, 'adaBerlaku' => $adaBerlaku])
                                        @endif
                                        {{-- Aktifkan kembali — Admin IT saja.

                                             Bila nomor finalnya MASIH dipegang dokumen
                                             Berlaku (biasanya penerusnya sendiri),
                                             peringatannya berbeda dan formnya membawa
                                             `nomor_baru` — server memeriksa ulang, jadi
                                             atribut ini cuma menerangkan, bukan penjaga. --}}
                                        @can('user.manage')
                                        @php
                                            // Nomor yang sudah dilepas SELALU diganti saat diaktifkan
                                            // lagi (Fase F) — server memeriksanya ulang, jadi ini
                                            // hanya menerangkan lebih dulu.
                                            $bentrok = $doc->nomorDilepas()
                                                || (filled($doc->doc_number_final) && $nomorTerpakai->contains($doc->doc_number_final));
                                        @endphp
                                        <form method="POST" action="{{ route('documents.restoreObsolete', $doc) }}" class="d-inline"
                                              @if ($bentrok)
                                                  data-confirm="{{ $doc->nomorDilepas()
                                                      ? 'Nomor ' . $doc->doc_number_final . ' sudah dilepas saat dokumen ini dinonaktifkan. Aktifkan ' . $doc->title . ' dengan NOMOR BARU (dibuat otomatis)?'
                                                      : 'Nomor ' . $doc->doc_number_final . ' masih dipakai dokumen yang Berlaku. Aktifkan ' . $doc->title . ' dengan NOMOR BARU (dibuat otomatis)?' }}"
                                                  data-confirm-title="Nomor Bentrok" data-confirm-ok="Ya, beri nomor baru" data-confirm-icon="warning"
                                              @else
                                                  data-confirm="Aktifkan kembali {{ $doc->displayNumber() }}? Dokumen akan kembali berstatus Berlaku."
                                                  data-confirm-title="Aktifkan Kembali?" data-confirm-ok="Ya, aktifkan"
                                              @endif>
                                            @csrf
                                            @if ($bentrok)<input type="hidden" name="nomor_baru" value="1">@endif
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Aktifkan</button>
                                        </form>
                                        @endcan
                                        {{-- Arsipkan = wewenang GL (dokumen buatannya) / MD / Admin
                                             sejak Fase C — sebelumnya SH/DH/PJO. Syaratnya SAMA
                                             PERSIS dengan DocumentController::destroy().
                                             Label lama berbunyi "Hapus PERMANEN … tidak bisa dibatalkan"
                                             padahal aksinya soft-delete — barisnya tetap ada dan nomornya
                                             tetap terkunci. Yang benar-benar memusnahkan adalah tombol
                                             Musnahkan di sebelahnya (Admin saja). --}}
                                        @if (auth()->user()->can('document.request_revision')
                                            && (auth()->user()->can('document.view_all') || $doc->created_by === auth()->id()))
                                        <form method="POST" action="{{ route('documents.destroy', $doc) }}" class="d-inline"
                                              data-confirm="Arsipkan {{ $doc->displayNumber() }}? Dokumen disingkirkan dari daftar, tetapi isi & nomornya tetap tersimpan dan bisa dipulihkan lewat basis data."
                                              data-confirm-title="Arsipkan Dokumen?" data-confirm-ok="Ya, arsipkan" data-confirm-icon="warning">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-archive"></i> Arsipkan</button>
                                        </form>
                                        @endif
                                        @can('user.manage')
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalMusnahkan{{ $doc->id }}">
                                            <i class="bi bi-trash3"></i> Musnahkan
                                        </button>
                                        @endcan
                                    </x-aksi>
                                </td>
                            </tr>
                            @if ($versiDoc->count() > 1)
                                <tr x-show="buka" x-cloak id="versi-{{ $doc->id }}">
                                    <td colspan="8" class="bg-light py-2">
                                        <table class="table table-sm mb-0 bg-transparent">
                                            <thead><tr class="small text-muted"><th class="text-center">Edisi</th><th class="text-center">Revisi</th><th>Tanggal nonaktif</th><th>Sebab</th><th class="text-end">Aksi</th></tr></thead>
                                            <tbody>
                                                @foreach ($versiDoc as $v)
                                                    <tr>
                                                        <td class="text-center small">{{ $v->edisi ?? 1 }}</td>
                                                        <td class="text-center small">{{ $v->no_revisi ?? 0 }}</td>
                                                        <td class="small">{{ $v->updated_at?->format('d/m/Y H:i') }} WITA</td>
                                                        <td class="small">
                                                            @if ($v->nomorDilepas())
                                                                <span class="badge-soft badge-soft-warning">Dinonaktifkan</span>
                                                            @else
                                                                <span class="badge-soft">Digantikan revisi</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-end text-nowrap">
                                                            <a href="{{ route('documents.pdf', $v) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i></a>
                                                            <a href="{{ route('documents.show', $v) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                                                            @include('documents._tombol-rollback', ['v' => $v, 'adaBerlaku' => $adaBerlaku])
                                                            {{-- Musnahkan (PLAN C §C1): syaratnya sama dengan baris induk.
                                                                 Tanpa tombol ini versi lama yang digantikan revisi hanya
                                                                 bisa dimusnahkan lewat CLI — padahal justru versi lamalah
                                                                 yang paling sering perlu dibersihkan. --}}
                                                            @can('user.manage')
                                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalMusnahkan{{ $v->id }}">
                                                                    <i class="bi bi-trash3"></i> Musnahkan
                                                                </button>
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Tidak ada dokumen tidak berlaku.</td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>

    {{-- Di LUAR tabel: di dalam <td> modal mewarisi konteks penumpukan tabel
         dan bisa tampil terpotong (alasan yang sama seperti di published). --}}
    @can('user.manage')
        {{-- Seluruh versi, bukan hanya baris induk — baris versi punya tombol
             Musnahkan sendiri sejak PLAN C §C1. `$versi[$doc->doc_number]` sudah
             memuat induknya sendiri (lihat $versiDoc di atas), jadi tak ada modal
             ganda dan tak ada yang terlewat. --}}
        @foreach ($documents as $doc)
            @foreach (($versi[$doc->doc_number] ?? collect([$doc])) as $v)
                @include('documents._modal-musnahkan', ['document' => $v])
            @endforeach
        @endforeach
    @endcan
@endsection
