{{-- FE-02 / FE-04 · Form Tambah Ruang/Lab
     Variabel dari RoomController@create:
       $academicUnits  collection AcademicUnit
     Dikirim ke route rooms.store (validasi server: StoreRoomRequest).
     Field form ada di rooms/partials/form.blade.php (dipakai juga oleh rooms/edit). --}}
@extends('layouts.app')

@section('title', 'Tambah Ruang')
@section('inline_error_fields', 'academic_unit_id,code,type,name,building,floor,capacity,status,description,photo')

@section('page_header')
    <x-page-header eyebrow="Data master" title="Tambah ruang." crumb="Ruangan & Foto"
                   subtitle="Isi data master ruang. Kolom bertanda * wajib diisi.">
        <x-slot:actions>
            <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-secondary">Kembali ke daftar</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form tambah ruang">
        <form method="POST" action="{{ route('rooms.store') }}" enctype="multipart/form-data" class="cs-card cs-card-body space-y-6" data-cs-validate>
            @csrf

            @include('rooms.partials.form')

            <div class="cs-form-actions">
                <a href="{{ route('rooms.manage') }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan Ruang</button>
            </div>
        </form>
    </section>
@endsection
