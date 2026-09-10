# PLAN-REVISI-v6 — Sembilan Butir Revisi & Fitur Baru

> Menggantikan `docs/PLAN-REVISI-v5.md` seluruhnya. v5 berisi 7 butir dan
> **belum satu baris pun dikerjakan** (diverifikasi: `batas_dokumen` masih `2`,
> blok `@for` bulatan masih ada, tak ada kunci `tingkat`, tak ada aksi pengalihan).
> Yang masih sahih dari v5 dipindahkan ke sini; sisanya sudah usang.
>
> Aturan pengerjaan tetap CLAUDE.md §5: **satu fase → berhenti → review → commit.**
> Delapan fase = delapan sesi.
>
> Sumber: permintaan pemilik sesi 2026-08-18 (7 butir) + pembaruan 2026-08-19
> (9 butir). Seluruh rujukan `berkas:baris` diverifikasi langsung ke kodebase
> pada 2026-08-19, bukan dikutip dari dokumen lain.

---

## Context — kenapa perubahan ini ada

SmartPro sudah dipakai (~Fase 2; alur SOP/IK/SP/JSA lengkap sampai PDF). Setelah
dipakai di lapangan muncul sembilan keluhan/permintaan nyata dari pemilik:

1. **Kuota peninjau 2 dokumen menyumbat kerja.** Departemen dengan satu Section
   Head langsung buntu. Indikator bulatan juga tak terbaca begitu bebannya lebih
   dari dua.
2. **Peninjau JSA sering ditugasi saat sedang menumpuk**, tanpa cara melihatnya.
3. **GL SHE yang kebanjiran JSA tak punya jalan keluar** selain mendiamkannya.
4. **Menu Informasi tak terukur** — tak ada yang tahu poster/kebijakan sudah
   dibaca siapa.
5. **Wewenang revisi salah alamat.** SH/DH & PJO — yang meninjau dan menyetujui —
   juga yang memulai revisi, padahal penyusunnya GL dan pemeriksa penulisannya MD.
   Masukan lapangan pun tercecer: ada di kartu dashboard dan di tempelan Dokumen
   Berlaku, tak punya halaman sendiri, dan alasan penolakan dokumen tak pernah
   bisa dilihat kembali di satu tempat.
6. **Daftar Tidak Berlaku membanjir** — tiap revisi menambah satu baris, sehingga
   satu dokumen dengan 5 revisi memakan 5 baris.
7. **Tidak ada jalan mundur.** Revisi yang ternyata keliru tak bisa dikembalikan
   ke versi sebelumnya.
8. **Menonaktifkan dokumen terlalu mudah** (sekali klik, satu izin) dan
   **nomornya mati selamanya**, padahal kolam nomor per jenis+dept terbatas.
9. **Dokumen lama yang diunggah tak punya lembar Catatan Revisi**, sehingga versi
   web dan versi cetak tak sinkron sejak hari pertama.

Hasil yang dituju: kuota diganti sinyal beban yang terbaca, wewenang kembali ke
peran yang benar, dokumen punya jalan mundur & jalan mati yang tertib, nomor
dokumen bisa didaur ulang, dan dokumen lama masuk lengkap dengan catatan revisinya.

---

## 1. Keputusan pemilik — TERKUNCI (sesi 2026-08-18 & 2026-08-19)

| # | Keputusan | Akibat |
|---|---|---|
| A1 | Indikator beban: **angka**, bukan bulatan | Blok `@for` dibuang |
| A2 | Kuota 2 dokumen **dihapus** untuk SOP/SP/IK **dan** JSA | `batas_dokumen → 0`; kuncinya TETAP ada sebagai rem darurat |
| A3 | Penempatan angka: **kolom tersendiri di kanan pita** | `.pp-papan-baris` jadi 3 kolom saat section peninjau |
| A4 | Tingkat: **0–2 aman · 3–4 padat · 5+ sibuk** | Dua ambang, di config |
| A5 | Tingkat **hanya di JSA**; SOP/SP/IK angka polos | Deteksi jenis di service, bukan Blade |
| **B1** | Pengalihan JSA **langsung berpindah**, penerima dinotifikasi | Nol status baru, nol tabel baru |
| **C1** | Cakupan Informasi **dipecah per departemen**; PJO/MD lihat semua | Tabel `informasi_reads` baru |
| **D1** | GL merevisi **hanya dokumen buatannya**; MD lintas 7 dept | `Document::bisaDirevisiOleh()` |
| **D2** | GL & MD **boleh** mengadopsi masukan, boleh juga tidak | Adopsi jadi opsional, bukan syarat |
| **D3** | **Alasan revisi WAJIB ditulis**, dikirim ke SH/DH & MD | `required`, bukan `required_without` |
| **D4** | SH/DH & PJO: **lihat saja** — tak membalas, tak merevisi, tak mengadopsi | Izin baru `document.feedback_respond` |
| **D5** | Menu baru **"Log Dokumen"** berisi 2 submenu: Masukan Lapangan + Alasan Dokumen | Tombol Revisi ada di DUA tempat (Berlaku & Masukan) |
| **F1** | Nonaktif **berurutan**. GL mengajukan → SH/DH → MD → PJO. MD mengajukan → SH/DH → PJO | Satu status + satu kolom tahap |
| **F2** | Nonaktif (dimatikan) **melepas nomor**; diaktifkan lagi = **nomor baru** | Kolom `obsolete_reason` |
| **G1** | Rollback **tanpa gerbang persetujuan terpisah** — jadi draft revisi GL, lalu alur pengesahan normal | Jauh lebih murah dari dugaan awal |
| **H1** | Potong halaman awal + sisipkan lembar revisi; **jumlah halaman yang dipotong dipilih user**, nomor halaman/edisi/revisi lembar sisipan bisa diatur | Butuh `setasign/fpdi` + `setasign/fpdf` |

**Pertanyaan terbuka v5 §5 ("khusus hosting") — TERJAWAB:** bacaan (b). Artinya
**dokumen existing yang diunggah**, bukan saklar environment. Butir 9 adalah
percabangan per jenis dokumen, sama sekali bukan urusan hosting.

---

## 2. Urutan fase & ketergantungan

Urutan ini **bukan** urutan penomoran permintaan, melainkan urutan risiko dan
ketergantungan nyata di kodebase.

| Fase | Butir | Risiko | Kenapa di posisi ini |
|---|---|---|---|
| **A** | 1 + 2 — beban jadi angka, kuota dihapus | Rendah | Nol migrasi, nol izin. Butir 1 & 2 jatuh di titik kode yang **sama persis** (§A.1) |
| **B** | 3 — GL SHE mengalihkan peninjauan | Sedang | Memakai ulang papan yang baru disentuh A |
| **C** | 5 — rombak izin revisi/masukan + menu Log Dokumen | **Tinggi** | **Didahulukan** dari butir 4: izin `document.request_revision` berpindah tangan, dan halaman Distribusi hari ini bergantung padanya. Membangun butir 4 dulu = membangunnya dua kali |
| **D** | 4 — distribusi dokumen Informasi | Sedang | Murni additif; dibangun langsung di atas izin yang sudah final |
| **E** | 6 — daftar Tidak Berlaku berkelompok | Rendah | Fase F & G sama-sama menambah baris/tombol di daftar ini — bereskan bentuknya dulu |
| **F** | 8 — nonaktif berjenjang + pelepasan nomor | **Tinggi** | Inisiatornya (GL/MD) **baru ada setelah C**. Memperkenalkan `obsolete_reason` yang dibutuhkan G |
| **G** | 7 — rollback ke versi sebelumnya | Sedang | Butuh C (GL boleh revisi), E (tombolnya di daftar itu), F (`obsolete_reason`) |
| **H** | 9 — catatan revisi di awal unggahan | **Tinggi** | Satu-satunya fase dengan dependensi baru. Berdiri sendiri — ditaruh terakhir agar penolakan dependensi tak memblokir apa pun |

**Ketergantungan keras:** A → B · C → D · C → F → G · E → G.
Fase H bebas urutan.

---

## FASE A — Beban peninjau: angka, bukan kuota (butir 1 + 2)

### A.1 Kenapa butir 1 dan 2 adalah satu pekerjaan

`ReviewerAvailability::bebanPeninjau()` (`app/Services/ReviewerAvailability.php:130-140`)
menghitung dokumen berjalan **tanpa membedakan jenis**:

```php
return Document::whereIn('reviewer_id', $ids)
    ->whereIn('status', ['waiting_for_review', 'in_review'])
    ->when($kecuali?->id, fn ($q, $id) => $q->where('id', '!=', $id))
    ->selectRaw('reviewer_id, COUNT(*) c')
    ->groupBy('reviewer_id')->pluck('c', 'reviewer_id')->map(fn ($c) => (int) $c)->all();
```

Dan papan pemilihannya pun **satu** untuk semua jenis — tak ada partial JSA
terpisah (`resources/views/documents/fields/_user_picker.blade.php:18-20`).
Perbedaan antar-jenis hanya nama section: SP/IK memakai `peninjau_penyetuju`,
SOP & JSA memakai `peninjau` + `penyetuju`; ketiganya jatuh ke cabang papan yang
sama. Menghapus kuota "untuk SOP/SP/IK" (butir 1) **otomatis** menghapusnya untuk
JSA (butir 2). Mengerjakannya terpisah hanya menghasilkan satu perubahan yang
dikerjakan dua kali.

