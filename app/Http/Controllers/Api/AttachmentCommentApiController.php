<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Komentar pada foto lampiran, dari aplikasi mobile.
 *
 * Aturan izinnya SAMA PERSIS dengan {@see \App\Http\Controllers\AttachmentCommentController}
 * — pembuat, peninjau, penyetuju, atau pemegang `document.view_all`. Ditulis
 * ulang di sini hanya karena bentuk jawabannya berbeda (JSON, bukan redirect);
 * kalau syaratnya ikut berbeda, foto yang tertutup di web bisa terbuka di HP.
 */
class AttachmentCommentApiController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(Request $request, Attachment $attachment): JsonResponse
    {
        $document = $attachment->document;
        $user = $request->user();

        $boleh = in_array($user->id, [$document->created_by, $document->reviewer_id, $document->approver_id], true)
            || $user->can('document.view_all');
        abort_unless($boleh, 403, 'Anda tidak berhak mengomentari lampiran dokumen ini.');

        $data = $request->validate(
            ['isi' => 'required|string|max:1000'],
            [],
            ['isi' => 'isi komentar']
        );

        $komentar = $attachment->comments()->create(['user_id' => $user->id, 'comment' => $data['isi']]);
        $this->audit->log('attachment.comment', $document->id, ['attachment_id' => $attachment->id]);

        return response()->json(['data' => [
            'nama' => $user->name,
            'isi' => $komentar->comment,
            'waktu' => $komentar->created_at?->toDateTimeString(),
        ]], 201);
    }
}
