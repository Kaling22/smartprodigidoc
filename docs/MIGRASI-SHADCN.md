# MIGRASI — Halaman Blade → shadcn/ui

> Fase 4–13. Prasyarat: Fase 0–3 di `SETUP-SHADCN.md` sudah selesai dan seluruh gerbangnya hijau.

---

## Pakem preservasi — sembilan aturan yang tidak bisa ditawar

Dilanggar satu saja, ada unsur SmartPro yang hilang **tanpa ada tes yang merah**.

### P1 — Skema JSON tetap penggerak tunggal

Form React membaca `schema_json` dari props, bukan menyalin strukturnya jadi TSX statis.

**Uji:** tambah satu seksi baru ke `schema_json` lewat SQL → seksi itu **harus muncul sendiri** di form tanpa satu baris kode pun disentuh. Kalau tidak muncul, P1 dilanggar.

### P2 — Mesin cetak haram disentuh

```
app/Services/Print/{PdfRenderer,ArsipPdf,ArsipPenggabung}.php
app/Services/{ActivityPrintLayout,JsaPrintLayout}.php
app/Http/Controllers/{DocumentPdfController,DocumentExportController}.php
resources/views/documents/print/**            (9 berkas)
resources/views/documents/export-excel.blade.php
resources/views/emails/**
template_sop.blade.php   template_sp.blade.php
template_ik.blade.php    template_jsa.blade.php
```

**Uji tiap fase — DIKOREKSI 2026-08-28.** `git diff --stat main -- <daftar di atas>`
**bukan** penjaga yang sah: `main` di repo ini lebih tua dari titik nol migrasi,
jadi diff-nya sudah tidak kosong sejak commit Fase 0 (`2e9abef` — `PdfRenderer.php`
+ `PdfStream.php`). Kalau tetap memakai diff, acuannya `2e9abef`.

Dua penjaga yang benar-benar bekerja:

```powershell
md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23 berkas mesin cetak
php artisan smartpro:cetak-baseline              # render ulang & bandingkan
```

md5 **mentah** antar PDF juga tidak bisa dipakai: tiap render DomPDF menulis
`/CreationDate`, `/ModDate`, dan `/ID` yang selalu berbeda. Di luar ketiganya
render ulang menghasilkan berkas berukuran persis sama dan byte-identik —
sudah diukur obyek per obyek. `smartpro:cetak-baseline` menetralkan ketiganya
lebih dulu.

### P3 — Peta status dari props

`statusMeta` dan `statusLabels` dibagikan lewat `HandleInertiaRequests`. Tidak boleh ada warna atau label status yang diketik di TSX.

Alasannya sudah tercatat di komentar `Document.php`: peta ini pernah tersalin di empat Blade dan menyimpang, membuat `verifikasi_md` tampil abu-abu seperti Draft.

### P4 — Otorisasi tetap di server

Middleware `can:` di `routes/web.php` tidak boleh dilepas. Props `auth.can` hanya untuk menyembunyikan tombol, **bukan** pengganti otorisasi.

### P5 — Logika kandidat peserta tetap di Service

`DocumentWizard::coAuthorCandidates()`, `DocumentParticipantResolver`, `ReviewerAvailability`, `UserOffDay::aktifPada()`.

React menerima daftar kandidat jadi, mengirim balik id. **Nol penyaringan kandidat di klien.**

Ingat kekhususan `peninjau_penyetuju` (SP/IK): satu nilai mengisi `reviewer_id` **dan** `approver_id`.

### P6 — Jenis unggahan tidak boleh terlupa

FK dan PX lewat jalur `unggahanSaja` / `DocumentType::isUnggahan()`. Tiap fase yang menyentuh dokumen wajib diuji dengan salah satu jenis ini juga, bukan cuma SOP.

> **Koreksi:** `schema_json` FK/PX **bukan NULL** melainkan `[]`. Unggahan dideteksi lewat kolom `document_types.class = 'unggahan'` (`DocumentType.php:70`) — jangan menulis pemeriksaan berbasis NULL.

### P7 — Jumlah tes tidak boleh berkurang

**608 tetap 608** (607 lulus + 1 dilewati, 4133 asersi — angka 527 di dokumen SETUP berasal dari commit lama `f9d58f4` dan sudah usang). `assertSee` boleh berubah jadi `assertInertia`, tapi tidak boleh dihapus.

