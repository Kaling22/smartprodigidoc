<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * OpenAI implementation. Only the HTTP call lives here.
 *
 * Diuji dgn kunci langsung dari platform.openai.com (BUKAN lewat OpenRouter) —
 * endpoint & autentikasinya beda sehingga perlu kelas tersendiri, walau bentuk
 * chat-completionsnya serupa OpenRouterReviewer.
 *
 * Model REASONING (o1/o3/o4-mini, …) TIDAK didukung di sini: penyedia itu
 * menolak `temperature`/`max_tokens` dan memakai `max_completion_tokens`
 * sebagai gantinya. Pakai model chat biasa (gpt-4o, gpt-4.1, gpt-4o-mini, …).
 */
class OpenAiReviewer extends AbstractAiReviewer
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    /** Ambil detail SATU model — membuktikan kunci sah (401) DAN nama model ada (404), pola sama dgn GeminiReviewer. */
    private const ENDPOINT_MODEL = 'https://api.openai.com/v1/models/%s';

    protected function callProvider(string $prompt): string
    {
        $response = Http::timeout(self::TIMEOUT_DETIK)
            ->withToken($this->apiKey)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Anda AI Document Auditor QMS/HSE. Audit menyeluruh & mendalam; laporkan setiap masalah sebagai temuan terpisah. Balas hanya JSON valid.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => self::MAX_TOKEN_JAWABAN,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException($this->pesanGalat($response));
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }

    protected function callPing(): \Illuminate\Http\Client\Response
    {
        return Http::timeout(self::TIMEOUT_PING)
            ->withToken($this->apiKey)
            ->get(sprintf(self::ENDPOINT_MODEL, $this->model));
    }
}
