<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentPurger;
use Illuminate\Console\Command;

/**
 * Kosongkan SELURUH dokumen beserta jejaknya — dan HANYA itu.
 *
 * Dipakai sekali menjelang impor arsip lama (docs/PLAN-MASTER.md §A.3 langkah 6):
 * dokumen uji masa pengembangan dibuang supaya tak bercampur dengan dokumen nyata
 * dan tak ikut memesan nomor urut.
 *
 * BUKAN `smartpro:renew`. Perintah itu mengosongkan users, audit log, notifikasi,
 * dan seluruh tabel operasional lewat TRUNCATE — jauh melampaui yang diminta, dan
 * TRUNCATE-nya mereset AUTO_INCREMENT sehingga id lama bisa dipakai ulang oleh
 * baris yang sama sekali berbeda. Di sini: user, departemen, jenis dokumen, dan
 * audit log TIDAK tersentuh, dan id tak pernah didaur ulang.
 *
 * Penghapusannya sendiri tidak ditulis di berkas ini melainkan didelegasikan ke
 * {@see DocumentPurger} — satu-satunya pemusnah dokumen di aplikasi ini, yang
 * sudah menangani job_executions ber-RESTRICT, notifikasi lonceng tanpa FK,
 * berkas lampiran, dan singgahan PDF. Penghapus kedua yang harus sepakat dengan
 * yang pertama adalah penghapus yang suatu hari tidak sepakat.
 */
class HapusDokumen extends Command
{
    protected $signature = 'smartpro:hapus-dokumen
                            {--force : Lewati konfirmasi}
                            {--dry-run : Tampilkan yang akan dihapus, jangan hapus apa pun}';

    protected $description = 'Musnahkan seluruh dokumen + berkasnya. User, audit log, & konfigurasi TIDAK tersentuh.';

    public function handle(DocumentPurger $purger): int
    {
        // Penjaga yang sama dengan RenewData: di produksi ini menghapus dokumen
        // asli tujuh departemen, dan tak ada undo-nya.
        if (app()->environment('production')) {
            $this->error('Ditolak: smartpro:hapus-dokumen tidak boleh dijalankan di lingkungan production.');

            return self::FAILURE;
        }

        // withTrashed: dokumen yang sudah di-soft-delete tetap memegang barisnya,
        // nomornya, dan berkasnya. Melewatkannya berarti meninggalkan justru yang
        // paling tak terlihat.
        $dokumen = Document::withTrashed()->with('type')->get();

        if ($dokumen->isEmpty()) {
            $this->info('Tidak ada dokumen. Tak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nomor', 'Status', 'Judul'],
            $dokumen->map(fn (Document $d) => [
                $d->id,
                $d->displayNumber(),
                $d->status,
                mb_strimwidth((string) $d->title, 0, 45, '…'),
            ])
        );

        if ($this->option('dry-run')) {
            $this->warn($dokumen->count().' dokumen AKAN dihapus. Tak satu pun disentuh (--dry-run).');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            'Musnahkan '.$dokumen->count().' dokumen di atas beserta lampiran & singgahan PDF-nya? '
            .'User, audit log, dan konfigurasi tetap. Tidak ada undo. Lanjutkan?'
        )) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        // Seluruh penghapusannya — termasuk pembersihan singgahan PDF yatim —
        // dikerjakan DocumentPurger::purgeAll(), yang juga dipakai tombol
        // "Bersihkan Seluruh Dokumen" milik Admin IT (PLAN C §C4). Perintah ini
        // tinggal mengurus yang memang urusan terminal: pratinjau & konfirmasi.
        $hasil = $purger->purgeAll('Pembersihan data pengembangan (smartpro:hapus-dokumen)');

        foreach ($hasil['nomor'] as $nomor) {
            $this->line("  dimusnahkan: {$nomor}");
        }
        $this->line('  dibersihkan: storage/app/pdf-cache');

        $this->newLine();
        $this->info("Selesai. {$hasil['terhapus']} dokumen dimusnahkan.");

        return self::SUCCESS;
    }
}
