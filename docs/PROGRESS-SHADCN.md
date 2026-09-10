# PROGRES MIGRASI SHADCN

Penanda status per fase. Sumber tugas: `SETUP-SHADCN.md` (Fase 0–3) dan
`MIGRASI-SHADCN.md` (Fase 4–13).

Legenda: `[x]` selesai & terverifikasi · `[~]` selesai sebagian, ada catatan ·
`[ ]` belum · `[!]` butuh keputusan/aksi pemilik

> **Seluruh butir `[!] Pemeriksaan mata` di bawah sudah digabung** jadi satu
> ceklis yang urutannya mengikuti LOGIN, bukan nomor fase:
> **`docs/CEKLIS-MATA-PER-PERAN.md`**. Nol butir dibuang; tiap baris di sana
> menyebut fase asalnya. Blok `[!]` per fase di dokumen ini dibiarkan sebagai
> jejak sejarah — kerjakan dari ceklis itu, bukan dari sini.
>
> **Keputusan pemilik atas kedelapan utang terbuka: lihat §Keputusan pemilik
> 2026-08-30 di dekat akhir dokumen.**

---

## ⚠️ PEKERJAAN YANG BERJALAN SESUDAH FASE 13 — BUKAN DI DOKUMEN INI

Fase 0–13 di bawah adalah migrasi Blade → Inertia. Sesudahnya ada **lapisan
kedua** yang punya dokumennya sendiri, dan berkas INI tidak melacaknya:

| pekerjaan | dokumen | status |
|---|---|---|
| Redesain UI V2 "maia" — pratinjau bertoggle, `components/ui-maia/`, ikon hugeicons, palet grafik tervalidasi, kerangka + arketipe tabel | **`docs/REDESAIN-UI-V2.md`** | T1 · T2 · T2b selesai (T2b kemudian dibatalkan T3) |
| Dasbor V2 — dirombak ulang atas keputusan pemilik: balik ke tata letak V1, enam widget digambar ulang, lencana & sparkline berdata `audit_logs` | **`docs/DASBOR-V2-REVISI.md`** | Fase 1–3 selesai · Fase 4 (pemeriksaan mata) menunggu pemilik |

**Sebelum menyentuh apa pun di `resources/js/components/v2/` atau
`resources/js/pages/V2/`, baca kedua berkas itu — bukan berkas ini.** Yang di
sini cuma latar belakangnya.

Dua hal dari lapisan itu yang paling mudah dilanggar tanpa sadar:

- **`components.json` masih `radix-nova` + `lucide` + `ui`.** `npx shadcn add <x>`
  hari ini menulis komponen NOVA berikon LUCIDE ke `components/ui/` — gaya yang
  salah untuk V2, sekaligus menimpa berkas V1 yang CLAUDE.md §4 larang disentuh.
  Prosedur menukar-sementara-lalu-mengembalikannya ada di `DASBOR-V2-REVISI.md` §P3,
  digerbangi `git diff components.json` yang wajib KOSONG.
- **Tiga berkas nol pengimpor ditahan atas keputusan pemilik (K3), belum dihapus:**
  `v2/dasbor/{PitaSambutan,ArusDokumen,PerluTindakan}.tsx`. Jangan dihapus
  sebagai "pembersihan" — penghapusan berkas butuh konfirmasi eksplisit.

---

## Fase 0 — Duplikasi & isolasi ✅ SELESAI

Commit: `33261f3` · branch `shadcn` · 2026-08-28

- [~] **0.1 Baseline ekspor** → `C:\baseline-smartpro\`
  - [x] PDF SOP (3 dokumen: id 1177, 1178, 1181)
  - [x] PDF JSA (id 1179, published)
  - [x] PDF SP — dokumen **1225** `PPA-ADRO-SP-ICTMD-01` (2026-08-28, lihat
        "Penutupan utang Fase 0" di bawah)
  - [x] PDF IK — dokumen **1224** `PPA-ADRO-IK-ICTMD-01`
  - [~] PDF arsip/unggahan — dokumen **1226** FK `PPA-ADRO-FK-ICTMD-99` dibuat,
        tapi `ArsipPdf::sajikan()` hanya MENGALIRKAN berkas yang diunggah, tidak
        merender apa pun. Jadi tak ada keluaran yang bisa menyimpang: yang perlu
        diuji di sana rutenya (auth + 404 bila berkas hilang), bukan bytenya.
  - [x] PDF dokumen ber-Log Revisi — dokumen **1227** `PPA-ADRO-SOP-ICTMD-98`,
        lembar CATATAN REVISI terbukti tercetak (2 baris)
  - [x] Daftar Induk `.xls` keenam jenis (hanya JSA yang berisi baris — ekspor
        cuma memuat status `published`/`sedang_direvisi`)
  - [x] `CATATAN.txt` + `_hasil.txt` (md5 & ukuran tiap berkas)
  - [x] `mesin-cetak.md5` — 23 berkas mesin cetak, **semua cocok** dengan
        `smartprorefactor_fresh`. Ini penjaga regresi yang sebenarnya;
        `sidik()` berbasis mtime jadi tidak tahan-salin.
  - [x] `menu-per-peran.txt` — **tambahan di luar dokumen.** Seluruh tautan
        yang terlihat di `/dashboard` untuk ketujuh peran, direkam dari
        `layouts/app.blade.php` yang lama. Sifatnya sama dengan baseline PDF:
        tak bisa diambil surut setelah Fase 3 mengganti layout. Membuat
        gerbang Fase 3.5 jadi `diff`, bukan adu mata dua peramban.
        (admin_it 61 · pimpinan 46 · MD 45 · SH 40 · GL 37 · DH 36 · staff 31)
- [x] **0.2 Salin folder** — robocopy → `new-app`, `.git` ikut, `.env` ikut,
      `storage/app/public` ikut (23 berkas), `avatars/` + `illustrations/` ikut
- [x] **0.3 Branch** `shadcn` dari `main` @ `410029e`
- [x] **0.4 Dependensi PHP** — `composer install`; `public/storage` dikembalikan
      jadi symlink (robocopy menyalinnya sebagai direktori nyata)
- [x] **0.5 Kloning basis data** — **dua** DB, bukan satu:
      `smartpro_fresh` → `smartpro_shadcn` · `smartpro_uji` → `smartpro_shadcn_uji`
      (39 tabel, 117 user, 7 dept, 20 permission, 6 jenis dokumen — semua cocok)
- [x] **0.6 `.env` + `.env.testing`** — `APP_KEY` **tidak** diregenerasi
- [x] **0.7 `jalankan.bat`** — port 9091 → 9092
- [~] **0.8 Gerbang verifikasi**
  - [x] `php artisan test` → **607 lulus, 1 dilewati, 0 gagal** (4133 asersi)
  - [x] Tes terbukti memakai `smartpro_shadcn_uji`, bukan DB uji web asli
  - [x] `smartprorefactor_fresh` terbukti tidak berubah (HEAD `410029e`, git
        status identik dengan sebelum mulai, kedua DB aslinya utuh)
  - [x] Kelengkapan salinan diaudit berkas-per-berkas terhadap asli: 1073 vs
        1077, satu-satunya selisih adalah singgahan PDF (regenerable), scratch
        tes, dan `smartpro_fresh.sql` yang baru muncul di asli **setelah**
        robocopy. Tidak ada berkas aplikasi yang hilang.
  - [x] `public/build` tidak ada di klon **maupun** di asli, dan tak satu pun
        Blade memakai `@vite` — aplikasi ini murni CDN, jadi pengecualian
        robocopy tidak merusak apa pun. (`npm install` belum perlu di Fase 0.)
  - [!] **Pemeriksaan manual di peramban — belum, butuh kamu:** buka
        `http://localhost:9092`, cocokkan menu & tombol per peran dengan web
        asli di port 9091. **Jangan pakai GL-0001/SH-0001/PJO-0001 — akun itu
        tidak ada.** Pakai daftar akun nyata di bawah.
- [x] **0.9 Commit titik nol** — `2e9abef`

### Akun nyata per peran di `smartpro_shadcn` (117 user, 7 peran)

| Peran | NRP | Jumlah user |
|---|---|---|
| `admin_it` | `ADM-0001` | 1 |
| `pimpinan` (PJO) | `16071367` | 1 |
| `departemen_head` | `17092728` | 2 |
| `section_head` | `16081467` | 6 |
| `group_leader` | `14040677` | 103 |
| `management_development` | `MD-0001` | 1 |
| `staff` (Non-Staff) | `20260219` | 3 |

`GL-0001` / `SH-0001` / `PJO-0001` yang disebut dokumen **tidak ada di kedua
basis data** — itu nama akun seeder dari keadaan lama.

### Audit SETUP-SHADCN.md — klaim §1–§13 vs kenyataan

Diperiksa seluruhnya terhadap klon, karena angka-angka ini jadi dasar Fase 4–13.

**Tepat / terbukti (tidak perlu tindakan):**

| § | Klaim | Hasil |
|---|---|---|
| 1 | Skema hanya di DB, tak ada berkas skema hidup di repo | ✅ hanya arsip `docs/schema-sop.json` & `docs/schema-baseline.sql` |
| 3 | Daftar berkas mesin cetak mandiri dari lapisan tampilan | ✅ 23 berkas, md5 identik dengan asli |
| 4 | `STATUS_LABELS` & `STATUS_META` 13 kunci, satu sumber kebenaran | ✅ 13 & 13, tanpa selisih kunci |
| 5 | 20 permission + gate `review-access` | ✅ persis 20, nama cocok semua; gate di `AppServiceProvider.php:107` |
| 5 | Sembilan permission dipakai sebagai middleware `can:` | ✅ 9 nama berbeda (11 pemakaian) |
| 6 | Logika kandidat peserta + kelas pendukungnya | ✅ `DocumentParticipantResolver`, `ReviewerAvailability`, `UserOffDay` ada |
| 7 | `<iframe id="previewFrame">` menunjuk route `documents.pdf` | ✅ `edit.blade.php:215` |
| 8 | Tujuh kelompok fitur admin | ✅ lengkap; jumlah baris controller **persis** (302/157/318/153) |
| 9 | Enam kelas AI di `app/Services/Ai/` | ✅ persis enam |
| 10 | `jalankan.bat` membuka dua jendela (serve + queue:work) | ✅ |
| 10 | Lonceng: 8 notifikasi terakhir + "Tandai dibaca" | ✅ ada (di baris 1307–1324, bukan 1212–1217) |
| 11 | 9 print + 1 email + 1 excel + 2 error + 2 layout tetap Blade | ✅ semua cocok |
| 12 | `assertJson` 64 · `assertSee/DontSee` 254 di 40 berkas · `assertViewIs/Has` 2 | ✅ ketiganya persis |
| — | Prasyarat Fase 1.1: Tailwind v4 sudah terpasang | ✅ `@import 'tailwindcss'` di `app.css:1`, `tailwindcss@^4` di `package.json` |
| — | `vite.config.js` punya blok `server.watch.ignored` yang wajib dipertahankan | ✅ ada |

**Meleset — angka dokumen sudah usang, pakai angka di kolom kanan:**

| § | Dokumen bilang | Kenyataan | Dampak |
|---|---|---|---|
| 1 | FK & PX ber-`schema_json` **NULL** | `schema_json` = `[]`; unggahan dideteksi lewat kolom **`class='unggahan'`** (`DocumentType.php:70`) | verifikasi 0.5 disesuaikan; **P6 di MIGRASI harus pakai `class`, bukan cek NULL** |
| 1 | `SchemaService` 105 baris | **197 baris** | — |
| 2 | **10** tipe seksi | **11** — ada `rich_text` (3x) yang tak disebut sama sekali | **satu komponen React ekstra di Fase 9** |
| 2 | Hitungan per tipe (user_picker 7x, text 6x, rich_list 5x, …) | user_picker **9x** · text **9x** · rich_list **8x** · repeatable_group **6x** · textarea **3x** · rich_text **3x** · reference_picker **2x** · image **2x** · document_picker 1x · date 1x · jsa_analysis 1x | — |
| 2 | **8** berkas renderer = 8 komponen React | **9** berkas (`_rich_text.blade.php` menyusul) | **Fase 9 = 9 renderer, bukan 8** |
| 6 | `coAuthorCandidates()` di baris 296–314 | baris **389** | — |
| 7 | `edit.blade.php` 29 KB | **49 KB / 858 baris** | Fase 9 lebih berat dari perkiraan |
| 10 | `layouts/app.blade.php` 1.283 baris | **1.516 baris** | Fase 3.2 lebih berat |
| 11 | 92 view · 77 dimigrasi | **93 view · 78 dimigrasi** | — |
| 12 | `assertOk/Status/Redirect` 434 | **444** | — |
| 12 | Tes jalan di DB yang sama dengan `.env` | `.env.testing` menunjuk DB **terpisah** `smartpro_uji` | **dikloning juga → `smartpro_shadcn_uji`**; tanpa ini tes klon menimpa data uji web asli |
| 12 | Patokan 527 tes | **608** (607 lulus + 1 dilewati, 4133 asersi) | patokan baru = **608** |
| — | Controller 44 · service 26 · model 24 · ~18.000 baris PHP | 44 ✅ · service **29** · 24 ✅ · **18.992** ✅ | — |
| 3 | `sidik()` jangkar verifikasi terkuat | `versiKodeCetak()` berbasis **mtime** berkas print, bukan hash isi — ikut berubah hanya karena disalin ulang, dan **tidak** berubah kalau isi diedit tanpa mengubah mtime | jangkar sebenarnya = **`mesin-cetak.md5`** |
| 0.8 | Login GL-0001 / SH-0001 / PJO-0001 | ketiganya **tidak ada** di kedua DB | pakai tabel akun di atas |
| 0.7 | Ganti 9091 di **tiga** tempat | hanya **dua** kemunculan di `jalankan.bat` | keduanya sudah diganti |

**Temuan baru yang berdampak ke fase berikutnya:**

- `STATUS_META` menyimpan **nama kelas Bootstrap Icons** (`verifikasi_md` →
  `["#7c2d12","#431407","bi-fonts"]`). CLAUDE.md v4.0 mewajibkan
  `lucide-react`. Fase 1.3 mengirim `statusMeta` apa adanya sebagai props
  (pakem P3 — jangan ditulis ulang di TSX), jadi Fase 3.3 `StatusBadge.tsx`
  butuh **peta `bi-*` → lucide** di satu tempat saja. Warna hex-nya dipakai
  langsung, tidak perlu diubah.
- `.env` di asli menunjuk IP `10.7.147.254`, `jalankan.bat` menunjuk
  `10.7.110.101`, `.env.testing` menunjuk `10.7.110.91`. Ketiganya sudah
  berbeda sejak sebelum migrasi; hanya port yang diseragamkan ke 9092.

---

## Fase 1 — Pondasi Inertia + React + TS + shadcn ✅ SELESAI (2026-08-28)

- [x] 1.1 Paket — `inertiajs/inertia-laravel` **v3.3.1**, `@inertiajs/react` 3.7,
      React 19.2, TypeScript 7, `@vitejs/plugin-react` **^5**
  > **Jebakan:** `@vitejs/plugin-react` terbaru (6.x) menuntut peer `vite@^8`,
  > sedangkan proyek ini vite 7 → `npm i` gagal ERESOLVE tanpa pesan yang jelas.
  > Dipatok `^5`. Menaikkan vite ke 8 sengaja TIDAK dilakukan: itu mengubah
  > build yang sudah jalan demi masalah yang bisa diselesaikan satu angka versi.
- [x] 1.2 Berkas — `vite.config.js` (plugin `react()`; blok `server.watch.ignored`
      DIPERTAHANKAN), `tsconfig.json`, `resources/js/app.tsx`,
      `resources/views/app.blade.php` (nol CDN Bootstrap/Alpine/SweetAlert2),
      `bootstrap/app.php`, `HandleInertiaRequests`, `resources/js/lib/utils.ts`
  > `resources/js/app.js` (Alpine/axios lama) sengaja TIDAK masuk input vite —
  > tak satu pun Blade memakai `@vite`, lapisan lama murni CDN. Berkasnya baru
  > dihapus di Fase 13.
- [x] 1.3 `share()` — `auth.user/department/roles/can`, `notifications`
      (8 terakhir + jumlah belum dibaca, meniru lonceng lama), `flash`
      (`success` ikut membaca `status`, seperti Blade lama), `statusMeta`,
      `statusLabels`
  > `can` dibangun dari tabel `permissions` (20 baris) + gate `review-access`
  > dan `beri-masukan`, jadi izin baru ikut terkirim tanpa menyunting berkas.
  > `can` dan `notifications` dibungkus **closure**: middleware ini dipasang di
  > grup `web` sehingga `share()` dipanggil pada SETIAP permintaan — termasuk
  > halaman Blade lama yang tak akan pernah membacanya. Sebagai nilai jadi,
  > tiga kueri ikut jalan di sana.
- [x] 1.4 `npx shadcn@latest init` → `components.json` (`rsc:false`, `tsx:true`,
      alias `@/*` → `resources/js`, ikon **lucide**)
  > CLI shadcn sekarang menuntut dua pilihan yang belum ada di dokumen SETUP:
  > **component library** (Base UI / React Aria / **Radix UI**) dan **preset**.
  > Dipilih `-b radix -p nova` — Radix karena itu shadcn/ui yang diasumsikan
  > seluruh dokumen & blok `dashboard-01`; Nova karena ia memakai **Lucide**,
  > yang memang diwajibkan CLAUDE.md v4.0.
- [x] 1.5 Gerbang — `/lab` merender komponen Inertia `Lab` dengan `<Button>`
      shadcn, props `statusMeta`/`statusLabels`/`auth` terkirim, dan **nol**
      jejak Bootstrap di HTML-nya · `npm run build` bersih (739 modul) ·
      `tsc --noEmit` bersih
  > Ziggy (`@routes`) sengaja **belum** dipasang: Fase 1 tak membutuhkannya dan
  > itu paket baru yang perlu izin (CLAUDE.md §4). Sampai diputuskan, URL
  > dikirim lewat props — kedua cara sah menurut MIGRASI-SHADCN.md.

## Fase 2 — Skill, MCP, dokumen aturan

- [x] 2.1 `pnpm dlx skills add shadcn/ui` — dijalankan pemilik (2026-08-28)
- [x] 2.2 `pnpm dlx shadcn@latest mcp init --client claude` — dijalankan
      pemilik + restart Claude Code (2026-08-28); MCP `shadcn` terpasang.
- [x] 2.3 Buang dokumen & skill bertema UI lama — 13 dokumen + `docs/dashboard_ref`
      (1,8 MB) + 7 skill lokal, seluruhnya lewat `git rm` (bisa dipulihkan).
      Ketujuh skill terbukti duplikat: salinan yang sama ada di
      `~/.claude/skills/`, jadi tak ada kemampuan yang hilang.
      `docs/REDESIGN-2026/{08,09,DATA-KONTRAK,RENCANA-EKSEKUSI}.md` DIPERTAHANKAN.
- [x] 2.4 (dokumen yang dibawa — sudah ikut tersalin di Fase 0)
- [x] 2.5 `CLAUDE.md` → **v4.0** — §2 tech stack dibalik (larangan React/Inertia
      dicabut, jadi keharusan) · §3 view→halaman TSX + uji P1 · §4 empat larangan
      baru · §13 tema shadcn (referensi Soft UI dibuang) · **§14b Cetak** dan
      **§14c Pengujian** (pasal baru) · §17 rujukan migrasi
- [ ] 2.6 Commit — menunggu review kamu (CLAUDE.md §5)

## Fase 3 — Kerangka aplikasi ✅ SELESAI (2026-08-28)

- [x] **3.0 Tema preset** — keputusan pemilik: preset `bOfrsPWx1` dipakai
      **hanya theme + font** (`shadcn apply bOfrsPWx1 --only theme,font`).
      Palet jadi rose/mauve (merah PPA sebagai `--primary`, sesuai CLAUDE.md
      §13), font DM Sans + Inter. Dua bagian preset lain SENGAJA ditolak:
      `iconLibrary: hugeicons` bertabrakan dengan kewajiban `lucide-react`
      (CLAUDE.md §2), dan `style: maia` akan menyimpang dari `radix-nova` yang
      diasumsikan blok `dashboard-01`. `components.json` tetap
      `style: radix-nova`, `iconLibrary: lucide`.
- [~] **3.1 Komponen dashboard-01** — yang dipasang adalah **pustaka
      komponennya**, bukan bloknya: `sidebar breadcrumb dropdown-menu avatar
      badge table sonner alert-dialog collapsible scroll-area skeleton`
      (+ turunannya: button, separator, sheet, tooltip, input, use-mobile).
      Susunan halaman meniru dashboard-01 (`SidebarProvider` + `Sidebar
      variant="inset"` + `SidebarInset` + header lengket).
  > Blok `dashboard-01` utuh **tidak** ditarik. Isinya menuntut enam paket npm
  > (`@dnd-kit` ×4, `@tanstack/react-table`, `zod`) yang seluruhnya melayani
  > satu tabel demo yang bisa di-drag — sementara pengurutan & paginasi di
  > SmartPro ada di server (`Document::scopeUrut` + paginator Laravel).
  > Mengurutkan di klien hanya membalik 15 baris yang kebetulan tampil, jadi
  > mesin tabel di peramban justru merusak perilaku yang sudah benar. Berkas
  > demo Next.js-nya (`page.tsx`, `data.json`, `"use client"`) karena itu tak
  > pernah masuk, dan tak ada yang perlu dibersihkan.
