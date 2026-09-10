<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Review;
use App\Models\ReviewAnnotation;
use App\Models\User;
use App\Notifications\DocumentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Keputusan peninjauan SH/DH — SATU sumber kebenaran untuk web dan mobile.
 *
 * Sebelumnya seluruh isi kelas ini berada di dalam ReviewController::store().
 * Begitu aplikasi mobile ikut mengirim hasil tinjauan, membiarkannya di sana
 * berarti menulis alur status & notifikasi untuk KEDUA kalinya — dan dua salinan
 * aturan alur akan berbeda cepat atau lambat (CLAUDE.md §3).
 *
 * ── Tanda Sesuai / Perlu Revisi (khusus JSA) ─────────────────────────────────
 * Pada JSA tiap Tindakan Pengendalian dinilai ✓/✗. Dua akibatnya ditegakkan di
 * sini, bukan di layar, supaya web dan HP mustahil berbeda hasil:
 *   • KELENGKAPAN — semua pengendalian wajib dinilai; kurang satu pun ditolak.
 *   • KEPUTUSAN DITURUNKAN — ada satu ✗ berarti dokumen dikembalikan untuk
 *     revisi. Peninjau tak memilih tombol; tandanya yang memutuskan.
 * Tanda ini TIDAK ikut tercetak — kolom "Beri tanda" pada PDF tetap kosong.
 */
class ReviewDecision
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Jenis ini dinilai per item (✓/✗)?
     *
     * Bukan sekadar "JSA": sebuah JSA yang analisanya masih kosong tak punya
     * satu pun pengendalian untuk dinilai, jadi ia kembali memakai dua tombol
     * biasa. Kondisi yang sama dipakai Blade untuk memilih bentuk tombolnya —
     * satu sumber, sehingga layar tak pernah menawarkan yang server tolak.
     */
    public function pakaiVerdict(Document $document): bool
    {
        return $document->type?->code === 'JSA' && $this->refPengendalian($document) !== [];
    }

    /**
     * Seluruh `item_ref` Tindakan Pengendalian pada dokumen ini.
     *
     * Bentuk refnya SAMA dengan yang dipakai review/show.blade.php sejak awal
     * (`L{n}-B{n}-P{n}`), jadi anotasi dari HP dan dari laptop menunjuk item
     * yang sama.
     *
     * @return array<int, string>
     */
    public function refPengendalian(Document $document): array
    {
        $isi = $document->contentMap()[$this->kunciAnalisa($document)] ?? [];
        if (! is_array($isi)) {
            return [];
        }

        $ref = [];
        foreach ($isi as $li => $langkah) {
            foreach (($langkah['bahaya'] ?? []) as $bi => $bahaya) {
                foreach (($bahaya['pengendalian'] ?? []) as $pi => $_) {
                    $ref[] = "L{$li}-B{$bi}-P{$pi}";
                }
            }
        }

        return $ref;
    }

    /** Key section bertipe `jsa_analysis` menurut schema (bukan ditebak). */
    public function kunciAnalisa(Document $document): ?string
    {
        if (! $document->type) {
            return null;
        }

        foreach (SchemaService::for($document->type)->allSections() as $section) {
            if (($section['type'] ?? null) === 'jsa_analysis') {
                return $section['key'];
            }
        }

        return null;
    }

    /**
     * Simpan hasil tinjauan, pindahkan status, kirim notifikasi, catat audit.
     *
     * @param  array{
     *     decision?:string, summary?:?string,
     *     annotations?:array<string, array<string, ?string>>,
     *     annotations_ai?:array<string, array<string, mixed>>,
     *     verdicts?:array<string, string>
     * }  $data
     * @return string kalimat untuk flash `status`
     *
     * @throws ValidationException bila penilaian JSA belum lengkap
     */
    public function simpan(Document $document, User $peninjau, array $data): string
    {
        $verdicts = $this->verdictSah($document, $data['verdicts'] ?? []);
        $keputusan = $this->keputusan($document, $data, $verdicts);

        $review = $document->reviews()->create([
            'reviewer_id' => $peninjau->id,
            'revision_round' => $document->revision_round,
            'decision' => $keputusan === 'approve' ? 'approved' : 'needs_revision',
            'summary' => $data['summary'] ?? null,
        ]);

        [$jumlahAnotasi, $jumlahAi] = $this->simpanAnotasi($document, $review, $data, $verdicts);

        return $keputusan === 'reject'
            ? $this->kembalikan($document, $jumlahAnotasi, $jumlahAi)
            : $this->loloskan($document);
    }

    /**
     * Verdict yang boleh disimpan: hanya untuk jenis yang memakainya, hanya ref
     * yang benar-benar ada, dan WAJIB lengkap.
     *
     * Kelengkapan diperiksa di server walau layar sudah menghalangi. Layar bisa
     * dilewati; ini tidak.
     *
     * @param  array<string, string>  $dikirim
     * @return array<string, string>
     */
    private function verdictSah(Document $document, array $dikirim): array
    {
        if (! $this->pakaiVerdict($document)) {
            return [];
        }

        $wajib = $this->refPengendalian($document);
        $bersih = [];
        foreach ($wajib as $ref) {
            $nilai = $dikirim[$ref] ?? null;
            if (in_array($nilai, [ReviewAnnotation::VERDICT_SESUAI, ReviewAnnotation::VERDICT_PERLU_REVISI], true)) {
                $bersih[$ref] = $nilai;
            }
        }

        if (count($bersih) < count($wajib)) {
            $kurang = count($wajib) - count($bersih);

            throw ValidationException::withMessages([
                'verdicts' => "Masih ada {$kurang} tindakan pengendalian yang belum dinilai. "
                    .'Tandai semuanya Sesuai atau Perlu Revisi sebelum mengirim.',
            ]);
        }

        return $bersih;
    }

    /**
     * `approve` atau `reject`.
     *
     * Pada JSA keputusan DITURUNKAN dari tanda — satu ✗ sudah cukup. Pada jenis
     * lain ia datang dari tombol yang ditekan peninjau, seperti sebelumnya.
     *
     * @param  array<string, string>  $verdicts
     */
    private function keputusan(Document $document, array $data, array $verdicts): string
    {
        if ($this->pakaiVerdict($document)) {
            return in_array(ReviewAnnotation::VERDICT_PERLU_REVISI, $verdicts, true) ? 'reject' : 'approve';
        }

        return ($data['decision'] ?? 'approve') === 'reject' ? 'reject' : 'approve';
    }

    /**
     * Catatan per item → `review_annotations`.
     *
     * Sebuah baris ditulis bila ada CATATAN **atau** ada TANDA. Dulu hanya
     * catatan; tanpa perubahan ini, pengendalian yang ditandai ✗ tanpa catatan
     * (yang memang boleh) tak meninggalkan jejak apa pun.
     *
     * @param  array<string, string>  $verdicts
     * @return array{0:int, 1:int}  [jumlah anotasi, jumlah yang diadopsi dari AI]
     */
    private function simpanAnotasi(Document $document, Review $review, array $data, array $verdicts): array
    {
        $catatan = $data['annotations'] ?? [];
        $aiFlags = $data['annotations_ai'] ?? [];

        // Verdict selalu menempel pada section analisa JSA; refnya digabungkan
        // ke daftar catatan agar penulisan barisnya cuma satu lintasan.
        if ($verdicts !== [] && ($kunci = $this->kunciAnalisa($document))) {
            foreach (array_keys($verdicts) as $ref) {
                $catatan[$kunci][$ref] ??= null;
            }
        }

        $jumlah = 0;
        $jumlahAi = 0;
        $kunciAnalisa = $this->kunciAnalisa($document);

        foreach ($catatan as $sectionKey => $items) {
            foreach ($items as $itemRef => $komentar) {
                $verdict = $sectionKey === $kunciAnalisa ? ($verdicts[$itemRef] ?? null) : null;

                if (blank($komentar) && $verdict === null) {
                    continue;
                }

                $dariAi = ! empty($aiFlags[$sectionKey][$itemRef]) && $aiFlags[$sectionKey][$itemRef] == '1';

                $review->annotations()->create([
                    'section_key' => $sectionKey,
                    'item_ref' => (string) $itemRef,
                    'verdict' => $verdict,
                    'severity' => 'minor',
                    'comment' => blank($komentar) ? null : $komentar,
                    'ai_generated' => $dariAi,
                    'ai_adopted' => $dariAi,
                ]);

                $jumlah++;
                $dariAi && $jumlahAi++;
            }
        }

        return [$jumlah, $jumlahAi];
    }

    /** Dokumen dikembalikan ke pembuat. */
    private function kembalikan(Document $document, int $jumlahAnotasi, int $jumlahAi): string
    {
        $document->update(['status' => 'rejected']);
        $this->audit->log('document.review_reject', $document->id, [
            'annotations' => $jumlahAnotasi, 'ai_adopted' => $jumlahAi,
        ]);

        // SELURUH penyusun, bukan hanya pembuat utama: pembuat tambahan ikut
        // mengerjakan revisinya, jadi ia harus tahu dokumennya dikembalikan.
        // Rangkuman peninjau dibawa ke email supaya penerima tak perlu membuka
        // aplikasi cuma untuk membaca satu kalimat alasan.
        Notification::send($document->penyusun(), new DocumentNotification(
            $document, "Dokumen {$document->doc_number} dikembalikan untuk revisi.",
            'bi-arrow-counterclockwise', 'documents.edit',
            penting: true,
            catatan: $document->reviews()->latest()->value('summary'),
        ));

        return "Dokumen {$document->doc_number} dikembalikan untuk revisi.";
    }

    /** Dokumen diteruskan — ke Management Development bila jenisnya menuntut, selain itu ke penyetuju. */
    private function loloskan(Document $document): string
    {
        // Jenis dokumen tertentu (saat ini SOP) masih harus melewati Management
        // Development — pemeriksaan sistematika PENULISAN — sebelum sampai ke
        // PJO. Lihat Document::perluTinjauanMd().
        if ($document->perluTinjauanMd()) {
            $document->update(['status' => 'verifikasi_md']);
            $this->audit->log('document.review_approve', $document->id, ['lanjut_ke' => 'md']);

            // Akun MD bersifat BERSAMA; dinotifikasi lewat izin, bukan nama
            // orang, sehingga tetap benar bila akunnya berpindah tangan.
            Notification::send(User::peninjauMd()->get(), new DocumentNotification(
                $document, "Dokumen {$document->doc_number} perlu ditinjau Management Development.",
                'bi-spellcheck', 'review.md.show',
                penting: true,
            ));

            // CELAH LAMA: pembuat tak pernah diberi tahu dokumennya LOLOS. Ia
            // hanya mendengar kabar buruk (ditolak) dan diam saat kabar baik,
            // sehingga satu-satunya cara tahu adalah membuka daftar berulang
            // kali. Bel saja — tak ada yang perlu ia kerjakan.
            Notification::send($document->penyusun(), new DocumentNotification(
                $document, "Dokumen {$document->doc_number} lolos tinjauan — diteruskan ke Management Development.",
                'bi-check2', 'documents.show'
            ));

            return "Dokumen {$document->doc_number} diteruskan ke Management Development.";
        }

        $document->update(['status' => 'pending_approval']);
        $this->audit->log('document.review_approve', $document->id);
        $document->approver?->notify(new DocumentNotification(
            $document, "Dokumen {$document->doc_number} perlu disetujui.",
            'bi-patch-check', 'approvals.show',
            penting: true,
        ));
        Notification::send($document->penyusun(), new DocumentNotification(
            $document, "Dokumen {$document->doc_number} lolos tinjauan — menunggu persetujuan.",
            'bi-check2', 'documents.show'
        ));

        return "Dokumen {$document->doc_number} diloloskan ke persetujuan.";
    }
}
