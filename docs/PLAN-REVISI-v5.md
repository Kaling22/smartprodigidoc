# PLAN-REVISI-v5 — Tujuh Butir Revisi & Fitur Baru

> Status: **RENCANA**. Belum ada satu baris kode pun ditulis.
> Sumber: permintaan pemilik sesi 2026-08-18 (7 butir).
> Aturan pengerjaan tetap CLAUDE.md §5: **satu fitur → berhenti → review → commit**.
>
> Seluruh rujukan `berkas:baris` di bawah sudah **diverifikasi langsung ke kodebase**
> pada 2026-08-18, bukan dikutip dari dokumen lain.

---

## 1. Keputusan pemilik — TERKUNCI (sesi 2026-08-18)

| # | Keputusan | Akibatnya pada rencana |
|---|---|---|
| A1 | Indikator beban peninjau: **angka**, bukan bulatan | Blok `@for` bulatan dibuang; satu kunci keluaran baru di service |
| A2 | Kuota **2 dokumen dihapus** untuk SOP, SP, IK, **dan** JSA | `batas_dokumen` → `0`; kuncinya **tetap ada** sebagai rem darurat |
| A3 | Penempatan angka: **kolom tersendiri di kanan pita** | `.pp-papan-baris` jadi 3 kolom saat section peninjau |
| A4 | Tingkat: **0–2 normal · 3–4 padat · 5+ sibuk** | Dua ambang, bukan satu |
| A5 | Tingkat **hanya berlaku di JSA**; SOP/SP/IK angka polos | Deteksi jenis ditaruh di service, bukan di Blade |
| G1 | Butir 1 dan 2 dikerjakan **sebagai satu pekerjaan** | Keduanya jatuh di titik kode yang sama persis (§3.1) |

**Belum terkunci** (jangan mulai butir F sebelum dijawab): arti "**khusus hosting**"
pada butir 7 — lihat §5.

---

## 2. Urutan pengerjaan yang ditetapkan

Urutan ini **bukan** urutan penomoran permintaan, melainkan urutan risiko nyata di
kodebase. Dua butir (5 dan 6) mencabut izin peran yang **sudah aktif dipakai di
produksi**, dan satu butir (7) melanggar invarian wizard sekaligus menyentuh golden
test PDF. Menaruhnya di depan berarti memulai dari yang paling berbahaya tanpa alasan
teknis apa pun.

| # | Butir | Lingkup sentuhan | Risiko | Alasan posisi |
|---|---|---|---|---|
| **A** | **1 + 2** — indikator beban jadi angka, kuota dihapus | 1 view, 1 service, 1 config, 1 berkas gaya | **Rendah** | Nol migrasi, nol izin, nol perubahan alur. Butir 1 & 2 = satu titik kode. |
| B | 3 — GL SHE mengalihkan tugas peninjauan | 1 aksi baru, izin, notifikasi, audit | Sedang | Menyentuh dokumen berjalan (`reviewer_id`), tapi additif & bisa dikunci ke 2 status. Memakai ulang papan dari A. |
| C | 4 — distribusi dokumen untuk menu Informasi | Migrasi pencatat baca, service, widget | Sedang | Murni additif; tak menyentuh izin peran mana pun. |
| D | 5 — rombak siapa boleh Ajukan Revisi & membalas masukan | Seeder izin, rute, 2 model, ±4 view, banyak test | **Tinggi** | Mencabut izin dari SH & PJO yang aktif. Wajib rencana migrasi izin + rollback. |
| E | 6 — nonaktif dokumen lewat approval berjenjang | Status baru, notifikasi, view | **Tinggi** | Inisiatornya (GL/MD) **baru ada setelah D selesai**. Tak bisa didahulukan. |
| F | 7 — catatan revisi di awal alur SOP/SP/IK | Wizard, cetak PDF, golden test | **Tinggi** | Melanggar invarian "langkah virtual selalu di ekor" (4 titik) + golden test PDF. **Requirement masih rancu.** |

**Ketergantungan keras:** D → E. Sisanya bebas urutan, tapi A didahulukan karena B
memakai ulang papan ketersediaan yang baru saja disentuh.

