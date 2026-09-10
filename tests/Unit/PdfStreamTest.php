<?php

namespace Tests\Unit;

use App\Services\Print\PdfStream;
use PHPUnit\Framework\TestCase;

/**
 * Pembongkar stream PDF — penjaga bug yang membuat SATU HALAMAN PENUH hilang
 * tanpa suara dari hasil pengukuran mesin paginasi 2-fase.
 *
 * Regex lama `/stream\r?\n(.*?)\r?\nendstream/s` punya `\r?` di pemisah
 * PENUTUP. DomPDF tak pernah menulis "\r" di sana (lib/Cpdf.php: selalu
 * `stream\n … \nendstream`), jadi `\r?` itu tak pernah cocok dgn pemisah — ia
 * HANYA bisa memakan byte DATA terakhir bila byte itu kebetulan 0x0D. Sekali
 * termakan, payload-nya terpotong, gagal didekompresi, dan halamannya dibuang
 * diam-diam oleh penyaring penanda halaman di pemanggil.
 */
class PdfStreamTest extends TestCase
{
    /**
     * Stream yang byte terakhirnya 0x0D terbaca UTUH — inti bug produksi.
     *
     * Di berkas nyata byte itu ada di dalam payload deflate (SOP-SHE-03:
     * `/Length 3288`, yang tertangkap 3287 byte, dan payload-nya bisa
     * didekompresi lagi begitu byte itu dikembalikan). Di sini dipakai stream
     * POLOS supaya kerusakannya terlihat sebagai apa adanya — satu byte data
     * hilang — tanpa bergantung pada kebetulan hasil kompresi.
     */
    public function test_stream_yang_berakhir_carriage_return_tak_terpotong(): void
    {
        $isi = "BT (Dokumen) Tj ET\r";

        $pdf = "%PDF-1.7\n1 0 obj\n<< /Length ".strlen($isi)." >>\nstream\n".$isi."\nendstream\nendobj\n";

        $this->assertSame([$isi], PdfStream::semua($pdf),
            'byte 0x0D di ekor stream tak boleh ikut termakan pemisah "endstream"');

        // Prasyarat: regex lama memang memakannya. Kalau suatu saat regex itu
        // kembali dipakai, kegagalannya tak lagi senyap.
        preg_match('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $lama);
        $this->assertSame(strlen($isi) - 1, strlen($lama[1]), 'prasyarat: regex lama memotong sebyte');
    }

    /** Stream yang isinya kebetulan memuat urutan byte "\nendstream". */
    public function test_stream_yang_memuat_kata_endstream_tak_terpotong(): void
    {
        $isi = "BT (Dokumen) Tj\nendstream palsu di tengah data\nET";

        $pdf = "%PDF-1.7\n1 0 obj\n<< /Length ".strlen($isi)." >>\nstream\n".$isi."\nendstream\nendobj\n";

        $this->assertSame([$isi], PdfStream::semua($pdf));
    }

    /** Beberapa objek berturut terbaca terpisah dan urut. */
    public function test_banyak_stream_terbaca_urut(): void
    {
        $pdf = '%PDF-1.7';
        foreach (['satu', 'dua', 'tiga'] as $i => $isi) {
            $pdf .= "\n".($i + 1)." 0 obj\n<< /Length ".strlen($isi)." >>\nstream\n".$isi."\nendstream\nendobj\n";
        }

        $this->assertSame(['satu', 'dua', 'tiga'], PdfStream::semua($pdf));
    }

    /** Stream ber-/FlateDecode didekompres; teksnya terbaca kembali. */
    public function test_stream_terkompresi_didekompres(): void
    {
        $isi = 'BT /F1 9 Tf (No. Dokumen: PPA-ADRO-SOP-SHE-03) Tj ET';
        $z = gzcompress($isi);

        // Urutan kunci MENIRU DomPDF: `/Length` selalu kunci TERAKHIR sebelum
        // `>>\nstream` (lib/Cpdf.php) — itulah yang dicari pembongkarnya.
        $pdf = "%PDF-1.7\n1 0 obj\n<< /Filter /FlateDecode /Length ".strlen($z)." >>\n"
            ."stream\n".$z."\nendstream\nendobj\n";

        $streams = PdfStream::semua($pdf);

        $this->assertSame([$isi], $streams);
        $this->assertSame('No. Dokumen: PPA-ADRO-SOP-SHE-03', PdfStream::teks($streams[0]));
    }
}
