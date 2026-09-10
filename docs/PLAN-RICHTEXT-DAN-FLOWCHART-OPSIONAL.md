# Rencana — Rich Text Aktivitas & Flowchart Opsional

> Status: **rencana, belum dikerjakan.** Disusun 27 Agustus 2026.
> Sumber kebenaran teknis tetap `CLAUDE.md`; produk tetap `docs/PRD-SmartPro-v3.1.md`.

## 1. Konteks

Dua permintaan pemilik yang bertemu di satu tempat: bab **AKTIVITAS DAN TANGGUNG JAWAB**
pada SOP/SP/IK.

**A.** Kolom "Deskripsi Aktivitas" hari ini `textarea` polos. Penyusun tak bisa membuat
daftar bernomor, tak bisa menebalkan/menggarisbawahi istilah penting, dan — yang paling
dibutuhkan — **tak bisa menyisipkan foto di tengah uraian**. Foto hanya bisa menempel di
bab LAMPIRAN, terpisah jauh dari langkah yang menjelaskannya.

**B.** Bab FLOWCHART sudah `required: false` di schema, tapi **nomornya terlanjur beku**:
`DocumentTypeSeeder::bernomor()` mencetak "V. FLOWCHART / VI. AKTIVITAS / VII. LAMPIRAN"
saat seed. SOP yang tak memakai flowchart tetap mencetak bab V kosong dan bab sesudahnya
tetap VI–VII — nomor bolong yang tak pantas untuk dokumen mutu. Yang diinginkan:
flowchart dipakai → V/VI/VII seperti sekarang; tak dipakai → AKTIVITAS naik jadi
**V (5.1, 5.2…)** dan LAMPIRAN jadi **VI**.

Keputusan yang sudah diambil pemilik:

| Perkara | Pilihan |
|---|---|
| Editor | **Quill 2 via CDN** |
| Cakupan rich text | **hanya kolom Deskripsi Aktivitas** (SOP + SP + IK sekaligus) |
| Cara menyatakan pakai/tidak | **sakelar eksplisit** "Gunakan Flowchart" |
| Pembersih HTML | **sanitizer sendiri** berbasis `DOMDocument` — nol package baru (CLAUDE.md §4) |

Aturan yang tak boleh goyah: schema tetap sumber tunggal form/preview/PDF (§7); satu set
view untuk semua jenis (§3); kalibrasi cetak DomPDF & mesin paginasi 2-fase tetap utuh.

---

## 2. Koreksi setelah pemeriksaan ulang

Rancangan pertama punya tiga cacat. Ketiganya sudah diperbaiki di dokumen ini, dan
dicatat di sini supaya tak diam-diam kembali.

**2.1 — Dokumen lama TIDAK boleh dinilai dari isinya.**
Rancangan pertama berkata: bila kunci `pakai_flowchart` belum ada (dokumen lama), anggap
flowchart aktif *bila isinya tak kosong*. Itu **salah dan berbahaya**. Setiap SOP yang
sudah terbit dengan flowchart kosong akan tiba-tiba dinomori ulang — dokumen yang sudah
disahkan dan sudah dibagikan berubah nomor babnya tanpa ada yang menyentuhnya.
Dua tes yang ada membuktikan cacat itu langsung:
`SopSectionOrderTest::test_flowchart_kosong_tak_memakan_halaman` dan
`PeringatanIsianKosongTest::test_flowchart_dan_lampiran_tak_menghalangi_kirim`
sama-sama membuat SOP tanpa isi flowchart dan menuntut "V. FLOWCHART"/"VI. AKTIVITAS".

> **Aturan yang benar: kunci tak ada → flowchart MENYALA.** Hanya `'0'` yang eksplisit
> mematikannya. Dengan begitu setiap dokumen yang sudah ada tercetak **persis** seperti
> hari ini, dan kedua tes tetap hijau tanpa diubah.

**2.2 — `saveStep` & `autosave` harus memakai schema TANPA penomoran ulang.**
Penomoran ulang **membuang** bab flowchart dari daftar section. Bila jalur penyimpanan
ikut memakai daftar yang sudah dibuang itu, isi flowchart berhenti tersimpan begitu
sakelar dimatikan — dan hilang diam-diam ketika sakelar dinyalakan lagi.

