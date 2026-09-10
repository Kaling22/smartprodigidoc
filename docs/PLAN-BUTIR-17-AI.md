# BUTIR 17 — Optimalkan Konfigurasi AI Audit

> Turunan dari `docs/PLAN-BUTIR-11-17.md`. Dikerjakan sesudah butir 16.
> Tujuan user: *"agar bisa menyesuaikan dan tahu apa yang perlu di ulik dari
> masing masing dokumen mutu"*.

---

## 1. Kondisi sekarang

Berkas: `app/Services/Ai/` — `AiReviewerInterface`, `AbstractAiReviewer`,
`GeminiReviewer`, `OpenRouterReviewer`, `NullReviewer`.
Binding di `AppServiceProvider.php:24-39`; konfigurasi di `config/services.php`
(`services.ai.provider`, `services.ai.enabled`). `.env` aktif:
`AI_PROVIDER=openrouter`, model `openai/gpt-4o-mini`.

Dua fokus audit:

- `FOKUS_SUBSTANSI` — dipakai SH/DH lewat `ReviewController@aiAnalyze`
  (`POST review/{document}/ai`). Persona dibaca dari berkas
  `docs/ai/Instruksi AI.md`.
- `FOKUS_PENULISAN` — dipakai MD lewat `MdReviewController@aiAnalyze`
  (`POST review-md/{document}/ai`), butuh `users.ai_review_enabled`.
  Persona hardcoded di `personaPenulisan()`.

Prompt dirakit di `AbstractAiReviewer::buildPrompt()`, hanya mengirim section
bertipe `rich_list`, `reference_picker`, `repeatable_group`, `jsa_analysis`.
Kunci section diambil dari `SchemaService::for($document->type)->allSections()`
dan dijadikan whitelist `section_key` — bagus, temuan AI tidak bisa menunjuk
bagian yang tidak ada.

Kontrak keluaran:
```json
{"summary":"…","findings":[{"section_key":"…","severity":"info|minor|major|critical","issue":"…","suggestion":"…"}]}
```

Kegagalan tidak pernah dilempar ke pengguna — `review()` menangkap `\Throwable`,
`Log::warning`, dan mengembalikan ringkasan yang menyuruh peninjau melanjutkan
manual. **Pertahankan sifat ini.**

---

## 2. Tiga masalah

### 2.1 Rubrik per jenis terlalu tipis (masalah utama)

