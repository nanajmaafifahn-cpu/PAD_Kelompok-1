{{-- Badge status. Warna dipilih CSS lewat data-status (bagian 5 campus-space.css): satu warna per status.
     Teks = label Bahasa Indonesia dari satu sumber per domain:
       booking  -> Booking::STATUS_LABELS          room     -> Room::LIVE_STATUS_SHORT_LABELS
       facility -> Facility::STATUS_LABELS         schedule -> Schedule::STATUS_LABELS
       category -> UtilizationResult::CATEGORY_LABELS   user -> Aktif / Nonaktif
     Tanpa atribut domain: dicari berurutan booking -> room -> schedule -> category -> user.
     Kondisi fasilitas WAJIB domain="facility" (kunci "available" berarti "Baik", bukan "Tersedia").
     Contoh: <x-status-badge :status="$booking->status" />, <x-status-badge :status="$f->getStatus()" domain="facility" /> --}}
@props(['status', 'domain' => null])

@php
    $labels = [
        'booking' => \App\Models\Booking::STATUS_LABELS,
        'room' => \App\Models\Room::LIVE_STATUS_SHORT_LABELS,
        'schedule' => \App\Models\Schedule::STATUS_LABELS,
        'category' => \App\Support\Utilization\UtilizationResult::CATEGORY_LABELS,
        'user' => ['active' => 'Aktif', 'inactive' => 'Nonaktif'],
        'facility' => \App\Models\Facility::STATUS_LABELS,
    ];
    $key = strtolower((string) $status);
    $label = $domain
        ? ($labels[$domain][$key] ?? null)
        : collect($labels)->except('facility')->first(fn ($map) => isset($map[$key]))[$key] ?? null;
@endphp

<span data-status="{{ $key }}" {{ $attributes->class(['cs-badge']) }}>
    {{ $label ?? str_replace('_', ' ', $key) }}
</span>
