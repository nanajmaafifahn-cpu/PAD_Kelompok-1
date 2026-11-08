<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 1: login/register/logout, penguncian akses per role, rate limiting,
 * proteksi JSON dashboard, timezone, dan booking atas nama user login.
 */
class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function createRoom(): Room
    {
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);

        return Room::create([
            'academic_unit_id' => $unit->id,
            'code' => 'TULT-0805',
            'name' => 'Lab Komputer R1',
            'building' => 'TULT',
            'floor' => '8',
            'capacity' => 48,
            'status' => 'available',
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        foreach (['/', '/dashboard', '/rooms', '/schedules', '/bookings', '/bookings/create'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        $this->post(route('bookings.store'), [])->assertRedirect(route('login'));
    }

    public function test_register_selalu_menghasilkan_borrower(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Budi Mahasiswa',
            'email' => 'budi@campus.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'admin', // dicoba disusupkan, harus diabaikan
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'budi@campus.test', 'role' => User::ROLE_BORROWER]);
    }

    public function test_login_berhasil_dan_home_mengarah_sesuai_role(): void
    {
        $borrower = $this->userWithRole(User::ROLE_BORROWER);
        $labHead = $this->userWithRole(User::ROLE_LAB_HEAD);

        $this->post(route('login.store'), ['email' => $borrower->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
        // '/' selalu ke dashboard; isinya sesuai role.
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertViewIs('dashboard.borrower');

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $labHead->email, 'password' => 'password']);
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertViewIs('dashboard.manager');
    }

    public function test_login_gagal_menampilkan_pesan(): void
    {
        $user = $this->userWithRole(User::ROLE_BORROWER);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_gagal_keenam_kena_throttle(): void
    {
        $user = $this->userWithRole(User::ROLE_BORROWER);

        for ($i = 1; $i <= 5; $i++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'salah'])
                ->assertSessionHasErrors('email');
        }

        // Percobaan ke-6, walau password benar, ditolak oleh rate limiter.
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('throttle');

        $this->assertGuest();
    }

    public function test_borrower_ditolak_di_halaman_pengelola(): void
    {
        $borrower = $this->userWithRole(User::ROLE_BORROWER);
        $room = $this->createRoom();

        $this->actingAs($borrower);

        $this->get(route('rooms.create'))->assertForbidden();
        $this->post(route('rooms.store'), [])->assertForbidden();
        $this->post(route('schedules.store'), [])->assertForbidden();

        $booking = Booking::create([
            'room_id' => $room->id,
            'user_id' => $borrower->id,
            'purpose' => 'Belajar kelompok',
            'participant_count' => 5,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
            'status' => Booking::STATUS_PENDING,
        ]);

        $this->post(route('bookings.approve', $booking))->assertForbidden();
        $this->post(route('bookings.reject', $booking), ['rejection_reason' => 'x'])->assertForbidden();
        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);

        // Halaman baca tetap terbuka untuk borrower.
        $this->get(route('rooms.index'))->assertOk();
        $this->get(route('bookings.mine'))->assertOk();
        // Daftar semua booking bukan untuk peminjam: diarahkan ke "Peminjaman Saya".
        $this->get(route('bookings.index'))->assertRedirect(route('bookings.mine'));
    }

    public function test_pengelola_boleh_membuka_halaman_tambah_ruang(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_LAB_HEAD))
            ->get(route('rooms.create'))
            ->assertOk();
    }

    public function test_pimpinan_hanya_membaca_dan_tidak_bisa_mengajukan_booking(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_LEADER));

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('bookings.create'))->assertForbidden();
        $this->post(route('bookings.store'), [])->assertForbidden();
    }

    public function test_json_dashboard_hanya_untuk_pengelola_admin_pimpinan(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_BORROWER));
        $this->getJson(route('dashboard.utilization'))->assertForbidden();
        // Laporan Utilisasi (HTML) juga khusus pengelola/admin/pimpinan; peminjam punya dashboard sendiri.
        $this->get(route('dashboard.utilization'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();

        foreach ([User::ROLE_LAB_HEAD, User::ROLE_ADMIN, User::ROLE_LEADER] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->getJson(route('dashboard.utilization'))
                ->assertOk()
                ->assertJsonStructure(['period', 'summary', 'rooms']);
        }
    }

    public function test_timezone_aplikasi_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    public function test_booking_memakai_identitas_user_login_bukan_input_bebas(): void
    {
        $room = $this->createRoom();
        $borrower = User::factory()->create(['name' => 'Andi Pratama', 'role' => User::ROLE_BORROWER]);
        $start = now()->addDay()->setTime(9, 0);

        // Form menampilkan identitas pemohon sebagai teks baca-saja, tanpa input bebas.
        $this->actingAs($borrower)->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('Andi Pratama')
            ->assertSee(User::ROLE_LABELS[User::ROLE_BORROWER])
            ->assertDontSee('name="requester_name"', false);

        $this->post(route('bookings.store'), [
            'requester_name' => 'Nama Palsu',
            'requester_role' => 'Rektor',
            'room_id' => $room->id,
            'participant_count' => 10,
            'start_time' => $start->format('Y-m-d H:i'),
            'end_time' => $start->copy()->addHours(2)->format('Y-m-d H:i'),
            'purpose' => 'Kerja kelompok',
        ])->assertSessionHasNoErrors()->assertRedirect(route('bookings.show', Booking::first()));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $borrower->id,
            'requester_name' => 'Andi Pratama',
            'requester_role' => User::ROLE_LABELS[User::ROLE_BORROWER],
            'status' => Booking::STATUS_PENDING,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'walkin@campus.local']);
    }

    public function test_pengajuan_booking_dibatasi_10_per_menit(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_BORROWER));

        for ($i = 1; $i <= 10; $i++) {
            $this->post(route('bookings.store'), [])->assertSessionDoesntHaveErrors('throttle');
        }

        $this->post(route('bookings.store'), [])->assertSessionHasErrors('throttle');
    }

    public function test_seeder_akun_demo_membuat_lima_role(): void
    {
        $this->seed();

        foreach (DemoAccountSeeder::ACCOUNTS as $email => [, $role]) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => $role]);
        }

        $this->post(route('login.store'), ['email' => 'pimpinan@campus.test', 'password' => 'password']);
        $this->assertAuthenticated();
    }
}
