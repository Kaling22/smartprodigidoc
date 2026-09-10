# PLAN PREPRODUKSI v9 — Impor User, Uji AI & Rilis Produksi

> Lanjutan `docs/PLAN-AKSES-v8.md` (Fase 1–5c **selesai**).
> Aturan kerja tetap CLAUDE.md §5: satu fase → berhenti → jelaskan → tunggu
> review + commit.

---

## 1. Konteks

Butir 1–5 sudah tuntas di v8. Pemilik mengangkat tiga permintaan baru yang
semuanya mengarah ke satu hal: **membawa sistem ini kembali ke produksi.**

- **Butir 6** — 112 akun sungguhan sudah terdaftar di produksi
  (`u805399352_smartpro_new.sql`), sementara basis data kerja lokal hanya punya
  17. Uji manual v8 (Fase 1, 2, 5b) berjalan di atas dunia yang jauh lebih
  kecil daripada dunia nyata. Ke-99 akun yang belum ada perlu masuk ke
  `smartpro_fresh` — **user saja**, tanpa dokumen, tanpa riwayat.
- **Butir 7** — produksi masih menjalankan build LAMA. Sembilan migration
  belum pernah dijalankan di sana, dan aplikasi mobile masih menunjuk IP laptop.
- **Butir 8** — kunci API AI dari layar Konfigurasi Sistem **sudah tersambung**,
  tetapi tidak ada cara membuktikannya dari layar itu.

**Hasil yang dituju:** basis data lokal yang seukuran dunia nyata, kode yang
lolos `config:cache`+`route:cache`, layar Konfigurasi Sistem yang bisa
membuktikan kuncinya hidup, dan satu runbook rilis yang bisa diikuti tanpa
menebak.

---

## 2. Jawaban langsung atas butir 7 & 8

Ditulis di depan supaya tidak tenggelam di badan rencana.

### Butir 7 — "apakah email-nya sudah berfungsi?"

**Sisi kode: siap.** Yang dulu jadi lubang sudah tertutup —
`config/mail.php:67` membaca `MAIL_TIMEOUT` (dulu `null` mati), mailer
`failover` (SMTP → log) ada di `config/mail.php:101`, katup `MAIL_PAKSA_KE`
terpasang, dan `DocumentNotification::viaConnections()` mengantrekan kanal
`mail` sementara lonceng tetap sinkron.

**Sisi produksi: kemungkinan besar jalan, dan dump-nya jadi buktinya.** Di
`u805399352_smartpro_new.sql`, tabel `jobs` **kosong** dan `failed_jobs`
**kosong** sementara `notifications` berisi 21 baris — pola yang muncul kalau
cron `queue:work` benar-benar menghabiskan antreannya. Kalau pekerjanya mati,
`jobs` akan menumpuk.

**Setelan cron kemarin masih berlaku, tanpa perubahan.** v8 tidak menambah satu
pun peristiwa berkirim email (masukan sejawat sengaja `penting = false` —
lonceng saja), jadi kedua baris cron di `docs/PANDUAN-PRODUKSI-EMAIL.md` §Fase 4
tetap apa adanya. Yang WAJIB ditambahkan ke prosedur rilis bukan cron baru,
melainkan `php artisan queue:restart` sesudah `git pull` — pekerja lama memegang
kode lama di memori.

Tiga hal yang tetap harus diperiksa mata sendiri di server (§6 runbook):
`MAIL_PAKSA_KE` masih terisi atau tidak, `APP_URL` sudah `https://`, dan
`MAIL_FROM_ADDRESS` sama persis dengan `MAIL_USERNAME`.

### Butir 8 — "apakah penambahan API key AI berfungsi?"

**Ya, dan langsung dipakai request berikutnya.** `AppServiceProvider` memakai
`bind` (bukan `singleton`), dan closure-nya membaca `Pengaturan::ai()` **saat
di-resolve** — bukan saat boot. Kunci yang disimpan Admin tersandi
(`Crypt::encryptString`), `Pengaturan::simpan()` membuang cache **dan** ingatan
statisnya, jadi permintaan berikutnya sudah memakai kunci baru. Tak ada
`config:cache` yang perlu dijalankan ulang, sebab nilainya tidak tinggal di
config.

