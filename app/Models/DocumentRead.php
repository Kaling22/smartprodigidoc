<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu pasangan (dokumen Berlaku, pembaca) — bukan satu kunjungan.
 *
 * Kunjungan berikutnya menaikkan pencacah pada baris yang sama, sehingga tabel
 * ini tumbuh sebesar jumlah ORANG, bukan sebesar jumlah klik.
 *
 * Sengaja TANPA `timestamps` Laravel: `first_read_at`/`last_read_at` sudah
 * menyatakan hal yang sama dengan makna yang lebih tepat, dan `created_at`
 * kedua hanya akan membingungkan pembaca query.
 */
class DocumentRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'user_id', 'first_read_at', 'last_read_at',
        'read_count', 'download_count', 'platform',
    ];

    protected function casts(): array
    {
        return [
            'first_read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'read_count' => 'integer',
            'download_count' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
