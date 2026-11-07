{{-- FE-03 / FE-05 · Jadwal Ruang (dasar perhitungan Scheduled Utilization)
     Variabel dari ScheduleController@index:
       $schedules  paginator Schedule (dengan relasi room), urut waktu mulai
       $rooms      collection Room untuk form tambah jadwal (AKTIF sejak Fase 3, BE-03).
     Form tambah & kolom Aksi (Ubah / Batalkan / Hapus) hanya untuk pengelola/admin (@can SchedulePolicy).
     Nama field form = key StoreScheduleRequest: room_id, title, start_time, end_time (status otomatis "scheduled").
     Field ada di schedules/partials/form.blade.php (dipakai juga oleh schedules/edit).
     Di bawah 768px tabel tampil sebagai kartu (.cs-table-stack). --}}
@extends('layouts.app')

@section('title', 'Jadwal Ruang')
@section('inline_error_fields', 'room_id,title,start_time,end_time')

@php
    $canManage = auth()->user()->can('create', \App\Models\Schedule::class);
@endphp

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Jadwal Ruang</h1>
            <p class="cs-page-description">Jadwal resmi perkuliahan/praktikum yang menjadi acuan <em>scheduled utilization</em>.</p>
        </div>
    </header>

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
                                <th scope="col">Aksi</th>
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
                                    <td data-label="Aksi">
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
@endsection
