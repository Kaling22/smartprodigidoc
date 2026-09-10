# Rencana Persiapan Rilis Produksi — 14 Butir

## Context

Aplikasi SmartPro Document Generator (PT PPA) sudah menyelesaikan migrasi tampilan ke Inertia + React + shadcn (Fase 0–13) dan pengembaran V2 "maia" (PATOKAN-GAYA-V2 Fase 0–6, 41 halaman, `sisa 0 halaman`). Sebelum kode didorong ke produksi (`https://adw.proworkppa.com`), pemilik mengajukan **14 permintaan** yang mencakup perbaikan fungsi (AI, penomoran, alur upload), penyempurnaan cetak, dan sepuluh penyesuaian dasbor/halaman.

Rencana ini memecah keempat belas permintaan itu menjadi **14 fase** yang tiap fasenya berhenti untuk review (CLAUDE.md §5), berurutan menurut prioritas yang pemilik tetapkan sendiri, dan menutup seluruhnya dengan verifikasi kesiapan produksi (email, mobile, cron).

### Keputusan pemilik yang mengikat rencana ini (2026-09-01)

| #Keputusan |                                                                                                                                                                                          |
| ---------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **K-A**    | **Produksi memakai V2.** Sakelar `session('ui_v2')` dijadikan **menyala secara bawaan**; butir 8–14 dikerjakan **hanya di pohon V2**. V1 dibiarkan sebagai cadangan mati, tidak dihapus. |
| **K-B**    | **Batas revisi jadi 0–5**, naik edisi pada yang ke-**6** (`nextEditionRevision` ambangnya `>= 6`, bukan `>= 5`).                                                                         |
| **K-C**    | Butir 14: **label DAN angka** meter Tertinjau berbeda per peran, memakai rumus "**persen yang sudah mencapai tahap milik peran itu**" (penyebut tetap sama untuk semua peran).           |
| **K-D**    | Butir 5 (yatim sub-bab) **dikerjakan, tapi TIDAK didahulukan**. Baseline cetak di-*bless* ulang **setelah** pemilik memeriksa PDF-nya dengan mata.                                       |
| **K-E**    | Butir 7: pilihan "gabung" dicabut **beserta** dua isian `potong_halaman` / `halaman_awal`. `ArsipPenggabung.php` **tidak dihapus**, hanya tak dipanggil siapa pun.                       |
| **K-F**    | Butir 4: modal nomor bentrok berlaku di **dua** tempat — form "Daftarkan dokumen lama" dan sakelar "Input manual" wizard.                                                                |
| **K-G**    | Butir 9: urutan departemen **tetap menurut jumlah** (dari server); yang diubah hanya gridnya jadi **3 atas / 4 bawah**.                                                                  |
| **K-H**    | Butir 10–11: tambah `rincian` per aksi; **`jumlah`**** (urutan wajah) TIDAK berubah** — kartunya tetap bukan papan peringkat.                                                            |

### Urutan pengerjaan

Prioritas pemilik: **3 → 7 → 1 → 6** didahulukan. Sisanya diurutkan dari yang paling ringan ke paling rumit; butir 5 (cetak) dan butir 2 (verifikasi rilis) di paling belakang.

| FaseButirIsiBerat |             |                                                         |    |
| ----------------- | ----------- | ------------------------------------------------------- | -- |
| **0**             | —           | Sakelar V2 menyala bawaan (prasyarat butir 8–14)        | XS |
| **1**             | **3**       | Roll-over edisi/revisi 0–5, satu konstanta              | S  |
| **2**             | **7**       | Cabut "gabung"; salin manual → auto-approve             | L  |
| **3**             | **1**       | AI berfungsi + pengerasan                               | M  |
| **4**             | **6**       | Pisah nomor & judul lampiran SOP                        | M  |
| **5**             | **4**       | Modal nomor bentrok + saran nomor otomatis              | M  |
| **6**             | **13**      | Filter departemen di Status Dokumen Staff               | XS |
| **7**             | **8**       | Pemilik dokumen di widget Masukan Lapangan              | S  |
| **8**             | **9**       | Warna & tata letak Sebaran per Departemen               | S  |
| **9**             | **12**      | Pratinjau PDF di halaman Persetujuan PJO                | M  |
| **10**            | **14**      | Meter Tertinjau per peran                               | M  |
| **11**            | **10 + 11** | Rincian aktivitas Performa PIC                          | L  |
| **12**            | **5**       | Sub-bab tak boleh yatim (cetak) + bless baseline        | XL |
| **13**            | **2**       | Verifikasi email / mobile / cron + gerbang pra-produksi | M  |

---

## Aturan yang berlaku di SETIAP fase

1. **Satu fase → berhenti → tunggu review + commit** (CLAUDE.md §5, pakem P8).
2. **Sembilan gerbang** dijalankan tiap fase (REVISI-UI-V3 §6):
   ```
   node_modules/.bin/tsc --noEmit
   npm run build
   npm run uji-render
   php artisan smartpro:uji-render
   php artisan smartpro:uji-render --pratinjau
   php artisan test                                   # patokan ≥ 718
   md5sum -c C:/baseline-smartpro/mesin-cetak.md5     # 23/23
   php artisan smartpro:cetak-baseline                # 8 dokumen "sama"
   git diff components.json                           # KOSONG

   ```
   Dua gerbang terakhir yang menyangkut cetak baru boleh berubah di **Fase 12**, dan hanya sesudah pemilik menyetujui PDF-nya (K-D).
3. **Jumlah tes tak boleh berkurang** (CLAUDE.md §14c). Tes yang "tidak relevan lagi" **diperbarui**, tidak dihapus.
4. **Nol berkas dihapus tanpa konfirmasi** (CLAUDE.md §4).
5. **Nol aturan peran/izin/status ditulis di TSX** (CLAUDE.md §4) — semuanya datang sebagai props dari server.
6. **Nol berkas di ****`components/ui/**`**** dan ****`components/ui-maia/**`**** disunting** (CLAUDE.md §4, PATOKAN-GAYA-V2 §2).
7. **Pohon V2 tak boleh mengimpor ****`lucide-react`**** maupun ****`@/components/ui/`** (dijaga `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama`).
8. **Periksa kebocoran props** tiap kali sebuah model pindah ke props (CLAUDE.md §4) — sudah tujuh kali nyaris bocor.

---

## Fase 0 — Sakelar V2 menyala bawaan (prasyarat)

**Kenapa lebih dulu.** Butir 8–14 hanya disunting di `pages/V2/*` dan `components/v2/*` (K-A). Dengan sakelar mati, satu pun perubahan itu tak akan terlihat pengguna produksi — dan tak bisa diperiksa mata selama pengerjaan.

### Berkas

| BerkasPerubahan                                       |                                                                                                                                                                                      |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `app/Inertia/PratinjauResponseFactory.php:40`         | `session('ui_v2')` → `session('ui_v2', true)` + perbarui docblock (sekarang berbunyi "dengan bendera MATI, kelas ini tak mengubah apa pun" — sudah tak benar)                        |
| `app/Http/Middleware/HandleInertiaRequests.php:111`   | `(bool) session('ui_v2')` → `(bool) session('ui_v2', true)`                                                                                                                          |
| `app/Http/Controllers/Auth/LoginController.php:68-75` | bawa **nilai** benderanya melintasi `session()->invalidate()`, bukan hanya kasus `true` — supaya pilihan "kembali ke V1" juga bertahan sesudah logout                                |
| `app/Console/Commands/UjiRender.php:225`              | `if (option) put(true)` → `put('ui_v2', (bool) $this->option('pratinjau'))`. **Wajib**: tanpa ini kedua sapuan gerbang render sama-sama menggambar V2, dan pohon V1 berhenti terjaga |
| `tests/Feature/PratinjauUiTest.php:38`                | `test_bendera_mati_tetap_menggambar_komponen_lama` → tambahkan `withSession(['ui_v2' => false])`. **Diperbarui, bukan dihapus** — yang diuji tetap "bendera mati → komponen V1"      |

