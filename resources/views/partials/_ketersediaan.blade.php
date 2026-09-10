{{--
    Kartu "Ketersediaan Saya" — KALENDER BULAN BERJALAN + log aktivitas.

    Ditempatkan di dashboard, bukan di halaman tersendiri: menyatakan diri off
    adalah tindakan sepuluh detik yang dilakukan sambil lalu, bukan tujuan
    kunjungan.

    Yang dilepas dari versi sebelumnya (pita 14 hari, pil status, meteran beban
    tinjau, daftar off): semuanya menjawab "bisakah saya dipilih jadi peninjau",
    sedangkan pertanyaan kartu ini cuma "kapan saya libur". Jawaban yang pertama
    tetap utuh di papan pemilihan peninjau, yang memang tempatnya.

    Log aktivitas duduk di kartu TERSENDIRI tepat di bawah kartu ini
    (partials/_widget-log): batas antar-keduanya adalah garis yang dipakai
    menyejajarkan kolom kiri (spec v2 L1/L2).

    Butuh: $kalenderOff (DashboardController::kalenderOff()), $offSaya
--}}
@include('partials._pita-style')

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent pb-0">
        <h6 class="fw-bold mb-0">Ketersediaan Saya</h6>
    </div>

    <div class="card-body">
        {{-- Ganti bulan lewat TAUTAN biasa (?bulan=YYYY-MM), bukan JS: kalender
             ini dibaca, bukan dimainkan, dan satu muat ulang halaman lebih murah
             daripada rute AJAX + penggambar kalender sisi klien. Tombolnya
             tetap tautan supaya bisa dibuka di tab baru & di-bookmark. --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="{{ route('dashboard', ['bulan' => $kalenderOff['sebelum']]) }}#kalender"
               class="pp-tombol-polos {{ $kalenderOff['bisaMundur'] ? '' : 'invisible' }}"
               aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>

            <span class="fw-semibold" id="kalender">
                {{ $kalenderOff['judul'] }}
                @unless ($kalenderOff['iniBulanIni'])
                    <a href="{{ route('dashboard') }}#kalender" class="text-xs text-decoration-none ms-1">hari ini</a>
                @endunless
            </span>

            <a href="{{ route('dashboard', ['bulan' => $kalenderOff['sesudah']]) }}#kalender"
               class="pp-tombol-polos {{ $kalenderOff['bisaMaju'] ? '' : 'invisible' }}"
               aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
        </div>

        <div class="pp-kal mb-1">
            @foreach ($kalenderOff['namaHari'] as $nama)
                <div class="pp-kal-nama">{{ $nama }}</div>
            @endforeach
        </div>

        <div class="pp-kal">
            @foreach ($kalenderOff['sel'] as $sel)
                @if ($sel === null)
                    {{-- Sel pendahulu supaya tanggal 1 jatuh di kolom harinya. --}}
                    <span></span>
                @else
                    {{-- Tiap tanggal adalah TOMBOL: menekannya membuka modal
                         Ajukan Off dengan tanggal itu sudah terisi. Kalender
                         berhenti jadi papan-baca dan menjadi pintu masuk.

                         Tanggal yang sudah LEWAT dimatikan: off kemarin ditolak
                         server (`after_or_equal:today`), jadi membiarkannya
                         bisa ditekan cuma menjanjikan jalan yang pasti buntu —
                         dan sejak kalender bisa mundur sebulan, tanggal lewat
                         memang sering terlihat. --}}
                    @php $lewat = $sel['tanggal']->lt(now()->startOfDay()); @endphp
                    <button type="button" @disabled($lewat)
                            class="pp-kal-hari{{ $sel['off'] ? ' is-off' : '' }}{{ $sel['tanggal']->isToday() ? ' is-hari-ini' : '' }}"
                            data-tanggal="{{ $sel['tanggal']->toDateString() }}"
                            title="{{ $sel['off'] ?: $sel['tanggal']->translatedFormat('l, j F') }}{{ $lewat ? ' · sudah lewat' : ' · klik untuk mengajukan off' }}">
                        {{ $sel['tanggal']->format('j') }}
                    </button>
                @endif
            @endforeach
        </div>

        <div class="pp-av-legenda mt-3"><span><i class="pp-av-off"></i> cuti/off</span></div>

        {{-- Tindakan duduk di BAWAH KANAN, bukan di kepala kartu: kalendernya
             yang dibaca lebih dulu, tombolnya baru dipakai sesudah itu.
             "On Site" hanya muncul saat off SEDANG berjalan — ia membatalkan off
             itu (saya sudah kembali), dan tanpa off aktif tombolnya tak berarti
             apa-apa. Sekaligus inilah jalan membatalkan off yang dulu cuma ada
             di dalam modal. --}}
        <div class="d-flex justify-content-end align-items-center gap-1 mt-3">
            @if ($offAktif)
                <button type="submit" form="batalOff{{ $offAktif->id }}" class="pp-tombol-polos">
                    <i class="bi bi-person-check"></i> On Site
                </button>
            @endif
            <button type="button" class="pp-tombol-polos" data-bs-toggle="modal" data-bs-target="#modalOff">
                <i class="bi bi-calendar-plus"></i> Ajukan Off
            </button>
        </div>
    </div>
</div>

{{-- Modal Ajukan Off --}}
<div class="modal fade" id="modalOff" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('off.store') }}" class="modal-content"
              data-confirm="Anda tidak akan bisa dipilih sebagai peninjau selama rentang ini. Dokumen yang sedang Anda tinjau tetap menjadi tanggung jawab Anda."
              data-confirm-title="Ajukan off?" data-confirm-ok="Ya, ajukan">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title h6 fw-bold mb-0"><i class="bi bi-calendar-plus"></i> Ajukan Off</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <label class="form-label small fw-semibold d-block">Jenis</label>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    @foreach (\App\Models\UserOffDay::JENIS_LABELS as $nilai => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis" value="{{ $nilai }}"
                                   id="jenis{{ $nilai }}" @checked(old('jenis', 'cuti') === $nilai) required>
                            <label class="form-check-label small" for="jenis{{ $nilai }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="offMulai" class="form-label small fw-semibold">Mulai</label>
                        {{-- Widget tanggal peramban, bukan ketik manual (CLAUDE.md §7). --}}
                        <input type="date" id="offMulai" name="mulai" class="form-control @error('mulai') is-invalid @enderror"
                               value="{{ old('mulai', now()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                        @error('mulai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="offSampai" class="form-label small fw-semibold">Sampai</label>
                        <input type="date" id="offSampai" name="sampai" class="form-control @error('sampai') is-invalid @enderror"
                               value="{{ old('sampai', now()->toDateString()) }}" min="{{ now()->toDateString() }}"
                               max="{{ now()->addDays(60)->toDateString() }}" required>
                        @error('sampai')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <label for="offCatatan" class="form-label small fw-semibold">Catatan (opsional)</label>
                <input type="text" id="offCatatan" name="catatan" maxlength="255" class="form-control"
                       placeholder="mis. cuti tahunan" value="{{ old('catatan') }}">

                <div class="alert alert-light border small mt-3 mb-0 py-2">
                    <i class="bi bi-info-circle"></i> Selama rentang ini Anda tidak muncul sebagai peninjau yang bisa
                    dipilih. Dokumen yang <strong>sedang</strong> Anda tinjau tetap menjadi tanggung jawab Anda.
                </div>

                {{-- Daftar off pindah KE SINI dari muka kartu (spec: kartunya cuma
                     kalender + log). Di sini ia mengurus off yang AKAN DATANG;
                     yang sedang berjalan hari ini punya jalan pintasnya sendiri —
                     tombol "On Site" di muka kartu. --}}
                @if ($offSaya->isNotEmpty())
                    <div class="fw-semibold small mt-3 mb-2">Off yang tercatat</div>
                    @foreach ($offSaya as $off)
                        <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1">
                            <span class="small">
                                <span class="fw-semibold">{{ $off->jenisLabel() }}</span>
                                <span class="text-muted">· {{ $off->rentangLabel() }}</span>
                                @if ($off->catatan)<span class="text-muted d-block" style="font-size:.75rem">{{ $off->catatan }}</span>@endif
                            </span>
                            {{-- Tombolnya menunjuk form LAIN lewat `form=`: <form> di
                                 dalam <form> tak sah, dan formulir batal harus tetap
                                 berada di luar formulir pengajuan. --}}
                            <button class="btn btn-sm btn-link text-danger p-0 text-decoration-none"
                                    form="batalOff{{ $off->id }}">Batalkan</button>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-sm btn-primary"><i class="bi bi-calendar-plus"></i> Ajukan Off</button>
            </div>
        </form>
    </div>
</div>

@foreach ($offSaya as $off)
    <form method="POST" action="{{ route('off.destroy', $off) }}" id="batalOff{{ $off->id }}"
          data-confirm="Batalkan {{ $off->jenisLabel() }} {{ $off->rentangLabel() }}? Anda langsung muncul lagi sebagai peninjau yang bisa dipilih."
          data-confirm-title="Batalkan off?" data-confirm-ok="Ya, batalkan">
        @csrf @method('DELETE')
    </form>
@endforeach

@push('styles')<style>
    /* KALENDER BULANAN. Sel tanpa bingkai — 31 kotak bergaris di kolom 30%
       terbaca seperti tabel, bukan kalender. Yang diberi rupa hanya dua hal
       yang memang berbeda: hari ini (cincin) dan tanggal off (gradasi tema,
       kelas .pp-av-off yang sama dengan papan peninjau). */
    .pp-kal-hari {
        aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
        padding: 0; border: 0; border-radius: .5rem; background: transparent;
        font-size: .78rem; font-weight: 500; color: var(--bs-body-color); cursor: pointer;
        transition: background-color .12s ease;
    }
    .pp-kal-hari:hover:not(:disabled) { background: var(--bs-tertiary-bg); }
    .pp-kal-hari:focus-visible { outline: 2px solid var(--su-orange1); outline-offset: 2px; }
    /* Tanggal lewat: masih TERBACA (ia tetap bagian kalender, dan tanda off di
       masa lalu justru bagian ceritanya), cuma tak bisa ditekan. */
    .pp-kal-hari:disabled { cursor: default; opacity: .45; }
    /* ANGKANYA yang oranye, bukan latarnya yang oranye dengan angka putih:
       angka putih di atas gradasi memang paling mencolok, tapi ia berhenti
       terbaca sebagai TANGGAL dan berubah jadi blok warna. Latarnya cukup
       tint tipis dari warna yang sama. */
    .pp-kal-hari.is-off {
        color: var(--su-orange1); font-weight: 700;
        background: color-mix(in srgb, var(--su-orange1) 15%, transparent);
    }
    .pp-kal-hari.is-hari-ini { box-shadow: inset 0 0 0 2px var(--su-orange1); color: var(--su-orange1); font-weight: 700; }

    /* Tombol tindakan kartu: TANPA bingkai. Dua tombol berbingkai bersebelahan
       di sudut kartu terbaca seperti formulir; teks berwarna sudah cukup
       menyatakan bahwa keduanya bisa ditekan. */
    .pp-tombol-polos {
        border: 0; background: transparent; padding: .3rem .55rem; border-radius: .5rem;
        font-size: .78rem; font-weight: 600; color: var(--su-orange1); line-height: 1.4;
        transition: background-color .12s ease;
    }
    .pp-tombol-polos:hover { background: color-mix(in srgb, var(--su-orange1) 12%, transparent); }
    .pp-tombol-polos:focus-visible { outline: 2px solid var(--su-orange1); outline-offset: 2px; }
</style>@endpush

@push('scripts')
    <script>
        // Klik tanggal di kalender → modal terbuka dengan tanggal itu terisi.
        // Kalendernya berhenti jadi papan-baca dan menjadi pintu masuk: orang
        // yang melihat "minggu depan saya libur" bisa langsung menekan harinya.
        document.addEventListener('click', function (e) {
            const sel = e.target.closest('.pp-kal-hari');
            if (!sel) return;

            const tgl = sel.dataset.tanggal;
            const mulai = document.getElementById('offMulai');
            const sampai = document.getElementById('offSampai');
            if (!tgl || !mulai || !sampai) return;

            mulai.value = tgl;
            // Sampai ikut disamakan supaya off sehari — kasus paling sering —
            // langsung sah tanpa menyentuh kolom kedua.
            sampai.value = tgl;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOff')).show();
        });
    </script>
@endpush

{{-- Validasi gagal → halaman dimuat ulang dan modalnya tertutup, sehingga pesan
     kesalahan tak pernah terbaca. Modalnya dibuka lagi (isian dipulihkan old()). --}}
@if ($errors->hasAny(['jenis', 'mulai', 'sampai', 'catatan']))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('modalOff')).show();
            });
        </script>
    @endpush
@endif
