<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Dashboard per role (route dashboard) memakai data demo seeder. */
class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Jakarta'));
        $this->seed();
    }

    private function login(string $email): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_dashboard_peminjam_berisi_ringkasan_miliknya_dan_peminjaman_berikutnya(): void
    {
        $user = $this->login('mahasiswa@campus.test');
        $mine = Booking::where('user_id', $user->id);

        $response = $this->get(route('dashboard'))->assertOk()->assertViewIs('dashboard.borrower');

        $this->assertSame([
            'total' => (clone $mine)->count(),
            'pending' => (clone $mine)->where('status', 'pending')->count(),
            'active' => (clone $mine)->whereIn('status', ['approved', 'checked_in'])->count(),
            'completed' => (clone $mine)->where('status', 'completed')->count(),
        ], $response->viewData('stats'));

        // Booking demo R2 mulai 10 menit lagi -> peminjaman berikutnya & sudah bisa dipindai.
        $this->assertSame('TULT-0807', $response->viewData('nextBooking')->room->code);
        $this->assertTrue($response->viewData('nextBookingScannable'));
        $this->assertCount(5, $response->viewData('recentBookings'));
        $this->assertTrue($response->viewData('recentBookings')->every(fn ($b) => $b->user_id === $user->id));
        $response->assertSee(route('scan.index'), false);
    }

    public function test_dashboard_pengelola_berisi_status_ruang_dan_antrean_pending(): void
    {
        $this->login('pengelola@campus.test');

        $response = $this->get(route('dashboard'))->assertOk()->assertViewIs('dashboard.manager');
        $stats = $response->viewData('stats');

        $this->assertSame(Room::count() - Room::where('status', 'maintenance')->count(), $stats['rooms_active']);
        $this->assertSame(1, $stats['occupied_now']); // R7 checked_in
        $this->assertSame(Booking::where('status', 'pending')->count(), $stats['pending']);
        $this->assertCount(Room::count(), $response->viewData('liveStatuses'));
        $this->assertTrue($response->viewData('pendingBookings')->every(fn ($b) => $b->status === 'pending'));
        // Pengelola boleh menyetujui / menolak langsung dari dashboard (form approve + dialog tolak).
        $first = $response->viewData('pendingBookings')->first();
        $response->assertSee(route('bookings.approve', $first), false)
            ->assertSee('data-dialog-open="reject-' . $first->id . '"', false);
    }

    public function test_pimpinan_melihat_dashboard_pengelola_tanpa_tombol_ubah(): void
    {
        $this->login('pimpinan@campus.test');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.manager')
            ->assertDontSee('/approve', false)
            ->assertDontSee('data-dialog-open="reject-', false);
    }
}
