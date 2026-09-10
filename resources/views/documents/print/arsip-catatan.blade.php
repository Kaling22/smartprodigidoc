<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    {{-- Lembar CATATAN REVISI BERDIRI SENDIRI — disisipkan di depan berkas
         dokumen LAMA (PLAN-REVISI-v6 Fase H), lalu digabung FPDI
         (App\Services\Print\ArsipPenggabung).

         Berbeda dari documents/print/render.blade.php yang mencetak SELURUH
         dokumen: di sini isinya memang cuma satu lembar, karena sisa halamannya
         datang dari berkas pindaian yang tak pernah lewat DomPDF. Gaya yang
         disalin ke bawah SENGAJA hanya yang menyangkut KOP — geometrinya (margin
         @page, top kop-fixed, tinggi baris) sudah terkalibrasi terhadap stamp
         "Halaman: X dari Y" di PdfRenderer, jadi keduanya wajib sama persis.
         Jangan diutak-atik tanpa mengukur ulang (CLAUDE.md §7). --}}
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

        /* Partial-nya memasang `page-break-after: always` karena di dokumen web
           masih ada isi di belakangnya. Di sini tak ada — dan tanpa penawar ini
           DomPDF menutup berkas dengan satu HALAMAN KOSONG yang ikut terbawa ke
           hasil gabung. */
        .catatan-revisi { page-break-after: auto !important; }
    </style>
</head>
<body>
    <div class="kop-fixed">@include('documents.print._kop')</div>

    {{-- Partial yang SAMA dengan lembar revisi dokumen web — satu bentuk lembar
         CATATAN REVISI untuk kedua jalur, jadi dokumen lama dan dokumen SmartPro
         tak pernah mencetak tabel yang berbeda. --}}
    @include('documents.print._catatan_revisi')
</body>
</html>
