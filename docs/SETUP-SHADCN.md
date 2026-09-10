# SETUP — Migrasi UI SmartPro ke shadcn/ui

> Fase 0–3: duplikasi, pondasi, skill/MCP/aturan, kerangka UI.
> Lanjutannya ada di `MIGRASI-SHADCN.md` (Fase 4–13).

## Konteks

`smartprorefactor_fresh` adalah sistem manajemen dokumen mutu PT PPA (site Adaro) yang sudah matang dan dipakai: 44 controller, 26 service, 24 model, 40 migrasi, 78 berkas tes, ~18.000 baris PHP, 92 view Blade (~12.000 baris). Tampilannya Blade + Bootstrap 5 (CDN) + Alpine.js + SweetAlert2, dengan `layouts/app.blade.php` sepanjang 1.283 baris.

Tujuan: mengganti **hanya lapisan tampilan** dengan shadcn/ui — sementara skema JSON, proses pembuatan dokumen, logika bisnis, alur persetujuan, fitur admin, dan hasil ekspor dokumen mutu bertahan **identik**.

Keputusan: duplikat `smartprorefactor_fresh` → `smartproshadcn`, acuan konfigurasi `laravel/react-starter-kit`, pasang skill resmi shadcn + MCP server shadcn, dokumen bertema Soft UI/CSS lama tidak dibawa.

---

## Hasil verifikasi: apa saja yang WAJIB bertahan

Ditelusuri langsung dari kode dan basis data. Daftar ini jadi tulang punggung kedua dokumen.

### 1. Skema JSON — aman total, tidak ada di repo

Skema **tinggal di kolom basis data** `document_types.schema_json`, dibaca `app/Services/SchemaService.php` (105 baris). Tidak ada berkas skema di repo yang bisa tertinggal saat menyalin — `docs/schema-sop.json` hanya arsip referensi. **Kloning basis data = skema utuh.**

Enam jenis dokumen terverifikasi di tabel `document_types`:

| Kode | Nama | Langkah wizard |
|---|---|---|
| SOP | Standard Operating Procedure | 2 |
| IK | Instruksi Kerja | 1 |
| SP | Standar Parameter | 2 |
| JSA | Job Safety Analysis | 2 |
| FK | Formulir Kerja | — (unggahan) |
| PX | Prosedur External | — (unggahan) |

FK dan PX ber-`schema_json` NULL: keduanya jenis **unggahan-saja** (`DocumentType::isUnggahan()`, dipakai `DocumentController@create:159`). Jalur ini gampang terlupa — sudah masuk daftar uji.

Komentar di `SchemaService` menyebut janji arsitektur yang harus dipertahankan:

> *"The SAME schema drives the form, the preview, and the PDF — so structural inconsistency between them is impossible."*

Artinya: form React yang baru **wajib membaca skema yang sama**, bukan menyalin strukturnya jadi TSX statis. Kalau ini dilanggar, menambah jenis dokumen baru tidak lagi cukup dengan menambah skema — dan itu kehilangan fitur, bukan sekadar perubahan tampilan.

### 2. Sepuluh tipe seksi — mesin form yang harus ditulis ulang

Diambil langsung dari `schema_json` di basis data:

| Tipe | Muncul | Renderer Blade sekarang |
|---|---|---|
| `user_picker` | 7x | `fields/_user_picker.blade.php` |
| `text` | 6x | `fields/_text.blade.php` |
| `rich_list` | 5x | `fields/_rich_list.blade.php` |
| `textarea` | 4x | `fields/_text.blade.php` |
| `repeatable_group` | 4x | `fields/_repeatable_group.blade.php` |
| `reference_picker` | 1x | `fields/_repeatable_group.blade.php` |
| `document_picker` | 1x | `fields/_repeatable_group.blade.php` |
| `jsa_analysis` | 1x | `fields/_jsa_analysis.blade.php` |
| `image` | 1x | dalam grup |
| `date` | 1x | dalam grup |

Plus tiga renderer di luar skema: `_signature`, `_revision_log` (langkah virtual Log Revisi), `_papan-ketersediaan` (papan pemilihan peninjau di dalam `user_picker`). Dan tipe field dalam grup: `text_or_image`.

**Delapan berkas di `resources/views/documents/fields/` adalah mesin form SmartPro.** Ini yang jadi delapan komponen React — pekerjaan tersulit sekaligus paling menentukan.

### 3. Mesin cetak — JANGAN DISENTUH SAMA SEKALI

Terverifikasi mandiri penuh dari lapisan tampilan:

```
app/Services/Print/PdfRenderer.php        <- cache sidik + versiKodeCetak()
app/Services/Print/ArsipPdf.php
app/Services/Print/ArsipPenggabung.php
app/Services/ActivityPrintLayout.php
app/Services/JsaPrintLayout.php
app/Http/Controllers/DocumentPdfController.php
app/Http/Controllers/DocumentExportController.php
resources/views/documents/print/          <- 9 berkas
resources/views/documents/export-excel.blade.php   <- Daftar Induk (.xls)
resources/views/emails/dokumen.blade.php
template_sop.blade.php, template_sp.blade.php,
template_ik.blade.php, template_jsa.blade.php      <- di akar proyek
```

`PdfRenderer` punya cache disk berbasis sidik jari dokumen (`sidik()`) dan konstanta versi kode cetak (`versiKodeCetak()`). **Selama kode cetak tidak disentuh, `versiKodeCetak()` tidak berubah, dan PDF hasilnya identik byte per byte.** Ini jangkar verifikasi paling kuat yang kita punya.

`DocumentExportController@export:89` merender `documents/export-excel` jadi HTML ber-BOM dengan header `application/vnd.ms-excel` — Daftar Induk tetap Blade selamanya.

### 4. Mesin status — 13 status, satu sumber kebenaran

`app/Models/Document.php` memuat `STATUS_LABELS` (13 kunci: 10 aktif + 3 warisan) dan `STATUS_META` (warna1, warna2, ikon per status). Komentar di kode mencatat bahwa peta ini **dulu tersalin di empat berkas Blade dan sudah saling menyimpang** — `verifikasi_md` hilang di semuanya sehingga tampil abu-abu seperti Draft.

> **Pakem wajib:** `STATUS_META` dikirim ke React **sebagai props**, tidak pernah ditulis ulang di TSX. Mengetik ulang peta ini di React akan mengulang persis bug yang sudah susah payah dibereskan.

Status aktif: `draft`, `waiting_for_review`, `in_review`, `rejected`, `verifikasi_md`, `pending_approval`, `published`, `sedang_direvisi`, `menunggu_nonaktif`, `obsolete`. Warisan: `submitted`, `needs_revision`, `archived`.

### 5. Izin & peran — 20 permission + 1 gate

```
audit.view                 document.approve            document.change_status
document.create            document.delete             document.edit
document.feedback_respond  document.publish            document.request_revision
document.review            document.review_jsa         document.review_md
document.submit            document.view_all           document.view_department
document.view_scope        informasi.manage            user.approve_registration
user.create_staff          user.manage
```

Plus gate `review-access` (dari `app/Services/ReviewAccess.php`). Sembilan di antaranya dipakai sebagai middleware `can:` di `routes/web.php`.

Jabatan: `staff` (label "Non-Staff", READ-ONLY), `group_leader`, `section_head`, `departemen_head`, `pimpinan/PJO` (tanpa departemen), plus Admin.

> **Pakem wajib:** izin dikirim lewat `HandleInertiaRequests::share()` sebagai `auth.can`, dan **middleware `can:` di route tetap dipertahankan**. Menyembunyikan tombol di React bukan otorisasi — otorisasi tetap di server.

### 6. Aturan pemilihan peserta alur — logika halus yang mudah hilang

`DocumentWizard::coAuthorCandidates()` (baris 296–314) memuat aturan berlapis yang komentar kodenya sendiri bilang tidak boleh menyimpang antara dropdown dan penyimpanan:

- pembuat tambahan = **GL & Non-Staff sedepartemen saja**
- bukan SH/DH/PJO (mereka peninjau/penyetuju — tak boleh menilai karyanya sendiri)
- bukan pembuat utama
- yang sedang cuti/off dibuang — **kecuali** yang sudah tercatat di dokumen ini (kalau tidak, GL yang mengajukan cuti setelah ditunjuk akan lenyap diam-diam dari baris "Dibuat Oleh")
- `whereIn` disengaja, bukan `whereNotIn`, supaya jabatan baru tidak otomatis lolos

`saveParticipants()` menyaring ulang kiriman POST dengan daftar yang sama, karena menyaring di dropdown saja tidak cukup.

> **Pakem wajib:** seluruh logika ini tetap di `DocumentWizard`. React hanya menerima daftar kandidat sebagai props dan mengirim balik id. **Tidak boleh** ada penyaringan kandidat yang ditulis ulang di sisi klien.

Terkait: `DocumentParticipantResolver` (reviewerCandidates/approverCandidates dengan matriks jenis + departemen + SHE/Plant), `ReviewerAvailability` (batas 2 dokumen), `UserOffDay::aktifPada()`.

Catatan khusus SP/IK: kunci `peninjau_penyetuju` mengisi `reviewer_id` **dan** `approver_id` sekaligus — satu SH/DH meninjau merangkap menyetujui.

### 7. Wizard — autosave dan pratinjau PDF langsung

`resources/views/documents/edit.blade.php` (29 KB) memuat:

- breadcrumb langkah
- panel anotasi peninjau (`$annotations` + `$reviewSummary`) berdampingan dengan form
- `@include($partial, ...)` dinamis per tipe seksi — inilah titik yang membuat satu view melayani semua jenis dokumen
- langkah virtual Log Revisi (`$isRevLogStep`) di luar skema
- Alpine `wizard()` → autosave ke `documents.autosave`
- **`<iframe id="previewFrame">` menunjuk route `documents.pdf`** — pratinjau PDF asli, bukan tiruan HTML

> Pratinjau iframe **tidak berubah sama sekali** di Inertia: `src` tetap menunjuk route yang sama, dan PDF-nya tetap dirender server. Ini justru salah satu bagian termudah.

### 8. Fitur admin — tujuh kelompok, seluruhnya wajib bertahan

| Fitur | Route | Controller |
|---|---|---|
| Kelola pengguna (CRUD, toggle status, konfigurasi MD) | `users.*` | `UserManagementController` (302 baris) |
| Persetujuan registrasi | `users.pending/approve/reject` | `UserApprovalController` |
| Profil akses (CRUD + tetapkan ke user) | `akses.*` | `AccessProfileController` (157 baris) |
| Penomoran dokumen | `pengaturan.penomoran` | `PengaturanController` |
| Master data (departemen, jenis, kategori informasi) | `pengaturan.master.*` | `MasterDataController` (153 baris) |
| Sistem (AI, uji email, uji AI, bersihkan cache) | `pengaturan.sistem.*` | `PengaturanController` (318 baris) |
| Musnahkan dokumen (satuan & massal) | `documents.purge`, `documents.purgeAll` | `DocumentRevisionController` + `DocumentPurger` |

### 9. AI peninjau — enam kelas, dikonfigurasi dari UI

`app/Services/Ai/`: `AiReviewerInterface`, `AbstractAiReviewer`, `GeminiReviewer`, `OpenRouterReviewer`, `FallbackReviewer`, `NullReviewer`. Dikonfigurasi lewat `pengaturan/sistem` dan diuji lewat `pengaturan.sistem.uji-ai`. Dipakai `review.ai` dan `review.md.ai`.

### 10. Antrean & notifikasi — jangan lupa dua jendela

`jalankan.bat` membuka **dua** jendela: `php artisan serve` dan `php artisan queue:work`. Komentar di berkasnya menjelaskan kenapa: notifikasi email dikirim lewat antrean, jadi tanpa `queue:work` email mengendap di tabel `jobs` **tanpa satu pun pesan salah** — sementara lonceng in-app tetap muncul karena kanal `database` sengaja sinkron. Kegagalannya separuh dan sulit disadari.

Lonceng in-app ada di `layouts/app.blade.php:1212–1217` (8 notifikasi terakhir + "Tandai dibaca"). Wajib ikut pindah ke `AppLayout.tsx`.

### 11. Inventaris 92 view

| Kelompok | Jumlah | Nasib |
|---|---|---|
| `documents/print/` | 9 | **tetap Blade selamanya** |
| `emails/` | 1 | **tetap Blade selamanya** |
| `documents/export-excel` | 1 | **tetap Blade selamanya** |
| `errors/` | 2 | tetap Blade (halaman galat Laravel) |
| `layouts/` | 2 | dihapus di fase akhir, diganti layout React |
| **sisanya** | **77** | **dimigrasi ke `.tsx`** |

### 12. Dampak ke tes

| Asersi | Jumlah | Nasib |
|---|---|---|
| `assertOk` / `assertStatus` / `assertRedirect` | 434 | **tidak berubah** |
| `assertJson` | 64 | **tidak berubah** (route API tak disentuh) |
| `assertSee` / `assertDontSee` | 254 (di 40 berkas) | → `assertInertia()`, bertahap per halaman |
| `assertViewIs` / `assertViewHas` | 2 | → `assertInertia()` |

`phpunit.xml` **sengaja menonaktifkan sqlite** — komentarnya menjelaskan tes berjalan di MySQL yang sama dengan `.env`, memakai `DatabaseTransactions`, dan bergantung pada data seeder (GL-0001, SH-0001, PJO-0001, 7 departemen, skema 4 jenis). **Konsekuensi: basis data wajib dikloning**, kalau tidak kedua aplikasi saling menimpa data uji.

