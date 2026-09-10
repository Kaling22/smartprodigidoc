<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;

/**
 * Gerbang asap halaman Inertia: apakah tiap halaman BENAR-BENAR bisa dirender.
 *
 * KENAPA PERINTAH INI ADA — dibayar sekali, mahal:
 *
 * `npm run build` hijau, `tsc --noEmit` hijau, `php artisan test` hijau, dan
 * halaman `/lab` tetap PUTIH KOSONG di peramban. Penyebabnya `<Tooltip>` di
 * luar `TooltipProvider` — Radix MELEMPAR galat alih-alih diam, jadi satu item
 * sidebar cukup untuk mematikan seluruh halaman. Ketiga gerbang itu memeriksa
 * tipe dan penyusunan modul; tak satu pun MERENDER, sehingga tak satu pun bisa
 * melihatnya.
 *
 * Yang dilakukan perintah ini:
 *   1. menembak tiap rute GET yang tak butuh parameter, sebagai pengguna nyata;
 *   2. mengambil payload Inertia dari HTML-nya (halaman Blade lama dilewati);
 *   3. merendernya di Node lewat `bootstrap/ssr/uji-render.js`.
 *
 * Halaman yang melempar galat dilaporkan beserta pesannya. Karena daftarnya
 * datang dari tabel rute, tiap halaman yang lahir di Fase 4–12 ikut terjaga
 * SENDIRI — tanpa ada daftar kedua yang harus diingat orang.
 *
 * `--pratinjau` menyalakan `session('ui_v2')`, sehingga
 * {@see \App\Inertia\PratinjauResponseFactory} memilih kembaran `V2/*` untuk
 * rute yang punya. TANPA opsi ini halaman V2 tak pernah benar-benar dirender
 * oleh gerbang mana pun — `npm run build`, `tsc --noEmit`, dan `npm run
 * uji-render` bertiga hanya memeriksa tipe dan penyusunan modul. Jalankan DUA
 * KALI, mati dan nyala (REDESAIN-UI-V2 §10).
 *
 * `--uri` (boleh berulang) menambahkan alamat MENTAH ke sapuan. Ia lahir di
 * tranche 2: rute daftar tak berparameter, tapi wizard dan layar tinjau
 * berparameter — dan justru dua halaman itulah yang paling rumit dan paling
 * mungkin berakhir putih. Lihat {@see UjiRender::rute()} untuk sebab id-nya
 * tetap tak boleh ditebak sendiri.
 */
class UjiRender extends Command
{
    protected $signature = 'smartpro:uji-render
        {--nrp=ADM-0001 : NRP pengguna yang dipakai membuka halaman}
        {--rute= : Batasi ke satu nama rute}
        {--uri=* : URI tambahan yang disapu apa adanya (untuk rute berparameter)}
        {--pratinjau : Nyalakan sesi pratinjau REDESAIN-UI-V2 (halaman V2/*)}';

    protected $description = 'Render tiap halaman Inertia di Node — menangkap halaman putih yang lolos build & tes';

    public function handle(Kernel $kernel): int
    {
        $bundel = base_path('bootstrap/ssr/uji-render.js');

        if (! is_file($bundel)) {
            $this->error('Bundel belum dibangun. Jalankan dulu:');
            $this->line('  npx vite build --ssr resources/js/uji-render.tsx --outDir bootstrap/ssr');

            return self::FAILURE;
        }

        $user = User::where('nrp', $this->option('nrp'))->first();

        if (! $user) {
            $this->error('Pengguna dengan NRP '.$this->option('nrp').' tidak ada.');

            return self::FAILURE;
        }

        $payload = [];

        // Satu penyapu tidak cukup. Halaman TAMU (masuk, daftar) membalas 302
        // bagi siapa pun yang sudah masuk, dan halaman TUNGGU cuma terbuka bagi
        // akun yang belum aktif — jadi menyapu sebagai satu pengguna saja
        // membuat ketiganya tak pernah terlihat gerbang ini. Itu persis jenis
        // halaman yang gerbang ini lahir untuk menjaga.
        foreach ($this->penyapu($user) as $sebagai) {
            // Berganti persona butuh DUA hal, dan yang kedua mudah terlewat:
            //
            //  - `logout()` membuang kunci login dari SESI. Guard `web` disebut
            //    eksplisit karena di konsol guard bawaan bisa jatuh ke
            //    `RequestGuard` (sanctum), yang tak punya `logout()` sama sekali.
            //  - `forgetGuards()` membuang guard yang sudah terlanjur DI-CACHE
            //    selama sapuan sebelumnya. Tanpa ini middleware `guest` masih
            //    melihat pengguna lama dan `/login` membalas 302 — padahal
            //    `Auth::check()` sudah `false`. Itu persis yang terjadi, dan
            //    diamnya total: halaman tamu cuma tak pernah muncul di laporan.
            Auth::guard('web')->logout();
            Auth::forgetGuards();

            if ($sebagai !== null) {
                Auth::guard('web')->login($sebagai);
            }

            foreach ($this->rute() as $nama => $uri) {
                if (isset($payload[$nama])) {
                    continue;   // sudah tertangkap penyapu sebelumnya
                }

                $page = $this->payload($kernel, $uri);

                if ($page !== null) {
                    $payload[$nama] = $page;
                }
            }
        }

        // URI yang DIMINTA pemanggil tapi tak menghasilkan payload wajib
        // berteriak. Rute bernama boleh diam saat dilewati — ada ratusan, dan
        // yang bukan Inertia memang banyak. `--uri` cuma ada karena seseorang
        // mengetiknya, jadi diam di situ berarti ia mengira halamannya tersapu
        // padahal id-nya salah, aksesnya ditolak, atau rutenya berubah.
        foreach ((array) $this->option('uri') as $uri) {
            if (! isset($payload['uri:'.$uri])) {
                $this->warn("--uri={$uri} tidak menghasilkan halaman Inertia (404, 403, atau bukan Inertia).");
            }
        }

        if ($payload === []) {
            $this->warn('Tak ada halaman Inertia yang bisa dibuka. Belum ada yang dimigrasi?');

            return self::SUCCESS;
        }

        return $this->render($bundel, $payload);
    }

    /**
     * Pengguna yang dipakai menyapu, berurutan. `null` berarti TAMU.
     *
     * Akun belum aktif DICARI, bukan dibuat: perintah ini menyentuh basis data
     * sungguhan, dan membuat pengguna di sana berarti gerbang asap meninggalkan
     * sampah tiap kali dijalankan. Kalau tak ada satu pun, halaman tunggu tak
     * ikut disapu — dan itu tetap lebih jujur daripada data karangan.
     *
     * @return list<User|null>
     */
    private function penyapu(User $utama): array
    {
        $penyapu = [$utama, null];
        $belumAktif = User::whereIn('status', ['pending', 'rejected'])->first();

        if ($belumAktif) {
            $penyapu[] = $belumAktif;
        }

        return $penyapu;
    }

    /**
     * Rute GET bernama yang tak menuntut parameter, DITAMBAH `--uri` yang
     * diberikan pemanggil.
     *
     * Yang berparameter (`/documents/{document}`) tetap tak pernah ditebak
     * sendiri: menebak id yang sah berarti perintah ini menyimpan pengetahuan
     * tentang data, dan pengetahuan itu akan basi.
     *
     * `--uri` menutup celah itu tanpa membawa pengetahuan tersebut MASUK ke
     * kode. Yang tahu id mana yang sah adalah orang yang menjalankan
     * perintahnya, dan ia menyebutkannya di baris perintah:
     *
     *     php artisan smartpro:uji-render --pratinjau \
     *         --uri=/documents/1227/edit --uri=/review/1179
     *
     * Tanpa ini `V2/Documents/Edit` dan `V2/Review/Show` — dua halaman paling
     * rumit di tranche 2 — tak pernah benar-benar DIRENDER oleh gerbang mana
     * pun, dan layar putih lolos sampai ke pengguna.
     *
     * @return array<string, string>
     */
    private function rute(): array
    {
        $saring = $this->option('rute');
        $daftar = [];

        // `--uri` didahulukan supaya ia tak pernah tersaring `--rute`, yang
        // menyaring berdasarkan NAMA rute — dan URI mentah tak punya nama.
        foreach ((array) $this->option('uri') as $uri) {
            $daftar['uri:'.$uri] = '/'.ltrim((string) $uri, '/');
        }

        foreach (Route::getRoutes() as $r) {
            $nama = $r->getName();

            if (! $nama || ! in_array('GET', $r->methods(), true) || str_contains($r->uri(), '{')) {
                continue;
            }

            if ($saring && $nama !== $saring) {
                continue;
            }

            $daftar[$nama] = '/'.ltrim($r->uri(), '/');
        }

        return $daftar;
    }

    /**
     * Payload Inertia dari sebuah URI, atau null bila halamannya bukan Inertia
     * (Blade lama, unduhan, pengalihan).
     *
     * @return array<string, mixed>|null
     */
    private function payload(Kernel $kernel, string $uri): ?array
    {
        try {
            $req = Request::create($uri, 'GET');
            $req->setLaravelSession(app('session.store'));

            /*
            | Ditulis ULANG tiap permintaan, bukan sekali di `handle()`:
            | `StartSession` memuat ulang atribut dari handler-nya di setiap
            | putaran, dan bendera yang cuma disetel di awal akan hilang di
            | halaman kedua — diam-diam, dengan laporan yang tetap hijau karena
            | yang dirender ternyata komponen lama.
            |
            | DICABUT 2026-09-07: V1 mati dan `PratinjauResponseFactory` tak
            | lagi membaca sesi, jadi menulis bendera ini tak mengubah apa pun.
            | Opsi `--pratinjau` sengaja TETAP DITERIMA supaya perintah gerbang
            | yang sudah tertulis di CLAUDE.md §14c dan docs/ tidak mendadak
            | gagal; ia kini tak berpengaruh, dan kedua jalannya menyapu pohon
            | V2 yang sama.
            */

            $res = $kernel->handle($req);
        } catch (\Throwable) {
            return null;
        }

        if ($res->getStatusCode() !== 200) {
            return null;
        }

        $html = $res->getContent();

        if (! preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', (string) $html, $m)) {
            return null;
        }

        return json_decode($m[1], true);
    }

    /** @param  array<string, array<string, mixed>>  $payload */
    private function render(string $bundel, array $payload): int
    {
        // Skripnya ditulis di sini, bukan disimpan sebagai berkas: ia cuma
        // jembatan tiga baris ke bundel, dan berkas terpisah berarti satu
        // tempat lagi yang bisa basi tanpa ketahuan.
        $skrip = <<<'JS'
        const fs = require('fs');
        const payload = JSON.parse(fs.readFileSync(process.argv[1], 'utf8'));
        const hasil = {};
        import('file://' + process.argv[2]).then(async (m) => {
            for (const [nama, page] of Object.entries(payload)) {
                try {
                    const html = await m.render(page);
                    hasil[nama] = { ok: true, komponen: page.component, panjang: html.length };
                } catch (e) {
                    hasil[nama] = { ok: false, komponen: page.component, pesan: e.message };
                }
            }
            process.stdout.write(JSON.stringify(hasil));
        });
        JS;

        // Payload lewat BERKAS, bukan argumen: satu halaman dasbor saja sudah
        // puluhan kilobita, dan baris perintah Windows putus di 32.767 aksara —
        // diam-diam, dengan galat yang menunjuk ke tempat yang salah.
        $berkas = storage_path('framework/uji-render.json');
        file_put_contents($berkas, json_encode($payload));

        $proc = new Process(['node', '-e', $skrip, '--', $berkas, $bundel], base_path(), timeout: 180);
        $proc->run();

        @unlink($berkas);

        $hasil = json_decode($proc->getOutput(), true);

        if (! is_array($hasil)) {
            $this->error('Node gagal: '.trim($proc->getErrorOutput() ?: $proc->getOutput()));

            return self::FAILURE;
        }

        $gagal = 0;

        foreach ($hasil as $nama => $h) {
            if ($h['ok']) {
                $this->line(sprintf('  <fg=green>oke  </> %-28s %-22s %d B', $nama, $h['komponen'], $h['panjang']));
            } else {
                $gagal++;
                $this->line(sprintf('  <fg=red>GAGAL</> %-28s %-22s %s', $nama, $h['komponen'], $h['pesan']));
            }
        }

        $this->newLine();

        if ($gagal > 0) {
            $this->error("{$gagal} halaman melempar galat saat dirender — di peramban itu berarti layar putih.");

            return self::FAILURE;
        }

        $this->info(count($hasil).' halaman Inertia dirender tanpa galat.');

        return self::SUCCESS;
    }
}
