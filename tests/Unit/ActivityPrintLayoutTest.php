<?php

namespace Tests\Unit;

use App\Services\ActivityPrintLayout;
use PHPUnit\Framework\TestCase;

/**
 * Logika paginasi tabel AKTIVITAS (SOP/SP/IK) — murni, tanpa render.
 *
 * Mengunci perbaikan bug: saat satu aktivitas TERPOTONG antar halaman, (a) border
 * tabel harus TERTUTUP di batas halaman (baris terakhir tiap halaman TIDAK boleh
 * memakai kelas yang membuang border-bottom) dan (b) sel PIC tak boleh rowspan
 * melintasi halaman — harus DIULANG dgn rowspan baru di halaman lanjutan.
 */
class ActivityPrintLayoutTest extends TestCase
{
    /** Grup 0: judul + 3 paragraf (4 baris). Grup 1: judul + 1 paragraf (2 baris). */
    private function sampleGroups(): array
    {
        return [
            ['sub_judul' => 'Persiapan', 'deskripsi' => "P1\nP2\nP3", 'pic' => 'ICT'],
            ['sub_judul' => 'Penutup', 'deskripsi' => 'Q1', 'pic' => 'Plant'],
        ];
    }

    public function test_flatten_makes_head_row_plus_one_row_per_paragraph(): void
    {
        $rows = ActivityPrintLayout::flatten($this->sampleGroups(), '5.');

        $this->assertCount(6, $rows);
        $this->assertTrue($rows[0]['isHead']);
        $this->assertSame('5.1', $rows[0]['number']);
        $this->assertSame('Persiapan', $rows[0]['text']);
        $this->assertFalse($rows[1]['isHead']);
        $this->assertSame('P1', $rows[1]['text']);
        // PIC dibawa di SETIAP baris (dipakai saat diulang di halaman lanjutan).
        $this->assertSame('ICT', $rows[3]['pic']);
        $this->assertTrue($rows[4]['isHead']);
        $this->assertSame('5.2', $rows[4]['number']);
    }

    /**
     * Dokumen yang dibuat SEBELUM editor rich text ada tersimpan sebagai teks
     * polos bernewline. Satu saja perilaku ini bergeser, tata letak seluruh SOP
     * berjalan ikut berubah saat dokumennya disimpan ulang.
     */
    public function test_teks_polos_tetap_dipecah_per_baris_baru(): void
    {
        $rows = ActivityPrintLayout::flatten(
            [['sub_judul' => 'Lama', 'deskripsi' => "Satu\n\nDua\nTiga", 'pic' => 'ICT']],
            '5.'
        );

        // Baris kosong di tengah TIDAK menghasilkan baris tabel kosong.
        $this->assertCount(4, $rows);
        $this->assertSame(['Satu', 'Dua', 'Tiga'], array_column(array_slice($rows, 1), 'text'));
        $this->assertSame(['teks', 'teks', 'teks'], array_column(array_slice($rows, 1), 'kind'));
        // Tak ada HTML: sel dicetak ter-escape, seperti dulu.
        $this->assertNull($rows[1]['html']);
    }

    /**
     * Daftar bernomor dipecah PER BUTIR — satu <ol> panjang di dalam satu <tr>
     * (page-break-inside: avoid) tak bisa dipecah antar-halaman dan akan meluber
     * margin bawah. Nomornya ikut sebagai teks (.mk), bukan sebagai <ol>.
     */
    public function test_daftar_bernomor_jadi_satu_baris_per_butir(): void
    {
        $rows = ActivityPrintLayout::flatten(
            [['sub_judul' => 'Langkah', 'deskripsi' => '<ol><li>Satu</li><li>Dua</li><li>Tiga</li></ol>', 'pic' => 'ICT']],
            '5.'
        );

        $this->assertCount(4, $rows);
        $this->assertSame('<span class="mk">1.</span>Satu', $rows[1]['html']);
        $this->assertSame('<span class="mk">2.</span>Dua', $rows[2]['html']);
        $this->assertSame('<span class="mk">3.</span>Tiga', $rows[3]['html']);
        // PIC tetap dibawa tiap baris → rowspan bisa diulang di halaman lanjutan.
        $this->assertSame('ICT', $rows[3]['pic']);
    }

