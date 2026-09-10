<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['code', 'name', 'alias', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * Departemen yang masih ditawarkan (PLAN-AKSES-v8 Fase 5b).
     *
     * Dipakai HANYA oleh jalur yang MEMILIH departemen baru — pendaftaran akun
     * dan pembuatan dokumen. Saringan daftar, laporan, dan distribusi sengaja
     * TIDAK memakainya: menonaktifkan departemen tak pernah berarti dokumen &
     * penggunanya hilang, dan daftar yang tak bisa disaring ke departemen itu
     * lagi adalah cara paling halus untuk membuat datanya tak terjangkau.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
