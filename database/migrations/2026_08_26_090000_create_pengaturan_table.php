<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setelan sistem (PLAN-AKSES-v8 Fase 5a).
 *
 * Satu tabel kunci–nilai untuk hal-hal yang dulu jadi konstanta di kode:
 * prefix penomoran site, nama site, dan — di Fase 5c — setelan AI. Bentuk
 * kunci–nilai dipilih justru KARENA isinya belum selesai bertambah; kolom
 * tetap per setelan berarti satu migration tiap kali pemilik minta satu
 * saklar baru.
 *
 * `kunci` jadi PRIMARY KEY, bukan id auto-increment berkolom unik: barisnya
 * SELALU dicari dengan namanya dan tak pernah dengan id. Id di sini hanya
 * kolom kedua yang harus dijaga tetap sinkron tanpa seorang pun memakainya.
 *
 * TANPA timestamps. Siapa mengubah apa dan kapan sudah tercatat di
 * `audit_logs` (`pengaturan.site_diubah`) lengkap dengan nilai sebelum &
 * sesudahnya — `updated_at` di sini hanya salinan yang lebih miskin.
 *
 * TIDAK ada baris yang di-seed. Setelan yang belum pernah disentuh HARUS
 * jatuh ke nilai bawaan di kode (`Pengaturan::ambil($kunci, $default)`),
 * supaya pemasangan baru dan seluruh test lama berperilaku persis seperti
 * sebelum tabel ini ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci', 100)->primary();
            $table->text('nilai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
