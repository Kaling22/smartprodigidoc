@extends('layouts.app')
@section('title', 'Dokumen Berlaku')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Dokumen Berlaku</h1>
            <p class="text-muted small mb-0">
                Dokumen aktif {{ $departments->isNotEmpty() ? 'di 7 departemen' : 'di departemen Anda' }}.
                {{-- Dua cabang lama dibuang (PLAN C §C5): keduanya menerangkan tombol
                     yang terlihat di baris tabel yang sama ("Tombol Revisi …", "Kirim
                     masukan lewat tombol Beri Masukan"). Yang tersisa satu-satunya yang
                     menunjuk ke tempat yang TIDAK terlihat dari halaman ini. --}}
                @cannot('document.request_revision')
                    @cannot('beri-masukan')
                        Masukan lapangan &amp; alasan revisi terkumpul di menu <strong>Log Dokumen</strong>.
                    @endcannot
                @endcannot
            </p>
        </div>

        {{-- Daftar induk dokumen → Excel. Izinnya SAMA PERSIS dengan penjaga
             rutenya (`document.publish` = PJO + Admin IT, lintas 7 dept;
             `document.review` = SH/DH dan `document.create` = GL, dept sendiri
             saja), supaya tak ada tombol yang tampil lalu berakhir 403. Alasan
             pemilihan izinnya di routes/web.php. --}}
        @canany(['document.publish', 'document.review', 'document.create'])
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-success dropdown-toggle d-flex align-items-center gap-1"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><span class="dropdown-header small text-uppercase fw-bold">Daftar Induk Dokumen</span></li>
                @foreach (\App\Models\DocumentType::RUPA as $jenis => [$ikon, $warna])
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="{{ route('documents.export', $jenis) }}">
                        <i class="bi {{ $ikon }}"></i> {{ $jenis }}
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endcanany
    </div>

    {{-- Filter & cari (2g) --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. {{ \App\Models\Pengaturan::prefix() }}-SOP atau judul...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Jenis</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach (\App\Models\DocumentType::kode() as $code)<option value="{{ $code }}" @selected(strtoupper($filters['type'] ?? '') === $code)>{{ $code }}</option>@endforeach
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
                <div class="col-md-2">
                    <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                        <a href="{{ route('documents.published') }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
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
                        <tr>@include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th><th>Jenis</th><th>Dept</th>@include('partials._urut-th', ['kolom' => 'revisi', 'label' => 'No. Revisi'])<th>Status</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td class="font-monospace small">
                                    {{ $doc->displayNumber() }}
                                    @include('partials._badge-nomor-lama', ['doc' => $doc])
                                </td>
                                <td class="fw-semibold">
                                    {{ $doc->title }}
                                    {{-- Lencana masukan lapangan (FITUR-BARU-v4 §3): masukan yang
                                         BELUM ditindak. Semua kecuali Non-Staff — SH/DH & PJO tak
                                         lagi menindak, tapi tetap perlu tahu (Fase C). --}}
                                    @if (auth()->user()->dashboardPenuh())
                                        @if ($doc->masukan_count)
                                            <a href="{{ route('documents.show', $doc) }}" class="badge-soft badge-soft-danger text-decoration-none">
                                                <i class="bi bi-chat-left-dots"></i> {{ $doc->masukan_count }} masukan
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                <td><span class="badge-soft">{{ $doc->type->code }}</span></td>
                                <td><span class="badge-soft">{{ $doc->department->code }}</span></td>
                                <td class="text-center">{{ $doc->no_revisi }}</td>
                                <td>
                                    {{-- Tiga status mungkin di daftar ini sejak Fase F
                                         (Berlaku / Sedang Direvisi / Menunggu Nonaktif),
                                         jadi rupanya diambil dari SATU sumber yang sama
                                         dengan seluruh aplikasi alih-alih if-else di sini. --}}
                                    @include('partials._badge-status', ['status' => $doc->status])
                                </td>
                                <td class="text-end">
                                    {{-- PDF tetap tombol berdiri sendiri (PLAN C §C3): aksi yang
                                         paling sering dipakai, menguburnya berbiaya satu klik di
                                         setiap baris. Sisanya masuk strip <x-aksi>. --}}
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                    <x-aksi>
                                        {{-- Dokumen LAMA (butir 0): perbaikan metadata & berkas. Admin
                                             melihatnya DI SINI, bukan di "Dokumen Saya" — dokumen
                                             Berlaku memang tak masuk daftar itu baginya. --}}
                                        @if ($doc->bisaDisuntingArsipOleh(auth()->user()))
                                            <a href="{{ route('documents.arsip.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                                        @endif
                                        {{-- Non-Staff: kanal masukan (§3). Tak menyentuh isi dokumen. --}}
                                        @if ($doc->bisaDiberiMasukanOleh(auth()->user()))
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMasukan{{ $doc->id }}">
                                                <i class="bi bi-chat-left-dots"></i> Beri Masukan
                                            </button>
                                        @endif
                                        {{-- Fase C: bukan lagi `@can` (izin saja) melainkan predikat
                                             per-DOKUMEN — GL hanya boleh merevisi buatannya sendiri. --}}
                                        @if ($doc->bisaDirevisiOleh(auth()->user()))
                                            {{-- §4: alasan diisi DULU di modal, konfirmasi menyusul saat dikirim. --}}
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalRevisi{{ $doc->id }}">
                                                <i class="bi bi-arrow-repeat"></i> Revisi
                                            </button>
                                            {{-- Fase F: mematikan dokumen bukan lagi sekali klik.
                                                 Tombolnya membuka form alasan; pengajuannya lalu
                                                 melewati SH/DH, MD, dan PJO. --}}
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalNonaktif{{ $doc->id }}">
                                                <i class="bi bi-slash-circle"></i> Ajukan Nonaktif
                                            </button>
                                        @endif
                                        {{-- Membatalkan revisi = wewenang yang SAMA dengan memulainya,
                                             tapi statusnya `sedang_direvisi` sehingga bisaDirevisiOleh()
                                             (yang mensyaratkan Berlaku) tak bisa dipakai apa adanya. --}}
                                        @if ($doc->status === 'sedang_direvisi' && auth()->user()->can('document.request_revision')
                                            && (auth()->user()->can('document.view_all') || $doc->created_by === auth()->id()))
                                            <form method="POST" action="{{ route('documents.cancelRevisionB', $doc) }}" class="d-inline"
                                                  data-confirm="Batalkan revisi? Versi baru dibuang dan versi lama ({{ $doc->displayNumber() }}) kembali Berlaku."
                                                  data-confirm-title="Batalkan Revisi?" data-confirm-ok="Ya, batalkan">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Batalkan Revisi</button>
                                            </form>
                                        @endif
                                        {{-- Musnahkan = Admin saja (§8). Berbeda dari "Tidak Berlaku"
                                             yang hanya memindahkan; ini menghapus tanpa rollback. --}}
                                        @can('user.manage')
                                            @if ($doc->status === 'published')
                                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalMusnahkan{{ $doc->id }}">
                                                    <i class="bi bi-trash3"></i> Musnahkan
                                                </button>
                                            @endif
                                        @endcan
                                    </x-aksi>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-folder2-open fs-1 d-block mb-2"></i>Belum ada dokumen Berlaku.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>

    {{-- Modal sengaja dirender DI LUAR tabel. Di dalam <td> ia mewarisi konteks
         penumpukan tabel dan bisa tampil terpotong/di belakang latar gelapnya. --}}
    @foreach ($documents as $doc)
        @if ($doc->bisaDiberiMasukanOleh(auth()->user()))
            @include('documents._modal-masukan', ['document' => $doc, 'masukanSaya' => $doc->feedback])
        @endif
        @if ($doc->bisaDirevisiOleh(auth()->user()))
            @include('documents._modal-revisi', ['document' => $doc, 'masukanRevisi' => $doc->feedback])
            @include('documents._modal-nonaktif', ['document' => $doc])
        @endif
        @can('user.manage')
            @if ($doc->status === 'published')
                @include('documents._modal-musnahkan', ['document' => $doc])
            @endif
        @endcan
    @endforeach
@endsection
