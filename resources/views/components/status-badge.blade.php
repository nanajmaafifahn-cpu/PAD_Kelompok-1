{{-- Badge status. Warna dipilih murni oleh CSS lewat atribut data-status
     (lihat bagian "Variasi status" di resources/css/campus-space.css),
     jadi tidak ada if/else atau pemetaan status di dalam Blade.
     Status baru cukup ditambahkan satu selector di campus-space.css.
     Contoh: <x-status-badge :status="$booking->status" /> --}}
@props(['status'])

<span data-status="{{ $status }}" {{ $attributes->class(['cs-badge']) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
