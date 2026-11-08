<?php

namespace App\Services;

use App\Models\Room;
use App\Strategies\UtilizationCalculatorContext;
use App\Support\Utilization\UtilizationPeriod;
use App\Support\Utilization\UtilizationResult;

/**
 * Menyusun data Live Dashboard (status live, utilisasi, under/overutilized)
 * sehingga UtilizationDashboardController cukup validasi input dan respons.
 */
class UtilizationDashboardService
{
    public function __construct(
        private readonly UtilizationCalculatorContext $calculator,
        private readonly BookingService $bookingService,
        private readonly RoomStatusService $roomStatus
    ) {
    }

    /** @return array<string, mixed> */
    public function build(UtilizationPeriod $period): array
    {
        // Pastikan ghost booking terbaru ikut terhitung (idempotent).
        $this->bookingService->detectGhostBookings();

        $rooms = Room::with('academicUnit')->orderBy('code')->get();
        $results = $this->calculator->calculateAll($rooms, $period);

        // Status live semua ruang dalam 2 query (RoomStatusService), bukan 2 query per ruang.
        $liveStatuses = collect($this->roomStatus->forRooms($rooms));

        $byCategory = fn (string $category) => $results
            ->where('category', $category)
            ->values();

        $countLive = fn (string $status) => $liveStatuses
            ->filter(fn ($live) => $live === $status)
            ->count();

        return [
            'period' => [
                'start' => $period->start->toDateString(),
                'end' => $period->end->toDateString(),
                'available_hours' => $period->availableHours(),
            ],
            'summary' => [
                'total_rooms' => $results->count(),
                'underutilized' => $byCategory(UtilizationResult::CATEGORY_UNDERUTILIZED)->count(),
                'overutilized' => $byCategory(UtilizationResult::CATEGORY_OVERUTILIZED)->count(),
                'occupied_now' => $countLive('occupied'),
                'booked_now' => $countLive('booked'),
                'available_now' => $countLive('available'),
            ],
            'rooms' => $results->map(fn (UtilizationResult $result) => [
                ...$result->toArray(),
                'live_status' => $liveStatuses[$result->roomId],
                'academic_unit' => $rooms->firstWhere('id', $result->roomId)
                    ?->academicUnit?->name,
            ])->values()->all(),
            'underutilized' => $byCategory(UtilizationResult::CATEGORY_UNDERUTILIZED)
                ->pluck('roomCode')->all(),
            'overutilized' => $byCategory(UtilizationResult::CATEGORY_OVERUTILIZED)
                ->pluck('roomCode')->all(),
        ];
    }
}