**Yang tidak ada: cara membuktikannya dari layar itu.** Satu-satunya cara
menguji kunci sekarang adalah membuka layar Tinjau Dokumen dan menekan Analisis
AI — 1–3 menit menunggu untuk pertanyaan yang jawabannya "kuncinya benar atau
tidak". Itu yang ditambal **Fase 2a**.

---

## 3. Keadaan terukur (sudah diverifikasi, bukan perkiraan)

| Hal | Angka |
|---|---|
| User di `smartpro_fresh` | 17 |
| User di dump produksi | 112 |
| NRP produksi yang **sudah ada** di lokal → dilewati | **13** |
| User yang **akan masuk** | **99** |
| Bentrok email dengan baris lokal | **0** |
| Bentrok email sesama baris baru | **0** |
| Jabatan yang masuk | `group_leader` 95 · `section_head` 3 · `departemen_head` 1 |
| Semua berstatus | `active`, `deleted_at` kosong, email terisi semua, tanpa foto |
| Sebaran departemen | PRODUKSI 66 · PLANT 8 · ENGINEERING 8 · SHE 10 · FAW-SCM 3 · HCGA 3 · ICTMD 1 |
| Migration di repo | 40 |
| Migration yang sudah jalan di produksi | 31 |
| **Migration tertunggak di produksi** | **9** (didaftar di §6) |
| Dokumen di produksi | 9 · `audit_logs` 287 · `jobs` & `failed_jobs` kosong |

**Skema aman.** `users` lokal = `users` produksi + satu kolom `access_profile_id`
(nullable). `departments` id 1–7 identik di kedua sisi; `roles` id & nama juga
identik. Tak ada pemetaan ulang departemen atau peran yang perlu ditebak.

**Satu jebakan yang membuat `id` TIDAK boleh dibawa:** produksi memakai id
`667` (Angga) & `668` (Arisal); lokal memakai id yang sama untuk `GLSHE-0001` &
`T.M.FATHIN RIFAT`. Impor dengan id asli akan menimpa dua akun yang tak ada
hubungannya. Id dibiarkan auto-increment.

---

## 4. Keputusan pemilik (26 Agu 2026)

| # | Keputusan |
|---|---|
| **A** | Impor hanya ke **`smartpro_fresh`**. `smartpro_uji` tidak disentuh — suite berjalan di atas `DatabaseTransactions`, dan menaruh user nyata di sana tidak menghidupkan satu pun dari 310 test mati (v8 §3 jalan B tetap satu-satunya jalan). Produksi juga tidak: dump ini **berasal** dari sana. |
| **B** | User baru masuk dengan **`access_profile_id` NULL**. Admin yang menetapkan profilnya per orang lewat Manajemen Akses. Konsekuensi yang DISENGAJA: 95 GL awalnya melihat menu penuh tapi `User::bolehBuatJenis()` menolak — mereka read-only sampai Admin menyentuhnya. Tidak ada aksi massal yang ditambahkan. |
| **C** | Butir 8 dikerjakan sebagai **tombol Uji Koneksi AI**, dan tombol **Bersihkan Seluruh Dokumen dipindah** dari Manajemen User ke Konfigurasi Sistem. |
| **D** | Butir 7 dikerjakan bertiga: **runbook rilis**, **ubah `api_config.dart` mobile**, dan **perbaikan temuan kode pra-produksi**. |

---

## 5. Urutan pengerjaan

| Fase | Isi | Bobot | Bagian |
|---|---|---|---|
| **1** | Impor 99 user produksi ke `smartpro_fresh` | Sedang | §6 |
| **2** | 2a Tombol Uji Koneksi AI · 2b Pindah Zona Berbahaya | Sedang | §7 |
| **3** | Temuan kode pra-produksi (`route:cache` & kawan-kawan) | Ringan | §8 |
| **4** | Runbook rilis + arahkan aplikasi mobile | Sedang | §9 |

Urutannya sengaja **berbeda dari penomoran pemilik** (6-7-8): butir 8 dan
perbaikan kode harus mendarat di repo **sebelum** butir 7, sebab butir 7 adalah
`git pull` di produksi. Merilis dulu lalu memperbaiki berarti dua kali rilis.