> **Aturan: yang dinomori ulang hanya jalur TAMPIL (form, cetak, tinjau, AI, log revisi).
> Jalur SIMPAN (`saveStep`, `autosave`, `persistStep`) tetap memakai schema penuh.**
> Mematikan sakelar menyembunyikan bab, bukan menghapus isinya.

**2.3 — Titik pemasangannya sepuluh, bukan tiga.**
`SchemaService::for()` dipanggil di **13 tempat**, bukan 4 seperti taksiran awal. Daftar
lengkapnya ada di §5.2. Karena itu rancangan ini menambah `SchemaService::untuk(Document)`
— membangun *dan* menomori dalam satu panggilan — supaya tak ada layar yang tertinggal
memakai nomor lama. `for(DocumentType)` tetap ada untuk dua pemanggil yang memang tak
punya dokumen.

---

## 3. Temuan penelusuran yang membentuk rancangan

- **Preview layar = PDF sungguhan** di dalam iframe (`edit.blade.php:207` →
  route `documents.pdf`). Hanya ada **satu** jalur render: DomPDF. Tak ada preview HTML
  terpisah yang harus disamakan — ini menghemat separuh pekerjaan Bagian A.
- `ActivityPrintLayout::flatten()` memecah `deskripsi` **per baris-baru** menjadi baris
  tabel terpisah. Itulah yang membuat aktivitas panjang mengalir antar-halaman dan sel PIC
  `rowspan` tak rusak di batas halaman. Rich text **wajib** masuk ke model yang sama —
  satu blok = satu baris — bukan satu sel besar berisi HTML.
- Nomor bab & `auto_number` dibekukan saat seed oleh `bernomor()`, yang **membuang** `nama`
  asli setelah memakainya.
- Peta partial field ada di **Blade** (`edit.blade.php:6–13`), bukan di controller.
- `AbstractAiReviewer` meratakan nilai section menjadi teks prompt — HTML akan bocor ke
  prompt AI bila tak di-`strip_tags`.
- Route unggah gambar **sudah ada** dan sudah dipakai field `image`:
  `DocumentController::uploadAttachment` → `lampiran/{DEPT}/{JENIS}/`. Tombol gambar Quill
  diarahkan ke sana, jadi foto tersimpan sebagai berkas — bukan base64 yang menggembungkan
  `value_json`.
- Kunci di luar schema **sudah lazim** di `document_contents`: `catatan_revisi` disimpan
  begitu (`DocumentWizard::persistRevisionLog`). Jadi `pakai_flowchart` mengikuti pola yang
  ada, bukan mekanisme baru — **nol migrasi**.

---

## 4. Bagian A — Rich text di Deskripsi Aktivitas

### 4.1 Tipe field baru `rich_text` (schema)

Di `database/seeders/DocumentTypeSeeder.php`, kolom `deskripsi` pada bab `aktivitas` —
**dua fungsi, tiga jenis**: `standardSchema()` (dipakai SOP & SP) dan `ikSchema()` —
berubah dari `'type' => 'textarea'` menjadi:

```php
['key' => 'deskripsi', 'label' => 'Deskripsi Aktivitas', 'type' => 'rich_text',
 'placeholder' => 'Deskripsi aktivitas...'],
```

Tak ada route/view baru per jenis dokumen: satu kunci schema, tiga jenis ikut. Persis
jalur §3/§7.

### 4.2 Editor — Quill 2 lewat CDN

Di `resources/views/layouts/app.blade.php`, dua baris di samping Bootstrap Icons/Alpine/
SweetAlert yang sudah dari CDN (baris 12–14 & 1283–1284):

```
https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css
https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js
```

Toolbar dibatasi **persis sebatas kebutuhan** — tak ada warna, font, atau tabel yang
takkan tercetak benar oleh DomPDF:

```js
toolbar: [['bold','italic','underline'], [{list:'ordered'},{list:'bullet'}], ['image'], ['clean']]
```

**Partial baru `resources/views/documents/fields/_rich_text.blade.php`** — komponen Alpine
`richText` yang:

1. memasang Quill pada satu `<div>`;
2. menulis balik HTML ke hidden `<input name="sections[aktivitas][i][deskripsi]">` pada
   tiap `text-change` (autosave 1200ms yang sudah ada ikut terpicu sendiri);
3. memasang *image handler* kustom → unggah ke `documents.uploadAttachment` → sisipkan
   `<img src="/storage/lampiran/…">`;
