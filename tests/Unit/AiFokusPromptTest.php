<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\Ai\AbstractAiReviewer;
use App\Services\Ai\AiReviewerInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Prompt AI berbeda menurut FOKUS tahap peninjauan.
 *
 * Tahap pertama (SH/DH) menilai SUBSTANSI; tahap kedua (MD) menilai PENULISAN.
 * Kalau keduanya diam-diam mengirim prompt yang sama, MD akan mengulang
 * pekerjaan SH/DH dan alur peninjauan berputar-putar — kegagalan yang TIDAK
 * terlihat dari layar, sebab keduanya tetap mengembalikan temuan.
 *
 * Diuji dengan menyadap prompt sebelum dikirim ke penyedia, jadi tak ada
 * panggilan jaringan sama sekali.
 */
class AiFokusPromptTest extends TestCase
{
    use DatabaseTransactions;

    private function penyadap(): AbstractAiReviewer
    {
        return new class extends AbstractAiReviewer
        {
            public string $promptTerakhir = '';

            public function __construct()
            {
                parent::__construct('kunci-uji', 'model-uji');
            }

            protected function callProvider(string $prompt): string
            {
                $this->promptTerakhir = $prompt;

                return '{"summary":"uji","findings":[]}';
            }

            protected function callPing(): \Illuminate\Http\Client\Response
            {
                throw new \LogicException('Penyadap prompt tak pernah di-ping.');
            }
        };
    }

    private function dokumenSop(): Document
    {
        return Document::whereHas('type', fn ($q) => $q->where('code', 'SOP'))->firstOrFail();
    }

    public function test_fokus_penulisan_menghasilkan_prompt_yang_berbeda(): void
    {
        $dok = $this->dokumenSop();

        $substansi = $this->penyadap();
        $substansi->review($dok, $dok->contentMap(), AiReviewerInterface::FOKUS_SUBSTANSI);

        $penulisan = $this->penyadap();
        $penulisan->review($dok, $dok->contentMap(), AiReviewerInterface::FOKUS_PENULISAN);

        $this->assertNotSame(
            $substansi->promptTerakhir,
            $penulisan->promptTerakhir,
            'Kedua tahap mengirim prompt identik — MD akan mengulang pekerjaan SH/DH.'
        );
    }

    public function test_prompt_penulisan_menyuruh_menandai_teks_anomali_sebagai_kritis(): void
    {
        $dok = $this->dokumenSop();
        $ai = $this->penyadap();
        $ai->review($dok, $dok->contentMap(), AiReviewerInterface::FOKUS_PENULISAN);

        $p = $ai->promptTerakhir;

        $this->assertStringContainsString('lorem ipsum', $p);
        $this->assertStringContainsString('critical', $p);
        $this->assertStringContainsString('typo', strtolower($p));
    }

    public function test_prompt_penulisan_melarang_menilai_substansi(): void
    {
        $dok = $this->dokumenSop();
        $ai = $this->penyadap();
        $ai->review($dok, $dok->contentMap(), AiReviewerInterface::FOKUS_PENULISAN);

        // Justru inilah batas yang menjaga alur tak berputar: MD tak boleh
        // menyuruh menambah/mengurangi langkah kerja.
        $this->assertStringContainsString('JANGAN menilai kebenaran isi', $ai->promptTerakhir);
        $this->assertStringContainsString('JANGAN menyarankan menambah', $ai->promptTerakhir);
    }

    public function test_bawaan_tetap_substansi_agar_tahap_pertama_tak_berubah(): void
    {
        $dok = $this->dokumenSop();

        $bawaan = $this->penyadap();
        $bawaan->review($dok, $dok->contentMap());          // tanpa argumen fokus

        $eksplisit = $this->penyadap();
        $eksplisit->review($dok, $dok->contentMap(), AiReviewerInterface::FOKUS_SUBSTANSI);

        $this->assertSame($bawaan->promptTerakhir, $eksplisit->promptTerakhir);
    }
}