### Tes

+1: `test_bawaan_menggambar_v2` — sesi kosong (pengguna baru, produksi) harus mendapat `V2/Dashboard`. Patokan **718 → 719**.

### Risiko & mitigasi

- **`CEKLIS-MATA-PER-PERAN.md`**** belum selesai untuk V2.** Menyalakan bawaan berarti V2 jadi yang dilihat pengguna sebelum ceklis mata itu tuntas. Mitigasi: sakelarnya **tetap ada** (rute `pratinjau.toggle`), jadi pemilik bisa membandingkan berdampingan kapan pun; dan pemeriksaan mata per peran dijadwalkan sebagai bagian Fase 13.
- Halaman galat (403/504) sengaja **bukan** Inertia — tak terpengaruh.

---

## Fase 1 — Butir 3: roll-over edisi/revisi 0–5

**Masalah.** `DocumentService::nextEditionRevision()` (`app/Services/DocumentService.php:151-154`) berbunyi `$noRevisi + 1 >= 5`, sehingga revisi tampil 0–4 dan edisi naik pada yang **ke-5**. Pemilik menetapkan naik pada yang **ke-6** (K-B). Angka `4` juga tersalin sebagai literal di **tiga** aturan validasi yang terpisah — persis bentuk yang akan menyimpang.

### Perubahan

1. **Satu sumber kebenaran baru.** `DocumentService::MAKS_REVISI = 5` (konstanta publik), lalu:
   ```php
   public static function nextEditionRevision(int $edisi, int $noRevisi): array
   {
       return $noRevisi + 1 > self::MAKS_REVISI ? [$edisi + 1, 0] : [$edisi, $noRevisi + 1];
   }

   ```
2. Tiga literal `max:4` → `'max:'.DocumentService::MAKS_REVISI`:
   - `app/Http/Requests/ArsipDocumentRequest.php:65`
   - `app/Http/Controllers/DocumentArsipController.php:140`
   - `app/Http/Requests/StoreInformasiRequest.php:71` (Informasi memakai roll-over yang sama — `InformasiController.php:207-216`)
3. Perbarui komentar yang menyebut "0..4" / "revisi ke-5": `DocumentService.php:146,157`, `DocumentController.php:384-389`, `DocumentLogController.php:111`, `DocumentRevisionController.php:276`, `InformasiController.php:207`, `ArsipDocumentRequest.php:61-63`, **`CLAUDE.md`**** §7**.

### Yang TIDAK berubah — dan itu penting

- **Kapan** revisi naik: tetap saat draft **DIKIRIM** (`Document::revisiSaatKirim()`, `DocumentService.php:176-182`), bukan saat "Ajukan Revisi" ditekan.
- Pewarisan nomor dokumen pada revisi Tipe B (`DocumentService.php:170`).
- Dokumen yang sudah ada: dokumen ber-`no_revisi = 4` yang direvisi lagi kini menjadi **Revisi 5** (dulu Edisi+1 Rev 0). Ini perubahan perilaku yang disengaja; dokumen yang sudah terbit tidak ditulis ulang.

### Tes

- `tests/Feature/RevisionTypeBTest.php:64` `test_revision_rolls_over_to_next_edition_at_five` → **diperbarui** jadi `..._at_six` dengan asersi 4→5 (masih edisi sama) dan 5→Edisi+1 Rev 0.
- +1 tes unit: `nextEditionRevision` dipanggil untuk 0..6 dan hasilnya dicocokkan tabel — supaya ambangnya tak bisa bergeser diam-diam.
- Periksa `PenomoranBabOpsionalTest`, `RevisionLogTest`, `CancelRevisionBTest`, `RollbackVersiTest`, `ArsipCatatanRevisiTest`, `InformasiTest` — perbarui angka bila ada yang memaku 4.

---

## Fase 2 — Butir 7: cabut "gabung", salin manual → auto-approve

**Keadaan sekarang.** `DocumentArsipController::simpanCatatan()` (`app/Http/Controllers/DocumentArsipController.php:125-176`) bercabang dua: `gabung` (lembar revisi disisipkan ke PDF unggahan lewat `ArsipPenggabung`) dan `salin` (`DocumentService::requestRevision()` → draft → wizard). Jalur `salin` kemudian **masuk alur review penuh**: `tandaiTerkirim()` → `waiting_for_review` → tinjau → setujui.

**Yang diminta.** Hanya `salin` yang tersisa, dan **sesudah salinan dikirim dokumen langsung Berlaku** tanpa peninjauan.

### 2a. Cabut pilihan "gabung" (K-E)

| BerkasPerubahan                                            |                                                                                                                                                                                                                                                                                   |
| ---------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `resources/js/pages/V2/Documents/Arsip/Catatan.tsx`        | buang `RadioGroup` (`:191-221`), dua `Field` `potong_halaman` / `halaman_awal` (`:133-169`), dan kunci-kunci itu dari `useForm`. Tombol jadi **"Simpan & Salin ke Web"**. `ConfirmDialog` tinggal satu pesan. Panel kanan (iframe PDF) **tetap** — ia rujukan saat mengetik ulang |
| `app/Http/Controllers/DocumentArsipController.php:125-176` | buang aturan `pilihan`, `potong_halaman`, `halaman_awal` dan seluruh cabang `gabung` beserta `try/catch` `DomainException`. `persistRevisionLog()` tetap dipanggil lebih dulu. Buang `ArsipPenggabung` dari `use` + parameter method — **berkasnya sendiri tidak dihapus**        |
| `app/Http/Controllers/DocumentArsipController.php:112`     | prop `jumlahHalaman` jadi tak dipakai halaman → dicabut dari `Inertia::render`                                                                                                                                                                                                    |
| `resources/js/types/distribusi.d.ts`                       | `ArsipCatatanProps` menyusut mengikuti props di atas                                                                                                                                                                                                                              |

> `ArsipPenggabung.php` ada di daftar HARAM (§14b) — **nol byte disentuh**. Ia cuma kehilangan pemanggilnya. `versiKodeCetak()` karena itu tidak berubah, dan `smartpro:cetak-baseline` tetap hijau di fase ini.
>
> Kembaran V1 `pages/Documents/Arsip/Catatan.tsx` **tidak** ikut disunting (K-A): pohon V1 dibekukan. Kalau seseorang mematikan sakelar V2, halaman V1-nya akan mengirim `pilihan`/`potong_halaman` yang server tak lagi kenal → server harus **mengabaikan** kunci asing, bukan 422. Diperiksa lewat satu tes.

### 2b. Auto-approve sesudah salinan dikirim

**Penanda.** Draft hasil salin harus bisa dibedakan dari revisi Tipe B biasa. Deteksi "induknya dokumen arsip" **tidak cukup**: revisi biasa atas dokumen unggahan juga memenuhi syarat itu, dan revisi biasa tetap harus ditinjau.

