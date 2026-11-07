{{-- C5 · Kelola Pengguna (admin.users.index, khusus admin)
     Variabel: $users paginator User, $roles User::ROLE_LABELS.
     Akun tidak dihapus (riwayat booking tetap utuh); gunakan status nonaktif di halaman ubah. --}}
@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <header class="cs-page-header">
        <div>
            <h1 class="cs-page-title">Pengguna</h1>
            <p class="cs-page-description">Kelola akun dan role. Akun nonaktif tidak bisa login.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="cs-btn cs-btn-primary"><span aria-hidden="true">+</span> Tambah Pengguna</a>
    </header>

    <div class="cs-table-wrap" role="region" aria-label="Daftar pengguna" tabindex="0">
        <table class="cs-table">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <th scope="row">{{ $user->name }}</th>
                        <td>{{ $user->email }}</td>
                        <td>{{ $roles[$user->role] ?? $user->role }}</td>
                        <td><x-status-badge :status="$user->isActive() ? 'active' : 'inactive'" /></td>
                        <td><a href="{{ route('admin.users.edit', $user) }}" class="cs-btn cs-btn-secondary cs-btn-sm">Ubah<span class="sr-only"> {{ $user->email }}</span></a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
