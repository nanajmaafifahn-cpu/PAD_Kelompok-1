{{-- FE-06 · Aksi pengelolaan booking: Ubah, Batalkan (pemilik), Hapus (pengelola/admin).
     STATUS INTEGRASI: AKTIF (BE-06, Fase 3). Tombol tampil bila route terdaftar DAN user berhak (@can):
       bookings.edit     (GET)     : pemilik, status pending
       bookings.cancel   (PATCH)   : pemilik, status pending / approved yang belum dimulai -> cancelled
       bookings.destroy  (DELETE)  : pengelola/admin, status pending / rejected / cancelled
     Server (BookingPolicy + BookingService) tetap menegakkan aturan yang sama.
     Dipanggil dari partial actions/pending, approved, rejected, dan none. --}}
@php
    $status = $booking->getStatus();
    $user = auth()->user();
    $canEdit = Route::has('bookings.edit') && $status === \App\Models\Booking::STATUS_PENDING
        && $user->can('update', $booking);
    $canCancel = Route::has('bookings.cancel')
        && in_array($status, [\App\Models\Booking::STATUS_PENDING, \App\Models\Booking::STATUS_APPROVED], true)
        && $booking->start_time->isFuture()
        && $user->can('cancel', $booking);
    $canDelete = Route::has('bookings.destroy')
        && in_array($status, \App\Models\Booking::DELETABLE_STATUSES, true)
        && $user->can('delete', $booking);
@endphp

@if ($canEdit || $canCancel || $canDelete)
    <div class="cs-actions mt-2 border-t border-border pt-2">
        @if ($canEdit)
            <a href="{{ route('bookings.edit', $booking) }}" class="cs-btn cs-btn-secondary cs-btn-sm">
                Ubah<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
            </a>
        @endif
        @if ($canCancel)
            <x-route-form route="bookings.cancel" :params="$booking"
                          confirm="Batalkan peminjaman {{ $booking->room?->name }} pada {{ \App\Support\DateFormat::dateTime($booking->start_time) }}?">
                <button type="submit" class="cs-btn cs-btn-ghost cs-btn-sm">
                    Batalkan<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
                </button>
            </x-route-form>
        @endif
        @if ($canDelete)
            <x-route-form route="bookings.destroy" :params="$booking"
                          confirm="Hapus peminjaman {{ $booking->room?->name }} pada {{ \App\Support\DateFormat::dateTime($booking->start_time) }}? Tindakan ini tidak dapat dibatalkan.">
                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">
                    Hapus<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
                </button>
            </x-route-form>
        @endif
    </div>
@endif
