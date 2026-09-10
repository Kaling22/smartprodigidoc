# Dasbor V2 — hasil review rencana + rencana revisi

> Status: **Fase 1–4 SELESAI** kecuali §9c. Fase 1 server+tipe+tes · Fase 2
> kembaran+logo+halaman · Fase 3 enam widget + tiga permintaan mata · Fase 4
> dokumentasi lintas-berkas. **Yang tersisa cuma §9c — pemeriksaan mata per
> peran, dan itu memang tugas pemilik: nol gerbang otomatis memeriksa tata letak.**
> Menggantikan bagian dasbor di `docs/REDESAIN-UI-V2.md` §8/§8b.
> Seluruh perubahan tampilan hanya di jalur **V2**. 41 halaman V1 tak tersentuh.
>
> Revisi 2 (2026-08-31 sore) — pemilik menambah tiga permintaan: kartu KPI berlencana
> kenaikan, kartu Performa PIC, dan garis waktu bergaya baru. §2b menjelaskan kenapa
> temuan "lencana delta dilarang" **dicabut** dan digantikan lencana yang datanya nyata.

---

## 0. Status pengerjaan

Dokumen ini tak punya penomoran fase saat ditulis; pembagian di bawah disepakati
pemilik 2026-08-31 dan dipakai seterusnya.

| Fase | Isi | Status |
|---|---|---|
| **1** | §6 server (seluruhnya) · §7 tipe · §4c cabut `rona` · §9a 13 tes | ✅ **SELESAI** 2026-08-31 |
| **2** | §4a kembaran V2 · §4b hapus 3 berkas · §4d logo · `pages/V2/Dashboard.tsx` disusun ulang | ✅ **SELESAI** 2026-08-31 (§4b DITAHAN, K3) |
| **3** | §5a–§5g enam widget · judul→topbar · logo diperbesar lagi · §9d utang komentar | ✅ **SELESAI** 2026-08-31 (§4b DITAHAN lagi, K3) |
| **4** | §9d dokumentasi lintas-berkas | ✅ **SELESAI** 2026-08-31 |
| 4 | §9c pemeriksaan mata per peran | ⏳ **menunggu pemilik** — gerbang otomatis tak pernah memeriksa tata letak |

### Rincian Fase 1 — apa yang sudah berdiri

| § | Butir | Status |
|---|---|---|
| §4c | `tiles[].rona` dicabut; `DasborTampilan::tile()` kini `$aksi` + `$arah` | ✅ |
| §4c | `components/v2/dasbor/KartuStatistik.tsx` dilepas dari `rona` | ⚠️ **sebagian** — aksen warna dibuang, tapi lencana + sparkline (§5c) menyusul di Fase 3 |
| §5c | Konstanta `DasborTampilan::AKSI_ALIR` + `KUNCI_PRODUKTIF` + `aksiDisapu()` + `aksiProduktif()` | ✅ |
| §6a | `masukanBaris()` + `statusKunci`, `rona`, `boleh_balas` (menerima `User $user`) | ✅ |
| §6b | `masukanTotal`, `sebaranDepartemen`, `performaPic`, `offSaya[].mulai/.sampai` | ✅ |
| §6c | `menungguDiMeja[]` + `sejak`, `status`; `'dokumen' => ['id' => …]` (§P4) | ✅ |
| §6d | `sebaranDepartemen()` | ✅ |
| §6e | `alirAudit()` + `wajahProduktif()` | ✅ |
| §6f | `performaPic()` | ✅ |
| §6g | `DasborTampilan`: `tiles($alir)`, `aktivitas()` + `kategori`, `sorotan()`, `berlencana()`, `delta()` | ✅ |
| §6h | `config/smartpro.php` tak disentuh | ✅ |
| §7 | `types/dasbor.d.ts` — `Tile` tanpa `rona`, + `SebaranDepartemen`/`PerformaPic`/`Sorotan` | ✅ |
| §9a | `tests/Feature/DasborV2Test.php` — 13 tes | ✅ |
| §P9 | Query `audit_logs` dijalankan sebelum peta ditulis | ✅ (34 aksi, hasilnya di bawah) |

**Gerbang §9b sesudah Fase 1** — seluruhnya dijalankan dari PowerShell:

| Gerbang | Hasil |
|---|---|
| `npm run build` | ✅ bersih |
| `node_modules/.bin/tsc --noEmit` | ✅ nol galat |
| `npm run uji-render` | ✅ bersih |
| `php artisan smartpro:uji-render` | ✅ 29 halaman, `dashboard → Dashboard` |
| `php artisan smartpro:uji-render --pratinjau` | ✅ 29 halaman, `dashboard → V2/Dashboard` |
| `php artisan test` | ✅ **715** (714 lulus + 1 dilewati) — patokan sebelumnya 702, tepat **+13** |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | ✅ 23/23 |
| `php artisan smartpro:cetak-baseline` | ⚠️ 8/8 PDF ber-baseline **identik**; keluar kode 1 karena dokumen **id 1239** ("testing", draft, dibuat Admin IT 2026-08-31 07:21 lewat peramban) belum punya baseline. Bukan akibat perubahan ini — lihat catatan di bawah |
| `git diff components.json` | ✅ KOSONG |
| Ongkos query `/dashboard` sbg PJO | 48 query; penjaga tetap di `DasborV2Test` menolak ≥ 60 |

### Rincian Fase 2 — apa yang sudah berdiri

| § | Butir | Status |
|---|---|---|
| §4a | `components/v2/dasbor/KartuSambutan.tsx` — kembaran mekanis | ✅ |
| §4a | `components/v2/dasbor/GrafikOverview.tsx` — kembaran mekanis | ✅ |
| §4a | `components/v2/dasbor/MeterTertinjau.tsx` — kembaran mekanis | ✅ |
| §4b | Hapus `PitaSambutan` / `ArusDokumen` / `PerluTindakan` | ⛔ **DITAHAN** — K3 ditanyakan ulang di awal Fase 2, pemilik memilih **tahan dulu**. Ketiganya masih di disk; `PitaSambutan` & `ArusDokumen` sudah **nol pengimpor** |
| §4d | Logo sidebar diperbesar (`h-12` / `h-9`, mengecil di rel ikon) | ✅ |
| §3 | `pages/V2/Dashboard.tsx` disusun ulang mengikuti irama V1 | ✅ **sebagian** — lihat dua penampung di bawah |

**Dua slot yang sengaja belum terisi** (keputusan pemilik saat Fase 2: pakai penampung
sementara supaya dasbor tetap utuh dan bisa diperiksa mata di akhir fase):

| Slot §3 | Isi sekarang | Diisi di |
|---|---|---|
| `LacakStatus` 2/3 | `PerluTindakan` yang sudah ada — prop `baris={menungguDiMeja}` sama persis | Fase 3 §5b |
| baris `PitaDepartemen 2/3 \| PerformaPic 1/3` | **belum dipasang** — kedua komponennya belum ada; propsnya (`sebaranDepartemen`, `performaPic`) sudah dikirim server sejak Fase 1 dan menganggur | Fase 3 §5e, §5d |

**Gerbang §9b sesudah Fase 2** — seluruhnya dijalankan dari PowerShell:

| Gerbang | Hasil |
|---|---|
| `npm run build` | ✅ bersih |
| `node_modules/.bin/tsc --noEmit` | ✅ nol galat |
| `npm run uji-render` | ✅ bersih |
| `php artisan smartpro:uji-render` | ✅ 29 halaman, `dashboard → Dashboard` |
| `php artisan smartpro:uji-render --pratinjau` | ✅ 29 halaman, `dashboard → V2/Dashboard` |
| `php artisan test` | ✅ **715** (714 lulus + 1 dilewati) — sama persis dengan patokan Fase 1, nol tes pecah |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | ✅ 23/23 |
| `php artisan smartpro:cetak-baseline` | ⚠️ 8/8 PDF ber-baseline **identik**; tetap keluar kode 1 karena dokumen **id 1239** belum punya baseline — keadaan yang **sama persis** dengan sesudah Fase 1, bukan akibat perubahan ini |
| `git diff components.json` | ✅ KOSONG |

> Fase 2 **nol perubahan server**, jadi ongkos query `/dashboard` tak dihitung ulang —
> ia masih 48 query yang sama dengan Fase 1.

**Verifikasi salinan mekanis §4a** — `tail -n +13 <kembaran> | diff <asli> -` menghasilkan
**hanya** baris impor pada ketiganya, plus satu baris ikon di `KartuSambutan`
(`<ArrowRight />` → `<HugeiconsIcon icon={ArrowRight01Icon} …/>`). Nol baris keputusan
bergeser, sesuai janji banner `KEMBARAN MAIA`.

### Rincian Fase 3 — apa yang sudah berdiri

Keenam widget §5a–§5g berdiri, ditambah tiga permintaan pemilik dari pemeriksaan
mata sesudah Fase 2 dan satu utang komentar §9d.

| § | Butir | Status |
|---|---|---|
| §5a | `KartuKetersediaan` + `Tabs` (Semua/Ditugaskan/Jadwal Saya) + `ScrollArea` agenda; titik agenda lewat `modifiers.agenda` | ✅ |
| §5b | `components/v2/dasbor/LacakStatus.tsx` BARU — 4 angka `matrix` berbatang + `v2/DataTable` + baris bentangan tahapan + kaki hitungan | ✅ |
| §5c | `KartuStatistik` ditulis ulang — `LencanaDelta` + `Sparkline` | ✅ |
| §5d | `components/v2/dasbor/PerformaPic.tsx` BARU | ✅ |
| §5e | `components/v2/dasbor/PitaDepartemen.tsx` BARU — bar bersegmen ramp `--primary` | ✅ |
| §5f | `KartuMasukan` ditulis ulang di atas `ui-maia/item`; titik `rona`, ikon `boleh_balas`, kaki `masukanTotal` | ✅ |
| §5g | `KartuLog` ditulis ulang — titik + judul + lencana `kategori` + keterangan + tanggal | ✅ |
| §3 | `pages/V2/Dashboard.tsx` — baris `PitaDepartemen \| PerformaPic` dipasang; `PerluTindakan` → `LacakStatus` | ✅ |

**Dua berkas kecil yang lahir di fase ini** (bukan di rencana, lahir dari aturan
"jangan menulis ulang aturan server di TSX"):

| Berkas | Kenapa ada |
|---|---|
| `components/v2/dasbor/LencanaDelta.tsx` | Satu panah melayani kartu KPI (§5c) DAN sorotan Performa PIC (§5d) — rencana §5d memang memintanya. `delta` menentukan arah panah, `arah` dari server menentukan warna; TSX tak pernah menebak yang kedua dari yang pertama. |
| `components/v2/dasbor/Sparkline.tsx` | Registry tak punya sparkline. Dipisah supaya alasan tidak-memakai-recharts (empat `ResizeObserver` demi 8 angka) tertulis di satu tempat. |

**Satu komponen yang disunting seminimal mungkin:** `TumpukanWajah` mendapat DUA
prop OPSIONAL (`satuan`, dan `ket` per orang). Keduanya berdefault ke perilaku
lama, jadi seluruh pemanggil yang ada tak berubah satu karakter pun — dipakai
ulang alih-alih digambar ulang, sesuai §10.

**Tiga permintaan pemilik dari pemeriksaan mata sesudah Fase 2:**

| # | Permintaan | Yang dikerjakan |
|---|---|---|
| M1 | "Logo masih belum terlalu besar, skalanya kecil sekali" | `AppSidebar` tombol `h-12`→`h-16`, `<img>` `h-9`→`h-12`. Di rel ikon tetap `h-8`/`h-6` supaya merek tak melebar dari rel 3rem. |
| M2 | "Tulisan Dashboard letakkan di samping tombol sidebar-trigger" | Judul halaman PINDAH ke topbar sebagai `<h1 className="text-base font-medium">` — dan itu justru MENGEMBALIKANNYA ke blok `site-header.tsx` `dashboard-01`. Blok judul H1 besar di badan `AppLayout` DICOPOT; yang tersisa baris `sub`+`aksi`, dan hanya bila halaman mengisinya. Halaman ber-remah menggambar remahnya dan menyimpan judulnya sebagai H1 `sr-only` — tepat satu H1 per halaman, nol nama tercetak dua kali. Berlaku untuk SELURUH 12 halaman V2 (keputusan pemilik: satu pola judul, bukan dua). |
| M3 | Utang komentar §9d | Prop `kepala` di `layouts/V2/AppLayout.tsx` **DICOPOT**, bukan cuma komentarnya diperbaiki — sejak §3 kembali ke irama V1 ia nol pemakai, dan prop tanpa pemakai adalah cabang yang tak pernah diuji. Docblock `AppLayout` + `SiteHeader` ditulis ulang mengikuti keadaan sekarang. |

**Utang komentar K1 yang ikut dibereskan** (semuanya masih berbunyi "GL &
Non-Staff" padahal K1 memutuskan gerbangnya `dashboardPenuh()` harfiah sehingga
**GL LULUS**): `types/dasbor.d.ts` (2 tempat), `DashboardController::performaPic()`,
`::sebaranDepartemen()`, `::wajahProduktif()`, dan komentar props `index()`.

**Gerbang §9b sesudah Fase 3** — seluruhnya dijalankan dari PowerShell:

| Gerbang | Hasil |
|---|---|
| `npm run build` | ✅ bersih |
| `node_modules/.bin/tsc --noEmit` | ✅ nol galat |
| `npm run uji-render` | ✅ bersih |
| `php artisan smartpro:uji-render` | ✅ 28 halaman, `dashboard → Dashboard` |
| `php artisan smartpro:uji-render --pratinjau` | ✅ 28 halaman, `dashboard → V2/Dashboard` |
| `php artisan test` | ✅ **715** (714 lulus + 1 dilewati) — sama persis dengan Fase 1–2, nol tes pecah |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | ✅ 23/23 |
| `php artisan smartpro:cetak-baseline` | ⚠️ 8/8 PDF ber-baseline **identik**; tetap keluar kode 1 karena dokumen **id 1239** belum punya baseline — keadaan yang SAMA PERSIS dengan Fase 1–2. ⟲ Ditanyakan lagi di awal Fase 3, **pemilik memilih "biarkan, catat saja"** |
| `git diff components.json` | ✅ KOSONG |

> **Angka sapuan render 28, bukan 29 — sudah ditelusuri, bukan regresi.**
> Kandidatnya 29 rute GET bernama tanpa parameter. Yang ke-29 adalah
> **`pending`** (`PageController::pending`), dan ia `redirect()->route('dashboard')`
> untuk pengguna ber-`status === 'active'` — jadi ia hanya menghasilkan payload
> Inertia bila personanya BELUM aktif. Sapuan hari ini memakai persona yang
> seluruhnya aktif, jadi ia dilewati. Jumlahnya bergantung DATA, bukan kode;
> Fase 3 nol menyentuh `routes/web.php` maupun `PageController`. Dicatat di sini
> supaya angka yang bergoyang antar-sesi tak pernah dikira regresi.

> Fase 3 **nol perubahan perilaku server** (yang disentuh cuma docblock), jadi
> ongkos query `/dashboard` tak dihitung ulang — ia masih 48 query yang sama
> dengan Fase 1, dan penjaga `DasborV2Test` yang menolak ≥ 60 tetap hijau.

### Keputusan pemilik 2026-08-31 yang MENGUBAH rencana ini

| # | Yang tertulis di rencana | Keputusan | Berlaku di |
|---|---|---|---|
| K1 | §3, §5d, §9a #1/#8, §9c: `sebaranDepartemen` & `performaPic` **`null` bagi GL & Non-Staff** — padahal §5e/§6d menamai gerbangnya `dashboardPenuh()`, dan `User::dashboardPenuh()` = `jabatan !== 'staff'` sehingga **GL LULUS**. Kedua pernyataan mustahil benar bersamaan. | **Turuti mekanismenya**: gerbangnya `dashboardPenuh()` harfiah. **GL IKUT mendapat kedua kartu**; hanya Non-Staff yang nihil. | §5d · §5e · §6d · §6f · §9a #1 · §9a #8 · §9c (ketiga baris "GL & Non-Staff" di §9c wajib dibaca "Non-Staff saja") |
| K2 | §P9: peta `AKSI_ALIR` hanya boleh memuat aksi yang **muncul di hasil query `audit_logs`**. Dua aksi yang dibutuhkan nol baris di DB pengembangan: `document.approval_reject` & `feedback.respond`. | **Ikutkan keduanya**; gerbang §P9 dipindah ke **katalog KODE** (`AuditLog::AKSI_META` ∪ `DasborTampilan::AKSI`). Nol baris ≠ salah nama — keduanya terbukti ditulis `ApprovalController:240` & `DocumentFeedbackService:75`. | §5c · §6e · §9a #13 |
| K3 | §4b: tiga berkas antre dihapus (`PitaSambutan`, `ArusDokumen`, `PerluTindakan`). | **JANGAN dihapus dulu.** Ditanyakan ulang tiap fase supaya tak tertinggal, keputusannya di tangan pemilik. ⟲ **Ditanyakan lagi di awal Fase 2 → pemilik tetap memilih "tahan dulu"**: ketiganya dibiarkan di disk sebagai pembanding, tapi tak lagi digambar ke pengguna. | §4b · §10 |

> **⚠️ PENGINGAT AKTIF (K3).** Ketiga berkas ini masih hidup dan belum dihapus:
>
> | Berkas | Pengimpor sesudah Fase 2 |
> |---|---|
> | `components/v2/dasbor/PitaSambutan.tsx` | **nol** — `pages/V2/Dashboard.tsx` kembali memakai blok judul `AppLayout` + `KartuSambutan` |
> | `components/v2/dasbor/ArusDokumen.tsx` | **nol** — digantikan `GrafikOverview` + `MeterTertinjau` |
> | `components/v2/dasbor/PerluTindakan.tsx` | **nol sejak Fase 3** — `LacakStatus` (§5b) sudah berdiri dan menggantikannya di `pages/V2/Dashboard.tsx` |
>
> ⟲ Ditanyakan lagi di awal **Fase 3** → pemilik **tetap memilih "tahan semua"**.
> Ketiganya kini nol pengimpor, jadi ketiganya sudah bisa dihapus kapan saja — tak
> ada lagi yang menunggu prasyarat. Tanyakan lagi di awal **Fase 4**.

### Hasil query §P9 (dijalankan 2026-08-31, DB `smartpro_shadcn`)

`SELECT action, COUNT(*) FROM audit_logs GROUP BY action ORDER BY 2 DESC` → 34 aksi.
Yang relevan bagi `AKSI_ALIR`:

| Aksi | Baris | Dipakai kunci |
|---|---|---|
| `document.create` | 33 | `dibuat` |
| `document.submit` | 28 | `dikirim` |
| `document.review_approve` | 9 | `diloloskan` |
| `document.approve` | 8 | `berlaku` |
| `document.md_approve` | 6 | `md` |
| `document.request_revision` | 6 | `revisi` |
| `document.review_reject` | 6 | `ditolak` |
| `document.arsip_upload` | 5 | `berlaku` |
| `document.approval_reject` | **0** | `ditolak` — diloloskan lewat K2 |
| `feedback.respond` | **0** | `masukan_tutup` — diloloskan lewat K2 |

Yang **tidak** dipetakan meski ramai: `user.login` (143), `user.logout` (102),
`document.purge` (38), `document.review_start` (21), `document.ai_review` (11) —
tak satu pun mewakili "sesuatu masuk ke keadaan ini".

### Catatan gerbang cetak

`smartpro:cetak-baseline` keluar kode 1, dan itu **bukan** temuan mesin cetak:

```
sama   SOP_PPA-ADRO-SOP-SHE-03_id1177.pdf        …  (8 berkas)
HILANG SOP_PPA-ADRO-SOP-ICTMD-03_id1239.pdf      (belum ada baseline)
```

Kedelapan PDF yang punya baseline **identik byte per byte**. Yang kesembilan adalah
draft "testing" yang dibuat pemilik lewat peramban pagi itu (id 1239, 07:21:36, Admin
IT) — sesudah baseline diambil 28 Agustus. Suite tes memakai `DatabaseTransactions`
sehingga tak pernah meninggalkan dokumen; dokumen terbaru berikutnya bertanggal 28
Agustus. **Keputusan pemilik yang ditunggu:** tambahkan id 1239 ke
`C:\baseline-smartpro\pdf` (jalankan ulang penangkapnya), atau hapus draft ujinya.
Menambah berkas ke baseline adalah mengubah acuan kebenaran, jadi tidak dikerjakan sendiri.

---

## 1. Keputusan pemilik

