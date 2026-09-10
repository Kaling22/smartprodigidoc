# BUTIR 15 — Koneksi SmartPro Web ↔ SmartPro Mobile + Mailpit

> Turunan dari `docs/PLAN-BUTIR-11-17.md`. Dikerjakan **paling akhir**, sesudah
> butir 14, 13, 11, 12, 16, 17 selesai dan di-commit.

---

## 1. Prinsip yang ditetapkan user

> "gunakan lonceng dan pemberitahuan pop up. mobile tidak boleh membuat, dan
> melakukan approving, hanya bisa melihat. kecuali untuk GL SHE dapat meninjau
> JSA dan dapat melihat list pekerjaan JSA yang sudah di setujui. kemudian GL
> dan SH/DH dari dept terkait dapat melihat riwayat pekerjaan nya"

Diterjemahkan jadi aturan yang bisa ditegakkan kode:

| Aksi | Mobile |
|---|---|
| Melihat dokumen berlaku, informasi, daftar induk, distribusi, perjalanan | **boleh** |
| Membuat/mengedit dokumen mutu (wizard) | **tidak** |
| Menyetujui / menolak dokumen (approve) | **tidak** |
| Meninjau JSA (GL SHE & Plant, lintas dept) | **boleh** — sudah ada |
| Melihat daftar JSA berlaku untuk dikerjakan | **boleh** — sudah ada |
| Melaksanakan pekerjaan JSA + centang pengendalian | **boleh** — sudah ada |
| Melihat riwayat pekerjaan bawahan (GL, SH, DH se-dept) | **boleh** — sudah ada |
| Mengirim masukan atas dokumen berlaku (Non-Staff) | **boleh** — sudah ada, kanal terpisah (CLAUDE.md §6) |
| Membaca notifikasi lonceng | **boleh** — **BELUM ADA, ini pekerjaan utama butir ini** |

Catatan penting: "mengirim masukan" **bukan** pelanggaran aturan read-only.
Masukan adalah kanal terpisah (`document_feedback`) yang memang dirancang untuk
Non-Staff; ia tidak mengedit isi, tidak mengubah status, tidak menyetujui.

---

## 2. Kondisi API sekarang (hasil audit, jangan ditulis ulang)

Autentikasi: **Sanctum bearer token**, login pakai **NRP + password**
(`Api\AuthController`). `config/sanctum.php` `'expiration' => null` (token tak
kedaluwarsa). `laravel/sanctum ^4.3` sudah terpasang.

Endpoint yang sudah jalan (`routes/api.php`):

- `POST login` / `POST register` / `POST logout` / `GET me` / `PATCH me` / `POST me/foto`
- `GET documents`, `GET documents/{document}`, `GET files/{jenis}/{berkas}`
- `GET masukan`, `POST documents/{document}/masukan`, `POST masukan/{feedback}/balas`, `POST masukan/{feedback}/adopsi`
- `GET informasi`, `GET informasi/{informasi}/berkas`
- `GET review`, `GET review/{document}`, `POST review/{document}` — gate `can:review-access`
- `POST attachments/{attachment}/komentar`
- `GET/POST/DELETE off`
- `GET perjalanan`, `GET status-dokumen`, `GET distribusi`, `GET daftar-induk/{type}`, `GET dokumen-export/{type}`
- `GET/POST pekerjaan`, `GET pekerjaan/{id}`, `PATCH pekerjaan/{id}/checklist`, `PATCH pekerjaan/{id}/selesai`

Resource: `UserResource`, `DocumentResource`, `FeedbackResource`,
`JobExecutionResource`.

### Yang ternyata SUDAH memenuhi permintaan user — cukup diverifikasi

1. **Daftar JSA berlaku** → `GET /api/documents?type=JSA` sudah menyaring
   `berlaku()`. Verifikasi filter `type` benar-benar ada di
   `DocumentApiController@index`.

