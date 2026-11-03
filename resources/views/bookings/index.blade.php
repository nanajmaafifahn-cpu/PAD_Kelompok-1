{{-- FE-03 / FE-05 · Daftar Booking + Panel Verify + Simulator Scan QR
     Variabel dari BookingController@index:
       $bookings  paginator Booking (dengan relasi room & user)
     Tombol aksi dipilih berdasarkan nama status lewat partial
     bookings/partials/actions/{status}.blade.php — tanpa if/else di view ini.
     Menambah status baru cukup menambah satu file partial.
     Di bawah 768px tabel tampil sebagai kartu (.cs-table-stack, label dari data-label).
     Catatan: approve/reject/check-in/check-out mensyaratkan user login (FormRequest authorize),
     sehingga saat ini ditolak 403 sampai fitur login tersedia (usulan BE-01). --}}
@extends('layouts.app')

@section('title', 'Booking Ruang')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Booking Ruang</h1>
            <p class="cs-page-description">Pengajuan, verifikasi, dan simulasi scan QR check-in / check-out.</p>
        </div>
        <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">
            <span aria-hidden="true">+</span> Ajukan Booking
        </a>
    </header>

    {{-- Alur kerja lintas stakeholder (informasi statis) --}}
    <section aria-labelledby="flow-heading" class="mb-6">
        <h2 id="flow-heading" class="sr-only">Alur peminjaman ruang</h2>
        <ol class="grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <li class="cs-card px-3 py-2">
                <span class="font-semibold text-ink">1. Submit</span>
                <span class="block text-ink-muted">Mahasiswa / Dosen</span>
            </li>
            <li class="cs-card px-3 py-2">
                <span class="font-semibold text-ink">2. Verify</span>
                <span class="block text-ink-muted">Kepala Lab / Admin Akademik</span>
            </li>
            <li class="cs-card px-3 py-2">
                <span class="font-semibold text-ink">3. QR Check-In</span>
                <span class="block text-ink-muted">Pengguna di depan ruang</span>
            </li>
            <li class="cs-card px-3 py-2">
                <span class="font-semibold text-ink">4. QR Check-Out</span>
                <span class="block text-ink-muted">Pengguna selesai memakai</span>
            </li>
        </ol>
    </section>

    @guest
        <div class="cs-alert cs-alert-info">
            <span aria-hidden="true" class="cs-alert-icon">i</span>
            <p>Verifikasi (Approve / Reject) dan scan QR check-in / check-out hanya dapat diproses oleh petugas yang login.
                Tanpa login, server akan menolak aksi tersebut.</p>
        </div>
    @endguest

    <section aria-labelledby="bookings-heading">
        <h2 id="bookings-heading" class="sr-only">Daftar booking</h2>

        <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-labelledby="bookings-heading" tabindex="0">
            <table class="cs-table cs-table-stack">
                <caption class="sr-only">
                    Daftar booking ruang, halaman {{ $bookings->currentPage() }} dari {{ $bookings->lastPage() }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Pemohon</th>
                        <th scope="col">Ruang</th>
                        <th scope="col" class="text-right">Peserta</th>
                        <th scope="col">Jadwal</th>
                        <th scope="col">Keperluan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aktual</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <th scope="row">
                                <span class="block font-medium text-ink">{{ $booking->requester_name ?? $booking->user?->name }}</span>
                                @if ($booking->requester_role)
                                    <span class="block text-xs text-ink-muted">{{ $booking->requester_role }}</span>
                                @endif
                            </th>
                            <td data-label="Ruang">
                                <span class="block text-ink">{{ $booking->room?->name }}</span>
                                <span class="cs-code">{{ $booking->room?->code }}</span>
                            </td>
                            <td data-label="Peserta" class="cs-cell-num">{{ $booking->participant_count }} <span class="text-xs text-ink-muted">orang</span></td>
                            <td data-label="Jadwal" class="cs-cell-nowrap">
                                <time datetime="{{ $booking->start_time->toIso8601String() }}" class="block text-ink">
                                    {{ $booking->start_time->locale('id')->isoFormat('ddd, D MMM YYYY') }}
                                </time>
                                <span class="block text-xs tabular-nums text-ink-muted">
                                    {{ $booking->start_time->format('H:i') }} –
                                    <time datetime="{{ $booking->end_time->toIso8601String() }}">{{ $booking->end_time->format('H:i') }}</time>
                                </span>
                            </td>
                            <td data-label="Keperluan" class="md:max-w-xs">{{ $booking->purpose }}</td>
                            <td data-label="Status"><x-status-badge :status="$booking->status" /></td>
                            <td data-label="Aktual" class="cs-cell-nowrap text-xs tabular-nums text-ink-soft">
                                <span class="block">In:
                                    @if ($booking->checked_in_at)
                                        <time datetime="{{ $booking->checked_in_at->toIso8601String() }}">{{ $booking->checked_in_at->format('H:i') }}</time>
                                    @else
                                        <span aria-hidden="true">—</span><span class="sr-only">belum ada</span>
                                    @endif
                                </span>
                                <span class="block">Out:
                                    @if ($booking->checked_out_at)
                                        <time datetime="{{ $booking->checked_out_at->toIso8601String() }}">{{ $booking->checked_out_at->format('H:i') }}</time>
                                    @else
                                        <span aria-hidden="true">—</span><span class="sr-only">belum ada</span>
                                    @endif
                                </span>
                            </td>
                            <td data-label="Aksi">
                                @includeFirst(
                                    ['bookings.partials.actions.' . $booking->status, 'bookings.partials.actions.none'],
                                    ['booking' => $booking]
                                )
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="cs-empty-state">
                                    <p class="cs-empty-state-title">Belum ada pengajuan booking.</p>
                                    <p class="mt-1">Booking yang diajukan akan muncul di sini beserta status verifikasi dan kehadirannya.
                                        <a href="{{ route('bookings.create') }}" class="cs-link">Ajukan booking pertama</a>.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    </section>
@endsection
