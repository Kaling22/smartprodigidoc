<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Catatan yang menempel pada SATU item dokumen — dari peninjau maupun sejawat.
 *
 * Dua kanal, satu bentuk. Keduanya sejak awal menyimpan `section_key` +
 * `item_ref` (peninjau di kolom `review_annotations`, sejawat di dalam
 * `document_feedback_antargl.catatan_json`), tapi jalan pulangnya ke pembuat
 * dulu membuang jangkarnya: `DocumentController::edit()` cuma
 * `groupBy('section_key')` lalu `pluck('comment')`, sehingga catatan atas baris
 * ke-3 Aktivitas jatuh jadi satu butir berlencana "aktivitas" di kotak merah
 * kepala halaman. Kelas ini memulihkan jangkar itu supaya tiap catatan bisa
 * digambar tepat di bawah isian yang dikomentari.
 *
 * Yang keluar dari sini SELURUHNYA skalar. Tak ada satu pun model Eloquent —
 * props Inertia tertulis lengkap di atribut `data-page` dan terbaca lewat "view
 * source", sedangkan sumber datanya menggendong relasi `User` (peninjau,
 * pemberi masukan) berikut `password` dan `remember_token`. Itu kelas kesalahan
 * yang sama dengan Fase 6/7/8 migrasi (CLAUDE.md §4).
 */
class CatatanPerItem
{
    public const SUMBER_PENINJAU = 'peninjau';

    public const SUMBER_SEJAWAT = 'sejawat';

    /** Panjang cuplikan teks item — cukup untuk dikenali, tak sampai menggeser tata letak. */
    private const PANJANG_KUTIPAN = 80;

    /**
     * `section_key` → `item_ref` → daftar catatan.
     *
     * `kutipan` adalah teks item SEBAGAIMANA ADANYA saat halaman dimuat.
     * Gunanya menangkap geser indeks: `item_ref` berbasis posisi, dan pembuat
     * bisa menyisipkan atau menghapus baris selagi merevisi. Dengan kutipan,
     * layar bisa menyatakan "teks item ini sudah berubah sejak dikomentari"
     * alih-alih diam-diam menempelkan catatan di baris yang keliru.
     * `kutipan === null` berarti itemnya sudah TIDAK ADA — lihat {@see yatim()}.
     *
     * @param  bool  $denganSejawat  Pemanggil WAJIB menentukannya sendiri:
     *                               masukan sejawat hanya boleh terbaca oleh
     *                               penyusun dokumen, pemberi masukannya, dan
     *                               pemegang `document.view_all` — syarat yang
     *                               sama dengan MasukanSejawatController::show().
     * @return array<string, array<string, list<array{komentar: string, oleh: string, sumber: string, kutipan: string|null}>>>
     */
    public function untuk(Document $document, bool $denganSejawat = false): array
    {
        $baris = $this->dariPeninjau($document);

        if ($denganSejawat) {
            $baris = $baris->concat($this->dariSejawat($document));
        }

        if ($baris->isEmpty()) {
            return [];
        }

        $isi = $document->contentMap();

        return $baris
            ->groupBy('section_key')
            ->map(fn (Collection $perSection, string $sectionKey) => $perSection
                ->groupBy('item_ref')
                ->map(fn (Collection $perItem, string $itemRef) => $perItem
                    ->map(fn (array $c) => [
                        'komentar' => $c['komentar'],
                        'oleh' => $c['oleh'],
                        'sumber' => $c['sumber'],
                        'kutipan' => $this->kutipan($isi[$sectionKey] ?? null, $itemRef),
                    ])
                    ->values()
                    ->all())
                ->all())
            ->all();
    }

    /**
     * Catatan yang TAK AKAN tergambar inline — supaya tak satu pun hilang.
     *
     * Dua sebabnya, dan keduanya nyata:
     *   • seksinya bertipe yang memang tak punya kotak catatan per item
     *     ({@see ReviewScreen::TIPE_DITINJAU}), mis. bila schema diubah sesudah
     *     catatannya telanjur ditulis;
     *   • itemnya sudah dihapus pembuat, jadi tak ada baris untuk ditempeli.
     *
     * @param  array<string, array<string, list<array<string, mixed>>>>  $perItem  keluaran {@see untuk()}
     * @return list<array{bagian: string, item: string, komentar: string, oleh: string, sumber: string}>
     */
    public function yatim(array $perItem, SchemaService $schema): array
    {
        $yatim = [];

        foreach ($perItem as $sectionKey => $perRef) {
            $section = $schema->findSection((string) $sectionKey);
            $inline = in_array($section['type'] ?? null, ReviewScreen::TIPE_DITINJAU, true);

            foreach ($perRef as $ref => $daftar) {
                foreach ($daftar as $c) {
                    if ($inline && $c['kutipan'] !== null) {
                        continue;
                    }

                    $yatim[] = [
                        'bagian' => $section['label'] ?? (string) $sectionKey,
                        'item' => self::label((string) $ref),
                        'komentar' => $c['komentar'],
                        'oleh' => $c['oleh'],
                        'sumber' => $c['sumber'],
                    ];
                }
            }
        }

        return $yatim;
    }