### A.2 Keadaan sekarang — hasil pemeriksaan

| Hal | Lokasi | Catatan |
|---|---|---|
| Meteran bulatan | `_papan-ketersediaan.blade.php:96-103` | `@for` sepanjang `$a['batas']`, ikon `bi-circle-fill`/`bi-circle`. **Bukan 2 hardcoded** — kebetulan 2 karena config bernilai 2 |
| Sumber angka | `ReviewerAvailability::untuk()` `:50-87` | View **tidak** menghitung apa pun, hanya membaca |
| Pembaca `batas_dokumen` | **hanya 2**: `ReviewerAvailability.php:58` & `DocumentController.php:226` | Permukaan perubahan sangat kecil |
| Dukungan "tanpa batas" | `ReviewerAvailability.php:71` (`$batas > 0 && …`), `DocumentController.php:228` (`$batas <= 0 → null`) | **Sudah ada dan sudah diuji** (`ReviewerAvailabilityTest.php:257-269`) |
| Penjaga sisi server | `saringPeninjauTakTersedia()` `DocumentController.php:187-213` (saat pilihan berubah), `tolakBilaPeninjauPenuh()` `:224-242` (saat kirim) | Masing-masing dipanggil dari **satu** tempat: `saveStep()` `:377` dan `:412` |
| Grid papan | `_pita-style.blade.php:82` | `grid-template-columns: minmax(0, 14rem) 1fr` |
| Deteksi JSA | pola yang sudah dipakai `ReviewAccess.php:28` | `$document->type?->code === 'JSA'`; relasi `type()` di `Document.php:91-94` |
| `$document` di partial | `edit.blade.php:112` | Sudah diteruskan ke tiap field partial — **tak perlu jalur data baru** |

**Empat temuan yang membuat fase ini murah:**

1. **Sistem badge sudah ada.** `layouts/app.blade.php:553-586` mendefinisikan
   `.badge-soft` + varian `secondary / warning / danger`, **lengkap dengan padanan
   dark mode**. Pil angka cukup memakainya → **nol CSS warna baru**, dan otomatis
   konsisten dengan lencana status di seluruh aplikasi. Yang perlu ditulis hanya
   aturan **grid**.
2. **Kartu dashboard sudah melepas meteran beban.** `partials/_ketersediaan.blade.php:8-11`
   menyatakannya eksplisit. Jadi perubahan ini **tidak menyentuh dashboard sama sekali**.
3. **Tak ada API mobile yang mengekspos `beban`/`batas`.** `UserResource.php:79`
   hanya memuat `bisa_ketersediaan` (boolean). → **kontrak aplikasi terpasang
   tidak pecah.**
4. **Validator radio** di `layouts/app.blade.php:1011-1040` memilih elemen lewat
   selektor (`input[type=radio][required]`), **bukan** posisi DOM → menambah kolom aman.

Juga aman karena diperiksa: `DocumentController.php:292-293` &
`DocumentRevisionController.php:96-99` memanggil `untuk()` **tanpa** dokumen
(untuk modal revisi); nilai tingkat di sana akan `normal` dan memang tak dirender.

### A.3 Rancangan UI — penempatan angka

```
┌─────────────────────────┬──────────────────────────────────────┬────────┐
│ ○ Budi Santoso          │ ▓▓░░░░▓ ░░░░░░░  (pita 14 hari)      │  3 dok │
│   GL SHE · Tersedia     │                                      │ (pil)  │
└─────────────────────────┴──────────────────────────────────────┴────────┘
   minmax(0,14rem)                       1fr                        auto
```

- **Kolom ketiga, rata kanan, lebar `auto`.** Angka dari semua kandidat berbaris
  vertikal di ujung baris → bisa dibandingkan sekali lihat. Itulah alasan kolom
  tersendiri dipilih ketimbang menyelipkannya ke baris meta: lebar `jabatan`
  bervariasi (`GL ICTMD` vs `DH FAW-SCM`), sehingga angka di sana tak pernah rata.
- Isi pil: angka + satuan kecil, mis. **`3`** `dok`. Nol tetap dicetak `0 dok` —
  kekosongan yang eksplisit lebih terbaca daripada sel kosong.
- Warna via `badge-soft-*`: **SOP/SP/IK selalu `secondary`** (polos);
  **JSA**: 0–2 `secondary` · 3–4 `warning` · 5+ `danger`.
- **Kolom ini TIDAK dirender pada section `penyetuju`** (`$memblokir === false`).
  Alasannya sudah tertulis di `_papan-ketersediaan.blade.php:93-95`: di sana angka
  itu mengukur beban **peninjauan**, tak ada hubungannya dengan tugas menyetujui.
- **Aksesibilitas:** pil wajib punya `title` + `aria-label` berbunyi
  `"3 dokumen sedang ditinjau"` — bukan angka telanjang.
- **Mobile (<768px):** media query `_pita-style.blade.php:120-124` sudah melipat
  papan jadi 1 kolom; varian 3-kolom **wajib ikut dilipat**, pil pindah ke atas
  pita rata kanan.

### A.4 Perubahan, berkas per berkas

#### (1) `config/smartpro.php` — blok `peninjau` (`:52-55`)

```php
'peninjau' => [
    // 0 = tanpa batas (keputusan pemilik A2). Kuncinya SENGAJA tidak dihapus:
    // menaikkannya kembali menghidupkan seluruh pembatasan tanpa satu baris kode.
    'batas_dokumen' => 0,
    'hari_pita' => 14,
    // Ambang warna pil beban. `jenis` berupa DAFTAR supaya menambah SP kelak
    // = satu suntingan di sini, bukan percabangan baru di service.
    'ambang' => ['jenis' => ['JSA'], 'padat' => 3, 'sibuk' => 5],
],
```

Perbarui juga komentar `:31-50` yang masih berbunyi seolah kuota aktif, dan
tuliskan **kenapa** ambang ada di config (pemilik menyetel tanpa menyentuh
kode/test).

#### (2) `app/Services/ReviewerAvailability.php`

- Di `untuk()` (`:73-83`) tambah **satu** kunci keluaran:
  `'tingkat' => 'normal'|'padat'|'sibuk'`. Dihitung dari `$jumlah`, dan **hanya**
  bila `$kecuali?->type?->code` ada di `ambang.jenis`; selain itu selalu `'normal'`.
- Perbarui docblock `@return` (`:45-48`) — kontrak array ini dibaca view.
- Perbarui komentar kelas (`:24`) yang masih menerangkan keadaan "PENUH".

> **Kenapa di service, bukan di Blade:** view papan tetap bodoh — ia hanya
> mencetak nilai. Efek sampingnya gratis: `untukSatu()` (kartu dashboard)
> memanggil tanpa dokumen, jadi otomatis dapat `normal` tanpa percabangan tambahan.

> **Catatan kehati-hatian:** parameter `$kecuali` sekarang dipakai untuk dua hal
> (pengecualian hitungan **dan** sumber jenis dokumen). Itu memang objek dokumen
> yang sedang disunting, jadi sah — **tulis komentarnya**, jangan biarkan pembaca
> berikutnya menebak.

#### (3) `resources/views/documents/fields/_papan-ketersediaan.blade.php`

| Baris | Tindakan |
|---|---|
| 76 | Tambah `'tingkat' => 'normal'` ke array fallback — **jangan terlewat**, kandidat yang tak punya entri ketersediaan akan `Undefined array key` tanpa ini |
| 96-103 | **Hapus** blok `@for` bulatan |
| 86-122 | Tambah kolom ketiga `.pp-papan-beban-sel`: pil `badge-soft-{secondary\|warning\|danger}` sesuai `$a['tingkat']`, **hanya bila `$memblokir`** |
| 61-72 | Tambah `<span>Beban</span>` ketiga di baris header supaya kolom tetap sejajar |
| 58 | Beri kelas penanda `pp-papan-beban` pada `.pp-papan` **hanya bila `$memblokir`** → CSS memilih grid 2 atau 3 kolom |
| 128-135 | Legenda: tambah keterangan padat/sibuk, **tampil hanya saat ambang aktif** — jangan menjelaskan warna yang tak muncul di papan SOP |
| 142 | `"Semua kandidat sedang penuh atau off"` → buang kata "penuh"; hanya off yang bisa mengunci sekarang |

#### (4) `resources/views/partials/_pita-style.blade.php`

Tambah di blok "PITA LURUS" (`:79-95`):

```css
.pp-papan-beban .pp-papan-baris { grid-template-columns: minmax(0, 14rem) 1fr auto; }
.pp-papan-beban-sel { text-align: right; white-space: nowrap; }
```

Media query `:120-124` sudah melipat papan jadi 1 kolom di bawah 768px —
pastikan varian 3 kolom **ikut** dilipat, jangan tertinggal sebagai grid 3 kolom
yang gepeng. **Nol warna baru di sini** — warna datang dari `badge-soft-*`.
Berkas dibungkus `@once` (`:19`); tak perlu penjagaan tambahan.

#### (5) `resources/views/documents/fields/_user_picker.blade.php:33-35`

Komentar masih berbunyi "Batas 2 dokumen TIDAK berlaku bagi penyetuju".
Perbarui. Komentar yang berbohong lebih mahal daripada kode yang salah.

