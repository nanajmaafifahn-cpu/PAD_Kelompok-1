{{-- Ruangan & Foto (rooms.manage, pengelola/admin) — referensi 10-pengelola-kelola-ruangan.
     Variabel dari RoomController@manage:
       $rooms         paginator Room 15/halaman (relasi academicUnit & facilities, bookings_count), urut kode
       $liveStatuses  [room_id => available|booked|occupied|maintenance] (RoomStatusService)
     Aksi: rooms.create, rooms.edit, rooms.facilities.index, rooms.destroy (dialog konfirmasi; server menolak bila
     masih ada booking, tombol Hapus disembunyikan bila bookings_count > 0). --}}
@extends('layouts.app')

@section('title', 'Ruangan & Foto')

@section('page_header')
    <x-page-header eyebrow="Data master" title="Ruangan & Foto." crumb="Ruangan & Foto"
                   subtitle="Kelola data ruang yang digunakan pada peminjaman dan laporan.">
        <x-slot:actions>
            <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">Tambah Ruang</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    @if ($rooms->isEmpty())
        <div class="cs-card">
            <x-empty-state title="Belum ada ruang" text="Tambahkan ruang pertama beserta fotonya.">
                <a href="{{ route('rooms.create') }}" class="cs-btn cs-btn-primary">Tambah Ruang</a>
            </x-empty-state>
        </div>
    @else
        <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-label="Data master ruang" tabindex="0">
            <table class="cs-table cs-table-stack">
                <thead>
                    <tr>
                        <th scope="col">Ruangan</th>
                        <th scope="col">Informasi</th>
                        <th scope="col">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr>
                            <th scope="row">
                                <div class="flex items-center gap-3">
                                    @if ($room->photoUrl())
                                        <img src="{{ $room->photoUrl() }}" alt="" class="h-12 w-16 shrink-0 rounded-lg object-cover">
                                    @else
                                        <span class="cs-icon-chip h-12 w-16 shrink-0" aria-hidden="true"><x-icon name="image" /></span>
                                    @endif
                                    <span>
                                        <span class="block font-bold text-ink">{{ $room->name }}</span>
                                        <span class="cs-code">{{ $room->code }}</span>
                                    </span>
                                </div>
                            </th>
                            <td data-label="Informasi">
                                <span class="block text-ink">{{ $room->typeLabel() }} · {{ $room->getCapacity() }} orang · {{ $room->academicUnit?->code }}</span>
                                <span class="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                                    <x-status-badge :status="$liveStatuses[$room->id] ?? 'available'" domain="room" />
                                    <span>{{ $room->photoUrl() ? 'Ada foto' : 'Belum ada foto' }}</span>
                                    <span>· {{ $room->facilities->count() }} fasilitas · {{ $room->bookings_count }} peminjaman</span>
                                </span>
                            </td>
                            <td data-label="Tindakan">
                                <div class="cs-actions">
                                    <a href="{{ route('rooms.edit', $room) }}" class="cs-btn cs-btn-secondary cs-btn-sm">Edit<span class="sr-only"> {{ $room->name }}</span></a>
                                    <a href="{{ route('rooms.facilities.index', $room) }}" class="cs-btn cs-btn-ghost cs-btn-sm">Fasilitas<span class="sr-only"> {{ $room->name }}</span></a>
                                    @if ($room->bookings_count === 0)
                                        <x-route-form route="rooms.destroy" :params="$room"
                                                      confirm="Hapus ruang {{ $room->name }} ({{ $room->code }})? Jadwal dan fasilitas ruang ini ikut terhapus.">
                                            <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Hapus<span class="sr-only"> {{ $room->name }}</span></button>
                                        </x-route-form>
                                    @else
                                        <span class="text-xs text-ink-muted" title="Ruang yang punya riwayat peminjaman tidak dapat dihapus; ubah status menjadi Perawatan.">Tidak dapat dihapus</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $rooms->links() }}</div>
    @endif
@endsection
