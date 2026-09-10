@extends('layouts.app')
@section('title', 'Manajemen Akses')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Manajemen Akses</h1>
            <p class="text-muted small mb-0">
                Tentukan jenis dokumen yang boleh disusun tiap Group Leader.
            </p>
        </div>
        <button type="button" class="btn btn-pp" data-bs-toggle="modal" data-bs-target="#profil-baru">
            <i class="bi bi-plus-lg"></i> Profil Baru
        </button>
    </div>

    {{-- ===== Kartu 1 — Profil Akses ===== --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-diagram-3 text-primary"></i>
            <div>
                <span class="fw-semibold">Profil Akses</span>
                <div class="text-muted small">
                    Buat profil sekali, tetapkan ke banyak orang. Mengubah profil langsung mengubah
                    wewenang semua penggunanya.
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Profil</th>
                            <th>Jenis Dokumen</th>
                            <th>Tinjau JSA</th>
                            <th class="text-center">Pengguna</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profiles as $p)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $p->nama }}</div>
                                    @if ($p->keterangan)
                                        <div class="text-muted small">{{ $p->keterangan }}</div>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($p->jenis_dibolehkan ?? [] as $kode)
                                        @php [$ikon, $warna] = App\Models\DocumentType::rupa($kode); @endphp
                                        <span class="badge-soft me-1">
                                            <i class="bi {{ $ikon }}" style="color: {{ $warna }}"></i> {{ $kode }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">&mdash; tidak menyusun &mdash;</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge-soft badge-soft-{{ $p->boleh_review_jsa ? 'info' : 'light' }}">
                                        <i class="bi {{ $p->boleh_review_jsa ? 'bi-check2' : 'bi-dash' }}"></i>
                                        {{ $p->boleh_review_jsa ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge-soft badge-soft-{{ $p->users_count > 0 ? 'success' : 'light' }}">
                                        {{ $p->users_count }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#profil-ubah-{{ $p->id }}">
                                        <i class="bi bi-pencil"></i> Ubah
                                    </button>
                                    <form action="{{ route('akses.destroy', $p) }}" method="POST" class="d-inline"
                                          data-confirm="{{ $p->users_count > 0
                                              ? $p->users_count.' pengguna akan kehilangan aksesnya dan kembali ke Tanpa Akses.'
                                              : 'Profil ini belum dipakai siapa pun.' }}"
                                          data-confirm-title="Hapus profil &quot;{{ $p->nama }}&quot;?"
                                          data-confirm-ok="Ya, hapus"
                                          data-confirm-icon="{{ $p->users_count > 0 ? 'warning' : 'question' }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-diagram-3 fs-4 d-block mb-2 opacity-50"></i>
                                    Belum ada profil akses.<br>
                                    <span class="small">
                                        Selama belum ada, tak seorang Group Leader pun bisa menyusun dokumen.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Kartu 2 — Penetapan Akses ===== --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-person-gear text-primary"></i>
            <div>
                <span class="fw-semibold">Penetapan Akses</span>
                <div class="text-muted small">
                    Group Leader saja. Admin IT berwenang penuh tanpa profil; SH/DH/PJO/Non-Staff
                    tidak menyusun dokumen sehingga tidak ditetapkan.
                </div>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Cari (nama / NRP)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(($filters['department_id'] ?? '') == $dept->id)>{{ $dept->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Profil Akses</label>
                    <select name="access_profile_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="tanpa" @selected(($filters['access_profile_id'] ?? '') === 'tanpa')>&mdash; Tanpa Akses &mdash;</option>
                        @foreach ($profiles as $p)
                            <option value="{{ $p->id }}" @selected(($filters['access_profile_id'] ?? '') == $p->id)>{{ $p->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-secondary flex-fill"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('akses.index') }}" class="btn btn-sm btn-outline-secondary" title="Bersihkan filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>NRP</th>
                            <th>Dept</th>
                            <th style="min-width: 260px;">Profil Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groupLeaders as $gl)
                            <tr>
                                <td class="fw-semibold">{{ $gl->name }}</td>
                                <td class="font-monospace small">{{ $gl->nrp }}</td>
                                <td><span class="badge-soft">{{ $gl->department->code ?? '—' }}</span></td>
                                <td>
                                    <form action="{{ route('akses.tetapkan', $gl) }}" method="POST"
                                          class="d-flex gap-2">
                                        @csrf
                                        <select name="access_profile_id" class="form-select form-select-sm">
                                            <option value="">&mdash; Tanpa Akses &mdash;</option>
                                            @foreach ($profiles as $p)
                                                <option value="{{ $p->id }}" @selected($gl->access_profile_id === $p->id)>
                                                    {{ $p->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary flex-shrink-0">
                                            <i class="bi bi-check2"></i> Simpan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    @if (array_filter($filters))
                                        Tidak ada Group Leader yang cocok dengan filter.
                                    @else
                                        Belum ada akun Group Leader.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($groupLeaders->hasPages())
            <div class="card-footer bg-white">{{ $groupLeaders->links() }}</div>
        @endif
    </div>

    {{-- ===== Modal: Profil Baru ===== --}}
    <div class="modal fade" id="profil-baru" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" action="{{ route('akses.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-diagram-3"></i> Profil Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">@include('akses._form-profil', ['profile' => null, 'jenis' => $jenis])</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-pp"><i class="bi bi-check2"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modal: Ubah Profil (satu per baris) ===== --}}
    @foreach ($profiles as $p)
        <div class="modal fade" id="profil-ubah-{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content" action="{{ route('akses.update', $p) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Ubah Profil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        @if ($p->users_count > 0)
                            <div class="alert alert-warning small py-2">
                                <i class="bi bi-exclamation-triangle"></i>
                                Perubahan ini langsung berlaku bagi <strong>{{ $p->users_count }} pengguna</strong>.
                            </div>
                        @endif
                        @include('akses._form-profil', ['profile' => $p, 'jenis' => $jenis])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-pp"><i class="bi bi-check2"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
