# PLAN AKSES v7 — Profil Akses, Peninjauan JSA, & Fitur Admin

> Rencana kerja lima fase. **Dikerjakan satu fase per sesi**, berurutan.
> Melengkapi `docs/PLAN-REVISI-v6.md`; yang bertabrakan dengannya disebut eksplisit.
> Aturan kerja: satu fase → berhenti → jelaskan → tunggu review + commit (CLAUDE.md §5).

---

## 0. Cara memakai dokumen ini di sesi baru

Dokumen ini ditulis agar **sesi baru tanpa konteks apa pun** bisa langsung bekerja.
Urutan di tiap sesi:

1. Baca `CLAUDE.md`, lalu `docs/HANDOVER-SESI-BARU.md`, lalu dokumen ini.
2. Baca **§3 Prasyarat lingkungan** — ada satu penghalang yang membuat sebagian besar
   suite merah sebelum satu baris kode pun dijalankan. Selesaikan dulu, atau ketahui
   bahwa hasil test tidak bisa dipercaya.
3. Kerjakan **SATU fase** dari §5–§9 sesuai nomor urut. Jangan menyerempet fase lain.
4. Jalankan verifikasi fase itu (§10), lalu berhenti dan laporkan.

Tiap bagian fase memuat: berkas yang disentuh · perubahan · test · **Selesai bila**.

> **STATUS PENGERJAAN (diperbarui 25 Agu 2026).**
> Fase **1 · 2 · 3 · 4 (4a+4b+4c) SELESAI & hijau.** Berikutnya **Fase 5** (§9).
> `ProfilAksesTest` 6/6 · `ManajemenAksesTest` 9/9 · `PeninjauJsaTest` 6/6 ·
> `MenuTitikTest` 2/2. Migration `create_access_profiles_table` SUDAH dijalankan di
> `smartpro_fresh` **dan** `smartpro_uji`.
>
> **Langkah pertama sesudah rilis:** Admin → Pengaturan Sistem → Manajemen Akses →
> buat profil → tetapkan ke tiap GL. Sampai itu dilakukan tak seorang GL pun bisa
> menyusun dokumen — keputusan pemilik butir 3, bukan cacat.

> **Status pohon kerja saat dokumen ini ditulis:** sesi perencanaan sempat menulis kode
> Fase 1 & 2 sebelum diminta berhenti. Bila ingin memulai benar-benar dari nol:
> `git checkout -- app/Http/Controllers/DocumentExportController.php routes/web.php resources/views/documents/published.blade.php resources/views/layouts/app.blade.php tests/Feature/DocumentExportTest.php` lalu hapus `tests/Feature/MenuTitikTest.php`.
> Bila ingin dipertahankan, keduanya sudah lolos verifikasi yang tercatat di §5 & §6.

---

## 1. Konteks

Lima permintaan pemilik berpangkal pada satu hal yang sama: **wewenang di SmartPro
terlalu kasar.** Izin spatie hanya mengenal "boleh membuat dokumen" atau "tidak" —
padahal di lapangan tidak semua Group Leader menyusun keempat jenis dokumen; ada yang
hanya SOP, ada yang hanya JSA, ada yang tidak menyusun sama sekali. Hal yang sama
berlaku pada peninjauan JSA: wewenangnya dipatok ke **departemen** (`SHE` + `PLANT`
ditulis mentah di dua berkas), padahal yang menentukan sebenarnya kompetensi orangnya.

Sekaligus, web ini akan dipakai lintas site. Nomor dokumen (`PPA-ADRO-…`), daftar
departemen, dan konfigurasi AI masih dipatok di kode atau `.env` — tiap site baru
berarti menyentuh kode.

**Hasil yang dituju:** wewenang membuat & meninjau ditentukan **profil akses** yang
dikelola Admin dari layar (pola konfigurasi Mikrotik: buat profil → tetapkan ke
pengguna), plus tiga layar administrasi yang membuat penyesuaian antar-site tidak lagi
menuntut perubahan kode.

---

## 2. Keputusan pemilik

| # | Keputusan |
|---|-----------|
| 1 | Export daftar induk: **GL, SH, DH** — semuanya **departemen sendiri saja**. Admin & PJO tetap lintas 7 dept (tidak diminta dicabut). |
| 2 | Peninjauan JSA: **GL SHE otomatis**, ditambah GL mana pun yang profil aksesnya mengizinkan. GL Plant kehilangan wewenangnya. |
| 3 | Profil default = **tidak ada profil** = tidak boleh membuat dokumen apa pun. Seluruh GL kehilangan wewenang membuat saat rilis, sampai Admin menetapkan profil. |
| 4 | Menu "Dokumen Baru" **tetap tampil**; jenis yang tak berwenang jadi item mati, ber-tooltip "Tidak berwenang", tidak bisa diklik. |
| 5 | Fitur admin: kustom nomor site, master departemen & jenis, konfigurasi AI + kesehatan sistem. Dikerjakan **paling akhir**. |
| 6 | Menu: item terpisah per fitur, didahului label section **"Pengaturan Sistem"** di sidebar. |

---

