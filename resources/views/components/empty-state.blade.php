{{-- Keadaan kosong: judul tebal + kalimat bantuan + tombol aksi opsional (slot).
     Contoh:
       <x-empty-state title="Belum ada peminjaman" text="Ajukan peminjaman pertama Anda.">
           <a href="{{ route('bookings.create') }}" class="cs-btn cs-btn-primary">Ajukan Peminjaman</a>
       </x-empty-state> --}}
@props(['title', 'text' => null])

<div {{ $attributes->class(['cs-empty']) }}>
    <p class="cs-empty-title">{{ $title }}</p>
    @if ($text)
        <p class="cs-empty-text">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-2 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
