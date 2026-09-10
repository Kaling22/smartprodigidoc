# PATOKAN GAYA V2 — angka yang mengikat 35 halaman berikutnya

> Induk: `docs/REDESAIN-UI-V2.md` (kit, ikon, palet, kerangka) ·
> `docs/DASBOR-V2-REVISI.md` (dasbor) · `docs/REVISI-UI-V3.md` (R1–R12).
> Aturan yang tetap menang: `CLAUDE.md`.
>
> Status: **Fase 0 SELESAI** (2026-09-01) · **Fase 1 SELESAI** (2026-09-01,
> sembilan gerbang hijau — §9) · **Fase 2a SELESAI** (2026-09-01, lima halaman
> dokumen, sembilan gerbang hijau — §10) · **Fase 2b SELESAI** (2026-09-01,
> lima halaman dokumen sisa, sembilan gerbang hijau — §11) · **Fase 3 SELESAI**
> (2026-09-01, enam halaman tinjau & persetujuan, sembilan gerbang hijau —
> §12) · **Fase 4 SELESAI** (2026-09-01, delapan halaman pengguna & akses,
> sembilan gerbang hijau — §13) · **Fase 5 SELESAI** (2026-09-01, empat halaman
> Informasi, sembilan gerbang dijalankan — §14) · **Fase 6 SELESAI**
> (2026-09-01, tujuh halaman pengaturan & log, sembilan gerbang dijalankan —
> §15). Rute A mengembarkan 35 halaman + komponen sisa ke V2 (§7);
> **sisa 0 halaman** — pengembaran SELESAI, yang tertinggal tinggal Fase 7
> (pindahkan `pages/V2/*` ke `pages/*`, cabut sakelar & kit lama).

---

## 1. Kenapa berkas ini ada

Enam halaman sudah bergaya V2. Tiga puluh lima menyusul. Tanpa satu daftar
angka yang mengikat, tiap halaman akan **menebak sendiri** radius, tinggi
kendali, ukuran ikon, dan bentuk modalnya — dan tiga puluh lima tebakan yang
masing-masing masuk akal tetap menghasilkan aplikasi yang tidak konsisten.

Yang lebih buruk: ketidakcocokan seperti itu tak pernah membuat satu pun gerbang
merah. `tsc` tak peduli `rounded-lg` atau `rounded-4xl`; `uji-render` cuma peduli
halamannya tergambar. Ia hanya terlihat oleh mata, satu per satu, sesudah 35
halaman terlanjur ditulis.

**Seluruh angka di bawah DIUKUR dari kode yang sudah ada, bukan dikarang.**
Perintah pengukurnya ikut ditulis (§6) supaya siapa pun bisa membuktikan
ulang — dan supaya angka yang berubah ketahuan, bukan diperdebatkan.

---

## 2. Kit — dari mana komponen diambil

| | V2 (pakai ini) | V1 (jangan, di pohon V2) |
|---|---|---|
| Komponen | `@/components/ui-maia/*` | `@/components/ui/*` |
| Ikon | `@hugeicons/core-free-icons` + `@hugeicons/react` | `lucide-react` |
| Bersama | `@/components/v2/*` | `@/components/*` |
| Kerangka | `@/layouts/V2/AppLayout` · `@/layouts/V2/AuthLayout` | `@/layouts/*` |

Keduanya adalah **modul terpisah**, jadi mencampurnya bukan cuma soal rupa: dua
`ui/tooltip` yang berbeda berarti dua React context, dan Radix **melempar**
— bukan diam — bila Root dipakai di luar Provider-nya. Itulah kegagalan yang
melahirkan `smartpro:uji-render` (REDESAIN-UI-V2 §4b), dan hasilnya LAYAR PUTIH.

> **Dijaga tes**, bukan kedisiplinan: `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama`
> menolak `from 'lucide-react'` dan `@/components/ui/` di mana pun di dalam
> `components/v2/`, `pages/V2/`, dan `layouts/V2/`. Menyebutnya di KOMENTAR
> tetap boleh — dua komentar seperti itu memang sudah ada dan sengaja.

### Yang TIDAK boleh disunting

`components/ui/**` **dan** `components/ui-maia/**` — CLAUDE.md §4. Seluruh
penyesuaian lewat `className` di tempat pemanggilan atau `variant` cva. Kalau
sebuah komponen registry terasa kurang, tarik ulang bloknya; jangan menambal
berkasnya.

---

## 3. Angka yang mengikat

### 3.1 Logo

| Tempat | Ukuran | Catatan |
|---|---|---|
| Sidebar terbentang | `h-12` | di dalam `SidebarMenuButton` ber-`h-16` |
| Sidebar menciut | `h-6` | `group-data-[collapsible=icon]:h-6` |
| Halaman tamu (login) | `h-12` | **disamakan di Fase 0** — dulu `h-8` |
| Logo di panel merek | — | dicabut V3 Fase 2b |

Berkasnya **4000×2000 = 2:1**, jadi `h-12` berarti lebar 96px. Rel sidebar
18rem (288px) dan kolom login `max-w-xs` (320px) dua-duanya jauh lebih lebar,
sehingga angka yang sama aman di kedua tempat — dan itulah yang membuatnya
boleh disamakan, bukan selera.

**Selalu dua berkas, bukan satu yang difilter:** `logo-web.png` + `dark:hidden`
dan `logodarkmode.png` + `hidden dark:block`. Tema bisa berubah tanpa memuat
ulang halaman, dan filter CSS pada logo berwarna menghasilkan warna yang bukan
merek.

`w-auto object-contain` wajib menemani tiap `h-*` — tanpanya logo 2:1 gepeng.

### 3.2 Radius — dari kit, bukan diketik ulang

| Bentuk | Kelas | Datang dari |
|---|---|---|
| Kendali (tombol, input, select, badge, dialog) | `rounded-4xl` | bawaan `ui-maia` — **jangan ditulis ulang** |
| Kartu | `rounded-2xl` | bawaan `ui-maia/card` |
| Textarea | `rounded-xl` | bawaan `ui-maia/textarea` |
| Kotak buatan sendiri yang berperan sebagai kartu | `rounded-2xl` | samakan dengan `card` |
| Avatar & titik | `rounded-full` | |

Maia berbentuk **pil** (`rounded-4xl`), nova berbentuk kotak tumpul
(`rounded-md`/`rounded-lg`). Itu perbedaan paling kentara antara dua kerangka,
jadi satu `rounded-md` yang tersasar ke halaman V2 langsung terbaca sebagai
"halaman ini belum digarap".

Aturannya: **jangan menulis `rounded-*` pada komponen yang sudah punya.**
Menuliskannya lagi bukan cuma mubazir — ia membekukan angka yang seharusnya ikut
kalau kit-nya diperbarui.

### 3.3 Tinggi kendali

| Kendali | Tinggi |
|---|---|
| Tombol & input & select baku | `h-9` |
| Tombol kecil / dalam `InputGroup` | `h-8` (`size="icon-sm"`) atau `h-6` (`icon-xs`) |
| Baris kepala tabel | `h-12` |
| Sub-item sidebar | `h-7`, lencananya `h-5` + `top-1` |
| Lencana status | `h-5` |

`h-9` adalah tinggi baku maia. Kotak isian yang dibungkus `InputGroup` **tetap**
`h-9` karena tingginya pindah ke pembungkusnya — itu sebabnya tombol mata di
halaman login tak perlu satu angka pun diketik (REVISI-UI-V3 §5.2).

### 3.4 Ikon

```tsx
import { NamaIcon } from '@hugeicons/core-free-icons';
import { HugeiconsIcon } from '@hugeicons/react';

<HugeiconsIcon icon={NamaIcon} strokeWidth={1.5} className="size-4" />
```

| Konteks | Ukuran | `strokeWidth` |
|---|---|---|
| Baku (dalam tombol, menu, baris) | `size-4` | `1.5` |
| Rapat (dalam lencana, teks kecil) | `size-3.5` | `1.5` |
| Sangat kecil (≤ `size-3`) | `size-3` | **`2`** |
| Hiasan besar (kosong/empty state) | `size-8` | `1.5` |

**`strokeWidth={2}` di bawah `size-3` bukan penyimpangan, melainkan keharusan:**
garis 1.5px pada glif 12px praktis hilang. Dua tempat yang sudah memakainya —
`LencanaDelta` (panah delta) dan `DataTable` (anak panah urut) — adalah
preseden, bukan kelalaian.

**Ikon dari SERVER lewat `v2/Ikon.tsx`, ikon KENDALI diimpor langsung.** Kunci
`bi-*` datang dari `Document::STATUS_META`, `NavigasiSidebar`, `AntreanTugas`,
dan kolom `informasi_kategori.ikon` — semuanya konstanta PHP atau baris tabel,
dan satu kunci yang bergeser jatuh DIAM ke lingkaran netral. Ikon yang murni
tampilan (mata sandi, cari, terang/gelap) tak punya kunci di sana dan tak boleh
dipaksa punya.

### 3.5 Warna & status — nol hex, selamanya

- Warna dan ikon status **datang dari props** `statusMeta`/`statusLabels`
  (CLAUDE.md §4). Tak pernah diketik di TSX.
- Warna lain memakai token: `--primary`, `--muted-foreground`, `--destructive`,
  `--border`. Butuh varian lebih lembut? Pakai opasitas token yang sama
  (`bg-primary/10`), bukan warna baru.
- Teks di atas latar `--primary` selalu `--primary-foreground`. Pasangan itu
  kontras menurut definisi, di terang maupun gelap.
- Palet grafik dari `REDESAIN-UI-V2 §6` — dihitung, bukan dikira.

### 3.6 Irama halaman

`layouts/V2/AppLayout` sudah memasang semuanya **sekali**:

```
@container/main · gap-4 py-4 md:gap-6 md:py-6 · talang px-4 lg:px-6
```

**Jangan mengulanginya di halaman.** Halaman menerima `judul`, `deskripsi`,
`aksi`, dan `remah` sebagai prop dan cuma mengisi isinya. Talang yang diulang
menghasilkan padding ganda yang cuma terlihat di layar sempit.

### 3.7 Tabel

Semua daftar lewat `components/v2/DataTable`, yang sudah membungkus dirinya
`overflow-x-auto` dan menempelkan kepala tabel (`sticky top-0`).

| Bagian | Aturan |
|---|---|
| Kolom aksi | `w-px text-right whitespace-nowrap` |
| Sel aksi | **tanpa** `flex-wrap` (REVISI-UI-V3 R4) |
| Pengurutan | tetap di **server**, lewat query string |
| Penyaringan | debounce 300 ms + `preserveState` + `preserveScroll` + `replace` (R5) |
| Lebar tetap | `table-fixed` + persen per kolom bila judul bisa panjang |

`w-px` membuat kolom menyusut ke isinya — dan karena `DataTable` menggulir
sendiri, kolom sesempit itu tak pernah memaksa halaman menggulir mendatar.

### 3.8 Modal & konfirmasi

Satu komponen untuk semua: `components/v2/ConfirmDialog`.

- **Berpemicu** — oper `pemicu`.
- **Dikendalikan** — oper `buka` + `onUbahBuka`, tanpa `pemicu`. Wajib dipakai
  di dua keadaan yang dua-duanya pernah jadi bug: dialog yang dipicu dari dalam
  `StripAksi` (Radix melepas isi menu begitu item dipilih, jadi dialog di
  dalamnya lenyap sebelum terlihat), dan "isi formulir dulu, konfirmasi saat
  submit" (tombolnya tetap `type="submit"` supaya `required` peramban hidup).
