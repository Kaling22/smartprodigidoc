{{--
    Form Ajukan Revisi (FITUR-BARU-v4 §4 & §5).

    Dulu tombol "Revisi" langsung memunculkan konfirmasi; sekarang
    alasannya diisi DULU, konfirmasi SweetAlert menyusul saat form dikirim
    (ditangani penyadap `data-confirm` di layouts/app).

    Masukan lapangan yang dicentang ikut jadi alasan revisi dan statusnya
    berubah `diadopsi` — masukan jadi pemicu tindakan, bukan arsip mati.

    Butuh: $document, $masukanRevisi (koleksi masukan yang belum ditindak).
    Opsional: $masukanTercentang (array id yang langsung dicentang — dipakai
    tombol "Revisi" di halaman Log Dokumen → Masukan Lapangan).
--}}
@php
    $tercentang = $masukanTercentang ?? [];
    // Angka yang DIJANJIKAN, bukan `no_revisi + 1`: revisi berguling di angka 4
    // menjadi Edisi+1 Rev 0, sehingga pada dokumen Revisi 4 kalimat lama
    // menjanjikan "Revisi ke-5" yang tak pernah ada.
    //
    // Aturan yang sama persis dipakai draft revisinya saat DIKIRIM
    // (Document::revisiSaatKirim), jadi janji di layar ini mustahil berbeda
    // dari angka yang akhirnya tersimpan.
    [$edisiJanji, $revisiJanji] = \App\Services\DocumentService::nextEditionRevision(
        max(1, (int) ($document->edisi ?: 1)), (int) $document->no_revisi,
    );
@endphp
<div class="modal fade" id="modalRevisi{{ $document->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" action="{{ route('documents.requestRevision', $document) }}" class="modal-content"
              data-confirm="Buat Edisi {{ $edisiJanji }} Revisi {{ $revisiJanji }} untuk {{ $document->displayNumber() }}? Versi lama tetap Berlaku sementara sampai versi baru disetujui."
              data-confirm-title="Mulai Revisi?" data-confirm-ok="Ya, revisi">
            @csrf
            {{-- Dipakai membuka kembali modal ini bila validasi gagal. --}}
            <input type="hidden" name="revisi_doc" value="{{ $document->id }}">

            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold mb-0"><i class="bi bi-arrow-repeat"></i> Revisi — <span class="font-monospace">{{ $document->displayNumber() }}</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @php
                    // Jadwal PEMBUAT — KETERANGAN saja (FITUR-BARU-v4 §6). Pada GL,
                    // ketersediaan tak pernah menghalangi: SH tetap boleh mengajukan
                    // revisi, ia hanya perlu tahu draftnya akan menunggu.
                    $pembuatOff = ($ketersediaanPembuat[$document->created_by] ?? null);
                @endphp
                @if ($pembuatOff && $pembuatOff['off'])
                    <div class="alert alert-light border small py-2">
                        <i class="bi bi-calendar-x"></i>
                        {{ $document->creator?->name }} (pembuat) sedang {{ strtolower($pembuatOff['jenis']) }} sampai
                        <strong>{{ $pembuatOff['kembali']->translatedFormat('l, j F') }}</strong>.
                        Draft revisi tetap dibuat atas namanya dan menunggu sampai ia kembali.
                    </div>
                @endif

                <label for="alasan{{ $document->id }}" class="form-label small fw-semibold">Apa yang perlu direvisi? <span class="text-danger">*</span></label>
                <textarea id="alasan{{ $document->id }}" name="alasan" rows="4" maxlength="2000"
                          class="form-control @error('alasan') is-invalid @enderror"
                          placeholder="mis. Langkah 5 tidak lagi sesuai kondisi pit terbaru.">{{ old('alasan') }}</textarea>
                @error('alasan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                {{-- "WAJIB diisi" dibuang (PLAN C §C5): tanda * merah pada labelnya
                     sudah menyatakan itu. --}}
                <div class="form-text">Dibaca penyusun revisi, SH/DH, dan MD.</div>

                @if ($masukanRevisi->isNotEmpty())
                    <hr class="my-3">
                    <div class="fw-semibold small mb-1"><i class="bi bi-chat-left-dots"></i> Masukan Lapangan ({{ $masukanRevisi->count() }})</div>
                    <div class="form-text mb-2">Yang dicentang otomatis ikut tercatat sebagai alasan revisi, dan statusnya menjadi <em>Diadopsi</em>.</div>

                    @foreach ($masukanRevisi as $m)
                        <div class="form-check border rounded p-2 ps-5 mb-2">
                            <input class="form-check-input" type="checkbox" name="masukan[]" value="{{ $m->id }}"
                                   id="masukan{{ $m->id }}" @checked(in_array($m->id, old('masukan', $tercentang)))>
                            <label class="form-check-label small w-100" for="masukan{{ $m->id }}">
                                <span class="font-monospace text-muted">{{ $m->feedback_number }}</span>
                                @if ($m->status === 'baru')<span class="badge-soft badge-soft-danger">Baru</span>@endif
                                <div>{{ $m->isi }}</div>
                                <div class="text-muted" style="font-size:.75rem">{{ $m->user?->nameWithJabatan() ?? '—' }} · {{ $m->created_at?->format('d/m/Y H:i') }} WITA</div>
                            </label>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-repeat"></i> Revisi</button>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Validasi gagal → halaman dimuat ulang dan modalnya tertutup, sehingga
            // pesan kesalahan tak pernah terbaca. Modal yang bersangkutan dibuka lagi
            // (isian lama dipulihkan `old()` di markup di atas).
            document.addEventListener('DOMContentLoaded', function () {
                const id = @json(old('revisi_doc'));
                const el = id ? document.getElementById('modalRevisi' + id) : null;
                if (el) new bootstrap.Modal(el).show();
            });
        </script>
    @endpush
@endonce
