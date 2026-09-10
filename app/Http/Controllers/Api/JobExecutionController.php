<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JobExecutionResource;
use App\Models\Document;
use App\Models\JobChecklistItem;
use App\Models\JobExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pelaksanaan pekerjaan menggunakan formulir JSA untuk aplikasi mobile.
 *
 * Alur:
 *   1. User pilih JSA berlaku  → GET /api/documents?type=JSA
 *   2. User mulai pekerjaan    → POST /api/pekerjaan
 *   3. User centang per item   → PATCH /api/pekerjaan/{id}/checklist
 *   4. User tandai selesai     → PATCH /api/pekerjaan/{id}/selesai
 *   5. Riwayat                 → GET /api/pekerjaan
 */
class JobExecutionController extends Controller
{
    /**
     * Riwayat pekerjaan — lingkupnya menurut PERAN (PLAN-MOBILE-v6 §2.3):
     * Non-Staff miliknya sendiri, GL & SH/DH seluruh pelaksana di
     * departemennya, pemegang `document.view_all` semuanya.
     *
     * Departemennya diambil dari PELAKSANANYA, bukan dari dokumen JSA-nya:
     * seorang Non-Staff PLANT yang bekerja mengacu JSA milik SHE tetap
     * pekerjaan PLANT, dan atasannyalah yang perlu melihatnya.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['berlangsung', 'selesai', 'dibatalkan'])],
        ]);

        $query = JobExecution::with(['document.department', 'user'])
            ->tap(fn ($q) => $this->lingkup($q, $request))
            ->latest();

        if ($status = ($data['status'] ?? null)) {
            $query->where('status', $status);
        }

        $list = $query->paginate(20);

        return response()->json([
            'data' => JobExecutionResource::collection($list)->resolve(),
            'meta' => [
                'current_page' => $list->currentPage(),
                'last_page'    => $list->lastPage(),
                'total'        => $list->total(),
            ],
        ]);
    }

    /**
     * Penyaring lingkup daftar — SATU aturan, dipakai `index()` dan `show()`.
     *
     * Ditulis sekali karena keduanya harus sepakat: daftar yang menampilkan
     * kartu bawahan lalu 403 saat kartunya diketuk adalah menu yang berbohong.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<JobExecution>  $query
     */
    private function lingkup($query, Request $request)
    {
        $user = $request->user();

        return match ($user->lingkupTim()) {
            'sendiri' => $query->where('user_id', $user->id),
            'departemen' => $query->whereHas('user',
                fn ($u) => $u->where('department_id', $user->department_id)),
            default => $query,
        };
    }

    /** Detail satu pekerjaan beserta status centang setiap tindakan pengendalian. */
    public function show(JobExecution $jobExecution, Request $request): JsonResponse
    {
        // Selebar `index()`, tidak lebih: atasan MELIHAT pekerjaan bawahannya.
        // Dulu `user_id === me` saja, yang memblokir atasan tepat saat ia
        // mengetuk kartu yang baru saja ditampilkan kepadanya.
        abort_unless(
            $this->lingkup(JobExecution::whereKey($jobExecution->id), $request)->exists(),
            403,
        );

        $jobExecution->load(['document.department', 'user', 'checklistItems']);

        return response()->json([
            'data' => (new JobExecutionResource($jobExecution, true))->resolve(),
        ]);
    }

    /**
     * Mulai pekerjaan baru mengacu pada JSA yang sudah berlaku.
     *
     * Snapshot analisa disimpan saat ini agar riwayat tidak berubah
     * bila JSA kemudian direvisi.
     */
    public function store(Request $request): JsonResponse
    {
        // Pelaksana pekerjaan = Non-Staff & GL saja (PLAN-MOBILE-v6 §2.3).
        // SH/DH & PJO MELIHAT daftar pekerjaan departemennya lewat index(),
        // tapi tak pernah menjadi pelaksananya — sejalan dengan larangan
        // mencentang checklist orang lain di updateChecklist().
        abort_unless($request->user()->bisaKerjaJsa(), 403,
            'Pekerjaan JSA dilaksanakan Non-Staff dan Group Leader.');

        $data = $request->validate([
            'document_id'         => ['required', 'integer'],
            'nama_pekerjaan'      => ['required', 'string', 'max:255'],
            'lokasi'              => ['required', 'string', 'max:255'],
            'tanggal_pelaksanaan' => ['required', 'date'],
            'catatan'             => ['nullable', 'string', 'max:1000'],
        ]);

        // Hanya JSA berstatus published yang boleh dijadikan acuan.
        $document = Document::query()
            ->where('id', $data['document_id'])
            ->whereHas('type', fn ($q) => $q->where('code', 'JSA'))
            ->berlaku()
            ->first();

        abort_unless($document !== null, 422, 'JSA tidak ditemukan atau belum berlaku.');

        // Ambil analisa dari document_contents.
        $analisa = $document->contents
            ->where('section_key', 'analisa')
            ->first()
            ?->value_json ?? [];

        $job = JobExecution::create([
            'document_id'         => $document->id,
            'user_id'             => $request->user()->id,
            'nama_pekerjaan'      => $data['nama_pekerjaan'],
            'lokasi'              => $data['lokasi'],
            'tanggal_pelaksanaan' => $data['tanggal_pelaksanaan'],
            'analisa_snapshot'    => $analisa,
            'status'              => 'berlangsung',
            'catatan'             => $data['catatan'] ?? null,
        ]);

        $job->load(['document.department', 'user']);

        return response()->json([
            'message' => 'Pekerjaan berhasil dimulai.',
            'data'    => (new JobExecutionResource($job, true))->resolve(),
        ], 201);
    }

    /**
     * Update status centang satu atau banyak tindakan pengendalian sekaligus.
     *
     * Body: { "items": [ { "langkah_ke":0, "bahaya_ke":0, "pengendalian_ke":0, "checked":true, "catatan":"..." } ] }
     */
    public function updateChecklist(JobExecution $jobExecution, Request $request): JsonResponse
    {
        // SENGAJA tetap "milik sendiri", tidak ikut dilonggarkan bersama
        // index()/show() (PLAN-MOBILE-v6 §2.3 butir 3): atasan MELIHAT, bukan
        // mencentang. Mencentangkan pekerjaan orang lain merusak arti centang
        // itu sendiri — ia pernyataan bahwa pengendaliannya sudah dikerjakan.
        abort_unless($jobExecution->user_id === $request->user()->id, 403);
        abort_if($jobExecution->status !== 'berlangsung', 422, 'Pekerjaan sudah selesai atau dibatalkan.');

        $data = $request->validate([
            'items'                      => ['required', 'array', 'min:1', 'max:200'],
            'items.*.langkah_ke'         => ['required', 'integer', 'min:0'],
            'items.*.bahaya_ke'          => ['required', 'integer', 'min:0'],
            'items.*.pengendalian_ke'    => ['required', 'integer', 'min:0'],
            'items.*.checked'            => ['required', 'boolean'],
            'items.*.catatan'            => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($jobExecution, $data) {
            foreach ($data['items'] as $item) {
                $checkedAt = $item['checked'] ? now() : null;

                JobChecklistItem::updateOrCreate(
                    [
                        'job_execution_id' => $jobExecution->id,
                        'langkah_ke'       => $item['langkah_ke'],
                        'bahaya_ke'        => $item['bahaya_ke'],
                        'pengendalian_ke'  => $item['pengendalian_ke'],
                    ],
                    [
                        'checked'    => $item['checked'],
                        'checked_at' => $checkedAt,
                        'catatan'    => $item['catatan'] ?? null,
                    ]
                );
            }
        });

        $jobExecution->load(['document.department', 'user', 'checklistItems']);

        return response()->json([
            'message' => 'Checklist diperbarui.',
            'data'    => (new JobExecutionResource($jobExecution, true))->resolve(),
        ]);
    }

    /**
     * Tandai pekerjaan selesai atau batalkan.
     *
     * Body: { "status": "selesai" | "dibatalkan", "catatan": "..." }
     */
    public function updateStatus(JobExecution $jobExecution, Request $request): JsonResponse
    {
        // Sama seperti updateChecklist: menandai selesai adalah pernyataan
        // pelaksananya sendiri, bukan penilaian atasan.
        abort_unless($jobExecution->user_id === $request->user()->id, 403);
        abort_if($jobExecution->status !== 'berlangsung', 422, 'Status pekerjaan sudah final.');

        $data = $request->validate([
            'status'  => ['required', Rule::in(['selesai', 'dibatalkan'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $jobExecution->update([
            'status'  => $data['status'],
            'catatan' => $data['catatan'] ?? $jobExecution->catatan,
        ]);

        return response()->json([
            'message' => 'Status pekerjaan diperbarui.',
            'data'    => (new JobExecutionResource($jobExecution->fresh(['document.department', 'user'])))->resolve(),
        ]);
    }
}
