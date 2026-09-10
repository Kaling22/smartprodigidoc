<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesDocumentAccess;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\DocumentType;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CatatanPerItem;
use App\Services\DocumentDistribution;
use App\Services\DocumentNumberService;
use App\Services\DocumentParticipantResolver;
use App\Services\DocumentService;
use App\Services\DocumentWizard;
use App\Services\InformasiDistribution;
use App\Services\Print\PdfRenderer;
use App\Services\ReviewerAvailability;
use App\Services\SchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * PEMBUATAN dokumen: daftar "Status Dokumen", wizard pengisian bertahap,
 * unggahan lampiran, pengiriman untuk ditinjau, penarikan, dan penghapusan draft.
 *
 * Cetak PDF ada di DocumentPdfController (mesinnya di App\Services\Print\PdfRenderer);
 * daur hidup pasca-terbit di DocumentRevisionController; orkestrasi pengisian
 * langkah di App\Services\DocumentWizard.
 */
class DocumentController extends Controller
{
    use AuthorizesDocumentAccess;

    public function __construct(
        private readonly DocumentService $documents,
        private readonly DocumentNumberService $numbering,
        private readonly DocumentWizard $wizard,
        private readonly PdfRenderer $pdf,
    ) {}

    /** "Status Dokumen Saya" — documents the user can see (strict per-dept, D12). */
    public function index(Request $request)
    {
        $user = $request->user();

        // GL "Dokumen Saya" (submenu): SELURUH dokumen BUATANNYA SENDIRI, segala
        // status — agar cocok dgn kartu dashboard "Dokumen Saya" (hapus tetap hanya
        // di draft). PJO/Admin & Non-Staff: hanya yang SEDANG BERPROSES (Berlaku ada
        // di menu "Dokumen Berlaku").
        $glOwnScope = ! $user->can('document.view_all') && $user->can('document.create');

        // withCount, bukan query per baris: lencana "ada masukan sejawat"
        // (PLAN-AKSES-v8 Fase 2) hanya butuh ANGKA, dan menghitungnya di dalam
        // @foreach akan menambah satu query untuk tiap dokumen di halaman.
        $query = Document::with('type', 'department', 'creator')
            ->withCount('masukanSejawat')
            ->latest();

        if ($glOwnScope) {
            // "Dokumen Saya": semua buatannya KECUALI versi lama yg SEDANG DIREVISI
            // (sudah digantikan draft revisinya) & yg OBSOLETE (ada di Tidak Berlaku).
            //
            // PEMBUAT TAMBAHAN ikut melihatnya di sini (FITUR-BARU-v4 §6). Sebelumnya
            // co-author tercatat di halaman pengesahan tapi tak pernah menerima apa
            // pun — dokumen yang ikut ia susun tak muncul di menu mana pun miliknya.
            $query->where(fn ($q) => $q->where('created_by', $user->id)
                ->orWhereHas('authors', fn ($a) => $a->where('user_id', $user->id)))
                ->whereNotIn('status', ['sedang_direvisi', 'obsolete']);
        } else {
            // `menunggu_nonaktif` ikut disembunyikan (Fase F): dokumennya masih
            // Berlaku, jadi tempatnya di menu "Dokumen Berlaku" — bukan di daftar
            // "yang sedang berproses".
            $query->whereNotIn('status', ['published', 'sedang_direvisi', 'menunggu_nonaktif', 'obsolete']);
            // PJO/Admin lihat 7 dept (+filter dept); Non-Staff read-only se-departemen.
            if ($user->can('document.view_all')) {
                if ($dept = $request->input('department_id')) {
                    $query->where('department_id', $dept);
                }
            } else {
                $query->where('department_id', $user->department_id);
            }
        }

        // Search + filter (Fase 7).
        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('doc_number', 'like', "%{$q}%")->orWhere('title', 'like', "%{$q}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->input('type')) {
            $query->whereHas('type', fn ($t) => $t->where('code', $type));
        }

        // Sumber dokumen (butir 0): disusun lewat wizard vs diunggah sebagai
        // dokumen lama. Halaman INI saja yang punya pemilihnya — "Dokumen
        // Berlaku" & "Status Dokumen Departemen" sengaja menampilkan keduanya
        // bercampur, karena di sana yang dicari orang adalah dokumen yang
        // berlaku, bukan cara dokumen itu masuk.
        $sumber = $request->input('sumber');
        if ($sumber === 'wizard') {
            $query->whereNull('arsip_path');
        } elseif ($sumber === 'unggahan') {
            $query->whereNotNull('arsip_path');
        }

        // Lencana masukan sejawat hanya ditawarkan kepada yang PASTI lolos
        // penjaga MasukanSejawatController::show — pembuatnya sendiri &
        // pengawas lintas-dokumen. Lencana yang diklik lalu berakhir 403 lebih
        // buruk daripada tak ada. (Pembuat TAMBAHAN tetap sampai lewat lonceng.)
        $lihatSejawat = $user->can('document.view_all');

        return Inertia::render('Documents/Index', [
            // `through()`, BUKAN `map()`: memetakan isi halaman tanpa
            // membongkar bentuk paginator (pelajaran Fase 6).
            'documents' => $query->urut($request->sort, $request->dir)->paginate(15)->withQueryString()
                ->through(fn (Document $doc) => $doc->barisDaftar($user) + [
                    'milik' => $doc->created_by === $user->id && $user->can('document.create'),
                    // null = lencananya tak ditawarkan sama sekali; 0 = boleh
                    // tapi memang belum ada masukan.
                    'masukan_sejawat' => $lihatSejawat || $doc->created_by === $user->id
                        ? (int) ($doc->masukan_sejawat_count ?? 0)
                        : null,
                ]),
            'filters' => $request->only('q', 'status', 'type', 'department_id', 'sumber'),
            'types' => DocumentType::orderBy('code')->pluck('code'),
            'departments' => $user->can('document.view_all') ? Department::orderBy('code')->get() : collect(),
            // GL "Dokumen Saya" memuat segala status → filter status tampilkan semua.
            'showAllStatuses' => $glOwnScope,
            /*
            | Tiga hal di bawah dulu dipanggil DARI DALAM Blade. Ketiganya
            | keputusan server, bukan tampilan:
            |
            |  - `statusOpsi` — status mana yang boleh disaring. GL "Dokumen
            |    Saya" memuat segala status; sisanya hanya yang berproses
            |    (Berlaku punya menunya sendiri). Labelnya tetap datang dari
            |    props global `statusLabels` (pakem P3).
            |  - `jenisBaru` — jenis pertama yang BOLEH ia susun. Tanpa satu
            |    pun, tombol "Dokumen Baru" tak ditawarkan — bukan ditawarkan
            |    lalu berakhir 403 (PLAN-AKSES-v7 Fase 4c).
            |  - `prefix` — contoh nomor di kotak cari, dari setelan site.
            */
            'statusOpsi' => $glOwnScope
                ? ['draft', 'waiting_for_review', 'in_review', 'rejected', 'pending_approval', 'published']
                : ['draft', 'waiting_for_review', 'in_review', 'rejected', 'pending_approval'],
            'jenisBaru' => $user->jenisPertamaBoleh(),
            'prefix' => Pengaturan::prefix(),
        ]);
    }

    /**
     * "Dokumen Baru — Langkah 1" (Task 2.3). The document type is chosen from
     * the sidebar dropdown and passed as ?type=CODE; the form itself no longer
     * has a type selector. Inactive types (schema not ready) show a notice.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $typeCode = strtoupper($request->query('type', 'SOP'));
        $type = DocumentType::where('code', $typeCode)->first();

        abort_if(! $type, 404, "Jenis dokumen {$typeCode} tidak dikenal.");

        // Schema not ready yet (IK/SP/JSA await samples). Jenis UNGGAHAN (FK/PX)
        // dikecualikan: ia memang TAK PERNAH punya bab, jadi schema kosongnya
        // bukan tanda "belum siap" melainkan bentuk normalnya.
        if (! $type->is_active || (! $type->isUnggahan() && empty($type->schema_json['steps'] ?? []))) {
            return Inertia::render('Documents/Unavailable', [
                'type' => ['code' => $type->code, 'name' => $type->name],
                // Jenis yang ditawarkan mengikuti profil aksesnya (Fase 4c).
                // Dihitung di sini, bukan di TSX: `jenisPertamaBoleh()` membaca
                // profil akses, dan itu keputusan otorisasi (pakem P4).
                'jenisBaru' => $user->jenisPertamaBoleh(),
            ]);
        }

        // Profil akses (PLAN-AKSES-v7 Fase 4). Penjaga sesungguhnya ada di
        // StoreDocumentRequest/ArsipDocumentRequest — yang ini supaya URL yang
        // ditempel langsung tak membuka formulir yang PASTI gagal saat dikirim.
        abort_unless($user->bolehBuatJenis($typeCode), 403,
            "Anda belum berwenang menyusun dokumen {$typeCode}. Hubungi Admin untuk penetapan profil akses.");

        // Non-admins are locked to their own department; admin may pick any.
        $canChooseDept = $user->can('document.view_all');
        // Admin memilih dari departemen AKTIF saja (Fase 5b). Cabang non-admin
        // sengaja tidak disaring: ia tak memilih apa pun — departemennya sendiri
        // satu-satunya isi daftar, dan menyaringnya di sini hanya menghasilkan
        // formulir tanpa departemen alih-alih pesan yang bisa dimengerti.
        $departments = $canChooseDept ? Department::aktif()->orderBy('code')->get() : Department::where('id', $user->department_id)->get();
        $defaultDept = $departments->firstWhere('id', $user->department_id) ?? $departments->first();

        $numberPreview = $defaultDept ? $this->numbering->generate($type, $defaultDept) : '—';

        // FK/PX hanya punya SATU bentuk isian: unggah. Saklar "dokumen lama"
        // dikunci menyala dan disembunyikan — pilihan yang tak punya alternatif
        // bukan pilihan, ia hanya undangan untuk salah.
        $unggahanSaja = $type->isUnggahan();

        return Inertia::render('Documents/Create', [
            'type' => ['id' => $type->id, 'code' => $type->code, 'name' => $type->name],
            'departments' => $departments->map(fn (Department $d) => [
                'id' => $d->id, 'code' => $d->code, 'name' => $d->name,
            ])->values(),
            'defaultDept' => $defaultDept?->id,
            'canChooseDept' => $canChooseDept,
            'numberPreview' => $numberPreview,
            'unggahanSaja' => $unggahanSaja,
            // Blade lama memanggil Pengaturan::prefix() langsung di layar; di
            // Inertia ia harus lewat props supaya pola nomor tetap ditentukan
            // Pengaturan, bukan ditulis ulang di TSX.
            'prefix' => Pengaturan::prefix(),
            /*
             * Saran nomor bebas sesudah kiriman ditolak karena nomornya bentrok
             * (rencana pra-produksi Fase 5 / butir 4). Diletakkan flash oleh
             * StoreDocumentRequest / ArsipDocumentRequest, jadi ia hanya terisi
             * pada request TEPAT sesudah penolakan — halaman yang dibuka biasa
             * mendapat null dan dialognya tak pernah muncul tanpa sebab.
             *
             * Nomornya datang dari DocumentNumberService, bukan dihitung TSX:
             * "nomor bebas berikutnya" adalah aturan penomoran, dan salinan
             * keduanya di klien pasti menyimpang (CLAUDE.md §4).
             */
            'nomorSaran' => $request->session()->get('nomorSaran'),
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $type = DocumentType::findOrFail($request->document_type_id);

