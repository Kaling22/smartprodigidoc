# PANDUAN PRODUKSI EMAIL — SmartPro (Hostinger)

> File ini sudah dirujuk dari `config/mail.php`, `.env.example`, dan
> `tests/Feature/MailProductionSafetyTest.php` sejak awal, tapi belum pernah
> ditulis. Ini isinya.
>
> Target produksi: `https://adw.proworkppa.com` (Hostinger shared hosting).

---

## 0-A. PENGEMBANGAN LOKAL — Mailpit (butir 15)

`.env` mesin pengembangan sekarang mengarah ke **Mailpit**, bukan relay
Hostinger. Konfigurasi produksinya TIDAK dihapus, hanya di-comment di blok
tepat di bawahnya — aktifkan kembali saat deploy.

```
MAIL_MAILER=smtp   MAIL_HOST=127.0.0.1   MAIL_PORT=1025
```

1. Unduh `mailpit.exe`, jalankan. SMTP di `1025`, kotak masuk di
   <http://127.0.0.1:8025>.
2. **`php artisan queue:work` HARUS jalan.** `QUEUE_CONNECTION=database`, dan
   `DocumentNotification::viaConnections()` sengaja mengantrekan kanal `mail`
   (lonceng tetap sinkron). Tanpa pekerja, lonceng muncul tapi surelnya
   menganggur di tabel `jobs` — gejala yang gampang disalahartikan sebagai
   "SMTP mati".
3. `MAIL_PAKSA_KE` dikosongkan. Mailpit sudah menangkap semua surel, jadi
   `Mail::alwaysTo()` hanya menyembunyikan siapa penerima sebenarnya.

---

## 0. Dua pertanyaan yang ditanyakan ke Hostinger AI — jawabannya

**"Apakah paket email gratis cukup?"** — Cukup. SmartPro tidak mengirim
kampanye; ia mengirim notifikasi alur kerja untuk ±7 departemen, dan itu pun
hanya untuk peristiwa `penting: true` (lihat §1). Perkiraan wajar: puluhan
email per hari, bukan ribuan. Batas paket gratis Hostinger jauh di atas itu.
Yang perlu dijaga bukan volumenya, melainkan **bounce rate**: alamat palsu di
basis data akan memantul dan menjatuhkan reputasi domain (ditangani Fase 5).

**"Apakah PHPMailer best practice?"** — **Tidak, dan jangan dipasang.**
Laravel 12 sudah memakai Symfony Mailer di bawah `Mail::`/`Notification::`,
dan seluruh kode SmartPro sudah berdiri di atasnya. Memasang PHPMailer berarti
menambah dependensi untuk pekerjaan yang sudah dilakukan framework, lalu
menulis ulang 12 titik pemanggilan notifikasi. PHPMailer relevan untuk PHP
tanpa framework — bukan di sini.

**Satu koreksi teknis atas saran Hostinger AI:** `MAIL_ENCRYPTION` **sudah
tidak dibaca Laravel 11/12**. Penggantinya `MAIL_SCHEME` (lihat
`config/mail.php:59`). Menyalin `.env` contoh dari sana apa adanya akan
menghasilkan konfigurasi yang tampak benar tapi TLS-nya tidak sesuai yang
dikira. Nilai yang benar ada di Fase 3.

Saran lain yang tidak perlu dijalankan karena sudah selesai:
`php artisan make:mail TestEmail` (badan email sudah ada), `php artisan
queue:table` + `migrate` (tabel `jobs` sudah ada sejak
`0001_01_01_000002_create_jobs_table.php`).

---

## 1. Hasil audit — yang SUDAH siap produksi

