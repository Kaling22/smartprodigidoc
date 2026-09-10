# TEMUAN-F8 — 12 temuan pemeriksaan mata + 4 mandat dasbor

## Context

`docs/planpreproduction.md` (14 fase) dan `docs/DASBOR-V4-RENCANA.md` (F0–F7) sudah
SELESAI dan ter-commit (`9f597ce`). Pemeriksaan mata pemilik atas hasilnya
(DASBOR-V4 F8 — gerbang otomatis memang tak pernah memeriksa tata letak)
menghasilkan **12 temuan** + **4 mandat dasbor** yang wajib mengacu ke
`docs/dasbor_ref/`.

Semua temuan sudah ditelusuri ke akar penyebabnya — bukan dugaan. Tiga di
antaranya ternyata **satu bug yang sama di satu berkas**, dan satu lagi
("AI tidak memberi hasil") ternyata **bukan** soal konfigurasi sama sekali.

### Jawaban atas pertanyaan pemilik: "untuk menyamakan desain kamu perlu apa?"

| Yang dibutuhkan | Kenapa | Status |
|---|---|---|
| **Foto referensi** | memberi proporsi, hierarki, urutan elemen | ✅ sudah ada, `docs/dasbor_ref/` |
| **Tangkapan layar HASILKU di lebar layar yang sama** | ini yang paling kurang. Aku menulis TSX tanpa pernah melihat halamannya tergambar — `tsc`/`build`/`uji-render` tak satu pun merender rupa. `Screenshot (112).png` adalah bahan paling berguna yang pernah kamu kirim | ⚠️ minta 1 per fase |
| **Kode blok referensi** | shadcn RESMI bisa kutarik sendiri (`curl https://ui.shadcn.com/r/styles/new-york/<blok>.json` — sudah terbukti jalan, `chart-bar-stacked` & `chart-pie-donut-active` sudah kuambil). Shadcn **UI Kit** (sumber ketujuh gambarmu) adalah template berbayar — kalau kamu punya `.tsx`-nya, itu membuat jarak & angkanya PERSIS, bukan hasil ukur mata | opsional |

Satu batas yang harus disadari sejak awal: kit kita **maia** berbentuk pil
(`rounded-4xl`, PATOKAN-GAYA-V2 §3.2), referensi berbentuk kotak tumpul
(`rounded-lg`). Radius **tidak** disamakan — mengubahnya mengecat ulang 41
halaman. Yang disamakan: struktur, jarak, tipografi, warna, isi badge.

### Keputusan pemilik sesi ini

| # | Keputusan |
|---|---|
| **W-1** | **Sparkline dibuang dari 4 kartu KPI.** Alasan: panjang/lebar/desain kartu wajib sama dengan `card.png`, dan sparkline-lah yang membuatnya ~2× lebih tinggi. (Sparkline = garis tren 8 bulan yang dipasang DASBOR-V2 §5c; "8 bulan" berasal dari `DashboardController::alirAudit():779` yang menyapu `now()->startOfMonth()->subMonths(7)` = 8 ember bulanan. Prop `deret` tetap dikirim server — cuma tak digambar.) |
| **W-2** | Warna Sebaran per Departemen = **ramp satu hue merah PPA**, tua→muda. |
| **W-3** | Batas unggah gambar **2MB → 5MB**, berlaku flowchart DAN foto Quill (satu rute). |
| **W-4** | Urutan: **dasbor dulu** (F1–F4), bug fungsi menyusul (F5–F8). |
| **W-5** | Penomoran: Edisi 1 = revisi 0–5; sesudahnya **Edisi 2 Revisi 1**; edisi ≥2 mulai dari revisi 1. |
| **W-6** | Review F1/F2: judul widget **tak pernah didahului ikon** — ikon (bila ada) di kanan lewat `CardAction`, persis baris kedua `card.png` ("Total Employees" polos, menu titik-tiga di kanan). Judul konsisten `text-sm font-semibold`, warnanya mewarisi `text-card-foreground` (beda dari `CardDescription` yang `text-muted-foreground`) — karakteristik judul lepas dari isi kartu. Dikunci di sini supaya widget BARU ikut pola yang sama, bukan menebak ulang. |

---

## Aturan yang berlaku di SETIAP fase

Sama dengan dua rencana sebelumnya — **satu fase → berhenti → review + commit**
(CLAUDE.md §5), dan sembilan gerbang dari PowerShell:

```powershell
node_modules/.bin/tsc --noEmit
npm run build
npm run uji-render
php artisan smartpro:uji-render
php artisan smartpro:uji-render --pratinjau
php artisan test                                 # patokan 753, tak boleh berkurang
md5sum -c C:/baseline-smartpro/mesin-cetak.md5   # 23/23
php artisan smartpro:cetak-baseline              # seluruh PDF "sama"
git diff components.json                         # WAJIB KOSONG
```

Plus: nol berkas `components/ui/**` & `components/ui-maia/**` disunting · pohon V2
tak mengimpor `lucide-react`/`@/components/ui/` · nol aturan peran/warna status di
TSX · tes diperbarui, tidak dihapus.

