# PLAN A — Kesadaran Tugas

> Bagian 1 dari 3. Dikerjakan **pertama**, karena bagian B & C menyentuh kolom
> Aksi yang sama dan akan menulis ulang hasil bagian ini bila urutannya dibalik.
>
> Berkaitan: `PLAN-DISTRIBUSI-DAN-LOG-PESAN.md` (B) · `PLAN-PERBAIKAN-DAN-RAPIKAN-UI.md` (C)

## Masalah

Pengguna tidak tahu ada pekerjaan yang menunggu sampai ia membuka menu satu per
satu. Lonceng memang ada, tapi ia catatan **peristiwa**, bukan daftar **tugas**:

- Notifikasi "perlu ditinjau" tetap tergantung di lonceng meski dokumennya sudah
  ditinjau — `DocumentNotification` tak pernah menariknya kembali.
- Persetujuan akun **tidak pernah mengirim notifikasi sama sekali**;
  `UserApprovalController` tak memanggil `notify()` di satu titik pun. PJO hanya
  tahu ada pendaftar bila kebetulan membuka dashboard.
- `notifications.data` tak menyimpan jenis peristiwa; kunci yang ada hanya
  `document_id, doc_number, title, message, icon, route`. Jenisnya cuma bisa
  ditebak dari ikon (`DocumentNotification::judulAksi()`).

Karena itu sumber modal dan badge **bukan** isi lonceng, melainkan hitungan
antrean langsung. Lonceng tetap seperti sekarang, tak disentuh.

---

## A1. Satu sumber antrean

Berkas baru **`app/Services/AntreanTugas.php`**.

```php
/**
 * Pekerjaan yang MASIH menunggu seorang pengguna — bukan riwayat peristiwa.
 *
 * Satu sumber untuk dua pemakai: badge angka di sidebar dan modal saat login.
 * Dipisah dari lonceng dengan sengaja: lonceng mencatat apa yang PERNAH terjadi
 * dan tetap tergantung setelah tugasnya selesai, sedangkan yang dibutuhkan di
 * sini adalah apa yang BELUM selesai.
 *
 * @return array<string, array{label: string, jumlah: int, route: string, icon: string}>
 *         Hanya kunci yang jumlahnya > 0. Antrean kosong = array kosong.
 */
public function untuk(User $user): array
```

Seluruh query **menyalin yang sudah ada** — jangan menulis rumus kedua:

| Kunci | Hitungan | Salin dari | Penjaga | Rute | Ikon |
|---|---|---|---|---|---|
| `revisi` | `rejected` **atau** (`revises_document_id` terisi **dan** `draft`), disaring `created_by` bila bukan `document.view_all` | `DocumentRevisionController::revisions()` (:244-252) | `can('document.create')` | `documents.revisions` | `bi-arrow-repeat` |
| `nonaktif` | `Document::menungguNonaktif($user)->count()` | sudah ada, kini dipindah dari view | `Document::tahapNonaktifUntuk($user) !== []` | `nonaktif.index` | `bi-slash-circle` |
| `masukan` | `DocumentFeedback::belumDitindak()->whereHas('document', fn ($q) => $q->terlihatOleh($user))->count()` | `DocumentLogController::masukan()` (:53) + `DocumentFeedback::scopeBelumDitindak` (:68-71) | `$user->dashboardPenuh()` | `log.masukan` | `bi-chat-left-dots` |
| `tinjau` | `whereIn('status', ['waiting_for_review','in_review'])`, disaring `reviewer_id` bila bukan `document.view_all` | `DashboardController.php:63-66` | **`Gate::allows('review-access')`** — lihat catatan di bawah | `review.index` | `bi-clipboard-check` |
| `tinjau_md` | `Document::where('status', 'verifikasi_md')->count()` — seluruh departemen, tanpa penyaring | `MdReviewController::index()` (:39-44) | `can('document.review_md')` | `review.md` | `bi-spellcheck` |
| `setujui` | `where('status', 'pending_approval')`, disaring `approver_id` bila bukan `document.view_all` | `DashboardController.php:70-73` | `can('document.approve')` | `approvals.index` | `bi-patch-check` |
| `akun` | `User::pendingVisibleTo($user)->count()` | `User.php:340-345` | `can('user.approve_registration')` | `users.pending` | `bi-person-check` |

Urutan kunci di atas = urutan tampil (pekerjaan sendiri lebih dulu, lalu
peninjauan, lalu administrasi).

### Catatan: satu bug diam ikut diperbaiki

`DashboardController.php:63` memakai `can('document.review')` saja. Izin itu
**tidak** dipegang GL SHE/Plant — mereka memakai `document.review_jsa`
(`User::canReviewJsa()`, `User.php:186-201`). Akibatnya GL SHE/Plant **tak
pernah melihat angka antrean JSA-nya di dashboard**, padahal menu "Tinjau
Dokumen" terbuka untuk mereka lewat gate `review-access`.