→ **Migration aditif**: `documents.salin_arsip_at` (`timestamp`, nullable). Diisi hanya oleh `simpanCatatan()` saat draft dibuat. Kolom, bukan bendera turunan, karena ia menyatakan **asal-usul** draft — fakta yang tak bisa dihitung ulang dari keadaan lain.

**Titik cabang.** `DocumentService::tandaiTerkirim()` (`app/Services/DocumentService.php:294-323`) — satu-satunya pintu "kirim", jadi tak ada jalur kedua yang bisa lolos.

```php
if ($document->salin_arsip_at) {
    return $this->sahkanSalinanArsip($document);   // BUKAN waiting_for_review
}

```

**`sahkanSalinanArsip()`**** (method baru, satu transaksi)** wajib mengerjakan SELURUH pekerjaan yang biasanya dikerjakan `ApprovalController::store()`, kalau tidak dokumen unggahan lamanya macet:

1. `status = 'published'`, `published_at = now()`, `doc_number_final = $document->doc_number` (nomor sudah diwarisi dari arsip).
2. Versi lama (`revises_document_id`, berstatus `sedang_direvisi`) → `obsolete` + `obsolete_reason = Document::OBSOLETE_REVISI`. **Tanpa langkah ini dokumen arsip asli terkunci selamanya** — tak bisa disunting, tak muncul di Berlaku maupun Tidak Berlaku.
3. Baris `document_versions` sudah dibuat `requestRevision()` — tidak diulang.
4. Audit: aksi baru `document.arsip_salin_sah` **atau** pakai ulang `document.approve`. → **Pakai ****`document.approve`** supaya kartu KPI "Berlaku" dan sparkline dasbor tetap menghitungnya (DASBOR-V2-REVISI §P9: nama aksi wajib diverifikasi ke DB sebelum dipetakan). Tambahkan `meta.sumber = 'salin_arsip'`.
5. Notifikasi: pakai jalur `kabarkanTerbit()` yang sudah ada (`ApprovalController.php:272`) — dipindah ke service atau dipanggil dari sana.

**Nomor final aman**: `createArsip()` sudah mengisi `doc_number_final` (`DocumentService.php:104`) dan `requestRevision()` mewarisi `doc_number` (`:170`) — jadi tak ada pembangkitan nomor baru, tak ada risiko bentrok.

### Tes (+4)

1. Draft hasil salin, saat dikirim → `published`, `published_at` terisi, `doc_number_final` == nomor arsip aslinya.
2. Dokumen arsip asli → `obsolete` dengan `obsolete_reason` yang benar.
3. Revisi Tipe B **biasa** (bukan salin) atas dokumen arsip → tetap `waiting_for_review`. Ini pengunci terpenting: ia yang membuktikan auto-approve tak bocor ke jalur lain.
4. `simpanCatatan()` mengabaikan kunci asing (`pilihan`, `potong_halaman`) tanpa 422.

---

## Fase 3 — Butir 1: AI berfungsi

**Diagnosis (sudah dijalankan, bukan dugaan).** Kodenya **lengkap end-to-end** — `AiReviewerInterface` → `AbstractAiReviewer` → `GeminiReviewer` / `OpenRouterReviewer` (keduanya HTTP nyata) → `FallbackReviewer` → binding dinamis di `AppServiceProvider.php:42-93` → dua controller → dua rute → komponen `v2/tinjau/PanelAi.tsx` → layar admin + tombol Uji Koneksi → 31 tes. **Tak ada yang perlu ditulis dari nol.**

Yang rusak ada di **data**, dan sudah terbaca dari tabel `pengaturan` (DB `smartpro_shadcn`):

| kuncinilaiakibat        |                                          |                                                                               |
| ----------------------- | ---------------------------------------- | ----------------------------------------------------------------------------- |
| `ai.provider`           | `openrouter`                             | —                                                                             |
| `ai.model`              | `nvidia/nemotron-3-ultra-550b-a55b:free` | —                                                                             |
| **`ai.key`**            | **barisnya TIDAK ADA**                   | `buatReviewer()` mengembalikan `NullReviewer` → penyedia UTAMA mati diam-diam |
| `ai.cadangan.provider`  | `openrouter`                             | —                                                                             |
| `ai.cadangan.key`       | terisi                                   | —                                                                             |
| **`ai.cadangan.model`** | **`chatgptnew`**                         | bukan id model OpenRouter yang sah → HTTP 400 dari penyedia                   |

Jadi rantainya: utama dilewati (kunci kosong), cadangan dipanggil dengan nama model karangan → gagal. Hasil yang dilihat peninjau: ringkasan "lanjutkan tinjauan manual", tanpa satu pun temuan. Persis "AI tidak berfungsi".

`.env` juga tak memuat `OPENROUTER_API_KEY`/`GEMINI_API_KEY`, jadi cadangan `config()` pun kosong.

### 3a. Perbaiki konfigurasi (bukan kode)

1. Isi **`ai.key`** lewat Admin → Konfigurasi Sistem (tersandi `APP_KEY`; `Pengaturan::ambilRahasia`). **Jangan lewat ****`.env`** — kunci di `.env` ikut terbawa `config:cache` dan tak bisa dirotasi dari layar.
2. Ganti `ai.model` dan `ai.cadangan.model` dengan id model OpenRouter yang **sah dan cepat** (non-reasoning). `AbstractAiReviewer::TIMEOUT_DETIK = 180` dan analisis berjalan **sinkron** dari peramban — model lambat = 504 di shared hosting (RUNBOOK §9.1).
3. Buktikan lewat tombol **"Uji koneksi AI"** (`PengaturanController::ujiAi`, timeout 15 dtk, melaporkan **per penyedia** lewat `FallbackReviewer::daftar()`).

### 3b. Pengerasan kode — supaya kegagalan yang sama tak diam lagi

| PerbaikanBerkasAlasan                                                                                         |                                                                                                                                   |                                                                                                                                               |
| ------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| Bedakan **"AI dimatikan"** dari **"kunci belum diisi"**                                                       | `AppServiceProvider::buatReviewer()` + `NullReviewer` (beri alasan) + `PanelAi.tsx`                                               | Sekarang keduanya menghasilkan pesan yang sama persis. Itulah sebabnya kunci kosong bisa bertahan berhari-hari tanpa ada yang sadar           |
| Kartu **Kesehatan** di Konfigurasi Sistem menyebut status tiap penyedia (aktif / kunci kosong / model kosong) | `PengaturanController::kesehatan()` + `pages/V2/Pengaturan/Sistem.tsx`                                                            | Layar admin sudah punya rumahnya; nol layar baru                                                                                              |
| `ReviewController` menghormati `users.ai_review_enabled`                                                      | `app/Http/Controllers/ReviewController.php:124` (prop `aiUrl`) dan `:223` (`aiAnalyze`)                                           | Sekarang toggle per-akun itu hanya ditegakkan di jalur MD (`MdReviewController.php:84,153`). Satu izin, dua perilaku                          |
| **Rotasi kunci yang bocor**                                                                                   | `.env.cadangan-uji` ter-*track* di git sejak `a00eaa5`, memuat `APP_KEY` + `GEMINI_API_KEY` + `OPENROUTER_API_KEY` (utang **U1**) | **Wajib sebelum repo dibagikan lebih luas.** Butuh keputusan pemilik: rotasi kunci + `git rm --cached`, atau tetap ditahan selama repo privat |

