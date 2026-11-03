{{-- FE-03 / FE-05 · Form Pengajuan Booking (Submit)
     Variabel dari BookingController@create:
       $rooms  collection Room
       $roles  (opsional) array label role stakeholder; bila tidak dikirim memakai daftar bawaan di bawah.
     Dikirim ke route bookings.store. Field form ada di bookings/partials/form.blade.php
     (dipakai juga oleh bookings/edit). Galat BookingService dikirim sebagai session('error')
     dan tampil di alert atas (layout). --}}
@extends('layouts.app')

@php
    $roles = $roles ?? ['Mahasiswa', 'Dosen', 'Admin Akademik', 'Kepala Laboratorium', 'Pimpinan Fakultas'];
@endphp

@section('title', 'Ajukan Booking')
@section('inline_error_fields', 'requester_name,requester_role,room_id,participant_count,start_time,end_time,purpose')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('bookings.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar booking</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ajukan Booking Ruang</h1>
            <p class="cs-page-description">
                Pengajuan akan berstatus <x-status-badge status="pending" /> sampai diverifikasi Kepala Laboratorium / Admin Akademik.
                Kolom bertanda <span class="cs-form-required" aria-hidden="true">*</span> wajib diisi.
            </p>
        </header>

        <form method="POST" action="{{ route('bookings.store') }}" class="cs-card cs-card-body space-y-6" data-cs-validate>
            @csrf

            @include('bookings.partials.form')

            <div class="cs-form-actions">
                <a href="{{ route('bookings.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </section>
@endsection
