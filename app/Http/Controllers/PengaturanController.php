<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanAiRequest;
use App\Http\Requests\SimpanPenomoranRequest;
use App\Models\Document;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\Ai\AiReviewerInterface;
use App\Services\Ai\FallbackReviewer;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

/**
 * Pengaturan Sistem (PLAN-AKSES-v8 Fase 5).
 *
 * 5a — Penomoran Dokumen: prefix site & nama site. Dua nilai yang dulu jadi
 *      konstanta di kode dan ternyata berubah begitu perusahaan menggarap site
 *      lain.
 * 5c — Konfigurasi Sistem: setelan AI (penyedia, model, kunci API, cadangan)
 *      dan kartu Kesehatan.
 *
 * Yang PALING penting pada 5a bukan kodenya, melainkan yang TIDAK dilakukannya:
 * setelan baru hanya berlaku untuk nomor yang BELUM dibangkitkan. Tak ada
 * penomoran ulang surut. Nomor dokumen mutu sudah beredar di luar sistem —
 * pada laporan audit, pada dokumen lain yang merujuknya, pada cetakan yang
 * dipegang orang di lapangan. Menulis ulangnya berarti membuat semua rujukan
 * itu menunjuk ke sesuatu yang tak lagi ada namanya.
 *
 * Dan yang paling penting pada 5c: setelan AI dibaca lewat SATU pintu,
 * `Pengaturan::ai()`. Sebelum fase ini, `config('services.ai.enabled')` dibaca
 * terpisah di dua controller tinjau — dibiarkan, saklar di layar ini akan
 * menyala tanpa mematikan apa pun, persis jebakan yang sudah kena sekali di
 * `DocumentType::kode()` (rencana §9.3 butir 1).
 *
 * Otorisasi: `can:user.manage` di grup rutenya. Seluruh perubahan ter-audit
 * (CLAUDE.md §6 — tindakan Admin wajib tercatat) lengkap dengan nilai sebelum
 * & sesudahnya, sebab tabel `pengaturan` sendiri tak menyimpan riwayat.
 */
