<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Membuang puing `audit_logs` yang menunjuk dokumen yang bukan miliknya.
 *
 * ## Sebabnya
 *
 * Basis data pengembangan digabung pada 2026-09-06 (cadangannya
 * `backup-db/smartpro_shadcn-before-merge-20260906-123413.sql`). `audit_logs`
 * ikut terbawa dengan `document_id` MENTAH dari basis data asalnya, sementara
 * `documents` dinomori ulang. Akibatnya sebagian baris audit kini menempel
 * pada dokumen yang tak ada hubungannya — dan itu langsung terlihat di
 * Timeline Riwayat halaman detail dokumen, yang memang membaca `audit_logs`
 * (CLAUDE.md §10).
 *
 * Contoh yang dilaporkan pemilik: `PPA-ADRO-JSA-PRODUKSI-07` (dept Produksi)
 * memperlihatkan "Revisi diajukan" dan "Disetujui — Berlaku" atas nama seorang
 * GL departemen ICTMD. Dua baris itu ber-id 2153/2154 sementara
 * `document.create` dokumen tersebut ber-id 2319: mustahil dalam satu basis
 * data, sebab id `audit_logs` naik monoton.
 *
 * ## Tiga kriteria
 *
 * Tak satu pun menebak dari isi, pelaku, atau tanggal "yang terlihat aneh".
 * Ketiganya adalah KEMUSTAHILAN urutan, sehingga baris yang sah tak bisa ikut
 * terbawa:
 *
 *   (a) **yatim** — `document_id` menunjuk dokumen yang sudah tidak ada.
 *   (b) **pra-dokumen** — log ditulis sebelum dokumennya dibuat.
 *   (c) **pra-create** — id log lebih kecil daripada id log `document.create`
 *       (atau `document.arsip_upload`, yang menciptakan dokumen tanpa melewati
 *       `create`) milik dokumen itu.
 *
 * (c) dihitung SESUDAH (b) disingkirkan: pada sebagian dokumen justru baris
 * `document.create` puing itulah yang ber-id terkecil, dan memakainya sebagai
 * acuan membuat kriteria ini buta terhadap dokumen tersebut.
 *
 * Baris ber-`document_id` NULL — login, logout, perubahan pengaturan — tak
 * pernah disentuh: ia tak menunjuk dokumen apa pun, jadi tak bisa salah tunjuk.
 *
 * ## Cara pakai
 *
 * Bawaannya MODE KERING: ia hanya menghitung dan menampilkan. Penghapusan
 * menuntut `--terapkan`, dan seluruh baris yang dibuang disalin lebih dulu ke
 * `backup-db/` sebagai JSON utuh sehingga bisa dimasukkan kembali.
 */
class BersihkanAuditPuing extends Command
{
    protected $signature = 'smartpro:bersihkan-audit-puing
        {--terapkan : Benar-benar HAPUS. Tanpa opsi ini perintah hanya menghitung}
        {--rinci : Tampilkan tiap baris yang terkena, bukan hanya jumlahnya}';

    protected $description = 'Buang baris audit_logs yang menunjuk dokumen bukan miliknya (puing penggabungan basis data)';

