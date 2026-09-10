<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;

/**
 * Siapa boleh meninjau dokumen ini, dan pada status apa.
 *
 * Dipisah dari controller supaya jalur WEB dan jalur API memakai pemeriksaan
 * yang sama persis. Kalau aturannya disalin, cepat atau lambat salah satunya
 * tertinggal saat aturan berubah — dan yang tertinggal itu justru pintu masuk.
 *
 * Tiga lapis, urutannya disengaja (dari yang paling umum ke paling khusus):
 *   1. WEWENANG JENIS — peninjau biasa (SH/DH) boleh semua jenis; GL peninjau
 *      JSA (SHE; v7 Fase 3 mencabut Plant) HANYA JSA. Diperiksa di sini, bukan
 *      cuma di gate rute, agar GL yang menempelkan URL review SOP langsung
 *      tetap ditolak.
 *   2. PENUGASAN — harus dia yang ditunjuk sebagai `reviewer_id`.
 *   3. STATUS — dokumen memang sedang pada tahap yang dimaksud.
 */
class ReviewAccess
{
    /** Boleh meninjau JENIS dokumen ini sama sekali? */
    public function bolehJenis(User $user, Document $document): bool
    {
        return $user->can('document.review')
            || ($user->canReviewJsa() && $document->type?->code === 'JSA');
    }

    /** Peninjau yang ditunjuk (atau pengawas lintas-dokumen). */
    public function bolehDokumen(User $user, Document $document): bool
    {
        return $document->reviewer_id === $user->id || $user->can('document.view_all');
    }

    /**
     * Menghentikan permintaan yang tak berhak. Dipakai controller web dan API;
     * keduanya menghasilkan 403 dengan kalimat yang sama.
     *
     * @param  array<int, string>  $statusDiizinkan
     */
    public function pastikan(User $user, Document $document, array $statusDiizinkan = ['in_review']): void
    {
        abort_unless($this->bolehJenis($user, $document), 403, 'Anda tidak berwenang meninjau jenis dokumen ini.');
        abort_unless($this->bolehDokumen($user, $document), 403, 'Anda bukan peninjau dokumen ini.');
        abort_unless(in_array($document->status, $statusDiizinkan, true), 403, 'Status dokumen tidak sesuai untuk aksi ini.');
    }

    /**
     * Antrian peninjauan seseorang: dokumen berstatus tertentu yang menjadi
     * tanggung jawabnya. Satu bentuk query, dipakai halaman web maupun API.
     *
     * @param  array<int, string>  $status
     */
    public function antrian(User $user, array $status): \Illuminate\Database\Eloquent\Builder
    {
        return Document::with('type', 'department', 'creator')
            ->whereIn('status', $status)
            ->when(! $user->can('document.view_all'), fn ($q) => $q->where('reviewer_id', $user->id));
    }
}