    /**
     * Penunjuk item, dalam bahasa manusia.
     *
     * Dua bentuk sekaligus, sebab formulir tinjauan memang mengirim dua bentuk:
     *   • angka biasa   → item ke-n sebuah daftar
     *   • L0-B1-P2      → Langkah / Bahaya / Pengendalian pada analisa JSA
     *
     * Nomor dinaikkan satu supaya cocok dengan yang TERBACA di layar & PDF,
     * yang menghitung dari 1, bukan dari 0.
     */
    public static function label(string $ref): string
    {
        if (is_numeric($ref)) {
            return 'Item '.((int) $ref + 1);
        }

        if (preg_match('/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/', $ref, $m)) {
            $teks = 'Langkah '.($m[1] + 1);
            $teks .= isset($m[2]) && $m[2] !== '' ? '.'.($m[2] + 1) : '';
            $teks .= isset($m[3]) && $m[3] !== '' ? '.'.($m[3] + 1) : '';

            return $teks.match (true) {
                isset($m[3]) && $m[3] !== '' => ' — Tindakan Pengendalian',
                isset($m[2]) && $m[2] !== '' => ' — Bahaya & Risiko',
                default => ' — Langkah Kerja',
            };
        }

        return $ref;
    }

    /**
     * Anotasi peninjau, SELURUH putaran.
     *
     * Sengaja tidak dibatasi putaran terakhir: dokumen yang bolak-balik dua kali
     * bisa saja masih menyisakan temuan putaran pertama yang belum tersentuh,
     * dan menyembunyikannya berarti pembuat mengira ia sudah selesai.
     *
     * Anotasi TANPA komentar dibuang. Ia memang ada: sejak tanda ✓/✗ JSA,
     * {@see ReviewDecision::simpanAnotasi()} menulis baris yang hanya memikul
     * `verdict`. Tanpa saringan ini layar menampilkan butir kosong, satu per
     * tindakan pengendalian.
     *
     * @return Collection<int, array{section_key: string, item_ref: string, komentar: string, oleh: string, sumber: string}>
     */
    private function dariPeninjau(Document $document): Collection
    {
        return $document->reviews()
            ->with(['annotations', 'reviewer'])
            ->get()
            ->flatMap(fn ($review) => $review->annotations
                ->filter(fn ($a) => filled($a->comment))
                ->map(fn ($a) => [
                    'section_key' => (string) $a->section_key,
                    'item_ref' => (string) $a->item_ref,
                    'komentar' => $a->comment,
                    'oleh' => $review->reviewer?->nameWithJabatan() ?? '—',
                    'sumber' => self::SUMBER_PENINJAU,
                ]));
    }

    /**
     * Masukan sejawat — bentuknya sudah sama sejak disimpan.
     *
     * `catatan_json` ditulis {@see \App\Http\Controllers\MasukanSejawatController::rapikan()}
     * sebagai `[{section_key, item_ref, komentar}]`, jadi yang perlu dilakukan
     * di sini cuma menambahkan penulis dan penanda sumbernya.
     *
     * @return Collection<int, array{section_key: string, item_ref: string, komentar: string, oleh: string, sumber: string}>
     */
    private function dariSejawat(Document $document): Collection
    {
        return $document->masukanSejawat()
            ->with('user')
            ->get()
            ->flatMap(fn ($m) => collect($m->catatan_json ?? [])
                ->filter(fn ($c) => filled($c['komentar'] ?? null))
                ->map(fn ($c) => [
                    'section_key' => (string) ($c['section_key'] ?? ''),
                    'item_ref' => (string) ($c['item_ref'] ?? ''),
                    'komentar' => (string) $c['komentar'],
                    'oleh' => $m->user?->nameWithJabatan() ?? '—',
                    'sumber' => self::SUMBER_SEJAWAT,
                ]));
    }

    /**
     * Cuplikan teks satu item, atau null bila itemnya sudah tak ada.
     *
     * Bentuk `item_ref` mengikuti apa yang ditulis formulir tinjauan; strukturnya
     * dibaca dari isi dokumen, bukan dari schema, sebab yang ditanyakan memang
     * "apa yang tertulis di baris ini SEKARANG".
     *
     * Bedakan `null` dari `''`: yang pertama berarti barisnya lenyap (catatannya
     * jadi yatim), yang kedua berarti barisnya ada tapi memang belum diisi.
     */
    private function kutipan(mixed $nilai, string $ref): ?string
    {
        if (! is_array($nilai)) {
            return null;
        }

        // Analisa JSA: L{langkah}-B{bahaya}-P{pengendalian}.
        if (preg_match('/^L(\d+)(?:-B(\d+))?(?:-P(\d+))?$/', $ref, $m)) {
            $langkah = $nilai[(int) $m[1]] ?? null;

            if (! is_array($langkah)) {
                return null;
            }

            if (! isset($m[2]) || $m[2] === '') {
                return $this->potong($langkah['langkah'] ?? '');
            }

            $bahaya = ($langkah['bahaya'] ?? [])[(int) $m[2]] ?? null;

            if (! is_array($bahaya)) {
                return null;
            }

            if (! isset($m[3]) || $m[3] === '') {
                return $this->potong($bahaya['risiko'] ?? '');
            }

            $kendali = ($bahaya['pengendalian'] ?? [])[(int) $m[3]] ?? null;

            return is_string($kendali) ? $this->potong($kendali) : null;
        }

        if (! is_numeric($ref)) {
            return null;
        }

        $item = $nilai[(int) $ref] ?? null;

        // `rich_list` / `reference_picker` menyimpan string; `repeatable_group`
        // menyimpan larik kolom — kolom pertama yang terisi mewakili barisnya.
        if (is_string($item)) {
            return $this->potong($item);
        }

        if (! is_array($item)) {
            return null;
        }

        foreach ($item as $isiKolom) {
            if (is_string($isiKolom) && filled(strip_tags($isiKolom))) {
                return $this->potong($isiKolom);
            }
        }

        return '';
    }

    /** `strip_tags` wajib: kolom `rich_text` menyimpan markup Quill, bukan teks polos. */
    private function potong(string $teks): string
    {
        return Str::limit(trim(html_entity_decode(strip_tags($teks))), self::PANJANG_KUTIPAN);
    }
}
