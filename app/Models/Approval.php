<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    /** 'pengesahan' = jejak alur dokumen · 'nonaktif' = pengajuan & keputusan mematikan dokumen (Fase F). */
    public const KIND_PENGESAHAN = 'pengesahan';

    public const KIND_NONAKTIF = 'nonaktif';

    protected $fillable = ['document_id', 'approver_id', 'kind', 'decision', 'comment', 'signed_at'];

    protected $casts = ['signed_at' => 'datetime'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
