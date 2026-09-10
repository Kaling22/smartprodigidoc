@extends('layouts.app')
@section('title', 'Masukan Sejawat: ' . $document->displayNumber())

@php
    /*
    | Nama bagian dibaca dari SCHEMA, tak pernah dari `section_key` mentah.
    | Kunci seperti `tujuan_ruang_lingkup` adalah nama untuk mesin; menampilkan
    | apa adanya membuat pembuat dokumen harus menebak bagian mana yang
    | dimaksud rekannya.
    */
    $labelSection = function (string $key) use ($schema) {
        return $schema->findSection($key)['label'] ?? $key;
    };

    /*
    | Penunjuk item. Dua bentuk sekaligus, sebab formulir tinjauan memang
    | mengirim dua bentuk (review/show.blade.php):
    |   • angka biasa   → item ke-n sebuah daftar
    |   • L0-B1-P2      → Langkah / Bahaya / Pengendalian pada analisa JSA
    | Nomor dinaikkan satu supaya cocok dengan yang TERBACA di layar & PDF,
    | yang menghitung dari 1, bukan dari 0.
    */
    $labelItem = function (string $ref) {
        if (is_numeric($ref)) {
            return 'Item '.((int) $ref + 1);
        }

        if (preg_match('/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/', $ref, $m)) {
            $teks = 'Langkah '.($m[1] + 1);
            $teks .= isset($m[2]) && $m[2] !== '' ? '.'.($m[2] + 1) : '';
            $teks .= isset($m[3]) && $m[3] !== '' ? '.'.($m[3] + 1) : '';

            return $teks.match (true) {
                isset($m[3]) && $m[3] !== '' => ' — Tindakan Pengendalian',
                isset($m[2]) && $m[2] !== '' => ' — Bahaya & Risiko',
                default => ' — Langkah Kerja',
            };
        }

        return $ref;
    };
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ url()->previous() }}" class="small text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h1 class="h5 fw-bold mb-0 mt-1">{{ $document->title }}</h1>
            <span class="font-monospace text-primary small">{{ $document->displayNumber() }}</span>
            <span class="badge-soft">{{ $document->department->code }}</span>
            @include('partials._badge-status', ['status' => $document->status])
        </div>
        <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Lihat Dokumen</a>
    </div>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        Catatan dari rekan sedepartemen atas dokumen ini. Ini <strong>bukan hasil peninjauan</strong> —
        tak ada status yang berubah karenanya, dan Anda bebas memakainya atau tidak saat menyunting dokumen.
    </div>

    @forelse ($masukan as $m)
        {{-- Anchor per kartu supaya Log Pesan & lonceng bisa menaut langsung ke
             SATU masukan, bukan hanya ke halamannya (Fase 3b). --}}
        <div class="card border-0 shadow-sm mb-3" id="m{{ $m->id }}">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-bold"><i class="bi bi-chat-square-text"></i> {{ $m->user?->nameWithJabatan() ?? '—' }}</span>
                <span class="small text-muted">{{ $m->created_at?->format('d/m/Y H:i') }} WITA</span>
            </div>
            <div class="card-body">
                @if (filled($m->ringkasan))
                    <div class="mb-3">
                        <div class="small text-muted fw-semibold mb-1">Ringkasan / Catatan Umum</div>
                        <div style="white-space:pre-line">{{ $m->ringkasan }}</div>
                    </div>
                @endif

                @php
                    // Dikelompokkan per bagian dokumen, bukan dibiarkan sebagai
                    // daftar datar: pembuat menyunting dokumennya per bagian,
                    // jadi catatan yang tercerai-berai memaksanya membolak-balik.
                    $perSection = collect($m->catatan_json ?? [])->groupBy('section_key');
                @endphp

                @forelse ($perSection as $key => $items)
                    <div class="border rounded p-2 mb-2">
                        <div class="fw-semibold small mb-2">{{ $labelSection($key) }}</div>
                        @foreach ($items as $c)
                            <div class="d-flex gap-2 mb-2">
                                <span class="badge-soft text-nowrap">{{ $labelItem((string) ($c['item_ref'] ?? '')) }}</span>
                                <div style="white-space:pre-line">{{ $c['komentar'] ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                @empty
                    @if (blank($m->ringkasan))
                        <div class="text-muted small">(Masukan kosong)</div>
                    @endif
                @endforelse
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Belum ada masukan sejawat atas dokumen ini.
            </div>
        </div>
    @endforelse
@endsection
