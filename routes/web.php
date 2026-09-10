<?php

use App\Http\Controllers\AccessProfileController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AttachmentCommentController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentArsipController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentDistributionController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Controllers\DocumentFeedbackController;
use App\Http\Controllers\DocumentLogController;
use App\Http\Controllers\DocumentPdfController;
use App\Http\Controllers\DocumentRevisionController;
use App\Http\Controllers\DocumentStaffStatusController;
use App\Http\Controllers\InformasiController;
use App\Http\Controllers\JobExecutionController;
use App\Http\Controllers\MasukanSejawatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OffDayController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicStorageController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\MdReviewController;
use App\Http\Controllers\NonaktifController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserApprovalController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

/*
|--------------------------------------------------------------------------
| Berkas storage — foto profil & lampiran, TANPA bergantung symlink
|--------------------------------------------------------------------------
|
| Alasan lengkapnya ada di PublicStorageController — badannya pindah ke sana
| pada v9 Fase 3a supaya berkas `route:cache` tak memuat closure terserialisasi.
|
| Yang TETAP tinggal di sini adalah pola `where`-nya, dan itu disengaja: ia
| bukan hiasan melainkan penjaga: membatasi ke dua folder yang memang publik dan
| melarang titik-dua (`..`), jadi tak ada jalan keluar dari folder itu. Nama
| rute, jalur, dan penjaganya terbaca dalam satu tarikan napas.
|
| Terbuka tanpa auth, sama seperti perilaku symlink yang digantikannya.
*/
Route::get('storage/{path}', PublicStorageController::class)
    ->where('path', '(avatars|lampiran)/[A-Za-z0-9_\-/]+\.[A-Za-z0-9]+')
    ->name('storage.public');