> **Utang yang dibawa masuk, dan tidak dilunasi di sini.** v8 §11 T4 masih
> berlaku: **310 test merah** karena NRP contoh yang tak akan kembali. Artinya
> jaring pengaman untuk penomoran, ekspor, alur tinjau–setuju, dan **tata letak
> cetak PDF** sedang mati justru pada rilis yang mengubah paling banyak hal.
> Rekomendasi saya: kerjakan v8 Fase 6 sebelum Fase 4 di sini. Kalau pemilik
> memilih rilis lebih dulu, §9 memuat uji manual pengganti — itu penambal, bukan
> pengganti setara.

---

## 6. Fase 1 — Impor 99 user produksi ke `smartpro_fresh`

Tidak ada berkas repo yang berubah. Yang dihasilkan fase ini adalah satu berkas
SQL yang **tidak boleh masuk Git** (memuat hash kata sandi & email 99 karyawan;
`.gitignore` sudah menutup `/*.sql` di akar, dan berkas ini ditaruh di
scratchpad, di luar repo).

### Langkah

1. **Cadangkan dulu** — dua tabel yang disentuh saja, bukan seluruh basis data:
   ```
   mysqldump -uroot smartpro_fresh users model_has_roles > <scratchpad>/cadangan-users-20260826.sql
   ```

2. **Bangkitkan `impor-user.sql`** dengan skrip sekali pakai di scratchpad. Ia
   membaca `u805399352_smartpro_new.sql`, membuang baris yang NRP-nya sudah ada
   di `smartpro_fresh`, dan menulis SATU `INSERT` berisi 99 baris.

   Kolom yang **dibawa**: `name`, `nrp`, `jabatan`, `nomor_hp`, `email`,
   `password`, `department_id`, `status`, `ai_review_enabled`, `created_at`,
   `updated_at`.

   Kolom yang **dibuang, beserta alasannya**:

   | Kolom | Alasan |
   |---|---|
   | `id` | bentrok 667/668 (§3) — dibiarkan auto-increment |
   | `access_profile_id` | keputusan B — NULL |
   | `username`, `jabatan_diajukan`, `photo_path`, `email_verified_at`, `remember_token`, `deleted_at` | NULL pada ke-99 baris; menyalin NULL hanya memanjangkan berkas |

   `password` **ikut apa adanya** — hash bcrypt produksi. Konsekuensi yang
   diinginkan: ke-99 orang itu masuk ke lokal dengan kata sandi yang sama dengan
   di produksi, jadi uji manual memakai kredensial sungguhan.

3. **Baca berkasnya** sebelum dijalankan — hitung `SELECT COUNT` di kepala
   berkas harus 99, dan tak boleh ada satu pun `id` di daftar kolom.

4. **Jalankan di dalam transaksi**, bukan `INSERT IGNORE`:
   ```sql
   START TRANSACTION;
   INSERT INTO users (name, nrp, …) VALUES (…99 baris…);

   -- Peran spatie. Nama role identik dengan kunci `jabatan`
   -- (group_leader/section_head/departemen_head/staff/pimpinan), jadi tak ada
   -- peta kedua yang perlu ditulis. LEFT JOIN membuatnya IDEMPOTEN: baris yang
   -- sudah punya peran dilewati, dan user lama yang kebetulan belum punya peran
   -- ikut tertambal.
   INSERT INTO model_has_roles (role_id, model_type, model_id)
   SELECT r.id, 'App\\Models\\User', u.id
   FROM users u
   JOIN roles r ON r.name = u.jabatan
   LEFT JOIN model_has_roles m ON m.model_id = u.id AND m.model_type = 'App\\Models\\User'
   WHERE m.model_id IS NULL;
   COMMIT;
   ```

   **`INSERT` polos, bukan `INSERT IGNORE`.** Kita sudah membuktikan nol bentrok
   (§3); kalau ternyata ada, statement harus **gagal keras dan roll back**, bukan
   membuang baris diam-diam. `IGNORE` juga menelan galat lain (foreign key,
   pemotongan kolom) — persis yang tak boleh terjadi pada data orang.

