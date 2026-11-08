{{-- Halaman daftar akun. POST register.store (RegisterController@store),
     field: name, email, password, password_confirmation. Role selalu borrower (Mahasiswa / Dosen). --}}
@extends('layouts.guest')

@section('title', 'Daftar')
@section('inline_error_fields', 'name,email,password')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="cs-card cs-card-body space-y-5">
        @csrf
        <div>
            <h1 class="cs-page-title">Daftar Akun</h1>
            <p class="cs-page-description">Akun baru terdaftar sebagai Mahasiswa / Dosen (peminjam ruang).</p>
        </div>

        <div>
            <label for="name" class="cs-form-label">Nama Lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="150" autocomplete="name"
                   class="cs-form-control" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            <x-field-error for="name" />
        </div>

        <div>
            <label for="email" class="cs-form-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                   class="cs-form-control" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <x-field-error for="email" />
        </div>

        <div>
            <label for="password" class="cs-form-label">Kata Sandi</label>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                   class="cs-form-control" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <p class="cs-form-hint">Minimal 8 karakter.</p>
            <x-field-error for="password" />
        </div>

        <div>
            <label for="password_confirmation" class="cs-form-label">Ulangi Kata Sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                   class="cs-form-control">
        </div>

        <button type="submit" class="cs-btn cs-btn-primary w-full" data-loading-text="Memproses...">Daftar</button>

        <p class="text-sm text-ink-muted">
            Sudah punya akun? <a href="{{ route('login') }}" class="cs-link">Masuk</a>
        </p>
    </form>
@endsection