4. meneruskan penanda `data-optional` / `data-pp-warn` ke hidden input itu, supaya
   `ppValidateRequired` dan peringatan saat Kirim tetap bekerja seperti field lain.

`_repeatable_group.blade.php` mendapat satu cabang `@elseif ($ftype === 'rich_text')` yang
meng-`@include` partial itu — sejajar dengan cabang `image` dan `document_picker` yang
sudah ada. Peta `$partials` di `edit.blade.php` **tidak wajib** diubah (rich_text adalah
tipe *group field*, bukan tipe section); menambahkannya boleh, sekadar untuk pemakaian
kelak sebagai section berdiri sendiri.

### 4.3 Satu perbaikan terarah yang wajib menyertainya

`<template x-for>` di `_repeatable_group` memakai `:key="i"` (indeks). Menghapus baris
ke-1 membuat Alpine **memakai ulang** DOM baris berikutnya. Untuk `<input>` biasa itu tak
apa-apa — `x-model` mengikat ulang. Untuk Quill **tidak**: instansnya akan memegang isi
baris yang salah, dan penyusun kehilangan tulisannya tanpa peringatan.

Perbaikannya kecil dan lokal: `Alpine.data('repeatable')` (`edit.blade.php:273`) menyimpan
larik `uids` sejajar dengan `rows` — dibuat di `add()`, dibuang di `remove()` — dan
`x-for` memakai `:key="uids[i]"`. `uids` hidup di komponen saja, **tak ikut tersimpan**
ke `rows`, jadi tak ada nilai asing yang masuk ke `value_json`.

Ini termasuk "memperbaiki kode yang sedang dikerjakan", bukan refactor liar: tanpa itu
fitur A tidak bisa benar.

### 4.4 Sanitasi — `App\Services\RichText\PembersihHtml`

Class murni (nol dependensi baru) memakai `DOMDocument` bawaan PHP dengan **daftar-putih
ketat**:

- Tag diizinkan: `p, br, strong, em, u, ol, ul, li, img`. `<b>`/`<i>` dinormalkan ke
  `<strong>`/`<em>`. Tag lain **dibuang, isinya dipertahankan**.
- Atribut: semua dibuang, kecuali `img[src]` yang **wajib** cocok
  `^/storage/lampiran/[A-Za-z0-9_-]+/[A-Z]+/[\w.-]+$`. Di luar pola itu, `<img>`-nya
  dibuang. Ini sekaligus menutup `javascript:`, `data:`, dan gambar dari luar.
- Quill 2 menulis bullet sebagai `<ol><li data-list="bullet">`; dinormalkan jadi
  `<ul><li>`, sehingga yang tersimpan HTML biasa — bukan dialek Quill. Editor lain kelak
  bisa menggantikan Quill tanpa menyentuh jalur cetak.
- **Editor kosong menghasilkan `<p><br></p>`** → dinormalkan jadi string kosong, supaya
  `filled()`, `cleanValue()`, dan peringatan isian-kosong tetap membacanya sebagai kosong.

Dipanggil dari `DocumentWizard::cleanValue()`: cabang `repeatable_group` membersihkan tiap
field yang schema-nya bertipe `rich_text` sebelum baris disimpan.

> Sanitasi terjadi **saat simpan**, bukan saat cetak. Yang tersimpan di `document_contents`
> sudah bersih, jadi jalur cetak tak perlu mempercayai apa pun.

### 4.5 Cetak — `ActivityPrintLayout` memecah per **blok**

Inti Bagian A. `flatten()` berhenti memakai `preg_split('/\r\n|\r|\n/')` dan mulai memecah
HTML menjadi **blok tingkat-atas**; satu blok = satu baris tabel:

| Blok HTML | Baris yang dihasilkan |
|---|---|
| `<p>…</p>` | `kind: 'teks'`, `html` = isi inline tersanitasi |
| `<li>` dalam `<ol>` | `kind: 'teks'`, `html` = `<span class="mk">1.</span>` + isi |
| `<li>` dalam `<ul>` | `kind: 'teks'`, `html` = `<span class="mk">&bull;</span>` + isi |
| `<img src="/storage/…">` | `kind: 'gambar'`, `path` = `lampiran/…` |
| teks polos (dokumen lama) | dipecah per baris-baru — **perilaku lama, tak berubah** |