5. **Buang cache izin** — spatie meng-cache peta peran/izin:
   `php artisan permission:cache-reset`.

### Verifikasi

```sql
SELECT COUNT(*) FROM users;                              -- 116 (17 + 99)
SELECT COUNT(*) FROM users u LEFT JOIN model_has_roles m
  ON m.model_id=u.id AND m.model_type='App\\Models\\User'
  WHERE m.model_id IS NULL;                              -- 0  (tak ada yang tanpa peran)
SELECT nrp, COUNT(*) c FROM users GROUP BY nrp HAVING c>1;    -- kosong
SELECT email, COUNT(*) c FROM users WHERE email IS NOT NULL
  GROUP BY email HAVING c>1;                             -- kosong
SELECT COUNT(*) FROM users WHERE department_id NOT BETWEEN 1 AND 7
  AND department_id IS NOT NULL;                         -- 0
```

Lalu di aplikasi: masuk sebagai satu NRP hasil impor (mis. `15111111` EKO
YULIANTO, PRODUKSI) dengan kata sandi produksinya → berhasil, sidebar GL
tampil, dan **"Dokumen Baru" menolak semua jenis** (keputusan B). Admin memberi
profil "GL ALL Access" ke orang itu lewat Manajemen Akses → jenisnya terbuka.

**Selesai bila:** 116 user, nol tanpa peran, satu akun hasil impor terbukti bisa
masuk, dan Manajemen User menampilkan halamannya tanpa galat pada 116 baris.

---

## 7. Fase 2 — Uji Koneksi AI & pindah Zona Berbahaya

### 7a — Tombol Uji Koneksi AI

**Berkas:** `app/Services/Ai/AiReviewerInterface.php` ·
`AbstractAiReviewer.php` · `GeminiReviewer.php` · `OpenRouterReviewer.php` ·
`NullReviewer.php` · `FallbackReviewer.php` ·
`app/Http/Controllers/PengaturanController.php` ·
`resources/views/pengaturan/sistem.blade.php` · `routes/web.php` ·
`tests/Feature/KonfigurasiSistemTest.php`

Satu method baru pada antarmuka:

```php
/**
 * Uji kredensial TANPA membangkitkan tinjauan. Null = berhasil; string =
 * pesan galat penyedia apa adanya.
 *
 * Sengaja BUKAN review() atas dokumen boneka: review() memakai
 * TIMEOUT_DETIK 180 dan MAX_TOKEN_JAWABAN 6000 karena model "reasoning"
 * berpikir ~2.000 token sebelum menjawab. Admin yang cuma ingin tahu
 * "kuncinya benar atau tidak" tak boleh menunggu tiga menit.
 */
public function ping(): ?string;
```

Implementasinya satu panggilan GET murah per penyedia, `Http::timeout(15)`:

| Penyedia | Panggilan | Yang terbukti |
|---|---|---|
| Gemini | `GET https://generativelanguage.googleapis.com/v1beta/models/{model}` + header `x-goog-api-key` | kunci **dan** nama model |
| OpenRouter | `GET https://openrouter.ai/api/v1/key` + `withToken` | kunci saja |
| `NullReviewer` | — | `'AI dinonaktifkan atau kunci belum disetel.'` |

```php
// ponytail: OpenRouter diuji lewat /api/v1/key — nama model TIDAK ikut
// diperiksa. Naikkan ke GET /api/v1/models dan cocokkan id-nya bila salah
// ketik nama model terbukti jadi keluhan nyata.
```

Galatnya dirakit `AbstractAiReviewer::pesanGalat()` yang **sudah ada** — itulah
yang membuat "402" terbaca sebagai "kredit OpenRouter habis", di layar maupun
di log.

`FallbackReviewer` mendapat `daftar(): array` (getter atas `$reviewers`), bukan
`ping()` yang menggabungkan hasil: layar Admin perlu tahu **penyedia mana** yang
mati, dan penggabungan justru membuang tepat informasi itu.

