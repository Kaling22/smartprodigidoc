{{--
| Badge status dokumen — SATU-SATUNYA tempat status dirupakan.
|
| Pemakaian:
|   @include('partials._badge-status', ['status' => $doc->status])
|   @include('partials._badge-status', ['status' => $doc->status, 'ringkas' => true])
|
| Parameter:
|   $status   string  kunci status (lihat Document::STATUS_META)
|   $ringkas  bool    opsional; true = ikon saja + tooltip, untuk kolom sempit
|
| Bentuknya kini menumpang .badge-soft (layouts/app) — kelas yang sama dengan
| SELURUH label status & chip kode di aplikasi (spec v3 R1). Dulu partial ini
| punya kelas sendiri (.pp-status) karena .badge bawaan dipakai belasan chip
| lain dengan gradasi pekat; setelah semuanya jadi lembut, alasan itu hilang.
|
| Yang tetap tinggal di sini cuma RONA-nya, karena hanya status yang paletnya
| datang dari data (Document::STATUS_META) alih-alih dari daftar tetap di CSS.
--}}
@once
    @push('styles')
        <style>
            /* Empat variabel dititipkan inline oleh partial ini; dua aturan di
               bawah yang memilih pasangan mana yang dipakai. Kalau --soft-bg
               langsung ditulis inline, aturan mode gelap mustahil menang —
               gaya inline mengalahkan selektor apa pun. */
            .badge-soft-status { --soft-bg: var(--sb); --soft-fg: var(--sf); }
            [data-bs-theme="dark"] .badge-soft-status { --soft-bg: var(--sb-d); --soft-fg: var(--sf-d); }
            .badge-soft-ringkas { padding: .35rem .45rem; }
        </style>
    @endpush
@endonce

@php
    $ppMeta = \App\Models\Document::STATUS_META[$status] ?? ['#8392ab', '#67748e', 'bi-question-circle'];
    [$ppC1, $ppC2, $ppIkon] = $ppMeta;
    $ppLabel = \App\Models\Document::STATUS_LABELS[$status] ?? $status;

    // Hex -> rgb, supaya latar tipis bisa dibuat transparan tanpa menyimpan
    // warna yang sama dua kali di STATUS_META.
    [$ppR, $ppG, $ppB] = sscanf($ppC1, '#%02x%02x%02x');

    // Teks mode gelap: warna PERTAMA dicerahkan (campur 55% dengan putih).
    // Sengaja dari --c1, bukan --c2: pada `verifikasi_md` gradasinya teal->navy,
    // dan mencerahkan navy menghasilkan abu-abu yang kehilangan jati diri tealnya.
    $ppTerang = sprintf('#%02x%02x%02x',
        (int) ($ppR + (255 - $ppR) * .55),
        (int) ($ppG + (255 - $ppG) * .55),
        (int) ($ppB + (255 - $ppB) * .55),
    );

    /*
    | Teks mode TERANG.
    |
    | Memakai --c1 apa adanya TIDAK bisa: warna cerah seperti biru muda
    | (#38bdf8) atau kuning (#eab308) di atas latar tint 12% nyaris tak
    | terbaca. Tapi menabelkan "warna teks gelap" satu per satu berarti dua
    | angka yang harus dijaga tetap seiring tiap kali palet berubah.
    |
    | Jadi digelapkan SEPERLUNYA saja, dihitung dari luminansi: warna yang
    | sudah gelap (teal, navy) dibiarkan; yang terlalu terang diturunkan
    | sampai ambang keterbacaan. Otomatis benar untuk warna baru mana pun.
    |
    | Ambang .33 bukan tebakan melainkan hasil ukur: pada .42 `waiting_for_review`
    | berhenti di 4.39:1 dan pada .36 `in_review` di 4.46:1 — dua-duanya tipis di
    | bawah syarat. Dikunci BadgeStatusTest, jadi kalau palet berubah lagi dan
    | ada yang jatuh, tesnya yang memberi tahu, bukan mata.
    |
    | Sejak v3 R1 rumus ini berlaku untuk SEMUA status. Dulu `published`,
    | `rejected` dan `obsolete` lolos dari sini karena dirupakan gradasi pekat
    | berteks putih; ketiganya kini ikut diukur.
    */
    $ppLum = (.2126 * $ppR + .7152 * $ppG + .0722 * $ppB) / 255;
    $ppSkala = $ppLum > .33 ? .33 / $ppLum : 1.0;
    $ppGelap = sprintf('#%02x%02x%02x',
        (int) ($ppR * $ppSkala),
        (int) ($ppG * $ppSkala),
        (int) ($ppB * $ppSkala),
    );

    $ppVars = "--sb:rgba({$ppR},{$ppG},{$ppB},.12)"
        . ";--sf:{$ppGelap}"
        . ";--sb-d:rgba({$ppR},{$ppG},{$ppB},.22)"
        . ";--sf-d:{$ppTerang}";
@endphp

<span class="badge-soft badge-soft-status{{ ($ringkas ?? false) ? ' badge-soft-ringkas' : '' }}"
      style="{{ $ppVars }}"
      @if ($ringkas ?? false) title="{{ $ppLabel }}" @endif>
    <i class="bi {{ $ppIkon }}"></i>
    @unless ($ringkas ?? false){{ $ppLabel }}@endunless
</span>
