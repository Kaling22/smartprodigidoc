<?php

namespace Tests\Unit;

use App\Services\RichText\PembersihHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pembersih HTML Deskripsi Aktivitas (PLAN-RICHTEXT §4.4, Fase A1).
 *
 * Yang dijaga di sini ada tiga, dan ketiganya punya akibat nyata:
 *
 *   1. KEAMANAN — apa pun yang datang dari peramban bisa dikarang. Tag & atribut
 *      di luar daftar putih tak boleh pernah tersimpan.
 *   2. DIALEK QUILL tak boleh bocor ke basis data — bulir Quill sesungguhnya
 *      `<ol>`, dan bila tersimpan begitu, mengganti editor kelak berarti
 *      membongkar jalur cetak.
 *   3. DOKUMEN LAMA — isinya teks polos bernewline. Satu saja disentuh, seluruh
 *      SOP berjalan berubah tata letaknya saat disimpan ulang.
 *
 * Murni, tanpa basis data & tanpa Laravel — memakai PHPUnit\TestCase langsung.
 */
class PembersihHtmlTest extends TestCase
{
    /** @return list<array{0: string, 1: string, 2: string}> */
    public static function kasusKeamanan(): array
    {
        return [
            'script dibuang berikut isinya' => [
                '<p>Aman</p><script>alert(1)</script>',
                '<p>Aman</p>',
                'isi script tak boleh ikut tersimpan sebagai kalimat',
            ],
            'atribut peristiwa tak pernah tertulis' => [
                '<p onclick="curi()">Halo</p>',
                '<p>Halo</p>',
                'onclick harus lenyap',
            ],
            'atribut gaya dibuang' => [
                '<p style="color:red">Merah</p>',
                '<p>Merah</p>',
                'style harus lenyap',
            ],
            'tag di luar daftar putih dibuang, isinya tetap' => [
                '<div><span>Isi</span> tetap</div>',
                'Isi tetap',
                'isi tak boleh ikut hilang bersama pembungkusnya',
            ],
            'b dan i dinormalkan' => [
                '<p><b>tebal</b> dan <i>miring</i></p>',
                '<p><strong>tebal</strong> dan <em>miring</em></p>',
                'yang tersimpan hanya satu ejaan',
            ],
            'underline dipertahankan' => [
                '<p><u>digarisbawahi</u></p>',
                '<p><u>digarisbawahi</u></p>',
                'garis bawah termasuk kebutuhan penyusun',
            ],
            'teks tetap ter-escape' => [
                '<p>5 &lt; 7 &amp; aman</p>',
                '<p>5 &lt; 7 &amp; aman</p>',
                'entitas tak boleh berubah jadi tag',
            ],
        ];
    }

    #[DataProvider('kasusKeamanan')]
    public function test_daftar_putih_menyaring_yang_berbahaya(string $masuk, string $harap, string $sebab): void
    {
        $this->assertSame($harap, PembersihHtml::bersihkan($masuk), $sebab);
    }

    /**
     * Tag yang TIDAK ADA di daftar putih maupun di daftar nama mana pun.
     *
     * KENAPA ADA: gerbang sanitasi dulu bertanya "apakah ada tag yang saya
     * kenal?" lewat daftar nama tag. Nama yang tak ada di daftar menjawab
     * "bukan HTML", dan bersihkan() memulangkan masukan APA ADANYA — tak
     * disaring sama sekali. Keempat payload pertama di bawah ini benar-benar
     * lolos utuh, lalu dicetak sebagai HTML di halaman Tinjau: pembuat dokumen
     * berpangkat paling rendah bisa menjalankan skrip di sesi peninjau yang
     * berwenang menyetujuinya.
     *
     * Yang dikunci di sini bukan "keenam nama ini ditangani", melainkan
     * PRINSIPNYA: keamanan tak boleh bergantung pada daftar nama tag, karena
     * daftar selalu ketinggalan satu nama — dan yang ketinggalan itulah yang
     * dipakai penyerang.
     *
     * @return list<array{0: string}>
     */
    public static function kasusTagAsing(): array
    {
        return [
            'svg dgn onload' => ['<svg onload="alert(1)">x</svg>'],
            'iframe berskema javascript' => ['<iframe src="javascript:alert(1)"></iframe>'],
            'video dgn onerror' => ['<video onerror="alert(1)"><source></video>'],
            'details dgn ontoggle' => ['<details ontoggle="alert(1)" open>teks</details>'],
            'body dgn onload' => ['<body onload=alert(1)>teks</body>'],
            'input ber-autofocus' => ['<form><input autofocus onfocus=alert(1)></form>'],
            'math dgn atribut peristiwa' => ['<math><mtext onclick="alert(1)">x</mtext></math>'],
            'marquee dgn onstart' => ['<marquee onstart="alert(1)">teks</marquee>'],
            'komentar bersyarat' => ['<!--[if IE]><script>alert(1)</script><![endif]-->'],
        ];
    }

