<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pengaman email menjelang produksi (docs/PANDUAN-PRODUKSI-EMAIL.md).
 *
 * Dua mekanisme, keduanya ada untuk kesalahan yang TAK BISA DITARIK KEMBALI:
 *
 *  1. `MAIL_PAKSA_KE` — pada hari relay sungguhan pertama dipasang, basis data
 *     masih memuat alamat uji. Tanpa pembelokan ini, percobaan pertama bisa
 *     mengirim surat resmi ke orang yang tak bermaksud menerimanya.
 *  2. `failover` — relay yang mati tak boleh MENGHILANGKAN isi email. Untuk
 *     surat berbunyi "dokumen Anda ditolak, ini alasannya", hilang diam-diam
 *     jauh lebih buruk daripada terlambat.
 *
 * Diuji lewat `Mail::raw()`, bukan lewat notifikasi: kanal mail notifikasi
 * berjalan di koneksi antrean `database` ({@see DocumentNotification::viaConnections()}),
 * jadi di dalam test ia mengendap di tabel `jobs` dan tak pernah benar-benar
 * dikirim. Yang diuji di sini memang MEKANISME pembelokannya, dan mekanisme itu
 * duduk di lapisan Mailer — berlaku sama untuk email dari mana pun asalnya.
 */
class MailProductionSafetyTest extends TestCase
{
    /** Alamat tujuan pada email yang benar-benar dilepas ke transport. */
    private function tujuanTerkirim(callable $aksi): array
    {
        $tujuan = [];
        Event::listen(MessageSending::class, function (MessageSending $e) use (&$tujuan) {
            $tujuan = collect($e->message->getTo())->map->getAddress()->all();
        });

        $aksi();

        return $tujuan;
    }

    public function test_katup_membelokkan_seluruh_email_ke_satu_alamat(): void
    {
        config(['mail.paksa_ke' => 'penguji@ppa-adro.local']);
        // Katup dipasang di boot(); di test ia harus dijalankan ulang sesudah
        // config diubah, persis seperti saat aplikasi dinyalakan dengan .env baru.
        (new AppServiceProvider($this->app))->boot();

        $tujuan = $this->tujuanTerkirim(fn () => Mail::raw('uji', fn ($m) => $m
            ->to('penerima.asli@ppa-adro.local')->subject('Uji katup')));

        $this->assertSame(['penguji@ppa-adro.local'], $tujuan, 'penerima harus dibelokkan');
        $this->assertNotContains('penerima.asli@ppa-adro.local', $tujuan);
    }

    public function test_katup_kosong_tak_mengubah_apa_pun(): void
    {
        config(['mail.paksa_ke' => null]);
        (new AppServiceProvider($this->app))->boot();

        $tujuan = $this->tujuanTerkirim(fn () => Mail::raw('uji', fn ($m) => $m
            ->to('penerima.asli@ppa-adro.local')->subject('Uji normal')));

        $this->assertSame(['penerima.asli@ppa-adro.local'], $tujuan);
    }

    public function test_failover_berujung_ke_log_bukan_ke_kegagalan(): void
    {
        // Bukan konfigurasi baru — `failover` sudah ada di config/mail.php
        // bawaan Laravel. Yang dikunci: rantainya berakhir di `log`, sebab
        // itulah yang menjamin isi email tak hilang saat relay mati.
        $rantai = config('mail.mailers.failover.mailers');

        $this->assertSame(['smtp', 'log'], $rantai);
        $this->assertSame('log', end($rantai), 'kanal terakhir HARUS log');
    }

    public function test_kunci_paksa_ke_terbaca_dari_env(): void
    {
        $this->assertArrayHasKey('paksa_ke', config('mail'), 'kunci hilang → katup mati diam-diam');
    }

    public function test_timeout_smtp_mengikuti_env(): void
    {
        // config/mail.php:65 pernah hardcode `null`, sehingga MAIL_TIMEOUT
        // (diisi 15 lewat phpunit.xml) tak pernah terbaca dan relay yang
        // menggantung menahan pekerja antrean tanpa batas. Angka literal di
        // sini, bukan `env('MAIL_TIMEOUT')`, supaya test benar-benar gagal
        // kalau berkas config di-hardcode lagi — memanggil `env()` di kedua
        // sisi akan selalu cocok apa pun isi config-nya.
        $this->assertSame(15, config('mail.mailers.smtp.timeout'));
    }
}
