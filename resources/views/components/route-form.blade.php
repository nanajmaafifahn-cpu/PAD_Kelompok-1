{{-- Form yang HANYA dirender bila route-nya benar-benar terdaftar di routes/web.php (FE-06).
     HTTP method diambil dari definisi route aktual, bukan ditebak:
       route punya DELETE -> @method('DELETE'); PUT -> @method('PUT'); PATCH -> @method('PATCH'); selain itu POST.
     Bila route belum ada, komponen tidak menghasilkan apa pun (tidak ada form dengan action palsu).
     Contoh:
       <x-route-form route="rooms.destroy" :params="$room" confirm="Hapus ruang ini?">
           <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Hapus</button>
       </x-route-form>
     Atribut lain (class, data-*) diteruskan ke <form>. --}}
@props(['route', 'params' => [], 'confirm' => null])

@php
    $definition = \Illuminate\Support\Facades\Route::getRoutes()->getByName($route);
    $methods = $definition?->methods() ?? [];
    $spoofMethod = collect(['DELETE', 'PUT', 'PATCH'])->first(fn ($method) => in_array($method, $methods, true));
@endphp

@if ($definition)
    <form method="POST" action="{{ route($route, $params) }}"
          @if ($confirm) data-confirm="{{ $confirm }}" @endif
          {{ $attributes }}>
        @csrf
        @if ($spoofMethod)
            @method($spoofMethod)
        @endif
        {{ $slot }}
    </form>
@endif
