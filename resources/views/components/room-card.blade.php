{{-- Kartu ruang (Jelajahi Ruangan): foto atau placeholder (ikon + kode + "Foto belum ditambahkan"),
     chip tipe di pojok kiri atas, nama, titik status live, kode lokasi, kapasitas, jumlah komputer / "Nonkomputer",
     tombol "Lihat Ruangan" lebar penuh.
     Props: $room (relasi facilities sudah dimuat untuk computerCount), $liveStatus (dari controller, bukan query di view).
     Contoh: <x-room-card :room="$room" :live-status="$liveStatuses[$room->id]" /> --}}
@props(['room', 'liveStatus' => 'available'])

@php
    $computers = $room->computerCount();
@endphp

<article {{ $attributes->class(['cs-room-card']) }} aria-labelledby="room-{{ $room->id }}-name" data-room-code="{{ $room->code }}">
    <div class="cs-room-card-media">
        <span class="cs-room-card-chip">{{ $room->typeLabel() }}</span>
        @if ($room->photoUrl())
            <img src="{{ $room->photoUrl() }}" alt="Foto {{ $room->name }}" loading="lazy">
        @else
            <div>
                <x-icon name="image" class="mx-auto h-7 w-7" />
                <span class="cs-room-card-code">{{ $room->name }}</span>
                <span class="text-xs">Foto belum ditambahkan</span>
            </div>
        @endif
    </div>

    <div class="cs-room-card-body">
        <div class="flex items-start justify-between gap-3">
            <h3 id="room-{{ $room->id }}-name" class="cs-room-card-name">{{ $room->name }}</h3>
            <span class="cs-live-dot mt-2" data-status="{{ $liveStatus }}">{{ \App\Models\Room::LIVE_STATUS_LABELS[$liveStatus] ?? $liveStatus }}</span>
        </div>
        <p class="text-ink-soft">{{ $room->code }}</p>

        <p class="cs-room-card-meta">
            <span><x-icon name="users" /> {{ $room->getCapacity() }} orang</span>
            <span><x-icon name="monitor" /> {{ $computers > 0 ? $computers . ' komputer' : 'Nonkomputer' }}</span>
        </p>

        <a href="{{ route('rooms.show', $room) }}" class="cs-btn cs-btn-secondary mt-4 w-full">
            Lihat Ruangan<span class="sr-only"> {{ $room->name }}</span>
        </a>
    </div>
</article>
