{{-- FE-02 / FE-04 · Daftar Ruang/Laboratorium & Fasilitas
     Variabel dari RoomController@index:
       $rooms          paginator Room (relasi academicUnit & facilities, bookings_count), withQueryString()
       $liveStatuses   [room_id => available|booked|occupied|maintenance] (RoomStatusService)
       $filters        ['q', 'type', 'min_capacity', 'facility'] nilai filter aktif (E4)
       $types          Room::TYPE_LABELS (kunci => label)
       $facilityNames  daftar nama fasilitas unik untuk filter
     Data ditampilkan lewat getter/summary milik Model (getEntitySummary, getCapacity, getStatus).
     FE-06: kolom Aksi (Ubah/Hapus) hanya muncul bila route rooms.edit / rooms.destroy terdaftar
     (aktif sejak Fase 3, BE-05). Controller mengirim bookings_count
     (Room::withCount('bookings')), ruang yang masih punya booking tidak diberi tombol Hapus
     karena FK bookings.room_id restrictOnDelete; tanpa data itu, penolakan tetap ditangani server. --}}
@extends('layouts.app')

@section('title', 'Ruang & Laboratorium')

@php
    $canManageRooms = (Route::has('rooms.edit') || Route::has('rooms.destroy'))
        && auth()->user()->can('create', \App\Models\Room::class);
    $hasFilters = collect($filters ?? [])->filter(fn ($value) => filled($value))->isNotEmpty();
@endphp

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Ruang &amp; Laboratorium</h1>
            <p class="cs-page-description">Master data ruang kelas dan laboratorium beserta kapasitas, unit akademik, dan fasilitasnya.</p>
        </div>
        @can('create', \App\Models\Room::class)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('rooms.qr.index') }}" class="cs-btn cs-btn-secondary">Cetak QR Ruangan</a>
                <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">
                    <span aria-hidden="true">+</span> Tambah Ruang
                </a>
            </div>
        @endcan
    </header>

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
        <h2 id="rooms-heading" class="sr-only">Daftar ruang</h2>

        <div class="cs-table-wrap" role="region" aria-labelledby="rooms-heading" tabindex="0">
            <table class="cs-table">
                <caption class="sr-only">
                    Daftar ruang dan laboratorium, halaman {{ $rooms->currentPage() }} dari {{ $rooms->lastPage() }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Ruang</th>
                        <th scope="col">Unit Akademik</th>
                        <th scope="col">Lokasi</th>
                        <th scope="col" class="text-right">Kapasitas</th>
                        <th scope="col">Status</th>
                        <th scope="col">Fasilitas</th>
                        @if ($canManageRooms)
                            <th scope="col">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td class="cs-cell-nowrap"><span class="cs-code">{{ $room->code }}</span></td>
                            <th scope="row">
                                @if ($room->photoUrl())
                                    <img src="{{ $room->photoUrl() }}" alt="" class="mb-1 h-12 w-20 rounded object-cover">
                                @endif
                                <span class="block font-medium text-ink">{{ $room->name }}</span>
                                <span class="block text-xs text-ink-soft">{{ $room->typeLabel() }}</span>
                                <span class="block text-xs text-ink-muted">{{ $room->getEntitySummary() }}</span>
                            </th>
                            <td>{{ $room->academicUnit?->name ?? '—' }}</td>
                            <td>
                                {{ $room->building }}
                                <span class="block text-xs text-ink-muted">Lantai {{ $room->floor ?? '-' }}</span>
                            </td>
                            <td class="cs-cell-num">
                                {{ $room->getCapacity() }} <span class="text-xs text-ink-muted">orang</span>
                            </td>
                            {{-- Status live dari controller (RoomStatusService), tanpa query di view --}}
                            <td><x-status-badge :status="$liveStatuses[$room->id] ?? 'available'" /></td>
                            <td>
                                @if ($room->facilities->isEmpty())
                                    <span class="text-xs text-ink-muted">Belum ada fasilitas</span>
                                @else
                                    <ul class="flex max-w-xs flex-wrap gap-1.5" aria-label="Fasilitas {{ $room->name }}">
                                        @foreach ($room->facilities as $facility)
                                            <li class="inline-flex items-center gap-1 rounded-md border border-border bg-background px-2 py-0.5 text-xs text-ink-body"
                                                title="{{ $facility->getEntitySummary() }}">
                                                {{ $facility->name }}
                                                <span class="tabular-nums text-ink-muted">×{{ $facility->getQuantity() }}</span>
                                                <x-status-badge :status="$facility->getStatus()" domain="facility" class="ml-0.5 px-1.5 text-[10px]" />
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            @if ($canManageRooms)
                                <td>
                                    <div class="cs-actions">
                                        @if (Route::has('rooms.edit'))
                                            <a href="{{ route('rooms.edit', $room) }}" class="cs-btn cs-btn-secondary cs-btn-sm">
                                                Ubah<span class="sr-only"> ruang {{ $room->code }}</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('rooms.facilities.index', $room) }}" class="cs-btn cs-btn-ghost cs-btn-sm">
                                            Fasilitas<span class="sr-only"> ruang {{ $room->code }}</span>
                                        </a>
                                        @if (isset($room->bookings_count) && $room->bookings_count > 0)
                                            <span class="cs-caption">Tidak dapat dihapus: masih memiliki {{ $room->bookings_count }} booking</span>
                                        @else
                                            <x-route-form route="rooms.destroy" :params="$room"
                                                          confirm="Hapus ruang {{ $room->code }}? Jadwal dan fasilitas ruang ini ikut terhapus dan tindakan ini tidak dapat dibatalkan.">
                                                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">
                                                    Hapus<span class="sr-only"> ruang {{ $room->code }}</span>
                                                </button>
                                            </x-route-form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageRooms ? 8 : 7 }}">
                                <div class="cs-empty-state">
                                    @if ($hasFilters)
                                        <p class="cs-empty-state-title">Tidak ada ruang yang cocok dengan filter.</p>
                                        <p class="mt-1"><a href="{{ route('rooms.index') }}" class="cs-link">Reset filter</a></p>
                                    @else
                                        <p class="cs-empty-state-title">Belum ada ruang terdaftar.</p>
                                        <p class="mt-1">Data ruang menjadi dasar jadwal, booking, dan perhitungan utilisasi.
                                            @can('create', \App\Models\Room::class)
                                                <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang pertama</a>.
                                            @endcan
                                        </p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rooms->links() }}
        </div>
    </section>
@endsection
