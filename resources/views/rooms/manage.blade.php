{{-- Ruangan & Foto (rooms.manage, pengelola/admin) — data master ruang.
     Variabel dari RoomController@manage:
       $rooms         paginator Room (relasi academicUnit & facilities, bookings_count), urut kode, 15 per halaman
       $liveStatuses  [room_id => available|booked|occupied|maintenance] (RoomStatusService)
     Aksi: rooms.create, rooms.edit, rooms.facilities.index, rooms.destroy (ditolak server bila masih ada booking).
     Tampilan final dirapikan di PROMPT 2 Tahap 4. --}}
@extends('layouts.app')

@section('title', 'Ruangan & Foto')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Ruangan &amp; Foto</h1>
            <p class="cs-page-description">Kelola data ruang yang digunakan pada peminjaman dan laporan.</p>
        </div>
        <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary"><span aria-hidden="true">+</span> Tambah Ruang</a>
    </header>

    <div class="cs-table-wrap" role="region" aria-label="Data master ruang" tabindex="0">
        <table class="cs-table">
            <thead>
                <tr>
                    <th scope="col">Ruangan</th>
                    <th scope="col">Informasi</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rooms as $room)
                    <tr>
                        <th scope="row">
                            @if ($room->photoUrl())
                                <img src="{{ $room->photoUrl() }}" alt="" class="mb-1 h-12 w-20 rounded object-cover">
                            @endif
                            <span class="block font-medium text-ink">{{ $room->name }}</span>
                            <span class="cs-code">{{ $room->code }}</span>
                        </th>
                        <td>
                            <span class="block">{{ $room->typeLabel() }} · {{ $room->getCapacity() }} orang</span>
                            <span class="block text-xs text-ink-muted">
                                <x-status-badge :status="$liveStatuses[$room->id] ?? 'available'" />
                                · {{ $room->photoUrl() ? 'Ada foto' : 'Belum ada foto' }}
                                · {{ $room->bookings_count }} booking
                            </span>
                        </td>
                        <td>
                            <div class="cs-actions">
                                <a href="{{ route('rooms.edit', $room) }}" class="cs-btn cs-btn-secondary cs-btn-sm">Edit<span class="sr-only"> {{ $room->code }}</span></a>
                                <a href="{{ route('rooms.facilities.index', $room) }}" class="cs-btn cs-btn-ghost cs-btn-sm">Fasilitas<span class="sr-only"> {{ $room->code }}</span></a>
                                @if ($room->bookings_count === 0)
                                    <x-route-form route="rooms.destroy" :params="$room"
                                                  confirm="Hapus ruang {{ $room->code }}? Jadwal dan fasilitas ruang ini ikut terhapus.">
                                        <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Hapus<span class="sr-only"> {{ $room->code }}</span></button>
                                    </x-route-form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><div class="cs-empty-state"><p class="cs-empty-state-title">Belum ada ruang.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $rooms->links() }}</div>
@endsection
