<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiKategori;
use App\Models\User;

/**
 * Susunan menu sidebar — SATU sumber, di server.
 *
 * Pemindahan langsung dari `layouts/app.blade.php` (baris 938–1300), sengaja
 * tetap di PHP dan bukan di `AppSidebar.tsx`. Alasannya bukan selera:
 *
 *  - Aturan tampil-per-peran di sidebar memanggil sembilan hal yang hanya ada
 *    di server: `dashboardPenuh()`, `bisaLihatDistribusi()`, `bolehBuatJenis()`,
 *    `Document::tahapNonaktifUntuk()`, `DocumentType::kode()`,
 *    `InformasiKategori::semua()`, `AntreanTugas`, izin spatie, dan
 *    `request()->routeIs()`. Menirunya di React berarti menyalin sembilan
 *    aturan yang bisa menyimpang — persis kesalahan yang sudah pernah dibayar
 *    mahal dengan `STATUS_META` (lihat komentar di {@see Document::STATUS_META}).
 *  - CLAUDE.md §4 melarang menulis ulang daftar izin di TSX.
 *  - Menu yang datang jadi membuat gerbang Fase 3.5 sebuah `diff` terhadap
 *    `C:\baseline-smartpro\menu-per-peran.txt`, bukan adu mata dua peramban.
 *
 * React hanya menggambar pohon yang dikembalikan `untuk()`.
 *
 * Ikon dikirim sebagai nama kelas Bootstrap Icons (`bi-*`) apa adanya —
 * sama seperti `STATUS_META` — dan dipetakan ke lucide di satu tempat saja:
 * `resources/js/components/Ikon.tsx`.
 */
class NavigasiSidebar
{
    /** Jenis dokumen di menu "Dokumen Baru": label panjang + ikon. */
    private const JENIS_BARU = [
        'SOP' => ['Standard Operating Procedure', 'bi-file-earmark-text'],
        'IK' => ['Instruksi Kerja', 'bi-file-earmark-ruled'],
        'SP' => ['Standar Parameter', 'bi-sliders2'],
        'JSA' => ['Job Safety Analysis', 'bi-shield-exclamation'],
        'FK' => ['Formulir Kerja (unggah berkas)', 'bi-ui-checks-grid'],
        'PX' => ['Prosedur External (unggah berkas)', 'bi-box-arrow-in-down'],
    ];

    /**
     * Ikon jenis di submenu "Status Dokumen". Sengaja BERBEDA dari
     * `DocumentType::RUPA` (kartu & donat): di sini ukurannya 13px, jadi glif
     * yang lebih sederhana lebih terbaca.
     */
    private const JENIS_STATUS = [
        'SOP' => 'bi-file-earmark-text',
        'IK' => 'bi-file-earmark-ruled',
        'SP' => 'bi-sliders2',
        'JSA' => 'bi-shield-exclamation',
        'FK' => 'bi-ui-checks-grid',
        'PX' => 'bi-box-arrow-in-down',
    ];

    /**
     * Ikon khas per departemen — disamakan dengan lambang resmi 7 departemen
     * PT PPA. Bootstrap Icons tak punya ikon pabrik, jadi PLANT memakai
     * gedung-bergerigi yang paling mendekati.
     */
    private const IKON_DEPT = [
        'PRODUKSI' => 'bi-truck',
        'ENGINEERING' => 'bi-gear-fill',
        'PLANT' => 'bi-building-fill-gear',
        'SHE' => 'bi-shield-fill-check',
        'HCGA' => 'bi-people-fill',
        'FAW-SCM' => 'bi-share-fill',
        'ICTMD' => 'bi-hdd-network-fill',
    ];

    public function __construct(private AntreanTugas $antrean) {}

