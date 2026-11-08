<?php

namespace App\Support\Scan;

use App\Models\Booking;

/**
 * Hasil evaluasi scan QR ruangan untuk satu user:
 * aksi yang akan dilakukan (check-in / check-out) atau alasan penolakan.
 */
final class ScanResult
{
    public const ACTION_CHECK_IN = 'check_in';
    public const ACTION_CHECK_OUT = 'check_out';

    private function __construct(
        public readonly ?string $action,
        public readonly ?Booking $booking,
        public readonly string $message,
    ) {
    }

    public static function checkIn(Booking $booking, string $message): self
    {
        return new self(self::ACTION_CHECK_IN, $booking, $message);
    }

    public static function checkOut(Booking $booking, string $message): self
    {
        return new self(self::ACTION_CHECK_OUT, $booking, $message);
    }

    public static function denied(string $message, ?Booking $booking = null): self
    {
        return new self(null, $booking, $message);
    }

    public function isAllowed(): bool
    {
        return $this->action !== null;
    }
}
