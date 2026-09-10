@extends('layouts.app')
@section('title', 'Manajemen User')

@php
    $roleLabels = [
        'admin_it' => ['Admin IT', 'danger'],
        'pimpinan' => ['Pimpinan', 'primary'],
        'section_head' => ['Section Head', 'info'],
        'group_leader' => ['Group Leader', 'success'],
        'departemen_head' => ['Departemen Head', 'info'],
        'management_development' => ['Management Development', 'warning'],
        // Kunci perannya tetap `staff`; di layar jabatan ini bernama "Non-Staff".
        'staff' => ['Non-Staff', 'secondary'],
    ];
    $statusLabels = ['active' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
@endphp

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h4 fw-bold text-dark mb-0">Manajemen User</h1>
            <p class="text-muted small mb-0">Kelola semua akun dan buat akun staf.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-pp"><i class="bi bi-person-plus"></i> Buat Akun</a>
    </div>

    {{-- ===== Akun Management Development =====
         Ditaruh TERPISAH dan di ATAS karena sifatnya berbeda dari akun lain:
         BERSAMA (dipakai siapa pun di departemennya), tak pernah muncul di
         dropdown pemilihan mana pun, dan memegang satu tahap wajib pada alur
         SOP. Menyelipkannya ke tabel biasa menyembunyikan justru hal-hal yang
         perlu diketahui pengelolanya. --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex align-items-center gap-2">
            <i class="bi bi-spellcheck text-primary"></i>
            <div>
                <span class="fw-semibold">Akun Management Development</span>
                <div class="text-muted small">
                    Peninjau kedua &mdash; memeriksa sistematika penulisan sesudah SH/DH dan sebelum PJO.
                    Akun bersama; hanya Admin yang boleh membuat dan mengelolanya.
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>NRP</th><th>Nama</th><th>Dept</th><th>Status</th><th>Bantuan AI</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($akunMd as $md)
                            <tr>
                                <td class="font-monospace small">{{ $md->nrp }}</td>
                                <td class="fw-semibold">{{ $md->name }}</td>
                                <td><span class="badge-soft">{{ $md->department->code ?? '—' }}</span></td>
                                <td>
                                    <span class="badge-soft badge-soft-{{ $md->isActive() ? 'success' : 'secondary' }} text-capitalize">
                                        {{ $md->status }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-soft badge-soft-{{ $md->ai_review_enabled ? 'info' : 'light' }}">
                                        <i class="bi bi-robot"></i>
                                        {{ $md->ai_review_enabled ? 'Aktif' : 'Mati' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#md-cfg-{{ $md->id }}">
                                        <i class="bi bi-gear"></i> Konfigurasi
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-exclamation-triangle text-warning fs-4 d-block mb-2"></i>
                                    Belum ada akun Management Development.<br>
                                    <span class="small">Tanpa akun ini, dokumen SOP akan tertahan dan tak bisa sampai ke PJO.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($akunMd as $md)
        <div class="modal fade" id="md-cfg-{{ $md->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-gear me-1"></i>Konfigurasi &mdash; {{ $md->nrp }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body d-grid gap-3">

                        {{-- Saklar AI --}}
                        <form method="POST" action="{{ route('users.mdConfig', $md) }}">
                            @csrf
                            <input type="hidden" name="aksi" value="ai">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small"><i class="bi bi-robot"></i> Bantuan AI</div>
                                    <div class="text-muted small">
                                        Membantu menemukan typo &amp; salah tulis. Keputusan tetap di tangan peninjau.
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-{{ $md->ai_review_enabled ? 'secondary' : 'info' }}">
                                    {{ $md->ai_review_enabled ? 'Matikan' : 'Aktifkan' }}
                                </button>
                            </div>
                        </form>
                        <hr class="my-0">

                        {{-- Aktif / nonaktif akun --}}
                        <form method="POST" action="{{ route('users.mdConfig', $md) }}"
                              onsubmit="return confirm('{{ $md->isActive()
                                    ? 'Nonaktifkan akun MD? Dokumen SOP akan TERTAHAN di tahap MD sampai diaktifkan lagi.'
                                    : 'Aktifkan kembali akun MD?' }}')">
                            @csrf
                            <input type="hidden" name="aksi" value="status">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small"><i class="bi bi-power"></i> Status akun</div>
                                    <div class="text-muted small">
                                        Menonaktifkan akun menghentikan seluruh SOP di tahap ini.
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-{{ $md->isActive() ? 'danger' : 'success' }}">
                                    {{ $md->isActive() ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </div>
                        </form>
                        <hr class="my-0">

                        {{-- Reset sandi — penting justru karena akunnya BERSAMA:
                             begitu ada orang pindah/keluar, sandinya harus cepat diganti. --}}
                        <form method="POST" action="{{ route('users.mdConfig', $md) }}">
                            @csrf
                            <input type="hidden" name="aksi" value="reset_sandi">
                            <div class="fw-semibold small mb-1"><i class="bi bi-key"></i> Ganti kata sandi</div>
                            <div class="text-muted small mb-2">
                                Akun ini dipakai bersama &mdash; ganti sandinya setiap ada orang keluar atau pindah tugas.
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="password" name="sandi_baru" class="form-control"
                                       placeholder="Kata sandi baru (min. 8 karakter)" minlength="8" required>
                                <button class="btn btn-outline-primary">Simpan</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Cari (nama / NRP / email)</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Departemen</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(($filters['department_id'] ?? '') == $dept->id)>{{ $dept->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-secondary w-100"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th><th>NRP</th><th>No. HP</th><th>Departemen</th><th>Peran</th><th>Status</th><th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $u)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $u->name }}</div>
                                    <div class="small text-muted"><i class="bi bi-person-vcard"></i> {{ $u->nrp }}</div>
                                </td>
                                <td>{{ $u->nrp ?? '—' }}</td>
                                <td class="small">{{ $u->nomor_hp ?? '—' }}</td>
                                <td><span class="badge-soft">{{ $u->department->code ?? '—' }}</span></td>
                                <td>
                                    @php $r = $u->getRoleNames()->first(); [$lbl,$clr] = $roleLabels[$r] ?? [$r,'secondary']; @endphp
                                    <span class="badge-soft badge-soft-{{ $clr }}">{{ $lbl }}</span>
                                </td>
                                <td><span class="badge-soft badge-soft-{{ $statusLabels[$u->status] ?? 'secondary' }} text-capitalize">{{ $u->status }}</span></td>
                                <td class="text-end">
                                    @if ($u->id !== auth()->id())
                                        <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-person-gear"></i> Ubah Peran</a>
                                        <form method="POST" action="{{ route('users.toggleStatus', $u) }}" class="d-inline"
                                              data-confirm="Ubah status akun {{ $u->name }}?" data-confirm-title="Ubah Status Akun?" data-confirm-ok="Ya, ubah">
                                            @csrf
                                            @if ($u->status === 'active')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i> Nonaktifkan</button>
                                            @else
                                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Aktifkan</button>
                                            @endif
                                        </form>
                                        {{-- Hapus DITOLAK bila user masih tertaut dokumen (lihat
                                             UserManagementController::jejakDokumen) — pesannya
                                             mengarahkan ke Nonaktifkan, bukan sekadar gagal. --}}
                                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="d-inline"
                                              data-confirm="Hapus akun {{ $u->name }} ({{ $u->nrp }})? Akun yang masih tertaut dokumen akan ditolak — pakai Nonaktifkan untuk itu."
                                              data-confirm-title="Hapus Akun?" data-confirm-ok="Ya, hapus" data-confirm-icon="warning">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-trash3"></i> Hapus</button>
                                        </form>
                                    @else
                                        <span class="badge-soft badge-soft-light">Akun Anda</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada user ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Zona Berbahaya (Bersihkan Seluruh Dokumen) PINDAH ke Konfigurasi Sistem
         pada v9 Fase 2b: ia bukan urusan akun, dan layar itu sudah jadi rumah
         bagi aksi sistemik lain. Izinnya tak berubah — keduanya `can:user.manage`. --}}
@endsection
