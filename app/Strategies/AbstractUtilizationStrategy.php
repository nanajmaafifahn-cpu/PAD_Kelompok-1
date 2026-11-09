<?php

namespace App\Strategies;

use App\Models\Booking;
use App\Models\Room;
use App\Support\Utilization\UtilizationPeriod;
use App\Support\Utilization\UtilizationResult;
use Illuminate\Support\Collection;

/**
 * Inheritance: helper bersama (query jadwal/booking, jam terjadwal, jam aktual,
 * klasifikasi). Subclass hanya menentukan rumus utilisasinya sendiri.
 */
abstract class AbstractUtilizationStrategy implements UtilizationStrategyInterface
{
    protected const CAPACITY_SAMPLE_MINIMUM = 5;
    protected const CAPACITY_REDUCTION_THRESHOLD = 80.0;

    // Ambang kategori (persen), mengacu contoh Project Report 1:
    // 12% = underutilized, 44% = normal, 88% = overutilized.
    protected const UNDERUTILIZED_BELOW = 30.0;
    protected const OVERUTILIZED_FROM = 80.0;

    /**
     * Booking disetujui (approved/checked_in/completed/no_show) yang
     * bersinggungan dengan periode.
     *
     * @return Collection<int, Booking>
     */
    protected function approvedBookings(
        Room $room,
        UtilizationPeriod $period
    ): Collection {
        return $room->bookings()
            ->whereIn('status', Booking::APPROVED_STATUSES)
            ->where('start_time', '<=', $period->end)
            ->where('end_time', '>=', $period->start)
            ->get();
    }

    /**
     * Jam terjadwal = jadwal (non-cancelled) + booking disetujui,
     * hanya yang jatuh di jam operasional.
     *
     * @param Collection<int, Booking> $bookings
     */
    protected function scheduledHours(
        Room $room,
        UtilizationPeriod $period,
        Collection $bookings
    ): float {
        $hours = 0.0;

        $schedules = $room->schedules()
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<=', $period->end)
            ->where('end_time', '>=', $period->start)
            ->get();

        foreach ($schedules as $schedule) {
            $hours += $period->operatingHoursWithin(
                $schedule->start_time,
                $schedule->end_time
            );
        }

        foreach ($bookings as $booking) {
            $hours += $period->operatingHoursWithin(
                $booking->start_time,
                $booking->end_time
            );
        }

        return $hours;
    }

    /**
     * Jam aktual = durasi check-in sampai check-out. Sesi yang belum check-out
     * dihitung sampai sekarang, maksimal sampai jam selesai booking.
     *
     * @param Collection<int, Booking> $bookings
     */
    protected function actualHours(
        Collection $bookings,
        UtilizationPeriod $period
    ): float {
        return $bookings
            ->filter(fn (Booking $booking) => $booking->checked_in_at !== null)
            ->sum(fn (Booking $booking) => $this->sessionHours($booking, $period));
    }

    protected function sessionHours(Booking $booking, UtilizationPeriod $period): float
    {
        $now = now();
        $out = $booking->checked_out_at
            ?? ($now->lt($booking->end_time) ? $now : $booking->end_time);

        return $period->operatingHoursWithin($booking->checked_in_at, $out);
    }

