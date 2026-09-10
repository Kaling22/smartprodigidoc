# REVISI-UI-V3 — dasbor per peran, tabel, dan login

> Induk: `docs/REDESAIN-UI-V2.md` (T1–T2b) · `docs/DASBOR-V2-REVISI.md` (T3 Fase 1–4)
> · status berjalan: `docs/PROGRESS-SHADCN.md`.
> Aturan yang tetap menang: `CLAUDE.md`. Pekerjaan ini **tidak mencabut** satu pun
> larangan di sana, dan tidak menyentuh mesin cetak (§14b).
>
> Status: **Fase 1 SELESAI** (2026-08-31, delapan gerbang hijau) ·
> **Fase 2a SELESAI** (2026-09-01, sembilan gerbang hijau — §4.8) ·
> **Fase 2b SELESAI** (2026-09-01, sembilan gerbang hijau — §5.4).
> Seluruh 12 permintaan tergarap; yang tersisa cuma pemeriksaan mata (§6).

---

## 1. Kenapa

Sesudah T1–T3 selesai, pemeriksaan mata pemilik atas jalur pratinjau V2
memunculkan **12 permintaan**. Sesudah ditelusuri ke kode, ketiganya terbelah
jadi dua jenis yang berbeda sifat:

- **Enam bug dengan akar tunggal** — lencana sidebar tergambar di baris yang
  salah, toolbar Quill kolaps, kartu Masukan membungkus ke samping, tombol aksi
  tabel bertumpuk, penyaring butuh klik, foto profil tak pernah dikirim server.
  Semuanya menyentuh komponen BERSAMA, jadi satu perbaikan menyembuhkan puluhan
  halaman sekaligus.
- **Enam keputusan produk** — yang mengubah **siapa melihat apa** di dasbor,
  bentuk satu tabel widget, keterbacaan kalender, satu kendali yang dicabut, dan
  halaman login.

Yang dituju: nol fitur hilang, nol logika izin/status pindah ke TSX (§4), dan
perubahan server tetap **aditif** kecuali gerbang peran yang memang diminta
berubah.

### Keputusan pemilik (2026-08-31 / 2026-09-01)

| # | Pertanyaan | Keputusan |
|---|---|---|
| E1 | Perbaikan tabel & live filtering: V1 saja, V2 saja, atau keduanya? | **Keduanya.** Hanya 6 halaman punya kembaran V2; 30+ daftar lain masih V1 dan itulah yang dilihat sebagian besar peran |
| E2 | "Hapus sebaran per dokumen" | Yang dimaksud **sebaran per DEPARTEMEN**. Donat `KartuSebaran` (per JENIS) **dipertahankan di semua peran** |
| E3 | Notif angka submenu | **Tetap berbasis AKSI.** Angkanya bertahan sampai pekerjaannya diselesaikan — bukan hilang saat menunya dibuka. Angka hidup di SUB-menu; menu tanpa sub-menu memakainya langsung |
| E4 | Admin IT ikut peran mana? | **Sama dengan PJO & MD** — lensa tujuh departemen lewat `can('document.view_all')` |
| E5 | Tata letak SH sesudah Sebaran per Dept dicabut | **1/3 kanan + ganjal `<div hidden>` 2/3**, mengikuti konvensi ganjal yang sudah ada |
| E6 | Sisa ⌘K | `components/v2/PencarianMenu.tsx` **ditahan, jangan dihapus** (perlakuan K3); `cmdk` tetap terpasang |
| E7 | Pembagian Fase 2 | **Dipecah**: 2a dasbor+kerangka, 2b login |
| E8 | Performa PIC di PJO & MD | Menampilkan performa **SH dan GL** |

---

## 2. Daftar permintaan — 12 butir, nol yang dibuang

| # | Permintaan | Fase |
|---|---|---|
| R1 | Notif angka sub-menu salah alamat | 1 ✅ |
| R2 | Quill: tombol bold/italic/underline/kutipan/daftar/gambar tak tampil | 1 ✅ |
| R3 | Widget Masukan Lapangan rusak tata letaknya | 1 ✅ |
| R4 | Tombol aksi tabel tumpang tindih | 1 ✅ |
| R5 | Live filtering tanpa tombol Filter | 1 ✅ |
| R6 | Foto profil di perjalanan dokumen & distribusi | 1 ✅ |
| R7 | Performa PIC digerbangi per peran | 2a ✅ |
| R8 | Sebaran per Departemen hanya PJO/MD/Admin | 2a ✅ |
| R9 | Lacak Status memakai tabel desain V1 | 2a ✅ |
| R10 | Kalender: angka tanggal biasa lebih terbaca | 2a ✅ |
| R11 | Palet ⌘K dihapus | 2a ✅ |
| R12 | Halaman login desain baru | 2b ✅ |

**Urutannya beralasan.** Fase 1 lebih dulu karena keenamnya akar tunggal dan nol
keputusan produk — dampaknya kena ke seluruh aplikasi tanpa perlu menunggu
siapa pun memutuskan apa pun. Fase 2a berikutnya karena ia mengubah **aturan**
(siapa lihat apa), dan aturan yang salah lebih mahal daripada gambar yang salah.
Fase 2b terakhir karena login berdiri sendiri: satu layout dengan satu pemakai,
dan ia layak dinilai tanpa tertutup ramainya perubahan dasbor.

---

## 3. Fase 1 — SELESAI (2026-08-31)

### Yang diperbaiki, beserta AKAR masalahnya