| # | Keputusan |
|---|---|
| D1 | Dari V2 yang dipertahankan **hanya**: tema maia, ikon hugeicons, desain sidebar. |
| D2 | Dasbor **balik penuh ke tata letak V1**. Pita merek, tile berona, `ArusDokumen` dibuang. |
| D3 | Halaman V2 non-dasbor (`Documents/*`, `Review/Show`, `Auth/Login`, blok judul `AppLayout`) **dibiarkan**. |
| D4 | Empat widget diganti desain baru mengikuti referensi visual yang dilampirkan. |
| D5 | Tab kalender = **Semua / Ditugaskan / Jadwal Saya**. |
| D6 | Bar proporsi = **sebaran per DEPARTEMEN**; donat `KartuSebaran` tetap ada. |
| D7 | Logo dasbor diperbesar. |
| **D8** | **Empat kartu KPI memakai lencana kenaikan** (`↗ 20.1% dari bulan lalu`). |
| **D9** | **Tambah kartu Performa PIC** (angka besar + deret wajah + tiga sorotan berpanah). |
| **D10** | **Garis waktu digambar ulang**: titik berwarna + judul + lencana + keterangan + tanggal. |
| **D11** | Desain yang ada diganti desain shadcn semaksimal mungkin; data yang menganggur dimanfaatkan. |

---

## 2. Temuan review — tindakan preventif

Delapan temuan dari review pertama, plus satu yang **dicabut** oleh D8.

### P1 · Menaikkan batas `menungguDiMeja` 4 → 12 — **DIBATALKAN**

Rencana pertama menaikkan `limit(4)` di `DashboardController.php:408`. Dua akibat yang tak terlihat:

1. **Memecah tes yang ada.** `tests/Feature/DashboardWidgetTest.php:124` menegaskan `assertCount(4, $this->idDiMeja($gl))`, docblock "TEPAT 4 baris (spec v2 L1)". CLAUDE.md §14c: jumlah tes tak boleh berkurang, dan tes yang terasa tak relevan adalah tanda ada perilaku yang hilang.
2. **Mengubah V1.** Props Inertia dibagi V1 dan V2 (P2). Dua belas baris akan masuk `components/dasbor/KartuPerjalanan.tsx` yang dirancang untuk empat — persis alasan di komentar `:405-407`.

**Tindakan:** batas tetap 4. Tabel Lacak Status memuat empat baris, tanpa paginasi dan tanpa kotak saring. Angka totalnya tetap jujur karena dijumlahkan dari `matrix` (§5b). `config('smartpro.dashboard.meja_widget')` **tidak jadi ditambahkan**.

### P2 · Props Inertia SELALU dibagi V1 dan V2 — aturan baru

`App\Inertia\PratinjauResponseFactory::render()` hanya menukar **nama komponen**; controller, props, dan policy identik. Tak ada "props khusus V2".

> **Aturan untuk seluruh pekerjaan ini:** perubahan server hanya boleh **ADITIF** — prop baru, atau kunci baru pada baris yang sudah ada. Dilarang mengubah bentuk, jumlah, atau nilai prop yang sudah digambar V1.

| Perubahan | Sifat | Aman? |
|---|---|---|
| prop `sebaranDepartemen`, `performaPic`, `masukanTotal` | prop baru | ya |
| `tiles[].deret` + `tiles[].delta` | kunci baru | ya — V1 mengabaikan kunci tak dikenal |
| `masukanBaris()` + `rona`, `statusKunci`, `boleh_balas` | kunci baru | ya |
| `offSaya[]` + `mulai`, `sampai` | kunci baru | ya |
| `menungguDiMeja[]` + `sejak`, `status` | kunci baru | ya |
| `activities[]` + `kategori` | kunci baru | ya |
| `limit(4)` → 12 | **mengubah jumlah** | TIDAK — dibatalkan (P1) |
| `tile()` buang `rona` | mengembalikan ke keadaan commit | ya — V1 tak pernah membacanya |

### P2b · ⟲ Temuan "lencana delta dilarang" — **DICABUT, diganti lencana nyata**

Review pertama membuang lencana `↑0.5%` dengan alasan `REDESAIN-UI-V2 §8b` melarang lencana tren karangan. Pemilik memintanya kembali (D8). Larangannya **tetap sah** — yang dilarang adalah angka *karangan*, bukan lencana itu sendiri. Jadi lencananya dipasang, dan **datanya dibuat nyata**.

Sumbernya sudah ada dan belum pernah disentuh: tabel **`audit_logs`**, yang mencatat setiap transisi keadaan lengkap dengan `created_at`, `document_id`, dan `user_id` — ketiganya **ter-indeks** (`database/migrations/*_create_audit_logs_table.php`). Rinciannya di §5c.

**Tindakan pencegahan yang menyertainya:** tile yang tak punya aksi audit yang jujur mewakilinya **tidak mendapat lencana sama sekali** (`delta: null`) — bukan mendapat nol atau angka yang dikira-kira. Lebih baik satu kartu tanpa lencana daripada empat kartu yang salah satunya berbohong.

### P3 · `components.json` masih `radix-nova` + `lucide` + `ui`

```json
"style": "radix-nova",  "iconLibrary": "lucide",  "aliases": { "ui": "@/components/ui" }
```

Komponen maia dulu dibuat dengan menukar tiga nilai ini sementara, lalu mengembalikannya. Artinya `npx shadcn@latest add <x>` **hari ini** akan menulis komponen **nova berikon lucide** ke `components/ui/` — gaya yang salah untuk V2, sekaligus menimpa berkas V1 yang CLAUDE.md §4 larang disentuh.

**Tindakan pertama — hindari sama sekali.** `components/ui-maia/` memuat **42 komponen**, superset penuh dari `components/ui/`. Seluruh kebutuhan enam widget sudah ada:

`tabs` · `progress` · `scroll-area` · `chart` · `calendar` · `item` · `badge` · `card` · `empty` · `separator` · `avatar` · `hover-card` · `tooltip` · `dropdown-menu`

→ **Nol `shadcn add` diperlukan.** Kalau nanti perlu:

```
1. tukar sementara di components.json:
   style → radix-maia · iconLibrary → hugeicons · aliases.ui → @/components/ui-maia
2. npx shadcn@latest add <komponen>
3. KEMBALIKAN components.json ke keadaan semula (nova / lucide / ui)
4. git diff components.json  → wajib kosong
```

> Catatan sesi: MCP server `shadcn` gagal tersambung (`CONNECT_TIMEOUT`), jadi `npx shadcn view <blok>` tak terverifikasi dari sini. Blok yang dirujuk di §5 adalah blok yang **sudah** dipakai dan tercatat di berkas yang ada — bukan nama karangan. Kalau jaringan pulih, tarik ulang dan samakan angkanya (CLAUDE.md §13).

### P4 · `'dokumen' => $d` membocorkan model `User` pembuat — DIPERBAIKI

`DashboardController.php:436` mengirim model `Document` **utuh**, dan `:391` memuat relasi `creator`. Model itu diserialkan bulat-bulat ke atribut `data-page`, terbaca lewat "view source". `User::$hidden` (`app/Models/User.php:130`) hanya menutup `password` dan `remember_token` — `email`, `nrp`, `jabatan`, `department_id`, dan seluruh kolom lain ikut terkirim.

Persis kelas kebocoran yang CLAUDE.md §4 catat nyaris terjadi di Fase 8 (relasi `creator`). Tipenya sendiri sudah menyatakan kontrak yang benar — `resources/js/types/dasbor.d.ts:67` menulis `dokumen: { id: number }` — dan satu-satunya pembacaan di TSX adalah `b.dokumen.id` (`KartuPerjalanan.tsx:76`).

**Tindakan:** `'dokumen' => $d` → `'dokumen' => ['id' => $d->id]`. Menyamakan kiriman dengan kontrak yang sudah tertulis, mengecilkan muatan, menutup kebocoran. Menyentuh muatan V1 juga — disengaja: perbaikan keamanan tidak dikecualikan oleh "jangan sentuh V1", dan nol penggambaran V1 yang bergantung padanya.

> **Aturan yang sama berlaku untuk kartu Performa PIC (§5d):** ia menampilkan wajah dan nama orang, jadi ia WAJIB mengirim `['nama', 'foto', 'jumlah']` — bukan model `User`, bukan koleksi `User`.

### P5 · Tujuh hex departemen berisiko gagal ambang keterbacaan §6 — **DICABUT 2026-09-02**

> **DICABUT** oleh rencana pra-produksi butir 9 (permintaan pemilik: warna berbeda per departemen, K-G). Yang dicabut adalah LARANGANNYA, bukan alasannya: palet karangan tetap terlarang. Yang dipakai sekarang tujuh **token tema** — `--chart-1..5` yang sudah lolos validator §6, ditambah `--chart-6/7` yang dicari dengan validator yang SAMA dengan kelima slot lama dikunci (terang deutan 14.8 · protan 15.0 · tritan 16.0 · normal 18.7; gelap deutan 13.9 · protan 15.0 · tritan 14.8 · normal 18.8 — angka & metodenya di komentar `resources/css/app.css`). Warna berkunci **kode departemen**, bukan peringkat, dan kelengkapan petanya dijaga `DasborV2Test::test_peta_warna_sebaran_lengkap()`. Tetap TAK ADA `Department::RUPA`, migration, maupun kolom `warna`.

Isi aslinya, sebagai catatan kenapa palet karangan tak boleh kembali:


Rencana pertama menambahkan `Department::RUPA` berisi tujuh hex. `REDESAIN-UI-V2 §6` sudah membuktikan bahaya persis di sini: palet yang "terlihat berbeda" ternyata ber-ΔE **4.1** pada deuteranopia, jauh di bawah ambang 12 yang dikalibrasi terhadap Okabe–Ito. Mengarang tujuh warna kategorikal baru mengulangi kesalahan itu tanpa validator yang menangkapnya.

**Tindakan — lebih sedikit kode DAN lebih benar:** bar departemen **diurutkan menurun** dan diwarnai **satu ramp `--primary`** (opasitas menurun mengikuti peringkat).

- Ramp sekuensial dipakai **secara sekuensial** — kebalikan dari cacat yang §6 temukan.
- Nol hex, nol token baru, nol validator yang perlu dijalankan.
- Identitas departemen tetap jelas: kodenya tertulis di legenda beserta angkanya.
- Tak ada `Department::RUPA`, tak ada migration, tak ada kolom `warna`.

### P6 · Gerbang render V2 memakai `--pratinjau`, bukan `--uri` dua kali

Opsi yang menyalakan `session('ui_v2')` adalah **`--pratinjau`** (`app/Console/Commands/UjiRender.php:53`, `:225`). `--uri` hanya menambahkan alamat berparameter ke sapuan. Perintah yang benar ada di §9b.

### P7 · Penghapusan berkas butuh konfirmasi eksplisit

