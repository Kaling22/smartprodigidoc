<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Kategori menu Informasi (PLAN-AKSES-v8 Fase 5b).
 *
 * Dulu empat konstanta di App\Models\Informasi; sekarang baris tabel, supaya
 * Admin bisa menambah kategori dari layar tanpa rilis kode. Yang TIDAK ikut
 * pindah: daftar ekstensi berkas (Informasi::EKSTENSI) — tak ada layar yang
 * mengubahnya, jadi memindahkannya berarti satu kolom yang tak pernah disetel
 * siapa pun.
 *
 * DUA aturan yang menjaganya:
 *
 * 1. `slug` TAK PERNAH BERUBAH. Ia tersimpan sebagai TEKS di
 *    `informasi.kategori` (bukan foreign key — lihat migration tabel
 *    `informasi`), jadi mengubahnya memutus setiap dokumen yang menunjuk ke
 *    sana tanpa satu pun galat yang terlihat: barisnya cuma berhenti muncul.
 *    Yang boleh diubah dari layar adalah `nama`.
 *
 * 2. TAK ADA HAPUS. Kategori ditutup dengan `is_active = false`; ia tetap
 *    tampil di sidebar dalam keadaan tak bisa diklik, mengikuti pola jenis
 *    dokumen yang belum tersedia. Menghilangkannya diam-diam akan membuat
 *    dokumen yang masih ada seolah tak pernah ada.
 */
class InformasiKategori extends Model
{
    protected $table = 'informasi_kategori';

    protected $fillable = ['slug', 'nama', 'deskripsi', 'ikon', 'kolom_json', 'is_active', 'urutan'];

    protected $casts = [
        'kolom_json' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Kolom tambahan yang BOLEH dipilih Admin — ketetapan pemilik G
     * (PLAN-AKSES-v8 §2): hanya kolom yang sudah ada di tabel `informasi`.
     *
     * `nomor` & `judul` tak ada di sini karena keduanya SELALU dipakai; kolom
     * di luar ketiga ini menuntut migration dan sengaja tidak disediakan dari
     * layar — menawarkannya berarti menjanjikan kotak isian yang tak punya
     * tempat menyimpan isinya.
     */
    public const KOLOM_TERSEDIA = [
        'edisi' => 'Edisi',
        'no_revisi' => 'No. Revisi',
        'tanggal_efektif' => 'Tanggal Efektif',
    ];

    /**
     * Ingatan seumur-request, ber-key slug dan URUT tampil.
     *
     * Wajib ada: sidebar dirender di SETIAP halaman dan mengulang seluruh
     * kategori, sementara `labelKategori()` dipanggil sekali per baris di
     * beberapa daftar. Tanpa ini satu halaman bisa menjalankan belasan query
     * untuk satu daftar sepuluh baris yang tak berubah sepanjang request.
     *
     * Sengaja BUKAN Cache: daftarnya diubah dari layar Master Data, dan cache
     * lintas-request menuntut pembuangan yang harus diingat di setiap jalur
     * penyimpanan. Satu query per request sudah menyelesaikan masalahnya.
     */
    private static ?Collection $ingatan = null;

    /** @return Collection<string, self> seluruh kategori, urut tampil, ber-key slug */
    public static function semua(): Collection
    {
        return self::$ingatan ??= static::orderBy('urutan')->orderBy('id')->get()->keyBy('slug');
    }

    /** @return Collection<string, self> kategori AKTIF saja, urut tampil */
    public static function aktif(): Collection
    {
        return self::semua()->filter->is_active;
    }

    public static function cari(?string $slug): ?self
    {
        return self::semua()->get((string) $slug);
    }

    /**
     * Buang ingatan seumur-request.
     *
     * Dipanggil sesudah setiap penyimpanan di layar Master Data (tanpa itu
     * halaman yang menyimpan masih menampilkan daftar lama), dan oleh test —
     * properti statis tak ikut di-rollback bersama transaksi basis data.
     */
    public static function lupakanIngatan(): void
    {
        self::$ingatan = null;
    }

    /** Kolom tambahan yang dipakai kategori ini, disaring ke yang benar-benar ada. */
    public function kolom(): array
    {
        return array_values(array_intersect(
            array_keys(self::KOLOM_TERSEDIA),
            (array) ($this->kolom_json ?? [])
        ));
    }
}
