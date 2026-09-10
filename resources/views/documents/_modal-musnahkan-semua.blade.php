{{--
    Bersihkan SELURUH dokumen (PLAN C §C4) — Admin IT.

    Bentuknya sengaja kembar dengan _modal-musnahkan: alasan + ketik ulang, lalu
    SweetAlert menyusul saat dikirim. Yang berbeda cuma apa yang diketik — di
    sana nomor satu dokumen, di sini frasa yang menyatakan seluruhnya.
--}}
<div class="modal fade" id="modalMusnahkanSemua" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('documents.purgeAll') }}" class="modal-content"
              data-confirm="Musnahkan SELURUH {{ $jumlahDokumen }} dokumen? Seluruh isi, versi, riwayat, lampiran, dan masukan hilang permanen. Tidak ada undo."
              data-confirm-title="Bersihkan Seluruh Dokumen?" data-confirm-ok="Ya, musnahkan semua" data-confirm-icon="warning">
            @csrf
            @method('DELETE')

            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-octagon text-danger"></i> Bersihkan Seluruh Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger py-2 small mb-3">
                    <span class="fw-semibold">{{ $jumlahDokumen }} dokumen</span> — segala status, tujuh departemen,
                    termasuk yang sudah diarsipkan. Beserta lampiran, berkas arsip, dan singgahan PDF-nya.
                    <span class="fw-semibold">Tidak ada undo.</span>
                </div>
                <div class="alert alert-light border py-2 small mb-3">
                    <i class="bi bi-info-circle"></i> Akun pengguna, departemen, jenis dokumen, menu Informasi,
                    dan <span class="fw-semibold">Audit Log</span> tidak tersentuh.
                </div>

                <div class="mb-3">
                    <label for="alasanSemua" class="form-label small fw-semibold">Alasan pembersihan</label>
                    <textarea id="alasanSemua" name="alasan" class="form-control" rows="3" required minlength="10"
                              placeholder="mis. mengosongkan data uji sebelum impor arsip lama">{{ old('alasan') }}</textarea>
                    <div class="form-text">Tersimpan di Audit Log sebagai satu-satunya jejak yang tersisa.</div>
                </div>

                <div>
                    <label for="konfirmasiSemua" class="form-label small fw-semibold">Ketik frasa konfirmasi</label>
                    <input type="text" id="konfirmasiSemua" name="konfirmasi" class="form-control font-monospace"
                           required autocomplete="off" placeholder="MUSNAHKAN SEMUA DOKUMEN">
                    <div class="form-text">Harus persis <span class="fw-semibold font-monospace">MUSNAHKAN SEMUA DOKUMEN</span>.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger"><i class="bi bi-trash3"></i> Musnahkan Semua</button>
            </div>
        </form>
    </div>
</div>
