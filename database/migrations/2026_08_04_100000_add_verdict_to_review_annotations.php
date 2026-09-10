<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanda Sesuai / Perlu Revisi per Tindakan Pengendalian pada peninjauan JSA.
 *
 * Dua perubahan, keduanya MELONGGARKAN — nol data dihapus, nol tabel baru:
 *
 * 1. Kolom `verdict` BARU dan nullable. NULL berarti "item ini memang tak
 *    dinilai" — berlaku untuk Langkah Kerja, Bahaya & Risiko, dan untuk seluruh
 *    SOP/IK/SP yang tetap hanya diberi catatan. Karena itu semua baris lama
 *    tetap sah tanpa disentuh.
 *
 * 2. `comment` tak lagi wajib. Peninjau boleh menandai sebuah pengendalian
 *    "Perlu Revisi" TANPA menulis catatan; tanpa pelonggaran ini baris anotasi
 *    seperti itu mustahil disimpan.
 *
 * Tanda ini TIDAK ikut tercetak: kolom "Beri tanda" pada PDF JSA tetap kotak
 * kosong untuk dicentang tangan (HANDOVER-SESI-BARU §6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_annotations', function (Blueprint $table) {
            $table->enum('verdict', ['sesuai', 'perlu_revisi'])->nullable()->after('item_ref');
        });

        // Dipisah dari blok di atas: MySQL menolak MODIFY dan ADD COLUMN dalam
        // satu pernyataan ALTER yang dibangun Laravel.
        Schema::table('review_annotations', function (Blueprint $table) {
            $table->text('comment')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('review_annotations', function (Blueprint $table) {
            $table->dropColumn('verdict');
        });

        Schema::table('review_annotations', function (Blueprint $table) {
            $table->text('comment')->nullable(false)->change();
        });
    }
};
