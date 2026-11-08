{{-- Status checked_in -> simulasi scan QR check-out.
     Field qr_token: wajib, maks 64 karakter (QrScanRequest); token sama dengan saat check-in.
     Status menjadi completed hanya bila server menerima token; frontend tidak mengubah status apa pun.
     Hanya tampil untuk pemilik booking (BookingPolicy::checkOut); server tetap menolak user lain (403). --}}
@can('checkOut', $booking)
<form method="POST" action="{{ route('bookings.checkout', $booking) }}" class="space-y-1.5" data-cs-validate>
    @csrf
    <label for="qr_token_out_{{ $booking->id }}" class="cs-form-label text-xs">
        Kode cadangan <span class="cs-form-required" aria-hidden="true">*</span>
    </label>
    <input id="qr_token_out_{{ $booking->id }}" name="qr_token" type="text" required maxlength="64"
           pattern="[A-Za-z0-9]+" data-trim data-pattern-message="Kode cadangan hanya berisi huruf dan angka, tanpa spasi."
           autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Tempel kode cadangan"
           aria-describedby="qr_token_out_{{ $booking->id }}-hint"
           class="cs-form-control font-mono text-xs md:w-48">
    <p id="qr_token_out_{{ $booking->id }}-hint" class="cs-form-hint">Check-out menandai ruang kembali tersedia.</p>
    <button type="submit" class="cs-btn cs-btn-secondary cs-btn-sm">
        <x-icon name="qr" class="h-4 w-4" /> Check-out dengan kode cadangan
    </button>
</form>
@else
<span class="block text-xs text-ink-muted">Sedang digunakan</span>
@endcan
