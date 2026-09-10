{{--
    Musnahkan dokumen (§8) — alasan DULU di modal, SweetAlert menyusul saat
    dikirim. Pola yang sama dengan _modal-revisi (CLAUDE.md v4 §4).

    Dua isian, bukan satu — itulah sebabnya ini modal Bootstrap dan bukan
    `input:'text'` pada pemanggil SweetAlert terdelegasi: SweetAlert hanya
    menampung satu isian, sedangkan di sini alasan DAN ketik-ulang nomor
    sama-sama wajib.
--}}
<div class="modal fade" id="modalMusnahkan{{ $document->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('documents.purge', $document) }}" class="modal-content"
              data-confirm="Musnahkan {{ $document->displayNumber() }}? Isi, riwayat tinjauan, persetujuan, versi, dan masukan ikut hilang permanen."
              data-confirm-title="Musnahkan Dokumen?" data-confirm-ok="Ya, musnahkan" data-confirm-icon="warning">
            @csrf
            @method('DELETE')

            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-exclamation-octagon text-danger"></i> Musnahkan Dokumen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger py-2 small">
                    <span class="fw-semibold">{{ $document->displayNumber() }}</span> — {{ $document->title }}<br>
                    Seluruh isi, versi, riwayat, lampiran, dan masukan hilang
                    <span class="fw-semibold">tanpa bisa dikembalikan</span>. Nomornya kembali ke kolam.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Alasan penghapusan</label>
                    <textarea name="alasan" class="form-control" rows="3" required minlength="10"
                              placeholder="mis. dokumen ganda, terbit karena kesalahan input"></textarea>
                    <div class="form-text">Tersimpan di Audit Log sebagai satu-satunya jejak yang tersisa.</div>
                </div>

                <div>
                    <label class="form-label small fw-semibold">Ketik ulang nomor dokumen</label>
                    <input type="text" name="konfirmasi_nomor" class="form-control" required autocomplete="off"
                           placeholder="{{ $document->displayNumber() }}">
                    <div class="form-text">Harus persis <span class="fw-semibold">{{ $document->displayNumber() }}</span>.</div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger"><i class="bi bi-trash3"></i> Musnahkan</button>
            </div>
        </form>
    </div>
</div>
