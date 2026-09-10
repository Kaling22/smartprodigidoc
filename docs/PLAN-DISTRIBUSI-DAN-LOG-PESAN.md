# PLAN B — Distribusi & Log Pesan

> Bagian 2 dari 3. Dikerjakan **sesudah** `PLAN-KESADARAN-TUGAS.md` (A) dan
> **sebelum** `PLAN-PERBAIKAN-DAN-RAPIKAN-UI.md` (C).

## Masalah

1. **Angka distribusi salah diam-diam.** Admin IT ikut jadi penyebut cakupan,
   padahal ia tak pernah menjadi sasaran distribusi dokumen mutu. Selain itu
   `DocumentDistribution::cakupan()` mengurangi penyebut dengan `-1` tanpa syarat
   untuk mengecualikan pembuat — pengurangan itu meleset bila pembuat berada di
   luar departemen dokumen, dan tak ada satu pun gejala di layar.
2. **Distribusi Informasi tak punya rincian.** Yang ada hanya angka gabungan dan
   rincian per departemen; siapa yang belum membuka tak pernah bisa dibaca
   namanya. Menu Informasi memang tak punya halaman detail sama sekali.
3. **"Alasan Dokumen" tidak memuat semua pesan.** Alasan pemusnahan dokumen —
   yang wajib minimal 10 karakter — tak pernah terlihat siapa pun. Dan alasan
   pengalihan peninjauan hanya sampai lewat email, hilang dari layar begitu
   notifikasinya ditandai terbaca.

## Keputusan yang sudah diambil

Sasaran distribusi dokumen = **seluruh pengguna aktif di departemen itu, minus
pembuat, minus Admin IT**. Pembuat tetap dikecualikan seperti sekarang. PJO
melihat per departemen.

---

## B1. Sasaran distribusi tanpa Admin IT

### Scope pakai-ulang di `app/Models/User.php`

```php
/**
 * Orang yang dihitung sebagai SASARAN distribusi: pengguna aktif, tanpa Admin IT.
 *
 * Admin IT dikecualikan karena ia tak pernah menjadi tujuan distribusi dokumen
 * mutu — ia mengurus sistemnya, bukan menjalankan prosedurnya. Menghitungnya
 * sebagai sasaran membuat setiap departemen tampak tertinggal satu orang
 * selamanya, dan tak ada satu pun gejala yang memperlihatkan sebabnya.
 */
public function scopeSasaranDistribusi(Builder $q): Builder
{
    return $q->where('status', 'active')
        ->whereDoesntHave('roles', fn ($r) => $r->where('name', RolePermissionSeeder::ROLE_ADMIN));
}
```

Satu scope, dipakai kedua service. Jangan menulis penyaring Admin dua kali.

### `app/Services/DocumentDistribution.php` — lima suntingan, satu berkas

| # | Baris | Perubahan |
|---|---|---|
| 1 | 84-91 `sasaran()` | Pakai `User::sasaranDistribusi()`; pengecualian `created_by` tetap seperti sekarang |
| 2 | 120-125 `cakupan()` `$perDept` | Penyebut mengecualikan Admin (subquery `NOT EXISTS` ke `model_has_roles`) |
| 3 | 111-115 `cakupan()` `$baca` | **Join ke `users`, buang pembaca Admin.** Tanpa ini muncul "9 dari 8 orang" — bug persis sama yang sudah dijelaskan di `InformasiDistribution.php:101-103` |
| 4 | 128-139 `cakupan()` | Ganti `-1` tanpa syarat dengan pengurangan bersyarat |
| 5 | 156-171 `rincian()` | Saring `DocumentRead` dengan `whereHas('user', …)` agar Admin tak muncul di kolom "Sudah Membaca" sementara ia tak ada di penyebut |

Untuk suntingan #4, **salin pola `$pengunggahDidalam` dari
`InformasiDistribution.php:122-126`** — hitung sekali untuk seluruh kumpulan
dokumen, lalu kurangi hanya bila pembuat aktif, berada di departemen itu, dan
bukan Admin. Jangan mengarang rumus kedua; dua rumus yang harus sepakat adalah
dua rumus yang suatu hari tidak sepakat.

### Kenapa menyaring di sisi BACA, bukan di `catat()`

