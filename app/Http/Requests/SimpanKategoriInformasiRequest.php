<?php

namespace App\Http\Requests;

use App\Models\InformasiKategori;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validasi Master Data → kartu Kategori Informasi (PLAN-AKSES-v8 Fase 5b,
 * ketetapan pemilik F & G).
 *
 * Tiga hal yang dijaga di sini:
 *
 * 1. **`slug` lahir sekali, dari nama, lalu beku.** `informasi.kategori`
 *    menyimpannya sebagai TEKS (bukan foreign key — lihat migration tabel
 *    `informasi`), jadi mengubah slug memutus setiap dokumen yang menunjuk ke
 *    sana tanpa satu pun galat: barisnya hanya berhenti muncul. Karena itu ia
 *    tak pernah dibaca dari kiriman saat menyunting, dan saat menambah ia
 *    diberi akhiran angka bila bentrok — bukan ditolak. Admin mengetik NAMA;
 *    kunci teknis yang bentrok bukan kesalahannya dan bukan urusannya.
 *
 * 2. **Kolom yang bisa dipilih hanya yang sudah ada di tabel** (ketetapan G):
 *    `edisi`, `no_revisi`, `tanggal_efektif`. Kolom baru menuntut migration.
 *    Menawarkan yang lain berarti menjanjikan kotak isian yang tak punya
 *    tempat menyimpan isinya.
 *
 * 3. **Ikon wajib Bootstrap Icons** (CLAUDE.md §13: nol emoji). Nama kelas dari
 *    set lain tak punya font-nya di sini dan hanya menghasilkan kotak kosong —
 *    kegagalan yang tak terlihat sampai seseorang membuka sidebar.
 */
class SimpanKategoriInformasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('user.manage');
    }

    public function kategori(): ?InformasiKategori
    {
        return $this->route('kategori');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ikon' => Str::lower(trim((string) $this->input('ikon'))) ?: 'bi-info-circle',
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => [
                'required', 'string', 'max:60',
                Rule::unique('informasi_kategori', 'nama')->ignore($this->kategori()),
            ],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'ikon' => ['required', 'string', 'max:50', 'regex:/^bi-[a-z0-9-]+$/'],
            'kolom' => ['array'],
            'kolom.*' => [Rule::in(array_keys(InformasiKategori::KOLOM_TERSEDIA))],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Data siap simpan — `kolom[]` dipetakan ke `kolom_json`, dan saat menambah
     * ditambahi `slug` serta `urutan` (di ekor daftar).
     */
    public function bersih(): array
    {
        $data = [
            'nama' => $this->validated('nama'),
            'deskripsi' => $this->validated('deskripsi'),
            'ikon' => $this->validated('ikon'),
            'kolom_json' => array_values($this->validated('kolom', [])),
            'is_active' => $this->validated('is_active'),
        ];

        if (! $this->kategori()) {
            $data['slug'] = $this->slugBebas($this->validated('nama'));
            $data['urutan'] = (int) InformasiKategori::max('urutan') + 10;
        }

        return $data;
    }

    /** Slug dari nama, diberi akhiran angka bila sudah terpakai. Maks 32 aksara. */
    private function slugBebas(string $nama): string
    {
        $dasar = Str::limit(Str::slug($nama, '_'), 32, '') ?: 'kategori';
        $slug = $dasar;

        for ($n = 2; InformasiKategori::where('slug', $slug)->exists(); $n++) {
            $slug = Str::limit($dasar, 32 - strlen((string) $n) - 1, '')."_{$n}";
        }

        return $slug;
    }

    public function messages(): array
    {
        return [
            'ikon.regex' => 'Ikon harus nama kelas Bootstrap Icons, mis. bi-journal-bookmark.',
            'nama.unique' => 'Sudah ada kategori bernama sama.',
        ];
    }

    public function attributes(): array
    {
        return ['nama' => 'nama kategori', 'ikon' => 'ikon', 'kolom' => 'kolom yang dipakai'];
    }
}
