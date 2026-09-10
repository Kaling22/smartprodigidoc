# CHECKLIST PREPRODUKSI — SmartPro

> **Panggil dokumen ini setiap kali hendak mendorong SmartPro ke produksi.**
> Ia menutup semua yang dikerjakan **di laptop, sebelum `git push`**.
> Yang dikerjakan **di server sesudah push** ada di `docs/RUNBOOK-RILIS.md`;
> sisi surel diperdalam `docs/PANDUAN-PRODUKSI-EMAIL.md`. Ketiganya tidak
> tumpang tindih — jangan menyalin isi yang satu ke yang lain.

| Berkas | Menjawab |
|---|---|
| **CHECKLIST-PREPRODUKSI.md** (ini) | "repo & basis data lokal saya sudah pantas dirilis belum?" |
| `RUNBOOK-RILIS.md` | "apa yang saya ketik di server sesudah `git pull`?" |
| `PANDUAN-PRODUKSI-EMAIL.md` | "kenapa email tidak sampai?" |

**Urutannya mengikat.** §1 (cadangan) sebelum §2 (hapus). §5 (audit rahasia)
sebelum `git push` — sesudah terdorong, rahasianya sudah ada di riwayat orang
lain dan tak bisa ditarik.

---

## 1. Cadangkan basis data lokal — SELALU lebih dulu

Bukan saran. §2 menghapus baris tanpa undo.

```bash
"C:/xamppnew/mysql/bin/mysqldump.exe" -uroot --single-transaction --routines --events \
  smartpro_fresh > "<scratchpad>/cadangan-smartpro_fresh-<TANGGAL>.sql"
```

Simpan **di luar repo**. `.gitignore` sudah menutup `/*.sql` di akar, tapi
cadangan yang tak pernah masuk repo lebih aman daripada yang tertutup pola.

**Periksa berkasnya sebelum lanjut** — baris terakhir harus berbunyi
`-- Dump completed on …`. Tanpa baris itu dumpnya terpotong, dan cadangan
terpotong lebih berbahaya daripada tak punya cadangan sama sekali: ia membuat
Anda berani menghapus.

---

## 2. Bersihkan data pengembangan

### 2a. Dokumen + lampiran + singgahan PDF

**Jangan tulis SQL tangan.** Alatnya sudah ada dan sudah teruji —
`app/Console/Commands/HapusDokumen.php`, yang mendelegasikan ke
`app/Services/DocumentPurger.php` (satu-satunya pemusnah dokumen di aplikasi
ini). Ia tahu hal-hal yang tak terlihat dari SQL polos: `job_executions`
ber-`RESTRICT`, notifikasi lonceng yang menyimpan `document_id` di dalam JSON
tanpa foreign key, berkas lampiran & arsip di disk, dan singgahan
`storage/app/pdf-cache`.

```bash
php artisan smartpro:hapus-dokumen --dry-run   # lihat dulu daftarnya
php artisan smartpro:hapus-dokumen --force     # baru hapus
```

Perintahnya **menolak jalan bila `APP_ENV=production`** — penjaga yang memang
disengaja, jangan dilucuti.

Yang **TIDAK** tersentuh perintah ini: `users`, foto profil di
`storage/app/public/avatars`, `departments`, `document_types`, `pengaturan`,
`roles`/`permissions`, `informasi_kategori`, dan `audit_logs`.

`audit_logs` sengaja selamat: jejak "siapa memusnahkan apa" justru yang paling
tidak boleh ikut hilang. Kalau memang ingin nol mutlak, kosongkan manual —
sadari bahwa entri `document.purge_all` yang baru saja ditulis pun ikut lenyap.

### 2b. Lampiran yatim

Purger hanya tahu berkas yang punya baris di `attachments`. Berkas sisa uji
manual tak punya baris, jadi ia tertinggal — 0 gejala di layar, tetap memakan
disk.

```bash
find storage/app/public/lampiran -type f      # harus kosong sesudah §2a
```

Ada isinya? **Grep dulu** apakah namanya dipakai kode/test sebelum dibuang:

```bash
grep -rn "<nama-berkas>" --include=*.php --include=*.blade.php app tests resources routes database
rm -f storage/app/public/lampiran/<DEPT>/<JENIS>/<nama-berkas>
```

Folder `lampiran/{DEPT}/{JENIS}/` yang tersisa kosong **biarkan** — struktur
itu dibuat ulang sendiri saat unggahan pertama, dan menghapusnya tak
menghemat apa pun.

### 2c. Jejak sesi & antrean (bukan data, bukan konfigurasi)

```sql
USE smartpro_fresh;
DELETE FROM sessions;                  -- semua orang login ulang
DELETE FROM personal_access_tokens;    -- token mobile mati, pasang APK & login ulang
DELETE FROM cache;
DELETE FROM cache_locks;
DELETE FROM jobs;
DELETE FROM failed_jobs;
DELETE FROM job_batches;
```

