<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\User;
use App\Notifications\DocumentNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Masukan lapangan Non-Staff (FITUR-BARU-v4 §3 & §5).
 *
 * Dipakai DUA pintu masuk — halaman web "Dokumen Berlaku" dan API aplikasi
 * mobile. Logikanya ditaruh di sini supaya keduanya mustahil memakai aturan
 * penomoran atau notifikasi yang berbeda (CLAUDE.md §3).
 */
class DocumentFeedbackService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly DocumentService $documents,
        private readonly DocumentParticipantResolver $peserta,
    ) {}

    /** Kirim satu masukan atas dokumen Berlaku, lalu beri tahu SH/DH-nya. */
    public function kirim(Document $document, User $pengirim, string $isi): DocumentFeedback
    {
        $feedback = DocumentFeedback::create([
            'feedback_number' => $this->nomorBaru(),
            'document_id' => $document->id,
            'user_id' => $pengirim->id,
            'isi' => $isi,
            'status' => 'baru',
        ]);

        $this->audit->log('feedback.create', $document->id, [
            'feedback_number' => $feedback->feedback_number,
        ]);

        foreach ($this->penerima($document) as $penerima) {
            $penerima->notify(new DocumentNotification(
                $document,
                "Masukan baru {$feedback->feedback_number} pada {$document->displayNumber()} dari {$pengirim->name}.",
                'bi-chat-left-dots',
                'log.masukan',
            ));
        }

        return $feedback;
    }

    /**
     * Balas sebuah masukan lalu TUTUP tanpa revisi.
     *
     * Dipakai kanal web (DocumentFeedbackController::respond) dan kanal HP
     * (Api\FeedbackApiController::balas). Dulu isinya tertulis lengkap di dalam
     * controller web; dipindahkan ke sini begitu HP ikut membalas, supaya tak
     * pernah ada dua aturan penutupan yang harus disamakan manual.
     *
     * Yang BOLEH membalas dijawab {@see DocumentFeedback::bisaDibalasOleh()} —
     * pemanggilnya yang memeriksa, sebab bentuk penolakannya berbeda (redirect
     * vs JSON 403).
     */
    public function balas(DocumentFeedback $masukan, User $penjawab, string $balasan): DocumentFeedback
    {
        $masukan->update([
            'status' => 'ditolak',
            'balasan' => $balasan,
            'replied_by' => $penjawab->id,
            'replied_at' => now(),
        ]);

        $this->audit->log('feedback.respond', $masukan->document_id, [
            'feedback_number' => $masukan->feedback_number,
        ]);

        $masukan->user?->notify(new DocumentNotification(
            $masukan->document,
            "Masukan Anda {$masukan->feedback_number} sudah dibalas.",
            'bi-reply',
            'documents.show',
        ));

        return $masukan->refresh();
    }

    /**
     * Ajukan revisi atas dokumen Berlaku, dengan masukan lapangan yang dipilih
     * ikut DIADOPSI sebagai alasannya (§5).
     *
     * Dipakai kanal web (DocumentRevisionController::requestRevision) dan kanal
     * HP (Api\FeedbackApiController::adopsi). Rangkaiannya — buat draft revisi,
     * catat alasan sebagai Review `needs_revision`, adopsi masukannya, kabari
     * penyusunnya — HARUS satu kesatuan: melewatkan salah satu langkah
     * menghasilkan draft revisi tanpa alasan, atau masukan yang tetap tergantung
     * meski revisinya sudah jalan.
     *
     * Wewenangnya diperiksa pemanggil ({@see DocumentFeedback::bisaDiadopsiOleh}
     * di HP, {@see Document::bisaDirevisiOleh} di web).
     *
     * @param  array<int, int>  $idMasukan  id masukan yang dicentang
     * @return array{0: Document, 1: string, 2: Collection<int, DocumentFeedback>}
     */
    public function ajukanRevisi(Document $document, User $pengaju, ?string $alasan, array $idMasukan): array
    {
        // Disaring ke dokumen INI + yang belum ditindak, supaya id sembarang
        // dari luar tak bisa mengadopsi masukan milik dokumen lain.
        $masukan = DocumentFeedback::where('document_id', $document->id)
            ->whereIn('id', $idMasukan)
            ->belumDitindak()->get();

        $new = $this->documents->requestRevision($document, $pengaju);

        // Alasan disimpan sebagai Review `needs_revision` — pola yang SAMA
        // dengan penolakan approver, sehingga otomatis tampil sebagai
        // "Rangkuman Peninjau" di form revisi pembuat tanpa view baru.
        $alasanFinal = $this->alasanRevisi($alasan, $masukan);

        $new->reviews()->create([
            'reviewer_id' => $pengaju->id,
            'revision_round' => 0,
            'decision' => 'needs_revision',
            'summary' => '[Pengaju Revisi] '.$alasanFinal,
        ]);

        $this->adopsi($masukan, $new, $pengaju);

        // Angka yang DIJANJIKAN, bukan yang tersimpan: sejak butir 7a nomor
        // revisi baru naik saat draft dikirim, jadi kolomnya masih memikul
        // angka versi terbit.
        [$edisiKirim, $revisiKirim] = $new->revisiSaatKirim();

        // Pengaju (SH/DH/PJO) TIDAK dibawa ke form edit — draft revisi milik
        // PEMBUAT (GL). Pembuat TAMBAHAN ikut dikabari (FITUR-BARU-v4 §6);
        // alasan revisi dibawa ke email supaya tak perlu membuka aplikasi cuma
        // untuk membacanya.
        Notification::send($new->penyusun(), new DocumentNotification(
            $new, "Dokumen {$new->doc_number} diajukan revisi (akan menjadi Edisi {$edisiKirim} Rev {$revisiKirim}) — silakan perbarui lalu kirim ulang.",
            'bi-arrow-repeat', 'documents.edit',
            penting: true, catatan: $alasanFinal,
        ));

        // SH/DH departemen itu + MD dikabari (PLAN-REVISI-v6 Fase C, D3).
        // Sejak revisi dimulai GL/MD, merekalah yang tak lagi otomatis tahu ada
        // dokumen sedang diubah — padahal merekalah yang akan meninjaunya lagi.
        //
        // `peninjauMd()` memilih lewat PERAN, bukan izin: Admin memegang semua
        // izin dan akan ikut terjaring tiap ada revisi.
        Notification::send(
            $this->peserta->heads([$document->department_id])
                ->merge(User::peninjauMd()->get())
                ->reject(fn (User $u) => $u->id === $pengaju->id),
            new DocumentNotification(
                $new,
                "{$pengaju->name} mengajukan revisi {$document->displayNumber()}.",
                'bi-arrow-repeat', 'documents.show',
                catatan: $alasanFinal,
            ),
        );

        return [$new, $alasanFinal, $masukan];
    }

    /**
     * Adopsi masukan menjadi bahan revisi (§5): statusnya jadi `diadopsi` dan
     * tertaut ke dokumen revisi yang lahir darinya, sehingga pertanyaan "kenapa
     * dokumen ini direvisi?" bisa dijawab sampai ke masukan pemicunya.
     *
     * @param  Collection<int, DocumentFeedback>  $masukan
     */
    public function adopsi(Collection $masukan, Document $revisi, User $pengaju): void
    {
        foreach ($masukan as $item) {
            $item->update([
                'status' => 'diadopsi',
                'replied_by' => $pengaju->id,
                'replied_at' => now(),
                'revision_document_id' => $revisi->id,
            ]);

            // Pengirimnya diberi tahu masukannya berujung ke mana — tanpa itu
            // orang lapangan berhenti mengirim masukan (FITUR-BARU-v4 §3).
            $item->user?->notify(new DocumentNotification(
                $revisi,
                "Masukan Anda {$item->feedback_number} diadopsi menjadi revisi {$revisi->displayNumber()}.",
                'bi-check2-circle',
                'documents.show',
            ));
        }
    }

    /**
     * Rangkai alasan revisi dari tulisan pengaju + masukan yang dicentang.
     * Nomor masukannya ikut ditulis agar pembuat bisa menelusuri asalnya.
     *
     * @param  Collection<int, DocumentFeedback>  $masukan
     */
    public function alasanRevisi(?string $alasan, Collection $masukan): string
    {
        $baris = array_filter([$alasan]);

        foreach ($masukan as $item) {
            $baris[] = "[{$item->feedback_number}] {$item->isi}";
        }

        return implode("\n", $baris);
    }

    /**
     * Nomor masukan yang dibaca manusia: MSK-2026-0001, berurut per tahun.
     *
     * ponytail: dihitung dari nomor terbesar tahun berjalan (bukan sequence
     * tabel) — dua pengiriman pada detik yang sama bisa bertabrakan dan ditolak
     * unique index. Untuk aplikasi internal seukuran ini itu tak pernah terjadi;
     * kalau kelak perlu, bungkus dengan lockForUpdate.
     */
    private function nomorBaru(): string
    {
        $tahun = now()->format('Y');
        $terakhir = DocumentFeedback::where('feedback_number', 'like', "MSK-{$tahun}-%")
            ->max('feedback_number');

        return sprintf('MSK-%s-%04d', $tahun, $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1);
    }

    /**
     * Siapa yang perlu tahu ada masukan baru: SH/DH departemen dokumen itu —
     * merekalah yang berwenang mengajukan revisi (CLAUDE.md §6).
     *
     * PJO sengaja TIDAK ikut selama departemennya punya SH/DH: ia tak punya
     * departemen dan hanya menyetujui, sehingga akan dibanjiri masukan dari 7
     * departemen tanpa bisa menindak.
     *
     * TAPI bila departemen itu BELUM punya SH/DH aktif — departemen baru, atau
     * atasannya sedang dinonaktifkan — daftarnya kosong dan masukan tersimpan
     * tanpa ada satu orang pun yang tahu. Dalam keadaan itu barulah jatuh ke
     * pemegang `document.view_all` (PJO & Admin), supaya masukan lapangan tak
     * pernah masuk ke ruang hampa.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function penerima(Document $document)
    {
        $heads = User::where('status', 'active')
            ->where('department_id', $document->department_id)
            ->whereIn('jabatan', [User::JABATAN_SECTION_HEAD, User::JABATAN_DEPARTEMEN_HEAD])
            ->get();

        if ($heads->isNotEmpty()) {
            return $heads;
        }

        return User::where('status', 'active')
            ->get()
            ->filter(fn (User $u) => $u->can('document.view_all'))
            ->values();
    }
}