### Yang TIDAK diubah

`AbstractAiReviewer` (prompt, rubrik `docs/ai/jenis/*.md`, whitelist `section_key`, kontrak JSON), penanganan galat yang tak pernah melempar ke pengguna, `temperature`, `max_tokens`. Rencana `docs/PLAN-BUTIR-17-AI.md` (rubrik per jenis) **sudah dikerjakan** — keempat berkas rubriknya ada.

### Tes (+3)

- `NullReviewer` beralasan: "dimatikan" vs "kunci kosong" menghasilkan pesan berbeda.
- `aiUrl` **null** bagi SH/DH yang `ai_review_enabled = false`.
- `aiAnalyze` menolak 403 bagi akun yang sama.

---

## Fase 4 — Butir 6: pisah nomor & judul lampiran

**Keadaan sekarang.** Bab **VII. LAMPIRAN** SOP adalah `repeatable_group` berisi dua kolom: `dokumen` (`document_picker`) + `keterangan` (`textarea`). `DocumentPicker` menulis **satu baris teks gabungan** `"PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja Perawatan"` ke kolom `dokumen` (`resources/js/components/v2/dokumen/fields/DocumentPicker.tsx:76,100,161`).

**Yang diminta.** Nomor masuk **kotak utama** (`dokumen`), judul turun ke **kotak teks di bawahnya** (`keterangan`).

### Cara — lewat schema, bukan hardcode (CLAUDE.md §3/§7)

1. **Kunci schema baru** pada field picker:
   ```json
   {"key":"dokumen","type":"document_picker","judul_ke":"keterangan", …}

   ```
   `judul_ke` menyebut kolom SAUDARA yang menampung judul. Tanpa kunci ini perilaku lama dipertahankan apa adanya (dokumen lama, jenis lain).
2. `DocumentPicker` mendapat prop opsional `onPilihJudul?: (judul: string) => void`. Saat sebuah baris daftar dipilih (klik atau Enter): `onChange(o.nomor)` + `onPilihJudul?.(o.judul)`. **Ketikan bebas tetap utuh** — hanya PEMILIHAN dari daftar yang memecah, karena hanya di situ nomor dan judulnya memang sudah terpisah (`DokumenPilihan { jenis, nomor, judul }`).
3. `RepeatableGroup.tsx:241` (cabang `document_picker`) menyambungkan `onPilihJudul` ke `field.judul_ke` pada baris yang sama.
4. **SQL** memperbarui `document_types.schema_json` SOP (satu kunci) **dan** `database/seeders/DocumentTypeSeeder.php:273-293` supaya pemasangan baru sama. Migration data tidak dibuat: skema hidup di DB, dan `SchemaService` membacanya apa adanya.

### Konsekuensi cetak — nol perubahan kode cetak

`render.blade.php:353-378` sudah membaca `$judul = $row['judul'] ?? ($row['dokumen'] ?? '')` dan `$ket = $row['keterangan'] ?? …`. Setelah perubahan, kotak utama berisi nomor dan `keterangan` berisi judul — keduanya tetap tercetak, hanya isinya yang berpindah. **Dokumen lama tidak ditulis ulang**; barisnya tetap tampil gabungan. Mesin cetak nol byte tersentuh, `smartpro:cetak-baseline` tetap hijau.

### Tes (+2)

- Pakem **P1**: menyisipkan `judul_ke` ke `schema_json` lewat SQL harus mengubah perilaku form **tanpa satu baris kode disentuh** — dan tanpa `judul_ke` form tetap berperilaku lama.
- Nilai tersimpan sesudah pemilihan: `dokumen` == nomor saja, `keterangan` == judul saja.

---

## Fase 5 — Butir 4: modal nomor bentrok

**Keadaan sekarang.** Nomor manual dokumen lama sudah **bertahan sampai Berlaku** — `createArsip()` mengisi `doc_number` **dan** `doc_number_final` sekaligus (`DocumentService.php:103-104`), dan untuk dokumen wizard `ApprovalController.php:122-126` mewarisi `doc_number` bila `doc_number_manual`. Jadi separuh permintaan butir 4 **sudah terpenuhi**.

Yang belum: saat nomornya **sudah dipakai**, yang muncul cuma galat merah inline (`ArsipDocumentRequest.php:81`, `StoreDocumentRequest.php:45`).

### Perubahan

1. **Server mengirim saran.** Saat validasi nomor gagal, sertakan `nomorSaran` = `DocumentNumberService::generateFinal($type, $department)` (nomor urut bebas terkecil — mengisi celah, bukan `count()+1`). Dikirim lewat `->with('nomorSaran', …)` / prop, **bukan** dihitung di TSX.
2. **Satu komponen dialog** `components/v2/dokumen/DialogNomorBentrok.tsx` (baru), dipakai dua halaman (K-F):
   - `pages/V2/Documents/Create.tsx` — mode dokumen lama **dan** sakelar "Input manual" (`:193-231` pada kembaran V1; padanan V2)
   - Isi: judul "Nomor Sudah Dipakai", kalimat yang menyebut nomor bentroknya, dua tombol — **"Ketik nomor lain"** (tutup dialog, fokus ke kotak nomor) dan **"Pakai nomor otomatis (…-07)"** (matikan sakelar manual, isikan `nomorSaran`, kirim ulang).
   - Komponen dari `ui-maia/alert-dialog`; nol komponen registry baru.
3. **Nol perubahan aturan keunikan.** `isUnique()` (`DocumentNumberService.php:173-181`) dan penjaga bentrok saat pengesahan (`ApprovalController.php:142-147`) tetap apa adanya — dialog ini murni lapisan tampilan di atas galat yang sudah ada.

### Catatan pengerasan (dicatat, di luar lingkup fase ini)

`doc_number` dan `doc_number_final` **tidak punya UNIQUE index** di DB (migration `create_documents_table.php:40-41` hanya `index()`). Keunikannya ditegakkan murni aplikasi (`isUnique()` + `lockForUpdate()`). Menambah UNIQUE menuntut audit duplikat lebih dulu (terutama sesudah impor data produksi 28 Agu) — **ditandai sebagai kandidat pengerasan pasca-rilis**, bukan dikerjakan sekarang.

### Tes (+2)

- Nomor bentrok pada pendaftaran arsip → galat `doc_number` **dan** prop `nomorSaran` terkirim, dan saran itu benar-benar bebas.
- "Pakai nomor otomatis" menghasilkan dokumen yang lolos, bernomor `nomorSaran`, dengan `doc_number_manual = false`.

---

## Fase 6 — Butir 13: filter departemen di Status Dokumen Staff

**Terkecil dari keempat belas.** Server **sudah** menerima `department_id` (`DocumentStaffStatusController.php:34`) dan **sudah** mengirim `departments` (`:76`); tipe `dokumen.d.ts:210` sudah mendeklarasikannya; helper `saringDepartemen()` sudah ada dan dipakai **delapan** halaman V2 lain. Hanya `StaffStatus.tsx` yang menerima `departments` tapi tak memakainya — departemen di sana dipilih dari **sub-menu sidebar** dan cuma dititipkan lewat `tersembunyi`.

### Perubahan (satu berkas)

`resources/js/pages/V2/Documents/StaffStatus.tsx`:

```tsx
pilihan={[
    ...(departments.length > 0 ? [saringDepartemen(departments)] : []),
    saringJenis(types), saringStatus(statusOpsi, statusLabels),
]}
tersembunyi={{}}

```

Pola `departments.length > 0 ? … : []` disalin dari `Documents/Index.tsx:107` — daftar kosong dari server = penyaring tak tampil, jadi keputusan peran tetap milik server.

**Sub-menu sidebar per departemen (****`NavigasiSidebar.php:292-309`****) DIPERTAHANKAN.** Keduanya menulis ke query string yang sama (`?department_id=`), jadi tak ada keadaan yang bisa berselisih: sidebar = pintasan, penyaring = pengubah. Menghapus salah satunya perubahan menu, dan menu dijaga `NavigasiSidebarTest`.

### Tes (+1)

Sebagai PJO, `?department_id=` menyaring dan pilihan itu bertahan saat penyaring lain diubah.

---

## Fase 7 — Butir 8: pemilik dokumen di widget Masukan Lapangan

**Keadaan sekarang.** `DashboardController::masukanBaris()` (`:616-648`) mengirim `pengirim` = nama **pengirim masukan**. Pemilik/pembuat dokumennya tidak ikut.

### Perubahan

1. `masukanQuery()`/`masukanWidget()` menambah eager-load `document.creator` (`:564` sudah `with('document','user')`).
2. `masukanBaris()` menambah **satu kunci**:
   ```php
   'pemilik' => $m->document?->creator?->name ?? '—',

   ```
   **String, bukan relasi.** Mengirim `creator` sebagai model persis kebocoran yang sudah terjadi di Fase 8 migrasi dan dikunci `DasborV2Test` #2/#3.
3. `resources/js/types/dasbor.d.ts` — `BarisMasukan` bertambah `pemilik: string`.
4. `components/v2/dasbor/KartuMasukan.tsx` — baris meta tiap kartu jadi `{pengirim} → {pemilik}` (mis. "Dari Budi · Pemilik: Rina A."). Lencana status yang sudah ada (`status` / `statusKunci` / `rona`) **tetap** — itulah yang menyatakan sudah dibaca atau belum.

> **Catatan jujur tentang "dia telah membacanya".** Status `dibaca` diset di `DocumentController.php:346-347` ketika **orang sedepartemen yang berwenang menindak** membuka dokumennya — bukan khusus pemiliknya. Jadi lencana "Sudah dibaca" berarti "sudah dilihat pihak departemen pemilik", dan itulah yang akan ditulis di tooltip-nya. Melacak "pemilik sendiri yang membuka" menuntut kolom baru; **tidak dikerjakan kecuali diminta.**

### Tes (+2)

- `pemilik` ada di props dan berupa **string**.
- HTML **MENTAH** dasbor tak memuat `email`/`password`/`nrp` pembuat — perluasan `DasborV2Test` #3, bukan tes baru yang terpisah.

---

## Fase 8 — Butir 9: warna & tata letak Sebaran per Departemen

**Keadaan sekarang.** `components/v2/dasbor/PitaDepartemen.tsx` memakai **satu ramp ****`--primary`** dengan opasitas menurut peringkat, dan legendanya `grid-cols-2 @2xl/card:grid-cols-3` → 7 departemen jadi 3/3/1, satu kotak yatim.

### 8a. Warna berbeda per departemen

Pemilik meminta warna berbeda antar departemen, "tetap ikuti konsep warna shadcn". → memakai **token grafik shadcn** `--chart-1` … `--chart-5` yang sudah ada di `resources/css/app.css`, ditambah dua token turunan (`--chart-6`, `--chart-7`) yang didefinisikan di `:root` **dan** di kedua blok gelap, mengikuti aturan tema (CLAUDE.md §13). **Nol hex diketik di TSX.**

> **Ini mencabut §P5 DASBOR-V2-REVISI**, yang melarang palet kategorikal karena palet karangan sebelumnya gagal ambang keterbacaan (ΔE 4.1 vs ambang 12). Pencabutannya sah karena permintaan pemilik eksplisit **dan** karena token `--chart-*` adalah palet **registry**, bukan karangan. Tetap wajib: jalankan validator kontras `docs/REDESAIN-UI-V2.md` §6 atas ketujuh warna sebelum fase ditutup, dan tulis hasilnya di komentar berkas. Kalau ada pasangan yang gagal, yang diubah **urutan pemakaiannya**, bukan ambangnya.

Warna ditetapkan per **kode departemen** (peta stabil), bukan per peringkat — kalau warnanya ikut peringkat, satu departemen berganti warna tiap hari dan legendanya berhenti berarti apa-apa.

### 8b. Legenda 3 atas / 4 bawah (K-G)

Urutan **tetap dari server** (menurun menurut jumlah, `DashboardController.php:946`). Yang berubah hanya gridnya: `grid-cols-12` dengan tiga kotak pertama `col-span-4` dan empat sisanya `col-span-3`. Jumlah departemen datang dari tabel, jadi pemecahannya dihitung — bukan angka 3 dan 4 yang diketik: bila jumlahnya bukan 7, jatuh kembali ke grid rata seperti sekarang.

### Tes (+1)

Peta warna memuat kunci untuk **setiap** departemen di tabel — bila departemen kedelapan lahir, tesnya merah, bukan warnanya diam-diam kosong.

---

## Fase 9 — Butir 12: pratinjau PDF di halaman Persetujuan PJO

**Keadaan sekarang.** `pages/V2/Approvals/Show.tsx` = dua kolom: "Rantai Dokumen" (`lg:col-span-7`, `<dl>` empat baris) dan "Keputusan" (`lg:col-span-5`). Pratinjau dokumen **tidak ada** — hanya tautan "Lihat PDF" `target="_blank"` (`:67-74`).

### Tata letak baru

```
┌──────────────────────────┬─────────────────────────────┐
│ Rantai Dokumen  (5/12)   │  Pratinjau PDF   (7/12)     │
│  Dibuat/Ditinjau/Dept/   │  <iframe> sticky, h-[78vh]  │
│  Edisi·Revisi            │                             │
├──────────────────────────┤                             │
│ Keputusan       (5/12)   │                             │
│  Catatan/Alasan          │                             │
│  [Setujui] [Kembalikan]  │                             │
└──────────────────────────┴─────────────────────────────┘

```

Kolom kiri jadi satu tumpukan (Rantai di atas, Keputusan **di bawahnya**, sesuai permintaan); kolom kanan `lg:sticky lg:top-4` berisi iframe.

**Pola yang dipakai ulang, bukan digambar ulang** — `pages/V2/Documents/Arsip/Catatan.tsx:246-279` (Card + CardHeader tombol "Buka"

- `CardContent h-[78vh] p-0` + `<iframe src={route('documents.pdf', id)}#toolbar=1&navpanes=0&view=Fit>`). Enam baris, nol komponen baru.

### Dua hal yang harus disebut

1. **Efek samping unduhan.** `DocumentPdfController::pdf()` (`:58`) memanggil `DocumentDistribution::catat($document, $user, unduh: true)` pada **setiap** hit. Dengan iframe, membuka halaman persetujuan akan menaikkan angka unduhan PJO. → tambahkan parameter agar pratinjau **tidak** dihitung sebagai unduhan, atau catat sebagai "dilihat" saja. Keputusan: **catat sebagai dilihat, bukan unduh** — angka distribusi dipakai untuk kepatuhan, dan menggelembungkannya dengan pembukaan halaman membuat angkanya tak bisa dipercaya. `DocumentPdfController` **tidak** ada di daftar HARAM §14b, jadi boleh disentuh; `PdfRenderer`/`PdfStream`/`ArsipPdf` **tidak** disentuh.
2. Tombol "Lihat PDF" di `aksi` layout **tetap** — iframe di dalam halaman tak menggantikan kebutuhan membuka PDF penuh di tab baru.