    /**
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     *         Daftar bagian sidebar; `label` null = bagian tanpa judul.
     */
    public function untuk(User $user): array
    {
        $antrean = $this->antrean->untuk($user);
        $jml = fn (string $kunci): int => $antrean[$kunci]['jumlah'] ?? 0;

        $jenisAktif = DocumentType::kode();
        $canViewAll = $user->can('document.view_all');
        // Semua KECUALI Non-Staff. Dulu tiga izin di-OR yang kebetulan berjumlah
        // sama; sejak `document.request_revision` pindah ke GL/MD, rumus lama
        // menjatuhkan SH/DH dari daftar.
        $dashboardPenuh = $user->dashboardPenuh();

        $bagian = [];
        $bagian[] = ['label' => null, 'items' => [
            $this->item('Dashboard', 'bi-speedometer2', route('dashboard'), $this->aktif('dashboard')),
        ]];

        $bagian[] = ['label' => 'Dokumen', 'items' => array_values(array_filter([
            $user->can('document.create') ? $this->dokumenBaru($user, $jenisAktif) : null,
            $user->can('document.create')
                ? $this->item('Dokumen Revisi', 'bi-arrow-counterclockwise', route('documents.revisions'), $this->aktif('documents.revisions'), $jml('revisi'))
                : null,
            // Status Dokumen: HANYA untuk GL (pembuat) & Non-Staff (read-only).
            // SH/DH/PJO memakai "Status Dokumen Staff" agar tidak dobel.
            ! $user->can('document.review') && ! $canViewAll
                ? $this->statusDokumen($user, $jenisAktif)
                : null,
            $dashboardPenuh
                ? $this->dokumenBerlaku($user, $jml)
                : $this->item('Dokumen Berlaku', 'bi-folder-check', route('documents.published'), $this->aktif('documents.published')),
            $dashboardPenuh ? $this->logDokumen($jml) : null,
            // Riwayat Pekerjaan JSA — cerminan web atas checklist yang diisi
            // lapangan dari HP. Terbuka bagi SEMUA peran aktif; lingkup
            // departemennya dibatasi di JobExecutionController::index().
            $this->item('Riwayat Pekerjaan', 'bi-person-check', route('job-executions.index'), $this->aktif('job-executions.*')),
        ]))];

        $peninjauan = array_values(array_filter([
            // Polanya sengaja TIDAK `review.*` — itu ikut menyorot halaman MD,
            // sehingga bagi Admin (pemegang kedua izin) dua menu tampak aktif.
            $user->can('review-access')
                ? $this->item('Tinjau Dokumen', 'bi-clipboard-check', route('review.index'), $this->aktif('review.index', 'review.show'), $jml('tinjau'))
                : null,
            $user->can('document.review_md')
                ? $this->item('Tinjau Penulisan', 'bi-spellcheck', route('review.md'), $this->aktif('review.md*'), $jml('tinjau_md'))
                : null,
            $user->can('document.review') || $canViewAll ? $this->statusStaff($canViewAll) : null,
            $user->can('document.approve')
                ? $this->item('Persetujuan Saya', 'bi-patch-check', route('approvals.index'), $this->aktif('approvals.*'), $jml('setujui'))
                : null,
        ]));

        if ($peninjauan !== []) {
            // Judul bagian hanya muncul bila salah satu peran peninjau dipegang;
            // "Status Dokumen Staff" & "Persetujuan Saya" sendiri tidak
            // memunculkannya di Blade lama, jadi ditiru persis.
            $adaJudul = $user->can('review-access') || $user->can('document.review_md');
            $bagian[] = ['label' => $adaJudul ? 'Peninjauan' : null, 'items' => $peninjauan];
        }

        $pengaturan = array_values(array_filter([
            $user->can('user.approve_registration')
                ? $this->item('Persetujuan Akun', 'bi-person-check', route('users.pending'), $this->aktif('users.pending'), $jml('akun'))
                : null,
            $user->can('user.manage')
                ? $this->item('Manajemen User', 'bi-people', route('users.index'), $this->aktif('users.index', 'users.create'))
                : null,
            $user->can('user.manage')
                ? $this->item('Manajemen Akses', 'bi-diagram-3', route('akses.index'), $this->aktif('akses.index'))
                : null,
            $user->can('user.manage')
                ? $this->item('Penomoran Dokumen', 'bi-hash', route('pengaturan.penomoran'), $this->aktif('pengaturan.penomoran'))
                : null,
            $user->can('user.manage')
                ? $this->item('Master Data', 'bi-diagram-2', route('pengaturan.master'), $this->aktif('pengaturan.master'))
                : null,
            $user->can('user.manage')
                ? $this->item('Konfigurasi Sistem', 'bi-sliders', route('pengaturan.sistem'), $this->aktif('pengaturan.sistem'))
                : null,
            $user->can('audit.view')
                ? $this->item('Audit Log', 'bi-shield-lock', route('audit.index'), $this->aktif('audit.index'))
                : null,
        ]));

        // Bagiannya ber-@canany atas KETIGA izin, bukan `user.manage` saja,
        // supaya GL — yang memegang user.approve_registration & audit.view —
        // tidak kehilangan dua menunya.
        if ($pengaturan !== []) {
            $bagian[] = ['label' => 'Pengaturan Sistem', 'items' => $pengaturan];
        }

        $bagian[] = ['label' => 'Informasi', 'items' => [
            $this->informasi(),
            $this->item('Informasi Akun', 'bi-person-badge', route('account.info'), $this->aktif('account.info')),
        ]];

        return $bagian;
    }

