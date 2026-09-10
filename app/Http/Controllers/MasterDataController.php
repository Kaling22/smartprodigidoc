<?php

namespace App\Http\Controllers;

use App\Http\Requests\SimpanDepartemenRequest;
use App\Http\Requests\SimpanJenisDokumenRequest;
use App\Http\Requests\SimpanKategoriInformasiRequest;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Informasi;
use App\Models\InformasiKategori;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master Data (PLAN-AKSES-v8 Fase 5b) — departemen, jenis dokumen, kategori
 * informasi. Satu layar, tiga kartu, `can:user.manage`.
 *
 * SATU aturan mengikat ketiganya: **tak ada yang bisa dihapus dari sini.**
 * Ketiga tabel ditunjuk data lain — `users.department_id`,
 * `documents.department_id`, `documents.document_type_id`,
 * `informasi.kategori` — dan penghapusan yang aman untuk ketiganya tidak ada.
 * Yang tersedia adalah saklar `is_active`, yang artinya sempit dan disengaja:
 * **berhenti ditawarkan**, bukan lenyap. Dokumen, pengguna, nomor, dan
 * riwayatnya utuh dan tetap terbaca sesudah saklarnya dimatikan.
 *
 * Kartunya dipisah controller sendiri, bukan menumpang PengaturanController:
 * yang itu menyetel DUA nilai skalar milik site, yang ini mengelola tiga
 * daftar baris. Digabung, satu berkas memikul enam method tulis + tiga query
 * daftar dan melewati batas ~300 baris (CLAUDE.md §3) sebelum Fase 5c
 * menambahkan apa pun.
 */
