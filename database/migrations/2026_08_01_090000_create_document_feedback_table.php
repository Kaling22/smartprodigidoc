<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Masukan lapangan atas dokumen yang BERLAKU.
 *
 * Dikirim Non-Staff dari aplikasi mobile (tahan dokumen → "Report"), ditujukan
 * ke pembuat/peninjau dokumennya. Bersifat TAMBAHAN — tak ada tabel lama yang
 * disentuh, dan aturan "Non-Staff read-only atas ISI dokumen" (CLAUDE.md §6)
 * tidak berubah: ini kanal TERPISAH, bukan penyuntingan dokumen.
 *
 * Polanya meniru `attachment_comments`, dengan tambahan alur tindak lanjut:
 * masukan bisa dibaca, diadopsi jadi bahan alasan revisi, atau ditolak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_feedback', function (Blueprint $table) {
            $table->id();

            // Nomor yang dibaca manusia, mis. "MSK-2026-0001". Dipakai aplikasi
            // mobile untuk menampilkan & mencari riwayat masukan pengirimnya.
            $table->string('feedback_number', 30)->unique();

            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();   // pengirim (Non-Staff)

            $table->text('isi');

            /*
            | baru      → belum dilihat peninjau
            | dibaca    → sudah dilihat, belum ditindak
            | diadopsi  → dipakai sebagai bahan alasan revisi
            | ditolak   → ditinjau, diputuskan tidak perlu tindakan
            */
            $table->enum('status', ['baru', 'dibaca', 'diadopsi', 'ditolak'])
                ->default('baru')
                ->index();

            // Balasan untuk si pengirim. Dibiarkan kosong sampai ditindak —
            // orang lapangan berhak tahu masukannya berujung ke mana.
            $table->text('balasan')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users');
            $table->timestamp('replied_at')->nullable();

            // Dokumen revisi yang lahir dari masukan ini (bila diadopsi).
            $table->foreignId('revision_document_id')->nullable()
                ->constrained('documents')->nullOnDelete();

            $table->timestamps();

            // Lencana jumlah masukan per dokumen di halaman "Dokumen Berlaku"
            // membaca lewat kombinasi ini.
            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_feedback');
    }
};