- [x] **3.2 Baca `layouts/app.blade.php` utuh** (1.516 baris) — seluruh menu,
      aturan per peran, lonceng, dan palet `--su-*` ditelusuri sebelum menulis
      satu baris TSX.
- [x] **3.3 Berkas kerangka**
  - `app/Services/NavigasiSidebar.php` — **BARU, dan ini inti Fase 3.** Seluruh
    susunan menu + aturan tampil-per-peran tetap di PHP, dikirim jadi lewat
    props `navigation`. Alasannya: sidebar lama memanggil sembilan hal yang
    hanya ada di server (`dashboardPenuh`, `bisaLihatDistribusi`,
    `bolehBuatJenis`, `Document::tahapNonaktifUntuk`, `DocumentType::kode`,
    `InformasiKategori::semua`, `AntreanTugas`, izin spatie, `routeIs`).
    Menirunya di TSX = sembilan aturan bersalinan yang bisa menyimpang, persis
    yang dilarang CLAUDE.md §4 dan persis kesalahan yang sudah dibayar mahal
    dengan `STATUS_META`.
  - `layouts/AppLayout.tsx` · `layouts/AuthLayout.tsx`
  - `components/AppSidebar.tsx` (penggambar pohon menu — nol pemeriksaan izin)
  - `components/SiteHeader.tsx` (breadcrumb · toggle tema · lonceng · menu akun)
  - `components/StatusBadge.tsx` (baca `statusMeta` — nol hex diketik)
  - `components/Ikon.tsx` — peta `bi-*` → lucide, **satu tempat** untuk status,
    sidebar, lonceng, dan kategori Informasi. 69 entri + jatuh ke lingkaran
    netral bila tak dikenal.
  - `components/Avatar.tsx` · `components/DataTable.tsx` (+ `Paginasi`) ·
    `components/ConfirmDialog.tsx` (pengganti SweetAlert2)
  - `types/index.d.ts` (kontrak props global) · `types/ziggy.d.ts`
- [x] **3.4 Tema** — `:root` + `.dark` dari preset; `next-themes` yang memegang
      togglenya (paketnya sudah ikut terpasang bersama `sonner`, jadi menulis
      pengelola tema kedua hanya akan membuat toast dan halaman berbeda tema).
      Anti-FOUC satu blok `<script>` di `views/app.blade.php`.
