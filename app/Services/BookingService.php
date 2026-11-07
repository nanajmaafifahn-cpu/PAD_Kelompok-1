<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Abstraction: menyembunyikan alur peminjaman yang rumit dari controller
 * (validasi ruangan, kapasitas, bentrok jadwal, approval, QR check-in/out).
 */
class BookingService
{
    /** Daftar peminjaman terbaru untuk halaman index (controller tanpa query). */
    public function paginateBookings(int $perPage = 10): LengthAwarePaginator
    {
        return Booking::with(['room', 'user'])
            ->orderByDesc('start_time')
            ->paginate($perPage);
    }

    /** Ruangan untuk pilihan di form peminjaman. */
    public function roomsForBooking(): EloquentCollection
    {
        return Room::orderBy('name')->get();
    }

    /**
     * Ajukan peminjaman baru (status awal: pending) atas nama user yang login.
     * Nama & peran pemohon diambil dari akun, bukan dari input form.
     *
     * @param array{room_id:int,purpose:string,participant_count:int,start_time:mixed,end_time:mixed} $data
     *
     * @throws BookingException
     */
    public function submitBooking(User $user, array $data): Booking
    {
        $room = Room::findOrFail($data['room_id']);

        $booking = new Booking([
            'room_id' => $room->id,
            'user_id' => $user->id,
            'requester_name' => $user->name,
            'requester_role' => $user->roleLabel(),
            'purpose' => $data['purpose'],
            'participant_count' => $data['participant_count'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->checkRoomAvailability($room);
        $this->checkCapacity($room, $booking->participant_count);
        $this->checkScheduleConflict($booking);

        return $this->saveBooking($booking);
    }

    /**
     * Setujui peminjaman (khusus Kepala Lab / Admin) dan terbitkan token QR.
     * Bentrok dicek ulang karena booking lain mungkin disetujui lebih dulu.
     *
     * @throws BookingException
     */
    public function approve(Booking $booking, User $approver): Booking
    {
        $this->assertIsApprover($approver);

        return DB::transaction(function () use ($booking) {
            // Kunci ruangan agar dua approval bersamaan tidak lolos bentrok.
            $room = Room::query()->lockForUpdate()->findOrFail($booking->room_id);

            // Baca ulang SETELAH kunci: approval lain mungkin sudah memprosesnya.
            $booking->refresh();
            $this->assertPending($booking, 'disetujui');

            $this->checkRoomAvailability($room);
            $this->checkCapacity($room, $booking->participant_count);
            $this->checkScheduleConflict($booking);

            $booking->setStatus(Booking::STATUS_APPROVED);
            $booking->qr_token = Str::random(40);
            $booking->rejection_reason = null;
            $booking->save();

            return $booking;
        });
    }

    /**
     * Tolak peminjaman beserta alasannya (khusus Kepala Lab / Admin).
     *
     * @throws BookingException
     */
    public function reject(
        Booking $booking,
        User $approver,
        string $reason
    ): Booking {
        $this->assertIsApprover($approver);

        return DB::transaction(function () use ($booking, $reason) {
            // Kunci baris booking, lalu baca ulang agar status tidak basi.
            Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $booking->refresh();
            $this->assertPending($booking, 'ditolak');

            $booking->setStatus(Booking::STATUS_REJECTED);
            $booking->rejection_reason = $reason;
            $booking->save();

            return $booking;
        });
    }

    /** @throws BookingException */
    public function checkIn(
        Booking $booking,
        string $qrToken,
        ?CarbonInterface $at = null
    ): Booking {
        $booking->performQrCheckIn($qrToken, $at);

        return $booking;
    }

    /** @throws BookingException */
    public function checkOut(
        Booking $booking,
        string $qrToken,
        ?CarbonInterface $at = null
    ): Booking {
        $booking->performQrCheckOut($qrToken, $at);

        return $booking;
    }

    /**
     * Tandai semua booking approved yang melewati toleransi sebagai no_show.
     * Aman dipanggil berulang (idempotent).
     *
     * @return int jumlah booking yang baru ditandai ghost
     */
    public function detectGhostBookings(?CarbonInterface $now = null): int
    {
        $now ??= now();
        $marked = 0;

        Booking::query()
            ->where('status', Booking::STATUS_APPROVED)
            ->whereNull('checked_in_at')
            ->where(
                'start_time',
                '<',
                $now->copy()->subMinutes(Booking::GHOST_TOLERANCE_MINUTES)
            )
            ->each(function (Booking $booking) use ($now, &$marked) {
                if ($booking->detectAndMarkGhostBooking($now)) {
                    $marked++;
                }
            });

        return $marked;
    }

    private function assertIsApprover(User $approver): void
    {
        if (!$approver->canApproveBookings()) {
            throw BookingException::notApprover();
        }
    }

    private function assertPending(Booking $booking, string $action): void
    {
        if ($booking->getStatus() !== Booking::STATUS_PENDING) {
            throw BookingException::invalidState($action, $booking->getStatus());
        }
    }

    private function checkRoomAvailability(Room $room): void
    {
        if ($room->getStatus() === 'maintenance') {
            throw BookingException::roomUnderMaintenance();
        }
    }

    private function checkCapacity(Room $room, int $participants): void
    {
        if ($participants > $room->getCapacity()) {
            throw BookingException::capacityExceeded(
                $participants,
                $room->getCapacity()
            );
        }
    }

    private function checkScheduleConflict(Booking $booking): void
    {
        if ($booking->hasScheduleConflict()) {
            throw BookingException::scheduleConflict();
        }
    }

    private function saveBooking(Booking $booking): Booking
    {
        $booking->save();

        return $booking;
    }
}
