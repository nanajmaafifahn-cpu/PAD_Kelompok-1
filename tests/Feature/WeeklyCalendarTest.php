<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\CalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Data kalender mingguan untuk halaman Jadwal Ruangan. */
class WeeklyCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_minggu_berjalan_berisi_jadwal_dan_peminjaman_disetujui_per_hari(): void
    {
        // Rabu 11 Nov 2026 -> minggu 9-15 Nov.
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Jakarta'));

        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'TULT-0805', 'type' => 'laboratory',
            'name' => 'R1', 'building' => 'TULT', 'capacity' => 48, 'status' => 'available',
        ]);
        $user = User::factory()->create();

        $schedule = fn (string $at, string $title, string $status = 'scheduled') => Schedule::create([
            'room_id' => $room->id, 'title' => $title, 'status' => $status,
            'start_time' => $at, 'end_time' => CarbonImmutable::parse($at)->addHours(2),
        ]);
        $booking = fn (string $at, string $purpose, string $status) => Booking::create([
            'room_id' => $room->id, 'user_id' => $user->id, 'purpose' => $purpose, 'participant_count' => 5,
            'start_time' => $at, 'end_time' => CarbonImmutable::parse($at)->addHours(2), 'status' => $status,
        ]);

        $schedule('2026-11-09 08:00', 'Statistika Senin');
        $schedule('2026-11-11 10:00', 'Basis Data Rabu');
        $schedule('2026-11-11 07:00', 'Kuliah batal', 'cancelled');
        $schedule('2026-11-16 08:00', 'Minggu depan');
        $booking('2026-11-11 13:00', 'Kerja kelompok', Booking::STATUS_APPROVED);
        $booking('2026-11-10 13:00', 'Sudah selesai', Booking::STATUS_COMPLETED);
        $booking('2026-11-12 13:00', 'Masih pending', Booking::STATUS_PENDING);
        $booking('2026-11-12 15:00', 'Ditolak', Booking::STATUS_REJECTED);

        $this->actingAs($user);
        $week = $this->get(route('schedules.index'))->assertOk()->assertViewHas('eventTypes')->viewData('week');

        $this->assertSame('2026-11-09', $week['start']->toDateString());
        $this->assertSame('2026-11-15', $week['end']->toDateString());
        $this->assertCount(7, $week['days']);
        $this->assertSame(4, $week['total']);

        $titles = collect($week['days'])->mapWithKeys(fn ($d) => [
            $d['date']->toDateString() => $d['events']->pluck('title')->all(),
        ])->all();

        $this->assertSame(['Statistika Senin'], $titles['2026-11-09']);
        $this->assertSame(['Sudah selesai'], $titles['2026-11-10']);
        $this->assertSame(['Basis Data Rabu', 'Kerja kelompok'], $titles['2026-11-11']); // urut jam mulai
        $this->assertSame([], $titles['2026-11-12']);

        $wednesday = $week['days'][2];
        $this->assertTrue($wednesday['is_today']);
        $this->assertSame([CalendarService::TYPE_SCHEDULE, CalendarService::TYPE_BOOKING], $wednesday['events']->pluck('type')->all());
        $this->assertSame('R1', $wednesday['events'][0]['room_name']);
    }
}