`notifications` dan `document_feedback_antargl` **tak perlu disebut** — yang
bertaut dokumen sudah ikut hilang di §2a (notifikasi lewat
`data->document_id`, feedback lewat cascade foreign key).

`DELETE`, **bukan `TRUNCATE`.** `TRUNCATE` mereset `AUTO_INCREMENT`, jadi id
lama bisa dipakai ulang oleh baris yang sama sekali berbeda — persis jebakan
yang membuat `smartpro:renew` tak boleh dipakai di sini.

### 2d. Cache framework & log

```bash
php artisan optimize:clear
: > storage/logs/laravel.log
```

---

## 3. Verifikasi hasil pembersihan

```sql
USE smartpro_fresh;
SELECT (SELECT COUNT(*) FROM documents)                          AS dokumen,      -- 0
       (SELECT COUNT(*) FROM attachments)                        AS lampiran,     -- 0
       (SELECT COUNT(*) FROM users)                              AS users,        -- TETAP
       (SELECT COUNT(*) FROM users WHERE photo_path IS NOT NULL) AS foto_user,    -- TETAP
       (SELECT COUNT(*) FROM departments)                        AS dept,         -- 7
       (SELECT COUNT(*) FROM document_types)                     AS jenis,        -- 6
       (SELECT COUNT(*) FROM informasi_kategori)                 AS kategori,     -- 10
       (SELECT COUNT(*) FROM pengaturan)                         AS pengaturan,
       (SELECT COUNT(*) FROM model_has_roles)                    AS peran;        -- = users
```

`peran` harus **sama dengan** `users`. Kalau lebih kecil, ada akun tanpa role
spatie — ia bisa masuk tapi menu-nya kosong, dan gejalanya baru muncul di
tangan orangnya. Tambalnya idempoten:

```sql
INSERT INTO model_has_roles (role_id, model_type, model_id)
SELECT r.id, 'App\\Models\\User', u.id
FROM users u
JOIN roles r ON r.name = u.jabatan
LEFT JOIN model_has_roles m ON m.model_id = u.id AND m.model_type = 'App\\Models\\User'
WHERE m.model_id IS NULL;
```

Lalu `php artisan permission:cache-reset`.

Di disk:

```bash
find storage/app/public/lampiran -type f | wc -l   # 0
find storage/app/pdf-cache       -type f | wc -l   # 0
find storage/app/public/avatars  -type f | wc -l   # TETAP
```

---

## 4. Gerbang kode — semuanya harus hijau sebelum push

```bash
php artisan route:cache    # pernah GAGAL karena closure; lihat catatan di bawah
php artisan config:cache
php artisan view:cache
php artisan optimize:clear # WAJIB — kembalikan laptop ke keadaan dev
```

`route:cache` adalah gerbang yang paling sering roboh: Laravel menolak
men-serialisasi **closure** di berkas rute. Sekali ini pernah terjadi
(`storage/{path}`), dan perbaikannya adalah memindahkan badan closure ke
controller invokable — bukan membuang `route:cache` dari runbook. Setiap rute
baru yang ditulis sebagai closure akan mengulang kegagalan yang sama.

```bash
php artisan test    # ±3 menit; patokan 27 Agu 2026: 602 lulus, 1 dilewati, 0 merah
```

Suite berjalan di atas **`smartpro_uji`** — basis data terpisah berisi 16 akun
kurasi, dengan `DatabaseTransactions`. Pembersihan §2 (yang mengenai
`smartpro_fresh`) tak menyentuhnya sama sekali, dan sebaliknya.

**Aturan NRP di dalam test.** Aktor uji **tidak boleh** ditulis sebagai NRP
harfiah di badan test. Panggil helper `TestCase`: `aktorGl()`, `aktorSh()`,
`aktorDh()`, `aktorPjo()`, `aktorNonStaff()` — tabel `NRP_AKTOR` yang
memetakannya ke NRP **sungguhan**. Kalau seseorang pindah atau keluar, yang
diperbarui satu tabel, bukan 54 berkas.

Dua NRP berbentuk contoh yang **sah** dan jangan diganti: `ADM-0001` (Admin IT)
dan `MD-0001` (Management Development). Keduanya akun **bersama**, bukan
karyawan, jadi memang tak punya NRP — dan keduanya benar-benar ada di produksi.

Dan satu pola yang harus ditolak saat review: `->first()` diikuti `continue`
atau `??` fallback saat akun tak ditemukan. Akun contoh sudah pensiun, jadi
pola itu tidak membuat test lentur melainkan membuatnya **hijau tanpa memeriksa
apa pun**. Pakai `firstOrFail()` — akun yang hilang harus merah, bukan
terlewat.

