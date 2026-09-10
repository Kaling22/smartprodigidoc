<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'document_id', 'action', 'meta_json', 'ip_address', 'created_at'];

    protected $casts = [
        'meta_json' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Rupa tiap aksi di TIMELINE dokumen: `[label, ikon bi-*, rona]`.
     *
     * Dipindahkan dari `documents/show.blade.php` — di sana ia larik `@php`
     * setinggi 23 baris di dalam view, dan view tak ikut terserialkan ke props
     * Inertia. Diletakkan di model, bukan diketik ulang di TSX, dengan alasan
     * yang sama seperti {@see Document::STATUS_META}: satu peta label+ikon+warna
     * yang punya dua salinan adalah peta yang suatu hari berselisih.
     *
     * Aksi yang belum terdaftar TIDAK jatuh ke ruang kosong — pembacanya
     * merangkai label darurat dari nama aksinya sendiri (lihat
     * DocumentController::show).
     */
    public const AKSI_META = [
        'document.create' => ['Dokumen dibuat', 'bi-file-earmark-plus', 'primary'],
        'document.submit' => ['Dikirim untuk ditinjau', 'bi-send', 'info'],
        'document.withdraw' => ['Ditarik kembali ke draft', 'bi-arrow-counterclockwise', 'secondary'],
        'document.review_start' => ['Mulai ditinjau', 'bi-clipboard', 'info'],
        'document.review_approve' => ['Diloloskan peninjau', 'bi-check2', 'success'],
        'document.review_reject' => ['Dikembalikan untuk revisi', 'bi-arrow-counterclockwise', 'warning'],
        'document.approve' => ['Disetujui — Berlaku', 'bi-patch-check', 'success'],
        'document.approval_reject' => ['Ditolak approver', 'bi-x-circle', 'danger'],
        'document.cancel_revision' => ['Penolakan dibatalkan (ditinjau ulang)', 'bi-arrow-repeat', 'secondary'],
        'document.reassign_review' => ['Peninjauan dialihkan', 'bi-arrow-left-right', 'info'],
        'document.arsip_upload' => ['Dokumen lama didaftarkan — Berlaku', 'bi-archive', 'success'],
        'document.arsip_edit' => ['Dokumen lama diperbaiki', 'bi-pencil', 'secondary'],
        'document.request_revision' => ['Revisi diajukan', 'bi-arrow-repeat', 'warning'],
        'document.cancel_revision_b' => ['Revisi dibatalkan', 'bi-x-circle', 'secondary'],
        'document.nonaktif_ajukan' => ['Nonaktif diajukan', 'bi-slash-circle', 'warning'],
        'document.nonaktif_setuju' => ['Nonaktif disetujui satu tahap', 'bi-check2', 'warning'],
        'document.nonaktif_tolak' => ['Pengajuan nonaktif ditolak', 'bi-x-circle', 'secondary'],
        'document.nonaktif_selesai' => ['Tidak Berlaku — nomor dilepas', 'bi-slash-circle', 'danger'],
        'attachment.comment' => ['Komentar pada lampiran', 'bi-chat-left-text', 'info'],
        'feedback.create' => ['Masukan lapangan masuk', 'bi-chat-left-dots', 'primary'],
        'feedback.respond' => ['Masukan dibalas & ditutup', 'bi-reply', 'secondary'],
        'masukan_sejawat.kirim' => ['Masukan sejawat masuk', 'bi-chat-square-text', 'info'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
