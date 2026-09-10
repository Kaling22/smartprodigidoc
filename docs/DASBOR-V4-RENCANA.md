# DASBOR-V4 — dasbor per peran, widget referensi, halaman notifikasi

## Context

Dasbor V2 sudah berdiri (DASBOR-V2-REVISI Fase 1–4, REVISI-UI-V3 R7–R11, pra-produksi
butir 8–14). Tujuh baris, enam belas komponen, seluruh gerbang hijau. Tapi pemeriksaan
mata pemilik menemukan dua hal yang gerbang otomatis memang tak pernah bisa lihat:

1. **Dasbor SH/DH punya kolom 2/3 yang benar-benar kosong** di baris ke-5. Itu bukan
   bug — `REVISI-UI-V3` §4.6 sengaja memasang ganjal `<div hidden>` di sana sesudah
   `sebaranDepartemen` dicabut dari SH (R8). Ganjalnya menahan kolom 1/3 supaya tak
   melompat kiri, tapi tak seorang pun pernah mengisi slotnya.
2. **Kartu Performa PIC di dasbor SH nyaris selalu kosong**, dan sebabnya struktural
   (§A.2 di bawah) — bukan data yang kebetulan sepi.

Selain itu pemilik memberi tujuh referensi visual (Shadcn UI Kit) yang harus ditiru
persis: header per widget, blok "Track Order Status", bar bertumpuk "Attendance
Overview", donat "Store Visits by Source", tabel "Recent Projects", tombol lonceng, dan
halaman Notifikasi penuh.

Hasil akhir yang dituju: tiap peran (GL · SH/DH · MD · PJO) membuka dasbor dan seluruh
layarnya berisi angka yang **memang urusannya**, tanpa kolom kosong, tanpa kartu yang
menjanjikan sesuatu lalu menampilkan nol.

**Keputusan pemilik yang mengikat rencana ini (2026-09-04):**

| # | Keputusan |
|---|---|
| **P-1** | Kanal WhatsApp/pengingat **DITUNDA**. Sekarang: dasbor + tombol "Lihat semua" di lonceng + halaman Notifikasi baru saja. **Wajib diingatkan lagi sesudah seluruh fase selesai** (F9 di bawah adalah pengingat itu, bukan pekerjaan). |
| **P-2** | Kalau nanti jadi: rancang kanalnya **netral provider** — satu antarmuka pengirim + satu implementasi null. Gateway dipilih belakangan tanpa mengubah kode pemanggil. |
| **P-3** | Widget lama **DIGANTI** dulu sesuai gambar + isi ruang kosong SH. Widget BARU ditambahkan **sesudah** itu, dan sarannya sudah harus tersedia sekarang (§D). |

---

## A. Analisis — apa yang layak disajikan per peran

### A.1 Apa yang sudah didapat tiap peran hari ini

Diambil dari `DashboardController::index()` + `DasborTampilan::{hero,tiles}()`.

| Baris dasbor | GL | SH / DH | MD | PJO | Non-Staff |
|---|---|---|---|---|---|
| KartuSambutan | ✅ revisi | ✅ tinjau+setujui | ✅ periksa | ✅ setujui | ✅ |
| GrafikOverview + MeterTertinjau | ✅ "Dibuat" | ✅ "Tertinjau" | ⚠️ "Tertinjau" (salah, §A.3) | ✅ "Disetujui" | ✅ |
| KartuStatistik (4 tile) | ✅ | ✅ | ✅ (1 tile tanpa lencana) | ✅ | ✅ |
| KartuDistribusi + KartuSebaran | ✅ | ✅ | ✅ | ✅ | ❌ distribusi |
| **PitaDepartemen (2/3)** | ❌ | **❌ KOSONG** | ✅ | ✅ | ❌ |
| **PerformaPic (1/3)** | ❌ | ⚠️ **selalu kosong** | ✅ | ✅ | ❌ |
| LacakStatus + KartuKetersediaan | ✅ | ✅ | ✅ | ✅ | kalender saja |
| KartuMasukan + KartuLog | ✅ | ✅ | ✅ | ✅ | ✅ |

### A.2 ★ Akar masalah "Performa PIC SH tidak menunjukkan apa-apa"

Bukan data sepi. Kartunya **mustahil terisi menurut definisinya sendiri**:

- `lingkupPic()` (`DashboardController.php:848-850`) → bagi SH/DH, wajah yang boleh
  tampil **hanya `group_leader` di departemennya sendiri**.
- `wajahProduktif()` (`:900-904`) memeringkat orang dengan
  `DasborTampilan::aksiProduktif()`, yang membaca `KUNCI_PRODUKTIF`
  (`DasborTampilan.php:138`) = **`diloloskan` · `md` · `berlaku`** →
  `document.review_approve`, `document.md_approve`, `document.approve`.
- **GL tak pernah melakukan satu pun dari ketiganya.** CLAUDE.md §6: GL adalah
  SATU-SATUNYA PEMBUAT, tak pernah meninjau (kecuali GL SHE/Plant atas JSA lintas
  departemen) dan tak pernah menyetujui.
- `dibuat` — satu-satunya aksi yang GL memang kerjakan — **sengaja dikeluarkan dari
  peringkat** oleh keputusan K-H (`:906-908`).
- `$hitung->isEmpty()` → `return []` (`:913-915`). Kartunya digambar, isinya nol wajah.

Dua angka sisanya juga tipis bagi SH: `picAktif` menghitung `reviewer_id`+`approver_id`
dokumen berjalan **di departemennya** — biasanya SH itu sendiri, jadi angkanya 1–2. Dan
"Rata-rata beban peninjau" karena itu adalah beban dirinya sendiri, ditulis sebagai
rata-rata.

**Kesimpulan:** kartunya benar untuk PJO/MD (yang lingkupnya memang berisi peninjau &
penyetuju) dan salah alat untuk SH. Perbaikannya di §C.F4.

### A.3 Temuan sampingan — meter MD memakai label peran lain

`$meter` (`DashboardController.php:132-151`) bercabang untuk `$isCreator` dan `$isPjo`
saja; MD jatuh ke `default` dan mendapat label **"Tertinjau"**. Padahal K-C (pra-produksi
Fase 10) memutuskan label DAN angka meter berbeda per peran, dan tahap milik MD adalah
**"Diperiksa"** (`verifikasi_md → pending_approval`). MD karena itu membaca persentase
milik SH di kartunya sendiri. Diperbaiki di F1 (satu cabang `match`, aditif).

