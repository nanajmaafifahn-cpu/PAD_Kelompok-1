{{-- Daftar peminjaman — dua mode:
       $scope = 'mine' (bookings.mine)  : Peminjaman Saya (ref 05) — ruang, waktu, keperluan, status, Detail/Ubah/Batalkan
       $scope = 'all'  (bookings.index) : Kelola Peminjaman pengelola/admin/pimpinan (ref 08) — Peminjam, Ruang, Waktu,
                                          Peserta, Status, Tindakan (Setujui/Tolak/Hapus lewat x-confirm-dialog)
     Variabel dari BookingController@index / @mine:
       $bookings  paginator Booking 10/halaman (relasi room & user), urut waktu mulai terbaru
       $scope     'all' | 'mine'
     Tombol aksi status dipilih dari bookings/partials/actions/{status}.blade.php (ber-@can).
     Filter status belum didukung controller, jadi tidak ditampilkan. Di bawah 768px tabel tampil sebagai kartu. --}}
@extends('layouts.app')

@use('App\Support\DateFormat')

@php
    $isMine = ($scope ?? 'all') === 'mine';
@endphp

@section('title', $isMine ? 'Peminjaman Saya' : 'Kelola Peminjaman')

@section('page_header')
    <x-page-header eyebrow="Pengajuan & aktivitas" :title="$isMine ? 'Peminjaman saya.' : 'Kelola peminjaman.'"
                   :crumb="$isMine ? 'Peminjaman Saya' : 'Peminjaman'"
                   subtitle="Pantau keputusan, akses ruangan, dan riwayat penggunaan.">
        @can('create', \App\Models\Booking::class)
            <x-slot:actions>
                <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
            </x-slot:actions>
        @endcan
    </x-page-header>
@endsection

@section('content')
    <section aria-labelledby="bookings-heading">
        <h2 id="bookings-heading" class="mb-4 text-ink-soft">{{ $bookings->total() }} pengajuan</h2>

        @if ($bookings->isEmpty())
            <div class="cs-card">
                <x-empty-state title="Belum ada peminjaman"
                               :text="$isMine ? 'Ajukan peminjaman ruang; statusnya akan dipantau di sini.' : 'Pengajuan dari mahasiswa dan dosen akan muncul di sini.'">
                    @can('create', \App\Models\Booking::class)
                        <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
                    @endcan
                </x-empty-state>
            </div>
        @else
            <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-labelledby="bookings-heading" tabindex="0">
                <table class="cs-table cs-table-stack">
                    <caption class="sr-only">
                        Daftar peminjaman, halaman {{ $bookings->currentPage() }} dari {{ $bookings->lastPage() }}
                    </caption>
                    <thead>
                        <tr>
                            @unless ($isMine)
                                <th scope="col">Peminjam</th>
                            @endunless
                            <th scope="col">Ruang</th>
                            <th scope="col">Waktu</th>
                            @if ($isMine)
                                <th scope="col">Keperluan</th>
                            @else
                                <th scope="col" class="text-right">Peserta</th>
                            @endif
                            <th scope="col">Status</th>
                            <th scope="col">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookings as $booking)
                            <tr>
                                @unless ($isMine)
                                    <th scope="row">
                                        <span class="block font-semibold text-ink">{{ $booking->requester_name ?? $booking->user?->name }}</span>
                                        @if ($booking->requester_role)
                                            <span class="block text-xs text-ink-muted">{{ $booking->requester_role }}</span>
                                        @endif
                                    </th>
                                @endunless
                                {{-- Mode "mine": kolom Ruang menjadi header baris (<th scope="row">). --}}
                                <{{ $isMine ? 'th scope=row' : 'td' }} data-label="Ruang">
                                    <span class="block font-semibold text-ink">{{ $booking->room?->name }}</span>
                                    <span class="cs-code">{{ $booking->room?->code }}</span>
                                </{{ $isMine ? 'th' : 'td' }}>
                                <td data-label="Waktu">
                                    <time datetime="{{ $booking->start_time->toIso8601String() }}">{{ DateFormat::range($booking->start_time, $booking->end_time) }}</time>
                                    @unless ($isMine)
                                        <span class="block text-xs text-ink-muted">{{ $booking->purpose }}</span>
                                    @endunless
                                </td>
                                @if ($isMine)
                                    <td data-label="Keperluan" class="md:max-w-xs">{{ $booking->purpose }}</td>
                                @else
                                    <td data-label="Peserta" class="cs-cell-num">{{ $booking->participant_count }} <span class="text-xs text-ink-muted">orang</span></td>
                                @endif
                                <td data-label="Status"><x-status-badge :status="$booking->status" /></td>
                                <td data-label="Tindakan">
                                    <a href="{{ route('bookings.show', $booking) }}" class="cs-btn cs-btn-secondary cs-btn-sm mb-2">
                                        Detail<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
                                    </a>
                                    @if ($isMine)
                                        @include('bookings.partials.manage')
                                    @else
                                        @includeFirst(
                                            ['bookings.partials.actions.' . $booking->status, 'bookings.partials.actions.none'],
                                            ['booking' => $booking]
                                        )
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-6">{{ $bookings->links() }}</div>
    </section>
@endsection
