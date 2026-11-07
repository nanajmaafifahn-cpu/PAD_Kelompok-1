<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class BookingException extends RuntimeException
{
    public static function notApprover(): self
    {
        return new self(
            'Hanya Kepala Laboratorium atau Admin Akademik yang dapat memverifikasi booking.'
        );
    }

    public static function invalidState(string $action, string $status): self
    {
        return new self(
            "Booking tidak dapat {$action} karena statusnya '{$status}'."
        );
    }

    public static function roomUnderMaintenance(): self
    {
        return new self('Ruangan sedang dalam perawatan.');
    }

    public static function capacityExceeded(
        int $participants,
        int $capacity
    ): self {
        return new self(
            "Jumlah peserta ({$participants}) melebihi kapasitas ruangan ({$capacity})."
        );
    }

    public static function scheduleConflict(): self
    {
        return new self(
            'Jadwal bentrok dengan jadwal atau booking lain di ruangan yang sama.'
        );
    }

    public static function invalidQrToken(): self
    {
        return new self('Token QR tidak valid.');
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('Booking sudah melakukan check-in.');
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('Booking sudah melakukan check-out.');
    }

    // Jam ditulis dalam zona waktu aplikasi (format "13.45 WIB").
    private static function clock(CarbonInterface $time): string
    {
        return $time->format('H.i T');
    }

    public static function checkInNotYetOpen(CarbonInterface $opensAt): self
    {
        return new self('Belum waktunya check-in, mulai ' . self::clock($opensAt) . '.');
    }

    public static function checkInClosed(CarbonInterface $closesAt): self
    {
        return new self(
            'Batas check-in pukul ' . self::clock($closesAt)
            . ' sudah lewat, sehingga peminjaman dianggap tidak hadir.'
        );
    }

    public static function noApprovedBookingToday(string $roomName): self
    {
        return new self("Tidak ada peminjaman Anda yang disetujui untuk ruang {$roomName} hari ini.");
    }

    public static function bookingStillPending(string $roomName): self
    {
        return new self(
            "Peminjaman Anda untuk ruang {$roomName} hari ini masih menunggu persetujuan pengelola."
        );
    }

    public static function unknownScanCode(): self
    {
        return new self(
            'Kode tidak dikenali. Tempel URL dari QR ruangan atau ketik kode cadangan dari detail peminjaman Anda.'
        );
    }
}