> **Kembaran V1.** Tiap berkas V2 di bawah punya kembaran non-V2 yang komentar
> kepalanya mewajibkan perbaikan berpasangan sampai Tranche 4. Aturan itu berlaku
> untuk **perbaikan perilaku** (F5–F8), **tidak** untuk pekerjaan menggambar
> (F1–F4) — pohon V1 sengaja dibekukan sejak K-A dan bukan jalur pengguna.
> Berkas bersama yang bukan V1/V2 (`resources/js/lib/unggah.ts`) menyembuhkan
> keduanya sekaligus.

---

# BAGIAN A — DASBOR (F1–F4)

## F1 — Lencana jujur + kartu KPI gaya `card.png`

Menutup temuan **1a**, **1c**, **12**, mandat dasbor **#1** dan **#2**.

### F1a — Buang lencana "Baru" (akar penyebab, satu baris)

`app/Services/DasborTampilan.php:543-556` — `delta()`:

```php
if ($lalu === 0) {
    return [100.0, 'Baru'];      // SEKARANG
    return [100.0, '+100,0%'];   // JADI
}
```

Ini satu-satunya sumber teks "Baru" di seluruh aplikasi — `LencanaDelta.tsx`
hanya mencetak `deltaLabel` apa adanya. Memperbaikinya di sini menyembuhkan
**keempat kartu KPI, baris sorotan Performa PIC, keempat angka Lacak Status, dan
kepala Sebaran per Departemen** sekaligus. Docblock `:536` ikut diperbarui.

Kasus `$kini === 0 && $lalu === 0` → tetap `[null, null]` → **lencana tak
digambar sama sekali**. Itu yang menjawab "badge selalu Baru padahal angkanya 0":
angka 0 memang tak boleh berlencana, dan yang selama ini berlencana "Baru" adalah
kartu ber-angka > 0 yang periode sebelumnya kebetulan kosong.

**Tes:** `tests/Feature/DasborV2Test.php:889` **diperbarui** (bukan dihapus) —
asersinya kini `'+100,0%'`, dan kalimat pesannya dibalik.

### F1b — `LencanaDelta` varian polos

`card.png` menampilkan badge sebagai **teks berwarna + panah**, tanpa pil;
`sebaran_perdepartemen.png` justru memakai pil hijau. Keduanya dibutuhkan.

`components/v2/dasbor/LencanaDelta.tsx` — tambah prop opsional `polos?: boolean`.
`polos` → `<span>` dengan warna & panah yang sama, tanpa `Badge`. Nol perubahan
pada pemanggil lama (default tetap pil).

### F1c — `KartuStatistik.tsx` ditata ulang mengikuti `card.png`

```
┌──────────────────────────────────┐  Card [--card-spacing:--spacing(4)] gap-0
│ ▢  Total Dokumen                 │  CardHeader border-b bg-muted/40
├──────────────────────────────────┤    ikon dalam kotak size-7 rounded-lg border
│                                  │    label text-sm font-medium
│  1.198                           │  CardTitle text-4xl font-semibold tabular-nums
│  ↗ +2,8%  vs 30 hari sebelumnya  │  LencanaDelta polos + span muted, text-xs
│                                  │
└──────────────────────────────────┘
```

Perubahan konkret di `components/v2/dasbor/KartuStatistik.tsx`:

| Sekarang | Jadi |
|---|---|
| `--card-spacing` bawaan 24px | `[--card-spacing:--spacing(4)]` (16px) — header tak lagi setinggi 72px. **Ini jawaban temuan 1a "header terlalu besar"**, dan mekanismenya milik kit (`ui-maia/card.tsx:15`), nol berkas registry disentuh |
| `CardDescription` polos (ikon + teks abu) | ikon masuk **kotak** `size-7 rounded-lg border bg-card`, label `text-sm font-medium text-foreground/80` |
| `CardHeader className="border-b"` | `+ bg-muted/40` — strip abu tipis persis referensi |
| lencana pil di kanan angka | `LencanaDelta polos` **di bawah** angka, diikuti `vs 30 hari sebelumnya` pada baris yang sama |
| `CardFooter` "vs 30 hari sebelumnya" | dicabut (teksnya pindah ke baris lencana) |
| `<Sparkline>` + `CardContent` pembungkusnya | **dicabut** (W-1). `t.deret` tetap datang dari server, tak dibaca |

Tinggi keempat kartu jadi seragam **tanpa syarat** — sebelumnya kartu tanpa
`deret` (mis. "Perlu Diperiksa (MD)") kehilangan sparkline + footer dan jadi
lebih pendek dari tetangganya.

### F1d — Irama header seluruh widget (temuan 1a)

`CardTitle` maia = `font-heading text-base font-medium` (`ui-maia/card.tsx:40`).
Pemilik: "bold namun jangan terlalu bold, secukupnya saja" → `font-semibold`,
ukuran turun ke `text-sm` supaya sepadan dengan referensi.