## 3. Prasyarat lingkungan — BACA SEBELUM MENJALANKAN TEST

> **KOREKSI (sesi Fase 4a).** Paragraf di bawah ini keliru menyebut basis datanya, dan
> kekeliruan itu MENGUBAH pilihan di tabel "dua jalan keluar". Yang benar: `phpunit.xml`
> memang tidak menyetel `DB_CONNECTION`, tapi Laravel memuat **`.env.testing`** saat
> `APP_ENV=testing` — dan di sana `DB_DATABASE=smartpro_uji`, **bukan** `smartpro_fresh`.
> Test tak pernah menyentuh basis data kerja pemilik.
>
> `smartpro_uji` berisi SALINAN 16 akun nyata (NRP asli) tapi tetap kehilangan akun
> contoh seeder — jadi gejalanya persis seperti diuraikan di bawah. Bedanya:
> **`php artisan db:seed --class=AdminUserSeeder --env=testing` hanya me-reset sandi
> `ADM-0001`/`MD-0001` di basis data UJI.** Kerugian yang membuat jalan A ditahan tidak
> berlaku di sana. Keputusannya tetap milik pemilik, tapi harganya jauh lebih murah dari
> yang tertulis.

Suite ini berjalan di atas basis data **PENGEMBANGAN** yang sesungguhnya
(`DatabaseTransactions`, bukan `RefreshDatabase` — data pemilik tak boleh terhapus).
`phpunit.xml` sengaja tidak menyetel `DB_CONNECTION`, jadi test memakai `.env`:
`mysql / smartpro_fresh`.

**Penghalangnya:** basis data itu kini berisi akun PT PPA sungguhan, dan akun contoh
seeder **sudah tidak ada**:

| Akun contoh | Ada? |
|---|---|
| `ADM-0001`, `MD-0001`, `GLSHE-0001` | ✅ ada |
| `GL-0001`, `PJO-0001`, `SH-0001`, `DH-0001`, `STF-0001` | ❌ **hilang** |

Akibatnya puluhan test lama gagal dengan `ModelNotFoundException` di
`User::where('nrp', …)->firstOrFail()` — **sebelum** kode yang diuji sempat berjalan.
Contoh: 5 dari 7 test `DocumentExportTest` merah karena sebab ini, bukan karena kodenya.

**Dua jalan keluar — pemilik yang memutuskan, JANGAN pilih sendiri:**

| | Cara | Untung | Rugi |
|---|---|---|---|
| A | `php artisan db:seed --class=AdminUserSeeder` | Satu perintah, seluruh suite lama hidup lagi. `updateOrCreate` per NRP — tak menghapus apa pun. | **Me-reset sandi `ADM-0001` & `MD-0001` jadi "password"** dan menimpa `syncRoles` keduanya. |
| B | Tulis ulang test agar membuat akunnya sendiri di dalam transaksi | Tak menyentuh data pemilik; kebal terhadap isi DB. Polanya sudah ada: `TestCase::peninjauBersih()`, `DashboardWidgetTest::makeUser()`, `MenuTitikTest`. | Menyentuh ~30 berkas test — pekerjaan tersendiri di luar rencana ini. |

**Sampai salah satunya dipilih, `php artisan test` penuh tidak bisa dijadikan lampu
hijau.** Verifikasi per fase karena itu WAJIB ditambah pembuktian langsung (tinker atau
uji manual), bukan hanya "test hijau".

### Data nyata yang berguna saat menulis test

Departemen: `1=SHE · 2=PLANT · 3=HCGA · 4=FAW-SCM · 5=ICTMD · 6=PRODUKSI · 7=ENGINEERING`

| NRP nyata | Jabatan | Dept |
|---|---|---|
| `16071367` WAHYU BINUKO | pimpinan | — |
| `17021841` ARISAL FARZAN | section_head | ICTMD |
| `18064116` ANGGA MARGI SAPUTRO | group_leader | ICTMD |
| `GLSHE-0001` GL_KEDUA | group_leader | **SHE** |
| `23000651` MARCIO CALVIN ROHI | group_leader | **SHE** (GL SHE kedua — berguna untuk Fase 7) |
| `22004759` RENDY ABRAHAM DOMIS | group_leader | **PLANT** |
| `20260219` Muhammad Surya aji Praja | staff | ICTMD |

---

## 4. Urutan pengerjaan

| Fase | Butir asal | Isi | Bobot | Bagian |
|------|-----------|-----|-------|--------|
| **1** | 1 | Export daftar induk dibuka untuk GL (dept sendiri) | Ringan | §5 |
| **2** | 5 | Titik penanda pada menu induk yang sub-menunya berangka | Ringan | §6 |
| **3** | 2 | GL Plant dicabut dari peninjauan JSA | Sedang | §7 |
| **4** | 3 | **Manajemen Akses** — profil akses + penetapan + penegakan | Berat (4a/4b/4c) | §8 |
| **5** | 4 | Fitur Admin — penomoran site, master data, sistem | Berat (5a/5b/5c) | §9 |

Fase 1 & 2 boleh digabung dalam satu sesi: keduanya ringan, tak bersinggungan, dan tak
menyentuh satu pun test yang sudah ada. Fase 3 ke atas **satu fase satu sesi**.