    public function handle(): int
    {
        [$a, $b, $c] = $this->kumpulkan();

        $semua = array_values(array_unique(array_merge($a, $b, $c)));
        sort($semua);

        $this->table(
            ['Kriteria', 'Jumlah'],
            [
                ['(a) yatim — dokumennya tak ada', count($a)],
                ['(b) pra-dokumen — log lebih tua dari dokumennya', count($b)],
                ['(c) pra-create — id log mendahului id penciptaannya', count($c)],
                ['TOTAL', count($semua)],
            ],
        );

        if ($semua === []) {
            $this->info('Tidak ada puing. Tak ada yang perlu dikerjakan.');

            return self::SUCCESS;
        }

        if ($this->option('rinci')) {
            $this->baris($semua);
        }

        if (! $this->option('terapkan')) {
            $this->warn('MODE KERING — nol baris dihapus. Jalankan ulang dengan --terapkan untuk benar-benar membuangnya.');

            return self::SUCCESS;
        }

        $sebelum = DB::table('audit_logs')->count();
        $cadangan = $this->cadangkan($semua, [count($a), count($b), count($c)]);

        $terhapus = DB::table('audit_logs')->whereIn('id', $semua)->delete();

        $this->info("Cadangan  : {$cadangan}");
        $this->info("Terhapus  : {$terhapus} baris");
        $this->info('audit_logs: '.$sebelum.' -> '.DB::table('audit_logs')->count());

        // Pemeriksaan ulang. Kalau (a)/(b) tak nol sesudah ini, kriterianya
        // salah tulis — dan lebih baik ketahuan sekarang daripada lewat
        // timeline yang tetap keliru.
        [$sisaA, $sisaB, $sisaC] = array_map('count', $this->kumpulkan());

        if ($sisaA + $sisaB + $sisaC > 0) {
            $this->error("Masih tersisa puing: a={$sisaA} b={$sisaB} c={$sisaC} — periksa kembali.");

            return self::FAILURE;
        }

        $this->info('Pemeriksaan ulang bersih: nol puing tersisa.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: list<int>, 1: list<int>, 2: list<int>}
     */
    private function kumpulkan(): array
    {
        $a = DB::table('audit_logs as a')
            ->whereNotNull('a.document_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('documents as d')->whereColumn('d.id', 'a.document_id'))
            ->pluck('a.id')->all();

        $b = DB::table('audit_logs as a')
            ->join('documents as d', 'a.document_id', '=', 'd.id')
            ->whereColumn('a.created_at', '<', 'd.created_at')
            ->pluck('a.id')->all();

        $sudah = array_merge($a, $b) ?: [0];

        $acuan = DB::table('audit_logs')
            ->whereNotIn('id', $sudah)
            ->whereIn('action', ['document.create', 'document.arsip_upload'])
            ->whereNotNull('document_id')
            ->groupBy('document_id')
            ->selectRaw('document_id, MIN(id) AS awal')
            ->pluck('awal', 'document_id');

        $c = DB::table('audit_logs')
            ->whereNotNull('document_id')
            ->whereNotIn('id', $sudah)
            ->get(['id', 'document_id'])
            ->filter(fn ($r) => isset($acuan[$r->document_id]) && $r->id < $acuan[$r->document_id])
            ->pluck('id')->all();

        return [array_map('intval', $a), array_map('intval', $b), array_map('intval', $c)];
    }

    /** @param  list<int>  $id */
    private function baris(array $id): void
    {
        $this->table(
            ['id', 'aksi', 'user', 'dokumen', 'nomor', 'waktu log'],
            DB::table('audit_logs as a')
                ->leftJoin('documents as d', 'a.document_id', '=', 'd.id')
                ->whereIn('a.id', $id)
                ->orderBy('a.id')
                ->get(['a.id', 'a.action', 'a.user_id', 'a.document_id', 'd.doc_number', 'a.created_at'])
                ->map(fn ($r) => [
                    $r->id, $r->action, $r->user_id ?? '—',
                    $r->document_id, $r->doc_number ?? '(hilang)', $r->created_at,
                ])->all(),
        );
    }

    /**
     * @param  list<int>  $id
     * @param  array{0: int, 1: int, 2: int}  $hitung
     */
    private function cadangkan(array $id, array $hitung): string
    {
        $dir = base_path('backup-db');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $jalur = $dir.'/audit-logs-puing-'.now()->format('Ymd-His').'.json';

        file_put_contents($jalur, json_encode([
            'dibuat' => now()->toDateTimeString(),
            'sebab' => 'Puing audit_logs dari penggabungan basis data 2026-09-06.',
            'kriteria' => ['a' => $hitung[0], 'b' => $hitung[1], 'c' => $hitung[2]],
            'baris' => DB::table('audit_logs')->whereIn('id', $id)->orderBy('id')->get(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $jalur;
    }
}