---

## 5. Audit rahasia — SEBELUM `git push`, bukan sesudah

```bash
git ls-files | grep -iE "\.env|\.sql$"
git ls-files -z | xargs -0 grep -lIE "sk-or-v1|AQ\.A|AIza|BEGIN (RSA|OPENSSH) PRIVATE KEY" 2>/dev/null
```

Yang **boleh** muncul di hasil: `.env.example` (nol nilai), `docs/*.sql` yang
memang skema, dan berkas test yang memakai kunci palsu. Selain itu: berhenti,
periksa satu per satu.

Yang **harus** terbukti tidak ter-track: `.env`, dump produksi
(`u805399352_smartpro_new.sql`), `*.zip`.

Pola `.env` di `.gitignore` menutup `.env`, `.env.backup`, `.env.production`,
`.env.testing` — tetapi **tidak** menutup nama karangan seperti
`.env.cadangan-uji`. Kalau membuat salinan `.env` dengan nama baru, tambahkan
namanya ke `.gitignore` pada saat yang sama, bukan nanti.

Terakhir, di `.env` produksi (di server, bukan di repo):

- `APP_ENV=production` · `APP_DEBUG=false` · `APP_URL=https://…`
- `APP_KEY` **tidak** disalin dari laptop tanpa alasan — lihat §7.

---

## 6. Konfigurasi surel

Kodenya sudah env-driven seluruhnya; yang diperiksa di sini adalah bahwa
kodenya masih menyediakan katup yang dipakai runbook.

```bash
grep -n "failover\|'timeout'\|paksa_ke" config/mail.php
```

Harus ada ketiganya:

| Yang dicari | Kenapa |
|---|---|
| mailer `failover` → `['smtp', 'log']` | SMTP tumbang tidak boleh menghilangkan surelnya diam-diam |
| `'timeout' => env('MAIL_TIMEOUT')` | pernah dipatok `null` = tanpa batas; satu SMTP yang menggantung mengunci pekerja antrean |
| `'paksa_ke' => env('MAIL_PAKSA_KE')` | katup uji; **harus disengaja keadaannya**, bukan tertinggal |

Nilai `.env`-nya sendiri diperiksa **di server**, langkah 4 `RUNBOOK-RILIS.md`.
Satu aturan yang gampang terlewat: `MAIL_TIMEOUT` harus **lebih kecil** dari
`DB_QUEUE_RETRY_AFTER` (90), kalau tidak satu surel bisa terkirim dua kali.

`.env` lokal memuat blok produksi Hostinger dalam keadaan **dikomentari**,
lengkap dengan `MAIL_PASSWORD` sungguhan. Itu aman selama `.env` tak pernah
ter-track (§5) — dan itulah satu-satunya yang menjaganya.

---

## 7. Konfigurasi AI

```bash
grep -n "'ai'" -A 30 config/services.php    # semuanya env(), nol nilai tertanam
grep -nE "AI_|GEMINI|OPENROUTER" .env.example   # semua nilainya harus KOSONG
```

**Dua sumber kunci, dan keduanya sah:**

1. **`.env`** — `GEMINI_API_KEY` / `OPENROUTER_API_KEY` / `AI_CADANGAN_API_KEY`.
2. **Tabel `pengaturan`** — diisi Admin dari layar Konfigurasi Sistem,
   tersimpan **tersandi** (`Crypt::encryptString`).

`AppServiceProvider` memakai `bind`, bukan `singleton`, dan closure-nya membaca
`Pengaturan::ai()` **saat di-resolve**. Kunci yang baru disimpan langsung
dipakai permintaan berikutnya — tak perlu `config:cache` ulang.

> ⚠️ **Jebakan yang hanya muncul saat pindah server.** Nilai tersandi di
> `pengaturan` terikat pada `APP_KEY`. Kalau produksi memakai `APP_KEY` yang
> berbeda dari laptop, baris `ai.*.key` hasil ekspor **tidak bisa didekripsi di
> sana** — gejalanya bukan pesan galat yang jelas, melainkan AI yang diam. Dua
> jalan keluar, pilih satu dengan sadar: samakan `APP_KEY`, atau **ketik ulang
> kuncinya dari layar Konfigurasi Sistem di produksi** (lebih disarankan).

Sesudah rilis, buktikan dari layar — jangan menebak:
**Konfigurasi Sistem → Uji koneksi AI** (timeout 15 detik sendiri, tak
membangkitkan tinjauan). Audit log mencatat `pengaturan.ai_diuji` berisi
penyedia + model + berhasil/gagal, **tidak pernah kuncinya**.

