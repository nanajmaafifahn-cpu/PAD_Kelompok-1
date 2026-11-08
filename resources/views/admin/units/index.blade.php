{{-- C5 · Kelola Unit Akademik (admin.units.index, khusus admin)
     Variabel: $units collection AcademicUnit (dengan rooms_count).
     Tambah: POST admin.units.store (code, name, description). Ubah: admin.units.edit. Hapus: DELETE admin.units.destroy
     (ditolak bila masih dipakai ruang). --}}
@extends('layouts.app')

@section('title', 'Unit Akademik')
@section('inline_error_fields', 'code,name,description')

@section('page_header')
    <x-page-header eyebrow="Administrasi" title="Unit akademik." crumb="Unit Akademik"
                   subtitle="Fakultas atau jurusan pemilik ruang. Unit yang masih dipakai ruang tidak dapat dihapus." />
@endsection

@section('content')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="cs-table-wrap cs-table-wrap-stack lg:col-span-2" role="region" aria-label="Daftar unit akademik" tabindex="0">
            <table class="cs-table cs-table-stack">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Nama</th>
                        <th scope="col" class="text-right">Ruang</th>
                        <th scope="col">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td data-label="Kode"><span class="cs-code">{{ $unit->code }}</span></td>
                            <th scope="row">{{ $unit->name }}</th>
                            <td data-label="Ruang" class="cs-cell-num">{{ $unit->rooms_count }}</td>
                            <td data-label="Tindakan">
                                <div class="cs-actions">
                                    <a href="{{ route('admin.units.edit', $unit) }}" class="cs-btn cs-btn-secondary cs-btn-sm">Ubah</a>
                                    @if ($unit->rooms_count === 0)
                                        <x-route-form route="admin.units.destroy" :params="$unit" confirm="Hapus unit {{ $unit->code }}?">
                                            <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">Hapus</button>
                                        </x-route-form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="cs-empty-state"><p class="cs-empty-state-title">Belum ada unit akademik.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.units.store') }}" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @csrf
            <h2 class="cs-card-title">Tambah unit</h2>
            @include('admin.units.partials.form', ['unit' => null])
            <button type="submit" class="cs-btn cs-btn-primary">Tambah</button>
        </form>
    </div>
@endsection