| # | Akar masalah | Perbaikan |
|---|---|---|
| **R1** | `SidebarMenuBadge` registry menaruh `top`-nya lewat `peer-data-[size=*]/menu-button:top-*`, tapi kelas `peer/menu-button` **hanya ada di `SidebarMenuButton` INDUK** — `SidebarMenuSubButton` tak punya. Akibatnya `top` tak pernah teratur, lencana jatuh ke posisi statis **di bawah barisnya sendiri**, dan angka "Masukan Lapangan" terbaca sebagai angka "Log Pesan" | `className="top-1"` di kedua `AppSidebar` (sub-button `h-7`, lencana `h-5` → (28−20)/2 = 4px). Berkas `ui/` & `ui-maia/` **tak disentuh** (§4) |
| **R2** | Quill **menimpa** `innerHTML` tiap tombol ber-kelas `ql-*` dengan SVG-nya sendiri (`themes/base.js` `buildButtons`), dan `quill.snow.css` menatanya `float:left; height:100%`. Anak yang di-float tak menyumbang tinggi ke induknya, jadi `.pp-rt … button{height:auto}` membuat tombolnya **kolaps ke nol** dan seluruh toolbar tampak hilang | Tinggi & lebar tombol dipatok `1.75rem`, svg dilepas dari float, di `resources/css/app.css`. Ikon React yang digambar di TSX memang cuma bertahan sampai Quill selesai dimuat — itu perilaku Quill, bukan cacat kita |
| **R3** | `Item` maia berbunyi `flex flex-wrap items-center` — dirancang **mendatar**. Dibalik jadi `flex-col`, `flex-wrap` mulai membungkus ke **KOLOM** baru, dan `ItemHeader`/`ItemFooter` yang ber-`basis-full` (= tinggi 100% begitu sumbu utamanya vertikal) memaksa tiap bagian pindah kolomnya sendiri: status, umur, kutipan, pengirim, dan nomor berjajar ke samping | `flex-nowrap` menemani `flex-col`; kepala & kaki jadi `div` biasa, karena `ItemHeader`/`ItemFooter` menggendong `basis-full` yang cuma benar di `Item` mendatar |
| **R4** | `flex-wrap` di 14 sel aksi + kolomnya tanpa `whitespace-nowrap` | 21 kolom aksi → `w-px text-right whitespace-nowrap`; 14 sel → `flex-wrap` dibuang. V1 **dan** V2. `DataTable` sudah membungkus dirinya `overflow-x-auto`, jadi kolom yang menyusut ke isinya tak pernah memaksa halaman menggulir mendatar |
| **R5** | Penyaring menunggu klik tombol Filter | Debounce 300 ms di **kedua** `PenyaringDokumen`, dengan `preserveState` (kursor tak keluar dari kotak cari), `preserveScroll` (daftar tak melompat), dan `replace` (satu penyaringan = satu entri riwayat, bukan satu per huruf). Tombol Filter dicabut; Reset jadi bersyarat. Penyaringan **TETAP di server** dan **TETAP lewat query string** — hasilnya masih bisa di-bookmark |
| **R6** | Server tak pernah mengirim foto: `timeline` hanya `oleh`, `ratakanRincian()` hanya `nama`/`jabatan` | +kunci `foto` (hasil `Storage::url()` **saja**, bukan model `User`, tanpa `photo_path`). `<Avatar>` di `Documents/Show` & `RincianPembaca`. Ikut cuma-cuma ke rincian Informasi — bentuk kembalian kedua service memang sengaja identik. Nol N+1: `with('user')` sudah ada |

### Berkas

**Disunting:** `components/{AppSidebar,dokumen/PenyaringDokumen,dokumen/RincianPembaca}.tsx`
· `components/v2/{AppSidebar,PenyaringDokumen}.tsx`
· `components/v2/dasbor/KartuMasukan.tsx` · `resources/css/app.css`
· `app/Http/Controllers/DocumentController.php` · `resources/js/types/dokumen.d.ts`
· `pages/Documents/Show.tsx` · 18 halaman daftar (kolom & sel aksi)
· `tests/Feature/DistribusiInertiaTest.php`

Penjaga kebocoran **diperkuat, bukan dilonggarkan**: himpunan kunci
`ratakanRincian()` tetap dikunci persis (kini ber-`foto`), dan **`photo_path`
masuk daftar rahasia** yang tak boleh muncul di HTML mentah — sejajar dengan
`arsip_path` dan `file_path` (§4).

### Gerbang — delapan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` | bersih |
| `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` (+2 `--uri`) | 30 halaman, nol galat |
| `php artisan test` | **715** (714 lulus + 1 dilewati) — patokan §14c terjaga persis |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `git diff components.json` | KOSONG |

### ✅ Satu temuan yang dulu terbuka — SELESAI 2026-09-01, BUKAN regresi

`php artisan smartpro:cetak-baseline` keluar kode 1:

```
BEDA   IK_PPA-ADRO-IK-ICTMD-01_id1224.pdf
BEDA   SP_PPA-ADRO-SP-ICTMD-01_id1225.pdf
HILANG SOP_PPA-ADRO-SOP-ICTMD-03_id1239.pdf   (sudah tercatat sejak DASBOR-V2 Fase 1)
HILANG SOP_PPA-ADRO-SOP-ICTMD-04_id1240.pdf   (baru)
```

Tiga bukti bahwa ini **data, bukan kode**:

1. `md5sum` mesin cetak **23/23 OK** — nol byte berubah di 23 berkas itu.
2. `BaselineCetak` hanya memakai `Document` + `Services\Print\PdfRenderer`.
   `DocumentController` — satu-satunya PHP yang Fase 1 sentuh — **tak ada di
   jalur itu sama sekali**.
