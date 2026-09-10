<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Informasi;
use App\Models\InformasiRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Distribusi menu INFORMASI — siapa sudah membuka poster/kebijakan, siapa belum
 * (PLAN-REVISI-v6 Fase D / butir 4).
 *
 * KELAS TERPISAH dari {@see DocumentDistribution}, bukan parameter tambahan di
 * sana: tabel sumbernya beda DAN definisi sasarannya beda — dokumen mutu milik
 * satu departemen, informasi berlaku lintas tujuh. Menggabungkannya berarti dua
 * percabangan `if` di setiap method, dan cabang yang salah pilih tak pernah
 * bergejala: ia cuma menghasilkan persentase yang salah diam-diam.
 *
 * Yang SENGAJA sama: bentuk kembalian {@see cakupan()} identik dengan
 * DocumentDistribution::cakupan() (kunci pembaca/sasaran/persen/unduhan),
 * sehingga `partials/_pita-cakupan` dan widget dashboard dipakai ulang apa
 * adanya — nol logika baru di Blade.
 */
class InformasiDistribution
{
    /**
     * Catat satu pembacaan. Idempoten per (informasi, pengguna).
     *
     * Hanya versi BERLAKU yang dilacak: membuka riwayat edisi lama bukan bukti
     * bahwa kebijakan yang sekarang sudah sampai. Pengunggahnya sendiri juga
     * dilewati — ia yang menaruh berkasnya di sana.
     */
    public function catat(Informasi $informasi, ?User $user, bool $unduh = false, string $platform = 'web'): void
    {
        if (! $user || ! $informasi->berlaku || $user->id === $informasi->uploaded_by) {
            return;
        }

        $now = now();

        // Satu upsert, sama seperti DocumentDistribution::catat() — dua tab yang
        // dibuka bersamaan diselesaikan basis data lewat unique index, bukan
        // oleh SELECT-lalu-INSERT yang punya lomba. `first_read_at` tak ikut
        // diperbarui: nilainya hanya boleh ditulis saat barisnya lahir.
        InformasiRead::upsert(
            [[
                'informasi_id' => $informasi->id,
                'user_id' => $user->id,
                'first_read_at' => $now,
                'last_read_at' => $now,
                'read_count' => 1,
                'download_count' => $unduh ? 1 : 0,
                'platform' => $platform,
            ]],
            ['informasi_id', 'user_id'],
            [
                'last_read_at' => $now,
                'platform' => $platform,
                'read_count' => DB::raw('read_count + 1'),
                'download_count' => DB::raw('download_count + '.($unduh ? 1 : 0)),
            ],
        );
    }