Diterapkan **di tempat pemanggilan** (`<CardTitle className="text-sm font-semibold">`),
bukan dengan menyunting `ui-maia/card.tsx`, pada sebelas kartu dasbor:
`GrafikOverview` · `MeterTertinjau` · `KartuDistribusi` · `KartuSebaran` ·
`PitaDepartemen` · `SebaranJenisDept` · `PerformaPic` · `LacakStatus` ·
`KartuKetersediaan` · `KartuMasukan` · `KartuLog` · `AktivitasTerbaru`.

`KartuSambutan` dikecualikan (judulnya memang judul halaman, `text-xl`).

### F1e — Review kedua pemilik: header 4 kartu KPI masih salah (W-6, DIPERBAIKI)

Analisis pertama di seksi ini (revisi awal) SALAH: baru memeriksa `CardTitle`
kesebelas widget F1d, padahal keluhan pemilik justru soal `KartuStatistik`
(F1c) — di situ `CardDescription` masih **ikon dulu, baru label** (kotak ikon
di kiri, teks label mengikutinya). Pemilik menegaskan itu tetap salah: judul
tak boleh didahului ikon SAMA SEKALI, termasuk di kartu KPI — bukan cuma di
sebelas widget lebar. Diperbaiki:

- `components/v2/dasbor/KartuStatistik.tsx` — header diganti `CardTitle`
  (label, kiri, bold) + `CardAction` (kotak ikon, kanan), bukan lagi
  `CardDescription` ikon-lalu-teks. Import `CardDescription` dicabut,
  `CardAction` ditambah.
- **Bold wajib** di SEMUA judul widget — `font-semibold` dinaikkan ke
  `font-bold` di kesebelas widget F1d **dan** `KartuStatistik`, bukan disamakan
  ke `font-semibold` seperti draf pertama.
- **Header dibedakan dari body** — `bg-muted/40` ditambah ke `CardHeader`
  kesebelas widget (sebelumnya cuma `border-b`, tanpa beda warna latar);
  `KartuStatistik` sudah punya ini dari F1c, dipertahankan.

Berkas yang berubah sesi ini: `KartuStatistik.tsx` + sebelas widget F1d
(`AktivitasTerbaru`, `GrafikOverview`, `KartuDistribusi`, `KartuKetersediaan`,
`KartuLog`, `KartuMasukan`, `KartuSebaran`, `LacakStatus`, `MeterTertinjau`,
`PerformaPic`, `PitaDepartemen`, `SebaranJenisDept`). `KartuSambutan` tetap
dikecualikan (§F1d). `ArusDokumen.tsx`/`PerluTindakan.tsx` tetap tak disentuh
— tak terpakai (`Dashboard.tsx:28,42,46`), sama seperti `Sparkline.tsx`.

**Verifikasi:** `tsc --noEmit` bersih · `npm run build` OK · `npm run uji-render`
OK · `php artisan smartpro:uji-render --pratinjau` — 29 halaman termasuk
`dashboard` render tanpa galat.

---

## F2 — Sebaran per Departemen (temuan 1b + mandat #3)

Acuan: `docs/dasbor_ref/sebaran_perdepartemen.png`.

### F2a — Warna: ramp merah PPA (W-2)

`components/v2/dasbor/PitaDepartemen.tsx:22-29` — peta `WARNA` enam token
`--chart-1..6` **diganti** ramp satu hue:

```tsx
// Enam langkah dari SATU hue = --chart-1 (merah PPA), tua→muda. Mekanisme
// color-mix yang SAMA dengan donat KartuSebaran.tsx:54 (keputusan D2/F5) —
// dua kartu bersebelahan karena itu berbicara satu bahasa warna.
const warnaJenis = (i: number, n: number) =>
    `color-mix(in oklab, var(--chart-1) ${90 - i * (60 / Math.max(n - 1, 1))}%, var(--card))`;
```

`DasborV2Test::test_peta_warna_sebaran_lengkap` membaca peta `WARNA` yang lama →
**diperbarui**: yang dijaga kini "tiap kode `DocumentType::kode()` mendapat satu
langkah ramp, dan nol langkah dipakai dua kali".

> Ini mencabut ulang §P5/§6 untuk kartu ini saja (palet kategorikal → ramp
> sekuensial). Sah karena permintaan pemilik eksplisit **dan** karena legendanya
> menyebut nama tiap jenis, bukan mengandalkan warna sebagai satu-satunya
> pembeda. Alasannya wajib ditulis di komentar berkas (CLAUDE.md §13).

### F2b — Isi seluruh ruang kosong (temuan 1b, mandat #3)

| Sekarang | Jadi |
|---|---|
| `ChartContainer … h-[220px]` **tetap** | `flex-1 min-h-[260px]` di dalam `CardContent className="flex flex-1 flex-col"` — batangnya memanjang mengikuti tinggi kartu, dan kartu ini memang `h-full` sejajar `PerformaPic` |
| tanpa `YAxis` | `<YAxis tickLine={false} axisLine={false} tickMargin={8} width={32} />` — referensi punya sumbu 0 / 0.3k / 0.6k / 0.9k / 1.2k di kiri |
| `CartesianGrid vertical={false}` | `+ strokeDasharray="3 3"` (garis putus tipis, persis referensi) |
| batang setipis default | `maxBarSize={44}` pada tiap `<Bar>` — tujuh departemen di lebar 2/3 kolom |
| legenda titik bulat di BAWAH | pindah ke **kanan-atas**, tiap butir = batang tebal `h-1.5 w-8 rounded-full` + nama + persen di bawahnya (bentuk "On-Time 78% / Late 13% / Absent 10%" di referensi) |
| radius `[2,2,0,0]` | `[4,4,0,0]` — referensi berujung membulat jelas |

