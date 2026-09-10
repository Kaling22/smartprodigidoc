<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autentikasi aplikasi mobile (token Sanctum).
 *
 * Sengaja TERPISAH dari LoginController sisi web: yang itu memakai sesi cookie
 * dan mengembalikan redirect, sedangkan mobile butuh token dan JSON. Memaksa
 * keduanya jadi satu hanya membuat dua-duanya rumit.
 */
class AuthController extends Controller
{
    /**
     * Pendaftaran mandiri Non-Staff dari HP — cermin RegisterController web.
     *
     * Tiga hal SENGAJA sama dengan sisi web dan tak boleh dilonggarkan:
     * jabatan alur selalu `staff` (yang diketik pendaftar disimpan terpisah
     * sebagai `jabatan_diajukan`, informasi bagi penyetuju), status selalu
     * `pending`, dan pendaftarannya tercatat di audit log.
     *
     * Satu hal SENGAJA berbeda: TIDAK ada token yang dikembalikan. Web
     * me-login-kan pendaftar agar ia mendarat di halaman "menunggu
     * persetujuan"; di HP layar itu tidak butuh sesi, dan memberi token kepada
     * akun `pending` berarti membuat token yang setiap pemakaiannya ditolak
     * `isActive()` — kredensial hidup tanpa satu pun kegunaan.
     */
    public function register(RegisterRequest $request, AuditService $audit): JsonResponse
    {
        $user = User::create([
            'name' => $request->input('nama'),
            'nrp' => $request->input('nrp'),
            'jabatan' => User::JABATAN_STAFF,
            'jabatan_diajukan' => $request->input('jabatan') ?: null,
            'nomor_hp' => $request->input('no_hp'),
            'department_id' => $request->input('department_id'),
            'password' => Hash::make((string) $request->input('password')),
            'status' => 'pending',
        ]);

        $user->assignRole(RolePermissionSeeder::ROLE_STAFF);

        $audit->log('user.register', null, ['user_id' => $user->id, 'department_id' => $user->department_id]);

        return response()->json([
            'message' => 'Pendaftaran diterima. Akun aktif setelah disetujui Group Leader / Pimpinan / Admin IT.',
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('department')->where('nrp', $request->string('nrp'))->first();

        // Pesan sengaja SAMA untuk "NRP tak ada" dan "sandi salah". Kalau
        // dibedakan, halaman ini bisa dipakai menebak NRP mana yang terdaftar.
        if (! $user || ! Hash::check((string) $request->string('password'), $user->password)) {
            return response()->json(['message' => 'NRP atau kata sandi salah.'], 401);
        }

        // Akun `pending` (menunggu persetujuan) atau `rejected` tak boleh masuk —
        // aturan yang sama dengan middleware `active` di sisi web.
        if (! $user->isActive()) {
            return response()->json([
                'message' => 'Akun Anda belum aktif. Tunggu persetujuan Admin/PJO.',
            ], 403);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /** Mencabut HANYA token perangkat ini — perangkat lain tetap masuk. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /** Dipakai mobile untuk memastikan token masih berlaku saat aplikasi dibuka. */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('department')),
        ]);
    }
}
