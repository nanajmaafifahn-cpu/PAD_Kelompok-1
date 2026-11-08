<?php

namespace Database\Seeders;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data demo "hidup" untuk presentasi (F1). Hanya dipanggil DatabaseSeeder saat local/testing.
 *
 * - 14 ruang TULT seperti desain (R1 TULT-0805, R2 TULT-0807, R3 TULT-0819, R5 TULT-0906,
 *   R6 TULT-0912, R7 TULT-0913, ...). Kode R8 dst. adalah contoh lanjutan pola yang sama.
 * - Fasilitas: komputer (lab), proyektor, AC.
 * - Jadwal kuliah Senin-Jumat MINGGU BERJALAN di ruang R1, R3, R5, R6, R8.
 * - Booking setiap status, dihitung relatif terhadap now() di ruang R2, R7, R9, R10
 *   (tidak bentrok dengan jadwal), termasuk satu booking approved milik
 *   mahasiswa@campus.test yang SEDANG dalam jendela check-in.
 */
class DemoCampusSeeder extends Seeder
{
    private const BUILDING = 'Telkom University Landmark Tower (TULT)';

    /** kode => [nama, tipe, lantai, kapasitas, status] */
    public const ROOMS = [
        'TULT-0805' => ['R1', 'laboratory', '8', 48, 'available'],
        'TULT-0807' => ['R2', 'laboratory', '8', 48, 'available'],
        'TULT-0819' => ['R3', 'classroom', '8', 50, 'available'],
        'TULT-0906' => ['R5', 'laboratory', '9', 48, 'available'],
        'TULT-0912' => ['R6', 'classroom', '9', 48, 'available'],
        'TULT-0913' => ['R7', 'laboratory', '9', 48, 'available'],
        'TULT-0914' => ['R8', 'classroom', '9', 50, 'available'],
        'TULT-1005' => ['R9', 'laboratory', '10', 48, 'available'],
        'TULT-1007' => ['R10', 'laboratory', '10', 48, 'available'],
        'TULT-1019' => ['R11', 'classroom', '10', 50, 'available'],
        'TULT-1106' => ['R12', 'laboratory', '11', 48, 'available'],
        'TULT-1112' => ['R13', 'classroom', '11', 49, 'available'],
        'TULT-1113' => ['R14', 'laboratory', '11', 48, 'available'],
        'TULT-1114' => ['R15', 'classroom', '11', 50, 'maintenance'],
    ];

    public function run(): void
    {
        $fri = AcademicUnit::where('code', 'FRI')->firstOrFail();
        $mahasiswa = User::where('email', 'mahasiswa@campus.test')->firstOrFail();
        $dosen = User::where('email', 'dosen@campus.test')->firstOrFail();

        $rooms = $this->seedRooms($fri);
        $this->seedSchedules($rooms);
        $this->seedBookings($rooms, $mahasiswa, $dosen);
    }

    /** @return array<string, Room> nama ruang (R1, R2, ...) => Room */
    private function seedRooms(AcademicUnit $unit): array
    {
        $rooms = [];

        foreach (self::ROOMS as $code => [$name, $type, $floor, $capacity, $status]) {
            $room = Room::firstOrCreate(['code' => $code], [
                'academic_unit_id' => $unit->id,
                'type' => $type,
                'name' => $name,
                'building' => self::BUILDING,
                'floor' => $floor,
                'capacity' => $capacity,
                'status' => $status,
                'description' => $type === 'laboratory'
                    ? 'Laboratorium komputer untuk praktikum dan kerja kelompok.'
                    : 'Ruang kelas untuk perkuliahan dan diskusi.',
            ]);

            $facilities = $type === 'laboratory'
                ? [['PC Komputer', $capacity, 'available'], ['Proyektor', 1, 'available'], ['AC', 2, 'available']]
                : [['Proyektor', 1, 'available'], ['AC', 2, $status === 'maintenance' ? 'maintenance' : 'available']];

            foreach ($facilities as [$facility, $quantity, $condition]) {
                Facility::firstOrCreate(
                    ['room_id' => $room->id, 'name' => $facility],
                    ['quantity' => $quantity, 'status' => $condition]
                );
            }

            $rooms[$name] = $room;
        }

        return $rooms;
    }

    /** Jadwal kuliah Senin-Jumat minggu berjalan. */
    private function seedSchedules(array $rooms): void
    {
        $monday = CarbonImmutable::now()->startOfWeek();

        foreach ([
            ['R3', '08:00', '10:00', 'Statistika Industri'],
            ['R1', '10:00', '12:00', 'Praktikum Basis Data'],
            ['R6', '13:00', '15:00', 'Perancangan Aplikasi Dinamis'],
            ['R5', '15:00', '17:00', 'Praktikum Pemrograman'],
            ['R8', '07:30', '09:30', 'Sistem Produksi'],
        ] as $i => [$roomName, $start, $end, $title]) {
            foreach (range(0, 4) as $day) {
                // Variasi: tiap mata kuliah hanya di 3 hari per minggu.
                if (($day + $i) % 5 >= 3) {
                    continue;
                }

                $date = $monday->addDays($day);

                Schedule::firstOrCreate([
                    'room_id' => $rooms[$roomName]->id,
                    'title' => $title,
                    'start_time' => $date->setTimeFromTimeString($start),
                ], [
                    'end_time' => $date->setTimeFromTimeString($end),
                    'status' => 'scheduled',
                ]);
            }
        }
    }

    /** Satu booking per status, relatif terhadap waktu seeder dijalankan. */
    private function seedBookings(array $rooms, User $mahasiswa, User $dosen): void
    {
        $now = CarbonImmutable::now()->startOfMinute();
        $tomorrow = $now->addDay()->startOfDay();
        $yesterday = $now->subDay()->startOfDay();

        foreach ([
            // Sedang dalam jendela check-in (dibuka 15 menit sebelum mulai).
            ['R2', $mahasiswa, $now->addMinutes(10), $now->addMinutes(130), 20, Booking::STATUS_APPROVED, 'Kerja kelompok tugas besar PAD', []],
            // Sedang dipakai.
            ['R7', $dosen, $now->subMinutes(30), $now->addMinutes(90), 35, Booking::STATUS_CHECKED_IN, 'Asistensi praktikum Basis Data', ['checked_in_at' => $now->subMinutes(25)]],
            ['R9', $mahasiswa, $tomorrow->setTime(9, 0), $tomorrow->setTime(11, 0), 15, Booking::STATUS_PENDING, 'Latihan presentasi seminar', []],
            ['R10', $dosen, $tomorrow->setTime(13, 0), $tomorrow->setTime(15, 0), 40, Booking::STATUS_APPROVED, 'Kuliah pengganti Statistika', []],
            ['R9', $mahasiswa, $tomorrow->addDay()->setTime(8, 0), $tomorrow->addDay()->setTime(10, 0), 45, Booking::STATUS_REJECTED, 'Rapat himpunan', ['rejection_reason' => 'Ruang dipakai ujian praktikum pada jam tersebut.']],
            ['R10', $dosen, $tomorrow->addDay()->setTime(10, 0), $tomorrow->addDay()->setTime(12, 0), 25, Booking::STATUS_CANCELLED, 'Bimbingan tugas akhir', []],
            ['R2', $mahasiswa, $yesterday->setTime(9, 0), $yesterday->setTime(11, 0), 18, Booking::STATUS_COMPLETED, 'Pengolahan data penelitian', ['checked_in_at' => $yesterday->setTime(9, 5), 'checked_out_at' => $yesterday->setTime(10, 50)]],
            ['R7', $dosen, $yesterday->setTime(13, 0), $yesterday->setTime(15, 0), 30, Booking::STATUS_NO_SHOW, 'Pelatihan software simulasi', []],
        ] as [$roomName, $user, $start, $end, $people, $status, $purpose, $extra]) {
            Booking::firstOrCreate([
                'room_id' => $rooms[$roomName]->id,
                'start_time' => $start,
            ], [
                'user_id' => $user->id,
                'requester_name' => $user->name,
                'requester_role' => $user->roleLabel(),
                'purpose' => $purpose,
                'participant_count' => $people,
                'end_time' => $end,
                'status' => $status,
                'qr_token' => in_array($status, Booking::APPROVED_STATUSES, true) ? Str::random(40) : null,
                ...$extra,
            ]);
        }
    }
}