#### (6) `resources/views/layouts/app.blade.php:1027-1029`

Komentar JS menyebut "semua peninjau penuh/off". Perbarui kalimatnya;
perilakunya sendiri tak berubah.

### A.5 Yang SENGAJA tidak disentuh

| Hal | Alasan |
|---|---|
| `tolakBilaPeninjauPenuh()` & `saringPeninjauTakTersedia()` **tidak dihapus** | Yang pertama sudah `return null` saat `batas <= 0` — dorman dengan sendirinya. Menghapusnya membuang jalan pulang bila lapangan minta kuota dihidupkan lagi |
| Penguncian karena **OFF tetap berlaku** | Permintaan ini menghapus kuota, bukan jadwal. Peninjau yang cuti tetap `disabled` |
| `bebanPeninjau()` tetap **lintas-jenis** | Memfilter per jenis membuat angka di layar tak lagi sama dengan beban sesungguhnya seseorang |
| Kartu dashboard "Ketersediaan Saya" | Sudah tak memuat meteran beban sejak versi sebelumnya |
| CSS mati `.pp-kal-sel/.pp-kal-tgl/.pp-kal-tanda` (`_pita-style.blade.php:61-77`) | Sudah tak dipakai siapa pun (dashboard memakai `.pp-kal-hari` dari `_ketersediaan.blade.php:192-216`), **tapi** membersihkannya adalah pekerjaan lain — jangan dicampur ke diff ini |

### A.6 Risiko & pencegahan

| Risiko | Pencegahan |
|---|---|
| Tanpa kuota, satu SH bisa dibanjiri diam-diam | Justru itu fungsi angka + warna. Cadangannya: `batas_dokumen` tetap ada sebagai rem satu-suntingan |
| `ReviewerAvailabilityTest.php:81-107` **akan merah** — ia mengandalkan default 2 dan `assertSee('Penuh — 2 dokumen berjalan')` | **Jangan hapus test itu.** Tambahkan `config(['smartpro.peninjau.batas_dokumen' => 2]);` di awalnya. Pembatasan masih ada di kode, jadi harus tetap dijaga test |
| `TestCase::peninjauBersih()` (`tests/TestCase.php:32-49`) dibuat khusus menghindari kuota | Dengan kuota mati ia jadi no-op tak berbahaya. Biarkan — mencabutnya menyentuh banyak berkas test tanpa keuntungan |
| Array fallback `:76` tanpa `'tingkat'` → `Undefined array key` | Tambahkan bersamaan dengan perubahan view (sudah dicantumkan di §A.4) |
| Kolom ketiga merusak keselarasan di layar sempit | Media query 767.98px wajib diuji manual pada 375px |
| Pil "padat" oranye tertukar dengan sel **off** yang juga oranye | Beda kolom, beda bentuk (pil vs batang). Bila saat uji manual masih membingungkan, turunkan "padat" ke `badge-soft-secondary` tebal dan sisakan warna hanya untuk "sibuk" |

### A.7 Verifikasi

**Test yang sudah ada — harus tetap hijau**

```
php artisan test --filter=ReviewerAvailabilityTest
```

13 test, termasuk `test_batas_nol_berarti_tanpa_batas` (`:257`) dan
`test_penyetuju_tak_pernah_terkunci` (`:297`).

**Test baru** — tambahkan ke `tests/Feature/ReviewerAvailabilityTest.php`, satu
test yang mengunci ketiga janji fitur ini sekaligus:

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

**Uji manual**

1. Login GL → draft **SOP** → langkah peninjau: angka muncul di kanan pita, **polos**.
2. Bebani satu GL SHE dengan 3 dokumen → draft **JSA**: pil kuning "padat".
   Naikkan ke 5 → pil merah "sibuk", **dan barisnya tetap bisa dipilih**.
3. Peninjau ber-status **off** → tetap terkunci (`disabled`).
4. Section **penyetuju** (SOP/JSA) → **tanpa** kolom angka.
5. Dark mode + lebar 375px.
6. Kirim dokumen ke peninjau yang memegang 5 dokumen → **berhasil terkirim**.

### A.8 Checklist eksekusi

- [ ] `config/smartpro.php` — `batas_dokumen => 0`, tambah `ambang`, perbarui komentar
- [ ] `ReviewerAvailability::untuk()` — kunci `tingkat` + docblock `:45-48` + komentar `:24`
- [ ] `_papan-ketersediaan.blade.php` — fallback `:76`, hapus `@for`, kolom ke-3, header, legenda, kalimat buntu `:142`
- [ ] `_pita-style.blade.php` — grid 3 kolom + aturan mobile
- [ ] `_user_picker.blade.php:33-35` — perbarui komentar
- [ ] `layouts/app.blade.php:1027-1029` — perbarui komentar JS
- [ ] `ReviewerAvailabilityTest.php:81` — pin `batas_dokumen => 2`
- [ ] Test baru (3 janji di §A.7)
- [ ] Jalankan test + 6 langkah uji manual
- [ ] **BERHENTI** — laporkan, tunggu review & commit

---

## FASE B — GL SHE mengalihkan tugas peninjauan JSA (butir 3)

### B.1 Keadaan sekarang

- Peninjau JSA ditentukan `DocumentParticipantResolver::reviewerCandidates()`
  (`app/Services/DocumentParticipantResolver.php:81-90`): GL SHE & Plant
  **kecuali** departemen dokumen itu sendiri, plus SH/DH SHE&Plant bila
  dokumennya memang buatan SHE/Plant.
- `documents.reviewer_id` ditulis hanya di **4 tempat**: `DocumentWizard.php:80`
  dan `:85`, `DocumentController.php:209` (revert pilihan yang ditolak),
  `DocumentService.php:376` (dibawa ke draft revisi).
- **Tak ada satu pun fitur reassign/alihkan/delegate di repo.** Preseden terdekat
  untuk mutasi dokumen berjalan: `DocumentController::withdraw()` (`:545-555`)
  dan `ReviewController::cancelRevision()` (`:144-155`).
- Izin `document.review_jsa` **terpisah** dari `document.review`; batas
  departemen ada di `User::canReviewJsa()` (`app/Models/User.php:197-201`).
  Gate `review-access` di `AppServiceProvider.php:71`.
- Helper audit: `AuditService::log(string $action, ?int $documentId = null, array $meta = [])`
  (`app/Services/AuditService.php:15`).

### B.2 Rancangan

**Rute** — di grup `can:review-access` (`routes/web.php:262`):

```php
Route::post('review/{document}/alihkan', [ReviewController::class, 'alihkan'])
    ->middleware('throttle:10,1')->name('review.alihkan');
```

**Penjagaan** (semua di controller, gaya `withdraw()`):

1. `abort_unless($document->type->code === 'JSA', 422)` — pengalihan **hanya JSA**.
2. `abort_unless($document->reviewer_id === $user->id, 403)` — hanya pemegangnya.
3. `abort_unless(in_array($document->status, ['waiting_for_review','in_review']), 422)`.
4. Tujuan **wajib** ada di `DocumentParticipantResolver::reviewerCandidates($document)`
   dan bukan diri sendiri.

> **Keputusan yang perlu dikonfirmasi saat review Fase B:** daftar tujuan diambil
> **apa adanya** dari resolver — jadi bisa memuat SH/DH SHE/Plant, bukan GL saja.
> Alasannya: itu persis kolam yang sah menerima dokumen ini sejak awal, jadi nol
> permukaan otorisasi baru, dan resolver sudah mengecualikan konflik kepentingan
> (GL departemen dokumen itu sendiri). Bila yang dimaksud hanya GL, satu
> `->filter(fn ($u) => $u->canReviewJsa())` menyelesaikannya.

**Efek:**

- `reviewer_id` → tujuan. Bila status `in_review`, **kembalikan ke
  `waiting_for_review`** agar antrean penerima terbaca benar dan
  `ReviewController::show()` (`:58`) membalikkannya sendiri saat dibuka.
- `alasan` **wajib** (`required|string|max:2000`).
- Audit: `document.reassign_review` dengan meta `['dari','ke','alasan']`.
- Notifikasi — **pola panggilan, BUKAN kelas baru**. `DocumentNotification.php:22-26`
  melarang kelas notifikasi per-peristiwa secara eksplisit:
  penerima (`penting: true`, ikon `bi-arrow-left-right`), pembuat (lonceng saja).
- Tambah satu arm di `DocumentNotification::judulAksi()` (`:114-125`) untuk
  `bi-arrow-left-right` → `'Tugas Peninjauan Dialihkan'`. Tanpa arm ini judul
  emailnya jatuh ke `'Pemberitahuan'`.
- Tambah label aksi baru ke peta timeline: `documents/show.blade.php:9-10+` dan
  `dashboard.blade.php:20-21+`.

**UI** — tombol **"Alihkan"** di kepala `review/show.blade.php`, muncul hanya saat
keempat syarat di atas terpenuhi. Membuka modal berisi:

- **papan ketersediaan yang sama** (`documents.fields._papan-ketersediaan`,
  `memblokir = true`, `ketersediaan = ReviewerAvailability::untuk($kandidat, $document)`)
  — pil beban dari Fase A langsung terpakai, dan peninjau yang off tetap terkunci;
