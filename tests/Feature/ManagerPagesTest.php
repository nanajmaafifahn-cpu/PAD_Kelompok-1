<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PROMPT 2 Tahap 4: halaman Pengelola, Admin, Pimpinan dengan data demo seeder. */
class ManagerPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Jakarta'));
        $this->seed();
    }

    private function login(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    public function test_semua_halaman_pengelola_dan_admin_terbuka_dengan_header_baru(): void
    {
        $this->login('admin@campus.test');
        $room = Room::where('code', 'TULT-0805')->firstOrFail();

        foreach ([
            route('dashboard') => 'Dashboard admin akademik',
            route('bookings.index') => 'Kelola peminjaman.',
            route('dashboard.utilization') => 'Bagaimana ruang digunakan?',
            route('rooms.manage') => 'Ruangan &amp; Foto.',
            route('rooms.create') => 'Tambah ruang.',
            route('rooms.edit', $room) => 'Ubah ruang R1.',
            route('rooms.facilities.index', $room) => 'Fasilitas R1.',
            route('rooms.qr.index') => 'QR ruangan.',
            route('admin.users.index') => 'Pengguna.',
            route('admin.users.create') => 'Tambah pengguna.',
            route('admin.units.index') => 'Unit akademik.',
        ] as $url => $title) {
            $this->get($url)->assertOk()->assertSee($title, false)->assertSee('cs-breadcrumb', false);
        }
    }

    public function test_laporan_berisi_stat_card_grafik_per_ruang_dan_tanpa_unduh_csv(): void
    {
        $this->login('pengelola@campus.test');

        $html = $this->get(route('dashboard.utilization'))
            ->assertSee('Jam terjadwal')
            ->assertSee('Sesi belum lengkap')
            ->assertSee('Terjadwal vs aktual')
            ->assertSee('data-print', false)
            ->assertDontSee('Unduh CSV')
            ->getContent();

        $this->assertSame(Room::count(), substr_count($html, 'class="cs-chart-row"'));
        $this->assertStringContainsString('data-series="scheduled"', $html);
        $this->assertStringContainsString('data-series="actual"', $html);
    }

    public function test_cetak_qr_satu_kartu_per_ruang_dengan_perbarui_qr(): void
    {
        $this->login('pengelola@campus.test');

        $html = $this->get(route('rooms.qr.index'))->assertSee('Campus Space · FRI')->getContent();

        $this->assertSame(Room::count(), substr_count($html, 'class="cs-qr-card"'));
        $this->assertSame(Room::count(), substr_count($html, 'Pindai untuk check-in / check-out'));
        $this->assertStringContainsString(route('rooms.qr.regenerate', Room::first()), $html);
    }

    public function test_form_ruang_berstatus_indonesia_dengan_pratinjau_foto(): void
    {
        $this->login('pengelola@campus.test');

        $this->get(route('rooms.create'))
            ->assertSee('>Tersedia</option>', false)
            ->assertSee('>Perawatan</option>', false)
            ->assertDontSee('>Available<', false)
            ->assertSee('data-photo-preview', false)
            ->assertSee('data-photo-input', false);
    }

    public function test_pimpinan_hanya_melihat_dashboard_dan_laporan(): void
    {
        $this->login('pimpinan@campus.test');

        $html = $this->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString(route('dashboard.utilization'), $html);
        $this->assertStringNotContainsString('href="' . route('rooms.index') . '"', $html);
        $this->assertStringNotContainsString('href="' . route('rooms.manage') . '"', $html);
        $this->assertStringNotContainsString('Pengelolaan', $html);
        $this->assertStringNotContainsString('data-dialog-open="reject-', $html);

        $this->get(route('dashboard.utilization'))->assertOk();
        $this->get(route('rooms.manage'))->assertForbidden();
    }
}
