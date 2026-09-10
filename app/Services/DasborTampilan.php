<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bagian dashboard yang DULU dihitung di dalam `dashboard.blade.php`.
 *
 * Kartu sambutan (kalimat "menunggu tindakanmu" + tombolnya), empat kartu
 * statistik per peran, dan peta aksi feed aktivitas semuanya berupa `@php`
 * panjang di kepala Blade lama. Ketiganya LOGIKA PER PERAN, bukan tata letak:
 * memindahkannya ke TSX berarti aturan jabatan punya salinan kedua di klien —
 * persis yang dilarang CLAUDE.md §4 dan pakem P5 MIGRASI-SHADCN.
 *
 * Rumahnya kelas ini, bukan `DashboardController`: controller itu sudah 575
 * baris sebelum migrasi, dan CLAUDE.md §3 menyuruh memecah di ~300. Yang
 * tinggal di controller adalah PENGAMBILAN data; yang pindah ke sini adalah
 * penyusunan data itu jadi kalimat & kartu.
 *
 * Nol query di kelas ini — seluruh angkanya sudah dihitung controller.
 */
class DasborTampilan
{
    /**
     * Peta aksi audit → [kalimat, ikon, rona, kategori].
     *
     * Ronanya mengikuti tata bahasa yang sama dengan Blade lama:
     *   maju    = dokumen MAJU selangkah
     *   netral  = peristiwa rutin
     *   baik    = HASIL baik (disahkan)
     *   buruk   = HASIL buruk (ditolak/dihapus)
     *
     * Dulu kelas Bootstrap (`bg-su-orange`/`bg-su-dark`/…). Diganti nama rona
     * netral karena nama kelas CSS di dalam data server adalah cara peta ini
     * jadi mustahil dipakai ulang di luar satu tema.
     *
     * KOLOM KEEMPAT `kategori` (DASBOR-V2-REVISI §5g) — kata benda pendek yang
     * jadi LENCANA di garis waktu V2. Ia tinggal di sini, bukan disimpulkan di
     * TSX dari string `aksi`: menyimpulkannya berarti klien mengurai kalimat
     * Indonesia untuk menebak jenis peristiwa, dan kalimat itu berubah tiap kali
     * ada yang memperbaiki ejaannya.
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    private const AKSI = [
        'document.create' => ['membuat dokumen', 'bi-file-earmark-plus', 'maju', 'Dibuat'],
        'document.submit' => ['mengirim untuk ditinjau', 'bi-send', 'maju', 'Dikirim'],
        'document.withdraw' => ['menarik dokumen', 'bi-arrow-counterclockwise', 'netral', 'Ditarik'],
        'document.review_start' => ['mulai meninjau', 'bi-clipboard', 'netral', 'Ditinjau'],
        'document.review_approve' => ['meloloskan tinjauan', 'bi-check2', 'maju', 'Ditinjau'],
        'document.review_reject' => ['mengembalikan untuk revisi', 'bi-arrow-counterclockwise', 'buruk', 'Ditolak'],
        'document.approve' => ['menyetujui (Berlaku)', 'bi-patch-check', 'baik', 'Berlaku'],
        // Ikut didaftarkan bersama lencana kenaikan: ia salah satu dari DUA
        // aksi yang menaikkan kartu "Berlaku" ({@see self::AKSI_ALIR}), jadi
        // feed yang menampilkannya sebagai nama mentah `arsip upload` sementara
        // kartunya menghitungnya adalah dua cerita untuk satu peristiwa.
        'document.arsip_upload' => ['mendaftarkan dokumen lama', 'bi-archive', 'baik', 'Berlaku'],
        'document.approval_reject' => ['menolak (approver)', 'bi-x-circle', 'buruk', 'Ditolak'],
        'document.cancel_revision' => ['membatalkan penolakan', 'bi-arrow-repeat', 'netral', 'Revisi'],
        'document.reassign_review' => ['mengalihkan peninjauan', 'bi-arrow-left-right', 'netral', 'Ditinjau'],
        'document.request_revision' => ['mengajukan revisi', 'bi-arrow-repeat', 'maju', 'Revisi'],
        'document.cancel_revision_b' => ['membatalkan revisi', 'bi-x-circle', 'netral', 'Revisi'],
        'document.nonaktif_ajukan' => ['mengajukan nonaktif', 'bi-slash-circle', 'maju', 'Nonaktif'],
        'document.nonaktif_setuju' => ['menyetujui nonaktif', 'bi-check2', 'maju', 'Nonaktif'],
        'document.nonaktif_tolak' => ['menolak pengajuan nonaktif', 'bi-x-circle', 'netral', 'Nonaktif'],
        'document.nonaktif_selesai' => ['menonaktifkan dokumen', 'bi-slash-circle', 'buruk', 'Nonaktif'],
        'document.delete' => ['menghapus draft', 'bi-trash', 'buruk', 'Dihapus'],
        'attachment.comment' => ['mengomentari lampiran', 'bi-chat-left-text', 'netral', 'Lampiran'],
        'document.md_approve' => ['meloloskan verifikasi MD', 'bi-fonts', 'maju', 'MD'],
        'document.ai_review' => ['meminta tinjauan AI', 'bi-stars', 'netral', 'AI'],
        'document.md_ai_review' => ['meminta tinjauan AI (MD)', 'bi-stars', 'netral', 'AI'],
        'feedback.create' => ['mengirim masukan', 'bi-chat-left-quote', 'netral', 'Masukan'],
        'feedback.respond' => ['membalas masukan', 'bi-reply', 'netral', 'Masukan'],
        'masukan_sejawat.kirim' => ['memberi masukan sejawat', 'bi-chat-square-text', 'netral', 'Masukan'],
        'off.create' => ['mengajukan off', 'bi-calendar-x', 'netral', 'Off'],
        'user.md_ai_toggle' => ['mengubah setelan AI MD', 'bi-toggles', 'netral', 'Setelan'],
        'user.register' => ['mendaftar akun', 'bi-person-plus', 'netral', 'Akun'],
        'user.login' => ['masuk', 'bi-box-arrow-in-right', 'netral', 'Akun'],
        'user.logout' => ['keluar', 'bi-box-arrow-right', 'netral', 'Akun'],
    ];

    /** Lencana kategori bagi aksi yang belum terdaftar di {@see self::AKSI}. */
    private const KATEGORI_LAIN = 'Lainnya';