Menolak mencatat bacaan Admin di `catat()` (baris 37-44) memang lebih pendek,
tapi ia hanya berlaku untuk yang akan datang: baris `document_reads` milik Admin
yang sudah telanjur ada akan tetap merusak angka, dan membereskannya berarti
menghapus data. Menyaring di query pembacaan membereskan yang lama dan yang baru
sekaligus, **tanpa menghapus satu baris pun**.

### `InformasiDistribution` ikut

Terapkan pengecualian Admin yang sama pada `sasaran()` (:77-85), `cakupan()`
(:95-142), dan `perDept()` (:157-200). Rumusnya kembar dengan dokumen mutu; kalau
hanya satu yang diperbaiki, dua halaman akan menampilkan dua kebenaran atas
pertanyaan yang sama.

### PJO per departemen — sudah ada untuk dokumen mutu

Filter Departemen di `distribution.blade.php:68-76` sudah aktif bagi pemegang
`document.view_all` (PJO/Admin/MD), disalurkan lewat
`DocumentDistributionController::distribution()` baris 54. **Tak perlu kode baru
di sisi ini.** Yang kurang hanya di sisi Informasi, dan itu B2.

---

## B2. Rincian distribusi Informasi

### 1. `InformasiDistribution::rincian()`

```php
/**
 * Rincian untuk panel detail: yang sudah membuka (beserta kapan & dari mana)
 * dan yang belum. Cermin DocumentDistribution::rincian().
 *
 * @return array{sudah: Collection, belum: EloquentCollection}
 */
public function rincian(Informasi $informasi, ?int $departmentId = null): array
```

Bentuk kembaliannya **wajib identik** dengan `DocumentDistribution::rincian()`
(kunci `sudah`/`belum`), dengan alasan yang sama seperti kesamaan `cakupan()`
yang sudah didokumentasikan di `InformasiDistribution.php:22-26`: partial-nya
dipakai ulang apa adanya, nol logika baru di Blade.

### 2. Angkat markup jadi partial bersama

Pindahkan isi kartu Distribusi dari `resources/views/documents/show.blade.php`
baris **130-169** (dua kolom "Sudah Membaca" / "Belum Membaca") ke berkas baru
`resources/views/partials/_rincian-pembaca.blade.php`, dengan parameter
`$rincian` dan `$cakupan`.

Di `documents/show.blade.php`, ganti dengan:
`@include('partials._rincian-pembaca', ['rincian' => $distribusiRincian, 'cakupan' => $distribusi])`
— dua variabel itu sudah disiapkan `DocumentController::show()` baris 298-299.

Satu tata letak, dua pemakai.

### 3. Rute, method, view — satu banding satu (CLAUDE.md §3)

```
GET  distribusi/informasi/{informasi}
  → route  documents.rincianInformasi
  → method DocumentDistributionController::rincianInformasi()
  → view   resources/views/documents/rincianInformasi.blade.php
```

- Penjaga: `abort_unless($user->bisaLihatDistribusi(), 403)` — sama persis dengan
  `distribution()` baris 36.
- Lingkup orang:
  `$deptId = $canAll ? $request->query('department_id') : $user->department_id`
  Bentuknya menyalin `daftarInformasi()` baris 96-97, sehingga PJO/Admin/MD bisa
  menelusuri per departemen dan SH/DH otomatis terkurung ke departemennya —
  keputusan C1 yang sudah berlaku di halaman daftarnya.
- Halaman memuat: judul & nomor informasi, pita cakupan
  (`partials/_pita-cakupan`), lalu `partials/_rincian-pembaca`. Bagi pemegang
  `document.view_all`, tambahkan pemilih departemen berupa form GET biasa.

### 4. Tautkan dari daftar

`resources/views/documents/_distribusi-informasi.blade.php` baris 74-87 — tambah
tombol `Rincian` ke rute baru di kolom Aksi. Expander "Per Departemen" (baris
76-80) **tetap**: ia menjawab "departemen mana yang tertinggal", sedangkan
halaman rincian menjawab "siapa orangnya". Cabang `@else` yang sekarang menunjuk
`informasi.index` (baris 82-85) diganti tombol Rincian yang sama, sehingga
SH/DH juga punya jalan ke sana.

---

## B3. "Alasan Dokumen" → "Log Pesan"

### Rename 1:1 (CLAUDE.md §3 — nama route = nama view = nama method)

| Sekarang | Menjadi |
|---|---|
| `DocumentLogController::alasan()` | `pesan()` |
| route `log.alasan`, URL `log-dokumen/alasan` (`routes/web.php:244`) | `log.pesan`, `log-dokumen/pesan` |
| `resources/views/log/alasan.blade.php` | `resources/views/log/pesan.blade.php` |
| label sidebar "Alasan Dokumen" (`layouts/app.blade.php:878`) | "Log Pesan" |
| `<h1>Alasan Dokumen</h1>` + kalimat pengantar (view :14-24) | "Log Pesan" |

Helper privat `dariReviews()` / `dariApprovals()` / `dariAuditLogs()` **tidak**
diganti namanya — namanya menerangkan sumber, bukan halaman.

Perbarui acuan di `tests/Feature/LogDokumenTest.php`. Grep `log.alasan` untuk
memastikan tak ada pemanggil lain yang tertinggal.

### Rincian isi Log Pesan

Inilah kontrak halaman tersebut. Sudah ada sekarang
(`DocumentLogController::TAHAP`, :124-132):

| Tahap | Sumber data | Isi kolom pesan | Dikirimkan ke |
|---|---|---|---|
| Pengajuan revisi | `reviews.summary` awalan `[Pengaju Revisi]` (ditulis `DocumentFeedbackService:121-126`) | alasan revisi + baris `[MSK-…]` masukan yang diadopsi | pembuat draft (lonceng+email), heads dept & MD (lonceng) |
| Ditolak peninjau | `reviews.summary` tanpa awalan, `decision=needs_revision` (ditulis `ReviewDecision`) | ringkasan peninjau | pembuat (lonceng+email) |
| Ditolak Management Development | awalan `[Management Development]` (`MdReviewController:120-126`) | alasan MD | pembuat |
| Ditolak penyetuju | awalan `[Penyetuju]` (`ApprovalController:187-195`) + `approvals.comment` | alasan penolakan | pembuat, dan peninjau yang meloloskan |
| Peninjauan dialihkan | `audit_logs.action=document.reassign_review`, `meta_json.alasan` | alasan pengalihan | **peninjau tujuan (lonceng+email)** dan pembuat (lonceng) |
| Pengajuan nonaktif | `approvals` `kind=nonaktif` | alasan pengajuan | pemutus tahap berikutnya |
| Pengajuan nonaktif ditolak | `approvals` `kind=nonaktif`, `decision=rejected` | alasan penolakan | pihak terkait |

**Dua tahap BARU** — inilah informasi yang selama ini terlewat:

| Tahap baru | Sumber | Kenapa selama ini hilang |
|---|---|---|
| `musnahkan` | `audit_logs.action=document.purge`, `meta_json.alasan` | `DocumentPurger.php:77` menulis audit dengan `document_id = null` — memang harus begitu, dokumennya lenyap sesaat kemudian. Tapi `dariAuditLogs()` (:204-213) menyaring `whereHas('document')`, sehingga alasan pemusnahan yang **wajib minimal 10 karakter** (`documents/_modal-musnahkan.blade.php`) tak pernah terbaca siapa pun. Butuh cabang query terpisah: baca `meta_json.doc_number`, `title`, `department_id` untuk tampilan, tanpa join ke `documents`. |
| `balasan_masukan` | `document_feedback.balasan` + `replied_by` + `replied_at` | Hanya tampil di submenu Masukan Lapangan. Log Pesan adalah gabungan baca-saja atas seluruh pesan yang pernah ditulis atas dokumen; balasan SH/DH ke lapangan adalah pesan, dan pantas ada di sini juga. |

Tambahkan keduanya ke konstanta `TAHAP` agar ikut muncul di dropdown filter
(view :28-63). Warna usulan: `musnahkan` → `danger`, `balasan_masukan` → `info`.

**Sengaja TIDAK ditambahkan — celah yang diketahui:**
`document.cancel_revision` (Batalkan Revisi oleh peninjau,
`ReviewController:267-282`) tidak menyimpan alasan sama sekali. Barisnya akan
kosong dan hanya menambah bising. Menambahkan kolom alasan pada form pembatalan
adalah perubahan **alur**, di luar cakupan rencana ini. Dicatat di sini supaya
keputusannya sadar, bukan kelupaan.

**Konsekuensi teknis:** sumbernya menjadi lima. `BATAS_SUMBER = 500` per sumber
tetap, begitu pula catatan `ponytail:` di `DocumentLogController.php:78-83`
tentang jalur naik ke `UNION ALL` bila datanya membesar — jalur itu justru makin
relevan dengan lima sumber, jadi perbarui angkanya di komentar.

