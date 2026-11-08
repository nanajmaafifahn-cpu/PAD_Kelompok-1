{{-- C3 · Ubah Jadwal (schedules.edit -> PUT schedules.update, pengelola/admin).
     Variabel: $schedule, $rooms. Bentrok ditolak server (ScheduleService, D2). --}}
@extends('layouts.app')

@section('title', 'Ubah Jadwal')
@section('inline_error_fields', 'room_id,title,start_time,end_time')

@section('page_header')
    <x-page-header eyebrow="Kalender laboratorium" title="Ubah jadwal." crumb="Jadwal Ruangan"
                   subtitle="{{ $schedule->title }} · {{ $schedule->room?->name }}">
        <x-slot:actions>
            <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-secondary">Kembali ke jadwal</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form ubah jadwal">
        <p class="mb-4 text-sm text-ink-soft">Status saat ini: <x-status-badge :status="$schedule->status" domain="schedule" /></p>

        <x-route-form route="schedules.update" :params="$schedule" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('schedules.partials.form', ['schedule' => $schedule])
            <div class="cs-form-actions">
                <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
            </div>
        </x-route-form>
    </section>
@endsection
