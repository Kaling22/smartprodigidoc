<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Validasi setelan AI (PLAN-AKSES-v8 Fase 5c).
 *
 * Dua hal yang membedakannya dari formulir biasa:
 *
 * 1. KUNCI API BOLEH KOSONG, dan kosong berarti "pertahankan yang lama" —
 *    bukan "hapus". Layarnya menampilkan kunci BERTOPENG, jadi Admin yang
 *    hanya ingin mengganti model tidak punya kunci asli untuk diketik ulang.
 *    Tanpa aturan ini, setiap penyimpanan biasa mencabut kunci AI diam-diam.
 *    Untuk benar-benar menghapusnya tersedia kotak centang tersendiri.
 *
 * 2. PENYEDIA DIBATASI DAFTAR TERTUTUP. `AppServiceProvider::buatReviewer()`
 *    hanya mengenal `gemini` & `openrouter`; nama lain diam-diam jatuh ke
 *    NullReviewer, yang di layar terbaca sebagai "AI dinonaktifkan" tanpa
 *    seorang pun tahu sebabnya salah ketik.
 */
class SimpanAiRequest extends FormRequest
{
    /** Penyedia yang benar-benar punya kelas implementasinya. */
    public const PENYEDIA = ['gemini' => 'Google Gemini', 'openrouter' => 'OpenRouter'];

    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            // Kunci API kerap ter-copy bersama spasi/baris baru dari layar
            // penyedia. Dibiarkan, ia terkirim apa adanya ke header HTTP dan
            // gagalnya berbunyi "401 unauthorized" — sesat menuduh kuncinya.
            'key' => trim((string) $this->input('key')),
            'cadangan_key' => trim((string) $this->input('cadangan_key')),
            'cadangan_provider' => Str::lower(trim((string) $this->input('cadangan_provider'))) ?: null,
        ]);
    }

    public function rules(): array
    {
        $penyedia = array_keys(self::PENYEDIA);

        return [
            'enabled' => ['boolean'],
            'provider' => ['required', 'string', 'in:'.implode(',', $penyedia)],
            'model' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:400'],
            'hapus_key' => ['boolean'],

            'cadangan_provider' => ['nullable', 'string', 'in:'.implode(',', $penyedia)],
            // Model cadangan wajib begitu penyedianya dipilih: penyedia tanpa
            // model menghasilkan NullReviewer, alias cadangan yang tampak
            // terpasang di layar tapi tak pernah menangkap kegagalan apa pun.
            'cadangan_model' => ['nullable', 'required_with:cadangan_provider', 'string', 'max:120'],
            'cadangan_key' => ['nullable', 'string', 'max:400'],
            'hapus_cadangan_key' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'provider' => 'penyedia AI',
            'model' => 'model',
            'key' => 'kunci API',
            'cadangan_provider' => 'penyedia cadangan',
            'cadangan_model' => 'model cadangan',
            'cadangan_key' => 'kunci API cadangan',
        ];
    }
}
