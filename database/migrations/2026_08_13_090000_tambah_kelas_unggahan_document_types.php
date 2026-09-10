<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kelas jenis dokumen baru: `unggahan` — FK (Formulir Kerja) & PX (Prosedur
 * External). Keduanya TIDAK punya bab: isinya adalah berkas PDF yang diunggah,
 * dan formulirnya hanya nomor/judul/edisi/revisi/tanggal efektif.
 *
 * Penandanya sengaja kolom `class`, BUKAN "schema_json kosong": jenis yang
 * masih MENUNGGU schema (dokumen independen, CLAUDE.md §4) juga berschema
 * kosong, dan ia harus tetap tampil sebagai "belum tersedia" — bukan diam-diam
 * berubah jadi formulir unggah.
 *
 * `ALTER ... MODIFY` mentah, bukan `->change()`: mengubah daftar nilai ENUM
 * adalah satu-satunya perubahan yang perlu, dan menuliskannya apa adanya
 * membuat nilai lamanya terbaca di sini tanpa membuka migration pertama.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `document_types` MODIFY `class` ENUM('inti','independen','lintas','unggahan') NOT NULL DEFAULT 'inti'");
    }

    public function down(): void
    {
        DB::table('document_types')->where('class', 'unggahan')->update(['class' => 'inti']);

        DB::statement("ALTER TABLE `document_types` MODIFY `class` ENUM('inti','independen','lintas') NOT NULL DEFAULT 'inti'");
    }
};
