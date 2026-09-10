<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArsipDocumentRequest;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Notifications\DocumentNotification;
use App\Services\AuditService;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use App\Services\DocumentWizard;
use App\Services\Print\PdfRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Dokumen LAMA (arsip, butir 0): PDF yang sudah terlanjur ada, didaftarkan apa
 * adanya alih-alih diketik ulang lewat wizard.
 *
 * Controller TERPISAH dari DocumentController — bukan karena alurnya rumit
 * (justru sebaliknya), melainkan karena DocumentController sudah 570-an baris
 * dan CLAUDE.md §3 meminta memecah di ~300. Ketiga aksinya juga tak berbagi
 * apa pun dengan wizard: tak ada langkah, tak ada schema, tak ada pratinjau.
 *
 * Yang TIDAK ada di sini, dan itu disengaja:
 *   • `create` — formulirnya menumpang documents/create (saklar "dokumen lama"),
 *     sesuai ketetapan pemilik. Satu pintu "Dokumen Baru", dua bentuk isian.
 *   • `destroy` — dokumen arsip berstatus Berlaku, jadi ia mengikuti jalur
 *     penghapusan yang sudah ada (Tidak Berlaku → Musnahkan). Jalur kedua hanya
 *     akan jadi pintu belakang yang melewati modal konfirmasi Admin.
 */
