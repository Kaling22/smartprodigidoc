<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Masukan lapangan Non-Staff atas dokumen yang BERLAKU (FITUR-BARU-v4 §3).
 *
 * Kanal TERPISAH dari isi dokumen: Non-Staff tetap read-only atas dokumen —
 * ia hanya menulis catatan yang ditujukan ke SH/DH departemennya (CLAUDE.md §6).
 *
 * Satu tabel dipakai BERSAMA aplikasi mobile & web supaya tak ada dua sumber
 * kebenaran; bedanya cuma pintu masuknya (controller web vs API Sanctum).
 */
class DocumentFeedback extends Model
{
    /** Nama tabel tunggal (Eloquent akan menebak "document_feedbacks"). */
    protected $table = 'document_feedback';

    protected $fillable = [
        'feedback_number', 'document_id', 'user_id', 'isi',
        'status', 'balasan', 'replied_by', 'replied_at', 'revision_document_id',
    ];

    protected $casts = ['replied_at' => 'datetime'];

    public const STATUS_LABELS = [
        'baru' => 'Baru',
        'dibaca' => 'Sudah dibaca',
        'diadopsi' => 'Diadopsi jadi revisi',
        'ditolak' => 'Tidak ditindaklanjuti',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /** Pengirim masukan (Non-Staff). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** SH/DH yang menindaklanjuti (membalas / mengadopsi). */
    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /** Dokumen revisi yang lahir dari masukan ini (bila diadopsi). */
    public function revisionDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'revision_document_id');
    }

    /**
     * Masukan yang BELUM ditindaklanjuti — inilah yang dihitung lencana di
     * halaman Dokumen Berlaku dan yang boleh dicentang saat Ajukan Revisi.
     *
     * `dibaca` ikut terhitung: sudah dilihat bukan berarti sudah ditindak.
     * Kalau `baru` saja, lencana hilang begitu SH membuka halaman — dan masukan
     * yang belum ditangani jadi tak terlihat lagi.
     */
    public function scopeBelumDitindak(Builder $query): Builder
    {
        return $query->whereIn('status', ['baru', 'dibaca']);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Boleh MEMBALAS (sekaligus menutup) masukan ini?
     *
     * Sejak PLAN-REVISI-v6 Fase C (keputusan D4) penjaganya adalah izin
     * `document.feedback_respond` — dipegang GL, MD, dan Admin. SH/DH & PJO
     * sengaja TIDAK: mereka meninjau & menyetujui, dan yang menindaklanjuti
     * masukan lapangan adalah penyusunnya (GL) atau MD. Keduanya tetap MELIHAT
     * masukan lewat menu Log Dokumen.
     *
     * Ini sekaligus menambal bug lama: dulu pemegang `document.view_all`
     * langsung `return true`, sehingga PJO bisa membalas — dan MENUTUP —
     * masukan tujuh departemen yang tak pernah ia tindak.
     *
     * Yang sudah `diadopsi`/`ditolak` tertutup: menimpa `balasan`-nya merusak
     * jejak "masukan ini memicu revisi yang mana".
     */
    public function bisaDibalasOleh(User $user): bool
    {
        if (! in_array($this->status, ['baru', 'dibaca'], true)) {
            return false;
        }

        if (! $user->can('document.feedback_respond')) {
            return false;
        }

        // MD & Admin: 7 departemen (MD tak punya departemen, jadi pagar
        // departemen di bawah akan selalu menolaknya).
        if ($user->can('document.view_all')) {
            return true;
        }

        // Batas DEPARTEMEN. Tanpa ini GL departemen A membalas — dan MENUTUP —
        // masukan atas dokumen departemen B.
        return $this->document?->department_id === $user->department_id;
    }

    /**
     * Boleh MENGADOPSI masukan ini menjadi alasan revisi?
     *
     * Lebih sempit daripada membalas: yang mengadopsi harus juga berwenang
     * MEREVISI dokumen yang bersangkutan — dan bagi GL itu berarti dokumen
     * buatannya sendiri saja ({@see Document::bisaDirevisiOleh}, yang sekaligus
     * mensyaratkan dokumennya masih Berlaku).
     */
    public function bisaDiadopsiOleh(User $user): bool
    {
        return $this->bisaDibalasOleh($user)
            && (bool) $this->document?->bisaDirevisiOleh($user);
    }
}
