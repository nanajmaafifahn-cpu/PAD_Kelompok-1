{{-- Item menu sidebar (<li><a>) dengan ikon. Sama seperti x-nav-link: hanya tampil bila route-nya
     terdaftar (Route::has), jadi menu tidak pernah menunjuk fitur yang belum dibuat.
     Contoh: <x-sidebar-link route="rooms.index" icon="search" active="rooms.index">Jelajahi Ruangan</x-sidebar-link> --}}
@props(['route', 'icon', 'active' => null])

@if (Route::has($route))
    @php
        $isActive = request()->routeIs(...(array) ($active ?? $route));
    @endphp
    <li>
        <a href="{{ route($route) }}" class="cs-sidebar-link" @if ($isActive) aria-current="page" @endif>
            <x-icon :name="$icon" />
            <span>{{ $slot }}</span>
        </a>
    </li>
@endif
