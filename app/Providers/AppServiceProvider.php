<?php

namespace App\Providers;

use App\Inertia\PratinjauResponseFactory;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\FallbackReviewer;
use App\Services\Ai\GeminiReviewer;
use App\Services\Ai\NullReviewer;
use App\Services\Ai\OpenRouterReviewer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Inertia\ResponseFactory;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        | Sakelar pratinjau REDESAIN-UI-V2 — lihat PratinjauResponseFactory.
        |
        | `singleton` menimpa binding senama milik ServiceProvider Inertia.
        | Yang menentukan pemenangnya adalah urutan pendaftaran, bukan jenis
        | binding-nya, jadi ini SUDAH DIVERIFIKASI berjalan dari `register()`
        | (bukan `boot()`) — dijaga tests/Feature/PratinjauUiTest.php, yang
        | akan merah seketika bila urutan itu berubah di rilis Laravel/Inertia
        | berikutnya.
        |
        | Bawaannya MENYALA sejak rencana pra-produksi Fase 0 (K-A): sesi yang
        | tak menyebut `ui_v2` mendapat V2. Dengan `ui_v2 => false` factory ini
        | berperilaku identik dengan aslinya. Dihapus di Tranche 4.
        */
        $this->app->singleton(ResponseFactory::class, PratinjauResponseFactory::class);

        // Penyedia AI di-abstraksi: berganti penyedia cukup mengubah binding ini,
        // bukan memburu pemanggilan di seluruh kode. Bila AI dimatikan atau
        // penyedianya tak dikenal → NullReviewer, sehingga aplikasi tetap jalan
        // dan halaman tinjau menampilkan pesan "AI sedang dinonaktifkan".
        $this->app->bind(AiReviewerInterface::class, function () {
            /*
            | Setelan dibaca dari Pengaturan::ai() — baris tabel menang, .env
            | jadi cadangan (PLAN-AKSES-v8 Fase 5c).
            |
            | Aman dibaca dari sini justru KARENA ini closure: isinya baru
            | dijalankan saat AiReviewerInterface benar-benar di-resolve, yaitu
            | di dua controller tinjau. `register()` sendiri berjalan pada
            | SETIAP perintah artisan — termasuk `migrate` di pemasangan yang
            | tabel `pengaturan`-nya belum ada. Memindahkan query ini ke luar
            | closure membuat pemasangan baru mustahil di-migrate.
            */
            $ai = Pengaturan::ai();

            if (! $ai['enabled']) {
                return new NullReviewer;
            }

            $utama = $this->buatReviewer($ai['provider'], $ai['key'], $ai['model']);

            // Cadangan HANYA dipasang bila dikonfigurasi lengkap. Tanpa itu
            // perilakunya persis seperti dulu — satu penyedia, tanpa pembungkus.
            $cadangan = $this->buatReviewer(
                $ai['cadangan_provider'],
                $ai['cadangan_key'],
                $ai['cadangan_model'],
            );

            return $cadangan instanceof NullReviewer
                ? $utama
                : new FallbackReviewer([$utama, $cadangan]);
        });
    }

    /**
     * Rakit satu penyedia AI dari nama + key + model.
     *
     * Penyedia tak dikenal, key kosong, atau model kosong → NullReviewer, yang
     * sekaligus menjadi cara pemanggil di atas mengetahui "cadangan tak diatur".
     */
    private function buatReviewer(?string $provider, ?string $key, ?string $model): AiReviewerInterface
    {
        if (! $provider || ! $key || ! $model) {
            return new NullReviewer(NullReviewer::TAK_LENGKAP);
        }

        return match ($provider) {
            'gemini' => new GeminiReviewer($key, $model),
            'openrouter' => new OpenRouterReviewer($key, $model),
            default => new NullReviewer(NullReviewer::TAK_LENGKAP),
        };
    }

    public function boot(): void
    {
        // Penomoran halaman memakai markup Bootstrap 5 — menyamai UI (CDN Bootstrap).
        Paginator::useBootstrapFive();

        /*
        | KATUP PENGAMAN PERALIHAN MAIL (docs/PANDUAN-PRODUKSI-EMAIL.md).
        |
        | Selama `MAIL_PAKSA_KE` terisi, SELURUH email dibelokkan ke alamat itu
        | berapa pun penerima aslinya. Gunanya satu hari saja: hari relay
        | sungguhan pertama kali dipasang. Saat itu basis data masih memuat
        | alamat uji, dan uji coba pertama bisa mengirim surat resmi ke orang
        | yang tak bermaksud menerimanya — kesalahan yang tak bisa ditarik
        | kembali. Sesudah yakin, hapus barisnya dari .env.
        |
        | Lonceng TIDAK terpengaruh: ini hanya menyentuh kanal mail.
        */
        if ($paksaKe = config('mail.paksa_ke')) {
            Mail::alwaysTo($paksaKe);
        }

        // @role('group_leader') … @endrole — gula sintaksis untuk Blade.
        Blade::if('role', fn (string $role) => auth()->check() && auth()->user()->hasRole($role));

        // Boleh MEMBUKA area "Tinjau Dokumen"? Peninjau biasa (SH/DH) plus GL
        // SHE yang meninjau JSA saja (v7 Fase 3). Dipakai penjaga rute & menu;
        // batas JENIS dokumennya ditegakkan ReviewController::authorizeReviewer(),
        // bukan gate ini.
        Gate::define('review-access', fn (User $user) => $user->can('document.review') || $user->canReviewJsa());

        // Boleh MEMBERI MASUKAN atas dokumen Berlaku (FITUR-BARU-v4 §3)?
        //
        // HANYA Non-Staff. Peran lain sudah punya kanal resminya sendiri: GL
        // menyusun, SH/DH meninjau & mengajukan revisi, PJO menyetujui —
        // memberi mereka kanal masukan justru memecah jejak keputusan.
        //
        // Sengaja gate berbasis JABATAN, bukan izin spatie baru: aturannya
        // memang soal jabatan, dan menambah izin menuntut seeder dijalankan
        // ulang + cache izin di-reset (HANDOVER-MIGRASI.md §7.0).
        Gate::define('beri-masukan', fn (User $user) => $user->jabatan === User::JABATAN_STAFF);
    }
}
