<?php

namespace App\Services;

use App\Models\Document;
use App\Services\Print\PdfRenderer;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Musnahkan dokumen beserta SELURUH jejaknya (CLAUDE.md v4 §8).
 *
 * Berbeda dari DocumentController::destroy yang hanya soft-delete: di sini
 * barisnya benar-benar hilang, dan nomor yang ditinggalkannya boleh diisi
 * dokumen berikutnya — DocumentNumberService::firstUnusedSeq memang mengisi
 * celah. Itu pilihan sadar pemilik produk: arsip bersih lebih penting daripada
 * nomor yang unik sepanjang sejarah.
 *
 * Sebagian besar tabel anak sudah ber-ON DELETE CASCADE ke `documents`
 * (contents, versions, reviews + anotasi, approvals, attachments + komentar,
 * authors, feedback, reads), jadi yang ditangani di sini HANYA yang berada di
 * luar jangkauan foreign key. Kelimanya nyata, bukan kehati-hatian berlebihan:
 *
 *  1. `job_executions` ber-RESTRICT — JSA yang sudah dipakai di lapangan akan
 *     melempar SQL 1451 dan membatalkan seluruh transaksi.
 *  2. Berkas lampiran (disk `public`) dan berkas dokumen lama/arsip (disk
 *     `local`) tak dikenal database sama sekali.
 *  3. Singgahan PDF di storage/app/pdf-cache.
 *  4. Notifikasi lonceng menyimpan document_id di dalam JSON, tanpa FK — tanpa
 *     dibersihkan, meng-klik lonceng lama berujung 404.
 *  5. `audit_logs` SENGAJA dibiarkan (tak ber-FK, tampilannya sudah tahan null):
 *     jejak "siapa memusnahkan apa" justru harus selamat.
 */
class DocumentPurger
{
    /**
     * Status draft revisi yang BELUM disahkan — dipakai
     * {@see pastikanTakSedangDirevisi()}. Sesudah draft ini terbit, penerusnya
     * berdiri sendiri dan induknya bebas dimusnahkan.
     */
    private const REVISI_BERJALAN = [
        'draft', 'waiting_for_review', 'in_review', 'rejected',
        'verifikasi_md', 'pending_approval',
    ];

    public function __construct(private readonly AuditService $audit) {}

    /**
     * @param  bool  $paksa  lewati penjaga "sedang direvisi" — HANYA sah dipakai
     *                       {@see purgeAll()}, saat seluruh isi tabel memang akan
     *                       hilang bersamaan sehingga tak ada yang bisa jadi yatim.
     * @return string nomor dokumen yang dimusnahkan (untuk pesan ke pengguna)
     *
     * @throws \RuntimeException bila dokumen sedang dalam proses revisi
     */
    public function purge(Document $document, string $alasan, bool $paksa = false): string
    {
        if (! $paksa) {
            $this->pastikanTakSedangDirevisi($document);
        }

        $nomor = $document->displayNumber();
        $id = $document->id;

        // Path dikumpulkan SEBELUM baris-barisnya lenyap: sesudah cascade
        // berjalan, tak ada lagi yang tahu berkas mana milik dokumen ini.
        $lampiran = $document->attachments()->pluck('path')->all();

        // Kode jenis ikut ditangkap di sini, dan alasannya sama persis: singgahan
        // PDF kini dikelompokkan per jenis (`pdf-cache/{JENIS}/…`), sedangkan
        // `buangSinggahanPdf()` dipanggil SESUDAH `forceDelete()` — pada saat itu
        // relasi `type` sudah tak bisa dibaca lagi. Tanpa baris ini, singgahan
        // dokumen yang dimusnahkan tak akan pernah terhapus, dan tak ada satu pun
        // gejala yang terlihat di layar.
        $jenis = $document->type?->code;

        // Berkas dokumen LAMA (butir 0) — alasannya sama: sesudah baris hilang,
        // tak ada lagi yang tahu berkas mana miliknya, dan berkas arsip bisa
        // puluhan MB yang menumpuk tanpa satu pun gejala di layar.
        $arsip = $document->arsip_path;

        DB::transaction(function () use ($document, $id, $alasan, $nomor) {
            // RESTRICT — wajib duluan, checklist item-nya ikut cascade.
            DB::table('job_executions')->where('document_id', $id)->delete();

            DatabaseNotification::where('data->document_id', $id)->delete();

            // Ditulis SEBELUM forceDelete: kalau penghapusannya gagal, transaksi
            // membatalkan entri ini juga — jadi audit tak pernah mengklaim
            // sesuatu yang tidak terjadi.
            $this->audit->log('document.purge', null, [
                'doc_number' => $nomor,
                'title' => $document->title,
                'type' => $document->type?->code,
                'department_id' => $document->department_id,
                'alasan' => $alasan,
            ]);

            $document->forceDelete();
        });

        // Berkas dihapus SESUDAH transaksi sukses. Disk tak ikut rollback:
        // menghapusnya lebih dulu berarti kehilangan foto padahal dokumennya
        // masih ada, dan itu tak bisa dibatalkan.
        Storage::disk('public')->delete($lampiran);
        if ($arsip) {
            Storage::disk('local')->delete($arsip);
        }
        $this->buangSinggahanPdf($id, $jenis);

        return $nomor;
    }

