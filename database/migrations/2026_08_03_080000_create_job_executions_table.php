<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pelaksanaan pekerjaan menggunakan formulir JSA berlaku.
 *
 * Setiap record mewakili satu sesi kerja di lapangan yang mengacu
 * pada JSA yang sudah disahkan. Isi analisa (langkah + bahaya +
 * pengendalian) disimpan sebagai snapshot JSON agar riwayat tetap
 * akurat meski JSA kemudian direvisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_executions', function (Blueprint $table) {
            $table->id();

            // JSA yang dijadikan acuan — harus berstatus published.
            $table->foreignId('document_id')->constrained('documents');
            // Pelaksana pekerjaan (user yang login di mobile).
            $table->foreignId('user_id')->constrained('users');

            $table->string('nama_pekerjaan');
            $table->string('lokasi');
            $table->date('tanggal_pelaksanaan');

            // Snapshot analisa JSA saat pekerjaan dimulai — antisipasi revisi JSA.
            $table->json('analisa_snapshot');

            $table->enum('status', ['berlangsung', 'selesai', 'dibatalkan'])->default('berlangsung');
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_executions');
    }
};
