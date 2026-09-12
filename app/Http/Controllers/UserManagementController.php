<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/**
 * Admin IT user management (roadmap Task 1.6). Admin lists all users and
 * creates staff accounts (GL / Section Head / Pimpinan / User / Admin) with a
 * role and department. All actions are audited (D11).
 *
 * Route-level authorization: can:user.manage.
 */
class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        $query = User::with('department', 'roles');

        if ($search = $request->input('q')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('nrp', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($dept = $request->input('department_id')) {
            $query->where('department_id', $dept);
        }

        return Inertia::render('Users/Index', [
            // `through()`, BUKAN `map()`: ia memetakan isi halaman tanpa
            // membongkar bentuk paginator, jadi `data`/`links`/`from`/`to`/
            // `total` yang dibaca komponen Paginasi tetap di tempatnya.
            'users' => $query->latest()->paginate(15)->withQueryString()
                ->through(fn (User $u) => $u->barisAdmin()),
            'departments' => Department::orderBy('code')->get(),
            'filters' => $request->only('q', 'department_id'),
            // Akun Management Development ditampilkan pada bagian TERSENDIRI.
            // Sifatnya beda dari akun lain: BERSAMA (dipakai siapa pun di
            // departemen itu), tak dipilih lewat dropdown mana pun, dan punya
            // saklar khusus. Menyelipkannya di tabel biasa membuat sifat itu
            // tak terlihat, padahal justru itu yang perlu diketahui pengelola.
            'akunMd' => User::peninjauMd()->with('department', 'roles')->orderBy('nrp')->get()
                ->map(fn (User $u) => $u->barisAdmin())->values(),
            // Peta peran → label, dari User::ROLE_LABELS. Dikirim sebagai peta
            // UTUH, bukan ditempelkan per baris: begitu halaman 2 kebetulan tak
            // memuat seorang Non-Staff pun, label itu lenyap dari payload — dan
            // penjaga ejaannya (LabelJabatanTest) jadi hijau tanpa memeriksa apa
            // pun. Peta selalu lengkap, siapa pun yang kebetulan tampil.
            'roleLabels' => User::ROLE_LABELS,
        ]);
    }

    /**
     * Konfigurasi akun Management Development.
     *
     * Satu pintu untuk tiga aksi supaya semuanya tercatat audit dengan pola yang
     * sama — dan supaya menambah saklar baru kelak tak menuntut rute baru.
     */
    public function updateMd(Request $request, User $user): RedirectResponse
    {
        abort_unless(
            $user->hasRole(\Database\Seeders\RolePermissionSeeder::ROLE_MD),
            403,
            'Akun ini bukan akun Management Development.'
        );

        $data = $request->validate([
            'aksi' => ['required', 'in:ai,status,reset_sandi'],
            'sandi_baru' => ['nullable', 'required_if:aksi,reset_sandi', 'string', 'min:8'],
        ], [
            'sandi_baru.required_if' => 'Kata sandi baru wajib diisi.',
            'sandi_baru.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        return match ($data['aksi']) {
            'ai' => $this->saklarAi($user),
            'status' => $this->saklarStatus($user),
            'reset_sandi' => $this->resetSandi($user, $data['sandi_baru']),
        };
    }

    private function saklarAi(User $user): RedirectResponse
    {
        $user->update(['ai_review_enabled' => ! $user->ai_review_enabled]);
        $this->audit->log('user.md_ai_toggle', null, [
            'user' => $user->nrp,
            'ai_aktif' => (bool) $user->ai_review_enabled,
        ]);

        return back()->with('status', $user->ai_review_enabled
            ? 'Bantuan AI untuk peninjauan MD DIAKTIFKAN.'
            : 'Bantuan AI untuk peninjauan MD DIMATIKAN. Peninjauan tetap berjalan manual.');
    }

    private function saklarStatus(User $user): RedirectResponse
    {
        // Enum `users.status` hanya mengenal pending/active/rejected — `rejected`
        // dipakai sebagai "nonaktif", mengikuti konvensi toggleStatus() di atas.
        $user->update(['status' => $user->isActive() ? 'rejected' : 'active']);
        $this->audit->log('user.md_status_toggle', null, [
            'user' => $user->nrp,
            'status' => $user->status,
        ]);

        // Akun MD dinonaktifkan = SOP berhenti di tahap MD. Pengelola harus tahu
        // konsekuensinya saat itu juga, bukan setelah dokumen menumpuk.
        return back()->with('status', $user->isActive()
            ? 'Akun MD diaktifkan kembali.'
            : 'Akun MD dinonaktifkan. Dokumen SOP akan TERTAHAN di tahap MD sampai diaktifkan lagi.');
    }

    private function resetSandi(User $user, string $sandi): RedirectResponse
    {
        $user->update(['password' => Hash::make($sandi)]);
        $this->audit->log('user.md_reset_password', null, ['user' => $user->nrp]);

        // Sandi TIDAK pernah ikut dicatat audit maupun ditampilkan ulang.
        return back()->with('status', 'Kata sandi akun MD berhasil diganti. Sampaikan ke pemakainya lewat kanal aman.');
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'departments' => Department::orderBy('code')->get(),
            'roles' => StoreUserRequest::assignableRoles(),
            'roleLabels' => User::ROLE_DESKRIPSI,
        ]);
    }

    /**
     * Jabatan & departemen yang MENGIKUTI peran terpilih.
     *
     * Dipakai bersama store() dan update() dengan sengaja: `users.jabatan` dan
     * peran spatie adalah DUA kolom kebenaran yang harus selalu sejalan, dan
     * tak ada apa pun di database yang menegakkannya. Kalau aturannya disalin,
     * cukup satu cabang terlewat untuk melahirkan user yang jabatannya bilang
     * GL tapi perannya masih staff — menunya muncul, aksinya 403, dan gejalanya
     * menyesatkan.
     *
     * @return array{0: ?string, 1: ?int} [jabatan, department_id]
     */
    private function turunanPeran(string $role, ?int $departmentId): array
    {
        $seeder = \Database\Seeders\RolePermissionSeeder::class;

        // Admin IT & Management Development bukan jabatan alur dokumen — nilainya
        // NULL, persis seperti yang ditanam AdminUserSeeder. Tanpa cabang MD di
        // sini, menambah MD ke daftar peran akan menulis jabatan
        // "management_development" yang tak dikenal JABATAN_LABELS, dan namanya
        // muncul kosong di seluruh layar.
        $jabatan = in_array($role, [$seeder::ROLE_ADMIN, $seeder::ROLE_MD], true) ? null : $role;

        // Pimpinan tidak terikat departemen (v3.1 §3.1) — justru karena ia
        // melintasi ketujuhnya.
        $department = $role === $seeder::ROLE_PIMPINAN ? null : $departmentId;

        return [$jabatan, $department];
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        [$jabatan, $departmentId] = $this->turunanPeran($request->role, $request->department_id);

        $user = User::create([
            'name' => $request->name,
            'nrp' => $request->nrp,
            'jabatan' => $jabatan,
            'jabatan_diajukan' => $request->jabatan_diajukan,
            'nomor_hp' => $request->nomor_hp,
            'email' => $request->email,
            'department_id' => $departmentId,
            'password' => Hash::make($request->password),
            'status' => 'active', // admin-created staff accounts are active immediately
        ]);

        $user->assignRole($request->role);

        $this->audit->log('user.create_staff', null, [
            'created_user_id' => $user->id,
            'role' => $request->role,
            'department_id' => $user->department_id,
        ]);

        return redirect()->route('users.index')
            ->with('status', "Akun {$user->name} ({$request->role}) berhasil dibuat.");
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'user' => $user->only(['id', 'name', 'nrp', 'department_id', 'jabatan_diajukan']),
            'departments' => Department::orderBy('code')->get(),
            'roles' => StoreUserRequest::assignableRoles(),
            'roleLabels' => User::ROLE_DESKRIPSI,
            'peranSekarang' => $user->getRoleNames()->first(),
        ]);
    }

    /**
     * Naikkan/turunkan peran akun.
     *
     * KENAPA ADA: pendaftaran mandiri SELALU melahirkan Non-Staff, jadi seorang
     * GL yang terlanjur mendaftar lewat halaman login dulu terkunci selamanya —
     * NRP unik, sehingga satu-satunya jalan adalah membuat akun kedua dan
     * meninggalkan yang lama sebagai sampah.
     */
    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $peranLama = $user->getRoleNames()->first();

        // Larangan mengubah peran sendiri ada di UpdateUserRoleRequest::authorize()
        // supaya menyala sebelum validasi, bukan sesudahnya.
        $this->tolakBilaAdminTerakhir($user, 'Ini satu-satunya Admin IT yang aktif — perannya tidak dapat diturunkan.');

        [$jabatan, $departmentId] = $this->turunanPeran($request->role, $request->department_id);

        $user->update([
            'jabatan' => $jabatan,
            'department_id' => $departmentId,
            'jabatan_diajukan' => $request->jabatan_diajukan,
        ]);
        // syncRoles, BUKAN assignRole: peran lama harus dilepas, kalau tidak
        // user menumpuk peran dan izinnya jadi gabungan keduanya.
        $user->syncRoles([$request->role]);

        $this->audit->log('user.update_role', null, [
            'user_id' => $user->id,
            'role_lama' => $peranLama,
            'role_baru' => $request->role,
            'department_id' => $departmentId,
        ]);

        return redirect()->route('users.index')
            ->with('status', "Peran {$user->name} diubah dari {$peranLama} menjadi {$request->role}.");
    }

    /**
     * Hapus akun (soft delete — trait SoftDeletes sudah terpasang di User).
     *
     * Penjaga ketiga yang paling penting dan paling tak kelihatan: `created_by`,
     * `reviewer_id`, dan `approver_id` pada `documents` hanyalah INDEX tanpa
     * foreign key, jadi database diam saja saat pemiliknya lenyap. Tetapi relasi
     * `Document::creator()` ikut terkena global scope SoftDeletes dan
     * mengembalikan NULL, sehingga halaman pengesahan & daftar dokumen pecah
     * saat memanggil `->name`. Untuk kasus itu arahkan admin ke Nonaktifkan.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak dapat menghapus akun sendiri.');
        $this->tolakBilaAdminTerakhir($user, 'Ini satu-satunya Admin IT yang aktif — akunnya tidak dapat dihapus.');

        if ($jejak = $this->jejakDokumen($user)) {
            return back()->with('error',
                "{$user->name} masih tertaut {$jejak} dan tidak dapat dihapus. Gunakan Nonaktifkan agar riwayat dokumennya tetap utuh.");
        }

        $nama = $user->name;
        $user->delete();

        $this->audit->log('user.delete', null, ['user_id' => $user->id, 'nrp' => $user->nrp]);

        return redirect()->route('users.index')->with('status', "Akun {$nama} dihapus.");
    }

    /** Admin IT aktif terakhir tak boleh dilucuti — kalau lolos, tak ada yang bisa masuk lagi. */
    private function tolakBilaAdminTerakhir(User $user, string $pesan): void
    {
        $admin = \Database\Seeders\RolePermissionSeeder::ROLE_ADMIN;

        if (! $user->hasRole($admin)) {
            return;
        }

        $sisa = User::role($admin)->where('status', 'active')->where('id', '!=', $user->id)->count();

        abort_if($sisa === 0, 403, $pesan);
    }

    /** Keterangan singkat keterikatan user pada dokumen, atau null bila bersih. */
    private function jejakDokumen(User $user): ?string
    {
        $sebagai = [
            'pembuat' => \App\Models\Document::withTrashed()->where('created_by', $user->id)->count(),
            'peninjau' => \App\Models\Document::withTrashed()->where('reviewer_id', $user->id)->count(),
            'penyetuju' => \App\Models\Document::withTrashed()->where('approver_id', $user->id)->count(),
            'pembuat tambahan' => \App\Models\DocumentAuthor::where('user_id', $user->id)->count(),
        ];

        $ada = array_filter($sebagai);

        return $ada === [] ? null : implode(', ', array_map(
            fn ($peran, $jml) => "{$jml} dokumen sebagai {$peran}",
            array_keys($ada), $ada,
        ));
    }

    /** Toggle active/rejected status (soft deactivation). */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Tidak dapat mengubah status akun sendiri.');

        $new = $user->status === 'active' ? 'rejected' : 'active';
        $user->update(['status' => $new]);

        $this->audit->log('user.toggle_status', null, ['user_id' => $user->id, 'status' => $new]);

        return back()->with('status', "Status {$user->name} diubah menjadi {$new}.");
    }
}
