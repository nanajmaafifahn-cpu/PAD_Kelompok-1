{{-- Detail Ruangan (rooms.show, semua user login) — referensi 03-mhs-detail-ruangan.
     Variabel dari RoomController@show:
       $room            Room (relasi academicUnit & facilities)
       $liveStatus      available|booked|occupied|maintenance (label: Room::LIVE_STATUS_LABELS)
       $computerCount   int, jumlah komputer (0 = nonkomputer)
       $upcomingEvents  collection kegiatan terdekat (CalendarService): type, title, room_*, start, end, status
     Tombol "Ajukan Peminjaman" -> bookings.create?room_id={id} (ruang langsung terpilih).
     "Jenis akses" di referensi tidak ditampilkan karena datanya belum ada; diganti "Lokasi". --}}
@extends('layouts.app')

@use('App\Models\Room')
@use('App\Services\CalendarService')
@use('App\Support\DateFormat')

@section('title', 'Ruangan ' . $room->name)

@section('page_header')
    <x-page-header :eyebrow="$room->typeLabel()" title="Ruangan {{ $room->name }}" crumb="Jelajahi Ruangan"
                   subtitle="{{ $room->code }} · {{ $room->academicUnit?->name }}">
        <x-slot:actions>
            @can('create', \App\Models\Booking::class)
                @if ($room->getStatus() !== 'maintenance')
                    <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
                @endif
            @endcan
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="cs-card cs-card-body space-y-6 lg:col-span-2" aria-labelledby="about-heading">
            <div class="cs-room-card-media overflow-hidden rounded-xl">
                @if ($room->photoUrl())
                    <img src="{{ $room->photoUrl() }}" alt="Foto {{ $room->name }}">
                @else
                    <div>
                        <x-icon name="image" class="mx-auto h-8 w-8" />
                        <span class="cs-room-card-code">{{ $room->name }}</span>
                        <span class="text-xs">Foto ruangan belum ditambahkan oleh pengelola.</span>
                    </div>
                @endif
            </div>

            <div>
                <h2 id="about-heading" class="text-xl font-extrabold text-ink">Tentang ruangan</h2>
                <p class="mt-2 text-ink-body">{{ $room->description ?: 'Rincian ruangan akan dilengkapi oleh pengelola.' }}</p>
            </div>

            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="cs-caption">Kapasitas</dt>
                    <dd class="text-lg font-bold text-ink">{{ $room->getCapacity() }} orang</dd>
                </div>
                <div>
                    <dt class="cs-caption">Komputer</dt>
                    <dd class="text-lg font-bold text-ink">{{ $computerCount > 0 ? $computerCount : 'Nonkomputer' }}</dd>
                </div>
                <div>
                    <dt class="cs-caption">Lokasi</dt>
                    <dd class="text-lg font-bold text-ink">{{ $room->building }} · Lantai {{ $room->floor ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="cs-caption">Status operasional</dt>
                    <dd class="mt-1"><span class="cs-live-dot text-sm" data-status="{{ $liveStatus }}">{{ Room::LIVE_STATUS_LABELS[$liveStatus] }}</span></dd>
                </div>
            </dl>

            <div>
                <h3 class="text-lg font-extrabold text-ink">Fasilitas tercatat</h3>
                @if ($room->facilities->isEmpty())
                    <p class="mt-2 text-sm text-ink-muted">Belum ada fasilitas tercatat.</p>
                @else
                    <ul class="mt-3 flex flex-wrap gap-2" aria-label="Fasilitas {{ $room->name }}">
                        @foreach ($room->facilities as $facility)
                            <li class="cs-badge" data-status="{{ $facility->getStatus() === 'available' ? 'completed' : $facility->getStatus() }}">
                                {{ $facility->name }} · {{ $facility->getQuantity() }}
                                @if ($facility->getStatus() !== 'available')
                                    ({{ \App\Models\Facility::STATUS_LABELS[$facility->getStatus()] }})
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <aside class="cs-card cs-card-body space-y-4 self-start" aria-labelledby="upcoming-heading">
            <div>
                <h2 id="upcoming-heading" class="text-xl font-extrabold text-ink">Jadwal mendatang</h2>
                <p class="mt-1 text-sm text-ink-soft">Hanya jadwal kuliah dan peminjaman yang sudah disetujui.</p>
            </div>

            @forelse ($upcomingEvents as $event)
                <div class="border-t border-border pt-3">
                    <p class="font-semibold text-ink">{{ $event['title'] }}</p>
                    <p class="text-sm text-ink-soft">{{ DateFormat::range($event['start'], $event['end']) }}</p>
                    <span class="cs-badge mt-1" data-status="{{ $event['type'] === CalendarService::TYPE_SCHEDULE ? 'scheduled' : 'completed' }}">
                        {{ CalendarService::TYPE_LABELS[$event['type']] }}
                    </span>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-ink-muted">Belum ada jadwal mendatang.</p>
            @endforelse

            <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-secondary w-full">Buka Kalender</a>
        </aside>
    </div>
@endsection
