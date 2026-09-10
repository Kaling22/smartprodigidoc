# REDESAIN-UI-V2 — preset `bOfrsPWx1` (maia + hugeicons)

> Status: **T1 · T2 · T2b SELESAI (2026-08-31).** Brief desainnya
> bertahan; bagian yang ternyata keliru saat dikerjakan sudah dikoreksi di
> tempatnya dan ditandai — §6 (palet grafik) paling banyak berubah. §13 memuat
> hasil per butir untuk kedua tranche.
> Induk: `docs/SETUP-SHADCN.md` (Fase 0–3) · `docs/MIGRASI-SHADCN.md` (Fase 4–13) · `docs/PROGRESS-SHADCN.md` (status berjalan).
> Aturan yang tetap menang: `CLAUDE.md`. Pekerjaan ini **menambah** lapisan pratinjau; ia tidak mencabut satu pun larangan di sana.

---

## 1. Mengapa

Aplikasi sudah 100% shadcn/ui — 41 halaman Inertia, kerangka `dashboard-01`, token oklch di `:root`/`.dark`. Tetapi hasilnya dinilai "tiada sama sekali miripnya" dengan referensi. Penyebabnya bukan warna. `shadcn preset resolve` atas proyek ini mengembalikan `bOfGN5ILT`, yang **identik** dengan preset target `bOfrsPWx1` kecuali dua sumbu:

| sumbu | sekarang | target |
|---|---|---|
| `style` | `radix-nova` | **`radix-maia`** |
| `iconLibrary` | `lucide` | **`hugeicons`** |
| baseColor · theme · chartColor · font · fontHeading · radius · menuAccent · menuColor | mauve · rose · red · dm-sans · inter · default · subtle · inverted-translucent | **sama persis** |

Jadi jurangnya ada di **bentuk & irama komponen**, ditambah empat hal struktural yang tak diberikan preset mana pun:

1. **Tak ada blok judul halaman.** Judul hanya hidup di topbar; badan halaman langsung dibuka paragraf `text-muted-foreground text-sm`. Setiap kit dasbor membuka dengan H1 + kontrol di kanan — ini penanda "dasbor" yang paling terasa hilang.
2. **`nova` itu padat.** `--card-spacing: 4`, `rounded-xl`, tombol/input persegi. `maia`: `--card-spacing: 6`, `rounded-2xl`, tombol/input/badge **pil** (`rounded-4xl`), tombol menu sidebar `rounded-lg px-3 py-2`.
3. **Dasbor = sup grafik.** Sepuluh widget; empat di antaranya menjawab pertanyaan yang sama ("berapa dokumen"): tren, meter tertinjau, empat kartu angka, donat sebaran. Antrean kerja — satu-satunya hal yang benar-benar ditunggu pengguna — duduk di baris **keempat**, di bawah kartu sapaan.
4. **Tata letak berkelahi dengan data.** Peran tanpa widget tertentu mendapat `<div className="hidden @5xl/main:block" />` kosong sebagai ganjal.

Ditambah satu cacat teknis: `--chart-1..5` seluruhnya rampa merah (hue 19–27). Itu rampa **sequential** yang dipakai untuk peran **categorical** — dua deret di grafik tren dibedakan hanya oleh terang-gelap.

**Hasil yang dituju:** satu tranche pratinjau yang bisa dibuka **berdampingan** dengan halaman lama (sakelar sesi), berisi kerangka baru + dasbor yang disusun ulang + satu arketipe tabel + login. Nol fitur hilang. Koreksi menyusul lewat prompt berikutnya.

### Keputusan pemilik (2026-08-30)

| # | Pertanyaan | Keputusan |
|---|---|---|
| D1 | Isolasi komponen maia | **Folder `components/ui-maia/` terpisah.** `components/ui/` (nova) tak disentuh, 41 halaman lama tetap bisa dipakai sebagai pembanding |
| D2 | Pustaka ikon | **Pindah ke hugeicons**, sesuai preset. CLAUDE.md §2 & §13 direvisi saat approve |
| D3 | Cakupan tranche 1 | **Kerangka + dasbor + satu arketipe tabel + login.** 38 halaman sisa menyusul |
| D4 | Sidebar | **Rail ikon saat menciut** + **⌘K** + **tooltip alasan terkunci**. Grup yang bisa dilipat **tidak** dikerjakan — induk menu sudah melipat, lapisan kedua berarti dua klik per tautan sementara `item.active` sudah membuka cabang yang benar |

---

## 2. Yang TIDAK disentuh

- `components/ui/**` (nova) dan seluruh 41 halaman lama.
- Semua di CLAUDE.md §14b (mesin cetak, 23 berkas) dan `resources/views/**` — termasuk `layouts/guest.blade.php` yang mandiri tanpa `@vite`.
- `NavigasiSidebar.php`, `Document::STATUS_META`, `DocumentParticipantResolver`, `ReviewerAvailability`, `DocumentController::coAuthorCandidates()`. **Nol logika izin/status pindah ke TSX** (CLAUDE.md §4).
- `HandleInertiaRequests::share()` selain **satu** prop baru.
- Utang Fase 13.1 (79 Blade yatim) dan `docs/CEKLIS-MATA-PER-PERAN.md` — 183 butirnya tetap sah, karena halaman lama tak berubah sebyte pun.

---

## 3. Mekanisme pratinjau — 20 baris, nol duplikasi route

Menggandakan 41 route berarti 41 titik yang bisa menyimpang diam-diam. Ganti dengan satu subclass factory Inertia yang menyisipkan awalan `V2/` bila (a) sesi menyalakan pratinjau **dan** (b) kembaran TSX-nya ada. Controller, props, Policy, middleware `can:` — jalur yang sama persis.

**`app/Inertia/PratinjauResponseFactory.php`** (baru)

```php
class PratinjauResponseFactory extends \Inertia\ResponseFactory
{
    public function render($component, $props = []): \Inertia\Response
    {
        if (session('ui_v2') && is_file(resource_path("js/pages/V2/{$component}.tsx"))) {
            $component = "V2/{$component}";
        }

        return parent::render($component, $props);
    }
}
```

- **`app/Providers/AppServiceProvider.php`** — `$this->app->singleton(\Inertia\ResponseFactory::class, PratinjauResponseFactory::class)` di `register()`. Bila provider Inertia menang urutan pendaftaran, pindahkan ke `boot()`; tes di §10 yang memutuskan, bukan tebakan.
- **`routes/web.php`** — `POST /pratinjau/{aktif}` → `session(['ui_v2' => …])` + `back()`. Di dalam grup `auth`, **tanpa izin tambahan**: pemilik menguji tujuh peran lewat `CEKLIS-MATA-PER-PERAN.md`, jadi sakelarnya harus ada di semua peran. Isinya murni kosmetik — tak ada data yang bisa bocor lewatnya.
- **`HandleInertiaRequests`** — satu prop: `'uiV2' => fn () => (bool) session('ui_v2')`, supaya kedua kerangka bisa menggambar sakelarnya.
- **`components/SiteHeader.tsx` (yang LAMA) disunting** — sakelar dipasang di situ juga. Tanpa ini pratinjau tak punya pintu masuk.

Konsekuensi yang penting: bendera mati → `assertInertia(component('Dashboard'))` tetap lulus. **695 tes lama tidak bergerak, nol yang diubah.**

---

## 4. Komponen maia terisolasi — `components/ui-maia/`

Prosedur sekali jalan; `components.json` dikembalikan sesudahnya.

1. Salin `components.json` → `components.json.bak`.
2. Ubah tiga kunci: `"style": "radix-maia"`, `"iconLibrary": "hugeicons"`, `"aliases.ui": "@/components/ui-maia"`.
3. `npx shadcn@latest add --overwrite` untuk **34 komponen yang sudah ada** + **10 yang memberi tampilan kit**:
   `command` · `popover` · `item` · `empty` · `input-group` · `button-group` · `kbd` · `pagination` · `spinner` · `hover-card`
4. Kembalikan `components.json` dari `.bak`.

> `npx shadcn diff` untuk `ui-maia` mati selama jendela pratinjau — disengaja. Saat approve, `components.json` diubah permanen ke maia/hugeicons dan `diff` hidup kembali. Selama jendela itu, **tak ada satu pun berkas di `ui-maia/` yang boleh disunting tangan** (CLAUDE.md §4), kecuali satu pengecualian di bawah.

### Empat jebakan yang wajib diperiksa SESUDAH generate

1. **`sidebar.json` maia mengimpor `@/app/(create)/components/icon-placeholder`** — artefak halaman *create* milik situs shadcn. Kalau CLI tidak membuangnya, build gagal dengan modul tak ditemukan. Ini **satu-satunya** suntingan yang dibenarkan atas berkas hasil generate; tulis alasannya sebagai komentar di berkas itu.
2. **Alias bersama ikut tertimpa.** `aliases.hooks` dan `aliases.lib` **tidak** diubah, jadi `sidebar` menulis ulang `resources/js/hooks/use-mobile.ts` dan `resources/js/lib/utils.ts`. Keduanya style-agnostic, jadi seharusnya identik — **buktikan dengan `git diff` yang kosong**, jangan diasumsikan. Kalau tidak kosong, isolasi juga aliasnya.
3. **`tsconfig` `@/*` → `resources/js/*`** — pastikan `@/components/ui-maia/...` resolve sebelum menulis satu pun halaman V2.
4. **Utilitas `rounded-4xl`** yang dipakai maia berasal dari `--radius-4xl` di blok `@theme inline` `app.css` — sudah ada. Pastikan Tailwind benar-benar memancarkan kelasnya, bukan mendiamkannya.

