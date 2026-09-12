<?php

namespace App\Services\RichText;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Pembersih HTML kolom Deskripsi Aktivitas (PLAN-RICHTEXT §4.4).
 *
 * Editor rich text memasukkan HTML yang DIKARANG DI PERAMBAN ke basis data.
 * Apa pun yang datang dari peramban tak boleh dipercaya — form yang dikarang
 * bisa mengirim `<script>` atau `<img src="javascript:…">` semaunya. Karena
 * itu daftarnya PUTIH (yang boleh disebut satu per satu), bukan hitam:
 * daftar hitam selalu ketinggalan satu tag yang belum terpikirkan.
 *
 * Dibersihkan SAAT SIMPAN, bukan saat cetak. Yang tersimpan di
 * `document_contents` sudah bersih, jadi jalur cetak, layar tinjau, dan
 * prompt AI tak perlu mempercayai apa pun.
 *
 * Nol package baru (CLAUDE.md §4) — `DOMDocument` bawaan PHP sudah cukup.
 */
class PembersihHtml
{
    /** Tag yang boleh tersimpan. Sisanya dibuang, ISINYA dipertahankan. */
    private const IZIN = ['p', 'br', 'strong', 'em', 'u', 'ol', 'ul', 'li', 'img', 'blockquote'];

    /** Tag yang sepadan — dinormalkan supaya yang tersimpan hanya satu ejaan. */
    private const SETARA = ['b' => 'strong', 'i' => 'em'];

    /**
     * Tag yang dibuang BERIKUT ISINYA.
     *
     * Untuk tag lain isinya dipertahankan (pembungkusnya saja yang lenyap) —
     * itu perilaku yang benar untuk `<div>`/`<font>` dan kerabatnya. Tapi untuk
     * yang di bawah ini isinya BUKAN kalimat: membiarkannya berarti mencetak
     * "alert(1)" atau sekumpulan koordinat SVG sebagai teks di tengah dokumen
     * mutu.
     *
     * Ini lapis KEDUA. Lapis pertamanya adalah cara {@see tulisSatu()} bekerja:
     * tag ditulis ULANG dari nol dan atribut tak pernah disalin, jadi `onload`,
     * `onerror`, dan `ontoggle` tak punya jalan untuk ikut walau tag-nya lolos.
     */
    private const BUANG_UTUH = [
        'script', 'style', 'head', 'title', 'iframe', 'object', 'embed',
        'svg', 'math', 'template', 'noscript', 'textarea', 'select',
    ];

    /**
     * Satu-satunya bentuk `src` gambar yang diterima: berkas yang memang
     * diunggah lewat `DocumentController::uploadAttachment` ke
     * `lampiran/{DEPT}/{JENIS}/`. Pola ini sekaligus menutup `javascript:`,
     * `data:`, dan gambar dari luar — tak satu pun cocok.
     *
     * Tanpa `/` di ruas terakhir, jadi `..` tak bisa memanjat folder.
     */
    private const POLA_GAMBAR = '#^/storage/lampiran/[A-Za-z0-9_-]+/[A-Z]+/[\w.-]+$#';

    /**
     * Apakah teks ini BISA terurai sebagai markup — atau teks polos belaka?
     *
     * DULU pertanyaannya "apakah ada tag yang saya kenal?", dijawab dengan
     * daftar nama tag. Itu LUBANG KEAMANAN, bukan sekadar kurang rapi: nama tag
     * yang tak ada di daftar membuat jawabannya `false`, dan `bersihkan()`
     * memulangkan masukan APA ADANYA — tanpa disaring sama sekali. `<svg
     * onload=…>`, `<iframe src=javascript:…>`, `<video onerror=…>`, dan
     * `<details ontoggle=…>` semuanya lolos utuh, lalu meledak di layar Tinjau
     * yang mencetaknya sebagai HTML. Pembuat dokumen berpangkat paling rendah
     * jadi bisa menjalankan skrip di sesi peninjau yang menyetujuinya.
     *
     * Pertanyaannya karena itu dibalik menjadi "mungkinkah ini markup?", dan
     * jawabannya sengaja BERLEBIH: apa pun yang mungkin, disaring. Daftar nama
     * tag tak boleh lagi menjadi penentu — daftar selalu ketinggalan satu nama
     * yang belum terpikirkan, dan yang ketinggalan itulah yang dipakai penyerang.
     *
     * `<` yang TIDAK diikuti huruf/`/`/`!`/`?` bukan awal tag menurut aturan
     * penguraian HTML mana pun, jadi kalimat polos "Suhu a < b" tetap pulang
     * utuh — dan itu penting: dokumen lama tersimpan sebagai teks bernewline,
     * dan menyentuhnya berarti mengubah tata letak SOP yang sudah berjalan.
     */
    public static function adalahHtml(?string $teks): bool
    {
        return $teks !== null && preg_match('/<[a-zA-Z\/!?]/', $teks) === 1;
    }