> **Diperbarui Fase 3 (2026-08-28):** patokannya kini **615**. Yang bertambah
> `tests/Feature/NavigasiSidebarTest.php` (7 tes / 69 asersi) — pengunci menu
> per peran. Aturannya tak berubah: jumlah boleh NAIK, tak boleh turun.
>
> **Diperbarui Fase 4 (2026-08-28):** patokannya kini **624** (623 lulus + 1
> dilewati, 4279 asersi). Yang bertambah `tests/Feature/AutentikasiInertiaTest.php`
> (9 tes) — komponen tiap rute tamu, kunci galat `nrp`, batas percobaan login,
> penjagaan akun belum aktif.
>
> **Diperbarui Fase 5 (2026-08-29):** patokannya kini **625** (624 lulus + 1
> dilewati, 4454 asersi). Sebelas tes di sepuluh berkas berpindah dari membaca
> MARKUP dashboard ke membaca PROPS; yang bertambah satu —
> `SpecDashboardV3Test::test_warna_status_dashboard_datang_dari_status_meta`,
> pengganti pemindaian `.badge.bg-*` yang di halaman Inertia lolos trivial
> (payload JSON meng-escape tanda kutipnya, sehingga regexnya mustahil cocok).
> Dua penolong baru di `Tests\TestCase` supaya sembilan berkas tak menulis
> pembaca props sendiri-sendiri: `propsInertia()` dan `menuSidebar()`.
>
> **Diperbarui Fase 7 (2026-08-29):** patokannya kini **641** (640 lulus + 1
> dilewati, 4704 asersi). Yang bertambah `tests/Feature/PengaturanInertiaTest.php`
> (9 tes) — komponen tiap rute, **kunci API yang tak boleh ikut ke payload**,
> kelengkapan `penyedia`/`kolomTersedia`/`rupa`, urutan jenis yang mengikuti
> `DocumentType::RUPA`, dan ketiga rute yang tetap 403 bagi bukan Admin. Dua
> asersi `ManajemenUserDanPemusnahanTest` berpindah gaya — nol dihapus.
>
> **Diperbarui Fase 6 (2026-08-29):** patokannya kini **632** (631 lulus + 1
> dilewati, 4550 asersi). Yang bertambah `tests/Feature/PenggunaInertiaTest.php`
> (7 tes) — komponen tiap rute, **bentuk paginator utuh** (`->through()`, bukan
> `->map()`), kelengkapan peta `roleLabels`, penyaring yang menyaring di SERVER,
> dan penjagaan `password`/`remember_token`/kunci mentah `jabatan` tak pernah
> ikut ke payload. Tiga asersi berpindah gaya (`MdAccountConfigTest`,
> `UserApprovalTest`, `ManajemenAksesTest`) — nol dihapus.
>
> **Diperbarui Fase 9 (2026-08-30):** patokannya kini **660** (659 lulus + 1
> dilewati, 5118 asersi). Yang bertambah `tests/Feature/WizardInertiaTest.php`
> (10 tes) — komponen & kelengkapan props ketiga rute wizard, **pakem P1 diuji
> sungguhan** (seksi disisipkan ke `schema_json` lewat basis data lalu
> dibuktikan muncul sendiri), **tiap tipe seksi & tipe kolom punya komponennya**
> (yang tak terpetakan jatuh diam-diam ke kolom teks), kebocoran kandidat
> peserta alur (`password`/`remember_token`/kunci mentah `jabatan`), bentuk
> kiriman JSON bersarang untuk `saveStep` **dan** `autosave`,
> `ReviewerAvailability::PAPAN` + tanggal pita yang sudah jadi teks, jenis
> unggahan FK (pakem P6), dan 403 di luar jangkauan (pakem P4). Asersi di
> sebelas berkas berpindah gaya — nol dihapus. Penolong baru `seksiWizard()`
> di `Tests\TestCase`.
>
> **Diperbarui Fase 10 (2026-08-30):** patokannya kini **673** (672 lulus + 1
> dilewati). Yang bertambah `tests/Feature/TinjauInertiaTest.php` (13 tes) —
> komponen & bentuk paginator ketiga antrean, **satu komponen melayani tiga
> pintu masuk** (tinjau SH/DH, tinjau MD, masukan sejawat), kebocoran kandidat
> pengalihan + pemberi masukan sejawat, **`rich_text` yang dibersihkan di
> SERVER sebelum masuk props**, pakem P1 lewat penyisipan seksi ke
> `schema_json`, `anotasiLama` yang membuang baris tanpa komentar, papan
> pengalihan yang sudah jadi dari server, jenis unggahan FK (pakem P6), dan 403
> di empat pintu di luar jangkauan (pakem P4), dan **bentuk kiriman JSON
> bersarang** yang dibuktikan langsung ke penerimanya lewat `postJson` (penanda
> AI berpindah dari STRING `'1'` ke BOOLEAN). Asersi di lima berkas berpindah
> gaya — nol dihapus; `UrutTabelTest` bertambah empat halaman.
>
> **Diperbarui Fase 11 (2026-08-30):** patokannya kini **684** (683 lulus + 1
> dilewati, 5568 asersi). Yang bertambah `tests/Feature/DistribusiInertiaTest.php`
> (11 tes) — komponen kelima layar, **kebocoran diperiksa pada HTML MENTAH**
> (`password`, `remember_token`, dan `arsip_path` — jalur berkas privat yang
> sengaja tak bisa ditebak dari nomor dokumen), rincian pembaca yang diratakan
> jadi empat kolom, cakupan yang menempel di barisnya, pengurutan & penyaringan
> yang tetap di server, `revisiKirim` + `tanggal_efektif` yang sudah jadi teks,
> **bentuk kiriman JSON bersarang** lembar Catatan Revisi lewat `postJson`,
> jenis unggahan FK (pakem P6), dan 403 di empat pintu (pakem P4). Tiga asersi
> `DistribusiInformasiTest` berpindah dari `assertSee`/`viewData` ke props —
> nol dihapus; `UrutTabelTest` bertambah satu halaman.
>
> **Diperbarui Fase 12 (2026-08-30):** patokannya kini **695** (694 lulus + 1
> dilewati, 5772 asersi). Yang bertambah
> `tests/Feature/InformasiLogInertiaTest.php` (11 tes) — komponen kedelapan
> layar, **kebocoran diperiksa pada HTML MENTAH** (`password`,
> `remember_token`, `arsip_path`, dan **`informasi.file_path`** — kembaran
> `arsip_path`: jalur berkas privat yang nama berkasnya diacak Laravel),
> **kolom Informasi yang datang dari `informasi_kategori.kolom_json`** (padanan
> pakem P1 di modul ini — diubah lewat basis data, props ikut sendiri), bentuk
> paginator utuh kelima daftar, penyaringan & pengelompokan yang tetap di
> server, jenis unggahan FK (pakem P6), dan 403 di empat pintu (pakem P4).
> Dua belas asersi berpindah gaya di empat berkas — nol dihapus; **dua di
> antaranya ternyata sudah HIJAU TRIVIAL** sejak halamannya Inertia
> (`assertDontSee` atas tombol yang kini dirakit React mustahil merah lagi).
>
> **Diperbarui Fase 8 (2026-08-29):** patokannya kini **650** (649 lulus + 1
> dilewati, 4942 asersi). Yang bertambah `tests/Feature/DokumenInertiaTest.php`
> (8 tes) — komponen tiap rute, bentuk paginator utuh, kebocoran
> `password`/`remember_token`, rupa status dari `STATUS_META`, kelima `boleh_*`
> per baris yang berbeda per peran, penyaringan masukan di server, **jenis
> unggahan FK** (pakem P6), dan dokumen di luar jangkauan yang tetap 403 —
> ditambah `UrutTabelTest::test_kolom_urut_di_halaman_inertia_memakai_kunci_yang_sah`
> (1 tes). Asersi di sebelas berkas berpindah gaya — nol dihapus.

Kalau sebuah tes terasa "tidak relevan lagi", itu tanda ada perilaku yang hilang — telusuri, jangan hapus.

### P8 — Satu fase, satu commit, lalu berhenti

Sesuai CLAUDE.md §5. Jangan menumpuk dua fase.

### P9 — Berkas `components/ui/` hasil generate tidak disunting

Kustomisasi lewat CSS variable dan `variant` cva.

---

## Pola kerja tiap halaman

Enam langkah, sama untuk semua:

1. **Controller** — `return view('x', [...])` → `return Inertia::render('X', [...])`.
   **Array props-nya tidak berubah sama sekali.** Kalau kamu merasa perlu mengubah bentuk props, berhenti — kemungkinan besar logikanya sedang ikut terbawa pindah, dan itu pelanggaran P5.
2. **Halaman** — tulis `resources/js/pages/X.tsx`.
3. **Komponen** — `npx shadcn@latest add <komponen>` seperlunya (lewat MCP).
4. **Tes** — ubah `assertSee()` di berkas tes terkait → `assertInertia(fn ($page) => $page->component('X')->has('...'))`.
5. **Verifikasi** — `php artisan test` hijau, `npm run build` bersih, `git diff --stat main -- <daftar P2>` kosong.
6. **Commit.**

---

## Tabel padanan Blade → shadcn

| Blade / Bootstrap / Alpine lama | shadcn / React |
|---|---|
| `@extends('layouts.app')` | `<AppLayout>` |
| `@extends('layouts.guest')` | `<AuthLayout>` |
| `<table class="table">` | `Table` + TanStack Table |
| `partials/_urut-th.blade.php` | kolom `sortable` DataTable → param query Inertia |
| `<div class="modal">` | `Dialog` |
| `Swal.fire({...})` konfirmasi | `AlertDialog` |
| `partials/_badge-status` | `<StatusBadge status={...} />` (baca `statusMeta` props) |
| `<i class="bi bi-*">` | ikon `lucide-react` |
| `x-data` / `x-show` / `x-cloak` | `useState` React |
| `@error('field')` | `useForm().errors` Inertia |
| `{{ route('x') }}` | Ziggy, atau URL dikirim lewat props |
| `@can('...')` | props `auth.can` dari `HandleInertiaRequests` |
| `{{ session('success') }}` | props `flash` → `Sonner` toast |
| `<form method="POST">` | `useForm().post()` Inertia |
| `{{ $paginator->links() }}` | `Pagination` shadcn + props paginator Laravel |
| `@include('partials/_avatar')` | `<Avatar>` |
| `<iframe src="{{ route('documents.pdf', ...) }}">` | **tetap `<iframe>` dengan src yang sama** |

---

## Fase 4 — Autentikasi (3 view) — ✅ SELESAI 2026-08-28

`auth/login`, `auth/register`, `auth/pending`.

Kemenangan murah yang memvalidasi `AuthLayout` + `useForm` Inertia + tampilan galat validasi.

Controller: `LoginController@create`, `RegisterController@create`, `PageController@pending`.

**Awas:** auth pakai **NRP**, bukan email. Rate-limit login (CLAUDE.md §15) tetap di server.

