<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Services\DocumentFeedbackService;
use App\Services\DocumentService;
use App\Services\ReviewerAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Daur hidup dokumen SESUDAH terbit: daftar Berlaku, pengajuan & pembatalan
 * revisi Tipe B, daftar Dokumen Revisi milik pembuat, serta penonaktifan
 * (Tidak Berlaku).
 *
 * Dipisahkan dari DocumentController yang mengurus PEMBUATAN — dua urusan yang
 * berbeda: yang satu menyusun draft, yang satu mengelola dokumen yang sudah sah.
 */
class DocumentRevisionController extends Controller
{
    use \App\Http\Controllers\Concerns\AuthorizesDocumentAccess;

    /**
     * Usulan isi lembar CATATAN REVISI — tombol "Revisi" di langkah Log Revisi
     * (butir 7b). Baca-saja: nol tulisan ke database.
     *
     * Aturannya SAMA PERSIS dengan aturan menyunting draft-nya (trait di atas):
     * yang tak boleh mengedit dokumen ini juga tak boleh menyuruh servernya
     * merender PDF untuknya.
     */
    public function usulanCatatanRevisi(Request $request, Document $document, \App\Services\Print\PdfRenderer $pdf)
    {
        $this->authorizeView($request, $document);
        abort_unless($this->isEditable($document, $request) && $document->usesRevisionLog(), 403);

        return response()->json($this->documents->usulanCatatanRevisi($document, $pdf));
    }

