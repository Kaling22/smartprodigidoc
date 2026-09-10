# CEKLIS PEMERIKSAAN MATA — Fase 0–12, disusun per PERAN

> Gabungan sebelas blok `[!] Pemeriksaan mata — butuh kamu` yang tersebar di
> `PROGRESS-SHADCN.md` (Fase 0, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12). **Nol butir
> dibuang** — tiap baris menyebut fase asalnya (`[F8]`) supaya bisa ditelusuri
> balik.
>
> **Kenapa disusun per peran, bukan per fase.** Yang kamu kerjakan berurutan di
> peramban adalah menu SATU orang, bukan satu fase. Mengurutkannya per fase
> memaksa login-logout puluhan kali untuk memeriksa hal yang sebetulnya
> bertetangga di layar yang sama.

## Cara memakai

- **Kiri 9091** (aplikasi lama, `smartprorefactor_fresh`) · **kanan 9092**
  (`new-app`, shadcn). Adu berdampingan, jangan dari ingatan.
- Kalau ada beda yang **disengaja**, ia sudah tertulis di
  `PROGRESS-SHADCN.md` §N.x "keputusan kecil" fase itu. Yang tidak ada di sana
  = temuan; catat di kolom Catatan.
- **JANGAN pakai `GL-0001` / `SH-0001` / `PJO-0001`** — akun itu tidak pernah
  ada di basis data ini. Pakai NRP di bawah.
- Mode gelap: **jangan diperiksa per halaman**. Ada satu bagian tersendiri di
  akhir — sekali sapu, seluruh halaman.

| Peran | NRP | Kata sandi |
|---|---|---|
| Admin IT | `ADM-0001` | (punyamu) |
| Pimpinan / PJO | `16071367` | |
| Departemen Head | `17092728` | |
| Section Head | `16081467` | |
| Group Leader | `14040677` | |
| Management Development | `MD-0001` | |
| Non-Staff | `20260219` | |

---

## 0 · Sebelum login — halaman tamu `[F4]`

- [ ] `/login` — NRP salah → pesan galat tampil di kolom **NRP**, bukan di email
- [ ] `/login` — salah berkali-kali → **rate-limit** membalas (CLAUDE.md §15)
- [ ] `/register` — daftar akun baru → mendarat di halaman **tunggu**
- [ ] `/pending` — akun `pending` tertahan, tak bisa menembus ke dashboard
- [ ] Akun nonaktif → tertahan middleware `active`
- [ ] Foto panel kanan (`curved6.jpg`) masih ada di halaman masuk & daftar

---

## 1 · Admin IT — `ADM-0001`

Peran dengan menu terbanyak (61 tautan di `menu-per-peran.txt`), jadi didahulukan:
kalau kerangka layarnya salah, di sinilah paling cepat terlihat.

### 1a. Kerangka & navigasi `[F0]` `[F3]`

- [ ] Seluruh menu sidebar ada dan menuju halaman yang sama dengan 9091 —
      bandingkan dengan `C:\baseline-smartpro\menu-per-peran.txt` (admin_it: **61**)
- [ ] Breadcrumb di topbar menamai halaman dengan benar
- [ ] Lonceng notifikasi: jumlah belum dibaca, buka satu, "tandai semua dibaca"
- [ ] Menu pengguna di kaki sidebar (bukan di topbar)
- [ ] Sidebar bisa dilipat (`collapsible="offcanvas"`) dan keadaannya bertahan

### 1b. Pengguna & akses `[F6]`

- [ ] `/users` — kartu MD beserta **tiga saklarnya**
- [ ] `/users` — penyaring bekerja, **halaman 2** benar-benar berpindah baris
- [ ] `/users` — Ubah Peran, Nonaktifkan
- [ ] `/users` — Hapus akun **yang punya dokumen** → harus **DITOLAK**, dengan
      pesan yang mengarahkan ke Nonaktifkan
- [ ] `/akses` — buat profil, ubah, hapus
- [ ] `/akses` — tetapkan ke GL lalu cabut → **menu GL itu berubah**
- [ ] `/akses` — keempat saringan, termasuk "— Tanpa Akses —"

### 1c. Pengaturan & master data `[F7]`

- [ ] `/pengaturan/penomoran` — ketik prefix → pratinjau "Sekarang → Setelah
      disimpan" ikut berubah
