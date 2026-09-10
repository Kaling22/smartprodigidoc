# RUNBOOK RILIS — SmartPro ke Hostinger (`adw.proworkppa.com`)

> Dari `docs/PLAN-PREPRODUKSI-v9.md` Fase 4. Sisi **surel** tidak disalin ke
> sini — ia sudah lengkap di `docs/PANDUAN-PRODUKSI-EMAIL.md`; runbook ini
> menunjuk ke sana, bukan menggandakannya (dua salinan prosedur = satu yang
> diperbarui dan satu yang menyesatkan).

**Sifat rilis ini:** produksi masih menjalankan build LAMA. Sembilan migration
belum pernah jalan di sana, dan aplikasi mobile masih menunjuk IP laptop.

**Urutan langkahnya mengikat.** Yang paling sering terlewat justru yang paling
sunyi akibatnya: langkah 6 (`queue:restart`) dan langkah 4 (memeriksa `.env`
**sebelum** `config:cache`, bukan sesudah).

---

## 0. Prasyarat

| Hal | Nilai |
|---|---|
| Domain | `https://adw.proworkppa.com` |
| Akar aplikasi | `/home/uXXXXXXX/domains/adw.proworkppa.com/public_html` |
| Basis data | `u805399352_smartpro_new` |
| PHP | 8.2+ |

Suite penuh **wajib hijau** sebelum berangkat:

```powershell
php artisan test
```

Keadaan 30 Agu 2026 (sesudah migrasi shadcn, Fase 13): **694 lulus, 1 dilewati,
0 gagal** (5772 asersi). Angka 535 di catatan 26 Agu berasal dari sebelum
migrasi. Kalau ada yang merah, berhenti di sini — jaring pengaman penomoran,
ekspor, alur tinjau–setuju, dan tata letak cetak PDF ada di suite itu.

Sejak migrasi shadcn, dua gerbang **frontend** ikut wajib hijau sebelum
berangkat. Keduanya memeriksa hal yang berbeda dan tak saling menggantikan:

```powershell
node_modules/.bin/tsc --noEmit      # tipe
npm run build                       # penyusunan modul
npm run uji-render                  # bundel SSR untuk gerbang di bawah
php artisan smartpro:uji-render     # RENDER sungguhan, 29 halaman
```

Perintah terakhir yang paling penting dan paling mudah dianggap berlebihan:
ketiga yang di atasnya memeriksa tipe dan penyusunan modul, **tak satu pun
merender**. Halaman yang melempar galat saat digambar lolos semuanya dan sampai
ke pengguna sebagai layar putih. Sudah terjadi sekali (Fase 3); perintah itu
lahir karenanya.

---

## 1. Cadangkan produksi LEBIH DULU

hPanel → Databases → phpMyAdmin → Export, atau:

```bash
mysqldump -u u805399352_xxx -p u805399352_smartpro_new > cadangan-produksi-YYYYMMDD.sql
```

Simpan **di luar server**. Ini prasyarat, bukan saran: langkah 3 menyentuh
skema, dan langkah 11 (rollback) tak punya arti tanpa berkas ini.

Cadangkan juga `storage/app/public/` (foto profil & lampiran) bila belum ada
salinannya — migration tidak menyentuhnya, tapi `public_html` yang ditimpa
bisa.

---

## 2. Ambil kode & pasang dependensi

```bash
cd /home/uXXXXXXX/domains/adw.proworkppa.com/public_html
git pull
composer install --no-dev --optimize-autoloader
```

`--no-dev` bukan sekadar penghematan: paket dev memuat perkakas yang tak pernah
perlu ada di server yang menghadap internet.

> `tightenco/ziggy` ada di `require`, **bukan** `require-dev` — sudah diperiksa.
> Kalau kelak ia berpindah, `--no-dev` akan mematikan `@routes` di
> `resources/views/app.blade.php` dan setiap halaman ikut mati.

---

## 2b. Bangun aset frontend — LANGKAH BARU sejak migrasi shadcn

**Tanpa langkah ini setiap halaman mati.** Sejak Fase 1, seluruh antarmuka
dirender React lewat Inertia, dan `resources/views/app.blade.php` memanggil
`@vite([...])`. Direktif itu membaca `public/build/manifest.json`; kalau berkas
itu tidak ada, Laravel melempar `ViteManifestNotFoundException` — dan yang
terlihat pengguna adalah **500 di semua rute sekaligus**, bukan halaman tanpa
gaya. Rilis-rilis sebelum migrasi tidak punya langkah ini karena lapisan lama
murni CDN.

**`public/build` ada di `.gitignore`, jadi `git pull` TIDAK membawanya.** Ini
disengaja (artefak build tak masuk riwayat), dan konsekuensinya harus dijawab
di salah satu dari dua cara:

**Cara A — bangun di laptop, unggah hasilnya (dianjurkan untuk shared hosting).**

```powershell
npm ci                # bukan `npm install` — mengunci ke package-lock.json
npm run build         # menghasilkan public/build/
```

Lalu unggah **seluruh isi** `public/build/` ke
`.../public_html/public/build/` lewat hPanel File Manager atau SFTP. Timpa
penuh, jangan digabung: nama berkas ber-hash, dan sisa build lama yang tak
tertimpa hanya memakan tempat tanpa pernah terpakai.

**Cara B — bangun di server**, hanya bila `node -v` di SSH menjawab v20+.
Shared hosting Hostinger lazimnya tidak menyediakannya, dan `npm ci` menarik
~300 MB `node_modules` yang tak ada gunanya di server produksi:

```bash
npm ci && npm run build && rm -rf node_modules
```

Sesudahnya, periksa satu berkas — bukan sekadar keberadaan foldernya:

```bash
php -r 'echo file_exists("public/build/manifest.json") ? "manifest OK\n" : "MANIFEST HILANG\n";'
```

> `npm run uji-render` menulis ke `bootstrap/ssr/`, yang juga di-`.gitignore`.
> Itu **hanya** bahan gerbang `smartpro:uji-render` di laptop; produksi tak
> pernah membutuhkannya, jadi jangan diunggah.

---

## 2c. Bila mengunggah ZIP dari laptop, BUKAN `git pull`

Cara yang dipakai pemilik sekarang: zip folder `htdocs/new-app` di laptop,
unggah lewat hPanel File Manager, extract di `public_html`. Langkah 2 dan 2b di
atas tetap berlaku isinya; yang berubah cuma cara kode sampai ke server.

**Yang WAJIB dikeluarkan dari zip.** Empat baris pertama bukan soal ukuran
berkas melainkan soal produksi yang rusak diam-diam:

| Jangan ikut | Alasan |
|---|---|
| `.env` dan semua `.env.*` | `.env` laptop menunjuk Mailpit `127.0.0.1:1025` dan DB lokal. Menimpanya di server = seluruh email masuk `failed_jobs` tanpa satu pun galat di layar. **Ini persis kegagalan yang terjadi pada rilis 2026-09-08.** |
| `public/storage` | Di server ini symlink ke `storage/app/public`; di Windows ia folder biasa berisi lampiran lokal. Menimpanya memutus symlink dan menyembunyikan seluruh lampiran produksi. |
| `storage/` | Berisi lampiran, arsip PDF, dan log PRODUKSI. Zip laptop akan menimpanya dengan data uji. |
| `bootstrap/cache/*.php` | Cache konfigurasi laptop. Kalau ikut, `config:cache` di server belum tentu menimpanya sebelum permintaan pertama masuk. |
| `node_modules/`, `.git/` | Tak dipakai produksi, ratusan MB. |
| `*.sql` di akar, `backup-db/`, `pindah-pc/`, `non_production/`, `*.rar`, `*.zip` | Dump berisi data nyata + catatan kerja. Tak boleh menghadap internet. |
| `vendor/` | Hanya bila `composer` tersedia di SSH server (langkah 2). Kalau tidak ada, `vendor/` justru WAJIB ikut. |

`public/build/` **harus ikut** — lihat langkah 2b, jalankan `npm run build` di
laptop sebelum mengezip.

**Urutan di server sesudah extract** (SSH, dari `public_html`):

```bash
PHP=/opt/alt/php85/usr/bin/php
grep -n '^APP_ENV\|^APP_URL\|^MAIL_HOST\|^MAIL_SCHEME' .env   # pastikan .env PRODUKSI, bukan laptop
composer install --no-dev --optimize-autoloader                  # lewati bila vendor/ ikut zip
$PHP artisan migrate --force
$PHP artisan config:clear && $PHP artisan config:cache
$PHP artisan route:cache && $PHP artisan view:cache
$PHP artisan queue:restart
ls -l public/storage                                             # harus '-> ../storage/app/public'
```