Kepala kartu (dua angka besar + `LencanaDelta`) tetap, hanya pindah sebaris
dengan legenda: `flex flex-wrap items-start justify-between`.

### F2c — `SebaranJenisDept.tsx` (dasbor SH/DH) ikut

Perlakuan tata letak yang **sama persis** (F2b). Warnanya **TIDAK** ikut ganti —
kartu itu menumpuk **status**, dan warna status wajib dari `statusMeta` props
(CLAUDE.md §4). Hanya `PitaDepartemen` yang menumpuk jenis.

---

## F3 — Lacak Status bagian atas (mandat #4)

**Status: SELESAI.** `AngkaStatus` ditata ulang persis tabel di bawah — angka
`text-3xl` dulu, `LencanaDelta polos` di samping label, track `Progress`
`h-2 rounded-full` diwarnai `color-mix(in oklab, var(--rona) 18%, transparent)`.
Verifikasi: `tsc` bersih, `npm run build` OK, `smartpro:uji-render --pratinjau`
29/29 halaman.

Acuan: `docs/dasbor_ref/lacak_status_dokumen.png`. Hanya `AngkaStatus`
(`components/v2/dasbor/LacakStatus.tsx:279-306`) + kepala kartunya. **Tabel di
bawahnya tidak disentuh** (desain R9).

| Sekarang | Jadi (referensi) |
|---|---|
| label kecil **di atas** angka | **angka dulu** `text-3xl font-semibold tabular-nums`, label di bawahnya |
| lencana pil di samping angka | `LencanaDelta polos` (F1b) **di samping label**, `text-xs` |
| `Progress` track `bg-muted` seragam | track diberi rona statusnya sendiri: `backgroundColor: color-mix(in oklab, var(--rona) 18%, transparent)` — di referensi tiap batang punya jalur berwarna muda senada, dan itulah yang membuat empat kolomnya terbaca sebagai empat hal berbeda |
| `h-1.5` | `h-2` + `rounded-full` |
| `aliranKet` `text-[11px]` | tetap — ia yang menjaga larangan §5b (lencana mengukur **aliran masuk**, bukan perubahan potret) |

Warna tetap dari `statusMeta` props; nol hex diketik.

---

## F4 — Kontras & keterbacaan (temuan 4, 5, 7)

**Status: SELESAI** (F4a–F4d, semua sub-bagian di bawah). Verifikasi: `tsc`
bersih, `npm run build` OK, `smartpro:uji-render --pratinjau` 29/29 halaman.
`components.json` & registry `ui-maia/**` tak disentuh — F4b lewat CSS
berlingkup `.ui-v2`, F4c pakai varian `outline` yang sudah ada di `item.tsx`.

Tiga temuan, satu akar tema: `--secondary` / `--muted` / `--input` terlalu dekat
dengan `--card` / `--background` / `--popover` putih, lalu dipakai **tanpa garis
tepi**.

### F4a — Papan ketersediaan (temuan 4)

`components/v2/dokumen/fields/PapanKetersediaan.tsx:337-348`. Sel "tersedia"
`bg-secondary` (L 0.967) di atas wadah `bg-muted` (L 0.960) → **ΔL 0.007**, rasio
≈ 1.02:1. Dua belas dari empat belas sel melebur jadi satu balok abu.

- sel tersedia: `bg-secondary` → `bg-card ring-1 ring-border` (tiap hari dapat
  bidangnya sendiri);
- wadah pita: `bg-muted` → `bg-muted/60`;
- label tanggal `text-[0.65rem] text-muted-foreground` → `text-xs text-foreground/70`;
- legenda ikut menyesuaikan supaya tetap cocok dengan pitanya.

### F4b — Kotak isian bisa vs tak bisa diisi (temuan 5, bagian 1)

`ui-maia/input.tsx:11` & `textarea.tsx:10` **HARAM DISUNTING** (CLAUDE.md §4).
Perbaikannya lewat **CSS variable**, bukan berkas registry:
`resources/css/app.css` blok `.ui-v2` — `--input` diturunkan terangnya sehingga
`bg-input/30` benar-benar terbaca sebagai bidang isian, dan tambahkan satu aturan
berlingkup `.ui-v2` untuk keadaan yang tak bisa diisi:

```css
.ui-v2 [data-slot='input'][readonly],
.ui-v2 [data-slot='input']:disabled,
.ui-v2 [data-slot='textarea'][readonly],
.ui-v2 [data-slot='textarea']:disabled {
    background: var(--muted);
    border-style: dashed;
}
```

Garis putus + latar abu adalah pembeda **bentuk**, bukan cuma terang — ia tetap
terbaca saat mode gelap dan bagi penderita buta warna. Sekarang satu-satunya
pembeda adalah `opacity-50` (ΔL ≈ 0.01), dan `readOnly` bahkan tak digambar sama
sekali.

