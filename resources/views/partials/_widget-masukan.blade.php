{{--
| Masukan lapangan — ditampilkan sebagaimana KALIMAT, bukan baris tabel.
|
| Isinya ucapan orang lapangan, jadi bentuknya kutipan: tanda kutip gantung,
| atribusi di bawahnya. Dua wajah, dipilih dari jabatan pembacanya:
|
|   • SH/DH     — masukan sedepartemen yang BELUM ditindak. Tiap baris menaut ke
|                 halaman dokumen, tempat form balasan (`_masukan-list`) sudah
|                 ada — tak ada jalur tindakan baru yang dibuat di sini.
|   • Non-Staff — kiriman sendiri; balasan SH/DH muncul menjorok di bawah
|                 kutipannya, membentuk percakapan dua giliran.
--}}
{{-- $mendatar: kutipan disusun BERDAMPINGAN (2 kolom), bukan bertumpuk.
     Dipakai saat kartunya menempati 70% lebar dashboard — kalimat butuh lebar,
     dan menumpuknya di kolom sempit membuat tiap kutipan patah jadi 4–5 baris
     sehingga kartunya memanjang jauh melewati tetangganya. --}}
@php
    $sayaNonStaff = $user->jabatan === \App\Models\User::JABATAN_STAFF;
    $mendatar = $mendatar ?? false;

    /*
    | TIGA keadaan, tiga tata letak. Yang dijaga SAMA di ketiganya adalah
    | DIMENSI KARTUNYA (permintaan pemilik) — yang berubah hanya isinya.
    |
    |   0 kutipan   → keterangan kosong, dipusatkan di tengah kartu. Dulu ia
    |                 menempel di atas dan menyisakan ruang menganga di bawahnya.
    |   1–2 kutipan → SATU kolom, dipusatkan tegak. Kalimatnya melebar mengisi
    |                 kartu alih-alih meninggalkan sel kanan kosong — itulah
    |                 ruang kosong yang dikeluhkan.
    |   3+ kutipan  → dua kolom, rapat ke atas, dan MENGGULUNG di dalam kartu
    |                 (lihat `.pp-kutip-tubuh`) supaya kartunya tak pernah
    |                 memanjang melewati tetangganya.
    |
    | `align-content-start` pada keadaan ketiga disengaja: bawaan `.row` adalah
    | `stretch`, dan itu menarik kotak kutipan (`height:100%`) menjadi setinggi
    | sisa kartu — satu kalimat pendek dalam kotak setinggi telapak tangan.
    */
    $jumlah = $masukanWidget->count();
    $sedikit = $jumlah > 0 && $jumlah < 3;
    $kisi = $mendatar
        ? 'row g-3 '.($sedikit ? 'row-cols-1 align-content-center' : 'row-cols-1 row-cols-md-2 align-content-start')
        : '';
@endphp

<div class="card border-0 shadow-sm {{ $mendatar ? 'h-100' : 'mt-3' }}">
    <div class="card-header bg-transparent pb-0 d-flex justify-content-between align-items-start">
        <div>
            <h6 class="fw-bold mb-0">{{ $sayaNonStaff ? 'Masukan Saya' : 'Masukan Lapangan' }}</h6>
            <p class="text-sm text-muted mb-0">
                <i class="bi bi-chat-quote"></i>
                {{ $sayaNonStaff ? 'kiriman Anda & balasannya' : 'belum ditindak' }}
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($jumlah)
                <span class="badge-soft badge-soft-primary">{{ $jumlah }}</span>
            @endif
            {{-- Kartu ini tetap ada (Fase C, D5); yang berubah cuma bahwa kini
                 ADA halaman penuhnya untuk dituju. --}}
            @if (! $sayaNonStaff)
                <a href="{{ route('log.masukan') }}" class="btn btn-sm btn-outline-secondary">
                    Lihat semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @endif
        </div>
    </div>

    @if ($jumlah === 0)
        {{-- Keadaan kosong: dipusatkan MENDATAR & TEGAK. Ia satu-satunya isi
             kartu, jadi ia yang menentukan rupanya — bukan sisa ruang. --}}
        <div class="card-body pt-3 d-flex flex-column align-items-center justify-content-center text-center text-muted">
            <i class="bi bi-chat-left-dots fs-3 mb-2"></i>
            @if ($sayaNonStaff)
                <div class="text-sm">Anda belum mengirim masukan.</div>
                <div class="text-xs">Buka <a href="{{ route('documents.published') }}" class="text-decoration-none">Dokumen Berlaku</a> lalu pilih Beri Masukan.</div>
            @else
                <div class="text-sm">Tak ada masukan yang menunggu.</div>
                <div class="text-xs">Masukan baru dari lapangan muncul di sini.</div>
            @endif
        </div>
    @else
        <div class="card-body pt-3 pp-kutip-tubuh {{ $kisi }}">
            @foreach ($masukanWidget as $m)
                @php
                    $tautan = $m->document ? route('documents.show', $m->document) : null;
                    $umur = $m->created_at?->diffForHumans(short: true);
                @endphp
                @if ($mendatar)<div class="col">@endif
                <div class="pp-kutip {{ $mendatar ? 'pp-kutip-kotak' : '' }}">
                    @if ($tautan && ! $sayaNonStaff)
                        <a href="{{ $tautan }}" class="pp-kutip-isi text-decoration-none">{{ $m->isi }}</a>
                    @else
                        <div class="pp-kutip-isi">{{ $m->isi }}</div>
                    @endif

                    <div class="pp-kutip-atribusi">
                        @unless ($sayaNonStaff)
                            <span class="fw-semibold">{{ $m->user->name ?? '—' }}</span> ·
                        @endunless
                        <span class="font-monospace">{{ $m->document?->displayNumber() ?? '—' }}</span>
                        @if ($umur) · {{ $umur }} @endif
                    </div>

                    @if ($sayaNonStaff)
                        <div class="mt-1">
                            <span class="badge-soft fw-normal">{{ $m->statusLabel() }}</span>
                        </div>
                        @if (filled($m->balasan))
                            <div class="pp-kutip-balasan">
                                <i class="bi bi-reply"></i> {{ $m->balasan }}
                            </div>
                        @endif
                    @endif
                </div>
                @if ($mendatar)</div>@endif
            @endforeach
        </div>
    @endif
</div>
