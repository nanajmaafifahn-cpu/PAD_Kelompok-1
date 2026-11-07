<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusControllerTest extends TestCase
{
    use RefreshDatabase;

    // Tambah ruang & jadwal khusus pengelola sejak Fase 1 (auth + role).
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]));
    }

    private function createAcademicUnit(): AcademicUnit
    {
        return AcademicUnit::create([
            'code' => 'FRI',
            'name' => 'Fakultas Rekayasa Industri',
        ]);
    }

    private function createRoom(): Room
    {
        $unit = $this->createAcademicUnit();

        return Room::create([
            'academic_unit_id' => $unit->id,
            'code' => 'C301',
            'name' => 'Laboratorium C301',
            'building' => 'Gedung C',
            'floor' => '3',
            'capacity' => 30,
            'status' => 'available',
        ]);
    }

    public function test_room_can_be_stored(): void
    {
        $unit = $this->createAcademicUnit();

        $response = $this->post(route('rooms.store'), [
            'academic_unit_id' => $unit->id,
            'code' => 'C301',
            'type' => 'laboratory',
            'name' => 'Laboratorium C301',
            'building' => 'Gedung C',
            'floor' => '3',
            'capacity' => 30,
            'status' => 'available',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('rooms.index'));

        $this->assertDatabaseHas('rooms', [
            'code' => 'C301',
            'capacity' => 30,
            'status' => 'available',
        ]);
    }

    public function test_room_rejects_negative_capacity(): void
    {
        $unit = $this->createAcademicUnit();

        $response = $this->post(route('rooms.store'), [
            'academic_unit_id' => $unit->id,
            'code' => 'C302',
            'name' => 'Laboratorium C302',
            'building' => 'Gedung C',
            'capacity' => -5,
            'status' => 'available',
        ]);

        $response->assertSessionHasErrors('capacity');

        $this->assertDatabaseMissing('rooms', [
            'code' => 'C302',
        ]);
    }

    public function test_room_rejects_duplicate_code(): void
    {
        $room = $this->createRoom();

        $response = $this->post(route('rooms.store'), [
            'academic_unit_id' => $room->academic_unit_id,
            'code' => 'C301',
            'name' => 'Laboratorium Duplikat',
            'building' => 'Gedung C',
            'capacity' => 20,
            'status' => 'available',
        ]);

        $response->assertSessionHasErrors('code');

        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_schedule_can_be_stored(): void
    {
        $room = $this->createRoom();

        $response = $this->post(route('schedules.store'), [
            'room_id' => $room->id,
            'title' => 'Praktikum ALPRO',
            'start_time' => '2026-11-02 08:00:00',
            'end_time' => '2026-11-02 10:00:00',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'room_id' => $room->id,
            'title' => 'Praktikum ALPRO',
            'status' => 'scheduled',
        ]);
    }

    public function test_schedule_rejects_invalid_time(): void
    {
        $room = $this->createRoom();

        $response = $this->post(route('schedules.store'), [
            'room_id' => $room->id,
            'title' => 'Praktikum ALPRO',
            'start_time' => '2026-11-02 10:00:00',
            'end_time' => '2026-11-02 08:00:00',
        ]);

        $response->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('schedules', 0);
    }
}