### F4c — Popup "Ada N hal yang menunggu Anda" (temuan 5, bagian 2)

`components/v2/DialogAntrean.tsx:45` — `<Item variant="muted">` = `bg-muted/50` +
`border-transparent` di atas `bg-popover` putih → ΔL 0.02 tanpa tepi.
`variant="muted"` → `variant="outline"` (varian yang **sudah ada** di
`ui-maia/item.tsx`, nol berkas registry disentuh). Tiap tugas kembali punya
bidangnya sendiri.

### F4d — Label "Cari" (temuan 7)

`components/v2/PenyaringDokumen.tsx:161` — `<Label className="sr-only">` →
`className="text-muted-foreground mb-1 text-xs"`. Teksnya sudah tersedia lewat
prop `labelCari` (default `'Cari'`, `:57`), dan kelasnya disalin persis dari
label penyaring lain (`:183`). Satu baris; berlaku di **seluruh** daftar yang
memakai penyaring ini, bukan cuma dasbor.

---

# BAGIAN B — BUG FUNGSI (F5–F8)

## F5 — CSRF unggahan + batas 5MB (temuan 2, 3)

### Akar penyebab (satu berkas, tiga pemanggil)

`resources/js/lib/unggah.ts:23-25` membaca token dari
`<meta name="csrf-token">`. Meta itu dirender **sekali** saat halaman dimuat
penuh dan **tak pernah** diperbarui di SPA Inertia. `LoginController::store():37`
memanggil `session()->regenerate()` yang **memutar token CSRF**, dan Inertia
mengikuti redirect-nya lewat XHR — dokumen tidak dimuat ulang.

Jadi sejak login sampai F5 ditekan, meta memegang token **pra-login** sementara
sesi memegang token pasca-login. Navigasi Inertia lain tetap normal karena axios
mengirim `X-XSRF-TOKEN` dari cookie `XSRF-TOKEN` yang Laravel tulis ulang di
setiap respons. Hanya `fetch` mentah yang memakai meta yang patah.

**Perbaikan** — `resources/js/lib/unggah.ts`, satu fungsi:

```ts
/**
 * Token diambil dari cookie `XSRF-TOKEN`, BUKAN dari <meta>: meta dirender
 * sekali saat muat penuh dan tak pernah disegarkan di SPA, sementara
 * LoginController memutar token sesi saat login. Cookie-nya ditulis ulang
 * Laravel di SETIAP respons, jadi ia satu-satunya sumber yang selalu segar.
 */
function xsrf(): string {
    const m = /(?:^|;\s*)XSRF-TOKEN=([^;]*)/.exec(document.cookie);
    return m ? decodeURIComponent(m[1]) : '';
}
// header: { 'X-XSRF-TOKEN': xsrf(), … }   ← BUKAN X-CSRF-TOKEN
```

`X-CSRF-TOKEN` **dibuang**, tidak dipertahankan sebagai cadangan: Laravel
memeriksanya lebih dulu, jadi nilai basi di sana justru menggagalkan pemeriksaan
sebelum `X-XSRF-TOKEN` sempat dilihat.

**Dua penumpang cacat yang sama, ikut diperbaiki di fase ini** (mereka menyalin
`csrf()` alih-alih memanggilnya):
- `pages/V2/Documents/Edit.tsx:345-346` — autosave wizard;
- `components/v2/tinjau/PanelAi.tsx:255-257` — pemanggil analisis AI (F8).

Keduanya diarahkan memanggil satu helper `xsrf()` yang di-`export` dari
`lib/unggah.ts`, supaya salinan ketiga tak lahir lagi. Kembaran V1 (`components/
dokumen/fields/*`, `pages/Documents/Edit.tsx`, `components/tinjau/PanelAi.tsx`)
ikut sembuh untuk `lib/unggah.ts` karena berkasnya memang bersama; dua salinan
V1 lainnya disamakan sekalian (perbaikan perilaku, bukan menggambar).

### Batas 5MB (W-3)

| Berkas | Perubahan |
|---|---|
| `app/Http/Controllers/DocumentController.php:823` | `'max:2048'` → `'max:5120'` |
| `database/seeders/DocumentTypeSeeder.php:256` | `'image_max_mb' => 2` → `5` |
| SQL `document_types.schema_json` | kunci `image_max_mb` field flowchart → `5` (skema hidup di DB; seeder hanya untuk pemasangan baru) |
| `components/v2/dokumen/fields/ImageUpload.tsx:98` | fallback `?? 2` → `?? 5` |
| `lib/unggah.ts:53-58` | docblock "maksimal 2MB" → 5MB |

PHP lokal sudah `upload_max_filesize=40M` / `post_max_size=40M` — aman.
**Produksi (Hostinger) wajib diperiksa**: bila `post_max_size` < 5M, badan POST
dibuang PHP dan galatnya muncul sebagai "berkas wajib diunggah", bukan "terlalu
besar". Dicatat ke `docs/RUNBOOK-RILIS.md`.

