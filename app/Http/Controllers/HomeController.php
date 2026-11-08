<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Halaman awal '/' selalu ke dashboard; isi dashboard sudah disesuaikan role
 * oleh DashboardController (peminjam vs pengelola/admin/pimpinan).
 */
class HomeController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
