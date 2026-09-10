<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distribusi menu INFORMASI (PLAN-REVISI-v6 Fase D / butir 4).
 *
 * Menu Informasi sejauh ini tak terukur: poster K3 dan kebijakan diunggah,
 * lalu tak ada yang tahu apakah orangnya membaca. Pertanyaannya sama persis
 * dengan `document_reads` — "dari sekian orang yang wajib tahu, berapa yang
 * sudah membukanya?" — jadi bentuk tabelnya pun sama persis, sampai ke nama
 * kolomnya, supaya kedua sumber bisa dilayani satu partial pita cakupan.
 *
 * Tabel TERPISAH, bukan kolom `sumber` di `document_reads`: kunci asingnya
 * menunjuk tabel yang berbeda, dan menyatukannya berarti membuang integritas
 * referensial (satu dari dua FK selalu NULL) demi menghemat satu tabel sempit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informasi_reads', function (Blueprint $table) {
            $table->id();

            // Nama tabelnya `informasi` (tunggal & jamak sama), jadi tebakan
            // Laravel (`informasis`) harus ditimpa di sini.
            $table->foreignId('informasi_id')->constrained('informasi')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // useCurrent() pada KEDUANYA — MySQL hanya memberi default otomatis
            // ke kolom TIMESTAMP pertama; yang kedua jatuh ke '0000-00-00' dan
            // ditolak mode ketat. Lihat migrasi document_reads.
            $table->timestamp('first_read_at')->useCurrent();
            $table->timestamp('last_read_at')->useCurrent();

            $table->unsignedInteger('read_count')->default(1);
            $table->unsignedInteger('download_count')->default(0);
            $table->string('platform', 10)->default('web');

            $table->unique(['informasi_id', 'user_id']);
            $table->index(['informasi_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informasi_reads');
    }
};
