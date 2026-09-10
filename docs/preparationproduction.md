# Plan: Persiapan Upload Produksi Hostinger — Mail/AI/Mobile + Impor Dokumen ADWPROWORK + Bersih-bersih Non-Produksi

## Context

Pemilik mau upload `new-app` ke Hostinger (`adw.proworkppa.com`) via **zip upload + extract** (bukan `git pull` — beda dari asumsi `docs/RUNBOOK-RILIS.md`). Sebelum upload, ada 4 concern:

1. Mail server siap jalan begitu di-upload (cron sudah disiapkan pemilik).
2. API AI + mobile app masih/akan terhubung.
3. Kosongkan dokumen di web lokal, ganti dengan data dokumen dari `ADWPROWORK.sql` (dump nyata) — **WAJIB lapor temuan dulu sebelum merge**.
4. Pindahkan file yang tak dipakai produksi ke folder `non_production/` (folder sudah ada, kosong) — **WAJIB lapor & tanya dulu sebelum pindah**.

Riset sudah dijalankan (read-only) untuk menjawab §1-3 dan menyiapkan bahan laporan §3-4. Temuan di bawah ini yang akan dipakai fase-fase eksekusi. Dua keputusan pemilik sudah diambil: **deploy via zip-upload** (bukan git pull → RUNBOOK perlu bagian baru), dan **wipe audit_logs hanya baris ber-`document_id`** (log non-dokumen tetap).

**Aturan tiap fase** (CLAUDE.md §5, tetap berlaku): satu-dua langkah kecil → berhenti → laporkan → tunggu review/persetujuan. Tidak menerka: kalau ada keputusan pemilik yang belum jelas saat eksekusi, tanya dulu. Tidak explore di luar yang diperlukan fase itu. Tidak test berlebihan (jalankan filter test yang relevan saja, bukan full suite, kecuali di gerbang akhir sebelum zip).

---

## Temuan riset (sudah diverifikasi, bukan tebakan)

**Concern 1 — Mail:** Kode sudah siap (dijawab tuntas di `PLAN-PREPRODUKSI-v9.md` §2 & `RUNBOOK-RILIS.md` §4). Tiga baris cron yang pemilik kirim **cocok persis** dengan `docs/PANDUAN-PRODUKSI-EMAIL.md` §Fase 4 + `RUNBOOK-RILIS.md` §7/§8 (queue:work tiap menit, queue:prune-failed harian, storage symlink). Tidak ada cron baru yang perlu ditambah. Yang wajib diperiksa di server: `.env` produksi (`MAIL_MAILER=failover`, `MAIL_FROM_ADDRESS`==`MAIL_USERNAME`, `MAIL_TIMEOUT=15`, `APP_URL=https://`, `MAIL_PAKSA_KE` sesuai niat) — **BUKAN** disalin dari `.env` lokal (lokal masih Mailpit, `MAIL_MAILER=smtp` ke `127.0.0.1:1025`).

**Concern 2 — AI:** Live-ping barusan (read-only, `Http::timeout` pendek) ke kedua provider **BERHASIL**: Gemini (utama) dan OpenRouter (cadangan) sama-sama OK dari `pengaturan` lokal saat ini. Jadi AI **sudah berfungsi di lokal**. Yang menentukan produksi: `pengaturan` table di DB **produksi** (bukan kode) — kalau DB produksi masih punya config lama yang rusak (persis kondisi di `ADWPROWORK.sql`: `ai.key` kosong, `ai.cadangan.model='chatgptnew'` — id model bukan yang sah), AI produksi akan mati sampai Admin isi ulang dari layar Konfigurasi Sistem + tombol **Uji Koneksi AI**. Ini konsisten dengan `PLAN-PREPRODUKSI-v9.md` §2 & `planpreproduction.md` Fase 3.

**Concern 2 — Mobile:** `SmartPro-mobile/lib/config/api_config.dart` **sudah** mengarah ke `https://adw.proworkppa.com` (bukan IP lokal lagi), `usesCleartextTraffic=false`, `pubspec.yaml=1.0.0+4`. Ketiganya **sudah sesuai** RUNBOOK §12 — tidak ada perubahan kode mobile yang tersisa. Yang tersisa hanya verifikasi setelah server hidup: `GET /api/notifications` harus 200/401 (bukan 404).

