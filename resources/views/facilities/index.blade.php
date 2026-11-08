{{-- C4 · Kelola Fasilitas Ruang (rooms.facilities.index, pengelola/admin)
     Variabel dari FacilityController@index:
       $room        Room
       $facilities  collection Facility milik ruang ini
       $statuses    Facility::STATUS_LABELS (available => Baik, damaged => Rusak, maintenance => Perawatan)
     Tambah: POST rooms.facilities.store (name, quantity, status). Ubah: GET facilities.edit. Hapus: DELETE facilities.destroy. --}}
@extends('layouts.app')

@section('title', 'Fasilitas ' . $room->name)
@section('inline_error_fields', 'name,quantity,status')

@section('page_header')
    <x-page-header eyebrow="Data master" title="Fasilitas {{ $room->name }}." crumb="Fasilitas"
                   subtitle="{{ $room->code }} · {{ $room->building }}. Catat jumlah dan kondisi setiap fasilitas ruang.">
        <x-slot:actions>
            <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-secondary">Kembali ke Ruangan &amp; Foto</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2" aria-labelledby="facility-list-heading">
            <h2 id="facility-list-heading" class="sr-only">Daftar fasilitas</h2>
            <div class="cs-table-wrap cs-table-wrap-stack" role="region" aria-labelledby="facility-list-heading" tabindex="0">
                <table class="cs-table cs-table-stack">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col" class="text-right">Jumlah</th>
                            <th scope="col">Kondisi</th>
                            <th scope="col">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($facilities as $facility)
                            <tr>
                                <th scope="row">{{ $facility->name }}</th>
                                <td data-label="Jumlah" class="cs-cell-num">{{ $facility->getQuantity() }}</td>
                                <td data-label="Kondisi"><x-status-badge :status="$facility->getStatus()" domain="facility" /></td>
                                <td data-label="Tindakan">
                                    <div class="cs-actions">
                                        <a href="{{ route('facilities.edit', $facility) }}" class="cs-btn cs-btn-secondary cs-btn-sm">
                                            Ubah<span class="sr-only"> {{ $facility->name }}</span>
                                        </a>
                                        <x-route-form route="facilities.destroy" :params="$facility" confirm="Hapus fasilitas {{ $facility->name }}?">
                                            <button type="submit" class="cs-btn cs-btn-danger cs-btn-sm">
                                                Hapus<span class="sr-only"> {{ $facility->name }}</span>
                                            </button>
                                        </x-route-form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"><div class="cs-empty-state"><p class="cs-empty-state-title">Belum ada fasilitas.</p></div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <form method="POST" action="{{ route('rooms.facilities.store', $room) }}" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @csrf
            <h2 class="cs-card-title">Tambah fasilitas</h2>
            @include('facilities.partials.form', ['facility' => null])
            <button type="submit" class="cs-btn cs-btn-primary">Tambah</button>
        </form>
    </div>
@endsection
