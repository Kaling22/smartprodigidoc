<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Pengaturan;

/**
 * Penomoran dokumen (PRD §7.1).
 * Format: {PREFIX}-{JENIS}-{DEPT}-{NN}  mis. PPA-ADRO-SOP-ICTMD-01
 *
 * PREFIX-nya datang dari setelan `site.prefix` (Fase 5a), bukan lagi konstanta.
 * Ia hanya berpengaruh pada nomor yang BARU dibangkitkan — nomor yang sudah
 * tersimpan di `doc_number`/`doc_number_final` tak pernah ditulis ulang.
 *
 * DUA TAHAP, DUA KOLAM — dan kolamnya sengaja beraturan BERBEDA:
 *
 * 1. Nomor SEMENTARA (`doc_number`, dipakai selama draft & peninjauan) diambil
 *    dari kolam yang MENGECUALIKAN dokumen terhapus. Draft yang dibatalkan
 *    karena itu langsung mengembalikan nomornya, dan draft berikutnya mengisi
 *    celah itu alih-alih naik satu. Draft memang belum tentu jadi dokumen; ia
 *    tak berhak menyandera nomor selamanya.
 *
 * 2. Nomor FINAL (`doc_number_final`, dikunci saat disahkan) diambil dari kolam
 *    yang MENYERTAKAN dokumen terhapus. Nomor yang pernah terbit tak boleh
 *    lahir kembali — dokumen nonaktif menahan nomornya, bahkan bila catatannya
 *    kelak dibersihkan. Celah di daftar induk lebih mudah dijelaskan kepada
 *    auditor daripada dua dokumen berbeda yang pernah bernomor sama.
 *
 * Nomor sementara TIDAK ikut menghitung nomor final ke dalam kolamnya sendiri
 * secara terpisah: kolam sementara membaca kedua kolom, supaya draft baru tak
 * pernah menampilkan nomor yang sedang dipakai dokumen Berlaku.
 */
class DocumentNumberService
{
    /**
     * Prefix BAWAAN — dipertahankan sebagai konstanta meski nilainya kini datang
     * dari setelan (PLAN-AKSES-v8 Fase 5a). Ia yang dipakai Pengaturan::BAWAAN,
     * jadi sistem yang setelannya belum pernah disentuh bernomor persis seperti
     * sebelum tabel `pengaturan` ada.
     */
    public const PREFIX = 'PPA-ADRO';

    private function format(DocumentType $type, Department $department, int $seq): string
    {
        return sprintf('%s-%s-%s-%02d', Pengaturan::prefix(), $type->code, $department->code, $seq);
    }

    /**
     * Nomor SEMENTARA (saat pembuatan & peninjauan).
     *
     * Mengambil nomor urut TERKECIL yang belum terpakai. Dulu memakai count()+1,
     * tetapi itu bentrok begitu ada dokumen dihapus atau bernomor manual: nomornya
     * tak ikut terhitung padahal masih dipakai, sehingga draft baru bisa memperoleh
     * nomor yang SAMA dengan dokumen yang sudah ada.
     */
    public function generateTemp(DocumentType $type, Department $department): string
    {
        return $this->format($type, $department, $this->firstUnusedSeq(
            $this->usedSequences($type, $department)
        ));
    }

    /**
     * Nomor FINAL (dikunci saat disahkan). Nomor urut TERKECIL yang belum
     * terpakai pada jenis+dept, sehingga dokumen yang baru terbit MENGISI celah
     * alih-alih menambah satu di ujung.
     *
     * `withTrashed()` disengaja: nomor yang pernah terbit ditahan selamanya.
     * Dokumen Berlaku memang tak bisa dihapus — tapi dokumen Tidak Berlaku bisa,
     * dan tanpa ini menghapus arsipnya akan melepas nomornya kembali. Nomor
     * dokumen mutu adalah rujukan yang dipakai di luar sistem (audit, dokumen
     * lain, cetakan di lapangan); mendaur ulangnya membuat satu nomor menunjuk
     * dua dokumen berbeda sepanjang sejarah.
     *
     * SATU pengecualian, sejak Fase F (F2): dokumen yang DIMATIKAN lewat alur
     * nonaktif berjenjang melepas nomornya. Itu keputusan sadar empat orang
     * (pengaju → SH/DH → MD → PJO) bahwa pekerjaannya memang berhenti, bukan
     * penghapusan diam-diam — dan justru itulah gunanya: nomor yang kosong bisa
     * dipakai lagi alih-alih menyisakan celah selamanya. `scopeNomorDitahan`
     * yang menyaringnya.
     */
    public function generateFinal(DocumentType $type, Department $department): string
    {
        $used = Document::withTrashed()
            ->where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            ->whereNotNull('doc_number_final')
            ->nomorDitahan()
            ->pluck('doc_number_final')
            ->map(fn ($n) => $this->seqOf($n))
            ->filter()
            ->all();

        return $this->format($type, $department, $this->firstUnusedSeq($used));
    }