### A.4 Apa yang LAYAK disajikan per peran — dan alasannya

**GL — penyusun.** Pertanyaannya "dokumen saya sampai mana, dan apa yang menghambatnya".
Sudah baik. Yang kurang: tak pernah melihat **masukan lapangan atas dokumennya sendiri**
sebagai angka, dan tak pernah tahu dokumennya **mendekati roll-over edisi** (`no_revisi`
≥ 4 dari `MAKS_REVISI` 5). Sengaja TIDAK diberi: performa orang lain (dicabut pemilik
2026-09-01), sebaran lintas departemen.

**SH / DH — peninjau + penyetuju IK/SP, kepala departemen.** Pertanyaannya "apa yang
menunggu saya, dan bagaimana kesehatan departemen saya". Yang hilang hari ini: gambaran
**sebaran dokumen departemennya sendiri** (kolom kosong itu), dan **produktivitas GL
timnya** dalam kosakata yang GL memang bisa capai. Datanya sudah dikirim server dan
menganggur: `matrix` (7 status × 6 jenis, sudah disaring departemen).

**MD — pemeriksa sistematika penulisan.** Akun bersama, `jabatan` NULL, `document.view_all`.
Pertanyaannya "apa yang menunggu diperiksa, dan seberapa banyak koreksi yang saya
tulis". Yang hilang: label meter yang benar (§A.3), dan **`review_annotations` tak
pernah dibaca satu widget pun** — padahal di sanalah pekerjaan MD tercatat (`severity`,
`ai_generated`, `ai_adopted`, `resolved`).

**PJO — penyetuju tunggal, tanpa departemen.** Pertanyaannya "apa yang menunggu tanda
tangan saya, dan apakah organisasinya bergerak". Sudah paling lengkap. Yang diminta
pemilik: **tabel aktivitas terbaru** berfoto + saring + paginasi (gambar 1), dan sebaran
departemen bergaya baru (gambar 2).

---

## B. Soal chat internal & WhatsApp — jawaban singkat

Pemilik sudah memutuskan menunda (P-1). Yang dicatat di sini supaya keputusannya punya
alasan tertulis saat dibuka lagi di F9:

- **Chat internal TIDAK direkomendasikan.** Ia inbox kedua di dalam aplikasi yang
  justru tidak dibuka — jadi ia tak menyelesaikan "user tidak notice". Lebih buruk:
  percakapan tentang dokumen sudah punya tiga kanal ber-audit (`document_feedback`,
  `review_annotations`, `masukan antar-GL`). Kanal keempat yang bebas-teks berarti
  keputusan mutu diambil di tempat yang tak masuk `audit_logs`.
- **Pesan otomatis "dokumen perlu ditindaklanjuti" TIDAK perlu subsistem baru.** Kalimatnya
  sudah ada dan sudah dipakai lonceng + email: `DocumentNotification::$message`, dengan
  tujuan yang sudah tahu rute mana butuh parameter (`urlUntuk()`).
- **Yang benar-benar menjawab "supaya notice" adalah kanal DI LUAR aplikasi.** Dua
  kandidat, dan `users.nomor_hp` **sudah ada** (`0001_01_01_000000_create_users_table.php:34`)
  sehingga nomornya nol migrasi:
  - WhatsApp — sampai ke semua orang; butuh gateway (resmi Meta = template disetujui
    dulu; gateway lokal = risiko nomor diblokir).
  - Push FCM lewat aplikasi mobile yang sudah ada (`routes/api.php`, Sanctum) — gratis,
    nol persetujuan pihak ketiga, tapi hanya sampai ke yang memasang aplikasinya.
- Bentuk kerjanya nanti: **satu channel Laravel baru** di `DocumentNotification::via()`,
  bukan kelas notifikasi baru (alasannya tertulis di `DocumentNotification:22-25` — dua
  kelas = dua kalimat yang perlahan menyimpang).

---

## C. Fase pengerjaan — satu fase, berhenti, review (CLAUDE.md §5)

> Tiap fase menjalankan **sembilan gerbang** `REVISI-UI-V3` §6 dan berhenti untuk
> review. Perubahan server **ADITIF saja** (§P2 DASBOR-V2): prop baru atau kunci baru
> pada baris yang sudah ada — dilarang mengubah bentuk/jumlah/nilai prop yang sudah
> digambar V1.

### F0 — Commit dasar (permintaan pemilik)

57 berkas pra-produksi Fase 1–13 masih belum ter-commit (`git status`), termasuk satu
migration baru `2026_09_02_090000_add_salin_arsip_at_to_documents.php` dan folder
`pindah-pc/`. Commit dulu supaya seluruh pekerjaan di bawah punya titik balik yang bersih.
Nol kode disentuh di fase ini.

### F1 — Header & irama kartu (gambar 3) · murni tampilan + 1 baris server

**Yang diminta:** tiap widget punya header sendiri yang menampung judulnya, dipisah
garis tipis dari badannya — persis "Total Employees" / "Attendance Rate" / "Today Used
Devices" di gambar.

**Berkas:**
- `components/v2/dasbor/KartuStatistik.tsx` — **restrukturisasi**. Sekarang ikon+label
  (`CardDescription`), angka (`CardTitle`), dan lencana (`CardAction`) semuanya di dalam
  `CardHeader` tanpa pemisah. Jadi: `CardHeader className="border-b"` berisi kotak ikon
  + label; `CardContent` berisi angka besar + `LencanaDelta`; `CardFooter` tetap kalimat
  "vs 30 hari sebelumnya". `ui-maia/card.tsx:28` sudah otomatis jadi
  `grid-cols-[1fr_auto]` begitu ada `CardAction`, jadi nol kelas grid diketik sendiri.
- Sembilan kartu lain (`GrafikOverview`, `MeterTertinjau`, `KartuDistribusi`,
  `KartuSebaran`, `PitaDepartemen`, `PerformaPic`, `LacakStatus`, `KartuKetersediaan`,
  `KartuMasukan`, `KartuLog`) — tambahkan `className="border-b"` pada `CardHeader` yang
  sudah ada. Ini arketipe yang SUDAH dipakai seluruh daftar V2
  (`pages/V2/Documents/Index.tsx:101`), jadi bukan gaya baru.
