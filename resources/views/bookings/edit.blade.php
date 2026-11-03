{{-- FE-06 · Form Ubah Booking
     STATUS INTEGRASI: BLOCKED — backend belum menyediakan route & method berikut (usulan BE-06 di KONTRAK-VIEW.md):
       GET  bookings.edit    -> BookingController@edit   kirim $booking, $rooms (opsional $roles) ke view ini
       PUT  bookings.update  -> BookingController@update validasi UpdateBookingRequest + cek ulang kapasitas & bentrok
     Aturan status (usulan BE-06, menunggu konfirmasi BE): hanya booking `pending` yang boleh diubah.
     View ini menyembunyikan form untuk status lain hanya sebagai kenyamanan tampilan; server TETAP wajib
     menolak perubahan booking non-pending (menyembunyikan form bukan pengganti otorisasi).
     Form hanya dirender bila route bookings.update terdaftar (x-route-form, method dari definisi route). --}}
@extends('layouts.app')

@php
    $roles = $roles ?? ['Mahasiswa', 'Dosen', 'Admin Akademik', 'Kepala Laboratorium', 'Pimpinan Fakultas'];
    $isEditable = $booking->getStatus() === \App\Models\Booking::STATUS_PENDING;
@endphp

@section('title', 'Ubah Booking')
@section('inline_error_fields', 'requester_name,requester_role,room_id,participant_count,start_time,end_time,purpose')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('bookings.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar booking</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah Booking</h1>
            <p class="cs-page-description">
                Status saat ini: <x-status-badge :status="$booking->status" />.
                Booking hanya dapat diubah selama masih menunggu verifikasi; setelah disimpan, pengajuan tetap berstatus pending.
            </p>
        </header>

        @if (! $isEditable)
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Booking ini tidak dapat diubah karena statusnya bukan <strong>pending</strong>.</p>
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
                        <time datetime="{{ $booking->start_time->toIso8601String() }}">{{ $booking->start_time->locale('id')->isoFormat('dddd, D MMM YYYY HH:mm') }}</time>
                        – <time datetime="{{ $booking->end_time->toIso8601String() }}">{{ $booking->end_time->format('H:i') }}</time>
                    </dd>
                </div>
                <div>
                    <dt class="cs-legend mb-1">Keperluan</dt>
                    <dd>{{ $booking->purpose }}</dd>
                </div>
            </dl>
            <a href="{{ route('bookings.index') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar booking</a>
        @elseif (Route::has('bookings.update'))
            <x-route-form route="bookings.update" :params="$booking" class="cs-card cs-card-body space-y-6" data-cs-validate>
                @include('bookings.partials.form', ['booking' => $booking])

                <div class="cs-form-actions">
                    <a href="{{ route('bookings.index') }}" class="cs-btn cs-btn-ghost">Kembali</a>
                    <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
                </div>
            </x-route-form>
        @else
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Perubahan booking belum dapat disimpan karena layanan pembaruan booking belum tersedia di sistem.</p>
            </div>
            <a href="{{ route('bookings.index') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar booking</a>
        @endif
    </section>
@endsection
