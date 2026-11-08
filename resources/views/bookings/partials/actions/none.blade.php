{{-- Status tanpa aksi lanjutan (completed, no_show, cancelled, dll.) --}}
<span class="block text-xs text-ink-muted">Tidak ada aksi</span>

{{-- FE-06: Hapus booking cancelled oleh pengelola/admin (manage hanya merender tombol yang diizinkan) --}}
@include('bookings.partials.manage')