- textarea alasan wajib;
- konfirmasi SweetAlert `data-confirm` sesuai standar UX CLAUDE.md §13.

### B.3 Test — `tests/Feature/PengalihanPeninjauanTest.php`

1. GL SHE pemegang JSA mengalihkan ke GL Plant → `reviewer_id` pindah, status
   kembali `waiting_for_review`, penerima dinotifikasi, audit tercatat.
2. Dokumen **SOP** → 422 (pengalihan hanya JSA).
3. GL yang **bukan** pemegang → 403.
4. Tujuan di luar daftar resolver (mis. GL departemen dokumen itu sendiri) → 422.
5. Tujuan sedang **off** → ditolak.
6. Status `published` → 422.

---

## FASE C — Rombak izin revisi & masukan + menu Log Dokumen (butir 5)

**Fase paling berisiko.** Mencabut izin yang aktif dipakai di produksi. Kerjakan
sendirian, jangan dicampur apa pun.

### C.1 Matriks yang dituju

| Peran | Lihat masukan | Balas masukan | Mulai Revisi | Adopsi masukan |
|---|---|---|---|---|
| **GL** | ✅ se-dept | ✅ | ✅ **hanya dokumen buatannya** | ✅ opsional |
| **MD** | ✅ 7 dept | ✅ | ✅ 7 dept | ✅ opsional |
| **SH/DH** | ✅ se-dept | ❌ | ❌ | ❌ |
| **PJO** | ✅ 7 dept | ❌ | ❌ | ❌ |
| **Non-Staff** | ✅ miliknya | ❌ | ❌ | ❌ |
| **Admin** | ✅ | ✅ | ✅ | ✅ |

### C.2 Titik jebakan yang sudah dipetakan

1. **MD bukan jabatan.** Ia role spatie `management_development` dengan
   `jabatan = NULL` (`UserManagementController.php:148-150`, dikunci
   `ManajemenUserDanPemusnahanTest.php:53-57`). Semua aturan berbasis
   `department_id`/jabatan **tidak berlaku padanya** — MD lolos lewat
   `document.view_all`.
2. **PJO juga memegang `document.view_all`** (`RolePermissionSeeder.php:71-74`).
   Jadi `bisaDibalasOleh()` (`app/Models/DocumentFeedback.php:92-109`) yang
   `return true` lebih awal bagi pemegang `view_all` **hari ini sudah salah** — ia
   memberi PJO hak membalas *dan menutup* masukan tujuh departemen. Fase ini
   **memperbaiki** bug itu, bukan sekadar memindahkannya.
3. **`document.request_revision` mengerjakan LIMA tugas sekaligus:** rute
   `requestRevision`/`cancelRevisionB`/`makeObsolete` (`routes/web.php:251-255`),
   `bisaDiadopsiOleh()` (`DocumentFeedback.php:118-123`), penyaringan masukan di
   `DocumentRevisionController::published()` (`:73-77`), tampil-tidaknya menu
   Distribusi, dan tombol "Arsipkan" di daftar Tidak Berlaku.
   **Pecah hanya yang benar-benar berpisah** — dua izin, bukan lima.

### C.3 Perubahan izin — `database/seeders/RolePermissionSeeder.php`

```
document.request_revision   dicabut dari : section_head, departemen_head, pimpinan
                            diberikan ke : group_leader, management_development
                            (admin sudah memegang seluruh izin)

document.feedback_respond   IZIN BARU
                            diberikan ke : group_leader, management_development
```

Tambah `'document.feedback_respond'` ke daftar `$permissions` (`:41-55`);
keluarkan `'document.request_revision'` dari `$headPerms` (`:61-65`) dan dari blok
`ROLE_PIMPINAN` (`:71-74`); tambahkan keduanya ke `ROLE_GROUP_LEADER` (`:76-84`)
dan `ROLE_MD` (`:95-104`). Perbarui komentar GL (`:76-83`) yang saat ini berbunyi
"TIDAK mengajukan revisi / menonaktifkan dokumen" — kalimat itu jadi terbalik.

**Migrasi izin & rollback.** Seeder idempoten (`syncPermissions` per role,
`:116-118`, `forgetCachedPermissions()` di `:39`). Jalankan
`php artisan db:seed --class=RolePermissionSeeder`. Rollback = `git revert` seeder
lalu jalankan ulang perintah yang sama. **Tulis itu di pesan commit.**

### C.4 Perubahan model

**`app/Models/Document.php` — method baru:**

```php
/**
 * Boleh memulai revisi Tipe B atas dokumen ini?
 *
 * GL = penyusunnya, jadi ia hanya boleh merevisi yang DIBUATNYA (keputusan D1).
 * MD & Admin lolos lewat view_all — MD memang bertugas lintas 7 departemen.
 * Pembuat tambahan sengaja TIDAK ikut: ia baris "Dibuat Oleh" tambahan di
 * lembar pengesahan, bukan pemilik dokumen.
 */
public function bisaDirevisiOleh(User $user): bool
{
    return $user->can('document.request_revision')
        && $this->status === 'published'
        && ($user->can('document.view_all') || $this->created_by === $user->id);
}
```

**`app/Models/DocumentFeedback.php` — ganti dua predikat:**

```php
public function bisaDibalasOleh(User $user): bool
{
    if (! in_array($this->status, ['baru', 'dibaca'], true)) return false;
    if (! $user->can('document.feedback_respond')) return false;      // ← SH/DH & PJO berhenti di sini
    if ($user->can('document.view_all')) return true;                 // MD & Admin: 7 dept
    return $this->document?->department_id === $user->department_id;  // GL: dept sendiri
}

public function bisaDiadopsiOleh(User $user): bool
{
    return $this->bisaDibalasOleh($user)
        && (bool) $this->document?->bisaDirevisiOleh($user);
}
```

Blok jabatan lama (`:104-109`) dibuang seluruhnya — izin sudah mewakilinya.
Perbarui docblock `:78-91` yang menerangkan aturan lama.

### C.5 Perubahan alur revisi

**`DocumentRevisionController::requestRevision()` (`:112-148`):**

- Validasi `alasan` jadi **`required`** (D3); buang `required_without:masukan`
  beserta pesan `alasan.required_without`. `masukan` tetap opsional (D2).
- Tambah penjagaan per-dokumen: `abort_unless($document->bisaDirevisiOleh($user), 403)`.
  Middleware rute (`can:document.request_revision`) menjaga **peran**; ini menjaga
  **dokumen mana**.
- Sama untuk `cancelRevisionB()` (`:154`).

**`DocumentFeedbackService::ajukanRevisi()` (`:105-127`)** — setelah `Review`
`[Pengaju Revisi]` dibuat, **kabari SH/DH dept + MD** (D3):

```php
Notification::send(
    $this->peserta->heads([$document->department_id])->merge(User::peninjauMd()->get()),
    new DocumentNotification(
        $new,
        "{$pengaju->name} mengajukan revisi {$document->displayNumber()}: {$alasanFinal}",
        'bi-arrow-repeat',
        'documents.revisions',
    ),
);
```

> `User::peninjauMd()` (`User.php:127-141`) memilih lewat **peran**, bukan izin —
> sengaja, agar Admin (pemegang semua izin) tak ikut terjaring. Docblock-nya
> menjelaskan pembagian itu; jangan dibalik.

**Bug ikutan yang wajib diperbaiki di fase ini:** `_modal-revisi.blade.php:16`
menjanjikan `{{ $document->no_revisi + 1 }}`, padahal `Document::revisiSaatKirim()`
(`:473-484`) berguling di revisi 4 → Edisi+1 Revisi 0
(`DocumentService::nextEditionRevision`, `:149-152`). Pada dokumen revisi 4 modal
menjanjikan "Revisi ke-5" yang tak pernah ada. Ganti dengan `revisiSaatKirim()`.

### C.6 Perubahan view

**`resources/views/documents/published.blade.php`**

- Tombol revisi: `@can('document.request_revision')` →
  `@if ($doc->bisaDirevisiOleh(auth()->user()))`, label **"Revisi"** — satu label
  untuk semua yang berwenang; "Ajukan" tak lagi tepat karena GL memang
  mengerjakannya sendiri.
- Tombol **"Tidak Berlaku"** (`documents.makeObsolete`) menjadi **"Ajukan
  Nonaktif"** di Fase F. Untuk Fase C **biarkan apa adanya**; ia otomatis ikut
  berpindah tangan ke GL/MD karena izinnya sama. Catat sebagai konsekuensi yang
  disengaja, dan sebutkan di laporan akhir fase.
- Header copy (`:12-16`) diperbarui mengikuti matriks baru.
- Penyaringan masukan di `DocumentRevisionController::published()` (`:73-77`) —
  ganti kondisi "lihat semua masukan" dari `can('document.request_revision')` ke
  **`$user->dashboardPenuh()`** (= semua kecuali Non-Staff; helper sudah ada di
  `User.php:227`). **Tanpa ini SH/DH & PJO kehilangan hak *melihat* yang justru
  masih mereka punya.**
- Form balasan di `_masukan-list` hanya dirender bila `bisaDibalasOleh()` — sudah
  begitu; tinggal pastikan tak ada tombol yang lolos di luar predikat itu.

