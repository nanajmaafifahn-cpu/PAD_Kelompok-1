<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Support\DateFormat;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PROMPT 2 Tahap 3: halaman Mahasiswa/Dosen dengan data demo seeder. */
class BorrowerPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Jakarta')); // Rabu
        $this->seed();
        $this->mahasiswa = User::where('email', 'mahasiswa@campus.test')->firstOrFail();
        $this->actingAs($this->mahasiswa);
    }

    public function test_semua_halaman_peminjam_terbuka_dengan_header_baru(): void
    {
        $booking = Booking::where('user_id', $this->mahasiswa->id)->where('status', 'approved')->firstOrFail();

        foreach ([
            route('dashboard') => 'Selamat datang, Andi.',
            route('rooms.index') => 'Jelajahi laboratorium.',
            route('rooms.show', $booking->room) => 'Ruangan ' . $booking->room->name,
            route('schedules.index') => 'Jadwal ruangan.',
            route('bookings.create') => 'Ajukan peminjaman.',
            route('bookings.mine') => 'Peminjaman saya.',
            route('bookings.show', $booking) => 'Detail peminjaman.',
            route('scan.index') => 'Check-in / Check-out.',
        ] as $url => $title) {
            $this->get($url)->assertOk()->assertSee($title)->assertSee('cs-breadcrumb', false);
        }
    }

    public function test_jelajahi_ruangan_memakai_kartu_dan_jumlah_hasil(): void
    {
        $this->get(route('rooms.index'))
            ->assertSee('19 ruangan ditemukan')
            ->assertSee('data-room-code="TULT-0805"', false)
            ->assertSee('Lihat Ruangan')
            ->assertSee('placeholder="Jumlah"', false);
    }

    public function test_kalender_minggu_berjalan_menandai_hari_ini_dan_legenda(): void
    {
        $this->get(route('schedules.index'))
            ->assertSee('09 Nov – 15 Nov 2026')
            ->assertSee('aria-current="date"', false)
            ->assertSee('Jadwal kuliah')
            ->assertSee('Peminjaman disetujui')
            ->assertDontSee('Kelola jadwal');
    }

    public function test_detail_peminjaman_menampilkan_timeline_dan_kode_cadangan(): void
    {
        $approved = Booking::where('user_id', $this->mahasiswa->id)->where('status', 'approved')->firstOrFail();

        $this->get(route('bookings.show', $approved))
            ->assertSee('cs-timeline', false)
            ->assertSeeInOrder(['Diajukan', 'Disetujui', 'Check-in', 'Selesai'])
            ->assertSee($approved->qr_token);

        $rejected = Booking::where('user_id', $this->mahasiswa->id)->where('status', 'rejected')->firstOrFail();
        $this->get(route('bookings.show', $rejected))
            ->assertSee('Alasan penolakan')
            ->assertSee($rejected->rejection_reason);
    }

    public function test_langkah_timeline_sesuai_status(): void
    {
        $states = fn (string $status) => collect((new Booking(['status' => $status]))->timelineSteps())
            ->map(fn ($s) => $s['label'] . ':' . $s['state'])->all();

        $this->assertSame(['Diajukan:done', 'Disetujui:current', 'Check-in:upcoming', 'Selesai:upcoming'], $states('pending'));
        $this->assertSame(['Diajukan:done', 'Ditolak:failed'], $states('rejected'));
        $this->assertSame(['Diajukan:done', 'Disetujui:done', 'Tidak hadir:failed'], $states('no_show'));
        $this->assertSame(['Diajukan:done', 'Disetujui:done', 'Check-in:current', 'Selesai:upcoming'], $states('approved'));
    }

    public function test_pindai_qr_berbahasa_indonesia(): void
    {
        $this->get(route('scan.index'))
            ->assertSee('Izinkan Kamera')
            ->assertSee('Pindai dari Foto')
            ->assertSee('Masukkan tautan QR secara manual')
            ->assertSee('Siap memindai')
            ->assertSee('<figcaption class="sr-only">Pratinjau kamera pemindai QR</figcaption>', false)
            ->assertDontSee('Request Camera Permissions');
    }

    public function test_format_tanggal_satu_sumber(): void
    {
        $start = CarbonImmutable::parse('2026-11-08 10:36', 'Asia/Jakarta');

        $this->assertSame('8 Nov 2026, 10.36 WIB', DateFormat::dateTime($start));
        $this->assertSame('Min, 8 Nov 2026 · 10.36–12.36 WIB', DateFormat::range($start, $start->addHours(2)));
        $this->assertSame('02 Nov – 08 Nov 2026', DateFormat::weekRange($start->startOfWeek(), $start->endOfWeek()));
    }
}