**Rute** — di samping `pengaturan.sistem.uji-email` (`routes/web.php:455`),
dengan rem yang sama sifatnya:
```
POST pengaturan/sistem/uji-ai   pengaturan.sistem.uji-ai   (throttle:6,1)
```

**Controller** `PengaturanController::ujiAi(AiReviewerInterface $ai)` —
`$daftar = $ai instanceof FallbackReviewer ? $ai->daftar() : [$ai];`, ping
tiap-tiap, rakit satu kalimat berlabel **Utama** / **Cadangan** (labelnya dari
`Pengaturan::ai()`), lalu `back()->with('status'|'error', …)`. Nol JS — pola
form-POST-redirect yang sama dengan tombol uji email di sebelahnya. Audit
`pengaturan.ai_diuji` mencatat penyedia, model, dan **berhasil/gagal** —
**tidak pernah kuncinya** (aturan v8 §9.4 butir 5).

**View** — satu tombol `<i class="bi bi-plug"></i> Uji koneksi AI` di kaki
kartu AI, di luar `<form>` penyimpan setelan (form bersarang tidak sah di HTML
dan tombolnya akan menyimpan, bukan menguji).

**Test** (`Http::fake()` — berbeda dari `Mail::fake()` yang badan `raw()`-nya
kosong, `Http::fake` benar-benar mencatat): kunci ditolak 401 → halaman memuat
pesan penyedia; 200 → pesan berhasil; saklar AI mati → hasilnya berbunyi
"dinonaktifkan" dan **tak ada panggilan HTTP** yang terjadi.

### 7b — Pindahkan "Zona Berbahaya" ke Konfigurasi Sistem

Kartu **Bersihkan Seluruh Dokumen** sekarang di `users/index.blade.php:246-270`.
Tempatnya memang salah: ia bukan urusan akun, dan Konfigurasi Sistem sudah jadi
rumah bagi aksi sistemik lain (bersihkan cache, uji email).

Perpindahannya bersih karena **kedua halaman sudah dikunci izin yang sama** —
`documents.purgeAll` ada di dalam grup `can:user.manage` (`routes/web.php:367`),
begitu pula `pengaturan.sistem`. Tak ada izin yang berubah.

| Berkas | Perubahan |
|---|---|
| `resources/views/users/index.blade.php` | buang blok `:246-270` **beserta** `@include('documents._modal-musnahkan-semua')` |
| `resources/views/pengaturan/sistem.blade.php` | tempel keduanya di **paling bawah**, sesudah kartu Kesehatan |
| `UserManagementController::index()` | buang `'jumlahDokumen'` |
| `PengaturanController::kesehatan()` | tambah `'dokumen_semua' => Document::withTrashed()->count()` — `withTrashed` **wajib**: dokumen yang sudah di-soft-delete pun ikut dimusnahkan, jadi angka yang dijanjikan tombolnya harus angka yang benar-benar terhapus. `'dokumen'` yang sudah ada TIDAK dipakai untuk ini. |
| `DocumentRevisionController::purgeAll()` | redirect `users.index` → `pengaturan.sistem` |
| `tests/Feature/ManajemenUserDanPemusnahanTest.php` | `:214` redirect target; assert kartu ada di `pengaturan.sistem`, **tidak** di `users.index` |

**Rute `documents.purgeAll` TIDAK diganti nama maupun path.** Path
`admin/documents/purge-all` punya alasan yang masih berlaku (komentar
`routes/web.php:371-377`: `documents/purge-all` akan tertangkap
`documents.destroy` lebih dulu dan menjawab 404). Menggantinya = diff besar
tanpa satu pun perbedaan yang terlihat pengguna.

**Selesai bila:** `php artisan test --filter=KonfigurasiSistem` dan
`--filter=ManajemenUserDanPemusnahan` hijau; Manajemen User tak lagi punya kartu
merah; Konfigurasi Sistem punya keduanya (Uji AI & Bersihkan Dokumen) dan
angkanya benar.

---

## 8. Fase 3 — Temuan kode pra-produksi

### 8a — `route:cache` GAGAL karena satu closure (WAJIB, memblokir)

`routes/web.php:61` mendaftarkan `storage/{path}` sebagai **closure**. Laravel
menolak men-serialisasi closure: `php artisan route:cache` akan berhenti dengan
`LogicException: Unable to prepare route [storage/{path}] for serialization`.

