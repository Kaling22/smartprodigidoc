<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan alur dokumen — SATU kelas untuk semua peristiwa.
 *
 * Dua kanal, satu saklar:
 *   • LONCENG (database) — SELALU. Setiap peristiwa muncul di lonceng, di web
 *     maupun di aplikasi mobile. Tak ada yang disembunyikan.
 *   • EMAIL (mail) — hanya bila `$penting`. Email dipakai untuk hal yang harus
 *     DIKERJAKAN atau yang mengubah nasib pekerjaan seseorang. Kalau semua
 *     peristiwa dikirim ke email, orang berhenti membaca email dari SmartPro
 *     dan justru yang genting ikut terlewat.
 *
 * KENAPA kelas notifikasi baru TIDAK dibuat per peristiwa: teks lonceng dan
 * teks email harus sama persis (`$message` yang sama dipakai keduanya). Dua
 * kelas berarti dua kalimat yang perlahan menyimpang, dan pengguna yang membaca
 * keduanya akan mengira ada dua kejadian berbeda.
 *
 * ATURAN MEMILIH `$routeName` (PLAN-AKSES-v8 Fase 3a). Ditulis di sini, satu
 * kali, supaya tak jadi hafalan yang harus diulang di 24 tempat pemanggilan:
 *
 *   • Notifikasi yang meminta seseorang MENGERJAKAN sesuatu menuju LAYAR
 *     KERJANYA — `documents.edit` (perbaiki dokumen), `review.show` /
 *     `review.md.show` (tinjau), `approvals.show` (setujui), `log.masukan`
 *     (tindak masukan lapangan), `nonaktif.index` (putuskan pengajuan).
 *   • Notifikasi yang hanya MENGABARKAN menuju DOKUMENNYA — `documents.show`.
 *
 * Sebelumnya mayoritas menunjuk halaman DAFTAR (`documents.index`,
 * `review.index`, `approvals.index`) padahal dokumennya sudah ada di payload.
 * Penerima dikabari nomor dokumen lalu diantar ke daftar berisi puluhan baris
 * dan disuruh mencarinya sendiri.
 *
 * Rute DAFTAR tetap dipakai bila memang di situlah pekerjaannya berada:
 * `nonaktif.index` adalah antrean keputusan nonaktif, bukan daftar bacaan.
 */
class DocumentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  bool  $penting  true → ikut dikirim ke email (lihat kelas di atas).
     * @param  string|null  $catatan  alasan/rangkuman peninjau. Hanya tampil di
     *                                email — lonceng harus tetap satu kalimat.
     */
    public function __construct(
        public Document $document,
        public string $message,
        public string $icon = 'bi-bell',
        public string $routeName = 'documents.index',
        public bool $penting = false,
        public ?string $catatan = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        // Tanpa alamat email, kanal mail hanya akan melempar galat di antrean.
        // Banyak akun lapangan memang belum mengisinya.
        return $this->penting && filled($notifiable->email ?? null)
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * Kanal LONCENG jalan seketika (sync), EMAIL lewat antrean.
     *
     * Inilah yang menjaga performanya: menekan "Loloskan" tidak ikut menunggu
     * SMTP, sementara lonceng tetap muncul saat itu juga — bahkan bila
     * `queue:work` sedang tidak berjalan sama sekali. Kalau keduanya diantrekan,
     * lonceng akan diam sampai ada pekerja; kalau keduanya sinkron, satu server
     * mail yang lambat menggantung seluruh aksi peninjauan.
     *
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync', 'mail' => 'database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'doc_number' => $this->document->doc_number,
            'title' => $this->document->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'route' => $this->routeName,
        ];
    }

    /**
     * Email dibangun dari `$message` YANG SAMA dengan lonceng — satu sumber
     * kalimat, jadi keduanya mustahil berbeda.
     *
     * Nomor dokumen ikut di SUBJEK supaya utas per dokumen mengelompok sendiri
     * di kotak masuk penerima, tanpa aturan filter apa pun.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[SmartPro] '.$this->judulAksi().' — '.$this->document->doc_number)
            ->view('emails.dokumen', [
                'nama' => $notifiable->name ?? '',
                'pesan' => $this->message,
                'catatan' => $this->catatan,
                'document' => $this->document,
                'aksi' => $this->judulAksi(),
                'tautan' => $this->tautan(),
            ]);
    }

    /**
     * Peta ikon → kategori, SATU tempat (F7 DASBOR-V4). Dulu hidup sebagai
     * `match` di dalam `judulAksi()`; diangkat jadi konstanta supaya
     * `NotificationController::index()` bisa memakainya sebagai opsi saring
     * kategori tanpa menyalin daftarnya (subjek email + saringan halaman,
     * dua pemakai satu peta).
     *
     * @var array<string, string>
     */
    public const KATEGORI = [
        'bi-clipboard-check' => 'Perlu Ditinjau',
        'bi-spellcheck' => 'Perlu Ditinjau MD',
        'bi-patch-check' => 'Perlu Disetujui',
        'bi-check-circle' => 'Dokumen Berlaku',
        'bi-x-circle' => 'Ditolak',
        'bi-arrow-counterclockwise' => 'Dikembalikan',
        'bi-arrow-repeat' => 'Revisi Diajukan',
        'bi-arrow-left-right' => 'Tugas Peninjauan Dialihkan',
        'bi-slash-circle' => 'Pengajuan Nonaktif',
        'bi-exclamation-triangle' => 'Perlu Perhatian',
        'bi-people' => 'Dokumen Anda',
        'bi-chat-square-text' => 'Masukan Sejawat',
    ];

    /**
     * Ringkasan aksi untuk subjek & lencana email.
     *
     * Diturunkan dari IKON, bukan dari kalimatnya: ikon sudah dipilih per
     * peristiwa di tiap pemanggil, jadi tak ada parameter baru yang harus
     * diisikan di 20-an tempat. Tak dikenali → "Pemberitahuan", bukan galat.
     */
    public function judulAksi(): string
    {
        return self::KATEGORI[$this->icon] ?? 'Pemberitahuan';
    }

    /**
     * URL tujuan sebuah notifikasi — dipakai TOMBOL EMAIL dan LONCENG.
     *
     * Parameter dokumen hanya diikutkan bila rutenya MEMANG memintanya. Ini
     * diperiksa, bukan dicoba-dengan-try: `route('review.index', $document)`
     * TIDAK melempar galat — Laravel diam-diam menempelkan id sebagai query
     * string (`/review?9238`), jadi versi try/catch menghasilkan URL yang
     * tampak benar tapi tak menuju ke mana-mana. Terukur di Mailpit.
     *
     * Rute DAFTAR (mis. `nonaktif.index`) memang tujuan yang tepat untuk
     * sebagian peristiwa: di sanalah antreannya dikerjakan.
     *
     * DIJADIKAN STATIC PUBLIC (PLAN-AKSES-v8 Fase 3a). Sebelumnya aturan ini
     * hanya dipakai email, sementara lonceng punya salinannya sendiri di
     * NotificationController::open() yang MEMBUANG parameter dokumen —
     * akibatnya setiap notifikasi mendarat di halaman daftar, dan notifikasi
     * ber-rute `documents.show` melempar UrlGenerationException alias galat
     * 500 begitu diklik. Satu tempat, dua pemakai: mustahil menyimpang lagi.
     *
     * $document null (dokumennya sudah dihapus) → rute tanpa parameter tetap
     * dituju; yang berparameter jatuh ke dashboard, bukan melempar galat.
     */
    public static function urlUntuk(string $routeName, ?Document $document = null): string
    {
        $rute = app('router')->getRoutes()->getByName($routeName);

        if (! $rute) {
            return $document ? route('documents.show', $document) : route('dashboard');
        }

        if (! in_array('document', $rute->parameterNames(), true)) {
            return route($routeName);
        }

        return $document ? route($routeName, $document) : route('dashboard');
    }

    private function tautan(): string
    {
        return self::urlUntuk($this->routeName, $this->document);
    }
}