**Menu Distribusi** di sidebar (`layouts/app.blade.php`, blok `berlakuMenu`) hari
ini `@if ($user->can('document.request_revision'))`. Setelah izinnya pindah,
SH/DH & PJO — justru pihak yang menegur cakupan rendah — kehilangan menunya.
**Ganti ke `$user->dashboardPenuh()`.** Sama untuk
`DocumentDistributionController::distribution()` (`:27`) dan tombol "Lihat semua"
di `partials/_widget-distribusi.blade.php:29-33`.

### C.7 Menu baru — "Log Dokumen" (D5)

Dropdown sidebar di bawah "Dokumen Berlaku", Alpine key `logMenu`, ikon
`bi-journal-text`. Tampil bagi `$user->dashboardPenuh()`.

Controller baru: **`app/Http/Controllers/DocumentLogController.php`** (~180 baris).

#### Submenu 1 — Masukan Lapangan · `GET /log-dokumen/masukan` · `log.masukan`

Semua masukan dalam satu halaman, menggantikan tempelan yang tercecer.

| Kolom | Isi |
|---|---|
| No. Masukan | `feedback_number` (MSK-2026-0001) |
| Dokumen | `displayNumber()` + judul, menaut ke `documents.show` |
| Isi | dipotong 120 karakter, buka penuh via `<details>` |
| Pengirim | nama + `jabatanShort()` |
| Tanggal | `created_at` WITA |
| Status | badge dari `DocumentFeedback::STATUS_LABELS` |
| Aksi | **Balas** (modal, bila `bisaDibalasOleh`) · **Revisi** (bila `bisaDiadopsiOleh`) |

- Tombol **Revisi** membuka `_modal-revisi` milik dokumen itu **dengan masukan ini
  sudah tercentang** — memakai ulang modal yang sudah ada, bukan form kedua.
  Jadi tombol Revisi hidup di **dua** tempat: Dokumen Berlaku dan halaman ini.
- Filter: status, departemen (hanya `view_all`), `q` pada isi/nomor.
- Lingkup: Non-Staff → `user_id = auth()->id()`; GL/SH/DH → `department_id`
  dokumen; MD/PJO/Admin → semua. Pakai `Document::scopeTerlihatOleh` lewat
  `whereHas('document', …)` — jangan menulis pagar departemen kedua.
- Kartu dashboard `_widget-masukan` **tetap ada**; tombol "Lihat semua"-nya
  menaut ke halaman ini.

#### Submenu 2 — Alasan Dokumen · `GET /log-dokumen/alasan` · `log.alasan`

Menampung setiap alasan yang pernah dikirim & diterima atas sebuah dokumen.
Sumbernya **sudah ada semua**, hanya belum pernah dibaca di satu tempat:

| Tahap | Sumber | Penanda |
|---|---|---|
| Pengajuan revisi | `reviews.summary` | awalan `[Pengaju Revisi]` |
| Ditolak peninjau | `reviews.summary` (`decision = needs_revision`) | tanpa awalan |
| Ditolak MD | `reviews.summary` | awalan `[Management Development]` |
| Ditolak penyetuju | `reviews.summary` + `approvals.comment` | awalan `[Penyetuju]` |
| Pengalihan JSA (Fase B) | `audit_logs.meta['alasan']` | aksi `document.reassign_review` |
| Nonaktif & penolakannya (Fase F) | `approvals.comment` (`kind = 'nonaktif'`) | — |

Kolom: Tanggal · Dokumen · Tahap · Oleh · Alasan (potong + buka) · → dokumen.
Filter: tahap, departemen, rentang tanggal, `q`.

**Implementasi:** JANGAN membuat tabel `document_logs` baru maupun VIEW union.
Tiga query terpisah (`reviews`, `approvals`, `audit_logs`) di-`merge()`,
di-`sortByDesc('created_at')`, lalu dibungkus `LengthAwarePaginator` manual.

```php
// ponytail: memindai maks 500 baris terbaru per sumber lalu memaginasi di PHP.
// Cukup untuk volume internal ini; kalau kelak melambat, ganti dengan satu
// VIEW SQL `document_alasan` (UNION ALL) dan paginasi di database.
```

### C.8 Test

**Berkas baru `tests/Feature/IzinRevisiMasukanTest.php`** — kunci matriks §C.1
utuh: untuk tiap peran (GL pemilik, GL bukan pemilik, MD, SH, DH, PJO, Non-Staff,
Admin) periksa keempat kemampuan.

**Berkas baru `tests/Feature/LogDokumenTest.php`** — halaman masukan & alasan:
lingkup per peran, tombol Revisi hanya muncul bagi yang berwenang, alasan dari
`reviews` + `approvals` + `audit_logs` semuanya tampil.

**Yang AKAN merah dan wajib diperbarui:**

- `tests/Feature/DocumentFeedbackTest.php` (14 test) — sebagian besar berasumsi
  SH boleh membalas & mengadopsi.
- `tests/Feature/Api/*` yang menyentuh `api.feedback.balas` / `api.feedback.adopsi`
  (`Api/FeedbackApiController.php:74,102`).
- `tests/Feature/RevisionTypeBTest.php`, `ApproverRevisionTest.php` — pengaju
  revisinya SH.
- `tests/Feature/DashboardWidgetTest.php` — widget masukan.

Jalankan **seluruh** suite di fase ini, bukan `--filter`.

---

## FASE D — Distribusi dokumen untuk menu Informasi (butir 4)

### D.1 Kendala yang harus dijawab lebih dulu

1. Tabel `informasi` (`app/Models/Informasi.php`, migrasi
   `2026_08_12_100000_create_informasi_table.php`) **tak mencatat siapa membaca**
   sama sekali. Yang ada hanya `audit_logs` untuk create/perbarui/destroy.
2. Tabel `informasi` **tak punya `department_id`** — informasi memang lintas 7
   dept. Jadi "sasaran" tak bisa disalin mentah dari
   `DocumentDistribution::sasaran()` (`app/Services/DocumentDistribution.php:84-91`)
   yang memakai `department_id` dokumen.

Keputusan C1 menjawab keduanya: cakupan **dipecah per departemen**, dan PJO/MD
melihat gabungan seluruh pengguna.

### D.2 Perubahan

**(1) Migrasi `informasi_reads`** — meniru `document_reads`
(`2026_08_05_100000_create_document_reads_table.php`): `informasi_id` (cascade),
`user_id`, `first_read_at`, `last_read_at`, `read_count`, `download_count`,
`platform`, `unique(informasi_id, user_id)`, `index(informasi_id, last_read_at)`,
`$timestamps = false`.

**(2) Model `app/Models/InformasiRead.php`** — salinan `DocumentRead`.

**(3) Pencatat baca** di `InformasiController::file()` (`:230`) — satu panggilan
`catat()` sebelum stream. Rute API menumpang method web yang sama
(`routes/api.php:110-111`), jadi mobile ikut tercatat gratis; platform dideteksi
`$request->user()?->currentAccessToken() ? 'mobile' : 'web'`.

**(4) Service baru `app/Services/InformasiDistribution.php`**

```php
catat(Informasi $i, ?User $u, bool $unduh = false, string $platform = 'web'): void
cakupan(Collection $informasi, ?int $departmentId = null): array  // bentuk SAMA dgn DocumentDistribution
rincian(Informasi $i): array                                      // ['per_dept' => [...], 'sudah' => …, 'belum' => …]
```

- `sasaran`: pengguna `status = 'active'` (minus pengunggah), disaring
  `department_id` bila `$departmentId` diisi.
- **Bentuk kembalian `cakupan()` wajib identik** dengan
  `DocumentDistribution::cakupan()` (`:103-142`): kunci `pembaca`, `sasaran`,
  `persen`, `unduhan`. Dengan begitu `partials/_pita-cakupan.blade.php` dan
  `_widget-distribusi.blade.php` dipakai ulang **tanpa perubahan logika**.
- Kelas terpisah, bukan parameter di `DocumentDistribution`: sumber tabelnya beda
  dan definisi sasarannya beda. Menggabungkannya berarti dua `if` di setiap method.

**(5) Widget bisa di-switch** — `partials/_widget-distribusi.blade.php`

- Header dapat `btn-group` dua tombol: **Dokumen Mutu | Informasi**.
- **Render KEDUA panel server-side, toggle dengan Alpine `x-show`** — nol request
  tambahan, nol kedipan. Datanya murah (2 grouped query per sumber, sudah bebas N+1).
- `DashboardController::distribusiWidget()` (`:450-486`) mengembalikan
  `['mutu' => [...], 'informasi' => [...]]`; separuh `informasi` di-`null`-kan
  bila kosong.
- `$deptId` yang dipakai: `$user->can('document.view_all') ? null : $user->department_id`.

**(6) Halaman penuh** `resources/views/documents/distribution.blade.php` dapat
switch yang sama lewat `?sumber=mutu|informasi` — **satu rute, satu percabangan**
di `DocumentDistributionController::distribution()`. Untuk `sumber=informasi` dan
pengguna `view_all`, halaman rincian menampilkan **tabel per departemen** (7 baris).

**(7) API** — `Api/KendaliApiController::distribusi()` (`:179-216`) dapat
parameter `?sumber=informasi`; bentuk JSON-nya sama persis, jadi aplikasi
terpasang tak perlu tahu apa-apa sampai ia memintanya.

**(8) Gerbang akses:** halaman & widget → `$user->dashboardPenuh()` (sudah
diseragamkan di Fase C.6).

