{{-- Formulir unggah Informasi — dipakai Tambah DAN Perbarui.

     Bentuknya menyesuaikan KATEGORI, bukan satu formulir seragam: field yang
     tak dipakai kategori ini tidak ditampilkan sama sekali
     (`informasi_kategori.kolom_json`, disetel Admin di Master Data — semula
     data sistem lama, atas ketetapan pemilik). Itu
     yang membuat pengunggah MEMO tak pernah melihat tiga kotak yang tak pernah
     ia isi — dan Form Request menolak kiriman yang tetap memuatnya. --}}
@php
    $kolom = \App\Models\Informasi::kolomEkstra($kategori);
    $ekstensi = \App\Models\Informasi::ekstensi($kategori);
    $label = \App\Models\Informasi::labelKategori($kategori);

    // Roll-over Edisi/Revisi memakai SATU rumus milik DocumentService — modul
    // ini sempat punya rumus keduanya sendiri (`no_revisi + 1` telanjang),
    // sehingga induk Edisi 1 Rev 4 memuat "Rev 5" yang tak pernah ada.
    // Aturannya: revisi 0..4, revisi berikutnya = Edisi+1 Rev 0 (CLAUDE.md §7).
    [$edisiBaru, $revisiBaru] = $induk
        ? \App\Services\DocumentService::nextEditionRevision(max(1, (int) ($induk->edisi ?: 1)), (int) $induk->no_revisi)
        : [1, 0];
@endphp

@if ($errors->any())
    <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      @if ($induk)
          data-confirm="Versi yang sekarang berlaku ({{ $induk->labelRevisi() ?? $induk->judul }}) akan dipindahkan ke Riwayat, dan versi baru inilah yang ditampilkan dengan nomor {{ $induk->nomor }}@if (in_array('no_revisi', $kolom, true)) — akan menjadi Edisi {{ $edisiBaru }} Rev {{ $revisiBaru }}@endif."
          data-confirm-title="Perbarui {{ $induk->nomor }}?" data-confirm-icon="warning" data-confirm-ok="Ya, perbarui"
      @endif>
    @csrf

    <div class="mb-3">
        <label class="form-label small fw-semibold">Kategori</label>
        <input type="text" class="form-control bg-light" value="{{ $label }}" readonly>
        {{-- Pada Perbarui, kategori DIWARISI dari induknya dan Form Request
             menolak kiriman yang memuatnya — jadi tak ada input tersembunyi. --}}
        @unless ($induk)
            <input type="hidden" name="kategori" value="{{ $kategori }}">
        @endunless
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Nomor Dokumen</label>
        @if ($induk)
            <input type="text" class="form-control bg-light font-monospace" value="{{ $induk->nomor }}" readonly>
            <div class="form-text">Nomor diwarisi dari versi yang berlaku — versi baru memang bernomor sama.</div>
        @else
            <input type="text" name="nomor" value="{{ old('nomor') }}" class="form-control font-monospace" required
                   placeholder="mis. {{ \App\Models\Pengaturan::prefix() }}-KBJ-01">
            <div class="form-text">Sudah ada dokumen bernomor ini? Pakai tombol <strong>Perbarui</strong> pada barisnya, bukan Tambah.</div>
        @endif
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Judul</label>
        <input type="text" name="judul" value="{{ old('judul', $induk?->judul) }}" class="form-control" required>
    </div>

    @if ($kolom)
        <div class="row g-3">
            @if (in_array('edisi', $kolom, true))
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Edisi</label>
                    <input type="number" name="edisi" value="{{ old('edisi', $edisiBaru) }}" min="1" max="99" class="form-control" required>
                </div>
            @endif
            @if (in_array('no_revisi', $kolom, true))
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">No. Revisi</label>
                    <input type="number" name="no_revisi" value="{{ old('no_revisi', $revisiBaru) }}" min="0" max="4" class="form-control" required>
                </div>
            @endif
            @if (in_array('tanggal_efektif', $kolom, true))
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Tanggal Efektif</label>
                    <input type="date" name="tanggal_efektif" value="{{ old('tanggal_efektif', $induk?->tanggal_efektif?->format('Y-m-d')) }}" class="form-control" required>
                </div>
            @endif
        </div>
    @endif

    <div class="mt-3">
        <label class="form-label small fw-semibold">Berkas</label>
        <input type="file" name="berkas" accept="{{ collect($ekstensi)->map(fn ($e) => '.'.$e)->implode(',') }}" class="form-control" required>
        <div class="form-text">
            Format: <strong>{{ strtoupper(implode(', ', $ekstensi)) }}</strong>. Maksimal <strong>40 MB</strong> (batas server).
            Berkas disimpan privat — hanya bisa dibuka pengguna yang sudah masuk.
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-pp">
            <i class="bi {{ $induk ? 'bi-arrow-repeat' : 'bi-upload' }}"></i>
            {{ $induk ? 'Perbarui & Berlakukan' : 'Unggah' }}
        </button>
        <a href="{{ route('informasi.index', ['kategori' => $kategori]) }}" class="btn btn-light">Batal</a>
    </div>
</form>
