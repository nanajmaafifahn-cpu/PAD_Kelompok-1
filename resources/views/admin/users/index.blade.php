{{-- C5 · Kelola Pengguna (admin.users.index, khusus admin)
     Variabel: $users paginator User, $roles User::ROLE_LABELS.
     Akun tidak dihapus (riwayat booking tetap utuh); gunakan status nonaktif di halaman ubah. --}}
@extends('layouts.app')

@section('title', 'Pengguna')

@section('page_header')
    <x-page-header eyebrow="Administrasi" title="Pengguna." crumb="Pengguna"
                   subtitle="Kelola akun dan role. Akun nonaktif tidak bisa login; riwayat peminjamannya tetap tersimpan.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="cs-btn cs-btn-primary">Tambah Pengguna</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')

    <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-label="Daftar pengguna" tabindex="0">
        <table class="cs-table cs-table-stack">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Status</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <th scope="row">{{ $user->name }}</th>
                        <td data-label="Email" class="break-all">{{ $user->email }}</td>
                        <td data-label="Role">{{ $roles[$user->role] ?? $user->role }}</td>
                        <td data-label="Status"><x-status-badge :status="$user->isActive() ? 'active' : 'inactive'" domain="user" /></td>
                        <td data-label="Tindakan"><a href="{{ route('admin.users.edit', $user) }}" class="cs-btn cs-btn-secondary cs-btn-sm">Ubah<span class="sr-only"> {{ $user->email }}</span></a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
