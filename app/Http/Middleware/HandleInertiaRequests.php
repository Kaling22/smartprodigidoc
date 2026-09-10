<?php

namespace App\Http\Middleware;

use App\Models\Document;
use App\Models\User;
use App\Services\NavigasiSidebar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

/**
 * Kontrak data global untuk seluruh halaman Inertia.
 *
 * Menggantikan apa yang dulu diambil `layouts/app.blade.php` langsung dari
 * `auth()` di dalam view. Tiga di antaranya BUKAN kemudahan melainkan pakem
 * migrasi, dan alasannya sudah mahal dibayar sekali:
 *
 *  - `statusMeta`/`statusLabels` dibagikan dari sini supaya tak ada satu pun
 *    warna, label, atau ikon status yang diketik ulang di TSX. Peta ini dulu
 *    tersalin di EMPAT berkas Blade dan menyimpang — `verifikasi_md` hilang di
 *    semuanya sehingga tampil abu-abu seperti Draft (lihat komentar di
 *    {@see Document::STATUS_META}).
 *
 *  - `auth.can` hanya untuk MENYEMBUNYIKAN tombol. Otorisasi tetap di server
 *    lewat middleware `can:` di routes/web.php dan Policy/Gate. Menyembunyikan
 *    tombol bukan otorisasi.
 *
 *  - `notifications` menyalin perilaku lonceng lama persis: 8 notifikasi
 *    terakhir + jumlah yang belum dibaca.
 */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nrp' => $user->nrp,
                    'jabatan' => $user->jabatan,
                    // Kunci mentah `jabatan` tak pernah dicetak ke layar —
                    // yang tampil selalu label ini (CLAUDE.md §6, dijaga
                    // tests/Feature/LabelJabatanTest.php).
                    'jabatan_label' => $user->jabatanLabel(),
                    'jabatan_short' => $user->jabatanShort(),
                    'department_id' => $user->department_id,
                    'photo_url' => $user->photoUrl(),
                ] : null,
                'department' => $user?->department?->only(['id', 'code', 'name']),
                'roles' => $user?->getRoleNames() ?? [],
                // Closure, BUKAN nilai jadi: middleware ini dipasang di grup
                // `web` sehingga `share()` dipanggil pada SETIAP permintaan —
                // termasuk halaman Blade lama yang tak akan pernah membacanya.
                // Sebagai nilai jadi, tiga kueri di bawah ikut jalan di sana.
                'can' => fn () => $this->izin($user),
            ],
            'notifications' => [
                'unread' => fn () => $user?->unreadNotifications()->count() ?? 0,
                'items' => fn () => $user
                    ? $user->notifications()->take(8)->get()->map(fn ($n) => [
                        'id' => $n->id,
                        'message' => $n->data['message'] ?? '',
                        'icon' => $n->data['icon'] ?? 'bi-bell',
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at,
                    ])
                    : [],
            ],
            'flash' => [
                // `status` ikut dibaca karena Blade lama memperlakukannya sama
                // dengan `success` (layouts/app.blade.php:1379).
                'success' => fn () => $request->session()->get('success') ?? $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Susunan sidebar dibangun di server (lihat NavigasiSidebar):
            // sembilan aturan tampil-per-peran di dalamnya tak boleh punya
            // salinan kedua di TSX. Closure, sebab menu memicu query antrean.
            'navigation' => fn () => $user ? app(NavigasiSidebar::class)->untuk($user) : [],
            /*
             * Modal "tugas menunggu" — muncul SEKALI tepat sesudah login.
             *
             * Dulu `@if (session('antrean_awal') && $antrean !== [])` di
             * `layouts/app.blade.php:1510`. Ia dibagikan dari SINI, bukan dari
             * DashboardController, karena penandanya flash yang bertahan tepat
             * satu request: halaman apa pun yang kebetulan jadi tujuan sesudah
             * login harus bisa menampilkannya, dan `LoginController` tak berjanji
             * tujuannya selalu dashboard.
             *
             * `null` bila tak ada flash-nya ATAU antreannya kosong — modal yang
             * tak membawa kabar hanya melatih orang menutupnya tanpa membaca.
             */
            'antreanAwal' => fn () => $this->antreanAwal($request, $user),
            'statusMeta' => Document::STATUS_META,
            'statusLabels' => Document::STATUS_LABELS,
        ]);
    }

    /**
     * Isi modal antrean pasca-login, atau `null`.
     *
     * Bentuknya diratakan jadi `label`/`icon`/`jumlah`/`url` — nama rutenya
     * diterjemahkan di sini, bukan di TSX, supaya tak ada satu pun nama rute
     * yang perlu diketahui klien.
     *
     * @return array<int, array{label: string, icon: string, jumlah: int, url: string}>|null
     */
    private function antreanAwal(Request $request, ?User $user): ?array
    {
        if (! $user || ! $request->session()->get('antrean_awal')) {
            return null;
        }

        $antrean = app(\App\Services\AntreanTugas::class)->untuk($user);

        return $antrean === [] ? null : array_values(array_map(fn (array $t) => [
            'label' => $t['label'],
            'icon' => $t['icon'],
            'jumlah' => $t['jumlah'],
            'url' => route($t['route']),
        ], $antrean));
    }

    /**
     * Seluruh izin + gate, dibaca sekali per permintaan.
     *
     * Daftarnya diambil dari tabel `permissions` (20 baris) supaya izin baru
     * ikut terkirim tanpa menyunting berkas ini; dua gate ditambahkan manual
     * karena gate memang tak tercatat di tabel itu.
     *
     * @return array<string, bool>
     */
    private function izin(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $izin = \Spatie\Permission\Models\Permission::query()
            ->pluck('name')
            ->mapWithKeys(fn (string $nama) => [$nama => $user->can($nama)])
            ->all();

        foreach (['review-access', 'beri-masukan'] as $gate) {
            $izin[$gate] = Gate::forUser($user)->allows($gate);
        }

        return $izin;
    }
}