- `KartuSambutan` **dikecualikan** — seluruh isinya memang hidup di `CardHeader`, dan
  garis di bawah header yang tak punya badan hanyalah garis mengambang.
- `app/Http/Controllers/DashboardController.php:132-151` — tambah cabang `$isMd` pada
  `match` `$meter`: `label 'Diperiksa'`, kalimat `sudah melewati pemeriksaan MD`,
  `status: ['pending_approval','published','sedang_direvisi','menunggu_nonaktif','obsolete']`
  (§A.3). Aditif: hanya menambah satu cabang sebelum `default`.

**Keputusan D6 — menu `⋮`:** gambar memperlihatkan titik-tiga di kanan header. Digambar
**hanya pada kartu yang punya aksi nyata** (`LacakStatus` → Lihat semua, `KartuMasukan`
→ Lihat semua/Beri Masukan, `KartuDistribusi` → Lihat semua). Kartu tanpa aksi tidak
mendapat menu kosong — tombol yang membuka menu berisi satu item mati adalah hiasan yang
akan dilaporkan sebagai bug.

**Tes:** nol tes baru untuk bagian tampilan (§14c: tes Inertia membaca props). **Satu tes
baru wajib** untuk cabang meter MD di `DasborV2Test`: MD ber-`meterLabel === 'Diperiksa'`
di **ketiga durasi** — pola yang sama dengan pengunci Fase 10 (deret yang kehilangan
kuncinya mengosongkan judul kartu hanya setelah rentangnya diganti).

### F2 — Lacak Status bagian atas → gaya "Track Order Status" (gambar 4)

**Yang diminta:** header menampung judul; empat angka besar; progress bar warna-warni;
statistik persen di samping tiap angka. Hanya bagian ATAS — tabelnya tetap desain V1
(R9), jangan disentuh.

**TSX** — `components/v2/dasbor/LacakStatus.tsx`, hanya `AngkaStatus` (`:277-291`) dan
kepala kartunya:
```
┌─ CardHeader border-b ──────────────────────────────────────────────┐
│ Lacak Status Dokumen                              [ Lihat semua ]  │
│ Pantau perpindahan dokumen antar tahap                             │
├─ CardContent ──────────────────────────────────────────────────────┤
│  12            4             7             2                       │
│  Draft         Dalam         Menunggu      Ditolak                 │
│                Peninjauan    Persetujuan                           │
│  ▬▬▬▬░░░░░     ▬▬░░░░░░░  ↗2.5%  ▬▬▬░░░░░  ↗8.1%  ▬░░░░░  ↘1.2%   │
│                masuk 30 hari      diloloskan 30 hari  ditolak 30 h │
└────────────────────────────────────────────────────────────────────┘
```
- Angka `text-3xl tabular-nums`, label `text-muted-foreground text-sm` di bawahnya,
  `LencanaDelta` di samping label (komponen yang sudah ada — dipakai ulang, tidak
  digambar ulang).
- Batang tetap `ui-maia/progress` diwarnai `statusMeta` lewat `--rona` (mekanisme yang
  sudah ada di `:277-291`) — nol hex, empat warna berbeda karena keempat statusnya
  memang berbeda warna di `Document::STATUS_META`.

**Server** — `DasborTampilan` + `DashboardController`. Kunci baru per baris angka:
`delta`, `deltaLabel`, `arah`, `aliranKet`. Sumbernya **`alirAudit()` yang sudah ada** —
nol query baru:

| Angka (snapshot `matrix`) | Aliran penyuapi lencana | `aliranKet` |
|---|---|---|
| Draft | `dibuat` (`document.create`) | "dibuat 30 hari" |
| Dalam Peninjauan | `dikirim` (`document.submit`) | "masuk tinjauan 30 hari" |
| Menunggu Persetujuan | `diloloskan` (`document.review_approve`) | "diloloskan 30 hari" |
| Ditolak | `ditolak` (`approval_reject`+`review_reject`) | "ditolak 30 hari" |

> **Keputusan D1 yang butuh persetujuan.** §5b DASBOR-V2 melarang lencana delta di baris
> ini, alasannya benar: `matrix` adalah potret sesaat, dan `↗5%` di atasnya akan dibaca
> orang sebagai "draft bertambah 5%" padahal tak ada yang mengukurnya. Rencana ini
> **tidak mencabut alasannya** — ia menukar apa yang diukur: lencananya mengukur
> **ALIRAN MASUK**, dan kalimat kecil di bawahnya menyebutkan aliran itu dengan namanya
> (`diloloskan 30 hari`). Tanpa kalimat itu, larangan §5b tetap berlaku dan lencananya
> harus dibuang. Kalau pemilik lebih memilih tanpa lencana sama sekali, keempat kolom
> cukup kehilangan `LencanaDelta` — sisanya tak berubah.

**Tes:** +1 `DasborV2Test` — keempat baris `matrix` yang dipantau punya kunci `delta`
(boleh `null`) DAN `aliranKet` bukan-kosong; `delta` tak pernah `INF`/`NAN` (pengunci
pembagi-nol yang sama dengan tes 11).

### F3 — Sebaran per Departemen → bar bertumpuk gaya "Attendance Overview" (gambar 2)

**Yang diminta:** ganti bar proporsi tunggal dengan **satu bar per departemen** (7 bar),
tiap bar bertumpuk **6 jenis dokumen mutu**; statistik persen tetap ada; unsur lainnya
(angka besar, delta, legenda berwarna di bawah) mirip referensi.

**Server** — `DashboardController::sebaranDepartemen()` (`:986-1022`), aditif:
- Tambah `document_types.code` ke `groupBy` yang sudah ada (satu join tambahan,
  **query tetap satu**), lalu isi kunci baru `per: {SOP: n, IK: n, …}` di tiap baris.
  Jenisnya dari `DocumentType::kode()` yang sudah dipanggil di `index()` — jangan
  mengarang daftar kedua.
- Kunci baru tingkat-atas: `persenTerbesar` + `delta`/`deltaLabel`/`arah` untuk kepala
  kartu, disuapi `alirAudit()['alir']['berlaku']` yang sudah ada.
- `total`, `baris[].{kode,nama,jumlah,persen}` dan **urutan menurun TIDAK berubah** —
  K-G mengunci urutannya menurut jumlah, dan `DasborV2Test::test_peta_warna_sebaran_lengkap`
  serta `test_sebaran_departemen_gerbang_dan_bentuknya` membacanya.