- [ ] `/pengaturan/penomoran` — Simpan → konfirmasi → pesan sukses, lalu
      **buat satu dokumen baru** dan periksa nomornya
- [ ] `/pengaturan/master` — ubah nama departemen, alias, saklar Aktif
- [ ] `/pengaturan/master` — tambah departemen baru
- [ ] `/pengaturan/master` — kode departemen **yang sudah punya dokumen** harus
      **terkunci**
- [ ] `/pengaturan/master` — ubah nama jenis + matikan satu jenis → periksa menu
      Dokumen Baru
- [ ] `/pengaturan/master` — tambah kategori Informasi baru → **muncul di sidebar
      tanpa rilis kode**
- [ ] `/pengaturan/master` — centang/lepas kolom Edisi / No. Revisi / Tanggal Efektif
- [ ] `/pengaturan/sistem` — simpan setelan AI **tanpa mengisi kunci** → kunci
      lama harus **bertahan**
- [ ] `/pengaturan/sistem` — centang hapus kunci
- [ ] `/pengaturan/sistem` — Uji koneksi AI → balasan tampil
- [ ] `/pengaturan/sistem` — Kirim email uji → masuk **Mailpit**
- [ ] `/pengaturan/sistem` — Bersihkan cache → tanpa galat
- [ ] `/pengaturan/sistem` — Zona Berbahaya: buka jendelanya, ketik frasa **salah**
      → harus **DITOLAK**. ⚠️ **Jangan dijalankan pada data yang masih dipakai.**

### 1d. Dokumen `[F8]` `[F11]`

- [ ] `/dokumen-tidak-berlaku` — tombol "N versi" membentang barisnya
- [ ] `/dokumen-tidak-berlaku` — Rollback
- [ ] `/dokumen-tidak-berlaku` — Aktifkan, termasuk kasus **nomor bentrok**
- [ ] `/dokumen-tidak-berlaku` — Arsipkan, dan Musnahkan **pada baris versi**
- [ ] `/dokumen-berlaku` — Musnahkan satu dokumen
- [ ] `/dokumen-berlaku` → **Perbaiki** dokumen lama: jenis & departemen
      **terkunci**, "Lihat berkas sekarang" membuka PDF
- [ ] Perbaiki dokumen lama — kosongkan Judul lalu Simpan → **peramban** yang
      menegur, bukan jendela konfirmasi (urutannya: sah dulu, konfirmasi menyusul)
- [ ] Perbaiki dokumen lama — tombol "Lembar Catatan Revisi" **hanya** pada
      SOP/SP/IK, tidak pada FK/PX
- [ ] Lembar Catatan Revisi — pratinjau PDF di panel kanan, "Berkas ini N halaman"
- [ ] Lembar Catatan Revisi — tambah/hapus baris, dua radio pilihan **mengubah
      bunyi konfirmasi**, "Nanti Saja" kembali ke Dokumen Berlaku

### 1e. Audit & distribusi `[F5]` `[F11]`

- [ ] Dashboard — tiap angka statistik **sama persis** dengan 9091
- [ ] Kartu aktivitas dibatasi 15 baris dan **menggulir di dalam tinggi tetap**
- [ ] `/distribusi-dokumen` — urutan bawaan cakupan **TERENDAH di atas**
- [ ] `/distribusi-dokumen` — tekan judul "No. Dokumen" → urutannya berganti
- [ ] `/distribusi-dokumen` — saring jenis & departemen; Reset mengembalikan semua
- [ ] `/distribusi-dokumen?sumber=informasi` — tombol "Per Departemen" ada,
      membukanya menampilkan **7 pita** + keterangan "hanya menghitung orang yang
      punya departemen"
- [ ] Kartu peringatan "N dokumen baru dibaca kurang dari separuh" muncul hanya
      bila memang ada; bunyinya berganti dokumen/informasi
- [ ] Rincian Informasi — kolom Sudah/Belum Membaca + lencana angka, ikon
      ponsel/laptop per pembaca
- [ ] Rincian Informasi — pemilih departemen menyempitkan daftar, tombol reset muncul

### 1f. Informasi, audit & riwayat `[F12]`

