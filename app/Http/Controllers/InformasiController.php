<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInformasiRequest;
use App\Models\Informasi;
use App\Models\InformasiKategori;
use App\Models\Pengaturan;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\InformasiDistribution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Menu Informasi (butir 3): dokumen pendukung yang TIDAK melewati alur mutu.
 *
 * Tiga aturan pemilik produk yang menentukan bentuk seluruh controller ini:
 *
 *  1. **Dibaca semua orang** — semua departemen, semua jabatan. Membaca karena
 *     itu tak diberi izin apa pun; cukup akun aktif. Yang dipagari hanyalah
 *     mengunggah/menghapus (`informasi.manage`, Admin saja).
 *  2. **Upload-only** — tak ada wizard, tak ada tinjau–setuju, tak ada nomor
 *     terbitan. Tak satu pun rute/model dokumen mutu tersentuh modul ini.
 *  3. **Revisi = unggah berkas baru**, yang lama turun jadi riwayat. Karena itu
 *     Tambah dan Perbarui adalah dua pintu berbeda: Tambah menolak nomor yang
 *     sudah ada, Perbarui justru mewarisi nomornya.
 */
class InformasiController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InformasiDistribution $distribusi,
    ) {}

    /**
     * Daftar per kategori. `?kategori=` memilih submenu.
     *
     * SATU view untuk SELURUH kategori (CLAUDE.md §3): perbedaannya datang
     * dari data (`informasi_kategori.kolom_json`), bukan dari view terpisah —
     * dan itulah yang membuat kategori baru bisa ditambah Admin dari layar
     * Master Data tanpa menyentuh satu berkas pun.
     *
     * Daftar utama memuat versi BERLAKU saja — satu baris per nomor. Riwayat
     * tiap nomor ikut dibawa, tapi TERSARANG di bawah barisnya masing-masing
     * (lihat komentar di bawah), bukan sebagai daftar datar terpisah.
     */
    public function index(Request $request)
    {
        $kat = $this->kategori($request->query('kategori'));

        // Kategori yang DITUTUP Admin (Fase 5b): dokumennya masih ada, tapi
        // daftarnya tidak dibuka. Halamannya menyatakan itu terang-terangan —
        // pola yang sama dengan jenis dokumen yang belum tersedia. Sidebar
        // sudah membuat menunya tak bisa diklik; yang ini menjaga URL yang
        // ditempel langsung atau ditandai favorit.
        if (! $kat->is_active) {
            return Inertia::render('Informasi/Nonaktif', [
                // Dua kolom saja, bukan modelnya: layar ini cuma menyebut nama
                // & ikonnya, sedangkan `InformasiKategori` menggendong
                // `kolom_json` dan `deskripsi` yang tak dibaca siapa pun di sini.
                'kat' => ['nama' => $kat->nama, 'ikon' => $kat->ikon],
            ]);
        }

        $kategori = $kat->slug;

        $query = Informasi::with('uploader')
            ->where('kategori', $kategori)
            ->berlaku()
            ->orderBy('nomor');   // itulah cara orang mencarinya di daftar

        if ($q = $request->input('q')) {
            $query->where(fn ($w) => $w->where('nomor', 'like', "%{$q}%")->orWhere('judul', 'like', "%{$q}%"));
        }

        $daftar = $query->paginate(20)->withQueryString();

        /*
        | Riwayat dibawa BERSAMA daftarnya, dikelompokkan per nomor.
        |
        | Dulu ia halaman sendiri (`?riwayat=1`) dan itu keliru: riwayat empat
        | versi dari SATU dokumen berbaur dengan riwayat dokumen lain di satu
        | daftar datar, sehingga makin rajin sebuah kebijakan diperbarui, makin
        | tak terbaca daftar itu bagi semua orang. Versi lama hanya punya arti
        | DI SEBELAH versi yang menggantikannya.
        |
        | SATU query untuk seluruh halaman, dibatasi ke nomor yang benar-benar
        | tampil — bukan satu query per baris, dan bukan seluruh riwayat
        | kategori ini. Dua puluh baris tetap dua query.
        */
        $riwayat = $daftar->isEmpty() ? collect() : Informasi::with('uploader')
            ->where('kategori', $kategori)
            ->where('berlaku', false)
            ->whereIn('nomor', $daftar->pluck('nomor'))
            ->latest('created_at')      // versi paling baru turun lebih dulu
            ->get()
            ->groupBy('nomor');

        return Inertia::render('Informasi/Index', [
            'kategori' => $kategori,
            'label' => Informasi::labelKategori($kategori),
            'ikon' => Informasi::ikon($kategori),
            // Kolom tabel mengikuti KATEGORI, sama seperti formulirnya:
            // menampilkan Edisi/Revisi pada MEMO hanya akan berisi strip di
            // seluruh baris.
            'kolom' => Informasi::kolomEkstra($kategori),
            'kelola' => (bool) $request->user()->can('informasi.manage'),
            // Riwayat MENEMPEL di barisnya sendiri, bukan jadi peta kedua
            // ber-key nomor (pelajaran Fase 11): dua larik yang harus
            // dicocokkan ulang di klien adalah dua larik yang suatu hari tak
            // cocok. Query-nya tetap satu untuk seluruh halaman.
            'daftar' => $daftar->through(fn (Informasi $i) => $this->baris($i) + [
                'riwayat' => ($riwayat[$i->nomor] ?? collect())
                    ->map(fn (Informasi $l) => $this->baris($l))->values(),
            ]),
            'filters' => $request->only('q'),
            'prefix' => Pengaturan::prefix(),
        ]);
    }

    /** Formulir Tambah — hanya untuk nomor yang BELUM ada. */
    public function create(Request $request)
    {
        $kat = $this->kategori($request->query('kategori'));
        abort_unless($kat->is_active, 404, 'Kategori ini sedang ditutup, jadi tak menerima unggahan baru.');

        return Inertia::render('Informasi/Create', $this->propsFormulir($kat->slug) + ['induk' => null]);
    }

    /**
     * Kategori dari parameter URL, atau kategori aktif pertama bila kosong.
     *
     * Dipusatkan supaya "kategori tidak dikenal" berbunyi sama di daftar dan
     * di formulir — dan supaya keduanya membaca tabel yang sama, bukan satu
     * membaca tabel sementara yang lain masih membaca konstanta lama.
     */
    private function kategori(?string $slug): InformasiKategori
    {
        $kat = InformasiKategori::cari($slug ?: Informasi::kategoriBawaan());

        abort_unless($kat, 404, 'Kategori informasi tidak dikenal.');

        return $kat;
    }

    /**
     * Formulir Perbarui — versi baru untuk nomor yang SUDAH ada.
     *
     * Nomor & kategori ditampilkan terkunci: keduanya diwarisi dari `$induk`,
     * bukan diambil dari kiriman. Itulah yang membuat riwayat satu nomor
     * mustahil tercecer ke nomor lain karena salah ketik.
     */
    public function perbarui(Informasi $induk)
    {
        abort_unless($induk->berlaku, 404, 'Hanya versi yang sedang berlaku yang bisa diperbarui.');

        return Inertia::render('Informasi/Perbarui', $this->propsFormulir($induk->kategori, $induk) + [
            'induk' => $this->baris($induk) + [
                // `<input type="date">` hanya menerima `Y-m-d`; `tanggal_efektif`
                // bertipe Carbon yang di JSON jadi cap waktu ISO, dan isiannya
                // akan diam-diam kosong (pelajaran Fase 11).
                'tanggal_input' => $induk->tanggal_efektif?->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Satu baris Informasi, seperlunya saja.
     *
     * Alasannya sama dengan {@see \App\Models\Document::barisDaftar()} dan
     * {@see DocumentDistributionController::barisInformasi()}: yang dipakai
     * layar cuma segelintir kolom, sedangkan modelnya menggendong relasi
     * `uploader` (model User utuh beserta `password`) DAN `file_path` — jalur
     * berkas di disk `local` yang namanya sengaja diacak Laravel supaya tak
     * bisa ditebak dari nomor dokumen. Blade lama aman karena tak pernah
     * mencetak keduanya; props Inertia tidak, sebab seluruhnya tertulis di
     * atribut `data-page`. Berkasnya dibuka lewat rute `informasi.file`, yang
     * memang satu-satunya pintu berpenjaga.
     *
     * @return array<string, mixed>
     */
    private function baris(Informasi $informasi): array
    {
        return [
            'id' => $informasi->id,
            'nomor' => $informasi->nomor,
            'judul' => $informasi->judul,
            'edisi' => $informasi->edisi,
            'no_revisi' => $informasi->no_revisi,
            'revisi' => $informasi->labelRevisi(),
            'tanggal' => $informasi->tanggal_efektif?->format('d/m/Y'),
            'diunggah' => $informasi->created_at?->format('d/m/Y'),
            'diunggah_lengkap' => $informasi->created_at?->format('d/m/Y H:i'),
            'oleh' => $informasi->uploader?->name,
            'gambar' => $informasi->isGambar(),
        ];
    }

    /**
     * Props yang dipakai KEDUA formulir (Tambah & Perbarui).
     *
     * Roll-over Edisi/Revisi dihitung di sini, bukan di React: rumusnya milik
     * `DocumentService::nextEditionRevision` (revisi 0..5, yang keenam naik
     * Edisi — CLAUDE.md §7), dan modul ini sempat punya rumus keduanya sendiri
     * yang memuat "Rev 6" yang tak pernah ada.
     *
     * @return array<string, mixed>
     */
    private function propsFormulir(string $kategori, ?Informasi $induk = null): array
    {
        [$edisiBaru, $revisiBaru] = $induk
            ? DocumentService::nextEditionRevision(max(1, (int) ($induk->edisi ?: 1)), (int) $induk->no_revisi)
            : [1, 0];

        return [
            'kategori' => $kategori,
            'label' => Informasi::labelKategori($kategori),
            'kolom' => Informasi::kolomEkstra($kategori),
            'ekstensi' => array_values(Informasi::ekstensi($kategori)),
            'prefix' => Pengaturan::prefix(),
            'edisiBaru' => $edisiBaru,
            'revisiBaru' => $revisiBaru,
        ];
    }

    /**
     * Simpan unggahan — Tambah (tanpa `$induk`) maupun Perbarui (dengan).
     *
     * SATU method untuk keduanya: yang berbeda hanya asal nomor/kategori dan
     * satu langkah penurunan versi lama. Dua method berarti dua jalur
     * penyimpanan yang suatu hari menyimpan hal yang berbeda untuk unggahan
     * yang sama.
     */
    public function store(StoreInformasiRequest $request, ?Informasi $induk = null): RedirectResponse
    {
        $kategori = $induk?->kategori ?? $request->kategori;
        $nomor = $induk?->nomor ?? $request->nomor;
        $berkas = $request->file('berkas');

        // Versi lama diturunkan DULU, sebelum yang baru ditulis: terbalik,
        // sesaat ada DUA baris berlaku bernomor sama, dan daftar menampilkan
        // keduanya. Keduanya di dalam satu transaksi supaya "sesaat" itu tak
        // pernah ada sama sekali.
        $baru = DB::transaction(function () use ($request, $induk, $kategori, $nomor, $berkas) {
            if ($induk) {
                Informasi::senomor($kategori, $nomor)->berlaku()->update(['berlaku' => false]);
            }

            return Informasi::create([
                'kategori' => $kategori,
                'nomor' => $nomor,
                'judul' => $request->judul,
                'edisi' => $request->edisi,
                'no_revisi' => $request->no_revisi,
                'tanggal_efektif' => $request->tanggal_efektif,
                'file_path' => $berkas->store('informasi/'.$kategori, 'local'),
                'file_mime' => $berkas->getClientMimeType(),
                'berlaku' => true,
                'uploaded_by' => $request->user()->id,
            ]);
        });

        $this->audit->log($induk ? 'informasi.perbarui' : 'informasi.create', null, [
            'informasi_id' => $baru->id,
            'kategori' => $kategori,
            'nomor' => $nomor,
            'judul' => $baru->judul,
            'menggantikan_id' => $induk?->id,
        ]);

        return redirect()->route('informasi.index', ['kategori' => $kategori])
            ->with('status', $induk
                ? "{$nomor} diperbarui. Versi sebelumnya dipindahkan ke Riwayat."
                : "{$nomor} — {$baru->judul} tersimpan.");
    }

    /**
     * Hapus. Dua cakupan, dipilih pemilik produk:
     *
     *  • `versi` — hanya baris ini. Bila ia yang sedang berlaku, riwayat
     *    TERBARU naik menggantikannya, sehingga nomor itu tak pernah kosong
     *    hanya karena unggahan terakhir salah.
     *  • `semua` — seluruh versi nomor itu, riwayatnya sekalian.
     *
     * Barisnya di-SOFT-delete dan berkasnya SENGAJA ditinggal di disk: selama
     * barisnya masih bisa dipulihkan, membuang berkasnya berarti pemulihan itu
     * menghasilkan baris yang menunjuk berkas hilang — kehilangan data yang
     * tak bisa dibatalkan, ditukar dengan ruang disk yang bisa.
     *
     * ponytail: berkas yatim menumpuk tanpa penyapu. Kalau ruangnya jadi
     * masalah, tambahkan perintah sapu yang membuang berkas milik baris
     * ter-soft-delete lebih tua dari N hari — pola yang sama dengan
     * PdfRenderer::sapuSinggahan(), bukan penghapusan seketika di sini.
     */
    public function destroy(Request $request, Informasi $informasi): RedirectResponse
    {
        $semua = $request->input('cakupan') === 'semua';
        $kategori = $informasi->kategori;
        $nomor = $informasi->nomor;

        $jumlah = DB::transaction(function () use ($informasi, $semua, $kategori, $nomor) {
            if ($semua) {
                $baris = Informasi::senomor($kategori, $nomor)->get();
                $baris->each->delete();

                return $baris->count();
            }

            $berlakuTadi = $informasi->berlaku;
            $informasi->delete();

            // Penerus = riwayat TERBARU nomor ini. Diambil sesudah penghapusan,
            // jadi baris yang baru saja dibuang mustahil terpilih sendiri.
            if ($berlakuTadi) {
                Informasi::senomor($kategori, $nomor)->latest('created_at')->first()
                    ?->update(['berlaku' => true]);
            }

            return 1;
        });

        $this->audit->log('informasi.destroy', null, [
            'kategori' => $kategori,
            'nomor' => $nomor,
            'cakupan' => $semua ? 'semua' : 'versi',
            'jumlah' => $jumlah,
        ]);

        return redirect()->route('informasi.index', ['kategori' => $kategori])
            ->with('status', $semua
                ? "{$nomor} dihapus beserta seluruh riwayatnya ({$jumlah} versi)."
                : "Satu versi {$nomor} dihapus.");
    }

    /**
     * Sajikan berkasnya.
     *
     * Terbuka bagi SEMUA akun aktif lintas 7 departemen — itu memang intinya
     * menu ini. Yang TIDAK terbuka: tanpa login sama sekali. Karena itu
     * berkasnya duduk di disk `local`, bukan `public` yang disajikan
     * routes/web.php tanpa penjaga apa pun.
     *
     * Tak ada `Content-Disposition: attachment`: poster dan kebijakan memang
     * untuk dibaca di tempat. `nosniff` menahan peramban menebak tipe berkas
     * unggahan dan mengeksekusinya sebagai HTML bila tebakannya meleset.
     */
    public function file(Request $request, Informasi $informasi)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($informasi->file_path), 404, 'Berkas tidak ditemukan.');

        /*
        | Pencatat DISTRIBUSI (Fase D). Diletakkan di sini, bukan di daftar:
        | melihat judul poster di tabel bukan membacanya — membuka berkasnyalah
        | satu-satunya bukti paparan isi, dan `catat()` memang idempoten per
        | orang, jadi menyegarkan halaman tak menggandakan pembaca.
        |
        | Rute API menumpang method ini (routes/api.php), jadi pembacaan dari HP
        | ikut tercatat tanpa kode kedua; yang membedakannya cuma token Sanctum.
        */
        $this->distribusi->catat(
            $informasi,
            $request->user(),
            unduh: true,
            platform: $request->user()?->currentAccessToken() ? 'mobile' : 'web',
        );

        return $disk->response($informasi->file_path, $informasi->nomor, [
            'Content-Type' => $informasi->file_mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; object-src 'self'; img-src 'self'",
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
