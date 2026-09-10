<?php

namespace Tests;

use App\Models\AccessProfile;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\Print\PdfStream;
use Database\Seeders\RolePermissionSeeder as Roles;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * NRP pengguna SUNGGUHAN yang dipakai aktor uji — ketetapan pemilik.
     *
     * Kelima NRP contoh (`GL-0001`, `SH-0001`, `DH-0001`, `PJO-0001`,
     * `STF-0001`) sudah pensiun dan tak akan kembali (PLAN-AKSES-v8 keputusan
     * I). Penggantinya BUKAN akun rekaan, melainkan akun yang benar-benar ada di
     * web — supaya test berjalan di atas keadaan yang sama dengan yang dipakai
     * pengguna, apa adanya.
     *
     * Dipusatkan di sini, bukan disebar ke 54 berkas test: kalau seseorang
     * pindah atau keluar, yang perlu diperbarui hanya tabel ini.
     *
     * DUA HAL YANG PERLU DIKETAHUI, bukan cacat kode melainkan keadaan data:
     *
     *   1. TAK ADA Departemen Head di ICTMD. Satu-satunya DH aktif ada di
     *      ENGINEERING, jadi `aktorDh()` berdepartemen lain daripada
     *      `aktorGl()`/`aktorSh()`. Test yang menuntut DH SEDEPARTEMEN dengan
     *      pembuatnya karena itu tak bisa hijau sampai ada DH ICTMD.
     *   2. TAK ADA GL kedua di ICTMD. `aktorGl(ke: 2)` — rekan sedepartemen
     *      untuk pembuat tambahan & masukan sejawat — memakai GL SHE, jadi
     *      aturan "harus sedepartemen" tak terpenuhi olehnya.
     *
     * Konsekuensi yang melekat pada pilihan ini: akun sungguhan MEMIKUL beban
     * dokumen sungguhan. Begitu salah satunya menerima penugasan di web, test
     * yang mengandaikan mejanya kosong bisa berubah merah tanpa satu baris kode
     * pun berubah. `peninjauBersih()` di bawah ada justru untuk itu.
     */
    private const NRP_AKTOR = [
        Roles::ROLE_GROUP_LEADER => ['ICTMD' => '18064116', 'SHE' => '23000651', 'PLANT' => '22004759'],
        Roles::ROLE_SECTION_HEAD => ['ICTMD' => '17021841', 'SHE' => '18043829', 'PLANT' => '18043807'],
        Roles::ROLE_DEPARTEMEN_HEAD => ['*' => '17092728'],
        Roles::ROLE_PIMPINAN => ['*' => '16071367'],
        Roles::ROLE_STAFF => ['ICTMD' => '20260219'],
        // Dua akun BERSAMA. NRP-nya berbentuk rekaan tapi keduanya benar-benar
        // ada di produksi (`AdminUserSeeder`): Admin IT & Management
        // Development memang tak punya NRP karyawan karena mereka jabatan,
        // bukan orang. Keduanya ber-`jabatan` NULL — itulah kenapa gerbang yang
        // membaca jabatan harus memeriksa IZIN lebih dulu.
        Roles::ROLE_ADMIN => ['*' => 'ADM-0001'],
        Roles::ROLE_MD => ['*' => 'MD-0001'],
    ];

    /**
     * GL KEDUA sedepartemen — lihat catatan 2 di atas.
     *
     * SATU-SATUNYA akun rekaan yang tersisa di seluruh suite. `ADM-0001` dan
     * `MD-0001` BUKAN akun rekaan meski NRP-nya berbentuk begitu: keduanya akun
     * BERSAMA (Admin IT & Management Development) yang memang tak punya NRP
     * karyawan, dan keduanya benar-benar ada di produksi.
     *
     * `GLSHE-0001` sebaliknya hanya ada di `smartpro_uji` & `smartpro_fresh`,
     * TIDAK di produksi. Penggantinya sudah tersedia di dunia nyata sejak impor
     * 99 akun — mis. `18125648` (SLAMET HUDA FIRMANSYAH, GL ICTMD kedua) — tapi
     * orang itu belum ada di `smartpro_uji`, dan menambah baris ke basis data
     * test adalah keputusan pemilik, bukan keputusan berkas ini.
     *
     * ponytail: akun rekaan terakhir; ganti dengan `18125648` (ICTMD) begitu ia
     * ditambahkan ke `smartpro_uji` — sesudah itu catatan 2 di atas ikut gugur,
     * sebab `aktorGl(ke: 2)` akhirnya benar-benar SEDEPARTEMEN.
     */
    private const NRP_AKTOR_KEDUA = [
        Roles::ROLE_GROUP_LEADER => ['ICTMD' => 'GLSHE-0001', 'SHE' => 'GLSHE-0001'],
    ];

    /**
     * Aktor uji, disebut dari PERANNYA (PLAN-AKSES-v8 Fase 6).
     *
     * Menggantikan 336 pemanggilan `User::where('nrp', 'GL-0001')->firstOrFail()`
     * di 54 berkas. Yang berubah cuma SATU hal: NRP-nya kini milik pengguna yang
     * sungguh ada, bukan akun contoh yang sudah pensiun. Test menyebut peran yang
     * ia butuhkan; tabel di atas yang memetakannya ke orang.
     */
    protected function aktorUji(string $role, ?string $jabatan, ?string $deptCode, int $ke = 1): User
    {
        $peta = $ke > 1 ? (self::NRP_AKTOR_KEDUA[$role] ?? []) : self::NRP_AKTOR[$role];
        $nrp = $peta[$deptCode ?? '*'] ?? $peta['*'] ?? null;

        $this->assertNotNull($nrp, "Tak ada NRP aktor untuk peran {$role} di departemen ".($deptCode ?? '-')." (ke-{$ke}).");

        return User::where('nrp', $nrp)->firstOrFail();
    }

    /**
     * Group Leader — satu-satunya pembuat dokumen (CLAUDE.md §6).
     *
     * `$ke` memilih GL KEBERAPA di departemen itu; dibutuhkan aturan yang
     * berbicara tentang DUA GL sedepartemen (pembuat tambahan CLAUDE.md §6,
     * masukan sejawat PLAN-AKSES-v8 Fase 2).
     */
    protected function aktorGl(string $dept = 'ICTMD', int $ke = 1): User
    {
        return $this->aktorUji(Roles::ROLE_GROUP_LEADER, User::JABATAN_GROUP_LEADER, $dept, $ke);
    }

    /** Section Head — peninjau. */
    protected function aktorSh(string $dept = 'ICTMD'): User
    {
        return $this->aktorUji(Roles::ROLE_SECTION_HEAD, User::JABATAN_SECTION_HEAD, $dept);
    }

    /** Departemen Head — berwewenang sama dengan SH (CLAUDE.md §6). */
    protected function aktorDh(string $dept = 'ICTMD'): User
    {
        return $this->aktorUji(Roles::ROLE_DEPARTEMEN_HEAD, User::JABATAN_DEPARTEMEN_HEAD, $dept);
    }

    /** Pimpinan/PJO — penyetuju, dan sengaja TANPA departemen (CLAUDE.md §6). */
    protected function aktorPjo(): User
    {
        return $this->aktorUji(Roles::ROLE_PIMPINAN, User::JABATAN_PIMPINAN, null);
    }

    /** Non-Staff — kunci internalnya tetap `staff` (CLAUDE.md §6, jangan di-rename). */
    protected function aktorNonStaff(string $dept = 'ICTMD'): User
    {
        return $this->aktorUji(Roles::ROLE_STAFF, User::JABATAN_STAFF, $dept);
    }

    /**
     * Management Development — peninjau KEDUA (sistematika penulisan).
     *
     * `jabatan` NULL dan bukan kelalaian: MD adalah ROLE, bukan jenjang. Ia
     * ber-`department_id` ICTMD tapi berwenang lintas tujuh departemen lewat
     * `document.view_all` — perpaduan yang gampang membuat gerbang berbasis
     * jabatan salah menempatkannya.
     */
    protected function aktorMd(): User
    {
        return $this->aktorUji(Roles::ROLE_MD, null, null);
    }

    /** Admin IT — full akses, `jabatan` NULL (setiap aksinya tercatat audit). */
    protected function aktorAdmin(): User
    {
        return $this->aktorUji(Roles::ROLE_ADMIN, null, null);
    }

    /**
     * Peninjau BARU yang bersih, tiruan akun contoh yang diberikan.
     *
     * KENAPA ADA: suite ini berjalan di atas basis data PENGEMBANGAN (lihat
     * `DatabaseTransactions`, bukan `RefreshDatabase` — data nyata pemilik tak
     * boleh terhapus). Akun contoh seperti `SH-0001` karena itu MEMBAWA beban
     * dokumen sungguhan: begitu pemilik menugaskan 3 dokumen kepadanya, test
     * yang mengandaikan mejanya kosong langsung merah — dan yang merah bukan
     * kodenya, melainkan andaiannya.
     *
     * Kegagalannya juga menyesatkan: `tolakBilaPeninjauPenuh()` menolak
     * penugasan baru, sehingga test tentang "Kirim" gagal dengan pesan tentang
     * status dokumen, bukan tentang beban peninjau.
     *
     * Peninjau di sini lahir di dalam transaksi test dan ikut di-rollback, jadi
     * bebannya SELALU nol berapa pun isi basis data. Jabatan, departemen, dan
     * peran DISALIN dari akun contoh (bukan ditulis ulang) supaya ia tetap lolos
     * DocumentParticipantResolver tanpa menduplikasi pengetahuan seeder — kalau
     * aturan perannya berubah, tiruan ini ikut berubah sendiri.
     */
    protected function peninjauBersih(User $contoh): User
    {
        $tanda = Str::upper(Str::random(6));

        $baru = User::create([
            'name' => 'Peninjau Uji '.$tanda,
            'nrp' => 'UJI-'.$tanda,
            'username' => 'uji.'.Str::lower($tanda),
            'password' => Hash::make('password'),
            'jabatan' => $contoh->jabatan,
            'department_id' => $contoh->department_id,
            'status' => 'active',
        ]);
        $baru->forceFill(['email_verified_at' => now()])->save();
        $baru->syncRoles($contoh->roles);

        return $baru->fresh();
    }

    /**
     * Beri $user profil akses berisi SELURUH jenis (PLAN-AKSES-v7 Fase 4).
     *
     * KENAPA ADA: sejak profil akses berlaku, izin `document.create` saja tak
     * lagi membuka pintu — GL tanpa profil 403 di `documents.create`,
     * `documents.store`, dan `documents.arsip.store`. Test yang pokok
     * bahasannya BUKAN wewenang (arsip, catatan revisi, jenis unggahan) karena
     * itu perlu satu baris ini di depan, dan alangkah rapuhnya kalau tiga
     * berkas menuliskan sendiri-sendiri profil apa yang "cukup".
     *
     * Profilnya `firstOrCreate` bernama tetap: dipanggil berkali-kali dalam
     * satu test tetap menghasilkan satu baris, dan ikut di-rollback bersama
     * transaksi test — basis data pengembangan tak ketambahan apa pun.
     *
     * Untuk MENGUJI wewenangnya sendiri, jangan pakai ini: ProfilAksesTest
     * membuat profilnya sendiri agar batasnya terlihat di berkas itu.
     */
    protected function berprofilPenuh(User $user): User
    {
        $user->accessProfile()->associate(AccessProfile::firstOrCreate(
            ['nama' => 'Uji — Akses Penuh'],
            ['jenis_dibolehkan' => DocumentType::kode(), 'boleh_review_jsa' => false],
        ))->save();

        return $user->fresh();
    }

    /**
     * Props sebuah halaman Inertia — pengganti `viewData('nama')`.
     *
     * KENAPA ADA: sejak Fase 4–13 halaman pindah ke Inertia, `viewData('tren')`
     * dan kawan-kawannya mengembalikan `null` — semua props kini duduk di dalam
     * SATU variabel view bernama `page`. Test yang menguji ANGKA (bukan rupa)
     * tak perlu ikut berubah bentuk karena itu: ia cukup mengganti pembacanya.
     *
     * Nilainya sudah melewati JSON, jadi model Eloquent sampai di sini sebagai
     * array asosiatif — `collect($props['x'])->pluck('y.id')` tetap bekerja.
     *
     * @return array<string, mixed>
     */
    protected function propsInertia(\Illuminate\Testing\TestResponse $response): array
    {
        $page = $response->viewData('page');

        $this->assertIsArray($page, 'Respons ini bukan halaman Inertia (tak ada variabel view `page`).');

        return $page['props'];
    }

    /**
     * Menu sidebar sebuah halaman Inertia, diratakan jadi daftar item.
     *
     * KENAPA ADA: sidebar dulu digambar `layouts/app.blade.php`, sehingga menu
     * bisa diperiksa dengan `assertSee('Audit Log')` pada HTML halaman apa pun.
     * Sejak Fase 3 ia datang sebagai props `navigation` (lihat NavigasiSidebar),
     * dan sejak halaman-halamannya jadi Inertia, HTML-nya cuma satu atribut
     * JSON — di mana URL ter-escape (`http:\/\/…`) sehingga `assertSee(route())`
     * gagal dengan alasan yang tak ada hubungannya dengan menunya.
     *
     * Yang dikembalikan tiap item: `label`, `href`, `locked`, `dot`, dan
     * `induk` (nama menu induknya, `null` untuk item tingkat atas) — cukup untuk
     * semua yang dulu diperiksa dari markup: ada/tidaknya menu, tertaut/tidaknya,
     * mati/tidaknya jenis di luar profil akses, dan titik "ada pekerjaan di
     * salah satu sub-menu" (dulu `class="pp-titik"`).
     *
     * @return list<array{label: string, href: ?string, locked: bool, dot: bool, induk: ?string, bagian: ?string}>
     */
    /**
     * Seksi schema yang sedang digambar wizard, dikunci `key`-nya.
     *
     * KENAPA ADA: bab & kolom wizard dulu bisa diperiksa dengan
     * `assertSee('V. FLOWCHART')` pada HTML `documents/edit`. Sejak Fase 9
     * halaman itu Inertia, dan schema-lah yang dikirim sebagai props — form
     * React membacanya, tak pernah menyalinnya (pakem P1). Jadi memeriksa
     * schema di props = memeriksa apa yang benar-benar digambar, satu tingkat
     * lebih dekat ke sumbernya daripada memeriksa markup.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function seksiWizard(\Illuminate\Testing\TestResponse $response): array
    {
        $props = $this->propsInertia($response);
        $langkah = collect($props['schema']['steps'] ?? [])->firstWhere('step', $props['currentStep']);

        return collect($langkah['sections'] ?? [])->keyBy('key')->all();
    }

    protected function menuSidebar(\Illuminate\Testing\TestResponse $response): array
    {
        $rata = [];

        foreach ($this->propsInertia($response)['navigation'] as $bagian) {
            foreach ($bagian['items'] as $item) {
                $rata[] = [
                    'label' => $item['label'],
                    'href' => $item['href'] ?? null,
                    'locked' => (bool) ($item['locked'] ?? false),
                    'dot' => (bool) ($item['dot'] ?? false),
                    'induk' => null,
                    'bagian' => $bagian['label'],
                ];

                foreach ($item['items'] ?? [] as $anak) {
                    $rata[] = [
                        'label' => $anak['label'],
                        'href' => $anak['href'] ?? null,
                        'locked' => (bool) ($anak['locked'] ?? false),
                        'dot' => (bool) ($anak['dot'] ?? false),
                        'induk' => $item['label'],
                        'bagian' => $bagian['label'],
                    ];
                }
            }
        }

        return $rata;
    }

    /**
     * Stream isi PDF yang sudah didekompres — satu per objek stream.
     *
     * Isinya PINDAH ke {@see PdfStream} supaya test dan
     * kode produksi membaca PDF dengan pembongkar yang SAMA. Dulu keduanya
     * punya salinan sendiri: test sudah memakai batas `/Length`, sementara
     * PdfRenderer masih memakai regex `stream…endstream` yang bisa memakan
     * byte data — dan justru di sanalah satu halaman penuh hilang tanpa suara
     * sehingga mesin paginasi menyimpulkan titik potong yang salah. Alasan
     * lengkapnya ada di docblock kelas itu.
     *
     * @return string[] isi stream, urut sesuai kemunculannya di berkas
     */
    protected function pdfStreams(string $raw): array
    {
        return PdfStream::semua($raw);
    }
}