---

## 3. FITUR A — Beban peninjau: angka, bukan kuota (butir 1 + 2)

### 3.1 Kenapa butir 1 dan 2 adalah satu pekerjaan

`ReviewerAvailability::bebanPeninjau()` (`app/Services/ReviewerAvailability.php:130-140`)
menghitung dokumen berjalan **tanpa membedakan jenis**:

```php
return Document::whereIn('reviewer_id', $ids)
    ->whereIn('status', ['waiting_for_review', 'in_review'])
    ->when($kecuali?->id, fn ($q, $id) => $q->where('id', '!=', $id))
    ->selectRaw('reviewer_id, COUNT(*) c')
    ->groupBy('reviewer_id')->pluck('c', 'reviewer_id')->map(fn ($c) => (int) $c)->all();
```

Dan papan pemilihannya pun **satu** untuk semua jenis — tak ada partial JSA terpisah
(`resources/views/documents/fields/_user_picker.blade.php:18-40`). Perbedaan antar-jenis
hanya nama section: SP/IK memakai `peninjau_penyetuju`, SOP & JSA memakai
`peninjau` + `penyetuju` (`database/seeders/DocumentTypeSeeder.php`). Ketiganya jatuh ke
cabang papan yang sama.

Konsekuensinya: menghapus kuota "untuk SOP/SP/IK" (butir 1) **otomatis** menghapusnya
untuk JSA (butir 2). Mengerjakannya terpisah hanya menghasilkan satu perubahan yang
dikerjakan dua kali.

### 3.2 Keadaan sekarang — hasil pemeriksaan

| Hal | Lokasi | Catatan |
|---|---|---|
| Meteran bulatan | `_papan-ketersediaan.blade.php:96-103` | `@for` sepanjang `$a['batas']`, ikon `bi-circle-fill`/`bi-circle`. **Bukan 2 hardcoded** — kebetulan 2 karena config bernilai 2 |
| Sumber angka | `ReviewerAvailability::untuk()` `:50-87` | View **tidak** menghitung apa pun, hanya membaca |
| Pembaca `batas_dokumen` | **hanya 2**: `ReviewerAvailability.php:58` & `DocumentController.php:226` | Permukaan perubahan sangat kecil |
| Dukungan "tanpa batas" | `ReviewerAvailability.php:71` (`$batas > 0 && …`), `DocumentController.php:228` (`$batas <= 0 → null`) | **Sudah ada dan sudah diuji** (`ReviewerAvailabilityTest.php:257-269`) |
| Penjaga sisi server | `saringPeninjauTakTersedia()` `:187-213` (saat pilihan berubah), `tolakBilaPeninjauPenuh()` `:224-242` (saat kirim) | Masing-masing dipanggil dari **satu** tempat: `saveStep()` `:377` dan `:412` |
| Grid papan | `_pita-style.blade.php:82` | `grid-template-columns: minmax(0, 14rem) 1fr` |
| Deteksi JSA | pola yang sudah dipakai `ReviewAccess.php:28` | `$document->type?->code === 'JSA'`; relasi `type()` di `Document.php:91-94` |
| `$document` di partial | `edit.blade.php:112` | Sudah diteruskan ke tiap field partial — **tak perlu jalur data baru** |

**Dua temuan yang mengubah rancangan menjadi lebih murah:**

1. **Sistem badge sudah ada.** `layouts/app.blade.php:553-586` mendefinisikan
   `.badge-soft` + varian `secondary / warning / danger`, **lengkap dengan padanan dark
   mode**. Pil angka cukup memakainya → **nol CSS warna baru**, dan otomatis konsisten
   dengan lencana status di seluruh aplikasi. Yang perlu ditulis hanya aturan **grid**.
2. **Kartu dashboard sudah melepas meteran beban.** `partials/_ketersediaan.blade.php:8-11`
   menyatakannya eksplisit. Jadi perubahan ini **tidak menyentuh dashboard sama sekali**.

**Yang aman karena diperiksa:**
- Tak ada API mobile yang mengekspos `beban`/`batas`. `UserResource.php:79` hanya
  memuat `bisa_ketersediaan` (boolean). → **kontrak aplikasi terpasang tidak pecah.**
