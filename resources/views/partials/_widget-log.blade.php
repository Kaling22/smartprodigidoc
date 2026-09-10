{{--
| Log Aktivitas — garis waktu vertikal (gaya "Orders overview" soft_ui).
|
| Kartunya BERDIRI SENDIRI, bukan menempel di bawah kalender dalam satu kartu:
| batas antara keduanya adalah garis yang dipakai menyejajarkan kolom kiri
| (spec v2 L1/L2) — Perjalanan Dokumen berakhir tepat di batas itu, dan Masukan
| Lapangan bersanding dengan kartu ini.
|
| Butuh: $activities, $actionMeta
--}}
<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent pb-0">
        <h6 class="fw-bold mb-0">Log Aktivitas</h6>
        <p class="text-sm text-muted mb-0">aktivitas terbaru</p>
    </div>
    <div class="card-body pp-log-isi pp-gulir">
        <div class="pp-timeline">
            @forelse ($activities as $log)
                {{-- Cadangan HITAM, bukan biru: aksi yang belum dipetakan adalah
                     peristiwa netral. Nama mentahnya tetap tampil sebagai isyarat
                     bahwa aksi itu perlu ditambahkan ke $actionMeta. --}}
                @php [$aksi, $icon, $bg] = $actionMeta[$log->action] ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'bg-su-dark']; @endphp
                <div class="pp-tl-item">
                    <span class="pp-tl-icon {{ $bg }}"><i class="bi {{ $icon }}"></i></span>
                    <div class="pp-tl-body">
                        <div class="text-sm"><span class="fw-semibold">{{ $log->user->name ?? 'Sistem' }}</span> {{ $aksi }}
                            @if ($log->document)<a href="{{ route('documents.show', $log->document) }}" class="text-decoration-none font-monospace text-xs">{{ $log->document->displayNumber() }}</a>@endif
                        </div>
                        <div class="text-xs text-muted">{{ $log->created_at?->format('d M, H:i') }} WITA</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Belum ada aktivitas.</div>
            @endforelse
        </div>
    </div>
</div>

@once
    @push('styles')<style>
        /* Digulir DI DALAM kartunya (spec v2 W2), bukan memanjangkan kartunya.
           Batang gulirnya sendiri disembunyikan oleh `.pp-gulir` (layouts/app).

           `flex:1 1 auto; min-height:0` wajib: tanpa min-height, flex item
           menolak menyusut di bawah tinggi isinya dan `overflow` tak pernah
           aktif — kartu ini setinggi barisnya (h-100), jadi tingginya memang
           ditentukan tetangga kirinya. `max-height` menjaga kasus sebaliknya,
           saat tetangganya pendek. */
        .pp-log-isi { flex: 1 1 auto; min-height: 0; max-height: 22rem; }
    </style>@endpush
@endonce
