<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class BookingException extends RuntimeException
{
    /**
     * @param string|null $field nama field form tempat galat ditampilkan (D8), mis. participant_count
     */
    public function __construct(string $message = '', public readonly ?string $field = null)
    {
        parent::__construct($message);
    }

    /** Bentuk siap pakai untuk back()->withErrors(...); tanpa field -> kunci 'booking' (alert umum). */
    public function toErrors(): array
    {
        return [$this->field ?? 'booking' => $this->getMessage()];
    }

    public static function notApprover(): self
    {
        return new self(
            'Hanya Kepala Laboratorium atau Admin Akademik yang dapat memverifikasi peminjaman.'
        );
    }

    public static function invalidState(string $action, string $status): self
    {
        return new self(
            'Peminjaman tidak dapat ' . $action . ' karena statusnya "'
            . (\App\Models\Booking::STATUS_LABELS[$status] ?? $status) . '".'
        );
    }

    public static function roomUnderMaintenance(): self
    {
        return new self('Ruangan sedang dalam perawatan.', 'room_id');
    }

    public static function capacityExceeded(
        int $participants,
        int $capacity
    ): self {
        return new self(
            "Jumlah peserta ({$participants}) melebihi kapasitas ruangan ({$capacity}).",
            'participant_count'
        );
    }

    public static function scheduleConflict(): self
    {
        return new self(
            'Jadwal bentrok dengan jadwal atau peminjaman lain di ruangan yang sama.',
            'start_time'
        );
    }

    public static function startInPast(): self
    {
        return new self('Waktu mulai harus setelah saat ini.', 'start_time');
    }

    public static function notOwner(): self
    {
        return new self('Hanya pemilik peminjaman yang dapat melakukan tindakan ini.');
    }

    public static function cannotCancelStarted(): self
    {
        return new self('Peminjaman yang sudah dimulai tidak dapat dibatalkan.');
    }

    public static function invalidQrToken(): self
    {
        return new self('Token QR tidak valid.');
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('Peminjaman ini sudah check-in.');
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('Peminjaman ini sudah check-out.');
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
