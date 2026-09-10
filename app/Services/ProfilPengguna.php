<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Sunting info akun SENDIRI: foto profil, nomor HP, email.
 *
 * Dipusatkan di sini karena DUA kanal melakukannya dengan aturan yang harus
 * sama persis — halaman web "Informasi Akun" ({@see \App\Http\Controllers\PageController})
 * dan aplikasi mobile ({@see \App\Http\Controllers\Api\ProfileController}).
 * Selama logikanya disalin dua kali, suatu hari yang satu menghapus foto lama
 * dan yang lain meninggalkannya di disk — dan tak ada gejala yang terlihat di
 * layar sampai kuota hosting habis.
 *
 * Field identitas (nama, NRP, jabatan, departemen, peran) SENGAJA tak ada di
 * sini: keempatnya ditetapkan admin, bukan oleh pemilik akun.
 */
class ProfilPengguna
{
    /**
     * @param  array{nomor_hp?: string|null, email?: string|null}  $data
     */
    public function perbarui(
        User $user,
        array $data,
        ?UploadedFile $foto = null,
        bool $hapusFoto = false,
    ): User {
        // `array_key_exists`, bukan `??`: mobile boleh mengirim HANYA foto tanpa
        // menyertakan nomor HP, dan itu tak boleh diam-diam mengosongkannya.
        if (array_key_exists('nomor_hp', $data)) {
            $user->nomor_hp = $data['nomor_hp'] ?: null;
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'] ?: null;
        }

        if ($hapusFoto && $user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
            $user->photo_path = null;
        }

        if ($foto) {
            // Foto lama dibuang SEBELUM yang baru ditulis. Terbalik, berkas
            // yatim menumpuk tiap kali orang mengganti fotonya.
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $user->photo_path = $foto->store('avatars', 'public');
        }

        $user->save();

        return $user;
    }
}
