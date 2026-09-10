<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\ReviewAccess;
use App\Services\ReviewDecision;
use App\Services\SchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Peninjauan dokumen dari aplikasi mobile.
 *
 * Cerminan ReviewController sisi web, dengan satu perbedaan yang disengaja:
 * **isi dokumen disusun DI SERVER**. Aplikasi menerima pohon Langkah → Bahaya →
 * Pengendalian yang sudah jadi, bukan `value_json` mentah plus schema. Kalau
 * kebalikannya, aplikasi harus ikut memahami schema — dan tiap perubahan schema
 * menuntut aplikasi dipasang ulang di semua HP.
 *
 * Keputusan, wewenang, dan alur statusnya TIDAK ditulis ulang di sini: keduanya
 * dipakai bersama web lewat {@see ReviewDecision} dan {@see ReviewAccess}.
 */
class ReviewApiController extends Controller
{
    public function __construct(
        private readonly ReviewAccess $akses,
        private readonly ReviewDecision $keputusan,
        private readonly AuditService $audit,
    ) {}

    /**
     * Antrian saya: yang perlu ditinjau + yang saya kembalikan.
     *
     * Dikirim dalam satu permintaan karena layarnya memang satu — dua endpoint
     * hanya membuat aplikasi menampilkan separuh daftar saat yang satu gagal.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->ringkas($this->akses->antrian($user, ['waiting_for_review', 'in_review'])
                ->latest('submitted_at')->get()),
            'status_revisi' => $this->ringkas($this->akses->antrian($user, ['rejected'])
                ->latest('updated_at')->get()),
        ]);
    }

    /**
     * Isi dokumen untuk ditinjau.
     *
     * Efek sampingnya sama dengan web: membuka dokumen menandainya `in_review`,
     * sehingga pembuat tak bisa menariknya lagi (v3.1 §4.2). Disengaja — kalau
     * hanya web yang menandai, dokumen yang dibuka dari HP akan tampak belum
     * tersentuh padahal sudah dibaca.
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        $this->akses->pastikan($request->user(), $document, ['waiting_for_review', 'in_review']);

        if ($document->status === 'waiting_for_review') {
            $document->update(['status' => 'in_review']);
            $this->audit->log('document.review_start', $document->id);
        }

        $schema = SchemaService::untuk($document);
        $isi = $document->contentMap();

        // Catatan putaran sebelumnya tetap terlihat selama revisi (§3.3),
        // dikelompokkan per item supaya aplikasi cukup melihat ke refnya.
        // Dibangun sambil menelusuri review-nya, bukan lewat `flatMap`: nomor
        // putaran hanya diketahui review induknya, dan menanyakannya dari tiap
        // anotasi berarti satu query per baris.
        $catatanLama = collect();
        foreach ($document->reviews()->with('annotations')->get() as $review) {
            foreach ($review->annotations as $anotasi) {
                if (blank($anotasi->comment)) {
                    continue;
                }
                $catatanLama[$anotasi->item_ref] = array_merge($catatanLama[$anotasi->item_ref] ?? [], [[
                    'isi' => $anotasi->comment,
                    'verdict' => $anotasi->verdict,
                    'putaran' => $review->revision_round,
                ]]);
            }
        }

        $kunciAnalisa = $this->keputusan->kunciAnalisa($document);

        return response()->json(['data' => [
            'id' => $document->id,
            'no_dokumen' => $document->displayNumber(),
            'judul' => $document->title,
            'jenis' => $document->type?->code,
            'departemen' => $document->department?->code,
            /*
            | Angka DOKUMEN — sama persis dengan yang tercetak di kop PDF.
            |
            | `revision_round` SENGAJA tidak dikirim sebagai "revisi": ia
            | menghitung berapa kali dokumen dikembalikan selama peninjauan, dan
            | memakainya membuat dokumen Edisi 1 Revisi 2 terbaca "Revisi 0" di
            | HP — persis cacat yang baru diperbaiki di sisi web.
            */
            'edisi' => $document->edisi ?? '1',
            'no_revisi' => $document->no_revisi,
            'putaran_tinjauan' => $document->revision_round + 1,

            'pembuat' => $document->creator?->name,
            'status' => $document->status,
            'status_label' => $document->statusLabel(),
            // Nama berkas mengikuti bentuk yang sudah dipakai layar PDF mobile.
            'berkas' => $document->id.'.pdf',

            // Apakah layar harus memasang tanda ✓/✗ dan menyembunyikan dua
            // tombol keputusan. Dihitung server, bukan ditebak dari `jenis`.
            'pakai_verdict' => $this->keputusan->pakaiVerdict($document),
            'jumlah_pengendalian' => count($this->keputusan->refPengendalian($document)),

            'informasi' => $this->informasi($schema, $isi, $kunciAnalisa),
            'analisa' => $kunciAnalisa ? $this->analisa($isi[$kunciAnalisa] ?? [], $catatanLama) : [],
            'bagian' => $kunciAnalisa ? [] : $this->bagianGenerik($schema, $isi, $catatanLama),
            'lampiran' => $this->lampiran($document),
        ]]);
    }

    /**
     * Simpan hasil tinjauan.
     *
     * Bentuk kiriman sengaja LEBIH DATAR daripada formulir web: `verdicts` dan
     * `catatan` dikunci langsung oleh ref (`L0-B1-P2`), tanpa lapisan
     * section_key. Aplikasi tak pernah tahu nama section, dan tak perlu tahu —
     * service yang memasangkannya kembali.
     */
    public function store(Request $request, Document $document): JsonResponse
    {
        $this->akses->pastikan($request->user(), $document, ['waiting_for_review', 'in_review']);

        $data = $request->validate([
            'decision' => [$this->keputusan->pakaiVerdict($document) ? 'nullable' : 'required', 'in:approve,reject'],
            'ringkasan' => 'nullable|string|max:2000',
            'verdicts' => 'array',
            'verdicts.*' => 'in:sesuai,perlu_revisi',
            'catatan' => 'array',
            'catatan.*' => 'nullable|string|max:2000',
        ], [], [
            'ringkasan' => 'ringkasan tinjauan',
            'decision' => 'keputusan',
        ]);

        $kunci = $this->keputusan->kunciAnalisa($document);

        $pesan = $this->keputusan->simpan($document, $request->user(), [
            'decision' => $data['decision'] ?? null,
            'summary' => $data['ringkasan'] ?? null,
            'verdicts' => $data['verdicts'] ?? [],
            // Catatan datar → bentuk bersarang yang dipahami service. Untuk
            // SOP/IK/SP tanpa analisa JSA, refnya sudah memuat section_key-nya
            // sendiri (lihat bagianGenerik()).
            'annotations' => $this->catatanBersarang($data['catatan'] ?? [], $kunci),
        ]);

        return response()->json([
            'pesan' => $pesan,
            'status' => $document->refresh()->status,
            'status_label' => $document->statusLabel(),
        ]);
    }

    /**
     * Ringkasan satu baris antrian. Umur ikut dikirim karena itulah yang dibaca
     * peninjau lebih dulu — "sudah berapa lama ini menunggu saya?"
     *
     * @param  \Illuminate\Support\Collection<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function ringkas(Collection $documents): array
    {
        return $documents->map(fn (Document $d) => [
            'id' => $d->id,
            'no_dokumen' => $d->displayNumber(),
            'judul' => $d->title,
            'jenis' => $d->type?->code,
            'departemen' => $d->department?->code,
            'pembuat' => $d->creator?->name,
            'status' => $d->status,
            'status_label' => $d->statusLabel(),
            'umur_hari' => (int) ($d->submitted_at ?? $d->updated_at)?->diffInDays(now()),
        ])->all();
    }

    /**
     * Kepala dokumen: section sederhana (teks, tanggal, daftar) sebagai pasangan
     * label → nilai siap tampil. Pemilih orang dibuang — peninjau tak
     * memerlukannya, dan namanya sudah ada di halaman pengesahan.
     *
     * @return array<int, array{label:string, nilai:string}>
     */
    private function informasi(SchemaService $schema, array $isi, ?string $kunciAnalisa): array
    {
        $out = [];

        foreach ($schema->allSections() as $section) {
            $key = $section['key'];
            $tipe = $section['type'] ?? 'text';

            if ($key === $kunciAnalisa || $tipe === 'user_picker' || ! isset($isi[$key])) {
                continue;
            }

            $nilai = $isi[$key];
            if (is_array($nilai)) {
                // Daftar sederhana (APD, peralatan) dirapatkan jadi satu baris;
                // yang bersarang diserahkan ke `bagian`.
                $datar = array_filter($nilai, 'is_string');
                if (count($datar) !== count($nilai)) {
                    continue;
                }
                $nilai = implode(', ', $datar);
            }

            if (blank($nilai)) {
                continue;
            }

            $out[] = ['label' => $section['label'] ?? $key, 'nilai' => (string) $nilai];
        }

        return $out;
    }

    /**
     * Pohon analisa JSA: Langkah → Bahaya → Pengendalian.
     *
     * `ref` memakai konvensi `item_ref` yang sudah berlaku sejak awal di
     * review/show.blade.php (`L{n}`, `L{n}-B{n}`, `L{n}-B{n}-P{n}`), jadi
     * anotasi dari HP dan dari laptop menunjuk item yang sama persis.
     *
     * @param  \Illuminate\Support\Collection<string, mixed>  $catatanLama
     * @return array<int, array<string, mixed>>
     */
    private function analisa(mixed $isi, Collection $catatanLama): array
    {
        if (! is_array($isi)) {
            return [];
        }

        $out = [];
        foreach ($isi as $li => $langkah) {
            $bahaya = [];
            foreach (($langkah['bahaya'] ?? []) as $bi => $b) {
                $pengendalian = [];
                foreach (($b['pengendalian'] ?? []) as $pi => $p) {
                    $refP = "L{$li}-B{$bi}-P{$pi}";
                    $pengendalian[] = [
                        'ref' => $refP,
                        'nomor' => ($li + 1).'.'.($bi + 1).'.'.($pi + 1),
                        'teks' => (string) $p,
                        'catatan_lama' => $this->catatanLama($catatanLama, $refP),
                    ];
                }

                $refB = "L{$li}-B{$bi}";
                $bahaya[] = [
                    'ref' => $refB,
                    'nomor' => ($li + 1).'.'.($bi + 1),
                    'risiko' => (string) ($b['risiko'] ?? ''),
                    'catatan_lama' => $this->catatanLama($catatanLama, $refB),
                    'pengendalian' => $pengendalian,
                ];
            }

            $out[] = [
                'ref' => "L{$li}",
                'nomor' => (string) ($li + 1),
                'langkah' => (string) ($langkah['langkah'] ?? ''),
                'catatan_lama' => $this->catatanLama($catatanLama, "L{$li}"),
                'bahaya' => $bahaya,
            ];
        }

        return $out;
    }

    /**
     * Bentuk generik untuk SOP/IK/SP: daftar item per bagian, catatan saja.
     *
     * Refnya diberi awalan section_key (`aktivitas::0`) supaya kiriman datar
     * dari aplikasi tetap bisa dipulangkan ke section yang benar.
     *
     * @param  \Illuminate\Support\Collection<string, mixed>  $catatanLama
     * @return array<int, array<string, mixed>>
     */
    private function bagianGenerik(SchemaService $schema, array $isi, Collection $catatanLama): array
    {
        $tipeDitinjau = ['rich_list', 'reference_picker', 'repeatable_group'];
        $out = [];

        foreach ($schema->allSections() as $section) {
            if (! in_array($section['type'] ?? 'text', $tipeDitinjau, true)) {
                continue;
            }

            $key = $section['key'];
            $nilai = $isi[$key] ?? [];
            if (! is_array($nilai) || $nilai === []) {
                continue;
            }

            $item = [];
            foreach ($nilai as $i => $baris) {
                $item[] = [
                    'ref' => "{$key}::{$i}",
                    'nomor' => (string) ($i + 1),
                    'teks' => is_array($baris)
                        ? implode(' — ', array_filter($baris, fn ($v) => is_string($v) && filled($v) && ! str_starts_with($v, 'lampiran/')))
                        : (string) $baris,
                    'catatan_lama' => $this->catatanLama($catatanLama, (string) $i),
                ];
            }

            $out[] = ['key' => $key, 'label' => $section['label'] ?? $key, 'item' => $item];
        }

        return $out;
    }

    /**
     * Catatan datar dari aplikasi → bentuk `[section_key][item_ref]`.
     *
     * @param  array<string, ?string>  $datar
     * @return array<string, array<string, ?string>>
     */
    private function catatanBersarang(array $datar, ?string $kunciAnalisa): array
    {
        $out = [];

        foreach ($datar as $ref => $komentar) {
            if (str_contains((string) $ref, '::')) {
                [$section, $item] = explode('::', (string) $ref, 2);
                $out[$section][$item] = $komentar;

                continue;
            }

            if ($kunciAnalisa) {
                $out[$kunciAnalisa][$ref] = $komentar;
            }
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $semua
     * @return array<int, array{isi:string, verdict:?string, putaran:?int}>
     */
    private function catatanLama(Collection $semua, string $ref): array
    {
        return $semua->get($ref, []);
    }

    /**
     * Foto lampiran beserta komentarnya. Justru di HP-lah foto lapangan paling
     * jelas dibaca, jadi ia ikut dikirim — bukan ditinggal di web.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lampiran(Document $document): array
    {
        return $document->attachments()->where('mime', 'like', 'image/%')
            ->with('comments.user')->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'nama' => $att->original_name,
                'url' => asset('storage/'.$att->path),
                'komentar' => $att->comments->map(fn ($c) => [
                    'nama' => $c->user->name ?? '—',
                    'isi' => $c->comment,
                    'waktu' => $c->created_at?->toDateTimeString(),
                ])->all(),
            ])->all();
    }
}
