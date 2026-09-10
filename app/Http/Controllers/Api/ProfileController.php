<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\ProfilPengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Sunting profil sendiri dari aplikasi mobile.
 *
 * DUA rute, bukan satu, karena begitulah layar "Informasi Akun" di HP
 * memanggilnya (`lib/layar/informasi_akun.dart`): foto diunggah lebih dulu
 * sebagai multipart, baru nomor HP & email dikirim sebagai JSON. Urutan itu
 * disengaja di sisi aplikasi — kalau unggahan fotonya gagal, teks tidak ikut
 * tersimpan, jadi layar tak pernah menampilkan keadaan setengah jadi.
 * Menggabungkannya jadi satu rute multipart berarti memutus aplikasi yang
 * sudah ditulis, untuk keuntungan yang tak seorang pun lihat.
 *
 * Penyimpanannya sendiri TIDAK diduplikasi: keduanya lewat {@see ProfilPengguna},
 * service yang sama dengan halaman web "Informasi Akun".
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfilPengguna $profil) {}

    /**
     * PATCH /api/me — nomor HP, email, dan (opsional) permintaan hapus foto.
     *
     * Aturan validasinya SAMA dengan PageController::updateAccount, termasuk
     * keunikan email yang mengabaikan baris sendiri — tanpa `ignore()`, orang
     * yang menyimpan tanpa mengubah emailnya ditolak oleh emailnya sendiri.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'no_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'hapus_foto' => ['nullable', 'boolean'],
        ]);

        // Kunci `no_hp` (kosakata aplikasi) dipetakan ke kolom `nomor_hp` DI
        // SINI, bukan di service. Yang TIDAK dikirim tidak ikut disentuh —
        // itulah gunanya memeriksa `has()` alih-alih membaca `$data` langsung.
        $ubah = [];
        if ($request->has('no_hp')) {
            $ubah['nomor_hp'] = $data['no_hp'] ?? null;
        }
        if ($request->has('email')) {
            $ubah['email'] = $data['email'] ?? null;
        }

        $this->profil->perbarui($user, $ubah, hapusFoto: $request->boolean('hapus_foto'));

        return response()->json([
            'message' => 'Perubahan tersimpan.',
            'user' => new UserResource($user->load('department')),
        ]);
    }

    /**
     * POST /api/me/foto — unggah foto profil (multipart, field `foto`).
     *
     * Batas 2 MB sama dengan yang dijanjikan teks bantuan di layar HP dan
     * dengan sisi web. `image` + `mimes` ditegakkan server juga, bukan hanya di
     * aplikasi: yang mengirim tak selalu aplikasi kita.
     */
    public function foto(Request $request): JsonResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
        ]);

        $user = $this->profil->perbarui($request->user(), [], $request->file('foto'));

        return response()->json([
            'message' => 'Foto profil diperbarui.',
            'user' => new UserResource($user->load('department')),
        ]);
    }
}
