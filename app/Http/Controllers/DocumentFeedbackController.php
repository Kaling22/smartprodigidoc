<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Services\DocumentFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Masukan lapangan Non-Staff — sisi WEB (FITUR-BARU-v4 §3).
 *
 * Orang lapangan tak selalu memegang HP saat di kantor, jadi kanal mobile
 * (Api\FeedbackApiController) punya kembaran di web. Keduanya menulis ke tabel
 * `document_feedback` yang SAMA lewat DocumentFeedbackService — satu sumber
 * kebenaran, bukan dua daftar masukan yang harus disamakan manual.
 */
class DocumentFeedbackController extends Controller
{
    public function __construct(private readonly DocumentFeedbackService $feedback) {}

    /** Non-Staff mengirim masukan atas dokumen Berlaku di departemennya. */
    public function store(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->bisaDiberiMasukanOleh($request->user()), 403,
            'Hanya Non-Staff yang dapat memberi masukan, atas dokumen Berlaku di departemennya.');

        $data = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
        ], [], ['isi' => 'isi masukan']);

        $masukan = $this->feedback->kirim($document, $request->user(), $data['isi']);

        return back()->with('status', "Masukan {$masukan->feedback_number} terkirim ke SH/DH {$document->department->code}. Anda akan melihat balasannya di sini.");
    }

    /**
     * GL/SH/DH menutup sebuah masukan tanpa revisi, disertai balasan.
     *
     * Masukan yang DIADOPSI tidak lewat sini — ia ditutup otomatis saat Ajukan
     * Revisi (DocumentRevisionController::requestRevision, §5).
     */
    public function respond(Request $request, DocumentFeedback $feedback): RedirectResponse
    {
        // SATU aturan untuk web & HP: siapa boleh membalas, batas departemennya,
        // dan larangan menimpa masukan yang sudah ditindak. Ketiganya dulu
        // tertulis sebagai tiga `abort_unless` DI SINI SAJA, sehingga kanal HP
        // mustahil menirunya tanpa menyalinnya (PLAN-MOBILE-v6 §2.2).
        abort_unless($feedback->bisaDibalasOleh($request->user()), 403,
            'Masukan ini bukan atas dokumen departemen Anda, atau sudah ditindaklanjuti.');

        $data = $request->validate([
            'balasan' => ['required', 'string', 'max:2000'],
        ], [], ['balasan' => 'balasan']);

        $this->feedback->balas($feedback, $request->user(), $data['balasan']);

        return back()->with('status', "Masukan {$feedback->feedback_number} dibalas dan ditutup.");
    }
}