CLAUDE.md §4 melarang menghapus berkas tanpa konfirmasi. Tiga berkas antre dihapus (`PitaSambutan`, `ArusDokumen`, `PerluTindakan`); disebut terpisah di §4b supaya tak lolos sebagai efek samping.

### P8 · `masukanBaris()` perlu `User`, dan aturannya jangan ditulis ulang

Menambah `boleh_balas` berarti `masukanBaris()` menerima `User $user` (pemanggilnya `:504` dan `:520`). Aturan izinnya **sudah ada** di `DocumentFeedback::bisaDibalasOleh()` (`app/Models/DocumentFeedback.php:93-114`). Panggil method itu; jangan salin syaratnya ke controller maupun TSX (CLAUDE.md §4). `document` sudah ter-eager-load → nol query tambahan.

### P9 · ⚠️ BARU — nama aksi audit wajib diverifikasi ke DB sebelum dipetakan

Menyapu `grep` atas kode menghasilkan 57 string ber-pola `document.*` / `user.*`, tapi **sebagiannya nama IZIN spatie, bukan aksi audit** — `document.view_all`, `document.review`, `document.review_md`, `document.create` muncul di kedua peran. Memetakan tile ke nama yang ternyata izin akan membuat lencana itu menunjukkan **0 selamanya**, diam-diam, tanpa satu gerbang pun merah.

**Tindakan wajib sebelum menulis §5c:**

```sql
SELECT action, COUNT(*) FROM audit_logs GROUP BY action ORDER BY 2 DESC;
```

Peta di §5c hanya boleh memuat aksi yang muncul di hasil query itu. Aksi yang tak muncul → tile-nya `delta: null` (P2b).

### Sudah aman — diperiksa, tak perlu tindakan

- **`matrix` disaring hak akses.** `DashboardController:173` memakai closure `$visible()` yang sama dengan `$stats` dan `$sebaran` (`:26-29`). Aman digambar.
- **`DasborTampilan.php` boleh di-revert bulat.** `git diff` berkas itu **hanya** berisi `rona`.
- **`uji-render` tak butuh pendaftaran.** `resources/js/uji-render.tsx:29` memakai `import.meta.glob('./pages/**/*.tsx')`.
- **`DashboardRenderTest:44` mengunci `has('tiles', 4)`** — jumlah saja, bukan `rona`. Mencabut `rona` aman.
- **`audit_logs` ter-indeks di `action`, `document_id`, `user_id`, `created_at`** — keempat kolom yang dipakai §5c dan §5d. Nol migration.

---

## 3. Susunan dasbor V2 sesudah perubahan

Tujuh baris, seluruhnya berirama 2/3 + 1/3 — nol baris penuh yang menyendiri, nol ganjal kosong:

```
AppLayout judul="Dashboard"                          ← ✅ blok H1 AppLayout V2 tetap (D3)

  KartuSambutan                                      ← ✅ kembaran V2, BARU (salinan mekanis)
  GrafikOverview 2/3        | MeterTertinjau 1/3     ← ✅ kembaran V2, BARU (satu state `durasi`)
  KartuStatistik  ★ lencana kenaikan + sparkline     ← ✅ Fase 3 · D8 · §5c
  KartuDistribusi 2/3       | KartuSebaran 1/3       ← ✅ V2 yang ada, tak berubah
  PitaDepartemen 2/3        | PerformaPic 1/3        ← ✅ Fase 3 · D6 + D9 · §5e, §5d
  LacakStatus 2/3           | KartuKetersediaan 1/3  ← ✅ Fase 3 · §5b, §5a
  KartuMasukan 2/3          | KartuLog 1/3           ← ✅ Fase 3 · §5f, §5g (D10)
```

Legenda: ✅ berdiri · ⏳ slotnya terisi tapi isinya menunggu · ⬜ barisnya belum ada.
Sejak Fase 3 seluruh baris ✅.

> Blok H1 `AppLayout` di baris pertama SUDAH DICOPOT (M1–M3 di rincian Fase 3):
> judulnya pindah ke topbar, sebaris dengan `SidebarTrigger`. Yang tersisa di badan
> cuma baris `sub`+`aksi`, dan dasbor tak mengisi keduanya — jadi halaman membuka
> langsung dengan `KartuSambutan`.

Gerbang tampil (`adaMeja`, `adaMasukan`, `duaKolom`, `distribusiWidget !== null`) disalin **apa adanya** dari `pages/Dashboard.tsx:69-71` — ✅ sudah, **termasuk ganjal `<div hidden>`-nya**. Ganjal itu dulu dibuang versi nova; ia dikembalikan karena tanpanya kolom 1/3 melompat ke kiri pada peran yang kehilangan kartu 2/3, dan dua kerangka jadi tak bisa dibandingkan berdampingan (§9c baris terakhir).

**Kenapa `PitaDepartemen` dan `PerformaPic` sebaris:** keduanya digerbangi `dashboardPenuh()` yang sama, jadi bagi **Non-Staff** (K1 — bukan GL) keduanya hilang **bersamaan** dan barisnya lenyap utuh. Menaruh salah satunya sendirian akan meninggalkan satu kolom yatim persis pada peran yang paling banyak jumlahnya.

---

## 4. Bagian 1 — Balik ke V1

### 4a. Kembaran V2 baru (salinan mekanis) — ✅ **SELESAI Fase 2**

Disalin dari V1, hanya menukar `@/components/ui` → `@/components/ui-maia`, `lucide-react` → `@/components/v2/Ikon`, `@/components/dasbor/*` → `@/components/v2/dasbor/*`. Pakai banner 12-baris `KEMBARAN MAIA dari …` yang sudah jadi konvensi di `components/v2/dokumen/fields/*`.

| Sumber | Tujuan | Sumber registry |
|---|---|---|
| `components/dasbor/KartuSambutan.tsx` | `components/v2/dasbor/KartuSambutan.tsx` | `dashboard-01` → `Card`/`CardHeader`/`CardAction` |
| `components/dasbor/GrafikOverview.tsx` | `components/v2/dasbor/GrafikOverview.tsx` | blok `chart-area-interactive` |
| `components/dasbor/MeterTertinjau.tsx` | `components/v2/dasbor/MeterTertinjau.tsx` | blok `chart-radial-text` |

`GrafikOverview` dan `MeterTertinjau` memakai `ui-maia/chart.tsx` (sudah ada) dan hook bersama `@/hooks/use-mobile`. Palet grafik `.ui-v2` di `resources/css/app.css:166-178` **tetap** — itu bagian "tema" (D1).

> Saat menyalin, ikuti pelajaran T2: pakai `perl -CSD` (bukan `perl -0pi` polos) supaya `§`, `—`, dan `→` tak jadi mojibake, lalu bandingkan dengan `tail -n +13` + `diff --strip-trailing-cr`.

### 4b. Dibuang (§P7 — butuh konfirmasi) — ⛔ **MASIH DITAHAN (K3): ketiga berkas MASIH ADA.** Ditanyakan ulang di awal Fase 2 DAN Fase 3; pemilik tetap memilih menahan. Sejak Fase 3 ketiganya nol pengimpor. Tanyakan lagi di awal Fase 4.

- `components/v2/dasbor/PitaSambutan.tsx` — antre dihapus. **Nol pengimpor sejak Fase 2** — sudah bisa dihapus kapan saja.
- `components/v2/dasbor/ArusDokumen.tsx` — antre dihapus. **Nol pengimpor sejak Fase 2** — sudah bisa dihapus kapan saja.
- `components/v2/dasbor/PerluTindakan.tsx` — digantikan `LacakStatus.tsx` (§5b), yang berdiri di Fase 3. **Nol pengimpor sejak Fase 3** — prasyaratnya sudah lunas, sudah bisa dihapus kapan saja.

### 4c. Rona tile dicabut — ✅ **SELESAI Fase 1** (kecuali tulis-ulang `KartuStatistik` §5c yang menyusul di Fase 3)

```
git checkout 0ebcc85 -- app/Services/DasborTampilan.php    # diff-nya murni `rona`
```

Lalu `DasborTampilan::tile()` dibangun ulang dengan kunci yang **berbeda maksudnya** (§5c): bukan `rona` (nada warna), melainkan `aksi` (aksi audit yang menyuapi lencananya). `resources/js/types/dasbor.d.ts` buang `Tile.rona`.

`components/v2/dasbor/KartuStatistik.tsx` ditulis ulang di §5c — kartunya tetap `section-cards.tsx` dari `dashboard-01`, tanpa batang tepi dan tanpa kotak ikon bernada.

Kosakata `maju | netral | baik | buruk` tidak hilang dari proyek — ia tetap milik `DasborTampilan::AKSI`/`KartuLog`, dan dipakai ulang untuk baris masukan (§6a) dan garis waktu (§5g).

### 4d. Logo sidebar diperbesar (D7) — ✅ **SELESAI Fase 2, DIPERBESAR LAGI Fase 3 (M1)**

`components/v2/AppSidebar.tsx:59-76`. `SidebarMenuButton` maia setinggi `h-8`; menaikkan `<img>` saja akan terpotong, jadi tombolnya ikut ditinggikan — dan dikecilkan lagi di rel ikon supaya tak melebar dari rel 3rem:

Angka Fase 2 (`h-12`/`h-9`) masih dinilai terlalu kecil pada pemeriksaan mata —
logonya wordmark yang melebar, jadi tinggi 9 satuan menyisakan huruf yang nyaris
tak terbaca di sidebar selebar 18rem. Fase 3 menaikkannya lagi:

```tsx
// tombol merek
className="h-16 group-data-[collapsible=icon]:h-8 data-[slot=sidebar-menu-button]:p-1.5!"

// kedua <img>  (terang & gelap)
className="h-12 w-auto object-contain group-data-[collapsible=icon]:h-6 dark:hidden"
```

Dua berkas logo tetap dua (CSS yang memilih terang/gelap, nol JS, nol kedipan) — jangan disatukan.

---

## 5. Bagian 2 — Widget

Prinsip untuk keenamnya: **komponen dari `ui-maia/` apa adanya, nol hex di TSX, nol angka yang menyiratkan data yang tak kita punya.** Penyimpangan dari registry wajib ditulis alasannya di komentar berkas (CLAUDE.md §13).

### 5a. Kalender ketersediaan + tab (referensi 1) — kolom 1/3 — ✅ **SELESAI Fase 3**

**Berkas:** `components/v2/dasbor/KartuKetersediaan.tsx` — **ditulis ulang di tempat**, bukan komponen paralel. Kalender `react-day-picker`, dialog Ajukan Off, dan nav bulan `?bulan=` sudah hidup di sana.

