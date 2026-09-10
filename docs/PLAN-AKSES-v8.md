# PLAN AKSES v8 — Masukan Sejawat, Pesan Berkonteks & Pengaturan Sistem

> Rencana kerja. **Dikerjakan satu fase per sesi**, berurutan.
> **MENGGANTIKAN `docs/PLAN-AKSES-v7.md`.** Fase 1–4 v7 SUDAH SELESAI & hijau;
> Fase 5 v7 (Fitur Admin) dipindah utuh ke sini sebagai Fase 5.
>
> **Keadaan per 26 Agu 2026:** Fase 1–4, 5a, 5b & **5c** **selesai** — seluruh Fase 5
> tuntas. Berikutnya **Fase 6** (utang test, §3). Ringkasan status ada di §4; apa yang
> sudah diketahui merah dan mana yang sengaja dibiarkan ada di **§11**.
> Aturan kerja: satu fase → berhenti → jelaskan → tunggu review + commit (CLAUDE.md §5).

---

## 0. Cara memakai dokumen ini di sesi baru

Dokumen ini ditulis agar **sesi baru tanpa konteks apa pun** bisa langsung bekerja.
Urutan di tiap sesi:

1. Baca `CLAUDE.md`, lalu dokumen ini.
   *(CLAUDE.md §17 menyuruh membaca `docs/HANDOVER-SESI-BARU.md` lebih dulu — berkas itu
   **TIDAK ADA** di repo per 26 Agu 2026. Jangan mencarinya; lihat §11 T5.)*
2. Baca **§3 Prasyarat lingkungan** — ada satu penghalang yang membuat sebagian besar
   suite merah sebelum satu baris kode pun dijalankan.
3. Kerjakan **SATU fase** dari §5–§9 sesuai nomor urut. Jangan menyerempet fase lain.
4. Jalankan verifikasi fase itu (§10), lalu berhenti dan laporkan.
5. Sebelum menyimpulkan "ada yang rusak": baca **§11 Temuan & utang teknis** — di situ
   tercatat apa yang sudah diketahui merah, siapa penyebabnya, dan mana yang SENGAJA
   dibiarkan.

Tiap bagian fase memuat: berkas yang disentuh · perubahan · test · **Selesai bila**.

> **STATUS PENGERJAAN (26 Agu 2026).** Fase **1, 2, 3, 4 SELESAI & hijau**; Fase **5a,
> 5b dan 5c SELESAI & hijau**. Berikutnya: **Fase 6** (utang test, §3).
>
> Suite yang sudah hijau dan TIDAK boleh dirusak — semuanya "jalan B" (membuat
> aktornya sendiri), jadi bisa dijalankan kapan saja tanpa akun seeder:
> `ProfilAksesTest` 6/6 · `ManajemenAksesTest` 9/9 · `PeninjauJsaTest` 6/6 ·
> `MenuTitikTest` 2/2 · `DokumenDepartemenTest` · `MasukanSejawatTest` ·
> `PesanBerkontekTest` 7/7 · `MenuPengaturanTest` 3/3 · `PengaturanSiteTest` 6/6 ·
> `MasterDataTest` 11/11 · `KonfigurasiSistemTest` 13/13.
> Totalnya 75 test hijau dalam satu jalannya.
>
> Migration yang sudah dijalankan di `smartpro_fresh` **dan** `smartpro_uji`:
> `create_access_profiles_table` · `create_document_feedback_antargl_table` ·
> `create_pengaturan_table` · `add_is_active_to_departments_table` ·
> `create_informasi_kategori_table`.
>
> **Fase 5c TIDAK menambah migration** — setelan AI menumpang tabel `pengaturan`
> yang sudah dibuat 5a. Tak ada perubahan basis data yang perlu dijalankan.
>
> **§3 BERUBAH ARTINYA (26 Agu 2026).** Suite penuh: **312 merah, 191 hijau**.
> Ke-312 itu SELURUHNYA sebab lingkungan — 310 `ModelNotFoundException` pada NRP
> contoh, 2 lagi pada data contoh (`MdReviewStageTest`: dokumen IK & `SH-0001`).
> **Nol** kegagalan menyentuh kode v8. Baca §3 sebelum menyimpulkan apa pun.

---

## 1. Konteks

`docs/PLAN-AKSES-v7.md` tinggal menyisakan **Fase 5** (Fitur Admin). Pemilik menambah
empat permintaan baru yang menggeser cakupan, sehingga v7 ditutup dan digantikan
dokumen ini.

Akar keempat permintaan itu satu: **v7 memindahkan wewenang MEMBUAT ke profil akses,
tapi tidak memikirkan GL yang tak diberi profil.** Ia kini punya sidebar penuh tanpa
satu pun dokumen — padahal rekan sedepartemennya menyusun dokumen yang ia pahami
isinya. Tiga permintaan pertama membuka jalur "ikut membaca dan menyumbang tanpa
menyusun"; yang keempat merapikan menu Admin yang terlanjur terbelah dua section.

**Hasil yang dituju:**

1. **Dokumen Saya** = murni buatannya sendiri. **Dokumen Departemen** = buatan GL LAIN
   sedepartemennya. Dua daftar yang tak pernah beririsan.
2. Sesama GL bisa memberi **masukan sejawat** atas dokumen yang **belum Berlaku**,
   lewat layar yang sama rupanya dengan proses tinjau (tanpa AI, tombol Batal & Kirim).
   Pemilik dokumen membacanya di **Dokumen Saya** & **Log Pesan**.
3. Setiap pesan punya tombol yang **menuju tempat pesan itu berasal** — lonceng maupun
   Log Pesan. Sekarang lonceng membuang parameter dokumen (semua mendarat di halaman
   daftar) dan satu jenis notifikasi **error 500** saat diklik.
4. Menu Admin disatukan di bawah section **Pengaturan Sistem**.

---

## 2. Keputusan pemilik

| # | Keputusan |
|---|---|
| A | Masukan sejawat HANYA untuk dokumen **belum Berlaku**, diberi lewat **Dokumen Departemen**. Masukan Lapangan tetap khusus dokumen **sudah Berlaku** (Non-Staff). Dua kanal, tak bercampur. |
| B | Layar pemberi masukan = **layar tinjau tanpa AI**; tombolnya hanya **Batal** & **Kirim**. |
| C | Masukan sejawat masuk ke **Dokumen Saya** + **lonceng**. Isinya ikut ke **Log Pesan**, bisa diklik untuk memunculkan isinya. TIDAK masuk Masukan Lapangan dan tidak menambah lencana antreannya. |
| D | Sidebar: label "Administrasi" DIHAPUS; satu section **Pengaturan Sistem** menampung Persetujuan Akun, Manajemen User, Audit Log, Manajemen Akses + item Fase 5. Tiap item tetap ber-gate sendiri, jadi GL masih melihat Persetujuan Akun & Audit Log. |
| E | Urutan: fitur baru dulu (Fase 1–4), **Fase 5 (Fitur Admin) terakhir, isinya persis warisan v7 §9 — tidak diubah**. |
| **F** | *(26 Agu 2026)* **Kategori Informasi bisa ditambah dari layar** — jadi kartu KETIGA di Master Data (5b), bukan fitur berdiri sendiri. Admin menulis nama, deskripsi, dan memilih kolom yang dipakai. Ini BUKAN pembatalan "tambah jenis dokumen dibuang": jenis INTI (setara SOP/IK/SP/JSA) tetap mustahil dari layar, sebab menuntut schema JSON + template cetak + baris `DocumentType::RUPA` + kalibrasi cetak. Informasi tak punya satu pun dari keempatnya. |
| **G** | *(26 Agu 2026)* **Kolom yang bisa dipilih HANYA yang sudah ada** di tabel `informasi` — `edisi`, `no_revisi`, `tanggal_efektif`. (`nomor` & `judul` selalu ada.) Kolom yang benar-benar baru menuntut migration dan **tidak** disediakan dari layar. |
| **H** | *(26 Agu 2026)* **Kategori tidak dihapus, hanya dinonaktifkan.** Kategori nonaktif tetap tampil di sidebar tapi **tak bisa diklik** — mengikuti pola jenis dokumen yang belum tersedia, bukan lenyap diam-diam. Dokumennya masih ada, dan orang perlu melihat bahwa kategorinya sengaja ditutup. |
| **I** | *(26 Agu 2026)* **Akun contoh `GL-0001` dkk. tidak akan kembali** — perannya diisi NRP pengguna sungguhan; hanya `ADM-0001` yang masih terpakai. Konsekuensinya di §3. |

