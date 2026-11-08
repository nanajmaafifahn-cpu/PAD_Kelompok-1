{{-- Detail Ruangan (rooms.show, semua user login).
     Variabel dari RoomController@show:
       $room            Room (relasi academicUnit & facilities)
       $liveStatus      available|booked|occupied|maintenance (label: Room::LIVE_STATUS_LABELS)
       $computerCount   int, jumlah komputer (0 = nonkomputer)
       $upcomingEvents  collection kegiatan terdekat (CalendarService): type, title, room_*, start, end, status
     Tombol "Ajukan Peminjaman" -> bookings.create?room_id={id} (ruang langsung terpilih).
     Tampilan final dirapikan di PROMPT 2 Tahap 3. --}}
@extends('layouts.app')

@section('title', 'Ruangan ' . $room->name)

@section('content')
    <header class="cs-page-header">
        <div>
            <a href="{{ route('rooms.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar ruang</a>
            <p class="cs-caption mt-2">{{ $room->typeLabel() }}</p>
            <h1 class="cs-page-title">Ruangan {{ $room->name }}</h1>
            <p class="cs-page-description">{{ $room->code }} · {{ $room->academicUnit?->name }}</p>
        </div>
        @can('create', \App\Models\Booking::class)
            @if ($room->getStatus() !== 'maintenance')
                <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
            @endif
        @endcan
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="cs-card cs-card-body space-y-4 lg:col-span-2" aria-labelledby="about-heading">
            @if ($room->photoUrl())
                <img src="{{ $room->photoUrl() }}" alt="Foto {{ $room->name }}" class="w-full rounded object-cover">
            @else
                <p class="cs-empty-state">Foto ruangan belum ditambahkan oleh pengelola.</p>
            @endif

            <h2 id="about-heading" class="cs-section-title">Tentang ruangan</h2>
            <p>{{ $room->description ?: 'Rincian ruangan akan dilengkapi oleh pengelola.' }}</p>

            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="cs-caption">Kapasitas</dt><dd class="font-semibold">{{ $room->getCapacity() }} orang</dd></div>
                <div><dt class="cs-caption">Komputer</dt><dd class="font-semibold">{{ $computerCount > 0 ? $computerCount : 'Nonkomputer' }}</dd></div>
                <div><dt class="cs-caption">Lokasi</dt><dd class="font-semibold">{{ $room->building }} · Lantai {{ $room->floor ?? '-' }}</dd></div>
                <div><dt class="cs-caption">Status sekarang</dt><dd class="font-semibold">{{ \App\Models\Room::LIVE_STATUS_LABELS[$liveStatus] }}</dd></div>
            </dl>

            <h3 class="cs-section-title">Fasilitas tercatat</h3>
            @if ($room->facilities->isEmpty())
                <p class="text-sm text-ink-muted">Belum ada fasilitas tercatat.</p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($room->facilities as $facility)
                        <li class="cs-badge">{{ $facility->name }} · {{ $facility->getQuantity() }}</li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="cs-card cs-card-body space-y-3" aria-labelledby="upcoming-heading">
            <h2 id="upcoming-heading" class="cs-section-title">Jadwal mendatang</h2>
            <p class="text-sm text-ink-muted">Hanya jadwal kuliah dan peminjaman yang sudah disetujui.</p>
            @forelse ($upcomingEvents as $event)
                <div class="border-t border-border pt-2 text-sm">
                    <p class="font-medium">{{ $event['title'] }}</p>
                    <p class="text-ink-muted">{{ $event['start']->locale('id')->isoFormat('ddd, D MMM YYYY HH.mm') }}–{{ $event['end']->format('H.i') }} WIB</p>
                    <p class="cs-caption">{{ \App\Services\CalendarService::TYPE_LABELS[$event['type']] }}</p>
                </div>
            @empty
                <p class="text-sm text-ink-muted">Belum ada jadwal mendatang.</p>
            @endforelse
            <a href="{{ route('schedules.index') }}" class="cs-btn cs-btn-secondary w-full">Buka Kalender</a>
        </aside>
    </div>
@endsection
