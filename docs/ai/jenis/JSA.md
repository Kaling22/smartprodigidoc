# JSA — Job Safety Analysis

## Apa dokumen ini
JSA menguraikan satu pekerjaan menjadi langkah-langkah kerja, lalu untuk tiap
langkah mengidentifikasi BAHAYA/RISIKO dan TINDAKAN PENGENDALIAN-nya, ditambah
APD dan peralatan yang dipakai. Nilai dokumen ini ada pada kelengkapan bahaya
dan kememadaian pengendalian — bukan pada kerapian kalimatnya.

## Bagian wajib
- `lokasi_kerja` — lokasi spesifik pekerjaan.
- `apd` — daftar APD yang digunakan.
- `tools` — daftar peralatan yang digunakan.
- `analisa` — langkah kerja → bahaya/risiko → pengendalian (boleh lebih dari
  satu pengendalian per bahaya).

## Daftar periksa audit

### A. Kesesuaian pekerjaan ↔ langkah kerja
- Apakah langkah-langkah di `analisa` benar-benar menguraikan pekerjaan yang
  tertulis di judul dan `lokasi_kerja`? Langkah yang tak berhubungan dengan
  pekerjaan itu = `major`.
- Apakah pekerjaannya terurai dari awal sampai selesai (persiapan →
  pelaksanaan → penyelesaian/housekeeping)? Tahap yang hilang = `minor`;
  hilangnya pekerjaan INTI (mis. JSA "pergantian AP" tanpa langkah penggantian
  itu sendiri) = `major`.
- Apakah satu langkah memuat beberapa pekerjaan sekaligus sehingga bahayanya
  tak bisa dipetakan? = `minor`.

### B. Kesesuaian langkah ↔ bahaya ↔ pengendalian
- Apakah bahayanya memang TIMBUL DARI langkah itu, bukan bahaya umum yang
  ditempel ke semua langkah? Tidak cocok = `major`.
- Apakah tiap pengendalian benar-benar MENGENDALIKAN bahaya di atasnya, bukan
  mengulang bunyi langkah kerja atau sekadar imbauan ("hati-hati", "fokus")?
  Tidak mengendalikan = `major`.
- Apakah setiap `bahaya` punya MINIMAL SATU `pengendalian`? Tidak ada =
  `critical`.

### C. Kememadaian & kelengkapan
- Hirarki kontrol (eliminasi → substitusi → rekayasa → administrasi → APD):
  bahaya berisiko tinggi yang pengendaliannya HANYA "gunakan APD" = `major`.
  Sebutkan di `suggestion` tingkat hirarki mana yang belum dipertimbangkan.
- Apakah bahaya mencakup unsafe ACTION (perilaku) dan unsafe CONDITION (keadaan
  alat/lingkungan)? Hanya salah satu untuk pekerjaan berisiko = `minor`.
- Apakah APD yang disebut di dalam `analisa` ada di daftar `apd`? Tidak ada =
  `minor`. Begitu pula sebaliknya: APD terdaftar tetapi tak pernah dipakai di
  langkah mana pun = `minor`.
- Apakah alat yang dipakai di langkah kerja ada di `tools`? Tidak ada = `minor`.
- Apakah ada langkah kerja tanpa bahaya sama sekali? Patut dicurigai = `info`,
  kecuali langkahnya memang administratif (mis. mengisi izin kerja).
- Apakah `lokasi_kerja` spesifik? Kosong atau generik ("area kerja") = `minor`.
- Apakah urutan langkah mencakup persiapan, pelaksanaan, DAN penyelesaian
  (housekeeping/serah terima)? Berhenti di tengah = `minor`.
- Apakah pengendaliannya dapat dijalankan dan terukur ("pasang barikade radius
  3 m") atau sekadar imbauan ("hati-hati", "fokus")? Imbauan kosong = `major`.

## Contoh temuan per tingkat keparahan
- critical: Bahaya "terjatuh dari ketinggian" pada langkah 2 tidak memiliki satu
  pun tindakan pengendalian.
- major: Bahaya "terpapar debu silika" hanya dikendalikan dengan masker (APD),
  padahal pengendalian rekayasa (penyiraman atau ventilasi lokal) belum
  dipertimbangkan.
- minor: "Body harness" disebut pada pengendalian langkah 3 tetapi tidak
  tercantum di daftar APD.
- info: Langkah "mengisi izin kerja" tidak memuat bahaya — wajar karena bersifat
  administratif.