### Tes (+1)

Membuka `approvals.show` **tidak** menambah baris distribusi ber-`unduh = true`.

---

## Fase 10 — Butir 14: meter Tertinjau per peran

**Keadaan sekarang.** `MeterTertinjau` menampilkan `deret.growth` = `tertinjau / total * 100`, di mana `tertinjau` dihitung dari daftar status `$lolosTinjau` (`DashboardController.php:116`) dan **judulnya di-hardcode "Tertinjau" di TSX** (`v2/dasbor/MeterTertinjau.tsx:44,54,92`).

### Rumus (K-C)

Penyebut **tetap sama** untuk semua peran: jumlah dokumen yang **dibuat** pada periode terpilih, dalam lingkup hak akses pembaca (`$visible()`). Pembilangnya berbeda:

| PeranLabelPembilang = dokumen yang sudah… |               |                                                                    |
| ----------------------------------------- | ------------- | ------------------------------------------------------------------ |
| GL (`isCreator`)                          | **Dibuat**    | keluar dari `draft` (status ∈ `waiting_for_review` dan seterusnya) |
| SH / DH / MD                              | **Tertinjau** | lewat tinjauan (`$lolosTinjau` — persis seperti sekarang)          |
| PJO                                       | **Disetujui** | `published`, `sedang_direvisi`, `menunggu_nonaktif`, `obsolete`    |
| Admin & Non-Staff                         | **Tertinjau** | seperti SH (lensa pemantau)                                        |

Ketiga himpunan itu bersarang, jadi angkanya selalu ≤ 100% dan turun berurutan — satu tangga, tiap peran membaca anak tangganya sendiri.

### Perubahan

1. `DashboardController` — closure `$deret()` (`:118-135`) menerima himpunan status pembilang sebagai parameter; himpunan dipilih **sekali** di atas, dari bendera peran yang sudah dihitung (`$isCreator`/`$isPjo`/…).
2. Tiap deret (`hari`/`minggu`/`bulan`) bertambah **satu kunci**: `meterLabel` (mis. `"Disetujui"`). Aditif — V1 mengabaikannya, sesuai §P2.
3. `resources/js/types/dasbor.d.ts` — `DeretTren` bertambah `meterLabel: string`.
4. `components/v2/dasbor/MeterTertinjau.tsx` — ketiga tempat "Tertinjau" yang di-hardcode dibaca dari `deret.meterLabel`; kalimat kaki ("N dari M dokumen sudah melewati tinjauan") juga datang dari server sebagai `meterKeterangan`. **Nol cabang jabatan di TSX** (CLAUDE.md §4).

### Tes (+2)

- GL, SH, PJO mendapat `meterLabel` yang berbeda, dan `growth` PJO ≤ `growth` SH ≤ `growth` GL pada data yang sama.
- `meterLabel` ikut di ketiga durasi (`hari`/`minggu`/`bulan`) — kalau hanya satu yang diisi, mengganti durasi akan mengosongkan judul kartunya.

---

## Fase 11 — Butir 10 & 11: rincian aktivitas Performa PIC

**Keadaan sekarang.** Tiap wajah membawa **hanya** `{nama, foto, jumlah}` (`DashboardController::wajahProduktif()` `:834-879`), dan `jumlah` adalah satu angka gabungan dari `DasborTampilan::KUNCI_PRODUKTIF = ['diloloskan','md','berlaku']`. Aksi `dibuat` **sengaja** tidak ikut. Di kartu, angkanya cuma muncul sebagai `ket` di HoverCard (`PerformaPic.tsx:49-58`).

### Perubahan (K-H)

1. **`wajahProduktif()`**** menambah ****`rincian`** — satu kunci baru per wajah:
   ```php
   'rincian' => ['dibuat' => 3, 'ditinjau' => 10, 'diperiksa' => 0, 'disetujui' => 2],

   ```
   Dihitung dari **sapuan ****`audit_logs`**** yang SAMA** (`alirAudit()` `:681-753`), cukup ditambah pengelompokan `action` di samping `user_id`. **Nol query baru.** Peta aksi → kunci rincian datang dari `DasborTampilan::AKSI_ALIR`, bukan daftar kedua (§P9: nama aksi wajib diverifikasi ke DB dulu).
2. **`jumlah`**** dan urutan wajah TIDAK berubah** — `dibuat` ikut di `rincian` tapi tidak ikut menghitung peringkat. Kartunya karena itu tetap bukan papan peringkat, dan butir 11 (berapa dokumen dibuat GL) tetap terjawab.
3. **Kalimat rincian dirakit SERVER**, dikirim sebagai `ket`: `"meninjau 10 · menyetujui 2"`, `"membuat 10"`. Merakitnya di TSX berarti menyalin kosakata alur ke klien.
4. `types/dasbor.d.ts` — `WajahPic` bertambah `rincian` + `ket`.
5. `components/v2/dasbor/PerformaPic.tsx` — di bawah tumpukan wajah, tambahkan daftar ringkas maksimal 5 baris (avatar kecil + nama + kalimat rincian), memakai `ui-maia/item`. Tumpukan wajahnya **tetap** — ia yang memberi kesan "berapa banyak orang"; daftarnya yang memberi "siapa mengerjakan apa".
6. **Perbaiki komentar usang** `PerformaPic.tsx:28-30` (masih menyebut gerbang `dashboardPenuh()`/K1 yang dicabut 2026-09-01) dan `DASBOR-V2-REVISI.md:588,603`.

### Butir 11 (dasbor SH) tidak butuh kode tambahan

`lingkupPic()` (`:785-806`) sudah memberi SH **GL di departemennya sendiri**. Dengan `rincian.dibuat` terkirim, "berapa dokumen yang dibuat GL di departemen tsb" langsung terbaca. Yang perlu dipastikan: aksi `document.create` memang masuk `AKSI_ALIR['dibuat']` dan tersapu `aksiDisapu()` — **verifikasi ke DB lebih dulu** (§P9).

### Kebocoran (§P4)

`rincian` hanya berisi **integer**; `ket` hanya **string**. Tidak ada `id`, `nrp`, `email`, `jabatan`. `DasborV2Test` #8 (`array_keys` persis `nama/foto/jumlah`) **diperbarui** jadi `nama/foto/jumlah/rincian/ket` — dan tetap memakai `assertSame` atas daftar kunci, bukan `assertArrayHasKey`, supaya kunci baru tak bisa menyelinap.

### Tes (+2)

- Bentuk `rincian` (empat kunci, semuanya integer) dan `ket` (string).
- Urutan wajah **tidak berubah** meski seorang GL membuat banyak dokumen — pengunci bahwa `dibuat` tak menyusup ke peringkat.

---

## Fase 12 — Butir 5: sub-bab tak boleh yatim (cetak)

> **Fase paling berisiko.** Ia satu-satunya yang menyentuh daftar HARAM §14b, dan satu-satunya yang mengubah keluaran PDF. Dikerjakan **paling akhir dari pekerjaan kode** (K-D).