Ironinya komentar di atas closure itu sendiri (`:55`) berbunyi *"runbook deploy
memang men-cache route"* — jadi rute yang ditulis demi ketahanan produksi justru
memblokir langkah produksi.

**Perbaikan:** pindahkan badannya ke controller invokable
`app/Http/Controllers/PublicStorageController.php` (~10 baris, isinya
dipindah apa adanya), lalu

```php
Route::get('storage/{path}', PublicStorageController::class)
    ->where('path', '(avatars|lampiran)/[A-Za-z0-9_\-/]+\.[A-Za-z0-9]+')
    ->name('storage.public');
```

Pola `where`, sifat terbuka-tanpa-auth, dan seluruh komentar penjelasnya
**tidak berubah** — yang berpindah hanya tempat kodenya.

**Verifikasi:** `php artisan route:cache` selesai tanpa galat, lalu
`php artisan route:clear`; buka satu foto avatar → tetap tampil.

### 8b — Timeout AI 180 detik vs shared hosting (setelan, bukan kode)

`AbstractAiReviewer::TIMEOUT_DETIK = 180`, dan panggilan Analisis AI berjalan
**sinkron** dari peramban. Di Hostinger shared, `max_execution_time` dan
batas proxy lazimnya jauh di bawah itu — layar tinjau bisa menggantung lalu
mati sebagai 504 alih-alih menampilkan pesan galat yang rapi.

**Tak ada perubahan kode.** Justru inilah gunanya layar Konfigurasi Sistem:
sebelum go-live, ganti model ke penyedia non-*reasoning* yang cepat dari layar
itu (v8 §9.4). Tombol Uji Koneksi (Fase 2a) memakai timeout 15 detik sendiri,
jadi ia tetap berguna meski model utamanya lambat. Dicatat di runbook §9.

### 8c — Yang SUDAH sembuh, coret dari daftar utang v8

`docs/PLAN-AKSES-v8.md` §11 **T2** ("flash `success` tak pernah tampil") sudah
tidak berlaku: `layouts/app.blade.php:1272` kini merender
`session('status') || session('success')`. Baris T2 dihapus dari daftar utang.

### 8d — `docs/data-migrasi.sql` ter-track dan memuat hash sandi

Berkas itu ada di Git dan memuat 9 baris `INSERT INTO users` lengkap dengan
hash bcrypt. Alamatnya semua akun contoh (`GLSHE-0001@gmail.com` dkk), jadi ini
**bukan** kebocoran data karyawan — tapi tetap layak dicatat sebelum repo
publik dipakai untuk rilis. **Tidak dikerjakan di v9**; keputusan pemilik apakah
berkasnya dikeluarkan dari riwayat.

**Selesai bila:** `route:cache` lolos, satu foto avatar tetap tampil sesudahnya,
dan suite yang menyentuh rute storage tetap hijau.

---

## 9. Fase 4 — Runbook rilis + arahkan aplikasi mobile

### 9a — `docs/RUNBOOK-RILIS.md` (berkas baru)

Belum ada satu pun dokumen langkah rilis di repo — `PANDUAN-PRODUKSI-EMAIL.md`
hanya menutup sisi surel. Runbook ini menutup sisanya, dan menunjuk ke sana
untuk bagian email alih-alih menyalinnya.

Isinya, urut:

1. **Cadangkan produksi lebih dulu** — `mysqldump` seluruh basis data dari
   hPanel, disimpan di luar server. Ini prasyarat, bukan saran: langkah 3
   menyentuh skema.

2. **`git pull`** di `public_html`, lalu
   `composer install --no-dev --optimize-autoloader`.