---

## 3. Prasyarat lingkungan — BACA SEBELUM MENJALANKAN TEST

Suite berjalan di atas basis data **UJI** (`.env.testing` → `smartpro_uji`), memakai
`DatabaseTransactions` (bukan `RefreshDatabase` — data pemilik tak boleh terhapus).

**KETETAPAN PEMILIK 26 Agu 2026 — ini mengubah kesimpulan v7 §3.** Akun contoh
`GL-0001`, `SH-0001`, `DH-0001`, `PJO-0001`, `STF-0001` **sudah tidak relevan**: peran
itu kini diisi NRP pengguna SUNGGUHAN. Yang masih terpakai hanya **`ADM-0001`**.

| Akun contoh | Status |
|---|---|
| `ADM-0001` | ✅ ada, **masih terpakai** |
| `MD-0001`, `GLSHE-0001` | ✅ ada |
| `GL-0001`, `PJO-0001`, `SH-0001`, `DH-0001`, `STF-0001` | ❌ hilang, **dan tak akan kembali** |

Akibatnya **jalan A (`db:seed --class=AdminUserSeeder --env=testing`) TIDAK BOLEH lagi
dipakai** sebagai jalan keluar: ia akan menghidupkan kembali lima akun hantu yang
sengaja ditiadakan, dan membuat suite hijau di atas pengguna yang tak ada di sistem
nyata. Hijau semacam itu lebih berbahaya daripada merah.

**Yang tersisa tinggal jalan B**: tiap test membuat aktornya sendiri di dalam
transaksi. Polanya sudah ada dan sudah terbukti di sembilan suite v7/v8
(`TestCase::peninjauBersih()`, `TestCase::berprofilPenuh()`,
`DashboardWidgetTest::makeUser()`, `MenuTitikTest::orang()`).

> **RISIKO YANG SEKARANG NYATA, bukan lagi ketidaknyamanan.** 310 test merah berarti
> jaring pengaman untuk penomoran, ekspor, alur tinjau–setuju, revisi, nonaktif, dan
> TATA LETAK CETAK sedang **mati**. Setiap perubahan berikutnya berjalan tanpa
> pengaman itu. Merapikan ~30 berkas test bukan lagi "pekerjaan tersendiri di luar
> rencana" melainkan **utang yang menghalangi**; ia layak jadi fase tersendiri sesudah
> Fase 5 selesai.

**Test BARU wajib mengikuti jalan B** — membuat aktornya sendiri — supaya tak menambah
utang yang sama.

### Data nyata yang berguna saat menulis test

Departemen: `1=SHE · 2=PLANT · 3=HCGA · 4=FAW-SCM · 5=ICTMD · 6=PRODUKSI · 7=ENGINEERING`

| NRP nyata | Jabatan | Dept |
|---|---|---|
| `16071367` WAHYU BINUKO | pimpinan | — |
| `17021841` ARISAL FARZAN | section_head | ICTMD |
| `18064116` ANGGA MARGI SAPUTRO | group_leader | ICTMD |
| `GLSHE-0001` GL_KEDUA | group_leader | **SHE** |
| `23000651` MARCIO CALVIN ROHI | group_leader | **SHE** |
| `22004759` RENDY ABRAHAM DOMIS | group_leader | **PLANT** |
| `20260219` Muhammad Surya aji Praja | staff | ICTMD |

> Dua GL sedepartemen (`GLSHE-0001` & `23000651`, keduanya SHE) — pasangan yang
> dibutuhkan seluruh uji manual Fase 1 & 2.

---

## 4. Urutan pengerjaan

| Fase | Isi | Bobot | Bagian | Status |
|------|-----|-------|--------|--------|
| **1** | Dokumen Departemen ≠ Dokumen Saya | Ringan | §5 | ✅ selesai |
| **2** | Masukan Sejawat antar-GL (2a + 2b) | Berat | §6 | ✅ selesai |
| **3** | Pesan yang menuju tempatnya (3a lonceng + 3b Log Pesan) | Sedang | §7 | ✅ selesai |
| **4** | Satu section "Pengaturan Sistem" | Ringan | §8 | ✅ selesai |
| **5a** | Penomoran Dokumen (prefix & nama site) | Sedang | §9.2 | ✅ selesai |
| **5b** | Master Departemen · Jenis Dokumen · Kategori Informasi | Berat | §9.3 | ✅ selesai |
| **5c** | Konfigurasi Sistem (AI + Kesehatan) | Sedang | §9.4 | ✅ selesai |
| **6** | Utang test — NRP contoh diganti NRP pengguna sungguhan | Berat | §3 | ✅ selesai |

Fase 3b bergantung pada tabel yang dibuat Fase 2a (sumber pesan ke-6), jadi urutannya
tidak boleh dibalik. Fase 1 & 4 boleh digabung satu sesi bila diminta: keduanya ringan
dan tak bersinggungan.

> **Migration.** Fase 2 & 5 menambah tabel. Jalankan `php artisan migrate` biasa,
> tunjukkan migration-nya lebih dulu untuk persetujuan, dan **tidak pernah**
> `migrate:fresh` / `migrate:refresh` (CLAUDE.md §4).

---

## 5. Fase 1 — Dokumen Departemen ≠ Dokumen Saya

**Berkas:** `app/Http/Controllers/DocumentStaffStatusController.php` ·
`resources/views/documents/staff-status.blade.php` ·
`tests/Feature/DokumenDepartemenTest.php` (baru)

Kabar baiknya: **GL tanpa profil akses SUDAH melihat menu ini.** Penjaga sidebar
(`layouts/app.blade.php:970`) dan controller (`DocumentStaffStatusController.php:27-30`)
membaca izin spatie `document.create` / jabatan — bukan profil akses. Profil akses
hanya lapis kedua di `User::bolehBuatJenis()`. Jadi tak ada yang perlu "dibuka";
yang perlu diperbaiki hanya **irisannya**.

Satu baris disisipkan pada rantai query `staffStatus()` (`:35-40`):

```php
// "Dokumen Departemen" = pekerjaan ORANG LAIN di departemen ini. Dokumen
// sendiri punya menunya sendiri (Dokumen Saya) — satu dokumen yang muncul di
// dua daftar membuat keduanya tak bisa dipercaya sebagai hitungan.
->where('created_by', '!=', $user->id)
```

**Tidak** disaring `jabatan = group_leader`: GL adalah satu-satunya pembuat
(CLAUDE.md §6), dan menyaringnya justru menyembunyikan dokumen lama yang didaftarkan
Admin lewat arsip.

**Tidak** ada perubahan di `DocumentController::index()` — ia sudah "creator atau
co-author" (`:63-65`), yang memang arti "Dokumen Saya".