    public function test_bulir_memakai_tanda_bukan_nomor(): void
    {
        $rows = ActivityPrintLayout::flatten(
            [['sub_judul' => 'Catatan', 'deskripsi' => '<ul><li>Awal</li><li>Akhir</li></ul>']],
            '5.'
        );

        $this->assertSame('<span class="mk">&bull;</span>Awal', $rows[1]['html']);
        $this->assertSame('<span class="mk">&bull;</span>Akhir', $rows[2]['html']);
    }

    /**
     * Gambar SELALU berdiri sebagai baris tabel sendiri: gambar yang duduk
     * sebaris dgn teks membuat tinggi baris tak terduga, dan tinggi itulah yang
     * diukur mesin 2-fase untuk menentukan titik potong halaman.
     */
    public function test_gambar_jadi_baris_tersendiri_di_antara_teks(): void
    {
        $rows = ActivityPrintLayout::flatten([[
            'sub_judul' => 'Pemeriksaan',
            'deskripsi' => '<p>Sebelum<img src="/storage/lampiran/PLANT/SOP/a.png">Sesudah</p>',
            'pic' => 'Plant',
        ]], '5.');

        $this->assertSame(['teks', 'teks', 'gambar', 'teks'], array_column($rows, 'kind'));
        $this->assertSame('Sebelum', $rows[1]['html']);
        $this->assertSame('lampiran/PLANT/SOP/a.png', $rows[2]['path']);
        $this->assertSame('Sesudah', $rows[3]['html']);
    }

    /**
     * Butir daftar yang isinya HANYA foto tetap bernomor.
     *
     * Penyusun menempel tangkapan layar di butir ke-2, dan "2." lenyap dari PDF:
     * penanda butir cuma ikut kalau ada teks yang menemaninya, sedangkan butir
     * seperti itu tak punya teks sama sekali. Nomor butir dicetak sebagai TEKS
     * (lihat .mk), jadi yang hilang benar-benar hilang — tak ada <ol> yang bisa
     * menomori ulang sendiri.
     */
    public function test_butir_daftar_berisi_gambar_tetap_bernomor(): void
    {
        $rows = ActivityPrintLayout::flatten([[
            'sub_judul' => 'Langkah',
            'pic' => 'ICT',
            'deskripsi' => '<ol><li>satu</li>'
                .'<li><img src="/storage/lampiran/ICTMD/SOP/a.png"></li>'
                .'<li>tiga</li></ol>',
        ]], '5.');

        $this->assertSame(['teks', 'teks', 'gambar', 'teks'], array_column($rows, 'kind'));
        $this->assertSame('<span class="mk">1.</span>satu', $rows[1]['html']);
        $this->assertSame('<span class="mk">2.</span>', $rows[2]['html'], 'baris gambar harus membawa nomornya');
        $this->assertSame('lampiran/ICTMD/SOP/a.png', $rows[2]['path']);
        // Butir sesudahnya TIDAK boleh memakai ulang nomor yang tadi menumpang.
        $this->assertSame('<span class="mk">3.</span>tiga', $rows[3]['html']);
    }

    /** Bulir pun begitu — foto sebagai isi butir tetap berbulir. */
    public function test_butir_bulir_berisi_gambar_tetap_berbulir(): void
    {
        $rows = ActivityPrintLayout::flatten([[
            'sub_judul' => 'Catatan',
            'deskripsi' => '<ul><li><img src="/storage/lampiran/SHE/SOP/b.png"></li></ul>',
        ]], '5.');

        $this->assertSame('<span class="mk">&bull;</span>', $rows[1]['html']);
    }

