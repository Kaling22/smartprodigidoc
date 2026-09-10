<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobExecution extends Model
{
    protected $fillable = [
        'document_id',
        'user_id',
        'nama_pekerjaan',
        'lokasi',
        'tanggal_pelaksanaan',
        'analisa_snapshot',
        'status',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'analisa_snapshot'    => 'array',
    ];

    public const STATUS_LABELS = [
        'berlangsung' => 'Berlangsung',
        'selesai'     => 'Selesai',
        'dibatalkan'  => 'Dibatalkan',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(JobChecklistItem::class);
    }

    /**
     * Berapa tindakan pengendalian yang sudah dicentang vs total.
     *
     * Penyebutnya HANYA yang benar-benar bisa dicentang. Bahaya yang tak punya
     * satu pun pengendalian dulu tetap dihitung 1, padahal aplikasi menyusun
     * kotak centangnya dari daftar pengendalian — daftar kosong berarti tak ada
     * kotak, tak ada posisi `pengendalian_ke` yang sah, jadi angka itu tak akan
     * pernah terpenuhi dan barnya mustahil penuh. Yang salah bukan pelaksana
     * melainkan JSA-nya, dan itu diperbaiki lewat revisi dokumen, bukan lewat
     * bar yang menuduhnya belum selesai.
     */
    public function progres(): array
    {
        $analisa = $this->analisa_snapshot ?? [];
        $total   = 0;

        foreach ($analisa as $step) {
            foreach ((array) ($step['bahaya'] ?? []) as $b) {
                $total += count(array_filter(
                    (array) ($b['pengendalian'] ?? []),
                    fn ($p) => trim((string) $p) !== ''
                ));
            }
        }

        $selesai = $this->checklistItems()->where('checked', true)->count();

        return ['total' => $total, 'selesai' => $selesai];
    }
}
