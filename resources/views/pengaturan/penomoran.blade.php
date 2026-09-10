@extends('layouts.app')
@section('title', 'Penomoran Dokumen')

{{-- Penomoran Dokumen — setelan site (PLAN-AKSES-v8 Fase 5a).

     Layar ini menyetel dua nilai yang masuk ke NOMOR DOKUMEN MUTU, jadi
     bentuknya sengaja lebih berhati-hati daripada formulir biasa:

       • Pratinjau LANGSUNG (Alpine, tanpa muat ulang) — Admin melihat nomor
         yang akan lahir sebelum menekan Simpan, bukan sesudahnya.
       • Contoh SEBELUM & SESUDAH bersanding — perubahan prefix tak pernah
         terlihat dari satu kotak isian sendirian.
       • Peringatan tetap, bukan hanya di konfirmasi: yang berubah HANYA
         dokumen baru. Tak ada penomoran ulang surut, dan itu disengaja
         (PLAN-AKSES-v8 §12) — nomor lama sudah beredar di luar sistem. --}}

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Penomoran Dokumen</h1>
        <p class="text-muted small mb-0">
            Prefix nomor dan nama site yang dipakai seluruh dokumen mutu.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('pengaturan.penomoran.simpan') }}"
                  x-data="{ prefix: @js($prefix), namaSite: @js($namaSite), semula: @js($prefix) }"
                  data-confirm="Setelan ini hanya berlaku untuk dokumen yang dibuat SETELAH disimpan. Nomor dokumen yang sudah ada tidak akan berubah."
                  data-confirm-title="Ubah penomoran site?" data-confirm-icon="warning" data-confirm-ok="Ya, simpan">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <i class="bi bi-hash text-primary"></i>
                        <div>
                            <span class="fw-semibold">Setelan Site</span>
                            <div class="text-muted small">Berlaku untuk seluruh jenis dokumen dan 7 departemen.</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="prefix">Prefix Penomoran</label>
                            <input type="text" id="prefix" name="prefix" x-model="prefix"
                                   value="{{ old('prefix', $prefix) }}" maxlength="20" required
                                   class="form-control font-monospace text-uppercase">
                            <div class="form-text">
                                Huruf, angka, dan garis pisah tunggal. Huruf kecil dinaikkan otomatis.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="nama_site">Nama Site</label>
                            <input type="text" id="nama_site" name="nama_site" x-model="namaSite"
                                   value="{{ old('nama_site', $namaSite) }}" maxlength="60" required
                                   class="form-control text-uppercase">
                            <div class="form-text">
                                Tercetak pada judul daftar induk &amp; kop ekspor:
                                <span class="font-monospace">DAFTAR INDUK DOKUMEN SOP PPA SITE <span x-text="namaSite || '…'"></span></span>
                            </div>
                        </div>

                        {{-- Pratinjau: contoh nomor SEBELUM dan SESUDAH bersanding. --}}
                        <div class="border rounded p-3 bg-light">
                            <div class="small fw-semibold text-secondary mb-2">
                                <i class="bi bi-eye"></i> Pratinjau nomor dokumen baru
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <div class="text-muted" style="font-size:.72rem">Sekarang</div>
                                    <code class="small text-muted" x-text="semula + '-SOP-ICTMD-01'"></code>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div>
                                    <div class="text-muted" style="font-size:.72rem">Setelah disimpan</div>
                                    <code class="small" x-text="(prefix.toUpperCase() || '…') + '-SOP-ICTMD-01'"></code>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-end gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-light">Batal</a>
                        <button type="submit" class="btn btn-pp"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h2 class="h6 fw-bold text-secondary">
                        <i class="bi bi-exclamation-triangle text-warning"></i> Yang perlu diketahui
                    </h2>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">
                            Setelan ini <strong>hanya berlaku untuk dokumen baru</strong>. Nomor dokumen yang
                            sudah dibuat — apalagi yang sudah Berlaku — tidak ditulis ulang.
                        </li>
                        <li class="mb-2">
                            Penomoran ulang surut memang <strong>tidak disediakan</strong>: nomor dokumen mutu
                            sudah beredar di luar sistem (laporan audit, dokumen lain yang merujuknya,
                            cetakan di lapangan). Menulisnya ulang membuat rujukan itu menunjuk ke
                            sesuatu yang tak lagi bernama sama.
                        </li>
                        <li class="mb-2">
                            Karena itu daftar induk akan memuat <strong>dua pola nomor sekaligus</strong>
                            selama masa peralihan. Itu perilaku yang benar, bukan kesalahan.
                        </li>
                        <li class="mb-2">
                            Konsekuensinya: dokumen berprefix lama akan berlencana
                            <span class="badge-soft badge-soft-warning">Nomor Lama</span> — sebab lencana itu
                            memang berarti "di luar pola yang berlaku sekarang". Isinya tidak berubah dan
                            tetap bisa dibuka seperti biasa.
                        </li>
                        <li>
                            Perubahan dicatat di <a href="{{ route('audit.index') }}">Audit Log</a> lengkap
                            dengan nilai sebelum &amp; sesudahnya.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
