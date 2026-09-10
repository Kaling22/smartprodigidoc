# RENCANA KERJA — Butir 11 s.d. 17

> Dokumen induk. Setiap butir dikerjakan **satu per satu**: kerjakan → berhenti →
> jelaskan singkat → tunggu review + commit Git (CLAUDE.md §5).
> Detail dua butir terbesar dipecah ke berkas terpisah:
> - `docs/PLAN-BUTIR-15-MOBILE.md` — koneksi mobile + Mailpit
> - `docs/PLAN-BUTIR-17-AI.md` — rubrik audit AI per jenis dokumen

## Urutan pengerjaan

Bug lebih dulu (mengganggu pemakaian sehari-hari), integrasi paling akhir
(paling besar dan paling banyak menyentuh berkas lain):

| # | Butir | Berkas utama | Status |
|---|---|---|---|
| 1 | **14** — crash log pesan | `app/Http/Controllers/DocumentLogController.php` | belum |
| 2 | **13** — edisi tak naik saat revisi 5 | `resources/views/informasi/_form.blade.php` | belum |
| 3 | **11** — spinner | `resources/views/partials/_spinner.blade.php` (baru) | belum |
| 4 | **12** — halaman 403 & 504 | `resources/views/errors/` (baru) | belum |
| 5 | **16** — dashboard MD | `DashboardController.php`, `dashboard.blade.php` | belum |
| 6 | **17** — konfigurasi AI audit | `docs/ai/jenis/*.md` (baru), `AbstractAiReviewer.php` | belum |
| 7 | **15** — mobile + Mailpit | `routes/api.php`, `.env` | belum |

---

## Butir 14 — Crash `Call to a member function getKey() on array`

**Gejala.** Membuka `log-dokumen/pesan` sesudah memusnahkan dokumen → 500 di
`DocumentLogController.php:99`.

**Akar masalah — bukan di baris 99.** Kelima helper dideklarasikan
`: Collection` (`Illuminate\Support\Collection`), tapi rantai
`Model::…->get()->map(fn (…) => $this->baris(...))` mengembalikan
`Eloquent\Collection` **ketika hasil query-nya kosong** — sebab
`EloquentCollection::map()` hanya memanggil `toBase()` bila ada item non-Model,
dan koleksi kosong tak punya item apa pun untuk diperiksa. Lalu
`Eloquent\Collection::merge()` memanggil `$item->getKey()` pada tiap elemen,
padahal `baris()` mengembalikan `array` biasa.

Karena itu crash-nya **bergantung data**: ia meledak pada sumber non-kosong
PERTAMA, selama semua sumber sebelumnya kosong. Di database yang sepi, log
pemusnahan pertama langsung memicunya. Menambal baris 99 saja meninggalkan bug
yang sama laten di baris 97, 98, dan 100.

**Perbaikan.** Tambahkan `->toBase()` di akhir rantai `->get()->map(...)` pada
kelima helper, sehingga tipe kembaliannya benar-benar cocok dengan deklarasi
`: Collection` dan docblock `@return Collection<int, array<string, mixed>>`:

| Helper | Baris |
|---|---|
| `dariReviews()` | ±154–172 |
| `dariApprovals()` | ±189–205 |
| `dariAuditLogs()` | ±214–223 |
| `dariPemusnahan()` | ±242–262 |
| `dariBalasanMasukan()` | ±274–284 |

Rantai `merge()` di baris 96–100 **tidak disentuh** — ia sudah benar begitu
kelima sumbernya konsisten.

**Merge lain sudah aman, tidak perlu diubah:** `NonaktifController.php:244`
(mulai dari `collect()`), `DocumentFeedbackService.php:151`,
`DocumentParticipantResolver.php:91,112` (model ke model).

**Uji.** `tests/Feature/LogPesanTest.php` — database yang HANYA punya baris
pemusnahan (tanpa review `needs_revision`, tanpa approval ditolak, tanpa audit
`document.reassign_review`), lalu `GET log-dokumen/pesan` harus 200. Tanpa
perbaikan test ini 500.

---

## Butir 13 — Edisi tidak naik saat revisi menyentuh 5 (modul **Informasi**)

**Aturannya sudah ada dan sudah benar**, terpusat di
`app/Services/DocumentService.php:145-154`:

```php
public static function nextEditionRevision(int $edisi, int $noRevisi): array
{
    return $noRevisi + 1 >= 5 ? [$edisi + 1, 0] : [$edisi, $noRevisi + 1];
}
```

Modul **dokumen mutu** memakainya (`Document::revisiSaatKirim()` →
`DocumentService::tandaiTerkirim()`), jadi Edisi 1 Rev 4 → Edisi 2 Rev 0. Benar.

**Modul Informasi punya implementasi kedua yang salah.** Tiga titik:

1. `resources/views/informasi/_form.blade.php:57,63` — `+ 1` telanjang:
   ```blade
   <input name="edisi"     value="{{ old('edisi', $induk?->edisi ?? 1) }}" …>
   <input name="no_revisi" value="{{ old('no_revisi', $induk ? $induk->no_revisi + 1 : 0) }}" …>
   ```
   Pada induk Edisi 1 Rev 4 form memuat **Edisi 1 Rev 5**.
2. `app/Http/Requests/StoreInformasiRequest.php:64-65` — `no_revisi` `max:99`
   (modul dokumen `max:4`), jadi 5 lolos validasi.
3. `app/Http/Controllers/InformasiController.php:140-141` — simpan apa adanya.

Kategori terdampak (`app/Models/Informasi.php:80-85`): `kebijakan`,
`instruksi_ktt`, `ibpr`. Kategori lain tak punya kolom edisi/revisi.

**Perbaikan — pakai helper yang sudah ada, jangan tulis rumus kedua.**

1. `_form.blade.php`, tiru pola `documents/_modal-revisi.blade.php:24-26`:
   ```blade
   @php([$edisiBaru, $revisiBaru] = $induk
       ? \App\Services\DocumentService::nextEditionRevision(max(1, (int) $induk->edisi), (int) $induk->no_revisi)
       : [1, 0])
   ```
   lalu pakai `$edisiBaru` / `$revisiBaru` sebagai default kedua input.
2. `StoreInformasiRequest`: `no_revisi` → `max:4`.
3. `_form.blade.php:20` — teks konfirmasi SweetAlert sekarang mengutip
   `labelRevisi()` versi **lama**. Tambahkan janji versi baru
   ("akan menjadi Edisi X Rev Y"), sejajar dengan `_modal-revisi.blade.php:31`.

**Periksa DULU sebelum mengunci validasi:**
```sql
SELECT id, kategori, edisi, no_revisi FROM informasi WHERE no_revisi > 4;
```
Bila ada baris warisan, `max:4` akan memblokir pembaruannya. Laporkan ke user
dan tawarkan normalisasi sekali jalan sebelum melanjutkan.

**Uji.** Informasi `kebijakan` Edisi 1 Rev 4 → buka Perbarui → prefill Edisi 2
Rev 0; POST `no_revisi=5` → 422.

---

## Butir 11 — Spinner (referensi CSS dari user)

**Kondisi sekarang.** Tidak ada indikator loading global, tidak ada skeleton.
Yang ada hanya tiga `spinner-border` Bootstrap ad-hoc dan satu progress bar:

- `resources/views/documents/edit.blade.php:131` — `x-show="previewing"`
- `resources/views/documents/fields/_repeatable_group.blade.php:142` — `x-show="uploading"`
- `resources/views/review/show.blade.php:86-88` — tombol Analisis AI, state `loading`
- `resources/views/job_executions/index.blade.php:140-147` — `.progress` (biarkan)

**Buat `resources/views/partials/_spinner.blade.php`**, meniru idiom yang sudah
dipakai `partials/_pita-style.blade.php` (`@once` + `@push('styles')`) agar
CSS-nya hanya tercetak sekali per halaman.

Catatan atas referensi CSS user, dua hal yang harus dikoreksi:

- `animation-timing-function: cubic-bezier;` **tidak valid** — `cubic-bezier`
  wajib berargumen. Browser mengabaikan baris itu dan jatuh ke `ease`.
  Pakai `cubic-bezier(.4, 0, .2, 1)`.
- `div { position:absolute; top:50%; left:50% }` di referensi menyasar SEMUA
  `div`. Jangan disalin apa adanya — batasi ke kelas varian overlay.

Isi partial:

- SVG lingkaran dengan keyframes `stroke-dasharray` / `stroke-dashoffset`
  persis dari referensi (1 98 / -105 → 80 10 / -160 → 1 98 / -300, 1.2s,
  infinite).
- Stroke memakai `<linearGradient>` `--su-orange1 (#ea580c)` → `--su-orange2
  (#facc15)`, sama dengan `.btn-pp` di `layouts/app.blade.php:276-288`.
  Token `--su-*` theme-invariant, jadi otomatis benar di tema gelap.
- Ukuran lewat CSS var `--sp-size` (default 40px), bukan lebar tetap.
- Dua varian dalam satu partial:
  - `inline` — pengganti `spinner-border`.
  - `overlay` — backdrop `rgba` + spinner di tengah, `role="status"`,
    `aria-busy="true"`, `<span class="visually-hidden">Memuat…</span>`.

**Pemakaian.** Ganti tiga `spinner-border` di atas, lalu pasang varian overlay
pada aksi yang benar-benar membuat pengguna menunggu: Preview wizard, unggah
lampiran, dan tombol Analisis AI (`review/show` + `review-md/show`).
Digerakkan state Alpine yang **sudah ada** (`previewing`, `uploading`,
`loading`) — tanpa JavaScript baru.

---

## Butir 12 — Halaman error 403 & 504

**Kondisi sekarang.** `resources/views/errors/` belum ada;
`bootstrap/app.php:37-39` `withExceptions` kosong. Semua error memakai view
bawaan framework, tanpa branding sama sekali.

### Langkah 1 — recolor ilustrasi (sekali jalan)

Sumber: `illustrations/light/403.png` dan `illustrations/light/504.png`.
Keputusan user: **navy gelap ikut digeser** ke coklat-orange gelap (bukan
dipertahankan biru).

Skrip PHP GD sekali pakai (ditaruh di scratchpad, **tidak** di-commit):

- Per pixel RGB → HSL.
- Bila `saturation > 0.15` **dan** hue ∈ [185°, 260°] → set hue = **22°**
  (hue `#ea580c`), pertahankan S dan L. Navy gelap ikut karena rentang hue-nya
  sama, hanya L-nya rendah — hasilnya coklat-orange gelap.
- Abu-abu dan putih (S rendah) tidak tersentuh — bayangan, kaca jam pasir, dan
  latar tetap netral.
- `imagealphablending(false)` + `imagesavealpha(true)` supaya transparansi utuh.

Keluaran → `public/images/errors/403.png` dan `public/images/errors/504.png`.
**Wajib dilihat mata sebelum lanjut** — bila hasilnya keruh, geser ambang
saturasi atau rentang hue, bukan menambah kode.

### Langkah 2 — view

- `resources/views/errors/403.blade.php` dan `resources/views/errors/504.blade.php`.
- `@extends('layouts.guest')` — **bukan** `layouts.app`. 403 kerap terjadi saat
  sesi/otorisasi bermasalah, dan `guest` sudah mendeklarasikan
  `--su-orange1/2` di baris 14 sehingga tombolnya tetap berwarna tema.
- Gambar `max-width:100%; height:auto`, tombol "Kembali ke Beranda" `.btn-pp`,
  Bootstrap Icons (nol emoji — CLAUDE.md §13).
- Teks Indonesia:
  - 403 — "Akses Ditolak. Kamu tidak punya wewenang membuka halaman ini."
  - 504 — "Server Tidak Merespons. Permintaanmu terlalu lama diproses, coba lagi sebentar."
- `bootstrap/app.php` **tidak perlu disentuh** — Laravel otomatis memakai
  `errors/{status}.blade.php` bila ada.

**Uji.** Buka rute yang di-`abort(403)` (mis. `documents/staff-status` sebagai
Non-Staff, lihat `DocumentStaffStatusController.php:27-30`). Untuk 504, panggil
`abort(504)` lewat tinker atau route uji sementara — **hapus rute ujinya sesudah
itu**.

---

## Butir 16 — Dashboard MD disamakan dengan SH

**Temuan yang mengubah cara mengerjakannya:** "MD" **bukan** `jabatan`, melainkan
**role** `management_development` (`RolePermissionSeeder.php:35`) dengan
`users.jabatan = NULL` (`AdminUserSeeder.php:34`). Akibatnya ketiga bendera di
`DashboardController.php:29-34`:

```php
$isCreator  = $user->jabatan === 'group_leader';
$isPjo      = $user->jabatan === 'pimpinan';
$isDeptHead = in_array($user->jabatan, ['section_head', 'departemen_head'], true);
```

semuanya `false` untuk MD → MD jatuh ke cabang `else` bersama Admin dan
Non-Staff. Itulah sebab dashboard-nya kopong.

**Izin MD** (`RolePermissionSeeder.php:119-129`): `document.review_md`,
`document.request_revision`, `document.feedback_respond`, `document.view_all`,
`audit.view`. **Tidak punya** `document.approve`, **tidak punya** `document.create`.

**Keputusan user:** jangan ubah menu/tautan — cukup **tambahkan widget milik SH
ke MD**, disesuaikan privilege MD.

### Perubahan

1. `DashboardController.php` — tambah satu bendera:
   ```php
   $isMd = $user->can('document.review_md');   // lebih tahan rename daripada hasRole()
   ```
   kirim ke view bersama tiga bendera yang sudah ada (baris 219–221).

2. `dashboard.blade.php:353-412` — **inti butir ini.** Sekarang:
   ```php
   $adaMeja    = $menungguDiMeja->isNotEmpty() || $isCreator || $isDeptHead || $isPjo;
   $adaMasukan = $masukanWidget->isNotEmpty() || $user->jabatan === STAFF || $isDeptHead || $isPjo || $isCreator;
   ```
   Untuk MD keduanya hanya `true` bila koleksinya kebetulan tidak kosong,
   sehingga grid runtuh jadi satu kolom penuh. Tambahkan `|| ($isMd ?? false)`
   pada keduanya → MD selalu mendapat grid dua kolom 70/30 seperti SH:
   `_widget-meja` + `_ketersediaan`, lalu `_widget-masukan` + `_widget-log`.
   (`_widget-distribusi` dan `_widget-sebaran` sudah aktif untuk MD lewat
   `dashboardPenuh()`.)

