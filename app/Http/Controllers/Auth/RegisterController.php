<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function create()
    {
        return Inertia::render('Auth/Register', [
            // Departemen NONAKTIF tak ditawarkan (Fase 5b): pendaftar baru
            // tak boleh masuk ke departemen yang sudah ditutup. Yang sudah
            // terdaftar di sana tetap utuh — ini pintu masuk, bukan saringan.
            'departments' => Department::aktif()->orderBy('code')->get(),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // Pendaftaran mandiri SELALU menghasilkan akun Non-Staff berstatus
        // pending (PRD v2 §7.1) — peran alur yang sebenarnya ditetapkan admin
        // setelah akun aktif. Jabatan yang DIKETIK pendaftar (mis. "Magang")
        // disimpan terpisah di `jabatan_diajukan` sebagai informasi bagi
        // penyetuju; kolom `jabatan` tetap kunci alur, jangan ditimpa teks bebas.
        $user = User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'jabatan' => User::JABATAN_STAFF,
            'jabatan_diajukan' => $request->jabatan ?: null,
            'nomor_hp' => $request->nomor_hp,
            'department_id' => $request->department_id,
            'password' => Hash::make($request->password),
            'status' => 'pending',
        ]);

        $user->assignRole(RolePermissionSeeder::ROLE_STAFF);

        $this->audit->log('user.register', null, ['user_id' => $user->id, 'department_id' => $user->department_id]);

        // Log them in so they land on the "waiting for approval" page.
        Auth::login($user);

        return redirect()->route('pending');
    }
}