**Concern 3 — `ADWPROWORK.sql`:**
- File di root repo, 843 KB, **tidak** ter-track git (kena pola `/*.sql`). Berisi data nyata (nama, email, hash bcrypt asli) — bukan fixture, harus diperlakukan sensitif.
- 37 tabel. Tabel kunci: `documents` 41 baris, `document_contents` 177, `document_versions` 11, `reviews` 19, `approvals` 11, `audit_logs` 824, `attachments` 49, `users` 118.
- **Skema cocok 1:1** dengan lokal untuk `document_contents`, `document_versions`, `reviews`, `approvals`, `attachments`, `attachment_comments`, `document_authors`, `document_feedback`, `document_feedback_antargl`, `document_reads`, `audit_logs`. `documents` di lokal punya **2 kolom ekstra** (`salin_arsip_at`, `tanggal_revisi`, keduanya nullable) yang tidak ada di dump — aman, akan ikut NULL.
- `departments` (id 1-7) dan `document_types` (id 1-6, SOP/IK/SP/JSA/FK/PX) **identik persis** kedua sisi (id maupun code) — **tidak perlu remap** untuk dua FK ini.
- `users`: NRP dump vs lokal **0 selisih** (118 = 118, sama persis) — impor user v9 sudah tuntas & sinkron. **Tapi `id`-nya beda** (auto-increment lokal ≠ id dump), jadi **semua FK ber-relasi ke user** (created_by, reviewer_id, approver_id, document_authors.user_id, document_versions.created_by, attachment_comments.user_id, document_feedback.user_id/replied_by, document_feedback_antargl.user_id, document_reads.user_id, audit_logs.user_id) **WAJIB di-remap via NRP**, tidak boleh bawa id mentah.
- **Temuan penting — kenapa harus WIPE, bukan merge-append:** 27 dokumen lokal saat ini adalah data uji coba (creator id 5/7/729/730/751/768 — akun dev), TAPI `doc_number`-nya **bentrok langsung** dengan dump (`SOP-SHE-03`, `SOP-SHE-09`, `SOP-ICTMD-01..10`, `JSA-PRODUKSI-01..09` — semua muncul di kedua sisi). Kalau di-append apa adanya, dua dokumen ber-nomor sama akan hidup berdampingan → melanggar asumsi keunikan nomor dan bikin dasbor/`isUnique()` kacau. Permintaan pemilik "kosongkan dulu" sudah tepat — bukan merge, tapi **replace penuh** tabel dokumen+turunannya.
- Perlu juga: `documents.id` dan self-reference `revises_document_id` di-remap (dump id lama → id baru lokal), konsisten di semua tabel anak yang punya `document_id`.
- **Keputusan pemilik:** `audit_logs` yang dihapus hanya baris ber-`document_id` (824 di dump vs sebagian dari 640 di lokal yang `document_id IS NOT NULL`); log non-dokumen (login, manajemen user, dst.) di lokal tetap dipertahankan.