- [ ] `/informasi?kategori=kebijakan` — kolom **Edisi / Rev** dan **Tgl Efektif**
      ada; buka `?kategori=memo_internal` → keduanya **hilang sendiri** (kolomnya
      mengikuti kategori, bukan cabang di kode)
- [ ] Baris ber-riwayat — chevron kolom pertama **dan** tombol "Riwayat (N)"
      membuka blok yang SAMA; blok itu menjorok & bergaris kiri
- [ ] Baris tanpa riwayat — chevron & tombol "Riwayat" **tidak ada sama sekali**
- [ ] Tombol **Buka** membuka berkasnya di tab baru; ikonnya gambar untuk POSTER,
      PDF untuk sisanya
- [ ] **Hapus** pada baris ber-riwayat → jendela menawarkan **DUA** pilihan
      ("versi ini saja" / "seluruhnya"); tanpa riwayat → hanya SATU
- [ ] Hapus versi lama dari dalam blok riwayat → konfirmasi menyebut nomornya
- [ ] **Tambah** — kosongkan Judul lalu tekan Unggah: **peramban** yang menegur,
      bukan jendela konfirmasi. Nomor kembar → pesan galat menyarankan Perbarui
- [ ] **Perbarui** — Kategori & Nomor **terkunci**; Edisi/Revisi terisi angka
      BERIKUTNYA (induk Edisi 1 Rev 4 → **Edisi 2 Rev 0**, bukan Rev 5)
- [ ] Perbarui — konfirmasi muncul SESUDAH kolom wajib terisi, bunyinya menyebut
      Edisi/Rev yang dijanjikan; kartu kanan "Versi Sekarang" + "Buka berkas sekarang"
- [ ] Kategori yang **ditutup** Admin di Master Data → URL-nya membuka halaman
      "Sedang Ditutup", bukan 404 telanjang
- [ ] `/audit-log` — saring **nama aksi** (kotak teks bebas) & **user**; Reset
      mengembalikan semuanya; nomor dokumen tertaut ke halamannya
- [ ] `/riwayat-pekerjaan` — bar progres terisi sesuai centang lapangan; baris
      **dibatalkan** menampilkan "—", **bukan** bar 0%
- [ ] `/riwayat-pekerjaan` — saring tanggal dari/sampai; tombol Hapus (Admin saja)
      berkonfirmasi

---

## 2 · Group Leader — `14040677`

Satu-satunya pembuat dokumen. **Bagian terberat seluruh ceklis** — sediakan waktu.

### 2a. Kerangka `[F0]` `[F5]`

- [ ] Menu sidebar cocok dengan `menu-per-peran.txt` (group_leader: **37**)
- [ ] "Status Dokumen" **tanpa dropdown** — hanya dokumen buatannya sendiri
- [ ] Dashboard — angka statistik sama dengan 9091, kartu kanan sesuai jabatan
- [ ] Kartu Ketersediaan — `?bulan=` maju-mundur bekerja (tautan, bukan JS)
- [ ] Kartu Ketersediaan — klik tanggal → jendela Ajukan Off **terisi tanggalnya**

### 2b. Daftar dokumen `[F8]`

- [ ] `/documents` — Kirim / Edit / Hapus pada **draft sendiri**
- [ ] `/documents` — Tarik pada `waiting_for_review`
- [ ] `/documents` — penyaring Sumber (SmartPro vs Dokumen Lama)
- [ ] `/documents` — urut No. Dokumen naik-turun, halaman 2
- [ ] `/dokumen-berlaku` — Revisi + Ajukan Nonaktif (sebagai pemilik)
- [ ] `/dokumen-revisi` — rangkuman **dan** anotasi peninjau keduanya terbaca
- [ ] `/status-dokumen-staff` — judulnya berbunyi **"Dokumen Departemen"**
- [ ] Satu `/documents/{id}` — Timeline, panel Distribusi, Masukan Lapangan,
      Balas & Tutup, tombol **Kembali tidak 403** (bug lama CLAUDE.md §14)

### 2c. Wizard — inti SmartPro `[F9]`

- [ ] Buat dokumen **tiap jenis**: SOP, IK, SP, JSA (wizard) + FK, PX (unggah)
- [ ] Jalur **arsip / dokumen lama** pada jenis berwizard
- [ ] Isi **seluruh** tipe seksi minimal sekali: `rich_list`, `reference_picker`,
      `repeatable_group` (dengan gambar **dan** dengan Quill), `jsa_analysis`
      bersarang penuh, `text`, `date`, `user_picker`
