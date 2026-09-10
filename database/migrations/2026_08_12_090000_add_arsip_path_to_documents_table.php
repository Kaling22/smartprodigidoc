<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Butir 0 — dokumen LAMA (arsip): PDF yang sudah terlanjur ada, didaftarkan
 * apa adanya alih-alih diketik ulang ke wizard.
 *
 * SATU kolom, disengaja. `arsip_path !== null` sudah berarti "ini dokumen
 * lama"; kolom penanda kedua hanyalah kolom kedua yang suatu hari tidak
 * sepakat dengan yang pertama. Turunannya (isArsip, nomorLuarPola) dihitung
 * di model — turunan tak bisa basi.
 *
 * Jalurnya relatif terhadap disk `local` (storage/app/private), BUKAN `public`:
 * routes/web.php:53 menyajikan storage/app/public tanpa login, dan dokumen mutu
 * utuh tak boleh bisa diunduh siapa pun yang menebak URL-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('arsip_path')->nullable()->after('doc_number_manual');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('arsip_path');
        });
    }
};