### 4b. Dua Provider bersarang — kalau ini lewat, hasilnya LAYAR PUTIH

`ui/tooltip` dan `ui-maia/tooltip` adalah dua modul, jadi **dua React context**. `SidebarMenuButton` maia memanggil `<Tooltip>` maia; kalau yang terpasang hanya Provider nova, Radix **melempar** dan halaman jadi putih — persis kegagalan yang melahirkan `smartpro:uji-render` di Fase 3.

`resources/js/app.tsx` **dan** `resources/js/uji-render.tsx` (wajib cerminan, CLAUDE.md §14c):

```tsx
<ThemeProvider …>
  <TooltipProvider>            {/* nova — halaman lama */}
    <TooltipProviderMaia>      {/* maia — halaman V2  */}
      <App {...props} />
    </TooltipProviderMaia>
  </TooltipProvider>
  <Toaster … />                {/* TETAP SATU */}
</ThemeProvider>
```

`Toaster` **tidak** digandakan: `toast()` datang dari paket `sonner` langsung, jadi satu Toaster melayani kedua kerangka. Dua Toaster = tiap notifikasi muncul dua kali.

`SidebarProvider` nova dan maia berbagi cookie `sidebar_state` — tidak berbahaya, keduanya cuma menyimpan buka/tutup.

---

## 5. Ikon — hugeicons, terisolasi di V2

`@hugeicons/react@1.1.10` + `@hugeicons/core-free-icons@4.3.0` (keduanya terverifikasi ada di npm). APInya **bukan** komponen per ikon seperti lucide:

```tsx
import { HugeiconsIcon } from '@hugeicons/react';
import { Home01Icon } from '@hugeicons/core-free-icons';

<HugeiconsIcon icon={Home01Icon} size={16} strokeWidth={1.5} />
```

- **`components/v2/Ikon.tsx`** (baru) — **kunci yang sama persis** dengan `components/Ikon.tsx`: 85 entri `bi-*` yang datang dari `informasi_kategori.icon` (DB), `Document::STATUS_META`, dan `NavigasiSidebar` — semuanya konstanta PHP / baris tabel, jadi kuncinya tak boleh bergeser. Nilainya objek hugeicons. Pembungkus `<Ikon nama className />` meneruskan `className` dan menyetel `size`/`strokeWidth` bawaan.
- `components/Ikon.tsx` (lucide) **tidak disentuh** — halaman lama tetap hidup.
- Impor lucide langsung di dalam komponen V2 → hugeicons.
- **Penjaga:** satu tes yang menegaskan himpunan kunci `v2/Ikon.tsx` ⊇ himpunan kunci `Ikon.tsx`. Tanpa ini, satu entri terlewat dari 85 jatuh diam-diam ke ikon fallback dan tak seorang pun tahu — persis kelas kegagalan yang paling mahal di proyek ini.
- Saat approve: `v2/Ikon.tsx` menggantikan `Ikon.tsx`, `lucide-react` dicopot, **CLAUDE.md §2 dan §13 direvisi** (`lucide-react` → hugeicons; kalimat "Nol emoji (**lucide-react**)" ikut berubah).

Dependensi npm baru seluruhnya: `@hugeicons/react`, `@hugeicons/core-free-icons`, **`cmdk`** (dibutuhkan `command` untuk ⌘K).

---

## 6. Palet grafik — dihitung, bukan dikira

> **DIKOREKSI 2026-08-31 saat implementasi.** Angka di bawah BUKAN yang tertulis
> di rencana. Rencana menjanjikan sian/oker/ungu/hijau dengan pasangan terburuk
> ΔE 18.4 (deutan); validator yang dijalankan ulang atas permukaan kartu proyek
> yang sebenarnya mengembalikan **4.1**. Merah `#C8102E` dan oker `#A16207`
> adalah pasangan yang persis dikacaukan deuteranop, dan pita lightness usulan
> itu cuma **0.082** — nyaris iso-luminan, sehingga tak ada kanal kedua yang
> menolong. Palet itu karena itu tidak dipakai.

Sebelum menyalahkan paletnya, alatnya dikalibrasi dengan palet yang memang
dirancang untuk CVD:

| palet | normal | deutan | protan | tritan |
|---|---|---|---|---|
| Okabe–Ito (5, rancangan CVD) | 22.2 | **12.5** | 17.9 | **12.1** |
| Tableau 10 (5 pertama) | 22.9 | 0.7 | 6.2 | 9.7 |
| usulan rencana | 28.8 | **4.1** | 7.2 | 12.3 |

Okabe–Ito menandai langit-langit praktis pada ~12, jadi ambang CVD disetel 12
(bukan 15 — pada simulasi dikromasi penuh, ambang 15 tak dilewati palet nyata
mana pun). Dengan tolok ukur itu, usulan rencana memang jatuh; Tableau 10 pun
jatuh lebih dalam.

**Yang dipasang** — hue dipilih dengan pencarian max-min di bawah keempat mode
sekaligus, dijangkar pada merah PPA yang tak boleh bergeser:

| slot | terang | gelap | |
|---|---|---|---|
| `--chart-1` | `#C8102E` | `#EC414B` | merah PPA |
| `--chart-2` | `#187082` | `#3E8EA1` | teal |
| `--chart-3` | `#DD7723` | `#FE984E` | jingga |
| `--chart-4` | `#7D76F6` | `#9C9DFF` | ungu-biru |
| `--chart-5` | `#27A680` | `#50C69E` | hijau |

```
terang  ALL CHECKS PASS  — deutan 16.1 · protan 17.3 · tritan 16.0 · normal 25.9
gelap   ALL CHECKS PASS  — deutan 13.9 · protan 15.0 · tritan 14.8 · normal 24.4
```

Keenam pemeriksaan (pita lightness · lantai chroma · pemisahan CVD deutan/
protan/tritan · lantai penglihatan normal · kontras terhadap permukaan) lolos
di kedua mode, atas permukaan kartu yang sesungguhnya — `--card` terang
`#FFFFFF`, gelap `oklch(0.212 0.019 322.12)`.

**Varian gelap = hue dan chroma yang SAMA, lightness +0.10 OKLab.** Itu
keputusan, bukan kemalasan: pengoptimum yang dibiarkan memilih bebas untuk
mode gelap menghasilkan `chart-3` KUNING dan `chart-4` BIRU, padahal di mode
terang keduanya jingga dan ungu. Mengganti tema akan mengecat ulang legenda,
dan identitas deret adalah hal yang paling tak boleh goyah di grafik. Angka
pemisahannya sedikit lebih rendah karenanya (13.9 vs 15.3 yang bisa dicapai
tanpa kunci hue) — tetap di atas Okabe–Ito, dan itu pertukaran yang benar.

Dipasang **berlingkup** di `resources/css/app.css`, supaya halaman lama tak
ikut berubah:

```css
.ui-v2       { --chart-1:#C8102E; --chart-2:#187082; --chart-3:#DD7723; --chart-4:#7D76F6; --chart-5:#27A680; }
.dark .ui-v2 { --chart-1:#EC414B; --chart-2:#3E8EA1; --chart-3:#FE984E; --chart-4:#9C9DFF; --chart-5:#50C69E; }
```

Kelas `.ui-v2` dipasang di akar `layouts/V2/AppLayout.tsx` (dan `AuthLayout`).
Saat approve, dipindahkan ke `:root` / `.dark`.

**Tidak tersentuh:** hex `Document::STATUS_META` (warna status) dan
`DocumentType::RUPA` (warna donat sebaran). Keduanya milik server dan dipakai
apa adanya — CLAUDE.md §13. Warna status adalah palet **cadangan**; ia tak
boleh dipinjam jadi "deret ke-6".

> Validatornya sendiri tidak ikut di-commit — ia skrip sekali-pakai di
> scratchpad. Yang perlu diulang bukan skripnya melainkan **angkanya**, dan
> angka itu tertulis di komentar `app.css` tepat di sebelah warnanya.

---

## 7. Kerangka baru

**`layouts/V2/AppLayout.tsx`** — irama `dashboard-01` dipertahankan (`--sidebar-width: calc(var(--spacing)*72)`, `--header-height: calc(var(--spacing)*12)`, `Sidebar variant="inset"`, `@container/main`, irama `gap-4 py-4 md:gap-6 md:py-6`, talang `px-4 lg:px-6` **sekali saja di layout**), **plus blok judul halaman** yang selama ini hilang:

```
┌─ SiteHeader  [☰]  Beranda / Dokumen / Status        [⌘K] [🔔] [☀] [pratinjau ●] ─┐
├──────────────────────────────────────────────────────────────────────────────────┤
│  Selamat pagi, Aji Pradja                       [ Hari  Minggu  Bulan ] [+ Baru] │  ← H1 + aksi
│  Group Leader · Plant · 09.14 WITA                                               │  ← sub-line
├──────────────────────────────────────────────────────────────────────────────────┤
│  {children}                                                                      │
└──────────────────────────────────────────────────────────────────────────────────┘
```