**Yang SUDAH ada.** Aturan **"judul BAB tak pernah yatim"** sudah berdiri — tiga aturan CSS di `render.blade.php:49-68` (`page-break-after: avoid` pada `.section-bar`, `page-break-before: avoid` pada tabel, `tr.keep-next`) dan dikunci `PrintLayoutTest.php:526-584` (bilah bab wajib sehalaman dengan **dua** poin pertamanya).

**Yang BELUM ada.** Aturan **"sub-bab ditemani ≥1 baris"** — yaitu baris `isHead` grup aktivitas (`6.1 Tahap Persiapan`) wajib sehalaman dengan setidaknya satu baris deskripsinya. `ActivityPrintLayout::plan()` menerima `$pageStarts` **apa adanya** dari hasil ukur; kalau titik potong jatuh tepat sesudah baris `isHead`, tak ada yang mencegahnya.

### Rancangan

**Titik intervensi = ****`$starts`****, bukan CSS.** CSS `keep-next` hanya dipasang di baris pertama tabel (`render.blade.php:335`), dan DomPDF tak mendukung `widows`/`orphans` sama sekali.

1. **Method baru murni** `ActivityPrintLayout::hindariYatim(array $rows, array $starts): array` — untuk tiap awal-halaman `s`, bila `$rows[$s-1]['isHead']` **dan** `$rows[$s]['groupIdx'] === $rows[$s-1]['groupIdx']`, geser awal halaman mundur satu (`s-1`), sehingga judul sub-bab ikut turun bersama baris pertamanya. Kelas murni → **bisa diuji unit tanpa merender PDF**.
2. **Iterasi terukur di ****`PdfRenderer::renderStandardPaginated()`**** (****`:433-467`****).** Menggeser satu baris turun membuat halaman berikutnya bertambah satu baris — yang bisa meluber. Jadi: ukur → `hindariYatim()` → render probe lagi dengan `$starts` yang dipaksa → ukur ulang → berhenti saat stabil, **maksimal 2 iterasi tambahan**, lalu jatuh ke hasil terakhir. Pola ini **sudah ada preseden**-nya di `renderJsaPaginated()` (`:589-614`, loop ≤4 iterasi sampai konvergen) — jadi bukan mekanisme karangan baru.
   > `ponytail:` batas 2 iterasi; naikkan hanya bila ada dokumen nyata yang terbukti belum konvergen.
3. **Kasus tepi**: grup yang seluruhnya tak muat di sisa halaman (judul + satu baris > tinggi tersisa) tetap dipindahkan utuh — geseran mundur berulang tidak boleh mengosongkan halaman. Bila `s-1` sama dengan awal halaman sebelumnya, geseran **dibatalkan**.

### Dampak yang PASTI terjadi

| GerbangAkibat                         |                                                                                                                     |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `md5sum -c mesin-cetak.md5`           | **MERAH** — `ActivityPrintLayout.php` + `PdfRenderer.php` berubah                                                   |
| `php artisan smartpro:cetak-baseline` | **MERAH** untuk dokumen SOP/SP/IK yang tata letaknya bergeser                                                       |
| Cache PDF                             | **seluruhnya ter-invalidasi** — `versiKodeCetak()` membaca `filemtime` kedua berkas itu (`PdfRenderer.php:161-181`) |
| `tests/Feature/PrintLayoutTest.php`   | 18 tes membaca stream PDF nyata; sebagian akan bergeser dan **wajib diperbarui, bukan dilewati**                    |

### Urutan kerja yang mengikat (K-D)

1. Tulis `hindariYatim()` + tes unit murni (nol render).
2. Sambungkan ke `renderStandardPaginated()`.
3. Render **sebelum/sesudah** untuk ketiga dokumen baseline SOP/SP/IK (id 1177/1178/1181, 1224, 1225) → serahkan ke pemilik untuk diperiksa mata.
4. **Tunggu persetujuan.** Baru kemudian:
   ```
   php artisan smartpro:cetak-baseline --tulis
   md5sum <23 berkas> > C:/baseline-smartpro/mesin-cetak.md5

   ```
   dan catat alasan bless-nya di `C:\baseline-smartpro\CATATAN.txt`.
5. +1 tes integrasi di `PrintLayoutTest`: `test_sub_judul_aktivitas_tak_pernah_yatim` — bangun dokumen dengan panjang yang memaksa potongan tepat sesudah judul sub-bab, buktikan judul dan baris pertamanya sehalaman.

### Yang TETAP tak disentuh

`JsaPrintLayout`, `render-jsa.blade.php`, `_cover`, `_kop*`, `_footer`, `_pengesahan`, `arsip-catatan`, `ArsipPdf`, `ArsipPenggabung`, `PdfStream`, `DocumentExportController`, keempat `template_*.blade.php`. JSA dan dokumen arsip **tidak** berubah keluarannya.

> **Dokumen rujukan yang hilang.** `CLAUDE.md:148-150` menunjuk `docs/COVER-CETAK-KONFIGURASI.md`, `docs/JSA-CETAK-KONFIGURASI.md`, dan `docs/HANDOVER-SESI-BARU.md` — **ketiganya tidak ada di repo maupun di riwayat git.** Sumber kebenaran yang benar-benar ada: komentar inline `render.blade.php:48-178` dan `PrintLayoutTest.php`. Perbaiki rujukan `CLAUDE.md` di fase ini.

---

## Fase 13 — Butir 2: verifikasi email, mobile, cron + gerbang pra-produksi

Butir 2 **bukan pekerjaan kode** — ia verifikasi bahwa yang sudah ada tetap bekerja setelah dua belas fase di atas. Karena itu ia terakhir.

### 13a. Cron — TIDAK ADA yang baru

**Tidak ada scheduler Laravel sama sekali** di proyek ini: tak ada `app/Console/Kernel.php`, `routes/console.php` cuma `inspire`, dan `bootstrap/app.php` tak memanggil `->withSchedule()`. Jangan mencari `schedule:run`. Kedua cron produksi adalah **cron OS murni** (`docs/RUNBOOK-RILIS.md:238-247`) dan **tetap sama persis**:

```
* * * * *  cd <root> && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> storage/logs/queue.log 2>&1
0 3 * * *  cd <root> && /usr/bin/php artisan queue:prune-failed --hours=168

```

Baris pertama **wajib**: notifikasi email dikirim lewat antrean (`DocumentNotification::viaConnections()` = `['database' => 'sync', 'mail' => 'database']`). Tanpa pekerja, email **mengendap di tabel ****`jobs`**** tanpa satu pun pesan salah** sementara lonceng tetap muncul — kegagalan separuh yang paling sulit disadari. Diagnosa pertama selalu `SELECT COUNT(*) FROM jobs`, bukan konfigurasi SMTP.

Yang berubah di rilis ini hanya kewajiban **`php artisan queue:restart`** (RUNBOOK langkah 6).

### 13b. Email

Periksa `.env` produksi (RUNBOOK §4): `MAIL_MAILER=failover` · `MAIL_FROM_ADDRESS` == `MAIL_USERNAME` · **`MAIL_TIMEOUT=15`**** (wajib < ****`DB_QUEUE_RETRY_AFTER=90`****, kalau tidak email terkirim dua kali)** · `MAIL_PAKSA_KE` dikosongkan **dengan sadar** · `QUEUE_CONNECTION=database`. Lalu tombol **"Kirim email uji"** di Konfigurasi Sistem (mendarat di alamat Admin sendiri).

