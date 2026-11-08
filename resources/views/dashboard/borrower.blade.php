{{-- Dashboard Peminjam (route dashboard, role borrower) — referensi 01-mhs-dashboard.
     Variabel dari DashboardController -> DashboardService::borrower():
       $stats                 ['total', 'pending', 'active', 'completed']
       $recentBookings        collection Booking (relasi room), 5 terbaru
       $nextBooking           Booking|null approved/checked_in terdekat yang belum selesai (relasi room)
       $nextBookingScannable  bool, tombol "Pindai QR" ditampilkan (dalam jendela check-in / sedang check-in) --}}
@extends('layouts.app')

@use('App\Support\DateFormat')

@php
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name;
@endphp

@section('title', 'Dashboard')

@section('page_header')
    <x-page-header eyebrow="Dashboard mahasiswa" title="Selamat datang, {{ $firstName }}." crumb="Dashboard"
                   subtitle="Semua informasi ruangan dan aktivitas peminjaman Anda dalam satu tempat.">
        <x-slot:actions>
            <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-primary">Temukan Ruangan</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <dl class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Peminjaman saya" :value="$stats['total']" caption="Seluruh pengajuan" icon="clipboard" />
        <x-stat-card label="Menunggu persetujuan" :value="$stats['pending']" caption="Dalam pemeriksaan" icon="clock" tone="pending" />
        <x-stat-card label="Peminjaman aktif" :value="$stats['active']" caption="Disetujui / sedang digunakan" icon="activity" tone="checked_in" />
        <x-stat-card label="Kegiatan selesai" :value="$stats['completed']" caption="Check-out tercatat" icon="check-circle" tone="completed" />
    </dl>

    @if ($nextBooking)
        <section class="cs-card cs-card-body mb-6" aria-labelledby="next-heading">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="cs-eyebrow mb-1">Peminjaman berikutnya</p>
                    <h2 id="next-heading" class="text-xl font-extrabold text-ink">
                        {{ $nextBooking->room?->name }} <span class="text-base font-semibold text-ink-soft">· {{ $nextBooking->room?->code }}</span>
                    </h2>
                    <p class="mt-1 text-ink-soft">{{ DateFormat::range($nextBooking->start_time, $nextBooking->end_time) }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        <x-status-badge :status="$nextBooking->status" />
                        <span class="text-ink-muted">{{ $nextBooking->purpose }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('bookings.show', $nextBooking) }}" class="cs-btn cs-btn-secondary">Detail</a>
                    @if ($nextBookingScannable)
                        <a href="{{ route('scan.index') }}" class="cs-btn cs-btn-primary"><x-icon name="qr" class="h-4 w-4" /> Pindai QR</a>
                    @else
                        <span class="self-center text-sm text-ink-muted">Check-in dibuka {{ DateFormat::time($nextBooking->checkInOpensAt()) }} WIB</span>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="cs-card" aria-labelledby="recent-heading">
        <div class="flex items-center justify-between gap-3 px-6 pt-6">
            <h2 id="recent-heading" class="text-xl font-extrabold text-ink">Peminjaman terbaru</h2>
            <a href="{{ route('bookings.mine') }}" class="cs-link">Lihat semua</a>
        </div>

        @if ($recentBookings->isEmpty())
            <x-empty-state title="Belum ada peminjaman" text="Data akan muncul setelah Anda mengajukan peminjaman ruang.">
                <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
            </x-empty-state>
        @else
            <div class="cs-table-wrap cs-table-wrap-stack mt-4 rounded-none border-0 border-t" role="region" aria-labelledby="recent-heading" tabindex="0">
                <table class="cs-table cs-table-stack">
                    <thead>
                        <tr>
                            <th scope="col">Ruangan</th>
                            <th scope="col">Waktu</th>
                            <th scope="col">Keperluan</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentBookings as $booking)
                            <tr>
                                <th scope="row">
                                    <a href="{{ route('bookings.show', $booking) }}" class="cs-link">{{ $booking->room?->name }}</a>
                                    <span class="block text-xs text-ink-muted">{{ $booking->room?->code }}</span>
                                </th>
                                <td data-label="Waktu">{{ DateFormat::range($booking->start_time, $booking->end_time) }}</td>
                                <td data-label="Keperluan">{{ $booking->purpose }}</td>
                                <td data-label="Status"><x-status-badge :status="$booking->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