`queue:restart` bukan opsional: pekerja antrean lama memegang kode DAN
konfigurasi lama di memori sampai dihentikan (langkah 6).

Verifikasi cepat bahwa email tidak kembali ke Mailpit:

```bash
$PHP artisan config:show mail | grep -E 'host|port|scheme|default'
$PHP artisan tinker --execute='echo "jobs=".DB::table("jobs")->count()." | failed=".DB::table("failed_jobs")->count()."\n";'
```

`host` harus `smtp.hostinger.com`. Kalau `127.0.0.1`, berhenti dan perbaiki
`.env` — jangan lanjut ke verifikasi rilis.

---

## 3. Sembilan migration yang belum pernah jalan di produksi

```bash
php artisan migrate
```

**TIDAK PERNAH** `migrate:fresh` maupun `migrate:refresh` (CLAUDE.md §4) —
keduanya mengosongkan tabel, dan di produksi itu berarti 112 akun dan seluruh
riwayat hilang.

Yang akan dijalankan (diverifikasi dengan membandingkan `database/migrations`
terhadap tabel `migrations` di dump produksi: 40 di repo, 31 sudah jalan):

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

Dua catatan yang menentukan:

- **`create_informasi_kategori_table` meng-INSERT kesepuluh kategori DI DALAM
  migration**, bukan lewat seeder. Tabel kosong berarti menu Informasi lenyap —
  jadi migration ini wajib benar-benar **tuntas**, bukan sekadar "tidak error".
  Periksa: `SELECT COUNT(*) FROM informasi_kategori;` harus **10**.
- **`create_pengaturan_table` sengaja TIDAK di-seed.** Tabel kosong = prefix
  `PPA-ADRO` diambil dari kode, sama persis dengan perilaku produksi sekarang.
  Kosong itu benar, bukan terlewat.

---

## 4. Periksa `.env` produksi — SEBELUM `config:cache`, bukan sesudah

Urutan ini bukan gaya: `config:cache` membekukan nilai yang ADA saat itu.
Memperbaiki `.env` sesudahnya tidak mengubah apa pun sampai cache dibangun
ulang, dan gejalanya adalah setelan yang "sudah benar di berkas" tapi tak
berlaku.

| Kunci | Nilai | Kalau salah |
|---|---|---|
| `APP_ENV` | `production` | — |
| `APP_DEBUG` | `false` | jejak galat lengkap **termasuk isi .env** tampil ke siapa pun yang memicu error |
| `APP_URL` | `https://adw.proworkppa.com` | tautan & logo di email menunjuk alamat salah |
| `MAIL_MAILER` | `failover` | relay tumbang = email hilang, bukan jatuh ke log |
| `MAIL_FROM_ADDRESS` | **sama persis** dengan `MAIL_USERNAME` | relay menolak: pengirim tak cocok dengan yang login |
| `MAIL_TIMEOUT` | `15` | harus **< `DB_QUEUE_RETRY_AFTER`** (bawaan 90, `config/queue.php:43`). Kalau lebih besar, antrean menganggap pekerjaan mati lalu mengulangnya — satu email terkirim dua kali |
| `QUEUE_CONNECTION` | `database` | kanal mail tak pernah jalan (`DocumentNotification::viaConnections()`) |
| `MAIL_PAKSA_KE` | **disengaja**, bukan tertinggal | selama terisi, SELURUH email dibelokkan ke satu alamat dan tak seorang penerima pun sadar |

`MAIL_PAKSA_KE` adalah katup peralihan sehari (`docs/PANDUAN-PRODUKSI-EMAIL.md`).
Nyalakan sengaja saat uji pertama; **kosongkan sengaja** saat sudah yakin.
Keadaannya juga terbaca di kartu Kesehatan (langkah 9), jadi tak perlu SSH untuk
mengetahuinya.

---

## 5. Bangun cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`route:cache` diuji lolos pada Laravel 12.63 termasuk rute `storage.public`
(v9 Fase 3a). Kalau salah satu gagal, **jangan diteruskan** — aplikasi akan
berjalan dengan cache separuh jadi.

---

## 6. `queue:restart` — langkah yang paling gampang terlupa

```bash
php artisan queue:restart
```