- Validator radio di `layouts/app.blade.php:1011-1040` memilih elemen lewat selektor
  (`input[type=radio][required]`), **bukan** posisi DOM. → menambah kolom aman.
- `DocumentController.php:292-293` & `DocumentRevisionController.php:96-99` memanggil
  `untuk()` **tanpa** dokumen (untuk modal revisi); nilai tingkat di sana akan `normal`
  dan memang tak dirender.

### 3.3 Perubahan, berkas per berkas

#### (1) `config/smartpro.php`

- `'batas_dokumen' => 2` → `0`.
  **Kuncinya tidak dihapus.** Ini rem darurat: menaikkannya kembali menghidupkan
  seluruh pembatasan tanpa satu baris kode pun — persis janji yang sudah tertulis di
  komentar `:38-46`.
- Tambah di blok `peninjau`:
  ```php
  'ambang' => ['jenis' => ['JSA'], 'padat' => 3, 'sibuk' => 5],
  ```
- Perbarui komentar `:36-46` supaya tidak lagi berbunyi seolah kuota aktif, dan
  tuliskan **kenapa** ambang ada di config (pemilik menyetel tanpa menyentuh kode/test)
  serta **kenapa `jenis` berupa daftar** (menambah SP ke sana kelak = satu suntingan).

#### (2) `app/Services/ReviewerAvailability.php`

- Di `untuk()`, tambah **satu** kunci keluaran: `'tingkat' => 'normal'|'padat'|'sibuk'`.
  Dihitung dari `$jumlah`, dan **hanya** bila `$kecuali?->type?->code` ada di
  `ambang.jenis`; selain itu selalu `'normal'`.
- Perbarui docblock `@return` `:45-48` (kontrak array ini dibaca view).
- Perbarui komentar kelas `:24` yang masih menerangkan keadaan "PENUH".

> **Kenapa di service, bukan di Blade:** view papan tetap bodoh — ia hanya mencetak
> nilai. Efek sampingnya gratis: `untukSatu()` (kartu dashboard) memanggil tanpa
> dokumen, jadi otomatis dapat `normal` tanpa percabangan tambahan.

> **Catatan kehati-hatian:** parameter `$kecuali` sekarang dipakai untuk dua hal
> (pengecualian hitungan **dan** sumber jenis dokumen). Itu memang objek dokumen yang
> sedang disunting, jadi sah — **tulis komentarnya**, jangan biarkan pembaca berikutnya
> menebak.

#### (3) `resources/views/documents/fields/_papan-ketersediaan.blade.php`

| Baris | Tindakan |
|---|---|
| 76 | Tambah `'tingkat' => 'normal'` ke array fallback — **jangan terlewat**, kandidat yang tak punya entri ketersediaan akan error tanpa ini |
| 96-103 | Hapus blok `@for` bulatan |
| 86-122 | Tambah kolom ketiga di dalam `.pp-papan-baris`: angka + satuan (`3` / `dok`) memakai `badge-soft badge-soft-{secondary\|warning\|danger}` sesuai `$a['tingkat']` |
| 61-72 | Tambah `<span>` kosong ketiga di baris header supaya kolom tetap sejajar |
| 58 | Beri kelas penanda pada `.pp-papan` (mis. `pp-papan-beban`) **hanya bila `$memblokir`** → CSS memilih grid 2 atau 3 kolom |
| 128-135 | Legenda: tambah keterangan padat/sibuk, **tampil hanya saat ambang aktif** — jangan menjelaskan warna yang tak muncul di papan SOP |
| 142 | "Semua kandidat sedang penuh atau off" → hapus kata "penuh"; hanya off yang bisa mengunci sekarang |

Kolom angka **tidak** dirender pada section `penyetuju` (`$memblokir === false`). Alasan
lamanya tetap berlaku dan sudah tertulis di `:93-95`: di sana angka itu mengukur beban
**peninjauan**, yang tak ada hubungannya dengan tugas menyetujui.

Aksesibilitas: pil angka wajib punya teks yang terbaca pembaca layar
(mis. `title`/`aria-label` "3 dokumen sedang ditinjau"), bukan angka telanjang.

