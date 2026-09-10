<?php

namespace App\Services;

use App\Models\Document;
use App\Services\RichText\PembersihHtml;

/**
 * Props BERSAMA layar tinjau — satu sumber untuk tiga controller.
 *
 * `review/show.blade.php` sejak awal dipakai TIGA layar sekaligus (peninjauan
 * SH/DH, peninjauan Management Development, dan Masukan Sejawat antar-GL), dan
 * yang membedakannya cuma beberapa variabel. Begitu halaman itu pindah ke
 * Inertia, "beberapa variabel" berubah jadi larik props — dan larik yang
 * disalin di tiga controller adalah larik yang suatu hari bertiga tak sepakat.
 * Jadi bagian bersamanya dikumpulkan di sini; masing-masing controller cukup
 * menimpa kunci yang memang khusus baginya.
 *
 * Yang TIDAK ikut, sengaja: model Eloquent apa pun. Props Inertia tertulis
 * seluruhnya di atribut `data-page` dan terbaca lewat "view source", sedangkan
 * layar ini menggendong `attachments.comments.user` — koleksi `User` lengkap
 * dengan `password` dan `remember_token`. Itu kelas kesalahan yang sama dengan
 * Fase 6 (password), Fase 7 (kunci API), Fase 8 (creator), dan Fase 9 (kandidat
 * peserta). Yang dikirim hanya kolom yang memang digambar layar.
 */
class ReviewScreen
{
    /**
     * Tipe seksi yang IKUT ditinjau.
     *
     * Dulu satu `@php` di kepala `review/show.blade.php`. Dikirim sebagai props,
     * bukan diketik ulang di TSX: menambah tipe seksi yang bisa ditinjau
     * seharusnya cukup menyentuh satu baris di server (pakem P1/P3).
     *
     * `jsa_analysis` WAJIB ikut — analisa JSA (langkah → bahaya → pengendalian)
     * harus bisa ditinjau sampai tingkat bersarangnya.
     */
    public const TIPE_DITINJAU = ['rich_list', 'reference_picker', 'repeatable_group', 'jsa_analysis'];

    /**
     * Bagian props yang sama untuk ketiga layar.
     *
     * @param  bool  $denganAnotasiLama  Masukan Sejawat sengaja TIDAK menerimanya:
     *                                   catatan putaran peninjauan sebelumnya
     *                                   adalah percakapan peninjau resmi dengan
     *                                   pembuat, bukan urusan rekan sejawat.
     * @return array<string, mixed>
     */
    public function bersama(Document $document, bool $denganAnotasiLama = true): array
    {
        // `untuk()`: bab opsional yang dimatikan pembuat tak ikut ditinjau, dan
        // nomor bab di layar tinjau sama persis dengan yang tercetak di PDF.
        $schema = SchemaService::untuk($document);

        return [
            'document' => [
                'id' => $document->id,
                'nomor' => $document->displayNumber(),
                'judul' => $document->title,
                'dept' => $document->department?->code,
                // Angka DOKUMEN — yang sama dengan yang tercetak di kop PDF.
                // Sengaja BUKAN `revision_round`, yang menghitung berapa kali
                // dokumen dikembalikan selama peninjauan: dokumen Edisi 1
                // Revisi 2 dulu terbaca "Revisi ke-0" oleh peninjaunya.
                'edisi' => (int) ($document->edisi ?: 1),
                'noRevisi' => (int) $document->no_revisi,
                // Putaran peninjauan tetap berguna ("ini kali kedua saya
                // melihatnya"), tapi diberi namanya sendiri.
                'putaran' => (int) $document->revision_round,
            ],
            // Schema PENUH, apa adanya — penggerak tunggal layar ini (pakem P1).
            // Seksi baru yang ditambahkan lewat `schema_json` muncul sendiri.
            'schema' => $schema->raw(),
            'tipeDitinjau' => self::TIPE_DITINJAU,
            'contentMap' => $this->bersihkanRichText($document->contentMap(), $schema),
            'anotasiLama' => $denganAnotasiLama ? $this->anotasiLama($document) : [],
            'lampiran' => $this->lampiran($document),
        ];
    }