3. id1224 pindah ke `pending_approval`, id1225 isinya disunting, id1240 lahir —
   ketiganya **2026-08-31 pukul 16:42–16:44** lewat peramban, sesudah gerbang
   hijau terakhir. Dokumen yang datanya tak bergerak (id1227, 28 Agustus) tetap
   `sama`.

**Keputusan pemilik 2026-09-01: TANGKAP ULANG keempat berkas.** Dikerjakan di
awal Fase 2a, sesudah dipastikan daftarnya masih persis empat itu (nol berkas
baru menyusul):

```
php artisan smartpro:cetak-baseline --tulis --id=1224 --id=1225 --id=1239 --id=1240
```

| Berkas | Sidik lama → baru |
|---|---|
| `IK_…-01_id1224.pdf` | `dd0dd7bf…` → `0a002f52…` (99.485 → 99.508 B) |
| `SP_…-01_id1225.pdf` | `89c01750…` → `62307a50…` (97.031 → 95.922 B) |
| `SOP_…-03_id1239.pdf` | — → `59c63b9d…` (93.230 B) |
| `SOP_…-04_id1240.pdf` | — → `ce8b8133…` (592.426 B) |

Kedua berkas yang DITIMPA disalin dulu jadi `*.pra-v3fase2a` di folder yang sama
— acuan kebenaran boleh berubah, tapi tidak boleh hilang tanpa jejak. Sesudahnya
gerbangnya berbunyi "Seluruh keluaran cetak identik dengan baseline".

Ini **mengubah acuan**, bukan memperbaiki kode: `md5sum` mesin cetak tetap 23/23
sebelum maupun sesudahnya.

---

## 4. Fase 2a — Dasbor per peran + kerangka

### 4.1 · R7 — Performa PIC digerbangi per peran

| Peran | Kartu | Wajah yang tampil |
|---|---|---|
| GL (`group_leader`) | **tak dirender** | — |
| Non-Staff (`staff`) | **tak dirender** | — |
| SH / DH (`section_head`, `departemen_head`) | ya | **GL di departemennya sendiri** |
| PJO · MD · Admin IT | ya | **GL + SH + DH, tujuh departemen** |

DH terhitung setara SH — CLAUDE.md §6: DH = wewenang SH.

#### Gerbangnya SATU helper, dan URUTAN pemeriksaannya menentukan benar-salah

```php
/** @return array{jabatan: string[], deptId: ?int}|null  null = kartunya tak dirender */
private function lingkupPic(User $user): ?array
```

Urutannya **wajib** begini, dan bukan selera:

1. **`$user->can('document.view_all')` DULU** → tujuh departemen, jabatan
   `[group_leader, section_head, departemen_head]`.
2. baru `jabatan ∈ [section_head, departemen_head]` → `[group_leader]`,
   `deptId = $user->department_id`.
3. selain itu → `null`.

Alasannya keras: **Admin IT dan MD ber-`jabatan` NULL** (`AdminUserSeeder`), dan
MD bahkan ber-`department_id` ICTMD. Kalau jabatan diperiksa lebih dulu, MD
jatuh ke cabang terakhir dan **kehilangan kartunya**, sementara Admin ikut
tersasar. Memeriksa izin lebih dulu membuat ketiganya benar tanpa satu pun
pengecualian bernama.

`can('document.view_all')` dipilih karena itulah pola yang **sudah** dipakai di
seluruh `DashboardController` untuk arti "lintas tujuh departemen"
(`RolePermissionSeeder`: pemegangnya persis Admin, PJO, MD). Nol aturan baru
yang harus diingat orang berikutnya.

#### Dua temuan review yang mengubah cara menulisnya

**T1 — `take(8)` WAJIB pindah ke belakang saringan.**
`wajahProduktif()` sekarang berbunyi `sortDesc()->take(8)` lalu mengambil
`User`-nya. Kalau saringan jabatan ditempelkan begitu saja pada pengambilan
`User`, delapan pelaku teratas bisa saja berisi tujuh PJO/MD, dan kartunya
menampilkan **satu** wajah padahal ada belasan GL yang memenuhi syarat. Jadi
urutannya harus: `sortDesc()` → ambil pengguna yang **memenuhi saringan** (tanpa
`take`) → buang pelaku yang tak lolos dari hitungan → **baru** `take(8)`.

Daftar `IN` yang lebih panjang tak jadi soal: pelaku berbeda dalam 30 hari
berjumlah puluhan, bukan ribuan, dan query-nya tetap **satu**.

**T2 — "Pengguna dihapus" tak lagi bisa ikut.**
Baris audit dari akun yang sudah dihapus sekarang tetap tampil bernama
"Pengguna dihapus". Dengan saringan jabatan, akun itu **tak bisa
diklasifikasikan** — kita tak tahu ia GL atau bukan — jadi ia **dibuang**.
Menebak jabatannya berarti mengarang; menampilkannya tanpa syarat berarti
saringannya bohong. Ini perubahan perilaku yang disengaja dan wajib tertulis di
komentar berkasnya.

#### Yang TIDAK ikut disaring

`picAktif` dan ketiga `sorotan` tetap apa adanya: keduanya angka **lingkup**
(`$visible()` + `ReviewerAvailability::untuk()`), bukan daftar orang. Menyaring
jabatan di sana berarti mengubah arti angkanya, bukan cakupannya.

#### Kebocoran (§P4) tetap dijaga

Yang keluar dari `wajahProduktif()` tetap **hanya** `['nama','foto','jumlah']`.
Menyaring di **query `User`**, bukan sesudahnya, berarti data yang tak berhak
memang tak pernah dikirim — bukan dikirim lalu disembunyikan.

