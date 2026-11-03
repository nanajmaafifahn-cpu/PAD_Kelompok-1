{{-- Status pending -> panel Verify untuk Kepala Lab / Admin Akademik.
     Approve : POST bookings.approve (ApproveBookingRequest).
     Reject  : POST bookings.reject + field rejection_reason, wajib, maks 1000 karakter (RejectBookingRequest).
     Keduanya mensyaratkan user login ber-role lab_head/admin; tanpa login server membalas 403 (usulan BE-01).
     Galat validasi reject tampil di alert atas halaman (bukan per baris). --}}
<div class="space-y-2">
    <form method="POST" action="{{ route('bookings.approve', $booking) }}">
        @csrf
        <button type="submit" class="cs-btn cs-btn-primary cs-btn-sm">
            <span aria-hidden="true">✓</span> Approve / Verify
        </button>
    </form>

    <details data-focus-on-open>
        <summary class="inline-block cursor-pointer rounded text-xs font-medium text-danger hover:underline">
            <span aria-hidden="true">✕</span> Reject
        </summary>
        <form method="POST" action="{{ route('bookings.reject', $booking) }}" class="mt-2 space-y-1.5" data-cs-validate>
            @csrf
            <label for="rejection_reason_{{ $booking->id }}" class="cs-form-label text-xs">
                Alasan penolakan <span class="cs-form-required" aria-hidden="true">*</span>
            </label>
            <textarea id="rejection_reason_{{ $booking->id }}" name="rejection_reason" rows="2" required maxlength="1000" data-not-blank
                      aria-describedby="rejection_reason_{{ $booking->id }}-hint"
                      class="cs-form-control text-xs md:w-56"></textarea>
            <p id="rejection_reason_{{ $booking->id }}-hint" class="cs-form-hint">Wajib diisi, maksimal 1000 karakter.</p>
            <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Tolak Booking</button>
        </form>
    </details>

    <p class="cs-caption">Kepala Lab / Admin Akademik</p>

    {{-- FE-06: Ubah / Batalkan / Hapus (hanya tampil bila route-nya tersedia) --}}
    @include('bookings.partials.manage')
</div>