Pekerja antrean memegang kode LAMA di memori. Tanpa ini, notifikasi masih
diproses oleh build sebelumnya sampai pekerjanya kebetulan mati sendiri —
gagal yang tak menimbulkan satu pun pesan galat.

---

## 7. `storage:link` bila memungkinkan

```bash
php artisan storage:link
```

Kalau hosting melarang symlink, **tak ada yang perlu dilakukan**: rute
`storage.public` (`PublicStorageController`) memang jaring pengamannya — foto
tetap tampil, disajikan PHP. Symlink hanya membuatnya lebih murah, bukan
membuatnya mungkin.

---

## 8. Dua cron — tetap seperti kemarin, TIDAK ADA yang baru

v9 tidak menambah satu pun peristiwa berkirim email (masukan sejawat sengaja
`penting = false` — lonceng saja), jadi kedua baris di
`docs/PANDUAN-PRODUKSI-EMAIL.md` §Fase 4 berlaku apa adanya:

```
* * * * * cd /home/uXXXXXXX/domains/adw.proworkppa.com/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> storage/logs/queue.log 2>&1

0 3 * * * cd /home/uXXXXXXX/domains/adw.proworkppa.com/public_html && /usr/bin/php artisan queue:prune-failed --hours=168
```

Kalau keduanya sudah terpasang dari rilis sebelumnya: **biarkan**. Yang berubah
di rilis ini hanyalah kewajiban `queue:restart` (langkah 6).

**Baris pertama itu wajib, bukan pelengkap.** Notifikasi email dikirim lewat
antrean (`DocumentNotification::viaConnections`) supaya aksi Tinjau/Setujui tak
ikut menunggu SMTP. Tanpa pekerja yang benar-benar berjalan, email **mengendap
di tabel `jobs`** dan tak pernah terkirim — tanpa satu pun pesan salah. Lonceng
tetap muncul karena kanal `database` memang sinkron, jadi kegagalannya separuh
dan justru sulit disadari: pengguna melihat notifikasi, dan menyimpulkan email
memang tidak dikirim by design. Alasan yang sama membuat `jalankan.bat` di
laptop membuka **dua** jendela, bukan satu.

Kalau menu Informasi atau lonceng terasa "hidup" tapi kotak surat sepi,
periksa `SELECT COUNT(*) FROM jobs` lebih dulu — bukan konfigurasi SMTP.

---

## 9. Setelan pasca-rilis dari LAYAR, bukan dari `.env`

Masuk sebagai Admin → **Konfigurasi Sistem**:

1. **Pastikan `php artisan queue:work` berjalan.** Analisis AI kini berjalan
   lewat **antrean** (`AnalisisAi` job), bukan sinkron dari peramban — request
   web selesai seketika (202) dan panel menjemput hasilnya berkala, jadi
   `max_execution_time`/batas proxy shared hosting tak lagi bisa mematikannya
   sebagai 504. Tanpa pekerja antrean yang jalan, panel menyerah sesudah 5 menit
   dan menyarankan tinjauan manual — bukan galat. `AbstractAiReviewer::TIMEOUT_DETIK
   = 180` tetap dikalibrasi pada JSA nyata; model lambat kini hanya memperlambat,
   bukan mematikan.
2. **Periksa kartu Kesehatan.** Mode debug harus terbaca **Mati**; `MAIL_PAKSA_KE`
   sesuai niat langkah 4; mailer bukan `log`.
3. **Tekan "Uji koneksi AI".** Memakai timeout 15 detik sendiri, jadi tetap
   berguna meski model utamanya lambat. Berhasil = penyedia & model disebut;
   gagal = pesan penyedia yang terbaca, bukan angka telanjang.
4. **Tekan "Kirim email uji"** — mendarat di alamat akun Admin sendiri.

Ketiga hal ini setelan **basis data**, bukan `.env`: mengubahnya tidak menuntut
`config:cache` ulang, dan berlaku pada permintaan berikutnya.

---

## 10. Verifikasi rilis

Basis data:

```sql
SELECT COUNT(*) FROM informasi_kategori;   -- 10 (langkah 3)
SELECT COUNT(*) FROM jobs;                 -- 0 sesudah cron menyala
SELECT COUNT(*) FROM failed_jobs;          -- 0
```

Lalu satu alur nyata, berurutan pada **satu** dokumen:

1. GL buat draft → kirim
2. SH tinjau → PJO setujui
3. **Unduh PDF**, periksa kop, `Halaman X dari Y`, dan blok pengesahan
4. Ekspor Daftar Induk
5. Buka menu Informasi — kategorinya terisi
6. Lonceng berbunyi, dan satu email sampai

Langkah 3 disebut tersendiri dengan sengaja: tata letak cetak adalah bagian yang
paling mudah bergeser tanpa menimbulkan galat apa pun.

Ditambah empat pemeriksaan yang **baru ada sejak migrasi shadcn** — semuanya
mustahil gagal di laptop dan hanya bisa gagal di server:

7. **Buka halaman mana pun sambil melihat konsol peramban.** Layar putih
   berkonsol bersih = `public/build` tak sepadan dengan kodenya (langkah 2b);
   500 di semua rute = manifest-nya memang tidak ada.
8. **Klik sidebar sampai halaman ketiga.** Inertia berpindah halaman lewat
   XHR; kalau sebuah rute mengembalikan HTML biasa (mis. karena middleware
   melempar redirect), yang terlihat adalah halaman membeku tanpa galat.
9. **Nyalakan mode gelap, muat ulang halaman penuh.** Satu kedipan putih boleh;
   layar yang bertahan terang berarti skrip anti-FOUC di
   `resources/views/app.blade.php` tak ikut terunggah.
10. **Buka satu URL yang pasti 403** (mis. menu Admin sebagai GL). Halaman galat
    sengaja **bukan** halaman Inertia — ia Blade mandiri
    (`layouts/guest.blade.php`) tanpa CDN dan tanpa `@vite`, supaya tetap
    tergambar justru saat build atau jaringan sedang bermasalah.

---

## 11. Rollback

```bash
git checkout <commit-sebelumnya>
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

**Dan pulihkan `public/build` yang SEPADAN dengan commit itu.** Folder itu tidak
ikut Git (lihat langkah 2b), jadi `git checkout` mengembalikan kode tanpa
mengembalikan asetnya — hasilnya bundel JavaScript baru di atas rute lama. Kalau
commit yang dituju berasal dari sebelum migrasi shadcn, build itu justru tak
boleh ada sama sekali: lapisan lama murni CDN dan tak pernah memanggil `@vite`.

> Karena itu, simpan arsip `public/build/` tiap kali merilis — satu `.zip` per
> rilis, dinamai commit-nya. Membangunnya ulang dari commit lama menuntut
> `node_modules` versi yang sama, dan itu tak dijamin oleh apa pun.

Basis data: pulihkan `mysqldump` langkah 1.

Ke-9 migration itu **aditif** — kolom & tabel baru. Satu-satunya yang menulis
data adalah INSERT kategori Informasi, dan itu pun tidak menimpa apa pun. Jadi
rollback kode **tanpa** rollback basis data aman: build lama sekadar mengabaikan
kolom yang tak dikenalnya.

---

## 12. Sesudah produksi hidup: arahkan aplikasi mobile

**Urutannya mengikat — hanya sesudah langkah 1–10 selesai.** Mengarahkan
aplikasi ke domain yang masih melayani build lama bukan gagal terhubung
melainkan gagal **diam-diam**: layar terbuka, isinya milik basis data lain.

Berkas ada di luar repo ini: `c:/xamppnew/htdocs/SmartPro-mobile/`, dan
foldernya **bukan repo git** — salin dulu berkas yang disentuh.

| Berkas | Perubahan |
|---|---|
| `lib/config/api_config.dart` | komentari `host` lokal (`http://10.121.143.9:9090`), buka komentar blok produksi (`https://adw.proworkppa.com`) |
| `android/app/src/main/AndroidManifest.xml` | `android:usesCleartextTraffic="true"` → **`"false"`**. Selama menyala, satu salah ketik `http://` sudah cukup mengirim token Bearer lewat jaringan polos |
| `pubspec.yaml` | `1.0.0+3` → `1.0.0+4` — build lama tak boleh tertukar dengan yang menunjuk produksi |

Tak ada berkas lain yang disentuh: seluruh layar menyusun URL-nya dari
`ApiConfig.apiUrl` / `publicUrl`.

**Selesai bila:** APK baru dipasang di satu HP, masuk memakai NRP produksi, menu
Informasi terisi, foto profil tampil, dan `GET /api/notifications` menjawab
**200** (bukan 404 seperti build lama).
