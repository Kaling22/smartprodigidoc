<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\MasukanSejawat;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;

/**
 * Menu "Log Dokumen" (PLAN-REVISI-v6 Fase C, keputusan D5).
 *
 * Dua daftar yang SELAMA INI SUDAH ADA datanya, tapi tercecer sebagai tempelan
 * di halaman lain sehingga tak pernah bisa dibaca utuh:
 *
 *   • Masukan Lapangan — dulu hanya muncul sebagai lencana di Dokumen Berlaku,
 *     panel di halaman dokumen, dan kartu dashboard. Tak ada satu tempat pun
 *     yang menjawab "masukan apa saja yang belum ditindak minggu ini".
 *   • Log Pesan — tiap pesan yang pernah ditulis atas sebuah dokumen:
 *     pengajuan revisi, penolakan peninjau/MD/penyetuju, pengalihan peninjauan
 *     JSA, alasan pemusnahan, balasan atas masukan lapangan, dan masukan
 *     sejawat antar-GL. Sebelum ini alasan penolakan hanya terbaca oleh
 *     pembuat, di form revisinya, dan lenyap begitu dokumennya terbit; alasan
 *     pemusnahan tak pernah terbaca siapa pun.
 *
 * Keduanya BACA SAJA kecuali dua tombol yang meminjam alur yang sudah ada
 * (Balas & Revisi) — halaman ini tidak memperkenalkan aturan wewenang baru.
 * Tombol per baris ({@see tautan()}) pun hanya MENGANTAR ke layar yang sudah
 * punya penjaganya sendiri, dan hanya ditampilkan kepada orang yang lolos
 * penjaga itu.
 */
class DocumentLogController extends Controller
{
    use Concerns\AuthorizesDocumentAccess;

    /** Berapa baris terbaru yang dipindai per sumber pesan. */
    private const BATAS_SUMBER = 500;