**Uji:** login benar → dashboard. Login salah → pesan galat tampil. Registrasi → status `pending` → halaman tunggu. Akun nonaktif → tertahan middleware `active`.

---

## Fase 5 — Dashboard (1 view + 6 partial widget) — ✅ SELESAI 2026-08-29

> Rincian lengkap (blok registry sumber tiap kartu, tiga penyimpangan sadar,
> logika yang pindah ke server, sebelas tes yang berubah gaya asersinya) ada di
> `docs/PROGRESS-SHADCN.md` §Fase 5.
>
> **Satu jebakan yang tak tertulis di bawah dan hampir memakan korban:** modal
> tugas pasca-login (`partials/_modal-antrean`) dipanggil dari
> `layouts/app.blade.php:1510`, BUKAN dari `dashboard.blade.php` — jadi ia
> lenyap begitu dashboard pindah, tanpa satu pun gerbang bicara.
> `AntreanTugasTest` yang menangkapnya. Ia kini props `antreanAwal` dari
> `HandleInertiaRequests` + `components/DialogAntrean.tsx` di `AppLayout`.
> **Pelajaran untuk fase berikutnya: periksa apa saja yang
> `layouts/app.blade.php` sisipkan DI SEKITAR halaman yang sedang dipindahkan,
> bukan cuma isi halamannya.**
>
> **Koreksi:** `matrix` + `jenisList` ternyata TAK dirender satu pun Blade
> (`grep` bersih di `resources/views/`). Keduanya tetap dikirim — props tak
> dikurangi — tapi kalimat "Chart `tren` dan `matrix` pakai komponen `Chart`"
> di bawah hanya benar untuk `tren`. Klaim serupa di CLAUDE.md §13 ("hex-nya
> juga jadi titik warna di matriks dasbor") ikut tidak benar.

`dashboard.blade.php` + `partials/_widget-{meja,masukan,distribusi,distribusi-tabel,log,sebaran}` + `_pita-cakupan` + `_ketersediaan`.

`DashboardController@index` (575 baris) mengirim **21 props**: `user`, `greeting`, `menungguDiMeja`, `masukanWidget`, `distribusiWidget`, `kalenderOff`, `offSaya`, `offAktif`, `stats`, `queues`, `isCreator`, `isPjo`, `isDeptHead`, `isMd`, `activities`, `tren`, `matrix`, `jenisList`, `sebaran`.

**Awas — empat jebakan yang tercatat di komentar kode:**

- `distribusiWidget` bernilai `null` bagi yang tidak berwenang, dan kartunya **tidak dirender sama sekali** — bukan dirender kosong.
- Widget kolom kanan **berbeda per jabatan**. Uji keempat peran.
- `kalenderOff` menerima `?bulan=YYYY-MM`, dan tombol bulannya **tautan biasa, bukan JS**. Di Inertia pakai `<Link>`, jangan diubah jadi fetch.
- Kartu aktivitas dibatasi 15 baris dan **digulir di dalam tinggi tetap** — jumlah baris tidak menentukan tinggi halaman.

Chart `tren` dan `matrix` pakai komponen `Chart` dari `dashboard-01`.

**Uji:** buka dashboard sebagai GL, SH, DH, PJO, Admin → bandingkan berdampingan dengan port 9091. Setiap angka statistik harus sama.

---

## Fase 6 — Pengguna & akses (7 view) — ✅ SELESAI 2026-08-29

`users/{index,create,edit,pending}`, `akses/{index,_form-profil}`, `account/info`.

Pola CRUD + DataTable yang jadi cetakan untuk fase-fase berikutnya. Kerjakan dengan rapi — sisanya menyalin pola ini.

Controller: `UserManagementController` (302 baris, termasuk `updateMd` dan `toggleStatus`), `UserApprovalController`, `AccessProfileController` (157 baris, termasuk `tetapkan`), `PageController@account` / `updateAccount`.

**Awas:** konfigurasi MD (`users.mdConfig`) dan penetapan profil akses (`akses.tetapkan`) adalah fitur admin tersendiri yang gampang terlewat karena tidak punya halaman sendiri.

**Uji:** buat user baru → sunting → ganti peran → toggle status → hapus. Setujui & tolak registrasi. Buat profil akses → tetapkan ke user → cek menu user itu berubah.

> **Hasil.** Enam halaman TSX (`Users/{Index,Create,Edit,Pending}`,
> `Akses/Index`, `Account/Info`); `akses/_form-profil` yang dulu di-`@include`
> dua kali jadi SATU komponen `DialogProfil`. Nol komponen registry baru, nol
> paket npm baru. Yang pindah ke server: `User::ROLE_LABELS` +
> `ROLE_DESKRIPSI` (dulu tersalin di TIGA Blade), `User::barisAdmin()`, dan
> `DocumentType::RUPA` sebagai props. Rincian lengkap + gerbangnya di
> `docs/PROGRESS-SHADCN.md` §Fase 6.
>
> **Jebakan yang hampir memakan korban, catat untuk fase berikutnya:**
> memetakan isi paginator dengan `->map()` mengembalikan Collection biasa —
> daftarnya tetap tampil, hanya `links`/`from`/`to`/`total` yang lenyap,
> sehingga paginasinya mati tanpa satu pun galat. Pakai `->through()`.

---

## Fase 7 — Pengaturan & master data (3 view) — ✅ SELESAI 2026-08-29

`pengaturan/{penomoran,master,sistem}`.

Controller: `PengaturanController` (318 baris — `penomoran`, `simpanPenomoran`, `sistem`, `simpanAi`, `ujiEmail`, `ujiAi`, `bersihkanCache`), `MasterDataController` (153 baris — departemen, jenis dokumen, kategori informasi).

**Awas:**

- `pengaturan/penomoran` mengatur pola nomor dokumen lewat `DocumentNumberService`. Salah di sini merusak penomoran seluruh dokumen baru — uji dengan membuat dokumen baru sesudahnya dan periksa nomornya.
- `simpanJenis` menyunting `document_types`. **Jangan sekali pun menyediakan jalur yang bisa menimpa `schema_json`** dari UI kalau aplikasi lama tidak menyediakannya — periksa `SimpanJenisDokumenRequest` dan pertahankan persis daftar kolom yang boleh disunting.
- `ujiEmail` dan `ujiAi` memanggil layanan luar. Beri status pending di tombolnya (`useForm().processing`).

**Uji:** ubah pola penomoran → buat dokumen baru → nomor sesuai. Tambah departemen → muncul di dropdown pembuatan dokumen. Uji email → masuk Mailpit. Uji AI → balasan tampil. Bersihkan cache → tidak ada galat.

> **Hasil.** Tiga halaman TSX (`Pengaturan/{Penomoran,Master,Sistem}`). Satu
> komponen registry baru (`switch`), **nol paket npm baru**. Rincian lengkap di
> `docs/PROGRESS-SHADCN.md` §Fase 7.
>
> **Kebocoran yang ditutup — catat untuk setiap fase berikutnya.**
> `Pengaturan::ai()` memuat kunci API dalam bentuk TERBACA. Blade lama aman
> karena tak pernah mencetaknya; props Inertia tidak — seluruhnya tertulis di
> atribut `data-page` dan terbaca lewat "view source". Controller kini
> membuangnya (`Arr::except`). Ini kelas kesalahan yang sama dengan
> `password`/`remember_token` di Fase 6: **tiap larik/model yang pindah dari
> Blade ke props wajib diperiksa kolom per kolom.**
>
> **`documents/_modal-musnahkan-semua` SUDAH SELESAI di sini**, bukan di Fase
> 11 seperti daftar di bawah. Ia hanya di-`@include` dari
> `pengaturan/sistem.blade.php` dan tak dirender dari tempat lain mana pun;
> memindahkan layar Sistem tanpa membawanya berarti tombol Zona Berbahaya
> kehilangan jendelanya. Sama seperti pelajaran `_modal-antrean` di Fase 5:
> **periksa apa yang di-`@include` halaman yang sedang dipindahkan, dan cek
> `grep` siapa lagi yang memakainya — daftar fase di dokumen ini bisa keliru.**
>
> **Atribut `form="…"` di Master Data DIPERTAHANKAN.** HTML melarang `<form>`
> membungkus `<tr>`, dan React tak menambal larangan itu: bundel SSR
> (`smartpro:uji-render`) menghasilkan markup yang tetap dibaca parser peramban,
> yang memindahkan form itu keluar tabel diam-diam. Satu `<form>` kosong per
> baris + `form={idForm}` pada tiap `Input`, persis seperti Blade lama.
>
> **`ConfirmDialog` kini bisa dikendalikan dari luar** (`buka`/`onUbahBuka`),
> supaya tombol Simpan tetap `type="submit"` dan `required` peramban tetap
> bekerja. Fase 11 membutuhkan pola ini untuk "Ajukan Revisi wajib beralasan".

---

## Fase 8 — Daftar dokumen (8 view) — ✅ SELESAI 2026-08-29

`documents/{index,show,published,obsolete,revisions,staff-status}` + `partials/_badge-nomor-lama` + `_rincian-pembaca`.

Controller: `DocumentController@index` (dengan `scopeTerlihatOleh`, `scopeUrut`), `@show`, `DocumentRevisionController@{revisions,published,obsolete}`, `DocumentStaffStatusController@staffStatus`.

**Awas:**

- `scopeTerlihatOleh(User $user)` adalah aturan visibilitas dokumen per peran. Tetap di model — **jangan pernah menyaring daftar dokumen di React**.
- `scopeUrut` + `Document::KOLOM_URUT` menangani pengurutan kolom. Kirim kolom & arah lewat param query Inertia, bukan mengurutkan di klien — daftarnya berhalaman, dan mengurutkan di klien cuma mengurutkan satu halaman.
- Badge "Nomor Lama" (`_badge-nomor-lama`) untuk dokumen di luar pola resmi.
- `documents/show` memuat tombol PDF, modal masukan, tombol rollback, dan rincian pembaca.

**Uji:** buka daftar sebagai lima peran → jumlah dokumen yang terlihat sama dengan aplikasi lama. Urutkan tiap kolom. Halaman 2 dan seterusnya. Buka dokumen berlaku → PDF terbuka dan identik dengan baseline.

> **Awas — fase ini MENYERET lima berkas yang didaftarkan di Fase 11.**
> `_modal-{revisi,nonaktif,musnahkan,masukan}`, `_tombol-rollback`, dan
> `_masukan-list` di-`@include` dari `published`/`obsolete`/`show`, jadi ia tak
> bisa dipisah: daftar tanpa modalnya = tombol tanpa jendelanya. Jalankan
> `grep -rn "_modal-" resources/views/documents/{index,show,published,obsolete,revisions,staff-status}.blade.php`
> lebih dulu, dan kerjakan Fase 8 SENDIRIAN.

> **Hasil.** Enam halaman TSX (`Documents/{Index,Show,Published,Obsolete,
> Revisions,StaffStatus}`) + **tujuh** berkas Fase 11 yang ikut terbawa
> (kelima di atas, ditambah `partials/_rincian-pembaca`). Nol komponen registry
> baru, nol paket npm baru. Yang pindah ke server: `Document::barisDaftar()`,
> `AuditLog::AKSI_META`, `DocumentController::{barisMasukan,jadwalPembuat}()`,
> serta `statusOpsi`/`jenisBaru`/`judul`/`isGlDept`/`prefix` sebagai props.
> Rincian lengkap + gerbangnya di `docs/PROGRESS-SHADCN.md` §Fase 8.
>
> **Keempat Blade modal itu TETAP TIDAK BOLEH DIHAPUS.** `log/masukan.blade.php`
> masih meng-`@include` `_modal-revisi` + `_masukan-list`, dan
> `documents/rincianInformasi.blade.php` masih memakai `_rincian-pembaca` —
> keduanya baru pindah di Fase 11–12. Begitu pula `partials/_urut-th`,
> `_badge-status`, dan `_badge-nomor-lama`, yang masih dipakai `review/*`,
> `approvals/index`, `nonaktif/index`, `masukan-sejawat/show`, dan
> `documents/distribution`. **`grep` dulu, jangan menghapus berdasarkan daftar
> fase.**
>
> **Jebakan Radix yang akan berulang di Fase 10–12:** menu aksi (`DropdownMenu`)
> MELEPAS isinya begitu sebuah item dipilih, jadi jendela yang dirender di dalam
> menu ikut lenyap sebelum sempat terlihat. Polanya: itemnya cuma menyalakan
> saklar, jendelanya berdiri di luar menu dengan `buka`/`onUbahBuka` — sama
> seperti `ConfirmDialog` sejak Fase 7. Keempat `Dialog*` di
> `components/dokumen/DialogAksi.tsx` sudah menyediakan mode itu.
>
> **Dan satu pelajaran tentang tes:** sejak tautan urut dirakit di peramban,
> `assertSee('sort=nomor')` tak akan pernah bisa merah lagi — ia cuma hijau
> trivial. Penggantinya `UrutTabelTest::test_kolom_urut_di_halaman_inertia_…`,
> yang membaca berkas TSX-nya dan mencocokkan tiap `urut:` dengan
> `Document::KOLOM_URUT`. Fase berikutnya yang memasang kolom urut baru wajib
> menambah halamannya ke daftar di test itu.

---

## Fase 9 — Pembuatan & wizard dokumen (11 view) — ✅ SELESAI 2026-08-30

`documents/create`, `documents/edit` (29 KB), `documents/unavailable`, dan seluruh `documents/fields/`.

Ini inti SmartPro. Kerjakan **tidak terburu-buru**, dan pecah jadi sub-fase kalau perlu.

> **Hasil.** Tiga halaman TSX (`Documents/{Create,Edit,Unavailable}`) + **sebelas**
> komponen di `components/dokumen/fields/`. Satu paket npm baru — `quill@2.0.3`,
> versi yang PERSIS SAMA dengan CDN yang digantikannya (atas izin pemilik), di-
> `import()` dinamis sehingga hanya halaman wizard yang membayarnya dan gerbang
> render di Node tak pernah ikut memuatnya. Nol komponen registry baru. Rincian
> lengkap + gerbangnya di `docs/PROGRESS-SHADCN.md` §Fase 9.
>
> **KOREKSI daftar di bawah: renderernya SEBELAS, bukan sembilan — dan
> `Signature.tsx` TIDAK ADA di antaranya.**
> `documents/fields/_signature.blade.php` adalah KODE MATI, dan sudah begitu
> sejak sebelum migrasi: tak ada satu pun schema yang memakai tipe `signature`,
> dan `edit.blade.php` pun tak pernah memetakannya di larik `$partials`. Ia tak
> pernah dirender sekali pun. Peninjau & penyetuju dipilih lewat `user_picker`.
> Yang justru harus dipecah keluar adalah tiga cabang besar di dalam
> `_repeatable_group`: `RichText`, `ImageUpload`, dan `DocumentPicker`.
>
> **Kebocoran yang ditutup — kelas kesalahan keempat berturut-turut.** Wizard
> menggendong DAFTAR KANDIDAT peserta alur: koleksi model `User` beserta
> `password`, `remember_token`, dan kunci mentah `jabatan`. Blade lama aman
> karena hanya mencetak empat kolom; props Inertia tidak. Kini lewat
> `DocumentWizard::propsKandidat()` — persis lima kunci, `jabatan` sebagai
> LABEL. Sesudah `password` (Fase 6), kunci API (Fase 7), dan `creator`
> (Fase 8): **tiap larik/model yang pindah dari Blade ke props wajib diperiksa
> kolom per kolom.**
>
> **Bentuk kiriman berubah, penerimanya tidak.** Alpine mengirim
> `new FormData(form)` (`sections[aktivitas][0][sub_judul]`); React mengirim
> JSON bersarang. `$request->input('sections')` membaca keduanya — tapi karena
> bentuknya berubah, ia dibuktikan langsung dengan dua tes `postJson`.
>
> **Jebakan Inertia yang akan berulang di Fase 10–12:** berpindah antar LANGKAH
> memakai rute yang sama (`documents.edit`), jadi Inertia MEMAKAI ULANG
> komponen halamannya dan state langkah lama terbawa ke langkah berikutnya.
> Obatnya satu baris: `<FormLangkah key={dokumen:langkah}>`. Yang TIDAK boleh
> ikut di-`key` adalah panel pratinjau — ia harus bertahan, kalau tidak
> iframe-nya dimuat ulang tiap pindah langkah.

### 9a — `documents/create`

Controller `DocumentController@create` mengirim: `type`, `departments`, `defaultDept`, `canChooseDept`, `numberPreview`, `unggahanSaja`.

**Awas:** kalau `$type->isUnggahan()` (FK/PX), formnya **berbeda** — cuma unggah PDF, tanpa langkah wizard. Ada juga mode "dokumen lama"/arsip dengan nomor manual di luar pola resmi. Ketiga jalur (baru, arsip, unggahan) wajib diuji.

Kalau jenisnya tak tersedia bagi user → `documents/unavailable`.

### 9b — Mesin renderer field

**SEBELAS** komponen React — bukan sembilan, dan bukan delapan. Lihat KOREKSI di kepala Fase 9: `Signature` tak pernah dipakai, sementara tiga cabang di dalam `_repeatable_group` (`rich_text`, `image`, `document_picker`) masing-masing jadi komponennya sendiri. Semuanya menerima `section` (dari skema) + `value` + `onChange`:

| Komponen React | Dari | Catatan |
|---|---|---|
| `fields/Text.tsx` | `_text.blade.php` | melayani `text` **dan** `textarea` |
| `fields/RichList.tsx` | `_rich_list.blade.php` | daftar berbutir; baris kosong dibuang saat simpan |
| `fields/RepeatableGroup.tsx` | `_repeatable_group.blade.php` (11 KB) | juga melayani `reference_picker` & `document_picker`; memuat field `image` / `text_or_image` / `date` |
| `fields/JsaAnalysis.tsx` | `_jsa_analysis.blade.php` | bersarang tiga lapis: Langkah Kerja → Bahaya → Pengendalian |
| `fields/UserPicker.tsx` | `_user_picker.blade.php` | kandidat dari props, **nol penyaringan di klien** |
| `fields/PapanKetersediaan.tsx` | `_papan-ketersediaan.blade.php` (11 KB) | papan pemilihan peninjau — beban & cuti |
| `fields/RevisionLog.tsx` | `_revision_log.blade.php` | langkah virtual, di luar skema |
| `fields/RichText.tsx` | `_rich_text.blade.php` | Quill 2 "snow"; dipecah keluar dari `_repeatable_group` |
| `fields/ImageUpload.tsx` | cabang `image` di `_repeatable_group` | unggah langsung, JALURNYA yang tersimpan |
| `fields/DocumentPicker.tsx` | cabang `document_picker` | combobox teks-bebas bab VII SOP |
| ~~`fields/Signature.tsx`~~ | ~~`_signature.blade.php`~~ | **TIDAK DIBUAT — kode mati, tak ada schema yang memakainya** |

Plus satu `fields/index.tsx` yang memetakan `section.type` → komponen, menggantikan `@include($partial)` dinamis di `edit.blade.php:112`. Tipe yang tak terpetakan JATUH ke `Text`, persis seperti Blade jatuh ke `_text` — benar untuk bertahan hidup, salah untuk dibiarkan tanpa disadari, jadi `WizardInertiaTest` mencocokkan tiap tipe yang dipakai schema mana pun dengan isi peta itu.

**Awas — aturan yang tercatat di `DocumentWizard::cleanValue()`:** baris/nilai kosong dibuang **di server** sebelum disimpan (`rich_list`, `reference_picker`, `repeatable_group`, `jsa_analysis` masing-masing punya aturan sendiri). Jangan menduplikasi pembersihan ini di React — kirim apa adanya, biarkan server yang membersihkan.

**Awas — unggahan gambar** (`DocumentWizard::applyUploads()`): hanya JPG/PNG, maks 2 MB, disimpan ke `storage/app/public/lampiran/{DEPT}/{JENIS}/`, dan tercatat di tabel `attachments`. Form wajib `multipart/form-data`; di Inertia pakai `useForm` dengan `forceFormData: true`.

**Awas — pemilih lampiran bab VII SOP** (`DocumentWizard::dokumenBerlaku()`): yang tersimpan adalah **satu baris teks gabungan** `"PPA-ADRO-IK-ICTMD-02 — Instruksi Kerja Perawatan"`, bukan id. Disengaja: lampiran menyebut dokumen sebagai keterangan cetak, bukan merelasikannya, supaya barisnya tetap terbaca kalau dokumen rujukannya kelak dihapus. Jangan diubah jadi relasi id.

### 9c — Rangka wizard

- breadcrumb langkah (dari `SchemaService::steps()` + `stepTitle()`)
- panel anotasi peninjau berdampingan (`annotations` + `reviewSummary`)
- langkah virtual Log Revisi bila `isRevLogStep`
- autosave → `documents.autosave` (debounce, sama seperti Alpine `wizard()`)
- **`<iframe>` pratinjau PDF → route `documents.pdf`** — src tidak berubah, tinggal di-`key` ulang setelah autosave supaya menyegarkan
- tombol Simpan Langkah → `documents.saveStep`
- unggah lampiran → `documents.uploadAttachment`

### 9d — Uji Fase 9 (paling ketat)

1. Buat dokumen **tiap jenis**: SOP, IK, SP, JSA, FK, PX
2. Isi **seluruh** tipe seksi minimal sekali, termasuk `jsa_analysis` bersarang penuh dan `repeatable_group` dengan gambar
3. Autosave berjalan — tutup tab, buka lagi, isian masih ada
4. Pratinjau PDF di iframe menyegar setelah menyimpan langkah
5. **Bandingkan PDF hasil dengan baseline** — harus identik
6. **Uji P1:** tambah satu seksi ke `schema_json` lewat SQL → muncul sendiri di form → kembalikan
7. **Uji pembuat tambahan:** pilih GL sedepartemen → simpan → ajukan cuti untuk GL itu → simpan lagi → **namanya harus tetap ada**
8. **Uji SP/IK:** pilih `peninjau_penyetuju` → periksa `reviewer_id` dan `approver_id` di DB terisi sama

---

## Fase 10 — Peninjauan & persetujuan (8 view) — ✅ SELESAI 2026-08-30

`review/{index,show,md,_modal-alihkan}`, `approvals/{index,show}`, `masukan-sejawat/show`, `nonaktif/index`.

Controller: `ReviewController` (termasuk `aiAnalyze`, `cancelRevision`, `alihkan`), `MdReviewController`, `ApprovalController`, `MasukanSejawatController`, `NonaktifController`.

**Awas:**

- `MasukanSejawatController@create` **memakai ulang view `review.show`** — satu halaman TSX melayani dua route. Jangan sampai jadi dua salinan.
- Masukan reviewer **dua lapis** (CLAUDE.md §13): rangkuman di atas + anotasi di samping tiap poin. Keduanya wajib ada.
- Foto lampiran bisa dilihat saat tinjau **dan dikomentari** (`attachments.comment` → `AttachmentCommentController`).
- Tombol AI (`review.ai`, `review.md.ai`) memanggil layanan luar — beri status pending, dan tangani kegagalan (`FallbackReviewer`).
- `review.alihkan` mengalihkan peninjauan ke orang lain.

**Uji:** alur penuh satu dokumen dari draft sampai berlaku, melewati seluruh status:

```
draft → waiting_for_review → in_review → verifikasi_md → pending_approval → published
```

Lalu jalur gagal: `rejected`. Lalu `sedang_direvisi` → `menunggu_nonaktif` → `obsolete`.

Periksa badge tiap status tampil dengan warna & ikon yang benar — **terutama `verifikasi_md`, jangan sampai abu-abu**.

> **Hasil.** Tujuh halaman TSX (`Review/{Index,Md,Show}`,
> `Approvals/{Index,Show}`, `MasukanSejawat/Show`, `Nonaktif/Index`) + tiga
> komponen bersama di `components/tinjau/` (`SeksiTinjau`, `PanelAi`,
> `DialogAlihkan`). Nol komponen registry baru, nol paket npm baru. Rincian
> lengkap + gerbangnya di `docs/PROGRESS-SHADCN.md` §Fase 10.
>
> **`review.show` benar-benar SATU halaman untuk tiga rute** — dugaan di atas
> terbukti, dan cara menjaganya bukan disiplin melainkan satu service:
> `App\Services\ReviewScreen::bersama()`. Di Blade, tiga pemanggil berbagi satu
> view lewat `?? default`; di Inertia yang dibagi adalah LARIK PROPS, dan larik
> yang disalin di tiga controller adalah larik yang suatu hari bertiga tak
> sepakat.
>
> **Sanitasi HTML harus PINDAH TEMPAT, bukan sekadar ikut.** Blade memanggil
> `PembersihHtml::bersihkan()` tepat saat mencetak. Di Inertia itu terlambat:
> apa pun yang sampai ke atribut `data-page` sudah terlanjur ada di halaman.
> Kini disapu di `ReviewScreen::bersihkanRichText()`, dan kolom mana yang
> `rich_text` dibaca dari SCHEMA — bukan daftar nama kolom yang ditulis tangan.
> **Fase berikutnya yang memindahkan layar ber-`{!! !!}` wajib memeriksa hal
> yang sama.**
>
> **Kebocoran yang ditutup — kelas kesalahan KELIMA berturut-turut.** Layar ini
> menggendong TIGA koleksi model sekaligus yang semuanya berujung ke `User`:
> kandidat pengalihan JSA, komentar foto lampiran (`attachments.comments.user`),
> dan pemberi masukan sejawat. Sesudah `password` (Fase 6), kunci API (Fase 7),
> `creator` (Fase 8), dan kandidat wizard (Fase 9), aturannya sudah tak bisa
> disebut kejutan lagi: **tiap larik/model yang pindah dari Blade ke props wajib
> diperiksa kolom per kolom.**
>
> **Satu cacat lama ikut tertutup.** `anotasiLama` kini membuang anotasi tanpa
> komentar. Baris itu memang ada — sejak tanda ✓/✗ JSA,
> `ReviewDecision::simpanAnotasi()` menulis baris yang hanya memikul `verdict` —
> dan Blade lama mencetaknya juga, jadi JSA berpengendalian 20 memajang 20 baris
> "Catatan sebelumnya:" yang kosong di belakangnya.
>
> **`PapanKetersediaan` dipecah dua, bukan disalin.** Jendela Alihkan hidup di
> luar `PenyediaWizard`, jadi isinya keluar jadi `PapanPilihPeninjau` yang
> menerima daftarnya lewat props; `PapanKetersediaan` tinggal pembungkus tiga
> baris. Ini padanan `@include` + enam variabel di Blade lama — dan ia menutup
> utang terakhir Fase 9: `documents/fields/_papan-ketersediaan` kini benar-benar
> tak dirujuk siapa pun.
>
> **Panel AI memakai `fetch`, bukan `router.post`** — yang kembali JSON temuan,
> bukan halaman, dan Inertia memperlakukan balasan non-Inertia sebagai galat
> navigasi. Pola ini akan berulang di Fase 11–12 untuk setiap tombol yang
> memanggil JSON.

---

## Fase 11 — Revisi, arsip, distribusi (10 view) — ✅ SELESAI 2026-08-30

> **Sudah berkurang:** `_modal-musnahkan-semua` selesai di Fase 7 (satu-satunya pemakainya `pengaturan/sistem`). Periksa juga apa yang sudah ikut terbawa Fase 8 sebelum mulai.
>
> **Terbukti benar, dan lebih jauh dari dugaan.** Dari sepuluh berkas yang
> didaftarkan di bawah, **enam sudah selesai sebelum fase ini dimulai** —
> `_modal-musnahkan-semua` di Fase 7, dan kelima sisanya
> (`_modal-{revisi,nonaktif,musnahkan,masukan}`, `_tombol-rollback`,
> `_masukan-list`) terseret Fase 8 karena di-`@include` dari
> `published`/`obsolete`/`show`. Seluruh aksi `DocumentRevisionController` &
> `DocumentFeedbackController` pun sudah terpasang di sana. Yang benar-benar
> tersisa **empat layar**: `distribution` (dua cabang), `rincianInformasi`,
> `arsip/edit`, `arsip/catatan`.

`documents/{distribution,rincianInformasi,arsip/edit,arsip/catatan}` + modal `_modal-{revisi,nonaktif,musnahkan,musnahkan-semua,masukan}` + `_tombol-rollback` + `_masukan-list` + `_distribusi-informasi`.

Controller: `DocumentRevisionController` (`requestRevision`, `cancelRevisionB`, `rollback`, `restoreObsolete`, `purge`, `purgeAll`), `DocumentArsipController`, `DocumentDistributionController`, `DocumentFeedbackController`.

**Awas:**

- Semua modal ini dulu pakai SweetAlert2 → jadi `AlertDialog`. Konfirmasi sebelum aksi destruktif **wajib tetap ada** (CLAUDE.md §13).
- `purgeAll` memusnahkan banyak dokumen sekaligus lewat `DocumentPurger`. Konfirmasinya harus paling tegas — pertahankan pola konfirmasi yang sudah ada di `_modal-musnahkan-semua`.
- Revisi Tipe B punya langkah Log Revisi tersendiri dan aturan roll-over Edisi/Revisi (`DocumentWizard::persistRevisionLog()` + `Document::revisiSaatKirim()`). Nomor yang dipikul baris baru adalah nomor **sesudah** dokumen dikirim, bukan nomor versi terbit yang masih tersimpan selama draft disusun.

**Uji:** ajukan revisi → batalkan revisi → ajukan lagi → isi Log Revisi → kirim → periksa lembar CATATAN REVISI di PDF **sama persis dengan baseline**. Rollback. Nonaktifkan. Musnahkan satu. Musnahkan massal (di data uji, jangan produksi).

> **Hasil.** Empat halaman TSX (`Documents/{Distribution,RincianInformasi}` +
> `Documents/Arsip/{Edit,Catatan}`). **Nol komponen baru, nol komponen registry
> baru, nol paket npm baru** — fase pertama yang seluruh bahannya sudah ada:
> `PitaCakupan` (Fase 5), `RincianPembaca` + `NomorDokumen` + `DataTable` +
> `PenyaringDokumen` (Fase 8), dan `fields/RevisionLog` (Fase 9). Rincian
> lengkap + gerbangnya di `docs/PROGRESS-SHADCN.md` §Fase 11.
>
> **Kebocoran yang ditutup — dan kali ini BUKAN model User.** Enam fase
> berturut-turut yang bocor selalu berujung ke `password`; di sini yang paling
> berbahaya justru `arsip_path`, jalur berkas privat di disk `local`. Nama
> berkasnya sengaja diacak Laravel supaya tak bisa ditebak dari nomor dokumen
> (lihat komentar `DocumentArsipController::simpanBerkas`) — dan
> `$document->load(...)` apa adanya akan menuliskannya ke atribut `data-page`,
> membatalkan lapis kedua itu diam-diam. Pelajarannya melebar: **yang wajib
> diperiksa kolom per kolom bukan cuma model yang menggendong User, melainkan
> SETIAP model yang pindah dari Blade ke props.**
>
> **Satu-satunya perubahan di luar keempat layar:**
> `DocumentController::ratakanRincian()` naik dari `private` jadi
> `public static` — `InformasiDistribution::rincian()` sengaja berbentuk
> identik dengan kembarannya, jadi menyalin pemerataannya berarti dua tempat
> yang harus sama-sama diingat saat kolom berikutnya bertambah.
>
> **`?sumber=informasi` tetap SATU komponen, bukan dua halaman.** Sama seperti
> Blade-nya (`distribution` + `_distribusi-informasi`), dan sama alasannya:
> penjaga akses, saklar sumber, kartu peringatan, dan pita cakupannya identik;
> yang benar-benar berbeda cuma kolom tabelnya. `DistribusiProps` di TypeScript
> adalah union bertanda `sumber`, jadi membaca `props.documents` di cabang
> informasi bukan bug yang menunggu — ia galat kompilasi.

---

## Fase 12 — Informasi, riwayat, log (13 view) — ✅ SELESAI 2026-08-30

> **Sudah berkurang:** `partials/_modal-antrean` selesai di Fase 5 (ia dipanggil
> `layouts/app.blade.php`, bukan dashboard) dan `components/aksi` di Fase 8
> (jadi `components/dokumen/StripAksi`). Keduanya kini tinggal cangkang yang
> hanya dipanggil Blade yang juga sudah mati. Yang benar-benar tersisa
> **delapan layar**.

`informasi/{index,create,perbarui,nonaktif,_form,_modal-hapus}`, `job_executions/index`, `audit/index`, `log/{masukan,pesan}`, `components/aksi`, `partials/_modal-antrean`.

Controller: `InformasiController`, `JobExecutionController`, `AuditController`, `DocumentLogController`.

`errors/{403,504}` **tetap Blade** — halaman galat Laravel dirender di luar konteks Inertia.

**Awas:** `audit/index` di balik `can:audit.view` dan mencatat aksi admin lewat `AuditService`. Jangan sampai ada aksi yang berhenti tercatat karena controller-nya diubah — `AuditService` dipanggil dari konstruktor tujuh controller, periksa semuanya masih utuh.

> **Hasil.** Delapan halaman TSX (`Informasi/{Index,Create,Perbarui,Nonaktif}`,
> `Audit/Index`, `JobExecutions/Index`, `Log/{Masukan,Pesan}`) + satu komponen
> formulir bersama (`components/informasi/FormInformasi`, padanan
> `informasi/_form` yang dulu di-`@include` dua kali). **Nol komponen registry
> baru, nol paket npm baru.** Rincian lengkap + gerbangnya di
> `docs/PROGRESS-SHADCN.md` §Fase 12.
>
> **Sejak fase ini tak satu pun controller mengembalikan Blade**, kecuali
> `DocumentExportController` yang memang tetap Blade selamanya.
>
> **`PenyaringDokumen` digeneralisasi, bukan disalin lima kali.** Empat dari
> lima penyaring baru tak muat di kontrak lamanya: Audit Log menyaring nama aksi
> sebagai TEKS bebas, Log Pesan & Riwayat Pekerjaan menyaring RENTANG TANGGAL.
> Tiga prop kecil (`tipe`, `labelCari`, `placeholderCari`) — dan kelimanya
> memakai kartu yang sama. Alternatifnya persis keadaan yang membuat komponen
> ini lahir di Fase 8.
>
> **Kebocoran yang ditutup — kelas kesalahan KETUJUH berturut-turut**, dan
> `informasi.file_path` adalah kembaran persis `arsip_path` (Fase 11): berkasnya
> duduk di disk `local`, namanya diacak Laravel, dan `$informasi` apa adanya
> akan menuliskannya ke atribut `data-page`. Empat lagi berujung ke model User —
> `uploader` tiap Informasi, `user` tiap AuditLog & JobExecution, dan `oleh`
> tiap baris Log Pesan. Yang terakhir diperbaiki di HULU
> (`DocumentLogController::baris()` tak lagi menyimpan modelnya sama sekali),
> bukan ditambal di `through()`: larik itu memang tak pernah membutuhkannya.
>
> **Dua asersi lama ternyata sudah hijau trivial.** `assertDontSee('Tambah
> KEBIJAKAN')` dan `assertDontSee('Riwayat (1)')` mustahil merah lagi begitu
> tombolnya dirakit React — persis pelajaran `assertSee('sort=nomor')` di Fase
> 8. **Fase berikutnya yang menemukan `assertSee`/`assertDontSee` atas teks yang
> kini digambar TSX wajib menggantinya, bukan membiarkannya.**
>
> **Dan satu jebakan penyapu rute:** `smartpro:uji-render` melewati
> `informasi.perbarui` (berparameter) DAN `Informasi/Nonaktif` (hanya muncul
> saat kategorinya ditutup Admin). Keadaan yang hanya lahir dari DATA tak
> tertangkap penyapu berbasis tabel rute — payloadnya diambil terpisah, di dalam
> transaksi yang selalu di-ROLLBACK.

---

## Fase 13 — Bersih-bersih & serah terima — 🟡 SEBAGIAN 2026-08-30

> **13.2, 13.3, 13.4 SELESAI. 13.1 DITAHAN** atas keputusan pemilik sampai
> `docs/CEKLIS-MATA-PER-PERAN.md` dikerjakan — Blade lama adalah acuan matanya.
> Rincian lengkap, bukti nol-perujuk, dan daftar 79 berkasnya ada di
> `docs/PROGRESS-SHADCN.md` §Fase 13.

### 13.1 Hapus sisa lapisan lama — **DITAHAN**

```
resources/views/layouts/app.blade.php     (1.283 baris)
resources/views/layouts/guest.blade.php   ← DIKOREKSI, lihat di bawah
resources/views/partials/**               (kecuali yang masih dipakai cetak)
resources/views/documents/fields/**
resources/views/documents/_modal-*.blade.php
resources/js/bootstrap.js                 (Alpine/axios lama)
```

Beserta CDN di root template lama: Bootstrap 5 JS, Bootstrap Icons, SweetAlert2, Alpine.js.

**Sebelum menghapus apa pun:** `grep -rn "nama-berkas" resources/ app/` untuk memastikan tidak ada yang masih merujuk — terutama dari `documents/print/`, yang mungkin masih meng-`@include` partial bersama.

> **KOREKSI 2026-08-30 — `layouts/guest.blade.php` TIDAK BOLEH dihapus.**
> `errors/403` dan `errors/504` meng-`@extends`-nya, dan keduanya memang tetap
> Blade selamanya (Laravel merendernya di luar konteks Inertia). Menghapusnya
> apa adanya mematikan dua halaman galat sekaligus, dan `php artisan test`
> **tidak akan merah** karena `assertForbidden` hanya memeriksa kode status.
>
> Yang dikerjakan: berkas itu **ditulis ulang** jadi cangkang mandiri — nol CDN,
> nol `@vite`, nol manifest, HTML + `<style>` inline. Bukan sekadar demi
> kebersihan: 504 justru muncul saat server tersendat, dan halaman galat yang
> bergantung pada CDN atau pada `public/build` ikut mati persis pada keadaan
> yang paling sering melahirkannya.
>
> **Dua ikutan yang tak tertulis di daftar atas dan baru ketahuan saat audit:**
> `axios` di `devDependencies` (satu-satunya pemakainya `bootstrap.js`) dan
> direktif `Blade::if('role')` di `AppServiceProvider.php:101` (`@role` nol
> kemunculan di `resources/views/`). Keduanya ikut yatim bersama 13.1.

### 13.2 Gerbang akhir — ulangi seluruh baseline Fase 0.1 — ✅ SELESAI

Ekspor ulang **semua** yang diambil di Fase 0.1 dari `smartproshadcn`, bandingkan satu per satu dengan `C:\baseline-smartpro\`:

- [x] PDF SOP, SP, IK, JSA, + Log Revisi → **8 dokumen, semuanya `sama`**
      (`php artisan smartpro:cetak-baseline`)
- [x] PDF arsip/unggahan → `ArsipPdf` hanya MENGALIRKAN berkas, tak merender —
      tak ada keluaran yang bisa menyimpang (sudah dicatat di Fase 0.1)
- [x] `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` → **23/23 OK**
- [~] Daftar Induk `.xls` tiap jenis → **lima identik, FK berbeda satu baris**,
      dan bedanya **DATA**: dokumen FK 1226 dibuat 12:40, baseline direkam
      05:22 hari yang sama. Baris "belum ada dokumen FK" digantikan dokumen itu;
      nol perbedaan lain. Keputusan menyegarkan baseline FK menunggu pemilik —
      lihat `PROGRESS-SHADCN.md` §13.2.

Kalau ada yang berbeda, ada yang menyentuh jalur cetak — dan jalur cetak seharusnya tidak pernah disentuh sama sekali. Telusuri dengan `git diff 2e9abef -- <daftar P2>` (**`2e9abef`, bukan `main`** — lihat P2).

> **Jangan jalankan perintah yang merender Blade bersamaan dengan
> `php artisan test`.** Di Windows keduanya berebut menulis singgahan di
> `storage/framework/views` dan salah satunya mati dengan
> `rename(...): Access is denied` — merah yang terlihat seperti regresi cetak
> padahal bukan. Sudah terjadi sekali di gerbang ini.

### 13.3 Gerbang akhir lainnya — ✅ SELESAI (kecuali yang manual)

```powershell
php artisan test                   # 694 lulus + 1 dilewati, 0 gagal (5772 asersi)
node_modules/.bin/tsc --noEmit     # bersih
npm run build                      # bersih
npm run uji-render                 # bersih
php artisan smartpro:uji-render    # 29 halaman, nol galat
php artisan view:cache             # 94 Blade terkompilasi; lalu view:clear

md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23/23 — pengganti `git diff`
```

> `git diff --stat main -- …` yang dulu tertulis di sini **bukan penjaga yang
> sah** (lihat P2): `main` lebih tua dari titik nol migrasi, jadi diff-nya sudah
> tidak kosong sejak `2e9abef`. `md5sum -c` di atas menggantikannya.

Manual, lima peran (GL, SH, DH, PJO, Admin), berdampingan dengan port 9091:

- setiap menu sidebar ada dan menuju halaman yang sama
- setiap tombol aksi ada, dan yang seharusnya tersembunyi tetap tersembunyi
- lonceng, konfirmasi destruktif, mode gelap, breadcrumb, empty state
- alur dokumen penuh untuk **keenam** jenis, termasuk FK/PX unggahan

### 13.4 Serah terima — ✅ SELESAI

Perbarui `docs/RUNBOOK-RILIS.md` dengan langkah build frontend (`npm run build`) yang sebelumnya tidak ada, dan catat bahwa `queue:work` tetap wajib berjalan (lihat komentar di `jalankan.bat` — tanpa itu email mengendap di tabel `jobs` tanpa satu pun pesan salah).

> **Dikerjakan, dan lebih besar dari yang diduga baris di atas.** Langkah build
> bukan sekadar "yang sebelumnya tidak ada": `public/build` ada di
> `.gitignore`, jadi `git pull` **tidak membawanya**, dan tanpa
> `manifest.json` Laravel melempar `ViteManifestNotFoundException` — **500 di
> semua rute sekaligus**, bukan halaman tanpa gaya. Ia karena itu jadi langkah
> tersendiri (**2b**) dengan dua cara pemasangan + satu perintah pemeriksa,
> bukan satu baris di langkah 2.
>
> Ikut diperbarui: patokan tes di langkah 0 (535 → 694) beserta keempat gerbang
> frontend, empat verifikasi pasca-rilis yang hanya bisa gagal di server
> (langkah 10), dan rollback yang harus ikut mengembalikan `public/build`
> sepadan commit-nya (langkah 11). Rincian di `PROGRESS-SHADCN.md` §13.4.

---

## Ringkasan fase

| Fase | Isi | View | Dokumen |
|---|---|---|---|
| 0 | Duplikasi & isolasi, baseline ekspor | — | SETUP |
| 1 | Pondasi Inertia + React + TS + shadcn | — | SETUP |
| 2 | Skill, MCP, CLAUDE.md v4.0, buang doc UI lama | — | SETUP |
| 3 | Kerangka AppLayout/AuthLayout | 2 | SETUP |
| 4 | Autentikasi ✅ | 3 | MIGRASI |
| 5 | Dashboard + 6 widget ✅ | 9 | MIGRASI |
| 6 | Pengguna & akses ✅ | 7 | MIGRASI |
| 7 | Pengaturan & master data ✅ | 3 | MIGRASI |
| 8 | Daftar dokumen ✅ | 8 | MIGRASI |
| 9 | Pembuatan & wizard + 11 komponen field ✅ | 11 | MIGRASI |
| 10 | Peninjauan & persetujuan ✅ | 8 | MIGRASI |
| 11 | Revisi, arsip, distribusi ✅ | 10 (6 sudah terbawa Fase 7–8) | MIGRASI |
| 12 | Informasi, riwayat, log ✅ | 13 (2 sudah terbawa Fase 5 & 8) | MIGRASI |
| 13 | Bersih-bersih & gerbang akhir 🟡 | −79 (ditahan) | MIGRASI |

Tetap Blade selamanya: 9 cetak + 1 email + 1 Excel + 2 galat = **13 view**.

---

## Pembagian tugas

**Bisa dikerjakan sendiri** — mekanis, perintahnya sudah lengkap:

- Fase 0 seluruhnya (baseline ekspor, robocopy, branch, composer, kloning DB, sunting `.env`, jalankan tes)
- Fase 2.1–2.4 (pasang skill + MCP, hapus dokumen UI lama)
- Menjalankan gerbang verifikasi manual tiap fase (bandingkan berdampingan port 9091 vs 9092)

**Diserahkan ke asisten:**

- Fase 1 (wiring Inertia/React/TS/shadcn, `HandleInertiaRequests::share()`)
- Fase 2.5 (tulis `CLAUDE.md` v4.0)
- Fase 3 (bersihkan `dashboard-01` dari Next.js, baca 1.283 baris layout lama, bangun sidebar per peran)
- Fase 4–13 (migrasi halaman, delapan renderer field, konversi 254 asersi tes)

---

**Langkah pertama, sebelum menyentuh apa pun: ambil baseline ekspor di Fase 0.1.** Itu tidak bisa diambil surut, dan tanpanya kita tidak punya cara membuktikan ekspor dokumen tidak berubah.