**Berkas:** `app/Http/Controllers/DashboardController.php`
(`lingkupPic()` baru, `wajahProduktif()`, `performaPic()`).

### 4.2 · R8 — Sebaran per Departemen hanya PJO / MD / Admin

`sebaranDepartemen()` — penjaga `dashboardPenuh()` → `can('document.view_all')`.
Satu baris, dan pemegangnya persis ketiga peran itu.

**Donat `KartuSebaran` (sebaran per JENIS) TETAP untuk semua peran** (E2) —
jangan ikut tercabut karena namanya mirip.

### 4.3 · R9 — Lacak Status memakai tabel desain V1

`components/v2/dasbor/LacakStatus.tsx`: buang `v2/DataTable` beserta kolom
penyingkap dan komponen `Tahapan`, ganti dengan tabel dari
`components/dasbor/KartuPerjalanan.tsx` — `table-fixed`, empat kolom
**Pembuat · Dokumen · Kemajuan · Status**:

| Kolom | Isi |
|---|---|
| Pembuat (27%) | `Avatar` + nama + `jabatan · dept` |
| Dokumen (28%) | judul (tautan) + nomor |
| Kemajuan (30%) | label "menunggu apa" + `ke/total` + `Progress` |
| Status (15%) | lencana umur berona `tingkat` + pemegang |

- **Empat angka `matrix` berbatang dan baris kaki hitungan TETAP** — yang
  ditukar hanya tabelnya.
- **Nol data jadi yatim:** `daftarTahap`/`ke`/`persen` yang tadinya cuma terlihat
  di baris bentangan sekarang tampil permanen di kolom Kemajuan. `sejak` tetap
  dipakai `KartuKetersediaan` tab "Ditugaskan".
- Pakai `ui-maia/*` + `v2/Avatar` + `v2/StatusBadge`. Rona umur dari `tingkat`,
  warna status dari `statusMeta` — keduanya props, **nol hex baru** (§4).
- `table-fixed` + lebar per kolom itulah yang membuat tabelnya mustahil melebihi
  kartunya betapapun panjang judul dokumennya — tanpa gulir mendatar.
- Karena `KartuPerjalanan` menaruh tabelnya di `CardContent` ber-`px-0`
  (`pl-6`/`pr-6` di sel tepi), sedangkan `LacakStatus` memakai `CardContent`
  ber-padding biasa, **talangnya disesuaikan** — bukan disalin buta.
- `useState` `dibentang` dan impor `ArrowDown01Icon` ikut hilang.

### 4.4 · R10 — Kalender: angka tanggal biasa lebih terbaca

**Akar masalahnya:** seluruh tanggal lampau kena `disabled={{ before: today }}`,
dan `ui-maia/calendar` menatanya `text-muted-foreground opacity-50` — separuh
bulan berjalan nyaris tak terbaca.

Dikerjakan lewat prop `classNames` / `modifiersClassNames` **di tempat
pemanggilan** (`KartuKetersediaan.tsx`); berkas `ui-maia/` tak boleh disunting
(§4). Catatan teknis: `Calendar` menggabung `{ ...bawaan, ...classNames }`,
sehingga kunci yang dikirim **mengganti seluruh** nilai bawaannya — jadi string
penggantinya harus lengkap, bukan tambahan.

| Keadaan | Perlakuan | Kenapa berbeda |
|---|---|---|
| tanggal biasa / lampau (`disabled`) | opasitas & warna teks dinaikkan | terbaca sebagai TANGGAL, tapi tetap jelas tak bisa ditekan — `aria-disabled` & kursornya tak diubah, jadi maknanya tak hilang |
| **hari ini** | tetap `bg-muted`, + cincin & tebal | satu-satunya penanda POSISI |
| **agenda** | **titik saja** (`::after` yang sudah ada) | angkanya TIDAK ikut diwarnai (permintaan pemilik) |
| **cuti/off** | satu-satunya yang berlatar `--primary` lembut + angka `text-primary` tebal | satu-satunya yang berarti KETIDAKHADIRAN |

Legenda di bawah kalender ditambah butir "hari ini". Urutan
`modifiersClassNames` diperiksa untuk kasus hari-ini-yang-**juga**-cuti.

Ketiga perilaku wajibnya tak boleh tergeser: navigasi bulan lewat `<Link>`
`?bulan=`, klik tanggal membuka dialog Ajukan Off **terisi**, dan tanggal lampau
tetap mati. Tanggal tetap dibandingkan sebagai **string** `Y-m-d`, jangan lewat
`new Date()` (geser hari UTC/WITA).

### 4.5 · R11 — Palet ⌘K dihapus

- `components/v2/SiteHeader.tsx` — buang `<TombolCari>`, `<PencarianMenu>`,
  state `cari`, dan impor `Kbd` / `Search01Icon` yang jadi menganggur.
- **`components/v2/PencarianMenu.tsx` TIDAK dihapus, `cmdk` TIDAK dicopot**
  (E6) — perlakuan yang sama dengan trio K3. Berkasnya jadi nol pengimpor;
  dicatat di §7 sebagai pengingat aktif.
- `components/SiteHeader.tsx` (V1) **tak punya** kotak cari — diperiksa, bukan
  diasumsikan. Jadi R11 murni V2.
- `PratinjauUiTest` hanya menjaga pohon `dokumen/fields` & `tinjau`, jadi nol tes
  pecah karenanya.

### 4.6 · Tata letak `pages/V2/Dashboard.tsx`

Gerbang `sebaranDepartemen` dan `performaPic` **tak lagi sama**, jadi baris
`{sebaranDepartemen && performaPic && …}` harus dipecah:

