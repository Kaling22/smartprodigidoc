<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Revisi judul dokumen — terpisah dari isian wizard (`sections`) karena
 * `title` kolom `documents`, bukan isi `document_contents`. Otorisasi
 * kepemilikan/status tetap diperiksa di `DocumentController::isEditable()`
 * (sama persis dgn saveStep/autosave), bukan di sini — `authorize()` hanya
 * memastikan peran yang boleh menyunting wizard sama sekali.
 */
class UpdateDocumentTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('document.create');
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255']];
    }

    public function messages(): array
    {
        return ['title.required' => 'Judul dokumen wajib diisi.'];
    }
}