- `destruktif` hanya menukar warna tombol konfirmasi. Susunannya tak berubah —
  yang membedakan Hapus dari Kirim adalah akibatnya, bukan letak tombolnya.

Dialog **bukan** konfirmasi (formulir dalam jendela) memakai `ui-maia/dialog`.

### 3.9 Formulir

```tsx
<FieldGroup>
  <Field data-invalid={!!errors.x || undefined}>
    <FieldLabel htmlFor="x">Label</FieldLabel>
    <Input id="x" aria-invalid={!!errors.x} … />
    <FieldError errors={errors.x ? [{ message: errors.x }] : undefined} />
  </Field>
</FieldGroup>
```

- `htmlFor` ↔ `id` wajib berpasangan.
- `aria-invalid` pada kendali **dan** `data-invalid` pada `Field`.
- Galat datang dari `errors` Inertia, bukan state sendiri.
- Kendali dengan tombol tempelan memakai `InputGroup`, bukan tombol yang
  di-`absolute`-kan di atas `Input`.
- `Field orientation="horizontal"` untuk checkbox/radio berlabel samping.

---

## 4. Yang datang dari SERVER, dan tak boleh pindah ke TSX

Diulang di sini karena inilah aturan yang paling mudah dilanggar tanpa sadar
saat menyalin halaman (CLAUDE.md §4):

- peta status & warnanya · daftar izin · kandidat peserta alur
- pengurutan, penyaringan, dan paginasi daftar
- keputusan siapa melihat widget apa

Halaman V2 menggambar apa yang dikirim. Kalau sebuah nilai belum ada di props,
**tambahkan di controller**, jangan hitung di TSX.

Dan setiap kali sebuah model pindah ke props: periksa **kolom per kolom**.
Sudah tujuh kali nyaris bocor, dua di antaranya bukan model `User` sama sekali
melainkan jalur berkas privat (`arsip_path`, `informasi.file_path`).

---

## 5. Nol berkas dihapus tanpa konfirmasi

Empat berkas nol pengimpor sengaja **ditahan** (K3/E6), dan jumlahnya akan
bertambah saat 35 halaman dikembarkan. Jangan menghapus satu pun sebagai
"pembersihan" — penghapusan butuh konfirmasi eksplisit (CLAUDE.md §4).

Daftarnya hidup di `docs/REVISI-UI-V3.md` §7.

---

## 6. Cara membuktikan patokan ini, bukan sekadar memercayainya

```bash
# radius & tinggi bawaan tiap komponen maia
for f in button input card dialog alert-dialog badge table; do
  echo "$f: $(grep -oh 'rounded-[a-z0-9]*' resources/js/components/ui-maia/$f.tsx | sort -u | tr '\n' ' ')"
done

# sebaran strokeWidth & ukuran ikon di pohon V2
grep -roh 'strokeWidth={[0-9.]*}' resources/js/components/v2/ resources/js/pages/V2/ | sort | uniq -c

# pencampuran kit — HARUS kosong (dijaga PratinjauUiTest juga)
grep -rn "from 'lucide-react'\|@/components/ui/" \
  resources/js/components/v2/ resources/js/pages/V2/ resources/js/layouts/V2/

# halaman yang belum punya kembaran V2
cd resources/js/pages && for f in $(find . -name '*.tsx' -not -path './V2/*' | sed 's|^\./||'); do
  [ -f "V2/$f" ] || echo "$f"
done
```

---

## 7. Urutan fase berikutnya — Rute A (keputusan pemilik 2026-09-01)

Kembarkan dulu, pindahkan belakangan. Sakelar pratinjau tetap hidup sampai
akhir, jadi tiap halaman bisa dibandingkan berdampingan dengan V1-nya.

| Fase | Isi | Jumlah | Status |
|---|---|---|---|
| **0** | patokan gaya (berkas ini) + logo login disamakan + tes anti-campur kit | — | `[x]` **selesai 2026-09-01** |
| **1** | komponen bersama — `DialogAksi`, `DaftarMasukan`, `RincianPembaca`, `FormInformasi`, `LencanaStatusAkun` **baru**; `StripAksi`, `NomorDokumen`, `PenyaringDokumen` sudah ada sejak T2/V3; `KartuPerjalanan` **dilewati** (§9) | 5 baru | `[x]` **selesai 2026-09-01** |
| **2a** | Dokumen, inti — `Show`, `Published`, `Obsolete`, `Revisions`, `Distribution` | 5 | `[x]` **selesai 2026-09-01** |
| **2b** | Dokumen, sisa — `StaffStatus`, `Unavailable`, `RincianInformasi`, `Arsip/Catatan`, `Arsip/Edit` | 5 | `[x]` **selesai 2026-09-01** |
| **3** | Tinjau & persetujuan — `Review/Index`, `Review/Md`, `Approvals/Index`, `Approvals/Show`, `MasukanSejawat/Show`, `Nonaktif/Index` | 6 | `[x]` **selesai 2026-09-01** |
| **4** | Pengguna & akses — `Users/{Index,Create,Edit,Pending}`, `Akses/Index`, `Account/Info`, `Auth/Register`, `Auth/Pending` | 8 | `[x]` **selesai 2026-09-01** |
| **5** | Informasi — `Index`, `Create`, `Perbarui`, `Nonaktif` | 4 | `[x]` **selesai 2026-09-01** |
| **6** | Pengaturan & log — `Pengaturan/{Master,Penomoran,Sistem}`, `Audit/Index`, `JobExecutions/Index`, `Log/Masukan`, `Log/Pesan` | 7 | `[x]` **selesai 2026-09-01** |
| **7** | Tranche 4 — `pages/V2/*` → `pages/*`, hapus factory + sakelar, cabut `lucide-react` & `components/ui/**` | — | `[ ]` |

**Komponen didahulukan** dan itu bukan urutan sembarang: sembilan berkas itu
dipakai oleh halaman-halaman di fase 2–6. Mengerjakannya belakangan berarti tiap
halaman menunggu, atau lebih buruk, tiap halaman membuat versinya sendiri.

Tiap fase berhenti untuk review (CLAUDE.md §5), dan tiap fase menjalankan
sembilan gerbang `docs/REVISI-UI-V3.md` §6.

### Yang TIDAK ikut dikerjakan

- **94 Blade di `resources/views/`.** Diperiksa: `grep "return view("` di
  seluruh `app/Http/Controllers/` mengembalikan **nol** — halaman-halaman itu
  sudah mati sejak Fase 12. CLAUDE.md §2 menahannya sebagai acuan mata
  `CEKLIS-MATA-PER-PERAN.md`, lalu menghapusnya di Fase 13.1. Mendandaninya
  berarti mengerjakan berkas yang antre dibuang.
- **Mesin cetak (§14b, 23 berkas)** — bukan lapisan tampilan, dan `PdfRenderer`
  menyinggah berdasarkan `versiKodeCetak()`. Selama kode cetak tak berubah, PDF
  hasilnya identik; itulah satu-satunya jaminan "ekspor tetap sama".
- `components.json` — tetap `radix-nova`/`lucide`/`ui` sampai fase 7.

---

## 8. Gerbang Fase 0 — sembilan, semuanya hijau (2026-09-01)

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — **+1**, tes anti-campur kit |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `git diff components.json` | KOSONG |

### Yang benar-benar disunting di Fase 0

Fase ini **hampir seluruhnya dokumen** — itu memang gunanya. Kode yang berubah
persis tiga tempat:

| Berkas | Perubahan |
|---|---|
| `docs/PATOKAN-GAYA-V2.md` | berkas ini (baru) |
| `layouts/V2/AuthLayout.tsx` | logo `h-8` → `h-12` (§3.1) + foto panel `login.jpg` |
| `public/images/login.jpg` | aset baru, kiriman pemilik (56 KB) |
| `tests/Feature/PratinjauUiTest.php` | +1 tes anti-campur kit + helper rekursif |

Tes barunya sudah dibuktikan **MERAH** dengan menyisipkan satu
`import { Bell } from 'lucide-react'` ke berkas V2 BERSARANG
(`v2/dasbor/KartuLog.tsx`) lalu memulihkannya — jadi sapuannya benar-benar
rekursif dan penjaganya benar-benar menggigit, bukan tes yang lolos dua-duanya.

---

## 9. Fase 1 — komponen bersama (2026-09-01)

### Daftar §7 dikoreksi sebelum menulis satu baris pun

Rencana menyebut sembilan komponen. Diukur ulang dengan sapuan §6 (seluruh
impor non-`ui/` di 35 halaman V1 yang belum berkembaran), angkanya **lima**:

| Komponen | Keadaan |
|---|---|
| `StripAksi` · `NomorDokumen` · `PenyaringDokumen` | kembarannya **sudah ada** sejak T2 / V3 Fase 1, isinya diperiksa dan sudah twin penuh |
| `DialogAksi` · `DaftarMasukan` · `RincianPembaca` · `FormInformasi` · `LencanaStatusAkun` | **dikerjakan di fase ini** |
| `KartuPerjalanan` | **dilewati** — keputusan pemilik 2026-09-01 |

`KartuPerjalanan` dilewati karena satu-satunya pengimpornya `pages/Dashboard.tsx`
(V1), sementara `pages/V2/Dashboard.tsx` sejak V3 R9 memakai
`v2/dasbor/LacakStatus.tsx` — yang komentarnya sendiri menyatakan ia MEMANG
tabel `KartuPerjalanan` V1 yang dipindah ke maia. Kembarannya karena itu akan
lahir dengan nol pengimpor dan nol pemakai yang menunggunya; Fase 7 membuang
`pages/Dashboard.tsx` beserta komponennya. Ini **bukan** penghapusan berkas —
`components/dasbor/KartuPerjalanan.tsx` tetap di tempatnya (§5).

### Berkas baru — semuanya di `components/v2/` ROOT

Letaknya rata, mengikuti preseden `dokumen/StripAksi` → `v2/StripAksi`:
`v2/` root menampung komponen bersama, sedangkan `v2/dokumen/fields`,
`v2/tinjau`, dan `v2/dasbor` tetap untuk subsistemnya.

| Berkas | Baris | Menunggu di fase |
|---|---|---|
| `v2/DialogAksi.tsx` | 600 | 2 & 6 — `Show`, `Published`, `Obsolete`, `Log/Masukan` |
| `v2/FormInformasi.tsx` | 282 | 5 — `Informasi/{Create,Perbarui}` |
| `v2/DaftarMasukan.tsx` | 163 | 2 — `Show`; juga dipakai `v2/DialogAksi` |
| `v2/RincianPembaca.tsx` | 120 | 2 — `Show`, `RincianInformasi` |
| `v2/LencanaStatusAkun.tsx` | 44 | 4 — `Users/Index`, `Account/Info` |

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.

