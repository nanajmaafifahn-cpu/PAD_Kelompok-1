{{-- C6 · Detail Booking
     Variabel dari BookingController@show:
       $booking     Booking (relasi room.academicUnit & user)
       $isOwner     bool, user login = pemilik booking
       $backupCode  string|null, kode cadangan QR; HANYA terisi untuk pemilik saat status approved/checked_in
     Aksi verifikasi / check-in memakai partial yang sama dengan daftar booking (@can di dalamnya). --}}
@extends('layouts.app')

@section('title', 'Detail Booking')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ $isOwner ? route('bookings.mine') : route('bookings.index') }}" class="cs-back-link">
                <span aria-hidden="true">←</span> Kembali ke daftar
            </a>
            <h1 id="page-title" class="cs-page-title mt-2">Detail Booking</h1>
            <p class="cs-page-description">Status: <x-status-badge :status="$booking->status" /></p>
        </header>

        <div class="cs-card cs-card-body space-y-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="cs-form-label">Ruang</dt>
                    <dd class="text-ink">{{ $booking->room?->name }} <span class="cs-code">{{ $booking->room?->code }}</span></dd>
                    <dd class="text-xs text-ink-muted">{{ $booking->room?->building }} · Lantai {{ $booking->room?->floor ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="cs-form-label">Jadwal</dt>
                    <dd class="text-ink">
                        <time datetime="{{ $booking->start_time->toIso8601String() }}">{{ $booking->start_time->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</time>
                    </dd>
                    <dd class="text-xs tabular-nums text-ink-muted">
                        {{ $booking->start_time->format('H.i') }}–{{ $booking->end_time->format('H.i T') }}
                    </dd>
                </div>
                <div>
                    <dt class="cs-form-label">Pemohon</dt>
                    <dd class="text-ink">{{ $booking->requester_name ?? $booking->user?->name }}</dd>
                    <dd class="text-xs text-ink-muted">{{ $booking->requester_role }}</dd>
                </div>
                <div>
                    <dt class="cs-form-label">Peserta</dt>
                    <dd class="text-ink">{{ $booking->participant_count }} orang</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="cs-form-label">Keperluan</dt>
                    <dd class="text-ink">{{ $booking->purpose }}</dd>
                </div>
                @if ($booking->rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="cs-form-label">Alasan penolakan</dt>
                        <dd class="text-danger">{{ $booking->rejection_reason }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="cs-form-label">Check-in</dt>
                    <dd class="tabular-nums text-ink">{{ $booking->checked_in_at?->format('H.i T') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="cs-form-label">Check-out</dt>
                    <dd class="tabular-nums text-ink">{{ $booking->checked_out_at?->format('H.i T') ?? '—' }}</dd>
                </div>
            </dl>

            @if ($backupCode)
                <div class="cs-alert cs-alert-info mb-0">
                    <span aria-hidden="true" class="cs-alert-icon">i</span>
                    <div>
                        <p class="cs-alert-title">Check-in / check-out lewat QR ruangan</p>
                        <p>Scan QR yang tertempel di pintu ruang {{ $booking->room?->name }}.
                            Check-in dibuka {{ $booking->checkInOpensAt()->format('H.i') }}–{{ $booking->checkInClosesAt()->format('H.i T') }}.
                            Bila kamera tidak bisa dipakai, ketik kode cadangan ini di halaman
                            <a href="{{ route('scan.index') }}" class="cs-link">Scan QR</a>:</p>
                        <p class="mt-2"><code class="cs-code select-all text-sm" data-testid="backup-code">{{ $backupCode }}</code></p>
                        <p class="cs-caption mt-1">Jangan bagikan kode ini; kode hanya berlaku untuk akun Anda.</p>
                    </div>
                </div>
            @endif

            <div>
                @includeFirst(
                    ['bookings.partials.actions.' . $booking->status, 'bookings.partials.actions.none'],
                    ['booking' => $booking]
                )
            </div>
        </div>
    </section>
@endsection