**Yang tidak dilakukan:** Chart.js sudah termuat di `dashboard.blade.php:526`,
tapi widget ini dirender **server-side tanpa chart library** dan tetap begitu.
Jangan menambah dependensi front-end.

### D.3 Test — `tests/Feature/DistribusiInformasiTest.php`

1. Membuka berkas informasi mencatat satu baris; membukanya lagi menaikkan
   `read_count` **tanpa** menambah baris (idempoten per pasangan).
2. Pengunggah sendiri tak dihitung sebagai pembaca.
3. GL/SH melihat cakupan **departemennya saja**; PJO melihat gabungan + rincian 7 dept.
4. Widget dashboard memuat kedua panel; Non-Staff tak melihat widget sama sekali.
5. `cakupan()` mengembalikan `persen = 0` dan **tidak** membagi nol saat sasaran kosong.

---

## FASE E — Daftar Tidak Berlaku berkelompok (butir 6)

### E.1 Keadaan sekarang

`DocumentRevisionController::obsolete()` (`:214-238`) mengembalikan daftar
**datar** semua baris `status = 'obsolete'`, paginate 15. Satu dokumen dengan 5
revisi = 5 baris. Kolom sekarang (`documents/obsolete.blade.php:43`): No. Dokumen
· Judul · Jenis · Dept · Edisi · Revisi · Pembuat · Aksi.

Kunci pengelompokan yang benar: **`doc_number`** — `ApprovalController::store()`
(`:93-121`) sengaja mewariskan `doc_number` yang sama ke versi baru pada revisi
Tipe B, jadi seluruh versi satu dokumen berbagi nilai itu.

### E.2 Rancangan

**Query induk** — hanya versi terbaru per `doc_number`:

```php
// ponytail: id terbesar = versi terbaru, karena draft revisi SELALU dibuat
// sesudah induknya (DocumentService::requestRevision). Kalau kelak ada impor
// massal yang mengacak urutan id, ganti dengan
//   ROW_NUMBER() OVER (PARTITION BY doc_number
//                      ORDER BY CAST(edisi AS UNSIGNED) DESC, no_revisi DESC).
$induk = Document::where('status', 'obsolete')
    ->whereIn('id', fn ($q) => $q->selectRaw('MAX(id)')->from('documents')
        ->where('status', 'obsolete')->whereNull('deleted_at')->groupBy('doc_number'))
    ->…->paginate(15);
```

Lalu **satu** query kedua mengambil seluruh versi untuk `doc_number` yang tampil
di halaman ini, `->get()->groupBy('doc_number')`. Nol N+1.

**Blade `documents/obsolete.blade.php`:**

- Baris induk = `<tr>` seperti sekarang, ditambah tombol chevron di kolom pertama
  (`x-data="{ buka:false }"`, `:aria-expanded="buka"`, ikon `bi-chevron-right` →
  `bi-chevron-down`) dan badge kecil `N versi`.
- Baris versi = `<tr x-show="buka" x-collapse><td colspan="8">` berisi sub-tabel:
  **Edisi · Revisi · Tanggal nonaktif (`updated_at`) · Sebab · PDF · Lihat**
  (+ **Rollback** di Fase G).
- **Grup berisi 1 versi → tanpa chevron.** Dokumen yang dimatikan (Fase F) memang
  selalu tunggal, dan itu benar.
- Sorting `urut()` tetap berlaku, tapi hanya pada baris **induk**; urutan di dalam
  dropdown selalu Edisi↓ Revisi↓.
- Filter `q` & `department_id` dipertahankan apa adanya. Tombol Aktifkan /
  Arsipkan / Musnahkan (`:58-100`) pindah ke baris **induk**.
- **Aksesibilitas:** chevron adalah `<button>` sungguhan dengan `aria-controls`,
  bukan `<a href="#">`.

### E.3 Test — `tests/Feature/DaftarNonaktifTest.php`

1. Dokumen dengan 3 versi obsolete → halaman menampilkan **1** baris induk, dan
   induknya adalah edisi/revisi tertinggi.
2. Dropdown memuat ketiga versi, terurut Edisi↓ Revisi↓.
3. Dokumen dengan 1 versi → tanpa chevron.
4. Paginasi menghitung **grup**, bukan baris (15 grup per halaman).
5. Dua dokumen dengan `doc_number` berbeda tak pernah tercampur dalam satu grup.

---

## FASE F — Nonaktif berjenjang + pelepasan nomor (butir 8)

### F.1 Keadaan sekarang

`DocumentRevisionController::makeObsolete()` (`:201-210`) adalah **sekali klik**:
satu izin, satu `update`, selesai. Tak ada snapshot, tak ada pemindahan masukan.
Nomornya melekat selamanya karena `DocumentNumberService::generateFinal()`
(`:67-79`) membaca `withTrashed()` seluruh `doc_number_final` sebagai kolam terpakai.

### F.2 Alur baru (F1)

```
GL mengajukan   →  SH/DH dept  →  MD  →  PJO  →  Tidak Berlaku + nomor dilepas
MD mengajukan   →  SH/DH dept  →  PJO         →  Tidak Berlaku + nomor dilepas
                        ↓ tolak (alasan wajib, di tahap mana pun)
                   kembali Berlaku
```

### F.3 Pemodelan — ikuti preseden `verifikasi_md`, jangan bikin mesin alur

Migrasi `2026_08_01_093000_add_pending_md_status_to_documents.php:18-24`
menuliskan alasannya eksplisit: *"Satu status sudah memodelkannya dengan jujur,
dan tidak membongkar ReviewController/ApprovalController/Resolver beserta 83 test
yang sudah hijau."* Ikuti itu.

**Migrasi 1 — status baru.** Pola TIGA LANGKAH dari
`2026_08_03_090000_rename_pending_md_to_verifikasi_md.php` (MySQL menolak enum
yang tak memuat nilai yang sedang dipakai baris mana pun): tambah
`'menunggu_nonaktif'` ke enum `documents.status`. `down()` memindahkan barisnya
kembali ke `published` **dulu**, baru menciutkan enum.

**Migrasi 2 — kolom pendamping pada `documents`:**

| Kolom | Tipe | Guna |
|---|---|---|
| `nonaktif_tahap` | `enum('sh','md','pjo')` nullable | tahap yang sedang berjalan |
| `nonaktif_oleh` | `foreignId` nullable | inisiator (menentukan apakah tahap MD ikut) |
| `obsolete_reason` | `enum('revisi','dinonaktifkan')` nullable, index | **F2** + dibutuhkan Fase E & G |

Sertakan **backfill** di migrasi ini: seluruh baris `status = 'obsolete'` yang
sudah ada → `obsolete_reason = 'revisi'`.

> **Satu status + satu kolom tahap, bukan tiga status.** Ketiga tahap muncul di
> **satu** menu yang sama dan disaring oleh siapa yang membuka. Tiga status berarti
> tiga nilai enum, tiga label, tiga entri `STATUS_META`, dan percabangan di setiap
> penyaring status yang sudah ada.

**Migrasi 3 — `approvals.kind`:** `enum('pengesahan','nonaktif') default 'pengesahan'`.
Jejak keputusan nonaktif ditulis sebagai baris `approvals` biasa (`approver_id`,
`decision`, `comment`, `signed_at`) — **tabel yang sudah ada, nol tabel baru** —
dan halaman Alasan Dokumen (§C.7) langsung membacanya gratis. `kind` menjaga
`$document->approvals()` (jejak pengesahan) tidak tercemar.

**`Document::STATUS_LABELS` (`:30-44`) & `STATUS_META` (`:67-89`)** tambah
`'menunggu_nonaktif' => 'Menunggu Nonaktif'`, warna kuning-tua, ikon `bi-slash-circle`.

### F.4 Pelepasan nomor (F2)

Tiga sentuhan, masing-masing satu klausa:

1. **`DocumentNumberService::generateFinal()` (`:67-79`)** — keluarkan baris
   `obsolete_reason = 'dinonaktifkan'` dari kolam terpakai. Sejak itu
   `firstUnusedSeq()` (`:114-123`, yang memang memilih terkecil-belum-terpakai)
   memberikan nomor itu kepada dokumen baru berikutnya.
2. **`DocumentNumberService::isUnique()` (`:144-152`)** — abaikan juga baris yang
   nomornya sudah dilepas. **Tanpa ini** entri manual atas nomor yang baru dilepas
   akan ditolak, padahal itu justru tujuan pelepasannya.
3. **`DocumentRevisionController::restoreObsolete()` (`:249-279`)** — untuk
   dokumen `obsolete_reason = 'dinonaktifkan'`, `nomor_baru` jadi **wajib** (bukan
   pilihan), dan `obsolete_reason` dikosongkan saat aktif kembali.

`nomorMasihDipakai()` (`:288-300`) sudah hanya menghitung `berlaku()`, jadi ia
sudah benar tanpa perubahan.

**Baris dokumen tetap memegang `doc_number_final` lamanya** supaya arsip tetap
terbaca. Di daftar Tidak Berlaku, tampilkan nomor itu **dicoret** + badge
"Nomor dilepas". Dua dokumen bisa sah memegang nomor final yang sama — itulah
yang membuat langkah 2 wajib, bukan opsional.

