<?php

namespace App\Services;

use App\Http\Controllers\ApprovalController;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\User;
use App\Notifications\DocumentNotification;
use App\Services\Print\PdfRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Orchestrates document creation and content persistence. Business logic lives
 * here rather than in the controller (CLAUDE.md §3).
 */
class DocumentService
{
    public function __construct(
        private readonly DocumentNumberService $numbering,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a new draft document (roadmap Task 2.3). The document number is
     * either auto-generated (PPA-ADRO-{TYPE}-{DEPT}-{NN}) or provided manually.
     */
    public function createDraft(
        User $creator,
        DocumentType $type,
        Department $department,
        string $title,
        ?string $manualNumber = null,
    ): Document {
        return DB::transaction(function () use ($creator, $type, $department, $title, $manualNumber) {
            $isManual = filled($manualNumber);
            $number = $isManual ? $manualNumber : $this->numbering->generateTemp($type, $department);

            $document = Document::create([
                'document_type_id' => $type->id,
                'department_id' => $department->id,
                'title' => $title,
                // Nomor SEMENTARA — dipakai selama disusun & ditinjau, dan
                // diwarisi apa adanya oleh draft revisi (lihat requestRevision).
                'doc_number' => $number,
                'doc_number_final' => null,       // dikunci saat disahkan (Fase 4)
                'doc_number_manual' => $isManual,
                'status' => 'draft',
                'current_step' => 1,
                'created_by' => $creator->id,
            ]);

            // Primary author = the creator (the only signatory on pengesahan, §2.3).
            $document->authors()->create([
                'user_id' => $creator->id,
                'is_primary' => true,
            ]);

            $this->audit->log('document.create', $document->id, [
                'doc_number' => $document->doc_number,
                'type' => $type->code,
                'department' => $department->code,
            ]);

            return $document;
        });
    }

    /**
     * Daftarkan dokumen LAMA (arsip, butir 0): PDF yang sudah terlanjur ada,
     * masuk apa adanya dan LANGSUNG BERLAKU (keputusan pemilik B7).
     *
     * Kenapa tidak lewat alur tinjau–setuju: dokumen ini SUDAH pernah ditinjau
     * dan disahkan — di kertas, bertahun lalu. Menjalankannya ulang lewat
     * wizard berarti meminta SH/DH menyetujui kembali ratusan dokumen yang
     * sudah berlaku di lapangan. Yang dibayar sebagai gantinya: entri audit log
     * per dokumen, dan kabar ke SH/DH departemennya (dikirim pemanggil).
     *
     * Nomornya SELALU manual — itu memang nomor lamanya. `doc_number_final`
     * diisi sekaligus di sini, bukan menunggu ApprovalController: dokumen ini
     * tak akan pernah melewati pengesahan, jadi tanpa ini ia berlaku selamanya
     * tanpa nomor final dan luput dari kolam nomor yang ditahan
     * (DocumentNumberService::generateFinal membaca `doc_number_final`).
     *
     * @param  string  $arsipPath  jalur relatif di disk `local`
     */
    public function createArsip(
        User $creator,
        DocumentType $type,
        Department $department,
        string $title,
        string $number,
        string $arsipPath,
        int $edisi,
        int $noRevisi,
        ?string $tanggalEfektif = null,
    ): Document {
        return DB::transaction(function () use ($creator, $type, $department, $title, $number, $arsipPath, $edisi, $noRevisi, $tanggalEfektif) {
            $document = Document::create([
                'document_type_id' => $type->id,
                'department_id' => $department->id,
                'title' => $title,
                'doc_number' => $number,
                'doc_number_final' => $number,
                'doc_number_manual' => true,
                'arsip_path' => $arsipPath,
                'status' => 'published',
                'current_step' => 1,
                'edisi' => (string) $edisi,
                'no_revisi' => $noRevisi,
                'created_by' => $creator->id,
                // Tanggal efektif dokumen lamalah tanggal terbitnya. Memakai
                // now() akan membuat seluruh arsip tampak terbit hari ini, dan
                // "Dokumen Berlaku" (urut `published_at`) berubah jadi urutan
                // impor alih-alih urutan berlakunya dokumen.
                'published_at' => $tanggalEfektif ?: now(),
            ]);

            $document->authors()->create(['user_id' => $creator->id, 'is_primary' => true]);

            $this->audit->log('document.arsip_upload', $document->id, [
                'doc_number' => $number,
                'type' => $type->code,
                'department' => $department->code,
                'edisi' => $edisi,
                'no_revisi' => $noRevisi,
            ]);

            return $document;
        });
    }

    /**
     * Persist content for one section (used by autosave and step submit).
     * Stored as value_json keyed by section — no per-type columns (PRD §11).
     */
    public function saveSection(Document $document, string $sectionKey, mixed $value): void
    {
        $document->contents()->updateOrCreate(
            ['section_key' => $sectionKey],
            ['value_json' => $value],
        );
    }

    /**
     * Revisi TERTINGGI yang boleh tampil dalam satu edisi (keputusan pemilik
     * K-B, rencana pra-produksi Fase 1). Dulu 4.
     *
     * SATU sumber kebenaran, bukan literal yang disalin: angka ini juga jadi
     * batas atas tiga aturan validasi terpisah (`ArsipDocumentRequest`,
     * `DocumentArsipController`, `StoreInformasiRequest`). Selama ia tersalin
     * sebagai `max:4` di tiap tempat, satu perubahan ambang akan menyisakan
     * formulir yang menolak angka yang baru saja disahkan roll-over — dan
     * penolakannya muncul jauh dari sini, sebagai galat validasi biasa.
     */
    public const MAKS_REVISI = 5;

    /**
     * Roll-over Edisi/Revisi: revisi naik 0→1→…→5; revisi berikutnya (yang ke-6)
     * menaikkan EDISI dan MEMULAI revisi dari 1 (W-5) — edisi ≥2 tak pernah
     * berevisi 0, angka itu cuma dipakai Edisi 1 saat dokumen baru dibuat.
     *
     * @return array{0:int,1:int} [edisi, no_revisi] versi berikutnya
     */
    public static function nextEditionRevision(int $edisi, int $noRevisi): array
    {
        return $noRevisi + 1 > self::MAKS_REVISI ? [$edisi + 1, 1] : [$edisi, $noRevisi + 1];
    }

    /**
     * Revisi Tipe B (PRD v2 §3.3): pembaruan dokumen Berlaku (0→1→…→5, lalu
     * Edisi naik & revisi kembali 0). Snapshot versi lama ke document_versions,
     * set lama "Sedang Direvisi" (masih Berlaku sementara), lalu buat versi baru
     * dengan salinan isi — direview dari awal (anotasi lama tidak dibawa).
     */
    public function requestRevision(Document $published, User $requester): Document
    {
        return DB::transaction(function () use ($published) {
            $this->snapshot($published);

            $published->update(['status' => 'sedang_direvisi']);

            $new = Document::create([
                'doc_number' => $published->doc_number,          // nomor diwariskan
                'doc_number_manual' => $published->doc_number_manual,
                'document_type_id' => $published->document_type_id,
                'department_id' => $published->department_id,
                'title' => $published->title,
                'status' => 'draft',
                'current_step' => 1,
                'revision_round' => 0,
                // Edisi & Revisi diwarisi APA ADANYA dari versi terbit; keduanya
                // baru naik saat draft ini DIKIRIM (butir 7a, keputusan pemilik
                // C1) — lihat Document::revisiSaatKirim(). Dulu naik di sini,
                // yaitu pada detik SH/DH menekan "Ajukan Revisi", sebelum
                // pembuat menyentuh apa pun; revisi yang lalu dibatalkan pun
                // sudah terlanjur memakan satu nomor.
                'no_revisi' => $published->no_revisi,
                'revises_document_id' => $published->id,   // penanda draft revisi (tahap-3 wizard)
                'edisi' => (string) max(1, (int) ($published->edisi ?: 1)),
                'is_controlled' => $published->is_controlled,
                // Pemilik versi baru = PEMBUAT ASLI (GL), bukan pengaju revisi (SH/DH/PJO),
                // karena hanya pembuat (GL) yang menyunting & mengirim dokumen (v2 rev).
                'created_by' => $published->created_by,
            ]);

            // Salin isi dari versi lama supaya revisor mulai dari isi terakhir.
            foreach ($published->contents as $content) {
                $new->contents()->create([
                    'section_key' => $content->section_key,
                    'value_json' => $content->value_json,
                ]);
            }

            $new->authors()->create(['user_id' => $published->created_by, 'is_primary' => true]);

            // PEMBUAT TAMBAHAN ikut terbawa ke draft revisinya (FITUR-BARU-v4 §6).
            // Mereka menyusun versi yang direvisi ini; menghilangkan mereka berarti
            // halaman pengesahan versi baru mendadak kehilangan nama yang ada di
            // versi lama, dan dokumennya lenyap dari menu "Dokumen Saya" milik mereka.
            foreach ($published->authors()->where('is_primary', false)->pluck('user_id') as $userId) {
                $new->authors()->create(['user_id' => $userId, 'is_primary' => false]);
            }

            $this->audit->log('document.request_revision', $new->id, [
                'from_document_id' => $published->id,
                'no_revisi' => $new->no_revisi,
            ]);

            return $new;
        });
    }

    /**
     * Rollback (butir 7): isi versi LAMA dihidupkan lagi sebagai draft revisi
     * baru atas dokumen yang sedang Berlaku.
     *
     * TANPA gerbang persetujuan tersendiri (keputusan pemilik G1) — draft ini
     * menempuh alur pengesahan yang sama persis dengan revisi biasa. Karena itu
     * seluruh pekerjaan beratnya diserahkan ke {@see requestRevision()}: nomor,
     * peserta, snapshot, pembuat tambahan, dan status dokumen induk sudah benar
     * di sana. Yang khas rollback hanyalah "isinya datang dari mana".
     */
    public function rollbackDraft(Document $versiLama, User $pemohon): Document
    {
        return DB::transaction(function () use ($versiLama, $pemohon) {
            // Kunci grup versi = doc_number; tiap revisi mewarisinya apa adanya.
            $berlaku = Document::where('doc_number', $versiLama->doc_number)
                ->where('status', 'published')->firstOrFail();

            $draft = $this->requestRevision($berlaku, $pemohon);

            // Isi draft DITIMPA dengan isi versi lama. Bab yang hanya ada di
            // versi Berlaku sengaja dibiarkan tertinggal apa adanya: menghapus
            // bab bukan bagian dari rollback, dan GL tetap bisa mengosongkannya
            // sendiri di wizard.
            foreach ($versiLama->contents as $content) {
                $this->saveSection($draft, $content->section_key, $content->value_json);
            }

            // Satu baris pembuka pada lembar CATATAN REVISI supaya GL tinggal
            // melanjutkan alasannya; baris ini bisa disunting/dihapus seperti
            // baris lain. Baris lama tetap di atasnya — riwayat revisi memang
            // terakumulasi lintas versi.
            [, $revisiKirim] = $draft->revisiSaatKirim();
            $catatan = collect($versiLama->contentMap()['catatan_revisi'] ?? [])
                ->filter(fn ($r) => is_array($r))
                ->push([
                    'no_rev' => $revisiKirim,
                    'tanggal' => now()->toDateString(),
                    'halaman' => '',
                    'catatan' => "Rollback ke Edisi {$versiLama->edisi} Revisi {$versiLama->no_revisi}. ",
                ])
                ->values()->all();
            $this->saveSection($draft, 'catatan_revisi', $catatan);

            $this->audit->log('document.rollback', $draft->id, [
                'dari_versi' => $versiLama->id,
                'edisi' => $versiLama->edisi,
                'no_revisi' => $versiLama->no_revisi,
            ]);

            // Penyusunlah yang akan mengerjakan draft ini, dan bisa jadi bukan
            // dia yang menekan tombolnya (MD boleh me-rollback dokumen dept mana
            // pun). penting: ini pekerjaan baru, bukan sekadar kabar.
            Notification::send(
                $draft->penyusun(),
                new DocumentNotification(
                    $draft,
                    "Dokumen {$draft->doc_number} dikembalikan ke Edisi {$versiLama->edisi} Revisi {$versiLama->no_revisi} — silakan periksa lalu kirim ulang.",
                    'bi-arrow-counterclockwise', 'documents.edit', penting: true,
                ),
            );

            return $draft;
        });
    }

    /**
     * Dokumen MASUK antrean tinjauan — satu-satunya pintu "kirim".
     *
     * Dipakai oleh KEDUA jalur pengiriman (tombol Kirim di daftar dokumen dan
     * langkah terakhir wizard) supaya nomor revisi, putaran revisi, audit, dan
     * notifikasi peninjau tak mungkin berbeda di antara keduanya. Di sinilah
     * Edisi/Revisi draft revisi Tipe B naik (butir 7a) — aturan naiknya sendiri
     * ada di {@see Document::revisiSaatKirim()}.
     */
    public function tandaiTerkirim(Document $document): void
    {
        // Salinan dokumen lama (rencana pra-produksi Fase 2, butir 7): dokumennya
        // SUDAH Berlaku sejak diunggah dan isinya cuma diketik ulang, jadi tak ada
        // yang perlu ditinjau — ia langsung disahkan.
        //
        // Dicabangkan DI SINI, di satu-satunya pintu "kirim", supaya tak ada jalur
        // kedua yang bisa lolos ke antrean tinjauan.
        if ($document->salin_arsip_at) {
            $this->sahkanSalinanArsip($document);

            return;
        }

        // Kirim ulang sesudah ditolak = putaran revisi baru (Tipe A).
        if ($document->status === 'rejected') {
            $document->revision_round++;
        }

        [$edisi, $noRevisi] = $document->revisiSaatKirim();
        $document->edisi = (string) $edisi;
        $document->no_revisi = $noRevisi;

        // waiting_for_review (BUKAN in_review): GL masih bisa MENARIK dokumen
        // selama peninjau belum membukanya (review.show → in_review).
        $document->status = 'waiting_for_review';
        $document->submitted_at = now();
        $document->save();

        $this->audit->log('document.submit', $document->id, [
            'status' => 'waiting_for_review',
            'round' => $document->revision_round,
            'no_revisi' => $document->no_revisi,
        ]);

        // penting: peninjau punya PEKERJAAN baru — dokumen menganggur di mejanya
        // sampai ia membukanya. Inilah kelas peristiwa yang layak masuk email.
        $document->reviewer?->notify(new DocumentNotification(
            $document, "Dokumen {$document->doc_number} perlu ditinjau.", 'bi-clipboard-check', 'review.show',
            penting: true,
        ));
    }

    /**
     * Salinan dokumen lama disahkan TANPA tinjauan (butir 7, keputusan pemilik).
     *
     * Wajib mengerjakan seluruh pekerjaan yang biasanya dikerjakan
     * {@see ApprovalController::store()}, bukan sekadar
     * mengubah status: tanpa langkah kedua di bawah, dokumen unggahan aslinya
     * tertinggal berstatus `sedang_direvisi` SELAMANYA — tak bisa disunting, tak
     * muncul di Dokumen Berlaku maupun Tidak Berlaku.
     *
     * Nomor final tak dibangkitkan ulang: `createArsip()` sudah mengisi
     * `doc_number_final`, dan `requestRevision()` mewarisi `doc_number` apa
     * adanya — jadi tak ada kolam nomor yang tersentuh, tak ada risiko bentrok.
     */
    private function sahkanSalinanArsip(Document $document): void
    {
        DB::transaction(function () use ($document) {
            // Baris `document_versions` sudah dibuat requestRevision() saat draft
            // ini lahir — tidak diulang di sini.
            $versiLama = Document::where('doc_number', $document->doc_number)
                ->where('id', '!=', $document->id)
                ->where('status', 'sedang_direvisi');

            // Id dikumpulkan SEBELUM update: `$versiLama` query menyaring status
            // `sedang_direvisi`, jadi menjalankannya lagi sesudahnya memulangkan
            // daftar KOSONG (pelajaran ApprovalController::store).
            $idVersiLama = $versiLama->pluck('id');

            $document->update([
                'status' => 'published',
                // Tanggal terbit dokumen LAMA, bukan hari pengetikan ulangnya
                // (TEMUAN-F8). Urutannya: yang diketik pengguna di langkah Log
                // Revisi, lalu tanggal terbit versi unggahan yang digantikan
                // (itulah `tanggal_efektif` yang dimasukkan saat mengunggah),
                // baru now() bila dua-duanya kosong. Memakai now() lebih dulu
                // membuat seluruh salinan arsip tampak terbit hari ini, dan
                // "Dokumen Berlaku" (urut `published_at`) berubah jadi urutan
                // pengetikan ulang — cacat yang sama yang sudah dijaga di
                // createArsip().
                'published_at' => $document->published_at
                    ?? $document->revisesDocument?->published_at
                    ?? now(),
                'doc_number_final' => $document->doc_number_final ?: $document->doc_number,
                'submitted_at' => now(),
            ]);

            $versiLama->update([
                'status' => 'obsolete',
                'obsolete_reason' => Document::OBSOLETE_REVISI,
            ]);

            // Masukan lapangan yang belum ditindak ikut pindah ke versi baru —
            // alasannya sama persis dengan di ApprovalController::store().
            DocumentFeedback::whereIn('document_id', $idVersiLama)
                ->belumDitindak()
                ->update(['document_id' => $document->id]);

            // Aksi `document.approve` DIPAKAI ULANG, bukan aksi baru: kartu KPI
            // "Berlaku" dan sparkline dasbor menghitung dokumen terbit lewat nama
            // aksi ini (DASBOR-V2-REVISI §P9). Asal-usulnya tetap terbaca dari
            // `meta.sumber`.
            $this->audit->log('document.approve', $document->id, [
                'status' => 'published',
                'doc_number_final' => $document->doc_number_final,
                'sumber' => 'salin_arsip',
            ]);
        });

        $this->kabarkanTerbit($document);
    }

    /**
     * Kabar "dokumen kini Berlaku" — ke SEMUA yang berkepentingan.
     *
     * Sebelumnya hanya pembuat utama yang tahu. Tiga celahnya ditutup di sini:
     *
     *  • PEMBUAT TAMBAHAN — ikut menyusun, tapi tak pernah menerima kabar apa
     *    pun tentang dokumennya (FITUR-BARU-v4 §6).
     *  • MD — meloloskan dokumen lalu tak pernah tahu nasib akhirnya. Tanpa itu
     *    ia tak bisa belajar apa pun dari keputusannya sendiri.
     *  • NON-STAFF SEDEPARTEMEN — justru orang yang harus MENJALANKAN prosedur
     *    ini. Merekalah alasan Fase C ada: dokumen sah yang tak diketahui
     *    lapangan sama saja dengan tidak ada. Bel saja, TIDAK email — satu
     *    dokumen terbit bisa berarti puluhan email sekaligus, dan email massal
     *    adalah cara tercepat membuat orang menyaring semua email SmartPro.
     *
     * Tinggal di service, bukan di ApprovalController, sejak salinan dokumen
     * lama (butir 7) juga menerbitkan dokumen tanpa melewati controller itu.
     */
    public function kabarkanTerbit(Document $document): void
    {
        Notification::send($document->penyusun(), new DocumentNotification(
            $document, "Dokumen {$document->doc_number} disetujui dan kini Berlaku.", 'bi-check-circle', 'documents.show',
            penting: true,
        ));

        // MD hanya terlibat pada jenis yang memang melewatinya (saat ini SOP).
        if ($document->perluTinjauanMd()) {
            Notification::send(User::peninjauMd()->get(), new DocumentNotification(
                $document, "Dokumen {$document->doc_number} yang Anda loloskan kini Berlaku.", 'bi-check-circle', 'documents.show'
            ));
        }

        $lapangan = User::where('status', 'active')
            ->where('department_id', $document->department_id)
            ->where('jabatan', User::JABATAN_STAFF)
            ->get();

        Notification::send($lapangan, new DocumentNotification(
            $document, "Dokumen baru Berlaku di departemen Anda: {$document->doc_number} — {$document->title}.",
            'bi-folder-check', 'documents.show'
        ));
    }

    /**
     * Usulan baris lembar CATATAN REVISI — isi tombol "Revisi" di langkah Log
     * Revisi (butir 7b).
     *
     * SATU BARIS PER BAB YANG BERUBAH (keputusan pemilik C2). Kolom Hal. ditulis
     * `x - y` bila babnya memakan lebih dari satu halaman, bukan dipecah jadi
     * beberapa baris. Kolom Catatan sengaja dibiarkan KOSONG — itu bagian yang
     * memang keputusan manusia; yang diotomatiskan hanya nomor, tanggal, dan
     * halaman.
     *
     * @return array{no_rev:int, tanggal:string, baris:array<int, array{bab:string, halaman:string}>}
     */
    public function usulanCatatanRevisi(Document $document, PdfRenderer $pdf): array
    {
        [, $noRevisi] = $document->revisiSaatKirim();
        $peta = $pdf->petaHalamanBab($document);
        // Label BERNOMOR menurut keadaan dokumen ini: kalau tidak, lembar
        // Catatan Revisi menyebut "VI. AKTIVITAS" untuk bab yang tercetak "V".
        $label = collect(SchemaService::untuk($document)->allSections())->pluck('label', 'key');

        return [
            'no_rev' => $noRevisi,
            'tanggal' => now()->toDateString(),   // WITA (config/app.php)
            'baris' => collect(self::babBerubah($document))
                ->map(fn (string $key) => [
                    'bab' => (string) ($label[$key] ?? $key),
                    'halaman' => self::rentangHalaman($peta[$key] ?? null),
                ])
                ->values()->all(),
        ];
    }

    /** "3" bila sehalaman, "3 - 5" bila lebih; kosong bila babnya tak terukur. */
    private static function rentangHalaman(?array $rentang): string
    {
        if ($rentang === null) {
            return '';
        }

        return $rentang[0] === $rentang[1] ? (string) $rentang[0] : $rentang[0].' - '.$rentang[1];
    }

    /**
     * Bab (`section_key`) yang isinya BERBEDA dari versi yang sedang direvisi.
     *
     * Pembandingnya dokumen Berlaku itu sendiri, bukan `document_versions`:
     * versi lama berstatus "Sedang Direvisi" dan tak bisa disunting siapa pun,
     * jadi isinya masih persis seperti saat snapshot diambil — dan membacanya
     * langsung tak perlu membongkar JSON snapshot.
     *
     * `catatan_revisi` dikecualikan: lembar itu memang selalu berbeda (barisnya
     * yang sedang disusun), jadi ia akan selalu melaporkan dirinya sendiri
     * sebagai perubahan.
     *
     * @return array<int, string>
     */
    public static function babBerubah(Document $draft): array
    {
        $lama = $draft->revisesDocument?->contentMap() ?? [];
        $baru = $draft->contentMap();

        return collect(array_keys($baru + $lama))
            ->reject(fn (string $key) => $key === 'catatan_revisi')
            ->filter(fn (string $key) => json_encode(self::baku($baru[$key] ?? null)) !== json_encode(self::baku($lama[$key] ?? null)))
            ->values()->all();
    }

    /**
     * Bentuk baku isi bab untuk PEMBANDINGAN, bukan untuk disimpan.
     *
     * Spasi di ujung dan baris/kolom kosong dibuang — mengetik ulang teks yang
     * sama dengan satu spasi tambahan bukan revisi. Urutan ITEM dipertahankan
     * (menukar urutan langkah adalah perubahan nyata), tapi urutan KUNCI tidak
     * (itu urusan penyimpanan JSON, bukan isi).
     */
    private static function baku(mixed $nilai): mixed
    {
        if (! is_array($nilai)) {
            return trim((string) $nilai);
        }

        $list = array_is_list($nilai);
        $out = [];
        foreach ($nilai as $kunci => $item) {
            $item = self::baku($item);
            if ($item === '' || $item === []) {
                continue;
            }
            $out[$kunci] = $item;
        }

        if ($list) {
            return array_values($out);
        }

        ksort($out);

        return $out;
    }

    /** Simpan snapshot isi + meta dokumen ke document_versions (jejak audit). */
    public function snapshot(Document $document): void
    {
        $document->versions()->create([
            'no_revisi' => $document->no_revisi,
            'created_by' => $document->created_by,
            'snapshot_json' => [
                'title' => $document->title,
                'doc_number' => $document->doc_number,
                'no_revisi' => $document->no_revisi,
                // Edisi ikut direkam: pasangan (edisi, revisi) — bukan revisi
                // sendirian — yang menentukan sel mana di daftar induk (butir 6).
                // Tanpa edisi, Edisi 2 Rev 0 tak bisa dibedakan dari Edisi 1 Rev 0.
                'edisi' => $document->edisi,
                // Dokumen LAMA (butir 0) tak punya `contents` — seluruh isinya
                // ADA DI BERKASNYA. Tanpa baris ini, snapshot versi arsip yang
                // direvisi tersimpan kosong melompong, dan riwayat versinya
                // kehilangan satu-satunya petunjuk ke dokumen aslinya.
                'arsip_path' => $document->arsip_path,
                'status' => $document->status,
                'reviewer_id' => $document->reviewer_id,
                'approver_id' => $document->approver_id,
                'published_at' => $document->published_at?->toDateTimeString(),
                'contents' => $document->contentMap(),
            ],
        ]);
    }
}
