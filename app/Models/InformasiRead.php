<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu pasangan (informasi, pembaca) — bukan satu kunjungan.
 *
 * Kembaran {@see DocumentRead} untuk menu Informasi; lihat komentar di sana
 * untuk alasan tanpa `timestamps`.
 */
class InformasiRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'informasi_id', 'user_id', 'first_read_at', 'last_read_at',
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

    public function informasi(): BelongsTo
    {
        return $this->belongsTo(Informasi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
