{{--
    Modal "tugas menunggu" — muncul SEKALI tepat sesudah login (penanda flash
    `antrean_awal` dari LoginController).

    Isinya $antrean milik layout, sumber yang sama dengan badge sidebar, jadi
    angka di modal dan angka di menu mustahil berbeda. Antrean kosong tak pernah
    sampai ke sini: layout tak me-render apa pun — modal yang tak membawa kabar
    hanya melatih orang menutupnya tanpa membaca.
--}}
<div class="modal fade" id="modalAntrean" tabindex="-1" aria-labelledby="modalAntreanJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAntreanJudul">
                    <i class="bi bi-inbox me-1"></i> Ada {{ count($antrean) }} hal yang menunggu Anda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="list-group list-group-flush">
                    @foreach ($antrean as $tugas)
                        <a class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                           href="{{ route($tugas['route']) }}">
                            <i class="bi {{ $tugas['icon'] }}"></i>
                            <span>{{ $tugas['label'] }}</span>
                            <span class="badge-soft badge-soft-danger ms-auto">{{ $tugas['jumlah'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
