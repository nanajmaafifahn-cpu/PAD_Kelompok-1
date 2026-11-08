{{-- FE-06 · Form Ubah Ruang/Lab
     STATUS INTEGRASI: AKTIF (BE-05, Fase 3):
       GET  rooms.edit    -> RoomController@edit   kirim $room dan $academicUnits ke view ini
       PUT  rooms.update  -> RoomController@update validasi UpdateRoomRequest (code unik kecuali ruang ini)
     Form hanya dirender bila route rooms.update terdaftar (komponen x-route-form, method diambil dari
     definisi route). Selama belum ada, halaman menampilkan keterangan tanpa form/action palsu.
     Variabel:
       $room           Room yang diubah (nilai awal = old() lalu fallback ke data model)
       $academicUnits  collection AcademicUnit --}}
@extends('layouts.app')

@section('title', 'Ubah Ruang ' . $room->code)
@section('inline_error_fields', 'academic_unit_id,code,type,name,building,floor,capacity,status,description,photo')

@section('page_header')
    <x-page-header eyebrow="Data master" title="Ubah ruang {{ $room->name }}." crumb="Ruangan & Foto"
                   subtitle="{{ $room->code }} · Kolom bertanda * wajib diisi.">
        <x-slot:actions>
            <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form ubah ruang">
        @if (Route::has('rooms.update'))
            <x-route-form route="rooms.update" :params="$room" enctype="multipart/form-data" class="cs-card cs-card-body space-y-6" data-cs-validate>
                @include('rooms.partials.form', ['room' => $room])

                <div class="cs-form-actions">
                    <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-ghost">Kembali</a>
                    <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
                </div>
            </x-route-form>
        @else
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Perubahan data ruang belum dapat disimpan karena layanan pembaruan ruang belum tersedia di sistem.</p>
            </div>
            <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar ruang</a>
        @endif
    </section>
@endsection
