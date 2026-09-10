{{-- Hapus Informasi (versi BERLAKU) — cakupannya dipilih di sini, tak diasumsikan.

     Dua pilihan itu tak bisa dibatalkan dan akibatnya sangat berbeda: "versi
     ini saja" mengembalikan riwayat terbaru menjadi berlaku, jadi nomor itu
     tetap punya dokumen; "seluruhnya" membuang nomor itu dari daftar. Satu
     tombol Hapus yang diam-diam memilih salah satunya adalah tombol yang suatu
     hari memilih yang tidak diinginkan.

     Tanpa riwayat, keduanya berakibat sama persis — jadi pilihannya tak
     ditawarkan sama sekali. Pilihan palsu lebih buruk daripada tidak ada
     pilihan: ia menyuruh orang menimbang sesuatu yang tak ada bedanya. --}}
@php $punyaRiwayat = ($jumlahRiwayat ?? 0) > 0; @endphp

<div class="modal fade" id="modalHapusInfo{{ $info->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold"><i class="bi bi-trash3 text-danger"></i> Hapus {{ $info->nomor }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-3">
                    <span class="fw-semibold">{{ $info->judul }}</span>
                    @if ($info->labelRevisi())<span class="badge-soft">{{ $info->labelRevisi() }}</span>@endif
                    @if ($punyaRiwayat)
                        <span class="d-block text-muted mt-1">
                            Nomor ini punya <strong>{{ $jumlahRiwayat }}</strong> versi lama di riwayatnya.
                        </span>
                    @endif
                </p>

                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('informasi.destroy', $info) }}"
                          data-confirm="{{ $punyaRiwayat
                              ? 'Versi ini dihapus, dan versi riwayat TERBARU naik menggantikannya sehingga nomor '.$info->nomor.' tetap punya dokumen.'
                              : 'Hapus '.$info->nomor.'? Nomor ini tidak punya versi lain, jadi ia hilang dari daftar sampai diunggah lagi.' }}"
                          data-confirm-title="Hapus {{ $punyaRiwayat ? 'Versi Ini' : $info->nomor }}?"
                          data-confirm-icon="warning" data-confirm-ok="Ya, hapus">
                        @csrf @method('DELETE')
                        <input type="hidden" name="cakupan" value="versi">
                        <button class="btn btn-{{ $punyaRiwayat ? 'outline-danger' : 'danger' }} w-100 text-start">
                            <i class="bi bi-file-earmark-x"></i>
                            <span class="fw-semibold">{{ $punyaRiwayat ? 'Hapus versi ini saja' : 'Hapus dokumen ini' }}</span>
                            <span class="d-block small {{ $punyaRiwayat ? 'text-muted' : '' }} ms-4" @if (! $punyaRiwayat) style="opacity:.85" @endif>
                                {{ $punyaRiwayat ? 'Riwayat terbaru naik jadi berlaku.' : 'Tidak ada riwayat yang ikut terhapus.' }}
                            </span>
                        </button>
                    </form>

                    @if ($punyaRiwayat)
                        <form method="POST" action="{{ route('informasi.destroy', $info) }}"
                              data-confirm="SELURUH versi bernomor {{ $info->nomor }} dihapus — versi berlaku dan {{ $jumlahRiwayat }} versi riwayatnya. Nomor ini hilang dari daftar sampai diunggah lagi."
                              data-confirm-title="Hapus Seluruhnya?" data-confirm-icon="warning" data-confirm-ok="Ya, hapus semuanya">
                            @csrf @method('DELETE')
                            <input type="hidden" name="cakupan" value="semua">
                            <button class="btn btn-danger w-100 text-start">
                                <i class="bi bi-trash3"></i>
                                <span class="fw-semibold">Hapus seluruhnya, riwayat sekalian</span>
                                <span class="d-block small ms-4" style="opacity:.85">{{ $jumlahRiwayat + 1 }} versi dibuang.</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>
