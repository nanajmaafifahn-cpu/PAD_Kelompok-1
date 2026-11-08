<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Jumlah dan antrean pengajuan pending untuk dashboard pengelola. */
class PendingQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_jumlah_dan_antrean_pending_urut_waktu_mulai_terdekat(): void
    {
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'FRI']);
        $room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'R1', 'type' => 'laboratory',
            'name' => 'R1', 'building' => 'TULT', 'capacity' => 40, 'status' => 'available',
        ]);
        $user = User::factory()->create();

        foreach ([7, 2, 5, 1, 9, 3] as $day) {
            Booking::create([
                'room_id' => $room->id, 'user_id' => $user->id, 'purpose' => "Hari {$day}", 'participant_count' => 5,
                'start_time' => now()->addDays($day), 'end_time' => now()->addDays($day)->addHour(),
                'status' => Booking::STATUS_PENDING,
            ]);
        }
        Booking::create([
            'room_id' => $room->id, 'user_id' => $user->id, 'purpose' => 'Sudah disetujui', 'participant_count' => 5,
            'start_time' => now()->addHours(2), 'end_time' => now()->addHours(3), 'status' => Booking::STATUS_APPROVED,
        ]);

        $service = app(BookingService::class);

        $this->assertSame(6, $service->pendingCount());

        $queue = $service->pendingQueue();
        $this->assertSame(['Hari 1', 'Hari 2', 'Hari 3', 'Hari 5', 'Hari 7'], $queue->pluck('purpose')->all());
        $this->assertTrue($queue->first()->relationLoaded('room'));
        $this->assertTrue($queue->first()->relationLoaded('user'));
    }
}
