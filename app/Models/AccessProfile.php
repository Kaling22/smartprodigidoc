<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil akses — lapis KEDUA di atas izin spatie (PLAN-AKSES-v7 Fase 4).
 *
 * Izin menjawab "boleh menyusun dokumen?"; profil menjawab "jenis yang mana?"
 * dan "boleh meninjau JSA?". Dikelola Admin dari layar, satu profil dipakai
 * banyak pengguna — pola konfigurasi Mikrotik: buat profil → tetapkan.
 *
 * Penegakannya TIDAK di sini melainkan di User::bolehBuatJenis() dan
 * User::canReviewJsa(): satu pertanyaan, satu tempat menjawab.
 */
class AccessProfile extends Model
{
    protected $fillable = ['nama', 'keterangan', 'jenis_dibolehkan', 'boleh_review_jsa'];

    protected $casts = [
        'jenis_dibolehkan' => 'array',
        'boleh_review_jsa' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
