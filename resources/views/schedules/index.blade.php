{{-- Jadwal Ruangan (schedules.index) — referensi 04-mhs-jadwal / 12-pengelola-jadwal.
     Variabel dari ScheduleController@index:
       $week        CalendarService::week(): start, end, total, days[7] (date, is_today, events)
       $eventTypes  CalendarService::TYPE_LABELS (legenda: Jadwal kuliah / Peminjaman disetujui)
       $schedules   paginator Schedule (relasi room) — tabel "Kelola jadwal" khusus pengelola/admin
       $rooms       collection Room untuk form tambah jadwal (pengelola/admin)
     Kalender hanya minggu berjalan: filter tanggal/ruang & Sebelumnya/Berikutnya belum didukung controller.
     Form tambah & kolom Aksi (Ubah / Batalkan / Hapus) hanya untuk pengelola/admin (@can SchedulePolicy).
     Nama field form = key StoreScheduleRequest: room_id, title, start_time, end_time (status otomatis "scheduled"). --}}
@extends('layouts.app')

@use('App\Services\CalendarService')
@use('App\Support\DateFormat')

@section('title', 'Jadwal Ruangan')
@section('inline_error_fields', 'room_id,title,start_time,end_time')

@php
    $canManage = auth()->user()->can('create', \App\Models\Schedule::class);
@endphp

@section('page_header')
    <x-page-header eyebrow="Kalender laboratorium" title="Jadwal ruangan." crumb="Jadwal Ruangan"
                   subtitle="Jadwal akademik dan peminjaman yang telah disetujui.">
        @can('create', \App\Models\Booking::class)
            <x-slot:actions>
                <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
            </x-slot:actions>
        @endcan
    </x-page-header>
@endsection

@section('content')
    <section aria-labelledby="week-heading" class="mb-10">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <h2 id="week-heading" class="text-2xl font-extrabold text-ink">{{ DateFormat::weekRange($week['start'], $week['end']) }}</h2>
            <p class="text-sm text-ink-soft">WIB · {{ $week['total'] }} kegiatan</p>
        </div>

        <ul class="mb-4 flex flex-wrap gap-4 text-sm" aria-label="Keterangan warna">
            @foreach ($eventTypes as $type => $label)
                <li class="cs-calendar-legend" data-type="{{ $type }}">{{ $label }}</li>
            @endforeach
        </ul>

        <ol class="cs-calendar">
            @foreach ($week['days'] as $day)
                <li class="cs-calendar-day" @if ($day['is_today']) data-today="true" aria-current="date" @endif>
                    <p class="cs-calendar-dayname">{{ $day['date']->locale('id')->isoFormat('dddd') }}</p>
                    <p class="cs-calendar-date">
                        <time datetime="{{ $day['date']->toDateString() }}">{{ $day['date']->format('d') }}</time>
                        <span class="text-sm font-semibold text-ink-muted lg:hidden">{{ $day['date']->locale('id')->isoFormat('MMM YYYY') }}</span>
                    </p>

                    @forelse ($day['events'] as $event)
                        <div class="cs-calendar-event" data-type="{{ $event['type'] }}">
                            <p class="text-xs font-bold tabular-nums">{{ DateFormat::time($event['start']) }}–{{ DateFormat::time($event['end']) }}</p>
                            <p class="text-xs">{{ $event['room_name'] }}</p>
                            <p class="mt-0.5 text-sm font-semibold leading-snug">{{ $event['title'] }}</p>
                            <span class="sr-only">({{ $eventTypes[$event['type']] }})</span>
                        </div>
                    @empty
                        <p class="cs-calendar-empty">Belum ada jadwal</p>
                    @endforelse
                </li>
            @endforeach
        </ol>

        <p class="cs-alert cs-alert-info mt-6 mb-0">
            Slot kosong pada kalender menunjukkan belum ada jadwal kuliah atau peminjaman yang disetujui.
            Pengajuan baru tetap melalui pemeriksaan pengelola.
        </p>
    </section>

    @if ($canManage)
    <h2 class="mb-4 text-2xl font-extrabold text-ink">Kelola jadwal</h2>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        {{-- Daftar jadwal --}}
        <section aria-labelledby="schedules-heading" class="min-w-0 flex-1">
            <h2 id="schedules-heading" class="sr-only">Daftar jadwal</h2>

            <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-labelledby="schedules-heading" tabindex="0">
                <table class="cs-table cs-table-stack">
                    <caption class="sr-only">
                        Daftar jadwal ruang urut waktu mulai, halaman {{ $schedules->currentPage() }} dari {{ $schedules->lastPage() }}
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Kegiatan</th>
                            <th scope="col">Ruang</th>
                            <th scope="col">Hari &amp; Tanggal</th>
                            <th scope="col">Waktu</th>
                            <th scope="col">Status</th>
                            @if ($canManage)
                                <th scope="col">Tindakan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $schedule)
                            <tr>
                                <th scope="row"><span class="font-medium text-ink">{{ $schedule->title }}</span></th>
                                <td data-label="Ruang">
                                    <span class="block text-ink">{{ $schedule->room?->name }}</span>
                                    <span class="cs-code">{{ $schedule->room?->code }}</span>
                                </td>
                                <td data-label="Hari & Tanggal" class="cs-cell-nowrap">
                                    <time datetime="{{ $schedule->start_time->toDateString() }}">
                                        {{ $schedule->start_time->locale('id')->isoFormat('dddd, D MMMM YYYY') }}
                                    </time>
                                </td>
                                <td data-label="Waktu" class="cs-cell-nowrap tabular-nums">
                                    <time datetime="{{ $schedule->start_time->toIso8601String() }}">{{ $schedule->start_time->format('H:i') }}</time>
                                    –
                                    <time datetime="{{ $schedule->end_time->toIso8601String() }}">{{ $schedule->end_time->format('H:i') }}</time>
                                    @unless ($schedule->start_time->isSameDay($schedule->end_time))
                                        <span class="block text-xs text-ink-muted">s/d {{ $schedule->end_time->locale('id')->isoFormat('D MMM YYYY') }}</span>
                                    @endunless
                                </td>
                                <td data-label="Status"><x-status-badge :status="$schedule->status" /></td>
                                @if ($canManage)
                                    <td data-label="Tindakan">
                                        <div class="cs-actions">
                                            <a href="{{ route('schedules.edit', $schedule) }}" class="cs-btn cs-btn-secondary cs-btn-sm">
                                                Ubah<span class="sr-only"> {{ $schedule->title }}</span>
                                            </a>
                                            @if ($schedule->status !== \App\Services\ScheduleService::STATUS_CANCELLED)
                                                <x-route-form route="schedules.cancel" :params="$schedule" confirm="Batalkan jadwal {{ $schedule->title }}? Jam terjadwal ruang akan berkurang.">
                                                    <button type="submit" class="cs-btn cs-btn-ghost cs-btn-sm">Batalkan</button>
                                                </x-route-form>
                                            @endif
                                            <x-route-form route="schedules.destroy" :params="$schedule" confirm="Hapus jadwal {{ $schedule->title }}? Tindakan ini tidak dapat dibatalkan.">
                                                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Hapus</button>
                                            </x-route-form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 6 : 5 }}">
                                    <div class="cs-empty-state">
                                        <p class="cs-empty-state-title">Belum ada jadwal.</p>
                                        <p class="mt-1">Jadwal resmi perkuliahan dan praktikum akan muncul di sini dan dihitung sebagai jam terjadwal ruang.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
        </section>

        {{-- Sidebar: form tambah jadwal (pengelola/admin) --}}
        @if ($canManage)
            <aside aria-labelledby="schedule-form-heading" class="w-full shrink-0 lg:w-80">
                <form method="POST" action="{{ route('schedules.store') }}" class="cs-card cs-card-body space-y-4" data-cs-validate>
                    @csrf
                    <h2 id="schedule-form-heading" class="cs-section-title">Tambah Jadwal</h2>

                    @include('schedules.partials.form', ['schedule' => null])

                    <button type="submit" class="cs-btn cs-btn-primary w-full">Simpan Jadwal</button>
                </form>
            </aside>
        @endif
    </div>
    @endif
@endsection
