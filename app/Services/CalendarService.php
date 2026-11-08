<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Schedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Kegiatan ruang dalam bentuk seragam untuk view kalender / jadwal mendatang:
 * jadwal kuliah (Schedule, status != cancelled) + peminjaman yang sudah disetujui
 * (approved, checked_in, completed). Pending/rejected/cancelled/no_show tidak ditampilkan.
 *
 * Bentuk satu kegiatan:
 *   ['type' => 'schedule'|'booking', 'title' => string, 'room_id' => int, 'room_name' => string,
 *    'room_code' => string, 'start' => Carbon, 'end' => Carbon, 'status' => string]
 */
class CalendarService
{
    public const TYPE_SCHEDULE = 'schedule';
    public const TYPE_BOOKING = 'booking';

    public const TYPE_LABELS = [
        self::TYPE_SCHEDULE => 'Jadwal kuliah',
        self::TYPE_BOOKING => 'Peminjaman disetujui',
    ];

    private const VISIBLE_BOOKING_STATUSES = [
        Booking::STATUS_APPROVED,
        Booking::STATUS_CHECKED_IN,
        Booking::STATUS_COMPLETED,
    ];

    /** Kegiatan terdekat sebuah ruang yang belum selesai (untuk detail ruang). */
    public function upcomingForRoom(Room $room, int $limit = 5, ?CarbonInterface $now = null): Collection
    {
        $now ??= now();

        return $this->events($now, null, $room->id)
            ->take($limit)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>> diurutkan dari waktu mulai
     */
    protected function events(CarbonInterface $from, ?CarbonInterface $to = null, ?int $roomId = null): Collection
    {
        $schedules = Schedule::query()
            ->with('room')
            ->where('status', '!=', 'cancelled')
            ->where('end_time', '>=', $from)
            ->when($to, fn ($q) => $q->where('start_time', '<=', $to))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->get()
            ->map(fn (Schedule $schedule) => $this->event(
                self::TYPE_SCHEDULE,
                (string) $schedule->title,
                $schedule->room,
                $schedule->start_time,
                $schedule->end_time,
                (string) $schedule->status
            ));

        $bookings = Booking::query()
            ->with('room')
            ->whereIn('status', self::VISIBLE_BOOKING_STATUSES)
            ->where('end_time', '>=', $from)
            ->when($to, fn ($q) => $q->where('start_time', '<=', $to))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->get()
            ->map(fn (Booking $booking) => $this->event(
                self::TYPE_BOOKING,
                (string) $booking->purpose,
                $booking->room,
                $booking->start_time,
                $booking->end_time,
                $booking->getStatus()
            ));

        return $schedules->concat($bookings)->sortBy(fn (array $e) => $e['start']->getTimestamp())->values();
    }

    private function event(string $type, string $title, ?Room $room, CarbonInterface $start, CarbonInterface $end, string $status): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'room_id' => $room?->id,
            'room_name' => (string) $room?->name,
            'room_code' => (string) $room?->code,
            'start' => $start,
            'end' => $end,
            'status' => $status,
        ];
    }
}
