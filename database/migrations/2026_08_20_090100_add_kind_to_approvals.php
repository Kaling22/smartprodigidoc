<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak keputusan NONAKTIF menumpang tabel `approvals` yang sudah ada
 * (PLAN-REVISI-v6 Fase F) — nol tabel baru: bentuk datanya sama persis
 * (siapa, memutuskan apa, dengan alasan apa, kapan), dan halaman "Alasan
 * Dokumen" (Fase C) langsung membacanya tanpa satu sumber tambahan.
 *
 * `kind` yang membedakan: 'pengesahan' = jejak alur pengesahan dokumen,
 * 'nonaktif' = pengajuan & keputusan mematikan dokumen. Default 'pengesahan'
 * supaya seluruh baris lama tetap berarti apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->enum('kind', ['pengesahan', 'nonaktif'])->default('pengesahan')->after('approver_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
