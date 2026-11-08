<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Support\Scan\ScanResult;
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

    /** Riwayat peminjaman milik satu user (halaman "Peminjaman Saya"). */
    public function paginateBookingsOf(User $owner, int $perPage = 10): LengthAwarePaginator
    {
        return Booking::with(['room', 'user'])
            ->where('user_id', $owner->id)
            ->orderByDesc('start_time')
            ->paginate($perPage);
    }

    /** Jumlah pengajuan yang menunggu persetujuan pengelola. */
    public function pendingCount(): int
    {
        return Booking::query()->where('status', Booking::STATUS_PENDING)->count();
    }

    /**
     * Antrean persetujuan: pengajuan pending dengan waktu mulai paling dekat lebih dulu
     * (yang sudah lewat waktu mulainya tetap ikut agar bisa ditolak / dihapus).
     */
    public function pendingQueue(int $limit = 5): EloquentCollection
    {
        return Booking::with(['room', 'user'])
            ->where('status', Booking::STATUS_PENDING)
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
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

        $this->checkStartInFuture($booking);
        $this->checkRoomAvailability($room);
        $this->checkCapacity($room, $booking->participant_count);
        $this->checkScheduleConflict($booking);

        return $this->saveBooking($booking);
    }

    /**
     * Ubah booking (C2): hanya pemilik, hanya selama pending. Validasi diulang penuh.
     *
     * @param array{room_id:int,purpose:string,participant_count:int,start_time:mixed,end_time:mixed} $data
     *
     * @throws BookingException
     */
    public function updateBooking(Booking $booking, User $user, array $data): Booking
    {
        $this->assertOwner($booking, $user);
        $this->assertPending($booking, 'diubah');

        $room = Room::findOrFail($data['room_id']);

        $booking->fill([
            'room_id' => $room->id,
            'purpose' => $data['purpose'],
            'participant_count' => $data['participant_count'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        $this->checkStartInFuture($booking);
        $this->checkRoomAvailability($room);
        $this->checkCapacity($room, $booking->participant_count);
        $this->checkScheduleConflict($booking);

        return $this->saveBooking($booking);
    }

    /**
     * Batalkan booking (C2): hanya pemilik, status pending/approved, dan belum dimulai.
     *
     * @throws BookingException
     */
    public function cancelBooking(Booking $booking, User $user, ?CarbonInterface $now = null): Booking
    {
        $this->assertOwner($booking, $user);

        if (! in_array($booking->getStatus(), [Booking::STATUS_PENDING, Booking::STATUS_APPROVED], true)) {
            throw BookingException::invalidState('dibatalkan', $booking->getStatus());
        }

        if ($booking->start_time->lte($now ?? now())) {
            throw BookingException::cannotCancelStarted();
        }

        $booking->setStatus(Booking::STATUS_CANCELLED);
        $booking->save();

        return $booking;
    }

    /**
     * Hapus booking (C2, pengelola/admin). Hanya untuk booking yang tidak dipakai laporan
     * utilisasi (pending, rejected, cancelled); riwayat approved/checked_in/completed/no_show dijaga.
     *
     * @throws BookingException
     */
    public function deleteBooking(Booking $booking): void
    {
        if (! in_array($booking->getStatus(), Booking::DELETABLE_STATUSES, true)) {
            throw BookingException::invalidState('dihapus', $booking->getStatus());
        }

        $booking->delete();
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
     * Evaluasi scan QR ruangan TANPA mengubah data (dipakai halaman hasil pindai).
     * Hanya booking milik $user di ruangan ini yang dipertimbangkan (B3):
     *  - status checked_in             -> check-out
     *  - approved & dalam jendela      -> check-in
     *  - selain itu                    -> alasan penolakan yang spesifik
     */
    public function evaluateScan(User $user, Room $room, ?CarbonInterface $now = null): ScanResult
    {
        $now ??= now();
        $roomName = (string) $room->name;

        $mine = fn () => Booking::query()
            ->where('user_id', $user->id)
            ->where('room_id', $room->id);

        $checkedIn = $mine()
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->orderBy('start_time')
            ->first();

        if ($checkedIn) {
            return ScanResult::checkOut(
                $checkedIn,
                "Anda sedang memakai ruang {$roomName}. Konfirmasi check-out bila sudah selesai."
            );
        }

        // Booking hari ini (overlap dengan tanggal $now).
        $today = fn () => $mine()
            ->where('start_time', '<=', $now->copy()->endOfDay())
            ->where('end_time', '>=', $now->copy()->startOfDay())
            ->orderBy('start_time');

        $approved = $today()->where('status', Booking::STATUS_APPROVED)->get();

        $open = $approved->first(
            fn (Booking $b) => $now->gte($b->checkInOpensAt()) && $now->lte($b->checkInClosesAt())
        );

        if ($open) {
            return ScanResult::checkIn(
                $open,
                "Peminjaman Anda di ruang {$roomName} pukul {$open->start_time->format('H.i')}"
                . "–{$open->end_time->format('H.i T')} siap check-in."
            );
        }

        if ($upcoming = $approved->first(fn (Booking $b) => $now->lt($b->checkInOpensAt()))) {
            return ScanResult::denied(
                BookingException::checkInNotYetOpen($upcoming->checkInOpensAt())->getMessage(),
                $upcoming
            );
        }

        if ($missed = $approved->first()) {
            return ScanResult::denied(
                BookingException::checkInClosed($missed->checkInClosesAt())->getMessage(),
                $missed
            );
        }

        if ($pending = $today()->where('status', Booking::STATUS_PENDING)->first()) {
            return ScanResult::denied(
                BookingException::bookingStillPending($roomName)->getMessage(),
                $pending
            );
        }

        return ScanResult::denied(BookingException::noApprovedBookingToday($roomName)->getMessage());
    }

    /**
     * Jalankan check-in / check-out hasil scan QR ruangan.
     *
     * @throws BookingException bila tidak ada aksi yang boleh dilakukan
     */
    public function scanRoom(User $user, Room $room, ?CarbonInterface $now = null): ScanResult
    {
        $now ??= now();

        return DB::transaction(function () use ($user, $room, $now) {
            $result = $this->evaluateScan($user, $room, $now);

            if (! $result->isAllowed()) {
                throw new BookingException($result->message);
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($result->booking->id);

            if ($result->action === ScanResult::ACTION_CHECK_OUT) {
                $booking->performCheckOut($now);
            } else {
                $booking->performCheckIn($now);
            }

            return $result->action === ScanResult::ACTION_CHECK_OUT
                ? ScanResult::checkOut($booking, "Check-out ruang {$room->name} berhasil. Ruangan kembali tersedia.")
                : ScanResult::checkIn($booking, "Check-in ruang {$room->name} berhasil. Selamat menggunakan ruangan.");
        });
    }

    /**
     * Input manual di halaman pemindai: URL hasil QR, token QR ruangan,
     * atau kode cadangan booking milik user. Hasilnya ruangan yang akan dievaluasi.
     *
     * @throws BookingException
     */
    public function resolveScanCode(User $user, string $code): Room
    {
        $code = trim($code);

        // URL dari QR, misalnya http://192.168.1.10:8000/scan/{token}
        if (preg_match('~/scan/([A-Za-z0-9]+)/?(?:[?#].*)?$~', $code, $match)) {
            $code = $match[1];
        }

        $room = Room::query()->where('qr_token', $code)->first();

        if ($room) {
            return $room;
        }

        $booking = Booking::query()
            ->with('room')
            ->where('user_id', $user->id)
            ->where('qr_token', $code)
            ->first();

        if ($booking?->room) {
            return $booking->room;
        }

        throw BookingException::unknownScanCode();
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

    private function assertOwner(Booking $booking, User $user): void
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            throw BookingException::notOwner();
        }
    }

    // D3: dicek ulang di service walau FormRequest sudah memakai after:now.
    private function checkStartInFuture(Booking $booking): void
    {
        $start = $booking->getAttribute('start_time');

        if ($start === null || $start->lte(now())) {
            throw BookingException::startInPast();
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
