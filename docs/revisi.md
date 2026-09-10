# DRAF PERENCANAAN — REVISI STRUKTUR & PRIVILEGE SMARTPRO (v4)
Status: DRAF — BAHAN PLAN MODE. JANGAN IMPLEMENTASI.
Terakhir update: 2026-08-10

## Cara baca dokumen ini
- [KUNCI] = intent pasti dari pemilik produk, jangan diganggu.
- [BUKA]  = keputusan belum final; "rencana:" adalah usulan default yang
  boleh (dan diharapkan) ditantang dengan trade-off.
- Bila dokumen ini bertentangan dengan kenyataan kodebase: catat sebagai
  konflik, jangan memilih sendiri secara diam-diam.
- Output yang diminta: rencana, bukan kode implementasi.

## Konteks
- Stack: [ISI: PHP native/framework, MySQL, dsb] + Bootstrap 5, apexcharts,
  perfect-scrollbar. Font Poppins; ikon fungsional Bootstrap Icons;
  dilarang emoji/ikon dekoratif di depan teks.
- Spec UI v1-v3: docs/SPEC-DASHBOARD.md (dianggap selesai).
- Role existing di sistem: [ISI dari kodebase].

## Intent revisi
1. staff -> non-staff, STRUKTURAL bukan rename.
   [KUNCI] seluruh struktur `staff` lama (DB, variabel, session, guard,
   label UI) diganti menjadi non-staff; role `staff` sudah tidak ada

2. Admin dapat menambah user staff, non-staff, MD.
   [BUKA] field per role, password awal, interaksi dengan alur
   Persetujuan Akun (rencana: buatan admin = auto-aktif, skip approval).

3. Ukuran foto logo light & dark mode disamakan agar transisi seamless.
   [KUNCI] hasil harus seamless; [BUKA] teknik (rencana: tinggi fixed +
   crossfade opacity, bukan ganti src).

4. Kartu sambutan light mode dibuat baru; desain sekarang khusus dark mode.
   [KUNCI] satu markup dua tema via CSS variables; layout & tinggi identik.

5. "Standar Produksi" -> "Standar Parameter", total: web dan dokumen.
   [KUNCI] rename menyeluruh di master, UI, template dokumen.
   [BUKA] prefix penomoran SP (rencana: tetap, kini = Standar Parameter);
   dokumen lama tampil dengan nama baru + snapshot nama di record
   (rencana) vs rename retroaktif penuh.

6. Seluruh widget dashboard tampil untuk semua role KECUALI non-staff
   (admin, GL, SH, DH, PJO). MD = menyusul, siapkan hook.
   [KUNCI] non-staff adalah satu-satunya role terbatas.
   non-staff (rencana: sambutan + dokumen milik sendiri + masukan lapangan).

7. Admin dapat menghapus user di manajemen user.
   [BUKA] soft delete (rencana) vs hard delete; guard: akun sendiri dan
   admin terakhir tidak dapat dihapus.
   
8. Admin dapat menghapus dokumen BERLAKU; histori bersih beserta penomoran.
   [KUNCI] cascade: versi, riwayat tinjauan, unduhan, masukan lapangan;
   nomor dokumen lenyap dan dokumen lain tidak di-renumber.
   [BUKA] nomor boleh dipakai ulang atau tidak (rencana: tidak);
   tetap 1 entri audit log untuk tindakan penghapusan, wajib alasan +
   ketik ulang nomor dokumen (rencana: ya).

## Matriks permission (draf, verifikasi ke kodebase)
| Kemampuan               | admin | sh | dh | gl | pjo | staff | nonstaff | md   |
| dashboard full widget   | v     | v  | v  | v  | v   | ?     | x        | nanti|
| tambah user             | v     |    |    |    |     |       |          |      |
| hapus user              | v     |    |    |    |     |       |          |      |
| hapus dokumen berlaku   | v     |    |    |    |     |       |          |      |

## Urutan kerja usulan (boleh direvisi)
A kosmetik (3,4) -> B refactor role (1) -> C rename terminologi (5) ->
D visibility widget (6) -> E tambah user (2) -> F destruktif (7,8)

## Tugas Claude di plan mode
1. Impact map: grep `staff` dan `Standar Produksi`/`standar_produksi`
   seluruh repo; hasil = file:baris + klasifikasi
   (logika permission / label UI / data / string audit).
2. Verifikasi konteks + matriks di atas; koreksi yang salah.
3. Tiap [BUKA]: rekomendasi + trade-off + dampak ke data existing.
4. Rancangan migrasi data (SQL) untuk role & rename, termasuk rollback.
5. Pecah jadi fase kecil per sesi + checklist uji manual per fase
   (login tiap role, transisi theme, alur approval, dsb).
6. Tulis hasil ke docs/PLAN-V4.md. JANGAN implementasi.