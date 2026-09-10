# Golden Fixture — Pembanding Migrasi ke Project Baru

Dibekukan pada **Tahap 0** migrasi ke `smartprorefactor`. Berkas di folder ini
merekam hasil cetak PDF yang **sudah benar** dari project ini, supaya project baru
bisa **dibuktikan** identik — bukan sekadar "kelihatannya sama".

> **JANGAN diubah manual.** Kalau isinya disesuaikan agar test lolos, seluruh gunanya
> hilang. Bila project baru tak cocok dengan berkas ini, yang salah project barunya.

## Selisih yang DISENGAJA (bukan kegagalan)

Satu-satunya pengecualian atas aturan di atas. Bila pembanding Tahap 4 melaporkan
selisih di bawah ini, itu **bukan** cacat cetak — jangan "perbaiki" project barunya
agar cocok, dan jangan pula menyunting berkas fixture-nya.

| Berkas | Selisih | Sebab |
|---|---|---|
| `SP.json` | kop & cover berbunyi `STANDARD PRODUKSI`, project sekarang mencetak `STANDAR PARAMETER` | Jenis SP diganti nama "Standar Produksi" → "Standar Parameter" (migrasi `2026_08_10_090000_rename_sp_standar_parameter`), sekaligus membetulkan ejaan "STANDARD". Kode jenis, nomor dokumen, dan seluruh tata letak TIDAK berubah. |

Akibatnya `sha1_teks_mentah` dan `teks_per_halaman` milik `SP.json` sudah tidak
sebanding lagi. `sha1_koordinat` kemungkinan besar ikut bergeser: judul kop
di-center, dan meski "STANDARD PRODUKSI" dan "STANDAR PARAMETER" sama-sama 17
karakter, lebar glyph-nya berbeda sehingga titik mulainya bergeser. Yang masih
sahih untuk SP tinggal `halaman`, `orientasi`, `lebar`, dan `tinggi`.

Untuk membandingkan SP secara utuh lagi, fixture-nya harus **dibekukan ulang**
dari dokumen SP yang sudah disahkan pada project ini — bukan disunting tangan.
Berkas SOP/IK/JSA tidak terpengaruh sama sekali.

## Dokumen yang dibekukan

| Berkas | Dokumen | Yang diuji |
|---|---|---|
| `SOP.json` | `PPA-ADRO-SOP-ICTMD-05` (id 2183) | 6 hal. portrait · 8 bab · **lembar CATATAN REVISI** · **lampiran gambar** · 3 cap APPROVED |
| `IK.json` | `PPA-ADRO-IK-ICTMD-08` (id 3229) | 2 hal. · alur **dual** (pengesahan 2 baris) · **pembuat tambahan** |
| `SP.json` | `PPA-ADRO-SP-ICTMD-03` (id 3228) | 5 hal. · alur **dual** · **pembuat tambahan** |
| `JSA.json` | `PPA-ADRO-JSA-ICTMD-06` (id 3233) | 2 hal. **landscape** · mesin paginasi 2-fase · Edisi 1/rev3 · punya data `catatan_revisi` tapi **tak boleh dicetak** |

## Isi tiap berkas

| Kunci | Guna |
|---|---|
| `halaman`, `lebar`, `tinggi`, `orientasi` | menangkap salah orientasi / ukuran kertas |
| `teks_per_halaman` | menangkap beda isi, urutan, dan **titik potong halaman** |
| `y_terendah_per_halaman` | menangkap teks menembus margin bawah 2cm (57pt) |
| `koordinat_per_halaman` | seluruh operator `Td` — menangkap pergeseran posisi sekecil apa pun |
| `sha1_teks_mentah` | sidik jari teks dari byte **mentah** — **otoritatif** untuk perbandingan |
| `sha1_koordinat` | sidik jari tata letak |

`teks_per_halaman` sudah dibersihkan agar valid UTF-8 (DomPDF menulis font tertanam
sebagai UTF-16BE; setelah `\x00` dibuang tersisa byte tak valid). Karena itu
**pembanding yang sah adalah `sha1_teks_mentah`**, bukan teks yang tampil.

## Cara memakainya di project baru (Tahap 4)

1. Salin folder ini apa adanya.
2. Impor `docs/data-migrasi.sql` supaya dokumen dengan **id yang sama** tersedia.
3. Render tiap dokumen lewat jalur yang sama (`renderPdfDocument()`), ekstrak dengan
   cara yang sama, lalu bandingkan `halaman`, `orientasi`, `sha1_teks_mentah`, dan
   `sha1_koordinat`.
4. Bila meleset → **BERHENTI dan lapor**. Jangan diteruskan ke tahap berikutnya:
   seluruh tahap sesudahnya bertumpu pada cetakan yang benar.

## Berkas pendamping (di luar folder ini)

- `docs/schema-baseline.sql` — 21 tabel `SHOW CREATE TABLE`, pembanding Tahap 2.
  Diurut **abjad** agar diff stabil, jadi saat dipasang perlu `SET FOREIGN_KEY_CHECKS=0`.
- `docs/data-migrasi.sql` — 10 dokumen (rantai revisi utuh), 9 user (referensi +
  1 per jabatan×dept), 3 lampiran, seluruh master. **Sudah diuji impor** ke database
  sementara: relasi utuh, tak ada rantai revisi putus.
- Berkas yang harus ikut disalin manual ke `storage/app/public/`:
  - `avatars/dlztRwZX8CCYJTKUOu3qf4pD93viK4zfIt0OkoUD.jpg`
  - `lampiran/ICTMD/SOP/img_6a615a5f085e0.png`
  - `lampiran/ICTMD/SOP/img_6a67f9a2bebf6.png`
  - `lampiran/SHE/SOP/img_6a6420e392443.png`
