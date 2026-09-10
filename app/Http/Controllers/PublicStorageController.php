<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Berkas storage — foto profil & lampiran, TANPA bergantung symlink.
 *
 * Bawaan Laravel menyajikan `storage/app/public` lewat symlink `public/storage`
 * yang harus dibuat dengan `php artisan storage:link` di TIAP server. Symlink
 * itu rapuh: tidak ikut Git, hilang tiap folder `public/` ditimpa, dan banyak
 * shared hosting/cPanel melarangnya sama sekali. Akibatnya foto ada di disk
 * tapi semua URL-nya 404.
 *
 * Controller ini jaring pengamannya: kalau symlink ADA, Apache/Nginx menyajikan
 * filenya lebih dulu dan rute ini tak pernah tersentuh (tanpa biaya). Kalau
 * symlink TIDAK ada, PHP yang menyajikan — foto tetap tampil, nol perintah
 * yang perlu dijalankan di server.
 *
 * Sengaja BUKAN `'serve' => true` pada disk: pendaftar rute bawaan Laravel
 * (FilesystemServiceProvider::serveFiles) berhenti bekerja begitu `route:cache`
 * dipakai — dan runbook rilis memang men-cache rute.
 *
 * Terbuka tanpa auth, sama seperti perilaku symlink yang digantikannya. Batas
 * folder & bentuk nama berkas ditegakkan pola `where` di `routes/web.php`, satu
 * tempat bersama nama rutenya.
 *
 * ---
 * Kenapa controller, bukan closure seperti sebelumnya (PLAN-PREPRODUKSI-v9
 * Fase 3a): rencana menduga closure MEMBLOKIR `route:cache`. Diuji di Laravel
 * 12.63: tidak — `laravel/serializable-closure` menyerialisasinya, dan rutenya
 * tetap melayani berkas dengan cache aktif. Jadi perpindahan ini pengerasan,
 * bukan perbaikan: berkas cache tak lagi memuat closure terserialisasi yang
 * akan pecah diam-diam begitu badannya suatu saat menangkap sesuatu yang tak
 * bisa diserialisasi.
 */
class PublicStorageController extends Controller
{
    public function __invoke(Request $request, string $path)
    {
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
