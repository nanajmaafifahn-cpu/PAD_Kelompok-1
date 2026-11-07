{{-- Hasil pindai QR ruangan (scan.room). Dibuka langsung dari kamera ponsel.
     Variabel dari ScanController@show:
       $room    Room yang QR-nya dipindai
       $result  App\Support\Scan\ScanResult: action ('check_in' | 'check_out' | null), booking, message
     Halaman ini TIDAK mengubah data; tombol konfirmasi mengirim POST scan.confirm. --}}
@extends('layouts.app')

@section('title', 'Hasil Scan QR')

@section('content')
    <section class="cs-container-narrow space-y-6" aria-labelledby="page-title">
        <header>
            <a href="{{ route('scan.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke pemindai</a>
            <h1 id="page-title" class="cs-page-title mt-2">{{ $room->name }}</h1>
            <p class="cs-page-description"><span class="cs-code">{{ $room->code }}</span> · {{ $room->building }}</p>
        </header>

        <div class="cs-card cs-card-body space-y-4">
            @if ($result->isAllowed())
                <div role="status" class="cs-alert cs-alert-info mb-0">
                    <span aria-hidden="true" class="cs-alert-icon">i</span>
                    <p>{{ $result->message }}</p>
                </div>

                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="cs-form-label">Keperluan</dt>
                        <dd>{{ $result->booking->purpose }}</dd>
                    </div>
                    <div>
                        <dt class="cs-form-label">Jadwal</dt>
                        <dd class="tabular-nums">{{ $result->booking->start_time->format('H.i') }}–{{ $result->booking->end_time->format('H.i T') }}</dd>
                    </div>
                </dl>

                <form method="POST" action="{{ route('scan.confirm', $room) }}">
                    @csrf
                    <button type="submit" class="cs-btn cs-btn-primary w-full sm:w-auto">
                        {{ $result->action === \App\Support\Scan\ScanResult::ACTION_CHECK_OUT ? 'Konfirmasi Check-Out' : 'Konfirmasi Check-In' }}
                    </button>
                </form>
            @else
                <div role="alert" class="cs-alert cs-alert-warning mb-0">
                    <span aria-hidden="true" class="cs-alert-icon">!</span>
                    <p>{{ $result->message }}</p>
                </div>

                @if ($result->booking)
                    <a href="{{ route('bookings.show', $result->booking) }}" class="cs-link text-sm">Lihat detail peminjaman</a>
                @else
                    <a href="{{ route('bookings.create') }}" class="cs-link text-sm">Ajukan peminjaman ruang ini</a>
                @endif
            @endif
        </div>
    </section>
@endsection
