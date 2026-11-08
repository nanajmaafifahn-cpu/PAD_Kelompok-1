<?php

namespace App\Exceptions;

use RuntimeException;

/** Galat domain data master ruang (misalnya ruang tidak boleh dihapus). */
class RoomException extends RuntimeException
{
    public static function hasActiveBookings(string $roomName, int $count): self
    {
        return new self(
            "Ruang {$roomName} tidak dapat dihapus karena masih punya {$count} peminjaman aktif"
            . ' (menunggu persetujuan / disetujui / sedang digunakan). Selesaikan atau batalkan peminjaman tersebut terlebih dahulu.'
        );
    }

    public static function hasBookingHistory(string $roomName, int $count): self
    {
        return new self(
            "Ruang {$roomName} punya {$count} riwayat peminjaman yang dipakai laporan utilisasi, sehingga tidak dapat dihapus."
            . ' Ubah statusnya menjadi Perawatan bila ruang tidak lagi dipakai.'
        );
    }
}
