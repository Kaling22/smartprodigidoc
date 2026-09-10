# Plan: Cocokkan berkas lampiran/arsip + perbaiki bug "kategori field is prohibited"

## Context

Lanjutan `docs/preparationproduction.md` Fase 1 (impor dokumen ADWPROWORK.sql). Sebelumnya ditemukan 50 attachment (foto SOP/IK) dan 14 arsip_path (PDF lama arsip) di dump yang berkasnya tak ada di storage lokal. Pemilik sudah menyalin folder `lampiran/` dan `arsip/` dari File Manager Hostinger ke `storage/app/public/lampiran/` dan `storage/app/private/arsip/`.

Verifikasi ulang (dibandingkan path persis dari kolom `attachments.path` / `documents.arsip_path` di DB staging `adwprowork_staging`):
- **arsip: 14/14 cocok persis.** Tak ada yang perlu dikerjakan di sisi ini.
- **lampiran: 31/50 cocok**, **19 tidak cocok** — semuanya di `lampiran/PRODUKSI/IK/`. Penyebabnya: 19 berkas ini aslinya tak punya ekstensi (nama file di DB berakhir titik polos, mis. `img_6a9e300997709.` — bug lama saat upload produksi, `original_name` untuk baris ini `"tempelan"` tanpa ekstensi terdeteksi). Windows tak mengizinkan nama file berakhir titik, jadi saat disalin dari Hostinger, File Manager/Windows mengganti titik akhir itu jadi garis bawah (`img_6a9e300997709_`). Filenya ADA, cuma namanya sedikit beda — bisa dicocokkan 1:1 lewat pemetaan yang sudah dihitung (lihat langkah 1).

Terpisah, pemilik juga minta perbaiki bug: saat memperbarui (bukan menambah) dokumen Informasi, muncul galat **"The kategori field is prohibited"**. Ditelusuri ke akar:
- `app/Http/Requests/StoreInformasiRequest.php:60-61` — untuk **Perbarui** (`$induk` terisi), aturan `kategori` adalah `['prohibited']` (field itu wajib KOSONG/tak dikirim, karena kategori diwarisi dari versi berlaku, bukan dari kiriman).
- `resources/js/components/v2/FormInformasi.tsx:63-79` — `useForm` selalu menginisialisasi `kategori: kategori` (nilai kategori asli, mis. `"SOP"`), TANPA membedakan Tambah vs Perbarui. Karena itu tiap submit Perbarui selalu mengirim `kategori` terisi → `prohibited` di server selalu gagal.
- Bandingkan dengan field `nomor` di berkas yang sama (baris 73): field itu diinisialisasi `nomor: ''` (kosong) — pola yang BENAR untuk field yang `prohibited` saat Perbarui, karena `input id="nomor"` versi Perbarui memang menampilkan `induk.nomor` langsung (bukan lewat `data.nomor`), jadi `data.nomor` tetap kosong dan lolos `prohibited`. Field kategori seharusnya memakai pola yang sama, dan input Kategori yang tampil (baris 120-122) juga sudah menampilkan `label`, bukan `data.kategori` — jadi mengosongkan `data.kategori` saat Perbarui tak mengubah tampilan sama sekali, murni memperbaiki payload.
- File ini satu-satunya salinan (kembaran V1 di `components/informasi/FormInformasi.tsx` sudah tak ada — pohon V1 sudah dihapus commit `e497ef3`), jadi cukup satu titik perbaikan.

## Rencana eksekusi

### 1. Cocokkan 19 berkas lampiran yang namanya bergeser
Rename 19 berkas di `storage/app/public/lampiran/PRODUKSI/IK/` dari akhiran `_` balik ke akhiran `.` (sudah dipetakan persis dari path DB, contoh 2 dari 19):
- `img_6a9e300997709_` → `img_6a9e300997709.`
- `img_6a9e300d81e16_` → `img_6a9e300d81e16.`
- (17 lainnya pola identik — daftar lengkap sudah dihitung dan akan dipakai persis, bukan ditebak ulang)

Verifikasi: jalankan ulang pencocokan by-path terhadap `adwprowork_staging.attachments.path` dan `documents.arsip_path` — target 50/50 lampiran + 14/14 arsip cocok.

### 2. Perbaiki bug "kategori field is prohibited"
Di `resources/js/components/v2/FormInformasi.tsx`, ubah inisialisasi `useForm`:
```
kategori,
```
menjadi
```
kategori: induk ? '' : kategori,
```
(baris ~72, searah dengan pola `nomor: ''` yang sudah ada persis di bawahnya). Tak ada perubahan lain — input Kategori tetap menampilkan `label` (read-only), tak terpengaruh oleh `data.kategori`.

### 3. Verifikasi
- `npm run build` + `node_modules/.bin/tsc --noEmit` (gerbang wajib CLAUDE.md §14c untuk perubahan TSX).
- `php artisan test --filter=Informasi` (regresi backend — perubahan ini murni bentuk payload frontend, aturan backend tak disentuh).
- Manual/logic check: pastikan `data.kategori` kosong saat `induk` terisi (Perbarui) dan tetap terisi kategori asli saat Tambah (`induk` null) — cukup baca kode, tak perlu jalankan browser kecuali pemilik minta.
- Ulang skrip pencocokan path attachment/arsip vs DB staging, laporkan 50/50 & 14/14.

### Tidak termasuk di rencana ini
- Fase 2 (wipe+impor DB `documents` dkk dari `adwprowork_staging`) — itu langkah terpisah di `docs/preparationproduction.md`, MASIH menunggu persetujuan eksplisit terpisah setelah rencana ini selesai (bukan bagian dari izin sekarang).
- Command `app/Console/Commands/ImporDokumenAdwprowork.php` sudah ada (dibuat sesi sebelumnya, sudah diuji `--dry-run`, belum dijalankan sungguhan) — tak diubah rencana ini.