```
GL · Non-Staff    → barisnya lenyap UTUH
SH · DH           → [ ganjal <div hidden> 2/3 ] [ PerformaPic 1/3 ]
PJO · MD · Admin  → [ PitaDepartemen     2/3 ] [ PerformaPic 1/3 ]
```

Ganjal `<div hidden>` mengikuti konvensi yang sudah dipakai baris `LacakStatus`
dan `KartuMasukan` — tanpanya kolom 1/3 melompat ke kiri, dan dua kerangka jadi
tak bisa dibandingkan berdampingan.

**Nol prop baru.** Barisnya digerbangi `!== null` atas prop yang sudah ada, jadi
keputusan peran tetap sepenuhnya milik server (§4).

### 4.7 · Tes — jumlah tak boleh berkurang (§14c)

Blast radius sudah diperiksa: **hanya `tests/Feature/DasborV2Test.php`** yang
menyentuh kedua prop ini. Dua tes gerbang **diperbarui, bukan dihapus**, dan
justru diperketat karena aturannya kini lebih kaya:

| Tes | Perubahan |
|---|---|
| `test_sebaran_departemen_gerbang_dan_bentuknya` | GL & SH kini `null`; PJO/MD/Admin non-null. Bentuk baris, `Department::count()`, dan urutan menurun tetap dikunci |
| `test_performa_pic_gerbang_dan_bentuk_wajahnya` | GL kini `null`; SH non-null; PJO non-null. Kunci `['nama','foto','jumlah']`, batas 8 wajah, dan penjaga ongkos query (`< 60`) tetap |

**Satu tes BARU yang wajib ada** — tanpa ini gerbangnya tak benar-benar terbukti:
wajah pada dasbor **SH** tak pernah memuat orang di luar departemennya **dan**
tak pernah memuat jabatan selain GL. Itulah satu-satunya jaminan saringannya
hidup di query, bukan di tampilan. Aktor MD dirakit dari `aktorUji(ROLE_MD, null, null)`
— helper `aktorMd()` belum ada di `tests/TestCase.php`.

Pastikan tetap hijau: `DashboardWidgetTest`, `DashboardRenderTest`,
`SpecDashboardV3Test`, `PratinjauUiTest`, `AntreanTugasTest`.

### 4.8 · Gerbang Fase 2a — sembilan, semuanya hijau (2026-09-01)

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` | bersih (client + SSR) |
| `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat |
| `smartpro:uji-render --pratinjau --rute=dashboard --nrp=…` × 5 peran | **oke** untuk GL · SH · Non-Staff · PJO · MD |
| `php artisan test` | **716** (715 lulus + 1 dilewati) — **+1**, yaitu tes baru §4.7 |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | **identik** sesudah tangkap ulang §3 |
| `git diff components.json` | KOSONG |

**Patokan tes naik 715 → 716**, dan itu memang yang diminta §4.7: dua tes gerbang
diperbarui (bukan dihapus), satu tes baru ditambahkan. CLAUDE.md §14c ikut
dicatat.

Baris keenam adalah gerbang yang **tidak** ditulis di §6 dan sengaja
ditambahkan: Fase 2a-lah yang pertama kali membuat `sebaranDepartemen` dan
`performaPic` bernilai `null` pada peran nyata, dan `--nrp` bawaan perintah itu
`ADM-0001` — satu-satunya peran yang menerima KEDUA kartu. Menjalankannya sekali
saja karena itu memeriksa persis kerangka yang paling kecil kemungkinannya
pecah. Kelima peran lain diperiksa satu per satu, dan semuanya tergambar.

---

## 5. Fase 2b — Halaman login (R12) — SELESAI (2026-09-01)

Acuan pemilik: dua kolom — kiri formulir, kanan panel merek + kartu mengambang.

`layouts/V2/AuthLayout` pemakainya **cuma satu** (`V2/Auth/Login`) — diperiksa
lewat grep, bukan diasumsikan — jadi menggarapnya **tak menyentuh**
Register/Pending yang masih memakai layout V1.

Intinya tampilan: dua berkas TSX, nol prop baru, nol perubahan rute. Satu-satunya
PHP yang ikut adalah tiga baris di `LoginController::destroy()` — tanpanya
halaman ini mustahil dilihat sama sekali (§5.3).

### 5.1 · `layouts/V2/AuthLayout.tsx`

- Kolom kiri: logo **turun menemani judul** sebagai satu blok rata kiri di tengah
  kolom, bukan lagi dipatok di pojok atas halaman. Baris hak cipta tetap.
- Kolom kanan: panel gradasi `--primary` yang sudah ada **dibagi dua** — judul +
  paragraf sambutan di ATAS, satu kartu mengambang di BAWAH (`rounded-2xl`,
  bayangan lembut) berisi judul pendek + satu kalimat.
- **Nol hex, nol aset baru** — gradasinya tetap dua ujung dari SATU token dan
  teksnya `--primary-foreground`, pasangan yang menurut definisi kontras di
  terang maupun gelap (REDESAIN-UI-V2 §9b).
- **Deret wajah "+3695" di acuan TIDAK ditiru.** Halaman ini belum
  terautentikasi; angka pengguna di sana berarti mengarang data atau
  membocorkannya. Kartunya diisi kalimat identitas yang benar.
- Prop `lebar` dipertahankan. Ia membatasi logo, judul, DAN formulir sekaligus
  sekarang — jadi logo tak pernah melebar melampaui kotak isian di bawahnya.
- **Logo di puncak panel kanan DICABUT** (pertanyaan terbuka §5, keputusan
  pemilik 2026-09-01). Sesudah logo turun ke kolom kiri, yang di panel cuma jadi
  salinan kedua di layar yang sama. Panelnya karena itu benar-benar dua bagian:
  sambutan (`flex-1 justify-center`) dan kartu (`justify-between` menempelkannya
  ke bawah), sehingga kartunya duduk di tempat yang sama betapapun tinggi
  jendelanya.
