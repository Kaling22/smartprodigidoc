<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * "Status Dokumen Staff" — pantauan READ-ONLY (PRD v3.1 §3.3).
 *
 * GL/SH/DH memantau dokumen di departemennya sendiri; PJO & Admin lintas 7
 * departemen. Tanpa hak sunting/kirim/hapus — murni melihat.
 */
class DocumentStaffStatusController extends Controller
{
    /**
     * "Status Dokumen Staff" — read-only (v3.1 §3.3). GL & Section Head melihat
     * status dokumen STAFF di departemennya. Tanpa hak edit/kirim/hapus.
     */
    public function staffStatus(Request $request)
    {
        $user = $request->user();
        $canAll = $user->can('document.view_all');
        abort_unless(
            $canAll || in_array($user->jabatan, [User::JABATAN_GROUP_LEADER, User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD], true),
            403
        );

        // PJO/Admin: pilih departemen (submenu 7 dept, v2 2e); GL/SH/DH: dept sendiri (2d).
        $deptId = $canAll ? ($request->input('department_id') ?: null) : $user->department_id;

        $query = Document::with('type', 'department', 'creator')
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId))
            // "Dokumen Departemen" = pekerjaan ORANG LAIN di departemen ini
            // (PLAN-AKSES-v8 Fase 1). Dokumen sendiri sudah punya menunya
            // sendiri (Dokumen Saya) — satu dokumen yang muncul di dua daftar
            // membuat keduanya tak bisa dipercaya sebagai hitungan.
            //
            // Sengaja TIDAK berlaku bagi pemegang `document.view_all`: bagi
            // PJO/Admin layar ini adalah pantauan 7 departemen, dan Admin
            // sendirilah yang mendaftarkan dokumen arsip (created_by = Admin,
            // DocumentArsipController:60). Menyembunyikan dokumen buatannya
            // sendiri dari pantauan lintas-dept itu bukan merapikan irisan,
            // melainkan melubangi daftarnya.
            ->when(! $canAll, fn ($q) => $q->where('created_by', '!=', $user->id))
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->latest();

        /*
        | Judul halaman ditentukan SERVER, bukan dirakit ulang di TSX. GL
        | (pembuat, tanpa wewenang tinjau/lihat-semua) memakai daftar ini sebagai
        | "Dokumen Departemen"; SH/DH/PJO sebagai "Status Dokumen Staff" — dan
        | ejaan "Staff" itu dijaga LabelJabatanTest, jadi ia tak boleh punya
        | salinan kedua (CLAUDE.md §6).
        */
        $isGlDept = $user->can('document.create') && ! $user->can('document.review') && ! $canAll;

        return Inertia::render('Documents/StaffStatus', [
            'documents' => $query->urut($request->sort, $request->dir)->paginate(15)->withQueryString()
                ->through(fn (Document $doc) => $doc->barisDaftar($user) + [
                    // Masukan sejawat (PLAN-AKSES-v8 Fase 2) — GL menyumbang
                    // catatan atas dokumen rekan sedepartemennya yang belum
                    // Berlaku. Syaratnya dijawab MODEL, aturan yang sama dengan
                    // penjaga di MasukanSejawatController: tombol yang tampil
                    // lalu berakhir 403 adalah gejala aturan yang disalin.
                    'boleh_masukan_sejawat' => $doc->bisaDiberiMasukanSejawatOleh($user),
                ]),
            'filters' => $request->only('q', 'status', 'type', 'department_id'),
            'types' => \App\Models\DocumentType::orderBy('code')->pluck('code'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            'canAll' => $canAll,
            'selectedDept' => $deptId ? Department::find($deptId)?->only(['id', 'code', 'name']) : null,
            'isGlDept' => $isGlDept,
            'judul' => $isGlDept ? 'Dokumen Departemen' : 'Status Dokumen Staff',
            // Status mana yang boleh disaring; labelnya tetap dari props global
            // `statusLabels` (pakem P3).
            'statusOpsi' => ['draft', 'waiting_for_review', 'in_review', 'rejected',
                'pending_approval', 'published', 'sedang_direvisi', 'obsolete'],
            'prefix' => \App\Models\Pengaturan::prefix(),
        ]);
    }

}
