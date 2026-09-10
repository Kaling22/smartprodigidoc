<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Isikan alamat email UJI kepada pengguna yang belum punya.
 *
 * KENAPA perlu: notifikasi email hanya dikirim bila penerimanya punya alamat
 * ({@see \App\Notifications\DocumentNotification::via()} menyaringnya, supaya
 * pekerjaan antrean tidak melempar galat untuk akun tanpa email). Di basis data
 * pengembangan hampir semua akun kosong alamatnya, sehingga seluruh alur email
 * TAMPAK tidak berfungsi padahal ia bekerja dengan benar — loncengnya sampai,
 * emailnya memang tak pernah dicoba dikirim. Perintah ini menutup jarak itu
 * dalam satu langkah.
 *
 * KENAPA perintah, bukan seeder: seeder ikut terpanggil `db:seed` dan bisa
 * menimpa data tanpa disengaja. Perintah bernama jelas hanya berjalan saat
 * sengaja diketik, dan bisa membawa penjaga APP_ENV di bawah.
 */
class SeedTestEmails extends Command
{
    protected $signature = 'smartpro:email-uji
        {--domain=ppa-adro.local : Domain alamat yang dibuat}
        {--timpa : Timpa juga alamat yang sudah terisi}
        {--force : Lewati konfirmasi & penjaga APP_ENV}';

    protected $description = 'Isikan alamat email uji ({nrp}@domain) kepada pengguna yang belum punya, agar notifikasi email bisa diuji lewat Mailpit.';

    public function handle(): int
    {
        /*
        | Penjaga produksi. Perintah ini MENANAM ALAMAT PALSU; kalau sampai
        | berjalan di produksi, notifikasi resmi akan terkirim ke domain yang
        | tak ada dan tak seorang pun tahu suratnya hilang — kegagalan yang
        | diam, jenis paling mahal.
        */
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('DITOLAK: APP_ENV=production. Perintah ini menanam alamat palsu.');
            $this->line('Gunakan --force hanya bila Anda benar-benar bermaksud demikian.');

            return self::FAILURE;
        }

        $domain = ltrim(trim((string) $this->option('domain')), '@');
        if ($domain === '') {
            $this->error('--domain tidak boleh kosong.');

            return self::FAILURE;
        }

        $timpa = (bool) $this->option('timpa');

        $users = User::orderBy('nrp')->get();
        $sasaran = $timpa ? $users : $users->filter(fn (User $u) => blank($u->email));

        if ($sasaran->isEmpty()) {
            $this->info('Semua pengguna sudah punya alamat email. Tidak ada yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Isikan alamat @{$domain} kepada {$sasaran->count()} pengguna"
            .($timpa ? ' (TERMASUK menimpa yang sudah terisi)' : '').'? Lanjutkan?'
        )) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        $baris = [];
        $diisi = 0;
        $dilewati = 0;

        foreach ($users as $u) {
            /*
            | Alamat yang SUDAH ADA tidak disentuh tanpa --timpa. Sebagian akun
            | memakai @gmail.com yang bisa jadi alamat sungguhan; menimpanya
            | diam-diam berarti menghapus data nyata.
            */
            if (filled($u->email) && ! $timpa) {
                $baris[] = [$u->nrp, $u->jabatan, $u->email, 'dilewati'];
                $dilewati++;

                continue;
            }

            // NRP dipakai apa adanya (huruf kecil): ia sudah unik dan sudah jadi
            // identitas orang di sistem ini, jadi alamatnya bisa dibaca balik
            // menjadi "siapa" tanpa menebak — penting saat memilah di Mailpit.
            $alamat = strtolower((string) $u->nrp).'@'.$domain;

            $u->forceFill(['email' => $alamat])->save();
            $baris[] = [$u->nrp, $u->jabatan, $alamat, 'diisi'];
            $diisi++;
        }

        $this->table(['NRP', 'Jabatan', 'Email', 'Hasil'], $baris);
        $this->info("Selesai: {$diisi} diisi, {$dilewati} dilewati.");

        $this->line('');
        $this->comment('Agar emailnya benar-benar terkirim, jalankan di jendela terpisah:');
        $this->line('  php artisan queue:work');
        $this->comment('Lalu buka Mailpit; pilah per orang dengan kotak cari, mis. to:'.strtolower((string) ($users->first()->nrp ?? 'sh-0001')).'@'.$domain);

        return self::SUCCESS;
    }
}