Efek pada SH/DH nihil: mereka tak pernah membuat dokumen, jadi baris itu no-op bagi
menu "Status Dokumen Staff" yang berbagi rute yang sama.

**Test `DokumenDepartemenTest`** — dua GL sedepartemen dibuat di dalam transaksi;
dokumen A tampil di Dokumen Departemen milik GL-B dan **tidak** tampil di milik GL-A.

**Selesai bila:** GL membuka Dokumen Departemen dan tidak menemukan satu pun dokumen
buatannya; Status Dokumen Staff milik SH/DH tetap utuh.

---

## 6. Fase 2 — Masukan Sejawat antar-GL

Fase terberat. Dua sub-fase; boleh dua sesi.

### 6a — Data & layar kirim

**Migration** `create_masukan_sejawat_table` (tunjukkan dulu, tunggu approval — CLAUDE.md §4):

```
masukan_sejawat
  id
  document_id   foreignId, constrained, cascadeOnDelete
  user_id       foreignId, constrained            pemberi masukan
  ringkasan     text nullable                     isi textarea "Ringkasan / Catatan Umum"
  catatan_json  json                              [{section_key, item_ref, komentar}]
  dibaca_at     timestamp nullable                ditandai saat pemilik membukanya
  timestamps
```

**Kenapa tabel sendiri, bukan menumpang `reviews` + `review_annotations`:** menumpang
terlihat lebih hemat, tapi `reviews` adalah tabel KEPUTUSAN — `decision` enum
(`pending|approved|needs_revision`) dibaca `DocumentLogController::dariReviews()`
(`:154`), `ReviewController::show()` (`$priorAnnotations`), dan
`DocumentController::edit()` (`:344-346`, "Rangkuman Peninjau"). Menyelipkan baris
yang bukan keputusan ke sana membuat masukan sejawat bocor ke layar peninjau resmi dan
ke form revisi pembuat, dan menuntut penyaring baru di tiga tempat sekaligus. Tabel
sendiri: nol blast radius.

Juga **bukan** `document_feedback` — tabel itu punya siklus hidup sendiri
(`feedback_number`, status baru/dibaca/diadopsi/ditolak, balasan, adopsi jadi revisi)
yang tak satu pun berlaku di sini, dan pemilik sudah memutuskan keduanya kanal terpisah
(keputusan A).

`catatan_json` disimpan sebagai JSON, bukan tabel anak: barisnya tak pernah dicari
satu-satu, hanya ditampilkan sebagai satu blok — sama seperti `document_contents.value_json`.

**Model** `app/Models/MasukanSejawat.php` — `$table = 'masukan_sejawat'`,
`$casts = ['catatan_json' => 'array', 'dibaca_at' => 'datetime']`, relasi
`document()` & `user()`.

**`app/Models/Document.php`** — relasi `masukanSejawat(): HasMany` + satu predikat di
samping `bisaDiberiMasukanOleh()` (`:210-215`), yang **tidak disentuh**:

```php
/**
 * Boleh memberi MASUKAN SEJAWAT atas dokumen ini? (v8 Fase 2)
 *
 * Kanal terpisah dari masukan lapangan: yang itu milik Non-Staff atas dokumen
 * BERLAKU; yang ini milik sesama GL atas dokumen yang MASIH BISA DIUBAH.
 * Batasnya sengaja "belum Berlaku", bukan "draft" saja — dokumen yang sedang
 * ditinjau pun masih bisa diperbaiki pembuatnya begitu dikembalikan.
 */
public function bisaDiberiMasukanSejawatOleh(User $user): bool
{
    return $user->jabatan === User::JABATAN_GROUP_LEADER
        && $this->department_id === $user->department_id
        && $this->created_by !== $user->id
        && ! in_array($this->status, ['published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete'], true);
}
```

Gate `beri-masukan` (`AppServiceProvider.php:108`) **tidak diubah** — ia tetap milik
Non-Staff. Aturan baru berdiri sendiri, jadi tak ada perilaku lama yang bergeser.

**Rute** (di dalam grup `auth`+`active`, dekat `documents.feedback.*` di `routes/web.php:220`):

```
GET  dokumen/{document}/masukan-sejawat/beri    masukan-sejawat.create
POST dokumen/{document}/masukan-sejawat         masukan-sejawat.store   (throttle:10,1)
GET  dokumen/{document}/masukan-sejawat         masukan-sejawat.show    (Fase 2b)
```

**Controller** `app/Http/Controllers/MasukanSejawatController.php`:

`create()` — `abort_unless($document->bisaDiberiMasukanSejawatOleh($user), 403)`, lalu
**pakai ulang `resources/views/review/show.blade.php`**. View itu memang sudah dirancang
dipakai ulang lewat variabel (contohnya `MdReviewController::show()` `:57-93`). Kirim:

| Variabel | Nilai | Akibat di layar |
|---|---|---|
| `document`, `schema`, `contentMap`, `imageAttachments` | sama seperti `ReviewController::show()` (`:59-125`) | isi dokumen per section |
| `aiUrl` | **tidak dikirim** | blok AI (`:74-123`) hilang sendiri — `@if ($aiUrl ?? null)` |
| `alihKandidat`, `pengalihan`, `priorAnnotations` | tidak dikirim | tombol Alihkan & alert pengalihan hilang (`:43`, `:58`) |
| `pakaiVerdict` | `false` | blok verdict JSA (`:279-311`) tidak dipakai |
| `backUrl` | `route('documents.staffStatus')` | "Kembali ke antrian" |
| `formAction` | `route('masukan-sejawat.store', $document)` | |
| `petunjuk` | "Beri masukan pada bagian yang menurut Anda perlu diperbaiki…" | |
| `tanpaKeputusan` | `true` | **satu-satunya perubahan di view** |

> **TIDAK ada side-effect status.** `create()` tak boleh mengubah dokumen jadi
> `in_review` seperti `ReviewController::show()` (`:66-69`). Ini bukan peninjauan, dan
> mengubah statusnya akan mencabut hak Tarik milik pembuatnya.

`store()` — validasi `summary` (nullable, max 2000) + `annotations` (array, tiap
komentar max 1000), rapikan jadi `catatan_json` dengan membuang item berkomentar kosong
(pola yang sudah ada di `ReviewDecision::simpanAnotasi()` `:207-209`), simpan, audit
`masukan_sejawat.kirim`, notifikasi ke `$document->penyusun()` (`Document.php:162-169`),
redirect ke `documents.staffStatus` dengan status.

Notifikasi memakai `DocumentNotification` yang sudah ada:
`"Masukan sejawat dari {nama} pada {no}."`, ikon `bi-chat-square-text`, route
`masukan-sejawat.show`, `$penting = false` (lonceng saja, bukan email). Tambahkan ikon
itu ke `DocumentNotification::judulAksi()` (`:112-128`) → `'Masukan Sejawat'`.

**Perubahan di `review/show.blade.php` — HANYA blok tombol** (`:312-321`), satu cabang
baru sebelum `@else`:

```blade
@elseif ($tanpaKeputusan ?? false)
    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ $backUrl }}" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary"
                data-confirm="Kirim masukan Anda kepada pembuat dokumen?"
                data-confirm-title="Kirim Masukan?" data-confirm-ok="Ya, kirim"><i class="bi bi-send"></i> Kirim</button>
    </div>
```

Atribut `annotations_ai` (`:160/196/235/262`) & `data-ai-badge` (`:263`) **dibiarkan** —
mereka hanya `<input type="hidden" value="0">` yang diabaikan `store()` kita. Membuangnya
berarti menyentuh empat cabang render demi nol perbedaan yang terlihat.

**Tombol pemicu** di `resources/views/documents/staff-status.blade.php` (`:81-84`), di
samping tombol PDF & Lihat yang sudah ada:

