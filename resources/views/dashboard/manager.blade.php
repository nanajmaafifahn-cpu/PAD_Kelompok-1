{{-- Dashboard Pengelola / Admin / Pimpinan (route dashboard).
     Variabel dari DashboardController -> DashboardService::manager():
       $stats            ['rooms_active', 'available_now', 'occupied_now', 'pending']
       $pendingBookings  collection Booking pending (relasi room & user), 5 dengan waktu mulai terdekat
       $rooms            collection Room (urut kode)
       $liveStatuses     [room_id => available|booked|occupied|maintenance]
     Tombol Setujui/Tolak hanya untuk lab_head/admin (@can approve). Pimpinan hanya membaca.
     Tampilan final dirapikan di PROMPT 2 Tahap 4. --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Selamat datang, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}.</h1>
            <p class="cs-page-description">Status ruang saat ini dan pengajuan yang menunggu keputusan.</p>
        </div>
        <a href="{{ route('dashboard.utilization') }}" class="cs-btn cs-btn-primary">Lihat Laporan</a>
    </header>

    <dl class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Ruangan aktif" :value="$stats['rooms_active']" caption="Selain yang sedang perawatan" icon="building" />
        <x-stat-card label="Tersedia sekarang" :value="$stats['available_now']" caption="Tanpa peminjaman berjalan" icon="door" tone="available" />
        <x-stat-card label="Sedang digunakan" :value="$stats['occupied_now']" caption="Check-in telah tercatat" icon="activity" tone="occupied" />
        <x-stat-card label="Menunggu persetujuan" :value="$stats['pending']" caption="Pengajuan peminjaman" icon="clock" tone="pending" />
    </dl>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="cs-card cs-card-body" aria-labelledby="pending-heading">
            <div class="flex items-center justify-between">
                <h2 id="pending-heading" class="cs-section-title">Menunggu persetujuan</h2>
                <a href="{{ route('bookings.index') }}" class="cs-link">Lihat semua</a>
            </div>
            @forelse ($pendingBookings as $booking)
                <div class="border-t border-border py-2 text-sm">
                    <p><a href="{{ route('bookings.show', $booking) }}" class="cs-link">{{ $booking->requester_name ?? $booking->user?->name }}</a>
                        · {{ $booking->room?->name }} · {{ $booking->start_time->locale('id')->isoFormat('D MMM YYYY HH.mm') }}</p>
                    @include('bookings.partials.actions.pending', ['booking' => $booking])
                </div>
            @empty
                <x-empty-state title="Tidak ada pengajuan" text="Semua pengajuan peminjaman sudah diputuskan." />
            @endforelse
        </section>

        <section class="cs-card cs-card-body" aria-labelledby="rooms-heading">
            <h2 id="rooms-heading" class="cs-section-title">Status ruang sekarang</h2>
            <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                @foreach ($rooms as $room)
                    <li class="cs-status-indicator" data-status="{{ $liveStatuses[$room->id] }}">
                        <span class="cs-status-dot" aria-hidden="true"></span>
                        {{ $room->name }} <span class="text-ink-muted">· {{ \App\Models\Room::LIVE_STATUS_LABELS[$liveStatuses[$room->id]] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection
