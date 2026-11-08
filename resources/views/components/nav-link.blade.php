{{-- Item navigasi (<li><a>) untuk <ul class="cs-nav-list"> di layout.
     Hanya tampil jika route-nya sudah didaftarkan di routes/web.php,
     sehingga layout tidak error ketika fitur anggota lain belum di-merge.
     Halaman aktif ditandai aria-current="page" (gayanya diatur campus-space.css). --}}
@props(['route', 'active'])

@if (Route::has($route))
    @php
        $isActive = request()->routeIs(...(array) $active);
    @endphp
    <li>
        <a href="{{ route($route) }}" class="cs-nav-link" @if ($isActive) aria-current="page" @endif>
            {{ $slot }}
        </a>
    </li>
@endif