#### (4) `resources/views/partials/_pita-style.blade.php`

- Tambah di blok "PITA LURUS" (`:79-95`):
  `.pp-papan-beban .pp-papan-baris { grid-template-columns: minmax(0, 14rem) 1fr auto; }`
  plus perataan sel angka.
- Media query `:120-124` sudah melipat papan jadi 1 kolom di bawah 768px — pastikan
  varian 3 kolom **ikut** dilipat, jangan tertinggal sebagai grid 3 kolom yang gepeng.
- **Nol warna baru di sini** — warna datang dari `badge-soft-*`.
- Berkas ini dibungkus `@once` (`:19`); tak perlu penjagaan tambahan.

#### (5) `resources/views/documents/fields/_user_picker.blade.php`

- Komentar `:33-35` masih berbunyi "Batas 2 dokumen TIDAK berlaku bagi penyetuju".
  Perbarui. Komentar yang berbohong lebih mahal daripada kode yang salah.

### 3.4 Yang SENGAJA tidak disentuh

| Hal | Alasan |
|---|---|
| `tolakBilaPeninjauPenuh()` & `saringPeninjauTakTersedia()` **tidak dihapus** | Yang pertama sudah `return null` saat `batas <= 0` — dorman dengan sendirinya. Menghapusnya membuang jalan pulang bila lapangan minta kuota dihidupkan lagi |
| Penguncian karena **OFF tetap berlaku** | Permintaan ini menghapus kuota, bukan menghapus jadwal. Peninjau yang cuti tetap `disabled` |
| `bebanPeninjau()` tetap lintas-jenis | Memfilter per jenis membuat angka di layar tak lagi sama dengan beban sesungguhnya seseorang |
| Kartu dashboard "Ketersediaan Saya" | Sudah tak memuat meteran beban sejak versi sebelumnya |
| CSS mati `.pp-kal-sel/.pp-kal-tgl/.pp-kal-tanda` di `_pita-style.blade.php:61-77` | Sudah tak dipakai siapa pun, **tapi** membersihkannya adalah pekerjaan lain — jangan dicampur ke diff ini |

### 3.5 Risiko & pencegahan

| Risiko | Pencegahan |
|---|---|
| Tanpa kuota, satu SH bisa dibanjiri diam-diam | Justru itu fungsi angka + warna. Cadangannya: `batas_dokumen` tetap ada sebagai rem satu-suntingan |
| `ReviewerAvailabilityTest.php:81-107` **akan merah** — ia mengandalkan default 2 dan `assertSee('Penuh — 2 dokumen berjalan')` | **Jangan hapus test itu.** Tambahkan `config(['smartpro.peninjau.batas_dokumen' => 2])` di awalnya. Pembatasan masih ada di kode, jadi harus tetap dijaga test |
| `TestCase::peninjauBersih()` (`tests/TestCase.php:32-49`) dibuat khusus untuk menghindari kuota | Dengan kuota mati ia jadi no-op tak berbahaya. Biarkan — mencabutnya menyentuh banyak berkas test tanpa keuntungan |
| Array fallback `:76` tanpa `'tingkat'` → `Undefined array key` | Tambahkan bersamaan dengan perubahan view (sudah dicantumkan di §3.3) |
| Kolom ketiga merusak keselarasan di layar sempit | Media query 767.98px wajib diuji manual pada 375px |
| Pil "padat" oranye tertukar dengan sel **off** yang juga oranye | Keduanya di kolom berbeda dan berbentuk berbeda (pil vs batang). Bila saat uji manual masih membingungkan, turunkan "padat" ke `badge-soft-secondary` bertebal dan sisakan warna hanya untuk "sibuk" |
| Komentar JS `layouts/app.blade.php:1027-1029` menyebut "semua peninjau penuh/off" | Perbarui kalimatnya; perilakunya sendiri tak berubah |

### 3.6 Verifikasi

**Test yang sudah ada — harus tetap hijau**
```
php artisan test --filter=ReviewerAvailabilityTest
```
13 test, termasuk `test_batas_nol_berarti_tanpa_batas` (`:257`) dan
`test_penyetuju_tak_pernah_terkunci` (`:297`).

