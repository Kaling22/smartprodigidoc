<?php

namespace App\Services\Print;

use App\Models\Document;
use App\Services\ActivityPrintLayout;
use App\Services\JsaPrintLayout;
use App\Services\SchemaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;

/**
 * Mesin cetak PDF — dipindahkan UTUH dari DocumentController agar controller
 * tetap tipis dan tata letak cetak punya rumah sendiri.
 *
 * ============================ PERINGATAN ============================
 * SELURUH konstanta di kelas ini hasil PENGUKURAN stream PDF nyata, bukan
 * hasil penalaran: koordinat stamp, penanda halaman, dan urutan render
 * 2-fase. Mengubahnya "supaya lebih rapi" akan MERUSAK cetakan, dan tak
 * ada cara menurunkannya ulang selain mengukur dari nol.
 * Rujukan: docs/JSA-CETAK-KONFIGURASI.md, docs/COVER-CETAK-KONFIGURASI.md,
 * dan docs/HANDOVER-SESI-BARU.md.
 *
 * Aturan mutlak: `output()` dipanggil **SEKALI** per objek Dompdf. Memanggil
 * lebih dari sekali MERUSAK subset font (glyph jadi simbol aneh) dan test
 * berbasis teks TIDAK menangkapnya — string literalnya tetap benar, yang
 * rusak glyph-nya. Karena itu render probe selalu objek TERPISAH dari final.
 * ====================================================================
 */
class PdfRenderer
{
    /*
    | Geometri HALAMAN COVER (SOP/IK/SP) — hasil pengukuran docs/Cover_Depan.docx,
    | lihat resources/views/documents/print/_cover.blade.php untuk rinciannya.
    | Digambar canvas, bukan HTML, karena letaknya di dalam pita kop yang harus
    | ditutup lebih dulu.
    */
    private const A4_LEBAR = 595.28;

    private const A4_TINGGI = 841.89;

    /** Bingkai halaman docx: `w:pgBorders` single 3pt, 24pt dari tepi kertas. */
    private const COVER_BINGKAI_JARAK = 24.0;

    private const COVER_BINGKAI_TEBAL = 3.0;

    /**
     * Logo cover: tepi atas 42.75pt, tinggi 118.5pt.
     *
     * Logo dipasang dari versi TERPOTONG (lihat logoCoverPath()), jadi 118.5pt
     * ini benar-benar tinggi gambar yang TERLIHAT. Bandingkan: sebelum padding
     * dipotong, kotak 130.39pt hanya menampilkan ±82pt gambar.
     *
     * 118.5 = 158 × 0.75 (dikecilkan 25% atas permintaan pemilik).
     * 42.75 = 24pt (bingkai halaman) + 18.75pt (1½ × enter, satu enter 12.5pt
     * seperti jarak "2× enter" 25pt di bawah kotak nomor) — dulu 28pt dan
     * dinilai terlalu mepet tepi atas.
     *
     * Lebar mengikuti rasio isi PNG (393×519 → 0.757), yang kebetulan sama
     * dengan potongan logo di docx (164×217 → 0.756).
     */
    private const COVER_LOGO_ATAS = 42.75;

    private const COVER_LOGO_TINGGI = 118.5;

    private const COVER_LOGO_LEBAR = 89.7;

    /**
     * Tinggi pita yang ditutup di halaman 1 untuk menghapus kop.
     *
     * Kop menempati 16pt–162pt (`@page` margin-top 162pt, `.kop-fixed`
     * top -146pt), jadi 163pt sudah menutupinya penuh dengan 1pt sisa.
     *
     * JANGAN dinaikkan "biar aman": sesudah logo dikecilkan, blok judul cover
     * mulai di 165.25pt — nilai lama 175pt akan MENIMPA baris "PT PUTRA
     * PERKASA ABADI". Logo sendiri aman berapa pun nilainya karena digambar
     * SESUDAH pita ini.
     */
    private const COVER_TUTUP_KOP = 163.0;

    /**
     * Lebar kolom teks cover (nama perusahaan / jenis / JUDUL).
     *
     * Docx aslinya 303.6pt, tapi judul PPA jauh lebih panjang dari contoh docx
     * ("INSTALASI DAN PENGGUNAAN MONITOR SS6" = 492.6pt @22pt bold) sehingga
     * pecah 3 baris dan mendorong kotak nomor dokumen turun. 500pt masih
     * menyisakan ±23pt ke bingkai halaman (24pt dari tepi kertas).
     *
     * DIPAKAI BERSAMA blade `_cover` lewat variabel `coverJudulLebar` — satu
     * angka saja, supaya hitungan baris dan lebar render tak mungkin beda.
     */
    private const COVER_JUDUL_LEBAR = 500.0;

    /**
     * Kolom `documents` yang TIDAK boleh masuk sidik cetak.
     *
     * `current_step` = posisi kursor wizard, bukan isi: cetakan selalu memuat
     * SEMUA bab, di langkah mana pun pengguna berada. Kalau ia ikut dihitung,
     * setiap "Langkah Berikutnya" akan mengubah sidik dan memicu render ulang —
     * persis keluhan panel pratinjau "mati-nyala" yang hendak diobati.
     *
     * `updated_at` ikut keluar karena menyimpan langkah pun membumbungkannya,
     * jadi ia menyeret masalah yang sama. Aman: setiap perubahan yang benar-
     * benar terlihat di cetakan tersimpan di salah satu kolom lain yang tetap
     * dihitung, atau di agregat relasinya.
     *
     * `deleted_at` tak pernah tercetak — dokumen terhapus tak bisa dibuka.
     */
    private const ABAI_SIDIK = ['current_step', 'updated_at', 'deleted_at'];

    /**
     * Sidik jari isi cetakan — berubah bila (dan hanya bila) PDF-nya berubah.
     *
     * Dihitung TANPA merender: hanya beberapa agregat berindeks + mtime berkas
     * cetak. Itulah gunanya — pemanggil bisa tahu "PDF ini masih sama" dengan
     * ongkos ±1ms, alih-alih 0.5–1 detik render.
     *
     * Yang ikut dihitung dan KENAPA:
     *  - SELURUH kolom `documents` kecuali {@see ABAI_SIDIK} → judul, status,
     *    nomor, edisi/revisi, tanggal terbit, penandatangan. Diambil borongan
     *    supaya kolom baru yang memengaruhi cetakan ikut sendiri; daftar kolom
     *    yang ditulis tangan pasti tertinggal suatu hari.
     *  - agregat `document_contents` → seluruh isi bab, termasuk path lampiran
     *    (foto diunggah lewat AJAX yang menulis ke value_json).
     *  - agregat `reviews` → tanggal pada baris pengesahan.
     *  - agregat `document_authors` → baris "Dibuat Oleh" tambahan di cover;
     *    melampirkan co-author TIDAK menyentuh `documents.updated_at`.
     *  - `document_types.updated_at` → schema JSON adalah sumber tata letak.
     *  - mtime berkas cetak → supaya saat pengembangan, mengedit template atau
     *    kelas ini LANGSUNG membatalkan cache. Tanpa ini kita menanam jebakan:
     *    template diubah, PDF tetap yang lama, dan tak ada petunjuk kenapa.
     */
    public function sidik(Document $document): string
    {
        /*
        | Kolomnya dibaca LANGSUNG dari database, bukan dari atribut model.
        | Model yang baru dibuat belum memantulkan nilai bawaan kolom — pada
        | draft segar `no_revisi` masih NULL di memori padahal 0 di tabel — jadi
        | sidik dari model dan sidik dari `fresh()` bisa berbeda untuk dokumen
        | yang SAMA. Cache dengan kunci yang goyah lebih buruk daripada tanpa
        | cache: ia merender ulang terus dan tak pernah kelihatan salah.
        */
        $baris = (array) DB::table('documents')->where('id', $document->id)->first();
        $baris = array_diff_key($baris, array_flip(self::ABAI_SIDIK));
        $baris['tipe'] = DB::table('document_types')
            ->where('id', $baris['document_type_id'] ?? 0)->value('updated_at');

        // Satu query per relasi (count + max sekaligus). Ketiganya berindeks
        // `document_id`, jadi ongkosnya sub-milidetik.
        foreach (['document_contents', 'reviews', 'document_authors'] as $tabel) {
            $agg = DB::table($tabel)->where('document_id', $document->id)
                ->selectRaw('COUNT(*) as n, MAX(updated_at) as t')->first();
            $baris[$tabel] = $agg->n.'@'.$agg->t;
        }

        return substr(md5(implode('|', $baris).'|'.self::versiKodeCetak()), 0, 16);
    }

    /** mtime tertinggi di antara berkas yang menentukan tata letak cetak. */
    private static function versiKodeCetak(): int
    {
        static $versi = null;
        if ($versi !== null) {
            return $versi;
        }

        $berkas = glob(resource_path('views/documents/print/*.blade.php')) ?: [];

        // SELURUH berkas kelas cetak, bukan cuma __FILE__: tata letak juga
        // ditentukan mesin paginasi & pembongkar stream di sebelah. Memperbaiki
        // salah satunya tanpa menyentuh berkas ini dulu TIDAK membatalkan
        // singgahan — persis jebakan yang diperingatkan docblock di atas.
        $berkas = array_merge(
            $berkas,
            glob(__DIR__.'/*.php') ?: [],
            glob(dirname(__DIR__).'/*PrintLayout.php') ?: [],
        );

        return $versi = max(array_map('filemtime', $berkas));
    }

    /**
     * Bytes PDF siap kirim — hasil render di-cache di disk per {@see sidik()}.
     *
     * Alasan adanya: pratinjau wizard menampilkan PDF sungguhan, jadi tiap
     * berpindah langkah dulu memicu render penuh (SOP ±1 dtk, JSA ±0.8 dtk).
     * Padahal berpindah langkah TIDAK mengubah isi — sidiknya sama, berkasnya
     * sama. Yang benar bukan mempercepat mesinnya, melainkan tidak
     * menjalankannya. Mesin 2-fase sendiri tak disentuh: seluruh konstantanya
     * hasil pengukuran (lihat peringatan di atas).
     *
     * Sengaja BERKAS, bukan `Cache::remember`. Cache project ini memakai driver
     * `database`, dan kolom `cache.value` bertipe TEXT — batasnya 64 KB,
     * sedangkan PDF di sini 130 KB–1,5 MB. Ditambah biner PDF memuat byte yang
     * tak sah sebagai UTF-8, sehingga INSERT-nya gagal total. Blob biner memang
     * tempatnya di berkas.
     *
     * Cache dibersihkan sendiri: menulis versi baru menghapus versi lama
     * dokumen yang sama, jadi paling banyak SATU berkas per dokumen.
     */
    public function cetak(Document $document): string
    {
        $jenis = $document->type?->code;
        $berkas = self::jalurSinggahan($document->id, $jenis, $this->sidik($document));

        if (is_file($berkas)) {
            return file_get_contents($berkas);
        }

        $bytes = $this->render($document)->output();

        $dir = dirname($berkas);
        is_dir($dir) || @mkdir($dir, 0775, true);
        foreach (glob(self::jalurSinggahan($document->id, $jenis)) ?: [] as $lama) {
            @unlink($lama);
        }
        @file_put_contents($berkas, $bytes);
        $this->sapuSinggahan();

        return $bytes;
    }

    /**
     * Jalur berkas singgahan — SATU-SATUNYA tempat bentuk jalur itu ditulis.
     *
     * Singgahan dikelompokkan per JENIS dokumen: `pdf-cache/{JENIS}/{id}-{sidik}.pdf`.
     * Dulu ia datar di akar, dan bentuknya ditulis ulang di TIGA tempat berbeda
     * (kelas ini, {@see \App\Services\DocumentPurger}, dan test-nya). Tiga tempat
     * yang harus sepakat adalah tiga tempat yang suatu hari tidak sepakat — dan
     * gejalanya paling buruk: berkas yatim yang menumpuk tanpa pernah terlihat di
     * layar mana pun.
     *
     * Tanpa `$sidik` ia mengembalikan POLA GLOB untuk seluruh versi dokumen itu —
     * bentuk yang dibutuhkan saat membuang versi lama dan saat memusnahkan dokumen.
     *
     * @param  string|null  $jenis  `document_types.code`; null → folder `LAIN`
     * @param  string  $sidik  sidik isi, atau `*` untuk pola glob
     */
    public static function jalurSinggahan(int $id, ?string $jenis, string $sidik = '*'): string
    {
        return storage_path('app/pdf-cache/'.self::folderAman($jenis)."/{$id}-{$sidik}.pdf");
    }