    /** Gambar di luar daftar tetap tanpa penanda — tak ada nomor yang mengada. */
    public function test_gambar_di_luar_daftar_tak_diberi_penanda(): void
    {
        $rows = ActivityPrintLayout::flatten([[
            'sub_judul' => 'Bebas',
            'deskripsi' => '<p><img src="/storage/lampiran/ICTMD/SOP/c.png"></p>',
        ]], '5.');

        $this->assertSame('gambar', $rows[1]['kind']);
        $this->assertSame('', $rows[1]['html']);
    }

    /** Inline yang tak tersanitasi tak boleh sampai ke jalur cetak. */
    public function test_format_inline_dipertahankan_tapi_disaring(): void
    {
        $rows = ActivityPrintLayout::flatten(
            [['sub_judul' => 'Peringatan', 'deskripsi' => '<p><b>APD</b> <span style="color:red">wajib</span></p>']],
            '5.'
        );

        $this->assertSame('<strong>APD</strong> wajib', $rows[1]['html']);
    }

    /** Deskripsi kosong → grup tetap punya baris judulnya, tanpa baris hantu. */
    public function test_editor_kosong_tak_menyisakan_baris(): void
    {
        $rows = ActivityPrintLayout::flatten(
            [['sub_judul' => 'Kosong', 'deskripsi' => '<p><br></p>']],
            '5.'
        );

        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['isHead']);
    }
    public function test_single_page_pic_rowspan_covers_whole_group(): void
    {
        $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'));

        $this->assertTrue($rows[0]['showPic']);
        $this->assertSame(4, $rows[0]['picRowspan'], 'PIC grup-1 menutupi 4 baris');
        $this->assertFalse($rows[1]['showPic']);
        $this->assertTrue($rows[4]['showPic']);
        $this->assertSame(2, $rows[4]['picRowspan'], 'PIC grup-2 menutupi 2 baris');

        // Kelas border satu segmen penuh: atas → tengah → bawah.
        $this->assertSame('mrg-top', $rows[0]['mrg']);
        $this->assertSame('mrg-mid', $rows[1]['mrg']);
        $this->assertSame('mrg-bot', $rows[3]['mrg']);
    }

    /** Grup terpotong halaman: border tertutup di kedua sisi & PIC diulang. */
    public function test_page_break_mid_group_closes_borders_and_repeats_pic(): void
    {
        // Paksa halaman baru di baris 2 (tengah grup-1: baris 0-1 hal.1, baris 2-3 hal.2).
        $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'), [2]);

        $this->assertTrue($rows[2]['pageBreakBefore']);
        $this->assertSame(1, $rows[1]['pageNo']);
        $this->assertSame(2, $rows[2]['pageNo']);

        // (a) BORDER TERTUTUP: baris terakhir di hal.1 pakai 'mrg-bot' (border-bottom
        //     tetap ada) dan baris pertama hal.2 pakai 'mrg-top' (border-top tetap ada).
        $this->assertSame('mrg-bot', $rows[1]['mrg'], 'tepi bawah halaman 1 harus tertutup');
        $this->assertSame('mrg-top', $rows[2]['mrg'], 'tepi atas halaman 2 harus tertutup');

        // (b) PIC diulang di halaman lanjutan dgn rowspan BARU (tak lintas halaman).
        $this->assertTrue($rows[0]['showPic']);
        $this->assertSame(2, $rows[0]['picRowspan'], 'PIC hal.1 hanya span 2 baris');
        $this->assertTrue($rows[2]['showPic'], 'PIC harus muncul lagi di halaman lanjutan');
        $this->assertSame(2, $rows[2]['picRowspan'], 'PIC hal.2 span 2 baris');
        $this->assertSame('ICT', $rows[2]['pic']);
    }

    /** Invarian umum: tak ada rowspan PIC yang melintasi batas halaman. */
    public function test_no_pic_rowspan_ever_crosses_a_page_boundary(): void
    {
        foreach ([[1], [2], [3], [4], [1, 3], [2, 5]] as $starts) {
            $rows = ActivityPrintLayout::plan(ActivityPrintLayout::flatten($this->sampleGroups(), '5.'), $starts);

            foreach ($rows as $i => $r) {
                if (! $r['showPic']) {
                    continue;
                }
                for ($j = $i; $j < $i + $r['picRowspan']; $j++) {
                    $this->assertSame(
                        $r['pageNo'], $rows[$j]['pageNo'],
                        'rowspan PIC tidak boleh melintasi halaman (starts: '.implode(',', $starts).')'
                    );
                }
            }
        }
    }

    /** Segmen satu baris = border penuh (tak ada kelas yang membuang border). */
    public function test_single_row_segment_keeps_full_borders(): void
    {
        $rows = ActivityPrintLayout::plan(
            ActivityPrintLayout::flatten([['sub_judul' => 'Tunggal', 'deskripsi' => '', 'pic' => 'ICT']], '1.')
        );

        $this->assertCount(1, $rows);
        $this->assertSame('', $rows[0]['mrg'], 'baris tunggal memakai border penuh');
        $this->assertSame(1, $rows[0]['picRowspan']);
    }

    /** Tiga grup seragam: judul + 3 paragraf → indeks 0-3, 4-7, 8-11. */
    private function tigaGrup(): array
    {
        return ActivityPrintLayout::flatten([
            ['sub_judul' => 'Persiapan', 'deskripsi' => "P1\nP2\nP3", 'pic' => 'ICT'],
            ['sub_judul' => 'Pelaksanaan', 'deskripsi' => "Q1\nQ2\nQ3", 'pic' => 'ICT'],
            ['sub_judul' => 'Penutup', 'deskripsi' => "R1\nR2\nR3", 'pic' => 'Plant'],
        ], '6.');
    }

    /**
     * Judul sub-bab yatim (halaman dimulai tepat SESUDAH judul) → awal halaman
     * mundur satu, judul ikut turun bersama baris pertamanya.
     */
    public function test_hindari_yatim_menggeser_awal_halaman_mundur_satu(): void
    {
        $rows = $this->tigaGrup();

        $this->assertSame([4], ActivityPrintLayout::hindariYatim($rows, [5]));
        $this->assertSame([4, 8], ActivityPrintLayout::hindariYatim($rows, [4, 9]));
    }

    /** Baris sebelum awal halaman bukan judul → tak ada yang digeser. */
    public function test_hindari_yatim_membiarkan_potongan_yang_sudah_benar(): void
    {
        $rows = $this->tigaGrup();

        $this->assertSame([4, 8], ActivityPrintLayout::hindariYatim($rows, [4, 8]));
        $this->assertSame([6], ActivityPrintLayout::hindariYatim($rows, [6]));
    }

    /**
     * Geseran DIBATALKAN bila titik barunya menyentuh awal halaman sebelumnya —
     * kalau tidak, halaman itu jadi KOSONG (judul + 1 baris tak muat sama sekali).
     */
    public function test_hindari_yatim_tak_pernah_mengosongkan_halaman(): void
    {
        $rows = $this->tigaGrup();

        $this->assertSame([4, 5], ActivityPrintLayout::hindariYatim($rows, [4, 5]));
        // Halaman pertama: menggeser ke 0 akan mengosongkannya.
        $this->assertSame([1], ActivityPrintLayout::hindariYatim($rows, [1]));
    }

    /**
     * Judul TANPA baris deskripsi bukan yatim: baris sesudahnya sudah milik grup
     * LAIN, jadi menggesernya cuma memindahkan judul tanpa menemani apa pun.
     */
    public function test_hindari_yatim_mengabaikan_judul_tanpa_deskripsi(): void
    {
        $rows = ActivityPrintLayout::flatten([
            ['sub_judul' => 'A', 'deskripsi' => "P1\nP2", 'pic' => 'ICT'],
            ['sub_judul' => 'B', 'deskripsi' => '', 'pic' => 'ICT'],
            ['sub_judul' => 'C', 'deskripsi' => "R1\nR2", 'pic' => 'ICT'],
        ], '6.');

        $this->assertTrue($rows[3]['isHead']);
        $this->assertSame([4], ActivityPrintLayout::hindariYatim($rows, [4]));
    }
}
