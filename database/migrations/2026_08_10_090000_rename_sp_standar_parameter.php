<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mengganti nama jenis SP: "Standar Produksi" → "Standar Parameter".
 *
 * KODE `SP` TIDAK BERUBAH. Penomoran dokumen dibangun dari `document_types.code`
 * (DocumentNumberService::PREFIX + $type->code), jadi seluruh nomor
 * PPA-ADRO-SP-… yang sudah terbit tetap sah — yang berganti hanya namanya.
 *
 * DUA nilai harus ikut, bukan satu:
 *   1. `name`            — nama di layar (daftar jenis, dropdown, legenda donat).
 *   2. `schema_json.doc_type_label` — judul di KOP dan COVER hasil cetak.
 * Melewatkan yang kedua membuat layar berbunyi "Standar Parameter" sementara
 * PDF-nya masih "STANDARD PRODUKSI"; keduanya dibaca dari baris yang sama tapi
 * dari kolom yang berbeda.
 *
 * Sekalian membetulkan ejaan lama "STANDARD" (Inggris) menjadi "STANDAR"
 * (Indonesia) — sisa salin-tempel dari label SOP di sebelahnya.
 *
 * Retroaktif dengan sendirinya: label tak pernah disalin ke record dokumen
 * (`document_versions.snapshot_json` pun tak memuatnya) dan PDF selalu dirender
 * ulang dari schema, jadi dokumen SP lama ikut tercetak dengan nama baru tanpa
 * perlu menyentuh satu baris `documents` pun.
 *
 * TIDAK menyentuh `document_contents`: kalimat "Standar Produksi adalah dokumen
 * yang…" di bab Definisi/Ruang Lingkup adalah tulisan penyusun yang sudah
 * disahkan. Mengubahnya di sini berarti menulis ulang dokumen resmi tanpa lewat
 * alur revisi. Biarkan pemiliknya yang memperbarui lewat Ajukan Revisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubah('Standar Parameter', 'STANDAR PARAMETER');
    }

    public function down(): void
    {
        $this->ubah('Standar Produksi', 'STANDARD PRODUKSI');
    }

    private function ubah(string $nama, string $labelCetak): void
    {
        // COALESCE menjaga baris yang schema_json-nya NULL: JSON_SET(NULL, …)
        // mengembalikan NULL dan akan MENGHAPUS seluruh schema, bukan menambah
        // satu kunci ke dalamnya.
        $sql = <<<'SQL'
            UPDATE `document_types`
               SET `name` = ?,
                   `schema_json` = JSON_SET(COALESCE(`schema_json`, JSON_OBJECT()), '$.doc_type_label', ?)
             WHERE `code` = 'SP'
        SQL;

        DB::statement($sql, [$nama, $labelCetak]);
    }
};