    #[DataProvider('kasusTagAsing')]
    public function test_tag_di_luar_daftar_nama_tetap_disaring(string $masuk): void
    {
        $keluar = PembersihHtml::bersihkan($masuk);

        $this->assertStringNotContainsString('<', $keluar, 'tak satu pun tag boleh lolos: '.$keluar);
        foreach (['alert', 'onload', 'onerror', 'ontoggle', 'onfocus', 'onclick', 'onstart', 'javascript:'] as $jejak) {
            $this->assertStringNotContainsString($jejak, $keluar, "\"{$jejak}\" masih tersisa: ".$keluar);
        }
    }

    /**
     * Gerbangnya menilai BENTUK, bukan nama: apa pun yang bisa terurai sebagai
     * markup harus masuk pengurai, dan hanya `<` yang tak mungkin membuka tag
     * yang boleh lewat.
     */
    public function test_gerbang_menilai_bentuk_bukan_nama_tag(): void
    {
        // Tak mungkin markup → teks polos, lewat apa adanya.
        $this->assertFalse(PembersihHtml::adalahHtml('Suhu a < b saat uji'));
        $this->assertFalse(PembersihHtml::adalahHtml('Toleransi <= 5 mm'));
        $this->assertFalse(PembersihHtml::adalahHtml('Langkah pertama'));

        // Mungkin markup → WAJIB disaring, tak peduli namanya dikenal atau tidak.
        foreach (['<svg>', '<xyz>', '</p>', '<!doctype html>', '<?php', '<A HREF=x>'] as $mungkin) {
            $this->assertTrue(PembersihHtml::adalahHtml($mungkin), "{$mungkin} harus dianggap mungkin-markup");
        }
    }
    /** @return list<array{0: string, 1: string}> */
    public static function kasusGambar(): array
    {
        return [
            'lampiran yang sah dipertahankan' => [
                '<p><img src="/storage/lampiran/PLANT/SOP/img_68af.png"></p>',
                '<p><img src="/storage/lampiran/PLANT/SOP/img_68af.png"></p>',
            ],
            'dept berstrip tetap sah' => [
                '<p><img src="/storage/lampiran/FAW-SCM/IK/img_1.jpg"></p>',
                '<p><img src="/storage/lampiran/FAW-SCM/IK/img_1.jpg"></p>',
            ],
            'gambar dari luar dibuang' => [
                '<p><img src="https://jauh.example.com/a.png">Teks</p>',
                '<p>Teks</p>',
            ],
            'skema javascript dibuang' => [
                '<p><img src="javascript:alert(1)">Teks</p>',
                '<p>Teks</p>',
            ],
            'data uri dibuang' => [
                '<p><img src="data:image/png;base64,AAAA">Teks</p>',
                '<p>Teks</p>',
            ],
            'di luar folder lampiran dibuang' => [
                '<p><img src="/storage/avatars/PLANT/SOP/a.png">Teks</p>',
                '<p>Teks</p>',
            ],
            'atribut lain pada img tak ikut' => [
                '<p><img src="/storage/lampiran/SHE/JSA/a.png" onerror="curi()" width="900"></p>',
                '<p><img src="/storage/lampiran/SHE/JSA/a.png"></p>',
            ],
        ];
    }