**TSX** — `components/v2/dasbor/PitaDepartemen.tsx` **ditulis ulang** (nama berkas tetap;
mengganti namanya berarti menyentuh `Dashboard.tsx` + dokumen + tes tanpa satu pun
perbaikan yang terlihat):
```
┌─ CardHeader border-b ───────────────────────────────────────────────┐
│ Sebaran Dokumen per Departemen                              [ ⋮ ]   │
├─ CardContent ───────────────────────────────────────────────────────┤
│  41,2%  ↗ +2,7%        128/311  ↗ +1,3%    ▬ SOP 34%  ▬ IK 22% …    │
│  Porsi dept terbesar   Dokumen berlaku      (legenda 6 jenis, %)    │
│                                                                      │
│  ▉▉▉▉  ▉▉▉▉  ▉▉▉   ▉▉▉   ▉▉    ▉▉    ▉      ← bar bertumpuk        │
│  ▉▉▉▉  ▉▉▉   ▉▉▉   ▉▉    ▉▉    ▉     ▉                              │
│  SHE   PLANT  PROD  ENG   HRGA  FIN   ICTMD                          │
└──────────────────────────────────────────────────────────────────────┘
```
- **Recharts `BarChart`** di dalam `ui-maia/chart.tsx` (`ChartContainer`), enam `<Bar>`
  ber-`stackId` sama — persis pola `GrafikOverview` yang sudah ada, jadi nol pustaka
  baru dan nol `ResizeObserver` tambahan di luar yang sudah ditanggung.
- **Warna = 6 jenis dokumen, bukan 7 departemen.** `ChartConfig` memetakan
  `SOP…PX` → `var(--chart-1)`…`var(--chart-6)`. Peta `WARNA` per kode departemen yang
  sekarang (`:14-22`) **dicabut** bersama tes penguncinya yang **diperbarui, bukan
  dihapus**: `test_peta_warna_sebaran_lengkap` kini menjaga kelengkapan peta **jenis**
  (tiap kode di `DocumentType::kode()` punya token, nol token dipakai dua kali).
- **`--chart-6` tak punya utilitas Tailwind** (`app.css` `@theme inline` hanya
  mendaftarkan 1–5). Ia dipakai lewat string `var(--chart-6)` di `ChartConfig` —
  `ChartStyle` (`ui-maia/chart.tsx:84-115`) memancarkannya apa adanya, jadi override
  `.ui-v2` tetap berlaku dan mode gelap tak butuh salinan kedua. Ini persis mekanisme
  yang `PitaDepartemen.tsx:20-21` sudah pakai hari ini.
- Kepala kartu memakai `LencanaDelta` yang sudah ada.
- Keadaan kosong `Empty` + `bi-building` dipertahankan.

### F4 — ★ Isi ruang kosong SH + perbaiki Performa PIC SH

Dua perbaikan yang harus satu fase, karena keduanya menyusun ulang **baris ke-5** yang
sama.

**F4a — kolom 2/3 SH/DH: "Sebaran Dokumen Departemen"**

Komponen yang **sama** dengan F3, disuapi bentuk data yang berbeda: bar = **6 jenis
dokumen**, tumpukan = **7 status** dari `matrix`.

- **Nol query baru.** `matrix` (`DashboardController:212-232`) sudah dikirim, sudah
  disaring `$visible()` (jadi otomatis se-departemen bagi SH), berisi tepat 7 status ×
  6 jenis, dan **sampai hari ini hanya empat angkanya yang pernah digambar**
  (`LacakStatus`). Ini butir #1 daftar "yang menganggur" di DASBOR-V2 §8.
- Server hanya menambah prop gerbang `sebaranJenisDept` (`null` bila
  `can('document.view_all')` — PJO/MD sudah punya versi departemennya) supaya keputusan
  siapa-melihat-apa tetap sepenuhnya milik server (CLAUDE.md §4).
- Warna tumpukan dari `statusMeta` (props), **bukan** `--chart-*` — tujuh status memang
  sudah punya warnanya sendiri dan memakainya membuat batang ini, badge `StatusBadge`,
  dan batang `LacakStatus` di bawahnya berbicara warna yang sama.
- `pages/V2/Dashboard.tsx` baris 4 (`:140-151`): ganjal `<div hidden>` bagi SH **diganti**
  komponen ini. Ganjalnya tetap ada untuk peran yang benar-benar tak punya keduanya.

**F4b — Performa PIC: peringkat yang bisa dicapai oleh yang diperingkat**

- `DasborTampilan` — konstanta baru `KUNCI_PRODUKTIF_GL = ['dibuat', 'dikirim']`
  di samping `KUNCI_PRODUKTIF` yang ada, plus `aksiProduktifGl()`. Satu tempat, seperti
  `aksiProduktif()`.
- `DashboardController::wajahProduktif()` — pilih himpunan peringkat dari
  `lingkupPic()`: lingkup yang jabatannya **hanya `group_leader`** (yakni SH/DH)
  memeringkat dengan `KUNCI_PRODUKTIF_GL`; lingkup lintas-departemen tetap
  `KUNCI_PRODUKTIF`. Sapuan `audit_logs`-nya **sama** (`AKSI_ALIR` sudah memuat `dibuat`
  dan `dikirim`) → **nol query baru**.
- Judul & sorotan ikut lingkup, dari **server** (kosakata peran tak boleh punya salinan
  di TSX):
  - SH/DH → judul "Produktivitas Tim", angka besar = jumlah GL aktif di departemen,
    sorotan: `Dokumen dibuat` · `Dokumen dikirim` · `Masukan ditutup` (ketiganya punya
    aksi audit yang jujur, jadi ketiganya berpanah).
  - PJO/MD/Admin → tetap persis seperti sekarang ("PIC Aktif", beban peninjau, dst).
- `PerformaPic.tsx` menggambar `judul`/`subjudul`/`satuan` dari props; nol `if peran` di
  TSX.

