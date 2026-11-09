<?php

namespace App\Services;

use App\Exceptions\RoomException;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Simpan / ubah / hapus data master ruang beserta fotonya (disk public, folder rooms/).
 */
class RoomService
{
    private const PHOTO_DIRECTORY = 'rooms';

    /** @param array<string, mixed> $data hasil validasi StoreRoomRequest */
    public function create(array $data, ?UploadedFile $photo = null): Room
    {
        unset($data['photo']);

        if ($photo) {
            $data['photo_path'] = $photo->store(self::PHOTO_DIRECTORY, 'public');

            if ($data['photo_path'] === false) {
                throw new RoomException('Foto ruang gagal disimpan. Pilih kembali foto dan coba lagi.');
            }
        }

        return Room::create($data);
    }

    /** @param array<string, mixed> $data hasil validasi UpdateRoomRequest */
    public function update(Room $room, array $data, ?UploadedFile $photo = null, bool $removePhoto = false): Room
    {
        unset($data['photo'], $data['remove_photo']);
        $oldPhoto = $room->photo_path;

        if ($photo) {
            $data['photo_path'] = $photo->store(self::PHOTO_DIRECTORY, 'public');

            if ($data['photo_path'] === false) {
                throw new RoomException('Foto ruang gagal disimpan. Pilih kembali foto dan coba lagi.');
            }
        } elseif ($removePhoto) {
            $data['photo_path'] = null;
        }

        $room->update($data);

        if ($oldPhoto && $oldPhoto !== $room->photo_path) {
            Storage::disk('public')->delete($oldPhoto);
        }

        return $room;
    }

    /**
     * Hapus ruang. Ditolak bila masih ada booking (FK bookings.room_id restrictOnDelete):
     * booking aktif harus diselesaikan dulu; riwayat booking menjaga laporan utilisasi.
     * Jadwal & fasilitas ikut terhapus (cascade).
     *
     * @throws RoomException
     */
    public function delete(Room $room): void
    {
        $active = $room->bookings()
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_APPROVED, Booking::STATUS_CHECKED_IN])
            ->count();

        if ($active > 0) {
            throw RoomException::hasActiveBookings((string) $room->name, $active);
        }

        $history = $room->bookings()->count();

        if ($history > 0) {
            throw RoomException::hasBookingHistory((string) $room->name, $history);
        }

        $photo = $room->photo_path;
        $room->delete();

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }
    }
}
