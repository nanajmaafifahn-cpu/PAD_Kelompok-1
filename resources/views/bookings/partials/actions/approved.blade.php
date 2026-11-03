{{-- Status approved -> simulasi scan QR check-in.
     Isi QR = token booking (dibuat server saat approve, huruf & angka acak).
     Field qr_token: wajib, maks 64 karakter (QrScanRequest).
     Server menerima check-in mulai 15 menit sebelum jadwal mulai hingga jadwal selesai (Booking::performQrCheckIn).
     Status hanya berubah bila server menerima token; frontend tidak mengubah status apa pun.
     Mensyaratkan user login; tanpa login server membalas 403 (usulan BE-01). --}}
<form method="POST" action="{{ route('bookings.checkin', $booking) }}" class="space-y-1.5" data-cs-validate>
    @csrf
    <label for="qr_token_in_{{ $booking->id }}" class="cs-form-label text-xs">
        Token QR <span class="cs-form-required" aria-hidden="true">*</span>
    </label>
    <input id="qr_token_in_{{ $booking->id }}" name="qr_token" type="text" required maxlength="64"
           pattern="[A-Za-z0-9]+" data-trim data-pattern-message="Token QR hanya berisi huruf dan angka, tanpa spasi."
           autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Tempel isi QR"
           aria-describedby="qr_token_in_{{ $booking->id }}-hint"
           class="cs-form-control font-mono text-xs md:w-48">
    <p id="qr_token_in_{{ $booking->id }}-hint" class="cs-form-hint">Check-in dibuka 15 menit sebelum jadwal mulai.</p>
    <button type="submit" class="cs-btn cs-btn-primary cs-btn-sm">
        <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/></svg>
        Simulasi Scan QR Check-In
    </button>
</form>

{{-- FE-06: Batalkan (hanya tampil bila route bookings.cancel tersedia) --}}
@include('bookings.partials.manage')
