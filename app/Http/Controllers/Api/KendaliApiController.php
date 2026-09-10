<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentExportController;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\DocumentDistribution;
use App\Services\InformasiDistribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menu "Kendali" di aplikasi mobile: Perjalanan Dokumen, Status Dokumen,
 * Distribusi, dan Daftar Induk.
 *
 * SELURUH lingkupnya menyalin aturan yang sudah dipakai web — bukan menulis
 * aturan kedua. Rujukannya:
 *   • perjalanan()  ← DashboardController::menungguDiMeja() (tanpa limit 4)
 *   • statusDokumen() ← DocumentStaffStatusController::staffStatus()
 *   • distribusi()  ← DocumentDistributionController::distribution(), ditambah
 *                     satu cabang baru untuk GL ("dokumen yang saya susun"),
 *                     karena GL tak memegang `document.request_revision`.
 *   • daftarInduk() ← DocumentExportController::susunMatriks()
 *
 * Semuanya BACA SAJA. Tinjau, setujui, dan ajukan revisi tetap di web.
 */
class KendaliApiController extends Controller
{
    public function __construct(private readonly DocumentDistribution $distribusi) {}

    /** Status yang berarti "dokumen sedang berjalan" — sama dengan dashboard. */
    private const BERJALAN = ['waiting_for_review', 'in_review', 'verifikasi_md', 'pending_approval', 'rejected'];

    // ─────────────────────────── Perjalanan Dokumen ──────────────────────────

    public function perjalanan(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $this->lingkupPerjalanan(
            Document::with('type', 'department', 'reviewer', 'approver', 'creator'),
            $user
        );

        if ($query === null) {
            return response()->json(['data' => [], 'meta' => ['total' => 0, 'lingkup' => 'Tidak ada']], 403);
        }

        $query->whereIn('status', self::BERJALAN)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when(
                $request->filled('department') && $user->can('document.view_all'),
                fn ($q) => $q->whereHas('department', fn ($d) => $d->where('code', $request->department))
            );

        // Terlama dulu — dokumen yang paling lama diam adalah yang paling perlu
        // didorong, dan itulah satu-satunya alasan layar ini ada.
        $dokumen = $query->orderBy('submitted_at')->get();

        return response()->json([
            'data' => $dokumen->map(fn (Document $d) => $this->barisPerjalanan($d))->all(),
            'meta' => [
                'total' => $dokumen->count(),
                'lingkup' => $this->labelLingkup($user),
                'terkunci_dept' => ! $user->can('document.view_all'),
            ],
        ]);
    }

    public function perjalananShow(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $this->pastikanTerlihat($document, $user);

        $document->load('type', 'department', 'reviewer', 'approver', 'creator');

        $riwayat = AuditLog::with('user')
            ->where('document_id', $document->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (AuditLog $l) => [
                'aksi' => $l->action,
                'label' => self::AKSI_LABEL[$l->action][0] ?? str_replace(['document.', '_'], ['', ' '], $l->action),
                'rona' => self::AKSI_LABEL[$l->action][1] ?? 'neutral',
                'pelaku' => $l->user?->name ?? 'Sistem',
                'waktu' => $l->created_at?->toIso8601String(),
                'catatan' => is_array($l->meta_json) ? ($l->meta_json['ringkasan'] ?? $l->meta_json['catatan'] ?? null) : null,
            ])->all();

        return response()->json(['data' => array_merge($this->barisPerjalanan($document), [
            'edisi' => $document->edisi ?? '1',
            'no_revisi' => $document->no_revisi,
            'penyusun' => $this->orang($document->creator),
            'peninjau' => $this->orang($document->reviewer),
            'penyetuju' => $this->orang($document->approver),
            'dikirim' => $document->submitted_at?->toIso8601String(),
            'berlaku_sejak' => $document->published_at?->toIso8601String(),
            'riwayat' => $riwayat,
        ])]);
    }

    // ──────────────────────────── Status Dokumen ─────────────────────────────

