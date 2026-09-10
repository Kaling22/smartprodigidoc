<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil akses (PLAN-AKSES-v7 Fase 4a, butir 3).
 *
 * Wewenang MENYUSUN dokumen berhenti ditentukan izin spatie semata. Izin
 * `document.create` menempel ke PERAN, jadi seluruh Group Leader memilikinya —
 * padahal di lapangan tidak semua GL menyusun keempat jenis. Lapis kedua ini
 * menyimpan "jenis apa saja" per orang, dikelola Admin dari layar.
 *
 * `users.access_profile_id` sengaja NULLABLE dan tanpa baris "Default" yang
 * di-seed: baris default bisa diganti nama, dihapus, atau disunting sampai
 * artinya berubah — dan saat itu terjadi, SELURUH GL yang belum ditetapkan
 * ikut berubah wewenangnya tanpa seorang pun menyentuhnya. Ketiadaan profil
 * tak bisa disunting. NULL = Tanpa Akses, dan itu nilai bawaan yang disengaja.
 *
 * `nullOnDelete` bukan `cascadeOnDelete`: menghapus profil TIDAK boleh ikut
 * menghapus penggunanya — ia hanya menjatuhkan mereka kembali ke Tanpa Akses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('keterangan')->nullable();
            // Kode DocumentType, mis. ["SOP","IK"]. JSON, bukan tabel pivot:
            // isinya daftar kode pendek yang selalu dibaca utuh, tak pernah
            // di-query per baris, dan tak punya atribut sendiri.
            $table->json('jenis_dibolehkan');
            $table->boolean('boleh_review_jsa')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('access_profile_id')->nullable()->after('department_id')
                ->constrained('access_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['access_profile_id']);
            $table->dropColumn('access_profile_id');
        });

        Schema::dropIfExists('access_profiles');
    }
};