Nomor/bulir daftar **dicetak sebagai teks**; `<ol>`/`<ul>` tak pernah sampai ke DomPDF.
Alasannya bukan selera: bila satu `<ol>` panjang duduk dalam satu `<tr>`
(`page-break-inside: avoid`), daftar itu **tak bisa dipecah antar-halaman** dan akan
meluber margin bawah — persis penyakit yang mesin 2-fase ini dibangun untuk menyembuhkan.
Memecah per `<li>` membuat daftar panjang mengalir seperti paragraf.

`plan()`, `spanCount()`, logika `mrg` / `showPic` / `picRowspan`, dan pengukuran anchor
`[[Ai]]` **tidak disentuh sama sekali** — semuanya bekerja atas indeks baris, dan jumlah
baris tetap terdefinisi. Deteksi HTML vs teks polos: `$s !== strip_tags($s)`.

### 4.6 `print/render.blade.php`

Pada cabang `$hasPic` (± baris 249–268):

- baris `kind: 'teks'` dicetak dengan penanda Blade tak-di-escape — sudah tersanitasi
  saat simpan;
- baris `kind: 'gambar'` mencetak `<img class="akt-img" src="…">` memakai closure `$embed`
  yang **sudah ada** di `viewData()` (data URI base64 — DomPDF tak bisa memuat
  `/storage/…`);
- CSS baru, sedikit saja:
  `.akt-img { max-width:100%; max-height:220pt; }` dan
  `.mk { display:inline-block; width:16pt; margin-left:-16pt; }`.

`.mk` **sengaja meniru pola `.jn`** yang sudah terkalibrasi di berkas itu (hanging indent
lewat `margin-left` negatif, bukan `text-indent` — DomPDF menerapkan `text-indent` negatif
dua kali lipat; lihat HANDOVER §7). Hasilnya: baris lanjutan sebuah butir sejajar dengan
teksnya, bukan dengan bulirnya.

Cabang `probeFlat` (probe pengukuran Fase 1) **wajib** memakai `html`/`path` yang sama —
tinggi yang diukur harus tinggi yang benar-benar dicetak, kalau tidak seluruh paginasi
meleset.

### 4.7 Dua pembaca lain

- `review/show.blade.php:250` mencetak tiap field grup ter-escape → peninjau akan melihat
  tag mentah. Cabang kecil: field bertipe `rich_text` dicetak tak-di-escape di dalam
  `<div class="pp-rt">` dengan `img{max-width:100%}`.
- `AbstractAiReviewer.php:352` — nilai di-`strip_tags` sebelum masuk prompt, jadi AI
  membaca kalimat, bukan markup. Sejalan dengan catatan `ponytail` yang sudah ada di sana
  (gambar dikirim sebagai metadata saja).

---

## 5. Bagian B — Flowchart opsional & penomoran bab dinamis

### 5.1 Schema menyimpan `nama`, bukan cuma label bernomor

`bernomor()` sekarang **membuang** `nama` setelah memakainya. Ubah agar `nama` ikut
tersimpan di section. `label` dan `auto_number` tetap diisi seperti sekarang (varian
"flowchart menyala"), sehingga bila penomoran ulang tak diterapkan pun hasilnya identik
dengan hari ini.

Bab flowchart mendapat tiga kunci baru:

```php
'bab_opsional' => true,
'toggle_key'   => 'pakai_flowchart',
'toggle_label' => 'Gunakan Flowchart',
```

### 5.2 `SchemaService` — dua method baru

```php
public static function untuk(Document $document): self   // build + nomori sekaligus
public function denganPenomoran(array $contentMap): self // instans baru, section dinomori ulang
```

`denganPenomoran()`:

1. Bab ber-`bab_opsional` yang **tidak aktif** dibuang dari daftar.
2. Sisanya dinomori ulang menurut posisi: `label = ROMAWI[n].'. '.$nama`,
   `auto_number = $n.'.'`. `user_picker` dilewati (tak bernomor).

**Aktif/tidaknya** — aturan final (lihat §2.1):

```
$contentMap['pakai_flowchart'] === '0'  →  MATI
selain itu (termasuk kunci tak ada)      →  MENYALA
```

