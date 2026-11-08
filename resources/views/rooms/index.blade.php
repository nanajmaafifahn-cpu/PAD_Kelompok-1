{{-- Jelajahi Ruangan (rooms.index, semua user) — referensi 02-mhs-jelajahi-ruangan / 13-pengelola-jelajahi-ruangan.
     Variabel dari RoomController@index:
       $rooms          paginator Room 12/halaman (relasi academicUnit & facilities, bookings_count), withQueryString()
       $liveStatuses   [room_id => available|booked|occupied|maintenance] (RoomStatusService)
       $filters        ['q', 'type', 'min_capacity', 'facility'] nilai filter aktif (E4)
       $types          Room::TYPE_LABELS (kunci => label)
       $facilityNames  daftar nama fasilitas unik untuk filter
     Kelola ruang (ubah/hapus/fasilitas) ada di Ruangan & Foto (rooms.manage). --}}
@extends('layouts.app')

@section('title', 'Jelajahi Ruangan')

@php
    $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

@section('page_header')
    <x-page-header eyebrow="Ruang untuk kegiatan Anda" title="Jelajahi laboratorium." crumb="Jelajahi Ruangan"
                   subtitle="Temukan fasilitas dan kapasitas yang sesuai, lalu periksa jadwalnya.">
        <x-slot:actions>
            <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-secondary">Lihat Jadwal</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    {{-- Filter (E4): GET query string, bertahan saat paginasi --}}
    <x-filter-bar :action="route('rooms.index')" :reset="route('rooms.index')" :active="$hasFilters" :cols="5" label="Filter ruang">
        <div class="md:col-span-2">
            <label for="filter-q" class="cs-form-label">Cari ruangan</label>
            <input id="filter-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100"
                   placeholder="Nama ruangan atau kode lokasi…" class="cs-form-control">
        </div>
        <div>
            <label for="filter-type" class="cs-form-label">Jenis</label>
            <select id="filter-type" name="type" class="cs-form-control">
                <option value="">Semua jenis</option>
                @foreach ($types ?? [] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="filter-capacity" class="cs-form-label">Peserta</label>
            <input id="filter-capacity" name="min_capacity" type="number" min="1" step="1" inputmode="numeric"
                   value="{{ $filters['min_capacity'] ?? '' }}" placeholder="Jumlah" class="cs-form-control">
        </div>
        <div>
            <label for="filter-facility" class="cs-form-label">Fasilitas</label>
            <select id="filter-facility" name="facility" class="cs-form-control">
                <option value="">Semua fasilitas</option>
                @foreach ($facilityNames ?? [] as $name)
                    <option value="{{ $name }}" @selected(($filters['facility'] ?? null) === $name)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </x-filter-bar>

    <section aria-labelledby="rooms-heading">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 id="rooms-heading" class="text-ink-soft" aria-live="polite">{{ $rooms->total() }} ruangan ditemukan</h2>
            <span class="cs-badge" data-status="completed">FRI · Telkom University</span>
        </div>

        @if ($rooms->isEmpty())
            <div class="cs-card">
                @if ($hasFilters)
                    <x-empty-state title="Tidak ada ruangan yang cocok" text="Ubah kata kunci atau kurangi filter untuk melihat lebih banyak ruangan.">
                        <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-secondary">Reset filter</a>
                    </x-empty-state>
                @else
                    <x-empty-state title="Belum ada ruangan" text="Data ruang menjadi dasar jadwal, peminjaman, dan perhitungan utilisasi.">
                        @can('create', \App\Models\Room::class)
                            <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">Tambah Ruang</a>
                        @endcan
                    </x-empty-state>
                @endif
            </div>
        @else
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($rooms as $room)
                    <x-room-card :room="$room" :live-status="$liveStatuses[$room->id] ?? 'available'" />
                @endforeach
            </div>
        @endif

        <div class="mt-6">{{ $rooms->links() }}</div>
    </section>
@endsection