**Test baru** — tambahkan ke `tests/Feature/ReviewerAvailabilityTest.php`, satu test
yang mengunci ketiga janji fitur ini sekaligus:
1. peninjau dengan **5 dokumen berjalan tetap bisa dipilih** — POST `saveStep`
   benar-benar menyimpan `reviewer_id` (dulu ditolak);
2. papan **SOP** menampilkan angkanya **tanpa** kelas tingkat;
3. papan **JSA** dengan beban 3 → `badge-soft-warning`, beban 5 → `badge-soft-danger`.

> Helper `bebani()` (`:73-79`) saat ini selalu membuat SOP. Untuk poin 3 ia perlu
> parameter jenis, atau dibuatkan pasangannya untuk JSA.

**Regresi cepat**
```
php artisan test --filter="Dashboard|Withdraw|RevisionTypeB"
```
Berkas-berkas itu memakai `peninjauBersih()` / `Http::fake` API libur.

**Uji manual**
1. Login GL → draft **SOP** → langkah peninjau: angka muncul di kanan pita, **polos**.
2. Bebani satu GL SHE dengan 3 dokumen → draft **JSA**: pil kuning "padat".
   Naikkan ke 5 → pil merah "sibuk", **dan barisnya tetap bisa dipilih**.
3. Peninjau ber-status **off** → tetap terkunci (`disabled`).
4. Section **penyetuju** (SOP/JSA) → **tanpa** kolom angka.
5. Dark mode + lebar 375px.
6. Kirim dokumen ke peninjau yang memegang 5 dokumen → **berhasil terkirim**.

### 3.7 Checklist eksekusi

- [ ] `config/smartpro.php` — `batas_dokumen => 0`, tambah `ambang`, perbarui komentar
- [ ] `ReviewerAvailability::untuk()` — kunci `tingkat` + docblock `:45-48` + komentar `:24`
- [ ] `_papan-ketersediaan.blade.php` — fallback `:76`, hapus `@for`, kolom ke-3, header, legenda, kalimat buntu `:142`
- [ ] `_pita-style.blade.php` — grid 3 kolom + aturan mobile
- [ ] `_user_picker.blade.php:33-35` — perbarui komentar
- [ ] `layouts/app.blade.php:1027-1029` — perbarui komentar JS
- [ ] `ReviewerAvailabilityTest.php:81` — pin `batas_dokumen => 2`
- [ ] Test baru (3 janji di §3.6)
- [ ] Jalankan test + 6 langkah uji manual
- [ ] **BERHENTI** — laporkan, tunggu review & commit

---

## 4. Temuan untuk butir B–F (bahan rencana berikutnya)

Bagian ini **bukan rencana**, melainkan hasil pemeriksaan yang akan menghemat waktu
saat gilirannya tiba. Rencana detail dibuat saat itu, karena kodebase akan bergeser.

### B — butir 3: GL SHE mengalihkan tugas peninjauan

- Peninjau JSA ditentukan `DocumentParticipantResolver.php:72-101`: GL SHE & Plant,
  **kecuali** departemen dokumen itu sendiri.
- Pengalihan = mengganti `documents.reviewer_id` saat status `waiting_for_review` /
  `in_review`. Kandidat tujuan **wajib** diambil dari resolver yang sama + saringan
  `ReviewerAvailability` — jangan menulis daftar kandidat kedua.
- Wajib: audit log, notifikasi ke penerima, tolak pengalihan ke diri sendiri dan ke GL
  departemen dokumen.
- Titik jebakan: izin `document.review_jsa` terpisah dari `document.review`
  (`AppServiceProvider.php:68`, `User::canReviewJsa()`).

### C — butir 4: distribusi dokumen untuk menu Informasi

- Informasi punya **tabel sendiri** (`informasi`, `app/Models/Informasi.php:22`) —
  bukan `documents`, bukan `document_types`. Model itu sendiri menuliskan alasan
  pemisahannya di `:11-17`.
