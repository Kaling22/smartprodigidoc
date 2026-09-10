<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

/**
 * OpenRouter implementation (D10). OpenAI-compatible chat completions endpoint
 * that fronts many models (set OPENROUTER_MODEL). Only the HTTP call lives here.
 */
class OpenRouterReviewer extends AbstractAiReviewer
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    /** Keterangan kunci yang sedang dipakai — 401 bila kuncinya salah/dicabut. */
    private const ENDPOINT_KEY = 'https://openrouter.ai/api/v1/key';

    protected function callProvider(string $prompt): string
    {
        $response = Http::timeout(self::TIMEOUT_DETIK)
            ->withToken($this->apiKey)
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
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
            // Sertakan pesan penyedia, bukan angka status telanjang: "402" tak
            // memberi tahu siapa pun bahwa kredit OpenRouter habis, sementara
            // pesannya menyebutkan persis itu — di log MAUPUN di layar peninjau.
            throw new \RuntimeException($this->pesanGalat($response));
        }

        $pesan = data_get($response->json(), 'choices.0.message', []);

        // Model penalaran menaruh jawabannya di `reasoning` dan menyisakan
        // `content` kosong bila jatah token habis sebelum ia sempat menutup
        // tahap berpikir — JSON-nya tetap ada di sana, jadi dibaca sekalian.
        // parse() sudah tahan teks berbungkus prosa (selamatkanJson()).
        return (string) (data_get($pesan, 'content') ?: data_get($pesan, 'reasoning', ''));
    }

    // ponytail: OpenRouter diuji lewat /api/v1/key — nama model TIDAK ikut
    // diperiksa. Naikkan ke GET /api/v1/models dan cocokkan id-nya bila salah
    // ketik nama model terbukti jadi keluhan nyata.
    protected function callPing(): \Illuminate\Http\Client\Response
    {
        return Http::timeout(self::TIMEOUT_PING)
            ->withToken($this->apiKey)
            ->get(self::ENDPOINT_KEY);
    }
}