Catatan: sejak perbaikan 504 (§ Analisis AI), Analisis AI berjalan lewat
**antrean** (`AnalisisAi` job) — request web selesai seketika (202), panel
menjemput hasilnya berkala. Syaratnya `php artisan queue:work` HARUS berjalan
(cron `--stop-when-empty --max-time=55` tiap menit di hosting, lihat
`docs/PANDUAN-PRODUKSI-EMAIL.md`); tanpa itu panel menyerah sesudah 5 menit dan
menyarankan tinjauan manual — **bukan** 504. `AbstractAiReviewer::TIMEOUT_DETIK
= 180` tetap dikalibrasi pada JSA nyata, jangan diturunkan.

---

## 8. Aplikasi mobile (`c:/xamppnew/htdocs/SmartPro-mobile`)

Folder ini **bukan repo git** — tak ada riwayat, tak ada undo. Salin berkas
yang hendak disentuh sebelum mengubahnya.

```bash
grep -n "defaultValue" lib/config/api_config.dart
grep -rn "usesCleartextTraffic" android/app/src/main/AndroidManifest.xml
grep -n "^version:" pubspec.yaml
grep -rn "http://\|https://" lib | grep -v api_config.dart    # tak boleh ada host lain
```

Tiga hal yang **harus bergerak bersama** — satu tertinggal = aplikasi mati
total, bukan setengah jalan:

| Berkas | Produksi | Lokal |
|---|---|---|
| `api_config.dart` → `host` | `https://adw.proworkppa.com` | `http://<IP-laptop>:9090` |
| `AndroidManifest.xml` → `usesCleartextTraffic` | `"false"` | `"true"` |
| `.env` backend → `APP_URL` | **sama persis** dengan `host` | sama persis |

`pubspec.yaml` → naikkan `version:` tiap kali `host` berubah. Build lama yang
menunjuk basis data lain tak boleh tertukar dengan yang baru.

**Urutannya mengikat: pasang APK BARU hanya SESUDAH server selesai dirilis.**
Selama domain masih melayani build lama, aplikasi tidak gagal terhubung — ia
gagal **diam-diam**: layar terbuka, isinya milik basis data yang berbeda.
Pembuktiannya lima detik, tanpa memasang apa pun:

```
GET https://adw.proworkppa.com/api/notifications
  401 atau 200 → SmartPro sudah di sana
  404          → masih build lama, TUNGGU
```

---

## 9. Membandingkan basis data lokal dengan dump produksi

Dipakai saat hendak menarik data dari produksi (`u805399352_smartpro_new.sql`),
atau sekadar memastikan tak ada yang tertinggal.

**Jangan impor dump ke `smartpro_fresh`.** Impor ke basis data sekali pakai:

```bash
"C:/xamppnew/mysql/bin/mysql.exe" -uroot -e "CREATE DATABASE smartpro_bandingan"
"C:/xamppnew/mysql/bin/mysql.exe" -uroot smartpro_bandingan < u805399352_smartpro_new.sql
```

Lalu bandingkan lewat `information_schema` — tabel, kolom, dan jumlah baris:

```sql
-- tabel yang hanya ada di satu sisi
SELECT table_schema, table_name FROM information_schema.tables
WHERE table_schema IN ('smartpro_fresh','smartpro_bandingan')
GROUP BY table_name HAVING COUNT(*) = 1;

-- kolom yang hanya ada di satu sisi
SELECT table_schema, table_name, column_name FROM information_schema.columns
WHERE table_schema IN ('smartpro_fresh','smartpro_bandingan')
GROUP BY table_name, column_name HAVING COUNT(*) = 1;

-- NRP yang belum ada di lokal
SELECT p.id, p.name, p.nrp, p.jabatan, p.department_id, p.status
FROM smartpro_bandingan.users p
LEFT JOIN smartpro_fresh.users l ON l.nrp = p.nrp
WHERE l.id IS NULL;
```

Selesai: `DROP DATABASE smartpro_bandingan`.

**Tiga aturan yang tak boleh dilanggar saat mengimpor data produksi:**

1. **`users.id` produksi TIDAK PERNAH dipakai.** Id 667 di produksi adalah
   Angga (GL ICTMD); id 667 di lokal adalah orang yang sama sekali berbeda.
   Memakainya berarti **menimpa akun yang tak ada hubungannya**. Biarkan
   `AUTO_INCREMENT`, dan petakan setiap kolom yang menunjuk user lewat **NRP**.

2. **Id tabel LAIN boleh dipertahankan — asal tabel tujuannya kosong.** Ini
   bukan pengecualian yang longgar melainkan pilihan yang lebih aman: kalau
   `documents.id` dipetakan ulang, seluruh `document_contents`,
   `review_annotations`, `approvals`, dan `document_reads` butuh peta kedua —
   dan peta kedua adalah peta yang suatu hari tidak sepakat dengan yang
   pertama. Periksa dua hal sebelum memutuskan: tabel tujuan **kosong**, dan
   `AUTO_INCREMENT`-nya sudah **di atas** id tertinggi yang masuk.

