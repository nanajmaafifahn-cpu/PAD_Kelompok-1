{{-- C3 · Ubah Jadwal (schedules.edit -> PUT schedules.update, pengelola/admin).
     Variabel: $schedule, $rooms. Bentrok ditolak server (ScheduleService, D2). --}}
@extends('layouts.app')

@section('title', 'Ubah Jadwal')
@section('inline_error_fields', 'room_id,title,start_time,end_time')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('schedules.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke jadwal</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah Jadwal</h1>
            <p class="cs-page-description">Status saat ini: <x-status-badge :status="$schedule->status" /></p>
        </header>

        <x-route-form route="schedules.update" :params="$schedule" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('schedules.partials.form', ['schedule' => $schedule])
            <div class="cs-form-actions">
                <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
            </div>
        </x-route-form>
    </section>
@endsection
