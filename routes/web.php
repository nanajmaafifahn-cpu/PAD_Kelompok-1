<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AcademicUnitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScanController;
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

// Semua halaman aplikasi wajib login (akun aktif). Aksi tulis dibatasi role
// (middleware `role`) dan dicek ulang oleh Policy di FormRequest.
Route::middleware(['auth', 'active'])->group(function () {
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
        // "Ruangan & Foto": tabel data master untuk pengelola (rooms.index = Jelajahi Ruangan)
        Route::get('/rooms/manage', [
            RoomController::class,
            'manage',
        ])->name('rooms.manage');

        Route::get('/rooms/create', [
            RoomController::class,
            'create',
        ])->name('rooms.create');

        Route::post('/rooms', [
            RoomController::class,
            'store',
        ])->name('rooms.store');

        // Cetak QR Ruangan & Perbarui QR (token baru, QR lama tidak berlaku)
        Route::get('/rooms/qr-codes', [
            RoomController::class,
            'qrIndex',
        ])->name('rooms.qr.index');

        Route::post('/rooms/{room}/qr-token', [
            RoomController::class,
            'regenerateQr',
        ])->name('rooms.qr.regenerate');

        // Ubah & hapus ruang (BE-05). Hapus ditolak bila masih ada booking (RoomService).
        Route::get('/rooms/{room}/edit', [
            RoomController::class,
            'edit',
        ])->name('rooms.edit');

        Route::put('/rooms/{room}', [
            RoomController::class,
            'update',
        ])->name('rooms.update');

        Route::delete('/rooms/{room}', [
            RoomController::class,
            'destroy',
        ])->name('rooms.destroy');

        // Fasilitas per ruang (C4)
        Route::get('/rooms/{room}/facilities', [
            FacilityController::class,
            'index',
        ])->name('rooms.facilities.index');

        Route::post('/rooms/{room}/facilities', [
            FacilityController::class,
            'store',
        ])->name('rooms.facilities.store');

        Route::get('/facilities/{facility}/edit', [
            FacilityController::class,
            'edit',
        ])->name('facilities.edit');

        Route::put('/facilities/{facility}', [
            FacilityController::class,
            'update',
        ])->name('facilities.update');

        Route::delete('/facilities/{facility}', [
            FacilityController::class,
            'destroy',
        ])->name('facilities.destroy');
    });

    // Detail ruang (semua user login). Didaftarkan setelah /rooms/create, /rooms/manage, /rooms/qr-codes.
    Route::get('/rooms/{room}', [
        RoomController::class,
        'show',
    ])->whereNumber('room')->name('rooms.show');

    // ==========================================
    // 3. JADWAL KULIAH & PRAKTIKUM (SCHEDULES)
    // ==========================================

    Route::get('/schedules', [
        ScheduleController::class,
        'index',
    ])->name('schedules.index');

    Route::middleware('role:lab_head,admin')->group(function () {
        Route::post('/schedules', [
            ScheduleController::class,
            'store',
        ])->name('schedules.store');

        // Ubah, batal (status cancelled), hapus jadwal (C3)
        Route::get('/schedules/{schedule}/edit', [
            ScheduleController::class,
            'edit',
        ])->name('schedules.edit');

        Route::put('/schedules/{schedule}', [
            ScheduleController::class,
            'update',
        ])->name('schedules.update');

        Route::patch('/schedules/{schedule}/cancel', [
            ScheduleController::class,
            'cancel',
        ])->name('schedules.cancel');

        Route::delete('/schedules/{schedule}', [
            ScheduleController::class,
            'destroy',
        ])->name('schedules.destroy');
    });

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

    // Riwayat milik user login & detail booking (BookingPolicy::view)
    Route::get('/bookings/mine', [
        BookingController::class,
        'mine',
    ])->name('bookings.mine');

    Route::get('/bookings/{booking}', [
        BookingController::class,
        'show',
    ])->whereNumber('booking')->name('bookings.show');

    // Ubah (pending) & batal oleh pemilik (BookingPolicy + BookingService), nama route sesuai KONTRAK-VIEW BE-06
    Route::get('/bookings/{booking}/edit', [
        BookingController::class,
        'edit',
    ])->name('bookings.edit');

    Route::put('/bookings/{booking}', [
        BookingController::class,
        'update',
    ])->middleware('throttle:booking-store')->name('bookings.update');

    Route::patch('/bookings/{booking}/cancel', [
        BookingController::class,
        'cancel',
    ])->name('bookings.cancel');

    // Hapus oleh pengelola / admin (hanya pending, rejected, cancelled)
    Route::delete('/bookings/{booking}', [
        BookingController::class,
        'destroy',
    ])->middleware('role:lab_head,admin')->name('bookings.destroy');

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
    // 4b. SCAN QR RUANGAN
    // ==========================================
    // QR di pintu ruang berisi URL /scan/{qr_token}. GET hanya menampilkan hasil evaluasi,
    // POST melakukan check-in / check-out untuk booking milik user yang login.

    Route::get('/scan', [
        ScanController::class,
        'index',
    ])->name('scan.index');

    Route::middleware('throttle:qr-scan')->group(function () {
        Route::post('/scan', [
            ScanController::class,
            'manual',
        ])->name('scan.manual');

        Route::get('/scan/{room:qr_token}', [
            ScanController::class,
            'show',
        ])->name('scan.room');

        Route::post('/scan/{room:qr_token}', [
            ScanController::class,
            'confirm',
        ])->name('scan.confirm');
    });

    // ==========================================
    // 5. DASHBOARD & LAPORAN UTILISASI
    // ==========================================
    // Dashboard per role (peminjam vs pengelola/admin/pimpinan).
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Laporan Utilisasi (HTML & JSON) khusus pengelola, admin, pimpinan.
    Route::get('/dashboard/utilization', [
        UtilizationDashboardController::class,
        'index',
    ])->middleware('role:lab_head,admin,leader')->name('dashboard.utilization');

    // ==========================================
    // 6. ADMIN: UNIT AKADEMIK & PENGGUNA (C5)
    // ==========================================

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/units', [AcademicUnitController::class, 'index'])->name('units.index');
        Route::post('/units', [AcademicUnitController::class, 'store'])->name('units.store');
        Route::get('/units/{unit}/edit', [AcademicUnitController::class, 'edit'])->name('units.edit');
        Route::put('/units/{unit}', [AcademicUnitController::class, 'update'])->name('units.update');
        Route::delete('/units/{unit}', [AcademicUnitController::class, 'destroy'])->name('units.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });
});
