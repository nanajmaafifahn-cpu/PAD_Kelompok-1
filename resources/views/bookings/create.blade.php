{{-- Form Ajukan Peminjaman (bookings.create -> POST bookings.store).
     Variabel dari BookingController@create:
       $rooms           collection Room (option membawa data-capacity untuk peringatan kapasitas di klien)
       $selectedRoomId  ?int, dari ?room_id= (tombol "Ajukan Peminjaman" di detail ruang)
     Field ada di bookings/partials/form.blade.php (dipakai juga bookings/edit). Pemohon & peran baca-saja dari akun.
     Galat server (kapasitas, bentrok, waktu lampau, maintenance) tampil di bawah field (x-field-error). --}}
@extends('layouts.app')

@section('title', 'Ajukan Peminjaman')
@section('inline_error_fields', 'room_id,participant_count,start_time,end_time,purpose')

@section('page_header')
    <x-page-header eyebrow="Pengajuan baru" title="Ajukan peminjaman." crumb="Ajukan Peminjaman"
                   subtitle="Pengajuan berstatus Menunggu persetujuan sampai diverifikasi pengelola ruang. Kolom bertanda * wajib diisi.">
        <x-slot:actions>
            <a href="{{ route('bookings.mine') }}" class="cs-btn cs-btn-secondary">Peminjaman Saya</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form pengajuan peminjaman">
        <form method="POST" action="{{ route('bookings.store') }}" class="cs-card cs-card-body space-y-6" data-cs-validate>
            @csrf

            @include('bookings.partials.form')

            <div class="cs-form-actions">
                <a href="{{ route('bookings.mine') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Kirim Pengajuan</button>
            </div>
        </form>
    </section>
@endsection
