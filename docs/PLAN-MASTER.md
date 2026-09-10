# PLAN-MASTER — Pemfasean Butir 0–8

> Status: **DRAF**. Sumber: `docs/master.md`. Belum ada satu baris kode pun ditulis.
> Berkas ini sengaja bisa dipecah jadi 3 berkas terpisah (`PLAN-FASE-A/B/C.md`)
> tanpa kehilangan makna — tiap bagian membawa prasyarat & konteksnya sendiri.

## Keputusan pemilik — TERKUNCI (sesi 2026-08-11)

| # | Keputusan | Akibatnya pada rencana |
|---|---|---|
| A1 | Hapus **berkas PDF + dokumen Berlaku** saja. Bukan user, bukan audit log | **`smartpro:renew` BUKAN alatnya** — lihat §A.3 langkah 6 yang ditulis ulang |
| A3 | Folder yatim `pdf-singgahan` **dibiarkan** | — |
| A4 | Batas kutipan widget = default (20, lewat config) | — |
| B1 | Tiap submenu Informasi memakai **kolomnya sendiri**, beda-beda | ✅ **terbukti benar** oleh skema lama — lihat §B.4 |
| B2 | Yang mengunggah Informasi: **Admin saja** dulu | Tak perlu izin baru bertingkat |
| B3 | Revisi Informasi = **unggah berkas baru**; yang lama disimpan sebagai **riwayat** | Butuh kolom status + pengelompokan per nomor |
| B4+B6 | Kolom revisi **dipotong sampai revisi tertinggi yang benar-benar ada**; tiap Edisi punya rev 0–4 | Aturan turunannya di §B.5 — **perlu konfirmasi** (blocking #3) |
| B5 | Keluaran **`.xls`**, yang paling mudah, **asal bukan CSV** | ✅ pertahankan HTML-`.xls` yang sudah ada, nol dependensi baru |
| B7 | Pengunggah dokumen lama: **GL dan Admin**, langsung Berlaku + audit log | — |
| C1 | `no_revisi` naik saat revisi **DIKIRIM**; nomor halaman terisi otomatis dari **cek perubahan field** | Pemindahan dari `requestRevision()` ke `submit()` — lihat §C.2 |
| C2 | **1 perubahan = 1 baris**; bila memakan banyak halaman ditulis **`x - y`** | — |
| C3 | Mobile di `C:\xamppnew\htdocs\SmartPro-mobile`; cukup pastikan **terhubung** | ✅ jauh lebih ringan dari dugaan — lihat §C.3 |
| C4 | Hosting: **Hostinger** | Implikasi shared hosting di §C.3 |
| C5 | Sumber lama: `C:\xamppnew\htdocs\Sistem_managemen_dokumen_prosedur` + `u805399352_db_prowork.sql` | ✅ sudah dibaca — lihat "Temuan" di bawah |
| C6 | Informasi **belum** dibuka ke mobile | — |
| B3-lanjut | Kolom induk: **hanya edisi TERAKHIR yang dipotong**, berhenti di revisi tempat dokumen itu berakhir. Contoh: berakhir di Edisi 2 Revisi 3 → berhenti di revisi 3 | ✅ **blocking #3 TERTUTUP** — aturan turunan di §B.5 terkonfirmasi |
| BARU-1 | `tb_fk_*` = **Formulir Kerja**, `tb_px_*` = **Prosedur External**. Keduanya **ditambahkan sebagai jenis dokumen** di tiap departemen; GL mendapat **2 sub-menu baru (FK & PX)** di menu "Dokumen Baru" | Lingkup BARU — lihat "Jenis dokumen baru" di bawah |
| BARU-2 | Menu **Informasi** jadi **menu sendiri**, diletakkan **di atas "Informasi Akun"** | Menggantikan rencana lama yang menaruhnya di tengah daftar (§B.4) |
| BARU-3 | Dokumen yang akan diunggah: **tunggu konfirmasi**; siapkan dulu yang perlu disiapkan | Butir 8 tetap terkunci; yang boleh dikerjakan hanya persiapannya |

## Jenis dokumen baru: FK (Formulir Kerja) & PX (Prosedur External)

Terbaca dari dump lama — bentuknya **identik** dengan SOP/IK/SP/JSA di sistem lama:

| Jenis | Tabel | Kolom | Baris |
|---|---|---|---|
| FK — Formulir Kerja | `tb_fk_{8 dept}` | `no_dokumen`, `judul_fk`, `file_fk`, `edisi`, `revisi`, `tanggal_efektif` | **39** |
| PX — Prosedur External | `tb_px_{8 dept}` | `no_dokumen`, `judul_px`, `file_px`, `edisi`, `revisi`, `tanggal_efektif` | **147** |

Total dokumen mutu yang akan diimpor naik: 950 → **1.136**. Ditambah 145 Informasi = **1.281**.

> ### ✅ KEPUTUSAN — FK & PX **tanpa schema JSON**
>
> Pemilik menetapkan: FK dan PX **tidak memakai wizard berbab**. Formulirnya cukup
> **nomor, judul, edisi, revisi, tanggal efektif, dan unggah berkas**.
>
> Artinya keduanya **berbentuk sama persis dengan dokumen arsip butir 0** — dan itu
> menghapus seluruh hambatan: tak ada schema yang perlu dikarang, tak ada contoh
> dokumen yang perlu ditunggu, tak ada template cetak baru (PDF-nya adalah berkas
> yang diunggah, bukan hasil render DomPDF).
>
> **Konsekuensi arsitektur yang menguntungkan:** formulir unggah butir 0 dan formulir
> FK/PX adalah **satu formulir yang sama**. Yang membedakan hanya `DocumentType`-nya.
> Jadi FK & PX **tidak menambah pekerjaan** di luar dua baris seeder + dua sub-menu —
> asalkan dikerjakan **sesudah** butir 0, bukan sebelum.
>
> Penanda jenisnya: `DocumentType` ber-`class` unggahan (tanpa `schema_json` berbab),
> sehingga wizard, langkah, dan pratinjau tak pernah dipanggil untuknya.
>
> **Blocking #3 TERTUTUP.** Yang tersisa hanya kode jenisnya (blocking #5).

> ### ✅ SELESAI 2026-08-13 — FK & PX terpasang (343 test hijau, naik dari 339)
>
> Persis seperti yang diramalkan di atas: **nol jalur baru**, keduanya menumpang
> `DocumentArsipController` yang sudah ada.
>
> - Migration `…_tambah_kelas_unggahan_document_types` — ENUM `document_types.class`
>   + nilai `unggahan`. Penandanya kolom `class`, **bukan** "schema_json kosong":
>   jenis yang masih MENUNGGU contoh (dokumen independen) juga berschema kosong dan
>   harus tetap tampil "belum tersedia", bukan diam-diam jadi formulir unggah.
> - `DocumentTypeSeeder` — `FK` (Formulir Kerja) & `PX` (Prosedur External),
>   `class: unggahan`, `schema_json: []` (kolomnya NOT NULL dan
>   `SchemaService::__construct` bertipe `array`).
> - `DocumentType::isUnggahan()` + `DocumentType::kode()`. `kode()` menggantikan
>   **empat** larik `['SOP','IK','SP','JSA']` yang tersebar di
>   `DocumentExportController` (konstanta, dihapus), `DashboardController`, dan dua
>   saringan "Jenis" — tanpa itu FK/PX ada di menu tapi ekspornya 404.
> - `DocumentController::create()` melewatkan jenis unggahan dari halaman
>   "belum tersedia"; `store()` (wizard) **menolaknya 422** — draft berbab untuk
>   jenis tanpa bab adalah dokumen yang tak akan pernah bisa diisi maupun dicetak.
> - `documents/create` — saklar dikunci menyala & disembunyikan saat `$unggahanSaja`.
>   Formulirnya SAMA PERSIS dengan dokumen arsip: nomor, judul, edisi, revisi,
>   tanggal efektif, berkas.
> - `tests/Feature/JenisUnggahanTest.php` **baru** — 4 test.
>
> **Yang TIDAK dikerjakan, disengaja:** impor 39 baris FK + 147 baris PX. Itu butir 8,
> dan berkas PDF-nya masih di server lama (**blocking #7** belum tertutup).

> ### ⚠ Catatan lama — dua pekerjaan berbeda (sudah tak berlaku setelah keputusan di atas)
>
> **(1) FK & PX sebagai dokumen ARSIP — bisa sekarang, nol bahan baru.**
> Di sistem lama keduanya hanya **PDF + metadata**, persis seperti SOP/IK/SP/JSA di
> sana. Jadi mendaftarkannya lewat jalur butir 0 (unggah PDF + nomor/judul/edisi/
> revisi/tanggal) **tidak memerlukan apa pun yang belum ada**. Cukup dua baris
> `DocumentType` baru + kode `FK`/`PX` untuk penomoran & daftar induk.
>
> **(2) FK & PX sebagai dokumen yang DISUSUN di wizard — BELUM bisa.**
> Sub-menu "Buat Dokumen → FK / PX" berarti GL mengisi **formulir berbab**, dan itu
> menuntut **schema JSON per jenis** (CLAUDE.md §7: schema adalah sumber tunggal
> form/preview/PDF). Schema FK dan PX **tidak ada di mana pun** — dump lama tak
> menyimpannya karena di sana isinya memang cuma berkas PDF.
>
> **CLAUDE.md §4 melarang saya mengarangnya:** *"JANGAN mengarang struktur dokumen
> independen — contoh belum ada, TUNGGU dari saya."* → **blocking #3 (baru)**.
>
> Yang saya butuhkan: **satu contoh FK dan satu contoh PX** (PDF/Word), supaya
> bab-babnya bisa dibaca, bukan ditebak. Setelah itu, menambah keduanya **tidak
> menyentuh view maupun route** — cukup schema + (bila perlu) template cetak.
| — | PDF lama di `docs/pdflama` (2 berkas), **teksnya bisa diseleksi**, format berbeda-beda, ada tabel | Opsi (b) layak sebagai akselerator; **bukan** proyek OCR |

## Temuan dari sumber lama — TERVERIFIKASI

Dump `u805399352_db_prowork.sql` (392 KB) sudah dibaca. Isinya jauh lebih berguna
daripada dugaan, dan sekaligus memunculkan satu pemblokir baru.

**Yang ADA di dump — dan cocok persis dengan brief:**

| Isi | Jumlah baris |
|---|---|
| Dokumen mutu: SOP 195, IK 404, SP 88, JSA 263 | **950** |
| Informasi: MEMO 89, POSTER 44, INSTRUKSI KTT 10, KEBIJAKAN 1, MSDS 1 | **145** |
| MEMO Internal, BAP, SERTIFIKAT & SIO, MOC & MPRP, IBPR | 0 (tabelnya ada, kosong) |
| **Total** | **1.095** |

Kolom tabel dokumen lama (`tb_sop_shes` dst.): `no_dokumen`, `judul_sop`, `file_sop`,
`edisi`, `revisi`, `tanggal_efektif` — **persis daftar butir 0 opsi (a)**. Kesepuluh
tabel informasi juga persis kolom yang Anda daftarkan (§B.4).

> ### ⛔ PEMBLOKIR BARU — berkas PDF-nya TIDAK ADA di salinan lokal
>
> Kolom `file` menyimpan nama teracak, mis.
> `PQf3MqOwSLtmHd2SWVdbgIMRw3DTQIxjkAd0dtr8.pdf`. Berkas itu **tidak ditemukan di
> mana pun** dalam salinan lokal. Seluruh project lama hanya memuat **34 PDF**,
> dan folder `kebijakan/`, `memo/`, `poster/` **tidak ada sama sekali**
> (`storage/app/public/` hanya berisi sop, ik_pending, jsa, jsa_pending, sp,
> lampiran_sop, lampiran_sp, sertifikatsio(kosong), *_pending).
>
> Artinya: berkasnya ada di **server Hostinger project lama**, bukan di salinan ini.
> Mengimpor sekarang menghasilkan 1.095 baris yang **setiap "Lihat PDF"-nya 404**.
> → **blocking #7**.

**Departemen: 8 lama → 7 baru.** Peta 7-nya **sudah ada** dan tak perlu dikarang —
`SmartPro-mobile/lib/config/api_config.dart:44-52`: `COE→ICTMD`, `FALOG→FAW-SCM`,
SHE/PLANT/HCGA/PRODUKSI/ENGINEERING tetap. Yang **tak punya padanan: `opds`**
(2 dokumen). → **blocking #6**.

## Cara baca

- **[KUNCI]** = intent pasti pemilik produk. Jangan diganggu.
- **[BUKA]** = keputusan belum final; `rencana:` adalah usulan default yang boleh ditantang.
- Nomor butir mengikuti `docs/master.md` (0–8), TIDAK dinomori ulang.
- Rujukan kode ditulis `file:baris` dari kondisi repo saat rencana ini disusun.

---

# LANGKAH 1 — Pengelompokan & Pengurutan

## Koreksi atas baseline pemilik

Baseline usulan pemilik:

| Fase | Baseline pemilik |
|---|---|
| A | UI + storage → **5, 1** |
| B | legacy + informasi + impor → **0, 2, 3, 8** |
| C | revisi + induk + mobile → **7, 6, 4** |

**Satu koreksi, dengan alasan tertulis: tukar butir 6 ↔ butir 8.**

Alasannya datang dari teks butir 8 sendiri:

> *"Ketika sub menu informasi sudah jadi, **export dokumen induk sudah jadi**, upload
> dokumen sudah jadi, dan **fitur revisi sudah jadi**, maka akan kita impor…"*

Butir 8 menamai butir 6 (export induk) dan butir 7 (revisi) sebagai prasyaratnya
sendiri. Keduanya ada di Fase C pada baseline. Jadi butir 8 di Fase B **mustahil
dijalankan** — ia akan berhenti menunggu fase sesudahnya. Butir 6 sebaliknya tidak
bergantung pada apa pun di Fase C: ia ekspor **baca-saja** (`DocumentExportController`
tak pernah menulis ke `documents`), jadi ia turun ke B tanpa merusak gradien
aman→mengusik.

Sisa baseline dipertahankan apa adanya.

## Fase final

| Fase | Nama | Butir |
|---|---|---|
| **A** | Kosmetik & Penyimpanan | **5, 1** |
| **B** | Fondasi Dokumen Lama, Informasi & Daftar Induk | **0, 2, 3, 6** |
| **C** | Alur Inti, Mobile & Impor Massal | **7, 4, 8** |

## Matriks butir → fase

| # | Butir | Fase | Yang disentuh | Alasan keamanan (urut aman→mengusik) | Bergantung pada |
|---|---|---|---|---|---|
| 5 | UI widget komentar lapangan | **A** | 1 partial Blade + CSS di `dashboard.blade.php` | **Paling aman.** Nol PHP, nol DB, nol rute. Salah pun hanya jelek, tak ada data yang rusak | — |
| 1 | Grouping storage + hapus data | **A** | `PdfRenderer::cetak()`, `DocumentPurger`, `PdfCacheTest` | Aman ke **logika**: yang berubah hanya nama folder singgahan (turunan, bisa dibangkitkan ulang). Destruktif ke **data**, tapi datanya memang diminta dibuang — dan membuangnya DULU membuat fase B & C tak perlu memigrasi baris lama | — (gerbang bagi 6) |
| 0 | Dokumen lama (PDF) masuk SmartPro | **B** | +1 kolom `documents`, service penyaji PDF baru, 2 controller bercabang | **Aditif.** Dokumen tanpa `arsip_path` menempuh jalur yang sama persis seperti sekarang; percabangan hanya menyala untuk baris baru | 1 (folder), 2 (nomor bertahan) |
| 2 | Nomor manual bertahan sampai berlaku | **B** | `ApprovalController.php:83-90` | **Perbaikan bug, bukan fitur.** Satu percabangan berpenjaga di satu method; sudah berpagar `DocumentNumberingTest`. Tapi ia menyentuh jalur pengesahan → tak bisa di Fase A | — (prasyarat 0 & 8) |
| 3 | Menu Informasi (upload-only) | **B** | 1 tabel baru, 1 controller, 1 set view, 1 blok menu | **Aditif murni.** Modul terpisah; tak satu pun rute/model dokumen mutu berubah. Besar, tapi tak mengusik | — (prasyarat 8) |
| 6 | Daftar induk adaptif + ekspor SH/DH | **B** | `DocumentExportController`, `export-excel.blade.php`, izin rute | **Baca-saja.** Tak pernah menulis. Risikonya salah-tampil, bukan salah-data. Butuh riwayat revisi bersih → itulah kenapa ia SESUDAH butir 1 | 1 (data bersih), `document_versions` |
| 7 | Revisi otomatis + tombol log revisi | **C** | `DocumentService::requestRevision`, `ApprovalController`, wizard, cetak PDF | **Mengusik inti.** Mengubah kapan `no_revisi` naik = mengubah arti roll-over Edisi/Revisi, nomor final, dan lembar Catatan Revisi yang tercetak | 0, 2 |
| 4 | Integrasi mobile app terhosting | **C** | Konfigurasi hosting, CORS/Sanctum, `Api/*` | **Mengusik ke luar.** Begitu aplikasi terpasang menunjuk server hidup, kontrak API membeku — tiap perubahan sesudahnya merusak aplikasi yang sudah di HP orang. Karena itu SETELAH semua permukaan API selesai berubah | 1, 3 (bila informasi ikut ke mobile) |
| 8 | Impor massal PDF+SQL via terminal/backend | **C** | Penulisan massal baris `published` | **Paling mengusik.** Menulis ratusan dokumen berstatus Berlaku sekaligus, melewati alur tinjau–setuju. Salah sekali = mengotori daftar induk resmi | **0, 2, 3, 6, 7** (disebut butir 8 sendiri) |

## Graf dependensi

```
1 ──────────► 6 ──────────────────────┐
                                      │
2 ──┬───────► 0 ──┬───────► 7 ────────┼──► 8
    │             │                   │
    └─────────────┘             3 ────┘

5   (berdiri sendiri)
1,3 ──────────► 4
```

Bacaan singkat: **8 adalah simpul terakhir** (5 prasyarat), **5 adalah satu-satunya
simpul lepas**, dan **1 & 2 adalah dua akar** yang membuka sisanya.

---
---

# BAGIAN A — Fase A: Kosmetik & Penyimpanan

**Status: DRAF**
**Prasyarat fase sebelumnya: TIDAK ADA.** Fase ini boleh dimulai kapan saja.
**Cakupan butir: 5, 1**

## A.1 Konvensi

- **[KUNCI]** = intent pasti pemilik, jangan diganggu.
- **[BUKA]** = keputusan terbuka; `rencana:` adalah default yang diusulkan.

## A.2 Butir 5 — UI/UX widget Komentar Lapangan — ✅ **SELESAI 2026-08-11**

> **Yang dikerjakan** (282 test hijau, naik dari 281):
> - `config/smartpro.php` — kunci baru `dashboard.masukan_widget` = 20.
> - `DashboardController::masukanWidget()` — dua `limit(4)` → `limit($batas)` dari konfigurasi.
> - `partials/_widget-masukan.blade.php` — tiga keadaan: 0 kutipan dipusatkan di
>   tengah kartu; 1–2 kutipan satu kolom & dipusatkan tegak; 3+ dua kolom, rapat ke
>   atas, **menggulung di dalam kartu**. Dimensi kartu tak berubah.
> - `dashboard.blade.php` — `.pp-kutip-tubuh { overflow-y:auto; min-height:0 }`.
>   `min-height:0` wajib: tanpa itu anak flex menolak menyusut dan gulirnya tak pernah aktif.
> - `tests/Feature/DashboardWidgetTest.php` — test baru
>   `test_widget_masukan_memuat_lebih_dari_empat_dan_menggulung`. **Sudah dibuktikan
>   gagal** saat `limit(4)` dikembalikan ke controller, jadi ia benar-benar menjaga.

### Intent

- **[KUNCI]** Dimensi kartu widget **TIDAK BOLEH** berubah.
- **[KUNCI]** Banyak komentar → bisa di-scroll.
- **[KUNCI]** Sedikit / tidak ada komentar → tetap enak dilihat, ruang kosong tidak menganga.

### Keadaan sekarang (terverifikasi)

- Widget: `resources/views/partials/_widget-masukan.blade.php`
- Dipasang di `resources/views/dashboard.blade.php:401-408`, `col-xl-8`, bersebelahan
  dengan `_widget-log` di `col-xl-4`.
- Kartunya `h-100` (`_widget-masukan.blade.php:22`) → tingginya **mengikuti tetangga**.
  Inilah sumber ruang kosongnya: dengan 1–2 kutipan, kartu tetap setinggi widget log.
- `card-body` memakai `row row-cols-1 row-cols-md-2 g-3` saat `$mendatar` (baris 35) —
  grid 2 kolom, tanpa batas tinggi, tanpa scroll.
- Gaya `.pp-kutip*` terpusat di `dashboard.blade.php:465-490`.

> ### ⚠ KOREKSI — butir 5 TIDAK bisa dikerjakan tanpa menyentuh PHP
>
> `DashboardController::masukanWidget()` memasang **`limit(4)`** di **KEDUA**
> cabangnya (`app/Http/Controllers/DashboardController.php:401` untuk Non-Staff,
> `:417` untuk SH/DH/GL/PJO). Dengan grid 2 kolom, 4 kutipan = **2 baris**.
>
> Artinya kartunya **tidak pernah bisa meluap**, dan permintaan **[KUNCI]** "jika ada
> banyak komentar, buat agar bisa di-scroll" **mustahil terealisasi** selama batas itu
> 4. Ini juga menjelaskan keluhan ruang kosongnya: kartu `h-100` yang tingginya
> mengikuti widget Log, tapi isinya dipatok maksimal 4 kutipan.
>
> **Penangkal:** butir 5 **harus** menaikkan batas itu. `rencana:` jadikan
> `config('smartpro.dashboard.masukan_widget', 20)` — angkanya di berkas konfigurasi
> mengikuti pola `config/smartpro.php` yang sudah ada (`peninjau.batas_dokumen`),
> supaya bisa disetel tanpa menyentuh kode. Barulah scroll punya arti.
>
> Konsekuensi: pernyataan "Tidak ada PHP, DB, rute, atau izin yang berubah" di bawah
> **dibatalkan** — `DashboardController` dan `config/smartpro.php` ikut berubah.
> Tetap nol DB, nol rute, nol izin.

### Rencana

- **[KUNCI]** Naikkan `limit(4)` di `DashboardController.php:401,417` (lihat koreksi di
  atas). Tanpa langkah ini, tiga poin di bawah tak bisa diuji.
- **[BUKA]** Teknik scroll. `rencana:` `card-body` diberi `overflow-y:auto` +
  `max-height` yang dihitung dari tinggi kartu (`flex:1 1 auto` di dalam
  `d-flex flex-column` pada `.card`), **bukan** `max-height` piksel tetap — persis
  aturan CLAUDE.md §6 soal sel ketersediaan yang elastis, jangan dipatok lebar/tinggi tetap.
  Alternatif yang ditolak: memotong daftar di PHP (`->take(4)`) — itu menyembunyikan
  masukan, bukan menatanya.
- **[BUKA]** Keadaan "sedikit". `rencana:` bila jumlah kutipan < 3, grid 2 kolom
  diturunkan jadi 1 kolom penuh sehingga kutipan melebar mengisi ruang, bukan
  menyisakan sel kanan kosong. Nol JS — cukup kelas kondisional Blade.
- **[BUKA]** Keadaan "kosong". `rencana:` blok kosong yang sudah ada
  (baris 69–80) dipusatkan vertikal (`h-100 d-flex align-items-center justify-content-center`)
  supaya ikon+teksnya duduk di tengah kartu, bukan menempel di atas.
- **[KUNCI]** Nol emoji; ikon tetap Bootstrap Icons (CLAUDE.md §13).
- Gaya baru menumpang di blok `@push('styles')` `dashboard.blade.php:411+` yang sudah ada
  — jangan buat berkas CSS baru.

### Titik kode terdampak

| Berkas | Baris | Perubahan |
|---|---|---|
| `app/Http/Controllers/DashboardController.php` | 401, 417 | **batas 4 → konfigurasi** (lihat koreksi di atas) |
| `config/smartpro.php` | akhir berkas | kunci `dashboard.masukan_widget` |
| `resources/views/partials/_widget-masukan.blade.php` | 22, 35, 69–80 | kelas kartu, wadah scroll, keadaan kosong |
| `resources/views/dashboard.blade.php` | 465–490 (blok gaya) | tambahan aturan `.pp-kutip-gulung` |

Nol DB, nol rute, nol izin. **Ada** PHP (dua baris batas + satu kunci konfigurasi).

## A.3 Butir 1 — Grouping storage & pembersihan data — ✅ **SELESAI SELURUHNYA 2026-08-12**

> **Yang dikerjakan** (283 test hijau):
> - `PdfRenderer::jalurSinggahan(int $id, ?string $jenis, string $sidik = '*')` —
>   **satu-satunya** tempat bentuk jalur ditulis. Tanpa `$sidik` ia mengembalikan pola
>   glob, jadi ketiga pemakai (render, pemusnahan, test) memakai sumber yang sama.
> - `PdfRenderer::folderJenis()` — saringan `preg_replace('/[^A-Z0-9]/','',…) ?: 'LAIN'`.
> - `PdfRenderer::cetak()` — menulis ke `pdf-cache/{JENIS}/{id}-{sidik}.pdf`.
> - `PdfRenderer::sapuSinggahan()` — **dua pola**: bentuk datar lama dibuang **tanpa
>   tenggang** (tak terjangkau siapa pun lagi, ongkos salahnya nol), bentuk baru tetap
>   bertenggang 30 hari.
> - `DocumentPurger` — `$jenis` ditangkap **sebelum** transaksi (sebelah `$lampiran`),
>   `buangSinggahanPdf($id, $jenis)` memanggil `PdfRenderer::jalurSinggahan()`.
> - `PdfCacheTest` — pola diminta ke pemiliknya; **test baru**
>   `test_singgahan_dikelompokkan_per_jenis_dan_ikut_musnah`. **Sudah dibuktikan gagal**
>   saat jenisnya tak dioper ke purger.
>
> **Terverifikasi di data nyata:** 97 berkas datar yang tadinya ada di akar kini
> **nol** — tersapu sendiri pada render dingin pertama. Seluruh singgahan berada di
> `pdf-cache/SOP/` dan `pdf-cache/JSA/`.
>
> **Langkah 4 (hapus folder `pdf-singgahan`)**: tidak dikerjakan sesuai keputusan
> pemilik A3 — dibiarkan.
>
> **Langkah 6–7 (penghapusan dokumen): ✅ SELESAI 2026-08-12** (commit `c737db1`).
> Blocking #1 dijawab **(b) seluruh dokumen**; blocking #2 ditutup dengan
> `backup-sebelum-hapus-20260812.sql` (95 KB, 22 tabel berisi data, di luar Git).
> `app/Console/Commands/HapusDokumen.php` ditulis sebagai pembungkus tipis atas
> `DocumentPurger` — `--dry-run`, penjaga `production`, `withTrashed`, dan
> dokumen "Sedang Direvisi" dilewati+dilaporkan alih-alih menghentikan perintah.
>
> **Terverifikasi sesudah dijalankan:** dokumen/isi/versi/masukan/dibaca/pekerjaan/
> lampiran/tinjauan/persetujuan = **0**; `users` = **9 UTUH**, departments 7,
> document_types 4; `audit_logs` **102 → 105** (bertambah 3 entri `document.purge`,
> bukan hilang); `storage/app/pdf-cache` kosong; **283 test hijau**.
>
> **Satu sisa yang sengaja TIDAK disentuh:** 7 berkas foto yatim di
> `storage/app/public/lampiran/{ICTMD,SHE}/SOP/`. Barisnya di `attachments` sudah
> nol, jadi tak satu pun dokumen menunjuknya — peninggalan penghapusan lewat jalur
> lain sebelum sesi ini. Tak berbahaya, tapi checklist uji nomor 18 mengharapkan
> folder itu bersih. CLAUDE.md §4 melarang saya menghapus berkas tanpa izin, jadi
> ia menunggu satu kalimat konfirmasi.

### Intent

- **[KUNCI]** PDF hasil ekspor dikelompokkan per **jenis dokumen** di storage:
  SOP di folder SOP, dan seterusnya.
- **[KUNCI]** Dokumen yang ada sekarang **dihapus sepenuhnya beserta datanya**.

### Keadaan sekarang (terverifikasi)

SmartPro **tidak menyimpan PDF sebagai berkas tetap.** PDF dibangkitkan DomPDF lalu
disinggahkan per sidik isi. Satu-satunya folder PDF yang ada:

- `storage/app/pdf-cache/{id}-{sidik}.pdf` — **datar**, 97 berkas saat ini.
  Ditulis `app/Services/Print/PdfRenderer.php:195-208`, disapu `:229-237`.
- Dihapus per dokumen di `app/Services/DocumentPurger.php:104`.
- Diuji `tests/Feature/PdfCacheTest.php:85`.
- `storage/app/private/pdf-singgahan/` — **folder yatim**, nol rujukan di seluruh
  `app/`, `config/`, `tests/`, `routes/`. Peninggalan.

Lampiran foto SUDAH berkelompok: `storage/app/public/lampiran/{DEPT}/{JENIS}/`
(`DocumentController.php:492`).

> **Temuan keamanan, di luar lingkup fase ini, dilaporkan agar Anda putuskan
> prioritasnya:** `config/filesystems.php:57-61` + `routes/web.php:53-58` membuat
> `storage/app/public/lampiran/**` **terbuka tanpa login**. Untuk foto lampiran itu
> perilaku yang sudah berjalan. Ia jadi relevan di Fase B: PDF dokumen utuh **tidak
> boleh** ikut ke sana.

### Rencana

1. **[BUKA]** Bentuk folder. `rencana:` `storage/app/pdf-cache/{JENIS}/{id}-{sidik}.pdf`
   (`{JENIS}` = `document_types.code`: SOP/IK/SP/JSA). Satu tingkat saja.
   Alternatif `{JENIS}/{DEPT}/` ditolak: singgahan dicari lewat `glob` per-id, tiap
   tingkat tambahan menambah tempat yang harus disapu tanpa manfaat yang bisa dilihat.
2. Ketiga titik yang menyusun jalur (`PdfRenderer:195`, `DocumentPurger:104`,
   `PdfCacheTest:85`) **dijadikan satu sumber** — satu method publik pemberi jalur di
   `PdfRenderer`, dipakai ketiganya. Tanpa itu, tiga tempat yang harus sepakat suatu
   hari tidak sepakat, dan gejalanya adalah berkas yatim yang tak pernah kelihatan.

   > **⚠ PERANGKAP — `DocumentPurger` tak akan tahu jenisnya.**
   > `buangSinggahanPdf($id)` (`DocumentPurger.php:102-107`) dipanggil di **baris 78**,
   > yaitu **SESUDAH** `$document->forceDelete()` (baris 70), dan hanya menerima `$id`.
   > Begitu jalurnya butuh `{JENIS}`, method itu **tidak punya cara mengetahuinya** —
   > barisnya sudah hilang dan relasi `type` tak bisa di-query lagi.
   >
   > **Penangkal:** tangkap `$jenis = $document->type?->code` **bersama `$lampiran`**
   > (baris 51, sebelum transaksi) — pola yang sudah dipakai berkas lampiran persis
   > karena alasan yang sama — lalu oper `buangSinggahanPdf($id, $jenis)`.
   > Bila dilewatkan: singgahan PDF dokumen yang dimusnahkan **tak pernah terhapus**,
   > menumpuk selamanya, dan tak ada gejala yang terlihat di layar.
3. `sapuSinggahan()` (`:229-237`) sekarang `glob($dir.'/*.pdf')` — **datar**. Sesudah
   regrouping ia tak akan pernah menemukan berkas di sub-folder jenis, **dan** berkas
   datar lama tak akan pernah disapu. Harus menyapu **kedua** bentuk:
   `$dir.'/*.pdf'` (sisa lama) **dan** `$dir.'/*/*.pdf'` (bentuk baru). Ini yang membuat
   peralihan bersih walau langkah 5 ditunda menunggu jawaban blocking #1.
4. Folder yatim `storage/app/private/pdf-singgahan/` **[BUKA]** `rencana:` dihapus
   bersamaan (nol rujukan). Butuh konfirmasi eksplisit — CLAUDE.md §4 melarang hapus
   folder tanpa izin.
5. **[BUKA]** `{JENIS}` ikut menyusun **jalur berkas**, dan nilainya datang dari DB
   (`document_types.code`). Hari ini bersih — `SOP`, `SP`, `IK`, `JSA`
   (`database/seeders/DocumentTypeSeeder.php:21,37,48,59`). `rencana:` tetap saring
   `preg_replace('/[^A-Z0-9]/', '', strtoupper($code)) ?: 'LAIN'` sebelum dipakai,
   supaya kode jenis baru yang mengandung `/` atau `..` tak pernah bisa keluar dari
   folder. Ongkosnya satu baris; tanpanya, penambahan jenis dokumen kelak adalah
   penulisan berkas sembarang tempat.

6. **Pembersihan data — [KUNCI A1] berkas PDF + dokumen, BUKAN user, BUKAN audit log.**

   > **✅ Jawaban A1 membatalkan bahaya terbesar fase ini.**
   >
   > Draf sebelumnya mengusulkan `php artisan smartpro:renew`. Dengan cakupan yang
   > Anda tetapkan, **perintah itu salah alat**: ia mengosongkan `users`, audit log,
   > notifikasi, dan seluruh tabel operasional — jauh melampaui "PDF + dokumen".
   >
   > Seluruh peringatan panjang tentang 7 tabel yatim, `AUTO_INCREMENT` yang direset,
   > dan token Sanctum yang masuk sebagai orang lain **GUGUR** — semuanya lahir dari
   > `TRUNCATE`, dan kita tidak akan memakai `TRUNCATE` sama sekali.

   **Alat yang benar juga SUDAH ADA: `app/Services/DocumentPurger.php`.** Ia dibangun
   persis untuk ini dan sudah menangani seluruh sudut yang berbahaya:

   - `job_executions` ber-**RESTRICT** dihapus lebih dulu (`:55`), jadi tak ada SQL 1451;
   - `DELETE` sungguhan → **`ON DELETE CASCADE` benar-benar jalan** untuk contents,
     versions, reviews + anotasi, approvals, attachments + komentar, authors,
     feedback, reads. Tak ada baris yatim;
   - notifikasi lonceng ber-`document_id` di dalam JSON ikut dibersihkan (`:57`);
   - berkas lampiran fisik dihapus (`:77`);
   - singgahan PDF dihapus (`:78`);
   - **audit log sengaja dipertahankan** — jejak "siapa memusnahkan apa" justru harus selamat;
   - `AUTO_INCREMENT` **tidak** direset, jadi id tak pernah dipakai ulang.

   **Rencana:** perintah tipis `php artisan smartpro:hapus-dokumen` yang **memanggil
   `DocumentPurger` dalam loop**, bukan menulis penghapus kedua. Isinya kira-kira:
   konfirmasi → pilih dokumen sesuai cakupan → purge satu per satu → laporkan jumlah.

   - **[BUKA]** Cakupan. `rencana:` **seluruh dokumen apa pun statusnya**, karena ini
     DB pengembangan dan 950 dokumen nyata akan menggantikannya. Kalau maksud Anda
     harfiah "hanya yang berstatus Berlaku", sisa draft/ditinjau/Tidak Berlaku akan
     bercampur dengan hasil impor nanti. → **blocking #1**.
   - **Dokumen "Sedang Direvisi" akan DITOLAK** oleh `pastikanTakSedangDirevisi()`
     (`:88-99`). Itu benar, bukan penghalang: batalkan revisinya dulu, atau perintahnya
     melewatinya dan melaporkannya di akhir.
   - Sesudah loop selesai, kosongkan sisa `storage/app/pdf-cache` (seluruh sub-folder)
     — menangkap singgahan yatim dari test dan dokumen yang pernah dihapus lewat jalur lain.
   - Penjaga `production` **ditiru** dari `RenewData.php:41-45`. Perintah penghapus
     dokumen tak boleh bisa dijalankan di Hostinger.

7. **Prosedur menjalankan langkah 6** — ini yang menyelamatkan hari Anda:
   - **[KUNCI] Backup database DULU. Anda bilang belum ada.** Satu perintah:
     ```
     C:\xamppnew\mysql\bin\mysqldump.exe -u root smartpro_refactor > backup-sebelum-hapus.sql
     ```
     (sesuaikan nama DB dengan `DB_DATABASE` di `.env`). **Saya tidak akan menjalankan
     langkah 6 sebelum Anda mengonfirmasi berkas ini ada dan ukurannya masuk akal.**
   - Urutannya: **regroup (langkah 1–5) DULU → uji → commit → baru hapus.** Terbalik,
     folder datar lama terisi lagi oleh pemakaian di antara keduanya.
   - Jalankan **hanya di XAMPP lokal**.
   - Akun Anda **tidak** tersentuh — itu bedanya dengan draf sebelumnya.

### Spec teknis

**Skema tabel: TIDAK ADA perubahan.** Butir 1 murni jalur berkas + pengosongan data.

| Berkas | Baris | Perubahan |
|---|---|---|
| `app/Services/Print/PdfRenderer.php` | 193–212 | jalur singgahan ber-sub-folder jenis; method jalur publik |
| `app/Services/Print/PdfRenderer.php` | 229–237 | sapuan rekursif |
| `app/Services/DocumentPurger.php` | **51** (tangkap `$jenis`), 78, 102–107 | oper jenis ke `buangSinggahanPdf()` |
| `app/Console/Commands/HapusDokumen.php` | **baru** | pembungkus tipis atas `DocumentPurger` + penjaga `production` |
| `app/Console/Commands/RenewData.php` | — | **TIDAK disentuh** (bukan alatnya, lihat langkah 6) |
| `tests/Feature/PdfCacheTest.php` | 85 | pola jalur baru |

### Risiko

| Risiko | Beratnya | Mitigasi |
|---|---|---|
| **Belum ada backup** dan penghapusan tak punya undo | **Tinggi** | `mysqldump` wajib lebih dulu (langkah 7); saya menolak jalan tanpanya |
| Cakupan "dokumen Berlaku saja" menyisakan draft/obsolete yang bercampur dgn 950 hasil impor | Sedang | → **blocking #1** |
| Dokumen "Sedang Direvisi" menolak dihapus | Rendah | Perilaku benar; lewati + laporkan, jangan dipaksa |
| Singgahan dokumen yang dimusnahkan tak pernah terhapus | Sedang | Tangkap `$jenis` sebelum `forceDelete()` (langkah 2) |
| Berkas datar lama jadi yatim selamanya bila langkah 6 ditunda | Sedang | `sapuSinggahan()` menyapu **kedua** bentuk jalur (langkah 3) |
| Kode jenis baru mengandung `/` atau `..` → tulis berkas di luar folder | Rendah | Saringan satu baris (langkah 5) |
| Perintah dijalankan di server terhosting | **Tinggi** | Penjaga `production` sudah ada (`RenewData.php:41-45`); jangan dilonggarkan |

## A.4 Checklist uji manual — Fase A

**Butir 5**

0. **Prasyarat uji:** batas `limit(4)` sudah dinaikkan (lihat koreksi §A.2). Tanpa ini,
   uji nomor 1 **tidak mungkin lulus** — datanya tak akan pernah lebih dari 4 baris.
1. Masuk sebagai **SH/DH** yang punya **≥ 8** masukan belum ditindak → kartu Masukan
   Lapangan **tidak** memanjang melewati widget Log; isinya bisa **digulir** di dalam kartu.
2. Masuk sebagai SH/DH dengan **1** masukan → kutipan melebar penuh, tak ada sel kanan kosong.
3. Masuk sebagai **Non-Staff** tanpa kiriman → keadaan kosong duduk di **tengah** kartu.
4. Masuk sebagai Non-Staff dengan kiriman **berbalasan** → balasan tetap menjorok di bawah kutipan.
5. **Toggle tema gelap** pada ketiga keadaan di atas — kontras & border tetap benar.
6. Lebar layar **1366px** dan **1920px** — tinggi kartu tetap sama dengan tetangganya.

**Butir 1**

7. Buka satu dokumen tiap jenis (SOP/IK/SP/JSA) → **Lihat PDF** → berkas muncul di
   `storage/app/pdf-cache/{JENIS}/`, dan folder `pdf-cache/` **tidak lagi** berisi `.pdf` datar.
8. Buka **dokumen yang sama** kedua kali → tak ada berkas baru (singgahan kena), PDF identik.
9. **Musnahkan** satu dokumen (Admin) → singgahannya di sub-folder jenis ikut hilang.
10. Buka PDF yang sama **dari aplikasi mobile** (`/api/files/...`) → berkas sama, bukan error.
11. `php artisan test` **penuh hijau** (41 berkas test di `tests/Feature/`).

**Butir 1 — penghapusan dokumen (jalankan HANYA setelah backup ada & blocking #1 dijawab)**

12. **Backup database sudah dibuat** (`mysqldump`), ukurannya masuk akal, lokasinya dicatat.
13. Sebelum menghapus: **buat satu pekerjaan JSA** + **satu masukan lapangan** +
    **satu lampiran foto** pada dokumen yang akan dihapus, supaya jalur cascade &
    RESTRICT benar-benar teruji, bukan hanya jalur kosong.
14. Setelah perintah selesai, **hitung barisnya** — semuanya harus **0**:
    ```sql
    SELECT
      (SELECT COUNT(*) FROM documents)           AS documents,
      (SELECT COUNT(*) FROM document_contents)   AS contents,
      (SELECT COUNT(*) FROM document_versions)   AS versions,
      (SELECT COUNT(*) FROM document_feedback)   AS feedback,
      (SELECT COUNT(*) FROM document_reads)      AS reads,
      (SELECT COUNT(*) FROM job_executions)      AS jobs,
      (SELECT COUNT(*) FROM job_checklist_items) AS checklist,
      (SELECT COUNT(*) FROM attachments)         AS lampiran;
    ```
15. **`users` masih utuh** dan Anda **tetap login** — inilah bedanya dengan draf lama:
    ```sql
    SELECT COUNT(*) FROM users;   -- harus SAMA dengan sebelum perintah dijalankan
    ```
16. **`audit_logs` bertambah**, bukan hilang — ada entri `document.purge` per dokumen.
17. Buka **Riwayat Pekerjaan** → halaman tampil **kosong**, bukan 500.
18. `storage/app/pdf-cache` **kosong sampai ke sub-foldernya**;
    `storage/app/public/lampiran/**` juga bersih.
19. Dokumen berstatus **Sedang Direvisi** (bila ada) **dilewati dan dilaporkan**, bukan
    membuat perintahnya berhenti di tengah.
20. `php artisan test` **penuh hijau** sekali lagi sesudah penghapusan.

## A.5 Prompt eksekusi — Fase A

```
Kerjakan Fase A di docs/PLAN-MASTER.md (butir 5 lalu butir 1). Satu butir = satu commit.

Butir 5 — BACA HANYA: resources/views/partials/_widget-masukan.blade.php,
resources/views/dashboard.blade.php baris 375-500,
app/Http/Controllers/DashboardController.php baris 390-420, config/smartpro.php.
WAJIB naikkan limit(4) di DashboardController:401,417 jadi kunci konfigurasi —
tanpa itu widget tak pernah meluap dan scroll tak bisa diuji (§A.2 koreksi).
Jangan sentuh DB/rute/izin. Jangan ubah dimensi kartu.

Butir 1 — BACA HANYA: app/Services/Print/PdfRenderer.php baris 190-240,
app/Services/DocumentPurger.php, app/Console/Commands/RenewData.php,
tests/Feature/PdfCacheTest.php.
Satukan jalur singgahan jadi SATU method di PdfRenderer, jangan salin glob ke tiga tempat.
WAJIB: tangkap $jenis SEBELUM forceDelete di DocumentPurger (§A.3 langkah 2) —
sesudahnya relasi type sudah tak bisa dibaca.
WAJIB: sapuSinggahan menyapu DUA bentuk jalur (datar lama + sub-folder baru).
Penghapusan data memakai DocumentPurger yang SUDAH ADA dalam loop (§A.3 langkah 6),
BUKAN smartpro:renew dan BUKAN TRUNCATE — cakupannya PDF + dokumen saja,
user & audit log TIDAK boleh tersentuh.
JANGAN jalankan perintah penghapus — tunggu blocking #1 DAN konfirmasi backup ada.

Selesai tiap butir: php artisan test penuh → berhenti → tunggu review.
```

---
---

# BAGIAN B — Fase B: Fondasi Dokumen Lama, Informasi & Daftar Induk

**Status: DRAF**
**Prasyarat: Fase A SELESAI & ter-commit.** Alasannya bukan formalitas:
butir 6 membaca riwayat revisi, dan pembersihan data butir 1 menentukan riwayat mana
yang ada. Butir 0 menulis PDF ke disk — konvensi foldernya diputuskan di butir 1.
**Cakupan butir: 0, 2, 3, 6**

## B.1 Konvensi

- **[KUNCI]** = intent pasti pemilik. **[BUKA]** = terbuka, `rencana:` = default usulan.

## B.2 Butir 0 — Dokumen existing (PDF) vs format SmartPro — ✅ **SELESAI 2026-08-12 (opsi A)**

> **Keputusan pemilik sesi ini — TERKUNCI:**
>
> | # | Pertanyaan | Jawaban |
> |---|---|---|
> | 0-a | Pintu masuk formulir unggah | **Saklar di dalam `documents/create`** ("Ini dokumen lama — unggah berkas PDF-nya"). Satu pintu "Dokumen Baru", dua penerima; `:action` Alpine yang menentukan tujuannya |
> | 0-b | Notifikasi saat didaftarkan | **Ya** — lonceng ke SH/DH departemennya (bukan email, agar butir 8 tak membanjiri) |
> | 0-c | Perbaikan metadata | **Tombol Edit di tabel** — metadata **dan** berkas bisa diubah |
> | 0-d | Siapa boleh Edit | **Pengunggah (dept sendiri) + Admin** |
> | 0-e | Pemilih 3 tabel (wizard/unggahan/gabungan) | **Hanya "Dokumen Saya"** (`documents/index`). "Dokumen Berlaku" & "Status Dokumen Departemen" menampilkan keduanya bercampur |
> | 0-f | Tab "PDF Lama" berdampingan di wizard revisi | **DITUNDA** — di luar butir 0 |
>
> **Yang dikerjakan** (302 test hijau, naik dari 286):
> - Migration `documents.arsip_path` nullable — **satu** kolom, tanpa penanda kedua.
> - `Document::isArsip()` / `nomorLuarPola()` / `bisaDisuntingArsipOleh()` — ketiganya turunan, nol kolom tambahan.
> - `app/Services/Print/ArsipPdf.php` **baru** — penyaji berkas arsip (disk `local`,
>   ETag mtime+ukuran, `nosniff` + CSP, streamed). Dipakai **web DAN mobile**.
> - `DocumentPdfController` + `Api/DocumentFileController` — cabang `isArsip()`
>   **sebelum** `sidik()`; distribusi tetap dicatat lebih dulu di keduanya.
> - `DocumentService::createArsip()` — langsung `published`, `doc_number_final`
>   **ikut terisi** (kalau tidak, nomornya tak masuk kolam nomor yang ditahan),
>   `published_at` = tanggal efektif dokumen lamanya.
> - `DocumentService::snapshot()` + `'arsip_path'`; `DocumentPurger` ikut membuang
>   berkas arsip (ditangkap **sebelum** transaksi, sebelah `$lampiran` & `$jenis`).
> - `DocumentArsipController` **baru** (`store`/`edit`/`update`) — bukan di
>   DocumentController yang sudah 580-an baris (CLAUDE.md §3).
> - `ArsipDocumentRequest` **baru** — satu kelas untuk store & update, dibedakan
>   ada-tidaknya parameter rute `{document}`; validasi **magic bytes** `%PDF-`
>   (`mimes:pdf` hanya membaca ekstensi); jenis & dept `prohibited` saat update.
> - `PdfRenderer::folderJenis()` → **`folderAman()`, dijadikan publik** — saringan
>   `preg_replace` yang sama dipakai jalur arsip `arsip/{DEPT}/{JENIS}/`.
> - `DocumentParticipantResolver::heads()` dijadikan publik (kabar ke SH/DH).
> - `DocumentController::edit()` — dokumen arsip **dialihkan** ke `documents.show`
>   (wizard kosong hanya mengundang orang mengisi apa yang tak akan tercetak).
> - View: saklar di `create`, `documents/arsip/edit.blade.php` **baru**, pemilih
>   Sumber di `index`, tombol Edit di `index` **dan** `published` (Admin tak
>   melihat dokumen Berlaku di "Dokumen Saya" — kalau hanya di `index`, tombolnya
>   tak pernah sampai ke Admin), badge `partials/_badge-nomor-lama` di
>   `index`/`published`/`obsolete`/`show`.
> - `tests/Feature/ArsipDocumentTest.php` **baru** — 16 test, termasuk
>   **jalur mobile** dan **revisi dokumen arsip** (draft revisinya ber-`arsip_path`
>   null, snapshot versi lama mengingat berkasnya).
>
> **Yang TIDAK dikerjakan, disengaja:** rekomendasi (d) poin 1 ("Belum Ada Berkas")
> — halaman itu baru berguna saat butir 8 mendaftarkan baris tanpa berkas;
> `ArsipPdf` sudah membalas 404 berpesan untuknya. Poin 4 (tab PDF Lama) ditunda
> atas keputusan pemilik 0-f. Poin 5 (opsi b / OCR) tetap pasca-MVP.

### Evaluasi opsi (rekaman: kenapa (a), bukan (b)/(c))

#### Opsi (a) — Fitur upload dokumen lama (metadata + berkas PDF)

*Isinya: nomor dokumen, judul, edisi, revisi, tanggal efektif.*

**Celah yang pemilik sebut:** *bagaimana kalau dokumen itu ingin direvisi? Format
SmartPro tidak sama, dan PDF-nya tak bisa diedit.*

**Jawabannya sudah ada di kodebase, dan ini kekuatan terbesar opsi (a):** revisi
dokumen arsip berjalan **tanpa satu baris kode alur baru**.
`DocumentService::requestRevision()` (`app/Services/DocumentService.php:96-140`) sudah:
menyimpan snapshot versi lama (`snapshot()`, `:151`), menjadikan yang lama
`sedang_direvisi`, membuat draft baru milik **pembuat asli**, mewarisi `doc_number` +
`doc_number_manual`, menghitung roll-over Edisi/Revisi (`nextEditionRevision()`, `:84`),
dan menaut `revises_document_id`.

Artinya: PDF lama **tidak perlu diedit**. Ia jadi **versi lama** yang otomatis
Tidak Berlaku begitu versi baru disahkan (`ApprovalController.php:96-102`), dan versi
barunya adalah dokumen SmartPro penuh yang diketik sekali. Ongkos ketik ulang hanya
dibayar untuk dokumen yang **benar-benar** direvisi — bukan untuk seluruh arsip di muka.

**Celah nyata (yang pemilik belum sebut), ketiganya bisa ditutup:**

| Celah | Beratnya | Penutup |
|---|---|---|
| PDF dokumen mutu utuh bocor tanpa login bila ditaruh di disk `public` (`filesystems.php:57-61` + `routes/web.php:53-58`) | **Tinggi** | Simpan di disk `local` (`filesystems.php:39-42`, `serve => false`) + rute berpenjaga auth |
| Ada **DUA** penyaji PDF: web (`DocumentPdfController`) dan mobile (`Api/DocumentFileController.php:37-92`). Menambal satu saja = dokumen arsip kosong/error di HP | Sedang | Percabangan dipusatkan di SATU service, dipakai kedua controller |
| Baris `documents` tanpa `contents`/`reviewer_id`/`approver_id` bisa meledakkan halaman yang mengasumsikan ketiganya ada | Sedang | Satu test yang membuka SEMUA halaman atas satu dokumen arsip |

**Potensi:** paling murah; nol dependensi baru; jalan sejak hari pertama; dan ia
**memperbaiki** penomoran alih-alih mengganggunya — `usedSequences()`
(`DocumentNumberService.php:98`) membaca `doc_number` semua dokumen, jadi arsip
bernomor `…-07` otomatis memesan urutan 07 dan berhenti bertabrakan dengan dokumen baru.

---

#### Opsi (b) — OCR + pemetaan isi otomatis oleh AI

**Celah yang pemilik sebut:** *bagaimana histori revisinya? Apakah penempatan isi
hasil konversi akan sesuai?*

**Keduanya sah, dan keduanya menghukum (b) bila dipakai sebagai mekanisme pendaftaran.**

- **Histori revisi:** OCR membaca satu PDF = satu keadaan. Ia tak bisa mengarang
  riwayat revisi yang tak tertulis di berkas itu. Pertanyaan histori **tidak
  terselesaikan oleh (b)** — ia terselesaikan oleh (a), yang mencatat edisi/revisi
  sebagai data lalu membiarkan riwayat berikutnya tumbuh dari alur revisi SmartPro.
- **Penempatan:** akan sering meleset. `AbstractAiReviewer::parse()` sudah punya pola
  validasi keluaran terhadap whitelist `section_key`, jadi "mengarang bab" bisa
  ditangkap — tapi "isi benar, bab salah" tidak. Untuk **tabel** (aktivitas ber-PIC,
  grid JSA) hasilnya paling buruk.
- **Prasyarat yang belum diketahui:** apakah PDF lama itu **teks digital atau hasil
  pindai**. Lahir dari Word → ekstraksi hampir sempurna. Hasil scan bertandatangan →
  ekstraksi menghasilkan sampah dan ini berubah jadi proyek **OCR sungguhan** yang
  jauh lebih mahal. *Cara memastikan: buka 3 PDF lama, seleksi teksnya dengan mouse.
  Bisa diseleksi = digital.*
- **Kepatuhan:** CLAUDE.md §12 mewajibkan konfirmasi izin pengiriman data ke API
  eksternal sebelum aktivasi. Dokumen mutu perusahaan = keputusan pemilik, bukan teknis.
- **Batas teknis yang sudah terukur:** `GeminiReviewer` berjalan `maxOutputTokens: 3000`
  dengan timeout 45 detik — cukup untuk **meninjau**, terlalu kecil untuk **mengisi**
  seluruh isi dokumen sekali jalan.

**Potensi:** penghematan ketik terbesar — **tapi hanya sebagai lapisan percepatan di
atas (a)**, bukan sebagai penggantinya. Sebagai akselerator, keluarannya adalah
**draft usulan yang harus diterima manusia**, dan draft tetap melewati tinjau–setuju.
Itulah yang menurunkan "salah tempat" dari cacat integritas data menjadi gangguan UX belaka.

---

#### Opsi (c) — Format adaptif / dokumen profil bermuatan banyak template

**Celah yang pemilik sebut:** *apakah performa & keamanan datanya aman? Apakah tercapai?*

**Jawaban jujur: performa dan keamanan BUKAN masalahnya** — datanya sama, query-nya
sama, tak ada permukaan serangan baru. Yang membunuh (c) adalah dua hal lain:

1. **Melawan CLAUDE.md §7** — "schema JSON per jenis = sumber tunggal form/preview/PDF".
   Membiarkan tiap dokumen membawa templatnya sendiri membatalkan aturan pusat itu.
2. **Permukaan perawatan tak berbatas.** Tiap template adalah proyek kalibrasi DomPDF
   tersendiri. `tests/Feature/PrintLayoutTest.php` ada **persis karena** tata letak
   DomPDF rapuh — ia membaca stream PDF nyata untuk mengunci tata letak. Menambah N
   template = menambah N kalibrasi yang harus dijaga selamanya.
3. **Melawan tujuan sistemnya sendiri.** Sistem dokumen mutu dibangun untuk
   **menghapus** keberagaman format, bukan mengabadikannya. (c) membekukan kekacauan
   format lama menjadi fitur permanen.

**Potensi:** kebutuhan sah di baliknya — "satu departemen butuh bab tambahan" — **sudah
tertampung arsitektur sekarang**: `schema_json` per `DocumentType` di DB.
→ **Ditolak.**

---

#### Opsi (d) — Rekomendasi

**(a) sekarang, (b) menyusul sebagai akselerator opsional, (c) ditolak.**

Bentuk konkretnya:

1. **Daftar dulu, berkas menyusul.** Baris dokumen dibuat dari metadata (butir 8 lewat
   .xlsx; butir 0 lewat form satuan), PDF-nya diunggah belakangan lewat halaman
   "Belum Ada Berkas". Dokumen langsung masuk daftar induk & penomoran walau PDF-nya
   belum sempat dipindai.
2. **Badge "Nomor Lama"** untuk nomor yang tak cocok pola
   `PPA-ADRO-{JENIS}-{DEPT}-{NN}` — diterima apa adanya, ditandai jelas.
3. **Revisi = jalur SmartPro biasa** (sudah ada, §B.2 opsi a).
4. **Tab "PDF Lama" berdampingan** di panel pratinjau wizard revisi
   (`resources/views/documents/edit.blade.php:181`) — PDF lama terbuka di samping form.
   Ini yang memangkas waktu ketik sejak hari pertama, **dan sekaligus wadah yang kelak
   diisi (b)**.
5. **(b) belakangan**, per-bagian, atas permintaan pengguna, hanya bila prasyarat
   "PDF berteks digital" terbukti.

### Spec teknis butir 0

**Skema tabel — satu kolom, disengaja:**

```
documents
  + arsip_path   VARCHAR NULL     -- 'arsip/{DEPT}/{JENIS}/{uuid}.pdf' di disk `local`
```

**Sengaja TANPA kolom penanda kedua.** `arsip_path !== null` sudah berarti "dokumen
lama". Dua kolom yang harus sepakat adalah dua kolom yang suatu hari tidak sepakat.

Turunan, **tidak disimpan** (turunan tak bisa basi):
- `Document::isArsip()` → `arsip_path !== null`
- `Document::nomorLuarPola()` → `doc_number` tak cocok `/^PPA-ADRO-[A-Z]+-[A-Z]+-\d{2}$/`

**Titik kode terdampak:**

| Berkas | Baris | Perubahan |
|---|---|---|
| migration baru | — | `documents.arsip_path` nullable |
| `app/Models/Document.php` | ~320–340 (dekat `isRevisionDraft`) | `isArsip()`, `nomorLuarPola()` |
| `app/Services/Print/ArsipPdf.php` | **baru** | penyaji PDF arsip: baca disk `local`, ETag mtime+ukuran, `nosniff` + CSP |
| `app/Http/Controllers/DocumentPdfController.php` | — | cabang `isArsip()` **sebelum** `sidik()` dipanggil |
| `app/Http/Controllers/Api/DocumentFileController.php` | 79–92 | cabang yang sama; `distribusi->catat()` tetap dipanggil lebih dulu (`:66`) |
| `app/Services/DocumentPurger.php` | 51–60 | ikut hapus berkas `arsip_path` |
| `app/Services/DocumentService.php` | 96–140 | `requestRevision()`: draft baru **tidak** mewarisi `arsip_path` |
| `app/Services/DocumentService.php` | 151–167 | `snapshot()` **harus** ikut merekam `arsip_path` |
| `resources/views/documents/{show,published,obsolete}.blade.php` | — | tahan `contents`/`reviewer` kosong; badge "Nomor Lama"; sembunyikan Edit/wizard |

**[BUKA]** Siapa yang boleh mengunggah dokumen lama. `rencana:` GL, terkunci ke
departemennya sendiri, lewat izin `document.create` yang **sudah ada** — tak perlu
permission baru. Admin (`document.view_all`) memilih departemen di form.
**[BUKA]** Status hasil upload. `rencana:` langsung **Berlaku**, dengan entri audit
log per dokumen + notifikasi ke SH/DH departemen (empat lapis tata kelola, karena ini
melewati alur tinjau–setuju).

## B.3 Butir 2 — Input dokumen existing dengan nomor manual

### Intent

- **[KUNCI]** Nomor dokumen lama tetap dipakai di SmartPro **hingga dokumen berlaku**.
- Pertanyaan pemilik: *apakah ditambah tombol atau setelan tertentu?*

### Jawaban: tombolnya SUDAH ADA — yang rusak adalah pengunciannya

Toggle nomor manual sudah lengkap dan berjalan:

- Form: `resources/views/documents/create.blade.php:19,51` (`x-data="{ manual: … }"` +
  input tersembunyi `doc_number_manual`).
- Validasi: `app/Http/Requests/StoreDocumentRequest.php:21-31` — `required_if`, lalu
  keunikan lewat `DocumentNumberService::isUnique()`.
- Penyimpanan: `DocumentController.php:143` → `DocumentService.php:45`
  (`doc_number_manual` => true).

**Bug-nya di ujung alur.** `app/Http/Controllers/ApprovalController.php:83-88`:

```
$final = $document->doc_number_final;
if (! $final) {
    $final = $isRevision
        ? $document->doc_number
        : app(DocumentNumberService::class)->generateFinal(...);   // ← menimpa nomor manual
}
```

`doc_number_final` masih NULL sampai disahkan, dan dokumen non-revisi **tidak pernah**
memeriksa `doc_number_manual`. Akibatnya: **nomor yang diketik tangan ditimpa diam-diam
tepat pada detik dokumen menjadi Berlaku.** Butir 0 dan butir 8 keduanya bocor di
langkah terakhir tanpa perbaikan ini — itulah kenapa butir 2 wajib mendahului keduanya.

### Rencana

**[KUNCI]** Tak ada tombol/setelan baru. Yang ditambah adalah **penguncian**:

1. Aturan nomor final jadi: `doc_number_final` yang sudah ada → bila kosong dan
   (`$isRevision` **atau** `doc_number_manual`) → pakai `doc_number` → selain itu
   `generateFinal()`.
2. **Penjaga bentrok:** bila nomor manual itu sudah dipegang `doc_number_final`
   dokumen lain (termasuk ter-soft-delete), pengesahan **ditolak** dengan pesan jelas.
   Pakai `DocumentNumberService::isUnique($nomor, $document->id)` — parameter
   `$ignoreId`-nya **sudah ada dan belum pernah dipakai**.
3. **Sebelum mengubah apa pun**, jalankan pemeriksa data terlanjur:
   ```
   php artisan tinker --execute="dump(App\Models\Document::whereNotNull('doc_number_final')->where('doc_number_manual',1)->whereColumn('doc_number_final','!=','doc_number')->get(['id','doc_number','doc_number_final'])->toArray());"
   ```
   Ada isinya → **berhenti, laporkan.** Jangan perbaiki otomatis: itu mengubah nomor
   dokumen yang sudah beredar di lapangan.
   *(Setelah `smartpro:renew` di Fase A, kemungkinan besar kosong — tapi tetap jalankan.)*

**Skema tabel: TIDAK ADA perubahan.** `doc_number_manual` sudah ada sejak
`database/migrations/2026_07_12_090003_create_documents_table.php:42`.

| Berkas | Baris | Perubahan |
|---|---|---|
| `app/Http/Controllers/ApprovalController.php` | 83–90 | hormati `doc_number_manual`; penjaga bentrok |
| `tests/Feature/DocumentNumberingTest.php` | — | 3 test baru (lihat checklist) |

## B.4 Butir 3 — Menu Informasi (upload-only, akses semua) — ✅ **SELESAI 2026-08-12**

> **Keputusan pemilik sesi ini — TERKUNCI:**
>
> | # | Pertanyaan | Jawaban |
> |---|---|---|
> | 3-a | Kolom formulir per kategori | **Ikut persis data lama.** Field yang tak dipakai kategori itu TIDAK ditampilkan, dan kiriman yang tetap memuatnya **ditolak** (`prohibited`) |
> | 3-b | Nomor POSTER | **Wajib, seperti kategori lain.** `no_dokumen` berisi judul di sistem lama adalah **salah input operator sebelumnya**, bukan aturan — impor butir 8 harus memperlakukannya begitu |
> | 3-c | Riwayat | ~~Halaman yang sama, `?riwayat=1`~~ → **DIGANTI 3-g** |
> | 3-d | **Konflik plan** §B.4 [B3] vs checklist §B.6 no.20 | **Dua pintu terpisah.** **Tambah** hanya untuk nomor yang BELUM ada (nomor kembar → ditolak, pesan mengarahkan ke Perbarui). **Perbarui** = tombol per baris → form unggah lagi → konfirmasi bahwa versi sekarang jadi riwayat dan yang baru ditampilkan dengan nomor yang sama |
> | 3-e | Hapus | **Dua opsi di modal:** "hapus versi ini saja" (riwayat TERBARU naik jadi berlaku) atau "hapus seluruhnya termasuk riwayat" |
> | 3-f | Siapa boleh mengunggah | **Admin + GL** — keputusan B2 ("Admin saja **dulu**") dibuka setelah modulnya berjalan. SH/DH & PJO sengaja **belum**. Aman karena Informasi tak punya alur mutu: tak ada status yang bisa dilompati, tak ada persetujuan yang bisa dilewati |
> | 3-g | Riwayat **(mengoreksi 3-c)** | **Tersarang & terlipat di bawah barisnya sendiri**, bukan tab terpisah. Sebab yang pemilik tunjuk: riwayat 4 versi dari SATU dokumen berbaur dengan riwayat dokumen lain di satu daftar datar, jadi makin rajin sebuah dokumen diperbarui, makin tak terbaca daftar itu bagi semua orang |
>
> **Konsekuensi 3-d untuk checklist §B.6:** butir 20 ("nomor kembar → ditolak")
> **BENAR untuk Tambah**, dan perlu ditambah satu langkah uji untuk Perbarui.
> Butir 17 & 18 tetap. Checklist di bawah sudah diperbarui.
>
> **Yang dikerjakan** (320 test hijau, naik dari 302):
> - Migration `informasi` — SATU tabel + `kategori`. Dua penyimpangan sengaja dari draf:
>   `kategori` **VARCHAR bukan ENUM** (menambah kategori pada ENUM menuntut ALTER
>   TABLE di server; daftar sahnya toh ditegakkan `Informasi::KATEGORI` + Form
>   Request), dan kolom bernama **`no_revisi`** mengikuti `documents`, bukan `revisi`.
> - `app/Models/Informasi.php` **baru** — `KATEGORI` (10, urut daftar pemilik),
>   `IKON`, **`KOLOM_EKSTRA`** (kebijakan: edisi+revisi · instruksi_ktt & ibpr:
>   ketiganya · tujuh sisanya nihil — dibaca dari skema lama, bukan dikarang),
>   `EKSTENSI` (POSTER menerima gambar), scope `berlaku()` & **`senomor()`**.
>   `senomor()` dipusatkan karena TIGA tempat memakainya dan ketiganya harus
>   sepakat: penolakan nomor kembar, penurunan versi lama, penghapusan menyeluruh.
> - `InformasiController` **baru** — `index`/`create`/`perbarui`/`store`/`destroy`/`file`.
>   `store()` melayani Tambah **dan** Perbarui; yang membedakan hanya ada-tidaknya
>   `{induk}`. Penurunan versi lama + penulisan versi baru **satu transaksi**,
>   supaya tak pernah ada dua baris berlaku bernomor sama walau sesaat.
> - `StoreInformasiRequest` **baru** — aturan **per kategori**; nomor `prohibited`
>   pada Perbarui (diwarisi, tak bisa dipalsukan kiriman); magic bytes `%PDF-`.
> - View: `informasi/{index,create,perbarui}.blade.php` + `_form` (dipakai dua
>   halaman) + `_modal-hapus` (dua opsi). Kolom TABEL juga mengikuti kategori.
> - Menu **Informasi** collapsible 10 submenu, **di atas "Informasi Akun"** (BARU-2),
>   **tanpa `@can`** — [KUNCI] "diakses semua orang, semua departemen, semua jabatan".
> - `RolePermissionSeeder` — izin `informasi.manage`; Admin memegangnya lewat
>   `$permissions` penuh yang sudah ada (nol baris tambahan di `$roles`).
> - `tests/Feature/InformasiTest.php` **baru** — 18 test.
>
> **Berkas di disk `local`** (`informasi/{kategori}/{acak}`), bukan `public`.
> **Penghapusan = soft delete, berkas SENGAJA ditinggal** di disk: selama barisnya
> masih bisa dipulihkan, membuang berkasnya membuat pemulihan itu menghasilkan
> baris yang menunjuk berkas hilang. Ditandai `ponytail:` di controller —
> penyapunya menyusul bila ruang jadi masalah.
>
> **Belum dikerjakan, sesuai keputusan C6:** Informasi belum dibuka ke mobile.

### Intent

- **[KUNCI]** Diakses **semua orang, semua departemen, semua jabatan**.
- **[KUNCI]** **Upload saja** — tidak ada wizard, tidak ada alur tinjau–setuju.
- **[KUNCI]** 10 submenu: KEBIJAKAN, MEMO External, MEMO Internal, INSTRUKSI KTT,
  POSTER, MSDS, BAP, SERTIFIKAT & SIO, MOC & MPRP, IBPR.

### Rencana

**[BUKA]** Satu tabel atau sepuluh. `rencana:` **SATU** tabel + kolom `kategori`.
Alasannya: kesepuluh submenu berbeda hanya pada **kolom opsional mana yang dipakai**
— nomor, judul, dan berkas dimiliki semuanya. Sepuluh tabel = sepuluh model, sepuluh
controller, sepuluh set view untuk perbedaan nol. Ini juga cerminan aturan CLAUDE.md §3:
satu set view untuk semua jenis, perbedaannya dari data, bukan dari rute terpisah.

```
informasi
  id
  kategori         ENUM('kebijakan','memo_external','memo_internal','instruksi_ktt',
                        'poster','msds','bap','sertifikat_sio','moc_mprp','ibpr')  INDEX
  nomor            VARCHAR          -- `no_dokumen` di sistem lama
  judul            VARCHAR
  edisi            UNSIGNED INT NULL
  no_revisi        UNSIGNED INT NULL
  tanggal_efektif  DATE NULL
  file_path        VARCHAR          -- 'informasi/{kategori}/{uuid}.{ext}' di disk `local`
  file_mime        VARCHAR          -- POSTER bisa gambar, sisanya PDF
  berlaku          BOOLEAN default true INDEX   -- [B3] false = riwayat
  uploaded_by      FK users
  timestamps, softDeletes
  INDEX (kategori, nomor)          -- SENGAJA bukan UNIQUE, lihat [B3] di bawah
```

> ### ✅ [B1] TERJAWAB OLEH DATA, bukan oleh pendapat
>
> Skema sistem lama (`u805399352_db_prowork.sql`) memuat kesepuluh tabel itu, dan
> kolomnya **persis** seperti yang Anda daftarkan di brief. Tak ada lagi yang perlu ditebak:
>
> | Kategori | Tabel lama | nomor | judul | file | edisi | revisi | tgl efektif |
> |---|---|:-:|:-:|:-:|:-:|:-:|:-:|
> | KEBIJAKAN | `tb_kebijakans` | ✓ | ✓ | ✓ | ✓ | ✓ | — |
> | MEMO External | `tb_memos` | ✓ | ✓ | ✓ | — | — | — |
> | MEMO Internal | `tb_memo_internals` | ✓ | ✓ | ✓ | — | — | — |
> | INSTRUKSI KTT | `tb_instruksiktts` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
> | POSTER | `tb_posters` | ✓ | ✓ | ✓ | — | — | — |
> | MSDS | `tb_msds` | ✓ | ✓ | ✓ | — | — | — |
> | BAP | `tb_baps` | ✓ | ✓ | ✓ | — | — | — |
> | SERTIFIKAT & SIO | `tb_sertifikatsios` | ✓ | ✓ | ✓ | — | — | — |
> | MOC & MPRP | `tb_mocmprps` | ✓ | ✓ | ✓ | — | — | — |
> | IBPR | `tb_ibprs` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
>
> Union-nya persis skema di atas → **satu tabel + kategori terbukti tepat**, dan
> aturan "kolom mana wajib" ditegakkan **per kategori di Form Request**, bukan di skema.
>
> Dua catatan dari data nyatanya:
> - `edisi`/`revisi`/`tanggal_efektif` di sistem lama bertipe **`varchar`** (`revisi`
>   berisi `'03'`, `edisi` berisi `'4'`). Impor harus menormalkan ke integer/date,
>   dan yang gagal parse **ditolak dengan laporan**, bukan dipaksa jadi 0.
> - Data **POSTER berantakan**: `no_dokumen` berisi **judulnya**, bukan nomor
>   (mis. `'7 Manfaat Sarapan Buah-buahan'`). Diterima apa adanya saat impor —
>   POSTER memang tak bernomor resmi.
> - Kolom `views` di sistem lama **tidak dibawa**. SmartPro sudah punya
>   `document_reads` untuk itu; kolom penghitung kedua hanya akan berselisih dengannya.

**[B3] Revisi Informasi = unggah berkas baru, yang lama jadi riwayat.**
Karena itu `(kategori, nomor)` **tidak boleh UNIQUE** — satu nomor kebijakan akan punya
beberapa baris lintas edisi/revisi. Aturannya: mengunggah versi baru untuk nomor yang
sama **menurunkan `berlaku` baris sebelumnya menjadi `false`**, tanpa menghapusnya.
Daftar menampilkan yang `berlaku`; riwayat dibuka lewat satu tautan "Riwayat".
Penjaga yang tetap ada: dua baris **berlaku** dengan `(kategori, nomor)` sama = ditolak.

**[B2] Yang mengunggah: Admin saja.** Izin baru `informasi.manage`, hanya untuk role
Admin. Membaca terbuka bagi **semua akun aktif** tanpa izin apa pun, lintas 7
departemen — sesuai [KUNCI] "diakses oleh semua orang, semua departemen, semua jabatan".
Menambahkan SH/DH kelak = satu baris di seeder, tanpa perubahan kode.

**[KUNCI]** Berkas ditaruh di disk **`local`** (privat) + rute berpenjaga `auth`,
**bukan** disk `public`. Alasannya identik dengan butir 0: `routes/web.php:53-58`
menyajikan `storage/app/public/**` tanpa login.

### Titik kode terdampak

| Berkas | Perubahan |
|---|---|
| migration baru | tabel `informasi` |
| `app/Models/Informasi.php` | **baru** — konstanta kategori + label Indonesia |
| `app/Http/Controllers/InformasiController.php` | **baru** — `index`/`create`/`store`/`destroy` + `file()` |
| `app/Http/Requests/StoreInformasiRequest.php` | **baru** — aturan per kategori; validasi **magic bytes** `%PDF-`, jangan percaya mime klien |
| `resources/views/informasi/{index,create}.blade.php` | **baru** — SATU set view, kategori dari `?kategori=` |
| `routes/web.php` | blok rute baru di dalam `middleware('active')` |
| `resources/views/layouts/app.blade.php` | 723–891 | blok menu "Informasi" collapsible, ikuti pola `docMenu`/`berlakuMenu` yang sudah ada |
| `database/seeders/RolePermissionSeeder.php` | 42–48 | `informasi.manage` |

**Batas ukuran unggah:** `upload_max_filesize`/`post_max_size` XAMPP = **40M**.
Tampilkan batasnya di layar — gagal diam-diam karena `post_max_size` terlampaui adalah
kegagalan paling membingungkan bagi pengguna.

## B.5 Butir 6 — Format daftar induk adaptif + ekspor SH/DH — ✅ **SELESAI 2026-08-12**

> **Yang dikerjakan** (325 test hijau, naik dari 322):
> - `DocumentService::snapshot()` + `'edisi'` — dikerjakan SEKARANG selagi data
>   masih kosong, jadi 100% riwayat nyata kelak memilikinya.
> - `DocumentExportController` — izin `document.publish` **atau** `document.review`;
>   lingkup departemen SH/DH **dipaksa dari user**, tak pernah dari input.
>   `susunMatriks()` menelusuri rantai `revises_document_id` dalam memori atas
>   **satu** query (versi obsolete ikut diambil): tanggal tiap revisi ada di baris
>   versinya sendiri, dokumen yang berlaku hanya tahu tanggalnya sendiri.
> - Lebar adaptif persis aturan B4+B6: edisi lama penuh rev 0–4, **hanya edisi
>   terakhir dipotong** di revisi tertinggi yang benar-benar ada.
> - `export-excel.blade.php` — header **dua tingkat** (`colspan` blok Edisi +
>   `rowspan="2"` kolom identitas/tanggal), kolom dinamis, sel tak berlaku
>   **berstrip** `-`. Kolom "Tanggal Dibuat" diganti **"Tanggal Efektif"**
>   (= `published_at` akar rantai, tanggal dokumen pertama kali berlaku).
> - `routes/web.php` — penjaga `can:` **dilepas**; middleware tak bisa menyatakan
>   "salah satu dari dua izin", dan lingkup dept ditentukan izin mana yang dipegang.
> - `published.blade.php` — `@can` → `@canany`, tombol ekspor sampai ke SH/DH.
> - `DocumentExportTest` — dua test baru: SH/DH dept ICTMD **tak melihat** dokumen
>   SHE (PJO melihat, sebagai pembanding), dan kolom adaptif + strip diuji atas
>   rantai revisi Edisi 2 Rev 1 (`colspan="5"` Edisi 1, `colspan="2"` Edisi 2).
>   Test lama yang menyatakan SH/DH tertutup diperbarui — GL & MD tetap tertutup.
>
> **Catatan:** matriks dibaca dari **kolom dokumen** (`edisi`/`no_revisi`/
> `published_at`) di sepanjang rantai, bukan dari `snapshot_json` — baris versi
> menempel pada dokumen LAMA, jadi rantai harus ditelusuri toh, dan kolomnya lebih
> langsung daripada JSON. `edisi` tetap ditambahkan ke snapshot sebagai jejak audit.

### Format acuan (sudah dibaca dari `docs/TEMPLATE_DAFTAR_INDUK.xlsx`)

```
A1:O1   DAFTAR INDUK DOKUMEN SOP
A2:O2   PT PUTRA PERKASA ABADI SITE ADARO INDONESIA
(baris 3 kosong)
        ┌──────┬───────────────┬───────────────┬───────────────────┬───────────────────┬──────────┬──────────────┐
baris 4 │ No   │ Nomor Dokumen │ Judul Dokumen │      Edisi 1      │      Edisi 2      │ Tanggal  │ Tanggal      │
        │(4:5) │    (4:5)      │    (4:5)      │      (D4:H4)      │      (I4:M4)      │ Efektif  │ Terakhir     │
baris 5 │      │               │               │ 0 │r1│r2│r3│r4    │ 0 │r1│r2│r3│r4    │  (N4:N5) │ Revisi(O4:O5)│
        └──────┴───────────────┴───────────────┴───────────────────┴───────────────────┴──────────┴──────────────┘
data    │ 1    │ PPA-ADRO-…-01 │ Penggunaan…   │ tanggal per sel …                     │ tanggal  │ tanggal      │
```

Satu blok Edisi = **5 kolom** (rev 0, rev 1…rev 4) — persis sejalan roll-over
CLAUDE.md §7 (revisi 0..4, lalu Edisi+1 Rev 0). Template contoh punya **2** blok Edisi.
Sel yang tak berlaku dibiarkan **kosong** di template; **[KUNCI] pemilik meminta
diberi strip (`-`)** → ikut pemilik.

### Intent

- **[KUNCI]** Kolom **adaptif** terhadap dokumen dengan revisi terbanyak.
- **[KUNCI]** Dokumen yang revisinya lebih sedikit → selnya diberi **strip**.
- **[KUNCI]** Sel diisi **tanggal revisi**.
- **[KUNCI]** **SH/DH bisa ekspor** daftar induk, dan yang muncul **hanya dokumen
  departemennya**.

### Keadaan sekarang (terverifikasi)

- `app/Http/Controllers/DocumentExportController.php:23-53` — 7 kolom tetap
  (No, Nomor, Judul, Edisi, Revisi, Tanggal Dibuat, Tanggal Terakhir Revisi),
  lintas **semua** departemen, penjaga `document.publish` (PJO + Admin), keluaran
  **HTML ber-Content-Type Excel** (`.xls`), bukan .xlsx sungguhan.
- Rute: `routes/web.php:182-184`. Komentarnya menyebut *"SH/DH sengaja belum diberi"*
  — butir 6 **mencabut** ketetapan itu; komentarnya harus ikut diperbarui.
- Sumber tanggal revisi: `document_versions` — `no_revisi` (kolom) +
  `snapshot_json.published_at` (`DocumentService.php:163`).

### Celah data yang harus ditutup lebih dulu

`snapshot_json` **tidak merekam `edisi`** (`DocumentService.php:156-165`). Untuk peta
(Edisi, Revisi) → tanggal, edisi wajib ada. **Ini murah diperbaiki SEKARANG dan mahal
nanti:** butir 1 baru saja mengosongkan seluruh data, jadi menambahkan `edisi` ke
`snapshot_json` di Fase B berarti **100% data nyata** kelak memilikinya. Menundanya
ke Fase C berarti sebagian riwayat lahir tanpa edisi dan tak bisa dipulihkan.

### Rencana

**[KUNCI B4+B6] Lebar adaptif — dipotong sampai revisi tertinggi yang benar-benar ada.**
Patokannya **revisi**, bukan edisi: dokumen mana yang punya revisi terbanyak, sebanyak
itulah kolom rev yang dibangkitkan. Tiap Edisi punya rev 0–4 sendiri.

Menggabungkan kedua kalimat Anda menghasilkan satu **aturan turunan** yang perlu Anda
benarkan sekali, karena ia tak tertulis eksplisit di mana pun:

> Edisi yang **sudah selesai** selalu tampil **penuh rev 0–4** — menurut roll-over
> CLAUDE.md §7, sebuah edisi hanya naik setelah revisi 4 terlampaui, jadi edisi lama
> memang **pasti** punya kelimanya. Yang **dipotong** hanyalah **edisi TERAKHIR**,
> sampai revisi tertinggi yang benar-benar ada.
>
> Contoh: dokumen tertinggi = Edisi 2 Revisi 2 →
> `Edisi 1 [0|1|2|3|4]` + `Edisi 2 [0|1|2]` = **8 kolom revisi**, bukan 10.

→ **Blocking #3** (konfirmasi satu kalimat saja).

**[KUNCI B5] Keluaran tetap `.xls`, bukan CSV.** Anda menyerahkan pilihan ke yang
paling memudahkan → **pertahankan HTML-`.xls` yang sudah ada**
(`DocumentExportController.php:42-52`). Ia bekerja, nol dependensi baru, dan merge dua
tingkat (`colspan`/`rowspan`) adalah HTML biasa — persis yang dibutuhkan header
Edisi/rev bertingkat. **Keputusan tertutup**, tak perlu ditanyakan lagi.

> **Catatan skala:** impor nanti membawa **950 dokumen**. Satu berkas `.xls` berisi 950
> baris × belasan kolom masih wajar, tapi **saring per jenis** (sudah ada, parameter
> `{type}`) dan per departemen (butir 6) menjadikannya puluhan–ratusan baris per berkas.
> Itu memang bentuk yang dipakai orang.

**[KUNCI]** Lingkup ekspor:
- `document.publish` (PJO + Admin) → semua departemen, seperti sekarang.
- **SH/DH** → **hanya `department_id` miliknya**, dipaksa dari user, **tidak boleh**
  datang dari input. Departemen yang boleh ditentukan parameter = SH satu dept bisa
  menarik daftar induk dept lain.

### Titik kode terdampak

**Skema tabel: TIDAK ADA perubahan.** Hanya isi `snapshot_json` yang bertambah.

| Berkas | Baris | Perubahan |
|---|---|---|
| `app/Services/DocumentService.php` | 156–165 | `snapshot_json` + `'edisi'` |
| `app/Http/Controllers/DocumentExportController.php` | 23–53 | lingkup departemen; hitung matriks (edisi,revisi)→tanggal |
| `resources/views/documents/export-excel.blade.php` | seluruhnya | header 2 tingkat bermerge, kolom dinamis, strip |
| `routes/web.php` | 173–184 | izin: `document.publish` **atau** `document.review`; komentar diperbarui |
| `resources/views/documents/published.blade.php` | — | tombol ekspor terlihat oleh SH/DH |
| `tests/Feature/DocumentExportTest.php` | — | SH dept A tak melihat dokumen dept B |

## B.6 Checklist uji manual — Fase B

**Butir 2 (dikerjakan PERTAMA di fase ini — butir 0 & 8 bocor tanpanya)**

1. Jalankan pemeriksa data terlanjur (§B.3 langkah 3) → **harus kosong**; ada isinya = berhenti & lapor.
2. Buat dokumen dengan **toggle nomor manual** menyala, nomor `PPA-ADRO-SOP-ICTMD-99`
   → kirim → tinjau → setujui → **`doc_number_final` tetap `…-99`**.
3. Dokumen berikutnya jenis+dept yang sama **TIDAK** mendapat nomor 99.
4. Dokumen bernomor manual yang **bentrok** dengan dokumen lain → **ditolak saat pengesahan**, pesan jelas.
5. Dokumen bernomor **otomatis** → perilakunya tak berubah sama sekali (regresi).

**Butir 0**

6. Unggah satu dokumen lama (PDF + metadata) sebagai **GL** → muncul di **Dokumen Berlaku**.
7. Nomor berformat asing → badge **"Nomor Lama"** tampil.
8. **Lihat PDF** dari web → berkas asli yang diunggah, **bukan** hasil DomPDF.
9. Buka `/storage/arsip/...` langsung di peramban → **404** (berkas privat).
10. Buka PDF yang sama dari **aplikasi mobile** → berkas sama, bukan error/kosong.
11. Buka `documents/show`, `published`, `obsolete`, widget Distribusi, dan daftar induk
    atas dokumen arsip → **tak satu pun 500**.
12. **Ajukan Revisi** atas dokumen arsip → draft terbuka → isi → kirim → tinjau →
    setujui → **nomor tetap nomor lama**, edisi/revisi naik, arsip lama jadi Tidak Berlaku.
13. Draft hasil revisi itu ber-`arsip_path` **null** (versi baru bukan arsip).
14. **Musnahkan** dokumen arsip (Admin) → berkas PDF-nya ikut hilang dari disk.

**Butir 3**

15. Masuk sebagai **Non-Staff** → menu **Informasi** terlihat, kesepuluh submenu bisa dibuka & PDF-nya terbaca.
16. Masuk sebagai Non-Staff → **tidak ada** tombol Unggah/Hapus di mana pun.
17. Masuk sebagai pemegang `informasi.manage` → unggah KEBIJAKAN (nomor, judul, PDF, edisi, revisi) → muncul di daftar, PDF terbuka.
18. Unggah **POSTER** berupa gambar → tampil benar (bukan dipaksa PDF).
19. Unggah berkas **berekstensi .pdf tapi isinya bukan PDF** → **ditolak** (magic bytes).
20. **Tambah** dengan nomor **kembar** dalam kategori sama → **ditolak**, pesannya
    mengarahkan ke tombol Perbarui (keputusan 3-d).
20a. **Perbarui** baris itu → unggah berkas baru → konfirmasi muncul → versi lama
    menjadi **riwayat yang tersarang di bawah baris itu**, versi baru menempati barisnya
    dengan nomor sama (keputusan 3-g).
20b. Perbarui dokumen yang sama **4×** → tabel **tetap satu baris** per nomor; lencana
    "4 riwayat" muncul, dan mengkliknya membuka keempatnya **di bawah baris itu saja** —
    tak bercampur dengan riwayat dokumen lain.
20c. **Hapus** → modal memberi **dua** pilihan bila ada riwayat. "Versi ini saja" →
    riwayat terbaru **naik** jadi berlaku. "Seluruhnya" → nomor itu hilang dari daftar.
    Dokumen **tanpa** riwayat → modal hanya menawarkan **satu** pilihan.
20d. Masuk sebagai **GL** → tombol Tambah/Perbarui/Hapus **ada** (keputusan 3-f);
    sebagai **SH/DH** → tidak ada, dan `informasi/create` **403**.
20e. Buka **MEMO External** → formulir & tabel **tidak** memuat kolom Edisi/Revisi/
    Tgl Efektif; buka **INSTRUKSI KTT** → ketiganya ada dan wajib (keputusan 3-a).
21. Akses URL berkas informasi **tanpa login** → 404/redirect login, bukan berkasnya.
22. Non-Staff **departemen lain** tetap bisa membaca (informasi lintas departemen).

**Butir 6**

23. Ekspor daftar induk SOP sebagai **PJO** → header dua tingkat bermerge, blok Edisi sebanyak edisi tertinggi, semua departemen.
24. Ekspor sebagai **SH ICTMD** → **hanya** dokumen ICTMD; dokumen SHE tak muncul.
25. Ubah URL ekspor SH agar menunjuk departemen lain → **tetap** departemennya sendiri (atau 403).
26. Dokumen ber-revisi 3 dan dokumen ber-revisi 0 di daftar yang sama → sel yang tak berlaku **berstrip**, bukan kosong.
27. Buka berkas hasil ekspor di **Excel** → tanggal terbaca sebagai tanggal, teks Indonesia tidak rusak (BOM UTF-8).
28. Dokumen **arsip** (butir 0) **ikut muncul** di daftar induk.
29. `php artisan test` penuh hijau.

## B.7 Prompt eksekusi — Fase B

```
Kerjakan Fase B di docs/PLAN-MASTER.md. URUTAN WAJIB: 2 → 0 → 3 → 6.
Satu butir = satu commit. Berhenti & tunggu review di tiap butir.

Butir 2 — BACA HANYA: app/Http/Controllers/ApprovalController.php baris 60-110,
app/Services/DocumentNumberService.php, tests/Feature/DocumentNumberingTest.php.
LANGKAH PERTAMA sebelum edit apa pun: jalankan pemeriksa data terlanjur di §B.3.

Butir 0 — BACA HANYA: app/Http/Controllers/DocumentPdfController.php,
app/Http/Controllers/Api/DocumentFileController.php, config/filesystems.php,
app/Models/Document.php, app/Services/DocumentPurger.php,
app/Services/DocumentService.php (requestRevision + snapshot).
Percabangan arsip WAJIB di SATU service yang dipakai kedua controller.
Berkas di disk `local`, JANGAN `public`.

Butir 3 — modul baru, JANGAN sentuh satu pun berkas dokumen mutu.
SATU tabel + kolom kategori, SATU set view.

Butir 6 — BACA HANYA: app/Http/Controllers/DocumentExportController.php,
resources/views/documents/export-excel.blade.php, routes/web.php baris 173-184,
app/Services/DocumentService.php (snapshot).
Tambahkan 'edisi' ke snapshot_json SEBELUM data nyata menumpuk.

Selesai tiap butir: php artisan test penuh → commit → berhenti.
```

---
---

# BAGIAN C — Fase C: Alur Inti, Mobile & Impor Massal

**Status: DRAF**
**Prasyarat: Fase A DAN Fase B selesai & ter-commit.** Bukan formalitas —
butir 8 menyebut prasyaratnya sendiri: menu informasi (3), ekspor induk (6),
upload dokumen (0), dan fitur revisi (7). Butir 4 membekukan kontrak API, jadi ia
harus datang setelah permukaan API berhenti berubah.
**Cakupan butir: 7, 4, 8**

## C.1 Konvensi

- **[KUNCI]** = intent pasti pemilik. **[BUKA]** = terbuka, `rencana:` = default usulan.

## C.2 Butir 7 — Fitur revisi berlaku (otomatis & tombol) — ✅ **SELESAI 2026-08-12**

> **Yang dikerjakan** (328 test hijau, naik dari 326; **dua** test baru sudah
> DIBUKTIKAN GAGAL saat perubahannya dibatalkan):
>
> **7a — `no_revisi` naik saat DIKIRIM, bukan saat diajukan**
> - `Document::revisiSaatKirim()` **baru** — satu-satunya tempat aturan kenaikan
>   ditulis, dipakai bersama oleh pengiriman, keterangan di wizard, pesan
>   pengajuan revisi, default `no_rev` baris catatan, dan tombol isi-otomatis.
>   Angka yang dijanjikan di layar mustahil berbeda dari yang tersimpan.
> - Penjaganya: naik **hanya bila** draft masih memikul (edisi, revisi) versi
>   yang direvisinya. Menutup ketiga jalur yang melewati kirim lebih dari sekali
>   — kirim ulang sesudah ditolak, tarik-lalu-kirim, dan override manual.
>   Roll-over Edisi ikut pindah, jadi Edisi & Revisi selalu dari satu saat yang sama.
> - `DocumentService::requestRevision()` mewarisi edisi/revisi apa adanya;
>   `DocumentService::tandaiTerkirim()` **baru** menampung logika kirim yang
>   tadinya DIDUPLIKASI di `DocumentController::submit()` **dan**
>   `saveStep()` (dua jalur kirim yang sudah lama menyalin 15 baris yang sama).
>
> **7b — tombol "Revisi" di samping Preview**
> - `PdfRenderer::petaHalamanBab()` **baru**: `section_key` → [halaman awal,
>   akhir], diukur dari **PDF yang benar-benar dirender**. Mesin pengukurnya
>   dipakai ulang — `measureRowPageStarts()` dipecah, intinya jadi
>   `petaAnchorHalaman()`; **tak ada pengukur kedua**.
> - Tiap bab menanam DUA anchor 1pt (`[[S{2i}]]` di bilah bab, `[[S{2i+1}]]` di
>   isi terakhirnya) — hanya pada render pengukuran (`anchorBab`), tak pernah di
>   berkas yang diterima pengguna. Keduanya INLINE di dalam teks yang sudah ada;
>   elemen baru akan menambah tinggi, dan yang terukur bukan lagi halaman sebenarnya.
> - Offset cover dihormati: pada jenis ber-cover halaman 1 tak bernomor, jadi
>   angka yang diusulkan sama dengan yang TERCETAK di kop (diuji: `'1'`, bukan `'2'`).
> - `DocumentService::babBerubah()` + `baku()` — pembandingnya dokumen Berlaku itu
>   sendiri (tak bisa disunting siapa pun, jadi masih persis seperti saat snapshot).
>   Beda spasi/baris kosong BUKAN revisi; urutan item tetap dihitung sebagai perubahan.
> - Rute `documents.revisi.usulan` (baca-saja, `throttle:20,1`) di
>   `DocumentRevisionController` + trait otorisasi yang SAMA dengan wizard.
> - Tombolnya di footer (sebelah Preview) hanya pada langkah Log Revisi;
>   footer di luar cakupan `revisionLog`, jadi ia memanggil lewat
>   `$dispatch('isi-log-revisi')` — nol skrip kedua.
> - Menekan tombol berkali-kali tak menumpuk duplikat: baris yang **catatannya
>   masih kosong** dibuang lebih dulu, sehingga baris revisi terdahulu (catatannya
>   sudah terisi) selalu selamat.
>
> **Yang TIDAK dikerjakan, disengaja:** `ApprovalController` **tidak disentuh** —
> keputusan C1 memindahkan kenaikan ke **kirim**, jadi rencana lama "batalkan
> kenaikan bila isi identik saat pengesahan" gugur dengan sendirinya.
>
> **Batas yang diketahui:** menambah baris ke lembar Catatan Revisi bisa mendorong
> isi turun satu halaman, jadi angka Hal. adalah **usulan** — kolomnya tetap
> boleh disunting (sesuai mitigasi di tabel Risiko di bawah).

### Intent (dua permintaan terpisah, jangan digabung)

- **7a [BUKA]** Revisi 0 → 1 **otomatis bila terdeteksi ada ubahan**.
- **7b [KUNCI]** **Tombol "Revisi" di samping tombol Preview**, khusus di halaman
  pembuatan revisi, yang mengisi **halaman, nomor, dan tanggal secara otomatis**;
  **isi catatannya diketik manual**.

### Keadaan sekarang (terverifikasi)

- Langkah wizard "Log Revisi" hanya untuk draft revisi Tipe B **non-JSA**
  (`Document::usesRevisionLog()`, `app/Models/Document.php:331-342`).
- Formnya: `resources/views/documents/fields/_revision_log.blade.php` — komponen Alpine
  `revisionLog` (baris 57–67), tiap baris: **No. Rev** (`:35`), **Tanggal Rev** (`:39`),
  **Hal.** (`:43`), **Catatan** (`:47`). **Keempatnya diketik tangan sekarang.**
- Persist: `app/Services/DocumentWizard.php:45-67` → section `catatan_revisi`.
- Cetak: `resources/views/documents/print/_catatan_revisi.blade.php`, dipanggil
  `print/render.blade.php:161-162`.
- Panel pratinjau (tempat tombol baru duduk): `resources/views/documents/edit.blade.php:181`
  — iframe ber-`src` dipasang di HTML, dikelola komponen Alpine `wizard()`.
- `no_revisi` **sudah** dinaikkan lebih awal, saat Ajukan Revisi
  (`DocumentService::requestRevision()` → `nextEditionRevision()`,
  `app/Services/DocumentService.php:84,101`) — **bukan** saat pengesahan.

### 7a — dua pembacaan yang sangat berbeda

| Pembacaan | Artinya | Beratnya |
|---|---|---|
| **(i)** `no_revisi` naik **hanya bila** draft revisi benar-benar berbeda dari versi terbit; sama persis → nomor revisi tidak naik | Perbaikan ketelitian. Perbandingan dilakukan **saat pengesahan** terhadap `snapshot_json.contents` | Sedang — dapat diuji, dapat dibalik |
| **(ii)** Menyunting dokumen Berlaku **otomatis membuat revisi** tanpa "Ajukan Revisi" | Membongkar tata kelola: dokumen Berlaku memang **tidak** bisa disunting; revisi adalah keputusan sadar SH/DH/PJO (CLAUDE.md §6, §7) | Berat — melanggar aturan terkunci |

> ### ✅ [KUNCI C1] TERJAWAB — bacaan (i), dan titiknya pindah ke **KIRIM**
>
> Keputusan Anda: *"no revisi naik saat revisi sudah di kirim"* + *"nomor hal yg di
> revisi harus otomatis dengan mencek apakah ada ubahan yg dilakukan di field"*.
>
> Sekarang `no_revisi` naik **terlalu awal** — di `requestRevision()`
> (`DocumentService.php:84,101`), yaitu pada detik SH/DH menekan "Ajukan Revisi",
> sebelum GL menyentuh apa pun. Yang Anda minta: naik pada **submit**.
>
> **Yang berubah:** `requestRevision()` membuat draft dengan `edisi`/`no_revisi`
> **sama seperti versi terbit**; `nextEditionRevision()` dipanggil di
> `DocumentController::submit()`.
>
> **Tiga penjaga yang WAJIB ikut, kalau tidak ini melahirkan bug baru:**
> 1. **Kirim ulang setelah ditolak tidak boleh menaikkan lagi.** Alur Tipe A
>    (ditolak → perbaiki → kirim lagi) melewati `submit()` berkali-kali. Naikkan
>    **hanya bila** `no_revisi` draft masih sama dengan versi yang direvisinya.
> 2. **Withdraw lalu kirim lagi** juga melewati `submit()`. Penjaga yang sama menutupnya.
> 3. **Roll-over edisi ikut pindah**, jangan tertinggal di `requestRevision()` —
>    kalau terpisah, Edisi dan Revisi bisa berasal dari dua saat yang berbeda.
>
> **Efek samping yang harus Anda sadari:** selama draft revisi disusun, wizard dan
> pratinjau PDF akan menampilkan **nomor revisi LAMA** (mis. masih Rev 0, bukan Rev 1).
> Itu konsekuensi wajar dari "naik saat dikirim" — tapi ia terlihat di layar, jadi
> sebaiknya diberi keterangan kecil: *"Revisi akan menjadi 1 setelah dokumen dikirim."*

### 7b — tombol "Revisi" di samping Preview

**[KUNCI]** Yang otomatis: **halaman, nomor, tanggal**. Yang manual: **catatan**.

| Kolom | Sumber otomatis | Kepastian |
|---|---|---|
| **No. Rev** | `$document->no_revisi` — sudah tersedia, sudah jadi `placeholder` di `_revision_log.blade.php:35` | **Pasti.** Tinggal isi, bukan sekadar placeholder |
| **Tanggal Rev** | `now()` WITA (Asia/Makassar, CLAUDE.md §2) | **Pasti** |
| **Hal.** | nomor halaman cetak tempat bab yang berubah mendarat | **Perlu mesin** — lihat di bawah |

**"Hal." otomatis — mesinnya sudah ada.** `PdfRenderer::measureRowPageStarts()`
(`app/Services/Print/PdfRenderer.php:589`) sudah membaca **stream PDF nyata**,
membongkar `gzuncompress`/`gzinflate`, memungut literal teks, dan memetakan penanda
`[[R{i}]]` → nomor halaman. Teknik yang sama bisa memetakan **`section_key` → halaman**:
tanam anchor 1pt per bab saat render, ukur, buang. **Jangan tulis mesin kedua** — ia
akan berbeda dari hasil cetak sebenarnya dan `PrintLayoutTest` tak akan menangkapnya.

> ### ✅ [KUNCI C2] TERJAWAB — 1 perubahan = 1 baris; rentang ditulis `x - y`
>
> Sekali klik → **satu baris per perubahan** (satu `section_key` yang isinya berbeda
> dari versi terbit), masing-masing sudah ber-No.Rev, tanggal hari ini, dan **nomor
> halaman**. Bila satu perubahan memakan lebih dari satu halaman, kolom Hal. ditulis
> **`x - y`** (mis. `3 - 5`), bukan dipecah jadi beberapa baris. Kolom **Catatan**
> tetap kosong menunggu diketik — itu bagian yang memang keputusan manusia.
>
> Konsekuensi teknisnya: peta yang dibutuhkan bukan `section_key → halaman` melainkan
> **`section_key → [halaman awal, halaman akhir]`**. Mesin `measureRowPageStarts()`
> sudah mengembalikan halaman per penanda, jadi min & max dari penanda milik satu bab
> sudah cukup — tak perlu pengukur tambahan.

### Titik kode terdampak

**Skema tabel: TIDAK ADA perubahan.** Semuanya dari `document_versions` + render.

| Berkas | Baris | Perubahan |
|---|---|---|
| `app/Services/Print/PdfRenderer.php` | 575–640 | generalisasi pengukur anchor → peta `section_key` → halaman |
| `app/Services/DocumentService.php` | — | `bandingkanIsi(draft, versiLama)` → daftar `section_key` yang berubah |
| `app/Http/Controllers/ApprovalController.php` | 73–90 | (7a-i) batalkan kenaikan revisi bila isi identik |
| `resources/views/documents/fields/_revision_log.blade.php` | 30–67 | tombol + pengisian otomatis; **tetap** di komponen Alpine `revisionLog` yang sama |
| `resources/views/documents/edit.blade.php` | ~150–185 | letak tombol di sebelah Preview; **wajib** lewat komponen `wizard()` yang sudah ada, jangan pasang skrip kedua |
| rute baru | — | endpoint pengambil (bab berubah + peta halaman) untuk draft revisi |
| `tests/Feature/RevisionLogTest.php`, `RevisionTypeBTest.php`, `PrintLayoutTest.php` | — | pagar regresi |

### Risiko

| Risiko | Mitigasi |
|---|---|
| Nomor halaman meleset karena render ulang menggeser tata letak | Ukur dari **PDF yang benar-benar dirender**, bukan tebakan; tandai sebagai isian yang **boleh diedit** pengguna |
| (7a-i) salah menilai "tak berubah" → revisi resmi tak tercatat | Bandingkan `contentMap()` yang sudah dinormalkan, bukan JSON mentah; test khusus untuk beda spasi/urutan |
| JSA tak memakai lembar catatan revisi (`usesRevisionLog()` = false) | Tombolnya **tak boleh muncul** untuk JSA — pakai penjaga yang sama, jangan buat pengecualian baru |

## C.3 Butir 4 — Integrasi mobile app terhosting — ✅ **SELESAI 2026-08-12**

> **KOREKSI atas draf ini: butir 4 BUKAN hanya pekerjaan hosting.**
>
> Draf di bawah menulis *"API mobile sudah terbangun cukup lengkap … jadi butir 4
> bukan 'bangun API'"*. Itu benar untuk keadaan aplikasi **saat draf ditulis**.
> Sejak itu pemilik menambahkan layar-layar baru di `SmartPro-mobile`, dan
> `lib/config/api_config.dart` sendiri menandai tiga di antaranya sebagai
> **"BELUM ADA di server"**. Ditambah satu cacat yang tak tertulis di mana pun,
> jumlahnya jadi lima sambungan putus — semuanya gagal **DIAM-DIAM** (layar
> terbuka, isinya kosong), bukan dengan pesan galat.
>
> **Yang dikerjakan** (339 test hijau, naik dari 328):
>
> | Putus | Perbaikan |
> |---|---|
> | Avatar tak pernah tampil di HP | `UserResource::foto` jadi **absolut** lewat `url()`. `photoUrl()` mengembalikan `/storage/…` (relatif, sengaja — `filesystems.php`), dan `NetworkImage` menolaknya tanpa satu pun pesan |
> | Kotak Nomor HP & Email selalu kosong | `UserResource` + `no_hp`, `email` — dibaca `Pengguna.dariJson` sejak lama, tak pernah dikirim |
> | `PATCH /api/me` + `POST /api/me/foto` | `Api\ProfileController` **baru**; penyimpanannya lewat `App\Services\ProfilPengguna` **baru** yang juga dipakai `PageController::updateAccount` — satu aturan, dua kanal |
> | `POST /api/register` | `AuthController::register` + `Api\RegisterRequest` **baru**. Kode dept ("ICTMD") → id di `prepareForValidation()`, jadi aplikasi tak perlu mengunduh daftar departemen. **Tanpa token**: akun `pending` yang memegangnya hanya punya kredensial yang setiap pemakaiannya ditolak `isActive()` |
> | Arsip Tidak Berlaku | `?status=tidak_berlaku` (ber-`in:`, salah ketik ditolak 422 — bukan diam-diam jadi Berlaku), `Document::direvisiOleh()` **baru** → `digantikan_oleh`. Pagar `User::bisaLihatArsip()` **baru** dipasang di **TIGA** pintu: daftar, detail, DAN `Api\DocumentFileController` — layar arsip memanggil ketiganya berurutan untuk satu pratinjau PDF, jadi menambal satu saja berakhir 404 tepat di langkah terakhir |
>
> **Keputusan C6 DIBATALKAN pemilik sesi ini: Informasi DIBUKA ke mobile.**
> `Api\InformasiApiController` **baru** (baca-saja; unggah/hapus tetap di web).
> Penyaji berkasnya **tidak ditulis ulang** — `routes/api.php` menunjuk langsung
> `InformasiController::file()` yang sudah ada, lengkap `nosniff`+CSP+disk `local`.
> Di sisi aplikasi, **21 berkas** `INFORMASI/*.dart` + `INFORMASI/List/*.dart`
> (menunjuk endpoint server LAMA, semuanya mati) diganti **satu**
> `layar/daftar_informasi.dart`; kategorinya datang dari server, jadi menambah
> kategori kelak tak menuntut rilis aplikasi baru. Berkas lamanya sengaja
> **tidak dihapus**, hanya dilepas dari daftar rute `main.dart`.
>
> **Hosting:** `bootstrap/app.php` + `trustProxies(at: '*')` — Hostinger memutus
> TLS di proxy, dan tanpa ini SETIAP URL absolut yang dibangun server (termasuk
> `foto`) menyimpulkan skema `http`. `.env.example` diberi catatan APP_URL.
>
> **Base URL mobile:** `api_config.dart` bawaannya `http://192.168.100.187:9090`
> (uji lokal), dengan baris produksi `https://adw.proworkppa.com` **tertulis
> tepat di atasnya sebagai komentar** — tinggal ditukar. Ia sengaja BELUM jadi
> bawaan: domain itu saat ini masih melayani aplikasi LAMA, sehingga
> mengarahkan aplikasi ke sana sekarang bukan gagal terhubung melainkan gagal
> diam-diam — layar terbuka, isinya salah.
>
> **Dikunci** `tests/Feature/Api/KoneksiMobileTest.php` **baru** (11 test).
> **Terverifikasi di server nyata** (`php artisan serve --host=0.0.0.0 --port=9090`,
> curl dari IP LAN): login → `/api/me` mengirim foto absolut + no_hp + email →
> `/api/informasi` mengirim 10 kategori → `?status=tidak_berlaku` 200 →
> `register` NRP kembar 422. `flutter analyze lib` **nol error, nol warning**.
>
> **Yang TIDAK dikerjakan, disengaja:** `usesCleartextTraffic` di
> AndroidManifest **tetap menyala** — uji lokal memakai HTTP polos. Matikan saat
> host-nya sudah `https://` (tertulis di komentar `api_config.dart`). Rekomendasi
> rencana nomor 3 (CORS) tetap tak dikerjakan: tak ada target Flutter Web.

### Intent

- **[KUNCI]** Web akan **di-hosting**; aplikasi SmartPro mobile harus **terhubung** ke
  server itu, sehingga aplikasi yang berjalan langsung tersambung.

### Keadaan sekarang (terverifikasi)

API mobile **sudah terbangun cukup lengkap** — `routes/api.php` (95 baris):
login/logout/me (Sanctum Bearer), daftar & detail dokumen, masukan lapangan (GET+POST,
`throttle:10,1`), byte PDF (`files/{jenis}/{berkas}`), peninjauan (gate `review-access`),
komentar lampiran, dan pelaksanaan pekerjaan JSA (5 rute). `config/sanctum.php` ada.
`personal_access_tokens` sudah bermigrasi.

**Jadi butir 4 bukan "bangun API" — melainkan pekerjaan hosting & kontrak.**

> ### ✅ Jauh lebih ringan dari dugaan — sudah diperiksa di sumbernya
>
> Sumber Flutter ada di `C:\xamppnew\htdocs\SmartPro-mobile`, dan **base URL-nya
> TIDAK hardcoded**:
>
> ```dart
> // SmartPro-mobile/lib/config/api_config.dart:29-32
> static const String host = String.fromEnvironment(
>   'API_HOST',
>   defaultValue: 'http://10.7.110.91:9090',
> );
> ```
>
> Artinya cukup **build ulang dengan `--dart-define=API_HOST=https://domain-anda`** —
> nol perubahan kode. Seluruh layar menyusun URL-nya dari `apiUrl`/`publicUrl`
> (`:35,39`), jadi semuanya ikut sendiri.
>
> **Peta departemen 8-lama → 7-baru pun sudah ada** di berkas yang sama (`:44-52`):
> `COE→ICTMD`, `FALOG→FAW-SCM`, lima lainnya tetap. Ini juga yang dipakai butir 8 —
> jangan dikarang ulang.
>
> Yang tersisa: **HTTPS** (lihat di bawah) dan verifikasi ujung ke ujung.

### Rencana

1. **Base URL** → `--dart-define=API_HOST=https://…` saat build. Samakan dengan `APP_URL`
   di `.env` backend; kalau berbeda, tautan & logo pada email pemberitahuan menunjuk
   alamat yang salah — rusak yang tak terlihat dari sisi aplikasi (peringatan ini sudah
   tertulis di `api_config.dart:22-24`).

   > **⚠ `android:usesCleartextTraffic="true"` di AndroidManifest.**
   > Ia ada untuk mengizinkan HTTP polos saat pengembangan (`api_config.dart:8-12`).
   > Begitu pindah ke Hostinger ber-HTTPS, saklar itu **harus dimatikan** — kalau
   > tidak, aplikasi tetap bersedia mengirim token Bearer lewat HTTP polos, dan
   > satu salah ketik `http://` sudah cukup untuk membocorkannya di jaringan site.
2. **HTTPS wajib.** Token Bearer di HTTP polos = kredensial terbuka di jaringan site.
3. **CORS.** Flutter native tidak butuh CORS; **Flutter Web butuh**. Laravel 12 tak
   memasang `config/cors.php` secara default — perlu dipublikasikan **hanya bila** ada target web.
4. **`APP_URL`** (`.env.example:5` masih `http://localhost:8001`) harus jadi domain
   nyata: ia dipakai membangun URL absolut & EHLO surel.
5. **`SESSION_DOMAIN`** (`.env.example:38`) dibiarkan `null` — API memakai token,
   bukan sesi. Jangan diaktifkan tanpa alasan.
6. **`FILESYSTEM_DISK=local`** (`.env.example:41`) dipertahankan; berkas arsip (butir 0)
   & informasi (butir 3) memang harus privat.
7. **Kuota disk & batas unggah** di hosting diperiksa **sebelum** produksi:
   `upload_max_filesize`/`post_max_size` 40M, plus ruang untuk `pdf-cache` + `arsip` + `informasi`.

9. **[KUNCI C4] Hostinger — tiga hal yang khas shared hosting dan harus dicek lebih dulu:**
   - **`storage/app/private` WAJIB di luar `public_html`.** Di Hostinger, docroot adalah
     `public_html`. Kalau seluruh project di-upload ke sana apa adanya, `storage/`
     ikut terbuka lewat URL dan **seluruh arsip PDF (butir 0) + berkas Informasi
     (butir 3) bocor tanpa login** — persis kebocoran yang sudah kita hindari
     dengan memilih disk `local`. Yang benar: project di luar `public_html`, isi
     folder `public/` Laravel yang dipetakan ke sana.
   - **`storage:link` sering tak tersedia.** Sudah aman: `routes/web.php:53-58`
     menyajikan `avatars|lampiran` lewat PHP tanpa symlink. Jangan dihapus.
   - **Tanpa queue worker & dengan `max_execution_time` ketat.** Ini yang membuat
     impor 950 dokumen (butir 8) **wajib** lewat Artisan di lokal, bukan lewat web
     di Hostinger.
8. **[BUKA]** Apakah menu Informasi (butir 3) ikut dibuka ke mobile.
   `rencana:` **belum** — tambahkan hanya bila diminta. Menambah rute API setelah
   aplikasi terpasang menyebar itu murah; **mengubah** yang sudah dipakai itu mahal.

**Skema tabel: TIDAK ADA perubahan.**

### Risiko

| Risiko | Beratnya | Mitigasi |
|---|---|---|
| Kontrak API membeku di tangan pengguna | **Tinggi** | Karena itu butir 4 SETELAH 0/3/7 — jangan dimajukan |
| Bentuk balasan dirapikan → aplikasi terpasang rusak | **Tinggi** | `routes/api.php` sudah memperingatkan: *"jangan dirapikan, aplikasinya yang jadi rusak"*. Hormati |
| Token Bearer di HTTP polos | **Tinggi** | HTTPS wajib sebelum aplikasi diarahkan ke server |
| `smartpro:renew` terbawa ke server produksi | **Tinggi** | Penjaga `production` sudah ada (`RenewData.php:41-45`) — jangan dilonggarkan, pastikan `APP_ENV=production` |

## C.4 Butir 8 — Impor massal PDF + SQL

### Intent

- **[KUNCI]** Dikerjakan **setelah** butir 3, 6, 0, dan 7 selesai.
- Sumber: **salinan project htdocs** berisi berkas PDF **dan** SQL.
- Pertanyaan pemilik: **lewat web (massal / per 10-20)** atau **lewat backend saja?**

### Jawaban atas pertanyaan itu

**Keduanya, dan pemisahnya adalah "sekali" vs "berulang":**

| Jalur | Untuk apa | Kenapa |
|---|---|---|
| **Backend (Artisan)** | **Migrasi awal sekali jalan** dari salinan project lama | Ratusan dokumen + PDF, dijalankan orang teknis, di server, bisa diulang & di-rollback. Web SAPI akan menabrak `max_execution_time` dan `post_max_size` 40M jauh sebelum selesai |
| **Web (.xlsx + unggah bertahap)** | **Pemakaian sehari-hari sesudahnya**, oleh GL, terkunci ke departemennya | Migrasi sekali jalan tak boleh menjadi satu-satunya jalan masuk dokumen lama; GL harus bisa menambah sendiri tanpa akses terminal |

**Dan sumbernya bukan `.xlsx` sama sekali — sumbernya adalah SQL.** Itu menyederhanakan
banyak hal: `phpoffice/phpspreadsheet` **tidak diperlukan** untuk migrasi awal.

`rencana:` **Artisan membaca langsung dari DB lama** (impor `u805399352_db_prowork.sql`
ke satu database sementara di XAMPP, lalu `php artisan smartpro:impor-lama --dry-run`),
**web + .xlsx untuk pemakaian sehari-hari sesudahnya**. Yang menulis DB **satu service
yang sama**, dipakai keduanya. Presedennya sudah ada di repo ini: `RenewData` (Artisan)
dan `DocumentPurger` (service dipakai controller) hidup berdampingan tanpa saling menyalin.

### Sumber lama — sudah dibaca, ini isinya

| Yang diimpor | Dari tabel | Baris |
|---|---|---|
| SOP | `tb_sop_{8 dept}` | 195 |
| IK | `tb_ik_{8 dept}` | 404 |
| SP | `tb_sp_{8 dept}` | 88 |
| JSA | `tb_jsa_{8 dept}` | 263 |
| **Dokumen mutu** | | **950** |
| MEMO External | `tb_memos` | 89 |
| POSTER | `tb_posters` | 44 |
| INSTRUKSI KTT | `tb_instruksiktts` | 10 |
| KEBIJAKAN / MSDS | `tb_kebijakans`, `tb_msds` | 1 + 1 |
| **Informasi** | | **145** |
| **TOTAL** | | **1.095** |

Kolomnya (`tb_sop_shes` dst.): `no_dokumen`, `judul_sop`, `file_sop`, `edisi`, `revisi`,
`tanggal_efektif` — **persis daftar butir 0 opsi (a)**. Tabel `tb_fk_*` dan `tb_px_*`
juga ada di dump; **belum diketahui apa isinya** dan sengaja **tidak** dijadwalkan.

**Peta departemen 8 → 7 sudah ada**, jangan dikarang:
`SmartPro-mobile/lib/config/api_config.dart:44-52` — `coes→ICTMD`, `falogs→FAW-SCM`,
`shes→SHE`, `plants→PLANT`, `hcgas→HCGA`, `produksis→PRODUKSI`,
`engineerings→ENGINEERING`. **`opds` (2 dokumen) tak punya padanan** → **blocking #6**.

> ### ⛔ PEMBLOKIR — berkas PDF-nya tidak ikut di salinan lokal
>
> Kolom `file`/`file_sop` menyimpan nama teracak
> (mis. `PQf3MqOwSLtmHd2SWVdbgIMRw3DTQIxjkAd0dtr8.pdf`). Berkas itu **tidak ada** di
> salinan `Sistem_managemen_dokumen_prosedur`: seluruh project hanya memuat **34 PDF**,
> dan **tak ada folder `kebijakan/`, `memo/`, `poster/` sama sekali**.
>
> Mengimpor sekarang menghasilkan **1.095 baris yang setiap "Lihat PDF"-nya 404** —
> dan karena statusnya langsung Berlaku, kerusakan itu langsung terlihat 7 departemen.
>
> **Penangkal WAJIB:** ambil folder `storage/app/public/**` project lama dari server
> Hostinger-nya (zip/FTP) **sebelum** impor dijalankan. Kalau berkas tak bisa didapat,
> impor tetap mungkin **dengan syarat** dokumen tanpa berkas masuk ke daftar
> "Belum Ada Berkas" (§B.2 opsi d butir 1) dan **tidak** menawarkan tombol Lihat PDF —
> bukan 404. → **blocking #7**.

### Rencana

1. **Selalu `--dry-run` dulu.** Laporan per baris: akan ditulis / ditolak + alasan.
   Nol tulisan sebelum laporan bersih.
2. **Satu `DB::transaction`, semua-atau-tak-satu-pun** per batch.
3. Tiap baris jadi: `status='published'`, `published_at`=tanggal efektif,
   `doc_number`=nomor lama, `doc_number_manual=true`,
   **`doc_number_final`=nomor lama (dikunci langsung** — dokumen ini memang sudah
   berlaku; inilah yang bergantung pada **butir 2**), `reviewer_id`/`approver_id` null,
   `arsip_path` diisi bila PDF-nya ikut (**butir 0**).
4. Validasi per baris: nomor unik (`DocumentNumberService::isUnique`), jenis dikenal
   & aktif, tanggal sah dan **tidak di masa depan**, tak ada nomor kembar **di dalam
   sumbernya sendiri**.
5. **`import_batch` (uuid)** dibagikan seluruh baris satu batch → membuka
   **Batalkan Batch** oleh Admin. Tanpa ini, impor 200 dokumen yang salah harus
   dibersihkan satu per satu.
6. Audit log **per dokumen** + notifikasi ringkas ke SH/DH departemen. Impor melewati
   alur tinjau–setuju, jadi jejaknya harus tebal.
7. **Pencocokan PDF ↔ dokumen** lewat nomor yang dinormalkan (huruf besar, buang
   non-alfanumerik), dibandingkan **token penuh**, bukan `str_contains` — kalau tidak,
   `…-01` akan tercocokkan ke berkas `…-010`.

### Spec teknis

```
documents
  + import_batch  CHAR(36) NULL INDEX   -- uuid per batch; NULL = bukan hasil impor
```

Ini kolom kedua dan terakhir yang butir 0+8 tambahkan ke `documents`
(yang pertama: `arsip_path` di Fase B).

| Berkas | Perubahan |
|---|---|
| migration baru | `documents.import_batch` |
| `app/Services/ArsipImporter.php` | **baru** — baca → validasi → laporkan → tulis. Satu-satunya penulis DB |
| `app/Console/Commands/ImporArsip.php` | **baru** — pembungkus Artisan + `--dry-run`; **tiru pola `RenewData`**, termasuk penjaga `production` bila perlu |
| `app/Http/Controllers/DocumentImportController.php` | **baru** — unggah, pratinjau, commit, batalkan batch |
| `app/Services/DocumentPurger.php` | Batalkan Batch memakai jalur pemusnahan yang sudah ada, jangan tulis penghapus kedua |

### Risiko

| Risiko | Beratnya | Mitigasi |
|---|---|---|
| **Menerbitkan ratusan dokumen tanpa alur persetujuan** | **Tinggi (tata kelola)** | Empat lapis: audit per dokumen, muncul di feed dashboard, notifikasi SH/DH, **Batalkan Batch** oleh Admin; plus larangan tanggal efektif di masa depan |
| Nomor lama bentrok dengan nomor yang sudah terbit | **Tinggi** | `isUnique()` per baris **plus** deteksi kembar di dalam sumbernya sendiri; bentrok = batalkan **seluruh** batch |
| Skema salinan htdocs ≠ skema sekarang | **Tinggi** | **Blocking #7**; `--dry-run` wajib sebelum tulisan pertama |
| `post_max_size` 40M → unggah 200 PDF via web pasti gagal | Sedang | Jalur Artisan untuk migrasi awal; web bertahap (mis. 10 berkas) + batasnya ditampilkan di layar |
| Ruang disk | Sedang | ±1 MB/dokumen; 200 dokumen ≈ 200 MB + `pdf-cache` + `informasi`. Cek kuota **sebelum** produksi |
| Nomor berformat asing tak memesan urutan (`seqOf('PPA/ADRO/SOP/07/2019')` membaca 2019) | Rendah | Nomornya sendiri tetap tak kembar; yang hilang hanya pemesanan urutan → tampilkan sebagai **peringatan** di pratinjau, bukan penghalang |

## C.5 Checklist uji manual — Fase C

**Butir 7**

1. Ajukan Revisi atas dokumen Berlaku → di langkah **Log Revisi**, tombol **Revisi**
   muncul di samping **Preview**.
2. Klik tombol → baris terisi **No. Rev**, **Tanggal** (hari ini, **WITA**), **Hal.**;
   kolom **Catatan** kosong.
3. Nomor halaman yang terisi **cocok** dengan halaman di PDF hasil unduhan.
4. Isi catatan → simpan → kirim → tinjau → setujui → baris itu **tercetak** di lembar
   CATATAN REVISI halaman depan.
5. Revisi **berikutnya** atas dokumen yang sama → baris revisi terdahulu **tetap
   terbawa** dan ikut tercetak (akumulasi lintas revisi).
6. Buka draft revisi **JSA** → tombol Revisi **tidak muncul**, dan tak ada langkah Log Revisi.
7. (7a-i, bila disetujui) Ajukan Revisi lalu **tanpa mengubah apa pun** → kirim → setujui
   → `no_revisi` **tidak naik**.
8. Ajukan Revisi, ubah **satu kata** → setujui → `no_revisi` naik dan roll-over
   Edisi berjalan benar pada revisi ke-5.

**Butir 4**

9. Dari HP di jaringan **nyata** (bukan localhost): login → daftar dokumen terisi.
10. Buka PDF dokumen biasa → tampil; buka PDF dokumen **arsip** → tampil.
11. Kirim masukan lapangan dari HP → muncul di widget dashboard SH/DH di web.
12. Tinjau dokumen dari HP sebagai SH → keputusannya muncul di web.
13. Pelaksanaan pekerjaan JSA (5 rute) → **tak satu pun** berubah perilakunya (regresi kontrak).
14. Akses API lewat **HTTP polos** → ditolak/dialihkan ke HTTPS.
15. `APP_ENV=production` → `php artisan smartpro:renew` **ditolak**.

**Butir 8**

16. `--dry-run` atas sumber nyata → laporan per baris, **nol** baris tertulis ke DB.
17. Sumber bernomor **kembar** → ditolak **utuh** (rollback, nol baris tertulis).
18. Impor sungguhan → dokumen muncul di **Dokumen Berlaku** dengan badge "Nomor Lama"
    bila formatnya beda.
19. Nomor hasil impor **tetap** nomor lama setelah impor (bergantung butir 2).
20. Dokumen hasil impor **muncul di daftar induk** (butir 6) di baris & kolom yang benar.
21. Dokumen berikutnya yang dibuat GL **tidak** mendapat nomor yang sudah dipakai hasil impor.
22. **Batalkan Batch** (Admin) → seluruh dokumen batch itu hilang, berkas PDF-nya ikut hilang.
23. **Ajukan Revisi** atas dokumen hasil impor → alur penuh sampai disahkan berhasil.
24. `php artisan test` penuh hijau.

## C.6 Prompt eksekusi — Fase C

```
Kerjakan Fase C di docs/PLAN-MASTER.md. URUTAN WAJIB: 7 → 4 → 8.
Satu butir = satu commit. Berhenti & tunggu review di tiap butir.

Butir 7 — BACA HANYA: resources/views/documents/fields/_revision_log.blade.php,
resources/views/documents/edit.blade.php baris 140-200,
app/Services/DocumentWizard.php, app/Services/Print/PdfRenderer.php baris 575-660,
app/Http/Controllers/ApprovalController.php baris 60-110.
Peta section→halaman WAJIB memakai ulang mesin measureRowPageStarts, jangan tulis pengukur kedua.
Tombol WAJIB lewat komponen Alpine wizard()/revisionLog() yang sudah ada.
JANGAN kerjakan 7a sebelum blocking #4 dijawab.

Butir 4 — konfigurasi & hosting, bukan fitur. JANGAN mengubah bentuk balasan
rute mana pun di routes/api.php. JANGAN dimulai sebelum blocking #6 dijawab.

Butir 8 — BACA HANYA: hasil butir 0, app/Services/DocumentNumberService.php,
app/Console/Commands/RenewData.php (pola Artisan), app/Services/DocumentPurger.php.
Penulisan DB HANYA di satu service, dipakai Artisan maupun web.
--dry-run WAJIB jalan sebelum tulisan pertama. JANGAN dimulai sebelum blocking #7 dijawab.

Selesai tiap butir: php artisan test penuh → commit → berhenti.
```

---
---

# Urutan eksekusi final

```
FASE A  (aman — nol logika inti)
  1. Butir 5   UI widget komentar lapangan          [tanpa prasyarat]
  2. Butir 1   Grouping storage + hapus data        [gerbang bagi butir 6]
      ↓ commit, review
FASE B  (aditif + satu perbaikan bug)
  3. Butir 2   Nomor manual bertahan                [WAJIB sebelum 0 & 8]
  4. Butir 0   Dokumen lama (PDF) — opsi (a)        [butuh 2]
  5. Butir 3   Menu Informasi                       [modul terpisah]
  6. Butir 6   Daftar induk adaptif + SH/DH         [butuh 1; +edisi ke snapshot]
      ↓ commit, review
FASE C  (mengusik inti / ke luar / massal)
  7. Butir 7   Revisi otomatis + tombol             [butuh 0, 2]
  8. Butir 4   Integrasi mobile terhosting          [butuh 1, 3 — bekukan kontrak]
  9. Butir 8   Impor massal                         [butuh 0, 2, 3, 6, 7]
```

Urutan **di dalam** Fase B tidak boleh diacak: butir 2 harus pertama, sebab tanpa
penguncian nomor manual, dokumen lama dari butir 0 kehilangan nomornya tepat saat
menjadi Berlaku — dan gejalanya baru terlihat berhari-hari kemudian.

# Putusan kesiapan per fase

Hasil pemeriksaan ulang rencana ini **terhadap kodebase**, bukan terhadap dirinya
sendiri. Tanggal pemeriksaan sama dengan tanggal draf.

## Fase A — **SIAP, setelah dua tambalan**

| # | Temuan | Beratnya | Status |
|---|---|---|---|
| A-1 | `limit(4)` di `DashboardController:401,417` membuat butir 5 **mustahil** seperti tertulis | **Pemblokir** | ✅ ditambal di §A.2 — batas naik ke konfigurasi |
| A-2 | `smartpro:renew` meninggalkan 7 tabel yatim + `AUTO_INCREMENT` direset → yatim menempel ke dokumen/orang **berbeda**; token Sanctum lama masuk sebagai user salah | **Tinggi** | ✅ **GUGUR** — jawaban A1 membuat perintah itu bukan alatnya. Kita pakai `DocumentPurger` yang sudah ada; nol `TRUNCATE`, nol reset id, user & audit log tak tersentuh |
| A-3 | `DocumentPurger::buangSinggahanPdf()` dipanggil **sesudah** `forceDelete()`, tak bisa tahu `{JENIS}` | Sedang | ✅ ditambal — tangkap `$jenis` sebelum transaksi |
| A-4 | `sapuSinggahan()` datar → berkas lama & baru sama-sama luput | Sedang | ✅ ditambal — sapu dua bentuk jalur |
| A-5 | `{JENIS}` masuk jalur berkas tanpa saringan | Rendah | ✅ ditambal — satu baris `preg_replace` |
| A-6 | Penghapusan tanpa undo, dan **backup belum ada** | **Prosedural** | ⛔ **blocking #2** — `mysqldump` wajib lebih dulu |

**Kesimpulan:** butir 5 dan langkah 1–5 butir 1 **boleh dikerjakan sekarang, hari ini**.
Langkah 6 (penghapusan dokumen) **ditahan** sampai blocking #1 dijawab **dan** backup ada.
Fase A tidak bergantung pada apa pun di luar repo — **dapat terealisasikan penuh**, dan
kini **lebih aman dari draf pertama** karena alat penghapusnya berganti.

## Fase B — **HAMPIR SIAP PENUH; satu konfirmasi kalimat tersisa**

| # | Temuan | Beratnya | Status |
|---|---|---|---|
| B-1 | Butir 2 adalah bug hidup di jalur pengesahan, bukan fitur | **Tinggi bila dilewat** | ✅ sudah jadi butir pertama Fase B |
| B-2 | Butir 0: disk `public` tersaji **tanpa login** (`routes/web.php:53-58`) | **Tinggi** | ✅ berkas arsip ke disk `local` |
| B-3 | Butir 0: **dua** penyaji PDF (web + mobile); menambal satu = dokumen kosong di HP | Sedang | ✅ percabangan dipusatkan di satu service |
| B-4 | Butir 6: `snapshot_json` **tak merekam `edisi`** (`DocumentService.php:156-165`) → peta (Edisi,Revisi)→tanggal mustahil | Sedang | ✅ ditambahkan di Fase B, saat data masih kosong pasca-butir 1 |
| B-5 | Butir 6: gate usulan `document.publish` **atau** `document.review` = PJO+Admin+SH+DH, dan **bukan** GL/MD/Non-Staff | — | ✅ **terverifikasi** di `RolePermissionSeeder.php:55-58,62-66,77-81,91-95,98-101` |
| B-6 | Butir 3: unggahan berkas oleh pengguna | Sedang | ✅ magic bytes + disk `local` + batas 40M diuji |

**Kesimpulan:** butir 2, 0, **dan 3** kini **dapat langsung dikerjakan** begitu Fase A
selesai — kolom Informasi tak lagi tebakan, ia terbaca dari 10 tabel sistem lama, dan
hak unggahnya sudah dikunci ke Admin. Butir 6 tinggal menunggu **satu konfirmasi
kalimat** (blocking #3). Fase B **naik dari "tertahan" menjadi hampir siap penuh.**

## Fase C — **TERBUKA kecuali butir 8** (menunggu berkas PDF server lama)

| # | Temuan | Beratnya | Status |
|---|---|---|---|
| C-1 | Butir 7: `measureRowPageStarts()` **private** & terikat penanda khas JSA (`'FORMULIR'`, `'R'`) di `PdfRenderer.php:589`; menggeneralisasikannya = menyentuh mesin cetak yang dikunci `PrintLayoutTest` | **Tinggi** | ⚠ **penangkal:** tambahkan **parameter**, jangan ubah perilaku default; jalankan `PrintLayoutTest` sebelum & sesudah, bandingkan |
| C-2 | Butir 7a: `no_revisi` pindah dari `requestRevision()` ke `submit()` | **Tinggi** | ✅ **terjawab** — tiga penjaga wajib (kirim ulang, withdraw, roll-over) tertulis di §C.2 |
| C-3 | Butir 4: sumber Flutter & sifat base URL | — | ✅ **terjawab & jauh lebih ringan** — `--dart-define=API_HOST`, nol perubahan kode |
| C-4 | Butir 8: **berkas PDF 1.095 dokumen tidak ada di salinan lokal** | **Pemblokir** | ⛔ **blocking #4** |
| C-5 | Butir 8: `opds` tak punya departemen padanan; `tb_fk_*`/`tb_px_*` tak dikenali | Sedang | ⛔ **blocking #5, #6** |
| C-6 | Butir 8 menulis **950 + 145 baris** `published` melewati alur tinjau–setuju | **Tinggi** | ✅ `--dry-run` wajib + `import_batch` + Batalkan Batch + audit per dokumen |
| C-7 | Hostinger shared: `storage/` bisa terbuka di bawah `public_html` | **Tinggi** | ⛔ **blocking #7** — struktur deploy ditentukan dulu |

**Kesimpulan:** butir 7 **kini bisa dikerjakan** — kedua pertanyaannya sudah dijawab, dan
satu-satunya risiko tersisa (C-1) adalah risiko teknis yang penangkalnya sudah tertulis.
Butir 4 tinggal HTTPS + rebuild. **Butir 8 tetap terkunci** sampai berkas PDF-nya ada.

## Ringkasan satu kalimat

**Fase A boleh dikerjakan hari ini** (langkah penghapusan menunggu backup). **Fase B
hampir siap penuh** — kolom Informasi kini datang dari data, bukan tebakan. **Fase C
terbuka kecuali butir 8**, yang menunggu berkas PDF dari server lama.

---

# Pertanyaan blocking — SISA setelah jawaban 2026-08-11

Dari tujuh pertanyaan draf pertama, **empat sudah tertutup** oleh jawaban Anda
(kolom Informasi, hak unggah, format `.xls`, sumber Flutter). Yang tersisa di bawah
ditambah tiga temuan baru dari membaca sumber lama.

Ditandai fase mana yang ditahan masing-masing. **Fase A boleh dikerjakan hari ini**
kecuali langkah penghapusan data.

---

**#1 — ✅ TUTUP 2026-08-12. Dijawab (b): seluruh dokumen, apa pun statusnya.**
**#2 — ✅ TUTUP 2026-08-12.** `backup-sebelum-hapus-20260812.sql`, 95 KB, 22 tabel
berisi data (`users` & `documents` termasuk), di luar Git. Penghapusan sudah dijalankan.

*(Teks asli kedua pertanyaan dipertahankan di bawah sebagai jejak alasan.)*

**#1 — [Fase A, menahan langkah 6 saja] Cakupan penghapusan: harfiah atau seluruhnya?**

Anda bilang *"hapus data pdf dan dokumen berlakunya saja"*. Dua bacaan:

- **(a)** Harfiah: hanya dokumen berstatus **Berlaku**. Draft, Menunggu Ditinjau,
  Ditolak, dan Tidak Berlaku **tetap ada**.
- **(b)** Seluruh dokumen apa pun statusnya — karena ini DB pengembangan dan
  **950 dokumen nyata** akan menggantikannya.

`rencana:` **(b)**. Dengan (a), sisa dokumen uji akan bercampur dengan hasil impor dan
ikut menempati nomor urut — lalu tak ada lagi cara membedakan mana dokumen uji.
User, audit log, departemen, dan jenis dokumen **tidak tersentuh** di kedua bacaan.

**#2 — [Fase A, menahan langkah 6 saja] Backup sudah dibuat?**

Anda bilang belum ada. Perintahnya (sesuaikan nama DB dengan `DB_DATABASE` di `.env`):

```
C:\xamppnew\mysql\bin\mysqldump.exe -u root smartpro_refactor > backup-sebelum-hapus.sql
```

Saya tidak akan menjalankan penghapusan sebelum Anda konfirmasi berkasnya ada dan
ukurannya masuk akal (bukan 0 KB). Ini bukan formalitas — penghapusan tak punya undo.

**#3 — [Fase B/C] Contoh dokumen FK (Formulir Kerja) & PX (Prosedur External).**

Anda minta keduanya jadi jenis dokumen baru dengan sub-menu di "Dokumen Baru". Dua
pekerjaan berbeda di baliknya:

- **Sebagai ARSIP** (unggah PDF + metadata) — **bisa dikerjakan tanpa bahan apa pun**,
  karena di sistem lama FK & PX memang hanya PDF + metadata.
- **Sebagai dokumen yang DISUSUN di wizard** — butuh **schema JSON** (bab-babnya).
  Schema itu tak ada di mana pun; dump lama tak menyimpannya. CLAUDE.md §4 melarang
  saya mengarangnya.

Yang saya perlukan: **satu contoh FK dan satu contoh PX** (PDF atau Word yang sudah
jadi), supaya bab-babnya dibaca, bukan ditebak. Taruh di `docs/` seperti `docs/pdflama`.

Sementara menunggu: saya siapkan FK & PX sebagai **jenis dokumen untuk penomoran,
daftar induk, dan arsip** — bagian itu tak menunggu apa-apa.

**#4 — [Fase C, butir 8] BERKAS PDF-nya di mana?**

Ini pemblokir terbesar yang tersisa. Dump memuat **1.095 baris** metadata, tapi
salinan lokal `Sistem_managemen_dokumen_prosedur` hanya berisi **34 PDF**, dan folder
`kebijakan/`, `memo/`, `poster/` **tidak ada sama sekali**. Nama berkas di kolom `file`
tidak cocok dengan satu pun berkas yang ada di sana.

Berkasnya ada di server Hostinger project lama. **Bisakah Anda mengambil folder
`storage/app/public/**` project lama itu** (zip lewat File Manager / FTP)?

Kalau **tidak bisa**: impor tetap jalan, tapi 1.095 dokumen masuk sebagai
"Belum Ada Berkas" — terdaftar & bernomor, tanpa tombol Lihat PDF. Bukan 404, tapi juga
belum bisa dibaca orang. Konfirmasikan mana yang Anda pilih.

**#5 — [Fase C, butir 8] Nomor FK & PX mengikuti pola yang mana?**

FK & PX ikut diimpor (39 + 147 baris). Penomoran SmartPro berpola
`PPA-ADRO-{JENIS}-{DEPT}-{NN}`. Kode jenisnya **`FK`** dan **`PX`**, atau ada singkatan
resmi lain yang dipakai PT PPA? Ini masuk ke nomor dokumen yang tercetak dan tak enak
diubah belakangan.

`rencana:` `FK` dan `PX` — sama dengan nama tabel lamanya. Nomor lama yang berformat
beda tetap diterima apa adanya dengan badge "Nomor Lama".

**#6 — [Fase C, butir 8] Departemen `opds` mau dikemanakan?**

8 departemen lama, 7 baru. Peta tujuhnya sudah ada di aplikasi mobile
(`api_config.dart:44-52`). **`opds` (2 dokumen: SOP/IK/SP/JSA gabungan) tak punya
padanan.** Dipetakan ke departemen mana, atau dilewati saja?

`rencana:` lewati dan laporkan — 2 dokumen lebih murah dimasukkan manual daripada
salah departemen selamanya.

**#7 — [Fase C, butir 4] Paket Hostinger yang mana?**

Shared hosting atau VPS? Versi PHP-nya? Kuota disknya berapa? Tiga hal yang bergantung
padanya:
- **`storage/` harus berada DI LUAR `public_html`.** Kalau tidak, seluruh arsip PDF
  (butir 0) dan berkas Informasi (butir 3) terbuka tanpa login — kebocoran yang
  justru sedang kita hindari dengan memilih disk `local`.
- **HTTPS** wajib sebelum aplikasi mobile diarahkan ke sana (token Bearer).
- **Kuota:** 1.095 PDF ≈ 1 MB/berkas ≈ **±1 GB**, di luar `pdf-cache`.

---

# Yang TIDAK lagi jadi pertanyaan

| Dulu ditanyakan | Sekarang |
|---|---|
| Kolom tiap submenu Informasi | ✅ Terbaca dari 10 tabel sistem lama (§B.4) |
| Siapa mengunggah Informasi | ✅ Admin saja |
| `.xls` atau `.xlsx` | ✅ `.xls` yang sudah ada — nol dependensi baru |
| Letak & sifat sumber Flutter | ✅ `SmartPro-mobile`, base URL lewat `--dart-define` |
| Peta departemen lama → baru | ✅ Sudah ada di `api_config.dart:44-52` (kecuali `opds`) |
| PDF lama: teks digital atau pindai? | ✅ **Teks digital** — opsi (b) layak, bukan proyek OCR |
| Bahaya `smartpro:renew` | ✅ Gugur — bukan alatnya, kita pakai `DocumentPurger` |
| Aturan kolom daftar induk | ✅ Hanya edisi TERAKHIR dipotong, berhenti di revisi tempat dokumen berakhir |
| `tb_fk_*` / `tb_px_*` itu apa | ✅ Formulir Kerja & Prosedur External — jadi jenis dokumen baru |
| Letak menu Informasi | ✅ Menu sendiri, tepat **di atas "Informasi Akun"** |

---

# Catatan sesi 2026-08-11

**Selesai:**
- **Butir 5** (§A.2) — widget masukan: batas ke konfigurasi + tiga keadaan tata letak.
- **Butir 1 langkah 1–5** (§A.3) — singgahan PDF dikelompokkan per jenis.

**283 test hijau** (1.577 assertion), naik dari 281 di awal sesi. Kedua test baru
sudah **dibuktikan gagal** saat perubahannya dibatalkan — bukan sekadar lulus.

**Ditahan:**
- Butir 1 langkah 6–7 (penghapusan dokumen) — blocking **#1** (cakupan) & **#2** (backup).
  Perintah `smartpro:hapus-dokumen` belum ditulis sama sekali.

---

# Catatan sesi 2026-08-12 — ✅ FASE A TUTUP

**Yang membuat Fase A tadinya belum layak dilanjutkan** (temuan verifikasi, bukan asumsi):

1. **Fase A belum ter-commit sama sekali.** Commit terakhir `a00eaa5` tertinggal jauh;
   seluruh hasilnya menggantung di working tree. Prasyarat Fase B (§B, "SELESAI &
   ter-commit") tak terpenuhi, dan tak ada titik mundur sebelum butir 2 menyentuh
   jalur pengesahan.
2. **"Commit Fase A saja" ternyata mustahil.** `dashboard.blade.php:298` memanggil
   `User::dashboardPenuh()` yang belum ter-commit; `PdfCacheTest` bergantung pada
   `DocumentPurger` yang bahkan belum pernah masuk Git, dan pemanggil satu-satunya
   service itu adalah `DocumentRevisionController` + `routes/web.php`. Potongan
   apa pun menghasilkan commit yang belum tentu bisa boot. → dicommit utuh sebagai
   baseline (`85942d1`), nol berkas kode terhapus (351 penghapusan seluruhnya `docs/`).
3. **Backup belum ada.** Dua `.sql` di root bertanggal 9 Agustus dan bernama DB lain
   (`.env` menunjuk `smartpro_fresh`). → dibuat `backup-sebelum-hapus-20260812.sql`.
   `/*.sql` kini masuk `.gitignore`: dump memuat hash kata sandi + NRP karyawan,
   berkasnya harus ada di disk tapi tak pernah boleh masuk riwayat Git.
4. **Pemeriksa data terlanjur butir 2 TIDAK bersih.** Dokumen 1151 bernomor manual
   `…-10` ditimpa jadi `…-02` saat disahkan — **bug butir 2 terekam di audit log**
   (`document.create` 10 Agu 13:47 → `document.approve` 13:57
   `doc_number_final: PPA-ADRO-SOP-ICTMD-02`). Pemilik sudah memusnahkannya sendiri
   lewat web (11 Agu 15:35, teraudit), jadi pemeriksa itu kini **0**.

**Selesai sesi ini:**
- **Butir 1 langkah 6–7** (§A.3) — `smartpro:hapus-dokumen` ditulis & dijalankan.
  3 dokumen dimusnahkan; user, audit log, departemen, jenis dokumen utuh.
- Commit `85942d1` (baseline) dan `c737db1` (perintah + pembersihan).
- **283 test hijau** sesudah penghapusan.

**Blocking yang TUTUP:** #1 (cakupan = seluruh dokumen), #2 (backup ada, 95 KB).
**Blocking yang masih terbuka:** #3 (contoh FK/PX — hanya menahan wizard, bukan arsip),
#4 (berkas PDF server lama), #5 (kode jenis FK/PX), #6 (`opds`), #7 (paket Hostinger).
Tak satu pun menahan Fase B butir 2.

**Keputusan pemilik sesi ini:** butir 0 memakai **opsi (a)** — unggah metadata + berkas
PDF, revisi lewat jalur SmartPro biasa. Opsi (b) tetap akselerator opsional belakangan,
opsi (c) tetap ditolak.

**Belum disentuh:** seluruh Fase B dan C.

**Untuk sesi berikutnya, cukup baca:** §B.3 (butir 2 — butir PERTAMA Fase B).
Jangan baca seluruh berkas ini.