    /**
     * Peta KUNCI ALIR → aksi audit yang mewakilinya (DASBOR-V2-REVISI §5c/§6e).
     *
     * Satu-satunya tempat pemetaan itu hidup: `DashboardController::alirAudit()`
     * membaca daftar AKSI-nya untuk menyapu `audit_logs`, {@see self::tiles()}
     * membaca KUNCI-nya untuk menempelkan lencana. Dua peta terpisah suatu hari
     * akan berselisih, dan selisihnya tak terlihat — lencananya cuma diam di nol.
     *
     * Kenapa kunci sendiri, bukan nama aksi langsung di kartu: satu kartu bisa
     * disuapi LEBIH DARI SATU aksi. "Berlaku" naik lewat persetujuan biasa DAN
     * lewat pendaftaran dokumen lama; "Ditolak" lewat penolakan approver DAN
     * pengembalian peninjau. Memaksa satu aksi per kartu memotong separuh
     * ceritanya.
     *
     * `document.approval_reject` & `feedback.respond` NOL BARIS di basis data
     * pengembangan saat peta ini ditulis — bukan karena namanya salah melainkan
     * karena peristiwanya memang belum pernah terjadi di sana. Keduanya
     * diverifikasi ke KODE yang menulisnya (`ApprovalController::240`,
     * `DocumentFeedbackService::75`), dan gerbang §P9 karena itu berdiri di
     * KATALOG KODE ({@see \Tests\Feature\DasborV2Test}), bukan di isi tabel:
     * salah ketik tetap tertangkap tanpa menuntut data seeder yang kebetulan
     * lengkap.
     *
     * @var array<string, array<int, string>>
     */
    public const AKSI_ALIR = [
        'dibuat' => ['document.create'],
        'berlaku' => ['document.approve', 'document.arsip_upload'],
        'ditolak' => ['document.approval_reject', 'document.review_reject'],
        'revisi' => ['document.request_revision'],
        'dikirim' => ['document.submit'],
        'diloloskan' => ['document.review_approve'],
        // Disapu HANYA untuk deret wajah Performa PIC, dan sengaja tak dipakai
        // kartu mana pun: "Perlu Diperiksa (MD)" adalah ANTREAN yang MENYUSUT
        // saat MD bekerja, jadi `md_approve` yang naik berarti antreannya turun.
        // Lencana di atasnya akan berbunyi persis terbalik (§5c).
        'md' => ['document.md_approve'],
        'masukan_tutup' => ['feedback.respond'],
    ];