**Concern 4 — file non-produksi (inventaris awal, BELUM dieksekusi):**
| Item | Ukuran | Status git | Catatan |
|---|---|---|---|
| `new-app.rar` | 270 MB | **untracked, TIDAK di-gitignore** | risiko: bisa ke-`git add -A` tanpa sengaja; jelas tak boleh ikut ke zip upload |
| `ADWPROWORK.sql`, `u805399352_smartpro_new.sql`, `inputed_smartpro_new (1).sql`, `smartpro_shadcn.sql` | 191 KB–843 KB | gitignored (`/*.sql`) | dump kerja, bukan untuk hosting |
| `backup-db/` | ~368 KB | gitignored | dump audit-log lama |
| `pindah-pc/` | ~58 KB | **tracked**, ada modifikasi belum commit | catatan kerja pribadi, contoh yang disebut pemilik sendiri |
| `template_{sop,sp,ik,jsa}.blade.php` (root) | 7–26 KB | untracked | **CLAUDE.md §17 bilang JANGAN commit, tapi tetap referensi visual cetak yang boleh disimpan lokal** — bukan otomatis "hapus/pindah" |
| `.env.cadangan-uji` | 1.6 KB | **tracked**, berisi secret asli | utang lama U1 (`CHECKLIST-PREPRODUKSI.md`), sudah ada keputusan pemilik sebelumnya "dibiarkan selama repo privat" — jangan disentuh tanpa konfirmasi ulang eksplisit |
| `docs/data-migrasi.sql`, `docs/schema-baseline.sql` | tracked | **JANGAN dipindah** — dipakai skrip audit rahasia di `CHECKLIST-PREPRODUKSI.md`, sudah keputusan pemilik diterima |
| `docs/dasbor_ref/`, `docs/pdflama/`, `docs/REDESIGN-2026/`, `docs/dokumen/`, `docs/login/` | belum dicek isinya | — | perlu dicek dulu isinya sebelum diusulkan pindah/tidak |
| `DAFTAR INDUK DOKUMEN SOP PPA SITE ADARO INDONESIA.xls` | 4.5 KB | belum dicek tracking | file data nyata di root, tak disebut di CLAUDE.md manapun — perlu ditanyakan |

Ini baru inventaris kasar (dari eksplorasi awal). Fase khusus di bawah akan memperdalam lalu **melaporkan + bertanya per-item sebelum memindahkan apa pun** (sesuai permintaan eksplisit pemilik).

---

## Konteks tambahan: kerjaan yang sudah ada tapi BELUM di-commit

`git status` menunjukkan 17 file berubah + `app/Jobs/` baru (untracked) yang secara koheren adalah implementasi **"AI berjalan lewat antrean"** (job `AnalisisAi`, perubahan `ReviewController`/`MdReviewController`/`PanelAi.tsx`/`AbstractAiReviewer.php`/`Pengaturan.php`, plus update `RUNBOOK-RILIS.md` & `CHECKLIST-PREPRODUKSI.md` yang sudah menyebut mekanisme ini). File test yang ikut berubah (`AiReviewTest`, `DasborV2Test`, `DashboardWidgetTest`) sudah disesuaikan. Ini persis pekerjaan yang diminta di-commit dulu sebelum mulai ("sebelum mulai lakukan commit").

---

## Fase eksekusi (1-2 langkah tiap kali, berhenti untuk review)

### Fase 0 — Commit pekerjaan berjalan
- Jalankan test filter yang relevan saja: `--filter=AiReview`, `--filter=DasborV2`, `--filter=DashboardWidget` (bukan full suite).
- Commit `app/Http/Controllers/{DashboardController,MdReviewController,ReviewController}.php`, `app/Models/Pengaturan.php`, `app/Services/Ai/AbstractAiReviewer.php`, `app/Jobs/AnalisisAi.php` (baru), ketiga TSX dasbor, `PanelAi.tsx`, `routes/web.php`, 3 file test, dan doc yang relevan (`RUNBOOK-RILIS.md`, `CHECKLIST-PREPRODUKSI.md`, `docs/ai/Instruksi AI.md`).
- **TIDAK ikut commit:** `pindah-pc/notes progress 1 sept.txt` (dibahas terpisah di Fase non-produksi), `new-app.rar` (bukan untuk di-git sama sekali).
- Berhenti, laporkan hasil test + commit hash, tunggu review.

### Fase 1 — Laporan tertulis ADWPROWORK.sql + rencana remap (TIDAK mengubah DB)
- Sudah banyak temuan di atas; fase ini merapikannya jadi laporan final (dan verifikasi kecil yang belum: cek `document_types`/isi schema tak berubah, spot-check 2-3 baris `documents` dump untuk pastikan `status` enum & `department_id`/`document_type_id` valid).
- Rancang urutan remap: build `nrp -> local_user_id` map, load `ADWPROWORK.sql` ke DB staging sementara (`adwprowork_staging`, dibuang setelah selesai) via client mysql XAMPP supaya baca datanya pakai query SQL biasa (bukan regex fragile seperti dicoba tadi), lalu tulis command Artisan sekali-pakai (transaksi, rollback-safe) yang: hapus dokumen+turunan+audit_logs(document_id not null) lokal → insert ulang dari staging dengan id & FK user di-remap.
- **STOP WAJIB** — laporkan rencana detail + hasil hitung akhir (berapa baris tiap tabel yang akan terhapus/masuk) ke pemilik, tunggu persetujuan eksplisit sebelum Fase 2 jalan. Backup dulu (`mysqldump` tabel-tabel yang disentuh) sebagai bagian laporan ini juga.