Karena yang dikembalikan instans `SchemaService` biasa, **semua pemanggil lama
(`allSections()`, `sectionsForStep()`, `findSection()`, `raw()`) jalan tanpa diubah.**
Konstanta `ROMAWI` pindah dari seeder ke `SchemaService` (seeder ikut memakainya) supaya
angka Romawi hanya hidup di satu tempat.

**Peta pemasangan — 13 pemanggil `SchemaService::for`:**

| Berkas | Jalur | Perlu penomoran? |
|---|---|---|
| `Print/PdfRenderer.php:336` (`viewData`) | cetak + preview + `petaHalamanBab` + `activitySection` | **YA** — satu ini menutup seluruh jalur cetak |
| `DocumentController.php:336` (`edit`) | form | **YA** |
| `ReviewController.php:70` | tinjau SH/DH | **YA** |
| `MasukanSejawatController.php:54,154` | masukan sejawat | **YA** |
| `MdReviewController.php:66` | tinjau Management Development | **YA** |
| `Api/ReviewApiController.php:70` | tinjau via mobile | **YA** |
| `DocumentService.php:341` (`usulanCatatanRevisi`) | label bab lembar Catatan Revisi | **YA** — kalau tidak, lembar revisi menyebut bab yang tak tercetak |
| `Ai/AbstractAiReviewer.php:146` | label bab di prompt AI | **YA** |
| `DocumentController.php:394` (`saveStep`) | **simpan** | **TIDAK** (§2.2) |
| `DocumentController.php:471` (`autosave`) | **simpan** | **TIDAK** (§2.2) |
| `ReviewDecision.php:81` | cari section `jsa_analysis` | tidak perlu (baca `type` saja) |
| `Print/PdfRenderer.php:322` (`viewName`) | baca `print_view` | tidak perlu |

### 5.3 Menyimpan sakelar — tanpa migrasi

Nilainya disimpan sebagai isi section berkunci `pakai_flowchart` di `document_contents`
(`'1'` / `'0'`). Tabelnya memang kunci–nilai bebas, dan kunci di luar schema sudah dipakai
hari ini oleh `catatan_revisi`. **Nol migrasi, nol kolom baru.**

`DocumentWizard::persistStep()` mendapat ±5 baris: section yang menyatakan `toggle_key`
juga menyimpan nilai sakelarnya. Karena jalur simpan memakai schema penuh (§2.2), bab
flowchart tetap ikut tersimpan walau sakelarnya mati — mematikan sakelar
**menyembunyikan**, bukan menghapus.

Bawaan untuk dokumen baru: **menyala** — sama persis dengan perilaku hari ini.

### 5.4 Form — nomor yang ikut berpindah, tanpa reload

Di `_repeatable_group.blade.php`, bab ber-`toggle_key` mendapat checkbox di kepala bab.
Mati → isian flowchart tersembunyi (`x-show`) dan nomor bab sesudahnya berubah **seketika**.

Penomoran di layar **tidak dihitung ulang oleh JavaScript**. PHP memanggil
`denganPenomoran()` dua kali (varian nyala & mati) dan mengirim kedua label ke Blade;
Alpine hanya memilih salah satunya — `x-text="$store.bab.on ? labelNyala : labelMati"`.

Hal yang sama untuk `auto_number` (lencana "6.1" vs "5.1" di `_repeatable_group` dan
`_rich_list`), dan untuk judul Langkah 2 ("Flowchart, Aktivitas, Lampiran & Verifikasi"
vs "Aktivitas, Lampiran & Verifikasi").

> PHP tetap satu-satunya yang tahu cara menomori bab. JS cuma sakelar dua posisi.

### 5.5 Yang otomatis ikut benar

`page_break: both` milik flowchart lenyap bersama babnya saat sakelar mati. Penjaga "bab
berdiri-sendiri yang kosong tak boleh menuntut halamannya" di `render.blade.php:186` tetap
di tempatnya sebagai jaring pengaman untuk keadaan sakelar-nyala-tapi-kosong.

`petaHalamanBab()` mencocokkan anchor `[[S{2i}]]` menurut indeks `allSections()`. Karena ia
membaca `$data['schema']` yang **sama** dengan yang dipakai `render.blade.php`, indeksnya
sejalan dengan sendirinya — tak ada penyesuaian tambahan.

---

## 6. Berkas yang disentuh

**Baru**