    /**
     * Isi `rich_text` dibersihkan DI SERVER, sekali, sebelum dikirim.
     *
     * Blade lama memanggil {@see PembersihHtml::bersihkan()} tepat saat
     * mencetaknya. Alasannya tak berubah sedikit pun: inilah SATU-SATUNYA layar
     * yang menyajikan isi `document_contents` sebagai HTML kepada orang yang
     * wewenangnya LEBIH TINGGI daripada penulisnya. Baris yang telanjur
     * tersimpan sebelum sanitasi ada, dan jalur tulis apa pun yang kelak lupa
     * membersihkan, berhenti di sini.
     *
     * Yang berpindah cuma TEMPATNYA — dari saat mencetak menjadi saat menyusun
     * props. Membersihkannya di peramban bukan pilihan: HTML yang sampai ke
     * `data-page` sudah terlanjur ada di halaman.
     *
     * Kolom mana yang `rich_text` dibaca dari SCHEMA, tak pernah dari daftar
     * nama kolom yang ditulis tangan (pakem P1).
     *
     * @param  array<string, mixed>  $contentMap
     * @return array<string, mixed>
     */
    private function bersihkanRichText(array $contentMap, SchemaService $schema): array
    {
        foreach ($schema->allSections() as $section) {
            $kunciRich = array_column(array_filter(
                $section['group_fields'] ?? $section['fields'] ?? [],
                fn ($f) => ($f['type'] ?? '') === 'rich_text',
            ), 'key');

            $isi = $contentMap[$section['key'] ?? ''] ?? null;

            if ($kunciRich === [] || ! is_array($isi)) {
                continue;
            }

            foreach ($isi as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach ($kunciRich as $k) {
                    if (is_string($item[$k] ?? null)) {
                        $contentMap[$section['key']][$i][$k] = PembersihHtml::bersihkan($item[$k]);
                    }
                }
            }
        }

        return $contentMap;
    }

    /**
     * Catatan putaran sebelumnya: `section_key` → `item_ref` → daftar komentar.
     *
     * Pengumpulan + penyaringannya dipegang {@see CatatanPerItem}, satu-satunya
     * pemilik aturan "catatan mana menempel di item mana" — layar wizard
     * pembuat memakai sumber yang sama persis. Di sini bentuknya diratakan
     * kembali jadi daftar STRING: layar tinjau hanya mencetak komentarnya, dan
     * penulisnya selalu peninjau itu sendiri.
     *
     * Masukan sejawat sengaja TIDAK ikut (`$denganSejawat: false`) — alasannya
     * sama dengan `$denganAnotasiLama` di {@see bersama()}: catatan antar-GL
     * bukan bagian dari percakapan peninjau resmi dengan pembuat.
     *
     * @return array<string, array<string, list<string>>>
     */
    private function anotasiLama(Document $document): array
    {
        return collect(app(CatatanPerItem::class)->untuk($document))
            ->map(fn (array $perSection) => collect($perSection)
                ->map(fn (array $perItem) => array_column($perItem, 'komentar'))
                ->all())
            ->all();
    }

    /**
     * Foto lampiran + komentarnya (v3.1 §6.2).
     *
     * Cap waktunya diformat DI SINI. `diffForHumans()` di peramban berarti
     * bahasa & zona waktu ditentukan mesin pengguna, bukan aplikasi — dan
     * aplikasi ini berbahasa Indonesia ber-WITA (CLAUDE.md §2).
     *
     * @return list<array<string, mixed>>
     */
    private function lampiran(Document $document): array
    {
        return $document->attachments()
            ->where('mime', 'like', 'image/%')
            ->with('comments.user')
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'url' => asset('storage/'.$att->path),
                'nama' => $att->original_name,
                'komentar' => $att->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'oleh' => $c->user->name ?? '—',
                    'isi' => $c->comment,
                    'waktu' => $c->created_at?->diffForHumans(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
