{{-- FE-06 · Aksi pengelolaan booking oleh pemohon: Ubah, Batalkan, Hapus.
     STATUS INTEGRASI: BLOCKED — route belum tersedia (usulan BE-06). Setiap tombol HANYA tampil bila
     route-nya terdaftar; method diambil dari definisi route (x-route-form). Tanpa route tidak ada yang dirender.
       bookings.edit     (GET)                : status pending
       bookings.cancel   (method dari route)  : status pending / approved -> menjadi cancelled
       bookings.destroy  (method dari route)  : status pending
     Aturan status di atas mengikuti usulan BE-06; server tetap wajib menegakkannya.
     Dipanggil dari partial actions/pending dan actions/approved. --}}
@php
    $status = $booking->getStatus();
    $canEdit = Route::has('bookings.edit') && $status === \App\Models\Booking::STATUS_PENDING;
    $canCancel = Route::has('bookings.cancel') && in_array($status, [\App\Models\Booking::STATUS_PENDING, \App\Models\Booking::STATUS_APPROVED], true);
    $canDelete = Route::has('bookings.destroy') && $status === \App\Models\Booking::STATUS_PENDING;
@endphp

@if ($canEdit || $canCancel || $canDelete)
    <div class="cs-actions mt-2 border-t border-border pt-2">
        @if ($canEdit)
            <a href="{{ route('bookings.edit', $booking) }}" class="cs-btn cs-btn-secondary cs-btn-sm">
                Ubah<span class="sr-only"> booking {{ $booking->purpose }}</span>
            </a>
        @endif
        @if ($canCancel)
            <x-route-form route="bookings.cancel" :params="$booking"
                          confirm="Batalkan booking {{ $booking->room?->code }} pada {{ $booking->start_time->format('d/m/Y H:i') }}?">
                <button type="submit" class="cs-btn cs-btn-ghost cs-btn-sm">
                    Batalkan<span class="sr-only"> booking {{ $booking->purpose }}</span>
                </button>
            </x-route-form>
        @endif
        @if ($canDelete)
            <x-route-form route="bookings.destroy" :params="$booking"
                          confirm="Hapus booking {{ $booking->room?->code }} pada {{ $booking->start_time->format('d/m/Y H:i') }}? Tindakan ini tidak dapat dibatalkan.">
                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">
                    Hapus<span class="sr-only"> booking {{ $booking->purpose }}</span>
                </button>
            </x-route-form>
        @endif
    </div>
@endif
