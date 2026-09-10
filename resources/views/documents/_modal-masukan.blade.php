{{--
    Form "Beri Masukan" untuk Non-Staff (FITUR-BARU-v4 §3, kanal WEB).

    Kembaran layar Report di aplikasi mobile — orang lapangan tak selalu
    memegang HP saat berada di kantor. Keduanya menulis ke tabel yang sama.

    Riwayat masukan si pengirim atas dokumen ini ikut ditampilkan di sini
    supaya ia tahu masukannya berujung ke mana tanpa berpindah halaman.

    Butuh: $document, $masukanSaya (koleksi masukan milik pengguna ini).
--}}
<div class="modal fade" id="modalMasukan{{ $document->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('documents.feedback.store', $document) }}" class="modal-content"
              data-confirm="Kirim masukan ini ke Section Head / Departemen Head {{ $document->department->code }}?"
              data-confirm-title="Kirim Masukan?" data-confirm-ok="Ya, kirim">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold mb-0"><i class="bi bi-chat-left-dots"></i> Beri Masukan — <span class="font-monospace">{{ $document->displayNumber() }}</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-light border small py-2">
                    <i class="bi bi-info-circle"></i> Masukan tidak mengubah isi dokumen. Ia dikirim ke atasan Anda sebagai bahan pertimbangan revisi.
                </div>

                <label for="isi{{ $document->id }}" class="form-label small fw-semibold">Isi masukan</label>
                <textarea id="isi{{ $document->id }}" name="isi" rows="4" maxlength="2000" required
                          class="form-control @error('isi') is-invalid @enderror"
                          placeholder="mis. Langkah 5 tidak sesuai kondisi pit; alat pelindung belum disebut.">{{ old('isi') }}</textarea>
                @error('isi')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

                @if ($masukanSaya->isNotEmpty())
                    <hr class="my-3">
                    <div class="fw-semibold small mb-2"><i class="bi bi-clock-history"></i> Masukan Anda sebelumnya</div>
                    @include('documents._masukan-list', ['masukan' => $masukanSaya, 'bolehMembalas' => false])
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Kirim Masukan</button>
            </div>
        </form>
    </div>
</div>