> **Keputusan D3 yang butuh persetujuan.** K-H (pra-produksi butir 10–11) memutuskan
> `dibuat` **tidak** masuk peringkat, supaya GL yang menulis lima puluh draft tak naik ke
> wajah teratas. Alasannya sah untuk kartu **lintas peran**, di mana pembuat dan peninjau
> diadu di satu daftar. Untuk kartu yang isinya **seluruhnya GL**, `dibuat` justru
> satu-satunya kosakata yang ada — dan K-H apa adanya menghasilkan kartu kosong permanen
> (§A.2). Usulnya karena itu **mempersempit** K-H, bukan mencabutnya: peringkat campuran
> tetap tanpa `dibuat`; peringkat sesama GL memakai `dibuat`+`dikirim`. `dikirim`
> diikutkan supaya draft yang ditumpuk tanpa pernah dikirim tak jadi jalan pintas.

**Tes F4:** +2 `DasborV2Test` — (1) `sebaranJenisDept` **`null`** bagi PJO/MD/Admin & GL
& Non-Staff, **bukan-null** bagi SH/DH, dan barisnya berjumlah `count(DocumentType::kode())`
dengan tumpukan berjumlah tujuh status; (2) dasbor SH yang departemennya punya GL dengan
`document.create` dalam 30 hari **menghasilkan `wajah` tidak kosong** — inilah tes yang
kalau ditulis hari ini langsung MERAH, dan itulah gunanya. Tes 8 (`nama/foto/jumlah/
rincian/ket`, `assertSame` daftar kunci) **diperbarui bukan ditambah** bila judul/satuan
masuk ke tingkat kartu (bukan ke tiap wajah — jaga supaya bentuk `wajah` tak berubah).

### F5 — Donat Sebaran Jenis → perilaku & warna gambar 5

**Berkas:** `components/v2/dasbor/KartuSebaran.tsx`.

- **Perilaku:** `Pie` mendapat `activeIndex` + `activeShape` (segmen yang di-hover
  membesar keluar), `ChartTooltip` bergaya chip satu baris `▪ Nama  173` seperti
  referensi. Label tengah **tetap statis** (total + "Dokumen") — di referensi pun
  begitu. `innerRadius`/`strokeWidth` yang ada dipertahankan; hanya `paddingAngle`
  dirapatkan supaya cincinnya menyambung seperti gambar.
- **Legenda** pindah ke bawah donat sebagai baris titik berwarna + nama HURUF BESAR +
  angka, mengikuti gambar (sekarang sudah `<ul>` dengan chip ikon — chipnya diganti
  titik `rounded-full`).
- **Warna — keputusan D2 yang butuh persetujuan.** Referensi memakai **satu ramp
  sekuensial**, gelap→terang menurut besar irisan. Dua jalan:
  - **(a) Ramp lokal di kartu ini saja (rekomendasi).** Irisan diurutkan menurun, warnanya
    `color-mix(in oklab, var(--chart-1) X%, var(--card))` dengan X menurun mengikuti
    peringkat. Nol hex di TSX, ramp sekuensial dipakai secara sekuensial (kebalikan dari
    cacat yang `REDESAIN-UI-V2` §6 temukan), dan `DocumentType::RUPA` **tak disentuh**
    sehingga warna identitas jenis di `KartuDistribusi`, `Documents/Index`, dan Daftar
    Induk tak bergeser satu pun. Konsekuensi yang harus disadari: warna irisan donat
    **tidak lagi sama** dengan warna chip jenis di kartu sebelahnya.
  - **(b) Ganti keenam hex `DocumentType::RUPA` jadi ramp yang benar.** Satu tempat,
    warna konsisten di seluruh aplikasi — tapi ia mengubah warna di belasan layar
    sekaligus, termasuk layar yang tak sedang direview. Bukan keputusan dasbor.
  - Alasan pilihan yang diambil **wajib ditulis di komentar berkas** (CLAUDE.md §13).

### F6 — Aktivitas Terbaru gaya "Recent Projects" (gambar 1) — PJO & MD

**Yang diminta:** desain sama persis dengan gambar — kotak saring, tabel berfoto,
paginasi.

**Server** — prop **BARU** `aktivitasTabel` (paginator Laravel), bukan memperbesar
`menungguDiMeja`:
> `menungguDiMeja` **tetap `limit(4)`**. `DashboardWidgetTest:124` menegaskan
> `assertCount(4, …)` dengan docblock "TEPAT 4 baris", dan `components/dasbor/
> KartuPerjalanan.tsx` (V1) dirancang untuk empat. Menaikkannya memecah tes DAN mengubah
> V1 — persis §P1 yang sudah pernah dibatalkan sekali.

- Gerbang `can('document.view_all')` → PJO · MD · Admin IT. `null` bagi yang lain, dan
  barisnya tak digambar. Pola gerbang yang sama dengan `sebaranDepartemen`.
- `->paginate(6, pageName: 'aktivitas')` — nama halaman sendiri supaya paginasi tabel ini
  tak bertabrakan dengan query string lain di `/dashboard` (`?bulan=` kalender).
- Penyaringan & pengurutan **tetap di server** lewat query string (§4 PATOKAN-GAYA):
  saring **Jenis · Departemen · Status** + kotak cari judul/nomor. Ketiganya sudah punya
  pembangun siap pakai — `saringJenis`, `saringDepartemen`, `saringStatus`
  (`components/v2/PenyaringDokumen.tsx:234-256`).
- **Kebocoran (§P4) — ini kartu berfoto, jadi ia kelas kartu yang paling gampang bocor.**
  Tiap baris mengirim **hanya**: `id`, `judul`, `nomor`, `tautan`, `status`,
  `dibuat` (tanggal), `diperbarui`, `persen`, `ke`, `dariTahap`,
  `pembuat: {nama, foto, jabatan}`. **Bukan** model `Document`, **bukan** relasi
  `creator`, tanpa `email`/`nrp`/`department_id`. Bentuk `pembuat` disalin dari
  `menungguDiMeja[].pembuat` yang sudah terbukti aman.

**TSX** — `components/v2/dasbor/AktivitasTerbaru.tsx` **baru**, dirakit dari komponen
yang sudah ada, nol tabel baru:
```
┌─ CardHeader border-b ────────────────────────────────────────────────┐
│ Aktivitas Terbaru                                                    │
├──────────────────────────────────────────────────────────────────────┤
│ [🔍 Cari dokumen, pembuat…]   [Jenis ▾] [Dept ▾] [Status ▾]          │  ← PenyaringDokumen
├──────────────────────────────────────────────────────────────────────┤
│ Dokumen        │ Pembuat      │ Dibuat   │ Diperbarui │ Status │ Kemajuan │
│ Prosedur K3    │ (◕) Rina A.  │ 20/03/26 │ 05/04/26   │[Berlaku]│ ▬▬▬ 100%│  ← DataTable
├──────────────────────────────────────────────────────────────────────┤
│ 1 – 6 dari 12 dokumen                                    ‹  ›        │  ← Paginasi
└──────────────────────────────────────────────────────────────────────┘
```
- `components/v2/PenyaringDokumen` di `CardHeader className="border-b"` — debounce 300 ms
  + `preserveState` + `preserveScroll` + `replace` sudah di dalamnya (R5).
- `components/v2/DataTable` di `CardContent className="px-0"`, kolom aksi
  `w-px text-right whitespace-nowrap` (§3.7).
- `components/v2/Avatar` + `v2/StatusBadge` + `ui-maia/progress` untuk sel-selnya —
  ketiganya sudah dipakai `LacakStatus`, jadi foto & warna status otomatis konsisten.
- `Paginasi` dari `DataTable.tsx:194-230` di `CardFooter className="border-t pt-6"` —
  ia sudah memakai `<Link preserveScroll>` (bukan `PaginationLink` yang memuat ulang
  penuh halaman).
- **Baris penuh** di `pages/V2/Dashboard.tsx`, ditaruh **sesudah** baris
  `PitaDepartemen | PerformaPic`. Ia satu-satunya kartu selebar layar penuh, dan itu
  memang bentuk referensinya.

**Ongkos query:** `+2` (count + select) + eager load pembuat. Penjaga
`DasborV2Test` yang menolak ≥ 60 query di `/dashboard` **wajib diukur ulang** di fase
ini; kalau melonjak lebih dari itu, ada N+1 yang menyelinap.

**Tes:** +2 `DasborV2Test` — (1) `aktivitasTabel` `null` bagi GL/SH/Non-Staff, berbentuk
paginator utuh (`data`,`links`,`from`,`to`,`total`) bagi PJO; (2) **kebocoran pada HTML
MENTAH**: muat `/dashboard` sebagai PJO sesudah membuat satu dokumen, tegaskan badan
respons tak memuat `email`, `nrp`, `password`, `remember_token`, `arsip_path` siapa pun
(pola `DistribusiInertiaTest`).

### F7 — Lonceng "Lihat semua" + halaman Notifikasi (gambar 6 & 7)

**Lonceng** — `components/v2/SiteHeader.tsx:150-200` (`Lonceng()`):
- Tombol "Tandai dibaca" di kepala dropdown **diganti** tautan **"Lihat semua"**
  ber-`text-primary underline` ke halaman baru, persis gambar 6.
- Baris notifikasi mendapat avatar/ikon bulat + judul + keterangan + waktu relatif,
  mengikuti gambar. Ikon tetap lewat `v2/Ikon` (kuncinya `bi-*` dari server).
- Tombol "Tandai semua dibaca" **pindah** ke halaman (di sana tempatnya di referensi).
  Rute `notifications.readAll` **tidak berubah**, hanya pemanggilnya pindah.

**Server** — `NotificationController::index()` baru + rute `GET notifications` bernama
`notifications.index` di grup `auth` (`routes/web.php:166-168`, di samping dua rute yang
sudah ada). Paginasi 20, saring lewat query string:
- `status` = semua / belum dibaca / sudah dibaca → `read_at` null/not-null.
- `kategori` — **petanya sudah ada di PHP**: `DocumentNotification::judulAksi()`
  (`:129-146`) memetakan `icon` → nama kategori. Diangkat jadi konstanta
  `DocumentNotification::KATEGORI` dan `judulAksi()` membacanya — satu tempat, dua
  pemakai (subjek email + saringan halaman). **Jangan** menulis peta kedua di TSX.
- `cari` — `data->message` / `data->doc_number`.
- Tiap baris keluar sebagai `{id, judul, pesan, ikon, kategori, nomor, tautan, waktu,
  dibaca}`. `tautan` dari `DocumentNotification::urlUntuk()` yang sudah ada.

**Halaman** — `resources/js/pages/V2/Notifications/Index.tsx` baru:
```
Notifikasi  [4 belum dibaca]              [✓ Tandai Semua Dibaca]
Ikuti perkembangan dokumen yang menyangkut Anda.
[🔍 Cari notifikasi…]              [Status ▾]  [Kategori ▾]
─────────────────────────────────────────────────────────────
(📄) Perlu Ditinjau  ●                    [• Tinjau]   5 menit lalu
     PPA-ADRO-SOP-SHE-04 menunggu ditinjau
─────────────────────────────────────────────────────────────
(👤) Rina A.  ●                           [• Persetujuan] 30 menit lalu
     Meminta persetujuan PPA-ADRO-IK-PLANT-02
     [ Buka Persetujuan ]  [ Tinjau Dulu ]
─────────────────────────────────────────────────────────────
                                              ‹ 1 2 3 ›
```
- Dirakit dari `ui-maia/item` + `badge` + `empty` + `PenyaringDokumen` + `Paginasi` —
  komponen yang semuanya sudah ada.

> **Keputusan D5 yang butuh persetujuan — tombol "Accept / Decline".** Di gambar,
> tombolnya menyetujui langsung dari daftar. Di sini keduanya dibuat **TAUTAN ke layar
> kerjanya**, bukan endpoint aksi baru, karena tiga hal yang bukan selera:
> (a) menolak **wajib beralasan** (CLAUDE.md §7 — approver reject → feedback wajib), jadi
> "Decline" satu klik mustahil ada; (b) menyetujui berarti **mengunci nomor final**
> (`DocumentService`, §8) dan mencap APPROVED seluruh TTD — tindakan yang tak boleh
> diambil tanpa membuka dokumennya; (c) ini sistem dokumen mutu: penyetuju menyatakan
> ia sudah membaca. Menyediakan tombol setuju di daftar notifikasi berarti membangun
> jalan pintas melewati pernyataan itu. Rupanya tetap dua tombol persis gambar; yang
> berbeda hanya keduanya membuka layar keputusan.

