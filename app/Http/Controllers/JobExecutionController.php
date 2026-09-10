<?php

namespace App\Http\Controllers;

use App\Models\JobExecution;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Halaman web: riwayat pekerjaan yang menggunakan JSA.
 *
 * Visibilitas: hanya pekerjaan dari user yang berdepartemen SAMA
 * dengan user yang sedang login. PJO (tanpa departemen) dan Admin
 * dapat melihat seluruh departemen.
 */
class JobExecutionController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $filters = $request->only(['q', 'status', 'tanggal_dari', 'tanggal_sampai']);

        $query = JobExecution::with(['document.department', 'user'])
            ->latest('tanggal_pelaksanaan');

        // Batasi ke departemen yang sama, kecuali Admin/PJO yang lintas departemen.
        //
        // Nama perannya `admin_it`, BUKAN `admin` — konstanta dipakai supaya
        // salah ketik jadi mustahil. Dengan string `admin` yang keliru, Admin IT
        // justru ikut tersaring ke departemennya sendiri.
        if ($user->department_id && ! $user->hasRole(RolePermissionSeeder::ROLE_ADMIN)) {
            $query->whereHas('user', fn ($q) => $q->where('department_id', $user->department_id));
        }

        if ($cari = ($filters['q'] ?? null)) {
            $query->where(function ($w) use ($cari) {
                $w->where('nama_pekerjaan', 'like', '%'.$cari.'%')
                    ->orWhere('lokasi', 'like', '%'.$cari.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$cari.'%')
                        ->orWhere('nrp', 'like', '%'.$cari.'%'))
                    ->orWhereHas('document', fn ($d) => $d->where('title', 'like', '%'.$cari.'%'));
            });
        }

        if ($status = ($filters['status'] ?? null)) {
            $query->where('status', $status);
        }

        if ($dari = ($filters['tanggal_dari'] ?? null)) {
            $query->whereDate('tanggal_pelaksanaan', '>=', $dari);
        }

        if ($sampai = ($filters['tanggal_sampai'] ?? null)) {
            $query->whereDate('tanggal_pelaksanaan', '<=', $sampai);
        }

        $pekerjaan = $query->paginate(20)->withQueryString();

        return Inertia::render('JobExecutions/Index', [
            /*
            | Diratakan `->through()` (bukan `->map()`, yang membunuh paginasi
            | tanpa galat — pelajaran Fase 6). Tiap baris menggendong relasi
            | `user` (model User utuh beserta `password`) dan `document`
            | (seluruh kolom dokumen, `arsip_path` termasuk); Blade lama aman
            | karena hanya mencetak enam kolom, props Inertia tidak.
            */
            'pekerjaan' => $pekerjaan->through(fn (JobExecution $job) => [
                'id' => $job->id,
                'nama' => $job->nama_pekerjaan,
                'catatan' => $job->catatan,
                'jsa_judul' => $job->document?->title,
                'jsa_nomor' => $job->document?->displayNumber(),
                'jsa_dept' => $job->document?->department?->code,
                'pelaksana' => $job->user?->name,
                'nrp' => $job->user?->nrp,
                'lokasi' => $job->lokasi,
                // `translatedFormat` membaca locale server (id) — dirangkai di
                // sini supaya klien tak perlu memikul tabel nama bulan kedua.
                'tanggal' => $job->tanggal_pelaksanaan?->translatedFormat('d M Y'),
                'progres' => $job->progres(),
                'status' => $job->status,
                'status_label' => JobExecution::STATUS_LABELS[$job->status] ?? $job->status,
            ]),
            'filters' => $filters,
            'statusLabels' => JobExecution::STATUS_LABELS,
            'bolehHapus' => (bool) $user->can('user.manage'),
        ]);
    }

    /** Hapus riwayat pekerjaan — hanya Admin IT. */
    public function destroy(Request $request, JobExecution $jobExecution): RedirectResponse
    {
        abort_unless($request->user()->can('user.manage'), 403);

        $nama = $jobExecution->nama_pekerjaan;
        $jobExecution->delete(); // cascadeOnDelete menghapus job_checklist_items

        return back()->with('status', "Riwayat pekerjaan \"{$nama}\" berhasil dihapus.");
    }
}