**Tes (+1):** unggah 3MB (di bawah 5120, di atas 2048) → 200 + `path` terisi.

---

## F6 — Preview Langkah 2 + modal nomor bentrok (temuan 6, 11)

### F6a — Tombol Preview mati di Langkah 2

Tombolnya **tidak** bergantung langkah (`pages/V2/Documents/Edit.tsx:556`), dan
handler-nya juga tidak. Yang patah adalah kontraknya: iframe hanya dimuat ulang
bila string `versi` berubah, dan `versi` = `PdfRenderer::sidik()` yang dihitung
dari `MAX(updated_at)` tiga tabel (`PdfRenderer.php:151-155`).

Dua hal menabrakkannya khusus di Langkah 2:
1. `DocumentService::saveSection()` memakai `updateOrCreate` — Eloquent tak
   menyentuh `updated_at` bila tak ada atribut yang kotor. Autosave 1200 ms sudah
   menyimpan isian sebelum jari sampai ke tombol → klik Preview menulis data
   identik → sidik tak naik → `src` iframe identik → React tak menyentuh
   atributnya → **tak terjadi apa-apa**.
2. Langkah 2 satu-satunya langkah ber-seksi `user_picker`, dan
   `DocumentController::autosave():777-779` sengaja `continue` untuk seksi itu.
   Jadi perubahan yang tersisa di langkah itu memang tak pernah menggerakkan sidik.

**Perbaikan (klien, satu berkas).** Preview adalah perintah **eksplisit** —
"muat ulang sekarang", bukan "muat ulang kalau ada yang berubah". Cukup jadikan
`versi` unik per klik:

```ts
const pratinjau = async () => {
    setPreviewing(true);
    const j = await simpanDiam();
    onVersi(`${j?.v ?? versi}-${Date.now()}`);   // selalu berubah
    setPreviewing(false);
};
```

Singgahan PDF sisi server **tidak** terpengaruh: `?v=` cuma query string; kunci
singgahan `PdfRenderer` dihitung dari sidik dokumen, bukan dari URL. Autosave
otomatis tetap memakai `j.v` apa adanya — hanya klik sadar yang memaksa.

Ditambah: `simpanDiam()` (`Edit.tsx:337-355`) menelan kegagalan diam-diam
(`r.ok ? json : null`). Tambahkan satu `toast` galat saat `!r.ok` — tombol yang
tampak mati tanpa satu pun pesan adalah cara gagal yang paling mahal.

### F6b — Modal nomor bentrok tak pernah muncul

Rantai servernya **sehat** dan bertes (`ArsipDocumentTest:425-432`):
`StoreDocumentRequest:44-51` / `ArsipDocumentRequest:82-94` mem-flash
`nomorSaran`, `DocumentController::create():231` meneruskannya ke props.

Yang mati ada di `pages/V2/Documents/Create.tsx:64-65`:

```tsx
const bentrokNomor = Boolean(errors?.doc_number && nomorSaran);
const [bentrok, setBentrok] = useState(bentrokNomor);   // hanya jalan saat MOUNT
```

`form.post()` Inertia memakai `preserveState: true` untuk metode non-GET, jadi
komponen **tidak** di-mount ulang sesudah redirect-back — `bentrok` tetap `false`
selamanya meski `errors.doc_number` + `nomorSaran` keduanya sudah ada di props.
Yang muncul cuma galat merah inline, persis yang dilaporkan.

**Perbaikan** — pakai callback yang memang disediakan Inertia, bukan `useEffect`
penyelaras:

```tsx
const opsi = { onError: (e: Record<string, string>) => setBentrok(Boolean(e.doc_number)) };
const kirim = () => form.post(url, { forceFormData: arsip, ...opsi });
```

Dipasang di **kedua** pemanggil (`kirim()` `:94` dan `pakaiNomorOtomatis()`
`:117`), sehingga percobaan kedua yang bentrok lagi tetap memunculkan dialognya —
cacat yang `useEffect` justru tak tertangkap karena nilainya tak berubah.

**Tes (+1):** `ArsipDocumentTest` — sesudah kiriman bentrok, props halaman
`Create` memuat `errors.doc_number` **dan** `nomorSaran` (pengunci rantai
servernya sudah ada; yang ditambah adalah pengunci bahwa keduanya tiba bersama
pada respons yang **sama**).

---

## F7 — Penomoran revisi + Langkah 3 (temuan 9, 10)

### F7a — Edisi ≥2 mulai dari Revisi 1 (W-5)

`app/Services/DocumentService.php:167-170`:

```php
// SEKARANG:  $noRevisi + 1 > self::MAKS_REVISI ? [$edisi + 1, 0] : [$edisi, $noRevisi + 1]
// JADI:      $noRevisi + 1 > self::MAKS_REVISI ? [$edisi + 1, 1] : [$edisi, $noRevisi + 1]
```

Tabel yang dituju (`MAKS_REVISI` tetap 5):

| Edisi masuk | Revisi masuk | Keluar |
|---|---|---|
| 1 | 0 … 4 | Edisi 1, Revisi 1 … 5 |
| 1 | **5** | **Edisi 2, Revisi 1** |
| 2 | 1 … 4 | Edisi 2, Revisi 2 … 5 |
| 2 | **5** | **Edisi 3, Revisi 1** |

