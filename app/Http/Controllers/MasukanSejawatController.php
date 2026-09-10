<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\MasukanSejawat;
use App\Notifications\DocumentNotification;
use App\Services\AuditService;
use App\Services\CatatanPerItem;
use App\Services\ReviewScreen;
use App\Services\SchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Masukan sejawat antar-Group Leader (PLAN-AKSES-v8 Fase 2).
 *
 * GL boleh membaca pekerjaan rekan sedepartemennya lewat "Dokumen Departemen",
 * dan sejak fase ini boleh ikut menyumbang catatan atasnya — tanpa menyusun,
 * tanpa meninjau, tanpa menggeser status apa pun.
 *
 * Batas siapa-boleh-apa dijawab {@see Document::bisaDiberiMasukanSejawatOleh()},
 * bukan di sini: aturan yang sama dipakai tombol di layar, sehingga mustahil
 * tombolnya tampil lalu berakhir 403.
 */
class MasukanSejawatController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ReviewScreen $layar,
    ) {}

    /**
     * Layar pemberi masukan — layar TINJAU yang sama, tanpa keputusan.
     *
     * `review/show.blade.php` memang sudah dirancang dipakai ulang lewat
     * variabel (tahap SH/DH dan tahap MD sudah berbagi ia). Menyalinnya jadi
     * view ketiga berarti tiga layar yang harus sepakat isinya tiap kali sebuah
     * jenis section baru ditambahkan — dan suatu hari tidak sepakat.
     *
     * Yang HILANG di sini, seluruhnya lewat variabel yang tak dikirim: panel AI
     * ($aiUrl), tombol Alihkan ($alihKandidat), alert pengalihan, catatan
     * peninjau putaran sebelumnya ($priorAnnotations — itu percakapan peninjau
     * resmi dengan pembuat, bukan urusan rekan sejawat), dan tanda ✓/✗ JSA
     * ($pakaiVerdict = false).
     *
     * TIDAK ADA side-effect status. ReviewController::show() menaikkan
     * `waiting_for_review` → `in_review`; di sini itu justru merugikan, sebab
     * status itulah yang mencabut hak Tarik milik pembuatnya. Membaca dokumen
     * rekan tak boleh mengubah nasib dokumennya.
     */
    public function create(Request $request, Document $document)
    {
        abort_unless($document->bisaDiberiMasukanSejawatOleh($request->user()), 403,
            'Masukan sejawat hanya untuk sesama Group Leader, atas dokumen rekan sedepartemen yang belum Berlaku.');

        // `denganAnotasiLama: false` — catatan peninjau putaran sebelumnya
        // adalah percakapan peninjau resmi dengan pembuat, bukan urusan rekan
        // sejawat. Panel AI & tombol Alihkan hilang lewat props yang memang tak
        // dikirim (`aiUrl` dan `alihKandidat` bernilai bawaan null di TSX).
        return Inertia::render('Review/Show', array_merge(
            $this->layar->bersama($document, denganAnotasiLama: false),
            [
                'pakaiVerdict' => false,
                'judulLayar' => 'Beri Masukan',
                'backUrl' => route('documents.staffStatus'),
                'backLabel' => 'Kembali ke Dokumen Departemen',
                'formAction' => route('masukan-sejawat.store', $document),
                'petunjuk' => 'Beri masukan pada bagian yang menurut Anda perlu diperbaiki. Catatan ini <strong>bukan keputusan peninjauan</strong> — ia dikirim langsung ke pembuat dokumen sebagai bahan pertimbangan dan tidak mengubah status dokumen.',
                'tanpaKeputusan' => true,
            ],
        ));
    }

    /** Simpan satu masukan lalu beri tahu seluruh penyusun dokumennya. */
    public function store(Request $request, Document $document): RedirectResponse
    {
        abort_unless($document->bisaDiberiMasukanSejawatOleh($request->user()), 403,
            'Masukan sejawat hanya untuk sesama Group Leader, atas dokumen rekan sedepartemen yang belum Berlaku.');

        $data = $request->validate([
            'summary' => ['nullable', 'string', 'max:2000'],
            'annotations' => ['nullable', 'array'],
            'annotations.*' => ['array'],
            'annotations.*.*' => ['nullable', 'string', 'max:1000'],
        ], [], ['summary' => 'ringkasan']);

        $catatan = $this->rapikan($data['annotations'] ?? []);

        // Kiriman kosong seluruhnya ditolak di sini, bukan disimpan lalu
        // dibunyikan ke lonceng pembuatnya. Formulirnya panjang; menekan Kirim
        // tanpa mengisi apa pun hampir selalu ketidaksengajaan.
        // Dilempar sebagai flash `error` (dirender layouts/app), BUKAN
        // withErrors: review/show.blade tak merender bag $errors, jadi
        // withErrors akan memantulkan orang ke formulir kosong tanpa sepatah
        // kata pun — tombol yang tampak rusak.
        if (blank($data['summary'] ?? null) && $catatan === []) {
            return back()->with('error', 'Masukan belum terkirim: isi ringkasan atau setidaknya satu catatan lebih dulu.');
        }

        MasukanSejawat::create([
            'document_id' => $document->id,
            'user_id' => $request->user()->id,
            'ringkasan' => $data['summary'] ?? null,
            'catatan_json' => $catatan,
        ]);

        $this->audit->log('masukan_sejawat.kirim', $document->id, ['catatan' => count($catatan)]);

        // SELURUH penyusun, bukan pembuat utama saja: pembuat tambahan ikut
        // mengerjakan dokumennya (FITUR-BARU-v4 §6).
        //
        // $penting = false — ini bahan pertimbangan, bukan tugas yang menunggu.
        // Mengirimkannya ke email hanya menenggelamkan email yang genting.
        foreach ($document->penyusun() as $penyusun) {
            $penyusun->notify(new DocumentNotification(
                $document,
                "Masukan sejawat dari {$request->user()->name} pada {$document->displayNumber()}.",
                'bi-chat-square-text',
                'masukan-sejawat.show',
            ));
        }

        return redirect()->route('documents.staffStatus')
            ->with('status', "Masukan Anda atas {$document->displayNumber()} terkirim ke pembuatnya.");
    }

    /**
     * Halaman baca-saja seluruh masukan atas satu dokumen.
     *
     * SATU halaman, bukan modal per baris: Dokumen Saya, Log Pesan, dan lonceng
     * ketiganya butuh tujuan yang sama, dan tiga modal yang harus sepakat isinya
     * adalah tiga modal yang suatu hari tidak sepakat. Satu URL melayani
     * ketiganya, dan bisa di-bookmark.
     */
    public function show(Request $request, Document $document)
    {
        $user = $request->user();

        $pemilik = $document->created_by === $user->id
            || $document->authors()->where('user_id', $user->id)->exists();

        abort_unless($document->bisaLihatMasukanSejawat($user), 403,
            'Masukan sejawat hanya terbaca oleh penyusun dokumennya dan pemberi masukannya.');

        $masukan = $document->masukanSejawat()->with('user')->latest()->get();

        // Ditandai dibaca SESUDAH diambil, supaya yang benar-benar baru masih
        // bisa disorot di kunjungan ini, dan HANYA oleh pemiliknya: MD & Admin
        // melihat 7 departemen, dan bila mereka ikut menandai, pembuat yang
        // seharusnya membacanya kehilangan lencananya hanya karena dokumennya
        // sempat dibuka orang lain (pola yang sama dgn DocumentController::show).
        if ($pemilik) {
            $document->masukanSejawat()->whereNull('dibaca_at')->update(['dibaca_at' => now()]);
        }

        $schema = SchemaService::untuk($document);

        return Inertia::render('MasukanSejawat/Show', [
            'document' => [
                'id' => $document->id,
                'nomor' => $document->displayNumber(),
                'judul' => $document->title,
                'dept' => $document->department?->code,
                'status' => $document->status,
            ],
            /*
            | Masukan DIRATAKAN di sini, bukan dikirim sebagai model.
            |
            | Dua sebab, dan keduanya wajib. Pertama `MasukanSejawat::$user`
            | adalah model `User` lengkap dengan `password` + `remember_token`,
            | dan props Inertia terbaca lewat "view source" (pelajaran Fase
            | 6/7/8/9). Kedua, kedua penamaan di bawah — nama BAGIAN dari schema
            | dan penunjuk ITEM — dulu dua closure di kepala Blade; keduanya
            | membaca schema, jadi tempatnya memang di server (pakem P1/P5).
            */
            'masukan' => $masukan->map(fn (MasukanSejawat $m) => [
                'id' => $m->id,
                'oleh' => $m->user?->nameWithJabatan() ?? '—',
                'waktu' => $m->created_at?->format('d/m/Y H:i').' WITA',
                'ringkasan' => $m->ringkasan,
                // Dikelompokkan per bagian dokumen, bukan dibiarkan sebagai
                // daftar datar: pembuat menyunting dokumennya per bagian, jadi
                // catatan yang tercerai-berai memaksanya membolak-balik.
                'perSection' => collect($m->catatan_json ?? [])
                    ->groupBy('section_key')
                    ->map(fn ($items, $key) => [
                        // Nama bagian dibaca dari SCHEMA, tak pernah dari
                        // `section_key` mentah: kunci seperti
                        // `tujuan_ruang_lingkup` adalah nama untuk mesin.
                        'label' => $schema->findSection((string) $key)['label'] ?? $key,
                        'items' => collect($items)->map(fn (array $c) => [
                            'ref' => $this->labelItem((string) ($c['item_ref'] ?? '')),
                            'komentar' => $c['komentar'] ?? '',
                        ])->values()->all(),
                    ])
                    ->values()
                    ->all(),
            ])->values()->all(),
        ]);
    }

    /**
     * Penunjuk item, dalam bahasa manusia.
     *
     * Aturannya dipegang {@see CatatanPerItem::label()} — wizard pembuat
     * memakai penunjuk yang sama untuk catatan yang tak punya baris tempat
     * menempel, dan dua salinan aturan ini adalah dua penomoran yang suatu hari
     * tak sepakat.
     */
    private function labelItem(string $ref): string
    {
        return CatatanPerItem::label($ref);
    }

    /**
     * Kiriman formulir menjadi [{section_key, item_ref, komentar}].
     *
     * Item tanpa komentar dibuang di sini, bukan disimpan sebagai baris kosong:
     * formulirnya merender SATU textarea per item dokumen, jadi sebuah SOP
     * panjang mengirim puluhan kolom yang hampir semuanya kosong (pola yang
     * sudah dipakai ReviewDecision::simpanAnotasi).
     *
     * @param  array<string, array<string, string|null>>  $annotations
     * @return array<int, array{section_key: string, item_ref: string, komentar: string}>
     */
    private function rapikan(array $annotations): array
    {
        $out = [];

        foreach ($annotations as $sectionKey => $items) {
            foreach ((array) $items as $itemRef => $komentar) {
                if (blank($komentar)) {
                    continue;
                }

                $out[] = [
                    'section_key' => (string) $sectionKey,
                    'item_ref' => (string) $itemRef,
                    'komentar' => trim($komentar),
                ];
            }
        }

        return $out;
    }
}
