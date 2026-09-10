<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Persetujuan pendaftaran akun (roadmap Task 1.5).
 *
 * Admin IT dan PJO melihat pendaftar dari SELURUH departemen; GL/SH/DH hanya
 * dari departemennya sendiri.
 */
class UserApprovalController extends Controller
{
    // Authorization (can:user.approve_registration) is applied at the route level.
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        return Inertia::render('Users/Pending', [
            // Lintas-departemen: Admin IT & PJO. Sisanya dibatasi dept sendiri.
            'pendingUsers' => User::with('department', 'roles')
                ->pendingVisibleTo($request->user())
                ->latest()
                ->get()
                // `jabatan_diajukan` = jabatan yang DIKETIK pendaftar (mis.
                // "Magang"); `jabatan_label` = hak akses yang akan ia peroleh.
                // Penyetuju perlu melihat KEDUANYA — lihat users/pending lama.
                ->map(fn (User $u) => $u->barisAdmin())
                ->values(),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->authorizeDepartment($request, $user);

        $user->update(['status' => 'active']);
        $this->audit->log('user.approve_registration', null, ['approved_user_id' => $user->id]);

        return back()->with('status', "Akun {$user->name} berhasil disetujui.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorizeDepartment($request, $user);

        $user->update(['status' => 'rejected']);
        $this->audit->log('user.reject_registration', null, ['rejected_user_id' => $user->id]);

        return back()->with('status', "Akun {$user->name} ditolak.");
    }

    /** Selain Admin IT & PJO, hanya boleh menindak pendaftar di departemennya. */
    private function authorizeDepartment(Request $request, User $user): void
    {
        $actor = $request->user();

        abort_if(
            ! $actor->approvesAllDepartments() && $actor->department_id !== $user->department_id,
            403,
            'Anda hanya dapat mengelola user di departemen Anda.',
        );
    }
}