class DocumentArsipController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentParticipantResolver $peserta,
        private readonly AuditService $audit,
    ) {}

    /** Daftarkan dokumen lama → langsung Berlaku (keputusan pemilik B7). */
    public function store(ArsipDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $type = DocumentType::findOrFail($request->document_type_id);

        // Lingkup departemen ditegakkan SERVER, sama seperti DocumentController::store:
        // yang bukan Admin selalu mendaftarkan untuk departemennya sendiri, apa pun
        // yang dikirim formulir.
        $department = Department::findOrFail(
            $user->can('document.view_all') ? $request->department_id : $user->department_id
        );

        $document = $this->documents->createArsip(
            creator: $user,
            type: $type,
            department: $department,
            title: $request->title,
            number: $request->doc_number,
            arsipPath: $this->simpanBerkas($request->file('berkas'), $department->code, $type->code),
            edisi: (int) $request->edisi,
            noRevisi: (int) $request->no_revisi,
            tanggalEfektif: $request->tanggal_efektif,
        );

        $this->kabariAtasan($document, $user->name);

        // SOP/SP/IK singgah dulu di halaman Catatan Revisi (Fase H): versi web
        // dan versi cetak harus sinkron sejak hari pertama, dan lembar itulah
        // satu-satunya bagian dokumen lama yang belum ada di berkasnya.
        // FK/PX (kelas `unggahan`) dan JSA lewat — ketiganya memang tak pernah
        // punya lembar revisi.
        if ($document->jenisBerlembarRevisi()) {
            return redirect()->route('documents.arsip.catatan', $document);
        }

        return redirect()->route('documents.published')
            ->with('status', "Dokumen lama {$document->displayNumber()} terdaftar dan langsung Berlaku.");
    }

    /**
     * Langkah 1 dokumen lama SOP/SP/IK: isi lembar CATATAN REVISI sambil
     * MELIHAT berkas yang baru diunggah (Fase H).
     *
     * Pratinjaunya bukan hiasan: berkas itulah yang isinya diketik ulang di
     * wizard sesudah lembar ini tersimpan — tanpa melihatnya berdampingan,
     * pengetikannya jadi menebak.
     */
    public function catatan(Request $request, Document $document)
    {
        $this->pastikanBolehMencatat($request, $document);

        [$edisiKirim, $revisiKirim] = $document->revisiSaatKirim();

        return Inertia::render('Documents/Arsip/Catatan', [
            'document' => $this->barisArsip($document),
            'baris' => $document->contentMap()['catatan_revisi'] ?? [],
            // Nomor yang BERLAKU saat dokumen dikirim (butir 7a). Dulu dihitung
            // di dalam `documents/fields/_revision_log`; sejak lembar ini
            // dirender React, ia harus datang sebagai props — perhitungannya
            // membaca versi lama di basis data (pakem P5).
            'revisiKirim' => ['edisi' => $edisiKirim, 'revisi' => $revisiKirim],
        ]);
    }

    /**
     * Simpan lembar catatan lalu SALIN dokumennya ke web (Fase H, langkah 2).
     *
     * Sejak rencana pra-produksi Fase 2 (butir 7) tinggal SATU jalan: lembar
     * disimpan, lalu dokumennya diketik ulang di wizard dan berkas unggahan
     * tinggal jadi rujukan di panel kanan. Pilihan lama "gabung" (lembar
     * disisipkan ke PDF unggahan lewat `ArsipPenggabung`) dicabut atas
     * keputusan pemilik — berkasnya sendiri tak dihapus, ia cuma kehilangan
     * pemanggilnya.
     *
     * Kunci asing dari halaman V1 yang belum ikut disunting (`pilihan`,
     * `potong_halaman`, `halaman_awal`) sengaja DIABAIKAN, bukan ditolak 422:
     * pohon V1 dibekukan, dan mematikan sakelar V2 tak boleh membuat lembar ini
     * mustahil disimpan.
     */
    public function simpanCatatan(Request $request, Document $document): RedirectResponse
    {
        $this->pastikanBolehMencatat($request, $document);

        $request->validate([
            'edisi' => ['required', 'integer', 'min:1', 'max:99'],
            'no_revisi' => ['required', 'integer', 'min:0', 'max:'.DocumentService::MAKS_REVISI],
            'sections.catatan_revisi' => ['nullable', 'array'],
        ]);

        // Baris lembar + Edisi/Revisi ditulis oleh jalur yang SAMA dengan wizard
        // (DocumentWizard::persistRevisionLog), jadi bentuk tersimpannya mustahil
        // berbeda antara dokumen lama dan dokumen web.
        app(DocumentWizard::class)->persistRevisionLog($request, $document);

        // Dokumen diketik ulang di wizard = versi web MENGGANTIKAN berkas
        // pindaian, dan itu persis bentuk revisi Tipe B: draft baru bernomor
        // sama, berkas lama tersimpan sebagai versi sebelumnya. Isi lembar
        // catatan ikut tersalin (requestRevision menyalin seluruh section),
        // dan `revises_document_id`-lah yang kelak menunjuk PDF rujukan di
        // panel kanan.
        $draft = $this->documents->requestRevision($document, $request->user());

        // Penanda asal-usul: draft inilah yang kelak langsung Berlaku saat
        // dikirim, tanpa tinjauan (DocumentService::tandaiTerkirim). Revisi Tipe
        // B BIASA atas dokumen unggahan memenuhi semua syarat lain yang bisa
        // dihitung, jadi tanpa kolom ini auto-approve akan bocor ke sana.
        $draft->update(['salin_arsip_at' => now()]);

        return redirect()->route('documents.edit', $draft)->with('status',
            'Lembar Catatan Revisi tersimpan. Ketik ulang isi dokumen di sini — berkas unggahan tetap bisa dilihat di panel kanan (tab Referensi). Setelah dikirim, dokumen langsung Berlaku.');
    }

    /**
     * Halaman ini hanya untuk dokumen lama SOP/SP/IK milik pengunggahnya.
     *
     * Izinnya dipinjam dari perbaikan arsip (`bisaDisuntingArsipOleh`): yang
     * boleh mengganti berkasnya sudah boleh mengubah isi yang tercetak, jadi
     * memberinya izin kedua hanya akan menambah tempat yang bisa menyimpang.
     */
    private function pastikanBolehMencatat(Request $request, Document $document): void
    {
        abort_unless($document->bisaDisuntingArsipOleh($request->user()), 403,
            'Hanya pengunggah dokumen ini atau Admin yang boleh mengisi lembar Catatan Revisi.');
        abort_unless($document->isArsip() && $document->jenisBerlembarRevisi(), 404);
    }

    /** Formulir perbaikan metadata & berkas. */
    public function edit(Request $request, Document $document)
    {
        abort_unless($document->bisaDisuntingArsipOleh($request->user()), 403,
            'Hanya pengunggah dokumen ini atau Admin yang boleh memperbaikinya.');

        return Inertia::render('Documents/Arsip/Edit', [
            'document' => $this->barisArsip($document),
        ]);
    }

    /**
     * Dokumen lama seperlunya — dipakai layar Perbaiki DAN layar Catatan Revisi.
     *
     * Bukan `$document->load(...)` apa adanya: Blade lama mencetak enam kolom,
     * props Inertia menuliskan SELURUH kolom ke atribut `data-page` — termasuk
     * `arsip_path`, jalur berkas privat di disk `local` yang sengaja tak bisa
     * ditebak dari nomor dokumen (lihat {@see simpanBerkas()}). Membocorkannya
     * ke "view source" akan membatalkan lapis kedua itu diam-diam.
     *
     * `tanggal_efektif` sudah jadi `Y-m-d` di sini: `<input type="date">` hanya
     * menerima bentuk itu, dan `published_at` bertipe Carbon yang di JSON jadi
     * cap waktu ISO lengkap dengan jamnya.
     */
    private function barisArsip(Document $document): array
    {
        $document->loadMissing('type', 'department');

        return [
            'id' => $document->id,
            'nomor' => $document->displayNumber(),
            'judul' => $document->title,
            'jenis' => $document->type?->code,
            'jenis_nama' => $document->type?->name,
            'dept' => $document->department?->code,
            'dept_nama' => $document->department?->name,
            'edisi' => (int) ($document->edisi ?: 1),
            'no_revisi' => (int) $document->no_revisi,
            'tanggal_efektif' => $document->published_at?->format('Y-m-d'),
            'berlembar_revisi' => $document->jenisBerlembarRevisi(),
        ];
    }

    /**
     * Simpan perbaikan.
     *
     * Jenis & departemen SENGAJA tak bisa diubah (`prohibited` di
     * ArsipDocumentRequest): keduanya menyusun nomor dokumen DAN jalur berkas,
     * jadi mengubahnya berarti memindahkan berkas sekaligus membatalkan nomor
     * yang mungkin sudah beredar. Salah jenis = musnahkan & daftarkan ulang.
     */
    public function update(ArsipDocumentRequest $request, Document $document): RedirectResponse
    {
        $lama = [
            'doc_number' => $document->doc_number,
            'title' => $document->title,
            'edisi' => $document->edisi,
            'no_revisi' => $document->no_revisi,
        ];

        $perubahan = [
            'title' => $request->title,
            'doc_number' => $request->doc_number,
            // Nomor final ikut, kalau tidak dokumen tetap TAMPIL bernomor lama:
            // displayNumber() mendahulukan `doc_number_final`, dan itulah yang
            // membuat perbaikan tampak tak berpengaruh sama sekali di layar.
            'doc_number_final' => $request->doc_number,
            'edisi' => (string) (int) $request->edisi,
            'no_revisi' => (int) $request->no_revisi,
            'published_at' => $request->tanggal_efektif ?: $document->published_at,
        ];

        // Berkas pengganti ditulis DULU, yang lama dibuang SESUDAH baris
        // tersimpan: terbalik, satu galat penyimpanan meninggalkan dokumen
        // Berlaku tanpa berkas — dan itu tak bisa dibatalkan.
        $berkasLama = null;
        if ($berkas = $request->file('berkas')) {
            $berkasLama = $document->arsip_path;
            $perubahan['arsip_path'] = $this->simpanBerkas(
                $berkas, $document->department->code, $document->type->code
            );
        }

        $document->update($perubahan);

        if ($berkasLama) {
            Storage::disk('local')->delete($berkasLama);
        }

        $this->audit->log('document.arsip_edit', $document->id, [
            'sebelum' => $lama,
            'sesudah' => array_intersect_key($perubahan, $lama),
            'berkas_diganti' => (bool) $berkasLama,
        ]);

        return redirect()->route('documents.published')
            ->with('status', "Dokumen lama {$document->displayNumber()} diperbarui.");
    }

    /**
     * Simpan berkas ke disk `local` (privat) → `arsip/{DEPT}/{JENIS}/{acak}.pdf`.
     *
     * BUKAN disk `public`: routes/web.php:53 menyajikan storage/app/public tanpa
     * login sama sekali. Nama berkas diacak Laravel, jadi jalurnya tak bisa
     * ditebak dari nomor dokumen — tapi itu lapis kedua; lapis pertamanya adalah
     * disk yang memang tak punya URL.
     *
     * Kode dept & jenis disaring PdfRenderer::folderAman() — keduanya datang
     * dari database, dan satu kode bertanda `..` sudah cukup untuk menulis
     * berkas di luar folder arsip.
     */
    private function simpanBerkas(UploadedFile $berkas, ?string $dept, ?string $jenis): string
    {
        return $berkas->store(
            'arsip/'.PdfRenderer::folderAman($dept).'/'.PdfRenderer::folderAman($jenis),
            'local'
        );
    }

    /**
     * Kabari SH/DH departemennya — dokumen ini masuk TANPA melewati mereka.
     *
     * Lonceng saja, bukan email (`penting: false`): butir 8 kelak menuang
     * ratusan dokumen lama sekaligus, dan email per dokumen akan membuat orang
     * berhenti membaca email dari SmartPro — termasuk yang benar-benar genting.
     */
    private function kabariAtasan(Document $document, string $pendaftar): void
    {
        $atasan = $this->peserta->heads([$document->department_id]);

        Notification::send($atasan, new DocumentNotification(
            $document,
            "Dokumen lama {$document->displayNumber()} — {$document->title} didaftarkan {$pendaftar} dan langsung Berlaku.",
            'bi-check-circle',
            'documents.show',
        ));
    }
}
