{{-- Dashboard Pengelola / Admin / Pimpinan (route dashboard) — referensi 07-pengelola-dashboard.
     Variabel dari DashboardController -> DashboardService::manager():
       $stats            ['rooms_active', 'available_now', 'occupied_now', 'pending']
       $pendingBookings  collection Booking pending (relasi room & user), 5 dengan waktu mulai terdekat
       $rooms            collection Room (urut kode)
       $liveStatuses     [room_id => available|booked|occupied|maintenance]
     Panel "Aktivitas peminjaman" di referensi (kosong) diganti dua panel: Menunggu persetujuan & Status ruang sekarang.
     Tombol Setujui/Tolak hanya untuk lab_head/admin (@can approve di partial). Pimpinan hanya membaca. --}}
@extends('layouts.app')

@use('App\Models\Room')
@use('App\Support\DateFormat')

@php
    $user = auth()->user();
    $firstName = \Illuminate\Support\Str::before($user->name, ' ') ?: $user->name;
    $isLeader = $user->hasRole(\App\Models\User::ROLE_LEADER); // pimpinan: hanya dashboard ringkas & laporan
    $eyebrow = match (true) {
        $user->hasRole(\App\Models\User::ROLE_ADMIN) => 'Dashboard admin akademik',
        $user->hasRole(\App\Models\User::ROLE_LEADER) => 'Dashboard pimpinan',
        default => 'Dashboard pengelola ruang',
    };
@endphp

@section('title', 'Dashboard')

@section('page_header')
    <x-page-header :eyebrow="$eyebrow" title="Selamat datang, {{ $firstName }}." crumb="Dashboard"
                   subtitle="Status ruang saat ini dan pengajuan yang menunggu keputusan.">
        <x-slot:actions>
            <a href="{{ route('dashboard.utilization') }}" class="cs-btn cs-btn-primary">Lihat Laporan</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <dl class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Ruangan aktif" :value="$stats['rooms_active']" caption="Selain yang sedang perawatan" icon="building" />
        <x-stat-card label="Tersedia sekarang" :value="$stats['available_now']" caption="Tanpa peminjaman berjalan" icon="door" tone="available" />
        <x-stat-card label="Sedang digunakan" :value="$stats['occupied_now']" caption="Check-in telah tercatat" icon="activity" tone="occupied" />
        <x-stat-card label="Menunggu persetujuan" :value="$stats['pending']" caption="Pengajuan peminjaman" icon="clock" tone="pending" />
    </dl>

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="cs-card xl:col-span-3" aria-labelledby="pending-heading">
            <div class="flex items-center justify-between gap-3 px-6 pt-6">
                <h2 id="pending-heading" class="text-xl font-extrabold text-ink">Menunggu persetujuan</h2>
                @unless ($isLeader)
                    <a href="{{ route('bookings.index') }}" class="cs-link">Lihat semua</a>
                @endunless
            </div>

            @if ($pendingBookings->isEmpty())
                <x-empty-state title="Tidak ada pengajuan" text="Semua pengajuan peminjaman sudah diputuskan." />
            @else
                <ul class="mt-4 divide-y divide-[color:var(--cs-color-border)]">
                    @foreach ($pendingBookings as $booking)
                        <li class="flex flex-col gap-3 px-6 py-4 md:flex-row md:items-center md:justify-between">
                            <div class="min-w-0">
                                <a href="{{ route('bookings.show', $booking) }}" class="font-bold text-ink hover:underline">
                                    {{ $booking->requester_name ?? $booking->user?->name }}
                                </a>
                                <p class="text-sm text-ink-soft">
                                    {{ $booking->room?->name }} · {{ DateFormat::range($booking->start_time, $booking->end_time) }}
                                </p>
                                <p class="text-xs text-ink-muted">{{ $booking->participant_count }} orang · {{ $booking->purpose }}</p>
                            </div>
                            <div class="shrink-0">
                                @include('bookings.partials.actions.pending', ['booking' => $booking])
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="cs-card cs-card-body xl:col-span-2" aria-labelledby="rooms-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="rooms-heading" class="text-xl font-extrabold text-ink">Status ruang sekarang</h2>
                @unless ($isLeader)
                    <a href="{{ route('rooms.index') }}" class="cs-link">Jelajahi</a>
                @endunless
            </div>
            <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                @foreach ($rooms as $room)
                    <li class="flex items-center justify-between gap-3 rounded-lg border border-[color:var(--cs-color-border)] px-3 py-2">
                        <span class="min-w-0 truncate font-semibold text-ink" title="{{ $room->name }} · {{ $room->code }}">{{ $room->name }}</span>
                        <span class="cs-live-dot" data-status="{{ $liveStatuses[$room->id] }}">{{ Room::LIVE_STATUS_SHORT_LABELS[$liveStatuses[$room->id]] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection
