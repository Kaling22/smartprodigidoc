<?php

namespace App\Services\Print;

use App\Models\Document;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

/**
 * Menyisipkan lembar CATATAN REVISI ke depan berkas dokumen LAMA (PLAN-REVISI-v6
 * Fase H) — dan, bila diminta, membuang halaman awal berkas aslinya.
 *
 * KENAPA ADA KELAS SENDIRI: DomPDF hanya bisa MEMBUAT PDF; ia tak bisa membaca
 * apalagi memotong PDF yang sudah jadi. FPDI-lah yang bisa, dan ia bekerja dari
 * sisi yang berlawanan (mengimpor halaman, bukan merender HTML). Dua mesin cetak
 * yang berbeda dipisahkan dari {@see PdfRenderer} supaya kalibrasi tata letak
 * DomPDF di sana tak bercampur dengan urusan gabung-menggabung di sini.
 *
 * Berkas ASLI tak pernah disentuh: {@see gabung()} MENGEMBALIKAN isi berkas baru
 * dan tak menulis apa pun. Pemanggilnya yang memutuskan hendak menyimpannya ke
 * mana — dan bila langkah mana pun gagal, tak ada satu byte pun yang berubah.
 */
class ArsipPenggabung
{
    public function __construct(private readonly AuditService $audit) {}

    /** Jumlah halaman sebuah PDF (untuk validasi "potong" & hitungan nomor halaman). */
    public function jumlahHalaman(string $pathAbsolut): int
    {
        return $this->bacaAman(fn () => (new Fpdi)->setSourceFile($pathAbsolut));
    }

    /** Jumlah halaman berkas ASLI dokumen — dasar validasi potongan & penomoran. */
    public function halamanAsli(Document $document): int
    {
        return $this->jumlahHalaman(Storage::disk('local')->path($this->berkasAsli($document)));
    }

    /**
     * Cetak lembar, sisipkan di depan berkas dokumen, simpan sebagai berkas BARU.
     *
     * Berkas asli disimpan di `arsip_path_asli` dan tak pernah ditimpa, jadi
     * pemotongan boleh diulang dengan angka lain — atau dibatalkan sama sekali.
     * Sumber potongan pun SELALU berkas asli, bukan hasil potong sebelumnya;
     * kalau tidak, mengulang dengan angka 1 akan memakan halaman kedua.
     */
    public function terapkan(Document $document, int $potong, int $halamanAwal): void
    {
        $disk = Storage::disk('local');
        $asli = $this->berkasAsli($document);
        $halaman = $this->jumlahHalaman($disk->path($asli));

        $lembar = $this->lembar(
            $document,
            (array) ($document->fresh()->contentMap()['catatan_revisi'] ?? []),
            $halamanAwal,
            $halaman - $potong + 1,   // lembar sisipan hampir selalu satu halaman
        );

        $hasil = $this->gabung($disk->path($asli), $lembar, $potong);

        // Berkas baru ditulis DULU, yang lama dibuang SESUDAH baris tersimpan —
        // urutan yang sama dengan DocumentArsipController::update(), dan
        // alasannya sama: terbalik, satu galat penyimpanan meninggalkan dokumen
        // Berlaku tanpa berkas sama sekali.
        $baru = dirname($asli).'/'.Str::random(40).'.pdf';
        $disk->put($baru, $hasil);

        $gabungLama = $document->arsip_path_asli ? $document->arsip_path : null;

        $document->update(['arsip_path' => $baru, 'arsip_path_asli' => $asli]);

        if ($gabungLama) {
            $disk->delete($gabungLama);
        }

        $this->audit->log('document.arsip_catatan', $document->id, [
            'potong_halaman' => $potong,
            'halaman_awal' => $halamanAwal,
            'halaman_asli' => $halaman,
        ]);
    }

    /** Berkas unggahan yang UTUH — `arsip_path` sendiri selama belum pernah digabung. */
    private function berkasAsli(Document $document): string
    {
        return (string) ($document->arsip_path_asli ?: $document->arsip_path);
    }

