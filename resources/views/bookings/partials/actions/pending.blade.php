{{-- Status pending -> aksi Setujui / Tolak untuk Kepala Lab / Admin Akademik.
     Setujui : POST bookings.approve, dikonfirmasi lewat dialog umum (form data-confirm).
     Tolak   : POST bookings.reject + rejection_reason (wajib, maks 1000) di dalam x-confirm-dialog.
               Tanpa JS, tombol dialog tersembunyi dan form inline di <noscript> dipakai.
     Hanya tampil untuk role lab_head/admin (BookingPolicy::approve); server tetap menolak role lain (403). --}}
<div class="space-y-2">
    @can('approve', $booking)
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('bookings.approve', $booking) }}"
                  data-confirm="Setujui peminjaman {{ $booking->room?->name }} oleh {{ $booking->requester_name ?? $booking->user?->name }}? Kode QR cadangan akan diterbitkan untuk peminjam.">
                @csrf
                <button type="submit" class="cs-btn cs-btn-primary cs-btn-sm">
                    <x-icon name="check" class="h-4 w-4" /> Setujui<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
                </button>
            </form>

            <button type="button" class="cs-btn cs-btn-danger cs-btn-sm" data-dialog-open="reject-{{ $booking->id }}" hidden>
                <x-icon name="close" class="h-4 w-4" /> Tolak<span class="sr-only"> peminjaman {{ $booking->purpose }}</span>
            </button>
        </div>

        <x-confirm-dialog id="reject-{{ $booking->id }}" title="Tolak peminjaman?" :action="route('bookings.reject', $booking)"
                          confirm="Tolak Peminjaman" reason="rejection_reason" reason-label="Alasan penolakan">
            {{ $booking->room?->name }} · {{ $booking->purpose }}. Alasan ini ditampilkan kepada peminjam.
        </x-confirm-dialog>

        <noscript>
            <form method="POST" action="{{ route('bookings.reject', $booking) }}" class="space-y-1.5">
                @csrf
                <label for="rejection_reason_{{ $booking->id }}" class="cs-form-label text-xs">Alasan penolakan</label>
                <textarea id="rejection_reason_{{ $booking->id }}" name="rejection_reason" rows="2" required maxlength="1000"
                          class="cs-form-control text-xs"></textarea>
                <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Tolak</button>
            </form>
        </noscript>
    @else
        <span class="text-xs text-ink-muted">Menunggu verifikasi pengelola</span>
    @endcan

    {{-- FE-06: Ubah / Batalkan / Hapus (hanya tampil bila route-nya tersedia dan diizinkan) --}}
    @include('bookings.partials.manage')
</div>