Pakai gate `review-access` (`AppServiceProvider.php:71`) yang sudah menampung
keduanya. Ini menyelaraskan angka dengan menunya sekaligus menutup celah itu.

### Dashboard ikut memakai service ini

Ubah `DashboardController::index()` agar `$queues` diisi dari `AntreanTugas`.

**Pertahankan nama kunci `review` / `approval` / `pending_users`** — ketiganya
dibaca `dashboard.blade.php:50-79`. Petakan dari kunci service ke kunci view;
jangan ganti nama kunci di view (tak ada untungnya, dan `DashboardRenderTest`
serta `DashboardIkhtisarTest` membacanya).

---

## A2. Badge angka di menu

Berkas: `resources/views/layouts/app.blade.php`.

**1. Hitung sekali.** Di blok `@php` yang sudah ada (baris 676-699), tambahkan:

```php
// Satu panggilan untuk seluruh sidebar. Layout ini dirender di SETIAP halaman,
// jadi menghitung per item menu berarti tujuh query yang berulang selamanya.
$antrean = app(\App\Services\AntreanTugas::class)->untuk($user);
$jml = fn ($kunci) => $antrean[$kunci]['jumlah'] ?? 0;
```

**2. Tambah parameter badge pada closure `$item()`** (baris 730-734). Closure ini
mengembalikan **string HTML mentah** yang dicetak dengan `{!! !!}`, jadi
angkanya wajib di-cast `(int)`:

```php
$item = function ($route, $label, $icon, $active, $badge = 0) {
    // Badge hanya dirender bila ada isinya: menu tanpa tugas tak boleh
    // menampilkan angka 0 — nol bukan kabar, cuma bising.
    $lencana = (int) $badge > 0
        ? '<span class="badge-soft badge-soft-danger ms-auto">'.(int) $badge.'</span>'
        : '';
    return '<li class="nav-item"><a class="nav-link '.$active.'" href="'.$route.'">'
        .'<div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center"><i class="bi '.$icon.'"></i></div>'
        .'<span class="nav-link-text ms-1">'.$label.'</span>'.$lencana.'</li>';
};
```

`ms-auto` bekerja di dalam `.nav-link` tanpa `d-flex` tambahan — buktinya caret
`ms-auto` pada menu lipat yang sudah ada (baris 824, 873, 914). Beri `$subItem()`
parameter yang sama dengan markup badge identik.

**3. Pasang di tujuh titik:**

| Baris | Menu | Argumen |
|---|---|---|
| 771 | Dokumen Revisi | `$jml('revisi')` |
| 843-855 | Persetujuan Nonaktif | `$jml('nonaktif')` |
| 877 | Masukan Lapangan | `$jml('masukan')` |
| 898 | Tinjau Dokumen | `$jml('tinjau')` |
| 904 | Tinjau Penulisan | `$jml('tinjau_md')` |
| 929 | Persetujuan Saya | `$jml('setujui')` |
| 934 | Persetujuan Akun | `$jml('akun')` |

**4. Hapus query inline di baris 843-855.** Blok itu memanggil
`Document::menungguNonaktif($user)->count()` langsung dari view. Sekarang sudah
tersedia lewat `$jml('nonaktif')`; sisakan `tahapNonaktifUntuk()` sebagai penjaga
tampil/tidaknya menu, buang `count()`-nya. View tidak boleh punya query sendiri.

**5. TANPA badge di Riwayat Pekerjaan** (baris 887). Menu itu catatan pasif —
tak ada tugas yang menunggu siapa pun di sana, jadi angka apa pun di situ akan
mengaku sebagai pekerjaan padahal bukan.

---

## A3. Modal tugas menunggu saat login

**1. Tandai login.** `app/Http/Controllers/Auth/LoginController.php:41` — satu
baris:

```php
// Flash session bertahan tepat satu request, jadi modal muncul sekali per
// login: tanpa kolom baru, tanpa middleware, dan tanpa perlu "menandai sudah
// dibaca" yang harus dibereskan sendiri.
return redirect()->intended(route('dashboard'))->with('antrean_awal', true);
```

**2. Partial baru** `resources/views/partials/_modal-antrean.blade.php` — modal
Bootstrap biasa:

- Judul: `Ada {{ count($antrean) }} hal yang menunggu Anda`
- Isi: `list-group` dari `$antrean`; setiap baris adalah `<a>` ke `route(...)`
  masing-masing, berisi ikon Bootstrap Icons, label, dan
  `<span class="badge-soft badge-soft-danger ms-auto">` berisi jumlah.
- Footer: satu tombol `Tutup`.
- Nol emoji, nol tanda panah dekoratif (CLAUDE.md §13).

