<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    {{-- Lembar sisipan dokumen LAMA (rencana pra-produksi lanjutan, saklar
         Admin "gabung PDF"): Cover lalu Catatan Revisi, disisipkan di depan
         berkas dokumen LAMA lewat FPDI (App\Services\Print\ArsipPenggabung).

         Susunan & include SAMA PERSIS dengan documents/print/render.blade.php
         (Cover → kop-fixed → Catatan Revisi, baris 182-202) — dokumen lama dan
         dokumen SmartPro biasa tak pernah mencetak dua bentuk lembar yang
         berbeda. Bedanya dari render.blade.php: di sini isinya memang cuma
         DUA halaman, sebab sisa halamannya datang dari berkas pindaian yang
         tak pernah lewat DomPDF. Gaya kop disalin dari arsip-catatan.blade.php
         — geometrinya (margin @page, top kop-fixed) sudah terkalibrasi
         terhadap stamp "Halaman: X dari Y" di ArsipPenggabung::lembar(),
         jangan diutak-atik tanpa mengukur ulang (CLAUDE.md §7). --}}
    <style>
        * { box-sizing: border-box; }
        body { font-family: Helvetica, "DejaVu Sans", sans-serif; font-size: 9pt; line-height: 1.5; color: #000; margin: 0; }

        @page { margin: 162pt 28pt 57pt 28pt; }
        .kop-fixed { position: fixed; top: -146pt; left: 0; right: 0; }

        table.kop { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.kop td { border: 0.75pt solid #000; padding: 2pt 5pt; font-size: 8pt; vertical-align: middle; line-height: 1.2; }
        .kop-logo { text-align: center; vertical-align: middle; padding: 2pt; }
        .kop-logo img { width: 108pt; height: 108pt; display: inline-block; vertical-align: middle; }
        .kop-logo-ph { font-weight: bold; font-size: 22pt; color: #c00; }
        table.kop td.kop-title { text-align: center; font-weight: bold; font-size: 14pt; line-height: 1.12; }
        table.kop td.kop-subject { text-align: center; font-weight: bold; font-size: 12pt; line-height: 1.15; }
        .kop-meta { font-size: 8pt; height: 18pt; white-space: nowrap; }

        /* Sama seperti arsip-catatan.blade.php: tanpa penawar ini DomPDF
           menutup berkas dengan satu HALAMAN KOSONG yang ikut terbawa ke
           hasil gabung — di sini tak ada isi lagi sesudah lembar Catatan
           Revisi. */
        .catatan-revisi { page-break-after: auto !important; }
    </style>
</head>
<body>
    {{-- HALAMAN COVER paling depan. Ditulis SEBELUM kop, sama seperti
         render.blade.php: kop `position: fixed` sehingga urutan DOM tak
         memengaruhi letaknya sama sekali. Bingkai, logo, dan penghapus kop
         halaman 1 digambar canvas — lihat
         ArsipPenggabung::lembar()/PdfRenderer::catCoverHalamanSatu(). --}}
    @if (($schema->raw()['cover_page'] ?? null) === '_cover')
        @include('documents.print._cover')
    @endif

    <div class="kop-fixed">@include('documents.print._kop')</div>

    {{-- Lembar CATATAN REVISI — partial yang SAMA dengan wizard maupun
         arsip-catatan.blade.php lama, jadi bentuk cetaknya tak pernah
         menyimpang antar jalur. --}}
    @include('documents.print._catatan_revisi')
</body>
</html>