---

## B4. Alasan pengalihan terbaca oleh penerima

### Hasil pemeriksaan kode

Pesan pengalihan **sudah** dikirim ke peninjau tujuan.
`ReviewController.php:240-247` mengirim lonceng **dan** email dengan
`penting: true, catatan: $data['alasan']`. Jadi permintaan "pesan pengalihan
harus dikirimkan juga ke GL SHE tujuan" sudah terpenuhi di jalur pengirimannya.

Yang benar-benar kurang: **alasannya hanya ada di email.** Pesan lonceng berbunyi
*"X mengalihkan peninjauan Y kepada Anda."* tanpa sebabnya
(`DocumentNotification::toArray()` hanya menyimpan `message`, bukan `catatan`),
dan begitu notifikasi ditandai terbaca, alasannya hilang dari layar.

### Perbaikan: tampilkan di tempat pekerjaannya dikerjakan

`ReviewController::show()` — ambil satu baris:

```php
$pengalihan = AuditLog::where('document_id', $document->id)
    ->where('action', 'document.reassign_review')
    ->latest('id')->first();
```

`resources/views/review/show.blade.php` — bila baris itu ada dan
`meta_json['ke']` cocok dengan pemegang sekarang, render `alert alert-info` di
atas halaman: *"Dialihkan oleh {dari}"* diikuti alasannya.

Bertahan setelah notifikasi dibaca, tak butuh kolom baru, dan berada persis di
halaman tempat penerima akan bekerja.

**Pesan lonceng TIDAK diperpanjang.** Lonceng memang ringkas by design, email
sudah membawa alasan lengkap, dan Log Pesan menyimpan salinannya permanen.

---

## Test

**Perluas yang sudah ada — jangan buat berkas baru:**

- `tests/Feature/DocumentDistributionTest.php`
  - Admin IT di departemen dokumen **tidak** masuk `sasaran()`.
  - Admin IT yang membuka dokumen **tidak** menaikkan `pembaca` di `cakupan()`
    dan tidak muncul di `rincian()['sudah']`.
  - Pembuat yang berada **di luar** departemen dokumen tidak lagi memicu
    pengurangan `-1` yang keliru.
- `tests/Feature/DistribusiInformasiTest.php`
  - Admin dikecualikan dari `sasaran()`/`cakupan()`/`perDept()`.
  - `rincian()` mengembalikan `sudah`/`belum` yang benar, tersaring departemen.
  - Rute `documents.rincianInformasi`: pemegang `bisaLihatDistribusi()` 200,
    Non-Staff 403.
- `tests/Feature/LogDokumenTest.php`
  - Perbarui seluruh acuan rute `log.alasan` → `log.pesan`.
  - Kasus baru: alasan `document.purge` muncul di Log Pesan **meski dokumennya
    sudah lenyap**.
  - Kasus baru: balasan masukan muncul sebagai tahap `balasan_masukan`.
- `tests/Feature/PengalihanPeninjauanTest.php`
  - Kasus baru: peninjau tujuan membuka `review.show` dan melihat teks alasannya.

**Harus tetap hijau tanpa diubah:** `NotificationTest`,
`NotificationCoverageTest`, `DocumentFeedbackTest`, `InformasiTest`.

## Verifikasi manual

1. **SH/DH** → Distribusi Dokumen: Admin IT tak lagi muncul di kolom "Belum
   Membaca", dan penyebut pita cakupan berkurang sesuai.
2. **SH/DH** → Distribusi Informasi → tombol Rincian membuka daftar sudah/belum
   membaca dengan nama orangnya.
3. **PJO** → rincian informasi bisa disempitkan per departemen lewat pemilih
   departemen; filter Departemen di distribusi dokumen mutu tetap bekerja.
4. **GL SHE** → alihkan JSA ke GL Plant beralasan. Login sebagai penerima →
   alasannya terbaca sebagai kotak info di halaman tinjau, bukan hanya di email.
   Tandai notifikasinya terbaca, muat ulang → kotak infonya **tetap ada**.
5. **Log Pesan** → menu terbaca "Log Pesan"; dropdown Tahap memuat sembilan
   pilihan termasuk dua yang baru. Musnahkan satu dokumen uji, lalu pastikan
   alasannya muncul di sini meski dokumennya sudah lenyap.