### Koreksi terhadap rancangan awal

Dua hal ditemukan saat penelusuran kode dan **mengubah bobot fase** — dicatat agar
tidak ditemukan ulang dengan susah payah:

1. **Fase 3 bukan "dua baris".** Mencabut PLANT mematahkan berkas test yang premisnya
   justru "GL Plant meninjau JSA": `PengalihanPeninjauanTest` (helper `glPlant()`
   dipakai delapan test), `ParticipantResolverTest` (dua test), plus komentar di
   `JsaDocumentTest` dan `Api/MenuPerPeranTest` yang perlu diperiksa. Semuanya ikut
   diperbaiki di fase yang sama.
2. **Fase 4 mematahkan tiga berkas test pembuatan dokumen.** `ArsipDocumentTest`,
   `ArsipCatatanRevisiTest`, dan `JenisUnggahanTest` menembak `documents.store` /
   `documents.arsip.store` sebagai GL; sesudah profil berlaku semuanya 403. Ketiganya
   diberi profil di `setUp()`. Biaya yang memang harus dibayar, bukan tanda
   rancangannya salah — tapi harus diketahui sebelum mulai.

> **Migration.** Fase 4 & 5 menambah tabel. Jalankan `php artisan migrate` biasa,
> tunjukkan migration-nya lebih dulu untuk persetujuan, dan **tidak pernah**
> `migrate:fresh` / `migrate:refresh` (CLAUDE.md §4).

---

## 5. Fase 1 — Export daftar induk untuk GL

**Berkas:** `app/Http/Controllers/DocumentExportController.php` ·
`resources/views/documents/published.blade.php` · `routes/web.php` (komentar) ·
`tests/Feature/DocumentExportTest.php`

Penjaganya sudah ada dan sudah benar bentuknya — ia memutuskan **izin + lingkup
departemen bersama-sama** di controller. Yang berubah hanya daftar izin:

```php
$lintasDept = $user->can('document.publish');            // PJO + Admin — tetap
abort_unless($lintasDept
    || $user->can('document.review')                     // SH/DH — tetap
    || $user->can('document.create'), 403);              // GL — BARU
```

`->unless($lintasDept, …where('department_id', $user->department_id))` yang sudah ada
otomatis mengurung GL ke departemennya. **Tak ada query kedua yang ditulis.**

MD tetap tertutup: ia memegang `document.view_all` tapi tak satu pun dari ketiga izin
di atas. Non-Staff juga tidak.

Tombolnya di `published.blade.php` ditambah izin ketiga pada `@canany`, supaya tak ada
tombol yang tampil lalu berakhir 403.

**Tidak dikerjakan:** membatasi GL ke dokumen buatannya sendiri. Daftar induk adalah
daftar **departemen**; memotongnya per pembuat menghasilkan daftar induk yang bohong.

**Test:** `test_gl_dan_md_tidak_boleh_mengunduh` → sisakan MD saja; `test_sh_dan_dh_…`
→ tambahkan aktor GL ke `foreach`-nya (pagar departemennya satu dan sama, jadi tak
perlu test terpisah).

**Selesai bila:** GL & SH hanya melihat dokumen departemennya; PJO & Admin melihat 7
dept; MD & Non-Staff 403. Terbukti lewat test **atau** — bila §3 belum diselesaikan —
lewat `php artisan tinker` yang mencetak keputusan izin per NRP nyata.

---

## 6. Fase 2 — Titik penanda pada menu induk

**Berkas:** `resources/views/layouts/app.blade.php` · `tests/Feature/MenuTitikTest.php` (baru)

Lencana angka hanya dirender pada **sub-item** (`$subItem`). Menu induk yang tertutup —
"Dokumen Berlaku" (Persetujuan Nonaktif), "Log Dokumen" (Masukan Lapangan) — tak memberi
isyarat apa pun bahwa ada pekerjaan di dalamnya.

Sumber angkanya sudah tunggal (`AntreanTugas`), jadi cukup satu helper di samping
`$lencana`:

```php
// Titik penanda menu INDUK: ada pekerjaan di salah satu sub-menunya.
$titik = fn (int ...$angka) => array_sum($angka) > 0
    ? '<span class="pp-titik" title="Ada yang perlu dikerjakan"></span>' : '';
```

Dipasang pada dua menu induk yang punya sub-menu berangka — **hanya dua**; "Dokumen
Baru", "Status Dokumen", "Status Dokumen Staff", dan "Informasi" tak punya angka sama
sekali:

- `Dokumen Berlaku` → `$titik($jml('nonaktif'))`
- `Log Dokumen` → `$titik($jml('masukan'))`

**Letaknya tepat SESUDAH label, bukan didorong `ms-auto` ke kanan.** Caret di sebelahnya
sudah memakai `ms-auto` (utilitas Bootstrap ber-`!important`), dan dua margin auto dalam
satu baris flex justru MEMBAGI ruang kosong di antara keduanya — titiknya berakhir
mengambang di tengah.

CSS `.pp-titik`: `display:inline-block; flex:none; width:7px; height:7px;
margin-left:.4rem; border-radius:50%; background:var(--su-orange1);`

**Test `MenuTitikTest`** — dua kasus: titik muncul saat ada masukan belum ditindak;
titik lenyap saat masukan itu berstatus `ditolak`.

Dua jebakan yang sudah terbukti dan jangan diulang:

1. `assertSee('pp-titik')` **selalu lolos** — nama kelas itu juga ada di blok `<style>`
   layout. Pakai `assertSee('class="pp-titik"', false)`.
2. Membuat SH di departemen yang sudah ada membuat kasus "meja kosong" mustahil diuji:
   basis data pengembangan memuat masukan sungguhan. Buat **departemen baru** di dalam
   transaksi.

Status masukan yang sah hanya `baru · dibaca · diadopsi · ditolak`
(`DocumentFeedback::STATUS_LABELS`); `belumDitindak()` = `baru` + `dibaca`.

**Selesai bila:** `php artisan test --filter=MenuTitikTest` hijau (2 test), dan titik
oranye terlihat di sidebar saat ada masukan belum ditindak.

> Fase ini **sudah dikerjakan & hijau** pada sesi perencanaan. Bila pohon kerja
> di-reset, ulangi persis seperti di atas.

---

## 7. Fase 3 — GL Plant dicabut dari peninjauan JSA

**Sumber perubahan — dua tempat, itu saja:**

1. `app/Models/User.php` → `canReviewJsa()`: `in_array(…, ['SHE','PLANT'])` → `'SHE'` saja.
2. `app/Services/DocumentParticipantResolver.php` → `specialDeptIds()`: `['SHE','PLANT']` → `['SHE']`.

Seluruh penegakan menyalur lewat `App\Services\ReviewAccess::bolehJenis()` dan gate
`review-access` (`AppServiceProvider::boot()`), keduanya membaca `canReviewJsa()`. Web
**dan** mobile (`routes/api.php`, grup peninjauan) ikut berubah tanpa satu baris
tambahan. `specialDeptIds()` & `groupLeaders()` hanya dipakai di cabang JSA — sudah
diperiksa, tak ada pemanggil lain.

**Konsekuensi:** JSA buatan SHE kehilangan GL Plant sebagai peninjau. Jalur SH/DH-nya
tetap terbuka, jadi peninjauan tidak buntu. Fase 4 mengembalikan keluwesannya lewat
profil.

**Test yang ikut diperbaiki — bagian terberat fase ini:**

| Berkas | Perbaikan |
|---|---|
| `PengalihanPeninjauanTest` | Helper `glPlant()` → GL SHE kedua. Premisnya jadi "GL SHE mengalihkan ke GL SHE lain" — tetap sah, karena JSA-nya milik ICTMD sehingga kedua GL SHE sama-sama kandidat. DB nyata sudah punya dua GL SHE (`GLSHE-0001`, `23000651`). |
| `ParticipantResolverTest` | `test_jsa_from_she_…allows_plant_gl…` & `test_jsa_from_plant_…allows_she_gl` ditulis ulang: JSA SHE → hanya SH/DH SHE; JSA PLANT → GL SHE. |
| `JsaDocumentTest` | Komentar "GL Plant bukan peninjau" tetap benar; hanya alasannya yang berubah. |
| `Api/MenuPerPeranTest` | Periksa: `glPlant` di sana tampaknya hanya **pelaksana pekerjaan**, bukan peninjau — kemungkinan besar tak berubah. |

**Test baru `PeninjauJsaTest`:** GL Plant → `canReviewJsa()` false & `review.show` atas
JSA → 403 · GL SHE tetap 200 · `reviewerCandidates()` JSA dept lain tak memuat GL Plant.

**Selesai bila:** GL Plant 403 atas JSA, GL SHE tetap normal, dan keempat berkas test di
atas hijau (atau merah hanya karena §3, bukan karena aturan barunya).

---

## 8. Fase 4 — Manajemen Akses (butir 3)

Fondasi seluruh rencana. Tiga sub-fase; boleh satu sesi per sub-fase.

### 8a — Data & aturan

**Migration** `create_access_profiles_table`:

```
access_profiles
  id
  nama              string, unique        "Penyusun SOP & IK"
  keterangan        string, nullable
  jenis_dibolehkan  json                  ["SOP","IK"] — kode DocumentType
  boleh_review_jsa  boolean, default false
  timestamps

users.access_profile_id → foreignId nullable, constrained, nullOnDelete
```

**Kenapa "tanpa profil" bukan baris `Default` yang di-seed:** baris default bisa diganti
nama, dihapus, atau disunting sampai artinya berubah — dan saat itu terjadi, seluruh GL
yang belum ditetapkan ikut berubah wewenangnya tanpa seorang pun menyentuhnya.
Ketiadaan profil tak bisa disunting. Layar penetapan menampilkannya `— Tanpa Akses —`.

**Model** `app/Models/AccessProfile.php` — `$casts` =
`['jenis_dibolehkan' => 'array', 'boleh_review_jsa' => 'boolean']`, relasi `users()`.

**`app/Models/User.php`** — relasi `accessProfile()`, satu method baru, satu diubah:

```php
/**
 * Boleh MEMBUAT dokumen jenis ini? (butir 3)
 *
 * Tiga lapis, urutannya penting:
 *   1. izin `document.create` — pagar lama, tak dicabut;
 *   2. Admin IT lolos penuh (CLAUDE.md §6);
 *   3. sisanya (= GL) harus punya profil yang menyebut jenis itu.
 * Tanpa profil = tanpa wewenang. Itu nilai bawaan yang disengaja.
 */
public function bolehBuatJenis(string $code): bool
{
    if (! $this->can('document.create')) return false;
    if ($this->can('user.manage')) return true;

    return in_array(strtoupper($code),
        $this->accessProfile?->jenis_dibolehkan ?? [], true);
}

public function canReviewJsa(): bool          // gabungan Fase 3 + Fase 4
{
    return $this->can('document.review_jsa')
        && ($this->department?->code === 'SHE'
            || (bool) $this->accessProfile?->boleh_review_jsa);
}
```

**`DocumentParticipantResolver::reviewerCandidates()`** — cabang JSA berhenti bertanya
"departemen apa" dan mulai bertanya "siapa yang berwenang":

```php
if ($document->type->code === 'JSA') {
    $peninjau = $this->peninjauJsa();                    // GL, disaring canReviewJsa()
    $glLain   = $peninjau->reject(fn ($u) => $u->department_id === $dept);
    // Dept yang GL-nya sendiri peninjau JSA tak boleh menilai pekerjaannya
    // sendiri → jalur SH/DH departemen itu dibuka sebagai gantinya.
    $heads = $peninjau->contains(fn ($u) => $u->department_id === $dept)
        ? $this->heads([$dept]) : collect();

    return $this->sortUnique($glLain->merge($heads));
}
```

`peninjauJsa()` menggantikan `groupLeaders()` + `specialDeptIds()`: query GL aktif
ber-izin `document.review_jsa` dengan `accessProfile` & `department` ter-eager-load,
lalu disaring `canReviewJsa()` di PHP — nol query per orang.

> **Perubahan perilaku yang disengaja:** JSA buatan SHE dulu juga boleh ditinjau SH/DH
> PLANT. Sekarang hanya SH/DH SHE. SH/DH lintas departemen menilai JSA departemen lain
> tak pernah punya dasar di `docs/aturan-alur-v2.md`; itu efek samping dari "SHE & Plant
> sebagai satu blok" yang justru sedang dibongkar.

**Penegakan — tiga titik, dan hanya tiga.** Sudah diverifikasi: aplikasi mobile tidak
punya rute pembuatan dokumen sama sekali (`routes/api.php` tak memuat `POST documents`).

| Titik | Berkas | Perubahan |
|---|---|---|
| Kirim wizard | `StoreDocumentRequest::authorize()` | `bolehBuatJenis(DocumentType::find($this->document_type_id)?->code)` |
| Daftar dokumen lama / FK / PX | `ArsipDocumentRequest::authorize()` — **cabang `else` saja** | idem. Cabang `bisaDisuntingArsipOleh` (perbaikan dokumen yang sudah terdaftar) TIDAK disentuh. |
| Buka form | `DocumentController::create()` sesudah pemeriksaan `is_active` | `abort_unless($user->bolehBuatJenis($typeCode), 403, …)` |

Dua FormRequest itulah akar sebenarnya — keduanya sudah jadi satu-satunya pintu tulis.
`create()` ditambah agar URL yang ditempel langsung tak membuka formulir yang pasti
gagal saat dikirim.

`RolePermissionSeeder` **tidak disentuh**: profil bukan izin spatie, ia lapis kedua di
atas `document.create`.

**Catatan pelaksanaan (sesi 4a).**

- Cabang JSA `reviewerCandidates()` TIDAK memakai potongan `$glLain` di atas: potongan
  itu ditulis sebelum Fase 3 memutuskan bahwa yang dikecualikan adalah **penyusunnya**
  (pembuat utama + pembuat tambahan), bukan seluruh GL sedepartemen. Yang berlaku
  sekarang: `peninjauJsa()` dikurangi penyusun, plus SH/DH dept pembuat bila dept itu
  sendiri memasok peninjau JSA. `groupLeaders()` & `specialDeptIds()` dihapus.
- `TestCase::berprofilPenuh()` dipakai `ArsipDocumentTest`, `ArsipCatatanRevisiTest`,
  `JenisUnggahanTest` — dipasang di helper `gl()` masing-masing, bukan `setUp()`,
  karena di ketiganya `gl()` sudah jadi satu-satunya sumber aktornya.
- **Menetapkan profil sebelum Fase 4b ada layarnya** (`php artisan tinker`):

  ```php
  $p = App\Models\AccessProfile::create([
      'nama' => 'Penyusun SOP & IK', 'jenis_dibolehkan' => ['SOP','IK'],
      'boleh_review_jsa' => false,
  ]);
  App\Models\User::where('nrp','18064116')->first()->accessProfile()->associate($p)->save();
  ```

  Sejak Fase 4b selesai, cara ini **tidak diperlukan lagi** — pakai layar Manajemen
  Akses. Ditinggalkan di sini untuk pemasangan baru yang belum punya akun Admin.

**Catatan pelaksanaan (sesi 4b–4c).**

- Ditemukan saat mengerjakan 4c: selain sidebar, **tiga tempat lain** menawarkan tombol
  "Dokumen Baru" ber-`type=SOP` mati — `dashboard.blade.php`, `documents/index.blade.php`
  (2×), `documents/unavailable.blade.php`. Sesudah profil berlaku, semuanya 403 bagi GL
  yang berwenang menyusun IK tapi bukan SOP. Diperbaiki di akarnya lewat satu method
  `User::jenisPertamaBoleh()` yang mengembalikan KODE (bukan boolean), jadi pemanggilnya
  sekaligus tahu ke mana harus menaut; null = tombolnya tak ditawarkan sama sekali.
- Isi formulir profil dipisah ke `akses/_form-profil.blade.php` dan di-`@include` dua
  kali (modal Baru & modal Ubah). Percobaan pertama memakai closure `ob_start()` di dalam
  `@php` — **tidak bisa**: Blade tidak mengkompilasi `{{ }}` maupun `@checked` di dalam
  blok `@php`, jadi keduanya tercetak apa adanya.
- `akses/tetapkan/{user}` sengaja beruas TIGA, bukan `akses/{user}`: yang kedua
  menyamarkan bahwa parameternya seorang PENGGUNA, bukan sebuah profil.

### 8b — Layar Admin

Rute baru, seluruhnya di grup `can:user.manage` yang sudah ada di `routes/web.php`:

```
GET    akses                   akses.index      AccessProfileController@index
POST   akses                   akses.store
PUT    akses/{profile}         akses.update
DELETE akses/{profile}         akses.destroy
POST   akses/tetapkan/{user}   akses.tetapkan
```

Satu controller (~150 baris), satu view `resources/views/akses/index.blade.php`,
**dua kartu**:

1. **Profil Akses** — tabel (nama · jenis sebagai chip · "Tinjau JSA" · jumlah pengguna
   · aksi). Tombol "Profil Baru" membuka modal: nama, keterangan, kotak centang per
   jenis dari `DocumentType::kode()` (bukan daftar yang ditulis ulang), kotak centang
   "Boleh meninjau dokumen JSA".
2. **Penetapan Akses** — tabel **GL saja** (nama · NRP · departemen · dropdown profil),
   simpan per baris. Di atasnya satu keterangan: Admin IT berwenang penuh tanpa profil;
   SH/DH/PJO/Non-Staff tak menyusun dokumen sehingga tak ditetapkan.

Menghapus profil: SweetAlert menyebut **berapa pengguna** akan kehilangan aksesnya
(`nullOnDelete` → jatuh ke Tanpa Akses). Semua aksi dicatat `AuditService`
(`akses.profil_dibuat` / `_diubah` / `_dihapus` / `akses.ditetapkan`) — CLAUDE.md §6
mewajibkan tindakan Admin ter-audit.

Sidebar: label section baru **"Pengaturan Sistem"** (`@can('user.manage')`) di bawah
"Administrasi", item pertamanya **Manajemen Akses** (`bi-diagram-3`; `bi-shield-lock`
sudah dipakai Audit Log).

### 8c — Menu Dokumen Baru yang mati

`resources/views/layouts/app.blade.php`, submenu jenis di dalam "Dokumen Baru". Jenis
yang tak berwenang berhenti jadi `<a>`:

```blade
@if ($user->bolehBuatJenis($code))
    <a class="nav-link pp-subnav …" href="…">…</a>
@else
    <span class="nav-link pp-subnav pp-subnav-mati" title="Tidak berwenang">…</span>
@endif
```

`.pp-subnav-mati { opacity:.45; cursor:not-allowed; }` — tooltip memakai atribut `title`
bawaan peramban, nol JavaScript. `<span>` tanpa `href` mustahil diklik maupun di-Tab;
tak ada `pointer-events:none` yang bisa ditembus.

Menu induk "Dokumen Baru" tetap tampil meski **semua** jenisnya mati — ketetapan
pemilik: pengguna harus melihat fiturnya ada dan tahu harus meminta akses.

### Test Fase 4

**Baru — `ProfilAksesTest`:** GL tanpa profil → `documents.create` / `documents.store` /
`documents.arsip.store` 403 · GL berprofil `["SOP"]` → SOP 200, JSA 403 · Admin tanpa
profil tetap 200 · GL Plant berprofil `boleh_review_jsa` → masuk `reviewerCandidates()`
JSA dept lain + `review.show` 200 · sidebar GL berprofil SOP memuat `pp-subnav-mati`.

**Diperbaiki:** `ArsipDocumentTest`, `ArsipCatatanRevisiTest`, `JenisUnggahanTest` —
`setUp()` menetapkan profil berisi jenis yang dipakai test itu.

**Selesai bila:** seorang GL tanpa profil benar-benar tak bisa membuat dokumen apa pun
lewat tiga pintu itu, GL berprofil SOP bisa membuat SOP dan hanya SOP, dan Admin tak
pernah terkunci.

