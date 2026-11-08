<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · Campus Space</title>

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
{{-- Layout aplikasi (PROMPT 2 Tahap 1): sidebar navy kiri + topbar + konten.
     < 1024px sidebar menjadi drawer (tombol menu di topbar, overlay, Esc, fokus terkunci; campus-space.js).
     Menu dirender per role DAN Route::has() (x-sidebar-link), jadi tidak pernah menunjuk route yang belum ada.
     Halaman yang memakai x-page-header menaruhnya di @section('page_header'); pesan flash tampil tepat di bawahnya. --}}
@php
    $user = auth()->user();
    $isBorrower = $user?->hasRole(\App\Models\User::ROLE_BORROWER);
    $isManager = $user?->hasRole(\App\Models\User::ROLE_LAB_HEAD, \App\Models\User::ROLE_ADMIN);
    $isLeader = $user?->hasRole(\App\Models\User::ROLE_LEADER);
    $isAdmin = $user?->hasRole(\App\Models\User::ROLE_ADMIN);
@endphp
<body>
    <a href="#main-content" class="cs-skip-link">Lewati ke konten utama</a>

    <div class="cs-shell">
        {{-- ===================== SIDEBAR ===================== --}}
        <aside id="cs-sidebar" class="cs-sidebar" data-drawer aria-label="Menu aplikasi">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ route('dashboard') }}" class="cs-sidebar-brand">
                    <span class="cs-logo" aria-hidden="true">C</span>
                    <span>
                        <span class="cs-sidebar-brand-name">Campus Space</span>
                        <span class="cs-sidebar-brand-tagline">Laboratorium FRI</span>
                    </span>
                </a>
                <button type="button" class="cs-icon-btn cs-drawer-toggle border-0 bg-transparent text-white hover:bg-white/10"
                        data-drawer-close aria-label="Tutup menu">
                    <x-icon name="close" />
                </button>
            </div>

            <nav aria-label="Navigasi utama">
                <div class="cs-sidebar-group">
                    <p class="cs-sidebar-heading">Menu utama</p>
                    <ul class="cs-sidebar-list">
                        <x-sidebar-link route="dashboard" icon="home" :active="['dashboard', 'home']">Dashboard</x-sidebar-link>

                        @unless ($isLeader)
                            <x-sidebar-link route="rooms.index" icon="search" :active="['rooms.index', 'rooms.show']">Jelajahi Ruangan</x-sidebar-link>
                            <x-sidebar-link route="schedules.index" icon="calendar" active="schedules.*">Jadwal Ruangan</x-sidebar-link>
                        @endunless

                        @if ($isBorrower)
                            <x-sidebar-link route="bookings.mine" icon="clipboard" :active="['bookings.mine', 'bookings.create', 'bookings.show', 'bookings.edit']">Peminjaman Saya</x-sidebar-link>
                            <x-sidebar-link route="scan.index" icon="qr" active="scan.*">Pindai QR</x-sidebar-link>
                        @elseif ($isManager)
                            <x-sidebar-link route="bookings.index" icon="clipboard" :active="['bookings.*']">Peminjaman</x-sidebar-link>
                        @endif

                        @if ($isManager || $isLeader)
                            <x-sidebar-link route="dashboard.utilization" icon="chart" active="dashboard.utilization">Laporan Utilisasi</x-sidebar-link>
                        @endif
                    </ul>
                </div>

                @if ($isManager)
                    <div class="cs-sidebar-group">
                        <p class="cs-sidebar-heading">Pengelolaan</p>
                        <ul class="cs-sidebar-list">
                            <x-sidebar-link route="rooms.manage" icon="building" :active="['rooms.manage', 'rooms.create', 'rooms.edit']">Ruangan &amp; Foto</x-sidebar-link>
                            {{-- Belum ada halaman fasilitas global: menu menuju Ruangan & Foto (tombol Fasilitas per ruang). --}}
                            <x-sidebar-link route="rooms.manage" icon="monitor" :active="['rooms.facilities.*', 'facilities.*']">Fasilitas</x-sidebar-link>
                            <x-sidebar-link route="rooms.qr.index" icon="printer" active="rooms.qr.*">Cetak QR Ruangan</x-sidebar-link>
                            @if ($isAdmin)
                                <x-sidebar-link route="admin.users.index" icon="users" active="admin.users.*">Pengguna</x-sidebar-link>
                                <x-sidebar-link route="admin.units.index" icon="academic" active="admin.units.*">Unit Akademik</x-sidebar-link>
                            @endif
                        </ul>
                    </div>
                @endif
            </nav>

            {{-- Kartu bantuan. Link "Buka panduan" disembunyikan sampai halaman panduan tersedia. --}}
            <div class="cs-sidebar-card">
                <p class="cs-sidebar-card-title">Ruang untuk setiap kegiatan.</p>
                <p class="mt-2">Periksa jadwal, ajukan peminjaman, dan catat penggunaan lewat QR ruangan.</p>
            </div>

            <div class="cs-sidebar-footer">
                <p>Campus Space · FRI</p>
                <p>Waktu aplikasi: {{ \App\Support\DateFormat::timezoneLabel() }}</p>
            </div>
        </aside>
        <div class="cs-drawer-overlay" data-drawer-overlay hidden></div>

        {{-- ===================== KOLOM KONTEN ===================== --}}
        <div class="cs-main-col">
            <header class="cs-topbar">
                <button type="button" class="cs-icon-btn cs-drawer-toggle" data-drawer-open
                        aria-controls="cs-sidebar" aria-expanded="false" aria-label="Buka menu">
                    <x-icon name="menu" />
                </button>

                {{-- Akun belum terhubung ke unit akademik: nama fakultas memakai teks tetap. --}}
                <p class="cs-topbar-faculty">Fakultas Rekayasa Industri</p>

                @auth
                    <div class="cs-topbar-user">
                        <span class="cs-avatar" aria-hidden="true">{{ $user->initials() }}</span>
                        <span class="hidden sm:block">
                            <span class="cs-user-name">{{ $user->name }}</span>
                            <span class="cs-user-role">{{ $user->roleLabel() }}</span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cs-btn cs-btn-primary cs-btn-sm" data-loading-text="Keluar...">
                                <x-icon name="logout" class="h-4 w-4 sm:hidden" />
                                <span class="sr-only sm:not-sr-only">Keluar</span>
                            </button>
                        </form>
                    </div>
                @endauth
            </header>

            <main id="main-content" tabindex="-1" class="cs-content focus:outline-none">
                @hasSection('page_header')
                    @yield('page_header')
                @endif

                @include('layouts.partials.flash')

                @yield('content')
            </main>

            <footer class="cs-app-footer">
                <p>Campus Space</p>
                <p>Kelola ruang. Catat penggunaan. Temukan peluang.</p>
            </footer>
        </div>
    </div>

    {{-- Dialog konfirmasi umum untuk semua <form data-confirm> (hapus, batal, setujui, perbarui QR). --}}
    <x-confirm-dialog id="cs-confirm-dialog" title="Konfirmasi tindakan" />

    {{-- Mode CDN: interaksi (campus-space.js) disisipkan langsung; mode Vite memuatnya lewat app.js. --}}
    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/campus-space.js')) !!}</script>
    @endunless
</body>
</html>
