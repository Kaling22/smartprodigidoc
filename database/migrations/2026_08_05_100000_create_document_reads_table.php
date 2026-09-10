<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelacakan DISTRIBUSI dokumen Berlaku (v5 Fase C).
 *
 * Pertanyaan yang harus dijawab murah: "dari sekian orang yang WAJIB tahu
 * dokumen ini, berapa yang sudah membukanya?" — yaitu COUNT(DISTINCT user) per
 * dokumen. Di atas `audit_logs` yang terus tumbuh, itu berarti pemindaian penuh
 * setiap kali dashboard dibuka. Satu tabel sempit ber-unique(document_id,
 * user_id) menjadikannya satu GROUP BY beruas indeks, dan pencatatannya cukup
 * SATU upsert per kunjungan.
 *
 * Tabel ini MELENGKAPI audit_logs, tidak menggantikannya: audit_logs tetap
 * kronik "apa yang terjadi", tabel ini ringkasan "siapa sudah membaca apa".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // `useCurrent()` pada KEDUANYA: MySQL hanya memberi default otomatis
            // kepada kolom TIMESTAMP pertama, dan yang kedua jatuh ke
            // '0000-00-00' — nilai yang ditolak mode ketat ("Invalid default
            // value"). Lagi pula "sekarang" memang benar: barisnya lahir pada
            // pembacaan pertama.
            $table->timestamp('first_read_at')->useCurrent();
            $table->timestamp('last_read_at')->useCurrent();

            // Dibedakan: MEMBUKA detail dokumen belum tentu MEMBACA isinya.
            // Yang mengunduh/membuka PDF-lah yang benar-benar terpapar isi.
            $table->unsignedInteger('read_count')->default(1);
            $table->unsignedInteger('download_count')->default(0);

            // 'web' | 'mobile' — menjawab "apakah lapangan memakai aplikasinya?"
            $table->string('platform', 10)->default('web');

            // Kunci upsert: satu baris per (dokumen, pengguna) selamanya.
            $table->unique(['document_id', 'user_id']);
            $table->index(['document_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reads');
    }
};
