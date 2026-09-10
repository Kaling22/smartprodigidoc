<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saklar bantuan AI per akun Management Development.
 *
 * Pemilik meminta Admin bisa MEMATIKAN AI di tahap MD (lewat tombol Konfigurasi
 * di section akun MD). Disimpan per akun, bukan global, supaya Admin bisa
 * mematikannya untuk satu orang saja — mis. saat mengevaluasi mutu saran AI
 * tanpa mengganggu peninjau lain.
 *
 * Kolom ini hanya bermakna bagi akun MD; untuk akun lain nilainya diabaikan.
 * Sengaja TIDAK dibuat tabel pengaturan tersendiri — satu saklar boolean belum
 * sepadan dengan tabel baru, dan menaruhnya di `users` membuat halaman
 * manajemen akun cukup membaca satu tabel.
 *
 * Default `true`: AI membantu kecuali sengaja dimatikan. AI tetap TIDAK pernah
 * memutuskan sendiri — reviewer yang mengadopsi/mengedit/menolak (CLAUDE.md §12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('ai_review_enabled')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_review_enabled');
        });
    }
};
