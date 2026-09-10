<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Syarat membersihkan SELURUH dokumen (PLAN C §C4): alasan tertulis + mengetik
 * frasa lengkap.
 *
 * Sengaja lebih berat daripada {@see PurgeDocumentRequest}. Di sana yang diketik
 * ulang adalah nomor dokumen — mata dipaksa membaca dokumen MANA yang dihapus.
 * Di sini tak ada satu nomor pun yang bisa disalin: yang dihapus adalah seluruh
 * arsip mutu tujuh departemen sekaligus, jadi yang diketik adalah kalimat yang
 * menyatakan persis itu, dan mustahil diketik tanpa sadar.
 */
class PurgeAllRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
            // Rule::in dengan satu nilai = "harus persis frasa ini".
            'konfirmasi' => ['required', 'string', Rule::in(['MUSNAHKAN SEMUA DOKUMEN'])],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pembersihan wajib diisi.',
            'alasan.min' => 'Alasan terlalu singkat — tuliskan setidaknya 10 karakter.',
            'konfirmasi.required' => 'Ketik frasa konfirmasi untuk melanjutkan.',
            'konfirmasi.in' => 'Frasa konfirmasi tidak cocok. Ketik persis: MUSNAHKAN SEMUA DOKUMEN',
        ];
    }
}
