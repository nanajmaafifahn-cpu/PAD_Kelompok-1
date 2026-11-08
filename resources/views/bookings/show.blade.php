{{-- Detail Peminjaman (bookings.show).
     Variabel dari BookingController@show:
       $booking     Booking (relasi room.academicUnit & user); timeline dari $booking->timelineSteps()
       $isOwner     bool, user login = pemilik booking
       $backupCode  string|null, kode cadangan QR; HANYA terisi untuk pemilik saat status approved/checked_in
     Aksi: pemilik -> Ubah/Batalkan (partial manage) & Pindai QR; pengelola -> Setujui/Tolak/Hapus (partial actions). --}}
@extends('layouts.app')

@use('App\Support\DateFormat')

@section('title', 'Detail Peminjaman')

@section('page_header')
    <x-page-header eyebrow="Pengajuan & aktivitas" title="Detail peminjaman." :crumb="$isOwner ? 'Peminjaman Saya' : 'Peminjaman'"
                   subtitle="{{ $booking->room?->name }} · {{ DateFormat::range($booking->start_time, $booking->end_time) }}">
        <x-slot:actions>
            <a href="{{ $isOwner ? route('bookings.mine') : route('bookings.index') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="cs-card cs-card-body" aria-labelledby="timeline-heading">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                    <h2 id="timeline-heading" class="text-xl font-extrabold text-ink">Status peminjaman</h2>
                    <x-status-badge :status="$booking->status" />
                </div>
                <ol class="cs-timeline">
                    @foreach ($booking->timelineSteps() as $i => $step)
                        <li class="cs-timeline-step" data-state="{{ $step['state'] }}">
                            <span class="cs-timeline-marker" aria-hidden="true">
                                @if ($step['state'] === 'done')
                                    <x-icon name="check" />
                                @elseif ($step['state'] === 'failed')
                                    <x-icon name="close" />
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="cs-timeline-label">{{ $step['label'] }}</p>
                                <p class="text-xs text-ink-muted">
                                    @if ($step['at'])
                                        {{ DateFormat::dateTime($step['at']) }}
                                    @elseif ($step['state'] === 'current')
                                        Sedang berlangsung
                                    @elseif ($step['state'] === 'upcoming')
                                        Belum
                                    @endif
                                </p>
                                <span class="sr-only">({{ ['done' => 'selesai', 'current' => 'tahap saat ini', 'upcoming' => 'belum', 'failed' => 'berhenti di sini'][$step['state']] }})</span>
                            </div>
                        </li>
                    @endforeach
                </ol>

                @if ($booking->rejection_reason)
                    <div class="cs-alert cs-alert-danger mt-5 mb-0">
                        <x-icon name="alert" class="cs-alert-icon h-5 w-5" />
                        <div>
                            <p class="cs-alert-title">Alasan penolakan</p>
                            <p>{{ $booking->rejection_reason }}</p>
                        </div>
                    </div>
                @endif
            </section>

            <section class="cs-card cs-card-body" aria-labelledby="info-heading">
                <h2 id="info-heading" class="mb-4 text-xl font-extrabold text-ink">Rincian</h2>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="cs-caption">Ruang</dt>
                        <dd class="font-semibold text-ink">{{ $booking->room?->name }} <span class="cs-code">{{ $booking->room?->code }}</span></dd>
                        <dd class="text-sm text-ink-muted">{{ $booking->room?->building }} · Lantai {{ $booking->room?->floor ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="cs-caption">Waktu</dt>
                        <dd class="font-semibold text-ink">{{ DateFormat::range($booking->start_time, $booking->end_time) }}</dd>
                    </div>
                    <div>
                        <dt class="cs-caption">Pemohon</dt>
                        <dd class="font-semibold text-ink">{{ $booking->requester_name ?? $booking->user?->name }}</dd>
                        <dd class="text-sm text-ink-muted">{{ $booking->requester_role }}</dd>
                    </div>
                    <div>
                        <dt class="cs-caption">Peserta</dt>
                        <dd class="font-semibold text-ink">{{ $booking->participant_count }} orang</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="cs-caption">Keperluan</dt>
                        <dd class="text-ink-body">{{ $booking->purpose }}</dd>
                    </div>
                    <div>
                        <dt class="cs-caption">Check-in</dt>
                        <dd class="font-semibold text-ink">{{ DateFormat::dateTime($booking->checked_in_at) }}</dd>
                    </div>
                    <div>
                        <dt class="cs-caption">Check-out</dt>
                        <dd class="font-semibold text-ink">{{ DateFormat::dateTime($booking->checked_out_at) }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside class="space-y-6 self-start">
            @if ($backupCode)
                <section class="cs-card cs-card-body space-y-3" aria-labelledby="qr-heading">
                    <h2 id="qr-heading" class="text-xl font-extrabold text-ink">Check-in / Check-out</h2>
                    <p class="text-sm text-ink-soft">
                        Pindai QR yang tertempel di pintu ruang {{ $booking->room?->name }}.
                        Check-in dibuka {{ DateFormat::time($booking->checkInOpensAt()) }}–{{ DateFormat::time($booking->checkInClosesAt()) }} WIB.
                    </p>
                    <a href="{{ route('scan.index') }}" class="cs-btn cs-btn-primary w-full"><x-icon name="qr" class="h-4 w-4" /> Pindai QR</a>
                    <div class="rounded-lg bg-[color:var(--cs-color-surface-muted)] p-3">
                        <p class="cs-caption">Kode cadangan (bila kamera tidak bisa dipakai)</p>
                        <p class="mt-1"><code class="cs-code select-all break-all text-sm" data-testid="backup-code">{{ $backupCode }}</code></p>
                        <p class="cs-caption mt-1">Ketik di halaman Pindai QR. Jangan bagikan kode ini.</p>
                    </div>
                </section>
            @endif

            <section class="cs-card cs-card-body space-y-3" aria-label="Tindakan">
                <h2 class="text-xl font-extrabold text-ink">Tindakan</h2>
                @if ($isOwner)
                    {{-- Partial manage hanya merender tombol yang diizinkan (Ubah / Batalkan); kosong = tidak ada tindakan. --}}
                    @php $ownerActions = trim(view('bookings.partials.manage', ['booking' => $booking])->render()); @endphp
                    @if ($ownerActions !== '')
                        {!! $ownerActions !!}
                    @else
                        <p class="text-sm text-ink-muted">Tidak ada tindakan untuk peminjaman ini. Pembatalan hanya bisa dilakukan sebelum waktu mulai.</p>
                    @endif
                @else
                    @includeFirst(
                        ['bookings.partials.actions.' . $booking->status, 'bookings.partials.actions.none'],
                        ['booking' => $booking]
                    )
                @endif
            </section>
        </aside>
    </div>
@endsection