Prop layout jadi `{judul, remah?, sub?, aksi?, children}`. `aksi` tak lagi mengambang sendirian di baris kosong di atas isi — ia duduk sebaris dengan H1, tempat mata mencarinya.

**`components/v2/AppSidebar.tsx`** — tetap **penggambar murni** atas prop `navigation`; nol pemeriksaan izin (CLAUDE.md §4). Empat perubahan:

| | sekarang | V2 |
|---|---|---|
| menciut | `collapsible="offcanvas"` — hilang total | **`collapsible="icon"`** — rel 3rem, ikon + tooltip tetap terlihat saat layar dilebarkan untuk tabel/wizard |
| cari | tak ada | **⌘K** `command` palette atas pohon `navigation` yang **sudah disaring server**. Admin IT punya 61 tautan (PJO 46 · MD 45 · SH 40 · GL 37 · DH 36 · Non-Staff 31) |
| terkunci | `<span title=…>` bawaan peramban — muncul ~1 detik, tak terbaca pembaca layar | `SidebarMenuButton` disabled + **Tooltip** shadcn berisi `item.title` dari server |
| grup dilipat | — | **tidak dikerjakan** (D4) |

> **CLAUDE.md §13 menyebut `collapsible="offcanvas"` sebagai kerangka acuan.** Mengubahnya ke `"icon"` berarti §13 ikut direvisi saat approve. Dicatat di sini supaya tak lolos sebagai penyimpangan diam-diam.

`Grup` / `Tautan` / `Terkunci`, titik `dot` induk, `SidebarMenuBadge`, logo dua berkas (dark/light dipilih CSS, nol JS, nol kedipan), logo klik → home, `NavUser` di `SidebarFooter` — semuanya bertahan.

**`components/v2/SiteHeader.tsx`** — trigger + breadcrumb (dari `remah`, item terakhir jadi `BreadcrumbPage`) + pemicu ⌘K + **Lonceng** (8 item, penanda belum dibaca, `99+`, "Tandai dibaca" hanya bila `unread > 0`, `notifications.open` — disalin utuh, nol query di klien) + toggle tema (ikon ditukar CSS, bukan JS) + **`SakelarPratinjau`**. Menu pengguna tetap di `SidebarFooter`, bukan header — aturan `dashboard-01`.

---

## 8. Dasbor — dari sup grafik jadi meja kerja

> ## ⛔ SELURUH §8 DAN §8b DIGANTIKAN `docs/DASBOR-V2-REVISI.md`
>
> Keputusan pemilik 2026-08-31 (D1/D2 di berkas itu): **dasbor balik PENUH ke
> tata letak V1.** Dari V2 yang dipertahankan hanya tema maia, ikon hugeicons,
> dan desain sidebar. Yang dibatalkan dari §8/§8b, satu per satu:
>
> | yang tertulis di §8/§8b | nasibnya |
> |---|---|
> | Larik deskriptor `{kunci, span, node}` + `grid-flow-row-dense` | **DIBATALKAN.** Susunan kembali ke tujuh baris 2/3 + 1/3 seperti `pages/Dashboard.tsx`, termasuk ganjal `<div hidden>`-nya — tanpa ganjal itu kolom 1/3 melompat ke kiri pada peran yang kehilangan kartu 2/3, dan dua kerangka jadi tak bisa dibandingkan berdampingan |
> | `PitaSambutan` (pita merek gradasi) | **DIBATALKAN.** Nol pengimpor; berkasnya masih di disk (K3) |
> | `AppLayout` prop `kepala` | **DICOPOT.** Nol pemakai begitu pita merek dibatalkan |
> | `ArusDokumen` (Overview + meter digabung) | **DIBATALKAN.** Kembali jadi `GrafikOverview` + `MeterTertinjau`, dua kartu, satu state `durasi` bersama |
> | `Tile.rona` + aksen batang tepi & kotak ikon bernada | **DICABUT** dari server maupun TSX. Penggantinya lencana kenaikan + sparkline yang datanya NYATA (di bawah) |
> | "Saat approve, CLAUDE.md §13 wajib ikut direvisi" | **GUGUR** — kewajiban itu melekat pada pita merek, dan pita merek dibatalkan. Nol permukaan berwarna penuh tersisa, jadi tak ada penyimpangan §13 yang perlu dicatat di sana |
> | `KartuPerjalanan` → "Perlu Tindakan Anda" | Digantikan **`LacakStatus`** (DASBOR-V2-REVISI §5b) |
>
> **Utang terbuka §8b "sparkline per KPI" — LUNAS.** §8b menutupnya dengan alasan
> "server SEKARANG BELUM menghitung deret tren per-kartu". DASBOR-V2-REVISI §5c
> menghitungnya: SATU sapuan `audit_logs` (tabel yang sudah ter-indeks di
> `action`/`created_at`), dikelompokkan aksi × bulan, delapan bulan, disaring hak
> akses — dan sapuan yang sama menyuapi lencana KPI, sparkline-nya, wajah
> "Paling Produktif", DAN tiga sorotan Performa PIC. Pagar §8b tetap berdiri
> utuh: badge tren KARANGAN tetap haram; kartu yang tak punya aksi audit yang
> jujur mewakilinya ("Perlu Diperiksa (MD)") pulang `delta: null` dan sengaja
> TIDAK berlencana.
>
> Yang di bawah ini dibiarkan apa adanya sebagai **catatan sejarah** — ia
> menjelaskan kenapa keputusan pembatalannya diambil. Jangan dikerjakan.

Urutannya dibalik mengikuti pertanyaan yang benar-benar dibawa pengguna saat membuka aplikasi: **apa yang menunggu saya → seberapa lancar arusnya → apa yang terjadi.**

```
JUDUL   Selamat pagi, Aji · GL · Plant · 09.14 WITA     [Hari Minggu Bulan] [+ Baru]
KPI     [ Draft 4 ]  [ Ditinjau 2 ]  [ Berlaku 31 ]  [ Revisi 1 ]
R1      ┌ Perlu Tindakan Anda ······· 2/3 ┐  ┌ Ketersediaan Saya · 1/3 ┐
R2      ┌ Arus Dokumen (grafik+meter) 2/3 ┐  ┌ Sebaran Jenis ····· 1/3 ┐
R3      ┌ Distribusi & Keterbacaan ·· 2/3 ┐  ┌ Aktivitas Terbaru · 1/3 ┐
R4      ┌ Masukan Lapangan ····································· 3/3 ┐
```

**Ganjal kosong dihapus.** Widget jadi larik deskriptor `{kunci, span, node}` yang dialirkan ke `grid grid-cols-3 grid-flow-row-dense`. Peran tanpa `distribusiWidget` tidak lagi menyisakan lubang — grid merapatkan sendiri. Ini **lebih sedikit** kode daripada `adaMeja` / `adaMasukan` / `duaKolom` + tiga `<div hidden>` yang ada sekarang, dan urutannya bisa ditukar dengan menggeser satu elemen larik alih-alih menulis ulang JSX.

| widget | perubahan | alasan |
|---|---|---|
| `KartuSambutan` | ~~dibubarkan → H1 + sub-line~~ → **PITA MEREK** (`v2/dasbor/PitaSambutan`), lihat §8b | pembubaran ternyata terlalu jauh: hasilnya sebelas kotak putih tanpa titik masuk |
| `KartuStatistik` | kartu maia, `text-3xl tabular-nums`, **seluruh kartu** jadi target klik + `hover:ring-primary/30`; sejak §8b + **aksen `rona`** (batang tepi kiri & kotak ikon bernada) | kartu tanpa `url` **tetap** tak bertaut — tautan berujung 403 lebih buruk daripada tak ada tautan |
| `KartuPerjalanan` → **Perlu Tindakan Anda**, naik ke R1 | tabel `table-fixed` → baris `Item`: nomor + judul di kiri, `StatusBadge` + progress umur di tengah, tombol CTA di kanan. Kosong → komponen `empty` | antrean adalah alasan orang membuka aplikasi ini; ia tak boleh ada di baris keempat |
| `GrafikOverview` + `MeterTertinjau` | **digabung** jadi satu kartu "Arus Dokumen": rail kiri (persen tertinjau besar + dua angka Dibuat/Berlaku), area chart bergradasi + crosshair tooltip di kanan, segmented control di `CardAction` | dua kartu → satu, dan state `durasi` jadi internal. Peretasan "satu state supaya dua angka tak beda rentang" tak perlu lagi diangkat ke halaman — ia jadi mustahil secara struktur |
| `KartuSebaran` | donat + **total di tengah** + legenda kanan (nama jenis · jumlah · %) | donat tanpa angka memaksa mata mengukur sudut. Warna tetap dari `DocumentType::RUPA` |
| `KartuDistribusi` | tabs Mutu/Informasi tetap; baris tabel → `Item`, `PitaCakupan` jadi progress tipis, `TumpukanWajah` `-space-x-2` + `hover-card` nama | — |
| `KartuKetersediaan` | kalender bulanan `react-day-picker` **dipertahankan apa adanya** | tiga perilaku wajib bertahan: navigasi bulan lewat `<Link>` Inertia `?bulan=YYYY-MM` (nav bawaan dimatikan `hideNavigation`, batas 12 bulan dihitung server) · klik tanggal membuka dialog Ajukan Off **terisi tanggal itu** · tanggal lewat mati. Tanggal diurai `tanggalLokal`, **bukan** `new Date('YYYY-MM-DD')` yang mundur sehari di WITA. Sel tetap **elastis** (`flex:1 1 0` / `1fr`) — CLAUDE.md §6 |
| `KartuLog` | garis timeline vertikal + titik rona (`maju`/`netral`/`baik`/`buruk`, dari server), `ScrollArea` tetap | — |
| `KartuMasukan` | pindah ke lebar penuh, 3-up kutipan | dua wajah (SH/DH/GL/PJO vs Non-Staff) & tiga tata letak (0 / 1–2 / 3+ kutipan) tetap |

