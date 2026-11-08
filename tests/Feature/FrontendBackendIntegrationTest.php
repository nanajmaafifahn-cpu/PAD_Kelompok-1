<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use App\Services\RoomService;
use App\Services\ScheduleService;
use App\Services\UtilizationDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class FrontendBackendIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;
    private User $manager;
    private User $borrower;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-09 08:00', 'Asia/Jakarta'));
        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);
        $this->room = Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'LAB-01', 'type' => 'laboratory',
            'name' => 'Laboratorium 1', 'building' => 'TULT', 'capacity' => 20, 'status' => 'available',
        ]);
        $this->manager = User::factory()->create(['role' => User::ROLE_LAB_HEAD]);
        $this->borrower = User::factory()->create(['role' => User::ROLE_BORROWER]);
        $this->actingAs($this->manager);
    }

    private function roomInput(): array
    {
        return [
            'academic_unit_id' => $this->room->academic_unit_id, 'code' => 'LAB-02',
            'type' => 'classroom', 'name' => 'Ruang Diskusi', 'building' => 'TULT',
            'capacity' => 15, 'status' => 'available',
        ];
    }

    private function bookingInput(): array
    {
        return [
            'room_id' => $this->room->id, 'participant_count' => 10,
            'start_time' => '2026-11-10T10:00', 'end_time' => '2026-11-10T12:00',
            'purpose' => 'Diskusi tugas besar',
        ];
    }

    private function scheduleInput(): array
    {
        return [
            'room_id' => $this->room->id, 'title' => 'Praktikum PAD',
            'start_time' => '2026-11-10T10:00', 'end_time' => '2026-11-10T12:00',
        ];
    }

    private function revisitForm(string $url): TestResponse
    {
        // Bawa cookie sesi pada redirect seperti browser, termasuk error bag berserialisasi JSON.
        $this->withCookie(config('session.cookie'), session()->getId());

        return $this->get($url);
    }

    public function test_room_validation_preserves_input_and_renders_indonesian_field_errors(): void
    {
        $input = [...$this->roomInput(), 'capacity' => -1];

        $this->from(route('rooms.create'))->post(route('rooms.store'), $input)
            ->assertRedirect(route('rooms.create'))
            ->assertSessionHasErrors(['capacity' => 'Kapasitas ruang minimal 1 orang.'])
            ->assertSessionHasInput('name', 'Ruang Diskusi');

        $this->revisitForm(route('rooms.create'))->assertOk()
            ->assertSee('value="Ruang Diskusi"', false)
            ->assertSee('value="-1"', false)
            ->assertSee('Kapasitas ruang minimal 1 orang.')
            ->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_room_rejects_invalid_master_data_and_photo_in_indonesian(): void
    {
        $this->postJson(route('rooms.store'), [
            ...$this->roomInput(), 'academic_unit_id' => 99999, 'code' => $this->room->code,
            'type' => 'invalid', 'name' => str_repeat('a', 151), 'building' => '',
            'floor' => str_repeat('a', 31), 'capacity' => 1.5, 'status' => 'occupied',
            'description' => ['invalid'], 'photo' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'academic_unit_id', 'code', 'type', 'name', 'building', 'floor',
            'capacity', 'status', 'description', 'photo',
        ])->assertJsonPath('errors.code.0', 'Kode ruang sudah digunakan.')
            ->assertJsonPath('errors.capacity.0', 'Kapasitas ruang harus berupa bilangan bulat.');

        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_empty_forms_return_specific_required_messages(): void
    {
        $this->postJson(route('rooms.store'), [])->assertUnprocessable()
            ->assertJsonPath('errors.academic_unit_id.0', 'Unit akademik wajib dipilih.')
            ->assertJsonPath('errors.code.0', 'Kode ruang wajib diisi.')
            ->assertJsonPath('errors.type.0', 'Tipe ruang wajib dipilih.')
            ->assertJsonPath('errors.name.0', 'Nama ruang wajib diisi.')
            ->assertJsonPath('errors.building.0', 'Gedung wajib diisi.')
            ->assertJsonPath('errors.capacity.0', 'Kapasitas ruang wajib diisi.')
            ->assertJsonPath('errors.status.0', 'Status ruang wajib dipilih.');

        $this->actingAs($this->borrower)->postJson(route('bookings.store'), [])->assertUnprocessable()
            ->assertJsonPath('errors.room_id.0', 'Ruang wajib dipilih.')
            ->assertJsonPath('errors.participant_count.0', 'Jumlah peserta wajib diisi.')
            ->assertJsonPath('errors.start_time.0', 'Waktu mulai wajib diisi.')
            ->assertJsonPath('errors.end_time.0', 'Waktu selesai wajib diisi.')
            ->assertJsonPath('errors.purpose.0', 'Keperluan peminjaman wajib diisi.');
    }

    public function test_booking_rejects_invalid_values_before_persistence(): void
    {
        $this->actingAs($this->borrower);
        $this->postJson(route('bookings.store'), [
            ...$this->bookingInput(), 'room_id' => 99999, 'participant_count' => 0,
            'start_time' => '2026-11-08T10:00', 'end_time' => '2026-11-08T09:00',
            'purpose' => str_repeat('a', 256),
        ])->assertUnprocessable()
            ->assertJsonPath('errors.room_id.0', 'Ruang yang dipilih tidak ditemukan.')
            ->assertJsonPath('errors.participant_count.0', 'Jumlah peserta minimal 1 orang.')
            ->assertJsonPath('errors.start_time.0', 'Waktu mulai harus setelah saat ini.')
            ->assertJsonPath('errors.end_time.0', 'Waktu selesai harus setelah waktu mulai.')
            ->assertJsonPath('errors.purpose.0', 'Keperluan peminjaman maksimal 255 karakter.');

        $this->postJson(route('bookings.store'), [
            ...$this->bookingInput(), 'start_time' => 'invalid', 'end_time' => 'invalid',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.start_time.0', 'Waktu mulai harus berupa tanggal dan jam yang valid.')
            ->assertJsonPath('errors.end_time.0', 'Waktu selesai harus berupa tanggal dan jam yang valid.');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_domain_error_keeps_input_visible_in_blade(): void
    {
        $this->actingAs($this->borrower);
        $this->from(route('bookings.create'))->post(route('bookings.store'), [
            ...$this->bookingInput(), 'participant_count' => 21,
        ])->assertRedirect(route('bookings.create'))
            ->assertSessionHasErrors(['participant_count' => 'Jumlah peserta (21) melebihi kapasitas ruangan (20).'])
            ->assertSessionHasInput('purpose', 'Diskusi tugas besar');

        $this->revisitForm(route('bookings.create'))->assertOk()
            ->assertSee('Diskusi tugas besar')->assertSee('value="21"', false)
            ->assertSee('Jumlah peserta (21) melebihi kapasitas ruangan (20).');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_schedule_conflict_keeps_input_and_invalid_time_is_localized(): void
    {
        Schedule::create([...$this->scheduleInput(), 'status' => 'scheduled']);
        $this->from(route('schedules.index'))->post(route('schedules.store'), $this->scheduleInput())
            ->assertRedirect(route('schedules.index'))->assertSessionHasErrors('start_time')
            ->assertSessionHasInput('title', 'Praktikum PAD');
        $this->revisitForm(route('schedules.index'))->assertOk()->assertSee('value="Praktikum PAD"', false)
            ->assertSee('Jadwal bentrok dengan jadwal atau peminjaman lain di ruangan yang sama.');

        $this->postJson(route('schedules.store'), [
            ...$this->scheduleInput(), 'end_time' => '2026-11-10T09:00',
        ])->assertUnprocessable()->assertJsonPath('errors.end_time.0', 'Waktu selesai harus setelah waktu mulai.');
        $this->assertDatabaseCount('schedules', 1);
    }

    public static function persistenceFailures(): array
    {
        return [
            'tambah ruang' => ['room', false, RoomService::class, 'create'],
            'ubah ruang' => ['room', true, RoomService::class, 'update'],
            'ajukan peminjaman' => ['booking', false, BookingService::class, 'submitBooking'],
            'ubah peminjaman' => ['booking', true, BookingService::class, 'updateBooking'],
            'tambah jadwal' => ['schedule', false, ScheduleService::class, 'create'],
            'ubah jadwal' => ['schedule', true, ScheduleService::class, 'update'],
        ];
    }

    #[DataProvider('persistenceFailures')]
    public function test_database_failure_returns_to_form_without_losing_input(
        string $entity, bool $update, string $service, string $method
    ): void {
        $secret = 'SQLSTATE detail-database-internal';
        $exception = new QueryException('sqlite', 'insert into internal_table values (?)', [], new RuntimeException($secret));
        $this->partialMock($service, fn ($mock) => $mock->shouldReceive($method)->once()->andThrow($exception));

        if ($entity === 'room') {
            $input = $this->roomInput();
            $params = $update ? [$this->room] : [];
            $target = route($update ? 'rooms.update' : 'rooms.store', $params);
            $form = route($update ? 'rooms.edit' : 'rooms.create', $params);
            $field = 'name';
        } elseif ($entity === 'booking') {
            $this->actingAs($this->borrower);
            $input = $this->bookingInput();
            $booking = $update ? Booking::create([
                ...$input, 'user_id' => $this->borrower->id, 'status' => 'pending',
            ]) : null;
            $params = $update ? [$booking] : [];
            $target = route($update ? 'bookings.update' : 'bookings.store', $params);
            $form = route($update ? 'bookings.edit' : 'bookings.create', $params);
            $field = 'purpose';
        } else {
            $input = $this->scheduleInput();
            $schedule = $update ? Schedule::create([...$input, 'status' => 'scheduled']) : null;
            $target = $update ? route('schedules.update', $schedule) : route('schedules.store');
            $form = $update ? route('schedules.edit', $schedule) : route('schedules.index');
            $field = 'title';
        }

        $this->from($form)->{$update ? 'put' : 'post'}($target, $input)
            ->assertRedirect($form)->assertSessionHas('error')
            ->assertSessionHasInput($field, $input[$field]);
        $this->revisitForm($form)->assertOk()->assertSee('gagal disimpan')
            ->assertSee($input[$field])->assertDontSee($secret)->assertDontSee('internal_table');
        $this->assertDatabaseCount('rooms', 1);
        $this->assertDatabaseCount('bookings', $entity === 'booking' && $update ? 1 : 0);
        $this->assertDatabaseCount('schedules', $entity === 'schedule' && $update ? 1 : 0);
    }

    public function test_report_rejects_partial_and_reversed_periods_and_keeps_dates(): void
    {
        $url = route('dashboard.utilization');
        $this->getJson($url . '?start=2026-11-09')->assertUnprocessable()
            ->assertJsonPath('errors.end.0', 'Tanggal akhir wajib diisi bersama tanggal awal.');
        $this->getJson($url . '?end=2026-11-15')->assertUnprocessable()
            ->assertJsonPath('errors.start.0', 'Tanggal awal wajib diisi bersama tanggal akhir.');
        $this->getJson($url . '?start=invalid&end=invalid')->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end']);
        $this->from($url)->get($url . '?start=2026-11-15&end=2026-11-09')
            ->assertRedirect($url)
            ->assertSessionHasErrors(['end' => 'Tanggal akhir tidak boleh sebelum tanggal awal.'])
            ->assertSessionHasInput('start', '2026-11-15');
        $this->revisitForm($url)->assertOk()->assertSee('value="2026-11-15"', false)
            ->assertSee('value="2026-11-09"', false)
            ->assertSee('Tanggal akhir tidak boleh sebelum tanggal awal.');
    }

    public function test_failed_photo_write_does_not_report_success_or_change_room_data(): void
    {
        $disk = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('putFileAs')->twice()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->twice()->andReturn($disk);

        $this->from(route('rooms.create'))->post(route('rooms.store'), [
            ...$this->roomInput(), 'photo' => UploadedFile::fake()->image('room.jpg'),
        ])->assertRedirect(route('rooms.create'))->assertSessionHas('error')
            ->assertSessionMissing('success')->assertSessionHasInput('name', 'Ruang Diskusi');
        $this->assertDatabaseCount('rooms', 1);

        $this->from(route('rooms.edit', $this->room))->put(route('rooms.update', $this->room), [
            ...$this->roomInput(), 'photo' => UploadedFile::fake()->image('room.jpg'),
        ])->assertRedirect(route('rooms.edit', $this->room))->assertSessionHas('error')
            ->assertSessionMissing('success');
        $this->assertSame('Laboratorium 1', $this->room->fresh()->name);
        $this->assertNull($this->room->fresh()->photo_path);
    }

    public function test_four_controllers_supply_the_blade_contracts_and_success_feedback(): void
    {
        $this->get(route('rooms.index'))->assertOk()
            ->assertViewHasAll(['rooms', 'liveStatuses', 'filters', 'types', 'facilityNames']);
        $this->get(route('rooms.create'))->assertOk()->assertViewHasAll(['academicUnits', 'types']);
        $this->get(route('schedules.index'))->assertOk()
            ->assertViewHasAll(['schedules', 'rooms', 'week', 'eventTypes']);
        $this->get(route('dashboard.utilization'))->assertOk()
            ->assertViewHasAll(['period', 'summary', 'rooms', 'underutilized', 'overutilized']);
        $this->getJson(route('dashboard.utilization'))->assertOk()
            ->assertJsonStructure(['period', 'summary', 'rooms', 'underutilized', 'overutilized']);

        $this->post(route('rooms.store'), $this->roomInput())->assertRedirect(route('rooms.manage'))
            ->assertSessionHas('success', 'Ruangan berhasil ditambahkan.');
        $this->get(route('rooms.manage'))->assertOk()->assertSee('Ruangan berhasil ditambahkan.');
        $this->post(route('schedules.store'), $this->scheduleInput())->assertSessionHas('success');

        $this->actingAs($this->borrower);
        $this->get(route('bookings.create'))->assertOk()->assertViewHasAll(['rooms', 'selectedRoomId']);
        $this->post(route('bookings.store'), [
            ...$this->bookingInput(), 'start_time' => '2026-11-11T10:00', 'end_time' => '2026-11-11T12:00',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->get(route('bookings.mine'))->assertOk()->assertViewHasAll(['bookings', 'scope']);
    }

    public function test_unexpected_report_failure_is_safe_for_html_and_json_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        $this->mock(UtilizationDashboardService::class, fn ($mock) => $mock->shouldReceive('build')
            ->twice()->andThrow(new RuntimeException('detail-internal-rahasia')));

        $this->get(route('dashboard.utilization'))->assertStatus(500)
            ->assertSee('Terjadi kesalahan pada server.')->assertDontSee('detail-internal-rahasia');
        $this->getJson(route('dashboard.utilization'))->assertStatus(500)
            ->assertExactJson(['message' => 'Terjadi kesalahan pada server. Coba lagi beberapa saat lagi.']);
    }
}
