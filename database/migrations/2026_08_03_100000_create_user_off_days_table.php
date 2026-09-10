<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ketersediaan peninjau: cuti, off day, dinas luar (FITUR-BARU-v4 §6).
 *
 * Ini BUKAN sistem cuti kepegawaian — cuti resmi tetap urusan HCGA. Yang
 * dicatat di sini hanyalah sinyal PENJADWALAN, supaya dokumen tak ditugaskan
 * ke orang yang sedang tak ada di tempat.
 *
 * Karena itu tak ada kolom persetujuan: pengajuan LANGSUNG berlaku (ketetapan
 * pemilik 3 Agustus 2026), dengan konfirmasi di layar sebelum tersimpan.
 *
 * Bersifat TAMBAHAN — tak ada tabel lama yang disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_off_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            /*
            | cuti       → cuti tahunan / izin
            | off_day    → jatah libur roster
            | dinas_luar → di tempat lain, tetap bekerja tapi tak bisa meninjau
            */
            $table->enum('jenis', ['cuti', 'off_day', 'dinas_luar'])->default('cuti');

            // Rentang INKLUSIF di kedua ujung: off sehari = mulai == sampai.
            $table->date('mulai');
            $table->date('sampai');

            $table->string('catatan', 255)->nullable();

            $table->timestamps();

            // Pertanyaan yang selalu ditanyakan: "apakah orang ini off pada
            // tanggal X?" — dijawab lewat kombinasi ini.
            $table->index(['user_id', 'mulai', 'sampai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_off_days');
    }
};
