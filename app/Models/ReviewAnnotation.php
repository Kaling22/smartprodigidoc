<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAnnotation extends Model
{
    /** Tanda peninjau per Tindakan Pengendalian JSA (kolom `verdict`). */
    public const VERDICT_SESUAI = 'sesuai';

    public const VERDICT_PERLU_REVISI = 'perlu_revisi';

    protected $fillable = [
        'review_id', 'section_key', 'item_ref', 'verdict', 'severity',
        'comment', 'ai_generated', 'ai_adopted', 'resolved',
    ];

    protected $casts = [
        'ai_generated' => 'boolean',
        'ai_adopted' => 'boolean',
        'resolved' => 'boolean',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
