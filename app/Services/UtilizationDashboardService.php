<?php

namespace App\Services;

use App\Models\Booking;
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
                // BE-09: ringkasan laporan dihitung di service, bukan di view.
                'maintenance_now' => $countLive('maintenance'),
                'total_scheduled_hours' => round($results->sum(fn (UtilizationResult $r) => $r->scheduledHours), 2),
                'total_actual_hours' => round($results->sum(fn (UtilizationResult $r) => $r->actualHours), 2),
                'total_ghost_bookings' => (int) $results->sum(fn (UtilizationResult $r) => $r->ghostBookings),
                'total_ghost_booking_hours' => round((float) $results->sum(
                    fn (UtilizationResult $r) => $r->metrics['ghost_booking_hours'] ?? 0
                ), 2),
                'total_ghost_opportunity_loss_seat_hours' => round((float) $results->sum(
                    fn (UtilizationResult $r) => $r->metrics['ghost_opportunity_loss_seat_hours'] ?? 0
                ), 2),
                'average_utilization_rate' => round((float) ($results->avg(fn (UtilizationResult $r) => $r->utilizationRate) ?? 0), 1),
                'average_scheduled_rate' => round((float) ($results->avg(fn (UtilizationResult $r) => $r->scheduledRate) ?? 0), 1),
                'average_seat_occupancy_efficiency' => round((float) ($results
                    ->map(fn (UtilizationResult $r) => $r->metrics['seat_occupancy_efficiency'] ?? null)
                    ->filter(fn ($value) => $value !== null)
                    ->avg() ?? 0), 1),
                // Sesi yang sudah check-in tetapi belum check-out (jam aktualnya belum lengkap).
                'open_sessions' => Booking::query()
                    ->where('status', Booking::STATUS_CHECKED_IN)
                    ->whereBetween('start_time', [$period->start, $period->end])
                    ->count(),
            ],
            'rooms' => $results->map(function (UtilizationResult $result) use ($liveStatuses, $rooms): array {
                $room = $rooms->firstWhere('id', $result->roomId);

                return [
                    ...$result->toArray(),
                    'capacity' => $room?->getCapacity(),
                    'live_status' => $liveStatuses[$result->roomId],
                    'academic_unit' => $room?->academicUnit?->name,
                ];
            })->values()->all(),
            'underutilized' => $byCategory(UtilizationResult::CATEGORY_UNDERUTILIZED)
                ->pluck('roomCode')->all(),
            'overutilized' => $byCategory(UtilizationResult::CATEGORY_OVERUTILIZED)
                ->pluck('roomCode')->all(),
        ];
    }
}
