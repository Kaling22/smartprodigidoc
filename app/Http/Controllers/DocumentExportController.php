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
        $rantai = Document::with(['contents' => fn ($q) => $q->where('section_key', 'catatan_revisi')])
            ->where('document_type_id', $tipe->id)
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
     * Tanggal tiap revisi dibaca dari lembar CATATAN REVISI dokumen itu SENDIRI
     * (`sections.catatan_revisi`, diisi manual — baik saat dokumen lama
     * didaftarkan lewat `DocumentArsipController`, maupun lewat langkah "Log
     * Revisi" wizard), BUKAN dari `published_at` versi-versi di rantai
     * `revises_document_id`. Baris lembar itu SENGAJA terakumulasi lintas
     * revisi (`DocumentWizard::persistRevisionLog` + salinan penuh section saat
     * `requestRevision`), jadi dokumen yang berlaku SEKARANG sudah memegang
     * seluruh riwayat tanggalnya sendiri — rantai hanya perlu ditelusuri untuk
     * "Tanggal Efektif" (tanggal terbit PERTAMA, revisi 0, yang tak pernah
     * dicatat di lembar ini).
     *
     * `no_rev` per baris TIDAK bertanda edisi (bebas diketik, tanpa batas atas
     * — lihat komentar `DocumentWizard::persistRevisionLog`), jadi batas edisi
     * dikenali dari POLA angkanya: naik selama masih satu edisi (1,2,3,4,5),
     * lalu TURUN begitu masuk edisi berikutnya (kembali ke 1). Itulah yang
     * memisahkan blok "Edisi 1" dari "Edisi 2" di grid, persis seperti yang
     * sungguh diketik saat pendaftaran.
     *
     * Lebar kolom ADAPTIF (keputusan pemilik B4+B6): edisi yang sudah selesai
     * selalu penuh 1..`DocumentService::MAKS_REVISI` — roll-over hanya
     * menaikkan edisi SETELAH revisi tertinggi terlampaui, jadi edisi lama
     * pasti punya semuanya. Yang dipotong hanya edisi TERAKHIR, berhenti di
     * revisi tertinggi yang benar-benar ada (minimal SATU kolom, walau
     * dokumennya belum pernah direvisi).
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
            // Root chain ditelusuri HANYA untuk "Tanggal Efektif": tanggal
            // terbit pertama sebelum revisi apa pun, yang tak pernah punya
            // baris di catatan_revisi. Berhenti sendiri di dokumen pertama
            // (penunjuknya null) atau versi yang sudah dimusnahkan (tak ada
            // di `$rantai`).
            $akar = $dok;
            for ($versi = $dok; $versi; $versi = $rantai->get($versi->revises_document_id)) {
                $akar = $versi;
            }

            $baris[] = [
                'dok' => $dok,
                'peta' => self::petaDariCatatanRevisi($dok),
                'efektif' => $akar->published_at,
            ];

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
            $kolom[$edisi] = range(1, $edisi < $maxEdisi
                ? \App\Services\DocumentService::MAKS_REVISI
                : max(1, $maxRevisi));
        }

        return [$baris, $kolom];
    }

    /**
     * Bagi baris `catatan_revisi` dokumen ke dalam (edisi, revisi) → tanggal,
     * mengenali batas edisi dari pola naik-lalu-turun pada `no_rev` (lihat
     * docblock `matriks()`). Baris tanpa `no_rev` sah (≥1) atau tanpa tanggal
     * diabaikan — bukan galat, cukup tak tercetak.
     *
     * @return array<int,array<int,\Illuminate\Support\Carbon>>
     */
    private static function petaDariCatatanRevisi(Document $dok): array
    {
        $peta = [];
        $edisi = 1;
        $noRevSebelumnya = null;

        foreach ($dok->contentMap()['catatan_revisi'] ?? [] as $baris) {
            if (! is_array($baris)) {
                continue;
            }

            $noRev = (int) ($baris['no_rev'] ?? 0);
            $tanggal = trim((string) ($baris['tanggal'] ?? ''));

            if ($noRev < 1 || $tanggal === '') {
                continue;
            }

            if ($noRevSebelumnya !== null && $noRev < $noRevSebelumnya) {
                $edisi++;
            }

            try {
                $peta[$edisi][$noRev] = \Illuminate\Support\Carbon::parse($tanggal);
            } catch (\Throwable) {
                // Tanggal tak valid (data lama yang diketik bebas) → lewati
                // baris ini saja, jangan gagalkan seluruh ekspor.
            }

            $noRevSebelumnya = $noRev;
        }

        return $peta;
    }
}