- Latar kartu `bg-primary-foreground/10` + `ring-primary-foreground/20` — yaitu
  warna TEKSNYA SENDIRI yang ditipiskan. Itulah cara memenuhi "nol hex" tanpa
  menambah token: latar dan teks kartu dijamin kontras selama pasangan
  `--primary`/`--primary-foreground` masih pasangan.

#### Susulan 2026-09-01 — foto panel & ukuran logo

Dua koreksi sesudah pemeriksaan mata pertama, keduanya di berkas yang sama:

- **Panel kanan dapat FOTO** — `public/images/login.jpg` (kiriman pemilik,
  1199×1600, 56 KB). Keputusan lama "nol aset baru" (§9b REDESAIN-UI-V2) memang
  digantikan, karena asetnya sekarang datang dari pemilik. Gradasi `--primary`
  **tidak dibuang** melainkan turun pangkat jadi lapis tint di atas fotonya:
  `from-primary/85 via-primary/30 to-primary/60`. Itu bukan hiasan — fotonya
  nyaris hitam di atas tapi merah TERANG di bawah, persis tempat kartu
  mengambang duduk, jadi `primary-foreground` di sana kontrasnya tipis tanpa
  tint. Paling pekat di bawah, paling tipis di tengah supaya gelombangnya tetap
  terlihat. **Tetap nol hex**: yang dipakai token yang sama dengan opasitas
  berbeda. Fotonya `alt=""` karena ia dekorasi.
- **Logo `h-8` → `h-12`**, menyamakannya dengan logo sidebar V2
  (`docs/PATOKAN-GAYA-V2.md` §3.1). Aman karena berkasnya 4000×2000 = 2:1, jadi
  `h-12` berarti lebar 96px di kolom `max-w-xs` (320px).
- **`Auth/Login` V1 TIDAK ikut diubah** — REDESAIN-UI-V2 §2 mengunci 41 halaman
  lama sebagai acuan mata `CEKLIS-MATA-PER-PERAN.md`, dan ia dihapus di Tranche
  4. Foto `curved6.jpg` di sana tinggal sampai saat itu.

### 5.2 · `pages/V2/Auth/Login.tsx`

- Judul + subjudul **rata kiri**, mengikuti acuan.
- Kolom Kata Sandi dapat **tombol mata** buka/tutup — satu `useState`, `type`
  bertukar `password`/`text`, tombolnya ber-`aria-label` dan `aria-pressed`.
  Ini satu-satunya kendali baru.
- **Tanpa "Lupa Kata Sandi"** — rutenya memang tak ada (reset sandi hanya lewat
  Admin, `routes/web.php`), dan tautan yang berujung 404 lebih buruk daripada
  tak ada tautan. "Ingat saya" karena itu berdiri sendiri.
- **Tanpa tombol pihak ketiga** — tak ada login Google/Facebook di aplikasi ini.
- Pesan galat tetap **satu** pesan pada kunci `nrp` (supaya tak jadi alat menebak
  NRP terdaftar); rate limit tetap di server (§15).

#### Tombol matanya dari registry, bukan digambar sendiri

Bungkusnya `ui-maia/input-group` (`InputGroup` + `InputGroupInput` +
`InputGroupAddon align="inline-end"` + `InputGroupButton size="icon-xs"`) —
komponen resmi yang **sudah ada** di kit, jadi nol berkas `ui-maia/` disunting
(CLAUDE.md §4) dan nol kelas karangan (§13).

Yang membuatnya bukan sekadar selera: `InputGroup` sudah memindahkan cincin
fokus dan rona `aria-invalid` dari input ke PEMBUNGKUSNYA
(`has-[[data-slot=input-group-control]:focus-visible]:…`). Tombol yang ditempel
sendiri di atas `Input` ber-`relative` akan menyisakan input yang bercincin di
dalam kotak yang tak bercincin, dan kotak sandi yang gagal memerah saat
kredensialnya salah. Tinggi & radiusnya pun sudah sama persis dengan kotak NRP
(`h-9 rounded-4xl`) tanpa satu angka pun diketik ulang.

`sandiTampil` sengaja **tidak** diingat antar-kunjungan: bawaannya harus selalu
tertutup, karena layar login paling sering dibuka justru di tempat yang ada
orang lain.

Ikonnya `ViewIcon` / `ViewOffSlashIcon` diimpor **langsung** dari hugeicons,
bukan lewat `v2/Ikon.tsx` — peta di sana khusus kunci `bi-*` yang datang dari
server (`STATUS_META`, `NavigasiSidebar`, kolom `informasi_kategori.ikon`), dan
ikon kendali murni tampilan tak punya kunci di sana. Polanya sama dengan
`v2/SiteHeader` (`Moon02Icon`/`Sun03Icon`) dan `v2/PenyaringDokumen`
(`Search01Icon`).

### 5.3 · Bendera pratinjau harus SELAMAT dari logout

Sesudah kedua berkas di atas selesai dan seluruh gerbang hijau, halaman
login di peramban **tetap V1**. Bukan cacat kodenya — jalan buntu, dan
tertutup rapat dari tiga sisi:

| Sisi | Berkas |
|---|---|
| Sakelar `pratinjau.toggle` ada di grup **`auth`** | `routes/web.php` |
| `/login` ada di grup **`guest`** | `routes/web.php` |
| `destroy()` memanggil `session()->invalidate()` → **`ui_v2` ikut terbuang** | `Auth/LoginController.php` |

