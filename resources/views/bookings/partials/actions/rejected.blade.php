{{-- Status rejected -> tidak ada aksi lanjutan; tampilkan alasan penolakan dari Kepala Lab / Admin --}}
<p class="text-xs text-ink-soft md:max-w-48">
    <span class="font-medium text-danger">Alasan:</span>
    {{ $booking->rejection_reason ?? '—' }}
</p>
