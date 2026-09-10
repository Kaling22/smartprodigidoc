<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Audit Log — menu tersendiri untuk penelusuran formal (v3.1 §9/§10).
 * Otorisasi via route (can:audit.view).
 */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'document')->latest('created_at');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }
        if ($q = $request->input('q')) {
            $query->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('nrp', 'like', "%{$q}%"));
        }

        return Inertia::render('Audit/Index', [
            /*
            | `->through()`, bukan `->map()`: memetakan isi paginator dengan
            | `map()` mengembalikan Collection biasa, daftarnya tetap tampil,
            | dan hanya `links`/`from`/`to`/`total` yang lenyap — paginasi mati
            | tanpa satu pun galat (pelajaran Fase 6).
            |
            | Diratakan, bukan dikirim apa adanya: tiap baris menggendong relasi
            | `user` (model User utuh beserta `password` & `remember_token`) dan
            | `document` (seluruh kolom dokumen, `arsip_path` termasuk). Blade
            | lama aman karena hanya mencetak empat kolom; props Inertia tidak.
            */
            'logs' => $query->paginate(30)->withQueryString()->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'waktu' => $log->created_at?->format('d/m/Y H:i:s'),
                'oleh' => $log->user?->name ?? 'Sistem',
                'nrp' => $log->user?->nrp,
                'aksi' => $log->action,
                'dokumen' => $log->document
                    ? ['id' => $log->document->id, 'nomor' => $log->document->displayNumber()]
                    : null,
                'ip' => $log->ip_address,
            ]),
            'filters' => $request->only('action', 'q'),
        ]);
    }
}
