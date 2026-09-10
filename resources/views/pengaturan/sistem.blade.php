@extends('layouts.app')
@section('title', 'Konfigurasi Sistem')

{{-- Konfigurasi Sistem — AI & Kesehatan (PLAN-AKSES-v8 Fase 5c).

     DUA kartu di satu layar, dan keduanya memang sepasang: yang satu menyetel
     satu-satunya layanan luar yang dipanggil sistem ini, yang lain menjawab
     "sistemnya masih waras?". Keduanya dibuka pada saat yang sama — waktu ada
     yang tidak beres.

     TIGA hal yang sengaja TIDAK ada di sini:

       • Kotak isian perintah artisan. Yang tersedia dua tombol tetap
         (bersihkan cache + reset cache izin). Kotak perintah adalah eksekusi
         kode jarak jauh dengan nama lain.
       • Kotak alamat tujuan email uji. Tujuannya SELALU alamat Admin yang
         sedang login — kalau tidak, layar ini jadi pengirim surat atas nama
         perusahaan bagi siapa pun pemegang user.manage.
       • Kunci API dalam bentuk terbaca. Yang tampil bertopeng, dan kotak
         kosong berarti "pertahankan yang lama", bukan "hapus". --}}

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Konfigurasi Sistem</h1>
        <p class="text-muted small mb-0">
            Setelan bantuan AI dan keadaan sistem.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        {{-- ================================ AI ================================ --}}
        <div class="col-lg-7">
            <form method="POST" action="{{ route('pengaturan.sistem.ai') }}"
                  data-confirm="Setelan AI berlaku untuk peninjauan berikutnya. Kunci API yang dikosongkan tidak akan terhapus."
                  data-confirm-title="Simpan setelan AI?" data-confirm-ok="Ya, simpan">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex align-items-center gap-2">
                        <i class="bi bi-robot text-primary"></i>
                        <div>
                            <span class="fw-semibold">Bantuan AI</span>
                            <div class="text-muted small">
                                AI hanya <strong>membantu</strong> peninjau — ia tak pernah meloloskan
                                atau menolak dokumen sendiri.
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="ai-enabled"
                                   name="enabled" value="1" @checked(old('enabled', $ai['enabled']))>
                            <label class="form-check-label fw-semibold small" for="ai-enabled">Aktifkan bantuan AI</label>
                            <div class="form-text">
                                Dimatikan, panel AI hilang dari layar tinjau dan sisanya berjalan
                                seperti biasa — peninjauan tak pernah bergantung padanya.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold" for="provider">Penyedia</label>
                                <select class="form-select" id="provider" name="provider" required>
                                    @foreach ($penyedia as $kunci => $label)
                                        <option value="{{ $kunci }}" @selected(old('provider', $ai['provider']) === $kunci)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label small fw-semibold" for="model">Model</label>
                                <input type="text" class="form-control font-monospace" id="model" name="model"
                                       value="{{ old('model', $ai['model']) }}" maxlength="120" required
                                       placeholder="mis. gemini-2.0-flash">
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label small fw-semibold" for="key">Kunci API</label>
                            <input type="password" class="form-control font-monospace" id="key" name="key"
                                   maxlength="400" autocomplete="new-password"
                                   placeholder="{{ $keyTopeng ?? 'belum disetel' }}">
                            <div class="form-text">
                                @if ($keyTopeng)
                                    Tersimpan &amp; tersandi: <span class="font-monospace">{{ $keyTopeng }}</span>.
                                    Kosongkan untuk mempertahankannya.
                                @else
                                    Belum ada kunci tersimpan di basis data — sistem memakai nilai dari
                                    <span class="font-monospace">.env</span> bila ada.
                                @endif
                            </div>
                            @if ($keyTopeng)
                                <div class="form-check small mt-1">
                                    <input class="form-check-input" type="checkbox" id="hapus-key" name="hapus_key" value="1">
                                    <label class="form-check-label text-danger" for="hapus-key">Hapus kunci yang tersimpan</label>
                                </div>
                            @endif
                        </div>

                        {{-- Cadangan: dipakai HANYA bila panggilan ke penyedia utama gagal
                             (kredit habis, key dicabut, penyedianya tumbang). Membawa key
                             sendiri, jadi satu akun yang kehabisan kredit tak ikut
                             menjatuhkan cadangannya. --}}
                        <hr class="my-4">
                        <div class="small fw-semibold text-secondary mb-2">
                            <i class="bi bi-arrow-repeat"></i> Penyedia cadangan <span class="fw-normal text-muted">(opsional)</span>
                        </div>
                        <p class="text-muted small">
                            Dipakai hanya bila panggilan ke penyedia utama gagal. Kosongkan
                            penyedianya untuk mematikan cadangan.
                        </p>

                        <div class="row g-3">
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold" for="cadangan_provider">Penyedia cadangan</label>
                                <select class="form-select" id="cadangan_provider" name="cadangan_provider">
                                    <option value="">— tanpa cadangan —</option>
                                    @foreach ($penyedia as $kunci => $label)
                                        <option value="{{ $kunci }}" @selected(old('cadangan_provider', $ai['cadangan_provider']) === $kunci)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label small fw-semibold" for="cadangan_model">Model cadangan</label>
                                <input type="text" class="form-control font-monospace" id="cadangan_model" name="cadangan_model"
                                       value="{{ old('cadangan_model', $ai['cadangan_model']) }}" maxlength="120">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="cadangan_key">Kunci API cadangan</label>
                                <input type="password" class="form-control font-monospace" id="cadangan_key" name="cadangan_key"
                                       maxlength="400" autocomplete="new-password"
                                       placeholder="{{ $cadanganKeyTopeng ?? 'belum disetel' }}">
                                @if ($cadanganKeyTopeng)
                                    <div class="form-check small mt-1">
                                        <input class="form-check-input" type="checkbox" id="hapus-cadangan-key" name="hapus_cadangan_key" value="1">
                                        <label class="form-check-label text-danger" for="hapus-cadangan-key">Hapus kunci cadangan yang tersimpan</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center gap-2">
                        <span class="text-muted small">
                            <i class="bi bi-shield-lock"></i> Kunci disimpan tersandi; audit log mencatat
                            perubahannya tanpa isinya.
                        </span>
                        <button type="submit" class="btn btn-pp"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </div>
            </form>

            {{-- Uji koneksi AI (PLAN-PREPRODUKSI-v9 Fase 2a).

                 DI LUAR form penyimpan setelan, dan itu bukan soal tata letak:
                 form bersarang tidak sah di HTML, dan tombol di dalam form yang
                 salah akan MENYIMPAN setelan, bukan mengujinya.

                 Yang diuji adalah kunci yang SUDAH TERSIMPAN — jadi ganti model
                 atau kunci, tekan Simpan dulu, baru uji. --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="small mb-0">
                        <span class="fw-semibold">Uji koneksi AI.</span>
                        Memanggil penyedia sekali tanpa membangkitkan tinjauan — menjawab
                        &ldquo;kuncinya hidup atau tidak&rdquo; dalam hitungan detik, bukan menit.
                        Yang diuji adalah setelan yang sudah <strong>tersimpan</strong>.
                    </div>
                    <form method="POST" action="{{ route('pengaturan.sistem.uji-ai') }}"
                          data-confirm="Satu panggilan ke penyedia AI, tanpa biaya tinjauan. Batas 6 kali per menit."
                          data-confirm-title="Uji koneksi AI?" data-confirm-ok="Ya, uji">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">
                            <i class="bi bi-plug"></i> Uji koneksi AI
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================= KESEHATAN ============================= --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-activity text-primary"></i>
                    <div>
                        <span class="fw-semibold">Kesehatan Sistem</span>
                        <div class="text-muted small">Keadaan server &amp; isi basis data.</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr><td class="text-muted small">PHP</td><td class="font-monospace small">{{ $kesehatan['php'] }}</td></tr>
                            <tr><td class="text-muted small">Laravel</td><td class="font-monospace small">{{ $kesehatan['laravel'] }}</td></tr>
                            <tr><td class="text-muted small">Zona waktu</td><td class="font-monospace small">{{ $kesehatan['zona'] }}</td></tr>
                            <tr>
                                <td class="text-muted small">Mode debug</td>
                                <td>
                                    @if ($kesehatan['debug'])
                                        {{-- Bukan hiasan: APP_DEBUG menyala di server produksi
                                             menampilkan jejak galat lengkap — termasuk isi .env —
                                             kepada siapa pun yang memicu error. --}}
                                        <span class="badge-soft badge-soft-danger">Menyala — matikan di server produksi</span>
                                    @else
                                        <span class="badge-soft badge-soft-success">Mati</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Lampiran foto</td>
                                <td class="small">
                                    {{ number_format($kesehatan['lampiran_bytes'] / 1048576, 1) }} MB
                                    <span class="text-muted" style="font-size:.72rem">(diperbarui tiap 5 menit)</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted small">Dokumen</td>
                                <td class="small">{{ $kesehatan['dokumen'] }} total &middot; {{ $kesehatan['dokumen_berlaku'] }} Berlaku</td>
                            </tr>
                            <tr><td class="text-muted small">Pengguna aktif</td><td class="small">{{ $kesehatan['pengguna'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-envelope text-primary"></i>
                    <div>
                        <span class="fw-semibold">Pengiriman Email</span>
                        <div class="text-muted small">Kanal notifikasi penting; lonceng tak terpengaruh.</div>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">Mailer</dt>
                        <dd class="col-7 font-monospace mb-1">{{ $kesehatan['mailer'] }}</dd>
                        <dt class="col-5 text-muted fw-normal">Host</dt>
                        <dd class="col-7 font-monospace mb-1">{{ $kesehatan['mail_host'] ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Pengirim</dt>
                        <dd class="col-7 font-monospace mb-0">{{ $kesehatan['mail_dari'] ?? '—' }}</dd>
                    </dl>

                    @if ($kesehatan['mailer'] === 'log')
                        <div class="alert alert-warning py-2 small mt-3 mb-0">
                            Mailer masih <span class="font-monospace">log</span> — email tidak benar-benar
                            terkirim, isinya ditulis ke <span class="font-monospace">storage/logs</span>.
                        </div>
                    @endif

                    @if ($kesehatan['mail_paksa_ke'])
                        {{-- Katup pengaman peralihan (docs/PANDUAN-PRODUKSI-EMAIL.md). Wajib
                             terlihat di sini: selama ia terisi, SELURUH notifikasi email
                             mendarat di satu alamat dan tak seorang penerima pun sadar. --}}
                        <div class="alert alert-danger py-2 small mt-3 mb-0">
                            Seluruh email sedang <strong>dibelokkan</strong> ke
                            <span class="font-monospace">{{ $kesehatan['mail_paksa_ke'] }}</span>
                            (<span class="font-monospace">MAIL_PAKSA_KE</span>). Penerima sesungguhnya
                            tidak menerima apa pun.
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('pengaturan.sistem.uji-email') }}"
                          data-confirm="Email uji dikirim ke alamat akun Anda sendiri. Batas 3 kali per menit."
                          data-confirm-title="Kirim email uji?" data-confirm-ok="Ya, kirim">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-send"></i> Kirim email uji ke alamat saya
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center gap-2">
                    <i class="bi bi-eraser text-primary"></i>
                    <div>
                        <span class="fw-semibold">Pemeliharaan</span>
                        <div class="text-muted small">Dipakai setelah mengubah izin atau setelan.</div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Membersihkan cache aplikasi dan cache izin (spatie). Tidak menyentuh satu pun
                        data: dokumen, pengguna, dan setelan tetap utuh — yang dibuang hanya
                        salinan sementaranya.
                    </p>
                    <form method="POST" action="{{ route('pengaturan.sistem.cache') }}"
                          data-confirm="Cache aplikasi & cache izin akan dibersihkan. Tidak ada data yang terhapus."
                          data-confirm-title="Bersihkan cache?" data-confirm-ok="Ya, bersihkan">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="bi bi-arrow-clockwise"></i> Bersihkan cache
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Zona Berbahaya (PLAN C §C4, dipindah ke sini oleh v9 Fase 2b) =====

         Dulu di Manajemen User. Tempatnya memang salah: ia bukan urusan akun,
         dan layar INI sudah jadi rumah bagi aksi sistemik lain (bersihkan cache,
         uji email, uji AI). Izinnya tidak berubah sedikit pun — `documents.purgeAll`
         dan `pengaturan.sistem` sama-sama di dalam grup `can:user.manage`.

         Tetap TIDAK ditaruh di Dokumen Berlaku/Tidak Berlaku: di sana ia
         bertetangga dengan tombol Musnahkan satuan yang bentuknya mirip, dan
         salah klik di antara keduanya berbeda SATU dokumen dengan SELURUH arsip. --}}
    <div class="card border-danger shadow-sm mt-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-octagon text-danger"></i>
            <span class="fw-semibold text-danger">Zona Berbahaya</span>
        </div>
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="small mb-0">
                <span class="fw-semibold">Bersihkan seluruh dokumen.</span>
                Menghapus {{ $kesehatan['dokumen_semua'] }} dokumen beserta lampiran, versi, dan riwayatnya — tanpa undo.
                Akun, departemen, dan Audit Log tidak tersentuh.
            </div>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalMusnahkanSemua"
                    @disabled($kesehatan['dokumen_semua'] === 0)>
                <i class="bi bi-trash3"></i> Bersihkan Seluruh Dokumen
            </button>
        </div>
    </div>

    @include('documents._modal-musnahkan-semua', ['jumlahDokumen' => $kesehatan['dokumen_semua']])
@endsection
