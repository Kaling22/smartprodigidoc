<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengganti nama status `pending_md` → `verifikasi_md`.
 *
 * Alasan: "pending_md" hanya menyebut SIAPA yang menahan, bukan APA yang sedang
 * terjadi. "Verifikasi MD" langsung terbaca sebagai tahap kerja, dan labelnya
 * sejajar dengan status lain yang berbahasa Indonesia (`sedang_direvisi`).
 *
 * Dikerjakan TIGA LANGKAH karena MySQL menolak enum yang tak memuat nilai yang
 * sedang dipakai baris mana pun:
 *   1. perluas enum agar memuat KEDUA nama,
 *   2. pindahkan barisnya,
 *   3. ciutkan enum, buang nama lama.
 *
 * Mengganti langsung dalam satu ALTER akan menggagalkan migrasi begitu ada satu
 * saja dokumen yang sedang berada di tahap itu — dan saat migration ini ditulis
 * memang ada satu.
 */
return new class extends Migration
{
    private const TETAP = "'draft','waiting_for_review','in_review','rejected',"
        ."'pending_approval','published','sedang_direvisi','obsolete',"
        ."'submitted','needs_revision','archived'";

    private function ubahEnum(string $tambahan): void
    {
        DB::statement(
            'ALTER TABLE `documents` MODIFY `status` ENUM('.$tambahan.','.self::TETAP.') NOT NULL DEFAULT \'draft\''
        );
    }

    public function up(): void
    {
        $this->ubahEnum("'pending_md','verifikasi_md'");
        DB::table('documents')->where('status', 'pending_md')->update(['status' => 'verifikasi_md']);
        $this->ubahEnum("'verifikasi_md'");
    }

    public function down(): void
    {
        $this->ubahEnum("'pending_md','verifikasi_md'");
        DB::table('documents')->where('status', 'verifikasi_md')->update(['status' => 'pending_md']);
        $this->ubahEnum("'pending_md'");
    }
};