3. **`INSERT` polos, bukan `INSERT IGNORE`,** di dalam `START TRANSACTION`.
   `IGNORE` menelan galat foreign key dan pemotongan kolom — persis yang tak
   boleh terjadi pada data orang.

**Pola pemetaannya, dan kenapa bukan tabel peta tulis-tangan** — `INSERT …
SELECT` lintas basis data mengerjakan pemetaannya sendiri:

```sql
INSERT INTO smartpro_fresh.<tabel> (…, user_id, …)
SELECT …, lu.id, …
FROM smartpro_bandingan.<tabel> x
JOIN smartpro_bandingan.users pu ON pu.id  = x.user_id   -- id produksi → NRP
JOIN smartpro_fresh.users     lu ON lu.nrp = pu.nrp;     -- NRP → id lokal
```

`JOIN` penuh untuk kolom **wajib**: NRP yang tak terpetakan membuat barisnya
hilang, sehingga hitungan verifikasi menangkapnya. `LEFT JOIN` **hanya** untuk
kolom yang memang boleh NULL — dan justru karena itu NRP-nya wajib dibandingkan
baris per baris sesudahnya, sebab pemetaan yang gagal di sana menulis NULL
diam-diam.

**Verifikasinya jangan berhenti di hitungan tabel.** Hitungan yang cocok cuma
membuktikan barisnya ada, bukan bahwa aplikasi bisa membacanya. Muat tiap
dokumen lewat Eloquent beserta relasinya, lalu **render PDF-nya** — contoh
lengkapnya di Lampiran B.

---

## 10. Lanjut ke rilis

Sesudah §1–§8 hijau: `git push`, lalu buka **`docs/RUNBOOK-RILIS.md`** dan
kerjakan langkah 1–12 di server.

---

## Lampiran A — Catatan eksekusi 27 Agustus 2026

Angka-angka di bawah adalah keadaan nyata pada tanggal itu, disimpan sebagai
pembanding untuk pembersihan berikutnya.

**Yang dihapus (§2):**

| | Sebelum | Sesudah |
|---|---|---|
| `documents` (5 draft uji: "Test QuillJS", "testing", "sedfv", …) | 5 | 0 |
| `attachments` / berkas / ukuran | 57 / 58 / **13 MB** | 0 / 0 / 0 |
| `document_contents` · `document_authors` | 33 · 5 | 0 · 0 |
| `storage/app/pdf-cache` | 4 berkas | 0 |
| `sessions` · `personal_access_tokens` · `cache` | 1 · 4 · 14 | 0 · 0 · 0 |
| `storage/framework/views` · `laravel.log` | 1,3 MB · 976 KB | 25 KB · 0 |

Satu lampiran yatim tertinggal sesudah purger jalan
(`lampiran/ICTMD/SOP/uji_tempel.png`, 1,2 KB, tanpa baris `attachments`) —
dibuang manual sesudah dipastikan tak dirujuk kode mana pun. **Inilah alasan
§2b ada.**

**Yang dipertahankan:** `users` 116 (5 berfoto) · `avatars` 22 berkas ·
`departments` 7 · `document_types` 6 · `informasi_kategori` 10 ·
`pengaturan` 6 · `model_has_roles` 116 · `audit_logs` 434.

**Gerbang kode:** `route:cache`/`config:cache`/`view:cache` **lolos**;
`--filter="ManajemenUserDanPemusnahan|KonfigurasiSistem"` → **34 lulus,
128 assertion**.

**Selisih terhadap `u805399352_smartpro_new.sql`** (dump ini lebih baru
daripada yang dicatat `PLAN-PREPRODUKSI-v9.md` §3 — 113 user, bukan 112;
4 dokumen, bukan 9):

- Skema lokal adalah **superset penuh** produksi. Hanya-di-lokal:
  tabel `access_profiles`, `pengaturan`, `informasi_kategori`,
  `informasi_reads`, `document_feedback_antargl`; kolom
  `users.access_profile_id`, `documents.{arsip_path_asli, nonaktif_oleh,
  nonaktif_tahap, obsolete_reason}`, `departments.is_active`,
  `approvals.kind`. Tak ada satu pun kolom produksi yang absen di lokal.
- **9 migration belum jalan di produksi** — persis daftar `RUNBOOK-RILIS.md` §3.
- `departments` id 1–7 dan `roles` id/nama **identik** di kedua sisi.
- Permission hanya-di-lokal: `document.feedback_respond`.
- Isi dump: `users` 113 · `documents` 4 · `document_contents` 19 ·
  `document_authors` 4 · `reviews` 2 · `review_annotations` 68 ·
  `approvals` 1 · `audit_logs` 318 · `notifications` 29 ·
  `model_has_roles` 128. **Kosong:** `attachments`, `informasi`,
  `document_versions`, `user_off_days`, `jobs`, `failed_jobs` — jadi tak ada
  foto lampiran produksi yang perlu ditarik.