    /**
     * Kembalikan HTML yang aman disimpan — atau string kosong bila isinya
     * sesungguhnya kosong.
     *
     * Teks POLOS dikembalikan apa adanya: dokumen yang dibuat sebelum editor
     * ada tersimpan sebagai teks bernewline, dan jalur cetak masih memecahnya
     * per baris. Menyentuhnya di sini akan mengubah dokumen berjalan.
     */
    public static function bersihkan(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Tak mungkin markup → teks polos dokumen lama, pulang apa adanya.
        // Segala yang mungkin markup JATUH ke pengurai di bawah — tak ada
        // jalan pintas berdasar nama tag (lihat {@see adalahHtml()}).
        if (! self::adalahHtml($html)) {
            return $html;
        }

        $akar = self::urai($html);
        if ($akar === null) {
            // Tak terurai sama sekali: jangan mengembalikan HTML mentah yang
            // tak diperiksa — turunkan jadi teks biasa.
            return trim(strip_tags($html));
        }

        self::pisahkanDaftarQuill($akar);

        return self::rapikan(self::tulis($akar));
    }

    /**
     * Isi yang sama, sebagai KALIMAT — untuk pembaca yang tak mengerti markup.
     *
     * Dipakai prompt AI: kalau HTML dikirim apa adanya, peninjau AI menghabiskan
     * perhatiannya pada tag dan bisa "memperbaiki" markup alih-alih isi. Yang
     * dikirim karena itu teksnya saja, tapi dengan batas blok DIPERTAHANKAN
     * sebagai baris-baru — tanpa itu tiga butir daftar menempel jadi satu
     * kalimat panjang yang artinya berubah.
     *
     * Gambar disebut sebagai keterangan, bukan dibuang diam-diam: AI perlu tahu
     * ada langkah yang bersandar pada foto yang tidak ia terima (sejalan dengan
     * perlakuan lampiran di AbstractAiReviewer).
     */
    public static function teksPolos(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        if (! self::adalahHtml($html)) {
            return trim($html);
        }

        $baris = [];
        foreach (self::blok(self::bersihkan($html)) as $b) {
            $baris[] = ($b['kind'] ?? '') === 'gambar'
                ? '(gambar tersisip — isinya tidak dikirim ke AI)'
                : trim(html_entity_decode(strip_tags($b['html'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return trim(implode("\n", array_filter($baris, fn ($b) => $b !== '')));
    }
    /**
     * Pecah HTML tersanitasi menjadi BLOK tingkat-atas — satu blok = satu baris
     * tabel aktivitas nanti (PLAN-RICHTEXT §4.5).
     *
     * Kenapa tinggal di sini, bersama daftar putihnya: yang dipecah persis
     * tag-tag yang ditulis `bersihkan()`. Kalau daftar putih bertambah, yang
     * mengurusnya satu berkas, bukan dua yang saling menebak.
     *
     * Kenapa DIPECAH sama sekali: satu `<ol>` panjang di dalam satu `<tr>`
     * (`page-break-inside: avoid`) TAK BISA dipecah antar-halaman dan akan
     * meluber margin bawah — persis penyakit yang mesin paginasi 2-fase
     * dibangun untuk menyembuhkan. Per butir, daftar panjang mengalir seperti
     * paragraf biasa.
     *
     * Nomor & bulir dicetak sebagai TEKS (`<span class="mk">`); `<ol>`/`<ul>`
     * tak pernah sampai ke DomPDF.
     *
     * @return list<array{kind: string, html?: string, path?: string}>
     */
    public static function blok(string $html): array
    {
        $akar = self::urai($html);
        if ($akar === null) {
            return [];
        }

        $blok = [];
        self::pecahBlok($akar, $blok, 'teks');

        return $blok;
    }

    /**
     * Telusuri anak-anak sebuah simpul, keluarkan satu blok per unsur blok.
     *
     * Memanggil DIRINYA SENDIRI untuk `<blockquote>` — dengan begitu kutipan
     * yang berisi paragraf, dan kutipan berisi daftar, sama-sama pecah menjadi
     * baris tabel yang bisa mengalir antar-halaman. Kalau kutipan diperlakukan
     * sebagai satu blok utuh, kutipan panjang akan mengunci satu <tr> yang tak
     * bisa dipotong — penyakit yang sama dengan <ol> panjang.
     *
     * $kind menurun ke seluruh isinya: sebuah butir daftar DI DALAM kutipan
     * tetap tercetak sebagai kutipan.
     */
    private static function pecahBlok(DOMNode $induk, array &$blok, string $kind): void
    {
        foreach ($induk->childNodes as $anak) {
            if ($anak instanceof DOMText) {
                self::pecahInline($anak, $blok, '', $kind);

                continue;
            }

            if (! $anak instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($anak->tagName);

            if ($tag === 'blockquote') {
                self::pecahBlok($anak, $blok, 'kutipan');

                continue;
            }

            // Gambar BERDIRI SENDIRI (bukan di dalam <p>/<li>) — bentuk yang
            // ditulis editor yang gambarnya BLOK asli (mis. Lexical
            // `DecoratorNode`), berbeda dari Quill lama yang gambarnya
            // inline dan selalu terbungkus <p> (lihat cabang <img> di
            // pecahInline() untuk bentuk ITU). Tanpa cabang ini, gambar
            // seperti ini tersimpan aman tapi DIAM-DIAM tak pernah tercetak:
            // pecahInline() cuma memeriksa ANAK sebuah simpul, dan <img>
            // tak punya anak sama sekali.
            if ($tag === 'img') {
                $jalur = self::jalurGambar($anak);
                if ($jalur !== null) {
                    $blok[] = ['kind' => 'gambar', 'path' => $jalur, 'html' => ''];
                }

                continue;
            }

            if ($tag === 'ol' || $tag === 'ul') {
                $no = 0;
                $liPertama = true;

                foreach ($anak->childNodes as $li) {
                    if (! $li instanceof DOMElement || strtolower($li->tagName) !== 'li') {
                        continue;
                    }

                    // "Lanjutkan Penomoran" (fitur toolbar Quill): butir
                    // PERTAMA sebuah <ol> boleh membawa `data-mulai`, ditulis
                    // `tulisSatu()` saat disimpan. Bulir (`ul`) tak bernomor,
                    // jadi tandanya tak berarti di sana.
                    if ($liPertama && $tag === 'ol') {
                        $mulai = $li->getAttribute('data-mulai');
                        if (self::mulaiSah($mulai)) {
                            $no = ((int) $mulai) - 1;
                        }
                    }
                    $liPertama = false;

                    $no++;
                    $tanda = $tag === 'ol' ? $no.'.' : '&bull;';
                    self::pecahInline($li, $blok, '<span class="mk">'.$tanda.'</span>', $kind);
                }

                continue;
            }

            self::pecahInline($anak, $blok, '', $kind);
        }
    }

    /**
     * Isi satu blok → satu blok teks, ATAU beberapa blok bila ada gambar di
     * tengahnya: gambar SELALU berdiri sebagai baris tabel tersendiri.
     *
     * Sebabnya DomPDF: gambar yang duduk sebaris dengan teks di dalam sel
     * membuat tinggi baris tak terduga, dan tinggi itulah yang diukur mesin
     * 2-fase untuk menentukan titik potong halaman.
     */
    private static function pecahInline(DOMNode $induk, array &$blok, string $awalan, string $kind = 'teks'): void
    {
        $inline = '';
        $anak = $induk instanceof DOMElement ? iterator_to_array($induk->childNodes) : [$induk];

        $tuang = function () use (&$inline, &$blok, &$awalan, $kind) {
            if (trim(strip_tags($inline)) !== '') {
                $blok[] = ['kind' => $kind, 'html' => $awalan.$inline];
                $awalan = '';   // penanda daftar hanya untuk potongan PERTAMA
            }
            $inline = '';
        };

        foreach ($anak as $simpul) {
            if ($simpul instanceof DOMElement && strtolower($simpul->tagName) === 'img') {
                $tuang();
                $jalur = self::jalurGambar($simpul);
                if ($jalur !== null) {
                    /*
                     | Penanda butir yang BELUM terpakai ikut menumpang baris
                     | gambar. Tanpa ini, butir daftar yang isinya HANYA foto
                     | kehilangan nomornya: $tuang() di atas hanya melepas
                     | $awalan kalau ada teks yang menemaninya, sedangkan butir
                     | seperti itu tak punya teks sama sekali. Penyusun menempel
                     | tangkapan layar di butir ke-4, lalu "4." lenyap dari PDF.
                     */
                    $blok[] = ['kind' => 'gambar', 'path' => $jalur, 'html' => $awalan];
                    $awalan = '';
                }

                continue;
            }

            $inline .= self::tulisSatu($simpul);
        }

        $tuang();
    }

    /**
     * Jalur disk sebuah `<img>` (`lampiran/…`) — atau null bila `src`-nya tak
     * menunjuk berkas lampiran yang sah.
     *
     * Yang dikembalikan jalur RELATIF disk `public`, bentuk yang dimengerti
     * closure `$embed` di `PdfRenderer::viewData()`; DomPDF tak bisa memuat
     * `/storage/…` sendiri.
     */
    private static function jalurGambar(DOMElement $img): ?string
    {
        $src = $img->getAttribute('src');

        return preg_match(self::POLA_GAMBAR, $src) === 1
            ? substr($src, strlen('/storage/'))
            : null;
    }

    /**
     * Urai HTML potongan (bukan dokumen utuh) menjadi satu simpul pembungkus.
     *
     * Non-ASCII diubah dulu jadi entitas angka: tanpa itu `loadHTML` menebak
     * masukan sebagai ISO-8859-1 dan huruf beraksen jadi kacau.
     */
    private static function urai(string $html): ?DOMElement
    {
        $doc = new DOMDocument;
        $sebelum = libxml_use_internal_errors(true);

        $doc->loadHTML(
            '<div>'.mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8').'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($sebelum);

        $div = $doc->getElementsByTagName('div')->item(0);

        return $div instanceof DOMElement ? $div : null;
    }

    /**
     * Quill 2 menulis bulir SEBAGAI `<ol>` — pembedanya cuma
     * `<li data-list="bullet">`. Daftar bernomor dan berbulir yang berdampingan
     * bahkan digabung ke dalam SATU `<ol>`.
     *
     * Di sini `<ol>` dipecah menjadi `<ol>`/`<ul>` yang benar menurut
     * `data-list`, sehingga yang tersimpan HTML biasa — bukan dialek Quill.
     * Editor lain kelak bisa menggantikan Quill tanpa menyentuh jalur cetak.
     */
    private static function pisahkanDaftarQuill(DOMElement $akar): void
    {
        $doc = $akar->ownerDocument;

        // Disalin ke larik dulu: daftarnya hidup, dan simpulnya dipindahkan
        // di dalam gelung — iterasi langsung akan melewati sebagian.
        foreach (iterator_to_array($akar->getElementsByTagName('ol')) as $ol) {
            $kelompok = null;
            $jenisKini = null;

            foreach (iterator_to_array($ol->childNodes) as $anak) {
                if (! $anak instanceof DOMElement || strtolower($anak->tagName) !== 'li') {
                    continue;
                }

                $jenis = $anak->getAttribute('data-list') === 'bullet' ? 'ul' : 'ol';

                if ($jenis !== $jenisKini) {
                    $kelompok = $doc->createElement($jenis);
                    $ol->parentNode->insertBefore($kelompok, $ol);
                    $jenisKini = $jenis;
                }

                $kelompok->appendChild($anak);
            }

            $ol->parentNode->removeChild($ol);
        }
    }

    /**
     * Tulis ulang pohon menurut daftar putih.
     *
     * Ditulis sendiri, bukan `saveHTML()` lalu atributnya dibuang: dengan
     * menulis sendiri, atribut tak pernah ikut tertulis sejak awal — tak ada
     * `onclick` yang bisa lolos karena terlupa dihapus.
     */
    private static function tulis(DOMNode $simpul): string
    {
        $keluar = '';

        foreach ($simpul->childNodes as $anak) {
            $keluar .= self::tulisSatu($anak);
        }

        return $keluar;
    }

    /** Satu simpul — lihat {@see tulis()}. */
    private static function tulisSatu(DOMNode $simpul): string
    {
        if ($simpul instanceof DOMText) {
            return htmlspecialchars($simpul->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        // Komentar & instruksi pengolahan dibuang tanpa sisa.
        if (! $simpul instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($simpul->tagName);

        if (in_array($tag, self::BUANG_UTUH, true)) {
            return '';
        }

        $tag = self::SETARA[$tag] ?? $tag;

        if ($tag === 'img') {
            $src = self::jalurGambar($simpul);

            return $src === null ? '' : '<img src="/storage/'.$src.'">';
        }

        if ($tag === 'br') {
            return '<br>';
        }

        if (! in_array($tag, self::IZIN, true)) {
            return self::tulis($simpul);
        }

        $isi = self::tulis($simpul);

        /*
         | Pembungkus yang isinya habis TIDAK ikut tertulis.
         |
         | Bukan kerapian belaka: sebuah <p> yang isinya hanya gambar dari luar
         | akan kehilangan gambarnya di sini dan menyisakan <p></p> — yang lalu
         | dibaca filled() sebagai isi, sehingga baris aktivitas yang sudah
         | sesungguhnya kosong tetap tersimpan dan memakan satu baris tabel di
         | PDF. Aturan ini menurun sendiri: <li> kosong lenyap, lalu <ul> yang
         | jadi kosong ikut lenyap.
         */
        if (trim(strip_tags($isi)) === '' && ! str_contains($isi, '<img')) {
            return '';
        }

        // Satu-satunya atribut yang dipertahankan di seluruh berkas ini —
        // lihat {@see mulaiSah()} untuk kenapa ia aman disalin apa adanya
        // (bilangan bulat murni, ditulis ulang bukan disalin mentah). Hanya
        // berarti pada `<ol>` (bulir tak bernomor); `pisahkanDaftarQuill()`
        // sudah dijalankan lebih dulu jadi `parentNode` di sini SELALU sudah
        // `<ol>`/`<ul>` yang benar menurut `data-list` aslinya.
        if ($tag === 'li') {
            $indukOl = $simpul->parentNode instanceof DOMElement
                && strtolower($simpul->parentNode->tagName) === 'ol';
            $mulai = $simpul->getAttribute('data-mulai');

            return $indukOl && self::mulaiSah($mulai)
                ? '<li data-mulai="'.((int) $mulai).'">'.$isi.'</li>'
                : "<li>{$isi}</li>";
        }

        return "<{$tag}>".$isi."</{$tag}>";
    }

    /**
     * `data-mulai` sah — fitur "Lanjutkan Penomoran" (butir daftar bernomor
     * yang mulai menghitung dari sini, bukan dari 1). Batas 1-999 bukan
     * batas teknis, sekadar akal sehat: tak ada prosedur yang benar-benar
     * butuh 999 langkah.
     */
    private static function mulaiSah(string $nilai): bool
    {
        return $nilai !== '' && ctype_digit($nilai) && (int) $nilai >= 1 && (int) $nilai <= 999;
    }

    /**
     * Editor yang dikosongkan tetap mengirim `<p><br></p>`. Kalau itu tersimpan
     * apa adanya, `filled()` dan peringatan isian-kosong akan menganggap bab
     * kosong sebagai bab terisi — penyusun tak pernah diperingatkan.
     */
    private static function rapikan(string $html): string
    {
        $html = trim($html);

        if (str_contains($html, '<img')) {
            return $html;
        }

        $sisa = preg_replace('/\x{00A0}/u', ' ', strip_tags($html)) ?? '';

        return trim($sisa) === '' ? '' : $html;
    }
}