| Bagian | Berkas | Keterangan |
|---|---|---|
| Pemicu & subjek | `app/Notifications/DocumentNotification.php` | Satu kelas, dua kanal. Subjek `[SmartPro] {Aksi} — {no dokumen}` sehingga utas mengelompok sendiri di kotak masuk. |
| Saklar email | idem, `via()` | Email hanya untuk `penting: true` **dan** penerima punya alamat. Lonceng selalu jalan. |
| Badan email | `resources/views/emails/dokumen.blade.php` | CSS inline, tata letak `<table>` (aman di Outlook), kartu dokumen + kotak catatan peninjau + tombol. |
| Performa | idem, `viaConnections()` | Lonceng `sync`, email lewat antrean `database`. Menekan "Loloskan" tidak menunggu SMTP. |
| Katup pengaman | `config/mail.php:34` + `AppServiceProvider::boot()` | `MAIL_PAKSA_KE` membelokkan SELURUH email ke satu alamat. |
| Anti-hilang | `config/mail.php:99` | Mailer `failover` = SMTP → log. Relay mati ≠ isi email lenyap. |
| Uji | `tests/Feature/MailProductionSafetyTest.php`, `NotificationTest`, `NotificationCoverageTest` | Katup & rantai failover dikunci test. |
| Alat uji | `php artisan smartpro:email-uji` | Menanam alamat uji; sudah berpenjaga `APP_ENV=production`. |

Peristiwa yang **masuk email** (`penting: true`) — sudah final, tidak diubah
di rencana ini:

1. Dokumen perlu ditinjau → peninjau (`DocumentService::tandaiTerkirim`)
2. Dikembalikan untuk revisi → penyusun (`ReviewDecision`)
3. Perlu ditinjau Management Development → tim MD (`ReviewDecision`)
4. Perlu disetujui → penyetuju (`ReviewDecision`, `MdReviewController`)
5. Ditolak approver → penyusun + peninjau yang meloloskan (`ApprovalController`)
6. Disetujui, kini Berlaku → penyusun (`ApprovalController`)
7. Dikembalikan MD → penyusun (`MdReviewController`)
8. Revisi diajukan → pemilik draft revisi (`DocumentRevisionController`)

Sisanya (masukan Non-Staff, peninjau off, arsip lama) sengaja **lonceng saja**.

## 2. Hasil audit — yang BELUM siap (4 lubang)

1. **`MAIL_TIMEOUT` mati.** `.env.example:81` mendokumentasikannya, tapi
   `config/mail.php:65` menulis `'timeout' => null` — nilainya tak pernah
   dibaca. Relay yang menggantung akan menahan pekerja antrean tanpa batas.
2. **`MAIL_MAILER=log`** masih bawaan. Produksi harus `failover`.
3. **Antrean tak punya cara jalan di shared hosting.** `queue:work` butuh
   proses yang hidup terus; Hostinger shared tidak punya Supervisor. Tanpa
   Fase 4, seluruh email mengendap di tabel `jobs` selamanya sementara
   lonceng tetap muncul — kegagalan yang paling sulit disadari.
4. **Alamat uji `@ppa-adro.local` masih ada di basis data.** Setiap kiriman
   ke domain itu memantul.

---

# RENCANA — 5 FASE

Aturan main tetap CLAUDE.md §5: satu fase → berhenti → laporkan → tunggu
review + commit.

---

## FASE 1 — Tutup dua lubang kode (3 langkah pendek)

Semuanya di sisi kita, belum menyentuh Hostinger.

1. `config/mail.php:65` — ganti `'timeout' => null` menjadi
   `'timeout' => env('MAIL_TIMEOUT')`, dengan komentar mengapa: pekerja
   antrean tak boleh digantung relay. (`null` tetap jadi nilai jatuh, jadi
   pengembangan lokal tak berubah.)
2. `.env.example` — jelaskan bahwa produksi memakai `MAIL_MAILER=failover`,
   dan ganti komentar `MAIL_ENCRYPTION` warisan bila masih tersisa di mana
   pun (Laravel 12 memakai `MAIL_SCHEME`).
3. Tambah satu assertion di `MailProductionSafetyTest`: `config('mail.mailers.smtp.timeout')`
   mengikuti `MAIL_TIMEOUT`. Tanpa ini lubang no. 1 bisa kembali diam-diam.

**Verifikasi:** `php artisan test --filter=MailProductionSafety` hijau.

---