    /**
     * Orang yang seharusnya membaca informasi ini = SELURUH pengguna aktif,
     * kecuali pengunggahnya dan kecuali Admin IT
     * ({@see User::scopeSasaranDistribusi()}). Tak disaring departemen: itulah
     * bedanya dengan dokumen mutu — kebijakan & poster memang berlaku di tujuh
     * departemen.
     *
     * `$departmentId` mempersempitnya ke satu departemen, dipakai GL/SH/DH yang
     * hanya berkepentingan atas orangnya sendiri (keputusan C1).
     */
    public function sasaran(Informasi $informasi, ?int $departmentId = null): EloquentCollection
    {
        return User::with('department')
            ->sasaranDistribusi()
            ->where('id', '!=', $informasi->uploaded_by)
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Ringkasan cakupan untuk BANYAK informasi sekaligus — satu GROUP BY untuk
     * pembacanya, satu COUNT untuk sasarannya. Menerima kumpulan justru supaya
     * daftar dan dashboard tak melahirkan N+1.
     *
     * @param  Collection<int, Informasi>|EloquentCollection<int, Informasi>  $informasi
     * @return array<int, array{pembaca:int, sasaran:int, persen:int, unduhan:int}>
     */
    public function cakupan(Collection|EloquentCollection $informasi, ?int $departmentId = null): array
    {
        if ($informasi->isEmpty()) {
            return [];
        }

        // Pembaca ikut disaring departemen: kalau tidak, SH melihat "12 dari 8
        // orang" karena pembaca departemen lain terhitung sedangkan sasarannya
        // tidak.
        $baca = DB::table('informasi_reads as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereIn('r.informasi_id', $informasi->pluck('id')->all())
            ->whereIn('u.id', User::sasaranDistribusi()->select('id'))
            ->when($departmentId, fn ($q) => $q->where('u.department_id', $departmentId))
            ->groupBy('r.informasi_id')
            ->selectRaw('r.informasi_id, COUNT(*) as pembaca, SUM(r.download_count) as unduhan')
            ->get()->keyBy('informasi_id');

        // Sasaran sama untuk SELURUH baris (satu himpunan pengguna), jadi
        // dihitung sekali — bukan sekali per informasi.
        $total = User::sasaranDistribusi()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();

        // Pengunggah dikecualikan dari sasaran, TAPI hanya bila ia memang ada di
        // dalam himpunan itu: informasi diunggah Admin/MD yang tanpa departemen,
        // sehingga di lingkup satu departemen tak ada apa pun untuk dikurangi.
        // Admin IT pun tak pernah ada di sini — ia bukan sasaran, jadi tak ada
        // yang perlu dikurangi saat dialah yang mengunggah.
        $pengunggahDidalam = User::sasaranDistribusi()
            ->whereIn('id', $informasi->pluck('uploaded_by')->filter()->unique()->all())
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->pluck('id')->all();

        $hasil = [];
        foreach ($informasi as $i) {
            $sasaran = max(0, $total - (in_array($i->uploaded_by, $pengunggahDidalam) ? 1 : 0));
            $pembaca = (int) ($baca[$i->id]->pembaca ?? 0);

            $hasil[$i->id] = [
                'pembaca' => $pembaca,
                'sasaran' => $sasaran,
                'persen' => $sasaran > 0 ? (int) round($pembaca / $sasaran * 100) : 0,
                'unduhan' => (int) ($baca[$i->id]->unduhan ?? 0),
            ];
        }

        return $hasil;
    }

    /**
     * Rincian untuk panel detail: yang sudah membuka (beserta kapan & dari mana)
     * dan yang belum. Cermin {@see DocumentDistribution::rincian()}.
     *
     * Bentuk kembaliannya WAJIB identik dengan kembarannya itu (kunci
     * `sudah`/`belum`), dengan alasan yang sama seperti kesamaan cakupan() yang
     * dijelaskan di kepala kelas ini: `partials/_rincian-pembaca` dipakai ulang
     * apa adanya, nol logika baru di Blade.
     *
     * @return array{sudah: Collection, belum: EloquentCollection}
     */
    public function rincian(Informasi $informasi, ?int $departmentId = null): array
    {
        $sasaran = $this->sasaran($informasi, $departmentId);

        $baca = InformasiRead::where('informasi_id', $informasi->id)
            ->whereHas('user', fn ($q) => $q->sasaranDistribusi()
                ->when($departmentId, fn ($w) => $w->where('department_id', $departmentId)))
            ->with('user.department')
            ->orderByDesc('last_read_at')
            ->get();

        $sudahId = $baca->pluck('user_id')->all();

        return [
            'sudah' => $baca,
            'belum' => $sasaran->reject(fn (User $u) => in_array($u->id, $sudahId, true))->values(),
        ];
    }

    /**
     * Rincian per DEPARTEMEN untuk banyak informasi sekaligus — dipakai pemegang
     * `document.view_all` (PJO/Admin/MD), yang justru butuh tahu departemen mana
     * yang tertinggal, bukan sekadar angka gabungan (keputusan C1).
     *
     * Dua query untuk berapa pun barisnya. Baris "tanpa departemen" (PJO/Admin/
     * MD sendiri) SENGAJA tak ditampilkan, jadi jumlah tujuh baris ini bisa
     * lebih kecil dari angka gabungan di kolom Cakupan — yang ditanyakan tabel
     * ini memang "departemen mana yang tertinggal".
     *
     * @param  Collection<int, Informasi>|EloquentCollection<int, Informasi>  $informasi
     * @return array<int, array<int, array{dept:string, pembaca:int, sasaran:int, persen:int, unduhan:int}>>
     */
    public function perDept(Collection|EloquentCollection $informasi): array
    {
        if ($informasi->isEmpty()) {
            return [];
        }

        $baca = DB::table('informasi_reads as r')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->whereIn('r.informasi_id', $informasi->pluck('id')->all())
            ->whereIn('u.id', User::sasaranDistribusi()->select('id'))
            ->whereNotNull('u.department_id')
            ->groupBy('r.informasi_id', 'u.department_id')
            ->selectRaw('r.informasi_id, u.department_id, COUNT(*) as pembaca, SUM(r.download_count) as unduhan')
            ->get()->groupBy('informasi_id');

        $orang = User::sasaranDistribusi()
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->selectRaw('department_id, COUNT(*) as n')
            ->pluck('n', 'department_id');

        // Departemen pengunggah dibaca dari himpunan SASARAN, bukan dari relasi
        // `uploader`: Admin IT bukan sasaran, jadi saat dialah yang mengunggah
        // tak ada apa pun yang boleh dikurangi. Satu query untuk seluruh daftar,
        // sekaligus melepas ketergantungan pada relasi yang mungkin belum dimuat.
        $deptPengunggahSemua = User::sasaranDistribusi()
            ->whereIn('id', $informasi->pluck('uploaded_by')->filter()->unique()->all())
            ->pluck('department_id', 'id');

        $departemen = Department::orderBy('code')->get();

        $hasil = [];
        foreach ($informasi as $i) {
            $perDept = ($baca[$i->id] ?? collect())->keyBy('department_id');
            $deptPengunggah = $deptPengunggahSemua[$i->uploaded_by] ?? null;

            $hasil[$i->id] = $departemen->map(function (Department $d) use ($perDept, $orang, $deptPengunggah) {
                $pengunggahDisini = $deptPengunggah !== null && (int) $deptPengunggah === (int) $d->id;
                $sasaran = max(0, (int) ($orang[$d->id] ?? 0) - ($pengunggahDisini ? 1 : 0));
                $pembaca = (int) ($perDept[$d->id]->pembaca ?? 0);

                return [
                    'dept' => $d->code,
                    'pembaca' => $pembaca,
                    'sasaran' => $sasaran,
                    'persen' => $sasaran > 0 ? (int) round($pembaca / $sasaran * 100) : 0,
                    'unduhan' => (int) ($perDept[$d->id]->unduhan ?? 0),
                ];
            })->all();
        }

        return $hasil;
    }
}
