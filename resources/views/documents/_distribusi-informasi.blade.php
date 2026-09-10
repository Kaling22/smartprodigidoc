{{--
| Cabang `?sumber=informasi` halaman Distribusi (Fase D / butir 4).
|
| Dipisah ke partial, bukan dijejalkan sebagai @if raksasa di dalam
| distribution.blade.php: kolomnya memang berbeda (kategori, bukan jenis &
| departemen), dan dua tabel bertumpuk di satu berkas membuat keduanya sulit
| diubah tanpa merusak yang lain.
|
| @param \Illuminate\Support\Collection $informasi
| @param array $cakupan
| @param array $perDept  rincian 7 departemen; KOSONG bagi non-`document.view_all`
--}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="sumber" value="informasi">
            <div class="col-md-6">
                <label class="form-label small fw-semibold">Cari (nomor / judul)</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="mis. nomor kebijakan atau judul...">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Kategori</label>
                <select name="kategori" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    {{-- Daftar PENUH, termasuk kategori nonaktif: yang disaring di
                         sini adalah dokumen yang SUDAH ADA, dan menutup kategori tak
                         pernah berarti dokumennya hilang. --}}
                    @foreach (\App\Models\Informasi::kategori() as $kunci => $label)
                        <option value="{{ $kunci }}" @selected(($filters['kategori'] ?? '') === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold invisible d-block mb-1">.</label>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-input-h btn-secondary flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('documents.distribution', ['sumber' => 'informasi']) }}" class="btn btn-sm btn-input-h btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nomor</th><th>Judul</th><th>Kategori</th>
                        <th style="min-width:11rem">Cakupan</th>
                        <th class="text-center">Unduhan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                @forelse ($informasi as $i)
                    @php $c = $cakupan[$i->id]; @endphp
                    {{-- Satu <tbody> per informasi: barisnya dan baris rinciannya
                         berbagi satu keadaan Alpine, jadi tak perlu id unik yang
                         harus dijaga tetap cocok antara tombol dan targetnya. --}}
                    <tbody x-data="{ buka: false }">
                        <tr>
                            <td class="font-monospace small">{{ $i->nomor }}</td>
                            <td>
                                {{ $i->judul }}
                                @if ($i->labelRevisi())
                                    <span class="text-muted small">· {{ $i->labelRevisi() }}</span>
                                @endif
                            </td>
                            <td><span class="badge-soft">{{ \App\Models\Informasi::labelKategori($i->kategori) }}</span></td>
                            <td>@include('partials._pita-cakupan', ['c' => $c])</td>
                            <td class="text-center small">
                                {{-- Unduhan dipisah dari cakupan: cakupan = berapa ORANG,
                                     unduhan = berapa KALI. --}}
                                <span class="text-muted">{{ $c['unduhan'] }}×</span>
                            </td>
                            <td class="text-end">
                                {{-- Dua tombol, dua pertanyaan berbeda: expander
                                     menjawab "departemen mana yang tertinggal",
                                     Rincian menjawab "siapa orangnya". Rincian
                                     ada untuk SEMUA yang berwenang — SH/DH dulu
                                     hanya diberi tautan balik ke daftar
                                     kategori, yang tak menjawab apa pun. --}}
                                @if (! empty($perDept[$i->id]))
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="buka = ! buka"
                                            :aria-expanded="buka.toString()">
                                        <i class="bi bi-diagram-3"></i> Per Departemen
                                        <i class="bi" :class="buka ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                    </button>
                                @endif
                                <a href="{{ route('documents.rincianInformasi', $i) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Siapa yang sudah & belum membuka">
                                    <i class="bi bi-people"></i> Rincian
                                </a>
                            </td>
                        </tr>
                        @if (! empty($perDept[$i->id]))
                            <tr x-show="buka" x-cloak>
                                <td colspan="6" class="bg-body-tertiary">
                                    <div class="row g-2 py-1">
                                        @foreach ($perDept[$i->id] as $d)
                                            <div class="col-md-6 col-xl-4 d-flex align-items-center gap-2">
                                                <span class="badge-soft" style="min-width:4.5rem">{{ $d['dept'] }}</span>
                                                <div class="flex-grow-1">@include('partials._pita-cakupan', ['c' => $d])</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="text-muted text-xs mb-0">
                                        Rincian ini hanya menghitung orang yang punya departemen, jadi jumlahnya
                                        bisa lebih kecil daripada angka gabungan di kolom Cakupan.
                                    </p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-broadcast fs-3 d-block mb-2 opacity-50"></i>
                            Belum ada informasi berlaku yang cocok dengan filter ini.
                        </td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>
