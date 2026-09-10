<?php

use App\Http\Controllers\Api\AttachmentCommentApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentApiController;
use App\Http\Controllers\Api\DocumentFileController;
use App\Http\Controllers\Api\FeedbackApiController;
use App\Http\Controllers\Api\InformasiApiController;
use App\Http\Controllers\Api\JobExecutionController;
use App\Http\Controllers\Api\KendaliApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\OffDayApiController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewApiController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Controllers\InformasiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute API — dipakai aplikasi mobile (Flutter)
|--------------------------------------------------------------------------
|
| Seluruh berkas ini TAMBAHAN. Tidak ada satu pun rute, controller, atau view
| sisi web yang berubah karenanya — paritas cetak PDF dan 76 test tetap utuh.
|
| Bedanya dengan sisi web: di sini autentikasi memakai TOKEN Sanctum lewat
| header `Authorization: Bearer <token>`, bukan sesi cookie. Aplikasi mobile
| menyimpan tokennya di SharedPreferences.
|
| Laravel otomatis memberi awalan `/api` pada seluruh rute di berkas ini.
*/

// Rem percobaan tebak kata sandi: 6 kali per menit per IP (CLAUDE.md §15).
Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('api.login');

// Pendaftaran mandiri Non-Staff — cermin formulir web, akun selalu `pending`.
// Remnya lebih ketat daripada login: satu HP tak punya alasan mendaftar lebih
// dari tiga akun semenit, dan tanpa rem ini tabel `users` bisa dibanjiri
// tanpa satu pun kredensial.
Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:3,1')
    ->name('api.register');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('me', [AuthController::class, 'me'])->name('api.me');

    /*
    | Sunting profil sendiri — foto, nomor HP, email. Field identitas (nama,
    | NRP, jabatan, departemen) TIDAK ada di sini: keempatnya ditetapkan admin.
    |
    | Dua rute karena begitulah layar "Informasi Akun" di HP memanggilnya;
    | penyimpanannya satu, lewat service ProfilPengguna yang juga dipakai web.
    */
    Route::patch('me', [ProfileController::class, 'update'])
        ->middleware('throttle:20,1')->name('api.me.update');
    Route::post('me/foto', [ProfileController::class, 'foto'])
        ->middleware('throttle:10,1')->name('api.me.foto');

    /*
    | Dokumen BERLAKU — satu rute untuk semua jenis & departemen.
    |   /api/documents?type=SOP&department=ICTMD
    | Menambah jenis dokumen baru TIDAK menambah rute di sini (CLAUDE.md §7).
    */
    Route::get('documents', [DocumentApiController::class, 'index'])->name('api.documents.index');
    Route::get('documents/{document}', [DocumentApiController::class, 'show'])->name('api.documents.show');

    /*
    | Masukan lapangan Non-Staff (FITUR-BARU-v4 §3) — padanan "Report" di
    | aplikasi lama, tapi pengirimnya diambil dari token, bukan dari badan
    | permintaan. Menulis ke tabel yang SAMA dengan kanal web.
    */
    Route::get('masukan', [FeedbackApiController::class, 'index'])->name('api.feedback.index');
    // Rem yang sama dengan kanal web — satu orang tak boleh bisa membanjiri
    // lonceng SH/DH dari HP (CLAUDE.md §15).
    Route::post('documents/{document}/masukan', [FeedbackApiController::class, 'store'])
        ->middleware('throttle:10,1')->name('api.feedback.store');

    /*
    | Kotak masuk "Masukan Dokumen" (PLAN-MOBILE-v6 §2.2) — GL & SH/DH
    | MENINDAKLANJUTI dari HP, bukan sekadar membaca.
    |
    |   balas  → jawab lalu tutup, tanpa revisi  (GL, SH, DH)
    |   adopsi → teruskan jadi ALASAN REVISI     (SH, DH, PJO — bukan GL)
    |
    | Keduanya memanggil DocumentFeedbackService yang SAMA dengan kanal web;
    | tak ada logika balasan maupun pengajuan revisi yang kedua.
    */
    Route::post('masukan/{feedback}/balas', [FeedbackApiController::class, 'balas'])
        ->middleware('throttle:20,1')->name('api.feedback.balas');
    Route::post('masukan/{feedback}/adopsi', [FeedbackApiController::class, 'adopsi'])
        ->middleware('throttle:10,1')->name('api.feedback.adopsi');

    // Byte PDF. Bentuk jalurnya menyesuaikan cara mobile menyusun URL berkas:
    // `{publicUrl}/{jenis}/{berkas}` — lihat DocumentFileController.
    Route::get('files/{jenis}/{berkas}', [DocumentFileController::class, 'show'])
        ->name('api.files.show');

    /*
    | Menu Informasi (butir 3) — BACA SAJA dari HP.
    |
    | Penyaji berkasnya SENGAJA menunjuk InformasiController web yang sudah ada,
    | bukan salinan di namespace Api: method itu tak punya apa pun yang khas
    | sesi (ia menerima model, mengembalikan berkas), dan menyalinnya berarti
    | dua tempat yang harus sepakat soal `nosniff`, CSP, dan disk `local`.
    */
    Route::get('informasi', [InformasiApiController::class, 'index'])->name('api.informasi.index');
    Route::get('informasi/{informasi}/berkas', [InformasiController::class, 'file'])
        ->name('api.informasi.file');

    /*
    | Peninjauan dokumen dari HP — SH/DH untuk semua jenis, GL SHE & Plant untuk
    | JSA saja. Gate `review-access` yang sama dengan menu web dipakai ulang
    | (AppServiceProvider), jadi daftar siapa-boleh-meninjau cuma ada satu.
    |
    | Wewenang per-dokumen masih diperiksa lagi di dalam controller lewat
    | ReviewAccess: gate ini hanya membuka pintu menunya.
    */
    Route::middleware('can:review-access')->group(function () {
        Route::get('review', [ReviewApiController::class, 'index'])->name('api.review.index');
        Route::get('review/{document}', [ReviewApiController::class, 'show'])->name('api.review.show');
        Route::post('review/{document}', [ReviewApiController::class, 'store'])
            ->middleware('throttle:30,1')->name('api.review.store');

        Route::post('attachments/{attachment}/komentar', [AttachmentCommentApiController::class, 'store'])
            ->middleware('throttle:20,1')->name('api.attachments.comment');
    });

    /*
    | Pelaksanaan pekerjaan menggunakan JSA — fitur checklist mobile.
    |
    | GET   /api/pekerjaan                  → riwayat pekerjaan user login
    | POST  /api/pekerjaan                  → mulai pekerjaan baru
    | GET   /api/pekerjaan/{id}             → detail + status checklist
    | PATCH /api/pekerjaan/{id}/checklist   → update centang per pengendalian
    | PATCH /api/pekerjaan/{id}/selesai     → tandai selesai / batalkan
    |
    | Jalur, nama field, dan bentuk balasannya PERSIS seperti yang dipakai
    | aplikasi mobile — jangan dirapikan, aplikasinya yang jadi rusak.
    */
    /*
    | Ketersediaan Saya — cuti / off day / dinas luar (butir 1).
    |
    | Aturannya SATU dengan web: validasi dari `UserOffDay::aturan()`, kabar ke
    | pembuat dokumen dari `OffDayController::beriTahuPembuat()`. Langsung
    | berlaku, tanpa alur persetujuan — jangan tambahkan status "menunggu".
    */
    Route::get('off', [OffDayApiController::class, 'index'])->name('api.off.index');
    Route::post('off', [OffDayApiController::class, 'store'])
        ->middleware('throttle:20,1')->name('api.off.store');
    Route::delete('off/{off}', [OffDayApiController::class, 'destroy'])->name('api.off.destroy');

    /*
    | Menu KENDALI — baca saja (butir 2, 3, 4, 6, 7).
    |
    | Lingkup tiap layar disalin dari padanannya di web dan dipagari di dalam
    | controller, bukan lewat middleware: aturannya berbunyi "salah satu dari
    | beberapa peran, dengan lingkup yang ikut peran mana" — kalimat yang tak
    | bisa dinyatakan oleh `can:`.
    */
    Route::get('perjalanan', [KendaliApiController::class, 'perjalanan'])->name('api.perjalanan.index');
    Route::get('perjalanan/{document}', [KendaliApiController::class, 'perjalananShow'])->name('api.perjalanan.show');
    Route::get('status-dokumen', [KendaliApiController::class, 'statusDokumen'])->name('api.status-dokumen');
    Route::get('distribusi', [KendaliApiController::class, 'distribusi'])->name('api.distribusi.index');
    Route::get('distribusi/{document}', [KendaliApiController::class, 'distribusiShow'])->name('api.distribusi.show');
    Route::get('daftar-induk/{type}', [KendaliApiController::class, 'daftarInduk'])->name('api.daftar-induk');

    /*
    | Ekspor daftar induk (butir 6). Controller-nya SAMA dengan web — ia hanya
    | membaca `$request->user()`, yang di sini datang dari token Sanctum.
    | `?format=pdf` memilih PDF lanskap, selain itu Excel.
    */
    Route::get('dokumen-export/{type}', [DocumentExportController::class, 'export'])
        ->middleware('throttle:20,1')->name('api.dokumen-export');

    /*
    | LONCENG — satu tabel `notifications` dengan sisi web, jadi menandai
    | dibaca di HP ikut mengurangi angkanya di peramban dan sebaliknya.
    |
    | `unread-count` dipisah dari `index` karena ia yang DIPOLLING mobile tiap
    | app resume: ia hanya menghitung, tak pernah memuat satu baris pun.
    | Remnya 60/menit — cukup untuk polling satu menitan, tetap membendung
    | aplikasi yang salah pasang timer.
    */
    Route::get('notifications', [NotificationApiController::class, 'index'])
        ->name('api.notifications.index');
    Route::get('notifications/unread-count', [NotificationApiController::class, 'unreadCount'])
        ->middleware('throttle:60,1')->name('api.notifications.unread-count');
    Route::post('notifications/read-all', [NotificationApiController::class, 'readAll'])
        ->middleware('throttle:20,1')->name('api.notifications.read-all');
    // SESUDAH `read-all` & `unread-count` — kalau lebih dulu, `{id}` menelan
    // keduanya sebagai id dan keduanya selalu 404.
    Route::post('notifications/{id}/read', [NotificationApiController::class, 'read'])
        ->middleware('throttle:60,1')->name('api.notifications.read');

    Route::get('pekerjaan', [JobExecutionController::class, 'index'])->name('api.pekerjaan.index');
    Route::post('pekerjaan', [JobExecutionController::class, 'store'])->name('api.pekerjaan.store');
    Route::get('pekerjaan/{jobExecution}', [JobExecutionController::class, 'show'])->name('api.pekerjaan.show');
    Route::patch('pekerjaan/{jobExecution}/checklist', [JobExecutionController::class, 'updateChecklist'])->name('api.pekerjaan.checklist');
    Route::patch('pekerjaan/{jobExecution}/selesai', [JobExecutionController::class, 'updateStatus'])->name('api.pekerjaan.selesai');
});