Seluruh pembedaan antar jenis ada di satu `match` di `typeContext()`, isinya
1–2 kalimat per jenis. Contohnya untuk SP hanya "seperti SOP dgn penekanan
standar/parameter hasil produksi". Model tidak punya daftar periksa, jadi
temuannya generik ("penjelasan kurang rinci") alih-alih spesifik ("langkah 3
tidak menyebut PIC").

### 2.2 Audit foto lampiran adalah teks mati

`docs/ai/Instruksi AI.md` memuat bagian "Audit Lampiran Foto" yang menyuruh
model menilai foto. Tapi `AbstractAiReviewer::renderSection()` **membuang** nilai
yang diawali `lampiran/` dari prompt. Model diminta menilai gambar yang tidak
pernah ia terima — sumber halusinasi yang pasti.

### 2.3 Duplikat berkas instruksi

Ada `docs/ai/Instruksi AI.md` **dan** `docs/AI/Instruksi AI.md`. Di Windows
keduanya berebut nama yang sama; di Linux (produksi, case-sensitive) hanya yang
huruf kecil terbaca karena `auditorInstruction()` mencari
`docs/ai/Instruksi AI.md` lalu `docs/ai/AI.md`. Risiko nyata: mengedit berkas
yang tidak pernah dibaca.

---

## 3. Perbaikan

### 3.1 Pindahkan rubrik per jenis dari kode ke berkas

Buat empat berkas:

```
docs/ai/jenis/SOP.md
docs/ai/jenis/IK.md
docs/ai/jenis/SP.md
docs/ai/jenis/JSA.md
```

Ubah `AbstractAiReviewer::typeContext(string $code)`:

```php
private function typeContext(string $code): string
{
    static $cache = [];

    if (! array_key_exists($code, $cache)) {
        $path = base_path('docs/ai/jenis/'.$code.'.md');
        $cache[$code] = is_file($path) ? trim(file_get_contents($path)) : '';
    }

    return $cache[$code] !== '' ? $cache[$code] : $this->typeContextBawaan($code);
}
```

`typeContextBawaan()` = isi `match` yang sekarang, **dipertahankan sebagai
fallback**. Alasannya bukan kehati-hatian berlebihan: `docs/` bisa saja tidak
ikut ter-deploy, dan audit AI tidak boleh berubah jadi prompt kosong tanpa ada
yang sadar. Cache statis per-request, tanpa dependensi baru.

Konsekuensi yang diinginkan: **menyetel AI = mengedit berkas Markdown**, bukan
mengubah PHP dan deploy ulang. Menambah jenis dokumen baru = menambah satu
berkas — sejalan dengan prinsip schema-driven di CLAUDE.md §7.

### 3.2 Isi tiap berkas rubrik

Kerangka yang sama untuk keempatnya:

```markdown
# <JENIS> — <Nama Panjang>

## Apa dokumen ini
<2-3 kalimat>

## Bagian wajib
<daftar section_key + apa yang seharusnya ada di dalamnya>

## Daftar periksa audit
<butir-butir konkret yang bisa dijawab ya/tidak dari isi dokumen>

## Contoh temuan per tingkat keparahan
critical: …
major: …
minor: …
info: …
```

Materi rubrik per jenis (diambil dari schema di `DocumentTypeSeeder` +
`docs/ai/Instruksi AI.md` yang sudah ada):

**SOP** — section `tujuan`, `ruang_lingkup`, `referensi`, `definisi`,
`flowchart`, `aktivitas`, `lampiran`.
- Setiap butir `aktivitas` wajib punya **PIC**; langkah tanpa PIC = `major`.
- `aktivitas` harus berurutan logis; langkah yang mengacu hasil langkah
  sesudahnya = `major`.
- `referensi` harus menyebut klausul ISO/SMKP yang benar-benar relevan dengan
  isi, bukan daftar tempel; referensi generik = `minor`.
- Istilah teknis yang muncul di `aktivitas` tapi tak ada di `definisi` = `minor`.
- `tujuan` dan `ruang_lingkup` tidak boleh saling menyalin = `minor`.
- `flowchart` ada tapi tidak sejalan dengan urutan `aktivitas` = `major`.

**IK** — hanya `aktivitas` (schema IK memang ringkas: satu step, langsung ke
pengesahan).
- IK adalah petunjuk **satu pekerjaan**, harus lebih rinci daripada SOP;
  langkah setingkat SOP ("lakukan pemeriksaan") = `major`.
- Alat, bahan, dan parameter (torsi, tekanan, suhu, durasi) harus bernilai
  konkret; parameter tanpa angka dan satuan = `major`.
- Hasil yang diharapkan tiap langkah harus terbaca; tidak ada = `minor`.
- Setiap langkah wajib punya PIC = `major` bila kosong.

**SP** — Standar Parameter, struktur seperti SOP tanpa flowchart.
- Titik berat: **parameter dan nilai standarnya**. Parameter tanpa nilai
  batas (min/maks/target) dan satuan = `critical`.
- Harus jelas apa tindakan bila parameter di luar batas; tidak ada = `major`.
- Metode/alat ukur harus disebut; tidak ada = `minor`.
- Sisanya mengikuti daftar periksa SOP.

**JSA** — `lokasi_kerja`, `apd`, `tools`, `analisa` (`jsa_analysis`:
langkah → bahaya/risiko → pengendalian[]).
- Setiap `bahaya` wajib punya **minimal satu** `pengendalian`; kalau tidak =
  `critical`.
- **Hirarki kontrol** (eliminasi → substitusi → rekayasa → administrasi → APD):
  bahaya berisiko tinggi yang pengendaliannya **hanya** "gunakan APD" = `major`.
  Sebutkan di `suggestion` tingkat hirarki mana yang belum dipertimbangkan.
- APD yang disebut di dalam `analisa` tapi tidak ada di daftar `apd` = `minor`
  (dan sebaliknya).
- Alat yang dipakai di langkah kerja tapi tidak ada di `tools` = `minor`.
- Langkah kerja yang tidak punya bahaya sama sekali patut dicurigai = `info`,
  kecuali langkahnya memang administratif.
- `lokasi_kerja` kosong atau generik ("area kerja") = `minor`.

### 3.3 Bereskan lampiran (jangan tinggalkan instruksi yang bohong)

Pilih satu, **jangan biarkan seperti sekarang**. Yang dikerjakan: opsi jujur.

- `renderSection()`: ganti pembuangan `lampiran/...` dengan **metadata** yang
  memang dimiliki sistem — judul lampiran, keterangan, jumlah foto, dan komentar
  peninjau bila ada. Contoh yang masuk prompt:
  `Lampiran 1: "Kondisi area kerja" — 3 foto, keterangan: "…"`.
- `docs/ai/Instruksi AI.md`: ubah bagian "Audit Lampiran Foto" jadi audit
  **kelengkapan keterangan lampiran**, bukan isi gambarnya. Mis. lampiran tanpa
  keterangan = `minor`; lampiran yang disebut di `aktivitas` tapi tidak ada
  berkasnya = `major`.

Mengirim gambar sungguhan ke model = perubahan besar (multimodal, biaya token
naik tajam, dan wajib konfirmasi kepatuhan data ke API eksternal — CLAUDE.md
§12). **Jangan dikerjakan sekarang**; tinggalkan penanda di kode:

```php
// ponytail: lampiran dikirim sebagai metadata saja. Naikkan ke multimodal
// (kirim gambar) bila peninjau benar-benar butuh AI menilai isi fotonya —
// butuh konfirmasi kepatuhan data ke API eksternal lebih dulu.
```

### 3.4 Duplikat `docs/AI/`

`docs/AI/Instruksi AI.md` **tidak dihapus tanpa konfirmasi** (CLAUDE.md §4).
Tanya user; bila disetujui, hapus. Bila tidak, ganti isinya jadi satu baris
penunjuk ke `docs/ai/Instruksi AI.md` supaya tidak ada yang salah edit.

### 3.5 Yang TIDAK diubah

- `caraMenilaiSubstansi()` / `caraMenilaiPenulisan()` — kerangka umum tingkat
  keparahan tetap di kode; yang per-jenis pindah ke berkas Markdown.
- Kontrak JSON keluaran, whitelist `section_key`, dan penanganan galat.
- Binding provider, `NullReviewer`, `temperature 0.3`, `max_tokens 3000`.
- `personaPenulisan()` — fokus MD memang harus tetap sempit (copy-editing);
  rubrik per jenis tidak relevan di sana.

---

## 4. Urutan pengerjaan

1. Tulis empat berkas `docs/ai/jenis/*.md` (isi dari §3.2). **Berhenti, minta
   user mengoreksi rubriknya** — user yang paling tahu praktik PT PPA.
2. Ubah `typeContext()` jadi pembaca berkas + fallback (§3.1).
3. Bereskan lampiran (§3.3).
4. Konfirmasi nasib `docs/AI/` (§3.4).

---

## 5. Verifikasi

```bash
php artisan test --filter=Ai
```

Manual:

1. Siapkan satu JSA dengan bahaya yang pengendaliannya **hanya** "gunakan APD".
   Jalankan Analisis AI dari `review/{id}` → harus muncul temuan `major` pada
   `section_key: analisa` yang menyebut hirarki kontrol.
2. Siapkan satu SOP dengan satu langkah `aktivitas` tanpa PIC → temuan `major`
   pada `section_key: aktivitas`.
3. Siapkan satu SP dengan parameter tanpa satuan → temuan `critical`.
4. Ganti sementara nama `docs/ai/jenis/JSA.md` → jalankan lagi, harus tetap
   berjalan memakai teks `match` bawaan (fallback hidup), tidak error.
5. `AI_ENABLED=false` → `NullReviewer`, tombol Analisis AI tidak meledak.
6. Matikan koneksi internet → `Log::warning` tercatat, pengguna menerima
   ringkasan "lanjutkan tinjauan manual", bukan halaman 500.