    /**
     * Kunci {@see self::AKSI_ALIR} yang dihitung sebagai "kerja yang benar-benar
     * menggerakkan dokumen" — penyuapi deret wajah "Paling Produktif" (§5d).
     *
     * `dibuat` sengaja TIDAK ikut: menulis draft memang kerja, tapi mencampurnya
     * dengan meloloskan/menyetujui membuat satu kartu mengukur dua pekerjaan
     * berbeda sekaligus, dan angkanya berhenti berarti apa-apa.
     *
     * @var array<int, string>
     */
    public const KUNCI_PRODUKTIF = ['diloloskan', 'md', 'berlaku'];

    /**
     * Kunci produktif KHUSUS lingkup sesama-GL (F4b DASBOR-V4, keputusan D3).
     *
     * `KUNCI_PRODUKTIF` di atas sengaja buang `dibuat` — tapi GL tak pernah
     * meninjau/menyetujui (CLAUDE.md §6), jadi bagi kartu yang isinya SELURUHNYA
     * GL (Performa PIC di dashboard SH/DH), `KUNCI_PRODUKTIF` selalu kosong.
     * `dikirim` diikutkan supaya draft yang ditumpuk tanpa pernah dikirim tak
     * jadi jalan pintas peringkat.
     *
     * @var array<int, string>
     */
    public const KUNCI_PRODUKTIF_GL = ['dibuat', 'dikirim'];

    /**
     * Rincian aktivitas per wajah di kartu Performa PIC (butir 10 & 11).
     *
     * Kunci rincian → [kunci {@see self::AKSI_ALIR}, kata kerjanya]. Nama
     * AKSI-nya sendiri TIDAK ditulis ulang di sini: ia dibaca dari `AKSI_ALIR`
     * lewat {@see self::rincian()}, supaya peta kartu dan peta query mustahil
     * berselisih (§P9) — daftar kedua yang menyimpang tak akan berbunyi, ia
     * cuma membuat satu kolom rincian diam di nol selamanya.
     *
     * `dibuat` IKUT di sini walau sengaja TIDAK ikut di
     * {@see self::KUNCI_PRODUKTIF}: butir 11 menanyakan berapa dokumen yang
     * dibuat GL, dan itu pertanyaan yang dijawab RINCIAN — bukan peringkat.
     * Urutan wajah karena itu tak berubah satu pun (K-H).
     *
     * Unsur KETIGA = aksi yang dikecualikan dari kunci alirnya. Ia lahir dari
     * satu temuan pemilik (2026-09-07): kartu Performa PIC di dashboard SH
     * menuliskan "menyetujui N" pada seorang GL — padahal GL tak pernah
     * menyetujui (CLAUDE.md §6). Sebabnya `AKSI_ALIR['berlaku']` memuat DUA
     * aksi, dan yang kedua (`document.arsip_upload`) dikerjakan justru oleh
     * PEMBUATNYA saat mendaftarkan dokumen lama. Untuk kartu ALIRAN kedua aksi
     * itu memang satu hal — dokumen jadi berlaku; untuk kalimat yang menyebut
     * KATA KERJA seseorang, keduanya dua pekerjaan berbeda.
     *
     * Pengecualiannya ditulis di sini, bukan dengan memecah `AKSI_ALIR`:
     * memecahnya akan mengubah angka setiap kartu "Berlaku" di dashboard —
     * dokumen lama yang didaftarkan memang berlaku, dan menghilangkannya dari
     * aliran itu justru cacat yang lebih besar.
     *
     * @var array<string, array{0: string, 1: string, 2?: array<int, string>}>
     */
    public const RINCIAN_PIC = [
        'dibuat' => ['dibuat', 'membuat'],
        'ditinjau' => ['diloloskan', 'meninjau'],
        'diperiksa' => ['md', 'memeriksa'],
        'disetujui' => ['berlaku', 'menyetujui', ['document.arsip_upload']],
    ];

