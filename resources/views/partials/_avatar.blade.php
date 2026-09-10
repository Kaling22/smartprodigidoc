{{--
| AVATAR bulat — satu bentuk untuk seluruh dashboard (kolom Pembuat pada
| Perjalanan Dokumen, tumpukan Anggota pada Distribusi).
|
| FOTO dulu; yang belum berfoto dapat glif orang Bootstrap Icons, BUKAN inisial
| (spec v2 W4) — perlakuan yang sama dengan avatar topbar, jadi seseorang tampak
| serupa di mana pun ia muncul. Lingkarannya tak pernah kosong.
|
| Pemakaian:
|   @include('partials._avatar', ['orang' => $user])
|   <div class="pp-avatar-grup"> …beberapa @include… </div>   ← tumpukan
|
| @param \App\Models\User|null $orang
| @param string|null $judul  teks tooltip; bawaannya nama orang itu
--}}
@php
    $orang = $orang ?? null;
    $judul = $judul ?? ($orang?->name ?? 'Tidak diketahui');
@endphp

<span class="pp-avatar" title="{{ $judul }}">
    @if ($orang?->photoUrl())
        <img src="{{ $orang->photoUrl() }}" alt="{{ $orang->name }}">
    @else
        <i class="bi bi-person-fill"></i>
    @endif
</span>

@once
    @push('styles')<style>
        .pp-avatar {
            flex: 0 0 auto; width: 34px; height: 34px; border-radius: 50%; overflow: hidden;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--bs-tertiary-bg); color: #8392ab; font-size: .95rem; line-height: 1;
        }
        .pp-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* Tumpukan: saling menindih, tiap wajah dibatasi cincin sewarna kartu
           supaya batas antar-wajah tetap terbaca di terang maupun gelap. */
        .pp-avatar-grup { display: flex; }
        .pp-avatar-grup .pp-avatar {
            width: 28px; height: 28px; font-size: .8rem;
            margin-left: -9px; border: 2px solid var(--bs-card-bg);
        }
        .pp-avatar-grup .pp-avatar:first-child { margin-left: 0; }
        .pp-avatar-sisa { background: var(--bs-secondary-bg); font-size: .62rem; font-weight: 600; }
    </style>@endpush
@endonce