> Jumlah tes hijau terakhir tercatat **527** di pesan commit `f9d58f4`. Konfirmasikan angka sebenarnya saat gerbang Fase 0.8, lalu pakai angka itu sebagai patokan seterusnya.

### 13. Yang tidak bisa dihindari

shadcn/ui adalah komponen **React**; tidak ada versi Blade resmi. Jadi "migrasi ke shadcn" = menambah Inertia + React dan menulis ulang 77 view. Biayanya sama besar di folder mana pun, jadi bukan alasan memilih folder.

Kabar baiknya untuk kekhawatiran *"biar shadcn-nya benar-benar terasa"*: halaman Inertia memakai root template sendiri (`resources/views/app.blade.php`) tanpa CDN Bootstrap. Bootstrap hanya hidup di `layouts/app.blade.php` yang tidak pernah disentuh halaman Inertia — jadi nol kontaminasi CSS lama, bahkan selama masa transisi.

---

## Fase 0 — Duplikasi & isolasi

**Tujuan:** salinan yang *terbukti* setia sebelum satu baris pun diubah.

### 0.1 Ambil baseline ekspor — LAKUKAN PALING DULU

Tidak bisa diambil surut. Ini satu-satunya bukti objektif nanti bahwa ekspor tidak berubah.

Dari `smartprorefactor_fresh` yang masih berjalan, unduh dan simpan di `C:\baseline-smartpro\`:

1. PDF satu dokumen **berlaku** tiap jenis: SOP, SP, IK, JSA
2. PDF satu dokumen **arsip/unggahan** (jenis FK atau PX) — jalur `ArsipPdf`
3. PDF satu dokumen yang punya **Log Revisi** terisi (lembar CATATAN REVISI)
4. Berkas Daftar Induk `.xls` dari `dokumen-export/{type}` untuk tiap jenis
5. Catat nomor dokumen dan id-nya di `C:\baseline-smartpro\CATATAN.txt`

### 0.2 Salin folder — bawa `.git`

```powershell
robocopy C:\xamppnew\htdocs\smartprorefactor_fresh C:\xamppnew\htdocs\smartproshadcn /E `
  /XD node_modules vendor .phpunit.cache `
      storage\framework\cache storage\framework\sessions storage\framework\views `
      public\build `
  /XF .phpunit.result.cache *.zip *.rar
```

Catatan:

- `.git` **ikut** — itu yang bikin `git merge main` masih mungkin nanti, untuk menarik perbaikan backend dari `fresh`
- `.env` ikut (robocopy tidak baca `.gitignore`) — memang mau, tinggal disunting
- `storage/app/public` ikut — lampiran dokumen harus cocok dengan basis data kloningan
- `avatars/` dan `illustrations/` di akar proyek ikut

### 0.3 Branch sendiri

```powershell
cd C:\xamppnew\htdocs\smartproshadcn
git switch -c shadcn
```

### 0.4 Dependensi PHP

```powershell
composer install
php artisan storage:link
```

### 0.5 Kloning basis data — WAJIB

```powershell
C:\xamppnew\mysql\bin\mysqldump.exe -u root --routines --events --triggers `
  smartpro_fresh > C:\baseline-smartpro\dump_fresh.sql

C:\xamppnew\mysql\bin\mysql.exe -u root `
  -e "CREATE DATABASE smartpro_shadcn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

C:\xamppnew\mysql\bin\mysql.exe -u root smartpro_shadcn < C:\baseline-smartpro\dump_fresh.sql
```

Bukti skema ikut terbawa:

```powershell
C:\xamppnew\mysql\bin\mysql.exe -u root smartpro_shadcn `
  -e "SELECT code, name, is_active, JSON_VALID(schema_json) AS skema_valid FROM document_types;"
```

Harus muncul enam baris: SOP, IK, SP, JSA (skema_valid = 1) dan FK, PX (NULL — jenis unggahan).

### 0.6 Sunting `.env` dan `.env.testing`

Hanya tiga baris:

```
APP_NAME=SmartPro shadcn
APP_URL=http://10.7.147.254:9092
DB_DATABASE=smartpro_shadcn
```

> **JANGAN jalankan `php artisan key:generate`.** `APP_KEY` wajib tetap sama — basis datanya kloningan, dan mengganti kunci membuat kolom terenkripsi tidak terbaca.

