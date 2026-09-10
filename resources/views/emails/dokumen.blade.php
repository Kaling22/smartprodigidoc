{{--
| Badan email pemberitahuan SmartPro.
|
| SATU berkas mandiri ber-CSS INLINE, bukan komponen markdown Laravel. Dua
| sebabnya:
|   1. Klien email (Outlook terutama) MEMBUANG <style> di <head>; gaya email
|      memang harus inline. Jadi tema CSS terpisah tak akan menolong.
|   2. Memakai komponen markdown berarti mem-publish ±13 berkas vendor/mail
|      hanya untuk mengubah pita atas. Satu berkas ini menggantikan semuanya.
|
| Tata letak memakai <table>, bukan flex/grid: itu satu-satunya yang dirender
| sama oleh Outlook, Gmail, dan aplikasi mobile.
|
| CATATAN PEMASANGAN: logo dan tombol memakai APP_URL. Kalau APP_URL masih
| `localhost`, keduanya MATI begitu email dibuka dari HP — isi dengan IP mesin
| di jaringan kantor.
--}}
@php
    $oranye = '#ea580c';
    $garis = '#e5e7eb';
    $redup = '#6b7280';

    // Logo versi kecil (±15 KB) yang sudah disinggahkan mesin cetak. Berkas
    // aslinya 279 KB — berat sekali untuk gambar yang ditampilkan 36 piksel,
    // dan diunduh ulang tiap kali email dibuka. Kembali ke aslinya bila
    // singgahan itu belum pernah dibuat (mis. pemasangan baru).
    $logo = is_file(public_path('images/logo-ppa-pdf.png'))
        ? 'images/logo-ppa-pdf.png'
        : 'images/logo-ppa.png';
@endphp
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f5f7;padding:24px 0;font-family:Arial,Helvetica,sans-serif">
<tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid {{ $garis }}">

        {{-- Pita atas: logo + nama sistem. --}}
        <tr><td style="background:{{ $oranye }};padding:18px 24px">
            <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                <td width="40" valign="middle">
                    <img src="{{ asset($logo) }}" width="36" height="36" alt="PPA"
                         style="display:block;border:0">
                </td>
                <td valign="middle" style="padding-left:12px">
                    <div style="color:#ffffff;font-size:16px;font-weight:bold;line-height:1.2">SmartPro</div>
                    <div style="color:#ffe8d9;font-size:11px;line-height:1.4">Dokumen Mutu PT Putra Perkasa Abadi</div>
                </td>
            </tr></table>
        </td></tr>

        <tr><td style="padding:24px">
            <p style="margin:0 0 14px;font-size:14px;color:#111827">Halo <strong>{{ $nama }}</strong>,</p>
            <p style="margin:0 0 18px;font-size:14px;color:#111827;line-height:1.55">{{ $pesan }}</p>

            {{-- Kartu dokumen: nomor + lencana aksi, judul, lalu metadata.
                 Cukup untuk memutuskan "perlu saya buka sekarang atau tidak"
                 tanpa membuka aplikasinya. --}}
            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                   style="border:1px solid {{ $garis }};border-left:3px solid {{ $oranye }};border-radius:6px;margin-bottom:20px">
                <tr><td style="padding:14px 16px">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td style="font-family:'Courier New',monospace;font-size:13px;font-weight:bold;color:{{ $oranye }}">
                            {{ $document->displayNumber() }}
                        </td>
                        <td align="right">
                            <span style="background:#fff1e7;color:{{ $oranye }};font-size:11px;font-weight:bold;padding:3px 9px;border-radius:10px;white-space:nowrap">{{ $aksi }}</span>
                        </td>
                    </tr></table>
                    <div style="font-size:14px;font-weight:bold;color:#111827;margin-top:6px;line-height:1.35">{{ $document->title }}</div>
                    <div style="font-size:12px;color:{{ $redup }};margin-top:6px">
                        {{ $document->type->code ?? '—' }} ·
                        {{ $document->department->code ?? '—' }} ·
                        Edisi {{ $document->edisi }} Rev {{ $document->no_revisi }}
                    </div>
                    <div style="font-size:12px;color:{{ $redup }};margin-top:2px">
                        Dibuat oleh {{ $document->creator->name ?? '—' }}
                        @if ($document->created_at) · {{ $document->created_at->format('d/m/Y') }} @endif
                    </div>
                </td></tr>
            </table>

            @if (filled($catatan))
                {{-- Alasan/rangkuman peninjau. Sengaja ditampilkan UTUH di sini:
                     inilah yang paling ingin dibaca penerima, dan memaksanya
                     membuka aplikasi hanya untuk melihat satu kalimat adalah
                     alasan orang berhenti mempercayai email pemberitahuan. --}}
                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background:#fffbeb;border:1px solid #fde68a;border-radius:6px;margin-bottom:20px">
                    <tr><td style="padding:12px 14px;font-size:13px;color:#78350f;line-height:1.55;white-space:pre-line">{{ $catatan }}</td></tr>
                </table>
            @endif

            <table cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 4px"><tr>
                <td style="background:{{ $oranye }};border-radius:6px">
                    <a href="{{ $tautan }}" target="_blank"
                       style="display:inline-block;padding:11px 26px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none">
                        Buka Dokumen di SmartPro
                    </a>
                </td>
            </tr></table>
        </td></tr>

        <tr><td style="border-top:1px solid {{ $garis }};padding:16px 24px;font-size:11px;color:{{ $redup }};line-height:1.6">
            Anda menerima email ini karena berperan dalam alur dokumen tersebut.
            Pemberitahuan yang tidak mendesak hanya muncul di lonceng aplikasi.<br>
            <em>Dokumen elektronik ini merupakan dokumen tidak terkendali apabila dicetak.</em>
        </td></tr>
    </table>
</td></tr>
</table>