    /**
     * Nomor urut yang sudah terpakai pada jenis+dept untuk kolam SEMENTARA.
     *
     * Dokumen ter-soft-delete SENGAJA tidak ikut: draft yang dibatalkan harus
     * mengembalikan nomornya supaya draft berikutnya mengisi celah itu. Kolom
     * `doc_number_final` tetap dibaca agar draft baru tak pernah menampilkan
     * nomor yang sedang dipakai dokumen Berlaku.
     *
     * `lockForUpdate()` menutup satu-satunya jalan duplikasi yang tersisa: dua
     * GL menekan "Buat" pada detik yang sama akan membaca daftar terpakai yang
     * sama persis, lalu keduanya memilih nomor yang sama. Kuncinya ditahan
     * sampai transaksi pemanggil (DocumentService::createDraft) selesai, jadi
     * yang kedua baru membaca setelah dokumen pertama tersimpan.
     */
    private function usedSequences(DocumentType $type, Department $department): array
    {
        return Document::where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            // Nomor yang sudah dilepas (Fase F) juga bebas dipakai draft baru —
            // kalau hanya kolam FINAL yang melepasnya, draft berikutnya akan
            // tampil bernomor -04 lalu terbit sebagai -03. Nomor yang berubah
            // sendiri di tengah alur persis keluhan yang menghasilkan butir 2.
            ->nomorDitahan()
            ->lockForUpdate()
            ->get(['doc_number', 'doc_number_final'])
            ->flatMap(fn (Document $d) => [$d->doc_number, $d->doc_number_final])
            ->map(fn ($n) => $this->seqOf($n))
            ->filter()
            ->unique()
            ->all();
    }

    /** Nomor urut (angka di akhir) dari sebuah nomor dokumen. */
    private function seqOf(?string $number): int
    {
        return preg_match('/(\d+)$/', (string) $number, $m) ? (int) $m[1] : 0;
    }

    /** Nomor urut terkecil yang belum terpakai. */
    private function firstUnusedSeq(array $used): int
    {
        $seq = 1;
        while (in_array($seq, $used, true)) {
            $seq++;
        }

        return $seq;
    }

    /** Backward-compatible alias (dipakai StoreDocumentRequest/preview). */
    public function generate(DocumentType $type, Department $department): string
    {
        return $this->generateTemp($type, $department);
    }

    /**
     * Nomor bebas yang disarankan saat nomor ketikan orang ternyata bentrok
     * (rencana pra-produksi Fase 5 / butir 4).
     *
     * Kolamnya GABUNGAN kedua kolam, dan sengaja lebih ketat dari keduanya:
     * `withTrashed()` atas KEDUA kolom. Alasannya satu — yang ditawarkan dialog
     * harus PASTI lolos `isUnique()`. `generateFinal()` sendiri tidak cukup: ia
     * hanya membaca `doc_number_final`, jadi ia bisa menyarankan nomor yang
     * sedang dipegang sebuah DRAFT, dan orangnya menekan "Pakai nomor otomatis"
     * hanya untuk ditolak sekali lagi.
     *
     * Ketatnya berbiaya paling satu nomor yang sebenarnya masih bisa dipakai
     * ulang — celah di daftar induk, bukan kebuntuan.
     *
     * Menerima id mentah dan mengembalikan null bila salah satunya tak dikenal:
     * pemanggilnya adalah lapisan validasi, tempat isian memang belum tentu sah.
     */
    public function saranUntuk(mixed $typeId, mixed $departmentId): ?string
    {
        $type = DocumentType::find($typeId);
        $department = Department::find($departmentId);

        if (! $type || ! $department) {
            return null;
        }

        $used = Document::withTrashed()
            ->where('document_type_id', $type->id)
            ->where('department_id', $department->id)
            ->nomorDitahan()
            ->get(['doc_number', 'doc_number_final'])
            ->flatMap(fn (Document $d) => [$d->doc_number, $d->doc_number_final])
            ->map(fn ($n) => $this->seqOf($n))
            ->filter()
            ->unique()
            ->all();

        return $this->format($type, $department, $this->firstUnusedSeq($used));
    }

    /**
     * Nomor manual yang diketik pengguna belum dipakai?
     *
     * Mencerminkan KEDUA kolam persis seperti penomoran otomatis, supaya nomor
     * yang boleh dipilih mesin juga boleh diketik tangan — dan sebaliknya:
     *   - `doc_number` diperiksa TANPA dokumen terhapus (draft yang dibatalkan
     *     mengembalikan nomornya);
     *   - `doc_number_final` diperiksa TERMASUK yang terhapus (nomor yang pernah
     *     terbit ditahan selamanya).
     *
     * Mencampur keduanya jadi satu aturan pernah membuat mesin memilih nomor
     * yang justru ditolak saat diketik orang.
     *
     * Nomor yang sudah DILEPAS (Fase F, F2) juga diabaikan di kedua kolam —
     * tanpa ini, nomor yang baru saja dilepas boleh dipilih mesin tapi ditolak
     * saat diketik tangan, padahal melepasnya persis bertujuan agar ia bisa
     * dipakai lagi.
     */
    public function isUnique(string $number, ?int $ignoreId = null): bool
    {
        $dipakai = fn ($query) => $query
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        return ! $dipakai(Document::where('doc_number', $number)->nomorDitahan())
            && ! $dipakai(Document::withTrashed()->where('doc_number_final', $number)->nomorDitahan());
    }
}