- `app/Services/RichText/PembersihHtml.php`
- `resources/views/documents/fields/_rich_text.blade.php`
- `tests/Unit/PembersihHtmlTest.php`
- `tests/Feature/PenomoranBabOpsionalTest.php`

**Diubah**

- `database/seeders/DocumentTypeSeeder.php` — `bernomor()` menyimpan `nama`; `deskripsi`
  jadi `rich_text`; kunci `bab_opsional`/`toggle_key`/`toggle_label` di flowchart
- `app/Services/SchemaService.php` — `untuk()`, `denganPenomoran()`, konstanta `ROMAWI`
- `app/Services/ActivityPrintLayout.php` — `flatten()` memecah per blok
- `app/Services/DocumentWizard.php` — sanitasi `rich_text`; simpan `toggle_key`
- `app/Services/Print/PdfRenderer.php` — pasang penomoran di `viewData()`
- `app/Services/DocumentService.php` — label bab bernomor untuk lembar Catatan Revisi
- `app/Services/Ai/AbstractAiReviewer.php` — schema bernomor + `strip_tags` sebelum prompt
- `app/Http/Controllers/DocumentController.php` — schema bernomor di `edit()` **saja**
- `app/Http/Controllers/ReviewController.php`, `MasukanSejawatController.php`,
  `MdReviewController.php`, `Api/ReviewApiController.php` — schema bernomor
- `resources/views/layouts/app.blade.php` — dua baris CDN Quill
- `resources/views/documents/edit.blade.php` — `uids` di `repeatable`; store `bab`
- `resources/views/documents/fields/_repeatable_group.blade.php` — cabang `rich_text`,
  checkbox sakelar, label & prefix dua-varian
- `resources/views/documents/fields/_rich_list.blade.php` — prefix dua-varian
- `resources/views/documents/print/render.blade.php` — render blok teks/gambar + CSS
- `resources/views/review/show.blade.php` — tampilkan `rich_text` sebagai HTML

**Tes yang menyesuaikan**

- `tests/Unit/ActivityPrintLayoutTest.php` — kasus blok HTML
- `tests/Feature/PrintLayoutTest.php` — rich text tercetak
- `tests/Feature/SopSectionOrderTest.php` — **tambah** kasus sakelar-mati; kasus lama
  tetap apa adanya (§2.1)
- `tests/Feature/PeringatanIsianKosongTest.php` — diharapkan **tak berubah** (§2.1); bila
  ia merah, aturan §2.1 sedang dilanggar

---

## 7. Urutan kerja (CLAUDE.md §5 — satu fase, lalu berhenti)

| Fase | Isi | Kenapa urutannya begini |
|---|---|---|
| **A1** | `PembersihHtml` + tes unit | Murni, tanpa UI. Fondasi keamanan lebih dulu. |
| **A2** | `flatten()` per blok + `render.blade.php` + tes cetak | **Cetak dibereskan SEBELUM editor menyala**, supaya begitu penyusun bisa mengetik, hasilnya sudah pasti tercetak benar. |
| **A3** | Schema `rich_text`, Quill CDN, partial, `uids`, sanitasi di wizard, review & AI | Barulah editor dipasang. |
| **B1** | `bernomor()` + `denganPenomoran()`/`untuk()` + pemasangan di 8 titik tampil + tes penomoran | Sakelar belum ada; semua dokumen masih "menyala" → **nol perubahan terlihat**. Fase ini seharusnya lolos tanpa satu tes pun berubah. |
| **B2** | Sakelar di form, penyimpanan `pakai_flowchart`, label dua-varian | Perilaku barunya baru muncul di sini. |

Tiap fase: berhenti, lapor singkat, tunggu review + commit.

**Perlu persetujuan terpisah sebelum Fase A3 & B1 dipakai:**

```
php artisan db:seed --class=DocumentTypeSeeder
```

Seeder memakai `updateOrCreate` atas kolom `code` — ia **hanya menulis ulang
`document_types.schema_json`**, tak menyentuh `documents` maupun `document_contents`.
Tetap ditunjukkan dan menunggu izin (CLAUDE.md §4).

---

## 8. Verifikasi

### Otomatis

```
php artisan test --filter="PembersihHtml|ActivityPrintLayout|PrintLayout|SopSectionOrder|PenomoranBabOpsional|PeringatanIsianKosong"
php artisan test          # suite penuh — patokan 527 hijau (commit f9d58f4)
```