> **JANGAN jalankan `migrate:fresh` / `migrate:refresh`** (CLAUDE.md §4). Basis data sudah lengkap dari dump.

### 0.7 Sunting `jalankan.bat`

Ganti port `9091` → `9092` di ketiga tempat (teks alamat dan `--port`). `smartprorefactor_fresh` sudah pakai 9091.

### 0.8 GERBANG VERIFIKASI — jangan lanjut kalau gagal

```powershell
php artisan test
```

Harus hijau seluruhnya (patokan: 527). Kalau hijau, duplikat terbukti setia. Kalau merah, benahi di sini — jangan pernah menumpuk migrasi UI di atas salinan yang rusak.

Lalu jalankan `jalankan.bat`, buka `http://localhost:9092`, dan periksa manual:

- login GL-0001, SH-0001, PJO-0001 → menu dan tombol sesuai peran
- buka satu dokumen → unduh PDF → **bandingkan dengan baseline 0.1, harus identik**
- unduh Daftar Induk `.xls` → bandingkan dengan baseline

### 0.9 Commit titik nol

```powershell
git add -A
git commit -m "Fase 0: duplikat smartproshadcn, DB terpisah, suite hijau, ekspor identik"
```

---

## Fase 1 — Pondasi Inertia + React + TypeScript + shadcn

Acuan konfigurasi: `laravel/react-starter-kit` (Inertia 2 + React 19 + TypeScript + Tailwind 4 + shadcn/ui) — <https://github.com/laravel/react-starter-kit>

**Contek konfigurasinya saja** — auth, model, dan migrasinya tidak dipakai; SmartPro sudah punya auth NRP + Spatie sendiri.

### 1.1 Paket

```powershell
composer require inertiajs/inertia-laravel
php artisan inertia:middleware

npm install @inertiajs/react react react-dom
npm install -D @vitejs/plugin-react typescript @types/react @types/react-dom
```

Tailwind v4 **sudah terpasang** (`@tailwindcss/vite` + `tailwindcss` di `package.json`, `resources/css/app.css` baris 1 `@import 'tailwindcss'`). Tidak ada yang perlu diinstal untuk Tailwind.

### 1.2 Berkas yang dibuat / diubah

| Berkas | Isi |
|---|---|
| `vite.config.js` | tambah plugin `react()`, input → `resources/js/app.tsx`; **pertahankan blok `server.watch.ignored`** yang sudah ada untuk `storage/framework/views` |
| `tsconfig.json` | baru — `"paths": { "@/*": ["./resources/js/*"] }`, `"jsx": "react-jsx"` |
| `resources/js/app.tsx` | baru — `createInertiaApp` + resolusi halaman dari `./pages/**/*.tsx` |
| `resources/views/app.blade.php` | root template Inertia — `@viteReactRefresh`, `@vite`, `@inertiaHead`, `@inertia`. **Tanpa CDN Bootstrap, tanpa Alpine, tanpa SweetAlert2** |
| `bootstrap/app.php` | daftarkan `HandleInertiaRequests` di grup `web` |
| `app/Http/Middleware/HandleInertiaRequests.php` | isi `share()` — lihat 1.3 |
| `resources/js/lib/utils.ts` | helper `cn()` |

### 1.3 `HandleInertiaRequests::share()` — kontrak data global

Ini menggantikan apa yang dulu diambil `layouts/app.blade.php` langsung dari `auth()`. Wajib memuat:

```php
'auth' => [
    'user' => $user?->only(['id','name','nrp','jabatan','department_id','avatar']),
    'department' => $user?->department?->only(['id','code','name']),
    'roles' => $user?->getRoleNames(),
    'can' => [/* 20 permission + gate review-access */],
],
'notifications' => [
    'unread' => $user?->unreadNotifications()->count(),
    'items'  => $user?->notifications()->take(8)->get(),
],
'flash' => ['success' => ..., 'error' => ...],
'statusMeta'   => \App\Models\Document::STATUS_META,
'statusLabels' => \App\Models\Document::STATUS_LABELS,
```

> `statusMeta`/`statusLabels` dibagikan global supaya **tidak ada satu pun warna atau label status yang diketik ulang di TSX**. Ini mencegah terulangnya bug yang komentarnya sudah tercatat di `Document.php`: peta status pernah tersalin di empat Blade dan menyimpang, membuat `verifikasi_md` tampil abu-abu seperti Draft.

### 1.4 Inisialisasi shadcn

```powershell
npx shadcn@latest init
```

Periksa `components.json` yang dihasilkan — alias wajib menunjuk ke dalam `resources/js`:

```json
{
  "$schema": "https://ui.shadcn.com/schema.json",
  "style": "new-york",
  "rsc": false,
  "tsx": true,
  "tailwind": {
    "config": "",
    "css": "resources/css/app.css",
    "baseColor": "neutral",
    "cssVariables": true
  },
  "aliases": {
    "components": "@/components",
    "utils": "@/lib/utils",
    "ui": "@/components/ui",
    "lib": "@/lib",
    "hooks": "@/hooks"
  },
  "iconLibrary": "lucide"
}
```

`"rsc": false` penting — kita bukan Next.js, tidak ada React Server Components.

### 1.5 Gerbang verifikasi Fase 1

Buat route sementara `/lab` yang me-render satu halaman Inertia berisi satu `<Button>` shadcn. Lalu:

1. Buka `/lab` → tombol shadcn tampil, tanpa gaya Bootstrap sama sekali
2. Buka `/dashboard` (masih Blade lama) → **masih berjalan normal**
3. `php artisan test` → tetap hijau
4. `npm run build` → TypeScript bersih

Langkah 2 adalah buktinya: Blade dan Inertia hidup berdampingan, jadi migrasi bisa bertahap. Hapus `/lab` sesudahnya.

```powershell
git add -A
git commit -m "Fase 1: pondasi Inertia + React + TS + shadcn, Blade & Inertia berdampingan"
```

---

## Fase 2 — Skill, MCP, dan dokumen aturan

### 2.1 Skill resmi shadcn

```powershell
pnpm dlx skills add shadcn/ui
```

Memberi asisten konteks project-aware: framework, alias, komponen yang sudah terpasang, icon library, plus referensi CLI lengkap (`init`, `add`, `search`, `view`, `docs`, `diff`, `info`, `build`).

### 2.2 MCP server shadcn

```powershell
pnpm dlx shadcn@latest mcp init --client claude
```

Jalankan **setelah** `components.json` ada — MCP membaca daftar registry dari sana. Lalu restart Claude Code dan verifikasi dengan `/mcp`.

Setelah aktif, komponen dan blok bisa dicari, dilihat, dan dipasang langsung dari registry tanpa menebak nama.

### 2.3 Buang dokumen & skill bertema UI lama

```powershell
cd C:\xamppnew\htdocs\smartproshadcn

Remove-Item docs\PROMPT-STITCH-UIUX.md, docs\PROMPT-STITCH-V2-1-FONDASI.md, `
            docs\PROMPT-STITCH-V2-2-LAYAR.md, docs\PROMPT-STITCH-V2-3-KONSISTENSI.md, `
            docs\PLAN-PERBAIKAN-DAN-RAPIKAN-UI.md

Remove-Item docs\REDESIGN-2026\00-FONDASI.md, docs\REDESIGN-2026\01-LAYAR.md, `
            docs\REDESIGN-2026\02-PENUTUP.md, docs\REDESIGN-2026\03-SISTEM-DESAIN.md, `
            docs\REDESIGN-2026\04-FONDASI-TEKNIS.md, docs\REDESIGN-2026\05-KERANGKA-KOMPONEN.md, `
            docs\REDESIGN-2026\06-LAYAR-INTI.md, docs\REDESIGN-2026\07-LAYAR-LANJUTAN.md

Remove-Item -Recurse docs\dashboard_ref

Remove-Item -Recurse .claude\skills\banner-design, .claude\skills\brand, .claude\skills\design, `
            .claude\skills\design-system, .claude\skills\slides, .claude\skills\ui-styling, `
            .claude\skills\ui-ux-pro-max
