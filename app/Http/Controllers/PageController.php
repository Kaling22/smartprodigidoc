<?php

namespace App\Http\Controllers;

use App\Services\ProfilPengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PageController extends Controller
{
    /** "Waiting for approval" page for pending/rejected accounts (Task 1.5). */
    public function pending(Request $request)
    {
        $user = $request->user();

        // Active users have no business here — send them to the dashboard.
        if ($user->status === 'active') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Pending', [
            'name' => $user->name,
            'status' => $user->status,
        ]);
    }

    /** Informasi Akun — tersedia untuk semua role (v3.1 §7). */
    public function account(Request $request)
    {
        $user = $request->user()->load('department', 'roles');

        return Inertia::render('Account/Info', [
            // Model mentah TIDAK dikirim (ikut membocorkan `password` &
            // `remember_token` — lihat UserResource). Yang dulu dipanggil dari
            // dalam Blade — `photoUrl()`, `JABATAN_LABELS`, `jabatanLabel()`,
            // `getRoleNames()->first()` — dijawab di sini.
            'user' => [
                'name' => $user->name,
                'nrp' => $user->nrp,
                'nomor_hp' => $user->nomor_hp,
                'email' => $user->email,
                'status' => $user->status,
                'photo_url' => $user->photoUrl(),
                'punya_foto' => (bool) $user->photo_path,
                'jabatan_label' => \App\Models\User::JABATAN_LABELS[$user->jabatan] ?? null,
                'departemen' => $user->department?->name,
                // Urutan cadangan disalin apa adanya dari Blade lama:
                // jabatanLabel() dulu, baru nama peran spatie — Admin IT & MD
                // memang tak punya jabatan alur dokumen.
                'peran' => $user->jabatanLabel()
                    ?: str_replace('_', ' ', $user->getRoleNames()->first() ?? '—'),
            ],
        ]);
    }

    /**
     * Sunting info akun sendiri: foto profil, nomor HP, email. Field identitas
     * (nama, NRP, jabatan, departemen, peran) tetap dikelola admin — tak diubah
     * di sini. Foto lama diganti/dihapus dengan bersih.
     */
    public function updateAccount(Request $request, ProfilPengguna $profil): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        // Penyimpanannya dipakai bersama kanal mobile — lihat ProfilPengguna.
        $profil->perbarui(
            $user,
            ['nomor_hp' => $request->input('nomor_hp'), 'email' => $request->input('email')],
            $request->file('photo'),
            $request->boolean('remove_photo'),
        );

        return redirect()->route('account.info')->with('status', 'Informasi akun berhasil diperbarui.');
    }
}