- Widget distribusi dirender **server-side, tanpa chart library**:
  `partials/_widget-distribusi.blade.php`, data dari `DashboardController.php:450-484`
  lewat `app/Services/DocumentDistribution.php`.
- **Kendala utama:** Informasi **belum mencatat siapa yang membaca**. "Cakupan" baru
  punya arti setelah ada pencatat baca — itu migrasi baru.
- Switch mutu ↔ informasi = **parameter pada widget yang sama**, bukan widget kedua.
- Chart.js sudah termuat di `dashboard.blade.php:526` bila kelak butuh grafik — jangan
  menambah dependensi.

### D — butir 5: rombak izin Ajukan Revisi & membalas masukan

- Semuanya bergantung pada **satu** izin spatie `document.request_revision`
  (`RolePermissionSeeder.php:62-73`), dipakai rute `routes/web.php:251-255` **dan**
  `DocumentFeedback::bisaDiadopsiOleh()` (`app/Models/DocumentFeedback.php:118-123`).
- **Membalas** masukan diatur terpisah di `bisaDibalasOleh()` (`:92-109`) — sekarang
  memberi GL/SH/DH sedepartemen, plus pemegang `document.view_all`.
- **Titik jebakan terbesar: MD bukan jabatan.** Ia role spatie
  `management_development` dengan `jabatan = null` (`UserManagementController.php:148-150`,
  dikunci `ManajemenUserDanPemusnahanTest.php:53-57`). Semua aturan berbasis
  `department_id`/jabatan **tidak berlaku padanya** — MD lolos lewat `document.view_all`.
  Merancang "MD boleh, SH tidak" berarti memisahkan dua hal yang hari ini satu izin.
- Butuh rencana migrasi izin + rollback, dan `tests/Feature/DocumentFeedbackTest.php`
  (14 test) akan banyak berubah.

### E — butir 6: nonaktif dokumen lewat approval berjenjang

- Sekarang sekali-klik: `DocumentRevisionController::makeObsolete()` `:201-210`
  (izin `document.request_revision`, hanya dari `published`).
- Repo **sengaja menolak tabel tahapan generik** — alasannya tertulis di migrasi
  `2026_08_01_093000_add_pending_md_status_to_documents.php:18-24` ("Satu status sudah
  memodelkannya"). **Ikuti polanya**: status baru, bukan mesin alur baru.
- `Approval` dan `Review` adalah jejak keputusan, bukan mesin alur — jangan salah baca.
- Bergantung pada D: inisiator GL/MD baru ada setelah izin dirombak.

### F — butir 7: catatan revisi di awal alur

- Langkah "Log Revisi" saat ini **virtual di ekor**. Konvensi "virtual = di atas
  `stepCount()`" tertanam di `DocumentController.php` (`edit:317`, `saveStep:370-384`,
  `autosave:447-451`) **dan** `edit.blade.php:18-20, 49-52`.
- Bila langkah baru ditambahkan sebagai **step schema** (di `schema_json`, geser
  sisanya), biayanya nyaris nol — `stepCount()` naik sendiri. Bila ditambahkan sebagai
  langkah **virtual di depan**, keempat titik di atas harus dibalik logikanya.
- `documents.current_step` pada draft yang **sudah ada** akan bergeser maknanya —
  butuh pertimbangan data lama.
- Golden test PDF (`tests/fixtures/golden/SOP.json:17`, `SP.json`, `IK.json`) ikut
  mengunci hasil cetaknya; `CoverPageTest` & `PrintLayoutTest` membaca stream PDF nyata.

---

## 5. Pertanyaan terbuka

**Butir 7 — arti "khusus hosting".** Dua bacaan yang menghasilkan pekerjaan sangat
berbeda:

- **(a)** Fitur hanya aktif di lingkungan produksi/hosting → saklar berbasis
  environment, alur wizard sama di mana-mana.
- **(b)** Berlaku khusus untuk dokumen yang diunggah/di-*host* (jenis kelas `unggahan`,
  mis. FK/PX) → percabangan per jenis dokumen, sama sekali bukan urusan environment.

**Jangan mulai butir F sebelum ini dipastikan.** Salah tebak di sini berarti menulis
ulang dari nol, bukan menambal.
