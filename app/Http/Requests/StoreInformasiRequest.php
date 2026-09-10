<?php

namespace App\Http\Requests;

use App\Models\Informasi;
use App\Services\DocumentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi unggahan Informasi (butir 3) — Tambah maupun Perbarui.
 *
 * Keduanya memakai kelas ini karena isiannya sama persis; yang berbeda hanya
 * aturan NOMOR, dan perbedaannya berlawanan arah:
 *
 *   • Tambah    → nomor harus BELUM ada. Menemukannya berarti pengunggah
 *                 sebenarnya sedang merevisi, dan ia diarahkan ke Perbarui.
 *   • Perbarui  → nomor & kategori DIWARISI dari versi berlakunya, tak diambil
 *                 dari kiriman. Itu yang membuat riwayat satu nomor mustahil
 *                 tercecer ke nomor lain karena salah ketik.
 *
 * Dibedakan lewat parameter rute `{induk}`, bukan lewat field tersembunyi:
 * rute adalah kebenaran yang tak bisa dipalsukan pengirim.
 */
class StoreInformasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()->can('informasi.manage');
    }

    /** Versi berlaku yang sedang diperbarui, atau null bila ini Tambah. */
    public function induk(): ?Informasi
    {
        $induk = $this->route('induk');

        return $induk instanceof Informasi ? $induk : null;
    }

    /** Kategori yang berlaku bagi permintaan ini. */
    public function kategori(): ?string
    {
        return $this->induk()?->kategori ?? $this->input('kategori');
    }

    public function rules(): array
    {
        $induk = $this->induk();
        $kolom = Informasi::kolomEkstra($this->kategori());

        // Kolom yang tak dipakai kategori ini `prohibited`, bukan sekadar
        // diabaikan: kiriman yang memuatnya berarti formulirnya tak sinkron
        // dengan aturannya, dan menelannya diam-diam menyimpan data yang tak
        // akan pernah tampil di layar mana pun.
        $ekstra = fn (string $nama, array $tambahan) => in_array($nama, $kolom, true)
            ? array_merge(['required'], $tambahan)
            : ['prohibited'];

        return [
            'kategori' => $induk
                ? ['prohibited']
                // AKTIF saja: kategori yang ditutup Admin tak boleh jadi
                // tujuan unggahan baru, meski dokumen lamanya tetap ada.
                : ['required', Rule::in(array_keys(Informasi::kategori(aktifSaja: true)))],
            'nomor' => $induk ? ['prohibited'] : ['required', 'string', 'max:150'],
            'judul' => ['required', 'string', 'max:255'],
            'edisi' => $ekstra('edisi', ['integer', 'min:1', 'max:99']),
            // Batasnya MAKS_REVISI — bukan 99. Revisi hanya 0..5; yang keenam
            // naik jadi Edisi+1 Rev 0 (CLAUDE.md §7,
            // DocumentService::nextEditionRevision). Baris warisan bernomor
            // lebih tinggi tak terkunci olehnya: rumus yang sama menggulung
            // mereka ke Edisi berikutnya saat diperbarui.
            'no_revisi' => $ekstra('no_revisi', ['integer', 'min:0', 'max:'.DocumentService::MAKS_REVISI]),
            'tanggal_efektif' => $ekstra('tanggal_efektif', ['date']),
            // Ekstensi yang diterima ikut KATEGORINYA: POSTER menerima gambar,
            // sisanya PDF (Informasi::EKSTENSI).
            'berkas' => [
                'required', 'file', 'max:40960',
                'mimes:'.implode(',', Informasi::ekstensi($this->kategori())),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Nomor kembar hanya dilarang pada TAMBAH. Pada Perbarui ia justru
            // yang dituju: versi baru memang bernomor sama, dan yang lama turun
            // jadi riwayat.
            if (! $this->induk() && filled($this->kategori()) && filled($this->nomor)) {
                $bentrok = Informasi::senomor($this->kategori(), $this->nomor)->berlaku()->first();

                if ($bentrok) {
                    $validator->errors()->add('nomor',
                        'Nomor ini sudah ada di kategori '.Informasi::labelKategori($this->kategori())
                        .' ("'.$bentrok->judul.'"). Untuk menggantinya dengan versi baru, pakai tombol Perbarui pada baris tersebut.');
                }
            }

            // `mimes:` hanya membaca EKSTENSI — ia meloloskan berkas apa pun
            // yang dinamai ulang jadi .pdf. Yang benar-benar menjawab "ini
            // PDF?" adalah lima byte pertamanya. Gambar dilewati: `mimes`
            // untuk gambar dibantu pemeriksaan isi bawaan Laravel.
            $berkas = $this->file('berkas');
            if ($berkas && $berkas->isValid()
                && strtolower($berkas->getClientOriginalExtension()) === 'pdf'
                && file_get_contents($berkas->getRealPath(), false, null, 0, 5) !== '%PDF-') {
                $validator->errors()->add('berkas', 'Berkas ini bukan PDF yang sah (isinya tidak diawali %PDF-).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'nomor.required' => 'Nomor dokumen wajib diisi.',
            'judul.required' => 'Judul wajib diisi.',
            'berkas.required' => 'Berkas wajib diunggah.',
            'berkas.max' => 'Ukuran berkas melebihi 40 MB.',
            'berkas.mimes' => 'Format berkas tidak diterima untuk kategori ini.',
            'edisi.required' => 'Edisi wajib diisi untuk kategori ini.',
            'no_revisi.required' => 'Nomor revisi wajib diisi untuk kategori ini.',
            'tanggal_efektif.required' => 'Tanggal efektif wajib diisi untuk kategori ini.',
        ];
    }
}