### F.5 Controller & rute baru

**`app/Http/Controllers/NonaktifController.php`** (~170 baris — jangan
ditambahkan ke `DocumentController` yang sudah 574 baris, CLAUDE.md §3).

| Method | Rute | Penjagaan |
|---|---|---|
| `ajukan(Request, Document)` | `POST documents/{document}/ajukan-nonaktif` | `bisaDirevisiOleh()` (GL pemilik / MD / Admin) + `status === 'published'` + alasan wajib |
| `index(Request)` | `GET nonaktif` → `nonaktif.index` | tampil bila pengguna adalah kemungkinan penyetuju salah satu tahap |
| `putuskan(Request, Document)` | `POST nonaktif/{document}` | tahap sekarang harus cocok dengan peran pemutus |

Pemetaan tahap → siapa yang berwenang:

```
'sh'  => SH/DH departemen dokumen   (document.review + department_id cocok)
'md'  => document.review_md         (DILEWATI bila inisiatornya MD)
'pjo' => document.approve + hasRole(pimpinan)
```

- **Approve** → maju ke tahap berikut; bila tahap terakhir: `status = 'obsolete'`,
  `obsolete_reason = 'dinonaktifkan'`, `nonaktif_tahap = null`.
- **Reject** → `status = 'published'`, `nonaktif_tahap = null`, alasan **wajib**,
  ditulis sebagai `approvals` `kind = 'nonaktif'` `decision = 'rejected'`,
  inisiator dinotifikasi.
- Dokumen `sedang_direvisi` **tak boleh** diajukan nonaktif — revisinya sedang berjalan.

Rute lama `documents.makeObsolete` (`routes/web.php:254`) **dihapus** beserta
tombolnya di `published.blade.php:117-159`. Diganti "Ajukan Nonaktif" + modal alasan.

**Menu sidebar:** sub-item baru **"Persetujuan Nonaktif"** di bawah dropdown
"Dokumen Berlaku", dengan lencana angka antrean. Tampil bila pengguna berwenang
di salah satu dari ketiga tahap.

**Notifikasi:** tiap perpindahan tahap mengabari penyetuju berikutnya
(`bi-slash-circle`, `penting: true`); keputusan akhir mengabari inisiator +
pembuat. Tambah arm `judulAksi()` untuk ikon itu.

**Audit:** `document.nonaktif_ajukan`, `document.nonaktif_setuju`,
`document.nonaktif_tolak`, `document.nonaktif_selesai`. Tambahkan ke peta label
timeline (`documents/show.blade.php`, `dashboard.blade.php`).

### F.6 Test — `tests/Feature/NonaktifBerjenjangTest.php`

1. GL mengajukan → tahap `sh`; SH approve → `md`; MD approve → `pjo`; PJO approve
   → `obsolete` + `obsolete_reason = 'dinonaktifkan'`.
2. **MD mengajukan → tahap MD dilewati** (`sh` → `pjo`).
3. Tolak di tahap mana pun → kembali `published`, alasan tersimpan di `approvals`
   `kind = 'nonaktif'`, inisiator dinotifikasi.
4. GL mengajukan dokumen **buatan GL lain** → 403.
5. SH departemen **lain** memutuskan → 403.
6. **Nomor dilepas:** dokumen `-03` dinonaktifkan → dokumen baru jenis+dept yang
   sama mendapat `-03`.
7. **Aktifkan kembali** dokumen yang nomornya dilepas → **wajib** nomor baru,
   `obsolete_reason` kosong.
8. Dokumen `sedang_direvisi` → 422.
9. Nonaktif hasil **revisi** (`ApprovalController`) tetap `obsolete_reason = 'revisi'`
   dan **tidak** melepas nomor.

> `ApprovalController::store()` (`:138-165`), yang menjadikan versi lama obsolete
> saat revisi disahkan, **harus ikut menulis `obsolete_reason = 'revisi'`**.
> Jangan sampai terlewat — tanpa itu obsolete baru bernilai `null` dan Fase E/G
> tak bisa membedakannya dari dokumen yang dimatikan.

---

## FASE G — Rollback ke versi revisi sebelumnya (butir 7)

### G.1 Alur yang ditetapkan (G1)

```
[Daftar Tidak Berlaku] versi lama → klik Rollback
        ↓
draft revisi BARU milik GL pembuat asli, isinya salinan versi lama
        ↓  (muncul di menu "Dokumen Revisi")
GL mengisi langkah Log Revisi — catatannya = alasan rollback
        ↓
Kirim → alur pengesahan NORMAL: SH/DH → MD (SOP) → PJO
        ↓
Berlaku dengan nomor revisi TERBARU
        ↓
dokumen yang tadinya Berlaku → Tidak Berlaku, jadi induk grup versi
versi yang di-rollback → TETAP Tidak Berlaku
```

**Tidak ada gerbang persetujuan terpisah.** Itulah yang membuat fase ini murah:
seluruh pengesahan memakai jalur yang sudah teruji.

### G.2 Kenapa hampir tak ada kode baru

- `DocumentService::requestRevision()` sudah membuat draft revisi lengkap:
  `revises_document_id`, snapshot `document_versions` (`:357-382`), membawa
  `reviewer_id`/`approver_id`/pembuat tambahan (`:376`), dan menjadikan dokumen
  sumber `sedang_direvisi`.
- `Document::usesRevisionLog()` (`:449-452`) bernilai `true` karena
  `revises_document_id` terisi → **langkah Log Revisi otomatis muncul**, tanpa
  satu baris pun.
- `Document::revisiSaatKirim()` (`:473-484`) menaikkan nomor revisi saat DIKIRIM,
  lengkap dengan penjagaan roll-over Edisi. Benar apa adanya.
- `ApprovalController::store()` (`:138-165`) sudah menjadikan versi lama obsolete
  saat versi baru disahkan, dan memindahkan masukan yang belum ditindak.

### G.3 Yang perlu ditulis

**`DocumentService::rollbackDraft(Document $versiLama, User $pemohon): Document`**

```php
// Versi berlaku dari dokumen yang sama (kunci grup = doc_number).
$berlaku = Document::where('doc_number', $versiLama->doc_number)
    ->where('status', 'published')->firstOrFail();

$draft = $this->requestRevision($berlaku, $pemohon);   // ← jalur yang sudah teruji

// Lalu TIMPA isinya dengan isi versi lama. Dua langkah, bukan satu jalur baru:
// requestRevision() sudah benar soal nomor, peserta, snapshot, dan status induk;
// yang khas rollback hanyalah "isinya dari mana".
foreach ($versiLama->contents as $c) {
    $this->saveSection($draft, $c->section_key, $c->value_json);
}
```

Kembalikan `$draft`. Audit `document.rollback` dengan meta
`['dari_versi' => $versiLama->id, 'edisi' => …, 'no_revisi' => …]`.

**Prefill catatan revisi:** isi section `catatan_revisi` draft dengan satu baris
awal `catatan = "Rollback ke Edisi {X} Revisi {Y}. "` supaya GL tinggal
melanjutkan alasannya. Baris ini bisa disunting/dihapus GL seperti baris lain.

**Rute & tombol:**

```php
Route::post('documents/{document}/rollback', [DocumentRevisionController::class, 'rollback'])
    ->middleware('can:document.request_revision')->name('documents.rollback');
```

Penjagaan di controller:

1. `$document->status === 'obsolete'` **dan** `obsolete_reason === 'revisi'` —
   dokumen yang **dimatikan** tak bisa di-rollback; ia dimatikan dengan sengaja
   lewat tiga persetujuan, jadi jalan kembalinya adalah "Aktifkan", bukan rollback;
2. ada saudara `published` dengan `doc_number` sama, dan saudara itu
   `bisaDirevisiOleh($user)` — inilah yang menegakkan "GL hanya dokumen buatannya";
3. tak ada draft revisi yang sedang berjalan atas dokumen itu.

Tombol **Rollback** muncul di **baris versi** dalam dropdown Fase E, dengan
konfirmasi SweetAlert yang menyebut tujuannya: *"Isi Edisi X Revisi Y akan
disalin menjadi draft revisi baru. Dokumen yang berlaku sekarang tetap berlaku
sampai revisi ini disahkan."*

**Notifikasi:** GL pembuat asli (`bi-arrow-counterclockwise`, `penting: true`) —
ia yang akan mengerjakan draftnya, dan bisa jadi bukan dia yang menekan tombol.

### G.4 Test — `tests/Feature/RollbackVersiTest.php`

1. Rollback versi Edisi 1 Revisi 2 → draft baru milik GL pembuat, isinya sama
   persis dengan versi itu, muncul di `documents.revisions` miliknya.
2. Dokumen berlaku jadi `sedang_direvisi`, **belum** obsolete.
3. Setelah draft dikirim & disahkan: draft jadi `published` dengan revisi
   berikutnya; dokumen yang tadinya berlaku jadi `obsolete` +
   `obsolete_reason = 'revisi'`; **versi yang di-rollback tetap obsolete**.
4. Daftar Tidak Berlaku menampilkan **satu** grup berisi semua versi.
5. Rollback dokumen `obsolete_reason = 'dinonaktifkan'` → 422.
6. GL merollback dokumen buatan GL lain → 403.
7. Rollback saat sudah ada draft revisi berjalan → 422.

---

