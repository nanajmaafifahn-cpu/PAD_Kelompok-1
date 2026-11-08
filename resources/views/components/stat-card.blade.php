{{-- Kartu statistik: label, angka besar, keterangan kecil, ikon di chip berwarna lembut (pojok kanan atas).
     Warna chip mengikuti tone = nama status di bagian 5 campus-space.css (mis. pending, completed, occupied);
     tanpa tone = teal. Pilih ikon sesuai makna (clock = menunggu, check-circle = selesai, dst.).
     Contoh: <x-stat-card label="Menunggu persetujuan" :value="$stats['pending']" caption="Dalam pemeriksaan" icon="clock" tone="pending" /> --}}
@props(['label', 'value', 'caption' => null, 'icon' => 'info', 'tone' => null])

<div {{ $attributes->class(['cs-stat-card']) }}>
    <span class="cs-icon-chip" @if ($tone) data-status="{{ $tone }}" @endif>
        <x-icon :name="$icon" />
    </span>
    <dt class="cs-stat-card-label">{{ $label }}</dt>
    <dd class="cs-stat-card-value">{{ $value }}</dd>
    @if ($caption)
        <dd class="cs-stat-card-caption">{{ $caption }}</dd>
    @endif
</div>
