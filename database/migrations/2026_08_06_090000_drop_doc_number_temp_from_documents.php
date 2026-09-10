<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buang `doc_number_temp` — kolom ketiga pada penomoran yang tahapnya hanya DUA.
 *
 * Tahap penomoran SmartPro ada dua: nomor SEMENTARA selama dokumen masih disusun
 * & ditinjau, lalu nomor FINAL yang dikunci saat disahkan (CLAUDE.md §8). Yang
 * memegang keduanya cukup `doc_number` dan `doc_number_final`.
 *
 * `doc_number_temp` tak pernah membawa nilai yang berbeda: pada draft baru ia
 * SALINAN `doc_number` (DocumentService::createDraft menulis nilai yang sama ke
 * dua kolom), dan pada draft revisi ia NULL karena hanya `doc_number` yang
 * diwarisi dari versi lama. Survei basis data pengembangan (35 dokumen)
 * mengonfirmasi: 11 baris NULL, 0 baris berisi nilai yang berbeda.
 *
 * Menyimpannya berarti setiap pembaca kode harus menebak mana dari tiga kolom
 * yang berlaku, dan setiap penulis kode harus ingat menulis ke dua-duanya —
 * `ApprovalController` sempat memakai `doc_number_temp ?? doc_number` justru
 * karena keraguan itu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('doc_number_temp');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('doc_number_temp')->nullable()->index()->after('doc_number');
        });

        // Dipulihkan dengan aturan yang SAMA seperti saat ia masih ditulis:
        // salinan `doc_number` bagi dokumen yang bukan revisi, NULL bagi draft
        // revisi (yang mewarisi nomor versi lama lewat `revises_document_id`).
        DB::table('documents')->whereNull('revises_document_id')
            ->update(['doc_number_temp' => DB::raw('doc_number')]);
    }
};
