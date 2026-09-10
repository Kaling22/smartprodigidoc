{{--
 | Judul kolom yang bisa diurutkan.
 |
 | Pengurutannya di SERVER, bukan di peramban: tabelnya berhalaman, dan
 | mengurutkan di sisi klien hanya membalik 15 baris yang kebetulan sedang
 | tampil — bukan seluruh dokumen.
 |
 | Seluruh query string lain (cari, jenis, departemen, halaman) ikut dibawa,
 | jadi menekan judul kolom tak pernah menghapus penyaringan yang sedang aktif.
 |
 | @param string $kolom  kunci di Document::KOLOM_URUT ('nomor' | 'revisi')
 | @param string $label  teks judul kolom
 | @param string $kelas  kelas tambahan untuk <th> (mis. 'text-center')
--}}
@php
    $aktif = request('sort') === $kolom;
    // Kolom aktif membalik arah; kolom lain selalu mulai dari menaik.
    $arahBerikut = $aktif && request('dir') !== 'desc' ? 'desc' : 'asc';

    // `page` sengaja DIBUANG: urutan baru berarti halaman 3 memuat baris yang
    // sama sekali berbeda, jadi bertahan di sana justru membingungkan.
    $tautan = request()->fullUrlWithQuery(['sort' => $kolom, 'dir' => $arahBerikut, 'page' => null]);
@endphp
<th class="{{ $kelas ?? '' }}">
    <a href="{{ $tautan }}" class="pp-urut {{ $aktif ? 'pp-urut-aktif' : '' }}"
       title="Urutkan {{ $arahBerikut === 'asc' ? 'menaik' : 'menurun' }}">
        {{ $label }}
        {{-- Dua caret bertumpuk seperti pengurutan tabel Windows: yang aktif
             pekat, pasangannya redup — arah yang SEDANG berlaku terbaca tanpa
             perlu mengingat klik terakhir. --}}
        <span class="pp-urut-caret" aria-hidden="true">
            <i class="bi bi-caret-up-fill {{ $aktif && request('dir') !== 'desc' ? 'on' : '' }}"></i>
            <i class="bi bi-caret-down-fill {{ $aktif && request('dir') === 'desc' ? 'on' : '' }}"></i>
        </span>
    </a>
</th>
