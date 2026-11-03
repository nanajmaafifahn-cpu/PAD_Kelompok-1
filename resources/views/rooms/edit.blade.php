{{-- FE-06 · Form Ubah Ruang/Lab
     STATUS INTEGRASI: BLOCKED — backend belum menyediakan route & method berikut (usulan BE-05 di KONTRAK-VIEW.md):
       GET  rooms.edit    -> RoomController@edit   kirim $room dan $academicUnits ke view ini
       PUT  rooms.update  -> RoomController@update validasi UpdateRoomRequest (code unik kecuali ruang ini)
     Form hanya dirender bila route rooms.update terdaftar (komponen x-route-form, method diambil dari
     definisi route). Selama belum ada, halaman menampilkan keterangan tanpa form/action palsu.
     Variabel:
       $room           Room yang diubah (nilai awal = old() lalu fallback ke data model)
       $academicUnits  collection AcademicUnit --}}
@extends('layouts.app')

@section('title', 'Ubah Ruang ' . $room->code)
@section('inline_error_fields', 'academic_unit_id,code,name,building,floor,capacity,status,description')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('rooms.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar ruang</a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah Ruang {{ $room->code }}</h1>
            <p class="cs-page-description">
                Perbarui data master {{ $room->name }}. Kolom bertanda <span class="cs-form-required" aria-hidden="true">*</span> wajib diisi.
            </p>
        </header>

        @if (Route::has('rooms.update'))
            <x-route-form route="rooms.update" :params="$room" class="cs-card cs-card-body space-y-6" data-cs-validate>
                @include('rooms.partials.form', ['room' => $room])

                <div class="cs-form-actions">
                    <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-ghost">Kembali</a>
                    <button type="submit" class="cs-btn cs-btn-primary">Simpan Perubahan</button>
                </div>
            </x-route-form>
        @else
            <div role="status" class="cs-alert cs-alert-warning">
                <span aria-hidden="true" class="cs-alert-icon">!</span>
                <p>Perubahan data ruang belum dapat disimpan karena layanan pembaruan ruang belum tersedia di sistem.</p>
            </div>
            <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar ruang</a>
        @endif
    </section>
@endsection
