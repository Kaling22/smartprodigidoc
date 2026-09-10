<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori menu Informasi, dipindahkan dari konstanta ke tabel
 * (PLAN-AKSES-v8 Fase 5b, ketetapan pemilik F/G/H).
 *
 * KENAPA INI BOLEH sedangkan "tambah jenis dokumen inti" tetap dibuang:
 * Informasi tak punya schema JSON, template cetak, wizard, alur tinjau–setuju,
 * maupun render PDF — keempat hal yang membuat SOP/IK/SP/JSA mustahil
 * dibangkitkan dari layar. Yang membedakan satu kategori Informasi dari yang
 * lain hanyalah empat konstanta yang semuanya sudah berbentuk data
 * (Informasi::KATEGORI, IKON, KOLOM_EKSTRA, EKSTENSI), dan `informasi.kategori`
 * memang sudah VARCHAR — bukan ENUM — persis supaya menambah kategori tak
 * menuntut ALTER TABLE. Pekerjaan migration ini karena itu memindahkan data,
 * bukan membangun alur baru.
 *
 * TIGA hal yang membuatnya aman:
 *
 * 1. BARISNYA DI-INSERT DI SINI, bukan di seeder. Berbeda dari tabel
 *    `pengaturan` (Fase 5a) yang bawaannya tinggal di kode: di sana kuncinya
 *    tetap dan terhingga, di sini justru intinya adalah baris yang BISA
 *    bertambah. Tabel kosong berarti menu Informasi kosong, dan itu bukan
 *    "berperilaku seperti sebelumnya" melainkan modul yang lenyap. Datanya
 *    disalin apa adanya dari konstanta — termasuk ejaan campuran "MEMO
 *    External", yang memang ejaan resmi PT PPA dan bukan kelalaian.
 *
 * 2. `slug` UNIQUE dan TAK PERNAH DIUBAH sesudah dibuat. Ia tersimpan di
 *    `informasi.kategori` sebagai teks, bukan foreign key — mengganti slug
 *    berarti memutus setiap baris informasi yang menunjuk ke sana. Yang boleh
 *    diubah dari layar adalah NAMA-nya.
 *
 * 3. TAK ADA kolom `deleted_at` dan tak ada rute hapus. Kategori ditutup lewat
 *    `is_active`, alasannya sama persis dengan departemen: barisnya ditunjuk
 *    data lain. Kategori nonaktif tetap tampil di sidebar dalam keadaan tak
 *    bisa diklik — orang perlu melihat bahwa kategorinya sengaja ditutup,
 *    bukan mendapati menunya lenyap tanpa kabar.
 */
return new class extends Migration
{
    /**
     * Kesepuluh kategori, URUT sesuai daftar pemilik produk — disalin dari
     * Informasi::KATEGORI/IKON/KOLOM_EKSTRA yang digantikan tabel ini.
     *
     * Ditulis LITERAL, bukan membaca konstanta modelnya: migration adalah
     * potret satu saat: ia harus menghasilkan baris yang sama meski
     * konstantanya kelak dibuang — dan memang dibuang di fase ini.
     */
    private const BAWAAN = [
        ['kebijakan', 'KEBIJAKAN', 'bi-journal-bookmark', ['edisi', 'no_revisi']],
        ['memo_external', 'MEMO External', 'bi-envelope-open', []],
        ['memo_internal', 'MEMO Internal', 'bi-envelope', []],
        ['instruksi_ktt', 'INSTRUKSI KTT', 'bi-megaphone', ['edisi', 'no_revisi', 'tanggal_efektif']],
        ['poster', 'POSTER', 'bi-image', []],
        ['msds', 'MSDS', 'bi-droplet-half', []],
        ['bap', 'BAP', 'bi-clipboard-data', []],
        ['sertifikat_sio', 'SERTIFIKAT & SIO', 'bi-patch-check', []],
        ['moc_mprp', 'MOC & MPRP', 'bi-diagram-3', []],
        ['ibpr', 'IBPR', 'bi-shield-exclamation', ['edisi', 'no_revisi', 'tanggal_efektif']],
    ];

    public function up(): void
    {
        Schema::create('informasi_kategori', function (Blueprint $table) {
            $table->id();

            // 32 aksara, sama dengan `informasi.kategori` yang menampungnya.
            $table->string('slug', 32)->unique();
            $table->string('nama');
            $table->string('deskripsi')->nullable();
            $table->string('ikon', 50)->default('bi-info-circle');

            // Kolom tambahan yang DIPAKAI kategori ini — subset dari kolom yang
            // sudah ada di tabel `informasi` (edisi, no_revisi, tanggal_efektif).
            // Ketetapan pemilik G: kolom yang benar-benar baru menuntut
            // migration dan tidak disediakan dari layar.
            $table->json('kolom_json')->nullable();

            $table->boolean('is_active')->default(true);

            // Urutan tampil di sidebar. Bukan `id`: kategori baru boleh
            // disisipkan di tengah tanpa menulis ulang kunci primer.
            $table->unsignedSmallInteger('urutan')->default(0);

            $table->timestamps();
        });

        $sekarang = now();

        DB::table('informasi_kategori')->insert(
            collect(self::BAWAAN)->values()->map(fn ($baris, $i) => [
                'slug' => $baris[0],
                'nama' => $baris[1],
                'ikon' => $baris[2],
                'kolom_json' => json_encode($baris[3]),
                'is_active' => true,
                'urutan' => ($i + 1) * 10,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('informasi_kategori');
    }
};
