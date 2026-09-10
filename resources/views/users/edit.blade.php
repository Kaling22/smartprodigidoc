@extends('layouts.app')
@section('title', 'Ubah Peran')

@php
    // Label peran DISALIN dari users/create — kalau suatu saat daftarnya
    // bertambah, keduanya harus ikut. Sengaja tidak dipusatkan dulu: dua tempat
    // masih terlacak mata, dan memindahkannya ke config menuntut satu lapisan
    // baru demi enam baris teks.
    $roleLabels = [
        'admin_it' => 'Admin IT — wewenang penuh',
        'pimpinan' => 'Pimpinan (PJO) — approver final',
        'section_head' => 'Section Head — peninjau & approver',
        'departemen_head' => 'Departemen Head — peninjau & approver',
        'group_leader' => 'Group Leader — satu-satunya pembuat',
        'staff' => 'Non-Staff — read-only (teknisi, helper, magang)',
        'management_development' => 'Management Development — peninjau tahap kedua',
    ];
@endphp

@section('content')
    <div class="mb-3">
        <a href="{{ route('users.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Kembali ke daftar user</a>
        <h1 class="h4 fw-bold text-dark mb-0 mt-1">Ubah Peran</h1>
        <p class="text-muted small mb-0">{{ $user->name }} · NRP {{ $user->nrp }}</p>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="alert alert-light border py-2 small mb-3">
                Peran sekarang: <span class="fw-semibold">{{ $roleLabels[$peranSekarang] ?? $peranSekarang ?? '—' }}</span>.
                Mengubah peran ikut menyesuaikan jabatan pada alur dokumen.
            </div>

            <form method="POST" action="{{ route('users.update', $user) }}"
                  data-confirm="Ubah peran {{ $user->name }}? Hak akses dan posisinya pada alur dokumen ikut berubah."
                  data-confirm-title="Ubah Peran?" data-confirm-ok="Ya, ubah" data-confirm-icon="warning">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Jabatan / Peran</label>
                        <select name="role" class="form-select" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', $peranSekarang) === $role)>{{ $roleLabels[$role] ?? $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Departemen</label>
                        <select name="department_id" class="form-select">
                            <option value="">— Tanpa departemen (Pimpinan / Admin) —</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected(old('department_id', $user->department_id) == $dept->id)>{{ $dept->code }} — {{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Pimpinan (PJO) selalu disimpan tanpa departemen — ia melintasi ketujuhnya.</div>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="btn btn-pp"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection
