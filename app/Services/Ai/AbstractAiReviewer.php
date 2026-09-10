<?php

namespace App\Services\Ai;

use App\Models\Document;
use App\Services\RichText\PembersihHtml;
use Illuminate\Support\Facades\Log;

/**
 * Shared LLM reviewer logic (prompt building + response parsing). Concrete
 * providers (Gemini, OpenRouter, …) only implement callProvider() — the HTTP
 * call. Adding a new provider = one small subclass + one binding case.
 */
abstract class AbstractAiReviewer implements AiReviewerInterface
{
    /*
    | Batas tunggu satu panggilan penyedia.
    |
    | Dulu 45 detik, dan itu memutus audit JSA di tengah jalan: model gratis yang
    | dipakai sekarang adalah "reasoning" — ~2.000 token habis untuk berpikir
    | sebelum satu huruf jawaban keluar. Diukur pada JSA nyata: 48 detik.
    | 120 memberi ruang bagi varian antrean free-tier tanpa menggantung peninjau
    | tanpa batas. Turunkan lagi begitu pindah ke model berbayar non-reasoning.
    */
    protected const TIMEOUT_DETIK = 180;

    /*
    | Ruang jawaban.
    |
    | Dulu 3.000 dan itu MEMOTONG audit JSA di tengah kalimat: model gratis yang
    | dipakai sekarang menghabiskan ~2.000–3.100 token untuk "berpikir" LEBIH
    | DULU, dan sisanya tak cukup memuat temuan per-baris. Akibatnya bukan
    | jawaban pendek melainkan JSON tak lengkap — seluruh temuan hilang, layar
    | hanya menampilkan ringkasan. Diukur pada JSA nyata: 3.000 → terpotong
    | (0 temuan), 6.000 → utuh (7 temuan, 78 detik).
    |
    | Naik lagi ke 12.000 (2026-09-07): SOP nyata dgn ~12 temuan MASIH terpotong
    | di 6.000 — persis pola yang sama, cuma di dokumen dengan lebih banyak
    | temuan. Sekarang aman dinaikkan lebih longgar: sejak Analisis AI pindah
    | ke pekerja antrean (bukan lagi sinkron dari peramban), jawaban yang lebih
    | lama tak lagi menabrak batas request web — TIMEOUT_DETIK per penyedia
    | (180 dtk) tetap batas atasnya. Kalau masih ada yang terpotong di angka
    | ini, naikkan lagi berdasar contoh nyata, JANGAN diturunkan.
    |
    | Menurunkan effort penalaran sudah dicoba dan JUSTRU lebih lambat (119
    | detik) — jadi yang dibeli di sini memang ruang jawaban, bukan kecepatan.
    */
    protected const MAX_TOKEN_JAWABAN = 12000;

    /*
    | Batas tunggu UJI KONEKSI — sengaja jauh di bawah TIMEOUT_DETIK.
    |
    | Yang diuji cuma satu GET tanpa penalaran, jadi penyedia yang sehat
    | menjawab dalam hitungan detik. Memakai 180 di sini berarti Admin yang
    | salah ketik kuncinya menatap layar tiga menit untuk mengetahui satu hal
    | yang sudah pasti sejak detik pertama.
    */
    protected const TIMEOUT_PING = 15;

    public function __construct(
        protected readonly ?string $apiKey,
        protected readonly string $model,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) $this->apiKey;
    }

    public function review(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): array
    {
        if (! $this->isEnabled()) {
            return ['summary' => 'AI tidak dikonfigurasi (API key kosong).', 'findings' => []];
        }

        // Batas waktu PHP diangkat DI SINI, bukan di kedua controller: ini
        // satu-satunya titik yang dilewati SELURUH pemanggil (tinjau biasa,
        // verifikasi MD, dan pekerja antrean) sekaligus satu-satunya yang tahu
        // TIMEOUT_DETIK. Tanpa ini `max_execution_time` bawaan memutus panggilan
        // penyedia di tengah jalan, dan yang sampai ke layar/log bukan pesan AI
        // melainkan galat fatal PHP.
        //
        // Angkanya sengaja DI ATAS timeout HTTP supaya yang berbunyi lebih dulu
        // adalah timeout penyedia — pesannya jelas dan tercatat di log.
        // FallbackReviewer memanggil review() sekali per penyedia dan
        // set_time_limit MENGULANG hitungannya dari nol, jadi tiap penyedia dapat
        // jatah penuh tanpa perlu menjumlahkan keduanya di sini.
        //
        // WAJIB dikembalikan di finally: di pekerja antrean (`queue:work`),
        // satu proses PHP hidup lama lintas BANYAK job tanpa pernah "request
        // baru" yang mereset batas eksekusi. Batas yang dipasang di sini TAK
        // dikembalikan sendiri sesudah callProvider() selesai — ia terus
        // menempel dan berjalan mundur di LATAR proses, lalu mematikan
        // SELURUH pekerja (bukan cuma review ini) begitu habis, walau job
        // AI-nya sendiri sudah lama beres. Terbukti dari log: worker mati
        // dengan "Maximum execution time of 210 seconds exceeded" di
        // Illuminate\Queue\Worker.php, jauh sesudah panggilan AI selesai.
        $batasSemula = (int) ini_get('max_execution_time');
        set_time_limit(self::TIMEOUT_DETIK + 30);

        try {
            $text = $this->callProvider($this->buildPrompt($document, $contentMap, $fokus));

            // Jawaban KOSONG adalah kegagalan, bukan hasil. Model penalaran
            // (mis. nemotron `:free`) rutin memulangkan `content` kosong saat
            // seluruh jatah token habis di tahap berpikir — dulu itu lolos ke
            // parse(), keluar sebagai summary "Respons AI tidak dapat diparse.",
            // dan karena tanpa penanda `gagal` cadangan TAK PERNAH dicoba.
            if (trim($text) === '') {
                throw new \RuntimeException('penyedia memulangkan jawaban kosong');
            }

            return $this->labuhkanRef($this->parse($text), $document, $contentMap);
        } catch (\Throwable $e) {
            Log::warning('AI review failed', ['provider' => static::class, 'message' => $e->getMessage()]);

            return [
                'summary' => "Gagal memanggil AI ({$e->getMessage()}). Reviewer dapat melanjutkan secara manual.",
                'findings' => [],
                // Penanda kegagalan yang bisa DIPERIKSA. review() sengaja tak pernah
                // melempar (peninjau tak boleh melihat halaman 500), tapi tanpa
                // penanda ini FallbackReviewer mustahil membedakan "AI gagal" dari
                // "AI menjawab tanpa temuan" selain dengan menebak-nebak teks summary.
                'gagal' => true,
                // Timeout/koneksi putus dibedakan dari galat penyedia (402/429/500):
                // cadangan menolong pada yang kedua, tapi pada yang pertama ia hanya
                // MENGGANDAKAN penantian — peninjau sudah menunggu satu timeout penuh,
                // lalu disuruh menunggu satu lagi untuk model yang sama lambatnya.
                'gagal_koneksi' => $e instanceof \Illuminate\Http\Client\ConnectionException,
            ];
        } finally {
            set_time_limit($batasSemula);
        }
    }

    /** Send the prompt to the provider and return the raw text reply. */
    abstract protected function callProvider(string $prompt): string;

    /**
     * Uji kredensial (PLAN-PREPRODUKSI-v9 Fase 2a).
     *
     * Bentuknya sengaja sama dengan review(): tak pernah melempar, sebab yang
     * memanggil adalah layar Admin dan "kunci salah" bukan alasan menampilkan
     * halaman 500 — justru itulah jawaban yang sedang dicari.
     */
    public function ping(): ?string
    {
        if (! $this->isEnabled()) {
            return 'AI dinonaktifkan atau kunci belum disetel.';
        }

        try {
            $response = $this->callPing();

            return $response->failed() ? $this->pesanGalat($response) : null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * Panggilan GET termurah yang membuktikan kredensial, tanpa membangkitkan
     * satu token jawaban pun. Balasannya dikembalikan mentah supaya ping() bisa
     * merakit pesannya lewat pesanGalat() — jalur yang sama dengan review().
     */
    abstract protected function callPing(): \Illuminate\Http\Client\Response;

    /**
     * Rakit pesan galat HTTP penyedia: "402 — Insufficient credits…".
     *
     * Kedua penyedia dulu melempar status telanjang, sehingga log & layar hanya
     * memuat angka. Sebab yang paling sering (kredit habis, key dicabut, kuota
     * lewat) SELALU ada di badan balasan, jadi itulah yang perlu ikut terbawa.
     */
    protected function pesanGalat(\Illuminate\Http\Client\Response $response): string
    {
        $pesan = data_get($response->json(), 'error.message');

        return $response->status().(is_string($pesan) && $pesan !== '' ? ' — '.$pesan : '');
    }

    protected function buildPrompt(Document $document, array $contentMap, string $fokus = self::FOKUS_SUBSTANSI): string
    {
        // Hanya kirim section KONTEN (buang user_picker, checklist, dll) & dalam
        // format TERBACA (bukan JSON mentah) → hemat token + AI paham konteks. Sekaligus
        // kumpulkan daftar section_key valid agar temuan menempel ke bagian yg benar.
        // `text` ikut karena LOKASI KERJA pada JSA bertipe itu. Tanpanya rubrik
        // menyuruh menilai kespesifikan lokasi yang tak pernah dikirim — kesalahan
        // yang sama dengan audit foto: menilai sesuatu yang tak terlihat.
        $contentTypes = ['text', 'rich_list', 'reference_picker', 'repeatable_group', 'jsa_analysis'];
        // Bernomor menurut keadaan dokumen: bab opsional yang dimatikan tak
        // ikut dinilai AI, dan nomor bab di temuan sama dgn yang dibaca manusia.
        $schema = \App\Services\SchemaService::untuk($document);

        $body = '';
        $keys = [];
        $adaJsa = false;
        foreach ($schema->allSections() as $s) {
            if (! in_array($s['type'] ?? '', $contentTypes, true)) {
                continue;
            }
            $key = $s['key'];
            $keys[] = $key;
            $adaJsa = $adaJsa || ($s['type'] ?? '') === 'jsa_analysis';
            $body .= '## '.($s['label'] ?? $key)." [{$key}]\n"
                .$this->renderSection($s['type'] ?? 'text', $contentMap[$key] ?? null)."\n\n";
        }
        $keyList = implode(', ', $keys);

        // Aturan penempelan temuan. Tanpa ini seluruh temuan JSA menumpuk di satu
        // kunci `analisa` dan peninjau harus membaca daftar panjang lalu mencari
        // sendiri baris mana yang dimaksud — persis "dirangkum semua".
        $aturanRef = $adaJsa
            ? $this->aturanItemRefJsa()
            : "Sertakan \"item_ref\" berisi NOMOR URUT item di dalam section (mulai 0) bila temuan menunjuk satu item tertentu; kosongkan bila temuan berlaku untuk seluruh section.";

        $instruction = $fokus === self::FOKUS_PENULISAN
            ? $this->personaPenulisan()
            : $this->auditorInstruction();   // persona (docs/ai/Instruksi AI.md)

        $ctx = $this->typeContext($document->type->code);
        $caraMenilai = $fokus === self::FOKUS_PENULISAN
            ? $this->caraMenilaiPenulisan()
            : $this->caraMenilaiSubstansi();

        return <<<PROMPT
{$instruction}

KONTEKS DOKUMEN: jenis {$document->type->code} — "{$document->title}".
{$ctx}

{$caraMenilai}
section_key WAJIB salah satu dari: {$keyList}.

{$aturanRef}

Balas HANYA JSON valid (tanpa teks lain), semua Bahasa Indonesia:
{"summary":"3-6 kalimat: penilaian umum, kekuatan, kekurangan utama, kesimpulan kelayakan.","findings":[{"section_key":"salah satu key di atas","item_ref":"penanda item yang ditunjuk, atau \"\" bila temuan berlaku untuk seluruh section","severity":"info|minor|major|critical","issue":"observasi/masalah spesifik + alasannya.","suggestion":"saran konkret & dapat diterapkan; kosongkan bila severity=info."}]}

ISI DOKUMEN:
{$body}
PROMPT;
    }

    /**
     * Aturan penempelan temuan JSA + dua sumbu kesesuaian yang harus dinilai.
     *
     * Formulir tinjau JSA punya kotak catatan TERPISAH untuk tiap langkah, tiap
     * bahaya, dan tiap pengendalian. Tanpa `item_ref`, seluruh temuan menempel di
     * satu kunci `analisa` dan peninjau harus mencari sendiri baris yang dimaksud
     * — itulah "dirangkum semua". Penanda [L0], [L0-B1], [L0-B1-P2] dicetak
     * bersama isinya di prompt supaya AI menyalin, bukan mengarang, nomornya.
     */
    protected function aturanItemRefJsa(): string
    {
        return <<<'TXT'
MENEMPELKAN TEMUAN (WAJIB untuk section `analisa`):
Setiap baris di `analisa` diawali penanda dalam kurung siku — [L0] langkah kerja,
[L0-B1] bahaya, [L0-B1-P2] tindakan pengendalian. SALIN penanda itu apa adanya ke
"item_ref" pada temuan yang menunjuknya. JANGAN mengarang penanda yang tak tercetak.
Kosongkan "item_ref" HANYA bila temuan benar-benar berlaku untuk seluruh analisa
(mis. seluruh langkah penyelesaian pekerjaan tidak ada).
Satu temuan = satu masalah pada satu baris. JANGAN menggabungkan beberapa masalah
dari baris berbeda menjadi satu temuan panjang.

DUA SUMBU KESESUAIAN YANG WAJIB DINILAI:
1) PEKERJAAN ↔ LANGKAH KERJA. Bandingkan judul pekerjaan & lokasi kerja dengan
   daftar langkahnya: apakah langkah-langkah itu benar-benar menguraikan pekerjaan
   tersebut dari awal sampai selesai? Langkah yang tak ada hubungannya dengan
   pekerjaan, atau tahap pekerjaan yang tak punya langkah sama sekali → temuan
   pada langkah terkait, atau item_ref kosong bila yang hilang adalah tahapnya.
2) LANGKAH KERJA ↔ BAHAYA ↔ PENGENDALIAN. Untuk tiap langkah: apakah bahayanya
   memang bahaya YANG TIMBUL DARI langkah itu (bukan bahaya umum yang ditempel),
   dan apakah tiap pengendalian benar-benar MENGENDALIKAN bahaya di atasnya
   (bukan sekadar pengulangan langkah kerja atau imbauan). Ketidakcocokan
   ditempelkan pada baris yang salah, bukan pada langkahnya.
TXT;
    }

    /** Instruksi penilaian tahap PERTAMA — kebenaran ISI. */
    protected function caraMenilaiSubstansi(): string
    {
        return <<<'TXT'
CARA MENILAI (WAJIB):
1) Pahami DULU tujuan & konteks dokumen ini dari isinya.
2) Bandingkan dengan bagaimana SEHARUSNYA dokumen jenis ini (lihat konteks di atas).
3) Baru berikan temuan.

JENIS TEMUAN — HARUS SEIMBANG, JANGAN semua "saran" (peninjau tak suka dinasihati terus):
- "info" = observasi/konteks/KEKUATAN yang sudah baik atau sekadar catatan (TANPA tindakan). "suggestion" boleh kosong.
- "minor|major|critical" = masalah NYATA yang perlu diperbaiki → sertakan saran konkret.
Utamakan yang SIGNIFIKAN. JANGAN mengada-ada atau mempermasalahkan gaya bahasa remeh.
Bila dokumen sudah baik: cukup sedikit temuan masalah + beberapa "info".
TXT;
    }

    /**
     * Persona tahap KEDUA — Management Development.
     *
     * Sengaja dipersempit: kalau persona auditor mutu dipakai di sini, AI akan
     * ikut mengomentari substansi dan mengulang pekerjaan SH/DH — persis yang
     * membuat alur peninjauan jadi berputar-putar.
     */
    protected function personaPenulisan(): string
    {
        return 'Anda adalah editor naskah dokumen mutu berbahasa Indonesia. Tugas Anda HANYA memeriksa '
            .'CARA PENULISAN. Anda TIDAK menilai benar-salahnya isi teknis — itu sudah diperiksa peninjau lain.';
    }

    /** Instruksi penilaian tahap KEDUA — cara PENULISAN. */
    protected function caraMenilaiPenulisan(): string
    {
        return <<<'TXT'
YANG DIPERIKSA (HANYA INI):
1) Teks ANOMALI / bukan pada tempatnya — teks contoh atau isian yang tertinggal
   ("lorem ipsum", "dolor sit amet", "asdf", "test test", "TBD", "xxx", "isi di sini",
   tanggal/nama contoh yang jelas palsu). Ini SELALU severity "critical":
   dokumen resmi yang terbit membawa teks semacam ini adalah cacat serius.
2) Salah ketik (typo) dan salah eja.
3) Kalimat rancu, terpotong, atau tidak selesai.
4) Ketidakkonsistenan penulisan: istilah yang sama ditulis berbeda-beda, satuan,
   kapitalisasi, penomoran yang meloncat.
5) Bahasa yang tidak baku untuk dokumen resmi.

YANG TIDAK BOLEH ANDA LAKUKAN:
- JANGAN menilai kebenaran isi, kelengkapan langkah, atau kesesuaian teknis.
- JANGAN menyarankan menambah/mengurangi langkah kerja.
- JANGAN mempermasalahkan gaya bahasa yang sekadar berbeda selera bila sudah baku dan jelas.

TINGKAT TEMUAN:
- "critical" = teks anomali/placeholder yang tertinggal (butir 1).
- "major"    = kalimat rancu/terpotong sehingga maknanya bisa disalahpahami.
- "minor"    = typo, ketidakkonsistenan istilah, bahasa kurang baku.
- "info"     = catatan penulisan yang sudah baik. "suggestion" boleh kosong.

Untuk setiap temuan, KUTIP potongan teks bermasalahnya di dalam "issue" agar
peninjau bisa langsung menemukannya, lalu tuliskan perbaikannya di "suggestion".
Bila penulisannya sudah rapi, katakan demikian — jangan mengada-ada.
TXT;
    }

    /**
     * Rubrik "dokumen ini seharusnya seperti apa" per jenis (grounding).
     *
     * Sumbernya berkas Markdown `docs/ai/jenis/{KODE}.md` — menyetel AI =
     * mengedit Markdown, bukan mengubah PHP lalu deploy ulang; jenis dokumen
     * baru = satu berkas baru. Fallback ke teks bawaan DIPERTAHANKAN karena
     * `docs/` bisa saja tidak ikut ter-deploy, dan audit tak boleh diam-diam
     * berubah jadi prompt tanpa konteks. Cache statis = sekali baca per request.
     */
    protected function typeContext(string $code): string
    {
        static $cache = [];

        if (! array_key_exists($code, $cache)) {
            $path = base_path('docs/ai/jenis/'.$code.'.md');
            $cache[$code] = is_file($path) ? trim(file_get_contents($path)) : '';
        }

        return $cache[$code] !== '' ? $cache[$code] : $this->typeContextBawaan($code);
    }

    /** Rubrik ringkas bawaan — dipakai bila berkas `docs/ai/jenis/{KODE}.md` tak ada. */
    protected function typeContextBawaan(string $code): string
    {
        return match ($code) {
            'SOP' => 'SOP = prosedur operasional baku. Seharusnya: Tujuan jelas, Ruang Lingkup tegas, Referensi relevan, Definisi istilah penting, Aktivitas berurutan-logis dgn PIC tiap langkah, Lampiran bila perlu. Nilai: kelengkapan proses, kejelasan tanggung jawab, konsistensi, kemudahan diterapkan.',
            'IK' => 'IK (Instruksi Kerja) = petunjuk teknis rinci satu pekerjaan. Seharusnya: langkah kerja berurutan & jelas + PIC, alat/bahan/parameter bila relevan, hasil yang diharapkan. Nilai: kejelasan & kelengkapan langkah, tak ada langkah hilang/ambigu.',
            'SP' => 'SP (Standar Parameter) = seperti SOP dgn penekanan standar/parameter hasil produksi. Nilai: kelengkapan proses, parameter/standar terukur, tanggung jawab, konsistensi.',
            'JSA' => 'JSA (Job Safety Analysis) = tiap Langkah Kerja diurai jadi Bahaya & Risiko lalu Tindakan Pengendalian, plus APD. Seharusnya: bahaya teridentifikasi lengkap (unsafe act & condition), pengendalian memadai mengikuti hirarki kontrol (eliminasi→substitusi→rekayasa→administrasi→APD), tiap langkah berisiko ada analisanya. Nilai: kelengkapan bahaya & kememadaian pengendalian.',
            default => 'Nilai kelengkapan, kejelasan, konsistensi, dan kemudahan implementasi.',
        };
    }

    /** Render isi section jadi teks ringkas & terbaca (bukan JSON mentah → hemat token). */
    protected function renderSection(string $type, mixed $val): string
    {
        if ($type === 'jsa_analysis') {
            $out = '';
            foreach ((array) $val as $li => $step) {
                if (! is_array($step)) {
                    continue;
                }
                // Penanda [L0] / [L0-B1] / [L0-B1-P2] = `item_ref` pada formulir
                // tinjau (lihat review/show.blade.php). Dicetak di sini supaya
                // temuan AI bisa menempel ke KOTAK CATATAN barisnya masing-masing;
                // nomor 1.1.1 yang terbaca manusia tetap ada di sebelahnya.
                $out .= "[L{$li}] ".($li + 1).'. Langkah: '.trim((string) ($step['langkah'] ?? ''))."\n";
                foreach (($step['bahaya'] ?? []) as $bi => $b) {
                    $out .= "   [L{$li}-B{$bi}] ".($li + 1).'.'.($bi + 1).' Bahaya: '.trim((string) ($b['risiko'] ?? ''))."\n";
                    foreach (($b['pengendalian'] ?? []) as $pi => $p) {
                        $out .= "      [L{$li}-B{$bi}-P{$pi}] ".($li + 1).'.'.($bi + 1).'.'.($pi + 1).' Kendali: '.trim((string) $p)."\n";
                    }
                }
            }

            return $out !== '' ? rtrim($out) : '(kosong)';
        }

        if (is_array($val)) {
            $lines = [];
            foreach ($val as $i => $item) {
                if (is_array($item)) {
                    $parts = [];
                    foreach ($item as $k => $v) {
                        if (! is_string($v) || trim($v) === '') {
                            continue;
                        }
                        // ponytail: lampiran/flowchart dikirim sebagai METADATA saja
                        // (ada/tidaknya berkas), bukan gambarnya. Dulu nilainya dibuang
                        // diam-diam — akibatnya AI diminta menilai foto yang tak pernah
                        // ia terima. Naikkan ke multimodal (kirim gambar sungguhan) bila
                        // peninjau benar-benar butuh AI menilai isi fotonya — butuh
                        // konfirmasi kepatuhan data ke API eksternal lebih dulu (CLAUDE.md §12).
                        // Kolom rich_text (Deskripsi Aktivitas) menyimpan HTML.
                        // Dikirim apa adanya, perhatian AI habis pada markup dan
                        // temuannya bisa berupa "tag <p> tidak ditutup" — bukan
                        // yang diminta peninjau. teksPolos() menurunkannya jadi
                        // kalimat TAPI mempertahankan batas blok sebagai baris
                        // baru, supaya tiga butir daftar tak menyatu jadi satu.
                        $parts[] = str_starts_with($v, 'lampiran/')
                            ? "{$k}: (berkas gambar terlampir — isinya tidak dikirim ke AI)"
                            : "{$k}: ".PembersihHtml::teksPolos($v);
                    }
                    if ($parts !== []) {
                        $lines[] = ($i + 1).'. '.implode(' | ', $parts);
                    }
                } elseif (is_string($item) && trim($item) !== '') {
                    $lines[] = ($i + 1).'. '.trim($item);
                }
            }

            return $lines !== [] ? implode("\n", $lines) : '(kosong)';
        }

        return is_string($val) && trim($val) !== '' ? trim($val) : '(kosong)';
    }

    /** Muat instruksi auditor dari docs/ai; fallback ke persona ringkas. */
    protected function auditorInstruction(): string
    {
        foreach (['docs/ai/Instruksi AI.md', 'docs/ai/AI.md'] as $path) {
            $full = base_path($path);
            if (is_file($full)) {
                return trim(file_get_contents($full));
            }
        }

        return 'Anda adalah AI Document Auditor profesional (QMS/HSE) yang mengaudit dokumen mutu pertambangan secara objektif berbasis best practice.';
    }

    protected function stringify(mixed $value): string
    {
        return is_string($value) ? $value : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '');
    }

    /**
     * Upaya kedua membaca jawaban yang bukan JSON murni.
     *
     * Model gratis "reasoning" menempelkan jejak penalaran di luar JSON, dan
     * kadang mengawali jawabannya dengan pecahan rusak — terlihat sungguhan:
     * `{\n  "{\n  "summary": …`. Mengambil dari kurawal PERTAMA karena itu gagal,
     * jadi tiap kurawal dicoba sampai ada yang terbaca. Dibatasi 5 percobaan:
     * kalau lima kurawal pertama tak menghasilkan JSON, yang datang memang bukan
     * jawaban — bukan sesuatu yang bisa diselamatkan dengan mencoba lebih keras.
     *
     * @return array<mixed>|null
     */
    protected function selamatkanJson(string $text): ?array
    {
        $tutup = strrpos($text, '}');
        if ($tutup === false) {
            return null;
        }

        $dari = 0;
        for ($coba = 0; $coba < 5; $coba++) {
            $buka = strpos($text, '{', $dari);
            if ($buka === false || $buka > $tutup) {
                return null;
            }

            $hasil = json_decode(substr($text, $buka, $tutup - $buka + 1), true);
            if (is_array($hasil)) {
                return $hasil;
            }

            $dari = $buka + 1;
        }

        return null;
    }

    /**
     * Isi `item_ref` yang dikosongkan model — dengan MENCOCOKKAN ISI, bukan menebak.
     *
     * Prompt sudah mencetak penanda [L0] / [L0-B1] / [L0-B1-P2] di tiap baris dan
     * MEWAJIBKAN model menyalinnya ({@see aturanItemRefJsa()}). Model tetap tak
     * menurut: pada JSA nyata, gemini-2.0-flash memulangkan `item_ref` KOSONG
     * untuk 4 dari 4 temuan, dan kalimatnya pun tak menyebut penanda apa pun
     * sehingga penyelamat regex di {@see parse()} tak menangkap apa-apa. Akibatnya
     * seluruh temuan mendarat di Ringkasan — bukan di kotak pengendalian, bahaya,
     * atau langkah kerja yang dimaksudnya.
     *
     * Yang TIDAK dilakukan di sini: menjatuhkan temuan tanpa tujuan ke kotak
     * pertama. Itu perilaku lama di layar tinjau, dan hasilnya belasan temuan
     * menumpuk jadi satu anotasi di Langkah Kerja #1.
     *
     * Yang dilakukan: mencari baris yang KATA-KATANYA benar-benar muncul di
     * kalimat temuan. Model mengutip isi barisnya ("Bahaya PC amblas hanya
     * dikendalikan dengan …"), dan baris itu memang tercetak di prompt — jadi
     * kecocokannya nyata, bukan kemiripan yang dikarang. Ambangnya sengaja tinggi
     * dan hasilnya wajib TUNGGAL; begitu dua baris sama kuat, temuan dibiarkan
     * tanpa tujuan dan tetap jatuh ke Ringkasan. Salah kolom lebih buruk daripada
     * tanpa kolom.
     *
     * @param  array{summary: string, findings: array<int, array<string, mixed>>}  $hasil
     * @param  array<string, mixed>  $contentMap
     * @return array{summary: string, findings: array<int, array<string, mixed>>}
     */
    protected function labuhkanRef(array $hasil, Document $document, array $contentMap): array
    {
        $kosong = array_filter(
            $hasil['findings'] ?? [],
            fn ($f) => ($f['item_ref'] ?? '') === '' && filled($f['section_key'] ?? null),
        );

        if ($kosong === []) {
            return $hasil;
        }

        $peta = $this->petaItem($document, $contentMap);

        foreach ($hasil['findings'] as $i => $f) {
            if (($f['item_ref'] ?? '') !== '') {
                continue;
            }

            $hasil['findings'][$i]['item_ref'] = $this->refTerdekat(
                trim(($f['issue'] ?? '').' '.($f['suggestion'] ?? '')),
                $peta[$f['section_key'] ?? ''] ?? [],
            );
        }

        return $hasil;
    }

    /**
     * Tiap baris dokumen berikut penandanya: `section_key` → `item_ref` → teks.
     *
     * Bentuk penandanya SAMA PERSIS dengan yang dicetak {@see renderSection()} ke
     * prompt dan dengan `data-annot` pada formulir tinjau — itu satu-satunya
     * sebab hasil pelabuhan ini bisa menemukan kotaknya di layar.
     *
     * @param  array<string, mixed>  $contentMap
     * @return array<string, array<string, string>>
     */
    protected function petaItem(Document $document, array $contentMap): array
    {
        $peta = [];

        foreach (\App\Services\SchemaService::untuk($document)->allSections() as $s) {
            $key = $s['key'] ?? null;
            $isi = $key === null ? null : ($contentMap[$key] ?? null);

            if ($key === null || ! is_array($isi)) {
                continue;
            }

            if (($s['type'] ?? '') === 'jsa_analysis') {
                foreach ($isi as $li => $langkah) {
                    if (! is_array($langkah)) {
                        continue;
                    }

                    $teksLangkah = (string) ($langkah['langkah'] ?? '');
                    $peta[$key]["L{$li}"] = ['teks' => $teksLangkah, 'induk' => ''];

                    foreach (($langkah['bahaya'] ?? []) as $bi => $b) {
                        if (! is_array($b)) {
                            continue;
                        }

                        $risiko = (string) ($b['risiko'] ?? '');
                        $peta[$key]["L{$li}-B{$bi}"] = ['teks' => $risiko, 'induk' => $teksLangkah];

                        foreach (($b['pengendalian'] ?? []) as $pi => $p) {
                            $peta[$key]["L{$li}-B{$bi}-P{$pi}"] = [
                                'teks' => is_string($p) ? $p : '',
                                'induk' => $teksLangkah.' '.$risiko,
                            ];
                        }
                    }
                }

                continue;
            }

            foreach ($isi as $i => $item) {
                // Baris `repeatable_group` berupa larik kolom; seluruh kolom
                // teksnya digabung supaya kata pembeda baris itu — di kolom mana
                // pun ia berada — ikut terbandingkan.
                $peta[$key][(string) $i] = [
                    'teks' => is_string($item)
                        ? $item
                        : (is_array($item) ? implode(' ', array_filter($item, 'is_string')) : ''),
                    'induk' => '',
                ];
            }
        }

        return $peta;
    }

    /**
     * Baris yang paling jelas dibicarakan sebuah temuan, atau '' bila tak jelas.
     *
     * Skornya = berapa banyak kata penting BARIS ITU yang muncul di kalimat
     * temuan, dibagi jumlah kata pentingnya. Arahnya sengaja begitu, bukan
     * sebaliknya: kalimat temuan selalu jauh lebih panjang daripada isi barisnya,
     * jadi membagi dengan panjang kalimat akan menghukum baris pendek yang justru
     * paling sering ditunjuk ("Terperosok", "PC amblas").
     *
     * @param  array<string, array{teks: string, induk: string}>  $kandidat  item_ref → teks baris + teks induknya
     */
    protected function refTerdekat(string $teks, array $kandidat): string
    {
        $kata = fn (string $s) => array_values(array_unique(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($s)) ?: [],
            // Kata pendek dibuang: "di", "dan", "pada" muncul di hampir semua
            // baris dan akan membuat baris apa pun tampak cocok.
            fn ($w) => mb_strlen((string) $w) >= 4,
        )));

        $kalimat = $kata($teks);

        if ($kalimat === []) {
            return '';
        }

        $nilai = [];

        foreach ($kandidat as $ref => $isi) {
            $kataBaris = $kata($isi['teks'] ?? '');

            if ($kataBaris === []) {
                continue;
            }

            $kataInduk = $kata($isi['induk'] ?? '');

            /*
            | Skor utama = kecocokan baris itu sendiri. Skor induk cuma PEMECAH
            | SERI, berbobot kecil: dua bahaya berbunyi sama di dua langkah
            | berbeda adalah hal biasa pada JSA, dan yang membedakannya justru
            | kalimat langkah di atasnya — yang memang ikut dikutip model
            | ("Bahaya X pada langkah inspeksi …"). Tanpa ini keduanya seri dan
            | temuan berakhir tanpa tujuan.
            */
            $nilai[] = [
                'ref' => (string) $ref,
                'skor' => count(array_intersect($kataBaris, $kalimat)) / count($kataBaris)
                    + ($kataInduk === [] ? 0.0 : 0.2 * count(array_intersect($kataInduk, $kalimat)) / count($kataInduk)),
            ];
        }

        usort($nilai, fn ($a, $b) => $b['skor'] <=> $a['skor']);

        $terbaik = $nilai[0] ?? ['ref' => '', 'skor' => 0.0];
        $kedua = $nilai[1]['skor'] ?? 0.0;

        // Dua syarat, dan keduanya wajib: cukup yakin, DAN tak ada baris lain
        // yang sama yakinnya. Yang kedua itulah yang menjaga JSA — dua bahaya
        // berbunyi sama di langkah berbeda adalah hal biasa, dan menebak salah
        // satunya lebih merugikan daripada mengaku tak tahu.
        return $terbaik['skor'] >= 0.6 && $terbaik['skor'] > $kedua ? $terbaik['ref'] : '';
    }

    /**
     * Saring `item_ref`: nomor item biasa, atau penanda JSA L#/L#-B#/L#-B#-P#.
     *
     * Nilai ini dipakai merangkai selector di layar, jadi apa pun yang tak
     * berbentuk demikian dibuang jadi string kosong (= temuan tingkat section).
     */
    protected function itemRefSah(mixed $ref): string
    {
        $ref = is_string($ref) || is_int($ref) ? trim((string) $ref) : '';

        return preg_match('/^(\d+|L\d+(-B\d+(-P\d+)?)?)$/', $ref) === 1 ? $ref : '';
    }

    /** @return array{summary: string, findings: array} */
    protected function parse(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? '');
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            $decoded = $this->selamatkanJson($text);
        }

        if (! is_array($decoded)) {
            return ['summary' => $text ?: 'Respons AI tidak dapat diparse.', 'findings' => []];
        }

        $findings = [];
        foreach (($decoded['findings'] ?? []) as $f) {
            // Model sering MENYEBUT penandanya di dalam kalimat ("Bahaya tersengat
            // listrik (L1-B0) …") tetapi membiarkan field item_ref kosong. Diamati
            // pada JSA nyata: 10 dari 10 temuan begitu. Menariknya dari teks jauh
            // lebih murah daripada memaksa model patuh lewat prompt yang makin
            // panjang — dan panjang prompt itulah yang bikin auditnya lambat.
            if (($f['item_ref'] ?? '') === '' && preg_match('/\bL\d+(?:-B\d+(?:-P\d+)?)?\b/', (string) ($f['issue'] ?? ''), $m) === 1) {
                $f['item_ref'] = $m[0];
            }

            $findings[] = [
                'section_key' => $f['section_key'] ?? '',
                // Hanya BENTUK yang diperiksa (nomor item, atau L#/L#-B#/L#-B#-P#),
                // bukan keberadaannya. Penanda yang berbentuk sah tapi menunjuk baris
                // tak ada akan gagal menemukan kotaknya di layar, dan di sana sudah
                // ada jalan mundur ke catatan section — memvalidasi ulang seluruh
                // pohon analisa di sini hanya menyalin aturan yang sama dua kali.
                'item_ref' => $this->itemRefSah($f['item_ref'] ?? null),
                'severity' => in_array($f['severity'] ?? '', ['info', 'minor', 'major', 'critical'], true) ? $f['severity'] : 'minor',
                'issue' => $f['issue'] ?? '',
                'suggestion' => $f['suggestion'] ?? '',
            ];
        }

        return ['summary' => $decoded['summary'] ?? '', 'findings' => $findings];
    }
}
