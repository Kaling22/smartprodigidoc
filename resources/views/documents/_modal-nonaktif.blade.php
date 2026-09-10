{{--
    Ajukan Nonaktif (PLAN-REVISI-v6 Fase F).

    Dulu tombolnya langsung mematikan dokumen. Sekarang ia membuka FORM lebih
    dulu — persis pola `documents/_modal-revisi` (§4): alasan diisi di sini,
    konfirmasi SweetAlert menyusul saat dikirim. Alasannya bukan formalitas:
    ia satu-satunya keterangan yang dibaca SH/DH, MD, dan PJO berikutnya.

    Butuh: $document
--}}
<div class="modal fade" id="modalNonaktif{{ $document->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('nonaktif.ajukan', $document) }}" class="modal-content"
              data-confirm="Pengajuan dikirim ke SH/DH departemen, lalu Management Development, lalu PJO. Dokumen tetap Berlaku sampai keputusan terakhir."
              data-confirm-title="Ajukan Nonaktif?" data-confirm-ok="Ya, ajukan" data-confirm-icon="warning">
            @csrf
            {{-- Dipakai membuka kembali modal ini bila validasi gagal. --}}
            <input type="hidden" name="nonaktif_doc" value="{{ $document->id }}">

            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold mb-0">
                    <i class="bi bi-slash-circle"></i> Ajukan Nonaktif —
                    <span class="font-monospace">{{ $document->displayNumber() }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border small mb-3">
                    <i class="bi bi-info-circle text-primary"></i>
                    Bila disetujui sampai tahap terakhir, dokumen menjadi <strong>Tidak Berlaku</strong>
                    dan <strong>nomornya dilepas</strong> — nomor itu bisa dipakai dokumen baru.
                </div>

                <label for="alasanNonaktif{{ $document->id }}" class="form-label small fw-semibold">
                    Alasan nonaktif <span class="text-danger">*</span>
                </label>
                <textarea id="alasanNonaktif{{ $document->id }}" name="alasan" rows="3" maxlength="2000" required
                          class="form-control @error('alasan') is-invalid @enderror"
                          placeholder="mis. Pekerjaannya sudah tidak dilakukan sejak alat X dipensiunkan.">{{ old('alasan') }}</textarea>
                @error('alasan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Dibaca SH/DH, Management Development, dan PJO; tercatat di Log Dokumen.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-warning"><i class="bi bi-slash-circle"></i> Ajukan Nonaktif</button>
            </div>
        </form>
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Validasi gagal → halaman dimuat ulang dan modalnya tertutup, sehingga
        // pesan kesalahan tak pernah terbaca. Pola yang sama dengan
        // documents/_modal-revisi dan review/_modal-alihkan.
        document.addEventListener('DOMContentLoaded', function () {
            const id = @json(old('nonaktif_doc'));
            const el = id ? document.getElementById('modalNonaktif' + id) : null;
            if (el) new bootstrap.Modal(el).show();
        });
    </script>
    @endpush
@endonce
