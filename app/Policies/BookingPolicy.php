<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

/**
 * Hak akses peminjaman. Aturan status (pending, approved, dst.) tetap
 * ditegakkan BookingService; policy ini hanya menjawab "siapa yang boleh".
 */
class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Daftar semua booking (semua peminjam): pengelola, admin, pimpinan.
    // Peminjam hanya melihat "Peminjaman Saya".
    public function viewAll(User $user): bool
    {
        return $user->hasRole(User::ROLE_LAB_HEAD, User::ROLE_ADMIN, User::ROLE_LEADER);
    }

    public function view(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id || $this->viewAll($user);
    }

    // Halaman pemindai QR: semua yang bisa punya booking.
    public function scan(User $user): bool
    {
        return $this->create($user);
    }

    // Pimpinan (leader) hanya membaca dashboard & laporan, tidak mengajukan booking.
    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_BORROWER, User::ROLE_LAB_HEAD, User::ROLE_ADMIN);
    }

    // Ubah & batal: pemilik (aturan status pending / belum mulai ditegakkan BookingService).
    public function update(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    // Hapus: pengelola & admin.
    public function delete(User $user, Booking $booking): bool
    {
        return $user->canApproveBookings();
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $user->canApproveBookings();
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $user->canApproveBookings();
    }

    // Hanya pemilik booking yang boleh check-in / check-out.
    public function checkIn(User $user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        return $this->checkIn($user, $booking);
    }
}