```blade
@if ($doc->bisaDiberiMasukanSejawatOleh(auth()->user()))
    <a href="{{ route('masukan-sejawat.create', $doc) }}" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-chat-square-text"></i> Beri Masukan
    </a>
@endif
```

### 6b — Layar lihat masukan

**Satu halaman baca-saja** `resources/views/masukan-sejawat/show.blade.php` — bukan
modal per baris. Alasannya: Dokumen Saya, Log Pesan, dan lonceng ketiganya butuh tujuan
yang sama, dan tiga modal yang harus sepakat isinya adalah tiga modal yang suatu hari
tidak sepakat. Satu URL melayani ketiganya, dan bisa di-bookmark.

Isinya: kop dokumen (nomor, judul, badge status) + satu kartu per masukan — nama pemberi
`nameWithJabatan()` + tanggal WITA, ringkasan, lalu daftar catatan per section. **Label
section dibaca dari `SchemaService::for($document->type)`** — jangan pernah menampilkan
`section_key` mentah kepada pengguna. Anchor `id="m{{ $m->id }}"` per kartu supaya Log
Pesan bisa menaut langsung ke satu masukan.

Wewenang: pemilik dokumen (`created_by` atau co-author), pemberi masukan itu sendiri,
dan pemegang `document.view_all`. Membuka halaman mengisi `dibaca_at` untuk masukan yang
belum dibaca **oleh pemiliknya saja** — pola yang sudah ada di
`DocumentController.php:283-285` (MD/Admin tak boleh menghabiskan sorotan milik pemilik).

**Dokumen Saya** (`resources/views/documents/index.blade.php`, kolom Judul `:96-97`) —
lencana kecil bila ada masukan sejawat:

```blade
@if ($doc->masukan_sejawat_count)
    <a href="{{ route('masukan-sejawat.show', $doc) }}" class="badge-soft badge-soft-info text-decoration-none"
       title="Masukan dari rekan sedepartemen">
        <i class="bi bi-chat-square-text"></i> {{ $doc->masukan_sejawat_count }}
    </a>
@endif
```

`DocumentController::index()` (`:55`) ditambah `->withCount('masukanSejawat')` — satu
kolom pada query yang sudah ada, bukan query per baris.

**Test `MasukanSejawatTest`** — GL-B boleh `create`/`store` atas draft GL-A sedepartemen;
GL-A atas dokumennya sendiri → 403; GL departemen lain → 403; dokumen `published` → 403
(itu jalur Masukan Lapangan); Non-Staff → 403; sesudah kirim, GL-A melihat lencana di
Dokumen Saya dan bisa membuka isinya; layar `create` **tidak** memuat blok AI dan
**tidak** mengubah status dokumen.

**Selesai bila:** GL-B memberi masukan atas draft GL-A dari Dokumen Departemen, GL-A
melihat lonceng + lencana di Dokumen Saya, membukanya dan membaca catatan per bagian;
status dokumen tak bergeser sedikit pun; Masukan Lapangan SH/DH tidak bertambah.

---

## 7. Fase 3 — Pesan yang menuju tempatnya

Dua permukaan. **3a lebih dulu** — ia memperbaiki satu galat 500 yang sudah hidup.

### 7a — Lonceng

**Akar masalah, satu baris:** `app/Http/Controllers/NotificationController.php:19`

```php
$target = Route::has($routeName) ? route($routeName) : route('dashboard');
```