- **Hanya 1 user produksi yang belum ada di lokal:**
  `NOVTANDHI PANANLULUY · NRP 230256 · staff · PRODUKSI · status pending ·
  tanpa email`.
- **4 user lokal tak ada di produksi** (akun uji): `20260219`, `24006332`,
  `250504`, `GLSHE-0001`.

---

## Lampiran B — Impor data produksi, 28 Agustus 2026 (SELESAI)

Keputusan pemilik: bawa **1 user yang kurang** + **4 dokumen produksi** beserta
seluruh jejaknya. `audit_logs`, `notifications`, `sessions`,
`personal_access_tokens`, dan `cache` **tidak** ikut.

### Cara yang dipakai — pemetaan lewat NRP, bukan salin id

Seluruh impor dikerjakan sebagai **satu `INSERT … SELECT` lintas basis data
dalam satu transaksi**, bukan `INSERT` bernilai harfiah. Bedanya menentukan:
setiap kolom yang menunjuk user di-JOIN ke `smartpro_fresh.users` **lewat NRP**,
jadi pemetaan id terjadi sendiri dan tak ada tabel peta yang perlu ditulis
tangan — apalagi disalin dua kali.

```sql
-- polanya, dipakai untuk documents / authors / reviews / approvals / reads
INSERT INTO smartpro_fresh.<tabel> (…, user_id, …)
SELECT …, lu.id, …
FROM smartpro_bandingan.<tabel> x
JOIN smartpro_bandingan.users pu ON pu.id  = x.user_id   -- id produksi → NRP
JOIN smartpro_fresh.users     lu ON lu.nrp = pu.nrp;     -- NRP → id lokal
```

`JOIN` penuh untuk kolom yang **wajib** (mis. `created_by`): NRP yang tak
terpetakan membuat barisnya HILANG, dan hitungan verifikasi menangkapnya.
`LEFT JOIN` hanya untuk kolom yang memang boleh NULL (`reviewer_id`,
`approver_id` pada draft) — dan justru karena itu NRP-nya dibandingkan satu per
satu sesudahnya.

**`documents.id` & `reviews.id` justru DIPERTAHANKAN** (1177–1181, 119–120).
Tabel tujuannya kosong dan `AUTO_INCREMENT`-nya sudah 1223, jadi tak ada yang
bisa bentrok — dan mempertahankannya membuat 19 `document_contents`, 68
`review_annotations`, serta `approvals`/`document_reads` ikut apa adanya tanpa
peta kedua. Yang dipetakan ulang hanya **user**, sebab hanya di situlah id
produksi berarti orang yang berbeda.

### Yang diperiksa SEBELUM dijalankan

| Pemeriksaan | Hasil |
|---|---|
| Tabel/kolom produksi yang absen di lokal | **nol** — lokal superset penuh |
| Beda kolom `documents` | 4 kolom lokal-saja, semuanya nullable def NULL |
| Beda kolom `approvals` | `kind` NOT NULL tapi ber-**default `'pengesahan'`** — dan itu memang artinya yang benar untuk baris ini |
| `document_types.id` & `departments.id` | **identik** di kedua sisi, disalin apa adanya |
| Semua NRP pelaku ada di lokal | **7 dari 7** (`ADM-0001`, 18064116, 16071367, 23000651, 18074307, 16081467, 19020361) |
| Rujukan berkas di `value_json` | **nol** (`lampiran/`, `storage/`, `.png`, `.jpg`, `data:image`) |
| `attachments` produksi | **kosong** → tak ada berkas yang perlu ikut disalin |
| Indeks unik `documents.doc_number` | tak ada → nol risiko bentrok nomor |

### Hasil

| Tabel | Produksi | Lokal sesudah impor |
|---|---|---|
| `documents` | 4 | **4** |
| `document_contents` | 19 | **19** |
| `document_authors` | 4 | **4** |
| `reviews` / `review_annotations` | 2 / 68 | **2 / 68** |
| `approvals` / `document_reads` | 1 / 2 | **1 / 2** |
| `users` | 113 | **117** (116 + NRP 230256) |
| `model_has_roles` | — | **117** — nol user tanpa peran |

Verifikasi yang dijalankan, dan semuanya cocok: NRP pembuat/peninjau/penyetuju
tiap dokumen dibandingkan **baris per baris** dengan produksi; pelaku
`authors`/`reviews`/`approvals`/`reads` idem; nol anotasi yatim.

