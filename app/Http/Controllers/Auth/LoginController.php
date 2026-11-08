<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Login & logout manual (tanpa starter kit) agar view yang sudah ada tidak tertimpa. */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->credentials(), $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi salah.'])
                ->onlyInput('email');
        }

        // C5: akun yang dinonaktifkan admin ditolak walau password benar.
        if (! Auth::user()->isActive()) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi admin akademik.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda sudah keluar.');
    }
}