2. **Riwayat pekerjaan untuk GL & SH/DH se-dept** → sudah ditangani
   `JobExecutionController::lingkup()`:
   ```php
   return match ($user->lingkupTim()) {
       'sendiri'    => $query->where('user_id', $user->id),
       'departemen' => $query->whereHas('user', fn ($u) => $u->where('department_id', $user->department_id)),
       default      => $query,   // pemegang document.view_all
   };
   ```
   Lingkupnya diambil dari **departemen pelaksana**, bukan departemen dokumen
   JSA-nya — sudah benar (Non-Staff PLANT yang memakai JSA milik SHE tetap
   pekerjaan PLANT, dan atasan PLANT-lah yang perlu melihatnya).
   `show()` memakai penyaring yang sama, jadi tidak ada kartu yang tampil lalu
   403 saat diketuk.
   **Tugas verifikasi:** pastikan `User::lingkupTim()` mengembalikan
   `'departemen'` untuk **group_leader, section_head, dan departemen_head**.
   Kalau salah satu belum, itulah satu-satunya baris yang perlu diubah.

3. **GL SHE meninjau JSA** → `POST /api/review/{document}` dengan gate
   `can:review-access`, batas departemen di `User::canReviewJsa()`.
   Verifikasi lewat `tests/Feature/Api/KontrakMobileJsaTest.php` yang sudah ada.

4. **Larangan approve/create** → audit awal menunjukkan `routes/api.php` memang
   **tidak punya** endpoint create dokumen maupun approve. Tidak ada yang perlu
   dihapus; yang perlu dilakukan adalah **menguncinya** (lihat §4).

---

## 3. Pekerjaan nyata — Notifikasi

### 3.1 Masalah

`app/Notifications/DocumentNotification.php` menulis ke channel `database` dan
docblock-nya menyatakan notifikasi "muncul di lonceng, di web maupun di aplikasi
mobile". Kenyataannya **tidak ada satu pun endpoint `/api/notifications`** —
mobile tidak bisa membaca lonceng sama sekali.

Sisi web ditangani `NotificationController` (`open()` + `readAll()`), tapi
keduanya mengembalikan **redirect**, jadi tidak bisa dipakai ulang oleh mobile.

### 3.2 Yang dibuat

**`app/Http/Resources/NotificationResource.php`** — bentuk stabil, mengikuti
gaya resource lain (kunci Indonesia, seperti `UserResource`/`FeedbackResource`):

```
id, judul, pesan, route, document_id, no_dokumen, dibaca (bool), tanggal (ISO8601)
```

Ambil dari `$notification->data[...]` dengan `?? null`, jangan asumsikan semua
kunci ada — notifikasi lama mungkin bentuknya beda.

**`app/Http/Controllers/Api/NotificationApiController.php`**:

| Method | Route | Perilaku |
|---|---|---|
| `index` | `GET /api/notifications` | Paginasi 20, terbaru dulu. Query `?belum_dibaca=1` menyaring `unreadNotifications`. Respons menyertakan `meta.belum_dibaca`. |
| `unreadCount` | `GET /api/notifications/unread-count` | Hanya `{ "belum_dibaca": n }`. Endpoint murah khusus polling. |
| `read` | `POST /api/notifications/{id}/read` | Padanan `open()` versi API: `findOrFail` di notifikasi milik user sendiri, `markAsRead()`, **kembalikan `route` + `document_id`** sebagai data. **Jangan redirect** — navigasi urusan mobile. |
| `readAll` | `POST /api/notifications/read-all` | `unreadNotifications->markAsRead()`, kembalikan `{ "belum_dibaca": 0 }`. |

Semua `auth:sanctum`. Beri `throttle:60,1` pada `unread-count` karena ia
di-polling.

Otorisasi: cukup `$request->user()->notifications()` — relasi itu sendiri sudah
membatasi ke milik user, jadi tidak perlu Policy tambahan.

### 3.3 Pop-up

Jalan terpendek yang benar adalah **polling + local notification**:

- Mobile memanggil `GET /api/notifications/unread-count` saat app resume dan
  pada interval (mis. 60 detik saat foreground).
- Bila angkanya naik, mobile menarik `GET /api/notifications?belum_dibaca=1`
  dan memunculkan **local notification** (`flutter_local_notifications`).
