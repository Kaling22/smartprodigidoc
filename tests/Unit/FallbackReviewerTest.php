<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\Ai\AbstractAiReviewer;
use App\Services\Ai\FallbackReviewer;
use Tests\TestCase;

/**
 * Rantai penyedia AI: cadangan dipakai HANYA saat yang utama gagal.
 *
 * Dua kesalahan yang tak terlihat dari layar dijaga di sini: (a) cadangan tak
 * pernah dipanggil sehingga kegagalan kredit tetap menghentikan audit, dan (b)
 * cadangan dipanggil SETIAP kali sehingga kuota penyedia kedua ikut terbakar
 * walau yang utama baik-baik saja. Keduanya sama-sama menghasilkan halaman yang
 * tampak normal.
 *
 * Tanpa jaringan: penyedia palsu memutus callProvider().
 */
class FallbackReviewerTest extends TestCase
{
    /** Penyedia palsu — $gagal true berarti panggilannya melempar (mis. 402). */
    private function penyedia(string $nama, bool $gagal): AbstractAiReviewer
    {
        return new class($nama, $gagal) extends AbstractAiReviewer
        {
            public int $dipanggil = 0;

            public function __construct(private readonly string $nama, private readonly bool $gagal)
            {
                parent::__construct('kunci-uji', 'model-uji');
            }

            protected function callProvider(string $prompt): string
            {
                $this->dipanggil++;

                if ($this->gagal) {
                    throw new \RuntimeException('402 — Insufficient credits');
                }

                return json_encode(['summary' => "jawaban {$this->nama}", 'findings' => []]);
            }

            // Prompt tak relevan di sini; potong agar tak menyentuh schema/DB.
            protected function buildPrompt(Document $d, array $c, string $f = self::FOKUS_SUBSTANSI): string
            {
                return 'prompt-uji';
            }

            // Yang diuji berkas ini adalah rantai review(), bukan uji koneksi.
            protected function callPing(): \Illuminate\Http\Client\Response
            {
                throw new \LogicException('Penyedia palsu ini tak pernah di-ping.');
            }
        };
    }

    public function test_cadangan_dipakai_saat_utama_gagal(): void
    {
        $utama = $this->penyedia('utama', gagal: true);
        $cadangan = $this->penyedia('cadangan', gagal: false);

        $hasil = (new FallbackReviewer([$utama, $cadangan]))->review(new Document, []);

        $this->assertSame('jawaban cadangan', $hasil['summary']);
        $this->assertArrayNotHasKey('gagal', $hasil);
        $this->assertSame(1, $cadangan->dipanggil);
    }

    public function test_cadangan_tidak_disentuh_saat_utama_berhasil(): void
    {
        $utama = $this->penyedia('utama', gagal: false);
        $cadangan = $this->penyedia('cadangan', gagal: false);

        $hasil = (new FallbackReviewer([$utama, $cadangan]))->review(new Document, []);

        $this->assertSame('jawaban utama', $hasil['summary']);
        $this->assertSame(0, $cadangan->dipanggil, 'cadangan terpanggil padahal utama berhasil');
    }

    /**
     * Temuan JSA harus menempel ke KOTAK CATATAN barisnya (item_ref), bukan
     * menumpuk jadi satu daftar panjang. Dua jalan masuk diuji sekaligus karena
     * keduanya terjadi sungguhan pada model yang dipakai: field `item_ref` diisi,
     * atau penandanya cuma disebut di dalam kalimat `issue`.
     */
    public function test_item_ref_dibaca_dari_field_maupun_dari_kalimat(): void
    {
        $parse = new \ReflectionMethod(AbstractAiReviewer::class, 'parse');
        $parse->setAccessible(true);

        $hasil = $parse->invoke($this->penyedia('x', false), json_encode(['summary' => '', 'findings' => [
            ['section_key' => 'analisa', 'item_ref' => 'L0-B1-P2', 'severity' => 'major', 'issue' => 'a'],
            ['section_key' => 'analisa', 'severity' => 'major', 'issue' => 'Bahaya tersengat listrik (L1-B0) hanya diverifikasi.'],
            ['section_key' => 'analisa', 'severity' => 'minor', 'issue' => 'Tidak ada tahap penyelesaian.'],
            ['section_key' => 'analisa', 'item_ref' => 'BARIS KE-3', 'severity' => 'minor', 'issue' => 'b'],
        ]]));

        $this->assertSame('L0-B1-P2', $hasil['findings'][0]['item_ref'], 'item_ref eksplisit hilang');
        $this->assertSame('L1-B0', $hasil['findings'][1]['item_ref'], 'penanda di dalam kalimat tak ditarik');
        $this->assertSame('', $hasil['findings'][2]['item_ref'], 'temuan tingkat section tak boleh dipaksa menempel');
        $this->assertSame('', $hasil['findings'][3]['item_ref'], 'penanda ngawur harus dibuang');
    }

    /**
     * Jawaban KOSONG = kegagalan, bukan hasil.
     *
     * Model penalaran `:free` rutin memulangkan `content` kosong saat jatah
     * tokennya habis di tahap berpikir. Dulu itu dianggap sukses: summary jadi
     * "Respons AI tidak dapat diparse.", tanpa penanda `gagal`, sehingga
     * cadangan tak pernah dicoba dan peninjau tak diberi tahu apa sebabnya.
     */
    public function test_jawaban_kosong_dihitung_gagal_dan_memicu_cadangan(): void
    {
        $kosong = new class extends AbstractAiReviewer
        {
            public function __construct()
            {
                parent::__construct('kunci-uji', 'model-uji');
            }

            protected function callProvider(string $prompt): string
            {
                return '   ';
            }

            protected function buildPrompt(Document $d, array $c, string $f = self::FOKUS_SUBSTANSI): string
            {
                return 'prompt-uji';
            }

            protected function callPing(): \Illuminate\Http\Client\Response
            {
                throw new \LogicException('Penyedia palsu ini tak pernah di-ping.');
            }
        };

        $sendiri = $kosong->review(new Document, []);
        $this->assertTrue($sendiri['gagal'] ?? false, 'jawaban kosong tak ditandai gagal');
        $this->assertStringNotContainsString('tidak dapat diparse', $sendiri['summary']);

        $cadangan = $this->penyedia('cadangan', gagal: false);
        $hasil = (new FallbackReviewer([$kosong, $cadangan]))->review(new Document, []);

        $this->assertSame('jawaban cadangan', $hasil['summary'], 'cadangan tak dicoba saat utama menjawab kosong');
        $this->assertSame(1, $cadangan->dipanggil);
    }

    public function test_semua_gagal_mengembalikan_sebab_terakhir_bukan_pesan_kabur(): void
    {
        $hasil = (new FallbackReviewer([
            $this->penyedia('utama', gagal: true),
            $this->penyedia('cadangan', gagal: true),
        ]))->review(new Document, []);

        $this->assertTrue($hasil['gagal']);
        $this->assertStringContainsString('Insufficient credits', $hasil['summary']);
    }
}