3. `dashboard.blade.php:288-311` — empat kartu. Untuk MD, kartu **"Perlu
   Disetujui"** tidak boleh muncul (MD tak punya `document.approve`). Gantikan
   dengan **"Perlu Diperiksa"** → `route('review-md.index')`, hitungan dari
   dokumen berstatus `verifikasi_md`. Tiga kartu lainnya sama dengan SH:
   Akun Menunggu · Berlaku · Sedang Revisi.
   Kartu "Total Dokumen" MD yang sekarang sudah menunjuk
   `documents.staffStatus` (`dashboardPenuh()` bernilai true untuk MD) —
   **pertahankan**, itu fitur pembeda MD.

4. `dashboard.blade.php:75-79` — tombol hero untuk MD:
   "Mulai Memeriksa" → `review-md.index` (utama, hanya bila antrean > 0) dan
   "Status Dokumen Staff" → `documents.staffStatus`.

5. **Data antrean.** Periksa `app/Services/AntreanTugas.php` — bila belum ada
   kunci untuk `verifikasi_md`, tambahkan satu kunci (`periksa`) di sana.
   Jangan query langsung dari blade.

**Yang TIDAK berubah:** menu sidebar, hak akses, `DocumentStaffStatusController`
(MD sudah lolos gate-nya lewat `document.view_all`).

**Uji.** Login `MD-0001`, bandingkan berdampingan dengan akun SH: struktur grid,
jumlah widget, dan kartu harus setara; pastikan tidak ada satu pun tombol menuju
approve atau create.

---

## Butir 17 — Optimalkan konfigurasi AI audit

Ringkas di sini, detail di **`docs/PLAN-BUTIR-17-AI.md`**.

Masalah inti: seluruh pembedaan antar jenis dokumen mutu hanya berupa satu
`match` berisi 1–2 kalimat per jenis di `AbstractAiReviewer::typeContext()`.
Model tidak diberi tahu **apa yang perlu diulik** dari SOP vs IK vs SP vs JSA,
sehingga temuannya generik.

Arah perbaikan: pindahkan rubrik dari kode ke berkas teks
(`docs/ai/jenis/{SOP,IK,SP,JSA}.md`), dengan fallback ke teks `match` yang
sekarang bila berkasnya tak ada. Menyetel AI jadi soal mengedit teks, bukan
mengubah PHP. Sekalian membereskan dua cacat: instruksi audit foto lampiran yang
tidak pernah menerima gambarnya, dan duplikat berkas `docs/AI/` vs `docs/ai/`.

---

## Butir 15 — Koneksi mobile + Mailpit

Ringkas di sini, detail di **`docs/PLAN-BUTIR-15-MOBILE.md`**.

Prinsip yang ditetapkan user: **mobile read-only** — tidak boleh membuat dokumen,
tidak boleh menyetujui. Pengecualian: GL SHE/Plant meninjau JSA, melihat daftar
JSA berlaku, dan GL/SH/DH dept terkait melihat riwayat pekerjaan.

Lubang terbesar: **notifikasi lonceng tak punya endpoint API sama sekali**,
padahal `DocumentNotification` mengklaim tampil di mobile.

Mailpit: `.env` diarahkan ke `127.0.0.1:1025`, konfigurasi Hostinger
di-comment (bukan dihapus).

---

## Verifikasi menyeluruh (sesudah seluruh butir selesai)

```bash
php artisan test
php artisan route:list --path=api
php artisan config:clear && php artisan view:clear
```

Manual, dengan `php artisan serve`, `php artisan queue:work`, dan Mailpit aktif:

1. Login `MD-0001` → dashboard dua kolom, widget lengkap, tanpa tombol approve.
2. Musnahkan satu dokumen → buka `log-dokumen/pesan` → 200, bukan 500.
3. Informasi `kebijakan` Edisi 1 Rev 4 → Perbarui → prefill Edisi 2 Rev 0.
4. Akses rute terlarang → halaman 403 berilustrasi orange; `abort(504)` → 504.
5. Klik Preview di wizard → overlay spinner orange muncul lalu hilang.
6. Setujui satu dokumen → surel `[SmartPro] …` mendarat di Mailpit (`:8025`).
7. `curl -H "Authorization: Bearer …" localhost:8000/api/notifications` → JSON.
8. Analisis AI pada JSA cacat → temuan `major` pada `section_key: analisa`.
