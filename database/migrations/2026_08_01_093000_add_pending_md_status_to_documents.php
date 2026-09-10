<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah status `pending_md` — tahap peninjauan Management Development.
 *
 * Alurnya jadi:
 *   in_review → **pending_md** → pending_approval → published
 *
 * MD memeriksa SISTEMATIKA PENULISAN (typo, salah tulis, kalimat tak sesuai),
 * berbeda dari SH/DH yang memeriksa SUBSTANSI. Hanya berlaku untuk dokumen yang
 * persetujuannya di PJO — saat ini SOP. IK/SP yang disetujui SH/DH sendiri
 * tidak melewati tahap ini.
 *
 * ---
 * Kenapa status baru, bukan tabel `document_stages` seperti rencana di
 * HANDOVER-MIGRASI.md:
 *
 * Rencana itu disusun saat bentuk MD belum diketahui, sehingga dibuat sefleksibel
 * mungkin. Setelah pemilik menetapkan bentuknya, ternyata MD **tidak dipilih**
 * per dokumen, posisinya **tetap**, dan hanya untuk **satu jenis** — tak satu pun
 * membutuhkan keluwesan tabel tahapan. Satu status sudah memodelkannya dengan
 * jujur, dan tidak membongkar ReviewController/ApprovalController/Resolver
 * beserta 83 test yang sudah hijau.
 *
 * Kalau kelak muncul tahap ke-4 yang benar-benar dinamis, barulah refactor
 * tabel tahapan itu sepadan.
 *
 * ---
 * CATATAN: ini kali pertama skema SmartPro SENGAJA berbeda dari project lama.
 * Paritas migrasi (Tahap 0–6) sudah terbukti dan selesai; menambah fitur baru
 * memang harus menyimpang. Impor `docs/data-migrasi.sql` tetap aman karena tak
 * ada baris lama yang bernilai `pending_md`.
 */
return new class extends Migration
{
    /** Delapan status alur + tiga warisan v1/v2 yang sengaja dipertahankan. */
    private const LAMA = "'draft','waiting_for_review','in_review','rejected',"
        ."'pending_approval','published','sedang_direvisi','obsolete',"
        ."'submitted','needs_revision','archived'";

    private const BARU = "'draft','waiting_for_review','in_review','rejected',"
        ."'pending_md','pending_approval','published','sedang_direvisi','obsolete',"
        ."'submitted','needs_revision','archived'";

    public function up(): void
    {
        // MySQL tak punya "ALTER TYPE"; enum harus ditulis ulang utuh.
        // Nilai lama TIDAK dihapus, jadi tak ada baris yang perlu dipindahkan.
        DB::statement('ALTER TABLE `documents` MODIFY `status` ENUM('.self::BARU.') NOT NULL DEFAULT \'draft\'');
    }

    public function down(): void
    {
        // Kembalikan dokumen yang tertahan di MD ke tahap sebelumnya, kalau tidak
        // MySQL menolak enum yang tak memuat nilai yang sedang dipakai.
        DB::table('documents')->where('status', 'pending_md')->update(['status' => 'in_review']);

        DB::statement('ALTER TABLE `documents` MODIFY `status` ENUM('.self::LAMA.') NOT NULL DEFAULT \'draft\'');
    }
};
