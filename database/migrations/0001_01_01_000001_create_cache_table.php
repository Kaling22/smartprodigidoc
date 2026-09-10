<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel cache bawaan Laravel.
 *
 * `expiration` SENGAJA tanpa index, mengikuti project lama. Templat Laravel
 * yang lebih baru menambahkannya (mempercepat pembersihan cache kedaluwarsa),
 * tapi di sini index itu dibuang agar skema IDENTIK dengan project lama —
 * gerbang verifikasi Tahap 2 mensyaratkan diff yang benar-benar bersih.
 * Menambahkannya kembali = perbaikan opsional SETELAH paritas terbukti;
 * tabel ini transien (tak ada data yang dimigrasikan), jadi aman kapan pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