---

## 9. Fase 5 — Fitur Admin (butir 4)

### 9.1 Analisis risiko

Ketiga fitur **bisa direalisasikan**, dua harus dipersempit. Yang dibuang bukan
penghematan tenaga — itu pintu yang kalau dibuka merusak data yang tak bisa dikembalikan.

| Fitur | Bahaya nyata | Penanggulangan | Putusan |
|---|---|---|---|
| **Prefix nomor site** | Nomor terbit adalah string tersimpan; mengubah prefix membuat dokumen lama & baru berbeda rupa. | Itu memang **benar** untuk site baru. Yang dilarang: renumbering surut — tak pernah ditawarkan. Layar memberi peringatan + contoh sebelum/sesudah + audit. `seqOf()` membaca digit belakang saja, jadi deteksi nomor bentrok tetap utuh. | **KERJAKAN** |
| | "ADARO" tersebar di ~15 berkas (judul export, placeholder input). | Hanya `DocumentNumberService::PREFIX` yang **membangkitkan**; sisanya kosmetik. Semua dialihkan ke satu setelan. | |
| **Hapus departemen** | `users.department_id` & `documents.department_id` menunjuk ke sana; kodenya tertanam di nomor dokumen terbit. Menghapus = pengguna & dokumen yatim. | **Tak bisa ditanggulangi dengan aman** → fitur hapus **DIBUANG**. Gantinya `is_active`. | **BUANG hapus** |
| **Ubah kode departemen** | Dokumen lama `…-ICTMD-01`, baru `…-ICT-01` — satu departemen, dua rupa nomor, selamanya. | Kode disunting **hanya selama departemen belum punya satu dokumen pun**; sesudah itu terkunci dengan keterangan. Nama panjang tetap bebas. | **KERJAKAN bersyarat** |
| **Tambah jenis dokumen** | Jenis baru butuh schema JSON + template cetak yang tak bisa dibangkitkan UI. `DocumentType::RUPA` (konstanta) menentukan ikon, urutan, submenu — jenis dari layar tak muncul di mana pun, atau muncul lalu mencetak PDF kosong. Bertentangan dengan CLAUDE.md §4. | — | **BUANG tambah/hapus** |
| **Aktif/nonaktif jenis** | Jenis dimatikan saat ada draft berjalan → draft tertahan. | Kolom `is_active` **sudah ada** & sudah dihormati `create()`/`store()`. Layar menampilkan jumlah dokumen berjalan sebelum saklar ditekan. Dokumen Berlaku tetap tercetak. | **KERJAKAN** |
| **Kunci API AI di basis data** | Rahasia ikut tersalin ke tiap dump `.sql` — repo ini memang menyimpan dump di pohon kerja. | Cast `encrypted`, tak pernah dikirim balik ke formulir (tampil bertopeng), isian kosong = pertahankan yang lama. `.env` tetap cadangan. | **KERJAKAN dengan syarat itu** |
| **Baca setelan di `register()`** | `AppServiceProvider::register()` jalan di **setiap** permintaan termasuk `artisan migrate`. Query ke tabel yang belum ada = fatal saat pemasangan baru. | Binding-nya **sudah** closure — hanya dieksekusi saat `AiReviewerInterface` di-resolve. Setelan dibaca di dalam closure, di-cache, jatuh ke `config()` bila tabelnya belum ada. | **AMAN** |
| **Uji kirim email** | Bisa mengirim surat ke alamat sungguhan yang tak bermaksud menerimanya. | Dikirim **hanya ke alamat Admin yang sedang login**, `throttle:3,1`. | **KERJAKAN terbatas** |
| **Artisan dari web** | `migrate` / `db:wipe` / `config:cache` bisa mematikan aplikasi tanpa jalan kembali (CLAUDE.md §4). | Hanya **dua** tombol: `cache:clear` dan `PermissionRegistrar::forgetCachedPermissions()` (yang memang dibutuhkan sesudah peran diubah). Tak ada eksekusi artisan sembarang. | **KERJAKAN terbatas** |

### 9.2 — 5a Penomoran Dokumen (site)

Fondasi bersama: migration `create_pengaturan_table` (`kunci` string primary, `nilai`
text nullable) + `app/Models/Pengaturan.php` dengan `ambil($kunci, $default)`
ber-`Cache::rememberForever` dan `simpan()` yang membuang cache-nya. Satu tabel melayani
5a dan 5c — itulah yang membuatnya layak dibuat, bukan satu fitur saja.

- `DocumentNumberService::PREFIX` → `private function prefix()` membaca
  `Pengaturan::ambil('site.prefix', 'PPA-ADRO')`. Konstanta dipertahankan sebagai nilai
  bawaan agar test lama & pemasangan baru tak berubah.
- `DocumentExportController` — judul "…PPA SITE ADARO INDONESIA" membaca `site.nama`.
- Placeholder di `documents/create.blade.php`, `documents/index.blade.php`, dst.
  mengambil contoh dari setelan yang sama.
