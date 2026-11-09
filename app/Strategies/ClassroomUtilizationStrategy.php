<?php

namespace App\Strategies;

use App\Models\Room;
use App\Support\Utilization\UtilizationPeriod;
use App\Support\Utilization\UtilizationResult;

/**
 * Ruang kelas reguler.
 *
 * utilization_rate = jam aktual / jam tersedia x 100
 * Fokus pada dua metrik tambahan:
 *  - realization_ratio     : jam aktual vs jam terjadwal (%)
 *  - seat_occupancy_ratio  : rata-rata peserta / kapasitas kursi pada sesi
 *                            yang benar-benar dipakai (%)
 */
class ClassroomUtilizationStrategy extends AbstractUtilizationStrategy
{
    public const ROOM_TYPE = 'classroom';

    public function roomType(): string
    {
        return self::ROOM_TYPE;
    }

    public function calculate(Room $room, UtilizationPeriod $period): UtilizationResult
    {
        $bookings = $this->approvedBookings($room, $period);
        $scheduled = $this->scheduledHours($room, $period, $bookings);
        $actual = $this->actualHours($bookings, $period);

        return $this->buildResult(
            $room,
            $period,
            $bookings,
            $scheduled,
            $actual,
            $this->percentage($actual, $period->availableHours()),
            [
                'realization_ratio' => $scheduled > 0
                    ? $this->percentage($actual, $scheduled)
                    : null,
            ]
        );
    }
}
