{{-- FE-02 / FE-04 · Form Tambah Ruang/Lab
     Variabel dari RoomController@create:
       $academicUnits  collection AcademicUnit
     Dikirim ke route rooms.store (validasi server: StoreRoomRequest).
     Field form ada di rooms/partials/form.blade.php (dipakai juga oleh rooms/edit). --}}
@extends('layouts.app')

@section('title', 'Tambah Ruang')
@section('inline_error_fields', 'academic_unit_id,code,name,building,floor,capacity,status,description')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('rooms.index') }}" class="cs-back-link"><span aria-hidden="true">←</span> Kembali ke daftar ruang</a>
            <h1 id="page-title" class="cs-page-title mt-2">Tambah Ruang / Laboratorium</h1>
            <p class="cs-page-description">
                Isi data master ruang. Kolom bertanda <span class="cs-form-required" aria-hidden="true">*</span> wajib diisi.
            </p>
        </header>

        <form method="POST" action="{{ route('rooms.store') }}" class="cs-card cs-card-body space-y-6" data-cs-validate>
            @csrf

            @include('rooms.partials.form')

            <div class="cs-form-actions">
                <a href="{{ route('rooms.index') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan Ruang</button>
            </div>
        </form>
    </section>
@endsection
