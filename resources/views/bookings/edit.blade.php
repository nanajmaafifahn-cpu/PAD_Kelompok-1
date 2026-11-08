{{-- FE-06 · Form Ubah Booking
     STATUS INTEGRASI: AKTIF (BE-06, Fase 3):
       GET  bookings.edit    -> BookingController@edit   kirim $booking, $rooms ke view ini
       PUT  bookings.update  -> BookingController@update validasi UpdateBookingRequest + cek ulang kapasitas & bentrok
     Aturan status (usulan BE-06, menunggu konfirmasi BE): hanya booking `pending` yang boleh diubah.
     View ini menyembunyikan form untuk status lain hanya sebagai kenyamanan tampilan; server TETAP wajib
     menolak perubahan booking non-pending (menyembunyikan form bukan pengganti otorisasi).
     Form hanya dirender bila route bookings.update terdaftar (x-route-form, method dari definisi route). --}}
@extends('layouts.app')

@php
    $isEditable = $booking->getStatus() === \App\Models\Booking::STATUS_PENDING;
@endphp

@section('title', 'Ubah Peminjaman')
@section('inline_error_fields', 'room_id,participant_count,start_time,end_time,purpose')

@section('page_header')
    <x-page-header eyebrow="Pengajuan & aktivitas" title="Ubah peminjaman." crumb="Ubah Peminjaman"
                   subtitle="Peminjaman hanya dapat diubah selama menunggu persetujuan; setelah disimpan statusnya tetap menunggu persetujuan.">
        <x-slot:actions>
            <a href="{{ route('bookings.show', $booking) }}" class="cs-btn cs-btn-secondary">Kembali ke detail</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form ubah peminjaman">
        <p class="mb-4 text-sm text-ink-soft">Status saat ini: <x-status-badge :status="$booking->status" /></p>

        @if (! $isEditable)
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Peminjaman ini tidak dapat diubah karena statusnya bukan <strong>Menunggu persetujuan</strong>.</p>
            </div>

            <dl class="cs-card cs-card-body mb-6 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="cs-legend mb-1">Pemohon</dt>
                    <dd>{{ $booking->requester_name }}@if ($booking->requester_role) · {{ $booking->requester_role }}@endif</dd>
                </div>
                <div>
                    <dt class="cs-legend mb-1">Ruang</dt>
                    <dd>{{ $booking->room?->code }} — {{ $booking->room?->name }}</dd>
                </div>
                <div>
                    <dt class="cs-legend mb-1">Jadwal</dt>
                    <dd>
                        <time datetime="{{ $booking->start_time->toIso8601String() }}">{{ \App\Support\DateFormat::range($booking->start_time, $booking->end_time) }}</time>
                    </dd>
                </div>
                <div>
                    <dt class="cs-legend mb-1">Keperluan</dt>
                    <dd>{{ $booking->purpose }}</dd>
                </div>
            </dl>
            <a href="{{ route('bookings.mine') }}" class="cs-btn cs-btn-secondary">Kembali ke Peminjaman Saya</a>
        @elseif (Route::has('bookings.update'))
            <x-route-form route="bookings.update" :params="$booking" class="cs-card cs-card-body space-y-6" data-cs-validate>
                @include('bookings.partials.form', ['booking' => $booking])

                <div class="cs-form-actions">
                    <a href="{{ route('bookings.show', $booking) }}" class="cs-btn cs-btn-ghost">Batal</a>
                    <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
                </div>
            </x-route-form>
        @else
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Perubahan booking belum dapat disimpan karena layanan pembaruan booking belum tersedia di sistem.</p>
            </div>
            <a href="{{ route('bookings.mine') }}" class="cs-btn cs-btn-secondary">Kembali ke Peminjaman Saya</a>
        @endif
    </section>
@endsection
