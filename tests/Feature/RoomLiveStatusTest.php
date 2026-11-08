<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\RoomStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Status live ruang dikirim controller (RoomStatusService), tidak dihitung lewat query di view.
 */
class RoomLiveStatusTest extends TestCase
{
    use RefreshDatabase;

    private AcademicUnit $unit;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $this->user = User::factory()->create();
    }

    private function room(string $code, string $status = 'available'): Room
    {
        return Room::create([
            'academic_unit_id' => $this->unit->id, 'code' => $code, 'type' => 'laboratory',
            'name' => $code, 'building' => 'TULT', 'capacity' => 40, 'status' => $status,
        ]);
    }

    private function booking(Room $room, string $status, int $startOffsetMinutes, int $durationMinutes = 120): void
    {
        Booking::create([
            'room_id' => $room->id, 'user_id' => $this->user->id, 'purpose' => 'x', 'participant_count' => 5,
            'start_time' => now()->addMinutes($startOffsetMinutes),
            'end_time' => now()->addMinutes($startOffsetMinutes + $durationMinutes),
            'status' => $status,
        ]);
    }

    public function test_status_live_massal_sama_dengan_aturan_per_ruang(): void
    {
        $available = $this->room('A');
        $booked = $this->room('B');
        $occupied = $this->room('C');
        $maintenance = $this->room('D', 'maintenance');
        $laterToday = $this->room('E');

        $this->booking($booked, Booking::STATUS_APPROVED, 60);
        $this->booking($occupied, Booking::STATUS_CHECKED_IN, -30);
        $this->booking($laterToday, Booking::STATUS_APPROVED, 300);

        $statuses = app(RoomStatusService::class)->forRooms(Room::all());

        $this->assertSame([
            $available->id => Room::LIVE_AVAILABLE,
            $booked->id => Room::LIVE_BOOKED,
            $occupied->id => Room::LIVE_OCCUPIED,
            $maintenance->id => Room::LIVE_MAINTENANCE,
            $laterToday->id => Room::LIVE_AVAILABLE,
        ], $statuses);

        foreach (Room::all() as $room) {
            $this->assertSame($room->getLiveStatus(), $statuses[$room->id], "Beda aturan untuk {$room->code}");
        }

        $this->assertSame(
            ['available' => 2, 'occupied' => 1, 'booked' => 1, 'maintenance' => 1],
            app(RoomStatusService::class)->countByStatus($statuses)
        );
    }

    public function test_daftar_ruang_menerima_status_live_dan_jumlah_query_tidak_bertambah_per_ruang(): void
    {
        $this->actingAs($this->user);

        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('rooms.index'))->assertOk()->assertViewHas('liveStatuses');
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        foreach (range(1, 2) as $i) {
            $this->booking($this->room("X{$i}"), Booking::STATUS_CHECKED_IN, -10);
        }
        $few = $countQueries();

        foreach (range(3, 9) as $i) {
            $this->booking($this->room("X{$i}"), Booking::STATUS_CHECKED_IN, -10);
        }
        $many = $countQueries();

        $this->assertSame($few, $many, 'Jumlah query halaman ruang tidak boleh naik mengikuti jumlah ruang.');
        $this->get(route('rooms.index'))->assertViewHas(
            'liveStatuses',
            fn (array $statuses) => count($statuses) === 9 && array_values(array_unique($statuses)) === [Room::LIVE_OCCUPIED]
        );
    }
}
