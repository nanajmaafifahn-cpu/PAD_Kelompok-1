<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2: approve/reject per role, QR per ruangan (scan -> check-in/out milik sendiri),
 * kode cadangan, detail booking, "Peminjaman Saya", Cetak QR, status ruang live.
 */
class BookingQrFlowTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;
    private User $owner;
    private CarbonImmutable $start;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = AcademicUnit::create(['code' => 'FRI', 'name' => 'Fakultas Rekayasa Industri']);
        $this->room = Room::create([
            'academic_unit_id' => $unit->id,
            'code' => 'TULT-0805',
            'name' => 'R1',
            'building' => 'TULT',
            'floor' => '8',
            'capacity' => 48,
            'status' => 'available',
        ]);
        $this->owner = User::factory()->create(['role' => User::ROLE_BORROWER]);

        // Senin 9 Nov 2026, booking pukul 14.00–16.00 WIB.
        $this->start = CarbonImmutable::parse('2026-11-09 14:00', 'Asia/Jakarta');
    }

    private function booking(string $status, ?User $owner = null, array $extra = []): Booking
    {
        return Booking::create([
            'room_id' => $this->room->id,
            'user_id' => ($owner ?? $this->owner)->id,
            'requester_name' => ($owner ?? $this->owner)->name,
            'purpose' => 'Praktikum Basis Data',
            'participant_count' => 30,
            'start_time' => $this->start,
            'end_time' => $this->start->addHours(2),
            'status' => $status,
            'qr_token' => in_array($status, [Booking::STATUS_APPROVED, Booking::STATUS_CHECKED_IN], true)
                ? 'KODECADANGAN' . $status
                : null,
            ...$extra,
        ]);
    }

    private function at(string $time): void
    {
        $this->travelTo(CarbonImmutable::parse("2026-11-09 {$time}", 'Asia/Jakarta'));
    }

    // ---------------- Approve / Reject ----------------

    public function test_pengelola_dan_admin_bisa_approve_reject_role_lain_403(): void
    {
        $this->at('09:00');
        $pending = $this->booking(Booking::STATUS_PENDING);

        foreach ([User::ROLE_BORROWER, User::ROLE_LEADER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->post(route('bookings.approve', $pending))
                ->assertForbidden();
        }
        $this->assertSame(Booking::STATUS_PENDING, $pending->fresh()->status);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]))
            ->post(route('bookings.approve', $pending))
            ->assertRedirect(route('bookings.index'));
        $this->assertSame(Booking::STATUS_APPROVED, $pending->fresh()->status);
        $this->assertNotNull($pending->fresh()->qr_token);

        $other = $this->booking(Booking::STATUS_PENDING, null, [
            'start_time' => $this->start->addDay(),
            'end_time' => $this->start->addDay()->addHour(),
        ]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))
            ->post(route('bookings.reject', $other), ['rejection_reason' => 'Ruang dipakai ujian.'])
            ->assertRedirect(route('bookings.index'));
        $this->assertDatabaseHas('bookings', [
            'id' => $other->id,
            'status' => Booking::STATUS_REJECTED,
            'rejection_reason' => 'Ruang dipakai ujian.',
        ]);
    }

    // ---------------- QR ruangan ----------------

    public function test_ruang_baru_otomatis_punya_qr_token_dan_bisa_diperbarui(): void
    {
        $oldToken = $this->room->qr_token;
        $this->assertNotEmpty($oldToken);

        $labHead = User::factory()->create(['role' => User::ROLE_LAB_HEAD]);

        $this->actingAs($labHead)->get(route('rooms.qr.index'))
            ->assertOk()
            ->assertSee(route('scan.room', $this->room, false));

        $this->actingAs($labHead)->post(route('rooms.qr.regenerate', $this->room))
            ->assertRedirect(route('rooms.qr.index'));

        $this->assertNotSame($oldToken, $this->room->fresh()->qr_token);

        // QR lama tidak berlaku lagi.
        $this->actingAs($this->owner)->get('/scan/' . $oldToken)->assertNotFound();
    }

    public function test_borrower_tidak_bisa_membuka_cetak_qr_atau_memperbarui_qr(): void
    {
        $this->actingAs($this->owner);

        $this->get(route('rooms.qr.index'))->assertForbidden();
        $this->post(route('rooms.qr.regenerate', $this->room))->assertForbidden();
    }

    public function test_pemilik_bisa_check_in_dalam_jendela_waktu(): void
    {
        $booking = $this->booking(Booking::STATUS_APPROVED);
        $this->at('13:50');

        $this->actingAs($this->owner)->get(route('scan.room', $this->room))
            ->assertOk()
            ->assertSee('Konfirmasi Check-In');

        $this->post(route('scan.confirm', $this->room))
            ->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_CHECKED_IN, $booking->status);
        $this->assertNotNull($booking->checked_in_at);
        $this->assertSame('occupied', $this->room->fresh()->getLiveStatus());
    }

    public function test_user_lain_ditolak_saat_scan_ruangan_yang_sama(): void
    {
        $booking = $this->booking(Booking::STATUS_APPROVED);
        $intruder = User::factory()->create(['role' => User::ROLE_BORROWER]);
        $this->at('13:50');

        $this->actingAs($intruder)->get(route('scan.room', $this->room))
            ->assertOk()
            ->assertSee('Tidak ada peminjaman Anda yang disetujui untuk ruang R1 hari ini.')
            ->assertDontSee('Konfirmasi Check-In');

        $this->post(route('scan.confirm', $this->room))
            ->assertRedirect(route('scan.room', $this->room))
            ->assertSessionHas('error');

        $this->assertSame(Booking::STATUS_APPROVED, $booking->fresh()->status);

        // Jalur kode cadangan lama juga hanya untuk pemilik.
        $this->post(route('bookings.checkin', $booking), ['qr_token' => $booking->qr_token])
            ->assertForbidden();
    }

    public function test_check_in_sebelum_jendela_ditolak_dengan_jam_buka(): void
    {
        $booking = $this->booking(Booking::STATUS_APPROVED);
        $this->at('13:30');

        $this->actingAs($this->owner)->get(route('scan.room', $this->room))
            ->assertSee('Belum waktunya check-in, mulai 13.45 WIB.');

        $this->post(route('scan.confirm', $this->room))
            ->assertSessionHas('error', 'Belum waktunya check-in, mulai 13.45 WIB.');

        $this->assertSame(Booking::STATUS_APPROVED, $booking->fresh()->status);
    }

    public function test_check_in_setelah_toleransi_ditolak(): void
    {
        $this->booking(Booking::STATUS_APPROVED);
        $this->at('14:20');

        $this->actingAs($this->owner)->get(route('scan.room', $this->room))
            ->assertSee('Batas check-in pukul 14.15 WIB sudah lewat');
    }

    public function test_booking_pending_diberi_alasan_menunggu_persetujuan(): void
    {
        $this->booking(Booking::STATUS_PENDING);
        $this->at('13:50');

        $this->actingAs($this->owner)->get(route('scan.room', $this->room))
            ->assertSee('masih menunggu persetujuan pengelola');
    }

    public function test_check_out_mengubah_status_menjadi_completed(): void
    {
        $booking = $this->booking(Booking::STATUS_CHECKED_IN, null, [
            'checked_in_at' => $this->start->subMinutes(5),
        ]);
        $this->at('15:30');

        $this->actingAs($this->owner)->get(route('scan.room', $this->room))
            ->assertSee('Konfirmasi Check-Out');

        $this->post(route('scan.confirm', $this->room))
            ->assertRedirect(route('bookings.show', $booking));

        $booking->refresh();
        $this->assertSame(Booking::STATUS_COMPLETED, $booking->status);
        $this->assertNotNull($booking->checked_out_at);
        $this->assertSame('available', $this->room->fresh()->getLiveStatus());
    }

    public function test_token_ruang_tidak_dikenal_404_dan_pimpinan_tidak_bisa_scan(): void
    {
        $this->actingAs($this->owner)->get('/scan/TOKENTIDAKADA')->assertNotFound();

        $leader = User::factory()->create(['role' => User::ROLE_LEADER]);
        $this->actingAs($leader)->get(route('scan.index'))->assertForbidden();
        $this->actingAs($leader)->get(route('scan.room', $this->room))->assertForbidden();
    }

    // ---------------- Input manual ----------------

    public function test_input_manual_menerima_url_qr_dan_kode_cadangan_milik_sendiri(): void
    {
        $booking = $this->booking(Booking::STATUS_APPROVED);
        $this->actingAs($this->owner);

        $this->get(route('scan.index'))->assertOk()->assertSee('data-qr-scanner', false);

        $this->post(route('scan.manual'), ['code' => 'http://192.168.1.10:8000/scan/' . $this->room->qr_token])
            ->assertRedirect(route('scan.room', $this->room));

        $this->post(route('scan.manual'), ['code' => '  ' . $booking->qr_token . ' '])
            ->assertRedirect(route('scan.room', $this->room));

        $this->post(route('scan.manual'), ['code' => 'asal-asalan'])
            ->assertSessionHasErrors('code');

        // Kode cadangan milik orang lain tidak dikenali.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_BORROWER]))
            ->post(route('scan.manual'), ['code' => $booking->qr_token])
            ->assertSessionHasErrors('code');
    }

    // ---------------- Detail & Peminjaman Saya ----------------

    public function test_detail_booking_kode_cadangan_hanya_untuk_pemilik(): void
    {
        $approved = $this->booking(Booking::STATUS_APPROVED);

        $this->actingAs($this->owner)->get(route('bookings.show', $approved))
            ->assertOk()
            ->assertSee($approved->qr_token);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]))
            ->get(route('bookings.show', $approved))
            ->assertOk()
            ->assertDontSee($approved->qr_token);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_BORROWER]))
            ->get(route('bookings.show', $approved))
            ->assertForbidden();

        $pending = $this->booking(Booking::STATUS_PENDING, null, [
            'start_time' => $this->start->addDay(),
            'end_time' => $this->start->addDay()->addHour(),
            'qr_token' => 'TIDAKBOLEHTAMPIL',
        ]);
        $this->actingAs($this->owner)->get(route('bookings.show', $pending))
            ->assertOk()
            ->assertDontSee('TIDAKBOLEHTAMPIL');
    }

    public function test_peminjaman_saya_hanya_berisi_milik_sendiri(): void
    {
        $this->booking(Booking::STATUS_PENDING, null, ['purpose' => 'Punya saya']);
        $this->booking(Booking::STATUS_PENDING, User::factory()->create(), [
            'purpose' => 'Punya orang lain',
            'start_time' => $this->start->addDay(),
            'end_time' => $this->start->addDay()->addHour(),
        ]);

        $this->actingAs($this->owner)->get(route('bookings.mine'))
            ->assertOk()
            ->assertSee('Peminjaman Saya')
            ->assertSee('Punya saya')
            ->assertDontSee('Punya orang lain');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]))
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Punya saya')
            ->assertSee('Punya orang lain');
    }

    // ---------------- Status ruang ----------------

    public function test_status_occupied_tidak_bisa_diisi_manual(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_LAB_HEAD]))
            ->post(route('rooms.store'), [
                'academic_unit_id' => $this->room->academic_unit_id,
                'code' => 'TULT-0807',
                'name' => 'R2',
                'building' => 'TULT',
                'capacity' => 50,
                'status' => 'occupied',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('rooms', ['code' => 'TULT-0807']);
    }

    public function test_status_live_booked_saat_approved_belum_check_in(): void
    {
        $this->booking(Booking::STATUS_APPROVED);
        $this->at('13:00');

        $this->assertSame('booked', $this->room->fresh()->getLiveStatus());
    }
}
