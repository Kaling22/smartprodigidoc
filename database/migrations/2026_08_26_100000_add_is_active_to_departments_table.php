<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saklar aktif/nonaktif departemen (PLAN-AKSES-v8 Fase 5b).
 *
 * MENGGANTIKAN "hapus departemen" yang sengaja dibuang (§9.1): `users.
 * department_id` dan `documents.department_id` menunjuk ke tabel ini, jadi
 * tak ada penghapusan yang aman — yang tersisa selalu pengguna tanpa
 * departemen dan dokumen tanpa asal.
 *
 * Nonaktif berarti SATU hal saja: departemen itu tak lagi ditawarkan pada
 * dropdown pembuatan dokumen & pendaftaran akun. Dokumen, pengguna, nomor,
 * dan seluruh riwayatnya utuh dan tetap terbaca.
 *
 * Default `true` supaya tujuh departemen yang sudah ada berperilaku persis
 * seperti sebelum kolom ini ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('alias');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
