<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Distribusi dokumen BERLAKU — siapa sudah membacanya, siapa belum.
 *
 * Gunanya bukan mengawasi orang, melainkan menjawab pertanyaan mutu yang selama
 * ini tak terjawab: "SOP ini sudah sah tiga bulan — apakah orang di lapangan
 * benar-benar tahu?" Satu-satunya angka yang bisa ditindaklanjuti dari situ
 * adalah daftar yang BELUM membaca, dan itulah yang ditonjolkan tampilannya.
 *
 * Semua yang di sini harus MURAH: pencatatan satu upsert, ringkasan satu GROUP
 * BY. Pelacakan yang memperlambat halaman yang dilacaknya akan dimatikan orang.
 */
class DocumentDistribution
{
    /**
     * Catat satu pembacaan. Idempoten per (dokumen, pengguna): kunjungan
     * berikutnya menaikkan pencacah, tak menambah baris.
     *
     * Hanya dokumen BERLAKU yang dilacak — draft dan dokumen dalam peninjauan
     * memang belum pantas didistribusikan, mencatatnya cuma mengotori angka
     * cakupan. Pembuat sendiri juga dilewati: ia menulis dokumennya, membaca
     * karyanya sendiri bukan bukti distribusi.
     *
     * @param  bool  $unduh  true bila yang dibuka PDF-nya (paparan isi sungguhan),
     *                       false bila hanya halaman detail.
     */
    public function catat(Document $document, ?User $user, bool $unduh = false, string $platform = 'web'): void
    {
        if (! $user || ! in_array($document->status, ['published', 'sedang_direvisi'], true)) {
            return;
        }
        if ($user->id === $document->created_by) {
            return;
        }

        $now = now();

        /*
        | SATU pernyataan: INSERT ... ON DUPLICATE KEY UPDATE lewat upsert().
        | Alternatifnya (SELECT lalu INSERT-atau-UPDATE) dua query DAN punya
        | lomba: dua tab yang dibuka bersamaan bisa sama-sama lolos SELECT lalu
        | bertabrakan di unique index. Di sini basis data yang menengahi.
        |
        | `first_read_at` sengaja TIDAK ikut kolom yang diperbarui — nilainya
        | hanya boleh ditulis sekali, saat barisnya lahir.
        */
        DocumentRead::upsert(
            [[
                'document_id' => $document->id,
                'user_id' => $user->id,
                'first_read_at' => $now,
                'last_read_at' => $now,
                'read_count' => 1,
                'download_count' => $unduh ? 1 : 0,
                'platform' => $platform,
            ]],
            ['document_id', 'user_id'],
            [
                'last_read_at' => $now,
                'platform' => $platform,
                'read_count' => DB::raw('read_count + 1'),
                'download_count' => DB::raw('download_count + '.($unduh ? 1 : 0)),
            ],
        );
    }

    /**
     * Orang yang SEHARUSNYA membaca dokumen ini = seluruh pengguna aktif di
     * departemennya, kecuali pembuatnya sendiri dan kecuali Admin IT
     * ({@see User::scopeSasaranDistribusi()}).
     *
     * PJO tidak masuk hitungan: ia tanpa departemen dan berperan menyetujui,
     * bukan menjalankan prosedur di lapangan.
     */
    public function sasaran(Document $document): EloquentCollection
    {
        return User::sasaranDistribusi()
            ->where('department_id', $document->department_id)
            ->where('id', '!=', $document->created_by)
            ->orderBy('name')
            ->get();
    }

