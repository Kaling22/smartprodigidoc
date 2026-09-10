<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\DocumentDistribution;
use App\Services\InformasiDistribution;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Menu "Distribusi" — apakah dokumen Berlaku benar-benar sampai ke orangnya.
 *
 * Dipisah dari DocumentRevisionController yang mengurus DAUR HIDUP dokumen
 * (ajukan revisi, nonaktifkan): ini urusan JANGKAUAN, bukan status.
 *
 * Diurutkan cakupan MENAIK, bukan menurun. Halaman ini ada untuk menemukan
 * dokumen yang belum sampai; menaruh yang paling sukses di puncak akan membuat
 * daftar yang enak dipandang tapi tak berguna.
 */
class DocumentDistributionController extends Controller
{
    public function __construct(
        private readonly DocumentDistribution $distribusi,
        private readonly InformasiDistribution $distribusiInformasi,
    ) {}

    public function distribution(Request $request)
    {
        $user = $request->user();
        // SH/DH + PJO/Admin/MD. Penjaga lama `document.request_revision` kini
        // milik GL/MD, jadi memakainya akan mencabut halaman ini dari SH/DH &
        // PJO sekaligus membukanya untuk GL — dua-duanya salah (Fase C).
        abort_unless($user->bisaLihatDistribusi(), 403);

        // SATU rute, satu percabangan (Fase D). Rute kedua berarti dua penjaga
        // akses & dua menu sidebar untuk pertanyaan yang persis sama — hanya
        // sumber barisnya yang berbeda.
        if ($request->query('sumber') === 'informasi') {
            return $this->daftarInformasi($request, $user);
        }

        // Lingkup departemen mengikuti pola DocumentRevisionController::published()
        // supaya "Berlaku" dan "Distribusi" tak pernah memperlihatkan himpunan
        // dokumen yang berbeda.
        $canAll = $user->can('document.view_all');

        $documents = Document::with('type', 'department', 'creator')
            ->berlaku()
            ->when($request->filled('type'), fn ($q) => $q->whereHas('type', fn ($t) => $t->where('code', strtoupper($request->type))))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('doc_number', 'like', "%{$request->q}%")->orWhere('title', 'like', "%{$request->q}%")))
            ->when($canAll && $request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->unless($canAll, fn ($q) => $q->where('department_id', $user->department_id))
            ->urut($request->sort, $request->dir)
            ->latest('published_at')
            ->get();

        // Satu panggilan untuk SELURUH halaman — cakupan() memang dirancang
        // menerima kumpulan justru supaya di sini tak lahir N+1.
        $cakupan = $this->distribusi->cakupan($documents);

        // Bawaannya: yang cakupannya PALING RENDAH di atas — itulah gunanya
        // halaman ini. Tapi begitu pembaca menekan judul kolom, permintaannya
        // yang menang; mengurutkan ulang di sini akan membuat tautan itu
        // seolah-olah tak berfungsi.
        if (! isset(Document::KOLOM_URUT[(string) $request->sort])) {
            $documents = $documents->sortBy(fn ($d) => $cakupan[$d->id]['persen'])->values();
        }

        return Inertia::render('Documents/Distribution', [
            'sumber' => 'mutu',
            // Baris DIRATAKAN, sama seperti seluruh daftar dokumen sejak Fase 8:
            // model Document utuh menggendong relasi `creator` — model User
            // beserta `password` & `remember_token`-nya — dan props Inertia
            // menuliskan semuanya ke atribut `data-page`. `cakupan` menempel di
            // barisnya, bukan jadi peta kedua yang dicocokkan ulang di klien.
            'documents' => $documents->map(fn (Document $d) => $d->barisDaftar($user) + [
                'cakupan' => $cakupan[$d->id],
            ])->values(),
            'filters' => $request->only('q', 'type', 'department_id'),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            'jenis' => DocumentType::kode(),
            'prefix' => Pengaturan::prefix(),
            'canAll' => $canAll,
            // Ringkasan sekujur halaman: berapa dokumen yang cakupannya di bawah
            // separuh — satu angka yang langsung bisa ditindaklanjuti.
            'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
        ]);
    }

    /**
     * Rincian SATU informasi — siapa yang sudah membukanya, siapa belum.
     *
     * Halaman terpisah, bukan expander ketiga di daftarnya: pertanyaannya beda
     * tingkat. Expander "Per Departemen" menjawab *departemen mana* yang
     * tertinggal, halaman ini menjawab *siapa orangnya* — dan nama orang
     * memerlukan ruang yang tak ada di dalam sel tabel.
     *
     * Lingkup orangnya menyalin daftarInformasi(): pemegang `document.view_all`
     * bisa menelusuri per departemen, SH/DH otomatis terkurung ke departemennya
     * (keputusan C1). Informasinya sendiri tak pernah disaring — ia memang
     * berlaku lintas tujuh departemen.
     */
    public function rincianInformasi(Request $request, Informasi $informasi)
    {
        $user = $request->user();
        abort_unless($user->bisaLihatDistribusi(), 403);

        $canAll = $user->can('document.view_all');
        $deptId = $canAll ? ((int) $request->query('department_id') ?: null) : $user->department_id;

        return Inertia::render('Documents/RincianInformasi', [
            'informasi' => $this->barisInformasi($informasi),
            'cakupan' => $this->distribusiInformasi->cakupan(collect([$informasi]), $deptId)[$informasi->id],
            // `rincian` mentah menggendong model User di KEDUA sisinya: `sudah`
            // lewat relasi `user` tiap baris InformasiRead, `belum` apa adanya.
            // Diratakan pemerata yang SAMA dengan panel distribusi dokumen mutu
            // — bentuk kembalian kedua service memang sengaja identik, jadi
            // salinan kedua di sini hanya akan jadi tempat kedua yang harus
            // diingat saat kolom berikutnya bertambah.
            'rincian' => DocumentController::ratakanRincian(
                $this->distribusiInformasi->rincian($informasi, $deptId)
            ),
            'departments' => $canAll ? Department::orderBy('code')->get() : collect(),
            'deptId' => $deptId,
        ]);
    }

    /**
     * Cabang `?sumber=informasi` (Fase D / butir 4) — cakupan menu Informasi.
     *
     * Lingkupnya beda dari dokumen mutu dan memang harus beda: informasi
     * berlaku lintas tujuh departemen, jadi yang disaring bukan informasinya
     * melainkan ORANG yang dihitung sebagai sasaran (keputusan C1). Pemegang
     * `document.view_all` melihat angka gabungan plus rincian per departemen —
     * satu-satunya bentuk yang bisa ditindaklanjuti bila 7 departemen dijadikan
     * satu angka.
     */
    private function daftarInformasi(Request $request, User $user)
    {
        $canAll = $user->can('document.view_all');
        $deptId = $canAll ? null : $user->department_id;

        $informasi = Informasi::with('uploader')
            ->berlaku()
            ->when($request->filled('kategori'), fn ($q) => $q->where('kategori', $request->query('kategori')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nomor', 'like', "%{$request->q}%")
                ->orWhere('judul', 'like', "%{$request->q}%")))
            ->orderBy('kategori')->orderBy('nomor')
            ->get();

        $cakupan = $this->distribusiInformasi->cakupan($informasi, $deptId);

        // Cakupan TERENDAH di atas — sama seperti daftar dokumen mutu.
        $informasi = $informasi->sortBy(fn (Informasi $i) => $cakupan[$i->id]['persen'])->values();

        // Dua query untuk seluruh halaman, bukan satu per baris.
        $perDept = $canAll ? $this->distribusiInformasi->perDept($informasi) : [];

        return Inertia::render('Documents/Distribution', [
            'sumber' => 'informasi',
            // Diratakan dengan alasan yang sama seperti cabang dokumen mutu:
            // `with('uploader')` menggendong model User beserta `password`-nya,
            // dan tak satu pun kolomnya digambar layar ini.
            'informasi' => $informasi->map(fn (Informasi $i) => $this->barisInformasi($i) + [
                'cakupan' => $cakupan[$i->id],
                'perDept' => $perDept[$i->id] ?? [],
            ])->values(),
            // Daftar PENUH, termasuk kategori nonaktif: yang disaring di sini
            // adalah informasi yang SUDAH ADA, dan menutup kategori tak pernah
            // berarti isinya hilang.
            'kategoriOpsi' => Informasi::kategori(),
            'filters' => $request->only('q', 'kategori'),
            'canAll' => $canAll,
            'rendah' => collect($cakupan)->filter(fn ($c) => $c['sasaran'] > 0 && $c['persen'] < 50)->count(),
        ]);
    }

    /**
     * Satu informasi, seperlunya saja.
     *
     * Alasannya sama dengan {@see Document::barisDaftar()}: yang dipakai layar
     * cuma empat kolom, sedangkan modelnya menggendong `uploaded_by` beserta
     * relasi `uploader` — model User utuh. Label kategori & revisi dirangkai di
     * sini, bukan di TSX: keduanya dibaca dari `InformasiKategori` di basis data
     * (pakem P3).
     *
     * @return array{id:int, nomor:?string, judul:string, kategori:string, revisi:?string}
     */
    private function barisInformasi(Informasi $informasi): array
    {
        return [
            'id' => $informasi->id,
            'nomor' => $informasi->nomor,
            'judul' => $informasi->judul,
            'kategori' => Informasi::labelKategori($informasi->kategori),
            'revisi' => $informasi->labelRevisi(),
        ];
    }
}
