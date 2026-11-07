<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UtilizationDashboardController;

// ==========================================
// 0. AUTENTIKASI (khusus tamu)
// ==========================================
// Register selalu menghasilkan role borrower. Login dibatasi 5x/menit per email+IP.
Route::middleware('guest')->group(function () {
    Route::get('/login', [
        LoginController::class,
        'create',
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'store',
    ])->middleware('throttle:login')->name('login.store');

    Route::get('/register', [
        RegisterController::class,
        'create',
    ])->name('register');

    Route::post('/register', [
        RegisterController::class,
        'store',
    ])->name('register.store');
});

// Semua halaman aplikasi wajib login. Aksi tulis dibatasi role
// (middleware `role`) dan dicek ulang oleh Policy di FormRequest.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [
        LoginController::class,
        'destroy',
    ])->name('logout');

    // ==========================================
    // 1. HALAMAN UTAMA (LANDING / HOME)
    // ==========================================
    // '/' diarahkan sesuai role: peminjam ke booking, lainnya ke Dashboard Utilisasi
    Route::get('/', HomeController::class)->name('home');

    // ==========================================
    // 2. MANAJEMEN RUANG & LAB (ROOMS)
    // ==========================================

    Route::get('/rooms', [
        RoomController::class,
        'index',
    ])->name('rooms.index');

    Route::middleware('role:lab_head,admin')->group(function () {
        Route::get('/rooms/create', [
            RoomController::class,
            'create',
        ])->name('rooms.create');

        Route::post('/rooms', [
            RoomController::class,
            'store',
        ])->name('rooms.store');
    });

    // ==========================================
    // 3. JADWAL KULIAH & PRAKTIKUM (SCHEDULES)
    // ==========================================

    Route::get('/schedules', [
        ScheduleController::class,
        'index',
    ])->name('schedules.index');

    Route::post('/schedules', [
        ScheduleController::class,
        'store',
    ])->middleware('role:lab_head,admin')->name('schedules.store');

    // ==========================================
    // 4. PEMESANAN RUANGAN (BOOKINGS)
    // ==========================================

    // Daftar dan form pengajuan (pimpinan hanya membaca)
    Route::get('/bookings', [
        BookingController::class,
        'index',
    ])->name('bookings.index');

    Route::middleware('role:borrower,lab_head,admin')->group(function () {
        Route::get('/bookings/create', [
            BookingController::class,
            'create',
        ])->name('bookings.create');

        Route::post('/bookings', [
            BookingController::class,
            'store',
        ])->middleware('throttle:booking-store')->name('bookings.store');
    });

    // Verifikasi: Approve & Reject oleh Kepala Lab / Admin
    Route::middleware('role:lab_head,admin')->group(function () {
        Route::match(['post', 'patch'], '/bookings/{booking}/approve', [
            BookingController::class,
            'approve',
        ])->name('bookings.approve');

        Route::post('/bookings/{booking}/reject', [
            BookingController::class,
            'reject',
        ])->name('bookings.reject');
    });

    // Simulasi Kehadiran: Check-In & Check-Out via Scan QR (hanya pemilik booking, lihat BookingPolicy)
    Route::middleware('throttle:qr-scan')->group(function () {
        Route::match(['post', 'patch'], '/bookings/{booking}/check-in', [
            BookingController::class,
            'checkIn',
        ])->name('bookings.checkin');

        Route::match(['post', 'patch'], '/bookings/{booking}/check-out', [
            BookingController::class,
            'checkOut',
        ])->name('bookings.checkout');
    });

    // ==========================================
    // 5. LIVE DASHBOARD UTILISASI
    // ==========================================
    // Data JSON (Accept: application/json) hanya untuk pengelola, admin, pimpinan
    // (UtilizationDashboardRequest::authorize).

    Route::get('/dashboard/utilization', [
        UtilizationDashboardController::class,
        'index',
    ])->name('dashboard.utilization');

    Route::get('/dashboard', [
        UtilizationDashboardController::class,
        'index',
    ])->name('dashboard');
});