    /**
     * Estimate occupied seat-hours from declared participants on checked-in
     * bookings; actual headcounts are not collected by the application.
     *
     * @param Collection<int, Booking> $bookings
     * @return array<string, float|int|null|string>
     */
    protected function capacityMetrics(
        Room $room,
        UtilizationPeriod $period,
        Collection $bookings,
        float $actualHours
    ): array {
        $attended = $bookings->filter(
            fn (Booking $booking) => $booking->checked_in_at !== null
        );
        $capacity = $room->getCapacity();
        $seatHours = $attended->sum(
            fn (Booking $booking) => $booking->participant_count * $this->sessionHours($booking, $period)
        );
        $ghosts = $bookings->filter(
            fn (Booking $booking) => $booking->isGhostBooking()
        );
        $ghostHours = $ghosts->sum(
            fn (Booking $booking) => $period->operatingHoursWithin($booking->start_time, $booking->end_time)
        );
        $ghostSeatHours = $ghosts->sum(
            fn (Booking $booking) => $booking->participant_count
                * $period->operatingHoursWithin($booking->start_time, $booking->end_time)
        );

        $recommendation = 'insufficient_data';
        $recommendedCapacity = null;

        if ($attended->count() >= self::CAPACITY_SAMPLE_MINIMUM) {
            $participantCounts = $attended
                ->pluck('participant_count')
                ->sort()
                ->values();
            $percentileIndex = (int) ceil($participantCounts->count() * 0.9) - 1;
            $recommendedCapacity = (int) ceil($participantCounts[$percentileIndex] * 1.1);
            $occupancyThreshold = $capacity * self::CAPACITY_REDUCTION_THRESHOLD / 100;

            $recommendation = match (true) {
                $recommendedCapacity > $capacity => 'review_larger_room',
                $recommendedCapacity < $occupancyThreshold => 'consider_smaller_room',
                default => 'maintain',
            };
        }

        return [
            'seat_hours_used' => round($seatHours, 2),
            'seat_occupancy_ratio' => $actualHours > 0 && $capacity > 0
                ? $this->percentage($seatHours, $capacity * $actualHours)
                : null,
            'seat_occupancy_efficiency' => $this->percentage(
                $seatHours,
                $capacity * $period->availableHours()
            ),
            'ghost_booking_hours' => round($ghostHours, 2),
            'ghost_opportunity_loss_seat_hours' => round($ghostSeatHours, 2),
            'ghost_opportunity_loss_rate' => $this->percentage($ghostHours, $period->availableHours()),
            'capacity_sample_sessions' => $attended->count(),
            'recommended_capacity' => $recommendedCapacity,
            'capacity_recommendation' => $recommendation,
        ];
    }

    protected function percentage(float $part, float $whole): float
    {
        if ($whole <= 0) {
            return 0.0;
        }

        return round(min(100.0, max(0.0, $part / $whole * 100)), 1);
    }

    protected function classify(float $rate, UtilizationPeriod $period): string
    {
        if ($period->availableHours() <= 0) {
            return UtilizationResult::CATEGORY_NORMAL;
        }

        if ($rate < static::UNDERUTILIZED_BELOW) {
            return UtilizationResult::CATEGORY_UNDERUTILIZED;
        }

        if ($rate >= static::OVERUTILIZED_FROM) {
            return UtilizationResult::CATEGORY_OVERUTILIZED;
        }

        return UtilizationResult::CATEGORY_NORMAL;
    }

    /**
     * @param Collection<int, Booking> $bookings
     * @param array<string, float|int|null|string> $metrics
     */
    protected function buildResult(
        Room $room,
        UtilizationPeriod $period,
        Collection $bookings,
        float $scheduledHours,
        float $actualHours,
        float $utilizationRate,
        array $metrics = []
    ): UtilizationResult {
        $available = $period->availableHours();
        $approved = $bookings->count();
        $ghosts = $bookings->filter(
            fn (Booking $booking) => $booking->isGhostBooking()
        )->count();

        $rate = round(min(100.0, max(0.0, $utilizationRate)), 1);

        return new UtilizationResult(
            roomId: $room->id,
            roomCode: (string) $room->code,
            roomName: (string) $room->name,
            roomType: (string) $room->type,
            availableHours: round($available, 2),
            scheduledHours: round($scheduledHours, 2),
            actualHours: round($actualHours, 2),
            scheduledRate: $this->percentage($scheduledHours, $available),
            utilizationRate: $rate,
            approvedBookings: $approved,
            ghostBookings: $ghosts,
            ghostRate: $approved > 0 ? round($ghosts / $approved * 100, 1) : 0.0,
            category: $this->classify($rate, $period),
            metrics: [...$this->capacityMetrics($room, $period, $bookings, $actualHours), ...$metrics],
        );
    }
}
