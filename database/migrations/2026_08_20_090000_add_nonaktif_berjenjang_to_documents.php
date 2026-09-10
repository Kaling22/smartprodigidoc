<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nonaktif BERJENJANG (PLAN-REVISI-v6 Fase F, keputusan F1 & F2).
 *
 * Sebelum ini "Tidak Berlaku" adalah SEKALI KLIK oleh satu orang. Sekarang
 * pengajuannya berjalan GL → SH/DH → MD → PJO (tahap MD dilewati bila MD
 * sendiri yang mengajukan), dan dokumen yang benar-benar dimatikan MELEPAS
 * nomornya supaya bisa dipakai dokumen baru.
 *
 * Satu status + satu kolom tahap, BUKAN tiga status — preseden `verifikasi_md`
 * (2026_08_01_093000): ketiga tahap muncul di menu yang SAMA dan disaring oleh
 * siapa yang membukanya. Tiga status berarti tiga nilai enum, tiga label, tiga
 * entri STATUS_META, dan satu percabangan tambahan di setiap penyaring status
 * yang sudah ada.
 *
 * Enum diubah TIGA LANGKAH pada down() (MySQL menolak enum yang tak memuat
 * nilai yang sedang dipakai baris mana pun): pindahkan barisnya dulu ke
 * `published`, baru enumnya diciutkan.
 */
return new class extends Migration
{
    private const TETAP = "'draft','waiting_for_review','in_review','rejected',"
        ."'verifikasi_md','pending_approval','published','sedang_direvisi','obsolete',"
        ."'submitted','needs_revision','archived'";

    private function ubahEnum(string $tambahan): void
    {
        DB::statement(
            'ALTER TABLE `documents` MODIFY `status` ENUM('
            .($tambahan ? $tambahan.',' : '').self::TETAP
            .') NOT NULL DEFAULT \'draft\''
        );
    }

    public function up(): void
    {
        $this->ubahEnum("'menunggu_nonaktif'");

        Schema::table('documents', function (Blueprint $table) {
            // Tahap yang sedang berjalan. NULL = tak ada pengajuan nonaktif.
            $table->enum('nonaktif_tahap', ['sh', 'md', 'pjo'])->nullable()->after('status');
            // Inisiator — menentukan apakah tahap MD ikut dilewati.
            $table->foreignId('nonaktif_oleh')->nullable()->after('nonaktif_tahap');
            // SEBAB dokumen jadi Tidak Berlaku. Dibutuhkan Fase E (kolom "Sebab"
            // di daftar) dan Fase G (rollback), dan yang paling penting: hanya
            // 'dinonaktifkan' yang MELEPAS nomor dokumennya.
            $table->enum('obsolete_reason', ['revisi', 'dinonaktifkan'])->nullable()->index()->after('nonaktif_oleh');
        });

        // Backfill: seluruh obsolete yang sudah ada lahir dari REVISI — nonaktif
        // berjenjang baru ada sejak migrasi ini. Tanpa backfill, arsip lama
        // bernilai NULL dan tak bisa dibedakan dari dokumen yang dimatikan.
        DB::table('documents')->where('status', 'obsolete')->update(['obsolete_reason' => 'revisi']);
    }

    public function down(): void
    {
        DB::table('documents')->where('status', 'menunggu_nonaktif')->update(['status' => 'published']);

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['nonaktif_tahap', 'nonaktif_oleh', 'obsolete_reason']);
        });

        $this->ubahEnum('');
    }
};
