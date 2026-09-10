<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Management Development (MD) — peninjau kedua
    |--------------------------------------------------------------------------
    |
    | MD memeriksa SISTEMATIKA PENULISAN (typo, salah tulis, kalimat tak sesuai)
    | sesudah SH/DH memeriksa SUBSTANSI. Ia MENJEMBATANI menuju PJO — bukan
    | menyetujui.
    |
    | Karena perannya menjembatani ke PJO, ia hanya masuk akal bagi jenis
    | dokumen yang persetujuannya memang di PJO. Saat ini: SOP.
    |
    | IK, SP, dan JSA BELUM masuk. Menambahkannya adalah keputusan pimpinan,
    | bukan keputusan teknis — karena itu daftarnya sengaja ditaruh di berkas
    | konfigurasi yang harus disunting sadar, bukan saklar di layar yang bisa
    | tertekan tanpa sengaja. Setelah pimpinan menyetujui, cukup tambahkan kode
    | jenisnya di sini; tak ada kode lain yang perlu diubah.
    |
    | Berlaku untuk SELURUH departemen.
    |
    */

    'md' => [
        'jenis_wajib' => ['SOP'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ketersediaan peninjau (FITUR-BARU-v4 §6)
    |--------------------------------------------------------------------------
    |
    | `batas_dokumen` — berapa dokumen berjalan (menunggu ditinjau + dalam
    | peninjauan) yang boleh dipegang SATU peninjau. Sudah mencapai batas =
    | tak bisa dipilih lagi.
    |
    | SEKARANG 0 = TANPA BATAS (keputusan pemilik A2, PLAN-REVISI-v6 Fase A).
    | Kuota 2 dokumen menyumbat kerja: departemen dengan satu Section Head
    | langsung buntu begitu ia memegang dua dokumen. Penggantinya bukan angka
    | lain melainkan SINYAL: beban dicetak sebagai angka di papan pemilihan,
    | jadi GL memutuskan sendiri siapa yang masih pantas ditugasi.
    |
    | Kuncinya SENGAJA tidak dihapus. Seluruh pembatasan (papan, penjaga saat
    | pilihan berubah, penolakan saat kirim) masih utuh di kode dan hidup lagi
    | begitu angkanya dinaikkan — satu suntingan, nol kode, sebagai rem darurat.
    |
    | `hari_pita` — panjang pita ketersediaan (hari ke depan) pada papan
    | pemilihan peninjau dan kartu dashboard.
    |
    | `ambang` — batas warna pil beban di papan pemilihan: 0..padat-1 = aman
    | (netral), padat..sibuk-1 = padat (kuning), >= sibuk = sibuk (merah).
    | Berlaku HANYA bagi jenis dokumen yang terdaftar di `ambang.jenis`; jenis
    | lain mencetak angka polos. Saat ini hanya JSA — merekalah yang menumpuk
    | di segelintir GL SHE & Plant.
    |
    | Ditaruh di sini, bukan dipatok di kode, karena "berapa dokumen sudah
    | terasa padat" adalah pertimbangan lapangan yang berubah seiring jumlah
    | peninjau. Pemilik menyetelnya tanpa menyentuh kode maupun test; menambah
    | SP kelak pun cukup satu kata di daftar `jenis`.
    |
    */

    'peninjau' => [
        'batas_dokumen' => 0,
        'hari_pita' => 14,
        'ambang' => ['jenis' => ['JSA'], 'padat' => 3, 'sibuk' => 5],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    |
    | `masukan_widget` — berapa kutipan masukan lapangan yang dimuat kartu
    | "Masukan Lapangan" / "Masukan Saya".
    |
    | Dulu dipatok 4 di dalam controller. Angka itu bukan sekadar terlalu kecil:
    | dengan grid dua kolom, empat kutipan hanya mengisi dua baris, sehingga
    | kartunya TAK PERNAH BISA MELUAP — dan permintaan pemilik "kalau banyak,
    | buat agar bisa digulir" mustahil dipenuhi selama angkanya 4.
    |
    | Ditaruh di sini, bukan dipatok di kode, karena berapa banyak masukan yang
    | nyaman dibaca sekali lihat adalah pertimbangan pemakaian, bukan teknis.
    | Kartunya menggulung sendiri, jadi angka besar tidak merusak tata letak —
    | yang dibatasi angka ini adalah ONGKOS QUERY, bukan tampilannya.
    |
    */

    'dashboard' => [
        'masukan_widget' => 20,
    ],

];
