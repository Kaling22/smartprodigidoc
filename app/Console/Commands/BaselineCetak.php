<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Print\PdfRenderer;
use Illuminate\Console\Command;

/**
 * Rekam & bandingkan BASELINE keluaran cetak (Fase 0.1 / 13.2 migrasi shadcn).
 *
 * Kenapa ada: perbandingan md5 mentah antar PDF SELALU gagal, karena DomPDF
 * menstempel `/CreationDate` + `/ModDate` pada tiap render. Terbukti di sesi
 * 2026-08-28: render ulang SOP/IK/SP menghasilkan ukuran byte PERSIS sama dan
 * hanya obyek `/Info` yang berbeda. Jadi yang dibandingkan di sini adalah
 * berkas dengan kedua stempel waktu itu dinetralkan — sisanya harus identik
 * byte per byte. Kalau ada yang berbeda, sesuatu di mesin cetak tersentuh.
 *
 * Dokumen arsip/unggahan (FK/PX) sengaja DILEWATI: `ArsipPdf` hanya
 * mengalirkan berkas yang sudah ada, tidak merender apa pun, jadi tak ada
 * keluaran yang bisa menyimpang.
 */
class BaselineCetak extends Command
{
    protected $signature = 'smartpro:cetak-baseline
        {--tulis : tulis baseline baru (default: bandingkan saja)}
        {--dir=C:/baseline-smartpro/pdf : folder baseline}
        {--id=* : batasi ke id dokumen tertentu}';

    protected $description = 'Rekam/bandingkan baseline PDF dokumen (abaikan stempel waktu PDF)';

    public function handle(PdfRenderer $renderer): int
    {
        $dir = rtrim((string) $this->option('dir'), '/\\');
        is_dir($dir) || mkdir($dir, 0775, true);

        $q = Document::query()->whereNull('arsip_path')->orderBy('id');
        if ($ids = $this->option('id')) {
            $q->whereIn('id', $ids);
        }

        $beda = 0;
        foreach ($q->get() as $doc) {
            $nama = sprintf('%s_%s_id%d.pdf', $doc->type?->code ?? 'X', $doc->doc_number ?? '-', $doc->id);
            $jalur = $dir.'/'.preg_replace('~[^A-Za-z0-9._-]~', '-', $nama);
            $bytes = $renderer->render($doc)->output();

            if ($this->option('tulis')) {
                file_put_contents($jalur, $bytes);
                $this->line(sprintf('  tulis  %-46s %8d B  %s', basename($jalur), strlen($bytes), self::sidik($bytes)));

                continue;
            }

            if (! is_file($jalur)) {
                $this->warn(sprintf('  HILANG %-46s (belum ada baseline)', basename($jalur)));
                $beda++;

                continue;
            }

            $sama = self::sidik($bytes) === self::sidik(file_get_contents($jalur));
            $this->line(sprintf('  %-6s %-46s %8d B', $sama ? 'sama' : 'BEDA', basename($jalur), strlen($bytes)));
            $sama || $beda++;
        }

        if ($this->option('tulis')) {
            $this->info('Baseline ditulis ke '.$dir);

            return self::SUCCESS;
        }

        $beda === 0
            ? $this->info('Seluruh keluaran cetak identik dengan baseline.')
            : $this->error($beda.' berkas BERBEDA dari baseline — telusuri mesin cetak.');

        return $beda === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * md5 isi PDF dengan seluruh bagian yang MEMANG berubah tiap render
     * dinetralkan: `/CreationDate`, `/ModDate`, dan `/ID` di trailer.
     *
     * Ketiganya diukur langsung, bukan diduga: render ulang dokumen yang sama
     * menghasilkan berkas berukuran PERSIS sama, dan satu-satunya byte yang
     * berbeda ada di obyek `/Info` (dua stempel waktu) serta pengenal acak
     * `/ID` di trailer. Sisanya — teks, gambar, font, tata letak — identik.
     */
    private static function sidik(string $pdf): string
    {
        $pdf = preg_replace('~/(CreationDate|ModDate) \(D:[^)]*\)~', '/$1 (D:X)', $pdf);
        $pdf = preg_replace('~/ID\[<[0-9a-f]+><[0-9a-f]+>\]~i', '/ID[<X><X>]', $pdf);

        return md5($pdf);
    }
}
