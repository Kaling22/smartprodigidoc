<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berkas ASLI dokumen lama (PLAN-REVISI-v6 Fase H).
 *
 * Sejak lembar CATATAN REVISI disisipkan di depan berkas unggahan, `arsip_path`
 * menunjuk berkas HASIL GABUNG — halaman awalnya sudah dipotong. Kolom ini
 * memegang berkas unggahan yang utuh, sehingga pemotongan bisa diulang dengan
 * angka lain atau dibatalkan sama sekali. Tanpa kolom ini, satu salah ketik
 * pada "potong halaman awal" berarti halaman dokumen hilang selamanya.
 *
 * NULL = berkasnya belum pernah digabung; `arsip_path` sendirilah yang asli.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('arsip_path_asli')->nullable()->after('arsip_path');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('arsip_path_asli');
        });
    }
};
