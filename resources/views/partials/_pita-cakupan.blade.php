{{--
| PITA CAKUPAN — berapa orang yang wajib tahu sudah membuka dokumen ini.
|
| Satu partial dipakai TIGA tempat (menu Distribusi, panel detail dokumen,
| widget dashboard) supaya ambang warnanya mustahil berbeda-beda. Ambang yang
| beda antar halaman membuat orang berdebat soal warna, bukan soal dokumennya.
|
|   < 50%  merah   — belum sampai; perlu ditindaklanjuti
|   50-79% kuning  — sedang berjalan
|   >= 80% biru    — sudah tersebar
|
| Angka disebutkan APA ADANYA di sebelah pita ("12 dari 34 orang"). Persen saja
| menipu di departemen kecil: 1 dari 2 orang bukan "50% tersebar".
|
| @param array{pembaca:int, sasaran:int, persen:int, unduhan:int} $c
| @param bool $ringkas  true → hanya pita + persen (untuk sel tabel sempit)
--}}
@php
    $ringkas = $ringkas ?? false;
    $persen = $c['sasaran'] > 0 ? $c['persen'] : 0;
    // Merah → kuning → hijau: naik searah "makin baik", jadi warnanya sendiri
    // sudah memberi tahu apakah angkanya cukup. Ambang tertinggi dulu BIRU —
    // biru tak berarti "baik" bagi siapa pun, jadi pembaca tetap harus membaca
    // angkanya untuk tahu ini kabar bagus atau bukan.
    $warna = match (true) {
        $c['sasaran'] === 0 => 'bg-secondary',
        $persen < 50 => 'bg-su-merah',
        $persen < 80 => 'bg-su-yellow',
        default => 'bg-su-hijau',
    };
@endphp

<div class="pp-cakupan {{ $ringkas ? 'pp-cakupan-ringkas' : '' }}">
    <div class="pp-cakupan-rel" role="img"
         aria-label="Cakupan {{ $persen }} persen, {{ $c['pembaca'] }} dari {{ $c['sasaran'] }} orang">
        {{-- width dibatasi 100%: pembaca bisa MELEBIHI sasaran (mis. peninjau
             dari departemen lain ikut membuka), dan pita yang meluber keluar
             kartunya terlihat seperti bug. --}}
        <span class="pp-cakupan-isi {{ $warna }}" style="width:{{ min(100, $persen) }}%"></span>
    </div>
    @if ($c['sasaran'] === 0)
        <span class="pp-cakupan-teks text-muted">tak ada sasaran</span>
    @else
        <span class="pp-cakupan-teks">
            <strong>{{ $persen }}%</strong>
            @unless ($ringkas)
                <span class="text-muted">· {{ $c['pembaca'] }} dari {{ $c['sasaran'] }} orang</span>
            @endunless
        </span>
    @endif
</div>

{{-- Gaya menempel pada partialnya, bukan pada tiap halaman pemakai: @once
     menjamin ia hanya terkirim sekali betapapun banyak pita di satu halaman,
     dan menambah pemakai baru tak perlu ingat menyalin CSS-nya. --}}
@once
    @push('styles')<style>
        .pp-cakupan { display: flex; align-items: center; gap: .5rem; }
        .pp-cakupan-rel {
            flex: 1 1 auto; min-width: 3rem; height: .45rem; border-radius: .45rem;
            background: var(--bs-secondary-bg); overflow: hidden;
        }
        .pp-cakupan-isi { display: block; height: 100%; border-radius: .45rem; transition: width .3s ease; }
        .pp-cakupan-teks { flex: 0 0 auto; font-size: .72rem; white-space: nowrap; }
        .pp-cakupan-ringkas .pp-cakupan-rel { min-width: 2.5rem; }
    </style>@endpush
@endonce
