# RENCANA EKSEKUSI — Indeks Induk Redesign SmartPro

> Berkas ini adalah checklist hidup. Centang tiap kotak setelah fase selesai DAN sudah diverifikasi
> di browser. Satu fase dikerjakan lalu berhenti untuk review — jangan lompat beberapa fase
> sekaligus dalam satu sesi kerja.
>
> Detail tiap fase ada di berkas terpisah (kolom "Berkas detail"). Berkas ini hanya status +
> ringkasan satu baris per fase.

## Peta berkas

| Berkas | Isi |
|---|---|
| `00-FONDASI.md` | Identitas visual "Paper & Signal", token, larangan, kamus domain (sudah ada, tidak berubah) |
| `01-LAYAR.md` | Kebutuhan konten per 18 layar inti — dipakai sbg referensi isi, BUKAN prompt Stitch lagi |
| `02-PENUTUP.md` | Peta 40+ layar turunan, batas cakupan, catatan porting (sudah ada, tidak berubah) |
| `03-SISTEM-DESAIN.md` | Token CSS & spek komponen final — sumber kebenaran kode |
| `04-FONDASI-TEKNIS.md` | Detail Fase A — scaffold proyek baru + setup token/tema |
| `05-KERANGKA-KOMPONEN.md` | Detail Fase B — shell aplikasi + pustaka komponen UI |
| `06-LAYAR-INTI.md` | Detail Fase C — Auth, Dashboard, Dokumen, Wizard, Papan Ketersediaan |
| `07-LAYAR-LANJUTAN.md` | Detail Fase D — Tinjau/Setuju, Distribusi/Log, Admin/Overlay/Notifikasi |
| `DATA-KONTRAK.md` | Akumulasi kebutuhan data per layar (diisi sepanjang Fase C–D), jadi spek Fase E |
| `08-BACKEND-PORTING.md` | Detail Fase E — sambungkan backend nyata memakai `DATA-KONTRAK.md` |
| `09-QA-CUTOVER.md` | Detail Fase F — QA kepatuhan desain, verifikasi cetak, cutover produksi |

## Status fase

### Fase A — Fondasi teknis (`04-FONDASI-TEKNIS.md`)
- [x] A.1 Scaffold `smartprorefactor_freshdesign1` (Laravel 12 + Tailwind v4 + Alpine + package inti)
- [x] A.2 Token desain (`@theme`), font, ikon Lucide, infra tema gelap/terang

### Fase B — Kerangka & komponen (`05-KERANGKA-KOMPONEN.md`)
- [ ] B.3 Shell aplikasi (S01 tanpa isi): navigasi global + topbar + lonceng + menu sesi
- [ ] B.4 Pustaka komponen UI (`components/ui/*`) + halaman `/design-system`

### Fase C — Layar inti (`06-LAYAR-INTI.md`)
- [ ] C.5 Auth — Masuk, Daftar, Menunggu Persetujuan
- [ ] C.6 Dashboard S01 (11 blok) + 5 varian peran
- [ ] C.7 Dokumen — Daftar (S03), Berjenjang/versi (S04), Detail (S05) + turunan peran
- [ ] C.8 Wizard — Dokumen baru (S06), Pengisian (S07), Editor JSA (S09), Log Revisi (S10)
- [ ] C.9 Papan Ketersediaan Peninjau (S08)

### Fase D — Layar lanjutan (`07-LAYAR-LANJUTAN.md`)
- [ ] D.10 Antrean (S11) + turunan; Ruang tinjau (S12) + turunan
- [ ] D.11 Distribusi & cakupan baca (S13) + turunan; Log & Audit (S14) + turunan
- [ ] D.12 Manajemen user (S15) + turunan; Overlay (S16); Notifikasi (S17); Akun (S18)

### Fase E — Backend nyata (`08-BACKEND-PORTING.md`)
- [ ] E.13 Port model/migrasi/Policy/Service/Controller + tutup semua butir `DATA-KONTRAK.md`
- [ ] E.14 Cetak PDF — salin `print/*` apa adanya, verifikasi `PrintLayoutTest`

### Fase F — QA & cutover (`09-QA-CUTOVER.md`)
- [ ] F.15 QA kepatuhan desain, dark mode, responsif, aksesibilitas
- [ ] F.16 Cutover produksi (hanya atas konfirmasi eksplisit user)

## Aturan tetap sepanjang proyek

1. Setiap fase sejak B diverifikasi di browser sebelum dicentang.
2. Data contoh memakai kamus `00-FONDASI.md` PASTE 1.4 (nama, judul, NRP, tempat) — tidak pernah
   lorem ipsum atau nama Inggris.
3. Setiap layar baru di Fase C/D menambah satu bagian di `DATA-KONTRAK.md` sebelum fase itu
   ditutup — jangan tunda ke Fase E.
4. Kalau ada keputusan desain yang menyimpang dari `03-SISTEM-DESAIN.md`, perbaiki sistemnya dulu
   (dgn persetujuan user), baru lanjut — jangan biarkan satu layar punya gaya sendiri.
