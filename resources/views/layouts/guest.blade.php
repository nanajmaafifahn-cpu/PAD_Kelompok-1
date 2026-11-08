<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Masuk') · Campus Space</title>

    {{-- Layout tamu (login/register): panel brand navy di kiri, form di kanan; di ponsel bertumpuk.
         Aset sama dengan layouts/app: Vite bila sudah di-build, fallback CDN. --}}
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

    <div class="cs-auth">
        <section class="cs-auth-brand" aria-label="Campus Space">
            <div class="cs-sidebar-brand mb-0">
                <span class="cs-logo" aria-hidden="true">C</span>
                <span>
                    <span class="cs-sidebar-brand-name">Campus Space</span>
                    <span class="cs-sidebar-brand-tagline">Laboratorium FRI</span>
                </span>
            </div>

            <div class="max-w-md">
                <p class="cs-eyebrow text-[color:var(--cs-color-primary-bright)]">Fakultas Rekayasa Industri</p>
                <p class="cs-auth-headline">Ruang untuk setiap kegiatan.</p>
                <p class="mt-4 text-base">Periksa jadwal laboratorium, ajukan peminjaman, lalu catat penggunaan ruangan lewat QR di pintu.</p>
            </div>

            <p class="text-xs text-[color:var(--cs-sidebar-text-muted)]">Campus Space · FRI · Waktu aplikasi {{ now()->format('T') }}</p>
        </section>

        <main id="main-content" class="cs-auth-main">
            <div class="cs-auth-form">
                @include('layouts.partials.flash')

                @yield('content')
            </div>
        </main>
    </div>

    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/campus-space.js')) !!}</script>
    @endunless
</body>
</html>
