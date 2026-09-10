<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackResource;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Services\DocumentFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Masukan lapangan Non-Staff — sisi APLIKASI MOBILE (FITUR-BARU-v4 §3).
 *
 * Kembaran DocumentFeedbackController di web; keduanya memakai
 * DocumentFeedbackService yang sama, jadi mustahil ada dua daftar masukan.
 *
 * Pengirimnya diambil dari TOKEN, bukan dari badan permintaan. Aplikasi lama
 * mengirim `name`/`nrp`/`departemen` dari HP — artinya siapa pun bisa mengaku
 * sebagai orang lain hanya dengan menyunting badan permintaan.
 */
class FeedbackApiController extends Controller
{
    public function __construct(private readonly DocumentFeedbackService $feedback) {}

    /**
     * DUA layar dalam satu rute (PLAN-MOBILE-v6 §2.2):
     *
     *   Non-Staff  → "Masukan Saya"     : yang PERNAH SAYA kirim + balasannya.
     *   GL/SH/DH   → "Masukan Dokumen"  : KOTAK MASUK — masukan lapangan atas
     *                                     dokumen departemennya.
     *   view_all   → seluruh departemen.
     *
     * Dicabangkan menurut PERAN, bukan menurut parameter dari HP: aplikasi
     * memasang menu, server yang memutuskan isinya. Sebelum ini rutenya selalu
     * `where('user_id', me)` — bagi GL & SH/DH itu selalu kosong, sebab
     * merekalah yang justru MENERIMA masukan.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // `user` (pengirim) & `document.department` dimuat di sini: kotak masuk
        // tanpa nama pengirim tak bisa ditindaklanjuti, dan memuatnya per baris
        // berarti satu query per masukan.
        $query = DocumentFeedback::with(['document.department', 'document.type', 'user', 'replier'])->latest();

        match ($user->lingkupTim()) {
            'sendiri' => $query->where('user_id', $user->id),
            'departemen' => $query->whereHas('document',
                fn ($d) => $d->where('department_id', $user->department_id)),
            default => null,   // `semua` — tanpa penyaring
        };

        return response()->json([
            'data' => FeedbackResource::collection($query->get())->resolve(),
            'meta' => [
                // Menentukan JUDUL & bentuk kartu di HP: "Masukan Saya" vs
                // "Masukan Dokumen". Dijawab server supaya aplikasi tak perlu
                // menyalin matriks perannya.
                'mode' => $user->lingkupTim() === 'sendiri' ? 'saya' : 'kotak_masuk',
            ],
        ]);
    }

    /**
     * Balas sebuah masukan lalu tutup, tanpa revisi.
     *
     * GL & MD membalas dari HP (ketetapan pemilik §2.2; sejak Fase C SH/DH &
     * PJO hanya MELIHAT — lihat DocumentFeedback::bisaDibalasOleh). Logikanya
     * dipinjam UTUH dari DocumentFeedbackService — kembaran webnya
     * (DocumentFeedbackController::respond) memanggil method yang sama persis.
     */
    public function balas(Request $request, DocumentFeedback $feedback): JsonResponse
    {
        $feedback->load('document');

        abort_unless($feedback->bisaDibalasOleh($request->user()), 403,
            'Masukan ini bukan atas dokumen departemen Anda, atau sudah ditindaklanjuti.');

        $data = $request->validate([
            'balasan' => ['required', 'string', 'max:2000'],
        ], [], ['balasan' => 'balasan']);

        $this->feedback->balas($feedback, $request->user(), $data['balasan']);

        return response()->json([
            'pesan' => "Masukan {$feedback->feedback_number} dibalas dan ditutup.",
            'data' => (new FeedbackResource($feedback->load(['document.department', 'document.type', 'user', 'replier'])))->resolve(),
        ]);
    }

    /**
     * Teruskan masukan ini menjadi ALASAN REVISI dokumennya (§2.2, ketetapan
     * pemilik) — sejak PLAN-REVISI-v6 Fase C hanya GL (atas dokumen BUATANNYA),
     * MD, dan Admin; SH/DH & PJO meninjau/menyetujui, bukan memulai revisi.
     *
     * Satu masukan per panggilan: di HP, mencentang beberapa masukan sekaligus
     * seperti di web berarti layar pilihan tersendiri untuk kejadian yang
     * jarang. Yang jamak tetap bisa lewat web.
     */
    public function adopsi(Request $request, DocumentFeedback $feedback): JsonResponse
    {
        $feedback->load('document');

        abort_unless($feedback->bisaDiadopsiOleh($request->user()), 403,
            'Hanya penyusun dokumen (GL) atau Management Development yang memulai revisi, atas dokumen Berlaku yang menjadi tanggung jawabnya.');

        $data = $request->validate([
            'alasan' => ['nullable', 'string', 'max:2000'],
        ], [], ['alasan' => 'alasan revisi']);

        [$revisi] = $this->feedback->ajukanRevisi(
            $feedback->document,
            $request->user(),
            $data['alasan'] ?? null,
            [$feedback->id],
        );

        [$edisiKirim, $revisiKirim] = $revisi->revisiSaatKirim();

        return response()->json([
            'pesan' => "Masukan {$feedback->feedback_number} diteruskan sebagai alasan revisi "
                ."{$revisi->displayNumber()} (akan menjadi Edisi {$edisiKirim} Rev {$revisiKirim}). "
                .'Versi lama tetap Berlaku sampai revisi disahkan.',
            'data' => (new FeedbackResource($feedback->fresh(['document.department', 'document.type', 'user', 'replier'])))->resolve(),
        ]);
    }

    public function store(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->bisaDiberiMasukanOleh($request->user()), 403,
            'Hanya Non-Staff yang dapat memberi masukan, atas dokumen Berlaku di departemennya.');

        $data = $request->validate([
            'isi' => ['required', 'string', 'max:2000'],
        ], [], ['isi' => 'isi masukan']);

        $masukan = $this->feedback->kirim($document, $request->user(), $data['isi']);

        return response()->json([
            'data' => (new FeedbackResource($masukan->load('document')))->resolve(),
        ], 201);
    }
}