    /**
     * Dokumen Baru — dropdown jenis. FK & PX ada di daftar yang SAMA: keduanya
     * jenis dokumen penuh, hanya cara mengisinya yang berbeda (unggah berkas).
     *
     * @param  list<string>  $jenisAktif
     * @return array<string, mixed>
     */
    private function dokumenBaru(User $user, array $jenisAktif): array
    {
        $anak = [];

        foreach (self::JENIS_BARU as $kode => [$judul, $ikon]) {
            // Jenis NONAKTIF (Master Data) dilewati sama sekali, bukan
            // ditampilkan terkunci: "terkunci" di menu ini sudah punya arti lain
            // — di luar profil akses, mintalah ke Admin. Jenis yang dimatikan
            // tak bisa diminta siapa pun.
            if (! in_array($kode, $jenisAktif, true)) {
                continue;
            }

            $aktif = request()->routeIs('documents.create')
                && strtoupper((string) request('type', 'SOP')) === $kode;

            // Jenis di luar profil akses: tanpa href, mustahil diklik MAUPUN
            // di-Tab. Menu induknya tetap tampil meski SELURUH jenisnya mati —
            // pengguna harus melihat fiturnya ada dan tahu harus meminta akses.
            $anak[] = $user->bolehBuatJenis($kode)
                ? $this->item($kode, $ikon, route('documents.create', ['type' => $kode]), $aktif, 0, $judul)
                : $this->terkunci($kode, $ikon, "Tidak berwenang menyusun {$judul} — hubungi Admin");
        }

        return $this->grup('Dokumen Baru', 'bi-file-earmark-plus', $anak, $this->aktif('documents.create'));
    }

    /**
     * @param  list<string>  $jenisAktif
     * @return array<string, mixed>
     */
    private function statusDokumen(User $user, array $jenisAktif): array
    {
        // GL: dua submenu — "Dokumen Departemen" (se-dept, read-only) &
        // "Dokumen Saya" (buatannya, bisa hapus draft).
        if ($user->can('document.create')) {
            return $this->grup('Status Dokumen', 'bi-list-check', [
                $this->item('Dokumen Departemen', 'bi-building', route('documents.staffStatus'), $this->aktif('documents.staffStatus')),
                $this->item('Dokumen Saya', 'bi-person-lines-fill', route('documents.index'), $this->aktif('documents.index')),
            ], $this->aktif('documents.index', 'documents.staffStatus'));
        }

        // Non-Staff: read-only se-departemen, disaring per jenis.
        $anak = [$this->item('Semua', 'bi-grid', route('documents.index'), request()->routeIs('documents.index') && ! request('type'))];

        foreach (array_intersect_key(self::JENIS_STATUS, array_flip($jenisAktif)) as $kode => $ikon) {
            $anak[] = $this->item($kode, $ikon, route('documents.index', ['type' => $kode]), strtoupper((string) request('type', '')) === $kode);
        }

        return $this->grup('Status Dokumen', 'bi-list-check', $anak, $this->aktif('documents.index'));
    }

    /**
     * Dokumen Berlaku + submenu Tidak Berlaku bagi SH/DH/PJO/Admin; GL ikut
     * melihat read-only.
     *
     * @param  callable(string): int  $jml
     * @return array<string, mixed>
     */
    private function dokumenBerlaku(User $user, callable $jml): array
    {
        $anak = [
            $this->item('Berlaku', 'bi-folder-check', route('documents.published'), $this->aktif('documents.published')),
            $this->item('Tidak Berlaku', 'bi-slash-circle', route('documents.obsolete'), $this->aktif('documents.obsolete')),
        ];

        // Menggantungkan Distribusi pada izin revisi (yang sejak PLAN-REVISI-v6
        // Fase C milik GL/MD) akan mencabut menunya justru dari pihak yang
        // menegur cakupan rendah. Aturannya di User::bisaLihatDistribusi().
        if ($user->bisaLihatDistribusi()) {
            $anak[] = $this->item('Distribusi', 'bi-broadcast', route('documents.distribution'), $this->aktif('documents.distribution'));
        }

        // SATU antrean untuk ketiga tahap nonaktif; tampil hanya bagi yang
        // berwenang di salah satunya. Angkanya datang dari AntreanTugas —
        // sidebar tak boleh punya query sendiri.
        if (Document::tahapNonaktifUntuk($user) !== []) {
            $anak[] = $this->item('Persetujuan Nonaktif', 'bi-slash-circle', route('nonaktif.index'), $this->aktif('nonaktif.*'), $jml('nonaktif'));
        }

        return $this->grup(
            'Dokumen Berlaku',
            'bi-folder-check',
            $anak,
            $this->aktif('documents.published', 'documents.obsolete', 'documents.distribution', 'nonaktif.*'),
            // Titik penanda menu INDUK: ada pekerjaan di salah satu sub-menunya.
            // Angkanya tetap milik sub-menu — induk cukup memberi tahu bahwa ada
            // sesuatu di balik menu yang tertutup.
            $jml('nonaktif') > 0,
        );
    }

