@extends('layouts.app')
@section('title', 'Master Data')

{{-- Master Data — departemen, jenis dokumen, kategori informasi
     (PLAN-AKSES-v8 Fase 5b).

     SATU layar tiga kartu, bukan tiga halaman: ketiganya daftar acuan yang
     jarang disentuh dan hampir selalu disentuh bersamaan (departemen baru
     biasanya datang bersama kategori barunya). Tiga menu untuk tiga tabel
     kecil hanya menambah tempat yang harus dicari.

     BENTUK FORMULIRNYA: satu <form> per BARIS, diletakkan di luar <table> dan
     ditautkan lewat atribut `form="…"`. Ini bukan kerumitan yang dicari-cari —
     HTML melarang <form> membungkus <tr>, dan peramban akan memindahkannya
     keluar tabel diam-diam sehingga tak satu pun input ikut terkirim. Atribut
     `form` adalah cara baku menautkannya, dan biayanya nol JavaScript.

     TAK ADA TOMBOL HAPUS di seluruh layar ini, dan itu disengaja: ketiga tabel
     ditunjuk data lain. Yang tersedia saklar "Aktif" — berhenti ditawarkan,
     bukan lenyap. --}}

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Master Data</h1>
        <p class="text-muted small mb-0">
            Departemen, jenis dokumen, dan kategori informasi yang dipakai seluruh sistem.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="alert alert-light border py-2 small d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div>
            Tidak ada penghapusan di layar ini. Departemen, jenis, dan kategori ditunjuk oleh
            dokumen &amp; pengguna yang sudah ada, jadi yang tersedia adalah saklar
            <strong>Aktif</strong>: barisnya berhenti ditawarkan pada formulir baru, sementara
            seluruh dokumen dan penggunanya tetap utuh dan tetap terbaca.
        </div>
    </div>

    {{-- ============================ DEPARTEMEN ============================ --}}
    @foreach ($departemen as $d)
        <form id="dept-{{ $d->id }}" method="POST" action="{{ route('pengaturan.master.departemen.simpan', $d) }}">
            @csrf @method('PUT')
        </form>
    @endforeach
    <form id="dept-baru" method="POST" action="{{ route('pengaturan.master.departemen.tambah') }}">@csrf</form>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-building text-primary"></i>
            <div>
                <span class="fw-semibold">Departemen</span>
                <div class="text-muted small">
                    Kode departemen ikut masuk ke nomor dokumen, jadi ia terkunci begitu
                    departemen itu punya dokumen.
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="text-uppercase text-secondary" style="font-size:.68rem">
                    <tr>
                        <th style="width:9rem">Kode</th>
                        <th>Nama</th>
                        <th style="width:8rem">Alias</th>
                        <th class="text-center" style="width:6rem">Dokumen</th>
                        <th class="text-center" style="width:6rem">Pengguna</th>
                        <th class="text-center" style="width:5rem">Aktif</th>
                        <th style="width:6rem"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($departemen as $d)
                        @php $terkunci = $d->documents_count > 0; @endphp
                        <tr>
                            <td>
                                <input type="text" name="code" form="dept-{{ $d->id }}" value="{{ $d->code }}"
                                       class="form-control form-control-sm font-monospace text-uppercase"
                                       maxlength="20" required @disabled($terkunci)>
                                @if ($terkunci)
                                    {{-- disabled, bukan readonly: input readonly TETAP terkirim,
                                         jadi ia hanya menyembunyikan kehendaknya dari layar tanpa
                                         menghalangi apa pun. Penjaga sesungguhnya tetap di
                                         SimpanDepartemenRequest. --}}
                                    <span class="text-muted" style="font-size:.68rem">
                                        <i class="bi bi-lock-fill"></i> terkunci oleh {{ $d->documents_count }} dokumen
                                    </span>
                                @endif
                            </td>
                            <td><input type="text" name="name" form="dept-{{ $d->id }}" value="{{ $d->name }}" class="form-control form-control-sm" maxlength="100" required></td>
                            <td><input type="text" name="alias" form="dept-{{ $d->id }}" value="{{ $d->alias }}" class="form-control form-control-sm text-uppercase" maxlength="50"></td>
                            <td class="text-center"><span class="badge-soft">{{ $d->documents_count }}</span></td>
                            <td class="text-center"><span class="badge-soft">{{ $d->users_count }}</span></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           form="dept-{{ $d->id }}" @checked($d->is_active)
                                           aria-label="Departemen {{ $d->code }} aktif">
                                </div>
                            </td>
                            <td class="text-end">
                                <button type="submit" form="dept-{{ $d->id }}" class="btn btn-sm btn-outline-primary"
                                        data-confirm="Departemen yang dinonaktifkan berhenti ditawarkan pada pendaftaran akun &amp; pembuatan dokumen. Dokumen dan penggunanya tidak berubah."
                                        data-confirm-title="Simpan {{ $d->code }}?" data-confirm-ok="Ya, simpan">
                                    <i class="bi bi-save"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-light">
                        <td><input type="text" name="code" form="dept-baru" value="{{ old('code') }}" class="form-control form-control-sm font-monospace text-uppercase" maxlength="20" placeholder="KODE"></td>
                        <td><input type="text" name="name" form="dept-baru" value="{{ old('name') }}" class="form-control form-control-sm" maxlength="100" placeholder="Nama departemen baru"></td>
                        <td><input type="text" name="alias" form="dept-baru" value="{{ old('alias') }}" class="form-control form-control-sm text-uppercase" maxlength="50" placeholder="Alias"></td>
                        <td class="text-center text-muted">&mdash;</td>
                        <td class="text-center text-muted">&mdash;</td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" form="dept-baru" checked aria-label="Departemen baru aktif">
                            </div>
                        </td>
                        <td class="text-end">
                            <button type="submit" form="dept-baru" class="btn btn-sm btn-pp"><i class="bi bi-plus-lg"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ========================== JENIS DOKUMEN ========================== --}}
    @foreach ($jenis as $j)
        <form id="jenis-{{ $j->id }}" method="POST" action="{{ route('pengaturan.master.jenis.simpan', $j) }}">
            @csrf @method('PUT')
        </form>
    @endforeach

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-files text-primary"></i>
            <div>
                <span class="fw-semibold">Jenis Dokumen</span>
                <div class="text-muted small">
                    Nama &amp; saklar aktif saja. Menambah jenis baru menuntut schema JSON dan
                    template cetak, jadi ia tidak bisa dibuat dari layar mana pun.
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="text-uppercase text-secondary" style="font-size:.68rem">
                    <tr>
                        <th style="width:7rem">Kode</th>
                        <th>Nama</th>
                        <th class="text-center" style="width:7rem">Berjalan</th>
                        <th class="text-center" style="width:7rem">Berlaku</th>
                        <th class="text-center" style="width:5rem">Aktif</th>
                        <th style="width:6rem"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jenis as $j)
                        @php [$ikon, $warna] = \App\Models\DocumentType::rupa($j->code); @endphp
                        <tr>
                            <td>
                                <span class="fw-semibold font-monospace" style="color:{{ $warna }}">
                                    <i class="bi {{ $ikon }}"></i> {{ $j->code }}
                                </span>
                            </td>
                            <td><input type="text" name="name" form="jenis-{{ $j->id }}" value="{{ $j->name }}" class="form-control form-control-sm" maxlength="100" required></td>
                            <td class="text-center"><span class="badge-soft">{{ $j->berjalan_count }}</span></td>
                            <td class="text-center"><span class="badge-soft badge-soft-success">{{ $j->berlaku_count }}</span></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           form="jenis-{{ $j->id }}" @checked($j->is_active)
                                           aria-label="Jenis {{ $j->code }} aktif">
                                </div>
                            </td>
                            <td class="text-end">
                                <button type="submit" form="jenis-{{ $j->id }}" class="btn btn-sm btn-outline-primary"
                                        data-confirm="Jenis yang dinonaktifkan hilang dari menu Dokumen Baru dan tidak bisa disusun lagi. Dokumen {{ $j->code }} yang sudah ada tetap terbaca dan tetap bisa dicetak."
                                        data-confirm-title="Simpan {{ $j->code }}?" data-confirm-ok="Ya, simpan">
                                    <i class="bi bi-save"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ======================= KATEGORI INFORMASI ======================= --}}
    @foreach ($kategori as $k)
        <form id="kat-{{ $k->id }}" method="POST" action="{{ route('pengaturan.master.kategori.simpan', $k) }}">
            @csrf @method('PUT')
        </form>
    @endforeach
    <form id="kat-baru" method="POST" action="{{ route('pengaturan.master.kategori.tambah') }}">@csrf</form>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-info-square text-primary"></i>
            <div>
                <span class="fw-semibold">Kategori Informasi</span>
                <div class="text-muted small">
                    Submenu pada menu Informasi. Kategori baru langsung tampil di sidebar dan di
                    aplikasi mobile — tanpa rilis kode.
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="text-uppercase text-secondary" style="font-size:.68rem">
                    <tr>
                        <th style="width:13rem">Nama</th>
                        <th style="width:10rem">Ikon</th>
                        <th>Deskripsi</th>
                        <th style="width:14rem">Kolom yang dipakai</th>
                        <th class="text-center" style="width:6rem">Dokumen</th>
                        <th class="text-center" style="width:5rem">Aktif</th>
                        <th style="width:6rem"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($kategori as $k)
                        <tr>
                            <td>
                                <input type="text" name="nama" form="kat-{{ $k->id }}" value="{{ $k->nama }}" class="form-control form-control-sm" maxlength="60" required>
                                {{-- Slug ditampilkan, tapi tak pernah bisa diubah: ia tersimpan
                                     sebagai teks di `informasi.kategori`. --}}
                                <span class="text-muted font-monospace" style="font-size:.68rem">{{ $k->slug }}</span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi {{ $k->ikon }}"></i></span>
                                    <input type="text" name="ikon" form="kat-{{ $k->id }}" value="{{ $k->ikon }}" class="form-control form-control-sm font-monospace" maxlength="50" required>
                                </div>
                            </td>
                            <td><input type="text" name="deskripsi" form="kat-{{ $k->id }}" value="{{ $k->deskripsi }}" class="form-control form-control-sm" maxlength="255" placeholder="opsional"></td>
                            <td>
                                @foreach (\App\Models\InformasiKategori::KOLOM_TERSEDIA as $kunci => $label)
                                    <div class="form-check form-check-inline small">
                                        <input class="form-check-input" type="checkbox" name="kolom[]" value="{{ $kunci }}"
                                               form="kat-{{ $k->id }}" id="kol-{{ $k->id }}-{{ $kunci }}"
                                               @checked(in_array($kunci, $k->kolom(), true))>
                                        <label class="form-check-label" for="kol-{{ $k->id }}-{{ $kunci }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-center"><span class="badge-soft">{{ $jumlahInformasi[$k->slug] ?? 0 }}</span></td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                           form="kat-{{ $k->id }}" @checked($k->is_active)
                                           aria-label="Kategori {{ $k->nama }} aktif">
                                </div>
                            </td>
                            <td class="text-end">
                                <button type="submit" form="kat-{{ $k->id }}" class="btn btn-sm btn-outline-primary"
                                        data-confirm="Kategori yang dinonaktifkan tetap tampil di menu Informasi dalam keadaan tak bisa diklik, dan tidak menerima unggahan baru. Dokumen yang sudah ada tidak dihapus."
                                        data-confirm-title="Simpan {{ $k->nama }}?" data-confirm-ok="Ya, simpan">
                                    <i class="bi bi-save"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-light">
                        <td>
                            <input type="text" name="nama" form="kat-baru" value="{{ old('nama') }}" class="form-control form-control-sm" maxlength="60" placeholder="Nama kategori baru">
                            <span class="text-muted" style="font-size:.68rem">kuncinya dibuat otomatis dari nama</span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-info-circle"></i></span>
                                <input type="text" name="ikon" form="kat-baru" value="{{ old('ikon', 'bi-info-circle') }}" class="form-control form-control-sm font-monospace" maxlength="50">
                            </div>
                        </td>
                        <td><input type="text" name="deskripsi" form="kat-baru" value="{{ old('deskripsi') }}" class="form-control form-control-sm" maxlength="255" placeholder="opsional"></td>
                        <td>
                            @foreach (\App\Models\InformasiKategori::KOLOM_TERSEDIA as $kunci => $label)
                                <div class="form-check form-check-inline small">
                                    <input class="form-check-input" type="checkbox" name="kolom[]" value="{{ $kunci }}" form="kat-baru" id="kol-baru-{{ $kunci }}">
                                    <label class="form-check-label" for="kol-baru-{{ $kunci }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </td>
                        <td class="text-center text-muted">&mdash;</td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" form="kat-baru" checked aria-label="Kategori baru aktif">
                            </div>
                        </td>
                        <td class="text-end">
                            <button type="submit" form="kat-baru" class="btn btn-sm btn-pp"><i class="bi bi-plus-lg"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white small text-muted">
            <i class="bi bi-lightbulb"></i>
            Kolom yang bisa dipilih hanya <strong>Edisi</strong>, <strong>No. Revisi</strong>, dan
            <strong>Tanggal Efektif</strong> &mdash; ketiganya sudah ada di tabel informasi. Nomor &amp;
            judul selalu dipakai. Nama ikon diambil dari
            <span class="font-monospace">bootstrap-icons</span>, mis.
            <span class="font-monospace">bi-journal-bookmark</span>.
        </div>
    </div>
@endsection
