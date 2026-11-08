{{-- Kerangka halaman galat HTTP (dipakai otomatis oleh Laravel untuk errors/{kode}.blade.php).
     Memakai layout tamu agar tetap bergaya Campus Space dan berbahasa Indonesia.
     Section: code, title, message (pesan bawaan bila exception tidak membawa pesan sendiri). --}}
@extends('layouts.guest')

@section('content')
    @php
        // Hanya abort(403, '...') aplikasi (middleware role) yang membawa pesan berbahasa Indonesia.
        // Pesan exception lain berasal dari framework (Inggris, mis. "The route ... could not be found.") sehingga diganti teks halaman.
        $custom = (isset($exception) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() === 403)
            ? trim($exception->getMessage()) : '';
        $message = ($custom !== '' && $custom !== 'This action is unauthorized.') ? $custom : trim($__env->yieldContent('message'));
    @endphp

    <div class="cs-card cs-card-body space-y-4">
        <p class="cs-eyebrow">Galat @yield('code')</p>
        <h1 class="cs-page-title">@yield('title')</h1>
        <p class="cs-page-description">{{ $message }}</p>

        <div class="flex flex-wrap gap-3 pt-2">
            @auth
                <a href="{{ route('dashboard') }}" class="cs-btn cs-btn-primary">Kembali ke Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="cs-btn cs-btn-primary">Masuk</a>
            @endauth
            <a href="{{ url()->previous() }}" class="cs-btn cs-btn-secondary">Halaman sebelumnya</a>
        </div>
    </div>
@endsection