Dokumen BARU tetap Edisi 1 Revisi 0 — itu ditulis saat pembuatan
(`InformasiController.php:216`, `createArsip`), bukan oleh `nextEditionRevision()`.

Empat validator ber-`min:0` **dibiarkan** (`ArsipDocumentRequest:67`,
`DocumentArsipController:132`, `StoreInformasiRequest:68`,
`DocumentWizard:88` `max(0,…)`): Edisi 1 Revisi 0 sah, dan dokumen lama yang
sudah terbit ber-"Edisi 2 Revisi 0" tak boleh mendadak ditolak saat disunting.
Komentar `ApprovalController:99-105` yang menyebut "Edisi 2 Rev 0" diperbarui.

**Tes:** `tests/Unit/RollOverRevisiTest.php:25-33` (`5 => [2,0]`, `6 => [2,0]`) dan
`tests/Feature/RevisionTypeBTest.php:74-77` + `:105-106,116` **diperbarui** ke
`[2,1]` / `[3,1]`. Nol tes dihapus.

### F7b — Langkah 3 wizard revisi menunjukkan nomor akan naik (temuan 9)

Servernya **sudah** mengirim angkanya: `DocumentController:663-667` →
prop `revisiKirim = {edisi, revisi}`. Yang kurang ada di layar
(`components/v2/dokumen/fields/RevisionLog.tsx:84-118`): dua kotak isian di
puncak langkah menampilkan angka **versi lama**, dan angka barunya cuma muncul
sebagai kalimat banner **kondisional** (`berbeda`, `:80`) — hilang persis saat
angkanya kebetulan sama.

Jadi tambahkan satu blok "sebelum → sesudah", **selalu** tergambar:

```
┌─ Penomoran versi ini ───────────────────────────────────────┐
│   Sekarang               Setelah dikirim                    │
│   Edisi 1 · Revisi 4  →  Edisi 1 · Revisi 5                 │
│   [ Edisi: 1 ] [ Revisi: 4 ]      (kotak isian manual)      │
│   Otomatis (roll-over) — boleh diubah manual.               │
└─────────────────────────────────────────────────────────────┘
```

Sisi "Setelah dikirim" `text-primary font-semibold`; ia dibaca dari
`revisiKirim`, bukan dihitung di TSX. Banner kondisional `:107-118` diganti blok
ini (bukan ditambah di sampingnya — dua kalimat yang mengatakan hal sama akan
menyimpang). Halaman `Arsip/Catatan.tsx:113-121` memakai komponen yang sama →
ikut sendiri.

**Tes (+1):** `WizardInertiaTest` — `revisiKirim` ikut di props langkah Log
Revisi untuk draft revisi Tipe B, dan angkanya = `[edisi, revisi]` hasil
`revisiSaatKirim()`.

---

## F8 — AI memberi hasil, atau mengatakan kenapa tidak (temuan 8)

### Yang TIDAK rusak

Pengerasan pra-produksi Fase 3 **lengkap** (`NullReviewer` beralasan, kartu
Kesehatan, penegakan `users.ai_review_enabled` di server 403). Konfigurasinya pun
**sehat**: `ai.enabled=true`, kunci utama & cadangan terisi (73 karakter
terdekripsi), kedua id model sah di katalog OpenRouter, `FallbackReviewer::ping()`
mengembalikan `null` (= lolos), dan `users.ai_review_enabled = 1` untuk 118 dari
118 akun.

### Yang rusak — dua hal

**1. `PanelAi.tsx:82-88` tak pernah memeriksa `r.ok`.**

```tsx
const d = await r.json();
setRingkasan(d.summary || '');
setTemuan(d.findings || []);
```

Setiap balasan galat tanpa field `summary` — 419 (token basi, akar F5), 403, 429,
500 — menghasilkan `ringkasan=''`, `temuan=[]`, `galat=''`, lalu jatuh ke cabang
`:211-216` dan tergambar sebagai **centang hijau "Tidak ada temuan signifikan
dari AI."** Kegagalan total dilaporkan sebagai keberhasilan. Itu persis "AI tidak
memberikan hasil apapun".

Perbaikan: periksa `r.ok` lebih dulu; `!r.ok` → `setGalat(d.message ?? d.summary ?? 'Analisis AI gagal (HTTP '+r.status+').')`
dan **`setAnalyzed(false)`**, supaya cabang hijau tak pernah tercapai untuk
percobaan yang gagal.

**2. Kredit penyedia habis.** `storage/logs/laravel.log:11738-11749`:
`AI review failed … "message":"402 — Insufficient credits"`. Model `:free`
OpenRouter tetap menuntut saldo minimum pada akun. `ping()` tak menangkapnya
karena ia hanya menguji `/api/v1/key`, bukan model — sudah dicatat sendiri di
`OpenRouterReviewer.php:47-49`.

