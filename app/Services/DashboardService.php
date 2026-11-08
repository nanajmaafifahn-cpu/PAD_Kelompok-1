<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Data dashboard per role (terpisah dari Laporan Utilisasi):
 *  - peminjam  : ringkasan peminjaman miliknya + peminjaman berikutnya
 *  - pengelola : ruang aktif, status live, antrean persetujuan (juga dipakai admin & pimpinan)
 */
class DashboardService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly RoomStatusService $roomStatus
    ) {
    }

    /** @return array<string, mixed> */
    public function borrower(User $user, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $counts = Booking::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $next = Booking::with('room')
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_CHECKED_IN])
            ->where('end_time', '>=', $now)
            ->orderBy('start_time')
            ->first();

        return [
            'stats' => [
                'total' => (int) $counts->sum(),
                'pending' => (int) ($counts[Booking::STATUS_PENDING] ?? 0),
                'active' => (int) (($counts[Booking::STATUS_APPROVED] ?? 0) + ($counts[Booking::STATUS_CHECKED_IN] ?? 0)),
                'completed' => (int) ($counts[Booking::STATUS_COMPLETED] ?? 0),
            ],
            'recentBookings' => Booking::with('room')
                ->where('user_id', $user->id)
                ->orderByDesc('start_time')
                ->limit(5)
                ->get(),
            'nextBooking' => $next,
            // Tombol "Pindai QR" aktif bila sedang checked_in (check-out) atau dalam jendela check-in.
            'nextBookingScannable' => $next !== null && (
                $next->getStatus() === Booking::STATUS_CHECKED_IN
                || ($now->gte($next->checkInOpensAt()) && $now->lte($next->checkInClosesAt()))
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function manager(): array
    {
        $rooms = Room::orderBy('code')->get();
        $liveStatuses = $this->roomStatus->forRooms($rooms);
        $counts = $this->roomStatus->countByStatus($liveStatuses);

        return [
            'stats' => [
                'rooms_active' => $rooms->count() - $counts[Room::LIVE_MAINTENANCE],
                'available_now' => $counts[Room::LIVE_AVAILABLE],
                'occupied_now' => $counts[Room::LIVE_OCCUPIED],
                'pending' => $this->bookings->pendingCount(),
            ],
            'pendingBookings' => $this->bookings->pendingQueue(5),
            'rooms' => $rooms,
            'liveStatuses' => $liveStatuses,
        ];
    }
}
