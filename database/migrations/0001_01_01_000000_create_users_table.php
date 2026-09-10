<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel users dalam bentuk FINAL.
 *
 * Di project lama kolomnya bertambah lewat 3 migration terpisah
 * (`add_username_and_phone`, `add_photo_path`, `add_jabatan_diajukan`).
 * Semuanya dilebur ke sini — urutan kolom dijaga PERSIS sama agar skema tetap
 * identik dengan project lama (pembanding: docs/schema-baseline.sql).
 *
 * Catatan penting soal DUA kolom jabatan:
 *   - `jabatan`          = KUNCI ALUR (staff|group_leader|section_head|
 *                          departemen_head|pimpinan). Dibaca di banyak tempat
 *                          — JANGAN diisi teks bebas.
 *   - `jabatan_diajukan` = jabatan yang DIKETIK sendiri pendaftar (mis.
 *                          "Magang"). Murni informasi bagi penyetuju akun;
 *                          tidak menentukan hak akses apa pun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable()->unique();
            $table->string('nrp')->nullable()->unique();          // Nomor Registrasi Pegawai — dipakai untuk login
            $table->string('jabatan')->nullable();                // kunci alur, bukan teks bebas
            $table->string('jabatan_diajukan', 100)->nullable();  // isian pendaftar (informasi saja)
            $table->string('nomor_hp')->nullable();
            $table->string('email')->nullable()->unique();        // opsional: login memakai NRP
            $table->string('photo_path')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('department_id')->nullable()->index();   // PJO sengaja TANPA departemen
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending')->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