- [ ] Quill — tebal / miring / garis-bawah, kutipan, daftar bernomor & berbutir
- [ ] Quill — tombol gambar
- [ ] Quill — **tempel (Ctrl+V) tangkapan layar** → harus jadi **berkas** di
      `lampiran/{DEPT}/{JENIS}/`, **bukan** base64
- [ ] Autosave — ketik, tunggu ~1,5 detik, **tutup tab**, buka lagi → isian masih ada
- [ ] Autosave — keterangan "Tersimpan otomatis" berjam **WITA** (bug lama §14)
- [ ] Preview — iframe menyegar **sesudah** menyimpan langkah
- [ ] Preview — **tidak berkedip** saat pindah langkah bila isinya tak berubah
- [ ] Sakelar "Gunakan Flowchart" mati → bab redup & tak bisa disentuh, tapi
      **ketikan TIDAK hilang** saat disimpan
- [ ] Sakelar "Gunakan Flowchart" mati → nomor bab AKTIVITAS **turun jadi V**
- [ ] Papan ketersediaan — baris terkunci saat peninjau off
- [ ] Papan ketersediaan — klik **di mana saja** pada baris memilihnya
- [ ] Papan ketersediaan — panah keyboard berpindah baris
- [ ] **Kirim** dengan bab opsional kosong → peringatan **dua langkah** muncul
- [ ] Sel pita/kalender **elastis** — nama hari tak terjepit, tak ada ruang kosong

### 2d. Uji khusus wizard `[F9]`

- [ ] **Bandingkan PDF hasil dengan baseline** — harus identik
- [ ] **Uji P1:** tambah satu seksi ke `schema_json` lewat SQL → **muncul sendiri**
      di form tanpa satu baris kode disentuh → kembalikan
- [ ] **Uji pembuat tambahan:** pilih GL sedepartemen → simpan → ajukan cuti untuk
      GL itu → simpan lagi → **namanya harus tetap ada**
- [ ] **Uji SP/IK:** pilih `peninjau_penyetuju` → periksa di DB `reviewer_id` dan
      `approver_id` **terisi sama**

---

## 3 · Section Head — `16081467`

### 3a. Kerangka & antrean `[F0]` `[F5]` `[F10]`

- [ ] Menu sidebar cocok (`section_head`: **40**) — **tanpa** "Status Dokumen",
      memakai "Status Dokumen Staff"
- [ ] Dashboard — angka sama dengan 9091, kartu kanan sesuai jabatan
- [ ] `/users/pending` — hanya **departemennya**, bukan tujuh
- [ ] `/review` — dua tabel, urutkan kolom Nomor & Edisi/Revisi
- [ ] `/review` — Batalkan Revisi berkonfirmasi

### 3b. Layar tinjau `[F10]`

- [ ] Tinjau **SOP** — kotak catatan di tiap item
- [ ] Tinjau SOP — bab `rich_text` (Aktivitas) tampil sebagai **teks berformat**,
      bukan `<p><strong>…` mentah
- [ ] Tinjau **JSA** — tanda Sesuai / Perlu Revisi tiap pengendalian
- [ ] Tinjau JSA — batang merah di tepi baris bertanda ✗
- [ ] Tinjau JSA — penanda borongan **hanya** pada bahaya berpengendalian banyak
- [ ] Tinjau JSA — tombol Kirim **mati** sampai semua ditandai, dan bunyinya
      berubah mengikuti jumlah ✗
- [ ] **Alihkan** dari antrian (`?alih=1` → jendela terbuka sendiri)
- [ ] **Alihkan** dari tombol di halaman tinjau
- [ ] Alihkan — peninjau yang off **terkunci**; alasan **wajib**
- [ ] **Panel AI** — tekan Analisis; adopsi satu temuan → layar **mengantar ke
      kotaknya** + lencana "Diadopsi dari saran AI"
- [ ] Panel AI — tolak satu temuan
- [ ] **Foto lampiran** tampil, komentar terkirim & **langsung terlihat**
- [ ] `/status-dokumen-staff` — submenu per departemen

### 3c. Distribusi & nonaktif `[F10]` `[F11]`