    /**
     * Hitung rincian satu orang dari daftar nama aksinya.
     *
     * Keempat kunci SELALU ada, termasuk yang bernilai nol: kunci yang hilang
     * saat angkanya nol membuat bentuk propsnya berubah-ubah per orang, dan
     * pengunci kebocoran (§P4) yang membandingkan daftar kunci jadi mustahil.
     *
     * @param  array<int, string>  $aksi
     * @return array<string, int>
     */
    public static function rincian(array $aksi): array
    {
        $hasil = [];

        foreach (self::RINCIAN_PIC as $kunci => $peta) {
            $hasil[$kunci] = count(array_intersect(
                $aksi,
                array_diff(self::AKSI_ALIR[$peta[0]], $peta[2] ?? []),
            ));
        }

        return $hasil;
    }

    /**
     * Kalimat rincian, dirakit DI SERVER: "meninjau 10 · menyetujui 2".
     *
     * Merakitnya di TSX berarti kosakata alur ("meninjau"/"menyetujui") punya
     * salinan kedua di klien — dan salinan kedua itulah yang suatu hari
     * menyebut satu peristiwa dengan dua nama di dua layar.
     *
     * @param  array<string, int>  $rincian
     */
    public static function ketRincian(array $rincian): string
    {
        $bagian = [];

        foreach (self::RINCIAN_PIC as $kunci => [1 => $kata]) {
            if (($rincian[$kunci] ?? 0) > 0) {
                $bagian[] = $kata.' '.$rincian[$kunci];
            }
        }

        return implode(' · ', $bagian);
    }

    /**
     * Seluruh nama aksi yang perlu disapu dari `audit_logs`, tanpa duplikat.
     *
     * @return array<int, string>
     */
    public static function aksiDisapu(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::AKSI_ALIR))));
    }

    /**
     * Nama aksi yang dihitung sebagai kerja produktif (§5d).
     *
     * @return array<int, string>
     */
    public static function aksiProduktif(): array
    {
        return array_values(array_unique(array_merge(
            ...array_map(fn (string $k) => self::AKSI_ALIR[$k], self::KUNCI_PRODUKTIF)
        )));
    }

    /**
     * Nama aksi produktif bagi lingkup SESAMA-GL (§KUNCI_PRODUKTIF_GL).
     *
     * @return array<int, string>
     */
    public static function aksiProduktifGl(): array
    {
        return array_values(array_unique(array_merge(
            ...array_map(fn (string $k) => self::AKSI_ALIR[$k], self::KUNCI_PRODUKTIF_GL)
        )));
    }

    /**
     * Kartu sambutan: apa yang MENUNGGU orang ini hari ini.
     *
     * Kalimat & tombolnya berbeda per jabatan, seluruhnya dari `$stats`/`$queues`
     * yang sudah dihitung controller — nol query tambahan.
     *
     * @param  array<string, int>  $stats
     * @param  array<string, int>  $queues
     * @return array{jabatan: string, dept: ?string, jam: string, tanggal: string,
     *               tunggu: array<int, string>, aksi: array<int, array{label: string, url: string, utama: bool}>}
     */
    public function hero(User $user, array $stats, array $queues, bool $isCreator, bool $isPjo, bool $isDeptHead, bool $isMd): array
    {
        $tunggu = [];
        $aksi = [];

        if ($isCreator) {
            if (($stats['need_revision'] ?? 0) > 0) {
                $tunggu[] = $stats['need_revision'].' dokumen perlu direvisi';
            }
            // Menaut ke jenis yang BOLEH ia susun, bukan `type=SOP` mati:
            // sesudah profil akses berlaku, SOP bisa saja justru satu-satunya
            // jenis yang tertutup baginya. Tanpa satu pun jenis, tombolnya
            // tidak ditawarkan sama sekali.
            if ($jenisBaru = $user->jenisPertamaBoleh()) {
                $aksi[] = $this->tombol('Buat Dokumen Baru', route('documents.create', ['type' => $jenisBaru]), true);
            }
            if (($stats['need_revision'] ?? 0) > 0) {
                $aksi[] = $this->tombol('Lihat Dokumen Revisi', route('documents.revisions'));
            }
        } elseif ($isDeptHead) {
            if (($queues['review'] ?? 0) > 0) {
                $tunggu[] = $queues['review'].' dokumen menunggu ditinjau';
                $aksi[] = $this->tombol('Mulai Meninjau', route('review.index'), true);
            }
            if (($queues['approval'] ?? 0) > 0) {
                $tunggu[] = $queues['approval'].' menunggu persetujuanmu';
            }
            // Ejaannya WAJIB sama dengan menu sidebar & judul halamannya
            // ("Status Dokumen Staff"); dulu di sini "Staf", sehingga satu
            // tujuan yang sama tampil dengan dua nama berbeda.
            $aksi[] = $this->tombol('Status Dokumen Staff', route('documents.staffStatus'));
        } elseif ($isPjo) {
            if (($queues['approval'] ?? 0) > 0) {
                $tunggu[] = $queues['approval'].' dokumen menunggu disetujui';
                $aksi[] = $this->tombol('Buka Persetujuan', route('approvals.index'), true);
            }
            $aksi[] = $this->tombol('Dokumen Berlaku', route('documents.published'));
        } elseif ($isMd) {
            if (($queues['review_md'] ?? 0) > 0) {
                $tunggu[] = $queues['review_md'].' dokumen menunggu diperiksa';
                $aksi[] = $this->tombol('Mulai Memeriksa', route('review.md'), true);
            }
            $aksi[] = $this->tombol('Status Dokumen Staff', route('documents.staffStatus'));
        } else {
            $aksi[] = $this->tombol('Dokumen Berlaku', route('documents.published'), true);
        }

        if (empty($aksi)) {
            $aksi[] = $this->tombol('Dokumen Berlaku', route('documents.published'), true);
        }

        return [
            'jabatan' => User::JABATAN_LABELS[$user->jabatan] ?? ($user->getRoleNames()->first() ?? '-'),
            'dept' => $user->department?->code,
            'jam' => now()->format('H:i'),
            'tanggal' => now()->translatedFormat('l, d F Y'),
            'tunggu' => $tunggu,
            'aksi' => $aksi,
        ];
    }

    /**
     * Empat kartu statistik, susunannya per peran.
     *
     *   GL       : Dokumen Saya · Berlaku · Dokumen Ditolak · Sedang Revisi
     *   SH/DH/PJO: Perlu Disetujui · Total Dokumen · Berlaku · Sedang Revisi
     *   MD       : Perlu Diperiksa · Total Dokumen · Berlaku · Sedang Revisi
     *   sisanya  : Total Dokumen · Menunggu Ditinjau · Berlaku · Sedang Revisi
     *
     * Angka antrean yang tak berlaku bagi seseorang tampil 0, bukan menghilang:
     * kartu yang kadang ada kadang tidak membuat tata letak berkedip antar-peran.
     *
     * @param  array<string, int>  $stats
     * @param  array<string, int>  $queues
     * @param  array<string, array{deret: array<int, int>, kini: int, lalu: int}>  $alir
     * @return array<int, array{label: string, nilai: int, ikon: string, url: ?string,
     *                          aksi: ?string, arah: string, deret: ?array<int, int>,
     *                          delta: ?float, deltaLabel: ?string}>
     */
    public function tiles(User $user, array $stats, array $queues, bool $isCreator, bool $isPjo, bool $isDeptHead, bool $isMd, array $alir = []): array
    {
        $kTotal = $this->tile('Total Dokumen', $stats['total'], 'bi-files', route('documents.staffStatus'), 'dibuat');
        $kBerlaku = $this->tile('Berlaku', $stats['published'], 'bi-patch-check', route('documents.published'), 'berlaku', 'baik');
        $kRevisi = $this->tile('Sedang Revisi', $stats['sedang_direvisi'], 'bi-arrow-repeat', route('documents.published'), 'revisi');
        $kSetuju = $this->tile('Perlu Disetujui', $queues['approval'] ?? 0, 'bi-patch-question', route('approvals.index'), 'diloloskan');

        if ($isCreator) {
            return $this->berlencana([
                // `dibuat_saya`, bukan `dibuat`: kartunya menghitung dokumen
                // BUATANNYA, sedangkan lingkup terlihat GL juga memuat dokumen
                // sedepartemen. Varian `_saya` disediakan alirAudit() untuk
                // SETIAP kunci — lihat DashboardController::alirAudit().
                $this->tile('Dokumen Saya', $stats['my_documents'], 'bi-folder2-open', route('documents.index'), 'dibuat_saya'),
                $kBerlaku,
                $this->tile('Dokumen Ditolak', $stats['rejected'], 'bi-x-octagon', route('documents.revisions'), 'ditolak', 'buruk'),
                $kRevisi,
            ], $alir);
        }

        if ($isDeptHead || $isPjo) {
            return $this->berlencana([$kSetuju, $kTotal, $kBerlaku, $kRevisi], $alir);
        }

        if ($isMd) {
            // MD memeriksa PENULISAN, tak pernah menyetujui — jadi "Perlu
            // Disetujui" diganti "Perlu Diperiksa". "Akun Menunggu" juga tidak
            // ikut: MD tak punya `user.approve_registration`, jadi kartunya
            // akan selalu 0 sekaligus menautkan ke halaman yang menolaknya 403.
            return $this->berlencana([
                // TANPA kunci alir (§5c): tak ada aksi audit yang jujur mewakili
                // "perlu diperiksa". Lebih baik satu kartu tanpa lencana
                // daripada empat kartu yang salah satunya berbohong.
                $this->tile('Perlu Diperiksa', $queues['review_md'] ?? 0, 'bi-spellcheck', route('review.md')),
                $kTotal,
                $kBerlaku,
                $kRevisi,
            ], $alir);
        }

        // Admin / Non-Staff: ringkasan umum, tanpa antrean yang memang tak
        // pernah jadi tugasnya. Tautan "Total Dokumen" HARUS bercabang —
        // halaman Status Dokumen Staff menolak Non-Staff dengan 403.
        return $this->berlencana([
            $this->tile(
                'Total Dokumen',
                $stats['total'],
                'bi-files',
                $user->dashboardPenuh() ? route('documents.staffStatus') : route('documents.index'),
                'dibuat',
            ),
            $this->tile('Menunggu Ditinjau', $stats['menunggu_tinjau'], 'bi-hourglass-split', null, 'dikirim'),
            $kBerlaku,
            $kRevisi,
        ], $alir);
    }

    /**
     * Feed aktivitas: satu baris audit → kalimat, ikon, rona, kategori, tautan.
     *
     * Aksi yang belum dipetakan tetap tampil dengan nama mentahnya — itu isyarat
     * bahwa aksi itu perlu ditambahkan ke {@see self::AKSI}, bukan alasan untuk
     * menyembunyikan barisnya. Kategorinya jatuh ke {@see self::KATEGORI_LAIN}
     * dan BUKAN ke string kosong: lencana yang kosong menyisakan lubang seukuran
     * lencana di garis waktu, dan barisnya jadi tak sejajar dengan tetangganya.
     *
     * @param  Collection<int, AuditLog>  $activities
     * @return array<int, array{id: int, pelaku: string, aksi: string, ikon: string,
     *                          rona: string, kategori: string, nomor: ?string,
     *                          tautan: ?string, waktu: string}>
     */
    public function aktivitas(Collection $activities): array
    {
        return $activities->map(function (AuditLog $log) {
            [$aksi, $ikon, $rona, $kategori] = self::AKSI[$log->action]
                ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'netral', self::KATEGORI_LAIN];

            return [
                'id' => $log->id,
                'pelaku' => $log->user->name ?? 'Sistem',
                'aksi' => $aksi,
                'ikon' => $ikon,
                'rona' => $rona,
                'kategori' => $kategori,
                'nomor' => $log->document?->displayNumber(),
                'tautan' => $log->document ? route('documents.show', $log->document) : null,
                'waktu' => $log->created_at?->format('d M, H:i').' WITA',
            ];
        })->all();
    }

    /**
     * Satu sorotan berpanah kartu Performa PIC (§5d).
     *
     * Bentuknya SENGAJA sama dengan lencana kartu KPI (`arah` + `delta` +
     * `deltaLabel`) supaya satu komponen panah melayani keduanya, dan supaya
     * "↗ hijau" berarti hal yang sama di dua kartu yang bersebelahan.
     *
     * `$aliran` null = angka tanpa panah. Itu bukan kekurangan melainkan §P2b:
     * sorotan yang tak punya deret waktu yang jujur (rata-rata beban peninjau
     * adalah potret HARI INI, bukan aliran) lebih baik tampil polos daripada
     * memakai panah yang diam-diam mengukur hal lain.
     *
     * @param  array{kini: int, lalu: int}|null  $aliran
     * @return array{label: string, nilai: string, arah: string, delta: ?float, deltaLabel: ?string}
     */
    public function sorotan(string $label, string $nilai, string $arah, ?array $aliran = null): array
    {
        [$delta, $deltaLabel] = $aliran === null
            ? [null, null]
            : $this->delta($aliran['kini'], $aliran['lalu']);

        return ['label' => $label, 'nilai' => $nilai, 'arah' => $arah, 'delta' => $delta, 'deltaLabel' => $deltaLabel];
    }

    /** @return array{label: string, url: string, utama: bool} */
    private function tombol(string $label, string $url, bool $utama = false): array
    {
        return ['label' => $label, 'url' => $url, 'utama' => $utama];
    }

    /**
     * Satu kartu statistik.
     *
     * `$aksi` = KUNCI {@see self::AKSI_ALIR} yang menyuapi lencana kenaikannya;
     * `null` berarti kartu ini sengaja tak berlencana (§5c). Ia MENGGANTIKAN
     * `rona` yang sempat ada di sini: nada warna per kartu dibuang bersama
     * seluruh tile berona (keputusan pemilik D2), dan yang dibutuhkan kartunya
     * sekarang bukan "kabar baik atau buruk" melainkan "dari deret mana angka
     * ini tumbuh".
     *
     * `$arah` menjawab satu-satunya pertanyaan yang TERSISA dari `rona`, dan
     * cuma untuk lencananya: naiknya angka ini kabar `baik`, `buruk`, atau
     * `netral`. Naiknya "Dokumen Ditolak" buruk, naiknya "Berlaku" baik. Ia
     * dihitung DI SINI, bukan disimpulkan TSX dari `delta > 0` — tanda angka tak
     * pernah tahu apakah yang tumbuh itu hal yang diinginkan (CLAUDE.md §4).
     *
     * `netral` dipakai untuk kartu yang pertumbuhannya memang tak bermuatan:
     * "Total Dokumen"/"Dokumen Saya" (sekadar volume kerja), "Sedang Revisi",
     * "Menunggu Ditinjau", dan "Perlu Disetujui" (proses berjalan — naiknya bisa
     * berarti tim produktif ATAU antrean menumpuk, dan memilih salah satunya
     * adalah mengarang).
     *
     * @return array{label: string, nilai: int, ikon: string, url: ?string, aksi: ?string, arah: string}
     */
    private function tile(string $label, int $nilai, string $ikon, ?string $url, ?string $aksi = null, string $arah = 'netral'): array
    {
        return ['label' => $label, 'nilai' => $nilai, 'ikon' => $ikon, 'url' => $url, 'aksi' => $aksi, 'arah' => $arah];
    }

    /**
     * Menempelkan `deret` (sparkline 8 bulan) + `delta`/`deltaLabel` ke tiap kartu.
     *
     * Dipisah dari {@see self::tile()} supaya keempat susunan per peran di atas
     * tetap terbaca sebagai DAFTAR KARTU, bukan daftar kartu berikut aritmatika
     * lencananya.
     *
     * `$alir` kosong (pemanggil yang belum menyapu audit — mis. tes lama yang
     * memanggil `tiles()` dengan tujuh argumen) TIDAK membuat ini galat:
     * kartunya cuma pulang tanpa lencana.
     *
     * @param  array<int, array<string, mixed>>  $daftar
     * @param  array<string, array{deret: array<int, int>, kini: int, lalu: int}>  $alir
     * @return array<int, array<string, mixed>>
     */
    private function berlencana(array $daftar, array $alir): array
    {
        return array_map(function (array $t) use ($alir) {
            $a = $t['aksi'] !== null ? ($alir[$t['aksi']] ?? null) : null;

            if ($a === null) {
                return $t + ['deret' => null, 'delta' => null, 'deltaLabel' => null];
            }

            [$delta, $deltaLabel] = $this->delta($a['kini'], $a['lalu']);

            return $t + ['deret' => $a['deret'], 'delta' => $delta, 'deltaLabel' => $deltaLabel];
        }, $daftar);
    }

    /**
     * Perbandingan 30 hari terakhir vs 30 hari sebelumnya → [angka, label].
     *
     * BUKAN "bulan ini vs bulan lalu", dan alasannya keras: pada tanggal 3,
     * bulan kalender berjalan baru berumur tiga hari, dan membandingkannya
     * dengan bulan penuh akan menampilkan `↘ 90%` setiap awal bulan — angka
     * nyata yang menceritakan kebohongan (§5c). Label lencananya karena itu
     * berbunyi "vs 30 hari sebelumnya", bukan "dari bulan lalu".
     *
     * Tiga keluaran, ketiganya disengaja:
     *   • dua-duanya 0 → `[null, null]`; lencananya TIDAK digambar sama sekali.
     *     Periode sepi bukan berita, dan `0%` di atas kartu kosong cuma menyuruh
     *     orang mencari makna yang tak ada.
     *   • pembagi 0    → `[100.0, '+100.0%']`. Dulu berbunyi `Baru`, dan itulah
     *     satu-satunya sumber teks "Baru" di seluruh aplikasi — ia tampak di
     *     kartu KPI, sorotan Performa PIC, Lacak Status, dan kepala Sebaran per
     *     Departemen sekaligus, lalu terbaca sebagai "kartu ini baru" alih-alih
     *     "angkanya naik dari nol" (TEMUAN-F8 1c). Formatnya kini sama dengan
     *     cabang biasa; `∞%` tetap tak pernah tercetak karena persennya dipatok
     *     100.0, dan tanda itu pula yang menentukan arah panahnya.
     *   • selebihnya   → persen ber-satu desimal, bertanda.
     *
     * @return array{0: ?float, 1: ?string}
     */
    private function delta(int $kini, int $lalu): array
    {
        if ($kini === 0 && $lalu === 0) {
            return [null, null];
        }

        if ($lalu === 0) {
            return [100.0, sprintf('%+.1f%%', 100.0)];
        }

        $persen = round(($kini - $lalu) / $lalu * 100, 1);

        return [$persen, sprintf('%+.1f%%', $persen)];
    }
}