- Tidak butuh server key, tidak butuh kolom baru di database, tidak butuh
  perubahan pada `DocumentNotification`.

**Push sejati (FCM) sengaja TIDAK dikerjakan di butir ini.** Ia menuntut proyek
Firebase, kredensial layanan, kolom `device_tokens`, dan pekerjaan di sisi
Flutter — itu fase tersendiri. Catat sebagai `ponytail:` di controller:

```php
// ponytail: mobile polling unread-count; pindah ke FCM bila latensi
// notifikasi jadi keluhan nyata (butuh tabel device_tokens + kredensial Firebase).
```

---

## 4. Mengunci aturan read-only

Buat `tests/Feature/Api/BatasMobileTest.php` — test kontrak, bukan test perilaku:

1. Ambil semua rute ber-prefix `api/` dari `Route::getRoutes()`.
2. Tegaskan **tidak ada** rute API yang menunjuk ke:
   - `DocumentController@store|update|submit|destroy|saveStep|autosave`
   - `ApprovalController@*`
   - `NonaktifController@*`
   - `DocumentRevisionController@purge|purgeAll`
   - `InformasiController@store|destroy`
3. Tegaskan seluruh rute API selain `login`, `register`, `documents` publik(?)
   memiliki middleware `auth:sanctum`.

Test ini murah dan menangkap kebocoran privilege di masa depan — persis kelas
bug yang paling mahal kalau baru ketahuan setelah terpasang di HP orang.

---

## 5. Menyelaraskan privilege ke mobile

`UserResource` **sudah** mengirim blok kapabilitas yang dihitung di server:

```
bisa_tinjau, bisa_tinjau_jsa, bisa_beri_masukan, bisa_kerja_jsa,
bisa_ketersediaan, bisa_perjalanan, bisa_status_dokumen, bisa_distribusi,
bisa_daftar_induk, lintas_departemen
```

**Inilah tempat privilege baru diumumkan ke mobile** — bukan lewat endpoint
terpisah, dan bukan dengan mobile menebak dari `role`. Karena banyak sekali
perubahan hak akses sejak versi mobile terakhir, langkah wajib:

1. Bandingkan daftar kapabilitas di `UserResource` dengan menu yang benar-benar
   ada di aplikasi Flutter (`SmartPro-mobile/lib/`).
2. Untuk setiap menu mobile yang tidak punya bendera padanannya, tambahkan
   bendera baru di `UserResource` — **jangan** biarkan mobile menyimpulkan hak
   akses dari `role`, itu sumber divergensi.
3. Tambah `bisa_lihat_notifikasi` hanya bila memang ada peran yang tidak boleh
   melihat lonceng; kalau semua boleh, jangan tambah bendera kosong.
4. Perbarui `tests/Feature/Api/KoneksiMobileTest.php` agar mengunci bentuk
   `UserResource` (kunci apa saja yang wajib ada).

**Kunci mentah `staff` dikirim apa adanya** lewat `UserResource::$role`
(CLAUDE.md §6) — jangan di-rename, itu memecah kontrak aplikasi terpasang.

---

## 6. Mailpit

### 6.1 Kondisi sekarang

`config/mail.php`: `'default' => env('MAIL_MAILER', 'log')`, plus kunci custom
`'paksa_ke' => env('MAIL_PAKSA_KE')` yang dipakai
`AppServiceProvider.php:60` → `Mail::alwaysTo($paksaKe)`.

`.env` yang aktif memakai relay produksi Hostinger:

```
MAIL_MAILER=failover      # failover = ['smtp', 'log'] → relay mati diam-diam jadi log
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
```

Pengirim surel: **satu kelas saja**,
`app/Notifications/DocumentNotification.php`. Tidak ada `app/Mail/`.
Email hanya terkirim bila `penting: true` **dan** penerima punya `email`,
lewat antrean `database`.

### 6.2 Perubahan

`.env` — arahkan ke Mailpit, konfigurasi hosting **di-comment, bukan dihapus**:

```dotenv
# --- Mailpit (pengembangan lokal) ---
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="smartpro@proworkppa.com"
MAIL_FROM_NAME="SmartPro PT PPA"
MAIL_PAKSA_KE=

# --- Produksi Hostinger (AKTIFKAN saat deploy, matikan blok Mailpit di atas) ---
# MAIL_MAILER=failover
# MAIL_HOST=smtp.hostinger.com
# MAIL_PORT=465
# MAIL_USERNAME=smartpro@proworkppa.com
# MAIL_PASSWORD=
# MAIL_ENCRYPTION=ssl
# MAIL_TIMEOUT=15
```

- `MAIL_PAKSA_KE` dikosongkan — Mailpit sudah menangkap semua surel, override
  global jadi mubazir dan malah menyembunyikan penerima sebenarnya.
- `config/mail.php` **tidak diubah** — `MAIL_MAILER` env sudah cukup.
- `.env.example` diperbarui dengan bentuk yang sama.
- `QUEUE_CONNECTION=database`, jadi surel baru terkirim kalau
  **`php artisan queue:work`** jalan. Catat ini di `docs/HANDOVER-SESI-BARU.md`.

Cara menjalankan Mailpit di Windows: unduh `mailpit.exe`, jalankan; SMTP di
`1025`, UI di `http://127.0.0.1:8025`.

### 6.3 Temuan keamanan yang HARUS dilaporkan

`.env` yang aktif memuat, dalam teks polos:

- password SMTP Hostinger `smartpro@proworkppa.com`
- kunci OpenRouter (`sk-or-v1-…`)
- kunci Gemini

Sebelum menyentuh `.env`, jalankan:

```bash
git log --all --oneline -- .env
git check-ignore -v .env
```

Bila `.env` pernah ter-commit, **ketiga kredensial itu harus dirotasi** —
riwayat Git bersifat permanen. Laporkan hasilnya ke user; jangan diam-diam
memperbaiki, dan jangan pernah meng-commit `.env` (CLAUDE.md §4).

---

## 7. Urutan pengerjaan butir 15

1. Cek `git log --all -- .env` → laporkan temuan keamanan. **Berhenti, tunggu user.**
2. Verifikasi `User::lingkupTim()` untuk GL/SH/DH; perbaiki bila perlu (kemungkinan besar tidak).
3. Verifikasi `GET /api/documents?type=JSA` menyaring `berlaku()`.
4. `NotificationResource` + `NotificationApiController` + 4 rute di `routes/api.php`.
5. `tests/Feature/Api/BatasMobileTest.php` (kunci read-only).
6. Selaraskan bendera kapabilitas `UserResource` dengan menu Flutter.
7. Mailpit di `.env` + `.env.example` + catatan di handover.
8. Uji ujung-ke-ujung (§8).

Setiap nomor: berhenti → jelaskan → tunggu review.

---

## 8. Verifikasi

```bash
php artisan route:list --path=api
php artisan test --filter="Api"
```

Manual (Mailpit jalan, `php artisan queue:work` jalan):

1. `curl -s -X POST localhost:8000/api/login -d "nrp=…&password=…"` → dapat token.
2. `curl -s -H "Authorization: Bearer <token>" localhost:8000/api/notifications`
   → JSON berisi `data[]` + `meta.belum_dibaca`.
3. `POST /api/notifications/{id}/read` → `dibaca: true`, dan lonceng di **web**
   ikut berkurang (channel database yang sama).
4. `POST /api/notifications/read-all` → `belum_dibaca: 0`.
5. Login sebagai GL SHE → `GET /api/review` berisi JSA dari departemen SELAIN
   SHE; `GET /api/documents?type=JSA` hanya yang berlaku.
6. Login sebagai SH PLANT → `GET /api/pekerjaan` memuat pekerjaan Non-Staff
   PLANT, dan `GET /api/pekerjaan/{id}` untuk salah satunya **tidak** 403.
7. Setujui satu dokumen dari web → surel `[SmartPro] …` mendarat di
   `http://127.0.0.1:8025`.
8. Coba `POST /api/documents` dan `POST /api/approvals/1` → **404** (rutenya
   memang tidak ada), dan `BatasMobileTest` hijau.