- Layar: dua isian, pratinjau langsung `{prefix}-SOP-ICTMD-01`, peringatan "hanya
  berlaku untuk dokumen BARU", audit `pengaturan.site_diubah`.

Rute `pengaturan/penomoran`, menu **Penomoran Dokumen** (`bi-hash`).

### 9.3 — 5b Master Departemen & Jenis Dokumen

Migration: `departments.is_active` boolean default true. Satu view dua kartu, rute
`pengaturan/master`, menu **Master Data** (`bi-diagram-2`).

- **Departemen** — tambah · ubah nama · ubah kode (terkunci bila `documents()->exists()`)
  · saklar aktif/nonaktif. **Tanpa hapus.** Departemen nonaktif hilang dari dropdown
  pembuatan & pendaftaran akun; dokumen dan penggunanya utuh. `$deptIconMap` di layout
  sudah punya cadangan `bi-building`, jadi departemen baru langsung tampil benar.
- **Jenis dokumen** — daftar `DocumentType` dengan saklar `is_active` + ubah `name`.
  Baris menampilkan jumlah dokumen berjalan & Berlaku. **Tanpa tambah/hapus**, dengan
  keterangan tercetak bahwa jenis baru menuntut schema + template cetak.

### 9.4 — 5c Konfigurasi Sistem (AI + Kesehatan)

Rute `pengaturan/sistem`, menu **Konfigurasi Sistem** (`bi-sliders`). Dua kartu:

- **AI** — saklar aktif, penyedia (gemini/openrouter), model, kunci API (encrypted,
  bertopeng), penyedia cadangan. `AppServiceProvider` closure membaca `Pengaturan` lebih
  dulu, `config('services.…')` sebagai cadangan — `.env` tetap bekerja dan pemasangan
  baru tak pernah gagal.
- **Kesehatan** — versi PHP/Laravel, ukuran `storage/app/public/lampiran` (cache 5
  menit), jumlah dokumen & pengguna, status pengiriman email + tombol uji ke alamat
  sendiri, tombol bersihkan cache & reset cache izin.

---

## 10. Verifikasi

```powershell
php artisan test --filter=DocumentExportTest          # Fase 1
php artisan test --filter=MenuTitikTest               # Fase 2
php artisan test --filter=PeninjauJsa                 # Fase 3
php artisan test --filter=ProfilAkses                 # Fase 4
php artisan test                                      # seluruh suite — lihat §3 dulu
```

Uji manual di XAMPP (`jalankan.bat`):

1. **Fase 1** — GL → Dokumen Berlaku → tombol Export ada, isinya hanya departemennya.
   GL departemen lain → daftarnya berbeda.
2. **Fase 2** — buat satu masukan lapangan dari akun Non-Staff → login SH → titik oranye
   muncul di "Log Dokumen" meski menunya tertutup.
3. **Fase 3** — GL Plant: antrean Tinjau Dokumen kosong dari JSA; URL `review/{jsa}`
   langsung → 403. GL SHE tetap normal.
4. **Fase 4** — Admin → Manajemen Akses → profil "Penyusun SOP" (centang SOP) →
   tetapkan ke satu GL → login GL: JSA/IK/SP/FK/PX kelabu ber-tooltip, SOP bisa diklik
   dan wizard-nya jalan sampai tersimpan. Cabut profilnya → seluruh jenis kelabu.
   Profil "Peninjau JSA" → tetapkan ke GL Produksi → JSA departemen lain muncul di papan
   pemilihan peninjau dan bisa ia buka.
5. **Fase 5** — ubah prefix jadi `PPA-MSW` → dokumen baru bernomor `PPA-MSW-SOP-…`,
   dokumen lama **tidak berubah**, daftar induk lama tetap terbuka. Nonaktifkan satu
   departemen → hilang dari dropdown, dokumennya tetap terbaca. Matikan jenis SP →
   `documents/create?type=SP` menampilkan halaman "belum tersedia".

---

## 11. Di luar scope (sengaja tidak dikerjakan)

- **Renumbering surut** dokumen terbit ke prefix site baru — merusak rujukan audit yang
  dipakai di luar sistem.
- **Menambah/menghapus jenis dokumen dari layar** — menuntut schema JSON + template
  cetak yang tak bisa dibangkitkan UI (CLAUDE.md §4).
- **Menghapus departemen** — tak ada cara aman selama pengguna & dokumennya menunjuk ke
  sana.
- **Profil akses untuk peran selain GL** — SH/DH/PJO/Non-Staff tak menyusun dokumen;
  memberi mereka profil hanya menambah kotak yang tak pernah berpengaruh.
- **Pembuat tambahan (co-author)** tetap terbuka bagi GL tanpa profil — ia baris tanda
  tangan pada lembar pengesahan, bukan tindakan menyusun.
- **Memindahkan knob `config/smartpro.php`** (batas peninjau, hari pita, ambang beban,
  jenis wajib MD) ke layar — tidak dipilih pemilik pada sesi ini.
- **Merapikan ~30 berkas test agar tak bergantung NRP seeder** (§3 jalan B) — pekerjaan
  tersendiri, bukan bagian rencana ini.
