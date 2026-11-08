{{-- C4 · Ubah Fasilitas (facilities.edit -> PUT facilities.update).
     Variabel: $facility (relasi room), $statuses. --}}
@extends('layouts.app')

@section('title', 'Ubah Fasilitas')
@section('inline_error_fields', 'name,quantity,status')

@section('page_header')
    <x-page-header eyebrow="Data master" title="Ubah fasilitas." crumb="Fasilitas"
                   subtitle="{{ $facility->name }} · {{ $facility->room?->name }}">
        <x-slot:actions>
            <a href="{{ route('rooms.facilities.index', $facility->room_id) }}" class="cs-btn cs-btn-secondary">Kembali ke fasilitas ruang</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <section class="cs-container-narrow ml-0" aria-label="Form ubah fasilitas">

        <x-route-form route="facilities.update" :params="$facility" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('facilities.partials.form', ['facility' => $facility])
            <div class="cs-form-actions">
                <a href="{{ route('rooms.facilities.index', $facility->room_id) }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </x-route-form>
    </section>
@endsection