    /**
     * Submenu 1 — Masukan Lapangan.
     *
     * Lingkupnya menumpang `Document::scopeTerlihatOleh` lewat `whereHas`, bukan
     * pagar departemen kedua yang ditulis di sini: "dokumen mana yang boleh
     * dilihat siapa" hanya boleh punya satu jawaban di seluruh aplikasi.
     * Non-Staff dipersempit lagi ke kirimannya sendiri.
     */
    public function masukan(Request $request)
    {
        $user = $request->user();
        abort_unless($user->dashboardPenuh(), 403);

        $masukan = DocumentFeedback::with(['document.type', 'document.department', 'document.creator', 'user', 'replier'])
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($user->can('document.view_all') && $request->filled('department_id'),
                fn ($q) => $q->whereHas('document', fn ($d) => $d->where('department_id', $request->department_id)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('isi', 'like', "%{$request->q}%")
                ->orWhere('feedback_number', 'like', "%{$request->q}%")))
            ->latest()
            ->paginate(20)->withQueryString();

        /*
        | Jendela "Revisi" dikelompokkan per DOKUMEN, bukan per masukan: beberapa
        | masukan bisa menunjuk dokumen yang sama, dan Blade lama pun sudah
        | meng-`groupBy('document_id')` sebelum meng-`@include` modalnya. Yang
        | pindah ke props hanyalah hasil pengelompokan itu — mengulanginya di
        | klien berarti aturan "siapa boleh mengadopsi" ikut pindah ke sana.
        |
        | Dihitung SEBELUM `through()`, yang mengganti isi paginator dengan larik
        | datar sehingga `pluck('document.creator')` sesudahnya kosong.
        */
        $adopsi = $masukan->getCollection()
            ->filter(fn (DocumentFeedback $m) => $m->document && $m->bisaDiadopsiOleh($user))
            ->groupBy('document_id');

        $ketersediaan = DocumentController::jadwalPembuat($masukan->pluck('document.creator'));

        return Inertia::render('Log/Masukan', [
            /*
            | `->through()`, bukan `->map()` (pelajaran Fase 6). Diratakan lewat
            | `DocumentController::barisMasukan()` — bentuk yang sama dipakai
            | halaman detail dokumen & Dokumen Berlaku sejak Fase 8, jadi rupa
            | status/balasannya mustahil berbeda antar layar. Yang TIDAK ikut:
            | relasi `user`, `replier`, dan `document.creator` — tiga model User
            | utuh beserta `password`-nya.
            */
            'masukan' => $masukan->through(fn (DocumentFeedback $m) => DocumentController::barisMasukan($m) + [
                'dokumen' => $m->document ? [
                    'id' => $m->document->id,
                    'nomor' => $m->document->displayNumber(),
                    'judul' => $m->document->title,
                ] : null,
                // Wewenang per BARIS, dijawab model — bukan diulang di klien.
                'boleh_balas' => $m->bisaDibalasOleh($user),
                'boleh_revisi' => $m->document && $m->bisaDiadopsiOleh($user),
            ]),
            'revisi' => $adopsi->map(fn (Collection $grup) => [
                'id' => $grup->first()->document->id,
                'nomor' => $grup->first()->document->displayNumber(),
                'pembuat_id' => $grup->first()->document->created_by,
                // Angka yang DIJANJIKAN, bukan `no_revisi + 1`: revisi berguling
                // di angka 5 jadi Edisi+1 Rev 0 (CLAUDE.md §7).
                'janji_revisi' => \App\Services\DocumentService::nextEditionRevision(
                    max(1, (int) ($grup->first()->document->edisi ?: 1)),
                    (int) $grup->first()->document->no_revisi,
                ),
                'masukan' => $grup->map(fn (DocumentFeedback $m) => DocumentController::barisMasukan($m))->values(),
                // Masukan pemicunya sudah tercentang — perilaku `$masukanTercentang`
                // di `documents/_modal-revisi`, yang HANYA layar ini memakainya.
                'tercentang' => $grup->pluck('id')->values(),
            ])->values(),
            'filters' => $request->only('q', 'status', 'department_id'),
            'departments' => $user->can('document.view_all') ? Department::orderBy('code')->get() : collect(),
            'statusOpsi' => DocumentFeedback::STATUS_LABELS,
            'bolehMembalas' => (bool) $user->can('document.feedback_respond'),
            'ketersediaanPembuat' => $ketersediaan,
        ]);
    }

