<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccessProfileRequest;
use App\Models\AccessProfile;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Manajemen Akses — profil akses & penetapannya (PLAN-AKSES-v7 Fase 4b).
 *
 * Polanya konfigurasi Mikrotik: Admin membuat PROFIL sekali, lalu MENETAPKAN
 * profil itu ke orang. Wewenangnya sendiri ditegakkan di tempat lain —
 * User::bolehBuatJenis() & User::canReviewJsa() — controller ini hanya
 * mengelola datanya. Kalau ia ikut memutuskan wewenang, akan ada dua sumber
 * kebenaran yang suatu hari menyimpang.
 *
 * Otorisasi tingkat rute: `can:user.manage`. SELURUH aksi tercatat audit
 * (CLAUDE.md §6 — tindakan Admin wajib ter-audit).
 */
class AccessProfileController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        // Kandidat penetapan = GROUP LEADER saja. SH/DH/PJO/Non-Staff tak
        // menyusun dokumen (PLAN-AKSES-v7 §11), dan Admin lolos tanpa
        // profil — menampilkan mereka hanya menyodorkan kotak yang tak
        // pernah berpengaruh.
        $gl = User::with(['department', 'accessProfile'])
            ->whereHas('roles', fn ($q) => $q->where('name', Roles::ROLE_GROUP_LEADER));

        if ($cari = $request->input('q')) {
            $gl->where(fn ($q) => $q
                ->where('name', 'like', "%{$cari}%")
                ->orWhere('nrp', 'like', "%{$cari}%"));
        }

        if ($dept = $request->input('department_id')) {
            $gl->where('department_id', $dept);
        }

        // 'tanpa' bukan id profil melainkan pertanyaan "siapa yang belum
        // ditetapkan" — justru daftar itu yang dicari Admin saat memeriksa.
        if ($profil = $request->input('access_profile_id')) {
            $profil === 'tanpa'
                ? $gl->whereNull('access_profile_id')
                : $gl->where('access_profile_id', $profil);
        }

        return Inertia::render('Akses/Index', [
            // Profil TIDAK dipaginasi: daftarnya jadi isi dropdown penetapan
            // dan modal Ubah di halaman yang sama — memotongnya per halaman
            // akan menghilangkan pilihan yang sah.
            'profiles' => AccessProfile::withCount('users')->orderBy('nama')->get()
                ->map(fn (AccessProfile $p) => [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'keterangan' => $p->keterangan,
                    'jenis_dibolehkan' => $p->jenis_dibolehkan ?? [],
                    'boleh_review_jsa' => (bool) $p->boleh_review_jsa,
                    'users_count' => $p->users_count,
                ])->values(),
            'groupLeaders' => $gl->orderBy('name')->paginate(15)->withQueryString()
                ->through(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'nrp' => $u->nrp,
                    'dept' => $u->department?->code,
                    'access_profile_id' => $u->access_profile_id,
                ]),
            'departments' => Department::orderBy('code')->get(),
            'filters' => $request->only('q', 'department_id', 'access_profile_id'),
            'jenis' => DocumentType::kode(),
            // Ikon & warna per kode jenis — `DocumentType::rupa()` dulu
            // dipanggil dari dalam Blade. Dikirim UTUH (RUPA, bukan hanya jenis
            // aktif) sebab profil lama bisa menyimpan kode yang jenisnya sudah
            // dimatikan di Master Data, dan barisnya tetap harus punya rupa.
            'rupa' => DocumentType::RUPA,
        ]);
    }

    public function store(StoreAccessProfileRequest $request): RedirectResponse
    {
        $profile = AccessProfile::create($request->tersimpan());

        $this->audit->log('akses.profil_dibuat', null, [
            'profil' => $profile->nama,
            'jenis' => $profile->jenis_dibolehkan,
            'boleh_review_jsa' => $profile->boleh_review_jsa,
        ]);

        return back()->with('success', "Profil \"{$profile->nama}\" dibuat.");
    }

    public function update(StoreAccessProfileRequest $request, AccessProfile $profile): RedirectResponse
    {
        $sebelum = $profile->only(['nama', 'jenis_dibolehkan', 'boleh_review_jsa']);
        $profile->update($request->tersimpan());

        $this->audit->log('akses.profil_diubah', null, [
            'profil' => $profile->nama,
            'sebelum' => $sebelum,
            'sesudah' => $profile->only(['nama', 'jenis_dibolehkan', 'boleh_review_jsa']),
            // Berapa orang yang wewenangnya ikut berubah detik ini. Angka ini
            // yang membuat catatan audit berguna saat ada yang bertanya
            // "kenapa saya tiba-tiba tak bisa membuat SOP".
            'pengguna_terdampak' => $profile->users()->count(),
        ]);

        return back()->with('success', "Profil \"{$profile->nama}\" diperbarui.");
    }

    /**
     * Menghapus profil TIDAK menghapus penggunanya — `nullOnDelete` di
     * migration menjatuhkan mereka kembali ke Tanpa Akses. Jumlahnya dihitung
     * SEBELUM dihapus, sebab sesudahnya tak ada lagi yang bisa dihitung.
     */
    public function destroy(AccessProfile $profile): RedirectResponse
    {
        $nama = $profile->nama;
        $terdampak = $profile->users()->count();
        $profile->delete();

        $this->audit->log('akses.profil_dihapus', null, [
            'profil' => $nama,
            'pengguna_terdampak' => $terdampak,
        ]);

        return back()->with('success',
            "Profil \"{$nama}\" dihapus. {$terdampak} pengguna kembali ke Tanpa Akses.");
    }

    /**
     * Tetapkan (atau cabut) profil seorang Group Leader.
     *
     * `access_profile_id` kosong = CABUT, dan itu bukan kegagalan validasi
     * melainkan pilihan yang sah: "— Tanpa Akses —" adalah salah satu isi
     * dropdown-nya.
     */
    public function tetapkan(Request $request, User $user): RedirectResponse
    {
        // Batasnya ditegakkan di sini, bukan dipercayakan kepada dropdown:
        // form yang dikarang bisa menembak id siapa pun. Admin sengaja
        // ditolak juga — ia lolos tanpa profil, jadi menetapkannya hanya
        // menyimpan data yang tak pernah dibaca.
        abort_unless($user->hasRole(Roles::ROLE_GROUP_LEADER), 403,
            'Profil akses hanya berlaku bagi Group Leader.');

        $data = $request->validate([
            'access_profile_id' => ['nullable', 'exists:access_profiles,id'],
        ]);

        $sebelum = $user->accessProfile?->nama;
        $user->access_profile_id = $data['access_profile_id'] ?: null;
        $user->save();

        $sesudah = $user->fresh()->accessProfile?->nama;

        $this->audit->log('akses.ditetapkan', null, [
            'nrp' => $user->nrp,
            'nama' => $user->name,
            'sebelum' => $sebelum ?? '— Tanpa Akses —',
            'sesudah' => $sesudah ?? '— Tanpa Akses —',
        ]);

        return back()->with('success',
            "Akses {$user->name} kini: ".($sesudah ?? '— Tanpa Akses —').'.');
    }
}
