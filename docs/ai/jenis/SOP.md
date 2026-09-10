# SOP — Standard Operating Procedure

## Apa dokumen ini
SOP adalah prosedur operasional baku untuk satu proses kerja yang melibatkan
lebih dari satu peran. Isinya menjawab: proses apa, sampai mana batasnya, siapa
mengerjakan apa, dan dengan urutan bagaimana. SOP mengatur ALUR PROSES — bukan
cara teknis satu pekerjaan (itu wilayah IK).

## Bagian wajib
- `tujuan` — mengapa prosedur ini ada, hasil akhir yang ingin dicapai.
- `ruang_lingkup` — proses/area/departemen yang dicakup DAN yang tidak.
- `referensi` — standar/regulasi/dokumen internal yang benar-benar dipakai.
- `definisi` — istilah teknis & singkatan yang muncul di dokumen.
- `flowchart` — gambar alur; keterangannya harus sejalan dengan `aktivitas`.
- `aktivitas` — langkah kerja berurutan; tiap baris: sub judul, deskripsi, PIC.
- `lampiran` — rujukan ke dokumen lain (form, IK, SP) bila ada.

## Daftar periksa audit
- Apakah setiap butir `aktivitas` menyebut PIC? Langkah tanpa PIC = `major`.
- Apakah urutan `aktivitas` logis? Langkah yang mengacu hasil langkah SESUDAHNYA
  atau melompati langkah antara = `major`.
- Apakah ada langkah yang hilang di antara dua langkah yang ada (mis. tidak ada
  verifikasi atau serah terima yang jelas dibutuhkan) = `major`.
- Apakah `referensi` relevan dengan isi, bukan daftar tempel? Referensi generik
  atau tidak berhubungan = `minor`.
- Apakah istilah teknis/singkatan di `aktivitas` ada di `definisi`? Tidak ada =
  `minor`.
- Apakah `tujuan` dan `ruang_lingkup` saling menyalin? Bila ya = `minor`.
- Apakah `ruang_lingkup` menyebut batas yang tegas (departemen/area/kondisi)?
  Bila hanya kalimat umum = `minor`.
- Apakah keterangan `flowchart` sejalan dengan urutan `aktivitas`? Bertentangan
  = `major`.
- Apakah ada langkah berisiko keselamatan yang tidak menyebut pengendalian dan
  tidak merujuk JSA/IK = `major`.
- Apakah tanggung jawab bertabrakan (dua PIC untuk satu keputusan, atau PIC
  menyetujui pekerjaannya sendiri) = `major`.

## Contoh temuan per tingkat keparahan
- critical: Prosedur menyuruh mengoperasikan alat berat tanpa satu pun langkah
  pemeriksaan pra-operasi maupun rujukan JSA.
- major: Langkah 4 "lakukan perbaikan" tidak menyebut PIC, padahal seluruh
  langkah lain menyebutnya.
- minor: Istilah "P2H" dipakai di langkah 2 tetapi tidak ada di bab DEFINISI.
- info: Pembagian sub judul per tahap (Persiapan/Pelaksanaan/Penutup) membuat
  prosedur mudah diikuti.