## FASE H — Catatan revisi di awal alur dokumen existing (butir 9)

### H.1 Lingkup — dipersempit dan sudah terjawab

Butir ini **hanya** untuk **dokumen lama yang diunggah** (jalur
`documents.arsip.store`, saklar "dokumen lama" di
`resources/views/documents/create.blade.php:51-57`) dan **hanya jenis SOP, SP, IK**.
FK/PX (kelas `unggahan`) tak ikut — keduanya memang tak pernah punya bab, cover,
maupun lembar revisi.

Ini menjawab pertanyaan terbuka v5 §5: bacaan **(b)**, bukan saklar environment.

### H.2 DEPENDENSI BARU — butuh persetujuan terpisah sebelum fase ini dimulai

DomPDF hanya **membuat** PDF; ia tak bisa membaca atau memotong PDF yang sudah
ada. Tak ada satu pun pustaka PDF-reader di `composer.json`.

```
setasign/fpdi   ^2.6   MIT   ± 1 MB     murni PHP, tanpa ekstensi baru
setasign/fpdf   ^1.8   MIT   ± 100 KB   kanvas penggabung saja, tak menggambar apa pun
```

- FPDI **v2** sudah mendukung PDF 1.5+ (cross-reference stream). Yang tetap gagal
  hanya PDF **terenkripsi** → tangkap `PdfParserException`, tolak dengan pesan
  jelas, **berkas asli tak disentuh**.
- Berkas hasil gabung disajikan `app/Services/Print/ArsipPdf::sajikan()` (stream
  mentah), **bukan** jalur render DomPDF. Konsekuensinya: **golden test PDF
  (`tests/fixtures/golden/SOP.json`, `SP.json`, `IK.json`), `CoverPageTest`, dan
  `PrintLayoutTest` sama sekali tak tersentuh.** Ini menghapus risiko terbesar
  yang dulu ditulis di v5 §F.

### H.3 Alur baru

**Langkah 0** — form unggah yang ada sekarang, tanpa perubahan. Bedanya: untuk
SOP/SP/IK arsip, `DocumentArsipController::store()` mengarahkan ke halaman baru
`documents.arsip.catatan` alih-alih langsung ke `documents.published`.

**Langkah 1 — Catatan Revisi + pratinjau berdampingan** ·
`resources/views/documents/arsip/catatan.blade.php`

```
┌──────────────────────────────┬─────────────────────────────┐
│ Form Catatan Revisi          │  ← PDF yang baru diunggah   │
│  Edisi [ 2 ]  Revisi [ 1 ]   │    (iframe, halaman penuh)  │
│  Halaman awal lembar [ 1 ]   │                             │
│  Potong halaman awal [ 1 ]   │   navigasi halaman ← →      │
│  ─────────────────────────   │   supaya user MELIHAT       │
│  NO │ NO.REV │ TGL │ HAL │ … │   halaman mana yang dibuang │
└──────────────────────────────┴─────────────────────────────┘
```

- Kiri: pakai ulang `documents/fields/_revision_log.blade.php` (`:49-73`) — nama
  field `sections[catatan_revisi][i][no_rev|tanggal|halaman|catatan]` persis sama.
- Tambahan khusus arsip (H1): **`potong_halaman`** (0–10, default 1; **0 = tidak
  memotong**), **`halaman_awal`** (nomor halaman yang dicetak di lembar sisipan),
  serta Edisi & Revisi yang sudah ada di partial itu.
- Kanan: `<iframe>` menunjuk berkas unggahan — pola persis dari
  `edit.blade.php:161-196` (src ditulis di HTML bukan oleh JS, placeholder di
  bawah iframe transparan, nol kedipan).

**Langkah 2 — dua pilihan**

| Pilihan | Yang terjadi |
|---|---|
| **"Cukup unggahan + lembar revisi baru"** | DomPDF merender `documents/print/_catatan_revisi.blade.php` → PDF sementara. FPDI mengimpor lembar itu + halaman `potong_halaman+1 … akhir` dari berkas asli → berkas **baru**. `arsip_path` menunjuk hasil gabung; **`arsip_path_asli` menyimpan berkas asli utuh**. Dokumen langsung Berlaku, seperti sekarang. |
| **"Salin seluruh dokumen ke web"** | Dibuatkan draft wizard sungguhan (jenis, dept, nomor sama), section `catatan_revisi` diisi dari form ini, lalu diarahkan ke `documents.edit`. Panel kanan `edit.blade.php` diberi **dua tab: `Referensi` (PDF unggahan) dan `Pratinjau` (hasil wizard)** — itulah patokan berdampingan yang diminta. |

**Kolom baru pada `documents`:** `arsip_path_asli` (string nullable).

> Berkas asli **tidak pernah** ditimpa. Pemotongan bisa diulang atau dibatalkan,
> dan salah potong tak pernah berarti dokumen hilang. Satu kolom, dan ia menghapus
> satu-satunya risiko permanen dari keputusan H1.

**Service baru: `app/Services/Print/ArsipPenggabung.php`** (~90 baris)

```php
gabung(string $pathAsli, string $pdfLembar, int $potong): string  // → path berkas baru
jumlahHalaman(string $path): int                                  // untuk validasi
```

Validasi: `potong_halaman` harus `< jumlahHalaman()` — memotong seluruh isi
dokumen adalah galat, bukan pilihan.

### H.4 Jebakan yang sudah dipetakan

1. **`Document::usesRevisionLog()` (`:449-452`) hanya mengecualikan JSA** — untuk
   dokumen kelas `unggahan` ia mengembalikan `true`. Hari ini tak terjangkau
   karena `DocumentController::edit()` (`:307-309`) memulangkan `isArsip()`,
   **tapi** baris FK/PX dengan `arsip_path` NULL lolos dari penjagaan itu.
   Tambahkan `&& ! $this->dariBerkasUnggahan()` (`Document.php:395-398`).
2. **Perbandingan `$step > $schema->stepCount()`** (`DocumentController.php:380`,
   `:451`) menganggap **hanya ada satu** langkah virtual. Fase ini **tidak**
   menambah langkah virtual kedua — form catatan arsip berdiri di halamannya
   sendiri, di luar wizard. Jaga agar tetap begitu.
3. Angka `+1` langkah virtual **terduplikasi di empat tempat**
   (`DocumentController.php:317`, `:370`, `edit.blade.php:19`, `:51`). Jangan
   ditambah jadi lima.
4. Berkas arsip disimpan di disk `local` (privat), bukan `public` —
   `DocumentArsipController::simpanBerkas()` (`:152-158`) menjelaskan alasannya.
   Berkas hasil gabung **wajib** ikut disk yang sama.

### H.5 Test — `tests/Feature/ArsipCatatanRevisiTest.php`

1. Fixture PDF 3 halaman (dibuat FPDF di dalam test) → potong 1, sisip lembar →
   hasil **3 halaman** (1 lembar revisi + 2 sisa); `arsip_path_asli` masih 3 halaman.
2. `potong_halaman = 0` → hasil 4 halaman, tak ada yang hilang.
3. `potong_halaman >= jumlah halaman` → ditolak validasi, `arsip_path` tak berubah.
4. PDF terenkripsi/rusak → pesan galat jelas, `arsip_path` **tak tersentuh**.
5. Pilihan "salin seluruh" → draft wizard lahir dengan `catatan_revisi` terisi dan
   panel referensi memuat PDF unggahan.
6. Jenis FK/PX **tidak** melewati halaman ini (langsung Berlaku seperti sekarang).

---

## 3. Verifikasi menyeluruh (tiap akhir fase)

```bash
php artisan test                       # SELURUH suite pada fase C, F, G, H
php artisan test --filter=<FaseTest>   # boleh untuk A, B, D, E
./vendor/bin/pint --test               # PSR-12 (CLAUDE.md §3)
```

**Uji manual wajib tiap fase:** login sebagai **GL · SH · DH · MD · PJO ·
Non-Staff · Admin**, pastikan menu + tombol yang tampak sesuai matriks fase itu.
Sebagian besar bug perizinan di repo ini muncul sebagai tombol yang *terlihat*
tapi 403 saat ditekan — itu hanya tertangkap dengan mata.

Tambahan: **dark mode + lebar 375px** untuk setiap layar baru (papan beban, Log
Dokumen, daftar nonaktif berkelompok, form catatan arsip berdampingan).

---

## 4. Larangan yang berlaku sepanjang rencana ini (CLAUDE.md §4)

- **JANGAN** `migrate:fresh` / `migrate:refresh`. Setiap migrasi di sini ditulis
  dengan `down()` yang benar; jalankan `migrate` saja.
- **JANGAN** memasang `setasign/*` sebelum Fase H disetujui terpisah.
- **JANGAN** mengerjakan dua fase dalam satu sesi.
- **JANGAN** me-*rename* kunci internal `staff` (CLAUDE.md §6) — dijaga
  `tests/Feature/LabelJabatanTest.php`.
- Ikon **Bootstrap Icons**, nol emoji. Seluruh teks antarmuka & komentar **Indonesia**.
- Controller melewati ~300 baris → pecah ke Service. `DocumentController` sudah
  574 baris: **jangan tambah apa pun ke sana**; itulah sebabnya Fase C dan F
  masing-masing mendapat controller sendiri (`DocumentLogController`,
  `NonaktifController`).