3. **Sembilan migration yang belum pernah jalan di produksi** — `php artisan migrate`
   biasa. **TIDAK PERNAH** `migrate:fresh`/`migrate:refresh` (CLAUDE.md §4):
   ```
   2026_08_19_100000_create_informasi_reads_table
   2026_08_20_090000_add_nonaktif_berjenjang_to_documents
   2026_08_20_090100_add_kind_to_approvals
   2026_08_21_090000_add_arsip_path_asli_to_documents
   2026_08_25_090000_create_access_profiles_table
   2026_08_25_100000_create_document_feedback_antargl_table
   2026_08_26_090000_create_pengaturan_table
   2026_08_26_100000_add_is_active_to_departments_table
   2026_08_26_100100_create_informasi_kategori_table
   ```
   Catatan yang menentukan: `create_informasi_kategori_table` **meng-INSERT
   kesepuluh kategori di dalam migration** (v8 §9.3 butir 4) — bukan seeder.
   Tabel kosong berarti menu Informasi lenyap, jadi migration ini wajib benar
   -benar tuntas, bukan sekadar "tidak error".
   `create_pengaturan_table` sengaja **tidak** di-seed: tabel kosong = prefix
   `PPA-ADRO` dari kode, sama persis dengan perilaku produksi sekarang.

4. **Periksa `.env` produksi** — sebelum `config:cache`, bukan sesudah:
   `APP_ENV=production` · `APP_DEBUG=false` · `APP_URL=https://…` ·
   `MAIL_MAILER=failover` · `MAIL_FROM_ADDRESS` **sama persis** dengan
   `MAIL_USERNAME` · `MAIL_TIMEOUT=15` (harus < `DB_QUEUE_RETRY_AFTER` 90,
   kalau tidak satu email bisa terkirim dua kali) · `QUEUE_CONNECTION=database` ·
   keadaan `MAIL_PAKSA_KE` disengaja, bukan tertinggal.

5. **`php artisan config:cache && php artisan route:cache && php artisan view:cache`** —
   `route:cache` baru mungkin sesudah Fase 3a.

6. **`php artisan queue:restart`** — langkah yang paling gampang terlupa dan
   paling sunyi akibatnya: pekerja lama memegang kode lama di memori.

7. **`php artisan storage:link`** bila memungkinkan. Kalau hosting melarang
   symlink, tak ada yang perlu dilakukan — rute `storage.public` (Fase 3a)
   memang jaring pengamannya.

8. **Dua cron tetap seperti kemarin** — disalin apa adanya dari
   `docs/PANDUAN-PRODUKSI-EMAIL.md` §Fase 4 (`queue:work --stop-when-empty
   --max-time=55` tiap menit; `queue:prune-failed` harian). **Tidak ada cron
   baru di v9.**

9. **Setelan pasca-rilis dari layar, bukan dari `.env`** — Konfigurasi Sistem:
   pilih model AI cepat (§8b), periksa kartu Kesehatan (`APP_DEBUG` harus
   terbaca mati, `MAIL_PAKSA_KE` sesuai niat), tekan **Uji koneksi AI**
   (Fase 2a), tekan **Kirim email uji**.

10. **Verifikasi rilis** (§10).

11. **Rollback** — kembalikan commit sebelumnya + `mysqldump` langkah 1.
    Ke-9 migration itu **aditif**; satu-satunya yang menulis data adalah INSERT
    kategori Informasi, dan itu pun tidak menimpa apa pun.

### 9b — Arahkan aplikasi mobile ke produksi

**Berkas di luar repo ini:** `c:/xamppnew/htdocs/SmartPro-mobile/` — dan
foldernya **bukan repo git**, jadi perubahannya tak punya riwayat. Salin dulu
berkas yang disentuh sebelum diubah.

`lib/config/api_config.dart` sendiri sudah memuat instruksinya (`:26-62`);
tinggal dijalankan, dan syaratnya sekarang **terpenuhi**: begitu langkah 9a
selesai, `adw.proworkppa.com` melayani SmartPro, bukan lagi build lama.

| Berkas | Perubahan |
|---|---|
| `lib/config/api_config.dart:51-62` | komentari `host` lokal (`http://10.121.143.9:9090`), buka komentar blok produksi (`https://adw.proworkppa.com`) |
| `android/app/src/main/AndroidManifest.xml:8` | `android:usesCleartextTraffic="true"` → **`"false"`**. Selama menyala, satu salah ketik `http://` sudah cukup mengirim token Bearer lewat jaringan polos |
| `pubspec.yaml:19` | `1.0.0+3` → `1.0.0+4` — build lama tak boleh tertukar dengan yang menunjuk produksi |