**Susunan:** `Calendar` → `Tabs` → `ScrollArea` berisi daftar agenda.
**Komponen:** `ui-maia/{calendar,tabs,scroll-area,item,badge,dialog}`. Tak ada blok registry untuk "kalender + tab agenda"; disusun dari primitif — alasan ditulis di komentar berkas.

| Tab | Isi | Sumber |
|---|---|---|
| Semua | gabungan, urut tanggal | — |
| Ditugaskan | dokumen yang menunggu tindakan saya, di tanggal ia masuk ke meja + umurnya | `menungguDiMeja[].sejak` (§6c) |
| Jadwal Saya | cuti / off day / dinas luar saya | `offSaya[].mulai` / `.sampai` (§6b) |

- **Titik di bawah tanggal** diturunkan di klien: `modifiers={{ agenda: tanggalAgenda }}` mencocokkan tanggal agenda dengan `kalenderOff.sel[].tanggal`. Nol perubahan server.
- Tab "Ditugaskan" memuat **paling banyak 4 butir** — konsekuensi P1, dan itu jujur: keempatnya memang yang paling lama menunggu (`orderBy('submitted_at')`).
- **Wajib bertahan:** nav bulan lewat `<Link href="?bulan=">` asli, klik tanggal membuka dialog Ajukan Off terisi (`bukaDengan()`), tanggal lampau mati, tombol "On Site" hanya saat `offAktif`.
- Tanggal tetap string `'Y-m-d'` dan **dibandingkan sebagai string** — jangan lewat `new Date()`. Alasannya di `DashboardController.php:314-317` (geser hari UTC/WITA).

### 5b. Lacak Status Dokumen (referensi 2) — kolom 2/3 — ✅ **SELESAI Fase 3**

**Berkas:** `components/v2/dasbor/LacakStatus.tsx`, menggantikan `PerluTindakan.tsx`.

**Susunan:** judul + tautan "Lihat semua" → empat angka status berbatang → tabel → baris ringkas jumlah.

- **Empat angka** — `Draft · Dalam Peninjauan · Menunggu Persetujuan · Ditolak`, dari prop **`matrix`** yang sudah dikirim server sejak lama dan **belum pernah digambar sekali pun** (`DashboardController.php:173-188`, sudah disaring `$visible()`). Batangnya `ui-maia/progress` — porsi terhadap total `matrix`. Warnanya dari prop bersama `statusMeta`.
- **Tabel** memakai **`components/v2/DataTable.tsx` yang sudah ada** (arketipe tabel V2 dari `pages/V2/Documents/Index.tsx`) — nol kode tabel baru. Baris dari `menungguDiMeja` (4). Kolom `Nomor · Judul · Dept · Menunggu · Umur · Status`.
- **Baris dapat dibentangkan** (`perluas` — kemampuan yang `v2/DataTable` sudah punya) untuk memperlihatkan **tahapan dokumen** memakai desain garis waktu yang sama dengan D10: `Dibuat ✓ · Ditinjau ● · MD ○ · Disetujui ○`. Datanya `daftarTahap`, `ke`, `persen` — **sudah dikirim server dan akan menganggur** begitu pita tahap V1 tak ikut pindah. Ini memenuhi D11 tanpa satu query pun.
- **Footer** = "4 dari N dokumen berjalan" + tautan `documents.index`. `N` dijumlahkan di klien dari lima status berjalan di `matrix` — nol query.

**Yang sengaja TIDAK diambil dari referensi:**

| Elemen referensi | Keputusan | Alasan |
|---|---|---|
| Tombol `Export` | jadi "Lihat semua" | Tak ada endpoint ekspor massal; `DocumentExportController` per-dokumen dan masuk daftar HARAM CLAUDE.md §14b. |
| Kotak `Filter…` + `Columns` | dibuang | Empat baris. Kendali penyaring di atas empat baris adalah hiasan. |
| `Previous` / `Next` | jadi hitungan + tautan | Idem — tautannya mengantar ke daftar yang memang berpaginasi sungguhan. |

> Lencana delta di baris angka ini **tidak** dipasang — berbeda dengan §5c. Keempatnya diambil dari `matrix` yang merupakan potret sesaat lintas seluruh riwayat, bukan aliran per periode; delta di atasnya akan mengukur hal yang berbeda dari yang dibaca orang.

### 5c. ★ Kartu KPI berlencana kenaikan (referensi 5) — D8 — ✅ **SELESAI:** datanya di Fase 1, kartunya di Fase 3

**Berkas:** `components/v2/dasbor/KartuStatistik.tsx` — ditulis ulang.
**Sumber registry:** `section-cards.tsx` dari blok `dashboard-01` **apa adanya**, termasuk `CardAction` untuk lencananya dan `CardFooter` untuk keterangannya. Inilah blok yang referensi pemilik sendiri turunkan — jadi tak ada yang perlu dikarang.

```
┌──────────────────────────────────┐
│ Dokumen Berlaku              [⛉] │  ← CardDescription + CardAction (ikon)
│ 128                              │  ← CardTitle, tabular-nums, text-3xl
│ ╭────────────────────────────╮   │  ← sparkline 8 titik, h-10, --primary/25
│ ↗ 20.1%  vs 30 hari sebelumnya   │  ← Badge outline + CardFooter
└──────────────────────────────────┘
```

#### Datanya nyata — dari `audit_logs`

Tiap tile menyatakan **satu aksi audit** yang mewakili "sesuatu masuk ke keadaan ini". Satu query saja, dikelompokkan aksi × bulan, delapan bulan, disaring hak akses:

```php
// SATU query untuk kedelapan tile. audit_logs ter-indeks di action + created_at.
AuditLog::query()
    ->where('created_at', '>=', now()->startOfMonth()->subMonths(7))
    ->whereIn('action', $aksiTerpakai)
    ->when(! $user->can('document.view_all'), fn ($q) => $q->whereIn(
        'document_id', (clone $visible())->select('id')
    ))
    ->selectRaw("action, DATE_FORMAT(created_at, '%Y-%m') AS bulan, COUNT(*) AS c")
    ->groupBy('action', 'bulan')
    ->get();
```

Peta tile → aksi (**wajib diverifikasi lebih dulu lewat P9**):

| Tile | Aksi audit | Lencana |
|---|---|---|
| Dokumen Saya | `document.create` (disaring `user_id = saya`) | ya |
| Total Dokumen | `document.create` | ya |
| Berlaku | `document.approve` + `document.arsip_upload` | ya |
| Dokumen Ditolak | `document.approval_reject` + `document.review_reject` | ya |
| Sedang Revisi | `document.request_revision` | ya |
| Menunggu Ditinjau | `document.submit` | ya |
| Perlu Disetujui | `document.review_approve` | ya |
| **Perlu Diperiksa (MD)** | — tak ada aksi yang mewakilinya secara jujur | **TIDAK** (`delta: null`) |

- **Delta = 30 hari terakhir vs 30 hari sebelumnya**, bukan "bulan kalender ini vs bulan lalu". Alasannya keras: pada tanggal 3, bulan kalender berjalan baru berumur tiga hari, dan membandingkannya dengan bulan penuh akan menampilkan `↘ 90%` setiap awal bulan — angka nyata yang menceritakan kebohongan. Label lencananya karena itu berbunyi **"vs 30 hari sebelumnya"**, bukan "dari bulan lalu".
- **Sparkline = delapan titik bulanan** dari query yang sama — gratis, dan ia menutup celah yang `REDESAIN-UI-V2` sendiri catat sebagai utang terbuka ("per-KPI sparklines butuh deret per-kartu di server").
- **Periode nol tetap nol**, tidak dilewati: bulan tanpa aktivitas adalah informasi, bukan lubang.
- **Pembagi nol**: periode sebelumnya = 0 dan sekarang > 0 → tampilkan `Baru` (bukan `∞%`, bukan `+100%`). Keduanya 0 → `delta: null`, lencana tak digambar.
- **Arah warna dari server, bukan dari tanda angka.** Naiknya "Dokumen Ditolak" adalah kabar **buruk**; naiknya "Berlaku" kabar **baik**. Kunci `arah: 'baik'|'buruk'|'netral'` ikut di tiap tile, dihitung `DasborTampilan` — TSX tak boleh menebaknya dari `delta > 0` (CLAUDE.md §4).
- Angkanya sendiri tetap `text-foreground`. Mewarnai angka melanggar lantai mutu §10 dan menurunkan keterbacaan pada elemen yang paling dibaca.

`DasborTampilan::tile()` karena itu menerima dua argumen baru menggantikan `rona`:

```php
private function tile(
    string $label, int $nilai, string $ikon, ?string $url,
    ?string $aksi = null,          // aksi audit penyuapi lencana; null = tanpa lencana
    string $arah = 'netral',       // naiknya angka ini kabar baik / buruk / netral
): array
```

### 5d. ★ Performa PIC (referensi 6) — D9 — ✅ **SELESAI Fase 3**

**Berkas baru:** `components/v2/dasbor/PerformaPic.tsx` — kolom 1/3.
**Komponen:** `ui-maia/{card,avatar,separator,hover-card,tooltip,empty}`. Deret wajah memakai pola yang sudah ada di proyek — `components/v2/dasbor/TumpukanWajah.tsx` (`-ml-2`, `+N`, `HoverCard` daftar lengkap) — jadi **dipakai ulang, tidak digambar ulang**.

```
┌──────────────────────────────┐
│ PIC Aktif                    │
│ 23                           │  ← peninjau/penyetuju yang memegang dokumen
├──────────────────────────────┤
│ Paling Produktif 30 Hari     │
│ (◕)(◔)(◑)(◐)(◒) +7          │  ← TumpukanWajah, HoverCard = nama + jumlah
├──────────────────────────────┤
│ Sorotan                      │
│ Rata-rata beban peninjau  ↗ 2,4 │
│ Dokumen disahkan          ↗ 31  │
│ Masukan ditutup           ↘ 12  │
└──────────────────────────────┘
```

**Datanya, semuanya nyata dan semuanya dari sumber yang sudah ada:**

| Baris | Sumber | Sudah ada? |
|---|---|---|
| Angka besar "PIC Aktif" | `COUNT(DISTINCT reviewer_id, approver_id)` atas dokumen berstatus berjalan | query baru, 1 baris |
| Wajah paling produktif | `audit_logs` grouped by `user_id`, aksi `review_approve` + `md_approve` + `approve`, 30 hari | query yang **sama** dengan §5c, cukup ditambah `user_id` di `groupBy` |
| Rata-rata beban peninjau | `ReviewerAvailability::untuk()` → kunci `beban` | **sudah ada**, `app/Services/ReviewerAvailability.php:81-129` |
| Dokumen disahkan (30 hari) | `audit_logs` aksi `document.approve` | dari query §5c |
| Masukan ditutup (30 hari) | `audit_logs` aksi `feedback.respond` | dari query §5c |