`PrintLayoutTest` dan `SopSectionOrderTest` membaca **stream PDF sungguhan**, jadi keduanya
sekaligus menjadi bukti tata letak — bukan sekadar bukti kode berjalan.

Tes baru yang harus ada:

- **sanitizer** — `<script>`/`onclick`/`style` hilang; `<b>`→`<strong>`; `img src` di luar
  `lampiran/…` dibuang; `<p><br></p>` → string kosong;
- **`flatten()`** — `<ol>` 3 butir → 3 baris bernomor; `<img>` → baris `kind: 'gambar'`;
  teks polos lama → tetap terpecah per baris-baru (regresi dokumen berjalan);
- **penomoran** — SOP `pakai_flowchart='0'` → `V. AKTIVITAS` / `auto_number '5.'` /
  `VI. LAMPIRAN`; kunci tak ada → V/VI/VII seperti sekarang; SP & IK tak bergeser
  sedikit pun.

### Manual (XAMPP, `php artisan serve`) — satu SOP draft

1. Aktivitas: ketik daftar bernomor 3 butir, tebalkan satu istilah, garisbawahi satu,
   sisipkan foto di tengah → **Preview**. Periksa di PDF: daftar bernomor benar, foto
   inline tak terpotong, kolom PIC tetap rata tengah.
2. Tulis aktivitas panjang (± 2 halaman) berisi daftar & gambar → pastikan **mengalir**
   antar-halaman, PIC muncul lagi di halaman lanjutan, tepi tabel tertutup di batas
   halaman. Inilah yang mesin 2-fase jaga.
3. Tambah baris aktivitas, isi rich text di ketiganya, lalu **hapus baris pertama** →
   isi baris ke-2 dan ke-3 harus tetap benar (bukti perbaikan `uids`, §4.3).
4. Matikan **Gunakan Flowchart** → label di form langsung jadi `V. AKTIVITAS` /
   `VI. LAMPIRAN`, lencana baris jadi `5.1`, `5.2`. Preview: bab FLOWCHART hilang, tak ada
   halaman kosong, kolom "Hal." lembar Catatan Revisi menunjuk halaman yang benar.
5. Nyalakan lagi → V/VI/VII kembali, **dan isi flowchart yang tadi diketik masih ada**
   (bukti §2.2).
6. Buka **Tinjau Dokumen** atas draft itu → deskripsi tampil sebagai teks berformat +
   gambar, bukan tag mentah.
7. Buka satu SOP **lama** (dibuat sebelum perubahan) → tercetak persis seperti sebelumnya,
   nomor bab tak bergeser (bukti §2.1).

---

## 9. Risiko yang dipantau

**Bisa dipastikan aman** — Bagian B setelah koreksi §2.1/§2.2: dokumen yang sudah ada
tercetak identik, tak ada migrasi, tak ada kolom baru, dan Fase B1 dirancang supaya lolos
tanpa satu tes pun berubah.

**Tak bisa dijanjikan sekali jadi** — Bagian A menyentuh tata letak DomPDF:

- **Kalibrasi.** Semua angka di `render.blade.php` adalah hasil pengukuran stream PDF, dan
  DomPDF menggeser `vertical-align` serta `text-indent` negatif **dua kali lipat** nilai
  yang ditulis (HANDOVER §7). `.mk` sengaja meniru pola `.jn` yang sudah terbukti, bukan
  angka baru. Bila tetap melenceng: **diukur ulang dari stream, tidak ditaksir.** Sangat
  mungkin butuh satu-dua putaran penyetelan — itu wajar untuk berkas ini dan sudah
  diperhitungkan di Fase A2.
- **Gambar besar dalam sel tabel.** DomPDF tak bisa memotong satu gambar antar-halaman;
  `max-height: 220pt` menjaga tiap gambar muat dalam satu halaman. Bila pemilik butuh
  bagan selebar halaman, tempatnya memang bab FLOWCHART (`.flow-img`, 500pt), bukan di
  dalam tabel aktivitas.
- **Ketergantungan CDN.** Quill menyusul Bootstrap/Alpine/SweetAlert yang sudah dari CDN.
  Bila kelak jaringan site dibatasi, keempatnya perlu di-vendor ke `public/` bersamaan —
  satu pekerjaan tersendiri, di luar cakupan rencana ini.