    public function statusDokumen(Request $request): JsonResponse
    {
        $user = $request->user();
        $canAll = $user->can('document.view_all');

        abort_unless(
            $canAll || in_array($user->jabatan, [
                User::JABATAN_GROUP_LEADER, User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD,
            ], true),
            403,
            'Anda tidak berwenang membuka halaman ini.'
        );

        // Sama persis dengan web: PJO/Admin boleh memilih departemen, sisanya
        // dipaku ke departemennya sendiri — kode departemen dari input TIDAK
        // pernah dipercaya untuk mereka.
        $query = Document::with('type', 'department', 'creator')
            ->when(
                $canAll,
                fn ($q) => $q->when($request->filled('department'), fn ($w) => $w->whereHas('department', fn ($d) => $d->where('code', $request->department))),
                fn ($q) => $q->where('department_id', $user->department_id)
            )
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('doc_number', 'like', "%{$request->q}%")
                ->orWhere('doc_number_final', 'like', "%{$request->q}%")
                ->orWhere('title', 'like', "%{$request->q}%")))
            ->latest();

        $dokumen = $query->get();

        return response()->json([
            'data' => $dokumen->map(fn (Document $d) => [
                'id' => $d->id,
                'no_dokumen' => $d->displayNumber(),
                'nomor_sementara' => ! $d->hasFinalNumber(),
                'judul' => $d->title,
                'jenis' => $d->type?->code,
                'departemen' => $d->department?->code,
                'edisi' => $d->edisi ?? '1',
                'no_revisi' => $d->no_revisi,
                'status' => $d->status,
                'status_label' => $d->statusLabel(),
                'pembuat' => $d->creator?->name,
                'foto_pembuat' => $d->creator?->photo_path ? url($d->creator->photoUrl()) : null,
                'umur_hari' => in_array($d->status, self::BERJALAN, true)
                    ? (int) ($d->submitted_at ?? $d->updated_at)?->diffInDays(now())
                    : null,
                'tanggal' => ($d->published_at ?? $d->updated_at)?->toIso8601String(),
            ])->all(),
            'meta' => [
                'total' => $dokumen->count(),
                'lingkup' => $canAll ? '7 departemen' : 'Departemen '.($user->department?->code ?? '—'),
                'terkunci_dept' => ! $canAll,
                // Kartu penyaring di kepala layar. Dihitung dari himpunan yang
                // SAMA dengan daftarnya, jadi angkanya mustahil berselisih.
                'ringkasan' => [
                    'berjalan' => $dokumen->whereIn('status', self::BERJALAN)->count(),
                    'berlaku' => $dokumen->where('status', 'published')->count(),
                    'sedang_direvisi' => $dokumen->where('status', 'sedang_direvisi')->count(),
                    'ditolak' => $dokumen->where('status', 'rejected')->count(),
                    'tidak_berlaku' => $dokumen->where('status', 'obsolete')->count(),
                ],
            ],
        ]);
    }

    // ───────────────────────────── Distribusi ────────────────────────────────

    public function distribusi(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $this->lingkupDistribusi(Document::with('type', 'department', 'creator')->berlaku(), $user);
        abort_if($query === null, 403, 'Anda tidak berwenang membuka halaman ini.');

        // `?sumber=informasi` (Fase D) — BENTUK JSON-nya sama persis, jadi
        // aplikasi terpasang tak perlu tahu apa-apa sampai ia memintanya.
        if ($request->query('sumber') === 'informasi') {
            return $this->distribusiInformasi($request, $user);
        }

        $dokumen = $query
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('doc_number_final', 'like', "%{$request->q}%")
                ->orWhere('title', 'like', "%{$request->q}%")))
            ->latest('published_at')
            ->get();

        $cakupan = $this->distribusi->cakupan($dokumen);

        // Cakupan TERENDAH di atas — layar ini ada untuk menemukan dokumen yang
        // belum sampai, bukan untuk memamerkan yang sudah.
        $dokumen = $dokumen->sortBy(fn (Document $d) => $cakupan[$d->id]['persen'])->values();

        return response()->json([
            'data' => $dokumen->map(fn (Document $d) => array_merge([
                'id' => $d->id,
                'no_dokumen' => $d->displayNumber(),
                'judul' => $d->title,
                'jenis' => $d->type?->code,
                'departemen' => $d->department?->code,
                'berlaku_sejak' => $d->published_at?->toIso8601String(),
            ], $cakupan[$d->id]))->all(),
            'meta' => [
                'total' => $dokumen->count(),
                'lingkup' => $this->labelLingkup($user),
                'terkunci_dept' => ! $user->can('document.view_all'),
                'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
            ],
        ]);
    }

    /**
     * Distribusi menu INFORMASI dari HP (Fase D).
     *
     * Yang disaring bukan informasinya melainkan ORANG yang dihitung sebagai
     * sasaran: kebijakan & poster memang berlaku di tujuh departemen, jadi
     * "informasi departemen saya" bukan pertanyaan yang ada jawabannya.
     */
    private function distribusiInformasi(Request $request, User $user): JsonResponse
    {
        $deptId = $user->can('document.view_all') ? null : $user->department_id;

        $informasi = Informasi::berlaku()
            ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->query('kategori')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nomor', 'like', "%{$request->q}%")
                ->orWhere('judul', 'like', "%{$request->q}%")))
            ->get();

        $cakupan = app(InformasiDistribution::class)->cakupan($informasi, $deptId);
        $informasi = $informasi->sortBy(fn (Informasi $i) => $cakupan[$i->id]['persen'])->values();

        return response()->json([
            'data' => $informasi->map(fn (Informasi $i) => array_merge([
                'id' => $i->id,
                'no_dokumen' => $i->nomor,
                'judul' => $i->judul,
                // Kategori menempati kolom `jenis`, dan `departemen` selalu null:
                // bentuk JSON-nya sengaja SAMA dengan sumber dokumen mutu supaya
                // layar mobile-nya satu, bukan dua.
                'jenis' => $i->kategori,
                'departemen' => null,
                'berlaku_sejak' => ($i->tanggal_efektif ?? $i->created_at)?->toIso8601String(),
            ], $cakupan[$i->id]))->all(),
            'meta' => [
                'total' => $informasi->count(),
                'lingkup' => $deptId === null ? '7 departemen' : 'Departemen '.($user->department?->code ?? '—'),
                'terkunci_dept' => $deptId !== null,
                'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
            ],
        ]);
    }

    public function distribusiShow(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        abort_if($this->lingkupDistribusi(Document::query(), $user) === null, 403);
        $this->pastikanTerlihat($document, $user);

        $rincian = $this->distribusi->rincian($document);

        return response()->json(['data' => [
            'id' => $document->id,
            'no_dokumen' => $document->displayNumber(),
            'judul' => $document->title,
            'status' => $document->status,
            'status_label' => $document->statusLabel(),
            'cakupan' => $this->distribusi->cakupanSatu($document),
            'sudah' => $rincian['sudah']->map(fn ($r) => [
                'nama' => $r->user?->name,
                'jabatan' => User::JABATAN_LABELS[$r->user?->jabatan] ?? '',
                'departemen' => $r->user?->department?->code,
                'foto' => $r->user?->photo_path ? url($r->user->photoUrl()) : null,
                'waktu' => $r->last_read_at?->toIso8601String(),
                'platform' => $r->platform,
                'unduh' => (int) $r->download_count > 0,
            ])->all(),
            'belum' => $rincian['belum']->map(fn (User $u) => [
                'nama' => $u->name,
                'jabatan' => User::JABATAN_LABELS[$u->jabatan] ?? '',
                'departemen' => $u->department?->code,
                'foto' => $u->photo_path ? url($u->photoUrl()) : null,
            ])->all(),
        ]]);
    }

    // ─────────────────────────── Daftar Induk ────────────────────────────────

    /**
     * Matriks Edisi × Revisi per jenis — bentuk JSON dari lembar ekspor.
     *
     * Perhitungannya TIDAK ditulis ulang: ia memanggil penyusun matriks yang
     * sama dengan yang mencetak Excel, jadi angka di HP dan angka di berkas
     * mustahil berbeda.
     */
    public function daftarInduk(Request $request, string $type): JsonResponse
    {
        $user = $request->user();
        $lintasDept = $user->can('document.publish');
        abort_unless($lintasDept || $user->can('document.review'), 403, 'Anda tidak berwenang membuka halaman ini.');

        $jenis = strtoupper($type);
        $tipe = DocumentType::where('code', $jenis)->first();
        abort_if($tipe === null, 404, 'Jenis dokumen tidak dikenal.');

        $rantai = Document::where('document_type_id', $tipe->id)
            ->unless($lintasDept, fn ($q) => $q->where('department_id', $user->department_id))
            ->get()->keyBy('id');

        $dokumen = $rantai->whereIn('status', ['published', 'sedang_direvisi'])
            ->sortBy(fn (Document $d) => $d->displayNumber())
            ->values();

        [$baris, $kolom] = DocumentExportController::matriks($dokumen, $rantai);

        return response()->json([
            'data' => collect($baris)->values()->map(fn (array $b, int $i) => [
                'no' => $i + 1,
                'id' => $b['dok']->id,
                'no_dokumen' => $b['dok']->displayNumber(),
                'judul' => $b['dok']->title,
                'edisi' => (int) ($b['dok']->edisi ?: 1),
                'no_revisi' => (int) $b['dok']->no_revisi,
                'efektif' => $b['efektif']?->toDateString(),
                'terakhir_revisi' => $b['dok']->published_at?->toDateString(),
                // peta[edisi][revisi] = tanggal ISO, atau null bila tak ada.
                'peta' => collect($b['peta'])->map(
                    fn (array $rev) => collect($rev)->map(fn ($t) => $t?->toDateString())->all()
                )->all(),
            ])->all(),
            'meta' => [
                'jenis' => $jenis,
                'kolom' => $kolom,
                'total' => count($baris),
                'lingkup' => $lintasDept ? '7 departemen' : 'Departemen '.($user->department?->code ?? '—'),
                'nama_berkas' => 'DAFTAR INDUK DOKUMEN '.$jenis.' PPA SITE '.Pengaturan::namaSite(),
            ],
        ]);
    }

    // ───────────────────────────── Pembantu ──────────────────────────────────

    /** Peta aksi audit → [label yang dibaca orang, rona]. Sama dengan web. */
    private const AKSI_LABEL = [
        'document.create' => ['Dokumen dibuat', 'accent'],
        'document.submit' => ['Dikirim untuk ditinjau', 'info'],
        'document.withdraw' => ['Ditarik kembali ke draft', 'neutral'],
        'document.review_start' => ['Mulai ditinjau', 'info'],
        'document.review_approve' => ['Diloloskan peninjau', 'success'],
        'document.review_reject' => ['Dikembalikan untuk revisi', 'warning'],
        'document.approve' => ['Disetujui — Berlaku', 'success'],
        'document.approval_reject' => ['Ditolak approver', 'danger'],
        'document.cancel_revision' => ['Penolakan dibatalkan (ditinjau ulang)', 'neutral'],
        'document.arsip_upload' => ['Dokumen lama didaftarkan — Berlaku', 'success'],
        'document.arsip_edit' => ['Dokumen lama diperbaiki', 'neutral'],
        'document.request_revision' => ['Revisi diajukan', 'warning'],
        'document.cancel_revision_b' => ['Revisi dibatalkan', 'neutral'],
        'attachment.comment' => ['Komentar pada lampiran', 'info'],
        'feedback.create' => ['Masukan lapangan masuk', 'accent'],
        'feedback.respond' => ['Masukan dibalas & ditutup', 'neutral'],
        'masukan_sejawat.kirim' => ['Masukan sejawat masuk', 'accent'],
    ];

    /**
     * Penyaring lingkup Perjalanan Dokumen. `null` = peran ini tak punya layar
     * itu sama sekali (Non-Staff).
     */
    private function lingkupPerjalanan(Builder $query, User $user): ?Builder
    {
        if ($user->can('document.view_all')) {
            return $query;   // PJO, Admin, MD — tujuh departemen.
        }
        if (in_array($user->jabatan, [User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD], true)) {
            return $query->where('department_id', $user->department_id);
        }
        if ($user->jabatan === User::JABATAN_GROUP_LEADER) {
            return $query->where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('authors', fn ($a) => $a->where('user_id', $user->id)));
        }

        return null;
    }

    /**
     * Penyaring lingkup Distribusi. Sama dengan web untuk SH/DH/PJO, DITAMBAH
     * cabang GL "dokumen yang saya susun" — permintaan pemilik, dan tanpa itu
     * GL tak punya cara melihat apakah dokumennya sendiri sampai ke orangnya.
     */
    private function lingkupDistribusi(Builder $query, User $user): ?Builder
    {
        if ($user->can('document.view_all')) {
            return $query;
        }
        if (in_array($user->jabatan, [User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD], true)) {
            return $query->where('department_id', $user->department_id);
        }
        if ($user->jabatan === User::JABATAN_GROUP_LEADER) {
            return $query->where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('authors', fn ($a) => $a->where('user_id', $user->id)));
        }

        return null;
    }

    /** Dokumen ini benar-benar berada di dalam lingkup orang ini? */
    private function pastikanTerlihat(Document $document, User $user): void
    {
        $lingkup = $this->lingkupPerjalanan(Document::query(), $user);
        abort_if($lingkup === null, 403, 'Anda tidak berwenang membuka halaman ini.');
        abort_unless($lingkup->whereKey($document->id)->exists(), 403, 'Dokumen ini di luar lingkup Anda.');
    }

    private function labelLingkup(User $user): string
    {
        return match (true) {
            $user->can('document.view_all') => '7 departemen',
            $user->jabatan === User::JABATAN_GROUP_LEADER => 'Dokumen yang Anda susun',
            default => 'Departemen '.($user->department?->code ?? '—'),
        };
    }

    private function orang(?User $u): ?array
    {
        return $u === null ? null : [
            'nama' => $u->name,
            'jabatan' => User::JABATAN_SHORT[$u->jabatan] ?? '',
            'departemen' => $u->department?->code,
            'foto' => $u->photo_path ? url($u->photoUrl()) : null,
        ];
    }

    /**
     * Satu baris Perjalanan Dokumen — kalimat "menunggu", tahap, dan umurnya.
     *
     * Kalimat & tahapnya disalin apa adanya dari `menungguDiMeja()`: aplikasi
     * tak boleh mengarang varian kalimatnya sendiri.
     */
    private function barisPerjalanan(Document $d): array
    {
        $singkat = fn (?User $u) => $u ? (User::JABATAN_SHORT[$u->jabatan] ?? '') : '';

        // Tahap yang dilalui jenis dokumen ini. "MD" hanya digambar bila jenis
        // itu memang melewati Verifikasi MD (saat ini SOP).
        $tahapan = $d->perluTinjauanMd()
            ? ['Dibuat', 'Ditinjau', 'MD', 'Disetujui']
            : ['Dibuat', 'Ditinjau', 'Disetujui'];

        $tahap = match ($d->status) {
            'waiting_for_review', 'in_review' => 'Ditinjau',
            'verifikasi_md' => 'MD',
            'rejected', 'draft' => 'Dibuat',
            default => 'Disetujui',
        };

        return [
            'id' => $d->id,
            'no_dokumen' => $d->displayNumber(),
            'nomor_sementara' => ! $d->hasFinalNumber(),
            'judul' => $d->title,
            'jenis' => $d->type?->code,
            'departemen' => $d->department?->code,
            'status' => $d->status,
            'status_label' => $d->statusLabel(),
            'pembuat' => $d->creator?->name,
            'jabatan_pembuat' => trim($singkat($d->creator).' '.($d->creator?->department?->code ?? '')),
            'foto_pembuat' => $d->creator?->photo_path ? url($d->creator->photoUrl()) : null,
            'pemegang' => match ($d->status) {
                'waiting_for_review', 'in_review' => $d->reviewer?->name,
                'pending_approval' => $d->approver?->name,
                'rejected' => $d->creator?->name,
                default => null,
            },
            'menunggu' => trim(match ($d->status) {
                'waiting_for_review', 'in_review' => 'Menunggu tinjauan '.$singkat($d->reviewer),
                'verifikasi_md' => 'Menunggu tinjauan MD',
                'pending_approval' => 'Menunggu persetujuan '.$singkat($d->approver),
                'rejected' => 'Menunggu revisi '.$singkat($d->creator),
                'published', 'sedang_direvisi' => 'Berlaku',
                default => 'Menunggu tindak lanjut',
            }),
            'tahapan' => $tahapan,
            'tahap' => $tahap,
            'umur_hari' => (int) ($d->submitted_at ?? $d->updated_at)?->diffInDays(now()),
        ];
    }
}