**Uji fungsional, bukan hanya hitungan tabel.** Keempat dokumen dimuat lewat
Eloquent beserta relasinya tanpa galat, lalu **dirender ke PDF sungguhan** —
keempatnya menghasilkan berkas `%PDF` yang sah, termasuk
`PPA-ADRO-JSA-PRODUKSI-01` (507 KB, status `published`, jadi seluruh TTD-nya
bercap APPROVED).

User baru masuk sebagai `id 796 · NOVTANDHI PANANLULUY · NRP 230256 · staff ·
PRODUKSI · status pending · access_profile_id NULL`, dengan peran spatie
`staff` terpasang.

### Satu akibat yang disengaja, jangan dikira bug

`PPA-ADRO-SOP-SHE-**03**` dan `-**09**` masuk tanpa 01, 02, 04–08. Itu bukan
data rusak: `DocumentNumberService::firstUnusedSeq` memang **mengisi celah**,
jadi SOP SHE berikutnya akan bernomor 01. Pilihan sadar pemilik produk — arsip
bersih lebih penting daripada nomor yang unik sepanjang sejarah.

---

## Lampiran C — Utang yang dibawa masuk (dicatat, sengaja belum dilunasi)

| # | Utang | Keputusan |
|---|---|---|
| **U1** | `.env.cadangan-uji` **ter-track** sejak commit `a00eaa5` ("DeployVerBeta", 9 Agu 2026), memuat `APP_KEY` sungguhan, `GEMINI_API_KEY` (`AQ.Ab8…`), dan `OPENROUTER_API_KEY` (`sk-or-…`). Pola `.env` di `.gitignore` tidak menangkap nama ini. | **Dibiarkan atas keputusan pemilik 27 Agu 2026 — dicatat saja.** Selama repo privat, dampaknya terbatas pada siapa pun yang punya akses klon. Begitu repo dibagikan lebih luas, kunci ini **wajib** dirotasi lebih dulu. |
| **U2** | `docs/data-migrasi.sql` ter-track dengan 9 hash bcrypt akun contoh (`GLSHE-0001@gmail.com` dkk). Bukan data karyawan. | Dibiarkan (`PLAN-PREPRODUKSI-v9.md` §8d). |
| **U3** | ~~310 test merah karena NRP contoh~~ — **sudah lunas.** `PLAN-PREPRODUKSI-v9.md` §5 masih memperingatkannya, tapi peringatan itu **kedaluwarsa**: `PLAN-AKSES-v8.md` §11 T4 sudah ✅, dan suite penuh dijalankan ulang 27 Agu 2026 → **602 lulus, 1 dilewati, 0 merah**. | Jangan teruskan klaim "310 test merah". Kalau ragu, jalankan `php artisan test` — itu 3 menit, dan lebih murah daripada merilis atas dasar catatan basi. |
| **U5** | Satu akun rekaan tersisa: `GLSHE-0001` (GL_KEDUA) di `tests/TestCase.php::NRP_AKTOR_KEDUA`. Ia ada di `smartpro_uji` & `smartpro_fresh`, **tidak** di produksi. | Penggantinya sudah ada di dunia nyata (`18125648`, GL ICTMD kedua) tapi belum ada di `smartpro_uji`. Menambah baris ke basis data test = keputusan pemilik. Ditandai `ponytail:` di berkasnya. |
| **U4** | Ekstensi `intl` belum terpasang di produksi (`PLAN-AKSES-v8.md` §11 T6). | Kode baru tetap **dilarang** memakai `Illuminate\Support\Number::`. |

---

## Lampiran D — Catatan eksekusi 2 September 2026 (rencana pra-produksi Fase 13)

Dijalankan di laptop, **sebelum** `git push`. Yang dikerjakan di sini hanya
**verifikasi**: nol pembersihan data, nol perubahan kode. §1 (cadangan) dan §2
(bersihkan data pengembangan) sengaja **belum** dijalankan — keduanya
destruktif dan menunggu perintah pemilik (CLAUDE.md §4).

### Gerbang kode (§4) — semuanya HIJAU

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | 0 galat |
| `npm run build` | selesai 2m 5s, nol peringatan gagal |
| `npm run uji-render` | selesai 3.14s |
| `php artisan smartpro:uji-render` | 28 halaman Inertia (pohon V1), nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman Inertia (pohon V2), nol galat |
| `php artisan test` | **743 lulus, 1 dilewati, 0 merah** (6.787 asersi, 280s) |
| `php artisan route:cache` | sukses — nol closure di berkas rute |
| `php artisan config:cache` | sukses |
| `php artisan view:cache` | sukses |
| `php artisan optimize:clear` | sukses — laptop kembali ke keadaan dev |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** (sesudah bless Fase 12; lihat `C:\baseline-smartpro\CATATAN.txt`) |
| `php artisan smartpro:cetak-baseline` | **11/11 "sama"** |
| `git diff components.json` | kosong |