→ **Tindakan pemilik, bukan kode**: isi saldo OpenRouter (atau ganti ke penyedia
dengan kuota gratis nyata), lalu buktikan lewat tombol "Uji koneksi AI". Sesudah
perbaikan 1, kegagalan seperti ini akan **tampil sebagai pesan merah yang
menyebut 402**, bukan centang hijau.

Ditambah: `PanelAi.tsx:255-257` memakai token meta yang basi → diperbaiki di F5.

**Tes (+2):** `AiReviewTest` — (a) balasan 403 (`ai_review_enabled = false`)
menghasilkan keadaan galat di props/kontrak, bukan "tak ada temuan"; (b)
`NullReviewer` ber-`TAK_LENGKAP` melaporkan alasannya sampai ke `summary`.
(Bagian TSX-nya dijaga gerbang render + pemeriksaan mata F9.)

---

## F9 — Pemeriksaan mata (tugas mata, bukan gerbang)

Buka `/dashboard` sebagai **GL · SH · DH · MD · PJO · Non-Staff · Admin**, tema
terang DAN gelap, lebar 1366px:

- keempat kartu KPI **sama tinggi**, dan tak satu pun berlencana "Baru";
- Sebaran per Departemen: batang memenuhi tinggi kartu, sejajar dengan Performa
  PIC, tujuh label tak bertabrakan, segmen bernilai nol tak jadi garis rambut;
- Lacak Status: empat jalur batang berwarna berbeda dan terbaca;
- papan ketersediaan: keempat belas hari terhitung satu per satu;
- kotak isian yang `readonly` terbaca berbeda **tanpa perlu diklik**;
- dialog "hal yang menunggu Anda": tiap baris punya bidangnya;
- label "Cari" sejajar dengan label Status/Jenis/Sumber.

Hasil dicatat di `docs/CEKLIS-MATA-PER-PERAN.md`. **Kirimkan satu tangkapan layar
per fase** — itu bahan yang membuat fase berikutnya tidak menebak.

---

## Ringkasan berkas

**Server (PHP)** `app/Services/DasborTampilan.php` (F1a) ·
`app/Services/DocumentService.php` (F7a) ·
`app/Http/Controllers/DocumentController.php` (F5 batas, F6a autosave) ·
`database/seeders/DocumentTypeSeeder.php` (F5) · satu skrip SQL `image_max_mb`

**Klien (TSX, pohon V2)** `components/v2/dasbor/{LencanaDelta,KartuStatistik,
PitaDepartemen,SebaranJenisDept,LacakStatus}.tsx` + sebelas `CardTitle` (F1–F3) ·
`components/v2/{DialogAntrean,PenyaringDokumen}.tsx` (F4) ·
`components/v2/dokumen/fields/{PapanKetersediaan,ImageUpload,RevisionLog}.tsx` ·
`components/v2/tinjau/PanelAi.tsx` · `pages/V2/Documents/{Edit,Create}.tsx` ·
`resources/js/lib/unggah.ts` · `resources/css/app.css` (F4b, berlingkup `.ui-v2`)

**Kembaran V1 yang ikut** (perbaikan perilaku saja, F5–F8):
`components/dokumen/fields/*` · `components/tinjau/PanelAi.tsx` ·
`components/DialogAntrean.tsx` · `pages/Documents/{Edit,Create}.tsx`

**Dokumen** `CLAUDE.md` (§7 tabel roll-over · §13 palet ramp kartu sebaran ·
§14c patokan tes) · `docs/DASBOR-V4-RENCANA.md` (status F8) ·
`docs/CEKLIS-MATA-PER-PERAN.md` · `docs/RUNBOOK-RILIS.md` (`post_max_size` ≥ 5M)

**TIDAK disentuh** — `components/ui/**` · `components/ui-maia/**` ·
`components.json` · seluruh daftar mesin cetak CLAUDE.md §14b · tabel
`LacakStatus` (desain R9) · `routes/api.php` & `app/Http/Resources/*`

**Patokan tes**: 753 → **±759** (nol dihapus; 4 diperbarui: `DasborV2Test:889`,
`test_peta_warna_sebaran_lengkap`, `RollOverRevisiTest`, `RevisionTypeBTest`;
~6 baru: F5 +1, F6b +1, F7b +1, F8 +2).

## Verifikasi menyeluruh (dijalankan sesudah F8)

1. **Login → unggah flowchart 4MB** tanpa refresh halaman → berhasil (dulu "CSRF
   token mismatch"). Ulangi untuk tempel gambar di kotak Quill.
2. **Wizard Langkah 2** → ubah peninjau → klik Preview → iframe benar-benar
   memuat ulang.
3. **Revisi berulang** dokumen Berlaku sampai Revisi 5, lalu sekali lagi →
   **Edisi 2 Revisi 1**, dan Langkah 3 menampilkannya sebelum dikirim.
4. **Unggah dokumen lama bernomor yang sudah dipakai** → **modal** muncul → "Pakai
   nomor otomatis" → terdaftar dengan nomor bebas berikutnya.
5. **Tinjau JSA → Analisis AI** → temuan muncul; matikan internet → **pesan merah**
   yang menyebut sebabnya, bukan centang hijau "tidak ada temuan".
6. **Dasbor tujuh peran, dua tema** (F9).
