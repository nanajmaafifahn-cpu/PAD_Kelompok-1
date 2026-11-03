<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · Campus Space Utilization</title>

    {{-- Slot tambahan untuk <head>, misalnya auto-refresh di dashboard --}}
    @stack('head')

    {{-- Pakai Vite (Tailwind + campus-space.css lewat app.css) bila sudah `npm run dev` / `npm run build`;
         jika belum, fallback ke Tailwind CDN dan campus-space.css disisipkan langsung
         agar token & komponen .cs-* tetap berlaku. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4.1.11"></script>
        <style type="text/tailwindcss">{!! file_get_contents(resource_path('css/campus-space.css')) !!}</style>
    @endif
</head>
<body>
    <a href="#main-content" class="cs-skip-link">Lewati ke konten utama</a>

    {{-- ===================== HEADER & NAVIGASI ===================== --}}
    <header class="cs-site-header">
        <div class="cs-container flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ url('/') }}" class="cs-brand">
                <span class="cs-brand-mark" aria-hidden="true">CS</span>
                <span>
                    <span class="cs-brand-name">Campus Space &amp; Lab Utilization</span>
                    <span class="cs-brand-tagline">Resource Utilization &amp; Facility Efficiency</span>
                </span>
            </a>

            <nav aria-label="Navigasi utama">
                <ul class="cs-nav-list">
                    <x-nav-link route="dashboard" :active="['dashboard*', 'home']">Dashboard</x-nav-link>
                    <x-nav-link route="rooms.index" active="rooms.*">Ruang &amp; Lab</x-nav-link>
                    <x-nav-link route="schedules.index" active="schedules.*">Jadwal</x-nav-link>
                    <x-nav-link route="bookings.index" active="bookings.*">Booking</x-nav-link>
                </ul>
            </nav>
        </div>
    </header>

    {{-- ===================== KONTEN ===================== --}}
    <main id="main-content" tabindex="-1" class="cs-container py-6 focus:outline-none">
        @include('layouts.partials.flash')

        @yield('content')
    </main>

    <footer class="cs-site-footer">
        <div class="cs-container">
            <p>PAD Kelompok 1 · Campus Space &amp; Laboratory Utilization System</p>
        </div>
    </footer>

    {{-- Mode CDN: interaksi form (campus-space.js) disisipkan langsung; mode Vite memuatnya lewat app.js. --}}
    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/campus-space.js')) !!}</script>
    @endunless
</body>
</html>
