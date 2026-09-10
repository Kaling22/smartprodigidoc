{{--
| Isi SATU panel widget Distribusi — dipakai panel Dokumen Mutu MAUPUN
| Informasi (Fase D).
|
| Barisnya sudah diseragamkan di DashboardController (judul · sub · ikon ·
| warna · tautan · pembaca · c), jadi di sini tak ada satu pun percabangan
| "kalau ini dokumen, kalau ini informasi": dua sumber yang tampil beda hanya
| karena Blade-nya berbeda adalah cara dua kolom Cakupan mulai berbeda arti.
|
| @param array{baris: \Illuminate\Support\Collection, rendah: int} $panel
--}}
<table class="table align-items-center mb-0 pp-dist-tabel">
    {{-- Lebar dipatok di sini, bukan diserahkan ke isi — resep yang sama dengan
         Perjalanan Dokumen. Tanpa ini `table-layout:auto` mengukur dari judul
         terpanjang, kolom Dokumen menggelembung, dan judulnya tetap membungkus
         jadi tiga baris. --}}
    <colgroup>
        <col style="width:45%"><col style="width:23%"><col style="width:12%"><col style="width:20%">
    </colgroup>
    <thead>
        <tr>
            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7 ps-4">Dokumen</th>
            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7">Anggota</th>
            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7 text-center">Unduhan</th>
            <th class="text-uppercase text-secondary text-xxs fw-bold opacity-7 text-center pe-4">Cakupan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($panel['baris'] as $b)
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center py-1">
                        {{-- Kotak bertint + glif jenis/kategori. --}}
                        <span class="pp-jenis-ikon me-3" style="background:{{ $b['warna'] }}1f;color:{{ $b['warna'] }}"
                              title="{{ $b['gelar'] }}">
                            <i class="bi {{ $b['ikon'] }}"></i>
                        </span>
                        {{-- `min-width:0` wajib pada anak flex: tanpa itu ia menolak
                             menyusut di bawah lebar teksnya dan ellipsis tak aktif. --}}
                        <div class="d-flex flex-column justify-content-center" style="min-width:0">
                            <a href="{{ $b['tautan'] }}" class="pp-dist-judul pp-satu-baris text-decoration-none"
                               title="{{ $b['judul'] }}">{{ $b['judul'] }}</a>
                            <span class="text-xs text-muted pp-satu-baris" title="{{ $b['sub'] }}">{{ $b['sub'] }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    {{-- Tumpukan wajah (partials/_avatar): FOTO pembacanya; yang belum
                         berfoto dapat glif orang, tak pernah inisial. --}}
                    @if ($b['pembaca']->isEmpty())
                        <span class="text-xs text-muted">belum ada</span>
                    @else
                        <div class="pp-avatar-grup">
                            @foreach ($b['pembaca']->take(4) as $orang)
                                @include('partials._avatar', ['orang' => $orang])
                            @endforeach
                            @if ($b['pembaca']->count() > 4)
                                <span class="pp-avatar pp-avatar-sisa" title="{{ $b['pembaca']->count() - 4 }} pembaca lain">+{{ $b['pembaca']->count() - 4 }}</span>
                            @endif
                        </div>
                    @endif
                </td>
                <td class="text-center text-sm fw-semibold">{{ $b['c']['unduhan'] }}</td>
                <td class="pe-4" style="min-width:9rem">
                    @include('partials._pita-cakupan', ['c' => $b['c'], 'ringkas' => true])
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