        abort_unless($type->is_active, 422, 'Jenis dokumen ini belum tersedia.');

        // Jenis unggahan tak punya bab: draft wizard untuknya adalah dokumen
        // yang tak akan pernah bisa diisi maupun dicetak. Pintunya
        // DocumentArsipController::store, dan itu satu-satunya.
        abort_if($type->isUnggahan(), 422, "Jenis {$type->code} didaftarkan lewat unggah berkas, bukan wizard.");

        // Enforce department scope: non-admins can only create for their own dept.
        $departmentId = $user->can('document.view_all') ? $request->department_id : $user->department_id;
        $department = Department::findOrFail($departmentId);

        $document = $this->documents->createDraft(
            creator: $user,
            type: $type,
            department: $department,
            title: $request->title,
            manualNumber: $request->boolean('doc_number_manual') ? $request->doc_number : null,
        );

        return redirect()->route('documents.edit', $document)
            ->with('status', "Draft dibuat dengan nomor {$document->doc_number}. Lanjutkan pengisian.");
    }

    /**
     * Menolak peninjau yang sedang PENUH atau OFF (FITUR-BARU-v4 §6).
     *
     * Diperiksa hanya saat pilihannya BERUBAH. Peninjau yang mengajukan off
     * SESUDAH ditunjuk tetap memegang dokumennya — kalau setiap penyimpanan
     * langkah ikut memeriksa, dokumen yang sah mendadak gagal disimpan dengan
     * pesan yang membingungkan pembuatnya.
     *
     * Pilihan yang ditolak dikembalikan ke nilai lama di dalam request, jadi
     * `persistStep` tetap menyimpan isi langkah ini tanpa ikut menyimpan
     * peninjau yang tak sah. Ini penjaga SISI SERVER; `disabled` di papan hanya
     * lapis pertama.
     */
    private function saringPeninjauTakTersedia(Request $request, Document $document): ?string
    {
        $sections = $request->input('sections', []);
        $key = array_key_exists('peninjau_penyetuju', $sections) ? 'peninjau_penyetuju' : 'peninjau';
        $baru = $sections[$key] ?? null;

        if (! $baru || (int) $baru === (int) $document->reviewer_id) {
            return null;
        }

        // Id ngawur bukan urusan di sini — DocumentParticipantResolver yang
        // menolaknya saat Kirim.
        $user = User::find($baru);
        if (! $user) {
            return null;
        }

        $ketersediaan = app(ReviewerAvailability::class)->untuk(collect([$user]), $document)[$user->id];
        if ($ketersediaan['tersedia']) {
            return null;
        }

        $sections[$key] = $document->reviewer_id;
        $request->merge(['sections' => $sections]);

        return "{$user->name} belum bisa dipilih sebagai peninjau — {$ketersediaan['alasan']}. Pilihan sebelumnya dipertahankan; isi langkah ini tetap tersimpan.";
    }

    /**
     * Peninjau dokumen ini sudah mencapai batas beban? (FITUR-BARU-v4 §6)
     *
     * Dipanggil saat KIRIM. Dokumen ini sendiri tak ikut dihitung — ia baru
     * akan masuk antrean, jadi menghitungnya akan membuat dokumen kedua selalu
     * tertolak pada batas 2.
     *
     * @return string|null pesan penolakan, atau null bila masih muat
     */
    private function tolakBilaPeninjauPenuh(Document $document): ?string
    {
        $batas = (int) config('smartpro.peninjau.batas_dokumen', 2);

        if ($batas <= 0 || ! $document->reviewer_id) {
            return null;
        }

        $beban = app(ReviewerAvailability::class)
            ->bebanPeninjau([$document->reviewer_id], $document)[$document->reviewer_id] ?? 0;

        if ($beban < $batas) {
            return null;
        }

        $nama = $document->reviewer?->name ?? 'Peninjau yang dipilih';

        return "{$nama} sudah memegang {$beban} dokumen — batasnya {$batas}. Pilih peninjau lain, atau tunggu sampai salah satu dokumennya selesai ditinjau.";
    }

    /** Halaman detail dokumen + timeline vertikal riwayat dari audit_logs (v3.1 §9). */
    public function show(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        $timeline = AuditLog::where('document_id', $document->id)
            ->with('user')->orderBy('created_at')->get();

        // Masukan lapangan (FITUR-BARU-v4 §3). Semua KECUALI Non-Staff melihat
        // seluruhnya; Non-Staff hanya masukan MILIKNYA beserta balasannya.
        //
        // Sejak Fase C MELIHAT dan MENINDAK dipisah: SH/DH & PJO tetap membaca
        // (dashboardPenuh), tapi yang membalas hanya GL/MD/Admin
        // (`document.feedback_respond`, dengan batas departemennya sendiri).
        $user = $request->user();
        $bolehLihatSemua = $user->dashboardPenuh();
        $bolehMembalas = $user->can('document.feedback_respond')
            && ($user->can('document.view_all') || $document->department_id === $user->department_id);
        $masukan = $document->feedback()->with('user', 'replier')
            ->unless($bolehLihatSemua, fn ($q) => $q->where('user_id', $user->id))
            ->latest()->get();

        // Ditandai dibaca SESUDAH diambil, supaya yang benar-benar baru masih
        // tersorot di kunjungan ini. Lencana tetap hidup sampai masukannya
        // ditindak (DocumentFeedback::scopeBelumDitindak).
        //
        // HANYA orang sedepartemen YANG MENINDAK yang menghabiskan penanda
        // "baru". MD & Admin melihat 7 departemen; kalau mereka ikut menandai,
        // GL yang seharusnya menindak kehilangan sorotannya hanya karena
        // dokumennya sempat dibuka orang lain.
        if ($bolehMembalas && $document->department_id === $user->department_id) {
            $document->feedback()->where('status', 'baru')->update(['status' => 'dibaca']);
        }

        $document->load('creator', 'reviewer', 'approver', 'department', 'type');

        // Distribusi (v5 Fase C): dicatat SESUDAH otorisasi, dan hanya untuk
        // dokumen Berlaku — DocumentDistribution::catat() sendiri yang menyaring,
        // supaya aturan "apa yang layak dilacak" cuma ada di satu tempat.
        $distribusi = app(DocumentDistribution::class);
        $distribusi->catat($document, $user);

        // Panel distribusi untuk SH/DH + PJO/Admin/MD — sama dengan halaman
        // penuhnya. Sejak Fase C mereka tak lagi memegang
        // `document.request_revision` yang dulu jadi penjaganya.
        $lihatDistribusi = $document->status !== 'draft' && $user->bisaLihatDistribusi();

        $bolehRevisi = $document->bisaDirevisiOleh($user);

        return Inertia::render('Documents/Show', [
            /*
            | Model DIRATAKAN, tak dikirim mentah. `creator`/`reviewer`/`approver`
            | adalah model User — dan model User memuat `password` +
            | `remember_token`, yang di props Inertia terbaca siapa pun lewat
            | "view source" (kelas kesalahan yang sama dengan Fase 6 & 7).
            | Yang dikirim hanya yang memang digambar layar.
            */
            'document' => $document->barisDaftar($user) + [
                'jenis_nama' => $document->type?->name,
                'dept_nama' => $document->department?->name,
                'pembuat' => $document->creator?->nameWithJabatan(),
                // Kunci ke `ketersediaanPembuat`, yang diindeks id PENGGUNA.
                'pembuat_id' => $document->created_by,
                'peninjau' => $document->reviewer?->nameWithJabatan(),
                'penyetuju' => $document->approver?->nameWithJabatan(),
                'terbit' => $document->published_at?->format('d/m/Y H:i'),
                'boleh_revisi' => $bolehRevisi,
                'boleh_beri_masukan' => $document->bisaDiberiMasukanOleh($user),
                // Angka yang DIJANJIKAN, bukan `no_revisi + 1`: revisi berguling
                // di angka 5 menjadi Edisi+1 Rev 0 (DocumentService::nextEditionRevision),
                // dan aturan yang sama dipakai draftnya saat DIKIRIM — jadi janji
                // di layar mustahil berbeda dari angka yang tersimpan.
                'janji_revisi' => DocumentService::nextEditionRevision(
                    max(1, (int) ($document->edisi ?: 1)), (int) $document->no_revisi,
                ),
            ],
            'timeline' => $timeline->map(fn (AuditLog $log) => [
                'id' => $log->id,
                // Aksi tak dikenal merangkai labelnya dari namanya sendiri —
                // sama persis dengan cadangan di Blade lama.
                'meta' => AuditLog::AKSI_META[$log->action]
                    ?? [str_replace(['document.', '_'], ['', ' '], $log->action), 'bi-dot', 'secondary'],
                'oleh' => $log->user->name ?? 'Sistem',
                // HANYA URL fotonya, bukan model User. Aturan yang sama dengan
                // wajah "Paling Produktif" di dashboard (DASBOR-V2-REVISI §P4):
                // barisan wajah adalah tempat paling gampang membocorkan satu
                // tabel penuh ke atribut `data-page`. `photoUrl()` dipanggil di
                // SINI karena ia menyusun URL disk publik, dan tata letak
                // storage tak punya urusan di peramban.
                'foto' => $log->user?->photoUrl(),
                'waktu' => $log->created_at?->format('d/m/Y H:i'),
            ])->values(),
            'masukan' => $masukan->map(fn ($m) => self::barisMasukan($m))->values(),
            // Yang belum ditindak dipilih di SERVER, bukan disaring ulang di
            // klien: daftar inilah yang jadi kotak centang "adopsi jadi alasan
            // revisi" (§5), dan aturan mana-yang-boleh-diadopsi hanya boleh
            // punya satu tempat (DocumentFeedback::scopeBelumDitindak).
            'masukanBelumDitindak' => $masukan->whereIn('status', ['baru', 'dibaca'])
                ->map(fn ($m) => self::barisMasukan($m))->values(),
            'bolehMembalasMasukan' => $bolehMembalas,
            // Panel "Masukan Lapangan" tetap dirender bagi yang berhak MELIHAT,
            // walau sedang kosong — itulah bedanya "belum ada masukan" dari
            // "Anda memang tak melihat kanal ini" (Fase C).
            'bolehLihatMasukan' => $bolehLihatSemua,
            'distribusi' => $lihatDistribusi ? $distribusi->cakupanSatu($document) : null,
            'distribusiRincian' => $lihatDistribusi ? self::ratakanRincian($distribusi->rincian($document)) : null,
            // Keterangan "pembuat sedang off" di jendela Ajukan Revisi (§6).
            'ketersediaanPembuat' => self::jadwalPembuat(collect([$document->creator])),
        ]);
    }

    /**
     * "Pembuat sedang cuti sampai …" untuk jendela Ajukan Revisi (§6).
     *
     * Diratakan di sini, bukan dikirim utuh: {@see ReviewerAvailability::untuk}
     * mengembalikan beban, ambang, dan pita 14 hari yang tak satu pun digambar
     * jendela itu — dan `kembali` di dalamnya bertipe Carbon, yang di JSON jadi
     * cap waktu ISO. Nama harinya HARUS dirangkai server: `translatedFormat`
     * membaca locale aplikasi, dan peramban tak tahu apa-apa soal itu.
     *
     * Dipakai halaman detail DAN daftar Dokumen Berlaku — keterangan yang sama
     * di dua layar, jadi bentuknya cuma ada di satu tempat.
     *
     * @param  Collection<int, ?User>  $pembuat
     * @return array<int, array{nama: ?string, jenis: ?string, kembali: ?string}>
     */
    public static function jadwalPembuat(Collection $pembuat): array
    {
        $orang = $pembuat->filter()->unique('id')->values();

        if ($orang->isEmpty()) {
            return [];
        }

        $hasil = [];

        foreach (app(ReviewerAvailability::class)->untuk($orang) as $id => $k) {
            if ($k['off']) {
                $hasil[$id] = [
                    'nama' => $orang->firstWhere('id', $id)?->name,
                    'jenis' => mb_strtolower((string) $k['jenis']),
                    'kembali' => $k['kembali']?->translatedFormat('l, j F'),
                ];
            }
        }

        return $hasil;
    }

    /**
     * Satu masukan lapangan, diratakan.
     *
     * Dipakai halaman detail DAN daftar Dokumen Berlaku (lewat
     * DocumentRevisionController), jadi bentuknya cuma ada di satu tempat —
     * `documents/_masukan-list.blade.php` dulu dipakai dua layar dengan alasan
     * yang sama persis.
     */
    public static function barisMasukan(DocumentFeedback $m): array
    {
        return [
            'id' => $m->id,
            'nomor' => $m->feedback_number,
            'isi' => $m->isi,
            'status' => $m->status,
            'status_label' => $m->statusLabel(),
            'oleh' => $m->user?->nameWithJabatan(),
            'waktu' => $m->created_at?->format('d/m/Y H:i'),
            'balasan' => $m->balasan,
            'pembalas' => $m->replier?->nameWithJabatan(),
        ];
    }

    /**
     * Rincian pembaca (siapa sudah/belum membaca), diratakan.
     *
     * `sudah` berisi baris DocumentRead yang menggendong relasi `user` —
     * model User utuh beserta `password`-nya. Yang sampai ke layar hanya nama,
     * jabatan pendek, platform, dan waktunya.
     *
     * PUBLIC STATIC sejak Fase 11: {@see DocumentDistributionController::rincianInformasi}
     * meratakan `InformasiRead` yang bentuknya SENGAJA identik
     * ({@see InformasiDistribution::rincian()}), dan menyalin
     * pemerataan ini ke sana berarti dua tempat yang harus sama-sama diingat
     * saat kolom berikutnya ditambahkan — termasuk saat kolom itu `password`.
     */
    public static function ratakanRincian(array $rincian): array
    {
        return [
            'sudah' => $rincian['sudah']->map(fn ($r) => [
                'nama' => $r->user->name ?? '—',
                'jabatan' => $r->user?->jabatanShort(),
                // URL foto SAJA — lihat catatan di `timeline` (§P4).
                'foto' => $r->user?->photoUrl(),
                'platform' => $r->platform,
                'waktu' => $r->last_read_at?->format('d/m/Y H:i'),
            ])->values(),
            'belum' => $rincian['belum']->map(fn ($u) => [
                'nama' => $u->name,
                'jabatan' => $u->jabatanShort(),
                'foto' => $u->photoUrl(),
            ])->values(),
        ];
    }

    /** 2-step wizard — renders the current step's fields from the schema (PRD v2 §4). */
    public function edit(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        // Dokumen LAMA (butir 0) tak punya isi bab — seluruhnya ada di
        // berkasnya. Merender wizard untuknya menghasilkan formulir kosong yang
        // membingungkan (dan mengundang orang mengisinya, padahal isian itu tak
        // akan pernah muncul di PDF-nya). Perbaikannya lewat jalur arsip.
        if ($document->isArsip()) {
            return redirect()->route('documents.show', $document);
        }

        /*
        | Form memakai schema PENUH — sengaja BUKAN SchemaService::untuk().
        | Penomoran ulang MEMBUANG bab opsional yang dimatikan; kalau formulir
        | ikut memakainya, bab flowchart lenyap dari layar bersama sakelarnya
        | dan tak ada lagi cara menyalakannya kembali. Di sini babnya tetap
        | dirender, hanya DISEMBUNYIKAN (x-show) — dan isinya tetap ikut
        | terkirim, jadi mematikan sakelar tak pernah menghapus ketikan.
        */
        $schema = SchemaService::for($document->type);
        $editable = $this->isEditable($document, $request);
        $contentMap = $document->contentMap();

        /*
        | Dua varian label bab, dihitung PHP, dipilih Alpine (§5.4 rencana):
        | "VI. AKTIVITAS" saat flowchart menyala, "V. AKTIVITAS" saat mati.
        | JS tak pernah menomori sendiri — ia cuma sakelar dua posisi.
        */
        $sakelar = collect($schema->allSections())->pluck('toggle_key')->filter();
        $schemaMati = $schema->denganPenomoran($sakelar->mapWithKeys(fn ($k) => [$k => '0'])->all());
        $babAktif = $sakelar->every(fn ($k) => SchemaService::babAktif($contentMap, $k));

        // Draft revisi Tipe B punya SATU langkah ekstra di akhir: form log revisi
        // (lembar CATATAN REVISI). Simpan/Kirim pindah ke langkah itu. JSA
        // dikecualikan — lihat Document::usesRevisionLog().
        $totalSteps = $schema->stepCount() + ($document->usesRevisionLog() ? 1 : 0);

        // Read-only viewers navigate via ?view_step (no DB write) — cegah bug 403
        // saat tombol Kembali di dokumen non-draft (v3.1 §6 bug fix).
        $currentStep = $editable
            ? $document->current_step
            : (int) $request->query('view_step', $document->current_step);
        $currentStep = max(1, min($currentStep, $totalSteps));

        /*
        | Umpan balik tetap terlihat selama revisi (§3.3), DUA lapis:
        |
        |  1. rangkuman — `Review.summary`. Tak punya jangkar item, dan memang
        |     tak boleh punya: ia juga wadah alasan Ajukan Revisi Tipe B
        |     (`[Pengaju Revisi] …`) dan catatan umum peninjau.
        |  2. catatan per-item — menempel di isian yang dikomentari, lewat
        |     `section_key` + `item_ref` yang sejak awal memang tersimpan.
        |     Dulu `item_ref` dibuang di sini (groupBy section_key lalu
        |     pluck comment), sehingga catatan atas baris ke-3 Aktivitas jatuh
        |     jadi satu butir berlencana "aktivitas" di kepala halaman.
        |
        | Masukan sejawat ikut di lapis kedua — bentuknya sudah sama sejak
        | disimpan — TAPI hanya bagi yang berhak membacanya: wizard juga dibuka
        | penonton read-only, dan catatan antar-GL bukan milik mereka.
        */
        $catatan = app(CatatanPerItem::class);
        $catatanItem = $catatan->untuk($document, $document->bisaLihatMasukanSejawat($request->user()));
        $reviewSummary = $document->reviews()
            ->latest()
            ->get()
            ->firstWhere('decision', 'needs_revision')?->summary;

        $candidates = $this->wizard->userPickerCandidates($schema, $document);
        $ketersediaan = app(ReviewerAvailability::class)->untukPicker($candidates, $document);

        // Langkah virtual "Log Revisi" — di LUAR schema. Diputuskan di sini
        // (bukan di TSX) karena syaratnya `Document::usesRevisionLog()`, aturan
        // bisnis yang sama dengan yang dipakai saveStep/autosave.
        $isRevLogStep = $document->usesRevisionLog() && $currentStep > $schema->stepCount();

        /*
        | Dokumen LAMA yang sedang disalin ke web (Fase H): draft revisi yang
        | dokumen sumbernya berupa berkas unggahan. Berkas itulah rujukan yang
        | diketik ulang, jadi ia berdiri berdampingan dengan pratinjau sebagai
        | tab "Referensi".
        */
        $rujukan = $document->revisesDocument?->isArsip() ? $document->revisesDocument : null;

        return Inertia::render('Documents/Edit', [
            /*
            | Model dokumen TIDAK dikirim apa adanya. Props Inertia tertulis
            | seluruhnya di atribut `data-page` dan terbaca lewat "view source",
            | jadi tiap larik yang pindah dari Blade ke props wajib diperiksa
            | kolom per kolom (pelajaran Fase 6 & 7). Yang di bawah ini persis
            | yang dicetak kop wizard — tak lebih.
            */
            'document' => [
                'id' => $document->id,
                'nomor' => $document->displayNumber(),
                'nomorFinal' => $document->hasFinalNumber(),
                'judul' => $document->title,
                'jenis' => $document->type->code,
                'departemen' => $document->department->code,
                // Kunci status, bukan labelnya: rupa & label datang dari props
                // global `statusMeta`/`statusLabels` (pakem P3).
                'status' => $document->status,
                'pembuat' => $document->creator->name ?? '—',
                'edisi' => (int) ($document->edisi ?: 1),
                'noRevisi' => (int) $document->no_revisi,
            ],
            // Schema PENUH, apa adanya — inilah penggerak tunggal form (pakem
            // P1). React MEMBACANYA, tak pernah menyalin strukturnya jadi TSX.
            'schema' => $schema->raw(),
            'currentStep' => $currentStep,
            'totalSteps' => $totalSteps,
            'isRevLogStep' => $isRevLogStep,
            'contentMap' => $contentMap,
            'editable' => $editable,
            // Varian "bab opsional dimatikan": label & auto_number per bab,
            // plus judul langkahnya. Dibaca RepeatableGroup.tsx & Edit.tsx.
            'babAktif' => $babAktif,
            'labelMati' => collect($schemaMati->allSections())
                ->filter(fn ($s) => isset($s['key']))
                ->mapWithKeys(fn ($s) => [$s['key'] => [
                    'label' => $s['label'] ?? null,
                    'auto_number' => $s['auto_number'] ?? null,
                ]])->all(),
            'judulLangkahMati' => $schemaMati->stepTitle($currentStep),
            'candidates' => $this->wizard->propsKandidat($candidates),
            // Beban & jadwal tiap kandidat peninjau/penyetuju — dipakai papan
            // ketersediaan menggantikan dropdown (FITUR-BARU-v4 §6).
            'ketersediaan' => $this->wizard->propsKetersediaan($ketersediaan),
            // Section mana yang memakai papan, dan pada yang mana batas beban
            // memblokir. Dari SERVER, bukan diketik ulang di TSX (pakem P5).
            'papan' => ReviewerAvailability::PAPAN,
            'ambang' => config('smartpro.peninjau.ambang'),
            'userValues' => $this->wizard->userPickerValues($document),
            // Isi pemilih lampiran (bab VII SOP).
            'dokumenBerlaku' => $this->wizard->dokumenBerlaku($document),
            // Sidik isi cetakan — jadi `?v=` pada URL iframe pratinjau. Isi tetap
            // → URL tetap → peramban memakai cache-nya sendiri saat pindah
            // langkah, jadi panelnya tak berkedip dan PDF tak dirender ulang.
            'previewV' => $this->pdf->sidik($document),
            // `section_key` → `item_ref` → catatan. Digambar komponen field
            // masing-masing, tepat di bawah isian yang dikomentari.
            'catatanItem' => $catatanItem,
            // Yang TAK punya baris tempat menempel (itemnya sudah dihapus, atau
            // seksinya bertipe tanpa kotak catatan) — dikumpulkan di kepala
            // halaman supaya tak satu pun catatan hilang tanpa jejak.
            'catatanYatim' => $catatan->yatim($catatanItem, $schema),
            'reviewSummary' => $reviewSummary,
            'rujukanPdfUrl' => $rujukan ? route('documents.pdf', $rujukan) : null,
            // Nomor yang BERLAKU saat dokumen dikirim (butir 7a) — dipakai
            // langkah Log Revisi. null bila jenis ini tak memakai lembar itu.
            'revisiKirim' => $document->usesRevisionLog()
                ? array_combine(['edisi', 'revisi'], $document->revisiSaatKirim())
                : null,
            // Tgl. Terbit & Tgl. Revisi kop cetak — HANYA draft salinan arsip
            // (TEMUAN-F8). null pada draft lain, dan langkah Log Revisi memakai
            // null itu sebagai isyarat "jangan gambar kedua isian ini": batas
            // siapa yang boleh menulis tanggal ada di server (DocumentWizard),
            // dan layar cuma mengikutinya — bukan memutuskannya sendiri.
            'tanggalCetak' => $document->salin_arsip_at ? [
                'terbit' => ($document->published_at ?? $document->revisesDocument?->published_at)?->toDateString() ?? '',
                // Bawaan hari ini, boleh diubah manual (keputusan pemilik).
                'revisi' => $document->tanggal_revisi?->toDateString() ?? now()->toDateString(),
            ] : null,
        ]);
    }

    /**
     * Persist one wizard step. Handles Back / Langkah Berikutnya / Simpan / Kirim.
     * Content sections go to value_json; user_picker sections map to the
     * reviewer/approver columns and document_authors.
     */
    public function saveStep(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        abort_unless($this->isEditable($document, $request), 403, 'Dokumen tidak dapat diedit pada status ini.');

        $schema = SchemaService::for($document->type);
        $totalSteps = $schema->stepCount() + ($document->usesRevisionLog() ? 1 : 0);
        $step = (int) $request->input('step', $document->current_step);
        $action = $request->input('action', 'next');

        // Peninjau yang penuh/off tak boleh DITUGASI (FITUR-BARU-v4 §6). Disaring
        // SEBELUM disimpan, tapi isi langkahnya tetap dipersist — pengguna tak
        // boleh kehilangan ketikannya hanya karena salah pilih orang.
        $tolakan = $this->saringPeninjauTakTersedia($request, $document);

        // Langkah virtual (log revisi) berada DI LUAR schema — dipersist terpisah.
        if ($step > $schema->stepCount() && $document->usesRevisionLog()) {
            $this->wizard->persistRevisionLog($request, $document);
        } else {
            $this->wizard->persistStep($request, $schema, $step, $document);
        }

        if ($tolakan) {
            return back()->with('error', $tolakan);
        }

        // Kirim: validate the flow participants then move to review.
        if ($action === 'submit') {
            if (! $document->reviewer_id || ! $document->approver_id) {
                return back()->with('error', 'Peninjau dan Penyetuju wajib dipilih sebelum mengirim.');
            }

            // Peninjau & penyetuju harus sesuai matriks (jenis + dept + SHE/Plant).
            $resolver = app(DocumentParticipantResolver::class);
            if (! $resolver->isValidReviewer($document, $document->reviewer_id) || ! $resolver->isValidApprover($document, $document->approver_id)) {
                return back()->with('error', 'Peninjau/Penyetuju tidak sesuai aturan untuk jenis & departemen dokumen ini.');
            }

            // Batas beban ditegakkan LAGI di sini, dan inilah penjagaan yang
            // sebenarnya (FITUR-BARU-v4 §6). `reviewer_id` sudah ditulis sejak
            // dokumen masih draft, jadi menunjuk satu SH di BEBERAPA draft
            // sekaligus lolos pemeriksaan saat pemilihan — saat itu bebannya
            // memang masih nol. Batasnya baru benar-benar terasa ketika dokumen
            // MASUK antrean orangnya, yaitu di sini.
            //
            // Yang diperiksa HANYA batasnya, BUKAN status off: peninjau yang
            // mengajukan off sesudah ditunjuk tetap memegang dokumennya, jadi
            // memeriksa off di sini justru menggagalkan dokumen yang sah.
            if ($penuh = $this->tolakBilaPeninjauPenuh($document)) {
                return back()->with('error', $penuh);
            }

            $this->documents->tandaiTerkirim($document);

            return redirect()->route('documents.index')->with('status', $this->kabarTerkirim($document));
        }

        // Simpan: keep as draft, return to index.
        if ($action === 'save') {
            $document->save();

            return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} disimpan sebagai draft.");
        }

        // Back / Next navigation.
        $target = $action === 'back' ? $step - 1 : $step + 1;
        $document->current_step = max(1, min($target, $totalSteps));
        $document->save();

        // Preview tetap terbawa antar langkah (revisi #2) — panel tidak kosong.
        return redirect()->route('documents.edit', [$document, 'preview' => 1])->with('status', "Langkah {$step} tersimpan.");
    }

    /** Autosave (D7) — persists text sections for the current step without navigating. */
    public function autosave(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        if (! $this->isEditable($document, $request)) {
            return response()->json(['ok' => false], 422);
        }

        $schema = SchemaService::for($document->type);
        $step = (int) $request->input('step', $document->current_step);
        $sections = $request->input('sections', []);

        // Langkah virtual "Log Revisi" (di luar schema) — autosave tersendiri.
        if ($step > $schema->stepCount() && $document->usesRevisionLog()) {
            $this->wizard->persistRevisionLog($request, $document);

            return response()->json($this->jawabanAutosave($document));
        }

        // Sakelar bab opsional ikut ter-autosave: tanpa ini, mematikan
        // "Gunakan Flowchart" baru tersimpan saat pindah langkah, dan pratinjau
        // yang dimuat sebelum itu masih memuat babnya.
        $this->wizard->persistSakelarBab($request, $schema, $step, $document);

        foreach ($schema->sectionsForStep($step) as $section) {
            if (($section['type'] ?? '') === 'user_picker') {
                continue; // relationship-backed, saved on step submit
            }
            // Kolom rich_text ikut disanitasi di sini juga: autosave menulis ke
            // document_contents persis seperti Simpan, jadi melewatkannya berarti
            // HTML mentah dari peramban tersimpan selama draft disusun.
            $this->documents->saveSection($document, $section['key'], $this->wizard->cleanValue(
                $section['type'] ?? 'text',
                $sections[$section['key']] ?? null,
                $section,
                $document,
            ));
        }

        return response()->json($this->jawabanAutosave($document));
    }

    /**
     * Jawaban autosave: jam simpan + sidik isi cetakan yang BARU.
     *
     * Sidiknya dipakai tombol Preview untuk menyusun URL iframe. Kalau isi
     * ternyata tak berubah, sidiknya sama dengan yang sedang tampil → iframe
     * dibiarkan apa adanya: nol permintaan jaringan, nol kedip.
     */
    private function jawabanAutosave(Document $document): array
    {
        return [
            'ok' => true,
            'saved_at' => now()->format('H:i').' WITA',
            'v' => $this->pdf->sidik($document->fresh()),
        ];
    }

    /**
     * Unggah satu foto lampiran secara langsung (AJAX) saat dipilih, lalu
     * kembalikan path tersimpan. Path itu disimpan di hidden input pada form,
     * sehingga Preview cukup memuat ulang iframe (tanpa reload halaman) dan
     * foto tidak hilang saat pindah langkah.
     */
    public function uploadAttachment(Request $request, Document $document)
    {
        $this->authorizeView($request, $document);

        if (! $this->isEditable($document, $request)) {
            return response()->json(['ok' => false, 'message' => 'Dokumen tidak dapat diedit.'], 422);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'section' => ['required', 'string'],
        ]);

        $dept = $document->department->code;
        $jenis = $document->type->code;
        $uploaded = $validated['image'];

        $filename = uniqid('img_').'.'.$uploaded->getClientOriginalExtension();
        $path = $uploaded->storeAs("lampiran/{$dept}/{$jenis}", $filename, 'public');

        $document->attachments()->create([
            'section_key' => $validated['section'],
            'path' => $path,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime' => $uploaded->getMimeType(),
            'size' => $uploaded->getSize(),
        ]);

        return response()->json(['ok' => true, 'path' => $path, 'url' => Storage::url($path)]);
    }

    /** Kirim dari index — draft menjadi in_review bila peninjau & penyetuju sudah dipilih. */
    public function submit(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        // draft = kirim awal; rejected = kirim ulang setelah revisi (Tipe A).
        abort_unless($this->isEditable($document, $request) && in_array($document->status, ['draft', 'rejected'], true), 403);

        if (! $document->reviewer_id || ! $document->approver_id) {
            return redirect()->route('documents.edit', $document)
                ->with('error', 'Lengkapi Peninjau dan Penyetuju di Langkah 2 sebelum mengirim.');
        }

        // Peninjau & penyetuju harus sesuai matriks (jenis + dept + SHE/Plant).
        $resolver = app(DocumentParticipantResolver::class);
        if (! $resolver->isValidReviewer($document, $document->reviewer_id) || ! $resolver->isValidApprover($document, $document->approver_id)) {
            return redirect()->route('documents.edit', $document)
                ->with('error', 'Peninjau/Penyetuju tidak sesuai aturan untuk jenis & departemen dokumen ini.');
        }

        $this->documents->tandaiTerkirim($document);

        return redirect()->route('documents.index')->with('status', $this->kabarTerkirim($document));
    }

    /**
     * Kabar sesudah "Kirim" — bunyinya ikut nasib dokumennya, bukan tombolnya.
     *
     * Salinan dokumen lama (butir 7) langsung Berlaku tanpa tinjauan, jadi
     * kalimat "dikirim untuk ditinjau" di sana akan menyuruh pengirimnya
     * menunggu peninjau yang tak pernah akan datang.
     */
    private function kabarTerkirim(Document $document): string
    {
        return $document->status === 'published'
            ? "Dokumen {$document->doc_number} langsung Berlaku — salinan dokumen lama tak perlu ditinjau."
            : "Dokumen {$document->doc_number} dikirim untuk ditinjau.";
    }

    /** Tarik (withdraw) — hanya saat waiting_for_review (belum disentuh peninjau) → kembali draft (v3.1 §4.1). */
    public function withdraw(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);
        abort_unless($document->created_by === $request->user()->id || $request->user()->can('document.view_all'), 403);
        abort_unless($document->status === 'waiting_for_review', 403, 'Hanya dokumen yang menunggu tinjauan yang bisa ditarik.');

        $document->update(['status' => 'draft']);
        app(AuditService::class)->log('document.withdraw', $document->id);

        return redirect()->route('documents.index')->with('status', "Dokumen {$document->doc_number} ditarik kembali ke draft.");
    }

    /** Hapus (soft delete) — hanya draft milik sendiri / admin. */
    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document);

        // Draft → pemilik/admin; Tidak Berlaku (obsolete) → yang berhak kelola
        // revisi (cleanup dokumen lama, v2 Fase D). Sejak Fase C wewenang itu
        // milik GL/MD, dan bagi GL dibatasi ke dokumen BUATANNYA — batas yang
        // sama dengan Document::bisaDirevisiOleh(), yang tak bisa dipakai apa
        // adanya di sini karena ia mensyaratkan status `published`.
        $user = $request->user();
        $draftOwner = $this->isEditable($document, $request) && $document->status === 'draft';
        $obsoleteCleaner = $document->status === 'obsolete'
            && $user->can('document.request_revision')
            && ($user->can('document.view_all') || $document->created_by === $user->id);
        abort_unless($draftOwner || $obsoleteCleaner, 403, 'Hanya draft (pemilik) atau dokumen Tidak Berlaku yang bisa dihapus.');

        $back = $document->status === 'obsolete' ? 'documents.obsolete' : 'documents.index';
        $document->delete();
        app(AuditService::class)->log('document.delete', $document->id);

        return redirect()->route($back)->with('status', 'Dokumen dihapus.');
    }
}
