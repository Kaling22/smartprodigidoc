<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Masukan sejawat: catatan seorang GL atas dokumen rekan sedepartemennya yang
 * BELUM Berlaku (PLAN-AKSES-v8 Fase 2).
 *
 * Kanal ini SEARAH dan tanpa status: ia bukan keputusan (itu `reviews`) dan
 * bukan tiket yang harus ditutup (itu `document_feedback`). Yang dicatat hanya
 * "siapa menulis apa, kapan, dan sudah dibaca pemiliknya atau belum".
 *
 * Siapa boleh mengirimnya dijawab {@see Document::bisaDiberiMasukanSejawatOleh()},
 * bukan di sini — aturan itu dipakai bersama oleh tombol di layar dan penjaga
 * di controller, sehingga mustahil tombolnya tampil lalu berakhir 403.
 */
class MasukanSejawat extends Model
{
    /**
     * Tabelnya dinamai dalam bahasa Inggris struktural agar berdiri sejajar
     * dengan `document_feedback` (masukan lapangan) di daftar tabel; kelasnya
     * memakai istilah bisnis PT PPA, sama seperti rute, controller, dan seluruh
     * teks layarnya (CLAUDE.md §3). Jadi $table ditulis eksplisit — Eloquent
     * akan menebak "masukan_sejawats".
     */
    protected $table = 'document_feedback_antargl';

    protected $fillable = ['document_id', 'user_id', 'ringkasan', 'catatan_json', 'dibaca_at'];

    protected $casts = [
        'catatan_json' => 'array',
        'dibaca_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** Pemberi masukan (GL sedepartemen). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