- [ ] `/nonaktif` — Setujui (bunyi konfirmasi **berbeda** di tahap terakhir)
- [ ] `/nonaktif` — Tolak lewat menu titik-tiga → alasan **wajib**
- [ ] `/distribusi-dokumen` — hanya dokumen **departemennya**
- [ ] `/distribusi-dokumen?sumber=informasi` — tombol "Per Departemen"
      **TIDAK ada** (lingkupnya satu departemen)
- [ ] Rincian Informasi — pemilih departemen **absen**

### 3d. Log Dokumen `[F12]`

- [ ] `/log-dokumen/masukan` — kalimat pengantar berbunyi "di departemen Anda"
      (PJO/Admin: "di 7 departemen"); penyaring **Departemen** ikut hilang di sini
- [ ] Isi masukan >120 karakter → terpotong dengan "selengkapnya"; menekannya
      membentangkan tanpa memuat ulang halaman
- [ ] Tombol **Balas** → jendela berisi kutipan masukannya; kosongkan balasan →
      peramban menegur, konfirmasi **belum** muncul
- [ ] Tombol **Revisi** → jendela Ajukan Revisi terbuka dengan masukan pemicunya
      **sudah tercentang**
- [ ] Dua masukan atas **dokumen yang sama** → tetap SATU jendela Revisi
- [ ] `/log-dokumen/pesan` — saring Tahap, Departemen, dan rentang tanggal
- [ ] Log Pesan — pesan >140 karakter terpotong; barisnya tak pernah setinggi paragraf
- [ ] Log Pesan — tombol baris **berbeda per penonton**: pembuat mendapat
      "Revisi", orang lain "Dokumen"; baris **pemusnahan** tak bertombol sama sekali

---

## 4 · Departemen Head — `17092728`

Wewenangnya sama dengan SH; yang diperiksa di sini hanya yang **beda**.

- [ ] Menu sidebar cocok (`departemen_head`: **36**)
- [ ] Dashboard — kartu kanan sesuai jabatan, angka sama dengan 9091
- [ ] Antrean tinjau & persetujuan terisi sesuai matriks `docs/aturan-alur-v2.md`
- [ ] Ajukan Revisi atas dokumen Berlaku → **form alasan dulu**, konfirmasi menyusul
- [ ] Alasan revisi muncul di "Dokumen Revisi" pembuat, berawalan `[Pengaju Revisi]`
- [ ] Masukan Non-Staff — Balas & Tutup, dan **centang saat Ajukan Revisi**
      (masukan jadi `diadopsi` + tertaut ke dokumen revisinya)

---

## 5 · Pimpinan / PJO — `16071367`

- [ ] Menu sidebar cocok (`pimpinan`: **46**) — **tanpa** "Status Dokumen"
- [ ] Dashboard — angka sama dengan 9091
- [ ] `/users/pending` — **lintas 7 departemen** (PJO tanpa departemen)
- [ ] `/approvals` — Setujui → dokumen **Berlaku**; cek **stempel APPROVED** di PDF
- [ ] `/approvals` — Kembalikan **tanpa alasan** → pesan galat muncul
- [ ] `/approvals` — peninjau yang meloloskan **dinotifikasi** saat approver menolak
- [ ] `/nonaktif` — tahap terakhir; nomor benar-benar dilepas
- [ ] `/dokumen-berlaku` — Export Excel per jenis
- [ ] `/distribusi-dokumen` — lintas 7 departemen

---

## 6 · Management Development — `MD-0001`

- [ ] Menu sidebar cocok (`management_development`: **45**)
- [ ] `/review-md` — antrean tahap MD terisi
- [ ] Tahap MD — label tombol berbunyi **"Loloskan ke PJO"** / **"Kembalikan untuk
      Perbaikan"**
- [ ] Tahap MD — panel AI **hilang** saat Admin mematikannya di kartu MD
- [ ] Badge status `verifikasi_md` **berwarna & berikon benar — jangan sampai abu-abu**

---

## 7 · Non-Staff — `20260219`

Read-only atas isi dokumen. Yang diperiksa: batas itu benar-benar dijaga.

