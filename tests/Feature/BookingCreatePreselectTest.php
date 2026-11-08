<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** bookings.create?room_id=.. memilih ruang otomatis (dari tombol Ajukan Peminjaman di detail ruang). */
class BookingCreatePreselectTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_id_di_query_memilih_ruang_kecuali_tidak_valid_atau_maintenance(): void
    {
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $make = fn (string $code, string $status) => Room::create([
            'academic_unit_id' => $unit->id, 'code' => $code, 'type' => 'classroom',
            'name' => $code, 'building' => 'TULT', 'capacity' => 40, 'status' => $status,
        ]);
        $r1 = $make('R1', 'available');
        $r2 = $make('R2', 'maintenance');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_BORROWER]));

        $html = $this->get(route('bookings.create', ['room_id' => $r1->id]))
            ->assertOk()
            ->assertViewHas('selectedRoomId', $r1->id)
            ->getContent();
        // <option value="{id}" ... selected> (tidak bergantung pada spasi/markup lain)
        $this->assertMatchesRegularExpression('/<option value="' . $r1->id . '"[^>]*\sselected/', $html);

        $this->get(route('bookings.create', ['room_id' => $r2->id]))->assertViewHas('selectedRoomId', null);
        $this->get(route('bookings.create', ['room_id' => 999]))->assertViewHas('selectedRoomId', null);
        $this->get(route('bookings.create', ['room_id' => 'abc']))->assertViewHas('selectedRoomId', null);
        $this->get(route('bookings.create'))->assertViewHas('selectedRoomId', null);
    }
}