class PengaturanController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function penomoran(): Response
    {
        return Inertia::render('Pengaturan/Penomoran', [
            'prefix' => Pengaturan::prefix(),
            'namaSite' => Pengaturan::namaSite(),
        ]);
    }

    public function simpanPenomoran(SimpanPenomoranRequest $request): RedirectResponse
    {
        $sebelum = ['prefix' => Pengaturan::prefix(), 'nama_site' => Pengaturan::namaSite()];
        $sesudah = ['prefix' => $request->string('prefix')->value(), 'nama_site' => $request->string('nama_site')->value()];

        if ($sebelum === $sesudah) {
            return back()->with('status', 'Tidak ada yang berubah.');
        }

        Pengaturan::simpan('site.prefix', $sesudah['prefix']);
        Pengaturan::simpan('site.nama', $sesudah['nama_site']);

        $this->audit->log('pengaturan.site_diubah', null, [
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
        ]);

        // Kunci flash-nya `status`, bukan `success`: layout hanya merender
        // `status` dan `error` (layouts/app.blade.php:1229-1234).
        return back()->with('status',
            "Setelan disimpan. Dokumen BARU akan bernomor {$sesudah['prefix']}-…; dokumen yang sudah ada tidak berubah."
        );
    }

    // ===================== 5c — Konfigurasi Sistem =====================

    public function sistem(): Response
    {
        $ai = Pengaturan::ai();

        return Inertia::render('Pengaturan/Sistem', [
            /*
            | Kedua kunci API DIBUANG dari props, dan ini bukan kerapian
            | melainkan syarat. Blade lama menerima `$ai` utuh dan aman karena
            | ia tak pernah MENCETAK kuncinya; payload Inertia tak punya
            | kemewahan itu — seluruh props tertulis di atribut `data-page`
            | dan terbaca siapa pun lewat "view source". Yang boleh sampai ke
            | layar hanya bentuk bertopengnya (`keyTopeng`).
            | Dijaga KonfigurasiSistemTest::test_layar_menampilkan_kunci_bertopeng.
            */
            'ai' => Arr::except($ai, ['key', 'cadangan_key']),
            'keyTopeng' => $this->topeng($ai['key']),
            'cadanganKeyTopeng' => $this->topeng($ai['cadangan_key']),
            'penyedia' => SimpanAiRequest::PENYEDIA,
            'kesehatan' => $this->kesehatan(),
        ]);
    }

    /**
     * Simpan setelan AI.
     *
     * SATU aturan menentukan seluruh bentuk method ini: kunci API kosong
     * berarti PERTAHANKAN YANG LAMA. Layarnya menampilkan kunci bertopeng,
     * jadi Admin yang cuma mengganti model tak punya kunci asli untuk diketik
     * ulang — dan tanpa aturan ini setiap penyimpanan biasa akan mencabutnya
     * diam-diam. Menghapusnya menuntut niat tersendiri: kotak centang.
     *
     * Yang tercatat di audit adalah penyedia & model, TIDAK PERNAH kuncinya —
     * cukup penanda "diganti/dihapus/tetap". Audit log dibaca banyak mata;
     * kunci API di dalamnya sama saja dengan kunci yang bocor.
     */
    public function simpanAi(SimpanAiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $sebelum = Pengaturan::ai();

        Pengaturan::simpan('ai.enabled', $data['enabled'] ? '1' : '0');
        Pengaturan::simpan('ai.provider', $data['provider']);
        Pengaturan::simpan('ai.model', $data['model']);
        Pengaturan::simpan('ai.cadangan.provider', $data['cadangan_provider'] ?? null);
        Pengaturan::simpan('ai.cadangan.model', $data['cadangan_model'] ?? null);

        $nasibKunci = $this->simpanKunci('ai.key', $data['key'] ?? null, $request->boolean('hapus_key'));
        $nasibCadangan = $this->simpanKunci('ai.cadangan.key', $data['cadangan_key'] ?? null, $request->boolean('hapus_cadangan_key'));

        $this->audit->log('pengaturan.ai_diubah', null, [
            'sebelum' => ['aktif' => $sebelum['enabled'], 'provider' => $sebelum['provider'], 'model' => $sebelum['model']],
            'sesudah' => ['aktif' => (bool) $data['enabled'], 'provider' => $data['provider'], 'model' => $data['model']],
            'kunci' => $nasibKunci,
            'kunci_cadangan' => $nasibCadangan,
        ]);

        return back()->with('status', $data['enabled']
            ? "Setelan AI disimpan — aktif lewat {$data['provider']} ({$data['model']})."
            : 'Setelan AI disimpan — bantuan AI dimatikan; layar tinjau tetap berjalan tanpa panel AI.');
    }

    /**
     * Uji pengiriman email — HANYA ke alamat Admin yang sedang login (§9.1).
     *
     * Batas itu bukan kehati-hatian berlebih: satu kotak alamat bebas
     * menjadikan layar ini pengirim surat atas nama perusahaan bagi siapa pun
     * yang memegang `user.manage`. Alamatnya diambil dari akunnya sendiri, tak
     * pernah dari input. Rutenya ber-throttle:3,1.
     */
    public function ujiEmail(Request $request): RedirectResponse
    {
        $admin = $request->user();

        if (! $admin->email) {
            return back()->with('error', 'Akun Anda belum punya alamat email. Isi dulu lewat Manajemen User.');
        }

        try {
            Mail::raw(
                "Ini email uji dari SmartPro Document Generator.\n\n"
                .'Dikirim '.now()->translatedFormat('d F Y H:i').' WITA oleh '.$admin->name.'. '
                .'Bila Anda menerimanya, pengiriman email sistem berjalan normal.',
                fn ($m) => $m->to($admin->email)->subject('Uji Pengiriman Email — SmartPro')
            );
        } catch (\Throwable $e) {
            /*
            | Pesan galatnya ditampilkan apa adanya. Itulah satu-satunya
            | petunjuk yang berguna saat relay SMTP menolak — menyembunyikannya
            | berarti Admin menebak-nebak. Layar ini sudah `can:user.manage`,
            | jadi tak ada penonton tambahan yang ikut membacanya.
            */
            $this->audit->log('pengaturan.uji_email', null, ['berhasil' => false, 'galat' => $e->getMessage()]);

            return back()->with('error', 'Gagal mengirim: '.$e->getMessage());
        }

        $this->audit->log('pengaturan.uji_email', null, ['berhasil' => true, 'ke' => $admin->email]);

        return back()->with('status', "Email uji dikirim ke {$admin->email}.");
    }

    /**
     * Uji koneksi AI (PLAN-PREPRODUKSI-v9 Fase 2a).
     *
     * Sebelum ini, satu-satunya cara membuktikan kunci API hidup adalah membuka
     * layar Tinjau Dokumen dan menekan Analisis AI — 1–3 menit menunggu untuk
     * pertanyaan yang jawabannya "kuncinya benar atau tidak".
     *
     * Rantai cadangan dilaporkan PER PENYEDIA, bukan digabung jadi satu
     * jawaban: "salah satu gagal" tak memberi tahu Admin kunci mana yang harus
     * diperbaiki. Labelnya dari `Pengaturan::ai()` karena urutan yang dirakit
     * AppServiceProvider memang utama-lalu-cadangan.
     */
    public function ujiAi(AiReviewerInterface $ai): RedirectResponse
    {
        $setelan = Pengaturan::ai();
        $daftar = $ai instanceof FallbackReviewer ? $ai->daftar() : [$ai];

        $label = [
            ['Utama', $setelan['provider'], $setelan['model']],
            ['Cadangan', $setelan['cadangan_provider'], $setelan['cadangan_model']],
        ];

        $baris = [];
        $catatan = [];
        $adaGagal = false;

        foreach ($daftar as $i => $penyedia) {
            [$nama, $provider, $model] = $label[$i] ?? ['Penyedia '.($i + 1), null, null];
            $galat = $penyedia->ping();
            $adaGagal = $adaGagal || $galat !== null;

            $baris[] = $nama.' ('.($provider ?: '—').' / '.($model ?: '—').'): '
                .($galat === null ? 'terhubung.' : $galat);

            $catatan[] = ['label' => $nama, 'provider' => $provider, 'model' => $model, 'berhasil' => $galat === null];
        }

        /*
        | Yang tercatat adalah penyedia, model, dan berhasil/gagal — TIDAK
        | PERNAH kuncinya, dan tidak pula pesan galat penyedia: audit log dibaca
        | banyak mata, dan pesan itu sudah tampil di layar Admin yang memicunya.
        */
        $this->audit->log('pengaturan.ai_diuji', null, ['hasil' => $catatan]);

        $pesan = implode(' | ', $baris);

        return $adaGagal ? back()->with('error', $pesan) : back()->with('status', $pesan);
    }

    /**
     * Bersihkan cache aplikasi + cache izin spatie.
     *
     * SENGAJA bukan "jalankan artisan apa saja dari web" (§9.1). Dua perintah
     * tetap, tanpa satu pun masukan dari pengguna — kotak isian perintah adalah
     * eksekusi kode jarak jauh dengan nama lain.
     */
    public function bersihkanCache(): RedirectResponse
    {
        Artisan::call('cache:clear');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->audit->log('pengaturan.cache_dibersihkan');

        return back()->with('status', 'Cache aplikasi & cache izin dibersihkan.');
    }

    /** Kunci bertopeng untuk layar — 4 aksara terakhir saja, panjangnya tak dibocorkan. */
    private function topeng(?string $kunci): ?string
    {
        return $kunci ? '••••••••'.Str::substr($kunci, -4) : null;
    }

    /**
     * Simpan satu kunci rahasia dan laporkan nasibnya untuk audit.
     *
     * Urutannya penting: HAPUS menang atas isi baru, supaya Admin yang
     * mencentang "hapus" tak dikalahkan oleh sisa teks yang tertinggal di
     * kotaknya.
     */
    private function simpanKunci(string $kunci, ?string $baru, bool $hapus): string
    {
        if ($hapus) {
            Pengaturan::simpanRahasia($kunci, null);

            return 'dihapus';
        }

        if ($baru !== null && $baru !== '') {
            Pengaturan::simpanRahasia($kunci, $baru);

            return 'diganti';
        }

        return 'tetap';
    }

    /**
     * Kartu Kesehatan — angka yang menjawab "sistemnya masih waras?".
     *
     * Ukuran folder lampiran DI-CACHE 5 MENIT karena ia satu-satunya yang
     * mahal: menelusuri seluruh berkas di storage/app/public/lampiran. Sisanya
     * agregat sekali jalan. Tanpa cache itu, halaman ini melambat sebanding
     * dengan jumlah foto yang diunggah tujuh departemen — padahal angka ini tak
     * pernah perlu akurat sampai ke detik.
     */
    private function kesehatan(): array
    {
        $lampiran = storage_path('app/public/lampiran');

        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'zona' => config('app.timezone'),
            'debug' => (bool) config('app.debug'),
            'lampiran_bytes' => Cache::remember('kesehatan:lampiran_bytes', now()->addMinutes(5), function () use ($lampiran) {
                if (! File::isDirectory($lampiran)) {
                    return 0;
                }

                return array_sum(array_map(fn ($f) => $f->getSize(), File::allFiles($lampiran)));
            }),
            'dokumen' => Document::count(),
            'dokumen_berlaku' => Document::where('status', 'published')->count(),
            /*
            | Angka untuk tombol Bersihkan Seluruh Dokumen (Fase 2b) — dan
            | `withTrashed` di sini WAJIB, bukan pilihan gaya: yang dimusnahkan
            | tombol itu termasuk dokumen yang sudah di-soft-delete. Memakai
            | `'dokumen'` di atas berarti menjanjikan angka yang lebih kecil
            | daripada yang benar-benar terhapus.
            */
            'dokumen_semua' => Document::withTrashed()->count(),
            'pengguna' => User::where('status', 'active')->count(),
            'ai' => $this->kesehatanAi(),
            'mailer' => config('mail.default'),
            'mail_host' => config('mail.mailers.'.config('mail.default').'.host'),
            'mail_dari' => config('mail.from.address'),
            'mail_paksa_ke' => config('mail.paksa_ke'),
        ];
    }

    /**
     * Keadaan kedua penyedia AI, per baris — bukan satu jawaban gabungan.
     *
     * Ini jawaban atas kegagalan yang memicu rencana pra-produksi Fase 3: baris
     * `ai.key` hilang dari tabel `pengaturan`, penyedia utama diam-diam menjadi
     * NullReviewer, dan tak ada satu pun layar yang menyebutkannya. Tombol "Uji
     * koneksi AI" memang melaporkannya, tapi hanya kalau ada yang menekannya.
     *
     * KUNCINYA TIDAK PERNAH IKUT — hanya ada/tidaknya (CLAUDE.md §4; kunci API
     * sudah sekali nyaris bocor ke props di Fase 7 migrasi).
     *
     * @return list<array{label:string,provider:?string,model:?string,status:string}>
     */
    private function kesehatanAi(): array
    {
        $ai = Pengaturan::ai();

        $baris = [];

        foreach ([
            ['Utama', 'provider', 'key', 'model'],
            ['Cadangan', 'cadangan_provider', 'cadangan_key', 'cadangan_model'],
        ] as [$label, $provider, $key, $model]) {
            $baris[] = [
                'label' => $label,
                'provider' => $ai[$provider] ?: null,
                'model' => $ai[$model] ?: null,
                'status' => match (true) {
                    ! $ai['enabled'] => 'dimatikan',
                    ! $ai[$provider] => 'penyedia kosong',
                    ! $ai[$key] => 'kunci kosong',
                    ! $ai[$model] => 'model kosong',
                    default => 'aktif',
                },
            ];
        }

        return $baris;
    }
}
