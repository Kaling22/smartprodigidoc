# IK — Instruksi Kerja

## Apa dokumen ini
IK adalah petunjuk teknis RINCI untuk SATU pekerjaan, biasanya dikerjakan satu
orang atau satu regu. IK harus lebih rinci daripada SOP: pembacanya menjalankan
pekerjaan sambil membaca. Schema IK sengaja ringkas — hanya tabel Aktivitas &
Tanggung Jawab, lalu langsung halaman pengesahan.

## Bagian wajib
- `aktivitas` — satu-satunya bab isi. Tiap baris: sub judul (tahap), deskripsi
  langkah teknis, dan PIC. Alat, bahan, parameter kerja, dan hasil yang
  diharapkan ditulis di dalam deskripsi.

## Daftar periksa audit
- Apakah langkahnya benar-benar setingkat instruksi kerja? Langkah selebar SOP
  ("lakukan pemeriksaan", "pastikan sesuai standar") tanpa cara melakukannya =
  `major`.
- Apakah parameter kerja (torsi, tekanan, suhu, arus, durasi, jarak) bernilai
  konkret DENGAN satuan? Parameter tanpa angka/satuan = `major`.
- Apakah alat dan bahan yang dibutuhkan disebut pada langkah yang memakainya?
  Tidak disebut = `minor`.
- Apakah hasil yang diharapkan / kriteria selesai tiap langkah terbaca? Tidak
  ada = `minor`.
- Apakah setiap baris `aktivitas` punya PIC? Kosong = `major`.
- Apakah urutan langkah bisa dijalankan apa adanya, tanpa pengetahuan yang tidak
  tertulis? Ada lompatan = `major`.
- Apakah langkah berbahaya menyebut APD atau pengendaliannya? Tidak = `major`.
- Apakah ada langkah yang menyuruh "lihat SOP" untuk hal yang justru menjadi
  inti IK ini = `minor`.

## Contoh temuan per tingkat keparahan
- critical: Instruksi penggantian komponen bertegangan tidak menyebut isolasi
  sumber daya (LOTO) sama sekali.
- major: Langkah 3 menyebut "kencangkan baut sesuai standar" tanpa nilai torsi
  dan satuannya.
- minor: Alat ukur yang dipakai pada langkah 5 tidak disebutkan namanya.
- info: Tiap langkah sudah menyebut kriteria selesai, memudahkan verifikasi.
