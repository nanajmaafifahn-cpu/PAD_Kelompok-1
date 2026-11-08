<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\CalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Detail ruang (rooms.show): atribut, komputer, status live, kegiatan mendatang. */
class RoomShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_ruang_menampilkan_atribut_dan_hanya_kegiatan_disetujui_yang_belum_selesai(): void
    {
        $this->freezeTime();
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);
        $room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'TULT-0805', 'type' => 'laboratory',
            'name' => 'R1', 'building' => 'TULT', 'floor' => '8', 'capacity' => 48, 'status' => 'available',
        ]);
        $room->facilities()->create(['name' => 'PC Komputer', 'quantity' => 48, 'status' => 'available']);
        $room->facilities()->create(['name' => 'Proyektor', 'quantity' => 1, 'status' => 'available']);
        $user = User::factory()->create(['role' => User::ROLE_BORROWER]);

        $make = fn (string $status, int $dayOffset, string $purpose) => Booking::create([
            'room_id' => $room->id, 'user_id' => $user->id, 'purpose' => $purpose, 'participant_count' => 5,
            'start_time' => now()->addDays($dayOffset), 'end_time' => now()->addDays($dayOffset)->addHours(2), 'status' => $status,
        ]);
        $make(Booking::STATUS_APPROVED, 2, 'Kerja kelompok disetujui');
        $make(Booking::STATUS_PENDING, 1, 'Masih pending');
        $make(Booking::STATUS_COMPLETED, -3, 'Sudah lewat');
        Schedule::create([
            'room_id' => $room->id, 'title' => 'Praktikum Basis Data', 'status' => 'scheduled',
            'start_time' => now()->addDay(), 'end_time' => now()->addDay()->addHours(2),
        ]);
        Schedule::create([
            'room_id' => $room->id, 'title' => 'Kuliah batal', 'status' => 'cancelled',
            'start_time' => now()->addDays(3), 'end_time' => now()->addDays(3)->addHour(),
        ]);

        $response = $this->actingAs($user)->get(route('rooms.show', $room))
            ->assertOk()
            ->assertViewHas('computerCount', 48)
            ->assertViewHas('liveStatus', Room::LIVE_AVAILABLE)
            ->assertSee('Praktikum Basis Data')
            ->assertSee('Kerja kelompok disetujui')
            ->assertDontSee('Masih pending')
            ->assertDontSee('Sudah lewat')
            ->assertDontSee('Kuliah batal')
            ->assertSee(route('bookings.create', ['room_id' => $room->id]), false);

        $events = $response->viewData('upcomingEvents');
        $this->assertSame([CalendarService::TYPE_SCHEDULE, CalendarService::TYPE_BOOKING], $events->pluck('type')->all());
    }

    public function test_ruang_tanpa_komputer_dan_route_tidak_dikenal(): void
    {
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'TULT-0819', 'type' => 'classroom',
            'name' => 'R3', 'building' => 'TULT', 'capacity' => 50, 'status' => 'available',
        ]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_LEADER]));

        $this->get(route('rooms.show', $room))->assertOk()->assertViewHas('computerCount', 0)->assertSee('Nonkomputer')
            ->assertDontSee('Ajukan Peminjaman');
        $this->get('/rooms/999')->assertNotFound();
    }
}
