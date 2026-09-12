{{-- HALAMAN COVER (SOP/IK/SP) — menggantikan halaman pengesahan di ekor dokumen.

     Tata letaknya DIUKUR dari docs/Cover_Depan.docx (word/document.xml +
     word/styles.xml), bukan dikira-kira. Angka pentingnya:

       bingkai halaman  w:pgBorders single 3pt, 24pt dari tepi kertas
       logo             vertikal 3cm → 6.5cm (permintaan pemilik), di tengah
       kolom teks       w:ind left=2223tw right=2338tw → lebar 303.6pt, di tengah
       nama perusahaan  Arial bold 16pt   (w:sz 32)
       jenis dokumen    Arial bold 16pt   (baris kedua paragraf yang sama)
       judul dokumen    Arial bold 22pt   (style Title, w:sz 44)
       spasi baris      1.5, spacing 0cm  (permintaan pemilik)
       kotak nomor      247.5pt × 36.5pt, di tengah, BORDER GANDA
       kotak pengesahan 423.35pt lebar, di tengah; jarak antar baris 118.5pt
       kolom di dalamnya label x=+41pt, nilai x=+167.6pt dari tepi kiri kotak

     TIGA elemen TIDAK digambar di sini melainkan oleh canvas DomPDF
     (App\Services\Print\PdfRenderer::catCoverHalamanSatu): bingkai halaman,
     logo, dan penghapus kop. Sebabnya: kop dokumen dipasang `position: fixed`
     supaya berulang di tiap halaman, dan DomPDF TIDAK punya cara mematikannya
     untuk satu halaman saja — kop akan menimpa cover. Canvas menggambar SESUDAH
     isi halaman, jadi hanya di situlah kop bisa ditutup, dan karena kop ditutup
     maka logo (yang berada di pita yang sama, 3–6.5cm) harus ikut digambar di
     situ pula. Alir HTML di bawah baru mulai di 184.25pt — persis di bawah logo.

     Gaya ditulis INLINE mengikuti partial cetak lain (mis. _catatan_revisi) —
     sekaligus kebal masalah spesifisitas CSS yang pernah membuat font kop tak
     pernah membesar (HANDOVER §7). --}}
