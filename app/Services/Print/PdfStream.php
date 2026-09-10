<?php

namespace App\Services\Print;

/**
 * Pembongkar stream isi PDF — SATU-SATUNYA cara yang benar membaca kembali
 * berkas yang baru saja dirender DomPDF.
 *
 * KENAPA BUKAN `preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s')`, yang dulu
 * disalin di berkas produksi maupun test:
 *
 *   1) Isi stream itu BINER hasil deflate — boleh memuat urutan byte apa pun,
 *      termasuk "\nendstream". Regex tak-rakus lalu memotong terlalu awal.
 *   2) Lebih sering lagi: DomPDF SELALU menulis `stream\n … \nendstream`
 *      (lib/Cpdf.php:1458, 2602) — tak pernah dengan "\r". Jadi `\r?` pada
 *      pemisah penutup tak pernah cocok dgn pemisah; ia HANYA bisa memakan byte
 *      DATA terakhir bila byte itu kebetulan 0x0D. Terukur pada SOP-SHE-03:
 *      `/Length 3288` tapi yang tertangkap 3287 byte.
 *
 * Akibat keduanya sama dan senyap: potongannya gagal didekompresi, jatuh ke
 * byte mentah, penanda halaman ("Dokumen"/"BT") tak ditemukan, lalu halaman itu
 * DIBUANG DIAM-DIAM oleh penyaring di pemanggil. Yang hilang bukan sebaris
 * melainkan SATU HALAMAN PENUH — dan pemanggilnya (mesin paginasi 2-fase)
 * menyimpulkan titik potong yang salah, sehingga border tabel menggantung dan
 * sel PIC hilang di halaman lanjutan.
 *
 * Sifatnya bergantung isi: mengganti satu kata di dokumen sudah menggeser byte
 * hasil kompresinya, jadi bug ini datang-pergi seolah acak.
 *
 * Menebak batas dari "endstream" berikutnya juga tak menyelamatkan — kalau
 * kandidat pertama gagal didekompresi, kandidat berikutnya justru MENELAN
 * stream sesudahnya. Batasnya tak perlu ditebak: kamus objeknya sudah menyebut
 * `/Length N` tepat sebelum kata `stream`.
 */
class PdfStream
{
    /**
     * Isi tiap objek stream, sudah didekompres, urut sesuai kemunculannya.
     *
     * @return string[]
     */
    public static function semua(string $raw): array
    {
        // (?<!end) supaya "endstream" tak ikut terbaca sebagai awal stream.
        preg_match_all('/(?<!end)stream\r?\n/', $raw, $awal, PREG_OFFSET_CAPTURE);

        $streams = [];
        $batasTerakhir = 0;

        foreach ($awal[0] as [$cocok, $posisi]) {
            // Awal yang jatuh di dalam stream sebelumnya = data biner yang
            // kebetulan berbunyi "stream", bukan objek baru.
            if ($posisi < $batasTerakhir) {
                continue;
            }

            $mulai = $posisi + strlen($cocok);

            // Panjangnya diambil dari kamus objek yang mendahuluinya. Dibatasi
            // 400 byte ke belakang supaya `/Length` milik objek LAIN tak
            // terpungut saat kamusnya (jarang) tak memuat kunci itu.
            $kamus = substr($raw, max(0, $posisi - 400), min($posisi, 400));
            if (! preg_match('/\/Length\s+(\d+)[^\/]*$/s', $kamus, $m)) {
                continue;   // `/Length 12 0 R` (rujukan tak langsung) — dilewati
            }

            $isi = substr($raw, $mulai, (int) $m[1]);
            $batasTerakhir = $mulai + (int) $m[1];

            // Dekompresi HANYA bila kamusnya memang bilang terkompresi. Menebak
            // dgn cara mencoba lalu gagal akan memuntahkan warning PHP untuk
            // stream polos — dan di test, warning itu dihitung sbg kegagalan.
            $keluar = false;
            if (str_contains($kamus, '/FlateDecode')) {
                $keluar = @gzuncompress($isi);
                if ($keluar === false) {
                    $keluar = @gzinflate($isi);   // deflate mentah (tanpa kepala zlib)
                }
            }

            // Stream tak-terkompresi tetap dibawa apa adanya — penyaring di
            // pemanggil ("BT" / penanda kop) yang memutuskan nasibnya.
            $streams[] = $keluar !== false ? $keluar : $isi;
        }

        return $streams;
    }

    /**
     * Teks yang terbaca dari satu stream: ambil literal "(...)", lalu buang byte
     * null (DomPDF menulis font tertanam sebagai UTF-16BE).
     */
    public static function teks(string $stream): string
    {
        preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $stream, $lit);

        $txt = '';
        foreach ($lit[0] as $l) {
            $inner = preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1));
            $txt .= str_replace("\x00", '', (string) $inner);
        }

        return $txt;
    }
}