    #[DataProvider('kasusGambar')]
    public function test_gambar_hanya_dari_folder_lampiran(string $masuk, string $harap): void
    {
        $this->assertSame($harap, PembersihHtml::bersihkan($masuk));
    }

    /**
     * Bulir Quill sesungguhnya `<ol data-list="bullet">`. Bila tersimpan begitu,
     * jalur cetak akan menomori bulir — dan basis data ikut terkunci pada satu
     * editor tertentu.
     */
    public function test_bulir_quill_dinormalkan_jadi_ul(): void
    {
        $this->assertSame(
            '<ul><li>satu</li><li>dua</li></ul>',
            PembersihHtml::bersihkan('<ol><li data-list="bullet">satu</li><li data-list="bullet">dua</li></ol>')
        );
    }

    public function test_daftar_bernomor_tetap_ol(): void
    {
        $this->assertSame(
            '<ol><li>satu</li><li>dua</li></ol>',
            PembersihHtml::bersihkan('<ol><li data-list="ordered">satu</li><li data-list="ordered">dua</li></ol>')
        );
    }

    /**
     * "Lanjutkan Penomoran" (tombol toolbar `ql-mulai`, RichText.tsx):
     * `data-mulai` pada butir PERTAMA sebuah `<ol>` bertahan tersimpan —
     * dibaca `PembersihHtml::pecahBlok()` untuk memulai nomor cetak dari sana,
     * bukan dari 1.
     */
    public function test_data_mulai_pada_ol_bertahan(): void
    {
        $this->assertSame(
            '<ol><li data-mulai="3">ketiga</li><li>keempat</li></ol>',
            PembersihHtml::bersihkan(
                '<ol><li data-list="ordered" data-mulai="3">ketiga</li>'
                .'<li data-list="ordered">keempat</li></ol>'
            )
        );
    }

    /** @return list<array{0: string}> */
    public static function kasusMulaiTakSah(): array
    {
        return [
            'nol' => ['0'],
            'negatif' => ['-1'],
            'bukan angka' => ['abc'],
            'di atas batas' => ['1000'],
            'desimal' => ['3.5'],
            'berspasi' => [' 3'],
        ];
    }

    #[DataProvider('kasusMulaiTakSah')]
    public function test_data_mulai_tak_sah_dibuang(string $mulai): void
    {
        $this->assertSame(
            '<ol><li>x</li></ol>',
            PembersihHtml::bersihkan('<ol><li data-list="ordered" data-mulai="'.$mulai.'">x</li></ol>')
        );
    }

    /** Bulir (`ul`) tak bernomor — `data-mulai` di sana tak berarti apa-apa, dibuang juga. */
    public function test_data_mulai_pada_bulir_dibuang(): void
    {
        $this->assertSame(
            '<ul><li>x</li></ul>',
            PembersihHtml::bersihkan('<ol><li data-list="bullet" data-mulai="3">x</li></ol>')
        );
    }

    /** `PembersihHtml::blok()` — nomor cetak lanjut dari `data-mulai`, bukan reset ke 1. */
    public function test_blok_menomori_lanjut_dari_data_mulai(): void
    {
        $blok = PembersihHtml::blok(
            '<p>Baris pertama</p><ol><li>pertama</li><li>kedua</li></ol>'
            .'<p>Baris kedua</p><ol><li data-mulai="3">ketiga</li></ol>'
        );

        $bertanda = array_values(array_filter($blok, fn ($b) => str_contains($b['html'] ?? '', 'class="mk"')));

        $this->assertSame('<span class="mk">1.</span>pertama', $bertanda[0]['html']);
        $this->assertSame('<span class="mk">2.</span>kedua', $bertanda[1]['html']);
        $this->assertSame('<span class="mk">3.</span>ketiga', $bertanda[2]['html']);
    }

