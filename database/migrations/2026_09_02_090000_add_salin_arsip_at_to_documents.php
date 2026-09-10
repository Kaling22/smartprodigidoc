<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda asal-usul draft "Salin ke Web" (rencana pra-produksi Fase 2, butir 7).
 *
 * Kolom, BUKAN bendera turunan: draft hasil salin harus bisa dibedakan dari
 * revisi Tipe B biasa atas dokumen unggahan, dan keduanya punya keadaan yang
 * sama persis (`revises_document_id` menunjuk dokumen ber-`arsip_path`). Yang
 * membedakan hanya siapa yang membuatnya dan kenapa — fakta yang tak bisa
 * dihitung ulang dari keadaan lain, jadi harus disimpan saat kejadian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->timestamp('salin_arsip_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('salin_arsip_at');
        });
    }
};
