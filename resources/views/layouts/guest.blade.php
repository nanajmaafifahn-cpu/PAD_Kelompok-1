<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') · Campus Space Utilization</title>

    {{-- Layout tamu (login/register). Aset sama dengan layouts/app: Vite bila sudah di-build, fallback CDN.
         Tampilan final dirapikan di PROMPT 2. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>
        <style type="text/tailwindcss">{!! file_get_contents(resource_path('css/campus-space.css')) !!}</style>
    @endif
</head>
<body>
    <main id="main-content" class="cs-container flex min-h-screen flex-col items-center justify-center py-10">
        <a href="{{ route('login') }}" class="cs-brand mb-6">
            <span class="cs-brand-mark" aria-hidden="true">CS</span>
            <span>
                <span class="cs-brand-name">Campus Space &amp; Lab Utilization</span>
                <span class="cs-brand-tagline">Resource Utilization &amp; Facility Efficiency</span>
            </span>
        </a>

        <div class="w-full max-w-md">
            @include('layouts.partials.flash')

            @yield('content')
        </div>
    </main>

    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/campus-space.js')) !!}</script>
    @endunless
</body>
</html>
