<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Tgl. Revisi" pada kop cetak, sebagai DATA — bukan lagi turunan `updated_at`.
 *
 * Kop membacanya dari `updated_at` (`documents/print/_kop.blade.php`), dan itu
 * benar selama dokumen benar-benar direvisi hari itu juga. Ia meleset justru
 * pada dokumen yang paling butuh tanggal betul: dokumen LAMA yang diketik ulang
 * lewat "Salin ke wizard" — tanggal revisinya adalah tanggal yang tercetak di
 * berkas aslinya, bukan waktu pengetikan ulangnya. Dan `updated_at` tak bisa
 * dipakai menyimpannya: setiap penyimpanan berikutnya menimpanya diam-diam.
 *
 * Nullable dan tanpa nilai bawaan: dokumen yang kolomnya kosong tetap dicetak
 * persis seperti sebelumnya (kop jatuh ke `updated_at`), sehingga seluruh PDF
 * yang sudah terbit tidak berubah satu byte pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->date('tanggal_revisi')->nullable()->after('salin_arsip_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('tanggal_revisi');
        });
    }
};
