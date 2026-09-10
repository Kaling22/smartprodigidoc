<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentAuthor;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\Print\PdfRenderer;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * HALAMAN COVER SOP/IK/SP — geometrinya dikunci di sini.
 *
 * Seluruh angka yang diuji adalah hasil PENGUKURAN docs/Cover_Depan.docx
 * (word/document.xml + word/styles.xml), bukan kesepakatan sembarang. Diperiksa
 * dari PDF yang BENAR-BENAR dirender, sama seperti PrintLayoutTest.
 *
 * Satu hal yang TIDAK bisa diuji dari teks: apakah kop tampak atau tidak.
 * Persegi putih yang menutupinya tidak menghapus teks kop dari stream — teks
 * itu tetap ada, hanya tertimbun. Yang menentukan adalah URUTAN GAMBAR, dan
 * itulah yang diuji test_kop_tertutup_di_halaman_cover().
 */
class CoverPageTest extends TestCase
{
    use DatabaseTransactions;

    /** Ukuran kertas A4 & geometri cover, dalam pt (1cm = 28.3465pt). */
    private const A4_LEBAR = 595.28;

    private const A4_TINGGI = 841.89;

    /**
     * Judul bawaan sengaja PENDEK (satu baris pada kolom 303.6pt).
     *
     * Panjang judul menentukan tepi ATAS kotak pengesahan — makin panjang,
     * makin turun — jadi test yang menyebut angka mutlak harus tahu pasti
     * berapa baris judulnya. Kasus dua baris diuji tersendiri.
     */
    private function sop(string $judul = 'SOP Handover'): Document
    {
        $gl = $this->aktorGl();

        return app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'SOP')->firstOrFail(), $gl->department, $judul
        );
    }

    /** Stream operator halaman 1 (cover), sudah didekompres. */
    private function coverStream(Document $document): string
    {
        $raw = app(PdfRenderer::class)->render($document)->output();

        foreach ($this->pdfStreams($raw) as $c) {
            if (str_contains($c, 'BT')) {
                return $c;   // stream berteks PERTAMA = halaman 1 = cover
            }
        }

        $this->fail('stream halaman cover tak terbaca');
    }

    /**
     * Teks + posisinya: [x, y-dari-ATAS, ukuran font, isi].
     *
     * y dibalik ke arah baca manusia (dari tepi atas kertas) supaya angka di
     * test bisa dibandingkan langsung dengan ukuran cm/pt pada docx.
     *
     * @return array<int, array{0:float, 1:float, 2:float, 3:string}>
     */
    private function positionedText(string $stream): array
    {
        preg_match_all('/([\d.]+)\s+([\d.]+)\s+(?:Td|TD)\s*(.*?)(?:TJ|Tj)/s', $stream, $m, PREG_SET_ORDER);

        $size = 0.0;
        $rows = [];
        foreach ($m as $seg) {
            if (preg_match('#/[A-Za-z0-9_+.-]+\s+([\d.]+)\s+Tf#', $seg[3], $tf)) {
                $size = (float) $tf[1];
            }
            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $seg[3], $lit);
            $t = '';
            foreach ($lit[0] as $l) {
                $t .= str_replace("\x00", '', preg_replace('/\\\\([()\\\\])/', '$1', substr($l, 1, -1)));
            }
            if (trim($t) !== '') {
                $rows[] = [(float) $seg[1], self::A4_TINGGI - (float) $seg[2], $size, $t];
            }
        }

        return $rows;
    }

    /** Persegi: [x, y-dari-ATAS, lebar, tinggi] dari operator `re`. */
    private function rects(string $stream): array
    {
        preg_match_all('/([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+([\d.-]+)\s+re/', $stream, $m, PREG_SET_ORDER);

        return array_map(fn ($r) => [
            (float) $r[1], self::A4_TINGGI - (float) $r[2] - (float) $r[4], (float) $r[3], (float) $r[4],
        ], $m);
    }

    /**
     * Garis mendatar: [x-kiri, y-dari-ATAS, panjang] dari path `m … l`.
     *
     * `border-bottom` TIDAK digambar DomPDF sebagai `re` melainkan sebagai
     * path garis — terukur, itulah sebabnya garis nama tak pernah muncul di
     * daftar persegi.
     */
    private function hLines(string $stream): array
    {
        preg_match_all('/([\d.-]+)\s+([\d.-]+)\s+m\s+([\d.-]+)\s+([\d.-]+)\s+l/', $stream, $m, PREG_SET_ORDER);

        $out = [];
        foreach ($m as $g) {
            if (abs((float) $g[2] - (float) $g[4]) < 0.01) {   // mendatar saja
                $out[] = [(float) $g[1], self::A4_TINGGI - (float) $g[2], abs((float) $g[3] - (float) $g[1])];
            }
        }

        return $out;
    }

    /** Gambar: [lebar, tinggi, x, y-dari-ATAS] dari matriks `cm` sebelum `Do`. */
    private function images(string $stream): array
    {
        preg_match_all(
            '/([\d.-]+) 0 0 ([\d.-]+) ([\d.-]+) ([\d.-]+) cm\s*\/[A-Za-z0-9_.]+\s+Do/', $stream, $m, PREG_SET_ORDER
        );

        return array_map(fn ($g) => [
            (float) $g[1], (float) $g[2], (float) $g[3], self::A4_TINGGI - (float) $g[4] - (float) $g[2],
        ], $m);
    }

    private function cari(array $rows, string $awalan): ?array
    {
        foreach ($rows as $r) {
            if (str_starts_with($r[3], $awalan)) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Blok judul: nama perusahaan 16pt, jenis dokumen 16pt, judul dokumen 22pt
     * — `w:sz` 32/32/44 pada docx, semuanya rata tengah.
     */
    public function test_ukuran_dan_perataan_blok_judul(): void
    {
        $rows = $this->positionedText($this->coverStream($this->sop('Penggunaan Radio')));

        $perusahaan = $this->cari($rows, 'PT PUTRA PERKASA ABADI');
        $jenis = $this->cari($rows, 'STANDARD OPERATING');
        $judul = $this->cari($rows, 'PENGGUNAAN RADIO');

        $this->assertNotNull($perusahaan, 'nama perusahaan tak ada di cover');
        $this->assertNotNull($jenis, 'jenis dokumen tak ada di cover');
        $this->assertNotNull($judul, 'judul dokumen tak ada di cover');

        $this->assertSame(16.0, $perusahaan[2], 'nama perusahaan harus 16pt');
        $this->assertSame(16.0, $jenis[2], 'jenis dokumen harus 16pt');
        $this->assertSame(22.0, $judul[2], 'judul dokumen harus 22pt');

        // Urutan vertikal: perusahaan → jenis → judul.
        $this->assertLessThan($jenis[1], $perusahaan[1], 'nama perusahaan harus di atas jenis');
        $this->assertLessThan($judul[1], $jenis[1], 'jenis harus di atas judul');
    }

    /**
     * Logo: 42.75pt → 161.25pt dari tepi ATAS kertas, mendatar di tengah.
     *
     * 118.5pt ini tinggi gambar yang BENAR-BENAR TERLIHAT, karena cover memakai
     * berkas logo yang paddingnya sudah dipotong (PdfRenderer::logoCoverPath()).
     * Sebelum dipotong, kotak 130pt hanya menampilkan ±82pt gambar.
     *
     * 42.75 = bingkai halaman 24pt + 1½ × enter (18.75pt) — permintaan pemilik
     * supaya logo tak mepet tepi atas.
     */
    public function test_logo_setinggi_118_5pt_dan_di_tengah(): void
    {
        $gambar = $this->images($this->coverStream($this->sop()));

        $logo = null;
        foreach ($gambar as $g) {
            if (abs($g[3] - 42.75) < 1.0) {
                $logo = $g;
            }
        }

        $this->assertNotNull($logo, 'logo cover tak ditemukan pada 42.75pt dari tepi atas');
        $this->assertEqualsWithDelta(42.75, $logo[3], 0.5, 'tepi atas logo harus 42.75pt (1½ × enter di bawah bingkai)');
        $this->assertEqualsWithDelta(118.5, $logo[1], 0.5, 'tinggi logo harus 118.5pt');
        $this->assertEqualsWithDelta((self::A4_LEBAR - $logo[0]) / 2, $logo[2], 0.5, 'logo harus di tengah');

        // Rasio isi PNG sesudah dipotong (393×519 → 0.757) — sama dgn potongan
        // logo di docx (164×217). Kalau pemotongnya rusak, rasionya jadi 1.0.
        $this->assertEqualsWithDelta(0.757, $logo[0] / $logo[1], 0.03,
            'logo cover harus dari berkas TERPOTONG, bukan PNG persegi berpadding');
    }

    /**
     * Bingkai halaman `w:pgBorders`: 3pt, 24pt dari tepi kertas, 4 sisi.
     * Kotak No. Dokumen: BORDER GANDA, lebar luar 247.5pt, di tengah.
     */
    public function test_bingkai_halaman_dan_kotak_nomor_berbingkai_ganda(): void
    {
        $persegi = $this->rects($this->coverStream($this->sop()));

        $bingkai = null;
        $kotakNomor = [];
        foreach ($persegi as $p) {
            if (abs($p[0] - 24.0) < 0.5 && abs($p[1] - 24.0) < 0.5) {
                $bingkai = $p;
            }
            // Kotak nomor: lebar 230–260pt, berada di paruh atas halaman.
            if ($p[2] > 225 && $p[2] < 265 && $p[1] > 240 && $p[1] < 440) {
                $kotakNomor[] = $p;
            }
        }

        $this->assertNotNull($bingkai, 'bingkai halaman tak ditemukan');
        $this->assertEqualsWithDelta(self::A4_LEBAR - 48, $bingkai[2], 0.5, 'lebar bingkai = kertas − 2×24pt');
        $this->assertEqualsWithDelta(self::A4_TINGGI - 48, $bingkai[3], 0.5, 'tinggi bingkai = kertas − 2×24pt');

        $this->assertCount(2, $kotakNomor, 'kotak No. Dokumen harus berbingkai GANDA (dua persegi bersarang)');
        usort($kotakNomor, fn ($a, $b) => $b[2] <=> $a[2]);
        [$luar, $dalam] = $kotakNomor;

        $this->assertEqualsWithDelta(247.5, $luar[2] + 1.0, 1.0, 'lebar luar kotak nomor harus 247.5pt');
        $this->assertEqualsWithDelta((self::A4_LEBAR - $luar[2]) / 2, $luar[0], 1.0, 'kotak nomor harus di tengah');
        $this->assertGreaterThan($dalam[2], $luar[2], 'persegi dalam harus lebih sempit dari persegi luar');
        $this->assertGreaterThan(3.0, $dalam[0] - $luar[0], 'celah antar bingkai ±5.4pt');
    }

    /**
     * Kotak pengesahan: lebar 423.35pt, di tengah, tepi atas 447pt — dan
     * SELURUH barisnya di halaman cover, tak ada yang terlempar ke halaman 2.
     */
    public function test_kotak_pengesahan_utuh_di_halaman_cover(): void
    {
        $doc = $this->sop();
        $stream = $this->coverStream($doc);
        $rows = $this->positionedText($stream);

        foreach (['Dibuat Oleh', 'Ditinjau Oleh', 'Disetujui Oleh'] as $label) {
            $this->assertNotNull($this->cari($rows, $label), "baris \"{$label}\" harus ada di halaman cover");
        }

        $kotak = null;
        foreach ($this->rects($stream) as $p) {
            if ($p[2] > 415 && $p[2] < 430) {
                $kotak = $p;
            }
        }

        $this->assertNotNull($kotak, 'bingkai kotak pengesahan tak ditemukan');
        $this->assertEqualsWithDelta(423.35, $kotak[2] - 1.0, 1.5, 'lebar kotak pengesahan harus 423.35pt');
        $this->assertEqualsWithDelta((self::A4_LEBAR - $kotak[2]) / 2, $kotak[0], 1.5, 'kotak pengesahan harus di tengah');
        $this->assertEqualsWithDelta(328.75, $kotak[1], 1.5, 'tepi atas kotak pengesahan (judul 1 baris) harus 328.75pt');

        // Tak boleh menembus dasar kotak konten (841.89 − margin bawah 57pt).
        $this->assertLessThanOrEqual(784.89, $kotak[1] + $kotak[3], 'kotak pengesahan melewati dasar kotak konten');
    }

    /**
     * Jabatan + DEPARTEMEN ikut tercetak (permintaan pemilik), dan pembuat
     * TAMBAHAN muncul sebagai baris "Dibuat Oleh" ekstra tepat di bawah
     * pembuat utama — tanpa membuat kotaknya terbelah ke halaman 2.
     */
    public function test_pembuat_tambahan_menambah_baris_tanpa_memecah_kotak(): void
    {
        $doc = $this->sop();
        $extra = User::where('jabatan', User::JABATAN_GROUP_LEADER)
            ->where('department_id', $doc->department_id)
            ->where('id', '!=', $doc->created_by)->first();

        if (! $extra) {
            $this->markTestSkipped('departemen uji tak punya GL kedua sebagai pembuat tambahan');
        }

        DocumentAuthor::create(['document_id' => $doc->id, 'user_id' => $extra->id, 'is_primary' => false]);

        $stream = $this->coverStream($doc->refresh());
        $rows = $this->positionedText($stream);

        $dibuat = array_filter($rows, fn ($r) => $r[3] === 'Dibuat Oleh');
        $this->assertCount(2, $dibuat, 'pembuat tambahan harus menambah SATU baris "Dibuat Oleh"');

        $this->assertNotNull($this->cari($rows, 'Disetujui Oleh'), 'baris terakhir tak boleh terlempar ke halaman 2');
        $this->assertNotNull($this->cari($rows, 'Group Leader ('), 'jabatan harus disertai departemen');

        $kotak = null;
        foreach ($this->rects($stream) as $p) {
            if ($p[2] > 415 && $p[2] < 430) {
                $kotak = $p;
            }
        }
        $this->assertLessThanOrEqual(784.89, $kotak[1] + $kotak[3], 'kotak melewati dasar kotak konten dgn 4 baris');
    }

    /**
     * Baris pengesahan mengikuti format PARAF di referensi, persis:
     *
     *     Dibuat Oleh          [cap APPROVE]
     *                     :  ‾‾‾‾‾NAMA‾‾‾‾‾   Tgl : 29/03/2026
     *                           Jabatan (DEPT)
     *
     * Label, ":", NAMA, dan tanggal SEBARIS; jabatan di baris bawahnya.
     * Susunan lama menumpuk nama/tgl/jabatan vertikal — itulah yang membuat
     * tinggi baris melampaui jaraknya dan kotaknya terbelah ke halaman 2.
     */
    public function test_baris_pengesahan_mengikuti_format_paraf(): void
    {
        $rows = $this->positionedText($this->coverStream($this->sop()));

        $label = $this->cari($rows, 'Dibuat Oleh');
        $titikDua = $this->cari($rows, ':');
        $nama = $this->cari($rows, strtoupper($this->aktorGl()->name));
        $tgl = $this->cari($rows, 'Tgl :');
        $jabatan = $this->cari($rows, 'Group Leader (');

        foreach (compact('label', 'titikDua', 'nama', 'tgl', 'jabatan') as $apa => $r) {
            $this->assertNotNull($r, "\"{$apa}\" tak ditemukan di baris pengesahan");
        }

        // SEBARIS dengan nama — inilah inti format paraf.
        $this->assertEqualsWithDelta($nama[1], $label[1], 1.5, 'label harus sebaris dengan NAMA');
        $this->assertEqualsWithDelta($nama[1], $titikDua[1], 1.5, '":" harus sebaris dengan NAMA');
        $this->assertEqualsWithDelta($nama[1], $tgl[1], 1.5, 'tanggal harus SEBARIS dengan NAMA, bukan di bawahnya');

        // Jabatan satu baris DI BAWAH nama (y bertambah ke bawah).
        $this->assertGreaterThan($nama[1] + 8, $jabatan[1], 'jabatan harus di baris bawah nama');
        $this->assertLessThan($nama[1] + 22, $jabatan[1], 'jabatan harus TEPAT satu baris di bawah nama');

        // Urutan mendatar: label → ":" → nama → tanggal, pada jarak terukur
        // referensi (relatif tepi kiri kotak 85.5pt): 41 · 158 · 167.
        $kiri = 85.5;
        $this->assertEqualsWithDelta(41.0, $label[0] - $kiri, 2.0, 'label harus di +41pt dari tepi kotak');
        $this->assertEqualsWithDelta(158.0, $titikDua[0] - $kiri, 3.0, '":" harus di +158pt');
        $this->assertGreaterThan($titikDua[0], $nama[0], 'nama di kanan ":"');
        $this->assertGreaterThan($nama[0], $tgl[0], 'tanggal di kanan nama');

        // Nama bergaris bawah selebar KOLOMNYA (garis tanda tangan), bukan
        // selebar teksnya — dan garisnya tepat di bawah baris nama.
        $garis = array_values(array_filter(
            $this->hLines($this->coverStream($this->sop())),
            fn ($g) => $g[2] > 100 && $g[2] < 180 && abs($g[1] - $nama[1]) < 8,
        ));

        $this->assertNotEmpty($garis, 'garis nama (border-bottom) tak ditemukan di bawah nama');
        $this->assertGreaterThan($nama[1], $garis[0][1], 'garis harus DI BAWAH teks nama');
    }

    /** Cap APPROVE menggantikan tanda tangan DI TEMPAT YANG SAMA: di atas garis nama. */
    public function test_cap_approve_berada_di_atas_garis_nama(): void
    {
        $doc = $this->sop('SOP Bercap');
        $doc->update(['status' => 'published', 'published_at' => now()]);

        $stream = $this->coverStream($doc->refresh());
        $nama = $this->cari($this->positionedText($stream), strtoupper($doc->creator->name));
        $this->assertNotNull($nama, 'nama pembuat tak ditemukan');

        // Cap = gambar di dalam kotak pengesahan (logo cover berakhir di 186pt,
        // jauh di atasnya). Ambil yang PALING ATAS supaya berpasangan dengan
        // nama pertama — tiap baris punya capnya sendiri.
        $cap = null;
        foreach ($this->images($stream) as $g) {
            if ($g[3] > 340 && ($cap === null || $g[3] < $cap[3])) {
                $cap = $g;
            }
        }

        $this->assertNotNull($cap, 'cap APPROVE tak digambar di kotak pengesahan');
        $this->assertLessThan($nama[1], $cap[3] + $cap[1], 'cap harus berada DI ATAS garis nama');
        $this->assertGreaterThan($nama[1] - 70, $cap[3], 'cap tak boleh melayang jauh dari garis namanya');
    }

    /**
     * Tepi BAWAH kotak pengesahan tetap; tepi ATAS-nya yang bergerak.
     *
     * Judul yang membungkus dua baris mendorong kotak nomor turun, dan kotak
     * pengesahan ikut turun agar jarak "2× enter" di antaranya tetap sama.
     * Yang TIDAK boleh ikut bergerak adalah tepi bawahnya — permintaan pemilik.
     */
    public function test_tepi_bawah_kotak_tetap_walau_judul_dua_baris(): void
    {
        $ukur = function (string $judul): array {
            $stream = $this->coverStream($this->sop($judul));
            $kotak = $nomor = null;
            foreach ($this->rects($stream) as $p) {
                if ($p[2] > 415 && $p[2] < 430) {
                    $kotak = $p;
                }
                if ($p[2] > 240 && $p[2] < 250) {
                    $nomor = $p;
                }
            }
            $this->assertNotNull($kotak, "kotak pengesahan tak ditemukan untuk judul \"{$judul}\"");
            $this->assertNotNull($nomor, "kotak nomor tak ditemukan untuk judul \"{$judul}\"");

            return ['atas' => $kotak[1], 'bawah' => $kotak[1] + $kotak[3], 'nomorBawah' => $nomor[1] + $nomor[3]];
        };

        $pendek = $ukur('SOP Handover');
        // Kolom judul kini 500pt (dulu 303.6): "Monitoring & Control Production
        // Unit" — judul dua baris yang lama — sekarang muat SATU baris, jadi
        // yang dipakai di sini judul yang benar-benar masih membungkus.
        $panjang = $ukur('Pengelolaan Perangkat Teknologi Informasi dan Komunikasi di Site Adaro');

        $this->assertEqualsWithDelta($pendek['bawah'], $panjang['bawah'], 1.0,
            'tepi BAWAH kotak pengesahan harus sama untuk judul 1 maupun 2 baris');
        $this->assertGreaterThan($pendek['atas'] + 20, $panjang['atas'],
            'judul 2 baris harus menurunkan tepi ATAS kotak');

        // Jarak "2× enter" antara kotak nomor dan kotak pengesahan tetap sama.
        foreach (['pendek' => $pendek, 'panjang' => $panjang] as $nama => $u) {
            $this->assertEqualsWithDelta(23.75, $u['atas'] - $u['nomorBawah'], 2.0,
                "jarak kotak nomor → kotak pengesahan (judul {$nama}) harus ±2× enter");
        }

        $this->assertLessThanOrEqual(784.89, $panjang['bawah'], 'kotak melewati dasar kotak konten');

        // Keluhan pemilik: judul panjang wajar pecah 3 baris dan mendorong kotak
        // nomor turun. Dengan kolom 500pt ia muat SATU baris — kotaknya berdiri
        // di tempat yang sama dengan judul pendek.
        $nyata = $ukur('Instalasi dan Penggunaan Monitor SS6');
        $this->assertEqualsWithDelta($pendek['atas'], $nyata['atas'], 1.0,
            'judul 36 karakter harus muat satu baris di kolom judul cover');
    }

    /**
     * Isi tiap baris RATA TENGAH atas-bawah dalam pitanya.
     *
     * Sebelumnya isi menempel ke bawah sehingga menyisakan rongga di atas tiap
     * baris — makin tinggi kotaknya, makin kentara.
     */
    public function test_isi_baris_rata_tengah_dalam_pitanya(): void
    {
        $stream = $this->coverStream($this->sop());

        $kotak = null;
        foreach ($this->rects($stream) as $p) {
            if ($p[2] > 415 && $p[2] < 430) {
                $kotak = $p;
            }
        }
        $this->assertNotNull($kotak, 'kotak pengesahan tak ditemukan');

        $label = array_values(array_filter(
            $this->positionedText($stream),
            fn ($r) => in_array($r[3], ['Dibuat Oleh', 'Ditinjau Oleh', 'Disetujui Oleh'], true),
        ));
        $this->assertGreaterThanOrEqual(3, count($label), 'baris pengesahan kurang dari 3');

        $pitch = $kotak[3] / count($label);

        foreach ($label as $i => $r) {
            $pitaAtas = $kotak[1] + $i * $pitch;
            $tengah = $pitaAtas + $pitch / 2;
            // Garis nama sebaris dgn label; ia harus dekat titik tengah pita,
            // bukan menempel ke dasarnya.
            $this->assertEqualsWithDelta($tengah, $r[1], $pitch * 0.22,
                'isi baris '.($i + 1).' tidak rata tengah dalam pitanya');
        }
    }

    /**
     * Kop TERTUTUP di halaman cover.
     *
     * Tak bisa diuji lewat "teksnya hilang" — persegi putih menimbun, tidak
     * menghapus, jadi teks kop TETAP ada di stream. Yang menentukan tampak atau
     * tidak adalah urutan gambar: persegi putih harus digambar SESUDAH teks
     * kop, dan logo cover SESUDAH persegi putih (kalau tidak, logo ikut
     * tertimbun).
     */
    public function test_kop_tertutup_di_halaman_cover(): void
    {
        $stream = $this->coverStream($this->sop());

        // Penanda dipilih yang BUKAN teks: sesudah dokumen memakai Helvetica
        // (font inti PDF), teks tak lagi ditulis UTF-16BE sehingga pola byte
        // lama tak cocok — dan "Tgl" kini juga muncul di cover sendiri. Logo
        // kop (108pt) & logo cover (130.39pt) adalah operator tunggal yang
        // tak mungkin salah kenali.
        $posKop = strpos($stream, '108.000 0 0 108.000');
        $posPutih = strpos($stream, '0.000 678.890 595.280 163.000 re');
        $posLogo = strrpos($stream, '89.700 0 0 118.500');

        $this->assertNotFalse($posKop, 'kop tak ditemukan di stream cover');
        $this->assertNotFalse($posPutih, 'persegi penutup kop tak digambar');
        $this->assertNotFalse($posLogo, 'logo cover tak digambar');

        $this->assertGreaterThan($posKop, $posPutih, 'penutup harus digambar SESUDAH kop, kalau tidak kop tetap tampak');
        $this->assertGreaterThan($posPutih, $posLogo, 'logo harus digambar SESUDAH penutup, kalau tidak logo ikut tertimbun');
    }

    /** JSA TIDAK memakai cover — blok TTD-nya menyatu di halaman 1 formulir. */
    public function test_jsa_tidak_memakai_cover(): void
    {
        $gl = $this->aktorGl();
        $jsa = app(DocumentService::class)->createDraft(
            $gl, DocumentType::where('code', 'JSA')->firstOrFail(), $gl->department, 'JSA Tanpa Cover'
        );

        $rows = $this->positionedText($this->coverStream($jsa));

        $this->assertNull($this->cari($rows, 'PT PUTRA PERKASA ABADI'), 'JSA tak boleh punya halaman cover');
        $this->assertNotNull($this->cari($rows, 'FORMULIR'), 'halaman 1 JSA tetap kop formulir');
    }
}