### Penyimpangan dari berkas asli — semuanya patokan Fase 0, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.9 | `Label` + kendali + `<p className="text-destructive">` → `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`. Bertambah `aria-invalid` pada kendali & `data-invalid` pada `Field`; galat tetap dari `errors` Inertia |
| §3.2 | `rounded-lg` pada kotak yang berperan kartu → `rounded-2xl`; `rounded-full` dilepas dari `<Avatar>` karena `v2/Avatar` sudah membawanya |
| §3.5 | `emerald-600`/`dark:emerald-400` → `text-chart-5`, hijau palet `.ui-v2`. Preseden `v2/dasbor/KartuLog.tsx`. Nol warna baru |
| §3.4 | tiap ikon eksplisit `strokeWidth={1.5}` + `size-4` / `size-3.5` |

Enam belas nama hugeicons diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
tidak melempar galat tipe, ia cuma menggambar kosong.

**Yang sengaja TIDAK diubah:** baris centang Masukan Lapangan di `DialogRevisi`
tetap `<Label>` yang MEMBUNGKUS checkbox beserta seluruh kutipannya. Dipindah ke
`Field orientation="horizontal"`, label jadi SAUDARA checkbox dan mengklik badan
kartu berhenti mencentang — perilaku yang hilang tanpa satu pun gerbang merah.

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama` | lulus — nol `lucide-react` / `@/components/ui/` di lima berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — lihat catatan di bawah |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja.** Fase ini menggambar; ia tak menambah satu
pun keputusan server. Tes Inertia membaca PROPS, dan komponen yang salah gambar
tetap mengirim props yang sama — asersi props untuk pekerjaan menggambar cuma
menambah tes yang tak pernah bisa merah (patokan §14c yang sama dengan DASBOR-V2
Fase 2–3).

### Baseline cetak: satu berkas ditangkap, dan itu DATA bukan kode

`smartpro:cetak-baseline` mula-mula keluar kode 1 dengan
`HILANG SOP_PPA-ADRO-SOP-ICTMD-05_id1241.pdf` — kelas yang sama persis dengan
temuan REVISI-UI-V3 §3. Buktinya tiga, sama seperti dulu: md5 mesin cetak
**23/23 OK**, Fase 1 **nol menyentuh PHP**, dan enam berkas lain berbunyi `sama`.
Dokumen id1241 lahir lewat peramban sesudah tangkapan terakhir.

Keputusan pemilik 2026-09-01: **tangkap**. `--tulis --id=1241` (93.315 B, sidik
`80f85c3a…`). Tak ada berkas lama yang ditimpa — id1241 belum punya baseline
sama sekali, jadi murni penambahan dan tak perlu salinan `*.pra-*`.

### Yang gerbang TIDAK periksa di fase ini — supaya tak lolos diam-diam

Kelima berkas baru belum punya satu pun pengimpor, jadi `npm run build`
mengeluarkannya dari bundel dan **kedua `smartpro:uji-render` tak pernah
menggambarnya**. Kegagalan kelas "Radix di luar Provider-nya → LAYAR PUTIH"
(REDESAIN-UI-V2 §4b) karena itu baru bisa ketahuan di Fase 2–6, saat halaman
pertamanya memasang komponen ini. Itu konsekuensi langsung dari urutan
"komponen didahulukan" (§7), bukan gerbang yang terlewat — dan alasan kenapa
halaman PERTAMA yang memakai `DialogAksi` layak dijalankan di peramban, bukan
cuma dilewatkan gerbang.

---

## 10. Fase 2a — lima halaman dokumen inti (2026-09-01)

### Kenapa Fase 2 dipecah

Keputusan pemilik 2026-09-01: kerjakan dulu `Show` + empat daftar, sisanya di
2b. Alasannya bukan besarnya pekerjaan melainkan §9 — kelima komponen Fase 1
lahir tanpa satu pun pengimpor, jadi **tak satu gerbang pun pernah
menggambarnya**. `Show` adalah halaman pertama yang memasang tiga di antaranya
(`DialogAksi`, `DaftarMasukan`, `RincianPembaca`), dan kegagalan kelas "Radix di
luar Provider-nya → LAYAR PUTIH" baru bisa ketahuan di situ. Mendahulukannya
berarti utang gerbang Fase 1 lunas di halaman pertama, bukan di halaman
kesepuluh.

### Berkas baru — lima, semuanya di `pages/V2/Documents/`

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.

| Berkas | Baris | Yang dipakainya pertama kali |
|---|---|---|
| `V2/Documents/Show.tsx` | 307 | `v2/DialogAksi`, `v2/DaftarMasukan`, `v2/RincianPembaca` |
| `V2/Documents/Published.tsx` | 308 | `v2/DialogAksi` (empat jendela), `v2/StripAksi` |
| `V2/Documents/Obsolete.tsx` | 352 | `DialogMusnahkan`, `perluas()` DataTable |
| `V2/Documents/Distribution.tsx` | 292 | `v2/dasbor/PitaCakupan` di luar dasbor |
| `V2/Documents/Revisions.tsx` | 136 | — arketipe daftar terpendek |

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | penyaring pindah ke `CardHeader className="border-b"`, tabel ke `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` — SATU kartu, bukan kartu penyaring bertumpuk di atas kartu tabel (arketipe `V2/Documents/Index.tsx`) |
| §3.7 | sel aksi `Distribution` kehilangan `flex-wrap` (REVISI-UI-V3 R4) |
| §3.6 | paragraf keterangan & saklar sumber pindah ke prop `sub`/`aksi` `AppLayout` — talang & irama dipasang sekali di layout, bukan diulang di halaman |
| §3.4 | ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons`; yang berkunci SERVER (`rupa[j]` di menu Export, `log.meta[1]` di timeline) tetap lewat `v2/Ikon`. `strokeWidth={2}` dipakai pada lencana `size-3` |
| §3.5 | peta `RONA` timeline `Show`: `sky-600`/`emerald-600`/`amber-500` → `chart-2`/`chart-5`/`chart-3`. Keadaan kosong `Revisions`: `emerald-600 dark:emerald-400` → `text-chart-5`. Nol warna baru |
| §3.2 | pita peringatan `Distribution` `rounded-lg` → `rounded-2xl`; `rounded-full` dilepas dari `<Avatar>` di timeline `Show` — `v2/Avatar` sudah membawanya |
| — | keadaan kosong keempat daftar memakai komponen `empty` resmi, bukan rakitan `div` + ikon |

**Yang sengaja TIDAK diubah:** tabel versi yang dibentangkan di `Obsolete`
tetap `<table>` polos. Ia hidup DI DALAM `perluas()` milik `DataTable`
induknya, jadi `DataTable` kedua di situ berarti pembungkus `overflow-x-auto`
dan kepala `sticky` bersarang di dalam pembungkus yang sama — dua gulungan
mendatar bertumpuk, bukan tabel yang lebih rapi.

Sembilan nama hugeicons baru diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
menggambar kosong tanpa galat apa pun.

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat — empat daftar barunya tergambar sebagai `V2/Documents/*` |
| `php artisan smartpro:uji-render --pratinjau --uri=/documents/1226 --uri=/documents/1241` | **30 halaman**, nol galat — `V2/Documents/Show` benar-benar DIRENDER (lihat di bawah) |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama` | lulus — nol `lucide-react` / `@/components/ui/` di lima berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — nol berkas hilang kali ini |
| `git diff components.json` | KOSONG |

### Utang gerbang §9 LUNAS — dan cara membuktikannya ulang

`documents.show` berparameter, jadi sapuan bernama tak pernah menyentuhnya.
`--uri` yang lahir di tranche 2 justru untuk ini:

```bash
# Git Bash MENGUBAH argumen berawalan "/" jadi jalur Windows sebelum PHP
# melihatnya — tanpa MSYS_NO_PATHCONV, "--uri=/documents/1226" sampai ke
# perintah sebagai "C:/Program Files/Git/documents/1226" dan cuma menghasilkan
# peringatan "tidak menghasilkan halaman Inertia".
MSYS_NO_PATHCONV=1 php artisan smartpro:uji-render --pratinjau     --uri=/documents/1226 --uri=/documents/1241
```

Keduanya berbunyi `oke  V2/Documents/Show`, jadi `DialogAksi`,
`DaftarMasukan`, dan `RincianPembaca` kini sudah benar-benar digambar mesin —
bukan cuma dicek tipenya. Yang **masih** belum tergambar dari Fase 1 tinggal
`FormInformasi` (menunggu Fase 5) dan `LencanaStatusAkun` (menunggu Fase 4).

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9 dan DASBOR-V2 Fase 2–3).

### Yang gerbang TIDAK periksa di fase ini

Gerbang render membuka tiap halaman sebagai **satu** persona (`ADM-0001`, lalu
tamu, lalu akun belum aktif). Cabang yang hanya hidup bagi peran lain — strip
aksi Non-Staff di `Published`, tombol Rollback di `Obsolete`, panel Distribusi
yang `null` bagi yang tak berwenang — karena itu tak pernah dirender oleh mesin
mana pun. Itu pekerjaan `docs/CEKLIS-MATA-PER-PERAN.md`, bukan gerbang.

---

## 11. Fase 2b — lima halaman dokumen sisa (2026-09-01)

### Berkas baru — lima, semuanya di `pages/V2/Documents/`

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Documents/Arsip/Edit.tsx` | 346 | arketipe FORMULIR §3.9 di halaman penuh |
| `V2/Documents/Arsip/Catatan.tsx` | 296 | formulir + panel pratinjau PDF lengket |
| `V2/Documents/RincianInformasi.tsx` | 162 | kartu rincian, sepadan panel Distribusi `Show` |
| `V2/Documents/StaffStatus.tsx` | 151 | arketipe daftar berpenyaring |
| `V2/Documents/Unavailable.tsx` | 75 | satu keadaan kosong, tanpa kartu |

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.9 | `Arsip/Edit` & `Arsip/Catatan`: `Label` + kendali + `<p>` keterangan → `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`. Galat kini muncul DI BAWAH kendalinya, bukan cuma sebagai daftar di kepala kartu — dan daftar itu **tetap ada**, karena galat yang tak punya kendali di layar (`jenis`/`dept` yang `prohibited`) tak boleh hilang tanpa jejak |
| §3.9 | dua pilihan "Setelah ini" di `Arsip/Catatan` jadi `Field orientation="horizontal"` + `FieldContent`. `htmlFor` ↔ `id` tetap berpasangan, jadi menekan label tetap memilih radionya — bukan kasus checkbox `DialogRevisi` (§9) yang labelnya MEMBUNGKUS kendali |
| §3.7 | `StaffStatus`: penyaring ke `CardHeader className="border-b"`, tabel ke `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` — SATU kartu (arketipe `V2/Documents/Index.tsx`) |
| §3.6 | keterangan halaman, baris nomor·judul·lencana, dan tombol Kembali pindah ke prop `sub`/`aksi` `AppLayout` di keempat halaman yang punya |
| §3.2/§3.5 | pita `bg-accent rounded-lg` dan daftar galat `border-destructive/40 rounded-lg` → komponen `alert` resmi (`variant="destructive"` untuk galat). Nol warna baru, nol radius diketik ulang |
| §3.4 | ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons`; hiasan `size-12` di `Unavailable` → `size-8`, ukuran hiasan baku |
| — | `Unavailable` melepas `Card`-nya: halaman itu SELURUHNYA satu keadaan kosong, dan `Empty` sudah membawa tepi, jarak, dan lebar teks maksimalnya. `Card` di luarnya cuma menggandakan tepi |
| — | keadaan kosong `StaffStatus` memakai komponen `empty` resmi; kedua kalimatnya tetap dua, karena keduanya menjawab sebab yang berbeda |

Sembilan nama hugeicons baru diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
menggambar kosong tanpa galat apa pun.

**Yang sengaja TIDAK diubah:** `Arsip/Edit` tetap MENAMPILKAN jenis &
departemen sebagai kotak `disabled`, bukan menghapusnya. Keduanya menyusun
nomor dokumen sekaligus jalur berkasnya; yang membacanya perlu memastikan
dokumen yang sedang diperbaiki memang yang itu, dan `prohibited` di
`ArsipDocumentRequest` tetap penjaga sesungguhnya.

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat — `documents.staffStatus` tergambar sebagai `V2/Documents/StaffStatus` |
| `… --pratinjau --uri=/documents/1226/arsip/edit` | **29 halaman**, `oke V2/Documents/Arsip/Edit` |
| `… --pratinjau --uri=/distribusi/informasi/11` (DB `smartpro_shadcn_uji`) | **29 halaman**, `oke V2/Documents/RincianInformasi` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama` | lulus — nol `lucide-react` / `@/components/ui/` di lima berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — tujuh berkas `sama`, nol hilang |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9 dan §10).

