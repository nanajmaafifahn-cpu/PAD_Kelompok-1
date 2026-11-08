<?php

namespace Tests\Feature;

use App\Models\AcademicUnit;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** PROMPT 2 Tahap 2: komponen bersama (badge, stat card, empty state, room card, dialog, filter, paginasi). */
class UiComponentsTest extends TestCase
{
    use RefreshDatabase;

    private function room(array $attributes = []): Room
    {
        $unit = AcademicUnit::firstOrCreate(['code' => 'FRI'], ['name' => 'FRI']);

        return Room::create([
            'academic_unit_id' => $unit->id, 'code' => 'TULT-0805', 'type' => 'laboratory',
            'name' => 'R1', 'building' => 'TULT', 'capacity' => 48, 'status' => 'available', ...$attributes,
        ]);
    }

    public function test_status_badge_berbahasa_indonesia_per_domain(): void
    {
        $badge = fn (string $status, string $domain = '') => trim(strip_tags(Blade::render(
            $domain ? '<x-status-badge :status="$s" :domain="$d" />' : '<x-status-badge :status="$s" />',
            ['s' => $status, 'd' => $domain]
        )));

        $this->assertSame('Menunggu persetujuan', $badge('pending'));
        $this->assertSame('Tidak hadir', $badge('no_show'));
        $this->assertSame('Check-in', $badge('checked_in'));
        $this->assertSame('Tersedia', $badge('available'));
        $this->assertSame('Sedang digunakan', $badge('occupied'));
        $this->assertSame('Baik', $badge('available', 'facility'));
        $this->assertSame('Rusak', $badge('damaged', 'facility'));
        $this->assertSame('Terjadwal', $badge('scheduled'));
        $this->assertSame('Kurang dimanfaatkan', $badge('underutilized'));
        $this->assertSame('Nonaktif', $badge('inactive'));
        $this->assertStringContainsString('data-status="no_show"', Blade::render('<x-status-badge status="no_show" />'));
    }

    public function test_stat_card_dan_empty_state(): void
    {
        $html = Blade::render('<x-stat-card label="Menunggu persetujuan" :value="3" caption="Dalam pemeriksaan" icon="clock" tone="pending" />');
        $this->assertStringContainsString('Menunggu persetujuan', $html);
        $this->assertStringContainsString('>3<', $html);
        $this->assertStringContainsString('data-status="pending"', $html);

        $empty = Blade::render('<x-empty-state title="Belum ada peminjaman" text="Ajukan sekarang."><a href="#">Ajukan Peminjaman</a></x-empty-state>');
        $this->assertStringContainsString('Belum ada peminjaman', $empty);
        $this->assertStringContainsString('Ajukan Peminjaman', $empty);
    }

    public function test_room_card_tanpa_foto_dan_nonkomputer(): void
    {
        $lab = $this->room();
        $lab->facilities()->create(['name' => 'PC Komputer', 'quantity' => 48, 'status' => 'available']);
        $class = $this->room(['code' => 'TULT-0819', 'name' => 'R3', 'type' => 'classroom']);

        $labHtml = Blade::render('<x-room-card :room="$r" live-status="occupied" />', ['r' => $lab->load('facilities')]);
        $this->assertStringContainsString('Computer Lab', $labHtml);
        $this->assertStringContainsString('Foto belum ditambahkan', $labHtml);
        $this->assertStringContainsString('48 komputer', $labHtml);
        $this->assertStringContainsString('Sedang digunakan', $labHtml);
        $this->assertStringContainsString(route('rooms.show', $lab), $labHtml);

        $classHtml = Blade::render('<x-room-card :room="$r" />', ['r' => $class->load('facilities')]);
        $this->assertStringContainsString('Nonkomputer', $classHtml);
        $this->assertStringContainsString('Tersedia sekarang', $classHtml);
    }

    public function test_layout_memasang_dialog_konfirmasi_dan_tolak_memakai_dialog_beralasan(): void
    {
        $labHead = User::factory()->create(['role' => User::ROLE_LAB_HEAD]);
        $room = $this->room();
        $booking = Booking::create([
            'room_id' => $room->id, 'user_id' => User::factory()->create()->id, 'purpose' => 'Kerja kelompok',
            'participant_count' => 5, 'start_time' => now()->addDay(), 'end_time' => now()->addDay()->addHour(),
            'status' => Booking::STATUS_PENDING,
        ]);

        $html = $this->actingAs($labHead)->get(route('bookings.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<dialog id="cs-confirm-dialog"', $html);
        $this->assertStringContainsString('<dialog id="reject-' . $booking->id . '"', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]*name="rejection_reason"[^>]*required/', $html);
        $this->assertMatchesRegularExpression('/<form[^>]*' . preg_quote(route('bookings.approve', $booking), '/') . '"[^>]*data-confirm=/s', $html);
        $this->assertStringNotContainsString('window.confirm(', $html);
    }

    public function test_paginasi_menampilkan_info_dan_membawa_filter(): void
    {
        foreach (range(1, 14) as $i) {
            $this->room(['code' => "LAB-{$i}", 'name' => "Lab {$i}"]);
        }

        $html = $this->actingAs(User::factory()->create())
            ->get(route('rooms.index', ['type' => 'laboratory']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Menampilkan 1–12 dari 14', $html);
        $this->assertStringContainsString('Berikutnya', $html);
        $this->assertStringContainsString('aria-current="page">1<', $html);
        $this->assertStringContainsString('type=laboratory&amp;page=2', $html);
        $this->assertStringContainsString('placeholder="Jumlah"', $html);
    }
}