```

Tujuh skill lokal itu duplikat dari skill global yang sudah tersedia di sesi Claude Code — menghapusnya tidak menghilangkan kemampuan apa pun, dan mencegahnya bertabrakan dengan skill resmi shadcn.

### 2.4 Dokumen yang DIBAWA (sudah ikut tersalin di Fase 0, biarkan)

```
docs/PLAN-MASTER.md                        docs/PLAN-AKSES-v7.md, PLAN-AKSES-v8.md
docs/PLAN-BUTIR-11-17.md                   docs/PLAN-BUTIR-15-MOBILE.md
docs/PLAN-BUTIR-17-AI.md                   docs/PLAN-DISTRIBUSI-DAN-LOG-PESAN.md
docs/PLAN-KESADARAN-TUGAS.md               docs/PLAN-PREPRODUKSI-v9.md
docs/PLAN-REVISI-v5.md, PLAN-REVISI-v6.md  docs/PLAN-RICHTEXT-DAN-FLOWCHART-OPSIONAL.md
docs/PANDUAN-PRODUKSI-EMAIL.md             docs/RUNBOOK-RILIS.md
docs/master.md   docs/revisi.md   docs/1. log.txt
docs/ai/         docs/pdflama/
docs/schema-sop.json   docs/schema-baseline.sql   docs/data-migrasi.sql
docs/TEMPLATE_DAFTAR_INDUK.xlsx
docs/REDESIGN-2026/08-BACKEND-PORTING.md, 09-QA-CUTOVER.md, DATA-KONTRAK.md
```

### 2.5 Tulis ulang `CLAUDE.md` → v4.0

Perubahan dari v3.1, pasal per pasal:

**§2 Tech stack — BERBALIK TOTAL.** v3.1 berbunyi *"Blade + Bootstrap 5 + Alpine.js (JANGAN Vue/React/Inertia/Tailwind-SPA)"*. Larangan ini kini justru jadi keharusan. Ganti jadi:

> Laravel 12 (PHP 8.2+) · **Inertia 2 + React 19 + TypeScript + Tailwind v4 + shadcn/ui** · MySQL 8 via XAMPP · Auth NRP+password · RBAC spatie/laravel-permission · PDF barryvdh/laravel-dompdf + setasign/fpdi · Ikon **lucide-react** (JANGAN emoji). Timezone Asia/Makassar (WITA).
>
> **Pengecualian yang tetap Blade selamanya:** seluruh isi `resources/views/documents/print/`, `documents/export-excel.blade.php`, `emails/`, `errors/`, dan `template_{sop,sp,ik,jsa}.blade.php` di akar.

**§3 Konvensi kode — dipertahankan, hanya pindah medium.** Aturan **satu set halaman untuk semua jenis dokumen** tetap mutlak: `resources/js/pages/Documents/{Aksi}.tsx`. Perbedaan SOP/IK/SP/JSA tetap datang dari skema JSON, bukan halaman terpisah. Aturan "nama route = nama halaman = nama method" tetap: `documents.published` → `pages/Documents/Published.tsx` → `published()`. Komentar & seluruh teks antarmuka tetap Bahasa Indonesia. Logika tetap di Service, validasi tetap di Form Request, otorisasi tetap Policy/Gate. Controller tetap tipis (~300 baris).

**§4 Larangan — satu dicabut, empat ditambah.**

- *Dicabut:* butir "bila aset referensi-visual bentrok dengan Bootstrap 5/Alpine, hentikan" — tidak relevan lagi.
- *Tetap:* jangan `migrate:fresh`/`migrate:refresh`; jangan commit `.env`; jangan hapus berkas tanpa konfirmasi; jangan install paket besar tanpa izin; jangan kerjakan banyak fase sekaligus.
- **Baru:** JANGAN menyunting berkas hasil generate di `resources/js/components/ui/`. Kustomisasi lewat CSS variable dan `variant` cva, bukan menimpa berkasnya — kalau ditimpa, `npx shadcn diff` jadi tak berguna dan pembaruan komponen mustahil.
- **Baru:** JANGAN menyentuh apa pun di daftar mesin cetak (§ Cetak). Itu bukan lapisan tampilan.
- **Baru:** JANGAN menulis ulang peta status, daftar izin, atau logika kandidat peserta di TSX. Semuanya datang sebagai props dari server.
- **Baru:** JANGAN menghapus middleware `can:` dari route dengan alasan "tombolnya sudah disembunyikan di React".

**§13 UX & Tema — ditulis ulang penuh.** Referensi Soft UI dibuang. Ganti jadi: sistem tema shadcn (CSS variable + kelas `.dark` di `resources/css/app.css`), merah identitas PPA sebagai `--primary`. Standar UX yang **tetap wajib**: lonceng notifikasi, dialog konfirmasi sebelum Kirim/Hapus/Ajukan Revisi/Batalkan Revisi, mode gelap, hover, transisi, skeleton/loading/empty state, breadcrumb, badge status berwarna, kolom Status + Aksi + Lihat PDF konsisten, nol emoji, logo klik → home, masukan reviewer dua lapis (rangkuman di atas + anotasi di samping tiap poin), foto lampiran bisa dilihat & dikomentari saat tinjau. Desktop dulu.

**§ Cetak (pasal BARU).** Daftar lengkap berkas mesin cetak yang haram disentuh, plus alasannya: `PdfRenderer` punya cache sidik jari dan `versiKodeCetak()`; selama kode cetak tidak berubah, PDF hasilnya identik byte per byte, dan itulah jaminan "ekspor dokumen tetap sama".

**§ Pengujian (pasal BARU).**

> `php artisan test` berjalan di MySQL `smartpro_shadcn` (bukan sqlite — lihat komentar di `phpunit.xml`), memakai `DatabaseTransactions`, dan bergantung pada data seeder: akun per peran (GL-0001, SH-0001, PJO-0001), 7 departemen, skema JSON 4 jenis dokumen.
>
> Halaman yang sudah pindah ke Inertia diuji dengan `assertInertia(fn (Assert $page) => $page->component('...')->has('...'))`, bukan `assertSee()`. Jumlah tes tidak boleh berkurang — hanya gaya asersinya yang berubah.
>
> Tiap fase juga wajib `npm run build` bersih (TypeScript tanpa galat).

**§5 Cara kerja — dipertahankan apa adanya.** Satu fase → BERHENTI → jelaskan singkat → tunggu review + commit Git. Belum jelas → TANYA. DB berubah → tunjukkan migration, tunggu approval.

### 2.6 Salin `MIGRASI-SHADCN.md` ke `docs/`

```powershell
git add -A
git commit -m "Fase 2: skill shadcn + MCP, CLAUDE.md v4.0, buang dokumen UI lama"
```

---

## Fase 3 — Kerangka aplikasi

### 3.1 Pasang blok dashboard

```powershell
npx shadcn@latest add dashboard-01
```

Menarik sidebar, nav, chart, dan data-table sekaligus.

> **Blok ini ditulis untuk Next.js.** Jangan berharap sekali perintah langsung jalan. Yang wajib disesuaikan manual:
> - hapus semua direktif `"use client"`
> - `next/link` → `Link` dari `@inertiajs/react`
> - `next/image` → `<img>` biasa
> - `usePathname()` → `usePage().url`
> - buang seluruh data contoh, sambungkan ke props Inertia

### 3.2 Baca `layouts/app.blade.php` UTUH sebelum menulis sidebar

1.283 baris itu bukan sekadar markup. Di dalamnya ada:

- seluruh struktur menu navigasi
- **aturan tampil-per-peran tiap menu** (yang mana muncul untuk GL, SH, DH, PJO, Admin)
- lonceng notifikasi (baris 1212–1217): 8 notifikasi terakhir, penanda belum dibaca, tombol "Tandai dibaca"
- palet warna `--su-*` / `--pp-*` yang jadi rujukan `STATUS_META`

Menulis sidebar tanpa membaca berkas ini akan menghilangkan menu untuk sebagian peran secara diam-diam.

### 3.3 Berkas layout yang dibangun

| Berkas | Menggantikan |
|---|---|
| `resources/js/layouts/AppLayout.tsx` | `layouts/app.blade.php` — sidebar + topbar + lonceng + breadcrumb + toggle tema |
| `resources/js/layouts/AuthLayout.tsx` | `layouts/guest.blade.php` |
| `resources/js/components/StatusBadge.tsx` | `partials/_badge-status.blade.php` — **baca `statusMeta` dari props, jangan hardcode** |
| `resources/js/components/Avatar.tsx` | `partials/_avatar.blade.php` |
| `resources/js/components/DataTable.tsx` | pola tabel + `partials/_urut-th.blade.php` (pengurutan kolom) |
| `resources/js/components/ConfirmDialog.tsx` | pengganti SweetAlert2 — `AlertDialog` shadcn |

### 3.4 Tema

Di `resources/css/app.css`, definisikan palet lengkap sebagai CSS variable pada `:root`, lalu timpa hanya token yang perlu di `.dark`. Merah identitas PPA jadi `--primary`.

Warna `STATUS_META` **tetap datang dari PHP** sebagai hex — sudah begitu di kode lama karena nilainya juga dipakai sebagai titik warna di matriks dasbor, bukan cuma kelas CSS.

### 3.5 Gerbang verifikasi Fase 3

- Login sebagai GL-0001, SH-0001, PJO-0001, dan Admin → **daftar menu sidebar sama persis** dengan aplikasi lama untuk tiap peran (bandingkan berdampingan dengan `fresh` di port 9091)
- Lonceng menampilkan 8 notifikasi + tombol "Tandai dibaca" berfungsi
- Toggle terang/gelap bekerja, tidak ada teks yang hilang di salah satu tema
- `php artisan test` → hijau; `npm run build` → bersih

```powershell
git add -A
git commit -m "Fase 3: kerangka AppLayout/AuthLayout shadcn, menu per peran setara"
```

---

Lanjut ke **`MIGRASI-SHADCN.md`** untuk Fase 4–13.