> `PROGRESS-SHADCN.md` mencatat **K7**: pemilik masih perlu memutuskan kartu mana di kolom mana, per peran. Susunan di atas adalah **usulan**; larik deskriptor dibuat justru supaya keputusan itu murah diubah nanti.

### 8b. Pita merek + KPI berwarna — koreksi pemilik 2026-08-31 — ⛔ **DIBATALKAN, lihat banner §8**

Sesudah T2 diserahkan, pemilik menilai dasbornya **"masih jelek, kurang
nendang, tiada yang terlalu wah dan tampak spesial"**. Diagnosisnya bukan soal
selera warna melainkan lima hal yang bisa ditunjuk:

1. **Sebelas kotak putih yang identik** — 4 KPI + 7 widget, semuanya `Card`
   maia yang sama (putih, `ring-1`, `rounded-2xl`, jarak 6). Nol kontras
   ukuran, nol kontras permukaan; mata tak punya pintu masuk.
2. **KPI-nya empat kembar** — angka terpenting tak terlihat lebih penting
   daripada angka paling remeh.
3. **Merah PPA nyaris tak muncul** — `--primary` hanya hadir sebagai kotak
   ikon `bg-primary/8`: 8% opasitas, empat kali, masing-masing 40 px.
4. **Puncak halaman cuma teks** — H1 + satu baris abu-abu. Ironisnya inilah
   akibat §8 sendiri: `KartuSambutan` dibubarkan terlalu jauh.
5. **Nol kedalaman** — maia memang sengaja datar; datar itu elegan kalau ada
   yang menonjol, tapi kalau SEMUA datar ia terbaca *belum jadi*.

**Keputusan pemilik: pita merek + KPI berwarna.** Cakupan **dasbor saja** —
tujuh halaman V2 lain tak disentuh, supaya arahnya bisa dinilai berdampingan
dulu sebelum bahasanya disebar.

#### Yang dipasang

| | isi |
|---|---|
| `v2/dasbor/PitaSambutan.tsx` (baru) | Sapaan + jabatan/dept/jam/tanggal + **pil "yang menunggu"** + tombol `hero.aksi`, di atas gradasi `from-primary to-primary/70`. Dua lingkaran redup `bg-primary-foreground/10 blur-2xl` memberi kedalaman tanpa satu pun aset gambar |
| `layouts/V2/AppLayout.tsx` | prop opsional **`kepala`** yang MENGGANTI blok judul bawaan. Satu pemakai, sengaja begitu; `judul` tetap dipakai `<Head title>`. Tujuh halaman V2 lain nol perubahan keluaran |
| `v2/dasbor/KartuStatistik.tsx` | batang tepi kiri + kotak ikon **bernada `rona`** |
| `App\Services\DasborTampilan::tile()` | +kunci **`rona`** per kartu |

#### Empat pagar yang dijaga, dan kenapa

- **NOL HEX.** Gradasinya dua ujung dari SATU token (`--primary`); seluruh teks
  di atasnya `--primary-foreground` — pasangan yang MENURUT DEFINISI kontras
  di terang maupun gelap. Tombol utamanya pun `bg-primary-foreground
  text-primary`, bukan `variant="secondary"`: `--secondary` kebetulan terang di
  mode terang dan gelap di mode gelap, jadi di mode gelap ia jadi kepingan
  gelap di atas merah. Idiom yang sama dengan panel kanan `V2/Auth/Login` (§9b).
- **Warna KPI datang dari SERVER.** `Tile.rona` diisi `DasborTampilan::tile()`
  dengan kosakata yang SUDAH ada — `maju` · `netral` · `baik` · `buruk`, sama
  persis dengan feed aktivitas. TSX cuma menerjemahkannya ke kelas tema di satu
  tempat, pola yang sudah dipakai `KartuLog` dan diterima sejak T1. **Bukan**
  kunci status mentah: "Total Dokumen", "Perlu Disetujui", dan "Perlu Diperiksa"
  adalah ANTREAN, bukan status, dan memaksakan status bagi mereka = mengarang.
  CLAUDE.md §4 karena itu tetap utuh.
- **Angkanya TIDAK diwarnai.** Lantai mutu §10 — teks memakai token teks, bukan
  warna deret. Yang berwarna cuma batang tepi & kotak ikon.
- **NOL GERAK.** Tak ada satu pun animasi masuk, jadi tak ada yang perlu
  dijaga `prefers-reduced-motion`. Kesan datang dari kontras.

#### Ini penyimpangan §13, dan disengaja

CLAUDE.md §13 memuat keputusan pemilik Fase 4: *"KODE DARI REGISTRY, BUKAN
KARANGAN SENDIRI"*. Blok `dashboard-01` **tak punya** padanan pita ini — ia
membuka dengan H1 telanjang di atas latar halaman. Itu benar untuk kit yang
harus netral bagi ribuan proyek, dan salah untuk satu aplikasi internal yang
punya warna identitas.

Aturannya karena itu dijaga di tempat lain: **pita ini satu-satunya permukaan
berwarna penuh di seluruh aplikasi.** Begitu ada yang kedua, ia berhenti jadi
jangkar dan berubah jadi kebisingan — dan seluruh sisa dasbor kembali rata.
Alasan ini tertulis juga di kepala `PitaSambutan.tsx`, sesuai syarat §13 untuk
penyimpangan.

**Saat approve, CLAUDE.md §13 wajib ikut direvisi** — bersama dua penyimpangan
yang sudah tercatat lebih dulu (`collapsible="icon"` di §7, dan `lucide-react`
→ hugeicons di §5).

#### Yang TIDAK dikerjakan, dan kapan menambahkannya

Sparkline per KPI dan batang umur di "Perlu Tindakan" (opsi "kokpit") **tidak**
dipasang: keduanya butuh deret tren per-kartu yang server SEKARANG BELUM
menghitungnya. Menambahkannya berarti pekerjaan PHP + query baru, bukan TSX —
kerjakan bila pemilik memang mau kekayaan data, jangan diselundupkan sebagai
hiasan. Badge tren "+12.5%" bawaan blok `dashboard-01` tetap **haram**: angka
tren palsu lebih buruk daripada tak ada angka.

---

## 9. Arketipe tabel — `V2/Documents/Index`

Satu halaman ini menentukan bentuk **20+ daftar lain**: `Users/*` · `Log/*` · `Audit` · `Review/*` · `Approvals` · `Informasi/Index` · `Nonaktif` · `JobExecutions` · `Akses` · `Documents/{Published,Obsolete,StaffStatus,Distribution,Revisions}`.

- **`PenyaringDokumen` masuk ke dalam `CardHeader` tabel.** Sekarang ia kartu terpisah di atas kartu tabel — dua kartu bertumpuk untuk satu daftar. Jadi satu kartu: kotak cari di kiri (`input-group` + ikon + `kbd`), `Select` di kanan, Reset ghost. Semua field bertahan (`pilih` / `teks` / `tanggal` native), termasuk konvensi `'*'` untuk nilai kosong (Radix Select menolak string kosong).
- **`DataTable` V2:** `thead` lengket, `tabular-nums` untuk nomor & tanggal, baris hover, `perluas()` (baris ekspansi `Obsolete`) dipertahankan, `JudulUrut` tetap menulis `sort`/`dir` ke query string, tetap mempertahankan query lain, dan tetap **membuang `page`**. Tetap **bukan** TanStack — sorting & paging milik server.
- **Empty state** memakai komponen `empty` resmi, bukan rakitan sendiri.
- **`Paginasi`** memakai `pagination`, tetap membaca `links`/`from`/`to`/`total` Laravel apa adanya, tetap menyembunyikan diri bila `links.length <= 3`, `«`/`»` tetap string biasa (bukan `dangerouslySetInnerHTML`).
- **Aksi baris:** dua tombol datar + `StripAksi`. Urutan kolom **Status + Aksi + Lihat PDF** konsisten (CLAUDE.md §13).
- **Pola `ConfirmDialog` terkendali dipertahankan.** Radix melepas isi menu saat item dipilih, jadi dialog yang dipicu dari dalam `StripAksi` **wajib** mode `buka`+`onUbahBuka` dan dirender **di luar** menunya. Ini bug yang sudah pernah dibayar; jangan diulang. Begitu juga pola "formulir diisi dulu, konfirmasi menyusul saat submit" — tombol tetap `type="submit"` supaya `required`/`minLength` peramban tetap hidup.

### 9b. `V2/Auth/Login`

`login-02` di gaya maia, `layouts/V2/AuthLayout.tsx` terpisah. Foto kolom kanan `/soft-ui/img/curved-images/curved6.jpg` **diganti** — referensi Soft UI sudah dibuang dari proyek (CLAUDE.md §13) dan menyisakan asetnya justru di halaman pertama yang dilihat orang itu tidak konsisten. `layouts/guest.blade.php` **tidak disentuh**.

