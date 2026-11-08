{{-- Dashboard Peminjam (route dashboard, role borrower).
     Variabel dari DashboardController -> DashboardService::borrower():
       $stats                 ['total', 'pending', 'active', 'completed']
       $recentBookings        collection Booking (relasi room), 5 terbaru
       $nextBooking           Booking|null approved/checked_in terdekat yang belum selesai (relasi room)
       $nextBookingScannable  bool, tombol "Pindai QR" ditampilkan (dalam jendela check-in / sedang check-in)
     Tampilan final dirapikan di PROMPT 2 Tahap 3. --}}
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Selamat datang, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') ?: auth()->user()->name }}.</h1>
            <p class="cs-page-description">Ringkasan peminjaman ruang Anda.</p>
        </div>
        <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-primary">Temukan Ruangan</a>
    </header>

    <dl class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Peminjaman saya" :value="$stats['total']" caption="Seluruh pengajuan" icon="clipboard" />
        <x-stat-card label="Menunggu persetujuan" :value="$stats['pending']" caption="Dalam pemeriksaan" icon="clock" tone="pending" />
        <x-stat-card label="Peminjaman aktif" :value="$stats['active']" caption="Disetujui / sedang digunakan" icon="activity" tone="checked_in" />
        <x-stat-card label="Kegiatan selesai" :value="$stats['completed']" caption="Check-out tercatat" icon="check-circle" tone="completed" />
    </dl>

    @if ($nextBooking)
        <section class="cs-card cs-card-body mb-6" aria-labelledby="next-heading">
            <h2 id="next-heading" class="cs-section-title">Peminjaman berikutnya</h2>
            <p class="mt-1">{{ $nextBooking->room?->name }} ({{ $nextBooking->room?->code }}) ·
                {{ $nextBooking->start_time->locale('id')->isoFormat('ddd, D MMM YYYY HH.mm') }}–{{ $nextBooking->end_time->format('H.i') }} WIB
                · <x-status-badge :status="$nextBooking->status" /></p>
            @if ($nextBookingScannable)
                <a href="{{ route('scan.index') }}" class="cs-btn cs-btn-primary mt-3">Pindai QR</a>
            @endif
        </section>
    @endif

    <section class="cs-card cs-card-body" aria-labelledby="recent-heading">
        <div class="flex items-center justify-between">
            <h2 id="recent-heading" class="cs-section-title">Peminjaman terbaru</h2>
            <a href="{{ route('bookings.mine') }}" class="cs-link">Lihat semua</a>
        </div>
        @forelse ($recentBookings as $booking)
            <p class="border-t border-border py-2 text-sm">
                <a href="{{ route('bookings.show', $booking) }}" class="cs-link">{{ $booking->room?->name }}</a>
                · {{ $booking->start_time->locale('id')->isoFormat('D MMM YYYY HH.mm') }} · {{ $booking->purpose }}
                · <x-status-badge :status="$booking->status" />
            </p>
        @empty
            <x-empty-state title="Belum ada peminjaman" text="Data akan muncul setelah Anda mengajukan peminjaman ruang.">
                <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
            </x-empty-state>
        @endforelse
    </section>
@endsection
