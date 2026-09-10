<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Informasi (butir 3): dokumen pendukung yang TIDAK melewati alur mutu —
 * kebijakan, memo, poster, MSDS, BAP, sertifikat, MOC/MPRP, IBPR, instruksi KTT.
 *
 * Bedanya dengan `Document` bukan sekadar tabel: Informasi tak punya wizard,
 * tak punya peninjau/penyetuju, tak punya nomor terbitan, dan terbuka bagi
 * SELURUH akun aktif lintas 7 departemen. Ia diunggah, dibaca, dan diperbarui.
 */
class Informasi extends Model
{
    use SoftDeletes;

    protected $table = 'informasi';

    protected $fillable = [
        'kategori', 'nomor', 'judul', 'edisi', 'no_revisi', 'tanggal_efektif',
        'file_path', 'file_mime', 'berlaku', 'uploaded_by',
    ];

    protected $casts = [
        'berlaku' => 'boolean',
        'tanggal_efektif' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Kategori — sekarang DATA, bukan konstanta (PLAN-AKSES-v8 Fase 5b)
    |--------------------------------------------------------------------------
    |
    | KATEGORI, IKON, dan KOLOM_EKSTRA dulu tiga konstanta di kelas ini; kini
    | ketiganya baris di tabel `informasi_kategori`, supaya Admin bisa menambah
    | kategori dari layar Master Data tanpa rilis kode. Method di bawah adalah
    | pintu yang SAMA seperti dulu — pemanggilnya tak perlu tahu asalnya
    | berubah, dan tak satu pun view menyentuh model kategorinya langsung.
    |
    | EKSTENSI sengaja TETAP konstanta: tak ada layar yang mengubahnya, jadi
    | memindahkannya hanya menghasilkan satu kolom yang tak pernah disetel
    | siapa pun. Kategori baru memakai EKSTENSI_BAWAAN (PDF).
    */

    /**
     * Seluruh kategori sebagai [slug => nama], URUT tampil.
     *
     * @param  bool  $aktifSaja  true untuk jalur yang MENULIS (formulir unggah,
     *                           daftar kategori di API) — kategori yang ditutup
     *                           tak boleh ditawarkan sebagai tujuan unggahan
     *                           baru. Jalur yang MEMBACA data lama (saringan
     *                           distribusi) memakai daftar penuh, kalau tidak
     *                           dokumen yang sudah ada jadi tak tersaring.
     * @return array<string, string>
     */
    public static function kategori(bool $aktifSaja = false): array
    {
        $daftar = $aktifSaja ? InformasiKategori::aktif() : InformasiKategori::semua();

        return $daftar->map->nama->all();
    }

    /** Slug kategori pertama yang aktif — tujuan bawaan menu Informasi. */
    public static function kategoriBawaan(): ?string
    {
        return InformasiKategori::aktif()->keys()->first();
    }

    /** Ikon Bootstrap kategori (CLAUDE.md §13: nol emoji). */
    public static function ikon(?string $kategori): string
    {
        return InformasiKategori::cari($kategori)?->ikon ?? 'bi-info-circle';
    }

    /**
     * Jenis berkas yang diterima per kategori.
     *
     * POSTER memang gambar — memaksanya jadi PDF berarti meminta orang
     * mengonversi tiap poster sebelum mengunggahnya, dan hasilnya tampil lebih
     * buruk daripada gambar aslinya. Sisanya dokumen resmi bertanda tangan: PDF.
     */
    public const EKSTENSI = [
        'poster' => ['pdf', 'jpg', 'jpeg', 'png'],
    ];

    public const EKSTENSI_BAWAAN = ['pdf'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Kolom tambahan yang dipakai kategori ini — menentukan isi formulir & tabel. */
    public static function kolomEkstra(?string $kategori): array
    {
        return InformasiKategori::cari($kategori)?->kolom() ?? [];
    }

    public static function ekstensi(?string $kategori): array
    {
        return self::EKSTENSI[(string) $kategori] ?? self::EKSTENSI_BAWAAN;
    }

    /** Nama kategori sebagaimana tercetak di layar; slug tak dikenal tampil apa adanya. */
    public static function labelKategori(?string $kategori): string
    {
        return InformasiKategori::cari($kategori)?->nama ?? (string) $kategori;
    }

    /** Kategori ini memakai kolom $kolom? Dipakai formulir & Form Request. */
    public function punyaKolom(string $kolom): bool
    {
        return in_array($kolom, self::kolomEkstra($this->kategori), true);
    }

    /** Versi yang sedang berlaku (bukan riwayat). */
    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->where('berlaku', true);
    }

    /**
     * Seluruh baris bernomor sama dalam kategori sama — versi berlaku + seluruh
     * riwayatnya.
     *
     * Dipusatkan di sini karena TIGA tempat memakainya dan ketiganya harus
     * sepakat: penolakan nomor kembar saat Tambah, penurunan versi lama saat
     * Perbarui, dan penghapusan "seluruhnya termasuk riwayat". Kalau salah satu
     * memakai definisi berbeda, gejalanya adalah dua baris berlaku dengan nomor
     * yang sama — dan itu baru ketahuan saat orang membuka berkas yang salah.
     */
    public function scopeSenomor(Builder $query, string $kategori, string $nomor): Builder
    {
        return $query->where('kategori', $kategori)->where('nomor', $nomor);
    }

    /** Berkasnya gambar? (POSTER) — menentukan pratinjau vs tautan unduh. */
    public function isGambar(): bool
    {
        return str_starts_with((string) $this->file_mime, 'image/');
    }

    /** "Edisi 4 Rev 2", atau null bila kategorinya memang tak bernomor edisi. */
    public function labelRevisi(): ?string
    {
        if ($this->edisi === null && $this->no_revisi === null) {
            return null;
        }

        return trim('Edisi '.($this->edisi ?? '—').' Rev '.($this->no_revisi ?? '—'));
    }
}