- [x] **3.5 Gerbang**
  - [x] **Menu per peran setara — 7/7 peran, dibuktikan `diff`, bukan adu mata.**
        Menu dari `NavigasiSidebar` dibandingkan dengan
        `C:aseline-smartpro\menu-per-peran.txt`. Nol tautan sidebar hilang di
        ketujuh peran. Satu-satunya selisih adalah tautan yang memang bukan
        milik sidebar (`/documents/1177..1181` dari feed aktivitas dan
        `/status-dokumen-staff` dari kartu statistik — keduanya isi dashboard,
        milik Fase 5).
  - [x] Lonceng 8 notifikasi + lencana "99+" + "Tandai dibaca" (POST
        `notifications.readAll`) — meniru Blade lama persis
  - [x] Toggle terang/gelap
  - [x] `md5sum -c mesin-cetak.md5` → **23/23 cocok**
  - [x] `php artisan test` → **607 lulus + 1 dilewati (4133 asersi)** — patokan
        608 utuh, nol tes berkurang (pakem P7). Ditambah
        `tests/Feature/NavigasiSidebarTest.php` (**7 tes / 69 asersi** baru),
        jadi total **615**. Tes itu menggantikan gerbang "adu mata" dengan
        pengunci permanen: tiap peran melihat menu haknya, dan TIDAK melihat
        yang bukan haknya.
  - [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
  - [x] `npm run build` bersih (2.552 modul) · `tsc --noEmit` bersih
  - [x] Payload `/lab` diperiksa langsung: `component=Lab`, 5 bagian menu,
        `statusMeta` 13 kunci, `auth.can` 22 izin, Ziggy tersisip, dan **nol
        jejak Bootstrap** di HTML-nya
  - [x] **`php artisan smartpro:uji-render`** — gerbang BARU, lihat di bawah
  - [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092/lab` (kini di
        balik `auth`+`active`, merender AppLayout utuh). Login bergantian
        dengan tujuh akun di tabel Fase 0, adu berdampingan dengan port 9091.

### Layar putih Fase 3 — dan gerbang yang lahir karenanya

**Kejadian (2026-08-28).** `/lab` PUTIH KOSONG di peramban, padahal
`npm run build` hijau, `tsc --noEmit` hijau, `php artisan test` hijau, dan
payload Inertia-nya terbukti lengkap.

**Sebabnya.** Versi `tooltip` yang dibangkitkan shadcn sekarang **tidak lagi**
membungkus Provider-nya sendiri (versi lama dulu begitu), dan `SidebarProvider`
juga tidak. `SidebarMenuButton` memakai `<Tooltip>` untuk tiap item bertooltip,
dan Radix **MELEMPAR** galat — bukan diam — bila Root dipakai di luar Provider.
Satu item sidebar cukup untuk memutihkan seluruh halaman. CLI shadcn sebenarnya
sudah memberi tahu ("Remember to wrap your app with the `TooltipProvider`"),
dan pesan itu terlewat.

**Perbaikannya.** `TooltipProvider` di `app.tsx`, membungkus `<App>` — di titik
masuk, bukan di AppLayout, supaya halaman berikutnya yang memakai Tooltip di
luar layout tak bisa menghidupkan kembali layar putih yang sama.

**Yang lebih penting: kenapa tak ada gerbang yang menangkapnya.** Ketiganya
memeriksa TIPE dan PENYUSUNAN MODUL; tak satu pun benar-benar MERENDER. Dengan
78 halaman yang akan lahir, celah itu akan terulang.

Maka ditambah gerbang keempat:

```powershell
npm run uji-render                  # bangun bundel SSR sekali
php artisan smartpro:uji-render     # render tiap halaman Inertia di Node
```

`resources/js/uji-render.tsx` (pembungkusnya cerminan `app.tsx`) +
`app/Console/Commands/UjiRender.php`. Perintahnya menembak tiap rute GET tanpa
parameter sebagai pengguna nyata, mengambil payload Inertia dari HTML-nya
(halaman Blade lama dilewati sendiri), lalu merendernya di Node. Halaman yang
melempar dilaporkan beserta pesan galatnya.

Daftarnya datang dari tabel rute, jadi **tiap halaman Fase 4–12 ikut terjaga
sendiri** tanpa daftar kedua yang harus diingat orang. Terbukti bekerja: dengan
`TooltipProvider` dilepas, gerbangnya merah berbunyi
`` `Tooltip` must be used within `TooltipProvider` ``; dengan dipasang, hijau.

Halaman BERPARAMETER (`/documents/{document}`) sengaja dilewati — menebak id
yang sah berarti perintah ini menyimpan pengetahuan tentang data, dan
pengetahuan itu akan basi. Halaman berparameter tetap diuji lewat test biasa.

`bootstrap/ssr/` masuk `.gitignore` (hasil bangun, seperti `public/build`).

### Keputusan Fase 3 yang perlu kamu ketahui

- **Ziggy DIPASANG** (`tightenco/ziggy` ^2.6, atas persetujuanmu). Pemicunya
  angka: 229 pemanggilan `route()` atas 100 nama rute berbeda tersebar di view
  yang akan dimigrasi. Tanpa Ziggy ketiganya harus jadi URL mentah di TSX —
  dan tiap perubahan `routes/web.php` kelak memutus halaman diam-diam.
  Risikonya diperiksa: `@routes` hanya membagikan peta **nama → URL**, bukan
  izin. Middleware `can:` tak tersentuh (pakem P4). Tipenya dideklarasikan
  seadanya di `types/ziggy.d.ts` — berkas bangkitan `ziggy:generate --types`
  sengaja tidak dipakai karena harus disegarkan tiap rute lahir, dan yang basi
  justru berbohong.
- **Vite TIDAK dinaikkan** ke 8, dan tak perlu. Build vite 7 bersih (2.552
  modul), `@vitejs/plugin-react` sudah dipatok `^5` sejak Fase 1 justru untuk
  itu. Menaikkannya berarti mengganti `laravel-vite-plugin` + `@tailwindcss/vite`
  sekaligus demi nol perbaikan yang terlihat. Ditinjau ulang hanya bila sebuah
  paket yang benar-benar dibutuhkan menuntut vite 8.
- **`@fontsource-variable/geist` dibuang** dari `app.css` — preset menggantinya
  dengan DM Sans + Inter, dan `@theme { --font-sans: 'Instrument Sans' }` yang
  tertinggal dari Fase 1 sudah tak pernah menang atas `@theme inline`.
- **`/lab` naik pangkat** jadi gerbang Fase 3: kini di balik `auth`+`active`
  dan merender AppLayout utuh. Dihapus setelah Fase 4 punya halaman nyata.

## Fase 4 — Autentikasi ✅ SELESAI (2026-08-28)

Tiga view tamu pindah ke Inertia, **dan** kerangka aplikasi disamakan dengan
blok resmi `dashboard-01` atas permintaan pemilik.

### 4.0 Penyamaan kerangka dengan `dashboard-01` — di luar rencana, diminta pemilik

Pemicunya keluhan: `/lab` "tidak mirip demo shadcn". Diagnosis dengan
tangkapan layar headless Chrome (bukan tebakan) menemukan lima sebab:
isi `/lab` memang cuma tiga elemen · `card`/`chart`/`tabs`/`select`/`label`/
`checkbox` belum terpasang sama sekali · blok `dashboard-01` sengaja tak ditarik
di Fase 3.1 · **kerangkanya memang meleset dari blok** · palet rose/mauve
memang bukan palet demo (keputusan Fase 3.0, dipertahankan).

Keputusan pemilik: **ambil kode dari registry, jangan mengarang komponen
sendiri.** Widget/tabel/modal/form fase berikutnya juga wajib dari blok.

Kodenya ditarik lewat `npx shadcn@4.19.0 view dashboard-01` (MCP `shadcn`
timeout, CLI dipakai sebagai gantinya) lalu disalin:

| Berkas | Yang disamakan |
|---|---|
| `layouts/AppLayout.tsx` | `--sidebar-width: calc(var(--spacing)*72)` (18rem, dulu 16rem) · `--header-height: calc(var(--spacing)*12)` (3rem, dulu 3.5rem) · `@container/main` · `gap-4 py-4 md:gap-6 md:py-6` |
| `components/SiteHeader.tsx` | `h-(--header-height)` · `border-b` · `transition-[width,height] ease-linear` · talang dalam `px-4 lg:gap-2 lg:px-6` · `Separator` `mx-2 data-[orientation=vertical]:h-4` · `h1 text-base font-medium` |
| `components/AppSidebar.tsx` | `collapsible="offcanvas"` + `variant="inset"` · tombol merek `data-[slot=sidebar-menu-button]:p-1.5!` · `SidebarFooter` |
| `components/NavUser.tsx` | **BARU** — salinan `nav-user.tsx`: `SidebarMenuButton size="lg"`, avatar `size-8 rounded-lg`, `EllipsisVertical`, menu `w-(--radix-dropdown-menu-trigger-width)` |
| `layouts/AuthLayout.tsx` | salinan `login-02/page.tsx`: `grid min-h-svh lg:grid-cols-2`, kolom kiri `p-6 md:p-10`, kolom kanan `bg-muted` + `object-cover dark:brightness-[0.2] dark:grayscale` |

**Menu pengguna PINDAH** dari topbar ke kaki sidebar — di `dashboard-01`
tempatnya memang di sana. Toggle tema & lonceng tetap di topbar (tak ada
padanannya di blok, tapi wajib menurut CLAUDE.md §13; keduanya `Button
variant="ghost" size="icon"` shadcn, bukan tombol karangan).

Satu penyimpangan sadar: talang `px-4 lg:px-6` dipasang di `AppLayout`, bukan
di tiap anak seperti di blok. Hasil visualnya sama persis, tapi 78 halaman
berikutnya tak perlu mengingat menuliskannya.

Komponen registry yang ditambah: `field label card select checkbox alert`
(+ `separator` diperbarui). **Nol paket npm baru** — semuanya di atas `radix-ui`
yang sudah ada.

### 4.1 Tiga halaman

| Halaman | Dari | Blok sumber |
|---|---|---|
| `pages/Auth/Login.tsx` | `auth/login.blade.php` | `login-02/login-form.tsx` |
| `pages/Auth/Register.tsx` | `auth/register.blade.php` | idem, tujuh field di grid `sm:grid-cols-2` |
| `pages/Auth/Pending.tsx` | `auth/pending.blade.php` | idem + `Alert variant="destructive"` |

Controller: `LoginController@create`, `RegisterController@create`,
`PageController@pending` → `Inertia::render`. **Bentuk props tak berubah
sedikit pun** (`departments`, `name`, `status`).

Blade lamanya BELUM dihapus (butuh konfirmasi, CLAUDE.md §4) — sudah tak
dirujuk siapa pun, dibuang bersama sisanya di Fase 13.1.

### 4.2 Gerbang render diperluas — celah yang baru ketahuan

`smartpro:uji-render` menyapu sebagai SATU pengguna aktif. Halaman tamu
membalas 302 bagi yang sudah masuk, dan halaman tunggu cuma terbuka bagi akun
belum aktif — jadi **ketiga halaman Fase 4 mustahil terlihat gerbang itu.**
Persis jenis halaman yang gerbangnya lahir untuk menjaga.

Sekarang tiga penyapu: pengguna `--nrp`, TAMU, lalu akun belum aktif pertama
yang ditemukan di DB (dicari, bukan dibuat — gerbang asap tak boleh
meninggalkan sampah).

Jebakan yang termakan saat menulisnya, dicatat supaya tak terulang:
`Auth::guard('web')->logout()` saja **tidak cukup**. Guard yang sudah di-cache
selama sapuan sebelumnya masih dipegang middleware `guest`, jadi `/login` tetap
302 padahal `Auth::check()` sudah `false` — dan diamnya total: halamannya cuma
tak pernah muncul di laporan. Perlu `Auth::forgetGuards()` menyertainya.

### 4.3 Gerbang

- [x] `php artisan test` → **623 lulus + 1 dilewati (4279 asersi)**. Patokan
      naik dari 615 ke **624**: `AutentikasiInertiaTest` (9 tes) mengunci
      komponen tiap rute, kunci galat `nrp`, batas percobaan login, dan
      penjagaan akun belum aktif. Dua `assertSee` di `MasterDataTest`
      (departemen di halaman pendaftaran) jadi `assertInertia` — diubah gaya
      asersinya, **tidak dihapus** (pakem P7).
- [x] `npm run build` bersih · `tsc --noEmit` bersih
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **4 halaman**
      (`lab`, `login`, `register`, `pending`) dirender tanpa galat
- [x] `md5sum -c mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [x] Tangkapan layar headless Chrome: `/login`, `/register`, `/lab` — ketiganya
      cocok dengan susunan bloknya
- [!] **Pemeriksaan mata — butuh kamu:** login/daftar/tunggu di
      `http://localhost:9092`, plus mode gelap. Dan putuskan dua hal di bawah.

### Tiga keputusanmu sebelum Fase 5 — SUDAH DIJAWAB (2026-08-29)

1. **`/lab` dihapus.** Rute + `pages/Lab.tsx` + impor `Inertia` di
   `routes/web.php` yang tinggal menganggur. Nol rujukan tersisa
   (`grep` bersih di `tests/`, `app/`, `resources/js/`).
2. **Kode diambil sepenuhnya dari registry shadcn.** Paket npm baru yang ikut
   terbawa komponennya: `recharts` (lewat `chart`), `react-day-picker` +
   `date-fns` (lewat `calendar`). Ketiganya dependensi resmi komponen registry,
   bukan pilihan sendiri.
3. **Foto panel kanan halaman tamu DIBIARKAN** (`curved6.jpg`) — halaman
   masuk & daftar tetap memakai blok `login-02`, fotonya saja yang bertahan.

---

## Fase 5 — Dashboard ✅ SELESAI (2026-08-29)

`dashboard.blade.php` (836 baris) + tujuh partial widget →
`pages/Dashboard.tsx` + sepuluh komponen di `components/dasbor/`.

### 5.1 Komponen registry yang ditarik

`chart` · `toggle-group` (+ `toggle`) · `progress` · `dialog` · `radio-group` ·
`tabs` · `calendar` · `textarea`. Tiga paket npm ikut sebagai dependensinya:
**`recharts`**, **`react-day-picker`**, **`date-fns`**.

### 5.2 Blok sumber tiap kartu — nol komponen karangan sendiri

| Komponen | Dari blok | Catatan |
|---|---|---|
| `GrafikOverview.tsx` | `dashboard-01/chart-area-interactive.tsx` | `ToggleGroup` + `Select` `@[767px]/card`, dua `linearGradient`, `Area type="natural"` |
| `MeterTertinjau.tsx` | `chart-radial-text` | busur dihitung dari persen (`3.6°`/persen); di blok sudutnya statis |
| `KartuSebaran.tsx` | `chart-pie-donut-text` | warna ruas dari `DocumentType::RUPA` |
| `KartuStatistik.tsx` | `dashboard-01/section-cards.tsx` | grid & gradasi kartu disalin; `px-4 lg:px-6` dilepas (sudah di `AppLayout`) |
| `KartuDistribusi.tsx` | `Card` + `Table` + `Tabs` + `ScrollArea` | Alpine `x-show` → `Tabs` |
| `KartuPerjalanan.tsx` | `Card` + `Table` + `Progress` + `Badge` | |
| `KartuKetersediaan.tsx` | `Calendar` + `Dialog` + `RadioGroup` | grid hari digambar react-day-picker |
| `KartuSambutan.tsx`, `KartuMasukan.tsx`, `KartuLog.tsx`, `PitaCakupan.tsx`, `TumpukanWajah.tsx` | `Card`/`Badge`/`ScrollArea`/`Progress`/`Avatar` | susunan primitif registry, bukan widget karangan |

**Tiga penyimpangan sadar**, sesuai CLAUDE.md §13 (wajib ditulis alasannya):

- Badge "+12.5%" di `section-cards` diganti IKON jenis kartu. SmartPro tak
  menghitung tren per kartu, dan angka tren palsu lebih buruk daripada tak ada.
- `stackId` di `chart-area-interactive` DIBUANG. "Dibuat" dan "Berlaku" bukan
  dua bagian dari satu jumlah — dokumen yang sama dihitung di keduanya, jadi
  menumpuknya menggambar total yang tak berarti apa-apa.
- Navigasi bulan bawaan `Calendar` dimatikan (`hideNavigation`) dan diganti dua
  `<Link>` Inertia. MIGRASI §Fase 5 mewajibkan tombol bulan tetap TAUTAN
  (`?bulan=YYYY-MM`), bukan JS — supaya bisa dibuka di tab baru & di-bookmark.

### 5.3 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

Blade lama menghitung banyak hal di dalam view. Semuanya aturan per JABATAN atau
per ALUR DOKUMEN, jadi rumahnya server:

- **`App\Services\DasborTampilan` (BARU)** — kartu sambutan (`hero`), empat
  kartu statistik (`tiles`), dan peta aksi feed aktivitas (`activities`).
  Kelas tersendiri, bukan ditambahkan ke controller: `DashboardController` sudah
  575 baris sebelum migrasi dan CLAUDE.md §3 menyuruh memecah di ~300.
- **`menungguDiMeja[]`** kini membawa `daftarTahap`, `ke`, `persen`, `tingkat`,
  `nomor`, `tautan`, dan `pembuat`. Yang paling penting `daftarTahap`: segmen MD
  hanya muncul bila `Document::perluTinjauanMd()` benar, sehingga JSA tak pernah
  dijanjikan tahap yang tak ada dalam alurnya.
- **`masukanWidget[]`** diratakan (`displayNumber()`, `statusLabel()`,
  `diffForHumans()` dipanggil di server).
- **`distribusiWidget.bolehBukaHalaman`** menggantikan `auth()->user()->
  bisaLihatDistribusi()` yang dulu dipanggil di dalam Blade.
- **`isNonStaff`** jadi bendera kelima di samping `isCreator`/`isPjo`/
  `isDeptHead`/`isMd` — supaya kunci mentah `staff` tak jadi aturan yang hidup
  di klien (CLAUDE.md §6).
- **`kalenderOff.sel[].tanggal`** dikirim `'Y-m-d'`, bukan objek Carbon: Carbon
  yang diserialkan sendiri jadi ISO ber-zona UTC — tepat pergeseran 8 jam yang
  membuat tanggal 1 WITA terbaca sebagai tanggal 31 bulan sebelumnya di klien.

`stats`, `queues`, `tren`, `matrix`, `jenisList`, `sebaran`, `greeting`, dan
keempat bendera peran **tak berubah sedikit pun**.

> `matrix` dan `jenisList` ternyata **tak dirender satu pun Blade** (`grep`
> bersih di `resources/views/`). Keduanya TETAP dikirim — props tak dikurangi —
> tapi catat: klaim CLAUDE.md §13 "hex-nya juga jadi titik warna di matriks
> dasbor" sudah tidak benar. Matriksnya memang tak pernah digambar.

### 5.4 Fitur yang HAMPIR HILANG — modal tugas pasca-login

`partials/_modal-antrean.blade.php` dipanggil dari `layouts/app.blade.php:1510`,
bukan dari `dashboard.blade.php`. Begitu dashboard pindah ke Inertia, ia lenyap
tanpa satu pun tes bicara — `AntreanTugasTest` yang menangkapnya.

Perbaikannya di lapisan yang benar: props **`antreanAwal`** dibagikan
`HandleInertiaRequests` (bukan `DashboardController`), dirender
`components/DialogAntrean.tsx` dari `AppLayout`. Alasannya `LoginController`
tak berjanji tujuan sesudah login selalu dashboard.

### 5.5 Tes — 624 → **625** (naik satu, nol berkurang)

`php artisan test` → **624 lulus + 1 dilewati (4454 asersi)**.

Sebelas tes di sepuluh berkas dulu membaca MARKUP dashboard; seluruhnya diubah
GAYA ASERSINYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `DashboardRenderTest` | `assertSee('Overview Dokumen')` | `assertInertia` + props `tren`/`sebaran`/`tiles` |
| `DashboardIkhtisarTest` | `viewData('tren')` | `propsInertia()['tren']` |
| `DashboardWidgetTest` | `viewData()`, `badge-soft-warning` | props `menungguDiMeja[].tingkat` |
| `SpecDashboardV3Test` | `.btn-rentang`, `apexcharts`, `.pp-naik`, `.pp-gulir-tipis` | props tren/sebaran/distribusi + **tes baru** `statusMeta` |
| `MenuPengaturanTest` | `<div class="section-label">` | props `navigation[].label` |
| `LogDokumenTest`, `InformasiTest` | `assertSee(route(...))` | `navigation[].href` |
| `ManajemenAksesTest` | `pp-subnav-mati` + `href` | `navigation[].locked` + `href === null` |
| `ReviewerAvailabilityTest` | `data-tanggal="…"`, `is-off` | `kalenderOff.sel[]` |
| `DistribusiInformasiTest`, `DocumentDistributionTest` | `viewData('distribusiWidget')` | `propsInertia()` |
| `AntreanTugasTest` | `assertSee('hal yang menunggu Anda')` | props `antreanAwal` |

Yang **bertambah**: `SpecDashboardV3Test::test_warna_status_dashboard_datang_dari_status_meta`
— pengganti pemindaian `.badge.bg-*` yang di halaman Inertia jadi lolos
trivial (JSON payload meng-escape tanda kutipnya). Ia menuntut hal yang lebih
tegas daripada pendahulunya: 13 kunci `statusMeta` + `statusLabels` terkirim,
`verifikasi_md` termasuk. Pemindaian gradasi lama TETAP berjalan untuk tiga rute
yang masih Blade.

Dua penolong baru di `Tests\TestCase`, supaya sembilan berkas tak menulis
pembaca props sendiri-sendiri:
`propsInertia(TestResponse)` dan `menuSidebar(TestResponse)`.

### 5.6 Gerbang

- [x] `php artisan test` → **624 lulus + 1 dilewati (4454 asersi)** — patokan
      naik 624 → **625**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **4 halaman**
      (`dashboard`, `login`, `register`, `pending`); dashboard 222 KB HTML.
      `lab` hilang dari daftar karena rutenya memang sudah dihapus.
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [!] **Pemeriksaan mata — butuh kamu:** buka `http://localhost:9092/dashboard`
      bergantian dengan **lima peran** (GL `14040677`, SH `16081467`,
      DH `17092728`, PJO `16071367`, Admin `ADM-0001`, MD `MD-0001`,
      Non-Staff `20260219`) dan adu berdampingan dengan port 9091. Yang perlu
      dicocokkan: **tiap angka statistik harus sama**, kartu kanan berbeda
      per jabatan, dan Distribusi TIDAK muncul bagi Non-Staff. Plus mode gelap
      dan `?bulan=` maju-mundur di kartu Ketersediaan.

### Utang yang dibawa Fase 5

- `dashboard.blade.php` + tujuh partial widget **belum dihapus** (CLAUDE.md §4
  butuh konfirmasi). Sudah tak dirujuk siapa pun; dibuang bersama sisanya di
  Fase 13.1.
- Bundel `Dashboard.js` 602 KB (gzip 173 KB) — seluruhnya recharts. Vite memberi
  peringatan >500 KB. Dibiarkan: ia chunk per-halaman yang dimuat hanya saat
  dashboard dibuka, dan memecahnya lebih jauh berarti mengarang `manualChunks`
  demi angka peringatan, bukan demi waktu muat yang terukur.

### Penyesuaian widget dashboard — MENYUSUL (ketetapan pemilik, 2026-08-29)

Dashboard sudah berpindah; **isi tiap widget akan disetel lagi di lain hari.**
Kelenturannya sudah ada dan disengaja, jadi penyesuaian nanti tak menuntut
halaman ditulis ulang:

| Yang ingin diubah | Sentuh berkas ini | Yang TIDAK ikut tersentuh |
|---|---|---|
| Rupa/isi satu kartu | satu berkas di `components/dasbor/` | sembilan kartu lain |
| Kartu mana yang muncul & di kolom mana | `pages/Dashboard.tsx` (`adaMeja`/`adaMasukan`/grid) | seluruh komponen kartu |
| ANGKA di dalam kartu | `DashboardController` + `DasborTampilan` | seluruh TSX |
| Kalimat sambutan & empat kartu statistik per peran | `App\Services\DasborTampilan` | `DashboardController` |

Tiap kartu adalah komponen tersendiri yang menerima props — nol kartu membaca
`usePage()` sendiri, jadi memindahkan, menyembunyikan, atau menukar isinya cuma
soal apa yang dioper `Dashboard.tsx`. Yang **tak boleh** dilakukan saat menyetel
nanti: memindahkan syarat "kartu ini untuk jabatan apa" ke dalam TSX. Aturannya
tetap datang dari server sebagai bendera (`isCreator`/`isPjo`/`isDeptHead`/
`isMd`/`isNonStaff`) — CLAUDE.md §4.

---

## Fase 6 — Pengguna & akses ✅ SELESAI (2026-08-29)

Enam view (`users/{index,create,edit,pending}`, `akses/index` + `_form-profil`,
`account/info`) → enam halaman TSX. Ini fase CETAKAN: pola CRUD + tabel +
penyaring + dialog di sini yang disalin Fase 7–12.

### 6.1 Halaman & controller

| Rute | Halaman TSX | Controller |
|---|---|---|
| `users.index` | `pages/Users/Index.tsx` | `UserManagementController@index` |
| `users.create` | `pages/Users/Create.tsx` | `@create` |
| `users.edit` | `pages/Users/Edit.tsx` | `@edit` |
| `users.pending` | `pages/Users/Pending.tsx` | `UserApprovalController@index` |
| `akses.index` | `pages/Akses/Index.tsx` | `AccessProfileController@index` |
| `account.info` | `pages/Account/Info.tsx` | `PageController@account` |

`akses/_form-profil.blade.php` yang di-`@include` dua kali menjadi SATU komponen
`DialogProfil` di dalam `Akses/Index.tsx` — dipakai "Profil Baru" dan "Ubah",
persis alasan yang sudah tertulis di kepala berkas Blade-nya.

Nol komponen registry baru ditarik dan **nol paket npm baru**: `card`, `table`,
`dialog`, `alert-dialog`, `checkbox`, `select`, `field`, `badge`, `separator`
semuanya sudah terpasang sejak Fase 3–5.

### 6.2 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

- **`User::ROLE_LABELS` + `User::ROLE_DESKRIPSI` (BARU).** Peta peran → label
  dulu tersalin di TIGA Blade sekaligus, dan komentar di `users/edit` sendiri
  sudah mencatat kekhawatirannya. Salinan keempat akan duduk di klien, tempat
  CLAUDE.md §4 melarangnya. Dua peta, bukan satu yang disambung: daftar user
  memakai nama pendek, dropdown pemilihan peran memakai kalimat penjelas.
- **`User::barisAdmin()` (BARU).** Satu bentuk baris untuk tiga tabel (daftar
  user, akun MD, persetujuan pendaftaran) menggantikan `getRoleNames()->first()`,
  `jabatanLabel()`, `isActive()`, dan `$u->department->code` yang dulu dipanggil
  dari dalam Blade. Kunci mentah `jabatan` sengaja TIDAK ikut — yang tak dikirim
  mustahil tercetak (CLAUDE.md §6).
- **`DocumentType::RUPA` dikirim sebagai props `rupa`** di Manajemen Akses.
  `DocumentType::rupa($kode)` dulu dipanggil di dalam Blade; hex warnanya tak
  boleh diketik ulang di TSX (P3).
- **`PageController@account`** tak lagi mengirim model User. Yang dikirim
  larik datar berisi `photo_url`, `jabatan_label`, `peran`, dan seterusnya —
  model mentah ikut membocorkan `password` & `remember_token` ke payload yang
  bisa dibaca siapa pun lewat "view source".

Prop lain **tak berubah bentuknya**: `departments`, `filters`, `roles`,
`peranSekarang`, `jenis`, `profiles`, `groupLeaders`, `users`.

### 6.3 Empat keputusan kecil yang perlu diketahui

1. **Paginator dipetakan dengan `->through()`, bukan `->map()`.** `map()` pada
   paginator mengembalikan Collection biasa — daftarnya tetap tampil, hanya
   `links`/`from`/`to`/`total` yang lenyap, sehingga tombol halaman 2 hilang
   tanpa satu pun galat. Dikunci `PenggunaInertiaTest`.
2. **Lencana peran memakai `variant` Badge registry, bukan tujuh warna karangan.**
   Badge shadcn punya enam varian semantik, sementara Blade lama memberi tujuh
   rona Bootstrap per peran. Mengarang hex per peran di TSX persis kesalahan
   yang dilarang P3. Yang hilang cuma hiasan; labelnya tetap terbaca penuh.
3. **`components/LencanaStatusAkun.tsx` (BARU)** — status AKUN (`users.status`),
   terpisah dari `StatusBadge` yang membaca `Document::STATUS_META`. Nilainya
   dicetak apa adanya seperti Blade lama (`text-capitalize`): `rejected` bermakna
   GANDA di kolom itu — pendaftaran ditolak ATAU akun dinonaktifkan — jadi
   menamainya ulang di layar akan salah pada salah satu dari keduanya.
   **Ini yang paling layak kamu putuskan kelak** (lihat "Utang" di bawah).
4. **Penyaring tetap query string.** `router.get(...)` dengan `preserveState`,
   bukan fetch — halaman hasil saringan tetap bisa di-bookmark dan dibuka di tab
   baru, sama seperti form GET Blade lama.

### 6.4 Tes — 625 → **632** (naik tujuh, nol berkurang)

`php artisan test` → **631 lulus + 1 dilewati (4550 asersi)**.

Tiga asersi berpindah GAYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `MdAccountConfigTest` | `assertSee('Akun Management Development')` | props `akunMd[].nrp` |
| `UserApprovalTest` | `assertSee('Akses: Non-Staff')` | props `pendingUsers[].jabatan_diajukan` + `.jabatan_label` |
| `ManajemenAksesTest` | `assertSee('Profil &quot;…&quot; dibuat.')` | props `flash.success` |

> Ketiganya bukan sekadar terjemahan: kalimat "Akses: Non-Staff" dan judul kartu
> kini dirangkai di TSX, jadi mustahil ada di payload. Yang diperiksa tetap hal
> yang sama — nilai-nilainya sampai, dan berbeda satu sama lain.

Yang **bertambah**: `tests/Feature/PenggunaInertiaTest.php` (**7 tes / 94
asersi**) — komponen tiap rute, bentuk paginator utuh, kelengkapan
`roleLabels`, penyaring yang menyaring di SERVER, dan penjagaan `password` /
`remember_token` / kunci mentah `jabatan` tak pernah ikut ke payload.

Dua asersi lama SENGAJA dibiarkan apa adanya karena masih bekerja di atas
payload Inertia: `LabelJabatanTest` (`Non-Staff` muncul lewat props `roleLabels`
& `user.jabatan_label`) dan `ManajemenAksesTest::test_filter_penetapan_…`
(NRP tetap terbaca sebagai string biasa di JSON).

### 6.5 Gerbang

- [x] `php artisan test` → **631 lulus + 1 dilewati (4550 asersi)** — patokan
      naik 625 → **632**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih (EXIT=0)
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **9 halaman**
      (`dashboard`, `account.info`, `users.pending`, `users.index`,
      `users.create`, `akses.index`, `login`, `register`, `pending`).
      `users.edit` tak ikut karena berparameter — ia dijaga `PenggunaInertiaTest`.
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091:
      - `/users` sebagai `ADM-0001` — kartu MD, tiga saklarnya, penyaring,
        halaman 2, Ubah Peran, Nonaktifkan, Hapus (akun yang punya dokumen
        harus DITOLAK dengan pesan yang mengarahkan ke Nonaktifkan)
      - `/users/pending` sebagai PJO `16071367` **dan** SH `16081467` —
        PJO lintas 7 dept, SH hanya departemennya
      - `/akses` — buat profil, ubah, hapus, tetapkan & cabut ke GL, keempat
        saringan (termasuk "— Tanpa Akses —")
      - `/akun` sebagai peran mana pun — unggah foto, pratinjaunya, centang
        hapus foto, simpan
      - mode gelap di keenam halaman

### Utang & satu keputusan yang menunggumu

- **Enam Blade lama belum dihapus** (`users/*.blade.php`, `akses/*.blade.php`,
  `account/info.blade.php`) — CLAUDE.md §4 butuh konfirmasi. Sudah tak dirujuk
  siapa pun; dibuang bersama sisanya di Fase 13.1.
- **[!] Kata "Rejected" di layar.** Kolom `users.status` memakai satu nilai
  (`rejected`) untuk DUA arti: pendaftaran yang ditolak, dan akun yang
  dinonaktifkan Admin. Blade lama mencetaknya apa adanya, dan itu dipertahankan
  supaya tak ada makna yang berubah diam-diam. Kalau kamu mau layarnya berbunyi
  "Nonaktif", itu keputusanmu — dan tempat mengubahnya cuma SATU berkas
  (`components/LencanaStatusAkun.tsx`), sebab peta itu memang sengaja tak
  disalin ke mana-mana.

---

## Fase 7 — Pengaturan & master data ✅ SELESAI (2026-08-29)

Tiga view (`pengaturan/{penomoran,master,sistem}`) → tiga halaman TSX. Plus SATU
modal yang dokumen MIGRASI daftarkan di Fase 11 tapi sesungguhnya hanya hidup di
layar ini — lihat 7.4.

### 7.1 Halaman & controller

| Rute | Halaman TSX | Controller |
|---|---|---|
| `pengaturan.penomoran` | `pages/Pengaturan/Penomoran.tsx` | `PengaturanController@penomoran` |
| `pengaturan.master` | `pages/Pengaturan/Master.tsx` | `MasterDataController@index` |
| `pengaturan.sistem` | `pages/Pengaturan/Sistem.tsx` | `PengaturanController@sistem` |

Satu komponen registry baru: **`switch`** (`npx shadcn add switch`) — padanan
`form-check form-switch` Bootstrap yang dipakai 25 kali di Master Data dan sekali
di saklar AI. **Nol paket npm baru**: ia di atas `radix-ui` yang sudah ada.

### 7.2 KEBOCORAN yang ditutup — kunci API di dalam payload

`Pengaturan::ai()` mengembalikan `key` dan `cadangan_key` dalam bentuk
**terbaca**. Blade lama menerima larik itu utuh dan aman, sebab ia tak pernah
mencetak keduanya. Props Inertia tak punya kemewahan itu: seluruh props tertulis
di atribut `data-page` dan terbaca siapa pun lewat "view source".

Controller sekarang membuang keduanya (`Arr::except`) sebelum props dirakit;
yang sampai ke layar hanya bentuk bertopengnya. Dikunci
`PengaturanInertiaTest::test_kunci_api_tak_pernah_ikut_ke_payload` — yang
memeriksa DUA hal sekaligus: kunci tak ada di props, dan teks aslinya tak ada di
HTML.

> Ini kelas kesalahan yang sama dengan `PageController@account` di Fase 6
> (`password` + `remember_token`). Untuk fase berikutnya: **tiap kali sebuah
> larik/model pindah dari Blade ke props, periksa isinya kolom per kolom.**
> Blade menyembunyikan yang tak dicetak; Inertia tidak.

### 7.3 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

Blade Master Data memanggil METHOD dari dalam view, dan method tak ikut
terserialkan ke JSON:

- **`$k->kolom()`** → props `kategori[].kolom` (sudah disaring terhadap
  `KOLOM_TERSEDIA`). Kolom mentah `kolom_json` sengaja TIDAK ikut.
- **`DocumentType::rupa($j->code)`** → props `rupa` (peta `RUPA` utuh, seperti
  di Manajemen Akses Fase 6). Hex-nya tak boleh diketik di TSX (P3).
- **`InformasiKategori::KOLOM_TERSEDIA`** → props `kolomTersedia`.
- **`SimpanAiRequest::PENYEDIA`** → sudah props `penyedia` sejak dulu; kini
  dikunci tes supaya daftarnya tetap datang dari Form Request, bukan disalin.

Ketiga daftar Master Data juga **diratakan** jadi larik datar (`departemen`,
`jenis`, `kategori`) — nama propsnya tak berubah, isinya hanya kolom yang
memang digambar layar. `jumlahInformasi`, `prefix`, `namaSite`, `keyTopeng`,
`cadanganKeyTopeng`, `kesehatan` **tak berubah sedikit pun**.

### 7.4 Fitur yang HAMPIR HILANG — modal Bersihkan Seluruh Dokumen

`documents/_modal-musnahkan-semua.blade.php` di-`@include` dari
`pengaturan/sistem.blade.php:336`. Dokumen MIGRASI mendaftarkannya di **Fase
11**, padahal `grep` membuktikan ia tak dirender dari tempat lain mana pun —
jadi memindahkan layar Sistem tanpa membawanya berarti tombol Zona Berbahaya
kehilangan jendelanya, dan `ManajemenUserDanPemusnahanTest` yang menangkapnya.

Ia kini komponen lokal `ZonaBerbahaya` di `pages/Pengaturan/Sistem.tsx`. **Fase
11 tak perlu mengerjakannya lagi.** Kedua penghalangnya dipertahankan apa
adanya: alasan tertulis (min 10 aksara) + mengetik frasa
`MUSNAHKAN SEMUA DOKUMEN`, lalu `AlertDialog` menyusul saat dikirim.

### 7.5 Empat keputusan kecil yang perlu diketahui

1. **Atribut `form="…"` DIPERTAHANKAN di Master Data.** Godaannya besar untuk
   membuang pola itu — "React kan bisa membungkus `<tr>`" — tapi tidak bisa:
   bundel SSR (`smartpro:uji-render`) menghasilkan markup yang tetap dibaca
   parser HTML peramban, dan parser itu memindahkan `<form>` keluar tabel
   diam-diam. Yang tersisa adalah tabel yang tombol Simpannya tak mengirim satu
   pun isian. Jadi: satu `<form>` KOSONG per baris + `form={idForm}` pada tiap
   `Input`, persis seperti Blade lama.
2. **Tiap baris punya `errorBag` sendiri** (`dept-7`, `jenis-2`, `kat-baru`).
   Props `errors` itu satu untuk seluruh halaman; tanpa kantong terpisah, satu
   baris yang gagal validasi mewarnai merah SELURUH baris di tabelnya.
   Kantongnya dioper lewat opsi kunjungan (`form.put(url, { errorBag })`),
   **bukan** lewat argumen pertama `useForm` — argumen itu `rememberKey`, hal
   yang sama sekali berbeda.
3. **`ConfirmDialog` kini bisa DIKENDALIKAN dari luar** (`buka` +
   `onUbahBuka`, `pemicu` jadi opsional). Sebabnya bukan selera: tombol Simpan
   harus tetap `type="submit"` supaya `required` peramban bekerja, sementara
   konfirmasi harus MENYUSUL sesudah formulirnya lolos. Dengan pemicu saja,
   setiap tombol berkonfirmasi terpaksa `type="button"` dan seluruh validasi
   bawaan peramban ikut mati diam-diam. Pola ini yang dibutuhkan Fase 11 untuk
   "Ajukan Revisi wajib beralasan" (CLAUDE.md §6).
4. **Ikon kategori tetap teks bebas `bi-*`.** Nilainya tersimpan di
   `informasi_kategori.ikon` dan ikut terkirim ke aplikasi mobile, jadi ia tak
   boleh diganti nama lucide (CLAUDE.md §13). Layar menggambar pratinjaunya
   lewat `components/Ikon.tsx`, sehingga nama yang belum terpetakan langsung
   terlihat sebagai lingkaran netral saat diketik — bukan sesudah dirilis.

### 7.6 Tes — 632 → **641** (naik sembilan, nol berkurang)

`php artisan test` → **640 lulus + 1 dilewati (4704 asersi)**.

Dua asersi berpindah GAYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `ManajemenUserDanPemusnahanTest::…kartu_bersihkan_dokumen_pindah…` | `assertSee('Bersihkan Seluruh Dokumen')` + `assertDontSee` di `/users` | props `kesehatan.dokumen_semua` ada di Sistem, dan `users.index` tak punya kunci `kesehatan`/`jumlahDokumen` |
| `ManajemenUserDanPemusnahanTest::…angka_zona_berbahaya…` | `assertSee("Menghapus N dokumen")` | `where('kesehatan.dokumen_semua', N)` |

Empat asersi lama SENGAJA dibiarkan apa adanya karena masih bekerja di atas
payload Inertia: `PengaturanSiteTest` (`assertSee('PPA-ADRO')`),
`KonfigurasiSistemTest` (`assertDontSee('rahasia-sekali-9999')` +
`assertSee('9999')` — keduanya justru makin bermakna sekarang), dan
`KonfigurasiSistemTest::test_menu_konfigurasi_sistem_hanya_untuk_admin`.

Yang **bertambah**: `tests/Feature/PengaturanInertiaTest.php` (**9 tes / 139
asersi**) — komponen tiap rute, kebocoran kunci API, kelengkapan `penyedia` /
`kolomTersedia` / `rupa`, urutan jenis yang mengikuti `DocumentType::RUPA`,
angka pengunci departemen, dan ketiga rute yang tetap 403 bagi bukan Admin (P4).

### 7.7 Gerbang

- [x] `php artisan test` → **640 lulus + 1 dilewati (4704 asersi)** — patokan
      naik 632 → **641**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih (EXIT=0)
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **12 halaman**
      (bertambah `pengaturan.penomoran`, `pengaturan.master` 304 KB,
      `pengaturan.sistem`)
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [x] Isi ketiga halaman diperiksa dari HASIL RENDER-nya, bukan dari kodenya:
      payload nyata ketiga rute diambil sebagai Admin, dirender lewat bundel
      SSR, lalu dicocokkan frasa demi frasa dengan Blade lamanya. 33/33 frasa
      ada; 8 form departemen + 6 jenis + 11 kategori + 25 saklar tergambar;
      **nol jejak kunci API** di HTML-nya.
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091, sebagai `ADM-0001`:
      - `/pengaturan/penomoran` — ketik prefix, pratinjau "Sekarang → Setelah
        disimpan" ikut berubah; Simpan → konfirmasi → pesan sukses; lalu
        **buat satu dokumen baru** dan periksa nomornya (MIGRASI §Fase 7)
      - `/pengaturan/master` — ubah nama departemen, alias, saklar Aktif;
        tambah departemen baru; kode departemen ber-dokumen harus **terkunci**;
        ubah nama jenis + matikan satu jenis lalu periksa menu Dokumen Baru;
        tambah kategori Informasi baru → **muncul di sidebar tanpa rilis kode**;
        centang/lepas kolom Edisi/No. Revisi/Tanggal Efektif
      - `/pengaturan/sistem` — simpan setelan AI tanpa mengisi kunci (**kunci
        lama harus bertahan**), centang hapus kunci, Uji koneksi AI, Kirim
        email uji (→ Mailpit), Bersihkan cache, lalu Zona Berbahaya:
        buka jendelanya, ketik frasa salah (**harus ditolak**) — jangan
        dijalankan pada data yang masih dipakai
      - mode gelap di ketiga halaman

### Utang yang dibawa Fase 7

- **Tiga Blade lama belum dihapus** (`pengaturan/{penomoran,master,sistem}.blade.php`)
  dan `documents/_modal-musnahkan-semua.blade.php` — CLAUDE.md §4 butuh
  konfirmasi. Keempatnya sudah tak dirujuk siapa pun; dibuang bersama sisanya di
  Fase 13.1.
- **Lencana peran/jenis** tetap memakai `variant` Badge registry (keputusan Fase
  6.3 butir 2). Yang berwarna hex di layar ini hanya KODE jenis dokumen, dan
  hexnya datang dari props `rupa`.

---

## Fase 8 — Daftar dokumen ✅ SELESAI (2026-08-29)

Enam view (`documents/{index,show,published,obsolete,revisions,staff-status}`,
973 baris Blade) → enam halaman TSX, **plus tujuh berkas yang didaftarkan
MIGRASI di Fase 11** dan tak bisa dipisah darinya — lihat 8.4.

### 8.1 Halaman & controller

| Rute | Halaman TSX | Controller |
|---|---|---|
| `documents.index` | `pages/Documents/Index.tsx` | `DocumentController@index` |
| `documents.show` | `pages/Documents/Show.tsx` | `DocumentController@show` |
| `documents.published` | `pages/Documents/Published.tsx` | `DocumentRevisionController@published` |
| `documents.obsolete` | `pages/Documents/Obsolete.tsx` | `@obsolete` |
| `documents.revisions` | `pages/Documents/Revisions.tsx` | `@revisions` |
| `documents.staffStatus` | `pages/Documents/StaffStatus.tsx` | `DocumentStaffStatusController@staffStatus` |

**Nol komponen registry baru dan nol paket npm baru.** Yang dipakai (`table`,
`card`, `dialog`, `alert-dialog`, `dropdown-menu`, `badge`, `checkbox`,
`select`, `textarea`, `progress`, `separator`) sudah terpasang sejak Fase 3–7.

### 8.2 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

- **`Document::barisDaftar(User)` (BARU).** Satu bentuk baris untuk keenam
  daftar. Lima Blade memanggil METHOD model dari dalam view —
  `displayNumber()`, `isArsip()`, `nomorLuarPola()`, `bisaDisuntingArsipOleh()`
  — dan method tak ikut terserialkan ke JSON. Tanpa perataan ini keempatnya
  harus ditulis ulang di TSX.
- **`AuditLog::AKSI_META` (BARU).** Peta 22 aksi → `[label, ikon, rona]` untuk
  timeline detail dokumen; dulu larik `@php` setinggi 23 baris di dalam
  `documents/show.blade.php`. Alasannya sama dengan `Document::STATUS_META`:
  satu peta label+ikon+warna yang punya dua salinan adalah peta yang suatu hari
  berselisih.
- **`DocumentController::barisMasukan()` + `::jadwalPembuat()` (BARU, static).**
  Bentuk satu masukan lapangan dan keterangan "pembuat sedang cuti", dipakai
  halaman detail DAN daftar Dokumen Berlaku. `jadwalPembuat()` juga MERATAKAN
  keluaran `ReviewerAvailability::untuk()`: yang digambar cuma tiga kolom,
  sementara `kembali` bertipe Carbon dan nama harinya HARUS dirangkai server
  (`translatedFormat` membaca locale aplikasi).
- **`statusOpsi`, `jenisBaru`, `judul`, `isGlDept`, `prefix` jadi props.**
  Kelimanya dulu dihitung di dalam Blade (`Document::STATUS_LABELS` disaring
  inline, `auth()->user()->jenisPertamaBoleh()`, `$isGlDept`,
  `Pengaturan::prefix()`). Ejaan "Status Dokumen **Staff**" dijaga
  `LabelJabatanTest`, jadi ia tak boleh punya salinan kedua (CLAUDE.md §6).
- **Tiap tombol per-baris jadi `boleh_*`** yang dijawab model
  (`bisaDirevisiOleh`, `bisaDiberiMasukanOleh`, `bisaDiberiMasukanSejawatOleh`,
  `bisaDisuntingArsipOleh`, `pemilikRevisi`). Nol penyaringan wewenang di klien;
  server tetap memeriksa ulang semuanya (pakem P4).

Prop lain **tak berubah namanya**: `documents`, `filters`, `types`,
`departments`, `showAllStatuses`, `filterType`, `canAll`, `selectedDept`,
`timeline`, `masukan`, `bolehMembalasMasukan`, `distribusi`,
`distribusiRincian`, `ketersediaanPembuat`.

Tiga props halaman Tidak Berlaku **berpindah tempat, bukan hilang**: `versi`,
`nomorTerpakai`, dan `adaBerlaku` dulu peta terpisah yang harus dicocokkan Blade
per baris; ketiganya kini sudah terjawab di dalam barisnya sendiri (`versi[]`,
`nomor_bentrok`, `boleh_rollback`).

### 8.3 Kebocoran yang ditutup

Keenam daftar menggendong relasi `creator` — model User beserta `password` dan
`remember_token`. Blade lama aman karena tak pernah mencetaknya; props Inertia
tidak punya kemewahan itu. Semua diratakan jadi kolom yang memang digambar
(`pembuat` = nama saja; di halaman detail `nameWithJabatan()`), begitu pula
`distribusiRincian` (yang menggendong `DocumentRead->user`) dan `masukan`
(`user` + `replier`). Dikunci
`DokumenInertiaTest::test_kolom_rahasia_pengguna_tak_pernah_ikut_ke_payload`,
yang memeriksa props DAN HTML-nya.

Ini kelas kesalahan yang sama dengan Fase 6 (`password`/`remember_token`) dan
Fase 7 (kunci API). **Tiap larik/model yang pindah dari Blade ke props wajib
diperiksa kolom per kolom.**

### 8.4 Tujuh berkas Fase 11 yang IKUT TERBAWA

`grep` membuktikan ketujuhnya di-`@include` dari halaman yang dipindahkan, jadi
memisahkannya berarti daftar tanpa jendelanya:

| Blade | Jadi |
|---|---|
| `documents/_modal-masukan` | `components/dokumen/DialogAksi.tsx` → `DialogMasukan` |
| `documents/_modal-revisi` | → `DialogRevisi` |
| `documents/_modal-nonaktif` | → `DialogNonaktif` |
| `documents/_modal-musnahkan` | → `DialogMusnahkan` |
| `documents/_masukan-list` | `components/dokumen/DaftarMasukan.tsx` |
| `documents/_tombol-rollback` | props `boleh_rollback` + `ConfirmDialog` di `Obsolete.tsx` |
| `partials/_rincian-pembaca` | `components/dokumen/RincianPembaca.tsx` |

**Keempat Blade modal itu TIDAK bisa dihapus di Fase 13**: `log/masukan.blade.php`
masih meng-`@include` `_modal-revisi` + `_masukan-list`, dan
`documents/rincianInformasi.blade.php` masih memakai `_rincian-pembaca`.
Keduanya baru pindah di Fase 11–12. Begitu pula `partials/_urut-th`,
`_badge-status`, dan `_badge-nomor-lama` — masih dipakai `review/*`,
`approvals/index`, `nonaktif/index`, `masukan-sejawat/show`, dan
`documents/distribution`.

### 8.5 Enam keputusan kecil yang perlu diketahui

1. **`<x-aksi>` jadi `components/dokumen/StripAksi.tsx`.** Seluruh kerumitan
   koordinat `position: fixed` di Blade lama HILANG: Radix memindahkan menunya
   ke portal di `<body>`, jadi `overflow-x` tabel tak lagi bisa memotongnya dan
   pembalikan arah saat ruang bawah kurang sudah jadi perilaku bawaannya. Yang
   DIPERTAHANKAN justru penjaga yang paling tak terlihat: menu tak dirender sama
   sekali bila isinya kosong (`Children.toArray(...).length`), diperiksa dari
   ISI-nya seperti dulu — bukan dengan mengulang syarat pemanggilnya.
2. **Jendela dirender DI LUAR `StripAksi`, itemnya cuma menyalakan saklar.**
   Radix melepas isi menunya begitu item dipilih, sehingga jendela yang lahir di
   dalamnya ikut lenyap sebelum sempat terlihat. Karena itu `DialogMasukan` /
   `DialogRevisi` / `DialogNonaktif` / `DialogMusnahkan` kini punya mode
   **dikendalikan** (`buka`/`onUbahBuka`) persis seperti `ConfirmDialog` sejak
   Fase 7.
3. **Empat jendela = SATU berkas** (`components/dokumen/DialogAksi.tsx`).
   Keempatnya bukan empat hal melainkan satu pola yang dipakai empat kali, dan
   pola itulah yang mudah menyimpang bila disalin: formulir diisi DULU,
   konfirmasi MENYUSUL saat dikirim (CLAUDE.md v4 §4), dengan tombol Kirim tetap
   `type="submit"` supaya `required`/`minLength` peramban tetap bekerja.
4. **Empat kartu penyaring jadi satu `PenyaringDokumen`.** Yang berbeda antar
   halaman hanya KOLOM MANA yang ditawarkan, bukan cara menyaringnya. Tetap
   query string (`router.get` + `preserveState`), jadi halaman hasil saringan
   masih bisa di-bookmark — keputusan Fase 6.4 butir 4.
5. **`DataTable` dapat satu prop baru: `perluas`.** Baris tambahan di bawah
   barisnya sendiri, untuk daftar versi di "Dokumen Tidak Berlaku".
   Buka-tutupnya dipegang HALAMAN, bukan komponen tabelnya: penyingkapnya duduk
   di kolom Aksi yang digambar halaman.
6. **Tombol "Kembali" di detail memakai `history.back()`**, bukan rute tetap.
   Layar itu dicapai dari enam daftar yang berbeda; ini sekaligus menutup bug
   CLAUDE.md §14 (Kembali yang berakhir 403).

### 8.6 Tes — 641 → **650** (naik sembilan, nol berkurang)

`php artisan test` → **649 lulus + 1 dilewati (4942 asersi)**.

Asersi di sebelas berkas berpindah GAYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `DaftarNonaktifTest` (4 tes) | `viewData('documents')`, `viewData('versi')`, `assertSee('3 versi')`, `assertDontSee('bi-chevron-right')` | props `documents.data[].versi` |
| `RevisionLogTest` (2 tes) | `assertViewHas('documents', …)` | props `documents.data[].id` |
| `RollbackVersiTest` | menghitung sel `<td>` di markup | satu elemen `documents.data` per grup |
| `WithdrawCancelRevisionTest` | mencari `name="nomor_baru"` di dalam markup form | props `nomor_bentrok` per baris |
| `StripAksiTest` (2 tes) | `assertSee('Aksi untuk baris ini')` | kelima `boleh_*` yang mengisi menunya |
| `DocumentFeedbackTest` (2 tes) | id modal Bootstrap `modalMasukan{id}`, `assertSee('2 masukan')` | `boleh_beri_masukan`, `masukan[]`, `masukan_count` |
| `DocumentDistributionTest` | `assertSee('Belum Membaca')` | `distribusi`/`distribusiRincian` yang `null` bagi yang tak berwenang |
| `MasukanSejawatTest` | URL `masukan-sejawat.show` di markup | props `masukan_sejawat` |
| `MenuTitikTest` (2 tes) | `class="pp-titik"` | `menuSidebar()[]['dot']` (penolong `menuSidebar` dapat kunci `dot`) |
| `NonaktifBerjenjangTest` | `href="…nonaktif.index"` + `assertSee('Ajukan Nonaktif')` | `menuSidebar()` + props `boleh_revisi` |
| `UrutTabelTest` | `dir=desc` di markup | `page.url` + props `filters` |

`SpecDashboardV3Test::test_tak_ada_badge_bergradasi_di_halaman_berdaftar`
sekarang MELEWATI halaman yang sudah Inertia, dengan sebab tertulis: markupnya
cuma satu atribut `data-page` berisi JSON, sehingga regexnya lolos trivial di
sana — persis kejadian dashboard di Fase 5. Ia tetap menjaga `review.index`
(masih Blade sampai Fase 10), dan penggantinya lebih kuat.

Yang **bertambah**:

- `tests/Feature/DokumenInertiaTest.php` (**8 tes / 192 asersi**) — komponen
  tiap rute, bentuk paginator utuh, kebocoran `password`/`remember_token`, rupa
  status yang datang dari `STATUS_META`, kelima `boleh_*` yang berbeda per
  peran, penyaringan masukan-belum-ditindak di server, **jenis unggahan FK**
  (pakem P6), dan dokumen di luar jangkauan yang tetap 403 (pakem P4).
- `UrutTabelTest::test_kolom_urut_di_halaman_inertia_memakai_kunci_yang_sah`
  (**1 tes**) — membaca kelima berkas TSX dan memastikan tiap `urut:` adalah
  kunci yang benar-benar ada di `Document::KOLOM_URUT`. Sebabnya: sejak tautan
  urut dirakit di peramban, `assertSee('sort=nomor')` tak bisa merah lagi,
  sementara `urut: 'nomer'` lolos TypeScript, lolos build, lolos gerbang render,
  dan hasilnya cuma daftar yang berhenti terurut tanpa satu pun galat.

### 8.7 Gerbang

- [x] `php artisan test` → **649 lulus + 1 dilewati (4942 asersi)** — patokan
      naik 641 → **650**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih (EXIT=0)
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **17 halaman**
      (bertambah `documents.{index,published,obsolete,revisions,staffStatus}`).
      `documents.show` tak ikut karena berparameter — ia dijaga
      `DokumenInertiaTest`.
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [x] Isi keenam halaman diperiksa dari HASIL RENDER-nya, bukan dari kodenya:
      payload nyata tiap rute diambil untuk **lima peran** (GL, SH, DH, PJO,
      Non-Staff) lalu dirender lewat bundel SSR — **23 halaman, nol galat**.
      Yang terbukti di sana: judul `documents.index` berganti menurut peran
      (GL "Seluruh dokumen yang Anda buat", sisanya tidak), judul
      `documents.staffStatus` berganti (GL "Dokumen Departemen", SH/DH/PJO
      "Status Dokumen Staff"), strip aksi TERGAMBAR bagi GL & Non-Staff tapi
      **KOSONG bagi SH/DH/PJO** (Fase C: mereka hanya membaca), Export Excel
      absen bagi Non-Staff, dan Non-Staff tetap **403** di
      `documents.obsolete`/`staffStatus`. Halaman detail yang terambil kebetulan
      dokumen **FK arsip** — dan ia benar menampilkan "Perbaiki" + lencana
      "Arsip", bukan "Buka Form" (pakem P6).
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091:
      - `/documents` sebagai GL — Kirim/Edit/Hapus pada draft sendiri, Tarik
        pada `waiting_for_review`, penyaring Sumber (SmartPro vs Dokumen Lama),
        urut No. Dokumen naik-turun, halaman 2
      - `/dokumen-berlaku` sebagai GL, SH, PJO, Admin, Non-Staff — Beri Masukan
        (Non-Staff), Revisi + Ajukan Nonaktif (GL pemilik), Batalkan Revisi,
        Musnahkan (Admin), Export Excel per jenis
      - `/dokumen-tidak-berlaku` sebagai Admin — tombol "N versi" membentang,
        Rollback, Aktifkan (termasuk kasus **nomor bentrok**), Arsipkan,
        Musnahkan pada baris versi
      - `/dokumen-revisi` sebagai GL yang dokumennya ditolak — rangkuman +
        anotasi peninjau terbaca
      - `/status-dokumen-staff` sebagai GL (judul "Dokumen Departemen") dan
        SH/PJO (submenu departemen)
      - satu `/documents/{id}` — Timeline, Distribusi, Masukan Lapangan,
        Balas &amp; Tutup, tombol Kembali
      - mode gelap di keenam halaman

### Utang yang dibawa Fase 8

- **Enam Blade lama belum dihapus**
  (`documents/{index,show,published,obsolete,revisions,staff-status}.blade.php`)
  — CLAUDE.md §4 butuh konfirmasi. Keenamnya sudah tak dirujuk siapa pun;
  dibuang bersama sisanya di Fase 13.1.
- **Baris versi di "Tidak Berlaku" kini memakai strip aksi**, sementara Blade
  lama menaruh Rollback & Musnahkan mendatar. Susunannya jadi sama dengan baris
  induk; nol aksi yang hilang.

---

## Fase 9 — Pembuatan & wizard dokumen ✅ SELESAI (2026-08-30)

Fase terberat. Tiga halaman TSX + **sebelas** komponen field, satu paket npm
baru (`quill@2.0.3` — versi yang persis sama dengan CDN yang digantikannya,
atas izin pemilik), nol komponen registry baru.

### 9.1 Halaman & controller

| Blade lama | TSX baru | Controller |
|---|---|---|
| `documents/create.blade.php` (11 KB) | `pages/Documents/Create.tsx` | `DocumentController@create` |
| `documents/unavailable.blade.php` | `pages/Documents/Unavailable.tsx` | `DocumentController@create` (cabang) |
| `documents/edit.blade.php` (858 baris) | `pages/Documents/Edit.tsx` | `DocumentController@edit` |

Sebelas komponen di `components/dokumen/fields/` menggantikan sembilan partial
`documents/fields/` + dua cabang besar di dalamnya:

| Komponen | Dari | Catatan |
|---|---|---|
| `Text.tsx` | `_text` | melayani `text`, `textarea`, `date` |
| `RichList.tsx` | `_rich_list` | melayani `rich_list` **dan** `reference_picker` |
| `RepeatableGroup.tsx` | `_repeatable_group` (11 KB) | bab berulang + sakelar bab opsional |
| `RichText.tsx` | `_rich_text` | Quill 2 "snow" — dipecah keluar dari cabang `_repeatable_group` |
| `ImageUpload.tsx` | cabang `image` di `_repeatable_group` | unggah langsung, jalur disimpan |
| `DocumentPicker.tsx` | cabang `document_picker` | combobox teks-bebas bab VII SOP |
| `JsaAnalysis.tsx` | `_jsa_analysis` | bersarang tiga lapis |
| `UserPicker.tsx` | `_user_picker` | bercabang tiga, cabangnya ditentukan server |
| `PapanKetersediaan.tsx` | `_papan-ketersediaan` (11 KB) | radio asli, nol JS |
| `RevisionLog.tsx` | `_revision_log` | langkah virtual di luar schema |
| `index.tsx` | larik `$partials` + `@include($partial)` | peta `section.type` → komponen |

**`fields/Signature.tsx` TIDAK dibuat, dan itu disengaja.**
`documents/fields/_signature.blade.php` adalah KODE MATI: tak ada satu pun
schema yang memakai tipe `signature`, dan `edit.blade.php` pun tak pernah
memetakannya di larik `$partials` — jadi ia tak pernah dirender sekali pun.
Peninjau & penyetuju dipilih lewat `user_picker`, bukan lewat tipe seksi
tersendiri. Daftar "sembilan renderer" di `MIGRASI-SHADCN.md` keliru
menghitungnya. Blade-nya tak dihapus (CLAUDE.md §4) — ia ikut dibuang di Fase
13.1.

### 9.2 Paket npm baru: `quill@2.0.3` (atas izin pemilik)

Versi yang **persis sama** dengan yang dimuat CDN di `edit.blade.php`. Itu
syaratnya: markup keluaran Quill-lah yang menentukan apa yang lolos
`PembersihHtml` (daftar putih `p/br/strong/em/u/ol/ul/li/img/blockquote`) dan
apa yang bisa ditata `ActivityPrintLayout`. Editor lain = markup lain = PDF
berbeda.

- Di-`import()` **dinamis** di dalam `useEffect`, sehingga (a) hanya halaman
  wizard yang membayarnya (bundel sendiri, 199 KB / 59 KB gz), dan (b) gerbang
  `smartpro:uji-render` yang merender di Node tak pernah ikut memuatnya —
  Quill menyentuh `document` saat dimuat.
- Toolbar-nya kini digambar React dengan ikon `lucide-react`, lalu DISERAHKAN
  ke Quill sebagai container (`modules.toolbar.container`). Quill mengikat
  tombolnya lewat kelas `ql-*` dan memasang `ql-active` sendiri. Ini sekaligus
  menghapus tambalan `Quill.import('ui/icons')` yang dulu menukar SVG bawaan
  satu per satu dengan Bootstrap Icons.
- `quill/dist/quill.snow.css` di-`@import` dari `resources/css/app.css`, dan
  seluruh penyesuaian tema `.pp-rt` ikut pindah ke sana — memakai token shadcn
  (`--border`, `--muted`, `--primary`), jadi mode gelap ikut sendiri tanpa satu
  pun aturan `@media`.

### 9.3 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

| Yang pindah | Ke mana | Kenapa |
|---|---|---|
| `in_array($key, ['peninjau','peninjau_penyetuju','penyetuju'])` + `$key !== 'penyetuju'` di `_user_picker` | `ReviewerAvailability::PAPAN` | keputusan yang SAMA ditegakkan `saringPeninjauTakTersedia()`; dua salinan pasti menyimpang |
| Serialisasi kandidat `user_picker` | `DocumentWizard::propsKandidat()` | model User apa adanya = `password` + `remember_token` + kunci mentah `jabatan` di `data-page` |
| Format tanggal `pita` & `kembali` | `DocumentWizard::propsKetersediaan()` | Carbon di props = tanggal diformat ulang oleh locale & timezone mesin pengguna |
| `$isRevLogStep` (dulu `@php` di Blade) | props `isRevLogStep` | syaratnya `Document::usesRevisionLog()`, aturan yang sama dengan saveStep/autosave |
| `$rujukan` (dulu `@php` di Blade) | props `rujukanPdfUrl` | butuh `revisesDocument->isArsip()` |
| `Pengaturan::prefix()` (dipanggil langsung di `create.blade`) | props `prefix` | pola nomor ditentukan Pengaturan, bukan diketik ulang di TSX |
| `$document->revisiSaatKirim()` | props `revisiKirim` | roll-over Edisi/Revisi = aturan bisnis |

`_user_picker.blade.php` DIUBAH dua baris supaya ikut membaca
`ReviewerAvailability::PAPAN`. Selama Blade & React hidup berdampingan
(Fase 9–13), dua salinan aturan yang sama pasti menyimpang.

### 9.4 Kebocoran yang ditutup — daftar kandidat peserta alur

Wizard menggendong `userPickerCandidates()`: koleksi model **User** untuk
peninjau, penyetuju, dan pembuat tambahan. Blade lama aman karena hanya
mencetak `name`, `nrp`, `department->code`, `jabatanLabel()`. Props Inertia
tidak punya kemewahan itu.

Yang dikirim sekarang **persis lima kunci**: `id`, `nama`, `nrp`, `dept`,
`jabatan` (LABEL, bukan kunci mentah). Dikunci
`WizardInertiaTest::test_kandidat_tak_membawa_kolom_rahasia`, yang memeriksa
daftar kuncinya satu per satu **dan** memindai seluruh payload untuk
`password`/`remember_token`. Ini kelas kesalahan yang sama dengan Fase 6
(`password`), Fase 7 (kunci API), dan Fase 8 (`creator`) — **tiap larik/model
yang pindah dari Blade ke props wajib diperiksa kolom per kolom.**

### 9.5 Kiriman langkah berubah bentuk: FormData berkurung → JSON bersarang

Alpine mengirim `new FormData(form)`, sehingga kolomnya bernama
`sections[aktivitas][0][sub_judul]`. React memegang nilainya sebagai state dan
mengirimkannya sebagai JSON bersarang lewat `router.post` / `fetch`.

Yang MENERIMA tak disentuh sama sekali: `$request->input('sections')` membaca
keduanya. Tapi karena bentuknya berubah, ia dibuktikan langsung —
`WizardInertiaTest::test_simpan_langkah_menerima_sections_bersarang_json` dan
`…_autosave_menerima_json_dan_menjawab_sidik` mem-`postJson` persis bentuk yang
dikirim peramban, lalu membaca `document_contents`-nya.

`name=""` tetap dipasang di tiap kolom walau tak lagi menentukan pengiriman:
`validasiWajib()` memindai DOM, dan atributnya juga yang membuat kiriman
terbaca saat ditelusuri lewat DevTools.

### 9.6 Enam keputusan kecil yang perlu diketahui

1. **`<FormLangkah key={dokumen:langkah}>`.** Inertia MEMAKAI ULANG komponen
   halaman ketika berpindah antar langkah (rutenya sama `documents.edit`), jadi
   tanpa `key` seluruh state langkah lama terbawa ke langkah berikutnya. Panel
   pratinjau sengaja DI LUAR pembungkus itu — kalau ikut ter-remount, iframe-nya
   dimuat ulang tiap pindah langkah dan panelnya "mati-nyala", persis cacat yang
   dulu diperbaiki dengan menaruh `src` di HTML.
2. **`ppValidateRequired()` + `warnKosong()` pindah ke `lib/validasi.ts`,
   tetap MEMINDAI DOM.** Aturannya memang tentang apa yang terlihat & bisa
   disentuh (`[inert]`, `offsetParent`, `disabled`) — menirunya di state React
   berarti salinan kedua yang pasti menyimpang. Yang berubah cuma cara
   mengabarkannya: `.is-invalid` + `Swal.fire` → `aria-invalid` (sudah bergaya
   sendiri di komponen registry) + `toast` Sonner. Satu penambahan: blok
   `data-pp-pilih`, karena `Select` Radix menukar `<select>` asli dengan tombol
   ber-`role` yang tak terjangkau pemindaian biasa.
   `el.closest('.border.rounded')` (kelas Bootstrap) jadi
   `el.closest('[data-pp-baris]')`.
3. **Peringatan dua langkah "Ada isian yang masih kosong" dirakit dari primitif
   `AlertDialog`, bukan `ConfirmDialog`.** Ia butuh DUA jalan keluar yang
   sama-sama berbuat sesuatu ("Isi dulu" mengantar ke kolomnya, "Tetap kirim"
   membuka konfirmasi kedua); `ConfirmDialog` hanya menyediakan satu.
4. **`DocumentPicker` sengaja BUKAN blok `Combobox` registry** (Command +
   Popover). Blok itu hanya bisa MEMILIH dari daftar tertutup dan memindahkan
   fokus ke dalam popover, sementara kolom ini harus tetap menerima ketikan
   bebas — yang tersimpan memang satu baris teks gabungan, bukan id. Alasannya
   ditulis di komentar berkasnya (CLAUDE.md §13).
5. **`PapanKetersediaan` memakai `<input type="radio">` sungguhan, bukan
   `RadioGroup` Radix.** Radix menukarnya dengan tombol ber-`role`, dan
   `validasiWajib()` memindai `input[type=radio][required]` yang sungguhan.
   Seluruh sel pita tetap ELASTIS (`flex-1`), tak satu pun berlebar tetap
   (CLAUDE.md §6).
6. **`RepeatableGroup` selalu menyisakan satu baris saat `min_groups > 0`.**
   Komponen Alpine `repeatable` menyediakannya hanya saat halaman DIMUAT,
   sehingga menghapus baris terakhir menyisakan bab tanpa kolom; ketiga
   saudaranya (`richList`, `userPicker`, `jsaAnalysis`) justru mengembalikan
   baris kosong pada `remove()`. Yang ditiru yang tiga. Nol akibat pada data —
   baris kosong tetap dibuang `cleanValue()`.

### 9.7 Tes — 650 → **660** (naik sepuluh, nol berkurang)

`php artisan test` → **659 lulus + 1 dilewati (5118 asersi)**.

Asersi di **sebelas** berkas berpindah GAYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `ArsipCatatanRevisiTest` | `assertSee('Referensi')` + URL PDF di markup | props `rujukanPdfUrl` |
| `CoAuthorCandidateTest` | `assertSee('sections[pembuat_tambahan]')` | seksi schema + props `candidates` |
| `JenisUnggahanTest` | `assertSee('arsip: true')`, `name="berkas"` | komponen + props `unggahanSaja` |
| `MasterDataTest` | `assertSee('Belum Tersedia')` | komponen `Documents/Unavailable` |
| `PdfCacheTest` | `id="previewFrame"`, `v=<sidik>` | props `previewV` |
| `PenomoranBabOpsionalTest` (2) | `V. FLOWCHART`, `pp-bab-mati`, `inert` di markup | schema + `labelMati` + `babAktif` + TSX |
| `PeringatanIsianKosongTest` | `data-pp-warn-bab=`, `data-optional data-pp-warn=` | pasangan `required:false` × `warn_if_empty` di schema |
| `ReviewerAvailabilityTest` (2) | `Penuh — 2 dokumen`, `data-tingkat=`, hitung `pp-papan` | props `ketersediaan` + `papan` |
| `RevisionLogTest` (2) | `value="submit"`, `Catatan Perubahan` | `currentStep`/`totalSteps`/`isRevLogStep` |
| `RichTextAktivitasTest` | `quill.js`, `richText(`, `bi-type-bold` | schema + pindaian `RichText.tsx` |
| `SopSectionOrderTest` | label bab + `docPicker(` di markup | schema + `dokumenBerlaku` + pindaian TSX |

Penolong baru di `Tests\TestCase`: **`seksiWizard()`** — seksi schema yang
sedang digambar wizard, dikunci `key`-nya. Sepuluh berkas di atas tak perlu
menulis pembaca schema sendiri-sendiri.

Yang **bertambah**: `tests/Feature/WizardInertiaTest.php` (**10 tes / 135
asersi**) —

- komponen + kelengkapan props ketiga rute;
- **pakem P1 diuji sungguhan**: satu seksi disisipkan ke `schema_json` lewat
  basis data, lalu dibuktikan muncul sendiri di props (dikembalikan
  `DatabaseTransactions`);
- **tiap tipe seksi & tipe kolom yang dipakai schema mana pun punya
  komponennya.** Tipe yang tak terpetakan tidak meledak — ia jatuh ke kolom
  teks, persis seperti Blade jatuh ke `_text`. Itu benar untuk bertahan hidup,
  salah untuk dibiarkan tanpa disadari: bab `jsa_analysis` yang tergambar
  sebagai satu kolom teks lolos build, lolos tipe, lolos gerbang render;
- kebocoran kandidat (§9.4);
- bentuk kiriman JSON bersarang (§9.5) untuk `saveStep` maupun `autosave`;
- `ReviewerAvailability::PAPAN` + tanggal pita yang sudah jadi TEKS;
- **jenis unggahan FK** (pakem P6): formulirnya unggah, dan wizard-nya
  dialihkan ke halaman detail;
- **403** untuk wizard & autosave dokumen di luar jangkauan (pakem P4);
- perakitan `src` iframe dari `previewV`.

### 9.8 Gerbang

- [x] `php artisan test` → **659 lulus + 1 dilewati (5118 asersi)** — patokan
      naik 650 → **660**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih (EXIT=0)
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **18 halaman**
      (bertambah `documents.create`)
- [x] **Sapuan tambahan untuk rute BERPARAMETER.** `smartpro:uji-render`
      sengaja melewati `documents/{document}/edit`, jadi payload nyata diambil
      terpisah untuk **4 jenis × 3 langkah + pembuatnya** dan **6 jenis halaman
      Create**, ditambah sembilan varian payload yang menutup cabang yang tak
      terwakili data di mesin ini (langkah 1, bab opsional MATI, anotasi +
      rangkuman peninjau, tab Referensi, mode baca, papan buntu tanpa kandidat,
      langkah Log Revisi terisi & kosong, dokumen kosong total). **31 halaman,
      nol galat.**
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091. Daftar Uji 9d di `MIGRASI-SHADCN.md`
      belum bisa dijalankan tanpa peramban:
      - buat dokumen **tiap jenis**: SOP, IK, SP, JSA (wizard) + FK, PX
        (unggah), dan jalur **arsip/dokumen lama** pada jenis berwizard
      - isi **seluruh** tipe seksi minimal sekali: `rich_list`,
        `reference_picker`, `repeatable_group` (dengan gambar **dan** dengan
        Quill), `jsa_analysis` bersarang penuh, `text`, `date`, `user_picker`
      - Quill: tebal/miring/garis-bawah, kutipan, daftar bernomor & berbutir,
        tombol gambar, **tempel (Ctrl+V) tangkapan layar** → harus berubah jadi
        berkas di `lampiran/{DEPT}/{JENIS}/`, bukan base64
      - autosave: ketik, tunggu ~1,5 detik, tutup tab, buka lagi → isian masih
        ada; keterangan "Tersimpan otomatis" berjam WITA
      - Preview: iframe menyegar SESUDAH menyimpan langkah, dan **tidak**
        berkedip saat pindah langkah bila isinya tak berubah
      - sakelar "Gunakan Flowchart": mati → bab redup & tak bisa disentuh, tapi
        ketikan TIDAK hilang saat disimpan; nomor bab AKTIVITAS turun jadi V
      - papan ketersediaan: baris terkunci saat off, klik di mana saja pada
        baris memilihnya, panah keyboard berpindah
      - **Kirim** dengan bab opsional kosong → peringatan dua langkah muncul
      - **Bandingkan PDF hasil dengan baseline** — harus identik
      - **Uji P1 manual:** tambah satu seksi ke `schema_json` lewat SQL → muncul
        sendiri di form → kembalikan
      - **Uji pembuat tambahan:** pilih GL sedepartemen → simpan → ajukan cuti
        untuk GL itu → simpan lagi → **namanya harus tetap ada**
      - **Uji SP/IK:** pilih `peninjau_penyetuju` → periksa `reviewer_id` dan
        `approver_id` di DB terisi sama
      - mode gelap di ketiga halaman

### Utang yang dibawa Fase 9

- **Tiga Blade lama belum dihapus** (`documents/{create,edit,unavailable}.blade.php`)
  beserta seluruh `documents/fields/**` — CLAUDE.md §4 butuh konfirmasi.
  `_papan-ketersediaan` MASIH DIPAKAI `review/_modal-alihkan` (pengalihan
  peninjauan JSA), jadi ia baru boleh dibuang sesudah Fase 10. Sisanya sudah
  tak dirujuk siapa pun dan dibuang bersama yang lain di Fase 13.1.
- **`documents/fields/_signature.blade.php` adalah kode mati** dan sudah begitu
  sejak sebelum migrasi (lihat §9.1). Ikut dibuang di Fase 13.1.
- **Bundel `Dashboard` 515 KB** melewati ambang peringatan Vite. Bukan bawaan
  fase ini (recharts), tapi ia kini bertetangga dengan bundel `quill` 200 KB;
  kalau kelak perlu ditekan, jalurnya `manualChunks`, bukan mengurangi fitur.

---

## Fase 10 — Peninjauan & persetujuan ✅ SELESAI (2026-08-30)

Delapan view Blade → **tujuh halaman TSX** + tiga komponen bersama. Nol
komponen registry baru, nol paket npm baru. Satu service baru
(`App\Services\ReviewScreen`) dan satu pemecahan komponen
(`PapanPilihPeninjau`), keduanya karena hal yang sama: layar tinjau dipakai
ulang di lebih dari satu tempat, dan salinan kedua adalah salinan yang suatu
hari menyimpang.

### 10.1 Halaman & controller

| Blade lama | TSX baru | Controller |
|---|---|---|
| `review/index.blade.php` | `pages/Review/Index.tsx` | `ReviewController@index` |
| `review/md.blade.php` | `pages/Review/Md.tsx` | `MdReviewController@index` |
| `review/show.blade.php` (592 baris) | `pages/Review/Show.tsx` | `ReviewController@show` · `MdReviewController@show` · `MasukanSejawatController@create` |
| `review/_modal-alihkan.blade.php` | `components/tinjau/DialogAlihkan.tsx` | `ReviewController@alihkan` |
| `approvals/index.blade.php` | `pages/Approvals/Index.tsx` | `ApprovalController@index` |
| `approvals/show.blade.php` | `pages/Approvals/Show.tsx` | `ApprovalController@show` |
| `masukan-sejawat/show.blade.php` | `pages/MasukanSejawat/Show.tsx` | `MasukanSejawatController@show` |
| `nonaktif/index.blade.php` | `pages/Nonaktif/Index.tsx` | `NonaktifController@index` |

Tiga komponen bersama di `components/tinjau/`:

| Komponen | Dari | Catatan |
|---|---|---|
| `SeksiTinjau.tsx` | perulangan `@foreach ($schema->allSections())` | bab biasa **dan** analisa JSA bersarang; tanda ✓/✗; penanda borongan |
| `PanelAi.tsx` | komponen Alpine `aiReview` | fetch + adopsi/tolak temuan |
| `DialogAlihkan.tsx` | `review/_modal-alihkan` | papan ketersediaan + alasan wajib |
| `konteks.tsx` | — | keadaan formulir tinjauan, pola yang sama dengan `fields/konteks.tsx` |

**SATU halaman melayani TIGA pintu masuk**, persis seperti Blade-nya:
`review.show` (SH/DH), `review.md.show` (Management Development), dan
`masukan-sejawat.create` (antar-GL, tanpa keputusan). Yang membedakan hanya
props — `formAction`, `aiUrl`, `petunjuk`, `labelLolos`/`labelTolak`,
`tanpaKeputusan`, `alihKandidat`. Dikunci
`TinjauInertiaTest::test_tiga_pintu_masuk_memakai_komponen_yang_sama`.

### 10.2 Service baru: `App\Services\ReviewScreen`

Blade lama mendedup props layar tinjau dengan cara yang paling murah: satu
view, tiga pemanggil, sisanya `?? default`. Di Inertia "beberapa variabel"
berubah jadi larik props — dan larik yang disalin di tiga controller adalah
larik yang suatu hari bertiga tak sepakat. `bersama()` mengembalikan bagian yang
sama (`document`, `schema`, `tipeDitinjau`, `contentMap`, `anotasiLama`,
`lampiran`); tiap controller cuma menimpa kunci khasnya.

Tiga hal yang ikut pindah ke sana, dan ketiganya bukan kerapian melainkan
kebutuhan:

1. **`TIPE_DITINJAU`** — dulu satu `@php` di kepala Blade. Kini konstanta yang
   dikirim sebagai props, jadi menambah tipe seksi yang bisa ditinjau cukup
   menyentuh satu baris di server (pakem P1/P3).
2. **Pembersihan `rich_text`** — lihat §10.3.
3. **Perataan `anotasiLama` & `lampiran`** — lihat §10.4.

### 10.3 `PembersihHtml` pindah dari "saat mencetak" ke "saat menyusun props"

Blade memanggil `PembersihHtml::bersihkan()` tepat pada detik mencetak. Alasan
sanitasi keduanya tak berubah sedikit pun — inilah SATU-SATUNYA layar yang
menyajikan isi `document_contents` sebagai HTML kepada orang yang wewenangnya
LEBIH TINGGI daripada penulisnya, sehingga baris yang telanjur tersimpan sebelum
sanitasi ada, dan jalur tulis apa pun yang kelak lupa membersihkan, berhenti di
situ.

Yang berubah cuma TEMPATNYA. Membersihkannya di peramban bukan pilihan: HTML
yang sampai ke atribut `data-page` **sudah terlanjur ada di halaman**. Jadi
`ReviewScreen::bersihkanRichText()` menyapu `contentMap` lebih dulu, dan kolom
mana yang `rich_text` dibaca dari SCHEMA — bukan dari daftar nama kolom yang
ditulis tangan (pakem P1). Dikunci
`TinjauInertiaTest::test_rich_text_dibersihkan_sebelum_masuk_props`.

### 10.4 Kebocoran yang ditutup — kelas kesalahan KELIMA berturut-turut

Layar ini menggendong **tiga** koleksi model sekaligus, dan ketiganya berujung
ke `User` beserta `password`, `remember_token`, dan kunci mentah `jabatan`:

| Apa | Dari | Sekarang |
|---|---|---|
| Kandidat pengalihan JSA | `DocumentParticipantResolver::reviewerCandidates()` | `DocumentWizard::propsKandidat()` — persis lima kunci, `jabatan` sebagai LABEL |
| Komentar foto lampiran | `attachments.comments.user` | `ReviewScreen::lampiran()` — id/url/nama + oleh/isi/waktu |
| Pemberi masukan sejawat | `masukanSejawat.user` | `MasukanSejawatController` — `oleh` sudah `nameWithJabatan()` |

Ditambah dua model yang diratakan karena membawa kolom yang tak satu pun
dicetak: `AuditLog` pengalihan (`meta_json` utuh → tiga kunci) dan
`ReviewAnnotation` (`severity`, `ai_generated`, `verdict` → daftar komentar).

Sesudah `password` (Fase 6), kunci API (Fase 7), `creator` (Fase 8), dan
kandidat wizard (Fase 9): **tiap larik/model yang pindah dari Blade ke props
wajib diperiksa kolom per kolom.**

### 10.5 Satu cacat lama yang ikut tertutup

`anotasiLama` sekarang MEMBUANG anotasi tanpa komentar. Baris semacam itu
memang ada — sejak tanda ✓/✗ JSA, `ReviewDecision::simpanAnotasi()` menulis
baris yang hanya memikul `verdict`. Blade lama mencetaknya juga, hasilnya baris
"Catatan sebelumnya:" yang kosong di belakangnya, **satu per pengendalian**.
Sebuah JSA dengan 20 pengendalian karena itu memajang 20 baris hampa di putaran
tinjauan kedua. Dikunci
`TinjauInertiaTest::test_anotasi_lama_diratakan_dan_yang_kosong_dibuang`.

### 10.6 Logika yang PINDAH KE SERVER, bukan ke TSX (pakem P5)

| Yang pindah | Dari | Ke |
|---|---|---|
| `TIPE_DITINJAU` | `@php` di kepala `review/show` | `ReviewScreen::TIPE_DITINJAU` → props |
| Label tahap nonaktif | larik `$tahapLabel` di dalam Blade | `NonaktifController::LABEL_TAHAP` → props `tahapLabel` |
| "ini tahap terakhir?" | `$doc->tahapNonaktifBerikut() === null` di Blade | props `tahapTerakhir` |
| Nama bagian & penunjuk item masukan sejawat | dua closure di kepala Blade | `MasukanSejawatController::labelItem()` + `schema->findSection()` |
| Alasan pengajuan nonaktif | `$doc->approvals->first()?->comment` di Blade | props `alasan` |
| Kandidat + ketersediaan pengalihan | `@include` partial + 6 variabel | `propsKandidat()` / `propsKetersediaan()` — jalur yang SAMA dengan wizard |

### 10.7 Lima keputusan kecil yang perlu diketahui

1. **`PapanKetersediaan` dipecah dua, bukan disalin.** Jendela Alihkan memilih
   peninjau dengan pertanyaan yang persis sama ("siapa yang paling lapang"),
   tapi ia hidup di luar `PenyediaWizard`. Isinya karena itu keluar jadi
   `PapanPilihPeninjau` yang menerima daftarnya lewat props; `PapanKetersediaan`
   tinggal pembungkus tiga baris yang membacanya dari konteks wizard. Blade lama
   menyelesaikannya dengan `@include` + enam variabel — ini padanannya.
2. **Tanda ✓/✗ tetap `<input type="radio">` sungguhan**, bukan `RadioGroup`
   Radix — alasan yang sama dengan papan ketersediaan: panah keyboard berpindah
   sendiri dan pembaca layar membacanya sebagai satu grup, tanpa satu baris JS.
   Warnanya dibedakan di TIGA sumbu (rona, terang, **cincin**), sebab kebutaan
   warna merah-hijau adalah yang paling umum dan ini formulir keselamatan —
   aturan yang diwarisi utuh dari CSS `.pp-verdict` lama.
3. **Tombol borongan tak lagi menyapu DOM.** Alpine mencarinya lewat
   `input[data-bahaya="…"]`; React memberi `Borongan` daftar ref pengendaliannya
   langsung. Atribut `data-bahaya`/`data-borongan` karena itu ikut hilang — dan
   dua asersi `JsaDocumentTest` yang membacanya berpindah gaya (§10.8).
4. **`data-annot` DIPERTAHANKAN.** Panel AI mengantar layar ke kotak tujuan
   sesudah sebuah temuan diadopsi (`scrollIntoView`), dan pada analisa JSA
   panjang kotak itu hampir selalu di luar layar. Pencarian kotaknya juga tetap
   lewat DOM, sebab jalan mundurnya ("ambil kotak pertama section ini") memang
   pertanyaan tentang apa yang SEDANG tergambar.
5. **Panel AI memakai `fetch`, bukan `router.post`.** Yang kembali JSON temuan,
   bukan halaman — Inertia akan memperlakukan balasan non-Inertia sebagai galat
   navigasi. Token CSRF-nya dibaca dari `<meta name="csrf-token">` di root
   template. Satu tambahan yang Alpine tak punya: `useEffect` cleanup mematikan
   penghitung detik bila halaman ditinggalkan selagi AI masih berpikir.

### 10.8 Tes — 660 → **673** (naik tiga belas, nol berkurang)

`php artisan test` → **672 lulus + 1 dilewati**.

Asersi di **lima** berkas berpindah GAYA, tak satu pun dihapus (pakem P7):

| Berkas | Yang dulu dibaca | Sekarang |
|---|---|---|
| `ApproverRevisionTest` | `assertDontSee('annotations[')` | `missing('schema')` + `missing('contentMap')` — halaman PJO memang tak menerimanya |
| `JsaDocumentTest` (4) | `name="verdicts[…]"`, `data-borongan=`, `data-bahaya=`, `Edisi 2 · Revisi 3` | `pakaiVerdict` + bentuk `contentMap` + pindaian `SeksiTinjau.tsx` + `document.{edisi,noRevisi,putaran}` |
| `MasukanSejawatTest` | `assertDontSee('AI Review Assist')`, `assertSee('Kirim')` | `missing('aiUrl')` + `tanpaKeputusan` + `anotasiLama` kosong |
| `MdReviewStageTest` (4) | `name="annotations["`, `AI Review Assist`, `href="/approvals/…"` | komponen + `labelLolos`/`labelTolak` + `aiUrl` + id di paginator |
| `PengalihanPeninjauanTest` (2) | `assertSee('Alihkan')`, `assertSee('Dialihkan oleh …')` | `alihKandidat.0` + `pengalihan.{dari,alasan}` |

`UrutTabelTest::test_kolom_urut_di_halaman_inertia_…` bertambah **empat**
halaman (`Review/Index`, `Review/Md`, `Approvals/Index`, `Nonaktif/Index`) —
sesuai catatan Fase 8: fase yang memasang kolom urut baru wajib menambah
halamannya ke daftar itu.

Yang **bertambah**: `tests/Feature/TinjauInertiaTest.php` (**13 tes**) —

- komponen + bentuk paginator utuh ketiga antrean;
- **satu komponen melayani tiga pintu masuk** (§10.1);
- **kebocoran** kandidat pengalihan & pemberi masukan sejawat (§10.4), diperiksa
  di HTML-nya, bukan cuma di props;
- **`rich_text` dibersihkan di server** (§10.3);
- **pakem P1 diuji sungguhan**: satu seksi disisipkan ke `schema_json` lewat
  basis data, lalu dibuktikan muncul sendiri di props;
- `tipeDitinjau` sama persis dengan `ReviewScreen::TIPE_DITINJAU`;
- `anotasiLama` diratakan & yang kosong dibuang (§10.5);
- papan pengalihan sudah jadi dari server — tanggal pita berupa TEKS, `ambang`
  lengkap, dan `alihKandidat` `null` (bukan larik kosong) pada jenis yang tak
  mengenal pengalihan;
- **jenis unggahan FK** (pakem P6): nol bab, layar tinjaunya tetap berdiri;
- **403** di empat pintu di luar jangkauan (pakem P4);
- antrean nonaktif membawa `tahap`/`tahapLabel`/`alasan`/`tahapTerakhir` dari
  server;
- **bentuk kiriman berubah, penerimanya tidak.** Formulir Blade mengirim
  `annotations[analisa][L0-B0-P0]` sebagai FormData berkurung dan penanda AI-nya
  sebagai STRING `'1'`; React mengirim JSON bersarang dengan penanda BOOLEAN.
  `ReviewDecision::simpanAnotasi()` membacanya dengan `== '1'` — yang pada PHP 8
  memang benar untuk `true`, tapi itu kesimpulan dari MEMBACA kode. Dibuktikan
  langsung dengan `postJson`, pelajaran yang sama dengan `saveStep`/`autosave`
  di Fase 9.

### 10.9 Gerbang

- [x] `php artisan test` → **672 lulus + 1 dilewati** — patokan naik 660 → **673**
- [x] `npm run build` bersih · `node_modules/.bin/tsc --noEmit` bersih (EXIT=0)
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **22 halaman**
      (bertambah `review.index`, `review.md`, `approvals.index`, `nonaktif.index`)
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] **Sapuan tambahan untuk rute BERPARAMETER.** `smartpro:uji-render`
      sengaja melewati rute ber-`{document}`, jadi payload nyatanya diambil
      terpisah lalu dirender lewat bundel SSR yang sama: SOP terisi / kosong /
      beranotasi-putaran-lalu, tahap MD dengan AI **hidup dan mati**, persetujuan
      PJO, JSA beranalisa penuh + `?alih=1` + JSA **tanpa** analisa (dua tombol
      biasa, bukan tanda), FK unggahan (pakem P6), masukan sejawat
      pemberi/pembaca/kosong, antrean nonaktif **tahap terakhir**, dan antrean
      tinjau sebagai Admin. **15 payload, nol galat.**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`.**

      > **Satu jebakan pada perintah ini, catat untuk fase berikutnya.** Tanpa
      > `--id`, ia merender SELURUH dokumen non-arsip — termasuk yang LAHIR
      > SESUDAH baseline diambil. Dokumen semacam itu dilaporkan `HILANG (belum
      > ada baseline)` dan **ikut dihitung sebagai `$beda`**, sehingga gerbangnya
      > merah walau tak satu byte pun keluaran cetak berubah. Yang memicunya di
      > sesi ini: `id1228` — draft berjudul "m" buatan `ADM-0001`
      > (2026-08-30 03:12), bukan hasil migrasi. Gerbang yang sah karena itu
      > dijalankan berbatas kedelapan id yang memang punya baseline:
      >
      > ```powershell
      > php artisan smartpro:cetak-baseline --id=1177 --id=1178 --id=1179 `
      >     --id=1181 --id=1223 --id=1224 --id=1225 --id=1227
      > ```
      >
      > **Butuh keputusanmu:** `id1228` dihapus (data uji), atau baselinenya
      > ditambah (`--tulis --id=1228`)? Sampai salah satunya diputuskan,
      > perintah tanpa `--id` akan selalu merah — dan gerbang yang selalu merah
      > adalah gerbang yang berhenti dibaca orang.
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091:
      - **Tinjau Dokumen**: dua tabel, urutkan kolom Nomor & Edisi/Revisi,
        tombol Alihkan hanya pada JSA yang benar-benar di tangan Anda,
        Batalkan Revisi berkonfirmasi
      - **Layar tinjau SOP**: kotak catatan tiap item, bab `rich_text`
        (Aktivitas) tampil sebagai teks berformat — bukan `<p><strong>…`
      - **Layar tinjau JSA**: tanda Sesuai/Perlu Revisi tiap pengendalian,
        batang merah di tepi baris bertanda ✗, penanda borongan hanya pada
        bahaya berpengendalian banyak, tombol Kirim mati sampai semua ditandai,
        dan bunyinya berubah mengikuti jumlah ✗
      - **Alihkan**: dari antrian (`?alih=1` → jendela terbuka sendiri) maupun
        dari tombol di halaman tinjau; peninjau yang off terkunci; alasan wajib
      - **Panel AI**: tekan Analisis, adopsi satu temuan → layar mengantar ke
        kotaknya + lencana "Diadopsi dari saran AI"; tolak satu temuan
      - **Tahap MD**: panel AI hilang saat Admin mematikannya, label tombol
        berbunyi "Loloskan ke PJO"/"Kembalikan untuk Perbaikan"
      - **Masukan sejawat**: layar pemberi tanpa AI & tanpa keputusan; layar
        pembaca menampilkan nama bab ("I. TUJUAN"), bukan `tujuan`
      - **Persetujuan PJO**: Setujui → Berlaku (cek stempel APPROVED di PDF),
        Kembalikan tanpa alasan → pesan galat muncul
      - **Persetujuan Nonaktif**: Setujui (bunyi konfirmasi berbeda di tahap
        terakhir), Tolak lewat menu titik-tiga → alasan wajib
      - **Foto lampiran**: tampil, komentar terkirim & langsung terlihat
      - alur penuh satu dokumen: `draft → waiting_for_review → in_review →
        verifikasi_md → pending_approval → published`, lalu jalur gagal
        `rejected`, lalu `sedang_direvisi → menunggu_nonaktif → obsolete`
      - **badge tiap status** berwarna & berikon benar — **terutama
        `verifikasi_md`, jangan sampai abu-abu**
      - mode gelap di ketujuh halaman

### Utang yang dibawa Fase 10

- **Delapan Blade lama belum dihapus** (`review/{index,md,show,_modal-alihkan}`,
  `approvals/{index,show}`, `masukan-sejawat/show`, `nonaktif/index`) —
  CLAUDE.md §4 butuh konfirmasi. Sudah `grep`-bersih: tak ada satu pun yang
  merujuknya selain sesamanya.
- **`documents/fields/_papan-ketersediaan` kini benar-benar mati.** Ia satu-satunya
  utang Fase 9 yang menunggu fase ini (`review/_modal-alihkan` pemakai
  terakhirnya). Seluruh `documents/fields/**` sekarang tak dirujuk siapa pun.
- **`dashboard.blade.php` masih merujuk `review.index`/`approvals.index`** —
  Blade itu sendiri sudah mati sejak Fase 5; disebut di sini hanya supaya
  `grep` di Fase 13.1 tak salah simpul.

---

## Fase 11 — Revisi, arsip, distribusi ✅ SELESAI (2026-08-30)

Sepuluh berkas didaftarkan; **enam sudah selesai sebelum fase ini dimulai**
(`_modal-musnahkan-semua` di Fase 7, kelima sisanya terseret Fase 8). Yang
benar-benar tersisa **empat layar** → **empat halaman TSX**. **Nol komponen
baru, nol komponen registry baru, nol paket npm baru** — fase pertama yang
seluruh bahannya sudah ada.

### 11.1 Halaman & controller

| Blade lama | TSX baru | Controller |
|---|---|---|
| `documents/distribution.blade.php` | `pages/Documents/Distribution.tsx` | `DocumentDistributionController@distribution` |
| `documents/_distribusi-informasi.blade.php` | ↑ **komponen di berkas yang sama** | `…@distribution` cabang `?sumber=informasi` |
| `documents/rincianInformasi.blade.php` | `pages/Documents/RincianInformasi.tsx` | `…@rincianInformasi` |
| `documents/arsip/edit.blade.php` | `pages/Documents/Arsip/Edit.tsx` | `DocumentArsipController@edit` |
| `documents/arsip/catatan.blade.php` | `pages/Documents/Arsip/Catatan.tsx` | `DocumentArsipController@catatan` |

Komponen yang DIPAKAI ULANG apa adanya — inilah yang membuat fase ini murah:

| Komponen | Lahir di | Dipakai di sini untuk |
|---|---|---|
| `dasbor/PitaCakupan` | Fase 5 | pita cakupan tabel, penyingkap per-departemen, kepala kartu rincian |
| `dokumen/RincianPembaca` | Fase 8 | siapa sudah / belum membuka informasi |
| `dokumen/NomorDokumen` | Fase 8 | lencana Arsip / Nomor Lama |
| `DataTable` + `Paginasi` | Fase 8 | kedua tabel, termasuk baris `perluas` per-departemen |
| `dokumen/PenyaringDokumen` | Fase 8 | ketiga penyaring (cari, jenis, departemen, kategori) |
| `dokumen/fields/RevisionLog` | Fase 9 | lembar CATATAN REVISI dokumen lama |
| `ConfirmDialog` | Fase 7 | konfirmasi Simpan Perubahan & Simpan-Lanjutkan |

Satu prop baru di `PenyaringDokumen`: `contoh`, menggantikan pola nomor dokumen
mutu di placeholder pencarian. Dipakai cabang Informasi, yang barisnya bukan
dokumen dan karena itu memang tak punya prefix penomoran.

### 11.2 KEBOCORAN yang ditutup — dan kali ini BUKAN model User

Enam fase berturut-turut yang bocor selalu berujung ke `password`. Di sini yang
paling berbahaya justru **`arsip_path`**: jalur berkas privat di disk `local`,
yang nama berkasnya sengaja diacak Laravel supaya tak bisa ditebak dari nomor
dokumen (komentar `DocumentArsipController::simpanBerkas`). `$document->load(…)`
apa adanya akan menuliskannya ke atribut `data-page` — membatalkan lapis kedua
itu diam-diam, tanpa satu pun gerbang berbunyi.

Tiga lagi yang ditutup di fase yang sama, semuanya model User:

| Sumber | Yang menggendongnya | Penutupnya |
|---|---|---|
| daftar dokumen mutu | relasi `creator` tiap `Document` | `Document::barisDaftar()` (Fase 8) |
| daftar informasi | relasi `uploader` tiap `Informasi` | `barisInformasi()` (baru, private) |
| rincian pembaca | `InformasiRead.user` **dan** `belum` apa adanya | `DocumentController::ratakanRincian()` |

`ratakanRincian()` naik dari `private` jadi `public static`, bukan disalin:
`InformasiDistribution::rincian()` sengaja berbentuk identik dengan kembarannya
di `DocumentDistribution` (tercatat di komentar kelasnya), jadi dua pemerata
berarti dua tempat yang harus sama-sama diingat saat kolom berikutnya bertambah
— termasuk saat kolom itu `password`.

Dikunci `DistribusiInertiaTest::test_props_tak_membocorkan_kata_sandi_maupun_jalur_berkas_arsip`,
yang memeriksa **HTML MENTAH** kelima layar, bukan larik props: yang berbahaya
justru kolom yang tak dibaca TSX mana pun — ia tetap terbaca lewat "view
source", dan justru karena tak dibaca, tak ada satu pun tes rupa yang bisa
menangkapnya.

### 11.3 Empat keputusan kecil yang perlu diketahui

1. **`?sumber=informasi` tetap SATU komponen.** Persis seperti Blade-nya, dan
   sama alasannya: penjaga akses, saklar sumber, kartu peringatan, dan pita
   cakupannya identik; yang berbeda cuma kolom tabelnya. `DistribusiProps` di
   TypeScript adalah **union bertanda `sumber`**, jadi membaca `props.documents`
   di cabang informasi bukan bug yang menunggu — ia galat kompilasi.
2. **`cakupan` menempel di BARISNYA, bukan jadi peta kedua.** Blade memakai
   `$cakupan[$d->id]` karena view memang menerima dua variabel; di props itu
   berarti dua larik yang harus dicocokkan ulang di klien. Perhitungannya tetap
   satu panggilan untuk seluruh halaman — `cakupan()` memang dirancang menerima
   kumpulan supaya tak lahir N+1.
3. **`tanggal_efektif` sudah jadi `Y-m-d` di server.** `<input type="date">`
   hanya menerima bentuk itu, sedangkan `published_at` bertipe Carbon yang di
   JSON jadi cap waktu ISO lengkap dengan jamnya — isian tanggalnya akan diam
   diam kosong. Diperiksa dengan regex, bukan dengan nilai tertentu.
4. **`revisiSaatKirim()` naik jadi props.** Di Blade ia dihitung di dalam
   `documents/fields/_revision_log`; komponen React-nya menerimanya sebagai
   prop `revisiKirim` (kontraknya sudah begitu sejak Fase 9). Perhitungannya
   membaca versi lama di basis data — pakem P5, jangan ditiru di klien.

### 11.4 Tes — 673 → **684** (naik sebelas, nol berkurang)

Baru: `tests/Feature/DistribusiInertiaTest.php` (11 tes / 120 asersi).

| Tes | Yang dijaga |
|---|---|
| `kelima_layar_memakai_komponen_inertia` | tiap rute → komponennya |
| `props_tak_membocorkan_kata_sandi_maupun_jalur_berkas_arsip` | §11.2, pada HTML mentah |
| `rincian_informasi_hanya_membawa_empat_kolom` | bentuk hasil `ratakanRincian()` |
| `tiap_baris_membawa_cakupannya_sendiri` | keempat angka + kolom lencana |
| `pengurutan_dikerjakan_server` | bawaan cakupan menaik; `?sort=nomor` membatalkannya |
| `penyaring_jenis_menyaring_di_server` | pakem P5 |
| `halaman_catatan_mengirim_revisi_saat_kirim` | `revisiKirim`, `jumlahHalaman`, `Y-m-d` |
| `lembar_catatan_diterima_dalam_bentuk_json_bersarang` | pelajaran Fase 9.5, lewat `postJson` |
| `jenis_unggahan_fk` | pakem P6 — FK punya Perbaiki, tak punya lembar revisi |
| `tetap_403_di_luar_jangkauan` | pakem P4 — GL & Non-Staff |
| `perbaikan_arsip_tertutup_bagi_orang_lain` | `bisaDisuntingArsipOleh` di kedua pintu |

Yang berpindah gaya (nol dihapus):

- `DistribusiInformasiTest` — tiga tes lepas dari `assertSee`/`viewData('perDept')`
  ke props. `perDept` kini menempel di barisnya, jadi asersinya justru lebih
  tepat: "PJO mendapat rincian untuk informasi INI", bukan "ada `perDept` di
  suatu tempat".
- `UrutTabelTest` — `Documents/Distribution` masuk daftar halaman ber-`urut:`.
  Cabang `?sumber=informasi` sengaja TIDAK punya kolom urut: barisnya bukan
  dokumen, jadi `Document::KOLOM_URUT` tak berlaku di sana (di Blade lama pun
  `_urut-th` tak dipakai di partial itu).

### 11.5 Gerbang

- [x] `php artisan test` → **683 lulus + 1 dilewati** (5568 asersi) — patokan naik 673 → **684**
- [x] `node_modules/.bin/tsc --noEmit` bersih · `npm run build` bersih
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **23 halaman**
      (bertambah `documents.distribution`)
- [x] **Sapuan tambahan untuk rute BERPARAMETER.** `smartpro:uji-render`
      melewati rute ber-`{document}`/`{informasi}`, jadi payload nyatanya
      diambil terpisah lalu dirender lewat bundel SSR yang sama: distribusi
      mutu sebagai PJO & SH, distribusi mutu ter-`sort`, distribusi informasi
      sebagai PJO (dengan `perDept`) & SH (tanpa), rincian informasi lingkup
      penuh / satu departemen / SH, perbaikan arsip SOP & FK unggahan, dan
      lembar Catatan Revisi. **11 payload, nol galat.**

      > Basis data pengembangan tak memuat satu pun Informasi maupun dokumen
      > lama SOP, jadi keduanya dibuat di dalam transaksi yang **selalu
      > di-ROLLBACK**. Itu bukan kerapian: `id1228` yang tertinggal di Fase 10
      > membuat `smartpro:cetak-baseline` merah tanpa satu byte keluaran cetak
      > berubah, dan gerbang yang selalu merah adalah gerbang yang berhenti
      > dibaca orang.
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline --id=…` (kedelapan id berbaseline) →
      **8 dokumen, semuanya `sama`**
- [!] **Pemeriksaan mata — butuh kamu:** `http://localhost:9092`, adu
      berdampingan dengan port 9091:
      - **Distribusi → Dokumen Mutu**: urutan bawaan cakupan TERENDAH di atas;
        tekan judul "No. Dokumen" → urutannya berganti; saring jenis &
        departemen; tombol Reset mengembalikan semuanya
      - **Distribusi → Informasi**: saklar sumber menyorot yang aktif, tombol
        "Per Departemen" hanya pada baris yang punya rinciannya (PJO/Admin/MD),
        membukanya menampilkan 7 pita, keterangan "hanya menghitung orang yang
        punya departemen" ikut tampil; sebagai SH tombol itu TIDAK ada
      - **Kartu peringatan** "N dokumen baru dibaca kurang dari separuh" muncul
        hanya bila memang ada; bunyinya berganti dokumen/informasi
      - **Rincian Informasi**: kolom Sudah/Belum Membaca beserta lencana
        angkanya, ikon ponsel/laptop per pembaca, pemilih departemen (PJO)
        menyempitkan daftar & tombol reset muncul; sebagai SH pemilihnya absen
      - **Perbaiki Dokumen Lama**: jenis & departemen terkunci, "Lihat berkas
        sekarang" membuka PDF, konfirmasi muncul SESUDAH kolom wajib terisi
        (kosongkan Judul → peramban yang menegur, bukan jendela konfirmasi),
        tombol "Lembar Catatan Revisi" hanya pada SOP/SP/IK
      - **Lembar Catatan Revisi**: pratinjau PDF di panel kanan, "Berkas ini N
        halaman", tambah/hapus baris catatan, dua radio pilihan mengubah bunyi
        konfirmasi, "Nanti Saja" kembali ke Dokumen Berlaku
      - mode gelap di keempat halaman

### Utang yang dibawa Fase 11

- **Lima Blade lama belum dihapus** (`documents/{distribution,rincianInformasi}`,
  `documents/_distribusi-informasi`, `documents/arsip/{edit,catatan}`) —
  CLAUDE.md §4 butuh konfirmasi. Sudah `grep`-bersih: yang merujuknya hanya
  sesama Blade yang juga sudah mati.
- **`partials/_rincian-pembaca` & `partials/_pita-cakupan` kini benar-benar
  mati.** Pemakai terakhirnya (`documents/rincianInformasi`,
  `documents/_distribusi-informasi`, `partials/_widget-distribusi-tabel`)
  semuanya sudah jadi TSX. Disebut di sini supaya `grep` di Fase 13.1 tak salah
  simpul.
- **`documents/fields/_revision_log` kini tak dirujuk siapa pun** — pemakai
  terakhirnya `arsip/catatan.blade.php`. Seluruh `documents/fields/**` sudah
  mati sejak Fase 10; ini menutup satu-satunya pengecualiannya.

---

## Fase 12 — Informasi, riwayat, log ✅ SELESAI (2026-08-30)

Tiga belas berkas didaftarkan; **dua sudah selesai sebelum fase ini dimulai**
(`partials/_modal-antrean` di Fase 5, `components/aksi` di Fase 8 — keduanya
tinggal cangkang yang hanya dipanggil Blade yang juga sudah mati). Yang
benar-benar tersisa **delapan layar** → **delapan halaman TSX** + satu komponen
formulir bersama. **Nol komponen registry baru, nol paket npm baru.**

Dengan fase ini **tak satu pun controller mengembalikan Blade lagi**, kecuali
`DocumentExportController` yang memang tetap Blade selamanya
(`documents/export-excel`).

### 12.1 Halaman & controller

| Blade lama | TSX baru | Controller |
|---|---|---|
| `informasi/index.blade.php` | `pages/Informasi/Index.tsx` | `InformasiController@index` |
| `informasi/_modal-hapus.blade.php` | ↑ **komponen di berkas yang sama** | `…@destroy` |
| `informasi/create.blade.php` | `pages/Informasi/Create.tsx` | `…@create` |
| `informasi/perbarui.blade.php` | `pages/Informasi/Perbarui.tsx` | `…@perbarui` |
| `informasi/_form.blade.php` | `components/informasi/FormInformasi.tsx` | dipakai KEDUA halaman di atas |
| `informasi/nonaktif.blade.php` | `pages/Informasi/Nonaktif.tsx` | `…@index` cabang kategori ditutup |
| `audit/index.blade.php` | `pages/Audit/Index.tsx` | `AuditController@index` |
| `job_executions/index.blade.php` | `pages/JobExecutions/Index.tsx` | `JobExecutionController@index` |
| `log/masukan.blade.php` | `pages/Log/Masukan.tsx` | `DocumentLogController@masukan` |
| `log/pesan.blade.php` | `pages/Log/Pesan.tsx` | `…@pesan` |
| `partials/_modal-antrean.blade.php` | — | **sudah** `components/DialogAntrean` (Fase 5) |
| `components/aksi.blade.php` | — | **sudah** `components/dokumen/StripAksi` (Fase 8) |

Komponen yang DIPAKAI ULANG apa adanya:

| Komponen | Lahir di | Dipakai di sini untuk |
|---|---|---|
| `DataTable` + `Paginasi` | Fase 8 | kelima tabel, termasuk baris `perluas` riwayat Informasi |
| `dokumen/PenyaringDokumen` | Fase 8 | kelima penyaring |
| `dokumen/DialogAksi` → `DialogRevisi` | Fase 8 | tombol Revisi di Log Masukan |
| `ConfirmDialog` | Fase 7 | Perbarui, Hapus (2 cakupan), Hapus riwayat pekerjaan, Balas |
| `Ikon` | Fase 3 | ikon kategori Informasi & ikon tombol baris Log Pesan |
| `Progress` | registry | bar checklist Riwayat Pekerjaan |

### 12.2 `PenyaringDokumen` digeneralisasi — satu komponen, bukan lima salinan

Empat dari lima penyaring baru TIDAK muat di kontrak lamanya: Audit Log
menyaring nama aksi sebagai **teks bebas**, Log Pesan & Riwayat Pekerjaan
menyaring **rentang tanggal**. Tiga prop kecil ditambahkan — `tipe`
(`pilih` | `teks` | `tanggal`), `labelCari`, `placeholderCari` — dan kelimanya
memakai kartu yang sama.

Alternatifnya lima `<form method="GET">` sendiri-sendiri, yaitu persis keadaan
yang membuat komponen ini lahir di Fase 8.

### 12.3 KEBOCORAN yang ditutup — kelas kesalahan KETUJUH berturut-turut

| Sumber | Yang menggendongnya | Penutupnya |
|---|---|---|
| daftar Informasi + formulir Perbarui | relasi `uploader` **dan `file_path`** | `InformasiController::baris()` (baru) |
| Audit Log | relasi `user` **dan `document` utuh** (`arsip_path` ikut) | perataan di `AuditController@index` |
| Riwayat Pekerjaan | relasi `user` + `document.department` | perataan di `JobExecutionController@index` |
| Log Masukan | `user`, `replier`, **`document.creator`** | `DocumentController::barisMasukan()` (Fase 8) + `dokumen` tiga kolom |
| Log Pesan | `document` utuh + `oleh` (User) di TIAP baris | dibuang dari `DocumentLogController::baris()` |

`informasi.file_path` adalah kembaran `arsip_path` (Fase 11): berkasnya duduk di
disk `local` dan nama berkasnya diacak Laravel, jadi menuliskannya ke atribut
`data-page` membatalkan lapis kedua itu diam-diam. Berkasnya dibuka lewat rute
`informasi.file`, satu-satunya pintu berpenjaga.

`DocumentLogController::baris()` diperbaiki di HULU, bukan di `through()`:
larik itu memang tak pernah butuh modelnya — `document` tak dibaca satu pun
pemanggil sesudah `tautan()` dirakit, dan `oleh` hanya dicetak sebagai nama.

### 12.4 Empat keputusan kecil yang perlu diketahui

1. **Riwayat Informasi MENEMPEL di barisnya**, bukan peta kedua ber-key nomor.
   Blade memakai `$riwayat[$info->nomor]` karena view memang menerima dua
   variabel; di props itu berarti dua larik yang harus dicocokkan ulang di
   klien. Query-nya tetap SATU untuk seluruh halaman (dua puluh baris = dua
   query), persis seperti sebelumnya.
2. **Jendela Revisi dikelompokkan per DOKUMEN di server.** Beberapa masukan bisa
   menunjuk dokumen yang sama, dan Blade lama pun sudah
   `groupBy('document_id')`. Mengulanginya di klien berarti aturan "siapa boleh
   mengadopsi" ikut pindah ke sana (pakem P5). `DialogRevisi` mendapat satu prop
   baru — `tercentang` — padanan `$masukanTercentang`, yang HANYA layar ini
   memakainya.
3. **Seluruh kunci formulir Informasi tetap dikirim, termasuk yang tak
   dirender.** `prohibited` di Laravel berarti "tidak ada ATAU kosong", dan
   Inertia mengirim `null` sebagai string kosong. Membangun larik data yang
   berbeda-beda per kategori hanya menambah cabang tanpa satu pun perbedaan
   hasil.
4. **Rona status memakai `variant` Badge registry** (keputusan Fase 6.3 butir 2),
   bukan hex karangan. LABEL-nya tetap datang dari server —
   `JobExecution::STATUS_LABELS`, `DocumentFeedback::STATUS_LABELS`, dan
   `DocumentLogController::TAHAP` — jadi tak ada teks status yang diketik di
   TSX. `TAHAP` tetap membawa nama rona Bootstrap-nya; yang dipetakan di klien
   hanyalah nama rona itu ke `variant`, bukan warnanya.

### 12.5 Tes — 684 → **695** (naik sebelas, nol berkurang)

Baru: `tests/Feature/InformasiLogInertiaTest.php` (11 tes / 168 asersi).

| Tes | Yang dijaga |
|---|---|
| `delapan_layar_memakai_komponen_inertia` | tiap rute → komponennya, termasuk cabang kategori ditutup |
| `props_tak_membocorkan_kata_sandi_maupun_jalur_berkas` | §12.3, pada HTML MENTAH keenam layar |
| `kolom_informasi_datang_dari_kategori_bukan_dari_kode` | padanan P1: `kolom_json` diubah di DB → props ikut sendiri |
| `kelima_daftar_membawa_bentuk_paginator_utuh` | `->through()`, bukan `->map()` (pelajaran Fase 6) |
| `penyaring_dikerjakan_server` | pakem P5 — audit `action`, masukan `status`, pesan `tahap` |
| `audit_meratakan_pelaku_dan_dokumen` | baris datar + `dokumen` dua kolom + format waktu |
| `riwayat_pekerjaan_membawa_progres_dan_label_status` | `progres()` tetap di model, label dari `STATUS_LABELS` |
| `log_pesan_meratakan_oleh_dan_tanggal_jadi_teks` | `oleh` string, `tanggal` teks, `document` **tak ada** |
| `jendela_revisi_dikelompokkan_per_dokumen_di_server` | §12.4 butir 2 |
| `jenis_unggahan_fk_ikut_terbaca_di_log_masukan` | pakem P6 |
| `tetap_403_di_luar_jangkauan` | pakem P4 — Non-Staff & SH, plus "membaca Informasi tetap terbuka" |

Yang berpindah gaya (nol dihapus):

- `InformasiTest` — tujuh asersi. Dua di antaranya dulu **hijau trivial** sejak
  halamannya Inertia: `assertDontSee('Tambah KEBIJAKAN')` dan
  `assertDontSee('Riwayat (1)')` mustahil merah lagi begitu tombolnya dirakit
  React. Keduanya kini membaca `kelola` dan larik `riwayat` barisnya.
  `assertSee(Informasi::labelKategori($slug))` gagal karena EJAAN, bukan karena
  kategorinya tertutup: `&` di "SERTIFIKAT & SIO" ter-escape jadi `\u0026` di
  dalam payload JSON.
- `LogDokumenTest` — dua tes. Id modal (`modalBalas{id}`, `modalRevisi{id}`)
  diganti `boleh_balas`/`boleh_revisi` per baris + isi `revisi`.
  `assertSee('Masukan Lapangan')` dibuang karena teks itu **juga label sidebar**
  — hijau tanpa syarat.
- `PesanBerkontekTest` — dua tes; penolong baru `tautanPesan()`.
- `MasterDataTest` — satu tes; "Sedang Ditutup" jadi komponen `Informasi/Nonaktif`.

### 12.6 Gerbang

- [x] `php artisan test` → **694 lulus + 1 dilewati** (5772 asersi) — patokan naik 684 → **695**
- [x] `node_modules/.bin/tsc --noEmit` bersih · `npm run build` bersih
- [x] `npm run uji-render` + `php artisan smartpro:uji-render` → **29 halaman**
      (bertambah `informasi.index`, `informasi.create`, `audit.index`,
      `job-executions.index`, `log.masukan`, `log.pesan`)
- [x] **Sapuan tambahan untuk yang dilewati penyapu rute.** `informasi.perbarui`
      berparameter, dan `Informasi/Nonaktif` hanya muncul saat kategorinya
      ditutup Admin — jadi keduanya tak pernah tersentuh `smartpro:uji-render`.
      Payload nyatanya diambil terpisah lalu dirender lewat bundel SSR yang
      sama. **2 payload, nol galat.**

      > Data ujinya dibuat di dalam transaksi yang **selalu di-ROLLBACK**, dan
      > `InformasiKategori::lupakanIngatan()` dipanggil di `finally` — ingatan
      > seumur-request itu tak ikut mundur bersama transaksinya.
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 cocok**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [!] **Pemeriksaan mata — butuh kamu:** butirnya sudah masuk
      `docs/CEKLIS-MATA-PER-PERAN.md` (§1f Admin, §3d Section Head, §7
      Non-Staff, dan satu baris di sapuan mode gelap §8c). Kerjakan dari sana,
      bukan dari sini.

### Utang yang dibawa Fase 12

- **Sepuluh Blade lama belum dihapus** (`informasi/{index,create,perbarui,
  nonaktif,_form,_modal-hapus}`, `audit/index`, `job_executions/index`,
  `log/{masukan,pesan}`) — CLAUDE.md §4 butuh konfirmasi, dan Fase 13.1 memang
  tempatnya.
- **`documents/_modal-revisi` & `documents/_masukan-list` kini benar-benar
  mati.** Pemakai terakhirnya `log/masukan.blade.php:170` — itulah alasan
  keduanya SENGAJA ditahan di keputusan pemilik **K4**. Alasan itu sekarang
  gugur.
- **`resources/views/components/aksi.blade.php` & `partials/_modal-antrean`
  kini tak dirujuk siapa pun** kecuali Blade yang juga sudah mati (dan
  `layouts/app.blade.php`, yang ikut dibuang di Fase 13.1).
- **Seluruh lapisan Blade non-cetak kini yatim.** Satu-satunya `view()` yang
  tersisa di controller adalah `documents.export-excel`. Gerbang `grep` Fase
  13.1 karena itu jadi jauh lebih sederhana — tapi tetap **`grep` dulu, jangan
  menghapus berdasarkan daftar fase.**

---

## Fase 13 — Bersih-bersih & serah terima 🟡 SEBAGIAN (2026-08-30)

**13.2, 13.3, dan 13.4 SELESAI. 13.1 (penghapusan 79 Blade yatim) DITAHAN atas
keputusan pemilik** — alasannya di §13.1 di bawah, dan alasan itu benar.

Sebelum apa pun: **Fase 12 dikomit** (`a766b85`), memenuhi K1 "mulai Fase 12,
satu fase satu commit". Sampai titik itu ia masih hidup di working tree saja.

### 13.1 — Hapus sisa lapisan lama · **DITAHAN, bukan terlupa**

Audit sudah lengkap, tinggal eksekusinya yang menunggu. **79 Blade** yatim,
**11.577 baris**, ditambah `resources/js/app.js` + `resources/js/bootstrap.js`.

Bukti nol perujuk — empat `grep` yang masing-masing menutup satu jalur masuk:

| Jalur masuk | Hasil |
|---|---|
| `view()` / `View::make` di `app/` + `routes/` | hanya `documents.export-excel` & `emails.dokumen` |
| `@extends` / `@include` dari 14 berkas yang bertahan | hanya sesama `documents/print/*` + `errors/*` → `layouts.guest` |
| `assertViewIs` / `assertViewHas` di `tests/` | **nol** (satu-satunya sebutan adalah komentar sejarah di `RevisionLogTest.php:178`) |
| input Vite (`vite.config.js`) & `@vite(...)` | hanya `resources/css/app.css` + `resources/js/app.tsx` |

Ditambah dua temuan yang mengubah rencana:

- **`resources/js/bootstrap.js` cuma memasang `axios` ke `window`, dan `axios`
  nol pemakai di `resources/js`.** Jadi `axios` di `devDependencies` ikut
  yatim — Inertia memakai `fetch`/XHR-nya sendiri.
- **`Blade::if('role', …)` di `AppServiceProvider.php:101` kini direktif tanpa
  pemakai** — `@role` nol kemunculan di seluruh `resources/views/`.

**Kenapa ditahan.** Nilai berkas-berkas itu bukan di kode melainkan sebagai
**acuan mata**: `docs/CEKLIS-MATA-PER-PERAN.md` masih `[!]` seluruhnya, dan
cara mengerjakannya adalah membandingkan tampilan lama dengan yang baru. Begitu
Blade-nya hilang, satu-satunya acuan tinggal port 9091 — dan kalau di sana
ternyata ada selisih kecil, membaca Blade lama jauh lebih cepat daripada
`git show`. Biaya menahan tetap **nol**, persis seperti K4: berkasnya tak dimuat
siapa pun, tak masuk bundel, tak melambatkan apa pun.

> **Prasyarat penghapusan sudah dikerjakan lebih dulu** (lihat §13.1a) — jadi
> yang tersisa nanti benar-benar tinggal satu perintah, bukan penyelidikan
> ulang.

Daftar yang akan dihapus, per folder:

```
(akar) dashboard 1 · account 1 · akses 2 · approvals 2 · audit 1 · auth 3
components 1 · documents 19 · documents/arsip 2 · documents/fields 9
informasi 6 · job_executions 1 · layouts 1 (app.blade.php) · log 2
masukan-sejawat 1 · nonaktif 1 · partials 15 · pengaturan 3 · review 4
users 4                                                        = 79 berkas
```

Yang **TIDAK** ikut dihapus, dan alasannya:

- **14 Blade yang memang tetap Blade selamanya** — 9 cetak + `export-excel` +
  `emails/dokumen` + `errors/{403,504}` + `app.blade.php` (root template
  Inertia, bukan sisa lapisan lama).
- **`layouts/guest.blade.php`** — lihat §13.1a.
- **`public/soft-ui/`** — folder ASET, bukan Blade, dan
  `AuthLayout.tsx:61` masih memakai `img/curved-images/curved6.jpg`. Yang
  benar-benar yatim di sana hanya `css/soft-ui-dashboard.min.css`; memisahkan
  satu berkas dari folder aset pihak ketiga tak sepadan dengan risikonya.

### 13.1a — `layouts/guest.blade.php` DITULIS ULANG, bukan dihapus

Daftar 13.1 di `MIGRASI-SHADCN.md` menyuruh menghapusnya. **Menghapusnya apa
adanya akan mematikan dua halaman galat:** `errors/403` dan `errors/504`
meng-`@extends` berkas itu, dan keduanya memang tetap Blade selamanya karena
Laravel merendernya di luar konteks Inertia.

Keputusan pemilik: tulis ulang jadi cangkang mandiri. Isinya kini **nol CDN,
nol `@vite`, nol manifest** — Soft UI, Bootstrap 5 JS, Bootstrap Icons, dan
Poppins semuanya dibuang; yang tersisa HTML + `<style>` inline. Dengan itu,
**satu-satunya CDN lama yang masih hidup di proyek tinggal di
`layouts/app.blade.php`**, yang memang antre dihapus di 13.1.

Alasannya bukan sekadar kebersihan: **504 justru muncul saat server sedang
tersendat**, dan saat itulah tiga permintaan CDN eksternal paling mungkin ikut
menggantung. Halaman galat yang bergantung pada `@vite` juga akan ikut mati
persis pada keadaan yang paling sering melahirkannya — `public/build` yang
hilang atau tak sepadan (lihat langkah 2b RUNBOOK).

Palet & radiusnya disalin dari token `resources/css/app.css` supaya sekeluarga
dengan aplikasi, dan skrip anti-FOUC `localStorage.theme` ikut disalin dari
`app.blade.php` supaya mode gelap tidak berkedip. Logo dua berkas
(`logo-web` / `logodarkmode`), sama seperti `AuthLayout.tsx`.

Kedua halaman galat ikut dibersihkan dari kelas Bootstrap (`btn-pp`, `text-sm`,
`fw-bold`) dan ikon `bi-house-door`. **Tombolnya sengaja tanpa ikon, bukan
diganti SVG karangan** — satu ikon di halaman yang tak punya lagi pustaka
ikonnya tak sepadan dengan menambahkan SVG mentah yang tak dipakai di tempat
lain mana pun.

### 13.2 — Gerbang baseline ekspor: **PDF 8/8 identik, Excel 5/6 identik**

- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 OK**
- [x] `php artisan smartpro:cetak-baseline` → **8 dokumen, semuanya `sama`**
- [x] Daftar Induk `.xls` keenam jenis → **lima `sama` byte-per-byte, satu
      berbeda: FK — dan bedanya DATA, bukan kode.**

Yang keenam itu ditelusuri sampai barisnya, bukan diterima sebagai "mungkin
wajar":

```
baris <tr>: baseline=6  sekarang=6
  HANYA DI SEKARANG: 1 PPA-ADRO-FK-ICTMD-99 Formulir Kerja Baseline Uji …
  HANYA DI BASELINE: Belum ada dokumen FK yang berlaku.
```

Baseline `.xls` diambil **2026-08-28 05:22**; dokumen FK 1226 dibuat
**2026-08-28 12:40**, di penutupan utang Fase 0 — yakni SESUDAH baseline
direkam. Jadi baris "belum ada dokumen FK" digantikan oleh satu-satunya dokumen
FK yang kini ada. Nol perbedaan lain, dan kelima jenis sisanya md5-identik —
bukti bahwa `export-excel.blade.php` tak tersentuh.

> **Keputusan yang menunggu pemilik:** menyegarkan `xls/DAFTAR-INDUK-FK.xls` +
> barisnya di `CATATAN.txt`, atau membiarkannya merah selamanya dengan
> penjelasan ini. Aku **tidak** menyentuh `C:\baseline-smartpro\` sendiri —
> menulis ulang baseline adalah hal yang tak boleh dikerjakan diam-diam.

Perbandingan Excel dikerjakan lewat `DocumentExportController::export()`
langsung, bukan lewat kernel HTTP: yang diuji di sana **byte keluarannya**, dan
otorisasi rutenya sudah punya tesnya sendiri. Ekspor Excel tak memuat satu pun
stempel waktu, jadi md5 **mentah** memang sah di sini — berbeda dengan PDF,
yang selalu butuh `sidik()` (lihat §Kenapa perintah itu perlu).

### 13.3 — Gerbang akhir lainnya

- [x] `php artisan test` → **694 lulus + 1 dilewati, 0 gagal** (5772 asersi) —
      patokan **695** bertahan, nol berkurang
- [x] `node_modules/.bin/tsc --noEmit` bersih
- [x] `npm run build` bersih · `npm run uji-render` bersih
- [x] `php artisan smartpro:uji-render` → **29 halaman, nol galat**
- [x] `php artisan view:cache` → seluruh 94 Blade (termasuk ketiga yang ditulis
      ulang) terkompilasi tanpa galat, lalu `view:clear`
- [!] **Pemeriksaan mata — butuh kamu:** `docs/CEKLIS-MATA-PER-PERAN.md`, masih
      utuh belum dikerjakan. Inilah satu-satunya yang menahan 13.1.

> **Satu merah yang ternyata ulahku sendiri, dicatat supaya tak membingungkan
> siapa pun yang mengulang gerbang ini.** Jalannya suite yang pertama gagal di
> `KoneksiMobileTest:222` dengan
> `rename(...\storage\framework\views\8d26510.tmp, …): Access is denied` pada
> `documents/print/_cover.blade.php`. Itu bukan regresi: aku menjalankan
> `smartpro:cetak-baseline` **bersamaan** dengan suite, dan di Windows kedua
> proses berebut menulis singgahan Blade yang sama. Berkas itu sendirian lulus
> 11/11, dan suite penuh yang diulang sendirian lulus 694. **Jangan jalankan
> perintah yang merender Blade bersamaan dengan `php artisan test`.**

### 13.4 — Serah terima: `docs/RUNBOOK-RILIS.md` diperbarui

Lima suntingan, dan yang pertama adalah **lubang yang benar-benar akan
mematikan produksi** kalau rilis dilakukan dengan runbook lama:

1. **Langkah 2b BARU — bangun aset frontend.** `resources/views/app.blade.php`
   memanggil `@vite`, yang membaca `public/build/manifest.json`. **Berkas itu
   di-`.gitignore`, jadi `git pull` tidak membawanya**, dan tanpa itu Laravel
   melempar `ViteManifestNotFoundException`: yang terlihat pengguna adalah
   **500 di semua rute sekaligus**, bukan halaman tanpa gaya. Rilis sebelum
   migrasi tak punya langkah ini karena lapisan lama murni CDN. Dua cara
   ditulis — bangun di laptop lalu unggah `public/build/` (dianjurkan untuk
   shared hosting), atau bangun di server bila `node -v` menjawab v20+ —
   berikut satu perintah pemeriksa manifest.
2. **Langkah 0** — patokan tes 535 → 694, plus keempat gerbang frontend,
   dengan catatan kenapa `smartpro:uji-render` tak bisa digantikan `tsc`
   maupun `npm run build` (keduanya tak merender apa pun; layar putih lolos).
3. **Langkah 8** — kenapa cron `queue:work` wajib, bukan pelengkap: tanpa
   pekerja, email mengendap di tabel `jobs` tanpa satu pun pesan salah,
   sementara lonceng tetap muncul karena kanal `database` sinkron. Kegagalan
   separuh itu yang paling sulit disadari. (Sumbernya komentar `jalankan.bat`.)
4. **Langkah 10** — empat verifikasi baru yang **mustahil gagal di laptop dan
   hanya bisa gagal di server**: layar putih berkonsol bersih, perpindahan
   Inertia yang membeku, mode gelap yang tak ikut termuat, dan satu URL 403
   untuk membuktikan halaman galat berdiri sendiri tanpa build.
5. **Langkah 11 (rollback)** — `git checkout` mengembalikan kode **tanpa**
   mengembalikan `public/build`, jadi hasilnya bundel baru di atas rute lama.
   Karena itu: arsipkan `public/build/` per rilis, dinamai commit-nya.

Ditambah satu catatan di langkah 2: `tightenco/ziggy` ada di `require`, bukan
`require-dev` — sudah diperiksa. Kalau kelak ia berpindah, `--no-dev` akan
mematikan `@routes` dan setiap halaman ikut mati.

### Yang tersisa di Fase 13

- [ ] **13.1** — menunggu `CEKLIS-MATA-PER-PERAN.md` selesai (keputusan pemilik)
- [!] **Keputusan baseline FK `.xls`** — segarkan atau biarkan (§13.2)
- [ ] Ikutan 13.1 yang baru ketahuan: `axios` di `devDependencies` dan
      `Blade::if('role')` di `AppServiceProvider.php:101` sama-sama nol pemakai

---

## Fase 13 — daftar tugas asli

- [x] 4 Autentikasi (3 view) · [x] 5 Dashboard (9) · [x] 6 Pengguna & akses (7)
- [x] 7 Pengaturan & master data (3) · [x] 8 Daftar dokumen (6 + 7 berkas Fase 11)
- [x] 9 Wizard + **11** komponen field (3 halaman) — **terberat**
      — `Signature.tsx` tak dibuat: tipe seksi itu tak pernah dipakai schema mana pun
- [x] 10 Peninjauan & persetujuan (8 view → 7 halaman + 3 komponen bersama)
- [x] 11 Revisi/arsip/distribusi (10 → **4 layar**; enam sisanya sudah terbawa
      Fase 7–8) — nol komponen baru, nol paket npm baru
- [x] 12 Informasi/riwayat/log (13 → **8 layar**; dua sisanya sudah terbawa
      Fase 5 & 8) — nol komponen registry baru, nol paket npm baru; sejak fase
      ini **nol controller mengembalikan Blade** kecuali ekspor Excel
- [~] 13 Bersih-bersih & gerbang akhir — **13.2/13.3/13.4 selesai**; 13.1
      (hapus 79 Blade yatim) ditahan sampai ceklis mata selesai

---

## Keputusan pemilik — 2026-08-30 ✅

Kedelapan utang terbuka dari Fase 0–11 diputuskan sekaligus. **Empat dijalankan,
empat sengaja TIDAK dijalankan** — dan yang kedua itu bukan penundaan malas,
melainkan keputusan bahwa mengerjakannya sekarang justru merusak.

### Dijalankan

**K1 — Titik simpan Git.** Fase 1–11 tak pernah dikomit; 272 berkas hanya hidup
di working tree, tanpa jaring pengaman. Dikomit sebagai **satu commit
checkpoint**, bukan sebelas.

> Kenapa tidak dipecah per fase (CLAUDE.md P8). Nilai aturan itu ada dua:
> `git bisect` per fase, dan granularitas review. Yang pertama **sudah hilang
> dan tak bisa dipulihkan** — keadaan kerja antar-fase tak pernah direkam, jadi
> memecah sekarang hanya melahirkan commit yang tak satu pun pernah benar-benar
> dijalankan, dan `bisect` di atas commit karangan lebih berbahaya daripada tak
> ada `bisect` sama sekali. Yang kedua **tidak hilang**: dokumen ini memuat
> sebelas bagian lengkap dengan gerbang dan alasan tiap keputusan, dan itulah
> yang dibaca peninjau — bukan `git log`. Memecah berkas bersama
> (`DocumentController` disentuh di Fase 8, 9, 11; `TestCase.php`; `CLAUDE.md`;
> `docs/`) menuntut `git add -p` puluhan hunk dan berujung pada commit yang tak
> bisa di-build.
>
> **Mulai Fase 12: satu fase, satu commit, tanpa kecuali.**

**K2 — Sepuluh pemeriksaan mata digabung.** Jadi
`docs/CEKLIS-MATA-PER-PERAN.md`, disusun per PERAN (delapan bagian: tamu, Admin,
GL, SH, DH, PJO, MD, Non-Staff, plus satu sapuan lintas peran). Nol butir
dibuang; tiap baris menyebut fase asalnya.

> Kenapa per peran. Yang dikerjakan berurutan di peramban adalah menu satu
> orang, bukan satu fase — mengurutkannya per fase memaksa login-logout puluhan
> kali untuk memeriksa hal yang bertetangga di layar yang sama. Mode gelap
> dicabut dari tiap fase dan dijadikan satu sapuan di akhir, dengan alasan yang
> sama.
>
> **Dikerjakan SEBELUM Fase 12.** Gerbang otomatis membuktikan halaman bisa
> dirender dan props-nya benar; ia tak pernah membuktikan tata letaknya setara
> dengan yang lama. Kesalahan tata letak yang lahir di `AppLayout` (Fase 3)
> sudah menurun ke ±30 halaman — menemukannya sesudah Fase 13 berarti
> memperbaikinya di 30 tempat, bukan satu.

**K3 — `id1228` dibuang.** Draft berjudul "m" buatan `ADM-0001`
(2026-08-30 03:12), 4 seksi, 1 penulis, **0 versi / tinjauan / persetujuan /
lampiran**, tak pernah punya `doc_number_final`, dan tak dirujuk sebagai
`revises_document_id` oleh siapa pun.

- Dicadangkan lebih dulu ke `C:\baseline-smartpro\cadangan-id1228-*.json`
  (documents + contents + authors + attachments + audit_logs).
- Dihapus lewat **jalur yang persis sama** dengan tombol "Hapus" pada draft
  (`DocumentController::destroy` → `$document->delete()` + satu baris audit).
  `Document` memakai `SoftDeletes`, jadi barisnya **masih ada** dengan
  `deleted_at` terisi — pemulihannya satu `restore()`.
- **Hasil: `php artisan smartpro:cetak-baseline` TANPA `--id` kini hijau
  penuh** — 8 dokumen, semuanya `sama`. Gerbang Fase 13.2 kembali jadi satu
  perintah tanpa daftar id yang harus diingat orang.

> Alternatifnya (`--tulis --id=1228`) menambahkan draft setengah jadi berjudul
> "m" ke baseline **permanen**, ikut dirender ulang tiap gerbang selamanya,
> tanpa menjawab pertanyaan apa pun. Nomor sementaranya
> (`PPA-ADRO-SOP-ICTMD-03`) kembali ke kolam dan diisi SOP ICTMD berikutnya —
> itu memang perilaku `DocumentNumberService::firstUnusedSeq` yang disengaja,
> dan ia tak pernah memegang nomor final.

**K5 (sebagian) — lubang pola `.gitignore` ditutup.** Daftar `.env` di sana dulu
ditulis satu-satu, dan `.env.cadangan-uji` lolos justru karena namanya tak ada
di daftar itu. Kini `.env.*` + `!.env.example`.

> Yang **TIDAK** dikerjakan sekarang, dan itu disengaja: berkasnya tidak
> di-`git rm --cached`, dan kuncinya tidak dirotasi. `.gitignore` tak pernah
> membatalkan pelacakan yang sudah ada, dan menghapus dari indeks pun tak
> menghapus rahasia dari **riwayat** yang sudah terdorong ke GitHub — jadi
> tindakan itu memberi rasa aman tanpa keamanan. Keputusan pemilik 27 Agu 2026
> (utang **U1**) tetap berlaku: dibiarkan selama repo privat, **wajib dirotasi
> saat repo dibagikan lebih luas**. Yang berubah cuma satu: salinan `.env`
> BERIKUTNYA tak akan lolos lagi.

### Sengaja TIDAK dijalankan

**K4 — 49 Blade mati tetap di tempatnya sampai Fase 13.1.** Bukan kehati-hatian:
menghapusnya sekarang **akan merusak**. `log/masukan.blade.php:170` masih
`@include('documents._modal-revisi')`, dan halaman itu baru pindah di Fase 12.
Pola ini sudah dua kali nyaris memakan korban (`_modal-antrean` di Fase 5,
`_modal-musnahkan-semua` di Fase 7) — **daftar fase di dokumen bisa keliru,
`grep` yang benar.** Biayanya menahan: nol. Berkasnya tak dimuat siapa pun, tak
masuk bundel, tak melambatkan apa pun.

**K6 — label "Ditolak" dibiarkan.** `users.status = 'rejected'` memikul dua arti
(pendaftaran ditolak / akun dimatikan Admin), tapi **saat ini nol pengguna
berstatus itu**. Rename global juga salah arah: di "Persetujuan Akun"
konteksnya memang pendaftaran, dan "Ditolak" tepat di sana. Kalau kelak ada
keluhan nyata, perbaikan yang jujur adalah prop konteks pada
`components/LencanaStatusAkun.tsx` — satu berkas, sebab peta itu memang sengaja
tak disalin ke mana-mana.

**K7 — setel ulang isi widget dashboard ditunda sampai sesudah Fase 13.**
Menyetelnya di tengah migrasi berarti menyetelnya dua kali. Kelenturannya sudah
disiapkan (tabel di §Penyesuaian widget dashboard menyebut berkas mana untuk
perubahan apa), jadi menunda tak menaikkan biayanya sedikit pun. Yang perlu
disiapkan pemilik cuma satu: **per peran, kartu mana yang muncul dan di kolom
mana.**

**K8 — akun rekaan `GLSHE-0001` (utang U5) ditunda sampai sesudah Fase 13.** Ia
terkurung di basis data uji, tak pernah menyentuh produksi, dan sudah ditandai
`ponytail:` di `tests/TestCase.php`. Menggantinya di tengah migrasi berarti
menyentuh patokan 684 tes tanpa satu pun perbaikan yang terlihat pengguna.

### Yang tidak butuh keputusan

Bundel `Dashboard` 508 KB melewati ambang peringatan Vite. Ia chunk per-halaman
yang hanya dimuat saat dashboard dibuka; memecahnya lebih jauh berarti mengarang
`manualChunks` demi angka peringatan, bukan demi waktu muat yang terukur.
Disebut di sini supaya berhenti muncul sebagai "utang".

---

## Penutupan utang Fase 0 — 2026-08-28 ✅

Diputuskan pemilik: impor dokumen + buat sisa yang kurang.

- [x] **Impor 1223/1224/1225** dari `smartpro_fresh` → `smartpro_shadcn`
      (INSERT-only: documents 3 · document_contents 15 · document_authors 3 ·
      attachments 1 · audit_logs 3, nol bentrok id) + 1 berkas lampiran
      `storage/app/public/lampiran/ICTMD/SOP/`. Ketiganya dibuat pemilik lewat
      wizard di aplikasi asli, PDF-nya ada di `docs/dokumen/`.
- [x] **1226** FK arsip/unggahan · **1227** SOP ber-CATATAN REVISI — dibuat di
      `smartpro_shadcn`, keduanya masuk baseline.
- [x] **`php artisan smartpro:cetak-baseline`** — perintah baru
      (`app/Console/Commands/BaselineCetak.php`). Tanpa `--tulis` ia merender
      ulang seluruh dokumen non-arsip dan membandingkannya dengan
      `C:aseline-smartpro\pdf`. Gerbang Fase 13.2 sekarang satu perintah.
      Hasil 2026-08-28: **8 dokumen, semuanya `sama`.**

### Kenapa perintah itu perlu — md5 mentah TIDAK BISA dipakai

Tiap render DomPDF menulis tiga hal yang selalu berbeda: `/CreationDate`,
`/ModDate` (obyek `/Info`), dan `/ID[<..><..>]` di trailer. Di luar ketiganya,
render ulang dokumen yang sama menghasilkan berkas **berukuran persis sama dan
byte-identik** — sudah diukur obyek per obyek. Jadi daftar `md5=` di
`CATATAN.txt` Fase 0.1 tidak akan pernah cocok lagi, dan itu **bukan** tanda
ada yang rusak. `sidik()` di perintah itu menetralkan ketiganya lebih dulu.

### Anomali `docs/dokumen/JSA.pdf` — belum terjelaskan, tidak memblokir

`JSA.pdf` (510.202 B) tidak bisa direproduksi dari kode + basis data mana pun
di mesin ini; render ulang dokumen 1179 selalu 507.476 B dan cocok dengan
baseline lama. Yang sudah dipastikan **sama** sehingga bukan penyebabnya: baris
`documents` 1179 beserta contents/authors/reviews/approvals/versions di kedua
DB, `mesin-cetak.md5` lolos penuh, aset gambar identik, timezone identik, dan
SOP/IK/SP yang dirender pemilik di sesi yang sama justru cocok sempurna.

Yang berbeda hanya: `Tanggal Pembuatan` mundur **tepat satu hari** (`created_at`
= 2026-08-27 **00:27:59** WITA = 2026-08-26 16:27:59 UTC — dan
`documents.created_at` bertipe **TIMESTAMP**, yang MySQL konversi menurut
`time_zone` sesi, jadi koneksi ber-UTC membacanya sebagai tanggal 26), plus dua
obyek gambar (logo + stempel APPROVED) yang terkompresi berbeda. Seluruh obyek
teks lain identik byte per byte.

Keputusan: pakai baseline JSA lama; `docs/dokumen/JSA.pdf` disimpan sebagai
acuan mata saja.

## Utang lain yang dibawa dari Fase 0
- `.env.cadangan-uji` di akar proyek masih menunjuk `smartpro_uji` & port 9091.
  Tidak dimuat siapa pun, dibiarkan (hapus berkas butuh konfirmasi, CLAUDE.md §4).
- Bootstrap 5 + Alpine + SweetAlert2 **masih aktif** di `layouts/app.blade.php`
  dan `layouts/guest.blade.php` — memang begitu rencananya sampai Fase 13.
  Halaman Inertia sejak Fase 1 memakai root template sendiri tanpa CDN itu.
