<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman awal '/' mengarahkan user sesuai role:
 * peminjam ke "Peminjaman Saya", pengelola/admin/pimpinan ke dashboard utilisasi.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return $request->user()->hasRole(User::ROLE_BORROWER)
            ? redirect()->route('bookings.mine')
            : redirect()->route('dashboard');
    }
}
