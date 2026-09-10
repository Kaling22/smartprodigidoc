<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Butir 3 — menu Informasi: kebijakan, memo, poster, MSDS, BAP, sertifikat,
 * MOC/MPRP, IBPR, instruksi KTT. Upload-only, tanpa alur tinjau–setuju,
 * dibaca SELURUH akun aktif lintas 7 departemen.
 *
 * SATU tabel + kolom `kategori`, bukan sepuluh tabel. Ini bukan tebakan:
 * kesepuluh tabel di sistem lama (`tb_kebijakans`, `tb_memos`, …) berbeda
 * HANYA pada kolom opsional mana yang dipakai — nomor, judul, dan berkas
 * dimiliki semuanya. Sepuluh tabel berarti sepuluh model, sepuluh controller,
 * dan sepuluh set view untuk perbedaan nol.
 *
 * `kategori` sengaja VARCHAR, bukan ENUM seperti draf rencana: menambah
 * kategori pada kolom ENUM menuntut ALTER TABLE di server produksi, sedangkan
 * daftar sahnya toh sudah ditegakkan Informasi::KATEGORI + Form Request. Yang
 * dijaga database adalah bentuk datanya; yang dijaga aplikasi adalah nilainya.
 *
 * `(kategori, nomor)` SENGAJA bukan UNIQUE: satu nomor kebijakan akan punya
 * beberapa baris lintas edisi/revisi — versi lama disimpan sebagai riwayat
 * (`berlaku = false`), tidak dihapus. Yang unik adalah "satu nomor, satu baris
 * BERLAKU", dan itu dijaga alur Perbarui di controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informasi', function (Blueprint $table) {
            $table->id();
            $table->string('kategori', 32)->index();
            $table->string('nomor');
            $table->string('judul');

            // Ketiganya nullable karena hanya SEBAGIAN kategori memakainya
            // (KEBIJAKAN: edisi+revisi; INSTRUKSI KTT & IBPR: ketiganya;
            // tujuh sisanya tak satu pun). Aturan "kolom mana wajib" hidup di
            // Informasi::KOLOM_EKSTRA + Form Request, bukan di skema — kalau
            // ditegakkan di sini, menambah kategori berarti migrasi lagi.
            $table->unsignedInteger('edisi')->nullable();
            $table->unsignedInteger('no_revisi')->nullable();
            $table->date('tanggal_efektif')->nullable();

            // Jalur relatif di disk `local` (storage/app/private) — BUKAN
            // `public`, yang disajikan routes/web.php tanpa login sama sekali.
            $table->string('file_path');
            $table->string('file_mime', 100);

            // false = riwayat. Versi lama tak pernah dihapus saat diperbarui;
            // ia hanya berhenti tampil di daftar utama.
            $table->boolean('berlaku')->default(true)->index();

            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Daftar per kategori & pencarian riwayat satu nomor — dua query
            // yang benar-benar dijalankan halaman ini.
            $table->index(['kategori', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('informasi');
    }
};