> **Keputusan D4 yang butuh persetujuan — halaman ini lahir V2-only.**
> `PratinjauResponseFactory::render()` (`:44`) memasang awalan `V2/` hanya bila
> kembaran TSX-nya ada; kalau tidak, ia jatuh ke `pages/{komponen}.tsx`. Halaman baru
> ini tak punya asal-usul V1 untuk dijadikan cadangan, dan menulis kembaran V1 berarti
> menulis halaman yang antre dibuang di Fase 7 PATOKAN-GAYA. Jadi controller merender
> **`V2/Notifications/Index`** secara harfiah, melewati pabrik itu. Konsekuensi yang
> harus dicatat di komentar: dengan bendera pratinjau MATI, halaman ini tetap V2 —
> satu-satunya di aplikasi. Itu jalur cadangan mati (K-A), bukan jalur pengguna.

**Tes:** +3 `tests/Feature/NotifikasiInertiaTest.php` (berkas baru) — (1) halaman
merender `V2/Notifications/Index` dengan paginator utuh; (2) saring `status=belum` &
`kategori=` menyaring **di server** (jumlah baris berubah, dan pilihan bertahan saat
saringan lain diubah); (3) **kebocoran HTML mentah** — notifikasi milik orang lain tak
pernah ikut, dan badan respons tak memuat `email`/`nrp`/`password` siapa pun.

### F8 — Pemeriksaan mata per peran (gerbang otomatis tak pernah memeriksa tata letak)

Buka `/dashboard` sebagai **GL · SH · DH · MD · PJO · Non-Staff · Admin**, tema terang
DAN gelap. Yang wajib diperiksa, karena tak satu pun gerbang bisa:
- SH: baris ke-5 **tak lagi punya kolom kosong**; Performa PIC berisi wajah GL.
- GL & Non-Staff: baris ke-5 **lenyap utuh**, bukan menyisakan kolom yatim.
- Keempat kartu KPI **tetap sama tinggi** meski salah satunya tanpa lencana.
- Bar bertumpuk: tujuh label departemen tak saling menabrak di layar 1366px; segmen
  bernilai nol tak menghasilkan garis rambut.
- Donat: irisan hover membesar tanpa memotong legenda; label tengah tak bergeser.
- Tabel Aktivitas Terbaru: judul terpanjang tak memaksa gulir mendatar halaman
  (`DataTable` menggulir sendiri); paginasi tak mengembalikan bulan kalender ke default.
- Halaman Notifikasi: baris terpanjang tak melipat lencana kategorinya.

Hasilnya dicatat di `docs/CEKLIS-MATA-PER-PERAN.md`.

### F9 — PENGINGAT (bukan pekerjaan)

Sesuai P-1: **sesudah F8 selesai, tanyakan lagi ke pemilik** soal kanal pengingat di luar
aplikasi (WhatsApp / push FCM), dengan bentuk netral-provider P-2. Bahannya sudah
tertulis di §B — jangan mulai tanpa keputusan gateway, karena pilihan resmi-Meta menuntut
template disetujui lebih dulu (hitungan hari, di luar kendali kita).

---

## D. Rekomendasi widget BARU — untuk ditambahkan sesudah F1–F8 (P-3)

Semuanya dibuktikan ke kolom yang **sudah ada di basis data dan belum satu pun dibaca
dasbor**. Diurutkan menurut manfaat berbanding kerja.

| # | Widget | Peran | Sumber (kolom menganggur) | Kerja |
|---|---|---|---|---|
| 1 | **Waktu Siklus Dokumen** | SH/DH · PJO · MD | `documents.submitted_at` → `published_at` | 1 query |
| 2 | **Cakupan Baca Departemen** | SH/DH | `document_reads.{read_count,download_count,platform,first_read_at}` | 1 query |
| 3 | **Mutu Tinjauan** | MD · SH/DH | `review_annotations.{severity,resolved}` — tabel ini **nol pembaca** | 1 query |
| 4 | **Adopsi Saran AI** | MD | `review_annotations.{ai_generated,ai_adopted}` | dari query #3 |
| 5 | **Mendekati Roll-over Edisi** | SH/DH · PJO | `documents.no_revisi` ≥ `MAKS_REVISI − 1` | 1 query |
| 6 | **Bolak-balik Revisi** | SH/DH · PJO | `documents.revision_round`, `reviews.revision_round` | 1 query |
| 7 | **SLA Masukan Lapangan** | SH/DH | `document_feedback.{replied_at,replied_by}` | 1 query |
| 8 | **Ketersediaan Tim 14 Hari** | SH/DH | `user_off_days` **se-tim** (kini hanya diri sendiri) + `ReviewerAvailability::untukSatu()` yang ditulis untuk dasbor lalu tak pernah dipakai | 0 query baru |
| 9 | **Dokumen Tidak Terkendali** | PJO · Admin | `documents.is_controlled = false` | 1 query |

### Rancangan tiga yang paling kuat

**#1 Waktu Siklus Dokumen** — menjawab pertanyaan manajemen yang hari ini tak bisa
dijawab sama sekali: "berapa lama sebuah SOP dari dikirim sampai berlaku?"
```
┌─ Waktu Siklus Dokumen ──────────────────────── 30 hari ─┐
│  9,4 hari   ↘ −1,8 hari                                 │
│  Median dikirim → berlaku          (turun = membaik)    │
├─────────────────────────────────────────────────────────┤
│  SOP   ▬▬▬▬▬▬▬▬▬▬▬▬▬▬  14,2 hari                        │
│  IK    ▬▬▬▬▬▬          6,1 hari                         │
│  SP    ▬▬▬▬▬           5,4 hari                         │
│  JSA   ▬▬▬             3,2 hari                         │
└─────────────────────────────────────────────────────────┘
```
Bar mendatar `ui-maia/progress`, angka besar + `LencanaDelta` (`arah: 'baik'` saat
TURUN — persis kenapa `arah` datang dari server dan TSX tak boleh menebaknya dari tanda
angka). **Median, bukan rata-rata**: satu dokumen yang menganggur tiga bulan menggeser
rata-rata sampai angkanya berhenti berarti apa-apa.

