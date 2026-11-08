{{-- Header halaman: breadcrumb "Campus Space / {crumb}", eyebrow teal, judul besar, subjudul, aksi di kanan.
     Pakai di dalam @section('page_header') agar layout menaruh pesan flash tepat di bawahnya:
       @section('page_header')
           <x-page-header eyebrow="Kalender laboratorium" title="Jadwal ruangan." crumb="Jadwal Ruangan"
                          subtitle="Jadwal akademik dan peminjaman yang telah disetujui.">
               <x-slot:actions><a href="..." class="cs-btn cs-btn-primary">Ajukan Peminjaman</a></x-slot:actions>
           </x-page-header>
       @endsection
     Atribut: title (wajib), eyebrow, subtitle, crumb (default = title tanpa titik akhir). --}}
@props(['title', 'eyebrow' => null, 'subtitle' => null, 'crumb' => null])

<header {{ $attributes->class(['mb-8']) }}>
    <nav aria-label="Breadcrumb">
        <ol class="cs-breadcrumb">
            <li><a href="{{ route('dashboard') }}">Campus Space</a></li>
            <li aria-current="page">{{ $crumb ?? rtrim($title, '.') }}</li>
        </ol>
    </nav>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            @if ($eyebrow)
                <p class="cs-eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 id="page-title" class="cs-page-title">{{ $title }}</h1>
            @if ($subtitle)
                <p class="cs-page-description">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="cs-page-actions cs-no-print">{{ $actions }}</div>
        @endisset
    </div>
</header>