    /**
     * Lembar CATATAN REVISI sebagai PDF berdiri sendiri (biner).
     *
     * Kop-nya kop dokumen yang sama, jadi lembar sisipan tak terbaca sebagai
     * kertas asing di depan berkas pindaian. Nomor halaman di-stamp dengan
     * koordinat yang SAMA PERSIS dengan cetakan biasa
     * ({@see PdfRenderer::stampPageNumbers()}) — angka AWALNYA saja yang datang
     * dari pengguna, karena hanya dia yang tahu penomoran dokumen lamanya.
     *
     * @param  array<int, array<string, mixed>>  $baris  baris lembar catatan revisi
     * @return string isi PDF (biner)
     */
    public function lembar(Document $document, array $baris, int $halamanAwal, int $totalHalaman): string
    {
        // Data cetak diambil dari sumber yang sama dengan cetakan biasa (schema,
        // logo, dokumen) — kop lembar sisipan mustahil menyimpang dari kop
        // dokumen SmartPro lainnya. Yang ditimpa hanya isinya.
        $data = app(PdfRenderer::class)->viewData($document);

        $pdf = Pdf::loadView(
            'documents.print.arsip-catatan',
            array_merge($data, ['contentMap' => ['catatan_revisi' => $baris]])
        )->setPaper('a4', 'portrait')->getDomPDF();

        $pdf->render();

        // page_script dipasang SESUDAH render, seperti di PdfRenderer: nomor
        // halaman baru bisa dihitung ketika seluruh halaman sudah terbentuk.
        $font = $pdf->getFontMetrics()->getFont('Helvetica');
        $pdf->getCanvas()->page_script(function (int $nomor, int $jumlah, $canvas) use ($font, $halamanAwal, $totalHalaman) {
            $canvas->text(392.8, 94.5, 'Halaman: '.($halamanAwal + $nomor - 1).' dari '.$totalHalaman, $font, 8, [0, 0, 0]);
        });

        return (string) $pdf->output();
    }

    /**
     * Lembar sisipan + berkas asli mulai halaman ke-($potong + 1).
     *
     * $potong = 0 berarti tak ada yang dibuang — lembar revisi sekadar
     * ditambahkan di depan.
     *
     * @param  string  $pdfLembar  ISI PDF lembar sisipan (biner), bukan jalur berkas
     * @return string isi PDF hasil gabung (biner)
     */
    public function gabung(string $pathAsli, string $pdfLembar, int $potong): string
    {
        return $this->bacaAman(function () use ($pathAsli, $pdfLembar, $potong) {
            $keluaran = new Fpdi;

            $lembar = $keluaran->setSourceFile(StreamReader::createByString($pdfLembar));
            for ($i = 1; $i <= $lembar; $i++) {
                $this->salinHalaman($keluaran, $i);
            }

            $asli = $keluaran->setSourceFile($pathAsli);
            for ($i = $potong + 1; $i <= $asli; $i++) {
                $this->salinHalaman($keluaran, $i);
            }

            return $keluaran->Output('S');
        });
    }

    /**
     * Satu halaman sumber → satu halaman keluaran, UKURAN & ORIENTASI IKUT
     * SUMBERNYA.
     *
     * Bukan detail sepele: dokumen lama dipindai dalam campuran A4 portrait dan
     * landscape (lampiran tabel), dan memaksa semuanya ke satu ukuran akan
     * meregangkan atau memotong halaman tanpa pernah melempar galat.
     */
    private function salinHalaman(Fpdi $pdf, int $halaman): void
    {
        $id = $pdf->importPage($halaman);
        $ukuran = $pdf->getTemplateSize($id);

        $pdf->AddPage($ukuran['orientation'], [$ukuran['width'], $ukuran['height']]);
        $pdf->useTemplate($id);
    }

    /**
     * Jalankan pembacaan PDF, terjemahkan kegagalannya jadi pesan yang bisa
     * dibaca pengguna.
     *
     * PDF TERENKRIPSI (dokumen lama sering dikunci "no copy" oleh pemindai) dan
     * berkas rusak sama-sama melempar turunan \Exception — FpdiException dari
     * pembacanya, \Exception biasa dari FPDF. Tanpa terjemahan ini, pemakainya
     * hanya melihat galat 500 dan tak pernah tahu bahwa yang perlu ia lakukan
     * adalah membuka kunci berkasnya.
     *
     * @template T
     *
     * @param  \Closure(): T  $aksi
     * @return T
     */
    private function bacaAman(\Closure $aksi)
    {
        try {
            return $aksi();
        } catch (\Exception $e) {
            /*
            | DomainException, BUKAN RuntimeException: QueryException Laravel
            | pun turunan RuntimeException, jadi penangkap di controller akan
            | ikut memakan galat basis data dan MENAMPILKAN SQL-nya kepada
            | pengguna sebagai "berkas tak bisa dibaca".
            */
            throw new \DomainException(
                'Berkas PDF ini tidak bisa dibaca (kemungkinan terkunci sandi/enkripsi atau rusak), '
                .'jadi lembar Catatan Revisi tidak disisipkan. Berkas aslinya tidak berubah. '
                .'Buka kunci berkasnya lalu ulangi. ['.$e->getMessage().']',
                previous: $e,
            );
        }
    }
}
