{{-- C5 · Tambah Pengguna (POST admin.users.store): name, email, role, password, password_confirmation.
     Variabel: $roles User::ROLE_LABELS. --}}
@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('inline_error_fields', 'name,email,role,password')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('admin.users.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke pengguna</a>
            <h1 id="page-title" class="cs-page-title mt-2">Tambah Pengguna</h1>
        </header>

        <form method="POST" action="{{ route('admin.users.store') }}" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @csrf
            @include('admin.users.partials.form', ['user' => null, 'isSelf' => false])

            <div>
                <label for="password" class="cs-form-label">Kata Sandi <span class="cs-form-required" aria-hidden="true">*</span></label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="cs-form-control"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <x-field-error for="password" />
            </div>
            <div>
                <label for="password_confirmation" class="cs-form-label">Ulangi Kata Sandi <span class="cs-form-required" aria-hidden="true">*</span></label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="cs-form-control">
            </div>

            <div class="cs-form-actions">
                <a href="{{ route('admin.users.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </form>
    </section>
@endsection
