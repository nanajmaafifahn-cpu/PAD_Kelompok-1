{{-- Status checked_in -> simulasi scan QR check-out.
     Field qr_token: wajib, maks 64 karakter (QrScanRequest); token sama dengan saat check-in.
     Status menjadi completed hanya bila server menerima token; frontend tidak mengubah status apa pun.
     Mensyaratkan user login; tanpa login server membalas 403 (usulan BE-01). --}}
<form method="POST" action="{{ route('bookings.checkout', $booking) }}" class="space-y-1.5" data-cs-validate>
    @csrf
    <label for="qr_token_out_{{ $booking->id }}" class="cs-form-label text-xs">
        Token QR <span class="cs-form-required" aria-hidden="true">*</span>
    </label>
    <input id="qr_token_out_{{ $booking->id }}" name="qr_token" type="text" required maxlength="64"
           pattern="[A-Za-z0-9]+" data-trim data-pattern-message="Token QR hanya berisi huruf dan angka, tanpa spasi."
           autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Tempel isi QR"
           aria-describedby="qr_token_out_{{ $booking->id }}-hint"
           class="cs-form-control font-mono text-xs md:w-48">
    <p id="qr_token_out_{{ $booking->id }}-hint" class="cs-form-hint">Check-out menandai ruang kembali tersedia.</p>
    <button type="submit" class="cs-btn cs-btn-secondary cs-btn-sm">
        <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/></svg>
        Simulasi Scan QR Check-Out
    </button>
</form>