    /**
     * Kode (jenis dokumen / departemen) yang aman dipakai sebagai NAMA FOLDER.
     *
     * Kodenya datang dari database, dan hari ini bersih (SOP/SP/IK/JSA). Saringan
     * ini untuk jenis dokumen yang ditambahkan kelak: satu kode bertanda `/` atau
     * `..` sudah cukup membuat berkas ditulis di luar folder singgahan. Ongkosnya
     * satu baris, dibayar sekali, di satu tempat.
     *
     * Publik sejak butir 0: berkas dokumen LAMA disimpan di
     * `arsip/{DEPT}/{JENIS}/` dan menghadapi persis bahaya yang sama, dari kolom
     * database yang sama. Satu saringan, dua pemakai — bukan dua salinan.
     */
    public static function folderAman(?string $kode): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $kode)) ?: 'LAIN';
    }

    /**
     * Buang berkas singgahan yang sudah lama tak tersentuh.
     *
     * Aturan "satu berkas per dokumen" hanya berlaku bagi dokumen yang MASIH
     * dirender: dokumen yang dihapus tak pernah datang lagi untuk membersihkan
     * berkasnya sendiri, dan test (yang me-rollback datanya) meninggalkan yatim
     * setiap kali dijalankan. Tanpa sapuan ini foldernya tumbuh selamanya.
     *
     * Dijalankan hanya pada render DINGIN — yang memang jarang — jadi ia tak
     * pernah membebani jalur cepat. Menghapus singgahan yang masih sah pun tak
     * merugikan: paling banter satu render ulang.
     *
     * ponytail: pemindaian folder satu tingkat; kalau dokumen menembus puluhan
     * ribu, pindahkan ke perintah terjadwal.
     */
    private function sapuSinggahan(): void
    {
        $akar = storage_path('app/pdf-cache');

        /*
        | Bentuk LAMA — datar di akar, `{id}-{sidik}.pdf`. Sejak singgahan
        | dikelompokkan per jenis, berkas berbentuk itu tak pernah lagi ditulis
        | MAUPUN dibaca siapa pun: ia tak terjangkau `jalurSinggahan()`.
        |
        | Dibuang TANPA tenggang umur, berbeda dari bentuk baru di bawah. Itu
        | disengaja: ongkos salahnya nol — isinya cuma singgahan, paling banter
        | satu render ulang — sedangkan tenggang 30 hari hanya berarti berkas
        | yang sudah pasti mati menganggur sebulan lagi. Inilah yang membuat
        | peralihan bentuk jalur bersih dengan sendirinya.
        */
        foreach (glob($akar.'/*.pdf') ?: [] as $datar) {
            @unlink($datar);
        }

        // Bentuk BARU — tetap bertenggang, karena berkas di sini MASIH dipakai.
        $batas = time() - 30 * 86400;
        foreach (glob($akar.'/*/*.pdf') ?: [] as $f) {
            if (@filemtime($f) < $batas) {
                @unlink($f);
            }
        }
    }

    /**
     * Render PDF siap unduh, lengkap dengan stamp nomor halaman.
     *
     * Dipakai bersama oleh unduhan pengguna DAN test tata letak, sehingga
     * yang diuji benar-benar berkas yang sama dengan yang diterima pengguna.
     * Test memanggil ini LANGSUNG (bukan {@see cetak()}) supaya yang diukur
     * selalu hasil render nyata, tak pernah isi cache.
     */
    public function render(Document $document): Dompdf
    {
        $data = $this->viewData($document);
        $view = $this->viewName($document);

        // JSA: mesin paginasi 2-fase (mengulang header Langkah/Bahaya di halaman
        // lanjutan + menutup border per-halaman spt docs/JSA new.docx). Jenis lain
        // (SOP/IK/SP) tetap 1-lintasan.
        if ($view === 'documents.print.render-jsa') {
            return $this->renderJsaPaginated($data, $view);
        }

        return $this->renderStandardPaginated($data, $view);
    }

    /** Template cetak jenis dokumen (sumber tunggal utk PDF & preview layar). */
    public function viewName(Document $document): string
    {
        return SchemaService::for($document->type)->raw()['print_view'] ?? 'documents.print.render';
    }

    /**
     * Data untuk template cetak/preview. Gambar (logo + lampiran) ditanam sebagai
     * data URI base64 supaya tampil baik di DomPDF maupun di browser.
     */
    public function viewData(Document $document): array
    {
        // Departemen penandatangan ikut dimuat: kolom JABATAN halaman pengesahan
        // & blok TTD JSA mencetak "Group Leader (ICTMD)" → tanpa ini, tiap baris
        // memicu query sendiri (dan JSA dirender berkali-kali oleh mesin 2-fase).
        $document->load('creator.department', 'reviewer.department', 'approver.department', 'contents', 'reviews');

        // `untuk()`, bukan `for()`: bab opsional yang dimatikan tak ikut
        // tercetak dan bab sesudahnya naik nomor. Satu titik ini menutup
        // SELURUH jalur cetak — render, pratinjau, petaHalamanBab(),
        // activitySection() — karena semuanya membaca $data['schema'] ini.
        $schema = SchemaService::untuk($document);

        $embed = function (?string $path) {
            if (! $path) {
                return null;
            }
            $full = storage_path('app/public/'.$path);

            return is_file($full)
                ? 'data:'.mime_content_type($full).';base64,'.base64_encode(file_get_contents($full))
                : null;
        };

        return [
            'document' => $document,
            'schema' => $schema,
            'contentMap' => $document->contentMap(),
            'logo' => $this->logoDataUri(),
            'stamp' => $this->approvalStampDataUri(),
            'embed' => $embed,
            // Orientasi dibaca SEKALI dari schema; dipakai PDF (setPaper) maupun
            // preview (lebar kertas di layar) → tak mungkin beda.
            'orientation' => ($schema->raw()['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait',
            // Berapa baris judul memakan tempat di cover. Angka ini menentukan
            // tepi atas kotak pengesahan, jadi ia DIUKUR, bukan ditaksir dari
            // jumlah huruf: salah tebak berarti isi cover menabrak kotaknya.
            'coverJudulBaris' => ($schema->raw()['cover_page'] ?? null) === '_cover'
                ? $this->coverJudulBaris((string) $document->title)
                : 1,
            'coverJudulLebar' => self::COVER_JUDUL_LEBAR,
        ];
    }

    /**
     * Berapa baris yang dimakan judul dokumen pada blok judul cover.
     *
     * Kolom teks cover selebar self::COVER_JUDUL_LEBAR dan judulnya Arial bold
     * 22pt. Lebarnya ditanya langsung ke FontMetrics DomPDF — mesin yang sama
     * yang nanti merender — sehingga jawabannya tak mungkin berbeda dari
     * kenyataan cetak.
     *
     * Pembungkusnya disimulasikan KATA PER KATA (greedy, persis DomPDF), bukan
     * lebar total ÷ lebar kolom: rumus bagi itu meleset karena kata terakhir
     * yang tak muat pindah baris utuh. "INSTALASI DAN PENGGUNAAN MONITOR SS6"
     * ditaksirnya 2 baris padahal tercetak 3 — dan selisih satu baris itulah
     * yang membuat kotak pengesahan bergeser dari tempat yang dihitung.
     */
    private function coverJudulBaris(string $judul): int
    {
        $metrics = (new Dompdf)->getFontMetrics();
        $font = $metrics->getFont('Helvetica', 'bold');
        if (! $font) {
            return 1;
        }

        $baris = 1;
        $sekarang = '';
        foreach (preg_split('/\s+/', trim(mb_strtoupper($judul))) ?: [] as $kata) {
            if ($kata === '') {
                continue;
            }
            $calon = $sekarang === '' ? $kata : $sekarang.' '.$kata;
            if ($sekarang !== '' && $metrics->getTextWidth($calon, $font, 22.0) > self::COVER_JUDUL_LEBAR) {
                $baris++;
                $sekarang = $kata;
            } else {
                $sekarang = $calon;
            }
        }

        return $baris;
    }

    /**
     * Render SOP/SP/IK dgn paginasi 2-fase pada tabel AKTIVITAS. Tanpa ini, grup
     * aktivitas yang terpotong antar halaman membuat (a) sel PIC rowspan RUSAK
     * (PIC hilang di halaman lanjutan) dan (b) border tabel MENGGANTUNG (tepi
     * bawah halaman tak tertutup) — krn kelas "sel gabungan" membuang border.
     *   1) Render PROBE "datar" (tanpa rowspan/kelas border) → ukur baris awal
     *      tiap halaman lewat anchor 1pt [[Ai]].
     *   2) plan() men-scope rowspan PIC & kelas border PER HALAMAN, lalu render
     *      ulang → tepi selalu tertutup & PIC diulang di halaman lanjutan.
     * Bila tak ada aktivitas / anchor tak terbaca → fallback 1-lintasan.
     */
    private function renderStandardPaginated(array $data, string $view): Dompdf
    {
        [$aktKey, $autoNumber] = $this->activitySection($data['schema']);

        $flat = $aktKey !== null
            ? ActivityPrintLayout::flatten((array) ($data['contentMap'][$aktKey] ?? []), $autoNumber)
            : [];

        $starts = [];
        $usePlan = false;
        if ($flat !== []) {
            $usePlan = true;
            $probeRows = ActivityPrintLayout::plan($flat, []);
            // Kop berulang tiap halaman memuat "Dokumen" (No. Dokumen) → penanda halaman.
            $measured = $this->measureRowPageStarts(
                $this->renderStandardOnce($view, $data, $aktKey, $probeRows, true), 'Dokumen', 'A'
            );
            if ($measured === null) {
                $usePlan = false;
            } else {
                $starts = $this->hindariSubJudulYatim($view, $data, $aktKey, $flat, $measured);
            }
        }

        $finalData = $usePlan
            ? array_merge($data, [
                'aktRows' => [$aktKey => ActivityPrintLayout::plan($flat, $starts)],
                'probeFlat' => false,
            ])
            : $data;

        $dompdf = Pdf::loadView($view, $finalData)
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();

        // Jenis yang schema-nya menyatakan cover mendapat halaman 1 tersendiri:
        // dicat canvas (kop dihapus, bingkai & logo digambar) dan tidak ikut
        // dinomori. Selama schema di database belum di-seed ulang, keduanya
        // mati sendiri dan cetakan lama tetap seperti semula.
        $punyaCover = ($data['schema']->raw()['cover_page'] ?? null) === '_cover';
        if ($punyaCover) {
            $this->catCoverHalamanSatu($dompdf);
        }
        $this->stampPageNumbers($dompdf, $data['orientation'], punyaCover: $punyaCover);

        return $dompdf;
    }

    /**
     * Cat halaman 1 sebagai COVER: hapus kop, gambar bingkai halaman, gambar logo.
     *
     * KENAPA canvas dan bukan HTML — kop dipasang `position: fixed` supaya
     * berulang di tiap halaman, dan DomPDF TIDAK menyediakan cara mematikannya
     * untuk satu halaman saja. `page_script` berjalan SESUDAH isi halaman
     * digambar, jadi hanya di sinilah kop bisa ditutup. Karena kop ditutup,
     * logo cover (yang berada di pita yang sama, 3–6.5cm dari tepi atas) harus
     * ikut digambar di sini — kalau ditaruh di HTML, ia akan ikut tertutup.
     *
     * Sisa cover (nama perusahaan ke bawah) tetap alir HTML biasa, mulai di
     * 184.25pt — persis di bawah logo, sudah di luar pita yang ditutup.
     *
     * Koordinatnya HASIL PENGUKURAN docs/Cover_Depan.docx; jangan diubah tanpa
     * mengukur ulang (HANDOVER §7).
     */
    public function catCoverHalamanSatu(Dompdf $dompdf): void
    {
        $logo = $this->logoCoverPath();

        $dompdf->getCanvas()->page_script(function (int $pageNumber, int $pageCount, $pdf) use ($logo) {
            if ($pageNumber !== 1) {
                return;
            }

            $pdf->filled_rectangle(0.0, 0.0, self::A4_LEBAR, self::COVER_TUTUP_KOP, [1, 1, 1]);

            $pdf->rectangle(
                self::COVER_BINGKAI_JARAK,
                self::COVER_BINGKAI_JARAK,
                self::A4_LEBAR - 2 * self::COVER_BINGKAI_JARAK,
                self::A4_TINGGI - 2 * self::COVER_BINGKAI_JARAK,
                [0, 0, 0],
                self::COVER_BINGKAI_TEBAL,
            );

            if ($logo !== null) {
                $pdf->image(
                    $logo,
                    (self::A4_LEBAR - self::COVER_LOGO_LEBAR) / 2,
                    self::COVER_LOGO_ATAS,
                    self::COVER_LOGO_LEBAR,
                    self::COVER_LOGO_TINGGI,
                );
            }
        });
    }

    /**
     * Titik potong yang bebas judul sub-bab YATIM — dan TERBUKTI stabil.
     *
     * {@see ActivityPrintLayout::hindariYatim()} menurunkan judul sub-bab yang
     * tertinggal sendirian di dasar halaman. Menurunkannya menambah satu baris ke
     * halaman berikutnya, yang bisa MELUBER — dan halaman luber memaksa DomPDF
     * memotong sendiri di titik yang tak di-scope (PIC hilang, border menggantung).
     * Jadi calonnya dipaksa lalu DIUKUR ULANG; hanya yang terukur PERSIS sama
     * dengan yang dipaksa yang dipakai. Pola verifikasi ini sama dgn Fase B
     * {@see renderJsaPaginated()}.
     *
     * Tidak konvergen → kembali ke $terukur (perilaku sebelum perbaikan ini):
     * judulnya tetap yatim, tapi tak pernah lebih buruk dari sebelumnya.
     *
     * ponytail: batas 2 iterasi; naikkan hanya bila ada dokumen nyata yang
     * terbukti belum konvergen.
     *
     * @param  int[]  $terukur  awal-halaman hasil ukur probe datar
     * @return int[] awal-halaman final
     */
    private function hindariSubJudulYatim(string $view, array $data, ?string $aktKey, array $flat, array $terukur): array
    {
        $kandidat = $terukur;

        for ($iter = 0; $iter < 2; $iter++) {
            $calon = ActivityPrintLayout::hindariYatim($flat, $kandidat);
            if ($calon === $kandidat) {
                break;   // tak ada yatim tersisa (atau tak ada geseran yang sah)
            }

            $ukur = $this->measureRowPageStarts(
                $this->renderStandardOnce($view, $data, $aktKey, ActivityPrintLayout::plan($flat, $calon)),
                'Dokumen', 'A'
            );
            if ($ukur === null) {
                break;
            }
            if ($ukur === $calon) {
                return $calon;   // dipaksa == terukur → aman dipakai
            }

            $kandidat = $ukur;
        }

        return $terukur;
    }

    /** Satu render SOP/SP/IK dgn baris aktivitas ter-plan (probe = tata letak datar). */
    private function renderStandardOnce(string $view, array $data, ?string $aktKey, array $rows, bool $probeFlat = false): Dompdf
    {
        $dompdf = Pdf::loadView($view, array_merge($data, [
            'aktRows' => [$aktKey => $rows],
            'probeFlat' => $probeFlat,
        ]))->setPaper('a4', $data['orientation'])->getDomPDF();
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Section "aktivitas" = repeatable_group yang punya field PIC (SOP/SP: bab V,
     * IK: bab I). Mengembalikan [key, auto_number] atau [null, ''].
     */
    private function activitySection(SchemaService $schema): array
    {
        foreach ($schema->allSections() as $section) {
            if (($section['type'] ?? '') !== 'repeatable_group') {
                continue;
            }
            $fields = collect($section['group_fields'] ?? $section['fields'] ?? []);
            if ($fields->contains('key', 'pic')) {
                return [$section['key'], $section['auto_number'] ?? ''];
            }
        }

        return [null, ''];
    }

    /**
     * Render JSA dgn paginasi 2-fase — ISI HALAMAN PENUH (v12).
     *
     * rowspan TAK BISA melintasi halaman di DomPDF (sel Langkah/Bahaya jadi KOSONG
     * di lanjutan) → kita scope PER HALAMAN + page-break DIPAKSA agar DomPDF
     * memotong PERSIS di titik yg di-scope. Titik itu dicari agar halaman TERISI PENUH:
     *   FASE A — KANDIDAT: render ALIRAN ALAMI rowspan NYATA tanpa paksa
     *     (forceBreaks=false). DomPDF mengisi & memecah grup sendiri → titik potong
     *     alami = fill points. Bisa BEROSILASI ±1 baris → kumpulkan semua kandidat.
     *   FASE B — PILIH: kandidat dgn potongan PALING SEDIKIT yang STABIL saat
     *     DIPAKSA (forced-measured == kandidat) → scope PERSIS cocok dgn potongan
     *     DomPDF: tak meluber, tak ada sel Langkah/Bahaya kosong.
     *
     * Penanda halaman = HEADER TABEL "Uraian" (berulang via thead); BUKAN
     * "FORMULIR" (kop hanya halaman 1) — salah pilih membuat halaman lanjutan tak
     * terdeteksi dan pengulangan Langkah/Bahaya GAGAL DIAM-DIAM.
     * Anchor tak terbaca → fallback 1-lintasan (tak pernah lebih buruk).
     */
    private function renderJsaPaginated(array $data, string $view): Dompdf
    {
        $analisa = is_array($data['contentMap']['analisa'] ?? null) ? $data['contentMap']['analisa'] : [];
        $flat = JsaPrintLayout::flatten($analisa);

        $starts = [];
        $usePlan = false;
        if ($flat !== []) {
            $cands = [];
            $probe = [];
            for ($iter = 0; $iter < 4; $iter++) {
                $m = $this->measureRowPageStarts(
                    $this->renderJsaOnce($view, $data, JsaPrintLayout::plan($flat, $probe), false, false), 'Uraian'
                );
                if ($m === null) {
                    break;
                }
                $cands[implode(',', $m)] = $m;
                if ($m === $probe) {
                    break;   // konvergen
                }
                $probe = $m;
            }

            $list = array_values($cands);
            usort($list, fn ($a, $b) => count($a) <=> count($b));
            foreach ($list as $cand) {
                $m = $this->measureRowPageStarts(
                    $this->renderJsaOnce($view, $data, JsaPrintLayout::plan($flat, $cand), false, true), 'Uraian'
                );
                if ($m === $cand) {
                    $starts = $cand;
                    $usePlan = true;
                    break;
                }
            }
        }

        $finalData = $usePlan
            ? array_merge($data, [
                'analisaRows' => JsaPrintLayout::plan($flat, $starts),
                'probeFlat' => false,
                'forceBreaks' => true,   // FINAL: paksa potong di titik konvergen
            ])
            : $data;
        $dompdf = Pdf::loadView($view, $finalData)
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();

        // Kop JSA SELALU hanya di halaman 1: formulir JSA tidak memakai lembar
        // CATATAN REVISI di halaman depan (permintaan pemilik), jadi halaman 1
        // selalu body JSA — baik dokumen baru maupun hasil revisi.
        $this->stampPageNumbers($dompdf, $data['orientation'], [1]);

        return $dompdf;
    }

    /**
     * Satu render JSA dgn baris ter-plan. $probeFlat=true → tata letak DATAR
     * (tanpa rowspan) khusus pengukuran Fase 1.
     */
    private function renderJsaOnce(string $view, array $data, array $rows, bool $probeFlat = false, bool $forceBreaks = true): Dompdf
    {
        $dompdf = Pdf::loadView($view, array_merge($data, ['analisaRows' => $rows, 'probeFlat' => $probeFlat, 'forceBreaks' => $forceBreaks]))
            ->setPaper('a4', $data['orientation'])
            ->getDomPDF();
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Ukur, dari anchor 1pt "[[Ri]]" pada stream PDF, indeks baris yang MEMULAI
     * tiap halaman (≥ halaman 2) — dipakai sbg $pageStarts untuk plan(). Halaman
     * dikenali dari penanda teks yang berulang tiap halaman. Mengembalikan null
     * bila tak ada anchor terbaca (→ fallback 1-lintasan).
     *
     * @return int[]|null indeks baris awal-halaman, menaik.
     */
    private function measureRowPageStarts(Dompdf $dompdf, string $pageMarker = 'FORMULIR', string $anchorPrefix = 'R'): ?array
    {
        $rowPage = $this->petaAnchorHalaman($dompdf, $pageMarker, $anchorPrefix);

        if ($rowPage === []) {
            return null;
        }

        $starts = [];
        $prevPage = 1;
        foreach ($rowPage as $idx => $pg) {
            if ($pg > $prevPage) {
                $starts[] = $idx;
                $prevPage = $pg;
            }
        }

        return $starts;
    }

    /**
     * Peta anchor `[[{prefix}{i}]]` → NOMOR HALAMAN FISIK tempat ia mendarat.
     *
     * Inti pengukuran yang dipakai bersama {@see measureRowPageStarts()} (baris
     * awal halaman, mesin paginasi 2-fase) dan {@see petaHalamanBab()} (rentang
     * halaman per bab, lembar Catatan Revisi). Satu pembongkar stream, dua
     * pemakai — jangan menulis pengukur kedua: ia akan berbeda dari hasil cetak
     * sebenarnya, dan PrintLayoutTest tak akan menangkapnya.
     *
     * @return array<int, int> indeks anchor → halaman, menaik menurut indeks
     */
    private function petaAnchorHalaman(Dompdf $dompdf, string $pageMarker, string $anchorPrefix): array
    {
        $pageNo = 0;
        $rowPage = [];
        foreach (PdfStream::semua($dompdf->output()) as $c) {
            $txt = PdfStream::teks($c);
            if (! str_contains($txt, $pageMarker)) {   // hanya stream halaman nyata
                continue;
            }
            $pageNo++;
            if (preg_match_all('/\[\['.preg_quote($anchorPrefix, '/').'(\d+)\]\]/', $txt, $mm)) {
                foreach ($mm[1] as $idx) {
                    $idx = (int) $idx;
                    if (! isset($rowPage[$idx])) {
                        $rowPage[$idx] = $pageNo;
                    }
                }
            }
        }

        ksort($rowPage);

        return $rowPage;
    }

    /**
     * Peta `section_key` → [halaman AWAL, halaman AKHIR] pada cetakan nyata —
     * kolom "Hal." lembar CATATAN REVISI (butir 7b).
     *
     * Diukur dari PDF yang benar-benar dirender, bukan ditaksir dari panjang
     * teks: tata letak DomPDF terlalu rapuh untuk ditebak (itu sebabnya
     * PrintLayoutTest membaca stream PDF). Tiap bab menanam DUA anchor 1pt putih
     * — `[[S{2i}]]` di bilah babnya, `[[S{2i+1}]]` di isi terakhirnya — sehingga
     * bab yang memakan beberapa halaman terbaca sebagai rentang, bukan satu
     * titik. Anchor HANYA muncul pada render pengukuran (`anchorBab`), tak
     * pernah pada berkas yang diterima pengguna.
     *
     * Nomor yang dikembalikan adalah nomor yang TERCETAK di kop, bukan nomor
     * lembar kertas: pada jenis ber-cover, halaman 1 tak bernomor dan hitungan
     * isi mulai dari 1 lagi (lihat {@see stampPageNumbers()}).
     *
     * Catatan jujur: menambah baris ke lembar Catatan Revisi bisa mendorong isi
     * turun satu halaman, jadi angka ini usulan — kolomnya tetap boleh disunting.
     *
     * @return array<string, array{0:int,1:int}>
     */
    public function petaHalamanBab(Document $document): array
    {
        $view = $this->viewName($document);

        // JSA tak memakai lembar CATATAN REVISI (Document::usesRevisionLog()),
        // dan mesin paginasinya sudah memakai anchor untuk urusan lain.
        if ($view === 'documents.print.render-jsa') {
            return [];
        }

        $data = $this->viewData($document);
        $dompdf = $this->renderStandardPaginated(array_merge($data, ['anchorBab' => true]), $view);
        $anchor = $this->petaAnchorHalaman($dompdf, 'Dokumen', 'S');

        $geser = ($data['schema']->raw()['cover_page'] ?? null) === '_cover' ? 1 : 0;

        $peta = [];
        foreach ($data['schema']->allSections() as $i => $section) {
            $awal = $anchor[2 * $i] ?? null;
            if ($awal === null) {
                continue;   // bab tanpa bentuk cetak (mis. pemilih peninjau)
            }
            $akhir = $anchor[2 * $i + 1] ?? $awal;
            $peta[$section['key']] = [max(1, $awal - $geser), max(1, $akhir - $geser)];
        }

        return $peta;
    }

    /**
     * Stamp "Halaman X dari Y" ke sel Halaman pada kop. Sel dibiarkan KOSONG di
     * HTML; nilainya hanya bisa dihitung DomPDF usai render.
     *
     * KOORDINAT DIKALIBRASI terhadap posisi teks kop pada stream PDF nyata —
     * berubah bila CSS kop (tinggi baris/rasio kolom) diubah. JANGAN diutak-atik
     * tanpa mengukur ulang.
     */
    private function stampPageNumbers(Dompdf $dompdf, string $orientation, array $kopPages = [1], bool $punyaCover = false): void
    {
        $canvas = $dompdf->getCanvas();
        // Helvetica, sama dengan isi kop. Dulu 'DejaVu Sans' — sesudah dokumen
        // pindah ke Helvetica, nomor halaman jadi satu-satunya teks di kop yang
        // masih bergaya lain (lebih tebal & lebar).
        $font = $dompdf->getFontMetrics()->getFont('Helvetica');

        if ($orientation === 'landscape') {
            // JSA: kop HANYA di halaman 1. `page_text()` menulis di SEMUA halaman,
            // jadi dipakai `page_script()` yang tahu nomor halaman → stamp hanya di
            // $kopPages. Halaman analisa lanjutan tanpa kop = tanpa nomor.
            $canvas->page_script(function (int $pageNumber, int $pageCount, $pdf) use ($font, $kopPages) {
                if (! in_array($pageNumber, $kopPages, true)) {
                    return;
                }
                $pdf->text(711.0, 59.0, "{$pageNumber} dari {$pageCount}", $font, 7.5, [0, 0, 0]);
            });

            return;
        }

        // SOP/IK/SP: kop berulang tiap halaman (position:fixed) → satu koordinat
        // berlaku untuk semua halaman.
        if (! $punyaCover) {
            $canvas->page_text(392.8, 94.5, 'Halaman: {PAGE_NUM} dari {PAGE_COUNT}', $font, 8, [0, 0, 0]);

            return;
        }

        // Dengan cover, halaman 1 TIDAK bernomor (kebiasaan Word) dan hitungan
        // isi mulai dari 1 lagi — pembaca menghitung halaman ISI, bukan lembar
        // kertas. Koordinatnya sama persis dengan page_text di atas: keduanya
        // bermuara ke CPDF::text() dengan ruang koordinat yang sama.
        $canvas->page_script(function (int $pageNumber, int $pageCount, $pdf) use ($font) {
            if ($pageNumber === 1) {
                return;
            }

            $pdf->text(392.8, 94.5, 'Halaman: '.($pageNumber - 1).' dari '.($pageCount - 1), $font, 8, [0, 0, 0]);
        });
    }

    /**
     * Logo kop: memakai versi kecil yang di-cache — DomPDF merender ulang gambar
     * tiap halaman, jadi logo asli 285KB terasa berat. Versi ~170px jauh ringan.
     */
    private function logoDataUri(): ?string
    {
        $use = $this->logoPath();

        return $use === null ? null : 'data:image/png;base64,'.base64_encode(file_get_contents($use));
    }

    /**
     * Berkas logo yang dipakai cetakan (versi kecil yang di-cache).
     *
     * Dipisah dari {@see logoDataUri()} karena canvas cover memerlukan JALUR
     * berkas, bukan data URI — `Canvas::image()` membacanya lewat getimagesize().
     */
    private function logoPath(): ?string
    {
        $src = public_path('images/logo-ppa.png');
        if (! is_file($src)) {
            return null;
        }

        $cache = public_path('images/logo-ppa-pdf.png');
        if (function_exists('imagecreatefrompng') && (! is_file($cache) || filemtime($cache) < filemtime($src))) {
            $img = @imagecreatefrompng($src);
            if ($img) {
                $small = imagescale($img, 170);
                imagesavealpha($small, true);
                imagepng($small, $cache, 8);
                imagedestroy($img);
                imagedestroy($small);
            }
        }

        return is_file($cache) ? $cache : $src;
    }

    /**
     * Logo untuk COVER — versi yang paddingnya DIPOTONG.
     *
     * `logo-ppa.png` berukuran 828×828 tetapi gambarnya sendiri hanya mengisi
     * 47.5% × 62.7% dari bidang itu; sisanya transparan (terukur: padding 19%
     * atas, 18% bawah, 26% kiri-kanan). Akibatnya kotak logo 130pt hanya
     * menampilkan gambar setinggi ±82pt — itulah sebab pemilik menilai logonya
     * kecil, bukan karena kotaknya kurang lebar.
     *
     * Yang dipotong hanya bidang KOSONG; tak satu piksel gambar pun berubah.
     * Kop TIDAK memakai versi ini (tetap {@see logoPath()}): di sana logo duduk
     * dalam sel bergaris dan justru butuh napas di sekelilingnya.
     */
    private function logoCoverPath(): ?string
    {
        $src = public_path('images/logo-ppa.png');
        if (! is_file($src) || ! function_exists('imagecreatefrompng')) {
            return $this->logoPath();
        }

        $cache = public_path('images/logo-ppa-cover.png');
        if (is_file($cache) && filemtime($cache) >= filemtime($src)) {
            return $cache;
        }

        $img = @imagecreatefrompng($src);
        if (! $img) {
            return $this->logoPath();
        }

        // Kotak isi = piksel yang tak transparan DAN tak putih. Dipindai tiap
        // 2 piksel — cukup teliti untuk logo 828px, dua kali lebih cepat.
        [$w, $h] = [imagesx($img), imagesy($img)];
        [$minX, $minY, $maxX, $maxY] = [$w, $h, -1, -1];
        for ($y = 0; $y < $h; $y += 2) {
            for ($x = 0; $x < $w; $x += 2) {
                $c = imagecolorat($img, $x, $y);
                $alpha = ($c >> 24) & 0x7F;
                $putih = (($c >> 16) & 0xFF) > 245 && (($c >> 8) & 0xFF) > 245 && ($c & 0xFF) > 245;
                if ($alpha < 100 && ! $putih) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }

        if ($maxX < 0) {
            imagedestroy($img);

            return $this->logoPath();   // berkas kosong/aneh → pakai apa adanya
        }

        $potong = imagecrop($img, ['x' => $minX, 'y' => $minY, 'width' => $maxX - $minX + 1, 'height' => $maxY - $minY + 1]);
        imagedestroy($img);
        if (! $potong) {
            return $this->logoPath();
        }

        // Diperkecil ke ±220px: DomPDF merender ulang gambar tiap halaman, dan
        // cover hanya butuh ±120pt lebar.
        $kecil = imagescale($potong, 220);
        imagedestroy($potong);
        imagesavealpha($kecil, true);
        imagepng($kecil, $cache, 8);
        imagedestroy($kecil);

        return is_file($cache) ? $cache : $src;
    }

    /**
     * Cap APPROVED — memakai berkas public/images/approve-stamp.png APA ADANYA,
     * TANPA diproses GD sama sekali (permintaan tegas pemilik: "STEMPEL APPROVAL
     * JANGAN DI EDIT"). Untuk mengubah tampilan/kemiringan stempel, ganti
     * berkasnya — bukan kodenya.
     */
    private function approvalStampDataUri(): ?string
    {
        foreach (['images/approve-stamp.png', 'images/approve.png'] as $rel) {
            $path = public_path($rel);
            if (is_file($path)) {
                return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
