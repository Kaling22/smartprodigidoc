<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRead;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiRead;
use App\Models\User;
use App\Models\UserOffDay;
use App\Services\DasborTampilan;
use App\Services\DocumentDistribution;
use App\Services\InformasiDistribution;
use App\Services\ReviewerAvailability;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Berapa baris yang DIKIRIM ke kartu "Distribusi & Keterbacaan".
     *
     * Ini bukan berapa yang TERLIHAT. Kartunya menggulir di dalam dirinya
     * sendiri (`KartuDistribusi.tsx`), jadi yang terlihat sekitar empat dan
     * sisanya dicapai dengan menggulir — memotong kiriman di angka yang
     * terlihat berarti tak ada lagi yang bisa digulir.
     *
     * SATU angka untuk kedua sumbernya (dokumen mutu & Informasi): dua tab pada
     * satu kartu yang sama.
     */
    private const BARIS_DISTRIBUSI = 10;

    /**
     * Plafon baris kartu "Lacak Status Dokumen".
     *
     * DULU 4, dan alasannya (§P1 DASBOR-V4) adalah komponen V1
     * `components/dasbor/KartuPerjalanan.tsx` yang memang dirancang untuk empat
     * baris. V1 dihapus seluruhnya pada 2026-09-07, jadi alasan itu ikut
     * hilang — dan `LacakStatus` V2 menggulir tabelnya di dalam kartu, jadi
     * baris kelima dan seterusnya tak lagi memanjangkan halaman.
     *
     * Tetap BERPLAFON, bukan tanpa batas: barisnya ikut ke atribut `data-page`
     * pada setiap pemuatan dashboard, dan departemen yang sedang sibuk bisa
     * punya ratusan dokumen berjalan. Lima puluh cukup untuk digulir dan tetap
     * murah; sisanya memang urusan halaman daftar penuh, yang tautannya sudah
     * ada di kaki kartu.
     */
    private const BARIS_MEJA = 50;

    public function index(Request $request, DasborTampilan $tampilan)
    {
        $user = $request->user();

        // Documents visible to this user (strict per-dept unless view_all, D12).
        $visible = fn () => Document::query()->when(
            ! $user->can('document.view_all'),
            fn ($q) => $q->where(fn ($w) => $w->where('department_id', $user->department_id)->orWhere('created_by', $user->id)),
        );

        // Peran dashboard: GL = pembuat (kartu "Perlu Revisi" + "Dokumen Ditolak");
        // SH/DH/PJO = peninjau/penyetuju (kartu "Sedang Revisi", tak merevisi sendiri).
        $isCreator = $user->jabatan === 'group_leader';
        $isPjo = $user->jabatan === 'pimpinan';
        // SH/DH memimpin departemen → kartu ke-2 menampilkan dokumen DEPARTEMEN
        // (bukan "Dokumen Saya"), sesuai lingkup wewenang mereka (#4).
        $isDeptHead = in_array($user->jabatan, ['section_head', 'departemen_head'], true);
        // MD bukan `jabatan` melainkan ROLE (jabatan-nya NULL), jadi ketiga
        // bendera di atas semuanya false untuknya dan dashboard-nya jatuh ke
        // cabang "Admin/Non-Staff" yang kopong. Dibaca dari IZIN, bukan
        // hasRole(): nama role bisa berubah, wewenangnya tidak.
        $isMd = $user->can('document.review_md');

        // "Perlu Revisi" (GL) = dokumen DITOLAK + draft AJUKAN REVISI (revises_document_id),
        // identik dgn menu "Dokumen Revisi" (DocumentController::revisi).
        $needRevision = Document::where('created_by', $user->id)
            ->where(fn ($q) => $q->where('status', 'rejected')
                ->orWhere(fn ($w) => $w->whereNotNull('revises_document_id')->where('status', 'draft')))
            ->count();

        $stats = [
            'total' => $visible()->count(),
            // Kartu GL "Dokumen Saya" — samakan dgn daftar (index): buatannya, kecuali
            // versi sedang_direvisi/obsolete (yg disembunyikan di daftar).
            'my_documents' => Document::where('created_by', $user->id)
                ->whereNotIn('status', ['sedang_direvisi', 'obsolete'])->count(),
            // Dokumen se-departemen (SH/DH) — dipakai kartu ke-2 menggantikan "Dokumen Saya" (#4).
            'dept_documents' => $user->department_id
                ? Document::where('department_id', $user->department_id)->count()
                : 0,
            'published' => $visible()->where('status', 'published')->count(),
            'need_revision' => $needRevision,                                                      // GL: kartu "Perlu Revisi"
            'rejected' => Document::where('created_by', $user->id)->where('status', 'rejected')->count(), // GL: kartu "Dokumen Ditolak"
            'sedang_direvisi' => $visible()->where('status', 'sedang_direvisi')->count(),          // SH/DH/PJO: kartu "Sedang Revisi"
            // Sedang/menunggu ditinjau (waiting_for_review + in_review) dlm lingkup terlihat.
            // PJO: kartu "Sedang Tinjau" (7 dept); SH/DH: kartu "Menunggu Ditinjau" (dept) (#3/#4).
            'menunggu_tinjau' => $visible()->whereIn('status', ['waiting_for_review', 'in_review'])->count(),
        ];

        /*
         * Angka antrean per peran — kini dari AntreanTugas, sumber yang SAMA
         * dengan badge sidebar & modal login. Sebelumnya dihitung di sini
         * sendiri, dan salah satu rumusnya sudah menyimpang: penjaga `tinjau`
         * memakai izin `document.review` saja, sehingga GL SHE (yang
         * meninjau JSA lewat `document.review_jsa`) tak pernah melihat angkanya.
         *
         * Nama kunci view (`review`/`approval`) DIPERTAHANKAN:
         * dashboard.blade sudah membacanya di banyak titik, dan menggantinya tak
         * membeli apa pun.
         */
        $antrean = app(\App\Services\AntreanTugas::class)->untuk($user);
        $queues = [];
        // `akun` tak lagi ikut: dashboard sudah tak menyebut antrean akun
        // di mana pun. Menu & badge sidebar Persetujuan Akun tetap hidup,
        // keduanya membaca AntreanTugas langsung.
        foreach (['review' => 'tinjau', 'approval' => 'setujui', 'review_md' => 'tinjau_md'] as $kunciView => $kunciAntrean) {
            $queues[$kunciView] = $antrean[$kunciAntrean]['jumlah'] ?? 0;
        }

        /*
         * Overview Dokumen — dua deret: DIBUAT vs BERLAKU, dalam TIGA durasi
         * (harian/mingguan/bulanan) yang bisa ditukar pembaca.
         *
         * Satu deret hanya menjawab "berapa banyak yang ditulis". Dua deret
         * menjawab pertanyaan yang sebenarnya dipakai pimpinan: seberapa banyak
         * yang ditulis akhirnya benar-benar disahkan.
         *
         * Ketiga durasi dihitung sekaligus di sini lalu ditukar di sisi klien:
         * mengganti sumbu X tak perlu rute AJAX tersendiri. Sumbernya SATU
         * query (bukan enam GROUP BY) dan pengelompokannya di PHP — dokumen
         * mutu di rentang ini berjumlah ratusan, jadi memindahkannya ke MySQL
         * tak membeli apa pun.
         * ponytail: pengelompokan O(baris × ember); pindahkan ke GROUP BY bila
         * dokumen dalam 8 bulan sudah puluhan ribu.
         */
        $awalTren = now()->startOfMonth()->subMonths(7);
        $barisTren = $visible()
            ->where(fn ($q) => $q->where('created_at', '>=', $awalTren)->orWhere('published_at', '>=', $awalTren))
            ->get(['created_at', 'published_at', 'status']);

        // "Tertinjau" = sudah LEWAT tahap tinjauan. Tak ada kolom reviewed_at,
        // jadi statuslah penandanya: semua yang sudah melampaui in_review.
        $lolosTinjau = ['verifikasi_md', 'pending_approval', 'published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete'];

        /*
         * Meter per PERAN (rencana pra-produksi Fase 10, keputusan K-C).
         *
         * Penyebutnya TETAP sama untuk semua peran — dokumen yang DIBUAT pada
         * periode terpilih, dalam lingkup hak akses pembaca. Yang berbeda
         * pembilangnya: tiap peran membaca anak tangganya sendiri di alur yang
         * sama. Ketiga himpunan bersarang (dibuat ⊇ tertinjau ⊇ disetujui),
         * jadi angkanya selalu ≤ 100% dan turun berurutan.
         *
         * Dipilih SEKALI di sini, dari bendera peran yang sudah dihitung di
         * atas — bukan bercabang di TSX (CLAUDE.md §4: nol aturan peran di
         * klien). Labelnya ikut ke props supaya judul kartu, tulisan di dalam
         * busur, dan kalimat kakinya tak bisa berbeda satu sama lain.
         */
        $meter = match (true) {
            // GL menulis; anak tangganya "sudah keluar dari draft".
            $isCreator => [
                'label' => 'Dibuat',
                'kalimat' => 'sudah keluar dari draft',
                'status' => ['waiting_for_review', 'in_review', 'rejected', ...$lolosTinjau],
            ],
            // PJO menyetujui; anak tangganya yang sudah LEWAT persetujuan.
            $isPjo => [
                'label' => 'Disetujui',
                'kalimat' => 'sudah disetujui',
                'status' => ['published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete'],
            ],
            // MD memeriksa sistematika penulisan; anak tangganya yang sudah
            // LEWAT pemeriksaan MD (rencana DASBOR-V4 §A.3 — sebelumnya jatuh
            // ke `default` dan salah membaca label/angka milik SH).
            $isMd => [
                'label' => 'Diperiksa',
                'kalimat' => 'sudah melewati pemeriksaan MD',
                'status' => ['pending_approval', 'published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete'],
            ],
            // SH/DH meninjau; Admin & Non-Staff memakai lensa yang sama.
            default => [
                'label' => 'Tertinjau',
                'kalimat' => 'sudah melewati tinjauan',
                'status' => $lolosTinjau,
            ],
        };

        $deret = function (int $jumlah, string $satuan, string $kunciFmt, string $labelFmt) use ($barisTren, $meter) {
            $set = ['labels' => [], 'dibuat' => [], 'berlaku' => [], 'total' => 0, 'tertinjau' => 0];
            for ($i = $jumlah - 1; $i >= 0; $i--) {
                $t = now()->sub($satuan, $i)->startOf($satuan);
                $kunci = $t->format($kunciFmt);
                $dibuat = $barisTren->filter(fn ($d) => $d->created_at?->format($kunciFmt) === $kunci);
                $set['labels'][] = $t->translatedFormat($labelFmt);
                $set['dibuat'][] = $dibuat->count();
                $set['berlaku'][] = $barisTren->filter(fn ($d) => $d->published_at?->format($kunciFmt) === $kunci)->count();
                $set['total'] += $dibuat->count();
                $set['tertinjau'] += $dibuat->whereIn('status', $meter['status'])->count();
            }
            $set['berlakuTotal'] = array_sum($set['berlaku']);
            // Meter growth: berapa persen dokumen periode ini sudah mencapai
            // tahap milik peran pembaca ({@see $meter}).
            $set['growth'] = $set['total'] > 0 ? (int) round($set['tertinjau'] / $set['total'] * 100) : 0;
            // Aditif (§P2): V1 mengabaikan kedua kunci ini. Ikut di KETIGA
            // durasi — kalau hanya satu yang diisi, mengganti rentang akan
            // mengosongkan judul kartunya.
            $set['meterLabel'] = $meter['label'];
            $set['meterKeterangan'] = "{$set['tertinjau']} dari {$set['total']} dokumen {$meter['kalimat']}";

            return $set;
        };

        $tren = [
            'hari' => $deret(14, 'day', 'Y-m-d', 'd M') + ['rentang' => '14 hari terakhir'],
            'minggu' => $deret(8, 'week', 'o-W', 'd M') + ['rentang' => '8 minggu terakhir'],
            'bulan' => $deret(8, 'month', 'Y-m', 'M') + ['rentang' => '8 bulan terakhir'],
        ];

        // Distribusi dokumen: matriks status × jenis + total.
        $jenisList = DocumentType::kode();

        // Sebaran: berapa dokumen BERLAKU per JENIS. Dibatasi status `published`
        // (permintaan pemilik) — donatnya menjawab "apa yang berlaku di lapangan
        // sekarang", bukan berapa banyak draft yang sedang digarap. Sengaja tidak
        // dijumlahkan dari $matrix — matriks itu hanya memuat tujuh status yang
        // dipantau, sehingga totalnya selalu lebih kecil dari jumlah sebenarnya.
        $sebaranJenis = $visible()
            ->where('documents.status', 'published')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->groupBy('document_types.code')
            ->selectRaw('document_types.code AS j, COUNT(*) AS c')
            ->pluck('c', 'j');
        // Nama panjang diambil terpisah, bukan ikut GROUP BY: jenis yang belum
        // punya satu dokumen pun tetap wajib bernama, dan baris hasil agregat
        // tak pernah memuat jenis kosong.
        $namaJenis = DocumentType::whereIn('code', $jenisList)->pluck('name', 'code');
        $sebaran = [];
        foreach ($jenisList as $j) {
            [$ikon, $warna] = DocumentType::rupa($j);
            $sebaran[$j] = [
                'nama' => $namaJenis[$j] ?? $j,
                'jumlah' => (int) ($sebaranJenis[$j] ?? 0),
                'ikon' => $ikon,
                'warna' => $warna,
            ];
        }
        $matrixStatuses = [
            'published' => 'Berlaku', 'draft' => 'Draft', 'in_review' => 'Dalam Peninjauan',
            'verifikasi_md' => 'Verifikasi MD',
            'pending_approval' => 'Menunggu Persetujuan', 'rejected' => 'Ditolak', 'sedang_direvisi' => 'Sedang Direvisi',
        ];
        $rawMatrix = $visible()
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->selectRaw('documents.status AS s, document_types.code AS j, COUNT(*) AS c')
            ->groupBy('documents.status', 'document_types.code')
            ->get();
        $matrix = [];
        foreach ($matrixStatuses as $key => $label) {
            $row = ['label' => $label, 'status' => $key, 'per' => array_fill_keys($jenisList, 0), 'total' => 0];
            foreach ($rawMatrix->where('s', $key) as $r) {
                if (isset($row['per'][$r->j])) {
                    $row['per'][$r->j] = (int) $r->c;
                    $row['total'] += (int) $r->c;
                }
            }
            $matrix[] = $row;
        }

        // Sapaan personal berdasarkan jam WITA (§8).
        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default => 'Selamat malam',
        };

        // Aliran peristiwa 8 bulan dari audit_logs — SATU sapuan yang menyuapi
        // DUA pembaca: lencana kenaikan + sparkline keempat kartu KPI
        // (DasborTampilan::tiles) dan kartu Performa PIC (performaPic).
        // Dipanggil SEBELUM keduanya, dan cuma sekali.
        $alir = $this->alirAudit($user, $visible);

        /*
         * F2 DASBOR-V4 (keputusan D1): lencana pada 4 angka status yang
         * dipantau `LacakStatus` mengukur ALIRAN MASUK 30 hari, BUKAN
         * perubahan potret `$matrix` — larangan §5b DASBOR-V2 tetap berlaku,
         * yang berubah cuma apa yang diukur. `aliranKet` menyebut nama
         * alirannya supaya lencananya tak terbaca sebagai "draft bertambah 5%".
         * Nol query baru — dibaca dari `$alir['alir']` yang sudah dihitung.
         */
        $petaAliranMatrix = [
            'draft' => ['dibuat', 'netral', 'dibuat 30 hari'],
            'in_review' => ['dikirim', 'netral', 'masuk tinjauan 30 hari'],
            'pending_approval' => ['diloloskan', 'netral', 'diloloskan 30 hari'],
            'rejected' => ['ditolak', 'buruk', 'ditolak 30 hari'],
        ];
        foreach ($matrix as &$row) {
            [$kunciAlir, $arahBaris, $ket] = $petaAliranMatrix[$row['status']] ?? [null, null, null];
            if ($kunciAlir === null) {
                $row += ['delta' => null, 'deltaLabel' => null, 'arah' => null, 'aliranKet' => null];

                continue;
            }
            // `sorotan()` dipakai semata untuk arithmetic delta-nya; label/nilai
            // dari pemanggilan ini dibuang karena baris matrix sudah punya
            // labelnya sendiri.
            $s = $tampilan->sorotan('', '', $arahBaris, $alir['alir'][$kunciAlir]);
            $row += ['delta' => $s['delta'], 'deltaLabel' => $s['deltaLabel'], 'arah' => $arahBaris, 'aliranKet' => $ket];
        }
        unset($row);

        // Feed aktivitas terbaru dari audit_logs (bukan documents), difilter hak akses.
        $activityQuery = AuditLog::with('user', 'document')->latest('created_at');
        if (! $user->can('document.view_all')) {
            $activityQuery->where(fn ($q) => $q
                ->where('user_id', $user->id)
                ->orWhereHas('document', fn ($d) => $d->where('department_id', $user->department_id)));
        }

        return Inertia::render('Dashboard', [
            'user' => ['name' => $user->name],
            'greeting' => $greeting,
            // Kartu sambutan + empat kartu statistik + peta aksi feed: seluruhnya
            // DULU `@php` panjang di kepala `dashboard.blade.php`. Ketiganya
            // aturan per JABATAN, jadi rumahnya server (pakem P5), bukan TSX.
            'hero' => $tampilan->hero($user, $stats, $queues, $isCreator, $isPjo, $isDeptHead, $isMd),
            'tiles' => $tampilan->tiles($user, $stats, $queues, $isCreator, $isPjo, $isDeptHead, $isMd, $alir['alir']),
            // Dua widget kolom kanan (di bawah Log Aktivitas), BEDA per jabatan.
            'menungguDiMeja' => $this->menungguDiMeja($user),
            'masukanWidget' => $this->masukanWidget($user),
            // Jumlah PENUH masukan dengan penyaring yang SAMA PERSIS dengan
            // koleksi di atas — keduanya dibangun `masukanQuery()`, jadi angka
            // "Lihat N masukan lainnya" mustahil menyimpang dari isi kartunya.
            'masukanTotal' => $this->masukanQuery($user)?->count() ?? 0,
            // Sebaran dokumen Berlaku per DEPARTEMEN (D6) dan kartu Performa PIC
            // (D9). Gerbangnya BERBEDA sejak REVISI-UI-V3 §4.1/§4.2, dan itu
            // disengaja:
            //   sebaranDepartemen -> `document.view_all` (PJO, MD, Admin saja)
            //   performaPic       -> lingkupPic() (SH/DH ikut; GL & Non-Staff tidak)
            // `pages/V2/Dashboard.tsx` karena itu menggambar barisnya PER KARTU
            // dan memberi ganjal saat hanya satu yang terisi — dulu keduanya
            // digerbangi `dashboardPenuh()` yang sama, sehingga barisnya cukup
            // diuji sepasang.
            'sebaranDepartemen' => $this->sebaranDepartemen($user, $visible, $jenisList, $alir, $tampilan),
            // F4a DASBOR-V4: pasangan SH/DH atas `sebaranDepartemen` — gerbangnya
            // BEDA (§sebaranJenisDept), jadi keduanya tak pernah bukan-null sekaligus.
            'sebaranJenisDept' => $this->sebaranJenisDept($isDeptHead, $matrix, $jenisList, $namaJenis, $alir, $tampilan),
            'performaPic' => $this->performaPic($user, $visible, $alir, $tampilan),
            // F6 DASBOR-V4 — "Aktivitas Terbaru" gaya Recent Projects, HANYA
            // PJO/MD/Admin. `aktivitasWidget` null bagi yang lain sekaligus,
            // supaya keempat kunci di bawah tak pernah setengah terisi.
            'aktivitasTabel' => ($aktivitasWidget = $this->aktivitasTabel($user, $request)) ? $aktivitasWidget['tabel'] : null,
            'aktivitasFilters' => $aktivitasWidget['filters'] ?? null,
            'aktivitasDepartemen' => $aktivitasWidget['departemen'] ?? [],
            'aktivitasStatusOpsi' => $aktivitasWidget['statusOpsi'] ?? [],
            // Widget Distribusi (v5 Fase C) — hanya bagi yang bisa
            // menindaklanjuti cakupan rendah. Bagi yang lain nilainya null dan
            // kartunya tak dirender sama sekali (bukan dirender kosong).
            'distribusiWidget' => $this->distribusiWidget($user),
            // Kartu "Ketersediaan Saya" (FITUR-BARU-v4 §6) — kini KALENDER BULAN
            // BERJALAN. Pita 14 hari & meteran beban dilepas: keduanya menjawab
            // "bisakah saya dipilih jadi peninjau", sedangkan pertanyaan kartu
            // ini cuma "kapan saya libur". Papan pemilihan peninjau tetap
            // memakai ReviewerAvailability, jadi tak ada rumus yang hilang.
            // ?bulan=YYYY-MM — tombol bulan berupa TAUTAN biasa, bukan JS.
            'kalenderOff' => $this->kalenderOff($user, $request->query('bulan')),
            'offSaya' => $user->offDays()->belumBerakhir()->orderBy('mulai')->get()
                ->map(fn (UserOffDay $off) => [
                    'id' => $off->id,
                    'jenis' => $off->jenisLabel(),
                    'rentang' => $off->rentangLabel(),
                    // Tanggal MENTAH 'Y-m-d' di samping `rentang` yang berupa
                    // LABEL: tab "Jadwal Saya" kalender V2 perlu mencocokkan
                    // agenda dengan sel tanggal, dan mengurai kembali label
                    // Indonesia ("3–5 Sep") untuk mendapatkannya adalah cara
                    // paling rapuh yang tersedia. Labelnya tetap milik server —
                    // TSX tak boleh memformat ulang tanggal ini jadi kalimat.
                    'mulai' => $off->mulai->toDateString(),
                    'sampai' => $off->sampai->toDateString(),
                    'catatan' => $off->catatan,
                    'urlBatal' => route('off.destroy', $off),
                ])->all(),
            // Off yang SEDANG berjalan hari ini — penentu munculnya tombol
            // "On Site" (= saya sudah kembali, batalkan off ini).
            'offAktif' => ($aktif = $user->offDays()->aktifPada(now())->first())
                ? ['id' => $aktif->id, 'urlBatal' => route('off.destroy', $aktif)]
                : null,
            'stats' => $stats,
            'queues' => $queues,
            'isCreator' => $isCreator,
            'isPjo' => $isPjo,
            'isDeptHead' => $isDeptHead,
            'isMd' => $isMd,
            // Bendera kelima, dan alasannya sama dengan keempat di atas: kartu
            // Masukan punya DUA wajah ("Masukan Saya" vs "Masukan Lapangan")
            // dan yang memilihnya adalah JABATAN. Dikirim sebagai bendera,
            // bukan disimpulkan di TSX dari `auth.user.jabatan === 'staff'` —
            // kunci mentah `staff` tak boleh jadi aturan yang hidup di klien
            // (CLAUDE.md §6).
            'isNonStaff' => $user->jabatan === User::JABATAN_STAFF,
            // 15 poin: kartunya kini DIGULIR di dalam tinggi tetap, jadi jumlah
            // baris tak lagi menentukan tinggi halaman — dan lima baris di
            // kartu yang bisa digulir cuma memamerkan gulir yang tak pernah
            // terpakai.
            'activities' => $tampilan->aktivitas($activityQuery->limit(15)->get()),
            'tren' => $tren,
            'matrix' => $matrix,
            'jenisList' => $jenisList,
            'sebaran' => $sebaran,
            // Konstanta & rute yang dulu dibaca `_ketersediaan.blade.php`
            // langsung dari kelas modelnya.
            'jenisOff' => UserOffDay::JENIS_LABELS,
            'urlOffStore' => route('off.store'),
        ]);
    }

    /**
     * Kalender satu bulan untuk kartu ketersediaan.
     *
     * Satu sel per tanggal, didahului sel kosong secukupnya supaya tanggal 1
     * jatuh tepat di kolom harinya. Nama hari dibangkitkan dari tanggal
     * sungguhan (bukan ditulis tetap "Min…Sab") agar ikut pelokalan.
     *
     * Off yang diambil adalah yang BERSINGGUNGAN dengan bulan itu — termasuk
     * yang sudah lewat. Memakai `belumBerakhir()` akan membuat kalender bulan
     * berjalan bercerita separuh: cuti tanggal 3 lenyap begitu tanggal 4 tiba.
     *
     * @param  ?string  $bulan  'Y-m' dari querystring; di luar rentang wajar
     *                          (12 bulan ke belakang, 12 ke depan) diabaikan
     *                          dan kembali ke bulan berjalan. Batas itu bukan
     *                          gaya-gayaan: tanpa itu `?bulan=9999-12` membuat
     *                          halaman menghitung kalender yang tak berguna
     *                          bagi siapa pun.
     * @return array{judul: string, namaHari: array<int, string>, sel: array<int, ?array{tanggal: \Carbon\Carbon, off: ?string}>, sebelum: string, sesudah: string, iniBulanIni: bool}
     */
    private function kalenderOff(User $user, ?string $bulan = null): array
    {
        $acuan = now()->startOfMonth();
        $pilihan = null;
        if ($bulan && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $pilihan = \Carbon\Carbon::createFromFormat('Y-m-d', $bulan.'-01')?->startOfMonth();
            if ($pilihan->lt($acuan->copy()->subMonths(12)) || $pilihan->gt($acuan->copy()->addMonths(12))) {
                $pilihan = null;
            }
        }

        $awal = ($pilihan ?? $acuan)->copy();
        $akhir = $awal->copy()->endOfMonth();

        $tanggalOff = [];
        foreach ($user->offDays()->bertindih($awal->toDateString(), $akhir->toDateString())->get() as $off) {
            // ->copy(): max()/min() bisa mengembalikan $awal/$akhir ITU SENDIRI,
            // dan menyerahkan objek yang masih dipakai ke CarbonPeriod adalah
            // undangan bagi bug yang sulit dilihat.
            foreach (\Carbon\CarbonPeriod::create($off->mulai->max($awal)->copy(), $off->sampai->min($akhir)->copy()) as $hari) {
                $tanggalOff[$hari->toDateString()] = $off->jenisLabel().' · '.$off->rentangLabel();
            }
        }

        // Tanggal dikirim sebagai 'Y-m-d' (bukan objek Carbon): payload Inertia
        // adalah JSON, dan Carbon yang diserialkan sendiri berakhir jadi tanggal
        // ISO ber-zona UTC — tepat pergeseran 8 jam yang membuat sel tanggal 1
        // WITA terbaca sebagai tanggal 31 bulan sebelumnya di klien.
        $sel = array_fill(0, $awal->dayOfWeek, null);   // dayOfWeek: 0 = Minggu
        foreach (\Carbon\CarbonPeriod::create($awal, $akhir) as $hari) {
            $sel[] = [
                'tanggal' => $hari->toDateString(),
                'angka' => (int) $hari->format('j'),
                'off' => $tanggalOff[$hari->toDateString()] ?? null,
                'iniHariIni' => $hari->isToday(),
                // Off kemarin ditolak server (`after_or_equal:today`), jadi
                // tanggal lewat tak bisa ditekan — membiarkannya cuma
                // menjanjikan jalan yang pasti buntu.
                'lewat' => $hari->lt(now()->startOfDay()),
                'judul' => $hari->translatedFormat('l, j F'),
            ];
        }

        $minggu = now()->startOfWeek(\Carbon\CarbonInterface::SUNDAY);

        return [
            'judul' => $awal->translatedFormat('F Y'),
            'namaHari' => array_map(fn ($i) => $minggu->copy()->addDays($i)->translatedFormat('D'), range(0, 6)),
            'sel' => $sel,
            // Tetangga kiri-kanan untuk tombol bulan. Dihitung di sini, bukan di
            // view: batas 12 bulan yang dipakai memvalidasi masukan dan batas
            // yang menonaktifkan tombolnya harus satu angka, bukan dua.
            'sebelum' => $awal->copy()->subMonth()->format('Y-m'),
            'sesudah' => $awal->copy()->addMonth()->format('Y-m'),
            'bisaMundur' => $awal->gt($acuan->copy()->subMonths(12)),
            'bisaMaju' => $awal->lt($acuan->copy()->addMonths(12)),
            'iniBulanIni' => $awal->equalTo($acuan),
            // URL-nya dibangun di sini juga: tombol bulan tetap TAUTAN biasa
            // (`<Link>` Inertia), bukan pemanggilan JavaScript — kalender ini
            // dibaca, bukan dimainkan, dan tautannya harus bisa dibuka di tab
            // baru serta di-bookmark seperti dulu.
            'urlSebelum' => route('dashboard', ['bulan' => $awal->copy()->subMonth()->format('Y-m')]),
            'urlSesudah' => route('dashboard', ['bulan' => $awal->copy()->addMonth()->format('Y-m')]),
            'urlHariIni' => route('dashboard'),
        ];
    }

    /**
     * "Perjalanan Dokumen" — dokumen yang sedang BERJALAN, beserta umurnya.
     *
     * Yang layak diingat dari kartu ini bukan daftarnya melainkan **berapa lama
     * sebuah dokumen diam**. Karena itu tiap baris membawa tahap sekarang dan
     * umur dalam hari; widget-nya yang menerjemahkan jadi rel bersegmen.
     *
     * Lingkupnya per jabatan:
     *   • GL      — buatannya sendiri ATAU yang ia bantu susun (pola yang SAMA
     *               dengan DocumentController::index(), supaya isi kartu tak
     *               pernah berbeda dari isi menu "Dokumen Saya").
     *   • SH/DH   — sedepartemen.
     *   • PJO/Adm — 7 departemen.
     *   • Non-Staff — kosong; ia mendapat kartu "Masukan Saya".
     *
     * @return \Illuminate\Support\Collection<int, array{dokumen:Document, pemegang:?string, umur:int}>
     */
    private function menungguDiMeja(User $user): \Illuminate\Support\Collection
    {
        /*
         * Status "berjalan" = sudah dikirim dan belum terbit.
         *
         * `rejected` IKUT: dokumen yang dikembalikan tidak selesai, ia MUNDUR ke
         * tangan pembuatnya. Dulu ia hilang begitu saja dari kartu ini, sehingga
         * dokumen yang paling butuh didorong justru yang tak terlihat.
         */
        $berjalan = ['waiting_for_review', 'in_review', 'verifikasi_md', 'pending_approval', 'rejected'];

        // Singkatan jabatan pemegangnya ("SH", "MD", "GL") — itulah yang membuat
        // "Menunggu tinjauan" berubah jadi kalimat yang bisa ditindaklanjuti.
        $singkat = fn (?User $u) => $u ? (User::JABATAN_SHORT[$u->jabatan] ?? '') : '';

        // `creator` ikut dimuat: tiap baris kini dipimpin WAJAH pembuatnya, dan
        // tanpa eager load lima baris berarti lima query tambahan.
        $query = Document::with('type', 'department', 'reviewer', 'approver', 'creator')
            ->whereIn('status', $berjalan);

        if ($user->can('document.view_all')) {
            // PJO & Admin: 7 departemen, tanpa penyaringan tambahan.
        } elseif (in_array($user->jabatan, ['section_head', 'departemen_head'], true)) {
            $query->where('department_id', $user->department_id);
        } elseif ($user->jabatan === User::JABATAN_GROUP_LEADER) {
            $query->where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('authors', fn ($a) => $a->where('user_id', $user->id)));
        } else {
            return collect();   // Non-Staff tak pernah punya dokumen di meja siapa pun.
        }

        // TEPAT 4 baris (spec v2 L1): kartunya bersanding dengan kalender, dan
        // empat baris itulah yang membuat tepi bawahnya jatuh pas di batas
        // kalender–Log Aktivitas. Baris kelima merusak kesejajaran itu.
        return $query->orderBy('submitted_at')->limit(self::BARIS_MEJA)->get()->map(function (Document $d) use ($singkat) {
            // Tahap yang sedang dijalani — penentu panjang pita. `rejected`
            // sengaja kembali ke 'Dibuat': dokumen yang dikembalikan memang
            // MUNDUR ke meja penyusunnya, dan pitanya harus ikut memendek.
            $tahap = match ($d->status) {
                'waiting_for_review', 'in_review' => 'Ditinjau',
                'verifikasi_md' => 'MD',
                'rejected' => 'Dibuat',
                default => 'Disetujui',
            };

            /*
             * Daftar tahap & panjang pita DULU dihitung di dalam
             * `partials/_widget-meja.blade.php`. Ia pindah ke sini karena
             * `perluTinjauanMd()` adalah aturan alur dokumen — segmen MD hanya
             * muncul bila jenis ini memang melewatinya (saat ini SOP), sehingga
             * JSA tak pernah dijanjikan tahap yang tak ada dalam alurnya. Aturan
             * seperti itu tak boleh punya salinan kedua di TSX (pakem P5).
             */
            $daftarTahap = array_values(array_filter([
                'Dibuat', 'Ditinjau', $d->perluTinjauanMd() ? 'MD' : null, 'Disetujui',
            ]));
            $i = array_search($tahap, $daftarTahap, true);
            $ke = ($i === false ? 1 : $i) + 1;

            $umur = (int) ($d->submitted_at ?? $d->updated_at)?->diffInDays(now());

            return [
                /*
                 * HANYA `id` (§P4). Dulu di sini model `Document` UTUH, dan
                 * relasi `creator` ikut dimuat di atas — artinya seluruh kolom
                 * User pembuat (`email`, `nrp`, `jabatan`, `department_id`, …)
                 * terserialkan bulat-bulat ke atribut `data-page` dan terbaca
                 * lewat "view source". `User::$hidden` cuma menutup `password`
                 * dan `remember_token`, jadi ia tak pernah menolong di sini.
                 *
                 * Kontraknya sendiri sudah menyatakan yang benar sejak awal —
                 * `types/dasbor.d.ts` menulis `dokumen: { id: number }` — dan
                 * satu-satunya pembacaan di TSX adalah `b.dokumen.id`. Jadi ini
                 * menyamakan kiriman dengan kontrak yang sudah tertulis,
                 * sekaligus mengecilkan muatannya.
                 */
                'dokumen' => ['id' => $d->id],
                // Tahap MD sengaja TANPA nama: akunnya bersama, jadi menyebut
                // satu orang justru menyesatkan.
                'pemegang' => match ($d->status) {
                    'waiting_for_review', 'in_review' => $d->reviewer?->name,
                    'pending_approval' => $d->approver?->name,
                    'rejected' => $d->creator?->name,
                    default => null,
                },
                // Menunggu APA, di tangan SIAPA. "Menunggu tinjauan SH" menyuruh
                // orang bertindak; "50%" tidak menyuruh apa-apa.
                'menunggu' => trim(match ($d->status) {
                    'waiting_for_review', 'in_review' => 'Menunggu tinjauan '.$singkat($d->reviewer),
                    'verifikasi_md' => 'Menunggu tinjauan MD',
                    'pending_approval' => 'Menunggu persetujuan '.$singkat($d->approver),
                    'rejected' => 'Menunggu revisi '.$singkat($d->creator),
                    default => 'Menunggu tindak lanjut',
                }),
                'tahap' => $tahap,
                'umur' => $umur,
                // Status MENTAH — pemasok `StatusBadge` di tabel Lacak Status
                // (§5b). Labelnya & warnanya tetap datang dari prop bersama
                // `statusMeta`, jadi yang dikirim di sini cuma kuncinya.
                'status' => $d->status,
                // Tanggal dokumen ini MASUK ke meja yang sekarang memegangnya —
                // sumbernya PERSIS sama dengan $umur di atas, supaya tanggal di
                // kalender dan angka umur di tabel tak pernah bercerita beda.
                'sejak' => ($d->submitted_at ?? $d->updated_at)?->toDateString(),
                // Umur menghangat sendiri: <3 hari hijau, 3–6 kuning, ≥7 merah.
                'tingkat' => $umur >= 7 ? 'lama' : ($umur >= 3 ? 'sedang' : 'baru'),
                'daftarTahap' => $daftarTahap,
                'ke' => $ke,
                'persen' => (int) round($ke / count($daftarTahap) * 100),
                'judul' => $d->title,
                'nomor' => $d->displayNumber(),
                'tautan' => route('documents.show', $d),
                'dept' => $d->department?->code,
                'pembuat' => [
                    'nama' => $d->creator?->name ?? 'Tidak diketahui',
                    'jabatan' => $d->creator ? $d->creator->jabatanLabel() : '—',
                    'foto' => $d->creator?->photoUrl(),
                ],
            ];
        });
    }

    /**
     * Kartu masukan lapangan, tiga wajah:
     *   • SH/DH & GL — masukan SEDEPARTEMEN yang belum ditindak. Lingkupnya
     *     sama dengan yang mereka lihat di menu "Dokumen Berlaku", karena
     *     masukan memang selalu menempel pada dokumen Berlaku. Bedanya
     *     wewenang, bukan pandangan: SH/DH yang membalas & menutup, GL
     *     membacanya sebagai penyusun dokumen yang dikomentari.
     *   • PJO — masukan yang belum ditindak dari KETUJUH departemen. Ia tanpa
     *     departemen, jadi menyaring `department_id` justru selalu mengosongkan
     *     kartunya (CLAUDE.md §6). Baginya juga bacaan: yang membalas tetap
     *     SH/DH departemennya.
     *   • Non-Staff — kiriman sendiri beserta balasannya.
     * Admin tak mendapat kartu ini.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, isi: string, pengirim: string,
     *                                                   nomor: ?string, tautan: ?string, umur: ?string,
     *                                                   status: string, balasan: ?string}>
     */
    private function masukanWidget(User $user): \Illuminate\Support\Collection
    {
        $query = $this->masukanQuery($user);

        if ($query === null) {
            return collect();
        }

        /*
        | Batasnya dari konfigurasi, bukan angka di sini. Dulu 4 — dan itu bukan
        | sekadar sedikit: dengan grid dua kolom, empat kutipan hanya mengisi dua
        | baris, sehingga kartunya tak pernah bisa meluap dan "bisa digulir"
        | mustahil dipenuhi. Kartunya kini menggulung sendiri, jadi angka ini
        | membatasi ONGKOS QUERY, bukan tinggi tampilannya.
        */
        $batas = (int) config('smartpro.dashboard.masukan_widget', 20);

        return $this->masukanBaris(
            // `document.creator` ikut karena tiap kartu menyebut PEMILIK
            // dokumennya. Yang dikirim ke props cuma NAMANYA (lihat
            // masukanBaris()) — relasinya dimuat di sini semata supaya nama itu
            // tak menimbulkan satu query per baris.
            $query->with('document.creator', 'user')->latest()->limit($batas)->get(),
            $user,
        );
    }

    /**
     * Penyaring kartu masukan — SATU tempat, dua pemakai.
     *
     * Dipisahkan dari {@see masukanWidget()} karena footer kartunya menyebut
     * jumlah PENUH ("Lihat N masukan lainnya"), dan jumlah itu wajib memakai
     * penyaring yang sama persis dengan koleksinya. Dua salinan syarat yang
     * sama adalah dua salinan yang suatu hari berselisih — dan selisihnya
     * muncul sebagai angka yang tak pernah cocok dengan isi kartu, tanpa satu
     * pun gerbang merah.
     *
     * `null` = peran ini memang tak mendapat kartu masukan sama sekali (Admin
     * tanpa `document.view_all`), dan itu BEDA dari "mendapat kartu yang
     * kebetulan kosong".
     *
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\DocumentFeedback>|null
     */
    private function masukanQuery(User $user): ?\Illuminate\Database\Eloquent\Builder
    {
        if ($user->jabatan === User::JABATAN_STAFF) {
            return \App\Models\DocumentFeedback::query()->where('user_id', $user->id);
        }

        $sedepartemen = in_array($user->jabatan, [
            User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD, User::JABATAN_GROUP_LEADER,
        ], true);
        // Lintas departemen = siapa pun yang memang melihat seluruh dokumen:
        // PJO, Admin, dan MD. Sebelumnya hanya PJO yang disebut, sehingga Admin
        // & MD (jabatan NULL) pulang dengan koleksi kosong.
        if (! $sedepartemen && ! $user->can('document.view_all')) {
            return null;
        }

        return \App\Models\DocumentFeedback::query()
            ->belumDitindak()
            ->when($sedepartemen, fn ($q) => $q->whereHas('document', fn ($d) => $d->where('department_id', $user->department_id)));
    }

    /**
     * Satu bentuk baris untuk KEDUA wajah kartu masukan.
     *
     * `displayNumber()`, `statusLabel()`, dan `diffForHumans()` dulu dipanggil
     * langsung di Blade. Ketiganya milik model/PHP, jadi ia diratakan di sini
     * sekali — bukan dikirim sebagai model mentah lalu diterjemahkan lagi di TSX.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\DocumentFeedback>  $daftar
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function masukanBaris(\Illuminate\Support\Collection $daftar, User $user): \Illuminate\Support\Collection
    {
        return $daftar->map(fn (\App\Models\DocumentFeedback $m) => [
            'id' => $m->id,
            'isi' => $m->isi,
            'pengirim' => $m->user->name ?? '—',
            // NAMA pembuat dokumennya, bukan modelnya. Mengirim `creator` utuh
            // adalah kebocoran yang persis sudah terjadi di Fase 8 migrasi dan
            // dikunci DasborV2Test #3 (CLAUDE.md §4).
            'pemilik' => $m->document?->creator?->name ?? '—',
            'nomor' => $m->document?->displayNumber() ?? '—',
            'tautan' => $m->document ? route('documents.show', $m->document) : null,
            'umur' => $m->created_at?->diffForHumans(short: true),
            'status' => $m->statusLabel(),
            // Kunci MENTAH di samping labelnya — kartu V2 menyaring & menandai
            // per status, dan menyaring berdasarkan LABEL berarti penyaringnya
            // rusak begitu ada yang memperbaiki ejaan labelnya.
            'statusKunci' => $m->status,
            // Kosakata yang SAMA dengan DasborTampilan::AKSI (`maju`/`netral`/
            // `baik`/`buruk`), supaya titik hijau di kartu Masukan berarti hal
            // yang sama dengan titik hijau di garis waktu di sebelahnya.
            'rona' => match ($m->status) {
                'baru' => 'maju',
                'dibaca' => 'netral',
                'diadopsi' => 'baik',
                'ditolak' => 'buruk',
                default => 'netral',
            },
            // Aturannya SUDAH ada di modelnya (DocumentFeedback::bisaDibalasOleh) —
            // dipanggil, bukan disalin. Menyalin syaratnya ke sini berarti
            // batas departemen & status punya salinan kedua, dan salinan kedua
            // itulah yang dulu membuat PJO bisa menutup masukan tujuh
            // departemen (CLAUDE.md §4). `document` sudah ter-eager-load, jadi
            // nol query tambahan.
            'boleh_balas' => $m->bisaDibalasOleh($user),
            'balasan' => $m->balasan,
        ]);
    }

    /**
     * Aliran peristiwa 8 bulan dari `audit_logs` — SATU sapuan, DUA pembaca.
     *
     * Ia menyuapi lencana kenaikan + sparkline keempat kartu KPI (§5c) DAN
     * kartu Performa PIC (§5d). Satu query untuk keduanya, bukan satu per
     * kartu: `audit_logs` ter-indeks di `action`, `document_id`, `user_id`, dan
     * `created_at` — keempat kolom yang dipakai di sini — jadi yang mahal bukan
     * membacanya melainkan membacanya berulang kali.
     *
     * Pengelompokannya di PHP, bukan `GROUP BY`, dan itu keputusan yang SAMA
     * dengan `$barisTren` di atas beserta alasannya: satu sapuan menghasilkan
     * DELAPAN belas rekapitulasi berbeda (deret bulanan, jendela 30 hari,
     * jendela 30 hari sebelumnya, masing-masing dengan varian "saya"), dan
     * menyuruh MySQL menghitung semuanya berarti banyak query, bukan satu.
     * ponytail: satu lintasan O(baris); pindahkan ke GROUP BY bila audit 8
     * bulan sudah ratusan ribu baris.
     *
     * Daftar aksi yang disapu dibaca dari SATU konstanta
     * ({@see DasborTampilan::AKSI_ALIR}) supaya peta kartu dan peta query
     * mustahil berselisih — selisihnya tak akan terlihat, lencananya cuma diam
     * di nol selamanya (§P9).
     *
     * Rentangnya 8 bulan, sama dengan `$barisTren`, supaya sparkline KPI dan
     * grafik Overview di atasnya berbicara tentang jendela waktu yang sama.
     *
     * @return array{
     *   alir: array<string, array{deret: array<int, int>, kini: int, lalu: int}>,
     *   perOrang: array<int, array{nama: string, foto: ?string, jumlah: int, rincian: array<string, int>, ket: string}>,
     * }
     */
    private function alirAudit(User $user, Closure $visible): array
    {
        $awal = now()->startOfMonth()->subMonths(7);
        $batasKini = now()->subDays(30);
        $batasLalu = now()->subDays(60);

        $baris = AuditLog::query()
            ->where('created_at', '>=', $awal)
            ->whereIn('action', DasborTampilan::aksiDisapu())
            // Lingkup yang SAMA dengan seluruh angka dashboard lain. Tanpa ini
            // GL melihat lencana yang menghitung peristiwa di enam departemen
            // yang dokumennya sendiri tak pernah ia lihat.
            ->when(
                ! $user->can('document.view_all'),
                fn ($q) => $q->whereIn('document_id', $visible()->select('id')),
            )
            ->get(['action', 'user_id', 'created_at']);

        // Delapan ember bulanan, dari yang tertua. Bulan tanpa aktivitas tetap
        // 0 dan TIDAK dilewati: bulan sepi adalah informasi, bukan lubang, dan
        // sparkline yang melompati bulan kosong memampatkan sumbu waktunya.
        $bulan = [];
        for ($i = 7; $i >= 0; $i--) {
            $bulan[] = now()->startOfMonth()->subMonths($i)->format('Y-m');
        }

        // Aksi → kunci alir. Berlarik (bukan satu kunci) supaya satu aksi yang
        // suatu hari menyuapi dua kartu tidak diam-diam hilang dari salah satunya.
        $kunciDari = [];
        foreach (DasborTampilan::AKSI_ALIR as $kunci => $aksiSet) {
            foreach ($aksiSet as $aksi) {
                $kunciDari[$aksi][] = $kunci;
            }
        }

        // Tiap kunci punya kembaran `_saya` — dipakai kartu yang menghitung
        // milik pelakunya sendiri ("Dokumen Saya"), bukan lingkup terlihatnya.
        $kosong = ['deret' => array_fill_keys($bulan, 0), 'kini' => 0, 'lalu' => 0];
        $alir = [];
        foreach (array_keys(DasborTampilan::AKSI_ALIR) as $kunci) {
            $alir[$kunci] = $kosong;
            $alir[$kunci.'_saya'] = $kosong;
        }

        foreach ($baris as $r) {
            $ym = $r->created_at?->format('Y-m');
            $jendela = match (true) {
                $r->created_at >= $batasKini => 'kini',
                $r->created_at >= $batasLalu => 'lalu',
                default => null,
            };

            foreach ($kunciDari[$r->action] ?? [] as $kunci) {
                $sasaran = $r->user_id === $user->id ? [$kunci, $kunci.'_saya'] : [$kunci];
                foreach ($sasaran as $k) {
                    if (isset($alir[$k]['deret'][$ym])) {
                        $alir[$k]['deret'][$ym]++;
                    }
                    if ($jendela !== null) {
                        $alir[$k][$jendela]++;
                    }
                }
            }
        }

        // Sparkline membaca URUTAN, bukan label bulan — kuncinya dilepas di
        // sini supaya JSON-nya tak menggandakan delapan string per kartu.
        foreach ($alir as $k => $v) {
            $alir[$k]['deret'] = array_values($v['deret']);
        }

        return ['alir' => $alir, 'perOrang' => $this->wajahProduktif($user, $baris, $batasKini)];
    }

    /**
     * Siapa yang boleh muncul di kartu Performa PIC, dan wajah siapa saja.
     *
     * SATU tempat untuk gerbang itu, dibaca DUA pemanggil ({@see wajahProduktif()}
     * dan {@see performaPic()}) supaya keduanya mustahil berselisih — kartu yang
     * dirender dengan deret wajah kosong, dan deret wajah yang dikirim ke peran
     * yang kartunya tak dirender, sama-sama cacat.
     *
     * Aturannya (REVISI-UI-V3 §4.1, keputusan pemilik E8):
     *
     *   | peran                | kartu | wajah                          |
     *   |----------------------|-------|--------------------------------|
     *   | GL, Non-Staff        | tidak | —                              |
     *   | SH / DH              | ya    | GL di departemennya SENDIRI    |
     *   | PJO / MD / Admin IT  | ya    | GL + SH + DH, tujuh departemen |
     *
     * **URUTAN pemeriksaannya menentukan benar-salah, dan bukan selera.**
     * `document.view_all` diperiksa LEBIH DULU karena Admin IT dan MD ber-`jabatan`
     * NULL (`AdminUserSeeder`) — MD bahkan ber-`department_id` ICTMD. Kalau
     * jabatan diperiksa duluan, MD jatuh ke cabang terakhir dan KEHILANGAN
     * kartunya, sementara Admin tersasar ke cabang departemen. Memeriksa izin
     * lebih dulu membuat ketiganya benar tanpa satu pun pengecualian bernama.
     *
     * Izin itu pula yang dipakai, bukan daftar nama peran: `document.view_all`
     * adalah cara yang SUDAH dipakai di seluruh controller ini untuk menyatakan
     * "lintas tujuh departemen", dan pemegangnya persis Admin, PJO, dan MD
     * (`RolePermissionSeeder`).
     *
     * @return array{jabatan: list<string>, deptId: ?int}|null  null = kartunya tak dirender
     */
    private function lingkupPic(User $user): ?array
    {
        if ($user->can('document.view_all')) {
            return [
                'jabatan' => [
                    User::JABATAN_GROUP_LEADER,
                    User::JABATAN_SECTION_HEAD,
                    // DH setara SH (CLAUDE.md §6: "departemen_head = wewenang SH").
                    User::JABATAN_DEPARTEMEN_HEAD,
                ],
                'deptId' => null,
            ];
        }

        if (in_array($user->jabatan, [User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD], true)) {
            return ['jabatan' => [User::JABATAN_GROUP_LEADER], 'deptId' => $user->department_id];
        }

        // GL dan Non-Staff. GL DULU mendapat kartu ini (K1); pemilik
        // mencabutnya 2026-09-01 — performa orang lain bukan bacaan penyusun.
        return null;
    }

    /**
     * Deret wajah "Paling Produktif 30 Hari" (§5d) — dari sapuan yang SAMA.
     *
     * Dipisah dari {@see alirAudit()} semata supaya method itu tetap terbaca
     * sebagai satu hal; ia tak punya query sendiri kecuali pengambilan nama &
     * foto, dan itupun cuma bagi peran yang memang mendapat kartunya.
     *
     * **Kebocoran (§P4).** Yang keluar dari sini HANYA `nama`, `foto`,
     * `jumlah`, `rincian` (empat integer) dan `ket` (satu kalimat yang sudah
     * dirakit). Bukan model `User`, bukan koleksi `User`, tanpa `id`, `nrp`,
     * maupun `email` — kartunya memperlihatkan wajah dan nama orang, jadi ia
     * justru kartu yang paling gampang membocorkan satu tabel penuh ke atribut
     * `data-page`. `photoUrl()` dipanggil DI SINI karena ia menyusun URL disk
     * publik, dan tata letak storage tak punya urusan di peramban.
     *
     * Saringan jabatan/departemen dikerjakan DI QUERY `User`, bukan sesudahnya:
     * data yang tak berhak karena itu tak pernah dikirim, bukan dikirim lalu
     * disembunyikan.
     *
     * BUKAN papan peringkat: delapan teratas saja, tanpa nomor, dan tak ada
     * yang ditampilkan di posisi terbawah. Kartu yang memperlihatkan siapa
     * paling sedikit bekerja adalah kartu yang akan dipakai untuk hal yang
     * bukan urusan dashboard.
     *
     * @param  \Illuminate\Support\Collection<int, AuditLog>  $baris
     * @return array<int, array{nama: string, foto: ?string, jumlah: int, rincian: array<string, int>, ket: string}>
     */
    private function wajahProduktif(User $user, \Illuminate\Support\Collection $baris, \Carbon\Carbon $batasKini): array
    {
        // Larik KOSONG bagi peran yang kartunya memang tak dirender — gerbang
        // yang SAMA dengan performaPic(), dibaca dari satu tempat.
        if (($lingkup = $this->lingkupPic($user)) === null) {
            return [];
        }

        // Jendela 30 hari, sekali disaring, DUA pembaca: peringkat wajah
        // (`$hitung`, hanya aksi produktif) dan rincian aktivitas (`$rincianPer`,
        // seluruh aksi yang dipetakan RINCIAN_PIC — termasuk `dibuat`). Nol
        // query tambahan: keduanya membaca sapuan `alirAudit()` yang sama.
        $dalam30 = $baris
            ->where('created_at', '>=', $batasKini)
            ->whereNotNull('user_id');

        // Lingkup SESAMA-GL (SH/DH: hanya `group_leader`) memeringkat dengan
        // KUNCI_PRODUKTIF_GL — GL tak pernah meninjau/menyetujui, jadi
        // KUNCI_PRODUKTIF biasa selalu kosong baginya (D3 DASBOR-V4 F4b).
        // Lingkup lintas-departemen (PJO/MD/Admin) tetap KUNCI_PRODUKTIF.
        $aksiPeringkat = $lingkup['jabatan'] === [User::JABATAN_GROUP_LEADER]
            ? DasborTampilan::aksiProduktifGl()
            : DasborTampilan::aksiProduktif();

        $hitung = $dalam30
            ->whereIn('action', $aksiPeringkat)
            ->groupBy('user_id')
            ->map->count()
            ->sortDesc();

        // `dibuat` MASUK rincian tapi TIDAK masuk `$hitung` (K-H): kartunya
        // tetap bukan papan peringkat, dan seorang GL yang menulis lima puluh
        // draft tak boleh karena itu naik ke wajah teratas.
        $rincianPer = $dalam30
            ->groupBy('user_id')
            ->map(fn ($g) => DasborTampilan::rincian($g->pluck('action')->all()));

        if ($hitung->isEmpty()) {
            return [];
        }

        // SATU query, tapi `take(8)` menunggu SAMPAI SESUDAH saringan.
        // Kalau delapan teratas dipotong lebih dulu lalu disaring, sepuluh
        // pelaku teratas yang kebetulan PJO/MD akan menyisakan kartu berisi
        // satu wajah — padahal ada belasan GL yang memenuhi syarat di bawahnya.
        // Daftar `IN`-nya tak jadi masalah: pelaku berbeda dalam 30 hari
        // berjumlah puluhan, bukan ribuan.
        $orang = User::whereIn('id', $hitung->keys())
            ->whereIn('jabatan', $lingkup['jabatan'])
            ->when($lingkup['deptId'], fn ($q, $id) => $q->where('department_id', $id))
            ->get(['id', 'name', 'photo_path'])
            ->keyBy('id');

        return $hitung
            // Pelaku yang tak lolos saringan DIBUANG, termasuk yang akunnya
            // sudah dihapus. Dulu akun terhapus tetap tampil bernama "Pengguna
            // dihapus"; sejak kartunya menjanjikan JABATAN tertentu ia tak bisa
            // lagi ikut — jabatannya tak diketahui, dan menebaknya berarti
            // mengarang sementara meloloskannya berarti saringannya bohong.
            ->filter(fn (int $jumlah, $id) => $orang->has((int) $id))
            ->take(8)
            ->map(function (int $jumlah, $id) use ($orang, $rincianPer) {
                // Keempat kunci selalu ada (lihat DasborTampilan::rincian()),
                // jadi tak ada wajah yang bentuk rinciannya berbeda dari
                // tetangganya. `ket` dirakit SERVER — kosakata alurnya tak
                // pernah menyeberang ke TSX.
                $rincian = $rincianPer->get($id) ?? DasborTampilan::rincian([]);

                return [
                    'nama' => $orang->get((int) $id)->name,
                    'foto' => $orang->get((int) $id)->photoUrl(),
                    'jumlah' => $jumlah,
                    'rincian' => $rincian,
                    'ket' => DasborTampilan::ketRincian($rincian),
                ];
            })->values()->all();
    }

    /**
     * Sebaran dokumen BERLAKU per departemen (D6 §6d) — bar proporsi V2.
     *
     * `null` bagi SEMUA KECUALI PJO, MD, dan Admin IT (REVISI-UI-V3 §4.2,
     * keputusan pemilik E2/E4: "sebaran dokumen per dept hanya ada di MD dan
     * PJO"). Gerbangnya `can('document.view_all')` — pemegangnya persis ketiga
     * peran itu (`RolePermissionSeeder`), dan itulah cara yang SUDAH dipakai di
     * seluruh controller ini untuk menyatakan "lintas tujuh departemen". Kartu
     * yang isinya tujuh departemen memang cuma berarti bagi yang berwenang atas
     * ketujuhnya; bagi SH/DH ia enam kolom yang bukan urusannya.
     *
     * Ini MENCABUT gerbang `dashboardPenuh()` yang lama (K1) — sejak sekarang
     * ia TIDAK lagi sama dengan {@see performaPic()}, dan
     * `pages/V2/Dashboard.tsx` menggambar barisnya per kartu, bukan sepasang.
     *
     * Donat `KartuSebaran` (sebaran per JENIS dokumen) TIDAK ikut dicabut — ia
     * tetap milik semua peran. Namanya mirip, cakupannya tidak.
     *
     * Memakai closure `$visible` walau izin itu sudah membuka semuanya —
     * pertahanan berlapis, konsisten dengan `$sebaran`/`$matrix`.
     *
     * Arti "berlaku" diambil dari {@see Document::scopeBerlaku()}, TIDAK
     * didefinisikan ulang di sini. Itu sekaligus alasan bentuknya agregat-lalu-
     * isi-nol dan bukan satu `leftJoin` dari `departments`: syarat statusnya
     * milik scope, dan menuliskannya lagi sebagai kondisi join berarti arti
     * "berlaku" punya salinan kedua yang suatu hari tertinggal saat statusnya
     * bertambah. Yang dijanjikan leftJoin tetap dipenuhi — daftarnya dibangun
     * dari `departments`, jadi departemen ber-NOL dokumen tetap muncul dan
     * jumlah kotak legenda tak berubah dari hari ke hari.
     *
     * F3 DASBOR-V4: tiap baris mendapat `per` (jumlah per JENIS dokumen,
     * `document_types.code` — bukan per departemen kedua kalinya) supaya bar
     * bertumpuk gaya "Attendance Overview" bisa digambar tanpa query kedua.
     * Kepala kartu mendapat `persenTerbesar` + `delta`/`deltaLabel`/`arah`,
     * disuapi `alirAudit()['alir']['berlaku']` yang sudah dihitung sekali.
     *
     * @param  array<int, string>  $jenisList
     * @param  array{alir: array<string, array{deret: array<int, int>, kini: int, lalu: int}>}  $alir
     * @return array{total: int, persenTerbesar: int, delta: ?float, deltaLabel: ?string, arah: string, baris: array<int, array{kode: string, nama: string, jumlah: int, persen: int, per: array<string, int>}>}|null
     */
    private function sebaranDepartemen(User $user, Closure $visible, array $jenisList, array $alir, DasborTampilan $tampilan): ?array
    {
        if (! $user->can('document.view_all')) {
            return null;
        }

        $hitung = $visible()->berlaku()
            ->join('departments', 'documents.department_id', '=', 'departments.id')
            ->join('document_types', 'documents.document_type_id', '=', 'document_types.id')
            ->groupBy('departments.code', 'document_types.code')
            ->selectRaw('departments.code AS k, document_types.code AS j, COUNT(*) AS c')
            ->get();
        $perDept = $hitung->groupBy('k');

        // `Department::aktif()` SENGAJA tidak dipakai: menonaktifkan departemen
        // tak pernah berarti dokumennya hilang, dan menyembunyikan barisnya
        // membuat jumlah segmen bar tak lagi berjumlah 100% (Department.php:17).
        $nama = Department::orderBy('code')->pluck('name', 'code');
        $total = (int) $hitung->sum('c');

        $baris = [];
        foreach ($nama as $kode => $n) {
            $per = array_fill_keys($jenisList, 0);
            foreach ($perDept->get($kode) ?? [] as $r) {
                if (isset($per[$r->j])) {
                    $per[$r->j] = (int) $r->c;
                }
            }
            $jumlah = array_sum($per);
            $baris[] = [
                'kode' => $kode,
                'nama' => $n,
                'jumlah' => $jumlah,
                'persen' => $total > 0 ? (int) round($jumlah / $total * 100) : 0,
                'per' => $per,
            ];
        }

        // MENURUN — itu yang membuat ramp `--primary` di V2 dipakai secara
        // sekuensial (opasitas mengikuti peringkat), kebalikan dari cacat palet
        // kategorikal yang REDESAIN-UI-V2 §6 temukan. Seri diputus oleh KODE
        // supaya urutannya tak berubah-ubah antar-permintaan.
        usort($baris, fn (array $a, array $b) => [$b['jumlah'], $a['kode']] <=> [$a['jumlah'], $b['kode']]);

        $s = $tampilan->sorotan('', '', 'baik', $alir['alir']['berlaku']);

        return [
            'total' => $total,
            'persenTerbesar' => $baris[0]['persen'] ?? 0,
            'delta' => $s['delta'],
            'deltaLabel' => $s['deltaLabel'],
            'arah' => $s['arah'],
            'baris' => $baris,
        ];
    }

    /**
     * Sebaran dokumen departemen SENDIRI, per JENIS × STATUS (F4a DASBOR-V4).
     *
     * Mengisi kolom 2/3 baris ke-5 bagi SH/DH — sebelumnya ganjal `<div hidden>`
     * (REVISI-UI-V3 §4.6), sebab `sebaranDepartemen()` di atas gerbangnya
     * `document.view_all` dan SELALU `null` bagi SH/DH.
     *
     * **Nol query baru.** Dipivot dari `$matrix` yang sudah dihitung `index()`
     * (7 status × 6 jenis, sudah disaring `$visible()` — otomatis se-departemen
     * bagi SH/DH). Sumbunya DIBALIK dari `sebaranDepartemen()`: bar per JENIS
     * (SH/DH cuma satu departemen, jadi "per departemen" tak berarti apa-apa
     * di sini), tumpukan per STATUS — warnanya karena itu `statusMeta` di TSX,
     * bukan `--chart-*` jenis.
     *
     * `null` bagi SELAIN SH/DH: GL & Non-Staff tak dapat baris ke-5 sama sekali
     * (ganjalnya lenyap, bukan diisi), PJO/MD/Admin sudah punya
     * `sebaranDepartemen` lintas tujuh departemen.
     *
     * Kepala kartunya mendapat kunci yang SAMA PERSIS dengan
     * `sebaranDepartemen()` — `persen`, `persenTerbesar`, `delta`, `deltaLabel`,
     * `arah` — sebab kedua kartu itu satu komponen bagi mata pemilik dan hanya
     * berbeda sumbu (keputusan pemilik 2026-09-07: "samakan style-nya seperti
     * milik PJO"). Bedanya cuma APA yang diukur lencananya, dan itu memang
     * harus berbeda: `sebaranDepartemen` menghitung dokumen BERLAKU, sedangkan
     * kartu ini menghitung SELURUH status di satu departemen — jadi aliran yang
     * jujur menyuapinya adalah `dibuat` (`document.create`), bukan `berlaku`.
     * `$alir` sendiri sudah disaring `$visible()` di {@see alirAudit()},
     * sehingga angkanya otomatis se-departemen tanpa query kedua.
     *
     * @param  array<int, string>  $jenisList
     * @param  array<int, array{label: string, status: string, per: array<string, int>, total: int}>  $matrix
     * @param  \Illuminate\Support\Collection<string, string>  $namaJenis
     * @param  array{alir: array<string, array{deret: array<int, int>, kini: int, lalu: int}>}  $alir
     * @return array{total: int, persenTerbesar: int, delta: ?float, deltaLabel: ?string, arah: string, baris: array<int, array{kode: string, nama: string, jumlah: int, persen: int, per: array<string, int>}>}|null
     */
    private function sebaranJenisDept(bool $isDeptHead, array $matrix, array $jenisList, \Illuminate\Support\Collection $namaJenis, array $alir, DasborTampilan $tampilan): ?array
    {
        if (! $isDeptHead) {
            return null;
        }

        $baris = [];
        foreach ($jenisList as $j) {
            $per = [];
            foreach ($matrix as $row) {
                $per[$row['status']] = $row['per'][$j] ?? 0;
            }
            // `persen` diisi nol DULU, bukan ditempel sesudah totalnya
            // diketahui: URUTAN kuncinya ikut diuji sejajar `sebaranDepartemen`
            // (kedua kartu dijanjikan seragam), dan kunci yang ditempel
            // belakangan mendarat di ujung larik.
            $baris[] = [
                'kode' => $j,
                'nama' => $namaJenis[$j] ?? $j,
                'jumlah' => array_sum($per),
                'persen' => 0,
                'per' => $per,
            ];
        }

        $total = array_sum(array_column($baris, 'jumlah'));

        foreach ($baris as $i => $b) {
            $baris[$i]['persen'] = $total > 0 ? (int) round($b['jumlah'] / $total * 100) : 0;
        }

        // MENURUN, aturan yang sama dengan `sebaranDepartemen()` — dan seri
        // diputus oleh KODE supaya urutan batang tak berubah antar-permintaan
        // saat dua jenis berjumlah sama. Warnanya per STATUS (tumpukan), jadi
        // mengurutkan barisnya tidak memindahkan satu warna pun.
        usort($baris, fn (array $a, array $b) => [$b['jumlah'], $a['kode']] <=> [$a['jumlah'], $b['kode']]);

        $s = $tampilan->sorotan('', '', 'netral', $alir['alir']['dibuat']);

        return [
            'total' => $total,
            'persenTerbesar' => $baris[0]['persen'] ?? 0,
            'delta' => $s['delta'],
            'deltaLabel' => $s['deltaLabel'],
            'arah' => $s['arah'],
            'baris' => $baris,
        ];
    }

    /**
     * Widget "Aktivitas Terbaru" gaya Recent Projects (F6 DASBOR-V4) — HANYA
     * PJO/MD/Admin (`document.view_all`). `null` bagi yang lain, dan Dashboard.tsx
     * tak menggambar barisnya sama sekali.
     *
     * **BUKAN** `menungguDiMeja()` diperbesar — kartu itu TETAP `limit(4)`
     * (§P1 rencana: `DashboardWidgetTest` menegaskan TEPAT 4 baris, dan
     * `KartuPerjalanan.tsx` V1 dirancang untuknya). Ini prop terpisah,
     * berpaginasi, lintas SEMUA status (bukan hanya yang sedang berjalan) —
     * tabel "aktivitas", bukan "meja kerja".
     *
     * Penyaringan (jenis/departemen/status/cari) tetap di SERVER lewat query
     * string, `pageName: 'aktivitas'` supaya tak bentrok dengan `?bulan=`
     * kalender ketersediaan di halaman yang sama.
     *
     * **Kebocoran (§P4).** Baris HANYA: `id`, `judul`, `nomor`, `tautan`,
     * `status`, `dibuat`, `diperbarui`, `persen`, `ke`, `dariTahap`, dan
     * `pembuat: {nama, foto, jabatan}` — bentuk yang sama persis dengan
     * `pembuat` di `menungguDiMeja()`, yang sudah terbukti aman. Bukan model
     * `Document`, bukan relasi `creator` mentah.
     *
     * @return array{tabel: array<string, mixed>, filters: array<string, ?string>, departemen: array<int, array{id: int, code: string, name: string}>, statusOpsi: array<int, string>}|null
     */
    private function aktivitasTabel(User $user, Request $request): ?array
    {
        if (! $user->can('document.view_all')) {
            return null;
        }

        $query = Document::with('type', 'department', 'creator')->latest('updated_at');

        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('doc_number', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->whereHas('type', fn ($t) => $t->where('code', $type));
        }
        if ($dept = $request->input('department_id')) {
            $query->where('department_id', $dept);
        }

        $tabel = $query->paginate(6, ['*'], 'aktivitas')->withQueryString()
            ->through(fn (Document $d) => $this->barisAktivitas($d))
            ->toArray();

        return [
            'tabel' => $tabel,
            'filters' => $request->only('q', 'status', 'type', 'department_id'),
            'departemen' => Department::orderBy('code')->get(['id', 'code', 'name'])->toArray(),
            // Status legacy (submitted/needs_revision/archived) sengaja tak
            // ditawarkan — tak pernah ditulis lagi, menyaringnya cuma
            // menjanjikan hasil yang selalu kosong.
            'statusOpsi' => [
                'draft', 'waiting_for_review', 'in_review', 'verifikasi_md', 'rejected',
                'pending_approval', 'published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete',
            ],
        ];
    }

    /**
     * Satu baris `aktivitasTabel()` — progres tahap dihitung ULANG di sini
     * (bukan memanggil ulang `menungguDiMeja()`) karena baris di sana
     * dibatasi status "berjalan"; di sini status published/obsolete/dst.
     * juga tampil, dan bagi status itu tahapnya selalu "Disetujui" (selesai).
     *
     * @return array{id: int, judul: string, nomor: string, tautan: string, status: string, dibuat: ?string, diperbarui: ?string, persen: int, ke: int, dariTahap: int, pembuat: array{nama: string, foto: ?string, jabatan: string}}
     */
    private function barisAktivitas(Document $d): array
    {
        $daftarTahap = array_values(array_filter([
            'Dibuat', 'Ditinjau', $d->perluTinjauanMd() ? 'MD' : null, 'Disetujui',
        ]));
        $tahap = match ($d->status) {
            'draft', 'rejected' => 'Dibuat',
            'waiting_for_review', 'in_review' => 'Ditinjau',
            'verifikasi_md' => 'MD',
            default => 'Disetujui',
        };
        $i = array_search($tahap, $daftarTahap, true);
        $ke = ($i === false ? 1 : $i) + 1;

        return [
            'id' => $d->id,
            'judul' => $d->title,
            'nomor' => $d->displayNumber(),
            'tautan' => route('documents.show', $d),
            'status' => $d->status,
            'dibuat' => $d->created_at?->toDateString(),
            'diperbarui' => $d->updated_at?->toDateString(),
            'ke' => $ke,
            'dariTahap' => count($daftarTahap),
            'persen' => (int) round($ke / count($daftarTahap) * 100),
            'pembuat' => [
                'nama' => $d->creator?->name ?? 'Tidak diketahui',
                'foto' => $d->creator?->photoUrl(),
                'jabatan' => $d->creator ? $d->creator->jabatanLabel() : '—',
            ],
        ];
    }

    /**
     * Kartu "Performa PIC" (D9 §5d/§6f).
     *
     * Tiga hal dalam satu kartu, dan ketiganya sudah ada sumbernya:
     *   • **PIC Aktif** — berapa orang sedang memegang dokumen berjalan. Satu
     *     query, dan digabung di PHP karena `COUNT(DISTINCT a, b)` MySQL
     *     menghitung pasangan (peninjau, penyetuju), bukan orang: satu SH yang
     *     jadi peninjau di dua dokumen berbeda akan terhitung dua kali.
     *   • **Paling Produktif** — deret wajah dari sapuan audit yang sama (§5d).
     *   • **Sorotan** — tiga angka berpanah.
     *
     * `null` bagi GL dan Non-Staff (REVISI-UI-V3 §4.1). Gerbangnya
     * {@see lingkupPic()}, yang SEKALIGUS menentukan wajah siapa yang boleh
     * tampil — SH/DH melihat GL departemennya sendiri, PJO/MD/Admin melihat
     * GL+SH+DH lintas tujuh departemen.
     *
     * Ini MENCABUT K1 (`DASBOR-V2-REVISI`), yang dulu memutuskan gerbangnya
     * `dashboardPenuh()` harfiah sehingga GL ikut mendapat kartunya. Pemilik
     * membalikkannya 2026-09-01 sesudah melihatnya berjalan: performa orang
     * lain bukan bacaan penyusun dokumen.
     *
     * Gerbangnya TIDAK lagi sama dengan {@see sebaranDepartemen()} — sejak
     * 2026-09-01 keduanya memang berbeda, dan `pages/V2/Dashboard.tsx` karena
     * itu menggambar barisnya per kartu, bukan sepasang.
     *
     * F4b DASBOR-V4 (D3): tiga kunci tambahan `judul`/`subjudul`/`satuan` —
     * kosakata kartu berbeda per lingkup (SH/DH vs PJO/MD/Admin), dan itu
     * aturan PERAN sehingga wajib dari server (CLAUDE.md §4); `PerformaPic.tsx`
     * mencetaknya apa adanya, nol `if peran` di TSX.
     *
     * @param  array{alir: array<string, array{deret: array<int, int>, kini: int, lalu: int}>, perOrang: array<int, array<string, mixed>>}  $alir
     * @return array{judul: string, subjudul: string, satuan: string, picAktif: int, wajah: array<int, array<string, mixed>>, sorotan: array<int, array<string, mixed>>}|null
     */
    private function performaPic(User $user, Closure $visible, array $alir, DasborTampilan $tampilan): ?array
    {
        // Gerbang yang SAMA dengan wajahProduktif(), dibaca dari satu tempat:
        // kartu tanpa wajah dan wajah tanpa kartu sama-sama cacat.
        if (($lingkup = $this->lingkupPic($user)) === null) {
            return null;
        }

        // SH/DH: kartunya seluruhnya berisi GL departemennya sendiri (D3) —
        // "PIC Aktif"/"beban peninjau" bukan kosakata yang GL bisa capai, jadi
        // angka besar & sorotannya diganti hal yang GL memang kerjakan.
        if ($lingkup['jabatan'] === [User::JABATAN_GROUP_LEADER]) {
            $jumlahGl = User::where('jabatan', User::JABATAN_GROUP_LEADER)
                ->where('department_id', $lingkup['deptId'])
                ->count();

            return [
                'judul' => 'Produktivitas Tim',
                'subjudul' => 'GL yang aktif menyusun dokumen di departemen Anda',
                'satuan' => 'GL',
                'picAktif' => $jumlahGl,
                'wajah' => $alir['perOrang'],
                'sorotan' => [
                    $tampilan->sorotan(
                        'Dokumen dibuat',
                        (string) $alir['alir']['dibuat']['kini'],
                        'baik',
                        $alir['alir']['dibuat'],
                    ),
                    $tampilan->sorotan(
                        'Dokumen dikirim',
                        (string) $alir['alir']['dikirim']['kini'],
                        'baik',
                        $alir['alir']['dikirim'],
                    ),
                    $tampilan->sorotan(
                        'Masukan ditutup',
                        (string) $alir['alir']['masukan_tutup']['kini'],
                        'baik',
                        $alir['alir']['masukan_tutup'],
                    ),
                ],
            ];
        }

        // Daftar status yang SAMA dengan menungguDiMeja() — "sedang dipegang"
        // hanya boleh berarti satu hal di satu halaman.
        $berjalan = ['waiting_for_review', 'in_review', 'verifikasi_md', 'pending_approval', 'rejected'];
        $pemegang = $visible()->whereIn('documents.status', $berjalan)->get(['reviewer_id', 'approver_id']);
        $ids = $pemegang->pluck('reviewer_id')->merge($pemegang->pluck('approver_id'))->filter()->unique()->values();

        // Beban rata-rata dihitung ReviewerAvailability — rumus beban peninjau
        // sudah tinggal di sana dan dipakai papan pemilihan peninjau. Menghitung
        // ulang di sini berarti dashboard dan papan bisa menyebut angka berbeda
        // untuk orang yang sama.
        $beban = $ids->isEmpty() ? null : collect(
            app(ReviewerAvailability::class)->untuk(User::whereIn('id', $ids)->get())
        )->avg('beban');

        return [
            'judul' => 'PIC Aktif',
            'subjudul' => 'Peninjau & penyetuju yang sedang memegang dokumen',
            'satuan' => 'PIC',
            'picAktif' => $ids->count(),
            'wajah' => $alir['perOrang'],
            'sorotan' => [
                // TANPA panah (§P2b): rata-rata beban adalah potret HARI INI,
                // bukan aliran, jadi tak ada "30 hari sebelumnya" yang jujur
                // untuk dibandingkan dengannya.
                $tampilan->sorotan(
                    'Rata-rata beban peninjau',
                    $beban === null ? '—' : number_format($beban, 1, ',', '.'),
                    'netral',
                ),
                $tampilan->sorotan(
                    'Dokumen disahkan',
                    (string) $alir['alir']['berlaku']['kini'],
                    'baik',
                    $alir['alir']['berlaku'],
                ),
                $tampilan->sorotan(
                    'Masukan ditutup',
                    (string) $alir['alir']['masukan_tutup']['kini'],
                    'baik',
                    $alir['alir']['masukan_tutup'],
                ),
            ],
        ];
    }

    /**
     * Widget "Distribusi" (v5 Fase C) — empat dokumen Berlaku dengan cakupan
     * TERENDAH.
     *
     * Terendah, bukan tertinggi: kartu ini ada supaya dokumen yang belum sampai
     * ke orangnya ketahuan tanpa perlu dicari. Menampilkan yang paling sukses
     * akan enak dipandang dan tak menggerakkan apa pun.
     *
     * Yang mendapatnya: SH/DH/PJO/Admin (mereka bisa menindaklanjuti) DAN GL.
     * Bagi GL kartu ini BACAAN — ia tak berwenang mengajukan revisi, jadi
     * tombol "Lihat semua" pun tak muncul baginya (halaman Distribusi tetap
     * tertutup). Gunanya tetap nyata: ia penulis dokumennya, dan cakupan rendah
     * memberitahunya bahwa tulisannya belum sampai ke orang lapangan.
     *
     * null bagi Non-Staff → kartunya tak dirender sama sekali.
     *
     * DUA SUMBER sejak Fase D: dokumen mutu DAN menu Informasi. Keduanya
     * dirender server-side lalu ditukar tombol (Alpine `x-show`) — nol request
     * tambahan, nol kedipan; datanya toh cuma dua GROUP BY per sumber.
     *
     * @return array{mutu: ?array, informasi: ?array}|null
     */
    private function distribusiWidget(User $user): ?array
    {
        // Semua peran KECUALI Non-Staff (§6). Dulu disaring lewat izin
        // `document.request_revision` + GL, sehingga MD — yang tak memegang izin
        // itu dan bukan GL — kehilangan kartunya tanpa alasan.
        if (! $user->dashboardPenuh()) {
            return null;
        }

        // Lingkup yang SAMA untuk kedua sumber: pemegang `document.view_all`
        // (PJO/Admin/MD) melihat tujuh departemen, sisanya departemennya sendiri.
        $deptId = $user->can('document.view_all') ? null : $user->department_id;

        $panel = [
            'mutu' => $this->panelMutu($user),
            'informasi' => $this->panelInformasi($deptId),
            // Tombol "Lihat semua" menghilang bagi yang tak boleh membuka
            // halaman Distribusi (GL & Non-Staff): tautan yang berujung 403
            // lebih buruk daripada tak ada tautan. Syaratnya SAMA dengan
            // penjaga controllernya — dan dijawab DI SINI, bukan disimpulkan
            // ulang dari `auth.can` di TSX (pakem P4/P5).
            'bolehBukaHalaman' => $user->bisaLihatDistribusi(),
            'urlMutu' => route('documents.distribution'),
            'urlInformasi' => route('documents.distribution', ['sumber' => 'informasi']),
        ];

        // Kartunya tetap muncul selama SALAH SATU sumber punya isi; hilang sama
        // sekali bila dua-duanya kosong.
        return ($panel['mutu'] || $panel['informasi']) ? $panel : null;
    }

    /**
     * Satu panel widget Distribusi: sepuluh baris cakupan TERENDAH.
     *
     * Terendah, bukan tertinggi — kartu ini ada supaya yang belum sampai ke
     * orangnya ketahuan tanpa dicari; menampilkan yang paling sukses enak
     * dipandang dan tak menggerakkan apa pun. Sepuluh baris pula yang membuat
     * gulir di dalam kartunya terpakai.
     *
     * @return array{baris: \Illuminate\Support\Collection, rendah: int}|null
     */
    private function panelMutu(User $user): ?array
    {
        $documents = Document::with('type', 'department')
            ->berlaku()
            ->unless($user->can('document.view_all'), fn ($q) => $q->where('department_id', $user->department_id))
            ->get();

        if ($documents->isEmpty()) {
            return null;
        }

        // Satu panggilan untuk seluruh koleksi — cakupan() memang menerima
        // kumpulan supaya kartu dashboard tak melahirkan N+1.
        // EMPAT baris, bukan sepuluh (keputusan pemilik 2026-09-07). Kartunya
        // duduk sebaris dengan `KartuSebaran` dan tak boleh lebih tinggi
        // darinya; sepuluh baris memaksa barisnya memanjang jauh melewati
        // tetangganya. Sisanya dibuka lewat "Lihat semua".
        $cakupan = app(DocumentDistribution::class)->cakupan($documents);
        $tampil = $documents->sortBy(fn ($d) => $cakupan[$d->id]['persen'])->take(self::BARIS_DISTRIBUSI)->values();

        // Tumpukan wajah: satu query untuk SELURUH baris, dikelompokkan di PHP.
        $pembaca = DocumentRead::whereIn('document_id', $tampil->pluck('id'))
            ->with('user:id,name,photo_path')
            ->orderByDesc('last_read_at')
            ->get()->groupBy('document_id');

        return [
            'baris' => $tampil->map(function (Document $d) use ($cakupan, $pembaca) {
                [$ikon, $warna] = DocumentType::rupa($d->type->code ?? null);

                return [
                    'judul' => $d->title,
                    'sub' => $d->displayNumber().' · '.($d->department->code ?? '—'),
                    'ikon' => $ikon,
                    'warna' => $warna,
                    'gelar' => $d->type->name ?? '',
                    'tautan' => route('documents.show', $d),
                    'pembaca' => $this->wajah($pembaca[$d->id] ?? collect()),
                    'c' => $cakupan[$d->id],
                ];
            }),
            'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
        ];
    }

    /**
     * Panel Informasi (Fase D) — bentuk barisnya IDENTIK dengan {@see panelMutu()}
     * supaya satu tabel Blade melayani keduanya.
     *
     * Warnanya satu untuk semua kategori: tak ada peta warna per kategori
     * informasi, dan mengarang sepuluh warna baru hanya membuat pembaca mengira
     * warnanya berarti sesuatu.
     *
     * @return array{baris: \Illuminate\Support\Collection, rendah: int}|null
     */
    private function panelInformasi(?int $deptId): ?array
    {
        $daftar = Informasi::berlaku()->get();

        if ($daftar->isEmpty()) {
            return null;
        }

        $cakupan = app(InformasiDistribution::class)->cakupan($daftar, $deptId);
        $tampil = $daftar->sortBy(fn ($i) => $cakupan[$i->id]['persen'])->take(self::BARIS_DISTRIBUSI)->values();

        $pembaca = InformasiRead::whereIn('informasi_id', $tampil->pluck('id'))
            // Wajah ikut lingkup departemen — kalau tidak, SH melihat wajah orang
            // departemen lain di atas angka yang tak menghitung mereka.
            ->when($deptId, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('department_id', $deptId)))
            ->with('user:id,name,photo_path')
            ->orderByDesc('last_read_at')
            ->get()->groupBy('informasi_id');

        return [
            'baris' => $tampil->map(fn (Informasi $i) => [
                'judul' => $i->judul,
                'sub' => $i->nomor.' · '.Informasi::labelKategori($i->kategori),
                'ikon' => Informasi::ikon($i->kategori),
                'warna' => '#8392ab',
                'gelar' => Informasi::labelKategori($i->kategori),
                'tautan' => route('informasi.index', ['kategori' => $i->kategori]),
                'pembaca' => $this->wajah($pembaca[$i->id] ?? collect()),
                'c' => $cakupan[$i->id],
            ]),
            'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
        ];
    }

    /**
     * Tumpukan wajah pembaca, diratakan jadi nama + URL foto.
     *
     * `photoUrl()` adalah method model (ia menyusun URL disk publik), jadi ia
     * harus dipanggil di server. Mengirim baris `document_reads` mentah berarti
     * TSX menyusun ulang URL berkas — pengetahuan tentang tata letak storage
     * yang tak punya urusan di peramban.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $baris
     * @return array<int, array{nama: string, foto: ?string}>
     */
    private function wajah(\Illuminate\Support\Collection $baris): array
    {
        return $baris->pluck('user')->filter()
            ->map(fn (User $u) => ['nama' => $u->name, 'foto' => $u->photoUrl()])
            ->values()->all();
    }
}
