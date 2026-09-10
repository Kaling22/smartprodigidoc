<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        /* Tipografi selaras referensi (template_sop): lebih lega & terbaca —
           line-height 1.5, body sedikit diperbesar (8→9pt). Kop tetap seperti
           kalibrasi cm/16pt (konfigurasi inti, tak diubah). */
        /* Helvetica DULU, bukan Arial. Word memakai Arial, tapi DomPDF tak punya
           Arial dan diam-diam menggantinya dgn DejaVu Sans — yang jauh lebih
           TEBAL & LEBAR, sehingga cetakan tampak "terlalu bold" dibanding docx.
           Helvetica Base-14 sudah ada di dompdf dan metriknya IDENTIK dgn Arial
           (Arial memang dirancang sbg pengganti metrik Helvetica), jadi
           pembungkusan barisnya sama dgn Word tanpa menanam berkas font apa pun.
           "DejaVu Sans" tetap di belakang sbg cadangan PER-GLYPH: dompdf memetakan
           tiap huruf ke font pertama yang memuatnya (FontMetrics::mapTextToFonts),
           jadi karakter di luar Latin-1 — kutip lengkung/en-dash hasil tempel dari
           Word — tetap tercetak benar. */
        body { font-family: Helvetica, "DejaVu Sans", sans-serif; font-size: 9pt; line-height: 1.5; color: #000; margin: 0; }

        /* Kop BERULANG tiap halaman via position:fixed (BUKAN thead page-frame):
           dgn page-frame, tabel isi jadi bersarang ganda -> DomPDF tak bisa
           memecahnya antar halaman (aktivitas panjang meluber ke dasar / terpotong).
           Kop fixed di area margin atas; SEMUA tabel isi jadi top-level -> mengalir
           & mengisi halaman, hormati margin bawah 2cm (57pt). */
        @page { margin: 162pt 28pt 57pt 28pt; }
        .kop-fixed { position: fixed; top: -146pt; left: 0; right: 0; }
        .page-footer { margin-top: 8pt; }
        .doc-footer { font-size: 7pt; color: #2a5bd7; font-style: italic; }

        /* Kop: lebar kolom 4cm/8cm/6cm; JENIS & JUDUL 2x (jenis 11.5->23, judul
           9->18); ikon mengisi kolom 4cm. */
        table.kop { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.kop td { border: 0.75pt solid #000; padding: 2pt 5pt; font-size: 8pt; vertical-align: middle; line-height: 1.2; }
        .kop-logo { text-align: center; vertical-align: middle; padding: 2pt; }
        .kop-logo img { width: 108pt; height: 108pt; display: inline-block; vertical-align: middle; }   /* ikon mengisi kolom 4cm (2x-an) */
        .kop-logo-ph { font-weight: bold; font-size: 22pt; color: #c00; }
        /* PENTING — pakai td.xxx, BUKAN .xxx: `table.kop td` (spesifisitas 0,1,2)
           MENGALAHKAN `.kop-title` (0,1,0), jadi font-size di kelas polos TIDAK
           PERNAH terpakai (tetap 8pt). Ini sebab kop tak kunjung membesar. */
        table.kop td.kop-title { text-align: center; font-weight: bold; font-size: 14pt; line-height: 1.12; }      /* JENIS dokumen */
        table.kop td.kop-subject { text-align: center; font-weight: bold; font-size: 12pt; line-height: 1.15; }    /* JUDUL dokumen */
        /* nowrap: sufiks "(sementara)" tak boleh membungkus baris -> tinggi kop
           draft = approved (posisi stamp Halaman stabil). */
        .kop-meta { font-size: 8pt; height: 18pt; white-space: nowrap; }

        /* Bilah judul bab & kotak isi — padding lega spt referensi (5px/10px). */
        /* JUDUL BAB TAK PERNAH YATIM: bar bab wajib serumah dgn 2 poin pertamanya.
           Dulu bab spt "IV. DEFINISI" bisa tertinggal SENDIRIAN di dasar halaman
           (kotak isinya kosong) sementara poin 4.1 dst. lompat ke halaman berikut.
           Tiga aturan saling melengkapi — DomPDF akan MUNDUR mencari titik potong
           yang diizinkan, dan satu-satunya yang tersisa adalah SEBELUM bar bab,
           sehingga seluruh bab pindah utuh ke halaman berikutnya:
             (a) .section-bar page-break-after: avoid → tak boleh putus antara bar
                 bab dan kotak isinya;
             (b) table.list/akt page-break-before: avoid → tak boleh putus antara
                 kotak isi dan tabel di dalamnya (INI penyebab kotak kosong dulu:
                 padding atas kotak dianggap "celah" sehingga potong diizinkan);
             (c) tr.keep-next page-break-after: avoid → poin PERTAMA tak boleh
                 terpisah dari poin KEDUA.
           Bila sisa halaman masih muat bar + 2 poin, tak ada yang berubah. */
        .section-bar { background: #EDEDED; border: 0.75pt solid #000; font-weight: bold; padding: 4pt 7pt; font-size: 9pt; margin-top: 8pt; page-break-after: avoid; }
        .section-box { border: 0.75pt solid #000; border-top: none; padding: 7pt 8pt; }
        table.list, table.akt { page-break-before: avoid; }
        /* Header tabel akt ikut "menempel" ke baris pertamanya (tanpa ini, potong
           tepat SESUDAH thead masih diizinkan → bar + header saja yg tertinggal). */
        table.list tr.keep-next, table.akt thead tr, table.akt tr.keep-next { page-break-after: avoid; }

        /* Body teks justified + HANGING INDENT lewat kolom nomor terpisah: baris
           lanjutan otomatis sejajar awal teks setelah nomor (bukan nomornya).
           Kolom nomor SEMPIT (jarak dari bullet kecil, #5). */
        table.list { width: 100%; border-collapse: collapse; }
        table.list td { padding: 2.5pt 4pt; vertical-align: top; font-size: 9pt; text-align: justify; line-height: 1.5; }
        table.list td.num { width: 26pt; white-space: nowrap; text-align: left; padding-right: 4pt; }   /* kolom nomor lega (referensi 35px) */
        table.list tr { page-break-inside: avoid; }

        /* AKTIVITAS: 2 kolom (AKTIVITAS | PIC) spt referensi. Nomor "5.x" DIGABUNG
           di sel AKTIVITAS (bukan kolom sendiri) sbg <span> inline-block lebar
           tetap → hanging indent PERSIS: judul & deskripsi sejajar setelah nomor.
           Deskripsi dipecah per-paragraf → baris kecil yg mengalir mengisi halaman. */
        table.akt { width: 100%; border-collapse: collapse; table-layout: fixed; }
        /* word-break: kata panjang tanpa spasi tak boleh meluber menembus kolom. */
        table.akt th, table.akt td { border: 0.75pt solid #000; padding: 6pt 6pt; font-size: 9pt; vertical-align: top; line-height: 1.5; overflow-wrap: anywhere; }
        table.akt th { background: #EDEDED; text-align: center; vertical-align: middle; }
        /* HANGING INDENT tanpa text-indent: DomPDF menerapkan text-indent negatif
           DUA KALI (terukur: nomor meleset 40pt utk -20pt) sehingga nomor keluar
           kotak. Pakai margin-left negatif pada span nomor: sel diberi padding
           25pt, nomor ditarik 20pt → nomor di 5pt (DALAM kotak), teks & baris
           lanjutan sama-sama mulai di 25pt. */
        /* Deskripsi = banyak baris tabel kecil (mengalir antar-halaman → aktivitas
           panjang tak meluber margin bawah). Padding VERTIKAL kecil (2pt) di antara
           baris satu grup → sub-judul & deskripsi MENEMPEL (tak ada celah baris
           kosong, #3); baris pertama/terakhir grup diberi 6pt agar tetap berjarak
           dari garis atas/bawah. */
        /* padding-left 46pt & .jn width 34pt: menyamakan indentasi AKTIVITAS (sub-bab
           5) dgn daftar sub-bab 1–6 (nomor x≈40.8pt, teks x≈74.8pt — terukur dari
           stream). Sebelumnya akt lebih rapat (teks 53.8pt) → tampak beda (#8). */
        table.akt td.aktcell, table.akt td.aktcont { text-align: justify; padding-left: 46pt; padding-top: 2pt; padding-bottom: 2pt; }
        table.akt td.mrg-top { padding-top: 6pt; }      /* baris pertama grup: napas atas */
        table.akt td.mrg-bot { padding-bottom: 6pt; }   /* baris terakhir grup: napas bawah */
        /* vertical-align:top — baseline inline-block di DomPDF membuat nomor
           mengambang 2.7pt DI ATAS judulnya (terukur). Dgn top, kotak nomor rata
           atas dgn baris → baseline nomor & judul PERSIS sejajar. */
        /* vertical-align dikalibrasi ULANG untuk Helvetica (DejaVu dulu -3.3pt).
           Dua kali pengukuran stream, dan DomPDF ternyata menggeser DUA KALI
           lipat nilai yang ditulis — gejala yg sama dgn text-indent negatif
           (HANDOVER §7):
             -3.3pt → nomor y=419.295 (1.19pt DI BAWAH judul y=420.484)
             -2.1pt → nomor y=421.695 (1.21pt DI ATAS judul)
           selisih vertical-align 1.2pt menggeser nomor 2.4pt → rasio 2:1,
           jadi koreksi yang dibutuhkan = 1.19/2 ≈ 0.6 → -3.3 + 0.6 = -2.7pt. */
        table.akt td.aktcell .jn { display: inline-block; width: 34pt; margin-left: -34pt; font-weight: bold; vertical-align: -2.7pt; line-height: 1.5; }
        /* Simulasi sel gabung kolom AKTIVITAS: hilangkan garis antar-baris satu grup. */
        table.akt td.mrg-top { border-bottom: none; }
        table.akt td.mrg-mid { border-top: none; border-bottom: none; }
        table.akt td.mrg-bot { border-top: none; }
        /* PIC = SATU sel rowspan menutupi seluruh grup → vertical-align:middle
           membuatnya RATA TENGAH atas-bawah apa pun panjang deskripsi (#3). */
        table.akt td.pic { text-align: center; vertical-align: middle; }
        table.akt tbody tr { page-break-inside: avoid; }

        /* Pengesahan SELALU di halaman tersendiri (permintaan #7): jangan digabung
           dgn ekor isi. page-break-before memaksa mulai halaman baru. */
        .pengesahan-page { page-break-before: always; }
        table.pengesahan { width: 100%; border-collapse: collapse; }
        table.pengesahan th, table.pengesahan td { border: 0.75pt solid #000; padding: 6pt 6pt; font-size: 9pt; }
        table.pengesahan th { background: #EDEDED; text-align: center; }
        table.pengesahan tr { page-break-inside: avoid; }
        .center { text-align: center; }
        .peng-role { font-size: 7pt; color: #444; }
        .stamp { display: inline-block; border: 1.5pt solid #1a7f37; color: #1a7f37; font-weight: bold; padding: 2pt 9pt; font-size: 10pt; letter-spacing: 1px; }

        .lampiran-judul { font-weight: bold; }
        .lampiran-img { max-width: 240pt; max-height: 190pt; margin-top: 3pt; page-break-inside: avoid; }
        /* Bab yang berdiri sendiri sehalaman (FLOWCHART) — gambarnya boleh
           memakai hampir seluruh halaman: bagan alur yang diperkecil ke 240pt
           tak terbaca. 500pt menyisakan ruang untuk bilah bab & keterangan. */
        .flow-img { max-width: 100%; max-height: 500pt; margin-top: 3pt; page-break-inside: avoid; }
        /* Gambar SISIPAN di dalam sel AKTIVITAS.

           vertical-align:top BUKAN hiasan — tanpanya gambar MENINDIH baris di
           atasnya. Gambar sebaris di DomPDF duduk pada BASELINE dan memanjang
           KE ATAS; tinggi barisnya sudah dihitung benar (177pt untuk foto
           169pt), tapi gambarnya digambar 34pt terlalu tinggi. Terukur dari
           stream, daftar 6 butir dgn foto di butir ke-4:
             tanpa     → puncak foto y=361.6, sedangkan butir "3." y=336.0 → TERTIMPA
             top       → puncak foto y=327.3, di bawah "3." → bersih
           `display:block` sempat dicoba dan TAK berpengaruh sedikit pun di
           DomPDF (y gambar sama persis) — jangan diulang.
           Dikunci RichTextAktivitasTest::test_foto_dalam_daftar_tak_menindih_butir_di_atasnya.

           Batas tinggi 220pt bukan selera:
           DomPDF tak bisa memotong satu gambar antar-halaman, jadi gambar yang
           lebih tinggi dari sisa halaman akan mendorong seluruh barisnya dan
           meninggalkan halaman setengah kosong. Bagan selebar halaman tempatnya
           di bab FLOWCHART (.flow-img, 500pt), bukan di dalam tabel ini. */
        .akt-img { vertical-align: top; max-width: 100%; max-height: 220pt; margin-top: 2pt; page-break-inside: avoid; }
        /* Penanda butir daftar ("1." / bulir) — dicetak sebagai TEKS, karena
           <ol>/<ul> di dalam satu <tr> page-break-inside:avoid tak bisa dipecah
           antar-halaman. Polanya MENIRU .jn yang sudah terkalibrasi: hanging
           indent lewat margin-left negatif, BUKAN text-indent (DomPDF menerapkan
           text-indent negatif dua kali lipat — HANDOVER §7). Hasilnya baris
           lanjutan sebuah butir sejajar dgn teksnya, bukan dgn bulirnya.
           vertical-align -2.7pt DIUKUR dari stream, bukan disalin dari .jn:
           tanpa itu kotak inline-block duduk 2.747pt DI ATAS baris teksnya
             0pt    → nomor y=374.258 vs teks y=371.511 (2.747pt di atas)
             -2.7pt → nomor y=371.522 vs teks y=371.511 (selisih 0.011pt)
           Dikunci PrintLayoutTest::test_rich_text_activity_prints_with_hanging_markers. */
        table.akt td.aktcont .mk { display: inline-block; width: 16pt; margin-left: -16pt; vertical-align: -2.7pt; }
        /* KUTIPAN (tombol " di editor) — menjorok satu langkah 16pt yang sama
           dgn penanda butir, plus miring. Batang abu-abu ala editor SENGAJA
           tak ditiru: border-left di sel ini akan menempel pada tepi KIRI
           tabel dan terbaca sebagai garis tabel, bukan sebagai tanda kutipan;
           sedangkan inline-block berbatang tak bisa ikut meninggi mengikuti
           teks yang membungkus. Menjorok + miring adalah cara kutipan memang
           dicetak di dokumen mutu, dan nol risiko kalibrasi. */
        table.akt td.aktquote { padding-left: 62pt; font-style: italic; }
    </style>
</head>
<body>
    {{-- HALAMAN COVER paling depan (menggantikan halaman pengesahan di ekor).
         Urutan berkas: Cover → Catatan Revisi → isi.

         Cover sengaja ditulis SEBELUM kop, dan itu benar untuk KEDUA moda:
           • cetak — kop `position: fixed`, jadi urutan DOM tak memengaruhi
             letaknya sama sekali (ia selalu di pita margin atas tiap halaman);
           • layar — kop `position: static`, jadi ia menyusul di bawah cover,
             persis seperti lembar cover lalu lembar isi.
         Bingkai, logo, dan penghapus kop halaman 1 digambar canvas — lihat
         PdfRenderer::catCoverHalamanSatu(). --}}
    @if (($schema->raw()['cover_page'] ?? null) === '_cover')
        @include('documents.print._cover')
    @endif

    {{-- Kop BERULANG tiap halaman (fixed); isi mengalir top-level. --}}
    <div class="kop-fixed">@include('documents.print._kop')</div>

    {{-- Lembar CATATAN REVISI di halaman DEPAN (hanya dokumen hasil revisi) --}}
    @if (! empty(array_filter((array) ($contentMap['catatan_revisi'] ?? []))))
        @include('documents.print._catatan_revisi')
    @endif

    @php
        /*
        | Satu baris deskripsi AKTIVITAS → HTML selnya. Tiga dialek hidup
        | berdampingan di kolom `deskripsi` (lihat ActivityPrintLayout::flatten):
        |
        |   kind 'gambar'  → sisipan foto; satu baris tabel tersendiri
        |   html  terisi   → potongan dari editor, SUDAH tersanitasi saat SIMPAN
        |                    (PembersihHtml) — karena itu dicetak tak-di-escape
        |   text  saja     → dokumen lama (teks polos), tetap di-escape
        |
        | Gambar ditanam base64 lewat $embed: DomPDF tak bisa memuat /storage/….
        | Berkas yang hilang menghasilkan sel kosong, BUKAN gambar rusak.
        |
        | Dipakai probe Fase 1 MAUPUN cetak akhir — tinggi yang diukur wajib
        | tinggi yang benar-benar dicetak, kalau tidak seluruh paginasi meleset.
        */
        $selAkt = function (array $r) use ($embed) {
            if (($r['kind'] ?? 'teks') === 'gambar') {
                $src = $embed($r['path'] ?? null);
                // Penanda butir (mis. "4.") bila fotonya MENGISI sebuah butir
                // daftar — lihat PembersihHtml::pecahInline(). Dicetak juga saat
                // berkasnya hilang, supaya nomor butir tak bolong.
                $mk = $r['html'] ?? '';

                return $src ? $mk.'<img class="akt-img" src="'.$src.'">' : $mk;
            }

            return $r['html'] ?? e($r['text'] ?? '');
        };
    @endphp

    @foreach ($schema->allSections() as $section)
        @php
            $type = $section['type'] ?? 'text';
            $val = $contentMap[$section['key']] ?? null;

            /*
            | Bab yang schema-nya menyatakan `page_break` berdiri SENDIRI di
            | halamannya (saat ini: FLOWCHART pada SOP). Bab tanpa kunci ini
            | tetap MENGALIR & menghabiskan halaman — pemilik menolak page-break
            | yang tak perlu, dan aturan "judul bab tak pernah yatim" di CSS
            | atas sudah menjaga bab yang tak muat berpindah utuh.
            |
            | `before` dipasang di bilah bab-nya sendiri; `after` berupa div
            | kosong sesudah bab, sebab bilah & kotak isi adalah dua elemen
            | bersaudara — tak ada satu pembungkus yang bisa memuat keduanya
            | tanpa membuat tabel isi bersarang (HANDOVER §7: tabel bersarang
            | ATOMIK, tak bisa dipecah antar halaman).
            */
            $pb = $section['page_break'] ?? '';

            /*
            | Bab berdiri-sendiri yang KOSONG tidak boleh menuntut halamannya.
            | FLOWCHART opsional (min_groups 0); tanpa penjagaan ini, SOP yang
            | tak memakai flowchart mencetak satu halaman penuh berisi bilah
            | "V. FLOWCHART" dan tak ada apa-apa lagi. Babnya tetap tercetak —
            | hanya saja ikut mengalir bersama bab sebelumnya.
            */
            if ($pb !== '' && empty(array_filter((array) $val, fn ($r) => filled(array_filter((array) $r))))) {
                $pb = '';
            }
            $pbBefore = in_array($pb, ['before', 'both'], true) ? ' style="page-break-before: always"' : '';
            $pbAfter = in_array($pb, ['after', 'both'], true);

            /*
            | Anchor 1pt putih penanda RENTANG HALAMAN bab — dibaca
            | PdfRenderer::petaHalamanBab() untuk mengisi kolom "Hal." lembar
            | CATATAN REVISI (butir 7b). Dua per bab: di bilah babnya dan di isi
            | TERAKHIRNYA, sehingga bab yang memakan beberapa halaman terbaca
            | sebagai rentang.
            |
            | Hanya menyala saat pengukuran (`anchorBab`) — berkas yang diunduh
            | pengguna tak pernah memuatnya. Keduanya ditanam INLINE di dalam
            | teks yang sudah ada, bukan sebagai elemen tersendiri: elemen baru
            | menambah tinggi, dan tinggi yang berubah berarti yang diukur bukan
            | lagi halaman yang sebenarnya.
            */
            $anc = ($anchorBab ?? false) ? '<span style="font-size:1pt;color:#fff">[[S'.(2 * $loop->index).']]</span>' : '';
            $ancAkhir = ($anchorBab ?? false) ? '<span style="font-size:1pt;color:#fff">[[S'.(2 * $loop->index + 1).']]</span>' : '';
        @endphp

        @if (in_array($type, ['rich_list', 'reference_picker']))
            <div class="section-bar"{!! $pbBefore !!}>{{ $section['label'] }}{!! $anc !!}</div>
            <div class="section-box">
                <table class="list">
                    @foreach ((is_array($val) ? $val : []) as $i => $item)
                        {{-- keep-next di poin PERTAMA: poin 1 & 2 tak boleh terpisah
                             halaman, sehingga judul bab selalu ditemani 2 poin. --}}
                        <tr @class(['keep-next' => $loop->first])><td class="num">{{ ($section['auto_number'] ?? '') }}{{ $i + 1 }}</td><td>{{ $item }}@if ($loop->last){!! $ancAkhir !!}@endif</td></tr>
                    @endforeach
                </table>
            </div>

        @elseif ($type === 'repeatable_group')
            @php $fields = collect($section['group_fields'] ?? $section['fields'] ?? []); $hasPic = $fields->contains('key', 'pic'); @endphp

            @if ($hasPic)
                <div class="section-bar"{!! $pbBefore !!}>{{ $section['label'] }}{!! $anc !!}</div>
                <table class="akt">
                    {{-- Lebar di TH (DomPDF mengabaikan colgroup): AKTIVITAS 75% | PIC 25%. --}}
                    <thead><tr><th style="width:75%">AKTIVITAS</th><th style="width:25%">PIC</th></tr></thead>
                    <tbody>
                        {{-- Baris ter-PAGINASI dari mesin 2-fase (DocumentController::
                             renderStandardPaginated → ActivityPrintLayout): rowspan PIC &
                             kelas border DI-SCOPE PER HALAMAN sehingga tepi tabel selalu
                             TERTUTUP di batas halaman & PIC tak hilang saat aktivitas
                             panjang terpotong. Bila TAK dikirim (preview layar/fallback) →
                             susun sekali jalan (satu segmen per grup). --}}
                        @php
                            $rowsAkt = ($aktRows[$section['key']] ?? null)
                                ?? \App\Services\ActivityPrintLayout::plan(
                                    \App\Services\ActivityPrintLayout::flatten(is_array($val) ? $val : [], $section['auto_number'] ?? '')
                                );
                        @endphp
                        @foreach ($rowsAkt as $ri => $r)
                            {{-- Anchor 1pt putih [[Ai]] (tak terlihat) di AKHIR sel → penanda
                                 pengukuran Fase 1. WAJIB di akhir: bila di depan, lebarnya
                                 (±2.9pt) menggeser BARIS PERTAMA ke kanan sehingga baris
                                 ke-2 dst. tak sejajar. --}}
                            @php $anchor = '<span style="font-size:1pt;color:#fff">[[A'.$ri.']]</span>'.($loop->last ? $ancAkhir : ''); @endphp
                            @if ($probeFlat ?? false)
                                {{-- PROBE DATAR (diukur lalu dibuang): tanpa rowspan & tanpa
                                     kelas border → DomPDF memaginasi bersih. --}}
                                <tr @class(['keep-next' => $loop->first])>
                                    <td class="{{ $r['isHead'] ? 'aktcell' : 'aktcont' }} {{ ($r['kind'] ?? '') === 'kutipan' ? 'aktquote' : '' }}" style="width:75%">@if($r['isHead'])<span class="jn">{{ $r['number'] }}</span><strong>{{ $r['text'] }}</strong>@else{!! $selAkt($r) !!}@endif{!! $anchor !!}</td>
                                    <td class="pic" style="width:25%">@if($r['isHead']){{ $r['pic'] }}@endif</td>
                                </tr>
                            @else
                                {{-- keep-next hanya di baris PERTAMA tabel (bukan tiap grup):
                                     menjaga bar bab V ditemani baris pertamanya. Titik potong
                                     antar-grup tetap ditentukan mesin paginasi 2-fase. --}}
                                <tr @class(['keep-next' => $loop->first]) @if($r['pageBreakBefore'] ?? false) style="page-break-before: always;" @endif>
                                    <td class="{{ $r['isHead'] ? 'aktcell' : 'aktcont' }} {{ $r['mrg'] }} {{ ($r['kind'] ?? '') === 'kutipan' ? 'aktquote' : '' }}" style="width:75%">@if($r['isHead'])<span class="jn">{{ $r['number'] }}</span><strong>{{ $r['text'] }}</strong>@else{!! $selAkt($r) !!}@endif{!! $anchor !!}</td>
                                    @if ($r['showPic'])<td class="pic" style="width:25%" rowspan="{{ $r['picRowspan'] }}">{{ $r['pic'] }}</td>@endif
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @else
                {{-- Grup non-PIC: FLOWCHART & LAMPIRAN. Satu cabang untuk keduanya
                     — susunannya sama (judul → gambar → keterangan), hanya isinya
                     yang berbeda:
                       flowchart  judul + gambar + keterangan
                       lampiran   dokumen rujukan + keterangan OPSIONAL
                     Judul diambil dari `judul` ATAU `dokumen`, mana yang ada.
                     Tiap item satu blok `page-break-inside: avoid` supaya gambar
                     tak pernah terpotong dua halaman. --}}
                <div class="section-bar"{!! $pbBefore !!}>{{ $section['label'] }}{!! $anc !!}</div>
                @foreach ((is_array($val) ? $val : []) as $i => $row)
                    @php
                        $judul = $row['judul'] ?? ($row['dokumen'] ?? '');
                        // `isi` = bentuk data LAMA (sebelum field dipisah); dulu ia
                        // bisa berisi teks ATAU path gambar. Dibaca apa adanya agar
                        // dokumen lama tetap tercetak benar.
                        $ket = $row['keterangan'] ?? ($row['isi'] ?? '');
                        $gambar = $row['gambar'] ?? (is_string($row['isi'] ?? '') && str_starts_with($row['isi'] ?? '', 'lampiran/') ? $row['isi'] : '');
                        // Bab yang berdiri sendiri sehalaman punya ruang jauh lebih
                        // lega, jadi gambarnya boleh besar — flowchart tak ada
                        // gunanya kalau kotak-kotaknya tak terbaca.
                        $imgClass = $pb !== '' ? 'flow-img' : 'lampiran-img';
                        // Anchor penutup bab menempel di item TERAKHIR (lihat $ancAkhir).
                        $ancBaris = $loop->last ? $ancAkhir : '';
                    @endphp
                    <div class="section-box" style="border-top:0.75pt solid #000; page-break-inside:avoid">
                        <table class="list"><tr>
                            <td class="num">{{ $i + 1 }}.</td>
                            <td>
                                @if ($judul !== '')<span class="lampiran-judul">{{ $judul }}</span>@endif
                                @if (is_string($gambar) && str_starts_with($gambar, 'lampiran/') && $embed($gambar))<br><img class="{{ $imgClass }}" src="{{ $embed($gambar) }}" alt="{{ $judul ?: 'lampiran' }}">@endif
                                @if (is_string($ket) && $ket !== '' && ! str_starts_with($ket, 'lampiran/'))<br>{{ $ket }}@endif{!! $ancBaris !!}
                            </td>
                        </tr></table>
                    </div>
                @endforeach
            @endif
        @endif

        {{-- Pemaksa halaman baru SESUDAH bab (schema `page_break: after|both`). --}}
        @if ($pbAfter)
            <div style="page-break-after: always"></div>
        @endif
    @endforeach

    {{-- Halaman pengesahan di EKOR hanya untuk jenis yang schema-nya BELUM
         menyatakan cover. Schema tersimpan di document_types.schema_json, jadi
         sebelum `php artisan db:seed --class=DocumentTypeSeeder` dijalankan,
         cabang inilah yang menjaga dokumen tetap punya lembar pengesahan. --}}
    @if (($schema->raw()['cover_page'] ?? null) !== '_cover')
        <div class="pengesahan-page">@include('documents.print._pengesahan')</div>
    @endif

    {{-- Footer: mengalir setelah isi → sekali saja, di halaman terakhir. --}}
    <div class="page-footer">@include('documents.print._footer')</div>
</body>
</html>