    /**
     * Musnahkan SELURUH dokumen beserta jejaknya — pembersihan menyeluruh
     * Admin IT (PLAN C §C4) dan perintah CLI `smartpro:hapus-dokumen`.
     *
     * Loopnya ada DI SINI, bukan disalin di controller dan di Command: penghapus
     * kedua yang harus sepakat dengan yang pertama adalah penghapus yang suatu
     * hari tidak sepakat.
     *
     * `paksa: true` pada tiap pemanggilan. Penjaga "sedang direvisi" ada untuk
     * mencegah draft revisi menjadi yatim; di sini tak ada yang bisa jadi yatim
     * karena draftnya ikut hilang pada perulangan yang sama.
     *
     * Yang TIDAK tersentuh: user, departemen, jenis dokumen, konfigurasi, dan
     * `audit_logs` — termasuk entri pembersihan ini sendiri. `informasi` juga
     * tidak: itu tabel dan menu tersendiri.
     *
     * @return array{terhapus:int, nomor:list<string>}
     */
    public function purgeAll(string $alasan): array
    {
        // withTrashed: dokumen yang sudah di-soft-delete tetap memegang barisnya,
        // nomornya, dan berkasnya. Melewatkannya berarti meninggalkan justru yang
        // paling tak terlihat.
        $nomor = [];

        foreach (Document::withTrashed()->with('type')->get() as $dokumen) {
            $nomor[] = $this->purge($dokumen, $alasan, paksa: true);
        }

        // Singgahan yatim: milik dokumen yang pernah dihapus lewat jalur lain,
        // atau sisa test. Aman dibuang tanpa pandang bulu — seluruhnya turunan
        // yang dibangkitkan ulang saat PDF diminta lagi.
        $singgahan = storage_path('app/pdf-cache');
        if (File::isDirectory($singgahan)) {
            File::cleanDirectory($singgahan);
        }

        // Dicatat SESUDAH seluruhnya selesai, dan sengaja terpisah dari entri
        // `document.purge` per dokumen: yang satu menjawab "dokumen mana", yang
        // ini menjawab "kapan seluruh arsip dikosongkan, oleh siapa, kenapa".
        $this->audit->log('document.purge_all', null, [
            'jumlah' => count($nomor),
            'alasan' => $alasan,
        ]);

        return ['terhapus' => count($nomor), 'nomor' => $nomor];
    }

    /**
     * Dokumen yang sedang direvisi tak boleh dimusnahkan.
     *
     * `documents.revises_document_id` ber-ON DELETE SET NULL, jadi draft revisi
     * yang menunjuk dokumen ini tidak ikut terhapus melainkan menjadi YATIM:
     * `isRevisionDraft()` berhenti mengenalinya, dan roll-over Edisi/Revisi
     * kehilangan induknya. Menolak lebih jujur daripada meninggalkan draft rusak.
     */
    private function pastikanTakSedangDirevisi(Document $document): void
    {
        // Hanya penerus yang BELUM pernah disahkan yang menahan pemusnahan.
        // `revises_document_id` tidak pernah dibersihkan sesudah revisi selesai,
        // jadi tanpa saringan status ini setiap versi lama yang digantikan
        // revisi akan ditolak selamanya dengan alasan "sedang direvisi" —
        // padahal revisinya sudah lama berlaku, dan versi lamanya justru yang
        // paling pantas dimusnahkan. Penerus yang sudah Berlaku (atau sudah
        // obsolete sendiri) tak bisa jadi yatim: `isRevisionDraft()` memang tak
        // lagi menanyainya.
        $adaDraft = Document::where('revises_document_id', $document->id)
            ->whereIn('status', self::REVISI_BERJALAN)
            ->exists();

        if ($document->status === 'sedang_direvisi' || $adaDraft) {
            throw new \RuntimeException(
                'Dokumen ini sedang dalam proses revisi. Batalkan revisinya lebih dulu, baru dokumen bisa dimusnahkan.'
            );
        }
    }

    /**
     * Singgahan PDF dokumen ini.
     *
     * Polanya TIDAK ditulis ulang di sini melainkan diminta ke
     * {@see \App\Services\Print\PdfRenderer::jalurSinggahan()} — pemilik bentuk
     * jalur itu. Dulu ia disalin, dan salinan yang harus sepakat dengan aslinya
     * adalah salinan yang suatu hari tidak sepakat; yang tertinggal cuma berkas
     * yatim yang tak pernah muncul di layar mana pun.
     */
    private function buangSinggahanPdf(int $id, ?string $jenis): void
    {
        foreach (glob(PdfRenderer::jalurSinggahan($id, $jenis)) ?: [] as $berkas) {
            @unlink($berkas);
        }
    }
}
