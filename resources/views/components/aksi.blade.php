{{--
| Pembungkus kolom Aksi: satu tombol yang membuka MENU AKSI menurun.
|
| Ada karena dua daftar sudah memuat enam tombol per baris dan tabelnya harus
| digeser ke samping untuk mencapainya.
|
| Menunya melayang dengan `position: fixed` dan koordinatnya dihitung saat
| dibuka. Itu BUKAN kerumitan yang bisa dipangkas: `.table-responsive`
| ber-`overflow-x: auto`, dan sumbu yang tersisa ikut terpotong — menu
| ber-`position: absolute` di dalam <td> akan terpangkas tepi bawah kartu tepat
| pada baris terakhir, yaitu baris yang paling sering dipakai. Yang `fixed` tak
| mengenal pemotongan itu. Menu juga membalik ke ATAS bila ruang di bawahnya
| kurang, dan menutup sendiri saat halaman/tabel digulir (koordinatnya beku).
|
| Slotnya diisi tombol apa adanya oleh pemanggil. SENGAJA tak menerima array
| konfigurasi: syarat tampil tiap aksi berbeda-beda per halaman, dan
| memindahkannya ke sini hanya menukar Blade yang terbaca dengan array yang
| harus ditafsirkan. Pakailah varian `btn-outline-*` — CSS di layouts/app
| melucuti bingkainya supaya seluruh baris menu rata kiri dan sewarna.
|
| `click.outside` dipasang pada PEMBUNGKUS, bukan pada menunya. Di menu, tombol
| pemicu terhitung "di luar": satu klik akan membuka lalu langsung menutupnya
| lagi, dan menunya tak pernah kelihatan.
--}}
@props(['label' => 'Aksi'])
{{-- Baris yang tak punya satu pun aksi (mis. SH/DH di Dokumen Berlaku, yang
     sejak Fase C hanya membaca) tak boleh mendapat tombol yang membuka menu
     kosong. Diperiksa dari SLOT-nya, bukan dengan mengulang seluruh syarat
     `@if` pemanggil di sini — dua daftar syarat yang harus sepakat adalah dua
     daftar yang suatu hari tidak sepakat. --}}
@if ($slot->isNotEmpty())
{{-- `bukaAksi`, bukan `buka`: obsolete.blade.php memakai `buka` pada <tbody>
     untuk membuka baris versi. Nama yang sama akan dibayangi Alpine di dalam
     sini — jalan, tapi menyesatkan saat dibaca ulang. --}}
<div class="pp-aksi" x-data="{
        bukaAksi: false, atas: -9999, kanan: 0,
        alihkan() {
            this.bukaAksi = ! this.bukaAksi;
            if (! this.bukaAksi) return;
            // Sesudah render: tinggi menunya baru terbaca, dan tinggi itulah
            // yang menentukan ia turun atau membalik ke atas.
            this.$nextTick(() => {
                const p = this.$refs.pemicu.getBoundingClientRect();
                const t = this.$refs.menu.offsetHeight;
                this.kanan = window.innerWidth - p.right;
                this.atas = (p.bottom + t + 8 > window.innerHeight) ? p.top - t - 4 : p.bottom + 4;
            });
        }
     }"
     x-on:click.outside="bukaAksi = false"
     x-on:keydown.escape.window="bukaAksi = false"
     {{-- Capture: event scroll tak menggelembung, jadi tanpa ini gulir DI DALAM
          .table-responsive tak terdengar dan menunya tertinggal di koordinat lama. --}}
     x-on:scroll.window.capture="bukaAksi = false">
    {{-- Titik-titik saja, tanpa tulisan: kolomnya sudah berjudul "Aksi", dan
         label yang diulang di setiap baris hanya melebarkan kolom. Namanya tetap
         terbaca pembaca layar & tooltip lewat aria-label/title. --}}
    <button type="button" class="btn btn-sm btn-outline-secondary pp-aksi-pemicu" x-ref="pemicu"
            x-on:click="alihkan()" :aria-expanded="bukaAksi"
            :class="{ 'active': bukaAksi }"
            title="{{ $label }}" aria-label="{{ $label }} untuk baris ini">
        <i class="bi" :class="bukaAksi ? 'bi-x-lg' : 'bi-three-dots-vertical'"></i>
    </button>
    {{-- Klik pada aksi mana pun ikut menutup menunya: tanpa ini, menu yang
         membuka modal tetap melayang di atas modal itu. --}}
    <div class="pp-aksi-menu" x-ref="menu" x-show="bukaAksi" x-cloak
         x-on:click="bukaAksi = false"
         x-transition.opacity.duration.120ms
         :style="`top:${atas}px; right:${kanan}px`">
        {{ $slot }}
    </div>
</div>
@endif
