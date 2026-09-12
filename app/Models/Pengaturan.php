<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Setelan sistem, satu baris satu kunci (PLAN-AKSES-v8 Fase 5a).
 *
 * Menampung hal-hal yang dulu jadi konstanta di kode dan ternyata perlu bisa
 * diubah Admin tanpa menyentuh berkas: prefix penomoran site, nama site, dan
 * setelan AI (Fase 5c).
 *
 * TIGA aturan yang membuatnya aman dipakai di jalur sepanas penomoran dokumen:
 *
 * 1. NILAI BAWAAN TINGGAL DI KODE (self::BAWAAN), bukan di-seed ke tabel.
 *    Pemasangan baru, basis data uji, dan sistem yang setelannya belum pernah
 *    disentuh semuanya berperilaku PERSIS seperti sebelum tabel ini ada. Baris
 *    seed akan menghasilkan yang sebaliknya: satu baris hilang, dan sistemnya
 *    berhenti bernomor.
 *
 * 2. KOSONG = BAWAAN, bukan kosong. Admin yang mengosongkan kotak prefix tidak
 *    boleh menghasilkan dokumen bernomor "-SOP-ICTMD-01". Nilai kosong dibaca
 *    sebagai "belum disetel".
 *
 * 3. DI-CACHE SELAMANYA, dibuang saat disimpan. `ambil()` dipanggil sekali per
 *    baris daftar dokumen (badge "Nomor Lama"), jadi tanpa cache satu halaman
 *    berisi 50 dokumen berarti 50 query untuk satu nilai yang sama. Cache-nya
 *    menyimpan STRING KOSONG untuk "tak ada barisnya" — bukan null — sebab
 *    Cache::rememberForever menganggap null sebagai cache miss dan akan
 *    mengulang query-nya tiap kali.
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['kunci', 'nilai'];

    /**
     * Nilai bawaan tiap kunci — sumber kebenaran saat tabelnya masih kosong.
     *
     * `site.prefix` sengaja sama persis dengan DocumentNumberService::PREFIX
     * yang lama, dan `site.nama` dengan potongan judul daftar induk yang lama
     * ("… PPA SITE ADARO INDONESIA"). Keduanya bukan pilihan gaya: itulah yang
     * membuat Fase 5a tidak mengubah satu pun keluaran sampai Admin benar-benar
     * mengubahnya dari layar.
     */
    public const BAWAAN = [
        'site.prefix' => 'PPA-ADRO',
        'site.nama' => 'ADARO INDONESIA',
        // Aktif = dokumen lama (arsip) yang lembar Catatan Revisinya baru
        // disimpan langsung memotong halaman 1-2 berkas asli dan menggantinya
        // dengan Cover + Catatan Revisi hasil cetak sistem (ArsipPenggabung),
        // tanpa mengetik ulang isi dokumen di wizard. Admin bisa mematikannya
        // untuk kembali ke alur lama (ketik ulang di wizard).
        'arsip.gabung_cover_enabled' => '1',
    ];

    /**
     * Ingatan SEUMUR REQUEST, di depan cache.
     *
     * Wajib ada, bukan penghematan yang manis-manis: penyimpan cache aplikasi ini
     * `database` (`CACHE_STORE=database`), dan Illuminate\Cache\DatabaseStore
     * menjalankan SATU SELECT tiap kali `Cache::get` dipanggil. Sementara itu
     * `Document::nomorLuarPola()` memanggil `ambil()` sekali PER BARIS daftar
     * dokumen. Tanpa larik ini, halaman berisi 50 dokumen menambah 50 query
     * untuk satu nilai yang sama — padahal sebelum Fase 5a nilainya konstanta
     * dan berbiaya nol.
     *
     * Isinya dibuang `simpan()`, jadi perubahan tetap langsung terlihat pada
     * request yang menyimpannya.
     *
     * @var array<string, string>
     */
    private static array $ingatan = [];

    private static function kunciCache(string $kunci): string
    {
        return 'pengaturan:'.$kunci;
    }

    /** Nilai setelan, atau bawaannya bila belum pernah disetel/dikosongkan. */
    public static function ambil(string $kunci, ?string $default = null): ?string
    {
        $nilai = self::$ingatan[$kunci] ??= Cache::rememberForever(
            self::kunciCache($kunci),
            fn () => (string) (static::find($kunci)?->nilai ?? '')
        );

        return $nilai === '' ? ($default ?? self::BAWAAN[$kunci] ?? null) : $nilai;
    }

    /** Simpan sebuah setelan, buang cache DAN ingatannya (tanpa itu perubahan tak terlihat). */
    public static function simpan(string $kunci, ?string $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);

        Cache::forget(self::kunciCache($kunci));
        unset(self::$ingatan[$kunci]);
    }

    /**
     * Buang ingatan seumur-proses. Dua pemakai:
     *
     * 1. Test — larik statis hidup lebih lama daripada satu test (tak ikut
     *    di-rollback bersama transaksi basis data), jadi nilai yang ditulis
     *    satu test bisa terbaca test berikutnya kalau tak dibuang di antaranya.
     * 2. Pekerja antrean (`AnalisisAi::handle()`) — proses `queue:work` hidup
     *    lintas BANYAK job, melanggar asumsi "seumur REQUEST" di atas. Tanpa
     *    ini, setelan yang diubah Admin dari Konfigurasi Sistem tak pernah
     *    kepakai sampai pekerja di-restart manual.
     */
    public static function lupakanIngatan(): void
    {
        self::$ingatan = [];
    }

    /**
     * Prefix penomoran site, mis. "PPA-ADRO".
     *
     * Pintasan tersendiri karena TIGA tempat memakainya dan ketiganya harus
     * sepakat: pembangkit nomor (DocumentNumberService), penanda "Nomor Lama"
     * (Document::nomorLuarPola), dan contoh format di layar. Kalau salah satu
     * memakai nilai berbeda, gejalanya adalah dokumen baru yang dicap lama.
     */
    public static function prefix(): string
    {
        return (string) self::ambil('site.prefix');
    }

    /** Nama site untuk judul daftar induk & kop ekspor, mis. "ADARO INDONESIA". */
    public static function namaSite(): string
    {
        return (string) self::ambil('site.nama');
    }

    /**
     * Saklar gabung-PDF dokumen lama (ArsipPenggabung): aktif = potong
     * halaman 1-2 berkas arsip lalu ganti dengan Cover + Catatan Revisi hasil
     * cetak sistem; nonaktif = alur lama, isi dokumen diketik ulang di wizard.
     */
    public static function arsipGabungCoverAktif(): bool
    {
        return self::ambil('arsip.gabung_cover_enabled') === '1';
    }

    /**
     * Setelan RAHASIA — disandikan sebelum tersimpan (PLAN-AKSES-v8 Fase 5c).
     *
     * Disandikan PER NILAI, bukan lewat cast `encrypted` pada kolom `nilai`.
     * Cast berlaku untuk SELURUH baris tabel, sementara `site.prefix` dan
     * `site.nama` sudah tersimpan sebagai teks biasa sejak Fase 5a — memasang
     * cast berarti Laravel mencoba menyandi-balik keduanya dan gagal, lalu
     * sistemnya berhenti bernomor. Yang perlu dirahasiakan hanya kunci API.
     *
     * Gagal disandi-balik → null, bukan lemparan. Kunci lama menjadi tak
     * terbaca bila APP_KEY diputar; itu memang berarti "kunci belum disetel",
     * dan Admin tinggal mengetiknya ulang. Layar tinjau menampilkan pesan
     * "AI sedang dinonaktifkan", bukan halaman 500.
     */
    public static function ambilRahasia(string $kunci): ?string
    {
        $tersimpan = self::ambil($kunci);

        if ($tersimpan === null || $tersimpan === '') {
            return null;
        }

        try {
            return Crypt::decryptString($tersimpan);
        } catch (DecryptException) {
            return null;
        }
    }

    /** Simpan setelan rahasia. Nilai kosong = hapus, BUKAN menyandi string kosong. */
    public static function simpanRahasia(string $kunci, ?string $nilai): void
    {
        self::simpan($kunci, ($nilai === null || $nilai === '')
            ? null
            : Crypt::encryptString($nilai));
    }

    /**
     * Setelan AI yang BERLAKU — satu-satunya sumber (PLAN-AKSES-v8 Fase 5c).
     *
     * Baris tabel menang; `config('services.ai.*')` alias .env jadi CADANGAN.
     * Urutan itu disengaja: sebelum Fase 5c, .env-lah satu-satunya tempat, dan
     * pemasangan yang setelannya belum pernah disentuh dari layar harus tetap
     * berperilaku persis seperti dulu (aturan yang sama dengan §9.2 aturan 1).
     *
     * Dipusatkan di sini karena EMPAT tempat membacanya — binding
     * AppServiceProvider, layar konfigurasi, dan dua controller tinjau. Selama
     * `config('services.ai.enabled')` masih dibaca terpisah di controller,
     * saklar di layar ini menyala tanpa mematikan apa pun: persis jebakan yang
     * sudah kena sekali di `DocumentType::kode()` (§9.3 butir 1).
     *
     * @return array{enabled:bool, provider:?string, model:?string, key:?string,
     *               cadangan_provider:?string, cadangan_model:?string, cadangan_key:?string}
     */
    public static function ai(): array
    {
        $aktif = self::ambil('ai.enabled');
        $penyedia = self::ambil('ai.provider', config('services.ai.provider'));

        return [
            // Belum pernah disetel dari layar → ikut .env, bukan ikut "mati".
            'enabled' => $aktif === null ? (bool) config('services.ai.enabled') : $aktif === '1',
            'provider' => $penyedia,
            'model' => self::ambil('ai.model', config('services.'.$penyedia.'.model')),
            'key' => self::ambilRahasia('ai.key') ?? config('services.'.$penyedia.'.key'),
            'cadangan_provider' => self::ambil('ai.cadangan.provider', config('services.ai.cadangan.provider')),
            'cadangan_model' => self::ambil('ai.cadangan.model', config('services.ai.cadangan.model')),
            'cadangan_key' => self::ambilRahasia('ai.cadangan.key') ?? config('services.ai.cadangan.key'),
        ];
    }
}
