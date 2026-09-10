<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Syarat memusnahkan dokumen: alasan tertulis + mengetik ulang nomornya.
 *
 * Ketik-ulang bukan hiasan. Ini satu-satunya aksi di aplikasi yang TIDAK punya
 * rollback — cascade menghapus isi, riwayat tinjauan, persetujuan, versi, dan
 * masukan sekaligus. SweetAlert saja tak cukup: tombol "Ya" ditekan refleks,
 * sedangkan mengetik PPA-ADRO-SOP-ICTMD-05 memaksa mata membaca dokumen MANA
 * yang sedang dihapus.
 */
class PurgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'min:10', 'max:1000'],
            // Rule::in dengan satu nilai = "harus persis nomor dokumen ini".
            'konfirmasi_nomor' => ['required', 'string', Rule::in([$this->route('document')->displayNumber()])],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan penghapusan wajib diisi.',
            'alasan.min' => 'Alasan terlalu singkat — tuliskan setidaknya 10 karakter.',
            'konfirmasi_nomor.in' => 'Nomor dokumen yang diketik tidak cocok. Salin persis seperti yang tertera.',
            'konfirmasi_nomor.required' => 'Ketik ulang nomor dokumen untuk mengonfirmasi.',
        ];
    }
}