    /**
     * Ringkasan cakupan untuk BANYAK dokumen sekaligus — satu GROUP BY untuk
     * pembacanya, satu GROUP BY untuk sasarannya.
     *
     * Dipanggil dari daftar dan dari dashboard, jadi ia TIDAK boleh menjadi
     * N+1: itulah sebabnya ia menerima kumpulan dokumen, bukan satu dokumen.
     *
     * @param  Collection<int, Document>|EloquentCollection<int, Document>  $documents
     * @return array<int, array{pembaca:int, sasaran:int, persen:int, unduhan:int}>
     */
    public function cakupan(Collection|EloquentCollection $documents): array
    {
        if ($documents->isEmpty()) {
            return [];
        }

        $ids = $documents->pluck('id')->all();

        // Pembaca disaring dengan penyaring yang PERSIS SAMA dengan penyebutnya:
        // kalau tidak, Admin IT yang membuka dokumen muncul sebagai "9 dari 8
        // orang" — pembilang yang menghitung orang yang tak ada di penyebut.
        $baca = DB::table('document_reads')
            ->whereIn('document_id', $ids)
            ->whereIn('user_id', User::sasaranDistribusi()->select('id'))
            ->groupBy('document_id')
            ->selectRaw('document_id, COUNT(*) as pembaca, SUM(download_count) as unduhan')
            ->get()->keyBy('document_id');

        // Sasaran dihitung per DEPARTEMEN, bukan per dokumen: sepuluh dokumen
        // ICTMD punya sasaran yang sama persis, jadi menghitungnya sepuluh kali
        // hanya membuang query.
        $perDept = User::sasaranDistribusi()
            ->whereIn('department_id', $documents->pluck('department_id')->filter()->unique()->all())
            ->groupBy('department_id')
            ->selectRaw('department_id, COUNT(*) as n')
            ->pluck('n', 'department_id');

        // Pembuat dikecualikan dari sasaran, TAPI hanya bila ia memang ada di
        // dalam himpunan itu — pengurangan `-1` tanpa syarat meleset bila
        // pembuatnya sudah nonaktif, sudah pindah departemen, atau Admin IT.
        // Dihitung SEKALI untuk seluruh kumpulan, sama seperti pola
        // $pengunggahDidalam di InformasiDistribution::cakupan().
        $deptPembuat = User::sasaranDistribusi()
            ->whereIn('id', $documents->pluck('created_by')->filter()->unique()->all())
            ->pluck('department_id', 'id');

        $hasil = [];
        foreach ($documents as $d) {
            $deptnya = $deptPembuat[$d->created_by] ?? null;
            $pembuatDidalam = $deptnya !== null && (int) $deptnya === (int) $d->department_id;

            $sasaran = max(0, (int) ($perDept[$d->department_id] ?? 0) - ($pembuatDidalam ? 1 : 0));
            $pembaca = (int) ($baca[$d->id]->pembaca ?? 0);

            $hasil[$d->id] = [
                'pembaca' => $pembaca,
                'sasaran' => $sasaran,
                'persen' => $sasaran > 0 ? (int) round($pembaca / $sasaran * 100) : 0,
                'unduhan' => (int) ($baca[$d->id]->unduhan ?? 0),
            ];
        }

        return $hasil;
    }

    /** Cakupan satu dokumen — pembungkus {@see cakupan()} agar tak ada dua rumus. */
    public function cakupanSatu(Document $document): array
    {
        return $this->cakupan(collect([$document]))[$document->id];
    }

    /**
     * Rincian untuk panel detail: yang sudah membaca (beserta kapan & dari mana)
     * dan yang belum.
     *
     * @return array{sudah: Collection, belum: EloquentCollection}
     */
    public function rincian(Document $document): array
    {
        $sasaran = $this->sasaran($document);

        // Penyaring yang sama dengan cakupan(): Admin IT tak boleh muncul di
        // kolom "Sudah Membaca" sementara ia tak ada di penyebutnya. Disaring di
        // sisi BACA, bukan di catat() — barisnya yang telanjur tercatat pun ikut
        // beres, tanpa menghapus satu baris pun.
        $baca = DocumentRead::where('document_id', $document->id)
            ->whereHas('user', fn ($q) => $q->sasaranDistribusi())
            ->with('user.department')
            ->orderByDesc('last_read_at')
            ->get();

        $sudahId = $baca->pluck('user_id')->all();

        return [
            'sudah' => $baca,
            'belum' => $sasaran->reject(fn (User $u) => in_array($u->id, $sudahId, true))->values(),
        ];
    }
}
