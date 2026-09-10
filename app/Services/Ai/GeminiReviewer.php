<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/** Google Gemini implementation (D10). Only the HTTP call lives here. */
class GeminiReviewer extends AbstractAiReviewer
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /**
     * Metadata satu model — endpoint yang sama tanpa `:generateContent`.
     *
     * Dipilih karena ia membuktikan DUA hal sekaligus: kuncinya sah (401/403
     * bila tidak) DAN nama modelnya ada (404 bila salah ketik). Daftar model
     * saja hanya membuktikan yang pertama.
     */
    private const ENDPOINT_MODEL = 'https://generativelanguage.googleapis.com/v1beta/models/%s';

    protected function callProvider(string $prompt): string
    {
        $response = Http::timeout(self::TIMEOUT_DETIK)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->post(sprintf(self::ENDPOINT, $this->model), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => self::MAX_TOKEN_JAWABAN, 'responseMimeType' => 'application/json'],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->pesanGalat($response));
        }

        return (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }

    protected function callPing(): \Illuminate\Http\Client\Response
    {
        return Http::timeout(self::TIMEOUT_PING)
            ->withHeaders(['x-goog-api-key' => $this->apiKey])
            ->get(sprintf(self::ENDPOINT_MODEL, $this->model));
    }
}
