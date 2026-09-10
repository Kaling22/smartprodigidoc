# FASE E — Sambungkan Backend Nyata

> Menaungi: `RENCANA-EKSEKUSI.md` E.13–E.14. Prasyarat: Fase C dan D selesai DAN `DATA-KONTRAK.md`
> terisi lengkap untuk semua layar — fase ini mengeksekusi daftar itu, bukan menerka ulang dari
> nol. Logika bisnis TIDAK ditulis ulang; yang berubah hanya kulit tampilan (sudah dikerjakan Fase
> B–D) dan penambahan method turunan yg dibutuhkan tampilan baru.

---

## E.13 — Port model, migrasi, service, controller

**Tujuan.** Setiap layar yg sudah dibangun dgn data statis (Fase C–D) sekarang menampilkan data
sungguhan dari database, lewat logika bisnis yg SAMA PERSIS dgn `smartprorefactor_fresh` — hanya
dibungkus ulang untuk cocok dgn bentuk data yg diminta komponen baru.

**Rujukan.** `DATA-KONTRAK.md` (spesifikasi lengkap per layar), inventarisasi model di bagian
"Referensi cepat" berkas yg sama.

**Langkah.**
1. **Migrasi & model** — salin migrasi dari `smartprorefactor_fresh/database/migrations` apa
   adanya (struktur tabel tidak berubah krn redesign ini UI-only). Salin model
   (`Document`, `DocumentContent`, `DocumentVersion`, `Review`, `Approval`, `Attachment`,
   `DocumentFeedback`, `User`, `Department`, `DocumentType`, `UserOffDay`, `AuditLog`,
   `JobExecution`, `Informasi`, `DocumentAuthor`, dst).
2. **Peta rupa** — `Document::STATUS_META`, `DocumentType::RUPA` di app lama menyimpan
   `[warna, ikon bi-*]`. Bangun ulang isinya sbg `config/status.php` / `config/document-type.php`
   (dibuat kerangkanya di Fase B.4) dgn NILAI YG SAMA tapi ikon di-map ke Lucide memakai tabel di
   akhir `00-FONDASI.md`. Model lama tetap bisa menyimpan status sbg string — hanya lapisan
   presentasinya pindah ke config.
3. **`app/Notifications/DocumentNotification.php`** — port apa adanya, ganti hanya nama ikon
   (`bi-*` → `lucide-*`) via peta yg sama.
4. **Policy, Gate, FormRequest** — port apa adanya (`document.review`, `document.approve`,
   `document.review_jsa`, `audit.view`, dst — semua aturan RBAC di CLAUDE.md §6 tidak berubah).
5. **Service** — port apa adanya: `PdfRenderer`, `DocumentWizard`, `DocumentParticipantResolver`,
   `DocumentService::nextEditionRevision`, resolver ketersediaan peninjau
   (`saringPeninjauTakTersedia`, `tolakBilaPeninjauPenuh`), `DocumentExportController` (export
   SH/DH).
6. **Controller** — port struktur route dari `routes/web.php` lama (grup: auth, dashboard,
   documents create/lifecycle, review, approvals, nonaktif, users/admin, informasi, audit,
   job-executions) — sambungkan tiap route ke VIEW BARU (Fase C/D), bukan view lama.
7. **Tutup tiap butir BARU di `DATA-KONTRAK.md`** satu per satu:
   - Deret waktu dashboard (Overview 14hari/8minggu/8bulan) → method baru di service statistik,
     query agregat harian dari `Document.created_at`/`published_at`.
   - Growth % dashboard → hitung dari deret di atas, bukan tabel baru.
   - Matriks status×jenis → query agregat `GROUP BY status, document_type_id`.
   - Cakupan baca terendah & persentase distribusi → cek dulu apakah backend lama punya tabel log
     baca; kalau tidak, buat migrasi baru minimal (jangan desain berlebih — kolom seperlunya saja).
   - Badge angka `(n)` navigasi per peran → count query ringan per item, dipertimbangkan cache
     pendek (mis. cache 60 detik per user) supaya tidak membebani tiap page-load.
8. **Kalau menemukan kontrak yg SALAH tafsir** (mis. asumsi "BARU" ternyata sudah ada di backend
   lama, atau sebaliknya) — perbaiki `DATA-KONTRAK.md` supaya tetap akurat sbg dokumentasi, jangan
   dibiarkan menyimpang diam-diam.

**Verifikasi.** Tiap layar dibuka ulang dgn data DB nyata (bukan lagi statis), dibandingkan visual
terhadap versi Fase C/D — TIDAK ada perubahan tata letak, hanya angka/isi yg berbeda. Uji dgn user
sungguhan tiap peran (GL/SH/DH/PJO/MD/Non-Staff/Admin) utk verifikasi RBAC & visibilitas menu.

---

## E.14 — Cetak PDF

**Tujuan.** Keluaran PDF resmi tetap identik byte-per-layout dgn app lama — ini di LUAR cakupan
redesign secara sengaja (lihat `02-PENUTUP.md` § "Di luar cakupan").

**Rujukan.** `docs/COVER-CETAK-KONFIGURASI.md`, `docs/JSA-CETAK-KONFIGURASI.md`,
`tests/Feature/PrintLayoutTest.php` (di app lama).

**Langkah.**
1. Salin apa adanya: `resources/views/documents/print/*` (`render`, `render-jsa`, `_cover`, `_kop`,
   `_kop_jsa`, `_catatan_revisi`, `_pengesahan`, `_footer`, `arsip-catatan`) dan
   `resources/views/emails/dokumen.blade.php`.
2. Salin `tests/Feature/PrintLayoutTest.php` apa adanya.
3. Jalankan `php artisan test --filter=PrintLayoutTest` di proyek baru — HARUS lulus tanpa
   modifikasi test-nya. Kalau gagal, penyebabnya ada di penyambungan data (E.13), bukan di
   template cetak — jangan mengubah template utk "memperbaiki" test.

**Verifikasi.** Bandingkan satu PDF SOP dan satu PDF JSA hasil proyek baru vs proyek lama secara
visual (halaman demi halaman) — identik.