> **Penggantinya diputuskan pemilik 2026-08-31: panel merek, tanpa foto.**
> Kolom kanan jadi gradasi `--primary` (`from-primary to-primary/70` — dua ujung
> dari SATU token, jadi nol hex di berkasnya) berisi logo PPA + satu kalimat
> identitas. Nol aset baru, nol unduhan, dan warnanya ikut tema alih-alih
> bertabrakan dengannya seperti foto ber-hex tetap. `curved6.jpg` sendiri tetap
> di tempatnya selama jendela pratinjau — halaman login LAMA masih memakainya.

---

## 10. Gerbang — semua wajib hijau

| gerbang | catatan |
|---|---|
| `npm run build` | glob `./pages/**/*.tsx` menangkap `V2/**` sendiri |
| `node_modules/.bin/tsc --noEmit` | — |
| `npm run uji-render` | `uji-render.tsx` harus sudah jadi cerminan `app.tsx` **termasuk Provider maia** (§4b) |
| `php artisan smartpro:uji-render` | **tambah opsi `--pratinjau`** yang menyalakan `session('ui_v2')`; jalankan **dua kali** (mati & nyala). Tanpa ini halaman V2 tak pernah benar-benar dirender, dan layar putih lolos ke pengguna — tiga gerbang di atas hanya memeriksa tipe & penyusunan modul. **Sejak T2 juga `--uri`** (boleh berulang) untuk rute BERPARAMETER — lihat §10b |
| `php artisan test` | **695 → 700**. +4 `PratinjauUiTest` (bendera mati → `Dashboard` · nyala → `V2/Dashboard` · nyala + halaman tanpa kembaran → komponen asli · route toggle butuh auth) +1 pengunci kunci ikon (§5). **Nol tes lama diubah, nol dihapus** |
| `md5sum -c C:/baseline-smartpro/mesin-cetak.md5` | 23 berkas harus lolos penuh — pekerjaan ini tak menyentuh mesin cetak |
| `php artisan smartpro:cetak-baseline` | 8 dokumen `sama` |

> Peringatan operasional dari `PROGRESS-SHADCN.md`: **jangan** menjalankan perintah yang merender Blade bersamaan dengan `php artisan test` (rebutan singgahan view di Windows).

**Hasil T1 — 2026-08-31, ketujuhnya hijau:**

| gerbang | hasil |
|---|---|
| `npm run build` | bersih, 1m34s |
| `tsc --noEmit` | nol galat |
| `npm run uji-render` | bersih |
| `smartpro:uji-render` (mati) | 29 halaman, nol galat — semuanya komponen lama |
| `smartpro:uji-render --pratinjau` | 29 halaman, nol galat — **3 jadi `V2/*`** (`V2/Dashboard`, `V2/Documents/Index`, `V2/Auth/Login`), 26 sisanya jatuh ke komponen aslinya |
| `php artisan test` | **700** (699 lulus + 1 dilewati), **nol tes lama diubah** |
| `md5sum -c mesin-cetak.md5` | 23/23 OK |
| `smartpro:cetak-baseline` | 8 dokumen, semuanya `sama` |

Sapuan `--pratinjau` itulah bukti mekanismenya bekerja ujung ke ujung: satu
sapuan yang sama menunjukkan tiga halaman berganti komponen dan 26 lainnya
TIDAK — persis janji §3.

### 10b. `--uri` — menutup lubang rute berparameter (T2)

`smartpro:uji-render` menyusun daftarnya dari tabel rute dan **melewati setiap
rute ber-`{}`** — menebak id yang sah berarti perintah itu menyimpan
pengetahuan tentang data, dan pengetahuan itu akan basi. Konsekuensinya baru
menggigit di T2: `documents.edit` (`/documents/{document}/edit`) dan
`review.show` (`/review/{document}`) — dua halaman paling rumit dari seluruh
migrasi — tak pernah dirender gerbang mana pun.

`--uri` (boleh berulang) menambahkan alamat MENTAH ke sapuan. Yang tahu id mana
yang sah adalah orang yang menjalankan perintahnya, dan ia menyebutkannya di
baris perintah — jadi lubangnya tertutup tanpa pengetahuan itu masuk ke kode:

```powershell
php artisan smartpro:uji-render --pratinjau `
    --uri=/documents/1223/edit --uri=/documents/1227/edit --uri=/review/1178
```

URI yang diminta tapi tak menghasilkan payload **berteriak**, tidak diam. Rute
bernama boleh diam saat dilewati (ada ratusan, dan yang bukan Inertia memang
banyak); `--uri` cuma ada karena seseorang mengetiknya, jadi diam di situ
berarti ia mengira halamannya tersapu padahal id-nya salah, aksesnya ditolak,
atau rutenya berubah. Peringatan itu langsung berguna dua kali saat T2
dikerjakan: sekali menangkap Git Bash yang mengubah `/documents/…` jadi jalur
Windows (`MSYS_NO_PATHCONV`; pakai PowerShell saja), sekali lagi menangkap
`/documents/1226/edit` — dokumen FK **unggahan**, yang memang tak punya wizard.

> **Jalankan lewat PowerShell, bukan Git Bash.** Git Bash menerjemahkan argumen
> berawalan `/` jadi jalur Windows, dan `--uri=/review/1178` sampai ke PHP
> sebagai `C:/Program Files/Git/review/1178`.

### Lantai mutu yang tak boleh ditawar

- Fokus keyboard terlihat di setiap kendali (maia sudah membawa `focus-visible:ring-[3px]`; jangan ditimpa).
- `prefers-reduced-motion` dihormati oleh transisi kartu, rail sidebar, dan animasi grafik.
- Identitas deret di grafik **tidak pernah warna saja** — legenda selalu ada untuk ≥ 2 deret, angka ditulis pada deret yang penting.
- Teks memakai token teks, **bukan** warna deret.

### Catatan ukuran bundel

Chunk `Dashboard` sudah 508 KB (dicatat "bukan utang" di `PROGRESS-SHADCN.md` karena chunk per-halaman). Selama jendela pratinjau, entry chunk bertambah sedikit: `ui/tooltip` + `ui-maia/tooltip` keduanya ikut, plus `cmdk`. Halaman V2 adalah chunk terpisah, jadi halaman lama tidak membayarnya. Sesudah approve, `ui/` dan `lucide-react` hilang dan angkanya turun di bawah keadaan sekarang.

---

## 11. Berkas

### T3 — Dasbor V2, Fase 1–3 (2026-08-31)

Rinciannya di `docs/DASBOR-V2-REVISI.md` §0; di sini cuma daftar berkasnya.
T3 **membatalkan sebagian T2b** — lihat banner §8.

**Baru**

```
resources/js/components/v2/dasbor/KartuSambutan.tsx      Fase 2, salinan mekanis
resources/js/components/v2/dasbor/GrafikOverview.tsx     Fase 2, salinan mekanis
resources/js/components/v2/dasbor/MeterTertinjau.tsx     Fase 2, salinan mekanis
resources/js/components/v2/dasbor/LacakStatus.tsx        Fase 3 (§5b)
resources/js/components/v2/dasbor/PitaDepartemen.tsx     Fase 3 (§5e)
resources/js/components/v2/dasbor/PerformaPic.tsx        Fase 3 (§5d)
resources/js/components/v2/dasbor/LencanaDelta.tsx       Fase 3, satu panah utk §5c + §5d
resources/js/components/v2/dasbor/Sparkline.tsx          Fase 3, SVG polos (bukan recharts)
tests/Feature/DasborV2Test.php                           Fase 1, +13 tes → 715
```

**Disunting**

```
app/Http/Controllers/DashboardController.php   +alirAudit/sebaranDepartemen/performaPic
                                               +sejak/status; 'dokumen' => ['id'] (kebocoran §P4)
