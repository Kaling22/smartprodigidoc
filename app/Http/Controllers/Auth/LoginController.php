<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (! Auth::attempt($request->only('nrp', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'nrp' => 'NRP atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());
        $request->session()->regenerate();

        $this->audit->log('user.login');

        // Akun nonaktif diarahkan ke halaman "menunggu" oleh middleware EnsureActive.
        //
        // `antrean_awal` = penanda modal "tugas menunggu". Flash session bertahan
        // tepat satu request, jadi modal muncul sekali per login: tanpa kolom
        // baru, tanpa middleware, dan tanpa perlu "menandai sudah dibaca" yang
        // harus dibereskan sendiri.
        return redirect()->intended(route('dashboard'))->with('antrean_awal', true);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->log('user.logout');

        // Bendera pratinjau `ui_v2` DULU dibawa menyeberang `invalidate()`
        // supaya pilihan V1/V2 bertahan sesudah logout. Dicabut 2026-09-07:
        // V1 mati, `PratinjauResponseFactory` tak lagi membaca sesi, jadi tak
        // ada lagi pilihan yang bisa hilang.

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