## FASE 2 — Sisi Hostinger (3 langkah, tanpa sentuh kode)

Dikerjakan di hPanel, hasilnya dicatat untuk Fase 3.

1. **Buat mailbox aplikasi** `noreply@proworkppa.com` (atau
   `noreply@adw.proworkppa.com` bila subdomain yang dipakai). Satu mailbox
   pengirim untuk seluruh sistem — bukan per pengguna. Password dibuat khusus,
   TIDAK masuk Git.
2. **Catat detail koneksi dari hPanel**, jangan menebak: Hostinger Email dan
   Titan Email memakai host berbeda (`smtp.hostinger.com` vs
   `smtp.titan.email`). Catat: host, port (465 atau 587), username persis.
3. **Periksa DNS**: `MX` mengarah ke penyedia mailbox, `SPF` (`v=spf1 include:… -all`)
   ada, `DKIM` aktif. Tambahkan `DMARC` `p=none` dulu supaya laporan masuk
   tanpa memblokir apa pun.

**Verifikasi:** kirim email uji dari webmail Hostinger ke Gmail pribadi; buka
"Show original" di Gmail — `SPF: PASS` dan `DKIM: PASS` harus terbaca.
Kalau di sini sudah gagal, jangan lanjut — masalahnya DNS, bukan Laravel.

---

## FASE 3 — Sambungkan Laravel, uji dengan katup TERTUTUP (1 proses panjang)

Ini fase paling panjang dan paling berisiko; karena itu berdiri sendiri.

**3a. Isi `.env` produksi** (di server, bukan di repo):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adw.proworkppa.com     # WAJIB https — logo & tombol email memakainya

MAIL_MAILER=failover                    # SMTP dulu; kalau mati → log, isi tak hilang
MAIL_HOST=smtp.hostinger.com            # ← ganti sesuai catatan Fase 2
MAIL_PORT=465
MAIL_SCHEME=smtps                       # 465 = smtps. Untuk 587: kosongkan (STARTTLS otomatis)
MAIL_USERNAME=noreply@proworkppa.com
MAIL_PASSWORD="…"
MAIL_FROM_ADDRESS=noreply@proworkppa.com   # HARUS sama dengan MAIL_USERNAME
MAIL_FROM_NAME="${APP_NAME}"
MAIL_TIMEOUT=15
MAIL_EHLO_DOMAIN=adw.proworkppa.com

MAIL_PAKSA_KE=ajipraja471@gmail.com     # KATUP: semua email dibelokkan ke sini
QUEUE_CONNECTION=database
```

Tiga hal yang paling sering salah dan efeknya tidak kelihatan:
`MAIL_FROM_ADDRESS` ≠ mailbox terautentikasi (Hostinger menolak, atau
penerima melihat "via"), `APP_URL` masih `http`/`localhost` (logo dan tombol
mati begitu email dibuka dari HP), dan `MAIL_SCHEME` salah pasangan dengan
port.

**3b. Terapkan & uji lewat `tinker`** — bukan lewat alur dokumen, supaya
kalau gagal kita tahu penyebabnya SMTP, bukan antrean:

```bash
php artisan config:clear && php artisan config:cache
php artisan tinker
>>> Mail::raw('uji relay SmartPro', fn($m) => $m->to('siapa.saja@example.com')->subject('Uji SMTP'));
```

Karena katup terisi, surat itu **tetap** mendarat di `MAIL_PAKSA_KE`, apa pun
alamat tujuannya. Itulah gunanya diuji sekarang dan bukan nanti.

**3c. Bila gagal** — periksa berurutan: `storage/logs/laravel.log` (galat
otentikasi vs koneksi) → port alternatif (465 ↔ 587 dengan `MAIL_SCHEME`
disesuaikan) → apakah Hostinger memblokir SMTP keluar untuk paket tersebut.
Selama `MAIL_MAILER=failover`, kegagalan relay menulis email ke log, jadi
isinya bisa dibaca meski tak terkirim.

**Verifikasi fase:** satu email uji sungguhan diterima di alamat katup, dengan
logo tampil dan tombol "Buka Dokumen di SmartPro" mengarah ke `https://adw.proworkppa.com/...`.

