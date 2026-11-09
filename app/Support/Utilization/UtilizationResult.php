<?php

namespace App\Support\Utilization;

/**
 * Hasil perhitungan utilisasi satu ruangan pada satu periode.
 *
 * Rumus mengikuti Project Report 1:
 *  - scheduledRate   = jam terjadwal / jam tersedia x 100
 *  - utilizationRate = ditentukan Strategy (dasarnya jam aktual / jam tersedia x 100)
 *  - ghostRate       = NO_SHOW / booking disetujui x 100
 */
final readonly class UtilizationResult
{
    public const CATEGORY_UNDERUTILIZED = 'underutilized';
    public const CATEGORY_NORMAL = 'normal';
    public const CATEGORY_OVERUTILIZED = 'overutilized';

    public const CATEGORY_LABELS = [
        self::CATEGORY_UNDERUTILIZED => 'Kurang dimanfaatkan',
        self::CATEGORY_NORMAL => 'Normal',
        self::CATEGORY_OVERUTILIZED => 'Sangat padat',
    ];

    /**
     * @param array<string, float|int|null|string> $metrics metrik utilisasi umum dan khusus per Strategy
     */
    public function __construct(
        public int $roomId,
        public string $roomCode,
        public string $roomName,
        public string $roomType,
        public float $availableHours,
        public float $scheduledHours,
        public float $actualHours,
        public float $scheduledRate,
        public float $utilizationRate,
        public int $approvedBookings,
        public int $ghostBookings,
        public float $ghostRate,
        public string $category,
        public array $metrics = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'room_id' => $this->roomId,
            'room_code' => $this->roomCode,
            'room_name' => $this->roomName,
            'room_type' => $this->roomType,
            'available_hours' => $this->availableHours,
            'scheduled_hours' => $this->scheduledHours,
            'actual_hours' => $this->actualHours,
            'scheduled_rate' => $this->scheduledRate,
            'utilization_rate' => $this->utilizationRate,
            'approved_bookings' => $this->approvedBookings,
            'ghost_bookings' => $this->ghostBookings,
            'ghost_rate' => $this->ghostRate,
            'category' => $this->category,
            'metrics' => $this->metrics,
        ];
    }
}
