<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Notifications\DocumentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** In-app notification bell actions (PRD v2 §8). */
class NotificationController extends Controller
{
    /**
     * Halaman Notifikasi penuh (F7 DASBOR-V4, gambar 7) — lonceng hanya
     * menampilkan 8 terakhir; di sinilah SELURUHNYA, berpaginasi + tersaring.
     *
     * Penyaringan & pengurutan TETAP DI SERVER (CLAUDE.md §4). `status` dan
     * `kategori` menyaring lewat kolom JSON `data` — MySQL JSON path via
     * `data->icon`/`data->message` (Laravel menerjemahkannya jadi `->>`).
     *
     * `tautan` tiap baris memakai `DocumentNotification::urlUntuk()` yang
     * SAMA dengan `open()` di bawah dan tombol email — satu tempat, tiga
     * pemakai. Dokumennya dimuat SEKALI lewat `whereIn` (bukan N query),
     * persis alasan `aktivitasTabel()` di `DashboardController` melakukannya.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = $user->notifications();

        if ($request->status === 'belum') {
            $query->whereNull('read_at');
        } elseif ($request->status === 'sudah') {
            $query->whereNotNull('read_at');
        }

        if ($kategori = $request->string('kategori')->toString()) {
            // Peta kategori→ikon dibalik dari KATEGORI (satu ikon = satu
            // kategori hari ini); kategori tak dikenal → nol baris, bukan galat.
            $ikon = array_search($kategori, DocumentNotification::KATEGORI, true);
            $query->where('data->icon', $ikon ?: '__tak_dikenal__');
        }

        if ($cari = $request->string('q')->toString()) {
            $query->where(function ($q) use ($cari) {
                $q->where('data->message', 'like', "%{$cari}%")
                    ->orWhere('data->doc_number', 'like', "%{$cari}%");
            });
        }

        $paginator = $query->paginate(20)->withQueryString();

        $documentIds = collect($paginator->items())->pluck('data.document_id')->filter()->unique();
        $documents = Document::whereIn('id', $documentIds)->get()->keyBy('id');

        $notifikasi = $paginator->through(function ($n) use ($documents) {
            $icon = $n->data['icon'] ?? 'bi-bell';
            $routeName = $n->data['route'] ?? 'dashboard';
            $document = $documents->get($n->data['document_id'] ?? null);

            return [
                'id' => $n->id,
                'judul' => DocumentNotification::KATEGORI[$icon] ?? 'Pemberitahuan',
                'pesan' => $n->data['message'] ?? '',
                'ikon' => $icon,
                'kategori' => DocumentNotification::KATEGORI[$icon] ?? 'Pemberitahuan',
                'nomor' => $n->data['doc_number'] ?? null,
                'tautan' => DocumentNotification::urlUntuk($routeName, $document),
                'waktu' => $n->created_at?->diffForHumans(short: true),
                'dibaca' => $n->read_at !== null,
            ];
        })->toArray();

        return Inertia::render('V2/Notifications/Index', [
            'notifikasi' => $notifikasi,
            'filters' => $request->only(['status', 'kategori', 'q']),
            'kategoriOpsi' => array_values(array_unique(DocumentNotification::KATEGORI)),
            'belumDibaca' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Buka sebuah notifikasi: tandai dibaca, lalu antar ke TEMPATNYA.
     *
     * Dulu barisnya `Route::has($routeName) ? route($routeName) : route('dashboard')`
     * — parameter dokumen dibuang seluruhnya. Dua akibatnya (PLAN-AKSES-v8 Fase 3a):
     *
     *   • Setiap notifikasi mendarat di halaman DAFTAR, bukan di dokumen yang
     *     dibicarakannya. Penerima harus mencari sendiri dokumen yang barusan
     *     disebutkan namanya kepadanya.
     *   • Notifikasi ber-rute `documents.show` melempar UrlGenerationException,
     *     alias galat 500 di layar penerima — sebab rute itu WAJIB berparameter.
     *
     * Aturannya sekarang dipinjam dari DocumentNotification::urlUntuk(), yang
     * sudah dipakai tombol email sejak awal dan sudah tahu rute mana yang minta
     * parameter. Satu tempat, dua pemakai.
     *
     * Dokumennya dibaca dari `data['document_id']` yang MEMANG sudah tersimpan
     * di tiap notifikasi (DocumentNotification::toArray) — tak ada kolom baru,
     * dan notifikasi lama pun ikut sembuh. Dokumen yang sudah dihapus → null,
     * dan urlUntuk() menjatuhkannya ke dashboard alih-alih melempar galat.
     */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $routeName = $notification->data['route'] ?? 'dashboard';
        $document = ($docId = $notification->data['document_id'] ?? null)
            ? Document::find($docId)
            : null;

        return redirect(DocumentNotification::urlUntuk($routeName, $document));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Semua notifikasi ditandai dibaca.');
    }
}
