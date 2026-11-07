{{-- C5 · Ubah Pengguna (PUT admin.users.update): name, email, role, is_active, password (opsional).
     Variabel: $user, $roles, $isSelf. Admin tidak boleh menurunkan role / menonaktifkan diri sendiri (UpdateUserRequest). --}}
@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('inline_error_fields', 'name,email,role,is_active,password')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('admin.users.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke pengguna</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah {{ $user->name }}</h1>
        </header>

        <x-route-form route="admin.users.update" :params="$user" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('admin.users.partials.form', ['user' => $user, 'isSelf' => $isSelf])

            <div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->isActive()))>
                    Akun aktif (bisa login)
                </label>
                <x-field-error for="is_active" />
            </div>

            <fieldset class="cs-fieldset">
                <legend class="cs-legend">Ganti kata sandi (opsional)</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="cs-form-label">Kata sandi baru</label>
                        <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" class="cs-form-control"
                               @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        <x-field-error for="password" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="cs-form-label">Ulangi kata sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="cs-form-control">
                    </div>
                </div>
            </fieldset>

            <div class="cs-form-actions">
                <a href="{{ route('admin.users.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </x-route-form>
    </section>
@endsection