class MasterDataController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): Response
    {
        /*
        | Jumlah dokumen ikut dibawa karena ia yang membuat saklar di layar ini
        | bisa ditimbang: "nonaktifkan HCGA" berarti hal yang sangat berbeda
        | ketika angkanya 0 dan ketika angkanya 240. Semuanya withCount/agregat
        | — satu query per kartu, bukan satu per baris.
        */
        $departemen = Department::withCount(['documents', 'users'])->orderBy('code')->get();

        $urutJenis = array_keys(DocumentType::RUPA);
        $jenis = DocumentType::withCount([
            'documents',
            'documents as berlaku_count' => fn ($q) => $q->where('status', 'published'),
            // "Berjalan" = masih di dalam alur mutu. Sengaja bukan "total
            // dikurangi berlaku": itu ikut menghitung yang sudah obsolete, dan
            // dokumen yang sudah tidak berlaku bukan pekerjaan yang tertahan.
            'documents as berjalan_count' => fn ($q) => $q->whereNotIn('status', ['published', 'obsolete']),
        ])->get()->sortBy(fn (DocumentType $t) => array_search($t->code, $urutJenis, true) === false
            ? PHP_INT_MAX
            : array_search($t->code, $urutJenis, true))->values();

        /*
        | Ketiga daftar DIRATAKAN jadi larik datar, bukan dikirim sebagai model.
        |
        | Bukan kerapian: Blade lama memanggil METHOD di dalam view —
        | `$k->kolom()` dan `DocumentType::rupa($j->code)` — dan method tak ikut
        | terserialkan ke JSON. Meninggalkannya berarti klien menghitung ulang
        | keduanya sendiri, tepat salinan aturan yang dilarang CLAUDE.md §4.
        | Kolom yang tak dipakai layar ini pun tak ikut: yang tak dikirim
        | mustahil bocor lewat "view source".
        */
        return Inertia::render('Pengaturan/Master', [
            'departemen' => $departemen->map(fn (Department $d) => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'alias' => $d->alias,
                'documents_count' => $d->documents_count,
                'users_count' => $d->users_count,
                'is_active' => (bool) $d->is_active,
            ])->all(),

            'jenis' => $jenis->map(fn (DocumentType $j) => [
                'id' => $j->id,
                'code' => $j->code,
                'name' => $j->name,
                'berjalan_count' => $j->berjalan_count,
                'berlaku_count' => $j->berlaku_count,
                'is_active' => (bool) $j->is_active,
            ])->all(),

            // `values()`: `InformasiKategori::semua()` ber-key slug, dan Blade
            // lama pun hanya mengiterasi NILAInya. Sebagai objek JSON urutannya
            // jadi janji yang tak dijamin siapa pun.
            'kategori' => InformasiKategori::semua()->values()
                ->map(fn (InformasiKategori $k) => [
                    'id' => $k->id,
                    'slug' => $k->slug,
                    'nama' => $k->nama,
                    'deskripsi' => $k->deskripsi,
                    'ikon' => $k->ikon,
                    'kolom' => $k->kolom(),
                    'is_active' => (bool) $k->is_active,
                ])->all(),

            'jumlahInformasi' => Informasi::berlaku()
                ->selectRaw('kategori, count(*) as jumlah')
                ->groupBy('kategori')
                ->pluck('jumlah', 'kategori'),

            // Dua konstanta yang dulu dibaca langsung dari dalam Blade.
            'kolomTersedia' => InformasiKategori::KOLOM_TERSEDIA,
            'rupa' => DocumentType::RUPA,
        ]);
    }

    /**
     * Tambah / ubah departemen. SATU method untuk keduanya — yang berbeda
     * hanya ada-tidaknya baris yang disunting, dan dua method berarti dua
     * jalur penyimpanan yang suatu hari menyimpan hal yang berbeda.
     *
     * Perhatikan yang TIDAK ada di sini: penghapusan. Lihat docblock kelas.
     */
    public function simpanDepartemen(SimpanDepartemenRequest $request, ?Department $department = null): RedirectResponse
    {
        $data = $request->validated();
        $sebelum = $department?->only(['code', 'name', 'alias', 'is_active']);

        $department
            ? $department->update($data)
            : $department = Department::create($data);

        $this->audit->log('master.departemen_simpan', null, [
            'department_id' => $department->id,
            'sebelum' => $sebelum,
            'sesudah' => $department->only(['code', 'name', 'alias', 'is_active']),
        ]);

        return back()->with('status', $sebelum
            ? "Departemen {$department->code} disimpan."
            : "Departemen {$department->code} ditambahkan.");
    }

    /**
     * Ubah nama & saklar aktif satu jenis dokumen. Tanpa tambah, tanpa hapus
     * (§9.1): jenis INTI menuntut schema JSON, template cetak, baris
     * DocumentType::RUPA, dan kalibrasi cetak — empat hal yang tak bisa
     * dibangkitkan dari layar mana pun.
     */
    public function simpanJenis(SimpanJenisDokumenRequest $request, DocumentType $jenis): RedirectResponse
    {
        $sebelum = $jenis->only(['name', 'is_active']);
        $jenis->update($request->validated());

        $this->audit->log('master.jenis_simpan', null, [
            'code' => $jenis->code,
            'sebelum' => $sebelum,
            'sesudah' => $jenis->only(['name', 'is_active']),
        ]);

        return back()->with('status', $jenis->is_active
            ? "Jenis {$jenis->code} disimpan dan aktif."
            : "Jenis {$jenis->code} dinonaktifkan — dokumen yang sudah ada tetap terbaca.");
    }

    /**
     * Tambah / ubah kategori Informasi (ketetapan pemilik F).
     *
     * `slug` hanya lahir saat penambahan dan TAK PERNAH diubah sesudahnya —
     * ia tersimpan sebagai teks di `informasi.kategori`, jadi menggantinya
     * memutus setiap dokumen yang menunjuk ke sana tanpa satu pun galat yang
     * terlihat. Lihat SimpanKategoriInformasiRequest.
     */
    public function simpanKategori(SimpanKategoriInformasiRequest $request, ?InformasiKategori $kategori = null): RedirectResponse
    {
        $data = $request->bersih();
        $sebelum = $kategori?->only(['nama', 'deskripsi', 'ikon', 'kolom_json', 'is_active']);

        $kategori
            ? $kategori->update($data)
            : $kategori = InformasiKategori::create($data);

        // Ingatan seumur-request memegang daftar LAMA. Tanpa baris ini, apa pun
        // yang membaca kategori sesudah penyimpanan pada request yang sama —
        // termasuk test yang memanggil rute ini lalu memeriksa hasilnya —
        // masih melihat keadaan sebelum disimpan.
        InformasiKategori::lupakanIngatan();

        $this->audit->log('master.kategori_simpan', null, [
            'slug' => $kategori->slug,
            'sebelum' => $sebelum,
            'sesudah' => $kategori->only(['nama', 'deskripsi', 'ikon', 'kolom_json', 'is_active']),
        ]);

        return back()->with('status', $sebelum
            ? "Kategori {$kategori->nama} disimpan."
            : "Kategori {$kategori->nama} ditambahkan dan langsung tampil di menu Informasi.");
    }
}