**Tak ada berkas lain yang disentuh** — seluruh layar menyusun URL-nya dari
`ApiConfig.apiUrl`/`publicUrl`.

**Urutannya mengikat:** 9b **sesudah** 9a. Mengarahkan aplikasi ke domain yang
masih melayani build lama bukan gagal terhubung melainkan gagal **diam-diam** —
layar terbuka, isinya milik basis data lain.

**Selesai bila:** APK baru dipasang di satu HP, masuk memakai NRP produksi,
menu Informasi terisi, foto profil tampil, dan `GET /api/notifications`
menjawab 200 (bukan 404 seperti build lama).

---

## 10. Verifikasi

```powershell
# Fase 2
php artisan test --filter=KonfigurasiSistem
php artisan test --filter=ManajemenUserDanPemusnahan

# Fase 3
php artisan route:cache ; php artisan route:clear
php artisan config:cache ; php artisan config:clear

# Regresi yang harus tetap hijau (v8 §4 — semuanya "jalan B")
php artisan test --filter="ProfilAkses|ManajemenAkses|MasterData|PengaturanSite|MenuPengaturan|MasukanSejawat|PesanBerkontek"
```

**Uji manual lokal (`jalankan.bat`), sesudah Fase 1 & 2:**

1. Masuk sebagai NRP hasil impor → sidebar GL, "Dokumen Baru" menolak semua
   jenis. Admin memberi profil → jenisnya terbuka.
2. Manajemen User → 116 baris terpaginasi, **tanpa** kartu merah di kaki halaman.
3. Konfigurasi Sistem → **Uji koneksi AI**: dengan kunci benar berbunyi
   berhasil; kunci sengaja disalahketik berbunyi pesan penyedia yang jelas
   (bukan "401" telanjang). Audit Log memuat `pengaturan.ai_diuji` **tanpa**
   kunci API.
4. Konfigurasi Sistem → **Bersihkan Seluruh Dokumen** ada di sana, angkanya
   sama dengan `SELECT COUNT(*) FROM documents` termasuk yang soft-deleted.
   **Jangan ditekan** kecuali memang berniat memusnahkan.
5. Buka satu foto avatar sesudah `route:cache` → tetap tampil.

**Uji manual produksi, sesudah Fase 4** — penambal atas 310 test yang mati
(v8 §11 T4), dijalankan berurutan pada satu dokumen:
GL buat draft → kirim → SH tinjau → PJO setujui → **unduh PDF dan periksa
kop, "Halaman X dari Y", serta blok pengesahan**; ekspor Daftar Induk; buka
Informasi; lonceng & satu email sampai.

---

## 11. Di luar scope (sengaja tidak dikerjakan)

- **Melunasi v8 Fase 6** (310 test merah). Dinyatakan risikonya di §5; kalau
  pemilik memilih mengerjakannya, tempatnya sebelum §9.
- **Impor dokumen/riwayat produksi ke lokal.** Pemilik meminta "hanya user-nya
  saja". Dokumen membawa `document_contents`, `attachments`, `reviews`,
  `approvals`, `document_versions`, dan berkas di `storage/` — id-nya bertaut
  ke `users.id` yang justru kita petakan ulang di §6.
- **Aksi massal profil akses.** Keputusan B: Admin menetapkan per orang.
- **Impor ke `smartpro_uji` atau ke produksi.** Keputusan A.
- **Cron baru.** v8 tak menambah satu pun peristiwa email; yang ada sudah cukup.
- **Memasang ekstensi `intl`** (v8 §11 T6) — perubahan lingkungan produksi,
  keputusan pemilik. Kode baru tetap dilarang memakai `Illuminate\Support\Number::`.
- **Mengeluarkan `docs/data-migrasi.sql` dari riwayat Git** (§8d) — menulis
  ulang riwayat repo, keputusan pemilik.
- **Menurunkan `TIMEOUT_DETIK`** (§8b) — diselesaikan dengan memilih model,
  bukan dengan mengubah konstanta yang sudah dikalibrasi pada JSA nyata.
