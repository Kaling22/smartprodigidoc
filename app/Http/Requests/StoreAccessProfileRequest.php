<?php

namespace App\Http\Requests;

use App\Models\AccessProfile;
use App\Models\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi profil akses (PLAN-AKSES-v7 Fase 4b).
 *
 * SATU kelas untuk store dan update — aturannya memang sama persis; yang
 * berbeda hanya baris mana yang dikecualikan dari keunikan nama. Dibedakan
 * lewat parameter rute `{profile}`, bukan lewat isian tersembunyi: rute adalah
 * kebenaran yang tak bisa dipalsukan pengirim.
 *
 * Otorisasinya sudah dipegang middleware `can:user.manage` di grup rutenya;
 * `authorize()` menegaskannya ulang supaya kelas ini tetap aman kalau suatu
 * hari rutenya dipindah.
 */
class StoreAccessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    /** Profil yang sedang disunting, atau null bila ini pembuatan baru. */
    public function profil(): ?AccessProfile
    {
        $profile = $this->route('profile');

        return $profile instanceof AccessProfile ? $profile : null;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255',
                Rule::unique('access_profiles', 'nama')->ignore($this->profil()?->id)],
            'keterangan' => ['nullable', 'string', 'max:255'],
            // `nullable` bukan `required`: profil yang HANYA memberi wewenang
            // meninjau JSA adalah profil yang sah, dan kotak centang yang tak
            // satu pun dicentang tak mengirimkan kunci ini sama sekali.
            'jenis_dibolehkan' => ['nullable', 'array'],
            // Kode divalidasi terhadap DocumentType::kode() — bukan daftar yang
            // ditulis ulang di sini. Jenis yang tak dikenal tersimpan diam-diam
            // berarti profil yang tak pernah cocok dengan apa pun.
            'jenis_dibolehkan.*' => ['string', Rule::in(DocumentType::kode())],
            'boleh_review_jsa' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama profil wajib diisi.',
            'nama.unique' => 'Sudah ada profil bernama sama.',
            'jenis_dibolehkan.*.in' => 'Ada jenis dokumen yang tidak dikenal.',
        ];
    }

    /**
     * Bentuk siap-simpan: jenis di-uppercase & dibuang duplikatnya.
     *
     * `array_values` penting — tanpa itu larik hasil `array_unique` menyimpan
     * kunci renggang (0, 2, 3) dan tersimpan sebagai OBJEK JSON, bukan larik.
     * `in_array()` di `bolehBuatJenis()` masih bekerja, tapi setiap pembaca
     * lain yang mengandaikan larik akan tersandung.
     *
     * @return array<string, mixed>
     */
    public function tersimpan(): array
    {
        return [
            'nama' => $this->string('nama')->trim()->value(),
            'keterangan' => $this->input('keterangan') ?: null,
            'jenis_dibolehkan' => array_values(array_unique(
                array_map('strtoupper', $this->input('jenis_dibolehkan', []))
            )),
            'boleh_review_jsa' => $this->boolean('boleh_review_jsa'),
        ];
    }
}
