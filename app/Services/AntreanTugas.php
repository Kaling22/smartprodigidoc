<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFeedback;
use App\Models\User;

/**
 * Pekerjaan yang MASIH menunggu seorang pengguna — bukan riwayat peristiwa.
 *
 * Satu sumber untuk tiga pemakai: kartu antrean dashboard, badge angka di
 * sidebar, dan modal saat login. Dipisah dari lonceng dengan sengaja: lonceng
 * mencatat apa yang PERNAH terjadi dan tetap tergantung setelah tugasnya
 * selesai, sedangkan yang dibutuhkan di sini adalah apa yang BELUM selesai.
 *
 * Tiap hitungan MENYALIN rumus daftarnya masing-masing (disebut di komentar per
 * baris) — angka di badge tak boleh punya rumus kedua yang bisa menyimpang dari
 * isi halaman yang dituju.
 */
class AntreanTugas
{
    /**
     * @return array<string, array{label: string, jumlah: int, route: string, icon: string}>
     *         Hanya kunci yang jumlahnya > 0; urutan = urutan tampil (pekerjaan
     *         sendiri dulu, lalu peninjauan, lalu administrasi). Antrean kosong
     *         = array kosong.
     */
    public function untuk(User $user): array
    {
        $canAll = $user->can('document.view_all');

        $daftar = [
            // Sama dgn DocumentRevisionController::revisions(): ditolak (Tipe A)
            // + draft hasil Ajukan Revisi (Tipe B).
            'revisi' => [
                'boleh' => $user->can('document.create'),
                'label' => 'Dokumen Revisi',
                'route' => 'documents.revisions',
                'icon' => 'bi-arrow-repeat',
                'hitung' => fn () => Document::where(fn ($q) => $q->where('status', 'rejected')
                    ->orWhere(fn ($w) => $w->whereNotNull('revises_document_id')->where('status', 'draft')))
                    ->when(! $canAll, fn ($q) => $q->where('created_by', $user->id))
                    ->count(),
            ],
            // Penjaganya sekaligus penjaga tampilnya menu (Document::tahapNonaktifUntuk).
            'nonaktif' => [
                'boleh' => Document::tahapNonaktifUntuk($user) !== [],
                'label' => 'Persetujuan Nonaktif',
                'route' => 'nonaktif.index',
                'icon' => 'bi-slash-circle',
                'hitung' => fn () => Document::menungguNonaktif($user)->count(),
            ],
            // Sama dgn DocumentLogController::masukan(): lingkupnya menumpang
            // scopeTerlihatOleh, bukan pagar departemen kedua.
            'masukan' => [
                'boleh' => $user->dashboardPenuh(),
                'label' => 'Masukan Lapangan',
                'route' => 'log.masukan',
                'icon' => 'bi-chat-left-dots',
                'hitung' => fn () => DocumentFeedback::belumDitindak()
                    ->whereHas('document', fn ($q) => $q->terlihatOleh($user))
                    ->count(),
            ],
            /*
             * Sama dgn ReviewController::index(): dokumen baru jadi `in_review`
             * saat peninjau membukanya, jadi keduanya tetap "perlu ditinjau".
             *
             * Penjaganya gate `review-access`, BUKAN izin `document.review`
             * saja: GL SHE meninjau JSA lewat `document.review_jsa`
             * (User::canReviewJsa) dan tak pernah memegang izin penuh itu.
             * Dengan penjaga lama mereka tak pernah melihat angka antreannya,
             * padahal menunya terbuka untuk mereka.
             */
            'tinjau' => [
                'boleh' => $user->can('review-access'),
                'label' => 'Tinjau Dokumen',
                'route' => 'review.index',
                'icon' => 'bi-clipboard-check',
                'hitung' => fn () => Document::whereIn('status', ['waiting_for_review', 'in_review'])
                    ->when(! $canAll, fn ($q) => $q->where('reviewer_id', $user->id))
                    ->count(),
            ],
            // Sama dgn MdReviewController::index(): SELURUH departemen, tanpa
            // penyaring — akun MD memang satu untuk semua.
            'tinjau_md' => [
                'boleh' => $user->can('document.review_md'),
                'label' => 'Tinjau Penulisan',
                'route' => 'review.md',
                'icon' => 'bi-spellcheck',
                'hitung' => fn () => Document::where('status', 'verifikasi_md')->count(),
            ],
            'setujui' => [
                'boleh' => $user->can('document.approve'),
                'label' => 'Persetujuan Saya',
                'route' => 'approvals.index',
                'icon' => 'bi-patch-check',
                'hitung' => fn () => Document::where('status', 'pending_approval')
                    ->when(! $canAll, fn ($q) => $q->where('approver_id', $user->id))
                    ->count(),
            ],
            // Cakupan SAMA dgn halaman Persetujuan Akun (Admin IT & PJO lintas-dept).
            'akun' => [
                'boleh' => $user->can('user.approve_registration'),
                'label' => 'Persetujuan Akun',
                'route' => 'users.pending',
                'icon' => 'bi-person-check',
                'hitung' => fn () => User::pendingVisibleTo($user)->count(),
            ],
        ];

        $antrean = [];
        foreach ($daftar as $kunci => $tugas) {
            if (! $tugas['boleh']) {
                continue;
            }
            // Nol bukan kabar: kunci berjumlah 0 tak pernah ikut, sehingga
            // pemakainya cukup memeriksa isi array — bukan tiap angkanya.
            if (($jumlah = (int) ($tugas['hitung'])()) > 0) {
                $antrean[$kunci] = [
                    'label' => $tugas['label'],
                    'jumlah' => $jumlah,
                    'route' => $tugas['route'],
                    'icon' => $tugas['icon'],
                ];
            }
        }

        return $antrean;
    }
}