**3. Panggil dari layout**, tepat sebelum `@stack('scripts')` (baris 1134):

```blade
@if (session('antrean_awal') && $antrean !== [])
    @include('partials._modal-antrean')
    <script>new bootstrap.Modal(document.getElementById('modalAntrean')).show();</script>
@endif
```

Bootstrap bundle sudah dimuat di baris 1047, jadi tak ada aset baru.

**4. Antrean kosong = tidak ada apa pun yang dirender.** Jangan tampilkan modal
"tidak ada tugas" — modal yang tak membawa kabar hanya melatih orang menutupnya
tanpa membaca.

---

## A4. Badge Riwayat pindah ke kolom Aksi

Berkas: `resources/views/informasi/index.blade.php`.

Sekarang badge `N riwayat` berada di kolom **Judul** (baris 82-87). Ia sudah bisa
diklik, tapi bentuknya badge — tak ada yang menyangka itu tombol.

**Pindahkan ke kolom Aksi** (baris 95-107), jadikan tombol sungguhan:

```blade
@if ($versiLama->isNotEmpty())
    <button type="button" class="btn btn-sm btn-outline-secondary"
            @click="buka = !buka" :aria-expanded="buka.toString()">
        <i class="bi bi-clock-history"></i> Riwayat ({{ $versiLama->count() }})
    </button>
@endif
```

**Kolom chevron di baris 70-78 TETAP.** Ia kolom tersendiri, dan menghapusnya
memaksa menyunting header, `$jumlahKolom`, serta `colspan="{{ $jumlahKolom - 1 }}"`
di baris 115 — tiga suntingan berisiko demi nol keuntungan yang terlihat. Chevron
menandai baris yang terbuka; tombol di Aksi yang membukanya. Keduanya sudah
berbagi `x-data="{ buka: false }"` di `<tbody>` (baris 68), jadi tak ada state
baru.

---

## A5. Tombol dropdown versi diperbesar

Berkas: `resources/views/documents/obsolete.blade.php`. **Pola yang sama persis
dengan A4.**

Sekarang: chevron `btn-link p-0` seukuran huruf (baris 56-63) menempel di kolom
nomor, dan badge `N versi` (baris 74-76) — yang bukan tombol — berdiri terpisah.
Dua penanda untuk satu hal, dan yang mencolok justru yang tak bisa diklik.

- Hapus baris 56-63 dan 74-76.
- Taruh satu tombol berlabel di kolom Aksi (baris 84):

```blade
@if ($versiDoc->count() > 1)
    <button type="button" class="btn btn-sm btn-outline-secondary"
            x-on:click="buka = ! buka" :aria-expanded="buka"
            aria-controls="versi-{{ $doc->id }}">
        <i class="bi" :class="buka ? 'bi-chevron-down' : 'bi-chevron-right'"></i>
        {{ $versiDoc->count() }} versi
    </button>
@endif
```

Chevron di sini menempel **inline**, bukan kolom tersendiri, jadi menghapusnya
tak menyentuh perhitungan `colspan="8"` di baris 149.

---

## Test

**Baru — `tests/Feature/AntreanTugasTest.php`:**

1. SH dengan 2 dokumen `waiting_for_review` → `untuk($sh)['tinjau']['jumlah'] === 2`.
2. **GL departemen SHE yang memegang JSA departemen lain ikut mendapat `tinjau`** —
   inilah kasus yang selama ini terlewat di dashboard. Wajib ada.
3. Non-Staff tanpa tugas → `untuk($staff) === []`.
4. Kunci yang jumlahnya 0 tidak muncul di hasil.
5. `POST route('login')` berhasil → response session punya `antrean_awal`.

**Harus tetap hijau tanpa diubah:** `DashboardRenderTest`,
`DashboardIkhtisarTest`, `DashboardWidgetTest`, `SpecDashboardV3Test` — keempatnya
membaca `$queues`, jadi merekalah bukti bahwa pemetaan kunci di
`DashboardController` benar.

## Verifikasi manual

1. Login sebagai GL → modal muncul memuat "Dokumen Revisi"; angkanya **sama
   persis** dengan badge sidebar. Muat ulang halaman → modal tidak muncul lagi.
2. Login sebagai GL departemen SHE yang memegang JSA departemen lain → badge
   "Tinjau Dokumen" terisi (sebelum perubahan ini selalu kosong).
3. Login sebagai Non-Staff → tidak ada modal sama sekali, tidak ada badge.
4. Menu Informasi → tombol `Riwayat (N)` ada di kolom Aksi dan membuka baris versi.
5. Dokumen Tidak Berlaku → tombol `N versi` ada di kolom Aksi, ukurannya sama
   dengan tombol lain di baris itu.