### Audit rahasia (§5)

`git ls-files | grep -iE "\.env|\.sql$"` → `.env.cadangan-uji`, `.env.example`,
`docs/data-migrasi.sql`, `docs/schema-baseline.sql`. Nol `*.zip`, nol dump
produksi.

Sapuan pola kunci menemukan empat berkas; tiga **sah**:

| Berkas | Isi | Nilai |
|---|---|---|
| `docs/PLAN-BUTIR-15-MOBILE.md:261` | teks `sk-or-v1-…` | contoh bentuk, bukan kunci |
| `tests/Feature/KonfigurasiSistemTest.php:425` | `'AIza-rahasia-sekali-'.Str::random(8)` | kunci palsu bangkitan |
| `docs/CHECKLIST-PREPRODUKSI.md:204` | pola grep-nya sendiri | — |
| **`.env.cadangan-uji`** | `APP_KEY` + `GEMINI_API_KEY` + `OPENROUTER_API_KEY` sungguhan | **U1 — masih terbuka** |

### Cron (§13a rencana) — nol yang baru

Terbukti **tidak ada scheduler Laravel** sama sekali: `app/Console/Kernel.php`
tidak ada, `routes/console.php` 6 baris (`inspire` saja), `bootstrap/app.php`
tak memanggil `->withSchedule()`. Kedua cron produksi tetap **cron OS murni**
persis seperti `docs/RUNBOOK-RILIS.md:238-247`. Jangan mencari `schedule:run`.

### Surel (§6) — yang bisa diperiksa dari laptop

`config/mail.php:67` sudah membaca `MAIL_TIMEOUT`; `config/queue.php:43`
memakai `DB_QUEUE_RETRY_AFTER` dengan bawaan **90**; mailer `failover`
terdefinisi (`config/mail.php:101`). `.env` **laptop** memakai `MAIL_MAILER=smtp`,
`QUEUE_CONNECTION=database`, dan **tidak** memuat `MAIL_TIMEOUT` — jadi
timeout-nya `null` (tanpa batas). Itu wajar untuk dev, **tetapi** `.env`
produksi wajib `MAIL_MAILER=failover` dan `MAIL_TIMEOUT=15`; tanpanya satu SMTP
yang menggantung lebih lama dari `retry_after=90` membuat email terkirim
**dua kali**. Diperiksa di server, bukan di sini.

### Kontrak mobile (§13c rencana) — nol tersentuh

`git status` atas `routes/api.php` dan `app/Http/Resources/` **kosong**: tak
satu pun dari dua belas fase menyentuhnya. Dua hal yang wajib diperiksa karena
Fase 1 menggeser ambang revisi 4→5:

- `DocumentResource.php:34-35` mengirim `edisi` dan `no_revisi` **apa adanya** —
  nol asumsi "≤ 4" di sisi server.
- `UserResource.php:36` mengirim `'role' => $this->jabatan`, yaitu kunci mentah
  (`staff` tetap `staff`). Sesuai CLAUDE.md §6.

Sisa §8 (`api_config.dart`, `usesCleartextTraffic`, bump `pubspec.yaml`) baru
bergerak **sesudah** server hidup — RUNBOOK §12.

### Dua keputusan pemilik yang menahan `git push`

| # | Isi | Yang dibutuhkan |
|---|---|---|
| **U1** | `.env.cadangan-uji` masih ter-track (sejak `a00eaa5`), memuat tiga kunci sungguhan. `.gitignore` sudah menutup pola `.env.*`, tapi berkas yang **sudah** terlacak tak ikut terbatalkan. | Rotasi ketiga kunci + `git rm --cached .env.cadangan-uji`? Atau tetap ditahan selama repo privat (keputusan 27 Agu diteruskan)? Riwayat git tetap memuatnya apa pun pilihannya. |
| **13.1** | ±80 Blade yatim (94 berkas `resources/views`, 14 di antaranya wajib tinggal), `axios` di `devDependencies`, dan `Blade::if('role')` (`AppServiceProvider.php:119`) — semuanya nol pemakai sejak Fase 12 migrasi. | Dihapus sekarang, atau sesudah `docs/CEKLIS-MATA-PER-PERAN.md` selesai? Penghapusan butuh konfirmasi eksplisit (CLAUDE.md §4). |

### Yang BELUM dijalankan dan sengaja begitu

§1 cadangan basis data · §2 pembersihan data pengembangan
(`smartpro:hapus-dokumen`, lampiran yatim, sesi/antrean) · §3 verifikasi hasil
pembersihan · §7 pengisian kunci AI produksi · §9 pembandingan dump produksi ·
**pemeriksaan mata per peran** (`docs/CEKLIS-MATA-PER-PERAN.md`, ketujuh peran).
Semuanya menuntut perintah atau kehadiran pemilik.