    /**
     * Gambar BERDIRI SENDIRI (bukan di dalam `<p>`/`<li>`) — bentuk editor
     * bergambar BLOK asli (mis. `DecoratorNode` Lexical), beda dari Quill lama
     * yang gambarnya selalu terbungkus `<p>`. Tanpa cabang `img` di
     * `pecahBlok()`, gambar begini tersimpan aman tapi diam-diam tak pernah
     * tercetak — `pecahInline()` cuma memeriksa ANAK sebuah simpul, dan `<img>`
     * tak punya anak sama sekali.
     */
    public function test_blok_mengenali_gambar_berdiri_sendiri(): void
    {
        $blok = PembersihHtml::blok('<p>sebelum</p><img src="/storage/lampiran/ICT/SOP/foto.png"><p>sesudah</p>');

        $this->assertSame(
            [
                ['kind' => 'teks', 'html' => 'sebelum'],
                ['kind' => 'gambar', 'path' => 'lampiran/ICT/SOP/foto.png', 'html' => ''],
                ['kind' => 'teks', 'html' => 'sesudah'],
            ],
            $blok
        );
    }

    /**
     * Quill MENGGABUNG daftar bernomor dan berbulir yang berdampingan ke dalam
     * satu `<ol>`. Tanpa dipecah, bulirnya akan tercetak bernomor.
     */
    public function test_daftar_campuran_dipecah_menurut_jenisnya(): void
    {
        $this->assertSame(
            '<ol><li>langkah</li></ol><ul><li>catatan</li></ul><ol><li>langkah lagi</li></ol>',
            PembersihHtml::bersihkan(
                '<ol><li data-list="ordered">langkah</li>'
                .'<li data-list="bullet">catatan</li>'
                .'<li data-list="ordered">langkah lagi</li></ol>'
            )
        );
    }

    /** @return list<array{0: ?string}> */
    public static function kasusKosong(): array
    {
        return [
            'editor yang dikosongkan' => ['<p><br></p>'],
            'paragraf kosong' => ['<p></p>'],
            'beberapa paragraf kosong' => ['<p><br></p><p><br></p>'],
            'hanya spasi keras' => ['<p>&nbsp;</p>'],
            'null' => [null],
            'string kosong' => [''],
            'spasi belaka' => ['   '],
        ];
    }

    /**
     * Kalau `<p><br></p>` tersimpan apa adanya, `filled()` menganggap bab
     * kosong sebagai terisi dan penyusun tak pernah diperingatkan saat Kirim.
     */
    #[DataProvider('kasusKosong')]
    public function test_editor_kosong_menghasilkan_string_kosong(?string $masuk): void
    {
        $this->assertSame('', PembersihHtml::bersihkan($masuk));
    }

    /** Kosong bagi mata, tapi ADA gambarnya — itu isi, bukan kekosongan. */
    public function test_gambar_tanpa_teks_bukan_kosong(): void
    {
        $this->assertSame(
            '<p><img src="/storage/lampiran/PLANT/SOP/a.png"></p>',
            PembersihHtml::bersihkan('<p><img src="/storage/lampiran/PLANT/SOP/a.png"></p>')
        );
    }

    /**
     * Dokumen yang dibuat sebelum editor ada tersimpan sebagai teks polos
     * bernewline, dan jalur cetak masih memecahnya per baris. Menyentuhnya di
     * sini akan mengubah tata letak seluruh SOP berjalan.
     */
    public function test_teks_polos_dokumen_lama_tak_disentuh(): void
    {
        $lama = "Langkah pertama\nLangkah kedua\nLangkah ketiga";

        $this->assertSame($lama, PembersihHtml::bersihkan($lama));
    }

    /** "a < b" tak mengandung tag — ia tak boleh dikira HTML lalu dipotong. */
    public function test_teks_polos_dengan_tanda_kurang_dari_utuh(): void
    {
        $this->assertSame('Suhu a < b saat uji', PembersihHtml::bersihkan('Suhu a < b saat uji'));
    }

    /** Huruf non-ASCII tak boleh berubah jadi cacing mojibake. */
    public function test_aksen_dan_simbol_utuh(): void
    {
        $this->assertSame(
            '<p>Ukuran ±5° — “aman”</p>',
            PembersihHtml::bersihkan('<p>Ukuran ±5° — “aman”</p>')
        );
    }
}