**#2 Cakupan Baca Departemen** — pasangan sisi-SH dari `KartuDistribusi`, dan komponen
batangnya (`v2/dasbor/PitaCakupan`) sudah berdiri.
```
┌─ Belum Sampai ke Timnya ─────────────── [ Lihat semua ]─┐
│  62%  ↗ +4%        18 dari 29 orang sudah membaca       │
├─────────────────────────────────────────────────────────┤
│ (📄) SOP Penanganan Tumpahan     ▬▬▬░░░░ 31%  (◕)(◔)+9  │
│ (📄) IK Pemeriksaan Harian       ▬▬▬▬░░░ 48%  (◑)(◐)+4  │
└─────────────────────────────────────────────────────────┘
```
Empat dokumen cakupan **TERENDAH** — alasan yang sama dengan `KartuDistribusi`
(`DashboardController:1103-1108`): menampilkan yang paling sukses enak dipandang dan tak
menggerakkan apa pun. `platform` (`web`/`mobile`) jadi keterangan kecil — angka yang
langsung menjawab "apakah aplikasi mobile-nya dipakai".

**#3 + #4 Mutu Tinjauan & Adopsi AI (MD)** — `review_annotations` adalah tempat pekerjaan
MD benar-benar tercatat, dan **belum satu pun widget membacanya**.
```
┌─ Anotasi Tinjauan ──────────────── 30 hari ─┐   ┌─ Bantuan AI ────────┐
│  Kritis 2 · Mayor 11 · Minor 34 · Info 8    │   │      ◜◝  61%        │
│  ▉▉ ▉▉▉▉▉▉▉▉ ▉▉▉▉▉▉▉▉▉▉▉▉▉▉ ▉▉▉▉            │   │      ◟◞  diadopsi   │
│  38 dari 55 sudah ditutup (69%)             │   │  47 saran · 29 pakai│
└─────────────────────────────────────────────┘   └─────────────────────┘
```
Bar bertumpuk empat severity + `Progress` "sudah ditutup"; radial `RadialBarChart`
(pola `MeterTertinjau` yang sudah ada) untuk rasio `ai_adopted / ai_generated`. Angka
adopsi AI adalah satu-satunya cara jujur menjawab "apakah AI-nya berguna" — dan CLAUDE.md
§12 memang mensyaratkan asal-usul AI terlacak.

---

## E. Berkas yang disentuh

**Server** — `app/Http/Controllers/DashboardController.php` · `app/Services/DasborTampilan.php`
· `app/Http/Controllers/NotificationController.php` · `app/Notifications/DocumentNotification.php`
(angkat peta kategori) · `routes/web.php` (satu rute)

**Halaman** — `resources/js/pages/V2/Dashboard.tsx` · `resources/js/pages/V2/Notifications/Index.tsx` (baru)

**Komponen ditulis ulang** — `components/v2/dasbor/{KartuStatistik,LacakStatus,PitaDepartemen,KartuSebaran,PerformaPic}.tsx`
· `components/v2/SiteHeader.tsx` (lonceng)

**Komponen baru** — `components/v2/dasbor/AktivitasTerbaru.tsx`

**Dipakai ulang tanpa disunting** — `components/v2/{DataTable,PenyaringDokumen,StatusBadge,Avatar,Ikon}.tsx`
· `components/v2/dasbor/{LencanaDelta,PitaCakupan,TumpukanWajah,Sparkline}.tsx` · seluruh `ui-maia/*`

**Tipe** — `resources/js/types/dasbor.d.ts` (aditif) · `resources/js/types/` baru untuk notifikasi

**Tes** — `tests/Feature/DasborV2Test.php` (+6) · `tests/Feature/NotifikasiInertiaTest.php` (baru, +3)

**TIDAK disentuh** — 41 halaman V1 · `components/dasbor/*` · `components/ui/**` ·
`components/ui-maia/**` · `components.json` · `resources/css/app.css` ·
`app/Models/{Document,DocumentType,User,Department}.php` (kecuali D2 opsi b) ·
**seluruh daftar mesin cetak CLAUDE.md §14b**

---

## F. Verifikasi

**Sembilan gerbang, tiap fase, dari PowerShell** (Git Bash mengubah `/dashboard` jadi
jalur Windows):
```powershell
node_modules/.bin/tsc --noEmit
npm run build
npm run uji-render
php artisan smartpro:uji-render                  # bendera V2 MATI
php artisan smartpro:uji-render --pratinjau      # bendera V2 NYALA
php artisan test                                 # patokan 744, tak boleh berkurang
md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23/23
php artisan smartpro:cetak-baseline              # seluruh PDF "sama"
git diff components.json                         # WAJIB KOSONG
```

**Patokan tes:** 744 sekarang. Sesudah seluruh fase: **744 + 9 = 753**
(F1 +1 · F2 +1 · F4 +2 · F6 +2 · F7 +3). Jumlah tak boleh berkurang (§14c) — tes yang
terasa tak relevan adalah tanda ada perilaku yang hilang.

**Ongkos query `/dashboard`** — diukur ulang di F3, F4, dan F6:
```php
DB::enableQueryLog(); → GET /dashboard sebagai PJO → count(DB::getQueryLog())
```
48 hari ini; penjaga `DasborV2Test` menolak ≥ 60. F3 & F4 menargetkan **nol query baru**;
F6 menambah dua. Kalau melonjak lebih dari itu, ada N+1 yang menyelinap.

**Yang gerbang TIDAK periksa** — tata letak. Itu F8, dan itu memang tugas mata.

---

## G. Ringkasan keputusan yang menunggu persetujuan pemilik

| # | Fase | Keputusan | Rekomendasi |
|---|---|---|---|
| **D1** | F2 | Lencana di angka Lacak Status mengukur **aliran masuk**, bukan perubahan potret; kalimat kecil menyebut alirannya | pasang dengan kalimatnya; tanpa kalimat → buang lencananya |
| **D2** | F5 | Warna donat: ramp lokal di kartu (a) vs mengganti `DocumentType::RUPA` se-aplikasi (b) | **(a)** |
| **D3** | F4b | Mempersempit K-H: peringkat sesama GL memakai `dibuat`+`dikirim` | setuju — tanpanya kartu SH kosong permanen |
| **D4** | F7 | Halaman Notifikasi lahir **V2-only**, tanpa kembaran V1 | setuju (K-A: produksi memakai V2) |
| **D5** | F7 | "Accept/Decline" = **tautan** ke layar keputusan, bukan aksi langsung | setuju — menolak wajib beralasan, menyetujui mengunci nomor final |
| **D6** | F1 | Menu `⋮` hanya pada kartu yang punya aksi nyata | setuju |
| **D7** | F6 | `menungguDiMeja` tetap 4; Aktivitas Terbaru prop baru berpaginasi | setuju (§P1) |
