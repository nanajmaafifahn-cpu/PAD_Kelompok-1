<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BE-09: total & rata-rata utilisasi tersedia di summary (tidak dihitung di view). */
class UtilizationSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_berisi_total_yang_konsisten_dengan_data_per_ruang(): void
    {
        // Rabu siang: data demo "kemarin" dan "30 menit lalu" pasti jatuh di minggu yang sama.
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Jakarta'));
        $this->seed();
        $this->actingAs(User::where('email', 'pengelola@campus.test')->firstOrFail());

        // Minggu lalu (data QA) dan minggu ini (data demo, ada sesi checked_in).
        foreach ([
            CarbonImmutable::now()->startOfWeek()->subWeek(),
            CarbonImmutable::now()->startOfWeek(),
        ] as $monday) {
            $json = $this->getJson(route('dashboard.utilization', [
                'start' => $monday->toDateString(),
                'end' => $monday->addDays(6)->toDateString(),
            ]))->assertOk()->json();

            $rooms = collect($json['rooms']);
            $summary = $json['summary'];

            $this->assertEqualsWithDelta($rooms->sum('scheduled_hours'), $summary['total_scheduled_hours'], 0.01);
            $this->assertEqualsWithDelta($rooms->sum('actual_hours'), $summary['total_actual_hours'], 0.01);
            $this->assertSame($rooms->sum('ghost_bookings'), $summary['total_ghost_bookings']);
            $this->assertEqualsWithDelta(
                $rooms->sum(fn ($room) => $room['metrics']['ghost_booking_hours']),
                $summary['total_ghost_booking_hours'],
                0.01
            );
            $this->assertEqualsWithDelta(
                $rooms->sum(fn ($room) => $room['metrics']['ghost_opportunity_loss_seat_hours']),
                $summary['total_ghost_opportunity_loss_seat_hours'],
                0.01
            );
            $this->assertEqualsWithDelta(round($rooms->avg('utilization_rate'), 1), $summary['average_utilization_rate'], 0.01);
            $this->assertEqualsWithDelta(round($rooms->avg('scheduled_rate'), 1), $summary['average_scheduled_rate'], 0.01);
            $this->assertEqualsWithDelta(
                round($rooms->avg(fn ($room) => $room['metrics']['seat_occupancy_efficiency']), 1),
                $summary['average_seat_occupancy_efficiency'],
                0.01
            );
            $this->assertSame($rooms->where('live_status', 'maintenance')->count(), $summary['maintenance_now']);
            $this->assertArrayHasKey('open_sessions', $summary);
        }

        // Minggu ini: booking demo R7 sedang checked_in tanpa check-out.
        $this->assertSame(1, $summary['open_sessions']);
        $this->assertSame(2, $summary['maintenance_now']); // KLS-203 & R15
        $this->assertSame(1, $summary['total_ghost_bookings']); // no_show demo R7 kemarin (Selasa)

        $this->get(route('dashboard.utilization', [
            'start' => $monday->toDateString(),
            'end' => $monday->addDays(6)->toDateString(),
        ]))->assertOk()
            ->assertSee('Efisiensi okupansi kursi')
            ->assertSee('Estimasi kursi-jam tertahan')
            ->assertSee('Kapasitas indikatif');
    }
}
