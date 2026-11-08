<?php

namespace Tests\Feature;

use App\Exceptions\BookingException;
use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use App\Strategies\LaboratoryUtilizationStrategy;
use App\Strategies\UtilizationCalculatorContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 3: A5, A6, C1–C5, D2, D3, D8, E4.
 */
class CrudManagementTest extends TestCase
{
    use RefreshDatabase;

    private AcademicUnit $unit;
    private User $labHead;
    private User $borrower;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Senin 9 Nov 2026 08.00 WIB
        $this->travelTo(CarbonImmutable::parse('2026-11-09 08:00', 'Asia/Jakarta'));

        $this->unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);
        $this->labHead = User::factory()->create(['role' => User::ROLE_LAB_HEAD]);
        $this->borrower = User::factory()->create(['role' => User::ROLE_BORROWER]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function room(array $attributes = []): Room
    {
        return Room::create([
            'academic_unit_id' => $this->unit->id,
            'code' => 'TULT-0805',
            'type' => 'laboratory',
            'name' => 'R1',
            'building' => 'TULT',
            'floor' => '8',
            'capacity' => 48,
            'status' => 'available',
            ...$attributes,
        ]);
    }

    private function roomPayload(array $overrides = []): array
    {
        return [
            'academic_unit_id' => $this->unit->id,
            'code' => 'TULT-0807',
            'type' => 'classroom',
            'name' => 'R2',
            'building' => 'TULT',
            'floor' => '8',
            'capacity' => 50,
            'status' => 'available',
            ...$overrides,
        ];
    }

    private function booking(Room $room, string $status, array $extra = []): Booking
    {
        $start = now()->addDay()->setTime(10, 0);

        return Booking::create([
            'room_id' => $room->id,
            'user_id' => $this->borrower->id,
            'purpose' => 'Kerja kelompok',
            'participant_count' => 10,
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(2),
            'status' => $status,
            ...$extra,
        ]);
    }

    private function bookingPayload(Room $room, array $overrides = []): array
    {
        return [
            'room_id' => $room->id,
            'participant_count' => 10,
            'start_time' => '2026-11-10 10:00',
            'end_time' => '2026-11-10 12:00',
            'purpose' => 'Kerja kelompok',
            ...$overrides,
        ];
    }

    // ---------- A5 & A6 ----------

    public function test_form_jadwal_tampil_untuk_pengelola_dengan_daftar_ruang(): void
    {
        $this->room();

        $this->actingAs($this->labHead)->get(route('schedules.index'))
            ->assertOk()
            ->assertSee('Tambah Jadwal')
            ->assertSee('TULT-0805 — R1');

        $this->actingAs($this->borrower)->get(route('schedules.index'))
            ->assertOk()
            ->assertDontSee('Simpan Jadwal');
    }

    public function test_tipe_ruang_tersimpan_dan_memilih_strategy_yang_benar(): void
    {
        $this->actingAs($this->labHead)
            ->post(route('rooms.store'), $this->roomPayload(['type' => 'laboratory']))
            ->assertSessionHasNoErrors();

        $room = Room::where('code', 'TULT-0807')->firstOrFail();
        $this->assertSame('laboratory', $room->type);
        $this->assertSame('Computer Lab', $room->typeLabel());
        $this->assertInstanceOf(
            LaboratoryUtilizationStrategy::class,
            app(UtilizationCalculatorContext::class)->strategyFor($room)
        );

        $this->post(route('rooms.store'), $this->roomPayload(['code' => 'X1', 'type' => 'auditorium']))
            ->assertSessionHasErrors('type');
    }

    // ---------- C1 ----------

    public function test_ubah_ruang_mengizinkan_kode_sendiri_dan_menolak_kode_ruang_lain(): void
    {
        $room = $this->room();
        $this->room(['code' => 'TULT-0819', 'name' => 'R3']);
        $this->actingAs($this->labHead);

        $this->get(route('rooms.edit', $room))->assertOk()->assertSee('Simpan Perubahan');

        $this->put(route('rooms.update', $room), $this->roomPayload(['code' => 'TULT-0805', 'name' => 'R1 Baru']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('rooms.manage'));
        $this->assertSame('R1 Baru', $room->fresh()->name);

        $this->put(route('rooms.update', $room), $this->roomPayload(['code' => 'TULT-0819']))
            ->assertSessionHasErrors('code');
    }

    public function test_unggah_dan_ganti_foto_ruang(): void
    {
        Storage::fake('public');
        $this->actingAs($this->labHead);

        $this->post(route('rooms.store'), $this->roomPayload([
            'photo' => UploadedFile::fake()->image('ruang.jpg', 800, 600)->size(500),
        ]))->assertSessionHasNoErrors();

        $room = Room::where('code', 'TULT-0807')->firstOrFail();
        $oldPath = $room->photo_path;
        Storage::disk('public')->assertExists($oldPath);

        $this->put(route('rooms.update', $room), $this->roomPayload([
            'photo' => UploadedFile::fake()->image('baru.webp')->size(300),
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($room->fresh()->photo_path);

        $this->put(route('rooms.update', $room), $this->roomPayload([
            'photo' => UploadedFile::fake()->image('besar.png')->size(3000),
        ]))->assertSessionHasErrors('photo');

        $this->put(route('rooms.update', $room), $this->roomPayload([
            'photo' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');
    }

    public function test_hapus_ruang_ditolak_bila_masih_punya_booking(): void
    {
        $room = $this->room();
        $this->booking($room, Booking::STATUS_APPROVED);
        $this->actingAs($this->labHead);

        $this->from(route('rooms.manage'))->delete(route('rooms.destroy', $room))
            ->assertRedirect(route('rooms.manage'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, '1 booking aktif'));
        $this->assertModelExists($room);

        Booking::query()->update(['status' => Booking::STATUS_COMPLETED]);
        $this->delete(route('rooms.destroy', $room))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'riwayat booking'));
        $this->assertModelExists($room);

        $empty = $this->room(['code' => 'TULT-0906', 'name' => 'R5']);
        $this->delete(route('rooms.destroy', $empty))->assertRedirect(route('rooms.manage'));
        $this->assertModelMissing($empty);

        $this->actingAs($this->borrower)->delete(route('rooms.destroy', $room))->assertForbidden();
    }

    // ---------- C2 ----------

    public function test_pemilik_bisa_ubah_booking_pending_dan_user_lain_ditolak(): void
    {
        $room = $this->room();
        $booking = $this->booking($room, Booking::STATUS_PENDING);

        $this->actingAs($this->borrower)->get(route('bookings.edit', $booking))->assertOk()->assertSee('Simpan');
        $this->put(route('bookings.update', $booking), $this->bookingPayload($room, ['purpose' => 'Revisi keperluan']))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('bookings.show', $booking));
        $this->assertSame('Revisi keperluan', $booking->fresh()->purpose);

        $this->actingAs(User::factory()->create())
            ->put(route('bookings.update', $booking), $this->bookingPayload($room))
            ->assertForbidden();

        $booking->update(['status' => Booking::STATUS_APPROVED]);
        $this->actingAs($this->borrower)
            ->put(route('bookings.update', $booking), $this->bookingPayload($room, ['purpose' => 'Tidak boleh']))
            ->assertSessionHasErrors('booking');
        $this->assertSame('Revisi keperluan', $booking->fresh()->purpose);
    }

    public function test_pemilik_bisa_membatalkan_sebelum_mulai(): void
    {
        $room = $this->room();
        $approved = $this->booking($room, Booking::STATUS_APPROVED);

        $this->actingAs($this->borrower)->patch(route('bookings.cancel', $approved))
            ->assertRedirect(route('bookings.show', $approved));
        $this->assertSame(Booking::STATUS_CANCELLED, $approved->fresh()->status);

        $started = $this->booking($room, Booking::STATUS_PENDING, [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $this->patch(route('bookings.cancel', $started))->assertSessionHas('error');
        $this->assertSame(Booking::STATUS_PENDING, $started->fresh()->status);

        $this->actingAs($this->labHead)->patch(route('bookings.cancel', $started))->assertForbidden();
    }

    public function test_pengelola_menghapus_booking_yang_tidak_dipakai_laporan(): void
    {
        $room = $this->room();
        $pending = $this->booking($room, Booking::STATUS_PENDING);
        $completed = $this->booking($room, Booking::STATUS_COMPLETED, [
            'start_time' => now()->subDay(),
            'end_time' => now()->subDay()->addHour(),
        ]);

        $this->actingAs($this->borrower)->delete(route('bookings.destroy', $pending))->assertForbidden();

        $this->actingAs($this->labHead)->delete(route('bookings.destroy', $pending))
            ->assertRedirect(route('bookings.index'));
        $this->assertModelMissing($pending);

        $this->delete(route('bookings.destroy', $completed))->assertSessionHas('error');
        $this->assertModelExists($completed);
    }

    // ---------- C3 & D2 ----------

    public function test_ubah_batal_dan_hapus_jadwal(): void
    {
        $room = $this->room();
        $schedule = Schedule::create([
            'room_id' => $room->id, 'title' => 'Basis Data',
            'start_time' => '2026-11-10 07:30', 'end_time' => '2026-11-10 10:00', 'status' => 'scheduled',
        ]);
        $this->actingAs($this->labHead);

        $this->put(route('schedules.update', $schedule), [
            'room_id' => $room->id, 'title' => 'Basis Data (ganti jam)',
            'start_time' => '2026-11-10 08:00', 'end_time' => '2026-11-10 10:30',
        ])->assertSessionHasNoErrors()->assertRedirect(route('schedules.index'));
        $this->assertSame('Basis Data (ganti jam)', $schedule->fresh()->title);

        $this->patch(route('schedules.cancel', $schedule))->assertRedirect(route('schedules.index'));
        $this->assertSame('cancelled', $schedule->fresh()->status);

        $this->delete(route('schedules.destroy', $schedule))->assertRedirect(route('schedules.index'));
        $this->assertModelMissing($schedule);

        $this->actingAs($this->borrower)->get(route('schedules.edit', Schedule::create([
            'room_id' => $room->id, 'title' => 'X', 'start_time' => '2026-11-11 08:00', 'end_time' => '2026-11-11 09:00', 'status' => 'scheduled',
        ])))->assertForbidden();
    }

    public function test_jadwal_bentrok_dengan_jadwal_lain_atau_booking_approved_ditolak(): void
    {
        $room = $this->room();
        $this->actingAs($this->labHead);
        $payload = ['room_id' => $room->id, 'title' => 'Statistika', 'start_time' => '2026-11-10 10:00', 'end_time' => '2026-11-10 12:00'];

        $this->post(route('schedules.store'), $payload)->assertSessionHasNoErrors();
        $existing = Schedule::firstOrFail();

        $this->post(route('schedules.store'), [...$payload, 'start_time' => '2026-11-10 11:00', 'end_time' => '2026-11-10 13:00'])
            ->assertSessionHasErrors('start_time');

        // Mengubah jadwal tanpa menggeser jam tidak dianggap bentrok dengan dirinya sendiri.
        $this->put(route('schedules.update', $existing), [...$payload, 'title' => 'Statistika Industri'])
            ->assertSessionHasNoErrors();

        // Booking approved di jam lain hari itu.
        $this->booking($room, Booking::STATUS_APPROVED, [
            'start_time' => '2026-11-10 14:00', 'end_time' => '2026-11-10 16:00',
        ]);
        $this->post(route('schedules.store'), [...$payload, 'start_time' => '2026-11-10 15:00', 'end_time' => '2026-11-10 17:00'])
            ->assertSessionHasErrors('start_time');

        // Jadwal yang dibatalkan tidak menghalangi.
        $existing->update(['status' => 'cancelled']);
        $this->post(route('schedules.store'), [...$payload, 'title' => 'Pengganti'])->assertSessionHasNoErrors();

        $this->assertSame(2, Schedule::count());
    }

    // ---------- C4 ----------

    public function test_kelola_fasilitas_per_ruang(): void
    {
        $room = $this->room();
        $this->actingAs($this->labHead);

        $this->get(route('rooms.facilities.index', $room))->assertOk()->assertSee('Tambah fasilitas');

        $this->post(route('rooms.facilities.store', $room), ['name' => 'PC Core i5', 'quantity' => 40, 'status' => 'available'])
            ->assertRedirect(route('rooms.facilities.index', $room));
        $facility = Facility::firstOrFail();

        $this->post(route('rooms.facilities.store', $room), ['name' => 'PC Core i5', 'quantity' => 1, 'status' => 'available'])
            ->assertSessionHasErrors('name');

        $this->put(route('facilities.update', $facility), ['name' => 'PC Core i5', 'quantity' => 38, 'status' => 'damaged'])
            ->assertSessionHasNoErrors();
        $this->assertSame(38, $facility->fresh()->getQuantity());
        $this->assertSame('damaged', $facility->fresh()->getStatus());

        $this->delete(route('facilities.destroy', $facility))->assertRedirect(route('rooms.facilities.index', $room));
        $this->assertModelMissing($facility);

        $this->actingAs($this->borrower)->get(route('rooms.facilities.index', $room))->assertForbidden();
    }

    // ---------- C5 ----------

    public function test_admin_kelola_user_ubah_role_dan_nonaktifkan(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.users.store'), [
            'name' => 'Kalab Baru', 'email' => 'kalab@campus.test', 'role' => User::ROLE_LAB_HEAD,
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.users.index'));
        $user = User::where('email', 'kalab@campus.test')->firstOrFail();
        $this->assertSame(User::ROLE_LAB_HEAD, $user->role);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Kalab Baru', 'email' => 'kalab@campus.test', 'role' => User::ROLE_LEADER, 'is_active' => '0',
        ])->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame(User::ROLE_LEADER, $user->role);
        $this->assertFalse($user->isActive());

        // Akun nonaktif tidak bisa login walau password benar.
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'kalab@campus.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_tidak_bisa_menurunkan_role_atau_menonaktifkan_diri_sendiri(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role' => User::ROLE_BORROWER, 'is_active' => '0',
        ])->assertSessionHasErrors(['role', 'is_active']);

        $this->assertSame(User::ROLE_ADMIN, $this->admin->fresh()->role);
        $this->assertTrue($this->admin->fresh()->isActive());
    }

    public function test_sesi_user_yang_dinonaktifkan_langsung_dikeluarkan(): void
    {
        $this->actingAs($this->borrower)->get(route('bookings.mine'))->assertOk();

        $this->borrower->update(['is_active' => false]);

        $this->get(route('bookings.mine'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_kelola_unit_akademik_dan_role_lain_ditolak(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.units.store'), ['code' => 'FIF', 'name' => 'Fakultas Informatika'])
            ->assertRedirect(route('admin.units.index'));
        $fif = AcademicUnit::where('code', 'FIF')->firstOrFail();

        $this->put(route('admin.units.update', $fif), ['code' => 'FIF', 'name' => 'Fakultas Informatika (baru)'])
            ->assertSessionHasNoErrors();

        $this->delete(route('admin.units.destroy', $fif))->assertRedirect(route('admin.units.index'));
        $this->assertModelMissing($fif);

        $this->room();
        $this->delete(route('admin.units.destroy', $this->unit))->assertSessionHas('error');
        $this->assertModelExists($this->unit);

        foreach ([$this->labHead, $this->borrower] as $user) {
            $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.units.index'))->assertForbidden();
        }
    }

    // ---------- D3 & D8 ----------

    public function test_booking_waktu_lampau_ditolak_di_request_dan_service(): void
    {
        $room = $this->room();

        $this->actingAs($this->borrower)
            ->post(route('bookings.store'), $this->bookingPayload($room, [
                'start_time' => '2026-11-09 07:00', 'end_time' => '2026-11-09 09:00',
            ]))
            ->assertSessionHasErrors('start_time');

        $this->expectException(BookingException::class);
        app(BookingService::class)->submitBooking($this->borrower, $this->bookingPayload($room, [
            'start_time' => '2026-11-09 07:00', 'end_time' => '2026-11-09 09:00',
        ]));
    }

    public function test_galat_kapasitas_dan_bentrok_tampil_di_field_yang_tepat(): void
    {
        $room = $this->room(['capacity' => 20]);
        $this->actingAs($this->borrower);

        $this->post(route('bookings.store'), $this->bookingPayload($room, ['participant_count' => 30]))
            ->assertSessionHasErrors(['participant_count' => 'Jumlah peserta (30) melebihi kapasitas ruangan (20).']);

        $this->booking($room, Booking::STATUS_APPROVED, [
            'start_time' => '2026-11-10 10:00', 'end_time' => '2026-11-10 12:00',
        ]);
        $this->post(route('bookings.store'), $this->bookingPayload($room))
            ->assertSessionHasErrors('start_time');

        $room->update(['status' => 'maintenance']);
        $this->post(route('bookings.store'), $this->bookingPayload($room, ['start_time' => '2026-11-11 10:00', 'end_time' => '2026-11-11 11:00']))
            ->assertSessionHasErrors('room_id');
    }

    // ---------- E4 ----------

    public function test_filter_ruang_bertahan_saat_paginasi(): void
    {
        $lab = $this->room(['code' => 'TULT-0805', 'name' => 'R1', 'type' => 'laboratory', 'capacity' => 48]);
        $this->room(['code' => 'TULT-0912', 'name' => 'R6', 'type' => 'classroom', 'capacity' => 50]);
        $lab->facilities()->create(['name' => 'Proyektor', 'quantity' => 1, 'status' => 'available']);

        foreach (range(1, 12) as $i) {
            $this->room(['code' => "LAB-{$i}", 'name' => "Lab Tambahan {$i}", 'type' => 'laboratory', 'capacity' => 10]);
        }

        $this->actingAs($this->borrower);
        // Cari kartu ruang (placeholder input pencarian juga memuat "TULT-0805").
        $cell = fn (string $code) => 'data-room-code="' . $code . '"';

        $this->get(route('rooms.index', ['q' => 'TULT-0912']))
            ->assertSee($cell('TULT-0912'), false)->assertDontSee($cell('TULT-0805'), false);
        $this->get(route('rooms.index', ['type' => 'classroom']))
            ->assertSee($cell('TULT-0912'), false)->assertDontSee($cell('TULT-0805'), false);
        $this->get(route('rooms.index', ['min_capacity' => 49]))
            ->assertSee($cell('TULT-0912'), false)->assertDontSee($cell('TULT-0805'), false);
        $this->get(route('rooms.index', ['facility' => 'Proyektor']))
            ->assertSee($cell('TULT-0805'), false)->assertDontSee($cell('TULT-0912'), false);

        // 13 lab -> 2 halaman (12 per halaman); link halaman 2 tetap membawa filter.
        $this->get(route('rooms.index', ['type' => 'laboratory']))
            ->assertOk()
            ->assertSee('type=laboratory&amp;page=2', false);

        $this->get(route('rooms.index', ['type' => 'auditorium']))->assertSessionHasErrors('type');
    }
}