### DUA halaman belum pernah digambar mesin — dan sebabnya DATA, bukan kode

`Unavailable` dan `Arsip/Catatan` tak bisa dicapai dengan isi basis data mana
pun yang ada sekarang, jadi keduanya lolos gerbang render tanpa pernah
dirender:

| Halaman | Syarat yang tak terpenuhi |
|---|---|
| `Unavailable` | butuh `document_types` yang `is_active = 0` **atau** jenis inti tanpa `schema_json.steps`. Keenam jenis aktif dan berskema di kedua basis data (`SOP IK SP JSA` berlangkah, `FK PX` unggahan yang memang dikecualikan `DocumentController::create`) |
| `Arsip/Catatan` | butuh dokumen `arsip` berjenis SOP/SP/IK (`pastikanBolehMencatat` → `jenisBerlembarRevisi()`). Satu-satunya dokumen arsip yang ada, id 1226, berjenis **FK** — dan FK memang tak pernah punya lembar revisi, jadi 404-nya BENAR |

Konsekuensinya sama persis dengan utang §9: kegagalan kelas "Radix di luar
Provider-nya → LAYAR PUTIH" pada kedua halaman ini baru bisa ketahuan saat
seseorang benar-benar membukanya di peramban. Yang sudah diperiksa mesin untuk
keduanya cuma tipe (`tsc`) dan penyusunan modul (`build`) — dan §14c menulis
persis kenapa itu tidak cukup.

Menutup utang ini butuh FIXTURE, dan fixture berarti menulis baris ke basis
data: satu jenis dokumen dinonaktifkan sementara, dan satu dokumen arsip
berjenis SOP. Keduanya **menunggu keputusan pemilik** (CLAUDE.md §4) — tak satu
baris pun ditulis ke basis data mana pun di fase ini.

### Yang gerbang TIDAK periksa di fase ini

Selain dua halaman di atas: gerbang render tetap membuka semuanya sebagai
`ADM-0001`. Pemilih departemen `RincianInformasi` yang hanya muncul bagi
pemegang `document.view_all`, dan tombol "Beri Masukan" `StaffStatus` yang
hanya muncul bagi Non-Staff sedepartemen, karena itu tak pernah dirender dalam
keadaan sebaliknya. Itu pekerjaan `docs/CEKLIS-MATA-PER-PERAN.md`.

---

## 12. Fase 3 — tinjau & persetujuan (2026-09-01)

