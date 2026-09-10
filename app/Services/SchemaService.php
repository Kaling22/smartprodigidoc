<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;

/**
 * Schema engine (D1). Reads a document type's schema definition and exposes
 * its steps and sections. The SAME schema drives the form, the preview, and
 * the PDF — so structural inconsistency between them is impossible.
 *
 * The engine never guesses layout; it renders exactly what the schema declares.
 */
class SchemaService
{
    /** Nomor bab. Dokumen mutu PPA tak pernah melewati sepuluh bab. */
    public const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];

    private array $schema;

    public function __construct(DocumentType $type)
    {
        $this->schema = $type->schema_json;
    }

    public static function for(DocumentType $type): self
    {
        return new self($type);
    }

    /**
     * Schema SATU DOKUMEN — dibangun DAN dinomori ulang sekaligus.
     *
     * Dipakai semua jalur TAMPIL (cetak, form tinjau, prompt AI, lembar Catatan
     * Revisi). Jalur SIMPAN sengaja TIDAK memakainya: penomoran ulang MEMBUANG
     * bab opsional yang dimatikan dari daftar section, jadi menyimpan lewat
     * daftar itu berarti isi babnya berhenti tersimpan — dan hilang diam-diam
     * saat sakelarnya dinyalakan lagi. Mematikan sakelar MENYEMBUNYIKAN bab,
     * bukan menghapus isinya.
     */
    public static function untuk(Document $document): self
    {
        return self::for($document->type)->denganPenomoran($document->contentMap());
    }

    /**
     * Apakah sebuah bab opsional sedang MENYALA?
     *
     * Aturannya satu kalimat, dan letaknya sengaja cuma di sini: kunci yang TAK
     * ADA berarti MENYALA. Hanya '0' yang eksplisit mematikan.
     *
     * Bab TIDAK BOLEH dinilai dari isinya ("kosong berarti tak dipakai"):
     * setiap SOP yang terlanjur terbit dengan flowchart kosong akan dinomori
     * ulang tanpa ada yang menyentuhnya — dokumen yang sudah disahkan dan sudah
     * dibagikan berubah nomor babnya sendiri.
     */
    public static function babAktif(array $contentMap, ?string $toggleKey): bool
    {
        return $toggleKey === null || (string) ($contentMap[$toggleKey] ?? '1') !== '0';
    }

    /**
     * Instans baru dengan bab opsional yang MATI dibuang, dan sisanya dinomori
     * ULANG menurut posisinya — "VI. AKTIVITAS" turun jadi "V. AKTIVITAS",
     * auto_number "6." jadi "5.".
     *
     * Yang dinomori hanya bab ber-`nama` (ditulis DocumentTypeSeeder::bernomor);
     * user_picker dan bab berlabel tetap seperti IK dilewati.
     *
     * Tak ada satu pun bab yang dibuang → instans ini dikembalikan APA ADANYA.
     * Dengan begitu seluruh dokumen yang sudah ada — dan seluruh jenis yang tak
     * punya bab opsional — tercetak persis seperti sebelum fitur ini ada.
     */
    public function denganPenomoran(array $contentMap): self
    {
        $schema = $this->schema;
        $nomor = 0;
        $adaYangDibuang = false;

        foreach (($schema['steps'] ?? []) as $i => $step) {
            $sections = [];

            foreach (($step['sections'] ?? []) as $section) {
                if (! self::babAktif($contentMap, $section['toggle_key'] ?? null)) {
                    $adaYangDibuang = true;

                    continue;
                }

                if (isset($section['nama'])) {
                    $nomor++;
                    $section['label'] = (self::ROMAWI[$nomor] ?? $nomor).'. '.$section['nama'];
                    $section['auto_number'] = $nomor.'.';
                }

                $sections[] = $section;
            }

            $schema['steps'][$i]['sections'] = $sections;

            // Judul langkah ikut menyebut bab yang tercetak: "Flowchart,
            // Aktivitas, Lampiran & Verifikasi" → "Aktivitas, Lampiran &
            // Verifikasi". Varian ini ditulis seeder, bukan disusun di sini.
            if ($adaYangDibuang && isset($step['title_tanpa_opsional'])) {
                $schema['steps'][$i]['title'] = $step['title_tanpa_opsional'];
            }
        }

        if (! $adaYangDibuang) {
            return $this;
        }

        $baru = clone $this;
        $baru->schema = $schema;

        return $baru;
    }

    public function raw(): array
    {
        return $this->schema;
    }

    public function docType(): string
    {
        return $this->schema['doc_type'] ?? '';
    }

    public function header(): ?string
    {
        return $this->schema['header'] ?? null;
    }

    public function footer(): ?string
    {
        return $this->schema['footer'] ?? null;
    }

    /** @return array<int, array> */
    public function steps(): array
    {
        return $this->schema['steps'] ?? [];
    }

    public function stepCount(): int
    {
        return count($this->steps());
    }

    /** Sections for a given 1-based step number. */
    public function sectionsForStep(int $step): array
    {
        foreach ($this->steps() as $s) {
            if (($s['step'] ?? null) === $step) {
                return $s['sections'] ?? [];
            }
        }

        return [];
    }

    public function stepTitle(int $step): string
    {
        foreach ($this->steps() as $s) {
            if (($s['step'] ?? null) === $step) {
                return $s['title'] ?? "Langkah {$step}";
            }
        }

        return "Langkah {$step}";
    }

    /** Flat list of every section across all steps. */
    public function allSections(): array
    {
        $out = [];
        foreach ($this->steps() as $s) {
            foreach (($s['sections'] ?? []) as $section) {
                $out[] = $section;
            }
        }

        return $out;
    }

    public function findSection(string $key): ?array
    {
        foreach ($this->allSections() as $section) {
            if (($section['key'] ?? null) === $key) {
                return $section;
            }
        }

        return null;
    }
}