    /**
     * Submenu 2 — Log Pesan.
     *
     * Dulu bernama "Alasan Dokumen", tapi isinya sudah melampaui alasan: balasan
     * SH/DH kepada lapangan bukan alasan, ia pesan. Namanya diseragamkan
     * route/view/method sekaligus (CLAUDE.md §3).
     *
     * ponytail: memindai maks 500 baris terbaru per ENAM sumber lalu memaginasi
     * di PHP. Cukup untuk volume internal ini; kalau kelak melambat, ganti
     * dengan satu VIEW SQL `document_pesan` (UNION ALL) dan paginasi di
     * database — jalur itu justru makin relevan dengan enam sumber. TIDAK
     * dibuat tabel `document_logs` baru: itu berarti menyalin data yang sudah
     * ada ke tempat kedua yang bisa berbeda isinya.
     */
    public function pesan(Request $request)
    {
        $user = $request->user();
        abort_unless($user->dashboardPenuh(), 403);

        $baris = $this->dariReviews($request)
            ->merge($this->dariApprovals($request))
            ->merge($this->dariAuditLogs($request))
            ->merge($this->dariPemusnahan($request))
            ->merge($this->dariBalasanMasukan($request))
            ->merge($this->dariMasukanSejawat($request))
            ->filter(fn (array $b) => trim((string) $b['alasan']) !== '')
            ->when($request->filled('tahap'), fn ($c) => $c->where('tahap', $request->tahap))
            ->when($request->filled('department_id'),
                fn ($c) => $c->where('department_id', (int) $request->department_id))
            ->when($request->filled('dari'),
                fn ($c) => $c->filter(fn ($b) => $b['tanggal']->gte(Carbon::parse($request->dari)->startOfDay())))
            ->when($request->filled('sampai'),
                fn ($c) => $c->filter(fn ($b) => $b['tanggal']->lte(Carbon::parse($request->sampai)->endOfDay())))
            ->when($request->filled('q'), fn ($c) => $c->filter(fn ($b) => str_contains(
                mb_strtolower($b['alasan'].' '.$b['nomor'].' '.$b['judul']), mb_strtolower($request->q))))
            ->sortByDesc(fn (array $b) => $b['tanggal'])
            ->values();

        $halaman = max(1, (int) $request->input('page', 1));
        $perHalaman = 20;

        $pesan = new LengthAwarePaginator(
            $baris->slice(($halaman - 1) * $perHalaman, $perHalaman)->values(),
            $baris->count(), $perHalaman, $halaman,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('Log/Pesan', [
            // `tanggal` masih Carbon selama disaring & diurutkan di atas; yang
            // menyeberang ke props sudah jadi teks. Sisanya memang sudah datar
            // sejak {@see baris()} — `document` & `oleh` tak pernah ikut sebagai
            // model, sebab keduanya menggendong `password`/`arsip_path`.
            'pesan' => $pesan->through(fn (array $b) => [
                'tanggal' => $b['tanggal']->format('d/m/Y H:i'),
                'nomor' => $b['nomor'],
                'judul' => $b['judul'],
                'dept' => $b['dept'],
                'tahap' => $b['tahap'],
                'oleh' => $b['oleh'],
                'alasan' => $b['alasan'],
                'tautan' => $b['tautan'],
            ]),
            'filters' => $request->only('q', 'tahap', 'department_id', 'dari', 'sampai'),
            'tahapan' => self::TAHAP,
            'departments' => $user->can('document.view_all') ? Department::orderBy('code')->get() : collect(),
            'prefix' => \App\Models\Pengaturan::prefix(),
        ]);
    }

    /** Label & warna tiap tahap — dipakai penyaring DAN badge di tabel. */
    public const TAHAP = [
        'pengajuan_revisi' => ['Pengajuan revisi', 'warning'],
        'ditolak_peninjau' => ['Ditolak peninjau', 'danger'],
        'ditolak_md' => ['Ditolak Management Development', 'danger'],
        'ditolak_penyetuju' => ['Ditolak penyetuju', 'danger'],
        'pengalihan' => ['Peninjauan dialihkan', 'info'],
        'nonaktif_ajukan' => ['Pengajuan nonaktif', 'warning'],
        'nonaktif_tolak' => ['Pengajuan nonaktif ditolak', 'secondary'],
        'musnahkan' => ['Dokumen dimusnahkan', 'danger'],
        'balasan_masukan' => ['Balasan masukan', 'info'],
        'masukan_sejawat' => ['Masukan sejawat', 'primary'],
    ];

    /**
     * Alasan yang tersimpan sebagai `reviews.summary`.
     *
     * Tahapnya dibedakan dari AWALAN yang sudah dipakai penulisnya — awalan itu
     * bukan hiasan, ia satu-satunya penanda asal yang tersimpan:
     * `[Pengaju Revisi]` (DocumentFeedbackService), `[Management Development]`
     * (MdReviewController), `[Penyetuju]` (ApprovalController).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariReviews(Request $request): Collection
    {
        $user = $request->user();

        return Review::with(['reviewer', 'document.department', 'document.type'])
            ->where('decision', 'needs_revision')
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(function (Review $r) use ($request) {
                $ringkas = (string) $r->summary;

                [$tahap, $bersih] = match (true) {
                    str_starts_with($ringkas, '[Pengaju Revisi]') => ['pengajuan_revisi', trim(substr($ringkas, 16))],
                    str_starts_with($ringkas, '[Management Development]') => ['ditolak_md', trim(substr($ringkas, 24))],
                    str_starts_with($ringkas, '[Penyetuju]') => ['ditolak_penyetuju', trim(substr($ringkas, 11))],
                    default => ['ditolak_peninjau', $ringkas],
                };

                return $this->baris($request, $r->document, $tahap, $r->reviewer, $bersih, $r->created_at);
            })->toBase();
    }

    /**
     * Alasan yang tersimpan sebagai `approvals.comment` — dua rupa sekaligus:
     *
     *   • `kind = 'pengesahan'` + ditolak → penolakan penyetuju. Beririsan
     *     dengan `[Penyetuju]` di atas — keduanya ditulis pada penolakan yang
     *     sama — tapi bukan salinan: yang ini memuat nama PENYETUJUNYA.
     *   • `kind = 'nonaktif'` → pengajuan & penolakan nonaktif berjenjang
     *     (Fase F). Itulah yang dimaksud "halaman ini membacanya gratis":
     *     alurnya menumpang tabel yang sudah dibaca di sini.
     *
     * Baris nonaktif yang DISETUJUI tak punya alasan tertulis, jadi ia gugur
     * sendiri di penyaring `alasan kosong` milik {@see alasan()}.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariApprovals(Request $request): Collection
    {
        $user = $request->user();

        return Approval::with(['approver', 'document.department', 'document.type'])
            ->where(fn ($q) => $q->where('decision', 'rejected')
                ->orWhere('kind', Approval::KIND_NONAKTIF))
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(fn (Approval $a) => $this->baris($request,
                $a->document,
                match (true) {
                    $a->kind !== Approval::KIND_NONAKTIF => 'ditolak_penyetuju',
                    $a->decision === 'rejected' => 'nonaktif_tolak',
                    default => 'nonaktif_ajukan',
                },
                $a->approver, (string) $a->comment, $a->created_at,
            ))->toBase();
    }

    /**
     * Alasan pengalihan peninjauan JSA — satu-satunya alasan yang tak punya
     * tabelnya sendiri; ia tersimpan di `audit_logs.meta_json['alasan']`
     * (ReviewController::alihkan, Fase B).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariAuditLogs(Request $request): Collection
    {
        $user = $request->user();

        return AuditLog::with(['user', 'document.department', 'document.type'])
            ->where('action', 'document.reassign_review')
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(fn (AuditLog $l) => $this->baris($request,
                $l->document, 'pengalihan', $l->user, (string) ($l->meta_json['alasan'] ?? ''), $l->created_at,
            ))->toBase();
    }

    /**
     * Alasan PEMUSNAHAN dokumen — satu-satunya sumber yang tak bisa menumpang
     * `whereHas('document')`, dan justru karena itu selama ini tak pernah
     * terbaca siapa pun.
     *
     * `DocumentPurger` menulis auditnya dengan `document_id = null` dan memang
     * harus begitu: dokumennya lenyap sedetik kemudian di transaksi yang sama.
     * Akibatnya alasan yang WAJIB minimal 10 karakter itu tersaring habis oleh
     * `dariAuditLogs()`. Di sini nomor, judul, dan departemennya dibaca dari
     * `meta_json` — satu-satunya jejak yang tersisa.
     *
     * Pagar departemennya menyalin `Document::scopeTerlihatOleh` (departemen
     * sendiri, kecuali pemegang `document.view_all`), dibaca dari meta karena
     * tak ada lagi baris dokumen untuk di-join.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariPemusnahan(Request $request): Collection
    {
        $user = $request->user();

        $kodeDept = Department::pluck('code', 'id');

        return AuditLog::with('user')
            ->where('action', 'document.purge')
            ->unless($user->can('document.view_all'),
                fn ($q) => $q->where('meta_json->department_id', $user->department_id))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(function (AuditLog $l) use ($kodeDept, $request) {
                $meta = $l->meta_json ?? [];
                $deptId = isset($meta['department_id']) ? (int) $meta['department_id'] : null;

                return $this->baris($request, null, 'musnahkan', $l->user, (string) ($meta['alasan'] ?? ''), $l->created_at, [
                    'nomor' => $meta['doc_number'] ?? '—',
                    'judul' => $meta['title'] ?? '—',
                    'department_id' => $deptId,
                    'dept' => $kodeDept[$deptId] ?? '—',
                ]);
            })->toBase();
    }

    /**
     * Balasan SH/DH atas masukan lapangan.
     *
     * Selama ini hanya terbaca di submenu Masukan Lapangan, di dalam baris
     * masukannya. Log Pesan adalah gabungan BACA-SAJA atas seluruh pesan yang
     * pernah ditulis atas sebuah dokumen — dan balasan kepada lapangan adalah
     * pesan, bukan catatan sampingan.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariBalasanMasukan(Request $request): Collection
    {
        $user = $request->user();

        return DocumentFeedback::with(['replier', 'document.department', 'document.type'])
            ->whereNotNull('balasan')
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(fn (DocumentFeedback $f) => $this->baris($request,
                $f->document, 'balasan_masukan', $f->replier, (string) $f->balasan,
                $f->replied_at ?? $f->created_at,
            ))->toBase();
    }

    /**
     * Sumber ke-6 — masukan sejawat antar-GL (PLAN-AKSES-v8 Fase 2).
     *
     * `alasan` diambil dari ringkasan; bila pemberinya hanya mengisi catatan
     * per bagian tanpa ringkasan, komentar PERTAMA dipakai sebagai gantinya.
     * Tanpa itu barisnya lenyap tanpa jejak di penyaring "alasan kosong"
     * ({@see pesan()}) — hilang justru untuk masukan yang paling rinci.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dariMasukanSejawat(Request $request): Collection
    {
        $user = $request->user();

        return MasukanSejawat::with(['user', 'document.department', 'document.type'])
            ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
            ->latest('id')->limit(self::BATAS_SUMBER)->get()
            ->map(fn (MasukanSejawat $m) => $this->baris(
                $request, $m->document, 'masukan_sejawat', $m->user,
                (string) ($m->ringkasan ?: ($m->catatan_json[0]['komentar'] ?? '')),
                $m->created_at,
            ))->toBase();
    }

    /**
     * Satu baris tabel Log Pesan. Bentuknya array (bukan model) karena keenam
     * sumbernya tak punya nenek moyang bersama, dan membuatkannya satu hanya
     * demi halaman ini adalah abstraksi yang tak dipakai siapa pun lagi.
     *
     * `$tanpaDokumen` dipakai HANYA oleh pemusnahan: dokumennya sudah tak ada,
     * jadi nomor/judul/departemennya datang dari `meta_json`. Ditaruh di sini,
     * bukan sebagai array kedua yang dirakit sendiri di sana, supaya bentuk
     * barisnya tetap satu — dua bentuk yang harus sepakat adalah dua bentuk yang
     * suatu hari tidak sepakat.
     *
     * @param  array<string, mixed>  $tanpaDokumen
     * @return array<string, mixed>
     */
    private function baris(Request $request, ?Document $document, string $tahap, ?User $oleh, string $alasan, $tanggal, array $tanpaDokumen = []): array
    {
        return [
            'tanggal' => $tanggal ?? now(),
            'nomor' => $document?->displayNumber() ?? $tanpaDokumen['nomor'] ?? '—',
            'judul' => $document?->title ?? $tanpaDokumen['judul'] ?? '—',
            'department_id' => $document?->department_id ?? $tanpaDokumen['department_id'] ?? null,
            'dept' => $document?->department?->code ?? $tanpaDokumen['dept'] ?? '—',
            'tahap' => $tahap,
            // NAMA, bukan model: `$oleh` adalah User utuh beserta `password` &
            // `remember_token`, dan larik ini berakhir di props Inertia.
            'oleh' => $oleh?->nameWithJabatan(),
            'alasan' => $alasan,
            'tautan' => $this->tautan($request, $document, $tahap),
        ];
    }

    /**
     * Tombol baris Log Pesan — menuju TEMPAT pesan itu ditindaklanjuti, bukan
     * selalu ke halaman dokumen (PLAN-AKSES-v8 Fase 3b).
     *
     * Bergantung PENONTON, bukan hanya tahap: alasan penolakan yang sama
     * mengantar PEMBUATNYA ke form revisi dan orang lain ke halaman dokumen.
     * Sebelumnya tiap baris punya tombol yang persis sama (`documents.show`),
     * sehingga pembuat yang membaca alasan penolakannya di sini masih harus
     * mencari sendiri jalan ke form revisinya.
     *
     * Dihitung di CONTROLLER, dan label serta ikonnya ikut dari sini, supaya
     * tak ada peta kedua di Blade yang bisa menyimpang.
     *
     * `null` = tanpa tombol. Itu terjadi pada baris pemusnahan: dokumennya
     * sudah lenyap, jadi tak ada yang bisa dituju.
     *
     * @return array{url: string, label: string, ikon: string}|null
     */
    private function tautan(Request $request, ?Document $document, string $tahap): ?array
    {
        if (! $document) {
            return null;
        }

        $user = $request->user();

        $dokumen = [
            'url' => route('documents.show', $document),
            'label' => 'Dokumen',
            'ikon' => 'bi-box-arrow-up-right',
        ];

        return match (true) {
            // Pesan "perbaiki dokumenmu" — hanya bagi yang MEMANG bisa
            // menyuntingnya sekarang. Syaratnya dipinjam dari trait yang sama
            // dengan penjaga rute edit-nya, jadi tombol ini mustahil menawarkan
            // halaman yang kelak menolaknya.
            in_array($tahap, ['pengajuan_revisi', 'ditolak_peninjau', 'ditolak_md', 'ditolak_penyetuju'], true)
                && $this->isEditable($document, $request) => [
                    'url' => route('documents.edit', $document),
                    'label' => 'Revisi',
                    'ikon' => 'bi-pencil-square',
                ],

            // Pengalihan: yang MEMEGANG dokumennya diantar ke meja tinjauannya.
            $tahap === 'pengalihan' && $document->reviewer_id === $user->id => [
                'url' => route('review.show', $document),
                'label' => 'Tinjau',
                'ikon' => 'bi-clipboard-check',
            ],

            // Nonaktif: yang gilirannya memutuskan diantar ke antreannya.
            in_array($tahap, ['nonaktif_ajukan', 'nonaktif_tolak'], true)
                && $document->bisaMemutuskanNonaktif($user) => [
                    'url' => route('nonaktif.index'),
                    'label' => 'Putuskan',
                    'ikon' => 'bi-slash-circle',
                ],

            $tahap === 'balasan_masukan' => [
                'url' => route('log.masukan'),
                'label' => 'Masukan',
                'ikon' => 'bi-chat-left-dots',
            ],

            // Masukan sejawat: dibatasi pembuat & pengawas lintas-dokumen,
            // sebab hanya merekalah yang lolos penjaga
            // MasukanSejawatController::show. Aturan yang sama dipakai lencana
            // di Dokumen Saya — tombol yang diklik lalu 403 lebih buruk
            // daripada tombol yang tak ada.
            $tahap === 'masukan_sejawat'
                && ($document->created_by === $user->id || $user->can('document.view_all')) => [
                    'url' => route('masukan-sejawat.show', $document),
                    'label' => 'Lihat Masukan',
                    'ikon' => 'bi-chat-square-text',
                ],

            default => $dokumen,
        };
    }
}
