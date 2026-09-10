<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobChecklistItem extends Model
{
    protected $fillable = [
        'job_execution_id',
        'langkah_ke',
        'bahaya_ke',
        'pengendalian_ke',
        'checked',
        'checked_at',
        'catatan',
    ];

    protected $casts = [
        'checked'    => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function jobExecution(): BelongsTo
    {
        return $this->belongsTo(JobExecution::class);
    }
}