    /**
     * @param  callable(string): int  $jml
     * @return array<string, mixed>
     */
    private function logDokumen(callable $jml): array
    {
        return $this->grup('Log Dokumen', 'bi-journal-text', [
            $this->item('Masukan Lapangan', 'bi-chat-left-dots', route('log.masukan'), $this->aktif('log.masukan'), $jml('masukan')),
            $this->item('Log Pesan', 'bi-card-list', route('log.pesan'), $this->aktif('log.pesan')),
        ], $this->aktif('log.masukan', 'log.pesan'), $jml('masukan') > 0);
    }

    /** @return array<string, mixed> */
    private function statusStaff(bool $canViewAll): array
    {
        // SH/DH melihat departemennya sendiri; PJO/Admin memilih dari 7 dept.
        if (! $canViewAll) {
            return $this->item('Status Dokumen Staff', 'bi-people-fill', route('documents.staffStatus'), $this->aktif('documents.staffStatus'));
        }

        $anak = Department::orderBy('code')->get(['id', 'code', 'name'])
            ->map(fn (Department $d) => $this->item(
                $d->code,
                self::IKON_DEPT[strtoupper($d->code)] ?? 'bi-building',
                route('documents.staffStatus', ['department_id' => $d->id]),
                request('department_id') == $d->id,
            ))->all();

        return $this->grup('Status Dokumen Staff', 'bi-people-fill', $anak, $this->aktif('documents.staffStatus'));
    }

    /**
     * Menu INFORMASI. Terbuka bagi SEMUA akun aktif — tanpa izin sama sekali,
     * karena itu memang intinya: kebijakan & poster ditujukan justru kepada
     * Non-Staff.
     *
     * @return array<string, mixed>
     */
    private function informasi(): array
    {
        $bawaan = Informasi::kategoriBawaan();
        $anak = [];

        foreach (InformasiKategori::semua() as $slug => $kat) {
            // Kategori NONAKTIF tetap tampil dalam keadaan mati: dokumennya
            // masih ada, dan orang perlu melihat bahwa kategorinya sengaja
            // ditutup, bukan mendapati menunya lenyap.
            $anak[] = $kat->is_active
                ? $this->item(
                    $kat->nama,
                    $kat->ikon,
                    route('informasi.index', ['kategori' => $slug]),
                    request()->routeIs('informasi.index') && request('kategori', $bawaan) === $slug,
                )
                : $this->terkunci($kat->nama, $kat->ikon, "{$kat->nama} sedang ditutup Admin");
        }

        return $this->grup('Informasi', 'bi-info-square', $anak, $this->aktif('informasi.*'));
    }

    /** Cocokkan rute aktif — padanan `request()->routeIs()` di Blade lama. */
    private function aktif(string ...$pola): bool
    {
        return request()->routeIs(...$pola);
    }

    /** @return array<string, mixed> */
    private function item(string $label, string $icon, string $href, bool $active = false, int $badge = 0, ?string $title = null): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'href' => $href,
            'active' => $active,
            // Nol bukan kabar, cuma bising: menu tanpa tugas tak menampilkan
            // angka. Dibuang di sini supaya React tak perlu tahu aturannya.
            'badge' => $badge > 0 ? $badge : null,
            'title' => $title,
        ];
    }

    /** @return array<string, mixed> */
    private function terkunci(string $label, string $icon, string $title): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'href' => null,
            'active' => false,
            'badge' => null,
            'title' => $title,
            'locked' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function grup(string $label, string $icon, array $items, bool $active = false, bool $dot = false): array
    {
        return [
            'label' => $label,
            'icon' => $icon,
            'href' => null,
            'active' => $active,
            'badge' => null,
            'title' => null,
            'dot' => $dot,
            'items' => $items,
        ];
    }
}