Delapan peristiwa yang benar-benar mengirim email tercatat di `docs/PANDUAN-PRODUKSI-EMAIL.md` §1; sisanya lonceng saja. Fase 2 rencana ini **menambah satu peristiwa terbit** (auto-approve salinan arsip) — pastikan ia memakai jalur `kabarkanTerbit()` yang sudah ada, jadi tak ada Notification baru yang perlu diuji dari nol.

### 13c. Mobile

`routes/api.php` (204 baris) 100% Sanctum Bearer, read-only, 12 controller. **Tak satu pun fase di atas menyentuh ****`routes/api.php`**** atau ****`app/Http/Resources/*`** — kontrak mobile aman. Dua hal yang wajib diperiksa karena Fase 1 mengubah angka revisi:

- `DocumentResource` — pastikan ia mengirim `no_revisi`/`edisi` apa adanya (tanpa asumsi ≤ 4 di sisi Flutter).
- `UserResource::$role` tetap mengirim kunci mentah `staff` apa adanya (CLAUDE.md §6).

Sesudah server hidup (RUNBOOK §12): `api_config.dart` diarahkan ke produksi · `usesCleartextTraffic` → **`false`** · `pubspec.yaml` bump build. Ketiganya bergerak bersama. Verifikasi: `GET /api/notifications` menjawab (404 = APK masih build lama).

### 13d. Gerbang pra-produksi

Jalankan `docs/CHECKLIST-PREPRODUKSI.md` berurutan: backup DB → bersihkan data uji (`smartpro:hapus-dokumen`, lampiran yatim, sesi/antrean, cache) → verifikasi → gerbang kode (`test` + `route:cache` + `config:cache` + `view:cache`) → **audit rahasia SEBELUM ****`git push`**.

**Dua utang yang wajib diputuskan pemilik sebelum push:**

| UtangIsiKeputusan yang dibutuhkan |                                                                                                                    |                                                                                                                                                                                 |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **U1**                            | `.env.cadangan-uji` **ter-track di git** sejak `a00eaa5`, memuat `APP_KEY`, `GEMINI_API_KEY`, `OPENROUTER_API_KEY` | Rotasi kunci + `git rm --cached`? Atau tetap ditahan selama repo privat? (`.gitignore` sudah ditutup pola `.env.*` di K5, tapi berkas yang sudah terlacak tak ikut terbatalkan) |
| **13.1**                          | 79 Blade yatim + `axios` di `devDependencies` + `Blade::if('role')` — semuanya nol pemakai                         | Dihapus sekarang atau sesudah `CEKLIS-MATA-PER-PERAN.md` selesai? Penghapusan butuh konfirmasi eksplisit (CLAUDE.md §4)                                                         |

**Pemeriksaan mata per peran** (gerbang otomatis tak pernah memeriksa tata letak) — `docs/CEKLIS-MATA-PER-PERAN.md`, ketujuh peran, ditambah empat pemeriksaan pasca-rilis RUNBOOK §10 (konsol peramban, navigasi Inertia, mode gelap muat-ulang, satu URL 403).

---

## Verifikasi menyeluruh (end-to-end, dijalankan di Fase 13)

Satu alur nyata di atas **satu** dokumen, ditambah dua alur baru dari rencana ini:

1. **Alur normal**: GL buat draft → kirim → SH tinjau → PJO setujui → unduh PDF (periksa kop, "Halaman X dari Y", blok pengesahan) → ekspor Daftar Induk → lonceng berbunyi → **satu email sampai**.
2. **Alur revisi (butir 3)**: revisi dokumen Berlaku berulang kali sampai Revisi 5, lalu sekali lagi → harus menjadi **Edisi 2 Revisi 0**. Periksa lembar CATATAN REVISI-nya di halaman depan PDF.
3. **Alur salin arsip (butir 7)**: unggah PDF lama bernomor manual `…-05` → halaman Catatan Revisi (tanpa radio, tanpa isian potong halaman) → wizard → kirim → **langsung Berlaku dengan nomor ****`…-05`**, dan dokumen unggahan aslinya **Tidak Berlaku**.
4. **Nomor bentrok (butir 4)**: unggah dokumen lama bernomor yang sudah dipakai → **modal** muncul → pilih "Pakai nomor otomatis" → terdaftar dengan nomor bebas berikutnya.
5. **AI (butir 1)**: buka layar tinjau JSA → Analisis AI → temuan muncul berbadge; matikan internet → pesan "lanjutkan tinjauan manual", **bukan 500**.
6. **Dasbor (butir 8–11, 14)**: login bergantian GL `14040677`, SH `16081467`, DH `17092728`, PJO `16071367`, MD `MD-0001`, Admin `ADM-0001`, Non-Staff `20260219` — periksa label meter, rincian PIC, warna sebaran, pemilik di kartu masukan. Plus **mode gelap**.
7. **Cetak (butir 5)**: satu SOP panjang yang sub-babnya jatuh tepat di batas halaman → judul sub-bab **tidak pernah** sendirian di dasar halaman.

---

## Ringkasan berkas yang disentuh

**Server (PHP)** `app/Inertia/PratinjauResponseFactory.php` · `app/Http/Middleware/HandleInertiaRequests.php` · `app/Http/Controllers/Auth/LoginController.php` · `app/Console/Commands/UjiRender.php` · `app/Services/DocumentService.php` · `app/Http/Requests/{ArsipDocumentRequest,StoreInformasiRequest,StoreDocumentRequest}.php` · `app/Http/Controllers/{DocumentArsipController,DashboardController,ReviewController,PengaturanController,DocumentPdfController,ApprovalController}.php` · `app/Providers/AppServiceProvider.php` · `app/Services/Ai/NullReviewer.php` · `app/Services/ActivityPrintLayout.php` · `app/Services/Print/PdfRenderer.php` *(Fase 12 saja)* · `database/seeders/DocumentTypeSeeder.php` · satu migration aditif (`documents.salin_arsip_at`)

**Klien (TSX, hanya pohon V2)** `pages/V2/{Documents/Arsip/Catatan,Documents/Create,Documents/StaffStatus,Approvals/Show,Pengaturan/Sistem}.tsx` · `components/v2/dasbor/{KartuMasukan,PitaDepartemen,PerformaPic,MeterTertinjau}.tsx` · `components/v2/dokumen/fields/{DocumentPicker,RepeatableGroup}.tsx` · `components/v2/dokumen/DialogNomorBentrok.tsx` *(baru)* · `components/v2/tinjau/PanelAi.tsx` · `types/{dasbor,dokumen,distribusi,wizard}.d.ts` · `resources/css/app.css` *(dua token **`--chart-6/7`**)*

**Dokumen** `CLAUDE.md` (§7 batas revisi · §13 palet grafik · §14b daftar cetak · §14c patokan tes · §17 rujukan yang hilang) · `docs/PROGRESS-SHADCN.md` · `docs/DASBOR-V2-REVISI.md` (§P5 dicabut, §5d/§5e diperbarui) · `docs/PATOKAN-GAYA-V2.md` (Fase 7) · `docs/CHECKLIST-PREPRODUKSI.md` · `docs/RUNBOOK-RILIS.md`

**Patokan tes**: 718 → **±740** (nol tes dihapus; \~6 tes diperbarui gaya asersinya, \~22 tes baru).