# DATA KONTRAK — Kebutuhan Data per Layar

> Berkas AKUMULATIF. Tiap kali satu layar di Fase C/D selesai dibangun (Fase B–D memakai data
> statis), tambahkan SATU bagian di sini SEBELUM fase itu ditutup dan dicentang di
> `RENCANA-EKSEKUSI.md`. Fase E (`08-BACKEND-PORTING.md`) mengeksekusi persis daftar "BARU" di
> bawah — bukan menerka ulang dari nol.
>
> Format tiap bagian: **Layar** → **Field yang ditampilkan** → **Sumber** (`SUDAH ADA: Model::field`
> atau `BARU: perlu method/query`) → **Catatan** (bentuk data khusus, mis. deret waktu, persentase).

---

## Referensi cepat — apa yang SUDAH ADA di model lama

Dari inventarisasi `smartprorefactor_fresh` (jangan port ulang tabel ini ke sini, hanya rujukan
saat mengisi kontrak di bawah):

- **`Document`**: `doc_number`, `doc_number_final`, `doc_number_manual`, `document_type_id`,
  `department_id`, `title`, `status`, `current_step`, `revision_round`, `no_revisi`,
  `revises_document_id`, `edisi`, `is_controlled`, `reviewer_id`, `approver_id`, `created_by`,
  `submitted_at`, `published_at`, `nonaktif_tahap`, `nonaktif_oleh`, `obsolete_reason`. Relasi:
  `type`, `department`, `creator`, `reviewer`, `approver`, `revisesDocument`/`revisedBy`,
  `contents`, `authors`, `reviews`, `approvals`, `versions`, `attachments`, `feedback`.
- **`DocumentContent`**: `document_id`, `section_key`, `value_json`.
- **`DocumentVersion`**: `document_id`, `no_revisi`, `snapshot_json`, `created_by`.
- **`Review`**: `document_id`, `reviewer_id`, `revision_round`, `decision`, `summary` (+
  `ReviewAnnotation`).
- **`Approval`**: `document_id`, `approver_id`, `kind`, `decision`, `comment`, `signed_at`.
- **`Attachment`**: `document_id`, `section_key`, `path`, `original_name`, `mime`, `size` (+
  `AttachmentComment`).
- **`DocumentFeedback`**: masukan lapangan, status sendiri, `revision_document_id`.
- **`User`**: `name`, `username`, `nrp`, `jabatan`, `nomor_hp`, `email`, `photo_path`,
  `department_id`, `status`, `ai_review_enabled`.
- **`Department`**: `code`, `name`, `alias` (7 tetap).
- **`DocumentType`**: `code`, `name`, `schema_json`, `class`, `scope`, `is_active`.
- **`UserOffDay`**: `user_id`, `jenis`, `mulai`, `sampai`, `catatan`.
- **`AuditLog`**: `user_id`, `document_id`, `action`, `meta_json`, `ip_address`, `created_at`.
- **`JobExecution`** (+ `JobChecklistItem`), **`Informasi`**, **`DocumentAuthor`** (pivot).

Yang TIDAK ADA (harus dipastikan BARU tiap kali muncul di layar): deret waktu harian/mingguan untuk
sparkline atau chart Overview, persentase pertumbuhan vs periode lalu, persentase cakupan baca per
dokumen, agregat matriks status×jenis, ranking "cakupan baca terendah".

---

## Shell / Navigasi

- **Badge angka `(n)`** pada 5 item nav (Dokumen Revisi, Persetujuan Nonaktif, Tinjau Dokumen,
  Persetujuan Saya, Persetujuan Akun, Masukan Lapangan) → `BARU: perlu count query per peran`,
  masing-masing setara filter yang sudah dipakai controller lama (`review.index`,
  `approvals.index`, `nonaktif.index`, `users.pending`, log masukan) tapi sekarang dibutuhkan
  sebagai angka ringkas real-time di setiap page-load, bukan hanya saat membuka halaman itu
  sendiri — pertimbangkan cache pendek per user/session di Fase E.

*(Bagian berikutnya ditambahkan saat Fase C.5 dst dikerjakan — lihat `06-LAYAR-INTI.md` dan
`07-LAYAR-LANJUTAN.md` untuk daftar layar yang wajib mengisi bagian di sini.)*

## Auth (Fase C.5)

_(diisi saat dikerjakan)_

## Dashboard (Fase C.6)

_(diisi saat dikerjakan — ekspektasikan BANYAK butir BARU: deret "Dibuat"/"Berlaku" 14hari/8minggu/
8bulan, growth %, matriks status×jenis, cakupan baca terendah dgn avatar stack, umur baris "Menunggu
di Meja")_

## Dokumen — Daftar/Detail (Fase C.7)

_(diisi saat dikerjakan)_

## Wizard Pengisian (Fase C.8)

_(diisi saat dikerjakan — perhatikan schema-driven: field bentuknya datang dari
`DocumentType::schema_json`, bukan kolom tetap)_

## Papan Ketersediaan (Fase C.9)

_(diisi saat dikerjakan — logika beban peninjau sudah ada di backend lama,
`smartpro.peninjau.batas_dokumen`, `UserOffDay`; pastikan dipakai lagi bukan ditulis ulang)_

## Antrean & Ruang Tinjau/Persetujuan (Fase D.10)

_(diisi saat dikerjakan)_

## Distribusi & Log/Audit (Fase D.11)

_(diisi saat dikerjakan — persentase cakupan baca per dokumen = BARU, hitung dari log baca yg
mungkin belum ada tabelnya di app lama, cek dulu sebelum asumsi)_

## Admin / Overlay / Notifikasi / Akun (Fase D.12)

_(diisi saat dikerjakan)_