- **Panah tiap sorotan** dihitung sama dengan §5c (30 hari vs 30 hari sebelumnya) dan **arahnya dari server**.
- **Gerbang:** `lingkupPic()` — `document.view_all` (PJO/MD/Admin) → GL+SH+DH tujuh departemen; SH/DH → GL departemennya sendiri; **GL & Non-Staff → `null`**, kartunya tak dirender. ⟲ **K1 (2026-08-31, "GL IKUT mendapatnya") DICABUT pemilik 2026-09-01** (REVISI-UI-V3 §4.1): performa orang lain bukan bacaan penyusun. `dashboardPenuh()` sudah tak jadi gerbang kartu ini.
- **Kebocoran (P4):** baris wajah mengirim **hanya** `['nama' => string, 'foto' => ?string, 'jumlah' => int, 'rincian' => array<string,int>, 'ket' => string]`. Bukan model `User`, bukan koleksi `User`, tanpa `nrp`, tanpa `email`, tanpa `id`. Dikunci tes §9a nomor 8.
- **Rincian aktivitas (butir 10 & 11, 2026-09-02):** `rincian` = `dibuat`/`ditinjau`/`diperiksa`/`disetujui`, dari sapuan `audit_logs` yang **sama** — nol query baru; petanya `DasborTampilan::RINCIAN_PIC` yang membaca `AKSI_ALIR`, bukan daftar kedua (§P9). `jumlah` dan **urutan wajah TIDAK berubah** (K-H): `dibuat` masuk rincian, tak masuk peringkat. Kalimatnya (`ket`, mis. `meninjau 10 · menyetujui 2`) dirakit **server**.
- **Bukan papan peringkat.** Judulnya "Paling Produktif", bukan ranking bernomor, dan tak ada yang ditampilkan di posisi terbawah. Kartu yang memperlihatkan siapa paling sedikit bekerja adalah kartu yang akan dipakai untuk hal yang bukan urusan dasbor.
- `ReviewerAvailability::untukSatu()` yang selama ini **ditulis untuk kartu dasbor lalu tak pernah dipakai** akhirnya terpakai di sini.

### 5e. Sebaran per departemen (referensi 4) — kolom 2/3 — ✅ **SELESAI Fase 3**

**Berkas baru:** `components/v2/dasbor/PitaDepartemen.tsx`.

**Susunan:** judul → satu bar proporsi bersegmen → grid legenda (titik + kode dept, angka besar, `(persen)`).
**Komponen:** `ui-maia/card` + `separator`. Tak ada blok registry untuk bar proporsi bersegmen; alasan ditulis di komentar berkas.

- **Warna: satu token `--chart-*` per KODE departemen** — §P5 dicabut 2026-09-02 (butir 9). Nol hex di TSX, nol palet karangan; urutan baris tetap menurun dari server, warnanya tidak ikut peringkat.
- Grid legenda `grid-cols-2`; di lebar `@2xl` **3 kotak atas / 4 bawah** lewat `grid-cols-12` (K-G). Pemecahannya bersyarat `baris.length === 7` — jumlah departemen bukan 7 jatuh kembali ke grid rata.
- Segmen bar `rounded-full` dengan celah, lebar `%` dari server. `title=` berisi nama panjang departemen.
- Hanya dirender bila `sebaranDepartemen !== null` — gerbangnya `can('document.view_all')` (PJO/MD/Admin saja) dan sejak 2026-09-01 **tidak lagi sama** dengan `PerformaPic` di sebelahnya; halaman menggambar barisnya per kartu, bukan sepasang.

### 5f. Masukan lapangan (referensi 3) — kolom 2/3 — ✅ **SELESAI Fase 3**

**Berkas:** `components/v2/dasbor/KartuMasukan.tsx` — ditulis ulang di tempat.

**Susunan:** judul + satu tombol kanan-atas → tiga kartu masukan sejajar → footer "Lihat N masukan lainnya →".
**Komponen:** `ui-maia/item` (`Item`/`ItemMedia`/`ItemContent`/`ItemActions` — komponen registry yang memang untuk bentuk ini) + `badge` + `empty` + `scroll-area`.

- Tiap kartu: titik berwarna + label status di kiri-atas, ikon lingkaran-centang di kanan-atas, `umur`, isi masukan, chip nomor dokumen di bawah.
- **Warna titik dari `rona` yang dikirim server** (§6a). Terjemahan `rona` → kelas tema sejajar dengan peta `RONA` di `KartuLog.tsx`, supaya "hijau = kabar baik" berarti hal yang sama di dua kartu bersebelahan.
- **Ikon centang = tautan, bukan aksi baru.** `boleh_balas` → `log.masukan`; selain itu → `tautan`. Tidak membangun form balas inline: `DocumentFeedbackController::respond()` sudah punya rumah.
- **Tombol kanan-atas** mengikuti prop `nonStaff`: Non-Staff → "Beri Masukan" (`documents.published`); selain itu → "Lihat semua" (`log.masukan`).
- **Footer memakai `masukanTotal`** (§6b), bukan `masukan.length` — koleksinya dipotong `config('smartpro.dashboard.masukan_widget')`.
- Tiga keadaan tata letak (0 / 1–2 / 3+) dipertahankan; `Empty` maia untuk keadaan kosong, lengkap dengan jalan keluarnya.

### 5g. ★ Garis waktu digambar ulang (referensi 7) — D10 — ✅ **SELESAI Fase 3**

**Berkas:** `components/v2/dasbor/KartuLog.tsx` — ditulis ulang di tempat.

```
│ ● Disetujui — Berlaku      [Berlaku]
│   oleh Rina A. · PPA-ADRO-SOP-SHE-04
│   6 Feb 2026, 14:20 WITA
│
│ ● Dikembalikan untuk revisi  [Ditolak]
│   oleh Budi S. · PPA-ADRO-IK-PLANT-02
│   6 Feb 2026, 09:05 WITA
│
│ ○ Masukan lapangan masuk     [Masukan]
```

**Komponen:** `ui-maia/{card,badge,scroll-area,empty}`. Garis penghubungnya `border-l` di wadah + titik ber-`-ml-[…]`, bukan SVG — pola yang sudah dipakai `KartuLog` sekarang, tinggal diberi lencana dan keterangan.

- **Titik** memakai `rona` yang **sudah dikirim server** (`maju`/`netral`/`baik`/`buruk`) — peta `RONA` yang sudah ada di berkas ini dipertahankan apa adanya.
- **Judul** = `aksi` (kalimatnya dari `DasborTampilan::AKSI`, bukan dari TSX).
- **Lencana** = kategori peristiwa. Perlu satu kunci baru `kategori` di `DasborTampilan::aktivitas()` (§6e) — kata benda pendek yang menempel pada aksi (`Berlaku`, `Ditolak`, `Ditinjau`, `Revisi`, `Masukan`, `Nonaktif`, `Akun`). Dihitung di PHP dari peta `AKSI` yang sudah ada; **jangan** disimpulkan di TSX dari string `aksi`.
- **Keterangan** = `oleh {pelaku}` + `nomor` dokumen bila ada.
- **Tanggal** = `waktu` (sudah ber-WITA dari server).
- Baris tetap `<Link>` ke `tautan` bila ada; `ScrollArea h-[22rem]` dipertahankan supaya jumlah baris tak menentukan tinggi halaman.

> Referensi memperlihatkan lencana `Completed / In progress / Upcoming`. Kosakata itu tidak dipakai di sini — log aktivitas adalah peristiwa yang **sudah** terjadi, jadi ketiganya akan selalu berbunyi "Completed" dan berhenti berarti apa-apa. Kosakata itu justru dipakai di tempat yang benar: tahapan dokumen di baris bentangan `LacakStatus` (§5b).

---

## 6. Bagian 3 — Perubahan server (ADITIF saja, §P2)

Seluruhnya di `app/Http/Controllers/DashboardController.php` kecuali §6e.

### 6a. `masukanBaris()` (`:538-550`) — tiga kunci baru — ✅ SELESAI Fase 1

```php
'statusKunci' => $m->status,                    // kunci mentah, untuk penyaringan
'rona' => match ($m->status) {                  // kosakata SAMA dgn DasborTampilan::AKSI
    'baru' => 'maju', 'dibaca' => 'netral',
    'diadopsi' => 'baik', 'ditolak' => 'buruk',
    default => 'netral',
},
'boleh_balas' => $m->bisaDibalasOleh($user),    // DocumentFeedback.php:93 — JANGAN disalin
```

Method menerima `User $user`; pemanggilnya `:504` dan `:520`. `document` sudah ter-eager-load → nol query tambahan (§P8).

### 6b. `index()` — prop baru & perluasan — ✅ SELESAI Fase 1

- **`masukanTotal`** — jumlah penuh dengan penyaring yang **sama persis** dengan `masukanWidget()`. Pisahkan penyusun query-nya jadi satu closure yang dipakai keduanya, supaya angka dan koleksinya mustahil menyimpang.
- **`sebaranDepartemen`** — §6d.
- **`performaPic`** — §6f.
- **`offSaya`** (`:229-236`) — tambah `'mulai' => $o->mulai->toDateString()`, `'sampai' => $o->sampai->toDateString()`. `rentang` (label) tetap; TSX tak boleh mem-format ulang label itu sendiri.

### 6c. `menungguDiMeja()` (`:374-472`) — dua kunci baru + satu perbaikan keamanan — ✅ SELESAI Fase 1

```php
'sejak'   => ($d->submitted_at ?? $d->updated_at)?->toDateString(),  // sumber persis sama dgn $umur (:433)
'status'  => $d->status,                                             // untuk StatusBadge di §5b
'dokumen' => ['id' => $d->id],                                       // §P4 — dulu model utuh + relasi creator
```

**`limit(4)` TIDAK diubah** (§P1). Komentar `:405-407` tetap berlaku.

### 6d. `sebaranDepartemen()` — method privat baru — ✅ SELESAI Fase 1

```php
private function sebaranDepartemen(User $user, Closure $visible): ?array
{
    if (! $user->dashboardPenuh()) {
        return null;                     // gerbang sama dgn distribusiWidget (:579)
    }
    // $visible() -> berlaku() (Document.php:454) -> leftJoin departments -> GROUP BY code
    // baris: ['kode','nama','jumlah','persen'] URUT MENURUN; + 'total'
}
```

- Memakai closure `$visible` walau `dashboardPenuh()` sudah membuka semuanya — pertahanan berlapis, konsisten dengan `$sebaran`/`$matrix`.
- Memakai scope `Document::berlaku()` yang sudah ada; jangan definisikan ulang arti "berlaku" (alasannya di `Document.php:450-453`).
- **`leftJoin` dari `departments`**, bukan `join` dari `documents`: departemen ber-nol dokumen tetap muncul, sehingga jumlah kotak legenda tidak berubah dari hari ke hari.
- **Tanpa `Department::RUPA`, tanpa migration, tanpa kolom `warna`** (§P5).
- Bentuk barisnya meniru `$sebaran` (`:158-167`) supaya bisa dikunci tes dengan pola yang sama seperti `SpecDashboardV3Test:108-112`.

### 6e. ★ `alirAudit()` — method privat baru, penyuapi §5c dan §5d — ✅ SELESAI Fase 1

**Satu query** untuk seluruh lencana, sparkline, wajah PIC, dan sorotan. Bentuknya di §5c.

```php
/**
 * @return array{
 *   perAksi: array<string, array{deret: int[], kini: int, lalu: int}>,
 *   perOrang: array<int, array{nama: string, foto: ?string, jumlah: int}>,
 * }
 */
private function alirAudit(User $user, Closure $visible): array
```

- Hasilnya dipakai `DasborTampilan::tiles()` (lencana + sparkline) **dan** `performaPic()` (wajah + sorotan) — satu query, dua pemakai.
- `perOrang` hanya diisi bila pemanggilnya berhak (§6f); bagi **Non-Staff** ia larik kosong (K1), bukan larik yang disaring belakangan.
- Aksi yang disapu dibaca dari **satu konstanta** `DasborTampilan::AKSI_ALIR` supaya peta tile dan peta query mustahil berselisih.
- Rentangnya 8 bulan, sama dengan `$barisTren` (`:107`), supaya sparkline KPI dan grafik Overview berbicara tentang jendela waktu yang sama.

### 6f. ★ `performaPic()` — method privat baru — ✅ SELESAI Fase 1

```php
private function performaPic(User $user, Closure $visible, array $alir): ?array
{
    // null bagi Non-Staff saja (K1 — GL ikut mendapatnya).
    // ['picAktif' => int,
    //  'wajah'    => [['nama','foto','jumlah'], ...max 8],     ← P4: BUKAN model User
    //  'sorotan'  => [['label','nilai','satuan','arah','delta'], ...3]]
}
```

`picAktif` satu query (`COUNT(DISTINCT …)` atas dokumen berstatus berjalan). Sorotan "rata-rata beban peninjau" memanggil `ReviewerAvailability::untuk()` yang sudah ada — jangan menghitung ulang beban di sini.

### 6g. `DasborTampilan` (`app/Services/DasborTampilan.php`) — ✅ SELESAI Fase 1

- `tile()` — ganti parameter `$rona` dengan `$aksi` + `$arah` (§5c).
- `tiles()` — terima `array $alir`, tempelkan `deret` / `delta` / `arah` per tile.
- `aktivitas()` — tambah kunci `kategori` (§5g), dihitung dari peta `AKSI` yang sudah ada.
- konstanta baru `AKSI_ALIR` — peta tile → aksi audit (§5c), satu-satunya tempat pemetaan itu hidup.

### 6h. `config/smartpro.php` — ✅ SELESAI Fase 1 (memang nol perubahan)

**Tidak ada perubahan.** `meja_widget` dibatalkan bersama P1.

---

## 7. Bagian 4 — Tipe (`resources/js/types/dasbor.d.ts`) — ✅ SELESAI Fase 1

- `Tile` — **buang** `rona`; tambah `deret: number[] | null`, `delta: number | null`, `arah: 'baik' | 'buruk' | 'netral'`, `deltaLabel: string | null`.
- `BarisMeja` — tambah `sejak: string | null`, `status: string`. (`dokumen: { id: number }` sudah benar.)
- `BarisMasukan` — tambah `statusKunci: string`, `rona: string`, `boleh_balas: boolean`.
- `BarisAktivitas` — tambah `kategori: string`.
- `Off` — tambah `mulai: string`, `sampai: string`.
- Baru: `SebaranDepartemen { total: number; baris: { kode: string; nama: string; jumlah: number; persen: number }[] }`.
- Baru: `PerformaPic { picAktif: number; wajah: { nama: string; foto: string | null; jumlah: number }[]; sorotan: { label: string; nilai: string; arah: string; delta: number | null }[] }`.
- `DashboardProps` — tambah `masukanTotal: number`, `sebaranDepartemen: SebaranDepartemen | null`, `performaPic: PerformaPic | null`.
- `matrix: BarisMatriks[]` **sudah ada** (`:193`) — §5b tinggal memakainya.

---

## 8. ★ Apa lagi yang bisa di-improve (D11)

Inventaris data yang **sudah dihitung server dan tak pernah digambar**. Diurutkan menurut rasio manfaat terhadap kerja.

### Masuk lingkup sekarang — nol query baru

| # | Yang menganggur | Dipakai di |
|---|---|---|
| 1 | `matrix` — 7 status × 6 jenis + total, tak pernah dirender | §5b baris angka + footer |
| 2 | `daftarTahap`, `ke`, `persen` di `menungguDiMeja` | §5b baris bentangan |
| 3 | `ReviewerAvailability::untukSatu()` — ditulis untuk kartu dasbor, lalu tak dipakai | §5d sorotan beban |
| 4 | `audit_logs` — 8 bulan riwayat transisi, nol pembaca selain feed 15 baris | §5c lencana + sparkline, §5d wajah |

### Layak dikerjakan berikutnya — kecil, tapi di luar lingkup hari ini

| # | Peluang | Kerja |
|---|---|---|
| 5 | `stats.dept_documents` dihitung (`:58-60`) tapi **tak dipakai tile mana pun** — SH/DH tak pernah melihat angka departemennya sendiri | ganti satu tile SH/DH |
| 6 | `AntreanTugas` menghitung `revisi` / `nonaktif` / `masukan` / `akun` untuk sidebar; dasbor hanya memakai 3 dari 7 | satu baris pil di `KartuSambutan` |
| 7 | `jenisList` dikirim, tak dirender | penyaring jenis di `KartuSebaran` |
| 8 | `queues` mentah dikirim, tak dirender | — sudah terwakili tile |
| 9 | `KartuKetersediaan` bisa memperlihatkan **pita beban 14 hari saya sendiri** (`ReviewerAvailability::untukSatu()` → kunci `pita`) di bawah kalender | satu komponen kecil |
| 10 | Belum ada **skeleton** di dasbor, padahal CLAUDE.md §13 mensyaratkannya. Kartu berat (`ArusDokumen`, `PitaDepartemen`) mengedip saat navigasi | `ui-maia/skeleton`, sudah ada |

### Sengaja TIDAK dikerjakan

| Peluang | Alasan |
|---|---|
| Papan peringkat PIC bernomor | §5d — kartu yang memperlihatkan siapa paling sedikit bekerja akan dipakai untuk hal yang bukan urusan dasbor. |
| Delta di baris angka `LacakStatus` | §5b — `matrix` potret sesaat, bukan aliran; delta di atasnya mengukur hal yang berbeda dari yang dibaca orang. |
| Lencana MD "Perlu Diperiksa" | §5c — tak ada aksi audit yang mewakilinya secara jujur. |
| Rata-rata waktu tinjau (submit → review_approve per dokumen) | Butuh query berpasangan atas `audit_logs` per dokumen. Berguna, tapi bukan satu query lagi — tunda sampai ada yang memintanya. |
| ~~Warna tetap per departemen~~ | **DIKERJAKAN 2026-09-02** (butir 9) — lewat validator §6, memakai token `--chart-1..7`. |

---

## 9. Verifikasi

### 9a. Tes baru — `tests/Feature/DasborV2Test.php` — ✅ SELESAI Fase 1 (13/13 hijau)

Jumlah tes tak boleh berkurang (CLAUDE.md §14c).

1. `sebaranDepartemen` = `null` bagi **Non-Staff** (K1); satu baris per departemen bagi GL, SH, PJO; jumlahnya cocok dengan hitungan `Document::berlaku()` langsung; `persen` menjumlah ~100.
2. `menungguDiMeja` tiap baris punya `sejak` + `status`, dan **`dokumen` hanya berisi kunci `id`** — pengunci §P4.
3. **Kebocoran pada HTML MENTAH** (pola `DistribusiInertiaTest`/`InformasiLogInertiaTest`): muat `/dashboard` sebagai PJO **dan** SH, tegaskan badan responsnya tak memuat `password`, `remember_token`, `arsip_path`, `file_path`, maupun `email`/`nrp` siapa pun.
4. `masukanWidget` tiap baris punya `rona` + `boleh_balas`; `boleh_balas` **false** bagi SH dan **true** bagi GL sedepartemen — menguji bahwa aturannya datang dari `bisaDibalasOleh()`.
5. `masukanTotal` >= `count(masukanWidget)` dan cocok dengan penyaring yang sama.
6. `tiles` **tidak lagi** memuat kunci `rona` — pengunci revert §4c.
7. `menungguDiMeja` tetap **maksimum 4** — pengunci §P1.
8. **`performaPic`** = `null` bagi **Non-Staff** (K1); bagi GL & PJO tiap butir `wajah` **hanya** punya kunci `nama`, `foto`, `jumlah` — tak ada `id`, `nrp`, `email`. Pengunci §P4 untuk kartu baru.
9. **Lencana KPI datanya nyata:** buat dua dokumen yang disahkan hari ini, nol pada 30 hari sebelumnya → tile "Berlaku" ber-`delta` bukan-null dan ber-`deret` 8 angka; tile "Perlu Diperiksa" ber-`delta: null`.
10. **Arah dari server:** tile "Dokumen Ditolak" ber-`arah: 'buruk'`, tile "Berlaku" ber-`arah: 'baik'` — pengunci bahwa TSX tak menebaknya dari tanda angka.
11. **Pembagi nol:** periode lalu 0 & sekarang > 0 → `delta` tidak `INF`/`NAN`; keduanya 0 → `delta: null`.
12. `activities` tiap baris punya `kategori` bukan-kosong.
13. **Setiap aksi di `DasborTampilan::AKSI_ALIR` benar-benar aksi audit yang terdaftar** — pengunci §P9. ⟲ **K2:** gerbangnya berdiri di KATALOG KODE (`AuditLog::AKSI_META` ∪ `DasborTampilan::AKSI`), bukan di isi tabel `audit_logs`: dua aksi yang sah (`document.approval_reject`, `feedback.respond`) nol baris di DB, dan menuntut barisnya ada berarti tes ini mengukur kelengkapan seeder alih-alih kebenaran peta.

Pastikan tetap hijau: `DashboardWidgetTest` (khususnya `:124`), `DashboardRenderTest`, `SpecDashboardV3Test`, `PratinjauUiTest`.

### 9b. Gerbang wajib — jalankan dari **PowerShell**

```powershell
npm run build
node_modules/.bin/tsc --noEmit
npm run uji-render
php artisan smartpro:uji-render                  # bendera V2 MATI
php artisan smartpro:uji-render --pratinjau      # bendera V2 NYALA  <- §P6
php artisan test
md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23/23 wajib lolos penuh
php artisan smartpro:cetak-baseline              # 8 dokumen identik
git diff components.json                         # WAJIB KOSONG  <- §P3
```

Git Bash mengubah `/dashboard` jadi `C:/Program Files/Git/dashboard` — karena itu PowerShell (`REDESAIN-UI-V2 §10b`).

**Sebelum menulis §5c**, jalankan sekali:

```sql
SELECT action, COUNT(*) FROM audit_logs GROUP BY action ORDER BY 2 DESC;
```

Peta `AKSI_ALIR` hanya boleh memuat aksi yang muncul di hasil itu (§P9).

**Ongkos query** — hitung ulang sesudah widget masuk:

```
DB::enableQueryLog(); → GET /dashboard sebagai PJO → count(DB::getQueryLog())
```

Tiga query baru (`alirAudit`, `sebaranDepartemen`, `picAktif`) plus panggilan `ReviewerAvailability::untuk()`. Kalau jumlahnya melonjak lebih dari itu, ada N+1 yang menyelinap.

### 9c. Pemeriksaan mata (gerbang otomatis tak pernah memeriksa tata letak)

Nyalakan pratinjau di topbar, buka `/dashboard` sebagai **GL · SH · PJO · MD · Non-Staff · Admin**, di tema **terang dan gelap**:

- **Non-Staff** (K1 — bukan GL): baris `PitaDepartemen | PerformaPic` **lenyap utuh** — bukan menyisakan satu kolom yatim.
- Non-Staff: `LacakStatus` absen (`adaMeja` salah); `KartuMasukan` berisi kiriman sendiri; tombolnya "Beri Masukan".
- PJO: tujuh segmen di bar departemen; ramp menurun mulus; wajah PIC punya cadangan inisial saat `foto` null.
- KPI: kartu yang `delta`-nya null **tidak** menyisakan ruang kosong sebesar lencana — tinggi keempat kartu tetap sama.
- KPI: sparkline tak menyentuh angka di atasnya, dan tetap terbaca di mode gelap.
- Kalender: klik tanggal → dialog terisi; ganti bulan **tidak** mengembalikan tab ke "Semua"; titik hanya di tanggal yang punya agenda.
- Garis waktu: garis penghubung tak putus di baris pertama/terakhir; lencana tak melipat baris pada nomor dokumen terpanjang.
- Sidebar diciutkan ke rel ikon → logo mengecil, tidak melebar dari rel 3rem.
- Bandingkan berdampingan dengan V1 (matikan pratinjau): susunan barisnya sama, hanya gaya dan enam widget yang berbeda.

### 9d. Dokumentasi

- ✅ `docs/REDESAIN-UI-V2.md` — banner "§8 DAN §8b DIGANTIKAN" di kepala §8 dengan tabel tujuh butir yang dibatalkan satu per satu; utang "per-KPI sparkline" ditandai **LUNAS** beserta cara menutupnya; §11 dapat blok berkas **T3**; §13 dapat baris progres T3 + rincian per fase. Ikut dicatat: kewajiban "saat approve, CLAUDE.md §13 wajib direvisi" **GUGUR** — ia melekat pada pita merek, dan pita merek dibatalkan, jadi nol permukaan berwarna penuh tersisa untuk dicatat sebagai penyimpangan.
- ✅ `CLAUDE.md` §14c — angka patokan **715** sudah masuk sejak Fase 1; ditambahkan bahwa Fase 2–3 **bertahan di 715**, beserta alasan kenapa fase TAMPILAN tak melahirkan tes baru: tes Inertia membaca PROPS, dan komponen yang salah gambar tetap mengirim props yang sama — yang menjaganya gerbang render, bukan asersi props yang tak pernah bisa merah.
- ✅ `CLAUDE.md` §17 — rujukan `REDESAIN-UI-V2.md` + `DASBOR-V2-REVISI.md` ditambahkan; selama ini keduanya tak pernah disebut di daftar referensi.
- ✅ `docs/PROGRESS-SHADCN.md` — blok "⚠️ PEKERJAAN YANG BERJALAN SESUDAH FASE 13" di kepala. Ini gerbang yang paling mudah bocor: CLAUDE.md menyuruh membaca berkas itu DULU tiap sesi baru, tapi ia berhenti di Fase 13 dan nol menyebut V2 — sesi baru akan mengira migrasinya sudah tamat. Blok itu juga mengulang dua jebakan yang paling mudah dilanggar tanpa sadar (`components.json` nova/lucide, dan tiga berkas K3 yang jangan dihapus sebagai "pembersihan").
- ✅ **Utang komentar dari Fase 2 — LUNAS di Fase 3.** Docblock prop `kepala` di `layouts/V2/AppLayout.tsx` berbunyi *"Ada satu pemakai, dan sengaja begitu: pita sambutan dasbor"* padahal sejak §3 ia nol pemakai. Keputusannya: prop `kepala` **DICOPOT**, bukan komentarnya yang ditambal — prop tanpa pemakai adalah cabang yang tak pernah diuji dan tak pernah dilihat, dan menyimpannya "untuk nanti" berarti menyimpan kode yang tak seorang pun tahu masih benar. Docblock `AppLayout` + `SiteHeader` ditulis ulang mengikuti keadaan sekarang (judul di topbar).
- ✅ **Utang komentar K1 — LUNAS di Fase 3.** Enam tempat masih berbunyi "GL & Non-Staff" padahal K1 memutuskan gerbangnya `dashboardPenuh()` harfiah, yang **meloloskan GL**: `types/dasbor.d.ts` (2), `DashboardController::{performaPic,sebaranDepartemen,wajahProduktif}()`, dan komentar props `index()`.

---

## 10. Berkas yang disentuh

**Server** — `app/Http/Controllers/DashboardController.php` · `app/Services/DasborTampilan.php`

**Halaman** — `resources/js/pages/V2/Dashboard.tsx` (tulis ulang mengikuti `pages/Dashboard.tsx`)

**Komponen baru** — `components/v2/dasbor/{KartuSambutan,GrafikOverview,MeterTertinjau}.tsx` ✅ Fase 2 · `{LacakStatus,PitaDepartemen,PerformaPic}.tsx` ✅ Fase 3 · `{LencanaDelta,Sparkline}.tsx` ✅ Fase 3 (dua berkas kecil di luar rencana — alasannya di rincian Fase 3)

**Komponen ditulis ulang** — `components/v2/dasbor/{KartuStatistik,KartuKetersediaan,KartuMasukan,KartuLog}.tsx`

**Komponen dipakai ulang tanpa disunting** — `components/v2/{DataTable,StatusBadge,Ikon}.tsx`

**Komponen dipakai ulang dengan prop OPSIONAL tambahan** — `components/v2/dasbor/TumpukanWajah.tsx` (`satuan` + `ket` per orang; keduanya berdefault ke perilaku lama, nol pemanggil lama berubah)

**Komponen dihapus** — `components/v2/dasbor/{PitaSambutan,ArusDokumen,PerluTindakan}.tsx` — ⛔ **BELUM, ditahan K3.** **Ketiganya nol pengimpor sejak Fase 3** (`PerluTindakan` menyusul begitu `LacakStatus` berdiri), jadi tak ada lagi prasyarat yang ditunggu — tinggal keputusan pemilik.

**Lain** — `components/v2/AppSidebar.tsx` (logo) ✅ Fase 2 · `resources/js/types/dasbor.d.ts` ✅ Fase 1 · `tests/Feature/DasborV2Test.php` (baru) ✅ Fase 1

**Lain (Fase 3)** — `components/v2/SiteHeader.tsx` + `layouts/V2/AppLayout.tsx` (judul pindah ke topbar, prop `kepala` dicopot — M2/M3)

**TIDAK disentuh** — 41 halaman V1 · `components/dasbor/*` (V1) · `pages/V2/{Documents,Review,Auth}/*` · `resources/css/app.css` · `components/ui/*` · `components/ui-maia/*` · `components.json` · `config/smartpro.php` · `app/Models/{Department,Document,User,AuditLog}.php` · seluruh daftar mesin cetak CLAUDE.md §14b

---

## 11. Perbandingan dengan rencana pertama

| | Rencana pertama | Sesudah review |
|---|---|---|
| Batas `menungguDiMeja` | 4 → 12 | **tetap 4** (memecah tes + mengubah V1) |
| `config.meja_widget` | ditambah | **dibatalkan** |
| Warna departemen | 7 hex `Department::RUPA` | **ramp `--primary`**, nol hex, nol konstanta |
| `app/Models/Department.php` | disunting | **tak disentuh** |
| Tabel Lacak Status | tabel baru + paginasi + saring | **`v2/DataTable` yang sudah ada** |
| Lencana kenaikan KPI | dibuang (tak ada data) | **dipasang, datanya dari `audit_logs`** (D8) |
| Sparkline per KPI | utang terbuka `REDESAIN-UI-V2` | **ditutup** — gratis dari query yang sama |
| Performa PIC | tidak ada | **kartu baru** (D9), sumbernya sudah ada semua |
| Garis waktu | `KartuLog` apa adanya | **digambar ulang** (D10) + kunci `kategori` |
| Tahapan dokumen | data menganggur | **dihidupkan** di baris bentangan `LacakStatus` |
| Gerbang render V2 | `--uri=/dashboard` | **`--pratinjau`** |
| `components.json` | tak disebut | **jebakan didokumentasikan + digerbangi `git diff`** |
| Kebocoran `creator` | disebut sambil lalu | **temuan bernomor + tes penguncinya** |
| Verifikasi nama aksi audit | tidak ada | **P9 — wajib query DB, + tes pengunci** |
| Tes baru | 6 | **13** |

Bersihnya: **satu berkas server lebih sedikit disentuh, satu konstanta hex dan satu kunci config tidak lahir, nol tes dipecahkan, satu kebocoran ditutup, empat sumber data yang menganggur dihidupkan, dan satu utang terbuka `REDESAIN-UI-V2` dilunasi.**