@php
    $raw = $schema->raw();

    // Kolom JABATAN memuat jabatan + DEPARTEMEN, mis. "Group Leader (ICTMD)"
    // (permintaan pemilik). PJO tanpa departemen → cukup "PJO".
    $jabatanLabel = fn ($user) => $user?->jabatanPengesahan() ?? '-';

    // Sudah pernah Berlaku (disetujui) → SELURUH cap terpasang. Memakai
    // published_at agar dokumen "Sedang Direvisi"/obsolete yang dulu disahkan
    // tetap menampilkan capnya.
    $published = $document->published_at !== null;

    $reviewDate = optional($document->reviews->firstWhere('decision', 'approved'))->updated_at?->format('d/m/Y') ?? '-';
    $pubDate = $document->published_at?->format('d/m/Y') ?? '-';

    // Dokumen arsip yang peninjau/penyetujunya diisi manual lewat saklar Admin
    // "gabung PDF" (ArsipPenggabung) tak pernah melewati alur tinjau
    // sungguhan: tak ada baris `reviews`, dan `published_at` cuma tanggal
    // efektif yang diketik di formulir unggah, bukan tanggal ia benar-benar
    // ditinjau/disetujui. Satu-satunya tanggal yang jujur untuk keduanya
    // adalah tanggal dokumen ini didaftarkan (`created_at`) — dan itu hanya
    // berlaku bila peninjau/penyetujunya memang terisi, supaya arsip LAMA yang
    // belum pernah mengisi keduanya (reviewer_id/approver_id kosong) tetap
    // mencetak apa adanya seperti sebelum saklar ini ada.
    $tanggalDibuatArsip = $document->created_at?->format('d/m/Y') ?? '-';
    $arsipTanpaSalin = $document->isArsip() && ! $document->salin_arsip_at;

    // Salinan arsip (retype-di-wizard) tidak melewati review. SH/DH
    // menandatangani ketika salinan diresmikan (`submitted_at`), sementara
    // `published_at` tetap menyimpan tanggal efektif dari formulir unggah arsip.
    $approvalDate = $document->salin_arsip_at
        ? ($document->submitted_at?->format('d/m/Y') ?? $pubDate)
        : ($arsipTanpaSalin && $document->approver_id ? $tanggalDibuatArsip : $pubDate);
    $reviewOrApprovalDate = $document->salin_arsip_at
        ? $approvalDate
        : ($arsipTanpaSalin && $document->reviewer_id ? $tanggalDibuatArsip : $reviewDate);

    // Peta peran → [penandatangan, tanggal]. "peninjau_penyetuju" (SP/IK) = satu
    // SH/DH; tanggalnya = tgl terbit (persetujuan final), fallback tgl tinjau.
    $resolveRole = function ($role) use ($document, $reviewOrApprovalDate, $approvalDate, $pubDate) {
        return match ($role) {
            'pembuat' => [$document->creator, $document->created_at?->format('d/m/Y') ?? '-'],
            'peninjau' => [$document->reviewer, $reviewOrApprovalDate],
            'penyetuju' => [$document->approver, $approvalDate],
            'peninjau_penyetuju' => [$document->reviewer, $approvalDate !== '-' ? $approvalDate : $reviewOrApprovalDate],
            default => [null, '-'],
        };
    };

    $layoutRows = ($raw['approval_page_layout']['rows'] ?? null)
        ?? [
            ['role_label' => 'Dibuat Oleh', 'role' => 'pembuat'],
            ['role_label' => 'Ditinjau Oleh', 'role' => 'peninjau'],
            ['role_label' => 'Disetujui Oleh', 'role' => 'penyetuju'],
        ];

    // Pembuat TAMBAHAN (opsional) tampil sbg baris "Dibuat Oleh" ekstra TEPAT DI
    // BAWAH pembuat utama. Tanggalnya = tanggal dokumen dibuat.
    $extraAuthors = $document->authors()->where('is_primary', false)
        ->with('user.department')->get()->pluck('user')->filter()->values();

    $rows = [];
    foreach ($layoutRows as $r) {
        $role = $r['role'] ?? '';
        $label = $r['role_label'] ?? '';
        [$signer, $date] = $resolveRole($role);
        $rows[] = ['label' => $label, 'user' => $signer, 'date' => $date];

        if ($role === 'pembuat') {
            foreach ($extraAuthors as $extra) {
                $rows[] = ['label' => $label, 'user' => $extra, 'date' => $date];
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PETA TATA LETAK VERTIKAL (pt dari tepi atas kertas A4 = 841.89pt)
    |--------------------------------------------------------------------------
    |
    | Diturunkan dari jarak antar-komponen di docs/Cover_Depan.docx:
    |
    |   bingkai halaman   24 .. 818        (w:pgBorders 3pt, jarak 24pt)
    |   logo              42.75 .. 161.25  (digambar canvas, lihat PdfRenderer)
    |   blok judul        165.25 ..        (jarak logo→judul 4pt)
    |     PT PPA 16pt/24 · jenis 16pt/24 · judul 22pt/33 per baris
    |   ↓ 22pt
    |   kotak nomor       ±35pt tinggi
    |   ↓ 25pt            ← "2× enter" (docx: 25.3pt)
    |   kotak pengesahan  .. 779           tepi BAWAH dipatok
    |
    | Tepi ATAS kotak pengesahan karena itu BERGERAK mengikuti panjang judul,
    | sementara tepi bawahnya tetap — persis yang diminta pemilik. Judul satu
    | baris → kotak mulai 353pt (tinggi 426); judul dua baris → 386pt (393).
    | Keduanya jauh lebih tinggi dari versi sebelumnya (337pt).
    |
    | $coverJudulBaris DIUKUR FontMetrics di PdfRenderer, bukan ditaksir dari
    | jumlah huruf: kalau meleset, isi cover menabrak kotak pengesahan.
    */
    $judulBaris = $coverJudulBaris ?? 1;

    $padAtas = 3.25;                                        // 162 (tepi kotak konten) → 165.25
    $tinggiSpacer = 24.0 + 24.0 + 33.0 * $judulBaris        // PT PPA + jenis + judul
        + 22.0                                              // jarak judul → kotak nomor
        + 35.0                                              // kotak nomor
        + 25.0;                                             // 2× enter → kotak pengesahan
    $atasKotak = 162.0 + $padAtas + $tinggiSpacer;
    $tinggiKotak = 779.0 - $atasKotak;

    /*
    | Jarak antar baris pengesahan = tinggi kotak dibagi rata. Isi tiap baris
    | ±76pt (cap 48 + nama 14 + jabatan 14), sisanya dibagi dua di atas dan di
    | bawah lewat $pad sehingga tiap baris RATA TENGAH dalam pitanya sendiri —
    | dulu isinya menempel ke bawah dan menyisakan rongga di atas.
    |
    | ponytail: cap mengerut sampai lantai 20pt. Di atas ±7 baris pengesahan
    | kotaknya sesak — perlu dipecah dua kolom.
    */
    $pitch = $tinggiKotak / max(1, count($rows));
    $stampH = max(20.0, min(48.0, $pitch - 30));

    $baris = 14.0;                                          // tinggi baris nama & jabatan
    $pad = max(0.0, ($pitch - ($stampH + 2 * $baris)) / 2);

@endphp

<div style="page-break-after: always">

    {{-- Blok judul + kotak nomor dikurung dalam ruang bertinggi tertentu; ruang
         itulah yang menentukan di mana tepi ATAS kotak pengesahan jatuh (lihat
         peta tata letak di blok @php di atas). Tingginya IKUT panjang judul,
         sehingga jarak "2× enter" ke kotak pengesahan selalu tepat 25pt entah
         judulnya satu baris atau dua.

         Sengaja BUKAN `position: absolute`: dicoba, dan DomPDF justru MEMBUANG
         seluruh halaman covernya. Alir biasa dengan tinggi terhitung sama
         pastinya dan tak menyentuh mesin paginasi.

         padding-top, BUKAN margin-top pada blok judul di dalamnya: margin anak
         pertama LURUH keluar dari induknya sehingga justru mendorong ruang ini
         turun dan tepi atas kotak pengesahan meleset (terukur 481.7pt untuk
         target 447pt). Padding tidak pernah luruh. --}}
    <div style="height:{{ $tinggiSpacer }}pt; padding-top:{{ $padAtas }}pt">

    {{-- Blok judul: nama perusahaan & jenis 16pt, judul dokumen 22pt, spasi 1.5.
         Jaraknya ke logo cuma 4pt (dulu 12.55pt) — permintaan pemilik, dan
         ruang yang dihemat dipakai membesarkan logo. --}}
    <div style="width:{{ $coverJudulLebar ?? 500.0 }}pt; margin:0 auto; text-align:center">
        <div style="font-size:16pt; font-weight:bold; line-height:24pt">PT PUTRA PERKASA ABADI</div>
        <div style="font-size:16pt; font-weight:bold; line-height:24pt">{{ $raw['doc_type_label'] ?? strtoupper($document->type->name) }}</div>
        <div style="font-size:22pt; font-weight:bold; line-height:33pt">{{ strtoupper($document->title) }}</div>
    </div>

    {{-- Kotak No. Dokumen — BORDER GANDA seperti docx (dua persegi bersarang,
         celah 5.4pt). Lebar LUAR 247.5pt: DomPDF memakai content-box, jadi
         `width` harus sudah dikurangi padding & border sendiri
         (247.5 − 2×5.4 padding − 2×1 border = 234.7). --}}
    <div style="width:234.7pt; margin:22pt auto 0; border:1pt solid #000; padding:5.4pt">
        <div style="border:1pt solid #000; text-align:center; font-size:16pt; font-weight:bold; line-height:20.7pt">
            {{ $document->displayNumber() }}
        </div>
    </div>

    </div>{{-- akhir ruang bertinggi tetap --}}

    {{-- Kotak pengesahan — lebar 423.35pt di tengah, persis docx.

         Bingkai ditaruh di DIV pembungkus, bukan di tabel: border tabel +
         border-collapse di DomPDF gampang meleset, sedangkan div selalu tepat. --}}
    <div style="width:423.35pt; margin:0 auto; border:1pt solid #000">
        {{-- Tiap baris meniru PERSIS blok paraf di referensi — bedanya tanda
             tangan basah diganti cap APPROVE:

                 Dibuat Oleh          [cap APPROVE, miring]
                                 :  ‾‾‾‾‾NAMA‾‾‾‾‾   Tgl : 29/03/2026
                                       Jabatan (DEPT)

             Empat kolom, lebarnya hasil ukur gambar referensi (relatif thd tepi
             kiri kotak): label di +41, ":" di +158, garis nama +167, tanggal
             +265. Nama & Tgl SEBARIS; jabatan di baris bawahnya, di bawah nama.

             Bentuk ini sekaligus menyembuhkan luapan ke halaman 2: susunan lama
             menumpuk nama/tgl/jabatan jadi 3 baris sehingga tinggi isi ±93pt
             melampaui jarak baris 82.5pt (terukur). Sekarang 2 baris, ±76pt. --}}
        <table style="width:100%; border-collapse:collapse; table-layout:fixed">
            @foreach ($rows as $row)
                <tr>
                    {{-- padding-bottom = $pad + $baris. $pad (separuh sisa ruang)
                         mengangkat isi agar RATA TENGAH atas-bawah dalam pitanya
                         — dulu isinya menempel ke bawah dan menyisakan rongga di
                         atas. $baris menyejajarkan sel ini dengan NAMA, bukan
                         dengan jabatan, karena semuanya rata bawah. --}}
                    {{-- Lebar kolom dalam PERSEN, bukan pt: dengan pt DomPDF
                         mengabaikannya dan menyusutkan tiap kolom ke lebar
                         kontennya (terukur ":" mendarat di +105.8pt padahal
                         dipatok +158pt), sehingga nama & label ikut membungkus.
                         Persen dari 423.35pt → 37.3% / 2.1% / 33.1% / 27.5%.
                         `nowrap` di label & tanggal: keduanya tak pernah boleh
                         patah dua ("Ditinjau" / "Oleh"). --}}
                    <td style="width:37.3%; padding:0 0 {{ $pad + $baris }}pt 41pt; vertical-align:bottom; font-size:11pt; font-weight:bold; white-space:nowrap">
                        {{ $row['label'] }}
                    </td>
                    <td style="width:2.1%; padding:0 0 {{ $pad + $baris }}pt 0; vertical-align:bottom; font-size:11pt; font-weight:bold">:</td>

                    {{-- Kolom nama: cap di atas, GARIS NAMA, jabatan di bawahnya.
                         Tinggi baris dipasang di sel INI karena ia satu-satunya
                         yang tak berpadding vertikal — DomPDF memakai content-box,
                         jadi `height` di sel berpadding akan DITAMBAH padding-nya
                         (terukur: baris jadi 129.7pt untuk pitch 96.7pt, dan
                         kotaknya terbelah ke halaman 2).
                         Lebar 140pt, bukan 91pt spt referensi: nama Indonesia
                         jauh lebih panjang dari "Irwan A. Putra" — 91pt membuat
                         "ANGGA MARGI SAPUTRO" membungkus. --}}
                    <td style="width:33.1%; padding:0 0 {{ $pad }}pt 0; vertical-align:bottom; text-align:center; font-size:11pt; font-weight:bold; height:{{ $pitch - $pad }}pt">
                        {{-- Cap APPROVE menggantikan tanda tangan, DI TEMPAT YANG
                             SAMA: tepat di atas garis nama. Berkas stempel TIDAK
                             diproses — ketetapan pemilik (HANDOVER §6). Penanda
                             teks 1pt menjaga cap tetap terbaca test dari stream. --}}
                        <div style="height:{{ $stampH }}pt">
                            @if ($published && ($stamp ?? null))
                                <img src="{{ $stamp }}" alt="APPROVED" style="height:{{ $stampH }}pt"><span style="font-size:1pt; color:#fff">APPROVED</span>
                            @endif
                        </div>
                        {{-- border-bottom, bukan text-decoration: garisnya jadi
                             selebar kolom seperti garis tanda tangan di referensi,
                             bukan sepanjang namanya. --}}
                        <div style="border-bottom:0.75pt solid #000; line-height:{{ $baris }}pt">{{ strtoupper($row['user']->name ?? '-') }}</div>
                        <div style="line-height:{{ $baris }}pt">{{ $jabatanLabel($row['user']) }}</div>
                    </td>

                    <td style="width:27.5%; padding:0 0 {{ $pad + $baris }}pt 8pt; vertical-align:bottom; font-size:11pt; font-weight:bold; white-space:nowrap">
                        Tgl : {{ $row['date'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
