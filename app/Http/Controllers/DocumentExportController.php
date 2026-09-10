<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Export daftar induk dokumen per jenis ke format Excel.
 *
 * Hanya menampilkan dokumen berstatus Berlaku & Sedang Direvisi.
 *
 * Akses: `document.publish` (PJO + Admin IT) melihat KETUJUH departemen;
 * `document.review` (SH/DH) dan `document.create` (GL) hanya departemennya
 * sendiri. Sengaja BUKAN `document.view_all`: izin itu juga dipegang MD, yang
 * meninjau penulisan dan tak berkepentingan atas daftar induk. Alasan
 * lengkapnya di routes/web.php.
 */
class DocumentExportController extends Controller
{
    /** Revisi per edisi: 0..4, lalu Edisi+1 Revisi 0 (CLAUDE.md §7). */
    private const REVISI_TERTINGGI = 4;

    public function export(Request $request, string $type): Response
    {
        $user = $request->user();
        $lintasDept = $user->can('document.publish');
        // GL ikut sejak PLAN-AKSES-v7 Fase 1: dialah penyusunnya, jadi dialah
        // yang paling sering ditanya "nomor berapa yang masih kosong". Ia
        // TIDAK menambah lingkup — `$lintasDept` tetap false untuknya, jadi
        // penyaring departemen di bawah tetap mengurungnya. MD tetap di luar:
        // ia memegang `document.view_all`, bukan salah satu dari ketiga ini.
        abort_unless(
            $lintasDept || $user->can('document.review') || $user->can('document.create'),
            403,
        );

        // Daftar jenis yang sah tak ditulis ulang di sini: tabelnya sendiri yang
        // menjawab. Dulu ada konstanta ['SOP','IK','SP','JSA'] di atas, dan
        // menambah jenis (FK/PX) berarti jenis itu ada di menu tapi ekspornya 404.
        $jenis = strtoupper($type);
        $tipe = DocumentType::where('code', $jenis)->firstOrFail();

        /*
        | Versi LAMA (obsolete) ikut diambil, bukan hanya yang berlaku: tanggal
        | tiap revisi tersimpan di baris versinya sendiri (`published_at`), dan
        | dokumen yang berlaku sekarang hanya tahu tanggalnya sendiri. Satu
        | query untuk seluruh rantai — bukan satu query per dokumen.
        |
        | Departemen SH/DH/GL dipaksa dari user dan TIDAK pernah datang dari
        | input: parameter departemen berarti SH satu dept bisa menarik daftar
        | induk dept lain.
        */
        $rantai = Document::where('document_type_id', $tipe->id)
            ->unless($lintasDept, fn ($q) => $q->where('department_id', $user->department_id))
            ->get()
            ->keyBy('id');

        $dokumen = $rantai->whereIn('status', ['published', 'sedang_direvisi'])
            ->sortBy(fn ($d) => $d->displayNumber())
            ->values();

        [$baris, $kolom] = self::matriks($dokumen, $rantai);

        // Nama site dari setelan (Fase 5a); bawaannya "ADARO INDONESIA", jadi
        // nama berkas tak berubah sampai Admin benar-benar menggantinya.
        $namaDasar = 'DAFTAR INDUK DOKUMEN ' . $jenis . ' PPA SITE ' . Pengaturan::namaSite();

        /*
        | PDF memakai view yang SAMA dengan Excel — bukan salinan yang dirapikan
        | untuk cetak. Dua lembar yang isinya "seharusnya sama" pasti berselisih
        | begitu satu di antaranya disentuh; satu view membuat itu mustahil.
        | Lanskap karena matriks Edisi × Revisi melebar ke kanan, bukan ke bawah.
        */
        if (strtolower((string) $request->query('format')) === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.export-excel', [
                'baris' => $baris,
                'kolom' => $kolom,
                'jenis' => $jenis,
            ])->setPaper('a4', 'landscape')->download($namaDasar . '.pdf');
        }

        $namaFile = $namaDasar . '.xls';

        // BOM UTF-8 agar Excel membaca karakter Indonesia dengan benar.
        $html = "\xEF\xBB\xBF" . view('documents.export-excel', [
            'baris' => $baris,
            'kolom' => $kolom,
            'jenis' => $jenis,
        ])->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $namaFile . '"',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Peta (Edisi, Revisi) → tanggal per dokumen, plus daftar kolom revisi yang
     * perlu dicetak.
     *
     * Lebar kolom ADAPTIF (keputusan pemilik B4+B6): edisi yang sudah selesai
     * selalu penuh rev 0–4 — roll-over §7 hanya menaikkan edisi SETELAH revisi 4
     * terlampaui, jadi edisi lama pasti punya kelimanya. Yang dipotong hanya
     * edisi TERAKHIR, berhenti di revisi tertinggi yang benar-benar ada.
     *
     * @param  \Illuminate\Support\Collection<int,Document>  $dokumen  yang berlaku
     * @param  \Illuminate\Support\Collection<int,Document>  $rantai   semua versi, ber-key id
     * @return array{0:array<int,array{dok:Document,peta:array<int,array<int,mixed>>,efektif:mixed}>,1:array<int,list<int>>}
     */
    public static function matriks($dokumen, $rantai): array
    {
        $baris = [];
        $maxEdisi = 1;
        $maxRevisi = 0;

        foreach ($dokumen as $dok) {
            $peta = [];
            $akar = $dok;

            // Telusuri rantai revisi mundur lewat `revises_document_id`. Berhenti
            // sendiri saat menemui dokumen pertama (penunjuknya null) atau versi
            // yang sudah dimusnahkan (tak ada di `$rantai`).
            for ($versi = $dok; $versi; $versi = $rantai->get($versi->revises_document_id)) {
                $peta[(int) ($versi->edisi ?: 1)][(int) $versi->no_revisi] = $versi->published_at;
                $akar = $versi;
            }

            $baris[] = ['dok' => $dok, 'peta' => $peta, 'efektif' => $akar->published_at];

            $edisi = (int) ($dok->edisi ?: 1);
            if ($edisi > $maxEdisi) {
                $maxEdisi = $edisi;
                $maxRevisi = (int) $dok->no_revisi;
            } elseif ($edisi === $maxEdisi) {
                $maxRevisi = max($maxRevisi, (int) $dok->no_revisi);
            }
        }

        $kolom = [];
        for ($edisi = 1; $edisi <= $maxEdisi; $edisi++) {
            $kolom[$edisi] = range(0, $edisi < $maxEdisi ? self::REVISI_TERTINGGI : $maxRevisi);
        }

        return [$baris, $kolom];
    }
}