`ui_v2` hidup HANYA di sesi (nol env, nol config, nol query string — diperiksa
dengan `grep -rn ui_v2 app/ routes/ config/`). Jadi tak pernah ada satu momen
pun di mana benderanya menyala sementara pemakainya berstatus tamu, dan
`V2/Auth/Login` mustahil dicapai lewat peramban.

Perbaikannya tiga baris di `destroy()`: baca `ui_v2` sebelum `invalidate()`,
tulis balik sesudahnya. **Aman** karena benderanya kosmetik murni — yang
diubahnya cuma nama komponen TSX yang dipilih `PratinjauResponseFactory` untuk
payload yang sama persis; nol izin, nol data. Sesi barunya tetap sesi baru:
id-nya sudah dimigrasi dan token CSRF-nya sudah diputar. Ikut terhapus di
Tranche 4 bersama factory dan sakelarnya.

Urutan pakainya sekarang: **masuk → nyalakan sakelar pratinjau → logout →
`/login` menggambar V2.**

Penjaganya `PratinjauUiTest::test_bendera_pratinjau_selamat_dari_logout`, dan
ia memeriksa **komponen yang tergambar** sesudah logout, bukan cuma isi sesi:
sesi yang benar tapi factory yang tak membacanya tetap berarti orang melihat
login lama. Arah sebaliknya ikut dikunci — bendera MATI tak boleh diam-diam
menyala. Tesnya sudah dibuktikan **MERAH** bila ketiga barisnya dicabut
(`Failed asserting that null matches expected true`), jadi ia bukan tes yang
lolos dua-duanya.

### 5.4 · Gerbang Fase 2b — sembilan, semuanya hijau (2026-09-01)

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` | bersih |
| `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat — `login` → `Auth/Login` |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat — `login` → **`V2/Auth/Login`** |
| `php artisan test` | **717** (716 lulus + 1 dilewati) — **+1**, yaitu tes §5.3 |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | **nol BEDA**; satu HILANG — lihat di bawah |
| `git diff components.json` | KOSONG |

Gerbang kelima adalah yang paling berarti di fase ini: `tsc`, `build`, dan
`uji-render` bertiga hanya memeriksa tipe dan penyusunan modul, dan halaman
login V2 hanya benar-benar DIRENDER dengan `--pratinjau`. Ia tersapu sebagai
**tamu** — `UjiRender::penyapu()` memang menyapu tiga persona, dan halaman tamu
cuma terbuka bagi yang ketiga.

**Patokan tes naik 716 → 717.** Yang ditambahkan bukan tes untuk pekerjaan
MENGGAMBAR — `AuthLayout` + `Login.tsx` nol tes baru, dan itu benar: tes Inertia
membaca PROPS, dan props halaman login tak berubah satu kunci pun; yang
menjaganya gerbang render (§14c). Tes barunya menjaga satu-satunya PERILAKU yang
ikut berubah di fase ini, yaitu bendera yang menyeberangi logout (§5.3).

#### ⚠️ `cetak-baseline` — satu HILANG, dan lagi-lagi DATA

```
HILANG SOP_PPA-ADRO-SOP-ICTMD-05_id1241.pdf   (belum ada baseline)
```

Kedelapan berkas lain **`sama`**, termasuk keempat yang ditangkap ulang di §3.
Nol `BEDA`. Bukti bahwa ini data:

1. `md5sum` mesin cetak **23/23 OK**.
2. Fase 2b menyunting **dua berkas TSX** dan nol PHP — `BaselineCetak` hanya
   memakai `Document` + `Services\Print\PdfRenderer`, dan tak satu pun berkas
   yang disentuh ada di jalur itu.
3. id1241 berstatus `draft`, lahir **2026-09-01 pukul 01:58** lewat peramban —
   sesudah gerbang Fase 2a hijau. Ia dokumen BARU, bukan berkas lama yang
   berubah: baseline-nya memang belum pernah ada.

**Belum ditangkap ulang.** Mengubah acuan kebenaran adalah keputusan pemilik
(preseden §3), dan sekali ini pun murni aditif — id1241 belum punya berkas
baseline, jadi `--tulis --id=1241` hanya MENAMBAH, tak menimpa apa pun:

```
php artisan smartpro:cetak-baseline --tulis --id=1241
```

---

## 6. Verifikasi

Jalankan dari **PowerShell** — Git Bash mengubah `/dashboard` jadi
`C:/Program Files/Git/dashboard`. Dan **jangan** menjalankan perintah perender
Blade bersamaan dengan `php artisan test` (rebutan singgahan view di Windows).

```powershell
node_modules/.bin/tsc --noEmit
npm run build
npm run uji-render
php artisan smartpro:uji-render                  # bendera V2 MATI
php artisan smartpro:uji-render --pratinjau      # bendera V2 NYALA
php artisan test                                 # wajib >= 718
md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23/23
php artisan smartpro:cetak-baseline              # lihat catatan §3
git diff components.json                         # WAJIB KOSONG
```

**Ongkos query** dihitung ulang sesudah Fase 2a
(`DB::enableQueryLog()` → `GET /dashboard` sebagai PJO): penjaga `DasborV2Test`
menolak ≥ 60. Saringan jabatan menumpang query `User` yang **sudah ada**, jadi
jumlahnya seharusnya **tidak** bergerak — kalau bergerak, ada N+1 yang menyelinap.

### Pemeriksaan mata — gerbang otomatis tak pernah memeriksa tata letak

Nyalakan pratinjau di topbar, tema **terang dan gelap**:

