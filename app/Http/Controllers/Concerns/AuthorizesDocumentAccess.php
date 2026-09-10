<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use Illuminate\Http\Request;

/**
 * Pemeriksaan akses dokumen yang dipakai BERSAMA oleh controller dokumen.
 *
 * Dulu keduanya `private` di DocumentController; setelah controller dipecah,
 * beberapa controller membutuhkannya. Dijadikan trait — bukan disalin — agar
 * aturannya tetap SATU, sehingga mustahil dua controller memakai aturan akses
 * yang berbeda.
 */
trait AuthorizesDocumentAccess
{
    /**
     * Visibilitas dokumen: per-departemen (D12), DITAMBAH orang yang ditugasi.
     *
     * Tiga syarat pertama adalah aturan lama: pengawas lintas-dokumen, pembuat,
     * dan orang sedepartemen.
     *
     * Syarat keempat menutup celah yang membuat peninjau JSA tak bisa bekerja.
     * Peninjau JSA **selalu** dari departemen LAIN — GL SHE meninjau JSA
     * Produksi, JSA Plant, JSA ICTMD (docs/aturan-alur-v2.md). Dengan tiga
     * syarat lama ia ditunjuk sebagai peninjau, menerima notifikasinya, bisa
     * membuka formulir tinjauan… lalu ditolak 403 begitu menekan "Lihat PDF"
     * atau membuka halaman dokumennya. Ia diminta menilai sesuatu yang tak
     * boleh ia lihat utuh.
     *
     * Daftar orangnya SAMA dengan yang sudah dipakai AttachmentCommentController
     * (pembuat / peninjau / penyetuju) — aturan itu memang sudah mengandaikan
     * peninjau bisa melihat dokumennya, sebab ia boleh mengomentari lampirannya.
     *
     * Ini TIDAK melebarkan akses ke orang lain: `reviewer_id`/`approver_id` diisi
     * dari kandidat yang divalidasi DocumentParticipantResolver, bukan dari
     * kiriman bebas.
     */
    private function authorizeView(Request $request, Document $document): void
    {
        $user = $request->user();

        $allowed = $user->can('document.view_all')
            || $document->created_by === $user->id
            || $document->department_id === $user->department_id
            || $document->reviewer_id === $user->id
            || $document->approver_id === $user->id;

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke dokumen ini.');
    }

    private function isEditable(Document $document, Request $request): bool
    {
        $user = $request->user();

        // Hanya pihak yang boleh MEMBUAT/menyunting dokumen (GL/Admin). Non-Staff
        // read-only tak bisa menyunting draft lama miliknya (Fase B).
        if (! $user->can('document.create')) {
            return false;
        }

        $ownerOrAdmin = $document->created_by === $user->id || $user->can('document.view_all');

        // draft = normal editing; rejected = directed revision (Tipe A, §3.3).
        return $ownerOrAdmin && in_array($document->status, ['draft', 'rejected', 'needs_revision'], true);
    }

}