Parameter dokumen dibuang, jadi setiap notifikasi mendarat di halaman DAFTAR — dan
notifikasi ber-route `documents.show` (`DocumentFeedbackService.php:158`, "X mengajukan
revisi …" ke SH/DH & MD) **melempar `UrlGenerationException` alias 500** karena rutenya
wajib berparameter.

Logika yang benar **sudah ada** di `DocumentNotification::tautan()` (`:142-153`) — ia
memeriksa `$rute->parameterNames()`. Perbaikannya: jadikan ia
**`public static function urlUntuk(string $routeName, Document $document): string`**,
lalu `tautan()` dan `NotificationController::open()` sama-sama memanggilnya. Satu
tempat, dua pemakai — bukan dua salinan yang harus sepakat.

`open()` mengambil dokumennya dari `data['document_id']` yang **sudah tersimpan**
(`DocumentNotification::toArray()` `:75`); dokumen yang sudah dihapus → jatuh ke
`route($routeName)` bila rutenya tak berparameter, selain itu `dashboard`.

**Pembenahan tujuan per peristiwa.** 29 titik pembuatan notifikasi, mayoritas menunjuk
halaman daftar padahal dokumennya sudah ada di payload. Yang diubah hanya argumen
`$routeName`:

| Kelompok | Titik | `route` sekarang | Jadi |
|---|---|---|---|
| Dikembalikan / ditolak (peninjau, MD, penyetuju), revisi diajukan ke pembuat | `ReviewDecision.php:243` · `MdReviewController.php:207` · `ApprovalController.php:218` · `DocumentService.php:274` · `DocumentFeedbackService.php:139` | `documents.revisions` / `documents.index` | **`documents.edit`** — pesan berbunyi "silakan perbaiki", tujuannya form revisi |
| Perlu ditinjau / dialihkan (penerima) | `DocumentService.php:319` · `ReviewController.php:263` | `review.index` | **`review.show`** |
| Perlu ditinjau MD | `ReviewDecision.php:265` | `review.md` | **`review.md.show`** (periksa nama rutenya di `routes/web.php:326-331`) |
| Perlu disetujui | `ReviewDecision.php:285` · `MdReviewController.php:181` | `approvals.index` | **`approvals.show`** bila ada; kalau tidak, biarkan daftar |
| Kabar (lolos tahap, kini Berlaku, penolakan dibatalkan, dokumen baru di dept, peninjau off, arsip terdaftar, masukan dibalas/diadopsi, nonaktif selesai/ditolak) | 12 titik di `ReviewDecision`, `ApprovalController`, `ReviewController`, `OffDayController`, `DocumentArsipController`, `DocumentFeedbackService` | `documents.index` / `.published` / `.obsolete` | **`documents.show`** |
| Masukan lapangan baru (ke SH/DH) | `DocumentFeedbackService.php:47` | `documents.published` | **`log.masukan`** — di situlah ia ditindak |
| Revisi diajukan (kabar ke heads+MD) | `DocumentFeedbackService.php:158` | `documents.show` ⚠️ 500 | tetap `documents.show`, **sembuh sendiri** oleh perbaikan `open()` |
| Pengajuan nonaktif (2 titik) | `NonaktifController.php:236` | `nonaktif.index` | tetap — antreannya memang di sana |
| Masukan sejawat (Fase 2) | baru | — | `masukan-sejawat.show` |

Aturannya, supaya tak jadi hafalan: **notifikasi yang meminta seseorang MENGERJAKAN
sesuatu menuju layar kerjanya; notifikasi yang hanya MENGABARKAN menuju dokumennya.**

Verifikasi rute berparameter sebelum menulis: `php artisan route:list --name=approvals`
dan `--name=review`. Rute yang ternyata tak punya `{document}` tetap dibiarkan daftar —
`urlUntuk()` menanganinya dengan benar, tapi kalimat notifikasinya jangan menjanjikan
lebih dari yang bisa dituju.

### 7b — Log Pesan

**Berkas:** `app/Http/Controllers/DocumentLogController.php` · `resources/views/log/pesan.blade.php`

Sekarang tiap baris punya tombol yang sama: `documents.show` (`log/pesan.blade.php:98`).
Diganti tujuan per **tahap** + **siapa yang melihat**. Ditaruh di controller, di dalam
`baris()` (`:300-313`) yang sudah jadi satu-satunya perakit baris:

```php
/**
 * Tombol baris Log Pesan — menuju TEMPAT pesan itu ditindaklanjuti, bukan
 * selalu ke halaman dokumen. Bergantung penonton: alasan penolakan yang sama
 * mengantar PEMBUATNYA ke form revisi dan orang lain ke halaman dokumen.
 */
private function tautan(?Document $doc, string $tahap, User $user): ?array
```

| Tahap | Penonton | Tombol → route |
|---|---|---|
| `pengajuan_revisi`, `ditolak_peninjau`, `ditolak_md`, `ditolak_penyetuju` | pembuat/co-author & dokumen masih bisa disunting (`AuthorizesDocumentAccess::isEditable()` `:53-67`) | **Revisi** → `documents.edit` |
| idem | penonton lain | **Dokumen** → `documents.show` |
| `pengalihan` | peninjau yang memegangnya (`reviewer_id === $user->id`) | **Tinjau** → `review.show` |
| idem | lain | **Dokumen** → `documents.show` |
| `nonaktif_ajukan`, `nonaktif_tolak` | `Document::bisaMemutuskanNonaktif()` (`:326-333`) | **Putuskan** → `nonaktif.index` |
| idem | lain | **Dokumen** → `documents.show` |
| `balasan_masukan` | semua | **Masukan** → `log.masukan` |
| **`masukan_sejawat`** (baru) | semua | **Lihat Masukan** → `masukan-sejawat.show` |
| `musnahkan` | — | tanpa tombol — dokumennya sudah lenyap; kondisi `@if ($b['document'])` yang sudah ada sudah menanganinya |

View tinggal merender `$b['tautan']` bila tidak null — label & ikonnya ikut dari
controller, jadi tak ada peta kedua di Blade yang bisa menyimpang.

**Sumber ke-6** `dariMasukanSejawat()`, mengikuti pola lima saudaranya
(`:154`/`:189`/`:214`/`:242`/`:274`) — `MasukanSejawat` + `whereHas('document', terlihatOleh)`,
`alasan` = `ringkasan` atau, bila kosong, komentar pertama dari `catatan_json` (jangan
biarkan barisnya lenyap oleh penyaring "alasan kosong" `:101`).
Tambah `'masukan_sejawat' => ['Masukan sejawat', 'primary']` ke `self::TAHAP` (`:132-142`)
supaya ia ikut jadi pilihan penyaring.

**Test `PesanBerkontekTest`** — notifikasi ber-route `documents.show` diklik → 302 ke URL
dokumen yang benar (bukan 500); notifikasi "dikembalikan untuk revisi" → mendarat di
`documents.edit`; baris Log Pesan `ditolak_peninjau` memuat tombol `documents.edit` bagi
pembuat dan `documents.show` bagi SH; baris `masukan_sejawat` muncul di Log Pesan dan
tombolnya menuju `masukan-sejawat.show`.

**Selesai bila:** tak ada satu pun notifikasi yang mendarat di dashboard atau 500, dan
tiap baris Log Pesan mengantar ke layar tempat pesan itu bisa ditindaklanjuti.

---

## 8. Fase 4 — Satu section "Pengaturan Sistem"

**Berkas:** `resources/views/layouts/app.blade.php:1114-1129` ·
`tests/Feature/MenuPengaturanTest.php` (baru)

Blok "Administrasi" (`:1114-1119`) dan "Pengaturan Sistem" (`:1126-1129`) dilebur jadi
satu; label "Administrasi" hilang. Urutan: yang paling sering dipakai di atas.

```blade
{{-- Pengaturan Sistem (v8 Fase 4) — dulu terbelah "Administrasi" + "Pengaturan
     Sistem". Belahan itu tak pernah punya garis yang jelas: Manajemen User
     menyetel SIAPA yang ada di sistem, sama seperti Manajemen Akses menyetel
     siapa boleh apa. Section-nya ber-@canany atas ketiga izin, BUKAN
     @can('user.manage'), supaya GL — yang memegang user.approve_registration &
     audit.view — tidak kehilangan dua menunya. --}}
@canany(['user.manage','user.approve_registration','audit.view'])
    <div class="section-label">Pengaturan Sistem</div>
    @can('user.approve_registration'){!! $item(route('users.pending'), 'Persetujuan Akun', 'bi-person-check', $nav('users.pending'), $jml('akun')) !!}@endcan
    @can('user.manage'){!! $item(route('users.index'), 'Manajemen User', 'bi-people', $nav('users.index').' '.$nav('users.create')) !!}@endcan
    @can('user.manage'){!! $item(route('akses.index'), 'Manajemen Akses', 'bi-diagram-3', $nav('akses.index')) !!}@endcan
    @can('audit.view'){!! $item(route('audit.index'), 'Audit Log', 'bi-shield-lock', $nav('audit.index')) !!}@endcan
@endcanany
```

Ketiga item Fase 5 (Penomoran Dokumen, Master Data, Konfigurasi Sistem) menyusul **di
dalam blok yang sama**, semuanya `@can('user.manage')`.

**Test `MenuPengaturanTest`** — Admin melihat keempat item di bawah satu label
"Pengaturan Sistem" dan **tidak** melihat kata "Administrasi"; GL melihat label itu
dengan Persetujuan Akun & Audit Log saja; Non-Staff tak melihat labelnya sama sekali.

**Selesai bila:** sidebar Admin memuat satu section administrasi, bukan dua, dan tak ada
peran yang kehilangan menu yang dulu ia punya.

---

## 9. Fase 5 — Fitur Admin (warisan v7 §9, tidak diubah)

Isi lengkapnya **disalin utuh dari `docs/PLAN-AKSES-v7.md` §9**, termasuk tabel analisis
risiko §9.1 beserta keputusan buang/kerjakan-nya. Ringkasnya:

### 9.1 Yang dibuang & alasannya

| Fitur | Putusan |
|---|---|
| Prefix nomor site | **KERJAKAN** — dengan peringatan + contoh sebelum/sesudah + audit. Renumbering surut tak pernah ditawarkan. |
| Hapus departemen | **BUANG** — `users.department_id` & `documents.department_id` menunjuk ke sana; tak ada cara aman. Gantinya `is_active`. |
| Ubah kode departemen | **KERJAKAN bersyarat** — hanya selama departemen belum punya satu dokumen pun. |
| Tambah/hapus jenis dokumen | **BUANG** — menuntut schema JSON + template cetak yang tak bisa dibangkitkan UI (CLAUDE.md §4). |
| Aktif/nonaktif jenis | **KERJAKAN** — kolom `is_active` sudah ada. |
| Kunci API AI di basis data | **KERJAKAN dengan syarat** — cast `encrypted`, tampil bertopeng, kosong = pertahankan yang lama. |
| Uji kirim email | **KERJAKAN terbatas** — hanya ke alamat Admin yang sedang login, `throttle:3,1`. |
| Artisan dari web | **KERJAKAN terbatas** — hanya `cache:clear` + reset cache izin. Tak ada eksekusi artisan sembarang. |

### 9.2 — 5a Penomoran Dokumen (site) — ✅ SELESAI (26 Agu 2026, commit `e005f83`)

Fondasi bersama: migration `create_pengaturan_table` (`kunci` string primary, `nilai`
text nullable) + `app/Models/Pengaturan.php` dengan `ambil($kunci, $default)` dan
`simpan()`. Satu tabel melayani 5a dan 5c — itulah yang membuatnya layak dibuat.
**Sudah dijalankan di `smartpro_fresh` DAN `smartpro_uji`.**

**Berkas baru:** `app/Models/Pengaturan.php` · `app/Http/Controllers/PengaturanController.php` ·
`app/Http/Requests/SimpanPenomoranRequest.php` · `resources/views/pengaturan/penomoran.blade.php` ·
`tests/Feature/PengaturanSiteTest.php` (6/6).
**Diubah:** `DocumentNumberService` · `Document::nomorLuarPola()` · `DocumentExportController` ·
`Api\KendaliApiController` · `documents/export-excel` + 9 blade contoh nomor · `routes/web.php` ·
`layouts/app.blade.php` (menu).

Rute `pengaturan/penomoran` (GET + PUT), menu **Penomoran Dokumen** (`bi-hash`) di dalam
section Pengaturan Sistem, `can:user.manage`. Audit `pengaturan.site_diubah` memuat
`sebelum` & `sesudah` — tabel `pengaturan` sendiri tak menyimpan riwayat.

**Tiga aturan yang membuatnya aman, dan yang HARUS dipertahankan 5c saat menumpang tabel
yang sama:**

1. **Nilai bawaan tinggal di KODE** (`Pengaturan::BAWAAN`), TIDAK di-seed. Tabel kosong =
   `PPA-ADRO` / `ADARO INDONESIA`, jadi pemasangan baru & basis data uji berperilaku
   persis seperti sebelum tabel ini ada. Baris seed menghasilkan yang sebaliknya: satu
   baris hilang, dan sistemnya berhenti bernomor.
2. **Kosong = bawaan**, bukan kosong. Admin yang mengosongkan kotak prefix tak boleh
   menghasilkan nomor `-SOP-ICTMD-01`.
3. **Ingatan statis seumur-request di depan cache** (`Pengaturan::$ingatan`). Ini bukan
   kemewahan: `CACHE_STORE=database`, dan `Illuminate\Cache\DatabaseStore::many()`
   menjalankan SATU SELECT tiap `Cache::get` — sementara `nomorLuarPola()` memanggil
   `ambil()` sekali PER BARIS daftar dokumen. Tanpa ingatan itu, halaman 50 dokumen
   menambah 50 query untuk satu nilai yang sama. Dibuang `simpan()` dan
   `lupakanIngatan()` (khusus test — larik statis tak ikut rollback transaksi).

**DUA hal di luar rencana semula yang WAJIB ada:**

- **`Document::nomorLuarPola()` ikut membaca `site.prefix`.** Rencana semula melewatkan
  ini. Ia menghardcode `PPA-ADRO`, jadi begitu prefix diganti, SETIAP dokumen yang baru
  dibuat langsung dicap lencana "Nomor Lama" — persis kebalikan dari artinya. Dikunci
  `test_prefix_baru_hanya_mengenai_dokumen_baru`.
- **Bentuk prefix ditegakkan Form Request** (`^[A-Z0-9]+(-[A-Z0-9]+)*$`, maks 20; huruf
  kecil dinaikkan otomatis, bukan ditolak). Prefix berspasi akan menghasilkan nomor yang
  tak pernah cocok dengan polanya sendiri.

**Konsekuensi yang DISENGAJA, jangan "diperbaiki":** dokumen berprefix lama akan
berlencana "Nomor Lama" sesudah prefix diganti. Menyembunyikannya menuntut daftar prefix
lama — sumber kebenaran kedua yang suatu hari menyimpang, demi menutupi fakta yang benar.
Sudah diperingatkan eksplisit di layarnya.

### 9.3 — 5b Master Departemen, Jenis Dokumen & Kategori Informasi — ✅ SELESAI (26 Agu 2026)

Rute `pengaturan/master`, menu **Master Data** (`bi-diagram-2`) di dalam section
Pengaturan Sistem, `can:user.manage`. SATU view tiga kartu.

**Migration (sudah dijalankan di `smartpro_fresh` DAN `smartpro_uji`):**
`add_is_active_to_departments_table` (aditif, default true) ·
`create_informasi_kategori_table` (+ INSERT kesepuluh kategori, disalin literal).

**Berkas baru:** `app/Models/InformasiKategori.php` ·
`app/Http/Controllers/MasterDataController.php` · `app/Http/Requests/SimpanDepartemenRequest.php` ·
`SimpanJenisDokumenRequest.php` · `SimpanKategoriInformasiRequest.php` ·
`resources/views/pengaturan/master.blade.php` · `resources/views/informasi/nonaktif.blade.php` ·
`tests/Feature/MasterDataTest.php` (11/11).
**Diubah:** `Informasi` · `Department` · `DocumentType::kode()` · `InformasiController` ·
`Api\InformasiApiController` · `StoreInformasiRequest` · `Auth\RegisterRequest` +
`Api\RegisterRequest` · `DashboardController` · `DocumentController::create` ·
`Auth\RegisterController` · `layouts/app.blade.php` · `informasi/index` · `informasi/_form` ·
`documents/_distribusi-informasi` · `routes/web.php` · `InformasiTest` · `KoneksiMobileTest`.

- **Departemen** — tambah · ubah nama/alias · ubah kode (**terkunci** bila
  `documents()->exists()`, ditolak dengan pesan) · saklar aktif. **Tanpa hapus.**
  Nonaktif = hilang dari dropdown pembuatan dokumen & pendaftaran akun; dokumen dan
  penggunanya utuh.
- **Jenis dokumen** — saklar `is_active` + ubah `name`, dengan jumlah dokumen berjalan
  & Berlaku per baris. **Tanpa tambah/hapus.**
- **Kategori Informasi** — tambah · ubah nama/ikon/deskripsi · pilih kolom · saklar
  aktif. **Tanpa hapus.**

**EMPAT hal yang HARUS dipertahankan:**

1. **`DocumentType::kode()` kini membaca DB** (`is_active`), urutannya tetap dari
   `RUPA`. Inilah jebakan yang dicatat rencana semula: selama ia membaca konstanta
   telanjang, saklar `is_active` menyala tanpa menyalakan apa pun. Sidebar ikut
   menyaring dua daftar hardcoded-nya dengan `kode()` — tanpa itu jenis yang dimatikan
   tetap tampil di "Dokumen Baru" dan submenu "Status Dokumen".
2. **`slug` kategori TAK PERNAH BERUBAH.** `informasi.kategori` menyimpannya sebagai
   TEKS, bukan foreign key; mengubahnya memutus dokumennya tanpa satu pun galat yang
   terlihat — barisnya hanya berhenti muncul. Yang bisa diubah dari layar adalah
   `nama`. Slug baru dibuat dari nama + akhiran angka bila bentrok. Dikunci
   `test_slug_kategori_beku_meski_namanya_diubah`.
3. **Ingatan seumur-request `InformasiKategori::$ingatan`**, alasannya sama dengan
   `Pengaturan::$ingatan` (§9.2 aturan 3): sidebar dirender di SETIAP halaman dan
   mengulang seluruh kategori. Dibuang `MasterDataController::simpanKategori()` dan
   `lupakanIngatan()` (khusus test — properti statis tak ikut rollback transaksi).
4. **Barisnya di-INSERT di migration, bukan di seeder, dan bukan pula bawaan di kode**
   — berbeda dari tabel `pengaturan`. Di sana kuncinya tetap dan terhingga; di sini
   intinya justru baris yang BISA bertambah, dan tabel kosong berarti menu Informasi
   lenyap, bukan "berperilaku seperti sebelumnya".

**DUA hal di luar rencana semula yang WAJIB ada:**

- **`is_active` ditegakkan di Form Request pendaftaran**, bukan cuma disembunyikan dari
  dropdown. Daftar pilihan bukan penjaga: `department_id` bisa dikirim langsung, dan
  pendaftar dari HP tak pernah melihat dropdown itu sama sekali.
- **`Informasi::EKSTENSI` sengaja TETAP konstanta.** Tak ada layar yang mengubahnya,
  jadi memindahkannya hanya menghasilkan satu kolom yang tak pernah disetel siapa pun.
  Kategori baru memakai `EKSTENSI_BAWAAN` (PDF).

**Konsekuensi yang DISENGAJA, jangan "diperbaiki":** ekspor Daftar Induk untuk jenis
yang dinonaktifkan tetap tersedia. Menutupnya berarti data yang sudah ada jadi tak
terjangkau — persis yang dihindari seluruh keputusan "nonaktif ≠ hapus" di fase ini.

### 9.4 — 5c Konfigurasi Sistem (AI + Kesehatan) — ✅ SELESAI (26 Agu 2026)

Rute `pengaturan/sistem` (GET) + `pengaturan/sistem/ai` (PUT) +
`pengaturan/sistem/uji-email` (POST, `throttle:3,1`) + `pengaturan/sistem/cache` (POST),
menu **Konfigurasi Sistem** (`bi-sliders`) di dalam section Pengaturan Sistem,
`can:user.manage`. **TANPA migration** — menumpang tabel `pengaturan` milik 5a.

**Berkas baru:** `app/Http/Requests/SimpanAiRequest.php` ·
`resources/views/pengaturan/sistem.blade.php` · `tests/Feature/KonfigurasiSistemTest.php` (13/13).
**Diubah:** `Pengaturan` (+`ambilRahasia`/`simpanRahasia`/`ai()`) · `PengaturanController`
(+4 method, 259 baris) · `AppServiceProvider` · `ReviewController` · `MdReviewController` ·
`routes/web.php` · `layouts/app.blade.php` (menu).

- **AI** — saklar aktif, penyedia (gemini/openrouter), model, kunci API (tersandi,
  bertopeng), penyedia cadangan lengkap dengan model & kunci sendiri.
- **Kesehatan** — versi PHP/Laravel, zona waktu, status `APP_DEBUG`, ukuran
  `storage/app/public/lampiran` (cache 5 menit), jumlah dokumen/Berlaku/pengguna aktif,
  kartu email (mailer, host, pengirim, peringatan `MAIL_PAKSA_KE`) + tombol uji ke alamat
  sendiri, tombol bersihkan cache & reset cache izin.

**LIMA hal yang HARUS dipertahankan:**

1. **`Pengaturan::ai()` adalah SATU-SATUNYA pintu setelan AI.** Sebelum fase ini,
   `config('services.ai.enabled')` dibaca terpisah di `AppServiceProvider`,
   `ReviewController::aiAnalyze()`, dan `MdReviewController::aiAnalyze()` — tiga
   tempat. Dibiarkan, saklar di layar ini menyala tanpa mematikan apa pun: persis
   jebakan yang sudah kena sekali di `DocumentType::kode()` (§9.3 butir 1). Kedua
   controller kini hanya bertanya `$ai->isEnabled()`; binding yang memutuskan.
   Dikunci `test_saklar_mati_membuat_reviewer_null` — yang memeriksa hasil resolve
   container, bukan nilai setelannya.
2. **Baris tabel menang, `.env` jadi CADANGAN.** Setelan yang belum pernah disentuh
   dari layar tetap ikut `.env`, jadi pemasangan lama berperilaku persis seperti
   sebelum fase ini ada — aturan yang sama dengan §9.2 aturan 1.
3. **Kunci API kosong = PERTAHANKAN, bukan hapus.** Layarnya menampilkan kunci
   bertopeng, jadi Admin yang cuma mengganti model tak punya kunci asli untuk diketik
   ulang; tanpa aturan ini setiap penyimpanan biasa mencabut kunci AI diam-diam.
   Menghapus menuntut kotak centang tersendiri, dan **hapus menang atas isi baru**.
4. **Disandikan PER NILAI (`Crypt::encryptString`), bukan lewat cast `encrypted`.**
   Cast berlaku untuk SELURUH baris tabel, sementara `site.prefix` & `site.nama` sudah
   tersimpan sebagai teks biasa sejak 5a — memasangnya berarti Laravel gagal
   menyandi-balik keduanya dan sistemnya berhenti bernomor. Gagal disandi-balik →
   `null` (APP_KEY diputar), bukan halaman 500.
5. **Audit `pengaturan.ai_diubah` mencatat penyedia & model, TIDAK PERNAH kuncinya** —
   cukup penanda `diganti`/`dihapus`/`tetap`. Audit log dibaca banyak mata; kunci API di
   dalamnya sama saja dengan kunci yang bocor. Dikunci
   `test_kunci_tersandi_dan_tak_bocor_ke_audit`.

**TIGA hal di luar rencana semula yang WAJIB ada:**

- **`Illuminate\Support\Number::fileSize()` TIDAK BOLEH dipakai.** Ekstensi PHP `intl`
  tidak terpasang di XAMPP ini, dan `Number::format()` melemparkan `RuntimeException` —
  kartu Kesehatan akan 500 pada baris ukuran lampiran. Dipakai `number_format($bytes /
  1048576, 1).' MB'` biasa.
- **Penyedia dibatasi daftar tertutup** (`SimpanAiRequest::PENYEDIA`).
  `AppServiceProvider::buatReviewer()` hanya mengenal `gemini` & `openrouter`; nama lain
  diam-diam jatuh ke `NullReviewer`, yang di layar terbaca "AI dinonaktifkan" tanpa
  seorang pun tahu sebabnya salah ketik. Begitu pula **model cadangan wajib** begitu
  penyedia cadangan dipilih — penyedia tanpa model = cadangan yang tampak terpasang di
  layar tapi tak pernah menangkap kegagalan apa pun.
- **Test email memakai transport `array`, BUKAN `Mail::fake()`.** Badan
  `MailFake::raw()` KOSONG — ia tak mencatat apa pun, sehingga assert apa pun sesudahnya
  lulus meski surat benar-benar terkirim ke alamat yang salah. Yang diuji justru batas
  keamanannya: alamat lain sengaja dikirimkan dalam permintaan dan harus diabaikan.

**Konsekuensi yang DISENGAJA, jangan "diperbaiki":** menghapus kunci dari basis data
membuat sistem jatuh kembali ke nilai `.env` bila ada — bukan ke "tanpa kunci". Itulah
arti "baris tabel menang, `.env` cadangan"; menutupnya menuntut penanda "sengaja
kosong" yang menjadi sumber kebenaran ketiga.

---

## 10. Verifikasi

```powershell
php artisan test --filter=DokumenDepartemen      # Fase 1
php artisan test --filter=MasukanSejawat         # Fase 2
php artisan test --filter=PesanBerkontek         # Fase 3
php artisan test --filter=MenuPengaturan         # Fase 4
php artisan test --filter=PengaturanSite         # Fase 5a
php artisan test --filter=MasterData             # Fase 5b
php artisan test --filter=KonfigurasiSistem      # Fase 5c
php artisan test --filter=ProfilAkses            # regresi warisan v7 Fase 4
php artisan test                                 # seluruh suite — baca §3 dulu
```

Uji manual di XAMPP (`jalankan.bat`), dua akun GL sedepartemen (`GLSHE-0001` &
`23000651`, keduanya SHE):

1. **Fase 1** — GL-A buat draft. GL-B → Dokumen Departemen: draft itu ada. GL-A →
   Dokumen Departemen: **tidak** ada (hanya di Dokumen Saya).
2. **Fase 2** — GL-B klik **Beri Masukan** → layar mirip Tinjau Dokumen, **tanpa panel
   AI**, tombol hanya Batal & Kirim. Isi catatan di dua bagian + ringkasan → Kirim.
   Periksa dokumen GL-A **masih `draft`** (tidak jadi `in_review`). GL-A: lonceng
   berbunyi, Dokumen Saya menampilkan lencana angka, diklik → catatan per bagian
   terbaca dengan **nama bagian** yang benar (bukan `section_key`). Login SH: **Masukan
   Lapangan tidak bertambah** dan lencananya tetap.
3. **Fase 3** — dari lonceng, klik notifikasi "dikembalikan untuk revisi" → mendarat di
   form revisi dokumen itu, bukan daftar. Klik notifikasi "X mengajukan revisi" sebagai
   SH → **tidak 500**. Log Pesan sebagai pembuat: baris penolakan bertombol **Revisi**;
   baris yang sama dilihat SH bertombol **Dokumen**. Baris **Masukan sejawat** ada dan
   membuka isinya.
4. **Fase 4** — sidebar Admin: satu label "Pengaturan Sistem" berisi empat item, kata
   "Administrasi" hilang. Sidebar GL: label itu berisi Persetujuan Akun & Audit Log.
5. **Fase 5a/5b** — ubah prefix jadi `PPA-MSW` → dokumen baru bernomor `PPA-MSW-SOP-…`,
   dokumen lama **tidak berubah**, daftar induk lama tetap terbuka. Nonaktifkan satu
   departemen → hilang dari dropdown, dokumennya tetap terbaca. Matikan jenis SP →
   `documents/create?type=SP` menampilkan halaman "belum tersedia".
6. **Fase 5c** — Konfigurasi Sistem: matikan saklar AI → buka Tinjau Dokumen, panel AI
   **hilang** dan sisanya berjalan seperti biasa. Nyalakan lagi, ganti model saja
   (kotak kunci dibiarkan KOSONG) → Simpan → panel AI **tetap bekerja**, kuncinya tidak
   ikut tercabut. Kirim email uji → mendarat di alamat akun Admin sendiri (mailer `log`
   → isinya di `storage/logs`). Audit Log memuat `pengaturan.ai_diubah` **tanpa** kunci
   API di dalamnya.

---

## 11. Temuan & utang teknis (diperbarui 26 Agu 2026)

Dicatat di sini, bukan di kepala seseorang. Tiap baris menyebut **siapa penyebabnya**
dan **apakah aman diperbaiki** — sebab tidak semua yang terlihat salah layak disentuh.

| # | Temuan | Penyebab | Putusan |
|---|---|---|---|
| T1 | **N+1 tersembunyi** — `Pengaturan::ambil()` dipanggil per baris daftar dokumen, dan penyimpan cache `database` menjalankan satu SELECT tiap `Cache::get`. | Fase 5a | ✅ **SUDAH DIPERBAIKI** — ingatan statis seumur-request (§9.2 aturan 3). Jangan dibuang. |
| T3 | **Badge "Nomor Lama" menyebar** ke dokumen berprefix lama sesudah prefix diganti. | Fase 5a, **disengaja** | ❌ **JANGAN diperbaiki.** Lihat §9.2 — alternatifnya sumber kebenaran kedua. |
| T4 | **310 test mati** karena akun contoh yang tak akan kembali (keputusan I). | Warisan | ✅ **SELESAI (Fase 6).** 336 pemanggilan NRP contoh di 54 berkas diganti aktor berbasis peran yang dipetakan ke **NRP pengguna sungguhan** (ketetapan pemilik), dipusatkan di `Tests\TestCase::NRP_AKTOR`. Suite penuh **527 hijau, 0 merah**. |
| T6 | **Ekstensi PHP `intl` tidak terpasang.** `Illuminate\Support\Number::*` melempar `RuntimeException`, bukan jatuh ke format biasa. | Lingkungan | ❌ **Jangan pakai `Number::`** di kode baru — `number_format()` biasa. Memasang `intl` adalah perubahan lingkungan produksi; keputusan pemilik. |
| T5 | *(pemilik: BIARKAN menggantung — berkas itu hanya aturan & panduan memulai sesi, bukan prasyarat kerja.)* **`docs/HANDOVER-SESI-BARU.md` tidak ada** padahal CLAUDE.md §17 menyuruh membacanya PERTAMA tiap sesi baru. Rujukan yang menunjuk ke ruang kosong. | Warisan | ⬜ Perlu keputusan pemilik: dibuat, atau rujukannya di CLAUDE.md §17 diarahkan ke dokumen ini. |

### Bukti keadaan sekarang (suite penuh, 26 Agu 2026)

```
Tests:  312 failed, 191 passed (1437 assertions)  ·  ~46 detik
```

Ke-312 itu **seluruhnya sebab lingkungan**, nol menyentuh kode v8:

- 310 × `ModelNotFoundException` pada NRP contoh — gagal di
  `User::where('nrp', …)->firstOrFail()`, SEBELUM kode yang diuji berjalan;
- 2 × `MdReviewStageTest` — dokumen contoh IK tak ada, dan `SH-0001` (NRP yang sama,
  hanya di-assert rapi alih-alih melempar).

Karena `DocumentExportTest` termasuk yang terblokir, `PengaturanSiteTest` sengaja
**memanggil rute ekspornya sendiri** dan mencocokkan `content-disposition` byte-per-byte
— bukan berhenti di `namaSite()`. Nama berkas bawaan terbukti identik dengan sebelum
Fase 5a.

---

## 12. Di luar scope (sengaja tidak dikerjakan)

- **Membalas masukan sejawat.** Ia catatan satu arah dari rekan; percakapan dua arah
  menuntut status, notifikasi balik, dan "sudah selesai belum" — itu `document_feedback`,
  dan kanalnya memang sudah ada untuk dokumen Berlaku.
- **Mengadopsi masukan sejawat jadi alasan revisi.** Dokumennya belum Berlaku; tak ada
  revisi Tipe B yang perlu dibuat — pembuatnya tinggal menyunting drafnya.
- **Masukan sejawat dari SH/DH/PJO.** Mereka peninjau & penyetuju; menyalurkan catatan
  lewat kanal kedua akan menyaingi jalur tinjauan resminya.
- **Halaman "Semua Notifikasi" di web.** Lonceng menampilkan 8 terbaru; API mobile sudah
  paginate (`Api\NotificationApiController::index()`).
- **Membuang atribut `annotations_ai` dari `review/show.blade.php`.** Empat cabang render
  disentuh demi nol perbedaan yang terlihat.
- **Mengubah gate `beri-masukan` atau `document_feedback`.** Kanal lapangan Non-Staff
  tidak bergeser sedikit pun di rencana ini.
- **Renumbering surut** dokumen terbit ke prefix site baru — merusak rujukan audit yang
  dipakai di luar sistem.
- **Menambah/menghapus jenis dokumen dari layar** — menuntut schema JSON + template
  cetak yang tak bisa dibangkitkan UI (CLAUDE.md §4).
- **Menghapus departemen** — tak ada cara aman selama pengguna & dokumennya menunjuk ke
  sana.
- **Profil akses untuk peran selain GL** — SH/DH/PJO/Non-Staff tak menyusun dokumen.
- **Memindahkan knob `config/smartpro.php`** (batas peninjau, hari pita, ambang beban,
  jenis wajib MD) ke layar — tidak dipilih pemilik.
- **Merapikan ~30 berkas test agar tak bergantung NRP seeder** (§3 jalan B) — pekerjaan
  tersendiri. **Naik status 26 Agu 2026:** sesudah pemilik menetapkan kelima NRP contoh
  tak akan kembali, ini bukan lagi kerapian melainkan satu-satunya cara menghidupkan
  kembali 310 test. Layak jadi fase tersendiri sesudah Fase 5.
