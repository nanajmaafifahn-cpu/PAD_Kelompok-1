{{-- Hasil pindai QR ruangan (scan.room). Dibuka langsung dari kamera ponsel atau dari halaman Pindai QR.
     Variabel dari ScanController@show:
       $room    Room yang QR-nya dipindai
       $result  App\Support\Scan\ScanResult: action ('check_in' | 'check_out' | null), booking, message
     Halaman ini TIDAK mengubah data; tombol konfirmasi mengirim POST scan.confirm.
     Galat konfirmasi kembali ke halaman ini sebagai pesan flash (di bawah header). --}}
@extends('layouts.app')

@use('App\Support\DateFormat')
@use('App\Support\Scan\ScanResult')

@section('title', 'Hasil Pindai QR')

@section('page_header')
    <x-page-header eyebrow="Penggunaan aktual" title="Ruangan {{ $room->name }}" crumb="Pindai QR"
                   subtitle="{{ $room->code }} · {{ $room->building }}">
        <x-slot:actions>
            <a href="{{ route('scan.index') }}" class="cs-btn cs-btn-secondary">Pindai QR lain</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-card cs-card-body mx-auto max-w-xl space-y-5 text-center lg:mx-0" aria-live="polite">
        @if ($result->isAllowed())
            <span class="cs-icon-chip mx-auto h-14 w-14" data-status="{{ $result->action === ScanResult::ACTION_CHECK_OUT ? 'checked_in' : 'completed' }}">
                <x-icon name="{{ $result->action === ScanResult::ACTION_CHECK_OUT ? 'logout' : 'door' }}" />
            </span>
            <div>
                <p class="text-xl font-extrabold text-ink">
                    {{ $result->action === ScanResult::ACTION_CHECK_OUT ? 'Siap check-out' : 'Peminjaman ditemukan' }}
                </p>
                <p class="mt-1 text-ink-soft">{{ $result->message }}</p>
            </div>

            <dl class="grid gap-3 rounded-xl bg-[color:var(--cs-color-surface-muted)] p-4 text-left text-sm sm:grid-cols-2">
                <div>
                    <dt class="cs-caption">Keperluan</dt>
                    <dd class="font-semibold text-ink">{{ $result->booking->purpose }}</dd>
                </div>
                <div>
                    <dt class="cs-caption">Waktu</dt>
                    <dd class="font-semibold text-ink">{{ DateFormat::range($result->booking->start_time, $result->booking->end_time) }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('scan.confirm', $room) }}">
                @csrf
                <button type="submit" class="cs-btn cs-btn-primary w-full">
                    {{ $result->action === ScanResult::ACTION_CHECK_OUT ? 'Konfirmasi Check-out' : 'Konfirmasi Check-in' }}
                </button>
            </form>
        @else
            <span class="cs-icon-chip mx-auto h-14 w-14" data-status="booked"><x-icon name="alert" /></span>
            <div>
                <p class="text-xl font-extrabold text-ink">Belum bisa check-in / check-out</p>
                <p class="mt-1 text-ink-soft" role="alert">{{ $result->message }}</p>
            </div>

            <div class="flex flex-wrap justify-center gap-2">
                @if ($result->booking)
                    <a href="{{ route('bookings.show', $result->booking) }}" class="cs-btn cs-btn-secondary">Lihat detail peminjaman</a>
                @elseif ($room->getStatus() !== 'maintenance')
                    <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="cs-btn cs-btn-secondary">Ajukan peminjaman ruang ini</a>
                @endif
                <a href="{{ route('bookings.mine') }}" class="cs-btn cs-btn-ghost">Peminjaman Saya</a>
            </div>
        @endif
    </section>
@endsection
