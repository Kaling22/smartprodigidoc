<?php

namespace App\Services\RichText;

use App\Models\Document;

/**
 * Foto yang DITEMPEL (Ctrl+V) atau diseret ke editor Deskripsi Aktivitas.
 *
 * Quill menyisipkannya sebagai `data:image/png;base64,…` — bukan berkas.
 * {@see PembersihHtml} hanya menerima `src` yang menunjuk lampiran/{DEPT}/{JENIS}/,
 * jadi tanpa kelas ini gambar tempelan DIBUANG saat simpan: tampak baik di editor,
 * lenyap di pratinjau dan di PDF.
 *
 * KENAPA DI SERVER, padahal peramban sudah menukarnya lebih dulu?
 *
 * Karena penukaran di peramban bisa GAGAL diam-diam — JS tak jalan, unggahan
 * ditolak, pengguna menekan Simpan sebelum penukarannya selesai — dan yang
 * dilihat penyusun cuma "fotonya hilang", tanpa sepatah pun keterangan. Yang di
 * peramban ada supaya base64 tak ikut terkirim di setiap autosave (satu
 * tangkapan layar 400KB, dikirim ulang tiap 1,2 detik). Yang di sini ada supaya
 * fotonya SAMPAI. Keduanya menuju tempat yang sama, dan yang belakangan tak
 * menemukan apa-apa untuk dikerjakan bila yang pertama sudah berhasil.
 *
 * WAJIB dijalankan SEBELUM PembersihHtml::bersihkan(), sebab sesudahnya data
 * URI-nya sudah lenyap.
 */
class GambarTempel
{
    /** Lebar maksimum yang disimpan. Di PDF gambarnya toh dibatasi .akt-img. */
    private const LEBAR_MAKS = 1600;

    /** Batas masuk akal sebuah tempelan, sebelum didekode. */
    private const BYTE_MAKS = 12 * 1024 * 1024;

    /**
     * Tukar setiap `<img src="data:…">` menjadi berkas di lampiran/{DEPT}/{JENIS}/.
     *
     * Gambar yang tak bisa dibaca DIBIARKAN apa adanya — bukan dibuang di sini:
     * PembersihHtml yang berhak memutuskan itu, dan menaruh keputusan yang sama
     * di dua tempat berarti kelak keduanya berbeda pendapat.
     */
    public function tukar(Document $document, string $html, string $sectionKey): string
    {
        if (! str_contains($html, 'data:image/')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/(<img\b[^>]*\bsrc\s*=\s*")(data:image\/[a-z0-9.+-]+;base64,[^"]+)(")/i',
            function (array $m) use ($document, $sectionKey) {
                $jalur = $this->simpan($document, $m[2], $sectionKey);

                return $jalur === null ? $m[0] : $m[1].'/storage/'.$jalur.$m[3];
            },
            $html
        );
    }

    /** @return string|null jalur relatif disk `public`, atau null bila tak bisa disimpan */
    private function simpan(Document $document, string $dataUri, string $sectionKey): ?string
    {
        $koma = strpos($dataUri, ',');
        if ($koma === false) {
            return null;
        }

        $base64 = substr($dataUri, $koma + 1);
        if (strlen($base64) > self::BYTE_MAKS) {
            return null;
        }

        $biner = base64_decode($base64, true);
        if ($biner === false || $biner === '') {
            return null;
        }

        // Jenis diambil dari ISI berkas, bukan dari yang ditulis di data URI:
        // yang ditulis di sana datang dari peramban dan bisa dikarang.
        $info = @getimagesizefromstring($biner);
        if ($info === false) {
            return null;
        }

        [$biner, $ext] = $this->rapikan($biner, $info);
        if ($biner === null) {
            return null;
        }

        $dept = $document->department?->code ?? 'UMUM';
        $jenis = $document->type?->code ?? 'DOC';
        $folder = "lampiran/{$dept}/{$jenis}";
        $nama = uniqid('img_').'.'.$ext;

        $abs = storage_path('app/public/'.$folder);
        if (! is_dir($abs) && ! @mkdir($abs, 0777, true) && ! is_dir($abs)) {
            return null;
        }

        if (@file_put_contents($abs.'/'.$nama, $biner) === false) {
            return null;
        }

        $jalur = $folder.'/'.$nama;

        $document->attachments()->create([
            'section_key' => $sectionKey,
            'path' => $jalur,
            'original_name' => 'tempelan.'.$ext,
            'mime' => $ext === 'png' ? 'image/png' : 'image/jpeg',
            'size' => strlen($biner),
        ]);

        return $jalur;
    }

    /**
     * Perkecil bila kelewat lebar, dan pastikan hasilnya PNG atau JPEG.
     *
     * Dua sebab, bukan satu: (a) GIF/WEBP tak lolos daftar-putih unggahan yang
     * sudah ada, jadi harus ditukar; (b) tangkapan layar 4K memakan penyimpanan
     * berlipat tanpa satu piksel pun terlihat — di PDF gambarnya dibatasi 220pt
     * tinggi dan selebar kolom AKTIVITAS.
     *
     * Tanpa GD, PNG/JPEG disimpan apa adanya — lebih baik gambar besar yang
     * TAMPIL daripada tak ada gambar sama sekali.
     *
     * @param  array{0:int,1:int,2:int}  $info  hasil getimagesizefromstring()
     * @return array{0: ?string, 1: string} [biner, ekstensi]
     */
    private function rapikan(string $biner, array $info): array
    {
        $png = $info[2] === IMAGETYPE_PNG;
        $jpeg = $info[2] === IMAGETYPE_JPEG;
        $perluTukar = ! $png && ! $jpeg;
        $perluKecil = $info[0] > self::LEBAR_MAKS;

        if (! $perluTukar && ! $perluKecil) {
            return [$biner, $png ? 'png' : 'jpg'];
        }

        if (! function_exists('imagecreatefromstring')) {
            return $perluTukar ? [null, ''] : [$biner, $png ? 'png' : 'jpg'];
        }

        $asal = @imagecreatefromstring($biner);
        if ($asal === false) {
            return [null, ''];
        }

        try {
            if ($perluKecil) {
                $lebar = self::LEBAR_MAKS;
                $tinggi = max(1, (int) round($info[1] * (self::LEBAR_MAKS / $info[0])));
                $kecil = imagecreatetruecolor($lebar, $tinggi);

                // Transparansi PNG dipertahankan; tanpa ini latar jadi hitam pekat.
                imagealphablending($kecil, false);
                imagesavealpha($kecil, true);
                imagecopyresampled($kecil, $asal, 0, 0, 0, 0, $lebar, $tinggi, $info[0], $info[1]);

                imagedestroy($asal);
                $asal = $kecil;
            }

            $buf = '';
            ob_start();
            // PNG dipertahankan PNG: tangkapan layar berteks rusak parah oleh JPEG.
            $ext = $jpeg ? 'jpg' : 'png';
            $ext === 'jpg' ? imagejpeg($asal, null, 88) : imagepng($asal, null, 6);
            $buf = (string) ob_get_clean();

            return $buf === '' ? [null, ''] : [$buf, $ext];
        } finally {
            if (is_resource($asal) || $asal instanceof \GdImage) {
                imagedestroy($asal);
            }
        }
    }
}