- [ ] Menu sidebar cocok (`staff`: **31**) — dropdown per jenis
- [ ] Dashboard — **Distribusi TIDAK muncul** sama sekali (bukan muncul kosong)
- [ ] `/dokumen-berlaku` — tombol **Beri Masukan** ada
- [ ] Beri Masukan — terkirim, muncul di Log Masukan SH/DH
- [ ] Tak ada satu pun tombol Edit / Kirim / Hapus / Revisi di mana pun
- [ ] `/distribusi-dokumen` → **403** (bukan halaman kosong)
- [ ] Panel "Belum Membaca" di detail dokumen **tidak dirender** baginya
- [ ] Masukan sejawat — layar pemberi **tanpa AI & tanpa keputusan**; layar pembaca
      menampilkan **nama bab** ("I. TUJUAN"), bukan kunci mentah (`tujuan`)
- [ ] `/informasi?kategori=…` **terbuka** — itulah inti menu ini `[F12]`; tombol
      **Buka** ada, tombol Tambah / Perbarui / Hapus **tidak ada satu pun**
- [ ] `/log-dokumen/masukan` & `/log-dokumen/pesan` → **403** `[F12]`
- [ ] `/audit-log` → **403** `[F12]`

---

## 8 · Lintas peran — sekali sapu di akhir

### 8a. Alur penuh satu dokumen `[F10]`

Kerjakan satu SOP dari nol sampai mati, berpindah akun sesuai tahapnya:

- [ ] `draft` → `waiting_for_review` → `in_review` → `verifikasi_md` →
      `pending_approval` → `published`
- [ ] Jalur gagal: `rejected` → pembuat memperbaiki → kirim ulang
- [ ] `sedang_direvisi` → `menunggu_nonaktif` → `obsolete`
- [ ] **Badge tiap status** berwarna & berikon benar di **setiap** layar yang
      menampilkannya

### 8b. Revisi Tipe B & lembar cetak `[F11]`

- [ ] Ajukan revisi → Batalkan revisi → ajukan lagi
- [ ] Isi **Log Revisi** (langkah ekstra di akhir wizard) → kirim
- [ ] Lembar **CATATAN REVISI** di PDF **sama persis dengan baseline**
- [ ] Roll-over: revisi 4 → berikutnya **Edisi+1 Revisi 0** (angka 5 tak pernah tampil)

### 8c. Mode gelap — seluruh halaman sekaligus

Nyalakan toggle sekali, lalu telusuri **semua** halaman yang sudah dicentang di
atas. Yang dicari cuma tiga: teks yang hilang di latar gelap, kartu yang tetap
putih, dan ikon yang lenyap.

- [ ] Halaman tamu (login, daftar, tunggu) `[F4]`
- [ ] Dashboard + sepuluh kartunya `[F5]`
- [ ] Pengguna & akses — 6 halaman `[F6]`
- [ ] Pengaturan — 3 halaman `[F7]`
- [ ] Daftar dokumen — 6 halaman `[F8]`
- [ ] Wizard — 3 halaman `[F9]`
- [ ] Tinjau & persetujuan — 7 halaman `[F10]`
- [ ] Distribusi & arsip — 4 halaman `[F11]`
- [ ] Informasi, audit, riwayat & log — 8 halaman `[F12]`

### 8d. Standar UX yang berlaku di mana-mana `[F3]` `[F13]`

- [ ] **Nol emoji** — seluruh ikon `lucide-react`
- [ ] Logo PPA bisa diklik → kembali ke beranda
- [ ] Konfirmasi (`AlertDialog`) muncul sebelum **tiap** aksi destruktif:
      Kirim, Hapus, Ajukan Revisi, Batalkan Revisi, Musnahkan, Nonaktifkan
- [ ] Keadaan **kosong** (empty state) terbaca di tiap daftar, bukan tabel melompong
- [ ] Kolom **Status + Aksi + Lihat PDF** konsisten susunannya antar-halaman
- [ ] Tabel lebar **menggulir di dalam kotaknya**, badan halaman tak pernah
      menggulir mendatar

---

## Catatan temuan

Tulis di sini, sebutkan peran + halaman + fase asalnya. Temuan yang menyangkut
tata letak bersama (`AppLayout`, sidebar, `DataTable`, `StatusBadge`) **dahulukan**
— satu perbaikan di sana menutup puluhan halaman sekaligus, dan menundanya sampai
Fase 13 berarti memperbaikinya di puluhan tempat.

| # | Peran | Halaman | Temuan | Fase |
|---|---|---|---|---|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |
