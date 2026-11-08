<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Halaman "Ruangan & Foto" (rooms.manage) terpisah dari Jelajahi Ruangan (rooms.index). */
class RoomManagePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengelola_melihat_tabel_data_master_dan_role_lain_ditolak(): void
    {
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'TULT-0805', 'type' => 'laboratory',
            'name' => 'R1', 'building' => 'TULT', 'capacity' => 48, 'status' => 'available',
        ]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]))
            ->get(route('rooms.manage'))
            ->assertOk()
            ->assertViewHas('liveStatuses', [$room->id => Room::LIVE_AVAILABLE])
            ->assertSee('TULT-0805')
            ->assertSee(route('rooms.edit', $room), false);

        foreach ([User::ROLE_BORROWER, User::ROLE_LEADER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('rooms.manage'))
                ->assertForbidden();
        }
    }
}
