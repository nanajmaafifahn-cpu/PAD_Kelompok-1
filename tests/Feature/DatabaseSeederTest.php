<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use App\Support\Scan\ScanResult;
use Database\Seeders\DemoCampusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Memastikan DatabaseSeeder berjalan tanpa error, berisi data kasus nyata,
 * dan seluruh halaman aplikasi bisa dibuka dengan data tersebut (bukti MVC
 * tersambung: route -> controller -> model -> view).
 *
 * Penyesuaian setelah merge ke feature/be-perbaikan-inti (Fase 1-3):
 * - akun QA @cslus.test diganti akun demo @campus.test (5 akun, tanpa Test User);
 * - data QA (5 ruang, 13 booking) + data demo TULT (14 ruang, 8 booking minggu berjalan);
 * - halaman wajib login, JSON dashboard khusus pengelola/admin/pimpinan.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Booking demo dihitung relatif terhadap now(); waktu dibekukan agar seeding berulang identik.
        $this->freezeTime();
    }

    public function test_seeder_mengisi_data_master(): void
    {
        $this->seed();

        $this->assertDatabaseHas('academic_units', ['code' => 'FRI']);
        $this->assertSame(3, AcademicUnit::count());
        $this->assertSame(19, Room::count()); // 5 ruang QA + 14 ruang TULT
        $this->assertSame(5, User::count());
        $this->assertDatabaseHas('users', ['email' => 'pengelola@campus.test', 'role' => 'lab_head']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_seeder_mengisi_tipe_ruang_lab_dan_kelas(): void
    {
        $this->seed();

        $this->assertSame('laboratory', Room::where('code', 'LAB-KOM-01')->value('type'));
        $this->assertSame('laboratory', Room::where('code', 'LAB-MAN-01')->value('type'));
        $this->assertSame('classroom', Room::where('code', 'KLS-201')->value('type'));
    }

    public function test_seeder_berisi_booking_completed_approved_dan_ghost_booking(): void
    {
        $this->seed();

        $this->assertSame(8, Booking::where('status', 'completed')->count());
        $this->assertSame(3, Booking::where('status', 'approved')->count());
        $this->assertSame(4, Booking::where('status', 'no_show')->count());

        foreach (Booking::where('status', 'completed')->get() as $booking) {
            $this->assertNotNull($booking->checked_in_at);
            $this->assertNotNull($booking->checked_out_at);
            $this->assertTrue($booking->isGhostBooking() === false);
        }
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(19, Room::count());
        $this->assertSame(21, Booking::count());
    }

    public function test_semua_halaman_bisa_dibuka_dengan_data_seeder(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'pengelola@campus.test')->firstOrFail());

        foreach (['/', '/rooms', '/rooms/create', '/schedules', '/bookings', '/bookings/create', '/dashboard/utilization'] as $url) {
            $this->followingRedirects()->get($url)->assertOk();
        }
    }

    public function test_dashboard_minggu_lalu_menampilkan_ruang_dari_seeder(): void
    {
        $this->seed();
        $this->actingAs(User::where('email', 'pengelola@campus.test')->firstOrFail());

        $monday = Carbon::now()->startOfWeek()->subWeek();

        $this->getJson('/dashboard/utilization?start=' . $monday->toDateString() . '&end=' . $monday->copy()->addDays(4)->toDateString())
            ->assertOk()
            ->assertJsonFragment(['room_code' => 'LAB-KOM-01'])
            ->assertJsonFragment(['room_code' => 'LAB-MAN-01']);
    }

    // ---------------- Data demo F1 ----------------

    public function test_ruang_tult_sesuai_desain_dan_semua_ruang_punya_qr_token(): void
    {
        $this->seed();

        foreach (['TULT-0805' => 'R1', 'TULT-0807' => 'R2', 'TULT-0819' => 'R3'] as $code => $name) {
            $this->assertDatabaseHas('rooms', ['code' => $code, 'name' => $name]);
        }

        $this->assertSame(14, Room::where('code', 'like', 'TULT-%')->count());
        $this->assertSame(0, Room::whereNull('qr_token')->count());
        $this->assertSame(Room::count(), Room::distinct()->count('qr_token'));
        $this->assertEqualsCanonicalizing(
            ['laboratory', 'classroom'],
            Room::where('code', 'like', 'TULT-%')->distinct()->pluck('type')->all()
        );
        $this->assertTrue(Room::where('code', 'like', 'TULT-%')->whereBetween('capacity', [48, 50])->count() === 14);
    }

    public function test_data_demo_berisi_setiap_status_dan_jadwal_minggu_ini(): void
    {
        $this->seed();

        foreach ([
            Booking::STATUS_PENDING, Booking::STATUS_APPROVED, Booking::STATUS_REJECTED, Booking::STATUS_CANCELLED,
            Booking::STATUS_CHECKED_IN, Booking::STATUS_COMPLETED, Booking::STATUS_NO_SHOW,
        ] as $status) {
            $this->assertTrue(
                Booking::where('status', $status)->whereHas('room', fn ($q) => $q->where('code', 'like', 'TULT-%'))->exists(),
                "Tidak ada booking demo berstatus {$status}"
            );
        }

        $this->assertGreaterThan(0, \App\Models\Schedule::whereBetween('start_time', [
            now()->startOfWeek(), now()->endOfWeek(),
        ])->count());
        $this->assertTrue(Room::where('code', 'like', 'TULT-%')->whereHas('facilities', fn ($q) => $q->where('name', 'PC Komputer'))->exists());
    }

    public function test_mahasiswa_demo_punya_booking_yang_sedang_dalam_jendela_check_in(): void
    {
        $this->seed();

        $mahasiswa = User::where('email', 'mahasiswa@campus.test')->firstOrFail();
        $r2 = Room::where('code', 'TULT-0807')->firstOrFail();

        $result = app(BookingService::class)->evaluateScan($mahasiswa, $r2);

        $this->assertSame(ScanResult::ACTION_CHECK_IN, $result->action);
        $this->assertSame('occupied', Room::where('code', 'TULT-0913')->firstOrFail()->getLiveStatus());
        $this->assertCount(14, DemoCampusSeeder::ROOMS);
    }
}
