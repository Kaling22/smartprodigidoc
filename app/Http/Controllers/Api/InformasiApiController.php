<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Informasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menu Informasi (butir 3) untuk aplikasi mobile — BACA SAJA.
 *
 * Mengunggah, memperbarui, dan menghapus tetap di web (`informasi.manage`,
 * Admin saja). Yang dibuka ke HP hanya dua hal: daftar per kategori dan
 * berkasnya. Karena itu tak ada satu pun rute tulis di sini, dan penyaji
 * berkasnya justru TIDAK ditulis ulang — routes/api.php menunjuk langsung ke
 * {@see \App\Http\Controllers\InformasiController::file()} yang sudah ada,
 * lengkap dengan `nosniff`, CSP, dan disk `local`-nya.
 *
 * Membacanya terbuka bagi SELURUH akun aktif lintas 7 departemen — itu memang
 * inti menu Informasi, dan aturan itu disalin apa adanya dari sisi web.
 */
class InformasiApiController extends Controller
{
    /**
     * GET /api/informasi           → daftar kesepuluh kategori (untuk grid menu)
     * GET /api/informasi?kategori= → isi satu kategori, versi BERLAKU saja
     *
     * Kategorinya dikirim SERVER, bukan dihardcode di aplikasi: menambah
     * kategori berarti satu baris di tabel `informasi_kategori` yang ditulis
     * Admin dari layar Master Data (Fase 5b), bukan rilis aplikasi baru ke HP
     * semua orang.
     *
     * Kategori NONAKTIF tak ikut dikirim dan isinya tak bisa dibuka — sama
     * seperti di web, di mana menunya tampil tapi tak bisa diklik. Bedanya
     * hanya cara menyatakannya: sidebar web punya tempat untuk menampilkan
     * menu mati, grid di HP tidak.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kategori' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $kategori = $data['kategori'] ?? null;

        if ($kategori === null) {
            return response()->json([
                'kategori' => collect(Informasi::kategori(aktifSaja: true))
                    ->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])
                    ->values(),
            ]);
        }

        abort_unless(isset(Informasi::kategori(aktifSaja: true)[$kategori]), 404, 'Kategori informasi tidak dikenal.');

        // Riwayat SENGAJA tidak ikut. Di web ia tersarang di bawah barisnya
        // untuk keperluan Admin; di lapangan yang dicari hanya versi yang
        // sedang berlaku, dan menampilkan versi lama di HP adalah cara paling
        // mudah membuat orang bekerja dengan acuan yang sudah diganti.
        $query = Informasi::where('kategori', $kategori)->berlaku()->orderBy('nomor');

        if ($q = ($data['q'] ?? null)) {
            $query->where(fn ($w) => $w->where('nomor', 'like', "%{$q}%")->orWhere('judul', 'like', "%{$q}%"));
        }

        return response()->json([
            'label' => Informasi::labelKategori($kategori),
            'data' => $query->get()->map(fn (Informasi $i) => [
                'id' => $i->id,
                'nomor' => $i->nomor,
                'judul' => $i->judul,
                'edisi' => $i->edisi,
                'no_revisi' => $i->no_revisi,
                'revisi_label' => $i->labelRevisi(),
                'tanggal_efektif' => $i->tanggal_efektif?->toDateString(),

                // Aplikasi memilih penampilnya dari sini: POSTER berupa gambar
                // (Image.memory), sisanya PDF. Menebaknya dari ekstensi nama
                // berkas mustahil — `file_path` tak pernah dikirim ke HP.
                'gambar' => $i->isGambar(),
                'mime' => $i->file_mime,
            ])->values(),
        ]);
    }
}
