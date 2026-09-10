{{--
    Daftar masukan lapangan (FITUR-BARU-v4 §3).

    Dipakai DUA tempat: panel "Masukan Lapangan" di halaman detail dokumen, dan
    riwayat "Masukan Anda sebelumnya" di modal Non-Staff. Satu partial supaya
    tampilan status & balasan tak pernah berbeda antar halaman.

    Butuh: $masukan (koleksi), $bolehMembalas (bool — form balasan SH/DH).
    CATATAN: $bolehMembalas WAJIB false bila partial ini dipasang di dalam
    <form> lain; form bersarang tidak sah di HTML dan tak akan terkirim.
--}}
@php
    $warnaStatus = ['baru' => 'danger', 'dibaca' => 'secondary', 'diadopsi' => 'success', 'ditolak' => 'dark'];
@endphp

@forelse ($masukan as $m)
    <div class="border rounded p-2 mb-2">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <span class="font-monospace text-muted small">{{ $m->feedback_number }}</span>
            <span class="badge-soft badge-soft-{{ $warnaStatus[$m->status] ?? 'secondary' }}">{{ $m->statusLabel() }}</span>
        </div>

        <div class="small">{{ $m->isi }}</div>
        <div class="text-muted" style="font-size:.75rem">
            {{ $m->user?->nameWithJabatan() ?? '—' }} · {{ $m->created_at?->format('d/m/Y H:i') }} WITA
        </div>

        @if ($m->balasan)
            <div class="bg-body-secondary border-start border-3 rounded-end px-2 py-1 mt-2 small">
                <i class="bi bi-reply"></i> <strong>Balasan</strong>
                @if ($m->replier)<span class="text-muted">— {{ $m->replier->nameWithJabatan() }}</span>@endif
                <div>{{ $m->balasan }}</div>
            </div>
        @endif

        @if ($m->status === 'diadopsi' && ! $m->balasan)
            <div class="text-success small mt-1"><i class="bi bi-check2-circle"></i> Diadopsi menjadi bahan revisi dokumen ini.</div>
        @endif

        @if ($bolehMembalas && in_array($m->status, ['baru', 'dibaca'], true))
            {{-- Menutup masukan TANPA revisi; yang diadopsi ditutup otomatis saat Ajukan Revisi. --}}
            <form method="POST" action="{{ route('documents.feedback.respond', $m) }}" class="mt-2"
                  data-confirm="Tutup masukan {{ $m->feedback_number }} tanpa revisi dan kirim balasan ke pengirimnya?"
                  data-confirm-title="Balas & Tutup?" data-confirm-ok="Ya, kirim balasan">
                @csrf
                <div class="input-group input-group-sm">
                    <input type="text" name="balasan" class="form-control" maxlength="2000" required
                           placeholder="Balasan untuk pengirim (mis. sudah sesuai standar terbaru)...">
                    <button class="btn btn-outline-secondary text-nowrap"><i class="bi bi-reply"></i> Balas &amp; Tutup</button>
                </div>
            </form>
        @endif
    </div>
@empty
    <div class="text-muted small"><i class="bi bi-inbox"></i> Belum ada masukan.</div>
@endforelse
