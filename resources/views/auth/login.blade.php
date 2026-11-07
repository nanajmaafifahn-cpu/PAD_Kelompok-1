{{-- Halaman login. POST login.store (LoginController@store), field: email, password, remember.
     Galat "throttle" (lebih dari 5 percobaan/menit) tampil di ringkasan galat layout. --}}
@extends('layouts.guest')

@section('title', 'Masuk')
@section('inline_error_fields', 'email,password')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="cs-card cs-card-body space-y-5">
        @csrf
        <h1 class="cs-page-title">Masuk</h1>

        <div>
            <label for="email" class="cs-form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="cs-form-control" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <x-field-error for="email" />
        </div>

        <div>
            <label for="password" class="cs-form-label">Kata Sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="cs-form-control" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <x-field-error for="password" />
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Ingat saya
        </label>

        <button type="submit" class="cs-btn cs-btn-primary w-full">Masuk</button>

        <p class="text-sm text-ink-muted">
            Belum punya akun? <a href="{{ route('register') }}" class="cs-link">Daftar sebagai Mahasiswa / Dosen</a>
        </p>
    </form>
@endsection