### Fase 2 — Eksekusi wipe + impor (HANYA setelah Fase 1 disetujui)
- Backup tabel tersentuh (kalau belum di Fase 1).
- Jalankan command Artisan sekali-pakai di dalam transaksi.
- Verifikasi: jumlah baris cocok, satu dokumen contoh dibuka di UI, `isUnique()`/nomor dokumen tak bentrok, satu PDF ter-generate.
- Bersihkan DB staging.

### Fase 3 — Update `RUNBOOK-RILIS.md` untuk metode zip-upload
- Tambah bagian baru: daftar **exclude wajib** saat membuat zip (`node_modules`, `.git`, `.env` lokal dan semua `.env.*` kecuali `.env.example`, semua `*.sql` root, `non_production/`, `storage/app/public` isi lokal, `new-app.rar`/`.zip`, `pindah-pc/`), urutan extract+setting di hosting (upload zip → extract → **isi `.env` produksi dari nol/edit di server, JANGAN ikut ter-upload** → `composer install` kalau vendor tak ikut zip → migrate → config/route/view:cache → queue:restart → storage:link).
- Tidak menyentuh bagian mail/mobile yang sudah benar.

### Fase 4 — Inventaris & pemindahan file non-produksi
- Cek isi `docs/dasbor_ref/`, `docs/pdflama/`, `docs/REDESIGN-2026/`, `docs/dokumen/`, `docs/login/`, dan file xls di root (belum dicek di riset awal).
- Susun daftar final kandidat pindah ke `non_production/` dengan alasan per item, ikut tabel di atas.
- **STOP** — laporkan daftar final, tanya per-item yang ambigu (terutama `new-app.rar`, `.env.cadangan-uji`, `pindah-pc/`, file xls), tunggu keputusan pemilik.
- Baru pindahkan (git mv untuk yang tracked, mv biasa untuk untracked) setelah disetujui.

### Fase 5 — Gerbang akhir sebelum zip
- `docs/CHECKLIST-PREPRODUKSI.md` dijalankan (sudah ada isinya) — backup DB, bersihkan data uji, `php artisan test` full, `route:cache`+`config:cache`+`view:cache`, audit rahasia.
- Verifikasi manual: tombol Uji Koneksi AI di layar, satu email uji.

---

## File/berkas kritikal

- `docs/RUNBOOK-RILIS.md`, `docs/CHECKLIST-PREPRODUKSI.md` — diperbarui Fase 3.
- Command Artisan baru (satu file, one-off, nama disepakati saat Fase 1, mis. `app/Console/Commands/ImporDokumenAdwprowork.php`) — Fase 1-2.
- `.gitignore` — mungkin tambah pola `*.rar` (Fase 4, akan ditanyakan dulu).
- Tidak ada perubahan ke daftar HARAM §14b (mesin cetak) di seluruh rencana ini.

## Verifikasi akhir end-to-end

1. Login satu akun hasil impor dokumen, buka salah satu dokumen ADWPROWORK, PDF ter-generate benar (kop, halaman, pengesahan).
2. `php artisan test` penuh hijau di angka patokan CLAUDE.md §14c saat ini.
3. Zip project (sesuai exclude-list baru) → ukurannya masuk akal (tidak 270MB+ lagi) → upload manual dicoba di staging/local extract dulu untuk sanity check sebelum benar-benar ke Hostinger (opsional, tanya pemilik).
4. Tombol Uji Koneksi AI hijau, satu email uji sampai, `GET /api/notifications` dari APK baru menjawab 200/401.