/*
|--------------------------------------------------------------------------
| Tamu (login / pendaftaran)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Terautentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    /*
    | Sakelar pratinjau REDESAIN-UI-V2 DICABUT 2026-09-07 — pemilik mematikan
    | V1 dan menjadikan V2 satu-satunya kerangka. `PratinjauResponseFactory`
    | kini memilih kembaran `V2/*` tanpa membaca sesi, jadi rute ini tak punya
    | lagi hal yang bisa diubahnya.
    */
    // Boleh diakses akun yang BELUM aktif (pending/rejected) — halaman tunggu.
    Route::get('pending', [PageController::class, 'pending'])->name('pending');

    // Semua di bawah ini mewajibkan akun aktif.
    Route::middleware('active')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Komentar foto lampiran (PRD v3.1 §6.2)
        Route::post('attachments/{attachment}/comment', [AttachmentCommentController::class, 'store'])->name('attachments.comment');

        // Informasi Akun (semua peran) — lihat & sunting foto, no. HP, email
        Route::get('akun', [PageController::class, 'account'])->name('account.info');
        Route::put('akun', [PageController::class, 'updateAccount'])->name('account.update');

        /*
        | Ketersediaan diri: cuti / off day / dinas luar (FITUR-BARU-v4 §6).
        | Tanpa alur persetujuan — langsung berlaku, dikonfirmasi di layar.
        | Terbuka bagi semua peran: off GL pun berguna sebagai keterangan bagi
        | SH yang hendak mengajukan revisi atas dokumennya.
        */
        Route::post('off', [OffDayController::class, 'store'])->name('off.store');
        Route::delete('off/{off}', [OffDayController::class, 'destroy'])->name('off.destroy');

        /*
        | Menu INFORMASI (butir 3) — kebijakan, memo, poster, MSDS, BAP,
        | sertifikat, MOC/MPRP, IBPR, instruksi KTT.
        |
        | MEMBACA sengaja TANPA izin apa pun: [KUNCI] pemilik "diakses semua
        | orang, semua departemen, semua jabatan". Akun aktif sudah cukup —
        | menambahkan izin baca di sini akan membuat Non-Staff, yang justru
        | paling butuh membacanya, terkunci di luar.
        |
        | MENGUNGGAH/MENGHAPUS dipagari `informasi.manage` (keputusan B2: Admin
        | saja dulu). Menambahkan SH/DH kelak = satu baris di seeder.
        |
        | Modul ini TIDAK menyentuh satu pun rute/model dokumen mutu.
        */
        Route::prefix('informasi')->name('informasi.')->group(function () {
            Route::get('/', [InformasiController::class, 'index'])->name('index');
            Route::get('berkas/{informasi}', [InformasiController::class, 'file'])->name('file');

            Route::middleware('can:informasi.manage')->group(function () {
                Route::get('create', [InformasiController::class, 'create'])->name('create');
                Route::post('/', [InformasiController::class, 'store'])->name('store');
                // Perbarui = versi BARU bernomor sama; yang lama turun jadi
                // riwayat. Penerimanya `store()` yang sama — yang membedakan
                // hanya ada-tidaknya `{induk}`.
                Route::get('{induk}/perbarui', [InformasiController::class, 'perbarui'])->name('perbarui');
                Route::post('{induk}/perbarui', [InformasiController::class, 'store'])->name('perbarui.store');
                Route::delete('{informasi}', [InformasiController::class, 'destroy'])->name('destroy');
            });
        });

        // Audit Log (menu tersendiri, PRD v3.1 §9)
        Route::get('audit-log', [AuditController::class, 'index'])
            ->middleware('can:audit.view')->name('audit.index');

        /*
        | Riwayat pekerjaan JSA — cerminan web atas apa yang dicentang lapangan
        | lewat `/api/pekerjaan/*`. Terbuka bagi SEMUA peran aktif (lingkupnya
        | dibatasi departemen di dalam controller): JSA dokumen keselamatan,
        | pelaksanaannya memang perlu terlihat semua orang di departemen itu.
        */
        Route::get('riwayat-pekerjaan', [JobExecutionController::class, 'index'])
            ->name('job-executions.index');
        Route::delete('riwayat-pekerjaan/{jobExecution}', [JobExecutionController::class, 'destroy'])
            ->middleware('can:user.manage')
            ->name('job-executions.destroy');

        // Notifikasi lonceng
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

        /*
        | Dokumen — PEMBUATAN & wizard pengisian.
        | Nama route = nama view = nama method, satu banding satu (CLAUDE.md §3).
        */
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::middleware('can:document.create')->group(function () {
            Route::get('documents/create', [DocumentController::class, 'create'])->name('documents.create');
            Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
            /*
            | Dokumen LAMA (arsip, butir 0). Formulirnya menumpang
            | documents/create (saklar "dokumen lama"), jadi tak ada rute
            | `create` tersendiri — hanya penerimanya. Izinnya sama dengan
            | pembuatan biasa: GL & Admin (keputusan pemilik B7).
            */
            Route::post('documents/arsip', [DocumentArsipController::class, 'store'])->name('documents.arsip.store');
        });

        /*
        | Perbaikan dokumen lama. Penjaganya di controller, BUKAN middleware:
        | aturannya "pengunggah atas dept sendiri ATAU Admin"
        | (Document::bisaDisuntingArsipOleh) — bergantung pada dokumennya, jadi
        | tak bisa diputuskan oleh izin belaka.
        */
        Route::get('documents/{document}/arsip/edit', [DocumentArsipController::class, 'edit'])->name('documents.arsip.edit');
        Route::put('documents/{document}/arsip', [DocumentArsipController::class, 'update'])->name('documents.arsip.update');
        /*
        | Lembar CATATAN REVISI dokumen lama (Fase H) — SOP/SP/IK saja.
        | Penjaganya di controller, sama seperti perbaikan di atas: aturannya
        | bergantung pada dokumennya (pengunggah atas dept sendiri ATAU Admin),
        | bukan pada izin belaka.
        */
        Route::get('documents/{document}/arsip/catatan', [DocumentArsipController::class, 'catatan'])->name('documents.arsip.catatan');
        Route::post('documents/{document}/arsip/catatan', [DocumentArsipController::class, 'simpanCatatan'])->name('documents.arsip.catatan.store');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
        Route::post('documents/{document}/step', [DocumentController::class, 'saveStep'])->name('documents.saveStep');
        Route::post('documents/{document}/autosave', [DocumentController::class, 'autosave'])->name('documents.autosave');
        Route::post('documents/{document}/lampiran-upload', [DocumentController::class, 'uploadAttachment'])->name('documents.uploadAttachment');
        Route::post('documents/{document}/submit', [DocumentController::class, 'submit'])->name('documents.submit');
        Route::post('documents/{document}/withdraw', [DocumentController::class, 'withdraw'])->name('documents.withdraw');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        // Keluaran cetak. SATU rute saja: pratinjau di panel wizard menampilkan
        // PDF ini juga lewat iframe, jadi tak ada jalur render kedua yang bisa
        // menyimpang dari hasil cetak sebenarnya.
        Route::get('documents/{document}/pdf', [DocumentPdfController::class, 'pdf'])->name('documents.pdf');

        // Status Dokumen Staff — pantauan read-only (PRD v3.1 §3.3)
        Route::get('status-dokumen-staff', [DocumentStaffStatusController::class, 'staffStatus'])->name('documents.staffStatus');

        /*
        | Dokumen — DAUR HIDUP pasca-terbit: Berlaku, revisi Tipe B, Tidak Berlaku.
        */
        /*
        | Masukan lapangan Non-Staff atas dokumen Berlaku (FITUR-BARU-v4 §3).
        | Kanal TERPISAH dari isi dokumen — Non-Staff tetap read-only atas
        | dokumennya sendiri. Batas siapa-boleh-apa ditegakkan di controller
        | (gate `beri-masukan` + Document::bisaDiberiMasukanOleh).
        */
        // Rem 10/menit: masukan diketik manusia, jadi sepuluh per menit sudah
        // jauh di atas pemakaian wajar — sementara tanpa rem satu orang bisa
        // membanjiri lonceng SH/DH dari HP (CLAUDE.md §15).
        Route::post('documents/{document}/masukan', [DocumentFeedbackController::class, 'store'])
            ->middleware('throttle:10,1')->name('documents.feedback.store');
        Route::post('masukan/{feedback}/balas', [DocumentFeedbackController::class, 'respond'])->name('documents.feedback.respond');

        /*
        | Masukan SEJAWAT antar-GL atas dokumen yang BELUM Berlaku
        | (PLAN-AKSES-v8 Fase 2).
        |
        | Kanal KEDUA, sengaja tak bercampur dengan masukan lapangan di atas:
        | yang itu milik Non-Staff atas dokumen BERLAKU dan berujung di antrean
        | SH/DH; yang ini milik sesama GL atas dokumen yang masih bisa diubah,
        | dan berujung langsung di tangan pembuatnya. Batasnya ditegakkan
        | Document::bisaDiberiMasukanSejawatOleh.
        */
        Route::get('dokumen/{document}/masukan-sejawat/beri', [MasukanSejawatController::class, 'create'])->name('masukan-sejawat.create');
        // Rem 10/menit, sama dengan kanal lapangan: masukan diketik manusia.
        Route::post('dokumen/{document}/masukan-sejawat', [MasukanSejawatController::class, 'store'])
            ->middleware('throttle:10,1')->name('masukan-sejawat.store');
        Route::get('dokumen/{document}/masukan-sejawat', [MasukanSejawatController::class, 'show'])->name('masukan-sejawat.show');

        Route::get('dokumen-revisi', [DocumentRevisionController::class, 'revisions'])->name('documents.revisions');
        // Usulan isi lembar CATATAN REVISI (tombol "Revisi" di langkah Log
        // Revisi, butir 7b). Baca-saja: ia hanya membandingkan isi & mengukur
        // halaman, tak menulis apa pun. Merender PDF sekali, jadi diberi rem
        // supaya tombol yang ditekan bertubi-tubi tak menghabiskan CPU server.
        Route::get('documents/{document}/usulan-catatan-revisi', [DocumentRevisionController::class, 'usulanCatatanRevisi'])
            ->middleware('throttle:20,1')->name('documents.revisi.usulan');
        Route::get('dokumen-berlaku', [DocumentRevisionController::class, 'published'])->name('documents.published');
        Route::get('dokumen-tidak-berlaku', [DocumentRevisionController::class, 'obsolete'])->name('documents.obsolete');
        // Distribusi dokumen Berlaku (v5 Fase C). Izinnya ditegakkan di
        // controller (sama seperti published()), bukan middleware, supaya
        // aturan lingkup departemen & izin duduk berdampingan.
        Route::get('distribusi-dokumen', [DocumentDistributionController::class, 'distribution'])->name('documents.distribution');
        // Rincian SATU informasi: siapa orangnya, bukan sekadar departemen mana
        // yang tertinggal. Penjaganya sama persis dengan distribution().
        Route::get('distribusi/informasi/{informasi}', [DocumentDistributionController::class, 'rincianInformasi'])
            ->name('documents.rincianInformasi');

        /*
        | Log Dokumen (PLAN-REVISI-v6 Fase C, D5) — dua daftar BACA SAJA yang
        | mengumpulkan data yang sudah tersimpan di tempat lain. Penjaganya di
        | controller (`dashboardPenuh()`), sama seperti published()/distribution():
        | ia bukan izin spatie melainkan "semua kecuali Non-Staff".
        */
        Route::get('log-dokumen/masukan', [DocumentLogController::class, 'masukan'])->name('log.masukan');
        Route::get('log-dokumen/pesan', [DocumentLogController::class, 'pesan'])->name('log.pesan');

        /*
        | Export daftar induk dokumen per jenis (SOP/IK/SP/JSA) ke Excel.
        |
        | TIGA izin, lingkupnya berbeda: `document.publish` (PJO + Admin IT)
        | menarik ketujuh departemen; `document.review` (SH/DH) dan
        | `document.create` (GL, PLAN-AKSES-v7 Fase 1) hanya departemennya
        | sendiri — butir 6 mencabut ketetapan lama "SH/DH sengaja belum
        | diberi", dan v7 mencabut "GL sengaja tidak".
        |
        | BUKAN `document.view_all`: izin itu juga dipegang MD, padahal MD
        | meninjau penulisan, ia bukan pemegang daftar induk.
        |
        | Penjaganya di CONTROLLER, bukan middleware: `can:` tak bisa menyatakan
        | "salah satu dari tiga izin", dan lingkup departemennya toh ditentukan
        | oleh izin mana yang dipegang — dua keputusan yang harus duduk bersama.
        */
        Route::get('dokumen-export/{type}', [DocumentExportController::class, 'export'])
            ->name('documents.export');

        // Aktifkan kembali dokumen Tidak Berlaku — Admin IT saja (izinnya
        // diperiksa lagi di controller bersama deteksi bentrok nomor).
        Route::post('documents/{document}/restore-obsolete', [DocumentRevisionController::class, 'restoreObsolete'])
            ->middleware('can:user.manage')
            ->name('documents.restoreObsolete');

        Route::middleware('can:document.request_revision')->group(function () {
            Route::post('documents/{document}/request-revision', [DocumentRevisionController::class, 'requestRevision'])->name('documents.requestRevision');
            Route::post('documents/{document}/cancel-revision-b', [DocumentRevisionController::class, 'cancelRevisionB'])->name('documents.cancelRevisionB');
            // Rollback ke versi Tidak Berlaku (Fase G) — wewenangnya SAMA dengan
            // memulai revisi, karena yang dihasilkannya memang draft revisi
            // biasa; batas kepemilikan per-dokumen ditegakkan di controller.
            Route::post('documents/{document}/rollback', [DocumentRevisionController::class, 'rollback'])->name('documents.rollback');
            // Ajukan nonaktif (Fase F) — MENGGANTIKAN `documents.makeObsolete`
            // yang dulu mematikan dokumen sekali klik. Rutenya duduk di grup
            // izin yang sama karena wewenang memulainya memang sama dengan
            // memulai revisi; batas kepemilikan per-dokumen di controller.
            Route::post('documents/{document}/ajukan-nonaktif', [NonaktifController::class, 'ajukan'])->name('nonaktif.ajukan');
        });

        /*
        | Peninjauan — 'review-access' (AppServiceProvider) = SH/DH, plus GL
        | SHE/Plant yang meninjau JSA saja. Batas JENIS dokumennya ditegakkan
        | ReviewController::authorizeReviewer(), bukan gate ini.
        */
        Route::middleware('can:review-access')->group(function () {
            Route::get('review', [ReviewController::class, 'index'])->name('review.index');
            Route::get('review/{document}', [ReviewController::class, 'show'])->name('review.show');
            Route::post('review/{document}', [ReviewController::class, 'store'])->name('review.store');
            // Analisis AI berjalan di antrean (AnalisisAi job) sebab bisa
            // memakan sampai 360 dtk — jauh di atas batas eksekusi request web.
            // POST memulai (202, kembali seketika); GET (URI sama) dijemput
            // panel tiap beberapa detik sampai hasilnya siap.
            Route::post('review/{document}/ai', [ReviewController::class, 'aiAnalyze'])
                ->middleware('throttle:10,1')->name('review.ai');
            Route::get('review/{document}/ai', [ReviewController::class, 'aiStatus'])->name('review.ai.status');
            Route::post('review/{document}/cancel-revision', [ReviewController::class, 'cancelRevision'])->name('review.cancelRevision');
            // Alihkan peninjauan JSA ke peninjau lain (PLAN-REVISI-v6 butir 3).
            // Di-throttle seperti kanal masukan: aksinya memindahkan tanggung
            // jawab DAN mengirim email, jadi tak boleh bisa ditekan beruntun.
            Route::post('review/{document}/alihkan', [ReviewController::class, 'alihkan'])
                ->middleware('throttle:10,1')->name('review.alihkan');
        });

        /*
        | Persetujuan Nonaktif (Fase F) — SATU antrean untuk KETIGA tahap
        | (SH/DH -> MD -> PJO); yang membedakan isinya adalah siapa yang membuka.
        | Penjaganya di controller (Document::tahapNonaktifUntuk), bukan
        | middleware: `can:` tak bisa menyatakan "salah satu dari tiga peran",
        | dan tahap `sh` masih dibatasi lagi oleh departemen.
        */
        Route::get('nonaktif', [NonaktifController::class, 'index'])->name('nonaktif.index');
        Route::post('nonaktif/{document}', [NonaktifController::class, 'putuskan'])->name('nonaktif.putuskan');

        /*
        | Peninjauan Management Development — tahap KEDUA (sistematika penulisan),
        | sesudah SH/DH dan sebelum PJO.
        |
        | Izinnya SENGAJA terpisah dari `review-access`: MD tak boleh masuk
        | antrean peninjauan tahap pertama, dan sebaliknya SH/DH tak boleh
        | mengerjakan tahap MD.
        */
        Route::middleware('can:document.review_md')->group(function () {
            Route::get('review-md', [MdReviewController::class, 'index'])->name('review.md');
            Route::get('review-md/{document}', [MdReviewController::class, 'show'])->name('review.md.show');
            Route::post('review-md/{document}', [MdReviewController::class, 'store'])->name('review.md.store');
            Route::post('review-md/{document}/ai', [MdReviewController::class, 'aiAnalyze'])
                ->middleware('throttle:10,1')->name('review.md.ai');
            Route::get('review-md/{document}/ai', [MdReviewController::class, 'aiStatus'])->name('review.md.ai.status');
        });

        // Persetujuan
        Route::middleware('can:document.approve')->group(function () {
            Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
            Route::get('approvals/{document}', [ApprovalController::class, 'show'])->name('approvals.show');
            Route::post('approvals/{document}', [ApprovalController::class, 'store'])->name('approvals.store');
        });

        // Persetujuan pendaftaran akun — Admin IT & PJO lintas 7 departemen.
        Route::middleware('can:user.approve_registration')->group(function () {
            Route::get('users/pending', [UserApprovalController::class, 'index'])->name('users.pending');
            Route::post('users/{user}/approve', [UserApprovalController::class, 'approve'])->name('users.approve');
            Route::post('users/{user}/reject', [UserApprovalController::class, 'reject'])->name('users.reject');
        });

        // Manajemen user (Admin IT)
        Route::middleware('can:user.manage')->group(function () {
            // Musnahkan dokumen — SATU-SATUNYA aksi tanpa rollback, karena itu
            // dikunci ke Admin dan bersyarat alasan + ketik ulang nomor.
            Route::delete('documents/{document}/purge', [DocumentRevisionController::class, 'purge'])->name('documents.purge');
            // Bersihkan SELURUH dokumen (PLAN C §C4).
            //
            // Berawalan `admin/` BUKAN karena rapi, melainkan karena harus:
            // `documents/purge-all` beruas dua dan DELETE, persis sama bentuknya
            // dengan `documents/{document}` (documents.destroy) yang terdaftar
            // lebih dulu — Laravel mencocokkan yang pertama, mengira "purge-all"
            // sebuah id dokumen, dan menjawab 404. Awalan ini yang membedakannya.
            Route::delete('admin/documents/purge-all', [DocumentRevisionController::class, 'purgeAll'])->name('documents.purgeAll');
            Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
            // Ubah peran: pendaftaran mandiri selalu melahirkan Non-Staff, jadi
            // tanpa ini seorang GL yang salah jalur terkunci selamanya.
            Route::get('users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
            Route::post('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggleStatus');
            // Konfigurasi akun Management Development (saklar AI, aktif/nonaktif,
            // reset sandi). Hanya Admin — sesuai ketetapan pemilik bahwa akun MD
            // dibuat & dikelola Admin saja.
            Route::post('users/{user}/md-config', [UserManagementController::class, 'updateMd'])->name('users.mdConfig');

            /*
            | Manajemen Akses (PLAN-AKSES-v7 Fase 4b) — profil akses & penetapannya.
            |
            | `akses/tetapkan/{user}` sengaja BERUAS TIGA. `akses/{user}` beruas
            | dua dan POST, bentuknya persis sama dengan `akses` + id — dan yang
            | terpenting, ia menyamarkan bahwa parameternya seorang PENGGUNA,
            | bukan sebuah profil.
            */
            Route::get('akses', [AccessProfileController::class, 'index'])->name('akses.index');
            Route::post('akses', [AccessProfileController::class, 'store'])->name('akses.store');
            Route::put('akses/{profile}', [AccessProfileController::class, 'update'])->name('akses.update');
            Route::delete('akses/{profile}', [AccessProfileController::class, 'destroy'])->name('akses.destroy');
            Route::post('akses/tetapkan/{user}', [AccessProfileController::class, 'tetapkan'])->name('akses.tetapkan');

            /*
            | Pengaturan Sistem (PLAN-AKSES-v8 Fase 5a) — Penomoran Dokumen.
            |
            | Menyimpannya PUT, bukan POST: setelan site itu SATU sumber daya
            | yang selalu ada dan hanya diganti isinya — tak pernah ada baris
            | kedua yang dibuat. Bentuk rutenya karena itu tanpa parameter.
            */
            Route::get('pengaturan/penomoran', [PengaturanController::class, 'penomoran'])->name('pengaturan.penomoran');
            Route::put('pengaturan/penomoran', [PengaturanController::class, 'simpanPenomoran'])->name('pengaturan.penomoran.simpan');

            /*
            | Master Data (PLAN-AKSES-v8 Fase 5b) — departemen, jenis dokumen,
            | kategori informasi. SATU layar, tiga kartu.
            |
            | Perhatikan yang TIDAK ada: rute DELETE, satu pun. Ketiga tabel
            | ditunjuk data lain (`users.department_id`, `documents.
            | department_id`, `documents.document_type_id`, `informasi.
            | kategori`), jadi yang tersedia hanyalah saklar `is_active` —
            | berhenti ditawarkan, bukan lenyap.
            |
            | Tambah & ubah berbagi controller method: yang membedakan hanya
            | ada-tidaknya route model, dan bentuk rutenya (POST tanpa
            | parameter vs PUT dengan) yang menyatakan mana yang mana.
            */
            Route::get('pengaturan/master', [MasterDataController::class, 'index'])->name('pengaturan.master');
            Route::post('pengaturan/master/departemen', [MasterDataController::class, 'simpanDepartemen'])->name('pengaturan.master.departemen.tambah');
            Route::put('pengaturan/master/departemen/{department}', [MasterDataController::class, 'simpanDepartemen'])->name('pengaturan.master.departemen.simpan');
            Route::put('pengaturan/master/jenis/{jenis}', [MasterDataController::class, 'simpanJenis'])->name('pengaturan.master.jenis.simpan');
            Route::post('pengaturan/master/kategori', [MasterDataController::class, 'simpanKategori'])->name('pengaturan.master.kategori.tambah');
            Route::put('pengaturan/master/kategori/{kategori}', [MasterDataController::class, 'simpanKategori'])->name('pengaturan.master.kategori.simpan');

            /*
            | Konfigurasi Sistem (PLAN-AKSES-v8 Fase 5c) — setelan AI & kartu
            | Kesehatan.
            |
            | Tiga rute tulis, dan ketiganya sengaja SEMPIT:
            |
            |   • `sistem.ai`   PUT tanpa parameter — satu sumber daya yang
            |     selalu ada dan hanya diganti isinya, sama seperti penomoran.
            |   • `sistem.uji-email` ber-throttle:3,1 dan mengirim HANYA ke
            |     alamat Admin yang sedang login. Tanpa dua batas itu, layar ini
            |     adalah pengirim surat massal atas nama perusahaan.
            |   • `sistem.cache` menjalankan DUA perintah tetap, bukan artisan
            |     sembarang (§9.1). Kotak isian perintah adalah eksekusi kode
            |     jarak jauh dengan nama lain.
            */
            Route::get('pengaturan/sistem', [PengaturanController::class, 'sistem'])->name('pengaturan.sistem');
            Route::put('pengaturan/sistem/ai', [PengaturanController::class, 'simpanAi'])->name('pengaturan.sistem.ai');
            Route::post('pengaturan/sistem/uji-email', [PengaturanController::class, 'ujiEmail'])
                ->middleware('throttle:3,1')->name('pengaturan.sistem.uji-email');
            // Uji koneksi AI (PLAN-PREPRODUKSI-v9 Fase 2a) — rem yang sama
            // sifatnya dengan uji-email: satu tombol memanggil layanan luar,
            // jadi ia tak boleh bisa ditekan berulang-ulang tanpa batas.
            Route::post('pengaturan/sistem/uji-ai', [PengaturanController::class, 'ujiAi'])
                ->middleware('throttle:6,1')->name('pengaturan.sistem.uji-ai');
            Route::post('pengaturan/sistem/cache', [PengaturanController::class, 'bersihkanCache'])->name('pengaturan.sistem.cache');
        });
    });
});