---

## FASE 4 — Antrean jalan di shared hosting (3 langkah)

Tanpa fase ini email tidak pernah keluar, karena kanal mail berjalan di
koneksi antrean (`DocumentNotification::viaConnections()`).

1. **Cron pekerja antrean** di hPanel → Cron Jobs, tiap 1 menit:

   ```
   * * * * * cd /home/uXXXXXXX/domains/adw.proworkppa.com/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> storage/logs/queue.log 2>&1
   ```

   `--stop-when-empty` + `--max-time=55` adalah kuncinya: prosesnya selesai
   sendiri sebelum cron berikutnya menyala, jadi tak pernah ada dua pekerja
   menumpuk. (Kalau paket hosting hanya mengizinkan interval 5 menit, naikkan
   `--max-time` ke 290 — email jadi tertunda maksimal 5 menit, dan itu masih
   wajar untuk notifikasi alur.)

2. **Cron pembersih**, sekali sehari:

   ```
   0 3 * * * cd /home/uXXXXXXX/domains/adw.proworkppa.com/public_html && /usr/bin/php artisan queue:prune-failed --hours=168
   ```

3. **Catat di prosedur rilis**: sesudah setiap `git pull` di produksi wajib
   `php artisan config:cache` **dan** `php artisan queue:restart` — pekerja
   lama memegang kode lama di memori.

**Verifikasi:** jalankan satu alur nyata (GL kirim dokumen ke peninjau),
tunggu ≤2 menit, email sampai di alamat katup. Lalu
`select count(*) from jobs;` harus kembali 0, dan `failed_jobs` kosong.

---

## FASE 5 — Bersihkan alamat, buka katup, pantau (3 langkah)

1. **Bereskan alamat pengguna.** Hitung dulu:
   `select email, count(*) from users where email like '%@ppa-adro.local' group by email;`
   Alamat uji harus dikosongkan sebelum katup dibuka — surat ke domain yang
   tak ada akan memantul dan menjatuhkan reputasi pengirim. Pengguna tanpa
   email tetap menerima lonceng (`via()` menyaringnya, bukan menggagalkannya),
   dan bisa mengisi alamatnya sendiri lewat halaman Informasi Akun / aplikasi
   mobile (`Api\ProfileController::update`).
   > Ini mengubah data. Tunjukkan hitungannya dulu, tunggu persetujuan
   > (CLAUDE.md §4).

2. **Buka katup**: hapus baris `MAIL_PAKSA_KE` dari `.env`, lalu
   `php artisan config:cache`. Mulai titik ini email menuju penerima
   sungguhan.

3. **Pantau seminggu pertama**: `failed_jobs` (kegagalan kirim),
   `storage/logs/laravel.log` (baris email = relay sedang mati), dan laporan
   DMARC. Bila tenang, naikkan DMARC ke `p=quarantine`.

**Verifikasi:** satu alur penuh (kirim → tinjau → setujui) diterima di kotak
masuk tiga orang berbeda, masuk Inbox dan bukan Spam.

---

## Catatan pemeliharaan

- **Jangan menaikkan jumlah email.** Saklar `penting` sengaja pelit: kalau
  semua peristiwa dikirim ke email, orang berhenti membacanya dan justru yang
  genting ikut terlewat. Menambah peristiwa ke email = keputusan produk,
  bukan keputusan teknis.
- **`MAIL_TIMEOUT` (15 s) harus tetap lebih kecil dari `DB_QUEUE_RETRY_AFTER`
  (90 s)**, jika tidak satu email bisa terkirim dua kali karena pekerjaannya
  diambil ulang sebelum percobaan pertama menyerah.
- **Katup itu alat sekali pakai.** Jangan tinggalkan `MAIL_PAKSA_KE` terisi
  "untuk aman-aman" — selama terisi, tak seorang pun pengguna menerima
  emailnya dan tak ada satu pun galat yang muncul.
