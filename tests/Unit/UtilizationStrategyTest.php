<?php

namespace Tests\Unit;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Strategies\ClassroomUtilizationStrategy;
use App\Strategies\LaboratoryUtilizationStrategy;
use App\Strategies\UtilizationCalculatorContext;
use App\Strategies\UtilizationStrategyInterface;
use App\Support\Utilization\UtilizationPeriod;
use App\Support\Utilization\UtilizationResult;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Menguji perhitungan ClassroomUtilizationStrategy dan LaboratoryUtilizationStrategy.
 *
 * Semua skenario memakai satu minggu tetap: Senin 5 Oktober 2026 s.d. Jumat 9 Oktober 2026.
 * Jam tersedia = 5 hari kerja x 10 jam (07.00-17.00) = 50 jam.
 * Angka harapan di setiap test dihitung manual (lihat komentar).
 */
class UtilizationStrategyTest extends TestCase
{
    use RefreshDatabase;

    private UtilizationPeriod $week;
    private User $borrower;
    private AcademicUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->week = new UtilizationPeriod(
            CarbonImmutable::parse('2026-10-05'),
            CarbonImmutable::parse('2026-10-09')
        );
        $this->borrower = User::create([
            'name' => 'Peminjam Uji',
            'email' => 'uji@campus.test',
            'password' => 'password',
        ]);
        $this->unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);
    }

    // ------------------------------------------------------------------
    // Data bantu
    // ------------------------------------------------------------------

    private function room(string $type, int $capacity): Room
    {
        $room = Room::create([
            'academic_unit_id' => $this->unit->id,
            'code' => strtoupper(substr($type, 0, 3)) . '-' . $capacity,
            'name' => "Ruang {$type}",
            'building' => 'Gedung FRI',
            'capacity' => $capacity,
            'status' => 'available',
        ]);
        $room->forceFill(['type' => $type])->save();

        return $room;
    }

    /** Booking pada hari ke-$day minggu uji (0 = Senin). Completed berarti check-in/out tepat waktu. */
    private function booking(Room $room, int $day, string $start, string $end, int $people, string $status): Booking
    {
        $startAt = CarbonImmutable::parse('2026-10-05 ' . $start)->addDays($day);
        $endAt = CarbonImmutable::parse('2026-10-05 ' . $end)->addDays($day);
        $used = in_array($status, ['completed', 'checked_in'], true);

        return Booking::create([
            'room_id' => $room->id,
            'user_id' => $this->borrower->id,
            'purpose' => 'Kegiatan uji',
            'participant_count' => $people,
            'start_time' => $startAt,
            'end_time' => $endAt,
            'status' => $status,
            'checked_in_at' => $used ? $startAt : null,
            'checked_out_at' => $status === 'completed' ? $endAt : null,
        ]);
    }

    // ------------------------------------------------------------------
    // Ruang kelas: utilisasi = jam aktual / jam tersedia x 100
    // ------------------------------------------------------------------

    public function test_kelas_menghitung_jam_terjadwal_aktual_dan_ghost_booking(): void
    {
        $room = $this->room('classroom', 40);
        $this->booking($room, 0, '08:00', '10:00', 20, 'completed'); // 2 jam dipakai
        $this->booking($room, 1, '13:00', '16:00', 30, 'completed'); // 3 jam dipakai
        $this->booking($room, 2, '08:00', '10:00', 25, 'no_show');   // 2 jam ghost
        Schedule::create([
            'room_id' => $room->id,
            'title' => 'Kuliah PAD',
            'start_time' => '2026-10-08 07:00:00',
            'end_time' => '2026-10-08 10:00:00',
        ]);                                                            // 3 jam jadwal

        $result = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(50.0, $result->availableHours);
        $this->assertSame(10.0, $result->scheduledHours);   // 2 + 3 + 2 + 3
        $this->assertSame(20.0, $result->scheduledRate);    // 10 / 50
        $this->assertSame(5.0, $result->actualHours);       // 2 + 3
        $this->assertSame(10.0, $result->utilizationRate);  // 5 / 50
        $this->assertSame(3, $result->approvedBookings);
        $this->assertSame(1, $result->ghostBookings);
        $this->assertSame(33.3, $result->ghostRate);        // 1 / 3
        $this->assertSame(UtilizationResult::CATEGORY_UNDERUTILIZED, $result->category); // 10% < 30%
    }

    public function test_kelas_menghitung_rasio_realisasi_dan_okupansi_kursi(): void
    {
        $room = $this->room('classroom', 40);
        $this->booking($room, 0, '08:00', '10:00', 20, 'completed');
        $this->booking($room, 1, '13:00', '16:00', 30, 'completed');
        $this->booking($room, 2, '08:00', '10:00', 25, 'no_show');

        $result = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(71.4, $result->metrics['realization_ratio']);    // 5 / 7 jam terjadwal
        $this->assertSame(62.5, $result->metrics['seat_occupancy_ratio']); // rata-rata(20/40, 30/40)
    }

    public function test_kelas_penuh_setiap_hari_termasuk_overutilized(): void
    {
        $room = $this->room('classroom', 40);
        foreach (range(0, 4) as $day) {
            $this->booking($room, $day, '07:00', '16:00', 35, 'completed'); // 9 jam x 5 hari
        }

        $result = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(90.0, $result->utilizationRate); // 45 / 50
        $this->assertSame(UtilizationResult::CATEGORY_OVERUTILIZED, $result->category); // >= 80%
    }

    public function test_jam_di_luar_jam_operasional_tidak_dihitung(): void
    {
        $room = $this->room('classroom', 40);
        $this->booking($room, 0, '16:00', '19:00', 10, 'completed'); // hanya 16.00-17.00 yang dihitung

        $result = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(1.0, $result->actualHours);
        $this->assertSame(2.0, $result->utilizationRate); // 1 / 50
    }

    public function test_booking_pending_dan_rejected_tidak_dihitung(): void
    {
        $room = $this->room('classroom', 40);
        $this->booking($room, 0, '08:00', '10:00', 20, 'pending');
        $this->booking($room, 1, '08:00', '10:00', 20, 'rejected');

        $result = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(0.0, $result->scheduledHours);
        $this->assertSame(0, $result->approvedBookings);
    }

    // ------------------------------------------------------------------
    // Laboratorium: (jam aktual + setup 30 menit/sesi - 0,5 x jam ghost) / jam tersedia
    // ------------------------------------------------------------------

    public function test_lab_menambah_waktu_setup_dan_mengurangi_penalti_ghost(): void
    {
        $room = $this->room('laboratory', 30);
        $this->booking($room, 0, '08:00', '11:00', 28, 'completed'); // 3 jam
        $this->booking($room, 1, '08:00', '12:00', 30, 'completed'); // 4 jam
        $this->booking($room, 3, '13:00', '16:00', 26, 'completed'); // 3 jam
        $this->booking($room, 2, '13:00', '15:00', 20, 'no_show');   // 2 jam ghost

        $result = (new LaboratoryUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(10.0, $result->actualHours);                 // 3 + 4 + 3
        $this->assertSame(1.5, $result->metrics['setup_hours']);       // 3 sesi x 0,5 jam
        $this->assertSame(1.0, $result->metrics['ghost_penalty_hours']); // 2 jam x 0,5
        $this->assertSame(21.0, $result->utilizationRate);             // (10 + 1,5 - 1) / 50
        $this->assertSame(20.0, $result->metrics['raw_actual_rate']);  // 10 / 50
        $this->assertSame(24.0, $result->scheduledRate);               // 12 / 50
        $this->assertSame(25.0, $result->ghostRate);                   // 1 / 4
    }

    public function test_lab_tidak_pernah_bernilai_negatif(): void
    {
        $room = $this->room('laboratory', 30);
        $this->booking($room, 0, '08:00', '12:00', 20, 'no_show'); // hanya ghost, tanpa pemakaian

        $result = (new LaboratoryUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(0.0, $result->utilizationRate);
    }

    // ------------------------------------------------------------------
    // Bukti pola Strategy
    // ------------------------------------------------------------------

    public function test_data_sama_menghasilkan_angka_berbeda_sesuai_jenis_ruang(): void
    {
        $room = $this->room('laboratory', 30);
        $this->booking($room, 0, '08:00', '11:00', 28, 'completed');
        $this->booking($room, 1, '08:00', '12:00', 30, 'completed');
        $this->booking($room, 3, '13:00', '16:00', 26, 'completed');
        $this->booking($room, 2, '13:00', '15:00', 20, 'no_show');

        $asClassroom = (new ClassroomUtilizationStrategy())->calculate($room, $this->week);
        $asLaboratory = (new LaboratoryUtilizationStrategy())->calculate($room, $this->week);

        $this->assertSame(20.0, $asClassroom->utilizationRate);  // 10 / 50
        $this->assertSame(21.0, $asLaboratory->utilizationRate); // (10 + 1,5 - 1) / 50
    }

    public function test_context_memilih_strategy_sesuai_tipe_ruang(): void
    {
        $context = $this->app->make(UtilizationCalculatorContext::class);

        $this->assertInstanceOf(ClassroomUtilizationStrategy::class, $context->strategyFor($this->room('classroom', 40)));
        $this->assertInstanceOf(LaboratoryUtilizationStrategy::class, $context->strategyFor($this->room('laboratory', 30)));
    }

    public function test_semua_strategy_terdaftar_memakai_interface_yang_sama(): void
    {
        $context = $this->app->make(UtilizationCalculatorContext::class);

        $this->assertContains('classroom', $context->supportedTypes());
        $this->assertContains('laboratory', $context->supportedTypes());
        $this->assertTrue(is_subclass_of(ClassroomUtilizationStrategy::class, UtilizationStrategyInterface::class));
        $this->assertTrue(is_subclass_of(LaboratoryUtilizationStrategy::class, UtilizationStrategyInterface::class));
    }

    public function test_context_menolak_tipe_ruang_yang_belum_punya_strategy(): void
    {
        $context = $this->app->make(UtilizationCalculatorContext::class);

        $this->expectException(InvalidArgumentException::class);

        $context->strategyFor($this->room('kolam-renang', 20));
    }
}
