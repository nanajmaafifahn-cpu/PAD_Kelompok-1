{{-- Kartu filter (GET): field di slot, tombol kirim, dan tautan Reset bila ada filter aktif.
     Nilai field diisi halaman pemakai dari variabel controller (mis. $filters) agar tetap terisi setelah submit.
     Props: action (URL), cols (jumlah kolom field di desktop, default 4), submit (teks tombol), reset (URL reset, opsional),
            active (bool, tampilkan Reset), label (aria-label form).
     Contoh:
       <x-filter-bar :action="route('rooms.index')" :reset="route('rooms.index')" :active="$hasFilters" label="Filter ruang">
           ...field...
       </x-filter-bar> --}}
@props(['action', 'cols' => 4, 'submit' => 'Cari', 'reset' => null, 'active' => false, 'label' => 'Filter'])

<form method="GET" action="{{ $action }}" role="search" aria-label="{{ $label }}"
      style="--cs-filter-cols: {{ (int) $cols }}" {{ $attributes->class(['cs-filter-bar', 'cs-no-print']) }}>
    {{ $slot }}

    <div class="cs-filter-bar-actions">
        <button type="submit" class="cs-btn cs-btn-primary">{{ $submit }}</button>
        @if ($reset && $active)
            <a href="{{ $reset }}" class="cs-btn cs-btn-ghost">Reset</a>
        @endif
    </div>
</form>