app/Services/DasborTampilan.php                -rona; +AKSI_ALIR/aksi/arah/deret/delta/kategori
resources/js/types/dasbor.d.ts                 -Tile.rona; +SebaranDepartemen/PerformaPic/Sorotan
resources/js/components/v2/AppSidebar.tsx      logo h-8 → h-12 (F2) → h-16 (F3)
resources/js/components/v2/SiteHeader.tsx      judul halaman KEMBALI ke topbar (F3)
resources/js/layouts/V2/AppLayout.tsx          prop `kepala` DICOPOT; blok H1 badan dicopot (F3)
resources/js/components/v2/dasbor/KartuStatistik.tsx    -rona; +lencana & sparkline
resources/js/components/v2/dasbor/KartuKetersediaan.tsx +Tabs agenda (§5a)
resources/js/components/v2/dasbor/KartuMasukan.tsx      ditulis ulang di atas ui-maia/item (§5f)
resources/js/components/v2/dasbor/KartuLog.tsx          garis waktu digambar ulang (§5g)
resources/js/components/v2/dasbor/TumpukanWajah.tsx     +2 prop OPSIONAL (satuan, ket)
resources/js/pages/V2/Dashboard.tsx                     disusun ulang mengikuti irama V1
docs/REDESAIN-UI-V2.md                                  berkas ini — §8/§8b digantikan
```

**Nol pengimpor, ditahan atas keputusan pemilik (K3) — belum dihapus:**
`v2/dasbor/{PitaSambutan,ArusDokumen,PerluTindakan}.tsx`

### T2 (2026-08-31)

**Baru**

```
resources/js/components/v2/dokumen/fields/**           (12, salinan mekanis)
                                                       konteks · index · Text ·
                                                       RichList · RichText ·
                                                       RepeatableGroup ·
                                                       DocumentPicker ·
                                                       ImageUpload · JsaAnalysis ·
                                                       PapanKetersediaan ·
                                                       UserPicker · RevisionLog
resources/js/components/v2/tinjau/**                   (4, salinan mekanis)
                                                       konteks · SeksiTinjau ·
                                                       PanelAi · DialogAlihkan
resources/js/pages/V2/Documents/Edit.tsx
resources/js/pages/V2/Documents/Create.tsx
resources/js/pages/V2/Review/Show.tsx
```

**Disunting**

```
app/Console/Commands/UjiRender.php            +opsi --uri (§10b)
tests/Feature/PratinjauUiTest.php             +2 tes → 702
docs/REDESAIN-UI-V2.md                        berkas ini
```

### T2b (2026-08-31) — koreksi dasbor, §8b

**Baru**

```
resources/js/components/v2/dasbor/PitaSambutan.tsx
```

**Disunting**

```
app/Services/DasborTampilan.php               +kunci `rona` per kartu KPI
resources/js/types/dasbor.d.ts                +Tile.rona
resources/js/layouts/V2/AppLayout.tsx         +prop opsional `kepala`
resources/js/components/v2/dasbor/KartuStatistik.tsx   aksen rona
resources/js/pages/V2/Dashboard.tsx           memakai `kepala`
```

`AppLayout` adalah satu-satunya berkas BERSAMA yang tersentuh, dan `kepala`
opsional: tujuh halaman V2 lain tak berubah sebyte pun keluarannya.

Enam belas berkas komponen itu **salinan mekanis** — dibuat dengan `cp` lalu
penulisan ulang impor, bukan diketik ulang. Tiap berkas dibuka penanda 12 baris
"KEMBARAN MAIA dari …" yang menyebut asalnya dan mengingatkan bahwa perbaikan
perilaku di satu sisi wajib ikut ke sisi lain sampai jendela pratinjau ditutup.

Karena itu tiap pasangan bisa dibandingkan dan hasilnya tetap terbaca sebagai
daftar tukar impor, bukan sebagai dua implementasi:

```bash
cd resources/js/components
for f in dokumen/fields/*.tsx tinjau/*.tsx; do
  printf '%-40s %3s\n' "$f" "$(diff --strip-trailing-cr <(tail -n +13 "v2/$f") "$f" | grep -c '^[<>]')"
done
```

Hasilnya 0–32 baris per berkas, seluruhnya baris impor + baris ikon; `index.tsx`
dan `fields/konteks.tsx` nol. Dua bendera itu WAJIB: `tail -n +13` melewati
penanda, dan `--strip-trailing-cr` melewati akhiran baris — tiga berkas asli
(`PapanKetersediaan`, `RichText`, `SeksiTinjau`) ber-CRLF di working tree
sementara salinannya LF, dan tanpa bendera itu diff-nya menyala 700–800 baris
tanpa satu pun perbedaan sungguhan. Isi yang di-commit sendiri identik: repo
ini ber-`.gitattributes` `* text=auto eol=lf`.

### T1 (2026-08-31)

**Baru**

```
app/Inertia/PratinjauResponseFactory.php
resources/js/components/ui-maia/**                       (42, hasil generate CLI)
resources/js/components/v2/Ikon.tsx
resources/js/components/v2/{AppSidebar,SiteHeader,NavUser,PencarianMenu,SakelarPratinjau}.tsx
resources/js/components/v2/{StatusBadge,DataTable,PenyaringDokumen,StripAksi,NomorDokumen}.tsx
resources/js/components/v2/{Avatar,ConfirmDialog,DialogAntrean}.tsx
resources/js/components/v2/dasbor/**                     (8: ArusDokumen, KartuStatistik,
                                                         PerluTindakan, KartuSebaran,
                                                         KartuDistribusi, KartuKetersediaan,
                                                         KartuLog, KartuMasukan
                                                         + PitaCakupan, TumpukanWajah)
resources/js/layouts/V2/{AppLayout,AuthLayout}.tsx
resources/js/pages/V2/Dashboard.tsx
resources/js/pages/V2/Documents/Index.tsx
resources/js/pages/V2/Auth/Login.tsx
tests/Feature/PratinjauUiTest.php
docs/REDESAIN-UI-V2.md                                   (berkas ini)
```

**Disunting**

```
app/Providers/AppServiceProvider.php          bind factory
app/Http/Middleware/HandleInertiaRequests.php +prop uiV2
app/Console/Commands/UjiRender.php            +opsi --pratinjau
routes/web.php                                +route toggle
resources/js/app.tsx                          Provider bersarang
resources/js/uji-render.tsx                   cerminannya
resources/js/components/SiteHeader.tsx        +sakelar pratinjau (pintu masuk)
resources/css/app.css                         +blok .ui-v2
package.json                                  +@hugeicons/react +@hugeicons/core-free-icons +cmdk
components.json                               diubah sementara, dikembalikan
```

---

## 12. Verifikasi ujung ke ujung

1. `php artisan serve`; masuk sebagai **GL** (pembuat), **SH** (peninjau), **PJO** (penyetuju), **Non-Staff** (read-only), **Admin IT** (61 tautan).
2. Tiap peran: buka `/dashboard`, nyalakan sakelar pratinjau, lalu **matikan lagi** untuk membandingkan langsung. Periksa tak ada widget yang hilang dan tak ada lubang di grid.
3. Sidebar: ciutkan → rel ikon + tooltip; ⌘K → cari "Audit" / "JSA"; arahkan ke item terkunci → tooltip berisi alasan dari server.
4. `/documents` V2: cari, saring per dept/jenis/status, urutkan kolom, ganti halaman, buka `StripAksi` → `ConfirmDialog` harus muncul dan **tidak lenyap**.
5. **T2 — wizard** (`/documents/{id}/edit` sebagai GL, dokumen berstatus
   `draft`): isi satu bab, tunggu "Tersimpan otomatis" menyala, tekan Preview
   dan pastikan HANYA iframe yang berganti (formulir tak reload, kursor tak
   lompat). Pindah langkah bolak-balik — isian langkah lama TIDAK boleh ikut
   terbawa. Pada bab bersakelar, matikan "Gunakan Flowchart": isian di dalamnya
   harus MEREDUP tapi tetap tersimpan, dan nomor bab di bawahnya bergeser.
   Tempel (Ctrl+V) sebuah tangkapan layar ke Deskripsi Aktivitas → ia harus
   berubah jadi berkas dan tetap ada di PDF. Pada draft revisi Tipe B, buka
   langkah **Log Revisi** dan tekan **Revisi**.
6. **T2 — papan ketersediaan** di langkah peninjau: sel pita harus ELASTIS
   (melebar mengikuti kartu, bukan terpatok), kandidat penuh/off terkunci, dan
   panah keyboard berpindah antar-kandidat.
7. **T2 — layar tinjau** (`/review/{id}` sebagai SH; lalu `/review-md/{id}`
   sebagai MD; lalu masukan sejawat sebagai GL): ketiganya harus digambar
   komponen yang SAMA dengan tombol yang berbeda. Pada JSA, tandai ✓/✗ tiap
   pengendalian — tombol Kirim baru hidup setelah semuanya ditandai, dan
   "Semua Sesuai" mengisi satu bahaya sekaligus. Komentari satu foto lampiran.
   Bila ada kandidat pengalihan: buka **Alihkan**, papannya harus muncul di
   dalam dialog dan `ConfirmDialog`-nya **tidak lenyap**.
8. Mode gelap di kedelapan halaman V2 (butir 8c `CEKLIS-MATA-PER-PERAN.md`).
9. Jalankan delapan gerbang §10 — dua di antaranya `smartpro:uji-render`
   (mati & nyala), keduanya dengan `--uri` (§10b).

---

## 13. Progres

| tranche | isi | status |
|---|---|---|
| **T1** | mekanisme pratinjau · `ui-maia` · ikon hugeicons · palet grafik · kerangka (layout/sidebar/header) · dasbor · `Documents/Index` · `Auth/Login` | `[x]` **selesai 2026-08-31** — rincian di bawah |
| **T2** | `Documents/Edit` (wizard) · `Documents/Create` · `Review/Show` · pohon `v2/dokumen/fields` (12) & `v2/tinjau` (4) · `--uri` | `[x]` **selesai 2026-08-31** — rincian di bawah |
| **T2b** | koreksi pemilik atas dasbor: pita merek + KPI berwarna (§8b) | `[x]` selesai 2026-08-31 — ⛔ **kemudian DIBATALKAN T3**, lihat banner §8 |
| **T3** | dasbor V2 dirombak ulang: balik ke tata letak V1 + enam widget baru + lencana/sparkline berdata nyata. Rencana & statusnya di `docs/DASBOR-V2-REVISI.md` | Fase 1–3 `[x]` **selesai 2026-08-31** · Fase 4 (pemeriksaan mata §9c) `[ ]` |
| **V3** | 12 permintaan pemilik: enam bug akar tunggal (V1+V2) · dasbor per peran · tabel Lacak Status · kalender · ⌘K dicabut · halaman login. Rencana & statusnya di `docs/REVISI-UI-V3.md` | Fase 1 · 2a · 2b `[x]` **selesai 2026-09-01** |
| **T3.5** | 35 halaman + 9 komponen sisa dikembarkan ke V2, lalu Tranche 4. Patokan angka + urutan fase di **`docs/PATOKAN-GAYA-V2.md`** | Fase 0 `[x]` **selesai 2026-09-01** · Fase 1–7 `[ ]` |

#### T3 — rincian (Fase 1–3 selesai 2026-08-31)

| fase | isi | status |
|---|---|---|
| 1 | server (`alirAudit`, `sebaranDepartemen`, `performaPic`, `AKSI_ALIR`) · tipe · `rona` dicabut · +13 `DasborV2Test` → **715** | `[x]` |
| 2 | tiga kembaran mekanis (`KartuSambutan`/`GrafikOverview`/`MeterTertinjau`) · logo diperbesar · halaman disusun ulang | `[x]` |
| 3 | enam widget §5a–§5g · `LencanaDelta` + `Sparkline` · judul ke topbar · logo diperbesar lagi · utang komentar `kepala` + K1 | `[x]` |
| 4 | pemeriksaan mata per peran (§9c) — **tugas pemilik, bukan gerbang otomatis** | `[ ]` |

Gerbang sesudah Fase 3: `build` bersih · `tsc` nol galat · `uji-render` bersih ·
sapuan `--pratinjau` 28 halaman nol galat · `php artisan test` **715**
(714 lulus + 1 dilewati, sama persis dengan Fase 1–2) · md5 cetak 23/23 ·
`cetak-baseline` 8/8 identik · `git diff components.json` kosong.

#### T2b — rincian (selesai 2026-08-31)

| # | butir | status |
|---|---|---|
| 1 | `DasborTampilan::tile()` +kunci `rona`; delapan kartu diberi ronanya | `[x]` |
| 2 | `types/dasbor.d.ts` — `Tile.rona` | `[x]` |
| 3 | `layouts/V2/AppLayout.tsx` — prop opsional `kepala` | `[x]` |
| 4 | `v2/dasbor/PitaSambutan.tsx` (baru) | `[x]` |
| 5 | `v2/dasbor/KartuStatistik.tsx` — aksen `rona` | `[x]` |
| 6 | gerbang hijau | `[x]` — `tsc` nol galat · `build` bersih · `uji-render` bersih · sapuan `--pratinjau` **31 halaman nol galat** · `php artisan test` **702** · md5 cetak 23/23 |

Nol hex di kedua berkas TSX baru — diperiksa `grep`, bukan diyakini. Kedelapan
kelas opasitas non-standar (`bg-foreground/8`, `bg-chart-5/12`,
`bg-primary-foreground/15`, …) dipastikan BENAR-BENAR dipancarkan Tailwind
dengan mencarinya di `public/build/assets/app-*.css` — kelas yang didiamkan
Tailwind gagal secara diam-diam: tak ada galat, elemennya cuma tak berwarna.

#### T2 — rincian (selesai 2026-08-31)

| # | butir | status |
|---|---|---|
| 1 | `components/v2/dokumen/fields/**` — 12 berkas, salinan mekanis | `[x]` |
| 2 | `components/v2/tinjau/**` — 4 berkas, salinan mekanis | `[x]` |
| 3 | `pages/V2/Documents/Edit.tsx` — wizard 2 langkah + iframe PDF + `PapanKetersediaan` + langkah Log Revisi | `[x]` |
| 4 | `pages/V2/Documents/Create.tsx` — keputusan pemilik, di luar §13 lama | `[x]` |
| 5 | `pages/V2/Review/Show.tsx` — satu komponen, tiga rute | `[x]` |
| 6 | `smartpro:uji-render --uri` (§10b) | `[x]` |
| 7 | `PratinjauUiTest` +2 tes — 700 → **702** | `[x]` |
| 8 | delapan gerbang §10 hijau | `[x]` — tabel di bawah |

**Hasil gerbang T2 — 2026-08-31:**

| gerbang | hasil |
|---|---|
| `npm run build` | bersih, 1m34s |
| `tsc --noEmit` | nol galat |
| `npm run uji-render` | bersih |
| `smartpro:uji-render` + 3 `--uri` (mati) | **32** halaman, nol galat — semuanya komponen lama |
| `smartpro:uji-render --pratinjau` + 3 `--uri` | **32** halaman, nol galat — **7 jadi `V2/*`** (`Dashboard`, `Documents/{Index,Create,Edit}`, `Review/Show`, `Auth/Login`; `Edit` dua kali, dokumen biasa & dokumen ber-Log Revisi), 25 sisanya jatuh ke komponen aslinya |
| `php artisan test` | **702** (701 lulus + 1 dilewati), **nol tes lama diubah** |
| `md5sum -c mesin-cetak.md5` | 23/23 OK |
| `smartpro:cetak-baseline` | 8 dokumen, semuanya `sama` |

Sapuan `--pratinjau` T2 memberi bukti yang tak dipunyai T1: `V2/Documents/Edit`
dan `V2/Review/Show` — dua halaman yang paling mungkin berakhir layar putih —
BENAR-BENAR dirender, bukan cuma lolos pemeriksaan tipe.

#### Penyimpangan T2 dari rencana — supaya tak lolos diam-diam

| # | rencana | yang dikerjakan | alasan |
|---|---|---|---|
| Q1 | T2 = `Documents/Edit` + `Review/Show` | + `Documents/Create` | keputusan pemilik 2026-08-31. Ia tak menambah satu berkas komponen pun (Button/Card/Input/Label/Select/Switch sudah ada), menutup lompatan nova→maia di tengah pembuatan dokumen, dan rutenya tanpa parameter sehingga ikut tersapu `--pratinjau` cuma-cuma |
| Q2 | (tak diatur) | kartu kop & kartu stepper **dibubarkan** ke blok judul layout | keputusan pemilik 2026-08-31 ("larut ke blok judul"), sejalan dengan pembubaran `KartuSambutan` di dasbor. Versi nova memakai dua kartu penuh sebelum isian pertama kelihatan; di layar 768 px isian itu sudah di bawah lipatan sebelum sebaris pun diketik. Nol informasi hilang — nomor, lencana "sementara", jenis, dept, status, pembuat, No. Revisi semuanya masih tercetak |
| Q3 | mockup menaruh `[Preview][PDF]` di `aksi` | hanya **PDF** yang naik | Preview bukan aksi HALAMAN melainkan aksi FORMULIR — ia menyimpan isian dulu lewat `simpanDiam()`, lalu menyegarkan iframe, dan keadaannya hidup di `<FormLangkah>`. Menaikkannya berarti menyalin `simpanDiam()` ke dua tempat yang harus sepakat selamanya. Ia tetap duduk bersama Simpan & Kirim di kaki formulir |
| Q4 | — | `smartpro:uji-render --uri` | tanpa itu dua halaman T2 luput dari SELURUH gerbang (§10b) |
| Q5 | — | H1 `Review/Show` = "Tinjau: {judul dokumen}" | layout memakai SATU string untuk H1 dan `<title>` tab; tab yang cuma berbunyi judul dokumen tak memberi tahu peninjau — yang sering membuka beberapa dokumen sekaligus — sedang apa ia di sana |
| Q6 | — | remah pertama `Review/Show` memakai `backLabel` APA ADANYA | kalimatnya milik server (tiga controller berbeda). Menukarnya jadi nama tempat karangan berarti tiga layar yang harus disepakati ulang di TSX — persis yang dihindari pakem P3 |
| Q7 | — | `PratinjauUiTest` menjaga **daftar berkas** kembaran, bukan isinya | seseorang menambah `fields/Sesuatu.tsx` untuk tipe seksi baru lalu lupa `v2/`; halaman lama tetap benar, halaman V2 diam-diam kehilangan satu tipe seksi, dan `tsc` tak bisa melihatnya karena `v2/.../index.tsx` berkas TERPISAH yang memang tak menyebutnya |

#### Yang TIDAK ikut berubah di T2, dan itu disengaja

- `components/dokumen/**`, `components/tinjau/**`, `components/ui/**` — `git
  status` sesudah seluruh pekerjaan T2 tak menyebut satu pun dari ketiganya.
- **Nol baris logika bergeser** di keenam belas salinan. Autosave 1200 ms,
  `key` per langkah, panel pratinjau di LUAR pembungkus ber-`key` itu, kunci
  baris stabil di `RepeatableGroup` (Quill memegang DOM-nya sendiri), `inert`
  alih-alih `disabled` pada bab yang dimatikan, radio ASLI di
  `PapanPilihPeninjau` & `TombolVerdict`, `import()` dinamis Quill, penjaring
  foto tempelan base64, dan bentuk `item_ref` `L0-B1-P2` — semuanya bertahan
  karena memang tak disentuh.
- Nol logika izin/status/kandidat pindah ke TSX. `statusMeta`, `statusLabels`,
  `candidates`, `ketersediaan`, `papan`, `ambang`, `tipeDitinjau`, `petunjuk`,
  `backLabel`, `labelTolak`/`labelLolos`, `alihKandidat` — semuanya tetap
  dibaca dari props.
- Mesin cetak: 23/23 md5 OK, 8 PDF `sama`.

#### Catatan teknis T2 — jebakan `perl` yang sempat menggigit

Salinan pertama dibuat dengan `perl -0pi` berpola ber-karakter lebar (`§`,
`—`, `→`). Tanpa lapisan UTF-8, Perl membaca berkas sebagai byte, menaikkan
`$_` ke semantik UTF-8 begitu polanya ber-karakter lebar, lalu **menulis
ulang seluruh berkas dalam UTF-8** — sehingga tiap `§` yang SUDAH UTF-8
(`C2 A7`) keluar sebagai `C3 82 C2 A7` alias `Â§`. Enam belas berkas rusak
diam-diam; `tsc` tak peduli, dan yang berubah cuma komentar.

Salinannya diulang dari nol dengan `perl -CSD`, dan hasilnya diperiksa dengan
pemindai byte (`\xC3\x82` dan `\xC3\xA2\xC2\x80`), bukan dengan mata. Dicatat
di sini karena pekerjaan salin-tukar-impor semacam ini akan berulang di T3–T4.

#### T1 — rincian (selesai 2026-08-31)

| # | butir | status |
|---|---|---|
| 1 | npm: `@hugeicons/react@1.1.10` · `@hugeicons/core-free-icons@4.3.0` · `cmdk@1.1.1` | `[x]` |
| 2 | `components/ui-maia/**` — 42 berkas maia+hugeicons, `components.json` dikembalikan | `[x]` |
| 3 | empat jebakan §4 diperiksa | `[x]` — catatan di bawah |
| 4 | `PratinjauResponseFactory` + binding + route toggle + prop `uiV2` | `[x]` |
| 5 | Provider maia bersarang di `app.tsx` **dan** `uji-render.tsx` | `[x]` |
| 6 | palet grafik `.ui-v2` — divalidasi ulang, **usulan §6 lama ditolak** | `[x]` |
| 7 | `components/v2/Ikon.tsx` (84 kunci) + penjaga tes | `[x]` |
| 8 | kerangka: `layouts/V2/AppLayout` · `v2/{AppSidebar,SiteHeader,NavUser,PencarianMenu,SakelarPratinjau}` | `[x]` |
| 9 | `pages/V2/Dashboard.tsx` + 8 widget di `v2/dasbor/**` | `[x]` |
| 10 | `pages/V2/Documents/Index.tsx` + `v2/{DataTable,PenyaringDokumen,StatusBadge,StripAksi,NomorDokumen}` | `[x]` |
| 11 | `pages/V2/Auth/Login.tsx` + `layouts/V2/AuthLayout.tsx` | `[x]` |
| 12 | sakelar pratinjau di `components/SiteHeader.tsx` LAMA (pintu masuk) | `[x]` |
| 13 | `smartpro:uji-render --pratinjau` | `[x]` |
| 14 | `tests/Feature/PratinjauUiTest.php` — 695 → **700** | `[x]` |
| 15 | tujuh gerbang §10 hijau | `[x]` — tabel hasil di §10 |

**Hasil pemeriksaan empat jebakan §4:**

1. **`icon-placeholder` TIDAK muncul.** Ia memang ada di `sidebar.json` mentah
   di registry (`curl` membuktikannya), tetapi CLI membuangnya sendiri saat
   menulis berkas. Nol suntingan diperlukan.
2. **Alias bersama TIDAK tertimpa.** `git status` sesudah generate hanya
   menyebut `ui-maia/` — `hooks/use-mobile.ts` dilewati CLI ("files might be
   identical"), dan `lib/utils.ts` beserta `resources/css/app.css` tak bergerak
   sebyte pun. Isolasi alias tidak perlu.
3. **`tsconfig` `@/*` → `resources/js/*`** sudah benar.
4. **`--radius-4xl` sudah ada** di `@theme inline` (`app.css:55`); 14 berkas
   maia memakai `rounded-4xl`.

**Cacat generate yang MUNCUL, di luar keempatnya:** `spinner.tsx` menyatakan
propsnya `React.ComponentProps<"svg">`, yang mendeklarasi
`strokeWidth?: string | number`; `HugeiconsIcon` hanya menerima `number`,
sehingga `tsc --noEmit` merah. Diperbaiki dengan `Omit<…, "strokeWidth">` —
satu kunci, beralasan tertulis di berkasnya, sesuai izin §4.1 untuk cacat
generate yang membuat build gagal. **Itu satu-satunya berkas hasil generate
yang disunting tangan.**

#### Penyimpangan T1 dari rencana — supaya tak lolos diam-diam

| # | rencana | yang dikerjakan | alasan |
|---|---|---|---|
| P1 | palet §6 (sian/oker/ungu/hijau) | palet baru (teal/jingga/ungu-biru/hijau) | usulan gagal validator: deutan 4.1, jauh di bawah Okabe–Ito 12.5. Rinciannya di §6 |
| P2 | `44` berkas `ui-maia` | **42** | proyek ini punya 32 komponen, bukan 34 yang ditulis rencana; +10 komponen kit = 42 |
| P3 | `v2/Ikon.tsx` "85 entri" | **84** | jumlah sebenarnya di `components/Ikon.tsx`; penjaganya membandingkan HIMPUNAN, bukan angka, jadi ia tak bisa salah hitung lagi |
| P4 | caret/tema/cari lewat `v2/Ikon.tsx` | impor hugeicons LANGSUNG | peta `bi-*` harus cerminan konstanta PHP; kendali antarmuka murni tak pernah punya nama `bi-*`, dan menaruhnya di sana mengotori daftar yang justru dijaga tes |
| P5 | `MeterTertinjau` jadi rail persen | rail persen + `Progress`, busur radial DIBUANG | satu angka persen tak butuh seperempat kartu; ruangnya jadi milik grafik |
| P6 | `TumpukanWajah` `hover-card` nama | + daftar LENGKAP termasuk yang di balik "+N" | tanpa foto, empat glif orang identik tak menjawab "siapa yang sudah membaca" |
| P7 | — | `PenyaringDokumen` dapat jalan pintas `/` | kotak cari di daftar panjang; `Kbd` di ujung kanan membuatnya ketahuan |
| P8 | — | tombol Reset hanya muncul bila ada yang bisa direset | tombol yang tak mengubah apa pun cuma mengundang orang mengujinya |

#### Yang TIDAK ikut berubah, dan itu disengaja

- `components/ui/**` (nova), 41 halaman lama, `components/Ikon.tsx` (lucide) —
  seluruhnya utuh; satu-satunya suntingan atas kerangka lama adalah **satu
  tombol** sakelar pratinjau di `components/SiteHeader.tsx`.
- `KartuKetersediaan` disalin dengan **nol baris logika bergeser** — hanya
  `ui` → `ui-maia` dan lucide → hugeicons. Ketiga perilaku wajibnya (navigasi
  bulan lewat `<Link>` `?bulan=`, klik tanggal membuka dialog terisi, tanggal
  lewat mati, `tanggalLokal` bukan `new Date('Y-m-d')`) bertahan karena
  memang tak disentuh.
- Nol logika izin/status/kandidat pindah ke TSX. `statusMeta`, `navigation`,
  `hero`, `tiles`, `bolehBukaHalaman`, `tingkat` umur dokumen — semuanya tetap
  dibaca dari props.
- Mesin cetak: 23/23 md5 OK, 8 PDF `sama`.

---

**K7 (kartu mana di kolom mana, per peran)** — pemilik memutuskan 2026-08-31:
**pakai usulan §8 apa adanya untuk T1.** Larik deskriptor `{kunci, span, node}`
di `pages/V2/Dashboard.tsx` dibuat justru supaya keputusan itu tetap murah:
menukar urutan berarti menggeser satu elemen larik, bukan menulis ulang JSX.

**Berikutnya — T3:** sisa daftar dan layar detail. Yang paling berdampak lebih
dulu, karena bentuknya sudah ditentukan arketipe `Documents/Index` (§9) dan
tinggal disalin: `Documents/{Show,Published,Obsolete,StaffStatus,Distribution,
Revisions}` · `Review/{Index,Md}` · `Approvals/Index` · `Users/*` · `Log/*` ·
`Audit` · `Informasi/*` · `Nonaktif` · `JobExecutions` · `Akses` ·
`Pengaturan/*`. Sisanya 34 halaman.

Dua hal yang sudah diketahui akan dibutuhkan T3, dicatat sekarang supaya tak
ditemukan ulang:

1. **`components/v2/dokumen/{DialogAksi,DaftarMasukan,RincianPembaca}.tsx`**
   belum ada — T2 hanya menyalin pohon `fields/` dan `tinjau/`. Ketiganya
   dipakai `Documents/{Show,Published,Distribution}`. Penjaga daftar berkas di
   `PratinjauUiTest` sengaja BELUM mencakup `components/dokumen/*.tsx` di akar
   pohonnya; tambahkan pohon itu ke `test_tiap_komponen_wizard_dan_tinjau_…`
   begitu salinannya dibuat.
2. **`--uri`** sudah ada, jadi tiap halaman T3 yang berparameter
   (`documents.show`, `informasi.show`, …) bisa langsung ikut disapu.

Seperti T1, T2 menunggu koreksi pemilik lebih dulu — itulah gunanya sakelar
pratinjau. Bandingkan berdampingan: buka wizard & layar tinjau, nyalakan
sakelar, lalu **matikan lagi**.
