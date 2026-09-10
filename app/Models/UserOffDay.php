<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu rentang ketidaktersediaan seseorang (FITUR-BARU-v4 §6).
 *
 * Rentangnya INKLUSIF di kedua ujung — off sehari berarti `mulai == sampai`.
 * Semua perbandingan tanggal di kelas ini memakai bentuk `Y-m-d` (bukan
 * datetime) supaya jam tak pernah ikut menentukan; seseorang yang off "hari
 * ini" tetap off pada pukul 23.00.
 */
class UserOffDay extends Model
{
    protected $fillable = ['user_id', 'jenis', 'mulai', 'sampai', 'catatan'];

    protected $casts = [
        'mulai' => 'date',
        'sampai' => 'date',
    ];

    public const JENIS_LABELS = [
        'cuti' => 'Cuti',
        'off_day' => 'Off day',
        'dinas_luar' => 'Dinas luar',
    ];

    /**
     * Aturan validasi pengajuan — SATU tempat, dipakai formulir web DAN API
     * mobile. Menyalinnya ke kanal kedua berarti dua daftar aturan yang pasti
     * berselisih: yang di HP masih menerima rentang lama setelah web
     * dipersempit.
     *
     * @return array{0:array<string,mixed>, 1:array<string,string>, 2:array<string,string>}
     */
    public static function aturan(): array
    {
        return [
            [
                'jenis' => ['required', \Illuminate\Validation\Rule::in(array_keys(self::JENIS_LABELS))],
                // Off kemarin tak ada gunanya dicatat — yang dijadwalkan hanya ke depan.
                'mulai' => ['required', 'date', 'after_or_equal:today'],
                // 60 hari: cukup untuk cuti panjang, tapi salah ketik TAHUN tak
                // membuat seseorang hilang dari daftar peninjau selamanya.
                'sampai' => ['required', 'date', 'after_or_equal:mulai', 'before_or_equal:'.now()->addDays(60)->toDateString()],
                'catatan' => ['nullable', 'string', 'max:255'],
            ],
            [
                'mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
                'sampai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                'sampai.before_or_equal' => 'Rentang off paling lama 60 hari ke depan.',
            ],
            ['jenis' => 'jenis', 'mulai' => 'tanggal mulai', 'sampai' => 'tanggal selesai'],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rentang yang MENCAKUP tanggal tertentu. */
    public function scopeAktifPada(Builder $query, \DateTimeInterface|string $tanggal): Builder
    {
        $hari = $tanggal instanceof \DateTimeInterface
            ? $tanggal->format('Y-m-d')
            : (string) $tanggal;

        return $query->whereDate('mulai', '<=', $hari)->whereDate('sampai', '>=', $hari);
    }

    /** Rentang yang belum berakhir — sedang berjalan ATAU akan datang. */
    public function scopeBelumBerakhir(Builder $query): Builder
    {
        return $query->whereDate('sampai', '>=', now()->toDateString());
    }

    /** Bertindih dengan rentang lain? Dipakai memvalidasi pengajuan baru. */
    public function scopeBertindih(Builder $query, string $mulai, string $sampai): Builder
    {
        // Dua rentang bertindih bila masing-masing mulai sebelum yang lain
        // berakhir — satu perbandingan, bukan empat kasus terpisah.
        return $query->whereDate('mulai', '<=', $sampai)->whereDate('sampai', '>=', $mulai);
    }

    public function jenisLabel(): string
    {
        return self::JENIS_LABELS[$this->jenis] ?? $this->jenis;
    }

    /** "4–6 Agustus 2026" atau "4 Agustus 2026" bila hanya sehari. */
    public function rentangLabel(): string
    {
        if ($this->mulai->isSameDay($this->sampai)) {
            return $this->mulai->translatedFormat('j F Y');
        }

        // Bulan & tahun tak diulang bila sama: "4–6 Agustus 2026".
        $awal = $this->mulai->isSameMonth($this->sampai)
            ? $this->mulai->translatedFormat('j')
            : $this->mulai->translatedFormat('j F');

        return $awal.'–'.$this->sampai->translatedFormat('j F Y');
    }
}
