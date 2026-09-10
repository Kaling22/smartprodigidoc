@extends('layouts.app')
@section('title', 'Persetujuan Nonaktif')

@section('content')
    <div class="mb-3">
        <h1 class="h4 fw-bold text-dark mb-0">Persetujuan Nonaktif</h1>
        <p class="text-muted small mb-0">
            <i class="bi bi-slash-circle"></i>
            Pengajuan mematikan dokumen Berlaku yang menunggu keputusan Anda.
        </p>
    </div>

    {{-- Alurnya ditulis di layar, bukan cuma di kode: pemutus tahap pertama
         perlu tahu bahwa keputusannya BUKAN yang terakhir, dan pemutus tahap
         terakhir perlu tahu bahwa nomornya akan benar-benar dilepas. --}}
    <div class="alert alert-light border d-flex gap-2 align-items-start" role="note">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div class="small mb-0">
            {{-- Pengecualian "tahap MD dilewati bila MD sendiri yang mengajukan"
                 dibuang (PLAN C §C5): tahap yang sedang berjalan sudah tertulis di
                 tiap baris, jadi kalimat itu menerangkan sesuatu yang toh terlihat. --}}
            Pengajuan berjalan <strong>SH/DH departemen &rarr; Management Development &rarr; PJO</strong>.
            Dokumen tetap <strong>Berlaku</strong> selama pengajuan berjalan; nomornya baru dilepas setelah
            tahap terakhir menyetujui, dan penolakan di tahap mana pun mengembalikannya ke Berlaku.
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @include('partials._urut-th', ['kolom' => 'nomor', 'label' => 'No. Dokumen'])<th>Judul</th>
                            <th>Jenis</th><th>Dept</th><th>Pengaju</th><th>Tahap</th><th>Alasan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            @php
                                $tahapLabel = ['sh' => 'SH/DH Departemen', 'md' => 'Management Development', 'pjo' => 'PJO'];
                                $alasanAjuan = $doc->approvals->first()?->comment;
                                $terakhir = $doc->tahapNonaktifBerikut() === null;
                            @endphp
                            <tr>
                                <td class="font-monospace small">
                                    {{ $doc->displayNumber() }}
                                    @include('partials._badge-nomor-lama', ['doc' => $doc])
                                </td>
                                <td class="fw-semibold">{{ $doc->title }}</td>
                                <td><span class="badge-soft">{{ $doc->type->code }}</span></td>
                                <td><span class="badge-soft">{{ $doc->department->code ?? '—' }}</span></td>
                                <td class="small">{{ $doc->nonaktifOleh->name ?? '—' }}</td>
                                <td>
                                    <span class="badge-soft badge-soft-warning">
                                        {{ $tahapLabel[$doc->nonaktif_tahap] ?? $doc->nonaktif_tahap }}
                                    </span>
                                    @if ($terakhir)
                                        <span class="badge-soft badge-soft-danger d-block mt-1"
                                              title="Menyetujui di tahap ini langsung mematikan dokumen">tahap terakhir</span>
                                    @endif
                                </td>
                                <td class="small text-muted" style="max-width:22rem">{{ $alasanAjuan ?? '—' }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('documents.pdf', $doc) }}" target="_blank"
                                       class="btn btn-sm btn-outline-danger" title="Lihat PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                                    <form method="POST" action="{{ route('nonaktif.putuskan', $doc) }}" class="d-inline"
                                          data-confirm="{{ $terakhir
                                              ? 'Ini tahap TERAKHIR: ' . $doc->displayNumber() . ' langsung menjadi Tidak Berlaku dan nomornya dilepas.'
                                              : 'Pengajuan diteruskan ke tahap berikutnya. Dokumen tetap Berlaku sampai keputusan terakhir.' }}"
                                          data-confirm-title="Setujui Nonaktif?" data-confirm-ok="Ya, setujui" data-confirm-icon="warning">
                                        @csrf
                                        <input type="hidden" name="keputusan" value="setuju">
                                        <button class="btn btn-sm btn-warning"><i class="bi bi-check2"></i> Setujui</button>
                                    </form>
                                    {{-- Tolak pindah ke strip, bukan sekadar demi jumlah tombol:
                                         "Setujui" dan "Tolak" berdampingan di baris sempit adalah
                                         dua keputusan berlawanan yang terpisah beberapa piksel,
                                         dan yang salah klik di sini melepas nomor dokumen. --}}
                                    <x-aksi>
                                        <a href="{{ route('documents.show', $doc) }}"
                                           class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat detail</a>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#modalTolakNonaktif{{ $doc->id }}">
                                            <i class="bi bi-x-circle"></i> Tolak
                                        </button>
                                    </x-aksi>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Tidak ada pengajuan nonaktif yang menunggu keputusan Anda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($documents->hasPages())<div class="card-footer bg-white">{{ $documents->links() }}</div>@endif
    </div>

    {{-- Modal penolakan DI LUAR tabel (di dalam <td> ia mewarisi konteks
         penumpukan tabel dan bisa tampil terpotong) — alasan WAJIB, sebab
         itulah satu-satunya yang dibaca pengaju. --}}
    @foreach ($documents as $doc)
        <div class="modal fade" id="modalTolakNonaktif{{ $doc->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('nonaktif.putuskan', $doc) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="keputusan" value="tolak">
                    <div class="modal-header">
                        <h5 class="modal-title h6 fw-bold mb-0">
                            <i class="bi bi-x-circle"></i> Tolak Pengajuan —
                            <span class="font-monospace">{{ $doc->displayNumber() }}</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Dokumen langsung kembali <strong>Berlaku</strong> dan pengaju dikabari alasannya.</p>
                        <label for="tolakNonaktif{{ $doc->id }}" class="form-label small fw-semibold">
                            Alasan penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea id="tolakNonaktif{{ $doc->id }}" name="alasan" rows="3" maxlength="2000" required
                                  class="form-control" placeholder="mis. Pekerjaannya masih berjalan di shift malam."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Tolak Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
