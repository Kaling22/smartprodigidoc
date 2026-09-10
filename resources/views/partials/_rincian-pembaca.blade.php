{{--
| RINCIAN PEMBACA — siapa sudah membaca, siapa belum.
|
| Satu tata letak, DUA pemakai: panel Distribusi di detail dokumen mutu
| (`documents/show`) dan halaman rincian Informasi (`documents/rincianInformasi`).
| Keduanya menjawab pertanyaan yang sama persis, jadi menyalin markup-nya berarti
| dua daftar yang suatu hari berbeda tanpa alasan yang bisa dijelaskan.
|
| Kolom "Belum Membaca" sengaja diberi lencana MERAH: itulah satu-satunya bagian
| yang bisa ditindaklanjuti.
|
| @param array{sudah: \Illuminate\Support\Collection, belum: \Illuminate\Support\Collection} $rincian
| @param array{pembaca:int, sasaran:int, persen:int, unduhan:int} $cakupan
| @param string $kosong  kalimat saat tak ada sasaran sama sekali
--}}
@if ($cakupan['sasaran'] === 0)
    <div class="text-muted small">{{ $kosong ?? 'Tak ada pengguna aktif yang bisa dihitung sebagai sasaran, jadi tak ada yang bisa diukur.' }}</div>
@else
    <div class="row g-3">
        <div class="col-md-6">
            <div class="fw-semibold small mb-2">
                <i class="bi bi-check2-circle text-success"></i> Sudah Membaca
                <span class="badge-soft badge-soft-success">{{ $rincian['sudah']->count() }}</span>
            </div>
            @forelse ($rincian['sudah'] as $r)
                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                    <span class="small">
                        <i class="bi {{ $r->platform === 'mobile' ? 'bi-phone' : 'bi-laptop' }} text-muted"></i>
                        {{ $r->user->name ?? '—' }}
                        <span class="text-muted">· {{ $r->user?->jabatanShort() }}</span>
                    </span>
                    <span class="text-muted" style="font-size:.7rem">{{ $r->last_read_at?->format('d/m/Y H:i') }} WITA</span>
                </div>
            @empty
                <div class="text-muted small">Belum ada yang membukanya.</div>
            @endforelse
        </div>
        <div class="col-md-6">
            <div class="fw-semibold small mb-2">
                <i class="bi bi-exclamation-circle text-danger"></i> Belum Membaca
                <span class="badge-soft badge-soft-danger">{{ $rincian['belum']->count() }}</span>
            </div>
            @forelse ($rincian['belum'] as $u)
                <div class="border-bottom py-1 small">
                    {{ $u->name }}
                    <span class="text-muted">· {{ $u->jabatanShort() }}</span>
                </div>
            @empty
                <div class="text-success small"><i class="bi bi-check2-all"></i> Semua sudah membaca.</div>
            @endforelse
        </div>
    </div>
@endif
