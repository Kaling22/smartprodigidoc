# SP — Standar Parameter

## Apa dokumen ini
SP menetapkan PARAMETER dan NILAI STANDAR hasil kerja/produksi beserta cara
mengukurnya dan tindakan bila menyimpang. Strukturnya mengikuti SOP (tujuan,
ruang lingkup, referensi, definisi, aktivitas, lampiran) tanpa flowchart, tetapi
titik beratnya bukan alur kerja melainkan ANGKA STANDARNYA.

## Bagian wajib
- `tujuan`, `ruang_lingkup`, `referensi`, `definisi` — sama seperti SOP.
- `aktivitas` — parameter, nilai standar (batas/target), cara & alat ukur,
  frekuensi pengukuran, dan tindakan bila di luar batas; tiap baris ber-PIC.
- `lampiran` — form pencatatan atau tabel pendukung bila ada.

## Daftar periksa audit
- Apakah setiap parameter punya nilai batas (min/maks/target) DAN satuan?
  Parameter tanpa nilai batas atau tanpa satuan = `critical`.
- Apakah tindakan bila parameter di luar batas dijelaskan (siapa, apa)? Tidak
  ada = `major`.
- Apakah metode dan alat ukur disebut? Tidak ada = `minor`.
- Apakah frekuensi atau waktu pengukuran disebut? Tidak ada = `minor`.
- Apakah nilai standar konsisten antar bagian? Dua angka berbeda untuk parameter
  yang sama = `major`.
- Apakah nilai standar masuk akal dan tidak saling meniadakan (mis. nilai
  minimum lebih besar daripada maksimum) = `critical`.
- Apakah setiap baris `aktivitas` menyebut PIC? Kosong = `major`.
- Sisanya mengikuti daftar periksa SOP: referensi relevan, definisi lengkap,
  tujuan tidak menyalin ruang lingkup.

## Contoh temuan per tingkat keparahan
- critical: Parameter "tekanan kerja" ditulis tanpa nilai batas maupun satuan,
  sehingga standar ini tidak dapat dipakai menilai apa pun.
- major: Tidak dijelaskan apa yang harus dilakukan dan oleh siapa ketika hasil
  pengukuran melewati batas atas.
- minor: Alat ukur untuk parameter kekentalan tidak disebutkan.
- info: Rentang toleransi ditulis lengkap dengan targetnya, memudahkan
  pengendalian proses.
