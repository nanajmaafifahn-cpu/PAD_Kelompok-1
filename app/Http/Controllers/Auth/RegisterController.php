<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pendaftaran akun mandiri. Selalu menghasilkan role borrower;
 * akun pengelola/admin/pimpinan dibuat lewat seeder atau halaman kelola user.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->validated(),
            'role' => User::ROLE_BORROWER,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', 'Akun berhasil dibuat. Selamat datang, ' . $user->name . '.');
    }
}
