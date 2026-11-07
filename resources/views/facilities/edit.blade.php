{{-- C4 · Ubah Fasilitas (facilities.edit -> PUT facilities.update).
     Variabel: $facility (relasi room), $statuses. --}}
@extends('layouts.app')

@section('title', 'Ubah Fasilitas')
@section('inline_error_fields', 'name,quantity,status')

@section('content')
    <section class="cs-container-narrow" aria-labelledby="page-title">
        <header class="mb-6">
            <a href="{{ route('rooms.facilities.index', $facility->room_id) }}" class="cs-back-link">
                <span aria-hidden="true">←</span> Kembali ke fasilitas {{ $facility->room?->name }}
            </a>
            <h1 id="page-title" class="cs-page-title mt-2">Ubah Fasilitas</h1>
        </header>

        <x-route-form route="facilities.update" :params="$facility" class="cs-card cs-card-body space-y-4" data-cs-validate>
            @include('facilities.partials.form', ['facility' => $facility])
            <div class="cs-form-actions">
                <a href="{{ route('rooms.facilities.index', $facility->room_id) }}" class="cs-btn cs-btn-ghost">Batal</a>
                <button type="submit" class="cs-btn cs-btn-primary">Simpan</button>
            </div>
        </x-route-form>
    </section>
@endsection
