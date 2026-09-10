<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SmartPro — AI Review Assist
    |--------------------------------------------------------------------------
    |
    | Penyedia AI di-abstraksi lewat AiReviewerInterface, jadi berganti penyedia
    | cukup mengubah `provider` di sini — bukan memburu pemanggilan di kode.
    | AI hanya MEMBANTU peninjau: ia tak pernah meloloskan atau menolak dokumen
    | sendiri (PRD v3.1 §12).
    |
    */

    'ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
        'enabled' => env('AI_ENABLED', true),

        /*
        | Penyedia CADANGAN — dipakai HANYA bila panggilan ke penyedia utama
        | gagal (kredit habis, key dicabut, penyedia sedang tumbang). Sengaja
        | membawa key & model sendiri, bukan menunjuk blok `gemini`/`openrouter`
        | di bawah: dengan begitu cadangannya boleh berupa penyedia lain MAUPUN
        | akun/key OpenRouter kedua — dan satu akun yang kehabisan kredit tidak
        | ikut menjatuhkan cadangannya.
        |
        | Kosongkan AI_CADANGAN_PROVIDER untuk mematikan cadangan.
        */
        'cadangan' => [
            'provider' => env('AI_CADANGAN_PROVIDER'),
            'key' => env('AI_CADANGAN_API_KEY'),
            'model' => env('AI_CADANGAN_MODEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
    ],

];