### Berkas baru — enam, nol berkas lama disentuh

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.
`Review/Show` sudah punya kembaran sejak tranche 2, jadi seluruh alur tinjau
kini bergaya V2 dari antrean sampai keputusan.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Nonaktif/Index.tsx` | 294 | daftar + `StripAksi` + jendela formulir (`ui-maia/dialog`) |
| `V2/Review/Index.tsx` | 214 | DUA tabel di satu halaman, dua kartu |
| `V2/Approvals/Show.tsx` | 205 | 7/5 kolom: rantai dokumen + kartu keputusan |
| `V2/MasukanSejawat/Show.tsx` | 139 | kartu berulang, satu per pemberi masukan |
| `V2/Review/Md.tsx` | 120 | daftar + `Alert` batas peran |
| `V2/Approvals/Index.tsx` | 96 | arketipe daftar terpendek fase ini |

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | tiap tabel jadi SATU kartu: tabel di `CardContent className="px-0"`, paginasi di `CardFooter className="border-t pt-6"` (arketipe `V2/Documents/Revisions.tsx`). Keempat daftar fase ini memang tak punya penyaring, jadi tanpa `CardHeader` — kecuali "Status Revisi" di bawah |
| §3.7 | judul "Status Revisi" di `Review/Index` yang di V1 berdiri sebagai `<h2>` LEPAS di antara dua kartu kini jadi `CardHeader` + `CardTitle` + `CardDescription` kartunya sendiri. Judul yang melayang di atas kartu membaca seperti judul halaman kedua, bukan nama tabel |
| §3.6 | paragraf pengantar kelima halaman berparagraf pindah ke prop `sub`; tombol "Lihat PDF"/"Lihat Dokumen" ke prop `aksi`; tautan "Kembali ke antrian" `Approvals/Show` jadi REMAH pertama (pola `V2/Review/Show`) |
| §3.2/§3.5 | pita `bg-accent … rounded-lg` di `Review/Md`, `MasukanSejawat/Show`, dan `Nonaktif/Index` jadi komponen `alert` resmi; kotak per-bagian `MasukanSejawat` `rounded-lg` → `rounded-2xl`. Kartu `gap-0 py-0` + `px-4 py-3` per bagian dilepas — jaraknya diserahkan ke `ui-maia/card` (preseden `RincianInformasi`). Nol warna baru |
| §3.9 | `Label` + kendali + `<p className="text-destructive">` → `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`, dengan `aria-invalid` pada kendali dan `data-invalid` pada `Field` — kotak alasan `Approvals/Show` dan alasan penolakan `Nonaktif` |
| §3.9 | galat `doc_number` di `Approvals/Show` jadi `alert` `variant="destructive"` sendiri, bukan paragraf merah di bawah kotak alasan: ia satu-satunya galat di layar itu yang TAK punya kendali (nomor final dikunci server saat pengesahan), jadi menempelkannya di bawah kendali yang bukan sebabnya justru menyesatkan |
| §3.4 | tiap ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}` + `size-4`. Enam belas nama diverifikasi satu per satu ada di `node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah menggambar kosong tanpa galat apa pun |
| — | keadaan kosong keenam halaman memakai komponen `empty` resmi, bukan rakitan `div` + ikon |
| — | tombol "Batal" jendela Tolak `Nonaktif` jadi `variant="ghost"`, menyamai `JendelaFormulir` di `v2/DialogAksi` — satu-satunya jendela formulir V2 lain yang punya pasangan Batal/Kirim |

**Yang sengaja TIDAK diubah:** jendela Tolak `Nonaktif` tetap `ui-maia/dialog`
POLOS tanpa konfirmasi bertingkat, bukan `JendelaFormulir` milik `v2/DialogAksi`
yang mewajibkan `konfirmasi*`. Menolak MENGEMBALIKAN dokumen ke Berlaku — ia
tak melepas nomor, tak mematikan apa pun — dan `required` peramban sudah menahan
kiriman kosong. Menambah satu lapis konfirmasi di situ berarti menambah perilaku
yang tak ada di V1, bukan mengembarkannya (§3.8: dialog bukan-konfirmasi memakai
`ui-maia/dialog`).

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat — `review.index`, `review.md`, `approvals.index`, `nonaktif.index` tergambar sebagai `V2/*` |
| `… --pratinjau --uri=/approvals/1224 --uri=/dokumen/1224/masukan-sejawat` | **30 halaman**, `oke V2/Approvals/Show` + `oke V2/MasukanSejawat/Show` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama` | lulus — nol `lucide-react` / `@/components/ui/` di enam berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — sebelas berkas `sama`, nol hilang |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9, §10, dan §11).

**Keenam halaman benar-benar DIRENDER mesin**, jadi fase ini tak meninggalkan
utang gerbang seperti §9 dan §11: dua yang rutenya berparameter dijangkau lewat
`--uri` pada dokumen id **1224** (satu-satunya berstatus `pending_approval`),
dengan `MSYS_NO_PATHCONV=1` — tanpanya Git Bash mengubah `/approvals/1224` jadi
jalur Windows sebelum PHP melihatnya (§10).

### Deadlock MySQL saat menjalankan gerbang tes — sebabnya CARA MENJALANKAN, bukan kode

`php artisan test` sempat merah tiga kali berturut-turut dengan `DeadlockException`
dan `ModelNotFoundException` yang **berpindah-pindah tesnya** tiap jalan — ciri
khas dua proses yang mengakses satu basis data uji sekaligus, bukan regresi.

Sebabnya pipa shell: `php artisan test | grep … | head -20`. `head` keluar
sesudah baris ke-20 dan menutup pipanya, tapi proses `phpunit` anak TIDAK ikut
mati di Windows — ia terus berjalan di latar sambil memegang transaksi
`DatabaseTransactions`, lalu bertabrakan dengan jalan berikutnya. Dibuktikan
lewat `information_schema.innodb_trx`: satu transaksi `RUNNING` tertinggal pada
koneksi `Sleep` ke `smartpro_shadcn_uji`.

Sesudah proses sisa itu selesai dan `innodb_trx` kosong, satu jalan penuh tanpa
pipa (`php artisan test > berkas`) berbunyi **717 lulus + 1 dilewati, exit 0**.

Aturannya untuk fase berikutnya: **jangan pernah menyalurkan `php artisan test`
ke `head`** (atau apa pun yang keluar lebih dulu). Tulis ke berkas, baru saring.

### Yang gerbang TIDAK periksa di fase ini

Gerbang render membuka semuanya sebagai `ADM-0001`. Cabang yang hidup bagi peran
lain — tombol **Alihkan** di antrean tinjau (hanya GL SHE/Plant atas JSA lintas
departemen, `boleh_alih`), lencana **tahap terakhir** di Persetujuan Nonaktif
(hanya pemutus terakhir), dan tabel **Status Revisi** yang hanya terisi bagi
peninjau yang benar-benar pernah menolak — karena itu tak pernah dirender mesin
mana pun dalam keadaan sebaliknya. Itu pekerjaan `docs/CEKLIS-MATA-PER-PERAN.md`.

---

## 12. Fase 3 — tinjau & persetujuan, enam halaman (2026-09-01)

### Berkas baru — enam, nol berkas lain disentuh

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.
Fase ini menyentuh **enam TSX dan tidak lebih** — nol PHP, nol komponen bersama,
nol berkas V1.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Nonaktif/Index.tsx` | 294 | daftar + jendela formulir (`ui-maia/dialog`) + `ConfirmDialog` |
| `V2/Review/Index.tsx` | 214 | DUA tabel di satu halaman, `sort`/`dir` yang sama |
| `V2/Approvals/Show.tsx` | 205 | keputusan + alasan, arketipe formulir §3.9 |
| `V2/MasukanSejawat/Show.tsx` | 139 | halaman baca-saja per bagian dokumen |
| `V2/Review/Md.tsx` | 120 | antrean MD, daftar + `alert` batas peran |
| `V2/Approvals/Index.tsx` | 96 | arketipe daftar terpendek |

`V2/Review/Show.tsx` **sudah ada sejak T2** dan bukan bagian fase ini — satu
komponen itu melayani tiga rute (§14c, `TinjauInertiaTest`).

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | keempat daftar jadi SATU kartu: tabel di `CardContent className="px-0"`, paginasi di `CardFooter className="border-t pt-6"` (arketipe `V2/Documents/Revisions.tsx`). Judul "Status Revisi" yang di V1 berdiri sebagai `<h2>` lepas di antara dua kartu kini jadi `CardHeader` kartunya sendiri — judul yang melayang di atas kartu membaca seperti judul halaman kedua, bukan nama tabel |
| §3.6 | paragraf pengantar, judul dokumen, baris nomor·dept·status, tombol Lihat PDF, dan tautan "Kembali ke antrian" pindah ke prop `sub`/`aksi`/`remah` `AppLayout`. Jalan kembali `Approvals/Show` tak hilang: ia jadi remah pertama, pola yang sama dengan `V2/Review/Show` |
| §3.9 | alasan penolakan (`Nonaktif`) dan alasan keputusan (`Approvals/Show`): `Label` + kendali + `<p className="text-destructive">` → `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`, lengkap `aria-invalid` pada kendali + `data-invalid` pada `Field` |
| §3.2/§3.5 | pita `bg-accent … rounded-lg` di `Review/Md`, `Nonaktif`, dan `MasukanSejawat` → komponen `alert` resmi; kotak per-bagian `MasukanSejawat` `rounded-lg` → `rounded-2xl`. Kartu tak lagi `gap-0 py-0` dengan `px-4 py-3` per bagian — jaraknya diserahkan ke `ui-maia/card` (preseden `RincianInformasi`) |
| §3.8 | jendela "Tolak" `Nonaktif` dirender DI LUAR `StripAksi` dan DIKENDALIKAN (`buka` + `onUbahBuka`) — Radix melepas isi menunya begitu item dipilih, jadi jendela di dalamnya lenyap sebelum sempat terlihat |
| §3.4 | tiap ikon eksplisit `strokeWidth={1.5}`; hiasan keadaan kosong `size-6` di dalam `EmptyMedia variant="icon"` (preseden `Revisions`/`StaffStatus`), bukan `size-8` yang untuk halaman yang SELURUHNYA keadaan kosong |
| — | keadaan kosong kelima daftar memakai komponen `empty` resmi, bukan rakitan `div` + ikon |

Galat `doc_number` di `Approvals/Show` sengaja berdiri sendiri sebagai `alert`
`variant="destructive"`, bukan menempel di bawah kotak alasan: ia satu-satunya
galat di layar itu yang TAK punya kendali (nomor final dikunci server saat
pengesahan), jadi menempelkannya pada kendali yang bukan sebabnya menyesatkan.

**Yang sengaja TIDAK diubah:** jendela "Tolak" `Nonaktif` tak diberi konfirmasi
bertingkat. `required` peramban sudah menahan kiriman kosong, dan penolakan
mengembalikan dokumen ke Berlaku — bukan melepas nomor. Yang MELEPAS nomor
justru "Setujui" di tahap terakhir, dan itulah satu-satunya yang memakai
`destruktif` (`ConfirmDialog` bercabang atas `doc.tahapTerakhir`).

### Bukti kembaran, bukan tulis-ulang

Sebelum satu gerbang pun dijalankan, keenam pasang V1↔V2 disapu mekanis: nama
route, aksi (`router.*` / `useForm` / `preserveScroll` / `preserveState`), props
& gerbang izin yang dibaca, dan kunci `urut:` — **identik** di keenam pasang.

```bash
cd resources/js/pages
for f in Review/Index Review/Md Approvals/Index Approvals/Show \
         MasukanSejawat/Show Nonaktif/Index; do
  diff <(grep -o "route('[^']*'" $f.tsx | sort -u) \
       <(grep -o "route('[^']*'" V2/$f.tsx | sort -u)
  diff <(grep -o "urut: '[^']*'"  $f.tsx | sort -u) \
       <(grep -o "urut: '[^']*'"  V2/$f.tsx | sort -u)
done
```

Sapuan `urut:` itu penting justru karena kunci yang salah ketik lolos
TypeScript, lolos build, lolos gerbang render, dan hasilnya cuma daftar yang
diam-diam berhenti terurut.

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman — `review.index`, `review.md`, `approvals.index`, `nonaktif.index` tergambar sebagai `V2/*` |
| `… --pratinjau --uri=/approvals/1224 --uri=/dokumen/1226/masukan-sejawat` | **30 halaman**, `oke V2/Approvals/Show` + `oke V2/MasukanSejawat/Show` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest::test_pohon_v2_tak_mencampur_kit_lama` | lulus — nol `lucide-react` / `@/components/ui/` di enam berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — sebelas berkas `sama`, nol hilang |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9, §10, dan §11).

### Suite yang merah karena DUA `php artisan test` sekaligus — catat, supaya tak dikejar sebagai bug kode

`php artisan test` mula-mula merah tiga kali berturut-turut dengan jumlah
kegagalan yang BERBEDA-BEDA (20, lalu 7, lalu 17), semuanya
`DeadlockException` pada `insert into documents`. Totalnya selalu tetap 718, dan
tiap berkas yang gagal LULUS saat dijalankan sendiri.

Sebabnya bukan kode. `SHOW ENGINE INNODB STATUS` → `LATEST DETECTED DEADLOCK`
menunjukkan **dua thread MySQL** berebut kunci gap pada indeks
`documents_document_type_id_foreign` di `smartpro_shadcn_uji`: yang satu
`insert into documents`, yang lain `select … for update` milik penomoran. Satu
proses uji tak bisa menghasilkan dua transaksi bersamaan — dan daftar proses
Windows memang menemukan `php artisan test` KEDUA berjalan dari luar sesi ini.

```bash
# siapa yang sedang memegang basis data uji
php artisan tinker --execute='$s=DB::select("show engine innodb status")[0]->Status;
  echo substr($s, strpos($s,"LATEST DETECTED DEADLOCK"), 2000);'

# ada berapa proses uji yang berjalan
powershell -NoProfile -Command "Get-CimInstance Win32_Process -Filter \"Name='php.exe'\" |
  Where-Object { $_.CommandLine -like '*phpunit*' } | Select-Object ProcessId,CreationDate"
```

Dijalankan sendirian sesudah proses itu selesai: **717 lulus + 1 dilewati, nol
gagal**. Aturannya: `php artisan test` di proyek ini memakai satu basis data
MySQL bersama tanpa isolasi antar-proses, jadi **jangan pernah menjalankan dua
suite sekaligus** — hasilnya bukan sekadar lambat, melainkan merah palsu yang
berpindah-pindah berkas tiap kali dijalankan.

### Yang gerbang TIDAK periksa di fase ini

- **Persona.** Gerbang render tetap membuka semuanya sebagai `ADM-0001`. Baris
  yang hanya hidup bagi peran lain — antrean `Review/Md` milik MD, tahap
  `Nonaktif` milik SH/DH vs PJO, `Approvals` milik pemegang `approver_id` —
  tak pernah dirender dalam keadaan sebaliknya. Itu pekerjaan
  `docs/CEKLIS-MATA-PER-PERAN.md`.
- **`UrutTabelTest` belum menyapu kembaran V2.** Daftar halamannya menyebut
  jalur V1 (`Review/Index`, `Nonaktif/Index`, …), jadi `urut:` yang salah ketik
  di berkas `V2/` tak akan membuatnya merah. Di fase ini kesenjangan itu ditutup
  MANUAL lewat diff di atas; menutupnya permanen berarti menambahkan varian
  `V2/` ke larik `$halaman` (nol tes baru, jumlah tetap 718) — dan itu
  **menunggu keputusan pemilik**, karena ia menyunting berkas uji di fase yang
  seharusnya cuma menggambar.

---

## 13. Fase 4 — pengguna & akses, delapan halaman (2026-09-01)

### Berkas baru — delapan, dan SATU berkas uji diperbarui

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.
Fase ini menyentuh **delapan TSX baru + satu berkas uji** — nol PHP aplikasi,
nol komponen bersama, nol berkas V1.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Akses/Index.tsx` | 553 | DUA kartu + jendela profil dipakai ulang (baru & ubah) |
| `V2/Users/Index.tsx` | 474 | kartu MD di atas + daftar berpenyaring, `StripAksi` |
| `V2/Users/Create.tsx` | 252 | arketipe FORMULIR §3.9, grid dua kolom |
| `V2/Auth/Register.tsx` | 226 | formulir tamu tujuh isian, `max-w-md` |
| `V2/Account/Info.tsx` | 220 | 1/2 kolom: kartu foto + kartu detail, satu `<form>` |
| `V2/Users/Edit.tsx` | 182 | dua `Select` + `ConfirmDialog` wajib |
| `V2/Users/Pending.tsx` | 144 | daftar tanpa paginasi, dua tombol per baris |
| `V2/Auth/Pending.tsx` | 62 | layar tunggu tamu, satu `alert` bersyarat |

Sesudah fase ini **tersisa 11 halaman** tanpa kembaran — persis Fase 5 (4) +
Fase 6 (7), dihitung ulang dengan sapuan §6.

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | penyaring `Users/Index` & `Akses/Index` yang di V1 berdiri sebagai KARTU SENDIRI di atas kartu tabel kini masuk ke `CardHeader` kartu itu (arketipe `V2/Documents/Index.tsx`). Konsekuensinya tombol **Filter** dan **Bersihkan filter** hilang: `PenyaringDokumen` menyaring hidup dengan debounce 300 ms + `preserveState`/`preserveScroll`/`replace`, dan memunculkan tombol reset sendiri hanya bila ada isian yang bisa direset. Penyaringannya **tetap di server** lewat query string, jadi hasilnya masih bisa di-bookmark |
| §3.7 | tabel ke `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` di keempat daftar. `Users/Pending` tanpa `CardFooter` — daftarnya memang tak berhalaman |
| §3.7 | keempat sel aksi kehilangan `flex-wrap` (R4). Ketiga tombol baris `Users/Index` masuk `StripAksi`, dan kedua jendelanya dirender DI LUAR menu (Radix melepas isi menu begitu item dipilih). Yang TETAP berdiri di baris: dua tombol `Users/Pending` dan dua tombol profil `Akses` — dua aksi belum layak dikubur satu klik lebih dalam, dan Setujui/Tolak adalah satu-satunya alasan layar itu dibuka |
| §3.9 | seluruh formulir jadi `FieldGroup` > `Field` > `FieldLabel` + kendali + `FieldDescription` + `FieldError`. Yang BERTAMBAH dari V1: `aria-invalid` kini juga dipasang pada `SelectTrigger` (`Users/{Create,Edit}`, `Auth/Register`) — sebelumnya cuma `Field` yang menandainya, jadi kotak pilihannya sendiri tak pernah merah |
| §3.9 | kotak sandi akun MD + tombol Simpan jadi `InputGroup` + `InputGroupButton type="submit"`, bukan tombol yang disandingkan sendiri di samping `Input`. Tingginya pindah ke pembungkus, jadi nol angka diketik |
| §3.9 | kotak centang berlabel samping jadi `Field orientation="horizontal"`: jenis dokumen & "Boleh meninjau JSA" (`Akses`), "Hapus foto profil" (`Account/Info`). `htmlFor` ↔ `id` tetap berpasangan, jadi menekan labelnya tetap mencentang — bukan kasus `DialogRevisi` (§9) yang labelnya MEMBUNGKUS kendali |
| §3.6 | keterangan halaman `Users/Index` (jumlah akun terdaftar) pindah ke prop `sub`; tombol Buat Akun & Profil Baru ke prop `aksi`; remah `Users/{Create,Edit}` tetap di topbar. Keterangan yang menerangkan FORMULIR-nya (mis. "Akun yang dibuat admin langsung berstatus aktif.") sengaja TETAP di `CardHeader` — ia bukan keterangan halaman |
| §3.6 | judul & pengantar `Auth/{Register,Pending}` jadi RATA KIRI, bukan `items-center text-center`: `layouts/V2/AuthLayout` sejak R12 menurunkan logo tepat di atas blok ini dan merapatkannya ke kiri, jadi judul yang dipusatkan tak lagi segaris dengan mereknya sendiri (pola `V2/Auth/Login`) |
| §3.2 | `rounded-full` dilepas dari `<Avatar>` `Account/Info` — `v2/Avatar` sudah membawanya; yang tersisa cuma `size-28` |
| §3.4 | tiap ikon `@hugeicons/core-free-icons` + `strokeWidth={1.5}` + `size-4`/`size-3.5`. `strokeWidth={2}` dipakai pada tiga lencana `size-3` (Bantuan AI, Tinjau JSA ya/tidak). Ikon berkunci SERVER (`rupa[kode]` pada lencana jenis dokumen `Akses`) TETAP lewat `v2/Ikon`; ikon kendali diimpor langsung |
| — | keadaan kosong kelima tabel memakai komponen `empty` resmi, bukan rakitan `div` + ikon. Kalimat kedua yang menerangkan AKIBAT (mis. "Tanpa akun ini, dokumen SOP akan tertahan…") turun jadi `EmptyDescription`, tidak dibuang |
| — | tiga tombol simpan memakai `Spinner` alih-alih menukar teksnya jadi "Menyimpan…"/"Memproses…" — tombol yang berubah lebar saat ditekan menggeser tata letak tepat pada saat orang sedang menunggu (preseden `V2/Auth/Login`) |

Dua puluh nama hugeicons diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
tidak melempar galat tipe, ia cuma menggambar kosong.

**Yang sengaja TIDAK diubah:** kedua kotak sandi `Auth/Register` **tidak**
diberi tombol mata, meski `V2/Auth/Login` punya. Tombol itu lahir sebagai
permintaan R12 untuk layar masuk; memasangnya di sini berarti menambah kendali
yang tak ada di V1 — dan fase ini mengembarkan, bukan menambah (alasan yang
sama dengan jendela Tolak `Nonaktif` di §12).

### Satu tes DIPERBARUI, nol tes ditambah, nol tes dihapus

`PratinjauUiTest::test_halaman_tanpa_kembaran_jatuh_ke_komponen_asli` merah
begitu `V2/Account/Info.tsx` lahir — **persis seperti yang dijanjikan
docblock-nya sendiri**: "Kalau kelak `V2/Account/Info.tsx` lahir, tes ini akan
merah — dan itu benar: gantilah dengan halaman lain yang belum bersaudara,
jangan hapus janjinya."

Penggantinya `job-executions.index` → komponen `JobExecutions/Index`. Dipilih
karena dua hal: ia **terbuka bagi SEMUA peran aktif tanpa satu pun syarat izin**
(routes/web.php menuliskannya eksplisit), sehingga yang diuji tetap murni
mekanisme pemilihan komponen; dan ia menunggu di **Fase 6**, fase terjauh yang
tersisa — jadi ia tak perlu ditukar lagi di Fase 5. Jumlah tes **tetap 718**
(717 lulus + 1 dilewati).

### Gerbang — sembilan, semuanya hijau

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman — `users.index`, `users.create`, `users.pending`, `akses.index`, `account.info`, `register` tergambar sebagai `V2/*` |
| `… --pratinjau --uri=/users/2/edit` | **29 halaman**, `oke V2/Users/Edit` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest` | 9 lulus, termasuk `test_pohon_v2_tak_mencampur_kit_lama` — nol `lucide-react` / `@/components/ui/` di delapan berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | "Seluruh keluaran cetak identik dengan baseline" — sebelas berkas `sama`, nol hilang |
| `git diff components.json` | KOSONG |

### Bukti kembaran, bukan tulis-ulang

Sebelum satu gerbang pun dijalankan, kedelapan pasang V1↔V2 disapu mekanis —
nama route dan kunci `urut:` **identik di kedelapan pasang** (kedelapan halaman
ini memang nol `urut:`, karena tak satu pun daftarnya diurutkan dari kepala
tabel). Sapuannya sama persis dengan §12, hanya daftar berkasnya yang berganti:
`Users/Index`, `Users/Create`, `Users/Edit`, `Users/Pending`, `Akses/Index`,
`Account/Info`, `Auth/Register`, `Auth/Pending`.

### SATU halaman belum pernah digambar mesin — dan sebabnya DATA, bukan kode

`V2/Auth/Pending` tak bisa dicapai dengan isi basis data mana pun yang ada
sekarang, jadi ia lolos gerbang render tanpa pernah dirender:

| Halaman | Syarat yang tak terpenuhi |
|---|---|
| `Auth/Pending` | `UjiRender::penyapu()` menambahkan persona "belum aktif" hanya bila ada `User` berstatus `pending`/`rejected`. **Nol** di `smartpro_shadcn` maupun `smartpro_shadcn_uji` (diperiksa langsung). Persona ADM-0001 yang aktif dilempar keluar `/pending`, dan tamu dilempar ke `/login` |

Ini **bukan utang baru dari Fase 4**: `Auth/Pending` V1 pun tak pernah muncul
di sapuan mana pun karena sebab yang sama. Konsekuensinya tetap sama dengan
§11 — kegagalan kelas "Radix di luar Provider-nya → LAYAR PUTIH" pada halaman
ini baru bisa ketahuan saat seseorang benar-benar membukanya di peramban; yang
sudah diperiksa mesin cuma tipe (`tsc`) dan penyusunan modul (`build`).

Menutupnya butuh FIXTURE — satu baris `users` berstatus `pending` — dan itu
berarti menulis ke basis data. **Menunggu keputusan pemilik** (CLAUDE.md §4);
tak satu baris pun ditulis ke basis data mana pun di fase ini.

### Yang gerbang TIDAK periksa di fase ini

- **Persona.** Gerbang render membuka semuanya sebagai `ADM-0001`. Cabang yang
  hidup bagi peran lain — lencana "Akun Anda" pada baris diri sendiri, antrean
  `Users/Pending` milik GL/PJO yang lingkupnya departemen sendiri, dan
  `Akses/Index` yang memang hanya Admin — tak pernah dirender dalam keadaan
  sebaliknya. Itu pekerjaan `docs/CEKLIS-MATA-PER-PERAN.md`.
- **Jendela.** Ketiga jendela baru (`Konfigurasi` akun MD, `Profil Baru`,
  `Ubah Profil`) hanya lahir sesudah diklik, jadi gerbang render yang menggambar
  keadaan awal halaman tak pernah menyentuh isinya.
- **`UrutTabelTest` masih menyebut jalur V1** (§12). Di fase ini tak ada
  akibatnya — kedelapan halaman nol `urut:` — tapi kesenjangannya belum
  tertutup.

---

## 14. Fase 5 — Informasi, empat halaman (2026-09-01)

### Berkas baru — empat, nol berkas lain disentuh

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.
Fase ini menyentuh **empat TSX baru + berkas dokumen ini** — nol PHP, nol
komponen bersama, nol berkas uji.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Informasi/Index.tsx` | 468 | daftar berpenyaring + baris riwayat terlipat + jendela hapus bercabang |
| `V2/Informasi/Perbarui.tsx` | 117 | 2/1 kolom: formulir + kartu "Versi Sekarang" |
| `V2/Informasi/Nonaktif.tsx` | 68 | satu keadaan kosong, tanpa kartu |
| `V2/Informasi/Create.tsx` | 54 | pembungkus `v2/FormInformasi` |

Formulirnya sendiri **bukan berkas baru**: `v2/FormInformasi` sudah lahir di
Fase 1 justru untuk fase ini, dan kedua halaman formulir cuma menaruhnya.

Sesudah fase ini **tersisa 7 halaman** tanpa kembaran — seluruhnya Fase 6,
dihitung ulang dengan sapuan §6.

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | `Informasi/Index`: penyaring yang di V1 berdiri LEPAS di atas kartu tabel masuk ke `CardHeader className="border-b"` kartu itu, tabel ke `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` — SATU kartu (arketipe `V2/Documents/Index.tsx`). `CardFooter className="pt-0"` V1 ikut lepas: angkanya milik kit |
| §3.7 | Perbarui & Hapus turun ke `StripAksi`. Yang TETAP berdiri di baris cuma dua: penyingkap **Riwayat (N)** — ia penyingkap baris, bukan aksi atas dokumen (preseden `V2/Documents/Obsolete.tsx`) — dan **Buka**, satu-satunya aksi yang dipakai SEMUA peran termasuk yang tak mengelola. Empat tombol berjejer adalah persis keadaan yang melahirkan `StripAksi` |
| §3.8 | jendela Hapus karena itu jadi **dikendalikan** (`buka` + `onUbahBuka`) dan dirender DI LUAR menu: Radix melepas isi menunya begitu item dipilih, jadi jendela di dalamnya lenyap sebelum sempat terlihat. Isinya — dua pilihan cakupan, masing-masing berkonfirmasi sendiri — **tak berubah satu kalimat pun** |
| §3.6 | kalimat pengantar `Index` beserta ikon kategorinya, dan baris nomor · judul · lencana revisi `Perbarui`, pindah ke prop `sub` layout |
| §3.6 | keterangan "Untuk dokumen yang **belum ada** di daftar…" di `Create` justru TURUN ke `CardHeader` + `CardDescription`: ia menerangkan FORMULIR-nya (pintu mana yang benar), bukan halamannya, dan remah di topbar sudah menamai halamannya (preseden `V2/Users/Create.tsx`) |
| §3.4 | ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons` + `strokeWidth={1.5}` + `size-4`; ikon KATEGORI (`informasi_kategori.ikon`, kunci SERVER) tetap lewat `v2/Ikon` di `Index` maupun `Nonaktif`. `lucide:FileX2` yang tak berpadanan jadi `FileMinusIcon` — "buang satu versi", bukan "batalkan berkas" |
| — | `Nonaktif` melepas `Card`-nya: halaman itu SELURUHNYA satu keadaan kosong, dan `Empty` sudah membawa tepi, jarak, dan lebar teks maksimalnya (preseden `V2/Documents/Unavailable.tsx`). Hiasan `size-12` → `size-8`. Kedua paragrafnya tetap DUA — yang pertama menerangkan keadaan, yang kedua menjawab pertanyaan yang langsung menyusul ("dokumen saya bagaimana?") |
| — | keadaan kosong `Index` memakai komponen `empty` resmi, dan jalan keluarnya jadi tombol sungguhan alih-alih tautan bergaris bawah di tengah kalimat |
| — | `max-w-3xl` pada kartu `Create` dan `CardContent className="grid gap-5"` pada `Perbarui` dilepas: lebar & jarak diserahkan ke `ui-maia/card` + `FieldGroup`, sepadan seluruh kartu V2 lain |

Sepuluh nama hugeicons diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
tidak melempar galat tipe, ia cuma menggambar kosong.

**Yang sengaja TIDAK diubah:** DUA penyingkap riwayat tetap dua — chevron di
kolom pertama DAN tombol "Riwayat (N)" di kolom Aksi, berbagi satu keadaan.
Membuang salah satunya memang menyederhanakan barisnya, tapi itu membuang
perilaku V1 di fase yang mengembarkan, bukan menyunting (alasan yang sama
dengan tombol mata `Auth/Register` di §13).

### Bukti kembaran, bukan tulis-ulang

Sebelum satu gerbang pun dijalankan, keempat pasang V1↔V2 disapu mekanis —
nama route dan kunci `urut:` **identik di keempat pasang** (keempat halaman ini
nol `urut:`; daftar Informasi memang tak diurutkan dari kepala tabel). Sapuannya
sama persis dengan §12, hanya daftar berkasnya yang berganti: `Informasi/Index`,
`Informasi/Create`, `Informasi/Perbarui`, `Informasi/Nonaktif`.

### Gerbang — sembilan dijalankan, delapan hijau + satu tertangkap DATA

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman — `informasi.index` & `informasi.create` tergambar sebagai `V2/Informasi/*` |
| `DB_DATABASE=smartpro_shadcn_uji … --pratinjau --uri=/informasi/16/perbarui` | **29 halaman**, `oke V2/Informasi/Perbarui` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest` | 9 lulus, termasuk `test_pohon_v2_tak_mencampur_kit_lama` — nol `lucide-react` / `@/components/ui/` di empat berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | 9 `sama`, **2 BEDA** — DATA, bukan kode; lihat di bawah |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9–§13).

### Utang gerbang Fase 1 untuk `FormInformasi` LUNAS

§9 mencatat kelima komponen Fase 1 lahir tanpa pengimpor, jadi tak satu gerbang
pun pernah menggambarnya; §10 melunasi tiga di antaranya, §13 melunasi
`LencanaStatusAkun`. Yang tersisa `FormInformasi`, dan fase ini menggambar
**kedua cabangnya**: `informasi.create` merender cabang `induk === null`
(tombol Unggah, tanpa konfirmasi), `--uri=/informasi/16/perbarui` merender
cabang `induk` terisi (tombol "Perbarui & Berlakukan" + `ConfirmDialog`
terkendali). Seluruh berkas Fase 1 kini sudah benar-benar dirender mesin.

### Dua PDF BEDA — sebabnya DATA, dan buktinya empat

`smartpro:cetak-baseline` menandai `SP_…_id1225` dan `SOP_…_id1240` berbeda dari
baseline. Itu **bukan** regresi mesin cetak:

1. md5 mesin cetak **23/23 OK** — tak satu byte pun dari 23 berkas §14b berubah;
2. Fase 5 **nol menyentuh PHP** — empat TSX baru dan berkas dokumen ini saja;
3. sembilan berkas lain berbunyi `sama`, termasuk yang terbesar (JSA 507 KB);
4. `audit_logs` mencatat `document.review_approve` oleh **user 11** pada kedua
   dokumen itu **hari ini pukul 13:26 WITA**, di tengah sesi ini — orang
   sungguhan memakai peramban. Persetujuan peninjau memang mengubah blok
   pengesahan yang TERCETAK, jadi PDF-nya wajib berubah.

```bash
php artisan tinker --execute='echo json_encode(DB::table("audit_logs")
  ->whereIn("document_id",[1225,1240])->orderByDesc("id")->limit(4)
  ->select("document_id","action","user_id","created_at")->get());'
```

Menangkap ulang baseline kedua berkas ini (`--tulis --id=1225 --id=1240`)
**menunggu keputusan pemilik** — berbeda dengan §9 yang murni penambahan berkas
baru, di sini ada baseline lama yang akan DITIMPA.

### SATU halaman belum pernah digambar mesin — dan sebabnya DATA juga

| Halaman | Syarat yang tak terpenuhi |
|---|---|
| `Informasi/Nonaktif` | butuh `informasi_kategori` yang `is_active = 0`. **Nol** di `smartpro_shadcn` maupun `smartpro_shadcn_uji` — kesepuluh kategori aktif, jadi `InformasiController::index` tak pernah sampai ke cabang itu |

Kelasnya sama persis dengan `Unavailable`/`Arsip/Catatan` (§11) dan
`Auth/Pending` (§13): kegagalan "Radix di luar Provider-nya → LAYAR PUTIH" baru
bisa ketahuan saat seseorang benar-benar membukanya di peramban. Yang sudah
diperiksa mesin cuma tipe (`tsc`) dan penyusunan modul (`build`). Menutupnya
butuh FIXTURE — satu kategori dinonaktifkan sementara — dan itu berarti menulis
ke basis data; **menunggu keputusan pemilik** (CLAUDE.md §4). Tak satu baris pun
ditulis ke basis data mana pun di fase ini.

### Yang gerbang TIDAK periksa di fase ini

- **Persona.** Gerbang render membuka semuanya sebagai `ADM-0001`, yang
  `kelola`-nya selalu benar. Baris tanpa hak kelola — Non-Staff & GL yang cuma
  melihat daftar dan tombol Buka — tak pernah dirender. Itu pekerjaan
  `docs/CEKLIS-MATA-PER-PERAN.md`.
- **Jendela & baris riwayat.** Menu aksi, jendela Hapus beserta dua cabang
  cakupannya, dan baris riwayat yang terlipat semuanya lahir sesudah diklik,
  jadi gerbang render yang menggambar keadaan awal tak menyentuh isinya.
- **Kategori selain `kebijakan`.** Kolom tabel & isian formulir mengikuti
  `informasi_kategori.kolom_json`, dan sapuan bernama hanya membuka kategori
  pertama. Kategori tanpa `edisi`/`tanggal_efektif` karena itu tak pernah
  dirender mesin — cabangnya ada di data, bukan di kode.
- **`UrutTabelTest` masih menyebut jalur V1** (§12). Di fase ini tak ada
  akibatnya — keempat halaman nol `urut:` — tapi kesenjangannya belum tertutup.

---

## 15. Fase 6 — Pengaturan & log, tujuh halaman (2026-09-01)

### Berkas baru — tujuh, dan SATU berkas uji diperbarui

Berkas V1-nya **tak satu pun disentuh**: ia masih jadi pembanding sampai Fase 7.
Fase ini menyentuh **tujuh TSX baru + satu berkas uji** — nol PHP aplikasi, nol
komponen bersama, nol berkas V1.

| Berkas | Baris | Bentuknya |
|---|---|---|
| `V2/Pengaturan/Sistem.tsx` | 668 | enam kartu di dua kolom + Zona Berbahaya berjendela |
| `V2/Pengaturan/Master.tsx` | 553 | tiga kartu, tiap BARIS tabel satu formulir sendiri |
| `V2/Pengaturan/Penomoran.tsx` | 253 | 7/5 kolom: formulir + kartu penjelas, pratinjau langsung |
| `V2/Log/Masukan.tsx` | 328 | daftar + jendela Balas + `v2/DialogAksi` per DOKUMEN |
| `V2/JobExecutions/Index.tsx` | 196 | daftar berpenyaring + bar progres per baris |
| `V2/Log/Pesan.tsx` | 180 | daftar berpenyaring, baca saja |
| `V2/Audit/Index.tsx` | 116 | arketipe daftar berpenyaring terpendek |

Sesudah fase ini **nol halaman tanpa kembaran** — sapuan §6 mengembalikan
daftar kosong, dan `grep "render("` di seluruh `app/Http/Controllers/` tidak
menyisakan satu pun komponen tanpa berkas `resources/js/pages/V2/`.

### Penyimpangan dari berkas asli — semuanya patokan §3, dan tertulis di komentar tiap berkas

| Patokan | Yang berubah |
|---|---|
| §3.7 | penyaring `Audit`, `JobExecutions`, `Log/Pesan`, dan `Log/Masukan` yang di V1 berdiri LEPAS di atas kartu tabel masuk ke `CardHeader className="border-b"` kartu itu; tabel ke `CardContent className="px-0"`, paginasi ke `CardFooter className="border-t pt-6"` — SATU kartu (arketipe `V2/Documents/Index.tsx`). `CardFooter pt-0` V1 ikut lepas: angkanya milik kit |
| §3.7 | tabel `Master` (tiga) dan tabel Kesehatan (`Sistem`) juga turun ke `CardContent className="px-0"`, pembungkusnya tinggal `overflow-x-auto`. `rounded-lg border` V1 dilepas: kartunya sudah membawa tepi, dan tepi kedua di dalamnya cuma menggandakan garis |
| §3.9 | `Penomoran` & kartu AI `Sistem`: isian dibungkus `FieldGroup`, jadi jarak antar-kelompok datang dari kit alih-alih dari `grid gap-5` yang diketik (arketipe `V2/Users/Create.tsx`). Yang BERTAMBAH dari V1: `aria-invalid` kini juga dipasang pada `SelectTrigger` penyedia utama & cadangan — sebelumnya cuma `Field` yang menandainya, jadi kotak pilihannya sendiri tak pernah merah |
| §3.9 | kotak balasan `Log/Masukan`: `Label` + `Textarea` + paragraf merah → `FieldGroup` > `Field` > `FieldLabel` + `Textarea` + `FieldError`; kotak centang "Hapus kunci yang tersimpan" (`Sistem`) jadi `Field orientation="horizontal"`; kedua isian Zona Berbahaya masuk satu `FieldGroup` |
| §3.9 | pratinjau ikon kategori (`Master`) yang di V1 dirakit sendiri — `span` ber-`size-9 rounded-md border bg-muted` di samping `Input` — jadi `InputGroup` + `InputGroupAddon` + `InputGroupInput`. Nol angka geometri diketik: tingginya pindah ke pembungkus, persis seperti tombol mata halaman login (§3.3) |
| §3.9 | paragraf galat merah per sel `Master` → `FieldError` berdiri sendiri (tanpa `Field` induk): label kolomnya sudah di kepala tabel dan tiap kendali membawa `aria-label`, jadi yang dibutuhkan cuma pengumumnya (`role="alert"`) dengan warna & ukuran dari kit |
| §3.6 | kalimat pengantar keempat daftar pindah ke prop `sub` layout |
| §3.2 | kotak pratinjau nomor (`Penomoran`) dan kotak kutipan masukan di jendela Balas (`Log/Masukan`) `rounded-lg` → `rounded-2xl` — keduanya kotak buatan sendiri yang berperan sebagai kartu |
| §3.5 | ikon peringatan kartu penjelas `Penomoran` `text-amber-500` → `text-chart-3`, jingga palet `.ui-v2` (preseden `V2/Documents/Unavailable.tsx`). Nol warna baru |
| §3.4 | ikon KENDALI diimpor langsung dari `@hugeicons/core-free-icons` + `strokeWidth={1.5}` + `size-4`; ikon berkunci SERVER tetap lewat `v2/Ikon` — `rupa[j.code]` pada lencana jenis dokumen & `informasi_kategori.ikon` pada pratinjau kategori (`Master`), dan `tautan.ikon` pada tombol tiap baris (`Log/Pesan`). `strokeWidth={2}` pada gembok `size-3` "terkunci oleh N dokumen" |
| — | keadaan kosong keempat daftar memakai komponen `empty` resmi, bukan string telanjang (`Audit`) maupun rakitan `div` + ikon (tiga lainnya) |
| — | enam tombol yang menunggu jawaban server memakai `Spinner` alih-alih menukar teksnya jadi "Menyimpan…"/"Menghubungi…"/"Mengirim…" — tombol yang berubah lebar saat ditekan menggeser tata letak tepat pada saat orang sedang menunggu (preseden `V2/Auth/Login`) |

Dua belas nama hugeicons baru diverifikasi satu per satu ada di
`node_modules/@hugeicons/core-free-icons/dist/esm/index.js` — nama yang salah
tidak melempar galat tipe, ia cuma menggambar kosong.

**Yang sengaja TIDAK diubah, dan alasannya:**

- **Bentuk formulir per baris `Master`** bertahan utuh: satu `<form>` KOSONG per
  baris, ditautkan ke kotak isiannya lewat atribut `form="…"`, dengan `errorBag`
  bernama sama dengan id form-nya. HTML melarang `<form>` membungkus `<tr>`, dan
  bundel SSR menghasilkan markup yang tetap dibaca parser HTML peramban — yang
  akan memindahkan form itu keluar tabel diam-diam sehingga tak satu pun input
  ikut terkirim. Ketiga tabelnya karena itu TIDAK lewat `v2/DataTable`: barisnya
  formulir yang bisa disimpan, bukan data yang diurutkan dari kepala tabel.
- **Kotak centang "Kolom yang dipakai" (`Master`)** tetap `<label>` yang
  MEMBUNGKUS `Checkbox`-nya, bukan `Field orientation="horizontal"`. `Field`
  membawa `w-full` di kelas dasarnya, sehingga tiga centang yang harus berjajar
  dalam satu `flex-wrap` akan pecah jadi tiga baris penuh — itu mengubah tata
  letak sel, bukan mengembarkannya (alasan yang sama dengan baris centang
  `DialogRevisi`, §9).
- **Pita balasan `Log/Masukan`** tetap `bg-muted rounded-r border-l-4`, TIDAK
  ikut naik ke `rounded-2xl`: ia pita kutipan, bukan kartu, dan bentuknya
  disamakan dengan `v2/DaftarMasukan` supaya balasan tampak sama di mana pun ia
  muncul.
- **Kolom Aksi `JobExecutions` (satu tombol) dan `Log/Masukan` (dua tombol)**
  tetap berdiri mendatar, bukan `StripAksi`. Satu-dua aksi yang dikubur di balik
  menu cuma menambah satu klik, dan di `Log/Masukan` keduanya justru alasan
  layar itu dibuka (preseden `V2/Users/Pending`).
- **Tiga hal yang tak ada di `Sistem`** tetap tak ada: kotak isian perintah
  artisan (itu eksekusi kode jarak jauh dengan nama lain), kotak alamat tujuan
  email uji (tujuannya SELALU alamat Admin yang sedang login), dan kunci API
  dalam bentuk terbaca (ia bahkan tak sampai ke props — `Arr::except` di
  controller).

### Satu tes DIPERBARUI, nol tes ditambah, nol tes dihapus

`PratinjauUiTest::test_halaman_tanpa_kembaran_jatuh_ke_komponen_asli` merah
begitu `V2/JobExecutions/Index.tsx` lahir — persis seperti yang dijanjikan
docblock-nya sendiri, dan persis seperti yang sudah terjadi sekali di Fase 4
(`account.info` → `job-executions.index`).

Kali ini **tak ada halaman pengganti**: Fase 6 menghabiskan daftarnya. Bukan
karena janjinya dilepas, melainkan karena pekerjaannya selesai.

Keputusan pemilik 2026-09-01: **tunjuk MEKANISMENYA, bukan halaman**. Tesnya
kini memanggil `PratinjauResponseFactory` langsung dengan satu nama komponen
yang memang tak akan pernah punya kembaran, dan membaca nama komponen dari
balasan X-Inertia — di situlah ia benar-benar tertulis; balasan HTML biasa cuma
menyematkannya di atribut `data-page`. Yang diuji tetap syarat (b) factory,
"kembaran TSX-nya benar-benar ada di disk", dan syarat itu wajib hidup sampai
Fase 7 membuang sakelarnya: kalau `is_file()` diganti prasangka "pasti sudah ada
semua", halaman yang kembarannya belum lahir langsung jadi LAYAR PUTIH.

Arah sebaliknya tidak ikut hilang — `test_bendera_nyala_menggambar_kembaran_v2`
sudah membuktikan awalan `V2/` benar-benar dipasang, jadi tes kedua di tingkat
factory hanya akan menggandakan yang sudah dijaga.

Tesnya dibuktikan **MERAH** dengan mencabut sementara penjaga `is_file()` di
`PratinjauResponseFactory::render()` lalu memulihkannya — jadi ia benar-benar
menggigit, bukan tes yang lolos dua-duanya. Jumlah tes **tetap 718** (717 lulus
+ 1 dilewati).

### Bukti kembaran, bukan tulis-ulang

Sebelum satu gerbang pun dijalankan, ketujuh pasang V1↔V2 disapu mekanis — nama
route, kunci `urut:`, DAN nama `errorBag` **identik di ketujuh pasang**.
`errorBag` ikut disapu khusus untuk fase ini: `Master` mengirim sembilan
formulir dari satu halaman, dan satu nama kantong yang salah ketik tidak
membuat apa pun merah — ia cuma membuat galat validasi satu baris mewarnai
SELURUH baris di tabelnya.

```bash
cd resources/js/pages
for f in Audit/Index JobExecutions/Index Log/Masukan Log/Pesan \
         Pengaturan/Master Pengaturan/Penomoran Pengaturan/Sistem; do
  diff <(grep -o "route(.[^']*'"     $f.tsx | sort -u) \
       <(grep -o "route(.[^']*'"  V2/$f.tsx | sort -u)
  diff <(grep -o "urut: .[^']*'"     $f.tsx | sort -u) \
       <(grep -o "urut: .[^']*'"  V2/$f.tsx | sort -u)
  diff <(grep -o "errorBag: [A-Za-z]*"    $f.tsx | sort -u) \
       <(grep -o "errorBag: [A-Za-z]*" V2/$f.tsx | sort -u)
done
```

### Gerbang — sembilan dijalankan, delapan hijau + satu tertangkap DATA

| Gerbang | Hasil |
|---|---|
| `node_modules/.bin/tsc --noEmit` | nol galat |
| `npm run build` · `npm run uji-render` | bersih |
| `php artisan smartpro:uji-render` | 28 halaman, nol galat |
| `php artisan smartpro:uji-render --pratinjau` | 28 halaman, nol galat — **ketujuhnya** tergambar sebagai `V2/*` |
| `php artisan test` | **718** (717 lulus + 1 dilewati) — patokan §14c terjaga persis, nol tes baru |
| `PratinjauUiTest` | 9 lulus, termasuk `test_pohon_v2_tak_mencampur_kit_lama` — nol `lucide-react` / `@/components/ui/` di tujuh berkas baru |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | **23/23 OK** |
| `php artisan smartpro:cetak-baseline` | 8 `sama`, **3 BEDA** — DATA, bukan kode; lihat di bawah |
| `git diff components.json` | KOSONG |

**Nol tes baru, dan itu disengaja** — fase ini menggambar, ia tak menambah satu
pun keputusan server (alasan yang sama dengan §9–§14).

**NOL utang gerbang render.** Berbeda dengan §9, §11, §13, dan §14, ketujuh
halaman fase ini punya route bernama tanpa parameter dan terbuka bagi ADM-0001,
jadi sapuan bernama merendernya semua. Tak ada halaman yang cuma diperiksa
`tsc` + `build`.

### Tiga PDF BEDA — sebabnya DATA, dan buktinya empat

`smartpro:cetak-baseline` menandai `IK_…_id1224`, `SP_…_id1225`, dan
`SOP_…_id1240` berbeda dari baseline. Dua yang terakhir sudah tercatat di §14;
`id1224` baru bergabung. Ketiganya **bukan** regresi mesin cetak:

1. md5 mesin cetak **23/23 OK** — tak satu byte pun dari 23 berkas §14b berubah;
2. Fase 6 **nol menyentuh PHP aplikasi** — tujuh TSX baru, satu berkas uji, dan
   berkas dokumen ini saja;
3. delapan berkas lain berbunyi `sama`, termasuk yang terbesar (JSA 507 KB);
4. `audit_logs` mencatat `document.approve` oleh **user 15** pada `id1224` dan
   `id1225` **hari ini pukul 14:17 WITA**, di tengah sesi ini, plus
   `document.review_approve` oleh user 11 pada `id1240` pukul 13:26 — orang
   sungguhan memakai peramban. Pengesahan memang mengubah blok TTD yang
   TERCETAK (saat Berlaku SEMUA TTD bercap APPROVED, CLAUDE.md §7), jadi
   PDF-nya wajib berubah.

```bash
php artisan tinker --execute='echo json_encode(DB::table("audit_logs")
  ->whereIn("document_id",[1224,1225,1240])->orderByDesc("id")->limit(8)
  ->select("document_id","action","user_id","created_at")->get());'
```

Keputusan pemilik 2026-09-01: **biarkan**, laporkan apa adanya. Menangkap ulang
ketiganya (`--tulis --id=1224 --id=1225 --id=1240`) berarti MENIMPA baseline
lama — berbeda dengan §9 yang murni penambahan berkas baru — jadi ia tetap
menunggu keputusan tersendiri.

### Yang gerbang TIDAK periksa di fase ini

- **Persona.** Gerbang render membuka semuanya sebagai `ADM-0001`, dan lima dari
  tujuh halaman ini memang hanya Admin. Yang punya cabang peran lain cuma dua:
  tombol Hapus `JobExecutions` (`bolehHapus`) dan pasangan Balas/Revisi
  `Log/Masukan` (`boleh_balas`/`boleh_revisi` per baris) — keduanya tak pernah
  dirender dalam keadaan MATI. Itu pekerjaan `docs/CEKLIS-MATA-PER-PERAN.md`.
- **Jendela.** Jendela Balas (`Log/Masukan`), jendela Revisi yang dipinjam dari
  `v2/DialogAksi`, dan jendela Zona Berbahaya (`Sistem`) baru lahir sesudah
  diklik, jadi gerbang render yang menggambar keadaan awal tak menyentuh
  isinya.
- **Cabang `Sistem` yang bergantung setelan server.** Lencana "Mode debug
  menyala", peringatan `mailer === 'log'`, dan peringatan `MAIL_PAKSA_KE` cuma
  muncul pada konfigurasi tertentu; yang dirender mesin hanya konfigurasi
  laptop ini. Begitu pula kotak centang "Hapus kunci yang tersimpan", yang
  hanya ada bila `keyTopeng` terisi.
- **`UrutTabelTest` masih menyebut jalur V1** (§12). Di fase ini tak ada
  akibatnya — ketujuh halaman nol `urut:` — tapi kesenjangannya belum tertutup.
