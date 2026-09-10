# FASE F — QA & Cutover

> Menaungi: `RENCANA-EKSEKUSI.md` F.15–F.16. Fase terakhir — dikerjakan setelah SEMUA layar (Fase
> C, D) sudah tersambung ke data nyata (Fase E).

---

## F.15 — QA kepatuhan desain

**Tujuan.** Pass verifikasi menyeluruh sebelum produk dianggap siap gantikan yg lama — cek ulang
SETIAP layar terhadap aturan yg sudah ditetapkan sejak Fase A, bukan hanya layar yg "terasa"
bermasalah.

**Rujukan.** `02-PENUTUP.md` § "Daftar tolak — 10 tanda hasil generate harus diulang",
`03-SISTEM-DESAIN.md` §10 ("Aturan yang gampang dilanggar tanpa sengaja").

**Langkah — checklist dijalankan per layar (bukan sekali untuk seluruh produk):**
1. Tidak lebih dari 3 elemen beraksen oranye terlihat sekaligus di 1440px.
2. Kartu diam tidak pakai bayangan; tidak ada gradien di mana pun.
3. Kartu statistik tidak punya ikon bulat berwarna di pojok kanan atas.
4. Susunan sidebar/topbar tidak kembar dgn screenshot referensi Zenith.
5. Judul halaman tidak diikuti subjudul basa-basi.
6. Tidak ada tombol digambar nonaktif utk orang yg memang tak berhak (kecuali papan ketersediaan
   S08 — pengecualian eksplisit).
7. Tidak ada warna di luar netral + oranye + hijau/merah/amber.
8. Tidak ada teks Inggris, lorem ipsum, nama fiktif Inggris, atau emoji.
9. Semua 10 badge status berikon, 5 varian amber terbedakan lewat ikon+label.
10. Komponen bersama (badge, tombol, header tabel, filter, modal) IDENTIK bentuknya di semua layar
    — bandingkan langsung ke `/design-system`.
11. Setiap kolom angka di setiap tabel `tabular-nums`, rata kanan.
12. Judul panjang terpotong 1 baris, tinggi baris tabel tidak berubah.
13. Nomor dokumen selalu monospace, bertanda "sementara" bila belum final.
14. Kunci `staff` selalu tercetak "Non-Staff" — grep seluruh view utk memastikan kunci mentah tak
    pernah tercetak (setara `tests/Feature/LabelJabatanTest.php` lama, port test itu juga).
15. Dark mode: ulangi poin 1–10 dlm tema gelap, bukan cuma tema terang.
16. Responsif: cek breakpoint tablet & phone — konten tidak boleh terpotong, nav rail tetap
    berfungsi.
17. Aksesibilitas dasar: fokus ring terlihat di semua kontrol interaktif, kontras teks/latar
    memenuhi WCAG AA minimal utk teks `--text-muted` di atas `--bg`/`--surface`.

**Kontrak data.** Tidak ada — fase ini QA visual & fungsional, bukan penambahan fitur.

**Verifikasi.** Isi checklist di atas per-layar dlm satu tabel (boleh ditambahkan sbg lampiran di
berkas ini saat dikerjakan), catat setiap pelanggaran + perbaikannya.

---

## F.16 — Cutover produksi

**Tujuan.** Memindahkan `smartprorefactor_freshdesign1` menjadi htdocs produksi PT PPA,
menggantikan `smartprorefactor_fresh`.

**PERINGATAN.** Fase ini TIDAK dieksekusi otomatis. Sesuai CLAUDE.md §4, tindakan yg memengaruhi
sistem bersama (vhost, DNS, migrasi data produksi) baru dijalankan atas konfirmasi eksplisit user,
di sesi terpisah, dgn rencana migrasi data yg dibahas tersendiri (data dokumen mutu yg sudah
Berlaku di produksi harus ikut pindah — ini BUKAN sekadar swap kode).

**Langkah (garis besar, dirinci nanti saat fase ini benar-benar dikerjakan).**
1. Rencana migrasi data dari DB produksi lama ke skema baru (kalau skema migrasi berubah) atau
   pemakaian DB yg sama (kalau skema tak berubah — lebih aman & lebih mungkin, krn E.13 memakai
   migrasi yg diporting apa adanya).
2. Backup penuh DB + storage (`storage/app/public/lampiran`) sebelum cutover.
3. Uji staging dgn salinan data produksi (bukan data contoh) sebelum benar-benar switch.
4. Jadwal downtime singkat (kalau perlu) + komunikasi ke user PT PPA.
5. Swap vhost/document root XAMPP dari `smartprorefactor_fresh` ke `smartprorefactor_freshdesign1`.
6. Pantau audit log & error log pasca-cutover, siapkan rencana rollback.

**Verifikasi.** Rencana migrasi data direview & disetujui user SEBELUM langkah 5 dijalankan.