| Peran | Yang wajib benar |
|---|---|
| GL | Performa PIC & Sebaran per Dept **dua-duanya lenyap**, barisnya hilang utuh — bukan menyisakan kolom yatim. Donat Sebaran Jenis **tetap ada** |
| SH / DH | Performa PIC di kolom 1/3, kolom 2/3 kosong. Wajahnya **hanya GL departemennya sendiri** |
| PJO / MD / Admin | Sebaran per Dept 2/3 + Performa PIC 1/3; wajahnya GL **dan** SH lintas 7 dept |
| Non-Staff | Baris itu lenyap, `LacakStatus` absen, tombol Masukan berbunyi "Beri Masukan" |
| Semua | Lacak Status memakai tabel V1 (avatar pembuat + pita kemajuan), nol gulir mendatar |
| Semua | Kalender: tanggal lampau terbaca tapi jelas mati; hari ini, agenda (titik), dan cuti tetap **tiga rupa berbeda**; klik tanggal tetap membuka dialog terisi; ganti bulan tak mengembalikan tab ke "Semua" |
| Semua | Topbar tak lagi punya kotak "Cari menu…"; ⌘K tak melakukan apa-apa |
| Semua | Tombol aksi di setiap daftar berbaris **mendatar**, tak menabrak baris di bawahnya |
| Semua | Mengetik di kotak cari langsung menyaring; kursor **tidak** keluar dari kotak; tombol Back peramban tetap berarti |
| Tamu | Login: logo menemani judul, judul **rata kiri**, mata sandi bekerja, panel kanan punya kartu mengambang (bukan foto `curved6.jpg`), terbaca di tema gelap |

> **Baris Tamu butuh urutan khusus: masuk → nyalakan sakelar pratinjau →
> logout → buka `/login`.** Sebelum §5.3, langkah ketiga selalu membuang
> benderanya dan langkah keempat mustahil. Kalau yang tergambar masih foto
> berwarna di kolom kanan dan judul rata tengah, itu `Auth/Login` (V1) — berarti
> benderanya mati, bukan berarti halamannya belum jadi.

---

## 7. Yang TIDAK disentuh — dan itu disengaja

- **Mesin cetak (§14b, 23 berkas)** dan seluruh `resources/views/**`.
- `components/ui/**` dan `components/ui-maia/**` — seluruh penyesuaian lewat
  `className` di tempat pemanggilan (§4).
- `components.json` — tetap `radix-nova` / `lucide` / `ui`. Kalau perlu
  `shadcn add`, tukar sementara ke maia/hugeicons/`ui-maia` lalu **kembalikan**,
  dan buktikan dengan `git diff components.json` yang kosong.
- `app/Services/NavigasiSidebar.php` dan `app/Services/AntreanTugas.php` —
  semantik angka notif **tetap berbasis AKSI** (E3): angkanya bertahan sampai
  pekerjaannya diselesaikan, bukan hilang saat menunya dibuka. Ketujuh kunci
  antrean sudah menempel di menu yang benar; yang rusak cuma posisinya (R1).
- `Document::STATUS_META` · `DocumentType::RUPA` · `DocumentParticipantResolver`
  · `ReviewerAvailability` · `config/smartpro.php`.
- 41 halaman V1 selain sel aksi & penyaringnya (R4/R5, yang memang diminta
  berlaku di kedua jalur).

### ⚠️ Pengingat aktif — berkas nol pengimpor yang sengaja DITAHAN

| Berkas | Sejak | Keputusan |
|---|---|---|
| `components/v2/dasbor/PitaSambutan.tsx` | T3 Fase 2 | K3 — tahan |
| `components/v2/dasbor/ArusDokumen.tsx` | T3 Fase 2 | K3 — tahan |
| `components/v2/dasbor/PerluTindakan.tsx` | T3 Fase 3 | K3 — tahan |
| `components/v2/PencarianMenu.tsx` | **V3 Fase 2a** | E6 — tahan; `cmdk` tetap terpasang |

Jangan menghapus satu pun sebagai "pembersihan" — penghapusan berkas butuh
konfirmasi eksplisit (§4).

---

## 8. Progres

| Fase | Isi | Status |
|---|---|---|
| **1** | R1–R6 — enam perbaikan akar tunggal, V1 + V2 | `[x]` **selesai 2026-08-31**, delapan gerbang hijau |
| **2a** | R7–R11 — gerbang peran, tabel V1, kalender, ⌘K | `[x]` **selesai 2026-09-01**, sembilan gerbang hijau (§4.8) |
| **2b** | R12 — halaman login | `[x]` **selesai 2026-09-01**, sembilan gerbang hijau (§5.4) |

Kedua belas permintaan §2 tergarap. Nol yang dibuang, nol yang ditunda.

**Lanjutannya bukan di berkas ini.** Sisa pekerjaan V2 — mengembarkan 35
halaman + 9 komponen yang belum punya kembaran, lalu Tranche 4 — punya rencana
dan patokannya sendiri di **`docs/PATOKAN-GAYA-V2.md`** (Fase 0 selesai
2026-09-01; Fase 1–7 antre).

> Yang MASIH menunggu di Fase 2a **dan 2b**: **pemeriksaan mata** (§6). Gerbang
> otomatis membuktikan halamannya tergambar dan propsnya benar; ia tak pernah
> bisa membuktikan kolom 1/3 SH berdiri di tempat yang benar, tanggal lampau di
> kalender sudah terbaca, atau kartu mengambang di panel login duduk di tempat
> yang enak dilihat. Tabel peran di §6 itulah daftar periksanya — baris **Tamu**
> untuk Fase 2b.
>
> Satu keputusan pemilik juga masih terbuka: menangkap ulang baseline cetak
> untuk id1241 (§5.4).
