{{--
    Alihkan Tugas Peninjauan JSA (PLAN-REVISI-v6 butir 3).

    GL SHE yang mejanya penuh menyerahkan satu JSA ke peninjau lain yang
    sama-sama berwenang. Dokumennya LANGSUNG berpindah (keputusan pemilik B1) —
    tak ada persetujuan yang perlu ditunggu, jadi tak ada pula status baru.

    Pemilihannya memakai PAPAN KETERSEDIAAN yang sama dengan wizard, bukan
    dropdown: yang dicari GL di sini justru "siapa yang paling lapang", dan itu
    pertanyaan pembandingan — persis yang tak bisa dijawab dropdown. Efek
    sampingnya gratis: peninjau yang sedang off ikut terkunci di sini tanpa satu
    baris aturan tambahan.

    Butuh: $document, $alihKandidat (Collection), $alihKetersediaan (array)
--}}
<div class="modal fade" id="modalAlihkan{{ $document->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form method="POST" action="{{ route('review.alihkan', $document) }}" class="modal-content"
              data-confirm="Peninjauan {{ $document->displayNumber() }} langsung berpindah ke peninjau yang dipilih dan tidak lagi muncul di antrian Anda."
              data-confirm-title="Alihkan peninjauan?" data-confirm-ok="Ya, alihkan">
            @csrf
            {{-- Dipakai membuka kembali modal ini bila validasi gagal. --}}
            <input type="hidden" name="alih_doc" value="{{ $document->id }}">

            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold mb-0">
                    <i class="bi bi-arrow-left-right"></i> Alihkan Peninjauan —
                    <span class="font-monospace">{{ $document->displayNumber() }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @include('documents.fields._papan-ketersediaan', [
                    'key' => 'tujuan',
                    'nama' => 'tujuan',
                    'section' => [
                        'label' => 'Alihkan kepada',
                        'required' => true,
                        'hint' => 'Kandidat yang sama dengan saat dokumen ini ditugaskan. Angka di kanan = dokumen yang sedang ia tinjau.',
                    ],
                    'options' => $alihKandidat,
                    'selected' => old('tujuan'),
                    'ketersediaan' => $alihKetersediaan,
                    'memblokir' => true,
                ])
                @error('tujuan')<div class="invalid-feedback d-block mb-3">{{ $message }}</div>@enderror

                <label for="alasanAlih{{ $document->id }}" class="form-label small fw-semibold">
                    Alasan pengalihan <span class="text-danger">*</span>
                </label>
                <textarea id="alasanAlih{{ $document->id }}" name="alasan" rows="3" maxlength="2000" required
                          class="form-control @error('alasan') is-invalid @enderror"
                          placeholder="mis. Sedang menangani 6 JSA lain minggu ini.">{{ old('alasan') }}</textarea>
                @error('alasan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Dikirim ke peninjau tujuan dan tercatat di audit log.</div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-left-right"></i> Alihkan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        // Validasi gagal → halaman dimuat ulang dan modalnya tertutup, sehingga
        // pesan kesalahan tak pernah terbaca. Modal ini dibuka lagi (isian lama
        // dipulihkan `old()` di markup di atas). Pola yang sama dengan
        // documents/_modal-revisi.
        document.addEventListener('DOMContentLoaded', function () {
            // `?alih=1` datang dari tombol Alihkan di TABEL antrian: modalnya
            // tak dirakit di sana (papan ketersediaan per baris = satu
            // perhitungan kandidat untuk tiap baris JSA), jadi tombol itu
            // mengantar ke halaman ini dan membukanya begitu tiba.
            const id = @json(old('alih_doc') ?: (request('alih') ? $document->id : null));
            const el = id ? document.getElementById('modalAlihkan' + id) : null;
            if (el) new bootstrap.Modal(el).show();
        });
    </script>
@endpush
