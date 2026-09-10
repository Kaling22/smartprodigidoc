<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    /**
     * Rupa tiap jenis di antarmuka: [ikon, warna].
     *
     * Dipusatkan di sini supaya "SOP" mustahil tampil dengan ikon berbeda di
     * dua kartu. Ikonnya WAJIB dari Bootstrap Icons — satu-satunya set yang
     * dimuat layout; glif dari set lain (boxicons/remix milik tema referensi)
     * tak punya font-nya di sini dan hanya menghasilkan kotak kosong.
     *
     * Warnanya gradasi oranye→kuning identitas PPA, ditutup abu/hitam. Hijau &
     * merah sengaja TIDAK dipakai: di aplikasi ini keduanya sudah berarti "sah"
     * dan "gagal", dan jenis dokumen tak berarti apa-apa soal itu.
     *
     * URUTAN larik ini = urutan tampil di seluruh antarmuka (lihat kode()).
     */
    public const RUPA = [
        'SOP' => ['bi-journal-text', '#ea580c'],
        'IK' => ['bi-list-check', '#f97316'],
        'SP' => ['bi-file-earmark-ruled', '#eab308'],
        'JSA' => ['bi-shield-exclamation', '#27272a'],
        'FK' => ['bi-ui-checks-grid', '#f59e0b'],
        'PX' => ['bi-box-arrow-in-down', '#71717a'],
    ];

    /** @return array{0: string, 1: string} [ikon, warna] — jenis tak dikenal tetap dapat ikon. */
    public static function rupa(?string $code): array
    {
        return self::RUPA[$code] ?? ['bi-file-earmark-text', '#8392ab'];
    }

    /**
     * Kode seluruh jenis, URUT tampil — satu sumber untuk saringan "Jenis",
     * submenu sidebar, sebaran dashboard, dan daftar induk. Dulu keempatnya
     * menulis `['SOP','IK','SP','JSA']` sendiri-sendiri, sehingga menambah jenis
     * berarti menemukan empat tempat yang tak saling menunjuk.
     *
     * URUTANNYA dari konstanta, KELENGKAPANNYA dari basis data
     * (PLAN-AKSES-v8 Fase 5b). Ini jebakan yang dicatat rencananya secara
     * khusus: selama method ini membaca RUPA telanjang, mematikan `is_active`
     * dari layar Master Data tak menghilangkan jenis itu dari satu pun
     * dropdown — saklarnya menyala tapi tak menyalakan apa pun.
     *
     * Yang tetap dari konstanta adalah urutannya, sebab itu memang urutan
     * tampil yang dipilih pemilik produk dan bukan sesuatu yang disetel Admin.
     * Jenis yang ada di basis data tapi tak ada di RUPA sengaja TIDAK ikut: ia
     * tak punya ikon, warna, maupun tempat di urutan — menampilkannya berarti
     * baris tanpa rupa di tengah daftar.
     *
     * @return array<int, string>
     */
    public static function kode(): array
    {
        $aktif = static::where('is_active', true)->pluck('code')->all();

        return array_values(array_intersect(array_keys(self::RUPA), $aktif));
    }

    /**
     * Jenis UNGGAHAN (FK/PX): tak punya bab, isinya berkas PDF yang diunggah.
     * Wizard, langkah, dan pratinjau tak pernah berlaku untuknya.
     */
    public function isUnggahan(): bool
    {
        return $this->class === 'unggahan';
    }

    protected $fillable = ['code', 'name', 'schema_json', 'class', 'scope', 'is_active'];

    protected $casts = [
        'schema_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