    /**
     * Musnahkan dokumen beserta seluruh jejaknya (§8). Admin saja.
     *
     * Orkestrasinya ada di DocumentPurger — controller tetap tipis (CLAUDE.md §3).
     */
    public function purge(\App\Http\Requests\PurgeDocumentRequest $request, Document $document): \Illuminate\Http\RedirectResponse
    {
        try {
            $nomor = app(\App\Services\DocumentPurger::class)->purge($document, $request->alasan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('documents.published')
            ->with('status', "Dokumen {$nomor} dimusnahkan beserta seluruh riwayatnya.");
    }

    /**
     * Bersihkan SELURUH dokumen (PLAN C §C4) — Admin IT.
     *
     * Tak ada `try` seperti pada purge() satuan: purgeAll() memakai `paksa`, jadi
     * satu-satunya \RuntimeException yang dulu mungkin muncul ("sedang direvisi")
     * tak bisa lagi terjadi di sini.
     */
    public function purgeAll(\App\Http\Requests\PurgeAllRequest $request, \App\Services\DocumentPurger $purger): \Illuminate\Http\RedirectResponse
    {
        $hasil = $purger->purgeAll($request->alasan);

        // Kembali ke tempat tombolnya berada — Konfigurasi Sistem sejak v9
        // Fase 2b, bukan lagi Manajemen User.
        return redirect()->route('pengaturan.sistem')
            ->with('status', "{$hasil['terhapus']} dokumen dimusnahkan beserta seluruh berkas & riwayatnya. Audit Log tetap utuh.");
    }

    public function __construct(private readonly DocumentService $documents) {}

    /** "Dokumen Berlaku" — dokumen published/sedang_direvisi (untuk Ajukan Revisi Tipe B). */
    public function published(Request $request)
    {
        $user = $request->user();

        // v2 2g: SH/GL/DH lihat dokumen Berlaku dept sendiri; PJO/Admin lihat 7 dept.
        $canAll = $user->can('document.view_all');

        $query = Document::with('type', 'department', 'creator')
            ->berlaku()
            // Lencana "[N masukan]" + isi modal Revisi (FITUR-BARU-v4 §3).
            // Semua KECUALI Non-Staff melihat seluruh masukan yang belum
            // ditindak; Non-Staff hanya masukan MILIKNYA SENDIRI.
            //
            // Digantung pada `dashboardPenuh()`, BUKAN pada
            // `document.request_revision` (Fase C): izin itu kini milik GL & MD,
            // dan SH/DH/PJO — yang kehilangan hak MEREVISI — tetap berhak
            // MELIHAT masukan atas dokumen yang mereka setujui.
            ->withCount(['feedback as masukan_count' => fn ($q) => $q->belumDitindak()])
            ->with(['feedback' => fn ($q) => $q->with('user', 'replier')->latest()
                ->when($user->dashboardPenuh(),
                    fn ($f) => $f->belumDitindak(),
                    fn ($f) => $f->where('user_id', $user->id))])
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->when($canAll && $request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->latest('published_at');

        if (! $canAll) {
            $query->where('department_id', $user->department_id);
        }

        $documents = $query->urut($request->sort, $request->dir)->paginate(15)->withQueryString();

        // Semua KECUALI Non-Staff melihat lencana & isi masukan; Non-Staff hanya
        // miliknya sendiri (sudah disaring di query di atas).
        $lihatMasukan = $user->dashboardPenuh();

        /*
        | Jadwal PEMBUAT tiap dokumen — KETERANGAN saja di modal Ajukan Revisi
        | (FITUR-BARU-v4 §6): pada GL ketersediaan tak pernah menghalangi, ia
        | hanya memberi tahu. Dihitung sekali untuk seluruh halaman, bukan per
        | baris — dan SEBELUM `through()`, yang mengganti isi paginator dengan
        | larik datar sehingga `pluck('creator')` sesudahnya kosong.
        */
        $ketersediaan = DocumentController::jadwalPembuat($documents->pluck('creator'));

        return Inertia::render('Documents/Published', [
            'documents' => $documents->through(fn (Document $doc) => $doc->barisDaftar($user) + [
                // null = lencananya tak ditawarkan sama sekali.
                'masukan_count' => $lihatMasukan ? (int) $doc->masukan_count : null,
                'boleh_beri_masukan' => $doc->bisaDiberiMasukanOleh($user),
                // Fase C: bukan lagi `@can` (izin saja) melainkan predikat
                // per-DOKUMEN — GL hanya boleh merevisi buatannya sendiri.
                // Tombol Ajukan Nonaktif memakai syarat yang SAMA (Fase F).
                'boleh_revisi' => $doc->bisaDirevisiOleh($user),
                // Membatalkan revisi = wewenang yang SAMA dengan memulainya,
                // tapi statusnya `sedang_direvisi` sehingga bisaDirevisiOleh()
                // (yang mensyaratkan Berlaku) tak bisa dipakai apa adanya.
                'boleh_batal_revisi' => $doc->status === 'sedang_direvisi'
                    && $user->can('document.request_revision')
                    && ($user->can('document.view_all') || $doc->created_by === $user->id),
                // Musnahkan = Admin saja (§8). Berbeda dari "Tidak Berlaku" yang
                // hanya memindahkan; ini menghapus tanpa rollback.
                'boleh_musnahkan' => $user->can('user.manage') && $doc->status === 'published',
                'pembuat_id' => $doc->created_by,
                // Isi modal Beri Masukan & Ajukan Revisi — sudah disaring
                // controller (belum ditindak, atau miliknya sendiri).
                'masukan' => $doc->feedback->map(fn ($m) => DocumentController::barisMasukan($m))->values(),
                // Angka revisi yang DIJANJIKAN — aturan yang sama dipakai
                // draftnya saat dikirim (Document::revisiSaatKirim).
                'janji_revisi' => DocumentService::nextEditionRevision(
                    max(1, (int) ($doc->edisi ?: 1)), (int) $doc->no_revisi,
                ),
            ]),
            'filterType' => $request->type,
            'filters' => $request->only('q', 'type', 'department_id'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            // Daftar jenis untuk penyaring & menu Export Excel. `RUPA` memuat
            // ikon per jenis; hex/ikonnya tak boleh diketik ulang di TSX (P3).
            'jenis' => \App\Models\DocumentType::kode(),
            'rupa' => \App\Models\DocumentType::RUPA,
            'prefix' => \App\Models\Pengaturan::prefix(),
            'ketersediaanPembuat' => $ketersediaan,
        ]);
    }


    /**
     * Ajukan Revisi (Tipe B) — buat versi baru dari dokumen Berlaku.
     *
     * FITUR-BARU-v4 §4: pengaju WAJIB menuliskan alasannya lebih dulu (dulu
     * langsung jadi begitu tombol ditekan), supaya pembuat tahu apa yang harus
     * diperbaiki. §5: alasan itu boleh berupa masukan lapangan Non-Staff yang
     * dicentang — masukan jadi PEMICU tindakan, bukan arsip mati.
     */
    public function requestRevision(Request $request, Document $document, DocumentFeedbackService $feedbackService): RedirectResponse
    {
        // Middleware rute menjaga PERAN; baris ini menjaga DOKUMEN MANA — bagi
        // GL hanya yang dibuatnya sendiri (Fase C, D1).
        abort_unless($document->bisaDirevisiOleh($request->user()), 403);
        abort_unless($document->status === 'published', 422, 'Hanya dokumen Berlaku yang dapat direvisi.');

        // Alasan WAJIB ditulis (Fase C, D3) — dulu boleh kosong asal ada masukan
        // yang dicentang. Alasannya dikirim ke SH/DH & MD, dan "[MSK-2026-0001]
        // pagar rusak" tanpa kalimat pengaju tak menerangkan apa pun kepada
        // mereka. Mencentang masukan tetap opsional (D2).
        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:2000'],
            'masukan' => ['nullable', 'array'],
            'masukan.*' => ['integer'],
        ], [
            'alasan.required' => 'Tuliskan alasan revisi — pembuat, SH/DH, dan MD membacanya.',
        ], ['alasan' => 'alasan revisi']);

        // Rangkaiannya — buat draft revisi, catat alasan sebagai Review
        // `needs_revision`, adopsi masukannya, kabari penyusunnya — tinggal di
        // DocumentFeedbackService sejak kanal HP ikut mengadopsi masukan
        // (PLAN-MOBILE-v6 §2.2). Yang tersisa di sini hanya bentuk permintaan
        // dan bunyi pesannya.
        [$new, , $masukan] = $feedbackService->ajukanRevisi(
            $document,
            $request->user(),
            $data['alasan'] ?? null,
            $data['masukan'] ?? [],
        );

        // Angka yang DIJANJIKAN, bukan yang tersimpan: sejak butir 7a nomor
        // revisi baru naik saat draft dikirim, jadi kolomnya masih memikul
        // angka versi terbit. Menyebut angka itu di pesan akan berbunyi
        // "diajukan revisi (Rev 0)" — persis kebalikan dari yang terjadi.
        [$edisiKirim, $revisiKirim] = $new->revisiSaatKirim();

        $catatanMasukan = $masukan->isEmpty() ? '' : " {$masukan->count()} masukan lapangan diadopsi.";

        return back()->with('status', "Revisi {$new->displayNumber()} (akan menjadi Edisi {$edisiKirim} Rev {$revisiKirim}) dibuat & dikembalikan ke pembuat ({$new->creator->name}). Versi lama tetap Berlaku sampai revisi disahkan.{$catatanMasukan}");
    }

    /**
     * Rollback (butir 7): kembalikan isi dokumen ke salah satu versi Tidak
     * Berlaku, lewat draft revisi biasa.
     *
     * Nol gerbang persetujuan tersendiri (keputusan pemilik G1): yang lahir di
     * sini hanyalah draft revisi milik pembuat asli, dan draft itu menempuh
     * alur pengesahan yang sudah ada. Versi yang Berlaku sekarang TETAP berlaku
     * sampai revisi ini disahkan.
     */
    public function rollback(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->status === 'obsolete', 422, 'Hanya versi Tidak Berlaku yang dapat dikembalikan.');

        // Dokumen yang DIMATIKAN (Fase F) tak bisa di-rollback: ia dimatikan
        // dengan sengaja lewat tiga persetujuan dan nomornya sudah dilepas,
        // jadi jalan kembalinya adalah tombol "Aktifkan", bukan tombol ini.
        abort_if($document->nomorDilepas(), 422,
            'Dokumen ini dinonaktifkan lewat persetujuan berjenjang — aktifkan kembali lewat tombol Aktifkan, bukan Rollback.');

        // Saudara yang masih HIDUP: Berlaku, atau sedang direvisi. Rollback
        // selalu menyasar dokumen yang aktif; versi lama hanya menyumbang isi.
        $aktif = Document::where('doc_number', $document->doc_number)
            ->whereIn('status', ['published', 'sedang_direvisi'])
            ->latest('id')->first();

        abort_unless($aktif, 422, 'Tidak ada versi yang Berlaku dari dokumen ini — tak ada yang bisa dikembalikan.');

        // `pemilikRevisi()` (bukan `bisaDirevisiOleh()`) supaya orang yang BENAR
        // atas dokumen yang statusnya salah mendapat 422 di baris berikutnya,
        // bukan 403 yang berbunyi "Anda tidak berhak".
        abort_unless($aktif->pemilikRevisi($user), 403);
        abort_unless($aktif->status === 'published', 422,
            'Dokumen ini sedang direvisi — selesaikan atau batalkan revisi itu dulu.');

        $draft = $this->documents->rollbackDraft($document, $user);

        [$edisiKirim, $revisiKirim] = $draft->revisiSaatKirim();

        return back()->with('status', "Isi Edisi {$document->edisi} Revisi {$document->no_revisi} disalin menjadi draft revisi baru (akan menjadi Edisi {$edisiKirim} Rev {$revisiKirim}) milik {$draft->creator->name}. Versi yang berlaku sekarang tetap berlaku sampai revisi ini disahkan.");
    }

    /**
     * Batalkan Revisi Tipe B (v3.1 §4.3): dokumen "Sedang Direvisi" batal
     * diperbarui → versi lama kembali Berlaku, versi baru (belum terbit) dibuang.
     */
    public function cancelRevisionB(Request $request, Document $document): RedirectResponse
    {
        // Yang boleh membatalkan = yang boleh memulai. `bisaDirevisiOleh()`
        // mensyaratkan status `published`, sedangkan di sini statusnya justru
        // `sedang_direvisi` — jadi yang dipinjam hanya bagian KEPEMILIKANNYA.
        $user = $request->user();
        abort_unless($user->can('document.request_revision')
            && ($user->can('document.view_all') || $document->created_by === $user->id), 403);
        abort_unless($document->status === 'sedang_direvisi', 422, 'Hanya dokumen yang sedang direvisi yang dapat dibatalkan.');

        \Illuminate\Support\Facades\DB::transaction(function () use ($document) {
            // Versi baru = dokumen bernomor sama yang belum terbit. JANGAN cari via
            // no_revisi + 1 — roll-over edisi membuat revisi berikutnya bernomor 0.
            $new = Document::where('doc_number', $document->doc_number)
                ->where('id', '!=', $document->id)
                ->whereIn('status', ['draft', 'waiting_for_review', 'in_review', 'rejected'])
                ->latest('id')->first();

            $document->update(['status' => 'published']);   // versi lama kembali Berlaku
            $new?->delete();                                 // buang versi baru (belum terbit)
        });

        app(\App\Services\AuditService::class)->log('document.cancel_revision_b', $document->id);

        return back()->with('status', "Revisi {$document->displayNumber()} dibatalkan; versi lama kembali Berlaku.");
    }

    /**
     * "Dokumen Revisi" — dokumen yang perlu DIREVISI pembuat:
     * (a) Tipe A: ditolak peninjau/approver; (b) Tipe B: draft revisi dari
     * pengajuan revisi SH/DH/PJO atas dokumen Berlaku (revises_document_id).
     */
    public function revisions(Request $request)
    {
        $user = $request->user();

        // Hanya PEMBUAT yang melihat dokumennya di sini. Peninjau memantau lewat
        // Tinjau Dokumen → Status Revisi (v3.1 §0/§4.2), bukan menu ini.
        $documents = Document::with('type', 'department', 'reviews.annotations')
            ->where(function ($q) {
                $q->where('status', 'rejected')
                    ->orWhere(fn ($w) => $w->whereNotNull('revises_document_id')->where('status', 'draft'));
            })
            ->when(! $user->can('document.view_all'), fn ($q) => $q->where('created_by', $user->id))
            ->latest('updated_at')
            ->urut($request->sort, $request->dir)
            ->paginate(15)->withQueryString();

        /*
        | Anotasi TANPA komentar dibuang — aturan yang sama dengan
        | {@see \App\Services\CatatanPerItem} dan {@see \App\Services\ReviewScreen}.
        |
        | Ia memang ada: sejak tanda ✓/✗ JSA, `ReviewDecision::simpanAnotasi()`
        | menulis baris yang HANYA memikul `verdict`, `comment`-nya null. Daftar
        | ini dulu mengirimkannya apa adanya, dan `potong()` di layar memanggil
        | `.length` atasnya — satu tinjauan JSA bertanda ✗ tanpa catatan sudah
        | cukup membuat SELURUH halaman ini putih kosong di peramban. Tipenya di
        | `types/dokumen.d.ts` sejak awal menyatakan `komentar: string`, jadi
        | yang salah adalah kirimannya, bukan layarnya.
        */
        $berkomentar = fn (Document $doc) => $doc->reviews
            ->flatMap
            ->annotations
            ->filter(fn ($a) => filled($a->comment))
            ->values();

        return Inertia::render('Documents/Revisions', [
            'documents' => $documents->through(fn (Document $doc) => $doc->barisDaftar($user) + [
                // Draft revisi Tipe B punya lencananya sendiri: yang perlu
                // dibaca pembuatnya adalah "Edisi berapa yang sedang disusun",
                // bukan kata "Draft".
                'draft_revisi' => $doc->isRevisionDraft() && $doc->status === 'draft',
                'status_label' => $doc->statusLabel(),
                // Alasan Ajukan Revisi / penolakan approver disimpan sbg
                // rangkuman review (tanpa anotasi per-item) — ikut ditampilkan
                // supaya pembuat tahu APA yang harus diperbaiki sebelum membuka
                // form (FITUR-BARU-v4 §4).
                'rangkuman' => $doc->reviews->firstWhere('decision', 'needs_revision')?->summary,
                // Empat anotasi pertama saja; sisanya dihitung, bukan dikirim.
                'anotasi' => $berkomentar($doc)
                    ->take(4)
                    ->map(fn ($a) => ['bagian' => $a->section_key, 'komentar' => $a->comment])
                    ->values(),
                'anotasi_sisa' => max(0, $berkomentar($doc)->count() - 4),
            ]),
        ]);
    }

    /** "Dokumen Tidak Berlaku" (obsolete) — daftar + opsi hapus (v2 Fase D).
     *  GL boleh MELIHAT (read-only, dept sendiri); hapus tetap SH/DH/PJO/Admin. */
    public function obsolete(Request $request)
    {
        $user = $request->user();
        // Daftar arsip terbuka bagi semua KECUALI Non-Staff (dulu tiga izin
        // di-OR yang kebetulan berjumlah sama; sejak Fase C tiga izin itu tak
        // lagi menjangkau PJO tanpa `view_all`).
        abort_unless($user->dashboardPenuh(), 403);
        $canAll = $user->can('document.view_all');

        $query = Document::with('type', 'department', 'creator')
            ->where('status', 'obsolete')
            // Satu BARIS per dokumen, bukan per versi: tiap revisi Tipe B
            // mewarisi `doc_number` yang sama (ApprovalController::store), jadi
            // dokumen berusia 5 revisi dulu memakan 5 baris di daftar ini.
            // ponytail: id terbesar = versi terbaru, karena draft revisi SELALU
            // dibuat sesudah induknya (DocumentService::requestRevision). Bila
            // kelak ada impor massal yang mengacak urutan id, ganti dengan
            //   ROW_NUMBER() OVER (PARTITION BY doc_number
            //                      ORDER BY CAST(edisi AS UNSIGNED) DESC, no_revisi DESC).
            ->whereIn('id', fn ($sub) => $sub->selectRaw('MAX(id)')->from('documents')
                ->where('status', 'obsolete')->whereNull('deleted_at')->groupBy('doc_number'))
            ->when(! $canAll, fn ($q) => $q->where('department_id', $user->department_id))
            ->when($canAll && $request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->latest('updated_at');

        $documents = $query->urut($request->sort, $request->dir)->paginate(15)->withQueryString();

        // Relasinya ikut dimuat di sini: sejak baris versi memakai bentuk yang
        // SAMA dengan baris induk (`$baris` di bawah), tiap versi menyentuh
        // `type`/`department`/`creator` — tanpa eager load itu satu halaman
        // 15 grup berubah jadi puluhan query.
        $versi = Document::with('type', 'department', 'creator')
            ->whereIn('doc_number', $documents->pluck('doc_number'))
            ->where('status', 'obsolete')
            ->orderByRaw('CAST(edisi AS UNSIGNED) DESC')->orderByDesc('no_revisi')
            ->get()->groupBy('doc_number');

        $nomorTerpakai = $this->nomorMasihDipakai($documents->pluck('doc_number_final'));

        // Grup mana yang masih punya versi Berlaku — tombol Rollback (Fase G)
        // hanya masuk akal di sana: yang dikembalikan adalah ISI dokumen yang
        // sedang berlaku, versi lama cuma sumbernya. Satu query untuk seluruh
        // halaman, bukan satu per baris.
        $adaBerlaku = Document::whereIn('doc_number', $documents->pluck('doc_number'))
            ->where('status', 'published')->pluck('doc_number');

        // Satu bentuk baris untuk induk MAUPUN baris versi di dalamnya: dua
        // daftar tombol yang harus sepakat adalah dua daftar yang suatu hari
        // tidak sepakat (alasan yang sama seperti `documents/_tombol-rollback`).
        $baris = fn (Document $v) => $v->barisDaftar($user) + [
            'nomor_dilepas' => $v->nomorDilepas(),
            'dinonaktifkan' => $v->updated_at?->format('d/m/Y H:i'),
            /*
            | Tiga syarat Rollback, cerminan persis penjaga di
            | DocumentRevisionController::rollback(): grupnya masih punya versi
            | Berlaku (itulah yang isinya ditimpa), versi ini bukan dokumen yang
            | DIMATIKAN (nomornya sudah dilepas — jalannya "Aktifkan"), dan
            | penekannya berhak merevisi dokumen ini.
            */
            'boleh_rollback' => $adaBerlaku->contains($v->doc_number)
                && ! $v->nomorDilepas()
                && $v->pemilikRevisi($user),
            // Nomor yang sudah dilepas SELALU diganti saat diaktifkan lagi
            // (Fase F) — server memeriksanya ulang, jadi ini hanya menerangkan
            // lebih dulu.
            'nomor_bentrok' => $v->nomorDilepas()
                || (filled($v->doc_number_final) && $nomorTerpakai->contains($v->doc_number_final)),
            'nomor_final' => $v->doc_number_final,
            // Arsipkan = wewenang GL (dokumen buatannya) / MD / Admin sejak Fase
            // C. Syaratnya SAMA PERSIS dengan DocumentController::destroy().
            'boleh_arsipkan' => $user->can('document.request_revision')
                && ($user->can('document.view_all') || $v->created_by === $user->id),
            'boleh_aktifkan' => $user->can('user.manage'),
            'boleh_musnahkan' => $user->can('user.manage'),
        ];

        return Inertia::render('Documents/Obsolete', [
            'documents' => $documents->through(fn (Document $doc) => $baris($doc) + [
                // Seluruh versi milik grup ini, induknya sendiri termasuk
                // (lihat query `$versi` di atas). Grup berisi SATU versi tak
                // punya penyingkap — dokumen yang DIMATIKAN memang selalu
                // tunggal, dan tombolnya ikut ke baris induk.
                'versi' => ($versi[$doc->doc_number] ?? collect([$doc]))->map($baris)->values(),
            ]),
            'filters' => $request->only('q', 'department_id'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            'prefix' => \App\Models\Pengaturan::prefix(),
        ]);
    }

    /**
     * Aktifkan kembali dokumen Tidak Berlaku → Berlaku. Hanya Admin IT.
     *
     * Dokumen jadi obsolete umumnya KARENA revisinya disahkan, dan penerusnya
     * memakai nomor yang sama. Mengaktifkannya begitu saja menghasilkan dua
     * dokumen Berlaku bernomor sama — keadaan yang tak bisa dijelaskan kepada
     * auditor. Karena itu bentroknya dideteksi di sini, dan pengaktifan hanya
     * berlanjut bila pemakainya sadar-sadar meminta nomor baru (`nomor_baru`).
     */
    public function restoreObsolete(Request $request, Document $document): RedirectResponse
    {
        abort_unless($request->user()->can('user.manage'), 403);
        abort_unless($document->status === 'obsolete', 422, 'Hanya dokumen Tidak Berlaku yang dapat diaktifkan kembali.');

        $lama = $document->doc_number_final;

        // Diperiksa ULANG di sini, tidak bergantung pada apa yang dikirim
        // halaman: permintaan bisa datang dari mana saja.
        $bentrok = $this->nomorMasihDipakai(collect([$lama]), kecuali: $document->id)->isNotEmpty();

        /*
        | Nomor yang sudah DILEPAS (Fase F, F2) SELALU diganti, bentrok atau
        | tidak. Sejak dilepas ia kembali ke kolam dan boleh diambil dokumen
        | mana pun — termasuk draft yang belum disahkan, yang tak terbaca oleh
        | `nomorMasihDipakai()` (ia hanya menghitung yang Berlaku). Memulihkan
        | dokumen ini dengan nomor lamanya berarti mempertaruhkan dua dokumen
        | bernomor sama pada hari draft itu terbit.
        */
        $dilepas = $document->nomorDilepas();
        $perluBaru = $bentrok || $dilepas;

        if ($perluBaru && ! $request->boolean('nomor_baru')) {
            return back()->with('error', $dilepas
                ? "Nomor {$lama} sudah dilepas saat {$document->title} dinonaktifkan, jadi dokumen ini hanya bisa diaktifkan dengan NOMOR BARU. Ulangi lewat tombol Aktifkan."
                : "Nomor {$lama} masih dipakai dokumen yang Berlaku, jadi {$document->title} tidak diaktifkan. Ulangi lewat tombol Aktifkan bila memang hendak diberi nomor baru.");
        }

        // Nomor urut terkecil yang belum terpakai pada jenis+dept — aturan yang
        // SAMA dengan pengesahan biasa, bukan penomoran khusus buatan sendiri.
        // Dihitung SEBELUM `obsolete_reason` dikosongkan, jadi nomor yang tadi
        // dilepas masih di kolam: bila belum diambil siapa pun, dokumen ini
        // mendapatkannya kembali.
        $baru = $perluBaru
            ? app(\App\Services\DocumentNumberService::class)->generateFinal($document->type, $document->department)
            : $lama;

        // `obsolete_reason` dikosongkan: dokumen yang Berlaku menahan nomornya
        // lagi, apa pun sebab ia dulu dimatikan.
        $document->update(['status' => 'published', 'doc_number_final' => $baru, 'obsolete_reason' => null]);

        app(\App\Services\AuditService::class)->log('document.restore_obsolete', $document->id, [
            'to' => 'published', 'nomor_lama' => $lama, 'nomor_baru' => $baru,
        ]);

        $catatan = ($perluBaru && $baru !== $lama)
            ? " Nomornya diganti dari {$lama} menjadi {$baru}."
            : '';

        return back()->with('status', "Dokumen {$document->displayNumber()} kembali Berlaku.{$catatan}");
    }

    /**
     * Dari sekumpulan nomor final, mana yang masih dipegang dokumen AKTIF
     * (Berlaku / Sedang Direvisi).
     *
     * @param  \Illuminate\Support\Collection<int, ?string>  $nomor
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function nomorMasihDipakai($nomor, ?int $kecuali = null)
    {
        $nomor = $nomor->filter()->unique()->values();

        if ($nomor->isEmpty()) {
            return $nomor;
        }

        return Document::berlaku()
            ->whereIn('doc_number_final', $nomor)
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->pluck('doc_number_final');
    }
}
