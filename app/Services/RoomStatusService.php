<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\CarbonInterface;

/**
 * Status live banyak ruang sekaligus (2 query total, bukan 2 query per ruang).
 * Aturannya sama dengan Room::getLiveStatus():
 *   maintenance -> status ruang maintenance
 *   occupied    -> ada booking checked_in yang jadwalnya belum selesai
 *   booked      -> ada booking approved yang sedang berjalan / mulai dalam 2 jam
 *   available   -> selain itu
 * View cukup membaca $liveStatuses[$room->id]; tidak ada query di Blade.
 */
class RoomStatusService
{
    /**
     * @param iterable<Room> $rooms
     * @return array<int, string> room_id => live status
     */
    public function forRooms(iterable $rooms, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $rooms = collect($rooms);
        $ids = $rooms->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        $occupied = Booking::query()
            ->whereIn('room_id', $ids)
            ->where('status', Booking::STATUS_CHECKED_IN)
            ->where('end_time', '>=', $now)
            ->distinct()
            ->pluck('room_id')
            ->flip();

        $booked = Booking::query()
            ->whereIn('room_id', $ids)
            ->where('status', Booking::STATUS_APPROVED)
            ->where('end_time', '>=', $now)
            ->where('start_time', '<=', $now->copy()->addHours(2))
            ->distinct()
            ->pluck('room_id')
            ->flip();

        return $rooms->mapWithKeys(fn (Room $room) => [
            $room->id => match (true) {
                $room->getStatus() === 'maintenance' => Room::LIVE_MAINTENANCE,
                $occupied->has($room->id) => Room::LIVE_OCCUPIED,
                $booked->has($room->id) => Room::LIVE_BOOKED,
                default => Room::LIVE_AVAILABLE,
            },
        ])->all();
    }

    /** @return array<string, int> jumlah ruang per status live */
    public function countByStatus(array $liveStatuses): array
    {
        $counts = array_fill_keys(array_keys(Room::LIVE_STATUS_LABELS), 0);

        foreach ($liveStatuses as $status) {
            $counts[$status]++;
        }

        return $counts;
    }
}
