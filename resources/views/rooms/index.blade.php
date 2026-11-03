{{-- FE-02 / FE-04 · Daftar Ruang/Laboratorium & Fasilitas
     Variabel dari RoomController@index:
       $rooms  paginator Room (dengan relasi academicUnit & facilities)
     Data ditampilkan lewat getter/summary milik Model (getEntitySummary, getCapacity, getStatus).
     FE-06: kolom Aksi (Ubah/Hapus) hanya muncul bila route rooms.edit / rooms.destroy terdaftar
     (saat ini BLOCKED, usulan BE-05). Bila Controller mengirim bookings_count
     (Room::withCount('bookings')), ruang yang masih punya booking tidak diberi tombol Hapus
     karena FK bookings.room_id restrictOnDelete; tanpa data itu, penolakan tetap ditangani server. --}}
@extends('layouts.app')

@section('title', 'Ruang & Laboratorium')

@php
    $canManageRooms = Route::has('rooms.edit') || Route::has('rooms.destroy');
@endphp

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Ruang &amp; Laboratorium</h1>
            <p class="cs-page-description">Master data ruang kelas dan laboratorium beserta kapasitas, unit akademik, dan fasilitasnya.</p>
        </div>
        <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">
            <span aria-hidden="true">+</span> Tambah Ruang
        </a>
    </header>

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
                                <span class="block font-medium text-ink">{{ $room->name }}</span>
                                <span class="block text-xs capitalize text-ink-soft">{{ $room->type }}</span>
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
                            <td><x-status-badge :status="$room->getStatus()" /></td>
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
                                                <x-status-badge :status="$facility->getStatus()" class="ml-0.5 px-1.5 text-[10px]" />
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
                                    <p class="cs-empty-state-title">Belum ada ruang terdaftar.</p>
                                    <p class="mt-1">Data ruang menjadi dasar jadwal, booking, dan perhitungan utilisasi.
                                        <a href="{{ route('rooms.create') }}" class="cs-link">Tambah ruang pertama</a>.</p>
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
