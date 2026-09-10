<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Notifications\DocumentNotification;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

/**
 * Persetujuan final (PRD v2 §3). Approver = document.approver_id. Setuju ->
 * published/Berlaku; tolak (feedback wajib) -> kembali ke GL (rejected), dan
 * peninjau yang meloloskan diberi tahu (§3.4).
 */
class ApprovalController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /** Antrian "Persetujuan Saya". */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Document::with('type', 'department', 'creator', 'reviewer')
            ->where('status', 'pending_approval')
            ->latest('updated_at');

        if (! $user->can('document.view_all')) {
            $query->where('approver_id', $user->id);
        }

        $documents = $query->urut($request->sort, $request->dir)->paginate(15)->withQueryString();

        return Inertia::render('Approvals/Index', [
            // `through()`, bukan `map()`: yang kedua memulangkan Collection
            // biasa dan paginasinya mati diam-diam (pelajaran Fase 6).
            'documents' => $documents->through(fn (Document $d) => $d->barisDaftar($user) + [
                'peninjau' => $d->reviewer?->name,
            ]),
        ]);
    }

    public function show(Request $request, Document $document)
    {
        $this->authorizeApprover($request, $document);

        // Halaman PJO SENGAJA ringkas: keputusan + alasan saja. Penilaian per-item
        // (termasuk analisa JSA) adalah tugas PENINJAU, tak diduplikasi di sini.
        //
        // Model dokumen TIDAK dikirim apa adanya: `creator`/`reviewer`/`approver`
        // adalah model `User` lengkap dengan `password` + `remember_token`, dan
        // props Inertia terbaca siapa pun lewat "view source" (pelajaran Fase
        // 6/7/8/9). Yang dikirim persis enam hal yang dicetak kartu Rantai
        // Dokumen — tak lebih.
        return Inertia::render('Approvals/Show', [
            'document' => [
                'id' => $document->id,
                'nomor' => $document->displayNumber(),
                'judul' => $document->title,
                'dept' => $document->department?->code,
                'departemen' => $document->department?->name,
                // Nama + jabatan + departemen, mis. "Angga - GL ICTMD".
                'pembuat' => $document->creator?->nameWithJabatan(),
                'pembuatNrp' => $document->creator?->nrp,
                'peninjau' => $document->reviewer?->nameWithJabatan(),
                'edisi' => (int) ($document->edisi ?: 1),
                'noRevisi' => (int) $document->no_revisi,
            ],
        ]);
    }

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeApprover($request, $document);

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'comment' => 'nullable|string|max:2000',
            'summary' => 'nullable|string|max:2000',
            'annotations' => 'array',
            'annotations.*.*' => 'nullable|string|max:2000',
        ]);

        // Menolak/ajukan revisi wajib beri alasan: ringkasan ATAU minimal satu catatan.
        if ($data['decision'] === 'reject') {
            $hasAnnotation = collect($data['annotations'] ?? [])->flatten()->filter(fn ($v) => filled($v))->isNotEmpty();
            if (blank($data['summary'] ?? null) && blank($data['comment'] ?? null) && ! $hasAnnotation) {
                return back()->withInput()->withErrors(['summary' => 'Beri ringkasan atau minimal satu catatan revisi sebelum mengembalikan dokumen.']);
            }
        }

        if ($data['decision'] === 'approve') {
            // Deteksi revisi Tipe B: ada versi lama "Sedang Direvisi" bernomor sama.
            // JANGAN pakai no_revisi > 0 — dokumen BARU juga mulai dari Revisi 0,
            // dan Edisi 2 Revisi 1 (roll-over, W-5) sah juga bagi dokumen baru.
            $oldVersion = Document::where('doc_number', $document->doc_number)
                ->where('id', '!=', $document->id)
                ->where('status', 'sedang_direvisi');
            $isRevision = $oldVersion->exists();

            $penomoran = app(DocumentNumberService::class);

            /*
            | Kunci nomor FINAL saat approved (v3.1 §5) — hanya dokumen terbit
            | memakan nomor final → tak bolong. Tiga sumber, urutannya penting:
            |
            |  1. `doc_number_final` yang SUDAH ada — tak pernah ditulis dua kali.
            |  2. Revisi Tipe B ATAU NOMOR MANUAL → pakai `doc_number` apa adanya.
            |  3. Selain itu → kolam nomor urut.
            |
            | Cabang `doc_number_manual` inilah butir 2. Sebelumnya dokumen
            | non-revisi TIDAK PERNAH memeriksanya, sehingga nomor yang diketik
            | tangan ditimpa generateFinal() DIAM-DIAM tepat pada detik dokumen
            | menjadi Berlaku — dan gejalanya baru terlihat berhari-hari kemudian,
            | saat orang mencari dokumen dengan nomor yang tak pernah ada.
            | Bukan dugaan: dokumen 1151 dibuat dengan …-10 dan terbit sebagai
            | …-02, terekam di audit log.
            */
            $final = $document->doc_number_final;
            if (! $final) {
                $manual = ! $isRevision && $document->doc_number_manual;

                $final = ($isRevision || $manual)
                    ? $document->doc_number                                // warisi nomor yang sudah tampil
                    : $penomoran->generateFinal($document->type, $document->department);

                /*
                | Penjaga bentrok — HANYA untuk nomor manual non-revisi.
                |
                | Kenapa di sini padahal StoreDocumentRequest sudah memvalidasi
                | keunikan saat nomor diketik: antara diketik dan disahkan bisa
                | lewat berhari-hari, dan dokumen lain bisa mengambil nomor itu di
                | tengah-tengahnya. Tanpa penjaga ini, hasilnya dua dokumen BERLAKU
                | bernomor sama — kerusakan yang tak bisa dibatalkan karena nomor
                | terbit tak pernah kembali ke kolam.
                |
                | Revisi Tipe B sengaja DIKECUALIKAN: versi lamanya memang memegang
                | `doc_number` yang sama persis, jadi isUnique() akan selalu bilang
                | bentrok dan setiap revisi jadi mustahil disahkan.
                */
                if ($manual && ! $penomoran->isUnique($final, $document->id)) {
                    return back()->withErrors(['doc_number' => "Nomor {$final} sudah dipakai dokumen lain. Ubah nomor dokumen ini lebih dulu, "
                        .'baru pengesahan bisa dilanjutkan.',
                    ]);
                }
            }

            // Dicatat SESUDAH nomornya dipastikan aman: kalau pengesahan batal
            // karena bentrok, tak boleh ada baris persetujuan yang mengklaim
            // dokumen ini pernah disetujui.
            $document->approvals()->create([
                'approver_id' => $request->user()->id,
                'decision' => 'approved',
                'signed_at' => now(),
            ]);

            $document->update(['status' => 'published', 'published_at' => now(), 'doc_number_final' => $final]);
            $this->audit->log('document.approve', $document->id, ['status' => 'published', 'doc_number_final' => $final]);
            app(DocumentService::class)->kabarkanTerbit($document);

            // Revisi Tipe B: versi lama yang "Sedang Direvisi" otomatis jadi
            // TIDAK BERLAKU (obsolete) begitu versi baru disahkan (§3.3).
            if ($isRevision) {
                // Id dikumpulkan SEBELUM update: `$oldVersion` adalah query yang
                // menyaring `status = sedang_direvisi`, jadi menjalankannya lagi
                // SESUDAH statusnya diubah akan mengembalikan daftar KOSONG.
                $idVersiLama = $oldVersion->pluck('id');

                // `obsolete_reason` WAJIB ikut ditulis (Fase F): tanpa ini versi
                // lama bernilai NULL dan tak terbedakan dari dokumen yang
                // DIMATIKAN — padahal hanya yang dimatikan melepas nomornya,
                // sementara versi lama justru mewariskan nomor yang sama ke
                // penerusnya.
                $oldVersion->update([
                    'status' => 'obsolete',
                    'obsolete_reason' => Document::OBSOLETE_REVISI,
                ]);

                // Masukan lapangan yang BELUM ditindak ikut pindah ke versi baru
                // (FITUR-BARU-v4 §6). Tanpa ini ia tetap menempel di versi lama
                // yang barusan jadi obsolete — hilang dari halaman Dokumen
                // Berlaku, tak muncul di versi baru, dan pengirimnya tak pernah
                // dijawab. Persis kegagalan yang kolom `balasan` ada untuk
                // mencegahnya.
                //
                // Dipindah DI SINI, bukan saat revisi diajukan: sebelum disahkan
                // draft revisinya masih bisa dibatalkan & dihapus
                // (cancelRevisionB), dan masukan yang terlanjur pindah akan ikut
                // tak terlihat. Sesudah disahkan, versi baru tak bisa dibatalkan
                // lagi.
                //
                // Yang sudah `diadopsi`/`ditolak` TIDAK ikut: keduanya sudah
                // selesai dan tertaut ke dokumen revisi lewat
                // `revision_document_id`.
                DocumentFeedback::whereIn('document_id', $idVersiLama)
                    ->belumDitindak()
                    ->update(['document_id' => $document->id]);
            }

            return redirect()->route('approvals.index')->with('status', "Dokumen {$document->doc_number} disetujui dan Berlaku.");
        }

        // Reject -> back to the flow as rejected; reviewer who passed it is notified.
        $summary = $data['summary'] ?? $data['comment'] ?? null;
        $document->approvals()->create([
            'approver_id' => $request->user()->id,
            'decision' => 'rejected',
            'comment' => $summary,
            'signed_at' => now(),
        ]);

        // Simpan catatan per-item sbg Review (decision needs_revision) supaya
        // TAMPIL di form revisi pembuat persis spt catatan peninjau (#4). Diberi
        // penanda "[Penyetuju]" agar pembuat tahu asalnya dari approver.
        $review = $document->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'revision_round' => $document->revision_round,
            'decision' => 'needs_revision',
            'summary' => $summary ? '[Penyetuju] '.$summary : '[Penyetuju] Mengajukan revisi.',
        ]);
        $annotationCount = 0;
        foreach (($data['annotations'] ?? []) as $sectionKey => $items) {
            foreach ($items as $itemRef => $comment) {
                if (blank($comment)) {
                    continue;
                }
                $review->annotations()->create([
                    'section_key' => $sectionKey,
                    'item_ref' => (string) $itemRef,
                    'severity' => 'minor',
                    'comment' => $comment,
                ]);
                $annotationCount++;
            }
        }

        $document->update(['status' => 'rejected']);
        $this->audit->log('document.approval_reject', $document->id, ['comment' => $summary, 'annotations' => $annotationCount]);

        // Penyusun (pembuat + pembuat tambahan) & peninjau yang meloloskannya.
        // Keduanya penting: yang satu harus memperbaiki, yang satu perlu tahu
        // penilaiannya tak diterima.
        Notification::send($document->penyusun(), new DocumentNotification(
            $document, "Dokumen {$document->doc_number} ditolak approver — perlu revisi.", 'bi-x-circle', 'documents.edit',
            penting: true, catatan: $summary,
        ));
        $document->reviewer?->notify(new DocumentNotification(
            $document, "Dokumen {$document->doc_number} yang Anda loloskan ditolak approver.", 'bi-exclamation-triangle', 'documents.show',
            penting: true, catatan: $summary,
        ));

        return redirect()->route('approvals.index')->with('status', "Dokumen {$document->doc_number} ditolak dan dikembalikan.");
    }

    private function authorizeApprover(Request $request, Document $document): void
    {
        $user = $request->user();
        abort_unless($user->can('document.approve'), 403);
        abort_unless($document->approver_id === $user->id || $user->can('document.view_all'), 403, 'Anda bukan penyetuju dokumen ini.');
        abort_unless($document->status === 'pending_approval', 403, 'Dokumen tidak menunggu persetujuan.');
    }
}
