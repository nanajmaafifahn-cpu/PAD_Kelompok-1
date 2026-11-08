<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard sesuai role. Pengelola, admin, dan pimpinan melihat ringkasan ruang + antrean
 * persetujuan; peminjam melihat ringkasan peminjamannya. Laporan utilisasi ada di
 * dashboard.utilization (UtilizationDashboardController).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();

        if ($user->can('viewAll', Booking::class)) {
            return view('dashboard.manager', $dashboard->manager());
        }

        return view('dashboard.borrower', $dashboard->borrower($user));
    }
}
